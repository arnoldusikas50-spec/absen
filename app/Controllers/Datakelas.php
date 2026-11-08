<?php

namespace App\Controllers;

use App\Models\MasterModel;
use App\Models\DashboardModel;
use App\Models\JabatanGuruModel;
use App\Models\KelasModel;
use App\Models\DropdownModel;
use App\Models\LogModel;

/**
 * Datakelas Controller - Migrated from CI3
 * Manages class/rombel data, student assignments, and promotions
 */
class Datakelas extends BaseController
{
    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;
    protected KelasModel $kelasModel;
    protected DropdownModel $dropdownModel;
    protected JabatanGuruModel $jabatanModel;
    protected LogModel $logModel;

    public function __construct()
    {
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
        $this->kelasModel = new KelasModel();
        $this->dropdownModel = new DropdownModel();
        $this->jabatanModel = new JabatanGuruModel();
        $this->logModel = new LogModel();
    }

    /**
     * Index page - List of classes
     */
    public function index()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return redirect()->to('auth');
        }

        $setting = $this->dashboardModel->getSetting();
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('dashboard')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Kelas',
            'subjudul' => 'Data Kelas',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
        ];

        $kelas = [];
        $kelas_lama = [];

        $kelas = $this->kelasModel->getKelasList($tp->id_tp, $smt->id_smt);
        foreach ($kelas as $k) {
            $k->jml_siswa = $this->masterModel->countSiswaInKelas($k->id_kelas, $tp->id_tp, $smt->id_smt);
        }

        $tp_before = $this->dashboardModel->getTpBefore($tp->tahun);
        if ($tp_before) {
            $kelas_lama = $this->kelasModel->getKelasList($tp_before->id_tp, '2');
        }

        $data['kelas'] = $kelas;
        $data['kelas_lama'] = $kelas_lama;
        $data['jurusan'] = $this->dropdownModel->getDataJurusan();
        $data['level'] = $this->dropdownModel->getAllLevelKelas($setting->jenjang ?? null);
        $data['guru'] = $this->dropdownModel->getAllGuru();

        return view('master/kelas/data', $data);
    }

    /**
     * Detail page - Student list in a class
     */
    public function detail($id)
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $setting = $this->dashboardModel->getSetting();

        $kelas = $this->kelasModel->get_one($id);
        if (!$kelas) {
            return redirect()->to('datakelas')->with('error', 'Kelas tidak ditemukan');
        }
        if (!$tp || !$smt) {
            return redirect()->to('datakelas')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }
        $kelas->siswa = $this->maybe_unserialize($kelas->jumlah_siswa ?? '[]');

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Detail Kelas',
            'subjudul' => 'Detail Kelas',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'kelas' => $kelas,
            'jurusan' => $this->dropdownModel->getDataJurusan(),
            'level' => $this->dropdownModel->getAllLevelKelas($setting->jenjang ?? null),
            'guru' => $this->dropdownModel->getAllGuru(),
            'siswas' => $this->kelasModel->get_siswa_kelas($id, $tp->id_tp, $smt->id_smt),
            'struktur' => $this->kelasModel->getStrukturKelas($id) ?? (object) $this->kelasModel->dummyStruktur(),
        ];

        return view('master/kelas/detail', $data);
    }

    /**
     * Add new class page
     */
    public function add()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('datakelas')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }
        $setting = $this->dashboardModel->getSetting();

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Tambah Kelas',
            'subjudul' => 'Tambah Kelas Baru',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'kelas' => (object) ['nama_kelas' => '', 'kode_kelas' => '', 'jurusan_id' => '', 'level_id' => ''],
            'jurusan' => $this->dropdownModel->getDataJurusan(),
            'level' => $this->dropdownModel->getAllLevelKelas($setting->jenjang ?? null),
            'siswa' => $this->masterModel->getAllSiswa($tp->id_tp, $smt->id_smt, 1000), // All available students
        ];

        return view('master/kelas/add', $data);
    }

    /**
     * Edit class page
     */
    public function edit($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard');
        }

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to('datakelas')->with('error', 'ID kelas tidak valid');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return redirect()->to('datakelas')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }
        $setting = $this->dashboardModel->getSetting();

        $kelas = $this->kelasModel->get_one($id);
        if (!$kelas) {
            return redirect()->to('datakelas')->with('error', 'Kelas tidak ditemukan');
        }
        $kelas->jumlah_siswa = $this->maybe_unserialize($kelas->jumlah_siswa ?? '[]');

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Edit Kelas',
            'subjudul' => 'Edit Data Kelas',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'id_kelas' => $id,
            'kelas' => $kelas,
            'jurusan' => $this->dropdownModel->getDataJurusan(),
            'level' => $this->dropdownModel->getAllLevelKelas($setting->jenjang ?? null),
            'guru' => $this->kelasModel->getWaliKelas($tp->id_tp, $smt->id_smt),
            'siswa' => $this->masterModel->getAllSiswa($tp->id_tp, $smt->id_smt, 2000),
            'siswakelas' => $this->kelasModel->get_siswa_kelas($id, $tp->id_tp, $smt->id_smt),
        ];

        return view('master/kelas/add', $data);
    }

    /**
     * Save/Update class and student assignments
     */
    public function save()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'TP/SMT aktif belum diset']);
        }
        $id_tp = $tp->id_tp;
        $id_smt = $smt->id_smt;

        $db = \Config\Database::connect();

        $id_kelas = (int) ($this->request->getPost('id_kelas') ?? 0);
        $nama_kelas = mb_substr(trim((string) ($this->request->getPost('nama_kelas') ?? '')), 0, 30);
        $kode_kelas = mb_substr(trim((string) ($this->request->getPost('kode_kelas') ?? '')), 0, 20);
        $jurusan_id = (int) ($this->request->getPost('jurusan_id') ?? 0);
        $level_id = (int) ($this->request->getPost('level_id') ?? 0);
        $guru_id = (int) ($this->request->getPost('guru_id') ?? 0);
        $siswasRaw = $this->request->getPost('siswa') ?? [];

        if ($nama_kelas === '' || mb_strlen($nama_kelas) < 2) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama kelas minimal 2 karakter']);
        }
        if ($id_kelas > 0 && !$db->table('master_kelas')->where('id_kelas', $id_kelas)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak ditemukan']);
        }

        // Validasi id siswa: integer, ada di master, unik
        $siswas = [];
        if (is_array($siswasRaw)) {
            $seen = [];
            foreach ($siswasRaw as $sid) {
                $sid = (int) $sid;
                if ($sid > 0 && !isset($seen[$sid])) {
                    $seen[$sid] = true;
                    $siswas[] = $sid;
                }
            }
        }
        if ($siswas) {
            $validIds = [];
            foreach ($db->table('master_siswa')->select('id_siswa')->whereIn('id_siswa', $siswas)->get()->getResult() as $r) {
                $validIds[(int) $r->id_siswa] = true;
            }
            $siswas = array_values(array_filter($siswas, fn($sid) => isset($validIds[$sid])));
        }

        // Validasi wali kelas: harus jabatan_guru valid pada TP/SMT ini
        if ($guru_id > 0 && !$this->jabatanModel->findForGuru($guru_id, (int) $id_tp, (int) $id_smt)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data wali kelas tidak valid']);
        }

        // level_id punya FK ke level_kelas.id_level dan jurusan_id ikut
        // di-join ke master_jurusan. Nilainya datang dari dropdown, jadi nilai
        // lama/seretan bisa tidak dikenal lagi — tanpa cek di sini query-nya
        // gagal oleh FK (update) atau rombel diam-diam kehilangan level/jurusan.
        if ($level_id <= 0 || !$db->table('level_kelas')->where('id_level', $level_id)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Level kelas tidak valid — pilih level dari daftar']);
        }
        if ($jurusan_id > 0 && !$db->table('master_jurusan')->where('id_jurusan', $jurusan_id)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jurusan tidak valid — pilih jurusan dari daftar']);
        }

        // Build jumlah_siswa JSON for storing in master_kelas
        $siswakelas = [];
        foreach ($siswas as $id_siswa) {
            $siswakelas[] = ['id' => $id_siswa];
        }
        $jumlah = json_encode($siswakelas);

        $insert = [
            'nama_kelas' => $nama_kelas,
            'kode_kelas' => $kode_kelas !== '' ? $kode_kelas : null,
            'id_tp' => $id_tp,
            'id_smt' => $id_smt,
            'jurusan_id' => $jurusan_id > 0 ? $jurusan_id : null,
            'level_id' => $level_id,
            'guru_id' => $guru_id,
            'jumlah_siswa' => $jumlah,
        ];

        $id_new = null;

        $db->transBegin();
        try {
            if ($id_kelas > 0) {
                if ($this->masterModel->updateData('master_kelas', $insert, 'id_kelas', $id_kelas) === false) {
                    throw new \RuntimeException('Update master_kelas gagal: ' . json_encode($db->error()));
                }
                $this->logModel->saveLog(2, 'Mengupdate kelas: ' . $nama_kelas);
            } else {
                if ($this->masterModel->create('master_kelas', $insert) === false) {
                    throw new \RuntimeException('Insert master_kelas gagal: ' . json_encode($db->error()));
                }
                $id_new = $db->insertID();
                if (!$id_new) {
                    throw new \RuntimeException('Gagal membuat kelas');
                }
                $id_kelas = $id_new;
                $this->logModel->saveLog(1, 'Menambah kelas: ' . $nama_kelas);
            }

            // Status wali tersimpan di jabatan_guru (level 4 + id_kelas)
            // dan master_kelas.guru_id. Keduanya wajib ikut berubah agar
            // dashboard dan scoping presensi tidak melihat wali berbeda.
            if ($guru_id > 0) {
                $this->jabatanModel->releaseWaliKelas((int) $id_tp, (int) $id_smt, $id_kelas, $guru_id);
                $this->jabatanModel->upsert($guru_id, (int) $id_tp, (int) $id_smt, [
                    'id_jabatan' => '4',
                    'id_kelas' => $id_kelas,
                ]);
                $this->jabatanModel->syncWaliToMasterKelas($guru_id, (int) $id_tp, (int) $id_smt, $id_kelas);
            } else {
                $this->jabatanModel->releaseWaliKelas((int) $id_tp, (int) $id_smt, $id_kelas);
            }

            // Penempatan siswa. kelas_siswa dikenali dari pasangan unik
            // (id_tp, id_smt, id_siswa). Kode lama menurunkan PK dari
            // concat(id_tp, id_smt, id_siswa) lalu memakai REPLACE — concat itu
            // ambigu (tp 6 + smt 11 + siswa 1 = "6111" sama dengan
            // tp 61 + smt 1 + siswa ... ), sehingga REPLACE dapat menghapus
            // enrollment siswa yang sama sekali berbeda.
            $terpilih = array_flip($siswas);
            $terpasang = 0;
            $gagal = 0;

            // Pertama, siswa yang ada di rombel ini saat ini.
            $existing_siswa = $this->kelasModel->get_status_siswa_kelas($id_kelas, $id_tp, $id_smt);

            if (!$id_new) {
                // Yang tidak lagi dicentang dikeluarkan dari rombel (id_kelas = 0);
                // barisnya tetap ada supaya PK dan riwayat tidak berubah.
                foreach ($existing_siswa as $idSiswaLama => $barisLama) {
                    if (isset($terpilih[$idSiswaLama])) {
                        continue;
                    }
                    if ($this->tempatkanSiswa($db, (int) $idSiswaLama, 0, (int) $id_tp, (int) $id_smt, (int) $barisLama->id_kelas_siswa)) {
                        $terpasang++;
                    } else {
                        $gagal++;
                    }
                }
            }

            foreach ($siswas as $idSiswa) {
                if ($this->tempatkanSiswa($db, (int) $idSiswa, (int) $id_kelas, (int) $id_tp, (int) $id_smt)) {
                    $terpasang++;
                } else {
                    $gagal++;
                }
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datakelas::save] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan kelas']);
        }

        if ($gagal > 0) {
            log_message('error', '[Datakelas::save] ' . $gagal . ' penempatan siswa gagal (terpasang ' . $terpasang . ')');
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Kelas tersimpan, tetapi ' . $gagal . ' penempatan siswa gagal — periksa kembali daftar siswa rombel ini.'
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Kelas tersimpan (' . count($siswas) . ' siswa)',
            'siswa' => $terpasang
        ]);
    }

    /**
     * Pasangkan satu siswa ke satu rombel pada periode aktif lewat kunci alami
     * (id_tp, id_smt, id_siswa). PK tidak pernah ikut ditulis, jadi barisnya
     * tetap baris yang sama. $idKelas 0 berarti keluarkan dari rombel: kolom
     * id_kelas diisi NULL, bukan 0, karena statistik "tanpa kelas" dan join
     * lain membaca IS NULL.
     */
    private function tempatkanSiswa($db, int $idSiswa, int $idKelas, int $idTp, int $idSmt, int $pkKnown = 0): bool
    {
        if ($idSiswa <= 0) {
            return false;
        }

        $idKelasSiswa = $pkKnown;
        if ($idKelasSiswa <= 0) {
            $baris = $db->table('kelas_siswa')
                ->select('id_kelas_siswa')
                ->where('id_tp', $idTp)
                ->where('id_smt', $idSmt)
                ->where('id_siswa', $idSiswa)
                ->get()->getRowArray();
            $idKelasSiswa = (int) ($baris['id_kelas_siswa'] ?? 0);
        }

        if ($idKelasSiswa > 0) {
            return $db->table('kelas_siswa')
                ->where('id_kelas_siswa', $idKelasSiswa)
                ->update(['id_kelas' => $idKelas > 0 ? $idKelas : null]) !== false;
        }

        if ($idKelas === 0) {
            return true;
        }

        return $db->table('kelas_siswa')->insert([
            'id_kelas_siswa' => $this->masterModel->generateKelasSiswaId(),
            'id_siswa' => $idSiswa,
            'id_kelas' => $idKelas,
            'id_tp'    => $idTp,
            'id_smt'   => $idSmt,
        ]) !== false;
    }

    /**
     * Bulk create classes from modal
     */
    public function bulk_create()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON(['success' => false, 'message' => 'TP/SMT aktif belum diset']);
        }

        $classes = json_decode($this->request->getPost('kelas') ?? '[]');
        if (!is_array($classes) || empty($classes)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada kelas yang dikirim']);
        }
        if (count($classes) > 50) {
            return $this->response->setJSON(['success' => false, 'message' => 'Maksimal 50 kelas sekaligus']);
        }

        $db = \Config\Database::connect();

        // Nilai yang dipakai bersama: level valid + nama yang sudah ada di periode ini.
        $levelValid = [];
        foreach ($db->table('level_kelas')->select('id_level')->get()->getResult() as $l) {
            $levelValid[(int) $l->id_level] = true;
        }
        $namaTerpakai = [];
        foreach ($db->table('master_kelas')->select('nama_kelas')
            ->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)->get()->getResult() as $n) {
            $namaTerpakai[mb_strtolower(trim((string) $n->nama_kelas))] = true;
        }

        $insert_count = 0;
        $lewat = [];

        // Satu transaksi: sebagian kelas terbentuk + sebagian gagal menyisakan
        // rombel rangkap yang membingutkan kenaikan kelas (target dicari by nama).
        $db->transBegin();
        try {
            foreach ($classes as $c) {
                $nama = mb_substr(trim((string) ($c->nama_kelas ?? '')), 0, 30);
                $kode = mb_substr(trim((string) ($c->kode_kelas ?? '')), 0, 20);
                $level = (int) ($c->level_id ?? 0);

                if (mb_strlen($nama) < 2) {
                    $lewat[] = $nama !== '' ? $nama : '(tanpa nama)';
                    continue;
                }
                if (!isset($levelValid[$level])) {
                    $lewat[] = $nama . ' (level tidak valid)';
                    continue;
                }
                if (isset($namaTerpakai[mb_strtolower($nama)])) {
                    $lewat[] = $nama . ' (sudah ada)';
                    continue;
                }

                // id_tp/id_smt selalu dari server, bukan dari payload: nama rombel
                // untuk periode lain tidak boleh bisa diselipkan dari luar.
                $tersimpan = $db->table('master_kelas')->insert([
                    'nama_kelas' => $nama,
                    'kode_kelas' => $kode !== '' ? $kode : null,
                    'id_tp' => (int) $tp->id_tp,
                    'id_smt' => (int) $smt->id_smt,
                    'level_id' => $level,
                    'jurusan_id' => null,
                    'guru_id' => 0,
                    'jumlah_siswa' => '[]',
                ]);
                if ($tersimpan === false) {
                    throw new \RuntimeException('Insert master_kelas gagal: ' . json_encode($db->error()));
                }

                $namaTerpakai[mb_strtolower($nama)] = true;
                $insert_count++;
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Pembuatan kelas gagal disimpan — tidak ada yang dibuat.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datakelas::bulk_create] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Pembuatan kelas gagal disimpan.']);
        }

        $this->logModel->saveLog(1, "Bulk create $insert_count kelas" . ($lewat ? ', ' . count($lewat) . ' dilewati' : ''));

        if ($insert_count === 0) {
            return $this->response->setJSON([
                'success' => false,
                'insert' => 0,
                'message' => 'Tidak ada kelas dibuat — ' . implode(', ', array_slice($lewat, 0, 5))
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'insert' => $insert_count,
            'message' => $insert_count . ' kelas dibuat' . ($lewat ? '; ' . count($lewat) . ' dilewati' : '')
        ]);
    }

    /**
     * Delete class
     */
    public function hapus($id)
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['kelas' => false, 'message' => 'Unauthorized']);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['kelas' => false, 'message' => 'ID kelas tidak valid']);
        }

        $db = \Config\Database::connect();
        $kelas = $db->table('master_kelas')->where('id_kelas', $id)->get()->getRowArray();
        if (!$kelas) {
            return $this->response->setJSON(['kelas' => false, 'message' => 'Kelas tidak ditemukan']);
        }

        // Enrollment tidak dihapus: barisnya adalah catatan "siswa ini aktif pada
        // periode itu". Dihapus, siswa hilang dari daftar periode & rekap.
        $tertaut = (int) $db->table('kelas_siswa')->where('id_kelas', $id)->countAllResults();

        $db->transBegin();
        try {
            if ($tertaut > 0 && $db->table('kelas_siswa')->where('id_kelas', $id)->update(['id_kelas' => null]) === false) {
                throw new \RuntimeException('Lepas enrollment gagal: ' . json_encode($db->error()));
            }

            $this->jabatanModel->releaseWaliKelas((int) $kelas['id_tp'], (int) $kelas['id_smt'], $id);

            if ($this->masterModel->hapus('master_kelas', [$id], 'id_kelas') === false) {
                throw new \RuntimeException('Hapus master_kelas gagal: ' . json_encode($db->error()));
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['kelas' => false, 'message' => 'Kelas gagal dihapus — tidak ada yang diubah.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datakelas::hapus] ' . $e->getMessage());
            return $this->response->setJSON(['kelas' => false, 'message' => 'Kelas gagal dihapus.']);
        }

        $this->logModel->saveLog(3, 'Menghapus kelas ID: ' . $id . ' (' . $kelas['nama_kelas'] . ', ' . $tertaut . ' siswa dilepas)');

        return $this->response->setJSON([
            'kelas' => true,
            'message' => 'Kelas terhapus' . ($tertaut > 0 ? '; ' . $tertaut . ' siswa dilepas dari rombel ini' : '')
        ]);
    }

    public function manage()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $setting = $this->dashboardModel->getSetting();
        if (!$tp || !$smt) {
            return redirect()->to('datakelas')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Manage Kelas',
            'subjudul' => 'Atur Siswa Per Semester',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
            'kelas' => $this->kelasModel->getNamaKelasByNama($tp->id_tp, '1'),
            'kelas2' => $this->kelasModel->getNamaKelasByNama($tp->id_tp, '2'),
        ];

        return view('master/kelas/persemester', $data);
    }

    public function copyallfromsmt1()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        if (!$tp) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tahun pelajaran aktif belum diset']);
        }

        $id_kelas_smt1 = $this->request->getPost('kelas_satu');
        if (!is_array($id_kelas_smt1) || empty($id_kelas_smt1)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada kelas sumber yang dikirim']);
        }

        $db = \Config\Database::connect();

        $row_insert = 0;
        $dipakai = 0;
        $terproses = 0;
        $gagal = 0;
        $siswa_gagal = 0;
        $db->transBegin();
        try {
            foreach ($id_kelas_smt1 as $id) {
                $kls_smt1 = $db->table('master_kelas')->where('id_kelas', (int) $id)->get()->getRow();
                if (!$kls_smt1) {
                    $gagal++;
                    continue;
                }

                // Nama rombel sama di semester 2 = sudah pernah disalin. Menyisip
                // lagi akan menghasilkan dua rombel dengan nama identik, dan
                // kenaikan kelas mencari tujuan lewat nama — tabrakan.
                $sudah = $db->table('master_kelas')
                    ->select('id_kelas')
                    ->where('nama_kelas', $kls_smt1->nama_kelas)
                    ->where('id_tp', (int) $tp->id_tp)
                    ->where('id_smt', '2')
                    ->get()->getRowArray();
                $baru = !$sudah;
                $new_id = $sudah ? (int) $sudah['id_kelas'] : 0;

                if ($baru) {
                    $insert_kelas = [
                        'nama_kelas' => $kls_smt1->nama_kelas,
                        'kode_kelas' => $kls_smt1->kode_kelas,
                        'id_tp' => (int) $tp->id_tp,
                        'id_smt' => '2',
                        'jurusan_id' => $kls_smt1->jurusan_id,
                        'level_id' => $kls_smt1->level_id,
                        'guru_id' => $kls_smt1->guru_id,
                        'jumlah_siswa' => $kls_smt1->jumlah_siswa,
                    ];
                    if ($db->table('master_kelas')->insert($insert_kelas) === false) {
                        throw new \RuntimeException('Salin rombel ke semester 2 gagal: ' . json_encode($db->error()));
                    }
                    $new_id = (int) $db->insertID();
                    if (!$new_id) {
                        throw new \RuntimeException('Salin rombel gagal: id kelas tujuan tidak diperoleh');
                    }
                }

                // Wali kelas pada periode tujuan ikut dicatat di jabatan_guru
                // agar dua sumber wali tetap sinkron setelah penyalinan.
                if ((int) $kls_smt1->guru_id > 0) {
                    $this->jabatanModel->upsert((int) $kls_smt1->guru_id, (int) $tp->id_tp, 2, [
                        'id_jabatan' => '4',
                        'id_kelas' => $new_id,
                    ]);
                    $this->jabatanModel->syncWaliToMasterKelas(
                        (int) $kls_smt1->guru_id,
                        (int) $tp->id_tp,
                        2,
                        $new_id
                    );
                }

                $siswas = $db->table('kelas_siswa')
                    ->where(['id_kelas' => (int) $id, 'id_tp' => (int) $tp->id_tp, 'id_smt' => '1'])
                    ->get()->getResult();

                // Lewat kunci alami (tp, smt, siswa): menyalin dua kali cukup
                // memindahkan rombel, tidak menabrak unique key dan tidak
                // menyisakan duplikat.
                foreach ($siswas as $siswa) {
                    if (!$this->tempatkanSiswa($db, (int) $siswa->id_siswa, $new_id, (int) $tp->id_tp, 2)) {
                        $siswa_gagal++;
                    }
                }
                if ($baru) {
                    $row_insert++;
                } else {
                    $dipakai++;
                }
                $terproses++;
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Penyalinan kelas gagal disimpan — tidak ada yang diubah.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datakelas::copyallfromsmt1] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Penyalinan kelas gagal disimpan.']);
        }

        if ($siswa_gagal > 0) {
            log_message('warning', '[Datakelas::copyallfromsmt1] ' . $siswa_gagal . ' siswa gagal dipasangkan');
        }

        return $this->response->setJSON([
            'success' => $terproses > 0,
            'insert' => $row_insert,
            'message' => $row_insert . ' kelas dibuat di semester 2'
                . ($dipakai ? '; ' . $dipakai . ' rombel tujuan sudah ada dan dipakai ulang' : '')
                . ($gagal ? '; ' . $gagal . ' kelas sumber tidak ditemukan' : '')
                . ($siswa_gagal ? '; ' . $siswa_gagal . ' siswa gagal dipasangkan' : '')
        ]);
    }

    public function kenaikan()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $setting = $this->dashboardModel->getSetting();
        if (!$tp || !$smt) {
            return redirect()->to('datakelas')->with('error', 'Tahun Pelajaran / Semester aktif belum diset');
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Manage Kelas',
            'subjudul' => 'Kenaikan Kelas/Rombel',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => $this->dashboardModel->getTahun(),
            'smt' => $this->dashboardModel->getSemester(),
        ];

        $kelas_selected = $this->request->getGet('kelas');
        $type_selected = $this->request->getGet('type');
        $data['kelas_selected'] = $kelas_selected;
        $data['type_selected'] = $type_selected;

        $tpBefore = $this->dashboardModel->getTpBefore($tp->tahun);
        $data['tpBefore'] = $tpBefore;

        if ($tpBefore) {
            $data['kelas_lama'] = $this->kelasModel->getNamaKelasByNama($tpBefore->id_tp, '2');
            if ($kelas_selected != null) {
                $data['siswas'] = $this->kelasModel->get_siswa_kelas($kelas_selected, $tpBefore->id_tp, '2');
                $kelas_now = $this->kelasModel->get_one($kelas_selected);

                $data['kelases'] = $this->kelasModel->getNamaKelasByNama($tp->id_tp, '1');

                if ($kelas_now) {
                    $level_now = $kelas_now->level_id;
                    $level_next = $level_now + 1;
                    $data['level'] = $level_next;
                }

                $data['buat_kelas_baru'] = $this->group_kelas_by_level($data['kelases']);
                $data['all_kelas_baru'] = $this->get_kelas_next($data['kelas_lama']);

                $siswa_baru = $this->kelasModel->get_siswa_kelas_baru($tp->id_tp, '1');
                $data['siswa_kelas_baru'] = [];
                foreach ($siswa_baru as $sb) {
                    $data['siswa_kelas_baru'][$sb->id_siswa] = $sb;
                }
            }
        }

        return view('master/kelas/naikkelas', $data);
    }

    private function group_kelas_by_level($kelases)
    {
        $ret = [];
        foreach ($kelases as $id => $nama) {
            $arr = explode(' ', $nama);
            $level = $arr[0];
            if (!isset($ret[$level]))
                $ret[$level] = [];
            $ret[$level][$id] = $nama;
        }
        return $ret;
    }

    private function get_kelas_next($kelas_lama)
    {
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $kelas_baru = $this->kelasModel->getNamaKelasByNama($tp->id_tp, $smt->id_smt);

        $ret = [];
        foreach ($kelas_lama as $id_lama => $nama_lama) {
            $nama_baru = $this->get_next_name($nama_lama);
            $id_baru = array_search($nama_baru, $kelas_baru);
            $ret[$id_lama] = $id_baru ? $id_baru : '0';
        }
        return $ret;
    }

    private function get_next_name($name)
    {
        $arr = explode(' ', $name);
        if (count($arr) > 1) {
            $level = $arr[0];
            $next_level = $this->is_roman($level) ? $this->roman_to_int($level) + 1 : $level + 1;
            $arr[0] = $this->is_roman($level) ? $this->int_to_roman($next_level) : $next_level;
        }
        return implode(' ', $arr);
    }

    private function is_roman($str)
    {
        return preg_match('/^[IVXLCDM]+$/', $str);
    }

    private function roman_to_int($roman)
    {
        $romans = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];
        $result = 0;
        foreach ($romans as $key => $value) {
            while (strpos($roman, $key) === 0) {
                $result += $value;
                $roman = substr($roman, strlen($key));
            }
        }
        return $result;
    }

    private function int_to_roman($number)
    {
        $map = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }

    public function naikkelas()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Unauthorized']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        if (!$tp) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Tahun pelajaran aktif belum diset']);
        }
        $id_tp = (int) $tp->id_tp;
        $id_smt = 1;

        $kelas_json = $this->request->getPost('kelas');
        $kelas = json_decode($kelas_json ?? '[]');
        $db = \Config\Database::connect();

        if (!is_array($kelas) || empty($kelas)) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Tidak ada data kelas yang dikirim']);
        }

        // Nama rombel tujuan -> id_kelas, hanya untuk periode aktif.
        $petaNama = $this->kelasModel->getNamaKelasByNama($id_tp, $id_smt);

        // id_siswa datang dari payload; kelas_siswa tidak punya FK, jadi siswa
        // yang tidak ada di master_siswa akan menjadi baris yatim yang muncul
        // di daftar periode tapi tidak bisa dicari di halaman mana pun.
        $idSiswaDikirim = [];
        foreach ($kelas as $kls) {
            $sid = (int) ($kls->id_siswa ?? 0);
            if ($sid > 0) {
                $idSiswaDikirim[$sid] = true;
            }
        }
        $siswaValid = [];
        if ($idSiswaDikirim) {
            foreach ($db->table('master_siswa')->select('id_siswa')
                ->whereIn('id_siswa', array_keys($idSiswaDikirim))->get()->getResult() as $s) {
                $siswaValid[(int) $s->id_siswa] = true;
            }
        }

        $row_insert = 0;
        $lewat = 0;
        $db->transBegin();
        try {
            foreach ($kelas as $kls) {
                $idSiswa = (int) ($kls->id_siswa ?? 0);
                $namaBaru = (string) ($kls->nama_kls_baru ?? '');
                $kls_id = $namaBaru === '' ? false : array_search($namaBaru, $petaNama, true);
                if ($idSiswa <= 0 || !isset($siswaValid[$idSiswa]) || !$kls_id) {
                    $lewat++;
                    continue;
                }
                if ($this->tempatkanSiswa($db, $idSiswa, (int) $kls_id, $id_tp, $id_smt)) {
                    $row_insert++;
                } else {
                    $lewat++;
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['res' => 0, 'message' => 'Kenaikan kelas gagal disimpan — tidak ada yang diubah.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datakelas::naikkelas] ' . $e->getMessage());
            return $this->response->setJSON(['res' => 0, 'message' => 'Kenaikan kelas gagal disimpan.']);
        }

        if ($lewat > 0) {
            log_message('warning', '[Datakelas::naikkelas] ' . $lewat . ' siswa dilewati (rombel tujuan tidak ditemukan)');
        }

        return $this->response->setJSON([
            'res' => $row_insert,
            'message' => $row_insert > 0
                ? $row_insert . ' siswa dipindahkan' . ($lewat > 0 ? '; ' . $lewat . ' dilewati karena rombel tujuan tidak ada' : '')
                : 'Tidak ada siswa yang dipindahkan — rombel tujuan tidak ditemukan di periode aktif.'
        ]);
    }

    public function naikkan_semua()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Unauthorized']);
        }

        $tp = $this->dashboardModel->getTahunActive();
        if (!$tp) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Tahun pelajaran aktif belum diset']);
        }
        $tpBefore = $this->dashboardModel->getTpBefore($tp->tahun);
        if (!$tpBefore) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Tahun pelajaran sebelumnya tidak ditemukan — tidak ada yang bisa dinaikkan.']);
        }
        $id_tp = (int) $tp->id_tp;
        $id_smt = 1;

        $kelas_baru = $this->request->getPost('kelas_baru');
        $db = \Config\Database::connect();

        if (!is_array($kelas_baru) || empty($kelas_baru)) {
            return $this->response->setJSON(['res' => 0, 'message' => 'Tidak ada pemetaan rombel yang dikirim']);
        }

        // Rombel tujuan wajib ada di periode aktif; yang tidak valid dikumpulkan
        // supaya bisa dilaporkan, bukan diam-diam dilewati.
        $petaKelasAktif = $this->kelasModel->getNamaKelasByNama($id_tp, $id_smt);

        $row_insert = 0;
        $lewat = [];
        $db->transBegin();
        try {
            foreach ($kelas_baru as $id_lama => $id_baru) {
                $id_baru = (int) $id_baru;
                if ($id_baru <= 0) {
                    continue;
                }
                if (!isset($petaKelasAktif[$id_baru])) {
                    $lewat[] = (int) $id_baru;
                    continue;
                }

                $siswas = $db->table('kelas_siswa')
                    ->select('id_siswa')
                    ->where(['id_kelas' => (int) $id_lama, 'id_tp' => (int) $tpBefore->id_tp, 'id_smt' => '2'])
                    ->get()->getResult();

                foreach ($siswas as $siswa) {
                    if ($this->tempatkanSiswa($db, (int) $siswa->id_siswa, $id_baru, $id_tp, $id_smt)) {
                        $row_insert++;
                    }
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['res' => 0, 'message' => 'Kenaikan kelas massal gagal disimpan — tidak ada yang diubah.']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Datakelas::naikkan_semua] ' . $e->getMessage());
            return $this->response->setJSON(['res' => 0, 'message' => 'Kenaikan kelas massal gagal disimpan.']);
        }

        if ($lewat) {
            log_message('warning', '[Datakelas::naikkan_semua] rombel tujuan dilewati: ' . implode(', ', $lewat));
        }

        return $this->response->setJSON([
            'res' => $row_insert,
            'message' => $row_insert . ' siswa dinaikkan'
                . ($lewat ? '; ' . count($lewat) . ' rombel tujuan tidak ada di periode aktif dan dilewati' : '')
        ]);
    }
}
