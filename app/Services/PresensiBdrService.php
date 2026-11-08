<?php

namespace App\Services;

use App\Models\DashboardModel;
use App\Models\PresensiBdrModel;

/**
 * PresensiBdrService — menjawab "kelas ini hari ini BDR (belajar dari rumah)?"
 *
 * Sumber data: tabel `presensi_bdr` (jadwal per lingkup SEMUA/TINGKAT/KELAS
 * dengan rentang tanggal). BDR dipakai ketika siswa tidak KBM di sekolah karena
 * kegiatan akademik lain — contoh paling umum: kelas X & XI diliburkan selama
 * ujian kelas XII. Mereka tidak scan kehadiran, jadi tanpa penanganan khusus
 * cron malam akan menandainya ALPHA.
 *
 * Pemakaian: cron penanda alpha (mengganti ALPA → BDR), halaman input absensi
 * (saran + entri manual), dan rekap bulanan (kolom BDR, dikecualikan dari
 * persen kehadiran).
 *
 * Semua query best-effort: tabel yang belum ada (migration belum dijalankan)
 * dianggap "tidak ada hari BDR", bukan melempar error.
 */
class PresensiBdrService
{
    public const STATUS = 'BDR';

    /** @var array<string, array<int, object>> tanggal => baris jadwal yang berlaku */
    private static array $cacheRows = [];

    /** @var array<string, array<int, int>> tanggal => id_kelas => index baris */
    private static array $cacheKelas = [];

    /**
     * Baris jadwal yang mencangkup satu tanggal.
     *
     * @return array<int, object>
     */
    public function rowsUntukTanggal(string $tanggal): array
    {
        $tanggal = $this->normalizeDate($tanggal);
        if (isset(self::$cacheRows[$tanggal])) {
            return self::$cacheRows[$tanggal];
        }

        $rows = [];
        try {
            $rows = (new PresensiBdrModel())->getRowsUntukTanggal($tanggal);
        } catch (\Throwable $e) {
            log_message('error', '[PresensiBdrService::rowsUntukTanggal] ' . $e->getMessage());
        }

        return self::$cacheRows[$tanggal] = $rows;
    }

    /**
     * Peta id_kelas => jadwal BDR yang berlaku pada satu tanggal.
     *
     * @return array<int, object>
     */
    public function kelasTerdampak(string $tanggal): array
    {
        $tanggal = $this->normalizeDate($tanggal);
        if (isset(self::$cacheKelas[$tanggal])) {
            return self::$cacheKelas[$tanggal];
        }

        $peta = [];
        $rows = $this->rowsUntukTanggal($tanggal);
        if ($rows !== []) {
            $db = \Config\Database::connect();
            foreach ($rows as $idx => $row) {
                $ids = $this->resolveKelasIds($row, $db);
                if ($ids === null) {
                    // Lingkup SEMUA → seluruh kelas aktif.
                    foreach ($this->semuaKelasAktif($db) as $idKelas) {
                        $peta[$idKelas] ??= $row;
                    }
                    continue;
                }
                foreach ($ids as $idKelas) {
                    $peta[$idKelas] ??= $row;
                }
            }
        }

        return self::$cacheKelas[$tanggal] = $peta;
    }

    /**
     * Jadwal BDR untuk satu kelas pada satu tanggal, atau null bila bukan hari BDR.
     */
    public function untukKelas(string $tanggal, int $idKelas): ?object
    {
        if ($idKelas <= 0) {
            return null;
        }
        return $this->kelasTerdampak($tanggal)[$idKelas] ?? null;
    }

    /**
     * Keterangan hari BDR satu kelas (untuk pesan/sapaan UI), null bila bukan.
     */
    public function keterangan(string $tanggal, int $idKelas): ?string
    {
        $row = $this->untukKelas($tanggal, $idKelas);
        if ($row === null) {
            return null;
        }
        $judul = trim((string) ($row->judul ?? ''));
        return $judul !== '' ? $judul : 'Belajar dari rumah';
    }

    /**
     * Tanggal-tanggal BDR satu kelas dalam satu rentang, untuk rekap bulanan.
     * Dipakai menandai sel kosong sebagai BDR (bukan alpha) bila baris presensi
     * belum terbentuk — mis. jadwal BDR baru dibuat setelah cron berjalan.
     *
     * @return array<string, string> 'Y-m-d' => judul
     */
    public function tanggalUntukKelas(string $tglAwal, string $tglAkhir, int $idKelas): array
    {
        $hasil = [];
        if ($idKelas <= 0 || $tglAwal > $tglAkhir) {
            return $hasil;
        }

        try {
            $rows = (new PresensiBdrModel())->getRowsDalamRentang($tglAwal, $tglAkhir);
        } catch (\Throwable $e) {
            log_message('error', '[PresensiBdrService::tanggalUntukKelas] ' . $e->getMessage());
            return $hasil;
        }

        $db = \Config\Database::connect();
        foreach ($rows as $row) {
            $ids = $this->resolveKelasIds($row, $db);
            if ($ids !== null && ! in_array($idKelas, $ids, true)) {
                continue;
            }
            $akhir = (string) ($row->tgl_selesai ?? '') !== '' ? (string) $row->tgl_selesai : (string) $row->tgl_mulai;
            $dari  = max((string) $row->tgl_mulai, $tglAwal);
            $sampai = min($akhir, $tglAkhir);
            for ($ts = strtotime($dari); $ts !== false && date('Y-m-d', $ts) <= $sampai; $ts = strtotime('+1 day', $ts)) {
                $hasil[date('Y-m-d', $ts)] = trim((string) ($row->judul ?? '')) ?: 'Belajar dari rumah';
            }
        }

        return $hasil;
    }

    /**
     * id kelas yang tercakup satu baris jadwal; null bila lingkup SEMUA.
     *
     * @return array<int, int>|null
     */
    private function resolveKelasIds(object $row, $db): ?array
    {
        $lingkup = strtoupper(trim((string) ($row->lingkup ?? 'KELAS')));

        if ($lingkup === 'KELAS') {
            $id = (int) ($row->id_kelas ?? 0);
            return $id > 0 ? [$id] : [];
        }

        if ($lingkup === 'SEMUA') {
            return null;
        }

        $level = (int) ($row->level_id ?? 0);
        if ($level <= 0) {
            return [];
        }

        try {
            if (! $db->tableExists('master_kelas')) {
                return [];
            }
            $q = $db->table('master_kelas')->select('id_kelas')->where('level_id', $level);
            $this->batasiAktif($q, $db);
            $ids = [];
            foreach ($q->get()->getResult() as $k) {
                $ids[] = (int) $k->id_kelas;
            }
            return $ids;
        } catch (\Throwable $e) {
            log_message('error', '[PresensiBdrService::resolveKelasIds] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return array<int, int>
     */
    private function semuaKelasAktif($db): array
    {
        static $ids = null;
        if ($ids !== null) {
            return $ids;
        }
        $ids = [];
        try {
            if (! $db->tableExists('master_kelas')) {
                return $ids;
            }
            $q = $db->table('master_kelas')->select('id_kelas');
            $this->batasiAktif($q, $db);
            foreach ($q->get()->getResult() as $k) {
                $ids[] = (int) $k->id_kelas;
            }
        } catch (\Throwable $e) {
            log_message('error', '[PresensiBdrService::semuaKelasAktif] ' . $e->getMessage());
        }
        return $ids;
    }

    /**
     * Batasi ke tahun pelajaran/semester aktif dan kelas yang belum terhapus.
     */
    private function batasiAktif($q, $db): void
    {
        try {
            $dashboard = new DashboardModel();
            $tp  = $dashboard->getTahunActive();
            $smt = $dashboard->getSemesterActive();
            if ($tp)  $q->where('id_tp', (int) $tp->id_tp);
            if ($smt) $q->where('id_smt', (int) $smt->id_smt);
        } catch (\Throwable $e) {
        }
        try {
            if ($db->fieldExists('deleted_at', 'master_kelas')) {
                $q->where('deleted_at IS NULL', null, false);
            }
        } catch (\Throwable $e) {
        }
    }

    private function normalizeDate(string $tanggal): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', trim($tanggal), $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        return date('Y-m-d');
    }

    /** Kosongkan cache — dipanggil setelah admin mengubah jadwal BDR. */
    public static function resetCache(): void
    {
        self::$cacheRows  = [];
        self::$cacheKelas = [];
    }
}
