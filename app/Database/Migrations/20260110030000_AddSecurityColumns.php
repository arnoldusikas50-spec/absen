<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSecurityColumns extends Migration
{
    public function up()
    {
        // Add is_mock_location column to absensi_harian
        $db = \Config\Database::connect();
        if (!$db->fieldExists('is_mock_location', 'absensi_harian')) {
            $this->forge->addColumn('absensi_harian', [
                'is_mock_location' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'gps_accuracy',
                    'comment' => 'Flag for suspected mock/fake GPS location'
                ]
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('absensi_harian', 'is_mock_location');
    }
}
