<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * absensi_setting diperlakukan singleton oleh seluruh kode, tetapi setiap
 * `spark migrate` yang berjalan ulang melewati migrasi pembuatnya dan
 * menyisipkan baris default baru. Karena itu tabel pernah berisi 3 baris
 * dengan jam masuk, hari libur, dan denda alpha yang berbeda, dan pembacaan
 * tanpa ORDER BY mengambil baris mana pun.
 *
 * Dipakai oleh migrasi dan command CLI: di instance yang tracker migrasinya
 * tidak selaras, `spark migrate` tidak bisa dijalankan, jadi pembersihan
 * harus bisa dipanggil sendiri.
 */
class AbsensiSettingSingleton
{
    private const BACKUP_TABLE = 'absensi_setting_dup_backup';
    private const GUARD_COLUMN = 'singleton_guard';
    private const GUARD_KEY    = 'uq_absensi_setting_single_row';

    /**
     * @return array{archived: list<int>, guard: string}
     */
    public static function enforce(BaseConnection $db): array
    {
        $archived = [];
        $guard    = 'skip';

        if (! $db->tableExists('absensi_setting')) {
            return ['archived' => $archived, 'guard' => 'no-table'];
        }

        $rows = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getResultArray();

        $extras = array_slice($rows, 1);
        if ($extras !== []) {
            // Sekali saja: tableExists() CI4 di-cache per request, jadi membuat
            // tabel di dalam loop akan mencoba CREATE dua kali.
            $db->query('CREATE TABLE IF NOT EXISTS `' . self::BACKUP_TABLE . '` LIKE `absensi_setting`');
            self::stripGuardFromBackup($db);
            foreach ($extras as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $archived[] = self::archiveThenDelete($db, $row, $id);
            }
        }

        $guard = self::addGuard($db);

        return ['archived' => array_values(array_filter($archived)), 'guard' => $guard];
    }

    /** Kembalikan baris yang pernah diarsipkan (untuk down() / uji coba). */
    public static function restore(BaseConnection $db): int
    {
        self::dropGuard($db);

        if (! $db->tableExists(self::BACKUP_TABLE)) {
            return 0;
        }

        $existing = array_column($db->table('absensi_setting')->get()->getResultArray(), 'id');
        $moved    = 0;
        foreach ($db->table(self::BACKUP_TABLE)->orderBy('id', 'ASC')->get()->getResultArray() as $row) {
            if (! in_array($row['id'], $existing, true)) {
                unset($row[self::GUARD_COLUMN]);
                $db->table('absensi_setting')->insert($row);
                $moved++;
            }
        }

        return $moved;
    }

    private static function archiveThenDelete(BaseConnection $db, array $row, int $id): int
    {
        if (! $db->table(self::BACKUP_TABLE)->where('id', $id)->countAllResults()) {
            unset($row[self::GUARD_COLUMN]);
            $db->table(self::BACKUP_TABLE)->insert($row);
        }
        $db->table('absensi_setting')->where('id', $id)->delete();

        log_message('warning', '[absensi_setting] baris duplikat id ' . $id
            . ' (jam_masuk ' . ($row['jam_masuk'] ?? '?') . ', hari libur ' . ($row['hari_libur_mingguan'] ?? '?')
            . ', denda alpha ' . ($row['denda_alpha_per_hari'] ?? '?') . ') diarsipkan ke ' . self::BACKUP_TABLE);

        return $id;
    }

    /**
     * Kolom generated konstan 1 + UNIQUE index: MySQL menolak baris kedua dari
     * jalur tulis mana pun. Dilewati bila server terlalu tua agar proses
     * pembersihan tidak gagal total.
     */
    private static function addGuard(BaseConnection $db): string
    {
        if ($db->fieldExists(self::GUARD_COLUMN, 'absensi_setting')) {
            return 'already';
        }

        try {
            $db->query(
                'ALTER TABLE `absensi_setting`
                    ADD COLUMN `' . self::GUARD_COLUMN . '` TINYINT GENERATED ALWAYS AS (1) STORED,
                    ADD UNIQUE KEY `' . self::GUARD_KEY . '` (`' . self::GUARD_COLUMN . '`)'
            );
            return 'installed';
        } catch (\Throwable $e) {
            log_message('error', '[absensi_setting] guard single-row tidak terpasang: ' . $e->getMessage());
            return 'unsupported';
        }
    }

    /**
     * CREATE TABLE ... LIKE menyalin kolom generated beserta UNIQUE index-nya,
     * jadi tabel arsip juga hanya mau satu baris. Unik di tabel sumber bahkan
     * membuat arsip baris kedua gagal, jadi guard dilepas dari salinan.
     */
    private static function stripGuardFromBackup(BaseConnection $db): void
    {
        foreach ([
            'ALTER TABLE `' . self::BACKUP_TABLE . '` DROP INDEX `' . self::GUARD_KEY . '`',
            'ALTER TABLE `' . self::BACKUP_TABLE . '` DROP COLUMN `' . self::GUARD_COLUMN . '`',
        ] as $sql) {
            try {
                $db->query($sql);
            } catch (\Throwable $e) {
                // Guard memang tidak ikut tersalin (mis. tabel arsip dibuat
                // sebelum guard ada) — tidak ada yang perlu dilepas.
            }
        }
    }

    private static function dropGuard(BaseConnection $db): void
    {
        if (! $db->tableExists('absensi_setting') || ! $db->fieldExists(self::GUARD_COLUMN, 'absensi_setting')) {
            return;
        }

        try {
            $db->query(
                'ALTER TABLE `absensi_setting`
                    DROP INDEX `' . self::GUARD_KEY . '`,
                    DROP COLUMN `' . self::GUARD_COLUMN . '`'
            );
        } catch (\Throwable $e) {
            log_message('error', '[absensi_setting] guard single-row gagal dilepas: ' . $e->getMessage());
        }
    }
}
