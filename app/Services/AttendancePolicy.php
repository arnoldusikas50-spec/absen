<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\ShiftModel;

/**
 * Satu sumber kebenaran untuk dua pertanyaan yang sebelumnya dijawab
 * berbeda-beda oleh setiap modul presensi:
 *
 *   1. Batas masuk & toleransi (grace) untuk satu tanggal — guru dan siswa.
 *   2. Tanggal ini hari libur atau hari kerja.
 *
 * Prioritas jadwal (dipakai identik untuk guru dan siswa):
 *   absensi_shift per guru  →  absensi_jadwal_kerja per hari  →  absensi_setting
 *   Grace: shift.grace_menit → absensi_setting.grace_period → self::GRACE_DEFAULT
 *
 * Rumus telat baku (self::telat()):
 *   threshold      = tanggal + jam_masuk
 *   batasAkhir     = threshold + grace*60 + 59   ← detik ke-59 masih tepat waktu
 *   ONTIME         = waktu absen <= batasAkhir
 *   telat_menit    = ceil((waktu absen - batasAkhir) / 60)
 *
 * +59 dan "telat dihitung dari akhir grace" adalah perilaku jalur guru yang
 * sudah dipakai 12 lokasi dan juga hasil perbandingan menit di rekap siswa;
 * jalur scan/poin/piket sebelumnya memakai detik penuh tanpa +59 sehingga satu
 * kejadian bisa dilaporkan telat 1 menit di kiosk tetapi tepat waktu di rekap.
 *
 * Libur: hari_libur_mingguan (normalisasi trim/kapitalisasi) ATAU
 * absensi_jadwal_kerja.is_libur ATAU absensi_hari_libur per tanggal.
 *
 * Cache per request: satu bacaan per tabel, dan reset() wajib dipanggil bila
 * setting diubah di dalam request yang sama (lihat Settings::saveAttendance).
 */
final class AttendancePolicy
{
    /** Toleransi bila grace belum pernah disetel (sama dengan jalur guru). */
    public const GRACE_DEFAULT = 15;

    /** Batas grace inklusif sampai detik ke-59 pada menit terakhir. */
    public const GRACE_EPS_DETIK = 59;

    public const DEFAULT_JAM_MASUK  = '07:15:00';
    public const DEFAULT_JAM_PULANG = '14:00:00';

    /**
     * Berapa hari admin boleh meninjau baris PENDING_VERIFICATION sebelum hari
     * itu dianggap ALPHA. Selama jendela ini barisnya bukan hadir DAN bukan
     * alpha: tidak masuk denda/surat teguran, tidak masuk kartu Hadir.
     * Dipakai bersama oleh layanan penalti, cron penuntas, dan dashboard
     * supaya satu tanggal tidak pernah dinilai dua cara berbeda.
     */
    public const PENDING_REVIEW_HARI = 3;

    private const NAMA_HARI = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    private static ?object $setting = null;
    private static bool $settingLoaded = false;

    /** @var array<int, object>|null map hari ISO => baris absensi_jadwal_kerja */
    private static ?array $jadwalKerja = null;

    /** @var array<string, string>|null map tanggal Y-m-d => keterangan libur */
    private static ?array $liburNasional = null;

    /** @var array<string, array> cache jadwal per tanggal/user */
    private static array $jadwalCache = [];

    private static ?ShiftModel $shiftModel = null;

    /** Buang seluruh cache (sesudah menyimpan setting, atau antar siklus cron). */
    public static function reset(): void
    {
        self::$setting      = null;
        self::$settingLoaded = false;
        self::$jadwalKerja  = null;
        self::$liburNasional = null;
        self::$jadwalCache  = [];
        self::$shiftModel   = null;
    }

    private static function db(): BaseConnection
    {
        return \Config\Database::connect();
    }

    /** Baris utama absensi_setting (id terkecil) — null bila tabel/baris belum ada. */
    public static function setting(): ?object
    {
        if (self::$settingLoaded) {
            return self::$setting;
        }
        self::$settingLoaded = true;

        try {
            $db = self::db();
            if ($db->tableExists('absensi_setting')) {
                self::$setting = $db->table('absensi_setting')
                    ->orderBy('id', 'ASC')->limit(1)->get()->getRow() ?: null;
            }
        } catch (\Throwable $e) {
            log_message('error', '[AttendancePolicy] gagal memuat absensi_setting: ' . $e->getMessage());
            self::$setting = null;
        }

        return self::$setting;
    }

    /**
     * Hari libur mingguan, ternormalisasi menjadi ['Sat','Sun'].
     *
     * CSV dibaca apa adanya: kolom kosong berarti memang tidak ada libur
     * mingguan (status libur hari itu dipegang absensi_jadwal_kerja.is_libur),
     * sedangkan kolom yang belum pernah diisi (NULL) jatuh ke default 'Sun'
     * seperti perilaku lama. Nilai ' sun ', 'SUN', 'minggu' tetap dikenali.
     *
     * @return list<string> kode hari 3 huruf kapital awal
     */
    public static function hariLiburMingguan(): array
    {
        $setting = self::setting();
        $raw = $setting->hari_libur_mingguan ?? 'Sun';

        $out = [];
        foreach (explode(',', (string) $raw) as $kode) {
            $out[] = self::normalisasiHari($kode);
        }

        return array_values(array_filter($out, static fn($d) => $d !== ''));
    }

    /** @return array<int, object> map hari ISO (1=Senin) => baris jadwal */
    public static function jadwalKerja(): array
    {
        if (self::$jadwalKerja !== null) {
            return self::$jadwalKerja;
        }

        $map = [];
        try {
            $db = self::db();
            if ($db->tableExists('absensi_jadwal_kerja')) {
                foreach ($db->table('absensi_jadwal_kerja')
                         ->select('hari, jam_masuk, jam_pulang, is_libur')
                         ->get()->getResult() as $row) {
                    $iso = (int) ($row->hari ?? 0);
                    if ($iso >= 1 && $iso <= 7) {
                        $map[$iso] = $row;
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[AttendancePolicy] gagal memuat absensi_jadwal_kerja: ' . $e->getMessage());
        }

        return self::$jadwalKerja = $map;
    }

    /** @return array<string, string> tanggal Y-m-d => keterangan libur nasional */
    public static function liburNasional(): array
    {
        if (self::$liburNasional !== null) {
            return self::$liburNasional;
        }

        $map = [];
        try {
            $db = self::db();
            if ($db->tableExists('absensi_hari_libur')) {
                foreach ($db->table('absensi_hari_libur')->select('tanggal, keterangan')->get()->getResult() as $r) {
                    $tgl = substr((string) ($r->tanggal ?? ''), 0, 10);
                    if ($tgl !== '') {
                        $map[$tgl] = trim((string) ($r->keterangan ?? ''));
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[AttendancePolicy] gagal memuat absensi_hari_libur: ' . $e->getMessage());
        }

        return self::$liburNasional = $map;
    }

    public static function isoHari(string $tanggal): int
    {
        $ts = strtotime(self::normalisasiTanggal($tanggal));
        return $ts === false ? 1 : (int) date('N', $ts);
    }

    /** Libur mingguan: CSV pengaturan ATAU is_libur pada jadwal hari itu. */
    public static function isLiburMingguan(string $tanggal): bool
    {
        $iso  = self::isoHari($tanggal);
        $kode = self::NAMA_HARI[(int) date('w', strtotime(self::normalisasiTanggal($tanggal)))];

        if (in_array($kode, self::hariLiburMingguan(), true)) {
            return true;
        }

        $jadwal = self::jadwalKerja()[$iso] ?? null;
        if ($jadwal !== null) {
            $flag = is_array($jadwal) ? ($jadwal['is_libur'] ?? 0) : ($jadwal->is_libur ?? 0);
            return (int) $flag === 1;
        }

        return false;
    }

    public static function isLiburNasional(string $tanggal): bool
    {
        return isset(self::liburNasional()[self::normalisasiTanggal($tanggal)]);
    }

    public static function isLibur(string $tanggal): bool
    {
        return self::isLiburMingguan($tanggal) || self::isLiburNasional($tanggal);
    }

    public static function isHariKerja(string $tanggal): bool
    {
        return ! self::isLibur($tanggal);
    }

    /** Keterangan libur untuk ditampilkan/dilog; null bila hari kerja. */
    public static function alasanLibur(string $tanggal): ?string
    {
        $tanggal = self::normalisasiTanggal($tanggal);
        if (self::isLiburNasional($tanggal)) {
            $ket = self::liburNasional()[$tanggal];
            return ($ket !== '') ? $ket : 'Hari libur nasional';
        }
        if (self::isLiburMingguan($tanggal)) {
            $iso  = self::isoHari($tanggal);
            $jadwal = self::jadwalKerja()[$iso] ?? null;
            $flag = $jadwal !== null ? (int) (is_array($jadwal) ? ($jadwal['is_libur'] ?? 0) : ($jadwal->is_libur ?? 0)) : 0;
            if ($flag === 1) {
                return 'Libur per jadwal hari kerja';
            }
            return 'Hari ' . self::namaHariId($tanggal) . ' (libur mingguan)';
        }

        return null;
    }

    public static function namaHariId(string $tanggal): string
    {
        $nama = [
            'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu',
            'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu',
        ];
        $kode = self::NAMA_HARI[(int) date('w', strtotime(self::normalisasiTanggal($tanggal)))];

        return $nama[$kode] ?? $kode;
    }

    /**
     * Jadwal efektif siswa pada satu tanggal.
     *
     * @return array{jam_masuk:string, jam_pulang:string, grace_menit:int, sumber:string}
     */
    public static function jadwalSiswa(string $tanggal): array
    {
        $tanggal = self::normalisasiTanggal($tanggal);
        $key     = 'siswa|' . $tanggal;
        if (isset(self::$jadwalCache[$key])) {
            return self::$jadwalCache[$key];
        }

        $setting = self::setting();
        $jadwal  = self::jadwalKerja()[self::isoHari($tanggal)] ?? null;

        $jamMasuk  = self::pickTime($jadwal->jam_masuk ?? null)
            ?? self::pickTime($setting->jam_masuk ?? null)
            ?? self::DEFAULT_JAM_MASUK;
        $jamPulang = self::pickTime($jadwal->jam_pulang ?? null)
            ?? self::pickTime($setting->jam_pulang ?? null)
            ?? self::DEFAULT_JAM_PULANG;

        $grace = self::graceGlobal();

        return self::$jadwalCache[$key] = [
            'jam_masuk'   => $jamMasuk,
            'jam_pulang'  => $jamPulang,
            'grace_menit' => $grace,
            'sumber'      => $jadwal !== null ? 'jadwal' : 'setting',
        ];
    }

    /**
     * Jadwal efektif guru: shift per anggota → jadwal harian → setting.
     * Memakai ShiftModel agar semantik shift (hari 0 = setiap hari) tidak berubah.
     *
     * @return array{jam_masuk:string, jam_pulang:string, grace_menit:int, sumber:string}
     */
    public static function jadwalGuru(int $userId, string $tanggal): array
    {
        $tanggal = self::normalisasiTanggal($tanggal);
        $key     = 'guru|' . $userId . '|' . $tanggal;
        if (isset(self::$jadwalCache[$key])) {
            return self::$jadwalCache[$key];
        }

        $setting = self::setting() ?? (object) [];
        $jadwal  = self::jadwalKerja()[self::isoHari($tanggal)] ?? null;

        try {
            self::$shiftModel ??= new ShiftModel();
            $sched = self::$shiftModel->getScheduleForUser($userId, self::isoHari($tanggal), $jadwal, $setting);
        } catch (\Throwable $e) {
            log_message('error', '[AttendancePolicy] jadwalGuru fallback: ' . $e->getMessage());
            $sched = null;
        }

        if (! is_array($sched) || empty($sched['jam_masuk'])) {
            $siswaSched = self::jadwalSiswa($tanggal);
            $sched = [
                'jam_masuk'   => $siswaSched['jam_masuk'],
                'jam_pulang'  => $siswaSched['jam_pulang'],
                'grace_menit' => $siswaSched['grace_menit'],
                'sumber'      => 'setting',
            ];
        }

        $sched['grace_menit'] = (int) ($sched['grace_menit'] ?? self::graceGlobal());

        return self::$jadwalCache[$key] = $sched;
    }

    /**
     * Timestamp batas akhir masuk (inklusif). Scan sampai detik ke-59 pada
     * menit terakhir masih dihitung tepat waktu.
     */
    public static function batasAkhirMasukTs(string $tanggal, array $jadwal): int
    {
        $jam  = self::pickTime($jadwal['jam_masuk'] ?? null) ?? self::DEFAULT_JAM_MASUK;
        $grace = (int) ($jadwal['grace_menit'] ?? self::graceGlobal());
        $ts = strtotime(self::normalisasiTanggal($tanggal) . ' ' . $jam);
        if ($ts === false) {
            $ts = strtotime(self::normalisasiTanggal($tanggal) . ' ' . self::DEFAULT_JAM_MASUK);
        }

        return $ts + ($grace * 60) + self::GRACE_EPS_DETIK;
    }

    /** Label 'H:i' untuk umpan balik ke pengguna. */
    public static function batasMasukLabel(string $tanggal, array $jadwal): string
    {
        return date('H:i', self::batasAkhirMasukTs($tanggal, $jadwal));
    }

    /**
     * Batas akhir masuk dari sebuah label 'H:i' / 'H:i:s' yang sudah dihitung
     * pemanggil (mis. array batas masuk per hari milik rekap).
     *
     * Label yang menunjuk awal menit ('07:30' atau '07:30:00') diperlakukan
     * inklusif sampai detik ke-59, sama seperti batas hasil batasAkhirMasukTs().
     * Tanpa normalisasi ini, rekap dan scan bisa berbeda pendapat untuk kejadian
     * yang sama hanya karena format stringnya berbeda.
     */
    public static function batasTsDariLabel(string $tanggal, string $batas): ?int
    {
        $batas = trim($batas);
        if ($batas === '') {
            return null;
        }
        $ts = self::waktuKeTs($tanggal, $batas);
        if ($ts === null) {
            return null;
        }
        $detik = (int) date('s', $ts);
        $menitOnly = preg_match('/^\d{1,2}:\d{2}$/', $batas) === 1;

        return ($menitOnly || $detik === 0) ? $ts + self::GRACE_EPS_DETIK : $ts;
    }

    /**
     * Rumus baku status masuk.
     *
     * @param string $tanggal Y-m-d
     * @param string $waktu   'H:i:s', 'Y-m-d H:i:s', atau 'Y-m-d H:i'
     * @return array{status:string, telat_menit:int, datang_awal_menit:int, batas:string}
     */
    public static function telat(string $tanggal, string $waktu, array $jadwal): array
    {
        $tanggal = self::normalisasiTanggal($tanggal);
        $batas   = self::batasAkhirMasukTs($tanggal, $jadwal);
        $checkTs = self::waktuKeTs($tanggal, $waktu);

        $label = date('H:i', $batas);

        if ($checkTs === null) {
            return ['status' => 'ONTIME', 'telat_menit' => 0, 'datang_awal_menit' => 0, 'batas' => $label];
        }

        $thresholdTs = strtotime($tanggal . ' ' . (self::pickTime($jadwal['jam_masuk'] ?? null) ?? self::DEFAULT_JAM_MASUK));

        if ($checkTs <= $batas) {
            return [
                'status'             => 'ONTIME',
                'telat_menit'        => 0,
                'datang_awal_menit'  => $checkTs < $thresholdTs ? (int) floor(($thresholdTs - $checkTs) / 60) : 0,
                'batas'              => $label,
            ];
        }

        return [
            'status'             => 'LATE',
            'telat_menit'        => (int) ceil(($checkTs - $batas) / 60),
            'datang_awal_menit'  => 0,
            'batas'              => $label,
        ];
    }

    /**
     * Apakah batas tinjau PENDING_VERIFICATION untuk satu tanggal sudah lewat?
     * Tanggal hari ini dan kemarin masih di dalam jendela (admin punya waktu),
     * tanggal lebih tua dari PENDING_REVIEW_HARI sudah tidak.
     */
    public static function pendingLewatBatas(string $tanggal): bool
    {
        $tanggal = self::normalisasiTanggal($tanggal);
        if ($tanggal === '') {
            return false;
        }

        return strtotime($tanggal) < strtotime('-' . self::PENDING_REVIEW_HARI . ' day', strtotime(date('Y-m-d')));
    }

    /**
     * Status yang TIDAK boleh dihukum "belum absen pulang" di skor poin maupun
     * pengingat cron. Sumber tunggal: dulu tiap query punya daftarnya sendiri,
     * ada yang lupa ALPHA, ada yang lupa PENDING_VERIFICATION, jadi satu baris
     * yang sedang menunggu verifikasi ikut dikenai penalti.
     *
     * WFH dan DINAS_* ikut dibebaskan (keputusan 2026-11-02): keduanya berarti
     * guru tidak berada di kantor, jadi tidak ada check-out yang bisa ditagih.
     * DINAS_REJECTED tetap dihitung ALPHA di rekap kehadiran (normGuru), tapi
     * tidak dihukum ganda sebagai "tidak pulang" — hukuman itu untuk orang yang
     * menghilang, bukan untuk dinas yang ditolak.
     */
    public static function statusBebasPenaltiPulang(): array
    {
        return [
            'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'ALPHA', 'ALPA', 'PENDING_VERIFICATION',
            'WFH',
            'DINAS', 'DINAS_OUT', 'DINAS_PENDING', 'DINAS_APPROVED', 'DINAS_REJECTED',
        ];
    }

    /** Apakah satu status membebaskan baris dari penalti "belum pulang"? */
    public static function bebasPenaltiPulang($status): bool
    {
        $st = strtoupper(trim((string) ($status ?? '')));
        if ($st === '') {
            return false;
        }

        // Prefix DINAS ikut untuk varian yang belum terdaftar di atas.
        return in_array($st, self::statusBebasPenaltiPulang(), true) || strpos($st, 'DINAS') === 0;
    }

    /**
     * Fragmen SQL: kolom status tertentu tidak menandai pembebasan "belum
     * pulang". NULL = tidak membebaskan (barisnya masih bisa kena penalti).
     */
    public static function sqlBebasPulangStatus(string $kolom): string
    {
        $kolom = preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $kolom) ? $kolom : 'a.status_masuk';
        $list  = "'" . implode("', '", self::statusBebasPenaltiPulang()) . "'";

        return "({$kolom} IS NULL OR ({$kolom} NOT IN ({$list}) AND {$kolom} NOT LIKE 'DINAS%'))";
    }

    /**
     * "Hari ini" menurut kalender aplikasi (WITA), untuk dibandingkan dengan
     * kolom DATE `tgl`.
     *
     * Jangan pakai CURDATE(): clock MySQL server berjalan di zona SYSTEM (UTC+7,
     * satu jam di belakang app yang Asia/Makassar). Pukul 00.00–00.59 WITA
     * CURDATE() masih kemarin, persis di batas ganti hari — hitungan
     * "belum pulang", rekap PDF, dan grafik dashboard salah satu hari.
     */
    public static function hariIniWita(): string
    {
        return date('Y-m-d');
    }

    /**
     * CASE expression untuk SUM(): 5 poin penalti bila hari kerja lewat tanpa
     * jam pulang. $alias adalah alias tabel absensi_harian di query pemanggil.
     *
     * Syarat jam_masuk ikut diperlukan: "lupa pulang" hanya masuk akal bagi
     * orang yang benar-benar masuk. Tanpa syarat ini, baris kosong yang statusnya
     * tidak termasuk daftar bebas (mis. dibuat manual dengan status ONTIME tapi
     * tanpa jam) otomatis dikenai 5 poin. Saat ini 0 baris terdampak, jadi ini
     * pengaman supaya definisinya sama dengan cron lupa-pulang.
     */
    public static function sqlTermBelumPulang(string $alias = 'a'): string
    {
        $alias = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) ? $alias : 'a';

        return "SUM(CASE WHEN ({$alias}.jam_masuk IS NOT NULL AND {$alias}.jam_masuk <> '0000-00-00 00:00:00')"
            . " AND ({$alias}.jam_pulang IS NULL OR {$alias}.jam_pulang = '0000-00-00 00:00:00')"
            . " AND {$alias}.tgl < '" . self::hariIniWita() . "'"
            . ' AND ' . self::sqlBebasPulangStatus($alias . '.status_masuk')
            . ' AND ' . self::sqlBebasPulangStatus($alias . '.status_pulang')
            . ' THEN 5 ELSE 0 END)';
    }

    /**
     * COUNT() hari yang dihitung HADIR. Sama persis dengan definisi yang selama
     * ini dipakai halaman presensi: ONTIME/LATE/WFH/DINAS*, kecuali dinas yang
     * ditolak. Status lain (ALPHA, IJIN, SAKIT, CUTI, PENDING_VERIFICATION)
     * bukan kehadiran — dulu tabel poin menghitung COUNT(*) sehingga hari alpha
     * tampil sebagai "hadir" di layar reward sementara halaman presensi memakai
     * angka yang lain untuk orang yang sama.
     */
    public static function sqlTermHadir(string $alias = 'a'): string
    {
        $alias = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) ? $alias : 'a';

        return "COUNT(CASE WHEN ({$alias}.status_masuk IN ('ONTIME', 'LATE', 'WFH') OR {$alias}.status_masuk LIKE 'DINAS%')"
            . " AND {$alias}.status_masuk != 'DINAS_REJECTED' THEN 1 ELSE NULL END)";
    }

    /**
     * Versi PHP dari sqlTermHadir(), untuk satu nilai status.
     */
    public static function isHadir($status): bool
    {
        if (! is_scalar($status)) {
            return false;
        }

        $st = strtoupper(trim((string) $status));
        if ($st === '') {
            return false;
        }

        return in_array($st, ['ONTIME', 'LATE', 'WFH'], true)
            || (strpos($st, 'DINAS') === 0 && $st !== 'DINAS_REJECTED');
    }

    /**
     * Rumus skor harian (SATU sumber untuk semua layar). $adaKolom dipakai untuk
     * menyesuaikan kolom menit yang belum ada di instalasi tertentu.
     *
     * Angka 10 / 5 / 0.1 / 0.05 di sini sengaja di-hardcode seperti sebelumnya:
     * nilai ini BUKAN point_hadir_ontime dari absensi_setting. Bila nanti skor
     * harus ikut pengaturan poin, itu perubahan kebijakan dan harus diputuskan
     * terpisah karena mengubah peringkat seluruh guru sekaligus.
     *
     * Aturan yang ditegakkan:
     *  - hari dinas (DINAS*) dihitung seperti hadir penuh;
     *  - menit telat/pulang-cepat tidak dipotong untuk hari dinas;
     *  - WFH/dinas tidak pernah dikenai penalti "belum pulang".
     */
    public static function sqlExprSkorHarian(string $alias = 'a', array $adaKolom = []): string
    {
        $alias = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) ? $alias : 'a';

        $ada = static function (string $kolom) use ($adaKolom): bool {
            return !array_key_exists($kolom, $adaKolom) || (bool) $adaKolom[$kolom];
        };

        $dinas      = "{$alias}.status_masuk LIKE 'DINAS%'";
        $dinasPulang = "{$alias}.status_pulang LIKE 'DINAS%'";

        // Dinas yang DITOLAK tidak boleh dapat poin kehadiran: rekap kehadiran
        // (Api\RekapHarian::normGuru) sudah menghitungnya sebagai ALPHA, dan
        // sqlTermHadir() juga mengecualikannya.
        $ditolak = "{$alias}.status_masuk != 'DINAS_REJECTED'";

        $termOntime = "SUM(CASE WHEN ({$alias}.status_masuk = 'ONTIME' OR {$dinas}) AND {$ditolak} THEN 10 ELSE 0 END)";
        $termLate   = "SUM(CASE WHEN {$alias}.status_masuk = 'LATE' THEN 5 ELSE 0 END)";
        $termTelat  = $ada('telat_menit')
            ? "COALESCE(SUM(CASE WHEN {$dinas} THEN 0 ELSE {$alias}.telat_menit END), 0) * 0.1" : '0';
        $termCepat  = $ada('pulang_cepat_menit')
            ? "COALESCE(SUM(CASE WHEN {$dinas} OR {$dinasPulang} THEN 0 ELSE {$alias}.pulang_cepat_menit END), 0) * 0.1" : '0';
        $termAwal   = $ada('datang_awal_menit')
            ? "COALESCE(SUM({$alias}.datang_awal_menit), 0) * 0.05" : '0';
        $termAkhir  = $ada('pulang_akhir_menit')
            ? "COALESCE(SUM({$alias}.pulang_akhir_menit), 0) * 0.05" : '0';
        $termPulang = self::sqlTermBelumPulang($alias);

        return "({$termOntime} - {$termLate} - {$termTelat} - {$termCepat}"
            . " + {$termAwal} + {$termAkhir} - {$termPulang})";
    }

    /**
     * '07:15' / '07:15:00' / '7:15' → 'H:i:s'; null bila tidak dapat dibaca.
     */
    public static function pickTime($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }
        // Baris TIME kadang datang sebagai datetime penuh dari jalur impor.
        if (strpos($value, ' ') !== false) {
            $value = trim(substr($value, strrpos($value, ' ') + 1));
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m) === 1) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
        }

        return null;
    }

    /** Gabung tanggal + jam jadi timestamp; null bila jam tidak valid. */
    public static function waktuKeTs(string $tanggal, string $waktu): ?int
    {
        $waktu = trim($waktu);
        if ($waktu === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{1,2}:\d{2}/', $waktu) === 1) {
            $ts = strtotime(str_replace('T', ' ', $waktu));
            return $ts === false ? null : $ts;
        }
        $jam = self::pickTime($waktu);
        if ($jam === null) {
            return null;
        }
        $ts = strtotime($tanggal . ' ' . $jam);

        return $ts === false ? null : $ts;
    }

    /** Grace global dari absensi_setting (bukan per shift). */
    public static function graceGlobal(): int
    {
        $setting = self::setting();
        $grace = $setting->grace_period ?? null;

        return ($grace === null || $grace === '') ? self::GRACE_DEFAULT : (int) $grace;
    }

    private static function normalisasiTanggal(string $tanggal): string
    {
        $tanggal = trim($tanggal);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tanggal, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return date('Y-m-d');
    }

    private static function normalisasiHari(string $kode): string
    {
        $kode = strtolower(trim($kode));
        if ($kode === '') {
            return '';
        }
        // Diterima: 'sun', 'Sun', 'SUN', 'sunday', 'minggu'.
        $penuh = [
            'sunday' => 'Sun', 'monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed',
            'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat',
            'minggu' => 'Sun', 'senin' => 'Mon', 'selasa' => 'Tue', 'rabu' => 'Wed',
            'kamis' => 'Thu', 'jumat' => 'Fri', 'sabtu' => 'Sat',
        ];
        $kode = $penuh[$kode] ?? $kode;

        return ucfirst(substr($kode, 0, 3));
    }
}
