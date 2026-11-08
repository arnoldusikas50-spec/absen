<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardTierModel extends Model
{
    protected $table = 'reward_tiers';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['nama_tier', 'poin_min', 'poin_max', 'badge', 'warna', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getTierByPoin($poin)
    {
        $poin = (int) $poin;
        // poin_max = 0 berarti tak terhingga; pilih tier dengan poin_min terbesar yang cocok
        return $this->groupStart()
                ->where('poin_min <=', $poin)
                ->groupStart()
                    ->where('poin_max >=', $poin)
                    ->orWhere('poin_max', 0)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('poin_min', 'DESC')
            ->first();
    }
}
