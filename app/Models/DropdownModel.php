<?php

namespace App\Models;

use CodeIgniter\Model;

class DropdownModel extends Model
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

    public function getAllSesi()
    {
        return [];
    }

    public function getAllRuang()
    {
        return [];
    }

    public function getDataKelompokMapel()
    {
        $result = $this->db->table('master_kelompok_mapel')->orderBy('kode_kel_mapel')->get()->getResult();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kel_mapel] = $row->nama_kel_mapel;
        }
        return $ret;
    }

    public function getAllMapel()
    {
        $result = $this->db->table('master_mapel')
            ->select('id_mapel, nama_mapel')
            ->where('status', '1')
            ->orderBy('urutan_tampil')
            ->get()->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_mapel] = $row->nama_mapel;
            }
        }
        return $ret;
    }

    public function getAllKelas($tp, $smt, $level = null)
    {
        $builder = $this->db->table('master_kelas')
            ->select('id_kelas, nama_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt);

        if ($level != null) {
            $builder->where('level_id', $level);
        }

        $result = $builder->orderBy('level_id', 'ASC')
            ->orderBy('nama_kelas', 'ASC')
            ->get()->getResult();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_kelas] = $row->nama_kelas;
            }
        }
        return $ret;
    }

    public function getAllKelasByArrayId($tp, $smt, $arrId)
    {
        if (empty($arrId))
            return [];

        $result = $this->db->table('master_kelas')
            ->select('id_kelas, nama_kelas')
            ->where('id_tp', $tp)
            ->whereIn('id_kelas', $arrId)
            ->get()->getResult();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_kelas] = $row->nama_kelas;
            }
        }
        return $ret;
    }

    public function getAllGuru()
    {
        $result = $this->db->table('master_guru a')
            ->select('a.id_guru, a.nama_guru')
            ->join('users e', 'a.username=e.username')
            ->get()->getResult();

        $ret = ['0' => 'Pilih Guru :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_guru] = $row->nama_guru;
            }
        }
        return $ret;
    }

    public function getAllLevelGuru()
    {
        $result = $this->db->table('level_guru')->get()->getResult();
        $ret = ['' => 'Pilih Jabatan :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_level] = $row->level;
            }
        }
        return $ret;
    }

    public function getAllEkskul()
    {
        $result = $this->db->table('master_ekstra')->orderBy('nama_ekstra')->get()->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_ekstra] = $row->nama_ekstra;
            }
        }
        return $ret;
    }

    public function getAllKodeEkskul()
    {
        $result = $this->db->table('master_ekstra')->orderBy('nama_ekstra')->get()->getResult();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_ekstra] = $row->kode_ekstra;
            }
        }
        return $ret;
    }

    public function getDataJurusan()
    {
        $result = $this->db->table('master_jurusan')->get()->getResult();
        $ret = ['' => 'Pilih Jurusan :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_jurusan] = $row->nama_jurusan;
            }
        }
        return $ret;
    }

    public function getAllLevelKelas($jenjang)
    {
        if ($jenjang == '1') {
            $levels = ['' => 'Pilih Level :', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'];
        } else if ($jenjang == '2') {
            $levels = ['' => 'Pilih Level :', '7' => '7', '8' => '8', '9' => '9'];
        } else {
            $levels = ['' => 'Pilih Level :', '10' => '10', '11' => '11', '12' => '12'];
        }
        return $levels;
    }

    public function getNamaKelasById($tp, $smt, $id_kelas)
    {
        $result = $this->db->table('master_kelas')
            ->select('nama_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_kelas', $id_kelas)
            ->get()->getRow();

        return $result ? $result->nama_kelas : '';
    }
    public function getAllPanitiaPengawas()
    {
        return $this->db->table('master_panitia')
            ->select('id_panitia, nama_panitia, ruang')
            ->get()
            ->getResult();
    }
}
