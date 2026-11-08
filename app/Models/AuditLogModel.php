<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'table_name', 'record_id', 'target_user_id', 'actor_id',
        'action', 'old_values', 'new_values', 'reason',
        'ip_address', 'user_agent', 'created_at'
    ];

    // Dates
    protected $useTimestamps = false; // We manage created_at manually or via trigger if needed, but here simple insert is fine.
    // Actually, let's use timestamps for created_at
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Helper to log an action
     */
    public function log($tableName, $recordId, $action, $targetUserId = null, $actorId = null, $oldValues = null, $newValues = null, $reason = null)
    {
        $request = \Config\Services::request();
        // CLIRequest tidak punya getUserAgent(); tanpa penjagaan ini setiap
        // audit yang ditulis dari jalur CLI/worker melempar exception yang
        // lalu ditelan try/catch pemanggil — jejak hilang tanpa suara.
        $agent = method_exists($request, 'getUserAgent') ? $request->getUserAgent() : null;

        $formatValues = function ($val) {
            if ($val === null) {
                return null;
            }
            if (is_array($val) || is_object($val)) {
                return json_encode($val, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }
            return (string) $val;
        };

        $data = [
            'table_name'     => $tableName,
            'record_id'      => $recordId,
            'target_user_id' => $targetUserId,
            'actor_id'       => $actorId ?? session()->get('user_id'), // Fallback to current session user
            'action'         => $action,
            'old_values'     => $formatValues($oldValues),
            'new_values'     => $formatValues($newValues),
            'reason'         => $reason,
            'ip_address'     => method_exists($request, 'getIPAddress') ? $request->getIPAddress() : 'cli',
            'user_agent'     => $agent ? $agent->getAgentString() : '',
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        return $this->insert($data);
    }
}
