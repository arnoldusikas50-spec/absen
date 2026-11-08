<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Penugasan user ke shift (hari 0 = semua hari, 1..7 = weekday ISO).
 */
class ShiftAnggotaModel extends Model
{
    protected $table = 'absensi_shift_anggota';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'shift_id',
        'user_id',
        'hari',
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}
