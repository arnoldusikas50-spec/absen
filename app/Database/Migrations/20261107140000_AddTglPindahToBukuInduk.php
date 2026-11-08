<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom tanggal peristiwa untuk aksi lintas kelas di buku induk.
 *
 * Jalur bulk-admin Datasiswa::delete() (aksi 'pindah'/'keluar') menulis
 * `tgl_pindah = hari ini` saat menandai siswa 3 (pindah) atau 4 (keluar),
 * tetapi tabel buku_induk belum punya kolom itu — hasilnya endpoint
 * /datasiswa/delete menjawab 500 dengan
 * "Unknown column 'tgl_pindah' in 'SET'" (kasus 7 Nov 2026, REF e536a1)
 * dan status buku induk siswa tidak berubah.
 *
 * Kolom baru TANPA mengubah data lama: baris pindah/keluar yang sudah
 * tercatat sebelum perbaikan ini memang tidak bisa menebak tanggalnya —
 * biarkan NULL daripada mengisi tanggal yang mungkin salah.
 */
class AddTglPindahToBukuInduk extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('buku_induk')) {
            return;
        }

        if ($db->fieldExists('tgl_pindah', 'buku_induk')) {
            return;
        }

        $this->forge->addColumn('buku_induk', [
            'tgl_pindah' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal diterbitkan status pindah (3) / keluar (4)',
            ],
        ]);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('buku_induk')) {
            return;
        }

        if (! $db->fieldExists('tgl_pindah', 'buku_induk')) {
            return;
        }

        $this->forge->dropColumn('buku_induk', 'tgl_pindah');
    }
}
