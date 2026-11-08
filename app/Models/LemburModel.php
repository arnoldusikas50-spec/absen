<?php

namespace App\Models;

use CodeIgniter\Model;

class LemburModel extends Model
{
    protected $table = 'presensi_lembur';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id', 'id_guru', 'tgl', 'jam_mulai', 'jam_selesai',
        'total_jam', 'keterangan', 'status',
        'approved_by', 'approved_at', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getLemburUser($userId, $limit = 20)
    {
        $db = \Config\Database::connect();
        return $db->table('presensi_lembur')
            ->where('user_id', $userId)
            ->orderBy('tgl', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()->getResult();
    }

    public function getPending()
    {
        $db = \Config\Database::connect();
        return $db->table('presensi_lembur pl')
            ->select('pl.*, COALESCE(mg.nama_guru, TRIM(CONCAT_WS(\' \', u.first_name, u.last_name)), u.username) as nama_guru, mg.nip')
            ->join('master_guru mg', 'mg.id_guru = pl.id_guru', 'left')
            ->join('users u', 'u.id = pl.user_id', 'left')
            ->where('pl.status', 'PENDING')
            ->orderBy('pl.tgl', 'DESC')
            ->orderBy('pl.created_at', 'DESC')
            ->get()->getResult();
    }

    public function getRekap($bulan, $tahun)
    {
        $db = \Config\Database::connect();
        $bulan = str_pad((int) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $tahun;
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        return $db->table('presensi_lembur pl')
            ->select("pl.user_id,
                COALESCE(mg.nama_guru, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) as nama_guru,
                mg.nip,
                COUNT(pl.id) as total_lembur,
                COALESCE(SUM(pl.total_jam), 0) as total_jam,
                SUM(CASE WHEN pl.status = 'APPROVED' THEN pl.total_jam ELSE 0 END) as jam_disetujui,
                SUM(CASE WHEN pl.status = 'APPROVED' THEN 1 ELSE 0 END) as lembur_disetujui,
                SUM(CASE WHEN pl.status = 'PENDING' THEN pl.total_jam ELSE 0 END) as jam_pending,
                SUM(CASE WHEN pl.status = 'PENDING' THEN 1 ELSE 0 END) as lembur_pending,
                SUM(CASE WHEN pl.status = 'REJECTED' THEN 1 ELSE 0 END) as lembur_ditolak")
            ->join('master_guru mg', 'mg.id_guru = pl.id_guru', 'left')
            ->join('users u', 'u.id = pl.user_id', 'left')
            ->where('pl.tgl >=', $tglAwal)
            ->where('pl.tgl <=', $tglAkhir)
            ->groupBy('pl.user_id, mg.nama_guru, mg.nip, u.first_name, u.last_name, u.username')
            ->orderBy('jam_disetujui', 'DESC')
            ->orderBy('total_jam', 'DESC')
            ->get()->getResult();
    }

    public function getRiwayat($bulan, $tahun, $limit = 200)
    {
        $db = \Config\Database::connect();
        $bulan = str_pad((int) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $tahun;
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        return $db->table('presensi_lembur pl')
            ->select('pl.*, COALESCE(mg.nama_guru, TRIM(CONCAT_WS(\' \', u.first_name, u.last_name)), u.username) as nama_guru, mg.nip')
            ->join('master_guru mg', 'mg.id_guru = pl.id_guru', 'left')
            ->join('users u', 'u.id = pl.user_id', 'left')
            ->where('pl.tgl >=', $tglAwal)
            ->where('pl.tgl <=', $tglAkhir)
            ->orderBy('pl.tgl', 'DESC')
            ->orderBy('pl.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResult();
    }
}
