<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mapping chat Telegram per user + preferensi notifikasi personal.
 * Dipakai bot dua arah (/start tautan, /denda, /rekap) dan kiriman personal
 * (denda, surat teguran) di samping broadcast grup.
 */
class CreateUserNotifikasiTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('user_notifikasi')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'telegram_chat_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'prefs' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON preferensi: {"denda":true,"teguran":true,"alpha":true,"kehadiran":true}',
            ],
            'link_token' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'null'       => true,
            ],
            'link_expires' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('user_notifikasi');
    }

    public function down()
    {
        if ($this->db->tableExists('user_notifikasi')) {
            $this->forge->dropTable('user_notifikasi');
        }
    }
}
