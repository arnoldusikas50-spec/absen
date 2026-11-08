<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CBT Report Model - Optimized for large-scale reporting
 * Use this for admin backend reports with 1000+ students
 */
class CbtReportModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get summary statistics for a jadwal (fast query)
     * Use this instead of loading all individual scores
     */
    public function getJadwalSummary(int $idJadwal): array
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT cs.id_siswa) as total_peserta,
                SUM(CASE WHEN cs.status = '2' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN cs.status = '1' THEN 1 ELSE 0 END) as sedang_ujian,
                SUM(CASE WHEN cs.status = '0' OR cs.status IS NULL THEN 1 ELSE 0 END) as belum_mulai,
                AVG(cn.pg_nilai) as rata_rata,
                MAX(cn.pg_nilai) as nilai_tertinggi,
                MIN(cn.pg_nilai) as nilai_terendah
            FROM cbt_siswa cs
            LEFT JOIN cbt_nilai cn ON cs.id_siswa = cn.id_siswa AND cs.id_jadwal = cn.id_jadwal
            WHERE cs.id_jadwal = ?
        ";

        return $this->db->query($sql, [$idJadwal])->getRowArray() ?? [];
    }

    /**
     * Get scores with pagination (for large datasets)
     * Much faster than loading all at once
     */
    public function getScoresPaginated(int $idJadwal, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                s.id_siswa, s.nama, s.nis, k.nama_kelas,
                cn.pg_benar, cn.pg_nilai,
                cs.mulai, cs.selesai, cs.lama_ujian, cs.status
            FROM cbt_siswa cs
            JOIN master_siswa s ON cs.id_siswa = s.id_siswa
            LEFT JOIN master_kelas k ON s.kelas_id = k.id_kelas
            LEFT JOIN cbt_nilai cn ON cs.id_siswa = cn.id_siswa AND cs.id_jadwal = cn.id_jadwal
            WHERE cs.id_jadwal = ?
            ORDER BY s.nama ASC
            LIMIT ? OFFSET ?
        ";

        return $this->db->query($sql, [$idJadwal, $perPage, $offset])->getResultArray();
    }

    /**
     * Get answer analysis per question (aggregate, not per student)
     * Useful for item analysis without loading individual answers
     */
    public function getQuestionAnalysis(int $idJadwal, int $idBank): array
    {
        $sql = "
            SELECT 
                j.id_soal,
                s.nomor_soal,
                s.jenis,
                s.jawaban as kunci,
                COUNT(j.id_jawaban) as total_menjawab,
                SUM(CASE WHEN j.jawaban = s.jawaban THEN 1 ELSE 0 END) as jawab_benar,
                ROUND(SUM(CASE WHEN j.jawaban = s.jawaban THEN 1 ELSE 0 END) / COUNT(j.id_jawaban) * 100, 1) as persen_benar
            FROM cbt_jawaban j
            JOIN cbt_soal s ON j.id_soal = s.id_soal
            WHERE j.id_jadwal = ? AND s.id_bank = ?
            GROUP BY j.id_soal, s.nomor_soal, s.jenis, s.jawaban
            ORDER BY s.nomor_soal ASC
        ";

        return $this->db->query($sql, [$idJadwal, $idBank])->getResultArray();
    }

    /**
     * Export scores to CSV (memory efficient)
     * Streams data instead of loading all into memory
     */
    public function streamScoresToCsv(int $idJadwal, string $filename = 'rekap_nilai.csv'): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // BOM for Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        fputcsv($output, ['No', 'NIS', 'Nama Siswa', 'Kelas', 'PG Benar', 'Nilai', 'Mulai', 'Selesai', 'Durasi', 'Status']);

        // Stream data in chunks
        $page = 1;
        $perPage = 100;
        $no = 1;

        do {
            $rows = $this->getScoresPaginated($idJadwal, $page, $perPage);

            foreach ($rows as $row) {
                $status = match ($row['status']) {
                    '2' => 'Selesai',
                    '1' => 'Sedang Ujian',
                    default => 'Belum Mulai'
                };

                fputcsv($output, [
                    $no++,
                    $row['nis'],
                    $row['nama'],
                    $row['nama_kelas'] ?? '-',
                    $row['pg_benar'] ?? 0,
                    $row['pg_nilai'] ?? 0,
                    $row['mulai'] ?? '-',
                    $row['selesai'] ?? '-',
                    $row['lama_ujian'] ?? '-',
                    $status
                ]);
            }

            $page++;

        } while (count($rows) === $perPage);

        fclose($output);
        exit;
    }

    /**
     * Get distribution of scores (for chart/histogram)
     */
    public function getScoreDistribution(int $idJadwal): array
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN pg_nilai >= 90 THEN 'A (90-100)'
                    WHEN pg_nilai >= 80 THEN 'B (80-89)'
                    WHEN pg_nilai >= 70 THEN 'C (70-79)'
                    WHEN pg_nilai >= 60 THEN 'D (60-69)'
                    ELSE 'E (0-59)'
                END as grade,
                COUNT(*) as jumlah
            FROM cbt_nilai
            WHERE id_jadwal = ?
            GROUP BY grade
            ORDER BY grade
        ";

        return $this->db->query($sql, [$idJadwal])->getResultArray();
    }

    /**
     * Batch calculate scores (for admin to recalculate all)
     * Run this in background job or CLI
     */
    public function batchCalculateScores(int $idJadwal, int $batchSize = 50): int
    {
        $cbtModel = new CbtModel();
        $processed = 0;

        // Get students who finished but have no score
        $sql = "
            SELECT cs.id_siswa 
            FROM cbt_siswa cs
            LEFT JOIN cbt_nilai cn ON cs.id_siswa = cn.id_siswa AND cs.id_jadwal = cn.id_jadwal
            WHERE cs.id_jadwal = ? AND cs.status = '2' AND cn.id_nilai IS NULL
            LIMIT ?
        ";

        $students = $this->db->query($sql, [$idJadwal, $batchSize])->getResultArray();

        foreach ($students as $student) {
            $cbtModel->hitungNilai($student['id_siswa'], $idJadwal);
            $processed++;
        }

        return $processed;
    }
}
