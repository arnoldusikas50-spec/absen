<?php

namespace App\Libraries;

/**
 * SuratNomor — penyusun nomor surat teguran dari template yang dapat
 * dikonfigurasi lewat halaman /settings.
 *
 * Template disimpan pada tabel `setting` kolom `surat_teguran_format`.
 * Bila kosong / kolom belum ada, dipakai format bawaan yang sama dengan
 * perilakuhistoris: SP/421.3/{kode_sekolah}/{nomor}/{tahun}
 *
 * Placeholder yang didukung:
 *   {kode_sekolah} {sekolah} {npsn} {nss} {nip_kepsek} {kepsek}
 *   {nomor} {nomor_urut} {bulan} {bulan_romawi} {bulan_nama}
 *   {tahun} {tahun_2} {level} {tanggal}
 */
class SuratNomor
{
    public const DEFAULT_FORMAT = 'SP/421.3/{kode_sekolah}/{nomor_urut}/{tahun}';
    public const DEFAULT_PADDING = 3;
    public const DEFAULT_KODE_SEKOLAH = 'SMAN2.AT';

    private const BULAN_ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** @var array<string,string|null> */
    private static array $cache = [];

    /**
     * Template aktif (tanpa nilai bila kolom belum tersedia).
     */
    public static function formatTemplate(?object $setting = null): string
    {
        $tpl = self::value($setting, 'surat_teguran_format');

        if ($tpl === null) {
            $row = self::row();
            $tpl = $row !== null && isset($row->surat_teguran_format)
                ? (string) $row->surat_teguran_format
                : null;
        }

        $tpl = trim((string) $tpl);

        return $tpl === '' ? self::DEFAULT_FORMAT : $tpl;
    }

    /**
     * Jumlah digit nomor urut (1-6).
     */
    public static function padding(?object $setting = null): int
    {
        $val = self::value($setting, 'surat_teguran_padding');

        if ($val === null) {
            $row = self::row();
            $val = ($row !== null && isset($row->surat_teguran_padding)) ? $row->surat_teguran_padding : null;
        }

        $pad = (int) $val;
        if ($pad < 1 || $pad > 6) {
            $pad = self::DEFAULT_PADDING;
        }

        return $pad;
    }

    /**
     * Kode sekolah. Urutan prioritas: kolom `kode_sekolah` -> singkatan dari
     * nama sekolah -> nilai bawaan.
     */
    public static function kodeSekolah(?object $setting = null): string
    {
        $kode = self::value($setting, 'kode_sekolah');

        if ($kode === null) {
            $row = self::row();
            $kode = ($row !== null && isset($row->kode_sekolah)) ? (string) $row->kode_sekolah : null;
        }

        $kode = trim((string) $kode);
        if ($kode !== '') {
            return $kode;
        }

        return self::DEFAULT_KODE_SEKOLAH;
    }

    /**
     * Rakit nomor surat dari template.
     */
    public static function build(
        int $nomorUrut,
        int $bulan,
        int $tahun,
        ?object $setting = null,
        int $level = 0,
        ?string $tanggal = null
    ): string {
        $setting = $setting ?? self::row();

        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = $tahun > 0 ? $tahun : (int) date('Y');
        $nomor = str_pad((string) max(0, $nomorUrut), self::padding($setting), '0', STR_PAD_LEFT);

        $map = [
            '{kode_sekolah}' => self::kodeSekolah($setting),
            '{sekolah}' => (string) (self::value($setting, 'sekolah') ?? ''),
            '{npsn}' => (string) (self::value($setting, 'npsn') ?? ''),
            '{nss}' => (string) (self::value($setting, 'nss') ?? ''),
            '{kepsek}' => (string) (self::value($setting, 'kepsek') ?? ''),
            '{nip_kepsek}' => (string) (self::value($setting, 'nip') ?? ''),
            '{nomor}' => $nomor,
            '{nomor_urut}' => $nomor,
            '{bulan}' => str_pad((string) $bulan, 2, '0', STR_PAD_LEFT),
            '{bulan_romawi}' => self::BULAN_ROMAWI[$bulan] ?? '',
            '{bulan_nama}' => self::BULAN_NAMA[$bulan] ?? '',
            '{tahun}' => (string) $tahun,
            '{tahun_2}' => substr((string) $tahun, -2),
            '{level}' => (string) $level,
            '{tanggal}' => $tanggal ?? date('d-m-Y'),
        ];

        $result = strtr(self::formatTemplate($setting), $map);

        // Bersihkan slash ganda & spasi berlebih yang mungkin muncul dari
        // placeholder kosong (mis. {npsn} tidak diisi).
        $result = preg_replace('#/{2,}#', '/', $result) ?? $result;
        $result = trim($result);

        return $result === '' ? self::DEFAULT_FORMAT : $result;
    }

    /**
     * Pratinjau nomor berdasarkan nilai form (dipakai live preview di UI).
     */
    public static function preview(
        string $template,
        int $padding,
        string $kodeSekolah,
        int $nomorUrut = 1,
        int $bulan = 0,
        int $tahun = 0,
        int $level = 0
    ): string {
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = $tahun > 0 ? $tahun : (int) date('Y');
        $pad = ($padding >= 1 && $padding <= 6) ? $padding : self::DEFAULT_PADDING;
        $nomor = str_pad((string) max(0, $nomorUrut), $pad, '0', STR_PAD_LEFT);
        $kode = trim($kodeSekolah) !== '' ? trim($kodeSekolah) : self::DEFAULT_KODE_SEKOLAH;

        $row = self::row();
        $tpl = trim($template) !== '' ? trim($template) : self::DEFAULT_FORMAT;

        $map = [
            '{kode_sekolah}' => $kode,
            '{sekolah}' => (string) (self::value($row, 'sekolah') ?? ''),
            '{npsn}' => (string) (self::value($row, 'npsn') ?? ''),
            '{nss}' => (string) (self::value($row, 'nss') ?? ''),
            '{kepsek}' => (string) (self::value($row, 'kepsek') ?? ''),
            '{nip_kepsek}' => (string) (self::value($row, 'nip') ?? ''),
            '{nomor}' => $nomor,
            '{nomor_urut}' => $nomor,
            '{bulan}' => str_pad((string) $bulan, 2, '0', STR_PAD_LEFT),
            '{bulan_romawi}' => self::BULAN_ROMAWI[$bulan] ?? '',
            '{bulan_nama}' => self::BULAN_NAMA[$bulan] ?? '',
            '{tahun}' => (string) $tahun,
            '{tahun_2}' => substr((string) $tahun, -2),
            '{level}' => (string) $level,
            '{tanggal}' => date('d-m-Y'),
        ];

        $result = strtr($tpl, $map);
        $result = preg_replace('#/{2,}#', '/', $result) ?? $result;

        return trim($result);
    }

    /**
     * Daftar placeholder untuk ditampilkan di halaman pengaturan.
     */
    public static function placeholders(): array
    {
        return [
            '{kode_sekolah}' => 'Kode sekolah (kolom Kode Sekolah di profil)',
            '{sekolah}' => 'Nama sekolah',
            '{npsn}' => 'NPSN sekolah',
            '{nss}' => 'NSS / NSM sekolah',
            '{kepsek}' => 'Nama Kepala Sekolah',
            '{nip_kepsek}' => 'NIP Kepala Sekolah',
            '{nomor_urut}' => 'Nomor urut (sama dengan {nomor})',
            '{nomor}' => 'Nomor urut (sama dengan {nomor_urut})',
            '{bulan}' => 'Bulan dua digit, contoh 09',
            '{bulan_romawi}' => 'Bulan angka romawi, contoh IX',
            '{bulan_nama}' => 'Nama bulan, contoh September',
            '{tahun}' => 'Tahun empat digit',
            '{tahun_2}' => 'Dua digit terakhir tahun',
            '{level}' => 'Jumlah hari tingkat teguran (3/7/10)',
            '{tanggal}' => 'Tanggal issuance hari ini',
        ];
    }

    /**
     * Normalisasi input template dari form.
     */
    public static function sanitizeTemplate(?string $template): string
    {
        $tpl = trim((string) $template);
        $tpl = preg_replace('/[\r\n\t]+/', ' ', $tpl) ?? $tpl;

        if (mb_strlen($tpl) > 120) {
            $tpl = mb_substr($tpl, 0, 120);
        }

        return $tpl;
    }

    private static function value(?object $setting, string $key): ?string
    {
        if ($setting === null || !isset($setting->{$key})) {
            return null;
        }

        return (string) $setting->{$key};
    }

    /**
     * Baris tabel `setting` (baris pertama), di-cache per request.
     */
    private static function row(): ?object
    {
        if (array_key_exists('setting', self::$cache)) {
            return self::$cache['setting'];
        }

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('setting')) {
                return self::$cache['setting'] = null;
            }
            self::$cache['setting'] = $db->table('setting')->limit(1)->get()->getRow();
        } catch (\Throwable $e) {
            log_message('error', '[SuratNomor] gagal memuat setting: ' . $e->getMessage());
            self::$cache['setting'] = null;
        }

        return self::$cache['setting'];
    }

    /**
     * Bersihkan cache (dipanggil setelah pengaturan disimpan).
     */
    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
