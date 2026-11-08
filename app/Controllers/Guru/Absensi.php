<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\AttendanceModel;
use App\Models\LeaveModel;
use App\Models\DashboardModel;
use App\Models\AttendanceSettingModel;

/**
 * Guru Attendance Controller
 * Allows teachers to view their own attendance records
 */
class Absensi extends BaseController
{
    protected AttendanceModel $attendanceModel;
    protected LeaveModel $leaveModel;
    protected DashboardModel $dashboardModel;
    protected AttendanceSettingModel $attendanceSettingModel;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->leaveModel = new LeaveModel();
        $this->dashboardModel = new DashboardModel();
        $this->attendanceSettingModel = new AttendanceSettingModel();
    }

    /**
     * Main attendance page - shows teacher's own attendance records
     */
    public function index()
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');

        $user = $this->getUser();
        if (!$user)
            return redirect()->to('auth');

        // Get guru data
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);

        if (!$guru) {
            return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
        }

        // Get current month and year
        $bulan = $this->request->getGet('bulan') ?: date('m');
        $tahun = $this->request->getGet('tahun') ?: date('Y');

        // Get attendance records for this teacher
        // Fix: Filter by user_id (PK di absensi_harian), bukan id_guru
        $absensi = $this->attendanceModel
            ->where('user_id', $user->id)
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->orderBy('tgl', 'DESC')
            ->findAll();

        // Calculate statistics for current month
        $statistik = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'cuti' => 0,
            'dinas' => 0,
            'wfh' => 0,
            'pending' => 0,
            'alpha' => 0, // alpha biasanya tidak ada di tabel harian jika tidak ada record
            'total' => count($absensi)
        ];

        // Hitung statistik berdasarkan status_masuk (semua enum; dulu ALPHA/
        // DINAS/WFH/PENDING tak terhitung → undercount).
        //
        // Sengaja ada lipatan, bukan double count: satu baris hanya menaikkan
        // satu bucket utama, tapi CUTI juga masuk 'izin' dan DINAS/WFH masuk
        // 'hadir' karena kartu yang ditampilkan di layar hanya Hadir/Izin/
        // Sakit/Alpha. Bucket aslinya tetap terpisah (cuti, dinas, wfh) dan
        // label kartu ikut menyebutkan isinya. `total` tetap jumlah baris,
        // jadi jumlah kartu bisa lebih besar dari total — itu konsekuensi
        // lipatan ini, bukan bug perhitungan.
        foreach ($absensi as $a) {
            $status = strtoupper($a->status_masuk ?? '');
            if (in_array($status, ['ONTIME', 'LATE'])) {
                $statistik['hadir']++;
            } elseif ($status === 'SAKIT') {
                $statistik['sakit']++;
            } elseif ($status === 'CUTI') {
                $statistik['cuti']++;
                $statistik['izin']++;
            } elseif (in_array($status, ['IJIN', 'IZIN'])) {
                $statistik['izin']++;
            } elseif (in_array($status, ['DINAS', 'DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_OUT']) || strpos($status, 'DINAS') === 0) {
                $statistik['dinas']++;
                $statistik['hadir']++;
            } elseif ($status === 'WFH') {
                $statistik['wfh']++;
                $statistik['hadir']++;
            } elseif ($status === 'PENDING_VERIFICATION') {
                $statistik['pending']++;
            } elseif ($status === 'ALPHA' || $status === 'ALPA') {
                $statistik['alpha']++;
            }
        }

        // Get leave/permission requests — filter by user_id
        $izin_list = $this->leaveModel
            ->where('user_id', $user->id)
            ->where('MONTH(tgl_mulai)', $bulan)
            ->where('YEAR(tgl_mulai)', $tahun)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        // Get all leave requests for history table (limit 10)
        $history_izin = $this->leaveModel
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();

        // Estimasi alpha yang belum tercatat (flat, tanpa hari ini) agar ringkasan
        // = SUM tercatat + missing, sama seperti halaman denda (anti double-count).
        $absenSet = $this->attendanceSettingModel->first() ?? (object) [];
        $alphaMissingCount = 0;
        $alphaMissingDenda = 0.0;
        try {
            $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
            $endDate = date('Y-m-t', strtotime($startDate));
            $miss = (new \App\Services\AttendancePenaltyService())
                ->missingAlphaFines((int) $user->id, $startDate, $endDate, $absenSet, false);
            $alphaMissingCount = (int) ($miss['count'] ?? 0);
            $alphaMissingDenda = (float) ($miss['total'] ?? 0);
        } catch (\Throwable $e) {}

        $data = [
            'user' => $user,
            'judul' => 'Absensi Saya',
            'subjudul' => 'Riwayat Kehadiran',
            'setting' => $this->dashboardModel->getSetting(),
            'attendance_setting' => $absenSet,
            'guru' => $guru,
            'absensi' => $absensi,
            'statistik' => $statistik,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulan_ini' => $this->getBulanOptions(),
            'izin_list' => $history_izin,
            'alpha_missing_count' => $alphaMissingCount,
            'alpha_missing_denda' => $alphaMissingDenda,
        ];

        // Load mobile view if available
        if (file_exists(APPPATH . 'Views/members/guru/absensi_mobile.php')) {
            return view('members/guru/absensi_mobile', $data);
        }

        return view('guru/absensi/index', $data);
    }

    /**
     * Detail view for specific date
     */
    public function detail($tanggal)
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');

        $user = $this->getUser();
        if (!$user)
            return redirect()->to('auth');

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);

        if (!$guru) {
            return redirect()->to('guru/absensi')->with('error', 'Data guru tidak ditemukan');
        }

        // Get attendance record for specific date
        // Fix: Filter by user_id, bukan id_guru
        $absensi = $this->attendanceModel
            ->where('user_id', $user->id)
            ->where('tgl', $tanggal)
            ->first();

        if (!$absensi) {
            return redirect()->to('guru/absensi')->with('error', 'Data absensi tidak ditemukan');
        }

        $data = [
            'user' => $user,
            'judul' => 'Detail Absensi',
            'subjudul' => 'Tanggal: ' . date('d F Y', strtotime($tanggal)),
            'setting' => $this->dashboardModel->getSetting(),
            'guru' => $guru,
            'absensi' => $absensi
        ];

        return view('guru/absensi/detail', $data);
    }

    /**
     * Export attendance to Excel/PDF
     */
    public function export()
    {
        if (!$this->isLoggedIn())
            return redirect()->to('auth');

        $user = $this->getUser();
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);

        if (!$guru) {
            return redirect()->to('guru/absensi')->with('error', 'Data guru tidak ditemukan');
        }

        // Validasi rentang bulan/tahun (dipakai di WHERE dan di nama file).
        $bulan = (int) ($this->request->getGet('bulan') ?: date('n'));
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));
        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) date('n');
        }
        if ($tahun < 2000 || $tahun > (int) date('Y') + 1) {
            $tahun = (int) date('Y');
        }
        $bulanStr = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $absensi = $this->attendanceModel
            ->where('user_id', $user->id)
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->orderBy('tgl', 'ASC')
            ->findAll();

        // Excel export (simple CSV for now)
        $namaAman = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', (string) $guru->nama_guru));
        $filename = 'absensi_' . $namaAman . '_' . $tahun . '_' . $bulanStr . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, ['Tanggal', 'Jam Masuk', 'Status Masuk', 'Jam Pulang', 'Status Pulang', 'Telat (menit)']);
        
        // Data
        foreach ($absensi as $a) {
            fputcsv($output, [
                $a->tgl ?? '',
                $a->jam_masuk ?? '',
                $a->status_masuk ?? '',
                $a->jam_pulang ?? '',
                $a->status_pulang ?? '',
                $a->telat_menit ?? '0'
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Helper: Get month options for dropdown
     */
    private function getBulanOptions(): array
    {
        return [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
    }
}
