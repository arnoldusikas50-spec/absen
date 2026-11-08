<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\DashboardModel;

/**
 * Presensi Siswa — halaman mandiri untuk role siswa:
 * statistik kehadiran, status hari ini, riwayat + rekap bulanan.
 */
class Presensi extends BaseController
{
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Resolve baris master_siswa milik user login + kelas aktif TP/SMT berjalan.
     */
    private function resolveSiswa(): ?object
    {
        $user = $this->getUser();
        if (!$user || empty($user->username)) return null;

        $db = \Config\Database::connect();
        if (!$db->tableExists('master_siswa')) return null;

        $siswa = $db->table('master_siswa')
            ->where('username', $user->username)
            ->get()->getRow();
        if (!$siswa) return null;

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $siswa->nama_kelas = '-';
        $siswa->id_kelas   = 0;
        if ($tp && $smt && $db->tableExists('kelas_siswa')) {
            $k = $db->table('kelas_siswa ks')
                ->select('mk.nama_kelas, ks.id_kelas')
                ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas')
                ->where('ks.id_siswa', $siswa->id_siswa)
                ->where('ks.id_tp', $tp->id_tp)
                ->where('ks.id_smt', $smt->id_smt)
                ->get()->getRow();
            if ($k) {
                $siswa->nama_kelas = $k->nama_kelas;
                $siswa->id_kelas   = (int) $k->id_kelas;
            }
        }

        $siswa->qr_value = !empty($siswa->nis) ? trim($siswa->nis) : (!empty($siswa->nisn) ? trim($siswa->nisn) : (string)$siswa->id_siswa);
        $siswa->foto_url = \App\Controllers\Admin\KartuSiswa::resolveFotoUrl($siswa->foto ?? null, $siswa->nis ?? null);

        return $siswa;
    }

    /**
     * Kanonik untuk layar siswa. Status DB yang mungkin ditulis jalur lain
     * (PresensiSiswa::validasiMassal default 'TANPA_KETERANGAN', migrasi
     * default ENUM sama itu, scan QR, input piket) semuanya harus punya
     * ember sendiri — dulu TANPA_KETERANGAN jatuh ke 'lainnya' yang tidak
     * pernah ada di $out, sehingga hari-hari itu tetap memotong persentase
     * kehadiran tanpa pernah terlihat oleh siswanya. Rekap admin sudah
     * menampilkan kolom tanpa_keterangan sendiri; angka siswa dan admin
     * sekarang menjelaskan hal yang sama.
     */
    public static function normalizeStatus(?string $status): string
    {
        $st = strtoupper(trim((string) $status));
        if (in_array($st, ['HADIR', 'H'], true)) return 'hadir';
        if (in_array($st, ['IZIN', 'IJIN', 'I'], true)) return 'izin';
        if (in_array($st, ['SAKIT', 'S'], true)) return 'sakit';
        if (in_array($st, ['BDR', 'B'], true)) return 'bdr';
        if (in_array($st, ['ALPHA', 'ALPA', 'A'], true)) return 'alpha';
        if ($st === 'TANPA_KETERANGAN') return 'tanpa_keterangan';
        return 'lainnya';
    }

    private function monthStats(int $idSiswa, string $yyyymm): array
    {
        $out = [
            'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'bdr' => 0,
            'tanpa_keterangan' => 0, 'lainnya' => 0,
            'total' => 0, 'persen' => 0,
        ];
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('presensi_siswa')) return $out;
            $rows = $db->table('presensi_siswa')
                ->select('status')
                ->where('id_siswa', $idSiswa)
                ->like('tgl', $yyyymm, 'after')
                ->get()->getResult();
            foreach ($rows as $r) {
                $cat = self::normalizeStatus($r->status ?? '');
                $out['total']++;
                if (isset($out[$cat])) $out[$cat]++;
            }
            // Hari BDR dikecualikan: siswa tidak hadir karena kebijakan sekolah,
            // jadi tidak menurunkan persentase kehadiran pribadinya.
            $efektif = $out['total'] - $out['bdr'];
            if ($efektif > 0) {
                $out['persen'] = round(($out['hadir'] / $efektif) * 100, 1);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Siswa\\Presensi::monthStats] ' . $e->getMessage());
        }
        return $out;
    }

    public function index()
    {
        if (!$this->isSiswa()) {
            return redirect()->to('auth')->with('error', 'Akses khusus siswa');
        }

        $siswa = $this->resolveSiswa();
        if (!$siswa) {
            return redirect()->to('auth/logout')->with('message', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) $bulan = date('Y-m');

        $db = \Config\Database::connect();
        $today = null;
        try {
            if ($db->tableExists('presensi_siswa')) {
                $today = $db->table('presensi_siswa')
                    ->where('id_siswa', $siswa->id_siswa)
                    ->where('tgl', date('Y-m-d'))
                    ->get()->getRow();
            }
        } catch (\Throwable $e) {
            log_message('error', '[Siswa\Presensi::index] status hari ini gagal dibaca: ' . $e->getMessage());
        }

        $setting = $this->dashboardModel->getSetting();

        // null = skor tidak terbaca, dan layar harus mengatakannya. Versi lama
        // menuliskan 100 di dua tempat (controller dan view) sehingga guru
        // piket yang ledgernya error melihat "100 / Sangat Baik" — PoinSiswaModel
        // sudah sengaja mengembalikan null supaya angka palsu tidak pernah
        // mengalir ke master_siswa.poin_disiplin; di sini penyambungnya yang
        // membungkam informasi itu.
        $skorDisiplin = null;
        $riwayatDisiplin = [];
        try {
            $poinModel = new \App\Models\PoinSiswaModel();
            $poinModel->ensureTables();
            $skorDisiplin = $poinModel->getSkor((int) $siswa->id_siswa);
            $riwayatDisiplin = $poinModel->getRiwayat((int) $siswa->id_siswa, 10);
        } catch (\Throwable $e) {
            log_message('error', '[Siswa\Presensi::index] skor disiplin#' . $siswa->id_siswa . ' tidak terbaca: ' . $e->getMessage());
        }

        return view('members/siswa/presensi', [
            'title'      => 'Presensi Saya',
            'judul'      => 'Presensi Saya',
            'subjudul'   => 'Statistik, riwayat & rekap kehadiran',
            'user'       => $this->getUser(),
            'setting'    => $setting,
            'siswa'      => $siswa,
            'bulan'      => $bulan,
            'stats'      => $this->monthStats((int) $siswa->id_siswa, $bulan),
            'today'      => $today,
            'today_cat'  => $today ? self::normalizeStatus($today->status ?? '') : null,
            'skor_disiplin' => $skorDisiplin,
            'riwayat_disiplin' => $riwayatDisiplin,
            'logo_url'   => \App\Controllers\Admin\KartuSiswa::resolveLogoUrl($setting),
        ]);
    }

    /**
     * JSON riwayat bulan berjalan untuk tabel (filter bulan dinamis).
     */
    public function history()
    {
        if (!$this->isSiswa()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses khusus siswa']);
        }
        $siswa = $this->resolveSiswa();
        if (!$siswa) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data siswa tidak ditemukan']);
        }

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) $bulan = date('Y-m');

        $rows = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('presensi_siswa')) {
                // Kolom yang benar-benar dibaca layar. Tanpa select(), respons
                // membawa lat/lng masuk-pulang, akurasi meter, ip_masuk/ip_keluar
                // dan id_mapel/id_guru — jejak lokasi presisi siswa usia sekolah
                // tidak ada alasan keluar dari endpoint ini.
                $rows = $db->table('presensi_siswa')
                    ->select('id, tgl, jam_masuk, jam_keluar, status, keterangan')
                    ->where('id_siswa', $siswa->id_siswa)
                    ->like('tgl', $bulan, 'after')
                    ->orderBy('tgl', 'DESC')
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $r->kategori = self::normalizeStatus($r->status ?? '');
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Siswa\Presensi::history] riwayat#' . $siswa->id_siswa . ' gagal dibaca: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal memuat riwayat']);
        }

        return $this->response->setJSON([
            'status' => true,
            'data'   => $rows,
            'stats'  => $this->monthStats((int) $siswa->id_siswa, $bulan),
        ]);
    }
}
