<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DashboardModel;

/**
 * KartuGuru Controller
 * Modul cetak kartu absen Guru / GTK QR Code:
 * - Cetak semua guru (format lembar A4 / grid)
 * - Cetak per guru (individu)
 * - Preview interaktif dengan live QR code rendering (Landscape / Portrait)
 * - Cetak kartu guru mandiri (kartuSaya)
 */
class KartuGuru extends BaseController
{
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    /** Maksimal kartu yang muat di satu permintaan cetak tanpa membuat browser macet. */
    private const BATAS_CETAK = 500;

    /**
     * Halaman Utama / Preview Kartu Absen Guru / GTK
     */
    public function index()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        // Kartu memuat NIP/NUPTK/QR seluruh guru — bukan untuk akun siswa.
        if (!$this->isAdmin() && !$this->isPanitia() && !$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $isStaffPenuh = $this->isAdmin() || $this->isPanitia();
        $idGuru = (int) ($this->request->getGet('id_guru') ?? 0);
        if (!$isStaffPenuh) {
            // Guru hanya boleh melihat/mencetak kartunya sendiri.
            $idGuru = $this->currentGuruId((int) $user->id);
            if ($idGuru <= 0) {
                return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
            }
        }
        $db     = \Config\Database::connect();
        $hasRfidGuru = $db->fieldExists('nomor_rfid', 'master_guru');

        $guruOptions = [];
        $guruList    = [];
        $terpotong   = false;
        $catatan     = '';

        // Ambil daftar guru untuk dropdown filter
        try {
            $guruOptionsQ = $db->table('master_guru')
                ->select('id_guru, nama_guru, nip, kode_guru, nuptk, jenis_ptk');
            if (!$isStaffPenuh) {
                $guruOptionsQ->where('id_guru', $idGuru);
            }
            $guruOptions = $guruOptionsQ
                ->orderBy('nama_guru', 'ASC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', '[KartuGuru::index] daftar guru untuk filter gagal dibaca: ' . $e->getMessage());
            $guruOptions = [];
            $catatan     = 'Daftar guru gagal dibaca dari basis data — periksa log aplikasi.';
        }

        // Query data guru yang akan ditampilkan
        if ($catatan === '') {
            try {
                $builder = $db->table('master_guru')
                    ->select('id_guru, id_user, nama_guru, nip, kode_guru, nuptk, jenis_ptk, tgs_tambahan, status_pegawai, jenis_kelamin, foto, username'
                    . ($hasRfidGuru ? ', nomor_rfid' : ''));

                if ($idGuru > 0) {
                    $builder->where('id_guru', $idGuru);
                }

                // BATAS_CETAK + 1 dibaca supaya pemotongan bisa dilaporkan, bukan diam-diam.
                $guruList = $builder->orderBy('nama_guru', 'ASC')
                    ->limit(self::BATAS_CETAK + 1)
                    ->get()->getResult();
                $terpotong = count($guruList) > self::BATAS_CETAK;
                if ($terpotong) {
                    $guruList = array_slice($guruList, 0, self::BATAS_CETAK);
                }
            } catch (\Throwable $e) {
                log_message('error', '[KartuGuru::index] daftar kartu guru gagal dibaca: ' . $e->getMessage());
                $guruList = [];
                $catatan  = 'Daftar kartu guru gagal dibaca dari basis data — periksa log aplikasi.';
            }
        }

        // Format data guru (QR value & photo)
        foreach ($guruList as $g) {
            $g->qr_value = self::nomorKartuGuru($g);
            $g->foto_url = self::resolveFotoUrl($g->foto, $g->nama_guru);
            $g->jabatan  = !empty($g->tgs_tambahan) ? $g->tgs_tambahan : (!empty($g->jenis_ptk) ? $g->jenis_ptk : 'Guru / Tenaga Pendidik');
        }

        $setting = $this->dashboardModel->getSetting();
        $tpSmt   = $this->getTpSmt();
        $tp      = $tpSmt->tp;
        $smt     = $tpSmt->smt;

        $isGuru   = $this->isGuru();
        $basePath = 'admin/presensi/guru/kartu';

        // Pilihan desain: landscape (dengan foto) | portrait (tanpa foto, QR besar)
        $desain = strtolower(trim((string) ($this->request->getGet('desain') ?? 'landscape')));
        if (!in_array($desain, ['landscape', 'portrait'], true)) $desain = 'landscape';

        $data = [
            'title'        => 'Cetak Kartu Absen Guru QR',
            'judul'        => 'Cetak Kartu Absen Guru / GTK',
            'subjudul'     => 'Cetak Kartu Berisi QR Code Presensi Guru / Tenaga Pendidik',
            'user'         => $user,
            'setting'      => $setting,
            'tp_active'    => $tp,
            'smt_active'   => $smt,
            'id_guru'      => $idGuru,
            'guru_options' => $guruOptions,
            'guru_list'    => $guruList,
            'terpotong'    => $terpotong,
            'batas_cetak'  => self::BATAS_CETAK,
            'catatan'      => $catatan,
            'logo_url'     => self::resolveLogoUrl($setting),
            'base_path'    => $basePath,
            'is_guru'      => $isGuru,
            'desain'       => $desain,
        ];

        return view('admin/presensi/kartu/guru_index', $data);
    }

    /**
     * Halaman Cetak Siap Print (A4 Grid / Single)
     */
    public function cetak()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->isAdmin() && !$this->isPanitia() && !$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $isStaffPenuh = $this->isAdmin() || $this->isPanitia();
        $idGuru = (int) ($this->request->getGet('id_guru') ?? 0);
        if (!$isStaffPenuh) {
            $idGuru = $this->currentGuruId((int) $user->id);
            if ($idGuru <= 0) {
                return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
            }
        }
        $db     = \Config\Database::connect();
        $hasRfidGuru = $db->fieldExists('nomor_rfid', 'master_guru');

        $guruList  = [];
        $terpotong = false;
        $catatan   = '';

        try {
            $builder = $db->table('master_guru')
                ->select('id_guru, id_user, nama_guru, nip, kode_guru, nuptk, jenis_ptk, tgs_tambahan, status_pegawai, jenis_kelamin, foto, username'
                    . ($hasRfidGuru ? ', nomor_rfid' : ''));

            if ($idGuru > 0) {
                $builder->where('id_guru', $idGuru);
            }

            // BATAS_CETAK + 1 dibaca supaya pemotongan bisa dilaporkan, bukan diam-diam.
            $guruList = $builder->orderBy('nama_guru', 'ASC')
                ->limit(self::BATAS_CETAK + 1)
                ->get()->getResult();
            $terpotong = count($guruList) > self::BATAS_CETAK;
            if ($terpotong) {
                $guruList = array_slice($guruList, 0, self::BATAS_CETAK);
            }
        } catch (\Throwable $e) {
            log_message('error', '[KartuGuru::cetak] daftar kartu guru gagal dibaca: ' . $e->getMessage());
            $guruList = [];
            $catatan  = 'Daftar kartu guru gagal dibaca dari basis data — periksa log aplikasi.';
        }

        foreach ($guruList as $g) {
            $g->qr_value = self::nomorKartuGuru($g);
            $g->foto_url = self::resolveFotoUrl($g->foto, $g->nama_guru);
            $g->jabatan  = !empty($g->tgs_tambahan) ? $g->tgs_tambahan : (!empty($g->jenis_ptk) ? $g->jenis_ptk : 'Guru / GTK');
        }

        $setting = $this->dashboardModel->getSetting();
        $tpSmt   = $this->getTpSmt();
        $tp      = $tpSmt->tp;
        $smt     = $tpSmt->smt;

        $judulCetak = 'Semua Guru / GTK';
        if ($idGuru > 0 && count($guruList) === 1) {
            $judulCetak = $guruList[0]->nama_guru;
        }

        $desain = strtolower(trim((string) ($this->request->getGet('desain') ?? 'landscape')));
        if (!in_array($desain, ['landscape', 'portrait'], true)) $desain = 'landscape';

        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');

        $data = [
            'setting'     => $setting,
            'tp_active'   => $tp,
            'smt_active'  => $smt,
            'guru_list'   => $guruList,
            'judul_cetak' => $judulCetak,
            'is_single'   => ($idGuru > 0 && count($guruList) === 1),
            'terpotong'   => $terpotong,
            'batas_cetak' => self::BATAS_CETAK,
            'catatan'     => $catatan,
            'logo_url'    => self::resolveLogoUrl($setting),
            'desain'      => $desain,
        ];

        return view('admin/presensi/kartu/guru_cetak', $data);
    }

    /**
     * Lihat Kartu QR Saya (Khusus Akun Guru)
     */
    public function kartuSaya()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $idGuru = $this->currentGuruId((int) $user->id);

        if ($idGuru > 0) {
            return redirect()->to('admin/presensi/guru/kartu?id_guru=' . $idGuru);
        }

        return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
    }

    /**
     * ID guru milik user yang sedang login (query dulu, sesi sebagai cadangan).
     */
    private function currentGuruId(int $userId): int
    {
        try {
            $row = \Config\Database::connect()->table('master_guru')
                ->select('id_guru')->where('id_user', $userId)->get()->getRow();
            if ($row && (int) ($row->id_guru ?? 0) > 0) {
                return (int) $row->id_guru;
            }
        } catch (\Throwable $e) {
        }

        return (int) (session()->get('id_guru') ?? 0);
    }

    /**
     * Satu nomor untuk semua perangkat scan.
     *
     * Nomor kartu RFID dipakai lebih dulu supaya QR/barcode yang dicetak,
     * chip RFID, dan input USB scanner semuanya membawa nomor yang identik.
     * Bila kartu RFID belum didaftarkan, tetap NIP → kode_guru → username →
     * id_guru seperti sebelumnya. Backend scanner menerima semuanya, jadi
     * kartu lama yang masih mencetak NIP tetap valid.
     */
    public static function nomorKartuGuru(object $row): string
    {
        $rfid = trim((string) ($row->nomor_rfid ?? ''));
        if ($rfid !== '') {
            return $rfid;
        }
        if (!empty($row->nip)) {
            return trim((string) $row->nip);
        }
        if (!empty($row->kode_guru)) {
            return trim((string) $row->kode_guru);
        }
        if (!empty($row->username)) {
            return trim((string) $row->username);
        }

        return (string) ($row->id_guru ?? '');
    }

    /**
     * Helper URL Foto Guru — delegasi ke AvatarService supaya semua halaman
     * memakai aturan resolusi yang sama (dan tidak bergantung host luar).
     */
    public static function resolveFotoUrl(?string $foto, ?string $nama): string
    {
        return \App\Services\AvatarService::tampilan($foto, $nama ?: 'Guru', '#7c3aed');
    }

    /**
     * Helper URL Logo Sekolah
     */
    public static function resolveLogoUrl(?object $setting): string
    {
        $defaultLogo = base_url('assets/img/logo.png');
        if (!$setting) return $defaultLogo;

        if (!empty($setting->logo_kiri)) {
            $cleanLogo = ltrim($setting->logo_kiri, '/');
            if (file_exists(FCPATH . $cleanLogo)) {
                return base_url($cleanLogo);
            }
        }
        if (!empty($setting->logo_kanan)) {
            $cleanLogo = ltrim($setting->logo_kanan, '/');
            if (file_exists(FCPATH . $cleanLogo)) {
                return base_url($cleanLogo);
            }
        }

        return $defaultLogo;
    }
}
