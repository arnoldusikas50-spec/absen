<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tandai satu lokasi sebagai "lokasi utama" (kantor pusat).
 *
 * Absen yang diinput manual oleh admin tidak punya koordinat GPS, sehingga
 * kolom lokasi_masuk_id / lokasi_pulang_id terisi NULL dan rekap menampilkan
 * "Lokasi Luar Sekolah". Dengan flag ini, baris absen manual otomatis
 * mengaitkan diri ke lokasi utama beserta koordinatnya.
 *
 * Tidak ada foreign key, jadi kolom ini murni penanda (0/1).
 */
class AddIsUtamaToAbsensiLokasi extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('absensi_lokasi')) {
            return;
        }

        if (!$this->db->fieldExists('is_utama', 'absensi_lokasi')) {
            $this->forge->addColumn('absensi_lokasi', [
                'is_utama' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'default'    => 0,
                    'after'      => 'nama_lokasi',
                    'comment'    => '1 = lokasi utama dipakai untuk absen manual admin',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_lokasi') && $this->db->fieldExists('is_utama', 'absensi_lokasi')) {
            $this->forge->dropColumn('absensi_lokasi', 'is_utama');
        }
    }
}
