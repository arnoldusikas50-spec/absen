<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penanda "pulang tidak tercatat".
 *
 * 28% baris presensi siswa pada 30 hari terakhir berstatus HADIR dengan
 * jam_keluar kosong dan tidak pernah diselesaikan siapa pun, jadi rekap tidak
 * bisa membedakan "sudah lewat jam pulang dan memang tidak scan" dari "belum
 * sempat". Cron menutup buku pulang per hari dan menyimpan faktanya di kolom
 * ini — jam_keluar tetap NULL, tidak ada jam karangan.
 *
 * `pulang_close_dari_tgl` di absensi_setting dipakai sebagai watermark supaya
 * cron pertama tidak mengubah ribuan baris historis menjadi "tidak tercatat".
 */
class AddPulangTidakTercatatToPresensiSiswa extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('presensi_siswa')
            && ! $this->db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) {
            $this->forge->addColumn('presensi_siswa', [
                'pulang_tidak_tercatat' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
        }

        if ($this->db->tableExists('absensi_setting')
            && ! $this->db->fieldExists('pulang_close_dari_tgl', 'absensi_setting')) {
            $this->forge->addColumn('absensi_setting', [
                'pulang_close_dari_tgl' => [
                    'type'    => 'DATE',
                    'null'    => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_setting')
            && $this->db->fieldExists('pulang_close_dari_tgl', 'absensi_setting')) {
            $this->forge->dropColumn('absensi_setting', 'pulang_close_dari_tgl');
        }

        if ($this->db->tableExists('presensi_siswa')
            && $this->db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) {
            $this->forge->dropColumn('presensi_siswa', 'pulang_tidak_tercatat');
        }
    }
}
