<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFaceDescriptorToUsers extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('users') && !$this->db->fieldExists('face_descriptor', 'users')) {
            $this->forge->addColumn('users', [
                'face_descriptor' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'foto'
                ]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('face_descriptor', 'users')) {
            $this->forge->dropColumn('users', 'face_descriptor');
        }
    }
}
