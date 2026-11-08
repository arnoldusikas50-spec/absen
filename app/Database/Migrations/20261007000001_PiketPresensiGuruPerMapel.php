<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Presensi guru per slot mapel + audit pencatat.
 *
 * - UNIQUE (tanggal,id_kelas,jam_ke) → UNIQUE (tanggal,id_kelas,jam_ke,id_mapel)
 *   agar dua mapel berbeda di jam yang sama tak saling menimpa/menolak.
 * - Tambah created_by / updated_by (id user pencatat).
 */
class PiketPresensiGuruPerMapel extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('piket_presensi_guru')) {
            return;
        }

        // 1. Drop unique lama (tanggal,id_kelas,jam_ke) — nama index bervariasi.
        try {
            foreach ($this->uniqueIndexMap('piket_presensi_guru') as $key => $cols) {
                if ($key === 'PRIMARY') continue;
                if ($cols === ['id_kelas', 'jam_ke', 'tanggal']) {
                    $this->db->query("ALTER TABLE piket_presensi_guru DROP INDEX `{$key}`");
                    break;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[PiketPresensiGuruPerMapel] drop old unique: ' . $e->getMessage());
        }

        // 2. Unique baru per mapel (best-effort bila belum ada).
        try {
            $has = false;
            foreach ($this->uniqueIndexMap('piket_presensi_guru') as $cols) {
                if ($cols === ['id_kelas', 'id_mapel', 'jam_ke', 'tanggal']) {
                    $has = true;
                    break;
                }
            }
            if (!$has) {
                $this->db->query('ALTER TABLE piket_presensi_guru ADD UNIQUE KEY `uq_ppg_slot` (`tanggal`, `id_kelas`, `jam_ke`, `id_mapel`)');
            }
        } catch (\Throwable $e) {
            log_message('error', '[PiketPresensiGuruPerMapel] add unique: ' . $e->getMessage());
        }

        // 3. Kolom audit pencatat.
        $add = [];
        foreach ([
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ] as $col => $def) {
            if (!$this->db->fieldExists($col, 'piket_presensi_guru')) {
                $add[$col] = $def;
            }
        }
        if (!empty($add)) {
            $this->forge->addColumn('piket_presensi_guru', $add);
        }
    }

    /**
     * Peta index UNIQUE saja: nama => kolom terurut. Satu pass SHOW INDEX.
     */
    private function uniqueIndexMap(string $table): array
    {
        $map = [];
        foreach ($this->db->query("SHOW INDEX FROM {$table}")->getResult() as $r) {
            if ((int) ($r->Non_unique ?? 1) !== 0) continue;
            $key = (string) ($r->Key_name ?? '');
            if ($key === '') continue;
            $map[$key][] = (string) ($r->Column_name ?? '');
        }
        foreach ($map as $key => $cols) {
            sort($cols);
            $map[$key] = array_values($cols);
        }
        return $map;
    }

    public function down()
    {
        if (!$this->db->tableExists('piket_presensi_guru')) {
            return;
        }
        // Unique baru dipertahankan (lebih ketat & benar); rollback hanya
        // menghapus kolom audit bila diminta.
        foreach (['created_by', 'updated_by'] as $col) {
            try {
                if ($this->db->fieldExists($col, 'piket_presensi_guru')) {
                    $this->forge->dropColumn('piket_presensi_guru', $col);
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
