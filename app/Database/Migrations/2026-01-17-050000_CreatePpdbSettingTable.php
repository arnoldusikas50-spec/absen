<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePpdbSettingTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tahun_ajaran' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'tanggal_buka' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'tanggal_tutup' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'kuota' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 200,
            ],
            'jumlah_kelas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 6,
            ],
            'whatsapp' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'pesan_selamat' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'pesan_tidak_lulus' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('ppdb_setting', true);

        // Insert default data
        $db = \Config\Database::connect();
        $exists = $db->table('ppdb_setting')->countAllResults();
        if ($exists == 0) {
            $db->table('ppdb_setting')->insert([
                'tahun_ajaran' => date('Y') . '/' . (date('Y') + 1),
                'tanggal_buka' => date('Y-01-01'),
                'tanggal_tutup' => date('Y-06-30'),
                'kuota' => 200,
                'jumlah_kelas' => 6,
                'whatsapp' => '08123456789',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ppdb_setting', true);
    }
}
