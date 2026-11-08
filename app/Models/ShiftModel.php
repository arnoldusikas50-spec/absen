<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Profil shift kerja + resolver jadwal per user.
 *
 * Prioritas resolve untuk (user_id, weekday ISO):
 *   1. penugasan hari spesifik + shift aktif
 *   2. penugasan semua-hari (hari=0) + shift aktif
 *   3. null → pemanggil fallback ke absensi_jadwal_kerja / setting global
 */
class ShiftModel extends Model
{
    protected $table = 'absensi_shift';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'nama',
        'jam_masuk',
        'jam_pulang',
        'grace_menit',
        'active',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Ambil shift yang berlaku untuk user pada weekday ISO tertentu.
     */
    public function getShiftForUser(int $userId, int $dayIso): ?object
    {
        if ($userId <= 0 || !$this->db->tableExists('absensi_shift_anggota')) {
            return null;
        }

        foreach ([$dayIso, 0] as $hari) {
            $row = $this->db->table('absensi_shift_anggota a')
                ->select('s.*')
                ->join('absensi_shift s', 's.id = a.shift_id')
                ->where('a.user_id', $userId)
                ->where('a.hari', $hari)
                ->where('s.active', 1)
                // Abaikan baris warisan yang jam-nya belum dilengkapi
                ->where('s.jam_masuk IS NOT NULL', null, false)
                ->where('s.jam_pulang IS NOT NULL', null, false)
                ->orderBy('s.id', 'ASC')
                ->get()->getRow();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Jadwal kerja efektif: shift (bila ada) lalu jadwal harian lalu setting.
     *
     * @return array{jam_masuk: string, jam_pulang: string, grace_menit: int|null, sumber: string}
     */
    public function getScheduleForUser(int $userId, int $dayIso, ?object $jadwalHarian, object $setting): array
    {
        $shift = $this->getShiftForUser($userId, $dayIso);
        if ($shift) {
            return [
                'jam_masuk' => substr((string) $shift->jam_masuk, 0, 8),
                'jam_pulang' => substr((string) $shift->jam_pulang, 0, 8),
                'grace_menit' => $shift->grace_menit !== null ? (int) $shift->grace_menit : (int) ($setting->grace_period ?? 15),
                'sumber' => 'shift:' . $shift->nama,
            ];
        }

        return [
            'jam_masuk' => substr((string) ($jadwalHarian->jam_masuk ?? $setting->jam_masuk ?? '07:30:00'), 0, 8),
            'jam_pulang' => substr((string) ($jadwalHarian->jam_pulang ?? $setting->jam_pulang ?? '15:00:00'), 0, 8),
            'grace_menit' => (int) ($setting->grace_period ?? 15),
            'sumber' => 'jadwal',
        ];
    }
}
