<?php

namespace App\Services;

/**
 * OpenWA (github.com/rmyndharis/OpenWA) menjalankan satu atau lebih sesi
 * WhatsApp di balik REST API. Untuk notifikasi kita hanya butuh tiga hal:
 * discovering sesi yang siap, mengirim teks, dan membaca status sesi.
 *
 * Kontrak yang dipakai di sini (lihat docs/06-api-specification.md OpenWA):
 *   - Semua route di-mount di bawah prefix /api.
 *   - Auth lewat header X-API-Key (TIDAK lewat query string — OpenWA
 *     sengaja menolak key di URL agar tidak bocor ke access log).
 *   - POST /api/sessions/:id/messages/send-text  body {chatId, text}
 *     membalas 201 {messageId, timestamp} begitu WhatsApp MENERIMA
 *     kiriman — bukan saat penerima sampai. 201 != terkirim.
 *   - text dibatasi 4096 karakter (WhatsAppFormatter::MAX_LENGTH).
 *   - Session status lowercase: created|initializing|qr_ready|
 *     authenticating|ready|disconnected|action_required|failed.
 *   - Error: {statusCode, message, error}; `message` bisa string atau
 *     array string (ValidationPipe), jadi selalu dibungkus (array).
 *
 * Gateway ini bersifat unofficial (reverse-engineered client, bukan Meta
 * Cloud API). Semua pengiriman WA di aplikasi ini best-effort: kegagalan
 * tidak boleh menggagalkan operasi bisnis yang memicunya.
 */
class OpenWAService
{
    /** Batas `text` OpenWA. Sama dengan formatter. */
    public const MAX_TEXT = WhatsAppFormatter::MAX_LENGTH;

    /** Batas caption send-document (karakter). WhatsApp memotong di sini. */
    public const MAX_CAPTION = 1024;

    /**
     * Batas ukuran berkas yang dikirim lewat gateway (byte).
     *
     * Isi dikirim sebagai base64 di body JSON, jadi ukuran di kabel jadi
     * ~4/3 ukuran file. 10 MB file ≈ 13 MB base64 — masih aman untuk
     * gateway, tapi file sebesar itu tidak realistis untuk rekap pdf dan
     * hanya akan membuat request timeout.
     */
    public const MAX_DOCUMENT_BYTES = 10 * 1024 * 1024;

    /** Timeout default (detik) per panggilan teks. */
    private const TIMEOUT = 15;

    /**
     * Timeout unggah dokumen (detik).
     *
     * Dokumen dikirim sebagai base64 di body JSON, jadi ukurannya ~4/3
     * file dan gateway butuh waktu untuk meneruskannya ke WhatsApp.
     * Timeout teks (15 dtk) akan memotong unggahan PDF yang wajar.
     */
    private const TIMEOUT_DOCUMENT = 90;

    /** Berapa lama hasil resolusi sesi disimpan di cache (detik). */
    private const SESSION_CACHE_TTL = 60;

    /** Kunci cache untuk id sesi hasil resolusi. */
    private const CACHE_KEY = 'openwa_session_id';

    protected $setting;

    /** Pesan error terakhir yang ramah, untuk ditampilkan ke admin. */
    public string $lastError = '';

    /** HTTP status terakhir, untuk log/test. */
    public int $lastStatus = 0;

    public function __construct(?object $setting = null)
    {
        if ($setting !== null) {
            $this->setting = $setting;

            return;
        }
        $row = (new \App\Models\AttendanceSettingModel())->first();
        $this->setting = $row ?: (object) [];
    }

    // ── konfigurasi ────────────────────────────────────────────────────

    public function baseUrl(): string
    {
        $url = trim((string) ($this->setting->wa_base_url ?? ''));
        if ($url === '') {
            $url = trim((string) ($this->setting->wa_gateway_url ?? ''));
        }

        // OpenWA me-mount API di /api; kirim URL mentahnya apa adanya kalau
        // admin sudah mengetik termasuk /api, jangan dobelkan.
        $url = rtrim($url, '/');
        if ($url !== '' && ! str_ends_with($url, '/api')) {
            $url .= '/api';
        }

        return $url;
    }

    private function apiKey(): string
    {
        return trim((string) ($this->setting->wa_api_key ?? ''));
    }

    /** Nama sesi yang dikonfigurasi (opsional). */
    public function sessionName(): string
    {
        return trim((string) ($this->setting->wa_session_name ?? ''));
    }

    /** Chat id tujuan broadcast: grup, nomor kepsek, atau keduanya. */
    public function broadcastChatId(): string
    {
        $raw = trim((string) ($this->setting->wa_target_number ?? ''));
        if ($raw === '') {
            return '';
        }
        // Sudah berupa WID grup/kontak -> pakai apa adanya.
        if (WhatsAppFormatter::isValidChatId($raw)) {
            return ltrim($raw, '@');
        }
        return WhatsAppFormatter::normalizeNumber($raw);
    }

    /**
     * True bila konfigurasi cukup untuk mencoba mengirim.
     */
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->apiKey() !== '';
    }

    /**
     * Satuan HTTP. Seluruh akses jaringan OpenWA lewat sini, sehingga test
     * bisa overriding satu method dan tetap menguji logika nyata di atasnya
     * (validasi config, normalisasi chat id, resolusi sesi, pemecahan
     * pesan, penerjemahan error).
     *
     * @param  string              $method 'GET' atau 'POST'
     * @param  string              $path   Path relatif terhadap baseUrl(),
     *                                      sudah termasuk prefix /api
     * @param  array<string,mixed>|null $body Body JSON, null untuk GET
     * @param  int              $timeout Override timeout (detik)
     * @return array{status:int, body:array|null, error:string}
     *                                  error non-kosong hanya bila koneksi
     *                                  sendiri gagal (bukan HTTP error)
     */
    protected function request(string $method, string $path, ?array $body = null, int $timeout = self::TIMEOUT): array
    {
        $url = $this->baseUrl() . $path;

        try {
            $options = [
                'headers' => [
                    // Auth WAJIB lewat header: OpenWA sengaja menolak API key
                    // di query string supaya tidak bocor ke access log proxy.
                    'X-API-Key' => $this->apiKey(),
                    'Accept'     => 'application/json',
                ],
                'timeout'     => $timeout,
                // Jangan FAILONERROR: body 4xx/5xx OpenWA memuat alasan yang
                // berguna ditampilkan ke admin (mis. peran API key kurang).
                'http_errors' => false,
            ];
            if ($body !== null) {
                $options['json'] = $body;
            }

            $client = \Config\Services::curlrequest();
            $response = $method === 'GET'
                ? $client->get($url, $options)
                : $client->post($url, $options);
        } catch (\Throwable $e) {
            log_message('error', '[OpenWAService] request gagal: ' . $e->getMessage());
            return ['status' => 0, 'body' => null, 'error' => 'OpenWA tidak dapat dihubungi — periksa log aplikasi.'];
        }

        $status = $response->getStatusCode();
        $decoded = json_decode($response->getBody(), true);

        return [
            'status' => $status,
            'body'   => is_array($decoded) ? $decoded : null,
            'error'  => '',
        ];
    }

    /**
     * Kirim pesan teks ke satu chat.
     *
     * @param  string $chatId Nomor (format bebas) atau WID grup
     * @param  string $html   Pesan HTML Telegram; dikonversi ke markup WA
     * @return bool true bila OpenWA menerima semua potongannya
     */
    public function sendText(string $chatId, string $html): bool
    {
        $this->lastError = '';
        $this->lastStatus = 0;

        if (! $this->isConfigured()) {
            $this->lastError = 'OpenWA belum dikonfigurasi (Base URL / API Key kosong di Pengaturan > Notifikasi).';
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        $wid = $this->resolveChatId($chatId);
        if ($wid === '') {
            $this->lastError = 'Nomor/chat tujuan WhatsApp tidak valid: ' . $chatId;
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        $sessionId = $this->resolveSessionId();
        if ($sessionId === '') {
            $this->lastError = 'Tidak ada sesi WhatsApp yang siap di OpenWA. '
                . 'Buat sesi, scan QR, lalu pastikan statusnya "ready".';
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        $chunks = WhatsAppFormatter::toWhatsApp($html);
        $chunks = is_array($chunks) ? $chunks : [$chunks];

        $sentAll = true;
        foreach ($chunks as $text) {
            if (trim($text) === '') {
                continue;
            }
            if (! $this->postText($sessionId, $wid, $text)) {
                // Potongan berikutnya mungkin masih terkirim sebagian; tetap
                // coba agar pesan tidak hilang total, lalu laporkan gagal.
                $sentAll = false;
            }
        }

        return $sentAll;
    }

    /**
     * Kirim satu berkas (dokumen) ke satu chat.
     *
     * Isi file dikirim sebagai base64 di body JSON, bukan multipart —
     * OpenWA menerima keduanya untuk send-document, dan base64 menghindari
     * kepekaan boundary multipart pada beberapa proxy yang dipakai di
     * sekolah.
     *
     * Field yang dikirim persis yang ada di SendDocumentDto. OpenWA memakai
     * ValidationPipe whitelist+forbidNonWhitelisted, jadi field asing
     * membuat 400 — mis. `linkPreview` yang dipakai sendText TIDAK boleh
     * ikut di sini.
     *
     * @param  string $chatId    Nomor tujuan atau WID grup
     * @param  string $filePath  Path absolut berkas di server
     * @param  string $filename  Nama berkas yang dilihat penerima
     * @param  string $caption   Teks pendek sebelum berkas (opsional)
     * @param  string $mimetype  MIME type, wajib bila isi dikirim base64
     * @return bool true bila OpenWA menerima berkasnya
     */
    public function sendDocument(
        string $chatId,
        string $filePath,
        string $filename = '',
        string $caption = '',
        string $mimetype = 'application/pdf'
    ): bool {
        $this->lastError = '';
        $this->lastStatus = 0;

        if (! $this->isConfigured()) {
            $this->lastError = 'OpenWA belum dikonfigurasi (Base URL / API Key kosong di Pengaturan > Notifikasi).';
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        $wid = $this->resolveChatId($chatId);
        if ($wid === '') {
            $this->lastError = 'Nomor/chat tujuan WhatsApp tidak valid: ' . $chatId;
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        if (! is_file($filePath) || ! is_readable($filePath)) {
            $this->lastError = 'Berkas tidak bisa dibaca: ' . $filePath;
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        $isi = file_get_contents($filePath);
        if ($isi === false || $isi === '') {
            $this->lastError = 'Berkas kosong atau gagal dibaca: ' . $filePath;
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        if (strlen($isi) > self::MAX_DOCUMENT_BYTES) {
            $mb = round(strlen($isi) / 1048576, 1);
            $this->lastError = "Berkas terlalu besar untuk dikirim lewat WhatsApp ({$mb} MB, batas "
                . round(self::MAX_DOCUMENT_BYTES / 1048576) . ' MB).';
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

        $nama = trim($filename) !== '' ? basename(trim($filename)) : basename($filePath);

        $sessionId = $this->resolveSessionId();
        if ($sessionId === '') {
            $this->lastError = 'Tidak ada sesi WhatsApp yang siap di OpenWA. '
                . 'Buat sesi, scan QR, lalu pastikan statusnya "ready".';
            log_message('error', '[OpenWA] ' . $this->lastError);
            return false;
        }

$body = [
            'chatId'   => $wid,
            'base64'   => base64_encode($isi),
            'mimetype' => $mimetype !== '' ? $mimetype : 'application/pdf',
            'filename' => $nama,
        ];
        $ketik = trim($caption);
        if ($ketik !== '') {
            // Caption HTML Telegram dikonversi ke markup WA seperti teks biasa.
            $teks = WhatsAppFormatter::toWhatsApp($ketik, false);
            // toWhatsApp() mengembalikan array saat pesan panjang; untuk
            // caption ambil potongan pertama supaya tidak jadi array ke JSON.
            if (is_array($teks)) {
                $teks = (string) ($teks[0] ?? '');
            }
            $teks = trim((string) $teks);
            if ($teks !== '') {
                if (mb_strlen($teks) > self::MAX_CAPTION) {
                    $teks = mb_substr($teks, 0, self::MAX_CAPTION - 1) . '…';
                }
                $body['caption'] = $teks;
            }
        }

        $res = $this->request(
            'POST',
            '/sessions/' . rawurlencode($sessionId) . '/messages/send-document',
            $body,
            self::TIMEOUT_DOCUMENT
        );

        $this->lastStatus = $res['status'];

        if ($res['error'] !== '') {
            $this->lastError = 'Server tidak bisa menghubungi OpenWA (' . $res['error'] . ')';
            log_message('error', '[OpenWA] koneksi gagal: ' . $res['error']);
            return false;
        }

        // 201 = WhatsApp MENERIMA berkas (bukan sampai ke penerima), sama
        // seperti send-text. 200 disertakan untuk proxy yang menormalkan.
        if ($res['status'] === 201 || $res['status'] === 200) {
            log_message('info', '[OpenWA] berkas "' . $nama . '" terkirim ke ' . $wid . ' (HTTP ' . $res['status'] . ')');
            return true;
        }

        $this->lastError = $this->translateError($res['status'], $res['body'], true);
        log_message('error', '[OpenWA] kirim berkas gagal (HTTP ' . $res['status'] . '): ' . $this->lastError);
        return false;
    }

    /**
     * Satu panggilan send-text. Dipisah agar pesan panjang bisa dipecah
     * tanpa mengulang resolusi sesi.
     */
    private function postText(string $sessionId, string $wid, string $text): bool
    {
        $res = $this->request(
            'POST',
            '/sessions/' . rawurlencode($sessionId) . '/messages/send-text',
            [
                // Hanya field yang ada di SendTextMessageDto. OpenWA memakai
                // ValidationPipe whitelist+forbidNonWhitelisted, jadi field
                // asing membuat 400.
                'chatId'      => $wid,
                'text'        => $text,
                'linkPreview' => false,
            ]
        );

        $this->lastStatus = $res['status'];

        if ($res['error'] !== '') {
            $this->lastError = 'Server tidak bisa menghubungi OpenWA (' . $res['error'] . ')';
            log_message('error', '[OpenWA] koneksi gagal: ' . $res['error']);
            return false;
        }

        // 201 = diterima WhatsApp (bukan terkirim ke penerima); 200 disertakan
        // untuk proxy yang menormalkan status.
        if ($res['status'] === 201 || $res['status'] === 200) {
            log_message('info', '[OpenWA] terkirim ke ' . $wid . ' (HTTP ' . $res['status'] . ')');
            return true;
        }

        $this->lastError = $this->translateError($res['status'], $res['body']);
        log_message('error', '[OpenWA] kirim gagal (HTTP ' . $res['status'] . '): ' . $this->lastError);
        return false;
    }

    // ── sesi ───────────────────────────────────────────────────────────

    /**
     * Id sesi yang akan dipakai untuk kirim.
     *
     * Urutan:
     *   1. Cache (60 detik) — resolusi mahal dan sesi jarang berubah.
     *   2. Sesi bernama yang dikonfigurasi, harus status "ready".
     *   3. Fallback: sesi "ready" pertama hasil GET /sessions.
     *   4. Cache kosong — sesi tidak ada yang siap.
     *
     * @return string '' bila tidak ada sesi siap
     */
    public function resolveSessionId(): string
    {
        $cached = $this->cacheGet(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $sessions = $this->listSessions();
        if ($sessions === null) {
            return '';
        }

        $name = $this->sessionName();
        if ($name !== '') {
            foreach ($sessions as $s) {
                if (($s['name'] ?? '') === $name && ($s['status'] ?? '') === 'ready') {
                    return $this->cacheSet((string) $s['id']);
                }
            }
        }

        foreach ($sessions as $s) {
            if (($s['status'] ?? '') === 'ready' && ! empty($s['id'])) {
                return $this->cacheSet((string) $s['id']);
            }
        }

        // Cache negatif singkat supaya halaman admin yang dibuka berulang
        // tidak memukul gateway tiap render.
        return $this->cacheSet('');
    }

    /**
     * Daftar sesi mentah dari gateway.
     *
     * @return array<int,array>|null null bila gateway tidak bisa dihubungi
     */
    public function listSessions(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $res = $this->request('GET', '/sessions');
        $this->lastStatus = $res['status'];

        if ($res['error'] !== '') {
            $this->lastError = 'Tidak bisa menghubungi OpenWA (' . $res['error'] . ')';
            log_message('error', '[OpenWA] listSessions gagal: ' . $res['error']);
            return null;
        }

        if ($res['status'] !== 200) {
            $this->lastError = $this->translateError($res['status'], $res['body']);
            return null;
        }

        // OpenWA tidak memakai envelope; list route mengembalikan array polos.
        return is_array($res['body']) ? $res['body'] : [];
    }

    /**
     * Ringkasan status untuk UI admin.
     *
     * @return array{ok:bool,base_url:string,session_name:string,sessions:array,error:string}
     */
    public function getStatus(): array
    {
        $out = [
            'ok'           => false,
            'base_url'     => $this->baseUrl(),
            'session_name' => $this->sessionName(),
            'sessions'     => [],
            'error'        => '',
        ];

        if (! $this->isConfigured()) {
            $out['error'] = 'Base URL atau API Key belum diisi.';
            return $out;
        }

        $sessions = $this->listSessions();
        if ($sessions === null) {
            $out['error'] = $this->lastError !== '' ? $this->lastError : 'Gateway tidak bisa dihubungi.';
            return $out;
        }

        $out['sessions'] = array_map(static fn($s) => [
            'id'          => (string) ($s['id'] ?? ''),
            'name'        => (string) ($s['name'] ?? ''),
            'status'      => (string) ($s['status'] ?? ''),
            'phone'       => (string) ($s['phone'] ?? ''),
            'push_name'   => (string) ($s['pushName'] ?? ''),
            'last_error'  => (string) ($s['lastError'] ?? ''),
            'restriction' => $s['restriction'] ?? null,
        ], $sessions);

        $ready = array_values(array_filter($out['sessions'], static fn($s) => $s['status'] === 'ready'));
        $out['ok'] = $ready !== [];
        if (! $out['ok']) {
            $out['error'] = 'Belum ada sesi WhatsApp berstatus "ready" di gateway.';
        }

        return $out;
    }

    // ── utilitas ───────────────────────────────────────────────────────

    /**
     * Terima nomor bebas ("0812…", "+62812…", "62812…") maupun WID grup.
     */
    private function resolveChatId(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (WhatsAppFormatter::isValidChatId($raw)) {
            return ltrim($raw, '@');
        }
        return WhatsAppFormatter::normalizeNumber($raw);
    }

    private function cacheGet(string $key)
    {
        try {
            return \Config\Services::cache()->get($key);
        } catch (\Throwable $e) {
            return null; // cache tidak wajib untuk berfungsi
        }
    }

    private function cacheSet(string $value): string
    {
        try {
            \Config\Services::cache()->save(self::CACHE_KEY, $value, self::SESSION_CACHE_TTL);
        } catch (\Throwable $e) {
            // diabaikan: hanya optimisation
        }
        return $value;
    }

    /** Buang cache sesi — dipakai setelah admin mengubah konfigurasi. */
    public function flushCache(): void
    {
        try {
            \Config\Services::cache()->delete(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // diabaikan
        }
    }

    /**
     * Terjemahkan respons error OpenWA menjadi instruksi yang bisa
     * ditindaklanjuti admin.
     *
     * @param int              $code HTTP status
     * @param array|object|null $body body hasil json_decode
     */
    /**
     * Translate error OpenWA menjadi pesan yang bisa ditindaklanjuti.
     *
     * @param int   $code          HTTP status
     * @param mixed $body          Body response
     * @param bool  $dokumen       Permintaan ini kirim berkas (bukan teks)?
     *                            413 punya arti berbeda: byte untuk berkas,
     *                            karakter untuk teks.
     */
    private function translateError(int $code, $body, bool $dokumen = false): string
    {
        // `message` bisa string atau array (ValidationPipe), dan `error`
        // bisa object pada framework lain — normalkan ke teks tunggal.
        $message = is_array($body) ? ($body['message'] ?? '') : '';
        if (is_array($message)) {
            $message = implode('; ', array_map('strval', $message));
        }
        $message = trim((string) $message);
        $detail = $message !== '' ? $message : ($body['error'] ?? '');

        if (is_object($detail) || is_array($detail)) {
            $detail = json_encode($detail);
        }

        return match ($code) {
            401 => 'API Key ditolak OpenWA. Salin ulang kunci dari dashboard OpenWA atau file data/.api-key.',
            // 403 punya beberapa sebab yang gejalanya sama persis, jadi
            // semua disebutkan sekaligus supaya admin bisa mencocokkan sendiri.
            403 => 'API Key ditolak akses (403). Salah satu dari: role di bawah OPERATOR, key di-scope ke sesi '
                . 'lain, atau Allowed IPs pada key tidak mencakup IP server ini. '
                . 'Kalau key tidak punya Allowed IPs, pastikan juga aplikasi dan OpenWA '
                . 'bisa saling menjangkau (127.0.0.1 hanya berlaku bila satu mesin).',
            404 => 'Sesi WhatsApp tidak ditemukan di OpenWA. Pastikan sesi dibuat dan tidak salah id.',
            409 => 'Sesi WhatsApp belum siap / sedang sibuk. Tunggu statusnya "ready" lalu coba lagi.',
            413 => $dokumen
                ? 'Berkas terlalu besar untuk OpenWA (batas ' . round(self::MAX_DOCUMENT_BYTES / 1048576) . ' MB).'
                : 'Pesan terlalu besar untuk OpenWA (batas ' . self::MAX_TEXT . ' karakter).',
            429 => 'OpenWA membatasi laju kirim (rate limit / pacing). Coba lagi beberapa saat lagi.',
            500 => 'OpenWA gagal mengirim lewat engine WhatsApp: ' . ($detail !== '' ? $detail : 'tanpa keterangan'),
            502 => 'Koneksi engine WhatsApp di OpenWA terputus (retryable).',
            503 => 'OpenWA tidak siap / sedang reconnect. Coba lagi sebentar.',
            504 => 'OpenWA timeout menunggu WhatsApp. Coba lagi.',
            default => 'OpenWA menolak (HTTP ' . $code . '): ' . ($detail !== '' ? (string) $detail : 'tanpa keterangan'),
        };
    }
}