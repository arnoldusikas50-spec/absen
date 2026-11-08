<?php

namespace App\Libraries;

use Config\Sync as SyncConfig;
use Throwable;

/**
 * SyncService - Library untuk sinkronisasi data absensi by ikasmedia
 * 
 * Mode 1 Sekolah:
 * - PULL: Mengambil data dari server pusat ke lokal
 * - PUSH: Mengirim hasil ujian dari lokal ke pusat
 */
class SyncService
{
    protected SyncConfig $config;
    protected $db;
    protected $client;
    protected array $dbSettings = [];
    protected string $jobTable = 'sync_jobs';

    public function __construct()
    {
        $this->config = new SyncConfig();
        $this->db = \Config\Database::connect();

        // Load settings from database
        $this->loadDbSettings();

        $this->client = \Config\Services::curlrequest([
            'timeout' => $this->getSetting('timeout', 60),
            'verify' => false,
        ]);
    }

    /**
     * Load settings from database
     */
    protected function loadDbSettings(): void
    {
        try {
            if ($this->db->tableExists('sync_settings')) {
                $rows = $this->db->table('sync_settings')->get()->getResult();
                foreach ($rows as $row) {
                    $this->dbSettings[$row->setting_key] = $row->setting_value;
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist, use file config
        }
    }

    /**
     * Get setting value (database first, then config file)
     */
    protected function getSetting(string $key, $default = null)
    {
        // Check database settings first
        if (isset($this->dbSettings[$key])) {
            return $this->dbSettings[$key];
        }

        // Fallback to config file
        $configMap = [
            'server_mode' => $this->config->serverMode,
            'pusat_url' => $this->config->pusatUrl,
            'api_key' => $this->config->apiKey,
            'timeout' => $this->config->timeout,
            'retry_attempts' => $this->config->retryAttempts,
            'batch_size' => $this->config->batchSize,
            'enable_logging' => $this->config->enableLogging,
            'enforce_signature' => $this->config->enforceSignature,
            'signature_drift' => $this->config->signatureDrift,
            'enable_checksum' => $this->config->enableChecksum,
        ];

        return $configMap[$key] ?? $default;
    }

    public function isPusat(): bool
    {
        return $this->getSetting('server_mode', 'lokal') === 'pusat';
    }

    public function isLokal(): bool
    {
        return $this->getSetting('server_mode', 'lokal') === 'lokal';
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->getSetting('pusat_url', ''), '/');
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getSetting('api_key', ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Prepare request options with headers and query params
     */
    protected function prepareRequest(string $method, string $endpoint, array $extra = []): array
    {
        $options = [
            'headers' => $this->getHeaders(),
            'verify' => false,
            'timeout' => $this->getSetting('timeout', 60),
        ];

        // Merge extra options (query, json, form_params, etc.)
        if (!empty($extra['query'])) {
            $options['query'] = $extra['query'];
        }

        if (!empty($extra['json'])) {
            $options['json'] = $extra['json'];
        }

        if (!empty($extra['form_params'])) {
            $options['form_params'] = $extra['form_params'];
        }

        return $options;
    }

    // =========================================
    // PULL: Pusat → Lokal
    // =========================================

    /**
     * Pull semua jadwal dari pusat
     */
    public function pullJadwal(int $tpId, int $smtId): array
    {
        if (!$this->isLokal()) {
            return ['success' => false, 'message' => 'Hanya server lokal yang bisa pull data'];
        }

        try {
            $options = $this->prepareRequest('GET', '/api/sync/jadwal', [
                'query' => ['tp_id' => $tpId, 'smt_id' => $smtId],
            ]);
            $response = $this->requestWithRetry(fn() => $this->client->get($this->getBaseUrl() . '/api/sync/jadwal', $options), 'pull_jadwal');

            $data = json_decode($response->getBody(), true);
            if (!($data['success'] ?? false)) {
                throw new \Exception($data['message'] ?? 'Unknown error');
            }

            $inserted = 0;
            $updated = 0;

            foreach ($data['jadwal'] ?? [] as $jadwal) {
                $result = $this->upsert('cbt_jadwal', $jadwal, 'id_jadwal');
                if ($result === 'insert')
                    $inserted++;
                if ($result === 'update')
                    $updated++;
            }

            $this->logSync('pull_jadwal', ['inserted' => $inserted, 'updated' => $updated]);

            return [
                'success' => true,
                'message' => "Sync jadwal: {$inserted} baru, {$updated} diperbarui",
                'inserted' => $inserted,
                'updated' => $updated,
            ];
        } catch (\Exception $e) {
            $this->logSync('pull_jadwal_error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sinkronisasi gagal — periksa log aplikasi.'];
        }
    }

    /**
     * Pull bank soal beserta soal-soalnya
     */
    public function pullBankSoal(int $bankId): array
    {
        if (!$this->isLokal()) {
            return ['success' => false, 'message' => 'Hanya server lokal yang bisa pull data'];
        }

        try {
            $options = $this->prepareRequest('GET', "/api/sync/bank-soal/{$bankId}");
            $response = $this->requestWithRetry(fn() => $this->client->get($this->getBaseUrl() . "/api/sync/bank-soal/{$bankId}", $options), 'pull_bank_soal');

            $data = json_decode($response->getBody(), true);
            if (!($data['success'] ?? false)) {
                throw new \Exception($data['message'] ?? 'Unknown error');
            }

            // Insert bank soal
            if ($data['bank_soal'] ?? null) {
                $this->upsert('cbt_bank_soal', $data['bank_soal'], 'id_bank');
            }

            // Insert soal
            $soalCount = 0;
            foreach ($data['soal'] ?? [] as $soal) {
                $this->upsert('cbt_soal', $soal, 'id_soal');
                $soalCount++;
            }

            // Download files
            $filesCount = 0;
            foreach ($data['files'] ?? [] as $file) {
                if ($this->downloadFile($file))
                    $filesCount++;
            }

            $this->logSync('pull_bank_soal', ['bank_id' => $bankId, 'soal' => $soalCount, 'files' => $filesCount]);

            return [
                'success' => true,
                'message' => "Sync bank soal: {$soalCount} soal, {$filesCount} file",
            ];
        } catch (\Exception $e) {
            $this->logSync('pull_bank_soal_error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sinkronisasi gagal — periksa log aplikasi.'];
        }
    }

    /**
     * Pull data siswa
     */
    public function pullSiswa(array $kelasIds, int $tpId, int $smtId): array
    {
        if (!$this->isLokal()) {
            return ['success' => false, 'message' => 'Hanya server lokal yang bisa pull data'];
        }

        try {
            $kelasIds = array_values(array_filter(array_map('intval', $kelasIds)));
            $batchSize = max(1, (int) $this->getSetting('batch_size', 100));
            $chunks = array_chunk($kelasIds, $batchSize);

            $totalCount = 0;
            foreach ($chunks as $chunk) {
                $options = $this->prepareRequest('POST', '/api/sync/siswa', [
                    'json' => ['kelas_ids' => $chunk, 'tp_id' => $tpId, 'smt_id' => $smtId],
                ]);
                $response = $this->requestWithRetry(fn() => $this->client->post($this->getBaseUrl() . '/api/sync/siswa', $options), 'pull_siswa');

                $data = json_decode($response->getBody(), true);
                if (!($data['success'] ?? false)) {
                    throw new \Exception($data['message'] ?? 'Unknown error');
                }

                $totalCount += $this->storeSiswaPayload($data);
            }

            $this->logSync('pull_siswa', ['count' => $totalCount, 'chunks' => count($chunks)]);

            return [
                'success' => true,
                'message' => "Sync {$totalCount} siswa",
                'count' => $totalCount,
            ];
        } catch (\Exception $e) {
            $this->logSync('pull_siswa_error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sinkronisasi gagal — periksa log aplikasi.'];
        }
    }

    /**
     * Pull sesi siswa (alokasi ruang/sesi)
     */
    public function pullSesiSiswa(int $tpId, int $smtId): array
    {
        if (!$this->isLokal()) {
            return ['success' => false, 'message' => 'Hanya server lokal yang bisa pull data'];
        }

        try {
            $options = $this->prepareRequest('GET', '/api/sync/sesi-siswa', [
                'query' => ['tp_id' => $tpId, 'smt_id' => $smtId],
            ]);
            $response = $this->requestWithRetry(fn() => $this->client->get($this->getBaseUrl() . '/api/sync/sesi-siswa', $options), 'pull_sesi_siswa');

            $data = json_decode($response->getBody(), true);
            if (!($data['success'] ?? false)) {
                throw new \Exception($data['message'] ?? 'Unknown error');
            }

            // Sync ruang
            foreach ($data['ruang'] ?? [] as $r) {
                $this->upsert('cbt_ruang', $r, 'id_ruang');
            }

            // Sync sesi
            foreach ($data['sesi'] ?? [] as $s) {
                $this->upsert('cbt_sesi', $s, 'id_sesi');
            }

            // Sync sesi_siswa
            $count = 0;
            foreach ($data['sesi_siswa'] ?? [] as $ss) {
                $this->upsert('cbt_sesi_siswa', $ss, 'id');
                $count++;
            }

            $this->logSync('pull_sesi_siswa', ['count' => $count]);

            return ['success' => true, 'message' => "Sync {$count} alokasi sesi siswa"];
        } catch (\Exception $e) {
            $this->logSync('pull_sesi_siswa_error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sinkronisasi gagal — periksa log aplikasi.'];
        }
    }

    // =========================================
    // PUSH: Lokal → Pusat
    // =========================================

    /**
     * Push hasil ujian ke pusat
     */
    public function pushHasilUjian(int $jadwalId): array
    {
        if (!$this->isLokal()) {
            return ['success' => false, 'message' => 'Hanya server lokal yang bisa push data'];
        }

        try {
            // Collect data
            $cbtSiswa = $this->db->table('cbt_siswa')
                ->where('id_jadwal', $jadwalId)
                ->get()->getResult();

            if (empty($cbtSiswa)) {
                return ['success' => false, 'message' => 'Tidak ada data siswa untuk jadwal ini'];
            }

            $siswaIds = array_column($cbtSiswa, 'id_siswa');

            $cbtJawaban = $this->db->table('cbt_jawaban')
                ->where('id_jadwal', $jadwalId)
                ->whereIn('id_siswa', $siswaIds)
                ->get()->getResult();

            $cbtNilai = $this->db->table('cbt_nilai')
                ->where('id_jadwal', $jadwalId)
                ->whereIn('id_siswa', $siswaIds)
                ->get()->getResult();

            // Send to pusat
            $options = $this->prepareRequest('POST', '/api/sync/hasil', [
                'json' => [
                    'jadwal_id' => $jadwalId,
                    'cbt_siswa' => $cbtSiswa,
                    'cbt_jawaban' => $cbtJawaban,
                    'cbt_nilai' => $cbtNilai,
                    'synced_at' => date('Y-m-d H:i:s'),
                ],
            ]);
            $response = $this->requestWithRetry(fn() => $this->client->post($this->getBaseUrl() . '/api/sync/hasil', $options), 'push_hasil');

            $data = json_decode($response->getBody(), true);
            if (!($data['success'] ?? false)) {
                throw new \Exception($data['message'] ?? 'Unknown error');
            }

            // Mark as synced
            $this->db->table('cbt_jadwal')
                ->where('id_jadwal', $jadwalId)
                ->update(['synced' => 1, 'synced_at' => date('Y-m-d H:i:s')]);

            $this->logSync('push_hasil', [
                'jadwal_id' => $jadwalId,
                'siswa' => count($cbtSiswa),
                'jawaban' => count($cbtJawaban),
            ]);

            return [
                'success' => true,
                'message' => "Berhasil mengirim hasil " . count($cbtSiswa) . " siswa ke pusat",
            ];
        } catch (\Exception $e) {
            $this->logSync('push_hasil_error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sinkronisasi gagal — periksa log aplikasi.'];
        }
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Generic upsert
     */
    protected function upsert(string $table, array $data, string $primaryKey): string
    {
        $existing = $this->db->table($table)
            ->where($primaryKey, $data[$primaryKey] ?? null)
            ->get()->getRow();

        if ($existing) {
            $this->db->table($table)
                ->where($primaryKey, $data[$primaryKey])
                ->update($data);
            return 'update';
        } else {
            $this->db->table($table)->insert($data);
            return 'insert';
        }
    }

    /**
     * Download file from pusat
     */
    protected function downloadFile(array $fileInfo): bool
    {
        try {
            $localPath = FCPATH . ($fileInfo['path'] ?? '');
            $dir = dirname($localPath);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $response = $this->requestWithRetry(fn() => $this->client->get($fileInfo['url'], [
                'headers' => $this->getHeaders(),
            ]), 'download_file');
            file_put_contents($localPath, $response->getBody());

            if ($this->config->enableChecksum && !empty($fileInfo['checksum'])) {
                $calculated = hash_file('sha256', $localPath);
                if (!hash_equals($fileInfo['checksum'], $calculated)) {
                    @unlink($localPath);
                    throw new \RuntimeException('Checksum mismatch for file ' . ($fileInfo['path'] ?? ''));
                }
            }
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Download file failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log sync activity
     */
    protected function logSync(string $action, array $data): void
    {
        if (!$this->config->enableLogging)
            return;

        try {
            $this->db->table('sync_log')->insert([
                'action' => $action,
                'data' => json_encode($data),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('info', "[SYNC] {$action}: " . json_encode($data));
        }
    }

    /**
     * Request helper with retry + exponential backoff
     */
    protected function requestWithRetry(callable $callback, string $action)
    {
        $maxAttempts = max(1, (int) $this->getSetting('retry_attempts', 3));
        $attempt = 0;
        $delay = 1;

        do {
            try {
                return $callback();
            } catch (Throwable $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                $this->logSync($action . '_retry', [
                    'attempt' => $attempt,
                    'max' => $maxAttempts,
                    'error' => $e->getMessage(),
                ]);

                sleep($delay);
                $delay = min($delay * 2, 30);
            }
        } while ($attempt < $maxAttempts);

        return $callback();
    }

    /**
     * Store siswa payload into database
     */
    protected function storeSiswaPayload(array $data): int
    {
        $siswaCount = 0;
        foreach ($data['siswa'] ?? [] as $siswa) {
            $this->upsert('master_siswa', $siswa, 'id_siswa');
            $siswaCount++;
        }

        foreach ($data['kelas_siswa'] ?? [] as $ks) {
            $existing = $this->db->table('kelas_siswa')
                ->where('id_siswa', $ks['id_siswa'])
                ->where('id_kelas', $ks['id_kelas'])
                ->where('id_tp', $ks['id_tp'])
                ->where('id_smt', $ks['id_smt'])
                ->get()->getRow();
            if (!$existing) {
                $this->db->table('kelas_siswa')->insert($ks);
            }
        }

        return $siswaCount;
    }

    // =========================================
    // Queue Management
    // =========================================

    protected function ensureJobTable(): void
    {
        try {
            if ($this->db->tableExists($this->jobTable)) {
                return;
            }

            $this->db->query(<<<SQL
CREATE TABLE IF NOT EXISTS `sync_jobs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL,
    `payload` JSON NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
    `last_error` TEXT NULL,
    `result` TEXT NULL,
    `available_at` DATETIME NULL,
    `started_at` DATETIME NULL,
    `finished_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status_available` (`status`, `available_at`)
)
SQL
            );
        } catch (\Exception $e) {
            log_message('error', 'Failed creating sync_jobs table: ' . $e->getMessage());
        }
    }

    public function enqueueJob(string $type, array $payload, ?int $maxAttempts = null): int
    {
        $this->ensureJobTable();

        $maxAttempts = $maxAttempts ?? (int) $this->getSetting('retry_attempts', 3);
        $builder = $this->db->table($this->jobTable);
        $builder->insert([
            'type' => $type,
            'payload' => json_encode($payload),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => max(1, $maxAttempts),
            'available_at' => date('Y-m-d H:i:s'),
        ]);

        $jobId = (int) $this->db->insertID();
        $this->logSync('enqueue_job', ['job_id' => $jobId, 'type' => $type]);

        return $jobId;
    }

    public function getJob(int $jobId): ?object
    {
        $this->ensureJobTable();
        return $this->db->table($this->jobTable)->where('id', $jobId)->get()->getRow();
    }

    public function getRecentJobs(int $limit = 50): array
    {
        $this->ensureJobTable();
        return $this->db->table($this->jobTable)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()->getResult();
    }

    protected function fetchNextJob(): ?object
    {
        $this->ensureJobTable();
        $job = $this->db->table($this->jobTable)
            ->where('status', 'pending')
            ->groupStart()
            ->where('available_at IS NULL')
            ->orWhere('available_at <=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get()
            ->getRow();

        if ($job) {
            $this->db->table($this->jobTable)
                ->where('id', $job->id)
                ->update([
                        'status' => 'processing',
                        'attempts' => $job->attempts + 1,
                        'started_at' => date('Y-m-d H:i:s'),
                    ]);
            $job->attempts++;
        }

        return $job;
    }

    protected function finalizeJob(object $job, bool $success, array $result): void
    {
        $data = [
            'status' => $success ? 'completed' : 'failed',
            'result' => $success ? json_encode($result) : null,
            'last_error' => $success ? null : ($result['message'] ?? 'Unknown error'),
            'finished_at' => date('Y-m-d H:i:s'),
        ];

        if (!$success && $job->attempts < ($job->max_attempts ?? 1)) {
            $delay = min(pow(2, $job->attempts), 60);
            $data['status'] = 'pending';
            $data['available_at'] = date('Y-m-d H:i:s', time() + $delay);
            $data['last_error'] = $result['message'] ?? 'Unknown error';
            unset($data['result']);
        }

        $this->db->table($this->jobTable)
            ->where('id', $job->id)
            ->update($data);
    }

    public function runWorker(bool $once = false, int $sleepSeconds = 5): void
    {
        do {
            $job = $this->fetchNextJob();

            if (!$job) {
                if ($once) {
                    return;
                }
                sleep($sleepSeconds);
                continue;
            }

            $result = $this->executeJob($job);
            $this->finalizeJob($job, $result['success'] ?? false, $result);

            if ($once) {
                return;
            }
        } while (true);
    }

    protected function executeJob(object $job): array
    {
        $payload = json_decode($job->payload ?? '[]', true) ?? [];

        switch ($job->type) {
            case 'pull_jadwal':
                return $this->pullJadwal((int) ($payload['tp_id'] ?? 0), (int) ($payload['smt_id'] ?? 0));
            case 'pull_bank_soal':
                return $this->pullBankSoal((int) ($payload['bank_id'] ?? 0));
            case 'pull_siswa':
                return $this->pullSiswa($payload['kelas_ids'] ?? [], (int) ($payload['tp_id'] ?? 0), (int) ($payload['smt_id'] ?? 0));
            case 'pull_sesi_siswa':
                return $this->pullSesiSiswa((int) ($payload['tp_id'] ?? 0), (int) ($payload['smt_id'] ?? 0));
            case 'push_hasil':
                return $this->pushHasilUjian((int) ($payload['jadwal_id'] ?? 0));
            default:
                return ['success' => false, 'message' => 'Unknown job type: ' . $job->type];
        }
    }

    /**
     * Get sync history
     */
    public function getSyncHistory(int $limit = 50): array
    {
        try {
            return $this->db->table('sync_log')
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Test connection to pusat
     */
    public function testConnection(): array
    {
        $pusatUrl = $this->getSetting('pusat_url', '');
        if (empty($pusatUrl)) {
            return ['success' => false, 'message' => 'URL server pusat belum dikonfigurasi'];
        }

        try {
            $options = $this->prepareRequest('GET', '/api/sync/ping');
            $response = $this->requestWithRetry(fn() => $this->client->get($this->getBaseUrl() . '/api/sync/ping', $options), 'test_connection');

            $data = json_decode($response->getBody(), true);
            return [
                'success' => $data['success'] ?? false,
                'message' => $data['message'] ?? 'Connected',
                'server_time' => $data['server_time'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Gagal terhubung — periksa log aplikasi.'];
        }
    }

    /**
     * Get pending jadwal to sync
     */
    public function getPendingJadwal(int $tpId, int $smtId): array
    {
        return $this->db->table('cbt_jadwal a')
            ->select('a.*, b.bank_kode, m.nama_mapel')
            ->join('cbt_bank_soal b', 'a.id_bank = b.id_bank', 'left')
            ->join('master_mapel m', 'b.bank_mapel_id = m.id_mapel', 'left')
            ->where('a.id_tp', $tpId)
            ->where('a.id_smt', $smtId)
            ->where('a.status', '0')
            ->groupStart()
            ->where('a.synced', null)
            ->orWhere('a.synced', 0)
            ->groupEnd()
            ->get()->getResult();
    }
}
