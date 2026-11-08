<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaPresensiModel extends Model
{
    protected $table      = 'presensi_siswa';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'id_kelas', 'id_siswa', 'id_mapel', 'id_guru',
        'tgl', 'jam', 'jam_masuk', 'jam_keluar',
        'status', 'keterangan',
        'created_at', 'updated_at',
    ];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    // ─────────────────────────────────────────────────────────────────────────
    // Daftar siswa berdasarkan kelas, TP, SMT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hanya siswa AKTIF (buku_induk.status = '1').
     * Siswa LULUS (2) / PINDAH (3) / KELUAR (4) dikecualikan dari
     * daftar absen, monitoring, rekap, dan poin. Baris tanpa buku_induk
     * (data lama) dianggap AKTIF agar tidak hilang diam-diam.
     */
    private function filterSiswaAktif($builder, string $aliasMs = 'ms'): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('buku_induk')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }
        $builder->join(
            'buku_induk bi_aktif',
            "bi_aktif.id_siswa = {$aliasMs}.id_siswa",
            'left'
        )->groupStart()
            ->where('bi_aktif.status', '1')
            ->orWhere('bi_aktif.id_siswa IS NULL', null, false)
            ->groupEnd();
    }

    public function getSiswaByKelas(int $idKelas, int $idTp, int $idSmt): array
    {
        $db = \Config\Database::connect();
        $b = $db->table('kelas_siswa ks')
            ->select('ks.id_siswa, ms.nama, ms.nis, ms.nisn, ms.foto, ms.jenis_kelamin')
            ->join('master_siswa ms', 'ms.id_siswa = ks.id_siswa')
            ->where('ks.id_kelas', $idKelas)
            ->where('ks.id_tp', $idTp)
            ->where('ks.id_smt', $idSmt);
        $this->filterSiswaAktif($b, 'ms');
        return $b->orderBy('ms.nama', 'ASC')
            ->get()->getResult();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mata pelajaran yang diajarkan di kelas ini
    // ─────────────────────────────────────────────────────────────────────────

    public function getMapelByKelas(int $idKelas, int $idTp, int $idSmt): array
    {
        $db = \Config\Database::connect();

        // Ambil dari jadwal mapel kelas jika ada
        $result = $db->table('kelas_jadwal_mapel kjm')
            ->select('mm.id_mapel, mm.nama_mapel')
            ->join('master_mapel mm', 'mm.id_mapel = kjm.id_mapel')
            ->where('kjm.id_kelas', $idKelas)
            ->where('kjm.id_tp', $idTp)
            ->where('kjm.id_smt', $idSmt)
            ->groupBy('mm.id_mapel')
            ->orderBy('mm.nama_mapel', 'ASC')
            ->get()->getResult();

        // Fallback: semua mapel aktif
        if (empty($result)) {
            $result = $db->table('master_mapel')
                ->select('id_mapel, nama_mapel')
                ->where('status', '1')
                ->orderBy('nama_mapel', 'ASC')
                ->get()->getResult();
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Presensi yang sudah ada untuk kelas + mapel + tanggal
    // ─────────────────────────────────────────────────────────────────────────

    public function getPresensiSiswa(
        int $idKelas,
        ?int $idMapel,
        string $tgl
    ): array {
        $db = \Config\Database::connect();

        $builder = $db->table('presensi_siswa ps')
            ->select('ps.id, ps.id_kelas, ps.id_siswa, ps.id_guru,
                      ps.tgl, ps.jam, ps.jam_masuk, ps.jam_keluar,
                      ps.status, ps.keterangan,
                      ms.nama, ms.nis, ms.foto', false)
            ->join('master_siswa ms', 'ms.id_siswa = ps.id_siswa')
            ->where('ps.id_kelas', $idKelas)
            ->where('ps.tgl', $tgl);
        $this->filterSiswaAktif($builder, 'ms');

        // Jika idMapel dikirim (mode lama), filter; mode baru (null) ambil semua per hari
        if ($idMapel !== null) {
            $builder->where('ps.id_mapel', $idMapel);
        }

        return $builder->orderBy('ms.nama', 'ASC')->get()->getResult();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rekap bulanan per kelas
    // ─────────────────────────────────────────────────────────────────────────

    public function getRekapBulanan(int $idKelas, string $bulan, string $tahun): array
    {
        $db       = \Config\Database::connect();
        $bulan    = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $tglAwal  = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        $b = $db->table('presensi_siswa ps')
            ->select("
                ps.id_siswa, ms.nama, ms.nis, ms.nisn,
                SUM(CASE WHEN ps.status = 'HADIR'            THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN ps.status = 'SAKIT'            THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN ps.status = 'IZIN'             THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN ps.status = 'ALPA'             THEN 1 ELSE 0 END) AS alpa,
                SUM(CASE WHEN ps.status = 'BDR'              THEN 1 ELSE 0 END) AS bdr,
                SUM(CASE WHEN ps.status = 'TANPA_KETERANGAN' THEN 1 ELSE 0 END) AS tanpa_keterangan,
                COUNT(ps.id) AS total
            ", false)
            ->join('master_siswa ms', 'ms.id_siswa = ps.id_siswa')
            ->where('ps.id_kelas', $idKelas)
            ->where('ps.tgl >=', $tglAwal)
            ->where('ps.tgl <=', $tglAkhir);
        $this->filterSiswaAktif($b, 'ms');
        return $b->groupBy('ps.id_siswa, ms.nama, ms.nis, ms.nisn')
            ->orderBy('ms.nama', 'ASC')
            ->get()->getResult();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Monitoring harian: siswa + status presensi hari tertentu
    // ─────────────────────────────────────────────────────────────────────────

    public function getMonitoringHarian(int $idKelas, string $tgl, ?int $idTp, ?int $idSmt): array
    {
        $db = \Config\Database::connect();

        $select = '
                ms.id_siswa, ms.nama, ms.nis, ms.foto,
                ps.id      AS presensi_id,
                ps.status,
                ps.jam,
                ps.jam_masuk,
                ps.jam_keluar,
                ps.keterangan,
                ps.id_guru,
                mg.nama_guru
            ';

        // Penanda dipasang cron saat buku pulang ditutup. Kolomnya bisa belum
        // ada di DB yang migration-nya belum jalan — jangan sampai monitoring
        // mati hanya karena kolom tambahan.
        if ($db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) {
            $select .= ',ps.pulang_tidak_tercatat';
        }

        $builder = $db->table('kelas_siswa ks')
            ->select($select, false)
            ->join('master_siswa ms', 'ms.id_siswa = ks.id_siswa')
            ->join('presensi_siswa ps',
                'ps.id_siswa = ks.id_siswa AND ps.id_kelas = ks.id_kelas AND ps.tgl = ' . $db->escape($tgl),
                'left')
            ->join('master_guru mg', 'mg.id_guru = ps.id_guru', 'left')
            ->where('ks.id_kelas', $idKelas);

        if ($idTp)  $builder->where('ks.id_tp', $idTp);
        if ($idSmt) $builder->where('ks.id_smt', $idSmt);
        $this->filterSiswaAktif($builder, 'ms');

        return $builder->orderBy('ms.nama', 'ASC')->get()->getResult();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Statistik ringkas harian per kelas
    // ─────────────────────────────────────────────────────────────────────────

    public function getStatHarian(int $idKelas, string $tgl): object
    {
        $db = \Config\Database::connect();

        $b = $db->table('presensi_siswa ps')
            ->select("
                SUM(CASE WHEN ps.status = 'HADIR'            THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN ps.status = 'SAKIT'            THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN ps.status = 'IZIN'             THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN ps.status = 'ALPA'             THEN 1 ELSE 0 END) AS alpa,
                SUM(CASE WHEN ps.status = 'BDR'              THEN 1 ELSE 0 END) AS bdr,
                SUM(CASE WHEN ps.status = 'TANPA_KETERANGAN' THEN 1 ELSE 0 END) AS tanpa,
                COUNT(ps.id) AS total
            ", false)
            ->join('master_siswa ms', 'ms.id_siswa = ps.id_siswa')
            ->where('ps.id_kelas', $idKelas)
            ->where('ps.tgl', $tgl);
        $this->filterSiswaAktif($b, 'ms');
        $row = $b->get()->getRow();

        return $row ?? (object) [
            'hadir' => 0, 'sakit' => 0, 'izin' => 0,
            'alpa'  => 0, 'bdr'   => 0, 'tanpa' => 0, 'total' => 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Riwayat entri absen oleh guru
    // ─────────────────────────────────────────────────────────────────────────

    public function getRiwayatGuru(int $idGuru, int $limit = 20): array
    {
        $db = \Config\Database::connect();

        return $db->table('presensi_siswa ps')
            ->select('
                ps.tgl, ps.jam, ps.id_kelas, mk.nama_kelas, mm.nama_mapel,
                COUNT(ps.id) AS total_siswa,
                SUM(CASE WHEN ps.status = "HADIR" THEN 1 ELSE 0 END) AS hadir
            ', false)
            ->join('master_kelas mk', 'mk.id_kelas = ps.id_kelas')
            ->join('master_mapel mm', 'mm.id_mapel = ps.id_mapel', 'left')
            ->where('ps.id_guru', $idGuru)
            ->groupBy('ps.tgl, ps.id_kelas, ps.id_mapel, mk.nama_kelas, mm.nama_mapel')
            ->orderBy('ps.tgl', 'DESC')
            ->limit($limit)
            ->get()->getResult();
    }
}
