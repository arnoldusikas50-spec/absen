<?php

namespace App\Models;

use CodeIgniter\Model;

class TujuanPembelajaranModel extends Model
{
    protected $table = 'e_tujuan_pembelajaran';
    protected $primaryKey = 'id_tujuan';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = [
        'id_guru',
        'id_mapel',
        'id_tp',
        'id_smt',
        'tingkat', // 7, 8, 9, 10, 11, 12
        'fase', // D, E, F
        'kode',
        'kode_cp',
        'ranah', // K,S,P
        'level', // C1, C2, C3, C4, C5, C6
        'status', // 1=Aktif, 0=Nonaktif
        'perangkat_count',
        'tujuan',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;

    public function initialize()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            $forge->addField([
                'id_tujuan' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'id_mapel' => ['type' => 'INT', 'constraint' => 11],
                'id_tp' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'tingkat' => ['type' => 'INT', 'constraint' => 2, 'default' => 0],
                'fase' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true], // A, B, C, D, E, F
                'kode' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'kode_cp' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'ranah' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'K,S,P'],
                'level' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'C1'],
                'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'perangkat_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'tujuan' => ['type' => 'TEXT'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
            $forge->addKey('id_tujuan', true);
            $forge->addKey('id_guru');
            $forge->addKey(['id_mapel', 'id_guru']);
            $forge->createTable($this->table, true);
        } else {
            // Check for new columns
            $fieldsToadd = [
                'tingkat' => ['type' => 'INT', 'constraint' => 2, 'default' => 0, 'after' => 'id_smt'],
                'fase' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'after' => 'tingkat'],
                'kode_cp' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'kode'],
                'ranah' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'K,S,P', 'after' => 'kode_cp'],
                'level' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'C1', 'after' => 'ranah'],
                'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'level'],
                'perangkat_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'status']
            ];

            $existingFields = array_map('strtolower', $db->getFieldNames($this->table));
            $fieldsToAddReally = [];
            foreach ($fieldsToadd as $field => $def) {
                if (!in_array(strtolower($field), $existingFields)) {
                    $fieldsToAddReally[$field] = $def;
                }
            }

            if (!empty($fieldsToAddReally)) {
                try {
                    $forge->addColumn($this->table, $fieldsToAddReally);
                } catch (\Throwable $e) {
                    // Ignore if already exists
                }
            }
        }
    }

    public function getTujuanByGuru($id_guru, $id_tp, $id_smt = null, $id_mapel = null, array $filters = [])
    {
        $builder = $this->db->table($this->table . ' t');
        $builder->select('t.*, m.nama_mapel, m.kode as kode_mapel, g.nama_guru, g.nip');
        $builder->join('master_mapel m', 'm.id_mapel = t.id_mapel', 'left');
        $builder->join('master_guru g', 'g.id_guru = t.id_guru', 'left');

        if (!empty($id_guru) && $id_guru !== 'all') {
            $builder->where('t.id_guru', $id_guru);
        }
        if (!empty($id_tp)) {
            $builder->where('t.id_tp', $id_tp);
        }
        if (!empty($id_smt)) {
            $builder->where('t.id_smt', $id_smt);
        }
        if (!empty($id_mapel)) {
            $builder->where('t.id_mapel', $id_mapel);
        }
        if (!empty($filters['tingkat'])) {
            $builder->where('t.tingkat', (int) $filters['tingkat']);
        }
        if (!empty($filters['fase'])) {
            $builder->where('t.fase', $filters['fase']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('t.tujuan', $filters['search'])
                ->orLike('t.kode', $filters['search'])
                ->orLike('m.nama_mapel', $filters['search'])
                ->orLike('g.nama_guru', $filters['search'])
                ->groupEnd();
        }

        $builder->orderBy('m.nama_mapel', 'ASC');
        $builder->orderBy('t.tingkat', 'ASC');
        $builder->orderBy('t.kode', 'ASC');

        return $builder->get()->getResult();
    }
}
