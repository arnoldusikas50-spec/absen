<?php

namespace App\Models;

use CodeIgniter\Model;

class GuruJadwalPribadiModel extends Model
{
    protected $table = 'guru_jadwal_pribadi';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'id_tp',
        'id_smt',
        'id_guru',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'id_mapel',
        'id_kelas',
        'ruangan',
    ];

    public function initialize()
    {
        $forge = \Config\Database::forge();
        $db = \Config\Database::connect();

        if (!$db->tableExists($this->table)) {
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_tp' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'hari' => ['type' => 'TINYINT', 'constraint' => 1],
                'jam_mulai' => ['type' => 'TIME'],
                'jam_selesai' => ['type' => 'TIME'],
                'id_mapel' => ['type' => 'INT', 'constraint' => 11],
                'id_kelas' => ['type' => 'INT', 'constraint' => 11],
                'ruangan' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ]);

            $forge->addKey('id', true);
            $forge->addKey(['id_guru', 'id_tp', 'id_smt', 'hari']);
            $forge->addKey(['id_kelas', 'id_tp', 'id_smt', 'hari']);
            $forge->createTable($this->table, true);
        }
    }

    public function getByGuru(int $idGuru, int $idTp, int $idSmt): array
    {
        return $this->db->table($this->table . ' j')
            ->select('j.*')
            ->select('m.nama_mapel', false)
            ->select('k.nama_kelas', false)
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->where('j.id_guru', $idGuru)
            ->where('j.id_tp', $idTp)
            ->where('j.id_smt', $idSmt)
            ->orderBy('j.hari', 'ASC')
            ->orderBy('j.jam_mulai', 'ASC')
            ->get()->getResult();
    }

    public function getByGuruAndHari(int $idGuru, int $idTp, int $idSmt, int $hari): array
    {
        return $this->db->table($this->table . ' j')
            ->select('j.*')
            ->select('m.nama_mapel', false)
            ->select('k.nama_kelas', false)
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->where('j.id_guru', $idGuru)
            ->where('j.id_tp', $idTp)
            ->where('j.id_smt', $idSmt)
            ->where('j.hari', $hari)
            ->orderBy('j.jam_mulai', 'ASC')
            ->get()->getResult();
    }

    public function findConflictsForGuru(int $idGuru, int $idTp, int $idSmt, int $hari, string $mulai, string $selesai, ?int $excludeId = null): array
    {
        $builder = $this->db->table($this->table)
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('hari', $hari)
            ->where('jam_mulai <', $selesai)
            ->where('jam_selesai >', $mulai);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->get()->getResult();
    }

    public function findConflictsForKelas(int $idKelas, int $idTp, int $idSmt, int $hari, string $mulai, string $selesai, ?int $excludeId = null): array
    {
        $builder = $this->db->table($this->table)
            ->where('id_kelas', $idKelas)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('hari', $hari)
            ->where('jam_mulai <', $selesai)
            ->where('jam_selesai >', $mulai);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->get()->getResult();
    }
}

