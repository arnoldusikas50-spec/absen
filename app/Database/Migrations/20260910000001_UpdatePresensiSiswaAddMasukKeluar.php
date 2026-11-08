<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mengubah tabel presensi_siswa agar mencatat jam masuk dan jam keluar harian siswa,
 * mirip dengan sistem absensi guru di tabel absensi_harian.
 *
 * Perubahan:
 * - Tambah kolom jam_masuk (TIME)
 * - Tambah kolom jam_keluar (TIME)
 * - Rename kolom jam → jam_masuk (via tambah kolom baru, salin data, biarkan jam lama)
 * - Kolom id_mapel tetap ada (nullable) untuk backward-compat, tapi tidak dipakai di UI baru
 */
class UpdatePresensiSiswaAddMasukKeluar extends Migration
{
    public function up()
    {
        // Cek apakah kolom sudah ada (aman untuk dijalankan ulang)
        $db = \Config\Database::connect();

        if (!$db->fieldExists('jam_masuk', 'presensi_siswa')) {
            $this->forge->addColumn('presensi_siswa', [
                'jam_masuk' => [
                    'type'    => 'TIME',
                    'null'    => true,
                    'after'   => 'tgl',
                ],
            ]);
        }

        if (!$db->fieldExists('jam_keluar', 'presensi_siswa')) {
            $this->forge->addColumn('presensi_siswa', [
                'jam_keluar' => [
                    'type'  => 'TIME',
                    'null'  => true,
                    'after' => 'jam_masuk',
                ],
            ]);
        }

        // Salin data jam lama ke jam_masuk supaya data existing tidak hilang
        $db->query("UPDATE presensi_siswa SET jam_masuk = jam WHERE jam IS NOT NULL AND jam_masuk IS NULL");
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->fieldExists('jam_masuk', 'presensi_siswa')) {
            // Kembalikan jam_masuk ke kolom jam sebelum drop
            $db->query("UPDATE presensi_siswa SET jam = jam_masuk WHERE jam IS NULL AND jam_masuk IS NOT NULL");
            $this->forge->dropColumn('presensi_siswa', 'jam_masuk');
        }

        if ($db->fieldExists('jam_keluar', 'presensi_siswa')) {
            $this->forge->dropColumn('presensi_siswa', 'jam_keluar');
        }
    }
}
