<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Integrasi mesin sidik jari (fingerprint) GEISA GX330 via HTTP Push.
 *
 * Menambahkan kolom `fingerprint_uid` (UserID mesin sidik jari) pada
 * master_siswa dan master_guru agar data yang dikirim mesin via HTTP POST
 * dapat dipetakan ke pengguna untuk check-in / check-out.
 *
 * Mesin GEISA GX330 dikonfigurasi untuk POST data ke endpoint:
 *   POST /scanner/scan  dengan field fingerprint_uid (atau user_id)
 *
 * Kolom diisi manual via halaman edit data siswa / guru (admin), lalu
 * disamakan dengan UserID yang terdaftar di mesin GEISA.
 */
class AddFingerprintUidToMasterSiswaGuru extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // master_siswa
        if ($db->tableExists('master_siswa') && !$db->fieldExists('fingerprint_uid', 'master_siswa')) {
            $this->forge->addColumn('master_siswa', [
                'fingerprint_uid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'unique'     => true,
                    'comment'    => 'UserID mesin sidik jari (GEISA GX330) siswa, disamakan dengan data mesin',
                ],
            ]);
        }

        // master_guru
        if ($db->tableExists('master_guru') && !$db->fieldExists('fingerprint_uid', 'master_guru')) {
            $this->forge->addColumn('master_guru', [
                'fingerprint_uid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'unique'     => true,
                    'comment'    => 'UserID mesin sidik jari (GEISA GX330) guru/GTK, disamakan dengan data mesin',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('master_siswa') && $db->fieldExists('fingerprint_uid', 'master_siswa')) {
            $this->forge->dropColumn('master_siswa', 'fingerprint_uid');
        }

        if ($db->tableExists('master_guru') && $db->fieldExists('fingerprint_uid', 'master_guru')) {
            $this->forge->dropColumn('master_guru', 'fingerprint_uid');
        }
    }
}
