<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChatTables extends Migration
{
    public function up()
    {
        // 1. Chat Rooms Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['private', 'group', 'support'],
                'default' => 'private',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'avatar' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('type');
        $this->forge->createTable('chat_rooms', true);

        // 2. Chat Participants Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'room_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'user_type' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'guru', 'panitia', 'siswa'],
                'default' => 'guru',
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['owner', 'admin', 'member'],
                'default' => 'member',
            ],
            'last_read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_muted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'joined_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['room_id', 'user_id', 'user_type']);
        $this->forge->createTable('chat_participants', true);

        // 3. Chat Messages Table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'room_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'sender_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true, // null for system/AI messages
            ],
            'sender_type' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'guru', 'panitia', 'siswa', 'system', 'ai'],
                'default' => 'guru',
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'message_type' => [
                'type' => 'ENUM',
                'constraint' => ['text', 'image', 'file', 'system', 'ai'],
                'default' => 'text',
            ],
            'attachment' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'attachment_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'reply_to' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'is_edited' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_deleted' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('room_id');
        $this->forge->addKey('sender_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('chat_messages', true);

        // 4. Chat Read Receipts (optional, for "seen" feature)
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'message_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'user_type' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'guru', 'panitia', 'siswa'],
                'default' => 'guru',
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['message_id', 'user_id', 'user_type']);
        $this->forge->createTable('chat_read_receipts', true);
    }

    public function down()
    {
        $this->forge->dropTable('chat_read_receipts', true);
        $this->forge->dropTable('chat_messages', true);
        $this->forge->dropTable('chat_participants', true);
        $this->forge->dropTable('chat_rooms', true);
    }
}
