<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostLikesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_like' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'auto_increment' => true,
            ],
            'id_post' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
            ],
            'id_comment' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'null' => true,
                'default' => null,
            ],
            'dari' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => false,
                'default' => 0,
            ],
            'dari_group' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => false,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id_like', true);
        $this->forge->addKey('id_post');
        $this->forge->addKey('id_comment');
        $this->forge->addKey(['dari', 'dari_group']);

        // Note: Unique key will be added separately
        $this->forge->createTable('post_likes', true);

        // Add unique constraint if not exists
        $result = $this->db->query("SHOW KEYS FROM `post_likes` WHERE Key_name = 'unique_like_post'")->getResult();
        if (empty($result)) {
            $this->db->query('ALTER TABLE `post_likes` ADD UNIQUE KEY `unique_like_post` (`id_post`, `id_comment`, `dari`, `dari_group`)');
        }
    }

    public function down()
    {
        $this->forge->dropTable('post_likes', true);
    }
}
