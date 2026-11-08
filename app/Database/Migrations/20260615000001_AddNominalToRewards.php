<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNominalToRewards extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Add nominal (voucher value in IDR) to rewards
        try {
            $this->forge->addColumn('rewards', [
                'nominal' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                    'after' => 'poin_dibutuhkan',
                    'null' => false,
                ],
            ]);
        } catch (\Throwable $e) {
            // Column may already exist
        }

        // Add reward_redemption_id to absensi_pembayaran_denda
        if ($db->tableExists('absensi_pembayaran_denda')) {
            try {
                $this->forge->addColumn('absensi_pembayaran_denda', [
                    'reward_redemption_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'keterangan',
                    ],
                ]);
            } catch (\Throwable $e) {
                // Column may already exist
            }
        }
    }

    public function down()
    {
        try {
            $this->forge->dropColumn('rewards', 'nominal');
        } catch (\Throwable $e) {}

        $db = \Config\Database::connect();
        if ($db->tableExists('absensi_pembayaran_denda')) {
            try {
                $this->forge->dropColumn('absensi_pembayaran_denda', 'reward_redemption_id');
            } catch (\Throwable $e) {}
        }
    }
}
