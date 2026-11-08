<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGpsAccuracyColumn extends Migration
{
    public function up()
    {
        // Add gps_accuracy column to absensi_harian
        $db = \Config\Database::connect();
        if (!$db->fieldExists('gps_accuracy', 'absensi_harian')) {
            $this->forge->addColumn('absensi_harian', [
                'gps_accuracy' => [
                    'type' => 'FLOAT',
                    'null' => true,
                    'after' => 'device_info',
                    'comment' => 'GPS accuracy in meters'
                ]
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('absensi_harian', 'gps_accuracy');
    }
}
