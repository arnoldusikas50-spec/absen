<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\DashboardModel;
use App\Models\AuthModel;
use App\Models\MasterModel;

class Userguru extends BaseController
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
        if (!$this->isAdmin() && !$this->auth->inGroup('guru')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Akses Terlarang');
        }

        $user = $this->getUser();
        $data = [
            'user' => $user,
            'judul' => 'User Management',
            'subjudul' => 'Data User Guru',
            'profile' => null,
            'setting' => $this->dashboard->getSetting(),
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $this->dashboard->getTahunActive(),
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive()
        ];

        if ($this->isAdmin()) {
            return view('users/guru/data', $data);
        } else {
            $guru = $this->users->getGuruByUsername($user->username);
            if (!$guru) {
                return redirect()->to('presensi')->with('error', 'Data guru tidak ditemukan');
            }
            return redirect()->to('userguru/edit/' . $guru->id_guru);
        }
    }

    public function data()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON([]);
        }
        $data = $this->users->getUserGuru($tp->id_tp, $smt->id_smt);
        foreach ($data as $list) {
            $list->foto = ($list->foto != null && $list->foto != "" && file_exists($list->foto)) ? base_url($list->foto) : base_url('assets/img/siswa_sm.png');
        }
        return $this->response->setJSON($data);
    }

    public function edit($id)
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }
        $guru = $this->users->getDetailGuru($id);

        if (!$guru)
            return redirect()->to('userguru');

        $users = $this->users->getUsers($guru->username);
        $user = $this->getUser();

        $data = [
            'user' => $user,
            'judul' => 'User Management',
            'subjudul' => 'Edit Data User',
            'setting' => $this->dashboard->getSetting(),
            'users' => $users,
            'guru' => $guru,
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $smt,
            'is_admin' => $this->isAdmin()
        ];

        if ($this->isAdmin()) {
            $data['groups'] = $this->auth->groups();
            $data['kelass'] = $this->users->getKelas($tp->id_tp, $smt->id_smt);
            $data['mapels'] = $this->users->getMapel();
            $data['levels'] = $this->users->getLevelGuru();
            return view('users/guru/edit', $data);
        }
        // Non-admin: hanya boleh buka data miliknya sendiri (anti-IDOR horizontal)
        $me = $this->getUser();
        $ownGuru = $me ? $this->users->getGuruByUsername($me->username) : null;
        if (!$me || !$ownGuru || (int) $ownGuru->id_guru !== (int) $guru->id_guru) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }
        return view('users/guru/edit', $data);
    }

    public function activate()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $id_guru = (int) $this->request->getPost('id_guru');
        $id_user = (int) $this->request->getPost('id');
        $nama_guru = trim((string) $this->request->getPost('nama'));
        $is_active = $this->request->getPost('is_active') === 'aktifkan' ? 'aktifkan' : 'nonaktifkan';

        if ($id_guru <= 0) {
            return $this->response->setJSON(['status' => false, 'msg' => 'ID guru tidak valid']);
        }

        $guru = $this->users->getDataGuru($id_guru);
        if (!$guru) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Data guru tidak ditemukan']);
        }
        $usernames = $this->users->usernameCheck($guru->username ?? '-', 'master_guru');

        if (count($usernames) > 1 && $is_active == 'aktifkan') {
            return $this->response->setJSON([
                'status' => false,
                'msg' => 'ada ' . count($usernames) . ' username menggunakan ' . $guru->username . ', silahkan ganti username',
                'users' => $usernames
            ]);
        }

        $active = false;

        if ($id_user) {
            if (!$this->auth->user($id_user)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Akun user tidak ditemukan']);
            }
            // Toggle active status in users table
            $status = ($is_active == 'aktifkan') ? 1 : 0;
            $active = $this->master->updateData('users', ['active' => $status], 'id', $id_user);
            $msg = $is_active == 'aktifkan' ? ' diaktifkan' : ' dinonaktifkan';
        } else {
            if ($is_active !== 'aktifkan') {
                return $this->response->setJSON(['status' => false, 'msg' => 'Akun belum ada — hanya bisa diaktifkan']);
            }
            $username = trim((string) ($guru->username ?? ''));
            if ($username === '') {
                return $this->response->setJSON(['status' => false, 'msg' => 'Username guru kosong — lengkapi data guru dulu']);
            }
            if ($this->auth->isExist($username)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Username ' . $username . ' sudah dipakai akun lain']);
            }
            $nama = preg_split('/\s+/', trim($guru->nama_guru ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $first_name = $nama[0] ?? 'Guru';
            $last_name = count($nama) > 1 ? implode(' ', array_slice($nama, 1)) : '';

            $plainPassword = trim((string) ($guru->password ?? ''));
            if ($plainPassword === '') $plainPassword = '123456';

            $user = [
                'username' => $username,
                'password' => password_hash($plainPassword, PASSWORD_BCRYPT),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $username . '@guru.sekolah' // Mock email
            ];

            $id_user = $this->auth->register($user, '2'); // 2 for guru group
            if ($id_user <= 0) {
                // id_user punya UNIQUE index; menulis 0 membuat guru kedua
                // menabrak duplikat dan guru tetap tanpa akun.
                return $this->response->setJSON([
                    'status' => false,
                    'msg' => 'Guru ' . $nama_guru . ' gagal: akun login tidak berhasil dibuat (lihat log aplikasi)'
                ]);
            }

            $input = ['id_user' => $id_user];
            $active = $this->master->updateData('master_guru', $input, 'id_guru', $id_guru) !== false;
            $msg = ' diaktifkan';
        }

        return $this->response->setJSON([
            'status' => (bool) $active,
            'msg' => $active ? 'Guru ' . $nama_guru . $msg : 'Guru ' . $nama_guru . ' gagal' . $msg
        ]);
    }

    public function reset_login()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $username = trim((string) $this->request->getPost('username'));
        $nama = mb_substr(trim((string) $this->request->getPost('nama')), 0, 60);

        if ($username === '') {
            return $this->response->setJSON(['status' => false, 'msg' => 'Username kosong']);
        }

        if ($this->auth->resetErrorLogin($username)) {
            $data = ['status' => true, 'msg' => 'User ' . $nama . ' berhasil direset'];
        } else {
            $data = ['status' => false, 'msg' => 'User ' . $nama . ' gagal direset (Mungkin tidak ada error login)'];
        }
        return $this->response->setJSON($data);
    }

    /**
     * Reset password guru ke default "123456" (khusus admin).
     * Update hash di users + plaintext legacy di master_guru.
     *
     * Nilai default tidak disertakan di respons: pesannya muncul di layar admin
     * dan bisa terbaca orang lain, sementara kolom password master_guru masih
     * plaintext.
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
        $account = $db->table('users')->where('username', $username)->get()->getRow();
        if (!$account) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akun login "' . $username . '" belum ada — aktifkan akun dulu']);
        }

        $default = '123456';
        // users (hash) dan master_guru (cermin plaintext) adalah satu kesatuan:
        // tulis separuh membuat guru bisa login dengan password lama lewat
        // jalur yang masih membaca master_guru.
        $dibuka = $db->transBegin();
        try {
            $hasilUsers = $db->table('users')->where('id', (int) $account->id)->update([
                'password' => password_hash($default, PASSWORD_BCRYPT),
            ]);
            $hasilMaster = true;
            if ($db->tableExists('master_guru')) {
                $hasilMaster = $db->table('master_guru')->where('username', $username)->update(['password' => $default]);
            }

            if ($hasilUsers === false || $hasilMaster === false) {
                if ($dibuka) {
                    $db->transRollback();
                }
                log_message('error', '[Userguru::reset_password] update gagal untuk "' . $username . '" (users='
                    . var_export($hasilUsers, true) . ', master=' . var_export($hasilMaster, true) . ')');
                return $this->response->setJSON(['status' => false, 'msg' => 'Password gagal direset — periksa log aplikasi.']);
            }

            if ($dibuka && $db->transStatus() === false) {
                $db->transRollback();
                log_message('error', '[Userguru::reset_password] transaksi gagal untuk "' . $username . '"');
                return $this->response->setJSON(['status' => false, 'msg' => 'Password gagal direset — periksa log aplikasi.']);
            }
            if ($dibuka) {
                $db->transComplete();
            }
        } catch (\Throwable $e) {
            if ($dibuka) {
                $db->transRollback();
            }
            log_message('error', '[Userguru::reset_password] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'msg' => 'Terjadi kesalahan sistem — periksa log aplikasi.']);
        }

        return $this->response->setJSON(['status' => true, 'msg' => 'Password "' . $username . '" berhasil direset ke password default.']);
    }

    public function aktifkan_all()
    {
        return $this->aktifkansemua();
    }

    public function aktifkansemua()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'msg' => 'Unauthorized']);

        try {
            $is_active = $this->request->getPost('is_active');
            $status = ($is_active == 'aktifkan') ? 1 : 0;

            $tp = $this->dashboard->getTahunActive();
            $smt = $this->dashboard->getSemesterActive();

            if (!$tp || !isset($tp->id_tp)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Tahun Pelajaran aktif tidak ditemukan']);
            }
            if (!$smt || !isset($smt->id_smt)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Semester aktif tidak ditemukan']);
            }

            $gurus = $this->users->getUserGuru($tp->id_tp, $smt->id_smt);
            $success_count = 0;
            $fail_count = 0;

            foreach ($gurus as $guru) {
                if ($is_active == 'aktifkan') {
                    if ($guru->id == null) {
                        // Register
                        $nama = explode(' ', $guru->nama_guru ?? '');
                        $firstName = $nama[0] ?? 'Guru';

                        // Handler for Last Name (Safe)
                        $lastName = '';
                        if (count($nama) > 1) {
                            unset($nama[0]);
                            $lastName = implode(' ', $nama);
                        }

                        // Ensure unique username if empty? 
                        // Assuming username is valid from master data.
                        $username = trim($guru->username ?? '');
                        if (empty($username))
                            continue;

                        $user = [
                            'username' => $username,
                            'password' => password_hash(trim($guru->password ?? '123456'), PASSWORD_BCRYPT),
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $username . '@guru.sekolah',
                            'active' => 1
                        ];

                        try {
                            $id_user = $this->auth->register($user, '2');
                            $terhubung = $id_user > 0 && $this->master->updateData('master_guru', ['id_user' => $id_user], 'id_guru', $guru->id_guru) !== false;
                            if ($terhubung) {
                                $success_count++;
                            } else {
                                // register() membuat akun tapi tautan ke
                                // master_guru gagal: akun itu harus dibuang
                                // lagi, kalau tidak ia menggantung tanpa guru.
                                if ($id_user > 0) {
                                    $this->auth->deleteUser($id_user);
                                }
                                $fail_count++;
                            }
                        } catch (\Exception $e) {
                            $fail_count++;
                            log_message('error', 'Error registering user: ' . $username . ' - ' . $e->getMessage());
                        }
                    } else {
                        // Activate
                        $aktif = $this->master->updateData('users', ['active' => 1], 'id', $guru->id);
                        $aktif !== false ? $success_count++ : $fail_count++;
                    }
                } else {
                    // Nonaktifkan
                    if ($guru->id != null) {
                        $nonaktif = $this->master->updateData('users', ['active' => 0], 'id', $guru->id);
                        $nonaktif !== false ? $success_count++ : $fail_count++;
                    }
                }
            }

            $msg = $success_count . ' guru berhasil ' . ($status ? 'diaktifkan' : 'dinonaktifkan');
            if ($fail_count > 0) {
                $msg .= ', ' . $fail_count . ' gagal (lihat log aplikasi)';
            }
            return $this->response->setJSON(['status' => $fail_count === 0, 'msg' => $msg]);

        } catch (\Throwable $th) {
            // Pesan exception mentah tidak dikirim ke klien.
            log_message('error', '[Userguru::aktifkansemua] ' . $th->getMessage());
            return $this->response->setJSON(['status' => false, 'msg' => 'Terjadi kesalahan sistem — periksa log aplikasi.']);
        }
    }

    /**
     * Dipanggil JS setelah aktivasi massal. Tidak ada lagi yang perlu ditulis di
     * sini (activate()/aktifkansemua() sudah menautkan master_guru.id_user),
     * jadi endpoint ini melaporkan keadaan tautan yang sebenarnya daripada
     * selalu menjawab "Data User Updated" walau ada guru yang menggantung.
     */
    public function update_iduser()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('master_guru')) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Tabel master_guru tidak ditemukan.']);
        }

        try {
            $belum = (int) $db->query(
                'SELECT COUNT(*) AS jumlah FROM master_guru
                 WHERE (id_user IS NULL OR id_user = 0) AND deleted_at IS NULL'
            )->getRow()->jumlah;

            // id_user yang menunjuk baris users yang sudah hilang
            $yatim = (int) $db->query(
                'SELECT COUNT(*) AS jumlah FROM master_guru mg
                 LEFT JOIN users u ON u.id = mg.id_user
                 WHERE mg.id_user IS NOT NULL AND mg.id_user <> 0 AND u.id IS NULL AND mg.deleted_at IS NULL'
            )->getRow()->jumlah;
        } catch (\Throwable $e) {
            log_message('error', '[Userguru::update_iduser] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'msg' => 'Status tautan akun gagal diperiksa — periksa log aplikasi.']);
        }

        if ($belum === 0 && $yatim === 0) {
            return $this->response->setJSON(['status' => true, 'msg' => 'Semua data guru sudah terhubung ke akun login.']);
        }

        $catatan = [];
        if ($belum > 0) {
            $catatan[] = $belum . ' guru belum punya akun login';
        }
        if ($yatim > 0) {
            $catatan[] = $yatim . ' guru menunjuk akun yang sudah tidak ada';
        }
        $msg = 'Tautan akun belum lengkap: ' . implode(', ', $catatan) . '. Aktifkan akun per guru atau gunakan Aktifkan Semua.';
        return $this->response->setJSON(['status' => false, 'msg' => $msg]);
    }

    public function editlogin()
    {
        if (!$this->isLoggedIn())
            return $this->response->setJSON(['status' => false, 'msg' => 'Login required']);

        $id = $this->request->getPost('id'); // guru id? or user id? View passes $guru->id (master_guru id likely? Or user id?)
        // In View: form_open(..., ['id' => $guru->id])
        // And $guru->id seems to be master_guru's primary key if fetched via getDetailGuru?
        // Wait, getDetailGuru returns row with id_guru, id_user, etc.
        // Let's assume input 'id' is id_guru from master_guru table context or id_user.
        // Standard in this app seems to be mixed.
        // But let's look at `activate`: it takes `id_guru`.

        // Actually, looking at `users/guru/edit.php` (CI3), line 36: `form_open(..., array('id' => $guru->id)`
        // If $guru object comes from `getDetailGuru`, check `UsersModel`.
        // `getUserGuru` joins users. `id` usually refers to `users.id` (user account id).
        // `id_guru` is master id.

        $username = trim((string) $this->request->getPost('username'));
        $oldUsername = trim((string) $this->request->getPost('old_username'));
        $idGuru = (int) $this->request->getPost('id_guru');
        $old = $this->request->getPost('old');
        $new = $this->request->getPost('new');

        // Resolusi akun yang diedit: utamakan id_guru, fallback username lama / username aktif
        $db = \Config\Database::connect();
        $guruRow = null;
        if ($idGuru > 0 && $db->tableExists('master_guru')) {
            $guruRow = $db->table('master_guru')->where('id_guru', $idGuru)->get()->getRow();
        }
        if (!$guruRow && $oldUsername !== '' && $db->tableExists('master_guru')) {
            $guruRow = $db->table('master_guru')->where('username', $oldUsername)->get()->getRow();
        }
        $currentUsername = $guruRow->username ?? ($oldUsername !== '' ? $oldUsername : $username);
        if ($username === '') $username = $currentUsername;

        // Non-admin hanya boleh mengubah login miliknya sendiri
        if (!$this->isAdmin()) {
            $me = $this->getUser();
            if (!$me || strcasecmp((string) $me->username, (string) $currentUsername) !== 0) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
            }
        }

        // Akun users milik username lama
        $account = $this->auth->isExist($currentUsername);
        if (!$account) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akun user tidak ditemukan']);
        }

        // If changing password — verifikasi dulu, sebelum ada tulis-menulis
        $wantPassword = (string) $new !== '';
        if ($wantPassword) {
            if (strlen((string) $new) < 6) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Password baru minimal 6 karakter']);
            }
            if (! password_verify((string) $old, (string) $account->password)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'Password lama salah']);
            }
        }

        // If changing username (harus unik, sinkron ke baris master terkait)
        $renamed = false;
        if ($username !== '' && strcasecmp($username, $currentUsername) !== 0) {
            $hasilRename = $this->auth->renameUser((int) $account->id, $username);
            if (! $hasilRename['status']) {
                return $this->response->setJSON(['status' => false, 'msg' => $hasilRename['message']]);
            }
            $renamed = (bool) ($hasilRename['changed'] ?? false);
            if ($renamed) {
                // Sinkronkan sesi bila yang diubah adalah akun sendiri
                if ((int) session()->get('user_id') === (int) $account->id) {
                    session()->set('username', $username);
                }
                $currentUsername = $username;
            }
            $account = $this->auth->isExist($currentUsername);
        }

        if ($wantPassword && ! $this->auth->change_password($currentUsername, (string) $old, (string) $new)) {
            return $this->response->setJSON([
                'status' => false,
                'msg' => $renamed
                    ? 'Username sudah berganti, tapi password gagal diubah — ulangi ubah password.'
                    : 'Gagal mengubah password (password lama salah?)'
            ]);
        }

        // If changing info (first_name, etc) — pakai akun hasil resolusi di atas
        $first_name = trim((string) $this->request->getPost('first_name'));
        if ($first_name !== '' && $account) {
            if (mb_strlen($first_name) > 50) {
                $first_name = mb_substr($first_name, 0, 50);
            }
            $last_name = trim((string) $this->request->getPost('last_name'));
            if (mb_strlen($last_name) > 50) {
                $last_name = mb_substr($last_name, 0, 50);
            }
            $email = trim((string) $this->request->getPost('email'));
            if ($email !== '' && (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100)) {
                return $this->response->setJSON([
                    'status' => false,
                    'msg' => 'Format email tidak valid (max 100 karakter).'
                ]);
            }
            $data = [
                'first_name' => $first_name,
                'last_name' => $last_name !== '' ? $last_name : null,
                'email' => $email !== '' ? $email : null
            ];
            $tersimpan = $this->master->updateData('users', $data, 'id', (int) $account->id);
            if ($tersimpan === false) {
                log_message('error', '[Userguru::editlogin] simpan profil users#' . (int) $account->id . ' gagal');
                return $this->response->setJSON([
                    'status' => false,
                    'msg' => ($renamed || $wantPassword)
                        ? 'Login sudah diperbarui, tapi nama/email gagal disimpan — ulangi simpan profil.'
                        : 'Nama/email gagal disimpan — periksa log aplikasi.'
                ]);
            }
        }

        return $this->response->setJSON(['status' => true, 'msg' => $renamed ? 'Username & login berhasil diperbarui' : 'Berhasil diupdate']);
    }
}
