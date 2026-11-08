<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexesCbtTables extends Migration
{
    /**
     * Helper: check if an index exists on given table
     */
    protected function indexExists(string $table, string $index): bool
    {
        $sql = "SELECT COUNT(1) AS idx FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?";
        $row = $this->db->query($sql, [$table, $index])->getRow();
        return ($row->idx ?? 0) > 0;
    }

    public function up()
    {
        $indexes = [
            'cbt_jadwal' => [
                'idx_jadwal_tpsmt' => ['id_tp', 'id_smt'],
                'idx_jadwal_bank' => ['id_bank'],
                'idx_jadwal_rekap' => ['rekap'],
                'idx_jadwal_status' => ['status'],
            ],
            'cbt_siswa' => [
                'idx_siswa_jadwal' => ['id_jadwal'],
                'idx_siswa_idsiswa' => ['id_siswa'],
            ],
            'cbt_jawaban' => [
                'idx_jawaban_jadwal_siswa' => ['id_jadwal', 'id_siswa'],
                'idx_jawaban_soal' => ['id_soal'],
            ],
            'cbt_rekap_jadwal' => [
                'idx_rekapjadwal_tpsmt' => ['id_tp', 'id_smt'],
                'idx_rekapjadwal_jadwal' => ['id_jadwal'],
            ],
            'cbt_rekap_siswa' => [
                'idx_rekapsiswa_jadwal' => ['id_jadwal'],
                'idx_rekapsiswa_siswa' => ['id_siswa'],
            ],
            'cbt_soal' => [
                'idx_soal_bank' => ['bank_id'],
                'idx_soal_jenis' => ['jenis'],
            ],
            'cbt_sesi_siswa' => [
                'idx_sesisiswa_tpsmt' => ['tp_id', 'smt_id'],
                'idx_sesisiswa_kelas' => ['kelas_id'],
                'idx_sesisiswa_siswa' => ['siswa_id'],
            ],
            'kelas_siswa' => [
                'idx_kls_siswa_tpsmt' => ['id_tp', 'id_smt'],
                'idx_kls_siswa_idsiswa' => ['id_siswa'],
                'idx_kls_siswa_idkelas' => ['id_kelas'],
            ],
        ];

        foreach ($indexes as $table => $defs) {
            if (!$this->db->tableExists($table)) {
                continue; // Tabel modul absensi by ikasmedia tidak ada di DB ini — lewati
            }
            foreach ($defs as $idxName => $columns) {
                if (!$this->indexExists($table, $idxName)) {
                    $colsSql = '`' . implode('`,`', $columns) . '`';
                    $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$idxName}` ({$colsSql})");
                }
            }
        }
    }

    public function down()
    {
        $indexes = [
            'cbt_jadwal' => ['idx_jadwal_tpsmt', 'idx_jadwal_bank', 'idx_jadwal_rekap', 'idx_jadwal_status'],
            'cbt_siswa' => ['idx_siswa_jadwal', 'idx_siswa_idsiswa'],
            'cbt_jawaban' => ['idx_jawaban_jadwal_siswa', 'idx_jawaban_soal'],
            'cbt_rekap_jadwal' => ['idx_rekapjadwal_tpsmt', 'idx_rekapjadwal_jadwal'],
            'cbt_rekap_siswa' => ['idx_rekapsiswa_jadwal', 'idx_rekapsiswa_siswa'],
            'cbt_soal' => ['idx_soal_bank', 'idx_soal_jenis'],
            'cbt_sesi_siswa' => ['idx_sesisiswa_tpsmt', 'idx_sesisiswa_kelas', 'idx_sesisiswa_siswa'],
            'kelas_siswa' => ['idx_kls_siswa_tpsmt', 'idx_kls_siswa_idsiswa', 'idx_kls_siswa_idkelas'],
        ];

        foreach ($indexes as $table => $idxList) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ($idxList as $idxName) {
                if ($this->indexExists($table, $idxName)) {
                    $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$idxName}`");
                }
            }
        }
    }
}
