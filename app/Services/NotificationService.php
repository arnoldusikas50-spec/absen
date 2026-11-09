<?php

namespace App\Services;

use App\Models\AttendanceSettingModel;
use App\Models\NotifChannelConfigModel;

class NotificationService
{
    /**
     * Kolom kredensial notifikasi yang boleh dicari ulang bila baris
     * pengaturan utama kosong. Satu-satunya sumber resmi tetap absensi_setting;
     * nilai di sini hanya daftar kolom yang dicoba.
     */
    private const NOTIFICATION_KEYS = [
        'telegram_bot_token',
        'telegram_chat_id',
        'wa_gateway_url',
        'wa_token',
        'wa_target_number',
        'wa_gateway_mode',
        'wa_base_url',
        'wa_api_key',
        'wa_session_name',
    ];

    protected $setting;

    /** Pesan error terakhir yang ramah (untuk ditampilkan ke admin). */
    public string $lastError = '';

    // ── Channel config ────────────────────────────────────────────────

    /** Instance model config channel, di-lazy-load satu kali per request. */
    private ?NotifChannelConfigModel $channelConfigModel = null;

    /**
     * Pabrik model channel config (bisa di-override di test).
     */
    protected function channelConfig(): NotifChannelConfigModel
    {
        return $this->channelConfigModel ??= new NotifChannelConfigModel();
    }

    /**
     * Apakah channel tertentu aktif untuk suatu jenis notifikasi?
     *
     * Channel: 'tg_grup' | 'wa_grup' | 'tg_personal' | 'wa_personal'
     * Jenis  : 'keterlambatan' | 'denda' | 'izin' | 'koreksi' | 'alpha' |
     *          'teguran' | 'dinas' | 'guru_terawal' | 'rekap_harian' |
     *          'rekap_denda_mingguan' | 'rekap_denda_bulanan' |
     *          'rekap_bulanan' | 'ucapan_pagi' | 'ucapan_sore' |
     *          'lupa_checkout' | 'kehadiran_rendah' | 'rekap_piket'
     *
     * Fail-open: jenis/channel tidak dikenal → true (agar notifikasi baru
     * yang belum punya baris config tetap terkirim sampai admin mengaturnya).
     */
    public function isChannelEnabled(string $jenis, string $channel): bool
    {
        try {
            return $this->channelConfig()->isEnabled($jenis, $channel);
        } catch (\Throwable $e) {
            log_message('error', '[Notification] isChannelEnabled gagal: ' . $e->getMessage());
            return true; // fail-open
        }
    }

    /**
     * Kirim ke WhatsApp grup hanya bila channel config mengizinkan.
     */
    private function sendWhatsAppGrup(string $jenis, string $message): bool
    {
        if (! $this->isChannelEnabled($jenis, 'wa_grup')) {
            log_message('info', "[Notification] WA Grup dinonaktifkan untuk jenis '{$jenis}', dilewati.");
            return false;
        }
        return $this->sendWhatsApp($message);
    }

    /**
     * Broadcast ke Telegram grup + WhatsApp grup dengan channel config.
     *
     * Menggantikan notify() untuk semua notify*() yang butuh kontrol per-jenis.
     *
     * Sukses = salah SATU kanal aktif berhasil. Ini yang membuat toggle di
     * /admin/presensi/notif-config bermakna: TG Grup mati + WA Grup nyala +
     * WA terkirim = berhasil, bukan GAGAL. Kegagalan WA tetap dicatat di
     * log supaya Telegram yang mati tidak tertutup.
     *
     * Bila KEDUA kanal grup dimatikan untuk jenis ini, tidak ada yang
     * dikirim dan lastError berisi kata 'dinonaktifkan' (bukan 'GAGAL'):
     * pemanggil cron harus melaporkannya sebagai Skip (HTTP 200), bukan
     * kegagalan. Lihat isGrupDisabled().
     */
    public function isGrupDisabled(string $jenis): bool
    {
        return ! $this->isChannelEnabled($jenis, 'tg_grup')
            && ! $this->isChannelEnabled($jenis, 'wa_grup');
    }

    private function notifyGrup(string $jenis, string $message): bool
    {
        if ($this->isGrupDisabled($jenis)) {
            $this->lastError = "Notifikasi jenis '{$jenis}' dinonaktifkan di Pengaturan → Channel Notifikasi (TG Grup + WA Grup mati).";
            log_message('info', '[Notification] ' . $this->lastError . ' Dilewati.');
            return false;
        }

        $tgAktif = $this->isChannelEnabled($jenis, 'tg_grup');
        $waAktif = $this->isChannelEnabled($jenis, 'wa_grup');

        $tgSent = false;
        $waSent = false;
        $telegramError = '';
        $waError       = '';

        if ($tgAktif) {
            $tgSent        = $this->sendTelegram($message);
            $telegramError = $this->lastError;
        }

        if ($waAktif) {
            $waSent  = $this->sendWhatsAppGrup($jenis, $message);
            $waError = $this->lastError;
            if (! $waSent && $this->waMode() !== 'off' && $waError !== '') {
                log_message('error', '[Notification] WA Grup gagal (' . $jenis . '): ' . $waError);
            }
        }

        if ($tgSent || $waSent) {
            $this->lastError = '';
            return true;
        }

        // Keduanya gagal (atau kanal yang aktif gagal): laporkan alasan
        // yang pertama tersedia supaya cron mencatatnya dengan jujur.
        $this->lastError = $telegramError !== '' ? $telegramError : $waError;

        return false;
    }

    /**
     * Kirim SATU BERKAS ke grup/channel sekolah.
     *
     * Berbeda dengan notifyGrup() yang mengirim teks, method ini mengirim
     * lampiran (rekap PDF, dsb). Motivasinya sama: admin menekan satu
     * tombol di aplikasi dan dokumennya sampai ke grup tanpa harus
     * mengunduh lalu mengunggah ulang.
     *
     * Hanya kanal grup yang dipakai (tg_grup / wa_grup) — dokumen rekap
     * tidak pernah dikirim ke individual. Filter channel config dihormati
     * supaya pengaturan admin berlaku sama seperti untuk teks: kalau
     * admin mematikan WA Grup untuk jenis ini, dokumen tidak dikirim ke WA
     * meski Telegram aktif.
     *
     * Kegagalan satu kanal tidak membatalkan kanal lain: Telegram dan WA
     * dicoba terpisah dan hasilnya dikembalikan per kanal supaya UI bisa
     * menyebut kanal mana yang gagal.
     *
     * @return array{telegram:bool, whatsapp:bool, telegram_error:string, whatsapp_error:string}
     */
    public function sendDokumenToGrup(
        string $jenis,
        string $filePath,
        string $filename = '',
        string $caption = ''
    ): array {
        $hasil = [
            'telegram'        => false,
            'whatsapp'        => false,
            'telegram_error'  => '',
            'whatsapp_error'  => '',
        ];

        // ── Telegram ────────────────────────────────────────────────
        if ($this->isChannelEnabled($jenis, 'tg_grup')) {
            $hasil['telegram'] = $this->sendTelegramDocument($filePath, $filename, $caption);
            $hasil['telegram_error'] = $this->lastError;
        } else {
            $hasil['telegram_error'] = 'Kanal Telegram Grup dimatikan untuk notifikasi jenis ini.';
            log_message('info', "[Notification] TG Grup dinonaktifkan untuk '{$jenis}', dokumen dilewati.");
        }

        // ── WhatsApp ────────────────────────────────────────────────
        if ($this->isChannelEnabled($jenis, 'wa_grup')) {
            if ($this->waMode() === 'off') {
                $hasil['whatsapp_error'] = 'Kanal WhatsApp sedang dimatikan di Pengaturan.';
            } else {
                $openwa = $this->openwaService();
                $hasil['whatsapp'] = $openwa->sendDocument(
                    $openwa->broadcastChatId(),
                    $filePath,
                    $filename,
                    $caption,
                    'application/pdf'
                );
                $hasil['whatsapp_error'] = $openwa->lastError;
            }
        } else {
            $hasil['whatsapp_error'] = 'Kanal WhatsApp Grup dimatikan untuk notifikasi jenis ini.';
            log_message('info', "[Notification] WA Grup dinonaktifkan untuk '{$jenis}', dokumen dilewati.");
        }

        return $hasil;
    }

    /**
     * Kirim satu berkas ke chat Telegram tujuan grup.
     *
     * Telegram menerima upload lewat multipart/form-data. Isi file dibaca
     * dari disk dan dikirim sebagai @-field; encode base64 di URL juga
     * diterima tapi multipart tidak membuat payload 33% lebih besar dan
     * tidak terkena batas panjang URL.
     *
     * Bot API menolak file > 50 MB; di sini dijaga lebih ketat supaya
     * error yang muncul bisa ditindaklanjuti admin.
     *
     * Dipisah jadi protected (bukan private) supaya test bisa men-stub
     * lapisan jaringan tanpa memalsukan respons Bot API — sama seperti
     * notifModel() dan openwaService().
     */
    protected function sendTelegramDocument(string $filePath, string $filename = '', string $caption = ''): bool
    {
        $chatId = trim((string) ($this->setting->telegram_chat_id ?? ''));

        if ($chatId === '') {
            $this->lastError = 'Chat ID tujuan kosong.';
            log_message('error', '[Notification] Dokumen DILEWATI: ' . $this->lastError);
            return false;
        }

        return $this->sendTelegramDocumentTo($chatId, $filePath, $filename, $caption);
    }

    /**
     * Kirim berkas ke chat_id tertentu (grup maupun personal).
     */
    public function sendTelegramDocumentTo(string $chatId, string $filePath, string $filename = '', string $caption = ''): bool
    {
        $this->lastError = '';
        $token = trim((string) ($this->setting->telegram_bot_token ?? ''));
        $chatId = trim($chatId);

        if ($token === '' || $chatId === '') {
            $this->lastError = $token === ''
                ? 'Bot Token kosong pada baris absensi_setting id=' . ($this->setting->id ?? '?') . '.'
                : 'Chat ID tujuan kosong.';
            log_message('error', '[Notification] Dokumen DILEWATI: ' . $this->lastError);
            return false;
        }

        if (! is_file($filePath) || ! is_readable($filePath)) {
            $this->lastError = 'Berkas tidak bisa dibaca: ' . $filePath;
            log_message('error', '[Notification] ' . $this->lastError);
            return false;
        }

        $isi = file_get_contents($filePath);
        if ($isi === false || $isi === '') {
            $this->lastError = 'Berkas kosong atau gagal dibaca.';
            return false;
        }

        // Batas Bot API adalah 50 MB; sisakan ruang supaya tidak pernah
        // ditolak karena batas yang tidak bisa kita kendalikan.
        $batas = 45 * 1024 * 1024;
        if (strlen($isi) > $batas) {
            $mb = round(strlen($isi) / 1048576, 1);
            $this->lastError = "Berkas terlalu besar untuk Telegram ({$mb} MB, batas {$batas} byte).";
            log_message('error', '[Notification] ' . $this->lastError);
            return false;
        }

        $nama = trim($filename) !== '' ? basename(trim($filename)) : basename($filePath);

        $fields = [
            'chat_id' => $chatId,
        ];
        if (trim($caption) !== '') {
            // Caption ikut parse_mode HTML supaya <b> dan <code> yang
            // dipakai teks notifikasi lain tidak tampil mentah.
            $fields['caption'] = $caption;
            $fields['parse_mode'] = 'HTML';
        }

        $fields['document'] = new \CURLFile($filePath, 'application/pdf', $nama);

        try {
            $client = \Config\Services::curlrequest();
            $url = "https://api.telegram.org/bot{$token}/sendDocument";
            $response = $client->post($url, [
                'multipart' => $fields,
                'timeout'   => 60,
                'http_errors' => false,
            ]);
            $code = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            if (($body['ok'] ?? false) === true) {
                log_message('info', '[Notification] Dokumen "' . $nama . '" terkirim ke Telegram.');
                return true;
            }
            $this->lastError = $this->translateTelegramError($code, (string) ($body['description'] ?? ''));
            log_message('error', 'Telegram sendDocument gagal (HTTP ' . $code . '): ' . ($body['description'] ?? 'tanpa keterangan'));
            return false;
        } catch (\Throwable $e) {
            $this->lastError = 'Server tidak bisa menghubungi api.telegram.org (' . $e->getMessage() . ')';
            log_message('error', 'Telegram sendDocument failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim personal (DM) ke guru dengan filter channel config.
     *
     * Sebelum memanggil notifyUser(), method ini memeriksa apakah kanal
     * personal (tg_personal / wa_personal) masing-masing aktif untuk jenis
     * ini. notifyUser() sendiri masih dipakai karena dia menangani fallback
     * urutan kanal dan preferensi per-guru — kita hanya perlu menyempitkan
     * kanal yang boleh dicoba.
     *
     * Strategi:
     *   1. Kedua kanal personal aktif   → panggil notifyUser() seperti biasa
     *   2. Hanya tg_personal aktif      → coba Telegram saja
     *   3. Hanya wa_personal aktif      → coba WA saja
     *   4. Keduanya mati                → skip personal sama sekali
     *
     * @param bool $bolehGrup Diteruskan ke notifyUser(). Hampir semua pemanggil
     *                         di file ini sudah meng-broadcast pesan yang sama
     *                         lewat notifyGrup() — untuk mereka WAJIB false:
     *                         fallback grup akan menduplikasi broadcast dan
     *                         melewati channel config jenis itu (notifyUser
     *                         memakai sendWhatsApp/sendTelegram mentah).
     */
    private function notifyPersonal(
        string $jenis,
        int    $userId,
        string $message,
        string $prefKey   = '',
        bool   $bolehGrup = true
    ): bool {
        $tgOk = $this->isChannelEnabled($jenis, 'tg_personal');
        $waOk = $this->isChannelEnabled($jenis, 'wa_personal');

        if (! $tgOk && ! $waOk) {
            log_message('info', "[Notification] Kedua kanal personal dinonaktifkan untuk '{$jenis}', skip DM user {$userId}.");
            return false;
        }

        if ($tgOk && $waOk) {
            // Kanal lengkap — biarkan notifyUser() memilih urutan normalnya
            return $this->notifyUser($userId, $message, $prefKey, $bolehGrup);
        }

        // Salah satu kanal mati — kita perlu sedikit trik:
        // Override personalChannels() tidak elegan; sebagai gantinya kita
        // kirim langsung ke kanal yang masih aktif.
        $model  = $this->notifModel();

        // Cek preferensi guru terlebih dahulu
        if ($prefKey !== '') {
            $prefs = $model->getPrefs($userId);
            if (empty($prefs[$prefKey])) {
                $this->lastError   = 'Guru mematikan preferensi "' . $prefKey . '".';
                $this->lastChannel = 'preferensi-mati';
                return false;
            }
        }

        if ($waOk) {
            $waNumber = $model->getVerifiedWaNumber($userId);
            if ($waNumber !== '' && $this->waMode() === 'openwa') {
                $openwa = $this->openwaService();
                if ($openwa->sendText($waNumber, $message)) {
                    $this->lastChannel = 'whatsapp';
                    return true;
                }
                $this->lastError = $openwa->lastError;
            }
        }

        if ($tgOk) {
            $row    = $model->getByUserId($userId);
            $chatId = (string) ($row->telegram_chat_id ?? '');
            if ($chatId !== '' && $this->sendTelegramTo($chatId, $message)) {
                $this->lastChannel = 'telegram';
                return true;
            }
        }

        // Tidak berhasil — fallback ke grup jika diizinkan
        if ($bolehGrup) {
            if ($this->sendWhatsApp($message) || $this->sendTelegram($message)) {
                $this->lastChannel = 'grup';
                return true;
            }
        }

        $this->lastChannel = 'tidak-terkirim';
        return false;
    }

    /**
     * Kirim satu berkas (mis. PDF surat teguran) ke kanal PERSONAL guru.
     *
     * Berbeda dengan notifyPersonal()/notifyUser() yang mengirim TEKS dan
     * boleh jatuh ke grup, method ini:
     *   - hanya mengirim BERKAS,
     *   - hanya ke personal (WA nomor terverifikasi / chat Telegram guru),
     *   - TANPA fallback grup: dokumen pribadi tidak boleh terekspos ke grup.
     *
     * Urutan kanal: WhatsApp dulu, Telegram bila WA gagal/tak tersedia —
     * sama seperti teks personal. Preferensi guru dan config channel
     * personal (tg_personal / wa_personal) dihormati.
     *
     * @return array{ok:bool, channel:string, error:string} channel:
     *         'whatsapp' | 'telegram' | 'preferensi-mati' | 'tidak-terkirim'
     */
    public function kirimDokumenPersonal(
        string $jenis,
        int    $userId,
        string $filePath,
        string $filename = '',
        string $caption = '',
        string $prefKey = ''
    ): array {
        $gagal = static fn(string $channel, string $error) => ['ok' => false, 'channel' => $channel, 'error' => $error];

        $this->lastChannel = '';
        $this->lastError = '';

        if ($userId <= 0) {
            return $gagal('tidak-terkirim', 'user_id tidak valid.');
        }

        if (! is_file($filePath) || ! is_readable($filePath)) {
            $this->lastError = 'Berkas tidak bisa dibaca.';
            $this->lastChannel = 'tidak-terkirim';
            return $gagal('tidak-terkirim', $this->lastError);
        }

        try {
            $model = $this->notifModel();

            if ($prefKey !== '') {
                $prefs = $model->getPrefs($userId);
                if (empty($prefs[$prefKey])) {
                    $this->lastError   = 'Guru mematikan preferensi "' . $prefKey . '".';
                    $this->lastChannel = 'preferensi-mati';
                    return $gagal('preferensi-mati', $this->lastError);
                }
            }

            $tgOk = $this->isChannelEnabled($jenis, 'tg_personal');
            $waOk = $this->isChannelEnabled($jenis, 'wa_personal');

            if (! $tgOk && ! $waOk) {
                $this->lastError = "Kedua kanal personal dinonaktifkan untuk '{$jenis}'.";
                $this->lastChannel = 'tidak-terkirim';
                log_message('info', '[Notification] ' . $this->lastError);
                return $gagal('tidak-terkirim', $this->lastError);
            }

            $alasan = [];

            // 1. WhatsApp — kanal utama.
            if ($waOk) {
                $waNumber = $model->getVerifiedWaNumber($userId);
                if ($waNumber !== '' && $this->waMode() === 'openwa') {
                    $openwa = $this->openwaService();
                    if ($openwa->sendDocument($waNumber, $filePath, $filename, $caption, 'application/pdf')) {
                        $this->lastChannel = 'whatsapp';
                        return ['ok' => true, 'channel' => 'whatsapp', 'error' => ''];
                    }
                    $this->lastError = $openwa->lastError;
                    log_message('error', '[kirimDokumenPersonal] WA gagal, coba Telegram: ' . $openwa->lastError);
                    $alasan[] = 'WhatsApp: ' . $openwa->lastError;
                } else {
                    $alasan[] = $waNumber === ''
                        ? 'Nomor WA guru belum terverifikasi.'
                        : 'Kanal WhatsApp tidak aktif.';
                }
            } else {
                $alasan[] = 'Kanal WA personal dimatikan di Channel Notifikasi.';
            }

            // 2. Telegram — cadangan.
            if ($tgOk) {
                try {
                    $row    = $model->getByUserId($userId);
                    $chatId = (string) ($row->telegram_chat_id ?? '');
                } catch (\Throwable $e) {
                    $chatId = '';
                    log_message('error', 'Gagal membaca chat id Telegram: ' . $e->getMessage());
                }
                if ($chatId !== '') {
                    if ($this->sendTelegramDocumentTo($chatId, $filePath, $filename, $caption)) {
                        $this->lastChannel = 'telegram';
                        return ['ok' => true, 'channel' => 'telegram', 'error' => ''];
                    }
                    $alasan[] = 'Telegram: ' . ($this->lastError !== '' ? $this->lastError : 'ditolak tanpa keterangan');
                } else {
                    $alasan[] = 'Chat Telegram guru belum tertaut (minta guru Start bot dan tautkan).';
                }
            } else {
                $alasan[] = 'Kanal Telegram personal dimatikan di Channel Notifikasi.';
            }

            // Sengaja TANPA fallback grup: dokumen personal tidak boleh
            // terekspos ke grup. Kegagalan dilaporkan apa adanya.
            $this->lastError = $alasan === []
                ? 'Tidak ada kanal personal yang tertaut.'
                : implode(' | ', $alasan);
            $this->lastChannel = 'tidak-terkirim';
            return $gagal('tidak-terkirim', $this->lastError);
        } catch (\Throwable $e) {
            log_message('error', 'Kirim dokumen personal gagal: ' . $e->getMessage());
            $this->lastChannel = 'tidak-terkirim';
            return $gagal('tidak-terkirim', 'Kesalahan sistem — periksa log aplikasi.');
        }
    }

    public function __construct()
    {
        $model = new AttendanceSettingModel();
        $this->setting = $model->first();
        if (!$this->setting) {
            $this->setting = (object) [];
        }
        // Baris utama (id terkecil) tetap dipakai untuk semua nilai pengaturan.
        // Hanya kredensial notifikasi yang dicari ulang bila kosong, karena
        // absensi_setting sempat terisi ganda: first() bisa mengambil baris
        // yang semua kolom notifikasinya NULL, sehingga rekap tidak terkirim
        // tanpa satu pun pesan di log.
        foreach (self::NOTIFICATION_KEYS as $k) {
            if (!empty($this->setting->$k)) {
                continue;
            }
            $fallback = $this->findNotificationValue($k);
            if ($fallback !== null) {
                $this->setting->$k = $fallback;
            }
        }
    }

    /**
     * Cari nilai kredensial notifikasi di luar baris pengaturan utama.
     *
     * Urutan sumber:
     *   1. Baris lain di absensi_setting (tabel bisa terisi ganda).
     *   2. Tabel setting — hanya bila kolomnya benar-benar ada. Dulu blok ini
     *      selecting kolom yang tidak pernah ada, jadi tidak pernah berguna.
     *
     * @return string|null null bila tidak ada sumber yang punya nilai
     */
    private function findNotificationValue(string $key): ?string
    {
        $db = \Config\Database::connect();

        foreach (['absensi_setting', 'setting'] as $table) {
            try {
                if (! $db->fieldExists($key, $table)) {
                    continue;
                }

                $row = $db->table($table)
                    ->select('*')
                    ->where($key . ' IS NOT NULL')
                    ->where($key . ' !=', '')
                    ->orderBy('id', 'ASC')
                    ->get(1)
                    ->getRow();

                if ($row !== null) {
                    log_message('debug', '[Notification] ' . $key . ' diambil dari fallback tabel ' . $table . ' (id=' . ($row->id ?? '?') . ')');
                    return (string) $row->{$key};
                }
            } catch (\Throwable $e) {
                log_message('error', '[Notification] Gagal mencari ' . $key . ' di tabel ' . $table . ': ' . $e->getMessage());
            }
        }

        return null;
    }

    public function sendTelegram(string $message): bool
    {
        $this->lastError = '';

        $token  = trim((string) ($this->setting->telegram_bot_token ?? ''));
        $chatId = trim((string) ($this->setting->telegram_chat_id ?? ''));

        if ($token === '' || $chatId === '') {
            $this->lastError = $token === ''
                ? 'Bot Token kosong pada baris absensi_setting id=' . ($this->setting->id ?? '?')
                  . ' — isi di Pengaturan > Presensi.'
                : 'Chat ID kosong pada baris absensi_setting id=' . ($this->setting->id ?? '?')
                  . ' — isi di Pengaturan > Presensi.';
            log_message('error', '[Notification] Pesan DILEWATI, tidak ada kredensial Telegram: ' . $this->lastError);
            return false;
        }

        return $this->sendTelegramTo($chatId, $message);
    }

    /**
     * Kirim ke chat_id tertentu (grup maupun personal).
     */
    public function sendTelegramTo(string $chatId, string $message): bool
    {
        $this->lastError = '';
        $token = trim((string) ($this->setting->telegram_bot_token ?? ''));

        if ($token === '' || $chatId === '') {
            $this->lastError = $token === ''
                ? 'Bot Token kosong pada baris absensi_setting id=' . ($this->setting->id ?? '?') . '.'
                : 'Chat ID tujuan kosong.';
            log_message('error', '[Notification] Pesan DILEWATI: ' . $this->lastError);
            return false;
        }

        try {
            $client = \Config\Services::curlrequest();
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $response = $client->post($url, [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ],
                'timeout' => 10,
                // Jangan FAILONERROR: body 4xx memuat alasan Telegram (chat not found, dsb.)
                'http_errors' => false,
            ]);
            $code = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            if (($body['ok'] ?? false) === true) {
                return true;
            }
            $this->lastError = $this->translateTelegramError($code, (string) ($body['description'] ?? ''));
            log_message('error', 'Telegram send gagal (HTTP ' . $code . '): ' . ($body['description'] ?? 'tanpa keterangan'));
            return false;
        } catch (\Throwable $e) {
            $this->lastError = 'Server tidak bisa menghubungi api.telegram.org (' . $e->getMessage() . ')';
            log_message('error', 'Telegram notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Terjemahkan error Bot API menjadi instruksi yang bisa ditindaklanjuti.
     */
    private function translateTelegramError(int $code, string $description): string
    {
        $d = strtolower($description);
        if ($code === 401 || str_contains($d, 'unauthorized')) {
            return 'Bot Token tidak valid / dicabut. Buat ulang di @BotFather dan simpan ulang.';
        }
        if (str_contains($d, 'chat not found')) {
            return 'Chat ID tidak ditemukan. Grup kemungkinan sudah upgrade ke supergroup sehingga ID berubah (biasanya jadi -100...). Kirim pesan apa pun di grup lalu cek ID terbaru via @getmyid_bot, atau jadikan bot admin grup/channel.';
        }
        if (str_contains($d, 'bot was blocked') || str_contains($d, 'bot was kicked') || $code === 403) {
            return 'Bot diblokir / dikeluarkan / bukan anggota. Untuk user: Start bot dulu. Untuk grup/channel: masukkan bot dan jadikan admin bila perlu.';
        }
        if (str_contains($d, 'not enough rights') || str_contains($d, 'need administrator')) {
            return 'Bot kurang hak akses di grup/channel. Jadikan bot sebagai admin.';
        }
        if (str_contains($d, 'message is too long')) {
            return 'Pesan terlalu panjang untuk satu pengiriman Telegram.';
        }
        if ($description !== '') {
            return 'Telegram menolak (HTTP ' . $code . '): ' . $description;
        }
        return 'Telegram menolak pengiriman (HTTP ' . $code . ').';
    }

    /**
     * Pabrik model notifikasi.
     *
     * Dipisah supaya test bisa menyuntikkan model tiruan tanpa harus
     * menyiapkan tabel — logika pemilihan kanal yang diuji tetap yang
     * produksi, bukan versi terpisah.
     */
    protected function notifModel(): \App\Models\UserNotifikasiModel
    {
        return new \App\Models\UserNotifikasiModel();
    }

    /**
     * Pabrik klien OpenWA.
     *
     * Kiriman personal memakai instance sendiri (bukan sendWhatsApp(),
     * yang selalu menembak wa_target_number) karena tujuannya per-guru.
     * Pabriknya dipisah supaya test bisa menyuntikkan stub tanpa jaringan.
     */
    protected function openwaService(): \App\Services\OpenWAService
    {
        return new \App\Services\OpenWAService($this->setting);
    }

    /**
     * Kanal personal dalam urutan prioritas.
     *
     * WhatsAppdicoba lebih dulu, Telegram jadi cadangan. Alasannya: OpenWA
     * berjalan di server sekolah, jadi kanal itu yang paling stabil — tidak
     * bergantung pada token bot Telegram yang bisa dicabut atau kehabisan
     * masa berlaku. Telegram tetap dijaga sebagai jaring pengaman.
     *
     * @return array{ok:bool, kanal:string, alasan:string}
     */
    public function personalChannels(int $userId): array
    {
        if ($userId <= 0) {
            return ['ok' => false, 'kanal' => '', 'alasan' => 'user_id tidak valid'];
        }

        $try = [];

        // 1. WhatsApp — kanal utama.
        try {
            $waNumber = $this->notifModel()->getVerifiedWaNumber($userId);
            if ($waNumber !== '') {
                if ($this->waMode() === 'openwa') {
                    $try['whatsapp'] = ['nomor' => $waNumber, 'aktif' => true];
                } else {
                    $try['whatsapp'] = ['nomor' => $waNumber, 'aktif' => false, 'alasan' => 'kanal WhatsApp tidak aktif'];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca nomor WA personal: ' . $e->getMessage());
        }

        // 2. Telegram — cadangan.
        try {
            $row = $this->notifModel()->getByUserId($userId);
            $chatId = (string) ($row->telegram_chat_id ?? '');
            if ($chatId !== '') {
                $try['telegram'] = ['nomor' => $chatId, 'aktif' => true];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca chat id Telegram: ' . $e->getMessage());
        }

        return $try;
    }

    /**
     * Kirim personal ke kanal tertaut user (hormati preferensi).
     * Tetap kirim broadcast grup via notify() oleh pemanggil bila perlu.
     *
     * Urutan kanal sesuai aturan fallback:
     *   1. Ada WA dan Telegram  → WhatsApp dulu, Telegram kalau WA gagal
     *   2. Hanya WA             → langsung WhatsApp
     *   3. Hanya Telegram       → langsung Telegram
     *   4. Tidak ada keduanya   → grup (bila $bolehGrup)
     *
     * Kalau kanal pertama menolak, method ini mencoba kanal berikutnya —
     * bukan berhenti di kegagalan pertama — supaya notifikasi tidak hilang.
     *
     * Catatan pada kasus 4: pesan yang tadinya pribadi akan ikut terekpos ke
     * grup, jadi seluruh guru bisa membacanya — dan lewat sendTelegram/
     * sendWhatsApp mentah, artinya menembus channel config jenis itu.
     * Karena itu semua notify*() di file ini yang sudah memanggil notifyGrup()
     * lebih dulu untuk pesan yang sama memakai $bolehGrup=false; fallback grup
     * hanya untuk pemanggil luar yang memang tidak punya jalur broadcast
     * sendiri.
     *
     * @param bool $bolehGrup true = boleh jatuh ke grup saat tidak ada
     *                         kanal personal. False untuk pesan
     *                         diagnostik (tombol "tes kirim") yang tidak
     *                         boleh menyiarkan apa pun ke grup.
     *
     * @return bool true bila terkirim ke kanal personal atau ke grup
     */
    public function notifyUser(int $userId, string $message, string $prefKey = '', bool $bolehGrup = true): bool
    {
        $this->lastChannel = '';

        if ($userId <= 0) {
            return false;
        }

        try {
            $model = $this->notifModel();

            if ($prefKey !== '') {
                $prefs = $model->getPrefs($userId);
                if (empty($prefs[$prefKey])) {
                    $this->lastError   = 'Guru mematikan preferensi "' . $prefKey . '".';
                    $this->lastChannel = 'preferensi-mati';

                    return false;
                }
            }

            $alasan = [];

            foreach ($this->personalChannels($userId) as $kanal => $info) {
                if (! $info['aktif']) {
                    $alasan[] = ($info['alasan'] ?? ($kanal . ' tidak terkonfigurasi'));
                    continue;
                }

                if ($kanal === 'whatsapp') {
                    $openwa = $this->openwaService();
                    if ($openwa->sendText($info['nomor'], $message)) {
                        $this->lastChannel = 'whatsapp';

                        return true;
                    }
                    $this->lastError = $openwa->lastError;
                    log_message('error', '[notifyUser] WA gagal, coba Telegram: ' . $openwa->lastError);
                    $alasan[] = 'WhatsApp: ' . $openwa->lastError;
                    continue;
                }

                if ($this->sendTelegramTo($info['nomor'], $message)) {
                    $this->lastChannel = 'telegram';

                    return true;
                }
                $alasan[] = 'Telegram: ' . ($this->lastError !== '' ? $this->lastError : 'ditolak tanpa keterangan');
                log_message('error', '[notifyUser] Telegram gagal: ' . $this->lastError);
            }

            // Tidak ada kanal personal yang berhasil — atau memang tidak ada.
            if (! $bolehGrup) {
                $this->lastError   = $alasan === []
                    ? 'Tidak ada kanal personal yang tertaut.'
                    : implode(' | ', $alasan);
                $this->lastChannel = 'tidak-terkirim';

                return false;
            }

            log_message('info', '[notifyUser] user ' . $userId
                . ' tidak punya kanal personal yang berhasil, pesan dialihkan ke grup.');

            $grupWa = $this->sendWhatsApp($message);
            $waErr  = $this->lastError;

            if ($grupWa || $this->sendTelegram($message)) {
                $this->lastChannel = 'grup';
                $this->lastError   = '';

                return true;
            }

            $this->lastError = implode(' | ', array_filter([
                $alasan === [] ? 'Tidak ada kanal personal yang tertaut.' : implode(' | ', $alasan),
                'WhatsApp grup: ' . ($waErr !== '' ? $waErr : 'ditolak'),
                'Telegram grup: ' . ($this->lastError !== '' ? $this->lastError : 'ditolak'),
            ]));
            $this->lastChannel = 'tidak-terkirim';

            return false;
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi personal gagal: ' . $e->getMessage());
            $this->lastChannel = 'tidak-terkirim';

            return false;
        }
    }

    /**
     * Kanal yang dipakai kiriman personal terakhir: 'whatsapp' | 'telegram'
     * | 'grup' | 'preferensi-mati' | 'tidak-terkirim' | '' (belum kirim).
     *
     * Dibedakan dari nilai balik bool karena keduanya sama-sama `true`,
     * sementara pemanggil perlu tahu pesan yang gagal terkirim ke DM
     * ternyata sampai sebagai pengumuman grup (lihat GuruTerawalService).
     */
    public string $lastChannel = '';

    /**
     * Username bot (tanpa @), di-cache 24 jam. Dipakai untuk deep-link t.me.
     */
    public function getBotUsername(): string
    {
        $token = $this->setting->telegram_bot_token ?? '';
        if (empty($token)) {
            return '';
        }
        try {
            $cache = \Config\Services::cache();
            $cached = $cache->get('tg_bot_username');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
            $client = \Config\Services::curlrequest();
            $response = $client->get("https://api.telegram.org/bot{$token}/getMe", ['timeout' => 10]);
            $body = json_decode($response->getBody(), true);
            $username = (string) ($body['result']['username'] ?? '');
            if ($username !== '') {
                $cache->save('tg_bot_username', $username, 86400);
            }
            return $username;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Status webhook dari sisi Telegram (url terdaftar, antrean, error terakhir).
     * Dipakai halaman admin untuk diagnosis "bot diam".
     *
     * @return array{ok: bool, url?: string, pending?: int, last_error?: string, error?: string}
     */
    public function getWebhookInfo(): array
    {
        $token = $this->setting->telegram_bot_token ?? '';
        if (empty($token)) {
            return ['ok' => false, 'error' => 'Bot Token belum diisi.'];
        }
        try {
            $client = \Config\Services::curlrequest();
            $response = $client->get("https://api.telegram.org/bot{$token}/getWebhookInfo", [
                'timeout' => 10,
                'http_errors' => false,
            ]);
            $body = json_decode($response->getBody(), true);
            if (($body['ok'] ?? false) !== true) {
                return ['ok' => false, 'error' => 'Token ditolak Telegram: ' . ($body['description'] ?? 'Unauthorized')];
            }
            $r = $body['result'] ?? [];
            $err = '';
            if (!empty($r['last_error_message'])) {
                $err = (string) $r['last_error_message'];
                if (!empty($r['last_error_date'])) {
                    $err .= ' (' . date('d/m/Y H:i', (int) $r['last_error_date']) . ')';
                }
            }
            return [
                'ok'        => true,
                'url'       => (string) ($r['url'] ?? ''),
                'pending'   => (int) ($r['pending_update_count'] ?? 0),
                'last_error' => $err,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Server tidak bisa menghubungi api.telegram.org (' . $e->getMessage() . ')'];
        }
    }

    /**
     * Kanal WhatsApp aktif: 'off' | 'openwa' | 'fonnte'.
     *
     * Default 'fonnte' bila nilainya kosong, karena sebelum OpenWA ada
     * hanya Fonnte yang dipakai dan kolom mode belum pernah diisi —
     * installations lama tidak boleh kehilangan notifikasi WAnya.
     */
    public function waMode(): string
    {
        $mode = strtolower(trim((string) ($this->setting->wa_gateway_mode ?? '')));
        if ($mode === '') {
            $mode = $this->hasLegacyFonnte() ? 'fonnte' : 'off';
        }

        return in_array($mode, ['off', 'openwa', 'fonnte'], true) ? $mode : 'off';
    }

    private function hasLegacyFonnte(): bool
    {
        return trim((string) ($this->setting->wa_token ?? '')) !== ''
            && trim((string) ($this->setting->wa_target_number ?? '')) !== '';
    }

    /**
     * Kirim ke WhatsApp sesuai kanal aktif di Pengaturan.
     *
     * Mode 'openwa' memakai OpenWAService (session-aware, HTML dikonversi ke
     * markup WhatsApp). Mode 'fonnte' memakai jalur HTTP lama apa adanya.
     * Best-effort: kegagalan tidak pernah dilempar ke pemanggil.
     */
    public function sendWhatsApp(string $message): bool
    {
        $mode = $this->waMode();

        if ($mode === 'off') {
            return false;
        }

        if ($mode === 'openwa') {
            $target = trim((string) ($this->setting->wa_target_number ?? ''));
            if ($target === '') {
                $this->lastError = 'Nomor/grup tujuan WhatsApp belum diisi di Pengaturan > Notifikasi.';
                log_message('error', '[Notification] WhatsApp DILEWATI: ' . $this->lastError);
                return false;
            }
            $openwa = $this->openwaService();
            $ok = $openwa->sendText($target, $message);
            if (! $ok) {
                $this->lastError = $openwa->lastError;
            }
            return $ok;
        }

        return $this->sendFonnte($message);
    }

    /**
     * Jalur Fonnte yang sudah ada sebelumnya, dipertahankan supaya
     * instalasi yang tidak pindah ke OpenWA tidak ikut rusak.
     */
    private function sendFonnte(string $message): bool
    {
        $url    = trim((string) ($this->setting->wa_gateway_url ?? ''));
        $token  = trim((string) ($this->setting->wa_token ?? ''));
        $target = trim((string) ($this->setting->wa_target_number ?? ''));

        if ($url === '' || $token === '' || $target === '') {
            $this->lastError = 'Kredensial Fonnte (URL / token / nomor tujuan) belum lengkap.';
            log_message('error', '[Notification] WhatsApp DILEWATI: ' . $this->lastError);
            return false;
        }

        try {
            $response = \Config\Services::curlrequest()->post($url, [
                'headers' => ['Authorization' => $token],
                'json'    => [
                    'target'      => $target,
                    'message'     => \App\Services\WhatsAppFormatter::toWhatsApp($message, false),
                    'countryCode' => '62',
                ],
                'timeout'      => 10,
                // Fonnte selalu balas 200 walau isinya menandai gagal.
                'http_errors'  => false,
            ]);
            $body = json_decode($response->getBody(), true);
            if (($body['status'] ?? false) === true) {
                return true;
            }
            $this->lastError = 'Fonnte menolak: ' . (string) ($body['reason'] ?? 'tanpa keterangan');
            log_message('error', 'WhatsApp notification failed: ' . $this->lastError);
            return false;
        } catch (\Throwable $e) {
            $this->lastError = 'Tidak bisa menghubungi gateway Fonnte (' . $e->getMessage() . ')';
            log_message('error', 'WhatsApp notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Broadcast ke grup/channel Telegram (bila dikonfigurasi) dan WhatsApp.
     *
     * Nilai balik tetap mengikuti Telegram, bukan "kanal mana pun yang
     * berhasil": pemanggil cron memakai nilai ini untuk mencatat apakah
     * notifikasi benar-benar sampai, dan membiarkannya jadi longgar
     * akan menutupi Telegram yang sedang mati.
     *
     * @return bool true bila Telegram menerimanya. $lastError diisi bila gagal
     *              supaya pemanggil (cron) bisa mencatat kegagalan yang jujur.
     */
    public function notify(string $message): bool
    {
        $sent = $this->sendTelegram($message);
        $telegramError = $this->lastError;

        $waSent = $this->sendWhatsApp($message);
        if (! $waSent && $this->waMode() !== 'off' && $this->lastError !== '') {
            // Jangan timpa lastError Telegram — pemanggil membacanya sebagai
            // alasan kegagalan utama.
            log_message('error', '[Notification] WhatsApp gagal: ' . $this->lastError);
        }
        $this->lastError = $telegramError;

        return $sent;
    }

    /**
     * Guru check-in melewati batas toleransi.
     *
     * @param int|null $userId Bila diisi, guru itu juga diberi tahu
     *                      secara personal. Parameter ini pernah ada di
     *                      signature tapi TIDAK pernah dipakai di badan
     *                      method — jadi guru yang telat tidak pernah
     *                      diberi tahu meski pemanggil sudah mengirim
     *                      user_id-nya.
     */
    public function notifyLateCheckin(string $namaGuru, string $jam, int $menitTelat, ?int $userId = null): void
    {
        $msg = "⚠️ <b>KETERLAMBATAN</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Jam: " . $this->plain($jam) . "\n"
             . "Telat: {$menitTelat} menit\n"
             . "Waktu: " . date('d/m/Y H:i');

        $this->notifyGrup('keterlambatan', $msg);

        // Personal tidak boleh fallback ke grup: pesan keterlambatan bisa
        // terjadi belasan kali sehari, membanjiri grup.
        if ($userId) {
            $this->notifyPersonal('keterlambatan', $userId, $msg, 'kehadiran', false);
        }
    }

    public function notifyAlpha(string $namaGuru, int $hariAlpha, string $bulanTahun, ?int $userId = null): void
    {
        $msg = "🔴 <b>ALPA / TIDAK HADIR</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Hari: {$hariAlpha} hari\n"
             . "Periode: " . $this->plain($bulanTahun) . "\n"
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('alpha', $msg);
        if ($userId) {
            $this->notifyPersonal('alpha', $userId, $msg, 'alpha', false);
        }
    }

    /**
     * Guru mengajukan izin/sakit/cuti — kabari grup agar admin tahu ada
     * antrean yang perlu ditinjau._best-effort: tidak boleh menggagalkan
     * pengajuan bila Telegram tidak bisa dihubungi.
     */
    public function notifyPermitSubmitted(string $namaGuru, string $jenis, string $tglMulai, string $tglSelesai, int $hari, ?string $keterangan = null, ?int $userId = null): void
    {
        $jenisLabel = match (strtolower($jenis)) {
            'sakit' => 'Sakit',
            'cuti'  => 'Cuti',
            'dinas' => 'Dinas',
            default => 'Izin',
        };

        $msg = "📨 <b>PENGAJUAN BARU — {$jenisLabel}</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Jenis: {$jenisLabel}\n"
             . "Tanggal: {$tglMulai} s/d {$tglSelesai} ({$hari} hari)\n";

        $ket = trim((string) $keterangan);
        if ($ket !== '') {
            $msg .= "Alasan: " . $this->clip(mb_strimwidth($this->plain($ket), 0, 200, '…')) . "\n";
        }

        $msg .= "─────────────────\n"
             . "Menunggu persetujuan di aplikasi.";

        $this->notifyGrup('izin', $msg);

        if ($userId) {
            $this->notifyPersonal('izin', $userId, $msg, 'izin', false);
        }
    }

    /**
     * Jurnal mengajar masuk antrean validasi → beri tahu guru piket yang
     * bertugas hari itu, supaya jurnal tidak menunggu tanpa kabar.
     *
     * Hanya kanal personal, tidak di-broadcast ke grup: isinya pekerjaan satu
     * guru dan grup berisi banyak orang. Kegagalan kirim tidak menghentikan
     * penyimpanan jurnal (dipanggil dalam try/catch oleh pemanggil).
     *
     * @param list<int> $idUserPiket
     * @return int banyak notifikasi terkirim
     */
    public function notifyJurnalMenungguValidasi(
        string $namaGuru,
        string $tanggal,
        int $jumlahSesi,
        array $idUserPiket,
        bool $validasiUlang = false
    ): int {
        $msg = ($validasiUlang ? "🔁 <b>JURNAL PERLU DIVALIDASI ULANG</b>\n" : "📕 <b>JURNAL MENUNGGU VALIDASI</b>\n")
             . "Guru: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . "Menunggu validasi: " . $jumlahSesi . " sesi\n"
             . "─────────────────\n"
             . "Validasi di aplikasi: menu Piket → Laporan Harian.";

        $kirim = 0;
        foreach ($idUserPiket as $idUser) {
            $idUser = (int) $idUser;
            if ($idUser <= 0) {
                continue;
            }
            if ($this->notifyUser($idUser, $msg, '', false)) {
                $kirim++;
            }
        }

        return $kirim;
    }

    /**
     * Guru mengajukan koreksi jam absensi.
     */
    public function notifyKoreksiSubmitted(string $namaGuru, string $tanggal, string $fieldLabel, string $oldValue, string $newValue, ?string $reason = null, ?int $userId = null): void
    {
        $msg = "🛠 <b>PENGAJUAN KOREKSI BARU</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: {$tanggal}\n"
             . "Field: {$fieldLabel}\n"
             . "Sebelum: <code>" . $this->clip($this->plain($oldValue)) . "</code>\n"
             . "Sesudah: <code>" . $this->clip($this->plain($newValue)) . "</code>\n";

        $ket = trim((string) $reason);
        if ($ket !== '') {
            $msg .= "Alasan: " . $this->clip(mb_strimwidth($this->plain($ket), 0, 200, '…')) . "\n";
        }

        $msg .= "─────────────────\n"
             . "Menunggu persetujuan di aplikasi.";

        $this->notifyGrup('koreksi', $msg);

        if ($userId) {
            $this->notifyPersonal('koreksi', $userId, $msg, 'koreksi', false);
        }
    }

    /**
     * Kurasi hasil koreksi (tanggal + jam) supaya muat di label Telegram.
     */
    public function normalizeKoreksiValue(string $value): string
    {
        $v = trim($value);
        if ($v === '' || $v === '0000-00-00 00:00:00') {
            return '(kosong)';
        }
        // Ambil bagian jam saja bila formatnya datetime.
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}(?::\d{2})?)/', $v, $m) === 1) {
            return date('d/m/Y', strtotime($m[1])) . ' ' . substr($m[2], 0, 5);
        }
        return $v;
    }

    /**
     * Label ramah untuk nama kolom absensi_harian.
     */
    public function koreksiFieldLabel(string $field): string
    {
        return match ($field) {
            'jam_masuk'  => 'Jam Masuk',
            'jam_pulang' => 'Jam Pulang',
            default      => $field,
        };
    }

    /**
     * Hilangkan karakter yang akan merusak parse_mode HTML Telegram.
     * Nama & alasan guru masih boleh bold/italic, sisanya di-escape.
     */
    private function plain(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function clip(string $text, int $max = 40): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return mb_substr($text, 0, $max) . '…';
    }

    /**
     * Normalisasi tanggal menjadi d/m/Y. Absen dari DB biasanya 'Y-m-d'
     * (atau 'Y-m-d H:i:s'), sementara notifikasi lain sudah d/m/Y —
     * tanpa ini satu pesan menampilkan 2026-09-30 di sebelah 30/09/2026.
     */
    private function tglLabel(?string $tanggal): string
    {
        $t = trim((string) $tanggal);
        if ($t === '') {
            return '-';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $t, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        }
        $ts = strtotime($t);
        return $ts !== false ? date('d/m/Y', $ts) : $this->plain($t);
    }

    public function notifyPermitApproved(string $namaGuru, string $jenis, string $tglMulai, string $tglSelesai, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "✅ <b>" . $this->permitJudul($jenis) . " DISETUJUI</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Jenis: " . $this->permitJenisLabel($jenis) . "\n"
             . "Tanggal: " . $this->tglLabel($tglMulai) . " s/d " . $this->tglLabel($tglSelesai) . "\n"
             . $this->catatanBlock($catatan)
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('izin', $msg);
        if ($userId) {
            $this->notifyPersonal('izin', $userId, $msg, 'izin', false);
        }
    }

    public function notifyPermitRejected(string $namaGuru, string $jenis, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "❌ <b>" . $this->permitJudul($jenis) . " DITOLAK</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Jenis: " . $this->permitJenisLabel($jenis) . "\n"
             . $this->catatanBlock($catatan, 'Alasan: ')
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('izin', $msg);
        if ($userId) {
            $this->notifyPersonal('izin', $userId, $msg, 'izin', false);
        }
    }

    /**
     * Koreksi disetujui — absensi Guru sudah dikoreksi otomatis.
     */
    public function notifyKoreksiApproved(string $namaGuru, string $tanggal, string $fieldLabel, string $oldValue, string $newValue, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "✅ <b>KOREKSI DISETUJUI</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . "Field: " . $this->plain($fieldLabel) . "\n"
             . "Sebelum: <code>" . $this->clip($this->plain($oldValue)) . "</code>\n"
             . "Sesudah: <code>" . $this->clip($this->plain($newValue)) . "</code>\n"
             . $this->catatanBlock($catatan)
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('koreksi', $msg);
        if ($userId) {
            $this->notifyPersonal('koreksi', $userId, $msg, 'koreksi', false);
        }
    }

    /**
     * Koreksi ditolak.
     */
    public function notifyKoreksiRejected(string $namaGuru, string $tanggal, string $fieldLabel, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "❌ <b>KOREKSI DITOLAK</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . "Field: " . $this->plain($fieldLabel) . "\n"
             . $this->catatanBlock($catatan, 'Alasan: ')
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('koreksi', $msg);
        if ($userId) {
            $this->notifyPersonal('koreksi', $userId, $msg, 'koreksi', false);
        }
    }

    /**
     * Verifikasi kehadiran (PENDING_VERIFICATION) diterima admin.
     */
    public function notifyVerifikasiDiterima(string $namaGuru, string $tanggal, string $statusBaru, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "✅ <b>VERIFIKASI KEHADIRAN DITERIMA</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . "Status: " . $this->plain($statusBaru) . "\n"
             . $this->catatanBlock($catatan)
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('koreksi', $msg);
        if ($userId) {
            $this->notifyPersonal('koreksi', $userId, $msg, 'koreksi', false);
        }
    }

    /**
     * Verifikasi kehadiran (PENDING_VERIFICATION) ditolak admin → ALPHA.
     */
    public function notifyVerifikasiDitolak(string $namaGuru, string $tanggal, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "❌ <b>VERIFIKASI KEHADIRAN DITOLAK</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . "Status: ALPHA\n"
             . $this->catatanBlock($catatan, 'Alasan: ')
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('koreksi', $msg);
        if ($userId) {
            $this->notifyPersonal('koreksi', $userId, $msg, 'koreksi', false);
        }
    }

    /**
     * Verifikasi kehadiran tidak ditindak admin sampai batas tinjau lewat →
     * cron menjadikannya ALPHA. Hanya personal: antrean yang mangkrak bisa
     * menimpa banyak guru sekaligus, dan spam grup bukan cara yang sopan untuk
     * memberitahu satu orang bahwa dia dinilai alpa.
     */
    public function notifyVerifikasiKedaluwarsa(string $namaGuru, string $tanggal, int $hariTinjau, ?int $userId = null): void
    {
        $msg = "⏳ <b>VERIFIKASI KEHADIRAN LEWAT BATAS</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . "Status: ALPHA (otomatis)\n"
             . "Alasan: tidak ditindak dalam " . $hariTinjau . " hari\n"
             . "Waktu: " . date('d/m/Y H:i');
        if ($userId) {
            $this->notifyPersonal('koreksi', $userId, $msg, 'koreksi', false);
        } else {
            $this->notifyGrup('koreksi', $msg);
        }
    }

    /**
     * Judul pesan sesuai jenis pengajuan, supaya pengajuan dinas yang
     * dibuat lewat form izin tidak berbunyi "IZIN DISETUJUI".
     */
    private function permitJudul(string $jenis): string
    {
        return match (strtolower(trim($jenis))) {
            'sakit' => 'SAKIT',
            'cuti'  => 'CUTI',
            'dinas' => 'DINAS',
            default => 'IZIN',
        };
    }

    /**
     * Label jenis izin yang rapi (ENUM DB: ijin/sakit/cuti/dinas).
     */
    public function permitJenisLabel(string $jenis): string
    {
        return match (strtolower(trim($jenis))) {
            'sakit' => 'Sakit',
            'cuti'  => 'Cuti',
            'dinas' => 'Dinas',
            'ijin', 'izin' => 'Izin',
            default => $jenis,
        };
    }

    /**
     * Blok catatan admin, dikecilkan agar tidak membanjiri grup.
     */
    private function catatanBlock(?string $catatan, string $prefix = 'Catatan: '): string
    {
        $c = trim((string) $catatan);
        if ($c === '') {
            return '';
        }
        return $prefix . $this->clip(mb_strimwidth($this->plain($c), 0, 200, '…')) . "\n";
    }

    public function notifyDinasApproved(string $namaGuru, string $tanggal, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "✅ <b>DINAS DISETUJUI</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . $this->catatanBlock($catatan)
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('dinas', $msg);
        if ($userId) {
            $this->notifyPersonal('dinas', $userId, $msg, 'izin', false);
        }
    }

    public function notifyWarningLetter(string $namaGuru, int $tingkat, string $bulan, string $tahun, ?int $userId = null): void
    {
        $level = match($tingkat) { 10 => 'III', 7 => 'II', default => 'I' };
        $msg = "📋 <b>SURAT TEGURAN</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tingkat: {$level} ({$tingkat} hari alpha)\n"
             . "Periode: " . $this->plain($bulan) . "/{$tahun}\n"
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('teguran', $msg);
        if ($userId) {
            $this->notifyPersonal('teguran', $userId, $msg, 'teguran', false);
        }
    }

    /**
     * Surat panggilan siswa terbit — kirim ke grup sekolah dan wali kelas
     * terkait secara personal.
     *
     * @param array<int, array{id_siswa:int, level:int, alpha:int, terlambat:int, id_user_wali?:int}> $daftar
     * @return int banyak notifikasi personal (ke wali kelas) yang terkirim
     */
    public function notifySuratPanggilan(array $daftar, string $bulan, string $tahun): int
    {
        $kirim = 0;

        // ── Grup sekolah ──────────────────────────────────
        $count = count($daftar);
        $msg   = "📋 <b>SURAT PANGGILAN ORANG TUA</b>\n"
             . "Bulan: " . $this->plain($bulan) . "/{$tahun}\n"
             . "Jumlah: {$count} siswa\n"
             . "Waktu: " . date('d/m/Y H:i');

        foreach ($daftar as $d) {
            $msg .= "\n• Tingkat " . (int) $d['level']
                  . " — Alpha " . (int) $d['alpha']
                  . ", Terlambat " . (int) $d['terlambat'];
        }

        $this->notifyGrup('panggilan', $msg);

        // ── Personal ke wali kelas ────────────────────────
        // Grupkan per wali agar satu DM berisi semua muridnya.
        $perWali = [];
        foreach ($daftar as $d) {
            $uid = (int) ($d['id_user_wali'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $perWali[$uid][] = $d;
        }

        foreach ($perWali as $idUser => $items) {
            $msg = "📋 <b>SURAT PANGGILAN ORANG TUA — Wali Kelas</b>\n"
                 . "Bulan: " . $this->plain($bulan) . "/{$tahun}\n"
                 . "Berikut siswa di kelas Anda yang mendapat surat panggilan:\n\n";

            foreach ($items as $it) {
                $msg .= "• Tingkat " . (int) $it['level']
                      . " — Alpha " . (int) $it['alpha']
                      . ", Terlambat " . (int) $it['terlambat']
                      . " (siswa #" . (int) $it['id_siswa'] . ")\n";
            }

            $msg .= "\nSilakan hubungi orang tua/wali siswa terkait.\n"
                 . "Waktu: " . date('d/m/Y H:i');

            if ($this->notifyPersonal('panggilan', $idUser, $msg, 'panggilan', false)) {
                $kirim++;
            }
        }

        return $kirim;
    }

    /**
     * Guru masuk tapi belum check-out sampai akhir hari.
     *
     * @return bool true bila broadcast benar-benar diterima
     */
    public function notifyForgotCheckout(string $namaGuru, string $tanggal): bool
    {
        // Angka ini harus identik dengan yang dipakai cron memotong
        // (CronController::penaltiGuruLupaPulang) — pengingat yang menjanjikan
        // -5 lalu memotong -3 membuat guru berdebat dengan admin, dan
        // sebaliknya membuat peringatan ini tidak ditanggapi. 0 = aturan
        // dimatikan di Pengaturan Presensi, jadi tidak ada yang dikutip.
        $poin = max(0, (int) ($this->setting->point_lupa_pulang ?? 5));

        $msg = "⚠️ <b>LUPA ABSEN PULANG</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n";

        if ($poin > 0) {
            $msg .= "Poin: -{$poin} bila jam pulang tetap kosong\n";
        }

        $msg .= "─────────────────\n";
        if ($poin > 0) {
            $msg .= "Segera absen pulang di aplikasi.\n";
        } else {
            $msg .= "Segera absen pulang di aplikasi.\n";
        }

        $msg .= "Waktu: " . date('d/m/Y H:i');

        return $this->notifyGrup('lupa_checkout', $msg);
    }

    public function notifyDenda(string $namaGuru, float $jumlah, string $keterangan, ?int $userId = null): void
    {
        $msg = "💰 <b>DENDA BARU</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Jumlah: Rp " . number_format($jumlah, 0, ',', '.') . "\n"
             . "Keterangan: " . $this->plain($keterangan) . "\n"
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('denda', $msg);
        if ($userId) {
            $this->notifyPersonal('denda', $userId, $msg, 'denda', false);
        }
    }

    public function notifyDinasRejected(string $namaGuru, string $tanggal, ?int $userId = null, ?string $catatan = null): void
    {
        $msg = "❌ <b>DINAS DITOLAK</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n"
             . $this->catatanBlock($catatan, 'Alasan: ')
             . "Waktu: " . date('d/m/Y H:i');
        $this->notifyGrup('dinas', $msg);
        if ($userId) {
            $this->notifyPersonal('dinas', $userId, $msg, 'izin', false);
        }
    }

    /**
     * Guru absen dengan Mode Dinas (status DINAS_PENDING) — kabari grup agar
     * admin tahu ada antrean verifikasi dinas yang baru masuk.
     * Best-effort: kegagalan Telegram tidak boleh menggagalkan check-in.
     */
    public function notifyDinasSubmitted(string $namaGuru, string $tanggal, ?string $keterangan = null, ?int $userId = null): void
    {
        $msg = "🚗 <b>PENGAJUAN DINAS BARU</b>\n"
             . "Nama: " . $this->plain($namaGuru) . "\n"
             . "Tanggal: " . $this->tglLabel($tanggal) . "\n";

        $ket = trim((string) $keterangan);
        if ($ket !== '') {
            $msg .= "Keterangan: " . $this->clip(mb_strimwidth($this->plain($ket), 0, 200, '…'), 200) . "\n";
        }

        $msg .= "─────────────────\n"
             . "Menunggu verifikasi admin.";

        $this->notifyGrup('dinas', $msg);
        if ($userId) {
            $this->notifyPersonal('dinas', $userId, $msg, 'izin', false);
        }
    }

    /**
     * Daftar guru dengan kehadiran di bawah standar.
     *
     * @return bool true bila broadcast benar-benar diterima
     */
    public function notifyKehadiranRendah(array $guruList, float $minPersen, string $periode): bool
    {
        if (empty($guruList)) {
            return true;
        }
        $lines = array_map(fn($g) => '• ' . $this->plain((string) $g), $guruList);
        $msg = "📉 <b>KEHADIRAN DI BAWAH STANDAR ({$minPersen}%)</b>\n"
             . "Periode: " . $this->plain($periode) . "\n"
             . "─────────────────\n"
             . implode("\n", $lines) . "\n"
             . "─────────────────\n"
             . "Segera tindak lanjuti sesuai SOP sekolah.";

        return $this->notifyGrup('kehadiran_rendah', $msg);
    }

    /**
     * Pengumuman guru yang absen paling awal.
     *
     * Dikirim dua kanal: DM personal ke guru tersebut (Telegram bila
     * tertaut, WhatsApp sebagai gantinya) dan pengumuman ke grup/broadcast.
     * Keduanya dilaporkan terpisah supaya admin tahu bagian mana yang gagal.
     *
     * Sengaja memakai sendTelegram/sendWhatsApp langsung, bukan notify()/
     * notifyUser(): pesan ini punya TEKS BERBEDA untuk grup dan personal,
     * sedangkan notify() mengirim string yang sama ke keduanya.
     *
     * @param  object $peminat  Baris berisi user_id, nama_guru, sapaan, jam_masuk
     * @param  string $chatId    Chat id personal guru, string kosong bila belum tertaut
     * @return array{grup:bool,dm:bool,alasan:string,alasan_dm:string}
     */
    public function notifyGuruTerawal(object $peminat, string $chatId = ''): array
    {
        $nama  = $this->plain((string) ($peminat->nama_guru ?? 'Guru'));
        $sapaan = $this->plain((string) ($peminat->sapaan ?? 'Bapak atau Ibu'));
        $jam   = substr((string) ($peminat->jam_masuk ?? ''), 11, 5);
        $jam   = $this->plain($jam !== '' ? $jam : '-');
        $tgl   = $this->plain($this->tglLabel(substr((string) ($peminat->jam_masuk ?? ''), 0, 10)));

// --- Pesan personal (lebih hangat, langsung ke yang absen)
        $dmTeks = "⭐ <b>TERIMA KASIH SUDAH DATANG PALING AWAL!</b>\n\n"
            . $sapaan . ' ' . $nama . ",\n"
            . "Anda tercatat datang paling awal hari ini pukul <b>{$jam}</b>. "
            . "Kehadiran Anda yang pertama menjadi semangat bagi semuanya. "
            . "Semoga hari ini productive dan menyenangkan!\n\n"
            . "Terima kasih sudah bersekolah hari ini. 🙏";

        // --- Pesan grup (menyebut nama, tanpa memakai sapaan pribadi)
        $grupTeks = "⭐ <b>GURU TERCEPAT HARI INI</b>\n"
            . $this->plain($tgl) . "\n\n"
            . "Selamat pagi, <b>{$nama}</b>!\n"
            . "Anda datang paling awal pukul <b>{$jam}</b>. ⏰\n\n"
            . "Terima kasih sudah menjadi yang pertama hadir hari ini. "
            . "Semangat mengajar, semoga hari Anda menyenangkan! 👏";

        // Grup: Telegram dulu, lalu WhatsApp ke wa_target_number.
        $grupTelegram = $this->isChannelEnabled('guru_terawal', 'tg_grup')
            ? $this->sendTelegram($grupTeks)
            : false;
        $grupWa = $this->isChannelEnabled('guru_terawal', 'wa_grup')
            ? $this->sendWhatsApp($grupTeks)
            : false;
        $grup = $grupTelegram || $grupWa;
        $alasanGrup = $this->lastError;

        // Personal: WhatsApp jadi kanal utama, Telegram cadangan. Diurutkan
        // lewat personalChannels() supaya aturan prioritasnya sama dengan
        // notifyUser() — bukan terpisah di dua tempat.
        $dm        = false;
        $kanalDm   = '';
        $alasanDm  = '';
        $userId    = (int) ($peminat->user_id ?? 0);
        $alasanList = [];

        $kanalList = $this->personalChannels($userId);

        // Parameter $chatId dari pemanggil dipakai kalau user_notifikasi
        // belum punya baris (mis. guru yang tautannya dibuat di luar app).
        if (trim($chatId) !== '' && ! isset($kanalList['telegram'])) {
            $kanalList['telegram'] = ['nomor' => trim($chatId), 'aktif' => true];
        }

        // Terapkan filter channel config untuk guru_terawal
        if (! $this->isChannelEnabled('guru_terawal', 'wa_personal') && isset($kanalList['whatsapp'])) {
            $kanalList['whatsapp']['aktif'] = false;
            $kanalList['whatsapp']['alasan'] = 'WA personal dinonaktifkan untuk jenis guru_terawal';
        }
        if (! $this->isChannelEnabled('guru_terawal', 'tg_personal') && isset($kanalList['telegram'])) {
            $kanalList['telegram']['aktif'] = false;
            $kanalList['telegram']['alasan'] = 'TG personal dinonaktifkan untuk jenis guru_terawal';
        }

        // "Belum menautkan apa pun" dan "sudah mencoba tapi gagal" itu dua
        // kondisi berbeda, dan operator membutuhkannya untuk membedakan
        // "fitur ini belum dipakai" dari "gateway sedang rusak".
        if ($kanalList === []) {
            $alasanDm = 'Guru belum menautkan akun WhatsApp maupun Telegram';
        }

        foreach ($kanalList as $kanal => $info) {
            if ($dm) {
                break;
            }
            if (! $info['aktif']) {
                $alasanList[] = ($info['alasan'] ?? ($kanal . ' tidak aktif'));
                continue;
            }

            if ($kanal === 'whatsapp') {
                $openwa = $this->openwaService();
                if ($openwa->sendText($info['nomor'], $dmTeks)) {
                    $dm      = true;
                    $kanalDm = 'whatsapp';
                    continue;
                }
                $alasanList[] = 'WhatsApp: ' . $openwa->lastError;
                log_message('error', '[GuruTerawal] DM WA gagal, coba Telegram: ' . $openwa->lastError
                    . ' (user_id=' . $userId . ')');
                continue;
            }

            if ($this->sendTelegramTo($info['nomor'], $dmTeks)) {
                $dm      = true;
                $kanalDm = 'telegram';
                continue;
            }
            $alasanList[] = 'Telegram: ' . ($this->lastError !== '' ? $this->lastError : 'ditolak');
        }

        // Aturan fallback ke-4 juga berlaku di sini: tanpa kanal personal,
        // pengumuman pribadi turun ke grup supaya guru tetap tahu dia
        // yang tercepat. Teksnya dibedakan supaya penerima tahu ini
        // pengumuman umum, bukan kiriman pribadi.
        if (! $dm) {
            $teksGrup = $dmTeks
                . "\n\n_(Dikirim ke grup karena belum ada kanal pribadi)_";

            if ($this->sendWhatsApp($teksGrup) || $this->sendTelegram($teksGrup)) {
                $dm      = true;
                $kanalDm = 'grup';
                $alasanDm = '';
                log_message('info', '[GuruTerawal] DM guru ' . $userId
                    . ' tidak ada kanal personal, turun ke grup.');
            } elseif ($alasanDm === '') {
                $alasanDm = $alasanList === [] ? 'tidak terkirim' : implode(' | ', $alasanList);
            }
        }

        if (! $dm) {
            log_message('info', '[GuruTerawal] DM gagal: ' . $alasanDm
                . ' (user_id=' . $userId . ')');
        }

        return [
            'grup'       => $grup,
            'dm'         => $dm,
            // Kanal yang benar-benar dipakai untuk DM. 'grup' berarti
            // pesan pribadi turun ke broadcast karena guru tidak punya
            // kanal personal — pemanggil perlu tahu supaya tidak melapork
            // an sebagai "DM berhasil".
            'kanal_dm'   => $kanalDm,
            'alasan'     => $alasanGrup,
            'alasan_dm'  => $alasanDm,
        ];
    }

    /**
     * Ucapan pagi pukul 06:00. Kalimatnya menyesuaikan status hari tersebut.
     *
     * @param  array $info  ['status' => 'aktif'|'akhir_pekan'|'libur',
     *                       'keterangan' => mis. nama hari libur,
     *                       'tanggal_panjang' => "Rabu, 30 September 2026"]
     * @return bool true bila Telegram menerimanya
     */
    public function notifyUcapanPagi(array $info): bool
    {
        $status     = (string) ($info['status'] ?? HariSekolahService::AKTIF);
        $keterangan = trim((string) ($info['keterangan'] ?? ''));
        $tanggal    = trim((string) ($info['tanggal_panjang'] ?? $this->tglLabel(null)));
        $jamMasuk   = trim((string) ($info['jam_masuk'] ?? ''));

        $kepala = "🌅 <b>SELAMAT PAGI</b>\n" . $this->plain($tanggal);

        if ($status === HariSekolahService::AKHIR_PEKAN) {
            $isi = "Hari Minggu. Selamat beristirahat! Semoga akhir pekan Anda menyenangkan, "
                 . "sehat, dan penuh semangat. 🛋️\n\n"
                 . "🤲 Selamat beristirahat!";
        } elseif ($status === HariSekolahService::LIBUR) {
            $isi = "Hari ini adalah <b>hari libur</b>"
                 . ($keterangan !== '' ? ' — ' . $this->plain($keterangan) : '')
                 . ". Selamat beristirahat! Semoga libur Anda menyenangkan, sehat, "
                 . "dan penuh kegembiraan. 🌿\n\n"
                 . "🤲 Selamat berlibur!";
        } else {
            $isi = "Hari ini adalah <b>hari kerja sekolah</b>. Semoga aktivitas belajar mengajar "
                 . "berjalan lancar, sehat, dan penuh semangat. 💪"
                 . ($jamMasuk !== '' ? "\n\nJam masuk hari ini pukul " . $this->plain($jamMasuk) . '.' : '')
                 . "\n\n🤲 Semoga hari Anda menyenangkan!";
        }

        return $this->notifyGrup('ucapan_pagi', $kepala . "\n\n" . $isi);
    }

    /**
     * Ucapan pulang pukul 15:00.
     *
     * @return bool true bila Telegram menerimanya
     */
    public function notifyUcapanSore(array $info = []): bool
    {
        $tanggal = trim((string) ($info['tanggal_panjang'] ?? $this->tglLabel(null)));

        $msg = "🌇 <b>SELAMAT JALAN</b>\n"
             . $this->plain($tanggal) . "\n\n"
             . "Kegiatan hari ini telah selesai. Hati-hati di perjalanan pulang, "
             . "dan semoga Anda bertemu keluarga dalam keadaan sehat dan bahagia. 🤲";

        return $this->notifyGrup('ucapan_sore', $msg);
    }

    /**
     * Rekap kehadiran harian. Dikirim cron jam 18:00.
     *
     * @return bool true bila Telegram menerimanya
     */
    public function notifyRekapHarian(array $data): bool
    {
        $hadir = $data['hadir'] ?? 0;
        $telat = $data['telat'] ?? 0;
        $izin  = $data['izin']  ?? 0;
        $sakit = $data['sakit'] ?? 0;
        $alpha = $data['alpha'] ?? 0;
        $dinas = $data['dinas'] ?? 0;
        $total = $data['total'] ?? 0;
        // Baris WFH & antrean verifikasi dulu tidak punya baris sendiri, jadi
        // jumlah baris di rekap ini tidak sama dengan Total Guru.
        $wfh   = $data['wfh'] ?? 0;
        $pending = $data['pending'] ?? 0;

        $msg = "📊 <b>REKAP PRESENSI HARIAN</b>\n"
             . "Tanggal: " . date('d/m/Y') . "\n"
             . "─────────────────\n"
             . "Total Guru: {$total}\n"
             . "✅ Hadir Tepat: {$hadir}\n"
             . "⏰ Terlambat:  {$telat}\n"
             . "📋 Izin:        {$izin}\n"
             . "🏥 Sakit:       {$sakit}\n"
             . "🚗 Dinas:       {$dinas}\n"
             . ($wfh > 0 ? "🏠 WFH:         {$wfh}\n" : '')
             . ($pending > 0 ? "⏳ Menunggu verifikasi: {$pending}\n" : '')
             . "❌ Alpha:       {$alpha}\n"
             . "─────────────────";
        return $this->notifyGrup('rekap_harian', $msg);
    }

    /**
     * Kirim rekap denda mingguan (setiap Sabtu).
     *
     * Rekap denda teks BULANAN sudah dihapus (diganti PDF tgl 1 via
     * monthlyPdf): method ini satu-satunya pengirim rekap denda teks.
     *
     * @param array  $data     Ringkasan global: total_denda, total_dibayar, sisa
     * @param array  $details  Detail per guru
     * @param string $periode  Label periode, mis. "22/09/2026 – 28/09/2026"
     * @return bool true bila Telegram menerimanya
     */
    public function notifyRekapDendaMingguan(array $data, array $details, string $periode): bool
    {
        $totalDenda   = number_format((float) ($data['total_denda'] ?? 0), 0, ',', '.');
        $totalDibayar = number_format((float) ($data['total_dibayar'] ?? 0), 0, ',', '.');
        $totalCash    = number_format((float) ($data['total_cash'] ?? 0), 0, ',', '.');
        $totalPoin    = number_format((float) ($data['total_poin'] ?? 0), 0, ',', '.');
        $sisa         = number_format((float) ($data['sisa'] ?? 0), 0, ',', '.');

        $msg = "💰 <b>REKAP DENDA MINGGUAN</b>\n"
             . "Periode: " . $this->plain($periode) . "\n"
             . "─────────────────\n"
             . "Timbul minggu ini: Rp {$totalDenda}\n"
             . "Dibayar minggu ini: Rp {$totalDibayar}\n"
             . "  💵 Cash: Rp {$totalCash}\n"
             . "  💳 Poin: Rp {$totalPoin}\n"
             . "Sisa akumulasi: Rp {$sisa}\n"
             . "─────────────────";

        return $this->notifyGrup('rekap_denda_mingguan', $msg);
    }

    /**
     * Rekap piket harian. Dikirim manual dari tombol "Kirim ke Grup" di
     * /guru/piket/laporan oleh petugas piket hari itu.
     *
     * @param array $data {
     *   @var string $tanggal   Label tanggal, mis. "Jumat, 9 Oktober 2026"
     *   @var string[] $petugas Nama petugas piket
     *   @var array[] $siswa    Per kelas (angka REAL): nama_kelas,total,
     *                          H(hadir tepat),T(telat),S,I,A,masuk,pulang,
     *                          tanpa_pulang,belum
     *   @var array   $siswa_total Subtotal dengan kunci yang sama
     *   @var array[] $siswa_bolos [{nama, kelas, jam}] — tampil hanya bila
     *                          bolos_resmi true (sudah lewat jam pulang)
     *   @var bool    $bolos_resmi Gerbang label bolos
     *   @var int    $guru_hadir_total Jumlah guru hadir
     *   @var int    $guru_hadir_tepat Jumlah hadir tepat waktu
     *   @var int    $guru_hadir_telat Jumlah hadir terlambat
     *   @var array[] $guru_absen  [{nama, alasan, alpha}]
     *   @var string $pengirim  Nama pengirim (petugas yang menekan tombol)
     * }
     * @return bool true bila salah satu kanal grup berhasil
     */
    public function notifyRekapPiket(array $data): bool
    {
        return $this->notifyGrup('rekap_piket', $this->renderRekapPiket($data));
    }

    /**
     * Susun teks rekap piket (tanpa mengirim). Dipakai notifyRekapPiket()
     * dan bisa dipanggil untuk pratinjau/pengujian.
     */
    public function renderRekapPiket(array $data): string
    {
        $msg = "📋 <b>REKAP PIKET HARIAN</b>\n"
             . "🗓 " . $this->plain((string) ($data['tanggal'] ?? date('d/m/Y'))) . "\n";

        $petugas = array_values(array_filter(array_map('trim', (array) ($data['petugas'] ?? []))));
        if (!empty($petugas)) {
            $msg .= "👮 Petugas: " . $this->plain(implode(', ', array_slice($petugas, 0, 5))) . "\n";
        }
        $msg .= "─────────────────\n"
             . "<b>🧑‍🎓 SISWA PER KELAS</b>\n";

        $siswa = (array) ($data['siswa'] ?? []);
        if (empty($siswa)) {
            $msg .= "Belum ada data.\n";
        } else {
            foreach (array_slice($siswa, 0, 20) as $k) {
                $blmPlg = (int) ($k['tanpa_pulang'] ?? 0);
                $blm = (int) ($k['belum'] ?? 0);
                $msg .= "• <b>" . $this->plain((string) ($k['nama_kelas'] ?? '-')) . "</b>"
                     . " (" . (int) ($k['total'] ?? 0) . ")\n"
                     . "  ✅" . (int) ($k['H'] ?? 0) . " ⏰" . (int) ($k['T'] ?? 0)
                     . " 😷" . (int) ($k['S'] ?? 0)
                     . " 📝" . (int) ($k['I'] ?? 0)
                     . " ❌" . (int) ($k['A'] ?? 0) . "\n"
                     . "  🏫 masuk " . (int) ($k['masuk'] ?? 0)
                     . " • pulang " . (int) ($k['pulang'] ?? 0)
                     . ($blmPlg > 0 ? " • blm plg {$blmPlg}" : "")
                     . ($blm > 0 ? " • blm masuk {$blm}" : "") . "\n";
            }
            if (count($siswa) > 20) {
                $msg .= "… dan " . (count($siswa) - 20) . " kelas lainnya\n";
            }
            $t2 = (array) ($data['siswa_total'] ?? []);
            if (!empty($t2)) {
                $sBlmPlg = (int) ($t2['tanpa_pulang'] ?? 0);
                $sBlm = (int) ($t2['belum'] ?? 0);
                $msg .= "<b>Subtotal:</b> ✅" . (int) ($t2['H'] ?? 0)
                     . " ⏰" . (int) ($t2['T'] ?? 0)
                     . " 😷" . (int) ($t2['S'] ?? 0)
                     . " 📝" . (int) ($t2['I'] ?? 0)
                     . " ❌" . (int) ($t2['A'] ?? 0) . "\n"
                     . "masuk " . (int) ($t2['masuk'] ?? 0) . "/" . (int) ($t2['total'] ?? 0)
                     . " • pulang " . (int) ($t2['pulang'] ?? 0)
                     . ($sBlmPlg > 0 ? " • blm plg {$sBlmPlg}" : "")
                     . ($sBlm > 0 ? " • blm masuk {$sBlm}" : "") . "\n";
            }
        }

        $bolos = array_values((array) ($data['siswa_bolos'] ?? []));
        if (!empty($bolos) && !empty($data['bolos_resmi'])) {
            $msg .= "─────────────────\n"
                 . "🏃 <b>BOLOS — MASUK TANPA PULANG (" . count($bolos) . ")</b>\n";
            foreach (array_slice($bolos, 0, 20) as $s) {
                $msg .= "• ❌ " . $this->plain((string) ($s['nama'] ?? 'Siswa'));
                if (trim((string) ($s['kelas'] ?? '')) !== '') {
                    $msg .= " (" . $this->plain((string) $s['kelas']) . ")";
                }
                if (trim((string) ($s['jam'] ?? '')) !== '') {
                    $msg .= " — masuk " . $this->plain(substr((string) $s['jam'], 0, 5));
                }
                $msg .= "\n";
            }
            if (count($bolos) > 20) {
                $msg .= "… dan " . (count($bolos) - 20) . " siswa lainnya\n";
            }
        }

        $hadirTotal = (int) ($data['guru_hadir_total'] ?? 0);
        $hadirTepat = (int) ($data['guru_hadir_tepat'] ?? 0);
        $hadirTelat = (int) ($data['guru_hadir_telat'] ?? 0);
        $msg .= "─────────────────\n"
             . "<b>👨‍🏫 GURU HADIR (" . $hadirTotal . ")</b>\n"
             . "✅ Tepat waktu: " . $hadirTepat . "\n"
             . "⏰ Terlambat: " . $hadirTelat . "\n";

        $absen = array_values((array) ($data['guru_absen'] ?? []));
        $absenAlpha = [];
        $absenIzin = [];
        foreach ($absen as $g) {
            if (!empty($g['alpha'])) {
                $absenAlpha[] = $g;
            } else {
                $absenIzin[] = $g;
            }
        }
        $msg .= "─────────────────\n"
             . "<b>🚫 GURU TIDAK HADIR (" . count($absen) . ")</b>\n";
        if (empty($absen)) {
            $msg .= "Nihil — semua guru hadir. ✅\n";
        } else {
            if (!empty($absenAlpha)) {
                $msg .= "⚠️ <b>ALPHA — PERLU TINDAK LANJUT (" . count($absenAlpha) . ")</b>\n";
                foreach (array_slice($absenAlpha, 0, 30) as $g) {
                    $msg .= "• ❌ <b>" . $this->plain((string) ($g['nama'] ?? 'Guru')) . "</b>\n";
                }
                if (count($absenAlpha) > 30) {
                    $msg .= "… dan " . (count($absenAlpha) - 30) . " guru lainnya\n";
                }
            }
            foreach (array_slice($absenIzin, 0, 30) as $g) {
                $msg .= "• " . $this->plain((string) ($g['nama'] ?? 'Guru'))
                     . " — " . $this->clip($this->plain((string) ($g['alasan'] ?? 'Tanpa keterangan')), 80) . "\n";
            }
            if (count($absenIzin) > 30) {
                $msg .= "… dan " . (count($absenIzin) - 30) . " guru lainnya\n";
            }
        }

        $msg .= "─────────────────";
        if (!empty($data['pengirim'])) {
            $msg .= "\n<i>Dikirim oleh " . $this->plain((string) $data['pengirim']) . " via Laporan Piket</i>";
        }
        return $msg;
    }
}
