<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\MasterModel;
use App\Models\PoinSiswaModel;
use Config\Database;

/**
 * Modul Poin Kedisiplinan Siswa (admin).
 * Skor awal 100, pelanggaran mengurangi, penghargaan menambah.
 * Pencatatan manual HANYA dari master disiplin_kategori.
 */
class PoinSiswa extends BaseController
{
    protected $db;
    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;
    protected PoinSiswaModel $poinModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
        $this->poinModel = new PoinSiswaModel();
        $this->poinModel->ensureTables();
    }

    /**
     * Leaderboard + kelola kategori.
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $setting = $this->dashboardModel->getSetting();

        $idKelas = (int) ($this->request->getGet('kelas') ?? 0);
        $kelasList = [];
        if ($tp && $smt) {
            try {
                $kelasList = $this->masterModel->getAllKelas((int) $tp->id_tp, (int) $smt->id_smt);
            } catch (\Throwable $e) {
                $kelasList = [];
            }
        }

        $leaderboard = $this->poinModel->getLeaderboard($idKelas ?: null);
        $kategori = $this->poinModel->getKategori(false);

        // Statistik ringkas
        $totalSiswa = count($leaderboard);
        $skorList = array_map(fn($r) => (int) ($r->skor ?? PoinSiswaModel::SKOR_AWAL), $leaderboard);
        $avg = $totalSiswa ? array_sum($skorList) / $totalSiswa : PoinSiswaModel::SKOR_AWAL;
        $diBawah75 = count(array_filter($skorList, fn($s) => $s < 75));
        try {
            $kasusBulanIni = (int) $this->db->table('poin_siswa_history')
                ->where('MONTH(created_at)', date('n'))
                ->where('YEAR(created_at)', date('Y'))
                ->countAllResults();
        } catch (\Throwable $e) {
            $kasusBulanIni = 0;
        }

        return view('admin/poin_siswa/index', [
            'user' => $this->getUser(),
            'judul' => 'Poin Disiplin Siswa',
            'subjudul' => 'Poin Kedisiplinan Siswa',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'kelas_list' => $kelasList,
            'filter_kelas' => $idKelas,
            'leaderboard' => $leaderboard,
            'kategori' => $kategori,
            'stats' => [
                'total_siswa' => $totalSiswa,
                'rata_rata' => $avg,
                'di_bawah_75' => $diBawah75,
                'kasus_bulan_ini' => $kasusBulanIni,
            ],
        ]);
    }

    /**
     * Detail satu siswa: skor + grafik bulanan + riwayat.
     */
    public function detail($idSiswa)
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin() && !$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $idSiswa = (int) $idSiswa;
        $siswa = $this->masterModel->getSiswaById($idSiswa);
        if (!$siswa) {
            return redirect()->to('admin/poin-siswa')->with('error', 'Siswa tidak ditemukan');
        }

        $tahun = (int) ($this->request->getGet('tahun') ?? date('Y'));
        $tahun = max(2020, min($tahun, (int) date('Y') + 1));

        return view('admin/poin_siswa/detail', [
            'user' => $this->getUser(),
            'judul' => 'Detail Poin Siswa',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'siswa' => $siswa,
            'skor' => $this->poinModel->getSkor($idSiswa),
            'tahun' => $tahun,
            'monthly' => $this->poinModel->getMonthly($idSiswa, $tahun),
            'type_summary' => $this->poinModel->getTypeSummary($idSiswa, $tahun),
            'riwayat' => $this->poinModel->getRiwayat($idSiswa, 100),
            'kategori' => $this->poinModel->getKategori(true),
        ]);
    }

    /**
     * Catat poin manual (admin & guru piket via endpoint guru).
     * HANYA kategori master aktif.
     */
    public function record()
    {
        if (!$this->isLoggedIn() || (!$this->isAdmin() && !$this->isGuru())) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $res = $this->poinModel->recordManual(
            (int) $this->request->getPost('id_siswa'),
            (int) $this->request->getPost('kategori_id'),
            (string) ($this->request->getPost('keterangan') ?? ''),
            $this->getUserId()
        );
        return $this->respond($res['status'], $res['message'] ?? '', $res['skor'] ?? null);
    }

    /**
     * CRUD master kategori (admin saja).
     */
    public function saveKategori()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $id = ($this->request->getPost('id') ?? '') !== '' ? (int) $this->request->getPost('id') : null;
        $res = $this->poinModel->saveKategori([
            'nama' => (string) ($this->request->getPost('nama') ?? ''),
            'jenis' => (string) ($this->request->getPost('jenis') ?? ''),
            'bobot' => (int) ($this->request->getPost('bobot') ?? 0),
            'is_active' => (int) ($this->request->getPost('is_active') ?? 1),
        ], $id);
        return $this->respond($res['status'], $res['message'] ?? '');
    }

    public function deleteKategori()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $res = $this->poinModel->deleteKategori((int) $this->request->getPost('id'));
        return $this->respond($res['status'], $res['message'] ?? '');
    }

    /**
     * Riwayat JSON untuk modal (limit 10–100).
     */
    public function getHistory($idSiswa)
    {
        if (!$this->isLoggedIn() || (!$this->isAdmin() && !$this->isGuru())) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $limit = max(10, min($limit, 100));
        return $this->respond(true, 'OK', [
            'skor' => $this->poinModel->getSkor((int) $idSiswa),
            'history' => $this->poinModel->getRiwayat((int) $idSiswa, $limit),
        ]);
    }

    /**
     * Sinkron presensi → poin (terlambat / alpha), idempoten.
     * Menghitung ulang seluruh baris presensi pada periode terpilih supaya
     * keterlambatan yang terlama lalu juga ikut tertangkap, sekaligus
     * mencabut pengurangan yang tak lagi berlaku.
     */
    public function syncPresensi()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $bulan = (int) ($this->request->getPost('bulan') ?? date('n'));
        $tahun = (int) ($this->request->getPost('tahun') ?? date('Y'));
        $idKelas = (int) ($this->request->getPost('id_kelas') ?? 0);
        if ($bulan < 1 || $bulan > 12 || $tahun < 2020 || $tahun > (int) date('Y') + 1) {
            return $this->respond(false, 'Bulan/tahun tidak valid');
        }

        // Job yang sama dengan /cron/poin-siswa: dua recalc paralel pada baris
        // presensi yang sama saling menimpa (hapus-lama lalu tulis-baru).
        if (!$this->cobaKunci('absen-cron-poin-siswa')) {
            return $this->respond(false, 'Sinkron poin lain masih berjalan — coba lagi sebentar.');
        }

        try {
            $b = $this->db->table('presensi_siswa ps')
                ->select('ps.*')
                ->where('MONTH(ps.tgl)', $bulan)->where('YEAR(ps.tgl)', $tahun);
            if ($idKelas) {
                $b->where('ps.id_kelas', $idKelas);
            }
            // Sinkron hanya baris milik siswa AKTIF; baris non-AKTIF
            // dilewati (recalc juga membatalkan poin otomatisnya).
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $b->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e2) {
            }
            $rows = $b->get()->getResult();
        } catch (\Throwable $e) {
            // Pesan exception berisi SQL + kolom — tidak boleh dikirim ke klien.
            log_message('error', '[Admin/PoinSiswa::syncPresensi] baca presensi gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal membaca data presensi — periksa log aplikasi');
        }
        $berubah = 0;
        $lewat = 0;
        $gagal = 0;
        // Satu tombol sinkron memperbaiki dua hal yang diturunkan dari baris
        // presensi yang sama: poin dan denda. Membiarkan salah satunya tertinggal
        // membuat rekap menampilkan poin baru dengan tagihan lama.
        $denda = new \App\Services\SiswaDendaService();
        foreach ($rows as $r) {
            $res = $this->poinModel->syncPresensi($r);
            if (!($res['status'] ?? false)) {
                $gagal++;
            } elseif (!empty($res['skipped'])) {
                $lewat++;
            } else {
                $berubah++;
            }
        }

        // Denda dihitung per bulan, bukan per baris: satu baca dan beberapa
        // tulis pengganti ribuan SELECT + UPDATE. Cakupannya sengaja seluruh
        // sekolah walau tombol ini menekan kelas tertentu — aturannya sama dan
        // perhitungannya idempoten, sementara filter kelas hanya mempersempit
        // baris poin yang disentuh di atas.
        $dendaHasil = $denda->recalcBulanBerjalan($bulan, $tahun);

        return $this->respond(true, "Sinkron selesai: {$berubah} poin berubah, {$lewat} dilewati/tidak berubah"
            . ', ' . $dendaHasil['diubah'] . ' denda disesuaikan'
            . ($dendaHasil['tanpa_baris'] > 0
                ? ' (' . $dendaHasil['tanpa_baris'] . ' hari alpha tanpa baris presensi tetap tertagih di rekap)'
                : '')
            . ($gagal ? ", {$gagal} gagal (lihat log)" : ''));
    }

    /**
     * Anti-tumpang-tindih dengan cron poin (GET_LOCK pada koneksi yang sama
     * dengan query sinkron). Gagal mengambil kunci bukan kesalahan fatal:
     * kalau mekanisme lock-nya sendiri error, jalan terus — sama seperti
     * pola di CronController.
     */
    private function cobaKunci(string $nama): bool
    {
        try {
            $row = $this->db->query('SELECT GET_LOCK(?, 0) AS l', [$nama])->getRow();
        } catch (\Throwable $e) {
            log_message('error', "[Admin/PoinSiswa] GET_LOCK({$nama}) gagal: " . $e->getMessage());
            return true;
        }

        if ((int) ($row->l ?? 0) === 1) {
            return true;
        }

        log_message('warning', "[Admin/PoinSiswa] {$nama} dilewati: proses lain masih memegang lock.");

        return false;
    }
}
