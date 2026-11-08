<?php

namespace App\Models;

use CodeIgniter\Model;

class PiketJadwalModel extends Model
{
    protected $table = 'piket_jadwal';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_tp', 'id_smt', 'hari', 'id_guru', 'keterangan'];

    public function initialize()
    {
        $forge = \Config\Database::forge();
        $db = \Config\Database::connect();

        if (!$db->tableExists('piket_jadwal')) {
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_tp' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'hari' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'Senin, Selasa, etc.'],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ]);
            $forge->addKey('id', true);
            $forge->addKey(['id_tp', 'id_smt', 'hari']);
            $forge->createTable('piket_jadwal', true);
        }
    }

    public function getJadwalByTpSmt($id_tp, $id_smt)
    {
        return $this->db->table($this->table . ' pj')
            ->select('pj.*, g.nama_guru, g.nip')
            ->join('master_guru g', 'g.id_guru = pj.id_guru')
            ->where('pj.id_tp', $id_tp)
            ->where('pj.id_smt', $id_smt)
            ->orderBy('FIELD(pj.hari, "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu")')
            ->get()->getResult();
    }

    public function getPiketHariIni($hari, $id_tp, $id_smt)
    {
        return $this->db->table($this->table . ' pj')
            ->select('pj.*, g.nama_guru, g.nip, g.id_user')
            ->join('master_guru g', 'g.id_guru = pj.id_guru')
            ->where('pj.id_tp', $id_tp)
            ->where('pj.id_smt', $id_smt)
            ->where('pj.hari', $hari)
            ->get()->getResult();
    }

    /**
     * Jadwal piket per guru (hari-hari tugas guru yang login).
     * Dipakai frontend presensi /guru/piket untuk "Jadwal Saya".
     */
    public function getJadwalByGuru($id_guru, $id_tp, $id_smt)
    {
        return $this->db->table($this->table . ' pj')
            ->select('pj.hari, pj.keterangan')
            ->where('pj.id_guru', $id_guru)
            ->where('pj.id_tp', $id_tp)
            ->where('pj.id_smt', $id_smt)
            ->orderBy('FIELD(pj.hari, "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu")')
            ->get()->getResult();
    }

    /**
     * Jadwal piket seminggu dikelompokkan per hari: ['Senin' => [...], ...].
     * Dipakai frontend presensi /guru/piket untuk "Jadwal Mingguan".
     */
    public function getJadwalMingguan($id_tp, $id_smt)
    {
        $rows = $this->getJadwalByTpSmt($id_tp, $id_smt);
        $order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $grouped = array_fill_keys($order, []);
        foreach ($rows as $r) {
            $hari = $r->hari ?? '';
            if (!isset($grouped[$hari])) $grouped[$hari] = [];
            $grouped[$hari][] = $r;
        }
        return $grouped;
    }
}
