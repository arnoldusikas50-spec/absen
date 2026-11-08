<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom snapshot jumlah_alpha dan status ke tabel absensi_surat_teguran:
 * - jumlah_alpha: snapshot jumlah hari alpha riil saat surat diterbitkan (immutable)
 * - status: 'aktif' atau 'dibatalkan' (jika izin resmi disetujui retroaktif)
 * - keterangan_batal: catatan alasan pembatalan
 */
class AddJumlahAlphaAndStatusToSuratTeguran extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_surat_teguran')) {
            $forge = \Config\Database::forge();

            $fieldsToAdd = [];

            if (!$this->db->fieldExists('jumlah_alpha', 'absensi_surat_teguran')) {
                $fieldsToAdd['jumlah_alpha'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => 0,
                    'after'      => 'level',
                ];
            }

            if (!$this->db->fieldExists('status', 'absensi_surat_teguran')) {
                $fieldsToAdd['status'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'aktif',
                    'after'      => 'nomor_urut',
                ];
            }

            if (!$this->db->fieldExists('keterangan_batal', 'absensi_surat_teguran')) {
                $fieldsToAdd['keterangan_batal'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'status',
                ];
            }

            if (!empty($fieldsToAdd)) {
                $forge->addColumn('absensi_surat_teguran', $fieldsToAdd);
            }

            // Backfill data lama: jika jumlah_alpha masih NULL atau 0,
            // set default sama dengan level surat (3, 7, atau 10) agar dokumen historis konsisten
            try {
                $this->db->query("
                    UPDATE absensi_surat_teguran 
                    SET jumlah_alpha = CASE 
                        WHEN level >= 10 THEN 10 
                        WHEN level >= 7 THEN 7 
                        ELSE 3 
                    END 
                    WHERE jumlah_alpha IS NULL OR jumlah_alpha = 0
                ");
            } catch (\Throwable $e) {
                log_message('warning', 'Backfill jumlah_alpha surat teguran: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_surat_teguran')) {
            if ($this->db->fieldExists('jumlah_alpha', 'absensi_surat_teguran')) {
                $this->db->query('ALTER TABLE absensi_surat_teguran DROP COLUMN jumlah_alpha');
            }
            if ($this->db->fieldExists('status', 'absensi_surat_teguran')) {
                $this->db->query('ALTER TABLE absensi_surat_teguran DROP COLUMN status');
            }
            if ($this->db->fieldExists('keterangan_batal', 'absensi_surat_teguran')) {
                $this->db->query('ALTER TABLE absensi_surat_teguran DROP COLUMN keterangan_batal');
            }
        }
    }
}
