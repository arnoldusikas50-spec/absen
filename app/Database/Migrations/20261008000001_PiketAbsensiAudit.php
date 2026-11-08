<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Audit pencatat absensi siswa jalur piket.
 */
class PiketAbsensiAudit extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('piket_absensi')) {
            return;
        }
        $add = [];
        foreach ([
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ] as $col => $def) {
            if (!$this->db->fieldExists($col, 'piket_absensi')) {
                $add[$col] = $def;
            }
        }
        if (!empty($add)) {
            $this->forge->addColumn('piket_absensi', $add);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('piket_absensi')) {
            return;
        }
        foreach (['created_by', 'updated_by'] as $col) {
            try {
                if ($this->db->fieldExists($col, 'piket_absensi')) {
                    $this->forge->dropColumn('piket_absensi', $col);
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
