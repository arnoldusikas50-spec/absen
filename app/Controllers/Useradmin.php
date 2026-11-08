<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\DashboardModel;
use App\Models\AuthModel;
use App\Models\MasterModel;

class Useradmin extends BaseController
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
        if (!$this->isAdmin())
            return redirect()->to('dashboard');

        $user = $this->getUser();
        $data = [
            'user' => $user,
            'judul' => 'Admin Management',
            'subjudul' => 'Data Admin',
            'profile' => null,
            'setting' => $this->dashboard->getSetting(),
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $this->dashboard->getTahunActive(),
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive(),
            'admins' => $this->users->getAllAdmin(),
            'is_admin' => $this->isAdmin()
        ];

        return view('users/admin/data', $data);
    }

    public function data()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        return $this->response->setJSON($this->users->getAllAdmin());
    }

    public function create()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $rules = [
            'username' => 'required|min_length[4]|max_length[30]|alpha_dash',
            'first_name' => 'required|max_length[50]',
            'email' => 'permit_empty|valid_email|max_length[100]',
            'password' => 'trim|min_length[6]|max_length[72]|required',
            'confirm_password' => 'trim|matches[password]|required'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ]);
        } else {
            $username = trim((string) $this->request->getPost('username'));
            $password = (string) $this->request->getPost('password');
            $first_name = trim((string) $this->request->getPost('first_name'));
            $last_name = trim((string) $this->request->getPost('last_name'));
            $email = trim((string) $this->request->getPost('email'));

            if ($this->auth->isExist($username)) {
                return $this->response->setJSON([
                    'status' => false,
                    'msg' => 'Username tidak tersedia (sudah digunakan).'
                ]);
            } else {
                $user = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email !== '' ? $email : null,
                ];
                $id_user = $this->auth->register($user, '1');
                if ($id_user <= 0) {
                    return $this->response->setJSON([
                        'status' => false,
                        'msg' => 'Akun gagal dibuat (username baru saja dipakai atau basis data menolak tulisannya).',
                    ]);
                }
                return $this->response->setJSON([
                    'status' => true,
                    'msg' => 'User berhasil dibuat.'
                ]);
            }
        }
    }

    public function edit($id)
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');

        $id = (int) $id;
        // Non-admin hanya boleh membuka halaman edit miliknya sendiri
        if (!$this->isAdmin() && $id !== (int) $this->getUserId()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $target = $this->auth->user($id);
        if (!$target) {
            return redirect()->to('useradmin')->with('error', 'User tidak ditemukan');
        }

        $user = $this->getUser();
        $data = [
            'user' => $user,
            'judul' => 'Administrator',
            'subjudul' => 'Edit Data Admin',
            'users' => $target,
            'groups' => $this->auth->groups(),
            'level' => $this->isAdmin() ? '1' : '2',
            'setting' => $this->dashboard->getSetting(),
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $this->dashboard->getTahunActive(),
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $this->dashboard->getSemesterActive(),
            'is_admin' => $this->isAdmin()
        ];

        return view('users/admin/edit', $data);
    }

    public function edit_info()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        if (!$this->validate(['first_name' => 'required|max_length[50]'])) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ]);
        } else {
            $id = (int) $this->request->getPost('id');
            if ($id <= 0 || !$this->auth->user($id)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'User tidak ditemukan']);
            }
            $input = [
                'first_name' => trim((string) $this->request->getPost('first_name')),
                'last_name' => trim((string) $this->request->getPost('last_name')),
                'email' => trim((string) $this->request->getPost('email')) ?: null,
                'jabatan' => mb_substr(trim((string) $this->request->getPost('jabatan')), 0, 50) ?: null,
            ];
            $update = $this->master->updateData('users', $input, 'id', $id);
            return $this->response->setJSON(['status' => (bool) $update]);
        }
    }

    public function edit_status()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        if (!$this->validate(['status' => 'required|in_list[0,1]'])) {
            return $this->response->setJSON(['status' => false, 'errors' => $this->validator->getErrors()]);
        }
        $id = (int) $this->request->getPost('id');
        $status = (int) $this->request->getPost('status');
        if ($id <= 0 || !$this->auth->user($id)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'User tidak ditemukan']);
        }
        // Cegah admin menonaktifkan akunnya sendiri
        if ($id === (int) $this->getUserId() && $status === 0) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Tidak bisa menonaktifkan akun sendiri']);
        }
        $update = $this->master->updateData('users', ['active' => $status], 'id', $id);
        return $this->response->setJSON(['status' => (bool) $update]);
    }

    public function delete($id)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        // Hanya izinkan POST (route GET sudah dihapus)
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => false, 'msg' => 'Metode tidak diizinkan']);
        }
        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'msg' => 'ID tidak valid']);
        }
        // Cegah hapus akun sendiri & superadmin utama (id 1)
        if ($id === (int) $this->getUserId()) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Tidak bisa menghapus akun sendiri']);
        }
        if ($id === 1) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akun superadmin utama tidak bisa dihapus']);
        }
        if (!$this->auth->user($id)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'User tidak ditemukan']);
        }
        $status = $this->auth->deleteUser($id);
        return $this->response->setJSON(['status' => (bool) $status, 'msg' => $status ? 'Admin dihapus' : 'Gagal menghapus']);
    }

    public function uploadFile($id_user)
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $id_user = (int) $id_user;
        if (!$this->isAdmin() && $id_user !== (int) $this->getUserId()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        // Batas ukuran & tipe ditegakkan di server: periksa nama ekstensi saja
        // membiarkan .php yang berganti nama jadi .jpg masuk ke webroot.
        $rules = [
            'foto' => [
                'label' => 'Foto profil',
                'rules' => 'uploaded[foto]|is_image[foto]|mime_in[foto,image/jpg,image/jpeg,image/gif,image/png,image/webp]|max_size[foto,2048]',
            ],
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $file = $this->request->getFile('foto');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada file yang diunggah.']);
        }
        if ($file->hasMoved()) {
            return $this->response->setJSON(['status' => false, 'message' => 'File sudah pernah diproses — muat ulang halaman lalu unggah lagi.']);
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format file tidak diizinkan. Gunakan JPG, PNG, WEBP, atau GIF.']);
        }

        // Kepemilikan baris diperiksa SEBELUM move: dulu file sudah menimpa foto
        // lama baru diketahui usernya tidak ada, sehingga foto lama hilang tanpa
        // pernah ada path yang tersimpan di database.
        $target = $this->auth->user($id_user);
        if (!$target) {
            return $this->response->setJSON(['status' => false, 'message' => 'User tidak ditemukan — foto tidak disimpan.']);
        }

        $targetDir = FCPATH . 'uploads/profiles/';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            log_message('error', '[Useradmin::uploadFile] folder uploads/profiles gagal dibuat');
            return $this->response->setJSON(['status' => false, 'message' => 'Foto gagal disimpan di server — coba lagi atau lapor ke pengembang.']);
        }

        $newName = 'foto_' . $id_user . '.' . $ext;
        if (! $file->move($targetDir, $newName, true)) {
            log_message('error', '[Useradmin::uploadFile] move gagal untuk user#' . $id_user . ': ' . $file->getError());
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Foto gagal disimpan di server — coba lagi, dan bila berulang lapor ke pengembang.',
            ]);
        }
        $src = 'uploads/profiles/' . $newName;

        $db = \Config\Database::connect();
        $tersimpan = $db->table('users')->where('id', $id_user)->update(['foto' => $src]);
        if ($tersimpan === false) {
            log_message('error', '[Useradmin::uploadFile] update kolom foto users#' . $id_user . ' gagal');
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File foto sudah tersimpan, tapi data user gagal diperbarui — ulangi unggah foto.',
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'src' => base_url($src) . '?v=' . time(),
            'filename' => $newName,
            'message' => 'Foto profil berhasil diperbarui.'
        ]);
    }

    public function deleteFile()
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $idUser = (int) $this->request->getPost('id_user');
        if (!$this->isAdmin() && $idUser !== (int) $this->getUserId()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if ($idUser <= 0 || !$this->auth->user($idUser)) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID User tidak valid.']);
        }

        $src = (string) $this->request->getPost('src');
        $rel = $this->resolveProfilePath($src);
        if ($rel !== null && @unlink(FCPATH . $rel) === false && is_file(FCPATH . $rel)) {
            // File masih ada di disk: jangan laporkan penghapusan yang tidak terjadi.
            log_message('error', '[Useradmin::deleteFile] unlink gagal untuk ' . $rel);
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File foto tidak bisa dihapus dari server — hapus kolomnya saja bila perlu.',
            ]);
        }

        $db = \Config\Database::connect();
        $tersimpan = $db->table('users')->where('id', $idUser)->update(['foto' => null]);
        if ($tersimpan === false) {
            log_message('error', '[Useradmin::deleteFile] update kolom foto users#' . $idUser . ' gagal');
            return $this->response->setJSON(['status' => false, 'message' => 'Data gagal diperbarui — periksa log aplikasi.']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Foto profil berhasil dihapus.']);
    }

    /**
     * Normalisasi src foto profil: wajib file nyata di dalam uploads/profiles/.
     * Tanpa batas ini, src='../../.env' diikat ke FCPATH sehingga pengguna ter-autentikasi
     * bisa menghapus file di luar webroot (pola yang sama sudah dipakai Dataguru::
     * resolveDeletablePath dan Settings::resolveMediaPath).
     */
    private function resolveProfilePath(?string $src): ?string
    {
        $rel = ltrim(str_replace(base_url(), '', (string) $src), '/');
        $rel = str_replace('\\', '/', $rel);
        if ($rel === '') return null;
        if (preg_match('#^https?://#i', $rel)) return null;
        if (strpos($rel, '..') !== false) return null;
        if (strpos($rel, 'uploads/profiles/') !== 0) return null;
        $abs = realpath(FCPATH . $rel);
        $root = realpath(FCPATH . 'uploads/profiles');
        if ($abs === false || $root === false || strpos($abs, $root) !== 0 || !is_file($abs)) {
            return null;
        }
        // FCPATH bisa berakhir '/' (Linux) atau '\' (Windows): potong dari akar
        // yang sudah dibersihkan, lalu paksa separator maju agar sama dengan
        // nilai yang tersimpan di kolom foto.
        $rel = substr($abs, strlen(rtrim(FCPATH, '/\\')) + 1);
        $rel = str_replace('\\', '/', $rel);
        // Jaga irisan: hasil harus tetap berada di bawah uploads/profiles/,
        // kalau tidak (mis. salah potong) lebih baik tolak daripada unlink
        // file yang salah.
        return strpos($rel, 'uploads/profiles/') === 0 ? $rel : null;
    }

    public function change_password()
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        // Username selalu divalidasi; password hanya wajib bila ingin diganti
        // (kolom password dikosongkan = hanya ganti username)
        $wantPassword = trim((string) $this->request->getPost('new')) !== ''
            || trim((string) $this->request->getPost('new_confirm')) !== ''
            || trim((string) $this->request->getPost('old')) !== '';
        $rules = [
            'username' => 'required|min_length[4]|max_length[30]|regex_match[/^[A-Za-z0-9_.-]+$/]',
        ];
        if ($wantPassword) {
            $rules['old'] = 'required';
            $rules['new'] = 'required|min_length[6]|max_length[72]';
            $rules['new_confirm'] = 'required|matches[new]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $id = (int) $this->request->getPost('id');
        $old = $this->request->getPost('old');
        $new = $this->request->getPost('new');
        $newUsername = trim((string) $this->request->getPost('username'));

        // Non-admin hanya boleh mengganti password akunnya sendiri
        if (!$this->isAdmin() && $id !== (int) $this->getUserId()) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $user = $this->auth->user($id);
        if (!$user)
            return $this->response->setJSON(['status' => false, 'msg' => 'User not found']);

        // Verifikasi password lama dilakukan SEBELUM ada tulis-menulis.
        // Urutan lama (ganti password, lalu validasi rename) meninggalkan akun
        // dengan password baru ketika rename ditolak.
        if ($wantPassword && ! password_verify((string) $old, (string) $user->password)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Password lama salah.']);
        }

        $msg = $wantPassword ? 'Password berhasil diubah' : 'Username berhasil diperbarui';
        $renamed = false;
        // Ganti username bila diubah (form menyediakan field username)
        if ($newUsername !== '' && strcasecmp($newUsername, (string) $user->username) !== 0) {
            // users + baris master tersambung dalam satu transaksi: join
            // roster memakai username, rename separuh jalan membuat guru/siswa
            // kehilangan akunnya dan "aktifkan" lalu membuat akun kedua.
            $hasilRename = $this->auth->renameUser((int) $user->id, $newUsername);
            if (! $hasilRename['status']) {
                return $this->response->setJSON(['status' => false, 'msg' => $hasilRename['message']]);
            }
            $renamed = (bool) ($hasilRename['changed'] ?? false);
            if ($renamed) {
                if ((int) session()->get('user_id') === (int) $user->id) {
                    session()->set('username', $newUsername);
                }
                $msg = $wantPassword ? 'Password & username berhasil diubah' : 'Username berhasil diubah';
            }
        }

        $usernameLogin = $renamed ? $newUsername : (string) $user->username;
        if ($wantPassword && ! $this->auth->change_password($usernameLogin, (string) $old, (string) $new)) {
            return $this->response->setJSON([
                'status' => false,
                'msg' => $renamed
                    ? 'Username sudah berganti, tapi password gagal diubah — ulangi ubah password.'
                    : 'Gagal mengubah password (password lama salah?)'
            ]);
        }

        return $this->response->setJSON(['status' => true, 'msg' => $msg]);
    }
}
