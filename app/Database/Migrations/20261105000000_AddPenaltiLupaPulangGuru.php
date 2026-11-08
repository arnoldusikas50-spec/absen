<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penalti poin guru yang tidak absen pulang.
 *
 * Sampai di sini "lupa check-out" untuk guru hanya menghasilkan pengingat
 * (CronController::markForgotCheckout) dan angka 5 poin yang di-hardcode di
 * rumus skor tampilan (AttendancePolicy::sqlTermBelumPulang). Tidak ada
 * satu pun baris absensi_harian.points yang pernah dipotong karena tidak
 * absen pulang, jadi kebijakan sekolah soal ini tidak bisa dijalankan.
 *
 * - absensi_setting.point_lupa_pulang  : besar pengurangan poin per hari;
 *   0 = fitur mati. Bawaan 5 = sama dengan point_alpa dan dengan angka yang
 *   selama ini dipakai rumus skor, sehingga mengaktifkan fitur tidak berubah
 *   drastis dari yang sudah terlihat admin.
 * - absensi_setting.lupa_pulang_dari_tgl: watermark forward-only. Tanpa ini,
 *   cron pertama memotong seluruh hari yang tercatat sebelum fitur ada —
 *   beribu baris historis berubah poinnya sekaligus.
 * - absensi_harian.pulang_tidak_tercatat + poin_penalti_pulang: penanda bahwa
 *   penalti sudah diterapkan (cron yang berjalan ulang tidak memotong dua
 *   kali) dan berapa persis yang dipotong, supaya jumlahnya bisa dibatalkan
 *   saat admin mengoreksi hari itu dan tidak disalin ulang dari pengaturan
 *   yang mungkin sudah berubah.
 *
 * Kolom absensi_harian diberi nama sama dengan pasangannya di presensi_siswa
 * (migrasi 20261102000002) agar satu konsep punya satu nama di kedua tabel.
 */
class AddPenaltiLupaPulangGuru extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_setting')) {
            if (! $this->db->fieldExists('point_lupa_pulang', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'point_lupa_pulang' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'default'    => 5,
                        'null'       => true,
                    ],
                ]);
            }

            if (! $this->db->fieldExists('lupa_pulang_dari_tgl', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'lupa_pulang_dari_tgl' => [
                        'type'    => 'DATE',
                        'null'    => true,
                        'default' => null,
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('absensi_harian')) {
            if (! $this->db->fieldExists('pulang_tidak_tercatat', 'absensi_harian')) {
                $this->forge->addColumn('absensi_harian', [
                    'pulang_tidak_tercatat' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                        'null'       => false,
                    ],
                ]);
            }

            if (! $this->db->fieldExists('poin_penalti_pulang', 'absensi_harian')) {
                $this->forge->addColumn('absensi_harian', [
                    'poin_penalti_pulang' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'default'    => 0,
                        'null'       => false,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_harian')) {
            foreach (['poin_penalti_pulang', 'pulang_tidak_tercatat'] as $kolom) {
                if ($this->db->fieldExists($kolom, 'absensi_harian')) {
                    $this->forge->dropColumn('absensi_harian', $kolom);
                }
            }
        }

        if ($this->db->tableExists('absensi_setting')) {
            foreach (['lupa_pulang_dari_tgl', 'point_lupa_pulang'] as $kolom) {
                if ($this->db->fieldExists($kolom, 'absensi_setting')) {
                    $this->forge->dropColumn('absensi_setting', $kolom);
                }
            }
        }
    }
}
