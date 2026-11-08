<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHolidayTables extends Migration
{
    public function up()
    {
        // 1. Create table absensi_hari_libur
        if (!$this->db->tableExists('absensi_hari_libur')) {
            $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'keterangan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('absensi_hari_libur', true);
        }

        // 2. Add hari_libur_mingguan to absensi_setting
        // Format: 'Sat,Sun' or 'Fri'
        if ($this->db->tableExists('absensi_setting')
            && !$this->db->fieldExists('hari_libur_mingguan', 'absensi_setting')) {
            $this->forge->addColumn('absensi_setting', [
                'hari_libur_mingguan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'Sun', // Default Minggu libur
                    'after' => 'foto_wajib'
                ]
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('absensi_hari_libur', true);
        $this->forge->dropColumn('absensi_setting', 'hari_libur_mingguan');
    }
}
