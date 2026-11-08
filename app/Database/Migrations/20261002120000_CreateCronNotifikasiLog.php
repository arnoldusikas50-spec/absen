<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penjaga pengiriman ulang untuk notifikasi cron.
 *
 * Masalah yang diselesaikan: beberapa endpoint cron mengirim pesan yang
 * sama lebih dari sekali.
 *   - `/cron/daily` (18:00) dan `/cron/daily-recap` keduanya memanggil
 *     sendDailyRecap(). Dokumentasi di CRONTAB.MD justru menyuruh operator
 *     memanggil `/cron/daily-recap` untuk mengirim ulang rekap yang gagal —
 *     jadi kalau pengirimannya kembali dicoba tanpa penjaga, grup menerima
 *     rekap ganda.
 *   -Ucapan pagi bisa diputar ulang lewat `?tanggal=`.
 *   - Rekap denda mingguan/bulanan tidak punya penanda periode sama sekali,
 *     dan `?bulan=&tahun=` tidak divalidasi.
 *
 * Tabel ini mencatat satu baris per (jenis, periode). Keunikan pada kedua
 * kolom itulah penjaganya: dua request bersamaan tidak bisa sama-sama
 * lolos klaim, karena hanya satu INSERT yang akan berhasil.
 *
 * Sifat penting: `terkirim` hanya 1 bila gateway MENERIMA pesan. Kalau
 * pengiriman gagal, barisnya tetap ada dengan `terkirim = 0` sehingga
 * percobaan berikutnya diizinkan — idempotensi di sini berarti "jangan
 * kirim dua kali BERHASIL", bukan "coba sekali selamanya".
 */
class CreateCronNotifikasiLog extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('cron_notifikasi_log')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'jenis' => [
                // rekap_harian | rekap_denda | ucapan_pagi | ucapan_sore
                // | lupa_checkout | kehadiran_rendah
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'periode' => [
                // Bentuknya tergantung jenis: Y-m-d untuk harian,
                // Y-m untuk bulanan, Y-Www untuk mingguan. Disimpan sebagai
                // teks supaya satu tabel bisa melayani semua.
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'terkirim' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '1 bila gateway menerima pesan; 0 = masih boleh dicoba lagi',
            ],
            'percobaan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Berapa kali endpoint ini dipanggil untuk periode ini',
            ],
            'catatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Alasan kegagalan terakhir',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        // Penjaga idempotensi. Tanpa ini dua request bersamaan bisa sama-sama
        // melihat "belum dikirim" lalu sama-sama mengirim.
        $this->forge->addUniqueKey(['jenis', 'periode']);
        $this->forge->addKey('jenis');
        $this->forge->createTable('cron_notifikasi_log');
    }

    public function down()
    {
        if ($this->db->tableExists('cron_notifikasi_log')) {
            $this->forge->dropTable('cron_notifikasi_log');
        }
    }
}
