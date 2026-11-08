<?php

namespace App\Models;

use CodeIgniter\Model;

class PanitiaModel extends Model
{
    protected $table = 'master_panitia';
    protected $primaryKey = 'id_panitia';
    protected $allowedFields = [
        'id_user',
        'nama_panitia',
        'nip',
        'username',
        'password',
        'instansi',
        'jabatan',
        'ruang',
        'email',
        'no_hp',
        'foto',
        'status',
        'sync_school_uuid'
    ];
    protected $useTimestamps = false;
    protected $returnType = 'object';

    public function get_all()
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' p');
        $builder->select('p.*, u.id as user_id, u.active, u.last_login');
        // Add subquery for reset_attempts if needed, or join
        $builder->select('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = p.username) AS reset_attempts');
        $builder->join('users u', 'p.id_user = u.id', 'left');
        $builder->orderBy('p.nama_panitia', 'ASC');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $db->fieldExists('sync_school_uuid', $this->table)) {
            $builder->where('p.sync_school_uuid', $school_uuid);
        }

        return $builder->get()->getResult();
    }

    public function get_by_id($id)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' p');
        $builder->select('p.*, u.id as user_id, u.active');
        $builder->join('users u', 'p.id_user = u.id', 'left');
        $builder->where('p.id_panitia', $id);
        return $builder->get()->getRow();
    }

    public function get_by_username($username)
    {
        return $this->where('username', $username)->first();
    }

    public function create($data)
    {
        $this->insert($data);
        return $this->getInsertID();
    }
}
