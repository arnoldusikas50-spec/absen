<?php

namespace App\Controllers;

use App\Models\MasterModel;
use App\Models\DashboardModel;
use App\Models\DropdownModel;
use App\Models\JabatanGuruModel;
use App\Models\LogModel;

/**
 * Dataguru Controller - Migrated from CI3
 * Comprehensive teacher management
 */
class Dataguru extends BaseController
{
    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;
    protected DropdownModel $dropdownModel;
    protected JabatanGuruModel $jabatanModel;
    protected LogModel $logModel;

    public function __construct()
    {
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
        $this->dropdownModel = new DropdownModel();
        $this->jabatanModel = new JabatanGuruModel();
        $this->logModel = new LogModel();
    }

    /**
     * Index page
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        // Master guru memuat PII: siswa tidak boleh akses daftar guru.
        if ($this->isSiswa()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $mode = $this->request->getGet('mode') ?? '1';

        // Prepare data kelas [id => nama]
        $rawKelass = $this->masterModel->getAllKelas($tp->id_tp, $smt->id_smt);
        $kelass = [];
        foreach ($rawKelass as $k) {
            $kelass[$k->id_kelas] = $k->nama_kelas;
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Guru',
            'subjudul' => 'Data Guru',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'mode' => $mode == '1' ? '1' : '2',
            'is_admin' => $this->isAdmin(),
            'extras' => $this->dropdownModel->getAllKodeEkskul(),
            'kelass' => $kelass,
        ];

        $mapels = $this->masterModel->getAllMapel();
        $retMapel = [];
        foreach ($mapels as $row) {
            $retMapel[$row->id_mapel] = $row;
        }
        $data['mapels'] = $retMapel;

        $gurus = $this->masterModel->getAllDataGuru($tp->id_tp, $smt->id_smt);
        foreach ($gurus as $guru) {
            $guru->mapel_kelas = $this->maybe_unserialize($guru->mapel_kelas ?? '[]');
            $guru->ekstra_kelas = $this->maybe_unserialize($guru->ekstra_kelas ?? '[]');
        }
        $data['gurus'] = $gurus;

        return view('master/guru/data', $data);
    }

    /**
     * Data for DataTable
     */
    public function data()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $search = $this->request->getGet('search')['value'] ?? null;

        $gurus = $this->masterModel->getDataGuru($tp->id_tp, $smt->id_smt, $search);

        return $this->response->setJSON([
            'draw' => $this->request->getGet('draw'),
            'recordsTotal' => count($gurus),
            'recordsFiltered' => count($gurus),
            'data' => $gurus
        ]);
    }

    /**
     * Edit Guru Profile
     */
    public function edit($id)
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('dataguru')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }
        $guru = $this->masterModel->getGuruById($id, $tp->id_tp, $smt->id_smt);

        if (!$guru) {
            return redirect()->to('dataguru')->with('error', 'Guru tidak ditemukan');
        }

        // Nomor WA notifikasi dibaca dari user_notifikasi (sumber runtime),
        // bukan master_guru yang hanya catatannya.
        $waNumber = '';
        $waVerified = false;
        try {
            $idUser = (int) ($guru->id_user ?? 0);
            if ($idUser > 0) {
                $waNumber = \App\Libraries\WhatsAppLink::numberForGuru((int) $guru->id_guru, $idUser);
                $waVerified = $waNumber !== ''
                    && (new \App\Models\UserNotifikasiModel())->getVerifiedWaNumber($idUser) !== '';
            }
        } catch (\Throwable $e) {
            // Blok ini HANYA berjalan di edit() (method GET yang membaca
            // nomor, bukan menyimpannya). Pesannya karena itu soal
            // "membaca", dan variabel yang dirujuk adalah $id — parameter
            // method — bukan $idGuru yang hanya ada di save(). Memakai
            // $idGuru di sini memicu "Undefined variable" dan membuat
            // halaman Edit 500 saat pembacaan gagal.
            log_message('error', '[Dataguru::edit] gagal membaca nomor WA guru id=' . (int) $id
                . ': ' . $e->getMessage());
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Edit Guru',
            'subjudul' => 'Edit Data Guru',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'id_active' => $id,
            'guru' => $guru,
            'mapel' => $this->masterModel->getAllMapel(),
            'waNumber' => $waNumber,
            'waVerified' => $waVerified,
        ];

        // Prepare Inputs like CI3
        $data['input_profile'] = [
            (object) ['label' => 'Nama Lengkap', 'name' => 'nama_guru', 'value' => $guru->nama_guru, 'icon' => 'bi bi-person', 'type' => 'text'],
            (object) ['label' => 'Email', 'name' => 'email', 'value' => $guru->email, 'icon' => 'bi bi-envelope', 'type' => 'text'],
            (object) ['label' => 'NIP / NUPTK', 'name' => 'nip', 'value' => $guru->nip, 'icon' => 'bi bi-person-badge', 'type' => 'text'],
            (object) ['label' => 'Jenis Kelamin', 'name' => 'jenis_kelamin', 'value' => $guru->jenis_kelamin, 'icon' => 'bi bi-gender-ambiguous', 'type' => 'text'],
            // Basis aturan eligibilitas cuti (PNS/PPPK berbeda hak, lihat
            // CutiKategoriService) dan masa kerja (TMT pengangkatan).
            (object) ['label' => 'Status Pegawai', 'name' => 'status_pegawai', 'value' => $guru->status_pegawai ?? '', 'icon' => 'bi bi-person-vcard', 'type' => 'text'],
            (object) ['label' => 'TMT Pengangkatan', 'name' => 'tmt', 'value' => $guru->tmt ?? '', 'icon' => 'bi bi-calendar-check', 'type' => 'date'],
            (object) ['label' => 'Agama', 'name' => 'agama', 'value' => $guru->agama, 'icon' => 'bi bi-person', 'type' => 'text'],
            (object) ['label' => 'Google API Key', 'name' => 'api_key', 'value' => $guru->api_key ?? '', 'icon' => 'bi bi-key', 'type' => 'text'],
            (object) ['label' => 'ID Telegram', 'name' => 'telegram_id', 'value' => $guru->telegram_id ?? '', 'icon' => 'bi bi-telegram', 'type' => 'text'],
            // Nomor WA notifikasi, bukan no_hp. Keduanya sengaja dipisah:
            // no_hp milik Dapodik dan ditimpa tiap sinkronisasi, sementara
            // ini tujuan kiriman yang tidak boleh berubah diam-diam.
            (object) ['label' => 'No. WhatsApp', 'name' => 'wa_number', 'value' => $waNumber ?? '', 'icon' => 'bi bi-whatsapp', 'type' => 'text'],
        ];

        $data['input_alamat'] = [
            (object) ['label' => 'NIK', 'name' => 'no_ktp', 'value' => $guru->no_ktp, 'icon' => 'bi bi-person-badge', 'type' => 'number'],
            (object) ['label' => 'Tempat Lahir', 'name' => 'tempat_lahir', 'value' => $guru->tempat_lahir, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Tgl. Lahir', 'name' => 'tgl_lahir', 'value' => $guru->tgl_lahir, 'icon' => 'bi bi-calendar-date', 'type' => 'text'],
            (object) ['label' => 'Alamat', 'name' => 'alamat_jalan', 'value' => $guru->alamat_jalan, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Kecamatan', 'name' => 'kecamatan', 'value' => $guru->kecamatan, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Kota/Kab.', 'name' => 'kabupaten', 'value' => $guru->kabupaten, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Provinsi', 'name' => 'provinsi', 'value' => $guru->provinsi, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Kode Pos', 'name' => 'kode_pos', 'value' => $guru->kode_pos, 'icon' => 'bi bi-envelope', 'type' => 'number'],
        ];

        $data['input_dokumen'] = [
            (object) ['label' => 'KTP', 'id' => 'ktp_id', 'name' => 'ktp_id', 'type' => 'file'],
            (object) ['label' => 'KK', 'id' => 'kk_id', 'name' => 'kk_id', 'type' => 'file'],
            (object) ['label' => 'Sertifikat', 'id' => 'sertifikat', 'name' => 'serti[]', 'type' => 'file'],
        ];

        return view('master/guru/edit', $data);
    }

    /**
     * Create Guru
     */
    public function create()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $nip = $this->request->getPost('nip');
        $nama_guru = $this->request->getPost('nama_guru');
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $rules = [
            'nama_guru' => 'required|max_length[100]',
            'username' => 'required|min_length[4]|max_length[30]|regex_match[/^[A-Za-z0-9_.-]+$/]|is_unique[master_guru.username]',
            'password' => 'required|min_length[5]|max_length[72]'
        ];

        if ($nip) {
            $rules['nip'] = 'numeric|is_unique[master_guru.nip]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Username juga harus unik di tabel users (akun admin/guru/siswa lain)
        $dbDup = \Config\Database::connect();
        if ($dbDup->table('users')->where('username', $username)->countAllResults() > 0) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => ['username' => 'Username sudah dipakai akun lain']
            ]);
        }

        try {
            $nama = explode(' ', $nama_guru ?? '');
            $user = [
                'username' => trim($username),
                'password' => password_hash(trim($password), PASSWORD_BCRYPT),
                'first_name' => $nama[0],
                'last_name' => count($nama) > 1 ? end($nama) : '',
                'active' => 1
            ];

            $db = \Config\Database::connect();
            $db->transException(true)->transStart();

            $db->table('users')->insert($user);
            $id_user = $db->insertID();

            // FIX: Add user to 'guru' group (id=2)
            $db->table('users_groups')->insert([
                'user_id' => $id_user,
                'group_id' => 2 // 2 is typically for Guru
            ]);

            $input = [
                'id_user' => $id_user,
                'nip' => trim($nip ?? ''),
                'nama_guru' => strtoupper(trim($nama_guru)),
                'kode_guru' => mb_substr(trim((string) $this->request->getPost('kode_guru')), 0, 30),
                'username' => trim($username),
                'password' => trim($password),
                'foto' => null,
                // 'status' => 1 // Removed: column does not exist
            ];

            // ID Telegram opsional saat menambah guru baru.
            $telegramId = '';
            $rawTelegram = trim((string) $this->request->getPost('telegram_id'));
            if ($rawTelegram !== '' && self::ensureTelegramColumn()) {
                try {
                    $telegramId = \App\Libraries\TelegramLink::normalizeChatId($rawTelegram);
                } catch (\InvalidArgumentException $e) {
                    $db->transRollback();
                    return $this->response->setJSON(['status' => false, 'message' => $e->getMessage()]);
                }
                if ($telegramId !== '') {
                    $input['telegram_id'] = $telegramId;
                }
            }

            $this->masterModel->create('master_guru', $input);
            $this->logModel->saveLog(1, 'Menambah guru: ' . $nama_guru); // 1 = ADD

            // Ambil SEBELUM transComplete(): penutup transaksi bisa me-reset
            // insertID, sehingga attachToGuru() di bawah akan menulis ke
            // id_guru = 0 dan diam-diam tidak menempel ke guru mana pun.
            $idGuruBaru = (int) $db->insertID();

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan ke database']);
            }

            // Tautan kanal notifikasi ditulis SESUDAH transaksi selesai.
            //
            // Sebelumnya jalur ini hanya menulis ke master_guru. Padahal
            // NotificationService::notifyUser() membaca user_notifikasi —
            // jadi ID Telegram yang diisi saat menambah guru tidak pernah
            // dipakai untuk kirim apa pun, dan guru itu diam-diam tidak
            // menerima notifikasi personal sama sekali.
            $kanalNote = [];

            if ($telegramId !== '') {
                $r = \App\Libraries\TelegramLink::attachToGuru($idGuruBaru, (int) $id_user, $telegramId);
                if (! $r['ok']) {
                    $kanalNote[] = 'Telegram: ' . $r['message'];
                }
            }

            $rawWa = trim((string) $this->request->getPost('wa_number'));
            if ($rawWa !== '') {
                $dbWa = \Config\Database::connect();
                if ($dbWa->fieldExists('wa_number', 'user_notifikasi')) {
                    $rw = \App\Libraries\WhatsAppLink::attachToGuru($idGuruBaru, (int) $id_user, $rawWa);
                    if (! $rw['ok']) {
                        $kanalNote[] = 'WhatsApp: ' . $rw['message'];
                    }
                } else {
                    $kanalNote[] = 'WhatsApp: kolom belum tersedia, jalankan php spark migrate';
                }
            }

            return $this->response->setJSON([
                'status'   => true,
                'kanal'    => $kanalNote,
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[Dataguru::create] ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Gagal menyimpan data guru — periksa log aplikasi.'
            ]);
        }

        return $this->response->setJSON(['status' => false]);
    }

    /**
     * Save Guru Profile Update
     */
    /**
     * Kolom master_guru yang boleh diubah via form edit (anti mass-assignment).
     */
    private const EDITABLE_GURU_FIELDS = [
        'nama_guru' => 100, 'email' => 100, 'nip' => 30, 'jenis_kelamin' => 2,
        // Basis eligibilitas kategori cuti + masa kerja (CutiKategoriService
        // membaca status_pegawai & tmt). Dapodik sync hanya menimpa bila
        // nilai sumbernya tidak kosong, jadi pengisian manual tetap berguna.
        'status_pegawai' => 50, 'tmt' => 10,
        // no_hp sengaja TIDAK ada di sini. Kolom itu milik Dapodik dan
        // ditimpa setiap sinkronisasi, jadi mengeditnya dari form hanya
        // memberi ilusi perubahan — lalu hilang saat sync berikutnya.
        // Nomor notifikasi WhatsApp ditangani terpisah lewat wa_number.
        'agama' => 30, 'api_key' => 100, 'no_ktp' => 20,
        'tempat_lahir' => 50, 'tgl_lahir' => 10, 'alamat_jalan' => 255,
        'kecamatan' => 50, 'kabupaten' => 50, 'provinsi' => 50, 'kode_pos' => 10,
        'telegram_id' => 50,
        // UID kartu RFID (dicetak pada kartu fisik, diisi manual admin).
        'nomor_rfid'  => 64,
        // UserID mesin sidik jari (GEISA GX330), diisi manual admin.
        'fingerprint_uid' => 64,
    ];

    /** Nilai resmi status kepegawaian yang boleh tersimpan. */
    private const STATUS_PEGAWAI_OPSI = [
        'PNS', 'CPNS', 'PPPK', 'Guru Honor Sekolah', 'Tenaga Honor Sekolah',
    ];

    public function save()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id_guru = (int) $this->request->getPost('id_guru');
        if ($id_guru <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID guru tidak valid']);
        }

        // Whitelist field (blokir timpa username/password/id_user/dokumen via POST)
        $raw = $this->request->getPost();
        $data = [];
        foreach (self::EDITABLE_GURU_FIELDS as $field => $maxLen) {
            if (array_key_exists($field, $raw)) {
                $val = trim((string) $raw[$field]);
                if ($field === 'nama_guru') {
                    $val = strtoupper($val);
                }
                if (mb_strlen($val) > $maxLen) {
                    return $this->response->setJSON(['status' => false, 'message' => "Field {$field} terlalu panjang (maks {$maxLen} karakter)"]);
                }
                $data[$field] = $val;
            }
        }
        if (empty($data['nama_guru'])) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama guru wajib diisi']);
        }
        if (isset($data['email']) && $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format email tidak valid']);
        }

        // Status pegawai: hanya nilai resmi yang boleh (mempengaruhi
        // eligibilitas kategori cuti — CutiKategoriService), kosong = tidak terdata.
        // Pemeriksaan final dilakukan setelah data existing terbaca, agar
        // nilai legacy yang tidak diubah tidak ikut ditolak.
        if (array_key_exists('tmt', $data) && $data['tmt'] !== '') {
            [$thn, $bln, $hri] = array_pad(explode('-', $data['tmt']), 3, '0');
            if (! checkdate((int) $bln, (int) $hri, (int) $thn)) {
                return $this->response->setJSON(['status' => false, 'message' => 'TMT pengangkatan tidak valid (yyyy-mm-dd)']);
            }
        }

        // ID Telegram: validasi format SEBELUM kolom dihapus dari whitelist,
        // supaya kolom yang tidak ada di master_guru tidak ikut ter-write.
        if (array_key_exists('telegram_id', $data)) {
            if (!self::ensureTelegramColumn()) {
                unset($data['telegram_id']);
            } else {
                try {
                    $data['telegram_id'] = \App\Libraries\TelegramLink::normalizeChatId($data['telegram_id']);
                } catch (\InvalidArgumentException $e) {
                    return $this->response->setJSON(['status' => false, 'message' => $e->getMessage()]);
                }
            }
        }

// Nomor WhatsApp notifikasi. Ditulis lewat WhatsAppLink supaya
        // normalisasi dan penanganan verifikasinya sama dengan jalur yang dipakai
        // guru sendiri — bukan UPDATE mentah yang bisa melewati validasi.
        $waRaw = null;
        $waSent = array_key_exists('wa_number', $raw);
        if ($waSent) {
            $waRaw = (string) ($raw['wa_number'] ?? '');
        }

        // Get existing guru data to retrieve id_user and existing documents
        $existingGuru = $this->masterModel->getGuruById($id_guru);
        if (!$existingGuru) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak ditemukan']);
        }

        // Status pegawai nilainya divalidasi di sini (data existing sudah
        // terbaca): nilai di luar daftar resmi HANYA boleh bila NOMINALNYA
        // sama dengan yang lama — form edit tidak ditolak total dan tidak
        // ada data legacy yang diam-diam berubah.
        if (array_key_exists('status_pegawai', $data) && $data['status_pegawai'] !== '') {
            $lama = trim((string) ($existingGuru->status_pegawai ?? ''));
            $tetap = ($data['status_pegawai'] === $lama);
            if ($tetap || in_array($data['status_pegawai'], self::STATUS_PEGAWAI_OPSI, true)) {
                // ok
            } else {
                return $this->response->setJSON(['status' => false, 'message' => 'Status pegawai tidak valid']);
            }
        }

        if ($existingGuru && $existingGuru->id_user) {
            $id_user = $existingGuru->id_user;
            $dokumen = json_decode($existingGuru->dokumen ?? '{}', true);
            $docPathRel = 'uploads/users/' . $id_user . '/documents/';
            $docPathAbs = FCPATH . $docPathRel;

            if (!is_dir($docPathAbs)) {
                mkdir($docPathAbs, 0755, true);
            }

            // Validasi file dokumen: gambar/PDF ≤ 2MB
            $allowedDocExt = ['jpg', 'jpeg', 'png', 'pdf'];
            $isValidDoc = function ($file) use ($allowedDocExt) {
                if (!$file || !$file->isValid() || $file->hasMoved()) return false;
                if ($file->getSize() > 2 * 1024 * 1024) return false;
                return in_array(strtolower($file->getExtension()), $allowedDocExt, true);
            };

            // Upload KTP
            $fileKtp = $this->request->getFile('ktp_id');
            if ($isValidDoc($fileKtp)) {
                $newName = 'ktp_' . $fileKtp->getRandomName();
                $fileKtp->move($docPathAbs, $newName);
                $dokumen['ktp'] = $docPathRel . $newName;
            }

            // Upload KK
            $fileKk = $this->request->getFile('kk_id');
            if ($isValidDoc($fileKk)) {
                $newName = 'kk_' . $fileKk->getRandomName();
                $fileKk->move($docPathAbs, $newName);
                $dokumen['kk'] = $docPathRel . $newName;
            }

            // Upload Sertifikat (Multiple)
            if ($filesSerti = $this->request->getFiles()) {
                if (isset($filesSerti['serti'])) {
                    $sertiFiles = $filesSerti['serti'];
                    $sertiPaths = $dokumen['sertifikat'] ?? [];
                    if (!is_array($sertiPaths))
                        $sertiPaths = []; // Safety check

                    foreach ($sertiFiles as $file) {
                        if ($isValidDoc($file)) {
                            $newName = 'sert_' . $file->getRandomName();
                            $file->move($docPathAbs, $newName);
                            $sertiPaths[] = $docPathRel . $newName;
                        }
                    }
                    $dokumen['sertifikat'] = $sertiPaths;
                }
            }

            $data['dokumen'] = json_encode($dokumen);
        }

        $success = $this->masterModel->updateData('master_guru', $data, 'id_guru', $id_guru);
        if ($success) {
            // SYNC TO USERS TABLE
            if ($existingGuru && $existingGuru->id_user) {
                $userUpdate = [];
                // Map fields from guru form to users table
                if (isset($data['email']))
                    $userUpdate['email'] = $data['email'];

                if (isset($data['nama_guru'])) {
                    $nama = trim($data['nama_guru']);
                    $parts = explode(' ', $nama, 2);
                    $userUpdate['first_name'] = $parts[0];
                    $userUpdate['last_name'] = isset($parts[1]) ? $parts[1] : '';
                }

                if (isset($data['no_hp'])) {
                    $userUpdate['phone'] = $data['no_hp'];
                }

                if (!empty($userUpdate)) {
                    $db = \Config\Database::connect();
                    // Check if 'phone' column exists before updating
                    if (isset($userUpdate['phone']) && !$db->fieldExists('phone', 'users')) {
                        unset($userUpdate['phone']);
                    }

                    if (!empty($userUpdate)) {
                        $db->table('users')->where('id', $existingGuru->id_user)->update($userUpdate);
                    }
                }
            }

            $this->logModel->saveLog(2, 'Mengedit profile guru ID: ' . $id_guru); // 2 = EDIT
        }

        // Kanal notifikasi hanya disentuh kalau BENAR-BENAR berubah.
        //
        // Form selalu mengirim telegram_id (kosong saat kolomnya
        // dikosongkan), jadi tanpa perbandingan di bawah, cabang "lepas"
        // berjalan di setiap penyimpanan — bahkan saat admin cuma mengubah
        // email. Akibatnya muncul pesan "Tautan Telegram dilepas" padahal
        // tidak ada yang dilepas.
        $notifChanged = [];

if ($success && array_key_exists('telegram_id', $data)) {
    $idUser = (int) ($existingGuru->id_user ?? 0);
    $chatId = (string) $data['telegram_id'];
    $lama   = trim((string) ($existingGuru->telegram_id ?? ''));

    if ($chatId === $lama) {
        // Tidak berubah — diamkan, jangan laporkan apa pun.
        $notifChanged = [];
    } elseif ($chatId === '') {
        $ok = \App\Libraries\TelegramLink::detachFromGuru($id_guru, $idUser);
        $notifChanged[] = $ok
            ? 'Tautan Telegram dilepas.'
            : 'Gagal melepas tautan Telegram — coba simpan lagi.';
    } elseif ($idUser > 0) {
        $r = \App\Libraries\TelegramLink::attachToGuru($id_guru, $idUser, $chatId);
        $notifChanged[] = $r['ok'] ? $r['message'] : 'ID Telegram tersimpan, tetapi tautan personal gagal: ' . $r['message'];
        } else {
            $notifChanged[] = 'ID Telegram tersimpan di profil guru, tetapi akun login belum tertaut sehingga notifikasi personal belum aktif.';
        }

        // Nomor WhatsApp notifikasi — hanya dilapor bila benar-benar berubah.
        if ($success && $waSent) {
            $idUser = (int) ($existingGuru->id_user ?? 0);
            $lama   = \App\Libraries\WhatsAppLink::numberForGuru($id_guru, $idUser);
            $ditolak = false;

            try {
                $baru = \App\Libraries\WhatsAppLink::normalizeNumber((string) $waRaw);
            } catch (\InvalidArgumentException $e) {
                $baru    = null;
                $ditolak = true;
                $notifChanged[] = 'Nomor WhatsApp ditolak: ' . $e->getMessage();
            }

            if (! $ditolak && $baru === $lama) {
                // Tidak berubah — tidak ada yang perlu dilaporkan.
            } elseif (! $ditolak) {
                $db = \Config\Database::connect();

                if ($idUser <= 0) {
                    $notifChanged[] = 'Nomor WhatsApp tidak bisa ditautkan: akun login guru belum tertaut ke master_guru.';
                } elseif (! $db->fieldExists('wa_number', 'user_notifikasi')) {
                    $notifChanged[] = 'Kolom WhatsApp belum tersedia, jalankan: php spark migrate';
                } else {
                    $r = \App\Libraries\WhatsAppLink::attachToGuru($id_guru, $idUser, (string) $waRaw);
                    $notifChanged[] = $r['ok'] ? $r['message'] : 'Nomor WhatsApp gagal disimpan: ' . $r['message'];
                }
            }
        }
        }

        // method save() ditutup di bawah; blok di atas adalah_if ($success && $waSent)
        return $this->response->setJSON([
            'status' => $success,
            // Daftar kanal yang benar-benar berubah. Kosong = tidak ada
            // yang perlu disebutkan, dan UI cukup menampilkan pesan umum.
            'notif'  => array_values($notifChanged),
        ]);
    }

    /**
     * Lazy migration kolom master_guru.telegram_id (idempotent).
     * Mengembalikan false bila tabel/Forge tidak tersedia.
     */
    private static function ensureTelegramColumn(): bool
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('master_guru')) {
                return false;
            }
            if ($db->fieldExists('telegram_id', 'master_guru')) {
                return true;
            }
            \Config\Database::forge()->addColumn('master_guru', [
                'telegram_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            ]);
            return true;
        } catch (\Throwable $e) {
            log_message('error', '[Dataguru::ensureTelegramColumn] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus guru biasa. Ditolak bila masih memiliki data terkait agar admin
     * memakai halaman detail (hapus permanen dengan konfirmasi).
     */
    public function delete()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Akses ditolak.']);
        }

        $id = (int) $this->request->getPost('id_guru');
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak dipilih.']);
        }

        $guru = $this->masterModel->getGuruById($id);
        if ($guru === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data guru tidak ditemukan.']);
        }

        if ((int) ($guru->id_user ?? 0) > 0 && (int) $guru->id_user === (int) $this->getUserId()) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Data guru ini tertaut dengan akun yang sedang Anda gunakan dan tidak dapat dihapus.',
            ]);
        }

        $linked = $this->guruLinkedCounts($id);
        $total  = array_sum($linked);

        if ($total > 0) {
            return $this->response->setJSON([
                'status'    => false,
                'count'     => $total,
                'breakdown' => $linked,
                'message'   => 'Guru masih memiliki ' . $total . ' data terkait (jabatan, materi, jurnal, forum, atau penunjukan wali kelas). Buka halaman detail guru untuk menghapus permanen.',
            ]);
        }

        if (!$this->masterModel->deleteGuruLinked($id)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Gagal menghapus guru. Silakan coba lagi.',
            ]);
        }

        $this->logModel->saveLog(3, 'Menghapus guru ID: ' . $id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Data guru berhasil dihapus.',
        ]);
    }

    /**
     * Hapus permanen dari halaman detail (beserta seluruh relasinya).
     * Wajib konfirmasi nama guru di sisi UI; server tetap memvalidasi.
     */
    public function forcedelete()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Akses ditolak.']);
        }

        $id = (int) $this->request->getPost('id_guru');
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak dipilih.']);
        }

        $guru = $this->masterModel->getGuruById($id);
        if ($guru === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data guru tidak ditemukan.']);
        }

        if ((int) ($guru->id_user ?? 0) > 0 && (int) $guru->id_user === (int) $this->getUserId()) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Data guru ini tertaut dengan akun yang sedang Anda gunakan dan tidak dapat dihapus.',
            ]);
        }

        $konfirmasi = trim((string) $this->request->getPost('confirm_nama'));
        if ($konfirmasi === '' || $konfirmasi !== trim((string) $guru->nama_guru)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Konfirmasi nama guru tidak cocok. Ketik nama guru dengan tepat untuk melanjutkan.',
            ]);
        }

        if (!$this->masterModel->deleteGuruLinked($id)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Gagal menghapus guru. Periksa log aplikasi lalu coba lagi.',
            ]);
        }

        $this->logModel->saveLog(3, 'Menghapus permanen guru ID: ' . $id . ' (' . $guru->nama_guru . ')');

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Data guru berhasil dihapus permanen.',
        ]);
    }

    /**
     * Rekap data yang akan ikut terhapus/dilepas saat guru dihapus.
     *
     * @return array<string,int>
     */
    private function guruLinkedCounts(int $idGuru): array
    {
        $db = \Config\Database::connect();

        $count = static function (string $table, string $field) use ($db, $idGuru): int {
            if (!$db->tableExists($table) || !$db->fieldExists($field, $table)) {
                return 0;
            }

            return (int) $db->table($table)->where($field, $idGuru)->countAllResults();
        };

        return [
            'jabatan'    => $count('jabatan_guru', 'id_guru'),
            'materi'     => $count('kelas_materi', 'id_guru'),
            'catatan'    => $count('kelas_catatan_mapel', 'id_guru'),
            'jurnal'     => $count('guru_jurnal', 'id_guru'),
            'post'       => $count('post', 'dari'),
            'komentar'   => $count('post_comments', 'dari') + $count('post_reply', 'dari'),
            'wali_kelas' => $count('master_kelas', 'guru_id'),
        ];
    }

    /**
     * Teacher detail page (profil, riwayat penugasan, statistik, hapus).
     */
    public function detail($id_guru)
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        // Sama seperti daftar guru: siswa tidak boleh melihat data guru.
        if ($this->isSiswa()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }
        $canManage = $this->isAdmin();

        $id_guru = (int) $id_guru;
        if ($id_guru <= 0) {
            return redirect()->to('dataguru')->with('error', 'ID guru tidak valid');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('dataguru')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $guru = $this->masterModel->getGuruById($id_guru);
        if ($guru === null) {
            return redirect()->to('dataguru')->with('error', 'Data guru tidak ditemukan');
        }

        $db = \Config\Database::connect();

        $akun = null;
        if (!empty($guru->id_user)) {
            $akun = $db->table('users')
                ->select('id, username, email, active, last_login')
                ->where('id', (int) $guru->id_user)
                ->get()->getRow();
        }

        $normalizeList = static function ($value): array {
            if ($value instanceof \stdClass) {
                $value = [$value];
            }
            if (!is_array($value)) {
                return [];
            }

            return array_map(static fn($item) => is_array($item) ? (object) $item : $item, $value);
        };

        $mapelNames = [];
        foreach ($this->masterModel->getAllMapel() as $mapel) {
            $mapelNames[(int) $mapel->id_mapel] = $mapel->nama_mapel;
        }
        $ekstraNames = $this->dropdownModel->getAllEkskul();

        $jabatan = [];
        $kelasIds = [];
        $jabatanAktif = null;

        foreach ($this->masterModel->getDetailJabatanGuru($id_guru) as $row) {
            $row->mapel_kelas = $normalizeList($this->maybe_unserialize($row->mapel_kelas ?? '[]'));
            $row->ekstra_kelas = $normalizeList($this->maybe_unserialize($row->ekstra_kelas ?? '[]'));

            foreach ($row->mapel_kelas as $mapel) {
                if (empty($mapel->nama_mapel) && !empty($mapel->id_mapel) && isset($mapelNames[(int) $mapel->id_mapel])) {
                    $mapel->nama_mapel = $mapelNames[(int) $mapel->id_mapel];
                }
                foreach (($mapel->kelas_mapel ?? []) as $kelas) {
                    $kelasId = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
                    if ($kelasId > 0) {
                        $kelasIds[$kelasId] = true;
                    }
                }
            }

            foreach ($row->ekstra_kelas as $ekstra) {
                if (empty($ekstra->nama_ekstra) && !empty($ekstra->id_ekstra) && isset($ekstraNames[(int) $ekstra->id_ekstra])) {
                    $ekstra->nama_ekstra = $ekstraNames[(int) $ekstra->id_ekstra];
                }
                foreach (($ekstra->kelas_ekstra ?? []) as $kelas) {
                    $kelasId = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
                    if ($kelasId > 0) {
                        $kelasIds[$kelasId] = true;
                    }
                }
            }

            if (!empty($row->id_kelas)) {
                $kelasIds[(int) $row->id_kelas] = true;
            }

            if ($jabatanAktif === null
                && (int) $row->id_tp === (int) $tp->id_tp
                && (int) $row->id_smt === (int) $smt->id_smt) {
                $jabatanAktif = $row;
            }

            $jabatan[] = $row;
        }

        $kelasMap = [];
        if ($kelasIds) {
            foreach ($db->table('master_kelas')
                ->select('id_kelas, nama_kelas')
                ->whereIn('id_kelas', array_keys($kelasIds))
                ->get()->getResult() as $kelas) {
                $kelasMap[(int) $kelas->id_kelas] = $kelas->nama_kelas;
            }
        }

        $linked = $this->guruLinkedCounts($id_guru);

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Detail Guru',
            'subjudul' => 'Detail Informasi Guru',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'id_guru' => $id_guru,
            'can_manage' => $canManage,
            'guru' => $guru,
            'akun' => $akun,
            'jabatan' => $jabatan,
            'jabatan_aktif' => $jabatanAktif,
            'kelas_map' => $kelasMap,
            'linked' => $linked,
            'total_linked' => array_sum($linked),
        ];

        return view('master/guru/detail', $data);
    }

    /**
     * Import setup
     */
    public function add()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Guru',
            'subjudul' => 'Tambah Data Guru',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
        ];

        return view('master/guru/add', $data);
    }

    /**
     * Process Excel Import (kolom: 1=No, 2=Nama, 3=NIP, 4=Kode Guru, 5=Username, 6=Password)
     * Validasi per baris + cek duplikat (dalam file & database) + insert grup guru + transaksi.
     */
    public function do_import()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $input = $this->request->getPost('guru');
        if (empty($input) || !is_array($input)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada data untuk diimport']);
        }
        if (count($input) > 1000) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 1000 baris per import']);
        }

        $db = \Config\Database::connect();

        // Snapshot existing untuk cek duplikat (sekali query, bukan per baris)
        $usedUsernames = [];
        foreach ($db->table('users')->select('username')->get()->getResult() as $u) {
            $usedUsernames[strtolower(trim((string) $u->username))] = 'users';
        }
        foreach ($db->table('master_guru')->select('username')->get()->getResult() as $g) {
            $un = strtolower(trim((string) ($g->username ?? '')));
            if ($un !== '') $usedUsernames[$un] = 'master_guru';
        }
        $usedNips = [];
        foreach ($db->table('master_guru')->select('nip')->get()->getResult() as $g) {
            $nip = trim((string) ($g->nip ?? ''));
            if ($nip !== '') $usedNips[$nip] = true;
        }

        $valid = [];
        $errors = [];
        $seenUser = [];
        $seenNip = [];
        $rowNo = 0;

        foreach ($input as $guru) {
            $rowNo++;
            if (!is_array($guru)) continue;
            $nama_guru = trim((string) ($guru['2'] ?? ''));
            $nip = trim((string) ($guru['3'] ?? ''));
            $kode = trim((string) ($guru['4'] ?? ''));
            $username = trim((string) ($guru['5'] ?? ''));
            $password = trim((string) ($guru['6'] ?? ''));

            // Lewati baris kosong total
            if ($nama_guru === '' && $username === '' && $password === '' && $nip === '' && $kode === '') {
                continue;
            }
            $label = $nama_guru !== '' ? "Baris {$rowNo} ({$nama_guru})" : "Baris {$rowNo}";

            if ($nama_guru === '') {
                $errors[] = "{$label}: nama wajib diisi";
                continue;
            }
            if (strlen($username) < 4 || strlen($username) > 30 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
                $errors[] = "{$label}: username 4–30 karakter (huruf/angka/titik/strip/underscore)";
                continue;
            }
            $lowUser = strtolower($username);
            if (isset($seenUser[$lowUser])) {
                $errors[] = "{$label}: username ganda dalam file (baris {$seenUser[$lowUser]})";
                continue;
            }
            if (isset($usedUsernames[$lowUser])) {
                $errors[] = "{$label}: username sudah dipakai";
                continue;
            }
            if ($password === '') {
                $password = '123456'; // default bila kosong
            } elseif (strlen($password) < 5) {
                $errors[] = "{$label}: password minimal 5 karakter";
                continue;
            }
            if ($nip !== '') {
                if (!ctype_digit($nip)) {
                    $errors[] = "{$label}: NIP harus angka";
                    continue;
                }
                if (isset($seenNip[$nip]) || isset($usedNips[$nip])) {
                    $errors[] = "{$label}: NIP sudah terdaftar";
                    continue;
                }
            }

            $seenUser[$lowUser] = $rowNo;
            if ($nip !== '') $seenNip[$nip] = $rowNo;
            $valid[] = [
                'nama_guru' => strtoupper(mb_substr($nama_guru, 0, 100)),
                'nip' => $nip,
                'kode_guru' => mb_substr($kode, 0, 30),
                'username' => $username,
                'password' => $password,
            ];
        }

        if (empty($valid)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada baris valid untuk diimport',
                'imported' => 0,
                'skipped' => count($errors),
                'errors' => array_slice($errors, 0, 50),
            ]);
        }

        $imported = 0;
        $db->transBegin();
        try {
            foreach ($valid as $v) {
                $namaArr = preg_split('/\s+/', $v['nama_guru'], -1, PREG_SPLIT_NO_EMPTY);
                $db->table('users')->insert([
                    'username' => $v['username'],
                    'password' => password_hash($v['password'], PASSWORD_BCRYPT),
                    'first_name' => $namaArr[0] ?? $v['username'],
                    'last_name' => count($namaArr) > 1 ? end($namaArr) : '',
                    'active' => 1,
                ]);
                $id_user = $db->insertID();
                // Wajib: masukkan ke grup guru (id=2) agar bisa login sebagai guru
                $db->table('users_groups')->insert(['user_id' => $id_user, 'group_id' => 2]);
                $db->table('master_guru')->insert([
                    'id_user' => $id_user,
                    'nama_guru' => $v['nama_guru'],
                    'nip' => $v['nip'],
                    'kode_guru' => $v['kode_guru'],
                    'username' => $v['username'],
                    'password' => $v['password'],
                    'foto' => null,
                ]);
                $imported++;
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Dataguru::do_import] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan ke database — periksa log aplikasi.']);
        }

        try {
            $this->logModel->saveLog(1, 'Mengimpor data guru sebanyak: ' . $imported);
        } catch (\Throwable $e) {}

        return $this->response->setJSON([
            'status' => true,
            'message' => "Berhasil mengimport {$imported} guru" . ($errors ? ' (' . count($errors) . ' baris dilewati)' : ''),
            'total' => $imported,
            'imported' => $imported,
            'skipped' => count($errors),
            'errors' => array_slice($errors, 0, 50),
        ]);
    }

    /**
     * Unduh format import guru (CSV kompatibel Excel, dibuka & disimpan
     * sebagai .xlsx bila perlu). Kolom: No | Nama | NIP | Kode Guru | Username | Password
     */
    public function downloadFormat()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $rows = [
            ['No', 'Nama', 'NIP', 'Kode Guru', 'Username', 'Password'],
            ['1', 'Contoh Nama Guru, S.Pd', '199001012015031001', 'GR001', 'contoh.guru', '123456'],
            ['2', '', '', '', '', ''],
        ];

        $out = fopen('php://temp', 'r+');
        // BOM agar Excel Windows membaca UTF-8 dengan benar
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($rows as $r) {
            fputcsv($out, $r, ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="format_guru.csv"')
            ->setBody($csv);
    }

    /**
     * Job assignment page
     */
    public function editJabatan($id)
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to('dataguru')->with('error', 'ID guru tidak valid');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('dataguru')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }
        $guru = $this->masterModel->getJabatanGuru($id, $tp->id_tp, $smt->id_smt);

        if (!$guru) {
            // Fallback if not found in jabatan_guru, get from master_guru
            $guruRaw = $this->masterModel->getGuruById($id);
            if (!$guruRaw) {
                return redirect()->to('dataguru')->with('error', 'Guru tidak ditemukan');
            }
            $guru = (object) [
                'id_guru' => $id,
                'nama_guru' => $guruRaw->nama_guru,
                'mapel_kelas' => '[]',
                'ekstra_kelas' => '[]',
                'id_level' => null,
                'id_kelas' => null
            ];
        }

        $guru->mapel_kelas = $this->maybe_unserialize($guru->mapel_kelas ?? '[]');
        if (!is_array($guru->mapel_kelas)) $guru->mapel_kelas = [];
        $guru->ekstra_kelas = $this->maybe_unserialize($guru->ekstra_kelas ?? '[]');
        if (!is_array($guru->ekstra_kelas)) $guru->ekstra_kelas = [];

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Jabatan Guru',
            'subjudul' => 'Edit Jabatan Guru',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'guru' => $guru,
            'kelass' => $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt),
            'mapels' => $this->dropdownModel->getAllMapel(),
            'levels' => $this->dropdownModel->getAllLevelGuru(),
            'ekskul' => $this->dropdownModel->getAllEkskul(),
        ];

        return view('master/guru/editmapel', $data);
    }

    /**
     * Save job assignment
     */
    public function saveJabatan()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id_guru = (int) $this->request->getPost('id_guru');
        $id_level = trim((string) ($this->request->getPost('level') ?? ''));
        $kelas_wali = (int) ($this->request->getPost('kelas_wali') ?? 0);

        if ($id_guru <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID guru tidak valid']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $db = \Config\Database::connect();

        // Guru harus ada
        if (!$db->table('master_guru')->where('id_guru', $id_guru)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak ditemukan']);
        }

        // Level jabatan harus terdaftar (boleh kosong = tanpa jabatan)
        if ($id_level !== '') {
            $levelOk = $db->table('level_guru')->where('id_level', $id_level)->countAllResults() > 0;
            if (!$levelOk) {
                return $this->response->setJSON(['status' => false, 'message' => 'Jabatan tidak valid']);
            }
        }

        // Peta validasi: mapel/ekstra/kelas yang sah pada TP/SMT aktif
        $validMapel = [];
        foreach ($db->table('master_mapel')->select('id_mapel, nama_mapel')->where('status', '1')->get()->getResult() as $m) {
            $validMapel[(int) $m->id_mapel] = $m->nama_mapel;
        }
        $validEkstra = [];
        if ($db->tableExists('master_ekstra')) {
            foreach ($db->table('master_ekstra')->select('id_ekstra, nama_ekstra')->get()->getResult() as $e) {
                $validEkstra[(int) $e->id_ekstra] = $e->nama_ekstra;
            }
        }
        $validKelas = [];
        foreach ($db->table('master_kelas')->select('id_kelas')->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)->get()->getResult() as $k) {
            $validKelas[(int) $k->id_kelas] = true;
        }

        // Wali kelas harus kelas valid pada TP/SMT aktif
        if ($kelas_wali > 0 && !isset($validKelas[$kelas_wali])) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas wali tidak valid pada TP/SMT aktif']);
        }
        // Hanya level 4 (Wali Kelas) yang boleh punya kelas wali
        if ($id_level !== '4') $kelas_wali = 0;

        $mapels = [];
        $postedMapels = $this->request->getPost('mapel');
        if (is_array($postedMapels)) {
            $seenMapel = [];
            foreach ($postedMapels as $mapelId) {
                $mapelId = (int) $mapelId;
                if (!isset($validMapel[$mapelId])) continue; // tolak mapel ngawur
                if (isset($seenMapel[$mapelId])) continue;
                $seenMapel[$mapelId] = true;

                $kelasmapel = $this->request->getPost('kelasmapel' . $mapelId);

                if (is_array($kelasmapel) && $kelasmapel) {
                    $selectedKelas = [];
                    foreach ($kelasmapel as $kls) {
                        $kls = (int) $kls;
                        if (isset($validKelas[$kls])) $selectedKelas[$kls] = ['kelas' => $kls];
                    }
                    if ($selectedKelas) {
                        $mapels[] = [
                            'id_mapel' => $mapelId,
                            'nama_mapel' => $validMapel[$mapelId], // nama otoritatif dari master
                            'kelas_mapel' => array_values($selectedKelas)
                        ];
                    }
                }
            }
        }

        $ekstras = [];
        $postedEkstras = $this->request->getPost('ekstra');
        if (is_array($postedEkstras)) {
            foreach ($postedEkstras as $exId) {
                $exId = (int) $exId;
                if (!isset($validEkstra[$exId])) continue;
                $kelasekstra = $this->request->getPost('kelasekstra' . $exId);
                if (is_array($kelasekstra) && $kelasekstra) {
                    $selectedKelas = [];
                    foreach ($kelasekstra as $kls) {
                        $kls = (int) $kls;
                        if (isset($validKelas[$kls])) $selectedKelas[] = ['kelas' => $kls];
                    }
                    if ($selectedKelas) {
                        $ekstras[] = [
                            'id_ekstra' => $exId,
                            'nama_ekstra' => $validEkstra[$exId],
                            'kelas_ekstra' => $selectedKelas
                        ];
                    }
                }
            }
        }

        // Relasi guru-mapel dijaga tetap sinkron dengan jadwal: pasangan
        // kelas:mapel yang masih punya slot tidak boleh dilepas begitu saja,
        // karena slot akan kehilangan guru pengajar dan hilang dari quick-pick.
        $existingRow = $this->jabatanModel->findForGuru($id_guru, (int) $tp->id_tp, (int) $smt->id_smt);

        $assignmentModel = new \App\Models\ElearningModel();
        $oldPairs = [];
        if ($existingRow) {
            foreach ($assignmentModel->decodeMapelKelasAssignments($existingRow->mapel_kelas) as $item) {
                if (!is_array($item)) continue;
                $oldMapelId = (int) ($item['id_mapel'] ?? 0);
                if ($oldMapelId <= 0) continue;

                foreach (($item['kelas_mapel'] ?? []) as $km) {
                    $oldKelasId = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($oldKelasId > 0) {
                        $oldPairs[$oldKelasId . ':' . $oldMapelId] = ['kelas' => $oldKelasId, 'mapel' => $oldMapelId];
                    }
                }
            }
        }

        $newPairs = [];
        foreach ($mapels as $m) {
            foreach ($m['kelas_mapel'] as $km) {
                $newPairs[$km['kelas'] . ':' . $m['id_mapel']] = ['kelas' => (int) $km['kelas'], 'mapel' => (int) $m['id_mapel']];
            }
        }

        $removedPairs = array_diff_key($oldPairs, $newPairs);
        if ($removedPairs) {
            // Pasangan yang masih dipegang guru lain (data ganda lama) tetap
            // aman dilepas karena slot jadwal masih punya pengajar.
            $coveredElsewhere = [];
            foreach ($db->table('jabatan_guru')->select('mapel_kelas')
                ->where('id_tp', $tp->id_tp)
                ->where('id_smt', $smt->id_smt)
                ->where('id_guru !=', $id_guru)
                ->get()->getResult() as $other) {
                foreach ($assignmentModel->decodeMapelKelasAssignments($other->mapel_kelas) as $item) {
                    if (!is_array($item)) continue;
                    $otherMapelId = (int) ($item['id_mapel'] ?? 0);
                    if ($otherMapelId <= 0) continue;
                    foreach (($item['kelas_mapel'] ?? []) as $km) {
                        $otherKelasId = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                        $otherKey = $otherKelasId . ':' . $otherMapelId;
                        if (isset($removedPairs[$otherKey])) $coveredElsewhere[$otherKey] = true;
                    }
                }
            }

            $scheduledRows = $db->table('kelas_jadwal_mapel')
                ->select('id_kelas, id_mapel')
                ->where('id_tp', $tp->id_tp)
                ->where('id_smt', $smt->id_smt)
                ->where('id_mapel >', 0)
                ->whereIn('id_kelas', array_unique(array_column($removedPairs, 'kelas')))
                ->whereIn('id_mapel', array_unique(array_column($removedPairs, 'mapel')))
                ->get()->getResult();

            $blocked = [];
            foreach ($scheduledRows as $s) {
                $key = (int) $s->id_kelas . ':' . (int) $s->id_mapel;
                if (isset($removedPairs[$key]) && !isset($coveredElsewhere[$key])) {
                    $blocked[$key] = $removedPairs[$key];
                }
            }

            if ($blocked) {
                $kelasNames = [];
                foreach ($db->table('master_kelas')->select('id_kelas, nama_kelas')
                    ->whereIn('id_kelas', array_unique(array_column($blocked, 'kelas')))
                    ->get()->getResult() as $k) {
                    $kelasNames[(int) $k->id_kelas] = $k->nama_kelas;
                }
                $mapelNames = [];
                foreach ($db->table('master_mapel')->select('id_mapel, nama_mapel')
                    ->whereIn('id_mapel', array_unique(array_column($blocked, 'mapel')))
                    ->get()->getResult() as $m) {
                    $mapelNames[(int) $m->id_mapel] = $m->nama_mapel;
                }

                $labels = [];
                foreach ($blocked as $pair) {
                    $labels[] = ($mapelNames[$pair['mapel']] ?? ('Mapel #' . $pair['mapel']))
                        . ' - ' . ($kelasNames[$pair['kelas']] ?? ('Kelas #' . $pair['kelas']));
                }
                $labelText = implode(', ', array_slice($labels, 0, 3));
                if (count($labels) > 3) $labelText .= ' dan ' . (count($labels) - 3) . ' lainnya';

                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Tidak bisa melepas ' . $labelText
                        . ' karena masih terpasang di jadwal. Tugaskan guru pengganti lewat halaman Edit Jabatan guru baru, atau hapus slot jadwalnya terlebih dahulu.'
                ]);
            }
        }

        $data = [
            'id_jabatan' => $id_level ?: '',
            'id_kelas' => $kelas_wali ?: 0,
            'mapel_kelas' => json_encode($mapels),
            'ekstra_kelas' => json_encode($ekstras),
        ];

        $db->transBegin();
        try {
            // Jika guru ini jadi Wali Kelas, copot wali lama di kelas yang sama
            if ($id_level === '4' && $kelas_wali > 0) {
                $this->jabatanModel->releaseWaliKelas((int) $tp->id_tp, (int) $smt->id_smt, $kelas_wali, $id_guru);
            }

            if (!$this->jabatanModel->upsert($id_guru, (int) $tp->id_tp, (int) $smt->id_smt, $data)) {
                throw new \RuntimeException('Query simpan jabatan gagal');
            }

            // master_kelas.guru_id adalah sumber kedua wali kelas; samakan
            // agar dashboard dan scoping presensi tidak melihat wali berbeda.
            $this->jabatanModel->syncWaliToMasterKelas(
                $id_guru,
                (int) $tp->id_tp,
                (int) $smt->id_smt,
                ($id_level === '4' && $kelas_wali > 0) ? $kelas_wali : 0
            );

            // Satu pasangan kelas:mapel hanya diampu satu guru. Lepaskan
            // pasangan yang baru disimpan ini dari guru lain agar jadwal,
            // quick-pick jurnal, dan monitoring tidak menampilkan sesi ganda.
            $this->removePairsFromOtherTeachers((int) $tp->id_tp, (int) $smt->id_smt, $newPairs, $id_guru);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[saveJabatan] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan data']);
        }

        $this->logModel->saveLog(2, "Mengedit jabatan guru ID: $id_guru pada TP: $tp->id_tp SMT: $smt->id_smt");

        return $this->response->setJSON(['status' => true, 'message' => 'Data berhasil disimpan']);
    }

    /**
     * Lepas pasangan kelas:mapel yang baru dipegang guru ini dari guru lain
     * pada periode aktif, sehingga satu kelas-mapel tidak punya dua pengampu.
     *
     * @param array<string, array{kelas:int, mapel:int}> $pairs
     */
    private function removePairsFromOtherTeachers(int $idTp, int $idSmt, array $pairs, int $keepGuru): void
    {
        if (empty($pairs)) return;

        $db = \Config\Database::connect();
        $assignmentModel = new \App\Models\ElearningModel();
        $rows = $db->table('jabatan_guru')
            ->select('id_jabatan_guru, mapel_kelas')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('id_guru !=', $keepGuru)
            ->get()->getResult();

        foreach ($rows as $row) {
            $mapels = $assignmentModel->decodeMapelKelasAssignments($row->mapel_kelas);
            $changed = false;

            foreach ($mapels as $i => $item) {
                if (!is_array($item)) continue;

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                if ($idMapel <= 0) continue;

                $kelasList = isset($item['kelas_mapel']) && is_array($item['kelas_mapel']) ? $item['kelas_mapel'] : [];
                $sisa = [];
                $itemChanged = false;

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas > 0 && isset($pairs[$idKelas . ':' . $idMapel])) {
                        $itemChanged = true;
                        continue;
                    }
                    $sisa[] = $km;
                }

                if (!$itemChanged) continue;

                $changed = true;
                if (empty($sisa)) {
                    unset($mapels[$i]);
                } else {
                    $mapels[$i]['kelas_mapel'] = array_values($sisa);
                }
            }

            if ($changed) {
                $db->table('jabatan_guru')
                    ->where('id_jabatan_guru', $row->id_jabatan_guru)
                    ->update(['mapel_kelas' => json_encode(array_values($mapels))]);
            }
        }
    }

    /**
     * Save signature image
     */
    public function save_signature()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id_guru = $this->request->getPost('id_guru');
        $image = $this->request->getPost('image');

        if (empty($image) || empty($id_guru)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap']);
        }

        // Batasi ukuran payload base64 ±700KB (≈500KB PNG) cegah DoS
        if (strlen((string) $image) > 700 * 1024) {
            return $this->response->setJSON(['status' => false, 'message' => 'Gambar tanda tangan terlalu besar']);
        }

        $guru = $this->masterModel->getGuruById((int) $id_guru);
        if (!$guru)
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak ditemukan']);
        if (!$guru->id_user)
            return $this->response->setJSON(['status' => false, 'message' => 'User ID tidak valid']);

        if (!preg_match('/^data:image\/png;base64,/', $image)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format gambar tidak valid']);
        }

        $image = preg_replace('#^data:image/\w+;base64,#i', '', $image);
        $image = str_replace(' ', '+', $image);
        $data = base64_decode($image, true);

        if ($data === false || $data === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal decode gambar']);
        }

        // Pastikan benar file PNG (magic bytes), bukan skrip tersamar
        if (substr($data, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return $this->response->setJSON(['status' => false, 'message' => 'Format gambar tidak valid']);
        }

        $file_name = 'ttd_' . $id_guru . '_' . time() . '.png';
        $dirPath = FCPATH . 'uploads/users/' . $guru->id_user . '/profile/';

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $dbPath = 'uploads/users/' . $guru->id_user . '/profile/' . $file_name;

        if (file_put_contents(FCPATH . $dbPath, $data)) {
            if (!empty($guru->ttd) && file_exists(FCPATH . $guru->ttd)) {
                @unlink(FCPATH . $guru->ttd);
            }

            $this->masterModel->updateData('master_guru', ['ttd' => $dbPath], 'id_guru', $id_guru);

            return $this->response->setJSON([
                'status' => true,
                'src' => base_url($dbPath),
                'message' => 'Tanda tangan berhasil disimpan'
            ]);
        }

        return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan file tanda tangan']);
    }

    /**
     * Delete signature
     */
    public function delete_ttd()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id_guru = $this->request->getPost('id_guru');
        $guru = $this->masterModel->getGuruById((int) $id_guru);

        if ($guru && !empty($guru->ttd)) {
            if (file_exists(FCPATH . $guru->ttd)) {
                unlink(FCPATH . $guru->ttd);
            }
            $this->masterModel->updateData('master_guru', ['ttd' => null], 'id_guru', $id_guru);
            return $this->response->setJSON(['status' => true, 'message' => 'Tanda tangan berhasil dihapus']);
        }

        return $this->response->setJSON(['status' => false, 'message' => 'Tanda tangan tidak ditemukan']);
    }

    /**
     * Upload photo
     */
    public function uploadFile($id_guru)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $file = $this->request->getFile('foto');
        $guru = $this->masterModel->getGuruById((int) $id_guru);

        if (!$guru)
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak ditemukan']);

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $id_user = $guru->id_user;
            if (!$id_user) {
                return $this->response->setJSON(['status' => false, 'message' => 'User ID null']);
            }

            // Validasi foto: JPG/PNG ≤ 2MB — cek MIME asli + is_image,
            // bukan hanya ekstensi nama file (mencegah polyglot/upload PHP).
            if ($file->getSize() > 2 * 1024 * 1024) {
                return $this->response->setJSON(['status' => false, 'message' => 'Ukuran foto maksimal 2MB']);
            }
            if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png'], true)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format foto harus JPG/PNG']);
            }
            if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format foto harus JPG/PNG']);
            }

            $newName = $file->getRandomName();
            $pathDir = FCPATH . 'uploads/users/' . $id_user . '/profile/';

            if (!is_dir($pathDir)) {
                mkdir($pathDir, 0755, true);
            }

            $file->move($pathDir, $newName);
            $dbPath = 'uploads/users/' . $id_user . '/profile/' . $newName;

            $this->masterModel->updateData('master_guru', ['foto' => $dbPath], 'id_guru', $id_guru);

            // Sync to Users Table
            $db = \Config\Database::connect();
            $db->table('users')->where('id', $id_user)->update(['foto' => $dbPath]);

            $this->logModel->saveLog(2, 'Mengubah foto guru: ' . $guru->nama_guru);

            return $this->response->setJSON([
                'status' => true,
                'src' => base_url($dbPath),
                'filename' => $newName
            ]);
        }

        return $this->response->setJSON(['status' => false, 'message' => 'Gagal upload foto']);
    }

    /**
     * Delete Profile Photo
     */
    public function delete_foto($id_guru)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $guru = $this->masterModel->getGuruById((int) $id_guru);

        if ($guru && $guru->foto && file_exists(FCPATH . $guru->foto)) {
            // Jangan hapus jika itu file default di assets
            if (strpos($guru->foto, 'assets/img/') === false) {
                @unlink(FCPATH . $guru->foto);
            }
        }

        $this->masterModel->updateData('master_guru', ['foto' => null], 'id_guru', $id_guru);

        // Sync Users
        if ($guru && $guru->id_user) {
            $db = \Config\Database::connect();
            $db->table('users')->where('id', $guru->id_user)->update(['foto' => null]);
        }

        return $this->response->setJSON(['status' => true]);
    }

    /**
     * Normalisasi path hapus file: wajib di dalam uploads/ (cegah path traversal
     * seperti ../../.env yang bisa menghapus file sistem). Return null bila tak valid.
     */
    private function resolveDeletablePath(?string $src): ?string
    {
        if ($src === null || $src === '') return null;
        $rel = ltrim(str_replace(base_url(), '', $src), '/');
        // Tolak URL absolut luar, traversal, dan file di luar uploads/
        if (preg_match('#^https?://#i', $rel)) return null;
        if (strpos($rel, '..') !== false) return null;
        if (strpos($rel, 'uploads/') !== 0) return null;
        $abs = realpath(FCPATH . $rel);
        $uploadsRoot = realpath(FCPATH . 'uploads');
        if ($abs === false || $uploadsRoot === false || strpos($abs, $uploadsRoot) !== 0 || !is_file($abs)) {
            return null;
        }
        // FCPATH berakhir '/' di Linux tapi '\' di Windows; potong dari akar yang
        // sudah dibersihkan lalu paksa separator maju agar sama dengan nilai kolom
        // foto. Irisannya dijaga: di luar uploads/ lebih baik ditolak.
        $out = substr($abs, strlen(rtrim(FCPATH, '/\\')) + 1);
        $out = str_replace('\\', '/', $out);
        return strpos($out, 'uploads/') === 0 ? $out : null;
    }

    /**
     * Delete photo
     */
    public function deleteFile($id_guru)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $src = $this->request->getGet('src');
        $file_path = $this->resolveDeletablePath($src);

        if ($file_path !== null && $file_path != 'assets/img/guru.png' && file_exists(FCPATH . $file_path)) {
            unlink(FCPATH . $file_path);
            $this->masterModel->updateData('master_guru', ['foto' => ''], 'id_guru', $id_guru);
            $this->logModel->saveLog(3, 'Menghapus foto guru: ' . $file_path);
            return $this->response->setJSON(['status' => true]);
        }

        return $this->response->setJSON(['status' => false]);
    }

    /**
     * Delete document
     */
    public function delete_dokumen($id_guru)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $src = $this->request->getGet('src');
        $file_name = $this->resolveDeletablePath($src);
        if ($file_name === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Path file tidak valid']);
        }

        $guru = $this->masterModel->getGuruById((int) $id_guru);
        if (!$guru) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tidak ditemukan']);
        }
        $dokumen = json_decode($guru->dokumen ?? '{}', true);
        if (!is_array($dokumen)) $dokumen = [];

        // Identify category based on file name prefix
        $type = basename($file_name);
        $file_type = explode('_', $type)[0];
        if ($file_type == 'sert')
            $file_type = 'sertifikat';

        if (isset($dokumen[$file_type])) {
            if (is_array($dokumen[$file_type])) {
                $dokumen[$file_type] = array_values(array_filter($dokumen[$file_type], function ($item) use ($file_name) {
                    return $item !== $file_name;
                }));
            } else {
                unset($dokumen[$file_type]);
            }
        }

        if (file_exists(FCPATH . $file_name)) {
            unlink(FCPATH . $file_name);
            $this->masterModel->updateData('master_guru', ['dokumen' => json_encode($dokumen)], 'id_guru', $id_guru);
            $this->logModel->saveLog(3, 'Menghapus dokumen ' . $file_type . ' guru: ' . $guru->nama_guru);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Dokumen berhasil dihapus',
            'dokumen' => $dokumen
        ]);
    }

    /**
     * Edit login credentials
     */
    public function editLogin()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id_guru = (int) $this->request->getPost('id_guru');
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('new');
        $passwordConfirm = (string) $this->request->getPost('new_confirm');

        // Username selalu wajib; password opsional (kosong = tidak diganti, sesuai label form)
        $rules = ['username' => 'required|min_length[4]|max_length[30]|alpha_dash'];
        if ($password !== '' || $passwordConfirm !== '') {
            $rules['new'] = 'required|min_length[6]|max_length[72]';
            $rules['new_confirm'] = 'required|matches[new]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $db = \Config\Database::connect();
        $guru = $db->table('master_guru')->where('id_guru', $id_guru)->get()->getRow();

        if (!$guru) {
            return $this->response->setJSON(['status' => false, 'text' => 'Guru tidak ditemukan']);
        }
        if (!$guru->id_user) {
            return $this->response->setJSON(['status' => false, 'text' => 'Akun user guru belum ada']);
        }

        // Username harus unik (kecuali milik sendiri)
        $dupe = $db->table('users')->where('username', $username)->where('id !=', $guru->id_user)->countAllResults();
        if ($dupe > 0) {
            return $this->response->setJSON(['status' => false, 'errors' => ['username' => 'Username sudah dipakai akun lain']]);
        }

        // Update identity
        $userUpdate = ['username' => $username];
        $guruUpdate = ['username' => $username];
        $changedPassword = false;
        if ($password !== '') {
            $userUpdate['password'] = password_hash($password, PASSWORD_BCRYPT);
            $guruUpdate['password'] = $password; // plaintext legacy di master_guru
            $changedPassword = true;
        }
        $db->table('users')->where('id', $guru->id_user)->update($userUpdate);

        // Update master_guru for compatibility
        $db->table('master_guru')->where('id_guru', $id_guru)->update($guruUpdate);

        $this->logModel->saveLog(2, 'Mengubah login guru: ' . $guru->nama_guru);

        return $this->response->setJSON([
            'status' => true,
            'text' => $changedPassword ? 'Berhasil mengganti username & password' : 'Username berhasil diperbarui (password tidak diubah)'
        ]);
    }

    /**
     * Update Data Guru page
     */
    public function update()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Update Data Guru',
            'subjudul' => 'Update Data & Foto Guru',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'gurus' => $this->masterModel->getAllDataGuru($tp->id_tp, $smt->id_smt)
        ];

        return view('master/guru/update', $data);
    }

    /**
     * Download data for update
     */
    public function downloadData()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $db = \Config\Database::connect();
        $result = $db->table('master_guru')
            ->select('id_guru, nama_guru, jenis_kelamin, foto, username, nip')
            ->get()
            ->getResult();

        foreach ($result as $ind => $guru) {
            $guru->no = ($ind + 1);
            $guru->foto = (!empty($guru->foto) && file_exists(FCPATH . $guru->foto)) ? base_url($guru->foto) : base_url('assets/img/guru.png');
            $guru->jenis_kelamin = $guru->jenis_kelamin ?? '';
        }

        return $this->response->setJSON(['status' => true, 'guru' => $result]);
    }

    /**
     * Update all teachers from Excel
     */
    public function update_all()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['status' => false]);
        }

        $update_guru = $this->request->getPost('guru');
        if (empty($update_guru) || !is_array($update_guru)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada data untuk diupdate']);
        }
        if (count($update_guru) > 1000) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 1.000 baris per update']);
        }

        // Hanya kolom yang memang boleh ditimpa dari lembar Excel. Sheet selalu
        // mengirim "no" (nomor baris) yang bukan kolom master_guru, jadi
        // seluruh batch pernah ditolak DB tanpa hasilnya pernah diperiksa:
        // UI melaporkan "Berhasil" padahal tidak ada satu baris pun berubah.
        $boleh = ['nip' => 30, 'nama_guru' => 50, 'jenis_kelamin' => 10, 'username' => 50];

        $db = \Config\Database::connect();

        $lama = [];
        foreach ($db->table('master_guru')->select('id_guru, id_user, username')->get()->getResultArray() as $r) {
            $lama[(int) $r['id_guru']] = $r;
        }
        $idUserByUsername = [];
        foreach ($db->table('users')->select('id, username')->get()->getResultArray() as $u) {
            $idUserByUsername[strtolower(trim((string) $u['username']))] = (int) $u['id'];
        }

        $clean = [];
        $gantiPassword = [];
        $gantiUsername = [];
        $alasan = [];
        $nipTerpakai = [];
        $usernameBatch = [];
        $nomor = 0;

        foreach ($update_guru as $baris) {
            $nomor++;
            if (!is_array($baris) || empty($baris['id_guru'])) {
                $alasan[] = "baris {$nomor}: id_guru kosong";
                continue;
            }
            $idGuru = (int) $baris['id_guru'];
            if (!isset($lama[$idGuru])) {
                $alasan[] = "baris {$nomor}: guru tidak ditemukan (mungkin sudah dihapus)";
                continue;
            }

            $entry    = ['id_guru' => $idGuru];
            $username = null;
            $valid    = true;

            foreach ($boleh as $kolom => $maks) {
                if (!array_key_exists($kolom, $baris)) {
                    continue;
                }
                $val = trim((string) $baris[$kolom]);

                if ($kolom === 'nama_guru') {
                    if ($val === '' || mb_strlen($val) > $maks) {
                        $alasan[] = "baris {$nomor}: nama wajib diisi, maksimal {$maks} karakter";
                        $valid = false;
                        break;
                    }
                } elseif ($kolom === 'nip') {
                    if ($val === '' || !ctype_digit($val) || strlen($val) > $maks) {
                        $alasan[] = "baris {$nomor}: NIP harus angka (maks {$maks} digit)";
                        $valid = false;
                        break;
                    }
                    if (isset($nipTerpakai[$val])) {
                        $alasan[] = "baris {$nomor}: NIP {$val} dipakai lebih dari satu baris di file ini";
                        $valid = false;
                        break;
                    }
                    $nipTerpakai[$val] = $nomor;
                } elseif ($kolom === 'jenis_kelamin') {
                    $val = strtoupper($val);
                    if (!in_array($val, ['L', 'P'], true)) {
                        $alasan[] = "baris {$nomor}: jenis kelamin isi L atau P";
                        $valid = false;
                        break;
                    }
                } else { // username
                    if (!preg_match('/^[A-Za-z0-9_.\-]{4,50}$/', $val)) {
                        $alasan[] = "baris {$nomor}: username 4-50 karakter (huruf/angka/titik/strip/underscore)";
                        $valid = false;
                        break;
                    }
                    $pemilik = $idUserByUsername[strtolower($val)] ?? null;
                    if ($pemilik !== null && $pemilik !== (int) $lama[$idGuru]['id_user']) {
                        $alasan[] = "baris {$nomor}: username {$val} sudah dipakai akun lain";
                        $valid = false;
                        break;
                    }
                    if (($usernameBatch[strtolower($val)] ?? 0) > 0 && $usernameBatch[strtolower($val)] !== $idGuru) {
                        $alasan[] = "baris {$nomor}: username {$val} dipakai lebih dari satu baris di file ini";
                        $valid = false;
                        break;
                    }
                    $usernameBatch[strtolower($val)] = $idGuru;
                    $username = $val;
                }

                $entry[$kolom] = $val;
            }

            if (!$valid) {
                continue;
            }

            // Password hanya sentuh bila lembar benar-benar mengisinya. Dulu
            // semua baris di-hash ulang dari master_guru.password walau kolomnya
            // kosong, sehingga guru yang sudah mengganti sandi sendiri tiba-tiba
            // kembali ke sandi lama saat admin menjalankan update massal.
            $passwordBaru = trim((string) ($baris['password'] ?? ''));
            if ($passwordBaru !== '') {
                if (mb_strlen($passwordBaru) < 5 || mb_strlen($passwordBaru) > 72) {
                    $alasan[] = "baris {$nomor}: password minimal 5 dan maksimal 72 karakter";
                    continue;
                }
                $entry['password'] = $passwordBaru;
                $idUser = (int) $lama[$idGuru]['id_user'];
                if ($idUser > 0) {
                    $gantiPassword[$idUser] = $passwordBaru;
                }
            }

            if ($username !== null && $username !== trim((string) $lama[$idGuru]['username'])) {
                $idUser = (int) $lama[$idGuru]['id_user'];
                if ($idUser > 0) {
                    $gantiUsername[$idUser] = $username;
                }
            }

            if (count($entry) > 1) {
                $clean[] = $entry;
            }
        }

        if ($clean === []) {
            $petikan = $alasan === [] ? '' : ' — ' . implode('; ', array_slice($alasan, 0, 8));

            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Tidak ada baris yang bisa disimpan, jadi tidak ada data yang berubah.' . $petikan,
            ]);
        }

        $db->transBegin();
        try {
            if ($db->table('master_guru')->updateBatch($clean, 'id_guru') === false || !$db->transStatus()) {
                $db->transRollback();
                log_message('error', '[Dataguru::update_all] updateBatch master_guru ditolak DB (' . count($clean) . ' baris)');

                return $this->response->setJSON([
                    'status'  => false,
                    'message' => 'Data guru gagal disimpan. Tidak ada yang diubah — perbaiki isian yang ditolak lalu coba lagi.',
                ]);
            }

            foreach ($gantiPassword as $idUser => $plain) {
                if ($db->table('users')->where('id', $idUser)->update(['password' => password_hash($plain, PASSWORD_BCRYPT)]) === false) {
                    $db->transRollback();
                    log_message('error', '[Dataguru::update_all] update users.password gagal untuk id_user ' . $idUser);

                    return $this->response->setJSON([
                        'status'  => false,
                        'message' => 'Sandi guru gagal disimpan. Tidak ada data yang diubah — coba lagi.',
                    ]);
                }
            }

            foreach ($gantiUsername as $idUser => $baru) {
                if ($db->table('users')->where('id', $idUser)->update(['username' => $baru]) === false) {
                    $db->transRollback();
                    log_message('error', '[Dataguru::update_all] update users.username gagal untuk id_user ' . $idUser);

                    return $this->response->setJSON([
                        'status'  => false,
                        'message' => 'Perubahan username gagal disimpan. Tidak ada data yang diubah — coba lagi.',
                    ]);
                }
            }

            if (!$db->transStatus()) {
                $db->transRollback();
                log_message('error', '[Dataguru::update_all] transaksi ditolak DB');

                return $this->response->setJSON([
                    'status'  => false,
                    'message' => 'Data guru gagal disimpan. Tidak ada yang diubah — coba lagi, dan bila berulang lapor ke pengembang.',
                ]);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            if ($db->transStatus() === false) {
                $db->transRollback();
            }
            log_message('error', '[Dataguru::update_all] ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Data guru gagal disimpan. Tidak ada yang diubah — coba lagi, dan bila berulang lapor ke pengembang.',
            ]);
        }

        $kurang = count($update_guru) - count($clean);
        $this->logModel->saveLog(2, 'Mengupdate data ' . count($clean) . ' guru');

        return $this->response->setJSON([
            'status'  => true,
            'user'    => count($clean),
            'message' => 'Berhasil memperbarui ' . count($clean) . ' guru'
                . ($kurang > 0 ? ' (' . $kurang . ' baris dilewati — periksa ulang file Anda)' : ''),
        ]);
    }

    /**
     * Update photos from Word
     */
    public function update_foto()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['status' => false]);
        }

        $input = $this->request->getPost('guru');
        if (empty($input) || !is_array($input)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada foto untuk diupdate']);
        }
        if (count($input) > 500) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 500 foto per batch']);
        }

        $db = \Config\Database::connect();

        // Hanya id_guru yang benar-benar ada; tanpa ini payload bisa menuliskan
        // path foto ke baris mana pun (termasuk milik guru lain).
        $validIds = [];
        try {
            foreach ($db->table('master_guru')->select('id_guru')->get()->getResult() as $r) {
                $validIds[(int) $r->id_guru] = true;
            }
        } catch (\Throwable $e) {
            log_message('error', '[Dataguru::update_foto] preload: ' . $e->getMessage());
        }

        $dir = 'uploads/profile_guru/';
        if (!is_dir(FCPATH . $dir)) {
            mkdir(FCPATH . $dir, 0755, true);
        }

        $siswas = [];
        $skipped = 0;
        foreach ($input as $value) {
            if (!is_array($value) || empty($value['id_guru']) || !isset($validIds[(int) $value['id_guru']])) {
                $skipped++;
                continue;
            }
            if (empty($value['foto'])) {
                $skipped++;
                continue;
            }

            $ext = strtolower(trim((string) ($value['ext'] ?? 'jpg')));
            // Hanya jpg/png — ekstensi lain (mis. php) ditolak mentah-mentah.
            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $skipped++;
                continue;
            }
            $raw = base64_decode((string) $value['foto'], true);
            if ($raw === false || strlen($raw) > 2 * 1024 * 1024) {
                $skipped++;
                continue;
            }
            // Pastikan benar file gambar (bukan skrip berekstensi gambar).
            $info = @getimagesizefromstring($raw);
            if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                $skipped++;
                continue;
            }

            $idGuru = (int) $value['id_guru'];
            $safeNama = preg_replace('/[^0-9A-Za-z\-_]/', '', trim((string) ($value['nip'] ?? 'guru' . $idGuru)));
            if ($safeNama === '') {
                $safeNama = 'guru' . $idGuru;
            }
            $file_path = $dir . $safeNama . '.jpg';
            if (file_put_contents(FCPATH . $file_path, $raw) === false) {
                $skipped++;
                continue;
            }

            $siswas[] = [
                'id_guru' => $idGuru,
                'foto'    => $file_path,
            ];
        }

        $tersimpan = 0;
        if (!empty($siswas)) {
            $tertulis = $db->table('master_guru')->updateBatch($siswas, 'id_guru');
            if ($tertulis === false) {
                log_message('error', '[Dataguru::update_foto] updateBatch ditolak DB (' . count($siswas) . ' baris)');

                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Foto gagal ditautkan ke data guru. File gambarnya sudah tersimpan — ulangi menyimpan, dan bila berulang lapor ke pengembang.',
                ]);
            }
            $tersimpan = count($siswas);
            $this->logModel->saveLog(2, 'Mengupdate foto ' . $tersimpan . ' guru');
        }

        return $this->response->setJSON([
            'status' => $tersimpan > 0,
            'user'   => $tersimpan,
            'message' => $tersimpan > 0
                ? 'Berhasil memperbarui ' . $tersimpan . ' foto guru' . ($skipped > 0 ? " ({$skipped} dilewati)" : '')
                : 'Tidak ada foto yang tersimpan — semua baris dilewati (' . $skipped . ' baris tidak valid).',
        ]);
    }
}
