<?php

namespace App\Models;

use CodeIgniter\Model;

class ElearningModel extends Model
{
    public function getBulan()
    {
        $result = $this->db->table('bulan')->get()->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_bln] = $row->nama_bln;
            }
        }
        return $ret;
    }

    public function getAllKodeMapel()
    {
        $builder = $this->db->table('master_mapel');
        $builder->orderBy('urutan_tampil');
        $builder->where('status', '1');
        $result = $builder->get()->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_mapel] = $row->kode;
            }
        }
        return $ret;
    }

    public function getMateriSiswa($id_kelas, $tgl, $jenis)
    {
        $builder = $this->db->table('kelas_jadwal_materi a');
        $builder->select('a.*, b.id_kjm, c.nama_mapel, c.kode');
        $builder->join('kelas_materi b', 'a.id_materi=b.id_materi');
        $builder->join('master_mapel c', 'b.id_mapel=c.id_mapel');
        $builder->where('a.id_kelas', $id_kelas);
        $builder->where('a.jadwal_materi', $tgl);
        $builder->where('a.jenis', $jenis);
        $result = $builder->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_mapel] = $row;
        }
        return $ret;
    }

    /**
     * Get nilai materi siswa (for student nilai page)
     */
    public function getNilaiMateriSiswa($id_siswa)
    {
        $builder = $this->db->table('log_materi a');
        $builder->select('a.nilai, a.catatan, b.jadwal_materi, c.kode_materi, c.judul_materi, c.jenis, d.nama_mapel, d.kode');
        $builder->join('kelas_jadwal_materi b', 'a.id_materi=b.id_kjm');
        $builder->join('kelas_materi c', 'b.id_materi=c.id_materi');
        $builder->join('master_mapel d', 'c.id_mapel=d.id_mapel');
        $builder->where('a.id_siswa', $id_siswa);
        $result = $builder->get()->getResult();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->jenis][] = $row;
            }
        }
        return $ret;
    }

    // =====================================================
    // JADWAL (SCHEDULE) METHODS - Ported from CI3
    // =====================================================

    /**
     * Get all Mapel
     */
    public function getAllMapel()
    {
        return $this->db->table('master_mapel')
            ->select('id_mapel, nama_mapel, kode')
            ->orderBy('nama_mapel', 'ASC')
            ->get()->getResult();
    }

    /**
     * Get Mapel as array [id => nama]
     */
    public function getMapelArray()
    {
        $result = [];
        $mapels = $this->getAllMapel();
        foreach ($mapels as $m) {
            $result[$m->id_mapel] = $m->nama_mapel;
        }
        return $result;
    }

    /**
     * Get all Guru
     */
    public function getAllGuru()
    {
        return $this->db->table('master_guru')
            ->select('id_guru, nama_guru, nip, kode_guru')
            ->orderBy('nama_guru', 'ASC')
            ->get()->getResult();
    }

    /**
     * Get Guru Mapel Kelas
     */
    public function getGuruMapelKelas($id_guru, $tp, $smt)
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
     * Get all Kelas (with optional level filter)
     */
    public function getAllKelas($tp = null, $smt = null, $level = null)
    {
        $builder = $this->db->table('master_kelas k')
            ->select('k.id_kelas, k.nama_kelas, k.kode_kelas');

        if ($tp && $smt) {
            $builder->join('kelas_siswa ks', 'ks.id_kelas = k.id_kelas AND ks.id_tp = ' . $tp . ' AND ks.id_smt = ' . $smt, 'inner');
            $builder->groupBy('k.id_kelas');
        }



        return $builder->orderBy('k.nama_kelas', 'ASC')
            ->get()->getResult();
    }

    /**
     * Get all Kelas sorted as array [id => nama]
     */
    public function getAllKelasSort($tp = null, $smt = null, $level = null)
    {
        $kelas = $this->getAllKelas($tp, $smt, $level);
        $result = [];
        foreach ($kelas as $k) {
            $result[$k->id_kelas] = $k->nama_kelas;
        }
        return $result;
    }

    /**
     * Get Kelas by Array of IDs
     */
    public function getAllKelasByArrayId($tp, $smt, $arrId)
    {
        if (empty($arrId))
            return [];

        $rows = $this->db->table('master_kelas k')
            ->select('k.id_kelas, k.nama_kelas')
            ->whereIn('k.id_kelas', $arrId)
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()->getResult();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->id_kelas] = $r->nama_kelas;
        }
        return $result;
    }

    /**
     * Get Jadwal KBM (Class Schedule Settings)
     */
    public function getJadwalKbm($tp, $smt)
    {
        $rows = $this->db->table('kelas_jadwal_kbm')
            ->where(['id_tp' => $tp, 'id_smt' => $smt])
            ->get()->getResult();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->id_kelas] = $r;
        }
        return $result;
    }

    /**
     * Decode penugasan mapel-kelas pada jabatan_guru.mapel_kelas.
     * Format: [['id_mapel' => 1, 'nama_mapel' => '...', 'kelas_mapel' => [['kelas' => 20], ...]], ...]
     * Data lama tersimpan sebagai serialize, data baru sebagai JSON.
     */
    public function decodeMapelKelasAssignments($raw): array
    {
        if (!is_string($raw) || trim($raw) === '') return [];

        $decoded = @unserialize($raw, ['allowed_classes' => false]);
        if ($decoded === false && $raw !== 'b:0;') {
            $decoded = json_decode($raw, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Peta penugasan guru per kelas:mapel.
     * Return: ['kelas:mapel' => [id_guru => nama_guru], ...]
     */
    public function getGuruAssignmentMap(int $idTp, int $idSmt): array
    {
        $rows = $this->db->table('jabatan_guru jg')
            ->select('jg.id_guru, jg.mapel_kelas, g.nama_guru')
            ->join('master_guru g', 'g.id_guru = jg.id_guru', 'left')
            ->where('jg.id_tp', $idTp)
            ->where('jg.id_smt', $idSmt)
            ->get()->getResult();

        $map = [];
        foreach ($rows as $row) {
            $idGuru = (int) ($row->id_guru ?? 0);
            if ($idGuru <= 0) continue;

            $namaGuru = trim((string) ($row->nama_guru ?? '')) ?: ('Guru #' . $idGuru);
            foreach ($this->decodeMapelKelasAssignments($row->mapel_kelas) as $item) {
                if (!is_array($item)) continue;

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = $item['kelas_mapel'] ?? [];
                if ($idMapel <= 0 || !is_array($kelasList)) continue;

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas <= 0) continue;

                    $map[$idKelas . ':' . $idMapel][$idGuru] = $namaGuru;
                }
            }
        }

        return $map;
    }

    /**
     * Daftar pasangan kelas:mapel milik satu guru pada TP/SMT.
     * Return: ['kelas:mapel' => true, ...]
     */
    public function getGuruAssignedPairs(int $idGuru, int $idTp, int $idSmt): array
    {
        if ($idGuru <= 0) return [];

        $rows = $this->db->table('jabatan_guru')
            ->select('mapel_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->get()->getResult();

        $pairs = [];
        foreach ($rows as $row) {
            foreach ($this->decodeMapelKelasAssignments($row->mapel_kelas) as $item) {
                if (!is_array($item)) continue;

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = $item['kelas_mapel'] ?? [];
                if ($idMapel <= 0 || !is_array($kelasList)) continue;

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas <= 0) continue;
                    $pairs[$idKelas . ':' . $idMapel] = true;
                }
            }
        }

        return $pairs;
    }

    /**
     * Pasangan kelas:mapel pada jadwal yang belum diampu guru mana pun.
     * Sesi seperti ini tidak muncul di quick-pick jurnal dan tidak ikut
     * dihitung gerbang wajib jurnal sebelum pulang.
     *
     * @return array<int,object>
     */
    public function getUnassignedSchedulePairs(int $idTp, int $idSmt): array
    {
        if ($idTp <= 0 || $idSmt <= 0) {
            return [];
        }

        $rows = $this->db->table('kelas_jadwal_mapel jm')
            ->select('jm.id_kelas, jm.id_mapel, k.nama_kelas, m.nama_mapel')
            ->join('master_kelas k', 'k.id_kelas = jm.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = jm.id_mapel', 'left')
            ->where('jm.id_tp', $idTp)
            ->where('jm.id_smt', $idSmt)
            ->where('jm.id_mapel >', 0)
            ->groupBy('jm.id_kelas, jm.id_mapel, k.nama_kelas, m.nama_mapel')
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('m.nama_mapel', 'ASC')
            ->get()->getResult();

        if (empty($rows)) {
            return [];
        }

        $assigned = [];
        $jabatan = $this->db->table('jabatan_guru')
            ->select('mapel_kelas')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->get()->getResult();

        foreach ($jabatan as $row) {
            foreach ($this->decodeMapelKelasAssignments($row->mapel_kelas) as $item) {
                if (!is_array($item)) continue;

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = $item['kelas_mapel'] ?? [];
                if ($idMapel <= 0 || !is_array($kelasList)) continue;

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas > 0) {
                        $assigned[$idKelas . ':' . $idMapel] = true;
                    }
                }
            }
        }

        $out = [];
        foreach ($rows as $row) {
            if (isset($assigned[(int) $row->id_kelas . ':' . (int) $row->id_mapel])) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Jadwal KBM guru hari ini (untuk quick-pick form jurnal).
     * Return list: id_jadwal, id_kelas, id_mapel, jam_ke, dari, sampai, nama_kelas, nama_mapel.
     */
    public function getJadwalKbmGuruHariIni(int $idGuru, int $hari, int $idTp, int $idSmt): array
    {
        $mine = $this->getGuruAssignedPairs($idGuru, $idTp, $idSmt);
        if (empty($mine)) return [];

        $rows = $this->db->table('kelas_jadwal_mapel jm')
            ->select('jm.id_jadwal, jm.id_kelas, jm.id_mapel, jm.jam_ke, jm.dari, jm.sampai, k.nama_kelas, m.nama_mapel')
            ->join('master_kelas k', 'k.id_kelas = jm.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = jm.id_mapel', 'left')
            ->where('jm.id_tp', $idTp)
            ->where('jm.id_smt', $idSmt)
            ->where('jm.id_hari', $hari)
            ->where('jm.id_mapel >', 0)
            ->orderBy('jm.dari', 'ASC')
            ->get()->getResult();

        $out = [];
        foreach ($rows as $r) {
            if (isset($mine[(int) $r->id_kelas . ':' . (int) $r->id_mapel])) {
                $out[] = $r;
            }
        }
        return $out;
    }

    /**
     * Get Jadwal Harian (Daily Schedule)
     */
    public function getJadwalHarian($tp, $smt, $hari, $kelas = null)
    {
        $builder = $this->db->table('kelas_jadwal_mapel jm')
            ->select('jm.*, m.kode, m.nama_mapel')
            ->join('master_mapel m', 'm.id_mapel = jm.id_mapel', 'left')
            ->where(['jm.id_tp' => $tp, 'jm.id_smt' => $smt, 'jm.id_hari' => $hari])
            ->orderBy('jm.dari', 'ASC');

        if ($kelas) {
            $builder->where('jm.id_kelas', $kelas);
        }

        $rows = $builder->get()->getResult();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->id_kelas][] = $r;
        }
        return $result;
    }

    /**
     * Get all Jadwal Harian
     */
    public function getAllJadwalHarian($tp, $smt)
    {
        return $this->db->table('kelas_jadwal_mapel')
            ->where(['id_tp' => $tp, 'id_smt' => $smt])
            ->orderBy('id_kelas, id_hari, dari', 'ASC')
            ->get()->getResult();
    }

    /**
     * Insert batch Jadwal KBM
     */
    public function insertBatchKbm($data)
    {
        if (empty($data))
            return true;
        return $this->db->table('kelas_jadwal_kbm')->insertBatch($data);
    }

    /**
     * Update batch Jadwal KBM
     */
    public function updateBatchKbm($data)
    {
        if (empty($data))
            return true;
        return $this->db->table('kelas_jadwal_kbm')->updateBatch($data, 'id_kbm');
    }

    /**
     * Insert batch Jadwal Mapel
     */
    public function insertBatchJadwal($data)
    {
        if (empty($data))
            return true;
        return $this->db->table('kelas_jadwal_mapel')->insertBatch($data);
    }

    /**
     * Update batch Jadwal Mapel
     */
    public function updateBatchJadwal($data)
    {
        if (empty($data))
            return true;
        return $this->db->table('kelas_jadwal_mapel')->updateBatch($data, 'id_jadwal');
    }

    /**
     * Delete Jadwal by IDs
     */
    public function deleteJadwalByIds($ids)
    {
        if (empty($ids))
            return false;
        return $this->db->table('kelas_jadwal_mapel')->whereIn('id_jadwal', $ids)->delete();
    }

    /**
     * Delete Jadwal Mapel by ID
     */
    public function deleteJadwalMapel($id)
    {
        return $this->db->table('kelas_jadwal_mapel')->where('id_jadwal', $id)->delete();
    }

    /**
     * Delete Jadwal by hari & tp/smt
     */
    public function deleteJadwalHarian($tp, $smt, $hari = null, $includeMapel = false)
    {
        $builder = $this->db->table('kelas_jadwal_mapel')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt);

        if ($hari !== null) {
            $builder->where('id_hari', $hari);
        }

        if (!$includeMapel) {
            $builder->where('id_mapel', '0');
        }

        return $builder->delete();
    }

    /** Helper for Copy Jadwal */
    public function getTpByTahun($tahun)
    {
        return $this->db->table('master_tp')->where('tahun', $tahun)->get()->getRow();
    }

    public function getAllJadwalKbmFull($tp, $smt)
    {
        return $this->db->table('kelas_jadwal_kbm kbm')
            ->select('kbm.*, k.kode_kelas, k.nama_kelas')
            ->join('master_kelas k', 'k.id_kelas = kbm.id_kelas')
            ->where(['kbm.id_tp' => $tp, 'kbm.id_smt' => $smt])
            ->get()->getResult();
    }

    public function getAllJadwalHarianFull($tp, $smt)
    {
        return $this->db->table('kelas_jadwal_mapel jm')
            ->select('jm.*, k.kode_kelas')
            ->join('master_kelas k', 'k.id_kelas = jm.id_kelas')
            ->where(['jm.id_tp' => $tp, 'jm.id_smt' => $smt])
            ->get()->getResult();
    }
}

