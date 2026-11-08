<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\DapodikClient;
use App\Services\DapodikSyncService;

class Cron extends BaseController
{
    /**
     * Sinkronisasi Dapodik otomatis via HTTP (untuk crontab server atau web-cron).
     *
     * Contoh crontab:
     *   * /30 * * * * curl -s "https://absensi.ikas.my.id/cron/dapodik_sync?key=SECRET_KEY" > /dev/null
     *
     * Hanya berjalan jika 'Auto Sync' aktif di pengaturan Integrasi Dapodik.
     */
    public function dapodik_sync()
    {
        // Validasi secret key — mencegah akses publik sembarangan
        $key       = $this->request->getGet('key') ?? '';
        $raw       = env('CRON_SECRET_KEY');
        $raw       = is_string($raw) ? trim($raw) : '';

        if ($raw === '') {
            // Fail-closed: tanpa key, jangan pernah menerima nilai tebakan
            // publik. Endpoint cron mengubah data absensi/poin.
            log_message('critical', '[Cron::dapodik_sync] CRON_SECRET_KEY kosong di .env — request ditolak.');
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Forbidden']);
        }

        if (! hash_equals($raw, (string) $key)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Forbidden']);
        }

        $db = \Config\Database::connect();
        $autoSync = 0;
        if ($db->tableExists('dapodik_settings')) {
            $autoSync = (int) ($db->table('dapodik_settings')->get()->getRowArray()['auto_sync'] ?? 0);
        }

        if (!$autoSync) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Auto-sync Dapodik nonaktif.',
            ]);
        }

        try {
            $client  = new DapodikClient();
            $service = new DapodikSyncService();
            $result  = $service->syncAllFromApi($client, null);

            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            log_message('error', '[Cron::dapodik_sync] Gagal: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => 'Sinkronisasi Dapodik gagal — periksa log aplikasi.',
            ]);
        }
    }
}
