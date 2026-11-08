<?php

namespace App\Models;

use CodeIgniter\Model;

class NilaiModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function initialize()
    {
        $forge = \Config\Database::forge();

        // 1. Tabel Nilai Proses (Per Tujuan Pembelajaran)
        // Menyimpan nilai Sumatif Lingkup Materi (LM) dan Formatif (Opsional)
        if (!$this->db->tableExists('nilai_proses')) {
            $forge->addField([
                'id_nilai_proses' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'id_siswa' => ['type' => 'INT', 'constraint' => 11],
                'id_mapel' => ['type' => 'INT', 'constraint' => 11],
                'id_tp' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true], // Link ke e_tujuan_pembelajaran
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'id_tahun' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'tipe' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'sumatif_lm'], // sumatif_lm, formatif
                'nilai' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
            $forge->addKey('id_nilai_proses', true);
            $forge->addKey(['id_siswa', 'id_mapel', 'id_tp', 'tipe']);
            $forge->createTable('nilai_proses', true);
        }

        // 2. Tabel Nilai Akhir (Rapor)
        // Menyimpan Nilai Akhir Semester (SAS/PAS) dan Nilai Rapor Jadi
        if (!$this->db->tableExists('nilai_akhir_kurmer')) {
            $forge->addField([
                'id_nilai_akhir' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'id_siswa' => ['type' => 'INT', 'constraint' => 11],
                'id_mapel' => ['type' => 'INT', 'constraint' => 11],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'id_tahun' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'nilai_sas' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0], // Sumatif Akhir Semester
                'nilai_rapor' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0], // Rata-rata (LM + SAS)
                'deskripsi_tinggi' => ['type' => 'TEXT', 'null' => true], // Deskripsi capaian tertinggi
                'deskripsi_rendah' => ['type' => 'TEXT', 'null' => true], // Deskripsi capaian terendah
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
            $forge->addKey('id_nilai_akhir', true);
            $forge->addKey(['id_siswa', 'id_mapel', 'id_tahun', 'id_smt']);
            $forge->createTable('nilai_akhir_kurmer', true);
        }
    }

    // --- Operations ---

    public function getNilaiProses($id_siswa, $id_mapel, $id_tp, $tipe = 'sumatif_lm')
    {
        return $this->db->table('nilai_proses')
            ->where('id_siswa', $id_siswa)
            ->where('id_mapel', $id_mapel)
            ->where('id_tp', $id_tp)
            ->where('tipe', $tipe)
            ->get()->getRow();
    }

    public function getAllNilaiProsesKelas($id_kelas, $id_mapel, $id_tp, $tipe = 'sumatif_lm')
    {
        // Join ke kelas_siswa untuk filter kelas
        return $this->db->table('nilai_proses np')
            ->join('kelas_siswa ks', 'ks.id_siswa = np.id_siswa')
            ->where('ks.id_kelas', $id_kelas)
            ->where('np.id_mapel', $id_mapel)
            ->where('np.id_tp', $id_tp)
            ->where('np.tipe', $tipe)
            ->get()->getResult();
    }

    public function saveNilaiProses($data)
    {
        // Helper untuk upsert (update if exists, insert if not)
        $existing = $this->getNilaiProses($data['id_siswa'], $data['id_mapel'], $data['id_tp'], $data['tipe']);

        if ($existing) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table('nilai_proses')->where('id_nilai_proses', $existing->id_nilai_proses)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->db->table('nilai_proses')->insert($data);
        }
    }

    public function getNilaiAkhir($id_siswa, $id_mapel, $id_tahun, $id_smt)
    {
        return $this->db->table('nilai_akhir_kurmer')
            ->where('id_siswa', $id_siswa)
            ->where('id_mapel', $id_mapel)
            ->where('id_tahun', $id_tahun)
            ->where('id_smt', $id_smt)
            ->get()->getRow();
    }

    public function saveNilaiAkhir($data)
    {
        $existing = $this->getNilaiAkhir($data['id_siswa'], $data['id_mapel'], $data['id_tahun'], $data['id_smt']);

        if ($existing) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table('nilai_akhir_kurmer')->where('id_nilai_akhir', $existing->id_nilai_akhir)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->db->table('nilai_akhir_kurmer')->insert($data);
        }
    }

    // --- Report Card Queries ---

    public function getRaporSiswa($id_siswa, $id_tahun, $id_smt)
    {
        return $this->db->table('nilai_akhir_kurmer na')
            ->select('na.*, m.nama_mapel, m.kode as kode_mapel, kel.kelompok')
            ->join('master_mapel m', 'm.id_mapel = na.id_mapel')
            ->join('master_mapel_kelompok kel', 'm.kelompok = kel.kode_kelompok', 'left') // Optional grouping
            ->where('na.id_siswa', $id_siswa)
            ->where('na.id_tahun', $id_tahun)
            ->where('na.id_smt', $id_smt)
            ->orderBy('m.kelompok', 'ASC') // Group by Kelompok (A, B, C)
            ->orderBy('m.no_urut', 'ASC')
            ->get()->getResult();
    }
}
