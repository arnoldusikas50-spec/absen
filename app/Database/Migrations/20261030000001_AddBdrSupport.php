<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dukungan status BDR (Belajar Dari Rumah).
 *
 * Kasus: selama ujian sekolah kelas XII, siswa kelas X dan XI diliburkan dan
 * mengerjakan tugas dari rumah. Mereka tidak scan kehadiran, jadi tanpa
 * penanganan khusus cron malam menandai mereka ALPHA.
 *
 * 1. Tabel baru `presensi_bdr`: jadwal harian per lingkup (SEMUA / TINGKAT /
 *    KELAS) dengan rentang tanggal. Satu baris = satu periode BDR.
 * 2. `presensi_siswa.status`: pada instalasi lama kolomnya
 *    ENUM('HADIR','SAKIT','IZIN','ALPA','TANPA_KETERANGAN') sehingga nilai
 *    'BDR' ditolak MySQL. Dibuat VARCHAR(30) seperti instalasi terbaru.
 */
class AddBdrSupport extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('presensi_bdr')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'lingkup' => [
                    'type'       => 'ENUM',
                    'constraint' => ['SEMUA', 'TINGKAT', 'KELAS'],
                    'default'    => 'KELAS',
                ],
                'id_kelas' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
                'level_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
                'tgl_mulai' => ['type' => 'DATE', 'null' => false],
                'tgl_selesai' => ['type' => 'DATE', 'null' => true, 'default' => null],
                'judul' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'Belajar dari rumah'],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['tgl_mulai', 'tgl_selesai']);
            $this->forge->addKey('id_kelas');
            $this->forge->addKey('level_id');
            $this->forge->createTable('presensi_bdr', true);
        }

        $this->lebarkanVarchar('presensi_siswa', 'status', 30, 'TANPA_KETERANGAN');

        $db->resetDataCache();
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('presensi_bdr')) {
            $this->forge->dropTable('presensi_bdr');
        }

        // Baris BDR dihapus lebih dulu supaya ENUM lama bisa dipasang kembali.
        if ($db->tableExists('presensi_siswa')) {
            $db->table('presensi_siswa')->where('status', 'BDR')->delete();
            $this->lebarkanVarchar(
                'presensi_siswa',
                'status',
                30,
                'TANPA_KETERANGAN',
                "ENUM('HADIR','SAKIT','IZIN','ALPA','TANPA_KETERANGAN')"
            );
        }

        $db->resetDataCache();
    }

    /**
     * Ubah kolom berjenis ENUM (atau VARCHAR terlalu pendek) menjadi VARCHAR
     * selebar yang dibutuhkan. Kolom yang sudah cukup lebar dibiarkan.
     */
    private function lebarkanVarchar(string $tabel, string $kolom, int $lebar, string $default, ?string $turunKe = null): void
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists($tabel) || ! $db->fieldExists($kolom, $tabel)) {
            return;
        }

        $info = $db->query(
            'SELECT COLUMN_TYPE, DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db->database, $db->DBPrefix . $tabel, $kolom]
        )->getRow();
        if (! $info) {
            return;
        }

        $tipe       = strtolower((string) ($info->DATA_TYPE ?? ''));
        $lebarAktual = 0;
        if ($tipe === 'varchar' && preg_match('/^varchar\((\d+)\)$/i', (string) $info->COLUMN_TYPE, $m)) {
            $lebarAktual = (int) $m[1];
        }
        $sudahCukup = $tipe === 'varchar' && $lebarAktual >= $lebar;

        if ($turunKe !== null) {
            if ($sudahCukup) {
                return;
            }
            $definisi = $turunKe;
        } elseif ($tipe === 'enum' || ! $sudahCukup) {
            $definisi = 'VARCHAR(' . $lebar . ')';
        } else {
            return;
        }

        $db->query('ALTER TABLE `' . $db->DBPrefix . $tabel . '` MODIFY `' . $kolom . '` '
            . $definisi . ' NOT NULL DEFAULT ' . $db->escape($default));
    }
}
