<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPpdbStatusToMasterSiswa extends Migration
{
    public function up()
    {
        // Check if ppdb_status column already exists
        if (!$this->db->fieldExists('ppdb_status', 'master_siswa')) {
            $this->forge->addColumn('master_siswa', [
                'ppdb_status' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0, // 0=Menunggu, 1=Diterima, 2=Ditolak
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('ppdb_status', 'master_siswa')) {
            $this->forge->dropColumn('master_siswa', 'ppdb_status');
        }
    }
}
