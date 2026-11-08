<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserNotifikasiModel;
use App\Services\AttendancePenaltyService;
use App\Services\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bot Telegram dua arah (inbound webhook + perintah personal).
 *
 * Setup sekali (admin):
 *   1. Isi Bot Token di Pengaturan → Notifikasi.
 *   2. Hitung secret: substr(hash_hmac('sha256','telegram-webhook',TOKEN),0,32)
 *      atau panggil GET /api/telegram/setup?key=API_KEY (butuh api_key terisi).
 *   3. Daftarkan webhook ke Telegram:
 *      https://api.telegram.org/bot<TOKEN>/setWebhook?url=<BASE>/api/telegram/webhook?secret=<SECRET>
 *
 * Perintah yang didukung: /start [token] · /rekap · /denda · /lepas · /bantuan
 */
class TelegramBot extends BaseController
{
    public static function webhookSecret(string $botToken): string
    {
        return substr(hash_hmac('sha256', 'telegram-webhook', $botToken), 0, 32);
    }

    /**
     * Info setup (secret + URL webhook) untuk admin pemegang API key.
     * GET /api/telegram/setup?key=XXX  (atau header X-API-KEY)
     */
    public function setup(): ResponseInterface
    {
        $key = $this->extractKey();
        try {
            // Kunci API aplikasi tinggal satu tempat: kolom api_key pada
            // tabel setting (menu Pengaturan), sama seperti yang dipakai
            // endpoint rekap. absensi_setting tidak punya kolom itu, jadi
            // membacanya dari sana selalu berujung 401.
            $setting = (new \App\Models\DashboardModel())->getSetting();
        } catch (\Throwable $e) {
            return $this->json(false, 'Koneksi database gagal.', null, 500);
        }

        $expected = trim((string) ($setting->api_key ?? ''));
        if ($expected === '') {
            return $this->json(false, 'API key belum dikonfigurasi. Isi api_key di menu Pengaturan.', null, 503);
        }
        if ($key === '' || !hash_equals($expected, $key)) {
            return $this->json(false, 'API key tidak valid.', null, 401);
        }

        try {
            $absensi = (new \App\Models\AttendanceSettingModel())->first();
        } catch (\Throwable $e) {
            return $this->json(false, 'Koneksi database gagal.', null, 500);
        }

        $botToken = trim((string) ($absensi->telegram_bot_token ?? ''));
        if ($botToken === '') {
            return $this->json(false, 'Bot token belum diisi di Pengaturan.', null, 503);
        }
        $secret = $this->webhookSecret($botToken);
        return $this->json(true, 'OK', [
            'secret'      => $secret,
            'webhook_url' => rtrim(base_url(), '/') . '/api/telegram/webhook?secret=' . $secret,
            'bot'         => (new NotificationService())->getBotUsername() ?: '(panggil setelah webhook aktif)',
        ]);
    }

    /**
     * Kunci API diterima lewat query (praktis untuk curl) atau header, supaya
     * admin bisa memakainya tanpa menaruh secret di URI yang tercacat di
     * access log.
     */
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

    /**
     * POST /api/telegram/webhook?secret=XXX  (dipanggil server Telegram)
     */
    public function webhook(): ResponseInterface
    {
        try {
            $setting = (new \App\Models\AttendanceSettingModel())->first();
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(200);
        }
        $botToken = trim((string) ($setting->telegram_bot_token ?? ''));
        if ($botToken === '') {
            return $this->response->setStatusCode(200);
        }
        $secret = trim((string) ($this->request->getGet('secret') ?? ''));
        if ($secret === '' || !hash_equals($this->webhookSecret($botToken), $secret)) {
            return $this->response->setStatusCode(403);
        }

        try {
            $update = $this->request->getJSON(true) ?? [];
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(200);
        }
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!is_array($message)) {
            return $this->response->setStatusCode(200);
        }
        // Payload datang dari jaringan dan bisa berbentuk apa pun: `chat`
        // yang bukan array membuat $message['chat']['id'] melempar TypeError
        // (500) sebelum switch, dan `text` array mencetuskan warning.
        $chat = $message['chat'] ?? null;
        $chatId = is_array($chat) ? $this->teks($chat['id'] ?? null) : '';
        $text = $this->teks($message['text'] ?? null);
        if ($chatId === '' || $text === '' || $text[0] !== '/') {
            return $this->response->setStatusCode(200);
        }

        $parts = preg_split('/\s+/', $text, 2);
        $command = strtolower(preg_replace('/@.*$/', '', $parts[0] ?? ''));
        $arg = trim($parts[1] ?? '');

        $notify = new NotificationService();
        try {
            switch ($command) {
                case '/start':
                    $reply = $this->cmdStart($arg, $chatId);
                    break;
                case '/rekap':
                    $reply = $this->cmdRekap($chatId);
                    break;
                case '/denda':
                    $reply = $this->cmdDenda($chatId);
                    break;
                case '/lepas':
                    $reply = $this->cmdLepas($chatId);
                    break;
                default:
                    $reply = $this->cmdBantuan($chatId);
                    break;
            }
            if ($reply !== '') {
                $notify->sendTelegramTo($chatId, $reply);
            }
        } catch (\Throwable $e) {
            log_message('error', '[telegram-webhook] ' . $e->getMessage());
        }

        // Selalu 200 agar Telegram tidak retry membabi-buta
        return $this->response->setStatusCode(200);
    }

    private function linkedUser(string $chatId): ?object
    {
        $model = new UserNotifikasiModel();
        $row = $model->getByChatId($chatId);
        if (!$row) {
            return null;
        }
        try {
            $user = \Config\Database::connect()->table('users')->where('id', $row->user_id)->get()->getRow();
            return ($user && (int) ($user->active ?? 0) === 1) ? $user : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function displayName(?object $user): string
    {
        if (!$user) {
            return 'Guru';
        }
        $name = trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? ''));
        return $name !== '' ? $name : (string) ($user->username ?? 'Guru');
    }

    private function cmdStart(string $arg, string $chatId): string
    {
        $model = new UserNotifikasiModel();
        if ($arg === '') {
            $user = $this->linkedUser($chatId);
            if ($user) {
                return "Halo <b>{$this->displayName($user)}</b>, akun Anda sudah tertaut. Ketik /bantuan untuk daftar perintah.";
            }
            return "Untuk menautkan akun, buka aplikasi presensi → menu <b>Notifikasi</b> → <b>Tautkan Telegram</b>, lalu ketik ulang /start diikuti kode yang tampil.\nContoh: <code>/start abc123</code>";
        }
        $userId = $model->bindChat($arg, $chatId);
        if (!$userId) {
            return 'Kode tautan tidak valid atau kedaluwarsa. Buat kode baru dari aplikasi (menu Notifikasi → Tautkan Telegram).';
        }
        $user = $this->linkedUser($chatId);
        return "✅ Akun <b>{$this->displayName($user)}</b> berhasil ditautkan. Ketik /bantuan untuk daftar perintah.";
    }

    private function cmdLepas(string $chatId): string
    {
        if (!(new UserNotifikasiModel())->unbindByChatId($chatId)) {
            return 'Gagal melepas tautan. Coba lagi nanti atau hubungi admin.';
        }
        return 'Tautan akun dilepas. Anda tidak akan menerima notifikasi personal lagi.';
    }

    /**
     * Nilai payload Telegram bisa berbentuk apa pun. Diambil sebagai teks
     * pendek tanpa karakter kontrol (chat id dipakai di log dan query).
     */
    private function teks(mixed $nilai): string
    {
        if (! is_scalar($nilai)) {
            return '';
        }
        $teks = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $nilai) ?? '';

        return mb_substr(trim($teks), 0, 64);
    }

    private function cmdBantuan(string $chatId): string
    {
        $user = $this->linkedUser($chatId);
        $sapaan = $user ? $this->displayName($user) : 'Bapak/Ibu Guru';
        return "Halo <b>{$sapaan}</b>! Perintah yang tersedia:\n"
            . "/rekap — rekap presensi hari ini\n"
            . "/denda — sisa denda Anda tahun ini\n"
            . "/lepas — lepas tautan akun\n"
            . "/bantuan — pesan ini";
    }

    private function cmdRekap(string $chatId): string
    {
        // Bot Telegram bersifat publik: tanpa memeriksa pengirim, siapa pun
        // yang tahu username bot bisa membaca agregat kehadiran sekolah.
        // Sama seperti /denda, hanya akun tertaut yang boleh meminta rekap.
        $user = $this->linkedUser($chatId);
        if (!$user) {
            return 'Akun Anda belum tertaut. Tautkan dulu via aplikasi (menu Notifikasi → Tautkan Telegram).';
        }

        try {
            $db = \Config\Database::connect();
            $today = date('Y-m-d');
            $row = $db->table('absensi_harian')
                ->select("COUNT(*) as total,"
                    // "Hadir" mengikuti definisi layar lain (ontime/late/WFH/dinas),
                    // bukan hanya yang tepat waktu — dulu guru yang telat/WFH/dinas
                    // tidak muncul di angka ini sama sekali.
                    . ' ' . \App\Services\AttendancePolicy::sqlTermHadir('absensi_harian') . ' as hadir,'
                    . " SUM(CASE WHEN status_masuk = 'LATE' THEN 1 ELSE 0 END) as telat,"
                    . " SUM(CASE WHEN status_masuk IN ('ALPHA','ALPA') THEN 1 ELSE 0 END) as alpha", false)
                ->where('tgl', $today)
                ->get()->getRow();
            return "📊 <b>REKAP " . date('d/m/Y') . "</b>\n"
                . "✅ Hadir: " . (int) ($row->hadir ?? 0) . "\n"
                . "⏰ Telat: " . (int) ($row->telat ?? 0) . "\n"
                . "❌ Alpha: " . (int) ($row->alpha ?? 0);
        } catch (\Throwable $e) {
            return 'Gagal mengambil rekap. Coba lagi nanti.';
        }
    }

    private function cmdDenda(string $chatId): string
    {
        $user = $this->linkedUser($chatId);
        if (!$user) {
            return 'Akun Anda belum tertaut. Tautkan dulu via aplikasi (menu Notifikasi → Tautkan Telegram).';
        }
        $userId = (int) $user->id;
        try {
            $db = \Config\Database::connect();
            $year = date('Y');
            $setting = (new \App\Models\AttendanceSettingModel())->first() ?? (object) [];
            $start = $year . '-01-01';
            $end = date('Y-m-d');

            $row = $db->table('absensi_harian')
                ->select('SUM(COALESCE(denda, 0)) as total')
                ->where('user_id', $userId)
                ->where('tgl >=', $start)
                ->where('tgl <=', $end)
                ->get()->getRow();
            $total = (float) ($row->total ?? 0);

            $miss = (new AttendancePenaltyService())->missingAlphaFines($userId, $start, $end, $setting, false);
            $total += (float) ($miss['total'] ?? 0);

            $bayar = 0.0;
            if ($db->tableExists('absensi_pembayaran_denda')) {
                $pr = $db->table('absensi_pembayaran_denda')
                    ->select('SUM(COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)) as total')
                    ->where('user_id', $userId)
                    ->where('tahun', $year)
                    ->where('status !=', 'batal')
                    ->get()->getRow();
                $bayar = (float) ($pr->total ?? 0);
            }

            $sisa = max(0.0, $total - $bayar);
            $fmt = static fn($v) => 'Rp ' . number_format($v, 0, ',', '.');
            return "💰 <b>DENDA {$this->displayName($user)} ({$year})</b>\n"
                . "Total: {$fmt($total)}\n"
                . "Dibayar: {$fmt($bayar)}\n"
                . "Sisa: <b>{$fmt($sisa)}</b>";
        } catch (\Throwable $e) {
            return 'Gagal mengambil data denda. Coba lagi nanti.';
        }
    }
}
