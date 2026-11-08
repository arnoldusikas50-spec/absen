<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan `kategori_cuti` pada `absensi_izin`.
 *
 * Sebelumnya semua jenis `cuti` generik dan hanya memotong kuota cuti
 * tahunan (`max_cuti_hari`). Sesuai Peraturan Presiden tentang hak cuti
 * PNS, jenis `cuti` kini dibedakan menjadi kategori:
 *
 *  - 'tahunan'    : cuti tahunan (12 hari kerja; kuota `max_cuti_hari`)
 *  - 'besar'      : cuti besar (min 5 tahun mengabdi, maks 3 bulan,
 *                   tidak boleh memotong cuti tahunan tahun berjalan)
 *  - 'melahirkan' : cuti melahirkan (anak ke-1..3, maks 3 bulan)
 *  - 'cap'        : cuti karena alasan penting (maks 1 bulan)
 *
 * NULL = cuti generik lama (belum berkategori / eligible non-PNS).
 */
class AddKategoriCuti extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_izin')) {
            return;
        }
        if ($db->fieldExists('kategori_cuti', 'absensi_izin')) {
            return;
        }

        \Config\Database::forge()->addColumn('absensi_izin', [
            'kategori_cuti' => [
                'type'    => 'VARCHAR',
                'constraint' => 30,
                'null'    => true,
                'default' => null,
                'after'   => 'jenis',
            ],
        ]);
        $db->resetDataCache();
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_izin') || ! $db->fieldExists('kategori_cuti', 'absensi_izin')) {
            return;
        }

        \Config\Database::forge()->dropColumn('absensi_izin', 'kategori_cuti');
        $db->resetDataCache();
    }
}
