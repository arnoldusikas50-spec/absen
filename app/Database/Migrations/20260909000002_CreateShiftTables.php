<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Shift kerja per guru.
 *
 * - absensi_shift: profil jam kerja bernama (mis. "Shift Pagi", "Shift Sore").
 *   grace_menit NULL = ikut grace_period global.
 * - absensi_shift_anggota: penugasan user ke shift.
 *   hari 0 = berlaku semua hari, 1..7 = ISO weekday (1=Senin).
 *   Prioritas saat resolve: baris hari spesifik > baris hari=0.
 */
class CreateShiftTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('absensi_shift')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
                'jam_masuk' => ['type' => 'TIME', 'default' => '07:30:00'],
                'jam_pulang' => ['type' => 'TIME', 'default' => '15:00:00'],
                'grace_menit' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'comment' => 'NULL = ikut setting global'],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->createTable('absensi_shift', true);
        }

        if (!$this->db->tableExists('absensi_shift_anggota')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'shift_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'hari' => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 0, 'comment' => '0=semua hari, 1..7=ISO weekday'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['user_id', 'hari', 'shift_id']);
            $this->forge->addKey('shift_id');
            $this->forge->createTable('absensi_shift_anggota', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('absensi_shift_anggota', true);
        $this->forge->dropTable('absensi_shift', true);
    }
}
