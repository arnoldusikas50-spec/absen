<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah kolom untuk alert persentase kehadiran dan data semester
 * Standar SMA: minimal 90% kehadiran untuk naik kelas / penilaian kinerja
 */
class AddAttendanceAlertColumns extends Migration
{
    public function up()
    {
        $forge = $this->forge;

        // Tambah kolom min_kehadiran_persen ke absensi_setting
        if ($this->db->tableExists('absensi_setting')) {
            if (!$this->db->fieldExists('min_kehadiran_persen', 'absensi_setting')) {
                $forge->addColumn('absensi_setting', [
                    'min_kehadiran_persen' => [
                        'type' => 'DECIMAL',
                        'constraint' => '5,2',
                        'default' => 90.00,
                        'null' => true,
                        'comment' => 'Persentase minimal kehadiran (standar SMA: 90%)',
                    ],
                ]);
            }

            if (!$this->db->fieldExists('alert_kehadiran_enabled', 'absensi_setting')) {
                $forge->addColumn('absensi_setting', [
                    'alert_kehadiran_enabled' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 1,
                        'null' => true,
                        'comment' => 'Aktifkan alert persentase kehadiran di dashboard',
                    ],
                ]);
            }

            // Opsi notifikasi otomatis ke kepsek jika ada guru < threshold
            if (!$this->db->fieldExists('notif_kehadiran_rendah', 'absensi_setting')) {
                $forge->addColumn('absensi_setting', [
                    'notif_kehadiran_rendah' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 1,
                        'null' => true,
                        'comment' => 'Kirim notifikasi ke kepsek jika kehadiran guru < threshold',
                    ],
                ]);
            }
        }

        // Tabel rekap kehadiran per semester (untuk laporan Dinas Pendidikan)
        if (!$this->db->tableExists('absensi_rekap_semester')) {
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'id_tp' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Tahun Pelajaran',
                ],
                'id_smt' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Semester',
                ],
                'tahun' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                ],
                'semester' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'comment' => '1 = Ganjil, 2 = Genap',
                ],
                'total_hari_efektif' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'total_hadir' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'total_telat' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'total_izin' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'total_sakit' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'total_alpha' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'total_dinas' => [
                    'type' => 'SMALLINT',
                    'constraint' => 4,
                    'default' => 0,
                ],
                'persentase_kehadiran' => [
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'default' => 0.00,
                ],
                'total_poin' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'total_denda' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0.00,
                ],
                'status_kelayakan' => [
                    'type' => 'ENUM',
                    'constraint' => ['layak', 'perhatian', 'tidak_layak'],
                    'default' => 'layak',
                    'comment' => 'Kelayakan berdasarkan persentase kehadiran',
                ],
                'catatan' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'generated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addKey(['user_id', 'tahun', 'semester'], false, true, 'unik_rekap_semester');
            $forge->createTable('absensi_rekap_semester', true);
        }

        // Set default nilai min_kehadiran jika tabel setting sudah ada data
        if ($this->db->tableExists('absensi_setting')) {
            $this->db->query("
                UPDATE absensi_setting 
                SET min_kehadiran_persen = 90.00 
                WHERE min_kehadiran_persen IS NULL OR min_kehadiran_persen = 0
            ");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_setting')) {
            try {
                $this->forge->dropColumn('absensi_setting', [
                    'min_kehadiran_persen',
                    'alert_kehadiran_enabled',
                    'notif_kehadiran_rendah',
                ]);
            } catch (\Throwable $e) {}
        }

        $this->forge->dropTable('absensi_rekap_semester', true);
    }
}
