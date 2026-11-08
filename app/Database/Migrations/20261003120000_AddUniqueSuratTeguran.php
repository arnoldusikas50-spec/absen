<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cegah surat teguran ganda untuk guru yang sama.
 *
 * Cron memeriksa `COUNT(*) == 0` baru INSERT. Pemeriksaan dan penulisan itu
 * dua operasi terpisah, jadi dua request /cron/daily yang datang bersamaan
 * bisa sama-sama lolos pemeriksaan lalu sama-sama INSERT. Akibatnya guru
 * mendapat dua surat teguran (dan dua notifikasi) untuk satu bintang
 * alpha, dan nomor urut jadi bentrok.
 *
 * UNIQUE di bawah ini yang menutup celahnya di level database: satu saja
 * dari dua INSERT yang berebut bisa berhasil.
 *
 *(Cancellation tidak dihapus dari unique key: batal = surat pernah terbit
 * dan notifikasi sudah dikirim. Menjadikannya boleh terbit ulang akan
 * membuka pengulangan notifikasi, bukan mencegah duplikat.)
 */
class AddUniqueSuratTeguran extends Migration
{
    /** Nama index, dipotong ke batas MySQL bila perlu. */
    private const INDEX = 'uniq_surat_teguran_guru_periode';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_surat_teguran')) {
            return;
        }

        $indexName = self::INDEX;
        if (strlen($indexName) > 60) {
            $indexName = substr($indexName, 0, 60);
        }

        // Jangan berlindedkan index yang sudah ada — migrasi bisa dijalankan
        // ulang dan menambahkan index kedua hanya memperlambat INSERT.
        if ($this->sudahAdaIndex($db, 'absensi_surat_teguran', $indexName)) {
            return;
        }

        // Kolom yang di-index harus ada. Versi lama tabel mungkin belum punya
        // salah satunya.
        foreach (['id_guru', 'bulan', 'tahun', 'level'] as $col) {
            if (! $db->fieldExists($col, 'absensi_surat_teguran')) {
                return;
            }
        }

        // Data lama yang sudah ganda harus dibersihkan lebih dulu, kalau
        // tidak ADD UNIQUE akan gagal dan migrasi menggagalkan seluruh batch.
        $duplikat = $db->query(
            'SELECT id_guru, bulan, tahun, level, COUNT(*) AS jml, MIN(id) AS pertahankan '
            . 'FROM ' . $db->prefixTable('absensi_surat_teguran') . ' '
            . 'GROUP BY id_guru, bulan, tahun, level HAVING jml > 1'
        )->getResult();

        $dibuang = 0;
        foreach ($duplikat as $d) {
            // Baris paling lama dipertahankan: surat yang lebih dulu terbit
            // adalah yang tercatat di arsip sekolah.
            $hapus = $db->table('absensi_surat_teguran')
                ->where('id_guru', $d->id_guru)
                ->where('bulan', $d->bulan)
                ->where('tahun', $d->tahun)
                ->where('level', $d->level)
                ->where('id !=', $d->pertahankan)
                ->get()
                ->getResult();

            foreach ($hapus as $row) {
                $db->table('absensi_surat_teguran')->where('id', $row->id)->delete();
                $dibuang++;
            }
        }

        // PENTING: addUniqueKey() hanya mengisi array internal; ia tidak
        // pernah menjalankan SQL untuk tabel yang sudah ada. Yang
        // mengeksekusinya adalah processIndexes() (dipanggil addColumn()/
        // createTable()). Karena tabel ini sudah ada dan tidak ada kolom
        // yang ditambah, processIndexes() harus dipanggil eksplisit —
        // tanpa itu migrasi dilaporkan "berhasil" padahal tidak ada index
        // sama sekali, dan race condition tetap terbuka.
        $this->forge->addUniqueKey(
            ['id_guru', 'bulan', 'tahun', 'level'],
            $indexName
        );
        $this->forge->processIndexes('absensi_surat_teguran');

        if (! $this->sudahAdaIndex($db, 'absensi_surat_teguran', $indexName)) {
            // Jangan diam-diam lanjut: index yang gagal berarti penjaga
            // idempotensi surat teguran tidak aktif.
            throw new \RuntimeException(
                'Unique key ' . $indexName . ' gagal dibuat pada absensi_surat_teguran.'
            );
        }

        if ($dibuang > 0) {
            log_message('warning', '[migrate] AddUniqueSuratTeguran: menghapus ' . $dibuang
                . ' baris surat teguran ganda sebelum memasang unique key.');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_surat_teguran')) {
            return;
        }

        $indexName = self::INDEX;
        if (strlen($indexName) > 60) {
            $indexName = substr($indexName, 0, 60);
        }

        if ($this->sudahAdaIndex($db, 'absensi_surat_teguran', $indexName)) {
            $this->forge->dropKey('absensi_surat_teguran', $indexName);
        }
    }

    /**
     * CI tidak menyediakan pemeriksaan index lintas-database yang andal, jadi
     * ditanyakan langsung ke skema.
     */
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
                foreach ($db->listFields($table) as $ignored) {
                    // tidak dipakai; placeholder agar cabang tidak kosong
                    break;
                }
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

            if ($driver === 'Postgre') {
                $res = $db->query(
                    'SELECT 1 FROM pg_indexes WHERE tablename = ' . $db->escape($table)
                    . ' AND indexname = ' . $db->escape($indexName)
                );
                return $res !== false && $res->getNumRows() > 0;
            }
        } catch (\Throwable $e) {
            log_message('error', '[migrate] gagal memeriksa index ' . $indexName . ': ' . $e->getMessage());
        }

        // Tidak diketahui: biarkan ADD UNIQUE yang memutuskan. Kalau index-nya
        // sudah ada, migrasi gagal dengan pesan yang jelas — lebih baik
        // daripada diam-diam melewati penjaga.
        return false;
    }
}