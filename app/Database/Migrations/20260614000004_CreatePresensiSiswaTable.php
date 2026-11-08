<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresensiSiswaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_kelas' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_siswa' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_mapel' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'id_guru' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'tgl' => ['type' => 'DATE', 'null' => false],
            'jam' => ['type' => 'TIME', 'null' => true],
            'status' => ['type' => "ENUM('HADIR','SAKIT','IZIN','ALPA','TANPA_KETERANGAN')", 'default' => 'TANPA_KETERANGAN'],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['id_kelas', 'id_siswa', 'tgl'], false, true, 'unik_presensi_siswa');
        $this->forge->createTable('presensi_siswa', true);
    }

    public function down()
    {
        $this->forge->dropTable('presensi_siswa');
    }
}
