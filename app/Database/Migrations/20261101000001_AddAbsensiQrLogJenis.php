<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Satu user kini boleh memindai event QR dua kali: masuk dan pulang.
 *
 * UNIQUE(qr_id, user_id) lama memblokir scan pulang, sehingga unik key
 * diganti menjadi UNIQUE(qr_id, user_id, jenis). Baris lama otomatis
 * berjenis 'masuk' lewat default kolom.
 */
class AddAbsensiQrLogJenis extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('absensi_qr_log')) {
            return;
        }

        $hasJenis = $this->db->fieldExists('jenis', 'absensi_qr_log');
        if (!$hasJenis) {
            $this->forge->addColumn('absensi_qr_log', [
                'jenis' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10,
                    'default' => 'masuk',
                    'null' => false,
                ],
            ]);
            $hasJenis = true;
        }

        if (!$hasJenis) {
            return;
        }

        $target = ['jenis', 'qr_id', 'user_id'];
        sort($target);
        $hasTarget = false;

        foreach ($this->uniqueIndexMap('absensi_qr_log') as $name => $cols) {
            $sorted = $cols;
            sort($sorted);

            if ($sorted === $target) {
                $hasTarget = true;
                continue;
            }

            // Unique lama (qr_id, user_id) menolak scan kedua di event yang sama.
            if ($sorted === ['qr_id', 'user_id']) {
                $this->db->query('ALTER TABLE `absensi_qr_log` DROP INDEX `' . str_replace('`', '``', $name) . '`');
            }
        }

        if (!$hasTarget) {
            $this->db->query('ALTER TABLE `absensi_qr_log` ADD UNIQUE KEY `uq_qr_user_jenis` (`qr_id`, `user_id`, `jenis`)');
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('absensi_qr_log')) {
            return;
        }

        // Riwayat pulang duplikat akan menolak unique lama (qr_id, user_id).
        try {
            if ($this->db->fieldExists('jenis', 'absensi_qr_log')) {
                $this->db->query("DELETE FROM `absensi_qr_log` WHERE `jenis` = 'pulang'");
            }
        } catch (\Throwable $e) {
        }

        try {
            foreach ($this->uniqueIndexMap('absensi_qr_log') as $name => $cols) {
                $sorted = $cols;
                sort($sorted);
                if ($sorted === ['jenis', 'qr_id', 'user_id']) {
                    $this->db->query('ALTER TABLE `absensi_qr_log` DROP INDEX `' . str_replace('`', '``', $name) . '`');
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $this->db->query('ALTER TABLE `absensi_qr_log` ADD UNIQUE KEY `uq_qr_user` (`qr_id`, `user_id`)');
        } catch (\Throwable $e) {
        }

        try {
            if ($this->db->fieldExists('jenis', 'absensi_qr_log')) {
                $this->forge->dropColumn('absensi_qr_log', 'jenis');
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * @return array<string,array<int,string>> nama index => kolom terurut
     */
    private function uniqueIndexMap(string $table): array
    {
        $map = [];
        if (!$this->db->tableExists($table)) {
            return $map;
        }

        $rows = $this->db->query('SHOW INDEX FROM `' . str_replace('`', '``', $table) . '`')->getResult();
        foreach ($rows as $row) {
            if ((int) ($row->Non_unique ?? 1) !== 0) {
                continue;
            }
            $map[$row->Key_name][(int) $row->Seq_in_index] = $row->Column_name;
        }

        foreach ($map as $name => $cols) {
            ksort($cols);
            $map[$name] = array_values($cols);
        }

        return $map;
    }
}
