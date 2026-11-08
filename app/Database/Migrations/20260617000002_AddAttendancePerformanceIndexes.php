<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah indexes ke tabel presensi untuk meningkatkan performa query:
 * - Monitoring harian
 * - Rekap bulanan
 * - Alpha counting
 * - Ranking kedisiplinan
 */
class AddAttendancePerformanceIndexes extends Migration
{
    public function up()
    {
        // ---- absensi_harian ----
        // Index untuk query monitoring harian (admin/presensi?date=...)
        if (!$this->indexExists('absensi_harian', 'idx_tgl_status')) {
            $this->db->query("
                ALTER TABLE absensi_harian 
                ADD INDEX idx_tgl_status (tgl, status_masuk)
            ");
        }

        // Index untuk rekap bulanan per user (MONTH(tgl) queries)
        if (!$this->indexExists('absensi_harian', 'idx_user_tgl')) {
            $this->db->query("
                ALTER TABLE absensi_harian 
                ADD INDEX idx_user_tgl (user_id, tgl)
            ");
        }

        // Index untuk alpha counting (LEFT JOIN by user+date)
        if (!$this->indexExists('absensi_harian', 'idx_tgl_userid')) {
            $this->db->query("
                ALTER TABLE absensi_harian 
                ADD INDEX idx_tgl_userid (tgl, user_id, status_masuk)
            ");
        }

        // Index untuk denda reporting
        if (!$this->indexExists('absensi_harian', 'idx_denda')) {
            $this->db->query("
                ALTER TABLE absensi_harian 
                ADD INDEX idx_denda (user_id, denda)
            ");
        }

        // ---- absensi_izin ----
        // Index untuk filter per user dan status
        if (!$this->indexExists('absensi_izin', 'idx_izin_user_status')) {
            $this->db->query("
                ALTER TABLE absensi_izin 
                ADD INDEX idx_izin_user_status (user_id, status, tgl_mulai)
            ");
        }

        // ---- absensi_koreksi ----
        if ($this->db->tableExists('absensi_koreksi')) {
            if (!$this->indexExists('absensi_koreksi', 'idx_koreksi_status')) {
                $this->db->query("
                    ALTER TABLE absensi_koreksi 
                    ADD INDEX idx_koreksi_status (status, created_at)
                ");
            }
        }

        // ---- presensi_siswa ----
        if ($this->db->tableExists('presensi_siswa')) {
            // Index rekap bulanan siswa
            if (!$this->indexExists('presensi_siswa', 'idx_siswa_kelas_tgl')) {
                $this->db->query("
                    ALTER TABLE presensi_siswa 
                    ADD INDEX idx_siswa_kelas_tgl (id_kelas, id_siswa, tgl)
                ");
            }

            // Index monitoring harian per kelas
            if (!$this->indexExists('presensi_siswa', 'idx_kelas_tgl')) {
                $this->db->query("
                    ALTER TABLE presensi_siswa 
                    ADD INDEX idx_kelas_tgl (id_kelas, tgl, status)
                ");
            }
        }

        // ---- absensi_hari_libur ----
        if ($this->db->tableExists('absensi_hari_libur')) {
            if (!$this->indexExists('absensi_hari_libur', 'idx_libur_tanggal')) {
                $this->db->query("
                    ALTER TABLE absensi_hari_libur 
                    ADD INDEX idx_libur_tanggal (tanggal)
                ");
            }
        }
    }

    public function down()
    {
        // Drop indexes (safe to ignore errors if not exists)
        try { $this->db->query("ALTER TABLE absensi_harian DROP INDEX idx_tgl_status"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE absensi_harian DROP INDEX idx_user_tgl"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE absensi_harian DROP INDEX idx_tgl_userid"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE absensi_harian DROP INDEX idx_denda"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE absensi_izin DROP INDEX idx_izin_user_status"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE absensi_koreksi DROP INDEX idx_koreksi_status"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE presensi_siswa DROP INDEX idx_siswa_kelas_tgl"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE presensi_siswa DROP INDEX idx_kelas_tgl"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE absensi_hari_libur DROP INDEX idx_libur_tanggal"); } catch (\Throwable $e) {}
    }

    /**
     * Helper: cek apakah index sudah ada di tabel
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (!$this->db->tableExists($table)) {
            return false;
        }
        $result = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return $result->getNumRows() > 0;
    }
}
