<?php

namespace App\Controllers;

use App\Models\AuthModel;
use App\Models\LogModel;
use App\Models\SettingsModel;

/**
 * Auth Controller - Migrated from CI3 Auth controller
 * Handles user authentication (login, logout, registration)
 */
class Auth extends BaseController
{
    protected LogModel $logModel;
    protected SettingsModel $settingsModel;

    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->logModel = new LogModel();
        $this->settingsModel = new SettingsModel();
    }

    /**
     * Halaman login utama — semua role (Admin, Guru, Siswa)
     * Route: GET /auth
     */
    public function index()
    {
        // Jika sudah login, langsung arahkan ke dashboard
        if ($this->session->get('is_login')) {
            return redirect()->to('dashboard');
        }

        $data['setting'] = $this->settingsModel->getSetting();
        return view('backend/login', $data);
    }

    /**
     * Halaman login siswa — redirect ke halaman login utama
     * Route: GET /auth/siswa
     */
    public function siswa()
    {
        return redirect()->to('auth');
    }

    /**
     * Render login page based on variant
     */
    private function renderLoginPage(string $variant = 'staff')
    {
        helper('init');

        // Check if setup is complete
        if (!function_exists('database_connected') || !database_connected() || is_database_empty() || is_setting_empty()) {
            return redirect()->to('install');
        }

        // Already logged in - redirect
        if ($this->session->get('is_login')) {
            $user = $this->authModel->user($this->session->get('user_id'));
            $redirectUrl = $this->determineRedirect($user, false);
            return redirect()->to($redirectUrl);
        }

        $setting = $this->settingsModel->getSetting();

        $data = [
            'setting' => $setting,
            'identity' => [
                'name' => 'username',
                'id' => 'identity',
                'type' => 'text',
                'placeholder' => 'Username',
                'autofocus' => 'autofocus',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ],
            'username' => [
                'name' => 'username',
                'id' => 'identity',
                'type' => 'text',
                'placeholder' => 'Username',
                'autofocus' => 'autofocus',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ],
            'password' => [
                'name' => 'password',
                'id' => 'password',
                'type' => 'password',
                'placeholder' => 'Password',
                'class' => 'form-control',
            ],
            'message' => session()->getFlashdata('message') ?? '',
            'login_variant' => $variant,
            'show_mode_selector' => false,
            'cbt_only_default' => '0',
            'login_title' => 'PRESENSI & JURNAL',
        ];

        // Use appropriate view based on variant
        if ($variant !== 'siswa') {
            return view('auth/login', $data);
        }

        return view('auth/index', $data);
    }

    /**
     * Handle login POST request
     */
    public function login()
    {

        try {
            $success = false;
            $message = '';
            $role = '0';
            $dataUser = null;
            $errors = [];

            $cbtOnly      = $this->request->getPost('cbt-only') === '1';
            $redirectUrl  = site_url('dashboard');

            if (strtolower($this->request->getMethod()) === 'post') {
                // Validation — tidak perlu login_variant lagi
                $rules = [
                    'username' => 'required|min_length[3]|max_length[50]',
                    'password' => 'required|min_length[4]|max_length[50]',
                ];

                if (!$this->validate($rules)) {
                    $validation = \Config\Services::validation();
                    $message = 'Username atau Password salah!';
                    $errors  = [
                        'username' => $validation->getError('username'),
                        'password' => $validation->getError('password'),
                    ];
                    return $this->respond(false, $message, null, $errors);
                }

                $username = (string) $this->request->getPost('username');
                $password = (string) $this->request->getPost('password');

                // Check login attempts — hanya dalam jendela 5 menit terakhir
                $detikKunci = 300;
                $percobaan = $this->authModel->cekErrorLogin($username, $detikKunci);
                if (count($percobaan) >= 3) {
                    $message = 'Anda sudah 3x melakukan percobaan login. Silakan tunggu 5 menit atau hubungi Administrator.';
                    return $this->respond(false, $message);
                }

                // Get user
                $user = $this->authModel->login($username);

                if ($user) {
                    $roleName = strtolower($user->role_name ?? '');

                    // Cek apakah siswa aktif (hanya untuk role siswa)
                    $dataUser = $this->authModel->isAktif($username);
                    $isSiswa  = $dataUser !== null;

                    if ($isSiswa && isset($dataUser->status) && $dataUser->status !== '1') {
                        return $this->respond(false, 'Akun Anda dinonaktifkan!');
                    }

                    if ($user->active === '1') {
                        // Verify password
                        if (password_verify($password, $user->password)) {
                            $role = $user->role;

                            // Set session data
                            $idGuru = 0;
                            try {
                                $dbConn = \Config\Database::connect();
                                if ($dbConn->tableExists('master_guru')) {
                                    $guruRow = $dbConn->table('master_guru')->where('id_user', $user->id)->get()->getRow();
                                    if ($guruRow && !empty($guruRow->id_guru)) {
                                        $idGuru = (int) $guruRow->id_guru;
                                    }
                                }
                            } catch (\Throwable $e) {}

                            // Cegah session fixation: pakai ID sesi baru
                            // sebelum data login ditulis.
                            $this->session->regenerate(true);

                            $this->session->set([
                                'user_id'     => $user->id,
                                'id_guru'     => $idGuru,
                                'username'    => $user->username,
                                'nama'        => $user->first_name . ($user->last_name ? ' ' . $user->last_name : ''),
                                'email'       => $user->email,
                                'role'        => $user->group_id,
                                'role_name'   => $user->role_name,
                                'foto'        => $user->foto,
                                'school_uuid' => $user->company ?? null,
                                'is_login'    => true,
                                'login_variant' => $roleName === 'siswa' ? 'siswa' : 'staff',
                            ]);

                            $this->authModel->resetErrorLogin($username);
                            $message = 'Login berhasil';
                            $success = true;
                            $redirectUrl = $this->determineRedirect($user, $cbtOnly);

                            // PWA "tetap login" 30 hari — hanya guru & siswa
                            // (admin/panitia dikecualikan demi keamanan)
                            if (in_array($roleName, ['guru', 'siswa'], true)) {
                                \App\Libraries\RememberLogin::issue((int) $user->id);
                            }

                            // Log with explicit userId since session might not be committed yet
                            $this->logModel->saveLog(1, 'Login', (int) $user->id);
                        } else {
                            // Wrong password
                            $this->authModel->errorLogin([
                                'ip_address' => $this->request->getIPAddress(),
                                'login' => $username,
                                'time' => time()
                            ]);
                            $message = 'Username atau Password salah!';
                        }
                    } else {
                        $message = 'Akun Anda dinonaktifkan!';
                    }
                } else {
                    $message = 'Akun tidak terdaftar!';
                }
            }

            $respData = [
                'role' => $role,
                'user' => $dataUser,
                'url' => 'dashboard',
                'redirect' => $redirectUrl
            ];

            return $this->respond($success, $message, $respData, $errors);

        } catch (\Throwable $th) {
            log_message('error', '[Auth::login] ' . $th->getMessage() . ' di ' . $th->getFile() . ':' . $th->getLine());
            return $this->respond(false, 'Terjadi kesalahan pada server. Silakan coba lagi.');
        }
    }

    /**
     * Determine redirect URL based on user role
     */
    private function determineRedirect(?object $user, bool $cbtOnly): string
    {
        $default = site_url('dashboard');

        if (!$user) {
            return $default;
        }

        // Admin Sekolah redirect
        if (!empty($user->company)) {
            return site_url('sekolahpanel');
        }

        $roleName = strtolower($user->role_name ?? '');

        if ($cbtOnly && $roleName === 'siswa') {
            return site_url('dashboard');
        }

        if ($roleName === 'siswa') {
            return site_url('siswa/presensi');
        }

        // Guru → halaman presensi (absensi harian)
        if ($roleName === 'guru') {
            return site_url('presensi');
        }

        // Admin & Panitia → dashboard
        if (in_array($roleName, ['admin', 'panitia'])) {
            return site_url('dashboard');
        }

        return $default;
    }

    /**
     * Handle logout - redirect ke /auth
     */
    public function logout()
    {
        \App\Libraries\RememberLogin::clear();
        $this->logModel->saveLog(2, 'Logout');
        $this->session->destroy();
        return redirect()->to('auth')->with('success', 'Anda telah berhasil keluar.');
    }
}
