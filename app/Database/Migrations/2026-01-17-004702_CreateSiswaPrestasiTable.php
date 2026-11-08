<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk tabel siswa_prestasi
 * Menyimpan data prestasi peserta didik
 */
class CreateSiswaPrestasiTable extends Migration
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
                'comment' => '1=Sains, 2=Seni, 3=Olahraga, 4=Lain-lain',
            ],
            'tingkat' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
                'comment' => '1=Sekolah, 2=Kecamatan, 3=Kabupaten, 4=Provinsi, 5=Nasional, 6=Internasional',
            ],
            'nama' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => true,
                'comment' => 'Nama prestasi/perlombaan',
            ],
            'tahun' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            'penyelenggara' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'peringkat' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Peringkat/juara',
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
        $this->forge->createTable('siswa_prestasi', true);
    }

    public function down()
    {
        $this->forge->dropTable('siswa_prestasi', true);
    }
}
