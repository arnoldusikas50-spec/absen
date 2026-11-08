<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixAlumniTables extends Migration
{
    public function up()
    {
        // 1. Handle ALUMNI table
        if (!$this->db->tableExists('alumni')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nama' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                ],
                'nisn' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'tahun_lulus' => [
                    'type' => 'YEAR',
                    'null' => true,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                ],
                'no_hp' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'tempat_lahir' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'tanggal_lahir' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'jenis_kelamin' => [
                    'type' => 'ENUM',
                    'constraint' => ['L', 'P'],
                    'null' => true,
                ],
                'alamat' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'jurusan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'foto' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'comment' => '0: Pending, 1: Verified'
                ],
                'token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'tracer_filled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'tracer_filled_at' => [
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
            $this->forge->addKey('email');
            $this->forge->addUniqueKey('token');
            $this->forge->createTable('alumni', true);
        } else {
            // Table exists, ensure columns exist
            $fields = [
                'token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'unique' => true, // Use unique here for addColumn
                ],
                'tracer_filled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'tracer_filled_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ];

            foreach ($fields as $field => $def) {
                if (!$this->db->fieldExists($field, 'alumni')) {
                    // Try catch to avoid errors if column exists but fieldExists failed (rare)
                    try {
                        $this->forge->addColumn('alumni', [$field => $def]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        }

        // 2. Handle TRACER_STUDY_RESPONSES table
        if (!$this->db->tableExists('tracer_study_responses')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_alumni' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'nama' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
                'tahun_lulus' => [
                    'type' => 'YEAR',
                    'null' => true,
                ],
                'status_kegiatan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'nama_institusi' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'bidang_pekerjaan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'jabatan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'gaji_range' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'waktu_tunggu_kerja' => [
                    'type' => 'INT',
                    'constraint' => 3,
                    'null' => true,
                    'comment' => 'Months'
                ],
                'kesesuaian_bidang' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'pendidikan_lanjut' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'nama_kampus' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'program_studi' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
                'kepuasan_sekolah' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'comment' => '1-5 rating'
                ],
                'saran' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'jawaban_detail' => [
                    'type' => 'JSON',
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
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
            $this->forge->addKey('id_alumni');
            $this->forge->addKey('email');
            $this->forge->addKey('tahun_lulus');
            $this->forge->addKey('status_kegiatan');
            $this->forge->createTable('tracer_study_responses', true);
        }

        // 3. Handle TRACER_STUDY_SETTINGS table
        if (!$this->db->tableExists('tracer_study_settings')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'value' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'description' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
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
            $this->forge->addUniqueKey('key');
            $this->forge->createTable('tracer_study_settings', true);

            // Insert default settings
            $this->db->table('tracer_study_settings')->insertBatch([
                [
                    'key' => 'is_active',
                    'value' => '1',
                    'description' => 'Enable/disable tracer study form',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'key' => 'welcome_message',
                    'value' => 'Selamat datang di Tracer Study. Bantu kami meningkatkan kualitas pendidikan.',
                    'description' => 'Welcome message on tracer study page',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'key' => 'require_verification',
                    'value' => '0',
                    'description' => 'Require email verification for alumni registration',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    public function down()
    {
        // No drop, too risky for shared tables
    }
}
