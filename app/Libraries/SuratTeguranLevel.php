<?php

namespace App\Libraries;

/**
 * Tingkat surat teguran guru.
 *
 * Satu sumber kebenaran untuk ambang dan nama tingkat. Sebelumnya label
 * "TINGKAT III (10 Hari)" ditulis ulang sebagai if/else di enam lokasi
 * (PresensiAdmin, Attendance, view admin). Begitu ambangnya diubah, satu
 * lokasi lupa diperbarui dan surat yang sama punya dua nama berbeda
 * tergantung halaman mana yang dibuka — termasuk di PDF yang jadi arsip.
 *
 * Nilai `level` di tabel absensi_surat_teguran adalah AMBANG alpha, bukan
 * nomor urut tingkat: 10 = 10 hari alpha, 7 = 7 hari, 3 = 3 hari.
 */
final class SuratTeguranLevel
{
    /**
     * Ambang alpha per tingkat, dari yang terberat.
     *
     * Dipakai juga sebagai urutan evaluasi: surat terbit untuk level
     * terberat yang sudah dilampaui, dan evaluation berhenti di situ.
     *
     * @var array<int,string> [ambang alpha => nama tingkat]
     */
    private const TINGKAT = [
        10 => 'TINGKAT III (10 Hari)',
        7  => 'TINGKAT II (7 Hari)',
        3  => 'TINGKAT I (3 Hari)',
    ];

    /**
     * @return array<int,string> [ambang alpha => nama tingkat]
     */
    public static function list(): array
    {
        return self::TINGKAT;
    }

    /**
     * @return int[] Ambang alpha, dari yang terberat
     */
    public static function ambang(): array
    {
        return array_keys(self::TINGKAT);
    }

    /**
     * Nama tingkat untuk sebuah level.
     *
     * Level di luar daftar (mis. 0 pada baris lama yang tidak menyimpan
     * level) jatuh ke tingkat paling ringan supaya PDF tetap terbaca,
     * bukan kosong.
     */
    public static function label(int $level): string
    {
        if (isset(self::TINGKAT[$level])) {
            return self::TINGKAT[$level];
        }

        $ringan = array_key_last(self::TINGKAT);

        return self::TINGKAT[$ringan];
    }

    /**
     * Level terberat yang sudah dilampaui(alpha saat ini).
     *
     * Null kalau alpha belum mencapai ambang mana pun — artinya guru
     * belum berhak atas surat teguran sama sekali.
     */
    public static function levelUntukAlpha(int $alpha): ?int
    {
        foreach (self::TINGKAT as $ambang => $label) {
            if ($alpha >= $ambang) {
                return $ambang;
            }
        }

        return null;
    }

    /**
     * Apakah surat untuk level ini masih sah?
     *
     * Dipakai saat pembatalan otomatis: alpha yang sudah turun di bawah
     * ambang membuat surat gugur.
     */
    public static function masihSah(int $level, int $alpha): bool
    {
        return $alpha >= $level;
    }
}
