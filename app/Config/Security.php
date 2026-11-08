<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * CSRF Protection Method
     * 'cookie' lebih aman untuk SPA/AJAX.
     */
    public string $csrfProtection = 'cookie';

    /**
     * Randomize token untuk keamanan ekstra.
     * Set false agar AJAX dengan token statis tetap bekerja.
     */
    public bool $tokenRandomize = false;

    public string $tokenName   = 'csrf_test_name';
    public string $headerName  = 'X-CSRF-TOKEN';
    public string $cookieName  = 'csrf_cookie_name';

    /**
     * Token berlaku 24 jam, sepadan dengan umur cache halaman PWA
     * (public/sw.js ONE_DAY_MS). Token lebih pendek dari cache membuat
     * halaman yang disajikan service worker membawa token kedaluwarsa.
     */
    public int $expires = 86400;

    /**
     * Jangan regenerate tiap submit agar AJAX multi-request tidak error.
     * Set true di production jika ingin lebih ketat (requires frontend update token).
     */
    public bool $regenerate = false;

    /** Redirect ke halaman sebelumnya jika CSRF gagal (production only). */
    public bool $redirect = (ENVIRONMENT === 'production');

    /**
     * CSRF Exclude URIs
     * Hanya endpoint tanpa session (login) + cron/API key-protected.
     * Endpoint presensi terautentikasi (check-in/out, izin, koreksi, QR,
     * register-face, update-profile, kepsek approve/reject) WAJIB kirim
     * token CSRF via header X-CSRF-TOKEN atau body csrf_test_name.
     *
     * @var list<string>
     */
    public array $excludeURIs = [
        // Login endpoint - excluded because CSRF cookie can expire/mismatch
        // between page render and form submit, locking users out permanently
        'auth/login',
        'presensi/auth',
        'presensi/google-callback',
        // Presensi Siswa QR scan endpoint (publik / kiosk terminal)
        'guru/presensi-siswa/scan',
        'presensi-siswa/scan',
        // Cron endpoints (key-protected)
        'cron/.*',
    ];
}
