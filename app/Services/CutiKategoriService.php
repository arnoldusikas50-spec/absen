<?php

namespace App\Services;

/**
 * CutiKategoriService — sumber kebenaran tunggal untuk kategori cuti
 * pegawai (mengacu pada Pasal-Pasal aturan hak cuti PNS).
 *
 * Sebelumnya semua pengajuan jenis `cuti` bersifat generik: hanya satu
 * kuota tahunan (`max_cuti_hari`) dan tidak ada pembedaan hak. Kini
 * `absensi_izin.kategori_cuti` membedakan:
 *
 *  1.tahunan    Cuti Tahunan       → min 1 tahun terus-menerus bekerja;
 *                                   12 hari kerja (kuota tahunan
 *                                   `max_cuti_hari` dipegang
 *                                   IzinKuotaService; bukan menjadi
 *                                   hak guru yang mendapat liburan
 *                                   sesuai kalender akademik).
 *  2. besar     Cuti Besar         → min 5 tahun mengabdi terus-menerus;
 *                                   maks 3 bulan; PNS saja; peminjar
 *                                   tidak berhak cuti tahunan pada tahun
 *                                   yang berjalan.
 *  3. melahirkan Cuti Melahirkan   → PNS/PPPK perempuan, anak ke-1 s.d.
 *                                   ke-3; maks 3 bulan. Anak ke-4 dst
 *                                   memakai Cuti Besar.
 *  4. cap       Cuti Karena Alasan Penting → kedaruratan pribadi
 *                                   (keluarga inti sakit keras/meninggal,
 *                                   pernikahan pertama, musibah bencana
 *                                   alam); maks 1 bulan.
 *
 * Cuti Bersama dan CLTN tidak masuk daftar ini: cuti bersama ditetapkan
 * melalui Kepres (tidak diajukan per orang), dan CLTN belum diaktifkan.
 *
 * Aturan masa kerja dibaca dari `master_guru.tmt` (TMT pengangkatan),
 * gender dari `master_guru.jenis_kelamin`, dan status kepegawaian dari
 * `master_guru.status_pegawai` (PNS/CPNS/PPPK; `jenis_ptk` hanya
 * fallback data lama — di Dapodik kolom itu menampung jenis tenaga,
 * bukan status ASN). Pegawai non-ASN (Guru Honor Sekolah, dll) TIDAK
 * memakai kategori ini — pengajuan cuti mereka tetap
 * bersifat generik (kategori NULL) dan mengikuti kuota cuti tahunan.
 */
class CutiKategoriService
{
    /** Cuti wajib diajukan minimal sebanyak ini HARI sebelum tanggal mulai. */
    public const HARI_MAJU_PENGAJUAN = 3;

    /**
     * Kategori yang dikecualikan dari hari maju: kejadiannya memang tidak
     * bisa direncanakan 3 hari sebelumnya — kedaruratan pribadi (keluarga
     * sakit keras/meninggal, musibah, pernikahan pertama) dan persalinan.
     */
    public const KATEGORI_BEBAS_HARI_MAJU = ['cap', 'melahirkan'];

    /**
     * Definisi kategori:
     *  - min_masa_kerja : tahun, dihitung dari TMT sampai tgl mulai.
     *  - maks_hari      : batas durasi SATU pengajuan dalam HARI KALENDER.
     *                     0 = tidak berbatas di sini (cuti tahunan
     *                     dipandu kuota tahunan + batas pengajuan di
     *                     IzinKuotaService).
     *  - jenis_ptk      : prefix jenis_ptk yang berhak.
     *  - gender         : 'P' bila hanya perempuan.
     */
    public const KATEGORI = [
        'tahunan' => [
            'label'          => 'Cuti Tahunan',
            'min_masa_kerja' => 1,
            'maks_hari'      => 0,
            'jenis_ptk'      => ['PNS', 'CPNS', 'PPPK'],
            'gender'         => null,
            'ketentuan'      => 'Minimal 1 tahun terus-menerus bekerja. 12 hari kerja, sisa dapat diakumulasi maksimal 18 hari kerja (termasuk cuti tahun berjalan). Tidak berlaku bagi yang mendapat liburan sesuai kalender akademik.',
        ],
        'besar' => [
            'label'          => 'Cuti Besar',
            'min_masa_kerja' => 5,
            'maks_hari'      => 90,
            'jenis_ptk'      => ['PNS', 'CPNS'],
            'gender'         => null,
            'ketentuan'      => 'Minimal 5 tahun mengabdi terus-menerus (PNS). Maksimal 3 bulan, misal untuk ibadah (Haji), melahirkan anak ke-4 dan seterusnya, atau kepentingan keluarga yang mendesak. Cuti tahunan tahun berjalan menjadi hangus.',
        ],
        'melahirkan' => [
            'label'          => 'Cuti Melahirkan',
            'min_masa_kerja' => 0,
            'maks_hari'      => 90,
            'jenis_ptk'      => ['PNS', 'CPNS', 'PPPK'],
            'gender'         => 'P',
            'ketentuan'      => 'Untuk pegawai perempuan yang melahirkan anak ke-1, ke-2, atau ke-3. Maksimal 3 bulan. Anak ke-4 dan seterusnya menggunakan Cuti Besar.',
        ],
        'cap' => [
            'label'          => 'Cuti Karena Alasan Penting',
            'min_masa_kerja' => 0,
            'maks_hari'      => 30,
            'jenis_ptk'      => ['PNS', 'CPNS', 'PPPK'],
            'gender'         => null,
            'ketentuan'      => 'Untuk kedaruratan pribadi: keluarga inti (ibu, bapak, istri/suami, anak, adik, kakak, mertua, menantu) sakit keras atau meninggal dunia, melangsungkan pernikahan pertama, atau mengalami musibah bencana alam. Maksimal 1 bulan.',
        ],
    ];

    /** Kategori yang memotong kuota cuti tahunan (di IzinKuotaService).
     *  NULL/'' (generik legacy) dan 'tahunan' ya; kategori khusus tidak.
     *  Nilai tak dikenal dianggap memotong kuota (konservatif: tidak ada
     *  hari cuti yang jadi bebas melorot dari semua pencacah). */
    public static function pakaiKuotaTahunan(string $kategori): bool
    {
        $kategori = trim($kategori);
        if ($kategori === '') {
            return true; // generik legacy = cuti tahunan kuota
        }
        if (! self::valid($kategori)) {
            return true; // nilai asing → aman dihitung ke kuota
        }
        return $kategori === 'tahunan';
    }

    /** Kategori valid, terlepas dari eligibilitas pegawai. */
    public static function valid(string $kategori): bool
    {
        return isset(self::KATEGORI[$kategori]);
    }

    /** Apakah pengajuan cuti kategori ini wajib maju HARI_MAJU_PENGAJUAN hari? */
    public static function butuhHariMaju(?string $kategori): bool
    {
        return ! in_array((string) $kategori, self::KATEGORI_BEBAS_HARI_MAJU, true);
    }

    /**
     * Apakah pengguna punya Cuti Besar yang masih aktif (pending/approved)
     * pada tahun kalender yang diberikan? Peminjam Cuti Besar hangus hak
     * cuti tahunannya pada tahun yang berjalan, jadi pengajuan kategori
     * 'tahunan' harus ditolak bila ini true.
     */
    public static function besarAktifTahun(int $userId, int $tahun): bool
    {
        if ($userId <= 0) {
            return false;
        }
        try {
            return (bool) \Config\Database::connect()
                ->table('absensi_izin')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved', '0', '1'])
                ->whereIn('jenis', ['cuti', 'CUTI', 'Cuti'])
                ->where('kategori_cuti', 'besar')
                ->where('YEAR(tgl_mulai) <=', $tahun)
                ->where('YEAR(tgl_selesai) >=', $tahun)
                ->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'CutiKategoriService: gagal cek cuti besar aktif: ' . $e->getMessage());
            return false;
        }
    }

    public static function label(string $kategori): string
    {
        return self::KATEGORI[$kategori]['label'] ?? '';
    }

    /** Profil pegawai untuk eligibilitas kategori. */
    public static function profil(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            $row = \Config\Database::connect()
                ->table('master_guru')
                ->select('status_pegawai, jenis_ptk, jenis_kelamin, tmt, nip')
                ->where('id_user', $userId)
                ->orderBy('deleted_at', 'ASC')
                ->limit(1)
                ->get()
                ->getRow();
        } catch (\Throwable $e) {
            log_message('error', 'CutiKategoriService: gagal membaca profil guru: ' . $e->getMessage());
            return null;
        }
        if (! $row) {
            return null;
        }

        // Status kepegawaian ada di `status_pegawai` (isi live: PNS, PPPK,
        // Guru Honor Sekolah, Tenaga Honor Sekolah). `jenis_ptk` di instalasi
        // ini berisi JENIS TENAGA (Guru, Kepala Sekolah, dst → hasil Dapodik),
        // bukan status — dipakai hanya sebagai fallback data lama yang
        // status_pegawai-nya kosong tetapi jenis_ptk-nya PNS/PPPK.
        $status   = strtoupper(trim((string) ($row->status_pegawai ?? '')));
        $jenisPtk = strtoupper(trim((string) ($row->jenis_ptk ?? '')));
        $ptk      = $status !== '' ? $status : $jenisPtk;

        return [
            'jenis_ptk'     => $ptk,
            'jenis_kelamin' => strtoupper(trim((string) ($row->jenis_kelamin ?? ''))),
            'tmt'           => self::tmtEfektif($row->tmt ?? null, $row->nip ?? ''),
        ];
    }

    /**
     * TMT efektif untuk verifikasi masa kerja.
     *
     * Kolom `tmt` di banyak profil live kosong. Sebagai
     * fallback, TMT pengangkatan dapat dibaca dari NIP yang memiliki
     * struktur resmi: 8 digit tanggal lahir + 4 digit tahun TMT +
     * 2 digit (bulan untuk NIP PNS, marker '21' untuk PPPK) + nomor
     * urut. Contoh PNS: 19881005**201504**1001 → TMT 2015-04-01.
     * Kolom `tmt` tetap menang bila berisi nilai.
     */
    public static function tmtEfektif(?string $tmt, string $nip): string
    {
        $tmt = trim((string) $tmt ?? '');
        if ($tmt !== '' && $tmt !== '0000-00-00') {
            return substr($tmt, 0, 10);
        }

        $nip = preg_replace('/\D/', '', (string) $nip);
        if ($nip === '' || strlen($nip) !== 18) {
            return '';
        }

        $tahun = substr($nip, 8, 4);
        $bulan = substr($nip, 12, 2);
        if (! ctype_digit($tahun) || ! ctype_digit($bulan)) {
            return '';
        }
        if ((int) $tahun < 1800 || (int) $tahun > 2100) {
            return '';
        }

        if ($bulan === '21') {
            // Marker PPPK: digit setelah tahun TMT bukan bulan (bulan
            // pengangkatan tidak terkode di NIP PPPK), jadi pakai awal
            // tahun pengangkatan sebagai aproksimasi. Verifikator tetap
            // bisa menilai lebih teliti — atau mengisi kolom
            // `master_guru.tmt` untuk memaksa nilai pasti.
            return $tahun . '-01-01';
        }
        if ((int) $bulan < 1 || (int) $bulan > 12) {
            return '';
        }

        return $tahun . '-' . $bulan . '-01';
    }

    /**
     * Kategori yang boleh ditampilkan pada form untuk profil tertentu.
     * Dipakai untuk merespons opsi dropdown form pengajuan.
     */
    public static function kategoriDiizinkan(?array $profil): array
    {
        if (! $profil) {
            return [];
        }
        $ptk = $profil['jenis_ptk'];

        // Pegawai non-ASN (honor, dll) memakai cuti generik.
        $cocok = false;
        foreach (['PNS', 'CPNS', 'PPPK'] as $pt) {
            if ($ptk === $pt || str_starts_with($ptk, $pt)) {
                $cocok = true;
                break;
            }
        }
        if (! $cocok) {
            return [];
        }

        $out = [];
        foreach (self::KATEGORI as $kode => $r) {
            $ptkOk = false;
            foreach ($r['jenis_ptk'] as $pt) {
                if ($ptk === $pt || str_starts_with($ptk, $pt)) {
                    $ptkOk = true;
                    break;
                }
            }
            if (! $ptkOk) {
                continue;
            }
            if ($r['gender'] !== null && $r['gender'] !== ($profil['jenis_kelamin'] ?? '')) {
                continue;
            }
            $out[] = [
                'kode'      => $kode,
                'label'     => $r['label'],
                'ketentuan' => $r['ketentuan'] . self::catatanHariMaju($kode),
                'maks_hari' => $r['maks_hari'],
            ];
        }

        return $out;
    }

    /**
     * Kalimat pelengkap aturan hari maju untuk teks ketentuan per kategori.
     * Angka dan daftar pengecualiannya tetap hidup di konstanta di atas,
     * jadi mengubah pengecualian otomatis memperbaiki seluruh teks.
     */
    public static function catatanHariMaju(string $kode): string
    {
        return self::butuhHariMaju($kode)
            ? ' Ajukan minimal ' . self::HARI_MAJU_PENGAJUAN . ' hari sebelum tanggal mulai.'
            : ' Dikecualikan dari batas ' . self::HARI_MAJU_PENGAJUAN . ' hari — boleh dimulai hari ini.';
    }

    /** Opsi dropdown form untuk seorang user (shortcut profil+filter). */
    public static function opsiUntukUser(int $userId): array
    {
        return self::kategoriDiizinkan(self::profil($userId));
    }

    /**
     * Masa kerja (tahun bulat-ke-bawah) dari TMT sampai tgl.
     * TMT kosong → null (tidak bisa diverifikasi).
     */
    public static function masaKerjaTahun(?string $tmt, string $tgl): ?float
    {
        $tmt = trim((string) $tmt ?? '');
        if ($tmt === '' || $tgl === '') {
            return null;
        }
        $tmtTs = strtotime($tmt);
        $tglTs = strtotime($tgl);
        if ($tmtTs === false || $tglTs === false) {
            return null;
        }
        return max(0.0, ($tglTs - $tmtTs) / (365.25 * 86400));
    }

    /**
     * Durasi pengajuan dalam HARI KALENDER.
     */
    public static function hariKalender(string $start, string $end): int
    {
        $s = strtotime($start);
        $e = strtotime($end);
        if ($s === false || $e === false || $e < $s) {
            return 0;
        }
        return (int) floor(($e - $s) / 86400) + 1;
    }

    /**
     * Periksa eligibilitas SATU kategori untuk seorang profil pada
     * tgl mulai pengajuan.
     *
     * @return array{ok:bool, pesan:string}
     */
    public static function cekEligibilitas(string $kategori, ?array $profil, string $tglMulai): array
    {
        if (! self::valid($kategori)) {
            return ['ok' => false, 'pesan' => 'Kategori cuti tidak dikenal.'];
        }

        if (! $profil) {
            return [
                'ok'    => false,
                'pesan' => 'Data pegawai tidak ditemukan. Kategori cuti hanya untuk pegawai ASN (PNS/PPPK).',
            ];
        }
        $r   = self::KATEGORI[$kategori];
        $ptk = $profil['jenis_ptk'];

        $ptkOk = false;

        foreach ($r['jenis_ptk'] as $pt) {
            if ($ptk === $pt || str_starts_with($ptk, $pt)) {
                $ptkOk = true;
                break;
            }
        }
        if (! $ptkOk) {
            if ($kategori === 'besar') {
                return [
                    'ok'    => false,
                    'pesan' => 'Cuti Besar hanya untuk PNS. PPPK dan pegawai lain tidak berhak mengajukan Cuti Besar.',
                ];
            }
            return [
                'ok'    => false,
                'pesan' => 'Kategori ' . $r['label'] . ' tidak berlaku untuk status kepegawaian "' . ($ptk !== '' ? $ptk : 'tidak terdata') . '".',
            ];
        }

        if ($r['gender'] !== null && $r['gender'] !== ($profil['jenis_kelamin'] ?? '')) {
            if ($kategori === 'melahirkan') {
                return [
                    'ok'    => false,
                    'pesan' => 'Cuti Melahirkan hanya untuk pegawai PEREMPUAN.',
                ];
            }
            return ['ok' => false, 'pesan' => 'Kategori ' . $r['label'] . ' tidak berlaku untuk data jenis kelamin Anda.'];
        }

        if ($r['min_masa_kerja'] > 0) {
            $masa = self::masaKerjaTahun($profil['tmt'] ?? '', $tglMulai);
            if ($masa === null) {
                return [
                    'ok'    => false,
                    'pesan' => 'Masa kerja (TMT pengangkatan) belum terdata, sehingga kategori ' . $r['label'] . ' tidak bisa diverifikasi. Hubungi administrator.',
                ];
            }
            if ($masa < $r['min_masa_kerja']) {
                if ($kategori === 'tahunan') {
                    return [
                        'ok'    => false,
                        'pesan' => 'Cuti Tahunan diberikan kepada pegawai yang telah bekerja minimal 1 tahun terus-menerus. Masa kerja Anda: ' . number_format($masa, 1, ',', '.') . ' tahun.',
                    ];
                }
                return [
                    'ok'    => false,
                    'pesan' => 'Kategori ' . $r['label'] . ' mensyaratkan masa kerja minimal ' . $r['min_masa_kerja'] . ' tahun terus-menerus. Masa kerja Anda: ' . number_format($masa, 1, ',', '.') . ' tahun.',
                ];
            }
        }

        return ['ok' => true, 'pesan' => ''];
    }

    /**
     * Batas durasi satu pengajuan untuk kategori, dalam TULISAN hari.
     * Basis hitung cuti tahunan tetap dipegang IzinKuotaService
     * (hari kerja efektif); kategori besar/melahirkan/cap memakai
     * hari kalender — "3 bulan" tetap 3 bulan walau defisit hari kerja.
     */
    public static function batasHariKategori(string $kategori): int
    {
        return (int) (self::KATEGORI[$kategori]['maks_hari'] ?? 0);
    }

    /**
     * Pesan aturan pendek untuk UI.
     */
    public static function deskripsiKategori(string $kategori): string
    {
        return self::KATEGORI[$kategori]['ketentuan'] ?? '';
    }
}
