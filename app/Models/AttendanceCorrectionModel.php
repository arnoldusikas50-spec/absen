<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceCorrectionModel extends Model
{
    protected $table = 'absensi_koreksi';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'attendance_id',
        'user_id',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'admin_note'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get pending corrections with user and attendance info.
     * LEFT JOIN agar baris tetap terlihat walau user/guru/absensi terkait
     * sudah dihapus (kasus tsb otomatis ditolak saat diproses).
     */
    public function getPendingWithDetails()
    {
        return $this->select('absensi_koreksi.*,
                             COALESCE(mg.nama_guru, TRIM(CONCAT_WS(\' \', u.first_name, u.last_name)), u.username) as nama_guru,
                             mg.nip, u.username,
                             a.tgl, a.jam_masuk, a.jam_pulang, a.status_masuk')
            ->join('users u', 'u.id = absensi_koreksi.user_id', 'left')
            ->join('master_guru mg', 'mg.id_user = u.id', 'left')
            ->join('absensi_harian a', 'a.id = absensi_koreksi.attendance_id', 'left')
            ->where('absensi_koreksi.status', 'pending')
            ->orderBy('absensi_koreksi.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Riwayat koreksi yang sudah diproses (approved/rejected) + auto-rejected sistem.
     */
    public function getHistoryWithDetails($limit = 100)
    {
        return $this->select('absensi_koreksi.*,
                             COALESCE(mg.nama_guru, TRIM(CONCAT_WS(\' \', u.first_name, u.last_name)), u.username) as nama_guru,
                             mg.nip, u.username,
                             a.tgl, a.jam_masuk, a.jam_pulang, a.status_masuk,
                             CONCAT_WS(\' \', adm.first_name, adm.last_name) as diproses_oleh')
            ->join('users u', 'u.id = absensi_koreksi.user_id', 'left')
            ->join('master_guru mg', 'mg.id_user = u.id', 'left')
            ->join('absensi_harian a', 'a.id = absensi_koreksi.attendance_id', 'left')
            ->join('users adm', 'adm.id = absensi_koreksi.processed_by', 'left')
            ->whereIn('absensi_koreksi.status', ['approved', 'rejected'])
            ->orderBy('absensi_koreksi.processed_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
