<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKeteranganToAbsensiHarian extends Migration
{
    public function up()
    {
        $fields = [
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'status_masuk'
            ]
        ];

        if (!$this->db->fieldExists('keterangan', 'absensi_harian')) {
            $this->forge->addColumn('absensi_harian', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('keterangan', 'absensi_harian')) {
            $this->forge->dropColumn('absensi_harian', 'keterangan');
        }
    }
}
