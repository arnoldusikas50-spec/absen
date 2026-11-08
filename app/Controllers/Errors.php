<?php

namespace App\Controllers;

use App\Debug\ErrorResponder;
use CodeIgniter\Controller;

/**
 * Penanganan error di luar alur controller biasa (dipakai 404 override).
 *
 * Sengaja tidak memakai BaseController: jalur error tidak boleh ikut
 * menyeret session, model, atau koneksi database yang justru bisa jadi
 * sumber gagalnya request.
 */
class Errors extends Controller
{
    /**
     * $routes->set404Override([self::class, 'notFound'])
     * Status 404 sudah dipasang lebih dulu oleh CodeIgniter::display404errors(),
     * jadi di sini cukup menyusun body-nya saja.
     */
    public function notFound(?string $message = null): string
    {
        $request  = service('request');
        $response = service('response');

        if (ErrorResponder::wantsJson($request)) {
            $response->setContentType('application/json; charset=UTF-8');

            return (string) json_encode(ErrorResponder::body('Endpoint tidak ditemukan.'));
        }

        return (string) view('errors/html/error_404', [
            'message' => ENVIRONMENT !== 'production' ? $message : null,
        ]);
    }
}
