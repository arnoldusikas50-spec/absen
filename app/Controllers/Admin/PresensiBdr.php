<?php

namespace App\Controllers\Admin;

use App\Models\DashboardModel;
use App\Models\DropdownModel;
use App\Models\PresensiBdrModel;
use App\Services\PresensiBdrService;

/**
 * Admin Presensi BDR Controller
 * Mengelola jadwal "Belajar Dari Rumah" (tabel presensi_bdr).
 *
 * Dipakai saat sebagian kelas tidak KBM di sekolah — mis. kelas X & XI
 * diliburkan selama ujian kelas XII — supaya siswa kelas itu tidak tercatat
 * ALPHA melainkan BDR.
 */
class PresensiBdr extends \App\Controllers\BaseController
{
    protected PresensiBdrModel $bdrModel;
    protected DropdownModel $dropdownModel;
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->bdrModel       = new PresensiBdrModel();
        $this->dropdownModel  = new DropdownModel();
        $this->dashboardModel = new DashboardModel();
    }

    /** Admin penuh boleh mengelola; guru hanya melihat (monitoring/rekap). */
    private function bolehKelola(): bool
    {
        return $this->isAdmin();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN UTAMA
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->isLoggedIn()) {
            return redirect()->to('auth');
        }

        $tp  = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $kelass = ($tp && $smt) ? $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt) : [];
        $levels = $this->dropdownModel->getAllLevelKelas($this->dashboardModel->getSetting()->jenjang ?? '3');

        return view('admin/presensi_bdr/index', [
            'user'       => $this->getUser(),
            'judul'      => 'Jadwal Belajar Dari Rumah (BDR)',
            'subjudul'   => 'Tandai kelas yang tidak KBM di sekolah agar absennya BDR, bukan Alpha',
            'daftar'     => $this->bdrModel->getDaftar(),
            'hari_ini'   => $this->bdrModel->getRowsUntukTanggal(date('Y-m-d')),
            'kelass'     => $kelass,
            'levels'     => $levels,
            'boleh'      => $this->bolehKelola(),
            'setting'    => $this->dashboardModel->getSetting(),
            'tp_active'  => $tp,
            'smt_active' => $smt,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD (AJAX, pola sama dengan Admin\Kalender)
    // ─────────────────────────────────────────────────────────────────────────

    public function save()
    {
        if (! $this->bolehKelola()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id       = (int) ($this->request->getPost('id') ?? 0);
        $lingkup  = strtoupper(trim((string) ($this->request->getPost('lingkup') ?? 'KELAS')));
        $idKelas  = (int) ($this->request->getPost('id_kelas') ?? 0);
        $levelId  = (int) ($this->request->getPost('level_id') ?? 0);
        $tglMulai = trim((string) $this->request->getPost('tgl_mulai'));
        $tglSelesai = trim((string) ($this->request->getPost('tgl_selesai') ?? ''));
        $judul    = mb_substr(trim((string) ($this->request->getPost('judul') ?? '')), 0, 255);
        $ket      = trim((string) ($this->request->getPost('keterangan') ?? ''));

        if (! in_array($lingkup, PresensiBdrModel::LINGKUP, true)) {
            return $this->respond(false, 'Lingkup tidak valid');
        }
        if (! $this->validDate($tglMulai)) {
            return $this->respond(false, 'Tanggal mulai tidak valid');
        }
        if ($tglSelesai !== '') {
            if (! $this->validDate($tglSelesai)) {
                return $this->respond(false, 'Tanggal selesai tidak valid');
            }
            if ($tglSelesai < $tglMulai) {
                return $this->respond(false, 'Tanggal selesai tidak boleh sebelum tanggal mulai');
            }
            if ((strtotime($tglSelesai) - strtotime($tglMulai)) > 370 * 86400) {
                return $this->respond(false, 'Rentang terlalu panjang (maks 370 hari)');
            }
        } else {
            $tglSelesai = null;
        }

        if ($lingkup === 'KELAS') {
            if ($idKelas <= 0) {
                return $this->respond(false, 'Pilih kelas terlebih dahulu');
            }
            $levelId = null;
        } elseif ($lingkup === 'TINGKAT') {
            if ($levelId <= 0) {
                return $this->respond(false, 'Pilih tingkat/kelas paralel terlebih dahulu');
            }
            $idKelas = null;
        } else {
            $idKelas = null;
            $levelId = null;
        }

        if ($judul === '') {
            $judul = 'Belajar dari rumah';
        }

        if ($this->bdrModel->adaBentrok($lingkup, $idKelas, $levelId, $tglMulai, $tglSelesai, $id)) {
            return $this->respond(false, 'Sudah ada jadwal BDR dengan lingkup dan rentang tanggal yang sama');
        }

        $data = [
            'lingkup'     => $lingkup,
            'id_kelas'    => $idKelas,
            'level_id'    => $levelId,
            'tgl_mulai'   => $tglMulai,
            'tgl_selesai' => $tglSelesai,
            'judul'       => $judul,
            'keterangan'  => $ket !== '' ? $ket : null,
        ];

        if ($id > 0) {
            if (! $this->bdrModel->find($id)) {
                return $this->respond(false, 'Jadwal tidak ditemukan');
            }
            $tersimpan = $this->bdrModel->update($id, $data);
            if ($tersimpan === false) {
                log_message('error', '[PresensiBdr] update jadwal ' . $id . ' gagal: ' . json_encode($this->bdrModel->errors()));
                return $this->respond(false, 'Jadwal BDR gagal diperbarui. Perubahan belum tersimpan — coba lagi, dan bila berulang lapor ke pengembang.');
            }
            PresensiBdrService::resetCache();
            return $this->respond(true, 'Jadwal BDR diperbarui. Absensi yang sudah terlanjur Alpha diperbarui cron berikutnya.', $data);
        }

        $data['created_by'] = (int) (session()->get('user_id') ?? 0);
        $insertId = $this->bdrModel->insert($data);
        if (! $insertId) {
            return $this->respond(false, 'Gagal menyimpan jadwal BDR');
        }
        PresensiBdrService::resetCache();

        return $this->respond(true, 'Jadwal BDR berhasil disimpan. Siswa kelas tersebut tidak akan ditandai Alpha pada tanggal itu.', ['id' => (int) $insertId] + $data);
    }

    /**
     * Terapkan jadwal pada tanggal yang sudah lewat: ubah Alpha hari itu menjadi
     * BDR. Tanpa langkah ini, jadwal yang dibuat setelah cron berjalan tidak
     * memperbaiki rekap kemarin.
     */
    public function terapkan($id = null)
    {
        if (! $this->bolehKelola()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) ($id ?? $this->request->getPost('id') ?? 0);
        $row = $this->bdrModel->find($id);
        if (! $row) {
            return $this->respond(false, 'Jadwal tidak ditemukan');
        }

        $piket = new \App\Models\PiketModel();
        $today = date('Y-m-d');
        $akhir = ((string) ($row->tgl_selesai ?? '') !== '' ? (string) $row->tgl_selesai : (string) $row->tgl_mulai);
        $akhir = min($akhir, $today);

        $dari = strtotime((string) $row->tgl_mulai);
        $sampai = strtotime($akhir);
        if ($dari === false || $sampai === false || $sampai < $dari) {
            return $this->respond(false, 'Rentang jadwal tidak valid atau masih di masa depan');
        }

        $ditandai = 0;
        for ($ts = $dari; $ts <= $sampai; $ts = strtotime('+1 day', $ts)) {
            $sync = $piket->terapkanBdr(date('Y-m-d', $ts));
            $ditandai += (int) ($sync['ditandai'] ?? 0);
        }

        PresensiBdrService::resetCache();

        $msg = $ditandai > 0
            ? "{$ditandai} absensi siswa diubah menjadi BDR"
            : 'Tidak ada absensi Alpha yang perlu diubah (cron belum berjalan atau siswa sudah punya status lain)';
        return $this->respond(true, $msg, ['ditandai' => $ditandai]);
    }

    public function delete($id = null)
    {
        if (! $this->bolehKelola()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) ($id ?? $this->request->getPost('id') ?? 0);
        if (! $this->bdrModel->find($id)) {
            return $this->respond(false, 'Jadwal tidak ditemukan');
        }
        $this->bdrModel->delete($id);
        PresensiBdrService::resetCache();

        return $this->respond(true, 'Jadwal BDR dihapus. Absensi yang sudah terlanjur BDR dibiarkan apa adanya.');
    }

    private function validDate(?string $date): bool
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));
        return checkdate($m, $d, $y);
    }
}
