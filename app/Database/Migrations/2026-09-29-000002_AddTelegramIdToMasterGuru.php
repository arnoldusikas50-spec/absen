<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ID Telegram per guru. Dipakai sebagai sumber data utama untuk
 * notifikasi personal (denda, surat teguran, alpha) dan sinkronkan
 * otomatis ke `user_notifikasi.telegram_chat_id` yang dibaca
 * NotificationService::notifyUser().
 */
class AddTelegramIdToMasterGuru extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('master_guru')) {
            return;
        }

        if (!$this->db->fieldExists('telegram_id', 'master_guru')) {
            $this->forge->addColumn('master_guru', [
                'telegram_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'no_hp',
                    'comment'    => 'Telegram chat id guru (numerik, boleh negatif untuk supergroup)',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('master_guru') && $this->db->fieldExists('telegram_id', 'master_guru')) {
            $this->forge->dropColumn('master_guru', 'telegram_id');
        }
    }
}
