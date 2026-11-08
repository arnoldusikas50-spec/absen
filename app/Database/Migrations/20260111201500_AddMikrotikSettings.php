<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMikrotikSettings extends Migration
{
    public function up()
    {
        $fields = [
            'mikrotik_host' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'after' => 'updated_at'
            ],
            'mikrotik_user' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'after' => 'mikrotik_host'
            ],
            'mikrotik_pass' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'after' => 'mikrotik_user'
            ],
            'mikrotik_port' => [
                'type' => 'INT',
                'constraint' => 5,
                'default' => 8728,
                'null' => true,
                'after' => 'mikrotik_pass'
            ],
            'mikrotik_hotspot_profile' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => 'default',
                'after' => 'mikrotik_port'
            ],
        ];

        // Check columns to avoid errors if run multiple times
        if ($this->db->tableExists('absensi_setting')) {
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
                'mikrotik_host',
                'mikrotik_user',
                'mikrotik_pass',
                'mikrotik_port',
                'mikrotik_hotspot_profile'
            ]);
        }
    }
}
