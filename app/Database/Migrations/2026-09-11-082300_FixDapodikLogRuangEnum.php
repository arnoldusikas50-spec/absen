<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fix: tambah nilai 'ruang' pada ENUM dapodik_log.entitas.
 * Sync ruang menulis writeLog('ruang', ...) sehingga tanpa nilai ini
 * insert log gagal (Incorrect enum value).
 */
class FixDapodikLogRuangEnum extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('dapodik_log')) {
            return;
        }

        // ENUM tidak didukung forge->modifyColumn secara portabel → pakai raw SQL.
        $this->db->query(
            "ALTER TABLE `dapodik_log` MODIFY COLUMN `entitas` " .
            "ENUM('all','sekolah','gtk','siswa','rombel','mapel','pembelajaran','nilai','ruang') " .
            "NOT NULL DEFAULT 'all'"
        );
    }

    public function down()
    {
        if (!$this->db->tableExists('dapodik_log')) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `dapodik_log` MODIFY COLUMN `entitas` " .
            "ENUM('all','sekolah','gtk','siswa','rombel','mapel','pembelajaran','nilai') " .
            "NOT NULL DEFAULT 'all'"
        );
    }
}
