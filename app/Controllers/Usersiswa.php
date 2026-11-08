<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\DashboardModel;
use App\Models\AuthModel;
use App\Models\MasterModel;

class Usersiswa extends BaseController
{
    protected $users;
    protected $dashboard;
    protected $auth;
    protected $master;

    public function __construct()
    {
        $this->users = new UsersModel();
        $this->dashboard = new DashboardModel();
        $this->auth = new AuthModel();
        $this->master = new MasterModel();
        helper(['form', 'url']);
    }

    public function index()
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('dashboard');
        }

        $user = $this->getUser();
        $data = [
            'user' => $user,
            'judul' => 'User Management',
            'subjudul' => 'Data User Siswa',
            'profile' => null,
            'setting' => $this->dashboard->getSetting(),
            'is_admin' => $this->isAdmin(),
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $this->dashboard->getTahunActive(),
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive()
        ];

        // Load role specific logic if panitia
        if ($this->isPanitia()) {
            $data['panitia'] = $this->dashboard->getDataPanitiaByUserId($user->id, $data['tp_active']->id_tp, $data['smt_active']->id_smt);
        }

        return view('users/siswa/data', $data);
    }

    /**
     * Panitia boleh melihat roster siswa, tetapi kolom password berisi
     * kredensial login plaintext — hanya admin yang boleh menerimanya.
     */
    private function redactPasswords(array $rows): void
    {
        if ($this->isAdmin()) {
            return;
        }
        foreach ($rows as &$row) {
            if (is_object($row) && property_exists($row, 'password')) {
                $row->password = '********';
            } elseif (is_array($row) && array_key_exists('password', $row)) {
                $row['password'] = '********';
            }
        }
        unset($row);
    }

    // JSON Data endpoint for Datatables
    public function data()
    {
        if ($deny = $this->requireRole(['admin', 'panitia'])) {
            return $deny;
        }
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON([]);
        }
        $rows = $this->users->getUserSiswa($tp->id_tp, $smt->id_smt);
        $this->redactPasswords($rows);
        return $this->response->setJSON($rows);
    }

    // Manual pagination endpoint
    public function list()
    {
        if ($deny = $this->requireRole(['admin', 'panitia'])) {
            return $deny;
        }
        $page = max(1, (int) ($this->request->getPost('page') ?? 1));
        $limit = (int) ($this->request->getPost('limit') ?? 10);
        $limit = in_array($limit, [10, 25, 50, 100], true) ? $limit : 10;
        $search = mb_substr(trim((string) $this->request->getPost('search')), 0, 100);
        $reset = $this->request->getPost('reset') === '1';

        $offset = ($page - 1) * $limit;
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON(['lists' => [], 'total' => 0, 'pages' => 0, 'search' => $search, 'perpage' => $limit]);
        }

        $count_siswa = $this->users->getUserSiswaTotalPage($search, $reset);
        $lists = $this->users->getUserSiswaPage($tp->id_tp, $smt->id_smt, $offset, $limit, $search, null, null, $reset);

        foreach ($lists as $list) {
            $list->foto = ($list->foto != null && $list->foto != "" && file_exists($list->foto)) ? base_url($list->foto) : base_url('assets/img/siswa_sm.png');
        }
        $this->redactPasswords($lists);

        $data = [
            'lists' => $lists,
            'total' => $count_siswa,
            'pages' => ceil($count_siswa / $limit),
            'search' => $search,
            'perpage' => $limit
        ];

        return $this->response->setJSON($data);
    }

    public function activate()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $id_siswa = (int) $this->request->getPost('id_siswa');
        $is_active = $this->request->getPost('is_active') === 'aktifkan' ? 'aktifkan' : 'nonaktifkan';

        if ($id_siswa <= 0) {
            return $this->response->setJSON(['status' => false, 'msg' => 'ID siswa tidak valid']);
        }

        $siswa = $this->master->getSiswa($id_siswa);
        if (!$siswa || empty($siswa->username)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Data siswa / username tidak ditemukan']);
        }

        $active = false;
        // Get user by username
        $user = $this->auth->isExist($siswa->username);
        if ($user) {
            $status = ($is_active == 'aktifkan') ? 1 : 0;
            $tersimpan = $this->master->updateData('users', ['active' => $status], 'id', $user->id);
            $active = $tersimpan !== false;
        } else {
            // Register if not exists
            if ($is_active == 'aktifkan') {
                // Cegah duplikat username di master (indikasi data ganda)
                $duplikat = $this->users->usernameCheck($siswa->username, 'master_siswa');
                if (count($duplikat) > 1) {
                    return $this->response->setJSON([
                        'status' => false,
                        'msg' => 'Username ' . $siswa->username . ' dipakai ' . count($duplikat) . ' data siswa — perbaiki dulu datanya',
                    ]);
                }
                $plainPassword = trim((string) ($siswa->password ?? ''));
                if ($plainPassword === '') $plainPassword = '123456';
                $user_data = [
                    'username' => $siswa->username,
                    'password' => password_hash($plainPassword, PASSWORD_BCRYPT),
                    'first_name' => $siswa->nama,
                    'active' => 1
                ];
                // register() hanya membuat; 0 berarti akun tidak tercipta,
                // jadi jangan lapor "diaktifkan" untuk siswa tanpa akun.
                $active = $this->auth->register($user_data, '3') > 0; // 3 for siswa
            }
        }

        return $this->response->setJSON([
            'status' => $active,
            'msg' => $active ? ('Siswa ' . ($is_active === 'aktifkan' ? 'diaktifkan' : 'dinonaktifkan')) : 'Gagal mengubah status',
        ]);
    }

    public function reset_login()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        $username = trim((string) $this->request->getPost('username'));
        if ($username === '') {
            return $this->response->setJSON(['status' => false, 'msg' => 'Username kosong']);
        }
        $ok = $this->auth->resetErrorLogin($username);
        return $this->response->setJSON(['status' => (bool) $ok, 'msg' => $ok ? 'Login berhasil direset' : 'Gagal mereset login']);
    }

    /**
     * Reset password siswa ke default = NISN (khusus admin).
     * Update hash di users + plaintext legacy di master_siswa.
     *
     * Nilai NISN sengaja TIDAK dikembalikan ke klien: pesan API ini terbaca di
     * layar siapa pun yang berdiri di belakang admin, dan NISN adalah password
     * default yang bisa ditebak dari data siswa yang mudah diperoleh.
     */
    public function reset_password()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        $username = trim((string) $this->request->getPost('username'));
        if ($username === '') {
            return $this->response->setJSON(['status' => false, 'msg' => 'Username kosong']);
        }

        $db = \Config\Database::connect();
        $siswa = null;
        if ($db->tableExists('master_siswa')) {
            $siswa = $db->table('master_siswa')->where('username', $username)->get()->getRow();
        }
        if (!$siswa) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Data siswa "' . $username . '" tidak ditemukan']);
        }
        $nisn = trim((string) ($siswa->nisn ?? ''));
        if ($nisn === '') {
            return $this->response->setJSON(['status' => false, 'msg' => 'NISN siswa kosong — lengkapi data siswa dulu']);
        }

        $account = $db->table('users')->where('username', $username)->get()->getRow();
        if (!$account) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akun login "' . $username . '" belum ada — aktifkan akun dulu']);
        }

        // users dan master_siswa harus berubah bersamaan: kalau hanya salah satu
        // yang tertulis, login memakai hash baru sementara cermin plaintext masih
        // lama (atau sebaliknya), dan admin tetap melihat pesan "berhasil".
        $dibuka = $db->transBegin();
        try {
            $hasilUsers = $db->table('users')->where('id', (int) $account->id)->update([
                'password' => password_hash($nisn, PASSWORD_BCRYPT),
            ]);
            $hasilMaster = $db->table('master_siswa')->where('username', $username)->update(['password' => $nisn]);

            if ($hasilUsers === false || $hasilMaster === false) {
                if ($dibuka) {
                    $db->transRollback();
                }
                log_message('error', '[Usersiswa::reset_password] update gagal untuk "' . $username . '" (users='
                    . var_export($hasilUsers, true) . ', master=' . var_export($hasilMaster, true) . ')');
                return $this->response->setJSON(['status' => false, 'msg' => 'Password gagal direset — periksa log aplikasi.']);
            }

            if ($dibuka && $db->transStatus() === false) {
                $db->transRollback();
                log_message('error', '[Usersiswa::reset_password] transaksi gagal untuk "' . $username . '"');
                return $this->response->setJSON(['status' => false, 'msg' => 'Password gagal direset — periksa log aplikasi.']);
            }
            if ($dibuka) {
                $db->transComplete();
            }
        } catch (\Throwable $e) {
            if ($dibuka) {
                $db->transRollback();
            }
            log_message('error', '[Usersiswa::reset_password] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'msg' => 'Terjadi kesalahan sistem — periksa log aplikasi.']);
        }

        // Nama, bukan username: di sekolah ini username = NISN = password
        // default, jadi menyebutkan username di pesan berarti menulis password
        // di layar admin.
        $nama = trim((string) ($siswa->nama ?? ''));

        return $this->response->setJSON([
            'status' => true,
            'msg'    => ($nama !== '' ? 'Password siswa ' . $nama . ' ' : 'Password siswa ini ')
                . 'berhasil direset ke NISN-nya.',
        ]);
    }
    public function aktifkansemua()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false]);

        $is_active = $this->request->getPost('is_active');
        $status = ($is_active == 'aktifkan') ? 1 : 0;

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $db = \Config\Database::connect();
        // QB update() tidak mendukung JOIN (JOIN dibuang → "unknown column").
        // Pakai subquery agar hanya grup siswa (group_id 3) yang tersentuh.
        try {
            $db->query(
                'UPDATE users SET active = ? WHERE id IN (SELECT user_id FROM users_groups WHERE group_id = 3)',
                [(int) $status]
            );
            $affected = $db->affectedRows();
        } catch (\Throwable $e) {
            log_message('error', '[Usersiswa::aktifkansemua] UPDATE users gagal: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'msg' => 'Gagal mengubah status akun siswa — periksa log aplikasi']);
        }

        // "Aktifkan Semua" juga harus membuatkan akun bagi siswa master
        // yang belum punya akun (ini yang membuat bulk terasa "tidak jalan"):
        // samakan dengan logika activate() per-siswa.
        $created = 0;
        $skipped = 0;
        $gagal = 0;
        if ($is_active == 'aktifkan' && $db->tableExists('master_siswa')) {
            $existing = [];
            foreach ($db->table('users')->select('username')->get()->getResult() as $u) {
                $existing[trim((string) $u->username)] = true;
            }
            $seen = [];
            foreach ($db->table('master_siswa')->select('username, nama, password')->get()->getResult() as $s) {
                $uname = trim((string) ($s->username ?? ''));
                if ($uname === '' || isset($existing[$uname])) continue;
                if (isset($seen[$uname])) { $skipped++; continue; } // duplikat di master
                $seen[$uname] = true;
                $plain = trim((string) ($s->password ?? ''));
                if ($plain === '') $plain = '123456';
                // register() = users + users_groups dalam satu transaksi dan
                // hanya membuat; loop lama menulis dua tabel tanpa transaksi
                // dan tanpa try/catch, sehingga satu username nabrak membuat
                // seluruh request 500 dengan batch separuh jalan.
                $idUser = $this->auth->register([
                    'username'   => $uname,
                    'password'   => password_hash($plain, PASSWORD_BCRYPT),
                    'first_name' => trim((string) ($s->nama ?? '')) ?: $uname,
                ], '3');
                if ($idUser > 0) {
                    $created++;
                } else {
                    $gagal++;
                    log_message('error', '[Usersiswa::aktifkansemua] akun "' . $uname . '" tidak terbuat');
                }
            }
        }

        $parts = [];
        if ($is_active == 'aktifkan') {
            if ($affected > 0) $parts[] = $affected . ' akun diaktifkan';
            if ($created > 0) $parts[] = $created . ' akun baru dibuat';
            if ($skipped > 0) $parts[] = $skipped . ' dilewati (username ganda)';
            if ($gagal > 0) $parts[] = $gagal . ' gagal dibuat (lihat log aplikasi)';
            $msg = $parts ? implode(', ', $parts) : 'Tidak ada perubahan (semua akun sudah aktif)';
        } else {
            $msg = $affected > 0 ? $affected . ' akun dinonaktifkan' : 'Tidak ada perubahan (semua akun sudah nonaktif)';
        }

        return $this->response->setJSON([
            'status' => $gagal === 0,
            'msg' => 'Siswa berhasil diupdate: ' . $msg,
        ]);
    }
}
