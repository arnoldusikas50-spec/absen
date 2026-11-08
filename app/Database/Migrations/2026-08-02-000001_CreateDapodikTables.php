<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDapodikTables extends Migration
{
    public function up()
    {
        // ── 1. Tabel dapodik_settings ──────────────────────────────────────────
        if (!$this->db->tableExists('dapodik_settings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'server_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'default'    => 'http://localhost:5774',
                ],
                'npsn' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'secret_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'semester_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'null'       => true,
                    'default'    => '20251',
                ],
                'auto_sync' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'last_sync_at' => [
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
            $this->forge->createTable('dapodik_settings', true);

            // Insert default row
            $this->db->table('dapodik_settings')->insert([
                'server_url'   => 'http://localhost:5774',
                'npsn'         => '',
                'secret_token' => '',
                'semester_id'  => '20251',
                'auto_sync'    => 0,
                'is_active'    => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        // ── 2. Tabel dapodik_log ───────────────────────────────────────────────
        if (!$this->db->tableExists('dapodik_log')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'entitas' => [
                    'type'       => 'ENUM',
                    'constraint' => ['all', 'sekolah', 'gtk', 'siswa', 'rombel', 'mapel', 'pembelajaran', 'nilai'],
                    'default'    => 'all',
                ],
                'metode' => [
                    'type'       => 'ENUM',
                    'constraint' => ['api', 'offline_json', 'offline_excel'],
                    'default'    => 'api',
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['sukses', 'gagal', 'parsial'],
                    'default'    => 'sukses',
                ],
                'total_diperoses' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'total_ditambah' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'total_diperbarui' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'keterangan' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('dapodik_log', true);
        }

        // ── 3. Kolom Tambahan di Tabel Master ──────────────────────────────────
        if ($this->db->tableExists('master_siswa')) {
            $fields = [];
            if (!$this->db->fieldExists('dapo_id', 'master_siswa')) {
                $fields['dapo_id'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'id_siswa',
                ];
            }
            if (!empty($fields)) {
                $this->forge->addColumn('master_siswa', $fields);
            }
        }

        if ($this->db->tableExists('master_guru')) {
            $fields = [];
            if (!$this->db->fieldExists('dapo_id', 'master_guru')) {
                $fields['dapo_id'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'id_guru',
                ];
            }
            if (!$this->db->fieldExists('ptk_id', 'master_guru')) {
                $fields['ptk_id'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'dapo_id',
                ];
            }
            if (!empty($fields)) {
                $this->forge->addColumn('master_guru', $fields);
            }
        }

        if ($this->db->tableExists('master_kelas')) {
            $fields = [];
            if (!$this->db->fieldExists('dapo_id', 'master_kelas')) {
                $fields['dapo_id'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'id_kelas',
                ];
            }
            if (!empty($fields)) {
                $this->forge->addColumn('master_kelas', $fields);
            }
        }

        if ($this->db->tableExists('master_mapel')) {
            $fields = [];
            if (!$this->db->fieldExists('dapo_id', 'master_mapel')) {
                $fields['dapo_id'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'id_mapel',
                ];
            }
            if (!empty($fields)) {
                $this->forge->addColumn('master_mapel', $fields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('dapodik_settings') && $this->db->fieldExists('is_active', 'dapodik_settings')) {
            $this->forge->dropColumn('dapodik_settings', 'is_active');
        }
        $this->forge->dropTable('dapodik_settings', true);
        $this->forge->dropTable('dapodik_log', true);

        if ($this->db->tableExists('master_siswa') && $this->db->fieldExists('dapo_id', 'master_siswa')) {
            $this->forge->dropColumn('master_siswa', 'dapo_id');
        }
        if ($this->db->tableExists('master_guru')) {
            if ($this->db->fieldExists('dapo_id', 'master_guru')) $this->forge->dropColumn('master_guru', 'dapo_id');
            if ($this->db->fieldExists('ptk_id', 'master_guru')) $this->forge->dropColumn('master_guru', 'ptk_id');
        }
        if ($this->db->tableExists('master_kelas') && $this->db->fieldExists('dapo_id', 'master_kelas')) {
            $this->forge->dropColumn('master_kelas', 'dapo_id');
        }
        if ($this->db->tableExists('master_mapel') && $this->db->fieldExists('dapo_id', 'master_mapel')) {
            $this->forge->dropColumn('master_mapel', 'dapo_id');
        }
    }
}
