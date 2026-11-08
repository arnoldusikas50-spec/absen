<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardRedemptionModel extends Model
{
    protected $table = 'reward_redemptions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'user_id', 'reward_id', 'poin_digunakan', 'status',
        'keterangan', 'approved_by', 'claimed_at', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;

    public function getRiwayat($userId, $limit = 20)
    {
        $db = \Config\Database::connect();
        return $db->table('reward_redemptions rr')
            ->select('rr.*, r.nama as reward_nama, r.poin_dibutuhkan, r.nominal')
            ->join('rewards r', 'r.id = rr.reward_id')
            ->where('rr.user_id', $userId)
            ->orderBy('rr.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResult();
    }

    public function getPending()
    {
        $db = \Config\Database::connect();
        return $db->table('reward_redemptions rr')
            ->select('rr.*, r.nama as reward_nama, r.nominal, r.poin_dibutuhkan as reward_poin, u.first_name, u.last_name')
            ->join('rewards r', 'r.id = rr.reward_id', 'left')
            ->join('users u', 'u.id = rr.user_id', 'left')
            ->where('rr.status', 'PENDING')
            ->orderBy('rr.created_at', 'ASC')
            ->get()->getResult();
    }
}
