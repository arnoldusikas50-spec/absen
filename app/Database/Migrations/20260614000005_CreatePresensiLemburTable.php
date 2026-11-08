<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresensiLemburTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_guru' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'tgl' => ['type' => 'DATE', 'null' => false],
            'jam_mulai' => ['type' => 'TIME', 'null' => false],
            'jam_selesai' => ['type' => 'TIME', 'null' => false],
            'total_jam' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'status' => ["type" => "ENUM('PENDING','APPROVED','REJECTED')", 'default' => 'PENDING'],
            'approved_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('tgl');
        $this->forge->addKey('status');
        $this->forge->createTable('presensi_lembur', true);
    }

    public function down()
    {
        $this->forge->dropTable('presensi_lembur');
    }
}
