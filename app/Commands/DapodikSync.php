<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\DapodikClient;
use App\Services\DapodikSyncService;

class DapodikSync extends BaseCommand
{
    protected $group       = 'Dapodik';
    protected $name        = 'dapodik:sync';
    protected $description = 'Sinkronisasi data dari Dapodik Web Service via CLI / Cron';
    protected $usage       = 'dapodik:sync [all|siswa|rombel|gtk|mapel|sekolah|jurusan]';

    public function run(array $params)
    {
        $entitas = $params[0] ?? 'all';
        $force   = array_key_exists('force', $params) || in_array('--force', $params, true);

        // Saat dijalankan oleh cron, hormati pengaturan auto_sync (kecuali dengan --force)
        $db = \Config\Database::connect();
        $autoSync = 0;
        if ($db->tableExists('dapodik_settings')) {
            $row = $db->table('dapodik_settings')->get()->getRowArray();
            $autoSync = (int) ($row['auto_sync'] ?? 0);
            $isActive = (int) ($row['is_active'] ?? 0);
        }
        if (!$force && (!$autoSync || !$isActive)) {
            CLI::write("Auto-sync Dapodik nonaktif. Aktifkan 'Auto Sync' dan 'Integration Web Service' di menu Integrasi Dapodik, atau jalankan dengan --force.", 'yellow');
            return;
        }

        $client  = new DapodikClient();
        $service = new DapodikSyncService();

        CLI::write("=== Dapodik Sync: {$entitas} ===", 'yellow');

        switch ($entitas) {
            case 'siswa':
                $res = $service->syncSiswaFromApi($client, null);
                break;
            case 'rombel':
                $res = $service->syncRombelFromApi($client, null);
                break;
            case 'gtk':
                $res = $service->syncGtkFromApi($client, null);
                break;
            case 'mapel':
                $res = $service->syncMapelFromApi($client, null);
                break;
            case 'jurusan':
                $rb = $client->getRombonganBelajar();
                $items = [];
                if ($rb['status'] && !empty($rb['data'])) {
                    $rows = $rb['data']['rows'] ?? $rb['data'];
                    if (is_array($rows)) $items = $rows;
                }
                if (empty($items)) {
                    $res = ['status' => false, 'message' => 'Tidak ada data rombel untuk sinkronisasi jurusan.'];
                } else {
                    $res = $service->syncJurusanFromRombel($items, null);
                }
                break;
            case 'sekolah':
                $res = $service->syncSekolahFromApi($client, null);
                break;
            case 'all':
            default:
                $res = $service->syncAllFromApi($client, null);
                break;
        }

        if ($res['status']) {
            CLI::write($res['message'], 'green');
        } else {
            CLI::write($res['message'], 'red');
        }

        // Show summary
        $db = \Config\Database::connect();
        $guruCount  = $db->tableExists('master_guru') ? $db->table('master_guru')->countAllResults() : 0;
        $siswaCount = $db->tableExists('master_siswa') ? $db->table('master_siswa')->countAllResults() : 0;
        $kelasCount = $db->tableExists('master_kelas') ? $db->table('master_kelas')->countAllResults() : 0;
        $mapelCount = $db->tableExists('master_mapel') ? $db->table('master_mapel')->countAllResults() : 0;

        CLI::newLine();
        CLI::write("=== Status Akhir Database Presensi & KBM ===", 'yellow');
        CLI::write("Guru: {$guruCount} | Siswa: {$siswaCount} | Kelas: {$kelasCount} | Mapel: {$mapelCount}", 'cyan');
    }
}
