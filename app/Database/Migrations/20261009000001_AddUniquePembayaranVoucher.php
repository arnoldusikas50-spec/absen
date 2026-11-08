<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cegah satu voucher dipakai untuk dua baris pembayaran denda.
 *
 * `Guru\Reward::applyVoucher()` memakai pola cek-status-lalu-update dalam
 * transaksi. Sejak Fase 1B klaimnya sudah atomik
 * (`WHERE status=APPROVED` + `affectedRows`), tetapi tanpa unique key di
 * database dua request yang menyelesaikan transaksi dalam urutan sial masih
 * bisa sama-sama INSERT ke `absensi_pembayaran_denda` untuk satu
 * `reward_redemption_id` yang sama.
 *
 * UNIQUE di bawah menutup celahnya di level database: INSERT kedua gagal
 * dengan duplicate-entry dan transaksi di-rollback.
 *
 * Catatan: TIDAK ada unique key (user_id, bulan, tahun) di tabel ini —
 * satu bulan legitimately punya >1 baris (cicilan sebagian+lunas, voucher
 * lalu tunai top-up, satu pembayaran FIFO mencakup banyak bulan). Audit
 * 2026-10-09 menemukan 8 grup (user,bulan,tahun,status) berisi 2-3 baris
 * lunas yang valid, jadi unique key per bulan akan memecahkan alur
 * pembayaran yang sah.
 *
 * MySQL mengizinkan banyak NULL pada kolom UNIQUE, jadi baris TUNAI/SALDO
 * (reward_redemption_id NULL) tidak terpengaruh.
 */
class AddUniquePembayaranVoucher extends Migration
{
    private const INDEX = 'uniq_pembayaran_voucher_redemption';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_pembayaran_denda')) {
            return;
        }
        if (! $db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda')) {
            return;
        }

        $indexName = self::INDEX;
        if ($this->sudahAdaIndex($db, 'absensi_pembayaran_denda', $indexName)) {
            return;
        }

        // Bersihkan duplikat lama bila ada (seharusnya 0 baris): pertahankan
        // baris tertua per redemption, sisanya dibatalkan (bukan dihapus agar
        // jejak audit utuh dan getSisaDendaPerBulan yang memfilter
        // status!=batal tetap konsisten).
        $duplikat = $db->query(
            'SELECT reward_redemption_id, COUNT(*) AS jml, MIN(id) AS pertahankan '
            . 'FROM ' . $db->prefixTable('absensi_pembayaran_denda') . ' '
            . 'WHERE reward_redemption_id IS NOT NULL '
            . 'GROUP BY reward_redemption_id HAVING jml > 1'
        )->getResult();

        $dibatalkan = 0;
        foreach ($duplikat as $d) {
            $db->table('absensi_pembayaran_denda')
                ->where('reward_redemption_id', $d->reward_redemption_id)
                ->where('id !=', $d->pertahankan)
                ->update(['status' => 'batal', 'updated_at' => date('Y-m-d H:i:s')]);
            $dibatalkan += $db->affectedRows();
        }

        $this->forge->addUniqueKey(['reward_redemption_id'], $indexName);
        $this->forge->processIndexes('absensi_pembayaran_denda');

        if (! $this->sudahAdaIndex($db, 'absensi_pembayaran_denda', $indexName)) {
            throw new \RuntimeException(
                'Unique key ' . $indexName . ' gagal dibuat pada absensi_pembayaran_denda.'
            );
        }

        if ($dibatalkan > 0) {
            log_message('warning', '[migrate] AddUniquePembayaranVoucher: membatalkan ' . $dibatalkan
                . ' baris pembayaran voucher ganda sebelum memasang unique key.');
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
