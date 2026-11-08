<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides common functionality for all controllers.
 * absensi by ikasmedia.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var \CodeIgniter\HTTP\IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['form', 'url', 'text', 'init', 'attendance_helper'];

    /**
     * Session service
     *
     * @var \CodeIgniter\Session\Session
     */
    protected $session;

    /**
     * @var \App\Models\AuthModel
     */
    protected \App\Models\AuthModel $authModel;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Kunci timezone aplikasi: seluruh logika presensi (date()/strtotime())
        // harus memakai Asia/Makassar, bukan default php.ini server.
        date_default_timezone_set(config('App')->appTimezone ?? 'Asia/Makassar');

        // Preload session service
        $this->session = service('session');
        try {
            $this->authModel = new \App\Models\AuthModel();
        } catch (\Throwable $e) {
            // Database connection failed - will be handled by controllers
        }
    }

    /**
     * Check if user is logged in
     * Equivalent to CI3's $this->auth->logged_in()
     */
    protected function isLoggedIn(): bool
    {
        return (bool) $this->session->get('is_login');
    }

    /**
     * Get current user ID
     */
    protected function getUserId(): ?int
    {
        // $this->session hanya diisi di initController. Instance yang dibuat
        // langsung (spark command, worker sync, `new Controller()`) punya
        // null di sini, dan panggilan tanpa null-safe membuat seluruh jalur
        // yang hanya ingin menulis audit log melempar exception.
        return $this->session?->get('user_id');
    }

    /**
     * Get current user role
     */
    protected function getUserRole(): ?string
    {
        return $this->session?->get('role_name');
    }

    /**
     * Check if current user is in specified group/role
     * @param string|array $group
     */
    protected function inGroup(string|array $group): bool
    {
        $role = strtolower((string) ($this->session->get('role_name') ?? ''));
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
     */
    protected function isAdmin(): bool
    {
        $role = $this->session->get('role');
        $roleName = strtolower((string) ($this->session->get('role_name') ?? ''));
        return (string) $role === '1' || $roleName === 'admin' || $this->inGroup('admin');
    }

    /**
     * Check if current user is guru (teacher)
     */
    protected function isGuru(): bool
    {
        $role = $this->session->get('role');
        $roleName = strtolower((string) ($this->session->get('role_name') ?? ''));
        return (string) $role === '2' || $roleName === 'guru' || $this->inGroup('guru');
    }

    /**
     * Check if current user is panitia (alias pengawas di tabel groups)
     */
    protected function isPanitia(): bool
    {
        $role = $this->session->get('role');
        $roleName = strtolower((string) ($this->session->get('role_name') ?? ''));
        return (string) $role === '4'
            || in_array($roleName, ['panitia', 'pengawas'], true)
            || $this->inGroup(['panitia', 'pengawas']);
    }

    /**
     * Check if current user is kepala sekolah (termasuk akun guru dengan jabatan/tugas tambahan).
     */
    protected function isKepsek(): bool
    {
        return is_kepsek((int) ($this->session->get('user_id') ?? 0));
    }

    /**
     * Check if current user is siswa
     */
    protected function isSiswa(): bool
    {
        $role = $this->session->get('role');
        $roleName = strtolower((string) ($this->session->get('role_name') ?? ''));
        return (string) $role === '3' || $roleName === 'siswa' || $this->inGroup('siswa');
    }

    /**
     * Fail-closed guard: wajib login. Return null bila lolos,
     * atau response redirect/403 bila ditolak (langsung return di controller).
     */
    protected function requireLogin()
    {
        if ($this->isLoggedIn()) {
            return null;
        }
        if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => false,
                'message' => 'Session expired. Please login again.',
            ]);
        }
        return redirect()->to('auth')->with('message', 'Silakan login terlebih dahulu.');
    }

    /**
     * Fail-closed guard: wajib salah satu role. Contoh:
     *   if ($deny = $this->requireRole('admin')) return $deny;
     *   if ($deny = $this->requireRole(['admin','panitia'])) return $deny;
     */
    protected function requireRole(string|array $roles)
    {
        if ($deny = $this->requireLogin()) {
            return $deny;
        }
        $roles = (array) $roles;
        if ($this->inGroup($roles)) {
            return null;
        }
        // Fallback id grup agar tetap lolos bila role_name usang di session
        $idMap = ['admin' => '1', 'guru' => '2', 'siswa' => '3', 'panitia' => '4', 'pengawas' => '4'];
        $roleId = (string) ($this->session->get('role') ?? '');
        foreach ($roles as $r) {
            $key = strtolower((string) $r);
            if ($key === 'pengawas') {
                $key = 'panitia';
            }
            if (isset($idMap[$key]) && $roleId === $idMap[$key]) {
                return null;
            }
        }
        if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => false,
                'message' => 'Akses ditolak.',
            ]);
        }
        return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
    }

    /**
     * Respond with JSON - equivalent to CI3's respond() method
     */
    protected function respond(bool $status, string $message = '', $data = null, $errors = null, int $httpCode = 200)
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'data' => $data
        ];

        return $this->response
            ->setStatusCode($httpCode)
            ->setContentType('application/json')
            ->setJSON($response);
    }

    /**
     * Get shared assets path (for accessing CI3 assets folder)
     */
    protected function getAssetsPath(): string
    {
        return base_url('../assets/');
    }

    /**
     * Get current logged in user object
     * Equivalent to CI3's $this->auth->user()
     */
    protected function getUser(): ?object
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return null;
        }

        $db = \Config\Database::connect();
        $user = $db->table('users')
            ->where('id', $userId)
            ->get()
            ->getRow();

        if ($user) {
            $user->role = $this->session->get('role');
            $user->role_name = $this->session->get('role_name');
        }

        return $user;
    }

    /**
     * Show error page
     * Equivalent to CI3's show_error()
     */
    protected function showError(string $message, int $statusCode = 500)
    {
        return view('errors/html/error_' . $statusCode, [
            'message' => $message
        ]);
    }

    /**
     * Render view with standard layout
     * Equivalent to CI3's render_view()
     */
    protected function renderView(string $view, array $data = [])
    {
        // Add common data
        $data['base_url'] = base_url();
        $data['user'] = $data['user'] ?? $this->getUser();

        return view($view, $data);
    }

    /**
     * Decode data that might be serialized or JSON
     */
    protected function maybe_unserialize($data)
    {
        if (empty($data)) {
            return [];
        }

        if (is_array($data) || is_object($data)) {
            return $data;
        }

        // Try JSON first
        $decoded = json_decode($data);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Try Serialized
        // Data berasal dari kolom DB; jangan izinkan objek PHP dihidrasi.
        $unserialized = @unserialize($data, ['allowed_classes' => false]);
        if ($unserialized !== false || $data === 'b:0;') {
            return $unserialized;
        }

        return $data;
    }

    /**
     * Get active academic year (TP) and semester (SMT)
     */
    protected function getTpSmt(): object
    {
        $dashboardModel = new \App\Models\DashboardModel();
        return (object) [
            'tp'  => $dashboardModel->getTahunActive(),
            'smt' => $dashboardModel->getSemesterActive()
        ];
    }
}