<?php

namespace App\Controllers;

use App\Models\MasterModel;
use App\Models\DashboardModel;
use App\Models\LogModel;

class Datatahun extends BaseController
{
    protected MasterModel $master;
    protected DashboardModel $dashboard;
    protected LogModel $logging;

    public function __construct()
    {
        $this->master = new MasterModel();
        $this->dashboard = new DashboardModel();
        $this->logging = new LogModel();
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth');
        }

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $data = [
            'user' => $this->authModel->user(),
            'judul' => 'Tahun Pelajaran dan Semester',
            'subjudul' => 'Atur Tahun Pelajaran dan Semester',
            'setting' => $this->dashboard->getSetting(),
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $smt,
            'tp_active_obj' => $tp,
            'smt_active_obj' => $smt
        ];

        $jml = $this->master->getJmlHariEfektif(($tp->id_tp ?? '') . ($smt->id_smt ?? ''));
        $data['jml_hari'] = $jml == null ? '0' : $jml->jml_hari_efektif;

        return view('master/tahun/data', $data);
    }

    public function data()
    {
        if (!$this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }
        return $this->response->setJSON($this->dashboard->getDataTahun());
    }

    public function gantiTahun()
    {
        return $this->gantiPeriode('tahun', 'master_tp', 'id_tp', 'tp', 'Tahun Pelajaran', 20);
    }

    public function gantiSemester()
    {
        return $this->gantiPeriode('semester', 'master_smt', 'id_smt', 'Semester', 'Semester', 10);
    }

    /**
     * Pindahkan penanda "aktif" ke satu baris master_tp / master_smt.
     *
     * $fieldJson = nama field POST yang memuat daftar baris (JSON),
     * $kolomLabel = nama kolom per baris di JSON itu (bedah huruf besar-kecil
     * seperti aslinya: 'tp' untuk tahun, 'Semester' untuk semester).
     *
     * Invarian yang dijaga: setelah sukses harus ada TEPAT SATU baris active=1.
     * Kalau id aktif yang dikirim tidak cocok dengan baris mana pun, seluruh
     * baris akan jadi active=0 dan getTahunActive()/getSemesterActive()
     * mengembalikan null — semua halaman yang bergantung TP/SMT jadi kosong.
     */
    private function gantiPeriode(string $fieldJson, string $tabel, string $pk, string $kolomLabel, string $namaManusiawi, int $maksLabel)
    {
        if (!$this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $aktif = (int) ($this->request->getPost('active') ?? 0);
        $baris = json_decode((string) ($this->request->getPost($fieldJson) ?? '[]'), true);
        if (!is_array($baris) || $baris === []) {
            return $this->balas(false, 'Data ' . strtolower($namaManusiawi) . ' tidak terbaca — tidak ada yang diubah.');
        }

        $update = [];
        foreach ($baris as $tps) {
            if (!is_array($tps)) {
                continue;
            }
            $id = (int) ($tps['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = trim((string) ($tps[$kolomLabel] ?? ''));
            if ($label === '' || mb_strlen($label) > $maksLabel) {
                return $this->balas(false, 'Label ' . strtolower($namaManusiawi) . ' kosong atau lebih dari '
                    . $maksLabel . ' karakter — tidak ada yang diubah.');
            }
            $update[$id] = [
                $pk    => $id,
                $this->kolomPeriode($tabel) => $label,
                'active' => $id === $aktif ? 1 : 0,
            ];
        }

        if ($update === []) {
            return $this->balas(false, 'Tidak ada ' . strtolower($namaManusiawi) . ' yang valid — tidak ada yang diubah.');
        }
        if (!isset($update[$aktif])) {
            return $this->balas(false, $namaManusiawi . ' yang ingin diaktifkan tidak ditemukan di daftar — tidak ada yang diubah.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            if ($db->table($tabel)->updateBatch(array_values($update), $pk) === false || !$db->transStatus()) {
                $db->transRollback();
                log_message('error', '[Datatahun] updateBatch ' . $tabel . ' ditolak DB (aktif=' . $aktif . ')');
                return $this->balas(false, $namaManusiawi . ' gagal disimpan. Periode berjalan belum berubah — coba lagi.');
            }
            // Jaga invarian "tepat satu aktif" walaupun updateBatch hanya
            // menyentuh sebagian baris.
            $db->table($tabel)->where($pk . ' <>', $aktif)->where('active', 1)->update(['active' => 0]);
            $db->table($tabel)->where($pk, $aktif)->update(['active' => 1]);

            $jumlahAktif = $db->table($tabel)->where('active', 1)->countAllResults();
            if ($jumlahAktif !== 1) {
                $db->transRollback();
                log_message('error', '[Datatahun] ' . $tabel . ' punya ' . $jumlahAktif . ' baris aktif setelah ganti');
                return $this->balas(false, $namaManusiawi . ' gagal disimpan: jumlah periode aktif tidak tepat satu. Data belum berubah.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            if ($db->transStatus() === false) {
                $db->transRollback();
            }
            log_message('error', '[Datatahun] gantiPeriode ' . $tabel . ' aktif=' . $aktif . ': ' . $e->getMessage());
            return $this->balas(false, $namaManusiawi . ' gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        $this->logging->saveLog(4, 'mengganti ' . strtolower($namaManusiawi) . ' aktif');
        return $this->balas(true, $namaManusiawi . ' berhasil diganti');
    }

    private function kolomPeriode(string $tabel): string
    {
        return $tabel === 'master_tp' ? 'tahun' : 'smt';
    }

    /**
     * Bentuk respon yang dipakai halaman ini. View master/tahun/data.php
     * membaca res.msg (bukan res.message seperti respond() BaseController),
     * jadi pesan kegagalan harus lewat kunci 'msg'.
     */
    private function balas(bool $status, string $pesan)
    {
        return $this->response->setJSON(['status' => $status, 'msg' => $pesan]);
    }

    public function add()
    {
        if (!$this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $method = $this->request->getPost('method');
        $tahun = trim((string) ($this->request->getPost('tahun') ?? ''));

        if ($tahun === '' || mb_strlen($tahun) > 20) {
            return $this->balas(false, 'Label tahun pelajaran wajib diisi (maks 20 karakter).');
        }

        $db = \Config\Database::connect();

        if ($method == 'add') {
            // Satu label tahun hanya sekali; kalau tidak, daftar TP jadi
            // ambigu dan admin salah mengaktifkan periode.
            if ($db->table('master_tp')->where('tahun', $tahun)->countAllResults() > 0) {
                return $this->balas(false, 'Tahun pelajaran "' . $tahun . '" sudah ada.');
            }
            $tersimpan = $db->table('master_tp')->insert(['tahun' => $tahun, 'active' => 0]);
            if ($tersimpan === false) {
                log_message('error', '[Datatahun] insert master_tp ditolak DB (tahun=' . $tahun . ')');
                return $this->balas(false, 'Tahun pelajaran gagal disimpan. Coba lagi, dan bila berulang lapor ke pengembang.');
            }
            $this->logging->saveLog(3, 'menambah tahun pelajaran');
            return $this->balas(true, 'Tahun pelajaran berhasil ditambahkan');
        }

        $id = (int) ($this->request->getPost('id_tahun') ?? 0);
        if ($id <= 0) {
            return $this->balas(false, 'Pilih tahun pelajaran yang akan diubah.');
        }
        $ada = $db->table('master_tp')->where('id_tp', $id)->get()->getRowArray();
        if (!$ada) {
            return $this->balas(false, 'Tahun pelajaran tidak ditemukan — mungkin sudah dihapus.');
        }

        $diubah = $db->table('master_tp')->where('id_tp', $id)->update(['tahun' => $tahun]);
        if ($diubah === false) {
            log_message('error', '[Datatahun] update master_tp#' . $id . ' ditolak DB');
            return $this->balas(false, 'Tahun pelajaran gagal disimpan. Data belum berubah.');
        }
        $this->logging->saveLog(4, 'mengedit tahun pelajaran');
        // affectedRows() 0 berarti nilainya memang sudah sama, bukan kegagalan.
        return $this->balas(true, $db->affectedRows() > 0
            ? 'Tahun pelajaran berhasil diubah'
            : 'Tidak ada perubahan — label tahun sudah sesuai.');
    }

    public function saveHariEfektif()
    {
        if (!$this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $tp  = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->balas(false, 'Belum ada tahun/semester aktif, jadi hari efektif tidak dapat disimpan. Aktifkan periode dulu.');
        }

        $jumlah = filter_var($this->request->getPost('jml_hari'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 366]]);
        if ($jumlah === false) {
            return $this->balas(false, 'Jumlah hari efektif harus bilangan bulat 0–366.');
        }

        $db = \Config\Database::connect();
        $tersimpan = $db->table('master_hari_efektif')->replace([
            'id_hari_efektif'  => (string) ((int) $tp->id_tp . (int) $smt->id_smt),
            'jml_hari_efektif' => $jumlah,
        ]);

        if ($tersimpan === false) {
            log_message('error', '[Datatahun] replace master_hari_efektif ditolak DB (tp=' . $tp->id_tp . ' smt=' . $smt->id_smt . ')');
            return $this->balas(false, 'Jumlah hari efektif gagal disimpan. Data belum berubah — coba lagi.');
        }

        return $this->balas(true, 'Jumlah hari efektif berhasil disimpan');
    }

    public function hapusTahun()
    {
        if (!$this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $id = (int) ($this->request->getPost('hapus') ?? 0);
        if ($id <= 0) {
            return $this->balas(false, 'Pilih tahun pelajaran yang akan dihapus.');
        }

        $db = \Config\Database::connect();
        $row = $db->table('master_tp')->where('id_tp', $id)->get()->getRowArray();
        if (!$row) {
            return $this->balas(false, 'Tahun pelajaran tidak ditemukan — mungkin sudah dihapus.');
        }
        // Menghapus periode yang sedang aktif membuat getTahunActive() null
        // dan seluruh halaman bergantung TP/SMT ikut kosong.
        if ((int) $row['active'] === 1) {
            return $this->balas(false, 'Tahun pelajaran ini sedang aktif. Aktifkan tahun lain dulu, baru hapus.');
        }

        [$dipakai, $diTabel] = $this->cekTahunDipakai($id);
        if ($dipakai) {
            log_message('warning', '[Datatahun] hapus master_tp#' . $id . ' ditolak: ' . ($diTabel ?: 'pemakaian tidak dapat diperiksa'));

            return $diTabel === ''
                ? $this->balas(false, 'Pemakaian tahun pelajaran ini tidak dapat diperiksa, jadi penghapusan ditolak. '
                    . 'Coba lagi nanti, atau biarkan tahun ini dalam keadaan nonaktif.')
                : $this->balas(false, 'Tahun pelajaran ini masih dipakai data lain (' . $diTabel . '). '
                    . 'Label tahun yang dihapus tidak ikut menghapus barisnya, tapi membuat data itu tidak lagi muncul '
                    . 'di halaman berbasis periode. Biarkan tahun ini tersimpan dalam keadaan nonaktif.');
        }

        if ($this->dashboard->hapus('master_tp', $id, 'id_tp')) {
            $this->logging->saveLog(5, 'menghapus tahun pelajaran');
            return $this->balas(true, 'Tahun Pelajaran berhasil dihapus');
        }
        return $this->balas(false, 'Gagal menghapus tahun pelajaran. Data belum berubah.');
    }

    /**
     * Apakah masih ada baris di tabel lain yang menunjuk ke id_tp ini?
     *
     * master_tp hanya label periode. Menghapusnya tidak menghapus data, tapi
     * memutus jalur aksesnya: kelas_siswa, jadwal, rapor, dan CBT menggantungkan
     * periode lewat id_tp, sehingga absensi siswa tahun itu tidak lagi muncul di
     * tampilan mana pun berbasis kelas/periode — tetap utuh di database, cuma
     * tidak terjangkau. Tahun lama cukup dinonaktifkan (active=0), tidak dihapus.
     *
     * Tabel kandidat dibaca dari information_schema (skema live, bukan daftar
     * yang ditulis di kode ini) dengan dua penapisan: kolom id_tp harus numerik
     * dan bukan AUTO_INCREMENT. Tanpa itu, tabel yang memakai id_tp sebagai
     * primary key sendiri — atau yang menyimpan kode periode dalam teks — akan
     * terbaca sebagai pemakai tahun dan menolak penghapusan yang wajar.
     *
     * Pemeriksaan berhenti di tabel pertama yang terpakai: penolakannya sudah
     * pasti, dan itu menghindari COUNT(*) penuh di puluhan tabel.
     *
     * @return array{0: bool, 1: string} [tolak, contoh tabel & jumlah baris]
     */
    private function cekTahunDipakai(int $id): array
    {
        $db = \Config\Database::connect();
        $sql = "SELECT c.TABLE_NAME AS nama
                  FROM information_schema.COLUMNS c
                  JOIN information_schema.TABLES t
                    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
                 WHERE c.TABLE_SCHEMA = DATABASE()
                   AND c.COLUMN_NAME = 'id_tp'
                   AND c.TABLE_NAME <> 'master_tp'
                   AND t.TABLE_TYPE = 'BASE TABLE'
                   AND c.DATA_TYPE IN ('int', 'bigint', 'mediumint', 'smallint')
                   AND UPPER(c.EXTRA) NOT LIKE '%AUTO_INCREMENT%'
                 ORDER BY c.TABLE_NAME";
        $q = $db->query($sql);
        if ($q === false) {
            // Ragu berarti jangan hapus: menghapus label tahun jauh lebih sulit
            // dibatalkan daripada mencoba lagi setelah database sehat.
            return [true, ''];
        }
        // Hasil dibaca habis lebih dulu supaya COUNT(*) berikutnya tidak
        // berbagi result set yang belum tuntas di koneksi yang sama.
        $kandidat = array_map(
            static fn (array $b): string => (string) ($b['nama'] ?? ''),
            $q->getResultArray()
        );

        foreach ($kandidat as $tabel) {
            if ($tabel === '') {
                continue;
            }
            try {
                $jumlah = $db->table($tabel)->where('id_tp', $id)->countAllResults();
                // DBDebug=false membuat query gagal mengembalikan 0, bukan
                // exception — tanpa pemeriksaan ini tabel yang gagal dihitung
                // akan terbaca "kosong" dan penghapusan lolos.
                $galat = $db->error();
                if (! empty($galat['code'])) {
                    log_message('error', '[Datatahun] cek pemakai master_tp#' . $id . ' di ' . $tabel
                        . ': ' . json_encode($galat));
                    return [true, ''];
                }
            } catch (\Throwable $e) {
                log_message('error', '[Datatahun] cek pemakai master_tp#' . $id . ' di ' . $tabel . ': ' . $e->getMessage());
                return [true, ''];
            }
            if ($jumlah > 0) {
                return [true, $tabel . ': ' . $jumlah . ' baris'];
            }
        }

        return [false, ''];
    }
}
