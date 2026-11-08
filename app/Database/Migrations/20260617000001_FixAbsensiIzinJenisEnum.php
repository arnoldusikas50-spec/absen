<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fix: Tambah 'sakit' ke ENUM jenis di tabel absensi_izin
 * Sebelumnya hanya ['ijin','cuti'] sehingga izin sakit tidak bisa disimpan.
 */
class FixAbsensiIzinJenisEnum extends Migration
{
    public function up()
    {
        // Ubah kolom jenis dari ENUM('ijin','cuti') menjadi VARCHAR
        // agar lebih fleksibel dan mendukung: ijin, sakit, cuti, dinas
        $this->db->query("
            ALTER TABLE absensi_izin 
            MODIFY COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'ijin'
        ");

        // Normalkan data lama yang mungkin sudah ada (huruf besar/kecil)
        $this->db->query("UPDATE absensi_izin SET jenis = LOWER(jenis)");
    }

    public function down()
    {
        // Kembalikan ke ENUM (hanya bisa jika tidak ada data 'sakit' atau 'dinas')
        $this->db->query("
            ALTER TABLE absensi_izin 
            MODIFY COLUMN jenis ENUM('ijin','cuti') NOT NULL
        ");
    }
}
