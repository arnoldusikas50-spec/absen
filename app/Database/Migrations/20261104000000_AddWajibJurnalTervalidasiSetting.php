<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWajibJurnalTervalidasiSetting extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('absensi_setting')) {
            return;
        }

        $kolom = [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'null' => true,
        ];
        // 'after' hanya dipakai bila kolom induknya memang ada, supaya ADD COLUMN
        // tidak gagal di instalasi yang kolomnya masih belum dibuat.
        if ($this->db->fieldExists('wajib_jurnal_sebelum_pulang', 'absensi_setting')) {
            $kolom['after'] = 'wajib_jurnal_sebelum_pulang';
        }

        if (!$this->db->fieldExists('wajib_jurnal_tervalidasi_sebelum_pulang', 'absensi_setting')) {
            $this->forge->addColumn('absensi_setting', [
                'wajib_jurnal_tervalidasi_sebelum_pulang' => $kolom,
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_setting') && $this->db->fieldExists('wajib_jurnal_tervalidasi_sebelum_pulang', 'absensi_setting')) {
            $this->forge->dropColumn('absensi_setting', 'wajib_jurnal_tervalidasi_sebelum_pulang');
        }
    }
}
