<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AuthModel - Migrated from CI3 Auth_model
 * Handles user authentication, login attempts, and user management
 */
class AuthModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = [
        'ip_address',
        'username',
        'password',
        'email',
        'activation_code',
        'forgotten_password_code',
        'forgotten_password_time',
        'remember_code',
        'created_on',
        'last_login',
        'active',
        'first_name',
        'last_name',
        'company',
        'phone',
        'foto',
        'nomor_peserta'
    ];

    // Dates
    protected $useTimestamps = false;

    /**
     * Check if user is logged in
     */
    public function loggedIn(): bool
    {
        return (bool) session()->get('is_login');
    }

    /**
     * Check if current user is in specified group
     * @param string|array $group
     */
    public function inGroup(string|array $group): bool
    {
        $role = strtolower((string) (session()->get('role_name') ?? ''));
        // Alias: pengawas (nama grup di DB) == panitia (nama di kode)
        if ($role === 'pengawas') {
            $role = 'panitia';
        }
        $norm = static function ($g): string {
            $g = strtolower((string) $g);
            return $g === 'pengawas' ? 'panitia' : $g;
        };
        if (is_array($group)) {
            $group = array_map($norm, $group);
            return in_array($role, $group, true);
        }
        return $role === $norm($group);
    }

    /**
     * Check if current user is admin
     * Disamakan dengan BaseController::isAdmin() (banding longgar + role_name).
     */
    public function isAdmin(): bool
    {
        $role = (string) (session()->get('role') ?? '');
        $roleName = strtolower((string) (session()->get('role_name') ?? ''));
        return $role === '1' || $roleName === 'admin' || $this->inGroup('admin');
    }

    /**
     * Get user by ID
     */
    public function user(?int $id = null): ?object
    {
        $userId = $id ?? session()->get('user_id');

        $user = $this->find($userId);

        if ($user) {
            if ($user->first_name === $user->last_name) {
                $user->nama_lengkap = $user->first_name;
            } else {
                $user->nama_lengkap = $user->first_name . ' ' . $user->last_name;
            }
        }

        return $user;
    }

    /**
     * Get user profile
     */
    public function userProfile(int $userId): ?object
    {
        return $this->find($userId);
    }

    /**
     * Get all profiles by user IDs
     */
    public function allProfiles(array $userIds): array
    {
        return $this->whereIn('id', $userIds)->findAll();
    }

    /**
     * Buat akun login baru.
     *
     * HANYA membuat. Bila username sudah dipakai, fungsi ini mengembalikan 0
     * tanpa menulis apa pun: versi lama mengubah baris akun yang sudah ada
     * — termasuk password dan grup — sehingga seorang admin yang mengetik
     * username orang lain di form "tambah panitia/admin" langsung mengambil
     * alih akun itu (password diganti, guru/admin turun jadi grup lain).
     * users_groups juga ditulis ulang semua barisnya, yang menabrak UNIQUE
     * (user_id, group_id) bila akun punya dua grup.
     *
     * Transaksinya melindungi dua tulisannya selama register() memegang
     * transaksi TERLUAR: di driver MySQLi transBegin() bersarang hanya
     * menaikkan depth, jadi rollback di dalam transaksi pemanggil tidak
     * membatalkan apa pun. Semua pemanggil saat ini (Useradmin/Userguru/
     * Usersiswa/Userpanitia) memanggil tanpa transaksi sendiri.
     */
    public function register(array $data, string $group): int
    {
        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '') {
            log_message('error', '[AuthModel::register] username kosong — akun tidak dibuat');
            return 0;
        }
        $data['username'] = $username;

        if ($this->isExist($username)) {
            log_message('error', '[AuthModel::register] username "' . $username . '" sudah dipakai — tidak ada yang diubah');
            return 0;
        }

        $data['ip_address'] = service('request')->getIPAddress();
        $data['created_on'] = time();
        $data['active'] = '1';

        $db = $this->db;
        $db->transBegin();
        try {
            unset($data['id']);
            $tertulisUser = $this->insert($data, false);
            $id = $tertulisUser ? (int) $this->getInsertID() : 0;
            if ($id <= 0) {
                $db->transRollback();
                log_message('error', '[AuthModel::register] insert users gagal: ' . json_encode($db->error()));
                return 0;
            }

            $tertulisGrup = $db->table('users_groups')->insert([
                'user_id'  => $id,
                'group_id' => (int) $group,
            ]);
            if ($tertulisGrup === false || $db->transStatus() === false) {
                $db->transRollback();
                log_message('error', '[AuthModel::register] insert users_groups gagal (user ' . $id . '): ' . json_encode($db->error()));
                return 0;
            }

            if (! $db->transCommit()) {
                $db->transRollback();
                log_message('error', '[AuthModel::register] commit gagal — akun dibatalkan');
                return 0;
            }
            return $id;
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', '[AuthModel::register] ' . $e->getMessage());
            return 0;
        }
    }


    /**
     * Check if username exists
     */
    public function isExist(string $username): ?object
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Ganti username akun bersama baris master yang tersambung, atomik.
     *
     * Join roster ke akun login memakai username (UsersModel::getUserGuru /
     * getUserSiswa, MasterModel), jadi mengganti hanya users.username membuat
     * guru/siswa kehilangan akunnya secara efektif — "aktifkan akun" lalu
     * membuatkan akun KEDUA untuk orang yang sama.
     */
    public function renameUser(int $userId, string $newUsername): array
    {
        $new = trim($newUsername);
        $old = '';

        if ($userId <= 0) {
            return ['status' => false, 'message' => 'Akun tidak valid.'];
        }
        if (strlen($new) < 4 || strlen($new) > 30 || ! preg_match('/^[A-Za-z0-9_.-]+$/', $new)) {
            return ['status' => false, 'message' => 'Username baru tidak valid (4–30 karakter: huruf/angka/titik/strip/underscore).'];
        }

        $db = $this->db;
        $db->transBegin();
        try {
            $user = $db->table('users')->where('id', $userId)->get()->getRow();
            if (! $user) {
                $db->transRollback();
                return ['status' => false, 'message' => 'Akun tidak ditemukan.'];
            }
            $old = trim((string) $user->username);
            if ($old === $new) {
                $db->transRollback();
                return ['status' => true, 'message' => 'Username tidak berubah.', 'changed' => false];
            }
            $dupe = $db->table('users')->where('username', $new)->where('id !=', $userId)->countAllResults();
            if ($dupe > 0) {
                $db->transRollback();
                return ['status' => false, 'message' => 'Username sudah dipakai akun lain.'];
            }

            $tertulis = $db->table('users')->where('id', $userId)->update(['username' => $new]);
            if ($tertulis === false || $db->transStatus() === false) {
                $db->transRollback();
                log_message('error', '[AuthModel::renameUser] update users gagal: ' . json_encode($db->error()));
                return ['status' => false, 'message' => 'Gagal mengganti username — tidak ada yang berubah.'];
            }

            $ikut = 0;
            if ($db->tableExists('master_guru') && $db->fieldExists('username', 'master_guru')) {
                $ikut += $db->table('master_guru')
                    ->groupStart()->where('id_user', $userId)->orWhere('username', $old)->groupEnd()
                    ->update(['username' => $new]) === false ? 0 : $db->affectedRows();
            }
            if ($db->tableExists('master_siswa') && $db->fieldExists('username', 'master_siswa')) {
                $ikut += $db->table('master_siswa')->where('username', $old)
                    ->update(['username' => $new]) === false ? 0 : $db->affectedRows();
            }
            if ($db->transStatus() === false) {
                $db->transRollback();
                log_message('error', '[AuthModel::renameUser] sinkron master gagal: ' . json_encode($db->error()));
                return ['status' => false, 'message' => 'Gagal menyinkronkan username ke data master — tidak ada yang berubah.'];
            }

            if (! $db->transCommit()) {
                $db->transRollback();
                return ['status' => false, 'message' => 'Gagal menyimpan perubahan username — tidak ada yang berubah.'];
            }

            return [
                'status'   => true,
                'message'  => 'Username "' . $old . '" → "' . $new . '"' . ($ikut > 0 ? ' (+' . $ikut . ' baris master)' : ''),
                'changed'  => true,
                'old'      => $old,
            ];
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', '[AuthModel::renameUser#' . $userId . '] ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal mengganti username — tidak ada yang berubah.'];
        }
    }

    /**
     * Register multiple users
     */
    public function registerAll(array $users, string $group): array
    {
        $inserted = [];

        foreach ($users as $user) {
            $id = $this->register($user, $group);
            $inserted[] = [
                'id' => $id,
                'username' => $user['username']
            ];
        }

        return $inserted;
    }

    /**
     * Login - get user with role information
     */
    public function login(string $username): ?object
    {
        $db = \Config\Database::connect();

        return $db->table('users a')
            ->select('a.*, b.user_id, b.group_id, c.id as role, c.name as role_name, c.description')
            ->join('users_groups b', 'a.id = b.user_id')
            ->join('groups c', 'b.group_id = c.id')
            ->where('a.username', $username)
            ->get()
            ->getRow();
    }

    /**
     * Record failed login attempt
     */
    public function errorLogin(array $data): bool
    {
        $db = \Config\Database::connect();
        return $db->table('login_attempts')->insert($data);
    }

    /**
     * Percobaan login yang gagal untuk satu username.
     *
     * $dalamDetik membatasi ke jendela terkini. Tanpa jendela, seluruh
     * riwayat ikut dihitung: akun yang pernah gagal 3x seumur hidupnya
     * langsung terkunci oleh SATU kegagalan terbaru.
     */
    public function cekErrorLogin(string $username, ?int $dalamDetik = null): array
    {
        $db = \Config\Database::connect();
        $b = $db->table('login_attempts')->where('login', $username);
        if ($dalamDetik !== null && $dalamDetik > 0) {
            $b->where('time >=', time() - $dalamDetik);
        }
        $hasil = $b->orderBy('time', 'ASC')->get();
        if ($hasil === false) {
            log_message('error', '[AuthModel::cekErrorLogin] query gagal: ' . json_encode($db->error()));
            return [];
        }
        return $hasil->getResult();
    }

    /**
     * Reset failed login attempts
     */
    public function resetErrorLogin(string $username): bool
    {
        $db = \Config\Database::connect();
        return $db->table('login_attempts')
            ->where('login', $username)
            ->delete();
    }

    /**
     * Check if student is active
     */
    public function isAktif(string $username): ?object
    {
        $db = \Config\Database::connect();

        return $db->table('master_siswa a')
            ->select('a.id_siswa, b.status')
            ->join('buku_induk b', 'a.id_siswa = b.id_siswa', 'left')
            ->where('a.username', $username)
            ->get()
            ->getRow();
    }

    /**
     * Check username availability
     */
    public function usernameCheck(string $username, ?int $id = null): bool
    {
        $result = $this->where('username', $username)->findAll();

        if ($id === null) {
            return count($result) > 0;
        }

        return count($result) > 0 && $result[0]->id != $id;
    }

    /**
     * Update user data
     */
    public function changeUser(int $id, array $userData): bool
    {
        return $this->update($id, $userData);
    }

    /**
     * Hapus akun beserta keanggotaan grupnya, atomik.
     *
     * Versi lama membungkus writes dengan SET FOREIGN_KEY_CHECKS=0 — tidak
     * ada FK yang merujuk users di skema ini, jadi perintah itu cuma
     * melumpuhkan pemeriksaan pada koneksi yang mungkin dipakai query lain
     * sesudahnya. master_guru.id_user juga ikut dinolkan supaya guru tidak
     * menggantung ke id yang sudah tidak ada.
     */
    public function deleteUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $db = $this->db;
        $db->transBegin();
        try {
            $ada = $db->table('users')->where('id', $id)->get()->getRow();
            if (! $ada) {
                $db->transRollback();
                return false;
            }

            if ($db->tableExists('master_guru') && $db->fieldExists('id_user', 'master_guru')) {
                $db->table('master_guru')->where('id_user', $id)->update(['id_user' => null]);
            }

            $grup = $db->table('users_groups')->where('user_id', $id)->delete();
            $akun = $db->table('users')->where('id', $id)->delete();

            if ($grup === false || $akun === false || $db->transStatus() === false) {
                $db->transRollback();
                log_message('error', '[AuthModel::deleteUser#' . $id . '] gagal: ' . json_encode($db->error()));
                return false;
            }

            if (! $db->transCommit()) {
                $db->transRollback();
                log_message('error', '[AuthModel::deleteUser#' . $id . '] commit gagal — akun dipertahankan');
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', '[AuthModel::deleteUser#' . $id . '] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all user groups
     */
    public function groups(): array
    {
        $db = \Config\Database::connect();
        return $db->table('users_groups')->get()->getResult();
    }

    /**
     * Get groups for specific user
     */
    public function getUsersGroups(int $userId): array
    {
        $db = \Config\Database::connect();

        return $db->table('users_groups a')
            ->join('groups b', 'a.group_id = b.id')
            ->where('a.user_id', $userId)
            ->get()
            ->getResult();
    }

    /**
     * Check user level by username
     */
    public function checkLevel(string $username): ?object
    {
        $db = \Config\Database::connect();

        return $db->table('users a')
            ->select('a.id, a.username, b.group_id')
            ->join('users_groups b', 'b.user_id = a.id')
            ->where('a.username', $username)
            ->get()
            ->getRow();
    }

    /**
     * Change user password
     */
    public function changePassword(string $username, string $oldPassword, string $newPassword): bool
    {
        $user = $this->where('username', $username)->first();

        if ($user && password_verify($oldPassword, $user->password)) {
            return $this->where('username', $username)
                ->set('password', password_hash($newPassword, PASSWORD_BCRYPT))
                ->update();
        }

        return false;
    }

    /**
     * Alias snake_case untuk changePassword (dipakai Userguru::editlogin & Useradmin::change_password)
     */
    public function change_password(string $username, string $oldPassword, string $newPassword): bool
    {
        return $this->changePassword($username, $oldPassword, $newPassword);
    }
}
