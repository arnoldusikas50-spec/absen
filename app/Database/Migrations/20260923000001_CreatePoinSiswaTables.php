<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Modul Poin Kedisiplinan Siswa
 * - disiplin_kategori  : master baku pelanggaran/penghargaan + bobot poin
 * - poin_siswa_history : ledger per id_siswa (skor = 100 + SUM(poin))
 * - master_siswa.poin_disiplin : cache skor (default 100)
 */
class CreatePoinSiswaTables extends Migration
{
    public function up()
    {
        // Master kategori (hanya admin yang kelola)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'jenis' => ['type' => "ENUM('PELANGGARAN','PENGHARGAAN')", 'default' => 'PELANGGARAN'],
            'bobot' => ['type' => 'INT', 'constraint' => 11, 'default' => 5, 'comment' => 'Besar poin (positif); arah +/- ikut jenis'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('jenis');
        $this->forge->addKey('is_active');
        $this->forge->createTable('disiplin_kategori', true);

        // Ledger poin siswa
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_siswa' => ['type' => 'INT', 'constraint' => 11],
            'id_kelas' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'poin' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => 'Negatif untuk pelanggaran, positif untuk penghargaan'],
            'kategori_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'tipe' => ['type' => "ENUM('PELANGGARAN','PENGHARGAAN','KOREKSI','PRESENSI')", 'default' => 'PELANGGARAN'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ref_id' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'unique' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'comment' => 'users.id pencatat'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('id_siswa');
        $this->forge->addKey('tipe');
        $this->forge->addKey('created_at');
        $this->forge->createTable('poin_siswa_history', true);

        // Cache skor di master_siswa (default 100)
        if ($this->db->tableExists('master_siswa') && !$this->db->fieldExists('poin_disiplin', 'master_siswa')) {
            $this->forge->addColumn('master_siswa', [
                'poin_disiplin' => ['type' => 'INT', 'constraint' => 11, 'default' => 100, 'after' => 'foto'],
            ]);
        }

        $this->seedDefaultKategori();
    }

    public function down()
    {
        $this->forge->dropTable('poin_siswa_history', true);
        $this->forge->dropTable('disiplin_kategori', true);
        if ($this->db->tableExists('master_siswa') && $this->db->fieldExists('poin_disiplin', 'master_siswa')) {
            $this->forge->dropColumn('master_siswa', 'poin_disiplin');
        }
    }

    /**
     * Seed kategori baku awal (idempoten).
     */
    private function seedDefaultKategori(): void
    {
        if (!$this->db->tableExists('disiplin_kategori')) {
            return;
        }
        $defaults = [
            ['Terlambat masuk sekolah', 'PELANGGARAN', 5],
            ['Tidak memakai seragam lengkap/atribut', 'PELANGGARAN', 5],
            ['Tidak mengerjakan tugas/PR', 'PELANGGARAN', 5],
            ['Keluar kelas tanpa izin', 'PELANGGARAN', 10],
            ['Merokok di lingkungan sekolah', 'PELANGGARAN', 25],
            ['Berkelahi / tawuran', 'PELANGGARAN', 50],
            ['Bolos sekolah', 'PELANGGARAN', 25],
            ['Membawa HP tanpa izin', 'PELANGGARAN', 10],
            ['Juara lomba / prestasi akademik', 'PENGHARGAAN', 20],
            ['Prestasi non-akademik / ekstrakurikuler', 'PENGHARGAAN', 15],
            ['Membantu kegiatan sekolah', 'PENGHARGAAN', 10],
            ['Hafalan / prestasi tahfidz', 'PENGHARGAAN', 15],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($defaults as [$nama, $jenis, $bobot]) {
            $exists = $this->db->table('disiplin_kategori')
                ->where('LOWER(nama)', strtolower($nama))
                ->countAllResults() > 0;
            if (!$exists) {
                $this->db->table('disiplin_kategori')->insert([
                    'nama' => $nama, 'jenis' => $jenis, 'bobot' => $bobot,
                    'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }
}
