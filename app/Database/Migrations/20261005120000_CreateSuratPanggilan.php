<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Surat panggilan orang tua/wali siswa.
 *
 * Berbeda dengan `absensi_surat_teguran` (guru, warisan impor CI3), tabel
 * ini dibuat dari nol untuk kebutuhan siswa. Isinya snapshot saat surat
 * terbit — `jumlah_alpha` dan `jumlah_terlambat` sengaja tidak dihitung
 * ulang saat PDF dicetak ulang, supaya arsip tidak berubah padahal data
 * absensinya sudah diedit atau disalink.
 *
 * Satu siswa bisa punya satu surat per tingkat dalam satu bulan. Unique key
 * (id_siswa, bulan, tahun, level) yang membuat cron tidak menulis
 * duplikat: cron harian dievaluasi 4x sebulan, dan tanpa unique index
 * setiap evaluasi menulis baris yang sama lagi.
 *
 * Kolom `sumber` menyimpan asal surat:
 *   - 'cron'   : terbit otomatis karena alpha melewati ambang
 *   - 'manual' : dibuat admin dari halaman menu
 *
 * Dipakai untuk membedakan mana yang boleh terbit otomatis lagi dan mana
 * yang murni keputusan admin.
 */
class CreateSuratPanggilan extends Migration
{
    /** Nama index unik, dipotong ke batas MySQL bila perlu. */
    private const INDEX_UNIQ = 'uniq_panggilan_siswa_periode';

    public function up(): void
    {
        $namaIndex = self::INDEX_UNIQ;
        if (strlen($namaIndex) > 60) {
            $namaIndex = substr($namaIndex, 0, 60);
        }
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_siswa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'master_siswa.id_siswa',
            ],
            'bulan' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'comment'    => '1-12',
            ],
            'tahun' => [
                'type'       => 'SMALLINT',
                'constraint' => 4,
                'unsigned'   => true,
            ],
            'level' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'comment'    => 'Ambang alpha yang memicu surat (3/5/7)',
            ],
            'jumlah_alpha' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => 'Snapshot total alpha saat surat terbit',
            ],
            'jumlah_terlambat' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => 'Snapshot jumlah keterlambatan saat surat terbit',
            ],
            'nomor_urut' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'sumber' => [
                'type'       => 'VARCHAR',
                'constraint' => 12,
                'default'    => 'cron',
                'comment'    => "cron | manual",
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'aktif',
                'comment'    => 'aktif | dibatalkan',
            ],
            'keterangan_batal' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Alasan untuk surat manual',
            ],
            'penerima' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'comment'    => 'Snapshot nama orang tua/wali tujuan',
            ],
            'nohp_penerima' => [
                'type'       => 'VARCHAR',
                'constraint' => 25,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        // Uniqueness per siswa/periode/tingkat. Nama index sengaja eksplisit
        // supaya tidak bentrok dengan index unik lain di tabel yang sama.
        // Catatan urutan argumen Forge: addUniqueKey($kolom, $namaIndex).
        $this->forge->addUniqueKey(['id_siswa', 'bulan', 'tahun', 'level'], $namaIndex);
        $this->forge->addKey(['bulan', 'tahun']);
        $this->forge->addKey('nomor_urut');
        $this->forge->createTable('absensi_surat_panggilan', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('absensi_surat_panggilan', true);
    }
}