<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Satu baris per siswa per hari + audit sumber scan.
 *
 * - Deduplikasi baris ganda (siswa+tgl di >1 kelas): pertahankan id terkecil.
 * - Ganti UNIQUE (id_kelas,id_siswa,tgl) → UNIQUE (id_siswa,tgl).
 * - Tambah kolom audit: sumber_masuk/sumber_keluar ('qr'|'manual'), ip_masuk/ip_keluar.
 */
class EnforceSatuBarisPerSiswaPerHari extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('presensi_siswa')) {
            return;
        }

        // 1. Deduplikasi bertahap (500 grup/batch, maks 20 batch) agar UNIQUE baru
        // tak gagal bila data ganda menumpuk. Pertahankan id terkecil.
        try {
            for ($i = 0; $i < 20; $i++) {
                $dupes = $this->db->query(
                    "SELECT id_siswa, tgl, MIN(id) AS keep_id, COUNT(*) AS c
                     FROM presensi_siswa GROUP BY id_siswa, tgl HAVING c > 1 LIMIT 500"
                )->getResult();
                if (empty($dupes)) break;
                foreach ($dupes as $d) {
                    $this->db->query(
                        "DELETE FROM presensi_siswa WHERE id_siswa = ? AND tgl = ? AND id <> ?",
                        [(int) $d->id_siswa, $d->tgl, (int) $d->keep_id]
                    );
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[EnforceSatuBarisPerSiswaPerHari] dedupe: ' . $e->getMessage());
        }

        // 2. Drop unique lama (nama bervariasi antar instalasi: uq_ps / unik_presensi_siswa).
        try {
            foreach ($this->uniqueIndexMap('presensi_siswa') as $key => $cols) {
                if ($key !== 'PRIMARY' && $cols === ['id_kelas', 'id_siswa', 'tgl']) {
                    $this->db->query("ALTER TABLE presensi_siswa DROP INDEX `{$key}`");
                    break;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[EnforceSatuBarisPerSiswaPerHari] drop old unique: ' . $e->getMessage());
        }

        // 3. Tambah UNIQUE (id_siswa,tgl) bila belum ada.
        try {
            $has = false;
            foreach ($this->uniqueIndexMap('presensi_siswa') as $cols) {
                if ($cols === ['id_siswa', 'tgl']) {
                    $has = true;
                    break;
                }
            }
            if (!$has) {
                $this->db->query("ALTER TABLE presensi_siswa ADD UNIQUE KEY `uq_ps_siswa_tgl` (`id_siswa`, `tgl`)");
            }
        } catch (\Throwable $e) {
            log_message('error', '[EnforceSatuBarisPerSiswaPerHari] add unique: ' . $e->getMessage());
        }

        // 4. Index bantu filter kelas+tanggal (best-effort).
        try {
            $this->db->query("ALTER TABLE presensi_siswa ADD INDEX `idx_ps_kelas_tgl` (`id_kelas`, `tgl`)");
        } catch (\Throwable $e) {
            // kemungkinan sudah ada — abaikan
        }

        // 5. Kolom audit sumber & IP.
        $add = [];
        $defs = [
            'sumber_masuk' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'sumber_keluar' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'ip_masuk'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'ip_keluar'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
        ];
        foreach ($defs as $col => $def) {
            if (!$this->db->fieldExists($col, 'presensi_siswa')) {
                $add[$col] = $def;
            }
        }
        if (!empty($add)) {
            $this->forge->addColumn('presensi_siswa', $add);
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
        if (!$this->db->tableExists('presensi_siswa')) {
            return;
        }
        foreach (['sumber_masuk', 'sumber_keluar', 'ip_masuk', 'ip_keluar'] as $col) {
            try {
                if ($this->db->fieldExists($col, 'presensi_siswa')) {
                    $this->forge->dropColumn('presensi_siswa', $col);
                }
            } catch (\Throwable $e) {
            }
        }
        // UNIQUE baru dipertahankan saat rollback agar tidak membuka kembali lubang duplikat.
    }
}
