<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pencatatan "guru paling awal" per hari.
 *
 * Ada 6 jalur yang menulis jam_masuk, dan setiap jalur memanggil service
 * pengumuman setelah absensi tersimpan. Tanpa penanda ini, guru kedua yang
 * absen pada menit yang sama akan memicu pengumuman ulang untuk orang yang
 * sama.
 *
 * UNIQUE(tgl) adalah penjaga terakhir: dua request yang datang bersamaan
 * bisa sama-sama lolos pemeriksaan SELECT, tetapi hanya satu yang berhasil
 * INSERT.
 */
class CreateAbsensiPengumumanHarianTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_pengumuman_harian')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tgl' => [
                'type' => 'DATE',
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nama_guru' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'jam_masuk' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
            'dm_terkirim' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '1 bila DM personal ke guru tersebut berhasil',
            ],
            'grup_terkirim' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '1 bila pengumuman grup berhasil',
            ],
            'catatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Alasan DM gagal, mis. guru belum menautkan Telegram',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tgl');
        $this->forge->addKey('user_id');
        $this->forge->createTable('absensi_pengumuman_harian');
    }

    public function down()
    {
        if ($this->db->tableExists('absensi_pengumuman_harian')) {
            $this->forge->dropTable('absensi_pengumuman_harian');
        }
    }
}
