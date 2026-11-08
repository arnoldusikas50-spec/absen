<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKalenderAkademikTableOld extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tgl_mulai' => ['type' => 'DATE', 'null' => false],
            'tgl_selesai' => ['type' => 'DATE', 'null' => true],
            'judul' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'tipe' => ["type" => "ENUM('LIBUR','UJIAN','EVENT','RAPAT','DINAS','LAINNYA')", 'default' => 'EVENT'],
            'warna' => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#3b82f6'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tgl_mulai');
        $this->forge->addKey('tipe');
        $this->forge->createTable('kalender_akademik', true);
    }

    public function down()
    {
        $this->forge->dropTable('kalender_akademik');
    }
}
