<?php

namespace App\Models;

use CodeIgniter\Model;

class FaceRegistrationRequestModel extends Model
{
    protected $table = 'face_registration_requests';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'face_descriptor',
        'status',
        'admin_id',
        'keterangan'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
