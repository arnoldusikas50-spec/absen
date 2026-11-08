<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNotificationSettings extends Migration
{
    public function up()
    {
        $fields = [
            'telegram_bot_token' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'radius_meter'
            ],
            'telegram_chat_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'after' => 'telegram_bot_token'
            ],
            'wa_gateway_url' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'default' => 'https://api.fonnte.com/send',
                'null' => true,
                'after' => 'telegram_chat_id'
            ],
            'wa_token' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'wa_gateway_url'
            ],
            'wa_target_number' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'comment' => 'Nomor WA tujuan laporan (contoh: 081234...)',
                'after' => 'wa_token'
            ],
        ];

        // Check if table exists first to avoid error
        if ($this->db->tableExists('absensi_setting')) {
            // Check if column exists individually to avoid duplicate column error on rerun
            foreach ($fields as $columnName => $definition) {
                if (!$this->db->fieldExists($columnName, 'absensi_setting')) {
                    $this->forge->addColumn('absensi_setting', [$columnName => $definition]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_setting')) {
            $this->forge->dropColumn('absensi_setting', [
                'telegram_bot_token',
                'telegram_chat_id',
                'wa_gateway_url',
                'wa_token',
                'wa_target_number'
            ]);
        }
    }
}
