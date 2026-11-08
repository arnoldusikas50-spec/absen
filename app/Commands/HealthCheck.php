<?php

namespace App\Commands;

use App\Controllers\Health;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cek kesehatan aplikasi dari cron server dan kirim alert Telegram bila gagal.
 *
 *   php spark health:check
 *
 * Pasang di cron tiap 5 menit:
 *   * / 5 * * * * cd /www/wwwroot/absensi.ikas.my.id && php spark health:check >/dev/null 2>&1
 */
class HealthCheck extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'health:check';
    protected $description = 'Cek database/writable/disk, kirim Telegram bila gagal';

    public function run(array $params)
    {
        $health = new Health();
        $results = [
            'database' => $health->checkDatabase(),
            'writable' => $health->checkWritable(),
            'disk'     => $health->checkDisk(),
        ];

        $failed = array_keys(array_filter($results, static fn($ok) => !$ok));

        if (empty($failed)) {
            CLI::write('OK: database, writable, disk sehat', 'green');
            return 0;
        }

        $msg = '🚨 <b>HEALTH CHECK GAGAL</b>' . "\n"
            . 'Komponen: ' . implode(', ', $failed) . "\n"
            . 'Waktu: ' . date('d/m/Y H:i:s') . "\n"
            . 'Segera periksa server absensi.';

        try {
            // Fallback WA bila TG mati: coba keduanya via method publik.
            $notif = new \App\Services\NotificationService();
            $sentTg = $notif->sendTelegram($msg);
            $sentWa = false;
            try {
                $sentWa = $notif->sendWhatsApp($msg);
            } catch (\Throwable $e2) {
                $sentWa = false;
            }
            $sent = $sentTg || $sentWa;
            CLI::write($sent ? 'Alert terkirim (TG/WA)' : 'Gagal kirim alert TG+WA', $sent ? 'yellow' : 'red');
        } catch (\Throwable $e) {
            CLI::error('Alert gagal: ' . $e->getMessage());
        }

        CLI::error('FAIL: ' . implode(', ', $failed));
        // Exit non-zero agar pola `curl --fail || php spark health:check`
        // dan monitoring cron bisa deteksi gagal (sebelumnya selalu exit 0).
        return 1;
    }
}
