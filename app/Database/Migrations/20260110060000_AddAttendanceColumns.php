<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add additional columns for attendance tracking
 */
class AddAttendanceColumns extends Migration
{
    public function up()
    {
        // Add columns if they don't exist
        $forge = \Config\Database::forge();
        $db = \Config\Database::connect();

        // Check and add telat_menit
        if (!$db->fieldExists('telat_menit', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'telat_menit' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => true,
                    'after' => 'status_masuk'
                ]
            ]);
        }

        // Check and add gps_accuracy
        if (!$db->fieldExists('gps_accuracy', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'gps_accuracy' => [
                    'type' => 'FLOAT',
                    'null' => true,
                    'after' => 'device_info'
                ]
            ]);
        }

        // Check and add is_mock_location
        if (!$db->fieldExists('is_mock_location', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'is_mock_location' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => true,
                    'after' => 'gps_accuracy'
                ]
            ]);
        }

        // Check and add pulang_cepat_menit
        if (!$db->fieldExists('pulang_cepat_menit', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'pulang_cepat_menit' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => true,
                    'after' => 'status_pulang'
                ]
            ]);
        }


        // Check and add status_pulang
        if (!$db->fieldExists('status_pulang', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'status_pulang' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'after' => 'jam_pulang'
                ]
            ]);
        }

        // --- UPDATE FOR ABSENSI_SETTING ---
        // Check and add foto_wajib
        if (!$db->fieldExists('foto_wajib', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'foto_wajib' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => true,
                    'after' => 'radius_meter'
                ]
            ]);
        }

        // Check and add libur_kalender
        if (!$db->fieldExists('libur_kalender', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'libur_kalender' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => true,
                    'after' => 'foto_wajib'
                ]
            ]);
        }

        // Check and add updated_at
        if (!$db->fieldExists('updated_at', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'libur_kalender'
                ]
            ]);
        }

    }

    public function down()
    {
        $forge = \Config\Database::forge();

        $forge->dropColumn('absensi_harian', 'telat_menit');
        $forge->dropColumn('absensi_harian', 'gps_accuracy');
        $forge->dropColumn('absensi_harian', 'is_mock_location');
        $forge->dropColumn('absensi_harian', 'pulang_cepat_menit');
        $forge->dropColumn('absensi_harian', 'status_pulang');
    }
}
