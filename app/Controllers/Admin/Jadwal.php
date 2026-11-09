<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ElearningModel;
use App\Models\DashboardModel;
use App\Models\JabatanGuruModel;
use App\Models\JurnalGuruModel;

class Jadwal extends BaseController
{
    protected $elearningModel;
    protected $dashboardModel;
    protected $jabatanModel;

    public function __construct()
    {
        $this->elearningModel = new ElearningModel();
        $this->dashboardModel = new DashboardModel();
        $this->jabatanModel = new JabatanGuruModel();
    }

    protected function getTpSmt(): object
    {
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        return (object) ['tp' => $tp, 'smt' => $smt];
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        // Safety Checks for Null Objects
        if (!$tp)
            $tp = (object) ['id_tp' => 0, 'tahun' => '-'];
        if (!$smt)
            $smt = (object) ['id_smt' => 0, 'smt' => '-'];

        // Get Data
        $jadk = $this->elearningModel->getJadwalKbm($tp->id_tp, $smt->id_smt);

        $settingKbm = (object) [
            'libur' => '7',
            'istirahat' => [],
            'ada' => false
        ];

        if (!empty($jadk)) {
            $firstKey = array_key_first($jadk);
            $jadwal = $jadk[$firstKey];

            // Safe Decode Istirahat (Expect Array)
            $ist = json_decode($jadwal->istirahat ?? '');
            $jadwal->istirahat = is_array($ist) ? $ist : [];

            // Safe Decode Upacara (Expect Object)
            $upc = json_decode($jadwal->upacara ?? '');
            $jadwal->upacara = is_object($upc) ? $upc : (object) [];

            $settingKbm = $jadwal;
            $settingKbm->ada = true;
        }

        $allKelas = $this->elearningModel->getAllKelasSort($tp->id_tp, $smt->id_smt);
        $mapels = $this->elearningModel->getMapelArray();
        $gurus = $this->elearningModel->getAllGuru();
        $penugasanKosong = $this->elearningModel->getUnassignedSchedulePairs((int) $tp->id_tp, (int) $smt->id_smt);

        $setting = $this->dashboardModel->getSetting();
        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_sekolah' => 'absensi by ikasmedia', 'logo' => null, 'website' => ''];

        $data = [
            'title' => 'Jadwal Pelajaran',
            'judul' => 'Jadwal Pelajaran',
            'subjudul' => 'Pengaturan Jadwal',
            'tp_active' => $tp,
            'smt_active' => $smt,
            'user' => $this->getUser(),
            'setting' => $setting, // App Setting (Logo etc)
            'setting_kbm' => $settingKbm,
            'kelas' => $allKelas,
            'mapels' => $mapels,
            'gurus' => $gurus,
            'penugasan_kosong' => $penugasanKosong,
            'is_admin' => true,
            'id_hari' => '-1'
        ];

        return view('admin/elearning/jadwal', $data);
    }

    /**
     * AJAX: Get Jadwal Harian & Timeline
     */
    public function get_jadwal($hari)
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        $allKelas = $this->elearningModel->getAllKelasSort($tp->id_tp, $smt->id_smt);
        $jadk = $this->elearningModel->getJadwalKbm($tp->id_tp, $smt->id_smt);
        $jadwalMapel = $this->elearningModel->getJadwalHarian($tp->id_tp, $smt->id_smt, $hari);

        // --- LOGIC PORTED FROM CI3 Elearning::hari() ---

        // Dummy jadwal jika kosong
        if (empty($jadwalMapel)) {
            foreach ($allKelas as $keyKls => $kelasName) {
                // Initialize empty slots (optional, skipping for now as UI handles it)
            }
        }

        // 1. Calculate Min/Max Time
        $minTime = "23:59";
        $maxTime = "06:00";
        $kelasKeys = array_keys($allKelas);

        foreach ($jadk as $idKelas => $kelasObj) {
            if (!in_array($idKelas, $kelasKeys))
                continue;

            if ($kelasObj->kbm_jam_selesai > $maxTime) {
                $maxTime = $kelasObj->kbm_jam_selesai;
            }
            if ($kelasObj->kbm_jam_mulai < $minTime) {
                $minTime = $kelasObj->kbm_jam_mulai;
            }
        }

        // Fallback defaults
        if ($minTime == "23:59") {
            $minTime = "07:00";
            $maxTime = "15:00";
        }

        // 2. Generate Time Slots (5 minutes interval)
        $longDay = round(abs(strtotime($maxTime) - strtotime($minTime)) / 60, 2) + 5;
        // Safety cap
        if ($longDay > 1440)
            $longDay = 1440;

        $startTime = new \DateTime($minTime);
        $timeSlots = [];

        for ($m = 0; $m < ($longDay / 5); $m++) {
            $formattedStart = $startTime->format('H:i');

            // Logic to merge slots logic (ported loosely)
            // Ideally we just send the raw slots and JS handles rendering rowspans?
            // CI3 logic merged 30 mins blocks.

            $timeSlots[] = ['start' => $formattedStart];
            $startTime->add(new \DateInterval('PT5M'));
        }

        // 3. Process Per-Class Detail
        $assignmentMap = $this->elearningModel->getGuruAssignmentMap((int) $tp->id_tp, (int) $smt->id_smt);
        $jadwalData = [];
        foreach ($allKelas as $idKelas => $namaKelas) {
            $mapelKls = $jadwalMapel[$idKelas] ?? [];

            foreach ($mapelKls as $row) {
                $teachers = $assignmentMap[(int) $row->id_kelas . ':' . (int) $row->id_mapel] ?? [];
                $row->guru_ids = array_keys($teachers);
                $row->guru_nama = implode(', ', array_values($teachers));
            }

            $jadwalData[$idKelas] = $mapelKls;
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => [
                'times' => $timeSlots,
                'min_time' => $minTime,
                'max_time' => $maxTime,
                'mapel' => $jadwalData,
                'kbm' => $jadk
            ]
        ]);
    }

    /**
     * Save Global Setting Jadwal (AJAX)
     */
    public function save_setting()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $mulai = $this->normalizeTime((string) $this->request->getPost('jam_mulai'));
        $selesai = $this->normalizeTime((string) $this->request->getPost('jam_selesai'));
        $libur = (string) ($this->request->getPost('hari_libur') ?? '7');
        $formatted = $this->request->getPost('format') === 'on';

        if ($mulai === null || $selesai === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam tidak valid (HH:MM)']);
        }
        if ($mulai >= $selesai) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jam masuk harus lebih awal dari jam pulang']);
        }
        if (!in_array($libur, ['0', '1', '2', '3', '4', '5', '6', '7'], true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Hari libur tidak valid']);
        }

        // Upacara
        $upacara = [
            'id_hari' => $this->request->getPost('hari_upacara'),
            'jam_ke' => $this->request->getPost('jam_upacara'),
            'durasi' => $this->request->getPost('durasi_upacara')
        ];

        // Format details
        $jamMapel = $this->request->getPost('jam_mapel');
        $jmlMapel = $this->request->getPost('jml_mapel');

        $istirahat = [];
        $jmlIst = (int) $this->request->getPost('jum_ist');
        for ($i = 0; $i < $jmlIst; $i++) {
            $istirahat[] = [
                'ist' => $this->request->getPost('ist' . $i),
                'dur' => $this->request->getPost('dur_ist' . $i),
            ];
        }

        // Logic Save
        $allKelas = $this->elearningModel->getAllKelas($tp, $smt);
        $jadk = $this->elearningModel->getJadwalKbm($tp, $smt); // keyed by id_kelas

        $insertKbm = [];
        $updateKbm = [];

        foreach ($allKelas as $kelas) {
            $idKelas = $kelas->id_kelas;
            $data = [
                'kbm_jam_mulai' => $mulai,
                'kbm_jam_selesai' => $selesai,
                'libur' => $libur,
                'upacara' => json_encode($upacara),
            ];

            if ($formatted) {
                $data['kbm_jam_pel'] = $jamMapel;
                $data['kbm_jml_mapel_hari'] = $jmlMapel;
                $data['istirahat'] = json_encode($istirahat);
            }

            if (isset($jadk[$idKelas])) {
                // Update
                $data['id_kbm'] = $jadk[$idKelas]->id_kbm;
                $updateKbm[] = $data;
            } else {
                // Insert
                $data['id_tp'] = $tp;
                $data['id_smt'] = $smt;
                $data['id_kelas'] = $idKelas;
                $insertKbm[] = $data;
            }
        }

        if (empty($insertKbm) && empty($updateKbm)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada rombel pada periode aktif']);
        }

        // insertBatch/updateBatch mengembalikan false tanpa melempar
        // exception, jadi kegagalan sebagian harus dicek lewat nilai
        // kembali dan status transaksi, bukan lewat catch saja.
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $gagal = [];
            if (!empty($insertKbm) && $this->elearningModel->insertBatchKbm($insertKbm) === false) {
                $gagal[] = 'pengaturan ' . count($insertKbm) . ' rombel baru';
            }
            if (!empty($updateKbm) && $this->elearningModel->updateBatchKbm($updateKbm) === false) {
                $gagal[] = 'pengaturan ' . count($updateKbm) . ' rombel lama';
            }
            if ($db->transStatus() === false) {
                $gagal[] = 'penyimpanan batch ditolak database';
            }

            if (!empty($gagal)) {
                $db->transRollback();
                log_message('error', '[save_setting] gagal menyimpan KBM: ' . implode('; ', $gagal));
                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Pengaturan jadwal gagal disimpan (' . implode('; ', $gagal) . ') — perubahan tidak diterapkan.'
                ]);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[save_setting] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan pengaturan jadwal']);
        }

        // Terapkan susunan waktu ke seluruh kelas. Baris istirahat/upacara
        // selalu disinkronkan; slot kosong hanya dibuat bila format dicentang.
        $susun = $this->syncKbmLayout($tp, $smt, $formatted);

        return $this->response->setJSON([
            'status' => true,
            'message' => $susun
                ? 'Pengaturan jadwal berhasil disimpan'
                : 'Pengaturan jam tersimpan, tetapi susunan slot istirahat/upacara gagal disusun ulang. Cek log atau simpan sekali lagi.'
        ]);
    }

    /**
     * Save Mapel per Slot (AJAX).
     * Guru pengajar disimpan pada jabatan_guru.mapel_kelas agar jadwal
     * otomatis muncul sebagai quick-pick pada form jurnal guru.
     */
    public function save_mapel_slot()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $idJadwal = (int) $this->request->getPost('jadwal');
        $idKelas = (int) $this->request->getPost('kelas');
        $idHari = (int) $this->request->getPost('hari');
        $idMapel = (int) $this->request->getPost('id_mapel');
        $idGuru = (int) $this->request->getPost('id_guru');
        $jumlahJam = (int) $this->request->getPost('jumlah_jam');
        $jamMulai = $this->normalizeTime((string) $this->request->getPost('jam_mulai'));
        $jamSelesai = $this->normalizeTime((string) $this->request->getPost('jam_selesai'));

        if ($idHari < 1 || $idHari > 7) {
            return $this->response->setJSON(['status' => false, 'message' => 'Hari tidak valid']);
        }
        if ($jamMulai === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam tidak valid (HH:MM)']);
        }
        if ($jumlahJam < 0 || $jumlahJam > 20) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jumlah jam pelajaran tidak valid']);
        }

        $allKelas = $this->elearningModel->getAllKelasSort($tp, $smt);
        if (!isset($allKelas[$idKelas])) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak ditemukan pada periode aktif']);
        }

        // Satu sesi mapel diisi sekali saja: jumlah jam menentukan jam selesai,
        // sehingga mapel 2 jam pelajaran tidak perlu dibuat dua baris terpisah.
        $kbmMap = $this->elearningModel->getJadwalKbm($tp, $smt);
        $kbmRow = $kbmMap[$idKelas] ?? null;
        $jamPel = (int) ($kbmRow->kbm_jam_pel ?? 0);

        if ($idMapel > 0 && $jumlahJam > 0 && $jamPel > 0) {
            $jamSelesai = $this->minutesToTime($this->timeToMinutes($jamMulai) + ($jumlahJam * $jamPel));
        }
        if ($jamSelesai === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam tidak valid (HH:MM)']);
        }
        if ($jamMulai >= $jamSelesai) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jam mulai harus lebih awal dari jam selesai']);
        }

        $jamPulang = $this->normalizeTime((string) ($kbmRow->kbm_jam_selesai ?? ''));
        if ($jamPulang !== null && $jamSelesai > $jamPulang) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Rentang ' . ($jumlahJam > 0 ? $jumlahJam . ' jam ' : '') . 'melebihi jam pulang (' . $jamPulang . ')'
            ]);
        }

        $db = \Config\Database::connect();
        if ($idMapel > 0 && !$db->table('master_mapel')->where('id_mapel', $idMapel)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Mata pelajaran tidak ditemukan']);
        }
        if ($idMapel > 0 && $idGuru <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Pilih guru pengajar agar jadwal terhubung ke jurnal']);
        }

        $guruNama = '';
        if ($idGuru > 0) {
            $guruRow = $db->table('master_guru')->select('id_guru, nama_guru')->where('id_guru', $idGuru)->get()->getRow();
            if (!$guruRow) {
                return $this->response->setJSON(['status' => false, 'message' => 'Guru pengajar tidak ditemukan']);
            }
            $guruNama = (string) $guruRow->nama_guru;
        }

        $oldRow = null;
        if ($idJadwal > 0) {
            $oldRow = $db->table('kelas_jadwal_mapel')
                ->where('id_jadwal', $idJadwal)
                ->where('id_tp', $tp)
                ->where('id_smt', $smt)
                ->get()->getRow();
            if (!$oldRow) {
                return $this->response->setJSON(['status' => false, 'message' => 'Jadwal tidak ditemukan pada periode aktif']);
            }
        }

        // Kelas tidak boleh memiliki dua mapel pada waktu yang beririsan.
        $classConflict = $db->table('kelas_jadwal_mapel')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_kelas', $idKelas)
            ->where('id_hari', $idHari)
            ->where('id_mapel !=', 0)
            ->where('dari <', $jamSelesai)
            ->where('sampai >', $jamMulai)
            ->where('id_jadwal !=', $idJadwal)
            ->orderBy('dari', 'ASC')
            ->get()->getRow();
        if ($classConflict) {
            $conflictLabel = 'jadwal';
            if ((int) $classConflict->id_mapel === -1) $conflictLabel = 'Istirahat';
            elseif ((int) $classConflict->id_mapel === -2) $conflictLabel = 'Upacara';

            return $this->response->setJSON([
                'status' => false,
                'message' => 'Bentrok: kelas sudah memiliki ' . $conflictLabel . ' ' . substr((string) $classConflict->dari, 0, 5) . '-' . substr((string) $classConflict->sampai, 0, 5)
            ]);
        }

        // Guru tidak boleh mengajar dua kelas pada waktu yang beririsan.
        if ($idMapel > 0 && $idGuru > 0) {
            $pairs = $this->elearningModel->getGuruAssignedPairs($idGuru, $tp, $smt);
            $pairs[$idKelas . ':' . $idMapel] = true;

            $teacherRows = $db->table('kelas_jadwal_mapel jm')
                ->select('jm.id_jadwal, jm.id_kelas, jm.id_mapel, jm.dari, jm.sampai, k.nama_kelas')
                ->join('master_kelas k', 'k.id_kelas = jm.id_kelas', 'left')
                ->where('jm.id_tp', $tp)
                ->where('jm.id_smt', $smt)
                ->where('jm.id_hari', $idHari)
                ->where('jm.id_mapel >', 0)
                ->where('jm.dari <', $jamSelesai)
                ->where('jm.sampai >', $jamMulai)
                ->get()->getResult();

            foreach ($teacherRows as $tr) {
                if ((int) $tr->id_jadwal === $idJadwal) continue;
                if (isset($pairs[(int) $tr->id_kelas . ':' . (int) $tr->id_mapel])) {
                    return $this->response->setJSON([
                        'status' => false,
                        'message' => 'Bentrok guru: ' . ($guruNama ?: 'guru ini') . ' sudah mengajar di ' . ($tr->nama_kelas ?: ('kelas #' . $tr->id_kelas)) . ' pada jam tersebut'
                    ]);
                }
            }
        }

        $data = [
            'id_tp' => $tp,
            'id_smt' => $smt,
            'id_kelas' => $idKelas,
            'id_hari' => $idHari,
            'id_mapel' => $idMapel,
            'dari' => $jamMulai,
            'sampai' => $jamSelesai,
        ];

        $db->transBegin();
        try {
            if ($idJadwal > 0) {
                $db->table('kelas_jadwal_mapel')->where('id_jadwal', $idJadwal)->update($data);
                $msg = 'Jadwal berhasil diperbarui';
            } else {
                $data['jam_ke'] = 0;
                $db->table('kelas_jadwal_mapel')->insert($data);
                $idJadwal = (int) $db->insertID();
                $msg = 'Jadwal berhasil ditambahkan';
            }

            // Slot kosong (placeholder hasil format otomatis) yang tertimpa
            // dilebur ke baris mapel ini agar sesi multi-jam cukup diisi sekali.
            $db->table('kelas_jadwal_mapel')
                ->where('id_tp', $tp)
                ->where('id_smt', $smt)
                ->where('id_kelas', $idKelas)
                ->where('id_hari', $idHari)
                ->where('id_mapel', 0)
                ->where('id_jadwal !=', $idJadwal)
                ->where('dari <', $jamSelesai)
                ->where('sampai >', $jamMulai)
                ->delete();

            $this->reindexJamKe($tp, $smt, $idKelas, $idHari);

            // Slot ini bisa saja baru saja dipindah kelas/mapelnya, padahal
            // ada jurnal lama yang masih menautkan id_jadwal ke slot tersebut.
            (new JurnalGuruModel())->detachStaleJadwalLinks($tp, $smt);

            if ($idMapel > 0 && $idGuru > 0) {
                // Satu pasangan kelas:mapel diampu satu guru agar quick-pick
                // jurnal dan monitoring tidak menampilkan sesi ganda.
                $this->removePairFromOtherTeachers($tp, $smt, $idKelas, $idMapel, $idGuru);
                $this->assignTeacherPair($tp, $smt, $idGuru, $idKelas, $idMapel);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[save_mapel_slot] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan jadwal']);
        }

        // Bersihkan penugasan pasangan lama yang sudah tidak punya slot.
        if ($oldRow && (int) $oldRow->id_mapel > 0) {
            $oldKey = (int) $oldRow->id_kelas . ':' . (int) $oldRow->id_mapel;
            if ((int) $oldRow->id_kelas !== $idKelas || (int) $oldRow->id_mapel !== $idMapel) {
                $this->cleanupPairAssignments($tp, $smt, [$oldKey => true]);
            }
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => $msg,
            'id_jadwal' => $idJadwal,
            'guru' => $guruNama,
        ]);
    }

    /**
     * Delete Jadwal Mapel ID (AJAX)
     */
    public function delete_mapel()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $id = (int) $this->request->getPost('id_jadwal');
        if (!$id)
            return $this->response->setJSON(['status' => false, 'message' => 'ID Jadwal tidak ditemukan']);

        $db = \Config\Database::connect();
        $row = $db->table('kelas_jadwal_mapel')
            ->where('id_jadwal', $id)
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()->getRow();
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jadwal tidak ditemukan pada periode aktif']);
        }

        $db->transBegin();
        try {
            $deleted = $this->elearningModel->deleteJadwalMapel($id);
            if (!$deleted) {
                $db->transRollback();
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus jadwal']);
            }

            $this->reindexJamKe($tp, $smt, (int) $row->id_kelas, (int) $row->id_hari);

            // Jurnal yang tertaut ke slot terhapus tidak boleh menggantung:
            // tautan basi menutupi fallback pencocokan kelas:mapel:jam.
            (new JurnalGuruModel())->detachStaleJadwalLinks($tp, $smt);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[delete_mapel] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus jadwal']);
        }

        if ((int) $row->id_mapel > 0) {
            $this->cleanupPairAssignments($tp, $smt, [(int) $row->id_kelas . ':' . (int) $row->id_mapel => true]);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Jadwal mapel berhasil dihapus']);
    }

    /**
     * Delete Jadwal - Handles Single Day or All Days
     */
    public function delete_jadwal()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $type = $this->request->getPost('type');
        $all = $this->request->getPost('all') === 'true'; // string from JS FormData
        $idHari = (int) $this->request->getPost('id_hari');

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $db = \Config\Database::connect();
        $builder = $db->table('kelas_jadwal_mapel')
            ->select('id_kelas, id_mapel, id_hari')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt);

        if ($type == 'r1' && $idHari) {
            $builder->where('id_hari', $idHari);
        }

        $affected = $builder->get()->getResult();
        if (empty($affected)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada jadwal pada periode ini' . ($idHari ? ' untuk hari tersebut' : '')]);
        }

        $db->transBegin();
        try {
            $terhapus = $this->elearningModel->deleteJadwalHarian($tp, $smt, ($type == 'r1' && $idHari) ? $idHari : null, true);
            if (!$terhapus) {
                $db->transRollback();
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus jadwal']);
            }

            // Sama seperti delete_mapel: jurnal yang tertaut ke slot hari ini
            // harus dilepas, bukan dibiarkan menunjuk id_jadwal yang sudah
            // tidak ada.
            (new JurnalGuruModel())->detachStaleJadwalLinks($tp, $smt);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[delete_jadwal] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menghapus jadwal']);
        }

        $pairs = [];
        foreach ($affected as $row) {
            if ((int) $row->id_mapel > 0) {
                $pairs[(int) $row->id_kelas . ':' . (int) $row->id_mapel] = true;
            }
        }
        $this->cleanupPairAssignments($tp, $smt, $pairs);

        return $this->response->setJSON([
            'status' => true,
            'message' => count($affected) . ' slot jadwal berhasil dihapus',
        ]);
    }

    public function reformat_jadwal()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        // Ambil range min/max dari detail mapel
        $jadwal_mapel = $this->elearningModel->getAllJadwalHarianFull($tp, $smt);
        $jadwal_kbm = $this->elearningModel->getJadwalKbm($tp, $smt);

        $minTime = "23:59";
        $maxTime = "06:00";
        if (count($jadwal_mapel) > 0) {
            foreach ($jadwal_mapel as $mapel) {
                if ($mapel->sampai > $maxTime)
                    $maxTime = $mapel->sampai;
                if ($mapel->dari < $minTime)
                    $minTime = $mapel->dari;
            }

            // Update KBM settings
            $updateKbm = [];
            foreach ($jadwal_kbm as $idKelas => $kbm) {
                $updateKbm[] = [
                    'id_kbm' => $kbm->id_kbm,
                    'kbm_jam_mulai' => ($minTime != "23:59") ? $minTime : $kbm->kbm_jam_mulai,
                    'kbm_jam_selesai' => ($minTime != "23:59") ? $maxTime : $kbm->kbm_jam_selesai
                ];
            }
            if (!empty($updateKbm))
                $this->elearningModel->updateBatchKbm($updateKbm);
        }

        return $this->response->setJSON([
            'status' => true,
            'min_time' => $minTime,
            'max_time' => $maxTime,
            'message' => 'Jadwal berhasil diformat ulang'
        ]);
    }

    public function copy_jadwal()
    {
        if (!$this->isAdmin())
            return $this->response->setJSON(['status' => false, 'message' => 'Access Denied']);

        $tpSmt = $this->getTpSmt();
        if (!$tpSmt->tp || !$tpSmt->smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tp = (int) $tpSmt->tp->id_tp;
        $smt = (int) $tpSmt->smt->id_smt;

        $thnLaluStr = $this->request->getPost('thnLalu');
        $smtLalu = (int) $this->request->getPost('smtLalu');

        $thnLalu = $this->elearningModel->getTpByTahun($thnLaluStr);
        if (!$thnLalu)
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun lalu tidak ditemukan']);
        if ($smtLalu <= 0)
            return $this->response->setJSON(['status' => false, 'message' => 'Semester sumber tidak valid']);

        // Menyalin periode ke dirinya sendiri akan menghapus jadwal lebih
        // dulu lalu membaca sumber yang sudah kosong — hasil akhirnya
        // semester ini bersih tanpa data. Tolak sebelum apa pun ditulis.
        if ((int) $thnLalu->id_tp === $tp && $smtLalu === $smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Periode sumber sama dengan periode aktif — salin dari periode lain.']);
        }

        // Baca sumber sebelum transaksi penghapusan. Kalau sumbernya kosong,
        // penyalinan hanya akan membabat jadwal yang sudah ada.
        $jadwalKbmLalu = $this->elearningModel->getAllJadwalKbmFull($thnLalu->id_tp, $smtLalu);
        $jadwalMapelLalu = $this->elearningModel->getAllJadwalHarianFull($thnLalu->id_tp, $smtLalu);
        if (empty($jadwalKbmLalu) && empty($jadwalMapelLalu)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Periode sumber tidak punya jadwal untuk disalin — jadwal aktif tidak diubah.']);
        }

        // 1. Delete Existing for Current TP/SMT
        // Warning: This deletes KBM settings too!
        // CI3: db->delete('kelas_jadwal_kbm') & db->delete('kelas_jadwal_mapel')
        // We will simulate this using clean delete logic.
        // Assuming model doesn't have deleteKbm methods, use DB builder or add model method.
        // For brevity, deleting mapel is supported. Deleting KBM needs access.
        // Let's rely on update/insert to overwrite, BUT CI3 explicitly deletes first.
        // If we don't delete, duplicates might happen or old data remains.

        // I'll add direct query here for simplicity or model method.
        // Direct builder access in controller is acceptable for bulk ops.
        $db = \Config\Database::connect();

        // Penghapusan di bawah ini bersifat permanen untuk TP/SMT berjalan.
        // Tanpa transaksi, kegagalan di tahap baca/sisip berikutnya membuat
        // jadwal semester ini hilang sementara salinan gagal terbentuk.
        $db->transBegin();
        try {
            $db->table('kelas_jadwal_kbm')->where('id_tp', $tp)->where('id_smt', $smt)->delete();
            $db->table('kelas_jadwal_mapel')->where('id_tp', $tp)->where('id_smt', $smt)->delete();

            // 2. Prepare Mapping (Normalized Kode Kelas)
            $kelasNow = $this->elearningModel->getAllKelas($tp, $smt);
            $mapKelas = [];
            foreach ($kelasNow as $kls) {
                $mapKelas[$this->normalize($kls->kode_kelas)] = $kls->id_kelas;
            }

            // 3. Process KBM
            $insertKbm = [];

            foreach ($jadwalKbmLalu as $kbm) {
                $kode = $this->normalize($kbm->kode_kelas); // grabbed from join
                if (isset($mapKelas[$kode])) {
                    $idKelasBaru = $mapKelas[$kode];
                    $insertKbm[] = [
                        'id_tp' => $tp,
                        'id_smt' => $smt,
                        'id_kelas' => $idKelasBaru,
                        'kbm_jam_pel' => $kbm->kbm_jam_pel,
                        'kbm_jam_mulai' => $kbm->kbm_jam_mulai,
                        'kbm_jam_selesai' => $kbm->kbm_jam_selesai,
                        'kbm_jml_mapel_hari' => $kbm->kbm_jml_mapel_hari,
                        'istirahat' => $kbm->istirahat,
                        'libur' => $kbm->libur,
                        'upacara' => $kbm->upacara
                    ];
                }
            }
            if (!empty($insertKbm))
                $this->elearningModel->insertBatchKbm($insertKbm);

            // 4. Process Mapel
            $insertMapel = [];
            $lewatKelas = [];

            foreach ($jadwalMapelLalu as $mapel) {
                $kode = $this->normalize($mapel->kode_kelas);
                if (isset($mapKelas[$kode])) {
                    $idKelasBaru = $mapKelas[$kode];
                    $insertMapel[] = [
                        'id_tp' => $tp,
                        'id_smt' => $smt,
                        'id_kelas' => $idKelasBaru,
                        'id_hari' => $mapel->id_hari,
                        'jam_ke' => $mapel->jam_ke,
                        'id_mapel' => $mapel->id_mapel,
                        'dari' => $mapel->dari,
                        'sampai' => $mapel->sampai
                    ];
                } else {
                    // Kode kelas lama tidak lagi ada sebagai rombel periode
                    // ini. Dulu dilewati diam-diam, padahal jadwal lama sudah
                    // terlanjur dihapus di awal transaksi.
                    $lewatKelas[(string) ($mapel->kode_kelas ?: '?')] = true;
                }
            }
            if (!empty($insertMapel))
                $this->elearningModel->insertBatchJadwal($insertMapel);

            // Slot lama periode ini sudah dihapus total, jadi semua tautan
            // jurnal periode ini menunjuk ke baris yang tidak ada lagi.
            (new JurnalGuruModel())->detachStaleJadwalLinks($tp, $smt);

            // 5. Salin penugasan guru (kelas:mapel) dari periode sumber.
            $oldAssign = $this->elearningModel->getGuruAssignmentMap((int) $thnLalu->id_tp, (int) $smtLalu);
            foreach ($jadwalMapelLalu as $mapel) {
                if ((int) $mapel->id_mapel <= 0) continue;

                $kode = $this->normalize($mapel->kode_kelas);
                if (!isset($mapKelas[$kode])) continue;

                // Semua guru pada pasangan kelas:mapel itu ikut disalin.
                // Sebelumya hanya array_key_first() yang dipakai, sehingga
                // guru kedua (mapel yang diampu dua orang) hilang tanpa jejak.
                // Catatan bentuk data: key = id_guru, value = nama_guru.
                $teachers = $oldAssign[(int) $mapel->id_kelas . ':' . (int) $mapel->id_mapel] ?? [];
                foreach (array_keys(is_array($teachers) ? $teachers : []) as $idGuruLama) {
                    $idGuruLama = (int) $idGuruLama;
                    if ($idGuruLama > 0) {
                        $this->assignTeacherPair($tp, $smt, $idGuruLama, (int) $mapKelas[$kode], (int) $mapel->id_mapel);
                    }
                }
            }
            $this->pruneAllUnusedPairAssignments($tp, $smt);

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['status' => false, 'message' => 'Jadwal gagal disalin — jadwal lama Anda tidak diubah.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[copy_jadwal] ' . $e->getMessage() . ' | baris ' . $e->getLine());
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Jadwal gagal disalin — jadwal lama Anda tidak diubah. Silakan ulangi.'
            ]);
        }

        $pesan = 'Jadwal berhasil disalin';
        if (!empty($lewatKelas)) {
            $kodeLewat = array_keys($lewatKelas);
            log_message('warning', '[copy_jadwal] rombel dilewati karena kode tidak dikenali: ' . implode(', ', $kodeLewat));
            $pesan .= ' — ' . count($kodeLewat) . ' rombel tidak ikut tersalin karena kode kelasnya tidak ada pada periode aktif ('
                . implode(', ', array_slice($kodeLewat, 0, 5)) . (count($kodeLewat) > 5 ? ' …' : ')')
                . '. Perbaiki kode rombel lalu salin ulang.';
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => $pesan,
            'lewat' => array_keys($lewatKelas ?? []),
        ]);
    }

    /**
     * Sinkronkan baris istirahat & upacara seluruh kelas dengan pengaturan KBM.
     * Slot mapel yang sudah terisi tidak diubah. Bila $withEmptySlots benar,
     * slot kosong (id_mapel = 0) untuk setiap jam pelajaran juga dibuat.
     */
    private function syncKbmLayout(int $tp, int $smt, bool $withEmptySlots): bool
    {
        $kbmByKelas = $this->elearningModel->getJadwalKbm($tp, $smt);
        if (empty($kbmByKelas)) return true;

        $db = \Config\Database::connect();
        $beres = true;

        foreach ($this->elearningModel->getAllKelasSort($tp, $smt) as $idKelas => $namaKelas) {
            $kbm = $kbmByKelas[$idKelas] ?? null;
            if (!$kbm) continue;

            $mulai = $this->timeToMinutes($kbm->kbm_jam_mulai ?? '') ?? (7 * 60);
            $selesai = $this->timeToMinutes($kbm->kbm_jam_selesai ?? '') ?? (15 * 60);
            if ($selesai <= $mulai) continue;

            $jamMapel = max(1, (int) ($kbm->kbm_jam_pel ?: 45));
            $jmlMapel = max(1, (int) ($kbm->kbm_jml_mapel_hari ?: 10));
            $libur = (string) ($kbm->libur ?? '7');

            $istirahat = json_decode((string) ($kbm->istirahat ?? ''), true);
            if (!is_array($istirahat)) $istirahat = [];

            $upacara = json_decode((string) ($kbm->upacara ?? ''), true);
            if (!is_array($upacara)) $upacara = [];

            for ($hari = 1; $hari <= 7; $hari++) {
                if ($libur !== '0' && $libur === (string) $hari) continue;

                $beres = $this->syncDayLayout(
                    $db,
                    $tp,
                    $smt,
                    (int) $idKelas,
                    $hari,
                    $mulai,
                    $selesai,
                    $jamMapel,
                    $jmlMapel,
                    $istirahat,
                    $upacara,
                    $withEmptySlots
                ) && $beres;
            }
        }

        return $beres;
    }

    private function syncDayLayout($db, int $tp, int $smt, int $idKelas, int $hari, int $mulai, int $selesai, int $jamMapel, int $jmlMapel, array $istirahat, array $upacara, bool $withEmptySlots): bool
    {
        $rows = $db->table('kelas_jadwal_mapel')
            ->where(['id_tp' => $tp, 'id_smt' => $smt, 'id_kelas' => $idKelas, 'id_hari' => $hari])
            ->orderBy('dari', 'ASC')
            ->get()->getResult();

        // Hari tanpa jadwal dibiarkan apa adanya kecuali sedang membuat format penuh.
        if (empty($rows) && !$withEmptySlots) return true;

        $lessons = [];
        foreach ($rows as $row) {
            if ((int) $row->id_mapel <= 0) continue;

            $from = $this->timeToMinutes($row->dari ?? '');
            $to = $this->timeToMinutes($row->sampai ?? '');
            if ($from !== null && $to !== null && $to > $from) $lessons[] = [$from, $to];
        }

        $breaks = [];
        $lastBreak = 0;
        foreach ($istirahat as $item) {
            $ke = (int) ($item['ist'] ?? 0);
            $dur = (int) ($item['dur'] ?? 0);
            if ($ke < 1 || $dur < 1) continue;

            $breaks[$ke] = ($breaks[$ke] ?? 0) + $dur;
            $lastBreak = max($lastBreak, $ke);
        }

        $upacaraHari = (int) ($upacara['id_hari'] ?? 0);
        $upacaraJamKe = max(1, (int) ($upacara['jam_ke'] ?? 1));
        $upacaraDur = (int) ($upacara['durasi'] ?? 0);
        $hasUpacara = $upacaraHari === $hari && $upacaraDur > 0;

        // Istirahat tetap dibuat walaupun jumlahnya melebihi jml mapel/hari.
        $slotCount = max($jmlMapel, $lastBreak);

        $placeholders = [];
        $emptySlots = [];
        $cursor = $mulai;

        $add = function (int $from, int $to, int $idMapel, int $jamKe, bool $placeholder) use (&$placeholders, &$emptySlots, $selesai): void {
            if ($from >= $selesai) return;
            $to = min($to, $selesai);
            if ($to <= $from) return;

            $range = ['start' => $from, 'end' => $to, 'id_mapel' => $idMapel, 'jam_ke' => $jamKe];
            if ($placeholder) {
                $placeholders[] = $range;
            } else {
                $emptySlots[] = $range;
            }
        };

        if ($hasUpacara && $upacaraJamKe === 1) {
            $add($cursor, $cursor + $upacaraDur, -2, 0, true);
            $cursor += $upacaraDur;
        }

        for ($i = 1; $i <= $slotCount; $i++) {
            $add($cursor, $cursor + $jamMapel, 0, $i, false);
            $cursor += $jamMapel;

            if ($hasUpacara && $upacaraJamKe === $i + 1) {
                $add($cursor, $cursor + $upacaraDur, -2, 0, true);
                $cursor += $upacaraDur;
            }

            if (isset($breaks[$i])) {
                $add($cursor, $cursor + $breaks[$i], -1, 0, true);
                $cursor += $breaks[$i];
            }

            if ($cursor >= $selesai) break;
        }

        $overlapsLesson = function (int $from, int $to) use ($lessons): bool {
            foreach ($lessons as $lesson) {
                if ($from < $lesson[1] && $to > $lesson[0]) return true;
            }
            return false;
        };

        $insert = [];
        foreach ($placeholders as $range) {
            if ($overlapsLesson($range['start'], $range['end'])) continue;

            $insert[] = [
                'id_tp' => $tp,
                'id_smt' => $smt,
                'id_kelas' => $idKelas,
                'id_hari' => $hari,
                'jam_ke' => $range['jam_ke'],
                'id_mapel' => $range['id_mapel'],
                'dari' => $this->minutesToTime($range['start']),
                'sampai' => $this->minutesToTime($range['end']),
            ];
        }

        if ($withEmptySlots) {
            foreach ($emptySlots as $range) {
                if ($overlapsLesson($range['start'], $range['end'])) continue;

                $insert[] = [
                    'id_tp' => $tp,
                    'id_smt' => $smt,
                    'id_kelas' => $idKelas,
                    'id_hari' => $hari,
                    'jam_ke' => $range['jam_ke'],
                    'id_mapel' => 0,
                    'dari' => $this->minutesToTime($range['start']),
                    'sampai' => $this->minutesToTime($range['end']),
                ];
            }
        }

        $db->transBegin();
        try {
            $placeholderIds = $withEmptySlots ? [-2, -1, 0] : [-2, -1];

            $db->table('kelas_jadwal_mapel')
                ->where(['id_tp' => $tp, 'id_smt' => $smt, 'id_kelas' => $idKelas, 'id_hari' => $hari])
                ->whereIn('id_mapel', $placeholderIds)
                ->delete();

            if (!empty($insert)) {
                $db->table('kelas_jadwal_mapel')->insertBatch($insert);
            }

            $this->reindexJamKe($tp, $smt, $idKelas, $hari);

            if ($db->transStatus() === false) {
                $db->transRollback();
                log_message('error', "[syncKbmLayout] susunan slot gagal untuk kelas {$idKelas} hari {$hari}");
                return false;
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[syncKbmLayout] ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /** Konversi jam "HH:MM[:SS]" ke menit sejak tengah malam. */
    private function timeToMinutes(?string $time): ?int
    {
        if ($time === null || trim($time) === '') return null;
        if (!preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) return null;

        $hour = (int) $m[1];
        $minute = (int) $m[2];
        if ($hour > 23 || $minute > 59) return null;

        return ($hour * 60) + $minute;
    }

    private function minutesToTime(int $minutes): string
    {
        $minutes = max(0, min((24 * 60) - 1, $minutes));
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** Normalisasi waktu ke HH:MM, atau null bila tidak valid. */
    private function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) return null;

        $hour = (int) $m[1];
        $minute = (int) $m[2];
        if ($hour > 23 || $minute > 59) return null;

        return sprintf('%02d:%02d', $hour, $minute);
    }

    /** Urutkan ulang jam_ke berdasarkan waktu mulai slot pada satu kelas/hari. */
    private function reindexJamKe(int $tp, int $smt, int $idKelas, int $idHari): void
    {
        $db = \Config\Database::connect();
        $rows = $db->table('kelas_jadwal_mapel')
            ->select('id_jadwal, jam_ke')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_kelas', $idKelas)
            ->where('id_hari', $idHari)
            ->orderBy('dari', 'ASC')
            ->orderBy('id_jadwal', 'ASC')
            ->get()->getResult();

        $jamKe = 1;
        foreach ($rows as $row) {
            if ((int) $row->jam_ke !== $jamKe) {
                $db->table('kelas_jadwal_mapel')->where('id_jadwal', $row->id_jadwal)->update(['jam_ke' => $jamKe]);
            }
            $jamKe++;
        }
    }

    /** Tambahkan pasangan kelas:mapel ke penugasan guru pada jabatan_guru. */
    private function assignTeacherPair(int $tp, int $smt, int $idGuru, int $idKelas, int $idMapel): void
    {
        if ($idGuru <= 0 || $idKelas <= 0 || $idMapel <= 0) return;

        $db = \Config\Database::connect();
        $row = $this->jabatanModel->findForGuru($idGuru, $tp, $smt);

        $mapels = $row ? $this->elearningModel->decodeMapelKelasAssignments($row->mapel_kelas) : [];
        $namaMapel = (string) ($db->table('master_mapel')->select('nama_mapel')
            ->where('id_mapel', $idMapel)->get()->getRow()->nama_mapel ?? ('Mapel #' . $idMapel));

        $found = false;
        foreach ($mapels as $i => $item) {
            if (!is_array($item) || (int) ($item['id_mapel'] ?? 0) !== $idMapel) continue;

            $kelasList = isset($item['kelas_mapel']) && is_array($item['kelas_mapel']) ? $item['kelas_mapel'] : [];
            $hasKelas = false;
            foreach ($kelasList as $km) {
                if ((int) (is_array($km) ? ($km['kelas'] ?? 0) : 0) === $idKelas) {
                    $hasKelas = true;
                    break;
                }
            }
            if (!$hasKelas) $kelasList[] = ['kelas' => $idKelas];

            $mapels[$i]['kelas_mapel'] = array_values($kelasList);
            $mapels[$i]['nama_mapel'] = $mapels[$i]['nama_mapel'] ?? $namaMapel;
            $found = true;
            break;
        }

        if (!$found) {
            $mapels[] = [
                'id_mapel' => $idMapel,
                'nama_mapel' => $namaMapel,
                'kelas_mapel' => [['kelas' => $idKelas]],
            ];
        }

        $payload = ['mapel_kelas' => json_encode(array_values($mapels))];
        $this->jabatanModel->upsert($idGuru, $tp, $smt, $payload);
    }

    /** Lepas pasangan kelas:mapel dari guru lain selain guru terpilih. */
    private function removePairFromOtherTeachers(int $tp, int $smt, int $idKelas, int $idMapel, int $keepGuru): void
    {
        if ($idKelas <= 0 || $idMapel <= 0) return;

        $db = \Config\Database::connect();
        $rows = $db->table('jabatan_guru')
            ->select('id_jabatan_guru, id_guru, mapel_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_guru !=', $keepGuru)
            ->get()->getResult();

        foreach ($rows as $row) {
            $mapels = $this->elearningModel->decodeMapelKelasAssignments($row->mapel_kelas);
            $changed = false;

            foreach ($mapels as $i => $item) {
                if (!is_array($item) || (int) ($item['id_mapel'] ?? 0) !== $idMapel) continue;

                $kelasList = isset($item['kelas_mapel']) && is_array($item['kelas_mapel']) ? $item['kelas_mapel'] : [];
                $sisa = [];
                foreach ($kelasList as $km) {
                    $kls = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($kls === $idKelas) {
                        $changed = true;
                        continue;
                    }
                    $sisa[] = $km;
                }

                if (empty($sisa)) {
                    unset($mapels[$i]);
                } else {
                    $mapels[$i]['kelas_mapel'] = array_values($sisa);
                }
            }

            if ($changed) {
                $db->table('jabatan_guru')
                    ->where('id_jabatan_guru', $row->id_jabatan_guru)
                    ->update(['mapel_kelas' => json_encode(array_values($mapels))]);
            }
        }
    }

    /**
     * Hapus pasangan kelas:mapel dari semua guru pada periode, tetapi hanya
     * untuk pasangan yang sudah tidak memiliki slot jadwal.
     */
    private function cleanupPairAssignments(int $tp, int $smt, array $pairs): void
    {
        if (empty($pairs)) return;

        $db = \Config\Database::connect();
        $unused = [];
        foreach ($pairs as $key => $_) {
            [$idKelas, $idMapel] = array_map('intval', explode(':', $key));
            if ($idKelas <= 0 || $idMapel <= 0) continue;

            $masihAda = $db->table('kelas_jadwal_mapel')
                ->where('id_tp', $tp)
                ->where('id_smt', $smt)
                ->where('id_kelas', $idKelas)
                ->where('id_mapel', $idMapel)
                ->countAllResults();

            if ($masihAda === 0) $unused[$idKelas . ':' . $idMapel] = true;
        }

        if (empty($unused)) return;

        $rows = $db->table('jabatan_guru')
            ->select('id_jabatan_guru, mapel_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()->getResult();

        foreach ($rows as $row) {
            $mapels = $this->elearningModel->decodeMapelKelasAssignments($row->mapel_kelas);
            $changed = false;

            foreach ($mapels as $i => $item) {
                if (!is_array($item)) continue;

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = isset($item['kelas_mapel']) && is_array($item['kelas_mapel']) ? $item['kelas_mapel'] : [];
                $sisa = [];

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas > 0 && isset($unused[$idKelas . ':' . $idMapel])) {
                        $changed = true;
                        continue;
                    }
                    $sisa[] = $km;
                }

                if (empty($sisa)) {
                    unset($mapels[$i]);
                } else {
                    $mapels[$i]['kelas_mapel'] = array_values($sisa);
                }
            }

            if ($changed) {
                $db->table('jabatan_guru')
                    ->where('id_jabatan_guru', $row->id_jabatan_guru)
                    ->update(['mapel_kelas' => json_encode(array_values($mapels))]);
            }
        }
    }

    /** Buang penugasan guru yang pasangannya tidak lagi ada di jadwal periode. */
    private function pruneAllUnusedPairAssignments(int $tp, int $smt): void
    {
        $db = \Config\Database::connect();
        $rows = $db->table('kelas_jadwal_mapel')
            ->select('id_kelas, id_mapel')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_mapel >', 0)
            ->get()->getResult();

        $used = [];
        foreach ($rows as $row) {
            $used[(int) $row->id_kelas . ':' . (int) $row->id_mapel] = true;
        }

        $unused = [];
        foreach ($this->elearningModel->getGuruAssignmentMap($tp, $smt) as $key => $_) {
            if (!isset($used[$key])) $unused[$key] = true;
        }

        $this->cleanupPairAssignments($tp, $smt, $unused);
    }

    private function normalize($str)
    {
        return strtoupper(str_replace([' ', '-', '/'], '', $str));
    }

    // ══════════════════════════════════════════════════════════════
    // EXPORT TEMPLATE EXCEL JADWAL PELAJARAN
    // ══════════════════════════════════════════════════════════════

    /**
     * Export template Excel (XML Spreadsheet 2003) untuk import jadwal pelajaran.
     * File bisa dibuka langsung oleh Microsoft Excel tanpa library eksternal.
     */
    public function export_template_jadwal()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        if (!$tp || !$smt) {
            return redirect()->back()->with('error', 'Tahun pelajaran/semester aktif belum diset');
        }

        $kelas   = $this->elearningModel->getAllKelas($tp->id_tp, $smt->id_smt);
        $mapels  = $this->elearningModel->getMapelArray();
        $gurus   = $this->elearningModel->getAllGuru();

        $arrHari = ['1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu'];

        // --- Build XML Spreadsheet 2003 ---
        $xml = '<?xml version="1.0"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' .
            'xmlns:o="urn:schemas-microsoft-com:office:office" ' .
            'xmlns:x="urn:schemas-microsoft-com:office:excel" ' .
            'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ' .
            'xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

        // Styles
        $xml .= '<Styles>' . "\n";
        $xml .= '<Style ss:ID="Header" ss:Name="Header">' . "\n";
        $xml .= '<Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/>' . "\n";
        $xml .= '<Interior ss:Color="#4338CA" ss:Pattern="Solid"/>' . "\n";
        $xml .= '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '</Style>' . "\n";
        $xml .= '<Style ss:ID="Body">' . "\n";
        $xml .= '<Font ss:Size="11"/>' . "\n";
        $xml .= '<Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '</Style>' . "\n";
        $xml .= '<Style ss:ID="Example" ss:Parent="Body">' . "\n";
        $xml .= '<Font ss:Color="#666666"/>' . "\n";
        $xml .= '<Interior ss:Color="#FFF7E6" ss:Pattern="Solid"/>' . "\n";
        $xml .= '</Style>' . "\n";
        $xml .= '</Styles>' . "\n";

        // Worksheet 1: Template
        $xml .= '<Worksheet ss:Name="Template Import Jadwal">' . "\n";
        $xml .= '<Table ss:ExpandedColumnCount="7" ss:ExpandedRowCount="20" x:FullColumns="1" x:FullRows="1">' . "\n";

        // Header row
        $headers = ['Kode Kelas', 'Nama Kelas', 'Hari', 'Mata Pelajaran', 'Kode Guru', 'Jam Mulai', 'Jam Selesai'];
        $xml .= '<Row ss:AutoFitHeight="0">' . "\n";
        foreach ($headers as $h) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        // Example rows
        $contohDitambahkan = false;
        foreach ($kelas as $k) {
            if ($contohDitambahkan) break;
            foreach (array_slice($arrHari, 0, 2, true) as $idHari => $namaHari) {
                if ($contohDitambahkan) break;
                foreach (array_slice($mapels, 0, 1, true) as $idMapel => $namaMapel) {
                    foreach ($gurus as $g) {
                        $xml .= '<Row ss:AutoFitHeight="0">' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">' . htmlspecialchars($k->kode_kelas ?? '') . '</Data></Cell>' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">' . htmlspecialchars($k->nama_kelas ?? '') . '</Data></Cell>' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">' . htmlspecialchars($namaHari) . '</Data></Cell>' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">' . htmlspecialchars($namaMapel) . '</Data></Cell>' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">' . htmlspecialchars($g->kode_guru ?? '') . '</Data></Cell>' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">07:00</Data></Cell>' . "\n";
                        $xml .= '<Cell ss:StyleID="Example"><Data ss:Type="String">07:45</Data></Cell>' . "\n";
                        $xml .= '</Row>' . "\n";
                        $contohDitambahkan = true;
                        break;
                    }
                }
            }
        }

        $xml .= '</Table>' . "\n";
        $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<ProtectObjects>False</ProtectObjects>' . "\n";
        $xml .= '<ProtectScenarios>False</ProtectScenarios>' . "\n";
        $xml .= '</WorksheetOptions>' . "\n";
        $xml .= '</Worksheet>' . "\n";

        // Worksheet 2: Data Referensi (Kelas)
        $xml .= '<Worksheet ss:Name="Data Kelas">' . "\n";
        $xml .= '<Table ss:ExpandedColumnCount="2" ss:ExpandedRowCount="' . (count($kelas) + 2) . '" x:FullColumns="1">' . "\n";
        $xml .= '<Row><Cell ss:StyleID="Header"><Data ss:Type="String">Kode Kelas</Data></Cell>' .
            '<Cell ss:StyleID="Header"><Data ss:Type="String">Nama Kelas</Data></Cell></Row>' . "\n";
        foreach ($kelas as $k) {
            $xml .= '<Row>' .
                '<Cell ss:StyleID="Body"><Data ss:Type="String">' . htmlspecialchars($k->kode_kelas ?? '') . '</Data></Cell>' .
                '<Cell ss:StyleID="Body"><Data ss:Type="String">' . htmlspecialchars($k->nama_kelas ?? '') . '</Data></Cell>' .
                '</Row>' . "\n";
        }
        $xml .= '</Table>' . "\n";
        $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<ProtectObjects>False</ProtectObjects>' . "\n";
        $xml .= '<ProtectScenarios>False</ProtectScenarios>' . "\n";
        $xml .= '</WorksheetOptions>' . "\n";
        $xml .= '</Worksheet>' . "\n";

        // Worksheet 3: Data Referensi (Mapel & Guru)
        $xml .= '<Worksheet ss:Name="Data Mapel & Guru">' . "\n";
        $xml .= '<Table ss:ExpandedColumnCount="4" ss:ExpandedRowCount="' . (max(count($mapels), count($gurus)) + 2) . '" x:FullColumns="1">' . "\n";
        $xml .= '<Row>' .
            '<Cell ss:StyleID="Header"><Data ss:Type="String">Kode Mapel</Data></Cell>' .
            '<Cell ss:StyleID="Header"><Data ss:Type="String">Nama Mapel</Data></Cell>' .
            '<Cell ss:StyleID="Header"><Data ss:Type="String">Kode Guru</Data></Cell>' .
            '<Cell ss:StyleID="Header"><Data ss:Type="String">Nama Guru</Data></Cell>' .
            '</Row>' . "\n";
        $mapelArr = array_values($mapels);
        $guruArr  = array_values($gurus);
        $maxRow   = max(count($mapelArr), count($guruArr));
        for ($i = 0; $i < $maxRow; $i++) {
            $xml .= '<Row>';
            if (isset($mapelArr[$i])) {
                $xml .= '<Cell ss:StyleID="Body"><Data ss:Type="String">' . htmlspecialchars($mapelArr[$i]->id_mapel ?? '') . '</Data></Cell>' .
                    '<Cell ss:StyleID="Body"><Data ss:Type="String">' . htmlspecialchars($mapelArr[$i]->nama_mapel ?? '') . '</Data></Cell>';
            } else {
                $xml .= '<Cell ss:StyleID="Body"><Data ss:Type="String"></Data></Cell>' .
                    '<Cell ss:StyleID="Body"><Data ss:Type="String"></Data></Cell>';
            }
            if (isset($guruArr[$i])) {
                $xml .= '<Cell ss:StyleID="Body"><Data ss:Type="String">' . htmlspecialchars($guruArr[$i]->kode_guru ?? '') . '</Data></Cell>' .
                    '<Cell ss:StyleID="Body"><Data ss:Type="String">' . htmlspecialchars($guruArr[$i]->nama_guru ?? '') . '</Data></Cell>';
            } else {
                $xml .= '<Cell ss:StyleID="Body"><Data ss:Type="String"></Data></Cell>' .
                    '<Cell ss:StyleID="Body"><Data ss:Type="String"></Data></Cell>';
            }
            $xml .= '</Row>' . "\n";
        }
        $xml .= '</Table>' . "\n";
        $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<ProtectObjects>False</ProtectObjects>' . "\n";
        $xml .= '<ProtectScenarios>False</ProtectScenarios>' . "\n";
        $xml .= '</WorksheetOptions>' . "\n";
        $xml .= '</Worksheet>' . "\n";

        $xml .= '</Workbook>' . "\n";

        $filename = 'template_jadwal_' . ($tp->tahun ?? 'tp') . '_smt' . ($smt->smt ?? '0') . '.xml';

        return $this->response
            ->setContentType('application/vnd.ms-excel')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody($xml);
    }

    // ══════════════════════════════════════════════════════════════
    // IMPORT EXCEL JADWAL PELAJARAN
    // ══════════════════════════════════════════════════════════════

    /**
     * Import jadwal dari file Excel XML Spreadsheet 2003 atau CSV.
     */
    public function import_jadwal()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        if (!$tp || !$smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun pelajaran/semester aktif belum diset']);
        }
        $tpId = (int) $tp->id_tp;
        $smtId = (int) $smt->id_smt;

        $file = $this->request->getFile('jadwal_file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON(['status' => false, 'message' => 'File tidak valid atau tidak diunggah']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xml', 'csv', 'xls'], true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format file tidak didukung. Gunakan .xml (template Excel), .csv atau .xls']);
        }

        $content = file_get_contents($file->getTempName());
        if ($content === false) {
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal membaca file']);
        }

        $rows = [];
        if ($ext === 'csv') {
            $rows = $this->parseCsvImport($content);
        } else {
            $rows = $this->parseXmlImport($content);
        }

        if (empty($rows)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada data valid yang ditemukan dalam file']);
        }

        // Lookup maps
        $kelasMap = [];  // kode_kelas => id_kelas
        foreach ($this->elearningModel->getAllKelas($tpId, $smtId) as $k) {
            $kelasMap[strtoupper(trim($k->kode_kelas ?? ''))] = (int) $k->id_kelas;
            $kelasMap[strtoupper(trim($k->nama_kelas ?? ''))] = (int) $k->id_kelas;
        }

        $mapelMap = []; // nama_mapel => id_mapel
        foreach ($this->elearningModel->getMapelArray() as $id => $nama) {
            $mapelMap[strtoupper(trim($nama))] = (int) $id;
        }

        $guruMap = []; // kode_guru => id_guru
        foreach ($this->elearningModel->getAllGuru() as $g) {
            $guruMap[strtoupper(trim($g->kode_guru ?? ''))] = (int) $g->id_guru;
            $guruMap[strtoupper(trim($g->nama_guru ?? ''))] = (int) $g->id_guru;
        }

        $hariMap = [
            'SENIN' => 1, 'SELASA' => 2, 'RABU' => 3, 'KAMIS' => 4,
            'JUMAT' => 5, 'SABTU' => 6, 'MINGGU' => 7,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7,
        ];

        $db = \Config\Database::connect();
        $insert = [];
        $errorRows = [];
        $success = 0;
        $lineNo = 1;

        foreach ($rows as $r) {
            $lineNo++;
            // Skip baris kosong atau baris header
            if (empty(array_filter($r))) continue;

            $kodeKelas   = strtoupper(trim($r[0] ?? ''));
            $namaKelas   = strtoupper(trim($r[1] ?? ''));
            $hari        = strtoupper(trim($r[2] ?? ''));
            $namaMapel   = strtoupper(trim($r[3] ?? ''));
            $kodeGuru    = strtoupper(trim($r[4] ?? ''));
            $jamMulai    = trim($r[5] ?? '');
            $jamSelesai  = trim($r[6] ?? '');

            // Validasi kolom wajib
            if ($kodeKelas === '' && $namaKelas === '') {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Kelas kosong'];
                continue;
            }
            if ($hari === '') {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Hari kosong'];
                continue;
            }
            if ($namaMapel === '') {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Mata pelajaran kosong'];
                continue;
            }
            if ($jamMulai === '' || $jamSelesai === '') {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Jam masuk/keluar kosong'];
                continue;
            }

            // Resolve ID kelas
            $idKelas = $kelasMap[$kodeKelas] ?? $kelasMap[$namaKelas] ?? null;
            if (!$idKelas) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Kelas tidak ditemukan: ' . ($kodeKelas ?: $namaKelas)];
                continue;
            }

            // Resolve ID mapel
            $idMapel = $mapelMap[$namaMapel] ?? null;
            if (!$idMapel) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Mata pelajaran tidak ditemukan: ' . $namaMapel];
                continue;
            }

            // Resolve ID guru (opsional)
            $idGuru = $guruMap[$kodeGuru] ?? null;
            if ($kodeGuru !== '' && !$idGuru) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Guru tidak ditemukan: ' . $kodeGuru];
                continue;
            }

            // Resolve hari
            $idHari = $hariMap[$hari] ?? null;
            if (!$idHari || $idHari < 1 || $idHari > 7) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Hari tidak valid: ' . $hari];
                continue;
            }

            // Validasi waktu
            if (!preg_match('/^(\d{1,2}):(\d{2})$/', $jamMulai, $m1) ||
                !preg_match('/^(\d{1,2}):(\d{2})$/', $jamSelesai, $m2)) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Format jam tidak valid (HH:MM)'];
                continue;
            }
            $jamMulaiNorm  = sprintf('%02d:%02d', (int)$m1[1], (int)$m1[2]);
            $jamSelesaiNorm = sprintf('%02d:%02d', (int)$m2[1], (int)$m2[2]);
            if ($jamMulaiNorm >= $jamSelesaiNorm) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Jam mulai harus lebih awal dari jam selesai'];
                continue;
            }

            // Cek bentrok kelas pada hari yang sama
            $conflict = $db->table('kelas_jadwal_mapel')
                ->where('id_tp', $tpId)
                ->where('id_smt', $smtId)
                ->where('id_kelas', $idKelas)
                ->where('id_hari', $idHari)
                ->where('id_mapel !=', 0)
                ->where('dari <', $jamSelesaiNorm)
                ->where('sampai >', $jamMulaiNorm)
                ->get()->getRow();
            if ($conflict) {
                $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Bentrok jadwal kelas pada ' . substr($conflict->dari, 0, 5) . '-' . substr($conflict->sampai, 0, 5)];
                continue;
            }

            // Cek bentrok guru
            if ($idGuru > 0) {
                $teacherConflict = $db->table('kelas_jadwal_mapel jm')
                    ->select('jm.id_jadwal, jm.id_kelas, jm.id_mapel, jm.dari, jm.sampai')
                    ->where('jm.id_tp', $tpId)
                    ->where('jm.id_smt', $smtId)
                    ->where('jm.id_hari', $idHari)
                    ->where('jm.id_mapel >', 0)
                    ->where('jm.dari <', $jamSelesaiNorm)
                    ->where('jm.sampai >', $jamMulaiNorm)
                    ->get()->getResult();

                foreach ($teacherConflict as $tc) {
                    $pairKey = (int)$tc->id_kelas . ':' . (int)$tc->id_mapel;
                    // Cek apakah guru ini mengajar kelas:mapel tersebut
                    $guruPair = $db->table('jabatan_guru')
                        ->where('id_tp', $tpId)
                        ->where('id_smt', $smtId)
                        ->get()->getResult();
                    $mengajar = false;
                    foreach ($guruPair as $gp) {
                        $mapels = $this->elearningModel->decodeMapelKelasAssignments($gp->mapel_kelas);
                        foreach ($mapels as $m) {
                            if ((int)($m['id_mapel'] ?? 0) === (int)$tc->id_mapel) {
                                foreach (($m['kelas_mapel'] ?? []) as $km) {
                                    if ((int)($km['kelas'] ?? 0) === (int)$tc->id_kelas) {
                                        $mengajar = true;
                                        break;
                                    }
                                }
                                if ($mengajar) break;
                            }
                        }
                        if ($mengajar) break;
                    }
                    if ($mengajar) {
                        $errorRows[] = ['baris' => $lineNo, 'pesan' => 'Bentrok guru: sudah mengajar kelas lain pada jam tersebut'];
                        continue 2;
                    }
                }
            }

            $insert[] = [
                'id_tp'      => $tpId,
                'id_smt'     => $smtId,
                'id_kelas'   => $idKelas,
                'id_hari'    => $idHari,
                'id_mapel'   => $idMapel,
                'dari'       => $jamMulaiNorm,
                'sampai'     => $jamSelesaiNorm,
                'jam_ke'     => 0,
            ];
            $success++;
        }

        if (empty($insert) && !empty($errorRows)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Semua baris gagal diimpor',
                'errors' => $errorRows,
            ]);
        }

        $db->transBegin();
        try {
            if (!empty($insert)) {
                $db->table('kelas_jadwal_mapel')->insertBatch($insert);
            }
            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan ke database']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[import_jadwal] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal mengimpor jadwal']);
        }

        // Reindex jam_ke untuk semua kelas yang terpengaruh
        $touchedKelas = array_unique(array_column($insert, 'id_kelas'));
        $touchedHari  = array_unique(array_column($insert, 'id_hari'));
        foreach ($touchedKelas as $k) {
            foreach ($touchedHari as $h) {
                $this->reindexJamKe($tpId, $smtId, (int)$k, (int)$h);
            }
        }

        // Sync penugasan guru (kelas:mapel) ke jabatan_guru
        foreach ($insert as $row) {
            if ((int) $row['id_mapel'] > 0 && $idGuru > 0) {
                $this->assignTeacherPair($tpId, $smtId, (int) $idGuru, (int) $row['id_kelas'], (int) $row['id_mapel']);
            }
        }

        $pesan = 'Berhasil mengimpor ' . $success . ' jadwal pelajaran';
        if (!empty($errorRows)) {
            $pesan .= ' (' . count($errorRows) . ' baris gagal)';
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => $pesan,
            'inserted' => $success,
            'errors' => $errorRows,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // PARSING HELPERS
    // ══════════════════════════════════════════════════════════════

    /**
     * Parse CSV content to array of rows.
     */
    private function parseCsvImport(string $content): array
    {
        $rows = [];
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $rows[] = str_getcsv($line);
        }
        return $rows;
    }

    /**
     * Parse Excel XML Spreadsheet 2003 content to array of rows.
     */
    private function parseXmlImport(string $content): array
    {
        $rows = [];
        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            return $rows;
        }

        $ns = $xml->getNamespaces(true);
        $ss = $xml->children($ns['ss'] ?? 'urn:schemas-microsoft-com:office:spreadsheet');

        foreach ($ss->Worksheet as $worksheet) {
            $table = $worksheet->Table;
            if (!$table) continue;

            foreach ($table->Row as $row) {
                $rowData = [];
                foreach ($row->Cell as $cell) {
                    $data = $cell->Data;
                    $rowData[] = $data !== null ? (string)$data : '';
                }
                // Skip completely empty rows
                if (count(array_filter($rowData)) > 0) {
                    $rows[] = $rowData;
                }
            }
        }

        return $rows;
    }
}
