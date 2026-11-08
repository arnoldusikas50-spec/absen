<?php

use CodeIgniter\Database\Migration;

/**
 * Membersihkan baris duplikat pada absensi_setting dan memasang constraint
 * single-row. Logikanya ada di App\Services\AbsensiSettingSingleton agar bisa
 * dijalankan lewat `php spark absensi:single-setting` pada instance yang
 * tracker migrasinya tidak selaras dengan skema.
 */
class Migration_EnforceSingleAbsensiSettingRow extends Migration
{
    public function up()
    {
        \App\Services\AbsensiSettingSingleton::enforce($this->db);
    }

    public function down()
    {
        \App\Services\AbsensiSettingSingleton::restore($this->db);
    }
}
