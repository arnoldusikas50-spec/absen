<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\PiketJadwalModel;
use App\Models\PiketModel; // To see monitoring stats
use App\Services\HariSekolahService;

class Piket extends BaseController
{
    protected $dashboardModel;
    protected $piketJadwalModel;
    protected $piketModel;
    protected $tp;
    protected $smt;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->piketJadwalModel = new PiketJadwalModel();
        $this->piketJadwalModel->initialize();
        $this->piketModel = new PiketModel();
        $this->piketModel->initialize();
    }

    private function initData()
    {
        $tpSmt = $this->getTpSmt();

        if (!$tpSmt->tp) {
            $this->tp = (object) ['id_tp' => 0, 'tahun' => '-'];
        } else {
            $this->tp = $tpSmt->tp;
        }

        if (!$tpSmt->smt) {
            $this->smt = (object) ['id_smt' => 0, 'smt' => '-'];
        } else {
            $this->smt = $tpSmt->smt;
        }
    }


    private function getCommonData($title)
    {
        $this->initData();
        $setting = $this->dashboardModel->getSetting();
        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_sekolah' => 'absensi by ikasmedia', 'logo' => null];

        return [
            'judul' => $title,
            'user' => $this->getUser(),
            'tp_active' => $this->tp,
            'smt_active' => $this->smt,
            'setting' => $setting
        ];
    }

    public function index()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        try {
            // View Jadwal & Monitoring Ringkas
            $data = $this->getCommonData('Manajemen Piket');

            $hari_arr = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            // Check if TP/SMT is valid
            if ($this->tp->id_tp == 0) {
                return redirect()->to('datatahun')->with('error', 'Tahun Pelajaran belum diset aktif. Aktifkan dulu TP/SMT.');
            }

            $jadwal_raw = $this->piketJadwalModel->getJadwalByTpSmt($this->tp->id_tp, $this->smt->id_smt);

            // Group by Hari
            $jadwal = [];
            foreach ($hari_arr as $h) {
                $jadwal[$h] = [];
            }
            if ($jadwal_raw) {
                foreach ($jadwal_raw as $row) {
                    if (isset($jadwal[$row->hari])) {
                        $jadwal[$row->hari][] = $row;
                    }
                }
            }

            $data['jadwal'] = $jadwal;
            $data['hari_list'] = $hari_arr;

            // Stats Hari Ini
            $hariService = new HariSekolahService();
            $statusHari = $hariService->status();
            $isHariSekolah = $statusHari['status'] === HariSekolahService::AKTIF;
            $hari_ini_indo = $statusHari['nama_hari'];
            $rosterHari = $hari_ini_indo;
            $rosterTanggal = $hariService->tanggalPanjang();
            if (! $isHariSekolah) {
                $berikut = $hariService->hariSekolahBerikutnya();
                if ($berikut !== null) {
                    $rosterHari = $hariService->namaHari($berikut);
                    $rosterTanggal = $hariService->tanggalPanjang($berikut);
                }
            }
            $data['is_hari_sekolah'] = $isHariSekolah;
            $data['libur_keterangan'] = $statusHari['keterangan'];
            $data['piket_hari_label'] = $rosterHari;
            $data['piket_tanggal_label'] = $rosterTanggal;
            $data['hari_ini_indo'] = $hari_ini_indo;
            $data['piket_hari_ini'] = $this->piketJadwalModel->getPiketHariIni($rosterHari, $this->tp->id_tp, $this->smt->id_smt);

            $tanggal = date('Y-m-d');
            try {
                $data['stats_hari_ini'] = $this->piketModel->getStats($tanggal); // Reuse existing model stats logic
            } catch (\Throwable $e) {
                log_message('error', '[Piket::index] getStats: ' . $e->getMessage());
                $data['stats_hari_ini'] = ['tamu' => 0, 'kejadian' => 0, 'siswa_absen' => 0];
            }

            // Get All Guru for Modal Add (exclude Kepsek)
            $db = \Config\Database::connect();
            $kepsekIds = $this->getKepsekGuruIds($db, $data['setting']);
            $guruBuilder = $db->table('master_guru')->select('id_guru, nama_guru');
            if ($db->fieldExists('deleted_at', 'master_guru')) {
                $guruBuilder->where('deleted_at IS NULL');
            }
            if (!empty($kepsekIds)) {
                $guruBuilder->whereNotIn('id_guru', $kepsekIds);
            }
            $data['guru_list'] = $guruBuilder->orderBy('nama_guru')->get()->getResult();
            $data['kepsek_ids'] = $kepsekIds;

            return view('admin/piket/jadwal', $data);

        } catch (\Throwable $e) {
            log_message('error', '[Piket::index] ' . $e->getMessage());
            return redirect()->to('dashboard')->with('error', 'Gagal memuat jadwal piket — periksa log aplikasi.');
        }
    }

    public function saveJadwal()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $this->getCommonData('Save'); // Init TP/SMT
        if ($this->tp->id_tp == 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran belum aktif']);
        }

        $hari = trim((string) $this->request->getPost('hari'));
        $id_guru = (int) $this->request->getPost('id_guru');
        $keterangan = mb_substr(trim((string) $this->request->getPost('keterangan')), 0, 500);

        $validHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if (!in_array($hari, $validHari, true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Hari tidak valid']);
        }
        if ($id_guru <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru wajib dipilih']);
        }

        $db = \Config\Database::connect();
        $setting = $this->dashboardModel->getSetting();
        $kepsekIds = $this->getKepsekGuruIds($db, $setting);
        if (in_array($id_guru, $kepsekIds, true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kepala Sekolah tidak dapat ditugaskan sebagai petugas piket']);
        }

        if (!$db->table('master_guru')->where('id_guru', $id_guru)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data guru tidak ditemukan']);
        }

        // Cegah duplikat: guru yang sama di hari yang sama pada TP/SMT aktif
        $exists = $db->table('piket_jadwal')
            ->where('id_tp', $this->tp->id_tp)
            ->where('id_smt', $this->smt->id_smt)
            ->where('hari', $hari)
            ->where('id_guru', $id_guru)
            ->countAllResults();
        if ($exists > 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Guru tersebut sudah terjadwal piket hari ' . $hari]);
        }

        $insertData = [
            'id_tp' => $this->tp->id_tp,
            'id_smt' => $this->smt->id_smt,
            'hari' => $hari,
            'id_guru' => $id_guru,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
        ];

        try {
            $this->piketJadwalModel->insert($insertData);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::saveJadwal] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan jadwal']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Guru piket ditambahkan']);
    }

    public function deleteJadwal()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak valid']);
        }
        if (!$this->piketJadwalModel->find($id)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jadwal tidak ditemukan']);
        }
        try {
            $this->piketJadwalModel->delete($id);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::deleteJadwal] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus jadwal']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Jadwal dihapus']);
    }

    public function monitoring()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        // Detailed monitoring view for admin
        $data = $this->getCommonData('Monitoring Piket');

        $tanggal = $this->request->getGet('tanggal') ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $tanggal) || $tanggal > date('Y-m-d')) {
            $tanggal = date('Y-m-d');
        }
        $data['tanggal'] = $tanggal;

        // Re-use logic from PiketModel
        $data['tamu'] = $this->piketModel->getTamu($tanggal);
        $data['kejadian'] = $this->piketModel->getKejadian($tanggal, $data['tp_active']->id_tp, $data['smt_active']->id_smt);

        // For absences, maybe just count or show list if critical
        // Let's show summary of today's absences count per class.
        // H/T = hadir (selaras dengan PiketModel::konflikKehadiran), jadi
        // rekap ketidakhadiran hanya menghitung S/I/A.
        $db = \Config\Database::connect();
        $rekap_absen = [];
        $sudah_direkap = false;
        if ($db->tableExists('piket_absensi')) {
            try {
                $sudah_direkap = $db->table('piket_absensi')
                    ->where('tanggal', $tanggal)
                    ->countAllResults() > 0;
                $rekap_absen = $db->table('piket_absensi p')
                    ->select('k.nama_kelas, count(p.id) as total_absen')
                    ->join('master_kelas k', 'k.id_kelas = p.id_kelas')
                    ->where('p.tanggal', $tanggal)
                    ->whereNotIn('p.status', ['H', 'T'])
                    ->groupBy(['p.id_kelas', 'k.nama_kelas'])
                    ->get()->getResult();
            } catch (\Throwable $e) {
                log_message('error', '[Piket::monitoring] rekap_absen: ' . $e->getMessage());
            }
        }

        $data['rekap_absen'] = $rekap_absen;
        $data['sudah_direkap'] = $sudah_direkap;
        try {
            $data['divergensi'] = $this->piketModel->getDivergensi($tanggal, null, 50);
        } catch (\Throwable $e) {
            $data['divergensi'] = [];
        }

        // Jurnal KBM + kehadiran guru (paritas dengan monitoring piket guru).
        // Filter TP/SMT hanya untuk hari ini; tanggal lama tampil apa adanya.
        $isToday = ($tanggal === date('Y-m-d'));
        $tpF = ($isToday && !empty($data['tp_active']->id_tp)) ? (int) $data['tp_active']->id_tp : 0;
        $smtF = ($isToday && !empty($data['smt_active']->id_smt)) ? (int) $data['smt_active']->id_smt : 0;
        try {
            $data['jurnal'] = $this->piketModel->getJurnalHarian($tanggal, ['id_tp' => $tpF, 'id_smt' => $smtF]);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::monitoring] jurnal: ' . $e->getMessage());
            $data['jurnal'] = [];
        }
        try {
            $data['guru_absensi'] = $this->piketModel->getAllGuruWithAbsensi($tanggal);
        } catch (\Throwable $e) {
            log_message('error', '[Piket::monitoring] guru_absensi: ' . $e->getMessage());
            $data['guru_absensi'] = [];
        }

        return view('admin/piket/monitoring', $data);
    }

    /**
     * Resolusi satu konflik divergensi oleh admin. Arah mengikuti status
     * piket: S/I/A piket = patokan → formal yang diselaraskan (jam scan
     * dipertahankan); H/T → piket yang mengikuti sistem.
     * Admin = override: tanpa batas H-7 / blokir libur (tanggung jawab admin).
     * POST: tanggal (Y-m-d, tak boleh future), id_siswa.
     */
    public function selarasDivergensi()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $tanggal = trim((string) $this->request->getPost('tanggal'));
        $idSiswa = (int) $this->request->getPost('id_siswa');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || $tanggal > date('Y-m-d') || $idSiswa <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak valid']);
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
            // S/I/A piket = patokan → formal yang mengikuti.
            if (in_array($piketSt, ['S', 'I', 'A'], true)) {
                $targetFormal = \App\Models\PiketModel::piketKeFormal($piketSt);
                if ($targetFormal === null) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Status piket tidak valid']);
                }
                if ($formal !== null && strtoupper(trim((string) ($formal->status ?? ''))) === $targetFormal) {
                    return $this->response->setJSON(['status' => true, 'message' => 'Sudah selaras']);
                }
                $user = $this->getUser();
                $sync = $this->piketModel->syncPiketKeFormal([[
                    'tanggal' => $tanggal,
                    'id_kelas' => (int) $piket->id_kelas,
                    'id_siswa' => $idSiswa,
                    'status' => $piketSt,
                    'updated_by' => $user ? (int) $user->id : null,
                ]], $tanggal);
                if ((int) ($sync['dibuat'] ?? 0) + (int) ($sync['diupdate'] ?? 0) <= 0) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyelaraskan']);
                }
                return $this->response->setJSON(['status' => true, 'message' => 'Diselaraskan mengikuti piket (' . $targetFormal . ')']);
            }
            $target = \App\Models\PiketModel::formalKePiket(
                $formal->status ?? null,
                !empty($formal->jam_masuk)
            );
            if ($target === null) {
                return $this->response->setJSON(['status' => false, 'message' => 'Sistem belum mencatat siswa ini — selaraskan via halaman Absensi']);
            }
            if (strtoupper(trim((string) ($piket->status ?? ''))) === $target) {
                return $this->response->setJSON(['status' => true, 'message' => 'Sudah selaras']);
            }
            $user = $this->getUser();
            $ok = $this->piketModel->saveAbsensiBatch([[
                'tanggal' => $tanggal,
                'id_kelas' => (int) $piket->id_kelas,
                'id_siswa' => $idSiswa,
                'status' => $target,
                'updated_by' => $user ? (int) $user->id : null,
            ]]);
            if (!$ok) {
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyelaraskan']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Admin\Piket::selarasDivergensi] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyelaraskan']);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Diselaraskan mengikuti sistem (' . $target . ')']);
    }

    public function generateJadwal()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false,
                'message' => 'Akses ditolak'
            ]);
        }

        $this->initData();
        if (empty($this->tp->id_tp) || empty($this->smt->id_smt)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tahun Pelajaran atau Semester aktif belum diset.'
            ]);
        }

        $jumlahPerHari = (int) ($this->request->getPost('jumlah_per_hari') ?: 2);
        if ($jumlahPerHari < 1) {
            $jumlahPerHari = 1;
        } elseif ($jumlahPerHari > 10) {
            $jumlahPerHari = 10;
        }

        $hariInput = $this->request->getPost('hari');
        if (!is_array($hariInput) || empty($hariInput)) {
            $hariInput = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        }

        $validHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $selectedHari = array_values(array_intersect($validHari, $hariInput));

        if (empty($selectedHari)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Pilih minimal satu hari yang valid.'
            ]);
        }

        $mode = trim((string) $this->request->getPost('mode')) === 'append' ? 'append' : 'replace';
        $keterangan = mb_substr(trim((string) ($this->request->getPost('keterangan') ?: 'Petugas Piket')), 0, 500);

        $db = \Config\Database::connect();
        $setting = $this->dashboardModel->getSetting();
        $kepsekIds = $this->getKepsekGuruIds($db, $setting);

        // Ambil daftar guru (kecualikan Kepala Sekolah)
        $builder = $db->table('master_guru')->select('id_guru, nama_guru');
        if ($db->fieldExists('deleted_at', 'master_guru')) {
            $builder->where('deleted_at IS NULL');
        }
        if ($db->fieldExists('status_aktif', 'master_guru')) {
            $builder->groupStart()
                ->where('status_aktif !=', 'Tidak Aktif')
                ->where('status_aktif !=', 'Nonaktif')
                ->orWhere('status_aktif IS NULL')
                ->orWhere('status_aktif', '')
                ->groupEnd();
        }
        if (!empty($kepsekIds)) {
            $builder->whereNotIn('id_guru', $kepsekIds);
        }
        $eligibleTeachers = $builder->get()->getResult();

        if (empty($eligibleTeachers)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada data guru yang memenuhi syarat untuk dijadwalkan piket.'
            ]);
        }

        $eligibleTeacherIds = array_map(function ($g) {
            return (int) $g->id_guru;
        }, $eligibleTeachers);

        // Jika mode replace, hapus jadwal lama untuk hari-hari terpilih pada TP dan SMT aktif
        if ($mode === 'replace') {
            $db->table('piket_jadwal')
                ->where('id_tp', $this->tp->id_tp)
                ->where('id_smt', $this->smt->id_smt)
                ->whereIn('hari', $selectedHari)
                ->delete();
        }

        // Ambil jadwal yang sudah ada (untuk append mode atau sisa hari lain)
        $existingJadwal = $db->table('piket_jadwal')
            ->where('id_tp', $this->tp->id_tp)
            ->where('id_smt', $this->smt->id_smt)
            ->get()->getResult();

        // Hitung berapa kali tiap guru sudah dijadwalkan piket di TP/SMT ini
        $guruAssignmentCount = array_fill_keys($eligibleTeacherIds, 0);
        $assignedPerDay = [];
        foreach ($validHari as $h) {
            $assignedPerDay[$h] = [];
        }

        foreach ($existingJadwal as $row) {
            $gid = (int) $row->id_guru;
            $hariName = $row->hari;
            if (isset($guruAssignmentCount[$gid])) {
                $guruAssignmentCount[$gid]++;
            }
            if (isset($assignedPerDay[$hariName])) {
                $assignedPerDay[$hariName][] = $gid;
            }
        }

        $toInsert = [];
        $totalNew = 0;

        foreach ($selectedHari as $hari) {
            $alreadyOnDay = $assignedPerDay[$hari] ?? [];
            $needed = $jumlahPerHari - count($alreadyOnDay);

            if ($needed <= 0) {
                continue;
            }

            // Guru yang belum bertugas di hari ini
            $candidates = array_values(array_diff($eligibleTeacherIds, $alreadyOnDay));
            if (empty($candidates)) {
                continue;
            }

            // Acak urutan tie-breaker, lalu urutkan berdasarkan jumlah tugas terendah
            shuffle($candidates);
            usort($candidates, function ($a, $b) use ($guruAssignmentCount) {
                return $guruAssignmentCount[$a] <=> $guruAssignmentCount[$b];
            });

            $picked = array_slice($candidates, 0, $needed);
            foreach ($picked as $gid) {
                $toInsert[] = [
                    'id_tp' => $this->tp->id_tp,
                    'id_smt' => $this->smt->id_smt,
                    'hari' => $hari,
                    'id_guru' => $gid,
                    'keterangan' => $keterangan !== '' ? $keterangan : 'Petugas Piket',
                ];
                $guruAssignmentCount[$gid]++;
                $assignedPerDay[$hari][] = $gid;
                $totalNew++;
            }
        }

        if (!empty($toInsert)) {
            try {
                $this->piketJadwalModel->insertBatch($toInsert);
            } catch (\Throwable $e) {
                log_message('error', '[Piket::generateJadwal] ' . $e->getMessage());
                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Gagal menyimpan jadwal hasil generate — periksa log aplikasi.'
                ]);
            }
        }

        $kepsekCount = count($kepsekIds);
        $msg = "Berhasil membuat jadwal piket untuk {$totalNew} penugasan guru (" . count($selectedHari) . " hari).";
        if ($kepsekCount > 0) {
            $msg .= " Kepala Sekolah otomatis dikecualikan.";
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => $msg,
            'total_inserted' => $totalNew,
            'excluded_kepsek' => $kepsekCount
        ]);
    }

    /**
     * Identifikasi ID Guru Kepala Sekolah untuk pengecualian
     */
    protected function getKepsekGuruIds($db, $setting = null)
    {
        $kepsekIds = [];

        // 1. Dari jabatan_guru (id_jabatan = 1 atau level Kepala Sekolah)
        if ($db->tableExists('jabatan_guru')) {
            $jg = $db->table('jabatan_guru')
                ->select('id_guru')
                ->where('id_jabatan', 1)
                ->get()->getResult();
            foreach ($jg as $r) {
                if (!empty($r->id_guru)) {
                    $kepsekIds[] = (int) $r->id_guru;
                }
            }
        }

        // 2. Dari setting NIP dan Nama Kepala Sekolah
        $nipSetting = trim($setting->nip ?? '');
        $namaSetting = strtolower(trim($setting->kepsek ?? ''));

        $builder = $db->table('master_guru')->select('id_guru, nip, nama_guru, tgs_tambahan');
        if ($db->fieldExists('deleted_at', 'master_guru')) {
            $builder->where('deleted_at IS NULL');
        }
        $allGuru = $builder->get()->getResult();

        foreach ($allGuru as $g) {
            $gid = (int) $g->id_guru;
            $gNip = trim($g->nip ?? '');
            $gNama = strtolower(trim($g->nama_guru ?? ''));
            $gTgs = strtolower(trim($g->tgs_tambahan ?? ''));

            if ($nipSetting !== '' && $gNip !== '' && $gNip === $nipSetting) {
                $kepsekIds[] = $gid;
            } elseif ($namaSetting !== '' && $gNama !== '' && (stripos($gNama, $namaSetting) !== false || stripos($namaSetting, $gNama) !== false)) {
                $kepsekIds[] = $gid;
            } elseif (stripos($gTgs, 'kepala sekolah') !== false || stripos($gTgs, 'kepsek') !== false) {
                $kepsekIds[] = $gid;
            }
        }

        return array_values(array_unique(array_filter($kepsekIds)));
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
