<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\DashboardModel;
use App\Models\ElearningModel;
use App\Models\JurnalGuruModel;
use App\Models\PiketModel;
use App\Models\PiketJadwalModel;
use App\Services\HariSekolahService;
use App\Services\NotificationService;
use Dompdf\Dompdf;

class Piket extends BaseController
{
    protected $dashboardModel;
    protected $elearningModel;
    protected $piketModel;
    protected $piketJadwalModel;
    protected $user;
    protected $guru;
    protected $tp;
    protected $smt;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->elearningModel = new ElearningModel();
        $this->piketModel = new PiketModel();
        $this->piketModel->initialize();
        $this->piketJadwalModel = new PiketJadwalModel();
        $this->piketJadwalModel->initialize();
    }

    private function initUser()
    {
        $this->user = $this->getUser();
        if (!$this->user)
            return false;

        $tpSmt = $this->getTpSmt();
        $this->tp = $tpSmt->tp;
        $this->smt = $tpSmt->smt;

        // Ensure user is guru/admin
        $this->guru = $this->dashboardModel->getDataGuruByUserId($this->user->id, $this->tp->id_tp, $this->smt->id_smt);
        return true;
    }

    private function getCommonData($title)
    {
        $setting = $this->dashboardModel->getSetting();
        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_sekolah' => 'absensi by ikasmedia', 'logo' => null, 'website' => ''];

        return [
            'judul' => $title,
            'title' => $title,
            'user' => $this->user,
            'guru' => $this->guru,
            'tp_active' => $this->tp,
            'smt_active' => $this->smt,
            'setting' => $setting,
            'is_admin' => false
        ];
    }

    /**
     * Cek apakah guru yang login bertugas piket hari ini.
     * Admin dan kepala sekolah selalu lolos agar tetap bisa monitoring.
     */
    private function isOnDutyToday(): bool
    {
        if ($this->inGroup('admin')) return true;
        if ($this->isKepsek()) return true;
        if (empty($this->guru) || empty($this->guru->id_guru)) return false;
        if (empty($this->tp) || empty($this->smt)) return false;
        $hari = $this->getHariIndo(date('l'));
        $roster = $this->piketJadwalModel->getPiketHariIni($hari, $this->tp->id_tp, $this->smt->id_smt);
        foreach ($roster as $p) {
            if ((int) ($p->id_guru ?? 0) === (int) $this->guru->id_guru) return true;
        }
        return false;
    }

    /**
     * Tolak akses menu piket bila bukan petugas hari ini.
     * Return null bila lolos; response (redirect/403 JSON) bila ditolak.
     */
    private function requireOnDuty()
    {
        if ($this->isOnDutyToday()) return null;
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false,
                'message' => 'Hanya petugas piket hari ini yang dapat mengakses menu ini'
            ]);
        }
        return redirect()->to('guru/piket')->with('error', 'Hanya petugas piket hari ini yang dapat mengakses menu tersebut');
    }

    public function index()
    {
        if (!$this->initUser())
            return redirect()->to('auth');
        // Dashboard piket memuat roster kehadiran seluruh guru — batasi untuk
        // guru/admin agar akun siswa tak bisa mengintip.
        if (empty($this->guru) && !$this->isAdminPiket()) {
            return redirect()->to('dashboard')->with('error', 'Hanya guru piket yang dapat mengakses menu ini');
        }
        if (!$this->tp || !$this->smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = $this->getCommonData('Dashboard Piket');
        $tanggal = date('Y-m-d');

        $hariService = new HariSekolahService();
        $statusHari = $hariService->status($tanggal);
        $isHariSekolah = $statusHari['status'] === HariSekolahService::AKTIF;
        $hari_ini = $statusHari['nama_hari'];

        // Roster hari ini tetap dipakai untuk status "bertugas"; saat libur
        // roster yang ditampilkan adalah hari sekolah berikutnya.
        $rosterHariIni = $this->piketJadwalModel->getPiketHariIni($hari_ini, $this->tp->id_tp, $this->smt->id_smt);
        $rosterHari = $hari_ini;
        $rosterTanggal = $hariService->tanggalPanjang($tanggal);
        if (! $isHariSekolah) {
            $berikut = $hariService->hariSekolahBerikutnya($tanggal);
            if ($berikut !== null) {
                $rosterHari = $hariService->namaHari($berikut);
                $rosterTanggal = $hariService->tanggalPanjang($berikut);
            }
        }
        $data['is_hari_sekolah'] = $isHariSekolah;
        $data['libur_keterangan'] = $statusHari['keterangan'];
        $data['piket_tanggal_label'] = $rosterTanggal;
        $data['piket_hari_ini'] = $isHariSekolah
            ? $rosterHariIni
            : $this->piketJadwalModel->getPiketHariIni($rosterHari, $this->tp->id_tp, $this->smt->id_smt);

        // Stats
        $data['stats'] = $this->piketModel->getStats($tanggal);

        // Ringkasan monitoring KBM (slot vs jurnal vs scan guru) — angka
        // mengikuti halaman input_laporan agar dashboard tak beda tafsir.
        $data['monitoring'] = $isHariSekolah
            ? $this->buildMonitoringRingkas($tanggal)
            : [
                'total_slot' => 0, 'jurnal_isi' => 0, 'jurnal_kosong' => 0,
                'belum_dicek' => 0, 'pengganti' => 0, 'tanpa_scan' => 0,
                'siswa_absen' => 0, 'persen_jurnal' => 0,
            ];

        // Latest journals
        $data['jurnal_terbaru'] = $this->piketModel->getJurnalHarian($tanggal, ['id_tp' => (int) $this->tp->id_tp, 'id_smt' => (int) $this->smt->id_smt]);

        // Weekly piket schedule grouped per day + personal schedule of logged-in guru
        // (integrasi jadwal piket ke frontend presensi /guru/piket)
        $data['jadwal_mingguan'] = $this->piketJadwalModel->getJadwalMingguan($this->tp->id_tp, $this->smt->id_smt);
        $data['jadwal_saya'] = [];
        $data['is_on_duty'] = false;
        $data['is_kepsek'] = $this->isKepsek();
        if (!empty($this->guru) && !empty($this->guru->id_guru)) {
            $data['jadwal_saya'] = $this->piketJadwalModel->getJadwalByGuru(
                (int) $this->guru->id_guru,
                $this->tp->id_tp,
                $this->smt->id_smt
            );
            foreach ($rosterHariIni as $p) {
                if ((int) ($p->id_guru ?? 0) === (int) $this->guru->id_guru) {
                    $data['is_on_duty'] = true;
                    break;
                }
            }
        }
        if ($data['is_kepsek']) {
            $data['is_on_duty'] = true;
        }

        // Today's teacher attendance (check-in/out) - all teachers with status
        $data['guru_absensi'] = $this->piketModel->getAllGuruWithAbsensi($tanggal);

        // Siswa AKTIF yang tidak hadir > 3 hari (bulan & minggu berjalan)
        // untuk ditindaklanjuti. Best-effort: gagal hitung → daftar kosong,
        // dashboard tetap tampil.
        try {
            $data['siswa_sering_alpha'] = $this->piketModel->getSiswaSeringAlpha(
                (int) $this->tp->id_tp,
                (int) $this->smt->id_smt,
                3,
                30
            );
        } catch (\Throwable $e) {
            log_message('error', '[Piket::index] sering alpha: ' . $e->getMessage());
            $data['siswa_sering_alpha'] = [
                'bulan' => [], 'minggu' => [], 'label_bulan' => '',
                'label_minggu' => '', 'batas' => 3,
                'senin' => '', 'ahad' => '',
            ];
        }

        return view('members/guru/piket_mobile', $data);
    }

    // --- TAMU HANDLERS ---
    public function tamu()
    {
        if (!$this->initUser())
            return redirect()->to('auth');
        if ($block = $this->requireOnDuty()) return $block;

        $data = $this->getCommonData('Buku Tamu');
        $tanggal = $this->tanggalLaporanAktif($this->request->getGet('tanggal'));

        $data['data_tamu'] = $this->piketModel->getTamu($tanggal);
        $data['tanggal'] = $tanggal;

        return view('members/guru/piket_tamu_mobile', $data);
    }

    private function validJamTamu(?string $jam): bool
    {
        return is_string($jam) && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', trim($jam)) === 1;
    }

    public function saveTamu()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        if ($block = $this->requireOnDuty()) return $block;

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $jamMasuk = trim((string) $this->request->getPost('jam_masuk'));
        $nama = mb_substr(trim((string) $this->request->getPost('nama')), 0, 255);
        if (!$this->validTanggalLaporan($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid (bukan masa depan)']);
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if ($nama === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama tamu wajib diisi']);
        }
        if ($jamMasuk !== '' && !$this->validJamTamu($jamMasuk)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam masuk tidak valid (HH:MM)']);
        }

        $data = [
            'tanggal' => $tanggal,
            'jam_masuk' => $jamMasuk !== '' ? substr($jamMasuk, 0, 5) . ':00' : date('H:i:s'),
            'nama' => $nama,
            'instansi' => mb_substr(trim((string) $this->request->getPost('instansi')), 0, 255) ?: null,
            'bertemu' => mb_substr(trim((string) $this->request->getPost('bertemu')), 0, 255) ?: null,
            'keperluan' => mb_substr(trim((string) $this->request->getPost('keperluan')), 0, 1000) ?: null,
        ];

        if ($this->piketModel->saveTamu($data)) {
            return $this->response->setJSON(['status' => true, 'message' => 'Data tamu disimpan']);
        }
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan']);
    }

    public function deleteTamu()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir']);
        if ($block = $this->requireOnDuty()) return $block;
        $id = (int) $this->request->getPost('id');
        $row = $id > 0 ? $this->piketModel->getTamuById($id) : null;
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tamu tidak ditemukan']);
        }
        if ($this->terlaluLama($row)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if ($this->piketModel->deleteTamu($id) === false) {
            log_message('error', '[piket] deleteTamu gagal untuk id ' . $id);
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus data tamu']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Data tamu dihapus']);
    }

    public function updateTamuKeluar()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir']);
        if ($block = $this->requireOnDuty()) return $block;
        $id = (int) $this->request->getPost('id');
        $row = $id > 0 ? $this->piketModel->getTamuById($id) : null;
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tamu tidak ditemukan']);
        }
        // Idempoten: sudah keluar → kembalikan jam tercatat, jangan timpa
        if (!empty($row->jam_keluar)) {
            return $this->response->setJSON(['status' => true, 'message' => 'Tamu sudah tercatat keluar', 'jam_keluar' => substr((string) $row->jam_keluar, 0, 5)]);
        }
        if ($this->terlaluLama($row)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if ($this->piketModel->updateTamuKeluar($id)) {
            $fresh = $this->piketModel->getTamuById($id);
            $jam = substr((string) ($fresh->jam_keluar ?? ''), 0, 5);
            return $this->response->setJSON(['status' => true, 'message' => 'Jam keluar berhasil dicatat', 'jam_keluar' => $jam]);
        }
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal mencatat jam keluar']);
    }

    /**
     * Baris buku tamu/kejadian yang lebih tua dari batas edit historis.
     * Tanggal yang tidak terbaca dianggap baru — penolakan sepihak akan
     * membuat catatan hari ini tidak bisa diperbaiki sama sekali.
     */
    private function terlaluLama(object $row): bool
    {
        $tanggal = trim((string) ($row->tanggal ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return false;
        }
        return !$this->bolehEditTanggal($tanggal);
    }

    // --- KEJADIAN HANDLERS ---
    public function kejadian()
    {
        if (!$this->initUser())
            return redirect()->to('auth');
        if ($block = $this->requireOnDuty()) return $block;

        if (!$this->tp || !$this->smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = $this->getCommonData('Catatan Kejadian');
        $tanggal = $this->tanggalLaporanAktif($this->request->getGet('tanggal'));

        $data['data_kejadian'] = $this->piketModel->getKejadian($tanggal, $this->tp->id_tp, $this->smt->id_smt);
        $data['tanggal'] = $tanggal;

        // Need List of Students for Dropdown
        // Simplification: Load All Students or search via Ajax (Better)
        // For now pass empty, assume using Select2 AJAX or simple text for simplicity
        // Let's grab all students for current active TP
        $db = \Config\Database::connect();
        $rows = $db->table('master_siswa m')
            ->select('m.id_siswa, m.nama, k.nama_kelas')
            ->join('kelas_siswa ks', 'ks.id_siswa = m.id_siswa')
            ->join('master_kelas k', 'k.id_kelas = ks.id_kelas')
            ->where('ks.id_tp', $this->tp->id_tp)
            ->where('ks.id_smt', $this->smt->id_smt)
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('m.nama', 'ASC')
            ->get()->getResult();
        // Satu siswa satu baris (data ganda riwayat tahun lalu terbuang)
        $seen = [];
        $data['siswa_list'] = [];
        foreach ($rows as $r) {
            $id = (int) ($r->id_siswa ?? 0);
            if ($id <= 0 || isset($seen[$id])) continue;
            $seen[$id] = true;
            $data['siswa_list'][] = $r;
        }

        return view('members/guru/piket_kejadian_mobile', $data);
    }

    public function saveKejadian()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        if ($block = $this->requireOnDuty()) return $block;

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $jam = trim((string) $this->request->getPost('jam'));
        $jenis = trim((string) $this->request->getPost('jenis'));
        $tingkat = strtolower(trim((string) ($this->request->getPost('tingkat') ?: 'ringan')));
        $terlapor = (int) ($this->request->getPost('terlapor_siswa_id') ?? 0);
        $keterangan = mb_substr(trim((string) $this->request->getPost('keterangan')), 0, 2000);

        if (!$this->validTanggalLaporan($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid (bukan masa depan)']);
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if ($jenis === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'Jenis kejadian wajib diisi']);
        }
        if ($jam !== '' && !$this->validJamTamu($jam)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam tidak valid (HH:MM)']);
        }
        if (!in_array($tingkat, ['ringan', 'sedang', 'berat'], true)) {
            $tingkat = 'ringan';
        }
        if ($keterangan === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'Keterangan kejadian wajib diisi']);
        }
        if ($terlapor > 0) {
            try {
                $ada = \Config\Database::connect()->table('master_siswa')
                    ->where('id_siswa', $terlapor)->countAllResults() > 0;
            } catch (\Throwable $e) {
                $ada = false;
            }
            if (!$ada) {
                return $this->response->setJSON(['status' => false, 'message' => 'Siswa terlapor tidak ditemukan']);
            }
        }

        $data = [
            'tanggal' => $tanggal,
            'jam' => $jam !== '' ? substr($jam, 0, 5) . ':00' : date('H:i:s'),
            'jenis' => mb_substr($jenis, 0, 50),
            'tingkat' => $tingkat,
            'pelapor' => mb_substr(trim((string) $this->request->getPost('pelapor')), 0, 255) ?: null,
            'terlapor_siswa_id' => $terlapor > 0 ? $terlapor : null,
            'keterangan' => $keterangan,
            'penanganan' => mb_substr(trim((string) $this->request->getPost('penanganan')), 0, 2000) ?: null,
            'tindak_lanjut' => mb_substr(trim((string) $this->request->getPost('tindak_lanjut')), 0, 2000) ?: null,
            'status' => 'belum'
        ];

        if ($this->piketModel->saveKejadian($data)) {
            return $this->response->setJSON(['status' => true, 'message' => 'Kejadian berhasil dicatat']);
        }
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan kejadian']);
    }

    public function updateKejadianStatus()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir']);
        if ($block = $this->requireOnDuty()) return $block;
        $id = (int) $this->request->getPost('id');
        $status = trim((string) $this->request->getPost('status'));
        $allowed = ['belum', 'proses', 'selesai'];
        if (!in_array($status, $allowed, true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Status tidak valid']);
        }
        if ($id <= 0 || !($row = $this->piketModel->getKejadianById($id))) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data kejadian tidak ditemukan']);
        }
        if ($this->terlaluLama($row)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if ($this->piketModel->updateKejadianStatus($id, $status)) {
            return $this->response->setJSON(['status' => true, 'message' => 'Status kejadian diperbarui']);
        }
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal memperbarui status']);
    }

    public function deleteKejadian()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir']);
        if ($block = $this->requireOnDuty()) return $block;
        $id = (int) $this->request->getPost('id');
        $row = $id > 0 ? $this->piketModel->getKejadianById($id) : null;
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data kejadian tidak ditemukan']);
        }
        if ($this->terlaluLama($row)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if ($this->piketModel->deleteKejadian($id) === false) {
            log_message('error', '[piket] deleteKejadian gagal untuk id ' . $id);
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus data kejadian']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Data kejadian dihapus']);
    }

    // --- ABSENSI HANDLERS ---
    public function absensi()
    {
        if (!$this->initUser())
            return redirect()->to('auth');
        if ($block = $this->requireOnDuty()) return $block;

        if (!$this->tp || !$this->smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = $this->getCommonData('Rekap Absensi Siswa');
        $tanggal = $this->tanggalLaporanAktif($this->request->getGet('tanggal'));
        $id_kelas = (int) ($this->request->getGet('kelas') ?? 0); // Selected Class

        $data['tanggal'] = $tanggal;
        $data['kelas_selected'] = $id_kelas;

        // Get Classes
        $data['kelas_list'] = $this->elearningModel->getAllKelas($this->tp->id_tp, $this->smt->id_smt);

        $data['siswa_absensi'] = [];
        if ($id_kelas > 0) {
            // Get Students in Class (lingkup TP/SMT aktif)
            $db = \Config\Database::connect();
            $qb = $db->table('kelas_siswa ks')
                ->select('s.id_siswa, s.nama, s.nis')
                ->join('master_siswa s', 's.id_siswa = ks.id_siswa')
                ->where('ks.id_kelas', $id_kelas);
            try {
                if ($db->fieldExists('id_tp', 'kelas_siswa')) $qb->where('ks.id_tp', $this->tp->id_tp);
                if ($db->fieldExists('id_smt', 'kelas_siswa')) $qb->where('ks.id_smt', $this->smt->id_smt);
            } catch (\Throwable $e) {
            }
            // Hanya siswa AKTIF (buku_induk.status = '1').
            try {
                if ($db->tableExists('buku_induk')) {
                    $qb->join('buku_induk bi', 'bi.id_siswa = ks.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e) {
            }
            $students = $qb->orderBy('s.nama', 'ASC')->get()->getResult();

            // Backfill best-effort: siswa yang scan telat otomatis tercatat
            // 'T' bila piket belum mengisinya. Kegagalan tak menggagalkan halaman.
            try {
                $this->piketModel->syncTelatOtomatis($tanggal, $id_kelas);
            } catch (\Throwable $e) {
                log_message('error', '[Piket::absensi] sync telat: ' . $e->getMessage());
            }
            // Backfill best-effort: yang belum scan otomatis 'A' bila tanggal
            // sudah layak (lampau, atau hari ini setelah jam pulang). Isian
            // manual tak pernah ditimpa; yang baru dibuat diteruskan ke formal.
            try {
                $syncA = $this->piketModel->syncAlphaOtomatis($tanggal, $id_kelas);
                if (!empty($syncA['batch'])) {
                    $this->piketModel->syncPiketKeFormal($syncA['batch'], $tanggal);
                }
            } catch (\Throwable $e) {
                log_message('error', '[Piket::absensi] sync alpha: ' . $e->getMessage());
            }

            // Get Existing Attendance (sanitasi: status korup → default H)
            $attendance = $this->piketModel->getAbsensiByKelas($tanggal, $id_kelas);
            $attMap = [];
            foreach ($attendance as $att) {
                $st = strtoupper(trim((string) ($att->status ?? '')));
                $attMap[(int) $att->id_siswa] = in_array($st, self::ABSENSI_STATUS_VALID, true) ? $st : 'H';
            }

            // Relasi read-only ke kehadiran REAL (presensi_siswa hasil scan QR):
            // jam masuk/pulang + status formal per siswa, agar piket tahu
            // kondisi sebenarnya. Tak mengubah arsip piket maupun formal.
            $realMap = $this->piketModel->getRealSiswaMap(
                $tanggal,
                array_map(static fn($s) => (int) ($s->id_siswa ?? 0), $students)
            );
            // Ambang telat = rumus baku rekap & poin (jam_masuk + grace).
            $poinSvc = new \App\Services\SiswaPoinService();
            $realMasuk = 0;
            $realPulang = 0;
            $realKonflik = 0;
            $realTelat = 0;

            foreach ($students as $s) {
                $s->status = $attMap[(int) $s->id_siswa] ?? 'H'; // Default Hadir
                $real = $realMap[(int) $s->id_siswa] ?? null;
                $s->real_masuk = ($real && !empty($real->jam_masuk)) ? substr((string) $real->jam_masuk, 0, 5) : null;
                // Kolom pulang HANYA jam_keluar. Kolom legasi `jam` selalu
                // berisi jam masuk (ditulis scan saat check-in) — memakainya
                // sebagai pulang memalsukan data (0 baris 30 hari terakhir
                // yang jam-nya beda dari jam_masuk).
                $s->real_pulang = ($real && !empty($real->jam_keluar)) ? substr((string) $real->jam_keluar, 0, 5) : null;
                $s->real_status = $real ? strtoupper(trim((string) ($real->status ?? ''))) : null;
                $s->real_sumber_masuk = $real->sumber_masuk ?? null;
                $s->real_sumber_keluar = $real->sumber_keluar ?? null;
                $s->real_telat = $real ? $poinSvc->menitTerlambat($real->jam_masuk ?? null, $real->status ?? null, $real->tgl ?? $tanggal) : 0;
                // Konflik hanya bila baris formal ADA (tanpa baris = belum scan,
                // bukan konflik — selaras keputusan getDivergensi()).
                $s->real_konflik = ($real !== null)
                    ? PiketModel::konflikKehadiran($s->status, $s->real_status, $s->real_masuk !== null)
                    : null;
                if ($s->real_masuk !== null) $realMasuk++;
                if ($s->real_pulang !== null) $realPulang++;
                if ($s->real_konflik !== null) $realKonflik++;
                if ($s->real_telat > 0) $realTelat++;
                $data['siswa_absensi'][] = $s;
            }
            $data['real_ringkasan'] = (object) [
                'masuk' => $realMasuk,
                'pulang' => $realPulang,
                'konflik' => $realKonflik,
                'telat' => $realTelat,
                'total' => count($students),
            ];
            // Tanpa absen pulang: masuk tapi jam pulang kosong. Label "bolos"
            // hanya bila sudah lewat jam pulang; sebelumnya "belum pulang".
            $data['tanpa_pulang'] = [];
            $data['bolos_resmi'] = false;
            try {
                $data['tanpa_pulang'] = $this->piketModel->getSiswaTanpaPulang($tanggal, $id_kelas);
                $data['bolos_resmi'] = $this->piketModel->sudahLewatJamPulang($tanggal);
            } catch (\Throwable $e) {
                log_message('error', '[Piket::absensi] tanpa pulang: ' . $e->getMessage());
            }
        }

        return view('members/guru/piket_absensi_mobile', $data);
    }

    /** Status absensi siswa yang sah untuk jalur piket (selaras opsi UI). */
    private const ABSENSI_STATUS_VALID = ['H', 'S', 'I', 'A', 'T', 'B'];

    public function saveAbsensi()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $id_kelas = (int) $this->request->getPost('id_kelas');
        $absensi = $this->request->getPost('absensi'); // Array of [id_siswa => status]

        if (!$this->validTanggalLaporan($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid (format YYYY-MM-DD, bukan masa depan)']);
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin untuk koreksi lebih lama.']);
        }
        if (($libur = $this->isHariLiburPiket($tanggal)) !== null) {
            return $this->response->setJSON(['status' => false, 'message' => $libur]);
        }
        if ($id_kelas <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak valid']);
        }
        if (!$absensi || !is_array($absensi) || empty($absensi)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada data yang diubah']);
        }
        if (count($absensi) > 500) {
            return $this->response->setJSON(['status' => false, 'message' => 'Batch terlalu besar (maks 500 siswa)']);
        }

        // Sanitasi + whitelist status; kunci id ke integer
        $clean = [];
        foreach ($absensi as $id_siswa => $status) {
            $id = (int) $id_siswa;
            $st = strtoupper(trim((string) $status));
            if ($id <= 0) continue;
            if (!in_array($st, self::ABSENSI_STATUS_VALID, true)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Status tidak valid untuk siswa ID ' . $id]);
            }
            $clean[$id] = $st;
        }
        if (empty($clean)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada data yang valid']);
        }

        // Keanggotaan: semua id harus anggota kelas ini (TP/SMT aktif)
        try {
            $db = \Config\Database::connect();
            $qb = $db->table('kelas_siswa')->select('id_siswa')
                ->where('id_kelas', $id_kelas)
                ->whereIn('id_siswa', array_keys($clean));
            try {
                if ($db->fieldExists('id_tp', 'kelas_siswa')) $qb->where('id_tp', $this->tp->id_tp);
                if ($db->fieldExists('id_smt', 'kelas_siswa')) $qb->where('id_smt', $this->smt->id_smt);
            } catch (\Throwable $e) {
            }
            $anggota = [];
            foreach ($qb->get()->getResult() as $r) $anggota[(int) $r->id_siswa] = true;
            foreach (array_keys($clean) as $id) {
                if (!isset($anggota[$id])) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Siswa ID ' . $id . ' bukan anggota kelas ini']);
                }
            }
            // Tolak siswa non-AKTIF (LULUS/PINDAH/KELUAR).
            try {
                if ($db->tableExists('buku_induk')) {
                    $stRows = $db->table('buku_induk')->select('id_siswa, status')
                        ->whereIn('id_siswa', array_keys($clean))->get()->getResult();
                    foreach ($stRows as $sr) {
                        if ((string) ($sr->status ?? '1') !== '1') {
                            unset($clean[(int) $sr->id_siswa]);
                        }
                    }
                    if (empty($clean)) {
                        return $this->response->setJSON(['status' => false, 'message' => 'Seluruh siswa yang dikirim sudah tidak aktif (lulus/pindah/keluar)']);
                    }
                }
            } catch (\Throwable $e2) {
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveAbsensi] cek anggota: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal memverifikasi data siswa']);
        }

        $pencatat = (int) ($this->user->id ?? 0);
        $batch = [];
        foreach ($clean as $id => $st) {
            $batch[] = [
                'tanggal' => $tanggal,
                'id_kelas' => $id_kelas,
                'id_siswa' => $id,
                'status' => $st,
                'updated_by' => $pencatat > 0 ? $pencatat : null,
            ];
        }

        try {
            if (!$this->piketModel->saveAbsensiBatch($batch)) {
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan absensi']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveAbsensi] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan absensi']);
        }
        // Isian piket = patokan: S/I/A menimpa status formal (jam scan
        // dipertahankan). Best-effort — kegagalan sync tak menggagalkan simpan utama.
        $syncMsg = '';
        try {
            $sync = $this->piketModel->syncPiketKeFormal($batch, $tanggal);
            $n = (int) ($sync['dibuat'] ?? 0) + (int) ($sync['diupdate'] ?? 0);
            if ($n > 0) $syncMsg = " + {$n} diselaraskan ke presensi formal (patokan piket)";
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveAbsensi] sync formal: ' . $e->getMessage());
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Absensi ' . count($batch) . ' siswa disimpan' . $syncMsg]);
    }

    /**
     * Simpan jam MASUK/PULANG manual siswa oleh piket (bulk maupun per orang).
     * POST: tanggal, id_kelas, jam[id_siswa][masuk|pulang] (HH:MM).
     * Aturan main di PiketModel::saveJamManual (cermin scan QR, authorship 'piket'):
     * scan tak pernah ditimpa, SAKIT/IZIN ditolak, pulang butuh masuk.
     */
    public function saveJam()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $id_kelas = (int) $this->request->getPost('id_kelas');
        $jam = $this->request->getPost('jam'); // Array [id_siswa => ['masuk' => ?, 'pulang' => ?]]

        if (!$this->validTanggalLaporan($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid (format YYYY-MM-DD, bukan masa depan)']);
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin untuk koreksi lebih lama.']);
        }
        if (($libur = $this->isHariLiburPiket($tanggal)) !== null) {
            return $this->response->setJSON(['status' => false, 'message' => $libur]);
        }
        if ($id_kelas <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak valid']);
        }
        if (!$jam || !is_array($jam) || empty($jam)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada jam yang diubah']);
        }
        if (count($jam) > 500) {
            return $this->response->setJSON(['status' => false, 'message' => 'Batch terlalu besar (maks 500 siswa)']);
        }

        // Normalisasi per sisi; sisi kosong = jangan sentuh, format ngawur = tolak.
        $rows = [];
        foreach ($jam as $id_siswa => $sisi) {
            $id = (int) $id_siswa;
            if ($id <= 0 || !is_array($sisi)) continue;
            $masukRaw = isset($sisi['masuk']) ? trim((string) $sisi['masuk']) : '';
            $pulangRaw = isset($sisi['pulang']) ? trim((string) $sisi['pulang']) : '';
            $masuk = $masukRaw !== '' ? PiketModel::normalisasiJam($masukRaw) : null;
            $pulang = $pulangRaw !== '' ? PiketModel::normalisasiJam($pulangRaw) : null;
            if ($masukRaw !== '' && $masuk === null) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format jam masuk tidak valid untuk siswa ID ' . $id . ' (HH:MM)']);
            }
            if ($pulangRaw !== '' && $pulang === null) {
                return $this->response->setJSON(['status' => false, 'message' => 'Format jam pulang tidak valid untuk siswa ID ' . $id . ' (HH:MM)']);
            }
            if ($masuk === null && $pulang === null) continue;
            $rows[] = ['id_siswa' => $id, 'masuk' => $masuk, 'pulang' => $pulang];
        }
        if (empty($rows)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada jam yang valid']);
        }

        log_message('info', '[Piket::saveJam] masuk: tanggal=' . $tanggal . ' kelas=' . $id_kelas
            . ' entri=' . count($jam) . ' valid=' . count($rows)
            . ' user=' . (int) ($this->user->id ?? 0));

        // Keanggotaan: semua id harus anggota kelas ini (TP/SMT aktif)
        try {
            $db = \Config\Database::connect();
            $qb = $db->table('kelas_siswa')->select('id_siswa')
                ->where('id_kelas', $id_kelas)
                ->whereIn('id_siswa', array_column($rows, 'id_siswa'));
            try {
                if ($db->fieldExists('id_tp', 'kelas_siswa')) $qb->where('id_tp', $this->tp->id_tp);
                if ($db->fieldExists('id_smt', 'kelas_siswa')) $qb->where('id_smt', $this->smt->id_smt);
            } catch (\Throwable $e) {
            }
            $anggota = [];
            foreach ($qb->get()->getResult() as $r) $anggota[(int) $r->id_siswa] = true;
            foreach ($rows as $r) {
                if (!isset($anggota[(int) $r['id_siswa']])) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Siswa ID ' . (int) $r['id_siswa'] . ' bukan anggota kelas ini']);
                }
            }
            // Tolak siswa non-AKTIF (LULUS/PINDAH/KELUAR): keluarkan dari batch.
            try {
                if ($db->tableExists('buku_induk')) {
                    $ids = array_values(array_unique(array_map(static fn($r) => (int) $r['id_siswa'], $rows)));
                    $buang = [];
                    foreach (array_chunk($ids, 500) as $chunk) {
                        foreach ($db->table('buku_induk')->select('id_siswa, status')->whereIn('id_siswa', $chunk)->get()->getResult() as $sr) {
                            if ((string) ($sr->status ?? '1') !== '1') {
                                $buang[(int) $sr->id_siswa] = true;
                            }
                        }
                    }
                    if (!empty($buang)) {
                        $rows = array_values(array_filter($rows, static fn($r) => !isset($buang[(int) $r['id_siswa']])));
                        if (empty($rows)) {
                            return $this->response->setJSON(['status' => false, 'message' => 'Seluruh siswa yang dikirim sudah tidak aktif (lulus/pindah/keluar)']);
                        }
                    }
                }
            } catch (\Throwable $e2) {
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveJam] cek anggota: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal memverifikasi data siswa']);
        }

        $pencatat = (!empty($this->guru) && !empty($this->guru->id_guru)) ? (int) $this->guru->id_guru : null;
        try {
            $ip = (string) $this->request->getIPAddress();
        } catch (\Throwable $e) {
            $ip = null;
        }
        try {
            $hasil = $this->piketModel->saveJamManual($rows, $tanggal, $id_kelas, $pencatat, $ip);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveJam] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan jam']);
        }

        $n = count($hasil['disimpan'] ?? []);
        if (!empty($hasil['gagal_total'])) {
            log_message('error', '[Piket::saveJam] transaksi dibatalkan: tanggal=' . $tanggal . ' kelas=' . $id_kelas);
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan jam (transaksi dibatalkan). Coba lagi, bila berlanjut hubungi admin.']);
        }
        log_message('info', '[Piket::saveJam] hasil: tanggal=' . $tanggal . ' kelas=' . $id_kelas
            . ' disimpan=' . $n . ' dibuat=' . (int) ($hasil['dibuat'] ?? 0)
            . ' diupdate=' . (int) ($hasil['diupdate'] ?? 0)
            . ' dilewati=' . count($hasil['dilewati'] ?? []));
        if ($n === 0) {
            $alasan = isset($hasil['dilewati'][0]['alasan']) ? (string) $hasil['dilewati'][0]['alasan'] : '';
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada jam tersimpan' . ($alasan !== '' ? ': ' . $alasan : '')]);
        }
        $msg = 'Jam ' . $n . ' siswa disimpan';
        if (!empty($hasil['dilewati'])) $msg .= ' (' . count($hasil['dilewati']) . ' dilewati)';
        if (!empty($hasil['peringatan'])) $msg .= ' — sebagian hanya mengisi 1 sisi';
        return $this->response->setJSON([
            'status' => true,
            'message' => $msg,
            'disimpan' => $n,
            'dilewati' => $hasil['dilewati'],
        ]);
    }

    /**
     * Resolusi satu konflik divergensi. Arah mengikuti status piket:
     * S/I/A piket = patokan → formal (presensi_siswa) yang diselaraskan
     * (jam scan dipertahankan); H/T tak mengalir ke formal sehingga untuk
     * kasus itu piket yang mengikuti sistem.
     * POST: tanggal, id_siswa.
     */
    public function selarasDivergensi()
    {
        if (!$this->initUser()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        }
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $idSiswa = (int) $this->request->getPost('id_siswa');
        if (!$this->validTanggalLaporan($tanggal) || $idSiswa <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak valid']);
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin.']);
        }
        if (($libur = $this->isHariLiburPiket($tanggal)) !== null) {
            return $this->response->setJSON(['status' => false, 'message' => $libur]);
        }

        try {
            $db = \Config\Database::connect();
            $piket = $db->table('piket_absensi')
                ->where('tanggal', $tanggal)->where('id_siswa', $idSiswa)->get()->getRow();
            if (!$piket) {
                return $this->response->setJSON(['status' => false, 'message' => 'Data piket tidak ditemukan']);
            }
            $formal = $db->table('presensi_siswa')
                ->where('id_siswa', $idSiswa)->where('tgl', $tanggal)->get()->getRow();
            $piketSt = strtoupper(trim((string) ($piket->status ?? '')));
            // S/I/A piket = patokan → formal yang mengikuti (jam dipertahankan).
            if (in_array($piketSt, ['S', 'I', 'A'], true)) {
                $targetFormal = \App\Models\PiketModel::piketKeFormal($piketSt);
                if ($targetFormal === null) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Status piket tidak valid']);
                }
                if ($formal !== null && strtoupper(trim((string) ($formal->status ?? ''))) === $targetFormal) {
                    return $this->response->setJSON(['status' => true, 'message' => 'Sudah selaras']);
                }
                $sync = $this->piketModel->syncPiketKeFormal([[
                    'tanggal' => $tanggal,
                    'id_kelas' => (int) $piket->id_kelas,
                    'id_siswa' => $idSiswa,
                    'status' => $piketSt,
                ]], $tanggal);
                if ((int) ($sync['dibuat'] ?? 0) + (int) ($sync['diupdate'] ?? 0) <= 0) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyelaraskan']);
                }
                return $this->response->setJSON(['status' => true, 'message' => 'Diselaraskan mengikuti piket (' . $targetFormal . ')']);
            }
            // H/T tak mengalir ke formal → piket yang mengikuti sistem.
            $target = \App\Models\PiketModel::formalKePiket(
                $formal->status ?? null, !empty($formal->jam_masuk)
            );
            // Formal tercatat hadir TAPI scan-nya telat → selaraskan ke 'T',
            // bukan 'H', memakai ambang baku yang sama (jam_masuk + grace).
            if ($target === 'H' && $formal !== null) {
                try {
                    $telat = (new \App\Services\SiswaPoinService())->menitTerlambat(
                        $formal->jam_masuk ?? null, $formal->status ?? null, $formal->tgl ?? $tanggal
                    );
                    if ($telat > 0) $target = 'T';
                } catch (\Throwable $e) {
                }
            }
            if ($target === null) {
                return $this->response->setJSON(['status' => false, 'message' => 'Sistem belum mencatat siswa ini — selaraskan via halaman Absensi']);
            }
            if (strtoupper(trim((string) ($piket->status ?? ''))) === $target) {
                return $this->response->setJSON(['status' => true, 'message' => 'Sudah selaras']);
            }
            $pencatat = (int) ($this->user->id ?? 0);
            $ok = $this->piketModel->saveAbsensiBatch([[
                'tanggal' => $tanggal,
                'id_kelas' => (int) $piket->id_kelas,
                'id_siswa' => $idSiswa,
                'status' => $target,
                'updated_by' => $pencatat > 0 ? $pencatat : null,
            ]]);
            if (!$ok) {
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyelaraskan']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::selarasDivergensi] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyelaraskan']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Diselaraskan mengikuti sistem (' . $target . ')']);
    }

    // --- JURNAL DETAIL (dipakai modal di Laporan; halaman Monitoring sudah dilebur) ---
    public function jurnalDetail($id)
    {
        if (!$this->initUser()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }
        if ($block = $this->requireOnDuty()) return $block;

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak valid']);
        }

        $j = $this->piketModel->getJurnalById($id);
        if (!$j) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jurnal tidak ditemukan']);
        }

        $infoAbsen = $this->piketModel->infoAbsenJurnal($j);

        $totalSiswa = $this->piketModel->countSiswaKelas((int) $j->id_kelas, isset($j->id_tp) ? (int) $j->id_tp : null, isset($j->id_smt) ? (int) $j->id_smt : null);
        $countAbsent = (int) $infoAbsen['jumlah'];
        $countHadir = max(0, $totalSiswa - $countAbsent);
        $absentNames = $infoAbsen['nama'];

        return $this->response->setJSON([
            'status' => true,
            'data' => [
                'id_jurnal' => (int) $j->id_jurnal,
                'id_guru' => (int) ($j->id_guru ?? 0),
                'nama_guru' => $j->nama_guru ?? '',
                'nama_mapel' => $j->nama_mapel ?? '',
                'nama_kelas' => $j->nama_kelas ?? '',
                'tanggal' => $j->tanggal ?? '',
                'jam_ke' => $j->jam_ke ?? '',
                'jam_mulai' => $j->jam_mulai_slot ?? null,
                'jam_selesai' => $j->jam_selesai_slot ?? null,
                'materi_pokok' => $j->materi_pokok ?? '',
                'tujuan_pembelajaran' => $j->tujuan_pembelajaran ?? '',
                'model_belajar' => $j->model_belajar ?? '',
                'media_alat' => $j->media_alat ?? '',
                'profil_pancasila' => $j->profil_pancasila ?? '[]',
                'kegiatan_belajar' => $j->kegiatan_belajar ?? '',
                'jenis_asesmen' => $j->jenis_asesmen ?? '',
                'refleksi' => $j->refleksi ?? '',
                'tindak_lanjut' => $j->tindak_lanjut ?? '',
                'catatan_khusus' => $j->catatan_khusus ?? '',
                'validasi_status' => (string) ($j->validasi_status ?? JurnalGuruModel::VALIDASI_PENDING) ?: JurnalGuruModel::VALIDASI_PENDING,
                'validasi_catatan' => (string) ($j->validasi_catatan ?? ''),
                'validasi_pada' => (string) ($j->validasi_pada ?? ''),
                'jumlah_siswa_total' => $totalSiswa,
                'jumlah_hadir' => $countHadir,
                'jumlah_tidak_hadir' => $countAbsent,
                'siswa_tidak_hadir' => $absentNames,
            ]
        ]);
    }

    public function jurnalPdf($id)
    {
        if (!$this->initUser()) {
            return redirect()->to('auth');
        }
        if ($block = $this->requireOnDuty()) return $block;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID tidak valid');
        }

        $j = $this->piketModel->getJurnalById($id);
        if (!$j) {
            return redirect()->back()->with('error', 'Jurnal tidak ditemukan');
        }

        $infoAbsen = $this->piketModel->infoAbsenJurnal($j);

        $totalSiswa = $this->piketModel->countSiswaKelas((int) $j->id_kelas, isset($j->id_tp) ? (int) $j->id_tp : null, isset($j->id_smt) ? (int) $j->id_smt : null);
        $countAbsent = (int) $infoAbsen['jumlah'];
        $countHadir = max(0, $totalSiswa - $countAbsent);
        $absentNames = $infoAbsen['nama'];

        $html = view('members/guru/piket_jurnal_detail_pdf', [
            'j' => $j,
            'total_siswa' => $totalSiswa,
            'jumlah_hadir' => $countHadir,
            'jumlah_tidak_hadir' => $countAbsent,
            'siswa_tidak_hadir' => $absentNames,
            'tp_active' => $this->tp,
            'smt_active' => $this->smt,
        ]);

        $download = (int) ($this->request->getGet('download') ?? 0) === 1;
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $safeGuru = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($j->nama_guru ?? 'Guru'));
        $safeTanggal = preg_replace('/[^0-9-]+/', '', (string) ($j->tanggal ?? date('Y-m-d')));
        $safeJam = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($j->jam_ke ?? ''));
        $filename = "Detail_Jurnal_{$safeGuru}_{$safeTanggal}_Jam_{$safeJam}.pdf";

        $dompdf->stream($filename, ['Attachment' => $download]);
        exit;
    }

    private function isAdminPiket(): bool
    {
        try {
            return $this->inGroup('admin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Batas edit historis: piket hanya H-7 ke belakang; admin bebas.
     * Masa depan tetap ditolak oleh validTanggalLaporan.
     */
    private function bolehEditTanggal(string $tgl): bool
    {
        if ($this->isAdminPiket()) return true;
        $batas = date('Y-m-d', strtotime('-7 days'));
        return $tgl >= $batas;
    }

    /**
     * Cek hari libur mingguan/nasional (AttendancePolicy, sumber yang sama
     * dengan presensi siswa, guru, rekap, dan cron).
     * KBM tak dicatat saat libur kecuali admin override via jadwal.
     */
    private function isHariLiburPiket(string $tgl): ?string
    {
        try {
            $alasan = \App\Services\AttendancePolicy::alasanLibur($tgl);
            if ($alasan !== null) {
                return $alasan . ' — KBM tidak dicatat';
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    /**
     * Validasi tanggal YYYY-MM-DD (tolak format ngawur & tanggal masa depan).
     */
    private function validTanggalLaporan(?string $tgl): bool
    {
        if (!is_string($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) return false;
        [$y, $m, $d] = array_map('intval', explode('-', $tgl));
        if (!checkdate($m, $d, $y)) return false;
        return $tgl <= date('Y-m-d');
    }

    private function tanggalLaporanAktif(?string $tgl): string
    {
        $tgl = trim((string) $tgl);
        if (!$this->validTanggalLaporan($tgl)) return date('Y-m-d');
        return $tgl;
    }

    /**
     * Peta guru pengampu: [id_kelas][id_mapel] => id_guru (+ nama guru).
     * Diekstrak agar laporan / PDF / input / save memakai satu sumber.
     */
    private function resolveTeacherMap(): array
    {
        $db = \Config\Database::connect();
        $assignments = $db->table('jabatan_guru')
            ->select('id_guru, mapel_kelas')
            ->where('id_tp', $this->tp->id_tp)
            ->where('id_smt', $this->smt->id_smt)
            ->get()->getResult();

        $teacherMap = [];
        $guruNames = [];
        foreach ($this->elearningModel->getAllGuru() as $g) {
            $guruNames[$g->id_guru] = $g->nama_guru;
        }

        foreach ($assignments as $a) {
            $raw = (string) ($a->mapel_kelas ?? '');
            $mapels = null;
            if ($raw !== '' && $raw !== 'b:0;') {
                // JSON dulu (format baru), lalu serialize (format lama) tanpa @
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $mapels = $decoded;
                } else {
                    try {
                        $tmp = unserialize($raw, ['allowed_classes' => false]);
                        if (is_array($tmp)) $mapels = $tmp;
                    } catch (\Throwable $e) {
                        log_message('warning', '[Piket::resolveTeacherMap] mapel_kelas rusak id_guru={g}', ['g' => $a->id_guru ?? 0]);
                        continue;
                    }
                    if (!is_array($mapels)) continue;
                }
            } elseif ($raw === 'b:0;') {
                continue;
            } else {
                continue;
            }
            if ($mapels && is_array($mapels)) {
                foreach ($mapels as $m) {
                    if (isset($m['kelas_mapel']) && is_array($m['kelas_mapel'])) {
                        foreach ($m['kelas_mapel'] as $km)
                            if (isset($km['kelas']))
                                $teacherMap[$km['kelas']][$m['id_mapel']] = $a->id_guru;
                    }
                }
            }
        }

        return [$teacherMap, $guruNames];
    }

    /**
     * Peta jurnal mengajar satu tanggal.
     *
     * - [id_kelas][id_mapel][jam_ke] => id_jurnal: fallback data lama.
     * - [id_jadwal] => id_jurnal: jurnal yang sudah di-set ke slot jadwal.
     *
     * Halaman input piket memakai peta id_jadwal agar jurnal hanya menempel
     * pada slot yang benar-benar di-set, bukan slot lain yang kebetulan sama
     * kelas/mapel/jam-nya. Slot ganda dihitung; id_jurnal terbesar dianggap
     * entri terbaru.
     *
     * @return array{0: array, 1: array<int, object>, 2: int, 3: array<int, int>}
     */
    private function loadJurnalMap(string $tanggal): array
    {
        // Filter TP/SMT hanya untuk hari ini; tanggal lama tampil apa adanya.
        $isToday = ($tanggal === date('Y-m-d'));
        $journals = $this->piketModel->getJurnalHarian($tanggal, [
            'id_tp' => $isToday ? (int) $this->tp->id_tp : 0,
            'id_smt' => $isToday ? (int) $this->smt->id_smt : 0,
        ]);

        $journalMap = [];
        $journalByJadwal = [];
        $jurnalInfo = [];
        $duplikatJurnal = 0;
        foreach ($journals as $j) {
            $id = (int) $j->id_jurnal;

            // Jurnal baru selalu membawa id_jadwal. Peta ini yang dipakai
            // halaman input agar hanya jurnal hasil "set" slot yang muncul.
            $idJadwal = (int) ($j->id_jadwal ?? 0);
            if ($idJadwal > 0) {
                $existingJadwal = (int) ($journalByJadwal[$idJadwal] ?? 0);
                if ($existingJadwal > 0) {
                    $duplikatJurnal++;
                    if ($id < $existingJadwal) continue;
                }
                $journalByJadwal[$idJadwal] = $id;
            }

            // Fallback lama tetap dipertahankan untuk laporan/monitoring
            // historis yang belum punya tautan jadwal.
            $existing = (int) ($journalMap[$j->id_kelas][$j->id_mapel][$j->jam_ke] ?? 0);
            if ($existing > 0 && $idJadwal <= 0) {
                $duplikatJurnal++;
                if ($id < $existing) continue;
            }
            $journalMap[$j->id_kelas][$j->id_mapel][$j->jam_ke] = $id;
            $jurnalInfo[$id] = $j;
        }

        return [$journalMap, $jurnalInfo, $duplikatJurnal, $journalByJadwal];
    }

    /**
     * Nama penanda tangan validasi dari baris jurnal: [users.id] => nama.
     * Satu query untuk seluruh jurnal tanggal terpilih.
     *
     * @param array<int, object> $jurnalInfo
     * @return array<int, string>
     */
    private function loadValidatorNames(array $jurnalInfo): array
    {
        $ids = [];
        foreach ($jurnalInfo as $j) {
            $oleh = (int) ($j->validasi_oleh ?? 0);
            if ($oleh > 0 && (string) ($j->validasi_status ?? '') !== JurnalGuruModel::VALIDASI_PENDING) {
                $ids[$oleh] = true;
            }
        }
        if ($ids === []) {
            return [];
        }

        $nama = [];
        try {
            // users tidak punya kolom `name` — nama tampil dari first/last name.
            $rows = \Config\Database::connect()->table('users')
                ->select('id, TRIM(CONCAT_WS(" ", first_name, last_name)) AS nama', false)
                ->whereIn('id', array_keys($ids))
                ->get()->getResult();
            foreach ($rows as $r) {
                $nama[(int) $r->id] = trim((string) ($r->nama ?? ''));
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::loadValidatorNames] ' . $e->getMessage());
        }

        return $nama;
    }

    /**
     * Peta check-in/out guru harian: [id_guru] => baris absensi_harian.
     * Baris lama ber-id_guru=0 dipetakan via master_guru.id_user.
     */
    private function loadAbsensiMap(string $tanggal): array
    {
        $rows = $this->piketModel->getAbsensiGuruHarian($tanggal);
        $userToGuru = [];
        try {
            foreach (\Config\Database::connect()->table('master_guru')->select('id_guru, id_user')->get()->getResult() as $gr) {
                if (!empty($gr->id_user)) $userToGuru[(int) $gr->id_user] = (int) $gr->id_guru;
            }
        } catch (\Throwable $e) {
        }

        $absensiMap = [];
        foreach ($rows as $a) {
            $gid = (int) ($a->id_guru ?? 0);
            if ($gid <= 0 && !empty($a->user_id) && isset($userToGuru[(int) $a->user_id])) {
                $gid = $userToGuru[(int) $a->user_id];
            }
            if ($gid > 0 && !isset($absensiMap[$gid])) $absensiMap[$gid] = $a;
        }

        return $absensiMap;
    }

    /**
     * Ringkasan monitoring KBM satu tanggal untuk dashboard piket.
     * Definisi angka (jurnal, tanpa scan, pengganti, absen siswa) sengaja
     * disamakan dengan inputLaporan() supaya kedua halaman tidak berbeda.
     *
     * @return array<string,int>
     */
    private function buildMonitoringRingkas(string $tanggal): array
    {
        $ringkas = [
            'total_slot' => 0,
            'jurnal_isi' => 0,
            'jurnal_kosong' => 0,
            'belum_dicek' => 0,
            'pengganti' => 0,
            'tanpa_scan' => 0,
            'siswa_absen' => 0,
            'persen_jurnal' => 0,
            'perlu_validasi' => 0,
        ];
        if (empty($this->tp) || empty($this->smt)) {
            return $ringkas;
        }

        $idHari = (int) date('N', strtotime($tanggal));
        $schedules = $this->elearningModel->getJadwalHarian($this->tp->id_tp, $this->smt->id_smt, $idHari);
        if (empty($schedules)) {
            return $ringkas;
        }

        [$teacherMap] = $this->resolveTeacherMap();
        [$journalMap, $jurnalInfo, , $journalByJadwal] = $this->loadJurnalMap($tanggal);
        $absensiMap = $this->loadAbsensiMap($tanggal);

        $sudahDicek = [];
        try {
            foreach ($this->piketModel->getPresensiGuru($tanggal) as $m) {
                $sudahDicek[(int) $m->id_kelas][(int) $m->jam_ke] = true;
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::buildMonitoringRingkas] presensi: ' . $e->getMessage());
        }

        foreach ($schedules as $idKls => $slots) {
            foreach ($slots as $s) {
                // Slot tanpa guru pengampu belum di-set; tidak dihitung
                // sebagai jurnal yang seharusnya ada.
                $guruId = $teacherMap[$idKls][$s->id_mapel] ?? null;
                if (empty($guruId)) {
                    continue;
                }

                $ringkas['total_slot']++;
                if (empty($sudahDicek[$idKls][$s->jam_ke])) {
                    $ringkas['belum_dicek']++;
                }

                // Samakan dengan halaman input: hanya jurnal yang sudah
                // di-set ke slot jadwal ini yang dihitung terisi.
                $idJadwalSlot = (int) ($s->id_jadwal ?? 0);
                $jurnalId = $idJadwalSlot > 0 ? (int) ($journalByJadwal[$idJadwalSlot] ?? 0) : 0;
                $jurnalRow = $jurnalId > 0 ? ($jurnalInfo[$jurnalId] ?? null) : null;
                if (!$jurnalRow) {
                    $ringkas['jurnal_kosong']++;
                    continue;
                }
                $ringkas['jurnal_isi']++;

                // Jurnal warisan (kolom validasi belum terisi) dihitung sebagai
                // antrean, sama seperti normalisasi di inputLaporan().
                $statusVal = (string) ($jurnalRow->validasi_status ?? '');
                if (!in_array($statusVal, JurnalGuruModel::validasiStatusList(), true) || $statusVal === JurnalGuruModel::VALIDASI_PENDING) {
                    $ringkas['perlu_validasi']++;
                }

                // Pelaksana = guru pengampu slot, kecuali jurnal ditulis guru
                // lain (pengganti). Scan yang dinilai adalah scan pelaksana.
                $guruId = (int) ($teacherMap[$idKls][$s->id_mapel] ?? 0);
                $pelaksanaId = (int) ($jurnalRow->id_guru ?? 0);
                $pengganti = $guruId > 0 && $pelaksanaId > 0 && $pelaksanaId !== $guruId;
                if ($pengganti) {
                    $ringkas['pengganti']++;
                }
                $scanId = $pengganti ? $pelaksanaId : $guruId;
                if ($scanId <= 0 || empty($absensiMap[$scanId]->jam_masuk)) {
                    $ringkas['tanpa_scan']++;
                }

                $absen = \App\Models\PiketModel::parseSiswaAbsent($jurnalRow->siswa_absent ?? null);
                $ringkas['siswa_absen'] += !empty($absen['ids']) ? count($absen['ids']) : count($absen['nama']);
            }
        }

        if ($ringkas['total_slot'] > 0) {
            $ringkas['persen_jurnal'] = (int) round($ringkas['jurnal_isi'] / $ringkas['total_slot'] * 100);
        }

        return $ringkas;
    }

    /**
     * Bangun data laporan harian (dipakai laporan() & laporanPdf() agar tak duplikasi).
     */
    private function buildLaporanReport(string $tanggal, int $filterKelas, int $filterMapel): array
    {
        [$teacherMap, $guruNames] = $this->resolveTeacherMap();

        // Jadwal hari berjalan apa adanya (Minggu = hari 7; kosong bila tak ada jadwal)
        $idHari = (int) date('N', strtotime($tanggal));
        $schedules = $this->elearningModel->getJadwalHarian($this->tp->id_tp, $this->smt->id_smt, $idHari);

        // Journals (Auto) — slot ganda terdeteksi, entri terbaru menang.
        [$journalMap, $jurnalInfo, $duplikatJurnal, $journalByJadwal] = $this->loadJurnalMap($tanggal);

        // Piket Manual Input (Manual Cross-check) — kunci per (kelas, jam, mapel)
        $manuals = $this->piketModel->getPresensiGuru($tanggal);
        $manualMap = [];
        foreach ($manuals as $m) {
            $manualMap[$m->id_kelas][$m->jam_ke][$m->id_mapel ?? '*'] = $m;
            $manualMap[$m->id_kelas][$m->jam_ke]['*'] ??= $m;
        }

        // Absensi Harian Guru (Check-in/out) — baris lama bisa ber-id_guru=0
        // (hanya user_id), petakan ulang via master_guru.id_user.
        $absensiMap = $this->loadAbsensiMap($tanggal);

        // Build Report
        $report = [];
        $classList = $this->elearningModel->getAllKelasSort($this->tp->id_tp, $this->smt->id_smt);

        foreach ($classList as $idKls => $namaKls) {
            if ($filterKelas > 0 && (int) $idKls !== $filterKelas) continue;
            if (!isset($schedules[$idKls]))
                continue;
            $report[$idKls] = ['nama_kelas' => $namaKls, 'slots' => []];
            foreach ($schedules[$idKls] as $s) {
                if ($filterMapel > 0 && (int) $s->id_mapel !== $filterMapel) continue;
                $guruId = $teacherMap[$idKls][$s->id_mapel] ?? null;
                // Slot tanpa guru pengampu belum di-set; lewati agar rekap
                // tidak menghitung "tanpa jurnal" untuk slot yang mustahil diisi.
                if (empty($guruId)) {
                    continue;
                }
                // Jurnal hanya dihitung bila sudah di-set ke slot jadwal ini.
                $idJadwalSlot = (int) ($s->id_jadwal ?? 0);
                $jurnalId = $idJadwalSlot > 0 ? (int) ($journalByJadwal[$idJadwalSlot] ?? 0) : 0;
                $jurnalRow = $jurnalId ? ($jurnalInfo[$jurnalId] ?? null) : null;
                $manualRow = $manualMap[$idKls][$s->jam_ke][$s->id_mapel]
                    ?? $manualMap[$idKls][$s->jam_ke]['*']
                    ?? null;

                // Monitoring: pengganti = jurnal ditulis guru lain; scan yang
                // dinilai adalah scan pelaksana (sama seperti inputLaporan).
                $jurnalGuruId = (int) ($jurnalRow->id_guru ?? 0);
                $pengganti = $jurnalRow && $guruId && $jurnalGuruId > 0 && $jurnalGuruId !== (int) $guruId;
                $absensiGuru = $guruId ? ($absensiMap[(int) $guruId] ?? null) : null;
                $absensiJurnal = $pengganti ? ($absensiMap[$jurnalGuruId] ?? null) : null;
                $scanMasuk = !empty($absensiGuru->jam_masuk);
                $penggantiScanMasuk = $pengganti && !empty($absensiJurnal->jam_masuk);
                $siswaAbsen = $jurnalRow
                    ? $this->piketModel->infoAbsenJurnal($jurnalRow)
                    : ['jumlah' => 0, 'nama' => []];

                $report[$idKls]['slots'][] = [
                    'jam_ke' => $s->jam_ke,
                    'id_mapel' => $s->id_mapel,
                    'nama_mapel' => $s->nama_mapel,
                    'id_guru' => $guruId,
                    'nama_guru' => $guruId ? ($guruNames[$guruId] ?? 'Guru Tidak Ditemukan') : 'Belum Diset',
                    'jurnal' => $jurnalId ? true : false,
                    'jurnal_id' => $jurnalId,
                    'materi' => ($jurnalId && isset($jurnalInfo[$jurnalId]))
                        ? trim((string) ($jurnalInfo[$jurnalId]->materi_pokok ?? ''))
                        : '',
                    'piket' => $manualRow,
                    // Alias untuk view lama yang membaca kunci 'manual'.
                    'manual' => $manualRow,
                    'absensi' => $absensiGuru,
                    'jurnal_guru_id' => $jurnalGuruId,
                    'jurnal_guru_nama' => $jurnalRow ? trim((string) ($jurnalRow->nama_guru ?? '')) : '',
                    'pengganti' => $pengganti,
                    'scan_masuk' => $scanMasuk,
                    'scan_jam' => $scanMasuk ? substr((string) $absensiGuru->jam_masuk, 0, 5) : '',
                    'pengganti_scan_masuk' => $penggantiScanMasuk,
                    'pengganti_scan_jam' => $penggantiScanMasuk ? substr((string) $absensiJurnal->jam_masuk, 0, 5) : '',
                    'tanpa_scan' => $jurnalRow && !($pengganti ? $penggantiScanMasuk : $scanMasuk),
                    'validasi_status' => $jurnalRow ? (string) ($jurnalRow->validasi_status ?? '') : '',
                    'siswa_absen_jumlah' => (int) ($siswaAbsen['jumlah'] ?? 0),
                    'siswa_absen_nama' => $siswaAbsen['nama'] ?? [],
                ];
            }
            if (empty($report[$idKls]['slots'])) {
                unset($report[$idKls]);
            }
        }

        $summary = [
            'total_slot' => 0,
            'jurnal_isi' => 0,
            'jurnal_kosong' => 0,
            'piket_isi' => 0,
            'belum_dicek' => 0,
            'pengganti' => 0,
            'tanpa_scan' => 0,
            'siswa_absen' => 0,
            'perlu_validasi' => 0,
            'absen_masuk' => 0,
            'mismatch' => 0,
            'kejadian' => 0,
            'duplikat_jurnal' => $duplikatJurnal,
        ];
        try {
            $stats = $this->piketModel->getStats($tanggal);
            if (is_array($stats)) {
                $summary['kejadian'] = (int) ($stats['kejadian'] ?? 0);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::buildLaporanReport] getStats: ' . $e->getMessage());
        }
        $uniqueGuruAbsen = [];
        foreach ($report as $kls) {
            foreach ($kls['slots'] as $s) {
                $summary['total_slot']++;
                if (!empty($s['jurnal'])) $summary['jurnal_isi']++;
                else $summary['jurnal_kosong']++;
                if (!empty($s['jurnal'])) {
                    $st = (string) ($s['validasi_status'] ?? '');
                    if ($st !== JurnalGuruModel::VALIDASI_APPROVED && $st !== JurnalGuruModel::VALIDASI_REJECTED) {
                        $summary['perlu_validasi']++;
                    }
                }
                if (!empty($s['piket'])) $summary['piket_isi']++;
                else $summary['belum_dicek']++;
                if (!empty($s['pengganti'])) $summary['pengganti']++;
                if (!empty($s['tanpa_scan'])) $summary['tanpa_scan']++;
                $summary['siswa_absen'] += (int) ($s['siswa_absen_jumlah'] ?? 0);
                if (!empty($s['absensi']) && !empty($s['absensi']->jam_masuk) && !empty($s['absensi']->id_guru)) {
                    $uniqueGuruAbsen[(int) $s['absensi']->id_guru] = true;
                }
                $piket = $s['piket'];
                if ($piket) {
                    $isFilled = (bool) $s['jurnal'];
                    $isPiketHadir = ($piket->status == 'H');
                    if ($isFilled !== $isPiketHadir) $summary['mismatch']++;
                }
            }
        }
        $summary['absen_masuk'] = count($uniqueGuruAbsen);

        // ── Seksi tambahan laporan piket (siswa + guru + pembelajaran) ──
        // 1. Rekap kehadiran SISWA per kelas (status piket + scan real).
        $siswaRekap = ['kelas' => [], 'total' => []];
        try {
            $siswaRekap = $this->piketModel->getRekapSiswaLaporan(
                $tanggal, (int) $this->tp->id_tp, (int) $this->smt->id_smt, $filterKelas
            );
        } catch (\Throwable $e) {
            log_message('error', '[Piket::buildLaporanReport] siswa: ' . $e->getMessage());
        }

        // 2. Rekap kehadiran GURU (check-in/out real).
        $guruRekap = ['total' => 0, 'tepat' => 0, 'telat' => 0, 'belum' => 0, 'list' => []];
        try {
            foreach ($this->piketModel->getAllGuruWithAbsensi($tanggal) as $g) {
                $masuk = !empty($g->jam_masuk);
                $telat = $masuk && strtolower((string) ($g->status_masuk ?? '')) === 'late';
                $guruRekap['total']++;
                if (!$masuk) {
                    $guruRekap['belum']++;
                    $g->hadir_kat = 'Belum';
                } elseif ($telat) {
                    $guruRekap['telat']++;
                    $g->hadir_kat = 'Telat';
                } else {
                    $guruRekap['tepat']++;
                    $g->hadir_kat = 'Tepat';
                }
                $guruRekap['list'][] = $g;
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::buildLaporanReport] guru: ' . $e->getMessage());
        }

        // 3. Rekap PEMBELAJARAN per kelas (slot KBM vs jurnal + materi).
        $belajarKelas = [];
        $belajarTerisi = 0;
        $belajarSlot = 0;
        $belajarMateri = 0;
        foreach ($report as $idKls => $kls) {
            $isi = 0;
            $materi = [];
            foreach ($kls['slots'] as $s) {
                if (!empty($s['jurnal'])) {
                    $isi++;
                    if (trim((string) ($s['materi'] ?? '')) !== '') {
                        $materi[] = 'Jam ' . $s['jam_ke'] . ' ' . $s['nama_mapel'] . ': ' . $s['materi'];
                    }
                }
            }
            $nSlot = count($kls['slots']);
            $belajarSlot += $nSlot;
            $belajarTerisi += $isi;
            $belajarMateri += count($materi);
            $belajarKelas[] = (object) [
                'id_kelas' => (int) $idKls,
                'nama_kelas' => $kls['nama_kelas'],
                'total_slot' => $nSlot,
                'jurnal_isi' => $isi,
                'materi' => $materi,
            ];
        }
        $belajarRekap = [
            'kelas' => $belajarKelas,
            'total_slot' => $belajarSlot,
            'terisi' => $belajarTerisi,
            'persen' => $belajarSlot > 0 ? (int) round($belajarTerisi / $belajarSlot * 100) : 0,
            'materi_count' => $belajarMateri,
        ];

        $plus = ['siswa' => $siswaRekap, 'guru' => $guruRekap, 'belajar' => $belajarRekap];

        return [$report, $summary, $classList, $teacherMap, $guruNames, $plus];
    }

    public function laporan()
    {
        if (!$this->initUser())
            return redirect()->to('auth');
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = $this->getCommonData('Rekap Kehadiran Guru');
        $tanggal = $this->tanggalLaporanAktif($this->request->getGet('tanggal'));
        $filterKelas = (int) ($this->request->getGet('kelas') ?? 0);
        $filterMapel = (int) ($this->request->getGet('mapel') ?? 0);
        $data['tanggal'] = $tanggal;
        $data['filter_kelas'] = $filterKelas;
        $data['filter_mapel'] = $filterMapel;

        [$report, $summary, $classList, , , $plus] = $this->buildLaporanReport($tanggal, $filterKelas, $filterMapel);

        $data['kelas_list'] = $classList;
        $data['mapel_list'] = $this->elearningModel->getMapelArray();
        $data['summary'] = $summary;
        $data['report'] = $report;
        $data['siswa_rekap'] = $plus['siswa'] ?? ['kelas' => [], 'total' => []];
        $data['guru_rekap'] = $plus['guru'] ?? [];
        $data['belajar_rekap'] = $plus['belajar'] ?? [];
        // Monitoring KBM memakai ringkasan report agar angkanya persis sama
        // dengan daftar slot yang tampil (termasuk filter kelas/mapel).
        $data['monitoring'] = [
            'total_slot' => (int) $summary['total_slot'],
            'jurnal_isi' => (int) $summary['jurnal_isi'],
            'jurnal_kosong' => (int) $summary['jurnal_kosong'],
            'belum_dicek' => (int) $summary['belum_dicek'],
            'pengganti' => (int) $summary['pengganti'],
            'tanpa_scan' => (int) $summary['tanpa_scan'],
            'siswa_absen' => (int) $summary['siswa_absen'],
            'perlu_validasi' => (int) $summary['perlu_validasi'],
            'persen_jurnal' => (int) $summary['total_slot'] > 0
                ? (int) round($summary['jurnal_isi'] / $summary['total_slot'] * 100)
                : 0,
        ];
        // Selisih piket vs presensi formal (pindahan halaman Monitoring yang dilebur).
        $data['divergensi'] = [];
        try {
            $data['divergensi'] = $this->piketModel->getDivergensi($tanggal, $filterKelas > 0 ? $filterKelas : null, 50);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::laporan] divergensi: ' . $e->getMessage());
        }
        return view('members/guru/piket_laporan_mobile', $data);
    }

    public function laporanPdf()
    {
        if (!$this->initUser()) {
            return redirect()->to('auth');
        }
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $tanggal = $this->tanggalLaporanAktif($this->request->getGet('tanggal'));
        $filterKelas = (int) ($this->request->getGet('kelas') ?? 0);
        $filterMapel = (int) ($this->request->getGet('mapel') ?? 0);

        [$report, $summary, , , , $plus] = $this->buildLaporanReport($tanggal, $filterKelas, $filterMapel);

        // Stats & Piket crew
        $hari_ini = $this->getHariIndo(date('l', strtotime($tanggal)));
        $piket_crew = $this->piketJadwalModel->getPiketHariIni($hari_ini, $this->tp->id_tp, $this->smt->id_smt);
        $tamu = $this->piketModel->getTamu($tanggal);
        $kejadian = $this->piketModel->getKejadian($tanggal, $this->tp->id_tp, $this->smt->id_smt);
        $stats = $this->piketModel->getStats($tanggal);
        $setting = $this->dashboardModel->getSetting();

        // Render PDF HTML
        $html = view('members/guru/piket_laporan_pdf', [
            'tanggal' => $tanggal,
            'hari_ini' => $hari_ini,
            'report' => $report,
            'summary' => $summary,
            'siswa_rekap' => $plus['siswa'] ?? ['kelas' => [], 'total' => []],
            'guru_rekap' => $plus['guru'] ?? [],
            'belajar_rekap' => $plus['belajar'] ?? [],
            'piket_crew' => $piket_crew,
            'tamu' => $tamu,
            'kejadian' => $kejadian,
            'stats' => $stats,
            'setting' => $setting,
            'tp_active' => $this->tp,
            'smt_active' => $this->smt,
            'guru' => $this->guru
        ]);

        $download = (int) ($this->request->getGet('download') ?? 0) === 1;
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Laporan_Piket_" . date('Y-m-d', strtotime($tanggal)) . ".pdf";
        $dompdf->stream($filename, ['Attachment' => $download]);
        exit;
    }

    /**
     * Kirim rekap piket harian ke grup WhatsApp/Telegram.
     * POST: tanggal. Hanya petugas piket hari ini (atau admin).
     */
    public function kirimRekap()
    {
        if (!$this->initUser()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        }
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $tanggal = $this->tanggalLaporanAktif(trim((string) $this->request->getPost('tanggal')));
        $isToday = ($tanggal === date('Y-m-d'));
        $tpId = $isToday ? (int) $this->tp->id_tp : 0;
        $smtId = $isToday ? (int) $this->smt->id_smt : 0;

        try {
            [$report, $summary, , , , $plus] = $this->buildLaporanReport($tanggal, 0, 0);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::kirimRekap] build: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyusun rekap']);
        }

        // Label tanggal Indonesia.
        $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $ts = strtotime($tanggal);
        $labelTgl = $this->getHariIndo(date('l', $ts)) . ', ' . date('j', $ts) . ' ' . ($bulan[(int) date('n', $ts)] ?? '') . ' ' . date('Y', $ts);

        // Petugas piket tanggal tersebut.
        $petugas = [];
        try {
            foreach ($this->piketJadwalModel->getPiketHariIni($this->getHariIndo(date('l', $ts)), (int) $this->tp->id_tp, (int) $this->smt->id_smt) as $c) {
                if (!empty($c->nama_guru)) $petugas[] = (string) $c->nama_guru;
            }
        } catch (\Throwable $e) {
        }

        // Siswa per kelas + subtotal.
        $siswaRows = [];
        foreach (($plus['siswa']['kelas'] ?? []) as $rk) {
            $siswaRows[] = [
                'nama_kelas' => (string) ($rk->nama_kelas ?? '-'),
                'total' => (int) ($rk->total ?? 0),
                'H' => (int) ($rk->r_hadir ?? 0), 'S' => (int) ($rk->r_sakit ?? 0),
                'I' => (int) ($rk->r_izin ?? 0), 'A' => (int) ($rk->r_alpha ?? 0),
                'T' => (int) ($rk->r_telat ?? 0), 'masuk' => (int) ($rk->masuk ?? 0),
                'pulang' => (int) ($rk->pulang ?? 0),
                'tanpa_pulang' => (int) ($rk->r_tanpa_pulang ?? 0),
                'belum' => (int) ($rk->r_belum ?? 0),
            ];
        }
        $st = [];
        foreach (['total', 'H', 'S', 'I', 'A', 'T', 'masuk', 'pulang', 'tanpa_pulang', 'belum'] as $kk) {
            $map = ['H' => 'r_hadir', 'S' => 'r_sakit', 'I' => 'r_izin', 'A' => 'r_alpha', 'T' => 'r_telat',
                'masuk' => 'masuk', 'pulang' => 'pulang', 'tanpa_pulang' => 'r_tanpa_pulang',
                'belum' => 'r_belum', 'total' => 'total'];
            $st[$kk] = (int) (($plus['siswa']['total'] ?? [])[$map[$kk]] ?? 0);
        }

        // Guru tidak hadir (+ alasan). Yang hadir tak ditampilkan di pesan.
        $absen = [];
        $alasanMap = [];
        try {
            $alasanMap = $this->piketModel->getAlasanGuruMap($tanggal);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::kirimRekap] alasan: ' . $e->getMessage());
        }
        foreach (($plus['guru']['list'] ?? []) as $g) {
            if (!empty($g->jam_masuk)) continue;
            $nama = (string) ($g->nama_guru ?? 'Guru');
            $als = $alasanMap[(int) ($g->id_guru ?? 0)] ?? 'Tanpa keterangan';
            $absen[] = [
                'nama' => $nama,
                'alasan' => $als,
                'alpha' => strncasecmp(trim($als), 'alpha', 5) === 0,
            ];
        }

        $pengirim = trim((string) (($this->guru->nama_guru ?? '') !== '' ? $this->guru->nama_guru : ($this->user->username ?? '')));

        // Bolos: masuk tanpa absen pulang. Nama tampil di pesan hanya bila
        // sudah lewat jam pulang; sebelumnya masih wajar belum pulang.
        $bolosRows = [];
        $bolosResmi = false;
        try {
            $bolosResmi = $this->piketModel->sudahLewatJamPulang($tanggal);
            if ($bolosResmi) {
                foreach ($this->piketModel->getSiswaTanpaPulang($tanggal, null, $tpId, $smtId) as $b) {
                    $bolosRows[] = [
                        'nama' => (string) ($b->nama ?? 'Siswa'),
                        'kelas' => (string) ($b->nama_kelas ?? ''),
                        'jam' => substr((string) ($b->jam_masuk ?? ''), 0, 5),
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::kirimRekap] bolos: ' . $e->getMessage());
        }

        try {
            $notif = new \App\Services\NotificationService();
            if ($notif->isGrupDisabled('rekap_piket')) {
                return $this->response->setJSON(['status' => false, 'message' => 'Kedua kanal grup (TG + WA) dinonaktifkan untuk Rekap Piket di Pengaturan → Channel Notifikasi.']);
            }
            $ok = $notif->notifyRekapPiket([
                'tanggal' => $labelTgl,
                'petugas' => $petugas,
                'siswa' => $siswaRows,
                'siswa_total' => $st,
                'siswa_bolos' => $bolosRows,
                'bolos_resmi' => $bolosResmi,
                'guru_absen' => $absen,
                'pengirim' => $pengirim,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::kirimRekap] kirim: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal mengirim — periksa log aplikasi.']);
        }

        if (!$ok) {
            $err = trim((string) $notif->lastError);
            log_message('error', '[Piket::kirimRekap] gateway: ' . $err);
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal terkirim. ' . $err]);
        }

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('cron_notifikasi_log')) {
                $db->table('cron_notifikasi_log')->insert([
                    'jenis' => 'rekap_piket',
                    'periode' => $tanggal,
                    'terkirim' => 1,
                    'percobaan' => 1,
                    'catatan' => 'Manual oleh ' . ($pengirim !== '' ? $pengirim : 'piket'),
                ]);
            }
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Rekap terkirim ke grup (' . count($absen) . ' guru tidak hadir)']);
    }

    public function inputLaporan()
    {
        if (!$this->initUser())
            return redirect()->to('auth');
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = $this->getCommonData('Input Kehadiran Guru (Piket)');
        $tanggal = $this->tanggalLaporanAktif($this->request->getGet('tanggal'));
        $filterKelas = (int) ($this->request->getGet('kelas') ?? 0);
        $data['tanggal'] = $tanggal;
        $data['filter_kelas'] = $filterKelas;

        // Deep link filter temuan dari dashboard piket (?issue=...)
        $issue = (string) ($this->request->getGet('issue') ?? '');
        $data['issue_filter'] = in_array($issue, ['tanpa_jurnal', 'tanpa_scan', 'pengganti', 'mismatch', 'belum', 'absen_siswa', 'perlu_validasi', 'jurnal_valid', 'jurnal_perlu_perbaikan'], true)
            ? $issue : '';

        // Guru piket yang ikut mengajar tidak menilai jurnalnya sendiri — sama
        // seperti larangan di validasiJurnal(). UI memakai angka ini untuk
        // menyembunyikan tombolnya, bukan hanya mengandalkan penolakan server.
        $data['val_guru_sendiri'] = ($this->isKepsek() || $this->isAdminPiket() || empty($this->guru->id_guru))
            ? 0
            : (int) $this->guru->id_guru;

        // Assignments & Names (satu sumber via helper)
        [$teacherMap, $guruNames] = $this->resolveTeacherMap();

        // Schedule
        $idHari = (int) date('N', strtotime($tanggal));
        $schedules = $this->elearningModel->getJadwalHarian($this->tp->id_tp, $this->smt->id_smt, $idHari);

        // Manual Input — kunci per (kelas, jam, mapel); '*' = fallback baris lama
        // sebelum migrasi unique per mapel.
        $manuals = $this->piketModel->getPresensiGuru($tanggal);
        $manualMap = [];
        foreach ($manuals as $m) {
            $manualMap[$m->id_kelas][$m->jam_ke][$m->id_mapel ?? '*'] = $m;
            $manualMap[$m->id_kelas][$m->jam_ke]['*'] ??= $m;
        }

        // Sumber monitoring KBM: jurnal mengajar + check-in guru.
        // Kehadiran siswa per jam hanya bisa diturunkan dari jurnal
        // (kolom siswa_absent) karena presensi siswa tidak disimpan per jam.
        [$journalMap, $jurnalInfo, $duplikatJurnal, $journalByJadwal] = $this->loadJurnalMap($tanggal);
        $absensiMap = $this->loadAbsensiMap($tanggal);
        // Nama penanda tangan validasi, satu query untuk semua jurnal hari itu.
        $namaValidator = $this->loadValidatorNames($jurnalInfo);

        // Summary Counters
        $statSummary = [
            'total_slot' => 0,
            'sudah_dicek' => 0,
            'belum_dicek' => 0,
            'hadir' => 0,
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
            'jurnal_isi' => 0,
            'jurnal_kosong' => 0,
            'pengganti' => 0,
            'hadir_tanpa_jurnal' => 0,
            'tanpa_scan' => 0,
            'scan_guru' => 0,
            'siswa_absen_jurnal' => 0,
            'siswa_hadir_jurnal' => 0,
            'duplikat_jurnal' => $duplikatJurnal,
            'perlu_validasi' => 0,
            'jurnal_valid' => 0,
            'jurnal_perlu_perbaikan' => 0,
        ];
        $scanGuruIds = [];

        $report = [];
        $classList = $this->elearningModel->getAllKelasSort($this->tp->id_tp, $this->smt->id_smt);
        $data['kelas_list'] = $classList;

        foreach ($classList as $idKls => $namaKls) {
            if (!isset($schedules[$idKls]))
                continue;

            // Ringkasan mengikuti filter kelas yang tampil (dulu dihitung
            // sebelum filter sehingga angka tak cocok dengan daftar).
            if ($filterKelas > 0 && (int) $idKls !== $filterKelas) {
                continue;
            }

            // Pembagi kehadiran per jam: siswa AKTIF di kelas ini.
            $jumlahSiswa = 0;
            try {
                $jumlahSiswa = $this->piketModel->countSiswaAktifKelas((int) $idKls, (int) $this->tp->id_tp, (int) $this->smt->id_smt);
            } catch (\Throwable $e) {
                log_message('error', '[Piket::inputLaporan] countSiswaAktifKelas: ' . $e->getMessage());
            }

            $slots = [];
            foreach ($schedules[$idKls] as $s) {
                $guruId = $teacherMap[$idKls][$s->id_mapel] ?? null;
                // Slot tanpa guru pengampu belum "di-set" dan tidak bisa
                // diisi piket. Jangan tampilkan sebagai baris jurnal.
                if (empty($guruId)) {
                    continue;
                }
                $manualEntry = $manualMap[$idKls][$s->jam_ke][$s->id_mapel]
                    ?? $manualMap[$idKls][$s->jam_ke]['*']
                    ?? null;

                // Jurnal hanya ditampilkan bila sudah di-set ke slot jadwal
                // ini. Jangan fallback ke kelas/mapel/jam: jurnal lama yang
                // belum tertaut bisa muncul di slot yang bukan miliknya.
                $idJadwalSlot = (int) ($s->id_jadwal ?? 0);
                $jurnalId = $idJadwalSlot > 0 ? (int) ($journalByJadwal[$idJadwalSlot] ?? 0) : 0;
                $jurnalRow = $jurnalId > 0 ? ($jurnalInfo[$jurnalId] ?? null) : null;
                $jurnalGuruId = (int) ($jurnalRow->id_guru ?? 0);

                // Status validasi dinormalkan: jurnal warisan (kolom baru
                // belum terisi) dianggap PENDING, bukan ditampilkan kosong.
                $validasiStatus = '';
                if ($jurnalRow) {
                    $validasiStatus = (string) ($jurnalRow->validasi_status ?? '');
                    if (!in_array($validasiStatus, JurnalGuruModel::validasiStatusList(), true)) {
                        $validasiStatus = JurnalGuruModel::VALIDASI_PENDING;
                    }
                }
                $pengganti = $jurnalRow && $guruId && $jurnalGuruId > 0 && $jurnalGuruId !== (int) $guruId;

                $absensiGuru = $guruId ? ($absensiMap[(int) $guruId] ?? null) : null;
                $absensiJurnal = $pengganti ? ($absensiMap[$jurnalGuruId] ?? null) : null;

                $scanMasuk = !empty($absensiGuru->jam_masuk);
                $penggantiScanMasuk = $pengganti && !empty($absensiJurnal->jam_masuk);

                $siswaAbsen = $jurnalRow
                    ? $this->piketModel->infoAbsenJurnal($jurnalRow)
                    : ['jumlah' => 0, 'nama' => [], 'ada' => false];
                $siswaHadir = $jurnalRow ? max(0, $jumlahSiswa - (int) $siswaAbsen['jumlah']) : null;

                $statSummary['total_slot']++;
                if ($manualEntry) {
                    $statSummary['sudah_dicek']++;
                    if ($manualEntry->status === 'H') $statSummary['hadir']++;
                    elseif ($manualEntry->status === 'S') $statSummary['sakit']++;
                    elseif ($manualEntry->status === 'I') $statSummary['izin']++;
                    elseif ($manualEntry->status === 'A') $statSummary['alpa']++;
                } else {
                    $statSummary['belum_dicek']++;
                }
                if ($jurnalRow) {
                    $statSummary['jurnal_isi']++;
                    $statSummary['siswa_absen_jurnal'] += (int) $siswaAbsen['jumlah'];
                    $statSummary['siswa_hadir_jurnal'] += (int) $siswaHadir;
                    if ($pengganti) $statSummary['pengganti']++;
                    if (!($pengganti ? $penggantiScanMasuk : $scanMasuk)) $statSummary['tanpa_scan']++;
                    if ($validasiStatus === JurnalGuruModel::VALIDASI_PENDING) $statSummary['perlu_validasi']++;
                    elseif ($validasiStatus === JurnalGuruModel::VALIDASI_APPROVED) $statSummary['jurnal_valid']++;
                    elseif ($validasiStatus === JurnalGuruModel::VALIDASI_REJECTED) $statSummary['jurnal_perlu_perbaikan']++;
                } else {
                    $statSummary['jurnal_kosong']++;
                    if ($manualEntry && $manualEntry->status === 'H') $statSummary['hadir_tanpa_jurnal']++;
                }
                if ($scanMasuk && $guruId) $scanGuruIds[(int) $guruId] = true;

                $slots[] = [
                    'jam_ke' => $s->jam_ke,
                    'id_mapel' => $s->id_mapel,
                    'nama_mapel' => $s->nama_mapel,
                    'id_guru' => $guruId,
                    'nama_guru' => $guruId ? ($guruNames[$guruId] ?? 'Guru Tidak Ditemukan') : 'Belum Diset',
                    'manual' => $manualEntry,
                    'jurnal' => $jurnalRow,
                    'jurnal_id' => $jurnalRow ? (int) $jurnalId : null,
                    // Pemilik jurnal — BEDA dari 'id_guru' (guru yang terjadwal),
                    // dipakai untuk melarang piket menilai jurnalnya sendiri.
                    'jurnal_id_guru' => $jurnalGuruId,
                    'jurnal_guru_nama' => $jurnalRow ? trim((string) ($jurnalRow->nama_guru ?? '')) : '',
                    'pengganti' => $pengganti,
                    'pengganti_scan_masuk' => $penggantiScanMasuk,
                    'pengganti_scan_jam' => $penggantiScanMasuk ? substr((string) $absensiJurnal->jam_masuk, 0, 5) : '',
                    'materi' => $jurnalRow ? trim((string) ($jurnalRow->materi_pokok ?? '')) : '',
                    'siswa_total' => $jumlahSiswa,
                    'siswa_hadir' => $siswaHadir,
                    'siswa_absen_jumlah' => (int) $siswaAbsen['jumlah'],
                    'siswa_absen_nama' => $siswaAbsen['nama'],
                    'absensi' => $absensiGuru,
                    'scan_masuk' => $scanMasuk,
                    'scan_jam' => $scanMasuk ? substr((string) $absensiGuru->jam_masuk, 0, 5) : '',
                    // Piket menyatakan Hadir padahal jurnal kosong, atau sebaliknya.
                    'piket_mismatch' => $manualEntry
                        ? (($manualEntry->status === 'H' && !$jurnalRow) || ($manualEntry->status !== 'H' && $jurnalRow))
                        : false,
                    'validasi_status'  => $validasiStatus,
                    'validasi_catatan' => $jurnalRow ? trim((string) ($jurnalRow->validasi_catatan ?? '')) : '',
                    'validasi_pada'    => $jurnalRow ? (string) ($jurnalRow->validasi_pada ?? '') : '',
                    'validasi_nama'    => $jurnalRow ? ($namaValidator[(int) ($jurnalRow->validasi_oleh ?? 0)] ?? '') : '',
                ];
            }

            if (!empty($slots)) {
                $report[$idKls] = [
                    'nama_kelas' => $namaKls,
                    'jumlah_siswa' => $jumlahSiswa,
                    'slots' => $slots,
                ];
            }
        }

        $statSummary['scan_guru'] = count($scanGuruIds);
        $data['summary'] = $statSummary;
        $data['report'] = $report;
        return view('members/guru/piket_input_laporan_mobile', $data);
    }

    public function saveLaporanGuru()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $idKelas = (int) $this->request->getPost('id_kelas');
        $jamKe = (int) $this->request->getPost('jam_ke');
        $idMapel = (int) $this->request->getPost('id_mapel');
        $status = trim((string) $this->request->getPost('status'));
        $keterangan = mb_substr(trim((string) $this->request->getPost('keterangan')), 0, 500);
        if ($keterangan === '') $keterangan = null;

        if (!$this->validTanggalLaporan($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid (format YYYY-MM-DD, bukan masa depan)']);
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Edit historis dibatasi H-7. Hubungi admin untuk koreksi lebih lama.']);
        }
        if (($libur = $this->isHariLiburPiket($tanggal)) !== null) {
            return $this->response->setJSON(['status' => false, 'message' => $libur]);
        }
        if ($idKelas <= 0 || $jamKe <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data kelas dan jam pelajaran harus valid']);
        }
        $allowedStatus = ['H', 'S', 'I', 'A'];
        if (!in_array($status, $allowedStatus, true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Status tidak valid']);
        }

        $idHari = (int) date('N', strtotime($tanggal));
        $schedules = $this->elearningModel->getJadwalHarian($this->tp->id_tp, $this->smt->id_smt, $idHari);
        $slotFound = null;
        if (isset($schedules[$idKelas])) {
            foreach ($schedules[$idKelas] as $s) {
                if ((int) $s->jam_ke === $jamKe) {
                    $slotFound = $s;
                    break;
                }
            }
        }

        // Slot wajib ada di jadwal — cegah baris yatim untuk jam tak terjadwal
        if (!$slotFound) {
            return $this->response->setJSON(['status' => false, 'message' => 'Slot jam ke-' . $jamKe . ' tidak ada di jadwal kelas ini']);
        }

        // Mapel & guru bersifat otoritatif dari jadwal + penugasan (abaikan kiriman client bila beda)
        $idMapelSlot = (int) $slotFound->id_mapel;
        if ($idMapel > 0 && $idMapel !== $idMapelSlot) {
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel tidak sesuai jadwal jam ini']);
        }
        $idMapel = $idMapelSlot;

        [$teacherMap] = $this->resolveTeacherMap();
        $idGuru = (int) ($teacherMap[$idKelas][$idMapel] ?? 0);
        if ($idGuru <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru pengampu mapel ini belum diset. Hubungi admin/kurikulum.']);
        }

        $pencatat = (int) ($this->user->id ?? 0);
        $data = [
            'tanggal' => $tanggal,
            'id_kelas' => $idKelas,
            'jam_ke' => $jamKe,
            'id_mapel' => $idMapel,
            'id_guru' => $idGuru,
            'status' => $status,
            'keterangan' => $keterangan,
            'is_auto' => 0,
            'updated_by' => $pencatat > 0 ? $pencatat : null,
        ];

        try {
            if ($this->piketModel->savePresensiGuru($data)) {
                return $this->response->setJSON(['status' => true, 'message' => 'Data kehadiran berhasil disimpan']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveLaporanGuru] ' . $e->getMessage());
        }
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan data ke database']);
    }

    /**
     * Validasi isian jurnal mengajar — guru piket hari itu, kepala sekolah,
     * atau admin.
     *
     * Menolak jurnal berarti "perbaiki isinya", bukan hukuman: catatan
     * perbaikan wajib diisi, hasilnya diarsip ke audit trail, dan guru
     * pemilik jurnal diberitahu lewat kanal personalnya (bukan grup, supaya
     * nilai satu guru tidak disiarkan ke seluruh sekolah).
     */
    public function validasiJurnal()
    {
        if (!$this->initUser())
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir, silakan login kembali']);
        if ($block = $this->requireOnDuty()) return $block;

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['status' => false, 'message' => 'Metode tidak diizinkan']);
        }

        $idJurnal = (int) $this->request->getPost('id_jurnal');
        $aksi     = strtolower(trim((string) $this->request->getPost('aksi')));
        $catatan  = mb_substr(trim((string) $this->request->getPost('catatan')), 0, 500);

        $statusBaru = [
            'setujui'  => JurnalGuruModel::VALIDASI_APPROVED,
            'tolak'    => JurnalGuruModel::VALIDASI_REJECTED,
            'batalkan' => JurnalGuruModel::VALIDASI_PENDING,
        ][$aksi] ?? null;

        if ($statusBaru === null) {
            return $this->gagalValidasi('Aksi tidak dikenal. Gunakan setujui, tolak, atau batalkan.');
        }
        if ($idJurnal <= 0) {
            return $this->gagalValidasi('Jurnal tidak ditemukan.');
        }
        if ($aksi === 'tolak' && $catatan === '') {
            return $this->gagalValidasi('Tulis dulu catatan perbaikannya — guru perlu tahu apa yang harus diubah.');
        }

        $jurnalModel = new JurnalGuruModel();
        $jurnalModel->initialize();
        $jurnal = $this->piketModel->getJurnalById($idJurnal);
        if (!$jurnal) {
            return $this->gagalValidasi('Jurnal tidak ditemukan.');
        }

        $tanggal = (string) ($jurnal->tanggal ?? '');
        if (!$this->validTanggalLaporan($tanggal)) {
            return $this->gagalValidasi('Tanggal jurnal tidak valid.');
        }
        if (!$this->bolehEditTanggal($tanggal)) {
            return $this->gagalValidasi('Validasi historis dibatasi H-7. Hubungi admin untuk koreksi lebih lama.');
        }

        // Guru piket yang ikut mengajar tidak menilai jurnalnya sendiri —
        // sama seperti larangan menyetujui izin/dinas milik sendiri.
        $idGuruPemilik = (int) ($jurnal->id_guru ?? 0);
        if (!$this->isAdminPiket() && $this->guru && $idGuruPemilik === (int) ($this->guru->id_guru ?? 0)) {
            return $this->gagalValidasi('Anda tidak dapat memvalidasi jurnal Anda sendiri.');
        }

        $lama = (string) ($jurnal->validasi_status ?? '');
        if (!in_array($lama, JurnalGuruModel::validasiStatusList(), true)) {
            $lama = JurnalGuruModel::VALIDASI_PENDING;
        }
        if ($lama === $statusBaru) {
            return $this->response->setJSON([
                'status'          => true,
                'message'         => $this->labelValidasi($statusBaru) . ' sudah tercatat.',
                'validasi_status' => $statusBaru,
            ]);
        }

        $actorId = (int) ($this->user->id ?? 0);
        $catatanSimpan = $catatan !== '' ? $catatan : null;

        try {
            $hasil = $jurnalModel->setValidasi($idJurnal, $statusBaru, $actorId, $catatanSimpan, [$lama]);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::validasiJurnal] ' . $e->getMessage());
            return $this->gagalValidasi('Validasi gagal tersimpan. Coba lagi sebentar.');
        }

        if ($hasil['kode'] === 'hilang') {
            return $this->gagalValidasi('Jurnal tidak ditemukan lagi.');
        }
        if ($hasil['kode'] === 'berubah') {
            return $this->gagalValidasi(
                'Status jurnal baru saja diubah validator lain. Muat ulang halaman untuk melihat kondisi terakhir.',
                ['validasi_status' => $hasil['status']]
            );
        }

        $labelKelasMapel = trim((string) ($jurnal->nama_kelas ?? '')) . ' · ' . trim((string) ($jurnal->nama_mapel ?? ''));

        try {
            $idUserGuru = $this->idUserGuru($idGuruPemilik);
            (new AuditLogModel())->log(
                'guru_jurnal',
                $idJurnal,
                'JURNAL_' . strtoupper($aksi),
                $idUserGuru ?: null,
                $actorId,
                ['validasi_status' => $lama],
                ['validasi_status' => $statusBaru, 'validasi_catatan' => $catatanSimpan],
                'Validasi jurnal ' . $labelKelasMapel . ' (' . $tanggal . ', jam ke-' . $jurnal->jam_ke . ')'
            );
        } catch (\Throwable $e) {
            log_message('error', '[Piket::validasiJurnal] audit gagal: ' . $e->getMessage());
        }

        // Beri tahu guru pemiliknya. Notifikasi tidak boleh membatalkan
        // keputusan yang sudah tersimpan, jadi kegagalan hanya dicatat.
        if ($aksi !== 'batalkan') {
            try {
                $idUserGuru = $this->idUserGuru($idGuruPemilik);
                if ($idUserGuru > 0) {
                    $pengesah = $this->isKepsek() ? 'Kepala Sekolah' : 'Guru Piket';
                    $pesan = 'Jurnal Anda pada ' . date('d/m/Y', strtotime($tanggal))
                        . ', jam ke-' . $jurnal->jam_ke . ' (' . $labelKelasMapel . '): '
                        . ($aksi === 'setujui' ? 'valid / disetujui' : 'perlu perbaikan')
                        . ' menurut ' . $pengesah . '.';
                    if ($catatanSimpan !== null) {
                        $pesan .= ' Catatan: ' . $catatanSimpan;
                    }
                    (new NotificationService())->notifyUser($idUserGuru, $pesan, '', false);
                }
            } catch (\Throwable $e) {
                log_message('error', '[Piket::validasiJurnal] notifikasi gagal: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status'           => true,
            'message'          => $this->labelValidasi($statusBaru)
                . ($catatanSimpan !== null ? ' — ' . $catatanSimpan : ''),
            'validasi_status'  => $statusBaru,
            'validasi_catatan' => $catatanSimpan,
            'validasi_pada'    => date('Y-m-d H:i:s'),
            'validasi_nama'    => $this->namaAktor(),
        ]);
    }

    private function gagalValidasi(string $pesan, array $extra = [])
    {
        return $this->response->setJSON(array_merge(['status' => false, 'message' => $pesan], $extra));
    }

    private function labelValidasi(string $status): string
    {
        return match ($status) {
            JurnalGuruModel::VALIDASI_APPROVED => 'Jurnal tervalidasi',
            JurnalGuruModel::VALIDASI_REJECTED => 'Jurnal perlu perbaikan',
            default                            => 'Validasi dibatalkan',
        };
    }

    /** Nama penanda tangan untuk balasan AJAX (users tidak punya kolom `name`). */
    private function namaAktor(): string
    {
        $nama = trim((string) ($this->session->get('nama') ?? ''));
        if ($nama !== '') {
            return $nama;
        }
        return trim((string) ($this->user->first_name ?? '') . ' ' . (string) ($this->user->last_name ?? ''));
    }

    /** users.id milik seorang guru (0 bila tidak bertaut akun). */
    private function idUserGuru(int $idGuru): int
    {
        if ($idGuru <= 0) return 0;
        try {
            $row = \Config\Database::connect()->table('master_guru')
                ->select('id_user')->where('id_guru', $idGuru)->get()->getRow();
            return (int) ($row->id_user ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function autofillPresensiGuru()
    {
        if (!$this->initUser()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
            }
            return redirect()->to('auth');
        }
        if ($block = $this->requireOnDuty()) return $block;
        if (!$this->tp || !$this->smt) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
            }
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $tanggal = $this->tanggalLaporanAktif(
            $this->request->getPost('tanggal') ?? $this->request->getGet('tanggal')
        );
        if (!$this->bolehEditTanggal($tanggal)) {
            $msg = 'Edit historis dibatasi H-7. Hubungi admin untuk koreksi lebih lama.';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }
        if (($libur = $this->isHariLiburPiket($tanggal)) !== null) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => $libur]);
            }
            return redirect()->back()->with('error', $libur);
        }
        $filterKelas = (int) ($this->request->getPost('kelas') ?? $this->request->getGet('kelas') ?? 0);
        $filterMapel = (int) ($this->request->getPost('mapel') ?? $this->request->getGet('mapel') ?? 0);

        $idHari = (int) date('N', strtotime($tanggal));
        $schedules = $this->elearningModel->getJadwalHarian((int) $this->tp->id_tp, (int) $this->smt->id_smt, $idHari);

        [$teacherMapAuto] = $this->resolveTeacherMap();
        $pencatatAuto = (int) ($this->user->id ?? 0);
        $scheduleSlotMap = [];
        foreach ($schedules as $idKls => $slots) {
            foreach ($slots as $s) {
                // Kunci per (jam, mapel) agar jadwal paralel tak saling menimpa
                $scheduleSlotMap[(int) $idKls][(int) $s->jam_ke][(int) $s->id_mapel] = $s;
                $scheduleSlotMap[(int) $idKls][(int) $s->jam_ke]['*'] ??= $s;
            }
        }

        $journals = $this->piketModel->getJurnalHarian($tanggal, [
            'id_tp' => (int) $this->tp->id_tp,
            'id_smt' => (int) $this->smt->id_smt,
            'id_kelas' => $filterKelas,
            'id_mapel' => $filterMapel,
        ]);

        $absensiRows = $this->piketModel->getAbsensiGuruHarian($tanggal);
        $absensiMap = [];
        foreach ($absensiRows as $a) {
            if (!empty($a->jam_masuk)) {
                $absensiMap[(int) $a->id_guru] = $a;
            }
        }

        $existingMap = $this->piketModel->getPresensiGuruMap($tanggal);

        $result = [
            'inserted' => 0,
            'updated' => 0,
            'skipped_manual' => 0,
            'skipped_no_checkin' => 0,
            'skipped_no_schedule' => 0,
            'skipped_mismatch_schedule' => 0,
        ];

        foreach ($journals as $j) {
            $idKelas = (int) ($j->id_kelas ?? 0);
            $jamKe = (int) ($j->jam_ke ?? 0);
            $idMapel = (int) ($j->id_mapel ?? 0);
            $idGuru = (int) ($j->id_guru ?? 0);
            if ($idKelas <= 0 || $jamKe <= 0 || $idMapel <= 0 || $idGuru <= 0) continue;
            if ($filterKelas > 0 && $idKelas !== $filterKelas) continue;
            if ($filterMapel > 0 && $idMapel !== $filterMapel) continue;

            if (!isset($absensiMap[$idGuru])) {
                $result['skipped_no_checkin']++;
                continue;
            }

            $slot = $scheduleSlotMap[$idKelas][$jamKe][$idMapel]
                ?? $scheduleSlotMap[$idKelas][$jamKe]['*']
                ?? null;
            if (!$slot) {
                $result['skipped_no_schedule']++;
                continue;
            }
            if ((int) $slot->id_mapel !== $idMapel) {
                $result['skipped_mismatch_schedule']++;
                continue;
            }

            $existing = $existingMap[$idKelas][$jamKe][$idMapel]
                ?? $existingMap[$idKelas][$jamKe]['*']
                ?? null;
            if ($existing && (int) ($existing->is_auto ?? 0) === 0) {
                $result['skipped_manual']++;
                continue;
            }

            // Atribusi: bila penulis jurnal ≠ pengampu jadwal, catat sebagai
            // pengganti agar tak dikira pengampu yang hadir.
            $pengampu = (int) ($teacherMapAuto[$idKelas][$idMapel] ?? 0);
            $ket = 'Auto: jurnal + absensi masuk';
            if ($pengampu > 0 && $pengampu !== $idGuru) {
                $ket = 'Auto: jurnal pengganti + absensi masuk';
            }

            $payload = [
                'tanggal' => $tanggal,
                'id_kelas' => $idKelas,
                'jam_ke' => $jamKe,
                'id_mapel' => $idMapel,
                'id_guru' => $idGuru,
                'status' => 'H',
                'keterangan' => $ket,
                'is_auto' => 1,
                'updated_by' => $pencatatAuto > 0 ? $pencatatAuto : null,
            ];

            $ok = $this->piketModel->savePresensiGuru($payload);
            if ($ok) {
                if ($existing) $result['updated']++;
                else $result['inserted']++;
                $existingMap[$idKelas][$jamKe][$idMapel] = (object) array_merge((array) ($existing ?? []), $payload);
                $existingMap[$idKelas][$jamKe]['*'] ??= $existingMap[$idKelas][$jamKe][$idMapel];
            }
        }

        $message = "Auto-fill selesai. Insert: {$result['inserted']}, Update: {$result['updated']}, Skip manual: {$result['skipped_manual']}.";
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => true, 'message' => $message, 'result' => $result]);
        }

        return redirect()->to(site_url('guru/piket/laporan') . '?tanggal=' . urlencode($tanggal) . '&kelas=' . $filterKelas . '&mapel=' . $filterMapel)
            ->with('success', $message);
    }

    protected function getTpSmt(): object
    {
        return (object) [
            'tp' => $this->dashboardModel->getTahunActive(),
            'smt' => $this->dashboardModel->getSemesterActive()
        ];
    }

    private function getHariIndo($day)
    {
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        return $days[$day] ?? $day;
    }
}
