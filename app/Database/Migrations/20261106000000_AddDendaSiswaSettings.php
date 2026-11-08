<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Denda alpha & bolos untuk siswa.
 *
 * Sampai di sini denda rupiah hanya ada untuk guru: tarifnya
 * (absensi_setting.denda_alpha_per_hari, denda_tier_1..3, max_denda_bulan)
 * hanya dibaca AttendancePenaltyService dan absen di absensi_harian.denda.
 * Presensi siswa punya poin disiplin (poin_siswa_history) tapi tidak punya
 * nominal rupiah sama sekali, sehingga sekolah tidak bisa menagih apa pun
 * atas alpha atau bolos siswa.
 *
 * - absensi_setting.denda_alpha_siswa_per_hari : tarif per hari siswa yang
 *   berstatus alpha/tanpa keterangan.
 * - absensi_setting.denda_bolos_siswa_per_hari : tarif per hari siswa yang
 *   masuk tapi tidak absen pulang (bolos, definisi yang sama dengan label
 *   "BOLOS — MASUK TANPA PULANG" di rekap piket).
 * - absensi_setting.max_denda_siswa_bulan      : plafon akumulasi per siswa
 *   per bulan kalender; 0 = tanpa batas. Dipakai terpisah dari
 *   max_denda_bulan milik guru supaya tagihan keduanya tidak saling memotong.
 * - presensi_siswa.denda + denda_jenis + keterangan_denda : nominal yang
 *   berlaku untuk satu baris presensi, ditulis SiswaDendaService saat barisnya
 *   pertama kali melanggar. Jenis disimpan supaya baris yang sudah tertagih
 *   tidak dihitung ulang dari tarif yang mungkin sudah berubah — sama seperti
 *   absensi_harian.poin_penalti_pulang di sisi guru.
 *
 * Ketiga tarif default 0 = fitur mati. Mengaktifkannya lewat /settings harus
 * menjadi keputusan sekolah, bukan efek samping upgrade yang diam-diam
 * menagih seluruh riwayat absensi.
 */
class AddDendaSiswaSettings extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_setting')) {
            if (! $this->db->fieldExists('denda_alpha_siswa_per_hari', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'denda_alpha_siswa_per_hari' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'default'    => 0,
                        'null'       => true,
                    ],
                ]);
            }

            if (! $this->db->fieldExists('denda_bolos_siswa_per_hari', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'denda_bolos_siswa_per_hari' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'default'    => 0,
                        'null'       => true,
                    ],
                ]);
            }

            if (! $this->db->fieldExists('max_denda_siswa_bulan', 'absensi_setting')) {
                $this->forge->addColumn('absensi_setting', [
                    'max_denda_siswa_bulan' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '10,2',
                        'default'    => 0,
                        'null'       => true,
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('presensi_siswa')) {
            if (! $this->db->fieldExists('denda', 'presensi_siswa')) {
                $this->forge->addColumn('presensi_siswa', [
                    'denda' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '10,2',
                        'default'    => 0,
                        'null'       => false,
                    ],
                ]);
            }

            if (! $this->db->fieldExists('denda_jenis', 'presensi_siswa')) {
                $this->forge->addColumn('presensi_siswa', [
                    'denda_jenis' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 10,
                        'default'    => '',
                        'null'       => false,
                    ],
                ]);
            }

            if (! $this->db->fieldExists('keterangan_denda', 'presensi_siswa')) {
                $this->forge->addColumn('presensi_siswa', [
                    'keterangan_denda' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('presensi_siswa')) {
            foreach (['keterangan_denda', 'denda_jenis', 'denda'] as $kolom) {
                if ($this->db->fieldExists($kolom, 'presensi_siswa')) {
                    $this->forge->dropColumn('presensi_siswa', $kolom);
                }
            }
        }

        if ($this->db->tableExists('absensi_setting')) {
            foreach (['max_denda_siswa_bulan', 'denda_bolos_siswa_per_hari', 'denda_alpha_siswa_per_hari'] as $kolom) {
                if ($this->db->fieldExists($kolom, 'absensi_setting')) {
                    $this->forge->dropColumn('absensi_setting', $kolom);
                }
            }
        }
    }
}
