<?php

namespace App\Debug;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bagi hasil untuk halaman error.
 *
 * Browser menerima HTML, sedangkan fetch() di view, aplikasi mobile, dan
 * endpoint api/* menerima JSON dengan bentuk yang sama seperti helper
 * BaseController::respond() — kalau tidak, resp.json() di sisi klien pecah
 * saat server jatuh.
 *
 * Setiap error juga memegang satu kode referensi pendek yang ditulis ke log
 * bersama stack trace-nya, jadi laporan user bisa dicocokkan tanpa menampilkan
 * detail internal ke layar.
 */
final class ErrorResponder
{
    private static ?string $ref = null;

    public static function ref(): string
    {
        return self::$ref ??= substr(bin2hex(random_bytes(3)), 0, 6);
    }

    public static function wantsJson(RequestInterface $request): bool
    {
        if ($request instanceof CLIRequest) {
            return false;
        }

        if ($request->isAJAX()) {
            return true;
        }

        // $uri properti protected; getUri() satu-satunya accessor yang aman
        // untuk IncomingRequest maupun CLIRequest (uri bisa null di CLI).
        $path = (string) ($request->getUri()?->getPath() ?? '');
        if (preg_match('#^/?api/#i', $path) === 1) {
            return true;
        }

        $accept = strtolower($request->getHeaderLine('Accept'));

        // Tanpa header Accept (curl, health check, bot) = mesin, bukan browser.
        if ($accept === '') {
            return true;
        }

        return str_contains($accept, 'json') || ! str_contains($accept, 'text/html');
    }

    /** Bentuk body yang sama dengan BaseController::respond(false, ...). */
    public static function body(string $message, bool $denganRef = false): array
    {
        return [
            'status'  => false,
            'message' => $message,
            'errors'  => null,
            'data'    => null,
            'ref'     => $denganRef ? self::ref() : null,
        ];
    }

    public static function json(ResponseInterface $response, int $status, string $message): ResponseInterface
    {
        return $response
            ->setStatusCode($status)
            ->setContentType('application/json; charset=UTF-8')
            ->setJSON(self::body($message, $status >= 500));
    }
}
