<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Admin Filter - Restrict access to admin only
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        $isAjax = $request instanceof IncomingRequest && $request->isAJAX();

        // First check if logged in
        if (!$session->get('is_login')) {
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status' => false,
                        'message' => 'Session expired. Please login again.'
                    ]);
            }
            return redirect()->to('auth');
        }

        // Check if admin (fail-closed: terima argumen role bila diberikan,
        // default hanya admin; banding longgar agar int 1 tetap lolos)
        $allowed = $arguments ?: ['admin'];
        $roleName = strtolower((string) ($session->get('role_name') ?? ''));
        $roleId = (string) $session->get('role');
        $allowedNorm = array_map(static fn($a) => strtolower((string) $a), (array) $allowed);
        // Petakan id grup -> nama agar 'auth:admin' juga lolos bila role_name usang
        $idToName = ['1' => 'admin', '2' => 'guru', '3' => 'siswa', '4' => 'panitia'];
        $effective = $roleName;
        if ($effective === '' && isset($idToName[$roleId])) {
            $effective = $idToName[$roleId];
        }
        // Alias: pengawas == panitia (nama grup DB vs kode)
        if (in_array($effective, ['pengawas'], true)) {
            $effective = 'panitia';
        }
        $allowedNorm = array_map(
            static fn($a) => $a === 'pengawas' ? 'panitia' : $a,
            $allowedNorm
        );
        $idAllowed = in_array($roleId, ['1', '2', '3', '4'], true)
            && in_array($idToName[$roleId] ?? '', $allowedNorm, true);
        if (!in_array($effective, $allowedNorm, true) && !$idAllowed) {
            if ($isAjax) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'status' => false,
                        'message' => 'Akses ditolak. Hanya admin yang dapat mengakses.'
                    ]);
            }
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
