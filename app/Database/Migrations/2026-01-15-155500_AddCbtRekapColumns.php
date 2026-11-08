<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to add missing columns to cbt_rekap_siswa table
 * 
 * These columns are needed for:
 * - id_kelas: Filtering siswa by kelas in ekspor rekap
 * - nama_siswa: Snapshot nama siswa (in case master_siswa is deleted)
 * - no_peserta: Nomor peserta ujian for export purposes
 */
class AddCbtRekapColumns extends Migration
{
    public function up()
    {
        // Helper function to check if table exists
        $tables = $this->db->listTables();
        $hasRekapSiswa = in_array('cbt_rekap_siswa', $tables);
        $hasRekapJadwal = in_array('cbt_rekap_jadwal', $tables);

        if (!$hasRekapSiswa) {
            // Create the table if it doesn't exist
            $this->forge->addField([
                'id_rekap_siswa' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_siswa' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'id_jadwal' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'id_bank' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'id_kelas' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'nama_siswa' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'no_peserta' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'jawaban' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'nilai' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'nilai_input' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'mulai' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'selesai' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'pelanggaran' => [
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
            $this->forge->addPrimaryKey('id_rekap_siswa');
            $this->forge->addKey('id_siswa');
            $this->forge->addKey('id_jadwal');
            $this->forge->addKey('id_kelas');
            $this->forge->addKey(['id_jadwal', 'id_siswa']);
            $this->forge->createTable('cbt_rekap_siswa', true);
        } else {
            // Get existing columns
            $existingFields = $this->db->getFieldNames('cbt_rekap_siswa');
            $fields = [];

            // Check and add id_kelas column
            if (!in_array('id_kelas', $existingFields)) {
                $fields['id_kelas'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'id_bank',
                ];
            }

            // Check and add nama_siswa column
            if (!in_array('nama_siswa', $existingFields)) {
                $fields['nama_siswa'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'id_kelas',
                ];
            }

            // Check and add no_peserta column
            if (!in_array('no_peserta', $existingFields)) {
                $fields['no_peserta'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'nama_siswa',
                ];
            }

            if (!empty($fields)) {
                $this->forge->addColumn('cbt_rekap_siswa', $fields);
            }

            // Add index for id_kelas if not exists
            // Note: CodeIgniter migration doesn't have great support for checking existing indexes
            // We'll use try-catch to handle if index already exists
            try {
                $this->db->query('ALTER TABLE cbt_rekap_siswa ADD INDEX idx_id_kelas (id_kelas)');
            } catch (\Exception $e) {
                // Index might already exist, ignore the error
            }
        }

        // Ensure cbt_rekap_jadwal table has proper columns
        if ($hasRekapJadwal) {
            $existingFields = $this->db->getFieldNames('cbt_rekap_jadwal');
            $fields = [];

            // Check and add created_at column
            if (!in_array('created_at', $existingFields)) {
                $fields['created_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }

            // Check and add updated_at column
            if (!in_array('updated_at', $existingFields)) {
                $fields['updated_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }

            if (!empty($fields)) {
                $this->forge->addColumn('cbt_rekap_jadwal', $fields);
            }
        }
    }

    public function down()
    {
        $tables = $this->db->listTables();

        // Remove columns if they exist
        if (in_array('cbt_rekap_siswa', $tables)) {
            $existingFields = $this->db->getFieldNames('cbt_rekap_siswa');

            if (in_array('id_kelas', $existingFields)) {
                $this->forge->dropColumn('cbt_rekap_siswa', 'id_kelas');
            }
            if (in_array('nama_siswa', $existingFields)) {
                $this->forge->dropColumn('cbt_rekap_siswa', 'nama_siswa');
            }
            if (in_array('no_peserta', $existingFields)) {
                $this->forge->dropColumn('cbt_rekap_siswa', 'no_peserta');
            }
        }

        if (in_array('cbt_rekap_jadwal', $tables)) {
            $existingFields = $this->db->getFieldNames('cbt_rekap_jadwal');

            if (in_array('created_at', $existingFields)) {
                $this->forge->dropColumn('cbt_rekap_jadwal', 'created_at');
            }
            if (in_array('updated_at', $existingFields)) {
                $this->forge->dropColumn('cbt_rekap_jadwal', 'updated_at');
            }
        }
    }
}
