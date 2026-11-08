<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk tabel siswa_beasiswa
 * Menyimpan data beasiswa peserta didik
 */
class CreateSiswaBeasiswaTable extends Migration
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
            'id_siswa' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'jenis' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
                'comment' => '01=Anak berprestasi, 02=Anak Miskin, 03=Pendidikan, 04=Unggulan',
            ],
            'keterangan' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
                'null' => true,
            ],
            'tahun_mulai' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            'tahun_selesai' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            // Timestamps
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
        $this->forge->addKey('id_siswa');
        $this->forge->createTable('siswa_beasiswa', true);
    }

    public function down()
    {
        $this->forge->dropTable('siswa_beasiswa', true);
    }
}
