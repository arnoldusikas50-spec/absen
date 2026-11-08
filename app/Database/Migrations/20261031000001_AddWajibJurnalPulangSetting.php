<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWajibJurnalPulangSetting extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('absensi_setting')) {
            return;
        }

        if (!$this->db->fieldExists('wajib_jurnal_sebelum_pulang', 'absensi_setting')) {
            $this->forge->addColumn('absensi_setting', [
                'wajib_jurnal_sebelum_pulang' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => true,
                    'after' => 'wfh_wajib_wajah',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_setting') && $this->db->fieldExists('wajib_jurnal_sebelum_pulang', 'absensi_setting')) {
            $this->forge->dropColumn('absensi_setting', 'wajib_jurnal_sebelum_pulang');
        }
    }
}
