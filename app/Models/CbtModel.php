<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtModel - Migrated from CI3 Cbt_model
 * Core CBT functionality: jadwal, bank soal, token
 */
class CbtModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // ==========================================
    // TOKEN FUNCTIONS
    // ==========================================

    /**
     * Get current token
     */
    public function getToken()
    {
        try {
            // Try with 'updated' column first, fallback to 'id_token' if not exists
            $result = $this->db->table('cbt_token')
                ->limit(1)
                ->get()
                ->getRow();

            return $result;
        } catch (\Exception $e) {
            log_message('error', 'getToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update or create token (single token only - no history)
     * - If token exists: UPDATE it
     * - If no token: INSERT new one
     */
    public function saveToken(array $data)
    {
        try {
            // Filter out columns that might not exist in DB
            if (isset($data['status'])) {
                unset($data['status']);
            }
            if (isset($data['updated'])) {
                unset($data['updated']);
            }
            if (isset($data['now'])) {
                unset($data['now']);
            }

            $existing = $this->getToken();

            // If token already exists, always UPDATE (no new inserts)
            if ($existing) {
                // Find the primary key column
                $pk = isset($existing->id_token) ? 'id_token' : 'token';
                $val = isset($existing->id_token) ? $existing->id_token : $existing->token;

                return $this->db->table('cbt_token')->where($pk, $val)->update($data);
            }

            // Only INSERT if table is completely empty
            return $this->db->table('cbt_token')->insert($data);
        } catch (\Exception $e) {
            log_message('error', 'saveToken error: ' . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // JADWAL FUNCTIONS
    // ==========================================

    /**
     * Get jadwal by ID
     */
    public function getJadwal(int $idJadwal): ?object
    {
        return $this->db->table('cbt_jadwal a')
            ->select('a.*, c.nama_mapel, c.kode as kode_mapel, b.bank_kode, b.bank_nama, b.bank_mapel_id as id_mapel, b.bank_kelas')
            ->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left')
            ->join('master_mapel c', 'b.bank_mapel_id = c.id_mapel', 'left')
            ->where('a.id_jadwal', $idJadwal)
            ->get()
            ->getRow();
    }

    /**
     * Get all jadwal by TP and semester
     */
    public function getAllJadwal(int $tp, int $smt): array
    {
        return $this->db->table('cbt_jadwal a')
            ->select('a.*, c.nama_mapel, b.bank_kode, b.bank_nama, b.bank_kelas')
            ->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left')
            ->join('master_mapel c', 'b.bank_mapel_id = c.id_mapel', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('a.tgl_mulai', 'DESC')
            ->get()
            ->getResult();
    }

    /**
     * Get distinct dates from jadwal
     */
    public function getAllJadwalDates(int $tp, int $smt): array
    {
        $result = $this->db->table('cbt_jadwal')
            ->select('tgl_mulai')
            ->distinct()
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->orderBy('tgl_mulai', 'DESC')
            ->get()
            ->getResult();

        return array_column($result, 'tgl_mulai');
    }

    /**
     * Get distinct ruang and sesi based on class IDs
     * Port from CI3 getDistinctRuang
     */
    public function getDistinctRuangSesi(int $tp, int $smt, array $arrKelas): array
    {
        $ruangs = [];
        $sesis = [];

        if (empty($arrKelas)) {
            return ['ruang' => $ruangs, 'sesi' => $sesis];
        }

        $result = $this->db->table('cbt_sesi_siswa a')
            ->distinct()
            ->select('a.ruang_id, a.sesi_id, b.id_ruang, b.kode_ruang, b.nama_ruang, c.id_sesi, c.kode_sesi, c.nama_sesi')
            ->join('cbt_ruang b', 'b.id_ruang = a.ruang_id')
            ->join('cbt_sesi c', 'c.id_sesi = a.sesi_id')
            ->where('a.tp_id', $tp)
            ->where('a.smt_id', $smt)
            ->whereIn('a.kelas_id', $arrKelas)
            ->orderBy('b.nama_ruang', 'ASC')
            ->orderBy('c.nama_sesi', 'ASC')
            ->get()
            ->getResult();

        if ($result) {
            foreach ($result as $row) {
                $ruangs[$row->ruang_id] = $row->nama_ruang;
                $sesis[$row->sesi_id] = $row->nama_sesi;
            }
        }

        return ['ruang' => $ruangs, 'sesi' => $sesis];
    }

    /**
     * Get jadwal by date
     */
    public function getDataJadwalByTgl(string $tanggal): array
    {
        return $this->db->table('cbt_jadwal a')
            ->select('a.*, b.bank_kode, b.bank_nama, b.bank_kelas, c.nama_mapel')
            ->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left')
            ->join('master_mapel c', 'b.bank_mapel_id = c.id_mapel', 'left')
            ->where('a.tgl_mulai <=', $tanggal . ' 23:59:59')
            ->where('a.tgl_selesai >=', $tanggal)
            ->get()
            ->getResult();
    }

    public function getAllJadwalByTanggal($tp, $smt, $jenis, $guru = null)
    {
        $builder = $this->db->table('cbt_jadwal a');
        $builder->select('a.*, b.bank_kode, b.bank_kelas, b.bank_mapel_id, b.bank_level, b.bank_jenis_id, c.nama_mapel, c.kode as kode_mapel, b.bank_mapel_id as id_mapel');
        $builder->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left');
        $builder->join('master_mapel c', 'b.bank_mapel_id = c.id_mapel', 'left');

        $builder->where('a.id_tp', $tp);
        $builder->where('a.id_smt', $smt);
        if ($jenis) {
            $builder->where('a.id_jenis', $jenis);
        }
        if ($guru) {
            $builder->where('b.bank_guru_id', $guru);
        }

        $builder->orderBy('a.tgl_mulai', 'ASC');
        $builder->orderBy('a.jam_ke', 'ASC');

        $result = $builder->get()->getResult();

        foreach ($result as &$row) {
            $row->bank_kelas = $this->maybeUnserialize($row->bank_kelas ?? '');
        }

        return $result;
    }

    /**
     * Get active jadwal for today
     */
    public function getActiveJadwal(int $tp, int $smt): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->table('cbt_jadwal a')
            ->select('a.*, b.bank_kode, b.bank_nama, c.nama_mapel')
            ->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left')
            ->join('master_mapel c', 'b.bank_mapel_id = c.id_mapel', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->where('a.tgl_mulai <=', $now)
            ->where('a.tgl_selesai >=', $now)
            ->get()
            ->getResult();
    }

    // ==========================================
    // RUANG & SESI FUNCTIONS
    // ==========================================

    /**
     * Get all ruang
     */
    public function getAllRuang(): array
    {
        return $this->db->table('cbt_ruang')
            ->orderBy('nama_ruang', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get ruang and sesi mapping for exam
     */
    public function getRuangSesi(int $tp, int $smt): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('a.siswa_id, a.sesi_id, a.ruang_id, a.kelas_id, ' .
            'b.nama_ruang, b.kode_ruang, c.nama_sesi, c.kode_sesi, d.nama_kelas');
        $builder->where('a.tp_id', $tp);
        $builder->where('a.smt_id', $smt);
        $builder->join('cbt_ruang b', 'b.id_ruang=a.ruang_id');
        $builder->join('cbt_sesi c', 'c.id_sesi=a.sesi_id');
        $builder->join('master_kelas d', 'd.id_kelas=a.kelas_id');
        $builder->orderBy('b.nama_ruang', 'ASC');
        $builder->orderBy('c.nama_sesi', 'ASC');
        $result = $builder->get()->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->sesi_id][$row->ruang_id][$row->kelas_id] = $row->nama_kelas;
            }
        }
        return $ret;
    }

    /**
     * Get ruang by ID
     */
    public function getRuangById(int $id): ?object
    {
        return $this->db->table('cbt_ruang')
            ->where('id_ruang', $id)
            ->get()
            ->getRow();
    }

    /**
     * Get distinct ruang/sesi combinations helper
     */
    public function getDistinctRuang(int $tp, int $smt, array $arrKelas): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('a.ruang_id, a.sesi_id, b.kode_ruang, b.nama_ruang, c.kode_sesi, c.nama_sesi, c.waktu_mulai, c.waktu_akhir, c.istirahat');
        $builder->distinct();
        $builder->join('cbt_ruang b', 'b.id_ruang=a.ruang_id');
        $builder->join('cbt_sesi c', 'c.id_sesi=a.sesi_id');
        $builder->where('a.tp_id', $tp);
        $builder->where('a.smt_id', $smt);
        if (!empty($arrKelas)) {
            $builder->whereIn('a.kelas_id', $arrKelas);
        }
        $builder->orderBy('b.nama_ruang', 'ASC');
        $builder->orderBy('c.nama_sesi', 'ASC');
        $result = $builder->get()->getResult();

        $sesis = [];
        if ($result) {
            foreach ($result as $row) {
                $sesis[$row->sesi_id] = $row;
            }
        }
        return $sesis;
    }

    /**
     * Get all sesi
     */
    public function getAllSesi(): array
    {
        return $this->db->table('cbt_sesi')
            ->orderBy('nama_sesi', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get sesi by ID
     */
    public function getSesiById(int $id): ?object
    {
        return $this->db->table('cbt_sesi')
            ->where('id_sesi', $id)
            ->get()
            ->getRow();
    }

    /**
     * Get all kode sesi
     */
    public function getAllKodeSesi(): array
    {
        $result = $this->db->table('cbt_sesi')
            ->select('id_sesi, nama_sesi, kode_sesi, waktu_mulai, waktu_akhir')
            ->get()
            ->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->kode_sesi] = $row;
            }
        }
        return $ret;
    }

    /**
     * Get distinct ruang/sesi combinations
     */
    public function getDistinctSesi(int $tp, int $smt, array $idsKelas): array
    {
        if (empty($idsKelas)) {
            return [];
        }

        $result = $this->db->table('cbt_sesi_siswa a')
            ->select('a.ruang_id, a.sesi_id, b.nama_ruang, b.kode_ruang, c.nama_sesi, c.kode_sesi, ' .
                'c.waktu_mulai, c.waktu_akhir, c.istirahat, ' .
                'GROUP_CONCAT(DISTINCT a.kelas_id ORDER BY a.kelas_id) as kelas_ids, ' .
                'GROUP_CONCAT(DISTINCT d.nama_kelas ORDER BY a.kelas_id) as nama_kelases, ' .
                'COUNT(DISTINCT a.siswa_id) as peserta')
            ->join('cbt_ruang b', 'a.ruang_id = b.id_ruang') // INNER JOIN to match CI3
            ->join('cbt_sesi c', 'a.sesi_id = c.id_sesi')   // INNER JOIN to match CI3
            ->join('master_kelas d', 'a.kelas_id = d.id_kelas', 'left')
            ->where('a.tp_id', $tp)
            ->where('a.smt_id', $smt)
            ->where('a.sesi_id IS NOT NULL')   // Exclude NULL sesi
            ->where('a.ruang_id IS NOT NULL')  // Exclude NULL ruang
            ->whereIn('a.kelas_id', $idsKelas)
            ->groupBy(['a.ruang_id', 'a.sesi_id'])
            ->get()
            ->getResult();

        foreach ($result as $row) {
            $ids = explode(',', $row->kelas_ids ?? '');
            $names = explode(',', $row->nama_kelases ?? '');
            $row->kelas_id = $ids;

            // Map className by ID for JS consumption keys
            $row->nama_kelas = [];
            foreach ($ids as $idx => $id) {
                if (isset($names[$idx])) {
                    $row->nama_kelas[$id] = $names[$idx];
                }
            }
        }

        return $result;
    }

    /**
     * Get students with their assigned ruang and sesi
     */
    public function getRuangSesiSiswa(int $kls, int $tp, int $smt): array
    {
        return $this->db->table('kelas_siswa a')
            ->select('a.id_siswa as siswa_id, a.id_kelas as kelas_id, b.nama, b.nis, b.nisn, c.id_sesi_siswa, c.ruang_id, c.sesi_id')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa')
            ->join('cbt_sesi_siswa c', 'a.id_siswa = c.siswa_id AND c.tp_id = ' . $tp . ' AND c.smt_id = ' . $smt, 'left')
            ->where('a.id_kelas', $kls)
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('b.nama', 'ASC')
            ->get()
            ->getResult();
    }

    // ==========================================
    // JENIS UJIAN FUNCTIONS
    // ==========================================

    /**
     * Get all jenis ujian
     */
    public function getAllJenisUjian(bool $activeOnly = false): array
    {
        $builder = $this->db->table('cbt_jenis');

        if ($activeOnly) {
            $builder->where('aktif', '1');
        }

        return $builder->orderBy('id_jenis', 'ASC')->get()->getResult();
    }

    /**
     * Get active jenis ujian ID
     */
    public function getActiveJenisId(): ?int
    {
        $result = $this->db->table('cbt_jenis')
            ->select('id_jenis')
            ->where('status', '1')
            ->limit(1)
            ->get()
            ->getRow();

        return $result ? (int) $result->id_jenis : null;
    }

    /**
     * Get jenis ujian for Datatable
     */
    public function getJenis(): array
    {
        $result = $this->db->table('cbt_jenis')->get()->getResult();
        $data = [];
        foreach ($result as $i => $row) {
            $data[] = [
                'no' => $i + 1,
                'id_jenis' => $row->id_jenis,
                'nama_jenis' => $row->nama_jenis,
                'kode_jenis' => $row->kode_jenis,
                'status' => $row->status
            ];
        }
        return ['data' => $data];
    }

    /**
     * Update jenis ujian from request
     */
    public function updateJenis(int $id, array $data): bool
    {
        return $this->db->table('cbt_jenis')->where('id_jenis', $id)->update($data);
    }

    // ==========================================
    // BANK SOAL FUNCTIONS
    // ==========================================

    /**
     * Get all bank soal for specific TP and semester
     */
    public function getAllBankSoal(int $tp, int $smt): array
    {
        return $this->db->table('cbt_bank_soal a')
            ->select('a.*, b.nama_mapel')
            ->join('master_mapel b', 'a.bank_mapel_id = b.id_mapel', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->get()
            ->getResult();
    }

    /**
     * Get bank soal by ID with details
     */
    public function getBankSoalById(int $idBank): ?object
    {
        return $this->db->table('cbt_bank_soal a')
            ->select('a.*, b.nama_mapel, b.kode as kode_mapel, c.nama_guru, d.nama_jurusan, d.kode_jurusan,
                (SELECT COUNT(id_jadwal) FROM cbt_jadwal WHERE cbt_jadwal.id_bank = a.id_bank AND cbt_jadwal.status="1") AS digunakan')
            ->join('master_mapel b', 'a.bank_mapel_id = b.id_mapel', 'left')
            ->join('master_guru c', 'a.bank_guru_id = c.id_guru', 'left')
            ->join('master_jurusan d', 'a.bank_jurusan_id = d.id_jurusan', 'left')
            ->where('a.id_bank', $idBank)
            ->get()
            ->getRow();
    }

    /**
     * Get all bank soal with details and filtering
     */
    public function getDataBank(?int $guru = null, ?int $mapel = null, ?int $level = null): array
    {
        $builder = $this->db->table('cbt_bank_soal a')
            ->select('a.*, b.nama_mapel, b.kode as kode, c.nama_guru,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank) AS total_soal,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="1") AS total_pg,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="2") AS total_kompleks,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="3") AS total_jodohkan,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="4") AS total_isian,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="5") AS total_esai,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="6") AS total_urutan,
                (SELECT COUNT(id_soal) FROM cbt_soal WHERE cbt_soal.bank_id = a.id_bank AND cbt_soal.jenis="7") AS total_cloze,
                (SELECT COUNT(id_jadwal) FROM cbt_jadwal WHERE cbt_jadwal.id_bank = a.id_bank AND cbt_jadwal.status="1") AS digunakan')
            ->join('master_mapel b', 'a.bank_mapel_id = b.id_mapel', "left")
            ->join('master_guru c', 'a.bank_guru_id = c.id_guru', "left");

        if ($guru !== null)
            $builder->where('a.bank_guru_id', $guru);
        if ($mapel !== null)
            $builder->where('a.bank_mapel_id', $mapel);
        if ($level !== null)
            $builder->where('a.bank_level', $level);

        $result = $builder->orderBy('a.bank_level', 'ASC')->get()->getResult();

        $data = [];
        foreach ($result as $row) {
            $data[$row->id_tp][$row->id_smt][] = $row;
        }
        return $data;
    }

    /**
     * Get all soal by bank ID
     */
    public function getAllSoalByBank(int $idBank, ?int $jenis = null): array
    {
        $builder = $this->db->table('cbt_soal')
            ->where('bank_id', $idBank);

        if ($jenis !== null)
            $builder->where('jenis', $jenis);

        return $builder->orderBy('nomor_soal', 'ASC')->get()->getResult();
    }

    /**
     * Count soal per type in bank
     */
    public function getJumlahJenisSoal(int $idBank): array
    {
        return $this->db->table('cbt_soal')
            ->select('jenis, COUNT(*) as jumlah')
            ->where('id_bank', $idBank)
            ->groupBy('jenis')
            ->get()
            ->getResult();
    }

    // ==========================================
    // PENGAWAS FUNCTIONS
    // ==========================================

    /**
     * Get all pengawas
     */
    public function getAllPengawas(int $tp, int $smt, ?int $ruang = null, ?int $sesi = null): array
    {
        $builder = $this->db->table('cbt_pengawas');
        $builder->where('id_tp', $tp);
        $builder->where('id_smt', $smt);
        if ($ruang != null)
            $builder->where('id_ruang', $ruang);
        if ($sesi != null)
            $builder->where('id_sesi', $sesi);
        //$builder->orderBy('jam_ke', 'ASC');
        $result = $builder->get()->getResult();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                // Determine structure: [id_jadwal][id_ruang][id_sesi] = Object
                // If multiple supervisors map to same slot, logic needs to handle it (currently simplified)
                $ret[$row->id_jadwal][$row->id_ruang][$row->id_sesi] = $row;
            }
        }
        return $ret;
    }

    /**
     * Get pengawas by jadwal
     */
    public function getPengawasByJadwal($tp, $smt, $id_jadwal, $sesi = null, $ruang = null): array
    {
        $builder = $this->db->table('cbt_pengawas');
        $builder->select('id_pengawas, id_guru, id_jadwal, id_ruang, id_sesi, jam_ke');
        $builder->where('id_tp', $tp);
        $builder->where('id_smt', $smt);
        if (is_array($id_jadwal)) {
            $builder->whereIn('id_jadwal', $id_jadwal);
        } else {
            $builder->where('id_jadwal', $id_jadwal);
        }
        if ($sesi != null) {
            $builder->where('id_sesi', $sesi);
        }
        if ($ruang != null) {
            $builder->where('id_ruang', $ruang);
        }
        return $builder->get()->getResult();
    }

    // ==========================================
    // CBT LOG FUNCTIONS
    // ==========================================

    /**
     * Save CBT activity log
     */
    public function saveLog(int $idSiswa, int $idJadwal, int $type): bool
    {
        $request = service('request');
        $agent = $request->getUserAgent();

        $currAddress = $request->getIPAddress();
        $currAgent = $agent->getBrowser() . ' ' . $agent->getVersion();
        $currDevice = $agent->getPlatform();

        $data = [
            'id_siswa' => $idSiswa,
            'id_jadwal' => $idJadwal,
            'log_type' => $type,
            'address' => $currAddress,
            'agent' => $currAgent,
            'device' => $currDevice,
            'log_time' => date('Y-m-d H:i:s')
        ];

        return $this->db->table('log_ujian')->insert($data);
    }

    /**
     * Get CBT logs by jadwal
     */
    public function getLogsByJadwal(int $idJadwal): array
    {
        return $this->db->table('cbt_log a')
            ->select('a.*, b.nama')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa', 'left')
            ->where('a.id_jadwal', $idJadwal)
            ->orderBy('a.waktu', 'DESC')
            ->get()
            ->getResult();
    }
    public function getSoalByBank(int $idBank, string $acak = '0', int $seed = 0)
    {
        $builder = $this->db->table('cbt_soal')
            ->where('bank_id', $idBank)
            ->where('tampilkan', '1'); // Only show questions marked to display

        if ($acak == '1') {
            // Use seed for consistent randomization per student
            // RAND(seed) ensures same student sees same order each time
            if ($seed > 0) {
                $builder->orderBy("RAND($seed)");
            } else {
                $builder->orderBy('RAND()');
            }
        } else {
            $builder->orderBy('nomor_soal', 'ASC')->orderBy('id_soal', 'ASC');
        }

        return $builder->get()->getResult();
    }

    public function getJawabanSiswa(int $idSiswa, int $idJadwal): array
    {
        $result = $this->db->table('cbt_jawaban')
            ->where('id_siswa', $idSiswa)
            ->where('id_jadwal', $idJadwal)
            ->get()
            ->getResult();

        $data = [];
        foreach ($result as $row) {
            $data[$row->id_soal] = $row;
        }
        return $data;
    }

    // ==========================================
    // NOMOR PESERTA FUNCTIONS
    // ==========================================

    /**
     * Get students by multiple class IDs
     */
    public function getSiswaByKelasArray(int $tp, int $smt, array $kelas_ids): array
    {
        if (empty($kelas_ids))
            return [];
        return $this->db->table('kelas_siswa a')
            ->select('a.id_siswa, b.nama, b.nis, b.nisn, b.username, c.nama_kelas, c.level_id as level')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa')
            ->join('master_kelas c', 'a.id_kelas = c.id_kelas')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->whereIn('a.id_kelas', $kelas_ids)
            ->orderBy('b.nama', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get all nomor peserta for specific students
     */
    public function getAllNomorPeserta(int $tp, int $smt, array $siswa_ids): array
    {
        if (empty($siswa_ids))
            return [];
        $result = $this->db->table('cbt_nomor_peserta')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->whereIn('id_siswa', $siswa_ids)
            ->get()
            ->getResult();

        $data = [];
        foreach ($result as $row) {
            $data[$row->id_siswa] = $row;
        }
        return $data;
    }

    /**
     * Check if nomor peserta exists by custom ID
     */
    public function cekNomorPeserta(array $ids_nomor): array
    {
        if (empty($ids_nomor))
            return [];
        $result = $this->db->table('cbt_nomor_peserta')
            ->whereIn('id_nomor', $ids_nomor)
            ->get()
            ->getResult();

        $data = [];
        foreach ($result as $row) {
            $data[$row->id_nomor] = $row;
        }
        return $data;
    }

    /**
     * Delete duplicate nomor peserta (cleanup)
     */
    public function hapus_duplikat_nomor_peserta(): int
    {
        $sql = "DELETE t1 FROM cbt_nomor_peserta t1
                INNER JOIN cbt_nomor_peserta t2 
                WHERE t1.id_nomor < t2.id_nomor 
                AND t1.id_siswa = t2.id_siswa 
                AND t1.id_tp = t2.id_tp 
                AND t1.id_smt = t2.id_smt";
        $this->db->query($sql);
        return $this->db->affectedRows();
    }

    /**
     * Get all jurusan for dropdown
     */
    public function getAllJurusan(): array
    {
        $result = $this->db->table('master_jurusan')->get()->getResult();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_jurusan] = $row->kode_jurusan;
        }
        return $ret;
    }

    /**
     * Get classes by level and allowed IDs
     */
    public function getKelasByLevel(int $level, array $arrKelas): array
    {
        if (empty($arrKelas))
            return [];
        return $this->db->table('master_kelas')
            ->select('id_kelas, kode_kelas')
            ->where('level_id', $level)
            ->whereIn('id_kelas', $arrKelas)
            ->get()
            ->getResult();
    }

    /**
     * Create dummy bank soal object
     */
    public function dummy(string $jenjang): array
    {
        return [
            'id_bank' => '',
            'bank_jenis_id' => '',
            'bank_kode' => '',
            'bank_mapel_id' => '',
            'bank_level' => '',
            'bank_kelas' => [],
            'bank_guru_id' => '',
            'jml_soal' => '0',
            'bobot_pg' => '0',
            'tampil_pg' => '0',
            'opsi' => $jenjang == '1' ? '3' : ($jenjang == '2' ? '4' : ($jenjang == '3' ? '5' : '')),
            'jml_kompleks' => '0',
            'tampil_kompleks' => '0',
            'bobot_kompleks' => '0',
            'jml_jodohkan' => '0',
            'tampil_jodohkan' => '0',
            'bobot_jodohkan' => '0',
            'jml_isian' => '0',
            'tampil_isian' => '0',
            'bobot_isian' => '0',
            'jml_esai' => '0',
            'bobot_esai' => '0',
            'tampil_esai' => '0',
            'tampil_urutan' => '0',
            'bobot_urutan' => '0',
            'tampil_cloze' => '0',
            'bobot_cloze' => '0',
            'kkm' => '',
            'soal_agama' => '-',
            'status' => '1'
        ];
    }

    /**
     * Save/Update bank soal
     */
    public function saveBankSoal(array $data, ?int $id = null)
    {
        if ($id) {
            return $this->db->table('cbt_bank_soal')->where('id_bank', $id)->update($data);
        }
        $this->db->table('cbt_bank_soal')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Get distinct jenis from jadwal
     */
    public function getDistinctJenisJadwal($tp, $smt): array
    {
        return $this->db->table('cbt_jadwal')
            ->select('id_jenis')
            ->distinct()
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()
            ->getResult();
    }

    /**
     * Get all jenis ujian by array of IDs
     */
    public function getAllJenisUjianByArrJenis(array $arrJenis): array
    {
        if (empty($arrJenis))
            return [];
        $result = $this->db->table('cbt_jenis')
            ->whereIn('id_jenis', $arrJenis)
            ->get()
            ->getResult();

        $ret = ['' => 'Jenis Penilaian :'];
        foreach ($result as $row) {
            $ret[$row->id_jenis] = $row->kode_jenis;
        }
        return $ret;
    }

    /**
     * Get data jadwal by mapel
     */
    public function getDataJadwalByMapel($tp, $smt, $mapel = null): array
    {
        $builder = $this->db->table('cbt_bank_soal a');
        $builder->select('b.id_jadwal, b.jam_ke, b.tgl_mulai, b.tgl_selesai,'
            . ' a.bank_kode, a.bank_level, a.bank_kelas,'
            . ' a.id_bank, a.bank_guru_id, a.soal_agama, c.kode_jenis, c.nama_jenis,'
            . ' d.id_mapel, d.kode, d.nama_mapel, f.id_guru, f.nama_guru');
        if ($mapel != null)
            $builder->where('a.bank_mapel_id', $mapel);
        $builder->where('a.id_tp', $tp);
        $builder->where('a.id_smt', $smt);
        $builder->join('cbt_jadwal b', 'a.id_bank=b.id_bank', "left");
        $builder->join('cbt_jenis c', 'c.id_jenis=b.id_jenis', "left");
        $builder->join('master_mapel d', 'd.id_mapel=a.bank_mapel_id', "left");
        $builder->join('master_guru f', 'f.id_guru=a.bank_guru_id', "left");
        return $builder->get()->getResult();
    }

    /**
     * Get jadwal by ID (comprehensive)
     */
    public function getJadwalById($id_jadwal, $sesi = null): ?object
    {
        $builder = $this->db->table('cbt_jadwal a');
        $builder->select('a.*, b.opsi, b.bank_kode, b.bank_level, b.bank_kelas,'
            . ' b.tampil_pg, b.tampil_kompleks, b.tampil_jodohkan, b.tampil_isian, b.tampil_esai,'
            . ' b.bobot_pg, b.bobot_kompleks, b.bobot_jodohkan, b.bobot_isian, b.bobot_esai,'
            . ' b.id_bank, b.bank_guru_id, b.soal_agama, c.kode_jenis, c.nama_jenis,'
            . ' d.id_mapel, d.kode, d.nama_mapel, f.id_guru, f.nama_guru');
        $builder->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', "left");
        $builder->join('cbt_jenis c', 'c.id_jenis=a.id_jenis', "left");
        $builder->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', "left");
        if ($sesi != null) {
            $builder->join('cbt_sesi e', 'e.id_sesi=' . $sesi, "left");
        }
        $builder->join('master_guru f', 'f.id_guru=b.bank_guru_id', "left");
        $builder->where('a.id_jadwal', $id_jadwal);

        return $builder->get()->getRow();
    }

    /**
     * Get jadwal by tanggal
     */
    public function getJadwalByTanggal($tgl_mulai = null): array
    {
        $builder = $this->db->table('cbt_jadwal a');
        $builder->select('a.id_jadwal,a.tgl_mulai,a.tgl_selesai,a.jam_ke,b.soal_agama,b.bank_mapel_id,b.bank_kelas');
        $builder->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', "left");
        if ($tgl_mulai != null) {
            if (is_array($tgl_mulai)) {
                $builder->whereIn('tgl_mulai', $tgl_mulai);
            } else {
                $builder->where('tgl_mulai', $tgl_mulai);
            }
        }
        return $builder->get()->getResult();
    }

    /**
     * Get jadwal by level (for student nilai page)
     */
    public function getJadwalByLevel(int $tp, int $smt, int $level): array
    {
        return $this->db->table('cbt_jadwal a')
            ->select('a.*, b.bank_kode, b.bank_kelas, b.tampil_pg, b.tampil_kompleks, b.tampil_jodohkan, b.tampil_isian, b.tampil_esai, c.nama_jenis, d.kode as kode_mapel, d.nama_mapel')
            ->join('cbt_bank_soal b', 'a.id_bank=b.id_bank', 'left')
            ->join('cbt_jenis c', 'a.id_jenis=c.id_jenis', 'left')
            ->join('master_mapel d', 'b.bank_mapel_id=d.id_mapel', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->groupStart()
            ->where('b.bank_level', $level)
            ->orWhere('b.bank_level', 0) // Support All Levels
            ->groupEnd()
            ->orderBy('a.tgl_mulai', 'DESC')
            ->get()->getResult();
    }

    public function getKelas($tp, $smt)
    {
        return $this->db->table('master_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->orderBy('nama_kelas', 'ASC')
            ->get()->getResult();
    }

    /**
     * Get students and their exam details by class
     */
    public function getRuangSiswaByKelas(int $id_tp, int $id_smt, $kelas, $sesi = null): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('a.ruang_id, a.sesi_id, b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.password, b.foto,' .
            ' f.id_kelas, f.nama_kelas, f.kode_kelas,' .
            ' d.nama_ruang, d.kode_ruang,' .
            ' e.kode_sesi, e.nama_sesi, e.istirahat,' .
            ' g.nomor_peserta');

        $builder->where('a.tp_id', $id_tp);
        $builder->where('a.smt_id', $id_smt);
        $builder->join('master_siswa b', 'b.id_siswa=a.siswa_id', 'left');
        $builder->join('cbt_ruang d', 'd.id_ruang=a.ruang_id', 'left');
        $builder->join('cbt_nomor_peserta g', 'g.id_siswa=a.siswa_id AND g.id_tp=' . $id_tp . ' AND g.id_smt=' . $id_smt, 'left');
        $builder->join('cbt_sesi e', 'e.id_sesi=a.sesi_id', 'left');
        $builder->join('kelas_siswa c', 'c.id_siswa=b.id_siswa AND c.id_tp=' . $id_tp . ' AND c.id_smt=' . $id_smt, 'left');
        $builder->join('master_kelas f', 'f.id_kelas=c.id_kelas', 'left');

        if ($kelas != 'all') {
            if (is_array($kelas)) {
                $builder->whereIn('a.kelas_id', $kelas);
            } else {
                $builder->where('a.kelas_id', $kelas);
            }
        }

        if ($sesi != null && $sesi != 'null')
            $builder->where('a.sesi_id', $sesi);
        $builder->orderBy('b.nama');

        return $builder->get()->getResult();
    }

    /**
     * Get students and their exam details by ruang
     */
    public function getSiswaByRuang(int $id_tp, int $id_smt, int $id_ruang, $sesi, $level = null): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('a.ruang_id, a.sesi_id, b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.password, b.foto, b.agama,' .
            ' f.id_kelas, f.nama_kelas, f.kode_kelas,' .
            ' d.nama_ruang, d.kode_ruang,' .
            ' e.kode_sesi, e.nama_sesi,' .
            ' us.active,' .
            ' (SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = b.username) AS reset,' .
            ' g.nomor_peserta');

        $builder->where('a.tp_id', $id_tp);
        $builder->where('a.smt_id', $id_smt);
        $builder->join('master_siswa b', 'b.id_siswa=a.siswa_id', 'left');
        $builder->join('cbt_ruang d', 'd.id_ruang=a.ruang_id', 'left');
        $builder->join('cbt_nomor_peserta g', 'g.id_siswa=a.siswa_id AND g.id_tp=' . $id_tp . ' AND g.id_smt=' . $id_smt, 'left');
        $builder->join('cbt_sesi e', 'e.id_sesi=a.sesi_id', 'left');
        $builder->join('users us', 'us.username=b.username', 'left');
        $builder->join('kelas_siswa c', 'c.id_siswa=b.id_siswa AND c.id_tp=' . $id_tp . ' AND c.id_smt=' . $id_smt);

        if ($level == null) {
            $builder->join('master_kelas f', 'f.id_kelas=c.id_kelas');
        } else {
            $builder->join('master_kelas f', 'f.id_kelas=c.id_kelas AND f.level_id=' . $level);
        }
        $builder->join('buku_induk i', 'i.id_siswa=b.id_siswa AND i.status="1"');

        $builder->where('a.ruang_id', $id_ruang);
        if ($sesi !== null && $sesi !== 'null') {
            $builder->where('a.sesi_id', $sesi);
        }
        $builder->orderBy('b.nama');

        return $builder->get()->getResult();
    }

    /**
     * Get students by class for exam
     */
    public function getSiswaByKelas(int $id_tp, int $id_smt, $id_kelas): array
    {
        $builder = $this->db->table('kelas_siswa a');
        $builder->select('b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.password, b.agama,' .
            ' b.foto, c.kelas_id, c.ruang_id, c.sesi_id, d.kode_ruang, e.kode_sesi, f.nama_kelas, f.kode_kelas, g.nomor_peserta');
        $builder->join('master_siswa b', 'b.id_siswa=a.id_siswa', 'left');
        $builder->join('cbt_sesi_siswa c', 'c.siswa_id=a.id_siswa AND a.id_tp = c.tp_id AND a.id_smt = c.smt_id', 'left');
        $builder->join('cbt_ruang d', 'd.id_ruang=c.ruang_id', 'left');
        $builder->join('cbt_sesi e', 'e.id_sesi=c.sesi_id', 'left');
        $builder->join('master_kelas f', 'f.id_kelas=a.id_kelas', 'left');
        $builder->join('cbt_nomor_peserta g', 'g.id_siswa=a.id_siswa AND g.id_tp=' . $id_tp . ' AND g.id_smt=' . $id_smt, 'left');
        $builder->join('buku_induk i', 'i.id_siswa=a.id_siswa AND i.status="1"');

        $builder->where('a.id_tp', $id_tp);
        $builder->where('a.id_smt', $id_smt);
        $builder->where('a.id_siswa is NOT NULL');
        $builder->where('b.id_siswa is NOT NULL');
        $builder->where('c.siswa_id is NOT NULL');
        $builder->where('g.id_siswa is NOT NULL');

        if (is_array($id_kelas)) {
            $builder->whereIn('a.id_kelas', $id_kelas);
        } else {
            $builder->where('a.id_kelas', $id_kelas);
        }
        $builder->orderBy('b.nama', 'ASC');

        return $builder->get()->getResult();
    }

    /**
     * Get students by ruang for exam
     */
    public function getSiswaByRuangId(int $id_tp, int $id_smt, int $id_ruang): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.password, b.agama,' .
            ' b.foto, a.ruang_id, a.sesi_id, d.kode_ruang, d.nama_ruang, e.kode_sesi, f.nama_kelas, f.kode_kelas, g.nomor_peserta');

        $builder->where('a.tp_id', $id_tp);
        $builder->where('a.smt_id', $id_smt);
        $builder->where('a.ruang_id', $id_ruang);

        $builder->join('master_siswa b', 'b.id_siswa=a.siswa_id', 'left');
        $builder->join('cbt_ruang d', 'd.id_ruang=a.ruang_id', 'left');
        $builder->join('cbt_nomor_peserta g', 'g.id_siswa=a.siswa_id AND g.id_tp=' . $id_tp . ' AND g.id_smt=' . $id_smt, 'left');
        $builder->join('cbt_sesi e', 'e.id_sesi=a.sesi_id', 'left');
        $builder->join('kelas_siswa c', 'c.id_siswa=b.id_siswa AND c.id_tp=' . $id_tp . ' AND c.id_smt=' . $id_smt);
        $builder->join('master_kelas f', 'f.id_kelas=c.id_kelas');
        $builder->join('buku_induk i', 'i.id_siswa=b.id_siswa AND i.status="1"');

        $builder->orderBy('e.kode_sesi');
        $builder->orderBy('g.nomor_peserta');
        $builder->orderBy('b.nama');

        return $builder->get()->getResult();
    }

    /**
     * Get all peserta sorted by ruang
     */
    public function getAllPesertaByRuang(int $id_tp, int $id_smt): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.password, b.foto, f.level_id,' .
            ' f.nama_kelas, f.kode_kelas, f.id_kelas,' .
            ' d.nama_ruang, d.kode_ruang, d.id_ruang,' .
            ' e.kode_sesi, e.nama_sesi, a.sesi_id as id_sesi,' .
            ' g.nomor_peserta');

        $builder->where('a.tp_id', $id_tp);
        $builder->where('a.smt_id', $id_smt);
        $builder->join('master_siswa b', 'b.id_siswa=a.siswa_id', 'left');
        $builder->join('cbt_ruang d', 'd.id_ruang=a.ruang_id', 'left');
        $builder->join('cbt_nomor_peserta g', 'g.id_siswa=a.siswa_id AND g.id_tp=' . $id_tp . ' AND g.id_smt=' . $id_smt, 'left');
        $builder->join('cbt_sesi e', 'e.id_sesi=a.sesi_id', 'left');
        $builder->join('kelas_siswa c', 'c.id_siswa=b.id_siswa AND c.id_tp=' . $id_tp . ' AND c.id_smt=' . $id_smt);
        $builder->join('master_kelas f', 'f.id_kelas=c.id_kelas');
        $builder->join('buku_induk i', 'i.id_siswa=b.id_siswa AND i.status="1"');

        $builder->orderBy('d.kode_ruang');
        $builder->orderBy('e.kode_sesi');
        $builder->orderBy('f.level_id');
        $builder->orderBy('f.kode_kelas');
        $builder->orderBy('b.nama');

        $result = $builder->get()->getResult();
        if (count($result) > 0) {
            usort($result, function ($a, $b) {
                return strnatcmp($a->nomor_peserta ?? '', $b->nomor_peserta ?? '');
            });
        }
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_ruang][$row->kode_sesi][] = $row;
        }
        if (count($ret) > 0) {
            ksort($ret);
            foreach ($ret as $items) {
                ksort($items);
            }
        }
        return $ret;
    }

    /**
     * Get all peserta sorted by kelas
     */
    public function getAllPesertaByKelas(int $id_tp, int $id_smt): array
    {
        $builder = $this->db->table('cbt_sesi_siswa a');
        $builder->select('b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.password, b.foto,' .
            ' f.nama_kelas, f.kode_kelas,' .
            ' d.nama_ruang, d.kode_ruang,' .
            ' e.kode_sesi, e.nama_sesi,' .
            ' g.nomor_peserta');

        $builder->where('a.tp_id', $id_tp);
        $builder->where('a.smt_id', $id_smt);
        $builder->join('master_siswa b', 'b.id_siswa=a.siswa_id', 'left');
        $builder->join('cbt_ruang d', 'd.id_ruang=a.ruang_id', 'left');
        $builder->join('cbt_nomor_peserta g', 'g.id_siswa=a.siswa_id AND g.id_tp=' . $id_tp . ' AND g.id_smt=' . $id_smt, 'left');
        $builder->join('cbt_sesi e', 'e.id_sesi=a.sesi_id', 'left');
        $builder->join('kelas_siswa c', 'c.id_siswa=b.id_siswa AND c.id_tp=' . $id_tp . ' AND c.id_smt=' . $id_smt);
        $builder->join('master_kelas f', 'f.id_kelas=c.id_kelas');
        $builder->join('buku_induk i', 'i.id_siswa=b.id_siswa AND i.status="1"');

        $builder->orderBy('f.level_id');
        $builder->orderBy('f.kode_kelas');
        $builder->orderBy('b.nama');

        $result = $builder->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kelas][] = $row;
        }
        return $ret;
    }

    // ==========================================
    // REPORT DATA FUNCTIONS
    // ==========================================

    public function getSettingKopAbsensi(): ?object
    {
        return $this->db->table('cbt_kop_absensi a')
            ->select('a.*, b.logo_kanan, b.logo_kiri, b.kepsek, b.tanda_tangan')
            ->join('setting b', 'b.id_setting=1', 'left')
            ->get()->getRow();
    }

    public function getSettingKopBeritaAcara(): ?object
    {
        return $this->db->table('cbt_kop_berita a')
            ->select('a.*, d.logo_kanan, d.logo_kiri, d.kepsek, d.nip, d.tanda_tangan, d.sekolah, d.proktor, d.nip_proktor, d.ttdproktor, d.kopsekolah, d.alamat, d.kota')
            ->join('setting d', 'd.id_setting=1', 'left')
            ->get()->getRow();
    }

    public function getSettingKartu(): ?object
    {
        return $this->db->table('cbt_kop_kartu')->get()->getRow();
    }

    public function getBeritaAcara($id_jadwal, $id_ruang, $id_sesi): ?object
    {
        return $this->db->table('cbt_berita_acara')
            ->where('id_jadwal', $id_jadwal)
            ->where('id_ruang', $id_ruang)
            ->where('id_sesi', $id_sesi)
            ->get()->getRow();
    }

    public function getPaktaIntegritas($id_jadwal, $id_ruang, $id_sesi): ?object
    {
        $builder = $this->db->table('cbt_pakta_integritas');
        $builder->where('id_jadwal', $id_jadwal);
        if ($id_ruang != null)
            $builder->where('id_ruang', $id_ruang);
        if ($id_sesi != null)
            $builder->where('id_sesi', $id_sesi);
        return $builder->get()->getRow();
    }

    public function saveBeritaAcara(array $data)
    {
        $allowed = ['id_tp', 'id_smt', 'id_jadwal', 'id_ruang', 'id_sesi', 'header_1', 'header_2', 'header_3', 'header_4', 'nama_ujian', 'mapel', 'waktu_mulai', 'waktu_akhir', 'jml_peserta', 'jml_hadir', 'jml_tidak_hadir', 'no_tidak_hadir', 'catatan', 'pengawas_1', 'nip_1', 'pengawas_2', 'nip_2', 'kepsek', 'nip_kepsek', 'tgl_ba'];
        $insertData = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $insertData[$field] = $data[$field];
            }
        }
        $insertData['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->db->table('cbt_berita_acara')
            ->where('id_jadwal', $data['id_jadwal'])
            ->where('id_ruang', $data['id_ruang'])
            ->where('id_sesi', $data['id_sesi'])
            ->get()->getRow();

        if ($existing) {
            return $this->db->table('cbt_berita_acara')->where('id_ba', $existing->id_ba)->update($insertData);
        } else {
            return $this->db->table('cbt_berita_acara')->insert($insertData);
        }
    }

    public function savePaktaIntegritas(array $data)
    {
        $allowed = ['id_tp', 'id_smt', 'id_jadwal', 'id_ruang', 'id_sesi', 'nama', 'nip', 'jabatan', 'sekolah', 'tgl_pi'];
        $insertData = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $insertData[$field] = $data[$field];
            }
        }
        $insertData['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->db->table('cbt_pakta_integritas')
            ->where('id_jadwal', $data['id_jadwal'])
            ->where('id_ruang', $data['id_ruang'])
            ->where('id_sesi', $data['id_sesi'])
            ->get()->getRow();

        if ($existing) {
            return $this->db->table('cbt_pakta_integritas')->where('id_pi', $existing->id_pi)->update($insertData);
        } else {
            return $this->db->table('cbt_pakta_integritas')->insert($insertData);
        }
    }

    public function getAllBeritaAcara(int $tp, int $smt): array
    {
        return $this->db->table('cbt_berita_acara a')
            ->select('a.*, b.nama_ruang, c.nama_sesi, d.tgl_mulai, e.bank_kode, e.bank_level, f.nama_mapel')
            ->join('cbt_ruang b', 'b.id_ruang=a.id_ruang', 'left')
            ->join('cbt_sesi c', 'c.id_sesi=a.id_sesi', 'left')
            ->join('cbt_jadwal d', 'd.id_jadwal=a.id_jadwal', 'left')
            ->join('cbt_bank_soal e', 'e.id_bank=d.id_bank', 'left')
            ->join('master_mapel f', 'f.id_mapel=e.bank_mapel_id', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('a.updated_at', 'DESC')
            ->get()->getResult();
    }

    public function getAllPaktaIntegritas(int $tp, int $smt): array
    {
        return $this->db->table('cbt_pakta_integritas a')
            ->select('a.*, b.nama_ruang, c.nama_sesi, d.tgl_mulai, e.bank_kode, e.bank_level, f.nama_mapel')
            ->join('cbt_ruang b', 'b.id_ruang=a.id_ruang', 'left')
            ->join('cbt_sesi c', 'c.id_sesi=a.id_sesi', 'left')
            ->join('cbt_jadwal d', 'd.id_jadwal=a.id_jadwal', 'left')
            ->join('cbt_bank_soal e', 'e.id_bank=d.id_bank', 'left')
            ->join('master_mapel f', 'f.id_mapel=e.bank_mapel_id', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('a.updated_at', 'DESC')
            ->get()->getResult();
    }



    public function getKelasUjian($tp, $smt, $kelas)
    {
        $result = $this->db->table('cbt_sesi_siswa b')
            ->select('b.siswa_id, b.kelas_id, b.ruang_id, b.sesi_id')
            ->where('b.tp_id', $tp)
            ->where('b.smt_id', $smt)
            ->where('b.kelas_id', $kelas)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kelas_id][$row->ruang_id][$row->sesi_id][] = $row->siswa_id;
        }
        return $ret;
    }


    public function getAllBankSoalByMapel($tp, $smt, $mapel, $guru = null)
    {
        $builder = $this->db->table('cbt_bank_soal b');
        $builder->select('b.*, m.nama_mapel, m.kode, g.nama_guru, j.nama_jenis, j.kode_jenis');
        $builder->join('master_mapel m', 'm.id_mapel = b.bank_mapel_id');
        $builder->join('master_guru g', 'g.id_guru = b.bank_guru_id');
        // $builder->join('master_level l', 'l.id_level = b.bank_level', 'left'); // Not always used
        $builder->join('cbt_jenis j', 'j.id_jenis = b.bank_jenis_id', 'left');

        $builder->where('b.id_tp', $tp);
        $builder->where('b.id_smt', $smt);
        $builder->where('b.bank_mapel_id', $mapel);
        if ($guru != null) {
            $builder->where('b.bank_guru_id', $guru);
        }
        $builder->where('b.status', '1');

        $result = $builder->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $jadwal = $this->db->table('cbt_jadwal')->select('id_jadwal')->where('id_bank', $row->id_bank)->get()->getRow();
            $row->id_jadwal = $jadwal ? $jadwal->id_jadwal : null;
            $ret[$row->id_bank] = $row;
        }
        return $ret;
    }

    public function saveJadwalUjian($tp, $smt)
    {
        $request = service('request');
        $data = [
            'id_tp' => $tp,
            'id_smt' => $smt,
            'id_bank' => $request->getPost('bank_id'),
            'id_jenis' => $request->getPost('jenis_id'),
            'tgl_mulai' => $request->getPost('tgl_mulai'),
            'tgl_selesai' => $request->getPost('tgl_selesai'),
            'durasi_ujian' => $request->getPost('durasi_ujian'),
            'acak_soal' => $request->getPost('acak_soal') ? '1' : '0',
            'acak_opsi' => $request->getPost('acak_opsi') ? '1' : '0',
            'hasil_tampil' => $request->getPost('hasil_tampil') ? '1' : '0',
            'token' => $request->getPost('token') ? '1' : '0',
            'status' => $request->getPost('status') ? '1' : '0',
            'reset_login' => $request->getPost('reset_login') ? '1' : '0',
            'rekap' => '0',
            'jam_ke' => '0',
            'jarak' => $request->getPost('jarak'),
            'pelanggaran' => $request->getPost('pelanggaran') ? '1' : '0'
        ];

        // $data['bank_kelas'] = serialize($bank_kelas);

        $id_jadwal = $request->getPost('id_jadwal');
        if (empty($id_jadwal)) {
            $this->db->table('cbt_jadwal')->insert($data);
            $insert_id = $this->db->insertID();
            return ['success' => true, 'insert_id' => $insert_id];
        } else {
            $this->db->table('cbt_jadwal')->where('id_jadwal', $id_jadwal)->update($data);
            return ['success' => true, 'insert_id' => $id_jadwal];
        }
    }

    public function getAllDataJadwal($tp, $smt, $guru = null, $mapel = null, $level = null)
    {
        $builder = $this->db->table('cbt_jadwal a');
        $builder->select('a.*, b.bank_kode, b.bank_kelas, b.tampil_pg, b.tampil_kompleks, b.tampil_jodohkan, b.tampil_isian, b.tampil_esai, ' .
            'b.bank_level, b.bank_mapel_id, ' .
            'c.nama_jenis, c.kode_jenis, d.kode as kode_mapel, d.nama_mapel, d.id_mapel, ' .
            'e.nama_guru, e.id_guru');
        $builder->join('cbt_bank_soal b', 'a.id_bank=b.id_bank');
        $builder->join('cbt_jenis c', 'a.id_jenis=c.id_jenis', 'left');
        $builder->join('master_mapel d', 'b.bank_mapel_id=d.id_mapel', 'left');
        $builder->join('master_guru e', 'b.bank_guru_id=e.id_guru', 'left');

        $builder->where('a.id_tp', $tp);
        $builder->where('a.id_smt', $smt);

        if ($guru)
            $builder->where('b.bank_guru_id', $guru);
        if ($mapel)
            $builder->where('b.bank_mapel_id', $mapel);
        if ($level)
            $builder->where('b.bank_level', $level);

        $builder->orderBy('a.tgl_mulai', 'ASC');
        $builder->orderBy('a.id_jadwal', 'ASC');

        $result = $builder->get()->getResult();

        $jadwals = [];
        foreach ($result as $row) {
            $tgl = date('Y-m-d', strtotime($row->tgl_mulai));
            $title = $row->kode_jenis . ' (' . date('d-m-Y', strtotime($row->tgl_mulai)) . ')';
            $lvl = "Level " . $row->bank_level;
            $jadwals[$title][$lvl][] = $row;
        }
        return $jadwals;
    }

    public function replaceKopKartu(array $data)
    {
        return $this->db->table('cbt_kop_kartu')->replace($data);
    }

    public function replaceKopAbsensi(array $data)
    {
        return $this->db->table('cbt_kop_absensi')->replace($data);
    }

    public function replaceKopBerita(array $data)
    {
        return $this->db->table('cbt_kop_berita')->replace($data);
    }

    public function getPengawas($id_pengawas)
    {
        return $this->db->table('cbt_pengawas')->where('id_pengawas', $id_pengawas)->get()->getRow();
    }

    public function getJadwalByArrId($arrId)
    {
        if (empty($arrId))
            return [];
        return $this->db->table('cbt_jadwal')->whereIn('id_jadwal', $arrId)->get()->getResult();
    }


    /**
     * Cek Soal Ada (summary)
     */
    public function cekSoalAda($id_bank, $jenis)
    {
        return $this->db->table('cbt_soal')
            ->select('id_soal, bank_id, jenis, nomor_soal')
            ->where('bank_id', $id_bank)
            ->where('jenis', $jenis)
            ->get()
            ->getResult(); // Returns array of objects
    }

    /**
     * Cek Soal Belum Komplit
     */
    public function cekSoalBelumKomplit($jenis, $opsi_ganda, $id_bank = null)
    {
        $builder = $this->db->table('cbt_soal');
        $builder->select('id_soal, bank_id, jenis, nomor_soal'); // removed mapel_id as it might not be in cbt_soal
        $builder->where('jenis', $jenis);

        if ($id_bank !== null) {
            $builder->where('bank_id', $id_bank);
        }

        $builder->groupStart()
            ->where('soal IS NULL')
            ->orWhere('soal', '')
            ->groupEnd();

        if ($jenis == "1") {
            $builder->groupStart()
                ->where('opsi_a IS NULL')->orWhere('opsi_a', '')
                ->orWhere('opsi_b IS NULL')->orWhere('opsi_b', '')
                ->orWhere('opsi_c IS NULL')->orWhere('opsi_c', '')
                ->groupEnd();

            if ($opsi_ganda == '4') {
                $builder->groupStart()
                    ->where('opsi_d IS NULL')->orWhere('opsi_d', '')
                    ->groupEnd();
            }
            if ($opsi_ganda == '5') {
                $builder->groupStart()
                    ->where('opsi_d IS NULL')->orWhere('opsi_d', '')
                    ->orWhere('opsi_e IS NULL')->orWhere('opsi_e', '')
                    ->groupEnd();
            }
        }

        if ($jenis == "2") {
            $builder->groupStart()
                ->where('opsi_a IS NULL')->orWhere('opsi_a', '')
                ->groupEnd();
        }

        $builder->groupStart()
            ->where('jawaban IS NULL')->orWhere('jawaban', '')
            ->groupEnd();

        return $builder->get()->getResult();
    }

    /**
     * Get Nomor Soal Terbesar
     */
    public function getNomorSoalTerbesar($id_bank, $jenis)
    {
        return $this->db->table('cbt_soal')
            ->select('nomor_soal')
            ->where('bank_id', $id_bank)
            ->where('jenis', $jenis)
            ->orderBy('nomor_soal', 'DESC')
            ->get()
            ->getRow();
    }

    /**
     * Get Soal By Nomor (Single)
     */
    public function getSoalByNomor($bank_id, $nomor, $jenis)
    {
        return $this->db->table('cbt_soal')
            ->where('bank_id', $bank_id)
            ->where('nomor_soal', $nomor)
            ->where('jenis', $jenis)
            ->get()
            ->getRow();
    }



    public function countSiswaTerpakai($idBank)
    {
        return $this->db->table('cbt_siswa')
            ->where('id_bank', $idBank)
            ->countAllResults();
    }

    // ==========================================
    // SCORE PROCESSING FUNCTIONS
    // ==========================================

    public function maybeUnserialize($data)
    {
        if (is_array($data))
            return $data;
        if (is_string($data)) {
            $unserialized = @unserialize($data, ['allowed_classes' => false]);
            if ($unserialized !== false || $data === 'b:0;') {
                return $unserialized;
            }
            $decoded = json_decode($data);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $data;
    }

    public function olahNilai($type_query, $id_tp, $id_smt, $query, $id_jadwal, $koreksi = false)
    {
        // 1. Get Jadwal with Bank Soal
        $jadwal = $this->db->table('cbt_jadwal a')
            ->select('a.*, b.bank_kode, b.bank_kelas, b.tampil_pg, b.bobot_pg, b.soal_agama,
                b.tampil_kompleks, b.bobot_kompleks, 
                b.tampil_jodohkan, b.bobot_jodohkan,
                b.tampil_isian, b.bobot_isian,
                b.tampil_esai, b.bobot_esai,
                b.tampil_urutan, b.bobot_urutan,
                b.tampil_cloze, b.bobot_cloze,
                c.nama_jenis, d.nama_mapel, d.kode as kode_mapel, e.nama_guru')
            ->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left')
            ->join('cbt_jenis c', 'a.id_jenis = c.id_jenis', 'left')
            ->join('master_mapel d', 'b.bank_mapel_id = d.id_mapel', 'left')
            ->join('master_guru e', 'b.bank_guru_id = e.id_guru', 'left')
            ->where('a.id_jadwal', $id_jadwal)
            ->get()->getRow();

        if (!$jadwal)
            return null;

        // 2. Get Soal
        $soalBuilder = $this->db->table('cbt_soal')->where('bank_id', $jadwal->id_bank);
        $soals = $soalBuilder->orderBy('nomor_soal', 'ASC')->get()->getResult();

        foreach ($soals as $s) {
            $s->opsi_a = $this->maybeUnserialize($s->opsi_a ?? '');
            $s->jawaban = $this->maybeUnserialize($s->jawaban ?? '');
        }

        $bank_kelas = $this->maybeUnserialize($jadwal->bank_kelas ?? '[]');
        // Ensure bank_kelas is simple array of IDs for easier handling if it was objects
        $arrBankKelas = [];
        if (is_array($bank_kelas)) {
            foreach ($bank_kelas as $bk) {
                $arrBankKelas[] = is_object($bk) ? ($bk->kelas_id ?? '') : (is_array($bk) ? ($bk['kelas_id'] ?? '') : $bk);
            }
        }

        $jadwal->bank = (object) [
            'soal' => $soals,
            'bank_kelas' => $arrBankKelas,
            'bank_kode' => $jadwal->bank_kode,
            'nama_mapel' => $jadwal->nama_mapel,
            'nama_guru' => $jadwal->nama_guru,
            'soal_agama' => $jadwal->soal_agama,
            'tampil_pg' => $jadwal->tampil_pg ?? 0,
            'tampil_kompleks' => $jadwal->tampil_kompleks ?? 0,
            'tampil_jodohkan' => $jadwal->tampil_jodohkan ?? 0,
            'tampil_isian' => $jadwal->tampil_isian ?? 0,
            'tampil_esai' => $jadwal->tampil_esai ?? 0,
            'tampil_urutan' => $jadwal->tampil_urutan ?? 0,
            'tampil_cloze' => $jadwal->tampil_cloze ?? 0,
            'bobot_pg' => $jadwal->bobot_pg ?? 0,
            'bobot_kompleks' => $jadwal->bobot_kompleks ?? 0,
            'bobot_jodohkan' => $jadwal->bobot_jodohkan ?? 0,
            'bobot_isian' => $jadwal->bobot_isian ?? 0,
            'bobot_esai' => $jadwal->bobot_esai ?? 0,
            'bobot_urutan' => $jadwal->bobot_urutan ?? 0,
            'bobot_cloze' => $jadwal->bobot_cloze ?? 0,
        ];

        // 3. Get Sesi Siswa
        $ssBuilder = $this->db->table('cbt_sesi_siswa a')
            ->select('a.siswa_id, a.kelas_id, a.ruang_id, a.sesi_id, 
                b.nama, b.nis, b.agama, c.nomor_peserta, 
                d.nama_kelas, e.nama_ruang, e.kode_ruang, f.nama_sesi, f.kode_sesi')
            ->join('master_siswa b', 'a.siswa_id = b.id_siswa')
            ->join('cbt_nomor_peserta c', 'a.siswa_id = c.id_siswa AND c.id_tp=' . $id_tp . ' AND c.id_smt=' . $id_smt, 'left')
            ->join('master_kelas d', 'a.kelas_id = d.id_kelas', 'left')
            ->join('cbt_ruang e', 'a.ruang_id = e.id_ruang', 'left')
            ->join('cbt_sesi f', 'a.sesi_id = f.id_sesi', 'left')
            ->where('a.tp_id', $id_tp)
            ->where('a.smt_id', $id_smt);

        if ($type_query == 1) { // Class
            $ssBuilder->where('a.kelas_id', $query);
        } elseif ($type_query == 2) { // Siswa
            $ssBuilder->where('a.siswa_id', $query);
        }

        $sesiSiswa = $ssBuilder->get()->getResult();

        $jadwal->sesisiswa = [];
        $ids_siswa = [];
        foreach ($sesiSiswa as $ss) {
            // Basic object structure expected by views
            $item = new \stdClass();
            $item->siswa_id = $ss->siswa_id;
            $item->kelas_id = $ss->kelas_id;
            $item->ruang_id = $ss->ruang_id;
            $item->sesi_id = $ss->sesi_id;
            $item->siswa = (object) ['id_siswa' => $ss->siswa_id, 'nama' => $ss->nama, 'nis' => $ss->nis, 'agama' => $ss->agama];
            $item->peserta = (object) ['nomor_peserta' => $ss->nomor_peserta];
            $item->kelas = (object) ['nama_kelas' => $ss->nama_kelas];
            $item->ruang = (object) ['nama_ruang' => $ss->nama_ruang, 'kode_ruang' => $ss->kode_ruang ?? '-'];
            $item->sesi = (object) ['nama_sesi' => $ss->nama_sesi, 'kode_sesi' => $ss->kode_sesi ?? '-'];

            // Also create ruang_sesi nested structure for View compatibility
            $item->ruang_sesi = (object) [
                'ruang' => $item->ruang,
                'sesi' => $item->sesi
            ];

            // Agama Check
            if ($jadwal->soal_agama && $jadwal->soal_agama != '-' && $jadwal->soal_agama != '0' && $jadwal->soal_agama != $ss->agama) {
                continue;
            }

            $ids_siswa[] = $ss->siswa_id;
            $jadwal->sesisiswa[] = $item;
        }

        if (empty($ids_siswa))
            return $jadwal;

        // 4. Get CBT Siswa (Answers)
        $cbtSiswa = $this->db->table('cbt_siswa')
            ->where('id_jadwal', $id_jadwal)
            ->whereIn('id_siswa', $ids_siswa)
            ->get()->getResult();

        $mapCbt = [];
        foreach ($cbtSiswa as $c)
            $mapCbt[$c->id_siswa] = $c;

        // 5. Merge Data
        foreach ($jadwal->sesisiswa as $ss) {
            $ss->cbtsiswa = $mapCbt[$ss->siswa_id] ?? null;
            if (!$ss->cbtsiswa)
                $ss->cbtsiswa = (object) [];

            if (isset($ss->cbtsiswa->jawaban))
                $ss->cbtsiswa->jawaban = $this->maybeUnserialize($ss->cbtsiswa->jawaban);
            if (isset($ss->cbtsiswa->nilai))
                $ss->cbtsiswa->nilai = $this->maybeUnserialize($ss->cbtsiswa->nilai);
            if (isset($ss->cbtsiswa->nilai_input))
                $ss->cbtsiswa->nilai_input = $this->maybeUnserialize($ss->cbtsiswa->nilai_input);

            // Build nilai_akhir structure for View compatibility
            $nilaiCbt = $ss->cbtsiswa->nilai ?? (object) [];
            $nilaiInput = $ss->cbtsiswa->nilai_input ?? (object) [];

            // Calculate score from cbt_nilai table if available
            $nilaiRow = $this->db->table('cbt_nilai')
                ->where('id_siswa', $ss->siswa_id)
                ->where('id_jadwal', $id_jadwal)
                ->get()->getRow();

            $skorPgTotal = $nilaiRow->pg_nilai ?? $nilaiCbt->pg ?? $nilaiInput->pg_nilai ?? 0;

            // Get jawaban details for PG from cbt_jawaban
            $jawabanPg = [];
            if (isset($mapCbt[$ss->siswa_id])) {
                $jawabanRows = $this->db->table('cbt_jawaban j')
                    ->select('j.id_soal, j.jawaban as jawaban_siswa, s.jawaban as kunci, s.nomor_soal')
                    ->join('cbt_soal s', 'j.id_soal = s.id_soal')
                    ->where('j.id_siswa', $ss->siswa_id)
                    ->where('j.id_jadwal', $id_jadwal)
                    ->where('s.jenis', '1') // PG only
                    ->orderBy('s.nomor_soal', 'ASC')
                    ->get()->getResult();

                foreach ($jawabanRows as $jr) {
                    $jawabanPg[] = (object) [
                        'nomor' => $jr->nomor_soal,
                        'jawaban_siswa' => $jr->jawaban_siswa,
                        'kunci' => $jr->kunci,
                        'benar' => (strtoupper(trim($jr->jawaban_siswa)) === strtoupper(trim($jr->kunci)))
                    ];
                }
            }

            $skorKompleks = $nilaiCbt->kompleks ?? $nilaiInput->kompleks_nilai ?? 0;
            $skorJodohkan = $nilaiCbt->jodohkan ?? $nilaiInput->jodohkan_nilai ?? 0;
            $skorIsian = $nilaiCbt->isian ?? $nilaiInput->isian_nilai ?? 0;
            $skorEsai = $nilaiCbt->esai ?? $nilaiInput->essai_nilai ?? 0;
            $skorUrutan = $nilaiCbt->urutan ?? $nilaiInput->urutan_nilai ?? 0;
            $skorCloze = $nilaiCbt->cloze ?? $nilaiInput->cloze_nilai ?? 0;

            // Calculate total score (use pg_nilai from cbt_nilai as main score)
            $skorTotal = floatval($skorPgTotal);

            $ss->nilai_akhir = (object) [
                'skor_pg' => (object) [
                    'skor_total' => $skorPgTotal,
                    'detail' => $jawabanPg
                ],
                'skor_kompleks' => (object) ['skor_total' => $skorKompleks],
                'skor_jodohkan' => (object) ['skor_total' => $skorJodohkan],
                'skor_isian' => (object) ['skor_total' => $skorIsian],
                'skor_esai' => (object) ['skor_total' => $skorEsai],
                'skor_urutan' => (object) ['skor_total' => $skorUrutan],
                'skor_cloze' => (object) ['skor_total' => $skorCloze],
                'skor_total' => $skorTotal,
                'dikoreksi' => $nilaiInput->dikoreksi ?? 0
            ];

            // Build durasi structure for View compatibility
            $mulaiRaw = $ss->cbtsiswa->mulai ?? null;
            $selesaiRaw = $ss->cbtsiswa->selesai ?? null;
            $mulaiTime = $mulaiRaw ? date('H:i', strtotime($mulaiRaw)) : null;

            $ss->durasi = (object) [
                'mulai' => $mulaiRaw,
                'mulai_ujian' => $mulaiTime, // Time only for View compatibility
                'selesai' => $selesaiRaw,
                'status_ujian' => $this->getStatusUjianLabel($ss->cbtsiswa->status ?? '0', $mulaiRaw, $selesaiRaw)
            ];

            // Also expose no_peserta for easy access
            $ss->no_peserta = $ss->peserta->nomor_peserta ?? '-';
        }

        if ($koreksi) {
            // Filter Soal based on tampilkan
            $jadwal->soal_pg = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '1' && $s->tampilkan == '1';
            }));
            $jadwal->soal_kompleks = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '2' && $s->tampilkan == '1';
            }));
            $jadwal->soal_jodohkan = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '3' && $s->tampilkan == '1';
            }));
            $jadwal->soal_isian = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '4' && $s->tampilkan == '1';
            }));
            $jadwal->soal_esai = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '5' && $s->tampilkan == '1';
            }));
            $jadwal->soal_urutan = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '6' && $s->tampilkan == '1';
            }));
            $jadwal->soal_cloze = array_values(array_filter($soals, function ($s) {
                return $s->jenis == '7' && $s->tampilkan == '1';
            }));
        }

        return $jadwal;
    }

    public function getBeritaAcaraById($id)
    {
        return $this->db->table('cbt_berita_acara a')
            ->select('a.*, d.nama_mapel, d.kode as kode_mapel, b.tgl_mulai')
            ->join('cbt_jadwal b', 'a.id_jadwal = b.id_jadwal', 'left')
            ->join('cbt_bank_soal c', 'b.id_bank = c.id_bank', 'left')
            ->join('master_mapel d', 'c.id_mapel = d.id_mapel', 'left')
            ->where('a.id_berita_acara', $id)
            ->get()->getRow();
    }

    public function getPaktaIntegritasById($id)
    {
        return $this->db->table('cbt_pakta_integritas')
            ->where('id_pakta', $id)
            ->get()->getRow();
    }

    public function getAllBeritaAcaraDetailed()
    {
        return $this->db->table('cbt_berita_acara a')
            ->select('a.*, d.nama_mapel, d.kode as kode_mapel, b.tgl_mulai')
            ->join('cbt_jadwal b', 'a.id_jadwal = b.id_jadwal', 'left')
            ->join('cbt_bank_soal c', 'b.id_bank = c.id_bank', 'left')
            ->join('master_mapel d', 'c.id_mapel = d.id_mapel', 'left')
            ->orderBy('a.created_at', 'DESC')
            ->get()->getResult();
    }

    public function saveBeritaAcaraSimple(array $data)
    {
        return $this->db->table('cbt_berita_acara')->insert($data);
    }

    public function savePaktaSimple(array $data)
    {
        return $this->db->table('cbt_pakta_integritas')->insert($data);
    }

    public function getJenisUjianById($id)
    {
        return $this->db->table('cbt_jenis')->where('id_jenis', $id)->get()->getRow();
    }

    /**
     * Hitung Nilai Otomatis dengan Weighted Scoring
     * 
     * Type 1: PG (Pilihan Ganda) - Full credit only
     * Type 2: PG Kompleks - Partial credit based on correct answers
     * Type 3: Menjodohkan - Partial credit per correct pair
     * Type 4: Isian Singkat - Full credit with multiple acceptable answers
     * Type 5: Esai - Manual Grading (Skipped)
     * Type 6: Mengurutkan - Full credit only (order matters)
     * Type 7: Isian Rumpang - Partial credit per correct blank
     * 
     * UPDATED: Now uses weighted scoring based on bobot from bank_soal
     */
    public function hitungNilai($id_siswa, $id_jadwal)
    {
        $db = $this->db;

        // 1. Get Jadwal with Bank Soal (including bobot weights)
        $jadwalData = $db->table('cbt_jadwal j')
            ->select('j.id_jadwal, j.id_bank, j.katrol_max, j.katrol_min,
                b.bobot_pg, b.bobot_kompleks, b.bobot_jodohkan, b.bobot_isian, 
                b.bobot_esai, b.bobot_urutan, b.bobot_cloze,
                b.tampil_pg, b.tampil_kompleks, b.tampil_jodohkan, b.tampil_isian,
                b.tampil_esai, b.tampil_urutan, b.tampil_cloze')
            ->join('cbt_bank_soal b', 'j.id_bank = b.id_bank', 'left')
            ->where('j.id_jadwal', $id_jadwal)
            ->get()->getRow();

        if (!$jadwalData)
            return false;

        // 2. Get All Questions (Base of Truth)
        $soalList = $db->table('cbt_soal')
            ->select('id_soal, jenis, jawaban as kunci, opsi_a')
            ->where('bank_id', $jadwalData->id_bank)
            ->where('tampilkan', '1') // Only count displayed questions
            ->get()->getResult();

        if (empty($soalList))
            return false;

        // 3. Get Student Answers
        $jawabanList = $db->table('cbt_jawaban')
            ->select('id_soal, jawaban as jawab')
            ->where('id_siswa', $id_siswa)
            ->where('id_jadwal', $id_jadwal)
            ->get()->getResult();

        $mapJawaban = [];
        foreach ($jawabanList as $j) {
            $mapJawaban[$j->id_soal] = $j->jawab;
        }

        // Score accumulators
        $stats = [
            '1' => ['skor' => 0, 'total' => 0], // PG
            '2' => ['skor' => 0, 'total' => 0], // PG Kompleks
            '3' => ['skor' => 0, 'total' => 0], // Menjodohkan
            '4' => ['skor' => 0, 'total' => 0], // Isian Singkat
            '5' => ['skor' => 0, 'total' => 0], // Esai (manual)
            '6' => ['skor' => 0, 'total' => 0], // Mengurutkan
            '7' => ['skor' => 0, 'total' => 0], // Isian Rumpang
        ];

        foreach ($soalList as $s) {
            $jenis = $s->jenis ?? '1';
            $kunci = $s->kunci ?? '';
            $jawab = $mapJawaban[$s->id_soal] ?? '';

            if (!isset($stats[$jenis]))
                $stats[$jenis] = ['skor' => 0, 'total' => 0];
            $stats[$jenis]['total']++;

            if ($jawab === '')
                continue;

            switch ($jenis) {
                case '1': // PG
                    if ($this->normalizeAnswer($jawab) === $this->normalizeAnswer($kunci)) {
                        $stats['1']['skor'] += 1;
                    }
                    break;

                case '2': // PG Kompleks
                    $kunciArr = $this->parseArrayAnswer($kunci);
                    $jawabArr = $this->parseArrayAnswer($jawab);
                    $kunciArr = array_map([$this, 'normalizeAnswer'], $kunciArr);
                    $jawabArr = array_map([$this, 'normalizeAnswer'], $jawabArr);

                    $correctCount = count(array_intersect($jawabArr, $kunciArr));
                    $wrongCount = count(array_diff($jawabArr, $kunciArr));
                    $totalExpected = count($kunciArr);

                    if ($totalExpected > 0) {
                        $partialScore = max(0, ($correctCount - $wrongCount) / $totalExpected);
                        $stats['2']['skor'] += $partialScore;
                    }
                    break;

                case '3': // Menjodohkan
                    $kunciArr = $this->parseArrayAnswer($kunci);
                    $jawabArr = $this->parseArrayAnswer($jawab);
                    $matchScore = $this->calculateMatchingScore($kunciArr, $jawabArr);
                    $stats['3']['skor'] += $matchScore;
                    break;

                case '4': // Isian Singkat
                    $acceptableAnswers = $this->parseAcceptableAnswers($kunci);
                    $userAnswer = $this->normalizeAnswer($jawab);
                    $matched = false;
                    foreach ($acceptableAnswers as $acceptable) {
                        if ($this->normalizeAnswer($acceptable) === $userAnswer) {
                            $matched = true;
                            break;
                        }
                    }
                    if ($matched) {
                        $stats['4']['skor'] += 1;
                    }
                    break;

                case '5': // Esai - Skip auto scoring
                    break;

                case '6': // Mengurutkan
                    $kunciArr = $this->parseArrayAnswer($kunci);
                    $jawabArr = $this->parseArrayAnswer($jawab);
                    $kunciArr = array_map([$this, 'normalizeAnswer'], $kunciArr);
                    $jawabArr = array_map([$this, 'normalizeAnswer'], $jawabArr);
                    if ($kunciArr === $jawabArr) {
                        $stats['6']['skor'] += 1;
                    }
                    break;

                case '7': // Cloze
                    $kunciArr = $this->parseArrayAnswer($kunci);
                    $jawabArr = $this->parseArrayAnswer($jawab);
                    $totalBlanks = count($kunciArr);
                    $correctBlanks = 0;

                    for ($i = 0; $i < $totalBlanks; $i++) {
                        $expected = $this->normalizeAnswer($kunciArr[$i] ?? '');
                        $actual = $this->normalizeAnswer($jawabArr[$i] ?? '');
                        if ($expected === $actual && $expected !== '') {
                            $correctBlanks++;
                        }
                    }

                    if ($totalBlanks > 0) {
                        $stats['7']['skor'] += ($correctBlanks / $totalBlanks);
                    }
                    break;
            }
        }

        // 4. Get Bobot (weights) from bank soal
        $bobotMap = [
            '1' => floatval($jadwalData->bobot_pg ?? 0),
            '2' => floatval($jadwalData->bobot_kompleks ?? 0),
            '3' => floatval($jadwalData->bobot_jodohkan ?? 0),
            '4' => floatval($jadwalData->bobot_isian ?? 0),
            '5' => floatval($jadwalData->bobot_esai ?? 0),
            '6' => floatval($jadwalData->bobot_urutan ?? 0),
            '7' => floatval($jadwalData->bobot_cloze ?? 0),
        ];

        // 5. Calculate Weighted Score
        $weightedScore = 0;
        $totalWeight = 0;
        $detailScores = [];

        foreach ($stats as $type => $data) {
            $bobot = $bobotMap[$type] ?? 0;

            if ($data['total'] > 0 && $type !== '5') { // Skip esai for auto-grading
                // Calculate percentage for this type
                $typePercentage = ($data['skor'] / $data['total']) * 100;

                // Store detail score per type
                $detailScores[$type] = [
                    'skor_mentah' => $data['skor'],
                    'total_soal' => $data['total'],
                    'persentase' => round($typePercentage, 2),
                    'bobot' => $bobot,
                    'nilai_berbobot' => $bobot > 0 ? round(($typePercentage / 100) * $bobot, 2) : 0
                ];

                if ($bobot > 0) {
                    // Apply weight: (percentage * weight)
                    $weightedScore += ($typePercentage / 100) * $bobot;
                    $totalWeight += $bobot;
                }
            }
        }

        // 6. Calculate Final Score
        // If weighted scoring is configured, use it; otherwise fallback to simple percentage
        if ($totalWeight > 0) {
            // Weighted score as percentage of total weight
            $mainScore = ($weightedScore / $totalWeight) * 100;
        } else {
            // Fallback: Simple percentage of correct answers
            $totalScore = 0;
            $totalQuestions = 0;
            foreach ($stats as $type => $data) {
                if ($data['total'] > 0 && $type !== '5') {
                    $totalScore += $data['skor'];
                    $totalQuestions += $data['total'];
                }
            }
            $mainScore = ($totalQuestions > 0) ? ($totalScore / $totalQuestions) * 100 : 0;
        }

        // 7. Apply Katrol if configured
        $katrolMax = floatval($jadwalData->katrol_max ?? 0);
        $katrolMin = floatval($jadwalData->katrol_min ?? 0);
        $nilaiAsli = $mainScore;

        if ($katrolMax > 0 && $katrolMin > 0 && $katrolMax > $katrolMin) {
            // Linear scaling: scale mainScore to range [katrolMin, katrolMax]
            $mainScore = $katrolMin + (($mainScore / 100) * ($katrolMax - $katrolMin));
        }

        // Count fully correct PG for backward compatibility
        $pgBenar = (int) round($stats['1']['skor']);

        // 8. Prepare data to save
        $dtNilai = [
            'id_siswa' => $id_siswa,
            'id_jadwal' => $id_jadwal,
            'pg_benar' => $pgBenar,
            'pg_nilai' => number_format($mainScore, 2, '.', ''),
            'nilai_asli' => number_format($nilaiAsli, 2, '.', ''),
            'pg_skor' => number_format($detailScores['1']['nilai_berbobot'] ?? 0, 2, '.', ''),
            'kompleks_skor' => number_format($detailScores['2']['nilai_berbobot'] ?? 0, 2, '.', ''),
            'jodohkan_skor' => number_format($detailScores['3']['nilai_berbobot'] ?? 0, 2, '.', ''),
            'isian_skor' => number_format($detailScores['4']['nilai_berbobot'] ?? 0, 2, '.', ''),
            'urutan_skor' => number_format($detailScores['6']['nilai_berbobot'] ?? 0, 2, '.', ''),
            'cloze_skor' => number_format($detailScores['7']['nilai_berbobot'] ?? 0, 2, '.', ''),
            'detail_json' => json_encode($detailScores),
        ];

        // 9. Save to database with proper error handling
        try {
            $existN = $db->table('cbt_nilai')
                ->where('id_siswa', $id_siswa)
                ->where('id_jadwal', $id_jadwal)
                ->countAllResults();

            if ($existN > 0) {
                $db->table('cbt_nilai')
                    ->where('id_siswa', $id_siswa)
                    ->where('id_jadwal', $id_jadwal)
                    ->update($dtNilai);
            } else {
                // Use raw query with ON DUPLICATE KEY UPDATE as fallback
                $sql = "INSERT INTO cbt_nilai (id_siswa, id_jadwal, pg_benar, pg_nilai, nilai_asli, pg_skor, kompleks_skor, jodohkan_skor, isian_skor, urutan_skor, cloze_skor, detail_json) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            pg_benar = VALUES(pg_benar),
                            pg_nilai = VALUES(pg_nilai),
                            nilai_asli = VALUES(nilai_asli),
                            pg_skor = VALUES(pg_skor),
                            kompleks_skor = VALUES(kompleks_skor),
                            jodohkan_skor = VALUES(jodohkan_skor),
                            isian_skor = VALUES(isian_skor),
                            urutan_skor = VALUES(urutan_skor),
                            cloze_skor = VALUES(cloze_skor),
                            detail_json = VALUES(detail_json)";

                $db->query($sql, [
                    $dtNilai['id_siswa'],
                    $dtNilai['id_jadwal'],
                    $dtNilai['pg_benar'],
                    $dtNilai['pg_nilai'],
                    $dtNilai['nilai_asli'],
                    $dtNilai['pg_skor'],
                    $dtNilai['kompleks_skor'],
                    $dtNilai['jodohkan_skor'],
                    $dtNilai['isian_skor'],
                    $dtNilai['urutan_skor'],
                    $dtNilai['cloze_skor'],
                    $dtNilai['detail_json']
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', "hitungNilai save error: siswa={$id_siswa}, jadwal={$id_jadwal}, " . $e->getMessage());
            // Try UPDATE as last resort
            $db->table('cbt_nilai')
                ->where('id_siswa', $id_siswa)
                ->where('id_jadwal', $id_jadwal)
                ->update($dtNilai);
        }

        // 10. Log scoring for debugging
        log_message('info', "hitungNilai completed: siswa={$id_siswa}, jadwal={$id_jadwal}, " .
            "nilai_asli={$nilaiAsli}, nilai_akhir={$mainScore}, weighted={$totalWeight}");

        return true;
    }

    /**
     * Batch calculate scores for multiple students
     * More efficient than calling hitungNilai individually
     * 
     * @param array $studentIds Array of student IDs
     * @param int $idJadwal Schedule ID
     * @return array Results with success/fail counts
     */
    public function batchHitungNilai(array $studentIds, int $idJadwal): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($studentIds as $idSiswa) {
            try {
                if ($this->hitungNilai($idSiswa, $idJadwal)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed for siswa {$idSiswa}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error for siswa {$idSiswa} — periksa log aplikasi";
                log_message('error', "batchHitungNilai error: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get scoring statistics for a jadwal
     * Useful for reports and analysis
     */
    public function getScoringStats(int $idJadwal): array
    {
        $db = $this->db;

        $stats = $db->table('cbt_nilai')
            ->select('
                COUNT(*) as total_siswa,
                AVG(pg_nilai) as rata_rata,
                MAX(pg_nilai) as nilai_tertinggi,
                MIN(pg_nilai) as nilai_terendah,
                AVG(nilai_asli) as rata_rata_asli,
                SUM(CASE WHEN pg_nilai >= 75 THEN 1 ELSE 0 END) as lulus,
                SUM(CASE WHEN pg_nilai < 75 THEN 1 ELSE 0 END) as tidak_lulus
            ')
            ->where('id_jadwal', $idJadwal)
            ->get()->getRow();

        // Get distribution
        $distribution = $db->table('cbt_nilai')
            ->select("
                CASE 
                    WHEN pg_nilai >= 90 THEN 'A (90-100)'
                    WHEN pg_nilai >= 80 THEN 'B (80-89)'
                    WHEN pg_nilai >= 70 THEN 'C (70-79)'
                    WHEN pg_nilai >= 60 THEN 'D (60-69)'
                    ELSE 'E (<60)'
                END as grade,
                COUNT(*) as jumlah
            ")
            ->where('id_jadwal', $idJadwal)
            ->groupBy('grade')
            ->get()->getResult();

        return [
            'summary' => $stats,
            'distribution' => $distribution
        ];
    }

    /**
     * Normalize answer string for comparison
     * - Lowercase
     * - Trim whitespace
     * - Collapse multiple spaces
     * - Remove common punctuation variations
     * - Handle Indonesian special characters
     * - Remove quotes
     */
    private function normalizeAnswer($answer): string
    {
        if (!is_string($answer)) {
            $answer = (string) $answer;
        }

        // Remove HTML tags if any
        $answer = strip_tags($answer);

        // Decode HTML entities (e.g., &nbsp;, &amp;)
        $answer = html_entity_decode($answer, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Lowercase (use mb_strtolower for UTF-8 support)
        $answer = mb_strtolower($answer, 'UTF-8');

        // Normalize accented characters to ASCII equivalents
        $accents = ['á', 'é', 'í', 'ó', 'ú', 'à', 'è', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û', 'ä', 'ë', 'ï', 'ö', 'ü', 'ñ', 'ç'];
        $noAccents = ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'c'];
        $answer = str_replace($accents, $noAccents, $answer);

        // Remove various quote types (including smart quotes)
        $quotes = ['"', "'", "\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{201E}", "\u{201A}", "\u{00AB}", "\u{00BB}", "`"];
        $answer = str_replace($quotes, '', $answer);

        // Trim and collapse multiple spaces/whitespace into one
        $answer = trim(preg_replace('/\s+/u', ' ', $answer));

        // Remove trailing punctuation like . or , ; : ! ?
        $answer = rtrim($answer, '.,;:!?');

        return $answer;
    }

    /**
     * Calculate partial score for Matching (Menjodohkan) questions
     * Returns value between 0 and 1
     */
    private function calculateMatchingScore($kunci, $jawab): float
    {
        if (!is_array($kunci) || !is_array($jawab)) {
            // Simple comparison
            return ($this->normalizeAnswer($kunci) === $this->normalizeAnswer($jawab)) ? 1 : 0;
        }

        $totalPairs = count($kunci);
        if ($totalPairs === 0)
            return 0;

        $correctPairs = 0;

        // Handle different formats
        // Format 1: [{left: 'A', right: '1'}, ...]
        // Format 2: Simple indexed array where index is the match
        // Format 3: Associative array {A: '1', B: '2', ...}

        if (isset($kunci[0]) && is_object($kunci[0])) {
            // Object format
            foreach ($kunci as $i => $kPair) {
                $kLeft = $this->normalizeAnswer($kPair->left ?? $kPair->kiri ?? '');
                $kRight = $this->normalizeAnswer($kPair->right ?? $kPair->kanan ?? '');

                // Find matching pair in jawab
                foreach ($jawab as $jPair) {
                    if (!is_object($jPair))
                        continue;
                    $jLeft = $this->normalizeAnswer($jPair->left ?? $jPair->kiri ?? '');
                    $jRight = $this->normalizeAnswer($jPair->right ?? $jPair->kanan ?? '');

                    if ($kLeft === $jLeft && $kRight === $jRight) {
                        $correctPairs++;
                        break;
                    }
                }
            }
        } elseif (isset($kunci[0]) && is_array($kunci[0])) {
            // Array of arrays format
            foreach ($kunci as $i => $kPair) {
                $kLeft = $this->normalizeAnswer($kPair['left'] ?? $kPair['kiri'] ?? $kPair[0] ?? '');
                $kRight = $this->normalizeAnswer($kPair['right'] ?? $kPair['kanan'] ?? $kPair[1] ?? '');

                foreach ($jawab as $jPair) {
                    if (!is_array($jPair))
                        continue;
                    $jLeft = $this->normalizeAnswer($jPair['left'] ?? $jPair['kiri'] ?? $jPair[0] ?? '');
                    $jRight = $this->normalizeAnswer($jPair['right'] ?? $jPair['kanan'] ?? $jPair[1] ?? '');

                    if ($kLeft === $jLeft && $kRight === $jRight) {
                        $correctPairs++;
                        break;
                    }
                }
            }
        } else {
            // Simple indexed comparison (index position is the answer)
            for ($i = 0; $i < $totalPairs; $i++) {
                $kVal = $this->normalizeAnswer($kunci[$i] ?? '');
                $jVal = $this->normalizeAnswer($jawab[$i] ?? '');
                if ($kVal === $jVal && $kVal !== '') {
                    $correctPairs++;
                }
            }
        }

        return $correctPairs / $totalPairs;
    }

    /**
     * Parse answer that could be JSON array, serialized, or plain string
     */
    private function parseArrayAnswer($answer): array
    {
        if (empty($answer))
            return [];

        if (is_string($answer)) {
            // Try JSON first
            $decoded = json_decode($answer, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            // Try unserialize
            $unserialized = @unserialize($answer, ['allowed_classes' => false]);
            if ($unserialized !== false && is_array($unserialized)) {
                return $unserialized;
            }

            // Return as single-element array
            return [$answer];
        }

        return is_array($answer) ? $answer : [$answer];
    }

    /**
     * Parse acceptable answers for short answer type (separated by |)
     */
    private function parseAcceptableAnswers($kunci): array
    {
        if (empty($kunci))
            return [];

        $arr = $this->parseArrayAnswer($kunci);
        $results = [];

        foreach ($arr as $item) {
            if (is_string($item) && strpos($item, '|') !== false) {
                $split = explode('|', $item);
                $results = array_merge($results, array_map('trim', $split));
            } else {
                $results[] = is_string($item) ? trim($item) : $item;
            }
        }

        return $results;
    }

    private function getStatusUjianLabel($status, $mulai, $selesai): string
    {
        switch ($status) {
            case '2':
                if ($selesai) {
                    $start = strtotime($mulai);
                    $end = strtotime($selesai);
                    $diff = $end - $start;
                    $mins = floor($diff / 60);
                    $secs = $diff % 60;
                    return sprintf('%d:%02d', $mins, $secs);
                }
                return 'Selesai';
            case '1':
                return 'Mengerjakan';
            case '3':
                return 'Reset';
            default:
                return $mulai ? 'Belum Selesai' : '';
        }
    }
}
