<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tandai pembatalan otomatis pada surat teguran.
 *
 * Sebelumnya 'dibatalkan' tidak membedakan dua hal yang berbeda arah:
 *
 *   - SURAT GUGUR karena alpha turun di bawah ambang. Alasan ini bisa dicabut:
 *     izin yang tadinya disetujui lalu dibatalkan operator, atau absen
 *     manual yang tadinya mengisi hari alpha lalu dikoreksi balik. Kalau
 *     alpha kembali naik, suratnya harus hidup lagi.
 *
 *   - SURAT DIBATALKAN OPERATOR. Keputusan administratif yang final; sistem
 *     tidak boleh menimpanya.
 *
 * Tanpa penanda, sinkronisasi hanya bisa membatalkan — tidak pernah
 * menghidupkan kembali. Efeknya: guru yang alpha-nya kembali di atas ambang
 * kehilangan suratnya secara permanen, karena slot (guru, bulan, tahun,
 * level) masih terisi baris yang dibatalkan dan tidak akan pernah terbit
 * ulang.
 *
 * Kolom ini TIDAK menghapus atau mengubah data yang ada. Baris lama yang
 * statusnya 'dibatalkan' tidak bisa dibedakan asal-usulnya, jadi sengaja
 * dibiarkan 0: diperlakukan sebagai keputusan operator. Mengaktifkan
 * kembali semuanya tanpa bukti berisiko membatalkan keputusan administratif
 * yang memang disengaja.
 */
class AddBatalOtomatisToSuratTeguran extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_surat_teguran')) {
            return;
        }

        if ($db->fieldExists('dibatalkan_otomatis', 'absensi_surat_teguran')) {
            return;
        }

        // 'after' diabaikan SQLite, jadi urutannya tidak penting di sana —
        // yang penting MySQL karena kolom ini dibaca admin lewat SELECT *.
        $this->forge->addColumn('absensi_surat_teguran', [
            'dibatalkan_otomatis' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => 0,
                'after'      => 'keterangan_batal',
                'comment'    => '1 = dibatalkan otomatis karena alpha turun, bisa dihidupkan lagi',
            ],
        ]);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_surat_teguran')) {
            return;
        }

        if (! $db->fieldExists('dibatalkan_otomatis', 'absensi_surat_teguran')) {
            return;
        }

        $this->forge->dropColumn('absensi_surat_teguran', 'dibatalkan_otomatis');
    }
}
