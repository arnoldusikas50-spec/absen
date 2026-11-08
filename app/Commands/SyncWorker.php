<?php

namespace App\Commands;

use App\Libraries\SyncService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncWorker extends BaseCommand
{
    protected $group = 'Sync';
    protected $name = 'sync:worker';
    protected $description = 'Menjalankan worker sinkronisasi lokal-pusat secara kontinu atau sekali jalan';

    protected $usage = 'sync:worker [options]';
    protected $arguments = [];
    protected $options = [
        '--once' => 'Jalankan hanya satu job lalu berhenti',
        '--sleep' => 'Waktu tidur antar polling job dalam detik (default: 5)',
    ];

    public function run(array $params)
    {
        $once = CLI::getOption('once') !== null;
        $sleep = (int) (CLI::getOption('sleep') ?? 5);
        $sleep = max(1, $sleep);

        $service = new SyncService();

        CLI::write('=== Sync Worker ===', 'yellow');
        CLI::write('Mode: ' . ($once ? 'single job' : 'daemon')); 
        CLI::write('Sleep interval: ' . $sleep . ' detik');

        try {
            $service->runWorker($once, $sleep);
        } catch (\Throwable $e) {
            CLI::error('Worker error: ' . $e->getMessage());
        }
    }
}
