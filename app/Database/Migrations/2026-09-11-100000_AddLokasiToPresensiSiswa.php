<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Audit lokasi scan QR siswa: simpan koordinat perangkat kiosk/pemindai
 * agar setiap presensi bisa diverifikasi berada di area sekolah.
 */
class AddLokasiToPresensiSiswa extends Migration
{
    private array $columns = [
        'lat_masuk'     => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
        'lng_masuk'     => ['type' => 'DECIMAL', 'constraint' => '11,7', 'null' => true],
        'akurasi_masuk' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'comment' => 'Akurasi GPS meter'],
        'lokasi_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'comment' => 'FK absensi_lokasi terdekat'],
        'lat_keluar'    => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
        'lng_keluar'    => ['type' => 'DECIMAL', 'constraint' => '11,7', 'null' => true],
    ];

    public function up()
    {
        if (!$this->db->tableExists('presensi_siswa')) {
            return;
        }

        $add = [];
        foreach ($this->columns as $col => $def) {
            if (!$this->db->fieldExists($col, 'presensi_siswa')) {
                $add[$col] = $def;
            }
        }
        if (!empty($add)) {
            $this->forge->addColumn('presensi_siswa', $add);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('presensi_siswa')) {
            return;
        }

        foreach (array_keys($this->columns) as $col) {
            if ($this->db->fieldExists($col, 'presensi_siswa')) {
                $this->forge->dropColumn('presensi_siswa', $col);
            }
        }
    }
}
