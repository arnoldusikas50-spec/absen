<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendanceTables extends Migration
{
    public function up()
    {
        /* ------------------ absensi_setting ------------------ */
        $this->forge->addField([
            'id'            => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'jam_masuk'     => [
                'type' => 'TIME',
                'null' => true,
            ],
            'jam_pulang'    => [
                'type' => 'TIME',
                'null' => true,
            ],
            'radius_meter'  => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 100,
            ],
            'foto_wajib'    => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'hari_kerja'    => [
                'type' => 'JSON',
                'null' => true,
            ],
            'libur_kalender' => [
                'type' => 'JSON',
                'null' => true,
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
        $this->forge->addKey('id', true);
        $this->forge->createTable('absensi_setting', true);

        /* ------------------ absensi_lokasi ------------------ */
        $this->forge->addField([
            'id'            => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_lokasi'   => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'lat'           => [
                'type' => 'DOUBLE',
                'null' => false,
            ],
            'lng'           => [
                'type' => 'DOUBLE',
                'null' => false,
            ],
            'radius_meter'  => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 100,
            ],
            'keterangan'    => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('absensi_lokasi', true);

        /* ------------------ absensi_harian ------------------ */
        $this->forge->addField([
            'id'            => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_guru'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tgl'           => [
                'type' => 'DATE',
            ],
            'jam_masuk'     => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lat_masuk'     => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'lng_masuk'     => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'lokasi_masuk_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'jarak_masuk'   => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'foto_masuk'    => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status_masuk'  => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'jam_pulang'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lat_pulang'     => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'lng_pulang'     => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'lokasi_pulang_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'jarak_pulang'   => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'foto_pulang'    => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status_pulang'  => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'created_at'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'tgl']);
        $this->forge->createTable('absensi_harian', true);

        /* ------------------ absensi_izin ------------------ */
        $this->forge->addField([
            'id'            => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_guru'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tgl_mulai'     => [
                'type' => 'DATE',
            ],
            'tgl_selesai'   => [
                'type' => 'DATE',
            ],
            'jenis'         => [
                'type'       => 'ENUM',
                'constraint' => ['ijin', 'cuti'],
            ],
            'keterangan'    => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status'        => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
            ],
            'created_at'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at'    => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('absensi_izin', true);
    }

    public function down()
    {
        $this->forge->dropTable('absensi_izin', true);
        $this->forge->dropTable('absensi_harian', true);
        $this->forge->dropTable('absensi_lokasi', true);
        $this->forge->dropTable('absensi_setting', true);
    }
}
