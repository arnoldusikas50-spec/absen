<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Auth Filter - Check if user is logged in
 * Migrated from CI3 authentication checks
 */
class AuthFilter implements FilterInterface
{
    /**
     * Check authentication before controller method
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');

        // PWA "tetap login" 30 hari: pulihkan session dari cookie remember
        // sebelum memutuskan redirect ke halaman login.
        if (!$session->get('is_login')) {
            \App\Libraries\RememberLogin::restore();
        }

        // Check if user is logged in
        if (!$session->get('is_login')) {
            // Check if it's an AJAX request
            $isAjax = $request instanceof IncomingRequest && $request->isAJAX();

            if ($isAjax) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status' => false,
                        'message' => 'Session expired. Please login again.',
                        'redirect' => site_url('auth')
                    ]);
            }

            // Store intended URL for redirect after login
            $session->set('redirect_url', current_url());

            // Redirect to login page
            return redirect()->to('auth')->with('message', 'Silakan login terlebih dahulu.');
        }

        // Revalidasi ringan: sinkronkan role dari DB (throttle 60 dtk)
        // agar demote/nonaktifkan user segera berlaku tanpa tunggu logout.
        try {
            $lastCheck = (int) ($session->get('role_checked_at') ?? 0);
            if (time() - $lastCheck > 60) {
                $db = \Config\Database::connect();
                $uid = (int) ($session->get('user_id') ?? 0);
                if ($uid > 0 && $db->tableExists('users') && $db->tableExists('users_groups') && $db->tableExists('groups')) {
                    $fresh = $db->table('users a')
                        ->select('a.active, b.group_id, c.name as role_name')
                        ->join('users_groups b', 'a.id = b.user_id', 'left')
                        ->join('groups c', 'b.group_id = c.id', 'left')
                        ->where('a.id', $uid)
                        ->get()->getRow();
                    if (!$fresh || (string) ($fresh->active ?? '') !== '1') {
                        $session->destroy();
                        if ($request instanceof IncomingRequest && $request->isAJAX()) {
                            return service('response')->setStatusCode(401)->setJSON([
                                'status'   => false,
                                'message'  => 'Akun dinonaktifkan. Silakan login ulang.',
                                'redirect' => site_url('auth'),
                            ]);
                        }
                        return redirect()->to('auth')->with('message', 'Akun dinonaktifkan.');
                    }
                    if (!empty($fresh->role_name)) {
                        $session->set('role_name', $fresh->role_name);
                    }
                    if (!empty($fresh->group_id)) {
                        $session->set('role', $fresh->group_id);
                    }
                }
                $session->set('role_checked_at', time());
            }
        } catch (\Throwable $e) {
            // DB down → jangan kunci user keluar; pakai session yang ada (fail-open
            // untuk ketersediaan, controller tetap cek role sebagai lapis kedua).
            log_message('error', 'AuthFilter revalidasi gagal: ' . $e->getMessage());
        }

        // Check role-based access if arguments provided
        // (alias pengawas == panitia; fallback id grup bila role_name usang)
        if (!empty($arguments)) {
            $norm = static fn($v): string => ($t = strtolower((string) $v)) === 'pengawas' ? 'panitia' : $t;
            $userRole = $norm($session->get('role_name') ?? '');
            $allowedRoles = array_map($norm, (array) $arguments);

            $roleId = (string) ($session->get('role') ?? '');
            $idToName = ['1' => 'admin', '2' => 'guru', '3' => 'siswa', '4' => 'panitia'];
            $idAllowed = isset($idToName[$roleId]) && in_array($idToName[$roleId], $allowedRoles, true);

            if (!in_array($userRole, $allowedRoles, true) && !$idAllowed) {
                $isAjax = $request instanceof IncomingRequest && $request->isAJAX();
                if ($isAjax) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'status' => false,
                            'message' => 'Anda tidak memiliki akses ke halaman ini.'
                        ]);
                }

                return redirect()->to('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        return $request;
    }

    /**
     * After controller method (not used)
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
        return $response;
    }
}
