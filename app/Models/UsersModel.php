<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getDuplicateUsernames($table)
    {
        $builder = $this->db->table($table);
        $builder->select('username, COUNT(*) as total');
        $builder->groupBy('username');
        $builder->having('total >', 1);
        return $builder->get()->getResult();
    }

    public function usernameCheck($username, $table)
    {
        switch ($table) {
            case 'master_siswa':
                $select_name = 'nama';
                break;
            case 'master_panitia':
                $select_name = 'nama_panitia';
                break;
            default:
                $select_name = 'nama_guru';
        }

        return $this->db->table($table)
            ->select('username, ' . $select_name)
            ->where('username', $username)
            ->get()->getResult();
    }

    public function getDatausers($id = null)
    {
        $builder = $this->db->table('users_groups');
        $builder->select('users.id, username, first_name, last_name, email, FROM_UNIXTIME(created_on) as created_on, last_login, active, groups.name as level');
        $builder->join('users', 'users_groups.user_id=users.id');
        $builder->join('groups', 'users_groups.group_id=groups.id');
        if ($id !== null) {
            $builder->where('users.id !=', $id);
        }
        return $builder->get()->getResult();
    }

    public function getLevelGuru()
    {
        return $this->db->table('level_guru')->get()->getResult();
    }

    public function getAllAdmin()
    {
        $builder = $this->db->table('users_groups');
        $builder->select('users.id, username, first_name, last_name, email, FROM_UNIXTIME(created_on) as created_on, last_login, active, groups.name as level');
        $builder->join('users', 'users_groups.user_id=users.id');
        $builder->join('groups', 'users_groups.group_id=groups.id');
        $builder->where('group_id', 1);
        return $builder->get()->getResult();
    }

    // Alias for compatibility
    public function getDataadmin()
    {
        return $this->getAllAdmin();
    }

    public function getUserGuru($tp, $smt)
    {
        $builder = $this->db->table('master_guru a');
        $builder->select('a.id_guru, a.nama_guru, a.username, a.password, a.foto, c.level, e.id, e.active');
        $builder->select('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = a.username) AS reset');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_guru')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('jabatan_guru b', 'a.id_guru=b.id_guru AND b.id_tp=' . $tp . ' AND b.id_smt=' . $smt . '', 'left');
        $builder->join('level_guru c', 'b.id_jabatan=c.id_level', 'left');
        $builder->join('users e', 'a.id_user=e.id', 'left');
        return $builder->get()->getResult();
    }

    public function getDataGuru($id)
    {
        return $this->db->table('master_guru')->where('id_guru', $id)->get()->getRow();
    }

    public function getDetailGuru($id)
    {
        $builder = $this->db->table('master_guru a');
        $builder->select('a.id_guru, a.nama_guru, a.username, a.password, a.email, c.level, e.id, e.active');
        $builder->join('jabatan_guru b', 'a.id_guru=b.id_guru', 'left');
        $builder->join('level_guru c', 'b.id_jabatan=c.id_level', 'left');
        $builder->join('users e', 'a.username=e.username', 'left');
        $builder->where('a.id_guru', $id);
        return $builder->get()->getRow();
    }

    public function getUsers($username)
    {
        return $this->db->table('users')->where('username', $username)->get()->getRow();
    }

    public function getGuruByUsername($username)
    {
        return $this->db->table('master_guru')->where('username', $username)->get()->getRow();
    }

    public function getUserPanitia()
    {
        if (!$this->db->tableExists('master_panitia'))
            return [];
        $count = $this->db->table('master_panitia')->countAllResults();
        if ($count == 0)
            return [];

        $builder = $this->db->table('master_panitia a');
        $builder->select('a.id_panitia, a.nama_panitia, a.nip, a.username, a.password, a.instansi, a.jabatan, a.ruang, a.email, a.no_hp, a.foto, a.status, u.id, u.active');
        $builder->select('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = a.username) AS reset');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_panitia')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('users u', 'a.id_user = u.id', 'left');
        $builder->orderBy('a.nama_panitia', 'ASC');

        $result = $builder->get()->getResult();
        foreach ($result as $row) {
            $row->foto = ($row->foto && file_exists($row->foto)) ? base_url($row->foto) : base_url('assets/img/profile-default.png');
        }
        return $result;
    }

    public function getDataPanitia($id)
    {
        if (!$this->db->tableExists('master_panitia'))
            return null;
        return $this->db->table('master_panitia')->where('id_panitia', $id)->get()->getRow();
    }

    public function getKelas($tp, $smt)
    {
        $builder = $this->db->table('master_kelas');
        $builder->where('id_tp', $tp)->where('id_smt', $smt);

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_kelas')) {
            $builder->where('sync_school_uuid', $school_uuid);
        }
        return $builder->get()->getResult();
    }

    public function getMapel()
    {
        $builder = $this->db->table('master_mapel');
        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_mapel')) {
            $builder->where('sync_school_uuid', $school_uuid);
        }
        return $builder->get()->getResult();
    }

    public function getUserSiswaPage($id_tp, $id_smt, $offset, $limit, $search = null, $sort = null, $order = null, $reset = false)
    {
        $builder = $this->db->table('master_siswa a');
        $builder->select('a.id_siswa, a.nis, a.foto, a.nama, a.username, a.password, d.id_kelas, f.nama_kelas, us.active');
        $builder->select('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = a.username) AS reset');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_siswa')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('buku_induk bu', 'bu.id_siswa=a.id_siswa AND bu.status="1"');
        $builder->join('kelas_siswa d', 'd.id_siswa=a.id_siswa AND d.id_tp = ' . $id_tp . ' AND d.id_smt = ' . $id_smt . '', 'left');
        $builder->join('master_kelas f', 'f.id_kelas=d.id_kelas', 'left');
        $builder->join('users us', 'us.username=a.username', 'left');

        $builder->orderBy('IF(ISNULL(f.level_id), 1, 0)');
        $builder->orderBy('f.level_id', 'ASC');
        $builder->orderBy('f.nama_kelas', 'ASC');
        $builder->orderBy('a.nama', 'ASC');

        if ($search) {
            $builder->groupStart()
                ->like('a.nama', $search)
                ->orLike('a.nis', $search)
                ->orLike('a.nisn', $search)
                ->groupEnd();
        }

        if ($reset) {
            $builder->where('EXISTS (SELECT 1 FROM login_attempts WHERE login_attempts.login = a.username)');
        }

        return $builder->limit($limit, $offset)->get()->getResult();
    }

    public function getUserSiswaTotalPage($search = null, $reset = false)
    {
        $builder = $this->db->table('master_siswa a');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_siswa')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('buku_induk bu', 'bu.id_siswa=a.id_siswa AND bu.status="1"');

        if ($search) {
            $builder->groupStart()
                ->like('a.nama', $search)
                ->orLike('a.nis', $search)
                ->orLike('a.nisn', $search)
                ->groupEnd();
        }

        if ($reset) {
            $builder->where('EXISTS (SELECT 1 FROM login_attempts WHERE login_attempts.login = a.username)');
        }

        return $builder->countAllResults();
    }

    public function getUserSiswa($tp, $smt)
    {
        $builder = $this->db->table('master_siswa a');
        $builder->select('a.id_siswa, a.nis, a.nama, a.username, a.password, c.nama_kelas, d.id, d.active');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && $this->db->fieldExists('sync_school_uuid', 'master_siswa')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('kelas_siswa b', 'b.id_siswa=a.id_siswa AND b.id_tp=' . $tp . ' AND b.id_smt=' . $smt . '', 'left');
        $builder->join('master_kelas c', 'c.id_kelas=b.id_kelas', 'left');
        $builder->join('users d', 'd.username=a.username', 'left');

        return $builder->get()->getResult();
    }
}
