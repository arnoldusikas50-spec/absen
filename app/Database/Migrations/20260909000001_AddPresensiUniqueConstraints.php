<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cegah double check-in/check-out race condition:
 * - UNIQUE(user_id, tgl) di absensi_harian
 * - UNIQUE(qr_id, user_id) di absensi_qr_log
 *
 * Duplikat lama dibersihkan dulu (simpan id terkecil per grup).
 */
class AddPresensiUniqueConstraints extends Migration
{
    public function up()
    {
        // ---- absensi_harian: bersihkan duplikat (user_id, tgl) ----
        if ($this->db->tableExists('absensi_harian')) {
            // Hapus baris duplikat, sisakan id terkecil per (user_id, tgl)
            try {
                $this->db->query("
                    DELETE a FROM absensi_harian a
                    INNER JOIN absensi_harian b
                        ON a.user_id = b.user_id
                        AND a.tgl = b.tgl
                        AND a.id > b.id
                ");
            } catch (\Throwable $e) {
                log_message('error', 'Dedup absensi_harian gagal: ' . $e->getMessage());
            }

            if (!$this->indexExists('absensi_harian', 'uq_user_tgl')) {
                try {
                    $this->db->query("
                        ALTER TABLE absensi_harian
                        ADD UNIQUE KEY uq_user_tgl (user_id, tgl)
                    ");
                } catch (\Throwable $e) {
                    log_message('error', 'ADD UNIQUE uq_user_tgl gagal: ' . $e->getMessage());
                }
            }
        }

        // ---- absensi_qr_log: satu scan per user per event ----
        if ($this->db->tableExists('absensi_qr_log')) {
            try {
                $this->db->query("
                    DELETE a FROM absensi_qr_log a
                    INNER JOIN absensi_qr_log b
                        ON a.qr_id = b.qr_id
                        AND a.user_id = b.user_id
                        AND a.id > b.id
                ");
            } catch (\Throwable $e) {
                log_message('error', 'Dedup absensi_qr_log gagal: ' . $e->getMessage());
            }

            if (!$this->indexExists('absensi_qr_log', 'uq_qr_user')) {
                try {
                    $this->db->query("
                        ALTER TABLE absensi_qr_log
                        ADD UNIQUE KEY uq_qr_user (qr_id, user_id)
                    ");
                } catch (\Throwable $e) {
                    log_message('error', 'ADD UNIQUE uq_qr_user gagal: ' . $e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        try {
            if ($this->db->tableExists('absensi_harian') && $this->indexExists('absensi_harian', 'uq_user_tgl')) {
                $this->db->query("ALTER TABLE absensi_harian DROP INDEX uq_user_tgl");
            }
        } catch (\Throwable $e) {
        }
        try {
            if ($this->db->tableExists('absensi_qr_log') && $this->indexExists('absensi_qr_log', 'uq_qr_user')) {
                $this->db->query("ALTER TABLE absensi_qr_log DROP INDEX uq_qr_user");
            }
        } catch (\Throwable $e) {
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!$this->db->tableExists($table)) {
            return false;
        }
        $result = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return $result->getNumRows() > 0;
    }
}
