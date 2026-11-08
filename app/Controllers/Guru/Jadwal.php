<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\ElearningModel;
use App\Models\GuruJadwalPribadiModel;

class Jadwal extends BaseController
{
    protected $dashboardModel;
    protected $elearningModel;
    protected $jadwalPribadiModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->elearningModel = new ElearningModel();
        $this->jadwalPribadiModel = new GuruJadwalPribadiModel();
        $this->jadwalPribadiModel->initialize();
    }

    public function index()
    {
        try {
            $user = $this->getUser();
            if (!$user)
                return redirect()->to('auth');

            $tpSmt = $this->getTpSmt();
            if (!$tpSmt->tp || !$tpSmt->smt) {
                return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran atau Semester Aktif belum diset.');
            }
            $tp = $tpSmt->tp;
            $smt = $tpSmt->smt;

            $setting = $this->dashboardModel->getSetting();
            if (!$setting)
                $setting = (object) ['logo_kiri' => null, 'nama_sekolah' => 'absensi by ikasmedia', 'logo' => null];

            $today = date('N');
            if ($today == 7) $today = 1;

            $data = [
                'judul' => 'Jadwal Pelajaran',
                'user' => $user,
                'tp_active' => $tp,
                'smt_active' => $smt,
                'is_admin' => false,
                'setting' => $setting,
                'title' => 'Jadwal',
                'today' => $today
            ];

            // Ambil Data Guru
            $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);

            if (!$guru) {
                return redirect()->back()->with('error', 'Data tahun pelajaran guru tidak ditemukan/tidak aktif.');
            }

            $data['guru'] = $guru;
            $data['mapel_options'] = $this->elearningModel->getMapelArray();
            $data['kelas_options'] = $this->elearningModel->getAllKelasSort($tp->id_tp, $smt->id_smt);
            $data['jadwal_pribadi'] = $this->jadwalPribadiModel->getByGuru((int) $guru->id_guru, (int) $tp->id_tp, (int) $smt->id_smt);

            // Ambil Kelas & Mapel yang diajar
            $mapel_guru = $this->elearningModel->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);

            $arrIdKelas = [];
            $arrMapel = [];

            if ($mapel_guru && $mapel_guru->mapel_kelas) {
                $mapelList = @unserialize((string) $mapel_guru->mapel_kelas, ['allowed_classes' => false]);
                if ($mapelList === false && $mapel_guru->mapel_kelas != 'b:0;') {
                    $mapelList = json_decode((string) $mapel_guru->mapel_kelas, true);
                }

                if ($mapelList && is_array($mapelList)) {
                    foreach ($mapelList as $m) {
                        if (isset($m['kelas_mapel']) && is_array($m['kelas_mapel'])) {
                            foreach ($m['kelas_mapel'] as $item) {
                                if (isset($item['kelas'])) {
                                    $arrMapel[$item['kelas']][$m['id_mapel']] = $m['nama_mapel'];
                                    $arrIdKelas[] = $item['kelas'];
                                }
                            }
                        }
                    }
                }
            }

            // Include classes from personal schedule as well
            if (!empty($data['jadwal_pribadi'])) {
                foreach ($data['jadwal_pribadi'] as $jp) {
                    if (!empty($jp->id_kelas)) {
                        $arrIdKelas[] = (int) $jp->id_kelas;
                        if (!empty($jp->id_mapel) && !empty($jp->nama_mapel)) {
                            $arrMapel[$jp->id_kelas][$jp->id_mapel] = $jp->nama_mapel;
                        }
                    }
                }
            }

            // Remove duplicate kelas IDs
            $arrIdKelas = array_unique(array_filter($arrIdKelas));

            if (!empty($arrIdKelas)) {
                $kelas = $this->elearningModel->getAllKelasByArrayId($tp->id_tp, $smt->id_smt, $arrIdKelas);
            } else {
                $kelas = $this->elearningModel->getAllKelasSort($tp->id_tp, $smt->id_smt);
            }

            $data['kelas'] = $kelas;
            $data['mapels'] = $arrMapel;

            // KBM Setting (General)
            $jadk = $this->elearningModel->getJadwalKbm($tp->id_tp, $smt->id_smt);

            if (!empty($jadk)) {
                $firstKbm = reset($jadk);
                if (isset($firstKbm->istirahat) && is_string($firstKbm->istirahat))
                    $firstKbm->istirahat = json_decode($firstKbm->istirahat);
                if (isset($firstKbm->upacara) && is_string($firstKbm->upacara))
                    $firstKbm->upacara = json_decode($firstKbm->upacara);
                $data['setting_kbm'] = $firstKbm;
            } else {
                $data['setting_kbm'] = (object) [
                    'libur' => '7',
                    'kbm_jam_mulai' => '07:00',
                    'kbm_jam_selesai' => '15:00',
                    'istirahat' => [],
                    'upacara' => []
                ];
            }

            return view('members/guru/jadwal_mobile', $data);

        } catch (\Throwable $th) {
            // die($th->getMessage()) dulu membocorkan isi error ke peramban
            // dan tidak pernah masuk log server.
            log_message('error', '[guru/jadwal] ' . $th->getMessage() . ' @ ' . $th->getFile() . ':' . $th->getLine());
            return redirect()->back()->with('error', 'Jadwal mengajar gagal dimuat. Silakan muat ulang halaman, atau laporkan ke admin bila terus terjadi.');
        }
    }

    public function get_jadwal($hari)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran atau Semester Aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp, $smt);
        $idGuru = $guru ? (int) $guru->id_guru : 0;

        $jadwal_kbm = $this->elearningModel->getJadwalKbm($tp, $smt);
        $jadwal_mapel = $this->elearningModel->getJadwalHarian($tp, $smt, (int) $hari);

        // Hanya tampilkan jadwal kelas yang benar-benar diajar guru ini.
        $myPairs = $idGuru > 0
            ? $this->elearningModel->getGuruAssignedPairs($idGuru, $tp, $smt)
            : [];
        $myJadwalMapel = [];

        // Fetch personal schedule (guru_jadwal_pribadi) for this day
        $pribadi = [];
        if ($idGuru > 0) {
            $pribadi = $this->jadwalPribadiModel->getByGuruAndHari($idGuru, $tp, $smt, (int) $hari);
        }

        // Helper to get time range
        $minTime = "23:59";
        $maxTime = "00:00";

        $combinedList = [];

        // 1. Process School KBM Schedule (hanya kelas:mapel milik guru)
        if ($jadwal_mapel) {
            foreach ($jadwal_mapel as $idKls => $classList) {
                foreach ($classList as $m) {
                    $sampai = is_object($m) ? ($m->sampai ?? '') : ($m['sampai'] ?? '');
                    $dari = is_object($m) ? ($m->dari ?? '') : ($m['dari'] ?? '');
                    $idMapel = (int) (is_object($m) ? ($m->id_mapel ?? 0) : ($m['id_mapel'] ?? 0));
                    $namaMapel = is_object($m) ? ($m->nama_mapel ?? '') : ($m['nama_mapel'] ?? '');
                    $jamKe = is_object($m) ? ($m->jam_ke ?? '') : ($m['jam_ke'] ?? '');

                    if (!isset($myPairs[(int) $idKls . ':' . $idMapel])) continue;

                    $myJadwalMapel[(int) $idKls][] = $m;

                    if ($sampai && $sampai > $maxTime) $maxTime = $sampai;
                    if ($dari && $dari < $minTime) $minTime = $dari;

                    $combinedList[] = [
                        'id' => is_object($m) ? ($m->id_jadwal ?? 0) : ($m['id_jadwal'] ?? 0),
                        'id_kelas' => (string) $idKls,
                        'id_mapel' => (int) $idMapel,
                        'nama_mapel' => $namaMapel,
                        'mapelName' => $namaMapel,
                        'dari' => substr($dari, 0, 5),
                        'sampai' => substr($sampai, 0, 5),
                        'timeLabel' => substr($dari, 0, 5) . '-' . substr($sampai, 0, 5),
                        'jam_ke' => $jamKe,
                        'ruangan' => null,
                        'is_pribadi' => 0
                    ];
                }
            }
        }

        // 2. Process Teacher's Personal Schedule (guru_jadwal_pribadi)
        if (!empty($pribadi)) {
            $autoJamKe = 1;
            foreach ($pribadi as $p) {
                $dari = substr($p->jam_mulai ?? '', 0, 5);
                $sampai = substr($p->jam_selesai ?? '', 0, 5);
                if ($sampai && $sampai > $maxTime) $maxTime = $sampai;
                if ($dari && $dari < $minTime) $minTime = $dari;

                $combinedList[] = [
                    'id' => (int) $p->id,
                    'id_kelas' => (string) $p->id_kelas,
                    'kelasName' => $p->nama_kelas ?? ('Kelas ' . $p->id_kelas),
                    'id_mapel' => (int) $p->id_mapel,
                    'nama_mapel' => $p->nama_mapel ?? 'Mata Pelajaran',
                    'mapelName' => $p->nama_mapel ?? 'Mata Pelajaran',
                    'dari' => $dari,
                    'sampai' => $sampai,
                    'timeLabel' => $dari . '-' . $sampai,
                    'jam_ke' => $autoJamKe++,
                    'ruangan' => $p->ruangan ?? null,
                    'is_pribadi' => 1
                ];
            }
        }

        if ($minTime == "23:59") {
            $minTime = "07:00";
            $maxTime = "15:00";
        }

        // Generate Time Slots (5 mins interval)
        $start = strtotime($minTime);
        $end = strtotime($maxTime);
        $times = [];
        while ($start < $end) {
            $times[] = ['start' => date('H:i', $start)];
            $start = strtotime('+5 minutes', $start);
        }

        $data = [
            'times' => $times,
            'mapel' => $myJadwalMapel,
            'kbm' => $jadwal_kbm,
            'pribadi' => $pribadi,
            'list' => $combinedList,
            'min_time' => $minTime,
            'max_time' => $maxTime
        ];

        return $this->response->setJSON([
            'status' => true,
            'data' => $data,
            'is_admin' => false
        ]);
    }

    protected function getTpSmt(): object
    {
        return (object) [
            'tp' => $this->dashboardModel->getTahunActive(),
            'smt' => $this->dashboardModel->getSemesterActive()
        ];
    }

    private function isValidTime(string $time): bool
    {
        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time);
    }

    public function save_pribadi()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran atau Semester Aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp, $smt);
        if (!$guru) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data guru tidak ditemukan']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $hari = (int) ($this->request->getPost('hari') ?? 0);
        $jamMulai = trim((string) ($this->request->getPost('jam_mulai') ?? ''));
        $jamSelesai = trim((string) ($this->request->getPost('jam_selesai') ?? ''));
        $idMapel = (int) ($this->request->getPost('id_mapel') ?? 0);
        $idKelas = (int) ($this->request->getPost('id_kelas') ?? 0);
        $ruangan = trim((string) ($this->request->getPost('ruangan') ?? ''));
        if ($ruangan === '') $ruangan = null;

        if ($hari < 1 || $hari > 7) {
            return $this->response->setJSON(['status' => false, 'message' => 'Hari tidak valid']);
        }
        if (!$this->isValidTime($jamMulai) || !$this->isValidTime($jamSelesai)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam tidak valid']);
        }
        if (strtotime($jamMulai) >= strtotime($jamSelesai)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jam mulai harus lebih kecil dari jam selesai']);
        }
        if ($idMapel <= 0 || $idKelas <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel dan kelas wajib dipilih']);
        }
        if ($ruangan !== null && mb_strlen($ruangan) > 50) {
            return $this->response->setJSON(['status' => false, 'message' => 'Ruangan terlalu panjang (maks 50 karakter)']);
        }

        if ($id) {
            $row = $this->jadwalPribadiModel->find($id);
            if (!$row || (int) $row->id_guru !== (int) $guru->id_guru || (int) $row->id_tp !== $tp || (int) $row->id_smt !== $smt) {
                return $this->response->setJSON(['status' => false, 'message' => 'Data tidak ditemukan atau akses ditolak']);
            }
        }

        $conflictsGuru = $this->jadwalPribadiModel->findConflictsForGuru((int) $guru->id_guru, $tp, $smt, $hari, $jamMulai, $jamSelesai, $id ?: null);
        if (!empty($conflictsGuru)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jadwal bentrok: Anda sudah memiliki jadwal di jam tersebut']);
        }

        $conflictsKelas = $this->jadwalPribadiModel->findConflictsForKelas($idKelas, $tp, $smt, $hari, $jamMulai, $jamSelesai, $id ?: null);
        if (!empty($conflictsKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jadwal bentrok: kelas sudah memiliki jadwal di jam tersebut']);
        }

        $payload = [
            'id_tp' => $tp,
            'id_smt' => $smt,
            'id_guru' => (int) $guru->id_guru,
            'hari' => $hari,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'id_mapel' => $idMapel,
            'id_kelas' => $idKelas,
            'ruangan' => $ruangan,
        ];

        if ($id) {
            $this->jadwalPribadiModel->update($id, $payload);
            return $this->response->setJSON(['status' => true, 'message' => 'Jadwal berhasil diperbarui']);
        }

        $this->jadwalPribadiModel->insert($payload);
        return $this->response->setJSON(['status' => true, 'message' => 'Jadwal berhasil ditambahkan']);
    }

    public function delete_pribadi()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran atau Semester Aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp, $smt);
        if (!$guru) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data guru tidak ditemukan']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak valid']);
        }

        $row = $this->jadwalPribadiModel->find($id);
        if (!$row || (int) $row->id_guru !== (int) $guru->id_guru || (int) $row->id_tp !== $tp || (int) $row->id_smt !== $smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak ditemukan atau akses ditolak']);
        }

        $this->jadwalPribadiModel->delete($id);
        return $this->response->setJSON(['status' => true, 'message' => 'Jadwal berhasil dihapus']);
    }
}
