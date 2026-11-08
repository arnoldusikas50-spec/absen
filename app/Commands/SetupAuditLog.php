<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SetupAuditLog extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:setup_audit';
    protected $description = 'Creates the audit_logs table manually.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if ($db->tableExists('audit_logs')) {
            CLI::write("Table audit_logs already exists.", 'yellow');
            return;
        }

        $fields = [
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'table_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'target_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID user pemilik data (misal: guru)',
            ],
            'actor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID user yang melakukan perubahan (misal: admin)',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => '20', // CREATE, UPDATE, DELETE
            ],
            'old_values' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'new_values' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        $forge->addField($fields);
        $forge->addKey('id', true);
        $forge->addKey('target_user_id');
        $forge->addKey('record_id');
        
        if ($forge->createTable('audit_logs')) {
            CLI::write("Table audit_logs created successfully.", 'green');
        } else {
            CLI::error("Failed to create table audit_logs.");
        }
    }
}
