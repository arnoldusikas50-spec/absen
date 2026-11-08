<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtSiswaModel - Migrated from CI3 Cbt_siswa_model
 * Handles student CBT records (exam sessions)
 */
class CbtSiswaModel extends Model
{
    protected $table = 'cbt_siswa';
    protected $primaryKey = 'id_cbt_siswa';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = [
        'id_siswa',
        'id_jadwal',
        'id_bank',
        'mulai',
        'selesai',
        'lama_ujian',
        'status',
        'reset_izin',
        'reset_waktu',
        'ip_address',
        'user_agent',
        'device_type',
        'queue_number'
    ];

    protected $useTimestamps = false;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get CBT siswa record
     */
    public function getCbtSiswa(int $idSiswa, int $idJadwal): ?object
    {
        return $this->where('id_siswa', $idSiswa)
            ->where('id_jadwal', $idJadwal)
            ->first();
    }

    /**
     * Get all CBT siswa by jadwal
     */
    public function getAllByJadwal(int $idJadwal): array
    {
        return $this->db->table('cbt_siswa a')
            ->select('a.*, b.nama, b.nis, b.nisn, c.nama_kelas')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa', 'left')
            ->join('kelas_siswa d', 'a.id_siswa = d.id_siswa', 'left')
            ->join('master_kelas c', 'd.id_kelas = c.id_kelas', 'left')
            ->where('a.id_jadwal', $idJadwal)
            ->get()
            ->getResult();
    }

    /**
     * Get siswa sedang ujian (in progress)
     */
    public function getSiswaSedangUjian(int $idJadwal): array
    {
        return $this->db->table('cbt_siswa a')
            ->select('a.*, b.nama, b.nis, b.nisn, c.nama_kelas')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa', 'left')
            ->join('kelas_siswa d', 'a.id_siswa = d.id_siswa', 'left')
            ->join('master_kelas c', 'd.id_kelas = c.id_kelas', 'left')
            ->where('a.id_jadwal', $idJadwal)
            ->where('a.mulai IS NOT NULL')
            ->where('a.selesai', null)
            ->get()
            ->getResult();
    }

    /**
     * Get siswa sudah selesai ujian
     */
    public function getSiswaSelesaiUjian(int $idJadwal): array
    {
        return $this->db->table('cbt_siswa a')
            ->select('a.*, b.nama, b.nis, b.nisn, c.nama_kelas')
            ->join('master_siswa b', 'a.id_siswa = b.id_siswa', 'left')
            ->join('kelas_siswa d', 'a.id_siswa = d.id_siswa', 'left')
            ->join('master_kelas c', 'd.id_kelas = c.id_kelas', 'left')
            ->where('a.id_jadwal', $idJadwal)
            ->where('a.selesai IS NOT NULL')
            ->get()
            ->getResult();
    }

    /**
     * Start exam (set mulai time)
     */
    public function startExam(int $idSiswa, int $idJadwal, int $idBank): int
    {
        $existing = $this->getCbtSiswa($idSiswa, $idJadwal);

        if ($existing) {
            // Only update start time if it was reset or empty
            if (empty($existing->mulai)) {
                $this->update($existing->id_cbt_siswa, [
                    'mulai' => date('Y-m-d H:i:s'),
                ]);
            }
            return $existing->id_cbt_siswa;
        }

        $data = [
            'id_siswa' => $idSiswa,
            'id_jadwal' => $idJadwal,
            'id_bank' => $idBank,
            'mulai' => date('Y-m-d H:i:s'),
            'status' => '0',
        ];

        $this->insert($data);
        return $this->getInsertID();
    }

    /**
     * End exam (set selesai time)
     */
    public function endExam(int $idCbtSiswa): bool
    {
        $cbtSiswa = $this->find($idCbtSiswa);

        if (!$cbtSiswa) {
            return false;
        }

        $mulai = strtotime($cbtSiswa->mulai);
        $selesai = time();
        $lamaUjian = gmdate('H:i:s', $selesai - $mulai);

        return $this->update($idCbtSiswa, [
            'selesai' => date('Y-m-d H:i:s'),
            'lama_ujian' => $lamaUjian,
            'status' => '1'
        ]);
    }

    /**
     * Reset selesai status (allow continue exam if time still available)
     */
    public function resetSelesai(int $idCbtSiswa): bool
    {
        return $this->update($idCbtSiswa, [
            'selesai' => null,
            'status' => '0'
        ]);
    }

    /**
     * Reset login (allow re-login from different device)
     */
    public function resetLogin(int $idCbtSiswa): bool
    {
        return $this->update($idCbtSiswa, [
            'reset_izin' => '1'
        ]);
    }

    /**
     * Reset waktu (extend exam time)
     */
    public function resetWaktu(int $idCbtSiswa, int $additionalMinutes = 15): bool
    {
        $cbtSiswa = $this->find($idCbtSiswa);

        if (!$cbtSiswa) {
            return false;
        }

        $currentReset = (int) ($cbtSiswa->reset_waktu ?? 0);

        return $this->update($idCbtSiswa, [
            'reset_waktu' => $currentReset + $additionalMinutes
        ]);
    }

    /**
     * Count statistics for jadwal
     */
    public function getStatistikJadwal(int $idJadwal): object
    {
        $total = $this->where('id_jadwal', $idJadwal)->countAllResults(false);
        $sedangUjian = $this->where('id_jadwal', $idJadwal)
            ->where('mulai IS NOT NULL')
            ->where('selesai', null)
            ->countAllResults(false);
        $selesai = $this->where('id_jadwal', $idJadwal)
            ->where('selesai IS NOT NULL')
            ->countAllResults(false);
        $belumMulai = $this->where('id_jadwal', $idJadwal)
            ->where('mulai', null)
            ->countAllResults();

        return (object) [
            'total' => $total,
            'sedang_ujian' => $sedangUjian,
            'selesai' => $selesai,
            'belum_mulai' => $belumMulai
        ];
    }
}
