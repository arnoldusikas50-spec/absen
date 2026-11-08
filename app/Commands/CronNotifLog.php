<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Riwayat pengiriman notifikasi cron.
 *
 *   php spark cron:notif-log            # 20 baris terakhir
 *   php spark cron:notif-log 50         # 50 baris terakhir
 *   php spark cron:notif-log 30 gagal   # hanya yang gagal
 *
 * Cron.log hanya berisi teks yang dikembalikan endpoint, dan teks itu
 * tidak punya bentuk yang bisa dipilah. Tabel cron_notifikasi_log yang
 * menyimpan penyebab kegagalannya, jadi ini cara memastikannya kelihatan
 * tanpa harus membuka file log di server.
 */
class CronNotifLog extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'cron:notif-log';
    protected $description = 'Tampilkan riwayat pengiriman notifikasi cron';
    protected $usage       = 'cron:notif-log [jumlah=20] [gagal|ok]';

    public function run(array $params)
    {
        $limit = (int) ($params[0] ?? 20);
        if ($limit < 1) {
            $limit = 20;
        }
        $limit = min($limit, 500);

        $filter = strtolower(trim((string) ($params[1] ?? '')));
        $where  = null;
        if ($filter === 'gagal' || $filter === 'fail') {
            $where = ['terkirim', 0];
        } elseif ($filter === 'ok' || $filter === 'sukses') {
            $where = ['terkirim', 1];
        }

        $db = \Config\Database::connect();

        if (! $db->tableExists('cron_notifikasi_log')) {
            CLI::error('Tabel cron_notifikasi_log belum ada. Jalankan: php spark migrate');
            return;
        }

        $builder = $db->table('cron_notifikasi_log')
            ->select('jenis, periode, terkirim, percobaan, catatan, updated_at')
            ->orderBy('updated_at', 'DESC')
            ->limit($limit);

        if ($where !== null) {
            $builder->where($where[0], $where[1]);
        }

        $rows = $builder->get()->getResult();

        if ($rows === []) {
            CLI::write('Belum ada riwayat pengiriman.', 'yellow');
            return;
        }

        CLI::write(sprintf('Riwayat notifikasi cron — %d baris (terbaru dulu)', count($rows)));
        CLI::write('');

        foreach ($rows as $r) {
            $status = ((int) $r->terkirim) === 1 ? 'OK   ' : 'GAGAL';
            $waktu  = substr((string) $r->updated_at, 0, 16);
            $ket    = $r->catatan !== null && $r->catatan !== ''
                ? ' — ' . mb_strimwidth((string) $r->catatan, 0, 60, '…')
                : '';

            CLI::write(sprintf(
                '%s  %-17s %-22s percobaan=%-3d %s%s',
                $status,
                $r->jenis,
                $r->periode,
                (int) $r->percobaan,
                $waktu,
                $ket
            ));
        }

        $gagal = array_filter($rows, static fn($r) => (int) $r->terkirim !== 1);
        CLI::write('');

        if ($gagal !== []) {
            CLI::write(count($gagal) . ' dari ' . count($rows) . ' gagal pada tampilan ini.', 'yellow');
        }
    }
}
