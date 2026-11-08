<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEarlyArrivalLateDepartureToAbsensi extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $fields = [
            'datang_awal_menit' => [
                'type' => 'INT',
                'default' => 0,
                'after' => 'telat_menit'
            ],
            'pulang_akhir_menit' => [
                'type' => 'INT',
                'default' => 0,
                'after' => 'pulang_cepat_menit'
            ]
        ];

        foreach ($fields as $field => $def) {
            if (!$db->fieldExists($field, 'absensi_harian')) {
                $this->forge->addColumn('absensi_harian', [$field => $def]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('absensi_harian', ['datang_awal_menit', 'pulang_akhir_menit']);
    }
}
