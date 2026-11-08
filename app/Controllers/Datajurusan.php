<?php

namespace App\Controllers;

use App\Models\MasterModel;
use App\Models\DashboardModel;

/**
 * Datajurusan Controller - Migrated from CI3
 */
class Datajurusan extends BaseController
{
    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
    }

    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $mapels = $this->masterModel->getAllMapel();
        $mapel_peminatan = [];
        foreach ($mapels as $m) {
            $mapel_peminatan[] = [
                'id_mapel' => $m->id_mapel,
                'nama_mapel' => $m->nama_mapel
            ];
        }

        $data = [
            'judul' => 'Data Jurusan',
            'subjudul' => 'Kelola Data Jurusan/Program',
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
            'user' => $this->getUser(),
            'mapel_peminatan' => $mapel_peminatan
        ];

        return view('master/jurusan/data', $data);
    }

    /**
     * Urai CSV mapel_peminatan ("10,11,12,") menjadi array id integer.
     */
    private function parseMapelPeminatan(?string $csv): array
    {
        if ($csv === null || $csv === '') return [];
        $ids = [];
        foreach (explode(',', $csv) as $part) {
            $id = (int) trim($part);
            if ($id > 0) $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }

    /**
     * Peta id_mapel => nama_mapel untuk label tampilan.
     */
    private function mapelNameMap(): array
    {
        $map = [];
        foreach ($this->masterModel->getAllMapel() as $m) {
            $map[(int) $m->id_mapel] = $m->nama_mapel;
        }
        return $map;
    }

    public function data()
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $jurusans = $this->masterModel->getAllJurusan();
        $mapelNames = $this->mapelNameMap();

        $data = [];
        foreach ($jurusans as $i => $j) {
            $peminatanIds = $this->parseMapelPeminatan($j->mapel_peminatan ?? null);
            $peminatanLabels = [];
            foreach ($peminatanIds as $mid) {
                $peminatanLabels[] = $mapelNames[$mid] ?? ('Mapel #' . $mid);
            }
            $locked = isset($j->deletable) && (int) $j->deletable === 0;
            $data[] = [
                'no' => $i + 1,
                'id_jurusan' => $j->id_jurusan,
                'kode_jurusan' => $j->kode_jurusan ?? $j->singkatan ?? '-',
                'nama_jurusan' => $j->nama_jurusan ?? '-',
                'mapel_peminatan' => $peminatanLabels,
                'mapel_count' => count($peminatanLabels),
                'locked' => $locked,
                'aksi' => '<div class="inline-flex items-center gap-1.5">
                                <button type="button" class="btn-edit inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors shadow-xs" data-id="' . (int) $j->id_jurusan . '" title="Edit Jurusan">
                                    <i class="bi bi-pencil-square text-sm"></i>
                                </button>'
                    . ($locked
                        ? '<span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-300" title="Data bawaan sistem — tidak dapat dihapus"><i class="bi bi-lock-fill text-sm"></i></span>'
                        : '<button type="button" class="btn-hapus inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors shadow-xs" data-id="' . (int) $j->id_jurusan . '" title="Hapus Jurusan"><i class="bi bi-trash3 text-sm"></i></button>')
                    . '</div>',
            ];
        }

        // For serverSide: true, we need draw from POST
        $draw = $this->request->getPost('draw') ?? $this->request->getGet('draw') ?? 1;

        return $this->response->setJSON([
            'draw' => intval($draw),
            'recordsTotal' => count($jurusans),
            'recordsFiltered' => count($jurusans),
            'data' => $data
        ]);
    }

    /**
     * Validasi + normalisasi payload jurusan. Return [data|pesan error].
     */
    private function validateJurusanPayload(?int $ignoreId = null): array
    {
        $nama = trim((string) ($this->request->getPost('nama_jurusan') ?? ''));
        $kode = trim((string) ($this->request->getPost('kode_jurusan') ?? ''));

        if (mb_strlen($nama) < 3) {
            return [null, 'Nama jurusan minimal 3 karakter'];
        }
        // master_jurusan.nama_jurusan adalah varchar(30). Dulu nilai yang lebih
        // panjang dipotong diam-diam; admin mengira menyimpan "Informatika (Rekayasa
        // Perangkat Lunak)" padahal yang tersimpan potongan 30 karakter.
        if (mb_strlen($nama) > 30) {
            return [null, 'Nama jurusan terlalu panjang (' . mb_strlen($nama) . ' karakter, maksimal 30)'];
        }
        if (mb_strlen($kode) > 10) {
            return [null, 'Kode jurusan maksimal 10 karakter'];
        }
        if ($kode !== '' && !preg_match('/^[A-Za-z0-9._-]+$/', $kode)) {
            return [null, 'Kode jurusan hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung'];
        }

        $db = \Config\Database::connect();
        if ($kode !== '') {
            $dup = $db->table('master_jurusan')->where('kode_jurusan', $kode);
            if ($ignoreId) $dup = $dup->where('id_jurusan !=', $ignoreId);
            if ($dup->countAllResults() > 0) {
                return [null, 'Kode "' . $kode . '" sudah dipakai jurusan lain'];
            }
        }
        $dupNama = $db->table('master_jurusan')->where('nama_jurusan', $nama);
        if ($ignoreId) $dupNama = $dupNama->where('id_jurusan !=', $ignoreId);
        if ($dupNama->countAllResults() > 0) {
            return [null, 'Jurusan "' . $nama . '" sudah terdaftar'];
        }

        // Mapel peminatan: hanya id yang terdaftar di master_mapel
        $mapelIds = [];
        $posted = $this->request->getPost('mapel_peminatan');
        if (is_array($posted)) {
            $validMapel = [];
            foreach ($this->masterModel->getAllMapel() as $m) {
                $validMapel[(int) $m->id_mapel] = true;
            }
            foreach ($posted as $mid) {
                $mid = (int) $mid;
                if ($mid > 0 && isset($validMapel[$mid])) $mapelIds[] = $mid;
            }
            $mapelIds = array_values(array_unique($mapelIds));
        }

        return [[
            'nama_jurusan' => $nama,
            'kode_jurusan' => $kode !== '' ? $kode : null,
            'mapel_peminatan' => $mapelIds ? implode(',', $mapelIds) . ',' : null,
        ], null];
    }

    public function save()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        [$data, $error] = $this->validateJurusanPayload();
        if ($error) {
            return $this->response->setJSON(['status' => false, 'message' => $error]);
        }

        $db = \Config\Database::connect();
        $data['status'] = 1;
        $data['deletable'] = 1;

        try {
            $result = $db->table('master_jurusan')->insert($data);
        } catch (\Throwable $e) {
            log_message('error', '[Datajurusan::save] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan data']);
        }

        return $this->response->setJSON([
            'status' => (bool) $result,
            'message' => $result ? 'Data jurusan berhasil disimpan' : 'Gagal menyimpan data'
        ]);
    }

    public function update()
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id = (int) $this->request->getPost('id_jurusan');

        if (!$id) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak valid']);
        }

        [$data, $error] = $this->validateJurusanPayload($id);
        if ($error) {
            return $this->response->setJSON(['status' => false, 'message' => $error]);
        }

        $db = \Config\Database::connect();
        if (!$db->table('master_jurusan')->where('id_jurusan', $id)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak ditemukan']);
        }

        try {
            $result = $db->table('master_jurusan')->where('id_jurusan', $id)->update($data);
        } catch (\Throwable $e) {
            log_message('error', '[Datajurusan::update] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal memperbarui data']);
        }

        return $this->response->setJSON([
            'status' => $result !== false,
            'message' => $result !== false ? 'Data jurusan berhasil diperbarui' : 'Gagal memperbarui data'
        ]);
    }

    public function delete()
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $id = (int) $this->request->getPost('id_jurusan');

        if (!$id) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak valid']);
        }

        $db = \Config\Database::connect();
        $row = $db->table('master_jurusan')->where('id_jurusan', $id)->get()->getRow();
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
        // Hormati flag proteksi data bawaan sistem
        if (isset($row->deletable) && (int) $row->deletable === 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jurusan bawaan sistem tidak dapat dihapus']);
        }

        // Tidak ada foreign key di DB ini: selama ini jurusan yang masih dipakai
        // kelas ikut terhapus dan master_kelas.jurusan_id menggantung (di data
        // nyata ada 9 kelas menunjuk jurusan yang sudah tidak ada).
        if ($db->tableExists('master_kelas')) {
            $pakaiKelas = $db->table('master_kelas')->where('jurusan_id', $id)->countAllResults();
            if ($pakaiKelas > 0) {
                return $this->response->setJSON([
                    'status'  => false,
                    'message' => 'Jurusan "' . ($row->nama_jurusan ?? $id) . '" masih dipakai ' . $pakaiKelas . ' kelas. Pindahkan kelas itu ke jurusan lain sebelum menghapus.'
                ]);
            }
        }

        $result = $db->table('master_jurusan')->where('id_jurusan', $id)->delete();

        if ($result === false) {
            log_message('error', '[Datajurusan::delete] ' . $id . ' gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Jurusan gagal dihapus. Data belum berubah — coba lagi.']);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function get_data($id = null)
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        if (!$id) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak valid']);
        }

        $db = \Config\Database::connect();
        $jurusan = $db->table('master_jurusan')->where('id_jurusan', $id)->get()->getRow();

        if (!$jurusan) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak ditemukan']);
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => [
                'id_jurusan' => $jurusan->id_jurusan,
                'kode_jurusan' => $jurusan->kode_jurusan ?? $jurusan->singkatan ?? '',
                'nama_jurusan' => $jurusan->nama_jurusan ?? '',
                'mapel_peminatan' => array_map(
                    fn($mid) => ['id_mapel' => $mid],
                    $this->parseMapelPeminatan($jurusan->mapel_peminatan ?? null)
                ),
            ]
        ]);
    }
}
