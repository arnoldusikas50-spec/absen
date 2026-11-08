<?php

namespace App\Controllers\Guru;

use App\Models\KalenderModel;

class Kalender extends \App\Controllers\BaseController
{
    protected $kalenderModel;
    protected $db;

    public function __construct()
    {
        $this->kalenderModel = new KalenderModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Grup rute hanya 'auth' (semua peran) — halaman ini milik guru.
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');

        [$bulan, $tahun] = $this->bulanTahunValid();

        $events = $this->kalenderModel->getEvents($bulan, $tahun);
        $holidays = $this->kalenderModel->getHolidays($bulan, $tahun);

        $dashboardModel = new \App\Models\DashboardModel();
        $setting = $dashboardModel->getSetting();

        return view('members/guru/kalender_mobile', [
            'title' => 'Kalender Akademik',
            'events' => $events,
            'holidays' => $holidays,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'setting' => $setting,
        ]);
    }

    public function getEvents()
    {
        if (!$this->inGroup('guru')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        [$bulan, $tahun] = $this->bulanTahunValid();

        $events = $this->kalenderModel->getEvents($bulan, $tahun);
        $holidays = $this->kalenderModel->getHolidays($bulan, $tahun);

        $result = [];
        $existingKeys = [];

        foreach ($events as $e) {
            $key = ($e->tgl_mulai) . '_' . strtolower(trim($e->judul));
            $existingKeys[$key] = true;

            $result[] = [
                'id' => $e->id,
                'title' => $e->judul,
                'start' => $e->tgl_mulai,
                'end' => $e->tgl_selesai ?: $e->tgl_mulai,
                'end_exclusive' => $e->tgl_selesai ? date('Y-m-d', strtotime($e->tgl_selesai . ' +1 day')) : $e->tgl_mulai,
                'description' => $e->deskripsi ?? '',
                'color' => $e->warna ?: '#6366f1',
                'tipe' => strtoupper($e->tipe ?: 'EVENT'),
            ];
        }

        foreach ($holidays as $h) {
            $key = ($h->tgl) . '_' . strtolower(trim($h->judul));
            if (isset($existingKeys[$key])) {
                continue; // Prevent duplicate entries
            }

            $result[] = [
                'id' => 'libur_' . $h->tgl,
                'title' => $h->judul,
                'start' => $h->tgl,
                'end' => $h->tgl,
                'end_exclusive' => $h->tgl,
                'description' => 'Hari Libur ' . ($h->sumber === 'ABSENSI' ? 'Resmi Sekolah / Nasional' : 'Kalender Akademik'),
                'color' => '#ef4444',
                'tipe' => 'LIBUR',
                'sumber' => $h->sumber,
            ];
        }

        return $this->response->setJSON($result);
    }

    /**
     * bulan/tahun dari query string hanya untuk memilih tampilan — paksa
     * integer dalam rentang wajar agar string liar tidak masuk ke rentang
     * tanggal yang dibangun model.
     *
     * @return array{0:string,1:string} bulan '01'-'12', tahun 4 digit
     */
    private function bulanTahunValid(): array
    {
        $bulan = (int) ($this->request->getGet('bulan') ?: date('m'));
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));
        if ($bulan < 1 || $bulan > 12) $bulan = (int) date('m');
        $minTahun = 2000;
        $maxTahun = (int) date('Y') + 1;
        if ($tahun < $minTahun || $tahun > $maxTahun) $tahun = (int) date('Y');
        return [str_pad((string) $bulan, 2, '0', STR_PAD_LEFT), (string) $tahun];
    }
}
