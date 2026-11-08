<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiswaBerkasTable extends Migration
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
            // 1. Kartu Keluarga (wajib)
            'file_kk' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 2. Akta Kelahiran atau Surat Keterangan Lahir (wajib)
            'file_akta' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 3. Ijazah atau Surat Keterangan Lulus (wajib)
            'file_ijazah' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 4. Buku Rapor (Semester 1-5) (optional/wajib)
            'file_rapor' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 5. Pas Foto (wajib) - Also stored in master_siswa.foto potentially
            'file_foto' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 6. KTP Orang Tua/Wali (wajib)
            'file_ktp_ortu' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 7. Surat Pernyataan Tanggung Jawab Mutlak (optional based on user prompt, but typically mandatory)
            'file_pernyataan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            // 8. Kartu KIP, KKS, dll (optional)
            'file_kip' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
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
        $this->forge->addKey('id_siswa');
        $this->forge->createTable('siswa_berkas', true);
    }

    public function down()
    {
        $this->forge->dropTable('siswa_berkas');
    }
}
