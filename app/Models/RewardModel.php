<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardModel extends Model
{
    protected $table = 'rewards';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['nama', 'deskripsi', 'poin_dibutuhkan', 'nominal', 'stok', 'gambar', 'is_active', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
