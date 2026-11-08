<?php

namespace App\Models;

use CodeIgniter\Model;

class KalenderModel extends Model
{
    protected $table = 'kalender_akademik';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tgl_mulai', 'tgl_selesai', 'judul', 'deskripsi',
        'tipe', 'warna', 'created_by',
        'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getEvents($bulan, $tahun)
    {
        $tglAwal = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        return $this->getEventsByRange($tglAwal, $tglAkhir);
    }

    /**
     * Event yang menyentuh rentang tanggal (termasuk event multi-hari yang
     * dimulai sebelum $tglAwal dan berakhir setelahnya).
     */
    public function getEventsByRange($tglAwal, $tglAkhir)
    {
        $db = \Config\Database::connect();

        return $db->table('kalender_akademik')
            ->where('tgl_mulai <=', $tglAkhir)
            ->groupStart()
                ->groupStart()
                    ->where('tgl_selesai IS NOT NULL', null, false)
                    ->where('tgl_selesai >=', $tglAwal)
                ->groupEnd()
                ->orGroupStart()
                    ->where('tgl_selesai IS NULL', null, false)
                    ->where('tgl_mulai >=', $tglAwal)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('tgl_mulai', 'ASC')
            ->orderBy('tgl_selesai', 'ASC')
            ->get()->getResult();
    }

    /**
     * Hari libur absensi (tanggal merah/libur sekolah) dalam rentang tanggal.
     */
    public function getHolidaysByRange($tglAwal, $tglAkhir)
    {
        $db = \Config\Database::connect();

        return $db->table('absensi_hari_libur')
            ->where('tanggal >=', $tglAwal)
            ->where('tanggal <=', $tglAkhir)
            ->orderBy('tanggal', 'ASC')
            ->get()->getResult();
    }

    public function getUpcoming($limit = 5)
    {
        $db = \Config\Database::connect();
        return $db->table('kalender_akademik')
            ->where('tgl_mulai >=', date('Y-m-d'))
            ->orderBy('tgl_mulai', 'ASC')
            ->limit($limit)
            ->get()->getResult();
    }

    public function getHolidays($bulan, $tahun)
    {
        $db = \Config\Database::connect();
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        // From kalender_akademik
        $events = $db->table('kalender_akademik')
            ->select("tgl_mulai as tgl, judul, 'KALENDER' as sumber")
            ->where('tipe', 'LIBUR')
            ->where('tgl_mulai >=', $tglAwal)
            ->where('tgl_mulai <=', $tglAkhir)
            ->get()->getResult();

        // From absensi_hari_libur
        $libur = $db->table('absensi_hari_libur')
            ->select("tanggal as tgl, keterangan as judul, 'ABSENSI' as sumber")
            ->where('tanggal >=', $tglAwal)
            ->where('tanggal <=', $tglAkhir)
            ->get()->getResult();

        return array_merge($events, $libur);
    }

    public function getEvent($id)
    {
        return $this->find($id);
    }
}
