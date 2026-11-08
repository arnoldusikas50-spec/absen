<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWfhColumns extends Migration
{
    public function up()
    {
        // Add is_wfh to absensi_harian
        if ($this->db->tableExists('absensi_harian') && !$this->db->fieldExists('is_wfh', 'absensi_harian')) {
            $this->forge->addColumn('absensi_harian', [
                'is_wfh' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => true,
                    'after' => 'keterangan_denda'
                ]
            ]);
        }

        // Add WFH settings to absensi_setting
        if ($this->db->tableExists('absensi_setting')) {
            if (!$this->db->fieldExists('wfh_enabled', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'wfh_enabled' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'null' => true,
                        'after' => 'notif_kehadiran_rendah'
                    ]
                ]);
            }
            if (!$this->db->fieldExists('wfh_mulai', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'wfh_mulai' => [
                        'type' => 'TIME',
                        'default' => '08:00:00',
                        'null' => true,
                        'after' => 'wfh_enabled'
                    ]
                ]);
            }
            if (!$this->db->fieldExists('wfh_selesai', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'wfh_selesai' => [
                        'type' => 'TIME',
                        'default' => '16:00:00',
                        'null' => true,
                        'after' => 'wfh_mulai'
                    ]
                ]);
            }
            if (!$this->db->fieldExists('wfh_max_per_bulan', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'wfh_max_per_bulan' => [
                        'type' => 'INT',
                        'constraint' => 3,
                        'default' => 5,
                        'null' => true,
                        'after' => 'wfh_selesai'
                    ]
                ]);
            }
            if (!$this->db->fieldExists('wfh_wajib_wajah', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'wfh_wajib_wajah' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 1,
                        'null' => true,
                        'after' => 'wfh_max_per_bulan'
                    ]
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_harian') && $this->db->fieldExists('is_wfh', 'absensi_harian')) {
            $this->forge->dropColumn('absensi_harian', 'is_wfh');
        }
        if ($this->db->tableExists('absensi_setting')) {
            foreach (['wfh_enabled', 'wfh_mulai', 'wfh_selesai', 'wfh_max_per_bulan', 'wfh_wajib_wajah'] as $col) {
                if ($this->db->fieldExists($col, 'absensi_setting')) {
                    $this->forge->dropColumn('absensi_setting', $col);
                }
            }
        }
    }
}
