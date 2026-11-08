<?php

namespace App\Controllers;

use App\Models\MasterModel;
use App\Models\DashboardModel;
use App\Models\DropdownModel;
use App\Models\LogModel;
use App\Models\KelasModel;

/**
 * Datasiswa Controller - Migrated from CI3
 * Comprehensive student management
 */
class Datasiswa extends BaseController
{
    /**
     * Format tanggal yang diterima untuk "Tanggal Diterima".
     *
     * Disimpan penuh Y-m-d supaya hari dan bulan penerimaan tidak hilang —
     * berguna untuk PPDB dan rekap. Input "2026" (tahun saja, dari form lama
     * atau impor Excel) tetap diterima dan dikembalikan sebagai tanggal 1
     * Januari, jadi data yang sudah terlanjur tersimpan tidak berubah
     * bentuknya.
     */
    public const TANGGAL_MASUK_FORMAT = 'Y-m-d';

    /**
     * Normalisasi tanggal diterima dari input form.
     *
     * Nilai yang tidak bisa dibaca sebagai tanggal dikembalikan null, bukan
     * string mentah — supaya tanggal ngawur tidak tersimpan diam-diam dan
     * muncul lagi sebagai teks aneh di halaman Detail.
     */
    public static function normalisasiTanggalTerima(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Tanggal lengkap. Digit satu/tiga bulan atau hari ikut diterima:
        // admin yang mengetik manual sering menulis "2026-1-5", dan akibatnya
        // form menolak siswa yang datanya sebenarnya sah.
        if (preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/', $raw, $m) === 1) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }

            return null;
        }

        // Tahun saja -> 1 Januari.
        if (preg_match('/^\d{4}$/', $raw) === 1) {
            $y = (int) $raw;
            if ($y >= 1900 && $y <= 2100) {
                return sprintf('%04d-01-01', $y);
            }

            return null;
        }

        // Slash sebagai pemisah — sering diketik manual.
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m) === 1) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        }

        return null;
    }

    /**
     * Susun pesan validasi yang menyebut field yang sebenarnya salah.
     *
     * Sebelumnya semua kegagalan dikembalikan dengan teks "Data Sudah ada,
     * Pastikan NIS, NISN dan Username belum digunakan siswa lain" — termasuk
     * saat penyebabnya tanggal diterima atau nama yang salah format. Admin
     * lalu mengejar duplikat yang tidak ada.
     *
     * @param array<string,string> $errors
     */
    private function pesanValidasiSiswa(array $errors): string
    {
        if ($errors === []) {
            return 'Data tidak valid.';
        }

        // is_unique = kandidat kuat "data sudah ada"; sisanya error input.
        // Pesan default CI4 4.6: "The {field} field must contain a unique
        // value." (bukan "already been taken" seperti versi lama).
        foreach (['nis' => 'NIS', 'nisn' => 'NISN', 'username' => 'Username'] as $field => $label) {
            if (isset($errors[$field]) && str_contains(strtolower($errors[$field]), 'unique')) {
                return "{$label} sudah dipakai siswa lain.";
            }
        }

        $pesan = ['Periksa kembali isian berikut:'];
        foreach ($errors as $field => $err) {
            $pesan[] = ucfirst(str_replace('_', ' ', (string) $field)) . ': ' . $err;
        }

        return implode('<br>', $pesan);
    }

    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;
    protected DropdownModel $dropdownModel;
    protected LogModel $logModel;
    protected KelasModel $kelasModel;

    public function __construct()
    {
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
        $this->dropdownModel = new DropdownModel();
        $this->logModel = new LogModel();
        $this->kelasModel = new KelasModel();
    }

    /**
     * Index page
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        // Data siswa memuat PII: siswa tidak boleh dump data siswa lain.
        if ($this->isSiswa()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $setting = $this->dashboardModel->getSetting();
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $stats = $this->masterModel->getSiswaStats(
            $tp ? (int) $tp->id_tp : 0,
            $smt ? (int) $smt->id_smt : 0
        );

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Siswa',
            'subjudul' => 'Data Siswa',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'has_periode' => (bool) ($tp && $smt),
            'is_admin' => $this->isAdmin(),
            'stats' => $stats,
            'kelass' => $tp && $smt ? $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt) : [],
        ];

        return view('master/siswa/data', $data);
    }

    /**
     * Custom pagination list (Ported from CI3)
     */
    public function list()
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Login required']);
        }
        if ($this->isSiswa()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak.']);
        }

        $page = max(1, (int) ($this->request->getPost('page') ?? 1));
        $limit = (int) ($this->request->getPost('limit') ?? 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $search = trim((string) ($this->request->getPost('search') ?? ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        $status = (string) ($this->request->getPost('status') ?? '0');
        if (!in_array($status, ['0', '1', '2', '3', '4', '5'], true)) {
            $status = '0';
        }

        $kelasRaw = trim((string) ($this->request->getPost('kelas') ?? ''));
        // 'tanpa' = filter siswa yang belum ditempatkan di kelas TP/SMT aktif.
        $tanpaKelas = ($kelasRaw === 'tanpa');
        $kelas      = (!$tanpaKelas && ctype_digit($kelasRaw) && (int) $kelasRaw > 0)
            ? (int) $kelasRaw
            : null;
        if ($tanpaKelas) {
            $kelas = null;
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        if (!$tp || !$smt) {
            return $this->response->setJSON([
                'status'  => true,
                'lists'   => [],
                'total'   => 0,
                'pages'   => 0,
                'page'    => 1,
                'perpage' => $limit,
                'status_filter' => $status,
                'kelas'   => $kelas,
                'message' => 'Tahun Pelajaran / Semester aktif belum diset.',
            ]);
        }

        $offset = ($page - 1) * $limit;
        $count_siswa = $this->masterModel->getSiswaTotalPage((int) $tp->id_tp, (int) $smt->id_smt, $status, $kelas, $search, $tanpaKelas);
        $lists = $this->masterModel->getSiswaPage((int) $tp->id_tp, (int) $smt->id_smt, $offset, $limit, $status, $kelas, $search, $tanpaKelas);

        foreach ($lists as $list) {
            $list->foto = $list->foto != null && $list->foto != "" && file_exists(FCPATH . $list->foto)
                ? base_url($list->foto)
                : base_url('assets/img/siswa_sm.png');
        }

        $data = [
            'lists'   => $lists,
            'total'   => $count_siswa,
            'pages'   => (int) ceil($count_siswa / $limit),
            'page'    => $page,
            'perpage' => $limit,
            'status_filter' => $status,
            'kelas'   => $kelas,
            'tanpa_kelas' => $tanpaKelas,
            'stats'   => $this->masterModel->getSiswaStats((int) $tp->id_tp, (int) $smt->id_smt),
        ];

        return $this->response->setJSON($data);
    }

    /**
     * Add/Import Siswa page
     */
    public function add($id_kelas = null)
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');
        if (!$this->isAdmin())
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Siswa',
            'subjudul' => 'Import Siswa',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
        ];

        if ($id_kelas) {
            $data['kelas'] = $this->masterModel->getKelasById($id_kelas);
        }

        return view('master/siswa/add', $data);
    }

    /**
     * Create single siswa
     */
    public function create()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin())
            return $this->response->setStatusCode(403)->setJSON(['insert' => false, 'text' => 'Akses ditolak']);

        // Tanggal diterima sudah dinormalisasi lebih dulu supaya yang
        // divalidasi bentuknya konsisten. Validasi Y-m-d murni akan menolak
        // tahun-saja & "5/10/2026" yang sengaja diterima untuk data
        // lama, jadi keduanya dikembalikan null dulu lalu dibaca dari hasil
        // normalisasi.
        $tanggalMasukRaw = trim((string) ($this->request->getPost('tahun_masuk') ?? ''));
        $tanggalMasuk    = self::normalisasiTanggalTerima($tanggalMasukRaw);

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_siswa' => 'required|min_length[3]|max_length[100]',
            'nis' => 'required|numeric|min_length[4]|max_length[20]|is_unique[master_siswa.nis]',
            'nisn' => 'required|numeric|min_length[9]|max_length[20]|is_unique[master_siswa.nisn]',
            'username' => 'required|min_length[4]|max_length[30]|alpha_dash|is_unique[master_siswa.username]|is_unique[users.username]',
            'password' => 'required|min_length[6]|max_length[72]',
            'jenis_kelamin' => 'permit_empty|in_list[L,P]',
        ]);
        // Tanggal diterima dicek terpisah, bukan lewat aturan string.
        // Aturan `valid_date[Y-m-d]` akan menolak tahun-saja dan
        // "5/10/2026" yang sengaja diterima normalisasiTanggalTerima(),
        // sementara `max_length[10]` tidak menolak tanggal ngawur seperti
        // "2026-13-45". Closure tidak didukung CI4 Rules, jadi pengecekan
        // dilakukan eksplisit di sini.
        $errorTanggal = ($tanggalMasukRaw !== '' && $tanggalMasuk === null)
            ? 'Tanggal diterima tidak valid. Gunakan format YYYY-MM-DD.'
            : null;

        if (!$this->validate($validation->getRules()) || $errorTanggal !== null) {
            $errors = $validation->getErrors();
            if ($errorTanggal !== null) {
                $errors['tahun_masuk'] = $errorTanggal;
            }

            // Rincian per-field ikut dikirim: pesan tunggal "Data Sudah
            // ada" menyesatkan kalau penyebabnya sebenarnya kolom lain
            // (mis. tanggal diterima yang formatnya salah).
            return $this->response->setJSON([
                'insert' => false,
                'error' => $errors,
                'text' => $this->pesanValidasiSiswa($errors)
            ]);
        }

        $nama_siswa = trim((string) $this->request->getPost('nama_siswa'));
        $namaArr = preg_split('/\s+/', $nama_siswa, -1, PREG_SPLIT_NO_EMPTY);
        $first_name = $namaArr[0] ?? $nama_siswa;
        $last_name = count($namaArr) > 1 ? implode(' ', array_slice($namaArr, 1)) : '';

        $nis = trim((string) $this->request->getPost('nis'));
        $jk = strtoupper(trim((string) $this->request->getPost('jenis_kelamin')));
        $insert = [
            "nama" => $nama_siswa,
            "nis" => $nis,
            "nisn" => trim((string) $this->request->getPost('nisn')),
            "jenis_kelamin" => in_array($jk, ['L', 'P'], true) ? $jk : null,
            "agama" => mb_substr(trim((string) $this->request->getPost('agama')), 0, 30),
            "kelas_awal" => mb_substr(trim((string) $this->request->getPost('kelas_awal')), 0, 50),
            "tahun_masuk" => $tanggalMasuk,
            "username" => trim((string) $this->request->getPost('username')),
            "password" => (string) $this->request->getPost('password'),
            "foto" => null,
        ];

        $insert['uid'] = bin2hex(random_bytes(16));

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $this->masterModel->create('master_siswa', $insert);
            $id = $this->masterModel->db->insertID();
            if (!$id) {
                throw new \RuntimeException('Gagal menyimpan master siswa');
            }

            $induk = [
                "id_siswa" => $id,
                "uid" => $insert['uid'],
                "status" => 1
            ];

            $this->masterModel->create('buku_induk', $induk);

            // Register user
            $userData = [
                'username' => $insert['username'],
                'password' => password_hash($insert['password'], PASSWORD_BCRYPT),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'active' => 1
            ];

            $db->table('users')->insert($userData);
            $userId = $db->insertID();
            $db->table('users_groups')->insert(['user_id' => $userId, 'group_id' => 3]);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datasiswa::create] ' . $e->getMessage());
            return $this->response->setJSON(['insert' => false, 'text' => 'Gagal menyimpan siswa']);
        }

        $this->logModel->saveLog(1, 'Menambahkan Siswa: ' . $nama_siswa);

        return $this->response->setJSON([
            'insert' => true,
            'text' => 'Siswa berhasil ditambahkan'
        ]);
    }

    /**
     * Edit Siswa page
     */
    public function edit($id)
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');
        if (!$this->isAdmin())
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');

        $siswa = $this->masterModel->getSiswaById($id);
        if (!$siswa)
            return redirect()->to('datasiswa');

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $setting = $this->dashboardModel->getSetting();

        // Kelas aktif siswa (TP/SMT aktif) + daftar kelas untuk dropdown edit
        $kelasSiswaId = null;
        $kelasSiswaNama = null;
        $kelasList = [];
        if ($tp && $smt) {
            $kelasList = $this->masterModel->getAllKelas((int) $tp->id_tp, (int) $smt->id_smt);
            $db = \Config\Database::connect();
            try {
                $row = $db->table('kelas_siswa ks')
                    ->select('ks.id_kelas, c.nama_kelas')
                    ->join('master_kelas c', 'c.id_kelas = ks.id_kelas', 'left')
                    ->where('ks.id_siswa', (int) $id)
                    ->where('ks.id_tp', (int) $tp->id_tp)
                    ->where('ks.id_smt', (int) $smt->id_smt)
                    ->get()->getRow();
                if ($row) {
                    $kelasSiswaId = (int) $row->id_kelas ?: null;
                    $kelasSiswaNama = $row->nama_kelas ?? null;
                }
            } catch (\Throwable $e) {
                log_message('error', '[Datasiswa::edit] kelas_siswa: ' . $e->getMessage());
            }
        }

        $input_data = [
            (object) ['label' => 'Nama Lengkap', 'name' => 'nama', 'value' => $siswa->nama, 'icon' => 'bi bi-person', 'class' => '', 'type' => 'text'],
            (object) ['label' => 'NIS', 'name' => 'nis', 'value' => $siswa->nis, 'icon' => 'bi bi-person-badge', 'class' => '', 'type' => 'number'],
            (object) ['name' => 'nisn', 'label' => 'NISN', 'value' => $siswa->nisn, 'icon' => 'bi bi-person-badge', 'class' => '', 'type' => 'text'],
            (object) ['label' => 'Jenis Kelamin', 'name' => 'jenis_kelamin', 'value' => $siswa->jenis_kelamin, 'icon' => 'bi bi-gender-ambiguous', 'class' => '', 'type' => 'text'],
            (object) ['name' => 'kelas_awal', 'label' => 'Diterima di kelas', 'value' => $siswa->kelas_awal, 'icon' => 'bi bi-mortarboard', 'class' => '', 'type' => 'text'],
            (object) ['name' => 'tahun_masuk', 'label' => 'Tgl diterima', 'value' => $siswa->tahun_masuk, 'icon' => 'tahun bi bi-calendar-date', 'class' => 'tahun', 'type' => 'text'],
            (object) ['name' => 'sekolah_asal', 'label' => 'Sekolah Asal', 'value' => $siswa->sekolah_asal, 'icon' => 'bi bi-mortarboard', 'class' => '', 'type' => 'text'],
            (object) ['name' => 'status', 'label' => 'Status', 'value' => $siswa->status, 'icon' => 'bi bi-person-check', 'class' => 'status', 'type' => 'text']
        ];

        $input_bio = [
            (object) ['name' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'value' => $siswa->tempat_lahir, 'icon' => 'bi bi-geo-alt', 'class' => '', 'type' => 'text'],
            (object) ['name' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'value' => $siswa->tanggal_lahir, 'icon' => 'bi bi-calendar-date', 'class' => 'tahun', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'agama', 'label' => 'Agama', 'value' => $siswa->agama, 'icon' => 'bi bi-star', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'alamat', 'label' => 'Alamat', 'value' => $siswa->alamat, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'rt', 'label' => 'Rt', 'value' => $siswa->rt, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'rw', 'label' => 'Rw', 'value' => $siswa->rw, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'kelurahan', 'label' => 'Kelurahan/Desa', 'value' => $siswa->kelurahan, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'kecamatan', 'label' => 'Kecamatan', 'value' => $siswa->kecamatan, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'kabupaten', 'label' => 'Kabupaten/Kota', 'value' => $siswa->kabupaten, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'kode_pos', 'label' => 'Kode Pos', 'value' => $siswa->kode_pos, 'icon' => 'bi bi-envelope', 'type' => 'text'],
            (object) ['class' => '', 'name' => 'hp', 'label' => 'Hp', 'value' => $siswa->hp, 'icon' => 'bi bi-telephone', 'type' => 'text']
        ];

        $input_ortu = [
            (object) ['name' => 'status_keluarga', 'label' => 'Status Keluarga', 'value' => $siswa->status_keluarga, 'icon' => 'bi bi-people', 'type' => 'text'],
            (object) ['name' => 'anak_ke', 'label' => 'Anak ke', 'value' => $siswa->anak_ke, 'icon' => 'bi bi-sort-numeric-down', 'type' => 'number'],
            (object) ['name' => 'nama_ayah', 'label' => 'Nama Ayah', 'value' => $siswa->nama_ayah, 'icon' => 'bi bi-person', 'type' => 'text'],
            (object) ['name' => 'pekerjaan_ayah', 'label' => 'Pekerjaan Ayah', 'value' => $siswa->pekerjaan_ayah, 'icon' => 'bi bi-briefcase', 'type' => 'text'],
            (object) ['name' => 'alamat_ayah', 'label' => 'Alamat Ayah', 'value' => $siswa->alamat_ayah, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['name' => 'nohp_ayah', 'label' => 'No. HP Ayah', 'value' => $siswa->nohp_ayah, 'icon' => 'bi bi-telephone', 'type' => 'number'],
            (object) ['name' => 'nama_ibu', 'label' => 'Nama Ibu', 'value' => $siswa->nama_ibu, 'icon' => 'bi bi-person', 'type' => 'text'],
            (object) ['name' => 'pekerjaan_ibu', 'label' => 'Pekerjaan Ibu', 'value' => $siswa->pekerjaan_ibu, 'icon' => 'bi bi-briefcase', 'type' => 'text'],
            (object) ['name' => 'alamat_ibu', 'label' => 'Alamat Ibu', 'value' => $siswa->alamat_ibu, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['name' => 'nohp_ibu', 'label' => 'No. HP Ibu', 'value' => $siswa->nohp_ibu, 'icon' => 'bi bi-telephone', 'type' => 'number'],
        ];

        $input_wali = [
            (object) ['name' => 'nama_wali', 'label' => 'Nama Wali', 'value' => $siswa->nama_wali, 'icon' => 'bi bi-person', 'type' => 'text'],
            (object) ['name' => 'pekerjaan_wali', 'label' => 'Pekerjaan Wali', 'value' => $siswa->pekerjaan_wali, 'icon' => 'bi bi-briefcase', 'type' => 'text'],
            (object) ['name' => 'alamat_wali', 'label' => 'Alamat Wali', 'value' => $siswa->alamat_wali, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['name' => 'nohp_wali', 'label' => 'No. HP Wali', 'value' => $siswa->nohp_wali, 'icon' => 'bi bi-telephone', 'type' => 'number'],
        ];

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Siswa',
            'subjudul' => 'Edit Data Siswa',
            'siswa' => $siswa,
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'input_data' => $input_data,
            'input_bio' => $input_bio,
            'input_ortu' => $input_ortu,
            'input_wali' => $input_wali,
            'kelas_list' => $kelasList,
            'kelas_siswa_id' => $kelasSiswaId,
            'kelas_siswa_nama' => $kelasSiswaNama,
        ];

        return view('master/siswa/edit', $data);
    }

    /**
     * Update Siswa data
     */
    /**
     * Kolom master_siswa yang boleh diubah massal + panjang maks (anti mass-assignment).
     */
    private const UPDATEABLE_SISWA_FIELDS = [
        'nama' => 100, 'nis' => 20, 'nisn' => 20, 'jenis_kelamin' => 2,
        'tempat_lahir' => 50, 'tanggal_lahir' => 10, 'agama' => 30,
        'status_keluarga' => 30, 'anak_ke' => 5, 'alamat' => 255,
        'rt' => 5, 'rw' => 5, 'kelurahan' => 50, 'kecamatan' => 50,
        'kabupaten' => 50, 'provinsi' => 50, 'kode_pos' => 10, 'hp' => 20,
        'nama_ayah' => 100, 'nohp_ayah' => 20, 'pendidikan_ayah' => 50,
        'pekerjaan_ayah' => 50, 'alamat_ayah' => 255,
        'nama_ibu' => 100, 'nohp_ibu' => 20, 'pendidikan_ibu' => 50,
        'pekerjaan_ibu' => 50, 'alamat_ibu' => 255,
        'nama_wali' => 100, 'nohp_wali' => 20, 'pendidikan_wali' => 50,
        'pekerjaan_wali' => 50, 'alamat_wali' => 255,
        'tahun_masuk' => 10, 'kelas_awal' => 50, 'sekolah_asal' => 100,
    ];

    public function updateData()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin())
            return $this->response->setStatusCode(403)->setJSON(['insert' => false, 'text' => 'Akses ditolak']);

        $id_siswa = (int) $this->request->getPost('id_siswa');
        $nis = trim((string) $this->request->getPost('nis'));
        $nisn = trim((string) $this->request->getPost('nisn'));

        if ($id_siswa <= 0) {
            return $this->response->setJSON(['insert' => false, 'text' => 'ID siswa tidak valid']);
        }

        $siswa = $this->masterModel->getSiswaById($id_siswa);
        if (!$siswa) {
            return $this->response->setJSON(['insert' => false, 'text' => 'Siswa tidak ditemukan']);
        }
        $u_nis = $siswa->nis == $nis ? "" : "|is_unique[master_siswa.nis]";
        $u_nisn = $siswa->nisn == $nisn ? "" : "|is_unique[master_siswa.nisn]";

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama' => 'required|min_length[3]|max_length[100]',
            'nis' => 'required|numeric|min_length[4]|max_length[20]' . $u_nis,
            'nisn' => 'required|numeric|min_length[9]|max_length[20]' . $u_nisn,
            'tanggal_lahir' => 'permit_empty|valid_date[Y-m-d]',
            'tahun_masuk' => 'permit_empty|max_length[10]',
        ]);

        if (!$this->validate($validation->getRules())) {
            return $this->response->setJSON([
                'insert' => false,
                'text' => implode('<br>', $validation->getErrors())
            ]);
        }

        // Whitelist + cap panjang (blokir timpa username/password/foto via POST)
        $raw = $this->request->getPost();
        $input = [];
        foreach (self::UPDATEABLE_SISWA_FIELDS as $field => $maxLen) {
            if (array_key_exists($field, $raw)) {
                $val = trim((string) $raw[$field]);
                if (mb_strlen($val) > $maxLen) {
                    return $this->response->setJSON(['insert' => false, 'text' => "Field {$field} terlalu panjang (maks {$maxLen})"]);
                }
                $input[$field] = $val !== '' ? $val : null;
            }
        }
        $jk = strtoupper((string) ($input['jenis_kelamin'] ?? ''));
        if (isset($input['jenis_kelamin']) && !in_array($jk, ['L', 'P', ''], true)) {
            return $this->response->setJSON(['insert' => false, 'text' => 'Jenis kelamin tidak valid']);
        }

        // Tanggal diterima dinormalisasi di sini juga, bukan hanya di create().
        // Kalau tidak, edit bisa menyimpan "2026-1-5" atau "05/01/2026" apa
        // adanya, dan kolomnya jadi tidak seragam.
        if (array_key_exists('tahun_masuk', $input)) {
            $rawTanggal = (string) ($input['tahun_masuk'] ?? '');
            $tanggal    = self::normalisasiTanggalTerima($rawTanggal);

            // Kalau isiannya tidak kosong tapi tidak terbaca sebagai tanggal,
            // jangan diam-diam jadi NULL: admin mengira tanggalnya tersimpan
            // padahal kolomnya justru dikosongkan.
            if ($rawTanggal !== '' && $tanggal === null) {
                return $this->response->setJSON([
                    'insert' => false,
                    'text'   => 'Tanggal diterima tidak valid',
                ]);
            }

            $input['tahun_masuk'] = $tanggal;
        }

        $this->masterModel->updateData('master_siswa', $input, 'id_siswa', $id_siswa);

        // Update status di buku_induk (1=AKTIF, 2=LULUS, 3=PINDAH, 4=KELUAR — sesuai opsi form)
        $status = $this->request->getPost('status');
        if ($status !== null && $status !== '' && in_array((string) $status, ['1', '2', '3', '4'], true)) {
            $db = \Config\Database::connect();
            $exists = $db->table('buku_induk')->where('id_siswa', $id_siswa)->countAllResults() > 0;
            if ($exists) {
                $this->masterModel->updateData('buku_induk', ['status' => (string) $status], 'id_siswa', $id_siswa);
            } else {
                $this->masterModel->create('buku_induk', [
                    'id_siswa' => $id_siswa,
                    'uid' => bin2hex(random_bytes(16)),
                    'status' => (string) $status,
                ]);
            }
        }

        // Update kelas siswa pada TP/SMT aktif (kunci: id_tp + id_smt + id_siswa)
        // AND Update presensi_siswa.id_kelas to keep rekap accurate after class change
        $rawKelas = $this->request->getPost('id_kelas');
        if ($rawKelas !== null) {
            $tpAktif = $this->dashboardModel->getTahunActive();
            $smtAktif = $this->dashboardModel->getSemesterActive();
            if (!$tpAktif || !$smtAktif) {
                return $this->response->setJSON(['insert' => false, 'text' => 'TP/SMT aktif belum diset, kelas tidak dapat diubah']);
            }
            $idTp = (int) $tpAktif->id_tp;
            $idSmt = (int) $smtAktif->id_smt;
            $db = \Config\Database::connect();

            $kelasStr = trim((string) $rawKelas);
            if ($kelasStr === '' || $kelasStr === '0') {
                // Keluarkan siswa dari kelas pada TP/SMT aktif
                // When leaving a class, just delete the kelas_siswa record.
                // The presensi_siswa records will be excluded from rekap by filterSiswaAktif
                // (status '3' PINDAH / '4' KELUAR are excluded, and students without buku_induk are considered AKTIF).
                $db->table('kelas_siswa')
                    ->where('id_siswa', $id_siswa)
                    ->where('id_tp', $idTp)
                    ->where('id_smt', $idSmt)
                    ->delete();
            } else {
                if (!ctype_digit($kelasStr)) {
                    return $this->response->setJSON(['insert' => false, 'text' => 'ID kelas tidak valid']);
                }
                $idKelas = (int) $kelasStr;
                $kelasOk = $db->table('master_kelas')
                    ->where('id_kelas', $idKelas)
                    ->where('id_tp', $idTp)
                    ->where('id_smt', $idSmt)
                    ->countAllResults() > 0;
                if (!$kelasOk) {
                    return $this->response->setJSON(['insert' => false, 'text' => 'Kelas tidak valid untuk TP/Semester aktif']);
                }
                // Upsert eksplisit per kunci logis (tp,smt,siswa). replace()
                // mencocokkan PRIMARY KEY (id_kelas_siswa) sehingga baris
                // berkunci lama justru digandakan, bukan diganti.
                $ksAda = $db->table('kelas_siswa')
                    ->where('id_tp', $idTp)
                    ->where('id_smt', $idSmt)
                    ->where('id_siswa', $id_siswa)
                    ->get()->getRow();
                if ($ksAda) {
                    $db->table('kelas_siswa')->where('id_kelas_siswa', $ksAda->id_kelas_siswa)
                        ->update(['id_kelas' => $idKelas]);
                } else {
                    $db->table('kelas_siswa')->insert([
                        'id_kelas_siswa' => $this->masterModel->generateKelasSiswaId(),
                        'id_tp' => $idTp,
                        'id_smt' => $idSmt,
                        'id_kelas' => $idKelas,
                        'id_siswa' => $id_siswa,
                    ]);
                }
                // Update presensi_siswa.id_kelas to match new class
                $ps = $db->table('presensi_siswa')->where('id_siswa', $id_siswa)->get()->getRow();
                if ($ps) {
                    $db->table('presensi_siswa')->where('id', $ps->id)->update(['id_kelas' => $idKelas]);
                }
            }
        }

        return $this->response->setJSON([
            'insert' => true,
            'text' => 'Siswa berhasil diperbaharui'
        ]);
    }

    /**
     * Bulk Delete/Actions
     */
    public function delete()
    {
        if (!$this->isAdmin())
            return $this->respond(false, 'Authorized Access');

        $chk = $this->request->getPost('checked');
        $aksi = $this->request->getPost('aksi');

        if (!$chk)
            return $this->respond(false, 'No students selected');

        $status = true;
        $messages = [];

        if ($aksi == 'pindah' || $aksi == 'keluar') {
            $valStatus = $aksi == 'pindah' ? '3' : '4';
            foreach ($chk as $id) {
                // Get current class name for record
                $siswa = $this->masterModel->getSiswaById($id);
                // Here we might need a more complex query to get current class, 
                // but let's simplify for now.

                $this->masterModel->updateData('buku_induk', [
                    'tgl_pindah' => date('Y-m-d'),
                    'status' => $valStatus
                ], 'id_siswa', $id);

                // Update presensi_siswa.id_kelas when student transfers to keep rekap accurate
                $db = \Config\Database::connect();
                $ps = $db->table('presensi_siswa')->where('id_siswa', $id)->get()->getRow();
                if ($ps) {
                    $tpAktif = $this->dashboardModel->getTahunActive();
                    $smtAktif = $this->dashboardModel->getSemesterActive();
                    if ($tpAktif && $smtAktif) {
                        // Ambil kelas baru dari kelas_siswa (sudah diupdate)
                        $ks = $db->table('kelas_siswa')
                            ->where('id_siswa', $id)
                            ->where('id_tp', $tpAktif->id_tp)
                            ->where('id_smt', $smtAktif->id_smt)
                            ->get()->getRow();
                        if ($ks && $ks->id_kelas) {
                            $db->table('presensi_siswa')->where('id', $ps->id)->update(['id_kelas' => $ks->id_kelas]);
                        }
                    }
                }
            }
        } elseif ($aksi == 'hapus') {
            $idSiswa = array_values(array_filter(array_map('intval', (array) $chk), static fn ($i) => $i > 0));
            if ($idSiswa === []) {
                return $this->respond(false, 'Siswa yang dipilih tidak valid — tidak ada yang dihapus.');
            }

            $db = \Config\Database::connect();

            // Presensi siswa menempel ke identitas lewat id_siswa. master_siswa +
            // buku_induk + kelas_siswa yang dihapus keras meninggalkan baris
            // absensi yatim: tetap utuh di database, tapi tidak lagi muncul di
            // rekap kelas/periode mana pun — persis kehilangan riwayat yang tidak
            // bisa dipulihkan. Siswa yang berhenti sekolah punya jalur sendiri di
            // atas (PINDAH/KELUAR) yang menyimpan seluruh riwayat absensinya.
            $q = $db->table('presensi_siswa as ps')
                ->select('ps.id_siswa, ms.nama, ms.nis, COUNT(ps.id) AS jml')
                ->join('master_siswa as ms', 'ms.id_siswa = ps.id_siswa')
                ->whereIn('ps.id_siswa', $idSiswa)
                // nama & nis ikut di GROUP BY: tanpa itu query gagal di server
                // dengan ONLY_FULL_GROUP_BY aktif (default MySQL 5.7/8.0).
                ->groupBy('ps.id_siswa, ms.nama, ms.nis')
                ->get();
            if ($q === false) {
                log_message('error', '[Datasiswa::delete] riwayat presensi tidak dapat diperiksa: ' . json_encode($db->error()));
                return $this->respond(false, 'Riwayat presensi siswa tidak dapat diperiksa, jadi penghapusan ditolak. Coba lagi nanti.');
            }

            $berpresensi = $q->getResultArray();
            if ($berpresensi !== []) {
                $contoh = array_slice(array_map(
                    static fn ($b) => trim(($b['nama'] ?? '') . ' (' . ($b['nis'] ?? '-') . '): ' . $b['jml'] . ' absen'),
                    $berpresensi
                ), 0, 3);
                if (count($berpresensi) > 3) {
                    $contoh[] = '+' . (count($berpresensi) - 3) . ' siswa lain';
                }
                log_message('warning', '[Datasiswa::delete] hapus ' . count($berpresensi) . ' siswa dengan riwayat presensi ditolak: ' . implode(', ', $contoh));

                return $this->respond(false, count($berpresensi) . ' dari ' . count($idSiswa)
                    . ' siswa yang dipilih sudah punya riwayat presensi (' . implode('; ', $contoh)
                    . '). Menghapusnya membuat riwayat absensi tahun-tahun sebelumnya tidak lagi muncul di rekap. '
                    . 'Gunakan aksi Pindah/Keluar untuk menyimpan riwayatnya, atau hapus riwayat presensinya lebih dulu bila memang data salah input.');
            }

            // Tiga tabel harus hilang bersamaan: sebagian terhapus = baris kelas
            // yatim untuk siswa yang identitasnya sudah tidak ada.
            $db->transBegin();
            try {
                $this->masterModel->hapus('master_siswa', $idSiswa, 'id_siswa');
                $this->masterModel->hapus('buku_induk', $idSiswa, 'id_siswa');
                $this->masterModel->hapus('kelas_siswa', $idSiswa, 'id_siswa');

                if (!$db->transStatus()) {
                    $db->transRollback();
                    log_message('error', '[Datasiswa::delete] penghapusan siswa ditolak DB: ' . json_encode($db->error()));
                    return $this->respond(false, 'Gagal menghapus siswa. Data belum berubah.');
                }
                $db->transCommit();
            } catch (\Throwable $e) {
                if ($db->transStatus() === false) {
                    $db->transRollback();
                }
                log_message('error', '[Datasiswa::delete] ' . $e->getMessage());
                return $this->respond(false, 'Gagal menghapus siswa. Data belum berubah.');
            }

            $this->logModel->saveLog(5, 'menghapus ' . count($idSiswa) . ' siswa tanpa riwayat presensi');
        }

        return $this->response->setJSON([
            'status' => $status,
            'message' => 'Siswa berhasil ' . ($aksi == 'hapus' ? 'dihapus' : 'diupdate')
        ]);
    }

    /**
     * Import logic (Ported/Simplified)
     */
    public function do_import()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        // Handle JSON input to avoid max_input_vars limit
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = json_decode($this->request->getBody(), true);
            $id_kelas = (int) ($jsonInput['id_kelas'] ?? 0);
            $input = $jsonInput['siswa'] ?? [];
        } else {
            $id_kelas = (int) ($this->request->getPost('id_kelas') ?? 0);
            $input = $this->request->getPost('siswa'); // Expecting array of student data
        }

        if (empty($input) || !is_array($input))
            return $this->respond(false, 'No data to import');
        if (count($input) > 2000) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 2.000 baris per import']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $db = \Config\Database::connect();

        // Validasi kelas tujuan bila dikirim
        if ($id_kelas > 0) {
            $kelasOk = $tp && $smt && $db->table('master_kelas')
                ->where('id_kelas', $id_kelas)->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)
                ->countAllResults() > 0;
            if (!$kelasOk) {
                return $this->response->setJSON(['status' => false, 'message' => 'Kelas tujuan tidak valid']);
            }
        }

        // Ambil semua kelas untuk mapping kode_kelas ke id_kelas
        $kelasMapping = [];
        if ($tp && $smt) {
            $allKelas = $this->kelasModel->getNamaKelasByNama($tp->id_tp, $smt->id_smt);
            
            // Ambil juga kode_kelas untuk mapping lebih akurat
            $kelasWithKode = $db->table('master_kelas')
                ->select('id_kelas, kode_kelas, nama_kelas')
                ->where('id_tp', $tp->id_tp)
                ->where('id_smt', $smt->id_smt)
                ->get()->getResult();
            
            foreach ($kelasWithKode as $k) {
                // Mapping berdasarkan kode_kelas dan juga nama_kelas untuk fleksibilitas
                if (!empty($k->kode_kelas)) {
                    $kelasMapping[strtoupper(trim($k->kode_kelas))] = $k->id_kelas;
                }
                // Juga mapping nama_kelas sebagai fallback
                $kelasMapping[strtoupper(trim($k->nama_kelas))] = $k->id_kelas;
            }
        }

        // Ambil NIS/NISN/username yang sudah ada (1 query, bukan per baris)
        $existNis = $existNisn = $existUser = [];
        try {
            foreach ($db->table('master_siswa')->select('nis, nisn, username')->get()->getResult() as $r) {
                if ($r->nis !== null && $r->nis !== '') $existNis[trim((string) $r->nis)] = true;
                if ($r->nisn !== null && $r->nisn !== '') $existNisn[trim((string) $r->nisn)] = true;
                if ($r->username !== null && $r->username !== '') $existUser[trim((string) $r->username)] = true;
            }
            foreach ($db->table('users')->select('username')->get()->getResult() as $r) {
                if ($r->username !== null && $r->username !== '') $existUser[trim((string) $r->username)] = true;
            }
        } catch (\Throwable $e) {
            log_message('error', '[do_import] preload: ' . $e->getMessage());
        }

        $clean = [];
        $skipped = 0;
        $errorRows = [];
        $batchNis = $batchNisn = $batchUser = [];
        $rowNo = 0;

        foreach ($input as $value) {
            $rowNo++;
            if (!is_array($value)) {
                $skipped++;
                continue;
            }
            $nama = mb_substr(trim((string) ($value['nama'] ?? '')), 0, 100);
            $nis = trim((string) ($value['nis'] ?? ''));
            $nisn = trim((string) ($value['nisn'] ?? ''));
            $username = mb_substr(trim((string) ($value['username'] ?? $nis)), 0, 30);
            $password = (string) ($value['password'] ?? $nis);
            $jk = strtoupper(trim((string) ($value['jenis_kelamin'] ?? '')));
            $label = $nama !== '' ? "Baris {$rowNo} ({$nama})" : "Baris {$rowNo}";

            // Lewati baris kosong total tanpa pesan
            if ($nama === '' && $nis === '' && $nisn === '' && $username === '' && trim($password) === '') {
                continue;
            }
            if ($nama === '' || mb_strlen($nama) < 3) {
                $skipped++;
                if (count($errorRows) < 50) $errorRows[] = "{$label}: nama minimal 3 karakter";
                continue;
            }
            if ($nis === '' || !ctype_digit($nis)) {
                $skipped++;
                if (count($errorRows) < 50) $errorRows[] = "{$label}: NIS wajib angka";
                continue;
            }
            if (isset($existNis[$nis]) || isset($batchNis[$nis])) {
                $skipped++;
                if (count($errorRows) < 50) $errorRows[] = "{$label}: NIS sudah terdaftar";
                continue;
            }
            if ($nisn !== '' && (isset($existNisn[$nisn]) || isset($batchNisn[$nisn]))) {
                $skipped++;
                if (count($errorRows) < 50) $errorRows[] = "{$label}: NISN sudah terdaftar";
                continue;
            }
            if ($username === '' || isset($existUser[$username]) || isset($batchUser[$username])) {
                $skipped++;
                if (count($errorRows) < 50) $errorRows[] = "{$label}: username kosong / sudah dipakai";
                continue;
            }

            $batchNis[$nis] = true;
            if ($nisn !== '') $batchNisn[$nisn] = true;
            $batchUser[$username] = true;

            $kelas_awal = mb_substr(trim((string) ($value['kelas_awal'] ?? '')), 0, 50);
            
            $clean[] = [
                'uid' => bin2hex(random_bytes(16)),
                'nama' => $nama,
                'nis' => $nis,
                'nisn' => $nisn !== '' ? $nisn : null,
                'jenis_kelamin' => in_array($jk, ['L', 'P'], true) ? $jk : null,
                'username' => $username,
                'password' => mb_substr($password !== '' ? $password : $nis, 0, 72),
                'kelas_awal' => $kelas_awal,
                'tahun_masuk' => self::normalisasiTanggalTerima((string) ($value['tahun_masuk'] ?? '')),
                'foto' => null,
                'target_kelas' => $kelas_awal, // Simpan untuk mapping kelas
            ];
        }

        if (empty($clean)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada baris valid (semua duplikat / tidak lengkap)',
                'imported' => 0,
                'skipped' => $skipped,
                'errors' => $errorRows,
            ]);
        }

        $db->transBegin();
        try {
            // Hapus field target_kelas dari data yang akan diinsert ke master_siswa
            $cleanForInsert = [];
            foreach ($clean as $c) {
                $insertData = $c;
                unset($insertData['target_kelas']);
                $cleanForInsert[] = $insertData;
            }
            
            $this->masterModel->insertBatchData('master_siswa', $cleanForInsert);

            $usernames = array_column($clean, 'username');
            $insertedSiswa = $this->masterModel->getSiswaByUsernames($usernames);

            $induk = [];
            $kelasSiswa = [];
            $usersBatch = [];
            $mapByUsername = [];
            foreach ($clean as $c) $mapByUsername[$c['username']] = $c; // Tetap gunakan $clean untuk mapping karena target_kelas ada di sini

            $pkDasarKelasSiswa = $this->masterModel->generateKelasSiswaId();

            foreach ($insertedSiswa as $s) {
                $induk[] = [
                    'id_siswa' => $s->id_siswa,
                    'uid' => bin2hex(random_bytes(16)),
                    'status' => 1
                ];

                // Logika mapping kelas: prioritaskan kelas_awal dari Excel, fallback ke id_kelas yang dipilih
                $targetKelasId = null;
                $src = $mapByUsername[$s->username] ?? null;
                
                if ($src && !empty($src['target_kelas']) && $tp && $smt) {
                    // Cek apakah kelas_awal cocok dengan kelas yang ada di database
                    $kelasKey = strtoupper(trim($src['target_kelas']));
                    if (isset($kelasMapping[$kelasKey])) {
                        $targetKelasId = $kelasMapping[$kelasKey];
                    }
                }
                
                // Jika tidak ada mapping dari kelas_awal, gunakan id_kelas yang dipilih secara manual
                if ($targetKelasId === null && $id_kelas > 0 && $tp && $smt) {
                    $targetKelasId = $id_kelas;
                }

                // Masukkan ke kelas_siswa jika ada target kelas
                if ($targetKelasId !== null && $tp && $smt) {
                    $kelasSiswa[] = [
                        // kelas_siswa tidak auto_increment dan concat(tp,smt,siswa)
                        // ambigu antar nilai multi-digit, jadi pakai urut di atas
                        // nilai maksimum yang ada.
                        'id_kelas_siswa' => $pkDasarKelasSiswa + count($kelasSiswa),
                        'id_tp' => $tp->id_tp,
                        'id_smt' => $smt->id_smt,
                        'id_kelas' => $targetKelasId,
                        'id_siswa' => $s->id_siswa
                    ];
                }

                if ($src) {
                    $parts = preg_split('/\s+/', $src['nama'], -1, PREG_SPLIT_NO_EMPTY);
                    $usersBatch[] = [
                        'username' => $src['username'],
                        'password' => password_hash($src['password'], PASSWORD_BCRYPT),
                        'first_name' => $parts[0] ?? $src['nama'],
                        'last_name' => count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '',
                        'active' => 1
                    ];
                }
            }

            if (!empty($induk))
                $this->masterModel->insertBatchData('buku_induk', $induk);
            if (!empty($kelasSiswa))
                $this->masterModel->insertBatchData('kelas_siswa', $kelasSiswa);
            if (!empty($usersBatch)) {
                $db->table('users')->insertBatch($usersBatch);
                // Ambil ulang ID berdasarkan username (aman dari asumsi auto-increment berurutan)
                $createdUsers = $db->table('users')
                    ->select('id')
                    ->whereIn('username', array_column($usersBatch, 'username'))
                    ->get()->getResult();
                $ugBatch = [];
                foreach ($createdUsers as $cu) $ugBatch[] = ['user_id' => $cu->id, 'group_id' => 3];
                if ($ugBatch) $db->table('users_groups')->insertBatch($ugBatch);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[do_import] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal import (duplikat / kesalahan database)']);
        }

        $this->logModel->saveLog(1, 'Import siswa: ' . count($clean) . ' berhasil, ' . $skipped . ' dilewati');

        // Hitung jumlah siswa yang berhasil dimasukkan ke kelas
        $assignedToKelas = count($kelasSiswa);
        $kelasInfo = $assignedToKelas > 0 ? " ({$assignedToKelas} siswa dimasukkan ke kelas)" : "";

        return $this->response->setJSON([
            'status' => true,
            'total' => count($clean),
            'imported' => count($clean),
            'assigned_to_kelas' => $assignedToKelas,
            'skipped' => $skipped,
            'errors' => $errorRows,
            'message' => 'Import berhasil: ' . count($clean) . ' siswa' . $kelasInfo . ($skipped > 0 ? " ({$skipped} baris dilewati: duplikat/tak lengkap)" : '')
        ]);
    }

    /**
     * Bulk update page
     */
    public function update()
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');
        if (!$this->isAdmin())
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('datasiswa')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Update Data Siswa',
            'subjudul' => 'Update Data Siswa',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'tipe' => 'update',
            'kelas' => $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt),
        ];

        return view('master/siswa/update', $data);
    }

    /**
     * Photo Upload
     */
    public function uploadFile($id_siswa)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $file = $this->request->getFile('foto');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $siswa = $this->masterModel->getSiswaById($id_siswa);
            if (!$siswa) {
                return $this->response->setJSON(['status' => false, 'message' => 'Siswa tidak ditemukan']);
            }
            // Hanya gambar JPG/PNG ≤ 2MB (cegah upload skrip .php = RCE)
            // Cek MIME asli + is_image, bukan hanya ekstensi.
            if ($file->getSize() > 2 * 1024 * 1024) {
                return $this->response->setJSON(['status' => false, 'message' => 'Ukuran foto maksimal 2MB']);
            }
            if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png'], true)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format foto harus JPG/PNG']);
            }
            if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format foto harus JPG/PNG']);
            }
            $safeNis = preg_replace('/[^0-9A-Za-z\-_]/', '', (string) ($siswa->nis ?? 'siswa' . $id_siswa));
            $newName = $safeNis . '.jpg';
            $file->move(FCPATH . 'uploads/foto_siswa/', $newName, true);

            $this->masterModel->updateData('master_siswa', ['foto' => 'uploads/foto_siswa/' . $newName], 'id_siswa', $id_siswa);

            return $this->response->setJSON([
                'status' => true,
                'src' => base_url('uploads/foto_siswa/' . $newName)
            ]);
        }

        return $this->respond(false, 'Gagal upload foto');
    }

    public function downloadData($id_kelas)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'TP/SMT aktif belum diset']);
        }

        $db = \Config\Database::connect();
        // Kolom eksplisit TANPA password/uid (dulu s.* membocorkan password plaintext)
        $siswas = $db->table('master_siswa s')
            ->select('s.id_siswa, s.nis, s.nisn, s.nama, s.jenis_kelamin, s.tempat_lahir, s.tanggal_lahir, s.agama, s.alamat, s.hp, s.foto, s.username, ks.id_kelas, c.nama_kelas')
            ->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa')
            ->join('master_kelas c', 'ks.id_kelas = c.id_kelas')
            ->where('ks.id_tp', $tp->id_tp)
            ->where('ks.id_smt', $smt->id_smt)
            ->where('ks.id_kelas', (int) $id_kelas)
            ->get()->getResult();

        foreach ($siswas as $ind => $siswa) {
            $siswa->no = ($ind + 1);
            $siswa->foto = !empty($siswa->foto) && file_exists(FCPATH . $siswa->foto) ? base_url($siswa->foto) : base_url('assets/img/siswa_sm.png');
        }

        return $this->response->setJSON(['status' => true, 'siswa' => $siswas]);
    }

    public function updateAll()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $input = $this->request->getPost('siswa'); // Array of student data

        if (empty($input) || !is_array($input)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada data untuk diupdate']);
        }
        if (count($input) > 2000) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 2.000 baris per update']);
        }

        // Whitelist field bulk (blokir timpa username/password/foto/uid)
        $allowed = ['nis' => 20, 'nisn' => 20, 'nama' => 100];
        $db = \Config\Database::connect();
        $clean = [];
        $skipped = 0;

        // Peta nis/nisn milik baris lain untuk cek duplikat
        $allRows = [];
        try {
            foreach ($db->table('master_siswa')->select('id_siswa, nis, nisn')->get()->getResult() as $r) {
                $allRows[(int) $r->id_siswa] = ['nis' => trim((string) $r->nis), 'nisn' => trim((string) $r->nisn)];
            }
        } catch (\Throwable $e) {
            log_message('error', '[updateAll] preload: ' . $e->getMessage());
        }

        foreach ($input as $row) {
            if (!is_array($row) || empty($row['id_siswa'])) {
                $skipped++;
                continue;
            }
            $idSiswa = (int) $row['id_siswa'];
            if (!isset($allRows[$idSiswa])) {
                $skipped++;
                continue;
            }
            $entry = ['id_siswa' => $idSiswa];
            $valid = true;
            foreach ($allowed as $field => $maxLen) {
                if (array_key_exists($field, $row)) {
                    $val = trim((string) $row[$field]);
                    if ($field === 'nama' && ($val === '' || mb_strlen($val) < 3)) {
                        $valid = false;
                        break;
                    }
                    if (in_array($field, ['nis', 'nisn'], true) && ($val === '' || !ctype_digit($val))) {
                        $valid = false;
                        break;
                    }
                    if (mb_strlen($val) > $maxLen) {
                        $valid = false;
                        break;
                    }
                    // Cek duplikat terhadap baris lain — hanya untuk penanda
                    // unik. Nama tidak di-dup-check: preload di atas hanya
                    // memetakan nis/nisn, dan di satu sekolah nama yang sama
                    // itu wajar, jadi memaksa uniknya hanya membuat baris
                    // sah dilewati diam-diam.
                    if (in_array($field, ['nis', 'nisn'], true)) {
                        foreach ($allRows as $otherId => $other) {
                            if ($otherId !== $idSiswa && $other[$field] !== '' && $other[$field] === $val) {
                                $valid = false;
                                break 2;
                            }
                        }
                    }
                    $entry[$field] = $val;
                    $allRows[$idSiswa][$field] = $val; // update peta untuk baris berikutnya
                }
            }
            if (!$valid) {
                $skipped++;
                continue;
            }
            $clean[] = $entry;
        }

        if (empty($clean)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada baris valid (semua dilewati)']);
        }

        $tertulis = $this->masterModel->updateBatchData('master_siswa', $clean, 'id_siswa');
        if ($tertulis === false) {
            log_message('error', '[Datasiswa::updateAll] updateBatch master_siswa ditolak DB (' . count($clean) . ' baris)');

            return $this->response->setJSON([
                'status' => false,
                'message' => 'Perubahan siswa gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.',
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Berhasil memperbarui ' . count($clean) . ' siswa' . ($skipped > 0 ? " ({$skipped} dilewati)" : ''),
            'siswa' => count($clean)
        ]);
    }

    public function update_foto()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $input = $this->request->getPost('siswa');
        if (empty($input) || !is_array($input)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada foto untuk diupdate']);
        }
        if (count($input) > 500) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 500 foto per batch']);
        }

        if (!is_dir(FCPATH . 'uploads/foto_siswa/')) {
            mkdir(FCPATH . 'uploads/foto_siswa/', 0755, true);
        }

        $db = \Config\Database::connect();
        $validIds = [];
        try {
            foreach ($db->table('master_siswa')->select('id_siswa')->get()->getResult() as $r) {
                $validIds[(int) $r->id_siswa] = true;
            }
        } catch (\Throwable $e) {
            log_message('error', '[update_foto] preload: ' . $e->getMessage());
        }

        $siswas = [];
        $skipped = 0;
        foreach ($input as $value) {
            if (!is_array($value) || empty($value['id_siswa']) || !isset($validIds[(int) $value['id_siswa']])) {
                $skipped++;
                continue;
            }
            $idSiswa = (int) $value['id_siswa'];
            $safeNis = preg_replace('/[^0-9A-Za-z\-_]/', '', trim((string) ($value['nis'] ?? 'siswa' . $idSiswa)));
            if ($safeNis === '') $safeNis = 'siswa' . $idSiswa;
            $fotoPath = 'uploads/foto_siswa/' . $safeNis . '.jpg';

            if (!empty($value['foto'])) {
                $ext = strtolower(trim((string) ($value['ext'] ?? 'jpg')));
                // Hanya jpg/png — ekstensi lain (mis. php) ditolak mentah-mentah
                if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                    $skipped++;
                    continue;
                }
                $raw = base64_decode((string) $value['foto'], true);
                if ($raw === false || strlen($raw) > 2 * 1024 * 1024) {
                    $skipped++;
                    continue;
                }
                // Pastikan benar file gambar (bukan skrip berekstensi gambar)
                $info = @getimagesizefromstring($raw);
                if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                    $skipped++;
                    continue;
                }
                $output_file = $safeNis . '.jpg';
                if (file_put_contents(FCPATH . 'uploads/foto_siswa/' . $output_file, $raw) === false) {
                    $skipped++;
                    continue;
                }
                $fotoPath = 'uploads/foto_siswa/' . $output_file;
            }

            $siswas[] = [
                "id_siswa" => $idSiswa,
                "foto" => $fotoPath
            ];
        }

        $tersimpan = 0;
        if (!empty($siswas)) {
            $tertulis = $this->masterModel->updateBatchData('master_siswa', $siswas, 'id_siswa');
            if ($tertulis === false) {
                log_message('error', '[Datasiswa::update_foto] updateBatch ditolak DB (' . count($siswas) . ' baris)');

                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Foto gagal ditautkan ke data siswa. File gambarnya sudah tersimpan — ulangi menyimpan, dan bila berulang lapor ke pengembang.',
                ]);
            }
            $tersimpan = count($siswas);
        }

        return $this->response->setJSON([
            'status' => $tersimpan > 0,
            'message' => $tersimpan > 0
                ? 'Berhasil memperbarui ' . $tersimpan . ' foto siswa' . ($skipped > 0 ? " ({$skipped} dilewati)" : '')
                : 'Tidak ada foto yang tersimpan — semua baris dilewati (' . $skipped . ' baris tidak valid).',
            'total' => $tersimpan
        ]);
    }

    /**
     * Hapus foto profil siswa (kembalikan ke default gender).
     */
    public function delete_foto($id_siswa)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $siswa = $this->masterModel->getSiswaById((int) $id_siswa);
        if (!$siswa) {
            return $this->response->setJSON(['status' => false, 'message' => 'Siswa tidak ditemukan']);
        }

        if (!empty($siswa->foto)) {
            $rel = ltrim((string) $siswa->foto, '/');
            // Hanya hapus file di dalam uploads/foto_siswa (jangan sentuh assets)
            if (strpos($rel, 'uploads/foto_siswa/') === 0 && strpos($rel, '..') === false && file_exists(FCPATH . $rel)) {
                @unlink(FCPATH . $rel);
            }
        }

        $this->masterModel->updateData('master_siswa', ['foto' => null], 'id_siswa', (int) $id_siswa);

        return $this->response->setJSON(['status' => true, 'message' => 'Foto dihapus, kembali ke default']);
    }

    public function editLogin()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id_siswa = (int) $this->request->getPost('id_siswa');
        $password = (string) $this->request->getPost('password');

        if (strlen($password) < 6 || strlen($password) > 72) {
            return $this->response->setJSON(['status' => false, 'message' => 'Password 6–72 karakter']);
        }

        $siswa = $this->masterModel->getSiswaById($id_siswa);
        if (!$siswa) {
            return $this->response->setJSON(['status' => false, 'message' => 'Siswa tidak ditemukan']);
        }

        // Update in master_siswa
        $this->masterModel->updateData('master_siswa', ['password' => $password], 'id_siswa', $id_siswa);

        // Update in users table
        $db = \Config\Database::connect();
        $db->table('users')
            ->where('username', $siswa->username)
            ->update(['password' => password_hash($password, PASSWORD_BCRYPT)]);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Password berhasil diperbarui'
        ]);
    }

    public function data()
    {
        return $this->response->setJSON(['data' => []]);
    }
}
