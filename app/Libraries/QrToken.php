<?php

namespace App\Libraries;

/**
 * Token QR rotasi ala TOTP (RFC 4226/HOTP truncation + counter waktu).
 *
 * - Token 6 digit, berganti tiap $interval detik (default 60).
 * - Verifikasi memakai window ±1 langkah agar toleran skew jam & rotasi
 *   yang terjadi tepat saat guru menekan tombol.
 * - Secret TIDAK PERNAH dikirim ke klien; klien hanya menerima token kini.
 */
class QrToken
{
    public const DEFAULT_INTERVAL = 60;
    public const ALLOWED_INTERVALS = [30, 60, 120];

    /**
     * Secret acak baru (40 hex char).
     */
    public static function generateSecret(int $bytes = 20): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Token untuk counter tertentu.
     */
    public static function forCounter(string $secret, int $counter): string
    {
        // 8-byte big-endian (high 32 bit nol — cukup untuk ratusan tahun)
        $msg = "\0\0\0\0" . pack('N', $counter);
        $hash = hash_hmac('sha1', $msg, $secret, true);
        $offset = ord($hash[19]) & 0x0f;
        $code = ((ord($hash[$offset]) & 0x7f) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($code % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Token yang berlaku saat ini.
     */
    public static function current(string $secret, int $interval = self::DEFAULT_INTERVAL, ?int $time = null): string
    {
        $interval = self::normalizeInterval($interval);
        $counter = (int) floor(($time ?? time()) / $interval);

        return self::forCounter($secret, $counter);
    }

    /**
     * Verifikasi token dengan toleransi window langkah.
     */
    public static function verify(string $token, string $secret, int $interval = self::DEFAULT_INTERVAL, int $window = 1, ?int $time = null): bool
    {
        if (!preg_match('/^\d{6}$/', $token) || $secret === '') {
            return false;
        }
        $interval = self::normalizeInterval($interval);
        $counter = (int) floor(($time ?? time()) / $interval);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::forCounter($secret, $counter + $i), $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sisa detik hingga token berganti (untuk UI countdown/polling).
     */
    public static function secondsRemaining(int $interval = self::DEFAULT_INTERVAL, ?int $time = null): int
    {
        $interval = self::normalizeInterval($interval);

        return $interval - (($time ?? time()) % $interval);
    }

    public static function normalizeInterval(int $interval): int
    {
        return in_array($interval, self::ALLOWED_INTERVALS, true) ? $interval : self::DEFAULT_INTERVAL;
    }

    /**
     * Cari event aktif pada tanggal tsb berdasarkan token yang disodorkan:
     * rotating (window ±1) atau static/legacy (samakan persis).
     */
    public static function findEvent($db, string $token, string $date): ?object
    {
        if ($token === '' || !$db->tableExists('absensi_qr')) {
            return null;
        }

        $events = $db->table('absensi_qr')
            ->where('active', 1)
            ->where('tanggal', $date)
            ->get()->getResult();

        foreach ($events as $e) {
            $mode = $e->mode ?? (!empty($e->secret) ? 'rotating' : 'static');
            if ($mode === 'rotating' && !empty($e->secret)) {
                if (self::verify($token, (string) $e->secret, (int) ($e->interval_detik ?? self::DEFAULT_INTERVAL))) {
                    return $e;
                }
            } elseif (hash_equals((string) ($e->token ?? ''), $token)) {
                return $e;
            }
        }

        return null;
    }

    /**
     * Token yang harus ditampilkan/dipakai klien untuk event ini.
     * (Secret tidak pernah keluar dari server.)
     */
    public static function displayToken(object $event): string
    {
        $mode = $event->mode ?? (!empty($event->secret) ? 'rotating' : 'static');
        if ($mode === 'rotating' && !empty($event->secret)) {
            return self::current((string) $event->secret, (int) ($event->interval_detik ?? self::DEFAULT_INTERVAL));
        }

        return (string) ($event->token ?? '');
    }
}
