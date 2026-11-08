<?php

namespace App\Models;

use CodeIgniter\Model;

class KelasModel extends Model
{
    public function getCatatanMapelKelas($kelas, $mapel, $id_tp, $id_smt)
    {
        return $this->db->table('kelas_catatan_mapel')
            ->where('id_kelas', $kelas)
            ->where('id_mapel', $mapel)
            ->where('type', '1')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->getResult();
    }

    public function getCatatanMapelSiswa($id_tp, $id_smt, $id_kelas, $id_mapel)
    {
        $id_mapel_val = $this->db->escape($id_mapel);
        return $this->db->table('kelas_siswa a')
            ->select('b.id_siswa, b.nama, b.nis, b.nisn, b.foto, (SELECT COUNT(id_siswa) FROM kelas_catatan_mapel c WHERE c.id_siswa = b.id_siswa AND c.id_mapel = ' . $id_mapel_val . ' AND c.type = \'2\') AS jml_catatan')
            ->join('master_siswa b', 'b.id_siswa=a.id_siswa', 'left')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->get()->getResult();
    }

    public function getAllCatatanMapelSiswa($id_siswa, $id_mapel, $id_tp, $id_smt)
    {
        return $this->db->table('kelas_catatan_mapel')
            ->where('id_siswa', $id_siswa)
            ->where('id_mapel', $id_mapel)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->getResult();
    }

    public function getGuruMapelKelas($id_guru, $tp, $smt)
    {
        return $this->db->table('master_guru a')
            ->select('a.id_guru, a.nama_guru, a.kode_guru, b.mapel_kelas, b.ekstra_kelas, d.nama_kelas')
            ->join('jabatan_guru b', 'a.id_guru=b.id_guru AND b.id_tp=' . (int) $tp . ' AND b.id_smt=' . (int) $smt, 'left')
            ->join('master_kelas d', 'b.id_kelas=d.id_kelas AND d.id_tp=' . (int) $tp . ' AND d.id_smt=' . (int) $smt, 'left')
            ->where('a.id_guru', $id_guru)
            ->get()->getRow();
    }

    public function getJadwalKbm($tp, $smt, $kelas)
    {
        return $this->db->table('kelas_jadwal_kbm')
            ->where("id_tp", $tp)
            ->where("id_smt", $smt)
            ->where("id_kelas", $kelas)
            ->get()->getRow();
    }

    public function getJadwalMapelByMapel($kelas, $mapel, $tp, $smt)
    {
        $builder = $this->db->table('kelas_jadwal_mapel a')
            ->select('a.*, b.nama_mapel, b.kode')
            ->join('master_mapel b', 'a.id_mapel=b.id_mapel', 'left')
            ->where("a.id_tp", $tp)
            ->where("a.id_smt", $smt);

        if ($mapel != null) {
            $builder->where("a.id_mapel", $mapel);
        }

        if (is_array($kelas)) {
            $builder->whereIn('a.id_kelas', $kelas);
        } else {
            $builder->where('a.id_kelas', $kelas);
        }

        return $builder->get()->getResult();
    }

    public function getCatatanKelas($kelas, $id_tp, $id_smt)
    {
        return $this->db->table('kelas_catatan_wali')
            ->where('id_kelas', $kelas)
            ->where('type', '1')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->getResult();
    }

    public function getCatatanSiswa($id_tp, $id_smt, $id_kelas)
    {
        return $this->db->table('kelas_siswa a')
            ->select('b.id_siswa, b.nama, b.nis, b.nisn, b.foto, (SELECT COUNT(id_siswa) FROM kelas_catatan_wali c WHERE c.id_siswa = b.id_siswa AND c.type = \'2\') AS jml_catatan')
            ->join('master_siswa b', 'b.id_siswa=a.id_siswa')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->get()->getResult();
    }

    public function getAllCatatanSiswa($id_siswa, $id_tp, $id_smt)
    {
        return $this->db->table('kelas_catatan_wali')
            ->where('id_siswa', $id_siswa)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->getResult();
    }

    public function getStrukturKelas($kelas)
    {
        return $this->db->table('kelas_struktur')
            ->where('id_kelas', $kelas)
            ->get()->getRow();
    }

    public function dummyStruktur()
    {
        return [
            'id_kelas' => '',
            'ketua' => '',
            'wakil_ketua' => '',
            'sekretaris_1' => '',
            'sekretaris_2' => '',
            'bendahara_1' => '',
            'bendahara_2' => '',
            'sie_ekstrakurikuler' => '',
            'sie_upacara' => '',
            'sie_olahraga' => '',
            'sie_keagamaan' => '',
            'sie_keamanan' => '',
            'sie_ketertiban' => '',
            'sie_kebersihan' => '',
            'sie_keindahan' => '',
            'sie_kesehatan' => '',
            'sie_kekeluargaan' => '',
            'sie_humas' => ''
        ];
    }

    public function loadJadwalSiswaHariIni($id_tp, $id_smt, $id_kelas, $id_hari)
    {
        return $this->db->table('kelas_jadwal_mapel a')
            ->select('a.*, b.kode, b.nama_mapel')
            ->join('master_mapel b', 'b.id_mapel=a.id_mapel', 'left')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->where('a.id_hari', $id_hari)
            ->get()->getResult();
    }

    public function loadJadwalSiswaSeminggu($id_tp, $id_smt, $id_kelas)
    {
        $result = $this->db->table('kelas_jadwal_mapel a')
            ->select('a.*, b.kode, b.nama_mapel')
            ->join('master_mapel b', 'b.id_mapel=a.id_mapel', 'left')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_hari][$row->jam_ke] = $row;
        }
        return $ret;
    }

    public function getStatusMateriSiswaByJadwal($id_siswa, $arr_id_kjm)
    {
        $result = $this->db->table('log_materi')
            ->whereIn("id_materi", $arr_id_kjm)
            ->where("id_siswa", $id_siswa)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_materi] = $row;
        }
        return $ret;
    }

    public function getCatatanMapelBySiswa($id_kelas, $id_tp, $id_smt)
    {
        return $this->db->table('kelas_catatan_mapel a')
            ->select('a.*, b.nama_guru, b.nip, b.foto')
            ->join('master_guru b', 'b.id_guru=a.id_guru')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->getResult();
    }

    public function getCatatanSiswaBySiswa($id_kelas, $id_tp, $id_smt)
    {
        return $this->db->table('kelas_catatan_wali a')
            ->select('a.*, c.nama_guru, c.nip, c.foto')
            ->join('jabatan_guru b', 'b.id_kelas=a.id_kelas')
            ->join('master_guru c', 'c.id_guru=b.id_guru')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->getResult();
    }

    public function getCatatanMapelSiswaDetail($id_catatan)
    {
        return $this->db->table('kelas_catatan_mapel a')
            ->select('a.*, b.nama_guru, b.nip, b.foto, d.level as jabatan, e.nama_mapel, e.kode')
            ->join('master_guru b', 'b.id_guru=a.id_guru')
            ->join('jabatan_guru c', 'c.id_guru=a.id_guru')
            ->join('level_guru d', 'd.id_level=c.id_jabatan')
            ->join('master_mapel e', 'e.id_mapel=a.id_mapel')
            ->where('a.id_catatan', $id_catatan)
            ->get()->getRow();
    }

    public function getCatatanKelasSiswaDetail($id_catatan)
    {
        return $this->db->table('kelas_catatan_wali a')
            ->select('a.*, c.nama_guru, c.nip, c.foto, e.level as jabatan, f.nama_kelas')
            ->join('jabatan_guru b', 'b.id_kelas=a.id_kelas')
            ->join('master_guru c', 'c.id_guru=b.id_guru')
            ->join('level_guru e', 'e.id_level=b.id_jabatan')
            ->join('master_kelas f', 'f.id_kelas=a.id_kelas')
            ->where('a.id_catatan', $id_catatan)
            ->get()->getRow();
    }

    public function getReading($table, $id_catatan)
    {
        return $this->db->table($table)
            ->select('reading, type, readed')
            ->where('id_catatan', $id_catatan)
            ->get()->getRow();
    }

    public function getKelasList($tp, $smt)
    {
        // Get kelas list with wali kelas from jabatan_guru (id_jabatan = 4)
        // Use MIN() for joined columns to be compatible with MySQL strict mode
        return $this->db->table('master_kelas a')
            ->select('a.id_kelas, a.nama_kelas, a.kode_kelas, a.level_id, a.jurusan_id, a.id_tp, a.id_smt, a.jumlah_siswa, a.guru_id')
            ->select('MIN(c.nama_jurusan) as nama_jurusan')
            ->select('MIN(g.nama_guru) as nama_guru')
            ->join('master_jurusan c', 'a.jurusan_id=c.id_jurusan', 'left')
            // Join wali kelas from jabatan_guru where id_jabatan = 4
            ->join('jabatan_guru jg', "jg.id_kelas=a.id_kelas AND jg.id_jabatan='4' AND jg.id_tp=a.id_tp AND jg.id_smt=a.id_smt", 'left')
            ->join('master_guru g', 'g.id_guru=jg.id_guru', 'left')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->groupBy('a.id_kelas, a.nama_kelas, a.kode_kelas, a.level_id, a.jurusan_id, a.id_tp, a.id_smt, a.jumlah_siswa, a.guru_id')
            ->orderBy('a.level_id', 'ASC')
            ->get()->getResult();
    }

    /**
     * Get all kelas from all TP/SMT (for building nested structure)
     */
    public function getAllKelas()
    {
        return $this->db->table('master_kelas a')
            ->select('a.*, b.nama_guru, c.nama_jurusan')
            ->join('master_guru b', 'a.guru_id=b.id_guru', 'left')
            ->join('master_jurusan c', 'a.jurusan_id=c.id_jurusan', 'left')
            ->orderBy('a.id_tp', 'DESC')
            ->orderBy('a.id_smt', 'DESC')
            ->orderBy('a.level_id', 'ASC')
            ->get()->getResult();
    }

    public function get_jurusan()
    {
        return $this->db->table('master_jurusan')->get()->getResult();
    }

    public function getJurusanById($id)
    {
        return $this->db->table('master_jurusan')->where('id_jurusan', $id)->get()->getRow();
    }

    public function getLevel($jenjang)
    {
        return $this->db->table('level_guru')->where('jenjang', $jenjang)->get()->getResult();
    }

    public function get_guru()
    {
        return $this->db->table('master_guru')->get()->getResult();
    }

    public function get_one($id, $id_tp = null, $id_smt = null)
    {
        // Get kelas data first - using level_kelas table for class level (not level_guru)
        $builder = $this->db->table('master_kelas a')
            ->select('a.*, c.nama_jurusan, d.level')
            ->join('master_jurusan c', 'a.jurusan_id=c.id_jurusan', 'left')
            ->join('level_kelas d', 'a.level_id=d.id_level', 'left')
            ->where('a.id_kelas', $id);

        if ($id_tp != null)
            $builder->where('a.id_tp', $id_tp);
        if ($id_smt != null)
            $builder->where('a.id_smt', $id_smt);

        $kelas = $builder->get()->getRow();

        // Get wali kelas from jabatan_guru table (id_jabatan = 4 means Wali Kelas)
        if ($kelas) {
            $waliKelas = $this->db->table('jabatan_guru jg')
                ->select('g.id_guru, g.nama_guru')
                ->join('master_guru g', 'g.id_guru = jg.id_guru')
                ->where('jg.id_kelas', $id)
                ->where('jg.id_jabatan', '4') // 4 = Wali Kelas
                ->where('jg.id_tp', $kelas->id_tp)
                ->where('jg.id_smt', $kelas->id_smt)
                ->get()->getRow();

            $kelas->nama_guru = $waliKelas->nama_guru ?? null;
            $kelas->guru_id = $waliKelas->id_guru ?? null;
        }

        return $kelas;
    }

    public function get_siswa_kelas($id, $tp, $smt)
    {
        return $this->db->table('kelas_siswa a')
            ->select('a.*, b.id_siswa, b.nama, b.nis, b.nisn, b.foto, b.jenis_kelamin')
            ->join('master_siswa b', 'a.id_siswa=b.id_siswa')
            ->where('a.id_kelas', $id)
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('b.nama', 'ASC')
            ->get()->getResult();
    }

    public function getKelasSiswa($id, $tp, $smt)
    {
        return $this->get_siswa_kelas($id, $tp, $smt);
    }

    public function getNamaKelasById($arr_id)
    {
        if (empty($arr_id))
            return [];
        $result = $this->db->table('master_kelas')
            ->whereIn('id_kelas', $arr_id)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kelas] = $row->nama_kelas;
        }
        return $ret;
    }

    public function getNamaKelasByKode($arr_kode)
    {
        if (empty($arr_kode))
            return [];
        $result = $this->db->table('master_kelas')
            ->whereIn('kode_kelas', $arr_kode)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kelas] = $row->nama_kelas;
        }
        return $ret;
    }

    public function getNamaKelasByNama($id_tp, $id_smt)
    {
        $result = $this->db->table('master_kelas')
            ->select('id_kelas, nama_kelas')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kelas] = $row->nama_kelas;
        }
        return $ret;
    }

    public function get_siswa_kelas_baru($id_tp, $id_smt)
    {
        return $this->db->table('kelas_siswa a')
            ->select('a.id_siswa, b.nama_kelas')
            ->join('master_kelas b', 'a.id_kelas=b.id_kelas')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->get()->getResult();
    }

    public function getKelasEkskul($kelas, $tp, $smt)
    {
        return $this->db->table('kelas_ekstra')
            ->where("id_kelas", $kelas)
            ->where("id_tp", $tp)
            ->where("id_smt", $smt)
            ->get()->getResult();
    }

    /**
     * Get available wali kelas (teachers with jabatan wali kelas)
     */
    public function getWaliKelas($tp, $smt)
    {
        $result = $this->db->table('jabatan_guru a')
            ->select('a.id_guru, b.nama_guru')
            ->join('master_guru b', 'b.id_guru=a.id_guru', 'left')
            ->where('a.id_jabatan', '4') // 4 = Wali Kelas
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->get()->getResult();

        $ret = ['' => 'Pilih Wali Kelas :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_guru] = $row->nama_guru;
            }
        }
        return $ret;
    }

    /**
     * Get status siswa kelas (for removing from class)
     */
    public function get_status_siswa_kelas($id_kelas, $id_tp, $id_smt)
    {
        $result = $this->db->table('kelas_siswa')
            ->where('id_kelas', $id_kelas)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->getResult();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_siswa] = $row;
        }
        return $ret;
    }
}
