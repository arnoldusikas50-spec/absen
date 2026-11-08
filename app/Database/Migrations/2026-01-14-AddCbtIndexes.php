<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add indexes for absensi by ikasmedia tables to improve performance
 * Critical for 1000+ concurrent users
 */
class AddCbtIndexes extends Migration
{
    public function up()
    {
        // Lewati tabel modul absensi by ikasmedia yang tidak ada di DB ini
        $idx = [
            'cbt_jawaban' => [
                'idx_jawaban_siswa_jadwal' => '(id_siswa, id_jadwal)',
                'idx_jawaban_lookup' => '(id_siswa, id_jadwal, id_soal)',
            ],
            'cbt_siswa' => [
                'idx_cbtsiswa_siswa_jadwal' => '(id_siswa, id_jadwal)',
                'idx_cbtsiswa_status' => '(status)',
            ],
            'cbt_nilai' => [
                'idx_nilai_siswa_jadwal' => '(id_siswa, id_jadwal)',
            ],
            'cbt_durasi_siswa' => [
                'idx_durasi_siswa_jadwal' => '(id_siswa, id_jadwal)',
            ],
            'cbt_jadwal' => [
                'idx_jadwal_tp_smt' => '(id_tp, id_smt)',
                'idx_jadwal_status' => '(status_ujian)',
            ],
        ];

        foreach ($idx as $table => $defs) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ($defs as $name => $cols) {
                try {
                    $this->db->query("CREATE INDEX IF NOT EXISTS {$name} ON {$table} {$cols}");
                } catch (\Throwable $e) {
                    log_message('error', "AddCbtIndexes gagal {$name}: " . $e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        // Drop indexes
        $drops = [
            'cbt_jawaban' => ['idx_jawaban_siswa_jadwal', 'idx_jawaban_lookup'],
            'cbt_siswa' => ['idx_cbtsiswa_siswa_jadwal', 'idx_cbtsiswa_status'],
            'cbt_nilai' => ['idx_nilai_siswa_jadwal'],
            'cbt_durasi_siswa' => ['idx_durasi_siswa_jadwal'],
            'cbt_jadwal' => ['idx_jadwal_tp_smt', 'idx_jadwal_status'],
        ];
        foreach ($drops as $table => $names) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ($names as $name) {
                try {
                    $this->db->query("DROP INDEX IF EXISTS {$name} ON {$table}");
                } catch (\Throwable $e) {
                }
            }
        }
    }
}
