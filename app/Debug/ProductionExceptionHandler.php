<?php

namespace App\Debug;

use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions as ExceptionsConfig;
use Throwable;

/**
 * Exception handler produksi.
 *
 * CodeIgniter\Debug\ExceptionHandler kelas final, jadi penanganan tambahannya
 * lewat komposisi: request non-browser dijawab di sini, sisanya diteruskan apa
 * adanya. Dua hal yang kurang dari bawaan framework untuk aplikasi ini:
 * respons error untuk klien non-browser memakai bentuk framework (bukan
 * {status,message,errors,data} yang dipakai fetch() dan aplikasi mobile), dan
 * stack trace hanya masuk log tanpa penanda yang bisa dikutip user.
 */
class ProductionExceptionHandler implements ExceptionHandlerInterface
{
    private ExceptionHandler $inner;

    public function __construct(ExceptionsConfig $config)
    {
        $this->inner = new ExceptionHandler($config);
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        $ref = ErrorResponder::ref();

        $this->tautkanLog($exception, $request, $statusCode, $ref);

        if (ErrorResponder::wantsJson($request)) {
            ErrorResponder::json(
                $response,
                $statusCode >= 400 ? $statusCode : 500,
                $this->pesan($exception, $statusCode),
            )->send();

            if (ENVIRONMENT !== 'testing') {
                exit($exitCode);
            }

            return;
        }

        $this->inner->handle($exception, $request, $response, $statusCode, $exitCode);
    }

    /** Pesan untuk user: detail hanya di luar produksi, kode referensi hanya untuk error server. */
    private function pesan(Throwable $exception, int $statusCode): string
    {
        if ($statusCode < 500) {
            return $statusCode === 404
                ? 'Endpoint tidak ditemukan.'
                : 'Permintaan tidak dapat diproses.';
        }

        if (ENVIRONMENT === 'production') {
            return 'Terjadi kesalahan pada server. Sertakan kode referensi '
                . ErrorResponder::ref() . ' saat melapor ke administrator.';
        }

        return $exception->getMessage() . ' (REF ' . ErrorResponder::ref() . ')';
    }

    private function tautkanLog(Throwable $exception, RequestInterface $request, int $statusCode, string $ref): void
    {
        try {
            $path = (string) ($request->getUri()?->getPath() ?? '');

            log_message('error', sprintf(
                '[REF %s] %s %s -> %s %s: %s @ %s:%d',
                $ref,
                $request instanceof CLIRequest ? 'CLI' : (string) $request->getMethod(),
                $path !== '' ? $path : '-',
                $statusCode,
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));
        } catch (Throwable) {
            // Logging tidak boleh menggagalkan halaman error itu sendiri.
        }
    }
}
