<?php

namespace App\Libraries;

/**
 * TelegramLink —reuplah agar guru bisa receives notifikasi personal tanpa
 * bergantung pada webhook Telegram.
 *
 * Dua jalur penautan:
 *   1. Deep link  : botNecakai webhook aktif; guru buka t.me/<bot>?start=<token>.
 *   2. Input manual: guru/admin mengetikkan ID Telegram-nya sendiri. Tidak
 *      perlu webhook sama sekali — cukup chat id yang valid.
 *
 * Keduanya menulis ke `user_notifikasi.telegram_chat_id`, kolom yang dibaca
 * NotificationService::notifyUser(). Kolom `master_guru.telegram_id` disimpan
 * sebagai catatan/admin display dan menjadi sumber saat-tautan belum ada.
 */
class TelegramLink
{
    /** Panjang maksimum kolom telegram_chat_id / master_guru.telegram_id. */
    public const MAX_LEN = 50;

    /**
     * Bersihkan & validasi input chat id dari form.
     *
     * Telegram chat id hanya angka, boleh diawali '-' (supergroup/channel).
     * Kita buang '@username' karena Telegram API hanya menerima id numerik.
     *
     * @return string '' bila kosong, string valid bila sah
     * @throws \InvalidArgumentException bila format tidak dikenali
     */
    public static function normalizeChatId(?string $raw): string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return '';
        }

        // Buang spasi/tanda baca yang sering ikut tersalin (@username, spasi).
        $v = str_replace(["\u{00A0}", ' ', '@'], ['', '', ''], $v);

        if (preg_match('/^-?\d{1,20}$/', $v) !== 1) {
            throw new \InvalidArgumentException(
                'ID Telegram harus berupa angka saja (contoh: 123456789 atau -1001234567890).'
            );
        }

        if (mb_strlen($v) > self::MAX_LEN) {
            throw new \InvalidArgumentException('ID Telegram terlalu panjang.');
        }

        return $v;
    }

    /**
     * True bila string sudah berupa chat id yang bisa dikirim ke Bot API.
     */
    public static function isValidChatId(?string $raw): bool
    {
        try {
            return self::normalizeChatId($raw) !== '';
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Terapkan chat id ke guru: tulis ke master_guru.telegram_id dan
     * user_notifikasi.telegram_chat_id sekaligus.
     *
     * Kolom telegram_id hanya ada setelah migration dijalankan; di bawah ini
     * ada lazy-guard supaya instalasi yang belum migrate tidak fatal.
     *
     * @return array{ok:bool, message:string, chat_id:string}
     */
    public static function attachToGuru(int $idGuru, int $userId, ?string $raw): array
    {
        if ($idGuru <= 0) {
            return ['ok' => false, 'message' => 'Data guru tidak ditemukan.', 'chat_id' => ''];
        }
        if ($userId <= 0) {
            return ['ok' => false, 'message' => 'Akun login guru belum tertaut ke master_guru.', 'chat_id' => ''];
        }

        try {
            $chatId = self::normalizeChatId($raw);
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'chat_id' => ''];
        }

        if ($chatId === '') {
            return ['ok' => false, 'message' => 'ID Telegram kosong.', 'chat_id' => ''];
        }

        $db = \Config\Database::connect();
        $model = new \App\Models\UserNotifikasiModel();

        if (! $model->bindChatDirect($userId, $chatId)) {
            // Model menyimpan alasannya di lastBindError; pakai itu supaya
            // pengguna tahu bedanya "ditolak karena sudah dipakai akun
            // lain" vs "gagal menyimpan" — dua masalah yang beda Lisbonya.
            $alasan = trim($model->lastBindError) !== ''
                ? $model->lastBindError
                : 'Gagal menyimpan tautan Telegram.';
            return ['ok' => false, 'message' => $alasan, 'chat_id' => $chatId];
        }

        try {
            if ($db->fieldExists('telegram_id', 'master_guru')) {
                $db->table('master_guru')->where('id_guru', $idGuru)
                    ->update(['telegram_id' => $chatId]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[TelegramLink] gagal menulis master_guru.telegram_id: ' . $e->getMessage());
        }

        return [
            'ok'      => true,
            'message' => 'Tautan Telegram aktif. Notifikasi personal akan dikirim ke ' . $chatId . '.',
            'chat_id' => $chatId,
        ];
    }

    /**
     * Lepas tautan Telegram seorang guru (kosongkan kedua tabel).
     *
     * Nilai balik dulu selalu `true` apa pun yang terjadi, jadi UI
     * melaporkan "Tautan Telegram dilepas" walau penulisan database-nya
     * gagal. Sekarang hasilnya jujur: false berarti ada yang gagal, dan
     * pemanggil bisa memberi tahu pengguna.
     */
    public static function detachFromGuru(int $idGuru, int $userId): bool
    {
        $db = \Config\Database::connect();
        $sukses = true;

        if ($userId > 0) {
            try {
                $row = (new \App\Models\UserNotifikasiModel())->getByUserId($userId);
                if ($row && !empty($row->telegram_chat_id)) {
                    $sukses = (new \App\Models\UserNotifikasiModel())
                        ->unbindByChatId((string) $row->telegram_chat_id) && $sukses;
                }
            } catch (\Throwable $e) {
                log_message('error', '[TelegramLink] gagal melepas user_notifikasi: ' . $e->getMessage());
                $sukses = false;
            }
        }

        try {
            if ($db->fieldExists('telegram_id', 'master_guru') && $idGuru > 0) {
                // Hasil update() sengaja diabaikan: `false` di sana berarti
                // "tidak ada baris yang cocok", bukan error. Yang menentukan
                // kegagalan hanyalah exception di bawah.
                $db->table('master_guru')->where('id_guru', $idGuru)
                    ->update(['telegram_id' => null]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[TelegramLink] gagal mengosongkan master_guru.telegram_id: ' . $e->getMessage());
            $sukses = false;
        }

        return $sukses;
    }

    /**
     * Chat id yang tersimpan untuk seorang guru.
     * Prioritaskan user_notifikasi (sumber runtime), fallback ke master_guru.
     */
    public static function chatIdForGuru(int $idGuru, int $userId): string
    {
        if ($userId > 0) {
            $row = (new \App\Models\UserNotifikasiModel())->getByUserId($userId);
            if ($row && !empty($row->telegram_chat_id)) {
                return (string) $row->telegram_chat_id;
            }
        }

        if ($idGuru > 0) {
            try {
                $db = \Config\Database::connect();
                if ($db->fieldExists('telegram_id', 'master_guru')) {
                    $g = $db->table('master_guru')->select('telegram_id')
                        ->where('id_guru', $idGuru)->get()->getRow();
                    if ($g && !empty($g->telegram_id)) {
                        return (string) $g->telegram_id;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[TelegramLink] gagal membaca master_guru.telegram_id: ' . $e->getMessage());
            }
        }

        return '';
    }
}
