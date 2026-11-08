<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbsensiQrLogTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'qr_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11],
            'id_guru' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'scanned_at' => ['type' => 'DATETIME'],
            'lat' => ['type' => 'DOUBLE', 'null' => true],
            'lng' => ['type' => 'DOUBLE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('qr_id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('absensi_qr_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('absensi_qr_log', true);
    }
}
