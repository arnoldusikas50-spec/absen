<?php

namespace App\Controllers;

use App\Models\MasterModel;
use App\Models\DashboardModel;
use App\Models\DropdownModel;

/**
 * Datamapel Controller - Ported from CI3
 */
class Datamapel extends BaseController
{
    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;
    protected DropdownModel $dropdownModel;

    /**
     * Kolom yang masih menunjuk id_mapel. Tidak ada foreign key di DB ini,
     * jadi referensi harus diperiksa sendiri sebelum menghapus master mapel —
     * kalau tidak, jurnal/materi milik kelas kehilangan mapelnya secara diam-diam.
     */
    private const REF_MAPEL = [
        'guru_jurnal'             => 'id_mapel',
        'kelas_jadwal_mapel'      => 'id_mapel',
        'kelas_jadwal_materi'     => 'id_mapel',
        'kelas_materi'            => 'id_mapel',
        'kelas_catatan_mapel'     => 'id_mapel',
        'log_materi'              => 'id_mapel',
        'e_tujuan_pembelajaran'   => 'id_mapel',
        'guru_jadwal_pribadi'     => 'id_mapel',
        'piket_presensi_guru'     => 'id_mapel',
        'presensi_siswa'          => 'id_mapel',
    ];

    public function __construct()
    {
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
        $this->dropdownModel = new DropdownModel();
    }

    public function index()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        $setting = $this->dashboardModel->getSetting();
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $kelompok_utama = $this->masterModel->getDataKelompokMapel();
        $sub_kelompok = $this->masterModel->getDataSubKelompokMapel();

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Mata Pelajaran',
            'subjudul' => 'Daftar Mata Pelajaran',
            'setting' => $setting,
            'tp' => $this->dashboardModel->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboardModel->getSemester(),
            'smt_active' => $smt,
            'kategori' => ['WAJIB', 'AGAMA (Kemenag)', 'PEMINATAN AKADEMIK', 'AKADEMIK KEJURUAN', 'LINTAS MINAT', 'MULOK'],
            'kelompok_mapel' => $kelompok_utama + $sub_kelompok,
            'kelompok' => $this->dropdownModel->getDataKelompokMapel(),
            'status' => ['0' => 'Nonaktif', '1' => 'Aktif'],
            'total_mapel' => $this->dashboardModel->total('master_mapel')
        ];

        return view('master/mapel/data', $data);
    }

    public function getdatakelompok()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $id_parent = 0;
        $db = \Config\Database::connect();
        $builder = $db->table('master_kelompok_mapel')
            ->where('id_parent', $id_parent)
            ->orderBy('kode_kel_mapel', 'ASC');

        $data = $builder->get()->getResult();
        return $this->response->setJSON([
            'draw' => $this->request->getPost('draw'),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }

    public function getdatasubkelompok()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $db = \Config\Database::connect();
        $builder = $db->table('master_kelompok_mapel')
            ->where('id_parent <>', 0)
            ->orderBy('kode_kel_mapel', 'ASC');

        $data = $builder->get()->getResult();
        return $this->response->setJSON([
            'draw' => $this->request->getPost('draw'),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }

    public function read()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        $db = \Config\Database::connect();
        $builder = $db->table('master_mapel')
            ->orderBy('kelompok', 'ASC')
            ->orderBy('urutan_tampil', 'ASC');

        $data = $builder->get()->getResult();

        // deletable adalah kolom asli master_mapel (data bawaan = 0). Dulu nilai ini
        // ditimpa '1' untuk semua baris, sehingga UI menawarkan hapus mapel wajib.
        foreach ($data as &$row) {
            $row->deletable = isset($row->deletable) && (int) $row->deletable === 0 ? '0' : '1';
        }
        unset($row);

        return $this->response->setJSON([
            'draw' => $this->request->getPost('draw'),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ]);
    }

    /**
     * Kode kelompok yang sah (dipakai master_mapel.kelompok, varchar(5)).
     */
    private function kodeKelompok(): array
    {
        $sah = [];
        $db = \Config\Database::connect();
        foreach ($db->table('master_kelompok_mapel')->select('kode_kel_mapel')->get()->getResultArray() as $k) {
            $v = trim((string) $k['kode_kel_mapel']);
            if ($v !== '') {
                $sah[$v] = true;
            }
        }
        return $sah;
    }

    /**
     * Jumlah baris di tabel lain yang menunjuk masing-masing id_mapel.
     * Sekali query per tabel (GROUP BY), bukan per id — menghapus 200 mapel
     * tidak boleh berubah menjadi 2.000 query.
     */
    private function petaReferensi($db, array $idMapel): array
    {
        $jumlah = array_fill_keys($idMapel, 0);
        if ($idMapel === []) {
            // whereIn dengan array kosong menghasilkan "IN ()" yang ditolak MySQL,
            // dan get() mengembalikan false -> fatal di jalur hapus massal.
            return $jumlah;
        }
        foreach (self::REF_MAPEL as $tabel => $kolom) {
            if (! $db->tableExists($tabel)) {
                continue;
            }
            $hasil = $db->table($tabel)
                ->select($kolom . ' AS id_mapel_ref, COUNT(*) AS n', false)
                ->whereIn($kolom, $idMapel)
                ->groupBy($kolom)
                ->get();
            if ($hasil === false) {
                log_message('error', '[Datamapel] gagal memeriksa referensi di ' . $tabel . ': ' . json_encode($db->error()));
                continue;
            }
            $rows = $hasil->getResultArray();
            foreach ($rows as $r) {
                $id = (int) $r['id_mapel_ref'];
                if (isset($jumlah[$id])) {
                    $jumlah[$id] += (int) $r['n'];
                }
            }
        }
        return $jumlah;
    }

    /**
     * Buang id mapel yang baru dihapus dari daftar mapel peminatan jurusan,
     * supaya UI jurusan tidak lagi menampilkan "Mapel #12" yang sudah tidak ada.
     */
    private function bersihkanPeminatan($db, array $idTerhapus): void
    {
        if (! $db->tableExists('master_jurusan')) {
            return;
        }
        foreach ($db->table('master_jurusan')->select('id_jurusan, mapel_peminatan')->get()->getResultArray() as $j) {
            $csv = trim((string) ($j['mapel_peminatan'] ?? ''));
            if ($csv === '') {
                continue;
            }
            $tersisa = [];
            foreach (explode(',', $csv) as $bagian) {
                $id = (int) trim($bagian);
                if ($id > 0 && !in_array($id, $idTerhapus, true)) {
                    $tersisa[] = $id;
                }
            }
            $baru = $tersisa === [] ? null : implode(',', $tersisa) . ',';
            if ($baru !== null && $baru === $csv) {
                continue;
            }
            if ($db->table('master_jurusan')->where('id_jurusan', (int) $j['id_jurusan'])
                    ->update(['mapel_peminatan' => $baru]) === false) {
                log_message('error', '[Datamapel] gagal membersihkan mapel_peminatan jurusan ' . $j['id_jurusan']);
            }
        }
    }

    /**
     * Validasi payload master_mapel. Return [data, pesanError].
     */
    private function validasiMapelPayload(?int $idIgnore = null): array
    {
        $nama = trim((string) $this->request->getPost('nama_mapel'));
        $kode = trim((string) $this->request->getPost('kode_mapel'));
        $kelompok = trim((string) $this->request->getPost('kelompok'));
        $urutanPost = $this->request->getPost('urutan_tampil');
        $statusPost = $this->request->getPost('status');

        if ($nama === '') {
            return [null, 'Nama mapel wajib diisi.'];
        }
        if (mb_strlen($nama) > 50) {
            return [null, 'Nama mapel terlalu panjang (' . mb_strlen($nama) . ' karakter, maksimal 50).'];
        }
        if (mb_strlen($kode) > 20) {
            return [null, 'Kode mapel maksimal 20 karakter.'];
        }
        if ($kode !== '' && !preg_match('/^[A-Za-z0-9._-]+$/', $kode)) {
            return [null, 'Kode mapel hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung.'];
        }
        if ($kelompok === '' || !isset($this->kodeKelompok()[$kelompok])) {
            return [null, 'Kelompok "' . $kelompok . '" tidak terdaftar — pilih kelompok yang tersedia dulu.'];
        }
        if (mb_strlen($kelompok) > 5) {
            // master_mapel.kelompok adalah varchar(5); kode kelompok yang lebih
            // panjang akan ditolak MySQL setelah validasi lolos — tolak di sini.
            return [null, 'Kode kelompok "' . $kelompok . '" terlalu panjang untuk kolom mapel (maks 5 karakter).'];
        }
        if (!is_numeric((string) $urutanPost)) {
            return [null, 'Urutan tampil harus berupa angka.'];
        }
        $urutan = (int) $urutanPost;
        if ($urutan < 0 || $urutan > 9999) {
            return [null, 'Urutan tampil harus antara 0 dan 9999.'];
        }
        $status = ($statusPost === null || $statusPost === '') ? 1 : (int) $statusPost;
        if (!in_array($status, [0, 1], true)) {
            return [null, 'Status hanya boleh 0 (nonaktif) atau 1 (aktif).'];
        }

        $db = \Config\Database::connect();
        $cekNama = $db->table('master_mapel')->where('nama_mapel', $nama);
        if ($idIgnore) {
            $cekNama->where('id_mapel !=', $idIgnore);
        }
        if ($cekNama->countAllResults() > 0) {
            return [null, 'Mapel "' . $nama . '" sudah terdaftar.'];
        }
        if ($kode !== '') {
            $cekKode = $db->table('master_mapel')->where('kode', $kode);
            if ($idIgnore) {
                $cekKode->where('id_mapel !=', $idIgnore);
            }
            if ($cekKode->countAllResults() > 0) {
                return [null, 'Kode "' . $kode . '" sudah dipakai mapel lain.'];
            }
        }

        return [[
            'nama_mapel'    => $nama,
            'kode'          => $kode !== '' ? $kode : null,
            'kelompok'      => $kelompok,
            'urutan_tampil' => $urutan,
            'status'        => $status,
        ], null];
    }

    public function addkelompokmapel()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        $nama = trim((string) $this->request->getPost('nama_kel_mapel'));
        $kode = trim((string) $this->request->getPost('kode_kel_mapel'));
        $kategori = trim((string) $this->request->getPost('kategori'));
        $idParent = (int) ($this->request->getPost('id_parent') ?? 0);
        $id = (int) ($this->request->getPost('id_kel_mapel') ?? 0);

        if ($nama === '' || mb_strlen($nama) > 100) {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama kelompok wajib diisi (maks 100 karakter).']);
        }
        if ($kode === '' || mb_strlen($kode) > 10 || !preg_match('/^[A-Za-z0-9._-]+$/', $kode)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kode kelompok wajib diisi, maks 10 karakter, tanpa spasi.']);
        }
        if ($kategori === '' || mb_strlen($kategori) > 20) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kategori kelompok wajib diisi (maks 20 karakter).']);
        }

        $db = \Config\Database::connect();
        if ($idParent > 0) {
            $induk = $db->table('master_kelompok_mapel')->where('id_kel_mapel', $idParent)->get()->getRowArray();
            if (!$induk) {
                return $this->response->setJSON(['status' => false, 'message' => 'Induk kelompok #' . $idParent . ' tidak ditemukan.']);
            }
            if ((int) $induk['id_parent'] !== 0) {
                return $this->response->setJSON(['status' => false, 'message' => 'Sub-kelompok hanya boleh punya satu level — pilih kelompok utama sebagai induk.']);
            }
            if ($id > 0 && $idParent === $id) {
                return $this->response->setJSON(['status' => false, 'message' => 'Kelompok tidak bisa menjadi induk bagi dirinya sendiri.']);
            }
        }

        $cekKode = $db->table('master_kelompok_mapel')->where('kode_kel_mapel', $kode);
        if ($id > 0) {
            $cekKode->where('id_kel_mapel !=', $id);
        }
        if ($cekKode->countAllResults() > 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kode "' . $kode . '" sudah dipakai kelompok lain.']);
        }

        $data = [
            'nama_kel_mapel' => $nama,
            'kode_kel_mapel' => $kode,
            'kategori'       => $kategori,
            'id_parent'      => $idParent,
        ];

        if ($id > 0) {
            if (!$db->table('master_kelompok_mapel')->where('id_kel_mapel', $id)->countAllResults()) {
                return $this->response->setJSON(['status' => false, 'message' => 'Kelompok #' . $id . ' tidak ditemukan — tidak ada yang diubah.']);
            }
            $tersimpan = $db->table('master_kelompok_mapel')->where('id_kel_mapel', $id)->update($data);
            if ($tersimpan === false) {
                log_message('error', '[Datamapel::addkelompokmapel] update ' . $id . ' gagal: ' . json_encode($db->error()));
                return $this->response->setJSON(['status' => false, 'message' => 'Kelompok gagal diperbarui. Data belum berubah — coba lagi.']);
            }
            return $this->response->setJSON(['status' => true, 'message' => 'Kelompok "' . $nama . '" berhasil diperbarui.']);
        }

        $tersimpan = $db->table('master_kelompok_mapel')->insert($data);
        if ($tersimpan === false) {
            log_message('error', '[Datamapel::addkelompokmapel] insert gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Kelompok gagal disimpan. Data belum berubah — coba lagi.']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Kelompok "' . $nama . '" berhasil ditambahkan.']);
    }

    public function create()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        [$data, $error] = $this->validasiMapelPayload();
        if ($error) {
            return $this->response->setJSON(['status' => false, 'message' => $error]);
        }

        $setting = $this->dashboardModel->getSetting();
        $data['jenjang'] = (int) ($setting->jenjang ?? 0);

        $db = \Config\Database::connect();
        $tersimpan = $db->table('master_mapel')->insert($data);
        if ($tersimpan === false) {
            log_message('error', '[Datamapel::create] insert gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Mata pelajaran "' . $data['nama_mapel'] . '" berhasil ditambahkan.']);
    }

    public function save()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }
        return $this->create();
    }

    public function update()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        $id = (int) ($this->request->getPost('id_mapel') ?? 0);
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID mapel tidak valid — tidak ada yang diubah.']);
        }

        $db = \Config\Database::connect();
        if (!$db->table('master_mapel')->where('id_mapel', $id)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel #' . $id . ' tidak ditemukan — tidak ada yang diubah.']);
        }

        [$data, $error] = $this->validasiMapelPayload($id);
        if ($error) {
            return $this->response->setJSON(['status' => false, 'message' => $error]);
        }
        if ($this->request->getPost('status') === null) {
            // field tidak dikirim sama sekali: jangan paksa jadi aktif, kalau tidak
            // mapel nonaktif ikut menyala tiap kali barisnya disunting
            unset($data['status']);
        }

        $tersimpan = $db->table('master_mapel')->where('id_mapel', $id)->update($data);
        if ($tersimpan === false) {
            log_message('error', '[Datamapel::update] update ' . $id . ' gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel gagal diperbarui. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Mata pelajaran "' . $data['nama_mapel'] . '" berhasil diperbarui.']);
    }

    public function hapuskelompok()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        $id = (int) ($this->request->getPost('id_kel') ?? 0);
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID kelompok tidak valid.']);
        }

        $db = \Config\Database::connect();
        $row = $db->table('master_kelompok_mapel')->where('id_kel_mapel', $id)->get()->getRowArray();
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelompok tidak ditemukan — tidak ada yang dihapus.']);
        }

        $anak = $db->table('master_kelompok_mapel')->where('id_parent', $id)->countAllResults();
        if ($anak > 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelompok "' . $row['nama_kel_mapel'] . '" masih punya ' . $anak . ' sub-kelompok. Hapus sub-kelompoknya dulu.']);
        }

        $pakai = $db->table('master_mapel')->where('kelompok', (string) $row['kode_kel_mapel'])->countAllResults();
        if ($pakai > 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelompok "' . $row['nama_kel_mapel'] . '" masih dipakai ' . $pakai . ' mapel. Pindahkan mapel itu ke kelompok lain dulu.']);
        }

        $terhapus = $db->table('master_kelompok_mapel')->where('id_kel_mapel', $id)->delete();
        if ($terhapus === false) {
            log_message('error', '[Datamapel::hapuskelompok] ' . $id . ' gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Kelompok gagal dihapus. Data belum berubah — coba lagi.']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Kelompok "' . $row['nama_kel_mapel'] . '" berhasil dihapus.']);
    }

    public function delete()
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        $posted = $this->request->getPost('checked');
        if (!is_array($posted)) {
            $posted = ($posted === null || $posted === '') ? [] : explode(',', (string) $posted);
        }
        $ids = [];
        foreach ($posted as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $ids = array_values($ids);

        if ($ids === []) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada mapel yang dipilih untuk dihapus.']);
        }
        if (count($ids) > 200) {
            return $this->response->setJSON(['status' => false, 'message' => 'Maksimal 200 mapel sekaligus.']);
        }

        $db = \Config\Database::connect();
        $baris = $db->table('master_mapel')->select('id_mapel, nama_mapel, deletable')->whereIn('id_mapel', $ids)->get()->getResultArray();
        $referensi = $this->petaReferensi($db, array_map(static fn ($r) => (int) $r['id_mapel'], $baris));

        $kunci = [];
        $terpakai = [];
        $boleh = [];
        $dikenal = [];
        foreach ($baris as $r) {
            $idMapel = (int) $r['id_mapel'];
            $dikenal[$idMapel] = true;
            if ((int) $r['deletable'] === 0) {
                $kunci[] = $r['nama_mapel'];
                continue;
            }
            $ref = $referensi[$idMapel] ?? 0;
            if ($ref > 0) {
                $terpakai[] = $r['nama_mapel'] . ' (' . $ref . ' data terkait)';
                continue;
            }
            $boleh[] = $idMapel;
        }
        $asing = count(array_diff($ids, array_keys($dikenal)));

        if ($boleh === []) {
            $alasan = [];
            if ($kunci) {
                $alasan[] = count($kunci) . ' mapel bawaan yang tidak boleh dihapus';
            }
            if ($terpakai) {
                $alasan[] = count($terpakai) . ' mapel masih dipakai jurnal/jadwal/materi';
            }
            if ($asing) {
                $alasan[] = $asing . ' id tidak ditemukan';
            }
            return $this->response->setJSON([
                'status'   => false,
                'message'  => 'Tidak ada mapel yang terhapus: ' . implode('; ', $alasan) . '. Hapus atau pindahkan data terkait lebih dulu.',
                'kunci'    => $kunci,
                'terpakai' => $terpakai,
            ]);
        }

        $db->transBegin();
        if ($db->table('master_mapel')->whereIn('id_mapel', $boleh)->delete() === false || ! $db->transStatus()) {
            $db->transRollback();
            log_message('error', '[Datamapel::delete] hapus massal gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel gagal dihapus. Tidak ada data yang berubah — coba lagi, dan bila berulang lapor ke pengembang.']);
        }
        $this->bersihkanPeminatan($db, $boleh);
        if (! $db->transStatus()) {
            $db->transRollback();
            log_message('error', '[Datamapel::delete] bersih-bersih peminatan gagal');
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel gagal dihapus. Tidak ada data yang berubah — coba lagi.']);
        }
        $db->transCommit();

        $dilewati = count($kunci) + count($terpakai) + $asing;
        $pesan = count($boleh) . ' mapel berhasil dihapus';
        if ($dilewati > 0) {
            $pesan .= ' (' . $dilewati . ' tidak dihapus: bawaan sistem / masih dipakai / tidak ditemukan)';
        }

        return $this->response->setJSON([
            'status'    => true,
            'message'   => $pesan,
            'total'     => count($boleh),
            'kunci'     => $kunci,
            'terpakai'  => $terpakai,
        ]);
    }

    public function aktifkan($id)
    {
        if ($deny = $this->requireRole('admin')) {
            return $deny;
        }

        $id = (int) $id;
        $db = \Config\Database::connect();
        if ($id <= 0 || !$db->table('master_mapel')->where('id_mapel', $id)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel tidak ditemukan — tidak ada yang diubah.']);
        }

        $tersimpan = $db->table('master_mapel')->where('id_mapel', $id)->update(['status' => 1]);
        if ($tersimpan === false) {
            log_message('error', '[Datamapel::aktifkan] ' . $id . ' gagal: ' . json_encode($db->error()));
            return $this->response->setJSON(['status' => false, 'message' => 'Mapel gagal diaktifkan. Coba lagi.']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Mapel berhasil diaktifkan.']);
    }
}
