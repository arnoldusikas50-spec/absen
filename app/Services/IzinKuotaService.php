<?php

namespace App\Services;

use App\Models\AttendanceSettingModel;

/**
 * IzinKuotaService — sumber kebenaran tunggal untuk aturan kuota izin/cuti.
 *
 * Sebelum ada kelas ini, perhitungan kuota diduplikasi di
 * `Guru\Attendance::leaveRequest()` dan
 * `Admin\PresensiAdmin::saveManualPermit()`, dan keduanya menghitung
 * HARI KALENDER. Padahal `approvePermit()` melewati hari libur saat
 * menulis `absensi_harian`, sehingga guru kehilangan kuota untuk
 * akhir pekan yang tidak menghasilkan baris absensi sama sekali.
 * Cuti satu minggu memotong 7 kuota tetapi hanya menulis 5 baris.
 *
 * Yang dikumpulkan di sini:
 *  - normalisasi jenis (pola 'izin'/'ijin'/'IJIN'/'Ijin'_amount legacy)
 *  - hitung hari kerja efektif memakai attendance_count_effective_days()
 *  - batas per pengajuan dipisah dari batas kumulatif tahunan
 *  - rentang lintas tahun dipecah per tahun kalender, bukan seluruhnya
 *    dibebankan ke tahun mulai
 *  - sakit/dinas punya batasnya sendiri (sebelumnya tanpa batas sama sekali)
 *
 * Semua pemanggil cukup memanggil cek() dan membaca `pesan` ketika
 * `ok` false. Batas bernilai 0 berarti "tanpa batas" (bukan 0 hari).
 */
class IzinKuotaService
{
    /** Label yang dipakai di pesan error ke pengguna. */
    private const LABEL = [
        'ijin'   => 'Izin',
        'sakit'  => 'Sakit',
        'cuti'   => 'Cuti',
        'dinas'  => 'Dinas',
    ];

    /**
     * Kuota tahunan per jenis. 0 = tanpa batas.
     * `dinas` sengaja 0: dinas adalah tugas resmi yang dihitung sebagai
     * HADIR (poin + hadir), bukan hak yang dikonsumsi.
     */
    private const KUOTA_TAHUNAN = [
        'ijin'   => 'max_izin_hari',
        'sakit'  => 'max_sakit_hari',
        'cuti'   => 'max_cuti_hari',
        'dinas'  => '',
    ];

    /**
     * Batas durasi per pengajuan. 0 = tanpa batas.
     */
    private const BATAS_PENGAJUAN = [
        'ijin'   => 'max_izin_per_pengajuan',
        'sakit'  => 'max_sakit_per_pengajuan',
        'cuti'   => 'max_cuti_per_pengajuan',
        'dinas'  => 'max_dinas_per_pengajuan',
    ];

    /**
     * Status absensi_izin yang SEDANG memblokir kuota (pending + approved).
     *
     * Bentuk lama '0' dan '1' ikut disertakan. `patchAbsensiIzinTable()`
     * memang menormalkan keduanya ke pending/approved, tapi routine itu
     * hanya dipanggil dari controller ADMIN — leaveRequest() guru tidak
     * pernah memanggilnya. Kalau baris legacy seperti itu masih ada di
     * instalasi ini, `WHERE status IN ('pending','approved')` akan
     * melewatkannya dan hari tersebut TIDAK ikut memotong kuota: izin
     * lama menjadi jalur bebas melewati batas.
     *
     * Daftar ini hanya untuk dibaca, bukan ditulis — writing tetap
     * selalu memakai status kanonik.
     */
    private const STATUS_MAKAN_KUOTA = ['pending', 'approved', '0', '1'];

    /** Default bila kolom setting belum terisi. */
    private const DEFAULT_KUOTA_TAHUNAN = [
        'ijin'   => 3,
        'sakit'  => 12,
        'cuti'   => 12,
        'dinas'  => 0,
    ];

    private const DEFAULT_BATAS_PENGAJUAN = [
        'ijin'   => 3,
        'sakit'  => 3,
        'cuti'   => 12,
        'dinas'  => 30,
    ];

    private $db;
    private $setting;
    private ?array $konteksLibur = null;

    /** Cache hari libur nasional per rentang tanggal. */
    private array $cacheNasional = [];

    public function __construct($db = null, $setting = null)
    {
        $this->db      = $db ?? \Config\Database::connect();
        $this->setting = $setting;
    }

    // ── Normalisasi jenis ───────────────────────────────────────────────

    /**
     * Kembalikan jenis kanonik: ijin | sakit | cuti | dinas.
     * Semua ejaan lain (izin, IJIN, Ijin, dll) dilipat ke 'ijin'.
     */
    public static function normalizeJenis(?string $jenis): string
    {
        $j = strtolower(trim((string) $jenis));
        if ($j === 'izin') {
            return 'ijin';
        }
        return in_array($j, ['ijin', 'sakit', 'cuti', 'dinas'], true) ? $j : '';
    }

    /**
     * Semua ejaan yang mungkin tersimpan di DB untuk satu jenis kanonik.
     *
     * Penting: kolom `jenis` adalah VARCHAR, bukan ENUM dengan
     * konsisten. Data lama memakai 'izin' (masih dibaca sebagai alias di
     * LeaveModel::getAll()), sehingga WHERE jenis IN ('ijin','IJIN','Ijin')
     * — pola lama — MELoloskan baris 'izin' dari akumulasi kuota.
     * Dawalkan sudah ternormalisasi ke 'ijin', jadi di sini cukup
     * varian itu saja.
     */
    public static function varianJenis(string $canon): array
    {
        if ($canon === 'ijin') {
            return ['ijin', 'izin', 'IJIN', 'IZIN', 'Ijin', 'Izin'];
        }
        return [$canon, strtoupper($canon), ucfirst($canon)];
    }

    public static function label(string $canon): string
    {
        return self::LABEL[$canon] ?? 'Izin';
    }

    // ── Konteks libur ──────────────────────────────────────────────────

    /**
     * Sumber libur yang sama dengan yang dipakai saat menulis absensi:
     * jadwal kerja GTK (kanonik) + hari libur mingguan turunan + nasional.
     */
    public function konteksLibur(): array
    {
        if ($this->konteksLibur !== null) {
            return $this->konteksLibur;
        }

        $hariLibur = AttendancePolicy::hariLiburMingguan();

        // Jadwal per hari disimpan apa adanya (object), bukan boolean:
        // attendance_is_weekly_holiday() membaca $row->is_libur dari
        // object/array; di-cast jadi bool akan membuat setiap hari libur
        // jadwal diam-diam terbaca sebagai hari kerja.
        $jadwal = AttendancePolicy::jadwalKerja();

        $this->konteksLibur = [
            'hariLibur' => $hariLibur,
            'jadwal'    => $jadwal,
            'adaNasional' => (bool) $this->db->tableExists('absensi_hari_libur'),
        ];

        return $this->konteksLibur;
    }

    /**
     * Hari libur nasional dalam rentang, sebagai map ['Y-m-d' => true].
     *
     * Cek dulu apakah ada entri cache yang MENCAKUP rentang diminta —
     * ini yang membuat terpakaiTahun() tidak jadi N+1 query. Tanpa ini,
     * setiap baris izin yang sudah ada memanggil tabel hari libur
     * dengan rentangnya sendiri, jadi satu pengecekan kuota bisa menembak
     * puluhan query sia-sia.
     */
    public function nasionalDalam(string $start, string $end): array
    {
        $key = $start . '|' . $end;
        if (isset($this->cacheNasional[$key])) {
            return $this->cacheNasional[$key];
        }

        foreach ($this->cacheNasional as $rentang => $isi) {
            [$a, $b] = explode('|', $rentang, 2);
            if ($a <= $start && $b >= $end) {
                return $this->cacheNasional[$key] = $isi;
            }
        }

        $out = [];
        $ctx = $this->konteksLibur();
        if ($ctx['adaNasional']) {
            try {
                $rows = $this->db->table('absensi_hari_libur')
                    ->select('tanggal')
                    ->where('tanggal >=', $start)
                    ->where('tanggal <=', $end)
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $out[substr((string) ($r->tanggal ?? ''), 0, 10)] = true;
                }
            } catch (\Throwable $e) {
                log_message('error', 'IzinKuotaService: gagal memuat hari libur nasional: ' . $e->getMessage());
            }
        }

        return $this->cacheNasional[$key] = $out;
    }

    /**
     * Apakah satu tanggal libur. Menyerahkan jawabannya ke AttendancePolicy,
     * sumber tunggal yang sama dengan jalur absensi, rekap, dan cron.
     */
    public function isLibur(string $date): bool
    {
        return AttendancePolicy::isLibur($date);
    }

    /**
     * Hari yang benar-benar dihitung kuota.
     *
     * Default (kuota_hari_kerja = 1): hanya hari kerja efektif — sama
     * dengan jumlah baris absensi yang akan ditulis approvePermit.
     * Setting 0 mengembalikan hitungan hari kalender untuk sekolah yang
     * memang masih perlu perilaku lama.
     */
    public function hitungHari(string $start, string $end): int
    {
        $ctx = $this->konteksLibur();

        if ((int) ($this->getSetting()->kuota_hari_kerja ?? 1) === 0) {
            $selisih = (int) ((strtotime($end) - strtotime($start)) / 86400) + 1;
            return max(0, $selisih);
        }

        return attendance_count_effective_days(
            $start,
            $end,
            $ctx['hariLibur'],
            $this->nasionalDalam($start, $end),
            $ctx['jadwal']
        );
    }

    // ── Batas ───────────────────────────────────────────────────────────

    /** Kuota kumulatif tahunan; 0 = tanpa batas. */
    public function batasTahunan(string $canon): int
    {
        $kolom = self::KUOTA_TAHUNAN[$canon] ?? '';
        if ($kolom === '') {
            return 0;
        }
        $nilai = $this->getSetting()->{$kolom} ?? null;
        if ($nilai === null || $nilai === '') {
            return self::DEFAULT_KUOTA_TAHUNAN[$canon] ?? 0;
        }
        return max(0, (int) $nilai);
    }

    /** Batas durasi per pengajuan; 0 = tanpa batas. */
    public function batasPengajuan(string $canon): int
    {
        $kolom = self::BATAS_PENGAJUAN[$canon] ?? '';
        if ($kolom === '') {
            return 0;
        }
        $nilai = $this->getSetting()->{$kolom} ?? null;
        if ($nilai === null || $nilai === '') {
            return self::DEFAULT_BATAS_PENGAJUAN[$canon] ?? 0;
        }
        return max(0, (int) $nilai);
    }

    // ── Akumulasi ───────────────────────────────────────────────────────

    /**
     * Hari kuota yang sudah terpakai untuk satu jenis pada satu tahun.
     *
     * Hanya pengajuan pending + approved yang dihitung: pending menahan
     * kuota supaya guru tidak Bisa menumpuk pengajuan, dan approved
     * adalah kuota yang benar-benar terpakai.
     *
     * Baris di-clip ke batas tahun, dan hari liburnya ikut dibuang agar
     * totalnya sama dengan yang akan ditulis ke absensi_harian.
     *
     * @param int $idAbaikan Baris izin yang sedang diedit (0 = tidak ada).
     *                       Diexclude supaya pengajuan yang sama tidak
     *                       menghitung dirinya sendiri.
     */
    public function terpakaiTahun(int $userId, string $canon, int $tahun, int $idAbaikan = 0, ?string $kategoriCuti = null): int
    {
        if ($userId <= 0 || $canon === '') {
            return 0;
        }

        $batasAwal  = $tahun . '-01-01';
        $batasAkhir = $tahun . '-12-31';

        // Panaskan cache libur nasional untuk SELURUH tahun sekali saja.
        // Semua hitungHari() di bawah memakai potongan yang terklip di dalam
        // rentang ini, jadi satu query ini menutup semuanya.
        $this->nasionalDalam($batasAwal, $batasAkhir);

        try {
            $builder = $this->db->table('absensi_izin')
                ->select('id, tgl_mulai, tgl_selesai')
                ->where('user_id', $userId)
                ->whereIn('status', self::STATUS_MAKAN_KUOTA)
                ->whereIn('jenis', self::varianJenis($canon))
                ->where('tgl_mulai <=', $batasAkhir)
                ->where('tgl_selesai >=', $batasAwal);

            // Kategori cuti: hanya kategori yang memotong kuota cuti
            // tahunan (kategori NULL/'' legacy + 'tahunan') yang dihitung;
            // Cuti Besar / Melahirkan / CAP adalah hak mandiri dan tidak
            // makan kuota tahunan (CutiKategoriService).
            if ($canon === 'cuti' && $this->db->fieldExists('kategori_cuti', 'absensi_izin')) {
                if (! CutiKategoriService::pakaiKuotaTahunan((string) $kategoriCuti)) {
                    $builder->where('kategori_cuti', $kategoriCuti);
                } else {
                    $builder->groupStart()
                            ->where('kategori_cuti', null)
                            ->orWhere('kategori_cuti', '')
                            ->orWhere('kategori_cuti', 'tahunan')
                            ->groupEnd();
                }
            }

            if ($idAbaikan > 0) {
                $builder->where('id !=', $idAbaikan);
            }

            $baris = $builder->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', 'IzinKuotaService: hitung kuota gagal: ' . $e->getMessage());
            return 0;
        }

        $total = 0;
        foreach ($baris as $row) {
            $mulai   = substr((string) ($row->tgl_mulai ?? ''), 0, 10);
            $selesai = substr((string) ($row->tgl_selesai ?? ''), 0, 10);
            if ($mulai === '' || $selesai === '') {
                continue;
            }
            // Clip ke tahun berjalan supaya pengajuan lintas tahun dihitung
            // hanya pada bagiannya di tahun ini.
            $mulai   = max($mulai, $batasAwal);
            $selesai = min($selesai, $batasAkhir);
            if ($mulai > $selesai) {
                continue;
            }
            $total += $this->hitungHari($mulai, $selesai);
        }

        return $total;
    }

    /**
     * Pecah rentang menjadi potongan per tahun kalender.
     *
     * Pengajuan 28 Des – 2 Jan tidak boleh seluruhnya dipotong dari kuota
     * Desember: 2 hari terakhirnya masuk ke kuota Januari.
     *
     * @return array<int, array{tahun:int, mulai:string, selesai:string}>
     */
    public function perTahun(string $start, string $end): array
    {
        $potongan = [];
        $tahun    = (int) substr($start, 0, 4);
        $tahunEnd = (int) substr($end, 0, 4);

        // Pengaman: rentang sudah tervalidasi <= 1 tahun di controller,
        // tapi ini tetap aman bila dipanggil terpisah.
        if ($tahunEnd < $tahun) {
            $tahunEnd = $tahun;
        }

        for ($t = $tahun; $t <= $tahunEnd; $t++) {
            $mulaiTahun   = $t . '-01-01';
            $selesaiTahun = $t . '-12-31';
            $mulai   = max($start, $mulaiTahun);
            $selesai = min($end, $selesaiTahun);
            if ($mulai > $selesai) {
                continue;
            }
            $potongan[] = ['tahun' => $t, 'mulai' => $mulai, 'selesai' => $selesai];
        }

        return $potongan;
    }

    // ── Guard utama ─────────────────────────────────────────────────────

    /**
     * Periksa satu pengajuan terhadap batas per pengajuan + kuota tahunan.
     *
     * Panggil DI DALAM transaksi yang juga mengunci baris guru bila
     * pemanggil butuh atomicity terhadap submit paralel.
     *
     * `sisa` selalu berarti KUOTA YANG TERSISA UNTUK GURU SEBELUM
     * pengajuan ini — baik saat lolos maupun saat ditolak. Nilai ini
     * sengaja TIDAK dikurangi durasi pengajuan yang sedang dinilai, supaya
     * pemanggil cukup membandingkan `sisa` dengan `hari` untuk tahu
     * muat atau tidak; tidak ada lagi dua arti yang berbeda antara
     * cabang sukses dan cabang gagal.
     *
     * @return array{
     *   ok:bool, pesan:string, hari:int, tahun:int,
     *   batas:int, terpakai:int, sisa:int, batasPengajuan:int
     * }
     */
    public function cek(int $userId, string $jenis, string $start, string $end, int $idAbaikan = 0, ?string $kategoriCuti = null): array
    {
        $canon = self::normalizeJenis($jenis);
        if ($canon === '') {
            $canon = 'ijin';
        }

        $label  = self::label($canon);
        $kategoriKhusus = $canon === 'cuti'
            // Kategori besar/melahirkan/cap adalah hak mandiri: batas
            // durasinya dipegang CutiKategoriService (hari kalender),
            // dan kuota tahunan cuti TIDAK tersentuh.
            && $kategoriCuti !== null
            && $kategoriCuti !== ''
            && ! CutiKategoriService::pakaiKuotaTahunan($kategoriCuti)
            && CutiKategoriService::valid((string) $kategoriCuti);

        // Pesan kuota menyebut "Cuti Tahunan" ketika kategorinya jelas —
        // membedakan dari hak besar/melahirkan/CAP yang tidak makan kuota.
        if ($canon === 'cuti' && ! $kategoriKhusus
            && $kategoriCuti !== null && trim((string) $kategoriCuti) !== ''
            && CutiKategoriService::valid((string) $kategoriCuti)) {
            $label = CutiKategoriService::label((string) $kategoriCuti);
        }

        $batasPengajuan = $kategoriKhusus ? 0 : $this->batasPengajuan($canon);
        $batasTahun     = $kategoriKhusus ? 0 : $this->batasTahunan($canon);

        $hasil = [
            'ok'             => true,
            'pesan'          => '',
            'hari'           => 0,
            'tahun'          => (int) substr($start, 0, 4),
            'batas'          => $batasTahun,
            'terpakai'       => 0,
            'sisa'           => $batasTahun,
            'batasPengajuan' => $batasPengajuan,
        ];

        // Seluruh rentang, untuk batas per pengajuan.
        $hasil['hari'] = $this->hitungHari($start, $end);

        // Rentang yang seluruhnya libur → tidak ada baris absensi yang
        // akan ditulis, jadi tidak ada kuota yang terpakai.
        if ($hasil['hari'] === 0) {
            return $hasil;
        }

        if ($batasPengajuan > 0 && $hasil['hari'] > $batasPengajuan) {
            $satuan = $this->satuanHari();
            $hasil['ok']    = false;
            $hasil['pesan'] = "Maksimal durasi $label per pengajuan adalah $batasPengajuan $satuan. Pengajuan Anda: {$hasil['hari']} $satuan.";
            return $hasil;
        }

        if ($batasTahun <= 0) {
            // Tanpa kuota tahunan: sisa tidak relevan.
            $hasil['sisa'] = 0;
            return $hasil;
        }

        // Per tahun: pengajuan lintas tahun diuji per bagiannya.
        foreach ($this->perTahun($start, $end) as $potongan) {
            $tahun    = $potongan['tahun'];
            $terpakai = $this->terpakaiTahun($userId, $canon, $tahun, $idAbaikan);
            $diminta  = $this->hitungHari($potongan['mulai'], $potongan['selesai']);

            if ($terpakai + $diminta > $batasTahun) {
                $sisa   = max(0, $batasTahun - $terpakai);
                $satuan = $this->satuanHari();

                $hasil['ok']       = false;
                $hasil['tahun']    = $tahun;
                $hasil['terpakai'] = $terpakai;
                // Sama seperti cabang sukses: sisa = yang tersedia
                // sebelum pengajuan ini.
                $hasil['sisa']     = $sisa;
                $hasil['pesan']    = "Kuota $label tahun $tahun tersisa $sisa $satuan (terpakai $terpakai dari $batasTahun $satuan). Pengajuan Anda: $diminta $satuan.";
                return $hasil;
            }

            // Simpan tahun terakhir yang lolos supaya pemanggil bisa
            // menampilkan sisa kuota yang relevan.
            $hasil['tahun']    = $tahun;
            $hasil['terpakai'] = $terpakai;
            $hasil['sisa']     = max(0, $batasTahun - $terpakai);
        }

        return $hasil;
    }

    /**
     * Ringkasan kuota untuk UI — sisa hari per jenis untuk satu tahun.
     *
     * @return array<string, array{label:string, batas:int, terpakai:int, sisa:?int, batasPengajuan:int, aktif:bool}>
     */
    public function ringkasan(int $userId, ?int $tahun = null): array
    {
        $tahun = $tahun ?: (int) date('Y');
        $out   = [];

        foreach (['ijin', 'sakit', 'cuti', 'dinas'] as $canon) {
            $batasTahun = $this->batasTahunan($canon);
            // Ringkasan cuti selalu menyajikan saldo CUTI TAHUNAN:
            // kategori besar/melahirkan/cap bukan bagian dari kuota ini.
            $terpakai   = $this->terpakaiTahun($userId, $canon, $tahun, 0, $canon === 'cuti' ? 'tahunan' : null);

            $out[$canon] = [
                'label'          => self::label($canon),
                'batas'          => $batasTahun,
                'terpakai'       => $terpakai,
                // null = tidak ada kuota tahunan, bukan "sisa 0".
                'sisa'           => $batasTahun > 0 ? max(0, $batasTahun - $terpakai) : null,
                'batasPengajuan' => $this->batasPengajuan($canon),
                'aktif'          => $batasTahun > 0 || $this->batasPengajuan($canon) > 0,
            ];
        }

        return $out;
    }

    // ── Internal ────────────────────────────────────────────────────────

    private function satuanHari(): string
    {
        return (int) ($this->getSetting()->kuota_hari_kerja ?? 1) === 0 ? 'hari kalender' : 'hari kerja';
    }

    private function getSetting()
    {
        if ($this->setting === null) {
            $this->setting = (new AttendanceSettingModel())->first() ?? (object) [];
        }
        return $this->setting;
    }
}