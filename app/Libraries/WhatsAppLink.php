<?php

namespace App\Libraries;

use App\Services\WhatsAppFormatter;

/**
 * WhatsAppLink —reuplah agar guru bisa menerima notifikasi personal lewat
 * WhatsApp, sejajar dengan TelegramLink.
 *
 * Telegram bisa menautkan lewat deep link (`/start <token>`) karena bot-nya
 * punya webhook inbound. OpenWA tidak memberi jalan murah untuk itu pada
 * sisi aplikasi ini, jadi penautannya manual: guru mengetik nomornya sendiri,
 * lalu admin mengonfirmasi di halaman daftar tautan.
 *
 * Konfirmasi itu bukan formalitas. OpenWA membalas 201 untuk nomor mana pun
 * selama formatnya benar, termasuk nomor yang bukan milik orang tersebut —
 * jadi satu digit salah ketik berarti data absensi terkirim ke orang tak
 * dikenal dan tidak ada respons yang memperingatkan. Karena itu hanya
 * nomor dengan wa_verified = 1 yang dibaca NotificationService.
 *
 * Penulisan ganda: `user_notifikasi.wa_number` (sumber runtime, dibaca
 * service notifikasi) dan `master_guru.wa_number` (catatan/display admin,
 * persis seperti telegram_id).
 */
class WhatsAppLink
{
    /**
     * Bersihkan & normalisasi nomor dari form ke digit kode negara.
     *
     * Menerima 0812…, +62812…, 62812…, dan bentuk dengan spasi/strip.
     * Nomor non-Indonesia dianggap Indonesia juga bila tidak diawali 62.
     *
     * @return string '' bila kosong, digit valid bila sah
     * @throws \InvalidArgumentException bila tidak ada digit yang cocok
     */
    public static function normalizeNumber(?string $raw): string
    {
        $digits = WhatsAppFormatter::normalizeDigits($raw);
        if ($digits === '') {
            $raw2 = trim((string) $raw);
            if ($raw2 === '') {
                return '';
            }
            throw new \InvalidArgumentException('Nomor WhatsApp harus berupa angka, contoh: 081234567890');
        }

        // 7 digit adalah batas bawah wajar: terlalu pendek hampir pasti
        // salah ketik dan akan membuang pesan ke nomor asing.
        if (mb_strlen($digits) < 7) {
            throw new \InvalidArgumentException('Nomor WhatsApp terlalu pendek (minimal 7 digit).');
        }
        if (mb_strlen($digits) > 20) {
            throw new \InvalidArgumentException('Nomor WhatsApp terlalu panjang.');
        }

        return $digits;
    }

    public static function isValidNumber(?string $raw): bool
    {
        try {
            return self::normalizeNumber($raw) !== '';
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Terapkan nomor ke guru: tulis ke master_guru.wa_number dan
     * user_notifikasi.wa_number sekaligus.
     *
     * Nomor kosong berarti melepas tautan.
     *
     * @param  bool $verified True bila penganggil sudah berwenang
     *                        mengonfirmasi (admin), sehingga langsung aktif
     * @return array{ok:bool, message:string, number:string}
     */
    public static function attachToGuru(int $idGuru, int $userId, ?string $raw, bool $verified = false): array
    {
        if ($idGuru <= 0) {
            return ['ok' => false, 'message' => 'Data guru tidak ditemukan.', 'number' => ''];
        }
        if ($userId <= 0) {
            return ['ok' => false, 'message' => 'Akun login guru belum tertaut ke master_guru.', 'number' => ''];
        }

        try {
            $number = self::normalizeNumber($raw);
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'number' => ''];
        }

        $db = \Config\Database::connect();
        $model = new \App\Models\UserNotifikasiModel();

        // Nomor kosong = lepas, dan tidak ada yang perlu dikonfirmasi.
        $verified = $verified && $number !== '';

        if (! $model->setWaNumber($userId, $number, $verified)) {
            return ['ok' => false, 'message' => 'Gagal menyimpan tautan WhatsApp.', 'number' => $number];
        }

        self::writeMasterGuru($idGuru, $number);

        if ($number === '') {
            return ['ok' => true, 'message' => 'Tautan WhatsApp dilepas.', 'number' => ''];
        }

        // Baca status SETELAH disimpan, bukan dari parameter $verified.
        // Kalau nomornya tidak berubah, setWaNumber() mempertahankan
        // konfirmasi yang sudah ada — sementara $verified dari form masih
        // bernilai false karena field-nya tidak disentuh. Melaporkan
        // "menunggu konfirmasi" untuk nomor yang sebenarnya sudah aktif
        // membuat admin mengejar konfirmasi yang tidak pernah dibutuhkan.
        $sudahAktif = $model->getVerifiedWaNumber($userId) !== '';

        return [
            'ok'      => true,
            'message' => $sudahAktif
                ? 'Tautan WhatsApp aktif. Notifikasi personal akan dikirim ke +' . $number . '.'
                : 'Nomor tersimpan, menunggu konfirmasi admin di menu Notifikasi.',
            'number'  => $number,
        ];
    }

    /**
     * Lepas tautan WhatsApp seorang guru (kosongkan kedua tabel).
     *
     * Nilai balik jujur: `false` berarti user_notifikasi tidak berhasil
     * dikosongkan, jadi guru bisa tetap menerima kiriman padahal UI sudah
     * bilang tautannya dicabut.
     */
    public static function detachFromGuru(int $idGuru, int $userId): bool
    {
        $sukses = true;

        if ($userId > 0) {
            $sukses = (new \App\Models\UserNotifikasiModel())->unbindWa($userId);
        }

        // master_guru hanya catatan tampilan; kegagalannya tidak membuat
        // guru tetap menerima pesan, jadi tidak dihitung sebagai gagal —
        // tapi tetap dicatat di log.
        self::writeMasterGuru($idGuru, null);

        return $sukses;
    }

    /**
     * Nomor WA yang tersimpan untuk seorang guru.
     * Prioritaskan user_notifikasi (sumber runtime), fallback ke master_guru.
     */
    public static function numberForGuru(int $idGuru, int $userId): string
    {
        if ($userId > 0) {
            $n = (new \App\Models\UserNotifikasiModel())->getWaNumber($userId);
            if ($n !== '') {
                return $n;
            }
        }

        if ($idGuru > 0) {
            try {
                $db = \Config\Database::connect();
                if ($db->fieldExists('wa_number', 'master_guru')) {
                    $g = $db->table('master_guru')->select('wa_number')
                        ->where('id_guru', $idGuru)->get()->getRow();
                    if ($g && ! empty($g->wa_number)) {
                        return (string) $g->wa_number;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[WhatsAppLink] gagal membaca master_guru.wa_number: ' . $e->getMessage());
            }
        }

        return '';
    }

    /**
     * Tulis catatan ke master_guru.wa_number bila kolomnya sudah ada
     * (instalasi yang belum migrate tidak boleh fatal).
     */
    private static function writeMasterGuru(int $idGuru, ?string $number): void
    {
        if ($idGuru <= 0) {
            return;
        }
        try {
            $db = \Config\Database::connect();
            if (! $db->fieldExists('wa_number', 'master_guru')) {
                return;
            }
            $db->table('master_guru')->where('id_guru', $idGuru)
                ->update(['wa_number' => $number === '' ? null : $number]);
        } catch (\Throwable $e) {
            log_message('error', '[WhatsAppLink] gagal menulis master_guru.wa_number: ' . $e->getMessage());
        }
    }
}