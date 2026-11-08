<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom catatan_admin sudah dipakai layar admin dan mobile guru, tetapi selama
 * ini hanya diciptakan oleh Admin\Lembur::ensureCatatanAdmin() saat halaman
 * pertama kali dibuka — jadi strukturnya bergantung pada hak ALTER TABLE
 * akun web dan pada siapa yang membuka halaman itu lebih dulu.
 */
class AddLemburCatatanAdmin extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('presensi_lembur')
            && !$this->db->fieldExists('catatan_admin', 'presensi_lembur')) {
            $this->forge->addColumn('presensi_lembur', [
                'catatan_admin' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'after'   => 'keterangan',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('presensi_lembur')
            && $this->db->fieldExists('catatan_admin', 'presensi_lembur')) {
            $this->forge->dropColumn('presensi_lembur', 'catatan_admin');
        }
    }
}
