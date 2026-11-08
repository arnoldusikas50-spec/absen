<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

use App\Models\AttendanceModel;
use App\Models\DashboardModel;
use App\Models\AuditLogModel;
use App\Models\LeaveModel;
use App\Models\FaceRegistrationRequestModel;
use Dompdf\Dompdf;

/**
 * Admin Controller for Attendance Management
 * Handles Dinas verification and attendance reports
 */
class PresensiAdmin extends BaseController
{
    protected $attendanceModel;
    protected $dashboardModel;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Dashboard / Daily Monitoring
     */
    /**
     * Dashboard / Daily Monitoring
     */
    public function index()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiSettingTable();
        $this->patchAbsensiHarianTable();

        $date = $this->request->getGet('date') ?? date('Y-m-d');
        // Validasi format tanggal (dulu: string sembarang → strtotime gagal → 1970)
        $dtCheck = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dtCheck || $dtCheck->format('Y-m-d') !== $date) {
            $date = date('Y-m-d');
        }
        $isFuture = $date > date('Y-m-d');
        $isToday  = $date === date('Y-m-d');
        $tahun = date('Y'); // For dashboard analytics

        // --- 1. DAILY MONITORING DATA ---

        // Semua guru aktif = user role guru (group 2). master_guru di-LEFT JOIN
        // agar guru yang belum punya baris master tetap muncul di monitoring
        // (sebelumnya inner join menyembunyikan mereka dari tabel & KPI).
        $users = $db->table('users u')
            ->select("u.id, u.first_name, u.last_name, u.username, u.foto, g.nip,
                      COALESCE(NULLIF(g.nama_guru, ''), TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) AS nama_guru")
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->orderBy('nama_guru', 'ASC')
            ->get()
            ->getResult();

        // Get attendance for the date
        $attendance = $db->table('absensi_harian a')
            ->select('a.*, loc_in.nama_lokasi as nama_lokasi_masuk, loc_out.nama_lokasi as nama_lokasi_pulang')
            ->join('absensi_lokasi loc_in', 'loc_in.id = a.lokasi_masuk_id', 'left')
            ->join('absensi_lokasi loc_out', 'loc_out.id = a.lokasi_pulang_id', 'left')
            ->where('a.tgl', $date)
            ->get()
            ->getResult();

        // Map attendance by user_id
        $map = [];
        foreach ($attendance as $att) {
            $map[$att->user_id] = $att;
        }

        // --- NEW: Monthly Recap for Table Columns ---
        $bulanIni   = date('Y-m', strtotime($date));
        $bulanAwal  = $bulanIni . '-01';
        $bulanAkhir = date('Y-m-t', strtotime($bulanAwal));
        $rekapRaw = $db->table('absensi_harian')
            ->select('user_id, status_masuk, COUNT(*) as jumlah')
            ->where('tgl >=', $bulanAwal)
            ->where('tgl <=', $bulanAkhir)
            ->groupBy('user_id, status_masuk')
            ->get()->getResult();

        $rekapBulanan = [];
        foreach ($rekapRaw as $r) {
            if (!isset($rekapBulanan[$r->user_id])) {
                $rekapBulanan[$r->user_id] = [
                    'hadir' => 0,
                    'telat' => 0,
                    'ijin' => 0,
                    'sakit' => 0,
                    'cuti' => 0,
                    'dinas' => 0,
                    'wfh' => 0,
                    'alpha' => 0,
                    'pending' => 0
                ];
            }
            // Map status to category (semua enum; dulu IZIN/CUTI/ALPHA/PENDING hilang)
            $st = strtoupper((string) $r->status_masuk);
            if ($st == 'ONTIME') {
                $rekapBulanan[$r->user_id]['hadir'] += $r->jumlah;
            } elseif ($st == 'LATE') {
                $rekapBulanan[$r->user_id]['telat'] += $r->jumlah;
            } elseif (in_array($st, ['IJIN', 'IZIN'])) {
                $rekapBulanan[$r->user_id]['ijin'] += $r->jumlah;
            } elseif ($st == 'SAKIT') {
                $rekapBulanan[$r->user_id]['sakit'] += $r->jumlah;
            } elseif ($st == 'CUTI') {
                $rekapBulanan[$r->user_id]['cuti'] += $r->jumlah;
            } elseif ($st == 'WFH') {
                $rekapBulanan[$r->user_id]['wfh'] += $r->jumlah;
            } elseif (in_array($st, ['ALPHA', 'ALPA'])) {
                $rekapBulanan[$r->user_id]['alpha'] += $r->jumlah;
            } elseif ($st == 'PENDING_VERIFICATION') {
                $rekapBulanan[$r->user_id]['pending'] += $r->jumlah;
            } elseif (strpos($st, 'DINAS') !== false) {
                // DINAS_PENDING, DINAS_APPROVED, DINAS_OUT, etc.
                if ($st != 'DINAS_REJECTED') {
                    $rekapBulanan[$r->user_id]['dinas'] += $r->jumlah;
                }
            }
        }


        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        // Status hari memakai service yang sama dengan dashboard & piket:
        // libur mingguan → jadwal kerja per hari → kalender libur sekolah.
        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();
        if (!$absenSetting) {
            $absenSetting = $setting;
        }

        $hariSekolah    = new \App\Services\HariSekolahService($absenSetting);
        $statusHari     = $hariSekolah->status($date);
        $isWorkingDay   = $statusHari['status'] === \App\Services\HariSekolahService::AKTIF;
        $isHoliday      = !$isWorkingDay;
        $hariStatus     = $statusHari['status'];
        $hariKeterangan = $statusHari['keterangan'];
        $peringatanKalender = $hariSekolah->peringatanKalender($date);

        // Rekap harian per kategori untuk KPI. Guru tanpa baris presensi hanya
        // dihitung "belum absen" pada hari kerja yang sudah berjalan — bukan
        // pada hari libur atau tanggal yang belum tiba.
        $rekapHari = [
            'tepat' => 0, 'telat' => 0, 'wfh' => 0, 'dinas' => 0,
            'izin' => 0, 'sakit' => 0, 'cuti' => 0,
            'pending_verifikasi' => 0, 'pending_dinas' => 0,
            'alpha' => 0, 'ditolak' => 0, 'lainnya' => 0, 'belum_absen' => 0,
        ];

        foreach ($users as $u) {
            $att = $map[$u->id] ?? null;
            if (!$att) {
                if ($isWorkingDay && !$isFuture) {
                    $rekapHari['belum_absen']++;
                }
                continue;
            }

            $st = strtoupper(trim((string) ($att->status_masuk ?? '')));
            if ($st === 'ONTIME') {
                $rekapHari['tepat']++;
            } elseif ($st === 'LATE') {
                $rekapHari['telat']++;
            } elseif ($st === 'WFH') {
                $rekapHari['wfh']++;
            } elseif (in_array($st, ['DINAS_APPROVED', 'DINAS', 'DINAS_OUT'], true)) {
                $rekapHari['dinas']++;
            } elseif ($st === 'DINAS_PENDING') {
                $rekapHari['pending_dinas']++;
            } elseif ($st === 'DINAS_REJECTED') {
                $rekapHari['ditolak']++;
            } elseif ($st === 'PENDING_VERIFICATION') {
                $rekapHari['pending_verifikasi']++;
            } elseif (in_array($st, ['IJIN', 'IZIN'], true)) {
                $rekapHari['izin']++;
            } elseif ($st === 'SAKIT') {
                $rekapHari['sakit']++;
            } elseif ($st === 'CUTI') {
                $rekapHari['cuti']++;
            } elseif (in_array($st, ['ALPHA', 'ALPA'], true)) {
                $rekapHari['alpha']++;
            } else {
                $rekapHari['lainnya']++;
            }
        }

        $totalGuru          = count($users);
        $hadirTerverifikasi = $rekapHari['tepat'] + $rekapHari['telat'] + $rekapHari['wfh'] + $rekapHari['dinas'];
        $menungguVerifikasi = $rekapHari['pending_verifikasi'] + $rekapHari['pending_dinas'];
        $sudahAbsen         = $hadirTerverifikasi + $menungguVerifikasi;
        $izinSah            = $rekapHari['izin'] + $rekapHari['sakit'] + $rekapHari['cuti'];
        $tidakHadir         = $rekapHari['belum_absen'] + $rekapHari['alpha'] + $rekapHari['ditolak'] + $rekapHari['lainnya'];
        $persenHadir        = $totalGuru > 0 ? round(($sudahAbsen / $totalGuru) * 100, 1) : 0.0;

        // --- 2. ANTREAN PERSETUJUAN (global, lintas tanggal) ---

        // Telat pada bulan yang sedang dilihat, bukan bulan berjalan.
        $telatBulanIni = 0;
        try {
            $telatBulanIni = $db->table('absensi_harian')
                ->where('status_masuk', 'LATE')
                ->where('tgl >=', $bulanAwal)
                ->where('tgl <=', $bulanAkhir)
                ->countAllResults();
        } catch (\Throwable $e) {}

        $pendingDinas = 0;
        $pendingVerifikasi = 0;
        try {
            $pendingDinas = $db->table('absensi_harian')
                ->where('status_masuk', 'DINAS_PENDING')->countAllResults();
            $pendingVerifikasi = $db->table('absensi_harian')
                ->where('status_masuk', 'PENDING_VERIFICATION')->countAllResults();
        } catch (\Throwable $e) {}

        $pendingKoreksi = 0;
        if ($db->tableExists('absensi_koreksi')) {
            try {
                $pendingKoreksi = $db->table('absensi_koreksi')
                    ->where('status', 'pending')->countAllResults();
            } catch (\Throwable $e) {}
        }

        $pendingIzin = 0;
        if ($db->tableExists('absensi_izin')) {
            try {
                $pendingIzin = $db->table('absensi_izin')
                    ->where('status', 'pending')->countAllResults();
            } catch (\Throwable $e) {}
        }

        $pendingWajah = 0;
        if ($db->tableExists('face_registration_requests')) {
            try {
                $pendingWajah = $db->table('face_registration_requests')
                    ->where('status', 'pending')->countAllResults();
            } catch (\Throwable $e) {}
        }

        $pendingReview = [
            'dinas'      => $pendingDinas,
            'koreksi'    => $pendingKoreksi,
            'izin'       => $pendingIzin,
            'verifikasi' => $pendingVerifikasi,
            'wajah'      => $pendingWajah,
        ];
        $pendingReview['total'] = array_sum($pendingReview);

        // Estimasi alpha missing-only bulan yang dilihat (recorded-aware, tanpa today):
        // denda tercatat sudah masuk $dendaMap, jadi di sini hanya missing agar
        // tidak double-count. Kolom A memakai count recorded+missing.
        $monthStart = $bulanAwal;
        $monthEnd   = $bulanAkhir;
        $alphaMap = [];
        $alphaMissingMap = [];
        $alphaDendaMap = [];
        try {
            $alphaFilteredMap = $this->filteredAlphaMonthMap($monthStart, $monthEnd, $absenSetting);
            foreach ($alphaFilteredMap as $uId => $aInfo) {
                $alphaMap[$uId]        = (int) ($aInfo['count'] ?? 0);
                $alphaMissingMap[$uId] = (int) ($aInfo['missing_count'] ?? 0);
                $alphaDendaMap[$uId]   = (float) ($aInfo['total_denda'] ?? 0);
            }
        } catch (\Throwable $e) {}

        // Total denda per user bulan ini (untuk kolom Denda; ranking sudah dihapus)
        $dendaMap = [];
        try {
            $dendaRaw = $db->table('absensi_harian')
                ->select('user_id, SUM(denda) as total_denda')
                ->where('tgl >=', $bulanAwal)
                ->where('tgl <=', $bulanAkhir)
                ->groupBy('user_id')
                ->get()->getResult();
            foreach ($dendaRaw as $p) {
                $dendaMap[$p->user_id] = (float) $p->total_denda;
            }
        } catch (\Throwable $e) {
            $dendaMap = [];
        }

        // Denda tampil = denda tercatat + estimasi alpha yang belum punya baris.
        $dendaTotalMap = $dendaMap;
        foreach ($alphaDendaMap as $uId => $est) {
            $dendaTotalMap[$uId] = ($dendaTotalMap[$uId] ?? 0) + (float) $est;
        }

        // 3. Alert Persentase Kehadiran (standar SMA: min 90%), mengikuti bulan yang dilihat
        $alertKehadiran = [];
        $minPersen = (float) ($absenSetting->min_kehadiran_persen ?? 90.0);
        $bulanAngka = (int) date('n', strtotime($bulanAwal));
        $tahunAngka = (int) date('Y', strtotime($bulanAwal));
        $bulanSudahMulai = $bulanAwal <= date('Y-m-d');
        if ((int) ($absenSetting->alert_kehadiran_enabled ?? 1) === 1 && $bulanSudahMulai) {
            $penaltyService = new \App\Services\AttendancePenaltyService();

            foreach ($users as $u) {
                $result = $penaltyService->hitungPersentaseKehadiran(
                    (int) $u->id, $bulanAngka, $tahunAngka, $minPersen
                );
                if ($result['status'] !== 'aman') {
                    $alertKehadiran[] = [
                        'user_id'    => $u->id,
                        'nama_guru'  => $u->nama_guru,
                        'persen'     => $result['persen'],
                        'hadir'      => $result['total_hadir'],
                        'efektif'    => $result['total_hari_efektif'],
                        'alpha'      => $result['total_alpha'],
                        'status'     => $result['status'],
                    ];
                }
            }
            // Urutkan dari paling rendah
            usort($alertKehadiran, fn($a, $b) => $a['persen'] <=> $b['persen']);
        }

        return view('admin/presensi/index', [
            'title' => 'Monitoring Harian',
            'judul' => 'Monitoring Presensi',
            'setting' => $setting,
            'absenSetting' => $absenSetting,
            'users' => $users,
            'map' => $map,
            'date' => $date,
            'isToday' => $isToday,
            'isFuture' => $isFuture,
            'isWorkingDay' => $isWorkingDay,
            'isHoliday' => $isHoliday,
            'hariStatus' => $hariStatus,
            'hariKeterangan' => $hariKeterangan,
            'peringatanKalender' => $peringatanKalender,
            'bulanIni' => $bulanIni,
            'bulanAwal' => $bulanAwal,
            'bulanAkhir' => $bulanAkhir,
            'rekapHari' => $rekapHari,
            'rekapBulanan' => $rekapBulanan,
            'totalGuru' => $totalGuru,
            'hadirTerverifikasi' => $hadirTerverifikasi,
            'menungguVerifikasi' => $menungguVerifikasi,
            'sudahAbsen' => $sudahAbsen,
            'izinSah' => $izinSah,
            'tidakHadir' => $tidakHadir,
            'persenHadir' => $persenHadir,
            'telatBulanIni' => $telatBulanIni,
            'pendingReview' => $pendingReview,
            'alphaMap' => $alphaMap,
            'alphaMissingMap' => $alphaMissingMap,
            'alphaDendaMap' => $alphaDendaMap,
            'dendaMap' => $dendaMap,
            'dendaTotalMap' => $dendaTotalMap,
            'tahun' => $tahun,
            // Layout Header Data
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            // Alert kehadiran < min threshold
            'alertKehadiran' => $alertKehadiran,
            'minKehadiranPersen' => $minPersen,
        ]);
    }

    /**
     * List pending Dinas requests.
     * LEFT JOIN + fallback nama agar baris tetap tampil walau data guru
     * terhapus sebagian (kasus tsb tetap bisa diproses/ditolak).
     */
    public function pendingDinas()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $bulanIni = date('m');
        $tahunIni = date('Y');

        $pending = $db->table('absensi_harian a')
            ->select('a.*, COALESCE(mg.nama_guru, TRIM(CONCAT_WS(\' \', u.first_name, u.last_name)), u.username) as nama_guru, u.username, u.foto, mg.nip')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->join('master_guru mg', 'mg.id_user = u.id', 'left')
            ->where('a.status_masuk', 'DINAS_PENDING')
            ->orderBy('a.created_at', 'DESC')
            ->limit(200)
            ->get()
            ->getResult();

        // Riwayat dinas yang sudah diverifikasi pada bulan berjalan
        $history = $db->table('absensi_harian a')
            ->select('a.*, COALESCE(mg.nama_guru, TRIM(CONCAT_WS(\' \', u.first_name, u.last_name)), u.username) as nama_guru, u.username, u.foto, mg.nip')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->join('master_guru mg', 'mg.id_user = u.id', 'left')
            ->whereIn('a.status_masuk', ['DINAS_APPROVED', 'DINAS_REJECTED', 'DINAS'])
            ->where('MONTH(a.tgl)', $bulanIni)
            ->where('YEAR(a.tgl)', $tahunIni)
            ->orderBy('a.updated_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResult();

        // Hitung statistik dinas
        $countPending = count($pending);
        $countApproved = $db->table('absensi_harian')
            ->whereIn('status_masuk', ['DINAS_APPROVED', 'DINAS'])
            ->where('MONTH(tgl)', $bulanIni)
            ->where('YEAR(tgl)', $tahunIni)
            ->countAllResults();
        $countRejected = $db->table('absensi_harian')
            ->where('status_masuk', 'DINAS_REJECTED')
            ->where('MONTH(tgl)', $bulanIni)
            ->where('YEAR(tgl)', $tahunIni)
            ->countAllResults();

        $stats = [
            'pending'   => $countPending,
            'approved'  => $countApproved,
            'rejected'  => $countRejected,
            // Total = seluruh antrean pending + yang sudah diproses bulan ini
            'total'     => $countPending + $countApproved + $countRejected,
        ];

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/pending_dinas', [
            'title'     => 'Verifikasi Dinas Luar',
            'judul'     => 'Verifikasi Dinas',
            'setting'   => $setting,
            'tp_active' => $tp_active,
            'smt_active'=> $smt_active,
            'user'      => $this->getUser(),
            'pending'   => $pending,
            'history'   => $history,
            'stats'     => $stats
        ]);
    }

    /**
     * List pending Permit (Izin/Sakit) requests
     */
    public function pendingPermits()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        
        // Use LeaveModel logic but with join
        $pending = $db->table('absensi_izin a')
            ->select('a.*, mg.nama_guru, u.username, u.foto, mg.nip')
            ->join('users u', 'u.id = a.user_id')
            ->join('master_guru mg', 'mg.id_user = u.id')
            ->where('a.status', 'pending') // fixed: was `0` (integer), now proper VARCHAR
            ->orderBy('a.created_at', 'DESC')
            ->get()
            ->getResult();

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/pending_permits', [
            'title' => 'Verifikasi Izin/Sakit',
            'judul' => 'Verifikasi Izin/Sakit',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'pending' => $pending
        ]);
    }

    /**
     * Approve Permit (Izin/Sakit/Cuti/Dinas)
     */
    public function approvePermit($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiIzinTable();

        $db = \Config\Database::connect();
        $builder = $db->table('absensi_izin');
        $permit = $builder->where('id', $id)->get()->getRow();

        if (!$permit) {
            return $this->respond(false, 'Data pengajuan tidak ditemukan');
        }

        $statusStr = strtolower(trim((string)$permit->status));
        if ($statusStr === 'approved' || $statusStr === '1') {
            return $this->respond(false, 'Pengajuan ini sudah disetujui sebelumnya');
        }

        // Approver tidak boleh menyetujui pengajuannya sendiri. Guru yang
        // rangkap jabatan (panitia/kepsek) sebelumnya bisa menyetujui izin
        // miliknya sendiri, dan approval itu menulis baris absensi atas nama
        // mereka — tanpa tinjauan siapa pun.
        if ($this->adalahPemohon($permit)) {
            return $this->respond(false, 'Anda tidak dapat menyetujui pengajuan Anda sendiri', null, null, 403);
        }

        // Transaksi: gagal di tengah jalan tidak menyisakan setengah hari terproses.
        $db->transBegin();
        try {
        // 1. Update Permit Status to 'approved' — atomik: hanya baris pending
        // yang bisa berpindah, cegah double-approve dua admin paralel.
        $updateData = [
            'status' => 'approved',
            'verified_by' => session()->get('user_id'),
            'verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $db->table('absensi_izin')
            ->where('id', $id)->where('status', 'pending')
            ->update($updateData);

        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return $this->respond(false, 'Status sudah diproses admin lain. Muat ulang halaman.');
        }

        // 2. Get Settings for Points
        $settingModel = new \App\Models\AttendanceSettingModel();
        $setting = $settingModel->first();
        $pointIzin = $setting->point_izin ?? 0;
        $pointSakit = $setting->point_sakit ?? 0;
        
        $jenisUpper = strtoupper($permit->jenis);
        if ($jenisUpper === 'SAKIT') {
            $point = $pointSakit;
            $statusMasuk = 'SAKIT';
        } elseif ($jenisUpper === 'CUTI') {
            $point = 0;
            $statusMasuk = 'CUTI';
        } elseif ($jenisUpper === 'DINAS' || strpos($jenisUpper, 'DINAS') !== false) {
            $point = (int)($setting->point_hadir_ontime ?? $setting->point_hadir ?? 10);
            $statusMasuk = 'DINAS_APPROVED';
        } else {
            $point = $pointIzin;
            $statusMasuk = 'IJIN';
        }

        // 3. Update Attendance for Date Range
        $start = strtotime($permit->tgl_mulai);
        $end   = strtotime($permit->tgl_selesai);
        $attendanceModel = new AttendanceModel();

        // Konteks libur dari IzinKuotaService — definisi "libur" yang sama
        // dengan yang dipakai saat menghitung kuota guru. Sebelumnya approve
        // punya salinan sendiri (mingguan + jadwal kerja + nasional) yang
        // bisa berbeda dari IzinKuotaService, sehingga kuota yang dibaca
        // guru dan baris absensi yang ditulis approve bisa menghitung
        // jumlah hari yang tidak sama.
        $svcLibur = new \App\Services\IzinKuotaService($db);

        // Dipakai sync surat teguran setelah commit.
        $globalSetting = $this->dashboardModel->getSetting();

        $punyaIzinId = $db->fieldExists('izin_id', 'absensi_harian');

        // IJIN/SAKIT/CUTI adalah status absen netral: tidak punya jam
        // kehadiran. DINAS_APPROVED dihitung sebagai HADIR.
        $isAbsenNetral = in_array($statusMasuk, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true);

        // Bulan-bulan yang benar-benar ditulis (untuk sync surat teguran).
        $bulanTersentuh = [];

        // Tanggal yang dilewati karena sudah ada scan asli guru.
        $dilewatiScanAsli = [];

        for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
            $date = date('Y-m-d', $i);

            if ($svcLibur->isLibur($date)) {
                continue;
            }

            $existing = $attendanceModel->where('user_id', $permit->user_id)->where('tgl', $date)->first();

            // Menimpanya ke baris yang SUDAH berisi scan asli menghapus
            // bukti kehadiran guru. Setelah itu deletePermit() melihat
            // "jam kosong + status izin" dan menganggap baris itu otomatis,
            // lalu MENGHAPUSNYA — scan asli lenyap tanpa jejak.
            //
            // Aturan: hari yang sudah ada scan asli dibiarkan utuh dan
            // dilaporkan balik ke admin, untuk SEMUA jenis pengajuan.
            // Termasuk dinas: status DINAS_APPROVED yang menimpa scan
            // ONTIME/LATE ikut menol-kan denda (kredit hantu bila bulan itu
            // sudah dibayar) dan menghapus telat_menit, lalu penolakan
            // pengajuan mengubah hari yang sebenarnya hadir itu menjadi ALPHA.
            if ($existing && ! empty($existing->jam_masuk)) {
                $dilewatiScanAsli[] = $date;
                continue;
            }

            $bulanTersentuh[date('Y-m', $i)] = true;

            $data = [
                'user_id' => $permit->user_id,
                'tgl' => $date,
                'status_masuk' => $statusMasuk,
                'status_pulang' => $statusMasuk,
                'points' => $point,
                'denda' => 0,
                'keterangan_denda' => null,
                'telat_menit' => 0,
                'pulang_cepat_menit' => 0,
                'keterangan' => $permit->keterangan ?? "Pengajuan {$permit->jenis}"
            ];

            // Penanda asal: tanpa ini revert tidak bisa membedakan baris
            // yang dibuat pengajuan ini dari baris milik guru/admin.
            if ($punyaIzinId) {
                $data['izin_id'] = (int) $permit->id;
            }

            // Dinas (DINAS_APPROVED) mempertahankan jam_masuk/jam_pulang
            // karena dihitung sebagai HADIR (guru bertugas, bukan bolos).
            // IJIN/SAKIT/CUTI tetap mengosongkan jam — tapi baris seperti
            // itu hanya mungkin kosong di titik ini, karena hari yang punya
            // scan asli sudah dilewati di atas.
            if ($existing) {
                if ($isAbsenNetral) {
                    $data['jam_masuk']  = null;
                    $data['jam_pulang'] = null;
                }
                $attendanceModel->update($existing->id, $data);
                $logAction = 'UPDATE';
                $logId = $existing->id;
            } else {
                $data['jam_masuk']  = null;
                $data['jam_pulang'] = null;
                $logId = $attendanceModel->insert($data);
                $logAction = 'INSERT';
            }

            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($logId);
        }

        $db->transCommit();

        // Jejak keputusan penyetuju. Sebelum ini hanya absensi_izin.updated_at
        // yang tersisa — tidak ada yang mencatat siapa menyetujui, kapan, dan
        // hari mana yang sengaja tidak diubah.
        try {
            (new AuditLogModel())->log('absensi_izin', (int) $id, 'APPROVE', (int) $permit->user_id, $this->getUserId(),
                ['status' => (string) ($permit->status ?? '')],
                [
                    'status'            => 'approved',
                    'hari_dilewati'     => $dilewatiScanAsli,
                    'bulan_tersentuh'   => array_keys($bulanTersentuh),
                ],
                'Persetujuan ' . $permit->jenis . ' ' . date('d/m/Y', $start) . '–' . date('d/m/Y', $end));
        } catch (\Throwable $ex) {
            log_message('error', 'Audit approvePermit gagal: ' . $ex->getMessage());
        }

        // Evaluasi dan sinkronisasi pembatalan surat teguran untuk SEMUA bulan
        // yang tersentuh (izin lintas bulan sebelumnya hanya sync bulan awal).
        try {
            $penaltySvc = new \App\Services\AttendancePenaltyService();
            $periode = array_keys($bulanTersentuh);
            if (empty($periode)) {
                $periode = [date('Y-m', $start)];
            }
            foreach ($periode as $ym) {
                [$thnSync, $blnSync] = array_map('intval', explode('-', $ym));
                $penaltySvc->syncSuratTeguranOnAttendanceChange((int) $permit->user_id, $blnSync, $thnSync, $globalSetting);
            }
        } catch (\Throwable $ex) {}

        // Notifikasi izin disetujui (best-effort)
        try {
            $catatan = (string) ($this->request->getPost('catatan') ?? $this->request->getPost('note') ?? '');

            // Admin wajib tahu hari mana yang TIDAK berubah dan kenapa —
            // tanpa iniapprove terlihat berhasil padahal absensi tidak
            // sama sekali bergerak untuk tanggal-tanggal itu.
            if (! empty($dilewatiScanAsli)) {
                $catatan = trim(
                    ($catatan !== '' ? $catatan . ' | ' : '')
                    . count($dilewatiScanAsli)
                    . ' hari dilewati karena sudah ada scan kehadiran: '
                    . implode(', ', $dilewatiScanAsli)
                );
            }

            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $permit->user_id)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            (new \App\Services\NotificationService())->notifyPermitApproved(
                $namaGuru,
                (string) ($permit->jenis ?? ''),
                date('d/m/Y', $start),
                date('d/m/Y', $end),
                (int) ($permit->user_id ?? 0),
                $catatan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi izin disetujui gagal: ' . $ex->getMessage());
        }
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Approve permit gagal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            return $this->respond(false, 'Gagal menyetujui pengajuan — periksa log aplikasi.', null, null, 500);
        }

        $pesan = 'Pengajuan ' . $permit->jenis . ' berhasil disetujui dan absensi diperbarui';
        if (empty($bulanTersentuh)) {
            $pesan .= ' (catatan: tidak ada hari kerja yang perlu ditulis pada rentang ini)';
        }
        if (! empty($dilewatiScanAsli)) {
            $pesan .= ' (catatan: ' . count($dilewatiScanAsli)
                . ' hari tidak diubah karena sudah ada scan kehadiran: '
                . implode(', ', $dilewatiScanAsli) . ')';
        }

        return $this->respond(true, $pesan);
    }

    /**
     * Reject Permit (Izin/Sakit/Cuti)
     */
    public function rejectPermit($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiIzinTable();

        $db = \Config\Database::connect();
        $builder = $db->table('absensi_izin');
        $permit = $builder->where('id', $id)->get()->getRow();

        if (!$permit) {
            return $this->respond(false, 'Data pengajuan tidak ditemukan');
        }

        $statusStr = strtolower(trim((string)$permit->status));
        if ($statusStr === 'rejected' || $statusStr === '2') {
            return $this->respond(false, 'Pengajuan ini sudah ditolak sebelumnya');
        }

        // Sama seperti approve: penolakan oleh pemohon sendiri tetap menulis
        // ALPHA + denda atas namanya tanpa tinjauan orang lain.
        if ($this->adalahPemohon($permit)) {
            return $this->respond(false, 'Anda tidak dapat memproses pengajuan Anda sendiri', null, null, 403);
        }

        $alasan = $this->request->getPost('alasan') ?: 'Ditolak oleh admin/panitia';

        // Update Permit Status to 'rejected'
        $updateData = [
            'status' => 'rejected',
            'verifikasi_catatan' => $alasan,
            'verified_by' => session()->get('user_id'),
            'verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

            $db->transBegin();
        try {
            // Atomik: hanya baris pending/approved yang bisa ditolak.
            // Cegah double-reject dua admin paralel (keduanya menulis ALPHA).
            $db->table('absensi_izin')
                ->where('id', $id)->whereIn('status', ['pending', 'approved'])
                ->update($updateData);
            if ($db->affectedRows() !== 1) {
                $db->transRollback();
                return $this->respond(false, 'Status sudah diproses admin lain. Muat ulang halaman.');
            }

            // Kembalikan absensi_harian pada periode izin yang ditolak:
            // catatan izin (IJIN/SAKIT/CUTI/DINAS_APPROVED) dibatalkan → baris
            // dikembalikan ke keadaan sebelumnya (tanpa status izin) agar guru
            // dihitung sebagai ALPHA/ALPA jika memang tidak hadir.
            // Nominal mengikuti cron: flat + poin alpa (dengan cap bulanan),
            // bukan 0 — agar tidak ada alpha gratis sebelum recalc berjalan.
            $absenSetReject = (new \App\Models\AttendanceSettingModel())->first() ?? (object) [];
            $baseAlphaReject = (float) ($absenSetReject->denda_alpha_per_hari ?? 5000);
            $pointAlphaReject = abs((int) ($absenSetReject->point_alpa ?? 5));
            $penaltyReject = new \App\Services\AttendancePenaltyService();
            $start = strtotime($permit->tgl_mulai);
            $end   = strtotime($permit->tgl_selesai);
            $globalSetting  = $this->dashboardModel->getSetting();
            $bulanTersentuhReject = [];
            $jadiAlphaReject = 0;

            // Baris absensi hanya ditulis saat APPROVE. Menolak pengajuan
            // yang masih PENDING tidak menulis apa pun, jadi tidak ada yang
            // perlu dikembalikan.
            //
            // Tanpa gerbang ini, blok di bawah akan membaca baris IJIN/SAKIT/
            // CUTI yang berizin_id NULL — misalnya hasil input absen manual
            // admin pada tanggal yang sama — lalu mengubahnya jadi ALPHA dan
            // menagih denda ke guru yang justru sedang menyetujui izin.
            $pernahDisetujui = strtolower(trim((string) ($permit->status ?? ''))) === 'approved';
            $permitStatusReject = $penaltyReject->normalizeIzinStatus((string) ($permit->jenis ?? ''));
            $svcLiburReject = $pernahDisetujui ? new \App\Services\IzinKuotaService($db) : null;

            for ($i = $start; $pernahDisetujui && $i <= $end; $i = strtotime('+1 day', $i)) {
                $date = date('Y-m-d', $i);

                if ($svcLiburReject->isLibur($date)) continue;

                $existing = $db->table('absensi_harian')
                    ->where('user_id', $permit->user_id)
                    ->where('tgl', $date)
                    ->get()->getRow();

                if (!$existing) continue;

                $currentStatus = strtoupper((string) ($existing->status_masuk ?? ''));
                $isIzinStatus  = in_array($currentStatus, ['IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS_APPROVED', 'DINAS'], true)
                                  || strpos($currentStatus, 'DINAS') === 0;

                if (!$isIzinStatus) continue;

                // Hanya baris yang benar-benar ditulis pengajuan ini yang
                // boleh diubah jadi ALPHA. Tanpa penyaringan ini, hari yang
                // statusnya kebetulan IJIN/SAKIT/CUTI karena alasan lain
                // bisa ikut diganti ALPHA lalu didenda ke guru yang
                // sebenarnya hadir.
                if (! AttendanceModel::apakahMilikIzin($existing, (int) $id, $permitStatusReject)) {
                    continue;
                }

                // Hapus jejak izin: kembalikan ke ALPHA (tanpa catatan izin)
                // Cap dengan lock (dalam transaksi) seperti check-in/cron agar
                // dua writer paralel tidak sama-sama lolos batas bulanan.
                $ketAlphaReject = 'Alpa (Rp ' . number_format($baseAlphaReject, 0, ',', '.') . ')';
                $cappedReject = $penaltyReject->applyMonthlyCap((int) $permit->user_id, $date, $baseAlphaReject, $absenSetReject, (int) $existing->id, true);
                $dendaReject = (float) $cappedReject['total'];
                if ($cappedReject['capped']) {
                    $ketAlphaReject .= ' (dibatasi cap bulanan Rp ' . number_format($dendaReject, 0, ',', '.') . ')';
                }
                $bulanTersentuhReject[substr($date, 0, 7)] = true;
                $db->table('absensi_harian')->where('id', $existing->id)->update([
                    'status_masuk'     => 'ALPHA',
                    'status_pulang'    => 'ALPHA',
                    'points'           => -$pointAlphaReject,
                    'denda'            => $dendaReject,
                    'keterangan_denda' => $ketAlphaReject,
                    'keterangan'       => 'Ditolak: ' . $alasan,
                    'catatan'          => $alasan,
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
                $jadiAlphaReject++;
            }

            $db->transCommit();

            // Jejak keputusan penolakan: penolakan mengubah hari izin menjadi
            // ALPHA + denda, jadi "siapa yang menolak dan berapa hari yang
            // ikut jadi alpa" harus tercatat, bukan cuma verifikasi_catatan.
            try {
                (new AuditLogModel())->log('absensi_izin', (int) $id, 'REJECT', (int) $permit->user_id, $this->getUserId(),
                    ['status' => (string) ($permit->status ?? '')],
                    [
                        'status'         => 'rejected',
                        'alasan'         => $alasan,
                        'jadi_alpha'     => $jadiAlphaReject,
                        'bulan_terpengaruh' => array_keys($bulanTersentuhReject),
                    ],
                    'Penolakan ' . $permit->jenis . ' ' . date('d/m/Y', $start) . '–' . date('d/m/Y', $end));
            } catch (\Throwable $ex) {
                log_message('error', 'Audit rejectPermit gagal: ' . $ex->getMessage());
            }

            // Surat yang seharusnya hidup lagi (revert ke ALPHA) disinkronkan
            // segera per bulan tersentuh — sebelumnya menunggu cron evaluasi
            // 7/14/21 sehingga surat tertunda berhari-hari.
            try {
                $periodeReject = array_keys($bulanTersentuhReject);
                if (empty($periodeReject)) {
                    $periodeReject = [date('Y-m', $start)];
                }
                foreach ($periodeReject as $ym) {
                    [$thnR, $blnR] = array_map('intval', explode('-', $ym));
                    $penaltyReject->syncSuratTeguranOnAttendanceChange((int) $permit->user_id, $blnR, $thnR, $globalSetting);
                }
            } catch (\Throwable $ex2) {}
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Reject permit gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menolak pengajuan — periksa log aplikasi.', null, null, 500);
        }

        // Send Notification
        try {
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $permit->user_id)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            $notifService = new \App\Services\NotificationService();
            // Pakai $alasan (sudah di-fallback 'Ditolak oleh admin/panitia') —
            // bukan getPost('catatan'/'note') yang tidak pernah dikirim form
            // ini (field-nya bernama 'alasan'), sehingga guru tidak tahu alasan.
            $notifService->notifyPermitRejected(
                $namaGuru,
                (string) ($permit->jenis ?? ''),
                (int) ($permit->user_id ?? 0),
                (string) $alasan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi izin ditolak gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Pengajuan ditolak');
    }

    /**
     * Approve Dinas Request.
     * Transisi atomik dalam transaksi: gagal di tengah tak menyisakan
     * absensi terupdate tanpa status, atau status tanpa ledger poin.
     */
    public function approveDinas($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID dinas tidak valid');
        }

        $db = \Config\Database::connect();
        $attendance = $this->attendanceModel->find($id);
        if (!$attendance) {
            return $this->respond(false, 'Data tidak ditemukan');
        }

        if ($attendance->status_masuk !== 'DINAS_PENDING') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        if ($this->adalahPemohon($attendance)) {
            return $this->respond(false, 'Anda tidak dapat menyetujui pengajuan Anda sendiri', null, null, 403);
        }

        // Poin dinas konsisten dengan modul lain via service terpusat
        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $setting = $absenSettingModel->first();
        $pointHadir = $setting
            ? (new \App\Services\AttendancePenaltyService())->getStatusPoints('DINAS', $setting)
            : 10;
        if ($pointHadir <= 0) $pointHadir = 10;

        $updateData = [
            'status_masuk' => 'DINAS_APPROVED',
            'status_pulang' => 'DINAS_APPROVED',
            'points' => (int) $pointHadir,
            'denda' => 0,
            'keterangan_denda' => null,
            'telat_menit' => 0,
            'pulang_cepat_menit' => 0
        ];

        $db->transBegin();
        try {
            $db->table('absensi_harian')
                ->where('id', $id)->where('status_masuk', 'DINAS_PENDING')
                ->update($updateData);

            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Status sudah diproses admin lain. Muat ulang halaman.');
            }

            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);

            // Dinas retroaktif bisa menggugurkan alpha: sinkronkan surat teguran
            // seperti approve izin/koreksi agar surat usang otomatis dibatalkan.
            try {
                $tglDinas = substr((string) ($attendance->tgl ?? date('Y-m-d')), 0, 10);
                (new \App\Services\AttendancePenaltyService())->syncSuratTeguranOnAttendanceChange(
                    (int) $attendance->user_id,
                    (int) date('m', strtotime($tglDinas)),
                    (int) date('Y', strtotime($tglDinas)),
                    $setting
                );
            } catch (\Throwable $e) {}

            // AUDIT LOG
            try {
                $auditModel = new AuditLogModel();
                $auditModel->log(
                    'absensi_harian',
                    $id,
                    'UPDATE',
                    $attendance->user_id,
                    $this->getUserId(),
                    (array) $attendance,
                    $updateData,
                    'Dinas Disetujui Admin'
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Approve dinas gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyetujui dinas — periksa log aplikasi.', null, null, 500);
        }

        // Send Notification (grup + guru). Best-effort, setelah transaksi.
        try {
            $dinasUserId = (int) ($attendance->user_id ?? 0);
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $dinasUserId)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            $notifService = new \App\Services\NotificationService();
            $notifService->notifyDinasApproved(
                $namaGuru,
                (string) ($attendance->tgl ?? date('Y-m-d')),
                $dinasUserId,
                (string) ($this->request->getPost('catatan') ?? $this->request->getPost('note') ?? '')
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi dinas gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Dinas disetujui. Poin +' . (int) $pointHadir . ' diberikan.');
    }

    /**
     * Reject Dinas Request.
     * Klaim palsu tidak boleh berbuah poin: ledger direset ke 0.
     * Alasan wajib agar guru tahu dasar penolakan.
     */
    public function rejectDinas($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID dinas tidak valid');
        }

        $db = \Config\Database::connect();
        $attendance = $this->attendanceModel->find($id);
        if (!$attendance) {
            return $this->respond(false, 'Data tidak ditemukan');
        }

        if ($attendance->status_masuk !== 'DINAS_PENDING') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        if ($this->adalahPemohon($attendance)) {
            return $this->respond(false, 'Anda tidak dapat menolak pengajuan Anda sendiri', null, null, 403);
        }

        $alasan = trim((string) ($this->request->getPost('alasan') ?? ''));
        if (mb_strlen($alasan) < 5 || mb_strlen($alasan) > 500) {
            return $this->respond(false, 'Alasan penolakan wajib diisi (5–500 karakter)');
        }

        // Preservasi keterangan awal guru agar tidak hilang tertimpa
        $ketAwal = trim((string) ($attendance->keterangan ?? ''));
        $ketTolak = 'Dinas ditolak: ' . $alasan . ($ketAwal !== '' ? ' | Ket awal: ' . mb_substr($ketAwal, 0, 200) : '');
        $updateData = [
            'status_masuk' => 'DINAS_REJECTED',
            'status_pulang' => 'DINAS_REJECTED',
            'points' => 0,
            'denda' => 0,
            'keterangan_denda' => null,
            'keterangan' => mb_substr($ketTolak, 0, 500),
        ];

        $db->transBegin();
        try {
            $db->table('absensi_harian')
                ->where('id', $id)->where('status_masuk', 'DINAS_PENDING')
                ->update($updateData);

            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Status sudah diproses admin lain. Muat ulang halaman.');
            }

            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);

            // AUDIT LOG
            try {
                $auditModel = new AuditLogModel();
                $auditModel->log(
                    'absensi_harian',
                    $id,
                    'UPDATE',
                    $attendance->user_id,
                    $this->getUserId(),
                    (array) $attendance,
                    $updateData,
                    'Dinas Ditolak Admin: ' . $alasan
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Reject dinas gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menolak dinas — periksa log aplikasi.', null, null, 500);
        }

        // Send Notification (grup + guru). Best-effort, setelah transaksi.
        try {
            $dinasUserId = (int) ($attendance->user_id ?? 0);
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $dinasUserId)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            $notifService = new \App\Services\NotificationService();
            // Alasan wajib diisi admin (min 5 karakter) — kirim ke guru agar
            // pengajuan yang ditolak tidak menggantung tanpa penjelasan.
            $notifService->notifyDinasRejected(
                $namaGuru,
                (string) ($attendance->tgl ?? date('Y-m-d')),
                $dinasUserId,
                $alasan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi dinas ditolak gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Dinas ditolak. Poin dikembalikan ke 0.');
    }





    /**
     * Periode (bulan, tahun) untuk halaman rekap.
     *
     * Tanpa validasi, `?bulan=13` lolos ke MONTH(tgl) sebagai angka liar dan
     * `sprintf('%04d-%02d-01')` melepas '2026-13-01' yang tidak dapat dibaca
     * strtotime — rentang tanggal rekap lalu jatuh ke 1970/Januari tahun
     * berikutnya dan kolom bulanan bergeser. Nilai tidak valid dikembalikan ke
     * periode berjalan (sama seperti default halaman), bukan ditolak, supaya
     * tautan lama tetap terbuka.
     *
     * @return array{int,int}
     */
    private function periodeRekap($bulan, $tahun): array
    {
        $b = (int) $bulan;
        $y = (int) $tahun;

        if ($b < 1 || $b > 12) {
            $b = (int) date('n');
        }
        if ($y < 2000 || $y > (int) date('Y') + 1) {
            $y = (int) date('Y');
        }

        return [$b, $y];
    }

    /**
     * Monthly Attendance Report
     */
    public function rekap()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        [$bulan, $tahun] = $this->periodeRekap(
            $this->request->getGet('bulan') ?? date('m'),
            $this->request->getGet('tahun') ?? date('Y')
        );

        $db = \Config\Database::connect();

        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->orderBy('g.nama_guru', 'ASC')
            ->get()
            ->getResult();

        // Get attendance data for the month
        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->get()
            ->getResult();

        // Map attendance by user_id
        $mapByUser = [];
        foreach ($attendances as $att) {
            if (!isset($mapByUser[$att->user_id])) {
                $mapByUser[$att->user_id] = [];
            }
            $mapByUser[$att->user_id][$att->tgl] = $att;
        }

        // Get holidays for the month (peta libur AttendancePolicy)
        $holidays = \App\Services\AttendancePolicy::liburNasional();

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        // Jadwal kerja dari AttendancePolicy (peta hari ISO => baris jadwal).
        $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

        // Get lunas status
        $lunasMap = [];
        if ($db->tableExists('absensi_pembayaran_denda')) {
            $lunasRows = $db->table('absensi_pembayaran_denda')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('status', 'lunas')
                ->get()->getResult();
            foreach ($lunasRows as $lr)
                $lunasMap[$lr->user_id] = $lr;
        }

        // Peta izin approved bulan ini agar rekap tidak menghitung alpha pada
        // tanggal dispensasi (konsisten dengan halaman denda via service).
        $leaveMap = [];
        if ($db->tableExists('absensi_izin')) {
            try {
                $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
                $endDate = date('Y-m-t', strtotime($startDate));
                $leaveRows = $db->table('absensi_izin')
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $endDate)
                    ->where('tgl_selesai >=', $startDate)
                    ->get()->getResult();
                foreach ($leaveRows as $l) {
                    $cur = strtotime(max($startDate, (string) $l->tgl_mulai));
                    $end = strtotime(min($endDate, (string) $l->tgl_selesai));
                    while ($cur <= $end) {
                        $leaveMap[(int) $l->user_id][date('Y-m-d', $cur)] = true;
                        $cur = strtotime('+1 day', $cur);
                    }
                }
            } catch (\Throwable $e) {}
        }

        return view('admin/presensi/rekap', [
            'title' => 'Rekap Presensi Bulanan',
            'judul' => 'Rekap Presensi',
            'setting' => $setting,
            'absenSetting' => $absenSetting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'users' => $users,
            'mapByUser' => $mapByUser,
            'holidays' => $holidays,
            'jadwalKerja' => $jadwalKerja,
            'hariLiburMingguan' => \App\Services\AttendancePolicy::hariLiburMingguan(),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'lunasMap' => $lunasMap,
            'leaveMap' => $leaveMap
        ]);
    }

    /**
     * Export Rekap to Excel (XLS)
     */
    public function exportRekap()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        [$bulan, $tahun] = $this->periodeRekap(
            $this->request->getGet('bulan') ?? date('m'),
            $this->request->getGet('tahun') ?? date('Y')
        );

        $db = \Config\Database::connect();

        // Get all users
        // Get Setting for Sorting Kepsek
        $sData = $this->dashboardModel->getSetting();
        $kepsekNip = trim($sData->nip ?? '');

        // Get all users
        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->get()->getResult();

        usort($users, function ($a, $b) use ($kepsekNip) {
            $isA = !empty($kepsekNip) && trim($a->nip) == $kepsekNip;
            $isB = !empty($kepsekNip) && trim($b->nip) == $kepsekNip;

            if ($isA && !$isB)
                return -1;
            if (!$isA && $isB)
                return 1;
            return strcasecmp($a->nama_guru, $b->nama_guru);
        });

        // Get attendance
        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->get()->getResult();

        $mapByUser = [];
        foreach ($attendances as $att) {
            if (!isset($mapByUser[$att->user_id]))
                $mapByUser[$att->user_id] = [];
            $mapByUser[$att->user_id][$att->tgl] = $att;
        }

        // Get Holidays (peta libur AttendancePolicy)
        $holidays = \App\Services\AttendancePolicy::liburNasional();

        $filename = 'Rekap_Presensi_' . sprintf('%02d-%04d', $bulan, $tahun) . '.xls';

        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();

        $setting = $this->dashboardModel->getSetting();
    if (!$setting)
        $setting = (object) ['nama_sekolah' => 'absensi by ikasmedia'];

    // Jadwal kerja dari AttendancePolicy (peta hari ISO => baris jadwal).
    $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

    // Peta izin approved bulan ini. Tanpa ini view Excel menandai guru yang
    // izinnya sudah disetujui sebagai ALPHA, padahal halaman Rekap dan PDF
    // sudah menampilkannya sebagai kolom kosong.
    $leaveMap = [];
    if ($db->tableExists('absensi_izin')) {
        try {
            $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
            $endDate = date('Y-m-t', strtotime($startDate));
            $leaveRows = $db->table('absensi_izin')
                ->where('status', 'approved')
                ->where('tgl_mulai <=', $endDate)
                ->where('tgl_selesai >=', $startDate)
                ->get()->getResult();
            foreach ($leaveRows as $l) {
                $cur = strtotime(max($startDate, (string) $l->tgl_mulai));
                $end = strtotime(min($endDate, (string) $l->tgl_selesai));
                while ($cur <= $end) {
                    $leaveMap[(int) $l->user_id][date('Y-m-d', $cur)] = true;
                    $cur = strtotime('+1 day', $cur);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[exportRekap] leaveMap: ' . $e->getMessage());
        }
    }

    $data = [
        'users' => $users,
        'mapByUser' => $mapByUser,
        'holidays' => $holidays,
        'bulan' => $bulan,
        'tahun' => $tahun,
        'setting' => $setting,
        'absenSetting' => $absenSetting,
        'jadwalKerja' => $jadwalKerja,
        'leaveMap' => $leaveMap
    ];

        // Render view first
        $body = view('admin/presensi/rekap_excel', $data);
        $db->close(); // Explicitly close DB connection

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($body);
    }

    /**
     * Export Rekap to PDF
     */
    public function exportPdf()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        [$bulan, $tahun] = $this->periodeRekap(
            $this->request->getGet('bulan') ?? date('m'),
            $this->request->getGet('tahun') ?? date('Y')
        );

        $db = \Config\Database::connect();

        // Get all users
        // Get Setting for Sorting Kepsek
        $sData = $this->dashboardModel->getSetting();
        $kepsekNip = trim($sData->nip ?? '');

        // Get all users
        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->get()->getResult();

        usort($users, function ($a, $b) use ($kepsekNip) {
            $isA = !empty($kepsekNip) && trim($a->nip) == $kepsekNip;
            $isB = !empty($kepsekNip) && trim($b->nip) == $kepsekNip;

            if ($isA && !$isB)
                return -1;
            if (!$isA && $isB)
                return 1;
            return strcasecmp($a->nama_guru, $b->nama_guru);
        });

        // Get attendance
        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->get()->getResult();

        $mapByUser = [];
        foreach ($attendances as $att) {
            if (!isset($mapByUser[$att->user_id]))
                $mapByUser[$att->user_id] = [];
            $mapByUser[$att->user_id][$att->tgl] = $att;
        }

        // Get Holidays (peta libur AttendancePolicy)
        $holidays = \App\Services\AttendancePolicy::liburNasional();

        $setting = $this->dashboardModel->getSetting();
        if (!$setting)
            $setting = (object) ['nama_sekolah' => 'absensi by ikasmedia'];

        $filename = 'Rekap_Presensi_' . sprintf('%02d-%04d', $bulan, $tahun);

        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();

    // Jadwal kerja dari AttendancePolicy (peta hari ISO => baris jadwal).
    $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

    // Peta izin approved bulan ini (konsisten dengan halaman rekap & denda)
    $leaveMap = [];
    if ($db->tableExists('absensi_izin')) {
        try {
            $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
            $endDate = date('Y-m-t', strtotime($startDate));
            $leaveRows = $db->table('absensi_izin')
                ->where('status', 'approved')
                ->where('tgl_mulai <=', $endDate)
                ->where('tgl_selesai >=', $startDate)
                ->get()->getResult();
            foreach ($leaveRows as $l) {
                $cur = strtotime(max($startDate, (string) $l->tgl_mulai));
                $end = strtotime(min($endDate, (string) $l->tgl_selesai));
                while ($cur <= $end) {
                    $leaveMap[(int) $l->user_id][date('Y-m-d', $cur)] = true;
                    $cur = strtotime('+1 day', $cur);
                }
            }
        } catch (\Throwable $e) {}
    }

    $data = [
        'users' => $users,
        'mapByUser' => $mapByUser,
        'holidays' => $holidays,
        'bulan' => $bulan,
        'tahun' => $tahun,
        'setting' => $setting,
        'absenSetting' => $absenSetting,
        'jadwalKerja' => $jadwalKerja,
        'leaveMap' => $leaveMap
    ];

        $html = view('admin/presensi/rekap_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // $dompdf->stream($filename, ["Attachment" => false]);

        // Fix for hosting: Use CI4 Response to ensure correct headers
        $output = $dompdf->output();
        $db->close(); // Explicitly close DB connection

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '.pdf"')
            ->setBody($output)
            ->setStatusCode(200);
    }

    /**
     * List Surat Teguran
     */
    public function suratTeguran()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $bulan = (int) ($this->request->getGet('bulan') ?: date('n'));
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));

        // Normalisasi supaya parameter ngawur tidak sampai ke query —
        // ?bulan=abc akan membuat halaman kosong tanpa penjelasan.
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = ($tahun >= 2000 && $tahun <= 2100) ? $tahun : (int) date('Y');

        // Fetch Settings & User Data FIRST (Required for Layout)
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        $user = $this->getUser();

        $db = \Config\Database::connect();

        // Lazy check table
        $adaTabel = $db->tableExists('absensi_surat_teguran');

        $letters  = [];
        $kandidat = [];

        if ($adaTabel) {
            $letters = $db->table('absensi_surat_teguran a')
                ->select('a.*, g.nama_guru, g.nip, g.id_user') // Join to get guru info
                ->join('master_guru g', 'g.id_guru = a.id_guru')
                ->where('a.bulan', $bulan)
                ->where('a.tahun', $tahun)
                ->orderBy('a.nomor_urut', 'DESC')
                ->get()->getResult();

            $kandidat = $this->kandidatSuratTeguran($db, $letters, $bulan, $tahun);
        }

        $data = [
            'title' => 'Surat Teguran',
            'judul' => 'Surat Teguran Guru',
            'letters' => $letters,
            'kandidat' => $kandidat,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'adaTabel' => $adaTabel,
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $user
        ];

        return view('admin/presensi/surat_teguran', $data);
    }

    /**
     * Guru yang alpha bulan ini sudah melewati ambang tapi belum punya
     * surat di periode itu.
     *
     * Daftar ini memanggil apa pun yang bisa mengubah data —
     * alpha dihitung lewat calculateTeacherMonthlyAlpha() yang hanya membaca.
     * Section ini murni untuk memutuskan apakah operator ingin menerbitkan
     * manual lewat tombol di halaman.
     *
     * @return list<array{id_guru:int,nama_guru:string,nip:string,alpha:int,level:int}>
     */
    private function kandidatSuratTeguran($db, array $letters, int $bulan, int $tahun): array
    {
        if (! $db->tableExists('master_guru')) {
            return [];
        }

        try {
            $guruList = $db->table('users u')
                ->select('u.id, g.id_guru, g.nama_guru, g.nip')
                ->join('master_guru g', 'g.id_user = u.id', 'inner')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where('u.active', 1)
                ->groupBy('u.id')
                ->get()
                ->getResult();

            // Guru yang sudah punya surat di periode ini tidak perlu
            // diusulkan lagi — termasuk yang suratnya dibatalkan, karena
            // slot-nya masih terisi dan surat baru tidak akan terbit.
            $sudahPunya = [];
            foreach ($letters as $l) {
                $sudahPunya[(int) $l->id_guru] = true;
            }

            $absenSetting = (new \App\Models\AttendanceSettingModel())->first();
            $penaltySvc   = new \App\Services\AttendancePenaltyService();
            $ambangAwal   = \App\Libraries\SuratTeguranLevel::ambang()[0];
            $svcTeguran   = new \App\Services\SuratTeguranService($db);

            $kandidat = [];
            foreach ($guruList as $g) {
                $idGuru = (int) ($g->id_guru ?? 0);
                if ($idGuru <= 0 || isset($sudahPunya[$idGuru])) {
                    continue;
                }

                // Kepala sekolah tidak pernah jadi kandidat — beliau yang
                // menandatangani surat teguran guru, bukan yang ditegur.
                if ($svcTeguran->adalahKepalaSekolah($idGuru)) {
                    continue;
                }

                $alpha = $penaltySvc->calculateTeacherMonthlyAlpha(
                    (int) $g->id,
                    $bulan,
                    $tahun,
                    $absenSetting
                );

                $level = \App\Libraries\SuratTeguranLevel::levelUntukAlpha($alpha);
                if ($level === null || $alpha < $ambangAwal) {
                    continue;
                }

                $kandidat[] = [
                    'id_guru'  => $idGuru,
                    'nama_guru' => (string) ($g->nama_guru ?? '-'),
                    'nip'      => (string) ($g->nip ?? ''),
                    'alpha'    => $alpha,
                    'level'    => $level,
                ];
            }

            // Yang paling parah di atas (urutan alpha menurun).
            usort($kandidat, static fn($a, $b) => $b['alpha'] <=> $a['alpha']);

            return $kandidat;
        } catch (\Throwable $e) {
            log_message('error', '[suratTeguran] gagal hitung kandidat: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Hitung alpha untuk periode (bulan/tahun) sebuah surat — metode kanonik
     * yang sama dengan saat penerbitan (CronController::generateWarningLetters).
     */
    private function alphaForLetterPeriod(int $userId, int $bulan, int $tahun, $setting, $db): int
    {
        try {
            if ($userId <= 0)
                return 0;
            $penaltyService = new \App\Services\AttendancePenaltyService();
            return $penaltyService->calculateTeacherMonthlyAlpha($userId, $bulan, $tahun, $setting);
        } catch (\Throwable $e) {
            log_message('error', 'alphaForLetterPeriod gagal: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Peta alpha bulanan yang sudah disaring untuk tampilan:
     * item + total_denda hanya untuk tanggal BOLONG (tanpa baris berdenda),
     * tanpa hari ini, streak kontinu (pindai mundur 62 hari).
     * count = semua hari alpha pada bulan itu (tercatat + bolong, tanpa hari ini).
     *
     * @return array<int, array{count:int, missing_count:int, total_denda:float, items:array}>
     */
    private function filteredAlphaMonthMap(string $startDate, string $endDate, $setting): array
    {
        return (new \App\Services\RekapPdfService())->filteredAlphaMonthMap($startDate, $endDate, $setting);
    }

    /**
     * Rakit data view PDF surat teguran dari satu baris arsip.
     *
     * Satu sumber untuk unduh admin dan kirim PDF personal: snapshot
     * alpha, nomor tersimpan, tanggal terbit, dan QR TTE semuanya dari
     * arsip — isi dokumen identik di semua jalur.
     */
    private function dataPdfSuratTeguran(object $letter): array
    {
        $db = \Config\Database::connect();

        // 1. Settings
        $attSetting = new \App\Models\AttendanceSettingModel();
        $baseSetting = $attSetting->first();
        if (!$baseSetting)
            $baseSetting = (object) []; // Prevent crash if empty

        $globalSetting = $this->dashboardModel->getSetting();
        // Merge
        $setting = clone $baseSetting;
        if ($globalSetting) {
            foreach (get_object_vars($globalSetting) as $key => $val) {
                if (!isset($setting->$key))
                    $setting->$key = $val;
            }
        }
        // Defaults
        if (!isset($setting->logo_kiri))
            $setting->logo_kiri = null;
        if (!isset($setting->nama_sekolah))
            $setting->nama_sekolah = 'Sekolah';
        if (!isset($setting->kota))
            $setting->kota = 'Kota';

        // 2. Gunakan snapshot jumlah_alpha dari database agar isi arsip tetap (immutable)
        $jumlahAlpha = (int) ($letter->jumlah_alpha ?? 0);
        if ($jumlahAlpha <= 0) {
            $calcAlpha = $this->alphaForLetterPeriod(
                (int) ($letter->id_user ?? 0),
                (int) ($letter->bulan ?? date('n')),
                (int) ($letter->tahun ?? date('Y')),
                $setting,
                $db
            );
            $jumlahAlpha = max((int) ($letter->level ?? 0), $calcAlpha);
        }

        // Guru Data
        $guru = $letter; // Already joined

        // Text
        $teguranText = \App\Libraries\SuratTeguranLevel::label((int) ($letter->level ?? 0));

        // Rincian tanggal alpha (sumber tunggal yang sama dengan angka
        // snapshot di atas) untuk kalimat "tanggal 1, 3 dan 4 ..." di PDF.
        $alphaDates = [];
        $userIdSurat = (int) ($letter->id_user ?? 0);
        if ($userIdSurat > 0) {
            try {
                $alphaDates = (new \App\Services\AttendancePenaltyService())->teacherMonthlyAlphaDates(
                    $userIdSurat,
                    (int) ($letter->bulan ?? date('n')),
                    (int) ($letter->tahun ?? date('Y')),
                    $setting
                );
            } catch (\Throwable $e) {
                log_message('error', '[SuratTeguran] rincian tanggal alpha gagal: ' . $e->getMessage());
            }
        }

        // Images
        $logoBase64 = '';
        if (isset($setting->logo_kiri) && $setting->logo_kiri && file_exists(FCPATH . $setting->logo_kiri)) {
            $type = pathinfo(FCPATH . $setting->logo_kiri, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->logo_kiri);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        $kopBase64 = '';
        if (isset($setting->kopsekolah) && $setting->kopsekolah && file_exists(FCPATH . $setting->kopsekolah)) {
            $type = pathinfo(FCPATH . $setting->kopsekolah, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->kopsekolah);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Nomor Surat (format dapat dikonfigurasi di /settings)
        $nomorSurat = \App\Libraries\SuratNomor::build(
            (int) ($letter->nomor_urut ?? 0),
            (int) ($letter->bulan ?? date('n')),
            (int) ($letter->tahun ?? date('Y')),
            $setting,
            (int) ($letter->level ?? 0),
            $letter->created_at ? date('d-m-Y', strtotime($letter->created_at)) : null
        );

        $viewData = [
            'setting' => $setting,
            'guru' => $guru,
            'jumlah_alpha' => $jumlahAlpha,
            'tingkat_teguran' => $teguranText,
            'kepsek_nama' => $setting->kepsek ?? '..........................',
            'kepsek_nip' => $setting->nip ?? '..........................', // Use nip from setting directly
            'nip' => $setting->nip ?? '..........................', // Alias for view if used
            'logo_base64' => $logoBase64,
            'kop_base64' => $kopBase64,
            'nomor_surat' => $nomorSurat,
            'periode_bulan' => (int) ($letter->bulan ?? date('n')),
            'periode_tahun' => (int) ($letter->tahun ?? date('Y')),
            // Tanggal surat = tanggal terbit arsip, bukan tanggal cetak.
            'tanggal_terbit' => !empty($letter->created_at) ? (string) $letter->created_at : null,
            // Daftar tanggal alpha periode surat untuk rincian di badan surat.
            'alpha_dates' => $alphaDates
        ];

        // QR TTE pengganti tanda tangan basah Kepsek
        $tteKode = \App\Libraries\TteQr::verificationCode(
            $nomorSurat, (int) $letter->id_guru, (int) $letter->level, (int) $letter->bulan, (int) $letter->tahun
        );
        $viewData['tte_kode'] = $tteKode;
        $viewData['tte_qr'] = \App\Libraries\TteQr::dataUri(
            \App\Libraries\TteQr::payload(
                $nomorSurat, (string) ($guru->nama_guru ?? '-'), (string) ($guru->nip ?? '-'),
                $teguranText, (int) $jumlahAlpha, $tteKode,
                (string) ($setting->kepsek ?? ''), (string) ($setting->nip ?? ''),
                (string) ($setting->sekolah ?? '')
            )
        );

        return $viewData;
    }

    /**
     * Download Surat Teguran PDF (Admin)
     */
    public function downloadTeguran($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();

        $letter = $db->table('absensi_surat_teguran a')
            ->join('master_guru g', 'g.id_guru = a.id_guru')
            ->where('a.id', $id)
            ->get()->getRow();

        if (!$letter) {
            return redirect()->back()->with('error', 'Data surat tidak ditemukan');
        }

        if (($letter->status ?? 'aktif') === 'dibatalkan') {
            return redirect()->back()->with('error', 'Surat teguran ini telah dibatalkan karena izin/dispensasi resmi disetujui');
        }

        // Reconstruct Context for PDF Generation (satu sumber via helper)
        $viewData = $this->dataPdfSuratTeguran($letter);

        // Reuse the teacher view since it's the same letter
        $html = view('members/guru/surat_teguran_pdf', $viewData);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Surat_Teguran_{$letter->level}_Hari_{$letter->nama_guru}.pdf";

        $dompdf->stream($filename, ["Attachment" => false]);
        exit;
    }

    /**
     * List pending correction requests
     */
    public function pendingKoreksi()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        // Ensure correction table schema
        $this->patchAbsensiKoreksiTable();

        $correctionModel = new \App\Models\AttendanceCorrectionModel();
        $pending = $correctionModel->getPendingWithDetails();
        $history = $correctionModel->getHistoryWithDetails(100);

        $oldestPending = null;
        foreach ($pending as $p) {
            if ($oldestPending === null || $p->created_at < $oldestPending) {
                $oldestPending = $p->created_at;
            }
        }
        $summaryKoreksi = [
            'pending' => count($pending),
            'oldest' => $oldestPending,
            'history' => count($history),
        ];

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/pending_koreksi', [
            'title' => 'Koreksi Absensi Manual',
            'judul' => 'Koreksi Absensi',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'pending' => $pending,
            'history' => $history,
            'summaryKoreksi' => $summaryKoreksi,
        ]);
    }

    /**
     * Approve Correction Request.
     * Seluruh penulisan (absensi + ledger poin + status koreksi) dalam satu
     * transaksi; transisi status memakai guard atomik agar dua admin yang
     * memproses bersamaan tidak menimbulkan hitungan ganda.
     */
    public function approveKoreksi($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID koreksi tidak valid');
        }

        $correctionModel = new \App\Models\AttendanceCorrectionModel();
        $correction = $correctionModel->find($id);

        if (!$correction) {
            return $this->respond(false, 'Data tidak ditemukan');
        }

        if ($correction->status !== 'pending') {
            return $this->respond(false, 'Sudah diproses sebelumnya');
        }

        $autoReject = function (string $note) use ($correctionModel, $id) {
            $correctionModel->where('id', $id)->where('status', 'pending')->set([
                'status' => 'rejected',
                'processed_by' => $this->getUserId(),
                'processed_at' => date('Y-m-d H:i:s'),
                'admin_note' => $note,
            ])->update();
        };

        // Whitelist field: cegah mass-assignment kolom points/denda/status/user_id
        // lewat field_name koreksi.
        if (!in_array($correction->field_name, ['jam_masuk', 'jam_pulang'], true)) {
            $autoReject('Field koreksi tidak didukung (otomatis ditolak sistem)');
            return $this->respond(false, 'Field koreksi tidak didukung');
        }

        // Validasi nilai jam HH:MM[:SS] / datetime valid
        $timeOnly = $correction->new_value;
        $datePart = null;
        if (strlen((string) $timeOnly) > 8) {
            $parts = explode(' ', (string) $timeOnly);
            $datePart = $parts[0] ?? null;
            $timeOnly = end($parts);
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $timeOnly)) {
            $autoReject('Nilai jam tidak valid (otomatis ditolak sistem)');
            return $this->respond(false, 'Nilai jam koreksi tidak valid');
        }

        // Apply the correction to attendance — data absen harus masih ada
        $att = $this->attendanceModel->find($correction->attendance_id);
        if (!$att) {
            $autoReject('Data absensi terkait tidak ditemukan (otomatis ditolak sistem)');
            return $this->respond(false, 'Data absensi terkait sudah tidak ditemukan');
        }

        // Baris proteksi (dinas/WFH/izin sah/alpha) tidak boleh diubah via koreksi jam —
        // koreksi jam pada baris tersebut akan menimpa status resmi menjadi ONTIME/LATE.
        {
            $attStMasuk = strtoupper((string) (is_array($att) ? ($att['status_masuk'] ?? '') : ($att->status_masuk ?? '')));
            $attStPulang = strtoupper((string) (is_array($att) ? ($att['status_pulang'] ?? '') : ($att->status_pulang ?? '')));
            $attIsWfh = (!empty(is_array($att) ? ($att['is_wfh'] ?? 0) : ($att->is_wfh ?? 0))) || $attStMasuk === 'WFH' || $attStPulang === 'WFH';
            $attIsLeave = in_array($attStMasuk, ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH'], true)
                || in_array($attStPulang, ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH'], true);
            if ($attIsWfh || $attIsLeave || in_array($attStMasuk, ['ALPHA', 'ALPA'], true)) {
                $autoReject('Baris ' . $attStMasuk . ' tidak bisa dikoreksi jamnya (otomatis ditolak sistem)');
                return $this->respond(false, 'Baris ' . $attStMasuk . ' tidak bisa dikoreksi jamnya');
            }
        }

        // --- Auto Recalculate Status and Points ---
        $updateData = [];

        // Get Settings
        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSet = $absenSettingModel->first();

        $db = \Config\Database::connect();
        $tgl = is_array($att) ? $att['tgl'] : $att->tgl;
        $dayOfWeek = date('N', strtotime($tgl));
        $attUserId = (int) (is_array($att) ? ($att['user_id'] ?? 0) : ($att->user_id ?? 0));

        // Koreksi hanya mengubah nilai waktu pada tanggal yang sama dengan data absen.
        // Nilai disimpan dinormalisasi mengikuti tanggal absen (hindari selisih hari).
        if ($datePart && $datePart !== substr((string) $tgl, 0, 10)) {
            $autoReject('Tanggal koreksi tidak sama dengan tanggal absen (otomatis ditolak sistem)');
            return $this->respond(false, 'Tanggal koreksi harus sama dengan tanggal absen');
        }
        $updateData[$correction->field_name] = date('Y-m-d H:i:s', strtotime($tgl . ' ' . $timeOnly));

            // Jadwal global opsional (tabel boleh belum ada) — fallback ke setting/shift
            $jadwal = null;
            if ($db->tableExists('absensi_jadwal_kerja')) {
                try {
                    $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
                } catch (\Throwable $e) {
                    log_message('error', '[approveKoreksi] jadwal: ' . $e->getMessage());
                    $jadwal = null;
                }
            }

            // Determine Limits: shift per guru (bila ditugaskan) → jadwal → setting
            $shiftModel = new \App\Models\ShiftModel();
            $workSchedule = $shiftModel->getScheduleForUser($attUserId, (int) $dayOfWeek, $jadwal, $absenSet);
            $jamMasukLimit = $workSchedule['jam_masuk'];
            $jamPulangLimit = $workSchedule['jam_pulang'];
            $shiftGrace = (int) ($workSchedule['grace_menit'] ?? ($absenSet->grace_period ?? 15));

            // --- 1. Handle Masuk Correction ---
            if ($correction->field_name === 'jam_masuk') {
                // Rumus baku AttendancePolicy — identik dengan check-in wajah,
                // scan QR, rekap, dan recalculasi bulanan.
                $hit = \App\Services\AttendancePolicy::telat($tgl, $timeOnly, [
                    'jam_masuk'   => $jamMasukLimit,
                    'grace_menit' => $shiftGrace,
                ]);

                $masukDenda = 0;
                $masukKeteranganDenda = null;

                if ($hit['status'] === 'ONTIME') {
                    $updateData['status_masuk'] = 'ONTIME';
                    $updateData['telat_menit']  = 0;
                    $masukPoints = ($hit['datang_awal_menit'] > 0)
                        ? $this->calculateProgressiveRewardPoints($hit['datang_awal_menit'], $absenSet)
                        : 0;
                } else {
                    $updateData['status_masuk'] = 'LATE';
                    $updateData['telat_menit']  = $hit['telat_menit'];
                    $calc = $this->calculateProgressivePenalty($hit['telat_menit'], $absenSet, 'Telat Masuk');
                    $masukPoints = -1 * $calc['points'];
                    $masukDenda = $calc['denda'];
                    $masukKeteranganDenda = $calc['keterangan'];
                }

                $updateData['datang_awal_menit'] = $hit['datang_awal_menit'];

                // Calculate Pulang Points (Keep existing status)
                $pulangPoints = 0;
                $pulangDenda = 0;
                $pulangKeteranganDenda = null;
                $statusPulang = is_array($att) ? $att['status_pulang'] : $att->status_pulang;
                $pulangCepatMenit = is_array($att) ? ($att['pulang_cepat_menit'] ?? 0) : ($att->pulang_cepat_menit ?? 0);
                if ($statusPulang === 'EARLY') {
                    $calcOut = $this->calculateProgressivePenalty($pulangCepatMenit, $absenSet, 'Pulang Awal');
                    $pulangPoints = -1 * $calcOut['points'];
                    $pulangDenda = $calcOut['denda'];
                    $pulangKeteranganDenda = $calcOut['keterangan'];
                }

                $updateData['points'] = $masukPoints + $pulangPoints;
                $updateData['denda'] = $masukDenda + $pulangDenda;
                $kets = array_values(array_filter([$masukKeteranganDenda, $pulangKeteranganDenda]));
                $updateData['keterangan_denda'] = $kets ? implode('; ', $kets) : null;
            }
            
            // --- 2. Handle Pulang Correction ---
            elseif ($correction->field_name === 'jam_pulang') {
                $thresholdPulang = strtotime($tgl . ' ' . $jamPulangLimit);
                
                $correctionParamsTime = strtotime($tgl . ' ' . $timeOnly);

                $pulangAkhirMenit = 0;
                $pulangDenda = 0;
                $pulangKeteranganDenda = null;

                if ($correctionParamsTime < $thresholdPulang) {
                    $updateData['status_pulang'] = 'EARLY';
                    $diff = $thresholdPulang - $correctionParamsTime;
                    $updateData['pulang_cepat_menit'] = ceil($diff / 60);
                    $updateData['pulang_akhir_menit'] = 0;
                    $calc = $this->calculateProgressivePenalty($updateData['pulang_cepat_menit'], $absenSet, 'Pulang Awal');
                    $pulangPoints = -1 * $calc['points'];
                    $pulangDenda = $calc['denda'];
                    $pulangKeteranganDenda = $calc['keterangan'];
                } else {
                    $updateData['status_pulang'] = 'ONTIME';
                    $updateData['pulang_cepat_menit'] = 0;
                    $pulangPoints = 0;
                    if ($correctionParamsTime > $thresholdPulang) {
                        $diffMinutes = (int) floor(($correctionParamsTime - $thresholdPulang) / 60);
                        $pulangAkhirMenit = ($diffMinutes >= 60) ? ($diffMinutes - 60) : 0;
                        if ($pulangAkhirMenit > 0) {
                            $pulangPoints = $this->calculateProgressiveRewardPoints($pulangAkhirMenit, $absenSet);
                        }
                    }
                }
                $updateData['pulang_akhir_menit'] = $pulangAkhirMenit;

                // Calculate Masuk Points (Keep existing status)
                $masukPoints = 0;
                $masukDenda = 0;
                $masukKeteranganDenda = null;
                $statusMasuk = is_array($att) ? $att['status_masuk'] : $att->status_masuk;
                $telatMenit = is_array($att) ? ($att['telat_menit'] ?? 0) : ($att->telat_menit ?? 0);
                if ($statusMasuk === 'LATE'
                    || ($statusMasuk === 'PENDING_VERIFICATION' && (int) $telatMenit > 0)) {
                    // Baris PENDING_VERIFICATION yang punya telat_menit tetap
                    // berdenda telat: sebelumnya hanya status 'LATE' yang
                    // dihitung, jadi mengoreksi jam PULANG saja menghapus denda
                    // masuknya tanpa pernah memulihkan status masuknya.
                    $calcIn = $this->calculateProgressivePenalty($telatMenit, $absenSet, 'Telat Masuk');
                    $masukPoints = -1 * $calcIn['points'];
                    $masukDenda = $calcIn['denda'];
                    $masukKeteranganDenda = $calcIn['keterangan'];
                }

                $updateData['points'] = $masukPoints + $pulangPoints;
                $updateData['denda'] = $masukDenda + $pulangDenda;
                $kets = array_values(array_filter([$masukKeteranganDenda, $pulangKeteranganDenda]));
                $updateData['keterangan_denda'] = $kets ? implode('; ', $kets) : null;
            }
        // ----------------------------------------------

        // Koreksi biasanya menurunkan denda hari itu. Kalau bulan itu sudah
        // dibayar bendahara, memangkas denda membuat sisa menjadi negatif —
        // kredit hantu yang bisa dipakai melunasi bulan lain. Status, jam, dan
        // poin tetap dikoreksi; hanya angkanya yang dipertahankan.
        if (array_key_exists('denda', $updateData)) {
            $dibayarCache = [];
            $dibayarBulan = $this->totalDibayarBulan(
                $attUserId,
                (int) date('n', strtotime($tgl)),
                (int) date('Y', strtotime($tgl)),
                $dibayarCache
            );
            $dendaLama    = (float) (is_array($att) ? ($att['denda'] ?? 0) : ($att->denda ?? 0));
            if ($dibayarBulan > 0 && (float) $updateData['denda'] < $dendaLama) {
                unset($updateData['denda'], $updateData['keterangan_denda']);
            }
        }

        $db->transBegin();

        try {
            // Tegakkan cap denda bulanan DI DALAM transaksi dengan lock agar
            // dua approval paralel tidak sama-sama lolos batas (sebelumnya
            // dihitung sebelum transBegin sehingga FOR UPDATE tak berpengaruh).
            if (isset($updateData['denda'], $tgl) && (float) $updateData['denda'] > 0 && isset($absenSet)) {
                $capService = new \App\Services\AttendancePenaltyService();
                $capped = $capService->applyMonthlyCap(
                    (int) ($correction->user_id ?? 0),
                    substr((string) $tgl, 0, 10),
                    (float) $updateData['denda'],
                    $absenSet,
                    (int) $correction->attendance_id,
                    true
                );
                if ($capped['capped']) {
                    $updateData['denda'] = $capped['total'];
                    $updateData['keterangan_denda'] = trim(($updateData['keterangan_denda'] ?? '') . ' (dibatasi cap bulanan Rp ' . number_format((float) $capped['total'], 0, ',', '.') . ')');
                }
            }
            $this->attendanceModel->update($correction->attendance_id, $updateData);
            // Sync poin sekali setelah update selesai
            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($correction->attendance_id);

            // Transisi status atomik: gagal bila admin lain sudah memproses duluan
            $correctionModel->where('id', $id)->where('status', 'pending')->set([
                'status' => 'approved',
                'processed_by' => $this->getUserId(),
                'processed_at' => date('Y-m-d H:i:s')
            ])->update();

            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Koreksi sudah diproses admin lain. Muat ulang halaman.');
            }

            // Koreksi pending lain untuk absensi+field yang sama kini basi
            // (old_value-nya tidak lagi cocok) → tolak otomatis agar tak menimpa.
            $staleCount = 0;
            try {
                $siblings = $db->table('absensi_koreksi')
                    ->select('id')
                    ->where('attendance_id', $correction->attendance_id)
                    ->where('field_name', $correction->field_name)
                    ->where('status', 'pending')
                    ->where('id !=', $id)
                    ->get()->getResult();
                foreach ($siblings as $sib) {
                    $db->table('absensi_koreksi')->where('id', $sib->id)->where('status', 'pending')->update([
                        'status' => 'rejected',
                        'processed_by' => $this->getUserId(),
                        'processed_at' => date('Y-m-d H:i:s'),
                        'admin_note' => 'Otomatis ditolak: sudah digantikan koreksi lain yang disetujui',
                    ]);
                    if ($db->affectedRows() === 1) $staleCount++;
                }
            } catch (\Throwable $ex) {
                log_message('error', 'Sibling koreksi cleanup: ' . $ex->getMessage());
            }

            $db->transCommit();

            // Evaluasi dan sinkronisasi pembatalan surat teguran jika hari alpha gugur
            try {
                $penaltySvc = new \App\Services\AttendancePenaltyService();
                $tglCorr = $tgl ?? date('Y-m-d');
                $blnCorr = (int) date('n', strtotime($tglCorr));
                $thnCorr = (int) date('Y', strtotime($tglCorr));
                $penaltySvc->syncSuratTeguranOnAttendanceChange((int) ($correction->user_id ?? 0), $blnCorr, $thnCorr, $absenSet ?? null);
            } catch (\Throwable $ex) {}
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Approve Koreksi Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyetujui koreksi — periksa log aplikasi.', null, null, 500);
        }

        // AUDIT LOG (di luar transaksi utama agar kegagalan log tak membatalkan koreksi)
        try {
            $auditModel = new AuditLogModel();
            $auditModel->log(
                'absensi_harian',
                $correction->attendance_id,
                'UPDATE',
                $correction->user_id,
                $this->getUserId(),
                [$correction->field_name => $correction->old_value],
                $updateData,
                'Koreksi Disetujui: ' . $correction->field_name . ' -> ' . $correction->new_value
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
        }

        // Notifikasi ke guru + grup. Best-effort, setelah transaksi selesai.
        try {
            $notif = new \App\Services\NotificationService();
            $corrUserId = (int) ($correction->user_id ?? 0);
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $corrUserId)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            $tglKoreksi = $tgl ?? date('Y-m-d');
            $notif->notifyKoreksiApproved(
                $namaGuru,
                date('d/m/Y', strtotime((string) $tglKoreksi)),
                $notif->koreksiFieldLabel((string) ($correction->field_name ?? '')),
                $notif->normalizeKoreksiValue((string) ($correction->old_value ?? '')),
                $notif->normalizeKoreksiValue((string) ($correction->new_value ?? '')),
                $corrUserId,
                (string) ($this->request->getPost('note') ?? '')
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi koreksi disetujui gagal: ' . $ex->getMessage());
        }

        $msg = 'Koreksi disetujui dan status diperbarui otomatis';
        if ($staleCount > 0) $msg .= " ({$staleCount} pengajuan basi lain ikut ditolak otomatis)";
        return $this->respond(true, $msg);
    }

    /**
     * Reject Correction Request (transisi atomik + audit dalam transaksi).
     */
    public function rejectKoreksi($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID koreksi tidak valid');
        }

        $correctionModel = new \App\Models\AttendanceCorrectionModel();
        $correction = $correctionModel->find($id);

        if (!$correction) {
            return $this->respond(false, 'Data tidak ditemukan');
        }

        if ($correction->status !== 'pending') {
            return $this->respond(false, 'Sudah diproses sebelumnya');
        }

        $note = trim((string) ($this->request->getPost('note') ?? ''));
        if (mb_strlen($note) > 500) {
            $note = mb_substr($note, 0, 500);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $correctionModel->where('id', $id)->where('status', 'pending')->set([
                'status' => 'rejected',
                'processed_by' => $this->getUserId(),
                'processed_at' => date('Y-m-d H:i:s'),
                'admin_note' => $note
            ])->update();

            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Koreksi sudah diproses admin lain. Muat ulang halaman.');
            }

            // AUDIT LOG
            try {
                $auditModel = new AuditLogModel();
                $auditModel->log(
                    'absensi_koreksi',
                    $id,
                    'UPDATE',
                    $correction->user_id,
                    $this->getUserId(),
                    ['status' => 'pending'],
                    ['status' => 'rejected', 'admin_note' => $note],
                    'Koreksi Ditolak Admin'
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Reject koreksi gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menolak koreksi — periksa log aplikasi.', null, null, 500);
        }

        // Notifikasi ke guru + grup. Best-effort, setelah transaksi selesai.
        try {
            $notif = new \App\Services\NotificationService();
            $corrUserId = (int) ($correction->user_id ?? 0);
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $corrUserId)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            $tglKoreksi = $correction->old_value
                && preg_match('/(\d{4}-\d{2}-\d{2})/', (string) $correction->old_value, $m) === 1
                ? date('d/m/Y', strtotime($m[1]))
                : date('d/m/Y');
            $notif->notifyKoreksiRejected(
                $namaGuru,
                $tglKoreksi,
                $notif->koreksiFieldLabel((string) ($correction->field_name ?? '')),
                $corrUserId,
                $note
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi koreksi ditolak gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Koreksi ditolak');
    }

    /**
     * Antrean verifikasi kehadiran (status PENDING_VERIFICATION).
     * Baris ini dibuat saat guru dengan alpha >= 10 check-in: kehadirannya
     * ditahan sampai admin/panitia memverifikasi (terima → hitung ulang
     * ONTIME/LATE, tolak → ALPHA). Tanpa halaman ini antrean mengendap
     * permanen dan ambang surat teguran macet.
     */
    public function pendingVerifikasi()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $pending = [];
        try {
            $pending = $db->table('absensi_harian ah')
                ->select('ah.*, mg.nama_guru, mg.nip')
                ->join('master_guru mg', 'mg.id_user = ah.user_id', 'left')
                ->where('ah.status_masuk', 'PENDING_VERIFICATION')
                ->orderBy('ah.tgl', 'ASC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', 'pendingVerifikasi gagal: ' . $e->getMessage());
        }

        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        return view('admin/presensi/pending_verifikasi', [
            'title' => 'Verifikasi Kehadiran',
            'judul' => 'Verifikasi Kehadiran',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'pending' => $pending,
        ]);
    }

    /**
     * Terima verifikasi: hitung ulang status dari jam tersimpan
     * (ONTIME/LATE + EARLY bila sudah pulang), cap lock dalam transaksi,
     * transisi atomik, sync poin + surat + notifikasi.
     */
    public function verifyVerifikasi($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID tidak valid');
        }

        $db = \Config\Database::connect();
        $row = $db->table('absensi_harian')->where('id', $id)->get()->getRow();
        if (!$row) {
            return $this->respond(false, 'Data tidak ditemukan');
        }
        if (strtoupper((string) ($row->status_masuk ?? '')) !== 'PENDING_VERIFICATION') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }
        if (empty($row->jam_masuk)) {
            return $this->respond(false, 'Baris tanpa jam masuk tidak bisa diverifikasi terima; tolak saja menjadi ALPHA.');
        }

        $this->patchAbsensiHarianVerifColumns();
        $db = \Config\Database::connect();

        $catatan = mb_substr(trim((string) ($this->request->getPost('note') ?? '')), 0, 500);
        $settingModel = new \App\Models\AttendanceSettingModel();
        $setting = $settingModel->first();
        $tanggal = substr((string) $row->tgl, 0, 10);
        $userId = (int) $row->user_id;

        $db->transBegin();
        try {
            $update = [];
            // pickTime(): zero-date ('0000-00-00 00:00:00') lolos empty() tetapi
            // membuat strtotime gagal, lalu date() atas false mengembalikan
            // epoch — jam masuk terbaca jadi 08:00:00 dan dendanya salah.
            $jamMasukTs  = \App\Services\AttendancePolicy::pickTime($row->jam_masuk ?? null);
            $jamPulangTs = \App\Services\AttendancePolicy::pickTime($row->jam_pulang ?? null);
            if ($jamMasukTs === null) {
                throw new \RuntimeException('Jam masuk pada baris ini tidak terbaca; tidak bisa diverifikasi.');
            }
            $this->recalculateCheckin($db, $setting, $tanggal, $jamMasukTs, $userId, $update);
            if ($jamPulangTs !== null) {
                $this->recalculateCheckout($db, $setting, $tanggal, $jamPulangTs, $userId, $update, $row);
            }
            // Cap dengan lock (dalam transaksi).
            if (($update['denda'] ?? 0) > 0) {
                $capped = (new \App\Services\AttendancePenaltyService())->applyMonthlyCap(
                    $userId, $tanggal, (float) $update['denda'], $setting, $id, true
                );
                if ($capped['capped']) {
                    $update['denda'] = $capped['total'];
                    $update['keterangan_denda'] = trim(($update['keterangan_denda'] ?? '') . ' (dibatasi cap bulanan Rp ' . number_format((float) $capped['total'], 0, ',', '.') . ')');
                }
            }
            // Sama seperti jalur hitung ulang harian: sisa denda dihitung live
            // dari absensi_harian.denda dikurangi pembayaran, jadi menurunkan
            // denda di bulan yang sudah dibayar bikin saldo negatif.
            $dibayarCache = [];
            $dibayarBulan = $this->totalDibayarBulan(
                $userId,
                (int) date('n', strtotime($tanggal)),
                (int) date('Y', strtotime($tanggal)),
                $dibayarCache
            );
            if ($dibayarBulan > 0 && array_key_exists('denda', $update)
                && (float) $update['denda'] < (float) ($row->denda ?? 0)) {
                unset($update['denda'], $update['keterangan_denda']);
            }
            if ($db->fieldExists('verified_by', 'absensi_harian')) {
                $update['verified_by'] = session()->get('user_id');
            }
            if ($db->fieldExists('verified_at', 'absensi_harian')) {
                $update['verified_at'] = date('Y-m-d H:i:s');
            }
            $update['updated_at'] = date('Y-m-d H:i:s');

            $db->table('absensi_harian')
                ->where('id', $id)->where('status_masuk', 'PENDING_VERIFICATION')
                ->update($update);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Status sudah diproses admin lain. Muat ulang halaman.');
            }

            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);

            try {
                (new \App\Services\AttendancePenaltyService())->syncSuratTeguranOnAttendanceChange(
                    $userId, (int) date('n', strtotime($tanggal)), (int) date('Y', strtotime($tanggal)), $setting
                );
            } catch (\Throwable $ex) {}

            try {
                (new AuditLogModel())->log('absensi_harian', $id, 'UPDATE', $userId, $this->getUserId(),
                    ['status_masuk' => 'PENDING_VERIFICATION'], $update, 'Verifikasi Kehadiran Diterima' . ($catatan !== '' ? ': ' . $catatan : ''));
            } catch (\Throwable $ex) {}

            $db->transCommit();
            $statusBaru = (string) ($update['status_masuk'] ?? 'ONTIME');
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', 'Verify verifikasi gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal memverifikasi — periksa log aplikasi.', null, null, 500);
        }

        try {
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $userId)->get()->getRow();
            (new \App\Services\NotificationService())->notifyVerifikasiDiterima(
                $guruRow->nama_guru ?? 'Guru', $tanggal, $statusBaru, $userId, $catatan !== '' ? $catatan : null
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi verifikasi diterima gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Kehadiran diverifikasi menjadi ' . $statusBaru);
    }

    /**
     * Tolak verifikasi: baris menjadi ALPHA + denda flat + cap lock,
     * transisi atomik, sync poin + surat + notifikasi.
     */
    public function rejectVerifikasi($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID tidak valid');
        }

        $db = \Config\Database::connect();
        $row = $db->table('absensi_harian')->where('id', $id)->get()->getRow();
        if (!$row) {
            return $this->respond(false, 'Data tidak ditemukan');
        }
        if (strtoupper((string) ($row->status_masuk ?? '')) !== 'PENDING_VERIFICATION') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        $alasan = mb_substr(trim((string) ($this->request->getPost('note') ?? 'Ditolak oleh admin/panitia')), 0, 500);
        $this->patchAbsensiHarianVerifColumns();
        $db = \Config\Database::connect();
        $setting = (new \App\Models\AttendanceSettingModel())->first() ?? (object) [];
        $baseAlpha = (float) ($setting->denda_alpha_per_hari ?? 5000);
        $pointAlpha = abs((int) ($setting->point_alpa ?? 5));
        $tanggal = substr((string) $row->tgl, 0, 10);
        $userId = (int) $row->user_id;

        $db->transBegin();
        try {
            $capped = (new \App\Services\AttendancePenaltyService())->applyMonthlyCap(
                $userId, $tanggal, $baseAlpha, $setting, $id, true
            );
            $denda = (float) $capped['total'];
            $ket = 'Alpa (Rp ' . number_format($baseAlpha, 0, ',', '.') . ')'
                . ($capped['capped'] ? ' (dibatasi cap bulanan Rp ' . number_format($denda, 0, ',', '.') . ')' : '');

            $updateTolak = [
                'status_masuk' => 'ALPHA',
                'status_pulang' => 'ALPHA',
                'points' => -$pointAlpha,
                'denda' => $denda,
                'keterangan_denda' => $ket,
                'keterangan' => 'Verifikasi ditolak: ' . $alasan,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Denda tidak boleh turun di bawah yang sudah terbayar: bulan yang
            // sudah dibayar tetap tercatat di ledger, jadi menol-kan tagihan
            // hari ini membuka kredit hantu (pola sama di approveKoreksi).
            $cacheTolak = [];
            $dibayarTolak = $this->totalDibayarBulan(
                $userId,
                (int) date('n', strtotime($tanggal)),
                (int) date('Y', strtotime($tanggal)),
                $cacheTolak
            );
            if ($dibayarTolak > 0 && $denda < (float) ($row->denda ?? 0)) {
                unset($updateTolak['denda'], $updateTolak['keterangan_denda']);
            }
            if ($db->fieldExists('verified_by', 'absensi_harian')) {
                $updateTolak['verified_by'] = session()->get('user_id');
            }
            if ($db->fieldExists('verified_at', 'absensi_harian')) {
                $updateTolak['verified_at'] = date('Y-m-d H:i:s');
            }

            $db->table('absensi_harian')
                ->where('id', $id)->where('status_masuk', 'PENDING_VERIFICATION')
                ->update($updateTolak);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Status sudah diproses admin lain. Muat ulang halaman.');
            }

            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);

            try {
                (new \App\Services\AttendancePenaltyService())->syncSuratTeguranOnAttendanceChange(
                    $userId, (int) date('n', strtotime($tanggal)), (int) date('Y', strtotime($tanggal)), $setting
                );
            } catch (\Throwable $ex) {}

            try {
                (new AuditLogModel())->log('absensi_harian', $id, 'UPDATE', $userId, $this->getUserId(),
                    ['status_masuk' => 'PENDING_VERIFICATION'], ['status_masuk' => 'ALPHA'], 'Verifikasi Kehadiran Ditolak: ' . $alasan);
            } catch (\Throwable $ex) {}

            $db->transCommit();
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', 'Reject verifikasi gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menolak verifikasi — periksa log aplikasi.', null, null, 500);
        }

        try {
            $guruRow = $db->table('master_guru')->select('nama_guru')->where('id_user', $userId)->get()->getRow();
            (new \App\Services\NotificationService())->notifyVerifikasiDitolak(
                $guruRow->nama_guru ?? 'Guru', $tanggal, $userId, $alasan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi verifikasi ditolak gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Verifikasi ditolak, baris menjadi ALPHA');
    }



    /**
     * Attendance Settings Page
     */
    public function setting()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        // Ensure table schema is up to date
        $this->patchAbsensiSettingTable();
        $this->patchAbsensiJadwalTable();
        $this->patchAbsensiHarianTable();

        // --- AUTO UGRADE DB (MAX IZIN/CUTI) ---
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        if ($db->tableExists('absensi_setting')) {
            $fields = $db->getFieldNames('absensi_setting');
            if (!in_array('max_izin_hari', $fields)) {
                $forge->addColumn('absensi_setting', [
                    'max_izin_hari' => ['type' => 'INT', 'constraint' => 3, 'default' => 3, 'null' => true],
                    'max_cuti_hari' => ['type' => 'INT', 'constraint' => 3, 'default' => 12, 'null' => true]
                ]);
            }

            // Kolom kuota yang dipisah dari batas durasi per pengajuan.
            // Default sengaja dibuat sama dengan nilai lama supaya
            // instalasi yang sudah ada tidak berubah perilaku saat upgrade.
            $tambahanKuota = [
                'max_izin_per_pengajuan'   => ['type' => 'INT', 'constraint' => 3, 'default' => 3, 'null' => true],
                'max_cuti_per_pengajuan'   => ['type' => 'INT', 'constraint' => 3, 'default' => 12, 'null' => true],
                'max_sakit_per_pengajuan'  => ['type' => 'INT', 'constraint' => 3, 'default' => 3, 'null' => true],
                'max_dinas_per_pengajuan'  => ['type' => 'INT', 'constraint' => 3, 'default' => 30, 'null' => true],
                'max_sakit_hari'           => ['type' => 'INT', 'constraint' => 3, 'default' => 12, 'null' => true],
                'kuota_hari_kerja'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => true],
            ];
            $baru = [];
            foreach ($tambahanKuota as $kolom => $def) {
                if (!in_array($kolom, $fields, true)) {
                    $baru[$kolom] = $def;
                }
            }
            if ($baru !== []) {
                $forge->addColumn('absensi_setting', $baru);
            }
        }
        // --- END UPGRADE ---

        $settingModel = new \App\Models\AttendanceSettingModel();
        $locationModel = new \App\Models\AttendanceLocationModel();

        $setting = $settingModel->first();
        if (!$setting) {
            // Create default if not exists
            $settingModel->insert([
                'jam_masuk' => '07:30:00',
                'jam_pulang' => '15:00:00',
                'radius_meter' => 100
            ]);
            $setting = $settingModel->first();
        }

        $locations = $locationModel->findAll();
        $jadwal = $db->table('absensi_jadwal_kerja')->orderBy('hari', 'ASC')->get()->getResult();

        $this->patchSettingNomorTable();

        $appSetting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$appSetting)
            $appSetting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        $tp = $this->dashboardModel->getTahun();
        $smt = $this->dashboardModel->getSemester();

        return view('admin/presensi/setting', [
            'title'        => 'Pengaturan & Konfigurasi Sistem',
            'judul'        => 'Pengaturan & Konfigurasi Sistem',
            'subjudul'     => 'Profil Sekolah & Kebijakan Presensi',
            'setting'      => $appSetting,
            'tp'           => $tp,
            'tp_active'    => $tp_active,
            'smt'          => $smt,
            'smt_active'   => $smt_active,
            'user'         => $this->getUser(),
            'absenSetting' => $setting,
            'locations'    => $locations,
            'jadwal'       => $jadwal,
            // Kartu "Tema Warna Aplikasi" di view ini butuh keduanya; sebelumnya
            // hanya Settings::index yang mengirim, sehingga route ini fatal.
            'theme'        => \App\Libraries\Theme::current(),
            'themePresets' => \App\Libraries\Theme::presetOptions(),
        ]);
    }

    /**
     * Save Settings
     */
    public function saveSetting()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        // Ensure table schema is up to date
        $this->patchAbsensiSettingTable();

        $settingModel = new \App\Models\AttendanceSettingModel();
        $db = \Config\Database::connect();

        // Nilai lama dipakai hanya supaya kolom hari_libur_mingguan tidak
        // ikut ter-reset jadi kosong sebelum sync dari jadwal dijalankan.
        $absenSettingRow = $db->table('absensi_setting')->where('id', 1)->get()->getRow();

        // 1. Save Global Setting
    $data = [
        'radius_meter' => (int) $this->request->getPost('radius_meter'),
        'foto_wajib' => $this->request->getPost('foto_wajib') ? 1 : 0,
        'libur_kalender' => $this->request->getPost('libur_kalender') ? 1 : 0,
        // Sakelar basis hitung kuota. Checkbox, jadi 0 (tidak dikirim)
        // berarti "hitung hari kalender" — itu/default nilai yang disengaja,
        // bukan POST parsial yang harus diabaikan.
        'kuota_hari_kerja' => $this->request->getPost('kuota_hari_kerja') ? 1 : 0,
        // hari_libur_mingguan TIDAK lagi dikirim dari form. Nilainya diturunkan
        // dari absensi_jadwal_kerja.is_libur di syncWeeklyHolidayFromJadwal(),
        // jadi kolom ini tidak lagi punya jalur edit yang bisa menggagalkan.
        'hari_libur_mingguan' => $absenSettingRow->hari_libur_mingguan ?? 'Sun',
        'max_izin_hari' => (int) $this->request->getPost('max_izin_hari'),
        'max_cuti_hari' => (int) $this->request->getPost('max_cuti_hari'),
        'max_sakit_hari' => (int) $this->request->getPost('max_sakit_hari'),
        'max_izin_per_pengajuan' => (int) $this->request->getPost('max_izin_per_pengajuan'),
        'max_cuti_per_pengajuan' => (int) $this->request->getPost('max_cuti_per_pengajuan'),
        'max_sakit_per_pengajuan' => (int) $this->request->getPost('max_sakit_per_pengajuan'),
        'max_dinas_per_pengajuan' => (int) $this->request->getPost('max_dinas_per_pengajuan'),
        'point_telat' => (int) $this->request->getPost('point_telat'),
        'point_alpa' => (int) $this->request->getPost('point_alpa'),
        'point_pulang_awal' => (int) $this->request->getPost('point_pulang_awal'),
        // Pengurang poin guru yang masuk tapi tidak absen pulang. 0 = sekolah
        // tidak menerapkan aturan ini; cron membaca nilai ini dan melewat
        // seluruh langkahnya bila 0. Batas atas 100 cuma penjaga salah ketik
        // (satu digit_extra membuat poin setahun guru anjlok drastis).
        'point_lupa_pulang' => max(0, min(100, (int) $this->request->getPost('point_lupa_pulang'))),
        'denda_alpha_per_hari' => (int) $this->request->getPost('denda_alpha_per_hari'),
        // Denda siswa: alpha (tidak masuk tanpa keterangan) dan bolos (masuk
        // tanpa absen pulang). 0 = tidak ditagih. Nominal negatif akan membuat
        // total bulanan siswa berkurang, jadi dipangkas di sisi server.
        'denda_alpha_siswa_per_hari' => max(0, (int) $this->request->getPost('denda_alpha_siswa_per_hari')),
        'denda_bolos_siswa_per_hari' => max(0, (int) $this->request->getPost('denda_bolos_siswa_per_hari')),
        'max_denda_siswa_bulan'      => max(0.0, (float) $this->request->getPost('max_denda_siswa_bulan')),

        // New Point Fields
        'point_hadir' => (int) $this->request->getPost('point_hadir'),
        'point_izin'  => (int) $this->request->getPost('point_izin'),
        'point_sakit' => (int) $this->request->getPost('point_sakit'),
        'point_cuti'  => (int) $this->request->getPost('point_cuti'),

        'grace_period' => (int) $this->request->getPost('grace_period'),
        'denda_per_point' => (float) $this->request->getPost('denda_per_point'),
        'max_denda_bulan' => (float) $this->request->getPost('max_denda_bulan'),
        'denda_tier_1' => (int) $this->request->getPost('denda_tier_1'),
        'denda_tier_2' => (int) $this->request->getPost('denda_tier_2'),
        'denda_tier_3' => (int) $this->request->getPost('denda_tier_3'),
        'point_tier_1' => (int) ($this->request->getPost('point_tier_1') ?: 1),
        'point_tier_2' => (int) ($this->request->getPost('point_tier_2') ?: 2),
        'point_tier_3' => (int) ($this->request->getPost('point_tier_3') ?: 5),
        'reward_point_tier_1' => (int) ($this->request->getPost('reward_point_tier_1') ?: 1),
        'reward_point_tier_2' => (int) ($this->request->getPost('reward_point_tier_2') ?: 1),
        'reward_point_tier_3' => (int) ($this->request->getPost('reward_point_tier_3') ?: 2),
        'reward_cap_menit'    => (int) ($this->request->getPost('reward_cap_menit') ?: 30),
        // denda_per_menit & denda_pulang_awal SENGAJA tidak ikut ditulis lagi.
        // Keduanya tidak punya input di /settings dan tidak pernah dipakai
        // perhitungan mana pun — tarif yang berlaku adalah denda_tier_1/2/3
        // (lihat AttendancePenaltyService::calculatePenalty). Dulu keduanya
        // masih ada di $data dengan fallback ?: 1000, sehingga setiap
        // penyimpanan /settings diam-diam menimpanya jadi 1000 walau
        // tidak ada yang mengubah. Kolomnya sengaja dibiarkan di database
        // supaya data lama tidak rusak.
        // Notification Settings
        'telegram_bot_token' => $this->request->getPost('telegram_bot_token'),
        'telegram_chat_id' => $this->request->getPost('telegram_chat_id'),
        'wa_gateway_url' => $this->request->getPost('wa_gateway_url') ?: 'https://api.fonnte.com/send',
        'wa_token' => $this->request->getPost('wa_token'),
        'wa_target_number' => $this->request->getPost('wa_target_number'),
        // Gateway WhatsApp OpenWA. Default 'off' supaya instalasi lama yang
        // tidak menyentuhnya tidak tiba-tiba menembak gateway baru.
        'wa_gateway_mode' => $this->normalizeWaModeInput(),
        'wa_base_url' => trim((string) ($this->request->getPost('wa_base_url') ?? '')),
        'wa_api_key' => trim((string) ($this->request->getPost('wa_api_key') ?? '')),
        'wa_session_name' => trim((string) ($this->request->getPost('wa_session_name') ?? '')),
        // Standar SMA — persentase kehadiran minimum
        'min_kehadiran_persen'    => (float) ($this->request->getPost('min_kehadiran_persen') ?: 90),
        'alert_kehadiran_enabled' => $this->request->getPost('alert_kehadiran_enabled') ? 1 : 0,
        // Tidak ada form control bernama ini, jadi getPost() selalu null dan
        // nilainya lama ditulis 0 di setiap penyimpanan — yang membuat cron
        // kehadiran rendah mati permanen tanpa cara menghidupkannya lagi.
        // Sekalian disinkronkan dari checkbox yang benar-benar ada di form.
        'notif_kehadiran_rendah'  => $this->kehadiranRendahFromRequest(),
        // WFH Settings
        'wfh_enabled'       => $this->request->getPost('wfh_enabled') ? 1 : 0,
        'wfh_mulai'         => $this->request->getPost('wfh_mulai') ?: '08:00:00',
        'wfh_selesai'       => $this->request->getPost('wfh_selesai') ?: '16:00:00',
        'wfh_max_per_bulan' => (int) ($this->request->getPost('wfh_max_per_bulan') ?: 5),
        'wfh_wajib_wajah'   => $this->request->getPost('wfh_wajib_wajah') ? 1 : 0,
        // Syarat jurnal sebelum absen pulang (checkbox)
        'wajib_jurnal_sebelum_pulang' => $this->request->getPost('wajib_jurnal_sebelum_pulang') ? 1 : 0,
        // Jurnal harus tervalidasi (hanya berlaku bila syarat jurnal di atas aktif)
        'wajib_jurnal_tervalidasi_sebelum_pulang' => $this->request->getPost('wajib_jurnal_tervalidasi_sebelum_pulang') ? 1 : 0,
        // Lembur
        'point_lembur_per_jam' => (int) ($this->request->getPost('point_lembur_per_jam') ?: 5),
        // Jam operasional scanner QR siswa (scan masuk di luar jendela ditolak)
        'qr_scan_aktif' => $this->request->getPost('qr_scan_aktif') ? 1 : 0,
        'qr_scan_mulai' => $this->normalizeScanTime($this->request->getPost('qr_scan_mulai'), '05:30:00'),
        'qr_scan_selesai' => $this->normalizeScanTime($this->request->getPost('qr_scan_selesai'), '18:00:00'),
        // Biometric Face Match
        'face_threshold'    => (float) ($this->request->getPost('face_threshold') ?: 0.72),
        'face_match_mode'   => $this->request->getPost('face_match_mode') ?: 'ai_match',
        // Google OAuth Integration
        'google_client_id'     => trim($this->request->getPost('google_client_id') ?? ''),
        'google_client_secret' => trim($this->request->getPost('google_client_secret') ?? ''),
        'google_login_enabled' => $this->request->getPost('google_login_enabled') ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Token kiosk: kolomnya write-only (nilainya tidak pernah ditampilkan ke
    // form, lihat Views/admin/presensi/setting.php). Karena field-nya ikut
    // terkirim dengan nilai kosong setiap kali form disimpan, "kosong" tidak
    // boleh berarti "hapus" — kalau ya, setiap penyimpanan lain diam-diam
    // membuka kembali /scanner untuk umum. Mengosongkan sekarang harus lewat
    // checkbox khusus.
    $hapusToken = $this->request->getPost('qr_kiosk_token_hapus') !== null;
    $tokenBaru  = substr(trim((string) ($this->request->getPost('qr_kiosk_token') ?? '')), 0, 64);

    if ($hapusToken && $tokenBaru !== '') {
        return $this->respond(false, 'Pilih salah satu: ganti token kiosk, atau hapus token kiosk.');
    }

    if ($tokenBaru !== '' && !preg_match('/^[A-Za-z0-9\-_]+$/', $tokenBaru)) {
        return $this->respond(false, 'Token kiosk hanya boleh huruf, angka, dash, underscore (maks 64).');
    }

    if ($hapusToken) {
        $data['qr_kiosk_token'] = null;
    } elseif ($tokenBaru !== '') {
        $data['qr_kiosk_token'] = $tokenBaru;
    }
    // Keduanya kosong & tidak dicentang → tidak dihapus dari $data? Tidak:
    // key-nya belum pernah ada, jadi kolom lama tidak tersentuh.

    // Jaga-jaga nilai numerik dan teks: kalau field tidak ikut terkirim di
    // POST, jangan menimpanya dengan 0 atau string kosong.
    //
    // Tanpa ini, satu POST parsial (curl, integrasi lain, atau form yang
    // gagal load sebagian) akan meng-nol-kan semua tarif sekaligus — termasuk
    // denda_tier_1/2/3, grace_period, dan radius_meter. Denda keterlambatan
    // yang tadinya Rp 100/200/500 per menit diam-diam jadi nol, padahal
    //_setting_ baru saja disimpan dengan "sukses".
    //
    // Sejak blok ini ada, kolom teks ikut dijaga dengan alasan yang sama:
    // telegram_bot_token, wa_token dan google_client_secret diambil mentah
    // dari POST, jadi POST parsial menghapus kredensial itu tanpa pernah
    // ada yang meminta. Form /settings mengirim kesemuanya (satu <form>),
    // jadi mengisi kolom lalu menghapus isinya secara sadar tetap bekerja
    // seperti sebelumnya — yang dibedakan hanya "tidak terkirim" vs "kosong".
    //
    // Checkbox TIDAK ikut daftar ini: memang sudah benar 0 saat tidak dicentang.
    foreach ([
        'radius_meter', 'max_izin_hari', 'max_cuti_hari', 'max_sakit_hari',
        'max_izin_per_pengajuan', 'max_cuti_per_pengajuan',
        'max_sakit_per_pengajuan', 'max_dinas_per_pengajuan',
        'point_telat', 'point_alpa', 'point_pulang_awal', 'point_lupa_pulang',
        'point_hadir', 'point_izin', 'point_sakit', 'point_cuti',
        'denda_alpha_per_hari', 'grace_period', 'denda_per_point', 'max_denda_bulan',
        'denda_alpha_siswa_per_hari', 'denda_bolos_siswa_per_hari', 'max_denda_siswa_bulan',
        'denda_tier_1', 'denda_tier_2', 'denda_tier_3',
        'wfh_max_per_bulan', 'point_lembur_per_jam',
        'min_kehadiran_persen', 'face_threshold', 'reward_cap_menit',
        // Teks & kredensial
        'telegram_bot_token', 'telegram_chat_id', 'wa_token', 'wa_target_number',
        'wa_gateway_url', 'wa_base_url', 'wa_api_key', 'wa_session_name',
        'google_client_id', 'google_client_secret', 'face_match_mode',
        'wfh_mulai', 'wfh_selesai',
        'point_tier_1', 'point_tier_2', 'point_tier_3',
        'reward_point_tier_1', 'reward_point_tier_2', 'reward_point_tier_3',
    ] as $kunci) {
        if ($this->request->getPost($kunci) === null) {
            unset($data[$kunci]); // biarkan nilai lama di database
        }
    }

    // Clamp batas kuota izin di sisi server.
    //
    // Atribut min="1" pada <input type="number"> hanya berlaku di browser;
    // POST langsung (curl, form lama, integrasi) bisa mengirim angka
    // negatif atau teks. Semuanya tanpa exception berakhir sebagai
    // `$batas > 0` bernilai false di IzinKuotaService — artinya batas
    // MATI SENYAP tanpa pesan apa pun. Kuota 3 hari/tahun yang diam-diam
    // berubah jadi "tanpa batas" adalah kegagalan bisnis, bukan
    // preferensi yang bisa dibiarkan lewat UI saja.
    //
    // 0 tetap sah sebagai "tanpa batas" untuk jenis yang memang tidak
    //-shelf consumed sebagai hak (dinas), tapi tidak boleh negatif.
    foreach ([
        'max_izin_hari', 'max_cuti_hari', 'max_sakit_hari',
        'max_izin_per_pengajuan', 'max_cuti_per_pengajuan',
        'max_sakit_per_pengajuan', 'max_dinas_per_pengajuan',
    ] as $kunci) {
        if (! array_key_exists($kunci, $data)) {
            continue;
        }
        $nilai = (int) $data[$kunci];
        $data[$kunci] = max(0, min(365, $nilai));
    }

    // kuota_hari_kerja sudah diproses di atas sebagai checkbox; di sini
    // hanya dijaga tetap 0/1 kalau form mengirim nilai aneh.

    // Sama untuk kolom gateway OpenWA: form lama / tab lain yang tidak
    // mengirimnya harus tidak menghapus konfigurasi yang sudah tersimpan.
    foreach (['wa_gateway_mode', 'wa_base_url', 'wa_api_key', 'wa_session_name'] as $kunci) {
        if ($this->request->getPost($kunci) === null) {
            unset($data[$kunci]);
        }
    }

        $jadwalData = $this->request->getPost('jadwal');
        if ($this->request->getPost('jam_masuk')) {
            $data['jam_masuk'] = $this->request->getPost('jam_masuk');
        } elseif (!empty($jadwalData[1]['jam_masuk'])) {
            $data['jam_masuk'] = $jadwalData[1]['jam_masuk'];
        }
        if ($this->request->getPost('jam_pulang')) {
            $data['jam_pulang'] = $this->request->getPost('jam_pulang');
        } elseif (!empty($jadwalData[1]['jam_pulang'])) {
            $data['jam_pulang'] = $jadwalData[1]['jam_pulang'];
        }

        // Jendela scan harus kronologis (tak dukung overnight)
        if ($data['qr_scan_mulai'] >= $data['qr_scan_selesai']) {
            return $this->respond(false, 'Jam mulai scan harus lebih awal dari jam selesai.');
        }

        $current = $settingModel->first();
        if ($current) {
            $settingModel->update($current->id, $data);
        } else {
            $settingModel->insert($data);
        }

        // Catatan keamanan: perubahan token kiosk dicatat, tapi nilainya tidak
        // pernah ditulis ke audit log — tabel audit dibaca banyak orang.
        if (array_key_exists('qr_kiosk_token', $data)) {
            try {
                (new AuditLogModel())->log(
                    'absensi_setting',
                    (int) ($current->id ?? 0),
                    'UPDATE',
                    null,
                    $this->getUserId(),
                    ['qr_kiosk_token' => '(tersimpan, tidak ditampilkan)'],
                    ['qr_kiosk_token' => $data['qr_kiosk_token'] === null ? '(dikosongkan)' : '(diganti)'],
                    'Perubahan token kiosk endpoint /scanner'
                );
            } catch (\Throwable $ex) {
            }
        }

        $this->flushOpenwaCache();

        // 2. Save Daily Schedule
        // Data posted: jadwal[1][jam_masuk], jadwal[1][jam_pulang], jadwal[1][is_libur]
        // Ini sumber kebenaran status libur mingguan. Setelah ditulis,
        // hari_libur_mingguan diturunkan ulang supaya tidak ada lagi
        // dua tempat yang bisa berbeda pendapat soal "hari ini libur?"
        if ($jadwalData && is_array($jadwalData)) {
            foreach ($jadwalData as $hari => $val) {
                if (!is_array($val)) {
                    continue;
                }
                // Kolom TIME tidak boleh menerima '': sql_mode sengaja tidak
                // strict di sini (MySQLi Connection men-SRET STRICT_*), jadi ''
                // disimpan sebagai '00:00:00' tanpa error — jadwal jadi "masuk
                // tengah malam" dan semua scan tercatat telat. Kolom yang
                // kosong/rusak dibiarkan seperti adanya; hari libur boleh
                // memang tidak punya jam.
                $mas  = \App\Services\AttendancePolicy::pickTime($val['jam_masuk'] ?? null);
                $pul  = \App\Services\AttendancePolicy::pickTime($val['jam_pulang'] ?? null);
                $upd  = ['is_libur' => isset($val['is_libur']) ? 1 : 0];
                if ($mas !== null && $mas !== '00:00:00') {
                    $upd['jam_masuk'] = $mas;
                }
                if ($pul !== null && $pul !== '00:00:00') {
                    $upd['jam_pulang'] = $pul;
                }
                $db->table('absensi_jadwal_kerja')->where('hari', (int) $hari)->update($upd);
            }
        }
        $this->syncWeeklyHolidayFromJadwal($db);

        // Auto-recalculate presensi hari ini mengikuti toleransi & jadwal terbaru
        try {
            $this->doRecalculateDate(date('Y-m-d'));
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::saveSetting] doRecalculateDate error: ' . $e->getMessage());
        }

        return $this->respond(true, 'Pengaturan berhasil disimpan');
    }

    /**
     * Hitung ulang status presensi harian (ONTIME / LATE, keterlambatan, denda, poin)
     * berdasarkan konfigurasi jam masuk, toleransi (grace_period), dan jadwal kerja aktif.
     */
    public function recalculateDayAttendance(?string $targetDate = null)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $date = trim((string) ($this->request->getPost('date') ?? ''));
        if ($date === '') {
            $date = $targetDate ?: date('Y-m-d');
        }

        // Tanggal masuk lewat POST dan dipakai apa adanya: '2026-11-31' membuat
        // strtotime meloloskan tanggal yang tidak ada (jadi 1 Desember), dan
        // string bebas membuat where('tgl', $date) tidak cocok dengan apa pun
        // sementara pesan sukses tetap menyebut tanggal itu.
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $date = $m[0];
        } else {
            return $this->respond(false, 'Format tanggal tidak valid (pakai YYYY-MM-DD).', null, null, 400);
        }

        $res = $this->doRecalculateDate($date);

        $pesan = "Berhasil menghitung ulang status presensi untuk tanggal {$date} ({$res['updated']} data dievaluasi)";
        if (!empty($res['denda_dipertahankan'])) {
            $pesan .= ', denda ' . $res['denda_dipertahankan'] . ' baris dipertahankan karena bulan ini sudah ada pembayaran';
        }
        if (!empty($res['lewati_rusak'])) {
            $pesan .= ', ' . $res['lewati_rusak'] . ' baris dilewati karena jam tersimpan tidak terbaca (perlu dikoreksi manual)';
        }

        return $this->respond(true, $pesan, $res);
    }

    /**
     * Hitung ulang menyusun poin dari jam & status saja, sementara penalti
     * "tidak absen pulang" adalah fakta yang sudah terjadi di baris itu
     * (pulang_tidak_tercatat + poin_penalti_pulang). Tanpa fungsi ini setiap
     * "Hitung Ulang" menghapus potongan yang sudah jatuh — poin guru naik
     * diam-diam — atau justru meninggalkan penanda pada hari yang jam
     * pulangnya ternyata sudah tercatat.
     *
     * Mengembalikan poin final dan menulis ulang penanda di $update (pola yang
     * sama dengan applyLokasiUtama). $adaKolom false pada instalasi yang belum
     * punya kolomnya: tidak ada yang dibaca, tidak ada yang ditulis, hitung
     * ulang berjalan seperti sebelumnya.
     */
    private function terapkanPenaltiLupaPulang(
        int $points,
        object $r,
        ?string $jamPulangTs,
        bool $adaKolom,
        array &$update
    ): int {
        if (! $adaKolom || (int) ($r->pulang_tidak_tercatat ?? 0) !== 1) {
            return $points;
        }

        if ($jamPulangTs === null) {
            // Hari masih tanpa absen pulang: potongan dipakai lagi, nilainya
            // diambil dari baris (tarif pengaturan bisa sudah berubah sejak
            // penalti itu jatuh).
            return $points - max(0, (int) ($r->poin_penalti_pulang ?? 0));
        }

        $update['pulang_tidak_tercatat'] = 0;
        $update['poin_penalti_pulang']   = 0;

        return $points;
    }

    /**
     * Logika inti rekalkulasi presensi untuk tanggal tertentu
     */
    public function doRecalculateDate(string $date): array
    {
        $db = \Config\Database::connect();
        $settingModel = new \App\Models\AttendanceSettingModel();
        $setting = $settingModel->first();

        $dayOfWeek = (int) date('N', strtotime($date));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }

        $shiftModel = new \App\Models\ShiftModel();

        $rows = $db->table('absensi_harian')
            ->where('tgl', $date)
            ->where('jam_masuk IS NOT NULL')
            ->get()->getResult();

        $penaltySvc = new \App\Services\AttendancePenaltyService();
        $adaKolomPenalti = $db->fieldExists('pulang_tidak_tercatat', 'absensi_harian');
        $updatedCount = 0;
        $dipertahankan = 0;
        $lewatiRusak = 0;
        $dibayarCache  = [];
        // Satu transaksi per tanggal + cap lock agar hitung ulang paralel
        // (atau vs check-in) tidak sama-sama lolos batas bulanan.
        $db->transBegin();
        try {
        foreach ($rows as $r) {
            // Baris proteksi (dinas/izin/sakit/cuti/WFH/alpha) tidak pernah dihitung ulang,
            // terlepas dari ada tidaknya jam_masuk. Kecuali: nolkan denda dinas yang terlanjur terisi.
            $currentStatus = strtoupper((string) ($r->status_masuk ?? ''));
            $currentPulang = strtoupper((string) ($r->status_pulang ?? ''));
            $isWfhRow = (!empty($r->is_wfh) && (int) $r->is_wfh === 1) || $currentStatus === 'WFH' || $currentPulang === 'WFH';
            $isDinasRow = strpos($currentStatus, 'DINAS') === 0 || strpos($currentPulang, 'DINAS') === 0;
            $isLeaveRow = in_array($currentStatus, ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH', 'PENDING_VERIFICATION'], true)
                || in_array($currentPulang, ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH', 'PENDING_VERIFICATION'], true);
            if ($isWfhRow || $isLeaveRow || in_array($currentStatus, ['ALPHA', 'ALPA'], true)) {
                if ($isDinasRow && (float) ($r->denda ?? 0) > 0) {
                    // Nol-kan denda dinas hanya bila bulan itu belum ada
                    // pembayaran; kalau sudah, menol-kannya membuka kredit
                    // hantu di ledger sisa denda (lihat guard di bawah).
                    $dibayarDinas = $this->totalDibayarBulan(
                        (int) $r->user_id,
                        (int) date('n', strtotime($date)),
                        (int) date('Y', strtotime($date)),
                        $dibayarCache
                    );
                    if ($dibayarDinas > 0) {
                        $dipertahankan++;
                    } else {
                        $db->table('absensi_harian')->where('id', $r->id)->update(['denda' => 0, 'keterangan_denda' => null]);
                        $updatedCount++;
                    }
                }
                continue;
            }

            $sched = $shiftModel->getScheduleForUser((int) $r->user_id, $dayOfWeek, $jadwal, $setting);
            $jamMasukBatas = $sched['jam_masuk'];
            $graceMins = (int) ($sched['grace_menit'] ?? ($setting->grace_period ?? 15));

            $jamMasukTs  = \App\Services\AttendancePolicy::pickTime($r->jam_masuk ?? null);
            $jamPulangTs = \App\Services\AttendancePolicy::pickTime($r->jam_pulang ?? null);

            // Zero-date ('0000-00-00 00:00:00') lolos !empty() tetapi tidak
            // terbaca: telat() atas nilai begitu mengembalikan ONTIME, jadi
            // baris rusak berubah menjadi "hadir tepat waktu, tanpa denda".
            // Tidak ada dasar hitung → jangan sentuh barisnya.
            if (($jamMasukTs === null && !empty($r->jam_masuk))
                || ($jamPulangTs === null && !empty($r->jam_pulang))) {
                $lewatiRusak++;
                continue;
            }

            // Rumus baku AttendancePolicy. Tanggal baris ikut dipakai, jadi
            // recalculasi hari-hari lampau tidak lagi dibandingkan dengan
            // ambang/jam milik hari ini.
            $hit = \App\Services\AttendancePolicy::telat($date, $jamMasukTs ?? (string) $r->jam_masuk, [
                'jam_masuk'   => $jamMasukBatas,
                'grace_menit' => $graceMins,
            ]);

            $update = [];
            $masukPoints = 0;
            $masukDenda = 0;
            $masukKetDenda = null;

            if ($hit['status'] === 'ONTIME') {
                $update['status_masuk'] = 'ONTIME';
                $update['telat_menit'] = 0;
                $masukPoints = $this->calculateProgressiveRewardPoints($hit['datang_awal_menit'], $setting);
                $update['datang_awal_menit'] = $hit['datang_awal_menit'];
            } else {
                $update['status_masuk'] = 'LATE';
                $update['telat_menit'] = $hit['telat_menit'];
                $update['datang_awal_menit'] = 0;
                $calc = $this->calculateProgressivePenalty($hit['telat_menit'], $setting, 'Telat Masuk');
                $masukPoints = -1 * $calc['points'];
                $masukDenda = $calc['denda'];
                $masukKetDenda = $calc['keterangan'];
            }

            // Pulang
            $pulangPoints = 0;
            $pulangDenda = 0;
            $pulangKetDenda = null;

            // $jamPulangTs sudah dinormalkan di atas (zero-date ⇒ baris
            // dilewati); null di sini berarti memang belum/tidak ada pulang.
            if ($jamPulangTs !== null) {
                $jamPulangLimit = $sched['jam_pulang'];
                $thresholdPulang = strtotime($date . ' ' . $jamPulangLimit);
                $pulangTime = strtotime($date . ' ' . $jamPulangTs);

                if ($pulangTime === false || $thresholdPulang === false) {
                    // jadwal tidak terbaca — jangan sampai salah denda
                } elseif ($pulangTime < $thresholdPulang) {
                    $update['status_pulang'] = 'EARLY';
                    $pulangCepatMenit = (int) ceil(($thresholdPulang - $pulangTime) / 60);
                    $update['pulang_cepat_menit'] = $pulangCepatMenit;
                    $update['pulang_akhir_menit'] = 0;
                    $calcOut = $this->calculateProgressivePenalty($pulangCepatMenit, $setting, 'Pulang Awal');
                    $pulangPoints = -1 * $calcOut['points'];
                    $pulangDenda = $calcOut['denda'];
                    $pulangKetDenda = $calcOut['keterangan'];
                } else {
                    $update['status_pulang'] = 'ONTIME';
                    $update['pulang_cepat_menit'] = 0;
                    if ($pulangTime > $thresholdPulang) {
                        // Kanonik: lembur dihitung di atas 60 menit pertama (service calcStatusPulang).
                        $diffMenit = (int) floor(($pulangTime - $thresholdPulang) / 60);
                        $update['pulang_akhir_menit'] = ($diffMenit >= 60) ? ($diffMenit - 60) : 0;
                    }
                }
            }

            $rawDenda = (float) ($masukDenda + $pulangDenda);
            // Tegakkan cap denda bulanan seperti jalur tulis lainnya (dengan lock).
            $capped = $penaltySvc->applyMonthlyCap((int) $r->user_id, $date, $rawDenda, $setting, (int) $r->id, true);
            $effectiveDenda = $capped['total'];
            if ($capped['capped']) {
                $masukKetDenda = trim(($masukKetDenda ?? '') . ' (dibatasi cap bulanan Rp ' . number_format($effectiveDenda, 0, ',', '.') . ')');
            }

            $update['points'] = $this->terapkanPenaltiLupaPulang(
                $masukPoints + $pulangPoints,
                $r,
                $jamPulangTs,
                $adaKolomPenalti,
                $update
            );
            $kets = array_values(array_filter([$masukKetDenda, $pulangKetDenda]));
            $update['keterangan_denda'] = $kets ? implode('; ', $kets) : null;

            // Jangan turunkan denda di bawah yang sudah diterima bendahara.
            // Sisa denda dihitung live dari absensi_harian.denda dikurangi
            // total pembayaran; menurunkannya setelah bulan itu lunas membuat
            // sisa jadi negatif — kredit hantu yang bisa dipakai melunasi
            // bulan lain. Status/jam/poin tetap dihitung ulang seperti biasa.
            $dibayarBulan = $this->totalDibayarBulan(
                (int) $r->user_id,
                (int) date('n', strtotime($date)),
                (int) date('Y', strtotime($date)),
                $dibayarCache
            );
            if ($dibayarBulan > 0 && $effectiveDenda < (float) ($r->denda ?? 0)) {
                unset($update['denda'], $update['keterangan_denda']);
                $dipertahankan++;
            } else {
                $update['denda'] = $effectiveDenda;
            }

            $db->table('absensi_harian')->where('id', $r->id)->update($update);
            $updatedCount++;
        }
        $db->transCommit();

        // Hitung ulang menimpa points & denda baris yang sudah ada. Tanpa
        // jejak, selisih angka bulan lampau tidak bisa direkonstruksi lagi.
        try {
            (new AuditLogModel())->log('absensi_harian', null, 'RECALCULATE', null, $this->getUserId(),
                null,
                ['tanggal' => $date, 'diperiksa' => count($rows), 'diperbarui' => $updatedCount,
                 'denda_dipertahankan' => $dipertahankan, 'lewati_jam_rusak' => $lewatiRusak],
                'Hitung Ulang Presensi ' . $date);
        } catch (\Throwable $ex) {}
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', '[PresensiAdmin::doRecalculateDate] ' . $e->getMessage());
            return [
                'date' => $date,
                'total_checked' => count($rows),
                'updated' => $updatedCount,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'date' => $date,
            'total_checked' => count($rows),
            'updated' => $updatedCount,
            'denda_dipertahankan' => $dipertahankan,
            'lewati_rusak' => $lewatiRusak
        ];
    }

    /**
     * Total yang sudah dibayarkan (status aktif) untuk satu user satu bulan.
     * Dipakai hitung ulang agar denda tidak pernah turun di bawah uang yang
     * sudah diterima. $cache dipakai bersama sepanjang satu tanggal karena
     * bulan/tahun-nya tetap dan user-nya berulang.
     */
    /**
     * Pengajuan ini milik admin/panitia yang sedang login? Approver tidak boleh
     * memproses permintaannya sendiri: keputusan itulah yang menulis baris
     * absensi atas namanya (IJIN/SAKIT/CUTI/DINAS, atau ALPHA + denda saat
     * ditolak) tanpa tinjauan orang lain.
     */
    private function adalahPemohon($row): bool
    {
        $approver = (int) (session()->get('user_id') ?? 0);

        return $approver > 0 && (int) ($row->user_id ?? 0) === $approver;
    }

    private function totalDibayarBulan(int $userId, int $bulan, int $tahun, array &$cache): float
    {
        $key = $userId . '|' . $bulan . '|' . $tahun;
        if (isset($cache[$key])) {
            return (float) $cache[$key];
        }

        $total = 0.0;
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('absensi_pembayaran_denda')) {
                $rows = $db->table('absensi_pembayaran_denda')
                    ->select('nominal_bayar, total_denda')
                    ->where('user_id', $userId)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->where('status !=', 'batal')
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $nominal = (float) ($r->nominal_bayar ?? 0);
                    if ($nominal <= 0) {
                        $nominal = (float) ($r->total_denda ?? 0);
                    }
                    $total += $nominal;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[totalDibayarBulan] ' . $e->getMessage());
        }

        return $cache[$key] = $total;
    }

    /**
     * Test Notification (Telegram / WhatsApp)
     */
    public function testNotification()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $channel = $this->request->getPost('channel');
        $notifService = new \App\Services\NotificationService();

        $message = "✅ <b>Test Notifikasi</b>\n"
                 . "Sistem presensi berjalan dengan baik.\n"
                 . "Waktu: " . date('d/m/Y H:i:s') . "\n"
                 . "Pesan ini adalah uji coba.";

        $success = false;
        $detail = '';
        if ($channel === 'telegram') {
            // Dukung uji nilai yang sedang diketik (belum disimpan): override kredensial
            // khusus untuk pengiriman tes ini tanpa mengubah konfigurasi tersimpan.
            $tryToken = trim((string) ($this->request->getPost('telegram_bot_token') ?? ''));
            $tryChat = trim((string) ($this->request->getPost('telegram_chat_id') ?? ''));
            if ($tryToken !== '' || $tryChat !== '') {
                $ref = new \ReflectionClass($notifService);
                $prop = $ref->getProperty('setting');
                $prop->setAccessible(true);
                $cfg = $prop->getValue($notifService) ?? (object) [];
                if ($tryToken !== '') {
                    $cfg->telegram_bot_token = $tryToken;
                }
                if ($tryChat !== '') {
                    $cfg->telegram_chat_id = $tryChat;
                }
                $prop->setValue($notifService, $cfg);
            }
            $success = $notifService->sendTelegram($message);
            if (!$success) {
                $detail = $notifService->lastError !== '' ? $notifService->lastError : 'Periksa konfigurasi.';
            }
        } elseif ($channel === 'whatsapp') {
            // Uji nilai yang sedang diketik (override config tersimpan),
            // sama seperti Telegram di atas. Tanpa ini, test WhatsApp hanya
            // bisa menguji konfigurasi yang SUDAH disimpan.
            $cfg = new \ReflectionClass($notifService);
            $prop = $cfg->getProperty('setting');
            $prop->setAccessible(true);
            $settings = $prop->getValue($notifService) ?? (object) [];
            foreach ([
                'wa_gateway_mode', 'wa_base_url', 'wa_api_key',
                'wa_session_name', 'wa_target_number',
                'wa_gateway_url', 'wa_token',
            ] as $k) {
                $v = $this->request->getPost($k);
                if ($v !== null && trim((string) $v) !== '') {
                    $settings->$k = trim((string) $v);
                }
            }
            $prop->setValue($notifService, $settings);

            // Id sesi OpenWA di-cache 60 detik berdasarkan gateway yang
            // sedang aktif. Kalau test memakai kredensial lain dari yang
            // tersimpan, cache itu bisa menunjuk sesi gateway lama.
            if (($settings->wa_gateway_mode ?? '') === 'openwa') {
                (new \App\Services\OpenWAService())->flushCache();
            }

            $success = $notifService->sendWhatsApp($message);
            if (! $success) {
                $detail = $notifService->lastError !== '' ? $notifService->lastError : 'Periksa konfigurasi gateway.';
            }
        }

        if ($success) {
            return $this->respond(true, "Notifikasi {$channel} berhasil dikirim");
        }

        return $this->respond(false, $detail !== '' ? $detail : "Gagal mengirim {$channel}. Periksa konfigurasi.");
    }

    /**
 * * Daftar akun WhatsApp guru + status gateway OpenWA.
 *
 * Nomor WA Unlike chat Telegram, hanya berstatus "terverifikasi" yang
 * benar-benar menerima kiriman (lihat UserNotifikasiModel::getVerifiedWaNumber).
 * Halaman ini tempat admin mengonfirmasi nomor yang diketik guru.
 */
public function waLinks()
{
    if (! $this->isAdmin()) {
        return redirect()->to('auth')->with('error', 'Akses ditolak');
    }

    $linked = [];
    $verifiedCount = 0;
    try {
        $model = new \App\Models\UserNotifikasiModel();
        $linked = $model->getAllWaLinked();
        $verifiedCount = $model->countVerifiedWa();
    } catch (\Throwable $e) {
        log_message('error', 'waLinks gagal: ' . $e->getMessage());
        $linked = [];
    }

    // Status gateway untuk diagnosis "WA diam".
    $status = ['ok' => false, 'sessions' => [], 'error' => 'Belum diperiksa.'];
    try {
        $status = (new \App\Services\OpenWAService())->getStatus();
    } catch (\Throwable $e) {
        $status = ['ok' => false, 'sessions' => [], 'error' => $e->getMessage()];
    }

    $setting = $this->dashboardModel->getSetting();
    if (! $setting) {
        $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
    }

    return view('admin/presensi/whatsapp', [
        'title'         => 'Tautan WhatsApp Guru',
        'judul'         => 'Tautan WhatsApp Guru',
        'setting'       => $setting,
        'tp_active'     => $this->dashboardModel->getTahunActive(),
        'smt_active'    => $this->dashboardModel->getSemesterActive(),
        'user'          => $this->getUser(),
        'linked'        => $linked,
        'verifiedCount' => $verifiedCount,
        'status'        => $status,
    ]);
}

/**
 * Konfirmasi / batalkan nomor WhatsApp seorang guru.
 * POST admin/presensi/wa/verify {user_id, verify: 1|0}
 *
 * Konfirmasi admin adalah syarat aktif: OpenWA tidak bisa membedakan
 * nomor aktif dari nomor salah ketik, jadi satu digit keliru akan
 * membocorkan data absensi ke orang tak dikenal.
 */
public function waVerifyUser()
{
    if (! $this->isAdmin()) {
        return $this->respond(false, 'Akses ditolak', null, null, 403);
    }

    $userId = (int) ($this->request->getPost('user_id') ?? 0);
    $verify = (int) ($this->request->getPost('verify') ?? 1) === 1;

    if ($userId <= 0) {
        return $this->respond(false, 'User tidak valid.');
    }

    try {
        $model = new \App\Models\UserNotifikasiModel();
        if ($model->getWaNumber($userId) === '') {
            return $this->respond(false, 'Nomor WhatsApp guru ini belum diisi.');
        }

        $ok = $verify
            ? $model->verifyWaNumber($userId)
            : $model->unbindWa($userId);

        if (! $ok) {
            return $this->respond(false, 'Gagal menyimpan status.');
        }

        // Endpoint ini mengubah kanal-notifikasi seorang guru, jadi
        // jejaknya harus ada — sama seperti aksi persetujuan izin/koreksi
        // di controller yang sama. Tanpa ini, kalau seorang guru
        // keberatan karena notifikasi teguran tidak sampai, tidak ada
        // bukti siapa yang memutus kanalnya dan kapan.
        $this->auditTautanNotifikasi(
            $verify ? 'verifikasi' : 'nonaktifkan',
            $userId,
            (string) $model->getWaNumber($userId)
        );

        return $this->respond(true, $verify ? 'Nomor dikonfirmasi dan aktif.' : 'Tautan dinonaktifkan.');
} catch (\Throwable $e) {
        log_message('error', '[PresensiAdmin::waVerifyUser] ' . $e->getMessage());
        return $this->respond(false, 'Gagal mengubah status nomor WhatsApp — periksa log aplikasi.', null, null, 500);
    }
    }

    /**
     * Catat perubahan kanal notifikasi ke audit_logs.
     *
     * Pemanggil WA sudah pasti admin (isAdmin() dicek di atas), jadi
     * actor_id diambil dari session.
     */
    private function auditTautanNotifikasi(string $aksi, int $userId, string $nomor): void
    {
        try {
            if (! class_exists(\App\Models\AuditLogModel::class)) {
                return;
            }
            $model = new \App\Models\AuditLogModel();
            if (! method_exists($model, 'log')) {
                return;
            }
            $model->log(
                'user_notifikasi',
                $userId,
                'Admin ' . $aksi . ' kanal WhatsApp notifikasi',
                $userId,
                (int) session()->get('user_id'),
                null,
                null,
                'user_id=' . $userId . ' nomor=' . $nomor
            );
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menulis audit tautan notifikasi: ' . $e->getMessage());
        }
    }

/**
 * Papan kondisi notifikasi.
 *
 * Semua kegagalan notifikasi (cron maupun guru-terawal) selama ini hanya
 * ada di file log di server — tidak ada layar yang menunjukkannya. Admin
 * baru tahu masalahnya saat rekap tidak masuk, atau tidak sama sekali.
 *
 * Halaman ini menyatukan tiga sumber yang sudah ada:
 *   - cron_notifikasi_log    : hasil tiap pengiriman cron
 *   - absensi_pengumuman_harian : hasil DM guru-terawal (tabel ini tadinya
 *     hanya ditulis, tidak pernah dibaca)
 *   - status gateway         : OpenWA + webhook Telegram, live
 */
public function notifikasiStatus()
{
    if (! $this->isAdmin()) {
        return redirect()->to('auth')->with('error', 'Akses ditolak');
    }

    $db = \Config\Database::connect();

    // ── pengiriman cron ─────────────────────────────────────────────
    $cron = [];
    $cronGagal = 0;
    if ($db->tableExists('cron_notifikasi_log')) {
        $cron = $db->table('cron_notifikasi_log')
            ->orderBy('updated_at', 'DESC')
            ->limit(30)
            ->get()->getResult();
        $cronGagal = (int) $db->table('cron_notifikasi_log')
            ->where('terkirim', 0)->countAllResults();
    }

    // ── DM guru-terawal ─────────────────────────────────────────────
    $dmGagal = [];
    if ($db->tableExists('absensi_pengumuman_harian')) {
        $dmGagal = $db->table('absensi_pengumuman_harian')
            ->select('tgl, nama_guru, dm_terkirim, grup_terkirim, catatan')
            ->where('dm_terkirim', 0)
            ->orderBy('tgl', 'DESC')
            ->limit(15)
            ->get()->getResult();
    }

    // ── status kanal ────────────────────────────────────────────────
    $wa = ['ok' => false, 'error' => 'Belum diperiksa.'];
    try {
        $wa = (new \App\Services\OpenWAService())->getStatus();
    } catch (\Throwable $e) {
        $wa = ['ok' => false, 'error' => $e->getMessage()];
    }

    $tg = ['ok' => false, 'error' => 'Belum diperiksa.'];
    try {
        $tg = (new \App\Services\NotificationService())->getWebhookInfo();
    } catch (\Throwable $e) {
        $tg = ['ok' => false, 'error' => $e->getMessage()];
    }

    $notif = new \App\Services\NotificationService();

    // ── state channel per jenis ─────────────────────────────────────
    // Supaya admin bisa membedakan "cron GAGAL (gateway)" dari "cron
    // dilewati karena channel dimatikan di notif-config". Tanpa ini,
    // baris Skip di cron.log tidak bisa dikorelasikan dari halaman ini.
    $chModel = new \App\Models\NotifChannelConfigModel();

    $setting = $this->dashboardModel->getSetting();
    if (! $setting) {
        $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
    }

    return view('admin/presensi/notifikasi-status', [
        'title'      => 'Status Notifikasi',
        'judul'      => 'Status Notifikasi',
        'setting'    => $setting,
        'tp_active'  => $this->dashboardModel->getTahunActive(),
        'smt_active' => $this->dashboardModel->getSemesterActive(),
        'user'       => $this->getUser(),
        'cron'       => $cron,
        'cronGagal'  => $cronGagal,
        'dmGagal'    => $dmGagal,
        'wa'         => $wa,
        'tg'         => $tg,
        'waMode'     => $notif->waMode(),
        'chConfig'   => $chModel->getConfig(),
        'chJenis'    => \App\Models\NotifChannelConfigModel::JENIS_LIST,
        'chLabels'   => \App\Models\NotifChannelConfigModel::CHANNEL_LABELS,
    ]);
}

/**
 * Daftar akun Telegram guru yang tertaut (notifikasi personal).
 */
    public function telegramLinks()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $linked = [];
        try {
            $linked = (new \App\Models\UserNotifikasiModel())->getAllLinked();
        } catch (\Throwable $e) {
            $linked = [];
        }

        // Total guru aktif sebagai pembanding
        $totalGuru = 0;
        try {
            $totalGuru = $db->table('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where('u.active', 1)
                ->countAllResults();
        } catch (\Throwable $e) {
        }

        $setting = $this->dashboardModel->getSetting();
        if (!$setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        // Status webhook Telegram untuk diagnosis "bot diam"
        $wh = ['ok' => false, 'error' => 'Belum diperiksa.'];
        $whExpected = '';
        $whSetUrl = '';
        try {
            $notifSvc = new \App\Services\NotificationService();
            $wh = $notifSvc->getWebhookInfo();
            $absenSet = (new \App\Models\AttendanceSettingModel())->first();
            $botToken = trim((string) ($absenSet->telegram_bot_token ?? ''));
            if ($botToken !== '') {
                $secret = \App\Controllers\Api\TelegramBot::webhookSecret($botToken);
                $whExpected = rtrim(base_url(), '/') . '/api/telegram/webhook?secret=' . $secret;
                $whSetUrl = 'https://api.telegram.org/bot' . $botToken . '/setWebhook?url=' . urlencode($whExpected);
            }
        } catch (\Throwable $e) {
            $wh = ['ok' => false, 'error' => $e->getMessage()];
        }

        return view('admin/presensi/telegram', [
            'title'      => 'Tautan Telegram Guru',
            'judul'      => 'Tautan Telegram Guru',
            'setting'    => $setting,
            'tp_active'  => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'user'       => $this->getUser(),
            'linked'     => $linked,
            'totalGuru'  => $totalGuru,
            'wh'         => $wh,
            'whExpected' => $whExpected,
            'whSetUrl'   => $whSetUrl,
        ]);
    }

    /**
     * Lepas tautan Telegram seorang guru (admin).
     */
    public function telegramUnlinkUser()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->respond(false, 'User tidak valid');
        }
        try {
            $model = new \App\Models\UserNotifikasiModel();
            $row = $model->getByUserId($userId);

            // Dulu endpoint ini selalu balas "Tautan dilepas" walau tidak
            // ada yang tertaut, jadi admin tidak bisa membedakan "saya
            // mencabut kanal aktif" dari "tidak ada kanal".
            if (! $row || empty($row->telegram_chat_id)) {
                return $this->respond(true, 'Tidak ada tautan Telegram pada akun ini.');
            }

            $chatId = (string) $row->telegram_chat_id;
            $model->unbindByChatId($chatId);

            $this->auditTautanNotifikasi('melepas', $userId, $chatId);

            return $this->respond(true, 'Tautan dilepas.');
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::telegramUnlinkUser] ' . $e->getMessage());
            return $this->respond(false, 'Gagal melepas tautan — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Kirim pesan tes ke chat personal guru (admin).
     */
    public function telegramTestUser()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->respond(false, 'User tidak valid');
        }
        try {
            $ok = (new \App\Services\NotificationService())->notifyUser(
                $userId,
                '✅ <b>TES NOTIFIKASI</b>\nAkun Telegram Anda tertaut dengan benar.\nWaktu: ' . date('d/m/Y H:i'),
                '',
                false
            );
            return $ok
                ? $this->respond(true, 'Pesan tes terkirim.')
                : $this->respond(false, 'Gagal terkirim. Pastikan akun masih tertaut.');
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::telegramTestUser] ' . $e->getMessage());
            return $this->respond(false, 'Gagal mengirim pesan tes — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * QR Code Event List
     */
    public function qrList()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiQrTable();
        $this->patchAbsensiQrLogTable();

        $events = $db->table('absensi_qr')
            ->orderBy('tanggal', 'DESC')
            ->orderBy('jam_mulai', 'DESC')
            ->get()->getResult();

        // Hitung peserta (scan masuk) per event dalam 1 query (hindari N+1)
        $pesertaCounts = [];
        if ($db->tableExists('absensi_qr_log') && !empty($events)) {
            try {
                $countBuilder = $db->table('absensi_qr_log')
                    ->select('qr_id, COUNT(*) AS total');
                if ($db->fieldExists('jenis', 'absensi_qr_log')) {
                    $countBuilder->where('jenis', 'masuk');
                }
                $rows = $countBuilder
                    ->groupBy('qr_id')
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $pesertaCounts[(int) $r->qr_id] = (int) $r->total;
                }
            } catch (\Throwable $e) {
                log_message('error', '[qrList] count peserta: ' . $e->getMessage());
            }
        }

        $appSetting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        return view('admin/presensi/qr_list', [
            'title' => 'QR Code Presensi',
            'judul' => 'QR Code Presensi',
            'setting' => $appSetting ?: (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'],
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'events' => $events,
            'pesertaCounts' => $pesertaCounts,
            'today' => date('Y-m-d'),
        ]);
    }

    /**
     * Hapus event QR. Ditolak bila sudah ada riwayat scan (lindungi audit).
     */
    public function qrDelete()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->respond(false, 'ID event tidak valid');
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiQrTable();

        $event = $db->table('absensi_qr')->where('id', $id)->get()->getRow();
        if (!$event) {
            return $this->respond(false, 'Event tidak ditemukan');
        }

        if ($db->tableExists('absensi_qr_log')) {
            $used = $db->table('absensi_qr_log')->where('qr_id', $id)->countAllResults();
            if ($used > 0) {
                return $this->respond(false, "Event tidak dapat dihapus karena sudah memiliki {$used} riwayat scan. Nonaktifkan saja agar tak dipakai lagi.");
            }
        }

        $db->table('absensi_qr')->where('id', $id)->delete();

        return $this->respond(true, 'Event "' . ($event->nama_acara ?? '') . '" dihapus');
    }

    /**
     * Generate QR Code Event
     */
    public function qrGenerate()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiQrTable();

        $namaAcara = trim((string) ($this->request->getPost('nama_acara') ?? ''));
        $tanggal = trim((string) ($this->request->getPost('tanggal') ?: date('Y-m-d')));
        $jamMulai = trim((string) ($this->request->getPost('jam_mulai') ?: '07:00'));
        $jamSelesai = trim((string) ($this->request->getPost('jam_selesai') ?: '16:00'));
        $lokasi = mb_substr(trim((string) ($this->request->getPost('lokasi') ?? '')), 0, 255);

        if (mb_strlen($namaAcara) < 3 || mb_strlen($namaAcara) > 255) {
            return $this->respond(false, 'Nama acara 3–255 karakter');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return $this->respond(false, 'Format tanggal tidak valid');
        }
        $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if (!preg_match($timeRe, $jamMulai) || !preg_match($timeRe, $jamSelesai)) {
            return $this->respond(false, 'Format jam tidak valid (HH:MM)');
        }
        if ($jamSelesai <= $jamMulai) {
            return $this->respond(false, 'Jam selesai harus setelah jam mulai');
        }

        // Mode rotating (default): token 6 digit berganti tiap interval — QR wajib
        // tampil di layar & di-refresh. Mode static: token tetap untuk QR cetak/poster.
        $mode = $this->request->getPost('mode') === 'static' ? 'static' : 'rotating';
        $interval = (int) ($this->request->getPost('interval_detik') ?: 60);
        $interval = \App\Libraries\QrToken::normalizeInterval($interval);

        if ($mode === 'rotating') {
            $secret = \App\Libraries\QrToken::generateSecret();
            // Identifier internal; TIDAK PERNAH diterima sebagai token check-in.
            $token = 'EVT-' . bin2hex(random_bytes(8));
            $displayToken = \App\Libraries\QrToken::current($secret, $interval);
        } else {
            $secret = null;
            $token = bin2hex(random_bytes(24));
            $displayToken = $token;
        }

        $db->table('absensi_qr')->insert([
            'nama_acara' => $namaAcara,
            'token' => $token,
            'tanggal' => $tanggal,
            'jam_mulai' => $jamMulai . ':00',
            'jam_selesai' => $jamSelesai . ':00',
            'lokasi' => $lokasi,
            'secret' => $secret,
            'interval_detik' => $interval,
            'mode' => $mode,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $id = $db->insertID();
        $url = site_url("qr/{$displayToken}");

        return $this->respond(true, 'QR Code berhasil dibuat', [
            'id' => $id,
            'token' => $displayToken,
            'url' => $url,
            'mode' => $mode,
            'refresh_ms' => $mode === 'rotating' ? $interval * 1000 : 0,
            'nama_acara' => $namaAcara,
            'tanggal' => $tanggal
        ]);
    }

    /**
     * Toggle QR Event Active Status
     */
    public function qrToggle()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $active = $this->request->getPost('active') ? 1 : 0;

        if ($id <= 0) {
            return $this->respond(false, 'ID event tidak valid');
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiQrTable();

        if (!$db->table('absensi_qr')->where('id', $id)->countAllResults()) {
            return $this->respond(false, 'Event tidak ditemukan');
        }

        $db->table('absensi_qr')->where('id', $id)->update([
            'active' => $active,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->respond(true, $active ? 'Event diaktifkan' : 'Event dinonaktifkan');
    }

    /**
     * Token QR kini untuk ditampilkan di layar (polling oleh halaman admin).
     * Secret tidak pernah dikembalikan.
     */
    public function qrCurrentToken($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiQrTable();

        $event = $db->table('absensi_qr')->where('id', $id)->get()->getRow();
        if (!$event) {
            return $this->respond(false, 'Event tidak ditemukan', null, null, 404);
        }

        $token = \App\Libraries\QrToken::displayToken($event);
        $mode = $event->mode ?? (!empty($event->secret) ? 'rotating' : 'static');
        $interval = \App\Libraries\QrToken::normalizeInterval((int) ($event->interval_detik ?? 60));

        return $this->respond(true, 'Token kini', [
            'id' => (int) $event->id,
            'token' => $token,
            'url' => site_url('qr/' . $token),
            'mode' => $mode,
            'refresh_ms' => $mode === 'rotating' ? $interval * 1000 : 0,
            'expires_in' => $mode === 'rotating' ? \App\Libraries\QrToken::secondsRemaining($interval) : null,
            'active' => (int) $event->active,
        ]);
    }

    /**
     * QR Event Recap (who scanned)
     */
    public function qrRekap($qrId)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiQrLogTable();

        $event = $db->table('absensi_qr')->where('id', $qrId)->get()->getRow();
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan');
        }

        $logs = $db->table('absensi_qr_log l')
            ->select('l.*, mg.nama_guru, mg.nip')
            ->join('master_guru mg', 'mg.id_guru = l.id_guru', 'left')
            ->where('l.qr_id', $qrId)
            ->orderBy('l.scanned_at', 'ASC')
            ->get()->getResult();

        $pesertaIds = [];
        $scanPulang = 0;
        foreach ($logs as $log) {
            $jenis = (string) ($log->jenis ?? 'masuk');
            if ($jenis === 'pulang') {
                $scanPulang++;
            } else {
                $pesertaIds[(int) $log->user_id] = true;
            }
        }

        $appSetting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        return view('admin/presensi/qr_rekap', [
            'title' => 'Rekap QR',
            'judul' => 'Rekap QR: ' . $event->nama_acara,
            'setting' => $appSetting ?: (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'],
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'event' => $event,
            'logs' => $logs,
            'peserta_count' => count($pesertaIds),
            'scan_pulang_count' => $scanPulang,
        ]);
    }

    private function patchAbsensiQrTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        if (!$db->tableExists('absensi_qr')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama_acara' => ['type' => 'VARCHAR', 'constraint' => 255],
                'token' => ['type' => 'VARCHAR', 'constraint' => 64, 'unique' => true],
                'tanggal' => ['type' => 'DATE'],
                'jam_mulai' => ['type' => 'TIME'],
                'jam_selesai' => ['type' => 'TIME'],
                'lokasi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'secret' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'interval_detik' => ['type' => 'INT', 'constraint' => 11, 'default' => 60],
                'mode' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'rotating'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addKey('token');
            $forge->createTable('absensi_qr', true);
            return;
        }
        // Migrasi skema lama: nama_event → nama_acara, is_active → active
        if (!$db->fieldExists('nama_acara', 'absensi_qr') && $db->fieldExists('nama_event', 'absensi_qr')) {
            try {
                $forge->modifyColumn('absensi_qr', [
                    'nama_event' => ['name' => 'nama_acara', 'type' => 'VARCHAR', 'constraint' => 255],
                ]);
            } catch (\Throwable $e) {
                log_message('error', '[patchAbsensiQrTable] rename nama_event: ' . $e->getMessage());
            }
        }
        if (!$db->fieldExists('active', 'absensi_qr') && $db->fieldExists('is_active', 'absensi_qr')) {
            try {
                $forge->modifyColumn('absensi_qr', [
                    'is_active' => ['name' => 'active', 'type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                ]);
            } catch (\Throwable $e) {
                log_message('error', '[patchAbsensiQrTable] rename is_active: ' . $e->getMessage());
            }
        }
        if (!$db->fieldExists('lokasi', 'absensi_qr')) {
            try {
                $forge->addColumn('absensi_qr', ['lokasi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]]);
            } catch (\Throwable $e) {
                log_message('error', '[patchAbsensiQrTable] add lokasi: ' . $e->getMessage());
            }
        }
        // Kolom TOTP untuk tabel lama
        if (!$db->fieldExists('secret', 'absensi_qr')) {
            $forge->addColumn('absensi_qr', ['secret' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true]]);
        }
        if (!$db->fieldExists('interval_detik', 'absensi_qr')) {
            $forge->addColumn('absensi_qr', ['interval_detik' => ['type' => 'INT', 'constraint' => 11, 'default' => 60]]);
        }
        if (!$db->fieldExists('mode', 'absensi_qr')) {
            $forge->addColumn('absensi_qr', ['mode' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'rotating']]);
            try {
                $db->query("UPDATE absensi_qr SET mode = 'static' WHERE secret IS NULL");
            } catch (\Throwable $e) {
            }
        }
    }

    private function patchAbsensiQrLogTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        if (!$db->tableExists('absensi_qr_log')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'qr_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11],
                'id_guru' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'jenis' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'masuk', 'null' => false],
                'scanned_at' => ['type' => 'DATETIME'],
                'lat' => ['type' => 'DOUBLE', 'null' => true],
                'lng' => ['type' => 'DOUBLE', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addKey('qr_id');
            $forge->addKey('user_id');
            $forge->addUniqueKey(['qr_id', 'user_id', 'jenis'], 'uq_qr_user_jenis');
            $forge->createTable('absensi_qr_log', true);
            return;
        }

        if (!$db->fieldExists('jenis', 'absensi_qr_log')) {
            try {
                $forge->addColumn('absensi_qr_log', [
                    'jenis' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'masuk', 'null' => false],
                ]);
            } catch (\Throwable $e) {
                log_message('error', '[patchAbsensiQrLogTable] add jenis: ' . $e->getMessage());
            }
        }

        // UNIQUE(qr_id,user_id) lama harus diganti agar scan pulang bisa tercatat.
        try {
            $target = ['jenis', 'qr_id', 'user_id'];
            sort($target);
            $hasTarget = false;
            $indexes = [];
            foreach ($db->query('SHOW INDEX FROM `absensi_qr_log`')->getResult() as $row) {
                if ((int) ($row->Non_unique ?? 1) !== 0) {
                    continue;
                }
                $indexes[$row->Key_name][(int) $row->Seq_in_index] = $row->Column_name;
            }
            foreach ($indexes as $name => $cols) {
                ksort($cols);
                $cols = array_values($cols);
                $sorted = $cols;
                sort($sorted);
                if ($sorted === $target) {
                    $hasTarget = true;
                } elseif ($sorted === ['qr_id', 'user_id']) {
                    $db->query('ALTER TABLE `absensi_qr_log` DROP INDEX `' . str_replace('`', '``', $name) . '`');
                }
            }
            if (!$hasTarget) {
                $db->query('ALTER TABLE `absensi_qr_log` ADD UNIQUE KEY `uq_qr_user_jenis` (`qr_id`, `user_id`, `jenis`)');
            }
        } catch (\Throwable $e) {
            log_message('error', '[patchAbsensiQrLogTable] unique jenis: ' . $e->getMessage());
        }
    }

    /**
     * Location Management Page
     */
    public function locations()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $this->patchAbsensiLokasiTable();

        $locationModel = new \App\Models\AttendanceLocationModel();
        $locations = $locationModel->findAll();

        // Get setting & active year/semester
        $appSetting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$appSetting)
            $appSetting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/locations', [
            'title' => 'Data Lokasi Kantor',
            'judul' => 'Lokasi Kantor',
            'setting' => $appSetting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'locations' => $locations
        ]);
    }

    /**
     * Save Location
     */
    public function saveLocation()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiLokasiTable();
        $locationModel = new \App\Models\AttendanceLocationModel();

        $id          = $this->request->getPost('id');
        $namaLokasi  = trim($this->request->getPost('nama_lokasi') ?? '');
        $lat         = (float) $this->request->getPost('lat');
        $lng         = (float) $this->request->getPost('lng');
        $radius      = (int)   $this->request->getPost('radius_meter');
        $keterangan  = trim($this->request->getPost('keterangan') ?? '');

        // ── Validasi Input ──────────────────────────────────────────
        if (empty($namaLokasi)) {
            return $this->respond(false, 'Nama lokasi wajib diisi');
        }
        if (strlen($namaLokasi) > 100) {
            return $this->respond(false, 'Nama lokasi maksimal 100 karakter');
        }
        if ($lat < -90 || $lat > 90 || ($lat == 0 && $lng == 0)) {
            return $this->respond(false, 'Latitude tidak valid (harus antara -90 dan 90, tidak boleh 0,0)');
        }
        if ($lng < -180 || $lng > 180) {
            return $this->respond(false, 'Longitude tidak valid (harus antara -180 dan 180)');
        }
        if ($radius < 10 || $radius > 5000) {
            return $this->respond(false, 'Radius harus antara 10 dan 5000 meter');
        }
        // ── End Validasi ────────────────────────────────────────────

        $data = [
            'nama_lokasi'  => $namaLokasi,
            'lat'          => $lat,
            'lng'          => $lng,
            'radius_meter' => $radius,
            'keterangan'   => $keterangan,
            'is_utama'     => 0,   // diurus terpisah lewat setUtama() (hanya satu yang boleh utama)
        ];

        if ($id) {
            $locationModel->update((int) $id, $data);
            $msg = 'Lokasi berhasil diperbarui';
        } else {
            $locationModel->insert($data);
            $msg = 'Lokasi berhasil ditambahkan';
        }

        // ── Lokasi utama ────────────────────────────────────────────
        // Hanya boleh satu lokasi berflag is_utama=1. Operator bisa menandai
        // lewat checkbox di form, atau membatalkannya (is_utama=0).
        $isUtama = $this->request->getPost('is_utama');
        $ssoon   = $this->request->getPost('set_utama');   // 1 = jadikan utama, 0 = lepas
        $targetId = (int) ($id ?: ($locationModel->getInsertID() ?: 0));

        if ($ssoon !== null && trim((string) $ssoon) !== '') {
            $locationModel->setUtama(((int) $ssoon) === 1 ? $targetId : 0);
        } elseif ($isUtama !== null && trim((string) $isUtama) !== '') {
            $locationModel->setUtama(((int) $isUtama) === 1 ? $targetId : 0);
        } else {
            $locationModel->ensureAdaUtama();
        }

        $utama = $locationModel->getUtama();
        if ($utama && (int) $utama->id === $targetId && ((int) $ssoon === 1 || (int) $isUtama === 1)) {
            $msg .= ' — ditandai sebagai lokasi utama';
        }

        // Row bisa saja sudah hilang (id kiriman lama/rusak) — first() null
        // akan fatal di produksi (DBDebug melempar), jadi aman-kan dulu.
        $rowTersimpan = $targetId > 0 ? $locationModel->first($targetId, true) : null;

        return $this->respond(true, $msg, [
            'is_utama' => $rowTersimpan ? (int) $rowTersimpan->is_utama : 0,
        ]);
    }

    /**
     * Tandai / lepas lokasi utama dari daftar (tombol bintang di kartu).
     * POST admin/presensi/lokasi/utama {id, aktif: 1|0}
     */
    public function setLocationUtama()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiLokasiTable();
        $locationModel = new \App\Models\AttendanceLocationModel();

        $id    = (int) $this->request->getPost('id');
        $aktif = (int) $this->request->getPost('aktif');

        if ($id <= 0 || !$locationModel->first($id, true)) {
            return $this->respond(false, 'Lokasi tidak ditemukan');
        }

        $utama = $locationModel->setUtama($aktif === 1 ? $id : 0);

        return $this->respond(true, $aktif === 1
            ? 'Lokasi ditandai sebagai utama'
            : 'Penandaan lokasi utama dilepas', [
            'is_utama' => $aktif === 1 ? 1 : 0,
            'utama_id' => $utama ? (int) $utama->id : 0,
        ]);
    }

    /**
     * Delete Location
     */
    public function deleteLocation()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = $this->request->getPost('id');
        $locationModel = new \App\Models\AttendanceLocationModel();

        if ($id) {
            $locationModel->delete($id);
            // Bila yang terhapus adalah lokasi utama, pastikan ada satu lagi
            // yang menjadi acuan koordinat absen manual.
            $locationModel->ensureAdaUtama();
            return $this->respond(true, 'Lokasi berhasil dihapus');
        }

        return $this->respond(false, 'ID tidak valid');
    }

    /**
     * Analytics Dashboard
     */
    public function dashboard()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // 1. Late Trend per Month (Last 12 months)
        $lateTrend = [];
        $hasTelatMenit = $db->fieldExists('telat_menit', 'absensi_harian');

        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            try {
                if ($hasTelatMenit) {
                    $result = $db->table('absensi_harian')
                        ->selectSum('telat_menit', 'total_menit')
                        ->selectCount('id', 'total_late')
                        ->where('status_masuk', 'LATE')
                        ->like('tgl', $month, 'after')
                        ->get()->getRow();
                } else {
                    $result = $db->table('absensi_harian')
                        ->selectCount('id', 'total_late')
                        ->where('status_masuk', 'LATE')
                        ->like('tgl', $month, 'after')
                        ->get()->getRow();
                    $result->total_menit = 0;
                }
            } catch (\Throwable $e) {
                $result = (object) ['total_late' => 0, 'total_menit' => 0];
            }

            $lateTrend[] = [
                'month' => buat_tanggal(date('M Y', strtotime($month . '-01'))),
                'total_late' => (int) ($result->total_late ?? 0),
                'total_menit' => (int) ($result->total_menit ?? 0)
            ];
        }

        // 2. Attendance Comparison per Teacher (This Month)
        $bulanIni = date('Y-m');
        $termHadir = \App\Services\AttendancePolicy::sqlTermHadir('a');
        try {
            $comparison = $db->table('absensi_harian a')
                ->select('a.user_id, u.first_name, u.last_name,
                          ' . $termHadir . ' as total_hadir,
                          SUM(CASE WHEN a.status_masuk = "LATE" THEN 1 ELSE 0 END) as total_telat,
                          SUM(CASE WHEN a.status_masuk = "ONTIME" THEN 1 ELSE 0 END) as total_tepat,
                          SUM(CASE WHEN a.status_masuk LIKE "DINAS%" THEN 1 ELSE 0 END) as total_dinas')
                ->join('users u', 'u.id = a.user_id')
                // Filter di bawah memakai alias g; tanpa join ini query selalu
                // gagal (Unknown column 'g.status_aktif') dan catch mengubahnya
                // jadi array kosong, sehingga grafik perbandingan guru selalu kosong.
                ->join('master_guru g', 'g.id_user = a.user_id', 'left')
                ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
                ->like('a.tgl', $bulanIni, 'after')
                ->groupBy('a.user_id')
                ->orderBy('total_hadir', 'DESC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            $comparison = [];
        }

        // 3. Discipline Ranking (Based on multiple factors)
        // 3. Discipline Ranking (Based on multiple factors)
        try {
            $hasPoints = $db->fieldExists('points', 'absensi_harian');
            
            if ($hasPoints) {
                // New Logic: 10 pts per attendance + (negative points)
                // "per attendance" = benar-benar hadir. Dulu COUNT(a.id), jadi
                // hari ALPHA/IJIN ikut menambah 10 poin: guru paling bolos
                // justru naik di peringkat kedisiplinan.
                $ranking = $db->query("
                    SELECT 
                        a.user_id,
                        u.first_name,
                        u.last_name,
                        {$termHadir} as total_hadir,
                        SUM(CASE WHEN a.status_masuk = 'ONTIME' THEN 1 ELSE 0 END) as ontime,
                        SUM(CASE WHEN a.status_masuk = 'LATE' THEN 1 ELSE 0 END) as late,
                        COALESCE(SUM(a.points), 0) as total_points,
                        (
                            {$termHadir} * 10 + COALESCE(SUM(a.points), 0)
                        ) as score
                    FROM absensi_harian a
                    JOIN users u ON u.id = a.user_id
                    WHERE a.tgl LIKE '{$bulanIni}%' AND u.active = 1
                    GROUP BY a.user_id
                    ORDER BY score DESC
                    LIMIT 20
                ")->getResult();
            } else if ($hasTelatMenit) {
                $skor = \App\Services\AttendancePolicy::sqlExprSkorHarian('a', [
                    'telat_menit' => true, 'pulang_cepat_menit' => $db->fieldExists('pulang_cepat_menit', 'absensi_harian'),
                    'datang_awal_menit' => $db->fieldExists('datang_awal_menit', 'absensi_harian'),
                    'pulang_akhir_menit' => $db->fieldExists('pulang_akhir_menit', 'absensi_harian'),
                ]);
                $ranking = $db->query("
                    SELECT 
                        a.user_id,
                        u.first_name,
                        u.last_name,
                        {$termHadir} as total_hadir,
                        SUM(CASE WHEN a.status_masuk = 'ONTIME' THEN 1 ELSE 0 END) as ontime,
                        SUM(CASE WHEN a.status_masuk = 'LATE' THEN 1 ELSE 0 END) as late,
                        COALESCE(SUM(a.telat_menit), 0) as total_telat_menit,
                        {$skor} as score
                    FROM absensi_harian a
                    JOIN users u ON u.id = a.user_id
                    WHERE a.tgl LIKE '{$bulanIni}%' AND u.active = 1
                    GROUP BY a.user_id
                    ORDER BY score DESC
                    LIMIT 20
                ")->getResult();
            } else {
                $skor = \App\Services\AttendancePolicy::sqlExprSkorHarian('a', [
                    'telat_menit' => false, 'pulang_cepat_menit' => false,
                    'datang_awal_menit' => $db->fieldExists('datang_awal_menit', 'absensi_harian'),
                    'pulang_akhir_menit' => $db->fieldExists('pulang_akhir_menit', 'absensi_harian'),
                ]);
                $ranking = $db->query("
                    SELECT 
                        a.user_id,
                        u.first_name,
                        u.last_name,
                        {$termHadir} as total_hadir,
                        SUM(CASE WHEN a.status_masuk = 'ONTIME' THEN 1 ELSE 0 END) as ontime,
                        SUM(CASE WHEN a.status_masuk = 'LATE' THEN 1 ELSE 0 END) as late,
                        0 as total_telat_menit,
                        {$skor} as score
                    FROM absensi_harian a
                    JOIN users u ON u.id = a.user_id
                    WHERE a.tgl LIKE '{$bulanIni}%'
                    GROUP BY a.user_id
                    ORDER BY score DESC
                    LIMIT 20
                ")->getResult();
            }
        } catch (\Throwable $e) {
            $ranking = [];
        }

        // 4. Summary Stats
        $pendingKoreksi = 0;
        if ($db->tableExists('absensi_koreksi')) {
            try {
                $pendingKoreksi = $db->table('absensi_koreksi')
                    ->where('status', 'pending')->countAllResults();
            } catch (\Throwable $e) {
                $pendingKoreksi = 0;
            }
        }

        $summary = [
            'total_guru' => $db->table('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)->countAllResults(),
            'hadir_hari_ini' => $db->table('absensi_harian')
                ->where('tgl', date('Y-m-d'))->countAllResults(),
            'telat_bulan_ini' => $db->table('absensi_harian')
                ->where('status_masuk', 'LATE')
                ->like('tgl', $bulanIni, 'after')->countAllResults(),
            'pending_koreksi' => $pendingKoreksi,
            'pending_dinas' => $db->table('absensi_harian')
                ->where('status_masuk', 'DINAS_PENDING')->countAllResults()
        ];

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        // Load absenSet for progressive penalty settings
        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSet = $absenSettingModel->first();

        return view('admin/presensi/dashboard', [
            'title' => 'Dashboard Presensi',
            'judul' => 'Dashboard Presensi',
            'setting' => $setting,
            'absenSetting' => $absenSet,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'lateTrend' => $lateTrend,
            'comparison' => $comparison,
            'ranking' => $ranking,
            'summary' => $summary,
            'tahun' => $tahun
        ]);
    }

    /**
     * Manual Attendance Page
     */
    public function manualAbsen()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();

        // Get all teachers with group guru (group_id = 2)
        $guruBuilder = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nama_guru, g.nip')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->where('ug.group_id', 2)
            ->where('u.active', 1);
        if ($db->fieldExists('status_aktif', 'master_guru')) {
            $guruBuilder->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd();
        }
        $guru = $guruBuilder
            ->orderBy('g.nama_guru', 'ASC')
            ->get()
            ->getResult();

        // Get holidays & jadwal for frontend validation (tanggal => keterangan)
        $holidays    = \App\Services\AttendancePolicy::liburNasional();
        $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

        // Get today's attendance for all teachers (pre-fill)
        $todayAtt = [];
        if ($db->tableExists('absensi_harian')) {
            $attRows = $db->table('absensi_harian')
                ->where('tgl', date('Y-m-d'))
                ->get()->getResult();
            foreach ($attRows as $a) {
                $todayAtt[$a->user_id] = $a;
            }
        }

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/manual_absen', [
            'title' => 'Absen Manual',
            'judul' => 'Absen Manual',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'guru' => $guru,
            'todayAtt' => $todayAtt,
            'holidays' => $holidays,
            'jadwalKerja' => $jadwalKerja,
            'hariLiburMingguan' => \App\Services\AttendancePolicy::hariLiburMingguan()
        ]);
    }

    /**
     * Batch Manual Absen — process all rows at once
     */
            public function batchManualAbsen()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $this->patchAbsensiHarianTable();

        $tanggal = $this->request->getPost('tanggal');
        $entries = $this->request->getPost('entries'); // array of {user_id, jam_masuk, jam_pulang, keterangan, status}

        if (empty($tanggal) || empty($entries)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap']);
        }

        // Validasi tanggal & siapkan transaksi (dulu: tanpa validasi, tanpa transaksi)
        $dt = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format tanggal tidak valid (YYYY-MM-DD)']);
        }
        if ($tanggal > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa mengisi presensi tanggal masa depan']);
        }

        $db = \Config\Database::connect();
        // Whitelist user guru aktif — tolak user_id ngawur
        $validUserIds = [];
        try {
            $rows = $db->table('users u')
                ->select('u.id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where('u.active', 1)
                ->get()->getResult();
            foreach ($rows as $r) $validUserIds[(int) $r->id] = true;
        } catch (\Throwable $e) {
            log_message('error', '[batchManualAbsen] whitelist: ' . $e->getMessage());
        }
        $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        $saved = 0;
        $errors = [];
        $touchedUserIds = [];
        $allowedStatus = ['HADIR', 'IJIN', 'SAKIT', 'CUTI', 'DINAS'];
        $timeRe = '/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';

        // Hari libur: menolak di server, bukan hanya lewat JavaScript.
        // Status DINAS (kegiatan luar) dan IJIN/SAKIT/CUTI tetap boleh
        // dicatat; hanya HADIR yang dihitung sebagai hari kerja.
        $libur = $this->cekHariLiburAbsenManual($tanggal);

        $db->transBegin();
        try {
        foreach ($entries as $idx => $e) {
            $userId = (int) ($e['user_id'] ?? 0);
            $jamMasuk = trim((string) ($e['jam_masuk'] ?? ''));
            $jamPulang = trim((string) ($e['jam_pulang'] ?? ''));
            $keterangan = mb_substr(trim((string) ($e['keterangan'] ?? '')), 0, 500);
            $status = strtoupper(trim((string) ($e['status'] ?? 'HADIR'))); // HADIR, IJIN, SAKIT, CUTI, DINAS

            if (!$userId) continue;
            if (!isset($validUserIds[$userId])) {
                $errors[] = "Baris {$idx}: user tidak dikenal / non-aktif";
                continue;
            }
            if (!in_array($status, $allowedStatus, true)) {
                $errors[] = "Baris {$idx}: status tidak valid";
                continue;
            }
            if ($jamMasuk !== '' && !preg_match($timeRe, $jamMasuk)) {
                $errors[] = "Baris {$idx}: jam masuk tidak valid";
                continue;
            }
            if ($jamPulang !== '' && !preg_match($timeRe, $jamPulang)) {
                $errors[] = "Baris {$idx}: jam pulang tidak valid";
                continue;
            }
            if ($status === 'HADIR' && $jamMasuk !== '' && $jamPulang !== '' && substr($jamPulang, 0, 5) < substr($jamMasuk, 0, 5)) {
                $errors[] = "Baris {$idx}: jam pulang lebih awal dari jam masuk";
                continue;
            }

            // Aturan hari aktif: pada tanggal berjalan (hari ini) hanya boleh
            // absen datang SAJA atau pulang SAJA — tidak boleh keduanya sekaligus.
            // Datang + pulang sekaligus hanya boleh untuk tanggal lampau (H-1 ke belakang).
            if ($status === 'HADIR' && $jamMasuk !== '' && $jamPulang !== '' && $tanggal === date('Y-m-d')) {
                $errors[] = "Baris {$idx}: pada hari aktif hanya boleh absen datang saja atau pulang saja (datang + pulang sekaligus hanya untuk tanggal kemarin atau sebelumnya)";
                continue;
            }

            if (!$userId) continue;
            // HADIR butuh minimal salah satu jam: kosong dua-duanya = baris tidak diisi → lewati.
            // (Dulu: HADIR tanpa jam masuk selalu dilewati sehingga "pulang saja" mustahil.)
            if ($status === 'HADIR' && $jamMasuk === '' && $jamPulang === '') continue;
            if (in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'DINAS'])) $jamMasuk = '';

            // Hari libur → HADIR ditolak. Ein-entry, bukan satu-request,
            // supaya sisa baris (DINAS, izin, sakit) tetap bisa tersimpan.
            if ($libur['libur'] && $status === 'HADIR') {
                $errors[] = "Baris {$idx}: {$tanggal} adalah hari libur ({$libur['alasan']}) — presensi HADIR tidak dapat diisi. Gunakan status DINAS bila memang ada kegiatan luar.";
                continue;
            }

            $existing = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl', $tanggal)
                ->get()->getRow();

            if ($existing) {
                // 1) Status izin/sakit/cuti/dinas → tulis ulang record.
                // 2) Absen pulang-saja (jam masuk kosong, jam pulang diisi) → lengkapi checkout.
                // 3) Record belum punya jam masuk → tulis jam masuk (+ pulang bila diisi).
                // Selain itu (sudah ada jam masuk + kirim jam masuk lagi) → lewati agar data asli terjaga.
                if (in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'DINAS'])) {
                    $update = ['updated_at' => date('Y-m-d H:i:s')];
                    $update['jam_masuk'] = null;
                    $update['jam_pulang'] = null;
                    $update['status_masuk'] = ($status === 'DINAS') ? 'DINAS_APPROVED' : $status;
                    $update['status_pulang'] = ($status === 'DINAS') ? 'DINAS_APPROVED' : null;
                    $update['telat_menit'] = 0;
                    $update['datang_awal_menit'] = 0;
                    $update['pulang_cepat_menit'] = 0;
                    $update['pulang_akhir_menit'] = 0;
                    $update['points'] = $this->getStatusPoints($status, $setting);
                    $update['denda'] = 0;
                    $update['keterangan_denda'] = null;
                    if ($db->fieldExists('keterangan', 'absensi_harian') && !empty($keterangan)) {
                        $update['keterangan'] = $keterangan;
                    }
                    $db->table('absensi_harian')->where('id', $existing->id)->update($update);
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
                } elseif ($status === 'HADIR' && $jamMasuk === '' && $jamPulang !== '') {
                    // Pulang saja: lengkapi checkout pada record yang sudah punya jam masuk.
                    if (empty($existing->jam_masuk)) {
                        $errors[] = "Baris {$idx}: pulang saja membutuhkan data jam masuk terlebih dahulu";
                        continue;
                    }
                    if (preg_match('/([01]\d|2[0-3]):[0-5]\d/', (string) $existing->jam_masuk, $mm)
                        && substr($jamPulang, 0, 5) < $mm[0]) {
                        $errors[] = "Baris {$idx}: jam pulang lebih awal dari jam masuk ({$mm[0]})";
                        continue;
                    }
                    // Idempoten: jam pulang sama → lewati agar penalti tidak terakumulasi ganda
                    $existingPulang = '';
                    if (!empty($existing->jam_pulang) && preg_match('/([01]\d|2[0-3]):[0-5]\d/', (string) $existing->jam_pulang, $m)) {
                        $existingPulang = $m[0];
                    }
                    if ($existingPulang !== '' && substr($jamPulang, 0, 5) === $existingPulang) {
                        if ($db->fieldExists('keterangan', 'absensi_harian') && !empty($keterangan)) {
                            $db->table('absensi_harian')->where('id', $existing->id)->update([
                                'keterangan' => $keterangan,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                        $saved++;
                        continue;
                    }
                    $update = ['updated_at' => date('Y-m-d H:i:s')];
                    $update['jam_pulang'] = $tanggal . ' ' . $jamPulang;
                    $this->recalculateCheckout($db, $setting, $tanggal, $jamPulang, $userId, $update, $existing);
                    if ($db->fieldExists('keterangan', 'absensi_harian') && !empty($keterangan)) {
                        $update['keterangan'] = $keterangan;
                    }
                    $this->applyCapToUpdate($setting, (int) $userId, $tanggal, $update, (int) $existing->id);
                    $db->table('absensi_harian')->where('id', $existing->id)->update($update);
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
                } elseif (empty($existing->jam_masuk)) {
                    // Datang saja / keduanya pada record yang belum punya jam masuk
                    $update = ['updated_at' => date('Y-m-d H:i:s')];
                    if (!empty($jamMasuk)) {
                        $update['jam_masuk'] = $tanggal . ' ' . $jamMasuk;
                        $this->recalculateCheckin($db, $setting, $tanggal, $jamMasuk, $userId, $update);
                    }
                    if (!empty($jamPulang) && !in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'DINAS'])) {
                        $update['jam_pulang'] = $tanggal . ' ' . $jamPulang;
                        $this->recalculateCheckout($db, $setting, $tanggal, $jamPulang, $userId, $update, $existing);
                    }
                    if ($db->fieldExists('keterangan', 'absensi_harian') && !empty($keterangan)) {
                        $update['keterangan'] = $keterangan;
                    }
                    $this->applyCapToUpdate($setting, (int) $userId, $tanggal, $update, (int) $existing->id);
                    $db->table('absensi_harian')->where('id', $existing->id)->update($update);
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
                }
                // else: already has jam_masuk and status is HADIR → skip to preserve existing data
            } else {
                $data = $this->buildCheckinData($db, $setting, $userId, $tanggal, $jamMasuk, $keterangan, $status);
                if (!empty($jamPulang) && $status === 'HADIR') {
                    $data['jam_pulang'] = $tanggal . ' ' . $jamPulang;
                    $updatePulang = [];
                    $this->recalculateCheckout($db, $setting, $tanggal, $jamPulang, $userId, $updatePulang, (object)$data);
                    foreach ($updatePulang as $k => $v) {
                        $data[$k] = $v;
                    }
                }
                $this->applyCapToUpdate($setting, (int) $userId, $tanggal, $data, null);
                $db->table('absensi_harian')->insert($data);
                $newId = $db->insertID();
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($newId);
            }
            $saved++;
            $touchedUserIds[$userId] = true;
        }
        $db->transCommit();

        // Admin mengisi/menimpa absensi banyak guru sekaligus dari satu layar.
        // Jejaknya: siapa, tanggal berapa, berapa baris yang benar-benar tulis.
        try {
            (new AuditLogModel())->log('absensi_harian', null, 'BATCH_MANUAL_ABSEN', null, $this->getUserId(),
                null,
                [
                    'tanggal'  => $tanggal,
                    'disimpan' => $saved,
                    'dilewati' => count($errors),
                    'user_ids' => array_map('intval', array_keys($touchedUserIds)),
                ],
                'Absen Manual Massal ' . $tanggal);
        } catch (\Throwable $ex) {}
        } catch (\Throwable $ex) {
            $db->transRollback();
            log_message('error', 'Batch manual absen gagal: ' . $ex->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan batch — periksa log aplikasi.']);
        }

        // Batch absen manual juga dapat relieving hari alpha -> surat teguran
        // yang ambangnya sudah tak terpenuhi harus ikut gugur.
        $canceledTotal = 0;
        foreach (array_keys($touchedUserIds) as $uid) {
            $note = $this->syncTeguranAfterManualAbsen((int) $uid, $tanggal, $setting, 'Absen manual oleh admin');
            if ($note !== '' && preg_match('/\((\d+) surat/', $note, $m)) {
                $canceledTotal += (int) $m[1];
            }
        }

        // Pengumuman guru paling awal hanya relevan untuk hari yang sedang
        // berjalan. Batch manual untuk tanggal lampau tidak boleh memicu
        // notifikasi "guru tercepat hari ini" karena jamnya diisi ulang admin.
        if ($tanggal === date('Y-m-d')) {
            try {
                \App\Services\GuruTerawalService::afterCheckIn($db, $tanggal, 0);
            } catch (\Throwable $e) {
                log_message('error', 'Pengumuman guru terawal gagal: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => "Berhasil menyimpan {$saved} data absensi" . ($errors ? ' (' . count($errors) . ' baris dilewati)' : '')
                . ($libur['libur'] ? " — {$tanggal} berstatus LIBUR, hanya DINAS/izin/sakit yang dicatat" : '')
                . ($canceledTotal > 0 ? " ({$canceledTotal} surat teguran otomatis dibatalkan karena hari alpha sudah dilengkapi)" : ''),
            'errors' => $errors
        ]);
    }

    /**
     * Validasi hari libur untuk absen manual — sisi server.
     *
     * Sebelumnyaiatricheck hanya ada di JavaScript, jadi POST langsung
     * (curl, integrasi lain, atau browser dengan JS cacat) tetap bisa mengisi
     * presensi pada hari libur. Absensi seperti itu tidak hanya bikin rekap
     * salah, tapi juga menggeser perhitungan nilai alpha, poin, dan denda.
     *
     * Status yang tetap boleh pada hari libur: DINAS (kegiatan luar) dan
     * IJIN/SAKIT/CUTI (keterangan keadaan, bukan kehadiran). Yang diblokir
     * hanya HADIR, karena itu yangARDS Hold dihitung sebagai hari kerja.
     *
     * @return array{libur:bool, alasan:string, label:string}
     */
    private function cekHariLiburAbsenManual(string $tanggal): array
    {
        $label  = \App\Services\AttendancePolicy::namaHariId($tanggal);
        $alasan = \App\Services\AttendancePolicy::alasanLibur($tanggal);

        if ($alasan === null) {
            return ['libur' => false, 'alasan' => '', 'label' => $label];
        }

        return ['libur' => true, 'alasan' => $alasan, 'label' => $label];
    }

    private function recalculateCheckin($db, $setting, $tanggal, $jamMasuk, $userId, &$update)
    {
        $dayOfWeek = date('N', strtotime($tanggal));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }
        // Jadwal efektif: shift per guru → jadwal harian → setting
        $shiftModel = new \App\Models\ShiftModel();
        $sched = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
        $jamMasukBatas = $sched['jam_masuk'];
        $graceMins = (int) ($sched['grace_menit'] ?? ($setting->grace_period ?? 15));

        $hit = \App\Services\AttendancePolicy::telat($tanggal, (string) $jamMasuk, [
            'jam_masuk'   => $jamMasukBatas,
            'grace_menit' => $graceMins,
        ]);

        if ($hit['status'] === 'LATE') {
            $update['status_masuk'] = 'LATE';
            $update['telat_menit'] = $hit['telat_menit'];
            $calc = $this->calculateProgressivePenalty($hit['telat_menit'], $setting, 'Telat Masuk');
            $update['points'] = -1 * $calc['points'];
            $update['denda'] = $calc['denda'];
            $update['keterangan_denda'] = $calc['keterangan'];
        } else {
            $update['status_masuk'] = 'ONTIME';
            $update['telat_menit'] = 0;
            $update['datang_awal_menit'] = $hit['datang_awal_menit'];
            if ($hit['datang_awal_menit'] > 0) {
                $update['points'] = $this->calculateProgressiveRewardPoints($hit['datang_awal_menit'], $setting);
            }
        }
    }

    private function recalculateCheckout($db, $setting, $tanggal, $jamPulang, $userId, &$update, $existing)
    {
        $dayOfWeek = date('N', strtotime($tanggal));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }
        // Jadwal efektif: shift per guru → jadwal harian → setting
        $shiftModel = new \App\Models\ShiftModel();
        $sched = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
        $jamPulangLimit = $sched['jam_pulang'];
        $threshold = strtotime($tanggal . ' ' . $jamPulangLimit);
        $pulangTime = strtotime($tanggal . ' ' . $jamPulang);

        if ($pulangTime < $threshold) {
            $update['status_pulang'] = 'EARLY';
            $diff = $threshold - $pulangTime;
            $pulangCepatMenit = (int) ceil($diff / 60);
            $update['pulang_cepat_menit'] = $pulangCepatMenit;
            $calc = $this->calculateProgressivePenalty($pulangCepatMenit, $setting, 'Pulang Awal');
            $earlyPoints = -1 * $calc['points'];
            $earlyDenda = $calc['denda'];
            $keteranganDendaUpdate = $calc['keterangan'];

            // Hitung ulang porsi masuk dari jam efektif agar koreksi pulang tidak
            // menumpuk denda/poin pulang yang lama (existing + delta = double).
            $effJamMasuk = $update['jam_masuk'] ?? ($existing->jam_masuk ?? null);
            if (!empty($effJamMasuk)) {
                $tmpMasuk = [];
                $this->recalculateCheckin($db, $setting, $tanggal, date('H:i:s', strtotime($effJamMasuk)), $userId, $tmpMasuk);
                foreach (['status_masuk', 'telat_menit', 'datang_awal_menit'] as $kMasuk) {
                    if (array_key_exists($kMasuk, $tmpMasuk)) {
                        $update[$kMasuk] = $tmpMasuk[$kMasuk];
                    }
                }
                $basePoints = (int) ($tmpMasuk['points'] ?? 0);
                $baseDenda = (float) ($tmpMasuk['denda'] ?? 0);
                $baseKet = $tmpMasuk['keterangan_denda'] ?? null;
                $update['points'] = $basePoints + $earlyPoints;
                $update['denda'] = $baseDenda + $earlyDenda;
                $update['keterangan_denda'] = $baseKet ? $baseKet . '; ' . $keteranganDendaUpdate : $keteranganDendaUpdate;
            } else {
                // Tanpa info jam masuk: pertahankan perilaku lama (akumulasi).
                $existingPoints = isset($update['points']) ? $update['points'] : (int) ($existing->points ?? 0);
                $update['points'] = $existingPoints + $earlyPoints;
                $existingDenda = isset($update['denda']) ? $update['denda'] : (float) ($existing->denda ?? 0);
                $update['denda'] = $existingDenda + $earlyDenda;

                // Append keterangan denda
                $existingKeterangan = $existing->keterangan_denda ?? null;
                if ($existingKeterangan) {
                    $update['keterangan_denda'] = $existingKeterangan . "; " . $keteranganDendaUpdate;
                } else {
                    $update['keterangan_denda'] = $keteranganDendaUpdate;
                }
            }
        } else {
            $update['status_pulang'] = 'ONTIME';
            $update['pulang_cepat_menit'] = 0;
            $update['pulang_akhir_menit'] = 0;
            if ($pulangTime > $threshold) {
                $diffMinutes = (int) floor(($pulangTime - $threshold) / 60);
                $pulangAkhirMenit = ($diffMinutes >= 60) ? ($diffMinutes - 60) : 0;
                $update['pulang_akhir_menit'] = $pulangAkhirMenit;
                if ($pulangAkhirMenit > 0) {
                    $rewardPoints = $this->calculateProgressiveRewardPoints($pulangAkhirMenit, $setting);
                    $existingPoints = isset($update['points']) ? $update['points'] : (int) ($existing->points ?? 0);
                    $update['points'] = $existingPoints + $rewardPoints;
                }
            }
        }
    }

    /**
     * Tegakkan cap denda bulanan pada $update/$data sebelum tulis (batch manual).
     * No-op bila tidak ada denda atau tidak ada cap; baris sendiri dikecualikan.
     */
    private function applyCapToUpdate($setting, int $userId, string $tanggal, array &$row, ?int $excludeId = null): void
    {
        if (!isset($row['denda']) || (float) $row['denda'] <= 0) {
            return;
        }
        try {
            $svc = new \App\Services\AttendancePenaltyService();
            // Lock: pemanggil (batch/manual) berjalan dalam transaksi.
            $capped = $svc->applyMonthlyCap($userId, $tanggal, (float) $row['denda'], $setting, $excludeId, true);
            if ($capped['capped']) {
                $row['denda'] = $capped['total'];
                $row['keterangan_denda'] = trim(($row['keterangan_denda'] ?? '') . ' (dibatasi cap bulanan Rp ' . number_format((float) $capped['total'], 0, ',', '.') . ')');
            }
        } catch (\Throwable $e) {}
    }

    private function buildCheckinData($db, $setting, $userId, $tanggal, $jamMasuk, $keterangan, $status = 'HADIR')
    {
        $statusMasuk = 'ONTIME';
        $telatMenit = 0;
        $points = 0;
        $denda = 0;
        $keteranganDenda = null;
        $datangAwalMenit = 0;

        if (in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'DINAS'])) {
            $statusMasuk = ($status === 'DINAS') ? 'DINAS_APPROVED' : $status;
            $points = $this->getStatusPoints($status, $setting);
            $denda = 0;
            $keteranganDenda = null;
            $jamMasuk = ''; // no time needed
        } else {
            $dayOfWeek = date('N', strtotime($tanggal));
            $jadwal = null;
            if ($db->tableExists('absensi_jadwal_kerja')) {
                $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
            }
            // Jadwal efektif: shift per guru → jadwal harian → setting
            $shiftModel = new \App\Models\ShiftModel();
            $sched = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
            $jamMasukBatas = $sched['jam_masuk'];
            $graceMins = (int) ($sched['grace_menit'] ?? ($setting->grace_period ?? 15));

            $hit = \App\Services\AttendancePolicy::telat($tanggal, (string) $jamMasuk, [
                'jam_masuk'   => $jamMasukBatas,
                'grace_menit' => $graceMins,
            ]);
            $statusMasuk = $hit['status'];
            $telatMenit  = $hit['telat_menit'];
            $datangAwalMenit = $hit['datang_awal_menit'];

            if ($statusMasuk === 'LATE') {
                $calc = $this->calculateProgressivePenalty($telatMenit, $setting, 'Telat Masuk');
                $points = -1 * $calc['points'];
                $denda = $calc['denda'];
                $keteranganDenda = $calc['keterangan'];
            } elseif ($datangAwalMenit > 0) {
                $points = $this->calculateProgressiveRewardPoints($datangAwalMenit, $setting);
            }
        }

        $guru = $db->table('master_guru')->where('id_user', $userId)->get()->getRow();
        $idGuru = $guru->id_guru ?? null;

        $data = [
            'user_id' => $userId,
            'id_guru' => $idGuru,
            'tgl' => $tanggal,
            'jam_masuk' => !empty($jamMasuk) ? $tanggal . ' ' . $jamMasuk : null,
            'status_masuk' => $statusMasuk,
            'telat_menit' => $telatMenit,
            'datang_awal_menit' => $datangAwalMenit,
            'points' => $points,
            'denda' => $denda,
            'keterangan_denda' => $keteranganDenda,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($db->fieldExists('device_info', 'absensi_harian')) {
            $data['device_info'] = 'Manual by Admin';
        }
        if ($db->fieldExists('keterangan', 'absensi_harian') && !empty($keterangan)) {
            $data['keterangan'] = $keterangan;
        }

        $this->applyLokasiUtama($db, $data, 'masuk');

        return $data;
    }

    /**
     * Absen manual tidak punya koordinat GPS. Agar rekap tetap menampilkan
     * nama lokasi yang benar, isi lokasi_masuk_id / lat / lng / jarak dengan
     * titik "Lokasi Utama" yang ditandai admin di /admin/presensi/lokasi.
     *
     * Hanya berlaku bila jam_masuk terisi — izin, sakit, cuti, dan dinas
     * jelas bukan kehadiran di kantor sehingga tidak boleh dikaitkan ke lokasi
     * sekolah. Jarak diisi 0 karena titik acuan adalah lokasi itu sendiri.
     *
     * @param string $ sisi 'masuk' atau 'pulang'
     */
    private function applyLokasiUtama($db, array &$data, string $sisi = 'masuk'): void
    {
        try {
            $sisi = ($sisi === 'pulang') ? 'pulang' : 'masuk';
            $jamKey = $sisi === 'pulang' ? 'jam_pulang' : 'jam_masuk';

            // Tidak hadir / belum absen -> jangan dikaitkan ke lokasi kantor.
            if (empty($data[$jamKey] ?? null)) {
                return;
            }

            $utama = (new \App\Models\AttendanceLocationModel())->getUtama();
            if (!$utama) {
                return;
            }

            $col = [
                'lokasi_' . $sisi . '_id' => (int) $utama->id,
                'lat_' . $sisi => (float) $utama->lat,
                'lng_' . $sisi => (float) $utama->lng,
                'jarak_' . $sisi => 0,
            ];
            foreach ($col as $k => $v) {
                if ($db->fieldExists($k, 'absensi_harian')) {
                    $data[$k] = $v;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'applyLokasiUtama gagal: ' . $e->getMessage());
        }
    }

    private function getStatusPoints($status, $setting)
    {
        // Satu sumber kebenaran via service (dulu: -abs() di sini vs mentah di
        // approvePermit → tanda poin izin/sakit/cuti inkonsisten).
        return (new \App\Services\AttendancePenaltyService())->getStatusPoints($status, $setting);
    }

    /**
     * Process Manual Check-In
     */
    public function processManualCheckin()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $this->patchAbsensiHarianTable();

        $userId = (int) $this->request->getPost('user_id');
        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $jamMasuk = trim((string) $this->request->getPost('jam_masuk'));
        $keterangan = mb_substr(trim((string) $this->request->getPost('keterangan')), 0, 500);
        $status = strtoupper(trim((string) ($this->request->getPost('status') ?? 'HADIR'))); // HADIR, IJIN, SAKIT, CUTI

        if (empty($userId) || empty($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap']);
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format tanggal tidak valid (YYYY-MM-DD)']);
        }
        if ($tanggal > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa mengisi presensi tanggal masa depan']);
        }
        if (!in_array($status, ['HADIR', 'IJIN', 'SAKIT', 'CUTI', 'DINAS'], true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Status tidak valid']);
        }
        if ($status === 'HADIR') {
            if (empty($jamMasuk)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Jam masuk harus diisi untuk status Hadir']);
            }
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $jamMasuk)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format jam masuk tidak valid (HH:MM)']);
            }
        }

        $db = \Config\Database::connect();

        // Pastikan user guru aktif yang valid
        $userExists = $db->table('users u')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('u.id', $userId)
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->countAllResults() > 0;
        if (!$userExists) {
            return $this->response->setJSON(['status' => false, 'message' => 'User guru tidak ditemukan / non-aktif']);
        }

        // Check if already exists
        $existing = $db->table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl', $tanggal)
            ->get()->getRow();

        // Allow updating existing records directly
        // Status list: HADIR, IJIN, SAKIT, CUTI, DINAS

        // Check if date is valid work day
        $dayOfWeek = date('N', strtotime($tanggal));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }
        // Gerbang libur: AttendancePolicy sudah menggabungkan libur mingguan,
        // jadwal per hari (is_libur), dan kalender nasional.
        $alasanLibur = \App\Services\AttendancePolicy::alasanLibur($tanggal);
        if ($alasanLibur !== null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa absen di hari libur: ' . $alasanLibur]);
        }

        // Get setting for late calculation
        $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();

        // Calculate late with grace period
        $telatMenit = 0;
        $points = 0;
        $denda = 0;
        $keteranganDenda = null;
        $datangAwalMenit = 0;

        if (in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'DINAS'])) {
            $statusMasuk = ($status === 'DINAS') ? 'DINAS_APPROVED' : $status;
            $jamMasuk = ''; // force clear so jam_masuk stays null
            // Poin via service (satu sumber; dulu -abs() padahal approve pakai mentah)
            $points = $this->getStatusPoints($status, $setting);
            if ($status === 'DINAS') {
                $denda = 0;
                $keteranganDenda = null;
            }
        } else {
            $statusMasuk = 'ONTIME';
            // Jadwal efektif: shift per guru → jadwal harian → setting
            $shiftModel = new \App\Models\ShiftModel();
            $sched = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
            $graceMins = (int) ($sched['grace_menit'] ?? ($setting->grace_period ?? 15));

            $hit = \App\Services\AttendancePolicy::telat($tanggal, (string) $jamMasuk, [
                'jam_masuk'   => $sched['jam_masuk'],
                'grace_menit' => $graceMins,
            ]);
            $statusMasuk     = $hit['status'];
            $telatMenit      = $hit['telat_menit'];
            $datangAwalMenit = $hit['datang_awal_menit'];

            if ($statusMasuk === 'LATE') {
                $calc = $this->calculateProgressivePenalty($telatMenit, $setting, 'Telat Masuk');
                $points = -1 * $calc['points'];
                $denda = $calc['denda'];
                $keteranganDenda = $calc['keterangan'];
            } elseif ($datangAwalMenit > 0) {
                $points = $this->calculateProgressiveRewardPoints($datangAwalMenit, $setting);
            }

            // Bila record sudah punya jam_pulang, denda/points hari ini adalah
            // gabungan porsi masuk + pulang. Tanpa blok ini, check-in manual
            // menimpa denda dengan porsi masuk saja sehingga denda "pulang
            // cepat" (EARLY) hilang senyap. Selaras prosesManualCheckout.
            if ($existing && !empty($existing->jam_pulang) && !empty($setting)) {
                $schedP    = (new \App\Models\ShiftModel())->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
                $thPulang  = strtotime($tanggal . ' ' . $schedP['jam_pulang']);
                $pulangTs  = strtotime((string) $existing->jam_pulang);
                $earlyKet  = null;
                if ($pulangTs < $thPulang) {
                    $pulangCepat = (int) ceil(($thPulang - $pulangTs) / 60);
                    $calcP = $this->calculateProgressivePenalty($pulangCepat, $setting, 'Pulang Awal');
                    $points += -1 * $calcP['points'];
                    $denda  += (float) $calcP['denda'];
                    $earlyKet = $calcP['keterangan'];
                } elseif ($pulangTs > $thPulang) {
                    $akhirMenit = (int) floor(($pulangTs - $thPulang) / 60);
                    if ($akhirMenit >= 60) $points += $this->calculateProgressiveRewardPoints($akhirMenit - 60, $setting);
                }
                $kets = array_values(array_filter([(string) ($keteranganDenda ?? ''), (string) ($earlyKet ?? '')]));
                $keteranganDenda = $kets ? implode('; ', $kets) : null;
            }
        }

        // Get id_guru
        $guru = $db->table('master_guru')->where('id_user', $userId)->get()->getRow();
        $idGuru = $guru->id_guru ?? null;

        $data = [
            'user_id' => $userId,
            'id_guru' => $idGuru,
            'tgl' => $tanggal,
            'jam_masuk' => !empty($jamMasuk) ? $tanggal . ' ' . $jamMasuk : null,
            'status_masuk' => $statusMasuk,
            'telat_menit' => $telatMenit,
            'datang_awal_menit' => $datangAwalMenit,
            'points' => $points,
            'denda' => $denda,
            'keterangan_denda' => $keteranganDenda,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($db->fieldExists('device_info', 'absensi_harian')) {
            $data['device_info'] = 'Manual by Admin';
        }

        if ($db->fieldExists('keterangan', 'absensi_harian')) {
            $data['keterangan'] = $keterangan ?? 'Absen manual oleh admin';
        }

        $this->applyLokasiUtama($db, $data, 'masuk');

        // Tulis + cap dalam satu transaksi agar cap lock (FOR UPDATE) berlaku
        // dan insert/update + sync poin atomik.
        $db->transBegin();
        try {
        // Tegakkan cap denda bulanan (baris sendiri dikecualikan bila update)
        if (($data['denda'] ?? 0) > 0) {
            $capService = new \App\Services\AttendancePenaltyService();
            $capped = $capService->applyMonthlyCap((int) $userId, $tanggal, (float) $data['denda'], $setting, $existing ? (int) $existing->id : null, true);
            if ($capped['capped']) {
                $data['denda'] = $capped['total'];
                $data['keterangan_denda'] = trim(($data['keterangan_denda'] ?? '') . ' (dibatasi cap bulanan Rp ' . number_format((float) $capped['total'], 0, ',', '.') . ')');
            }
        }

        // Insert or Update
        if ($existing) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            // Remove unupdatable fields
            unset($data['user_id'], $data['id_guru'], $data['tgl'], $data['created_at']);
            
            // Clear checkout info when changing to IJIN/SAKIT/CUTI/DINAS
            if (in_array($statusMasuk, ['IJIN', 'SAKIT', 'CUTI', 'DINAS', 'DINAS_APPROVED'])) {
                $data['jam_pulang'] = null;
                $data['status_pulang'] = ($statusMasuk === 'DINAS' || $statusMasuk === 'DINAS_APPROVED') ? 'DINAS_APPROVED' : null;
                $data['pulang_cepat_menit'] = 0;
                $data['pulang_akhir_menit'] = 0;

                // Denda hanya dinolkan bila bulan itu belum ada pembayaran.
                // Menolkan denda yang sudah terbayar membuka kredit hantu:
                // ledger tetap mencatat pembayaran padahal tagihannya hilang
                // (sama seperti proteksi di doRecalculateDate()).
                $cacheDibayar = [];
                $dibayar = $this->totalDibayarBulan(
                    (int) $userId,
                    (int) date('n', strtotime($tanggal)),
                    (int) date('Y', strtotime($tanggal)),
                    $cacheDibayar
                );
                if ($dibayar > 0 && (float) ($existing->denda ?? 0) > 0) {
                    $data['denda'] = (float) $existing->denda;
                    $data['keterangan_denda'] = $existing->keterangan_denda;
                    $dendaDipertahankan = true;
                } else {
                    $data['denda'] = 0;
                    $data['keterangan_denda'] = null;
                }
            }
            
            $db->table('absensi_harian')->where('id', $existing->id)->update($data);
            // Sync poin sekali setelah update
            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
            $insertedId = $existing->id;
            $action = 'UPDATE';
            $msg = 'Berhasil memperbarui data absensi menjadi ' . $statusMasuk;
            if (!empty($dendaDipertahankan)) {
                $msg .= ' — denda Rp ' . number_format((float) $existing->denda, 0, ',', '.')
                    . ' dipertahankan karena bulan ini sudah ada pembayaran';
            }
        } else {
            $db->table('absensi_harian')->insert($data);
            $insertedId = $db->insertID();
            // Sync poin setelah insert
            (new \App\Models\PoinHistoryModel())->syncAttendancePoint($insertedId);
            $action = 'CREATE';
            $msg = 'Absen masuk berhasil dicatat' . ($statusMasuk === 'LATE' ? " (Terlambat {$telatMenit} menit)" : '');
        }
        $db->transCommit();
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', 'Manual checkin gagal: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal mencatat absen masuk — periksa log aplikasi.']);
        }

        // AUDIT LOG
        try {
            $auditModel = new \App\Models\AuditLogModel();
            $auditModel->log(
                'absensi_harian',
                $insertedId,
                $action,
                $userId,
                session()->get('user_id'),
                $existing ? json_encode($existing) : null,
                $data,
                'Absen Manual Masuk oleh Admin'
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
        }

        // Absen manual dapat menghilangkan hari alpha. Bila itu membuat alpha
        // bulan ini turun di bawah ambang surat teguran, surat otomatis gugur.
        $msg .= $this->syncTeguranAfterManualAbsen((int) $userId, $tanggal, $setting, 'Absen manual oleh admin');

        // Pengumuman guru paling awal hanya relevan untuk hari yang sedang
        // berjalan dan hanya bila guru benar-benar hadir (bukan izin/sakit/
        // cuti/dinas). Absen manual untuk tanggal lampau tidak boleh memicu
        // pengumuman "guru tercepat hari ini" karena jam-nya diisi ulang oleh
        // admin — bukan rekaman scan/kedatangan nyata.
        if ($tanggal === date('Y-m-d') && $status === 'HADIR') {
            try {
                \App\Services\GuruTerawalService::afterCheckIn($db, $tanggal, (int) $userId);
            } catch (\Throwable $e) {
                log_message('error', 'Pengumuman guru terawal gagal: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => $msg
        ]);
    }

    /**
     * Batalkan surat teguran yang tidak lagi sah setelah admin mengisi absen
     * manual untuk hari-hari alpha. Mengembalikan teks tambahan untuk
     * ditambahkan ke pesan respons, atau string kosong bila tidak ada yg gugur.
     */
    private function syncTeguranAfterManualAbsen(int $userId, string $tanggal, $setting, string $reason): string
    {
        if ($userId <= 0 || $tanggal === '') {
            return '';
        }

        try {
            $ts = strtotime($tanggal);
            if (!$ts) {
                return '';
            }

            $svc = new \App\Services\AttendancePenaltyService();
            $canceled = $svc->syncSuratTeguranOnAttendanceChange(
                $userId,
                (int) date('n', $ts),
                (int) date('Y', $ts),
                $setting,
                $reason
            );

            if ($canceled > 0) {
                return ' (' . $canceled . ' surat teguran otomatis dibatalkan karena hari alpha sudah dilengkapi)';
            }
        } catch (\Throwable $ex) {
            log_message('error', 'Sync surat teguran setelah absen manual gagal: ' . $ex->getMessage());
        }

        return '';
    }

    public function processManualCheckout()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $userId = (int) $this->request->getPost('user_id');
        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $jamPulang = trim((string) $this->request->getPost('jam_pulang'));

        if (empty($userId) || empty($tanggal) || empty($jamPulang)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap']);
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format tanggal tidak valid (YYYY-MM-DD)']);
        }
        if ($tanggal > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa mengisi presensi tanggal masa depan']);
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $jamPulang)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam pulang tidak valid (HH:MM)']);
        }

        $db = \Config\Database::connect();

        // Check if attendance exists
        $existing = $db->table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl', $tanggal)
            ->get()->getRow();

        if (!$existing) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data absen masuk tidak ditemukan untuk tanggal tersebut']);
        }

        // Absen pulang WAJIB punya absen masuk lebih dulu. Baris bisa sudah ada
        // tanpa jam_masuk (alpha dari cron, izin/sakit/cuti/dinas yang
        // disetujui admin), dan itu bukan bukti guru hadir. Tanpa guard ini
        // admin bisa mengisi jam pulang pada baris yang tak pernah dimasuki —
        // bug yang dulu membangkitkan baris "pulang tanpa masuk".
        // Setara dengan guard yang sama di Guru\Attendance::checkOut().
        if (empty($existing->jam_masuk)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Absen masuk belum tercatat pada tanggal ' . $tanggal
                    . '. Isi absen masuk terlebih dahulu sebelum mengisi absen pulang.',
            ]);
        }

        // Jam pulang tak boleh lebih awal dari jam masuk (format DB bisa datetime/time)
        if (preg_match('/([01]\d|2[0-3]):[0-5]\d/', (string) $existing->jam_masuk, $m)) {
            $jamMasukEff = $m[0];
            if (substr($jamPulang, 0, 5) < $jamMasukEff) {
                return $this->response->setJSON(['status' => false, 'message' => 'Jam pulang tidak boleh lebih awal dari jam masuk (' . $jamMasukEff . ')']);
            }
        }

        // Idempoten: jam pulang yang sama dikirim ulang → anggap sukses tanpa
        // menghitung ulang (mencegah penalti pulang-awal terakumulasi ganda)
        if (!empty($existing->jam_pulang) && preg_match('/([01]\d|2[0-3]):[0-5]\d/', (string) $existing->jam_pulang, $mp)) {
            if (substr($jamPulang, 0, 5) === $mp[0]) {
                return $this->response->setJSON(['status' => true, 'message' => 'Jam pulang sudah tercatat (' . $mp[0] . ')']);
            }
        }

        // Check if date is valid work day
        $dayOfWeek = date('N', strtotime($tanggal));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }
        // Gerbang libur: AttendancePolicy menggabungkan libur mingguan,
        // jadwal per hari (is_libur), dan kalender nasional.
        $alasanLibur = \App\Services\AttendancePolicy::alasanLibur($tanggal);
        if ($alasanLibur !== null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa absen di hari libur: ' . $alasanLibur]);
        }

        // Get setting for checkout calculation
    $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();

    // Jadwal efektif: shift per guru → jadwal harian → setting
    $shiftModel = new \App\Models\ShiftModel();
    $sched = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
    $jamPulangLimit = $sched['jam_pulang'];
    
    // Baris proteksi (WFH/izin/sakit/cuti/alpha) tidak boleh di-checkout-kan manual.
    $exStatusMasuk = strtoupper((string) ($existing->status_masuk ?? ''));
    $exStatusPulang = strtoupper((string) ($existing->status_pulang ?? ''));
    $exIsWfh = (!empty($existing->is_wfh) && (int) $existing->is_wfh === 1) || $exStatusMasuk === 'WFH' || $exStatusPulang === 'WFH';
    $exIsLeave = in_array($exStatusMasuk, ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH'], true)
        || in_array($exStatusPulang, ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH'], true);
    if ($exIsWfh || $exIsLeave || in_array($exStatusMasuk, ['ALPHA', 'ALPA'], true)) {
        return $this->response->setJSON(['status' => false, 'message' => 'Baris ' . $exStatusMasuk . ' tidak bisa di-checkout manual']);
    }

    // Calculate Early
    $isDinas = in_array($existing->status_masuk ?? '', ['DINAS', 'DINAS_APPROVED', 'DINAS_PENDING']) || (strpos($existing->status_masuk ?? '', 'DINAS') === 0);
    $statusPulang = 'ONTIME';
    $pulangCepatMenit = 0;
    $threshold = strtotime($tanggal . ' ' . $jamPulangLimit);
    $pulangTime = strtotime($tanggal . ' ' . $jamPulang);

    $pulangAkhirMenit = 0;
    $earlyPoints = 0;
    $earlyDenda = 0;
    $keteranganDendaUpdate = null;

    if ($isDinas) {
        $statusPulang = ($existing->status_masuk === 'DINAS_APPROVED') ? 'DINAS_APPROVED' : 'DINAS_OUT';
        $pulangCepatMenit = 0;
        $pulangAkhirMenit = 0;
        $earlyPoints = 0;
        $earlyDenda = 0;
        $keteranganDendaUpdate = null;
    } elseif ($pulangTime < $threshold) {
        $statusPulang = 'EARLY';
        $diff = $threshold - $pulangTime;
        $pulangCepatMenit = (int) ceil($diff / 60);
        $calc = $this->calculateProgressivePenalty($pulangCepatMenit, $setting, 'Pulang Awal');
        $earlyPoints = -1 * $calc['points'];
        $earlyDenda = $calc['denda'];
        $keteranganDendaUpdate = $calc['keterangan'];
    } elseif ($pulangTime > $threshold) {
        // Kanonik: reward lembur hanya untuk kelebihan >= 60 menit, dikurangi 60.
        $diffMenit = (int) floor(($pulangTime - $threshold) / 60);
        $pulangAkhirMenit = ($diffMenit >= 60) ? ($diffMenit - 60) : 0;
        $earlyPoints = $this->calculateProgressiveRewardPoints($pulangAkhirMenit, $setting);
    }

    // Hitung ulang dari NOL (bukan existing + delta) agar koreksi jam pulang
    // tidak menumpuk denda/poin lama. Porsi masuk dihitung ulang dari jam_masuk tersimpan.
    $masukPoints = 0;
    $masukDenda = 0;
    $masukKetDenda = null;
    if (!empty($existing->jam_masuk)) {
        $graceMins = (int) ($sched['grace_menit'] ?? ($setting->grace_period ?? 15));

        $hitMasuk = \App\Services\AttendancePolicy::telat($tanggal, (string) $existing->jam_masuk, [
            'jam_masuk'   => $sched['jam_masuk'],
            'grace_menit' => $graceMins,
        ]);

        if ($hitMasuk['status'] === 'LATE') {
            $calcMasuk = $this->calculateProgressivePenalty($hitMasuk['telat_menit'], $setting, 'Telat Masuk');
            $masukPoints = -1 * $calcMasuk['points'];
            $masukDenda = $calcMasuk['denda'];
            $masukKetDenda = $calcMasuk['keterangan'];
        } elseif ($hitMasuk['datang_awal_menit'] > 0) {
            $masukPoints = $this->calculateProgressiveRewardPoints($hitMasuk['datang_awal_menit'], $setting);
        }
    }

    $totalPoints = $isDinas ? (int) ($existing->points ?? 0) : ($masukPoints + $earlyPoints);
    $totalDenda = $isDinas ? 0 : ((float) $masukDenda + (float) $earlyDenda);

    // Gabungkan keterangan porsi masuk + pulang yang baru dihitung.
    $ketsBaru = array_values(array_filter([$masukKetDenda, $keteranganDendaUpdate]));
    $keteranganDendaUpdate = $isDinas ? null : ($ketsBaru ? implode('; ', $ketsBaru) : null);

    // Cap + tulis dalam satu transaksi agar lock (FOR UPDATE) berlaku.
    $db->transBegin();
    try {
    // Tegakkan cap denda bulanan (baris sendiri dikecualikan)
    if (!$isDinas && $totalDenda > 0) {
        $capService = new \App\Services\AttendancePenaltyService();
        $capped = $capService->applyMonthlyCap((int) $userId, $tanggal, (float) $totalDenda, $setting, (int) $existing->id, true);
        if ($capped['capped']) {
            $totalDenda = $capped['total'];
            $keteranganDendaUpdate = ($keteranganDendaUpdate ? $keteranganDendaUpdate . ' ' : '') . '(dibatasi cap bulanan Rp ' . number_format($totalDenda, 0, ',', '.') . ')';
        }
    }

    // Update
    $updateData = [
        'jam_pulang' => $tanggal . ' ' . $jamPulang,
        'status_pulang' => $statusPulang,
        'pulang_cepat_menit' => $pulangCepatMenit,
        'pulang_akhir_menit' => $pulangAkhirMenit,
        'points' => $totalPoints,
        'denda' => $totalDenda,
        'keterangan_denda' => $keteranganDendaUpdate,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $this->applyLokasiUtama($db, $updateData, 'pulang');

    // Admin mencatat absen pulang untuk hari yang sudah dipotong penalti
    // "tidak absen pulang": faktanya berubah (guru memang pulang, hanya
    // lupa absen), jadi penalti itu tidak boleh tinggal. Poin di atas sudah
    // dihitung ulang dari jam/status sehingga pengembaliannya otomatis — yang
    // dibersihkan di sini penandanya, supaya cron tidak menganggap hari itu
    // masih menunggu penalti dan supaya angka tidak terlihat dipotong dua kali.
    if ((int) ($existing->pulang_tidak_tercatat ?? 0) === 1) {
        $updateData['pulang_tidak_tercatat'] = 0;
        $updateData['poin_penalti_pulang']   = 0;
    }

    // Pulang manual menghitung ULANG denda masuk + pulang. Bila bulan itu
    // sudah ada pembayaran, hasil hitung yang lebih kecil tidak boleh
    // menimpa tagihan: ledger tetap mencatat bayarannya (kredit hantu).
    $cachePulang = [];
    $dibayarPulang = $this->totalDibayarBulan(
        (int) $existing->user_id,
        (int) date('n', strtotime($tanggal)),
        (int) date('Y', strtotime($tanggal)),
        $cachePulang
    );
    if ($dibayarPulang > 0 && (float) $totalDenda < (float) ($existing->denda ?? 0)) {
        unset($updateData['denda'], $updateData['keterangan_denda']);
    }

    $db->table('absensi_harian')
        ->where('id', $existing->id)
        ->update($updateData);
    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
    $db->transCommit();
    } catch (\Throwable $e) {
        try { $db->transRollback(); } catch (\Throwable $e2) {}
        log_message('error', 'Manual checkout gagal: ' . $e->getMessage());
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal mencatat absen pulang — periksa log aplikasi.']);
    }

    // AUDIT LOG
    try {
        $auditModel = new AuditLogModel();
        $auditModel->log(
            'absensi_harian',
            $existing->id,
            'UPDATE',
            $userId,
            session()->get('user_id'),
            $existing,
            $updateData,
            'Absen Manual Pulang oleh Admin'
        );
    } catch (\Throwable $ex) {
        log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
    }

    return $this->response->setJSON([
        'status' => true, 
        'message' => 'Absen pulang berhasil dicatat' . ($statusPulang === 'EARLY' ? " (Pulang Cepat {$pulangCepatMenit} menit)" : '')
    ]);
    }

    /**
     * Get attendance status for a teacher on specific date
     */
    public function getAttendanceStatus()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $userId = (int) $this->request->getGet('user_id');
        $tanggal = trim((string) $this->request->getGet('tanggal'));

        if (empty($userId) || empty($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap']);
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format tanggal tidak valid']);
        }

        $db = \Config\Database::connect();
        $att = $db->table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl', $tanggal)
            ->get()->getRow();

        // Sertakan jadwal efektif (shift → global) untuk user+tanggal ini
        $sched = null;
        try {
            $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
            $dayOfWeek = date('N', strtotime($tanggal));
            $jadwalGlobal = $db->tableExists('absensi_jadwal_kerja')
                ? $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow()
                : null;
            $sched = (new \App\Models\ShiftModel())->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwalGlobal, $setting ?: (object) []);
        } catch (\Throwable $e) {
            $sched = null;
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => $att,
            'jadwal' => $sched
        ]);
    }

    /**
     * Delete / Reset attendance record for a specific teacher on a date
     */
    public function deleteAttendance()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $userId = (int) $this->request->getPost('user_id');
        $tanggal = trim((string) $this->request->getPost('tanggal'));

        if (empty($userId) || empty($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap']);
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format tanggal tidak valid']);
        }

        $db = \Config\Database::connect();
        $row = $db->table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl', $tanggal)
            ->get()->getRow();

        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data presensi tidak ditemukan']);
        }

        $db->transBegin();
        try {
            // Clean point history
            if ($db->tableExists('absensi_poin_history')) {
                $db->table('absensi_poin_history')->where('absensi_id', $row->id)->delete();
            }
            $db->table('absensi_harian')->where('id', $row->id)->delete();
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Delete attendance gagal: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus data']);
        }

        // AUDIT LOG (dulu: hapus tanpa jejak + total poin tidak direcompute)
        try {
            (new AuditLogModel())->log(
                'absensi_harian',
                $row->id,
                'DELETE',
                $row->user_id,
                session()->get('user_id'),
                $row,
                null,
                'Absensi Dihapus/Reset oleh Admin'
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Data presensi berhasil direset / dihapus'
        ]);
    }

    /**
     * Get attendance status for ALL teachers on a given date (for batch refresh)
     */
    public function batchAttendanceStatus()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $tanggal = trim((string) ($this->request->getGet('tanggal') ?? date('Y-m-d')));
        $dt = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            $tanggal = date('Y-m-d');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('absensi_harian')
            ->where('tgl', $tanggal)
            ->get()->getResult();

        // Jadwal efektif per user (shift → global) agar halaman manual menampilkan
        // batas jam yang benar untuk tiap guru.
        $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        $dayOfWeek = date('N', strtotime($tanggal));
        $jadwalGlobal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwalGlobal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }
        $shiftModel = new \App\Models\ShiftModel();

        $data = [];
        foreach ($rows as $r) {
            $sched = $shiftModel->getScheduleForUser((int) $r->user_id, (int) $dayOfWeek, $jadwalGlobal, $setting ?: (object) []);
            $data[$r->user_id] = [
                'jam_masuk' => $r->jam_masuk ? date('H:i', strtotime($r->jam_masuk)) : null,
                'jam_pulang' => $r->jam_pulang ? date('H:i', strtotime($r->jam_pulang)) : null,
                'keterangan' => $r->keterangan ?? '',
                'status_masuk' => $r->status_masuk ?? null,
                'jam_masuk_raw' => $r->jam_masuk,
                'jam_pulang_raw' => $r->jam_pulang,
                'batas_masuk' => substr($sched['jam_masuk'], 0, 5),
                'batas_pulang' => substr($sched['jam_pulang'], 0, 5),
                'jadwal_sumber' => $sched['sumber'],
            ];
        }

        return $this->response->setJSON(['status' => true, 'data' => $data]);
    }

    /**
     * List Pending Leave/Permit Requests (Izin/Cuti/Sakit/Dinas)
     */
    public function permits()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $this->patchAbsensiIzinTable();

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $filterStatus = $this->request->getGet('status') ?? 'all';
        $filterJenis = $this->request->getGet('jenis') ?? 'all';

        $db = \Config\Database::connect();

        $builder = $db->table('absensi_izin i')
            ->select('i.*, u.first_name, u.last_name, u.username, u.foto, g.nama_guru, g.nip,
                      adm.first_name as verifier_first, adm.last_name as verifier_last')
            ->join('users u', 'u.id = i.user_id', 'left')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->join('users adm', 'adm.id = i.verified_by', 'left');

        if ($bulan !== 'all' && is_numeric($bulan)) {
            $builder->where("(MONTH(i.tgl_mulai) = {$bulan} OR MONTH(i.tgl_selesai) = {$bulan})");
        }
        if ($tahun !== 'all' && is_numeric($tahun)) {
            $builder->where("(YEAR(i.tgl_mulai) = {$tahun} OR YEAR(i.tgl_selesai) = {$tahun})");
        }
        if ($filterStatus !== 'all') {
            $builder->where('i.status', strtolower($filterStatus));
        }
        if ($filterJenis !== 'all') {
            $builder->where('i.jenis', strtolower($filterJenis));
        }

        $permits = $builder->orderBy('i.created_at', 'DESC')->get()->getResult();

        // Get all active teachers for manual input modal
        $teachers = $db->table('users u')
            ->select('u.id as user_id, u.first_name, u.last_name, g.nama_guru, g.nip')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        // Compute Statistics
        $totalAll = count($permits);
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $sakitCount = 0;
        $izinCount = 0;
        $cutiCount = 0;
        $dinasCount = 0;

        foreach ($permits as $p) {
            $st = strtolower(trim((string)$p->status));
            if ($st === 'approved' || $st === '1') $approvedCount++;
            elseif ($st === 'rejected' || $st === '2') $rejectedCount++;
            else $pendingCount++;

            $jn = strtolower(trim((string)$p->jenis));
            if ($jn === 'sakit') $sakitCount++;
            elseif ($jn === 'cuti') $cutiCount++;
            elseif ($jn === 'dinas') $dinasCount++;
            else $izinCount++;
        }

        $stats = [
            'total' => $totalAll,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'sakit' => $sakitCount,
            'izin' => $izinCount,
            'cuti' => $cutiCount,
            'dinas' => $dinasCount
        ];

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/permits', [
            'title' => 'Data & Pengajuan Izin / Cuti Guru',
            'judul' => 'Kelola Izin & Cuti',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'permits' => $permits,
            'teachers' => $teachers,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'filterStatus' => $filterStatus,
            'filterJenis' => $filterJenis,
            'stats' => $stats
        ]);
    }

    /**
     * Admin Manual Leave Input
     */
    public function saveManualPermit()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiIzinTable();

        $userId = (int) $this->request->getPost('user_id');
        $jenis = strtolower(trim($this->request->getPost('jenis') ?? 'izin'));
        $tglMulai = $this->request->getPost('tgl_mulai');
        $tglSelesai = $this->request->getPost('tgl_selesai') ?: $tglMulai;
        $keterangan = trim($this->request->getPost('keterangan') ?? '');
        $langsungApprove = $this->request->getPost('langsung_approve') == '1';

        if (!$userId || empty($tglMulai)) {
            return $this->respond(false, 'Guru dan tanggal mulai izin harus diisi');
        }

        // Validasi tanggal & jenis (samakan dengan leaveRequest guru)
        $allowedJenis = ['ijin', 'izin', 'sakit', 'cuti', 'dinas'];
        if (!in_array($jenis, $allowedJenis, true)) {
            return $this->respond(false, 'Jenis pengajuan tidak valid.');
        }
        if ($jenis === 'izin') {
            $jenis = 'ijin';
        }
        try {
            $startObj = new \DateTime($tglMulai);
            $endObj = new \DateTime($tglSelesai);
        } catch (\Throwable $e) {
            return $this->respond(false, 'Format tanggal tidak valid (YYYY-MM-DD).');
        }
        if ($startObj->format('Y-m-d') !== substr((string) $tglMulai, 0, 10)
            || $endObj->format('Y-m-d') !== substr((string) $tglSelesai, 0, 10)) {
            return $this->respond(false, 'Format tanggal tidak valid (YYYY-MM-DD).');
        }
        if ($startObj > $endObj) {
            return $this->respond(false, 'Tanggal mulai tidak boleh setelah tanggal selesai.');
        }
        $tglMulai = $startObj->format('Y-m-d');
        $tglSelesai = $endObj->format('Y-m-d');

        // Guard + insert memakai force di banyak tempat (kategori cuti di
        // bawah, lalu overlap & kuota). Dulu variabelnya baru di-assign SETELAH
        // blok cuti, jadi `!$force` di baris 6104/6109 membaca variabel yang
        // belum ada: peringatan "Undefined variable" (→ 500 saat debug) dan
        // force=1 diabaikan untuk cuti berkategori.
        $force = $this->request->getPost('force') == '1';

        // ── Kategori cuti (Cuti Tahunan/Besar/Melahirkan/Alasan Penting) ──
        $kategoriCuti = null;
        if ($jenis === 'cuti') {
            $kategoriCuti = strtolower(trim((string) $this->request->getPost('kategori_cuti')));
            if ($kategoriCuti !== '') {
                if (! \App\Services\CutiKategoriService::valid($kategoriCuti)) {
                    return $this->respond(false, 'Kategori cuti tidak valid.');
                }

                // Eligibilitas mengikuti aturan pengajuan guru (PNS vs
                // PPPK berbeda hak); force=1 mem-bypass dengan sadar.
                $profilCuti = \App\Services\CutiKategoriService::profil($userId);
                $cekElig = \App\Services\CutiKategoriService::cekEligibilitas($kategoriCuti, $profilCuti, $tglMulai);
                if (! $cekElig['ok'] && !$force) {
                    return $this->respond(false, $cekElig['pesan'] . ' Ulangi dengan force=1 bila memang disengaja.', null, null, 409);
                }

                $batasKat = \App\Services\CutiKategoriService::batasHariKategori($kategoriCuti);
                if ($batasKat > 0 && !$force) {
                    $hariKal = \App\Services\CutiKategoriService::hariKalender($tglMulai, $tglSelesai);
                    if ($hariKal > $batasKat) {
                        return $this->respond(false, 'Maksimal durasi ' . \App\Services\CutiKategoriService::label($kategoriCuti) . ' adalah ' . $batasKat . ' hari. Pengajuan: ' . $hariKal . ' hari. Ulangi dengan force=1 bila memang disengaja.', null, null, 409);
                    }
                }
            } else {
                $kategoriCuti = null;
            }
        }

        // Guard overlap + kuota (samakan leaveRequest guru). Admin boleh
        // override sadar dengan force=1 (mis. backfill arsip).
        //
        // Perhitungan kuota memanggil IzinKuotaService — sama persis dengan
        // yang dipakai guru, sehingga angka yang dibaca admin saat mengisi
        // form manual dan angka yang ditegakkan server tidak bisa berbeda.
        if (!$force) {
            $dbCek = \Config\Database::connect();
            $tabrakan = $dbCek->table('absensi_izin')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('tgl_mulai <=', $tglSelesai)
                ->where('tgl_selesai >=', $tglMulai)
                ->countAllResults();
            if ($tabrakan > 0) {
                return $this->respond(false, 'Rentang tanggal bertabrakan dengan pengajuan lain yang masih aktif. Ulangi dengan force=1 bila memang disengaja.', null, null, 409);
            }

            $cekKuota = (new \App\Services\IzinKuotaService($dbCek))->cek($userId, $jenis, $tglMulai, $tglSelesai, 0, $kategoriCuti);
            if (!$cekKuota['ok']) {
                return $this->respond(
                    false,
                    $cekKuota['pesan'] . ' Ulangi dengan force=1 bila memang disengaja.',
                    null,
                    null,
                    409
                );
            }
        }

        $db = \Config\Database::connect();

        // Guard + insert diulang DI BAWAH lock. Pengecekan pertama di atas
        // tetap berguna karena ia memberi pesan 409 yang cepat tanpa
        // menahan lock, tapi ia dilakukan di luar transaksi sehingga TOCTOU:
        // dua admin yang menyimpan input manual untuk guru yang sama pada
        // saat berdekatan bisa sama-sama membaca kuota yang sama lalu
        // sama-sama lolos. Yang menentukan adalah yang kedua ini.
        //
        // Cakupannya sengaja hanya guard + insert baris izin. Penulisan
        // absensi tetap di bloknya sendiri yang sudah punya roll-back dan
        // kompensasi (hapus baris izin + berkas bukti), supaya tidak ada
        // dua transaksi yang saling menimpa.
        $dbLock = \Config\Database::connect();
        $dbLock->transBegin();
        try {
            if ($dbLock->tableExists('users')) {
                try {
                    $dbLock->query(
                        'SELECT id FROM ' . $dbLock->prefixTable('users') . ' WHERE id = ? FOR UPDATE',
                        [$userId]
                    );
                } catch (\Throwable $e) {
                    // SQLite tidak mendukung FOR UPDATE.
                }
            }

            if (!$force) {
                $tabrakan = $dbLock->table('absensi_izin')
                    ->where('user_id', $userId)
                    ->whereIn('status', \App\Models\LeaveModel::STATUS_MAKAN_KUOTA)
                    ->where('tgl_mulai <=', $tglSelesai)
                    ->where('tgl_selesai >=', $tglMulai)
                    ->countAllResults();
                if ($tabrakan > 0) {
                    $dbLock->transRollback();
                    return $this->respond(false, 'Rentang tanggal bertabrakan dengan pengajuan lain yang masih aktif. Ulangi dengan force=1 bila memang disengaja.', null, null, 409);
                }

                $cekKuota = (new \App\Services\IzinKuotaService($dbLock))->cek($userId, $jenis, $tglMulai, $tglSelesai, 0, $kategoriCuti);
                if (!$cekKuota['ok']) {
                    $dbLock->transRollback();
                    return $this->respond(
                        false,
                        $cekKuota['pesan'] . ' Ulangi dengan force=1 bila memang disengaja.',
                        null,
                        null,
                        409
                    );
                }
            }
        } catch (\Throwable $e) {
            $dbLock->transRollback();
            log_message('error', 'Simpan izin manual gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyimpan izin manual.', null, null, 500);
        }

        // Get Guru ID
        $guru = $db->table('master_guru')->where('id_user', $userId)->get()->getRow();
        $idGuru = $guru ? $guru->id_guru : null;

        // Handle File Upload if provided (validasi mime + ukuran)
        $fileBukti = $this->request->getFile('bukti');
        $buktiName = null;
        if ($fileBukti && $fileBukti->isValid() && !$fileBukti->hasMoved()) {
            // Tipe dari ISI file (guessExtension), bukan header Content-Type
            // kiriman browser — dulu berkas skrip yang mengaku image/jpeg lolos,
            // lalu getRandomName() memakai MIME asli sehingga unggahannya bisa
            // berekstensi php di uploads/izin.
            if (!in_array(strtolower($fileBukti->guessExtension()), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return $this->respond(false, 'Bukti harus berupa PDF/JPG/PNG.');
            }
            if ($fileBukti->getSize() > 2 * 1024 * 1024) {
                return $this->respond(false, 'Ukuran bukti maksimal 2 MB.');
            }
            $uploadDir = FCPATH . 'uploads/izin/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $buktiName = $fileBukti->getRandomName();
            $fileBukti->move($uploadDir, $buktiName);
        }

        $status = $langsungApprove ? 'approved' : 'pending';

        $insertData = [
            'user_id' => $userId,
            'id_guru' => $idGuru,
            'tgl_mulai' => $tglMulai,
            'tgl_selesai' => $tglSelesai,
            'jenis' => $jenis,
            'kategori_cuti' => $kategoriCuti,
            'keterangan' => $keterangan,
            'status' => $status,
            'bukti' => !empty($buktiName) ? 'uploads/izin/' . $buktiName : null,
            'verified_by' => $langsungApprove ? session()->get('user_id') : null,
            'verified_at' => $langsungApprove ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $dbLock->table('absensi_izin')->insert($insertData);
        $permitId = $dbLock->insertID();
        $dbLock->transCommit();

        // If approved directly, update attendance records
        $dilewatiManual = [];
        if ($langsungApprove) {
            $settingModel = new \App\Models\AttendanceSettingModel();
            $setting = $settingModel->first();
            $pointIzin = $setting->point_izin ?? 0;
            $pointSakit = $setting->point_sakit ?? 0;

            $jenisUpper = strtoupper($jenis);
            if ($jenisUpper === 'SAKIT') {
                $point = $pointSakit;
                $statusMasuk = 'SAKIT';
            } elseif ($jenisUpper === 'CUTI') {
                $point = 0;
                $statusMasuk = 'CUTI';
            } elseif ($jenisUpper === 'DINAS' || strpos($jenisUpper, 'DINAS') !== false) {
                $point = (int)($setting->point_hadir_ontime ?? $setting->point_hadir ?? 10);
                // Samakan enum dengan approvePermit ('DINAS_APPROVED', bukan 'DINAS')
                $statusMasuk = 'DINAS_APPROVED';
            } else {
                $point = $pointIzin;
                $statusMasuk = 'IJIN';
            }

            $start = strtotime($tglMulai);
            $end = strtotime($tglSelesai);
            $attendanceModel = new AttendanceModel();

            // Definisi libur yang sama dengan approvePermit dan dengan
            // perhitungan kuota (IzinKuotaService). Salinan lokal di sini
            // bisa berbeda darinya sehingga input manual menulis baris
            // absensi pada hari yang guru tidak anggap hari kerja.
            $globalSetting = $this->dashboardModel->getSetting();
            $svcLiburManual = new \App\Services\IzinKuotaService($db);
            $punyaIzinIdManual = $db->fieldExists('izin_id', 'absensi_harian');
            $isNetralManual  = in_array($statusMasuk, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true);
            $dilewatiManual  = [];
            $bulanTersentuhManual = [];

            $db->transBegin();
            try {
            for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
                $date = date('Y-m-d', $i);

                if ($svcLiburManual->isLibur($date)) continue;

                $existing = $attendanceModel->where('user_id', $userId)->where('tgl', $date)->first();

                // Jangan timpa hari yang sudah ada scan asli guru — sama
                // seperti approvePermit, alasannya ada di sana.
                if ($isNetralManual && $existing && !empty($existing->jam_masuk)) {
                    $dilewatiManual[] = $date;
                    continue;
                }

                $bulanTersentuhManual[date('Y-m', $i)] = true;

                $attData = [
                    'user_id' => $userId,
                    'tgl' => $date,
                    'status_masuk' => $statusMasuk,
                    'status_pulang' => $statusMasuk,
                    'points' => $point,
                    'denda' => 0,
                    'keterangan' => $keterangan ?: "Input Manual Admin: {$jenis}"
                ];

                // Penanda asal supaya delete izin manual bisa mengembalikan
                // baris yang tepat, bukan menebak dari status saja.
                if ($punyaIzinIdManual) {
                    $attData['izin_id'] = (int) $permitId;
                }

                if ($existing) {
                    if ($isNetralManual) {
                        $attData['jam_masuk'] = null;
                        $attData['jam_pulang'] = null;
                    }
                    $attendanceModel->update($existing->id, $attData);
                    $logId = $existing->id;
                } else {
                    $attData['jam_masuk'] = null;
                    $attData['jam_pulang'] = null;
                    $logId = $attendanceModel->insert($attData);
                }
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($logId);
            }
            $db->transCommit();

            // Evaluasi dan sinkronisasi pembatalan surat teguran untuk SEMUA
            // bulan yang tersentuh (sebelumnya hanya bulan tgl_mulai).
            try {
                $penaltySvc = new \App\Services\AttendancePenaltyService();
                $periodeManual = array_keys($bulanTersentuhManual);
                if (empty($periodeManual)) {
                    $periodeManual = [date('Y-m', $start)];
                }
                foreach ($periodeManual as $ym) {
                    [$thM, $blM] = array_map('intval', explode('-', $ym));
                    $penaltySvc->syncSuratTeguranOnAttendanceChange($userId, $blM, $thM, $globalSetting);
                }
            } catch (\Throwable $ex) {}
            } catch (\Throwable $e) {
                $db->transRollback();
                // Rollback baris izin yang sudah ter-insert agar tidak yatim
                $db->table('absensi_izin')->where('id', $permitId)->delete();
                if (!empty($buktiName) && file_exists(FCPATH . 'uploads/izin/' . $buktiName)) {
                    @unlink(FCPATH . 'uploads/izin/' . $buktiName);
                }
                log_message('error', 'Save manual permit gagal: ' . $e->getMessage());
                return $this->respond(false, 'Gagal menyimpan izin manual.', null, null, 500);
            }
        }

        $pesan = 'Data izin/cuti berhasil disimpan' . ($langsungApprove ? ' dan absensi telah diperbarui' : '');
        if ($langsungApprove && !empty($dilewatiManual)) {
            $pesan .= ' (catatan: ' . count($dilewatiManual)
                . ' hari tidak diubah karena sudah ada scan kehadiran: '
                . implode(', ', $dilewatiManual) . ')';
        }

        // Absen manual atas nama guru = keputusan, bukan isian formulir: tanpa
        // jejak ini tidak tercatat admin mana yang membuatnya, dan apakah ia
        // sekaligus menyetujuinya (langsung_approve).
        try {
            (new AuditLogModel())->log('absensi_izin', (int) $permitId, $langsungApprove ? 'CREATE_APPROVE' : 'CREATE',
                (int) $userId, $this->getUserId(),
                null,
                [
                    'status'          => $status,
                    'jenis'           => $jenis,
                    'tgl_mulai'       => $tglMulai,
                    'tgl_selesai'     => $tglSelesai,
                    'hari_dilewati'   => $dilewatiManual ?? [],
                ],
                'Izin dibuat manual oleh admin' . ($langsungApprove ? ' + langsung disetujui' : ''));
        } catch (\Throwable $ex) {
            log_message('error', 'Audit saveManualPermit gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, $pesan);
    }

    /**
     * Delete Permit
     */
    public function deletePermit($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $db = \Config\Database::connect();
        $permit = $db->table('absensi_izin')->where('id', $id)->get()->getRow();
        if (!$permit) {
            return $this->respond(false, 'Data izin tidak ditemukan');
        }

        // Izin yang sudah approved menulis baris absensi_harian. Menghapus izin
        // tanpa rollback menyisakan status IJIN/SAKIT/CUTI yatim. Revert hanya
        // baris yang benar-benar ditulis pengajuan ini.
        $reverted = 0;
        $kept = 0;
        $poinYatimDihapus = 0;
        $permitStatus = (new \App\Services\AttendancePenaltyService())->normalizeIzinStatus((string) ($permit->jenis ?? ''));
        $wasApproved = strtolower(trim((string) ($permit->status ?? ''))) === 'approved';

        if ($wasApproved && $this->request->getPost('force') != '1') {
            return $this->respond(false, 'Izin ini sudah approved dan menulis absensi. Ulangi dengan konfirmasi (force=1) untuk menghapus + revert otomatis.', null, null, 409);
        }

        $db->transBegin();
        try {
            if ($wasApproved) {
                $start = strtotime((string) $permit->tgl_mulai);
                $end = strtotime((string) $permit->tgl_selesai);
                for ($i = $start; $i <= $end; $i = strtotime('+1 day', $i)) {
                    $date = date('Y-m-d', $i);
                    $row = $db->table('absensi_harian')
                        ->where('user_id', $permit->user_id)
                        ->where('tgl', $date)
                        ->get()->getRow();
                    if (!$row) continue;

                    // Penentuan "baris ini milik pengajuan ini".
                    //
                    // Hanya baris BERTANDA yang boleh dihapus begitu saja.
                    // Baris LAMA (izin_id NULL, ditulis sebelum migrasi)
                    // boleh ikut karena tidak punya penanda sama sekali —
                    // kalau tidak, menghapus izin yang sudah disetujui
                    // SEBELUM upgrade akan meninggalkan baris IJIN/SAKIT/
                    // CUTI yatim, yaitu justru bug yang revert ini awalnya
                    // dibuat untuk perbaiki.
                    if (! AttendanceModel::apakahMilikIzin($row, (int) $id, $permitStatus)) {
                        $kept++;
                        continue;
                    }

                    $db->table('absensi_harian')->where('id', $row->id)->delete();
                    if ($db->tableExists('absensi_poin_history')) {
                        $db->table('absensi_poin_history')->where('absensi_id', $row->id)->delete();
                    }
                    // Poin yang menempel pada baris yang baru dihapus ikut
                    // hilang. Tanpa ini poin_history menyimpan baris 'att-{id}'
                    // yatim: skor bulanan & leaderboard tetap memberi poin untuk
                    // absensi yang sudah tidak ada.
                    $db->table('poin_history')
                        ->where('user_id', (int) $permit->user_id)
                        ->whereIn('ref_id', [
                            'att-' . $row->id,
                            'att-' . $row->id . '-out',
                            'att-' . $row->id . '-daily',
                        ])->delete();
                    $poinYatimDihapus += $db->affectedRows();
                    $reverted++;
                }

                if ($poinYatimDihapus > 0) {
                    (new \App\Models\PoinHistoryModel())->updateTotalPoin((int) $permit->user_id);
                }
            }

            // If file exists, delete it
            if (!empty($permit->bukti)) {
                $bFile = basename((string) $permit->bukti);
                if (file_exists(FCPATH . 'uploads/izin/' . $bFile)) {
                    @unlink(FCPATH . 'uploads/izin/' . $bFile);
                } elseif (file_exists(FCPATH . ltrim((string) $permit->bukti, '/'))) {
                    @unlink(FCPATH . ltrim((string) $permit->bukti, '/'));
                }
            }

            $db->table('absensi_izin')->where('id', $id)->delete();
            $db->transCommit();

            // Hapus HADIR bisa melahirkan alpha (atau hapus ALPHA bisa
            // menggugurkan surat): sinkronkan surat per bulan tersentuh.
            if ($wasApproved && $reverted > 0) {
                try {
                    $blnDel = (int) date('n', $start);
                    $thnDel = (int) date('Y', $start);
                    $blnDel2 = (int) date('n', $end);
                    $thnDel2 = (int) date('Y', $end);
                    $svcDel = new \App\Services\AttendancePenaltyService();
                    $setDel = $this->dashboardModel->getSetting();
                    $svcDel->syncSuratTeguranOnAttendanceChange((int) $permit->user_id, $blnDel, $thnDel, $setDel);
                    if ($blnDel !== $blnDel2 || $thnDel !== $thnDel2) {
                        $svcDel->syncSuratTeguranOnAttendanceChange((int) $permit->user_id, $blnDel2, $thnDel2, $setDel);
                    }
                } catch (\Throwable $exSync) {}
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Delete permit gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menghapus izin.', null, null, 500);
        }

        // AUDIT LOG
        try {
            (new AuditLogModel())->log(
                'absensi_izin',
                $id,
                'DELETE',
                $permit->user_id,
                session()->get('user_id'),
                $permit,
                ['reverted_attendance' => $reverted, 'kept_attendance' => $kept],
                'Izin Dihapus Admin'
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
        }

        $msg = 'Data pengajuan izin berhasil dihapus';
        if ($wasApproved) {
            $msg .= " ({$reverted} baris absensi otomatis direvert" . ($kept ? ", {$kept} baris berisi check-in nyata dibiarkan" : '') . ')';
        }

        return $this->respond(true, $msg);
    }



    /**
     * Holiday Management (Hari Libur)
     */
    public function holidays()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $this->patchAbsensiLiburTable();

        $db = \Config\Database::connect();
        $holidays = $db->table('absensi_hari_libur')->orderBy('tanggal', 'DESC')->get()->getResult();

        // Get setting & active year/semester
        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        return view('admin/presensi/holidays', [
            'title' => 'Hari Libur Absensi',
            'judul' => 'Hari Libur',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'holidays' => $holidays
        ]);
    }

    public function saveHoliday()
    {
        if (!$this->isAdmin())
            return $this->respond(false, 'Akses ditolak', null, null, 403);

        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $isRange = $this->request->getPost('is_range') == '1';
        $keterangan = trim($this->request->getPost('keterangan') ?? '');

        if (empty($keterangan)) {
            return $this->respond(false, 'Keterangan hari libur wajib diisi');
        }

        if ($id) {
            $tanggal = $this->request->getPost('tanggal');
            if (empty($tanggal)) {
                return $this->respond(false, 'Tanggal hari libur wajib diisi');
            }
            $db->table('absensi_hari_libur')->where('id', $id)->update([
                'tanggal' => $tanggal,
                'keterangan' => $keterangan
            ]);
            return $this->respond(true, 'Data hari libur berhasil diperbarui');
        }

        if ($isRange) {
            $tglMulai = $this->request->getPost('tanggal_mulai');
            $tglSelesai = $this->request->getPost('tanggal_selesai');

            if (empty($tglMulai) || empty($tglSelesai)) {
                return $this->respond(false, 'Tanggal mulai dan tanggal selesai wajib diisi');
            }

            $start = strtotime($tglMulai);
            $end = strtotime($tglSelesai);

            if ($start > $end) {
                return $this->respond(false, 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai');
            }

            $inserted = 0;
            $current = $start;
            while ($current <= $end) {
                $curDate = date('Y-m-d', $current);
                $exists = $db->table('absensi_hari_libur')->where('tanggal', $curDate)->countAllResults();
                if ($exists == 0) {
                    $db->table('absensi_hari_libur')->insert([
                        'tanggal' => $curDate,
                        'keterangan' => $keterangan
                    ]);
                    $inserted++;
                } else {
                    $db->table('absensi_hari_libur')->where('tanggal', $curDate)->update([
                        'keterangan' => $keterangan
                    ]);
                    $inserted++;
                }
                $current = strtotime('+1 day', $current);
            }

            return $this->respond(true, "Berhasil menyimpan {$inserted} hari libur untuk rentang tanggal.");
        } else {
            $tanggal = $this->request->getPost('tanggal');
            if (empty($tanggal)) {
                return $this->respond(false, 'Tanggal hari libur wajib diisi');
            }

            $exists = $db->table('absensi_hari_libur')->where('tanggal', $tanggal)->countAllResults();
            if ($exists > 0) {
                $db->table('absensi_hari_libur')->where('tanggal', $tanggal)->update([
                    'keterangan' => $keterangan
                ]);
            } else {
                $db->table('absensi_hari_libur')->insert([
                    'tanggal' => $tanggal,
                    'keterangan' => $keterangan
                ]);
            }

            return $this->respond(true, 'Data hari libur berhasil disimpan');
        }
    }

    public function deleteHoliday()
    {
        if (!$this->isAdmin())
            return $this->respond(false, 'Akses ditolak', null, null, 403);

        $id = $this->request->getPost('id');
        $db = \Config\Database::connect();
        $db->table('absensi_hari_libur')->where('id', $id)->delete();

        return $this->respond(true, 'Data hari libur berhasil dihapus');
    }

    public function syncHolidays()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        // '0' dan '' sama-sama falsy: pakai ?: membuat year=0 diam-diam jatuh ke
        // tahun ini lalu menarik data dari internet.
        $tahunDiminta = trim((string) ($this->request->getPost('year') ?? ''));
        $year         = $tahunDiminta === '' ? (int) date('Y') : (int) $tahunDiminta;
        $tahunMin     = (int) date('Y') - 1;
        $tahunMaks    = (int) date('Y') + 5;
        if ($year < $tahunMin || $year > $tahunMaks) {
            return $this->respond(false, 'Tahun tidak valid. Pilih antara ' . $tahunMin . ' dan ' . $tahunMaks . '.');
        }

        $client = \Config\Services::curlrequest();
        $json = null;

        // Try primary API
        $apis = [
            "https://dayoffapi.vercel.app/api?year={$year}",
            "https://api-harilibur.vercel.app/api?year={$year}"
        ];

        foreach ($apis as $apiUrl) {
            try {
                $response = $client->get($apiUrl, ['timeout' => 8]);
                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody());
                    if (is_array($data) && count($data) > 0) {
                        $json = $data;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', "Failed fetching from {$apiUrl}: " . $e->getMessage());
            }
        }

        if (empty($json) || !is_array($json)) {
            return $this->respond(false, "Gagal mengambil data hari libur nasional untuk tahun {$year}. Pastikan koneksi internet server aktif.");
        }

        try {
            $db = \Config\Database::connect();
            $db->transBegin();

            $addedCount   = 0;
            $updatedCount = 0;
            $dilewati     = 0;

            foreach ($json as $holiday) {
                if (!is_object($holiday) && !is_array($holiday)) {
                    $dilewati++;
                    continue;
                }
                $item = (array) $holiday;

                // Support variations in field names across API versions
                $tanggal    = trim((string) ($item['tanggal'] ?? $item['holiday_date'] ?? ''));
                $keterangan = trim((string) ($item['keterangan'] ?? $item['holiday_name'] ?? ''));
                $isCuti     = !empty($item['is_cuti']);

                // Payload API pihak ketiga bukan sumber terpercaya: kolomnya
                // DATE NOT NULL + VARCHAR(255), jadi bentuknya wajib benar dan
                // tahunnya harus sama dengan yang diminta.
                if ($tanggal === '' || $keterangan === ''
                    || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tanggal, $m)
                    || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                    || (int) $m[1] !== $year) {
                    $dilewati++;
                    continue;
                }

                $keterangan = trim(preg_replace('/\s+/u', ' ', $keterangan));
                $ketFinal   = $keterangan . ($isCuti && stripos($keterangan, 'cuti') === false ? ' (Cuti Bersama)' : '');
                $ketFinal   = substr($ketFinal, 0, 255);

                $existing = $db->table('absensi_hari_libur')
                    ->where('tanggal', $tanggal)
                    ->get()
                    ->getRowArray();

                if (!$existing) {
                    $tertulis = $db->table('absensi_hari_libur')->insert([
                        'tanggal'    => $tanggal,
                        'keterangan' => $ketFinal,
                    ]);
                } else {
                    $tertulis = $db->table('absensi_hari_libur')
                        ->where('id', (int) $existing['id'])
                        ->update(['keterangan' => $ketFinal]);
                }

                if ($tertulis === false) {
                    $dilewati++;
                    log_message('error', '[syncHolidays] simpan ' . $tanggal . ' gagal: ' . json_encode($db->error()));
                    continue;
                }

                if ($existing) {
                    $updatedCount++;
                } else {
                    $addedCount++;
                }
            }

            if ($addedCount + $updatedCount === 0) {
                $db->transRollback();
                return $this->respond(false, "Tidak ada hari libur tahun {$year} yang bisa disimpan dari sumber. {$dilewati} entri dilewati.");
            }

            $db->transCommit();
            if ($db->transStatus() === false) {
                log_message('error', '[syncHolidays] transCommit gagal: ' . json_encode($db->error()));
                return $this->respond(false, 'Penyimpanan data hari libur gagal, perubahan dibatalkan.');
            }

            $msg = "Sinkronisasi libur nasional tahun {$year} selesai. Ditambahkan: {$addedCount}, Diperbarui: {$updatedCount}.";
            if ($dilewati > 0) {
                $msg .= " {$dilewati} entri dilewati karena formatnya tidak dikenali.";
            }

            return $this->respond($dilewati === 0, $msg);
        } catch (\Throwable $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            log_message('error', '[syncHolidays] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond(false, 'Terjadi kesalahan saat menyimpan data hari libur. Detail ada di log aplikasi.');
        }
    }

    private function getAlphaCounts($bulan, $tahun, $userId = null)
    {
        $db = \Config\Database::connect();
        
        // Handle input format
        if (strpos($bulan, '-') !== false) {
            // Already YYYY-MM
            $period = $bulan;
        } else {
            $bulanStr = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $period = "{$tahun}-{$bulanStr}";
        }
        
        $userFilter = "";
        if ($userId) {
            $userFilter = "AND u.id = " . (int)$userId;
        }

        // Hari libur mingguan dari AttendancePolicy (absensi_setting, nama hari
        // ternormalisasi) — bukan dari tabel `setting` yang tidak punya kolom itu.
        $dayMap = ['Sun' => 1, 'Mon' => 2, 'Tue' => 3, 'Wed' => 4, 'Thu' => 5, 'Fri' => 6, 'Sat' => 7];
        $liburDays = [];
        foreach (\App\Services\AttendancePolicy::hariLiburMingguan() as $dName) {
            if (isset($dayMap[$dName])) {
                $liburDays[] = $dayMap[$dName];
            }
        }
        // 1 = "tidak pernah libur mingguan", supaya NOT (...) selalu benar.
        $liburQuery = $liburDays ? 'DAYOFWEEK(d.date) IN (' . implode(',', $liburDays) . ')' : '1 = 0';

        // Exclude jadwal libur & approved leave (isi_libur & izin sah tidak dihitung alpha)
        $hasJadwalTable = $db->tableExists('absensi_jadwal_kerja');
        $hasIzinTable   = $db->tableExists('absensi_izin');
        $jadwalJoin = $hasJadwalTable ? "LEFT JOIN absensi_jadwal_kerja jk ON jk.hari = CASE WHEN DAYOFWEEK(calendar.date)=1 THEN 7 ELSE DAYOFWEEK(calendar.date)-1 END" : "";
        $jadwalWhere = $hasJadwalTable ? "AND (jk.is_libur IS NULL OR jk.is_libur = 0)" : "";
        $izinWhere = $hasIzinTable ? "AND NOT EXISTS (SELECT 1 FROM absensi_izin iz WHERE iz.user_id = calendar.user_id AND iz.status='approved' AND iz.tgl_mulai <= calendar.date AND iz.tgl_selesai >= calendar.date)" : "";

        $sql = "
            SELECT calendar.user_id, COUNT(*) as count 
            FROM (
                SELECT u.id as user_id, d.date
                FROM users u
                CROSS JOIN (
                    SELECT ADDDATE('{$period}-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) AS date
                    FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
                         (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4
                ) d
                JOIN users_groups ug ON ug.user_id = u.id AND ug.group_id = 2
                WHERE d.date BETWEEN '{$period}-01' AND LAST_DAY('{$period}-01')
                AND u.active = 1
                AND d.date <= '" . \App\Services\AttendancePolicy::hariIniWita() . "'
                AND NOT ({$liburQuery})
                {$userFilter}
            ) calendar
            LEFT JOIN absensi_harian h ON h.user_id = calendar.user_id AND h.tgl = calendar.date
            LEFT JOIN absensi_hari_libur hl ON hl.tanggal = calendar.date
            {$jadwalJoin}
            WHERE h.id IS NULL AND hl.id IS NULL {$jadwalWhere} {$izinWhere}
            GROUP BY calendar.user_id
        ";

        try {
            $alphaRaw = $db->query($sql)->getResult();
        } catch (\Throwable $e) {
            $alphaRaw = [];
        }
        
        $alphaMap = [];
        foreach ($alphaRaw as $a) {
            $alphaMap[$a->user_id] = $a->count;
        }

        if ($userId) {
            return $alphaMap[$userId] ?? 0;
        }
        return $alphaMap;
    }

    private function getYTDDendaStats($tahun)
    {
        return (new \App\Services\RekapPdfService())->getYTDDendaStats($tahun);
    }

    /**
     * Sanitasi input hari libur mingguan: hanya nama hari valid (Sun..Sat),
     * gabung koma. Fallback 'Sun' bila kosong/tak valid.
     */
    /**
     * Normalisasi jam HH:MM[:SS] → HH:MM:SS, fallback bila tak valid.
     */
    private function normalizeScanTime($input, string $fallback): string
    {
        $s = trim((string) ($input ?? ''));
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/', $s, $m)) {
            return $m[1] . ':' . $m[2] . ':' . ($m[4] ?? '00');
        }
        return $fallback;
    }

    /**
     * Turunkan absensi_setting.hari_libur_mingguan dari
     * absensi_jadwal_kerja.is_libur (sumber kebenaran tunggal).
     *
     * Dipanggil setiap kali jadwal mingguan disimpan supaya kolom lama —
     * yang masih dibaca sebagian kode lama — tidak pernah menyimpang dan
     * membuat satu hari dianggap libur di satu layar, hari kerja di layar lain.
     */
    /**
     * Nilai untuk kolom `notif_kehadiran_rendah` dari form.
     *
     * Dua sumber, dalam urutan prioritas:
     *   1. Field `notif_kehadiran_rendah` — dipakai kalau suatu saat
     *      form-nya ditambah.
     *   2. Field `alert_kehadiran_enabled` — checkbox yang BENAR-BENAR
     *      ada di form sekarang (Presensi/Setting).
     *
     * Kenapa tidak sekadar `getPost(...) ? 1 : 0`: field yang pertama
     * tidak ada di form mana pun, jadi nilainya selalu null dan checkbox
     * kedua selalu tertimpa 0 setiap kali admin menekan Simpan.
     * Akibatnya CronController::checkKehadiranThreshold() melewati
     * pemeriksaan kehadiran rendah selamanya, tanpa ada UI untuk
     * menghidupkannya kembali.
     */
    private function kehadiranRendahFromRequest(): int
    {
        if ($this->request->getPost('notif_kehadiran_rendah') !== null) {
            return $this->request->getPost('notif_kehadiran_rendah') ? 1 : 0;
        }

        return $this->request->getPost('alert_kehadiran_enabled') ? 1 : 0;
    }

    /**
     * Kanal WhatsApp aktif. Nilai tak dikenal (atau select yang tidak ada
     * di form) diturunkan ke 'off' supaya tidak pernah menembak gateway
     * yang tidak sengaja.
     */
    private function normalizeWaModeInput(): string
    {
        $mode = strtolower(trim((string) ($this->request->getPost('wa_gateway_mode') ?? '')));
        return in_array($mode, ['off', 'openwa', 'fonnte'], true) ? $mode : 'off';
    }

    /**
     * Buang cache resolusi sesi OpenWA.
     *
     * Id sesi di-cache 60 detik supaya setiap notifikasi tidak memanggil
     * GET /sessions. Setelah admin mengganti Base URL / API Key / nama sesi,
     * cache lama bisa menunjuk sesi di gateway yang salah.
     */
    private function flushOpenwaCache(): void
    {
        (new \App\Services\OpenWAService())->flushCache();
    }

    private function syncWeeklyHolidayFromJadwal($db): void
    {
        try {
            if (!$db->tableExists('absensi_jadwal_kerja')) {
                return;
            }
            $isoDariNama = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7];
            $jadwal = [];
            foreach ($db->table('absensi_jadwal_kerja')->select('hari, is_libur')->get()->getResult() as $r) {
                $jadwal[(int) $r->hari] = ((int) $r->is_libur === 1);
            }
            $turunan = [];
            foreach ($isoDariNama as $nama => $iso) {
                if (!empty($jadwal[$iso])) {
                    $turunan[] = $nama;
                }
            }
            $csv = implode(',', $turunan);
            foreach (['absensi_setting', 'pengaturan'] as $tabel) {
                if ($db->tableExists($tabel) && $db->fieldExists('hari_libur_mingguan', $tabel)) {
                    $db->table($tabel)->where('id', 1)->update(['hari_libur_mingguan' => $csv]);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::syncWeeklyHolidayFromJadwal] ' . $e->getMessage());
        }
    }

    /**
     * Shift kerja per guru: daftar shift + penugasan.
     */
    public function shiftPage()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $this->patchAbsensiShiftTables();
        $db = \Config\Database::connect();

        $shifts = $db->table('absensi_shift')->orderBy('jam_masuk', 'ASC')->get()->getResult();
        $anggota = [];
        $counts = [];
        if ($db->tableExists('absensi_shift_anggota')) {
            $rows = $db->table('absensi_shift_anggota a')
                ->select('a.*, s.nama as shift_nama, COALESCE(g.nama_guru, u.username) as nama_guru')
                ->join('absensi_shift s', 's.id = a.shift_id')
                ->join('users u', 'u.id = a.user_id', 'left')
                ->join('master_guru g', 'g.id_user = u.id', 'left')
                ->orderBy('s.jam_masuk', 'ASC')
                ->orderBy('nama_guru', 'ASC')
                ->get()->getResult();
            foreach ($rows as $r) {
                $anggota[$r->shift_id][] = $r;
                $counts[$r->shift_id] = ($counts[$r->shift_id] ?? 0) + 1;
            }
        }

        $guruList = [];
        if ($db->tableExists('users_groups')) {
            $guruList = $db->table('users u')
                ->select('u.id, COALESCE(g.nama_guru, u.username) as nama')
                ->join('master_guru g', 'g.id_user = u.id', 'left')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where('u.active', 1)
                ->orderBy('nama', 'ASC')
                ->get()->getResult();
        }

        $appSetting = $this->dashboardModel->getSetting();

        return view('admin/presensi/shift', [
            'title' => 'Shift Kerja Guru',
            'judul' => 'Shift Kerja Guru',
            'setting' => $appSetting ?: (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'],
            'tp_active' => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'user' => $this->getUser(),
            'shifts' => $shifts,
            'anggota' => $anggota,
            'counts' => $counts,
            'guruList' => $guruList,
        ]);
    }

    public function saveShift()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $this->patchAbsensiShiftTables();
        $db = \Config\Database::connect();

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nama = trim((string) ($this->request->getPost('nama') ?? ''));
        $jamMasuk = (string) ($this->request->getPost('jam_masuk') ?? '');
        $jamPulang = (string) ($this->request->getPost('jam_pulang') ?? '');
        $graceRaw = trim((string) ($this->request->getPost('grace_menit') ?? ''));

        if ($nama === '' || mb_strlen($nama) > 100) {
            return $this->respond(false, 'Nama shift wajib diisi (maks 100 karakter).');
        }
        // Cegah nama shift kembar (case-insensitive, abaikan diri sendiri saat edit).
        $dupSql = 'SELECT COUNT(*) AS c FROM `absensi_shift` WHERE LOWER(nama) = ?' . ($id > 0 ? ' AND id != ?' : '');
        $dupParams = $id > 0 ? [strtolower($nama), $id] : [strtolower($nama)];
        $dupCount = (int) $db->query($dupSql, $dupParams)->getRow()->c;
        if ($dupCount > 0) {
            return $this->respond(false, 'Nama shift "' . $nama . '" sudah dipakai. Gunakan nama lain.');
        }
        foreach (['jam_masuk' => $jamMasuk, 'jam_pulang' => $jamPulang] as $label => $val) {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $val)) {
                return $this->respond(false, "Format $label tidak valid (HH:MM).");
            }
        }
        // Shift malam/overnight belum didukung threshold (jam_pulang harus >
        // jam_masuk di hari yang sama; dulu: lolos lalu merusak status).
        $masukLen = strlen($jamMasuk) === 5 ? $jamMasuk . ':00' : $jamMasuk;
        $pulangLen = strlen($jamPulang) === 5 ? $jamPulang . ':00' : $jamPulang;
        if (strtotime($pulangLen) <= strtotime($masukLen)) {
            return $this->respond(false, 'Jam pulang harus setelah jam masuk (shift overnight belum didukung).');
        }
        $grace = null;
        if ($graceRaw !== '') {
            if (!ctype_digit($graceRaw) || (int) $graceRaw > 120) {
                return $this->respond(false, 'Toleransi 0–120 menit, atau kosongkan untuk ikut global.');
            }
            $grace = (int) $graceRaw;
        }

        $data = [
            'nama' => $nama,
            'jam_masuk' => $masukLen,
            'jam_pulang' => $pulangLen,
            'grace_menit' => $grace,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $exists = $db->table('absensi_shift')->where('id', $id)->get()->getRow();
            if (!$exists) {
                return $this->respond(false, 'Shift tidak ditemukan.');
            }
            $db->table('absensi_shift')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('absensi_shift')->insert($data);
            $id = $db->insertID();
        }

        return $this->respond(true, 'Shift berhasil disimpan', ['id' => $id]);
    }

    public function deleteShift()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $this->patchAbsensiShiftTables();
        $db = \Config\Database::connect();
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->respond(false, 'ID shift tidak valid.');
        }

        $shift = $db->table('absensi_shift')->where('id', $id)->get()->getRow();
        if (!$shift) {
            return $this->respond(false, 'Shift tidak ditemukan.');
        }
        $memberCount = $db->table('absensi_shift_anggota')->where('shift_id', $id)->countAllResults();

        $db->transBegin();
        try {
            $db->table('absensi_shift_anggota')->where('shift_id', $id)->delete();
            $db->table('absensi_shift')->where('id', $id)->delete();
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->respond(false, 'Gagal menghapus shift.', null, null, 500);
        }

        $msg = 'Shift "' . ($shift->nama ?? '') . '" dihapus';
        if ($memberCount > 0) $msg .= " beserta {$memberCount} penugasannya";
        return $this->respond(true, $msg . '.');
    }

    public function assignShift()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $this->patchAbsensiShiftTables();
        $db = \Config\Database::connect();

        $shiftId = (int) ($this->request->getPost('shift_id') ?? 0);
        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $hari = (int) ($this->request->getPost('hari') ?? 0);

        if ($shiftId <= 0 || $userId <= 0) {
            return $this->respond(false, 'Shift dan guru wajib dipilih.');
        }
        if ($hari < 0 || $hari > 7) {
            return $this->respond(false, 'Hari tidak valid (0=semua hari, 1–7=Senin–Minggu).');
        }
        $shift = $db->table('absensi_shift')->where('id', $shiftId)->where('active', 1)->get()->getRow();
        if (!$shift) {
            return $this->respond(false, 'Shift tidak ditemukan / nonaktif.');
        }
        $user = $db->table('users')->where('id', $userId)->get()->getRow();
        if (!$user) {
            return $this->respond(false, 'User tidak ditemukan.');
        }
        // Hanya guru (group 2) yang memakai jadwal shift; dropdown sudah
        // memfilter, tapi POST mentah bisa membawa user lain.
        if ($db->tableExists('users_groups')) {
            $isGuru = $db->table('users_groups')->where('user_id', $userId)->where('group_id', 2)->countAllResults() > 0;
            if (!$isGuru) {
                return $this->respond(false, 'Hanya guru yang bisa ditugaskan ke shift.');
            }
        }
        $dup = $db->table('absensi_shift_anggota')
            ->where('shift_id', $shiftId)->where('user_id', $userId)->where('hari', $hari)
            ->countAllResults();
        if ($dup > 0) {
            return $this->respond(false, 'Penugasan ini sudah ada.');
        }
        // Tolak konflik: satu user tidak boleh punya 2 shift berbeda yang
        // tumpang tindih (hari sama, atau salah satunya hari=0/semua hari).
        // Resolver diam-diam memakai shift id terkecil → membingungkan.
        $conflict = $db->query(
            'SELECT a.hari, s.nama FROM absensi_shift_anggota a JOIN absensi_shift s ON s.id = a.shift_id WHERE a.user_id = ? AND a.shift_id != ? AND (a.hari = ? OR a.hari = 0 OR ? = 0) LIMIT 1',
            [$userId, $shiftId, $hari, $hari]
        )->getRow();
        if ($conflict) {
            $namaHari = [0 => 'semua hari', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            $hariTxt = $namaHari[$hari] ?? ('hari ' . $hari);
            return $this->respond(false, "Bentrok dengan shift \"{$conflict->nama}\" untuk {$hariTxt}. Lepaskan dulu penugasan lama.");
        }

        try {
            $db->table('absensi_shift_anggota')->insert([
                'shift_id' => $shiftId,
                'user_id' => $userId,
                'hari' => $hari,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Race (klik ganda): UNIQUE(user_id, hari, shift_id) → pesan ramah
            if (stripos($e->getMessage(), 'uplicate') !== false || (int) $e->getCode() === 1062) {
                return $this->respond(false, 'Penugasan ini sudah ada.');
            }
            throw $e;
        }

        return $this->respond(true, 'Guru ditugaskan ke shift.');
    }

    public function unassignShift()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $this->patchAbsensiShiftTables();
        $db = \Config\Database::connect();
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->respond(false, 'ID penugasan tidak valid.');
        }
        $exists = $db->table('absensi_shift_anggota')->where('id', $id)->get()->getRow();
        if (!$exists) {
            return $this->respond(false, 'Penugasan shift tidak ditemukan.');
        }
        $db->table('absensi_shift_anggota')->where('id', $id)->delete();

        return $this->respond(true, 'Penugasan shift dihapus.');
    }

    /**
     * Aktif/nonaktif shift (shift nonaktif diabaikan resolver & tak bisa ditugaskan).
     */
    public function toggleShift()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $this->patchAbsensiShiftTables();
        $db = \Config\Database::connect();
        $id = (int) ($this->request->getPost('id') ?? 0);
        $active = $this->request->getPost('active') ? 1 : 0;
        if ($id <= 0) {
            return $this->respond(false, 'ID shift tidak valid.');
        }
        $exists = $db->table('absensi_shift')->where('id', $id)->get()->getRow();
        if (!$exists) {
            return $this->respond(false, 'Shift tidak ditemukan.');
        }
        $db->table('absensi_shift')->where('id', $id)->update([
            'active' => $active,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond(true, $active ? 'Shift diaktifkan.' : 'Shift dinonaktifkan (resolver mengabaikannya).');
    }

    private function patchAbsensiShiftTables()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists('absensi_shift')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
                'jam_masuk' => ['type' => 'TIME', 'default' => '07:30:00'],
                'jam_pulang' => ['type' => 'TIME', 'default' => '15:00:00'],
                'grace_menit' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->createTable('absensi_shift', true);
        } else {
            // Tabel warisan bisa ada dengan struktur lama (tanpa kolom nama, dst.)
            // → lengkapi kolom yang hilang agar query tidak 500.
            $shiftCols = [
                'nama' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'jam_masuk' => ['type' => 'TIME', 'null' => true],
                'jam_pulang' => ['type' => 'TIME', 'null' => true],
                'grace_menit' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ];
            foreach ($shiftCols as $col => $def) {
                if (!$db->fieldExists($col, 'absensi_shift')) {
                    try {
                        $forge->addColumn('absensi_shift', [$col => $def]);
                    } catch (\Throwable $e) {
                        log_message('error', "patch absensi_shift.$col gagal: " . $e->getMessage());
                    }
                }
            }
        }
        if (!$db->tableExists('absensi_shift_anggota')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'shift_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'hari' => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addUniqueKey(['user_id', 'hari', 'shift_id']);
            $forge->addKey('shift_id');
            $forge->createTable('absensi_shift_anggota', true);
        } else {
            // Pastikan unique key pencegah double-assign ada di tabel lama
            try {
                $hasUq = $db->query("SHOW INDEX FROM `absensi_shift_anggota` WHERE Key_name = 'user_id'")->getNumRows() > 0
                    || $db->query("SHOW INDEX FROM `absensi_shift_anggota` WHERE Non_unique = 0 AND Column_name = 'user_id'")->getNumRows() > 0;
                if (!$hasUq) {
                    // Bersihkan duplikat dulu agar ADD UNIQUE tidak gagal
                    $db->query('DELETE a FROM `absensi_shift_anggota` a INNER JOIN `absensi_shift_anggota` b ON a.user_id = b.user_id AND a.hari = b.hari AND a.shift_id = b.shift_id AND a.id > b.id');
                    $db->query('ALTER TABLE `absensi_shift_anggota` ADD UNIQUE KEY `user_id` (user_id, hari, shift_id)');
                }
            } catch (\Throwable $e) {
                log_message('error', 'patch unique absensi_shift_anggota gagal: ' . $e->getMessage());
            }
        }
    }

    /**
     * Lazy migration kolom penomoran Surat Teguran pada tabel `setting`
     * (lihat migration AddSuratTeguranNomorSetting).
     */
    private function patchSettingNomorTable()
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('setting')) {
                return;
            }

            $wanted = [
                'kode_sekolah' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => \App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH],
                'surat_teguran_format' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => \App\Libraries\SuratNomor::DEFAULT_FORMAT],
                'surat_teguran_padding' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => \App\Libraries\SuratNomor::DEFAULT_PADDING],
            ];

            $add = [];
            foreach ($wanted as $col => $def) {
                if (!$db->fieldExists($col, 'setting')) {
                    $add[$col] = $def;
                }
            }
            if ($add !== []) {
                \Config\Database::forge()->addColumn('setting', $add);
            }

            \App\Libraries\SuratNomor::flushCache();
        } catch (\Throwable $e) {
            log_message('error', 'patchSettingNomorTable gagal: ' . $e->getMessage());
        }
    }

    private function patchAbsensiSettingTable()
    {
        $db = \Config\Database::connect();
        // Skip if table doesn't exist yet
        if (!$db->tableExists('absensi_setting')) {
            return;
        }

        $forge = \Config\Database::forge();

        if (!$db->fieldExists('foto_wajib', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'foto_wajib' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => true,
                    'after' => 'radius_meter'
                ]
            ]);
        }

        if (!$db->fieldExists('libur_kalender', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'libur_kalender' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => true,
                    'after' => 'radius_meter'
                ]
            ]);
        }

        // Kolom resmi hari libur mingguan (dulu: dibaca di mana-mana tapi tidak
        // pernah ada di skema/allowedFields → selalu fallback 'Sun').
        if (!$db->fieldExists('hari_libur_mingguan', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'hari_libur_mingguan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'Sun',
                    'null' => true,
                ]
            ]);
        }

        if (!$db->fieldExists('updated_at', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ]
            ]);
        }

        if (!$db->fieldExists('point_telat', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_telat' => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            ]);
        }

        if (!$db->fieldExists('point_alpa', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_alpa' => ['type' => 'INT', 'constraint' => 11, 'default' => 5],
            ]);
        }

        if (!$db->fieldExists('denda_per_point', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'denda_per_point' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1000],
            ]);
        }

        if (!$db->fieldExists('max_denda_bulan', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'max_denda_bulan' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 50000]
            ]);
        }

        if (!$db->fieldExists('grace_period', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'grace_period' => ['type' => 'INT', 'constraint' => 11, 'default' => 15, 'null' => true]
            ]);
        }

        if (!$db->fieldExists('point_pulang_awal', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_pulang_awal' => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'null' => true]
            ]);
        }

        // Penalti poin guru yang tidak absen pulang (migrasi 20261105000000).
        // Default 5 = point_alpa; watermark lupa_pulang_dari_tgl yang masih
        // NULL berarti cron belum pernah berjalan sejak fitur ini ada, jadi
        // ia memasang dirinya pada tanggal hari itu alih-alih memotong
        // seluruh hari yang historisnya sudah terbuka.
        if (!$db->fieldExists('point_lupa_pulang', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_lupa_pulang'    => ['type' => 'INT', 'constraint' => 11, 'default' => 5, 'null' => true],
                'lupa_pulang_dari_tgl' => ['type' => 'DATE', 'null' => true, 'default' => null],
            ]);
        }

        if (!$db->fieldExists('point_hadir', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_hadir' => ['type' => 'INT', 'constraint' => 11, 'default' => 10, 'null' => true],
                'point_izin'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => true],
                'point_sakit' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => true],
                'point_cuti'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => true]
            ]);
        }

        if (!$db->fieldExists('denda_alpha_per_hari', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'denda_alpha_per_hari' => ['type' => 'INT', 'constraint' => 11, 'default' => 5000, 'null' => true],
            ]);
        }

        // Denda siswa (migrasi 20261106000000). Default 0 = fitur mati: tarif
        // pada instalasi lama harus tetap nol sampai sekolah sendiri
        // mengisinya, bukan ikut menagih riwayat absensi.
        if (!$db->fieldExists('denda_alpha_siswa_per_hari', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'denda_alpha_siswa_per_hari' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => true],
            ]);
        }

        if (!$db->fieldExists('denda_bolos_siswa_per_hari', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'denda_bolos_siswa_per_hari' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => true],
            ]);
        }

        if (!$db->fieldExists('max_denda_siswa_bulan', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'max_denda_siswa_bulan' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => true],
            ]);
        }

        // DEPRECATED: kolom progresif alpa tidak lagi dipakai (denda kini flat).
        // Blok dibiarkan kosong agar instalasi lama tetap kompatibel tanpa menambah kolom baru.

        if (!$db->fieldExists('denda_tier_1', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'denda_tier_1' => ['type' => 'INT', 'constraint' => 11, 'default' => 100, 'null' => true],
                'denda_tier_2' => ['type' => 'INT', 'constraint' => 11, 'default' => 200, 'null' => true],
                'denda_tier_3' => ['type' => 'INT', 'constraint' => 11, 'default' => 500, 'null' => true],
            ]);
        }

        // Kolom standar SMA: persentase minimum kehadiran dan alert
        if (!$db->fieldExists('min_kehadiran_persen', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'min_kehadiran_persen'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 90.00, 'null' => true],
                'alert_kehadiran_enabled' => ['type' => 'TINYINT', 'constraint' => 1,     'default' => 1,     'null' => true],
                'notif_kehadiran_rendah'  => ['type' => 'TINYINT', 'constraint' => 1,     'default' => 1,     'null' => true],
            ]);
        }

        // WFH settings
        if (!$db->fieldExists('wfh_enabled', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'wfh_enabled'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => true],
                'wfh_mulai'         => ['type' => 'TIME',    'default' => '08:00:00', 'null' => true],
                'wfh_selesai'       => ['type' => 'TIME',    'default' => '16:00:00', 'null' => true],
                'wfh_max_per_bulan' => ['type' => 'INT',     'constraint' => 3, 'default' => 5, 'null' => true],
                'wfh_wajib_wajah'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => true],
            ]);
        }

        // Syarat jurnal mengajar sebelum absen pulang
        if (!$db->fieldExists('wajib_jurnal_sebelum_pulang', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'wajib_jurnal_sebelum_pulang' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => true],
            ]);
        }

        // Jurnal harus sudah divalidasi piket sebelum absen pulang (opt-in)
        if (!$db->fieldExists('wajib_jurnal_tervalidasi_sebelum_pulang', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'wajib_jurnal_tervalidasi_sebelum_pulang' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => true],
            ]);
        }

        // Telegram & WA notification columns
        if (!$db->fieldExists('telegram_bot_token', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'telegram_bot_token' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'telegram_chat_id'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);
        }
        if (!$db->fieldExists('wa_gateway_url', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'wa_gateway_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'wa_token'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'wa_target_number'  => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true],
            ]);
        }

        // Poin lembur per jam
        if (!$db->fieldExists('point_lembur_per_jam', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_lembur_per_jam' => ['type' => 'INT', 'constraint' => 11, 'default' => 5, 'null' => true],
            ]);
        }

        // Google OAuth Integration
        if (!$db->fieldExists('google_client_id', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'google_client_id'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'google_client_secret' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'google_login_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => true],
            ]);
        }

        // Biometric Face Match Threshold & Mode
        if (!$db->fieldExists('face_threshold', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'face_threshold' => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0.72, 'null' => true],
            ]);
        }
        if (!$db->fieldExists('face_match_mode', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'face_match_mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ai_match', 'null' => true],
            ]);
        }

        if (!$db->fieldExists('point_tier_1', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'point_tier_1' => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'null' => true],
                'point_tier_2' => ['type' => 'INT', 'constraint' => 11, 'default' => 2, 'null' => true],
                'point_tier_3' => ['type' => 'INT', 'constraint' => 11, 'default' => 5, 'null' => true],
            ]);
        }

        if (!$db->fieldExists('reward_point_tier_1', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'reward_point_tier_1' => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'null' => true],
                'reward_point_tier_2' => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'null' => true],
                'reward_point_tier_3' => ['type' => 'INT', 'constraint' => 11, 'default' => 2, 'null' => true],
                'reward_cap_menit'    => ['type' => 'INT', 'constraint' => 11, 'default' => 30, 'null' => true],
            ]);
        }

        if (!$db->fieldExists('denda_per_menit', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'denda_per_menit' => ['type' => 'INT', 'constraint' => 11, 'default' => 1000, 'null' => true],
                'denda_pulang_awal' => ['type' => 'INT', 'constraint' => 11, 'default' => 1000, 'null' => true],
            ]);
        }

        // Jam operasional scanner QR siswa (default aktif 05:30–18:00)
        if (!$db->fieldExists('qr_scan_aktif', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'qr_scan_aktif'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => true],
                'qr_scan_mulai'   => ['type' => 'TIME', 'default' => '05:30:00', 'null' => true],
                'qr_scan_selesai' => ['type' => 'TIME', 'default' => '18:00:00', 'null' => true],
            ]);
        }
        if (!$db->fieldExists('qr_kiosk_token', 'absensi_setting')) {
            $forge->addColumn('absensi_setting', [
                'qr_kiosk_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            ]);
        }
    }

    private function patchAbsensiHarianTable()
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_harian')) {
            return;
        }

        // Pastikan jam_masuk dan jam_pulang bisa NULL untuk status Sakit/Ijin/Cuti
        $fields = $db->getFieldData('absensi_harian');
        $jamMasukField = null;
        $jamPulangField = null;
        foreach($fields as $f) {
            if ($f->name === 'jam_masuk') $jamMasukField = $f;
            if ($f->name === 'jam_pulang') $jamPulangField = $f;
        }
        if ($jamMasukField && !$jamMasukField->nullable) {
            $db->query("ALTER TABLE absensi_harian MODIFY jam_masuk DATETIME NULL");
        }
        if ($jamPulangField && !$jamPulangField->nullable) {
            $db->query("ALTER TABLE absensi_harian MODIFY jam_pulang DATETIME NULL");
        }

        $forge = \Config\Database::forge();
        
        if (!$db->fieldExists('status_pulang', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'status_pulang' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'after' => 'foto_pulang'
                ]
            ]);
        }

        if (!$db->fieldExists('pulang_cepat_menit', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'pulang_cepat_menit' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => true,
                    'after' => 'status_pulang'
                ]
            ]);
        }

        if (!$db->fieldExists('points', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'points' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => true,
                    'after' => 'pulang_cepat_menit' // or after pulang_akhir_menit if it exists, but strict order doesn't matter much
                ]
            ]);
        }

        if (!$db->fieldExists('denda', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'denda' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                    'null' => true,
                    'after' => 'points'
                ]
            ]);
        }

        // Penanda + jumlah penalti "tidak absen pulang" (migrasi 20261105000000).
        // Jumlahnya disimpan per baris, bukan dihitung ulang dari pengaturan,
        // supaya koreksi admin mengembalikan tepat poin yang dulu dipotong
        // walau tarifnya sudah berubah di tengah jalan.
        if (!$db->fieldExists('pulang_tidak_tercatat', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'pulang_tidak_tercatat' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
                'poin_penalti_pulang' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ],
            ]);
        }

        if (!$db->fieldExists('keterangan_denda', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'keterangan_denda' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'denda'
                ]
            ]);
        }

        if (!$db->fieldExists('is_wfh', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'is_wfh' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => true,
                    'after' => 'keterangan_denda'
                ]
            ]);
        }
    }

    /**
     * Helper to patch absensi_koreksi table if columns are missing
     */
    private function patchAbsensiKoreksiTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        // If table doesn't exist, create it
        if (!$db->tableExists('absensi_koreksi')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'attendance_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'field_name' => ['type' => 'VARCHAR', 'constraint' => 50],
                'old_value' => ['type' => 'TEXT', 'null' => true],
                'new_value' => ['type' => 'TEXT'],
                'reason' => ['type' => 'TEXT'],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
                'processed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'processed_at' => ['type' => 'DATETIME', 'null' => true],
                'admin_note' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addKey('attendance_id');
            $forge->addKey('user_id');
            $forge->createTable('absensi_koreksi', true);
            return;
        }

        // Check columns
        $cols = [
            'attendance_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'field_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'old_value' => ['type' => 'TEXT', 'null' => true],
            'new_value' => ['type' => 'TEXT'],
            'reason' => ['type' => 'TEXT'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'processed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'admin_note' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];

        foreach ($cols as $field => $def) {
            if (!$db->fieldExists($field, 'absensi_koreksi')) {
                $forge->addColumn('absensi_koreksi', [$field => $def]);
            }
        }
    }

    /**
     * Helper to patch absensi_lokasi table if columns are missing
     */
    private function patchAbsensiLokasiTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        // If table doesn't exist, create it
        if (!$db->tableExists('absensi_lokasi')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama_lokasi' => ['type' => 'VARCHAR', 'constraint' => 100],
                'is_utama' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => true],
                'lat' => ['type' => 'DECIMAL', 'constraint' => '10,8'],
                'lng' => ['type' => 'DECIMAL', 'constraint' => '11,8'],
                'radius_meter' => ['type' => 'INT', 'constraint' => 11, 'default' => 100],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->createTable('absensi_lokasi', true);
            return;
        }

        // Check columns
        $cols = [
            'nama_lokasi' => ['type' => 'VARCHAR', 'constraint' => 100],
            'is_utama' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => true],
            'lat' => ['type' => 'DECIMAL', 'constraint' => '10,8'],
            'lng' => ['type' => 'DECIMAL', 'constraint' => '11,8'],
            'radius_meter' => ['type' => 'INT', 'constraint' => 11, 'default' => 100],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
        ];

        foreach ($cols as $field => $def) {
            if (!$db->fieldExists($field, 'absensi_lokasi')) {
                $forge->addColumn('absensi_lokasi', [$field => $def]);
            }
        }
    }

    private function patchAbsensiIzinTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists('absensi_izin')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'id_guru' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'tgl_mulai' => ['type' => 'DATE'],
                'tgl_selesai' => ['type' => 'DATE'],
                'jenis' => ['type' => 'VARCHAR', 'constraint' => 20],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
                'bukti' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'verifikasi_catatan' => ['type' => 'TEXT', 'null' => true],
                'verified_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'verified_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->createTable('absensi_izin', true);
        } else {
            if (!$db->fieldExists('verifikasi_catatan', 'absensi_izin')) {
                $forge->addColumn('absensi_izin', [
                    'verifikasi_catatan' => ['type' => 'TEXT', 'null' => true, 'after' => 'bukti']
                ]);
            }
            if (!$db->fieldExists('verified_by', 'absensi_izin')) {
                $forge->addColumn('absensi_izin', [
                    'verified_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'verifikasi_catatan']
                ]);
            }
            if (!$db->fieldExists('verified_at', 'absensi_izin')) {
                $forge->addColumn('absensi_izin', [
                    'verified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'verified_by']
                ]);
            }
            // Normalize legacy integer status values.
            // Dulu blok ini jalan buta setiap kali dipanggil: ALTER TABLE + 3
            // UPDATE penuh di tengah request admin, padahal di server sekarang
            // kolomnya sudah VARCHAR(20) dan tidak ada lagi nilai 0/1/2/''.
            $perluNormalisasi = false;
            try {
                $sisaLama = $db->query("SELECT 1 FROM absensi_izin
                    WHERE status IS NULL OR status = '' OR status IN ('0', '1', '2') LIMIT 1")->getNumRows();
                foreach ($db->getFieldData('absensi_izin') as $f) {
                    if ($f->name === 'status') {
                        $perluNormalisasi = $sisaLama > 0
                            || strtolower((string) ($f->type ?? '')) !== 'varchar';
                        break;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'Pemeriksaan status absensi_izin gagal: ' . $e->getMessage());
            }
            if ($perluNormalisasi) {
                try {
                    $db->query("ALTER TABLE absensi_izin MODIFY status VARCHAR(20) DEFAULT 'pending'");
                    $db->query("UPDATE absensi_izin SET status = 'pending' WHERE status = '0' OR status IS NULL OR status = ''");
                    $db->query("UPDATE absensi_izin SET status = 'approved' WHERE status = '1'");
                    $db->query("UPDATE absensi_izin SET status = 'rejected' WHERE status = '2'");
                } catch (\Throwable $e) {
                    log_message('error', 'Normalisasi status absensi_izin gagal: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Self-healing: pastikan kolom audit verifikasi ada di absensi_harian.
     *
     * verifyVerifikasi()/rejectVerifikasi() mencatat verified_by/verified_at,
     * tapi instalasi lama tidak punya kolomnya sehingga UPDATE gagal dengan
     * "Unknown column 'verified_by' in 'SET'". Patch ini dipanggil di awal
     * kedua method tersebut; guard fieldExists di payload tetap dipasang
     * supaya tidak pernah fatal bila ALTER ditolak (hak DB terbatas).
     */
    private function patchAbsensiHarianVerifColumns()
    {
        try {
            $db = \Config\Database::connect();
            $forge = \Config\Database::forge();

            if (!$db->tableExists('absensi_harian')) {
                return;
            }
            if (!$db->fieldExists('verified_by', 'absensi_harian')) {
                $forge->addColumn('absensi_harian', [
                    'verified_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'keterangan_denda'],
                ]);
            }
            if (!$db->fieldExists('verified_at', 'absensi_harian')) {
                $forge->addColumn('absensi_harian', [
                    'verified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'verified_by'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin] patchAbsensiHarianVerifColumns gagal: ' . $e->getMessage());
        }
    }

    private function patchAbsensiLiburTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists('absensi_hari_libur')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tanggal' => ['type' => 'DATE'],
                'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addPrimaryKey('id');
            $forge->createTable('absensi_hari_libur', true);
        }
    }
    private function patchAbsensiJadwalTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists('absensi_jadwal_kerja')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'hari' => ['type' => 'INT', 'constraint' => 11], // 1=Senin, 7=Minggu
                'jam_masuk' => ['type' => 'TIME', 'default' => '07:30:00'],
                'jam_pulang' => ['type' => 'TIME', 'default' => '15:00:00'],
                'is_libur' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $forge->addPrimaryKey('id');
            $forge->createTable('absensi_jadwal_kerja', true);

            // Seed default data
            $data = [];
            for ($i = 1; $i <= 7; $i++) {
                $isLibur = ($i == 7) ? 1 : 0; // Default Minggu libur
                $data[] = [
                    'hari' => $i,
                    'jam_masuk' => '07:30:00',
                    'jam_pulang' => '15:00:00',
                    'is_libur' => $isLibur
                ];
            }
            $db->table('absensi_jadwal_kerja')->insertBatch($data);
        }
    }

    /**
     * Perlu tidaknya migrasi/perbaikan legacy pada ledger pembayaran denda.
     *
     * @return array{alter:bool, update:bool}
     */
    private function kebutuhanMigrasiPembayaranDenda(): array
    {
        $out = ['alter' => false, 'update' => false];
        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_pembayaran_denda')) {
            return $out;
        }

        try {
            foreach ($db->getFieldData('absensi_pembayaran_denda') as $f) {
                if ($f->name === 'status') {
                    $out['alter'] = strtolower((string) ($f->type ?? '')) !== 'varchar';
                    break;
                }
            }
            $sisaLegacy = $db->query("SELECT 1 FROM absensi_pembayaran_denda
                WHERE metode_bayar IS NULL OR metode_bayar = ''
                   OR (reward_redemption_id IS NOT NULL AND metode_bayar = 'TUNAI')
                   OR ((nominal_bayar IS NULL OR nominal_bayar = 0) AND total_denda > 0 AND status = 'lunas')
                   OR (tanggal_bayar IS NULL AND paid_at IS NOT NULL)
                LIMIT 1")->getNumRows();
            $out['update'] = $sisaLegacy > 0;
        } catch (\Throwable $e) {
            // Pemeriksaan gagal = jangan menjalankan ALTER/UPDATE buta;
            // kegagalan struktur akan tetap terlihat di log.
            log_message('error', 'Pemeriksaan legacy absensi_pembayaran_denda gagal: ' . $e->getMessage());
        }

        return $out;
    }

    private function patchAbsensiPembayaranDendaTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists('absensi_pembayaran_denda')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'bulan' => ['type' => 'INT', 'constraint' => 2],
                'tahun' => ['type' => 'INT', 'constraint' => 4],
                'total_denda' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'nominal_bayar' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'metode_bayar' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'TUNAI'],
                'poin_digunakan' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'reward_redemption_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'lunas'],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'tanggal_bayar' => ['type' => 'DATE', 'null' => true],
                'paid_at' => ['type' => 'DATETIME', 'null' => true],
                'paid_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addKey('user_id');
            $forge->createTable('absensi_pembayaran_denda', true);
        } else {
            $fields = [];
            if (!$db->fieldExists('idempotency_key', 'absensi_pembayaran_denda')) {
                $fields['idempotency_key'] = ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true];
            }
            if (!$db->fieldExists('metode_bayar', 'absensi_pembayaran_denda')) {
                $fields['metode_bayar'] = ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'TUNAI'];
            }
            if (!$db->fieldExists('poin_digunakan', 'absensi_pembayaran_denda')) {
                $fields['poin_digunakan'] = ['type' => 'INT', 'constraint' => 11, 'default' => 0];
            }
            if (!$db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda')) {
                $fields['reward_redemption_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true];
            }
            if (!$db->fieldExists('nominal_bayar', 'absensi_pembayaran_denda')) {
                $fields['nominal_bayar'] = ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0];
            }
            if (!$db->fieldExists('tanggal_bayar', 'absensi_pembayaran_denda')) {
                $fields['tanggal_bayar'] = ['type' => 'DATE', 'null' => true];
            }
            if (!empty($fields)) {
                $forge->addColumn('absensi_pembayaran_denda', $fields);
            }
        }

        // Migrate ENUM status to VARCHAR to support 'sebagian', lalu perbaiki
        // baris legacy — keduanya hanya bila masih ada yang perlu diperbaiki.
        // Dijaga dengan pemeriksaan murah karena metode ini dipanggil dari
        // banyak endpoint (halaman denda, laporan, bayar nominal): ALTER + 4
        // UPDATE tanpa syarat mengunci seluruh ledger pada setiap page load.
        $kebutuhan = $this->kebutuhanMigrasiPembayaranDenda();
        if ($kebutuhan['alter']) {
            try {
                $db->query("ALTER TABLE absensi_pembayaran_denda MODIFY COLUMN status VARCHAR(20) DEFAULT 'lunas'");
            } catch (\Throwable $e) {
                log_message('error', 'Migrasi status absensi_pembayaran_denda gagal: ' . $e->getMessage());
            }
        }

        if ($kebutuhan['update']) {
            try {
                // Baris lama tanpa metode tapi tertaut redemption: itu voucher,
                // bukan tunai — signal kuat, jadi boleh dikoreksi.
                $db->query("
                    UPDATE absensi_pembayaran_denda
                    SET metode_bayar = 'POIN'
                    WHERE reward_redemption_id IS NOT NULL
                      AND (metode_bayar IS NULL OR metode_bayar = '' OR metode_bayar = 'TUNAI')
                ");
                // Metode kosong: baru boleh ditebak dari keterangan. Baris yang
                // secara sadar dicatat TUNAI tidak pernah ditulis ulang hanya
                // karena keterangannya menyebut kata "poin"/"voucher" — dulu
                // itu mengubah kas tunai menjadi potong poin secara permanen.
                $db->query("
                    UPDATE absensi_pembayaran_denda
                    SET metode_bayar = 'POIN'
                    WHERE (metode_bayar IS NULL OR metode_bayar = '')
                      AND (keterangan LIKE '%voucher%' OR keterangan LIKE '%poin%')
                ");
                $db->query("
                    UPDATE absensi_pembayaran_denda
                    SET metode_bayar = 'TUNAI'
                    WHERE metode_bayar IS NULL OR metode_bayar = ''
                ");
                // Backfill nominal_bayar from total_denda for legacy records
                $db->query("
                    UPDATE absensi_pembayaran_denda
                    SET nominal_bayar = total_denda
                    WHERE (nominal_bayar IS NULL OR nominal_bayar = 0) AND total_denda > 0 AND status = 'lunas'
                ");
                // Backfill tanggal_bayar from paid_at for legacy records
                $db->query("
                    UPDATE absensi_pembayaran_denda
                    SET tanggal_bayar = DATE(paid_at)
                    WHERE tanggal_bayar IS NULL AND paid_at IS NOT NULL
                ");
            } catch (\Throwable $e) {
                log_message('error', 'Perbaikan data legacy pembayaran denda gagal: ' . $e->getMessage());
            }
        }

        // Table for Saldo Denda (Deposit/Kelebihan Pembayaran Denda)
        if (!$db->tableExists('absensi_saldo_denda')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tipe' => ['type' => 'VARCHAR', 'constraint' => 30], // LEBIH_BAYAR, POTONG_DENDA, MANUAL_ADJUST
                'nominal' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'saldo_akhir' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'pembayaran_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addKey('user_id');
            $forge->createTable('absensi_saldo_denda', true);
        }
    }

    /**
     * Validasi kunci idempotensi dari client (UUID/unik per aksi bayar).
     * NULL = client lama tanpa kunci → alur lama (tanpa proteksi ganda).
     */
    private function normalisasiIdempotencyKey(): ?string
    {
        $key = trim((string) ($this->request->getPost('idempotency_key') ?? ''));
        if ($key === '') {
            return null;
        }
        if (!preg_match('/^[A-Za-z0-9-]{8,80}$/', $key)) {
            return null;
        }

        return $key;
    }

    /**
     * Cek apakah aksi bayar dengan kunci ini sudah tercatat (klik ganda /
     * retry). Mencakup baris FIFO berkunci `key:YYYY-MM`.
     */
    private function pembayaranKunciSudahAda(string $key): bool
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->fieldExists('idempotency_key', 'absensi_pembayaran_denda')) {
                return false;
            }
            return $db->table('absensi_pembayaran_denda')
                ->where('status !=', 'batal')
                ->groupStart()
                ->where('idempotency_key', $key)
                ->orLike('idempotency_key', $key . ':', 'after')
                ->groupEnd()
                ->countAllResults() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Daftar Denda per Guru per Bulan
     */
    public function daftarDenda()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $this->patchAbsensiPembayaranDendaTable();
        $this->patchAbsensiSettingTable();

        [$bulan, $tahun] = $this->periodeRekap(
            $this->request->getGet('bulan') ?? date('m'),
            $this->request->getGet('tahun') ?? date('Y')
        );

        $db = \Config\Database::connect();

        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->get()->getResult();

        $mapByUser = [];
        foreach ($attendances as $att) {
            if (!isset($mapByUser[$att->user_id]))
                $mapByUser[$att->user_id] = [];
            $mapByUser[$att->user_id][$att->tgl] = $att;
        }

        $holidays = \App\Services\AttendancePolicy::liburNasional();

        $setting = $this->dashboardModel->getSetting();
        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

        $pembayaranList = $db->table('absensi_pembayaran_denda')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status !=', 'batal')
            ->get()->getResult();

        $lunasMap = [];
        $bayarTunaiMap = [];
        $potonganPoinMap = [];
        $potonganSaldoMap = [];
        $poinDigunakanMap = [];

        foreach ($pembayaranList as $p) {
            $nominalActual = (float)($p->nominal_bayar ?: $p->total_denda);
            if ($p->status === 'lunas') {
                $lunasMap[$p->user_id] = $p;
            }
            $kelas = \App\Services\AttendancePenaltyService::kelasPembayaranDenda($p->metode_bayar, $p->keterangan ?? null);
            if ($kelas === 'POIN') {
                $potonganPoinMap[$p->user_id] = ($potonganPoinMap[$p->user_id] ?? 0) + $nominalActual;
                $poinDigunakanMap[$p->user_id] = ($poinDigunakanMap[$p->user_id] ?? 0) + (int)($p->poin_digunakan ?? 0);
            } elseif ($kelas === 'SALDO') {
                $potonganSaldoMap[$p->user_id] = ($potonganSaldoMap[$p->user_id] ?? 0) + $nominalActual;
            } else {
                $bayarTunaiMap[$p->user_id] = ($bayarTunaiMap[$p->user_id] ?? 0) + $nominalActual;
            }
        }

        // Payment history (all statuses, with admin/user name)
        $paymentHistory = $db->table('absensi_pembayaran_denda pd')
            ->select('pd.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = pd.paid_by', 'left')
            ->where('pd.bulan', $bulan)
            ->where('pd.tahun', $tahun)
            ->orderBy('pd.paid_at', 'DESC')
            ->get()->getResult();

        $historyByUser = [];
        foreach ($paymentHistory as $h) {
            $historyByUser[$h->user_id][] = $h;
        }

        // Get Current Active Points for all teachers
        $poinModel = new \App\Models\PoinHistoryModel();
        $saldoPoinMap = [];
        foreach ($users as $u) {
            $saldoPoinMap[$u->id] = $poinModel->getSaldo($u->id);
        }

        // Get Saldo Denda for all teachers
        $saldoDendaMap = [];
        if ($db->tableExists('absensi_saldo_denda')) {
            $saldoRaw = $db->table('absensi_saldo_denda')
                ->select('user_id, SUM(nominal) as total_saldo')
                ->groupBy('user_id')
                ->get()->getResult();
            foreach ($saldoRaw as $sr) {
                $saldoDendaMap[$sr->user_id] = max(0, (float)$sr->total_saldo);
            }
        }

        // Get Alpha Counts & Progressive Alpha details — peta tersaring kanonik:
        // item/total hanya tanggal bolong, tanpa hari ini, streak kontinu.
        $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endDate = date('Y-m-t', strtotime($startDate));
        $alphaDetailMap = $this->filteredAlphaMonthMap($startDate, $endDate, $absenSetting);
        $alphaMap = [];
        $alphaMissingMap = [];
        foreach ($alphaDetailMap as $uId => $aInfo) {
            $alphaMap[$uId] = $aInfo['count'];
            $alphaMissingMap[$uId] = $aInfo['missing_count'];
        }

        // Fetch YTD Stats
        $ytdStats = $this->getYTDDendaStats($tahun);

        return view('admin/presensi/denda', [
            'title' => 'Denda Presensi',
            'judul' => 'Denda Bulanan',
            'setting' => $setting,
            'absenSetting' => $absenSetting,
            'dendaPerPoin' => $this->tarifDendaPerPoin(),
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'users' => $users,
            'mapByUser' => $mapByUser,
            'alphaMap' => $alphaMap,
            'alphaDetailMap' => $alphaDetailMap,
            'holidays' => $holidays,
            'jadwalKerja' => $jadwalKerja,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'lunasMap' => $lunasMap,
            'bayarTunaiMap' => $bayarTunaiMap,
            'potonganPoinMap' => $potonganPoinMap,
            'potonganSaldoMap' => $potonganSaldoMap,
            'poinDigunakanMap' => $poinDigunakanMap,
            'saldoPoinMap' => $saldoPoinMap,
            'saldoDendaMap' => $saldoDendaMap,
            'historyByUser' => $historyByUser,
            'alphaMissingMap' => $alphaMissingMap,
            'ytdStats' => $ytdStats
        ]);
    }

    /**
     * Susun rekap denda satu periode menjadi PDF (isi mentah, bukan unduhan).
     *
     * Dipisah dari exportDendaPdf() supaya isi yang sama bisa dipakai untuk
     * dua hal: diunduh lewat browser (stream) dan dikirim ke grup WhatsApp /
     * Telegram sebagai lampiran. Kalau logikanya diduplikasi, rekap yang
     * terkirim ke grup bisa berbeda dari yang dicetak admin — dan tidak ada
     * yang mengetahuinya.
     *
     * @return array{pdf:string, filename:string, bulan:string, tahun:string, jumlah_guru:int}
     */
    private function buildDendaPdf(string $bulan, string $tahun): array
    {
        return (new \App\Services\RekapPdfService())->buildDendaPdf($bulan, $tahun);
    }

    /**
     * Unduh rekap denda periode terpilih sebagai PDF.
     *
     * GET /admin/presensi/denda/pdf?bulan=MM&tahun=YYYY
     */
    public function exportDendaPdf()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        // normalizeBulan/normalizeTahun membereskan format ('2026-11' → '11'),
        // periodeRekap membereskan nilai di luar rentang ('13' → bulan berjalan).
        [$bulanInt, $tahunInt] = $this->periodeRekap(
            $this->normalizeBulan((string) ($this->request->getGet('bulan') ?? date('m'))),
            $this->normalizeTahun((string) ($this->request->getGet('tahun') ?? date('Y')))
        );
        $bulan = sprintf('%02d', $bulanInt);
        $tahun = (string) $tahunInt;

        $hasil = $this->buildDendaPdf($bulan, $tahun);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $hasil['filename'] . '"')
            ->setBody($hasil['pdf']);
    }

    /**
     * Terima "9", "09", atau "2026-09" supaya tidak ada yang bisa mengirim
     * periode di luar 12 bulan dan membuat PDF kosong.
     */
    private function normalizeBulan(string $bulan): string
    {
        $bulan = trim($bulan);

        if (preg_match('/^\d{4}-\d{1,2}$/', $bulan) === 1) {
            $bulan = substr($bulan, 5);
        }

        return str_pad((string) (int) $bulan, 2, '0', STR_PAD_LEFT);
    }

    private function normalizeTahun(string $tahun): string
    {
        $tahun = trim($tahun);

        if (preg_match('/^\d{4}-\d{2}$/', $tahun) === 1) {
            $tahun = substr($tahun, 0, 4);
        }

        return (string) (int) $tahun;
    }

    /**
     * Kirim file rekap denda ke grup WhatsApp dan grup Telegram.
     *
     * POST /admin/presensi/denda/kirim-rekap
     * Body: bulan, tahun
     *
     * PDF yang dikirim adalah hasil render yang sama dengan tombol
     * "Cetak PDF" — bukan dokumen terpisah yang bisa formatnya melenceng.
     *
     * Dua kanal dicoba terpisah dan dilaporkan terpisah, jadi admin tahu
     * kalau hanya salah satu yang gagal. Kegagalan satu kanal tidak
     * membatalkan kanal lain.
     */
    public function kirimRekapDenda()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $bulan = $this->normalizeBulan((string) ($this->request->getPost('bulan') ?: date('m')));
        $tahun = $this->normalizeTahun((string) ($this->request->getPost('tahun') ?: date('Y')));

        if ((int) $bulan < 1 || (int) $bulan > 12 || (int) $tahun < 2000 || (int) $tahun > 2100) {
            return $this->respond(false, 'Periode tidak valid. Gunakan bulan 1-12 dan tahun 2000-2100.');
        }

        try {
            $hasil = $this->buildDendaPdf($bulan, $tahun);
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::kirimRekapDenda] gagal membuat PDF: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyusun PDF rekap denda — periksa log aplikasi.', null, null, 500);
        }

        if ($hasil['jumlah_guru'] === 0) {
            return $this->respond(
                false,
                'Tidak ada guru aktif untuk periode ' . $bulan . '/' . $tahun . ' sehingga PDF kosong.'
            );
        }

        // PDF perlu file di disk: Telegram multipart dan OpenWA base64
        // keduanya membaca dari path, bukan dari string.
        $dir = WRITEPATH . 'uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Nama file pakai prefix acak supaya tidak bisa ditebak dari luar
        // dan tidak bentrok dengan request bersamaan.
        $path = $dir . '/rekap_denda_' . bin2hex(random_bytes(8)) . '.pdf';

        if (@file_put_contents($path, $hasil['pdf']) === false) {
            log_message('error', '[PresensiAdmin::kirimRekapDenda] gagal menulis ' . $path);
            return $this->respond(false, 'Gagal menyimpan PDF sementara untuk dikirim.', null, null, 500);
        }

        $namaGrup = $this->bulanNama((int) $bulan) . ' ' . $tahun;
        $caption = "<b>📄 REKAP DENDA PRESENSI</b>\n"
            . "Periode: {$namaGrup}\n"
            . 'Total guru: ' . $hasil['jumlah_guru'] . ' orang';

        try {
            $svc = new \App\Services\NotificationService();
            $hasilKirim = $svc->sendDokumenToGrup(
                'rekap_denda_bulanan',
                $path,
                $hasil['filename'],
                $caption
            );
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::kirimRekapDenda] error saat kirim: ' . $e->getMessage());
            return $this->respond(false, 'Gagal mengirim rekap — periksa log aplikasi.', null, null, 500);
        } finally {
            // PDF memuat catatan kehadiran dan nominal denda tiap guru —
            // tidak boleh tertinggal di disk lebih lama dari yang perlu.
            // finally dipakai (bukan hanya di jalur sukses) supaya file
            // terhapus bahkan saat gateway melempar exception.
            $this->hapusPdfSementara($path);
        }

        $berhasil = $hasilKirim['telegram'] || $hasilKirim['whatsapp'];

        $rincian = [];
        $rincian[] = 'Telegram: ' . ($hasilKirim['telegram'] ? 'terkirim' : 'GAGAL — ' . $hasilKirim['telegram_error']);
        $rincian[] = 'WhatsApp: ' . ($hasilKirim['whatsapp'] ? 'terkirim' : 'GAGAL — ' . $hasilKirim['whatsapp_error']);

        return $this->respond(
            $berhasil,
            $berhasil
                ? 'Rekap denda ' . $namaGrup . ' dikirim. ' . implode(' | ', $rincian)
                : 'Rekap denda tidak terkirim ke kanal mana pun. ' . implode(' | ', $rincian),
            [
                'bulan'       => $bulan,
                'tahun'       => $tahun,
                'jumlah_guru' => $hasil['jumlah_guru'],
                'filename'    => $hasil['filename'],
                'telegram'    => $hasilKirim['telegram'],
                'whatsapp'    => $hasilKirim['whatsapp'],
            ],
            null,
            $berhasil ? 200 : 500
        );
    }

    private function hapusPdfSementara(string $path): void
    {
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    /** "09" -> "September" untuk judul caption. */
    private function bulanNama(int $bulan): string
    {
        $nama = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $nama[$bulan] ?? (string) $bulan;
    }

    /**
     * Hitung ulang seluruh denda presensi untuk periode (bulan & tahun) tertentu
     * - Mengevaluasi keterlambatan masuk & toleransi (grace period)
     * - Mengevaluasi pulang awal (early departure)
     * - Mengevaluasi denda alpa flat per hari
     * - Memperbarui tabel absensi_harian
     */
    public function recalculateMonthlyDenda()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $bulanReq = (int) ($this->request->getPost('bulan') ?: date('m'));
        $tahunReq = (int) ($this->request->getPost('tahun') ?: date('Y'));

        // KUNCI BULAN AKTIF: hitung ulang hanya untuk bulan berjalan agar tidak
        // mengaduk data arsip bulan lampau (sumber inkonsistensi denda).
        $bulanAktif = (int) date('m');
        $tahunAktif = (int) date('Y');
        if ($bulanReq !== $bulanAktif || $tahunReq !== $tahunAktif) {
            return $this->respond(false, 'Hitung ulang hanya diperbolehkan untuk bulan aktif (' . $bulanAktif . '/' . $tahunAktif . '). Data bulan lampau dikunci sebagai arsip.');
        }
        $bulan = $bulanAktif;
        $tahun = $tahunAktif;

        $db = \Config\Database::connect();
        $settingModel = new \App\Models\AttendanceSettingModel();
        $setting = $settingModel->first();
        if (!$setting) {
            return $this->respond(false, 'Konfigurasi absensi tidak ditemukan');
        }

        $shiftModel = new \App\Models\ShiftModel();
        $penaltyService = new \App\Services\AttendancePenaltyService();
        $adaKolomPenalti = $db->fieldExists('pulang_tidak_tercatat', 'absensi_harian');

        // Load work schedules
        $jadwalKerja = [];
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jRows = $db->table('absensi_jadwal_kerja')->get()->getResult();
            foreach ($jRows as $jr) {
                $jadwalKerja[(int) $jr->hari] = $jr;
            }
        }

        $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endDate = date('Y-m-t', strtotime($startDate));
        $maxDendaBulan = (float) ($setting->max_denda_bulan ?? 0);
        // Status yang diproteksi: tidak pernah ditulis ulang oleh hitung ulang.
        // (Dinas/WFH/izin sah dimiliki alur approval & checkout; recalc hanya
        //  boleh MENOLKAN denda dinas yang terlanjur terisi, bukan menimpa status.)
        $leaveStatuses = ['DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS', 'DINAS_OUT', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'WFH', 'PENDING_VERIFICATION'];
        // Seed akumulator cap dengan baris yang dilewati loop di bawah (hari ini +
        // baris proteksi) agar basisnya sama dengan applyMonthlyCap (SUM DB).
        // Tanpa seed, recalc memberi allowance penuh padahal DB sudah berisi denda.
        $userMonthlyDenda = [];
        $todayStr = date('Y-m-d');
        try {
            $q = fn(string $v): string => "'" . str_replace("'", "''", $v) . "'";
            $statusList = implode(',', array_map($q, $leaveStatuses));
            $wfhCond = $db->fieldExists('is_wfh', 'absensi_harian') ? ' OR is_wfh = 1' : '';
            $seedRows = $db->table('absensi_harian')
                ->select('user_id, SUM(denda) as s')
                ->where('MONTH(tgl)', $bulan)->where('YEAR(tgl)', $tahun)
                ->where(
                    '(tgl >= ' . $q($todayStr) . ' OR (tgl < ' . $q($todayStr)
                    . ' AND (status_masuk IN (' . $statusList . ') OR status_pulang IN (' . $statusList . ')' . $wfhCond . ')))',
                    null, false
                )
                ->groupBy('user_id')->get()->getResult();
            foreach ($seedRows as $sr) {
                $userMonthlyDenda[(int) $sr->user_id] = (float) ($sr->s ?? 0);
            }
        } catch (\Throwable $e) {}

        // 1. Recalculate attendance records in absensi_harian for this month
        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->orderBy('tgl', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResult();

        $updatedAttendanceCount = 0;
        $skippedUnchangedCount = 0;
        $dendaDipertahankan = 0;
        $dibayarCache = [];
        $activeAlphaRecords = [];
        // User yang barisnya benar-benar berubah — untuk sync surat teguran.
        $userTersentuhRecalc = [];

        foreach ($attendances as $r) {
            $uid = (int) $r->user_id;
            if (!isset($userMonthlyDenda[$uid])) {
                $userMonthlyDenda[$uid] = 0.0;
            }

            // Hari berjalan milik cron/checkout — jangan rebut tulis (bagian alpha
            // di bawah juga melewati today).
            if (substr((string) ($r->tgl ?? ''), 0, 10) >= $todayStr) {
                $skippedUnchangedCount++;
                continue;
            }

            $currentStatus = strtoupper((string) ($r->status_masuk ?? ''));
            $currentPulang = strtoupper((string) ($r->status_pulang ?? ''));
            $isWfhRow = (!empty($r->is_wfh) && (int) $r->is_wfh === 1) || $currentStatus === 'WFH' || $currentPulang === 'WFH';
            $isDinasRow = strpos($currentStatus, 'DINAS') === 0 || strpos($currentPulang, 'DINAS') === 0;
            $isLeaveRow = in_array($currentStatus, $leaveStatuses, true) || in_array($currentPulang, $leaveStatuses, true);

            // Baris proteksi: lewati total (kecuali pastikan denda dinas = 0).
            if ($isWfhRow || $isLeaveRow) {
                if ($isDinasRow && (float) ($r->denda ?? 0) > 0) {
                    $db->table('absensi_harian')->where('id', $r->id)->update(['denda' => 0, 'keterangan_denda' => null]);
                    $updatedAttendanceCount++;
                } else {
                    $skippedUnchangedCount++;
                }
                continue;
            }

            // If recorded ALPHA
            $jamMasukTs  = \App\Services\AttendancePolicy::pickTime($r->jam_masuk ?? null);
            $jamPulangTs = \App\Services\AttendancePolicy::pickTime($r->jam_pulang ?? null);

            // ALPHA tidak pernah dinilai ulang dari jam, ada atau tidak jam_masuk.
            // Baris ALPHA yang punya jam_masuk nyata berasal dari keputusan admin
            // (verifikasi ditolak / cron finalisasi pending) — menghitung ulangnya
            // dari jam akan mengubah vonis alpha menjadi ONTIME/LATE dan menukar
            // denda alpha dengan denda telat. Sama seperti doRecalculateDate().
            if (in_array($currentStatus, ['ALPHA', 'ALPA'], true)) {
                $activeAlphaRecords[$uid][$r->tgl] = $r;
                continue;
            }

            // Nilai waktu ada tapi tidak terbaca (zero-date hasil impor/form
            // kosong): tidak ada dasar untuk menilai telat atau pulang awal, dan
            // menulis denda = 0 di baris yang memang berdenda sama saja merusak
            // tagihan. Lewati barisnya, jangan karang angkanya.
            if (($jamMasukTs === null && !empty($r->jam_masuk))
                || ($jamPulangTs === null && !empty($r->jam_pulang))) {
                $skippedUnchangedCount++;
                continue;
            }

            // Recalculate check-in
            $dayOfWeek = (int) date('N', strtotime($r->tgl));
            $jadwal = $jadwalKerja[$dayOfWeek] ?? null;
            $sched = $shiftModel->getScheduleForUser($uid, $dayOfWeek, $jadwal, $setting);

            $update = [];
            $masukPoints = 0;
            $masukDenda = 0;
            $masukKetDenda = null;

            // Baris tanpa check-in (mis. alpha yang belum dinormalkan di atas)
            // tidak punya dasar perhitungan masuk.
            if ($jamMasukTs !== null) {
                $graceMins = (int) ($sched['grace_menit'] ?? ($setting->grace_period ?? 15));

                // Rumus baku AttendancePolicy + tanggal baris, bukan tanggal hari ini.
                $hit = \App\Services\AttendancePolicy::telat($r->tgl, $jamMasukTs, [
                    'jam_masuk'   => $sched['jam_masuk'],
                    'grace_menit' => $graceMins,
                ]);

                if ($hit['status'] === 'ONTIME') {
                    $update['status_masuk'] = 'ONTIME';
                    $update['telat_menit'] = 0;
                    $update['datang_awal_menit'] = $hit['datang_awal_menit'];
                    $masukPoints = $this->calculateProgressiveRewardPoints($hit['datang_awal_menit'], $setting);
                } else {
                    $update['status_masuk'] = 'LATE';
                    $update['telat_menit'] = $hit['telat_menit'];
                    $update['datang_awal_menit'] = 0;
                    $calc = $this->calculateProgressivePenalty($hit['telat_menit'], $setting, 'Telat Masuk');
                    $masukPoints = -1 * $calc['points'];
                    $masukDenda = $calc['denda'];
                    $masukKetDenda = $calc['keterangan'];
                }
            }

            // Recalculate check-out
            $pulangPoints = 0;
            $pulangDenda = 0;
            $pulangKetDenda = null;

            if ($jamPulangTs !== null) {
                $jamPulangLimit = $sched['jam_pulang'];
                $thresholdPulang = strtotime($r->tgl . ' ' . $jamPulangLimit);
                $pulangTime = strtotime($r->tgl . ' ' . $jamPulangTs);

                if ($pulangTime === false || $thresholdPulang === false) {
                    // jadwal tidak terbaca — jangan sampai salah denda
                } elseif ($pulangTime < $thresholdPulang) {
                    $update['status_pulang'] = 'EARLY';
                    $pulangCepatMenit = (int) ceil(($thresholdPulang - $pulangTime) / 60);
                    $update['pulang_cepat_menit'] = $pulangCepatMenit;
                    $update['pulang_akhir_menit'] = 0;
                    $calcOut = $this->calculateProgressivePenalty($pulangCepatMenit, $setting, 'Pulang Awal');
                    $pulangPoints = -1 * $calcOut['points'];
                    $pulangDenda = $calcOut['denda'];
                    $pulangKetDenda = $calcOut['keterangan'];
                } else {
                    $update['status_pulang'] = 'ONTIME';
                    $update['pulang_cepat_menit'] = 0;
                    if ($pulangTime > $thresholdPulang) {
                        // Kanonik: lembur dihitung di atas 60 menit pertama.
                        $diffMenit = (int) floor(($pulangTime - $thresholdPulang) / 60);
                        $update['pulang_akhir_menit'] = ($diffMenit >= 60) ? ($diffMenit - 60) : 0;
                    }
                }
            }

            $rawDenda = (float) ($masukDenda + $pulangDenda);
            $effectiveDenda = $rawDenda;

            // Enforce monthly cap if set
            if ($maxDendaBulan > 0) {
                $allowed = max(0, $maxDendaBulan - $userMonthlyDenda[$uid]);
                if ($effectiveDenda > $allowed) {
                    $effectiveDenda = $allowed;
                    $masukKetDenda .= ' (dibatasi cap bulanan Rp ' . number_format($effectiveDenda, 0, ',', '.') . ')';
                }
            }
            $userMonthlyDenda[$uid] += $effectiveDenda;

            $update['points'] = $this->terapkanPenaltiLupaPulang(
                $masukPoints + $pulangPoints,
                $r,
                $jamPulangTs,
                $adaKolomPenalti,
                $update
            );
            $update['denda'] = $effectiveDenda;
            $kets = array_values(array_filter([$masukKetDenda, $pulangKetDenda]));
            $update['keterangan_denda'] = $kets ? implode('; ', $kets) : null;

            // Sama seperti doRecalculateDate(): hitung ulang bulanan tidak boleh
            // menurunkan denda di bawah uang yang sudah diterima bendahara,
            // kalau tidak sisa denda user jadi negatif (kredit hantu).
            if ($effectiveDenda < (float) ($r->denda ?? 0)) {
                $dibayarBulan = $this->totalDibayarBulan($uid, $bulan, $tahun, $dibayarCache);
                if ($dibayarBulan > 0) {
                    unset($update['denda'], $update['keterangan_denda']);
                    $userMonthlyDenda[$uid] += (float) ($r->denda ?? 0) - $effectiveDenda;
                    $dendaDipertahankan++;
                }
            }

            // Write-guard idempotensi: tulis hanya bila ada yang berubah agar
            // tekan ulang tidak mengaduk data & hitungan "dievaluasi" jujur.
            $needsWrite = false;
            foreach (['status_masuk', 'telat_menit', 'datang_awal_menit', 'status_pulang', 'pulang_cepat_menit', 'pulang_akhir_menit', 'points', 'denda', 'keterangan_denda'] as $col) {
                if (!array_key_exists($col, $update)) {
                    continue;
                }
                $old = $r->$col ?? null;
                $new = $update[$col];
                if (in_array($col, ['telat_menit', 'datang_awal_menit', 'pulang_cepat_menit', 'pulang_akhir_menit', 'points'], true)) {
                    if ((int) ($old ?? 0) !== (int) $new) { $needsWrite = true; break; }
                } elseif ($col === 'denda') {
                    if (abs((float) ($old ?? 0) - (float) $new) > 0.0001) { $needsWrite = true; break; }
                } else {
                    if ((string) ($old ?? '') !== (string) ($new ?? '')) { $needsWrite = true; break; }
                }
            }
            if (!$needsWrite) {
                $skippedUnchangedCount++;
                continue;
            }
            $db->table('absensi_harian')->where('id', $r->id)->update($update);
            $updatedAttendanceCount++;
            $userTersentuhRecalc[$uid] = true;
        }

        // 2. Recalculate & Sync Alpha Fines (flat per hari)
        // Item disaring ke bulan ini.
        $scanStartAlpha = date('Y-m-d', strtotime($startDate . ' -62 days'));
        $alphaSummary = $penaltyService->calculateAllUsersPeriodAlphaSummary($scanStartAlpha, $endDate, $setting);
        $updatedAlphaCount = 0;
        $pointAlpha = abs((int) ($setting->point_alpa ?? 5));

        // Peta izin approved bulan ini (memutus streak & melindungi baris cuti).
        $leaveCoveredDates = [];
        if ($db->tableExists('absensi_izin')) {
            try {
                $leaveRowsAlpha = $db->table('absensi_izin')
                    ->select('user_id, tgl_mulai, tgl_selesai')
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $endDate)
                    ->where('tgl_selesai >=', $startDate)
                    ->get()->getResult();
                foreach ($leaveRowsAlpha as $lv) {
                    $c = strtotime(max($startDate, (string) $lv->tgl_mulai));
                    $eLv = strtotime(min($endDate, (string) $lv->tgl_selesai));
                    while ($c <= $eLv) {
                        $leaveCoveredDates[(int) $lv->user_id][date('Y-m-d', $c)] = true;
                        $c = strtotime('+1 day', $c);
                    }
                }
            } catch (\Throwable $e) {}
        }

        foreach ($alphaSummary as $uid => $summary) {
            if (!isset($userMonthlyDenda[$uid])) {
                $userMonthlyDenda[$uid] = 0.0;
            }

            foreach ($summary['items'] as $item) {
                $tglAlpha = substr($item['tgl'] ?? '', 0, 10);
                // Hanya bulan yang diminta; masa depan/today bukan urusan recalc bulan lampau,
                // dan hari ini milik cron/checkout (hindari rebutan tulis).
                if ($tglAlpha === '' || $tglAlpha < $startDate || $tglAlpha > $endDate) {
                    continue;
                }
                if ($tglAlpha >= $todayStr) {
                    continue;
                }
                $dendaAlpha = (float) $item['denda'];
                $ketAlpha = $item['keterangan'];

                // Enforce monthly cap
                if ($maxDendaBulan > 0) {
                    $allowed = max(0, $maxDendaBulan - $userMonthlyDenda[$uid]);
                    if ($dendaAlpha > $allowed) {
                        $dendaAlpha = $allowed;
                        $ketAlpha .= ' (dibatasi cap bulanan Rp ' . number_format($dendaAlpha, 0, ',', '.') . ')';
                    }
                }

                // Akumulator cap hanya bertambah bila denda tanggal ini benar-benar
                // tercatat (update/insert) atau sudah tercatat tak berubah — bukan
                // saat baris existing non-alpha dipertahankan (dendanya sudah masuk
                // via loop presensi di atas).
                // Check if existing record in absensi_harian
                if (isset($activeAlphaRecords[$uid][$tglAlpha])) {
                    $rec = $activeAlphaRecords[$uid][$tglAlpha];
                    if ((string) ($rec->status_masuk ?? '') === 'ALPHA'
                        && (int) ($rec->points ?? 0) === -$pointAlpha
                        && abs((float) ($rec->denda ?? 0) - $dendaAlpha) < 0.0001
                        && (string) ($rec->keterangan_denda ?? '') === (string) $ketAlpha) {
                        $userMonthlyDenda[$uid] += $dendaAlpha;
                        $skippedUnchangedCount++;
                        continue;
                    }
                    $recId = $rec->id;
                    $db->table('absensi_harian')->where('id', $recId)->update([
                        'status_masuk'     => 'ALPHA',
                        'points'           => -$pointAlpha,
                        'denda'            => $dendaAlpha,
                        'keterangan_denda' => $ketAlpha,
                    ]);
                    $userMonthlyDenda[$uid] += $dendaAlpha;
                    $updatedAlphaCount++;
                    $userTersentuhRecalc[$uid] = true;
                } else {
                    $existingRow = $db->table('absensi_harian')->where('user_id', $uid)->where('tgl', $tglAlpha)->get()->getRow();
                    if (!$existingRow) {
                        $db->table('absensi_harian')->insert([
                            'user_id'          => $uid,
                            'tgl'              => $tglAlpha,
                            'status_masuk'     => 'ALPHA',
                            'status_pulang'    => null,
                            'points'           => -$pointAlpha,
                            'denda'            => $dendaAlpha,
                            'keterangan_denda' => $ketAlpha,
                            'created_at'       => date('Y-m-d H:i:s')
                        ]);
                        $userMonthlyDenda[$uid] += $dendaAlpha;
                        $updatedAlphaCount++;
                        $userTersentuhRecalc[$uid] = true;
                    } elseif (empty($existingRow->jam_masuk)
                        && !in_array(strtoupper((string) ($existingRow->status_masuk ?? '')), ['ONTIME', 'LATE', 'WFH', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS', 'DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_REJECTED', 'DINAS_OUT', 'ALPHA', 'ALPA'], true)
                        && empty($leaveCoveredDates[$uid][$tglAlpha])) {
                        // Baris zombie (status kosong/tak dikenal, tanpa jam, tanpa izin):
                        // normalkan menjadi ALPHA agar state konvergen, bukan diestimasi terus.
                        $db->table('absensi_harian')->where('id', $existingRow->id)->update([
                            'status_masuk'     => 'ALPHA',
                            'points'           => -$pointAlpha,
                            'denda'            => $dendaAlpha,
                            'keterangan_denda' => $ketAlpha,
                        ]);
                        $userMonthlyDenda[$uid] += $dendaAlpha;
                        $updatedAlphaCount++;
                        $userTersentuhRecalc[$uid] = true;
                    } else {
                        $skippedUnchangedCount++;
                    }
                }
            }
        }

        // Hitung ulang bisa melahirkan alpha baru (atau menggugurkan yang lama):
        // sinkronkan surat teguran user yang berubah, bulan aktif saja
        // (fungsi ini dikunci ke bulan berjalan). Best-effort per user.
        $suratTersinkron = 0;
        foreach (array_keys($userTersentuhRecalc) as $uidSync) {
            try {
                $penaltyService->syncSuratTeguranOnAttendanceChange((int) $uidSync, $bulan, $tahun, $setting);
                $suratTersinkron++;
            } catch (\Throwable $eSync) {}
        }

        $namaBulanList = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $namaBulan = $namaBulanList[$bulan] ?? (string) $bulan;
        $msg = "Berhasil menghitung ulang denda presensi periode {$namaBulan} {$tahun}. "
             . "({$updatedAttendanceCount} data presensi dievaluasi, {$updatedAlphaCount} data alpa disinkronkan, {$skippedUnchangedCount} data sudah sesuai dilewati"
             . ($dendaDipertahankan > 0 ? ", {$dendaDipertahankan} denda dipertahankan karena sudah dibayar" : '') . ").";

        return $this->respond(true, $msg, [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'updated_attendance' => $updatedAttendanceCount,
            'updated_alpha' => $updatedAlphaCount,
            'skipped_unchanged' => $skippedUnchangedCount,
            'denda_dipertahankan' => $dendaDipertahankan
        ]);
    }

    /**
     * Tarif Rupiah per poin untuk pelunasan denda metode POIN.
     *
     * Kolom `denda_per_point` sudah lama ada di Pengaturan > Presensi tetapi
     * tidak pernah dibaca kode mana pun, sementara modal denda memakai angka
     * tetap 1000. Nilai 0/kosong berarti belum pernah diisi, jadi tetap 1000
     * supaya instalasi lama tidak tiba-tiba mengubah harga poin.
     */
    private function tarifDendaPerPoin(): float
    {
        try {
            $row = \Config\Database::connect()->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
            $tarif = (float) ($row->denda_per_point ?? 0);
            return $tarif > 0 ? $tarif : 1000.0;
        } catch (\Throwable $e) {
            return 1000.0;
        }
    }

    public function markLunas()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiPembayaranDendaTable();

        $userId = $this->request->getPost('user_id');
        $bulan = $this->request->getPost('bulan');
        $tahun = $this->request->getPost('tahun');
        $keterangan = $this->request->getPost('keterangan');
        $tanggalBayar = $this->request->getPost('tanggal_bayar') ?: date('Y-m-d');
        $metodeBayar = strtoupper(trim($this->request->getPost('metode_bayar') ?? 'TUNAI'));
        if (!in_array($metodeBayar, ['TUNAI', 'POIN', 'SALDO'])) {
            $metodeBayar = 'TUNAI';
        }
        $poinDigunakan = (int) ($this->request->getPost('poin_digunakan') ?? 0);

        if (empty($userId) || empty($bulan) || empty($tahun)) {
            return $this->respond(false, 'Data tidak lengkap');
        }

        $db = \Config\Database::connect();

        // Baca-sisa-lalu-tulis: kunci dulu pembayarannya per guru.
        if (!$this->cobaKunciTulisDenda((int) $userId)) {
            return $this->respond(false, 'Pembayaran lain untuk guru ini masih diproses, ulangi sebentar lagi.', null, null, 409);
        }

        // Sisa bulan target diambil dari getSisaDendaPerBulan agar konsisten dengan
        // tampilan & FIFO: sudah termasuk fallback nominal legacy (COALESCE) dan
        // pengurangan kredit lebih-bayar dari bulan lain.
        $sisaList = $this->getSisaDendaPerBulan($userId, $tahun);
        $match = null;
        foreach ($sisaList as $sRow) {
            if ((int) $sRow['bulan'] === (int) $bulan && (int) $sRow['tahun'] === (int) $tahun) {
                $match = $sRow;
                break;
            }
        }
        if ($match === null) {
            return $this->respond(false, 'Denda bulan ini sudah lunas');
        }
        $calculatedDenda = (float) $match['total_denda'];
        $sisaDenda = (float) $match['sisa'];

        if ($sisaDenda <= 0) {
            return $this->respond(false, 'Denda bulan ini sudah lunas');
        }

        // Pelunasan POIN memakai tarif Rupiah-per-poin dari pengaturan, dan
        // jumlahnya dihitung server. Dulu `poin_digunakan` diambil apa adanya
        // dari modal sementara nominal_bayar selalu sisa penuh, jadi 1 poin
        // cukup untuk melunasi Rp 500.000 dan ledger poin tidak lagi
        // mencerminkan uang yang hilang. Angka di UI hanya saran.
        if ($metodeBayar === 'POIN') {
            $tarifPoin     = $this->tarifDendaPerPoin();
            $poinDigunakan = (int) ceil($sisaDenda / $tarifPoin);
        }

        // Idempotensi klik ganda: kunci unik per aksi bayar (lihat
        // denda.php: UUID baru tiap buka modal). Aksi yang sama dikirim
        // ulang menjawab sukses tanpa menulis ganda.
        $idemKey = $this->normalisasiIdempotencyKey();
        if ($idemKey !== null && $this->pembayaranKunciSudahAda($idemKey)) {
            return $this->respond(true, 'Pembayaran denda (' . $metodeBayar . ') sudah tercatat sebelumnya');
        }

        $db->transBegin();
        try {
            // If paid via SALDO, verify & deduct from absensi_saldo_denda
            if ($metodeBayar === 'SALDO') {
                $saldoDenda = $this->getSaldoDenda($userId);
                if ($saldoDenda < $sisaDenda) {
                    $db->transRollback();
                    return $this->respond(false, 'Saldo denda guru tidak mencukupi (Saldo: Rp ' . number_format($saldoDenda, 0, ',', '.') . ', Dibutuhkan: Rp ' . number_format($sisaDenda, 0, ',', '.') . ')');
                }
                $this->potongSaldoDenda($userId, $sisaDenda, null, 'Pelunasan Denda Periode ' . $bulan . '/' . $tahun . (!empty($keterangan) ? ' (' . $keterangan . ')' : ''));
            }

            // If paid via POIN and poinDigunakan is set, verify & deduct points
            $poinModel = new \App\Models\PoinHistoryModel();
            if ($metodeBayar === 'POIN') {
                $saldo = $poinModel->getSaldo($userId);
                if ($saldo < $poinDigunakan) {
                    $db->transRollback();
                    return $this->respond(false, 'Poin guru tidak mencukupi (Saldo: ' . number_format($saldo) . ' pts, Dibutuhkan: ' . number_format($poinDigunakan) . ' pts untuk Rp ' . number_format($sisaDenda, 0, ',', '.') . ' pada tarif ' . number_format($tarifPoin, 0, ',', '.') . ' Rp/poin)');
                }

                $tercatat = $poinModel->insert([
                    'user_id' => $userId,
                    'poin' => -1 * abs($poinDigunakan),
                    'tipe' => 'REDEEM',
                    'keterangan' => 'Potong Denda Periode ' . $bulan . '/' . $tahun . (!empty($keterangan) ? ' (' . $keterangan . ')' : ''),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($tercatat === false) {
                    // CI4 mengembalikan false (bukan exception) saat DB menolak
                    // insert — tanpanya denda dianggap lunas tanpa poin terpakai.
                    $db->transRollback();
                    log_message('error', '[PresensiAdmin] insert poin REDEEM ditolak DB user#' . $userId . ' periode ' . $bulan . '/' . $tahun);

                    return $this->respond(false, 'Poin belum berhasil dikurangi, sehingga pembayaran denda dibatalkan. Silakan coba lagi.');
                }
                $poinModel->updateTotalPoin($userId);
            }

            $defaultKet = 'Pelunasan Tunai/Transfer';
            if ($metodeBayar === 'POIN') $defaultKet = 'Pelunasan via Potongan Poin';
            if ($metodeBayar === 'SALDO') $defaultKet = 'Pelunasan via Potongan Saldo Denda';

            $db->table('absensi_pembayaran_denda')->insert([
                'user_id' => $userId,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_denda' => $calculatedDenda,
                'nominal_bayar' => $sisaDenda,
                'metode_bayar' => $metodeBayar,
                'poin_digunakan' => ($metodeBayar === 'POIN') ? $poinDigunakan : 0,
                'idempotency_key' => $idemKey,
                'status' => 'lunas',
                'keterangan' => $keterangan ?: $defaultKet,
                'tanggal_bayar' => $tanggalBayar,
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_by' => session()->get('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Pembayaran uang tanpa jejak siapa yang mencatat = tidak bisa
            // direkonstruksi saat selisih. Baris audit ini yang jadi jejaknya.
            try {
                (new AuditLogModel())->log('absensi_pembayaran_denda', $db->insertID(), 'INSERT', (int) $userId, $this->getUserId(),
                    null,
                    [
                        'bulan'          => $bulan,
                        'tahun'          => $tahun,
                        'total_denda'    => $calculatedDenda,
                        'nominal_bayar'  => $sisaDenda,
                        'metode_bayar'   => $metodeBayar,
                        'poin_digunakan' => $poinDigunakan,
                    ],
                    'Pelunasan Denda ' . $bulan . '/' . $tahun . ' via ' . $metodeBayar);
            } catch (\Throwable $ex) {}

            $db->transCommit();
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            // Balapan tulis: kunci unik menolak INSERT kedua. Karena
            // potongan poin/saldo ikut rollback, cukup jawab sudah tercatat.
            if ($idemKey !== null && stripos($e->getMessage(), 'uplicate') !== false && $this->pembayaranKunciSudahAda($idemKey)) {
                return $this->respond(true, 'Pembayaran denda (' . $metodeBayar . ') sudah tercatat sebelumnya');
            }
            log_message('error', 'markLunas gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal mencatat pembayaran — periksa log aplikasi.', null, null, 500);
        }

        return $this->respond(true, 'Pembayaran denda (' . $metodeBayar . ') berhasil dicatat'
            . ($metodeBayar === 'POIN' ? ' — poin dipotong ' . number_format($poinDigunakan) . ' pts' : ''));
    }

    public function batalLunas()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiPembayaranDendaTable();

        $userId = $this->request->getPost('user_id');
        $bulan = $this->request->getPost('bulan');
        $tahun = $this->request->getPost('tahun');

        if (empty($userId) || empty($bulan) || empty($tahun)) {
            return $this->respond(false, 'Data tidak lengkap');
        }

        $db = \Config\Database::connect();

        // Refund poin/saldo juga baca-lalu-tulis; pakai kunci yang sama dengan
        // jalur bayar supaya pembatalan tidak bersilang dengan pelunasan.
        if (!$this->cobaKunciTulisDenda((int) $userId)) {
            return $this->respond(false, 'Transaksi denda lain untuk guru ini masih diproses, ulangi sebentar lagi.', null, null, 409);
        }

        // Batalkan SEMUA baris non-batal bulan ini (bayar nominal bisa membuat
        // >1 baris: sebagian + lunas). Refund poin/saldo per baris.
        $existingRows = $db->table('absensi_pembayaran_denda')
            ->where('user_id', $userId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status !=', 'batal')
            ->get()->getResult();

        if ($existingRows) {
            $db->transBegin();
            try {
                $poinModel = new \App\Models\PoinHistoryModel();
                foreach ($existingRows as $existing) {
                    // If payment was made with POIN and points were deducted, refund them
                    if (($existing->metode_bayar === 'POIN' || !empty($existing->poin_digunakan)) && (int)$existing->poin_digunakan > 0) {
                        $tercatatRefund = $poinModel->insert([
                            'user_id' => $userId,
                            'poin' => abs((int)$existing->poin_digunakan),
                            'tipe' => 'MANUAL_ADD',
                            'keterangan' => 'Pengembalian Poin (Pembatalan Pelunasan Denda ' . $bulan . '/' . $tahun . ')',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        if ($tercatatRefund === false) {
                            // Pembatalan tanpa pencatatan refund = poin guru
                            // hilang diam. Batalkan seluruh pembatalan.
                            $db->transRollback();
                            log_message('error', '[PresensiAdmin] insert refund poin ditolak DB user#' . $userId . ' periode ' . $bulan . '/' . $tahun);

                            return $this->respond(false, 'Poin pengembalian belum berhasil dicatat, pembatalan dibatalkan. Silakan coba lagi.');
                        }
                        $poinModel->updateTotalPoin($userId);
                    }

                    // If payment was made with SALDO, refund saldo denda
                    if ($existing->metode_bayar === 'SALDO') {
                        $nominalRefund = (float)($existing->nominal_bayar ?: $existing->total_denda);
                        $this->tambahSaldoDenda($userId, $nominalRefund, $existing->id, 'Pengembalian Saldo Denda (Pembatalan Pelunasan Periode ' . $bulan . '/' . $tahun . ')');
                    }

                    $db->table('absensi_pembayaran_denda')
                        ->where('id', $existing->id)
                        ->update([
                            'status' => 'batal',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);

                    try {
                        (new AuditLogModel())->log('absensi_pembayaran_denda', $existing->id, 'UPDATE', (int) $userId, $this->getUserId(),
                            ['status' => $existing->status, 'nominal_bayar' => $existing->nominal_bayar, 'poin_digunakan' => $existing->poin_digunakan],
                            ['status' => 'batal'],
                            'Pembatalan Pelunasan Denda ' . $bulan . '/' . $tahun);
                    } catch (\Throwable $ex) {}
                }
                $db->transCommit();
            } catch (\Throwable $e) {
                try { $db->transRollback(); } catch (\Throwable $e2) {}
                log_message('error', 'batalLunas gagal: ' . $e->getMessage());
                return $this->respond(false, 'Gagal membatalkan pelunasan — periksa log aplikasi.', null, null, 500);
            }
        }

        return $this->respond(true, 'Status lunas berhasil dibatalkan');
    }

    /**
     * Serialisasi tulis-denda per guru.
     *
     * markLunas / bayarDendaNominal / batalLunas membaca saldo atau sisa denda
     * dengan SELECT biasa, baru kemudian menulis di dalam transaksi. Dua request
     * yang datang bersamaan — klik ganda admin, atau admin + jalur mobile —
     * bisa sama-sama melihat "saldo cukup" lalu sama-sama memotong, atau sama-
     * sama melihat bulan masih berbaki dan mencatat pelunasan dua kali.
     *
     * GET_LOCK per user menutup jendela itu tanpa butuh hak istimewa DB.
     * Timeout 5 detik: klik ganda cukup antre sebentar, lalu membaca keadaan
     * yang sudah final dan menjawab "sudah lunas" — bukan gagal yang membingungkan.
     *
     * Lock melekat pada koneksi; 'pConnect' => false membuat koneksi ditutup di
     * akhir request, jadi lock lepas sendiri tanpa RELEASE eksplisit.
     *
     * @return bool true = silakan lanjut menulis; false = writer lain masih sibuk.
     */
    private function cobaKunciTulisDenda(int $userId): bool
    {
        $nama = 'absen:denda:' . $userId;
        try {
            $row = \Config\Database::connect()->query('SELECT GET_LOCK(?, 5) AS l', [$nama])->getRow();
        } catch (\Throwable $e) {
            log_message('error', "[denda] GET_LOCK({$nama}) gagal: " . $e->getMessage());
            // Mekanisme lock bukan syarat benar/salahnya pembukuan; kalau query
            // GET_LOCK ditolak, biarkan pembayaran tetap jalan seperti sebelumnya.
            return true;
        }

        if ((int) ($row->l ?? 0) === 1) {
            return true;
        }

        log_message('warning', "[denda] pembayaran user {$userId} ditunda: writer lain masih memegang lock.");

        return false;
    }

    /**
     * Get current active Saldo Denda (fine deposit/balance) for a user
     */
    private function getSaldoDenda($userId)
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_saldo_denda')) return 0;
        $row = $db->table('absensi_saldo_denda')
            ->selectSum('nominal', 'total_saldo')
            ->where('user_id', $userId)
            ->get()->getRow();
        return max(0, (float)($row->total_saldo ?? 0));
    }

    /**
     * Tambah Saldo Denda (misal kelebihan pembayaran)
     */
    private function tambahSaldoDenda($userId, $nominal, $pembayaranId = null, $keterangan = '')
    {
        if ($nominal <= 0) return 0;
        $this->patchAbsensiPembayaranDendaTable();
        $db = \Config\Database::connect();
        $currentSaldo = $this->getSaldoDenda($userId);
        $newSaldo = $currentSaldo + $nominal;
        $db->table('absensi_saldo_denda')->insert([
            'user_id' => $userId,
            'tipe' => 'LEBIH_BAYAR',
            'nominal' => $nominal,
            'saldo_akhir' => $newSaldo,
            'pembayaran_id' => $pembayaranId,
            'keterangan' => $keterangan ?: 'Kelebihan pembayaran denda',
            'created_by' => session()->get('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $newSaldo;
    }

    /**
     * Potong Saldo Denda (misal digunakan bayar denda)
     */
    private function potongSaldoDenda($userId, $nominal, $pembayaranId = null, $keterangan = '')
    {
        if ($nominal <= 0) return 0;
        $this->patchAbsensiPembayaranDendaTable();
        $db = \Config\Database::connect();
        $currentSaldo = $this->getSaldoDenda($userId);
        $newSaldo = max(0, $currentSaldo - $nominal);
        $db->table('absensi_saldo_denda')->insert([
            'user_id' => $userId,
            'tipe' => 'POTONG_DENDA',
            'nominal' => -1 * abs($nominal),
            'saldo_akhir' => $newSaldo,
            'pembayaran_id' => $pembayaranId,
            'keterangan' => $keterangan ?: 'Penggunaan saldo untuk denda',
            'created_by' => session()->get('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $newSaldo;
    }

    /**
     * Get sisa (remaining) denda per bulan for a given user, ordered oldest first (FIFO).
     * Returns array of ['bulan'=>int, 'tahun'=>int, 'total_denda'=>float, 'sudah_dibayar'=>float, 'sisa'=>float]
     */
    private function getSisaDendaPerBulan($userId, $tahun = null)
    {
        // Sumber tunggal: AttendancePenaltyService::sisaDendaPerBulan().
        // $tahun null = lintas tahun (dipakai bayar nominal + preview agar
        // tunggakan lama ikut terlunasi sekaligus). Bentuk kembalian sama:
        // list bulan {bulan,tahun,total_denda,sudah_dibayar,sisa}.
        return (new \App\Services\AttendancePenaltyService())->sisaDendaPerBulan((int) $userId, $tahun);
    }

    /**
     * Bayar denda dengan nominal tertentu, auto-distribusi FIFO ke bulan yang belum lunas.
     * POST: user_id, nominal_bayar, metode_bayar, tanggal_bayar, keterangan, poin_digunakan
     */
    public function bayarDendaNominal()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $this->patchAbsensiPembayaranDendaTable();

        $userId = $this->request->getPost('user_id');
        $nominalBayar = (float) ($this->request->getPost('nominal_bayar') ?? 0);
        $metodeBayar = strtoupper(trim($this->request->getPost('metode_bayar') ?? 'TUNAI'));
        $tanggalBayar = $this->request->getPost('tanggal_bayar') ?: date('Y-m-d');
        $keterangan = $this->request->getPost('keterangan') ?? '';
        $poinDigunakan = (int) ($this->request->getPost('poin_digunakan') ?? 0);
        // Lintas tahun: satu pembayaran melunasi tunggakan tertua dulu
        // tanpa memandang tahun (baris distribusi membawa tahunnya
        // masing-masing). Param tahun lama diabaikan agar arsip ikut lunas.
        $tahunFilter = null;

        if (!in_array($metodeBayar, ['TUNAI', 'POIN', 'SALDO'])) {
            $metodeBayar = 'TUNAI';
        }

        if (empty($userId) || $nominalBayar <= 0) {
            return $this->respond(false, 'Data tidak lengkap. Pastikan user dan nominal bayar terisi.');
        }

        // Idempotensi klik ganda (kunci unik per aksi, sufiks per bulan).
        $idemKey = $this->normalisasiIdempotencyKey();
        if ($idemKey !== null && $this->pembayaranKunciSudahAda($idemKey)) {
            return $this->respond(true, 'Pembayaran Rp ' . number_format($nominalBayar, 0, ',', '.') . ' sudah tercatat sebelumnya');
        }

        // If paying via SALDO (Saldo Denda), verify balance
        if (!$this->cobaKunciTulisDenda((int) $userId)) {
            return $this->respond(false, 'Pembayaran lain untuk guru ini masih diproses, ulangi sebentar lagi.', null, null, 409);
        }

        if ($metodeBayar === 'SALDO') {
            $saldoDenda = $this->getSaldoDenda($userId);
            if ($saldoDenda < $nominalBayar) {
                return $this->respond(false, 'Saldo denda guru tidak mencukupi (Saldo: Rp ' . number_format($saldoDenda, 0, ',', '.') . ', Dibutuhkan: Rp ' . number_format($nominalBayar, 0, ',', '.') . ')');
            }
        }

        // Get unpaid months FIFO
        $sisaPerBulan = $this->getSisaDendaPerBulan($userId, $tahunFilter);

        // If no unpaid months, but teacher pays TUNAI, top up directly to Saldo Denda
        if (empty($sisaPerBulan)) {
            if ($metodeBayar === 'SALDO') {
                return $this->respond(false, 'Guru ini tidak memiliki tunggakan denda.');
            }
            $saldoBaru = $this->tambahSaldoDenda($userId, $nominalBayar, null, 'Setoran Saldo Denda (Tidak ada tunggakan denda)');
            return $this->respond(true, 'Guru tidak memiliki tunggakan denda. Nominal Rp ' . number_format($nominalBayar, 0, ',', '.') . ' telah disimpan menjadi Saldo Denda guru (Total Saldo: Rp ' . number_format($saldoBaru, 0, ',', '.') . ').');
        }

        // Total remaining fines
        $totalSisaAll = array_sum(array_column($sisaPerBulan, 'sisa'));

        // When paying via SALDO, payment is capped at total sisa
        if ($metodeBayar === 'SALDO' && $nominalBayar > $totalSisaAll) {
            $nominalBayar = $totalSisaAll;
        }

        // Bayar dengan POIN: nominal yang menentukan, poin dihitung server
        // dari tarif denda_per_point. Sama seperti markLunas — dulu kolom poin
        // di modal bisa diketik berapa saja sementara Rp-nya tetap penuh,
        // sehingga 1 poin bisa "membayar" ratusan ribu.
        $db = \Config\Database::connect();
        $poinModel = new \App\Models\PoinHistoryModel();
        if ($metodeBayar === 'POIN') {
            $tarifPoin     = $this->tarifDendaPerPoin();
            $poinDigunakan = (int) ceil($nominalBayar / $tarifPoin);
            $saldo = $poinModel->getSaldo($userId);
            if ($poinDigunakan <= 0 || $saldo < $poinDigunakan) {
                return $this->respond(false, 'Poin guru tidak mencukupi (Saldo: ' . number_format($saldo) . ' pts, Dibutuhkan: ' . number_format($poinDigunakan) . ' pts untuk Rp ' . number_format($nominalBayar, 0, ',', '.') . ' pada tarif ' . number_format($tarifPoin, 0, ',', '.') . ' Rp/poin)');
            }
        }

        // Distribute payment FIFO
        $sisaNominal = $nominalBayar;
        $distribusi = [];
        $bulanTerbayar = [];

        foreach ($sisaPerBulan as $item) {
            if ($sisaNominal <= 0) break;

            $bayarBulanIni = min($sisaNominal, $item['sisa']);
            $statusBulanIni = ($bayarBulanIni >= $item['sisa']) ? 'lunas' : 'sebagian';

            $distribusi[] = [
                'bulan' => $item['bulan'],
                'tahun' => $item['tahun'],
                'total_denda' => $item['total_denda'],
                'nominal_bayar' => $bayarBulanIni,
                'status' => $statusBulanIni,
            ];

            $sisaNominal -= $bayarBulanIni;
            $bulanTerbayar[] = str_pad($item['bulan'], 2, '0', STR_PAD_LEFT) . '/' . $item['tahun'];
        }

        // Semua tulis (poin/saldo/payment) dalam satu transaksi agar double-submit
        // tidak menghasilkan potongan ganda atau baris pembayaran ganda.
        $db->transBegin();
        try {
            // Deduct points once for the whole payment if POIN method
            if ($metodeBayar === 'POIN' && $poinDigunakan > 0) {
                $bulanStr = implode(', ', $bulanTerbayar);
                $tercatat = $poinModel->insert([
                    'user_id' => $userId,
                    'poin' => -1 * abs($poinDigunakan),
                    'tipe' => 'REDEEM',
                    'keterangan' => 'Potong Denda Periode ' . $bulanStr . (!empty($keterangan) ? ' (' . $keterangan . ')' : ''),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($tercatat === false) {
                    $db->transRollback();
                    log_message('error', '[PresensiAdmin] insert poin REDEEM ditolak DB user#' . $userId . ' periode ' . $bulanStr);

                    return $this->respond(false, 'Poin belum berhasil dikurangi, sehingga pembayaran denda dibatalkan. Silakan coba lagi.');
                }
                $poinModel->updateTotalPoin($userId);
            }

            // Handle excess payment or saldo deduction
            $lebihBayar = max(0, $nominalBayar - $totalSisaAll);
            $saldoAkhir = 0;

            if ($metodeBayar === 'SALDO') {
                $nominalPakai = min($nominalBayar, $totalSisaAll);
                $saldoAkhir = $this->potongSaldoDenda($userId, $nominalPakai, null, 'Pembayaran denda periode ' . implode(', ', $bulanTerbayar));
            } elseif ($lebihBayar > 0) {
                $saldoAkhir = $this->tambahSaldoDenda($userId, $lebihBayar, null, 'Kelebihan pembayaran denda tanggal ' . date('d/m/Y', strtotime($tanggalBayar)));
            }

            // Default keterangan
            $defaultKet = 'Pembayaran Rp ' . number_format($nominalBayar, 0, ',', '.') . ' - distribusi otomatis';
            if ($metodeBayar === 'SALDO') {
                $defaultKet = 'Pembayaran via Potongan Saldo Denda';
            }

            // Insert payment records per month — poin_digunakan didistribusikan
            // proporsional per baris agar batalLunas bisa refund per baris
            // (sebelumnya selalu 0 → poin FIFO hangus saat dibatalkan).
            $totalDistribusi = array_sum(array_column($distribusi, 'nominal_bayar'));
            $sisaPoin = (int) $poinDigunakan;
            $nDist = count($distribusi);
            foreach ($distribusi as $idx => $d) {
                $poinBaris = 0;
                if ($metodeBayar === 'POIN' && $totalDistribusi > 0 && $sisaPoin > 0) {
                    if ($idx === $nDist - 1) {
                        $poinBaris = $sisaPoin;
                    } else {
                        $poinBaris = (int) floor($d['nominal_bayar'] / $totalDistribusi * $poinDigunakan);
                        $sisaPoin -= $poinBaris;
                    }
                }
                $db->table('absensi_pembayaran_denda')->insert([
                    'user_id' => $userId,
                    'bulan' => $d['bulan'],
                    'tahun' => $d['tahun'],
                    'total_denda' => $d['total_denda'],
                    'nominal_bayar' => $d['nominal_bayar'],
                    'metode_bayar' => $metodeBayar,
                    'poin_digunakan' => $poinBaris,
                    'idempotency_key' => $idemKey !== null ? $idemKey . ':' . $d['tahun'] . '-' . str_pad((string) $d['bulan'], 2, '0', STR_PAD_LEFT) : null,
                    'status' => $d['status'],
                    'keterangan' => $keterangan ?: $defaultKet,
                    'tanggal_bayar' => $tanggalBayar,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'paid_by' => session()->get('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            try {
                (new AuditLogModel())->log('absensi_pembayaran_denda', null, 'INSERT', (int) $userId, $this->getUserId(),
                    null,
                    [
                        'nominal_bayar'  => $nominalBayar,
                        'metode_bayar'   => $metodeBayar,
                        'poin_digunakan' => $poinDigunakan,
                        'distribusi'     => array_map(
                            static fn ($d) => ['bulan' => $d['bulan'], 'tahun' => $d['tahun'], 'nominal' => $d['nominal_bayar'], 'status' => $d['status']],
                            $distribusi
                        ),
                    ],
                    'Pembayaran Nominal Denda Rp ' . number_format($nominalBayar, 0, ',', '.') . ' via ' . $metodeBayar);
            } catch (\Throwable $ex) {}

            $db->transCommit();
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            // Balapan tulis: kunci unik menolak baris kedua. Potongan
            // poin/saldo ikut rollback, cukup jawab sudah tercatat.
            if ($idemKey !== null && stripos($e->getMessage(), 'uplicate') !== false && $this->pembayaranKunciSudahAda($idemKey)) {
                return $this->respond(true, 'Pembayaran Rp ' . number_format($nominalBayar, 0, ',', '.') . ' sudah tercatat sebelumnya');
            }
            log_message('error', 'bayarDendaNominal gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal mencatat pembayaran — periksa log aplikasi.', null, null, 500);
        }

        // Build summary message
        $bulanStr = implode(', ', $bulanTerbayar);
        if ($lebihBayar > 0) {
            $msg = 'Pembayaran Rp ' . number_format($nominalBayar, 0, ',', '.') . ' (' . $metodeBayar . ') berhasil dicatat. Seluruh denda (' . $bulanStr . ') telah lunas, dan kelebihan sebesar Rp ' . number_format($lebihBayar, 0, ',', '.') . ' disimpan sebagai Saldo Denda guru (Total Saldo: Rp ' . number_format($saldoAkhir, 0, ',', '.') . ').';
        } else {
            $msg = 'Pembayaran Rp ' . number_format($nominalBayar, 0, ',', '.') . ' (' . $metodeBayar . ') berhasil dicatat untuk ' . count($distribusi) . ' bulan: ' . $bulanStr;
            if ($metodeBayar === 'SALDO') {
                $msg .= ' (Sisa Saldo Denda: Rp ' . number_format($saldoAkhir, 0, ',', '.') . ')';
            }
        }

        return $this->respond(true, $msg, ['distribusi' => $distribusi, 'lebih_bayar' => $lebihBayar]);
    }

    /**
     * AJAX: Preview distribusi pembayaran nominal ke bulan-bulan belum lunas (FIFO)
     */
    public function previewDistribusi()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $userId = $this->request->getGet('user_id') ?? $this->request->getPost('user_id');
        $nominalBayar = (float) ($this->request->getGet('nominal_bayar') ?? $this->request->getPost('nominal_bayar') ?? 0);

        if (empty($userId) || $nominalBayar <= 0) {
            return $this->respond(false, 'Data tidak lengkap');
        }

        // Lintas tahun seperti bayarDendaNominal agar preview = realisasi.
        $sisaPerBulan = $this->getSisaDendaPerBulan($userId, null);
        $totalSisa = array_sum(array_column($sisaPerBulan, 'sisa'));
        $saldoDendaSekarang = $this->getSaldoDenda($userId);

        if (empty($sisaPerBulan)) {
            return $this->respond(true, 'Tidak ada tunggakan', [
                'distribusi' => [],
                'total_sisa' => 0,
                'nominal_bayar' => $nominalBayar,
                'alokasi_denda' => 0,
                'lebih_bayar' => $nominalBayar,
                'sisa_setelah_bayar' => 0,
                'saldo_denda_sekarang' => $saldoDendaSekarang,
            ]);
        }

        // Simulate FIFO distribution
        $sisaNominal = $nominalBayar;
        $distribusi = [];

        foreach ($sisaPerBulan as $item) {
            if ($sisaNominal <= 0) break;

            $bayarBulanIni = min($sisaNominal, $item['sisa']);
            $distribusi[] = [
                'bulan' => $item['bulan'],
                'tahun' => $item['tahun'],
                'total_denda' => $item['total_denda'],
                'sisa_sebelum' => $item['sisa'],
                'nominal_bayar' => $bayarBulanIni,
                'sisa_sesudah' => $item['sisa'] - $bayarBulanIni,
                'status' => ($bayarBulanIni >= $item['sisa']) ? 'LUNAS' : 'SEBAGIAN',
            ];

            $sisaNominal -= $bayarBulanIni;
        }

        $alokasiDenda = min($nominalBayar, $totalSisa);
        $lebihBayar = max(0, $nominalBayar - $totalSisa);
        $sisaSetelahBayar = max(0, $totalSisa - $nominalBayar);

        return $this->respond(true, 'Preview distribusi', [
            'distribusi' => $distribusi,
            'total_sisa' => $totalSisa,
            'nominal_bayar' => $nominalBayar,
            'alokasi_denda' => $alokasiDenda,
            'lebih_bayar' => $lebihBayar,
            'sisa_setelah_bayar' => $sisaSetelahBayar,
            'saldo_denda_sekarang' => $saldoDendaSekarang,
        ]);
    }

    /**
     * Laporan Pembayaran Denda - with filters for teacher, date range, method
     */
    public function laporanPembayaran()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $data = $this->getLaporanPembayaranData();

        return view('admin/presensi/laporan_pembayaran', $data);
    }

    /**
     * Unduh Laporan Pembayaran Denda sebagai PDF (filter ikut terbawa).
     */
    public function exportLaporanPdf()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $data = $this->getLaporanPembayaranData();

        $html = view('admin/presensi/laporan_pembayaran_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Laporan_Pembayaran_Denda_' . ($data['filterTglDari'] ?? date('Y-m-d'))
            . '_sd_' . ($data['filterTglSampai'] ?? date('Y-m-d')) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output())
            ->setStatusCode(200);
    }

    /**
     * Query laporan pembayaran + ringkasan (dipakai halaman & PDF).
     */
    private function getLaporanPembayaranData(): array
    {
        $this->patchAbsensiPembayaranDendaTable();

        $db = \Config\Database::connect();

        $filterUserId = $this->request->getGet('user_id');
        $filterTglDari = $this->request->getGet('tgl_dari') ?: date('Y-m-01');
        $filterTglSampai = $this->request->getGet('tgl_sampai') ?: date('Y-m-d');
        $filterMetode = $this->request->getGet('metode');

        $builder = $db->table('absensi_pembayaran_denda pd')
            ->select('pd.*, mg.nama_guru, mg.nip, admin.first_name as admin_first, admin.last_name as admin_last')
            ->join('master_guru mg', 'mg.id_user = pd.user_id', 'left')
            ->join('users admin', 'admin.id = pd.paid_by', 'left')
            ->where('pd.status !=', 'batal');

        if ($filterUserId) {
            $builder->where('pd.user_id', $filterUserId);
        }
        if ($filterTglDari) {
            $builder->where('pd.tanggal_bayar >=', $filterTglDari);
        }
        if ($filterTglSampai) {
            $builder->where('pd.tanggal_bayar <=', $filterTglSampai);
        }
        if ($filterMetode && in_array(strtoupper($filterMetode), ['TUNAI', 'POIN', 'SALDO'])) {
            $builder->where('pd.metode_bayar', strtoupper($filterMetode));
        }

        $payments = $builder->orderBy('pd.tanggal_bayar', 'DESC')
            ->orderBy('pd.paid_at', 'DESC')
            ->get()->getResult();

        // Summary
        $totalNominal = 0;
        $totalTunai = 0;
        $totalPoin = 0;
        $totalSaldo = 0;
        foreach ($payments as $p) {
            $nominal = (float)($p->nominal_bayar ?: $p->total_denda);
            $totalNominal += $nominal;
            switch (\App\Services\AttendancePenaltyService::kelasPembayaranDenda($p->metode_bayar, $p->keterangan ?? null)) {
                case 'POIN':
                    $totalPoin += $nominal;
                    break;
                case 'SALDO':
                    $totalSaldo += $nominal;
                    break;
                default:
                    $totalTunai += $nominal;
            }
        }

        // Guru list for filter dropdown
        $guruList = $db->table('users u')
            ->select('u.id, g.nama_guru, g.nip')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        $setting = $this->dashboardModel->getSetting();
        $tp_active = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        if (!$setting) $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        // Nama guru terfilter (untuk judul PDF)
        $filterGuruNama = null;
        if ($filterUserId) {
            foreach ($guruList as $g) {
                if ((string) $g->id === (string) $filterUserId) {
                    $filterGuruNama = $g->nama_guru;
                    break;
                }
            }
        }

        return [
            'title' => 'Laporan Pembayaran Denda',
            'judul' => 'Laporan Pembayaran',
            'setting' => $setting,
            'tp_active' => $tp_active,
            'smt_active' => $smt_active,
            'user' => $this->getUser(),
            'payments' => $payments,
            'guruList' => $guruList,
            'filterUserId' => $filterUserId,
            'filterGuruNama' => $filterGuruNama,
            'filterTglDari' => $filterTglDari,
            'filterTglSampai' => $filterTglSampai,
            'filterMetode' => $filterMetode,
            'totalNominal' => $totalNominal,
            'totalTunai' => $totalTunai,
            'totalPoin' => $totalPoin,
            'totalSaldo' => $totalSaldo,
        ];
    }

    /**
     * Backup Database for Attendance Tables
     */
    public function backupDatabase()
    {
        if (!$this->isAdmin()) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        $tables = [
            'absensi_harian',
            'absensi_koreksi',
            'absensi_izin',
            'absensi_lokasi',
            'absensi_setting',
            'absensi_jadwal_kerja',
            'absensi_hari_libur',
            'absensi_pembayaran_denda'
        ];

        $sql = "-- Backup Database Absensi \n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Generated by Admin Panel\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            if (!$db->tableExists($table))
                continue;

            // Structure
            $sql .= "-- Table structure for table `$table` --\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $query = $db->query("SHOW CREATE TABLE `$table`")->getRowArray();
            $sql .= $query['Create Table'] . ";\n\n";

            // Data
            $sql .= "-- Dumping data for table `$table` --\n";
            $rows = $db->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $insertKeys = array_map(function ($k) {
                        return "`$k`";
                    }, array_keys($row));
                    $insertVals = array_map(function ($v) use ($db) {
                        return $db->escape($v);
                    }, array_values($row));
                    $sql .= "INSERT INTO `$table` (" . implode(', ', $insertKeys) . ") VALUES (" . implode(', ', $insertVals) . ");\n";
                }
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup_absensi_' . date('Y-m-d_H-i-s') . '.sql';

        return $this->response->download($filename, $sql);
    }

    /**
     * Backup Data as JSON
     */
    public function backupDataJson()
    {
        if (!$this->isAdmin()) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();
        
        // Dynamically fetch tables
        $tables = [];
        $allTables = $db->listTables();
        foreach ($allTables as $t) {
            if (strpos($t, 'absensi_') === 0) {
                $tables[] = $t;
            }
        }
        
        // Core tables fallback
        $coreTables = [
            'absensi_harian',
            'absensi_koreksi',
            'absensi_izin',
            'absensi_lokasi',
            'absensi_setting', 
            'absensi_jadwal_kerja',
            'absensi_hari_libur',
            'absensi_pembayaran_denda'
        ];
        foreach ($coreTables as $ct) {
            if (!in_array($ct, $tables)) $tables[] = $ct;
        }

        $data = [
            'meta' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'version' => '1.0'
            ],
            'tables' => []
        ];

        foreach ($tables as $table) {
            if ($db->tableExists($table)) {
                $data['tables'][$table] = $db->table($table)->get()->getResultArray();
            }
        }

        $filename = 'backup_absensi_json_' . date('Y-m-d_H-i-s') . '.json';

        return $this->response->download($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Restore Data from JSON
     */
    public function restoreDataJson()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $file   = $this->request->getFile('file_backup');
        $isAjax = $this->request->isAJAX();

        $tolak = function (string $msg) use ($isAjax) {
            if ($isAjax) {
                return $this->respond(false, $msg);
            }
            session()->setFlashdata('message', ['type' => 'error', 'text' => $msg]);
            return redirect()->to('admin/presensi/setting');
        };

        if (!$file || !$file->isValid()) {
            return $tolak('File tidak valid atau tidak ada');
        }

        if ($file->getClientExtension() !== 'json') {
            return $tolak('Format file harus JSON');
        }

        // Batas ukuran: semua baris ditulis dalam satu transaksi, file raksasa
        // akan mengunci database sampai timeout.
        if ((int) $file->getSize() > 20 * 1024 * 1024) {
            return $tolak('File backup terlalu besar (maks 20 MB). Pecah backup per tabel lebih dulu.');
        }

        $content = file_get_contents($file->getTempName());
        $data    = json_decode((string) $content, true);

        if (!is_array($data) || !isset($data['tables']) || !is_array($data['tables'])) {
            return $tolak('Struktur file JSON tidak valid: bagian "tables" tidak ditemukan.');
        }

        try {
            $db = \Config\Database::connect();
            $db->transBegin();

            $ditulis = 0;
            $alasan  = [];

            foreach ($data['tables'] as $tableName => $rows) {
                $tableName = (string) $tableName;

                // backupDataJson hanya mengekspor tabel berafiks absensi_, jadi
                // restore pun tidak boleh menyentuh tabel lain — file upload bisa
                // berisi apa saja, termasuk users/groups/migrations.
                if (!preg_match('/^absensi_[A-Za-z0-9_]{1,50}$/', $tableName)) {
                    $alasan[] = $tableName . ': tabel di luar kelompok absensi tidak dipulihkan';
                    continue;
                }
                if (!$db->tableExists($tableName)) {
                    $alasan[] = $tableName . ': tabel tidak ada di database ini';
                    continue;
                }
                if ($rows === [] || !is_array($rows)) {
                    $alasan[] = $tableName . ': tidak ada baris untuk dipulihkan';
                    continue;
                }

                $kolomSah = array_map(
                    static fn ($f) => (string) (is_object($f) ? ($f->name ?? '') : ($f['Field'] ?? '')),
                    $db->getFieldData($tableName)
                );

                $builder = $db->table($tableName);
                foreach ($rows as $nomor => $row) {
                    if (!is_array($row) || $row === []) {
                        $alasan[] = $tableName . ' baris #' . $nomor . ': bentuk baris tidak dikenali';
                        continue;
                    }

                    $bersih = array_intersect_key($row, array_flip($kolomSah));

                    // REPLACE dengan nilai array menghasilkan 'Array' (kolom DATE
                    // jadi zero-date) tapi tetap dilaporkan berhasil — baris dengan
                    // nilai bukan skalar dibatalkan seluruhnya, tidak ditulis separuh.
                    $cacat = [];
                    foreach ($bersih as $kolom => $nilai) {
                        if ($nilai !== null && !is_scalar($nilai)) {
                            $cacat[] = (string) $kolom;
                        }
                    }
                    if ($cacat !== []) {
                        $alasan[] = $tableName . ' baris #' . $nomor . ': kolom ' . implode(',', $cacat) . ' berisi struktur, bukan nilai';
                        continue;
                    }

                    if ($bersih === []) {
                        $alasan[] = $tableName . ' baris #' . $nomor . ': tidak ada kolom yang cocok';
                        continue;
                    }

                    if ($builder->replace($bersih) === false) {
                        log_message('error', '[restoreDataJson] ' . $tableName . ' #' . $nomor . ' gagal: ' . json_encode($db->error()));
                        $alasan[] = $tableName . ' baris #' . $nomor . ': penulisan ditolak database';
                        continue;
                    }
                    $ditulis++;
                }
            }

            if ($alasan !== []) {
                // Restore bersifat all-or-nothing: sebagian tabel boleh saja
                // berasal dari skema lama, tapi memulihkan separuh data absensi
                // tanpa pemberitahuan lebih berbahaya daripada menolak file-nya.
                $db->transRollback();
                log_message('error', '[restoreDataJson] rollback, ' . count($alasan) . ' masalah: ' . json_encode(array_slice($alasan, 0, 20)));
                return $tolak('Restore dibatalkan, tidak ada baris yang tersimpan. '
                    . count($alasan) . ' masalah, contoh: ' . implode('; ', array_slice($alasan, 0, 3)));
            }

            $db->transCommit();

            if ($db->transStatus() === false) {
                log_message('error', '[restoreDataJson] transCommit gagal: ' . json_encode($db->error()));
                return $tolak('Gagal merestore database. Transaksi dibatalkan.');
            }

            $msg = 'Restore berhasil. ' . $ditulis . ' baris data ditulis.';
            if ($isAjax) {
                return $this->respond(true, $msg);
            }
            session()->setFlashdata('message', ['type' => 'success', 'text' => $msg]);
            return redirect()->to('admin/presensi/setting');

        } catch (\Throwable $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            log_message('error', '[restoreDataJson] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            // Pesan exception memuat nama kolom/SQL — jangan diteruskan ke browser.
            return $tolak('Restore gagal karena struktur file backup tidak cocok dengan database. Detail ada di log aplikasi.');
        }
    }

    private function calculateProgressivePenalty(int $minutes, $setting, string $type = 'Telat'): array
    {
        // Sumber tunggal di AttendancePenaltyService (dulu duplikat di 2 controller).
        if (is_array($setting)) {
            $setting = (object) $setting;
        }
        return (new \App\Services\AttendancePenaltyService())->calculatePenalty($minutes, $setting, $type);
    }

    private function calculateProgressiveRewardPoints(int $minutes, $setting): int
    {
        // Sumber tunggal di AttendancePenaltyService.
        if (is_array($setting)) {
            $setting = (object) $setting;
        }
        return (new \App\Services\AttendancePenaltyService())->calculateReward($minutes, $setting);
    }

    /**
     * Clean old attendance photos (before current month)
     * to save disk space
     */
    public function cleanOldPhotos()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $db = \Config\Database::connect();
        
        // Start of current month
        $firstDayOfMonth = date('Y-m-01');

        // Find all photos before this month
        $records = $db->table('absensi_harian')
            ->select('id, foto_masuk, foto_pulang')
            ->where('tgl <', $firstDayOfMonth)
            ->groupStart()
                ->where('foto_masuk IS NOT NULL')
                ->where('foto_masuk !=', '')
                ->orWhere('foto_pulang IS NOT NULL')
                ->orWhere('foto_pulang !=', '')
            ->groupEnd()
            ->get()->getResult();

        $deletedCount = 0;
        $pathTerhapus = [];
        $gagalUpdate  = 0;
        $lewatPath    = 0;
        $db->transStart();

        foreach ($records as $row) {
            $updateData = [];

            if (!empty($row->foto_masuk)) {
                $updateData['foto_masuk'] = null;
            }
            if (!empty($row->foto_pulang)) {
                $updateData['foto_pulang'] = null;
            }

            // Nilai kolom path datang dari database dan bisa saja berisi '..'.
            // Hapus hanya file yang realpath-nya masih di bawah uploads/, dan
            // penundaan penghapusan fisiknya sampai transaksi commit.
            foreach (['foto_masuk', 'foto_pulang'] as $kolom) {
                if (empty($row->{$kolom})) {
                    continue;
                }
                $nyata = $this->pathFotoAman((string) $row->{$kolom});
                if ($nyata === null) {
                    $lewatPath++;
                    continue;
                }
                $pathTerhapus[] = $nyata;
            }

            if ($updateData !== []) {
                if ($db->table('absensi_harian')->where('id', $row->id)->update($updateData) === false) {
                    $gagalUpdate++;
                    log_message('error', '[cleanOldPhotos] update id ' . $row->id . ' gagal: ' . json_encode($db->error()));
                }
            }
        }

        if ($gagalUpdate > 0) {
            $db->transRollback();
        }

        $db->transComplete();

        if ($db->transStatus() === false || $gagalUpdate > 0) {
            return $this->respond(false, 'Gagal membersihkan foto: ' . $gagalUpdate . ' baris tidak bisa diperbarui, tidak ada file yang dihapus.');
        }

        // Database sudah pasti melepas referensinya — baru file fisiknya hilang.
        foreach ($pathTerhapus as $path) {
            if (is_file($path) && @unlink($path)) {
                $deletedCount++;
            }
        }

        // Log this action
        if (class_exists('App\Models\AuditLogModel')) {
            $logModel = new \App\Models\AuditLogModel();
            if (method_exists($logModel, 'saveLog')) {
                $logModel->saveLog(3, "Membersihkan {$deletedCount} foto absensi lama");
            }
        }

        $pesan = "Berhasil menghapus " . $deletedCount . " file foto absensi lama dari server.";
        if ($lewatPath > 0) {
            $pesan .= " " . $lewatPath . " referensi dilewati karena path-nya di luar folder uploads.";
        }

        return $this->respond(true, $pesan);
    }

    /**
     * Path foto absensi yang dijamin masih di dalam FCPATH.'uploads'
     * (traversal/symlink keluar → null).
     */
    private function pathFotoAman(string $rel): ?string
    {
        $root = realpath(FCPATH . 'uploads');
        if ($root === false) {
            return null;
        }

        $rel = str_replace('\\', '/', ltrim(trim($rel), '/'));
        if ($rel === '' || str_contains($rel, "\0")) {
            return null;
        }

        $nyata = realpath(FCPATH . $rel);
        if ($nyata === false || !str_starts_with($nyata, $root . DIRECTORY_SEPARATOR) || !is_file($nyata)) {
            return null;
        }

        return $nyata;
    }

    // ================================================================
    // REKAP SEMESTER
    // ================================================================

    /**
     * Halaman rekap kehadiran per semester (standar SMA / Dinas Pendidikan).
     */
    public function rekapSemester()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $curYear = (int) date('Y');
        $tahun    = (int) ($this->request->getGet('tahun')    ?? $curYear);
        $semester = (int) ($this->request->getGet('semester') ?? ((date('m') >= 7) ? 1 : 2));

        // Validasi + clamp agar tak ada periode mustahil
        if ($tahun < attendance_tahun_tertua() || $tahun > $curYear + 1) $tahun = $curYear;
        if (!in_array($semester, [1, 2], true)) $semester = ((int) date('m') >= 7) ? 1 : 2;

        $db = \Config\Database::connect();

        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting      = $absenSettingModel->first();
        $minPersen         = (float) ($absenSetting->min_kehadiran_persen ?? 90.0);

        $rekap = [];
        if ($db->tableExists('absensi_rekap_semester')) {
            $rekap = $db->table('absensi_rekap_semester rs')
                ->select('rs.*, COALESCE(mg.nama_guru, u.username) as nama_guru, mg.nip, mg.foto')
                ->join('users u', 'u.id = rs.user_id', 'left')
                ->join('master_guru mg', 'mg.id_user = rs.user_id', 'left')
                ->where('rs.tahun', $tahun)
                ->where('rs.semester', $semester)
                ->get()->getResult();

            // Urutkan: tidak_layak -> perhatian -> layak, lalu nama guru
            $statusOrder = ['tidak_layak' => 0, 'perhatian' => 1, 'layak' => 2];
            usort($rekap, function ($a, $b) use ($statusOrder) {
                $sa = $statusOrder[$a->status_kelayakan] ?? 2;
                $sb = $statusOrder[$b->status_kelayakan] ?? 2;
                if ($sa !== $sb) return $sa <=> $sb;
                return strcmp((string) ($a->nama_guru ?? ''), (string) ($b->nama_guru ?? ''));
            });
        }

        $summary = [
            'guru'            => count($rekap),
            'layak'           => 0,
            'perhatian'       => 0,
            'tidak_layak'     => 0,
            'rata_kehadiran'  => 0.0,
        ];
        if ($rekap) {
            $rata = 0;
            foreach ($rekap as $r) {
                $rata += (float) $r->persentase_kehadiran;
                if ($r->status_kelayakan === 'tidak_layak') {
                    $summary['tidak_layak']++;
                } elseif ($r->status_kelayakan === 'perhatian') {
                    $summary['perhatian']++;
                } else {
                    $summary['layak']++;
                }
            }
            $summary['rata_kehadiran'] = round($rata / count($rekap), 1);
        }

        // Meta: total guru aktif, kesegaran data, dan rentang periode
        $startBulan = ($semester === 1) ? 7 : 1;
        $endBulan   = ($semester === 1) ? 12 : 6;
        $startDate  = sprintf('%04d-%02d-01', $tahun, $startBulan);
        $endDate    = date('Y-m-t', mktime(0, 0, 0, $endBulan, 1, $tahun));
        $today      = date('Y-m-d');
        $isPartial  = $endDate > $today;

        $totalGuruAktif = 0;
        try {
            $totalGuruAktif = (int) $db->table('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)->where('u.active', 1)
                ->countAllResults();
        } catch (\Throwable $e) {}

        $generatedAt = null;
        $maxAbsensiTgl = null;
        $isStale = false;
        try {
            if ($db->tableExists('absensi_rekap_semester')) {
                $gRow = $db->table('absensi_rekap_semester')
                    ->selectMax('generated_at', 'maks')
                    ->where('tahun', $tahun)->where('semester', $semester)
                    ->get()->getRow();
                $generatedAt = $gRow->maks ?? null;
            }
            if ($db->tableExists('absensi_harian')) {
                $aRow = $db->table('absensi_harian')
                    ->selectMax('tgl', 'maks')
                    ->where('tgl >=', $startDate)->where('tgl <=', min($endDate, $today))
                    ->get()->getRow();
                $maxAbsensiTgl = $aRow->maks ?? null;
            }
            // Stale: ada absensi lebih baru dari waktu generate, atau guru aktif > baris rekap
            if ($generatedAt && $maxAbsensiTgl && $maxAbsensiTgl > substr($generatedAt, 0, 10)) $isStale = true;
            if ($totalGuruAktif > count($rekap)) $isStale = true;
        } catch (\Throwable $e) {}

        $semLabel = ($semester === 1) ? 'Ganjil' : 'Genap';
        $tpStart  = ($semester === 1) ? $tahun : $tahun - 1;
        $meta = [
            'periode'      => ($semester === 1) ? "Juli {$tahun} – Desember {$tahun}" : "Januari {$tahun} – Juni {$tahun}",
            'tahunAjaran'  => "{$tpStart}/" . ($tpStart + 1),
            'semLabel'     => $semLabel,
            'isPartial'    => $isPartial,
            'totalGuru'    => $totalGuruAktif,
            'generatedAt'  => $generatedAt,
            'maxAbsensi'   => $maxAbsensiTgl,
            'isStale'      => $isStale && !empty($rekap),
        ];

        $appSetting = $this->dashboardModel->getSetting();
        $tp_active  = $this->dashboardModel->getTahunActive();
        $smt_active = $this->dashboardModel->getSemesterActive();

        return view('admin/presensi/rekap_semester', [
            'title'      => 'Rekap Semester',
            'judul'      => 'Rekap Kehadiran Per Semester',
            'setting'    => $appSetting ?: (object)['nama_aplikasi' => 'absensi by ikasmedia'],
            'tp_active'  => $tp_active,
            'smt_active' => $smt_active,
            'user'       => $this->getUser(),
            'rekap'      => $rekap,
            'summary'    => $summary,
            'tahun'      => $tahun,
            'semester'   => $semester,
            'minPersen'  => $minPersen,
            'meta'       => $meta,
        ]);
    }

    /**
     * Generate / update rekap semester via tombol di view (bukan cron).
     * Dijalankan langsung dalam proses ini (tanpa HTTP/curl) agar tidak
     * membocorkan secret key lewat URL dan tidak tergantung koneksi loopback.
     */
    public function generateRekapSemester()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $curYear  = (int) date('Y');
        // Route generate adalah POST (mutasi DB → dilindungi CSRF global).
        $tahun    = (int) ($this->request->getPost('tahun')    ?: $curYear);
        $semester = (int) ($this->request->getPost('semester') ?: ((date('m') >= 7) ? 1 : 2));

        if ($tahun < attendance_tahun_tertua() || $tahun > $curYear + 1 || !in_array($semester, [1, 2], true)) {
            return redirect()
                ->to(site_url('admin/presensi/rekap-semester'))
                ->with('error', 'Parameter tahun/semester tidak valid.');
        }

        try {
            $cron    = new \App\Controllers\CronController();
            $body    = $cron->runSemesterRecap($tahun, $semester);

            // runSemesterRecap() mengembalikan string status, bukan melempar
            // exception. Tanpa pemeriksaan ini, admin melihat
            // "berhasil di-generate (GAGAL: tabel ... tidak ada)".
            if (str_starts_with($body, 'GAGAL:')) {
                log_message('error', 'generateRekapSemester: ' . $body);
                return redirect()
                    ->to(site_url("admin/presensi/rekap-semester?tahun={$tahun}&semester={$semester}"))
                    ->with('error', 'Rekap semester gagal di-generate — periksa log aplikasi.');
            }

            $message = 'Rekap semester berhasil di-generate (' . $body . ').';
        } catch (\Throwable $e2) {
            log_message('error', 'generateRekapSemester: ' . $e2->getMessage());
            return redirect()
                ->to(site_url("admin/presensi/rekap-semester?tahun={$tahun}&semester={$semester}"))
                ->with('error', 'Rekap semester gagal di-generate — periksa log aplikasi.');
        }

        return redirect()
            ->to(site_url("admin/presensi/rekap-semester?tahun={$tahun}&semester={$semester}"))
            ->with('success', $message);
    }

    /**
     * Export rekap semester ke Excel.
     */
    public function exportRekapSemester()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $tahun    = (int) ($this->request->getGet('tahun')    ?? date('Y'));
        $semester = (int) ($this->request->getGet('semester') ?? 1);
        $curYear  = (int) date('Y');
        if ($tahun < attendance_tahun_tertua() || $tahun > $curYear + 1) $tahun = $curYear;
        if (!in_array($semester, [1, 2], true)) $semester = 1;
        $export    = $this->fetchRekapSemesterData($tahun, $semester);
        $rekap     = $export['rekap'];
        $minPersen = $export['minPersen'];
        $semLabel  = $export['semLabel'];
        $appSetting = $export['setting'];

        if (empty($rekap)) {
            return redirect()
                ->to(site_url("admin/presensi/rekap-semester?tahun={$tahun}&semester={$semester}"))
                ->with('error', "Belum ada data rekap semester {$semLabel} {$tahun}. Klik Generate Rekap terlebih dahulu.");
        }

        $rata = $rekap ? round(array_sum(array_map(fn($r) => (float) $r->persentase_kehadiran, $rekap)) / count($rekap), 1) : 0.0;

        $html  = view('admin/presensi/rekap_semester_excel', [
            'rekap'     => $rekap,
            'tahun'     => $tahun,
            'semester'  => $semester,
            'semLabel'  => $semLabel,
            'minPersen' => $minPersen,
            'rata'      => $rata,
            'setting'   => $appSetting ?: (object)['nama_sekolah' => 'Sekolah'],
        ]);

        $filename = "Rekap_Semester_{$semLabel}_{$tahun}.xls";

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setHeader('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setBody("\xEF\xBB\xBF" . $html);
    }

    /**
     * Ambil data rekap semester terurut + meta export.
     * Dipakai bersama oleh export Excel & PDF supaya kedua dokumen
     * selalu menampilkan baris dan urutan yang sama.
     *
     * @return array{rekap: array, minPersen: float, semLabel: string, setting: ?object}
     */
    private function fetchRekapSemesterData(int $tahun, int $semester): array
    {
        $db = \Config\Database::connect();

        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting      = $absenSettingModel->first();
        $minPersen         = (float) ($absenSetting->min_kehadiran_persen ?? 90.0);
        $semLabel          = ($semester === 1) ? 'Ganjil' : 'Genap';

        $rekap = [];
        if ($db->tableExists('absensi_rekap_semester')) {
            $rekap = $db->table('absensi_rekap_semester rs')
                ->select('rs.*, COALESCE(mg.nama_guru, u.username) as nama_guru, mg.nip')
                ->join('users u', 'u.id = rs.user_id', 'left')
                ->join('master_guru mg', 'mg.id_user = rs.user_id', 'left')
                ->where('rs.tahun', $tahun)
                ->where('rs.semester', $semester)
                ->get()->getResult();

            // Urutkan: tidak_layak -> perhatian -> layak, lalu nama guru
            $statusOrder = ['tidak_layak' => 0, 'perhatian' => 1, 'layak' => 2];
            usort($rekap, function ($a, $b) use ($statusOrder) {
                $sa = $statusOrder[$a->status_kelayakan] ?? 2;
                $sb = $statusOrder[$b->status_kelayakan] ?? 2;
                if ($sa !== $sb) return $sa <=> $sb;
                return strcmp((string) ($a->nama_guru ?? ''), (string) ($b->nama_guru ?? ''));
            });
        }

        return [
            'rekap'     => $rekap,
            'minPersen' => $minPersen,
            'semLabel'  => $semLabel,
            'setting'   => $this->dashboardModel->getSetting(),
        ];
    }

    /**
     * Download rekap semester ke PDF (Dompdf A4 landscape, lampiran).
     */
    public function exportRekapSemesterPdf()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $tahun    = (int) ($this->request->getGet('tahun')    ?? date('Y'));
        $semester = (int) ($this->request->getGet('semester') ?? 1);
        $curYear  = (int) date('Y');
        if ($tahun < attendance_tahun_tertua() || $tahun > $curYear + 1) $tahun = $curYear;
        if (!in_array($semester, [1, 2], true)) $semester = 1;

        $export = $this->fetchRekapSemesterData($tahun, $semester);
        $rekap  = $export['rekap'];

        if (empty($rekap)) {
            return redirect()
                ->to(site_url("admin/presensi/rekap-semester?tahun={$tahun}&semester={$semester}"))
                ->with('error', "Belum ada data rekap semester {$export['semLabel']} {$tahun}. Klik Generate Rekap terlebih dahulu.");
        }

        $rata = round(array_sum(array_map(fn($r) => (float) $r->persentase_kehadiran, $rekap)) / count($rekap), 1);

        $html = view('admin/presensi/rekap_semester_pdf', [
            'rekap'     => $rekap,
            'tahun'     => $tahun,
            'semester'  => $semester,
            'semLabel'  => $export['semLabel'],
            'minPersen' => $export['minPersen'],
            'rata'      => $rata,
            'setting'   => $export['setting'] ?: (object) ['nama_sekolah' => 'Sekolah'],
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = "Rekap_Semester_{$export['semLabel']}_{$tahun}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setHeader('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->setBody($dompdf->output());
    }

    // ============================================
    // FACE REGISTRATION APPROVAL
    // ============================================

    public function pendingWajah()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $db = \Config\Database::connect();

        $pending = [];
        $history = [];
        $stats = [
            'pending' => 0,
            'approved_today' => 0,
            'total_guru' => 0,
            'belum_wajah' => 0,
        ];

        // Total guru aktif (untuk statistik)
        $stats['total_guru'] = $db->table('users u')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->countAllResults();

        // Guru yang belum punya data wajah (bertanda)
        if ($db->fieldExists('face_descriptor', 'users')) {
            $stats['belum_wajah'] = $db->table('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where('u.active', 1)
                ->groupStart()
                    ->where('u.face_descriptor', null)
                    ->orWhere('u.face_descriptor', '')
                ->groupEnd()
                ->countAllResults();
        }

        if ($db->tableExists('face_registration_requests')) {
            $pending = $db->table('face_registration_requests f')
                ->select('f.*, u.username, u.first_name, u.last_name, mg.nama_guru, mg.nip, mg.foto')
                ->join('users u', 'u.id = f.user_id', 'left')
                ->join('master_guru mg', 'mg.id_user = f.user_id', 'left')
                ->where('f.status', 'pending')
                ->orderBy('f.created_at', 'DESC')
                ->get()
                ->getResult();

            $stats['pending'] = count($pending);
            $stats['approved_today'] = $db->table('face_registration_requests')
                ->where('status', 'approved')
                ->like('updated_at', date('Y-m-d'), 'after')
                ->countAllResults();

            // Riwayat 15 pengajuan terakhir yang telah diproses (audit)
            $history = $db->table('face_registration_requests f')
                ->select('f.*, u.username, u.first_name, u.last_name, mg.nama_guru, mg.nip,
                          adm.first_name as admin_first, adm.last_name as admin_last')
                ->join('users u', 'u.id = f.user_id', 'left')
                ->join('master_guru mg', 'mg.id_user = f.user_id', 'left')
                ->join('users adm', 'adm.id = f.admin_id', 'left')
                ->where('f.status !=', 'pending')
                ->orderBy('f.updated_at', 'DESC')
                ->limit(15)
                ->get()
                ->getResult();
        }

        return view('admin/presensi/pending_wajah', [
            'title'      => 'Verifikasi Wajah Guru',
            'judul'      => 'Pengajuan Registrasi Ulang Wajah',
            'pending'    => $pending,
            'history'    => $history,
            'stats'      => $stats,
            'setting'    => $this->dashboardModel->getSetting(),
            'tp_active'  => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'user'       => $this->getUser(),
        ]);
    }

    public function approveWajah($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID tidak valid');
        }

        $db = \Config\Database::connect();

        if (!$db->fieldExists('face_descriptor', 'users')) {
            return $this->respond(false, 'Kolom face_descriptor belum tersedia di tabel users', null, null, 500);
        }

        $request = $db->table('face_registration_requests')
            ->where('id', $id)
            ->where('status', 'pending')
            ->get()
            ->getRow();

        if (!$request) {
            return $this->respond(false, 'Pengajuan tidak ditemukan atau sudah diproses');
        }

        // Jangan timpa wajah aktif dengan descriptor kosong (guru kehilangan verifikasi)
        if (trim((string) ($request->face_descriptor ?? '')) === '') {
            return $this->respond(false, 'Data wajah pengajuan kosong — minta guru mengulang registrasi');
        }

        // Pastikan akun user masih ada & aktif
        $targetUser = $db->table('users')->where('id', $request->user_id)->get()->getRow();
        if (!$targetUser) {
            return $this->respond(false, 'Akun guru tidak ditemukan');
        }

        $db->transBegin();

        try {
            $db->table('face_registration_requests')
                ->where('id', $id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'admin_id' => session()->get('user_id'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Proteksi race: hanya 1 admin yang berhasil mengubah pending → approved
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Pengajuan sudah diproses admin lain. Muat ulang halaman.');
            }

            $db->table('users')
                ->where('id', $request->user_id)
                ->update(['face_descriptor' => $request->face_descriptor]);

            $db->transCommit();

            try {
                (new \App\Models\AuditLogModel())->log(
                    'users',
                    (int) $request->user_id,
                    'UPDATE',
                    (int) $request->user_id,
                    $this->getUserId(),
                    ['face_descriptor' => '***'],
                    ['face_descriptor' => '***'],
                    'Wajah disetujui admin (request #' . $id . ')'
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Audit approve wajah gagal: ' . $ex->getMessage());
            }

            return $this->respond(true, 'Wajah berhasil disetujui. Guru dapat langsung memakai wajah baru untuk absensi.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Approve Face Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyetujui pengajuan — periksa log aplikasi.', null, null, 500);
        }
    }

    public function rejectWajah($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID tidak valid');
        }

        $db = \Config\Database::connect();

        $request = $db->table('face_registration_requests')
            ->where('id', $id)
            ->where('status', 'pending')
            ->get()
            ->getRow();

        if (!$request) {
            return $this->respond(false, 'Pengajuan tidak ditemukan atau sudah diproses');
        }

        $keterangan = trim((string) $this->request->getPost('keterangan')) ?: 'Ditolak oleh admin';
        if (mb_strlen($keterangan) > 255) {
            $keterangan = mb_substr($keterangan, 0, 255);
        }

        $db->transBegin();

        try {
            $db->table('face_registration_requests')
                ->where('id', $id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'admin_id' => session()->get('user_id'),
                    'keterangan' => $keterangan,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Pengajuan sudah diproses admin lain. Muat ulang halaman.');
            }

            $db->transCommit();

            try {
                (new \App\Models\AuditLogModel())->log(
                    'face_registration_requests',
                    $id,
                    'UPDATE',
                    (int) $request->user_id,
                    $this->getUserId(),
                    ['status' => 'pending'],
                    ['status' => 'rejected'],
                    'Wajah ditolak admin: ' . mb_substr($keterangan, 0, 200)
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Audit reject wajah gagal: ' . $ex->getMessage());
            }

            return $this->respond(true, 'Pengajuan ditolak. Wajah lama guru tetap berlaku dan tidak diubah.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Reject Face Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menolak pengajuan — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Hapus satu baris riwayat pengajuan wajah yang SUDAH diproses.
     * Pengajuan pending tidak boleh dihapus lewat sini (gunakan Setujui/Tolak).
     */
    public function deleteWajah($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID tidak valid');
        }

        $db = \Config\Database::connect();

        $request = $db->table('face_registration_requests')
            ->where('id', $id)
            ->get()
            ->getRow();

        if (!$request) {
            return $this->respond(false, 'Data riwayat tidak ditemukan');
        }

        if (strtolower($request->status ?? '') === 'pending') {
            return $this->respond(false, 'Pengajuan yang masih menunggu tidak dapat dihapus. Selesaikan dulu via Setujui/Tolak.', null, null, 422);
        }

        try {
            $db->table('face_registration_requests')->where('id', $id)->delete();

            if ($db->affectedRows() < 1) {
                return $this->respond(false, 'Data gagal dihapus');
            }

            try {
                (new \App\Models\AuditLogModel())->log(
                    'face_registration_requests',
                    $id,
                    'DELETE',
                    (int) ($request->user_id ?? 0),
                    $this->getUserId(),
                    ['status' => $request->status ?? ''],
                    null,
                    'Riwayat pengajuan wajah dihapus admin (request #' . $id . ')'
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Audit delete wajah gagal: ' . $ex->getMessage());
            }

            return $this->respond(true, 'Riwayat pengajuan berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', 'Delete Face History Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menghapus riwayat — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Hapus SELURUH riwayat pengajuan wajah yang sudah diproses.
     * Pengajuan pending tidak ikut terhapus.
     */
    public function clearWajahHistory()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $db = \Config\Database::connect();

        try {
            $count = $db->table('face_registration_requests')
                ->where('status !=', 'pending')
                ->countAllResults(false);

            if ($count < 1) {
                return $this->respond(true, 'Tidak ada riwayat yang perlu dihapus.');
            }

            $db->table('face_registration_requests')
                ->where('status !=', 'pending')
                ->delete();

            try {
                (new \App\Models\AuditLogModel())->log(
                    'face_registration_requests',
                    0,
                    'DELETE',
                    0,
                    $this->getUserId(),
                    null,
                    null,
                    'Seluruh riwayat pengajuan wajah dihapus admin (' . $count . ' data)'
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Audit clear wajah gagal: ' . $ex->getMessage());
            }

            return $this->respond(true, $count . ' data riwayat berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', 'Clear Face History Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menghapus riwayat — periksa log aplikasi.', null, null, 500);
        }
    }

    // ── Konfigurasi Channel Notifikasi ─────────────────────────────────

    /**
     * Halaman pengaturan channel notifikasi per jenis.
     * GET /admin/presensi/notif-config
     */
    public function notifConfig()
    {
        if (! $this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $cfgModel = new \App\Models\NotifChannelConfigModel();
        $config   = $cfgModel->getConfig();

        $setting = $this->dashboardModel->getSetting();
        if (! $setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        return view('admin/presensi/notif_config', [
            'title'      => 'Pengaturan Channel Notifikasi',
            'judul'      => 'Pengaturan Channel Notifikasi',
            'subjudul'   => 'Atur channel mana yang aktif untuk setiap jenis notifikasi',
            'setting'    => $setting,
            'tp_active'  => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'user'       => $this->getUser(),
            'config'     => $config,
            'jenisList'  => \App\Models\NotifChannelConfigModel::JENIS_LIST,
            'channels'   => \App\Models\NotifChannelConfigModel::CHANNELS,
            'chLabels'   => \App\Models\NotifChannelConfigModel::CHANNEL_LABELS,
        ]);
    }

    /**
     * Simpan konfigurasi channel notifikasi.
     * POST /admin/presensi/notif-config/save
     */
    public function saveNotifConfig()
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)
                ->setJSON(['status' => false, 'message' => 'Method tidak diizinkan']);
        }

        $raw = $this->request->getPost('notif') ?? [];
        if (! is_array($raw)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak valid']);
        }

        // Sanitasi: pastikan hanya jenis dan channel yang dikenal yang diproses
        $allowed = array_keys(\App\Models\NotifChannelConfigModel::JENIS_LIST);
        $allowedCh = \App\Models\NotifChannelConfigModel::CHANNELS;
        $clean = [];
        foreach ($raw as $jenis => $channels) {
            if (! in_array($jenis, $allowed, true)) {
                continue;
            }
            if (! is_array($channels)) {
                continue;
            }
            $clean[$jenis] = [];
            foreach ($allowedCh as $ch) {
                // Checkbox yang tidak dicentang tidak dikirim POST sama
                // sekali. Yang menentukan nyala/mati adalah NILAINYA,
                // bukan keberadaan key: isset() selalu true untuk key yang
                // ada, jadi memakai isset() di sini membuat semua channel
                // nyala dan admin tidak pernah bisa mematikan satu pun.
                $clean[$jenis][$ch] = (int) ($channels[$ch] ?? 0) === 1 ? 1 : 0;
            }
        }

        // Jenis yang tidak ada di POST (mungkin semua checkbox-nya tidak
        // dicentang) tetap perlu di-update agar semua channel = 0
        foreach ($allowed as $jenis) {
            if (! isset($clean[$jenis])) {
                $clean[$jenis] = array_fill_keys($allowedCh, 0);
            }
        }

        $cfgModel = new \App\Models\NotifChannelConfigModel();
        $ok = $cfgModel->saveConfig($clean);

        return $this->response->setJSON([
            'status'  => $ok,
            'message' => $ok ? 'Konfigurasi notifikasi berhasil disimpan.' : 'Gagal menyimpan sebagian konfigurasi.',
        ]);
    }

    // ── Surat panggilan orang tua/wali siswa ─────────────────────────

    /**
     * Daftar surat panggilan siswa, plus form input manual.
     *
     * GET /admin/presensi/surat-panggilan?bulan=MM&tahun=YYYY
     */
    public function suratPanggilan()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $bulan = (int) ($this->request->getGet('bulan') ?: date('n'));
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));

        // Normalisasi supaya parameter ngawur tidak sampai ke query.
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = ($tahun >= 2000 && $tahun <= 2100) ? $tahun : (int) date('Y');

        $setting = $this->dashboardModel->getSetting();
        if (!$setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        $db = \Config\Database::connect();

        $letters   = [];
        $kandidat  = [];
        $rekap     = [];
        $teguranSiswa = [];
        $adaTabel  = $db->tableExists(\App\Services\SuratPanggilanService::TABEL);

        if ($adaTabel) {
            // Hanya surat milik siswa AKTIF (buku_induk.status = '1').
            // Surat siswa LULUS/PINDAH/KELUAR tidak ditampilkan di daftar
            // (arsip fisiknya tetap tersimpan di tabel).
            $lq = $db->table(\App\Services\SuratPanggilanService::TABEL . ' p')
                ->select('p.*, s.nama, s.nis, s.nisn, s.kelas_awal')
                ->join('master_siswa s', 's.id_siswa = p.id_siswa')
                ->where('p.bulan', $bulan)
                ->where('p.tahun', $tahun);
            try {
                if ($db->tableExists('buku_induk')) {
                    $lq->join('buku_induk bi', 'bi.id_siswa = p.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e2) {
            }
            $letters = $lq->orderBy('p.nomor_urut', 'DESC')
                ->get()->getResult();

            // Deteksi siswa yang wali kelasnya sudah punya surat teguran aktif
            // pada bulan ini, untuk ditampilkan badge alpha di bulan berjalan.
            // Rantai: siswa → kelas_siswa → master_kelas.guru_id →
            // absensi_surat_teguran.id_guru (aktif, bulan ini).
            $teguranSiswa = [];
            if ($db->tableExists('absensi_surat_teguran')
                && $db->tableExists('master_kelas')
                && $db->tableExists('kelas_siswa')) {
                try {
                    $teguran = $db->table('absensi_surat_teguran')
                        ->select('id_guru')
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->where('status', 'aktif')
                        ->get()->getResult();
                    $idGuruTeguran = [];
                    foreach ($teguran as $t) {
                        $idGuruTeguran[] = (int) ($t->id_guru ?? 0);
                    }
                    if (!empty($idGuruTeguran)) {
                        $idKelasTeguran = $db->table('master_kelas')
                            ->select('id_kelas')
                            ->whereIn('guru_id', $idGuruTeguran)
                            ->get()->getResult();
                        $idKelasList = array_values(
                            array_unique(array_map(fn($r) => (int) ($r->id_kelas ?? 0), $idKelasTeguran))
                        );
                        if (!empty($idKelasList)) {
                            $siswaTeguran = $db->table('kelas_siswa')
                                ->select('id_siswa')
                                ->whereIn('id_kelas', $idKelasList)
                                ->get()->getResult();
                            foreach ($siswaTeguran as $s) {
                                $teguranSiswa[(int) ($s->id_siswa ?? 0)] = true;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', '[suratPanggilan] gagal deteksi surat teguran: ' . $e->getMessage());
                }
            }
        }

        // Kandidat untuk input manual: siswa yang alpha-nya sudah melewati
        // ambang tapi belum punya surat di periode ini.
        if ($adaTabel && $db->tableExists('presensi_siswa')) {
            try {
                $absenSetting = (new \App\Models\AttendanceSettingModel())->first();
                $ctx = \App\Services\SuratPanggilanService::buildContext($bulan, $tahun);
                $rekap = \App\Services\SuratPanggilanService::rekapAlphaBulan($ctx);

                $sudahPunya = [];
                foreach ($letters as $l) {
                    // Surat yang pernah dibatalkan bukan alasan untuk
                    // menyembunyikan kandidatnya: alpha hari ini tetap bicara.
                    if (($l->status ?? 'aktif') !== 'aktif') {
                        continue;
                    }
                    $sudahPunya[(int) $l->id_siswa] = true;
                }

                foreach ($rekap as $idSiswa => $r) {
                    if (isset($sudahPunya[$idSiswa])) {
                        continue;
                    }
                    if ((int) $r['alpha'] < \App\Services\SuratPanggilanService::AMBANG[0]) {
                        continue;
                    }
                    $siswa = $db->table('master_siswa')
                        ->select('id_siswa, nama, nis')
                        ->where('id_siswa', $idSiswa)
                        ->get()->getRow();
                    if (!$siswa) {
                        continue;
                    }
                    $kandidat[] = [
                        'id_siswa'   => $idSiswa,
                        'nama'       => $siswa->nama,
                        'nis'        => $siswa->nis,
                        'alpha'      => (int) $r['alpha'],
                        'terlambat'  => (int) $r['terlambat'],
                        'level'      => \App\Services\SuratPanggilanService::levelTercapai((int) $r['alpha']),
                    ];
                }

                usort($kandidat, static fn($a, $b) => $b['alpha'] <=> $a['alpha']);
            } catch (\Throwable $e) {
                log_message('error', '[suratPanggilan] gagal hitung kandidat: ' . $e->getMessage());
                $kandidat = [];
            }
        }

        return view('admin/presensi/surat_panggilan', [
            'title'      => 'Surat Panggilan Orang Tua',
            'judul'      => 'Surat Panggilan Orang Tua',
            'setting'    => $setting,
            'tp_active'  => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'user'       => $this->getUser(),
            'letters'    => $letters,
            'kandidat'   => $kandidat,
            // Alpha live per siswa (dari data absen real saat ini) untuk
            // penanda drift: snapshot surat yang beda dari angka ini
            // berarti perlu Hitung Ulang.
            'alpha_live' => array_map(
                static fn($r) => ['alpha' => (int) ($r['alpha'] ?? 0), 'terlambat' => (int) ($r['terlambat'] ?? 0)],
                $rekap
            ),
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'adaTabel'   => $adaTabel,
            'teguranSiswa' => $teguranSiswa,
        ]);
    }

    /**
     * Kirim notifikasi WA/Telegram ke wali kelas untuk surat panggilan
     * yang sudah terbit pada bulan/tahun tertentu (manual trigger dari admin).
     *
     * POST /admin/presensi/surat-panggilan/notifikasi
     * Body: bulan, tahun
     */
    public function kirimNotifikasiPanggilan()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $bulan = (int) ($this->request->getPost('bulan') ?: date('n'));
        $tahun = (int) ($this->request->getPost('tahun') ?: date('Y'));

        if ($bulan < 1 || $bulan > 12) {
            return $this->respond(false, 'Bulan tidak valid.', null, null, 422);
        }
        if ($tahun < 2000 || $tahun > 2100) {
            return $this->respond(false, 'Tahun tidak valid.', null, null, 422);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists(\App\Services\SuratPanggilanService::TABEL)) {
            return $this->respond(false, 'Tabel surat panggilan belum dibuat.', null, null, 422);
        }

        try {
            $ctx = \App\Services\SuratPanggilanService::buildContext($bulan, $tahun);
            $hasil = \App\Services\SuratPanggilanService::generate($bulan, $tahun, $ctx);

            if (empty($hasil['detail'])) {
                return $this->respond(
                    true,
                    'Tidak ada surat panggilan baru untuk periode ini.',
                    ['diterbitkan' => 0, 'terkirim' => 0]
                );
            }

            $daftar = \App\Services\SuratPanggilanService::buildDaftarPanggilanDenganWaliKelas(
                $db,
                $bulan,
                $tahun,
                $hasil['detail']
            );

            $notification = new \App\Services\NotificationService();
            $terkirim = $notification->notifySuratPanggilan($daftar, (string) $bulan, (string) $tahun);

            return $this->respond(
                true,
                'Notifikasi terkirim ke ' . $terkirim . ' wali kelas.',
                [
                    'diterbitkan' => (int) $hasil['diterbitkan'],
                    'terkirim'    => (int) $terkirim,
                    'gagal'       => (int) $hasil['gagal'],
                ]
            );
        } catch (\Throwable $e) {
            log_message('error', '[kirimNotifikasiPanggilan] ' . $e->getMessage());

            return $this->respond(false, 'Gagal mengirim notifikasi — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Buat surat panggilan manual.
     *
     * POST /admin/presensi/surat-panggilan/buat
     * Body: id_siswa, bulan, tahun, level, keterangan
     */
    public function buatSuratPanggilan()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $idSiswa = (int) ($this->request->getPost('id_siswa') ?? 0);
        $bulan   = (int) ($this->request->getPost('bulan') ?: date('n'));
        $tahun   = (int) ($this->request->getPost('tahun') ?: date('Y'));
        $level   = (int) ($this->request->getPost('level') ?? 0);
        $ket     = trim((string) ($this->request->getPost('keterangan') ?? ''));

        if ($idSiswa <= 0) {
            return $this->respond(false, 'Pilih siswa terlebih dahulu.');
        }

        $hasil = \App\Services\SuratPanggilanService::buatManual($idSiswa, $bulan, $tahun, $level, $ket);

        if (!$hasil['ok']) {
            return $this->respond(false, $hasil['message'], null, null, 422);
        }

        $db = \Config\Database::connect();
        $nama = $db->table('master_siswa')->select('nama')->where('id_siswa', $idSiswa)->get()->getRow();
        $namaSiswa = $nama->nama ?? ('siswa #' . $idSiswa);

        return $this->respond(
            true,
            'Surat panggilan untuk ' . $namaSiswa . ' berhasil dibuat.',
            ['id' => $hasil['id'] ?? null]
        );
    }

    /**
     * Terbitkan surat teguran guru secara manual.
     *
     * POST /admin/presensi/surat-teguran/buat
     * Body: id_guru, bulan, tahun, keterangan (opsional)
     *
     * Level TIDAK diterima dari request. Alfa guru dihitung ulang di sini dan
     * tingkat yang terbit ditentukan SuratTeguranService — level terberat yang
     * sudah dilampaui. Menerima level dari klien berarti operator bisa membuat
     * surat tingkat I untuk guru yang sudah punya tingkat III.
     *
     * Alpha juga dicek ulang di sini. Tombol di halaman sudah menampilkan
     * kandidat, tapi angka itu bisa basi beberapa detik setelahnya; lebih baik
     * menolak dengan jelas daripada menerbitkan surat ke orang yang Sending
     * sudah kembali hadir.
     *
     * Catatan: keputusan untuk mengabaikan angka alpha dan menerbitkan
     * surat paksa tetap perlu dibiarkan ada di sistem, tapi harus lewat
     * keputusan sadar — bukan tombol yang diam-diam mengabaikan perhitungan.
     */
    public function buatSuratTeguran()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $idGuru = (int) ($this->request->getPost('id_guru') ?? 0);
        $bulan  = (int) ($this->request->getPost('bulan') ?: date('n'));
        $tahun  = (int) ($this->request->getPost('tahun') ?: date('Y'));

        if ($idGuru <= 0) {
            return $this->respond(false, 'Pilih guru terlebih dahulu.');
        }

        $db          = \Config\Database::connect();
        $absenSetting = (new \App\Models\AttendanceSettingModel())->first();

        // Alpha dihitung dari user, bukan dari id_guru: yang punya catatan
        // absensi adalah akun guru. id_guru → id_user selalu dari master_guru,
        // dan kalau kolomnya kosong alpha tidak bisa dihitung sama sekali —
        // surat yang terbit dengan angka tebakan akan masuk arsip sekolah.
        $guru = $db->table('master_guru')
            ->select('id_user, nama_guru')
            ->where('id_guru', $idGuru)
            ->get()
            ->getRow();

        if (!$guru) {
            return $this->respond(false, 'Data guru tidak ditemukan.', null, null, 404);
        }

        $userId = (int) ($guru->id_user ?? 0);
        if ($userId <= 0) {
            return $this->respond(false, 'Guru ini belum terhubung ke akun presensi, alpha tidak bisa dihitung.');
        }

        $alpha = (new \App\Services\AttendancePenaltyService())
            ->calculateTeacherMonthlyAlpha($userId, $bulan, $tahun, $absenSetting);

        $svc = new \App\Services\SuratTeguranService($db);
        $svc->sinkronkan($idGuru, $alpha, (string) $bulan, (string) $tahun);

        $terbit = $svc->terbitkan($idGuru, $alpha, (string) $bulan, (string) $tahun);

        if ($terbit === null) {
            return $this->respond(
                false,
                'Surat tidak diterbitkan. Kemungkinan: guru sudah punya surat di periode ini, '
                . 'atau alpha-nya belum melewati ambang.',
                null,
                null,
                422
            );
        }

        // Notifikasi best-effort: jalur cron mengirim via notifyWarningLetter,
        // jalur manual selama ini diam sehingga surat terbit tanpa kabar ke
        // guru. Kegagalan kirim tak membatalkan surat yang sudah terbit.
        $hasil = $this->kirimNotifikasiTeguran(
            (string) ($guru->nama_guru ?? ''),
            (int) $terbit['level'],
            $bulan,
            $tahun,
            $userId
        );

        return $this->respond(
            true,
            'Surat teguran berhasil dibuat. ' . $hasil['pesan'],
            ['id' => $terbit['id'], 'level' => $terbit['level']]
        );
    }

    /**
     * Batalkan surat teguran.
     *
     * POST /admin/presensi/surat-teguran/batal/{id}
     *
     * Dibatalkan, bukan dihapus — surat yang sudah keluar dari sekolah tetap
     * harus bisa dibuktikan keberadaannya. Ditandai pembatalan MANUAL supaya
     * sinkronisasi otomatis tidak menghidupkan kembalinya: keputusan operator
     * tidak ditimpa perhitungan alpha.
     */
    public function batalkanSuratTeguran($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID surat tidak valid.');
        }

        $db     = \Config\Database::connect();
        $alasan = trim((string) ($this->request->getPost('alasan') ?? ''));
        $letter = $db->table('absensi_surat_teguran')->where('id', $id)->get()->getRow();

        if (!$letter) {
            return $this->respond(false, 'Surat tidak ditemukan.', null, null, 404);
        }

        $update = [
            'status'           => 'dibatalkan',
            'keterangan_batal' => $alasan !== ''
                ? $alasan
                : 'Dibatalkan manual oleh operator',
        ];

        // Tanpa penanda ini, sinkronkan() akan menghidupkan kembali surat ini
        // pada evaluation berikutnya begitu alpha-nya kembali di atas ambang.
        if ($db->fieldExists('dibatalkan_otomatis', 'absensi_surat_teguran')) {
            $update['dibatalkan_otomatis'] = 0;
        }

        $db->table('absensi_surat_teguran')->where('id', $id)->update($update);

        return $this->respond(true, 'Surat teguran dibatalkan.');
    }

    /**
     * Kirim FILE PDF surat teguran ke WA/Telegram PERSONAL guru.
     *
     * POST /admin/presensi/surat-teguran/kirim/{id}
     *
     * Untuk surat yang terbit sebelum notifikasi manual ada, atau yang
     * pengirimannya gagal — tanpa menerbitkan surat baru. Isi PDF identik
     * dengan unduhan (satu builder data). Sengaja HANYA ke personal, tanpa
     * fallback/pengumuman grup: dokumen pribadi tidak boleh terekspos.
     */
    public function kirimSuratTeguran($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID surat tidak valid.');
        }

        $db = \Config\Database::connect();
        $letter = $db->table('absensi_surat_teguran a')
            ->select('a.*, g.nama_guru, g.id_user')
            ->join('master_guru g', 'g.id_guru = a.id_guru')
            ->where('a.id', $id)
            ->get()->getRow();

        if (!$letter) {
            return $this->respond(false, 'Surat tidak ditemukan.', null, null, 404);
        }

        if (($letter->status ?? 'aktif') === 'dibatalkan') {
            return $this->respond(false, 'Surat sudah dibatalkan, PDF tidak dikirim.', null, null, 422);
        }

        $userId = (int) ($letter->id_user ?? 0);
        if ($userId <= 0) {
            return $this->respond(false, 'Guru belum terhubung ke akun presensi, tujuan personal tidak diketahui.', null, null, 422);
        }

        // Render PDF arsip ke berkas sementara (isi identik dengan unduhan).
        $tmpFile = '';
        try {
            $viewData = $this->dataPdfSuratTeguran($letter);
            $html = view('members/guru/surat_teguran_pdf', $viewData);

            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->set_option('isRemoteEnabled', true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $tmpDir = WRITEPATH . 'temp' . DIRECTORY_SEPARATOR;
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }
            $namaFile = 'Surat_Teguran_' . (int) ($letter->level ?? 0) . '_Hari_'
                . preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($letter->nama_guru ?? 'Guru')) . '.pdf';
            $tmpFile = $tmpDir . 'teguran_' . $id . '_' . date('YmdHis') . '.pdf';
            file_put_contents($tmpFile, $dompdf->output());
        } catch (\Throwable $e) {
            log_message('error', '[SuratTeguran] render PDF kirim gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal membuat berkas PDF — periksa log aplikasi.');
        }

        try {
            $caption = 'Surat Teguran ' . \App\Libraries\SuratTeguranLevel::label((int) ($letter->level ?? 0))
                . ' — ' . ($letter->nama_guru ?? '');
            $notif = new \App\Services\NotificationService();
            $hasil = $notif->kirimDokumenPersonal(
                'teguran',
                $userId,
                $tmpFile,
                $namaFile,
                $caption,
                'teguran'
            );

            if ($hasil['ok']) {
                return $this->respond(
                    true,
                    'File PDF terkirim ke ' . ($hasil['channel'] === 'whatsapp' ? 'WhatsApp' : 'Telegram') . ' personal guru.',
                    ['channel' => $hasil['channel']]
                );
            }

            return $this->respond(false, 'PDF gagal dikirim ke personal: ' . $hasil['error']);
        } catch (\Throwable $e) {
            log_message('error', '[SuratTeguran] kirim PDF gagal: ' . $e->getMessage());
            return $this->respond(false, 'PDF gagal dikirim ke personal — periksa log aplikasi.');
        } finally {
            if ($tmpFile !== '' && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Kirim notifikasi surat teguran (grup + personal) secara best-effort.
     *
     * Dipakai jalur buat-manual dan kirim-ulang. Kegagalan kirim tak
     * membatalkan surat yang sudah terbit.
     *
     * @return array{ok:bool, pesan:string, channel:string}
     */
    private function kirimNotifikasiTeguran(string $namaGuru, int $level, $bulan, $tahun, int $userId): array
    {
        try {
            $notif = new \App\Services\NotificationService();
            $notif->notifyWarningLetter($namaGuru, $level, (string) $bulan, (string) $tahun, $userId);
            // lastChannel = hasil kaki personal: whatsapp/telegram (DM sampai),
            // grup (DM gagal, sudah diumumkan di grup), preferensi-mati,
            // tidak-terkirim, '' (kanal personal mati — hanya kaki grup jalan).
            $via = (string) ($notif->lastChannel ?? '');
            $err = trim((string) ($notif->lastError ?? ''));
            if (in_array($via, ['whatsapp', 'telegram'], true)) {
                return [
                    'ok' => true,
                    'pesan' => 'Notifikasi terkirim ke guru via '
                        . ($via === 'whatsapp' ? 'WhatsApp.' : 'Telegram.'),
                    'channel' => $via,
                ];
            }
            if ($via === 'grup') {
                return [
                    'ok' => true,
                    'pesan' => 'Pesan pribadi gagal terkirim, tetapi sudah diumumkan di grup.',
                    'channel' => $via,
                ];
            }
            if ($via === 'preferensi-mati') {
                return [
                    'ok' => true,
                    'pesan' => 'Guru menonaktifkan notifikasi teguran; pengumuman grup tetap dikirim bila kanal grup aktif.'
                        . ($err !== '' ? ' (' . $err . ')' : ''),
                    'channel' => $via,
                ];
            }
            if ($via === '' && $err === '') {
                return ['ok' => true, 'pesan' => 'Kanal personal mati; pengumuman grup dikirim.', 'channel' => 'grup'];
            }
            $pesan = 'Notifikasi gagal dikirim' . ($err !== '' ? ': ' . $err : '.');
            log_message('error', '[SuratTeguran] notifikasi gagal untuk ' . $namaGuru . ': ' . $pesan);
            return ['ok' => false, 'pesan' => $pesan, 'channel' => $via !== '' ? $via : 'tidak-terkirim'];
        } catch (\Throwable $e) {
            log_message('error', '[SuratTeguran] notifikasi gagal untuk ' . $namaGuru . ': ' . $e->getMessage());
            return ['ok' => false, 'pesan' => 'Notifikasi gagal dikirim.', 'channel' => 'tidak-terkirim'];
        }
    }

    /**
     * Batalkan surat panggilan.
     *
     * POST /admin/presensi/surat-panggilan/batal/{id}
     *
     * Dibatalkan, bukan dihapus — surat yang sudah keluar dari sekolah tetap
     * harus bisa dibuktikan keberadaannya.
     */
    public function batalkanSuratPanggilan($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID surat tidak valid.');
        }

        $alasan = trim((string) ($this->request->getPost('alasan') ?? ''));
        $ok = \App\Services\SuratPanggilanService::batalkan($id, $alasan);

        if (!$ok) {
            return $this->respond(false, 'Surat tidak ditemukan atau gagal dibatalkan.', null, null, 404);
        }

        return $this->respond(true, 'Surat panggilan dibatalkan.');
    }

    /**
     * Hitung ulang surat panggilan satu periode agar sesuai data absen real.
     *
     * POST /admin/presensi/surat-panggilan/hitung-ulang
     * Body: bulan, tahun
     *
     * Snapshot jumlah_alpha/jumlah_terlambat surat aktif dihitung ulang
     * dari tabel presensi siswa (aturan hari efektif yang sama dengan
     * cron & rekap). Surat yang syarat alphanya sudah gugur dibatalkan
     * otomatis. Level dan penerima tidak diubah.
     */
    public function hitungUlangSuratPanggilan()
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $bulan = (int) ($this->request->getPost('bulan') ?: date('n'));
        $tahun = (int) ($this->request->getPost('tahun') ?: date('Y'));
        if ($bulan < 1 || $bulan > 12) {
            return $this->respond(false, 'Bulan tidak valid.');
        }
        if ($tahun < 2000 || $tahun > 2100) {
            return $this->respond(false, 'Tahun tidak valid.');
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists(\App\Services\SuratPanggilanService::TABEL)) {
            return $this->respond(false, 'Tabel surat panggilan belum dibuat. Jalankan php spark migrate.');
        }

        try {
            $absenSetting = (new \App\Models\AttendanceSettingModel())->first();
            $setting = $this->dashboardModel->getSetting();
            $ctx = \App\Services\SuratPanggilanService::buildContext($bulan, $tahun);
            $hasil = \App\Services\SuratPanggilanService::hitungUlang($bulan, $tahun, $ctx);
        } catch (\Throwable $e) {
            log_message('error', '[hitungUlangSuratPanggilan] ' . $e->getMessage());

            return $this->respond(false, 'Gagal menghitung ulang — periksa log aplikasi.');
        }

        return $this->respond(
            true,
            'Hitung ulang selesai: ' . (int) $hasil['dicek'] . ' surat dicek, '
                . (int) $hasil['diperbarui'] . ' diperbarui, '
                . (int) $hasil['dibatalkan'] . ' dibatalkan otomatis.',
            $hasil
        );
    }

    /**
     * Cetak PDF satu surat panggilan.
     *
     * GET /admin/presensi/surat-panggilan/{id}/pdf
     *
     * Angka alpha & keterlambatan memakai SNAPSHOT yang tersimpan saat surat
     * terbit, bukan dihitung ulang. Kalau dihitung ulang, surat yang dicetak
     * ulang tahun depan akan menampilkan angka berbeda dari yang pernah
     * dibaca orang tua — dan arsip jadi tidak bisa dipercaya.
     */
    public function downloadSuratPanggilan($id)
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $id = (int) $id;
        $db = \Config\Database::connect();

        $letter = $db->table(\App\Services\SuratPanggilanService::TABEL . ' p')
            ->select('p.*, s.nama, s.nis, s.nisn, s.kelas_awal, s.nama_ayah, s.nohp_ayah, s.nama_ibu, s.nohp_ibu, s.nama_wali, s.nohp_wali')
            ->join('master_siswa s', 's.id_siswa = p.id_siswa')
            ->where('p.id', $id)
            ->get()->getRow();

        if (!$letter) {
            return redirect()->back()->with('error', 'Data surat panggilan tidak ditemukan.');
        }

        if (($letter->status ?? 'aktif') === 'dibatalkan') {
            return redirect()->back()->with('error', 'Surat panggilan ini telah dibatalkan.');
        }

        $setting = $this->mergeSettingUntukSurat();

        $nomorSurat = \App\Libraries\SuratNomor::build(
            (int) ($letter->nomor_urut ?? 0),
            (int) ($letter->bulan ?? date('n')),
            (int) ($letter->tahun ?? date('Y')),
            $setting,
            (int) ($letter->level ?? 0),
            $letter->created_at ? date('d-m-Y', strtotime($letter->created_at)) : null
        );

        $level = (int) ($letter->level ?? 3);
        $labelTingkat = \App\Services\SuratPanggilanService::LABEL_LEVEL[$level] ?? ('Tingkat (' . $level . ')');

        // Penerima: pakai snapshot saat surat terbit; fallback ke data
        // sekarang hanya bila surat dibuat manual tanpa snapshot.
        $penerima = trim((string) ($letter->penerima ?? ''));
        if ($penerima === '') {
            $snap = \App\Services\SuratPanggilanService::snapshotPenerima((int) $letter->id_siswa);
            $penerima = (string) ($snap['penerima'] ?? '');
        }
        $nohp = trim((string) ($letter->nohp_penerima ?? ''));
        if ($nohp === '') {
            $snap = \App\Services\SuratPanggilanService::snapshotPenerima((int) $letter->id_siswa);
            $nohp = (string) ($snap['nohp_penerima'] ?? '');
        }

        $viewData = [
            'setting'         => $setting,
            'letter'          => $letter,
            'siswa'           => $letter,
            'jumlah_alpha'    => (int) ($letter->jumlah_alpha ?? 0),
            'jumlah_terlambat'=> (int) ($letter->jumlah_terlambat ?? 0),
            'label_tingkat'   => $labelTingkat,
            'penerima'        => $penerima,
            'nohp_penerima'   => $nohp,
            'nomor_surat'     => $nomorSurat,
            'periode_bulan'   => (int) ($letter->bulan ?? date('n')),
            'periode_tahun'   => (int) ($letter->tahun ?? date('Y')),
            'logo_base64'     => $this->gambarBase64($setting->logo_kiri ?? null),
            'kop_base64'      => $this->gambarBase64($setting->kopsekolah ?? null),
        ];

        // QR TTE, sama seperti surat teguran — supaya surat panggilan bisa
        // diverifikasi tanpa harus menghubungi sekolah.
        $viewData['tte_kode'] = \App\Libraries\TteQr::verificationCode(
            $nomorSurat,
            (int) $letter->id_siswa,
            $level,
            (int) $letter->bulan,
            (int) $letter->tahun
        );
        $viewData['tte_qr'] = \App\Libraries\TteQr::dataUri(
            \App\Libraries\TteQr::payload(
                $nomorSurat,
                (string) ($letter->nama ?? '-'),
                (string) ($letter->nis ?? '-'),
                $labelTingkat,
                (int) ($letter->jumlah_alpha ?? 0),
                $viewData['tte_kode'],
                (string) ($setting->kepsek ?? ''),
                (string) ($setting->nip ?? ''),
                (string) ($setting->sekolah ?? '')
            )
        );

        $html = view('admin/presensi/surat_panggilan_pdf', $viewData);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $namaFile = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($letter->nama ?? 'Siswa'));

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="Surat_Panggilan_' . $namaFile . '.pdf"')
            ->setBody($dompdf->output());
    }

    /**
     * Gabungkan absensi_setting + setting global untuk kop surat.
     *
     * Dipisah supaya dipakai ulang oleh downloadSuratPanggilan() tanpa
     * menyalin 20 baris merge lagi.
     */
    private function mergeSettingUntukSurat(): object
    {
        $attSetting = (new \App\Models\AttendanceSettingModel())->first();
        if (!$attSetting) {
            $attSetting = (object) [];
        }

        $globalSetting = $this->dashboardModel->getSetting();

        $setting = clone $attSetting;
        if ($globalSetting) {
            foreach (get_object_vars($globalSetting) as $key => $val) {
                if (!isset($setting->$key)) {
                    $setting->$key = $val;
                }
            }
        }

        foreach (['logo_kiri' => null, 'nama_sekolah' => 'Sekolah', 'kota' => 'Kota', 'kepsek' => '..........................', 'nip' => '..........................'] as $k => $v) {
            if (!isset($setting->$k)) {
                $setting->$k = $v;
            }
        }

        return $setting;
    }

    /**
     * Baca gambar profil settings sebagai data URI untuk Dompdf.
     *
     * Dompdf tidak bisa ambil gambar dari URL (butuh akses jaringan),
     * jadi gambar harus di-inline sebagai base64.
     */
    private function gambarBase64(?string $path): string
    {
        return (new \App\Services\RekapPdfService())->gambarBase64($path);
    }

    // ─────────────────────────────────────────────────────────────────
    // KIRIM REKAP BULANAN PDF KE GRUP WA & TELEGRAM
    // ─────────────────────────────────────────────────────────────────

    /**
     * Helper: render rekap_pdf.php menjadi string PDF (via Dompdf).
     *
     * Pola sama dengan buildDendaPdf() — gambar logo/kop di-inline
     * sebagai base64 supaya Dompdf tidak perlu akses jaringan.
     *
     * @return array{pdf:string, filename:string, bulan:string, tahun:string, jumlah_guru:int}
     * @throws \RuntimeException bila Dompdf tidak menghasilkan output
     */
    private function buildRekapBulananPdf(string $bulan, string $tahun): array
    {
        return (new \App\Services\RekapPdfService())->buildRekapBulananPdf($bulan, $tahun);
    }

    /**
     * Kirim PDF rekap bulanan absensi guru ke grup WhatsApp dan Telegram.
     *
     * POST /admin/presensi/rekap/kirim
     * Body: bulan (1–12), tahun (YYYY)
     */
    public function kirimRekapBulanan(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isAdmin() && !$this->isPanitia()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->respond(false, 'Method tidak diizinkan', null, null, 405);
        }

        $bulan = $this->normalizeBulan((string) ($this->request->getPost('bulan') ?: date('m')));
        $tahun = $this->normalizeTahun((string) ($this->request->getPost('tahun') ?: date('Y')));

        if ((int) $bulan < 1 || (int) $bulan > 12
            || (int) $tahun < 2000 || (int) $tahun > 2100) {
            return $this->respond(false, 'Periode tidak valid. Gunakan bulan 1–12 dan tahun 2000–2100.');
        }

        try {
            $hasil = $this->buildRekapBulananPdf($bulan, $tahun);
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::kirimRekapBulanan] gagal membuat PDF: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyusun PDF rekap bulanan — periksa log aplikasi.', null, null, 500);
        }

        if ($hasil['jumlah_guru'] === 0) {
            return $this->respond(
                false,
                'Tidak ada guru aktif untuk periode ' . $bulan . '/' . $tahun . ' sehingga PDF kosong.'
            );
        }

        // Tulis ke file sementara — Telegram (multipart) dan OpenWA (base64)
        // keduanya membaca dari path, bukan dari string.
        $dir = WRITEPATH . 'uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // Prefix acak: tidak bisa ditebak dari luar dan tidak bentrok
        // dengan request yang berjalan bersamaan.
        $path = $dir . '/rekap_bulanan_' . bin2hex(random_bytes(8)) . '.pdf';

        if (@file_put_contents($path, $hasil['pdf']) === false) {
            log_message('error', '[PresensiAdmin::kirimRekapBulanan] gagal menulis ' . $path);
            return $this->respond(false, 'Gagal menyimpan PDF sementara untuk dikirim.', null, null, 500);
        }

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $namaGrup = ($namaBulan[(int) $bulan] ?? $bulan) . ' ' . $tahun;

        $caption = "<b>📋 REKAP PRESENSI GURU</b>\n"
            . "Periode: {$namaGrup}\n"
            . 'Total guru: ' . $hasil['jumlah_guru'] . ' orang';

        try {
            $svc       = new \App\Services\NotificationService();
            $hasilKirim = $svc->sendDokumenToGrup(
                'rekap_bulanan',
                $path,
                $hasil['filename'],
                $caption
            );
        } catch (\Throwable $e) {
            log_message('error', '[PresensiAdmin::kirimRekapBulanan] error saat kirim: ' . $e->getMessage());
            return $this->respond(false, 'Gagal mengirim rekap — periksa log aplikasi.', null, null, 500);
        } finally {
            // PDF memuat data kehadiran tiap guru — hapus segera setelah
            // terkirim (atau gagal), tidak boleh tertinggal di disk.
            $this->hapusPdfSementara($path);
        }

        $berhasil = $hasilKirim['telegram'] || $hasilKirim['whatsapp'];

        $rincian   = [];
        $rincian[] = 'Telegram: ' . ($hasilKirim['telegram']
            ? 'terkirim'
            : 'GAGAL — ' . ($hasilKirim['telegram_error'] ?? 'tidak diketahui'));
        $rincian[] = 'WhatsApp: ' . ($hasilKirim['whatsapp']
            ? 'terkirim'
            : 'GAGAL — ' . ($hasilKirim['whatsapp_error'] ?? 'tidak diketahui'));

        return $this->respond(
            $berhasil,
            $berhasil
                ? 'Rekap ' . $namaGrup . ' berhasil dikirim. ' . implode(' | ', $rincian)
                : 'Rekap tidak terkirim ke kanal mana pun. '  . implode(' | ', $rincian),
            [
                'bulan'       => $bulan,
                'tahun'       => $tahun,
                'jumlah_guru' => $hasil['jumlah_guru'],
                'filename'    => $hasil['filename'],
                'telegram'    => $hasilKirim['telegram'],
                'whatsapp'    => $hasilKirim['whatsapp'],
            ],
            null,
            $berhasil ? 200 : 500
        );
    }
}

