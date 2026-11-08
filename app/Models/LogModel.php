<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * LogModel - Migrated from CI3 Log_model
 * Handles activity logging
 */
class LogModel extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'id_log';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = [
        'id_user',
        'aktivitas',
        'keterangan',
        'ip_address',
        'user_agent',
        'waktu'
    ];

    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        $this->_ensureTableExists();
    }

    /**
     * Ensure logs table exists, create if not
     */
    private function _ensureTableExists(): void
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('logs')) {
            $forge = \Config\Database::forge();

            $forge->addField([
                'id_log' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_user' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'aktivitas' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'comment' => '1=Login, 2=Logout, 3=Create, 4=Read, 5=Update, 6=Delete',
                ],
                'keterangan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ],
                'user_agent' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'waktu' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $forge->addKey('id_log', true);
            $forge->addKey('id_user');
            $forge->addKey('waktu');
            $forge->createTable('logs', true);
        }
    }

    /**
     * Save activity log
     * 
     * @param int    $aktivitas Activity type (1=Login, 2=Logout, etc)
     * @param string $keterangan Description of activity
     * @param int|null $userId Optional user ID (if not from session)
     */
    public function saveLog(int $aktivitas, string $keterangan = '', ?int $userId = null): bool
    {
        $userId = $userId ?? session()->get('user_id');

        if (!$userId) {
            return false;
        }

        try {
            $data = [
                'id_user' => $userId,
                'aktivitas' => $aktivitas,
                'keterangan' => $keterangan,
                'ip_address' => service('request')->getIPAddress(),
                'user_agent' => service('request')->getUserAgent()->getAgentString(),
                'waktu' => date('Y-m-d H:i:s')
            ];

            return $this->insert($data) !== false;
        } catch (\Exception $e) {
            // Log table may not exist, ignore error
            return false;
        }
    }

    /**
     * Get recent logs
     */
    public function getRecentLogs(int $limit = 50): array
    {
        return $this->orderBy('waktu', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get logs by user
     */
    public function getLogsByUser(int $userId, int $limit = 50): array
    {
        return $this->where('id_user', $userId)
            ->orderBy('waktu', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Delete log by ID
     */
    public function hapusLog(int $idLog): bool
    {
        return $this->delete($idLog);
    }
    /**
     * Get logs with user details (CI3 parity)
     */
    public function getLog($offset, $limit, $search = null, $filters = null)
    {
        $builder = $this->db->table('logs a');
        $builder->select('a.*, b.first_name, b.last_name, b.foto');
        //$builder->join('users b', 'b.id=a.id_user', 'left');
        // In CI4 Auth, id is 'id', but in LogModel join condition was 'id=id_user' or similar
        $builder->join('users b', 'b.id = a.id_user', 'left');

        if ($filters != null && is_array($filters)) {
            // Note: CI4 logs table in migration didn't have id_group column shown in _ensureTableExists
            // We might skip this filter or assume it exists if table created manually or extended
            // For now, removing id_group filter to avoid SQL error if column absent
            // $builder->whereIn('a.id_group', $filters);
        }

        if ($search) {
            $builder->groupStart();
            $builder->like('a.keterangan', $search);
            $builder->orLike('b.first_name', $search);
            $builder->orLike('b.last_name', $search);
            $builder->groupEnd();
        }

        $builder->orderBy('a.waktu', 'DESC');
        $builder->limit($limit, $offset);

        return $builder->get()->getResult();
    }

    public function getCountLog($search = null, $filters = null)
    {
        $builder = $this->db->table('logs a');
        $builder->join('users b', 'b.id = a.id_user', 'left');

        if ($search) {
            $builder->groupStart();
            $builder->like('a.keterangan', $search);
            $builder->orLike('b.first_name', $search);
            $builder->orLike('b.last_name', $search);
            $builder->groupEnd();
        }

        return $builder->countAllResults();
    }
}
