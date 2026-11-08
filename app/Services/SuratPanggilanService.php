<?php

namespace App\Services;

/**
 * SuratPanggilanService — penerbitan & rekap surat panggilan orang tua.
 *
 * Berpasangan dengan `absensi_surat_teguran` (surat teguran guru), tapi untuk
 * siswa dan suratannya ditujukan ke orang tua/wali. Yang sama:
 *
 *   - Surat terbit otomatis begitu alpha siswa melewati ambang (3/5/7 hari),
 *     dievaluasi pada tanggal yang sama seperti surat teguran guru.
 *   - Satu siswa satu surat per ambang per bulan. Unique index di database
 *     yang menjaga ini; kode di sini tetap mengecek lebih dulu supaya
 *     percobaan kedua tidak meledak jadi exception.
 *   - Angka alpha & keterlambatan di dalam surat adalah SNAPSHOT saat
 *     terbit. PDF yang dicetak ulang bertahun-tahun kemudian harus menampilkan
 *     angka yang sama seperti waktu surat dibuat, bukan angka hari ini.
 *
 * Hitungan alpha memakai attendance_rekap_day_cell() — fungsi yang sama yang
 * mengisi tabel rekap siswa (`PresensiSiswa::getRekapBulananData()`) dan yang
 * menilai denda siswa (`SiswaDendaService`). Karena satu penilai untuk
 * bertiga, hari libur, hari BDR kelas itu, dan hari yang presensinya belum
 * dicatat tidak pernah menambah alpha di surat saja.
 */
class SuratPanggilanService
{
    public const TABEL = 'absensi_surat_panggilan';

    /**
     * Ambang alpha (hari) yang memicu surat, dari ringan ke berat.
     *
     * Urutan ini penting untuk levelTercapai(): siswa dengan 7 alpha
     * memenuhi level 3, 5, dan 7 sekaligus, dan yang terbit hanya yang
     * terberat — surat level 3 dan 5 akan dianggap sudah terlewati dan
     * hanya menambah surat sia-sia ke orang tua.
     */
    public const AMBANG = [3, 5, 7];

    /** Sumber surat. */
    public const SUMBER_CRON   = 'cron';
    public const SUMBER_MANUAL = 'manual';

    /** Label tingkat untuk UI & PDF. */
    public const LABEL_LEVEL = [
        3 => 'Tingkat I (3 Hari Alpha)',
        5 => 'Tingkat II (5 Hari Alpha)',
        7 => 'Tingkat III (7 Hari Alpha)',
    ];

    // ── data bulan untuk perhitungan ──────────────────────────────────

    /**
     * Konteks perhitungan alpha untuk satu bulan.
     *
     * Mengembalikan semua yang dibutuhkan supaya perhitungan alpha tidak
     * query database berulang-ulang per siswa (O(n) query untuk n siswa).
     *
     * @return array{
     *   tglAwal:string, tglAkhir:string, hariEfektif:int, hariLibur:array<string,bool>,
     *   jamBatasMasuk:string
     * }
     */
    public static function buildContext(int $bulan, int $tahun): array
    {
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = $tahun > 0 ? $tahun : (int) date('Y');

        $daysInMonth = (int) date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
        $tglAwal  = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tglAkhir = sprintf('%04d-%02d-%02d', $tahun, $bulan, $daysInMonth);

        // Hari efektif & daftar hari libur bulan ini. Sumbernya satu:
        // AttendancePolicy (libur mingguan ternormalisasi ATAU jadwal per hari
        // ATAU kalender libur nasional), sama dengan rekap siswa dan scan QR.
        $hariEfektif = 0;
        $hariLibur   = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            if (AttendancePolicy::isLibur($tgl)) {
                $hariLibur[$tgl] = true;
                continue;
            }
            $hariEfektif++;
        }

        // Batas akhir masuk (jam_masuk + grace, inklusif detik ke-59) untuk
        // display. Perbandingan per baris memakai tanggal baris (lihat
        // hitungKehadiranBulanan) agar jadwal per hari ikut berlaku.
        $jamBatasMasuk = date('H:i:s', AttendancePolicy::batasAkhirMasukTs(
            $tglAwal,
            AttendancePolicy::jadwalSiswa($tglAwal)
        ));

        return [
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'tglAwal'        => $tglAwal,
            'tglAkhir'       => $tglAkhir,
            'daysInMonth'    => $daysInMonth,
            'hariEfektif'    => $hariEfektif,
            'hariLibur'      => $hariLibur,
            'jamBatasMasuk'  => $jamBatasMasuk,
        ];
    }

    /**
     * Alpha & keterlambatan semua siswa dalam satu bulan, sekaligus.
     *
     * Satu query untuk seluruh siswa, bukan satu query per siswa — dengan
     * 400 siswa, pendekatan per-siswa berarti 400 query per evaluasi cron.
     *
     * Penilainya attendance_rekap_day_cell(), fungsi yang sama yang mengisi
     * tabel rekap siswa dan yang menagih denda siswa. Hanya dengan begitu
     * surat tidak bisa memanggil orang tua untuk hari yang di layar sekolah
     * tampil hadir — dan sebaliknya.
     *
     *   - Hari libur (mingguan/nasional/jadwal kerja) tidak dihitung.
     *   - Hari BDR kelas itu tidak dihitung: siswa memang tidak datang.
     *   - Tanggal yang presensi kelasnya tidak tercatat sama sekali BUKAN
     *     alpha. Yang belum berjalan adalah pencatatan absensinya, bukan
     *     seisi kelas yang membolos; tanpa aturan ini, bulan yang datanya
     *     belum masuk menerbitkan surat untuk seluruh sekolah.
     *   - Tidak ada baris pada hari yang tercatat = alpha.
     *   - Hari ini DILEWATI, karena hari ini belum selesai dan siswa yang
     *     belum absen bisa saja sedang di jalan.
     *
     * @return array<int, array{id_siswa:int, alpha:int, terlambat:int, tanpa_keterangan:int, tanggal_alpha:array<int,string>}>
     */
    public static function rekapAlphaBulan(array $ctx, ?int $idKelas = null): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('presensi_siswa')
            ->select('id_siswa, id_kelas, tgl, status, jam_masuk, jam_keluar')
            ->where('tgl >=', $ctx['tglAwal'])
            ->where('tgl <=', $ctx['tglAkhir']);
        if ($idKelas !== null && $idKelas > 0) {
            $builder->where('id_kelas', $idKelas);
        }

        // Kelompokkan per siswa per tanggal, sekaligus catat hari mana presensi
        // satu kelas benar-benar berjalan (penanda 'no_data' di helper rekap).
        $perSiswa = [];
        $tercatat = [];
        $kelasBaris = [];
        foreach ($builder->get()->getResult() as $r) {
            $tgl = substr((string) $r->tgl, 0, 10);
            $sid = (int) $r->id_siswa;
            if ($sid <= 0 || $tgl === '') {
                continue;
            }
            $kid = (int) ($r->id_kelas ?? 0);
            $perSiswa[$sid][$tgl] = $r;
            $tercatat[$kid][$tgl] = true;
            $kelasBaris[$sid] = $kid > 0 ? $kid : ($kelasBaris[$sid] ?? 0);
        }

        // Siswa tanpa satu pun baris sebulan penuh (0 scan) sebelumnya tidak
        // pernah masuk rekap → tidak pernah dapat surat. Lengkapi dari
        // siswa AKTIF (buku_induk.status = '1') agar bolong total dihitung
        // alpha seperti guru. Non-AKTIF (LULUS/PINDAH/KELUAR) dikecualikan.
        // (Hanya evaluasi seluruh sekolah; filter per-kelas butuh konteks
        // TP/SMT yang tidak ada di fungsi statis ini.)
        try {
            // Peta status dibaca SEKALI (buku_induk kecil) — bukan N query.
            // null = tak bisa ditentukan → jangan filter agresif.
            $petaStatus = null;
            if ($db->tableExists('buku_induk')) {
                try {
                    $petaStatus = [];
                    foreach ($db->table('buku_induk')->select('id_siswa, status')->get()->getResult() as $s) {
                        $sid = (int) ($s->id_siswa ?? 0);
                        if ($sid > 0) {
                            $petaStatus[$sid] = (string) ($s->status ?? '1');
                        }
                    }
                } catch (\Throwable $e) {
                    $petaStatus = null;
                }
            }
            if (is_array($petaStatus)) {
                // Baris presensi milik non-AKTIF dibuang dari hitungan.
                // Tanpa baris buku_induk (data lama) dianggap AKTIF.
                foreach (array_keys($perSiswa) as $sid) {
                    if (isset($petaStatus[$sid]) && $petaStatus[$sid] !== '1') {
                        unset($perSiswa[$sid]);
                    }
                }
            }
            if (($idKelas === null || $idKelas <= 0) && is_array($petaStatus)) {
                try {
                    if ($db->tableExists('master_siswa')) {
                        $semua = $db->table('master_siswa')->select('id_siswa')->get()->getResult();
                        foreach ($semua as $s) {
                            $sid = (int) ($s->id_siswa ?? 0);
                            if ($sid <= 0 || isset($perSiswa[$sid])) {
                                continue;
                            }
                            if (!isset($petaStatus[$sid]) || $petaStatus[$sid] === '1') {
                                $perSiswa[$sid] = [];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', '[SuratPanggilan] gagal enumerasi siswa tanpa baris: ' . $e->getMessage());
                }
            } elseif (($idKelas === null || $idKelas <= 0) && $petaStatus === null) {
                // Fallback saat tabel buku_induk belum ada (mis. skema lama /
                // lingkungan uji): enumerasi master aktif via deleted_at.
                try {
                    if ($db->tableExists('master_siswa') && $db->fieldExists('deleted_at', 'master_siswa')) {
                        $aktif = $db->table('master_siswa')
                            ->select('id_siswa')
                            ->where('deleted_at', null)
                            ->get()->getResult();
                        foreach ($aktif as $s) {
                            $sid = (int) ($s->id_siswa ?? 0);
                            if ($sid > 0 && !isset($perSiswa[$sid])) {
                                $perSiswa[$sid] = [];
                            }
                        }
                    } elseif ($db->tableExists('master_siswa')) {
                        // Tanpa penanda aktif apa pun, pertahankan perilaku lama:
                        // semua siswa master dianggap aktif.
                        $semua = $db->table('master_siswa')->select('id_siswa')->get()->getResult();
                        foreach ($semua as $s) {
                            $sid = (int) ($s->id_siswa ?? 0);
                            if ($sid > 0 && !isset($perSiswa[$sid])) {
                                $perSiswa[$sid] = [];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', '[SuratPanggilan] gagal enumerasi siswa tanpa baris: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] gagal filter siswa aktif: ' . $e->getMessage());
        }

        // Daftar hari efektif (tanggal => true).
        // Hari BDR (belajar dari rumah) tidak dihitung: siswa memang tidak
        // datang ke sekolah, jadi tidak boleh dianggap bolong/alpha meski
        // cron belum sempat menulis baris BDR. Peta BDR dibangun untuk SEMUA
        // kelas (bukan hanya satu kelas yang sedang disaring), sebab evaluasi
        // cron dan halaman rekap berjalan untuk seluruh sekolah.
        $bdrKelas = [];
        try {
            $bdrSvc = new PresensiBdrService();
            for ($d = 1; $d <= $ctx['daysInMonth']; $d++) {
                $tgl = sprintf('%04d-%02d-%02d', $ctx['tahun'], $ctx['bulan'], $d);
                $terdampak = $bdrSvc->kelasTerdampak($tgl);
                if ($terdampak !== []) {
                    $bdrKelas[$tgl] = $terdampak;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] gagal membaca jadwal BDR: ' . $e->getMessage());
        }

        // Kelas setiap siswa, untuk menilai BDR dan hari tercatat per kelas.
        $petaKelas = self::petaKelasSiswa($db);

        $hariEfektifList = [];
        for ($d = 1; $d <= $ctx['daysInMonth']; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $ctx['tahun'], $ctx['bulan'], $d);
            if (!isset($ctx['hariLibur'][$tgl])) {
                $hariEfektifList[$tgl] = true;
            }
        }

        $hariIni = date('Y-m-d');

        $hasil = [];
        foreach ($perSiswa as $idSiswa => $tanggalMap) {
            $alpha = 0;
            $terlambat = 0;
            $tanpaKet = 0;
            $tglAlpha = [];

            $idKelasSiswa = (int) ($petaKelas[$idSiswa] ?? ($kelasBaris[$idSiswa] ?? 0));

            foreach ($hariEfektifList as $tgl => $_) {
                // Hari ini belum selesai, dan hari yang belum datang jelas
                // tidak bisa dihitung. Tanpa Guards ini, bulan berjalan di
                // awal bulan akan menghitung semua hari yang AKAN terjadi
                // sebagai alpha — dan pada evaluasi tanggal 7 setiap siswa
                // langsung mendapat surat tingkat heaviest.
                //
                // Aturan yang sama dipakai rekap_siswa.php: hanya tanggal
                // yang sudah lewat yang dihitung.
                if ($tgl >= $hariIni) {
                    continue;
                }

                $rec = $tanggalMap[$tgl] ?? null;

                $sel = attendance_rekap_day_cell(
                    $rec,
                    $tgl,
                    false,
                    false,
                    $idKelasSiswa > 0 && isset($bdrKelas[$tgl][$idKelasSiswa]),
                    self::batasMasuk($tgl, $ctx),
                    $hariIni,
                    attendance_hari_tercatat($tercatat, $tgl, $idKelasSiswa > 0 ? $idKelasSiswa : null)
                );

                if ($sel['state'] === 'no_data') {
                    // Presensi kelas ini tidak tercatat hari itu: bukan
                    // alpha, dan tidak boleh menjadi alasan surat.
                    continue;
                }

                if ($sel['state'] === 'alpha') {
                    $alpha++;
                    if (strtoupper(trim((string) ($rec->status ?? ''))) === 'TANPA_KETERANGAN') {
                        $tanpaKet++;
                    }
                    $tglAlpha[] = $tgl;
                    continue;
                }

                // Terlambat = state 'late' dari sel yang sama: status hadir
                // varian apa pun (HADIR_TELAT, TERLAMBAT, ...) dan baris tanpa
                // status resmi tapi jam masuknya lewat batas ikut terhitung,
                // persis seperti kolom TL di rekap.
                if ($sel['state'] === 'late') {
                    $terlambat++;
                }
            }

            $hasil[$idSiswa] = [
                'id_siswa'         => $idSiswa,
                'alpha'            => $alpha,
                'terlambat'        => $terlambat,
                'tanpa_keterangan' => $tanpaKet,
                'tanggal_alpha'    => $tglAlpha,
            ];
        }

        return $hasil;
    }

    /**
     * Batas akhir jam masuk satu tanggal (label 'H:i:s'), dengan fallback ke
     * konteks bulan bila jadwal hari itu tidak dikenal.
     */
    private static function batasMasuk(string $tgl, array $ctx): string
    {
        static $peta = [];
        if (isset($peta[$tgl])) {
            return $peta[$tgl];
        }

        try {
            return $peta[$tgl] = date('H:i:s', AttendancePolicy::batasAkhirMasukTs(
                $tgl,
                AttendancePolicy::jadwalSiswa($tgl)
            ));
        } catch (\Throwable $e) {
            return $peta[$tgl] = (string) ($ctx['jamBatasMasuk'] ?? '07:00:00');
        }
    }

    /**
     * id_siswa => id_kelas dari penempatan kelas, didahulukan kelas pada
     * tahun/semester aktif. Dipeta sekali per proses; instalasi tanpa
     * kelas_siswa mengembalikan peta kosong dan pemanggil jatuh ke kelas
     * pada baris presensi siswa itu sendiri.
     *
     * @return array<int, int>
     */
    private static function petaKelasSiswa(object $db): array
    {
        static $peta = null;
        if ($peta !== null) {
            return $peta;
        }

        $peta = [];
        try {
            if (!$db->tableExists('kelas_siswa') || !$db->tableExists('master_kelas')) {
                return $peta;
            }
            $rows = $db->table('kelas_siswa ks')
                ->select('ks.id_siswa, ks.id_kelas')
                ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas')
                ->join('master_tp tp', 'tp.id_tp = mk.id_tp', 'left')
                ->join('master_smt sm', 'sm.id_smt = mk.id_smt', 'left')
                ->orderBy('tp.active', 'DESC')
                ->orderBy('sm.active', 'DESC')
                ->orderBy('ks.id_kelas', 'DESC')
                ->get()->getResult();
            foreach ($rows as $r) {
                $sid = (int) ($r->id_siswa ?? 0);
                if ($sid > 0 && !isset($peta[$sid])) {
                    $peta[$sid] = (int) ($r->id_kelas ?? 0);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] gagal membaca penempatan kelas: ' . $e->getMessage());
            $peta = [];
        }

        return $peta;
    }

    /**
     * Alpha satu siswa pada satu bulan.
     *
     * Dipakai saat membuat surat manual — hasilnya harus sama persis
     * dengan yang dipakai cron otomatis, supaya angka di surat manual dan
     * surat otomatis tidak berbeda hanya karena cara punching-nya berbeda.
     */
    public static function alphaSiswa(int $idSiswa, array $ctx): array
    {
        $semua = self::rekapAlphaBulan($ctx);

        return $semua[$idSiswa] ?? [
            'id_siswa'         => $idSiswa,
            'alpha'            => 0,
            'terlambat'        => 0,
            'tanpa_keterangan' => 0,
            'tanggal_alpha'    => [],
        ];
    }

    /**
     * Tingkat terberat yang sudah terpenuhi, atau 0 kalau belum ada.
     *
     * Hanya dipakai untuk tampilan. Surat yang benar-benar terbit tetap
     * diurus oleh generate() yang iterate semua ambang.
     */
    public static function levelTercapai(int $alpha): int
    {
        $level = 0;
        foreach (self::AMBANG as $ambang) {
            if ($alpha >= $ambang) {
                $level = $ambang;
            }
        }

        return $level;
    }

    // ── penerbitan ────────────────────────────────────────────────────

    /**
     * Terbitkan surat otomatis untuk semua siswa yang melewati ambang.
     *
     * Dipanggil dari cron pada tanggal 7/14/21/akhir bulan — sama seperti
     * surat teguran guru.
     *
     * @return array{diterbitkan:int, dicek:int, gagal:int}
     */
    public static function generate(int $bulan, int $tahun, array $ctx): array
    {
        $db = \Config\Database::connect();
        $hasil = ['diterbitkan' => 0, 'dicek' => 0, 'gagal' => 0, 'dibatalkan' => 0, 'detail' => []];

        // Batalkan dulu surat aktif yang alphanya sudah gugur (koreksi/
        // hapus presensi setelah terbit), baru terbitkan yang baru.
        try {
            $hasil['dibatalkan'] = self::sinkronkan($bulan, $tahun, $ctx);
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] sinkronkan gagal: ' . $e->getMessage());
        }

        $rekap = self::rekapAlphaBulan($ctx);

        foreach ($rekap as $idSiswa => $r) {
            $hasil['dicek']++;

            $alpha = (int) $r['alpha'];
            if ($alpha < self::AMBANG[0]) {
                continue; // belum mencapai ambang terendah
            }

            // Terbitkan hanya ambang TERBERAT yang terpenuhi dalam satu
            // evaluasi. Kalau alpha = 7, surat terbit untuk level 7 saja;
            // level 3 dan 5 tidak dibuat karena keduanya sudah terlewati
            // dan hanya akan menambah surat sia-sia ke orang tua.
            $level = self::levelTercapai($alpha);

            try {
                $ok = self::insertJikaBelumAda($idSiswa, $bulan, $tahun, $level, $alpha, (int) $r['terlambat'], self::SUMBER_CRON);
                if ($ok) {
                    $hasil['diterbitkan']++;
                    $hasil['detail'][] = [
                        'id_siswa'   => $idSiswa,
                        'level'      => $level,
                        'alpha'      => $alpha,
                        'terlambat'  => (int) $r['terlambat'],
                    ];
                }
            } catch (\Throwable $e) {
                $hasil['gagal']++;
                log_message('error', '[SuratPanggilan] gagal terbit untuk siswa ' . $idSiswa . ': ' . $e->getMessage());
            }
        }

        return $hasil;
    }

    /**
     * Bangun daftar surat panggilan lengkap dengan id_user wali kelas per siswa,
     * untuk notifikasi manual dari halaman admin.
     *
     * Alur: siswa → kelas_siswa.id_kelas → master_kelas.guru_id →
     * master_guru.id_user → user_id (tujuan notifikasi personal).
     *
     * @param array<int,array{id_siswa:int,level:int,alpha:int,terlambat:int}> $detail
     * @return array<int,array{id_siswa:int,level:int,alpha:int,terlambat:int,id_user_wali:int}>
     */
    public static function buildDaftarPanggilanDenganWaliKelas(object $db, int $bulan, int $tahun, array $detail): array
    {
        if (empty($detail)) {
            return [];
        }

        $idSiswaList = [];
        foreach ($detail as $d) {
            $idSiswaList[] = (int) $d['id_siswa'];
        }
        $idSiswaList = array_unique($idSiswaList);

        // ── siswa → kelas ─────────────────────────────────
        $kelasSiswa = [];
        try {
            $rows = $db->table('kelas_siswa')
                ->select('id_siswa, id_kelas')
                ->whereIn('id_siswa', $idSiswaList)
                ->get()->getResult();
            foreach ($rows as $r) {
                $kelasSiswa[(int) $r->id_siswa] = (int) $r->id_kelas;
            }
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] gagal baca kelas_siswa untuk notif manual: ' . $e->getMessage());
        }

        // ── kelas → wali guru (guru_id) ───────────────────
        $idKelasList = array_values(array_unique(array_filter($kelasSiswa)));
        $kelasWaliGuru = [];
        if (!empty($idKelasList)) {
            try {
                $rows = $db->table('master_kelas')
                    ->select('id_kelas, guru_id')
                    ->whereIn('id_kelas', $idKelasList)
                    ->where('guru_id IS NOT NULL')
                    ->where('guru_id !=', 0)
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $kelasWaliGuru[(int) $r->id_kelas] = (int) $r->guru_id;
                }
            } catch (\Throwable $e) {
                log_message('error', '[SuratPanggilan] gagal baca master_kelas wali untuk notif manual: ' . $e->getMessage());
            }
        }

        // ── guru_id → user_id ─────────────────────────────
        $guruIds = array_values(array_unique(array_filter($kelasWaliGuru)));
        $guruUser = [];
        if (!empty($guruIds)) {
            try {
                $rows = $db->table('master_guru')
                    ->select('id_guru, id_user')
                    ->whereIn('id_guru', $guruIds)
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $guruUser[(int) $r->id_guru] = (int) $r->id_user;
                }
            } catch (\Throwable $e) {
                log_message('error', '[SuratPanggilan] gagal baca master_guru user_id untuk notif manual: ' . $e->getMessage());
            }
        }

        // Susun daftar akhir
        $daftar = [];
        foreach ($detail as $d) {
            $idSiswa = (int) $d['id_siswa'];
            $idKelas = $kelasSiswa[$idSiswa] ?? 0;
            $idGuru  = $kelasWaliGuru[$idKelas] ?? 0;
            $idUser  = $guruUser[$idGuru] ?? 0;

            $daftar[] = [
                'id_siswa'     => $idSiswa,
                'level'        => (int) $d['level'],
                'alpha'        => (int) $d['alpha'],
                'terlambat'    => (int) $d['terlambat'],
                'id_user_wali' => $idUser,
            ];
        }

        return $daftar;
    }

    /**
     * Batalkan otomatis surat aktif yang syarat alphanya sudah gugur
     * (mis. presensi dikoreksi/dihapus setelah surat terbit).
     *
     * Satu arah saja (aktif → batal), tanpa menghidupkan lagi: surat manual
     * yang dibuat admin untuk kasus khusus tidak boleh hidup-mati sendiri.
     * Dipanggil di awal generate() sehingga tiap evaluasi 7/14/21/akhir
     * bulan lebih dulu membereskan yang usang. Manual via
     * PresensiAdmin::batalkanSuratPanggilan tetap tersedia.
     *
     * @return int jumlah surat yang dibatalkan otomatis
     */
    public static function sinkronkan(int $bulan, int $tahun, array $ctx): int
    {
        $db = \Config\Database::connect();
        $batal = 0;

        $aktif = $db->table(self::TABEL)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'aktif')
            // Surat manual adalah keputusan sadar admin — tingkatnya memang
            // sengaja bisa tidak cocok dengan jumlah alpha, jadi cron tidak
            // boleh membatalkannya diam-diam.
            ->where('sumber', self::SUMBER_CRON)
            ->get()->getResult();
        if (empty($aktif)) {
            return 0;
        }

        $rekap = self::rekapAlphaBulan($ctx);
        $now = date('Y-m-d H:i:s');

        foreach ($aktif as $surat) {
            $sid = (int) ($surat->id_siswa ?? 0);
            $alpha = isset($rekap[$sid]) ? (int) ($rekap[$sid]['alpha'] ?? 0) : 0;
            if ($alpha >= (int) ($surat->level ?? 0)) {
                continue; // masih sah
            }
            $db->table(self::TABEL)->where('id', $surat->id)->update([
                'status' => 'dibatalkan',
                'keterangan_batal' => 'Otomatis: alpha turun menjadi ' . $alpha . ' (ambang level ' . $surat->level . ') per ' . substr($now, 0, 10),
                'updated_at' => $now,
            ]);
            if ($db->affectedRows() === 1) {
                $batal++;
            }
        }

        return $batal;
    }

    /**
     * Buat satu surat manual (dari menu admin).
     *
     *_level_ di sini adalah ambang yang dipilih admin, bukan hasil hitung.
     * Admin boleh membuat surat meskipun alpha belum melewati ambang —
     * misalnya untuk kasus khusus yang tidak tercermin di sistem.
     *
     * @return array{ok:bool, message:string, id?:int}
     */
    public static function buatManual(
        int $idSiswa,
        int $bulan,
        int $tahun,
        int $level,
        string $keterangan = ''
    ): array {
        if ($idSiswa <= 0) {
            return ['ok' => false, 'message' => 'Siswa tidak valid.'];
        }
        // Hanya siswa AKTIF boleh diterbitkan surat.
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('buku_induk')) {
                $st = $db->table('buku_induk')->select('status')
                    ->where('id_siswa', $idSiswa)->get()->getRow();
                if ($st && (string) ($st->status ?? '1') !== '1') {
                    return ['ok' => false, 'message' => 'Siswa sudah tidak aktif (lulus/pindah/keluar).'];
                }
            }
        } catch (\Throwable $e) {
        }
        if (!in_array($level, self::AMBANG, true)) {
            return ['ok' => false, 'message' => 'Tingkat surat tidak valid.'];
        }
        if ($bulan < 1 || $bulan > 12) {
            return ['ok' => false, 'message' => 'Bulan tidak valid.'];
        }
        if ($tahun < 2000 || $tahun > 2100) {
            return ['ok' => false, 'message' => 'Tahun tidak valid.'];
        }

        try {
            $ctx = self::buildContext($bulan, $tahun);
            $stat = self::alphaSiswa($idSiswa, $ctx);
            $terbit = self::insertJikaBelumAda(
                $idSiswa,
                $bulan,
                $tahun,
                $level,
                (int) $stat['alpha'],
                (int) $stat['terlambat'],
                self::SUMBER_MANUAL,
                $keterangan
            );

            if ($terbit === null) {
                return [
                    'ok' => false,
                    'message' => 'Sudah ada surat untuk siswa ini pada periode dan tingkat yang sama.',
                ];
            }

            return ['ok' => true, 'message' => 'Surat panggilan berhasil dibuat.', 'id' => $terbit];
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] buatManual gagal: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Gagal membuat surat — periksa log aplikasi.'];
        }
    }

    /**
     * Insert satu surat, atau null kalau surat aktif pada tingkat itu sudah ada.
     *
     * Pengecekan dilakukan dengan SELECT dulu supaya percobaan kedua
     * mengembalikan null (bukan exception dari unique index). Unique index
     * tetap dipertahankan sebagai pengaman terakhir terhadap dua request
     * yang benar-benar bersamaan.
     *
     * Surat periode/tingkat yang sudah ada tapi pernah dibatalkan dihidupkan
     * lagi (id barisnya dikembalikan), bukan dianggap ganda.
     *
     * @return int|null id baris baru atau yang dihidupkan lagi, null kalau sudah aktif
     */
    private static function insertJikaBelumAda(
        int $idSiswa,
        int $bulan,
        int $tahun,
        int $level,
        int $jumlahAlpha,
        int $jumlahTerlambat,
        string $sumber,
        string $keterangan = ''
    ): ?int {
        $db = \Config\Database::connect();

        // Muat semua surat periode ini untuk satu siswa sekaligus (satu query,
        // bukan satu per ambang) agar pengecekan slot permanen murah.
        $baris = $db->table(self::TABEL)
            ->where('id_siswa', $idSiswa)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()->getResult();

        $sudahAda = null;      // baris pada level yang sama (jika ada)
        $levelTerisi = [];     // level => status, semua level yang pernah terisi
        foreach ($baris as $b) {
            $lv = (int) ($b->level ?? 0);
            $levelTerisi[$lv] = (string) ($b->status ?? 'aktif');
            if ($lv === $level) {
                $sudahAda = $b;
            }
        }

        // ── SLOT PERMANEN / MONOTONIK ──────────────────────────────
        // Dalam satu periode (siswa, bulan, tahun) hanya boleh ada SATU surat
        // aktif: tingkat terberat yang telah tercapai, dan tingkat tidak
        // pernah turun. Aturan:
        //
        //   1. Tingkat lebih ringan tidak boleh diterbitkan setelah tingkat
        //      lebih berat pernah terisi — termasuk yang statusnya
        //      'dibatalkan' (slot permanen). Mencegah "surat sia-sia" ke
        //      orang tua (level 3, lalu 5, lalu 7, atau sebaliknya).
        //   2. Tingkat yang sama tidak boleh dibuat dua kali saat aktif;
        //      surat yang pernah dibatalkan pada level yang sama
        //      di-reaktivasi dengan angka terbaru.
        //   3. Eskalasi ke tingkat lebih berat membatalkan surat aktif di
        //      tingkat lebih ringan (surat berat menggantikan ringan), jadi
        //      akhir bulan siswa hanya memiliki satu surat aktif pada
        //      tingkat terberat yang tercapai.
        //
        // Pola yang sama telah diterapkan pada SuratTeguranService; di sini
        // berlaku baik untuk cron otomatis maupun pembuatan manual.
        foreach (array_reverse(self::AMBANG) as $ambang) {
            if ($ambang < $level) {
                break; // hanya ambang >= level yang dianggap sebagai slot
            }
            if ($ambang === $level) {
                // Level itu sendiri ditangani di bawah (aktif = duplikat,
                // dibatalkan = reaktivasi). Jangan blokir di sini.
                continue;
            }
            if (isset($levelTerisi[$ambang])) {
                return null; // slot lebih berat sudah pernah terisi, batal terbit
            }
        }

        if ($sudahAda) {
            // Surat aktif tidak dibuat dua kali. Surat yang pernah dibatalkan
            // dihidupkan lagi dengan angka terbaru: arsipnya (nomor, tanggal
            // buat, penerima) tetap, dan siswa tidak kehilangan tingkat itu
            // selamanya hanya karena alphanya sempat turun.
            if (($sudahAda->status ?? 'aktif') === 'aktif') {
                return null;
            }

            $now = date('Y-m-d H:i:s');
            $db->table(self::TABEL)->where('id', $sudahAda->id)->update([
                'status'           => 'aktif',
                'jumlah_alpha'     => $jumlahAlpha,
                'jumlah_terlambat' => $jumlahTerlambat,
                'keterangan'       => $keterangan !== '' ? mb_substr($keterangan, 0, 255) : $sudahAda->keterangan,
                'keterangan_batal' => null,
                'updated_at'       => $now,
            ]);

            return (int) $sudahAda->id;
        }

        // Eskalasi: batalkan surat aktif di tingkat lebih ringan (jika ada),
        // karena surat yang akan terbit di tingkat lebih berat menggantikan
        // yang ringan. Tingkat lebih berat tidak akan ada di sini (sudah
        // di-blokir di atas), sehingga tersisa hanya tingkat lebih ringan.
        $now = date('Y-m-d H:i:s');
        foreach ($baris as $b) {
            $lv = (int) ($b->level ?? 0);
            if ($lv >= $level || $lv <= 0) {
                continue; // hanya tingkat lebih ringan yang di-eskalasi
            }
            if ((($b->status ?? 'aktif') !== 'aktif')) {
                continue; // sudah dibatalkan, biarkan arsip utuh
            }
            $db->table(self::TABEL)->where('id', $b->id)->update([
                'status'           => 'dibatalkan',
                'keterangan_batal' => 'Digantikan oleh surat tingkat ' . $level . ' per ' . substr($now, 0, 10),
                'updated_at'       => $now,
            ]);
        }

        $now = date('Y-m-d H:i:s');

        $row = [
            'id_siswa'         => $idSiswa,
            'bulan'            => $bulan,
            'tahun'            => $tahun,
            'level'            => $level,
            'jumlah_alpha'     => $jumlahAlpha,
            'jumlah_terlambat' => $jumlahTerlambat,
            'nomor_urut'       => self::nomorUrutBerikutnya($tahun),
            'sumber'           => $sumber,
            'status'           => 'aktif',
            'keterangan'       => $keterangan !== '' ? mb_substr($keterangan, 0, 255) : null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        // Penerima disalin dari master_siswa saat surat terbit, supaya
        // perubahan data orang tua di kemudian hari tidak mengubah
        // isi surat yang sudah terbit.
        $row += self::snapshotPenerima($idSiswa);

        $db->table(self::TABEL)->insert($row);

        return (int) $db->insertID();
    }

    /**
     * Nomor urut berikutnya dalam satu tahun.
     *
     * Dipakai untuk format nomor surat, jadi harus naik berurutan.
     * `nomor_urut` berdasar tahun karena nomor surat sekolah umumnya
     * diulang tiap tahun.
     */
    public static function nomorUrutBerikutnya(int $tahun): int
    {
        $db = \Config\Database::connect();
        $last = $db->table(self::TABEL)
            ->selectMax('nomor_urut', 'max_urut')
            ->where('tahun', $tahun)
            ->get()->getRow();

        return (int) ($last->max_urut ?? 0) + 1;
    }

    /**
     * Ambil nama & no. HP penerima dari master_siswa, prioritkan wali.
     *
     * Urutan: wali → ayah → ibu. Wali dipilih lebih dulu karena di
     * sekolah, wali adalah orang yang paling sering dihubungi untuk
     * urusan kediplinan siswa.
     *
     * @return array{penerima:?string, nohp_penerima:?string}
     */
    public static function snapshotPenerima(int $idSiswa): array
    {
        $db = \Config\Database::connect();

        try {
            $row = $db->table('master_siswa')
                ->select('nama_ayah, nohp_ayah, nama_ibu, nohp_ibu, nama_wali, nohp_wali')
                ->where('id_siswa', $idSiswa)
                ->get()->getRow();
        } catch (\Throwable $e) {
            log_message('error', '[SuratPanggilan] gagal baca penerima: ' . $e->getMessage());

            return ['penerima' => null, 'nohp_penerima' => null];
        }

        if (!$row) {
            return ['penerima' => null, 'nohp_penerima' => null];
        }

        $pasangan = [
            [$row->nama_wali, $row->nohp_wali],
            [$row->nama_ayah, $row->nohp_ayah],
            [$row->nama_ibu,  $row->nohp_ibu],
        ];

        foreach ($pasangan as [$nama, $nohp]) {
            $nama = trim((string) $nama);
            $nohp = trim((string) $nohp);
            if ($nama !== '' || $nohp !== '') {
                return [
                    'penerima'     => $nama !== '' ? $nama : null,
                    'nohp_penerima' => $nohp !== '' ? $nohp : null,
                ];
            }
        }

        return ['penerima' => null, 'nohp_penerima' => null];
    }

    // ── hitung ulang ────────────────────────────────────────────────

    /**
     * Hitung ulang surat aktif satu periode agar sesuai data absen real.
     *
     * Snapshot `jumlah_alpha` / `jumlah_terlambat` di dalam surat adalah
     * foto saat surat terbit. Kalau presensi dikoreksi/dihapus setelah
     * surat terbit (atau cron evaluasi memakai data basi), angka di daftar
     * dan di PDF cetak-ulang tidak lagi mencerminkan kenyataan.
     *
     * Untuk tiap surat berstatus aktif pada periode:
     *   - alpha live < level surat → surat dibatalkan otomatis (syarat
     *     sudah gugur), sama seperti sinkronkan().
     *   - selain itu, snapshot yang berbeda dari hitungan live
     *     diperbarui (jumlah_alpha, jumlah_terlambat).
     *
     * Level TIDAK diubah: eskalasi tingkat tetap lewat generate()/manual
     * (satu siswa satu surat per level per bulan) supaya arsip tiap
     * tingkat tetap utuh. Data penerima juga TIDAK disentuh — itu foto
     * penerima saat surat terbit.
     *
     * @return array{dicek:int, diperbarui:int, dibatalkan:int}
     */
    public static function hitungUlang(int $bulan, int $tahun, array $ctx): array
    {
        $db = \Config\Database::connect();
        $hasil = ['dicek' => 0, 'diperbarui' => 0, 'dibatalkan' => 0];

        if (!$db->tableExists(self::TABEL)) {
            return $hasil;
        }

        $aktif = $db->table(self::TABEL)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'aktif')
            ->get()->getResult();
        if (empty($aktif)) {
            return $hasil;
        }

        $rekap = self::rekapAlphaBulan($ctx);
        $now = date('Y-m-d H:i:s');

        foreach ($aktif as $surat) {
            $hasil['dicek']++;
            $sid = (int) ($surat->id_siswa ?? 0);
            $alphaLive = isset($rekap[$sid]) ? (int) ($rekap[$sid]['alpha'] ?? 0) : 0;
            $terlambatLive = isset($rekap[$sid]) ? (int) ($rekap[$sid]['terlambat'] ?? 0) : 0;
            $level = (int) ($surat->level ?? 0);

            // Syarat sudah gugur → batalkan, jangan perbarui angka. Surat
            // manual kecuali: tingkatnya pilihan admin, bukan hasil hitungan,
            // jadi alphanya boleh di bawah ambang. Angkanya tetap diselaraskan.
            if ($alphaLive < $level && (string) ($surat->sumber ?? self::SUMBER_CRON) !== self::SUMBER_MANUAL) {
                $db->table(self::TABEL)->where('id', $surat->id)->update([
                    'status' => 'dibatalkan',
                    'keterangan_batal' => 'Otomatis (hitung ulang): alpha turun menjadi ' . $alphaLive . ' (ambang level ' . $level . ') per ' . substr($now, 0, 10),
                    'updated_at' => $now,
                ]);
                if ($db->affectedRows() === 1) {
                    $hasil['dibatalkan']++;
                }
                continue;
            }

            if ((int) ($surat->jumlah_alpha ?? -1) !== $alphaLive
                || (int) ($surat->jumlah_terlambat ?? -1) !== $terlambatLive) {
                $db->table(self::TABEL)->where('id', $surat->id)->update([
                    'jumlah_alpha' => $alphaLive,
                    'jumlah_terlambat' => $terlambatLive,
                    'updated_at' => $now,
                ]);
                if ($db->affectedRows() === 1) {
                    $hasil['diperbarui']++;
                }
            }
        }

        return $hasil;
    }

    // ── pembatalan ───────────────────────────────────────────────────

    /**
     * Batalkan satu surat.
     *
     * Dibatalkan, bukan dihapus: arsip surat yang sudah keluar dari
     * sekolah tetap harus bisa dibuktikan keberadaannya. `status` diubah
     * supaya PDF tidak bisa diunduh lagi dan tidak muncul sebagai surat
     * aktif di daftar.
     */
    public static function batalkan(int $id, string $alasan = ''): bool
    {
        $db = \Config\Database::connect();

        $row = $db->table(self::TABEL)->where('id', $id)->get()->getRow();
        if (!$row) {
            return false;
        }
        if (($row->status ?? 'aktif') === 'dibatalkan') {
            return true; // sudah dibatalkan, tidak perlu diulang
        }

        $set = [
            'status'           => 'dibatalkan',
            'keterangan_batal' => $alasan !== '' ? mb_substr($alasan, 0, 255) : null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        return (bool) $db->table(self::TABEL)->where('id', $id)->update($set);
    }
}