<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk tabel sinkronisasi absensi by ikasmedia
 */
class CreateSyncTables extends Migration
{
    public function up()
    {
        // Tabel sync_log - Menyimpan riwayat sinkronisasi
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('sync_log', true);

        // Tabel sync_settings - Menyimpan pengaturan sinkronisasi
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('setting_key', false, true); // Unique key
        $this->forge->createTable('sync_settings', true);

        // Tabel sync_jobs - Menyimpan antrian job sinkronisasi
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'payload' => [
                'type' => 'JSON',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'max_attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 3,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'result' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'available_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'finished_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey(['status', 'available_at']);
        $this->forge->createTable('sync_jobs', true);

        // Tambah kolom synced dan synced_at ke cbt_jadwal jika belum ada
        // (lewati bila tabel modul absensi by ikasmedia tidak ada di DB ini)
        $db = \Config\Database::connect();

        if ($db->tableExists('cbt_jadwal')) {
            // Check and add synced column
            if (!$db->fieldExists('synced', 'cbt_jadwal')) {
            $this->forge->addColumn('cbt_jadwal', [
                'synced' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => true,
                    'after' => 'status',
                ],
            ]);
        }

        // Check and add synced_at column
        if (!$db->fieldExists('synced_at', 'cbt_jadwal')) {
            $this->forge->addColumn('cbt_jadwal', [
                'synced_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'synced',
                ],
            ]);
        }
        }
    }

    public function down()
    {
        // Drop sync tables
        $this->forge->dropTable('sync_log', true);
        $this->forge->dropTable('sync_settings', true);
        $this->forge->dropTable('sync_jobs', true);

        // Remove columns from cbt_jadwal
        $db = \Config\Database::connect();

        if ($db->fieldExists('synced', 'cbt_jadwal')) {
            $this->forge->dropColumn('cbt_jadwal', 'synced');
        }

        if ($db->fieldExists('synced_at', 'cbt_jadwal')) {
            $this->forge->dropColumn('cbt_jadwal', 'synced_at');
        }
    }
}
