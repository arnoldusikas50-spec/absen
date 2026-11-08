<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Preferensi tema tampilan (Dynamic Theme / Theme Customizer).
 * - theme_preset: kunci preset (default|ocean|emerald|royal|sunset|rose|custom)
 * - theme_custom: warna dasar HEX 7 karakter bila preset = custom
 */
class AddThemeToSetting extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('setting')) {
            return;
        }

        $add = [];
        if (!$this->db->fieldExists('theme_preset', 'setting')) {
            $add['theme_preset'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'default',
            ];
        }
        if (!$this->db->fieldExists('theme_custom', 'setting')) {
            $add['theme_custom'] = [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'null'       => true,
                'default'    => null,
            ];
        }

        if ($add !== []) {
            $this->forge->addColumn('setting', $add);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('setting')) {
            return;
        }
        try {
            $drop = [];
            foreach (['theme_preset', 'theme_custom'] as $col) {
                if ($this->db->fieldExists($col, 'setting')) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $this->forge->dropColumn('setting', $drop);
            }
        } catch (\Throwable $e) {
        }
    }
}
