<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Idempotensi pembayaran denda: satu kunci unik per aksi bayar.
 *
 * `markLunas` / `bayarDendaNominal` memakai pola baca-sisa-lalu-insert
 * dalam transaksi. Klik ganda (atau retry jaringan) mengirim dua request
 * yang sama-sama lolos pengecekan sebelum salah satunya commit — tanpa
 * unique key, transaksi kedua tidak punya penolak akhir dan denda
 * tercatat dua kali (poin/saldo ikut terpotong dua kali sebelum rollback
 * pun tak terjadi karena tidak ada error).
 *
 * `idempotency_key` diisi client (UUID per buka modal, lihat
 * Views/admin/presensi/denda.php). Baris FIFO satu pembayaran memakai
 * `key:YYYY-MM` per bulan agar tiap baris unik namun satu aksi tetap
 * terdeteksi via prefix. Kolom NULL bebas ganda (data lama & jalur
 * voucher yang tidak memakai kunci).
 */
class AddUniquePembayaranIdempotency extends Migration
{
    private const INDEX = 'uniq_pembayaran_idempotency';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_pembayaran_denda')) {
            return;
        }
        if (! $db->fieldExists('idempotency_key', 'absensi_pembayaran_denda')) {
            $forge = \Config\Database::forge();
            $forge->addColumn('absensi_pembayaran_denda', [
                'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            ]);
        }

        $indexName = self::INDEX;
        if ($this->sudahAdaIndex($db, 'absensi_pembayaran_denda', $indexName)) {
            return;
        }

        $this->forge->addUniqueKey(['idempotency_key'], $indexName);
        $this->forge->processIndexes('absensi_pembayaran_denda');

        if (! $this->sudahAdaIndex($db, 'absensi_pembayaran_denda', $indexName)) {
            throw new \RuntimeException(
                'Unique key ' . $indexName . ' gagal dibuat pada absensi_pembayaran_denda.'
            );
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_pembayaran_denda')) {
            return;
        }

        if ($this->sudahAdaIndex($db, 'absensi_pembayaran_denda', self::INDEX)) {
            $this->forge->dropKey('absensi_pembayaran_denda', self::INDEX);
        }
    }

    private function sudahAdaIndex($db, string $table, string $indexName): bool
    {
        try {
            $driver = $db->DBDriver;

            if ($driver === 'MySQLi' || $driver === 'MySQL') {
                $res = $db->query(
                    'SHOW INDEX FROM ' . $db->prefixTable($table) . " WHERE Key_name = '"
                    . addslashes($indexName) . "'"
                );
                return $res !== false && $res->getNumRows() > 0;
            }

            if ($driver === 'SQLite3') {
                $res = $db->query("PRAGMA index_list('" . $db->prefixTable($table) . "')");
                if ($res === false) {
                    return false;
                }
                foreach ($res->getResultArray() as $row) {
                    if (($row['name'] ?? '') === $indexName) {
                        return true;
                    }
                }
                return false;
            }
        } catch (\Throwable $e) {
            log_message('error', '[migrate] gagal memeriksa index ' . $indexName . ': ' . $e->getMessage());
        }

        return false;
    }
}
