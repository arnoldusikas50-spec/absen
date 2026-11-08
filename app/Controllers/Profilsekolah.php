<?php

namespace App\Controllers;

use App\Models\DashboardModel;
use App\Models\SettingsModel;

/**
 * ProfilSekolah Controller - Migrated from CI3
 * Handles school profile management
 */
class Profilsekolah extends BaseController
{
    protected DashboardModel $dashboardModel;
    protected SettingsModel $settingsModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->settingsModel = new SettingsModel();
    }

    /**
     * Main profile page
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }

        // Halaman ini menulis identitas sekolah + api_key, jadi hanya admin.
        // school_uuid (session) dulu dipakai sebagai jalan masuk alternatif,
        // padahal Auth.php menyetaknya untuk SEMUA role dari users.company —
        // begitu kolom itu terisi, guru/siswa bisa mengubah profil sekolah.
        if (!$this->isAdmin()) {
            return $this->showError('Hanya Admin Sekolah yang dapat mengakses halaman ini.', 403);
        }

        $user = $this->getUser();
        $setting = $this->dashboardModel->getSetting();

        // Tampilkan konfigurasi notifikasi AKTIF (sumber tunggal: absensi_setting)
        // agar form tidak terlihat kosong padahal pengirim memakai nilai lain.
        try {
            $absen = (new \App\Models\AttendanceSettingModel())->first();
            if ($setting && $absen) {
                foreach (['telegram_bot_token', 'telegram_chat_id', 'wa_gateway_url', 'wa_token', 'wa_target_number'] as $k) {
                    if (isset($absen->$k)) {
                        $setting->$k = $absen->$k;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $data = [
            'user' => $user,
            'judul' => 'Profile Sekolah',
            'subjudul' => 'Pengaturan Data Sekolah',
            'profile' => null,
            'setting' => $setting,
            'tp' => $this->dashboardModel->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboardModel->getSemester(),
            'smt_active' => $smt,
        ];

        return view('setting/data', $data);
    }

    /**
     * Upload file/logo
     */
    public function uploadFile($logo = 'logo')
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $file = $this->request->getFile('logo');

        if (!$file || !$file->isValid()) {
            return $this->respond(false, 'File tidak valid');
        }

        $allowedTypes = ['image/gif', 'image/jpg', 'image/jpeg', 'image/png'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->respond(false, 'Tipe file tidak diizinkan');
        }

        $school_uuid = $this->session->get('school_uuid') ?? 'default';
        // $logo datang dari segmen URL (:segment) yang masih boleh berisi
        // backslash; di Windows '\' menyelesaikan path, jadi nama field dibersihkan
        // agar file tidak pernah bisa ditulis di luar uploads/settings/.
        $logo    = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $logo);
        $newName = $logo . '_' . $school_uuid . '.' . $file->getExtension();

        $uploadPath = FCPATH . 'uploads/settings/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        if ($file->move($uploadPath, $newName, true)) {
            return $this->respond(true, 'File berhasil diupload', [
                'src' => base_url('uploads/settings/' . $newName),
                'filename' => pathinfo($newName, PATHINFO_FILENAME),
                'relative_path' => 'uploads/settings/' . $newName,
                'type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);
        }

        return $this->respond(false, 'Gagal mengupload file');
    }

    /**
     * Delete file
     */
    public function deleteFile()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $src = $this->request->getPost('src');
        $file_name = str_replace(base_url(), '', $src ?? '');
        $file_name = ltrim($file_name, '/');
        // Batasi hanya di uploads/settings/ agar tidak bisa hapus file sembarang
        if (!str_starts_with($file_name, 'uploads/settings/') || str_contains($file_name, '..')) {
            return $this->respond(false, 'Path tidak diizinkan');
        }
        $file_path = FCPATH . $file_name;

        if (file_exists($file_path) && unlink($file_path)) {
            return $this->respond(true, 'File berhasil dihapus');
        }

        return $this->respond(false, 'File tidak ditemukan atau gagal menghapus');
    }

    /**
     * Normalize media path
     */
    private function normalizeMediaPath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $normalized = str_replace(base_url(), '', $path);
        return ltrim($normalized, '/');
    }

    /**
     * Save school settings
     */
    public function saveSetting()
    {
        // sama seperti index(): school_uuid bukan pembatas yang sah karena
        // semua role login mendapatkannya dari users.company.
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses Sekolah tidak valid', null, null, 403);
        }

        $db = \Config\Database::connect();

        // Peta kolom => [nama field POST, batas panjang, jenis].
        // Divalidasi di sini karena sql_mode produksi tidak strict: nilai
        // yang terlalu panjang dipotong diam-diam dan kolom int yang
        // dikirimi teks menjadi 0 tanpa suara.
        $peta = [
            'nama_aplikasi'      => ['nama_aplikasi', 100, 'teks'],
            'web'                => ['web', 50, 'teks'],
            'fax'                => ['fax', 20, 'digit'],
            'email'              => ['email', 50, 'teks'],
            'telp'               => ['tlp', 20, 'digit'],
            'kepsek'             => ['kepsek', 50, 'teks'],
            'nip'                => ['nip', 30, 'teks'],
            'proktor'            => ['proktor', 50, 'teks'],
            'nip_proktor'        => ['nip_proktor', 30, 'teks'],
            'tkn_siswa'          => ['tkn_siswa', 11, 'pilih12'],
            'mode_app'           => ['mode_app', 11, 'pilih12'],
            'ba_aktif'           => ['ba_aktif', 20, 'angkaPendek'],
            'jenjang'            => ['jenjang', 3, 'jenjang'],
            'satuan_pendidikan'  => ['satuan_pendidikan', 1, 'pilih123'],
            'sekolah'            => ['nama_sekolah', 50, 'teks'],
            'nss'                => ['nss', 20, 'digit'],
            'npsn'               => ['npsn', 10, 'digit'],
            'alamat'             => ['alamat', 2000, 'teks'],
            'desa'               => ['desa', 100, 'teks'],
            'kecamatan'          => ['kec', 50, 'teks'],
            'kota'               => ['kota', 30, 'teks'],
            'provinsi'           => ['provinsi', 100, 'teks'],
            'kode_pos'           => ['kode_pos', 11, 'digit'],
            'api_key'            => ['api_key', 100, 'kunci'],
        ];

        $insert    = [];
        $salah     = [];
        $labelWps  = [
            'nama_aplikasi' => 'Nama aplikasi', 'web' => 'Website', 'fax' => 'Fax',
            'email' => 'Email', 'telp' => 'Telepon', 'kepsek' => 'Nama kepala sekolah',
            'nip' => 'NIP kepala sekolah', 'proktor' => 'Nama proktor',
            'nip_proktor' => 'NIP proktor', 'tkn_siswa' => 'Token siswa',
            'mode_app' => 'Mode aplikasi', 'ba_aktif' => 'Bank soal aktif',
            'jenjang' => 'Jenjang', 'satuan_pendidikan' => 'Satuan pendidikan',
            'sekolah' => 'Nama sekolah', 'nss' => 'NSS', 'npsn' => 'NPSN',
            'alamat' => 'Alamat', 'desa' => 'Desa', 'kecamatan' => 'Kecamatan',
            'kota' => 'Kota', 'provinsi' => 'Provinsi', 'kode_pos' => 'Kode pos',
            'api_key' => 'API key',
        ];

        foreach ($peta as $kolom => [$field, $maks, $jenis]) {
            $raw = $this->request->getPost($field);
            if ($raw === null) {
                continue; // field tidak terkirim -> jangan kosongkan kolomnya
            }
            $nilai = $this->bersihkanNilai((string) $raw, $jenis, $maks);
            if ($nilai === null) {
                $salah[] = $labelWps[$kolom] ?? $kolom;
                continue;
            }
            $insert[$kolom] = $nilai;
        }

        if ($salah !== []) {
            return $this->respond(false, 'Periksa lagi isian ini: ' . implode(', ', $salah)
                . '. Nilai di luar batas kolom tidak disimpan.');
        }

        // Hanya update media fields if explicitly submitted
        $mediaFields = ['logo_kanan', 'logo_kiri', 'stampel', 'tanda_tangan', 'ttdproktor', 'kodescan', 'kopsekolah', 'banner1', 'banner2', 'banner3'];
        foreach ($mediaFields as $mf) {
            $val = $this->request->getPost($mf);
            if ($val !== null) {
                $insert[$mf] = $this->normalizeMediaPath($val);
            }
        }

        // Handle ba_waktu datetime
        $ba_waktu_input = $this->request->getPost('ba_waktu');
        if (!empty($ba_waktu_input)) {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $ba_waktu_input);
            if ($dt instanceof \DateTime) {
                $insert['ba_waktu'] = $dt->format('Y-m-d H:i:s');
            }
        }

        if ($insert === []) {
            return $this->respond(false, 'Tidak ada isian yang terbaca — tidak ada yang disimpan.');
        }

        $school_uuid = $this->session->get('school_uuid');

        try {
            $existing = $db->table('setting')->limit(1)->get()->getRow();

            if ($existing) {
                $sebelum = array_intersect_key((array) $existing, $insert);
                $updated = $db->table('setting')
                    ->where('id_setting', $existing->id_setting ?? 1)
                    ->update($insert);
            } else {
                $sebelum = [];
                $insert['id_setting'] = 1;
                if ($school_uuid) {
                    $insert['sync_school_uuid'] = $school_uuid;
                }
                $updated = $db->table('setting')->insert($insert);
            }

            if ($updated === false) {
                log_message('error', '[Profilsekolah] tulis setting ditolak DB (' . implode(', ', array_keys($insert)) . ')');
                return $this->respond(false, 'Profil sekolah gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
            }

            $this->catatAuditProfil($sebelum, $insert);
        } catch (\Throwable $e) {
            // Pesan exception bisa memuat potongan SQL; hanya ke log.
            log_message('error', '[Profilsekolah] saveSetting: ' . $e->getMessage());
            return $this->respond(false, 'Profil sekolah gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        return $this->respond(true, 'Pengaturan berhasil disimpan');
    }

    /**
     * Kembalikan nilai siap-simpan, atau null kalau tidak sah untuk kolomnya.
     */
    private function bersihkanNilai(string $raw, string $jenis, int $maks): ?string
    {
        if ($jenis === 'teks' || $jenis === 'angkaPendek') {
            // alamat/nama boleh punya baris; cukup pangkas spasi di ujung.
            $nilai = trim($raw);
        } else {
            $nilai = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
        }

        switch ($jenis) {
            case 'digit':
                $nilai = preg_replace('/[^0-9]/', '', $nilai) ?? '';
                break;
            case 'kunci':
                $nilai = preg_replace('/\s+/', '', $nilai) ?? '';
                break;
            case 'jenjang':
                // kolom int(11): teks apa pun akan jadi 0 dan daftar level
                // kelas ikut salah.
                if (!in_array($nilai, ['1', '2', '3'], true)) {
                    return null;
                }
                break;
            case 'pilih12':
                if ($nilai !== '' && !in_array($nilai, ['1', '2'], true)) {
                    return null;
                }
                break;
            case 'pilih123':
                if ($nilai !== '' && !in_array($nilai, ['1', '2', '3'], true)) {
                    return null;
                }
                break;
            case 'angkaPendek':
                if ($nilai !== '' && !preg_match('/^\d{1,20}$/', $nilai)) {
                    return null;
                }
                break;
        }

        if ($maks > 0 && mb_strlen($nilai) > $maks) {
            return null;
        }

        return $nilai;
    }

    /**
     * Identitas sekolah & api_key dipakai lintas modul; simpan jejaknya.
     */
    private function catatAuditProfil(array $sebelum, array $sesudah): void
    {
        try {
            // api_key tidak boleh ikut tertulis ke audit log.
            unset($sesudah['api_key'], $sebelum['api_key']);
            (new \App\Models\AuditLogModel())->log(
                'setting',
                null,
                'Mengubah profil sekolah',
                null,
                (int) ($this->getUserId() ?? 0),
                $sebelum,
                $sesudah,
                null
            );
        } catch (\Throwable $e) {
            log_message('error', '[Profilsekolah] gagal tulis audit profil sekolah: ' . $e->getMessage());
        }
    }
}
