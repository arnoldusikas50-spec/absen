<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Satu baris per siswa per TP/SMT di kelas_siswa.
 *
 * Mencegah /datasiswa menampilkan siswa dobel akibat dua baris
 * (kunci lama vs kunci baru) untuk kombinasi yang sama.
 */
class KelasSiswaUnique extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('kelas_siswa')) {
            return;
        }

        // Bersihkan sisa duplikat lama (pertahankan id terkecil).
        try {
            for ($i = 0; $i < 20; $i++) {
                $dupes = $this->db->query(
                    'SELECT id_tp, id_smt, id_siswa, MIN(id_kelas_siswa) AS keep_id, COUNT(*) AS c
                     FROM kelas_siswa GROUP BY id_tp, id_smt, id_siswa HAVING c > 1 LIMIT 500'
                )->getResult();
                if (empty($dupes)) break;
                foreach ($dupes as $d) {
                    $this->db->query(
                        'DELETE FROM kelas_siswa WHERE id_tp = ? AND id_smt = ? AND id_siswa = ? AND id_kelas_siswa <> ?',
                        [(int) $d->id_tp, (int) $d->id_smt, (int) $d->id_siswa, $d->keep_id]
                    );
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[KelasSiswaUnique] dedupe: ' . $e->getMessage());
        }

        // UNIQUE (id_tp, id_smt, id_siswa) bila belum ada.
        try {
            $has = false;
            foreach ($this->uniqueIndexMap('kelas_siswa') as $cols) {
                if ($cols === ['id_siswa', 'id_smt', 'id_tp']) {
                    $has = true;
                    break;
                }
            }
            if (!$has) {
                $this->db->query('ALTER TABLE kelas_siswa ADD UNIQUE KEY `uq_ks_tp_smt_siswa` (`id_tp`, `id_smt`, `id_siswa`)');
            }
        } catch (\Throwable $e) {
            log_message('error', '[KelasSiswaUnique] add unique: ' . $e->getMessage());
        }
    }

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
        // UNIQUE dipertahankan saat rollback agar lubang dobel tak terbuka lagi.
    }
}
