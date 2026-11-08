<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Konfirmasi baca surat teguran oleh guru:
 * - ack_at: kapan guru mengetik konfirmasi "ya saya mengerti"
 * - ack_text: teks yang diketik (audit)
 * Surat yang belum ack memicu modal wajib saat tekan tombol Absen.
 */
class AddAckToSuratTeguran extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_surat_teguran')) {
            $forge = \Config\Database::forge();

            if (!$this->db->fieldExists('ack_at', 'absensi_surat_teguran')) {
                $forge->addColumn('absensi_surat_teguran', [
                    'ack_at' => ['type' => 'DATETIME', 'null' => true],
                ]);
            }
            if (!$this->db->fieldExists('ack_text', 'absensi_surat_teguran')) {
                $forge->addColumn('absensi_surat_teguran', [
                    'ack_text' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                ]);
            }
        }
    }

    public function down()
    {
        try {
            if ($this->db->tableExists('absensi_surat_teguran')) {
                if ($this->db->fieldExists('ack_at', 'absensi_surat_teguran')) {
                    $this->db->query('ALTER TABLE absensi_surat_teguran DROP COLUMN ack_at');
                }
                if ($this->db->fieldExists('ack_text', 'absensi_surat_teguran')) {
                    $this->db->query('ALTER TABLE absensi_surat_teguran DROP COLUMN ack_text');
                }
            }
        } catch (\Throwable $e) {
        }
    }
}
