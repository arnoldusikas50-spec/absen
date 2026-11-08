<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * QR rotasi ala TOTP:
 * - secret: kunci HMAC per event (NULL = event lama → mode static)
 * - interval_detik: periode rotasi token (30/60/120)
 * - mode: 'rotating' (layar, token 6 digit ganti tiap interval) atau
 *   'static' (cetak/poster, token tetap seperti dulu)
 */
class AddQrTotpColumns extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_qr')) {
            $forge = \Config\Database::forge();

            if (!$this->db->fieldExists('secret', 'absensi_qr')) {
                $forge->addColumn('absensi_qr', [
                    'secret' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                ]);
            }
            if (!$this->db->fieldExists('interval_detik', 'absensi_qr')) {
                $forge->addColumn('absensi_qr', [
                    'interval_detik' => ['type' => 'INT', 'constraint' => 11, 'default' => 60],
                ]);
            }
            if (!$this->db->fieldExists('mode', 'absensi_qr')) {
                $forge->addColumn('absensi_qr', [
                    'mode' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'rotating'],
                ]);
            }

            // Event lama (tanpa secret) tetap static agar QR cetak lama tidak mati
            try {
                $this->db->query("UPDATE absensi_qr SET mode = 'static' WHERE secret IS NULL");
            } catch (\Throwable $e) {
                log_message('error', 'Backfill mode QR gagal: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        try {
            if ($this->db->tableExists('absensi_qr')) {
                if ($this->db->fieldExists('secret', 'absensi_qr')) {
                    $this->db->query('ALTER TABLE absensi_qr DROP COLUMN secret');
                }
                if ($this->db->fieldExists('interval_detik', 'absensi_qr')) {
                    $this->db->query('ALTER TABLE absensi_qr DROP COLUMN interval_detik');
                }
                if ($this->db->fieldExists('mode', 'absensi_qr')) {
                    $this->db->query('ALTER TABLE absensi_qr DROP COLUMN mode');
                }
            }
        } catch (\Throwable $e) {
        }
    }
}
