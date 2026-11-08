<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDendaColumnsToSettings extends Migration
{
    public function up()
    {
        $fields = [
            'denda_per_menit' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 1000.00,
                'null' => true,
            ],
            'denda_pulang_awal' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 1000.00,
                'null' => true,
            ],
        ];

        // Check if columns exist before adding
        $db = \Config\Database::connect();
        if ($db->tableExists('absensi_setting')) {
            $existingColumns = $db->getFieldNames('absensi_setting');
            
            foreach ($fields as $column => $definition) {
                if (!in_array($column, $existingColumns)) {
                    $this->forge->addColumn('absensi_setting', [$column => $definition]);
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('absensi_setting', ['denda_per_menit', 'denda_pulang_awal']);
    }
}
