<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk tabel siswa_ortu
 * Menyimpan data orang tua (Ayah, Ibu, Wali) peserta didik
 */
class CreateSiswaOrtuTable extends Migration
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
            // Data Ayah
            'nama_ayah' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'nik_ayah' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
            ],
            'tahun_lahir_ayah' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            'pendidikan_ayah' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'pekerjaan_ayah' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'penghasilan_ayah' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'berkebutuhan_khusus_ayah' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Data Ibu
            'nama_ibu' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'nik_ibu' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
            ],
            'tahun_lahir_ibu' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            'pendidikan_ibu' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'pekerjaan_ibu' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'penghasilan_ibu' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'berkebutuhan_khusus_ibu' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Data Wali
            'nama_wali' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'nik_wali' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
            ],
            'tahun_lahir_wali' => [
                'type' => 'YEAR',
                'null' => true,
            ],
            'pendidikan_wali' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'pekerjaan_wali' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'penghasilan_wali' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            // Kontak
            'no_telepon_rumah' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'no_hp_ortu' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'email_ortu' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
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
        $this->forge->createTable('siswa_ortu', true);
    }

    public function down()
    {
        $this->forge->dropTable('siswa_ortu', true);
    }
}
