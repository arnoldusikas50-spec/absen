<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Jalankan pembersihan baris duplikat absensi_setting tanpa `spark migrate`.
 * Diperlukan karena pada beberapa instance tracker migrasi tidak selaras
 * dengan skema (schema dibuat lewat import SQL), sehingga `spark migrate`
 * menjalankan ulang seluruh migrasi dan berhenti di tengah.
 */
class SingleSettingRow extends BaseCommand
{
    protected $group       = 'Absensi';
    protected $name        = 'absensi:single-setting';
    protected $description = 'Arsipkan & hapus baris duplikat absensi_setting, lalu pasang guard single-row.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('absensi_setting')) {
            CLI::error('Tabel absensi_setting tidak ada.');
            return;
        }

        $before = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getResultArray();
        CLI::write('Baris sebelum: ' . count($before));
        foreach ($before as $r) {
            CLI::write(sprintf(
                '  id=%-3s jam_masuk=%-9s hari_libur=%-8s denda_alpha=%-7s updated=%s',
                $r['id'] ?? '?',
                $r['jam_masuk'] ?? '?',
                $r['hari_libur_mingguan'] ?? '?',
                $r['denda_alpha_per_hari'] ?? '?',
                $r['updated_at'] ?? '?'
            ));
        }

        if (in_array('--restore', $params, true)) {
            $moved = \App\Services\AbsensiSettingSingleton::restore($db);
            CLI::write("Baris dipulihkan dari arsip: {$moved}", 'yellow');
            return;
        }

        $result = \App\Services\AbsensiSettingSingleton::enforce($db);

        CLI::write(
            $result['archived'] === []
                ? 'Tidak ada baris duplikat.'
                : 'Baris duplikat diarsipkan & dihapus: id ' . implode(', ', $result['archived']),
            $result['archived'] === [] ? 'green' : 'yellow'
        );
        CLI::write([
            'installed' => 'Guard single-row terpasang (baris kedua akan ditolak MySQL).',
            'already'   => 'Guard single-row sudah terpasang.',
            'unsupported' => 'Guard tidak terpasang — server tidak mendukung generated column.',
            'no-table'  => 'Tabel tidak ada.',
            'skip'      => 'Guard dilewati.',
        ][$result['guard']] ?? $result['guard'], $result['guard'] === 'unsupported' ? 'red' : 'green');

        $after = $db->table('absensi_setting')->countAllResults();
        CLI::write("Baris sesudah: {$after}");
    }
}
