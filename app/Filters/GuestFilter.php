<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Guest Filter - Only allow non-authenticated users
 */
class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');

        // PWA "tetap login" 30 hari: pulihkan session dulu bila memungkinkan.
        if (!$session->get('is_login')) {
            \App\Libraries\RememberLogin::restore();
        }

        // If already logged in, redirect to dashboard
        if ($session->get('is_login')) {
            $role = strtolower($session->get('role_name') ?? '');

            // Redirect based on role
            if ($role === 'siswa') {
                return redirect()->to('cbtsiswa/cbt');
            }

            return redirect()->to('dashboard');
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
