<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Token kiosk opsional untuk scanner publik (/scanner).
 * Kosong = nonaktif (kompatibel mundur). Bila diisi, request publik wajib
 * menyertakan token via header X-Kiosk-Token atau field `k`.
 */
class AddKioskTokenToAbsensiSetting extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('absensi_setting')) {
            return;
        }
        if (!$this->db->fieldExists('qr_kiosk_token', 'absensi_setting')) {
            $this->forge->addColumn('absensi_setting', [
                'qr_kiosk_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('absensi_setting')) {
            return;
        }
        try {
            if ($this->db->fieldExists('qr_kiosk_token', 'absensi_setting')) {
                $this->forge->dropColumn('absensi_setting', 'qr_kiosk_token');
            }
        } catch (\Throwable $e) {
        }
    }
}
