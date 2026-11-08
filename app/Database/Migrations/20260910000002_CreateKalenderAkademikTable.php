<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKalenderAkademikTable extends Migration
{
    public function up()
    {
        // Buat tabel jika belum ada
        if ($this->db->tableExists('kalender_akademik')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'judul' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tgl_mulai' => [
                'type' => 'DATE',
            ],
            'tgl_selesai' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'tipe' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'EVENT',
                // Nilai valid: EVENT, UJIAN, LIBUR, RAPAT, DINAS, LAINNYA
            ],
            'warna' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '#3b82f6',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('tgl_mulai');
        $this->forge->addKey('tgl_selesai');
        $this->forge->addKey('tipe');
        $this->forge->createTable('kalender_akademik', true);
    }

    public function down()
    {
        $this->forge->dropTable('kalender_akademik', true);
    }
}
