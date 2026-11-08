<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jam operasional scanner QR siswa: scan masuk di luar jendela ditolak.
 * Default aktif 05:30–18:00 WITA (aman secara bawaan).
 */
class AddQrScanWindowToAbsensiSetting extends Migration
{
    private array $columns = [
        'qr_scan_aktif' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 1,
        ],
        'qr_scan_mulai' => [
            'type'    => 'TIME',
            'null'    => true,
            'default' => '05:30:00',
        ],
        'qr_scan_selesai' => [
            'type'    => 'TIME',
            'null'    => true,
            'default' => '18:00:00',
        ],
    ];

    public function up()
    {
        if (!$this->db->tableExists('absensi_setting')) {
            return;
        }

        $add = [];
        foreach ($this->columns as $col => $def) {
            if (!$this->db->fieldExists($col, 'absensi_setting')) {
                $add[$col] = $def;
            }
        }
        if (!empty($add)) {
            $this->forge->addColumn('absensi_setting', $add);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('absensi_setting')) {
            return;
        }

        foreach (array_keys($this->columns) as $col) {
            if ($this->db->fieldExists($col, 'absensi_setting')) {
                $this->forge->dropColumn('absensi_setting', $col);
            }
        }
    }
}
