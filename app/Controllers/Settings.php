<?php

namespace App\Controllers;

use App\Models\SettingsModel;
use App\Models\DashboardModel;
use CodeIgniter\Files\File;

class Settings extends BaseController
{
    protected $settings;
    protected $dashboard;

    public function __construct()
    {
        $this->settings = new SettingsModel();
        $this->dashboard = new DashboardModel();
        helper(['form', 'filesystem', 'text', 'number']);
    }

    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $user = $this->getUser();
        $settingModel = new \App\Models\AttendanceSettingModel();
        $locationModel = new \App\Models\AttendanceLocationModel();
        $db = \Config\Database::connect();

        $absenSetting = $settingModel->first();
        if (!$absenSetting) {
            $settingModel->insert([
                'jam_masuk' => '07:30:00',
                'jam_pulang' => '15:00:00',
                'radius_meter' => 100
            ]);
            $absenSetting = $settingModel->first();
        }

        $locations = $locationModel->findAll();
        $jadwal = $db->table('absensi_jadwal_kerja')->orderBy('hari', 'ASC')->get()->getResult();

        $this->ensureNomorSettingColumns();
        \App\Libraries\SuratNomor::flushCache();

        $appSetting = $this->dashboard->getSetting();
        if (!$appSetting) {
            $appSetting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        $data = [
            'title'        => 'Pengaturan & Konfigurasi Sistem',
            'judul'        => 'Pengaturan & Konfigurasi Sistem',
            'subjudul'     => 'Profil Sekolah & Kebijakan Presensi',
            'user'         => $user,
            'setting'      => $appSetting,
            'tp'           => $this->dashboard->getTahun(),
            'tp_active'    => $this->dashboard->getTahunActive(),
            'smt'          => $this->dashboard->getSemester(),
            'smt_active'   => $this->dashboard->getSemesterActive(),
            'absenSetting' => $absenSetting,
            'locations'    => $locations,
            'jadwal'       => $jadwal,
            'theme'        => \App\Libraries\Theme::current(),
            'themePresets' => \App\Libraries\Theme::presetOptions(),
        ];

        return view('admin/presensi/setting', $data);
    }

    public function dbManager()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return redirect()->to('auth');
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Backup dan Restore',
            'subjudul' => 'Backup dan Restore',
        ];

        $data['tp'] = $this->dashboard->getTahun();
        $data['tp_active'] = $this->dashboard->getTahunActive();
        $data['smt'] = $this->dashboard->getSemester();
        $data['smt_active'] = $this->dashboard->getSemesterActive();
        $data['setting'] = $this->settings->getSetting();
        // Arsip backup disimpan di writable/backups (di luar webroot) dan
        // diunduh lewat downloadBackup(), bukan sebagai file publik.
        $backupPath = WRITEPATH . 'backups/';
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        $list = [];
        $totalBytes = 0;
        $dbCount = 0;
        $fileCount = 0;
        $lastBackup = null;
        foreach (glob($backupPath . '*') ?: [] as $filepath) {
            if (!is_file($filepath)) {
                continue;
            }
            $filename = basename($filepath);
            if ($filename === 'index.html' || $filename === '.htaccess') {
                continue;
            }
            $info = pathinfo($filename);
            $bytes = (int) filesize($filepath);
            $mtime = (int) filemtime($filepath);
            $totalBytes += $bytes;
            if ($lastBackup === null || $mtime > $lastBackup) {
                $lastBackup = $mtime;
            }
            $lower = strtolower($filename);
            $isDb = (str_ends_with($lower, '.sql') || str_contains($lower, '.sql.'))
                && !str_starts_with($lower, 'files_backup_');
            if (str_starts_with($lower, 'db_backup_') || $isDb) {
                $kind = 'database';
                $dbCount++;
            } else {
                $kind = 'file';
                $fileCount++;
            }
            $list[] = [
                'nama'       => $info['filename'] ?? $filename,
                'type'       => $info['extension'] ?? '',
                'size'       => number_to_size($bytes, 2),
                'size_bytes' => $bytes,
                'tgl'        => $mtime,
                'src'        => $filename,
                'kind'       => $kind,
                'is_sql'     => str_ends_with($lower, '.sql'),
            ];
        }
        usort($list, static fn ($a, $b) => $b['tgl'] <=> $a['tgl']);
        $data['list'] = $list;
        $data['stats'] = [
            'total'       => count($list),
            'db'          => $dbCount,
            'file'        => $fileCount,
            'total_bytes' => $totalBytes,
            'total_size'  => count($list) > 0 ? number_to_size($totalBytes, 2) : '0 KB',
            'last'        => $lastBackup,
        ];

        return view('setting/db', $data);
    }

    /**
     * Field media yang boleh diunggah/dihapus via endpoint ini.
     */
    private const MEDIA_FIELDS = [
        'logo_kanan',
        'logo_kiri',
        'stampel',
        'tanda_tangan',
        'ttdproktor',
        'kodescan',
        'kopsekolah',
        'banner1',
        'banner2',
        'banner3'
    ];

    /**
     * Ubah src (URL/relatif) menjadi path relatif aman di dalam uploads/settings/.
     * Return null bila di luar direktori yang diizinkan (cegah path traversal).
     */
    private function resolveMediaPath(?string $src): ?string
    {
        if ($src === null || $src === '') return null;
        $rel = ltrim(str_replace(base_url(), '', $src), '/');
        $rel = str_replace('\\', '/', $rel);
        if (preg_match('#^https?://#i', $rel)) return null;
        if (strpos($rel, '..') !== false) return null;
        if (strpos($rel, 'uploads/settings/') !== 0) return null;
        $abs = realpath(ROOTPATH . 'public/' . $rel);
        $root = realpath(ROOTPATH . 'public/uploads/settings');
        if ($abs === false || $root === false || strpos($abs, $root) !== 0 || !is_file($abs)) {
            return null;
        }
        // ROOTPATH bisa berakhir '/' (Linux) atau '\' (Windows): potong dari akar
        // yang sudah dibersihkan lalu paksa separator maju agar cocok dengan
        // nilai di kolom settings.
        $out = substr($abs, strlen(rtrim(ROOTPATH . 'public/', '/\\')) + 1);
        $out = str_replace('\\', '/', $out);
        return strpos($out, 'uploads/settings/') === 0 ? $out : null;
    }

    public function uploadFile($logo)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (!in_array($logo, self::MEDIA_FIELDS, true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Field media tidak valid']);
        }

        $file = $this->request->getFile('logo');
        $validationRule = [
            'logo' => [
                'label' => 'Image File',
                'rules' => 'uploaded[logo]|is_image[logo]|mime_in[logo,image/jpg,image/jpeg,image/gif,image/png,image/webp]|max_size[logo,5120]'
            ],
        ];

        if (!$this->validate($validationRule)) {
            $data = [
                'status' => false,
                'src' => $this->validator->getErrors()['logo'] ?? 'Invalid file',
                'message' => 'Upload failed'
            ];
        } else {
            if ($file->isValid() && !$file->hasMoved()) {
                $uploadPath = ROOTPATH . 'public/uploads/settings/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Ekstensi dari MIME tervalidasi (jangan percaya nama file client → cegah .php)
                $mimeMap = [
                    'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
                    'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
                ];
                $ext = $mimeMap[$file->getMimeType()] ?? null;
                if ($ext === null) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Tipe file tidak didukung']);
                }

                $fileName = $logo . '.' . $ext;

                // Pindahkan file baru DULU, baru bersihkan varian lama.
                // Urutan terbalik (hapus dulu, move tanpa diperiksa) membuat
                // gangguan apa pun pada move() meninggalkan sekolah tanpa logo
                // sama sekali sementara respons tetap berkata "berhasil".
                try {
                    $pindah = $file->move($uploadPath, $fileName, true);
                } catch (\Throwable $e) {
                    log_message('error', '[Settings::uploadFile] move ' . $fileName . ' gagal: ' . $e->getMessage());
                    $pindah = false;
                }

                $tujuan = $uploadPath . $fileName;
                if ($pindah !== true || !is_file($tujuan) || (int) @filesize($tujuan) === 0) {
                    return $this->response->setJSON([
                        'status' => false,
                        'message' => 'Gambar gagal disimpan ke server. Logo lama belum diganti — coba lagi, dan bila berulang periksa izin folder public/uploads/settings.',
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
                }

                // Hapus file lama field ini dengan ekstensi apapun agar tidak menumpuk
                $existen = str_replace('\\', '/', $uploadPath) . $fileName;
                foreach ((glob($uploadPath . $logo . '.*') ?: []) as $ef) {
                    $efN = str_replace('\\', '/', $ef);
                    if (is_file($ef) && $efN !== $existen) {
                        @unlink($ef);
                    }
                }

                $relativePath = 'uploads/settings/' . $fileName;

                if ($this->settings->updateSetting([$logo => $relativePath]) !== true) {
                    log_message('error', '[Settings::uploadFile] simpan path ' . $relativePath . ' ke setting gagal');
                    return $this->response->setJSON([
                        'status' => false,
                        'message' => 'Gambar sudah tersimpan tapi penamaan di pengaturan gagal diperbarui. Logo yang tampil bisa masih yang lama — simpan ulang.',
                        'src' => base_url($relativePath),
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
                }

                $data = [
                    'status' => true,
                    'src' => base_url($relativePath),
                    'relative_path' => $relativePath,
                    'filename' => $fileName,
                    'type' => $file->getClientMimeType(),
                    'size' => $file->getSize()
                ];

            } else {
                $data = [
                    'status' => false,
                    'src' => $file->getErrorString(),
                    'message' => 'File move failed'
                ];
            }
        }

        $data['csrfName'] = csrf_token();
        $data['csrfHash'] = csrf_hash();

        return $this->response->setJSON($data);
    }

    public function deleteFile()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $src = $this->request->getPost('src');
        $field = $this->request->getPost('field');

        if (empty($field) || !in_array($field, self::MEDIA_FIELDS, true)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Field media tidak valid',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $deletedFile = false;
        if (!empty($src)) {
            $rel = $this->resolveMediaPath($src);
            if ($rel !== null && is_file(ROOTPATH . 'public/' . $rel)) {
                @unlink(ROOTPATH . 'public/' . $rel);
                $deletedFile = true;
            }
        }

        // Hapus juga file fisik yang ada di direktori settings dengan prefix nama field
        $uploadPath = ROOTPATH . 'public/uploads/settings/';
        $existing = glob($uploadPath . $field . '.*');
        if (!empty($existing)) {
            foreach ($existing as $oldFile) {
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                    $deletedFile = true;
                }
            }
        }

        // Selalu sinkronkan DB agar kolom media dikosongkan
        $dbTersimpan = $this->settings->updateSetting([$field => '']);

        if (!$dbTersimpan) {
            log_message('error', '[Settings::deleteFile] kosongkan kolom ' . $field . ' di setting gagal');
        }

        return $this->response->setJSON([
            'status' => $dbTersimpan,
            'message' => $dbTersimpan
                ? ($deletedFile ? 'File media dihapus.' : 'Kolom media dikosongkan (file sudah tidak ada di server).')
                : 'File di server sudah dihapus, tapi datanya di pengaturan gagal dibersihkan. Isi form pengaturan lalu simpan lagi untuk membereskan.',
            'fileDeleted' => $deletedFile,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Kanal WhatsApp aktif hanya boleh salah satu dari tiga nilai.
     * Nilai tak dikenal turunkan ke 'off' — lebih aman daripada menebak
     * dan diam-diam mengirim ke gateway yang tidak diinginkan.
     */
    private function normalizeWaMode($mode): string
    {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, ['off', 'openwa', 'fonnte'], true) ? $mode : 'off';
    }

    private function normalizeMediaPath($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $normalized = str_replace(base_url(), '', $path);
        return ltrim($normalized, '/');
    }

    public function saveSetting()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $input = $this->request->getPost();

        // Handle ba_waktu transformation
        $ba_waktu = null;
        if (!empty($input['ba_waktu'])) {
            // Usually datetime-local input sends format like '2025-01-01T12:00'
            // We want to store as 'Y-m-d H:i:s'
            try {
                $dt = new \DateTime($input['ba_waktu']);
                $ba_waktu = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Ignore parsing error or set to null
            }
        }

        // Sanitize phone and postal code
        $telp = isset($input['tlp']) ? preg_replace('/[^0-9]/', '', $input['tlp']) : null;
        $kodepos = isset($input['kode_pos']) ? preg_replace('/[^0-9]/', '', $input['kode_pos']) : null;

        $updateData = [
            'nama_aplikasi' => $input['nama_aplikasi'] ?? '',
            'web' => $input['web'] ?? '',
            'fax' => $input['fax'] ?? '',
            'email' => $input['email'] ?? '',
            'telp' => $telp,
            'kepsek' => $input['kepsek'] ?? '',
            'nip' => $input['nip'] ?? '',
            'proktor' => $input['proktor'] ?? '',
            'nip_proktor' => $input['nip_proktor'] ?? '',
            'tkn_siswa' => $input['tkn_siswa'] ?? '',
            'mode_app' => $input['mode_app'] ?? '',
            'api_key' => $input['api_key'] ?? '',
            'ba_aktif' => $input['ba_aktif'] ?? '0',
            'ba_waktu' => $ba_waktu,
            'jenjang' => $input['jenjang'] ?? '',
            'satuan_pendidikan' => $input['satuan_pendidikan'] ?? '',
            'sekolah' => $input['nama_sekolah'] ?? '',
            'nss' => $input['nss'] ?? '',
            'npsn' => $input['npsn'] ?? '',
            // Penomoran Surat Teguran
            'kode_sekolah' => trim((string) ($input['kode_sekolah'] ?? '')),
            'surat_teguran_format' => \App\Libraries\SuratNomor::sanitizeTemplate($input['surat_teguran_format'] ?? null),
            'surat_teguran_padding' => $this->normalizeTeguranPadding($input['surat_teguran_padding'] ?? null),
            'alamat' => $input['alamat'] ?? '',
            'desa' => $input['desa'] ?? '',
            'kecamatan' => $input['kec'] ?? '',
            'kota' => $input['kota'] ?? '',
            'provinsi' => $input['provinsi'] ?? '',
            'kode_pos' => $kodepos,
            // Notification Settings
            'telegram_bot_token' => $input['telegram_bot_token'] ?? '',
            'telegram_chat_id' => $input['telegram_chat_id'] ?? '',
            'wa_gateway_url' => $input['wa_gateway_url'] ?? '',
            'wa_token' => $input['wa_token'] ?? '',
            'wa_target_number' => $input['wa_target_number'] ?? '',
            'wa_gateway_mode' => $this->normalizeWaMode($input['wa_gateway_mode'] ?? ''),
            'wa_base_url' => trim((string) ($input['wa_base_url'] ?? '')),
            'wa_api_key' => trim((string) ($input['wa_api_key'] ?? '')),
            'wa_session_name' => trim((string) ($input['wa_session_name'] ?? '')),
            // MikroTik Settings
            'mikrotik_host' => $input['mikrotik_host'] ?? '',
            'mikrotik_port' => $input['mikrotik_port'] ?? '8728',
            'mikrotik_user' => $input['mikrotik_user'] ?? '',
            'mikrotik_pass' => $input['mikrotik_pass'] ?? '',
        ];

        // HANYA perbarui field media jika memang ada di input POST (mencegah form profil menghapus path gambar di DB)
        foreach (self::MEDIA_FIELDS as $mf) {
            if (array_key_exists($mf, $input)) {
                $updateData[$mf] = $this->normalizeMediaPath($input[$mf]);
            }
        }

        $this->ensureNomorSettingColumns();
        $updateData = $this->filterExistingSettingFields($updateData);

        $status = $this->settings->updateSetting($updateData);
        \App\Libraries\SuratNomor::flushCache();
        // Base URL / API Key / nama sesi bisa berubah; resolutionsesi OpenWA
        // di-cache 60 detik dan harus tidak menunjuk gateway lama.
        (new \App\Services\OpenWAService())->flushCache();

        // Status sinkronisasi notifikasi ikut dilaporkan, supaya "Gagal" di
        // halaman profil tidak lagi menyembunyikan fakta bahwa kolom Telegram/WA
        // tidak pernah sampai ke absensi_setting yang dibaca NotificationService.
        $syncNotif = 'tidak_ada_perubahan';

        // Sinkronkan kolom notifikasi ke absensi_setting (sumber tunggal yang
        // dibaca NotificationService). SettingsModel::allowedFields TIDAK memuat
        // kolom-kolom ini sehingga tanpa sync ini konfigurasi Telegram/WA dari
        // halaman Profil Sekolah diam-diam tidak tersimpan.
        $notifKeys = [
            'telegram_bot_token', 'telegram_chat_id',
            'wa_gateway_url', 'wa_token', 'wa_target_number',
            'wa_gateway_mode', 'wa_base_url', 'wa_api_key', 'wa_session_name',
        ];
        $notifData = [];
        foreach ($notifKeys as $k) {
            if (array_key_exists($k, $input)) {
                $v = is_string($input[$k]) ? trim($input[$k]) : $input[$k];
                // Kanal WA disamakan dengan nilai di form: mode tak dikenal
                // turun ke 'off'. Tanpa ini absensi_setting (sumber yang dibaca
                // NotificationService) bisa berisi 'modeAneh' dan menebak
                // gateway mana yang dipakai.
                if ($k === 'wa_gateway_mode') {
                    $v = $this->normalizeWaMode($v);
                }
                $notifData[$k] = $v;
            }
        }
        if ($notifData !== []) {
            try {
                $db = \Config\Database::connect();
                if ($db->tableExists('absensi_setting')) {
                    $sync = [];
                    foreach ($notifData as $k => $v) {
                        if ($db->fieldExists($k, 'absensi_setting')) {
                            $sync[$k] = $v;
                        }
                    }
                    if ($sync !== []) {
                        $mainId = $this->mainAbsensiSettingId($db);
                        if ($mainId > 0) {
                            $tersimpan = $db->table('absensi_setting')->where('id', $mainId)->update($sync);
                        } else {
                            $tersimpan = $db->table('absensi_setting')->insert($sync) !== false;
                        }
                        $syncNotif = $tersimpan === false ? 'gagal' : 'ok';
                    } else {
                        $syncNotif = 'kolom_tidak_ada';
                    }
                } else {
                    $syncNotif = 'tabel_tidak_ada';
                }
            } catch (\Throwable $e) {
                $syncNotif = 'gagal';
                log_message('error', '[Settings::saveSetting] sync notifikasi gagal: ' . $e->getMessage());
            }
        }

        // Dukung pembaruan grace_period jika dikirim ke endpoint ini
        $graceTersimpan = null;
        $dendaTersimpan = null;
        if (isset($input['grace_period'])) {
            $db = \Config\Database::connect();
            $gp = (int) $input['grace_period'];
            $mainId = $db->tableExists('absensi_setting') ? $this->mainAbsensiSettingId($db) : 0;
            if ($mainId > 0) {
                $graceTersimpan = $db->table('absensi_setting')->where('id', $mainId)->update(['grace_period' => $gp]);
            } else {
                $graceTersimpan = false;
                log_message('error', '[Settings::saveSetting] grace_period tidak disimpan: baris utama absensi_setting tidak ditemukan');
            }

            try {
                (new \App\Controllers\Admin\PresensiAdmin())->doRecalculateDate(date('Y-m-d'));
            } catch (\Throwable $e) {
                log_message('error', '[Settings::saveSetting] doRecalculateDate error: ' . $e->getMessage());
            }
        }

        if (isset($input['denda_alpha_per_hari'])) {
            $db = \Config\Database::connect();
            $alphaData = [
                'denda_alpha_per_hari' => (int) $input['denda_alpha_per_hari'],
            ];
            $mainId = $db->tableExists('absensi_setting') ? $this->mainAbsensiSettingId($db) : 0;
            if ($mainId > 0) {
                $dendaTersimpan = $db->table('absensi_setting')->where('id', $mainId)->update($alphaData);
            } else {
                $dendaTersimpan = false;
                log_message('error', '[Settings::saveSetting] denda_alpha_per_hari tidak disimpan: baris utama absensi_setting tidak ditemukan');
            }
        }

        if (!$status) {
            log_message('error', '[Settings::saveSetting] updateSetting gagal');
        }

        $pesan = $status
            ? 'Pengaturan disimpan.'
            : 'Pengaturan GAGAL disimpan — data yang tampil di halaman ini belum tersimpan di database.';
        $catatan = [];
        if ($graceTersimpan === false) {
            $catatan[] = 'batas waktu telat (grace period)';
        }
        if ($dendaTersimpan === false) {
            $catatan[] = 'denda Alpha per hari';
        }
        if (in_array($syncNotif, ['gagal', 'tabel_tidak_ada', 'kolom_tidak_ada'], true)) {
            $catatan[] = 'pengaturan notifikasi Telegram/WhatsApp';
        }
        if ($catatan !== []) {
            $pesan .= ' Bagian yang TIDAK tersimpan: ' . implode(', ', $catatan) . '.';
        }

        return $this->response->setJSON([
            'status' => $status && $catatan === [],
            'message' => $pesan,
            'notifSync' => $syncNotif,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Baris utama absensi_setting = id terkecil, konvensi yang sama dengan
     * AttendanceSettingModel::first(). Update tanpa WHERE dulu menyapu semua
     * baris, termasuk duplikat sisa migrate yang berjalan ulang.
     */
    private function mainAbsensiSettingId($db): int
    {
        try {
            $row = $db->table('absensi_setting')->select('id')
                ->orderBy('id', 'ASC')->limit(1)->get()->getRow();
            return (int) ($row->id ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Simpan preferensi tema tampilan (Theme Customizer).
     */
    public function saveTheme()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $input = null;
        if (stripos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            try {
                $input = $this->request->getJSON(true);
            } catch (\Throwable $e) {
                $input = null;
            }
        }
        if (!is_array($input)) {
            $input = $this->request->getPost();
        }

        $preset = strtolower(trim((string) ($input['preset'] ?? \App\Libraries\Theme::DEFAULT_PRESET)));
        if (!in_array($preset, \App\Libraries\Theme::presetKeys(), true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Preset tema tidak dikenal']);
        }

        $custom = \App\Libraries\Theme::normalizeHex($input['custom'] ?? null);
        if ($preset === 'custom' && $custom === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Warna dasar custom tidak valid']);
        }

        $this->ensureThemeColumns();

        $status = $this->settings->updateSetting([
            'theme_preset' => $preset,
            'theme_custom' => $custom,
        ]);

        \App\Libraries\Theme::flushCache();
        $theme = \App\Libraries\Theme::resolve($preset, $custom);

        return $this->response->setJSON([
            'status'  => (bool) $status,
            'message' => $status ? 'Tema tampilan berhasil disimpan.' : 'Gagal menyimpan tema.',
            'theme'   => [
                'preset' => $theme['preset'],
                'custom' => $theme['custom'],
                'label'  => $theme['label'],
            ],
        ]);
    }

    /**
     * Pastikan kolom preferensi tema ada (lazy migration).
     */
    private function ensureThemeColumns(): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('setting')) {
                return;
            }

            $add = [];
            if (!$db->fieldExists('theme_preset', 'setting')) {
                $add['theme_preset'] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'default'];
            }
            if (!$db->fieldExists('theme_custom', 'setting')) {
                $add['theme_custom'] = ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'default' => null];
            }
            if ($add !== []) {
                \Config\Database::forge()->addColumn('setting', $add);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Settings::ensureThemeColumns] ' . $e->getMessage());
        }
    }

    /**
     * Pastikan kolom penomoran surat teguran ada (lazy migration, sama seperti
     * patchAbsensiSettingTable di PresensiAdmin). Guard ini menjaga agar
     * instance yang belum menjalankan `php spark migrate` tetap bisa menyimpan.
     */
    private function ensureNomorSettingColumns(): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('setting')) {
                return;
            }

            $wanted = [
                'kode_sekolah' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'SMAN2.AT'],
                'surat_teguran_format' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => \App\Libraries\SuratNomor::DEFAULT_FORMAT],
                'surat_teguran_padding' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => \App\Libraries\SuratNomor::DEFAULT_PADDING],
            ];

            $add = [];
            foreach ($wanted as $col => $def) {
                if (!$db->fieldExists($col, 'setting')) {
                    $add[$col] = $def;
                }
            }
            if ($add !== []) {
                \Config\Database::forge()->addColumn('setting', $add);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Settings::ensureNomorSettingColumns] ' . $e->getMessage());
        }
    }

    /**
     * Buang key yang kolomnya belum tersedia agar update tidak gagal total.
     */
    private function filterExistingSettingFields(array $data): array
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('setting')) {
                return $data;
            }
            foreach (array_keys($data) as $key) {
                if (!$db->fieldExists($key, 'setting')) {
                    unset($data[$key]);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Settings::filterExistingSettingFields] ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Validasi jumlah digit nomor urut (1-6).
     */
    private function normalizeTeguranPadding($value): int
    {
        $pad = (int) $value;
        if ($pad < 1 || $pad > 6) {
            return \App\Libraries\SuratNomor::DEFAULT_PADDING;
        }

        return $pad;
    }

    /**
     * Backup Database only
     */
    public function backupDb()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $backupPath = WRITEPATH . 'backups/';
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $db = \Config\Database::connect();
        $tables = $db->listTables();
        $filename = 'db_backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupPath . $filename;

        $sql = '';
        $dilewati = [];
        foreach ($tables as $table) {
            if (strpos($table, 'migration') === 0) continue; // skip migration tables
            $result = $db->table($table)->get();
            if ($result === false) {
                // get() mengembalikan false saat query gagal; melanjutkan berarti
                // dump tanpa tabel ini tapi tetap dilaporkan "berhasil".
                log_message('error', '[Settings::backupDb] gagal membaca ' . $table . ': ' . json_encode($db->error()));
                $dilewati[] = $table;
                continue;
            }
            $fields = $db->getFieldNames($table);
            if (empty($fields)) {
                log_message('error', '[Settings::backupDb] tidak bisa membaca kolom ' . $table);
                $dilewati[] = $table;
                continue;
            }

            $createQ = $db->query("SHOW CREATE TABLE `$table`");
            $create = $createQ ? $createQ->getRow() : null;
            $ddl = is_object($create) ? ($create->{'Create Table'} ?? $create->{'Create View'} ?? null) : null;
            if ($ddl === null) {
                log_message('error', '[Settings::backupDb] tidak ada DDL untuk ' . $table);
                $dilewati[] = $table;
                continue;
            }

            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $ddl . ";\n\n";

            if ($result->getNumRows() > 0) {
                $sql .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                foreach ($result->getResultArray() as $row) {
                    $values = [];
                    foreach ($fields as $field) {
                        $val = $row[$field] ?? null;
                        if ($val === null) {
                            $values[] = 'NULL';
                        } else {
                            // Selalu diberi kutip. is_numeric() juga benar untuk
                            // string seperti '0611450719200011' (NIP/ponsel/kodepos),
                            // dan tanpa kutip angka itu menjadi literal numerik saat
                            // restore, sehingga semua angka 0 di depan hilang.
                            $values[] = "'" . $db->escapeString((string) $val) . "'";
                        }
                    }
                    $rows[] = '(' . implode(',', $values) . ')';
                }
                $sql .= implode(",\n", $rows) . ";\n\n";
            }
        }

        if ($dilewati !== []) {
            // Penanda di dalam dump: restore menolak file bertanda ini, karena
            // "sebagian lengkap" jauh lebih berbahaya daripada tidak ada backup.
            $sql = "# absen:backup-tidak-lengkap tabel=" . implode(',', $dilewati) . "\n"
                . "# Dibuat " . date('Y-m-d H:i:s') . " — jangan dipakai untuk restore.\n\n" . $sql;
        }

        if (file_put_contents($filepath, $sql, LOCK_EX) === false) {
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menulis file backup']);
        }

        if ($dilewati !== []) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Backup TIDAK lengkap: ' . count($dilewati) . ' tabel tidak ikut tersimpan ('
                    . implode(', ', array_slice($dilewati, 0, 10)) . ').'
                    . ' File ' . $filename . ' tidak boleh dipakai untuk restore sebelum tabel-tabel itu berhasil dibaca.',
                'file' => $filename,
                'dilewati' => $dilewati,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Backup database berhasil: ' . $filename,
            'file' => $filename,
            'size' => filesize($filepath),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Backup File Uploads (public/uploads)
     */
    public function backupData()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        if (!class_exists('ZipArchive')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Ekstensi ZipArchive tidak tersedia']);
        }

        $backupPath = WRITEPATH . 'backups/';
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $srcPath = ROOTPATH . 'public/uploads/';
        if (!is_dir($srcPath)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Folder uploads tidak ditemukan']);
        }

        $filename = 'files_backup_' . date('Ymd_His') . '.zip';
        $filepath = $backupPath . $filename;

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal membuat file zip']);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $dimasukkan = 0;
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($srcPath));
                $zip->addFile($filePath, $relativePath);
                $dimasukkan++;
            }
        }
        // close() mengembalikan false bila arsip gagal ditulis/ditutup (disk penuh,
        // izin berubah). Tanpa pemeriksaan ini admin mendapat pesan "berhasil"
        // sementara file .zip-nya rusak atau kosong.
        if ($zip->close() !== true) {
            @unlink($filepath);
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Backup file gagal ditutup/dirusak — arsip tidak disimpan. Periksa ruang disk folder writable.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($dimasukkan === 0) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada file di public/uploads — backup tidak dibuat.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Backup file berhasil: ' . $filename,
            'file' => $filename,
            'jumlah_file' => $dimasukkan,
            'size' => filesize($filepath),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Delete all backup files
     */
    public function delBackup()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $backupPath = WRITEPATH . 'backups/';
        if (!is_dir($backupPath)) {
            return $this->response->setJSON(['status' => true, 'message' => 'Folder backup tidak ada',
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()]);
        }

        // Hanya sapu file yang endpoint ini buat sendiri. Sebelumnya glob '*.*'
        // menghapus SEMUA isi folder, termasuk arsip malam cron db_*.sql.gz
        // (satu-satunya recovery point otomatis) dan index.html/.htaccess
        // pelindung folder — semua hilang tanpa jejak dan tetap dilaporkan bersih.
        $polaDiizinkan = ['db_backup_', 'files_backup_'];
        $dilindungi = [];
        $gagalHapus = [];
        $count = 0;
        foreach (glob($backupPath . '*') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            $nama = basename($file);
            foreach ($polaDiizinkan as $pola) {
                if (str_starts_with($nama, $pola)) {
                    if (@unlink($file)) {
                        $count++;
                    } else {
                        $gagalHapus[] = $nama;
                    }
                    continue 2;
                }
            }
            $dilindungi[] = $nama;
        }

        if ($count === 0 && $gagalHapus === []) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada file backup manual yang bisa dihapus'
                    . (empty($dilindungi) ? '' : '. ' . count($dilindungi) . ' file arsip otomatis dipertahankan.'),
                'dilindungi' => $dilindungi,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $pesan = "$count file backup dihapus";
        if ($gagalHapus !== []) {
            $pesan .= ', ' . count($gagalHapus) . ' gagal dihapus: ' . implode(', ', array_slice($gagalHapus, 0, 5));
        }
        if ($dilindungi !== []) {
            $pesan .= '. ' . count($dilindungi) . ' file arsip otomatis dipertahankan';
        }

        return $this->response->setJSON([
            'status' => $gagalHapus === [],
            'message' => $pesan,
            'dihapus' => $count,
            'gagal' => $gagalHapus,
            'dilindungi' => $dilindungi,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Delete single backup file (alias for view)
     */
    public function hapusBackup()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $src = $this->request->getPost('src');
        if (empty($src)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama file tidak boleh kosong']);
        }

        // Security: only allow alphanumeric, underscore, dash, dot
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $src)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama file tidak valid']);
        }

        $backupPath = WRITEPATH . 'backups/';
        $filepath = $backupPath . $src;

        if (!is_file($filepath)) {
            return $this->response->setJSON(['status' => false, 'message' => 'File tidak ditemukan']);
        }

        @unlink($filepath);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'File backup dihapus',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Pecah isi dump SQL menjadi daftar statement.
     *
     * explode(';') tidak aman untuk data sekolah: alamat, catatan wali, dan
     * token bisa mengandung tanda titik koma, sehingga statement terpotong di
     * tengah string literal dan restore menulis data yang sudah rusak.
     */
    private function pisahStatement(string $sql): array
    {
        $statements = [];
        $current    = '';
        $quote      = null;
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($quote !== null) {
                $current .= $ch;
                if ($ch === '\\' && $quote !== '`' && $i + 1 < $len) {
                    $current .= $sql[++$i];
                    continue;
                }
                if ($ch === $quote) {
                    if ($quote !== '`' && $i + 1 < $len && $sql[$i + 1] === $quote) {
                        // kutip berganda ('' di dalam ') adalah literal, bukan penutup
                        $current .= $sql[++$i];
                        continue;
                    }
                    $quote = null;
                }
                continue;
            }

            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
                $current .= $ch;
                continue;
            }

            if (($ch === '#' || ($ch === '-' && ($sql[$i + 1] ?? '') === '-'))
                && ($ch === '#' || preg_match('/^--(\s|$)/', substr($sql, $i, 3)))) {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $current .= "\n";
                continue;
            }

            if ($ch === '/' && ($sql[$i + 1] ?? '') === '*') {
                $tutup = strpos($sql, '*/', $i + 2);
                $i = $tutup === false ? $len : $tutup + 1;
                continue;
            }

            if ($ch === ';') {
                $trim = trim($current);
                if ($trim !== '') {
                    $statements[] = $trim;
                }
                $current = '';
                continue;
            }

            $current .= $ch;
        }

        $trim = trim($current);
        if ($trim !== '') {
            $statements[] = $trim;
        }

        return $statements;
    }

    /**
     * Restore database from SQL backup
     */
    public function restore()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $src = $this->request->getPost('src');
        if (empty($src)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama file tidak boleh kosong']);
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $src)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama file tidak valid']);
        }

        $backupPath = WRITEPATH . 'backups/';
        $filepath = $backupPath . $src;

        if (!is_file($filepath)) {
            return $this->response->setJSON(['status' => false, 'message' => 'File backup tidak ditemukan']);
        }

        // Hanya dump teks murni. Arsip cron .sql.gz dibaca sebagai teks menghasilkan
        // byte acak yang lolos "pisahStatement" lalu dieksekusi sebagai SQL rusak —
        // sebagian di antaranya bisa saja kebetulan valid dan merusak data.
        if (!str_ends_with(strtolower($src), '.sql')) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Hanya file .sql yang bisa direstore dari halaman ini. File terkompresi (.gz/.zip) harus di-ekstrak dulu di server.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $sql = file_get_contents($filepath);
        if ($sql === false || trim($sql) === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'File backup kosong atau tidak bisa dibaca']);
        }

        // Dump yang dilaporkan tidak lengkap saat dibuat tidak boleh dipakai untuk
        // restore: DROP TABLE di dalamnya akan menyapu data sedangkan tabel yang
        // tadi dilewati tidak ikut dipulihkan.
        if (preg_match('/^#?\s*absen:backup-tidak-lengkap/m', $sql)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File ini ditandai sebagai backup TIDAK lengkap saat dibuat. Buat backup baru dulu, lalu restore yang lengkap.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Pisah per statement dengan parser yang mengenali string literal dan
        // komentar; explode(';') memotong alamat/catatan yang berisi ';'.
        $statements = $this->pisahStatement($sql);
        $berhasil = 0;
        $gagal = 0;
        foreach ($statements as $stmt) {
            if (strlen($stmt) > 0) {
                try {
                    $db->query($stmt);
                    $berhasil++;
                } catch (\Throwable $e) {
                    // Continue on error (e.g., table already exists).
                    // Tetap dihitung: tanpa ini restore sebagian terbaca
                    // "berhasil" padahal banyak statement tidak berjalan,
                    // dan DDL MySQL di-implied-commit sehingga transComplete()
                    // tidak bisa membatalkannya.
                    $gagal++;
                    log_message('error', '[Settings::restore] SQL error: ' . $e->getMessage());
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => false, 'message' => 'Restore gagal (transaksi dibatalkan)']);
        }

        if ($gagal > 0) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Restore TIDAK lengkap: ' . $berhasil . ' statement berjalan, ' . $gagal
                    . ' gagal. Periksa log server sebelum mengandalkan data ini.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Restore database berhasil (' . $berhasil . ' statement)',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * Download backup file
     */
    public function downloadBackup()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return redirect()->to('auth');
        }

        $src = (string) ($this->request->getGet('src') ?? '');

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $src)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $backupPath = WRITEPATH . 'backups/';
        $filepath = $backupPath . $src;

        if (!is_file($filepath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        // File backup sering dibuat cron yang berjalan sebagai user lain; kalau
        // ownership/umask-nya tidak bisa dibaca PHP, DownloadResponse akan
        // gagal di tengah stream tanpa pesan yang jelas di log.
        if (!is_readable($filepath)) {
            log_message('error', '[Settings::downloadBackup] tidak bisa membaca ' . $filepath
                . ' — cek permission/owner file (dibuat cron sebagai user lain?).');
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($filepath, null)->setFileName($src);
    }
}
