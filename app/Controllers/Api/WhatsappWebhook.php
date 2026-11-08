<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Webhook masuk dari gateway WhatsApp OpenWA.
 *
 * Senderhana: endpoint ini hanya MENERIMA laporan status, tidak pernah
 * mengirim pesan. Gunanya agar masalah ketahuan seketika — terutama saat
 * sesi WhatsApp jatuh ke `failed` atau nomor kena pembatasan WhatsApp —
 * bukan baru terlihat sore hari saat rekap tidak masuk.
 *
 * Setup sekali (admin):
 *   1. Isi OpenWA di Pengaturan → Notifikasi.
 *   2. Hitung secret:
 *        substr(hash_hmac('sha256', 'openwa-webhook', API_KEY), 0, 32)
 *      atau panggil GET /api/whatsapp/setup?key=API_KEY_ANDA (butuh api_key
 *      aplikasi di menu Pengaturan terisi).
 *   3. Daftarkan webhook di dashboard OpenWA:
 *        POST /api/sessions/{sessionId}/webhooks
 *        { "url": "https://absensi.ikas.my.id/api/whatsapp/webhook?secret=XXX",
 *          "events": ["session.status","session.restriction"],
 *          "secret": "HMAC_OPENWA" }
 *
 * Autentikasi memakai `?secret=` — pola yang sama dengan
 * Api\TelegramBot, karena pemanggilnya server OpenWA yang tidak punya
 * session aplikasi ini.
 */
class WhatsappWebhook extends BaseController
{
    /**
     * Event yang benar-benarinformatif untuk absensi.
     *
     * `message.*` sengaja TIDAK diikyuti: notifikasi sudah lewat kanal
     * keluar, sedangkan pesan masuk tidak ada yang menindaklanjuti, jadi
     * menambahkannya hanya menambah beban tanpa manfaat.
     */
    private const WANTED_EVENTS = [
        'session.status',
        'session.restriction',
    ];

    /** Jeda antar alert identik ke grup admin. */
    private const ALERT_COOLDOWN_DETIK = 1800;

    /**
     * Secret webhook diturunkan dari API key OpenWA.
     *
     * Secret turunan membuat webhook otomatis tidak berlaku begitu admin
     * merotasi API key — tidak ada secret lama yang ditinggal aktif.
     */
    public static function webhookSecret(string $apiKey): string
    {
        return substr(hash_hmac('sha256', 'openwa-webhook', $apiKey), 0, 32);
    }

    /**
     * Info setup (secret + URL webhook) untuk admin pemegang API key.
     * GET /api/whatsapp/setup?key=XXX  (atau header X-API-KEY)
     */
    public function setup(): ResponseInterface
    {
        $key = $this->extractKey();

        try {
            // Autentikasi memakai api_key aplikasi (tabel setting, menu
            // Pengaturan) — sama seperti endpoint rekap. Kolom itu tidak ada
            // di absensi_setting, jadi membaca dari sana selalu 401.
            $aplikasi = (new \App\Models\DashboardModel())->getSetting();
        } catch (\Throwable $e) {
            return $this->json(false, 'Koneksi database gagal.', null, 500);
        }

        $expected = trim((string) ($aplikasi->api_key ?? ''));
        if ($expected === '') {
            return $this->json(false, 'API key belum dikonfigurasi. Isi api_key di menu Pengaturan.', null, 503);
        }
        if ($key === '' || ! hash_equals($expected, $key)) {
            return $this->json(false, 'API key tidak valid.', null, 401);
        }

        try {
            $setting = (new \App\Models\AttendanceSettingModel())->first();
        } catch (\Throwable $e) {
            return $this->json(false, 'Koneksi database gagal.', null, 500);
        }

        $openwaKey = trim((string) ($setting->wa_api_key ?? ''));
        if ($openwaKey === '') {
            return $this->json(false, 'API Key OpenWA belum diisi di Pengaturan.', null, 503);
        }

        $secret = self::webhookSecret($openwaKey);
        $svc    = new \App\Services\OpenWAService($setting);

        return $this->json(true, 'OK', [
            'secret'      => $secret,
            'webhook_url' => rtrim(base_url(), '/') . '/api/whatsapp/webhook?secret=' . $secret,
            'base_url'    => $svc->baseUrl(),
            'events'      => self::WANTED_EVENTS,
            'curl'        => 'curl -X POST "' . $svc->baseUrl() . '/api/sessions/{sessionId}/webhooks" '
                . '-H "Content-Type: application/json" -H "X-API-Key: ' . $openwaKey . '" '
                . '-d \'{"url":"' . rtrim(base_url(), '/') . '/api/whatsapp/webhook?secret=' . $secret . '",'
                . '"events":' . json_encode(self::WANTED_EVENTS) . ',"secret":"ganti-dengan-secret-anda"}\'',
            'status'      => $svc->getStatus(),
        ]);
    }

    /**
     * POST /api/whatsapp/webhook?secret=XXX  (dipanggil server OpenWA)
     */
    public function webhook(): ResponseInterface
    {
        try {
            $setting = (new \App\Models\AttendanceSettingModel())->first();
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(200);
        }

        $openwaKey = trim((string) ($setting->wa_api_key ?? ''));
        if ($openwaKey === '') {
            return $this->response->setStatusCode(200);
        }

        $secret = trim((string) ($this->request->getGet('secret') ?? ''));
        if ($secret === '' || ! hash_equals(self::webhookSecret($openwaKey), $secret)) {
            log_message('warning', '[openwa-webhook] secret tidak cocok, ditolak.');
            return $this->response->setStatusCode(403);
        }

        try {
            $payload = $this->request->getJSON(true) ?? [];
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(200);
        }

        $event = (string) ($payload['event'] ?? '');
        if (! in_array($event, self::WANTED_EVENTS, true)) {
            return $this->response->setStatusCode(200);
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }

        $name   = $this->teks($data['name'] ?? null) ?: $this->teks($data['sessionId'] ?? null) ?: 'sesi';
        $status = $this->teks($data['status'] ?? null);

        // session.restriction = pembatasan imposed WhatsApp sendiri
        // (reach_out timelock, TOS block, proxy block). Nomor seperti ini
        // masih "ready" tapi TIDAK boleh dipakai broadcast.
        // Bentuk `restriction` tidak dijamin: payload jarak jauh bisa
        // mengirim string, dan $restriction['kind'] pada string melempar
        // TypeError — webhook gagal padahal event-nya nyata.
        $restriction = $data['restriction'] ?? null;
        if ($restriction !== null && ! is_array($restriction)) {
            $restriction = ['kind' => $this->teks($restriction)];
        }

        if ($event === 'session.restriction' || ! empty($restriction)) {
            $msg = "🚫 <b>WHATSAPP DIBATASI</b>\n"
                . "Sesi: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "\n"
                . "Jenis: " . htmlspecialchars(
                    (string) ($restriction['kind'] ?? 'tidak diketahui'),
                    ENT_QUOTES,
                    'UTF-8'
                ) . "\n";
            if (! empty($restriction['expiresAt'])) {
                $msg .= "Berlaku sampai: " . $this->waktuLabel($this->teks($restriction['expiresAt'])) . "\n";
            }
            $msg .= "─────────────────\n"
                . "Notifikasi WhatsApp mungkin tidak terkirim. Cek dashboard OpenWA.";
            $this->kirimKeAdmin($msg, $name, 'restriction:' . (string) ($restriction['kind'] ?? ''));
        } elseif ($status !== '' && $status !== 'ready' && $status !== 'disconnected') {
            // Kirim saat status benar-benar bermasalah. 'ready' dan
            // 'disconnected' terlalu sering untuk jadi notifikasi —
            // reconnect adalah hal biasa, bukan kejadian.
            $lastError = $this->teks($data['lastError'] ?? null);
            $msg = "⚠️ <b>WHATSAPP GATEWAY BERMASALAH</b>\n"
                . "Sesi: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "\n"
                . "Status: " . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . "\n";
            if ($lastError !== '') {
                $msg .= "Error: " . mb_strimwidth(
                    htmlspecialchars($lastError, ENT_QUOTES, 'UTF-8'),
                    0,
                    300,
                    '…'
                ) . "\n";
            }
            $msg .= "─────────────────\n"
                . "Notifikasi WhatsApp tidak terkirim sampai sesi kembali ready.";
            $this->kirimKeAdmin($msg, $name, 'status:' . $status);
        }

        // Selalu 200 agar OpenWA tidak retry event yang tidak kita-Azukan.
        return $this->response->setStatusCode(200);
    }

    /**
     * Kabari admin lewat Telegram — kanal yang sudah dipakai aplikasi ini
     * dan tidak bergantung pada WhatsApp yang sedang bermasalah.
     *
     * Mengirim ke admin, bukan ke guru, karena yang bermasalah adalah
     * gateway, bukan individual.
     */
    private function kirimKeAdmin(string $message, string $sesi, string $penanda = ''): void
    {
        log_message('error', '[openwa-webhook] ' . $sesi . ': ' . strip_tags($message));

        if ($penanda !== '' && ! $this->sudahPerluDikirim($sesi, $penanda)) {
            return;
        }

        try {
            (new NotificationService())->sendTelegram($message);
        } catch (\Throwable $e) {
            // Kegagalan reporting tidak boleh menggagalkan webhook.
            log_message('error', '[openwa-webhook] gagal kirim alert: ' . $e->getMessage());
        }
    }

    /**
     * Sesi yang flapping (failed → connecting → failed) mengirim event yang
     * sama berkali-kali. Tanpa jeda, grup admin terbanjiri sampai peringatan
     * penting tenggelam.
     */
    private function sudahPerluDikirim(string $sesi, string $penanda): bool
    {
        $cache = \Config\Services::cache();
        $kunci = 'openwa_alert_' . md5($sesi . '|' . $penanda);

        if ($cache->get($kunci) !== null) {
            return false;
        }

        $cache->save($kunci, 1, self::ALERT_COOLDOWN_DETIK);

        return true;
    }

    /** Tanggal ISO OpenWA -> label lokal. */
    private function waktuLabel(string $iso): string
    {
        $ts = strtotime($iso);
        return $ts !== false ? date('d/m/Y H:i', $ts) : $iso;
    }

    /**
     * Nilai payload jarak jauh bisa berbentuk apa pun. Diambil sebagai teks
     * pendek tanpa karakter kontrol: nama sesi ikut masuk ke log_message, dan
     * baruan di dalamnya bisa dipakai memalsukan baris log.
     */
    private function teks(mixed $nilai): string
    {
        if (! is_scalar($nilai)) {
            return '';
        }
        $teks = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $nilai) ?? '';

        return mb_substr(trim($teks), 0, 200);
    }

    /** Kunci API lewat query (praktis untuk curl) atau header X-API-KEY. */
    private function extractKey(): string
    {
        $key = trim((string) ($this->request->getGet('key') ?? ''));
        if ($key === '') {
            $key = trim($this->request->getHeaderLine('X-API-KEY'));
        }

        return $key;
    }

    private function json(bool $status, string $message, $data, int $code): ResponseInterface
    {
        $body = ['status' => $status, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }

        return $this->response->setStatusCode($code)->setJSON($body);
    }
}