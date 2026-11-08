<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAbsensiTables extends Migration
{
    public function up()
    {
        // Add columns to absensi_harian for Audit & Precision
        $db = \Config\Database::connect();

        $fields = [
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'after' => 'foto_pulang'
            ],
            'device_info' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'ip_address'
            ],
            'telat_menit' => [
                'type' => 'INT',
                'default' => 0,
                'after' => 'status_masuk'
            ],
            'pulang_cepat_menit' => [
                'type' => 'INT',
                'default' => 0,
                'after' => 'status_pulang'
            ]
        ];

        foreach ($fields as $field => $def) {
            if (!$db->fieldExists($field, 'absensi_harian')) {
                $this->forge->addColumn('absensi_harian', [$field => $def]);
            }
        }

        // Add column to absensi_izin
        if (!$db->fieldExists('bukti', 'absensi_izin')) {
            $this->forge->addColumn('absensi_izin', [
                'bukti' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'keterangan'
                ]
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('absensi_harian', ['ip_address', 'device_info', 'telat_menit', 'pulang_cepat_menit']);
        $this->forge->dropColumn('absensi_izin', 'bukti');
    }
}
