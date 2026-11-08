<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\DashboardModel;
use App\Models\AuthModel;
use App\Models\MasterModel;

/**
 * Admin/UserManagement
 * ─────────────────────────────────────────────────────────────────────────────
 * Halaman terpadu manajemen user: Admin, Guru, dan Siswa dalam satu tampilan.
 * Controller ini hanya bertugas merender view — semua aksi AJAX tetap
 * diarahkan ke controller lama (Useradmin, Userguru, Usersiswa) agar tidak
 * memutus kompatibilitas dengan kode yang sudah ada.
 */
class UserManagement extends BaseController
{
    protected UsersModel    $usersModel;
    protected DashboardModel $dashModel;
    protected AuthModel     $authModel;

    public function __construct()
    {
        $this->usersModel = new UsersModel();
        $this->dashModel  = new DashboardModel();
        $this->authModel  = new AuthModel();
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $tp  = $this->dashModel->getTahunActive();
        $smt = $this->dashModel->getSemesterActive();

        // ── Statistik ringkas untuk KPI cards ─────────────────────────────
        $totalAdmin = 0;
        $totalGuru  = 0;
        $totalSiswa = 0;

        try {
            $db = \Config\Database::connect();

            $totalAdmin = (int) $db->table('users_groups')
                ->where('group_id', 1)
                ->countAllResults();

            $totalGuru = (int) $db->table('master_guru')->countAllResults();

            // Siswa aktif di buku induk
            if ($db->tableExists('buku_induk')) {
                $totalSiswa = (int) $db->table('master_siswa ms')
                    ->join('buku_induk bi', "bi.id_siswa = ms.id_siswa AND bi.status = '1'")
                    ->countAllResults();
            } else {
                $totalSiswa = (int) $db->table('master_siswa')->countAllResults();
            }
        } catch (\Throwable $e) {
            log_message('error', 'UserManagement::index stats error: ' . $e->getMessage());
        }

        // ── Data admin (langsung — biasanya sedikit) ──────────────────────
        $admins = [];
        try {
            $admins = $this->usersModel->getAllAdmin();
        } catch (\Throwable $e) {
            // Daftar kosong bukan berarti tidak ada admin — catat alasannya.
            log_message('error', 'UserManagement::index admins error: ' . $e->getMessage());
        }

        return view('admin/users/index', [
            'user'        => $this->getUser(),
            'judul'       => 'Manajemen Pengguna',
            'subjudul'    => 'Kelola akun Admin, Guru, dan Siswa',
            'setting'     => $this->dashModel->getSetting(),
            'tp_active'   => $tp,
            'smt_active'  => $smt,
            'admins'      => $admins,
            'totalAdmin'  => $totalAdmin,
            'totalGuru'   => $totalGuru,
            'totalSiswa'  => $totalSiswa,
        ]);
    }
}
