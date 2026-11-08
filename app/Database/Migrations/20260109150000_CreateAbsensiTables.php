<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbsensiTables extends Migration
{
    public function up()
    {
        // Table absensi_setting
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'jam_masuk' => [
                'type' => 'TIME',
                'default' => '07:00:00',
            ],
            'jam_pulang' => [
                'type' => 'TIME',
                'default' => '15:00:00',
            ],
            'radius_meter' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 50,
            ],
            'foto_wajib' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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

        // Table absensi_lokasi
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_lokasi' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'lat' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
            ],
            'lng' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
            ],
            'radius_meter' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 50,
            ],
            'keterangan' => [
                'type' => 'TEXT',
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
        $this->forge->createTable('absensi_lokasi', true);

        // Table absensi_harian
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'id_guru' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'tgl' => [
                'type' => 'DATE',
            ],
            'jam_masuk' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lat_masuk' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'lng_masuk' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'lokasi_masuk_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'jarak_masuk' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'foto_masuk' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_masuk' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'jam_pulang' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lat_pulang' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'lng_pulang' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'lokasi_pulang_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'jarak_pulang' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'foto_pulang' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_pulang' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
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
        $this->forge->createTable('absensi_harian', true);

        // Table absensi_izin
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'id_guru' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'tgl_mulai' => [
                'type' => 'DATE',
            ],
            'tgl_selesai' => [
                'type' => 'DATE',
            ],
            'jenis' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'default' => 'pending',
            ],
            'approved_by' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->createTable('absensi_izin', true);

        // Seed default setting — hanya bila tabel masih kosong. Tabel dibuat
        // dengan IF NOT EXISTS, jadi migrate yang dijalankan ulang akan sampai
        // ke titik ini lagi dan tanpa guard ini menambah baris kedua.
        if ($this->db->table('absensi_setting')->countAllResults() === 0) {
            $this->db->table('absensi_setting')->insert([
                'jam_masuk' => '07:00:00',
                'jam_pulang' => '15:00:00',
                'radius_meter' => 50,
                'foto_wajib' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('absensi_setting', true);
        $this->forge->dropTable('absensi_lokasi', true);
        $this->forge->dropTable('absensi_harian', true);
        $this->forge->dropTable('absensi_izin', true);
    }
}
