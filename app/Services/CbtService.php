<?php

namespace App\Services;

use App\Models\CbtModel;
use App\Models\CbtSiswaModel;
use App\Models\DashboardModel;

/**
 * CbtService - Business Logic Layer for CBT
 * 
 * Centralizes common CBT operations to avoid code duplication
 * across controllers. Implements caching for frequently accessed data.
 */
class CbtService
{
    protected $cbtModel;
    protected $cbtSiswaModel;
    protected $dashboardModel;
    protected $db;
    protected $cache;

    // Cache TTL in seconds
    const CACHE_TTL_SHORT = 60;      // 1 minute
    const CACHE_TTL_MEDIUM = 300;    // 5 minutes
    const CACHE_TTL_LONG = 3600;     // 1 hour

    public function __construct()
    {
        $this->cbtModel = new CbtModel();
        $this->cbtSiswaModel = new CbtSiswaModel();
        $this->dashboardModel = new DashboardModel();
        $this->db = \Config\Database::connect();
        $this->cache = \Config\Services::cache();
    }

    /**
     * Get current student data from session
     * Cached per request to avoid multiple DB calls
     */
    public function getCurrentSiswa(): ?object
    {
        static $siswa = null;
        static $checked = false;

        // Return cached result (even if null)
        if ($checked) {
            return $siswa;
        }
        $checked = true;

        try {
            $session = session();

            // Use username from session directly (like Siswa.php and Kbm.php do)
            $username = $session->get('username');

            if (!$username) {
                log_message('debug', 'CbtService: No username in session');
                return null;
            }

            $tp = $this->dashboardModel->getTahunActive();
            $smt = $this->dashboardModel->getSemesterActive();

            if (!$tp || !$smt) {
                log_message('debug', 'CbtService: No active TP or SMT');
                return null;
            }

            $siswa = $this->dashboardModel->getDataSiswa($username, $tp->id_tp, $smt->id_smt);

            if (!$siswa) {
                log_message('debug', 'CbtService: Siswa not found for username ' . $username);
            }

            return $siswa;
        } catch (\Throwable $e) {
            log_message('error', 'CbtService::getCurrentSiswa error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get active academic period (cached)
     */
    public function getActivePeriod(): array
    {
        $cacheKey = 'cbt_active_period';

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $data = [
            'tp' => $tp,
            'smt' => $smt
        ];

        $this->cache->save($cacheKey, $data, self::CACHE_TTL_LONG);

        return $data;
    }

    /**
     * Get jadwal with all related data (optimized single query)
     */
    public function getJadwalComplete(int $idJadwal): ?object
    {
        $cacheKey = "cbt_jadwal_complete_{$idJadwal}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = "
            SELECT 
                j.*,
                b.id_bank, b.bank_kode, b.bank_nama, b.bank_level, b.bank_kelas,
                b.bank_mapel_id, b.tampil_pg, b.tampil_kompleks, b.tampil_jodohkan,
                b.tampil_isian, b.tampil_esai, b.tampil_urutan,
                b.soal_agama,
                jn.nama_jenis,
                m.nama_mapel, m.kode as kode_mapel
            FROM cbt_jadwal j
            LEFT JOIN cbt_bank_soal b ON j.id_bank = b.id_bank
            LEFT JOIN cbt_jenis jn ON j.id_jenis = jn.id_jenis
            LEFT JOIN master_mapel m ON b.bank_mapel_id = m.id_mapel
            WHERE j.id_jadwal = ?
        ";

        $jadwal = $this->db->query($sql, [$idJadwal])->getRow();

        if ($jadwal) {
            $this->cache->save($cacheKey, $jadwal, self::CACHE_TTL_MEDIUM);
        }

        return $jadwal;
    }

    /**
     * Get available exams for student (optimized)
     */
    public function getAvailableExams(int $idSiswa, int $levelId, int $kelasId): array
    {
        $period = $this->getActivePeriod();

        $sql = "
            SELECT DISTINCT
                j.id_jadwal, j.durasi, j.tgl_mulai, j.tgl_selesai, j.status_ujian,
                b.bank_kode, b.bank_nama,
                jn.nama_jenis,
                m.nama_mapel, m.kode as kode_mapel,
                cs.id_cbt_siswa, cs.status as siswa_status, cs.mulai, cs.selesai
            FROM cbt_jadwal j
            JOIN cbt_bank_soal b ON j.id_bank = b.id_bank
            LEFT JOIN cbt_jenis jn ON j.id_jenis = jn.id_jenis
            LEFT JOIN master_mapel m ON b.bank_mapel_id = m.id_mapel
            LEFT JOIN cbt_siswa cs ON j.id_jadwal = cs.id_jadwal AND cs.id_siswa = ?
            WHERE j.id_tp = ? 
              AND j.id_smt = ?
              AND j.status_ujian = '1'
              AND (b.bank_level = ? OR b.bank_level = 0)
            ORDER BY j.tgl_mulai DESC
        ";

        $exams = $this->db->query($sql, [
            $idSiswa,
            $period['tp']->id_tp,
            $period['smt']->id_smt,
            $levelId
        ])->getResult();

        // Filter by class
        $filtered = [];
        foreach ($exams as $exam) {
            // Get bank_kelas from jadwal
            $jadwal = $this->getJadwalComplete($exam->id_jadwal);
            if (!$jadwal)
                continue;

            $bankKelas = $this->parseKelasIds($jadwal->bank_kelas);

            // Include if class matches or "all classes" (empty)
            if (empty($bankKelas) || in_array($kelasId, $bankKelas)) {
                $filtered[] = $exam;
            }
        }

        return $filtered;
    }

    /**
     * Parse bank_kelas serialized/JSON data to array of class IDs
     */
    public function parseKelasIds($bankKelas): array
    {
        if (empty($bankKelas)) {
            return [];
        }

        // Try JSON decode
        $decoded = json_decode($bankKelas, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_column($decoded, 'kelas_id');
        }

        // Try unserialize
        $unserialized = @unserialize($bankKelas, ['allowed_classes' => false]);
        if ($unserialized !== false && is_array($unserialized)) {
            return array_column($unserialized, 'kelas_id');
        }

        return [];
    }

    /**
     * Check if student can start exam
     */
    public function canStartExam(int $idSiswa, int $idJadwal): array
    {
        $jadwal = $this->getJadwalComplete($idJadwal);

        if (!$jadwal) {
            return ['allowed' => false, 'reason' => 'Jadwal tidak ditemukan'];
        }

        // Check time window
        $now = time();
        $start = strtotime($jadwal->tgl_mulai);
        $end = strtotime($jadwal->tgl_selesai);

        if ($now < $start) {
            return ['allowed' => false, 'reason' => 'Ujian belum dimulai'];
        }

        if ($now > $end) {
            return ['allowed' => false, 'reason' => 'Waktu ujian sudah berakhir'];
        }

        // Check exam status
        if ($jadwal->status_ujian != '1') {
            return ['allowed' => false, 'reason' => 'Ujian tidak aktif'];
        }

        // Check if already finished
        $cbtSiswa = $this->cbtSiswaModel->getCbtSiswa($idSiswa, $idJadwal);
        if ($cbtSiswa && $cbtSiswa->status == '2') {
            return ['allowed' => false, 'reason' => 'Anda sudah menyelesaikan ujian ini'];
        }

        return ['allowed' => true, 'jadwal' => $jadwal, 'cbtSiswa' => $cbtSiswa];
    }

    /**
     * Get exam duration remaining (in seconds)
     */
    public function getRemainingTime(object $cbtSiswa, object $jadwal): int
    {
        $durasi = ($jadwal->durasi ?? 60) * 60; // Duration in seconds
        $mulai = strtotime($cbtSiswa->mulai);
        $elapsed = time() - $mulai;

        // Add reset time if any
        $extraMinutes = (int) ($cbtSiswa->reset_waktu ?? 0);
        $totalDurasi = $durasi + ($extraMinutes * 60);

        $remaining = $totalDurasi - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Get questions for exam (paginated for performance)
     */
    public function getQuestions(int $idBank, int $limit = 0, int $offset = 0): array
    {
        $builder = $this->db->table('cbt_soal')
            ->where('id_bank', $idBank)
            ->orderBy('nomor_soal', 'ASC');

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResult();
    }

    /**
     * Get question count by type
     */
    public function getQuestionCountByType(int $idBank): array
    {
        $cacheKey = "cbt_soal_count_{$idBank}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = "
            SELECT jenis, COUNT(*) as jumlah
            FROM cbt_soal
            WHERE id_bank = ?
            GROUP BY jenis
        ";

        $result = $this->db->query($sql, [$idBank])->getResultArray();
        $counts = array_column($result, 'jumlah', 'jenis');

        $this->cache->save($cacheKey, $counts, self::CACHE_TTL_LONG);

        return $counts;
    }

    /**
     * Calculate and save score (delegates to model)
     */
    public function calculateScore(int $idSiswa, int $idJadwal): bool
    {
        return $this->cbtModel->hitungNilai($idSiswa, $idJadwal);
    }

    /**
     * Invalidate cache for a jadwal
     */
    public function invalidateJadwalCache(int $idJadwal): void
    {
        $this->cache->delete("cbt_jadwal_complete_{$idJadwal}");
    }

    /**
     * Get token (no cache - token changes frequently)
     */
    public function getCurrentToken(): ?object
    {
        // Token changes frequently, don't cache to avoid validation issues
        return $this->cbtModel->getToken();
    }

    /**
     * Get questions with deterministic shuffle (cached per student+jadwal)
     * This avoids expensive RAND() queries on each navigation
     */
    public function getSoalCached(int $idBank, int $idSiswa, int $idJadwal, string $acak = '0'): array
    {
        // Build unique cache key per student+jadwal
        $seed = $idSiswa + $idJadwal;
        $cacheKey = "cbt_soal_{$idBank}_{$seed}_{$acak}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Fetch questions from database
        $questions = $this->db->table('cbt_soal')
            ->where('bank_id', $idBank)
            ->where('tampilkan', '1')
            ->orderBy('nomor_soal', 'ASC')
            ->orderBy('id_soal', 'ASC')
            ->get()
            ->getResult();

        // Apply deterministic shuffle if randomization is enabled
        if ($acak == '1' && !empty($questions)) {
            // Use student+jadwal as seed for consistent order
            mt_srand($seed);
            $shuffled = $questions;
            usort($shuffled, function ($a, $b) {
                return mt_rand(-1, 1);
            });
            mt_srand(); // Reset seed
            $questions = $shuffled;
        }

        // Cache for duration of exam (30 minutes should be safe)
        $this->cache->save($cacheKey, $questions, 1800);

        return $questions;
    }

    /**
     * Get all jawaban for student+jadwal (cached during exam)
     * Reduces per-soal query to single query
     */
    public function getJawabanCached(int $idSiswa, int $idJadwal): array
    {
        $cacheKey = "cbt_jawaban_{$idSiswa}_{$idJadwal}";

        // Short cache - 10 seconds to balance freshness vs performance
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->db->table('cbt_jawaban')
            ->where('id_siswa', $idSiswa)
            ->where('id_jadwal', $idJadwal)
            ->get()
            ->getResult();

        // Index by id_soal for O(1) lookup
        $data = [];
        foreach ($result as $row) {
            $data[$row->id_soal] = $row;
        }

        $this->cache->save($cacheKey, $data, 10);

        return $data;
    }

    /**
     * Invalidate jawaban cache when answer is saved
     */
    public function invalidateJawabanCache(int $idSiswa, int $idJadwal): void
    {
        $this->cache->delete("cbt_jawaban_{$idSiswa}_{$idJadwal}");
    }

    /**
     * Get cbt_siswa record (with request-level caching)
     */
    public function getCbtSiswaCached(int $idSiswa, int $idJadwal): ?object
    {
        static $cache = [];
        $key = "{$idSiswa}_{$idJadwal}";

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $cache[$key] = $this->cbtSiswaModel->getCbtSiswa($idSiswa, $idJadwal);
        return $cache[$key];
    }

    /**
     * Clear request-level cbt_siswa cache (call after updates)
     */
    public function clearCbtSiswaRequestCache(): void
    {
        // Note: static cache is automatically cleared per request
    }
}
