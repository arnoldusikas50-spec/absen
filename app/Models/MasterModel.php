<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MasterModel - Migrated from CI3 Master_model
 * Handles master data operations (siswa, guru, kelas, mapel)
 */
class MasterModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // ==========================================
    // SISWA FUNCTIONS
    // ==========================================

    /**
     * Get all siswa with pagination
     * Returns students including those not yet assigned to a class
     */
    public function getAllSiswa(int $tp, int $smt, int $limit = 10, int $offset = 0, ?string $search = null): array
    {
        $builder = $this->db->table('master_siswa a')
            ->select('a.*, b.id_kelas, c.nama_kelas, c.kode_kelas')
            // Use LEFT JOIN with tp/smt in JOIN condition (not WHERE) to include unassigned students
            ->join('kelas_siswa b', "a.id_siswa = b.id_siswa AND b.id_tp = $tp AND b.id_smt = $smt", 'left')
            ->join('master_kelas c', 'b.id_kelas = c.id_kelas', 'left')
            ->join('buku_induk bi', 'bi.id_siswa = a.id_siswa AND bi.status = "1"'); // Only active students

        if ($search) {
            $builder->groupStart()
                ->like('a.nama', $search)
                ->orLike('a.nis', $search)
                ->orLike('a.nisn', $search)
                ->groupEnd();
        }

        return $builder->orderBy('a.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Count total siswa
     */
    public function getTotalSiswa(int $tp, int $smt, ?string $search = null): int
    {
        $builder = $this->db->table('master_siswa a')
            ->join('kelas_siswa b', 'a.id_siswa = b.id_siswa', 'left')
            ->where('b.id_tp', $tp)
            ->where('b.id_smt', $smt);

        if ($search) {
            $builder->groupStart()
                ->like('a.nama', $search)
                ->orLike('a.nis', $search)
                ->orLike('a.nisn', $search)
                ->groupEnd();
        }

        return $builder->countAllResults();
    }

    /**
     * Get siswa by ID
     */
    public function getSiswa(int $id): ?object
    {
        return $this->db->table('master_siswa')
            ->where('id_siswa', $id)
            ->get()
            ->getRow();
    }

    /**
     * Get siswa by username
     */
    public function getSiswaByUsername(string $username): ?object
    {
        return $this->db->table('master_siswa')
            ->where('username', $username)
            ->get()
            ->getRow();
    }

    /**
     * Save siswa
     */
    public function saveSiswa(array $data): bool
    {
        return $this->db->table('master_siswa')->insert($data);
    }

    /**
     * Update siswa
     */
    public function updateSiswa(int $id, array $data): bool
    {
        return $this->db->table('master_siswa')
            ->where('id_siswa', $id)
            ->update($data);
    }

    /**
     * Delete siswa
     */
    public function deleteSiswa(int $id): bool
    {
        return $this->db->table('master_siswa')
            ->where('id_siswa', $id)
            ->delete();
    }

    /**
     * Get siswa by ID including buku_induk status
     */
    public function getSiswaById(int $id): ?object
    {
        return $this->db->table('master_siswa a')
            ->select('a.*, b.status')
            ->join('buku_induk b', 'a.id_siswa = b.id_siswa', 'left')
            ->where('a.id_siswa', $id)
            ->get()
            ->getRow();
    }

    public function getAllGuru()
    {
        return $this->db->table('master_guru')->orderBy('nama_guru', 'ASC')->get()->getResult();
    }

    /**
     * Builder dasar daftar siswa: satu baris per siswa pada TP/SMT aktif.
     *
     * Status mengikuti buku_induk (1 aktif, 2 lulus, 3 pindah, 4 keluar);
     * status "5" adalah akun login nonaktif untuk siswa yang masih aktif.
     * Kelas difilter terpisah supaya pengguna bisa memilih status dan kelas
     * dalam waktu yang sama.
     */
    private function siswaListBuilder(int $tp, int $smt, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false)
    {
        $builder = $this->db->table('master_siswa a')
            ->join('buku_induk u', 'u.id_siswa = a.id_siswa', 'left')
            ->join('kelas_siswa d', "d.id_siswa = a.id_siswa AND d.id_tp = {$tp} AND d.id_smt = {$smt}", 'left')
            ->join('master_kelas f', 'f.id_kelas = d.id_kelas', 'left')
            ->join('users us', 'us.username = a.username', 'left');

        switch ($status) {
            case '1':
                $builder->where('u.status', '1');
                break;
            case '2':
                $builder->where('u.status', '2');
                break;
            case '3':
                $builder->where('u.status', '3');
                break;
            case '4':
                $builder->where('u.status', '4');
                break;
            case '5':
                $builder->where('u.status', '1')->where('us.active', '0');
                break;
        }

        if ($kelas !== null && $kelas > 0) {
            $builder->where('d.id_kelas', $kelas);
        } elseif ($tanpaKelas) {
            // Filter "Tanpa Kelas": kelas_siswa untuk TP/SMT aktif belum
            // menempatkan siswa ini ke kelas mana pun. Mutually exclusive
            // dengan filter kelas — controller memastikan salah satu saja.
            $builder->where('d.id_kelas', null);
        }

        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('a.nama', $search)
                ->orLike('a.nis', $search)
                ->orLike('a.nisn', $search)
                ->groupEnd();
        }

        return $builder;
    }

    /**
     * Get students with custom pagination and filtering.
     */
    public function getSiswaPage(int $tp, int $smt, int $offset, int $limit, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false): array
    {
        $builder = $this->siswaListBuilder($tp, $smt, $status, $kelas, $search, $tanpaKelas);

        return $builder
            ->select('a.id_siswa, a.foto, a.nama, a.nis, a.nisn, a.jenis_kelamin, a.agama, a.username, d.id_kelas, f.nama_kelas, us.active, u.status')
            ->orderBy('a.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();
    }

    /**
     * Count total students with filtering.
     */
    public function getSiswaTotalPage(int $tp, int $smt, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false): int
    {
        return $this->siswaListBuilder($tp, $smt, $status, $kelas, $search, $tanpaKelas)->countAllResults();
    }

    /**
     * Ringkasan jumlah siswa untuk kartu statistik halaman /datasiswa.
     *
     * @return array<string,int>
     */
    public function getSiswaStats(int $tp, int $smt): array
    {
        $row = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM master_siswa) AS total,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '1') AS aktif,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '2') AS lulus,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '3') AS pindah,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '4') AS keluar,
                (SELECT COUNT(DISTINCT a.id_siswa) FROM master_siswa a
                    LEFT JOIN kelas_siswa d ON d.id_siswa = a.id_siswa AND d.id_tp = ? AND d.id_smt = ?
                    WHERE d.id_kelas IS NULL) AS tanpa_kelas,
                (SELECT COUNT(DISTINCT a.id_siswa) FROM master_siswa a
                    INNER JOIN users us ON us.username = a.username
                    WHERE us.active = '0') AS akun_nonaktif",
            [$tp, $smt]
        )->getRow();

        return [
            'total'         => (int) ($row->total ?? 0),
            'aktif'         => (int) ($row->aktif ?? 0),
            'lulus'         => (int) ($row->lulus ?? 0),
            'pindah'        => (int) ($row->pindah ?? 0),
            'keluar'        => (int) ($row->keluar ?? 0),
            'tanpa_kelas'   => (int) ($row->tanpa_kelas ?? 0),
            'akun_nonaktif' => (int) ($row->akun_nonaktif ?? 0),
        ];
    }

    /**
     * Get students by array of IDs
     */
    public function getSiswaByArrId(array $ids): array
    {
        if (empty($ids))
            return [];
        $result = $this->db->table('master_siswa a')
            ->select('a.id_siswa, a.nama, f.nama_kelas')
            ->join('kelas_siswa b', 'b.id_siswa = a.id_siswa', 'left')
            ->join('master_kelas f', 'f.id_kelas = b.id_kelas', 'left')
            ->whereIn('a.id_siswa', $ids)
            ->get()
            ->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_siswa] = $row;
        }
        return $ret;
    }

    /**
     * Get students by array of NISN, NIS, or Username
     */
    public function getSiswaByArrNisn(array $arr_nisn, array $arr_nis, array $arr_username): array
    {
        return $this->db->table('master_siswa')
            ->select('id_siswa, nama, nisn, nis, username')
            ->whereIn('nisn', $arr_nisn)
            ->orWhereIn('nis', $arr_nis)
            ->orWhereIn('username', $arr_username)
            ->get()
            ->getResult();
    }

    /**
     * Get students by list of usernames
     */
    public function getSiswaByUsernames(array $usernames): array
    {
        if (empty($usernames))
            return [];
        return $this->db->table('master_siswa')
            ->select('id_siswa, username')
            ->whereIn('username', $usernames)
            ->get()
            ->getResult();
    }

    /**
     * Get students for new class assignment
     */
    public function getSiswaKelasBaru(int $tp, int $smt): array
    {
        $result = $this->db->table('kelas_siswa a')
            ->select('b.id_siswa, b.nama, f.id_kelas, f.nama_kelas, f.kode_kelas')
            ->join('master_siswa b', 'b.id_siswa = a.id_siswa')
            ->join('master_kelas f', 'f.id_kelas = a.id_kelas')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->get()
            ->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_siswa] = $row;
        }
        return $ret;
    }

    /**
     * Get data siswa by kelas with optional pagination and search
     */
    public function getDataSiswaByKelas(int $tp, int $smt, int $id_kelas, int $offset = 0, int $limit = 0, ?string $search = null): array
    {
        $builder = $this->db->table('kelas_siswa a')
            ->select('b.id_siswa, b.nama, b.nis, b.nisn, b.jenis_kelamin, b.username, b.password, b.foto, b.agama, f.nama_kelas, us.active')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa', 'right')
            ->join('master_kelas f', 'a.id_kelas = f.id_kelas')
            ->join('users us', 'b.username = us.username', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->where('a.id_kelas', $id_kelas);

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        if ($search) {
            $builder->groupStart()
                ->like('b.nama', $search)
                ->orLike('b.nis', $search)
                ->orLike('b.nisn', $search)
                ->groupEnd();
        }

        return $builder->orderBy('b.nama', 'ASC')->get()->getResult();
    }

    /**
     * Count data siswa by kelas for pagination
     */
    public function getDataSiswaByKelasPage(int $tp, int $smt, int $id_kelas, ?string $search = null): int
    {
        $builder = $this->db->table('kelas_siswa a')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->where('a.id_kelas', $id_kelas);

        if ($search) {
            $builder->groupStart()
                ->like('b.nama', $search)
                ->orLike('b.nis', $search)
                ->orLike('b.nisn', $search)
                ->groupEnd();
        }

        return $builder->countAllResults();
    }

    /**
     * Alias for getDataSiswaByKelas for backward compatibility
     */
    public function getSiswaByKelas(int $id_kelas, int $tp, int $smt): array
    {
        return $this->getDataSiswaByKelas($tp, $smt, $id_kelas);
    }

    // ==========================================
    // GURU FUNCTIONS
    // ==========================================

    /**
     * Get all guru with full details (Ported from CI3)
     */
    public function getAllDataGuru(int $tp, int $smt): array
    {
        return $this->db->table('master_guru a')
            ->select('a.id_guru, a.nama_guru, a.nip, a.kode_guru, a.jenis_kelamin, a.foto, b.id_jabatan, b.id_kelas, b.mapel_kelas, b.ekstra_kelas, c.id_level, c.level, d.nama_kelas, e.tahun, f.nama_smt, g.active')
            ->join('jabatan_guru b', "a.id_guru=b.id_guru AND b.id_tp=$tp AND b.id_smt=$smt", 'left')
            ->join('level_guru c', 'b.id_jabatan=c.id_level', 'left')
            ->join('master_kelas d', "b.id_kelas=d.id_kelas AND d.id_tp=$tp AND d.id_smt=$smt", 'left')
            ->join('master_tp e', 'b.id_tp=e.id_tp', 'left')
            ->join('master_smt f', 'b.id_smt=f.id_smt', 'left')
            ->join('users g', 'a.username=g.username', 'left')
            ->orderBy('c.id_level', 'DESC')
            ->orderBy('a.id_guru', 'ASC')
            ->get()->getResult();
    }

    /**
     * Get data guru for DataTables (Ported from CI3)
     */
    public function getDataGuru(int $tp, int $smt, ?string $search = null): array
    {
        $builder = $this->db->table('master_guru a')
            ->select('a.id_guru, a.nama_guru, a.nip, a.kode_guru, a.jenis_kelamin, a.foto, b.id_jabatan, b.id_kelas, b.mapel_kelas, c.id_level, c.level, d.nama_kelas, e.tahun, f.nama_smt')
            ->join('jabatan_guru b', "a.id_guru=b.id_guru AND b.id_tp=$tp AND b.id_smt=$smt", 'left')
            ->join('level_guru c', 'b.id_jabatan=c.id_level', 'left')
            ->join('master_kelas d', "b.id_kelas=d.id_kelas AND d.id_tp=$tp AND d.id_smt=$smt", 'left')
            ->join('master_tp e', 'b.id_tp=e.id_tp', 'left')
            ->join('master_smt f', 'b.id_smt=f.id_smt', 'left');

        if ($search) {
            $builder->groupStart()
                ->like('a.nama_guru', $search)
                ->orLike('a.nip', $search)
                ->orLike('a.kode_guru', $search)
                ->groupEnd();
        }

        return $builder->get()->getResult();
    }

    /**
     * Get guru by ID including jabatan (Ported from CI3)
     */
    public function getGuruById(int $id, ?int $id_tp = null, ?int $id_smt = null): ?object
    {
        $builder = $this->db->table('master_guru a')
            ->select('a.*, b.id_jabatan, b.id_kelas, b.mapel_kelas, b.ekstra_kelas, c.*')
            ->join('jabatan_guru b', 'a.id_guru=b.id_guru', 'left')
            ->join('level_guru c', 'b.id_jabatan=c.id_level', 'left');

        if ($id_tp !== null && $id_smt !== null) {
            $builder->select('d.*')
                ->join('master_kelas d', "a.id_guru=d.guru_id AND d.id_tp=$id_tp AND d.id_smt=$id_smt", 'left');
        }

        return $builder->where('a.id_guru', $id)->get()->getRow();
    }

    /**
     * Riwayat jabatan guru, terurut dari TP/SMT terbaru.
     * Return flat list agar setiap periode dapat ditampilkan apa adanya.
     */
    public function getDetailJabatanGuru(int $id_guru): array
    {
        return $this->db->table('master_guru a')
            ->select('a.id_guru, a.nama_guru, b.id_tp, b.id_smt, b.mapel_kelas, b.ekstra_kelas, c.id_level, c.level, d.id_kelas, d.nama_kelas, e.tahun, f.smt, f.nama_smt')
            ->join('jabatan_guru b', 'a.id_guru=b.id_guru', 'inner')
            ->join('level_guru c', 'b.id_jabatan=c.id_level', 'left')
            ->join('master_kelas d', 'b.id_kelas=d.id_kelas', 'left')
            ->join('master_tp e', 'b.id_tp=e.id_tp', 'left')
            ->join('master_smt f', 'b.id_smt=f.id_smt', 'left')
            ->where('a.id_guru', $id_guru)
            ->orderBy('b.id_tp', 'DESC')
            ->orderBy('b.id_smt', 'ASC')
            ->get()->getResult();
    }

    /**
     * Jabatan guru for specific TP/SMT (Ported from CI3)
     */
    public function getJabatanGuru(int $id_guru, int $tp, int $smt): ?object
    {
        return $this->db->table('master_guru a')
            ->select('a.id_guru, a.nama_guru, b.mapel_kelas, b.ekstra_kelas, c.id_level, c.level, d.id_kelas, d.nama_kelas')
            ->join('jabatan_guru b', "a.id_guru=b.id_guru AND b.id_tp=$tp AND b.id_smt=$smt", 'left')
            ->join('level_guru c', 'b.id_jabatan=c.id_level', 'left')
            ->join('master_kelas d', "b.id_kelas=d.id_kelas AND d.id_tp=$tp AND d.id_smt=$smt", 'left')
            ->where('a.id_guru', $id_guru)
            ->get()->getRow();
    }

    /**
     * Class/Mapel assignment for guru (Ported from CI3)
     */
    public function getGuruMapel(int $tp, int $smt, ?array $ids_guru = null): array
    {
        $builder = $this->db->table('jabatan_guru a')
            ->select('a.mapel_kelas, a.ekstra_kelas, a.id_jabatan, a.id_kelas, b.id_guru, b.nama_guru')
            ->join('master_guru b', 'a.id_guru=b.id_guru')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt);

        if ($ids_guru) {
            $builder->whereIn('a.id_guru', $ids_guru);
        }

        return $builder->get()->getResult();
    }

    /**
     * Update guru by ID
     */
    public function updateGuru(int $id, array $data): bool
    {
        return $this->db->table('master_guru')
            ->where('id_guru', $id)
            ->update($data);
    }

    /**
     * Delete guru by ID
     */
    public function deleteGuru($id): bool
    {
        if (is_array($id)) {
            return $this->db->table('master_guru')->whereIn('id_guru', $id)->delete();
        }
        return $this->db->table('master_guru')->where('id_guru', $id)->delete();
    }

    /**
     * Delete teacher from linked tables.
     * Dijalankan dalam satu transaksi agar tidak ada penghapusan setengah jadi.
     */
    public function deleteGuruLinked(int $id_guru): bool
    {
        $tables = [
            'jabatan_guru' => 'id_guru',
            'kelas_materi' => 'id_guru',
            'kelas_catatan_mapel' => 'id_guru',
            'guru_jurnal' => 'id_guru',
            'post' => 'dari',
            'post_comments' => 'dari',
            'post_reply' => 'dari',
        ];

        $this->db->transBegin();

        try {
            $guru = $this->db->table('master_guru')
                ->select('id_user')
                ->where('id_guru', $id_guru)
                ->get()->getRow();

            // Lepas referensi wali kelas supaya tidak menunjuk guru yang sudah dihapus.
            if ($this->db->tableExists('master_kelas') && $this->db->fieldExists('guru_id', 'master_kelas')) {
                if (!$this->db->table('master_kelas')
                    ->where('guru_id', $id_guru)
                    ->update(['guru_id' => null])) {
                    throw new \RuntimeException('Gagal melepas penunjukan wali kelas');
                }
            }

            foreach ($tables as $table => $field) {
                if (!$this->db->tableExists($table)) {
                    continue;
                }
                if (!$this->db->table($table)->where($field, $id_guru)->delete()) {
                    throw new \RuntimeException("Gagal menghapus data pada tabel {$table}");
                }
            }

            $idUser = (int) ($guru->id_user ?? 0);
            if ($idUser > 0 && $this->db->tableExists('users')) {
                $isAdmin = $this->db->tableExists('users_groups')
                    && $this->db->table('users_groups')
                        ->where('user_id', $idUser)
                        ->where('group_id', 1)
                        ->countAllResults() > 0;

                if (!$isAdmin) {
                    // Akun tidak ikut dihapus agar jejak/relasi lain tetap utuh,
                    // tetapi dinonaktifkan supaya tidak bisa login lagi.
                    $this->db->table('users')->where('id', $idUser)->update(['active' => 0]);
                }
            }

            if (!$this->deleteGuru($id_guru)) {
                throw new \RuntimeException('Gagal menghapus data master_guru');
            }

            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[deleteGuruLinked] guru id=' . $id_guru . ': ' . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // KELAS FUNCTIONS
    // ==========================================

    /**
     * Get all kelas by TP and semester
     */
    public function getAllKelas(int $tp, int $smt): array
    {
        return $this->db->table('master_kelas a')
            ->select('a.*, b.nama_guru as wali')
            ->join('master_guru b', 'a.guru_id = b.id_guru', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('a.level_id', 'ASC')
            ->orderBy('a.nama_kelas', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get kelas by ID
     */
    public function getKelasById(int $id): ?object
    {
        return $this->db->table('master_kelas')
            ->select('id_kelas, nama_kelas, level_id, jumlah_siswa')
            ->where('id_kelas', $id)
            ->get()
            ->getRow();
    }

    /**
     * Get kelas by level
     */
    public function getKelasByLevel(int $tp, int $smt, int $level): array
    {
        return $this->db->table('master_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('level_id', $level)
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Save kelas
     */
    public function saveKelas(array $data): bool
    {
        return $this->db->table('master_kelas')->insert($data);
    }

    /**
     * Update kelas
     */
    public function updateKelas(int $id, array $data): bool
    {
        return $this->db->table('master_kelas')
            ->where('id_kelas', $id)
            ->update($data);
    }

    /**
     * Delete kelas
     */
    public function deleteKelas(int $id): bool
    {
        return $this->db->table('master_kelas')
            ->where('id_kelas', $id)
            ->delete();
    }

    /**
     * Count siswa in kelas
     */
    public function countSiswaInKelas(int $idKelas, int $tp, int $smt): int
    {
        return $this->db->table('kelas_siswa')
            ->where('id_kelas', $idKelas)
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->countAllResults();
    }

    // ==========================================
    // MAPEL FUNCTIONS
    // ==========================================

    /**
     * Get all mapel
     */
    public function getAllMapel(?int $jenjang = null): array
    {
        $builder = $this->db->table('master_mapel');

        if ($jenjang !== null) {
            $builder->where('jenjang', $jenjang);
        }

        return $builder->orderBy('urutan_tampil', 'ASC')
            ->orderBy('nama_mapel', 'ASC')
            ->get()
            ->getResult();
    }

    public function getDataKelompokMapel()
    {
        $result = $this->db->table('master_kelompok_mapel')
            ->where('id_parent', '0')
            ->orderBy('kode_kel_mapel')
            ->get()
            ->getResult();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kel_mapel] = $row;
        }
        return $ret;
    }

    public function getDataSubKelompokMapel()
    {
        $result = $this->db->table('master_kelompok_mapel')
            ->where('id_parent <> 0')
            ->orderBy('kode_kel_mapel')
            ->get()
            ->getResult();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kel_mapel] = $row;
        }
        return $ret;
    }

    public function getKategoriKelompokMapel()
    {
        return $this->db->table('master_kelompok_mapel')
            ->select('kode_kel_mapel, kategori')
            ->groupStart()
            ->where('kategori', 'WAJIB')
            ->orWhere('kategori', 'AGAMA (Kemenag)')
            ->groupEnd()
            ->get()
            ->getResult();
    }

    /**
     * Get mapel by ID
     */
    public function getMapel(int $id): ?object
    {
        return $this->db->table('master_mapel')
            ->where('id_mapel', $id)
            ->get()
            ->getRow();
    }

    /**
     * Save mapel
     */
    public function saveMapel(array $data): bool
    {
        return $this->db->table('master_mapel')->insert($data);
    }

    /**
     * Update mapel
     */
    public function updateMapel(int $id, array $data): bool
    {
        return $this->db->table('master_mapel')
            ->where('id_mapel', $id)
            ->update($data);
    }

    /**
     * Delete mapel
     */
    public function deleteMapel(int $id): bool
    {
        return $this->db->table('master_mapel')
            ->where('id_mapel', $id)
            ->delete();
    }

    // ==========================================
    // JURUSAN/PROGRAM FUNCTIONS
    // ==========================================

    /**
     * Get all jurusan
     */
    public function getAllJurusan(): array
    {
        // Prefer master_jurusan if exists and has data, otherwise fallback to jurusan (legacy)
        $builder = $this->db->table('master_jurusan');
        if ($this->db->tableExists('master_jurusan')) {
            $rows = $builder->orderBy('nama_jurusan', 'ASC')->get()->getResult();
            $normalized = [];
            foreach ($rows as $r) {
                $r->singkatan = $r->kode_jurusan ?? '';
                $normalized[] = $r;
            }
            if (count($normalized) > 0) {
                return $normalized;
            }
        }
        // Fallback legacy table (column jurusan_id -> id_jurusan)
        if ($this->db->tableExists('jurusan')) {
            $rows = $this->db->table('jurusan')->orderBy('nama_jurusan', 'ASC')->get()->getResult();
            $normalized = [];
            foreach ($rows as $r) {
                $obj = (object) [
                    'id_jurusan' => $r->jurusan_id ?? ($r->id_jurusan ?? null),
                    'nama_jurusan' => $r->nama_jurusan ?? '',
                    'singkatan' => $r->singkatan ?? '',
                ];
                $normalized[] = $obj;
            }
            return $normalized;
        }
        return [];
    }

    /**
     * Get jurusan by ID
     */
    public function getJurusan(int $id): ?object
    {
        return $this->db->table('master_jurusan')
            ->where('id_jurusan', $id)
            ->get()
            ->getRow();
    }

    // ==========================================
    // DROPDOWN HELPERS
    // ==========================================

    /**
     * Get kelas for dropdown
     */
    public function getDropdownKelas(int $tp, int $smt): array
    {
        $result = [];
        $kelas = $this->getAllKelas($tp, $smt);

        foreach ($kelas as $k) {
            $result[$k->id_kelas] = $k->nama_kelas;
        }

        return $result;
    }

    /**
     * Get mapel for dropdown
     */
    public function getDropdownMapel(): array
    {
        $result = [];
        $mapels = $this->getAllMapel();

        foreach ($mapels as $m) {
            $result[$m->id_mapel] = $m->nama_mapel;
        }

        return $result;
    }

    /**
     * Get guru for dropdown
     */
    public function getDropdownGuru(): array
    {
        $result = [];
        $gurus = $this->getAllGuru();

        foreach ($gurus as $g) {
            $result[$g->id_guru] = $g->nama_guru;
        }

        return $result;
    }

    /**
     * Generate id_kelas_siswa (kelas_siswa tidak memakai auto_increment,
     * jadi PK selalu dihitung eksplisit — lihat juga KelasSiswaUnique migration).
     */
    public function generateKelasSiswaId(): int
    {
        $row = $this->db->table('kelas_siswa')->selectMax('id_kelas_siswa')->get()->getRowArray();
        return (int) ($row['id_kelas_siswa'] ?? 0) + 1;
    }

    /**
     * Create record in any table
     */
    public function create(string $table, array $data)
    {
        return $this->db->table($table)->insert($data);
    }

    /**
     * Create multiple records
     */
    public function insertBatchData(string $table, array $data)
    {
        return $this->db->table($table)->insertBatch($data);
    }

    /**
     * Update record in any table
     */
    public function updateData(string $table, array $data, string $pk, $id)
    {
        return $this->db->table($table)->where($pk, $id)->update($data);
    }

    /**
     * Update multiple records
     */
    public function updateBatchData(string $table, array $data, string $pk)
    {
        return $this->db->table($table)->updateBatch($data, $pk);
    }

    /**
     * Delete from any table
     */
    public function hapus(string $table, $ids, string $pk): bool
    {
        if (is_array($ids)) {
            return $this->db->table($table)->whereIn($pk, $ids)->delete();
        }
        return $this->db->table($table)->where($pk, $ids)->delete();
    }

    /**
     * Get Jml Hari Efektif
     */
    public function getJmlHariEfektif(string $id)
    {
        return $this->db->table('master_hari_efektif')
            ->where('id_hari_efektif', $id)
            ->get()
            ->getRow();
    }

    /**
     * Get guru mapel and kelas
     */
    public function getGuruMapelKelas(int $idGuru, int $tp, int $smt)
    {
        return $this->db->table('jabatan_guru')
            ->where('id_guru', $idGuru)
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()
            ->getRow();
    }

    /**
     * Get all kelas by array of IDs
     */
    public function getAllKelasByArrayId(int $tp, int $smt, array $ids): array
    {
        if (empty($ids))
            return [];
        return $this->db->table('master_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->whereIn('id_kelas', $ids)
            ->orderBy('level_id', 'ASC')
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get levels by jenjang
     */
    public function getLevelsByJenjang(int $jenjang): array
    {
        $levels = [];
        if ($jenjang == 1) { // SD
            $levels = [
                (object) ['level' => 1],
                (object) ['level' => 2],
                (object) ['level' => 3],
                (object) ['level' => 4],
                (object) ['level' => 5],
                (object) ['level' => 6]
            ];
        } elseif ($jenjang == 2) { // SMP
            $levels = [
                (object) ['level' => 7],
                (object) ['level' => 8],
                (object) ['level' => 9]
            ];
        } elseif ($jenjang == 3 || $jenjang == 4) { // SMA/SMK
            $levels = [
                (object) ['level' => 10],
                (object) ['level' => 11],
                (object) ['level' => 12]
            ];
        } else {
            // Default fallback if unknown jenjang, return empty or common levels?
            // Returning empty array as per CI3 logic
        }

        return $levels;
    }

    /**
     * Get unique religions from master_siswa
     */
    public function getAgamaSiswa(): array
    {
        $result = $this->db->table('master_siswa')
            ->select('agama')
            ->distinct()
            ->where('agama IS NOT NULL', null, false)
            ->where('agama != "0"', null, false)
            ->notLike('agama', 'Pilih')
            ->get()
            ->getResult();

        $ret = ['-' => 'Bukan Mapel Agama'];
        foreach ($result as $row) {
            $ret[$row->agama] = $row->agama;
        }
        return $ret;
    }

    public function getAllKodeKelas($tp, $smt)
    {
        $result = $this->db->table('master_kelas')
            ->select('id_kelas, kode_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()
            ->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kelas] = $row->kode_kelas;
        }
        return $ret;
    }

    // ==========================================
    // GURU/JABATAN FUNCTIONS (additional)
    // ==========================================
    public function getAllJabatanGuru(int $id_guru): array
    {
        $result = $this->db->table('jabatan_guru')
            ->where('id_guru', $id_guru)
            ->get()->getResult();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_tp][$row->id_smt] = $row->id_kelas;
        }
        return $ret;
    }

    // ==========================================
    // MAPEL/Kelompok FUNCTIONS (additional)
    // ==========================================
    public function getKodeKelompokMapel(): array
    {
        $result = $this->db->table('master_kelompok_mapel')
            ->orderBy('kode_kel_mapel')
            ->get()->getResult();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kel_mapel] = $row;
        }
        return $ret;
    }

    public function getAllStatusMapel(?array $arrKelompok = null, ?string $arrMapel = null): array
    {
        $builder = $this->db->table('master_mapel');

        if ($arrKelompok !== null) {
            $builder->whereIn('kelompok', $arrKelompok);
        }
        if ($arrMapel !== null) {
            $builder->orWhereIn('id_mapel', explode(',', $arrMapel));
        }

        return $builder->orderBy('urutan_tampil')->get()->getResult();
    }

    // ==========================================
    // ALUMNI FUNCTIONS (Ported from CI3)
    // ==========================================
    public function getDistinctTahunLulus(): array
    {
        $result = $this->db->table('buku_induk')
            ->select('tahun_lulus')
            ->distinct()
            ->where('tahun_lulus IS NOT NULL', null, false)
            ->where('tahun_lulus != ""')
            ->get()
            ->getResult();

        $ret = [];
        foreach ($result as $row) {
            if ($row->tahun_lulus !== '') {
                $ret[$row->tahun_lulus] = $row->tahun_lulus;
            }
        }
        return $ret;
    }

    public function getDistinctKelasAkhir(): array
    {
        $result = $this->db->table('buku_induk')
            ->select('kelas_akhir')
            ->distinct()
            ->where('kelas_akhir IS NOT NULL', null, false)
            ->where('kelas_akhir != ""')
            ->get()
            ->getResult();
        $ret = [];
        foreach ($result as $row) {
            if ($row->kelas_akhir !== '') {
                $ret[$row->kelas_akhir] = $row->kelas_akhir;
            }
        }
        return $ret;
    }

    public function getAlumniByTahun(string $tahun, ?string $kelas = null): array
    {
        $builder = $this->db->table('buku_induk a')
            ->select('*')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa')
            ->where('a.tahun_lulus', $tahun);
        if ($kelas !== null) {
            $builder->where('a.kelas_akhir', $kelas);
        }
        return $builder->get()->getResult();
    }

    public function getAlumniFilter(string $filter): array
    {
        return $this->db->table('buku_induk a')
            ->select('*')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa')
            ->where('a.status', $filter)
            ->get()
            ->getResult();
    }

    public function getAlumniById(int $id)
    {
        return $this->db->table('master_siswa a')
            ->select('*')
            ->join('buku_induk b', 'a.id_siswa = b.id_siswa')
            ->where('a.id_siswa', $id)
            ->get()
            ->getRow();
    }


}
