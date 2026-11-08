<?php

namespace App\Controllers;

use App\Models\AuthModel;
use App\Models\DashboardModel;
use App\Models\DropdownModel;
use App\Models\MasterModel;
use App\Services\HariSekolahService;

/**
 * Dashboard Controller - Fully ported from CI3
 * Main dashboard for all user roles with complete AJAX endpoints
 */
class Dashboard extends BaseController
{
    protected AuthModel $authModel;
    protected DashboardModel $dashboardModel;
    protected MasterModel $masterModel;
    protected DropdownModel $dropdownModel;

    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->dashboardModel = new DashboardModel();
        $this->masterModel = new MasterModel();
        $this->dropdownModel = new DropdownModel();
    }

    /**
     * Main dashboard index
     */
    public function index()
    {
        // Check if logged in
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }

        $user = $this->authModel->user($this->getUserId());
        $setting = $this->dashboardModel->getSetting();
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $data = [
            'user' => $user,
            'judul' => 'Beranda',
            'subjudul' => 'Halaman Utama',
            'setting' => $setting,
            'tp' => $this->dashboardModel->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboardModel->getSemester(),
            'smt_active' => $smt,
        ];

        $role = $this->getUserRole();

        // Panitia membantu verifikasi via monitoring (tanpa dashboard khusus)
        if (strtolower($role ?? '') === 'panitia') {
            return redirect()->to('admin/presensi');
        }

        // Siswa memiliki halaman presensi mandiri
        if (strtolower($role ?? '') === 'siswa') {
            $redirect = redirect()->to('siswa/presensi');
            if ($err = session()->getFlashdata('error')) $redirect->with('error', $err);
            if ($succ = session()->getFlashdata('success')) $redirect->with('success', $succ);
            return $redirect;
        }

        // Route based on role
        switch (strtolower($role ?? '')) {
            case 'guru':
                // Guru tidak boleh masuk halaman /dashboard (milik admin);
                // halaman utama guru adalah /presensi (sama seperti redirect setelah login)
                $redirect = redirect()->to('presensi');
                if ($err = session()->getFlashdata('error')) $redirect->with('error', $err);
                if ($warn = session()->getFlashdata('warning')) $redirect->with('warning', $warn);
                if ($succ = session()->getFlashdata('success')) $redirect->with('success', $succ);
                if ($msg = session()->getFlashdata('message')) $redirect->with('message', $msg);
                return $redirect;

            default:
                return $this->adminDashboard($data, $setting, $tp, $smt);
        }
    }

    /**
     * Admin dashboard view
     */
    private function adminDashboard(array $data, ?object $setting, ?object $tp, ?object $smt)
    {
        $data['title'] = 'Dashboard';
        $data['judul'] = 'Dashboard';
        $data['subjudul'] = 'Ringkasan Sistem';

        // absensi by ikasmedia — difokuskan ke Presensi, Jurnal & Piket
        $data['jadwal_kbm'] = [];
        $data['kelases'] = $tp && $smt ? $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt) : [];
        $data['times'] = [];
        $data['mapels'] = [];

        $db = \Config\Database::connect();
        $today = date('Y-m-d');

        // Status hari ini: hari sekolah, akhir pekan, atau libur kalender.
        $hariService = new HariSekolahService();
        $hariSekolah = $hariService->status($today);
        $isHariSekolah = $hariSekolah['status'] === HariSekolahService::AKTIF;
        $namaHariIni = $hariSekolah['nama_hari'];

        $data['hari_sekolah'] = $hariSekolah;
        $data['is_hari_sekolah'] = $isHariSekolah;
        $data['today_label'] = $hariService->tanggalPanjang($today);
        $data['next_school_day'] = $isHariSekolah
            ? null
            : $this->cariHariSekolahBerikutnya($hariService, $today);

        // Metrik 4 Pilar Utama
        $data['presensiHariIni'] = $this->getPresensiHariIni($today);

        // 1. Presensi Siswa Hari Ini — hanya siswa AKTIF.
        $siswaHadirHariIni = 0;
        try {
            if ($db->tableExists('presensi_siswa')) {
                $hq = $db->table('presensi_siswa ps')
                    ->where('ps.tgl', $today)
                    ->whereIn('ps.status', ['H', 'HADIR', 'hadir', 'h']);
                try {
                    if ($db->tableExists('buku_induk')) {
                        $hq->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                            ->groupStart()
                                ->where('bi.status', '1')
                                ->orWhere('bi.id_siswa IS NULL', null, false)
                            ->groupEnd();
                    }
                } catch (\Throwable $e2) {
                }
                $siswaHadirHariIni = (int) $hq->countAllResults();
            }
        } catch (\Throwable $e) {}
        $data['siswaHadirHariIni'] = $siswaHadirHariIni;

        // 2. Jurnal Mengajar Hari Ini
        $jurnalHariIni = 0;
        try {
            if ($db->tableExists('guru_jurnal')) {
                $jurnalHariIni = $db->table('guru_jurnal')
                    ->where('tanggal', $today)
                    ->countAllResults();
            }
        } catch (\Throwable $e) {}
        $data['jurnalHariIni'] = $jurnalHariIni;

        // 3. Piket Guru Hari Ini (saat libur: roster hari sekolah berikutnya)
        $tamuHariIni = 0;
        $kejadianHariIni = 0;
        $petugasPiketHariIni = [];
        $piketHariRoster = $namaHariIni;
        $piketTanggalRoster = $today;
        if (! $isHariSekolah && ! empty($data['next_school_day']['tanggal'])) {
            $piketTanggalRoster = $data['next_school_day']['tanggal'];
            $piketHariRoster = $hariService->namaHari($piketTanggalRoster);
        }
        try {
            if ($db->tableExists('piket_tamu')) {
                $tamuHariIni = $db->table('piket_tamu')->where('tanggal', $today)->countAllResults();
            }
            if ($db->tableExists('piket_kejadian')) {
                $kejadianHariIni = $db->table('piket_kejadian')->where('tanggal', $today)->countAllResults();
            }
            if ($db->tableExists('piket_jadwal') && $tp && $smt) {
                $petugasPiketHariIni = $db->table('piket_jadwal pj')
                    ->select('pj.*, g.nama_guru, g.nip')
                    ->join('master_guru g', 'g.id_guru = pj.id_guru', 'left')
                    ->where('pj.hari', $piketHariRoster)
                    ->where('pj.id_tp', $tp->id_tp)
                    ->where('pj.id_smt', $smt->id_smt)
                    ->orderBy('g.nama_guru', 'ASC')
                    ->get()->getResult();
            }
        } catch (\Throwable $e) {}
        $data['tamuHariIni'] = $tamuHariIni;
        $data['kejadianHariIni'] = $kejadianHariIni;
        $data['petugasPiketHariIni'] = $petugasPiketHariIni;
        $data['petugas_piket_hari'] = $piketHariRoster;
        $data['petugas_piket_tanggal'] = $piketTanggalRoster;

        if ($tp && $smt) {
            $data['info_box'] = $this->getAdminBox($setting, $tp->id_tp, $smt->id_smt, $today);
        } else {
            $data['info_box'] = [];
        }

        // Feed presensi guru. Saat libur, tampilkan data check-in terakhir
        // agar panel tidak kosong sekaligus tidak menyiratkan "belum absen".
        $checkinDate = $today;
        if (! $isHariSekolah) {
            $checkinDate = null;
            try {
                if ($db->tableExists('absensi_harian')) {
                    $latest = $db->table('absensi_harian')
                        ->selectMax('tgl', 'tgl_terakhir')
                        ->where('tgl <', $today)
                        ->get()->getRow();
                    $checkinDate = $latest->tgl_terakhir ?? null;
                }
            } catch (\Throwable $e) {
                $checkinDate = null;
            }
        }
        $data['checkin_date'] = $checkinDate;
        $data['checkin_date_label'] = $checkinDate ? $hariService->tanggalPanjang($checkinDate) : null;

        $data['recentPresensi'] = [];
        try {
            if ($checkinDate !== null && $db->tableExists('absensi_harian')) {
                $data['recentPresensi'] = $db->table('absensi_harian a')
                    ->select('a.*, u.first_name, u.last_name, u.foto, g.nama_guru, g.nip, loc_in.nama_lokasi as nama_lokasi_masuk')
                    ->join('users u', 'u.id = a.user_id', 'left')
                    ->join('master_guru g', 'g.id_guru = a.id_guru', 'left')
                    ->join('absensi_lokasi loc_in', 'loc_in.id = a.lokasi_masuk_id', 'left')
                    ->where('a.tgl', $checkinDate)
                    ->orderBy('a.jam_masuk', 'DESC')
                    ->limit(8)
                    ->get()->getResult();
            }
        } catch (\Throwable $e) {
            $data['recentPresensi'] = [];
        }

        // Ringkasan verifikasi pending
        $data['pendingSummary'] = [
            'dinas' => 0,
            'koreksi' => 0,
            'izin' => 0,
            'wajah' => 0,
            'kehadiran' => 0,
            'total' => 0,
        ];
        try {
            if ($db->tableExists('absensi_harian')) {
                $data['pendingSummary']['dinas'] = $db->table('absensi_harian')->where('status_masuk', 'DINAS_PENDING')->countAllResults();
                // Baris PENDING_VERIFICATION tidak lagi dihitung hadir, jadi
                // ia harus muncul di sini — kalau tidak, guru yang menunggu
                // tindakan hanya hilang dari hitungan admin.
                $data['pendingSummary']['kehadiran'] = $db->table('absensi_harian')->where('status_masuk', 'PENDING_VERIFICATION')->countAllResults();
            }
            if ($db->tableExists('absensi_koreksi')) {
                $data['pendingSummary']['koreksi'] = $db->table('absensi_koreksi')->where('status', 'pending')->countAllResults();
            }
            if ($db->tableExists('absensi_izin')) {
                $data['pendingSummary']['izin'] = $db->table('absensi_izin')
                    ->whereIn('status', \App\Models\LeaveModel::STATUS_PENDING)
                    ->countAllResults();
            }
            if ($db->tableExists('face_registration_requests')) {
                $data['pendingSummary']['wajah'] = $db->table('face_registration_requests')->where('status', 'pending')->countAllResults();
            }
            $data['pendingSummary']['total'] = $data['pendingSummary']['dinas'] + $data['pendingSummary']['koreksi'] + $data['pendingSummary']['izin'] + $data['pendingSummary']['wajah'] + $data['pendingSummary']['kehadiran'];
        } catch (\Throwable $e) {
        }

        // Log aktivitas terbaru (pre-rendered)
        $data['recentLogs'] = [];
        try {
            $logModel = new \App\Models\LogModel();
            $data['recentLogs'] = $logModel->getLog(0, 7);
        } catch (\Throwable $e) {
            $data['recentLogs'] = [];
        }

        return view('dashboard', $data);
    }

    /**
     * Ringkasan kehadiran hari ini (hadir/telat/alpha/pending/total guru).
     */
    private function getPresensiHariIni(?string $tanggal = null): array
    {
        $tanggal = $tanggal ?: date('Y-m-d');
        $out = [
            'hadir' => 0,
            'ontime' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'cuti' => 0,
            'dinas' => 0,
            'wfh' => 0,
            'alpha' => 0,
            'belum_absen' => 0,
            'pending' => 0,
            'total_guru' => 0,
            'persen' => 0,
        ];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('users')) {
                $out['total_guru'] = (int) $db->table('users')
                    ->join('users_groups ug', 'ug.user_id = users.id')
                    ->where('ug.group_id', 2)
                    ->where('users.active', 1)
                    ->countAllResults();
                if ($out['total_guru'] === 0) {
                    $out['total_guru'] = (int) $db->table('users')->where('active', 1)->countAllResults();
                }
            }
            if ($db->tableExists('absensi_harian')) {
                $rows = $db->table('absensi_harian')->select('status_masuk')->where('tgl', $tanggal)->get()->getResult();
                $recordedUsers = count($rows);
                foreach ($rows as $r) {
                    $st = strtoupper($r->status_masuk ?? '');
                    if ($st === 'DINAS_REJECTED') {
                        continue;
                    }
                    // PENDING_VERIFICATION BUKAN hadir: guru sudah tercatat tapi
                    // admin belum menindak. Selama masa tinjau ia dihitung
                    // 'belum tentu' (antrean), setelah lewat batas cron
                    // menuliskannya sebagai ALPHA. Memasukkannya ke 'hadir'
                    // membuat persen kehadiran lebih tinggi dari jumlah yang
                    // dipakai surat teguran.
                    if (in_array($st, ['ONTIME', 'LATE', 'WFH']) || strpos($st, 'DINAS') !== false) {
                        $out['hadir']++;
                    }
                    if ($st === 'PENDING_VERIFICATION') {
                        $out['pending']++;
                    }
                    if ($st === 'ONTIME') {
                        $out['ontime']++;
                    } elseif ($st === 'LATE') {
                        $out['telat']++;
                    } elseif ($st === 'WFH') {
                        $out['wfh']++;
                    } elseif ($st === 'SAKIT') {
                        $out['sakit']++;
                    } elseif ($st === 'CUTI') {
                        $out['cuti']++;
                    } elseif (in_array($st, ['IJIN', 'IZIN'])) {
                        $out['izin']++;
                    } elseif (in_array($st, ['ALPHA', 'ALPA'])) {
                        $out['alpha']++;
                    } elseif (strpos($st, 'DINAS') !== false && $st !== 'DINAS_PENDING') {
                        $out['dinas']++;
                    }
                }
                $out['belum_absen'] = max(0, $out['total_guru'] - $recordedUsers);
                $out['pending'] += (int) $db->table('absensi_harian')->where('status_masuk', 'DINAS_PENDING')->countAllResults();
                if ($db->tableExists('absensi_koreksi')) {
                    $out['pending'] += (int) $db->table('absensi_koreksi')->where('status', 'pending')->countAllResults();
                }
            }
            if ($out['total_guru'] > 0) {
                $out['persen'] = round(($out['hadir'] / $out['total_guru']) * 100, 1);
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /**
     * Ringkasan kehadiran hari ini untuk dashboard admin.
     */
    private function countHadirHariIni(?string $tanggal = null): int
    {
        $tanggal = $tanggal ?: date('Y-m-d');
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('absensi_harian')) {
                return 0;
            }
            $rows = $db->table('absensi_harian')->select('status_masuk')->where('tgl', $tanggal)->get()->getResult();
            $n = 0;
            foreach ($rows as $r) {
                $st = strtoupper($r->status_masuk ?? '');
                if ($st === 'DINAS_REJECTED') {
                    continue;
                }
                // Sama seperti getPresensiHariIni(): yang menunggu verifikasi
                // admin belum boleh disebut hadir.
                if (in_array($st, ['ONTIME', 'LATE', 'WFH']) || strpos($st, 'DINAS') !== false) {
                    $n++;
                }
            }
            return $n;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get admin info boxes
     */
    private function getAdminBox(?object $setting, int $tp, int $smt, ?string $tanggal = null)
    {
        $db = \Config\Database::connect();
        $today = $tanggal ?: date('Y-m-d');
        $hariService = new HariSekolahService();
        $statusHari = $hariService->status($today);
        $isHariSekolah = $statusHari['status'] === HariSekolahService::AKTIF;
        $namaHari = $statusHari['nama_hari'];

        $totalGuru = $this->dashboardModel->total('master_guru');
        $totalSiswa = $this->dashboardModel->getTotalSiswa($tp, $smt);
        $guruHadir = $this->countHadirHariIni($today);

        $siswaHadir = 0;
        try {
            if ($db->tableExists('presensi_siswa')) {
                $shq = $db->table('presensi_siswa ps')
                    ->where('ps.tgl', $today)
                    ->whereIn('ps.status', ['H', 'HADIR', 'hadir', 'h']);
                try {
                    if ($db->tableExists('buku_induk')) {
                        $shq->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                            ->groupStart()
                                ->where('bi.status', '1')
                                ->orWhere('bi.id_siswa IS NULL', null, false)
                            ->groupEnd();
                    }
                } catch (\Throwable $e2) {
                }
                $siswaHadir = (int) $shq->countAllResults();
            }
        } catch (\Throwable $e) {}

        $jurnalHariIni = 0;
        try {
            if ($db->tableExists('guru_jurnal')) {
                $jurnalHariIni = $db->table('guru_jurnal')->where('tanggal', $today)->countAllResults();
            }
        } catch (\Throwable $e) {}

        $piketCount = 0;
        $piketHari = $namaHari;
        $piketHint = $isHariSekolah ? 'Hari ini' : 'Libur hari ini';
        if (! $isHariSekolah) {
            $berikut = $hariService->hariSekolahBerikutnya($today);
            if ($berikut !== null) {
                $piketHari = $hariService->namaHari($berikut);
                $piketHint = 'Jadwal ' . $piketHari . ', ' . (int) date('j', strtotime($berikut)) . ' ' . date('M', strtotime($berikut));
            }
        }
        try {
            if ($db->tableExists('piket_jadwal')) {
                $piketCount = $db->table('piket_jadwal')
                    ->where('hari', $piketHari)
                    ->where('id_tp', $tp)
                    ->where('id_smt', $smt)
                    ->countAllResults();
            }
        } catch (\Throwable $e) {}

        $box = [
            [
                'box' => 'teal',
                'total' => $totalGuru,
                'title' => 'Guru GTK',
                'hint' => 'Terdaftar',
                'url' => 'dataguru',
                'icon' => 'user-circle'
            ],
            [
                'box' => 'blue',
                'total' => $totalSiswa,
                'title' => 'Total Siswa',
                'hint' => 'Tahun aktif',
                'url' => 'datasiswa',
                'icon' => 'users'
            ],
            [
                'box' => 'success',
                'total' => $guruHadir,
                'title' => 'Guru Hadir',
                'hint' => $isHariSekolah ? 'Hari ini' : 'Libur hari ini',
                'url' => 'admin/presensi',
                'icon' => 'user-clock'
            ],
            [
                'box' => 'indigo',
                'total' => $siswaHadir,
                'title' => 'Siswa Hadir',
                'hint' => $isHariSekolah ? 'Hari ini' : 'Libur hari ini',
                'url' => 'admin/presensi/siswa/harian',
                'icon' => 'user-check'
            ],
            [
                'box' => 'fuchsia',
                'total' => $jurnalHariIni,
                'title' => 'Jurnal KBM',
                'hint' => $isHariSekolah ? 'Hari ini' : 'Tidak ada KBM',
                'url' => 'admin/jurnal',
                'icon' => 'book'
            ],
            [
                'box' => 'amber',
                'total' => $piketCount,
                'title' => 'Guru Piket',
                'hint' => $piketHint,
                'url' => 'admin/piket',
                'icon' => 'bell'
            ],
        ];
        return $box;
    }

    /**
     * Cari hari sekolah aktif berikutnya (maks. 14 hari ke depan).
     *
     * @return array{tanggal:string,label:string}|null
     */
    private function cariHariSekolahBerikutnya(HariSekolahService $service, string $dari): ?array
    {
        $tanggal = $service->hariSekolahBerikutnya($dari);
        if ($tanggal === null) {
            return null;
        }

        return [
            'tanggal' => $tanggal,
            'label' => $service->tanggalPanjang($tanggal),
        ];
    }

    /**
     * Get ujian info boxes
     */
    private function getUjianBox(int $tp, int $smt)
    {
        return [];
    }

    /**
     * Get panitia info boxes
     */
    private function getPanitiaBox(int $tp, int $smt)
    {
        $db = \Config\Database::connect();

        // Kehadiran hari ini (presensi; modul ujian sudah dilepas)
        $hadirHariIni = 0;
        $pendingVerifikasi = 0;
        try {
            if ($db->tableExists('absensi_harian')) {
                $today = date('Y-m-d');
                $rows = $db->table('absensi_harian')->select('status_masuk')->where('tgl', $today)->get()->getResult();
                foreach ($rows as $r) {
                    $st = strtoupper($r->status_masuk ?? '');
                    if ($st === 'DINAS_REJECTED') {
                        continue;
                    }
                    if (in_array($st, ['ONTIME', 'LATE', 'WFH']) || strpos($st, 'DINAS') !== false) {
                        $hadirHariIni++;
                    }
                }
                $pendingVerifikasi = (int) $db->table('absensi_harian')->where('status_masuk', 'DINAS_PENDING')->countAllResults();
                $pendingVerifikasi += (int) $db->table('absensi_harian')->where('status_masuk', 'PENDING_VERIFICATION')->countAllResults();
                if ($db->tableExists('absensi_koreksi')) {
                    $pendingVerifikasi += (int) $db->table('absensi_koreksi')->where('status', 'pending')->countAllResults();
                }
            }
        } catch (\Throwable $e) {
        }

        $box = [
            [
                'box' => 'blue',
                'total' => $this->dashboardModel->total('master_guru'),
                'title' => 'Guru',
                'url' => 'dataguru',
                'icon' => 'chalkboard-teacher'
            ],
            [
                'box' => 'cyan',
                'total' => $this->dashboardModel->getTotalSiswa($tp, $smt),
                'title' => 'Siswa',
                'url' => 'datasiswa',
                'icon' => 'users'
            ],
            [
                'box' => 'teal',
                'total' => $hadirHariIni,
                'title' => 'Hadir Hari Ini',
                'url' => 'admin/presensi',
                'icon' => 'user-clock'
            ],
            [
                'box' => 'fuchsia',
                'total' => $pendingVerifikasi,
                'title' => 'Pending Verifikasi',
                'url' => 'admin/presensi/dinas',
                'icon' => 'clock'
            ],
        ];
        return $box;
    }

    /**
     * Get siswa menu items
     */

    // ============================================
    // AJAX ENDPOINTS
    // ============================================

    /**
     * Get student exam status (real-time)
     */
    public function get_status_siswa()
    {
        return $this->response->setJSON([
            'ruangs' => [],
            'jadwals_ujian' => [],
            'sesisiswa' => [],
            'cbtsiswa' => [],
            'gurus' => [],
            'pengawas' => [],
            'kelases' => [],
            'panitias' => [],
            'today' => date('Y-m-d')
        ]);
    }

    /**
     * Get realtime statistics
     */
    public function get_realtime_stats()
    {
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $setting = $this->dashboardModel->getSetting();

        if (!$tp || !$smt || !$setting) {
            return $this->response->setJSON(['info_box' => [], 'ujian_box' => []]);
        }

        $role = $this->getUserRole();

        if (strtolower($role) === 'panitia') {
            $data = [
                'info_box' => $this->getPanitiaBox($tp->id_tp, $smt->id_smt),
                'ujian_box' => []
            ];
        } else {
            $data = [
                'info_box' => $this->getAdminBox($setting, $tp->id_tp, $smt->id_smt),
                'ujian_box' => $this->getUjianBox($tp->id_tp, $smt->id_smt)
            ];
        }

        return $this->response->setJSON($data);
    }

    /**
     * Get activity logs
     */
    public function getlog()
    {
        try {
            $logModel = new \App\Models\LogModel();

            // Query string tidak dipercaya: 'limit=abc' bikin TypeError di
            // aritmetika dan 'page=0' menghasilkan offset negatif yang
            // ditolak MySQL. Dipaksa int lalu dibatasi sebelum dipakai.
            $limit = (int) ($this->request->getGet('limit') ?? 5);
            $page = (int) ($this->request->getGet('page') ?? 1);
            $limit = max(1, min(100, $limit));
            $page = max(1, $page);
            $offset = ($page - 1) * $limit;

            $logs = $logModel->getLog($offset, $limit);
            $total = $logModel->getCountLog();

            // Format for frontend
            $dataLogs = [];
            foreach ($logs as $log) {
                $foto = $log->foto;
                if ($foto) {
                    if (strpos($foto, 'uploads/') === 0) {
                        $fotoUrl = base_url($foto);
                    } else {
                        $fotoUrl = base_url('uploads/profiles/' . $foto);
                    }
                } else {
                    $fotoUrl = base_url('assets/img/user.png');
                }

                $dataLogs[] = [
                    'text' => $log->keterangan, // JS uses 'text' or 'message'
                    'message' => $log->keterangan,
                    'user' => $log->first_name . ' ' . $log->last_name,
                    'time' => $log->waktu,
                    'foto' => $fotoUrl
                ];
            }

            return $this->response->setJSON(['logs' => $dataLogs, 'total' => $total]);
        } catch (\Throwable $th) {
            return $this->response->setJSON(['logs' => [], 'total' => 0]);
        }
    }

    /**
     * Check token and jadwal
     */
    public function checkTokenJadwal()
    {
        return $this->response->setJSON([
            'ada_ujian' => 0,
            'token' => (object) ['token' => '', 'auto' => '0', 'jarak' => '1', 'elapsed' => '00:00:00']
        ]);
    }

    /**
     * Load token
     */
    public function loadtoken()
    {
        return $this->response->setJSON((object) ['token' => '', 'auto' => '0', 'now' => date('H:i:s')]);
    }

    /**
     * Get posts/pengumuman
     */
    public function getpost()
    {
        try {
            $postModel = new \App\Models\PostModel();
            $user = $this->authModel->user();

            if (!$user) {
                // Return empty if no user (session technically expired but handled gracefully for dashboard widgets)
                return $this->response->setJSON(['posts' => [], 'total' => 0]);
            }

            $role = $this->getUserRole();
            $kepada = '0'; // Public
            $kelas = null;

            if (strtolower($role) === 'siswa') {
                $tp = $this->dashboardModel->getTahunActive();
                $smt = $this->dashboardModel->getSemesterActive();

                // Safety check for TP/SMT
                if ($tp && $smt) {
                    $siswa = $this->dashboardModel->getDataSiswa($user->username, $tp->id_tp, $smt->id_smt);
                    $kelas = $siswa ? $siswa->id_kelas : null;
                }
                $kepada = "'siswa'";
            } elseif (strtolower($role) === 'guru') {
                $kepada = "'guru'";
            }

            // Query string tidak dipercaya: 'limit=abc' bikin TypeError di
            // aritmetika dan 'page=0' menghasilkan offset negatif yang
            // ditolak MySQL. Dipaksa int lalu dibatasi sebelum dipakai.
            $limit = (int) ($this->request->getGet('limit') ?? 5);
            $page = (int) ($this->request->getGet('page') ?? 1);
            $limit = max(1, min(100, $limit));
            $page = max(1, $page);
            $offset = ($page - 1) * $limit;

            // Use proper params
            // If class is null for siswa, it might cause issues if model expects a value.
            // Ensure getPostForUser handles null $kelas or we pass a dummy.
            // Passing 0 or safe fallback.
            $kelasParam = $kelas ?? 0;

            $posts = $postModel->getPostForUser($kepada, $kelasParam, $limit, $offset);

            // Map to standard JSON response
            $mappedPosts = [];
            if ($posts) {
                foreach ($posts as $p) {
                    $foto = base_url('assets/img/user.png');
                    if ($p->dari != '0') {
                        $pathInDb = $p->foto;
                        if ($pathInDb) {
                            if (strpos($pathInDb, 'uploads/') === 0) {
                                if (file_exists(FCPATH . $pathInDb)) {
                                    $foto = base_url($pathInDb);
                                } else {
                                    $foto = base_url('assets/img/guru.png');
                                }
                            } else {
                                if (file_exists(FCPATH . 'uploads/profiles/' . $pathInDb)) {
                                    $foto = base_url('uploads/profiles/' . $pathInDb);
                                } else {
                                    $foto = base_url('assets/img/guru.png');
                                }
                            }
                        } else {
                            $foto = base_url('assets/img/guru.png');
                        }
                    }

                    $mappedPosts[] = [
                        'id_post' => $p->id_post,
                        'title' => 'Pengumuman',
                        'content' => $p->text ?? ($p->isi ?? ''),
                        'text' => $p->text ?? ($p->isi ?? ''),
                        'created_at' => $p->updated ?? $p->tanggal ?? date('Y-m-d H:i:s'),
                        'author' => $p->dari == '0' ? 'Administrator' : ($p->nama_guru ?? 'Guru'),
                        'author_photo' => $foto,
                        'comments_count' => $p->jml ?? 0
                    ];
                }
            }

            return $this->response->setJSON(['posts' => $mappedPosts, 'total' => count($mappedPosts)]);

        } catch (\Throwable $th) {
            // Log error and return empty to prevent dashboard breakage
            log_message('error', '[Dashboard::getpost] ' . $th->getMessage());
            return $this->response->setJSON(['posts' => [], 'total' => 0, 'error' => 'Gagal memuat postingan']);
        }
    }

    /**
     * Ganti tahun ajaran
     */
    public function gantiTahun()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        return $this->gantiPeriodeAktif('id_tp', 'tahun', 'master_tp', 'Tahun Pelajaran', 20);
    }

    /**
     * Ganti semester
     */
    public function gantiSemester()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        return $this->gantiPeriodeAktif('id_smt', 'smt', 'master_smt', 'Semester', 10);
    }

    /**
     * Salinan ringan dari pemindah periode (lihat Datatahun::gantiPeriode).
     * Invarian yang sama dijaga: harus ada TEPAT SATU baris active=1, karena
     * kalau id 'active' tidak cocok dengan baris mana pun semua baris jadi 0
     * dan seluruh halaman yang membaca getTahunActive()/getSemesterActive()
     * ikut kosong.
     */
    private function gantiPeriodeAktif(string $pk, string $kolomLabel, string $tabel, string $nama, int $maksLabel)
    {
        $aktif = (int) ($this->request->getPost('active') ?? 0);
        $ids   = $this->request->getPost($pk) ?? [];
        $label = $this->request->getPost($kolomLabel) ?? [];
        if (!is_array($ids) || !is_array($label)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Data ' . strtolower($nama) . ' tidak terbaca — tidak ada yang diubah.']);
        }

        $update = [];
        foreach ($ids as $i => $idRaw) {
            $id = (int) $idRaw;
            if ($id <= 0) {
                continue;
            }
            $nilaiLabel = trim((string) ($label[$i] ?? ''));
            if ($nilaiLabel === '' || mb_strlen($nilaiLabel) > $maksLabel) {
                return $this->response->setJSON([
                    'status' => false,
                    'msg' => 'Label ' . strtolower($nama) . ' kosong atau lebih dari ' . $maksLabel . ' karakter — tidak ada yang diubah.',
                ]);
            }
            $update[$id] = [$pk => $id, $kolomLabel => $nilaiLabel, 'active' => $id === $aktif ? 1 : 0];
        }

        if ($update === []) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Tidak ada ' . strtolower($nama) . ' yang valid — tidak ada yang diubah.']);
        }
        if (!isset($update[$aktif])) {
            return $this->response->setJSON(['status' => false, 'msg' => $nama . ' yang ingin diaktifkan tidak ada di daftar — tidak ada yang diubah.']);
        }

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            if ($db->table($tabel)->updateBatch(array_values($update), $pk) === false || !$db->transStatus()) {
                $db->transRollback();
                return $this->response->setJSON(['status' => false, 'msg' => $nama . ' gagal disimpan. Periode berjalan belum berubah.']);
            }
            $db->table($tabel)->where($pk . ' <>', $aktif)->where('active', 1)->update(['active' => 0]);
            $db->table($tabel)->where($pk, $aktif)->update(['active' => 1]);

            if ($db->table($tabel)->where('active', 1)->countAllResults() !== 1) {
                $db->transRollback();
                return $this->response->setJSON(['status' => false, 'msg' => $nama . ' gagal disimpan: jumlah periode aktif tidak tepat satu.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            if ($db->transStatus() === false) {
                $db->transRollback();
            }
            log_message('error', '[Dashboard::gantiPeriodeAktif] ' . $tabel . ' aktif=' . $aktif . ': ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'msg' => $nama . ' gagal disimpan. Data belum berubah.']);
        }

        return $this->response->setJSON(['status' => true, 'msg' => $nama . ' berhasil diganti', 'update' => array_values($update)]);
    }

    /**
     * Fitur komentar pengumuman belum ada tabelnya (komentar/balasan tidak
     * pernah dimigrasi), tapi endpoint-nya ter-route dan duluh melaporkan
     * status:true "berhasil" tanpa menulis apa pun. Sekarang jawab jujur.
     */
    private function fiturBelumTersedia(string $apa)
    {
        return $this->response->setJSON([
            'status'  => false,
            'message' => $apa . ' belum tersedia di versi ini — tidak ada data yang diubah.',
            'msg'     => $apa . ' belum tersedia di versi ini — tidak ada data yang diubah.',
        ]);
    }

    /**
     * Save balasan (reply to comment)
     */
    public function savebalasan()
    {
        if ($deny = $this->requireLogin()) {
            return $deny;
        }
        return $this->fiturBelumTersedia('Menyimpan balasan komentar');
    }

    /**
     * Save komentar (comment)
     */
    public function savekomentar()
    {
        if ($deny = $this->requireLogin()) {
            return $deny;
        }
        return $this->fiturBelumTersedia('Menyimpan komentar');
    }

    /**
     * Hapus post
     */
    public function hapuspost()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        return $this->fiturBelumTersedia('Menghapus pengumuman');
    }

    /**
     * Hapus komentar
     */
    public function hapuskomentar()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        return $this->fiturBelumTersedia('Menghapus komentar');
    }

    /**
     * Hapus balasan
     */
    public function hapusbalasan()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        return $this->fiturBelumTersedia('Menghapus balasan');
    }

    /**
     * Nama tabel log yang dipakai (kompatibel instalasi lama: logs / log)
     */
    private function logTableName(): string
    {
        $db = \Config\Database::connect();
        $tableName = 'logs';
        if (!$db->tableExists('logs') && $db->tableExists('log')) {
            $tableName = 'log';
        }
        return $tableName;
    }

    /**
     * Hapus satu baris log aktivitas (dipakai kartu Aktivitas Sistem di dashboard)
     */
    public function deletelog($id)
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID log tidak valid');
        }

        try {
            $db = \Config\Database::connect();
            $db->table($this->logTableName())->where('id_log', $id)->delete();

            if ($db->affectedRows() < 1) {
                return $this->respond(false, 'Data log tidak ditemukan');
            }

            return $this->respond(true, 'Log berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', '[Dashboard::deletelog] ' . $e->getMessage());
            return $this->respond(false, 'Gagal menghapus log', null, null, 500);
        }
    }

    /**
     * Hapus seluruh log aktivitas
     */
    public function hapuslog()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        try {
            $db = \Config\Database::connect();
            $db->table($this->logTableName())->emptyTable();
            return $this->respond(true, 'Seluruh log aktivitas berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', '[Dashboard::hapuslog] ' . $e->getMessage());
            return $this->respond(false, 'Gagal menghapus log', null, null, 500);
        }
    }
}
