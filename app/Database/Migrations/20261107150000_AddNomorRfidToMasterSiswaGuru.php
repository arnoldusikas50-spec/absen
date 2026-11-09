<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Integrasi kartu RFID absensi.
 *
 * Menambahkan kolom `nomor_rfid` (UID kartu RFID) pada master_siswa dan
 * master_guru agar UID yang dibaca RFID reader dapat dipetakan ke
 * pengguna untuk check-in / check-out via endpoint scanner.
 *
 * Kolom diisi manual via halaman edit data siswa / guru (admin), lalu
 * dicetak pada kartu RFID fisik. Endpoint scan mencocokkan `rfid_value`
 * terhadap kolom ini sebelum mencatat presensi.
 */
class AddNomorRfidToMasterSiswaGuru extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // master_siswa
        if ($db->tableExists('master_siswa') && !$db->fieldExists('nomor_rfid', 'master_siswa')) {
            $this->forge->addColumn('master_siswa', [
                'nomor_rfid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'unique'     => true,
                    'comment'    => 'UID kartu RFID siswa (diisi admin, dicetak pada kartu fisik)',
                ],
            ]);
        }

        // master_guru
        if ($db->tableExists('master_guru') && !$db->fieldExists('nomor_rfid', 'master_guru')) {
            $this->forge->addColumn('master_guru', [
                'nomor_rfid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'unique'     => true,
                    'comment'    => 'UID kartu RFID guru/GTK (diisi admin, dicetak pada kartu fisik)',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('master_siswa') && $db->fieldExists('nomor_rfid', 'master_siswa')) {
            $this->forge->dropColumn('master_siswa', 'nomor_rfid');
        }

        if ($db->tableExists('master_guru') && $db->fieldExists('nomor_rfid', 'master_guru')) {
            $this->forge->dropColumn('master_guru', 'nomor_rfid');
        }
    }
}
