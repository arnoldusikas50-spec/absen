<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menormalkan id_jabatan_guru menjadi "{id_guru}-{id_tp}-{id_smt}".
 *
 * Kunci lama dibentuk tanpa pemisah sehingga dua kombinasi berbeda bisa
 * menghasilkan kunci yang sama (mis. guru 1/TP 61/SMT 0 dan guru 16/TP 1/SMT 0).
 * Baris ganda per guru+periode (bila pernah terjadi) digabung agar penugasan
 * mapel/ekstra tidak terpecah, lalu indeks unik menjaga invariant tersebut.
 */
class NormalizeJabatanGuruKeys extends Migration
{
    private const UNIQUE_INDEX = 'uniq_jabatan_guru_periode';

    public function up()
    {
        if (!$this->db->tableExists('jabatan_guru')) {
            return;
        }

        $rows = $this->db->table('jabatan_guru')
            ->orderBy('id_jabatan_guru', 'ASC')
            ->get()->getResultArray();

        $groups = [];
        foreach ($rows as $row) {
            $groupKey = (int) ($row['id_guru'] ?? 0) . '|'
                . (int) ($row['id_tp'] ?? 0) . '|'
                . (int) ($row['id_smt'] ?? 0);
            $groups[$groupKey][] = $row;
        }

        $this->db->transBegin();
        try {
            foreach ($groups as $groupRows) {
                $this->normalizeGroup($groupRows);
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $this->ensureUniqueIndex();
    }

    public function down()
    {
        if (!$this->db->tableExists('jabatan_guru')) {
            return;
        }

        // Kunci tidak dikembalikan ke format lama; hanya lepas indeks unik.
        try {
            $this->forge->dropKey('jabatan_guru', self::UNIQUE_INDEX);
        } catch (\Throwable $e) {
            // Indeks memang tidak ada.
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function normalizeGroup(array $rows): void
    {
        $newKey = (int) $rows[0]['id_guru'] . '-' . (int) $rows[0]['id_tp'] . '-' . (int) $rows[0]['id_smt'];

        $keeper = $rows[0];
        foreach ($rows as $row) {
            if ((string) $row['id_jabatan_guru'] === $newKey) {
                $keeper = $row;
                break;
            }
        }

        // Wali kelas diutamakan agar status wali tidak hilang saat penggabungan.
        $wali = null;
        foreach ($rows as $row) {
            if ((string) ($row['id_jabatan'] ?? '') === '4' && (int) ($row['id_kelas'] ?? 0) > 0) {
                $wali = $row;
                break;
            }
        }

        $mapels = [];
        $ekstras = [];
        foreach ($rows as $row) {
            $this->mergeAssignments($mapels, $row['mapel_kelas'] ?? null, 'id_mapel', 'kelas_mapel');
            $this->mergeAssignments($ekstras, $row['ekstra_kelas'] ?? null, 'id_ekstra', 'kelas_ekstra');
        }

        $payload = [
            'id_jabatan_guru' => $newKey,
            'id_guru' => (int) $keeper['id_guru'],
            'id_jabatan' => $wali ? (string) $wali['id_jabatan'] : (string) ($keeper['id_jabatan'] ?? ''),
            'id_kelas' => $wali ? (int) $wali['id_kelas'] : (int) ($keeper['id_kelas'] ?? 0),
            'mapel_kelas' => json_encode(array_values($mapels)),
            'ekstra_kelas' => json_encode(array_values($ekstras)),
            'id_tp' => (int) $keeper['id_tp'],
            'id_smt' => (int) $keeper['id_smt'],
        ];

        foreach ($rows as $row) {
            if ((string) $row['id_jabatan_guru'] === (string) $keeper['id_jabatan_guru']) {
                continue;
            }
            $this->db->table('jabatan_guru')
                ->where('id_jabatan_guru', $row['id_jabatan_guru'])
                ->delete();
        }

        $this->db->table('jabatan_guru')
            ->where('id_jabatan_guru', $keeper['id_jabatan_guru'])
            ->update($payload);
    }

    /**
     * Gabungkan daftar kelas per mapel/ekstra tanpa duplikasi.
     *
     * @param array<int, array<string, mixed>> $target
     */
    private function mergeAssignments(array &$target, $raw, string $idField, string $kelasField): void
    {
        foreach ($this->decodeAssignments($raw) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = (int) ($item[$idField] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if (!isset($target[$id])) {
                $target[$id] = $item;
                $target[$id][$kelasField] = [];
            }

            $existing = [];
            foreach ((array) ($target[$id][$kelasField] ?? []) as $kelas) {
                $kelasId = (int) (is_array($kelas) ? ($kelas['kelas'] ?? 0) : 0);
                if ($kelasId > 0) {
                    $existing[$kelasId] = true;
                }
            }

            foreach ((array) ($item[$kelasField] ?? []) as $kelas) {
                $kelasId = (int) (is_array($kelas) ? ($kelas['kelas'] ?? 0) : 0);
                if ($kelasId > 0 && !isset($existing[$kelasId])) {
                    $target[$id][$kelasField][] = ['kelas' => $kelasId];
                    $existing[$kelasId] = true;
                }
            }
        }
    }

    private function decodeAssignments($raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = @unserialize($raw, ['allowed_classes' => false]);
        if ($decoded === false && $raw !== 'b:0;') {
            $decoded = json_decode($raw, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function ensureUniqueIndex(): void
    {
        try {
            foreach ($this->db->getIndexData('jabatan_guru') as $name => $index) {
                if ($name === self::UNIQUE_INDEX) {
                    return;
                }
            }

            $this->forge->addKey(['id_guru', 'id_tp', 'id_smt'], false, true, self::UNIQUE_INDEX);
            $this->forge->processIndexes('jabatan_guru');
        } catch (\Throwable $e) {
            log_message('warning', 'NormalizeJabatanGuruKeys: indeks unik dilewati - ' . $e->getMessage());
        }
    }
}
