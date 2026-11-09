<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\SiswaPresensiModel;

/**
 * PresensiSiswaQr
 * ─────────────────────────────────────────────────────────────────────────────
 * Mengelola absensi masuk/keluar siswa menggunakan QR Code Scanner (kamera).
 *
 * QR Card siswa dikodekan sebagai NIS atau NISN siswa.
 * Alur scan:
 *   1. Guru membuka /guru/presensi-siswa
 *   2. Kamera scan QR Code → nilai QR dikirim ke POST /guru/presensi-siswa/scan
 *   3. Controller cari siswa berdasarkan NIS/NISN, simpan check-in/check-out
 *   4. Response JSON dikembalikan untuk ditampilkan di UI
 */
class PresensiSiswaQr extends BaseController
{
    protected DashboardModel    $dashModel;
    protected SiswaPresensiModel $presensiModel;

    private const VALID_STATUS = ['HADIR', 'SAKIT', 'IZIN', 'ALPA', 'TANPA_KETERANGAN', 'BDR'];

    /** Cookie penanda perangkat kiosk yang sudah mendaftar dengan token. */
    private const KIOSK_GATE_COOKIE = 'qr_kiosk_gate';

    public function __construct()
    {
        $this->dashModel     = new DashboardModel();
        $this->presensiModel = new SiswaPresensiModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OTORISASI & KEAMANAN HASH URL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Helper pemetaan nama hari Indonesia
     */
    public static function getHariIndo(string $day): string
    {
        $map = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];
        return $map[$day] ?? $day;
    }

    /**
     * Generate security hash untuk URL presensi siswa (valid per user per hari).
     * 32 hex chars (128-bit). Link lama 16-char tetap diterima saat validasi.
     */
    public static function generateHash(int $userId, ?string $date = null): string
    {
        $date = $date ?: date('Y-m-d');
        $salt = (string) (config('App')->appTimezone ?? 'Asia/Makassar') . (config('Encryption')->key ?? 'absen_ikas_secret_salt_2026');
        return substr(hash_hmac('sha256', "presensi-siswa:{$userId}:{$date}", $salt), 0, 32);
    }

    /**
     * Validasi security hash (toleransi hari ini dan kemarin).
     * Terima format baru 32-char dan legacy 16-char (prefix) selama transisi.
     */
    public static function isValidHash(?string $hash, int $userId): bool
    {
        $hash = trim((string) ($hash ?? ''));
        if ($hash === '' || strlen($hash) < 8 || strlen($hash) > 64) return false;
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        foreach ([$today, $yesterday] as $d) {
            $expected = self::generateHash($userId, $d);
            if (hash_equals($expected, $hash)) return true;
            // Legacy 16-char: prefix dari hash baru.
            if (hash_equals(substr($expected, 0, 16), substr($hash, 0, 16)) && strlen($hash) >= 16 && strlen($expected) >= 16) {
                // Hanya terima bila 16 char pertama cocok persis dan panjang hash 16.
                if (strlen($hash) === 16 && hash_equals(substr($expected, 0, 16), $hash)) return true;
            }
        }
        return false;
    }

    /**
     * Ambil hash dari request: header diutamakan (hindari tersimpan di URL/history),
     * fallback ke POST/GET untuk kompatibilitas.
     */
    protected function requestHash(): string
    {
        try {
            $h = trim((string) ($this->request->getHeaderLine('X-QR-Hash') ?? ''));
            if ($h !== '') return $h;
        } catch (\Throwable $e) {
        }
        return trim((string) ($this->request->getPost('h') ?? $this->request->getGet('h')
            ?? $this->request->getPost('token') ?? $this->request->getGet('token') ?? ''));
    }

    /**
     * Token kiosk opsional untuk jalur publik (/scanner).
     * Bila kolom absensi_setting.qr_kiosk_token diisi admin, SEMUA jalur publik
     * wajib membawa token yang sama: header X-Kiosk-Token, field `k`
     * (POST/GET), atau cookie gerbang perangkat (lihat pasangKioskGate()).
     *
     * @return string|null pesan penolakan, atau null bila lolos/tidak diaktifkan
     */
    protected function checkKioskToken(): ?string
    {
        $expected = $this->tokenKioskDiset();
        if ($expected === '') {
            return null;
        }

        if ($this->kioskGateLolos($expected)) {
            return null;
        }

        try {
            log_message('warning', '[PresensiSiswaQr] kiosk token invalid ip={ip} uri={uri}', [
                'ip'  => $this->request->getIPAddress(),
                'uri' => uri_string(),
            ]);
        } catch (\Throwable $e) {
        }

        return 'Token kiosk tidak valid. Hubungi admin untuk aktivasi perangkat ini.';
    }

    /**
     * Token yang dikonfigurasi admin. '' = fitur mati (endpoint publik terbuka,
     * persis seperti sebelum kolom ini ada).
     *
     * Sengaja TIDAK dipakai `setting()` biasa: halaman kiosk memuat setting
     * lengkap berkali-kali dan kolom ini harus tetap terbaca walau form
     * pengaturan belum pernah disimpan lagi setelah migrasi.
     */
    protected function tokenKioskDiset(): string
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('absensi_setting')) {
                return '';
            }
            if (!$db->fieldExists('qr_kiosk_token', 'absensi_setting')) {
                return '';
            }
            $row = $db->table('absensi_setting')
                ->select('qr_kiosk_token')->orderBy('id', 'ASC')->limit(1)->get()->getRow();

            $token = trim((string) ($row->qr_kiosk_token ?? ''));
            if ($token === '') {
                // Sekali per request cycle: pengingat bahwa endpoint publik ini
                // menerima QR/NIS mentah tanpa token sampai admin mengisinya.
                static $sudahDilaporkan = false;
                if (!$sudahDilaporkan) {
                    $sudahDilaporkan = true;
                    log_message('warning', '[PresensiSiswaQr] qr_kiosk_token kosong — endpoint scan publik menerima QR/NIS tanpa token. Isi token kiosk di pengaturan presensi.');
                }
            }

            return $token;
        } catch (\Throwable $e) {
            // Fail-open supaya gerbang sekolah tetap bisa scan saat basis data
            // bermasalah, tapi tidak lagi diam-diam: tanpa catatan ini
            // kegagalan baca terlihat persis sama dengan "token tidak aktif".
            log_message('error', '[PresensiSiswaQr] gagal membaca qr_kiosk_token, endpoint publik dibiarkan terbuka: '
                . $e->getMessage());

            return '';
        }
    }

    /**
     * Token yang dibawa request ini (header → field k → cookie gerbang).
     * Cookie kiosk membawa nilai turunan, bukan token aslinya, jadi berkas
     * cookie perangkat yang bocor tidak membocorkan rahasia yang dipakai admin
     * untuk mendaftarkan perangkat lain.
     */
    protected function tokenKioskDibawa(): string
    {
        try {
            $h = trim((string) $this->request->getHeaderLine('X-Kiosk-Token'));
            if ($h !== '') {
                return $h;
            }
        } catch (\Throwable $e) {
        }

        $langsung = trim((string) ($this->request->getPost('k') ?? $this->request->getGet('k') ?? ''));
        if ($langsung !== '') {
            return $langsung;
        }

        try {
            return trim((string) ($this->request->getCookie(self::KIOSK_GATE_COOKIE) ?? ''));
        } catch (\Throwable $e) {
        }

        return '';
    }

    /** Nilai turunan token untuk cookie gerbang (deterministik, tanpa penyimpanan). */
    public static function kioskGateVerifier(string $token): string
    {
        $salt = (string) (config('Encryption')->key ?? 'absen_ikas_secret_salt_2026');

        return hash_hmac('sha256', 'kiosk-gate:' . $token, $salt);
    }

    protected function kioskGateLolos(string $expected): bool
    {
        $dibawa = $this->tokenKioskDibawa();
        if ($dibawa === '') {
            return false;
        }
        if (hash_equals($expected, $dibawa)) {
            return true;
        }

        return hash_equals(self::kioskGateVerifier($expected), $dibawa);
    }

    /**
     * Daftarkan perangkat kiosk: simpan verifier token di cookie HttpOnly.
     * Nilai cookienya bukan token aslinya, jadi riwayat browser atau berkas
     * cookie perangkat tidak membocorkan rahasia yang dipakai admin untuk
     * mendaftarkan perangkat lain.
     */
    protected function pasangKioskGate($response, string $token): void
    {
        $response->setCookie(
            self::KIOSK_GATE_COOKIE,
            self::kioskGateVerifier($token),
            30 * 86400,
            '',
            '/',
            '',
            $this->request->isSecure(),
            true,
            'Lax'
        );
    }

    /** URL halaman kiosk untuk kelas ini (tanpa token di query string). */
    protected function urlKiosk($idKelas = null): string
    {
        $path = 'scanner';
        $id   = (int) ($idKelas ?? 0);
        if ($id > 0) {
            $path .= '/' . $id;
        }

        return site_url($path);
    }

    /** Samarkan identifier untuk log (hindari NIS penuh di file log). */
    protected static function maskQrForLog(string $qr): string
    {
        $qr = trim($qr);
        $len = strlen($qr);
        if ($len <= 4) return str_repeat('*', max($len, 0));
        return substr($qr, 0, 2) . str_repeat('*', $len - 4) . substr($qr, -2);
    }

    /**
     * Dapatkan URL resmi dengan hash untuk user tertentu
     */
    public static function getHashedUrl(?int $idKelas = null, ?int $userId = null): string
    {
        if (!$userId) {
            $userId = (int) (session()->get('user_id') ?? 0);
        }
        $hash = self::generateHash($userId);
        $path = 'guru/presensi-siswa';
        if ($idKelas && (int)$idKelas > 0) {
            $path .= '/kelas/' . (int)$idKelas;
        }
        return site_url($path . '?h=' . $hash);
    }

    /**
     * Periksa apakah pengguna yang login adalah Admin, Kepala Sekolah, Guru Piket, atau Wali Kelas
     * Mengembalikan array dengan info otorisasi.
     * Wali kelas hanya diberi akses terbatas pada kelas perwaliannya (wali_kelas = [id_kelas...]).
     */
    public function checkAuthorization(): array
    {
        if (!$this->isLoggedIn()) {
            return [
                'authorized' => false,
                'is_admin'   => false,
                'is_kepsek'  => false,
                'is_piket'   => false,
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => null,
                'guru'       => null,
                'reason'     => 'unauthenticated',
                'message'    => 'Silakan login terlebih dahulu untuk mengakses menu ini.'
            ];
        }

        $user = $this->getUser();
        if (!$user) {
            return [
                'authorized' => false,
                'is_admin'   => false,
                'is_kepsek'  => false,
                'is_piket'   => false,
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => null,
                'guru'       => null,
                'reason'     => 'user_not_found',
                'message'    => 'Sesi pengguna tidak valid.'
            ];
        }

        // 1. Cek Admin
        if ($this->isAdmin() || $this->inGroup('admin') || (string)$this->session->get('role') === '1') {
            return [
                'authorized' => true,
                'is_admin'   => true,
                'is_kepsek'  => false,
                'is_piket'   => true, // Admin punya akses penuh
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => $user,
                'guru'       => null,
                'reason'     => 'admin',
                'message'    => 'Akses sebagai Administrator'
            ];
        }

        // 1b. Cek Kepala Sekolah — group/role kepsek langsung lolos
        $roleName = strtolower(trim((string) ($this->session->get('role_name') ?? '')));
        if ($this->inGroup('kepsek') || in_array($roleName, ['kepsek', 'kepala sekolah', 'kepala_sekolah'], true)) {
            return [
                'authorized' => true,
                'is_admin'   => false,
                'is_kepsek'  => true,
                'is_piket'   => true, // Kepsek punya akses penuh seperti admin
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => $user,
                'guru'       => null,
                'reason'     => 'kepsek',
                'message'    => 'Akses sebagai Kepala Sekolah'
            ];
        }

        // 1c. Cek guru yang menjabat Kepala Sekolah (tugas tambahan / jabatan / data setting sekolah)
        if ($this->isKepalaSekolah((int) $user->id)) {
            return [
                'authorized' => true,
                'is_admin'   => false,
                'is_kepsek'  => true,
                'is_piket'   => true, // Kepsek punya akses penuh seperti admin
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => $user,
                'guru'       => null,
                'reason'     => 'kepsek',
                'message'    => 'Akses sebagai Kepala Sekolah'
            ];
        }

        // 2. Cek Guru Piket
        $db = \Config\Database::connect();
        $guru = null;
        try {
            $guru = $db->table('master_guru')
                ->where('id_user', $user->id)
                ->get()->getRow();
        } catch (\Throwable $e) {}

        if (!$guru) {
            return [
                'authorized' => false,
                'is_admin'   => false,
                'is_kepsek'  => false,
                'is_piket'   => false,
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => $user,
                'guru'       => null,
                'reason'     => 'not_guru',
                'message'    => 'Akses ditolak: Menu ini hanya dapat diakses oleh Admin, Kepala Sekolah, Guru Piket, atau Wali Kelas.'
            ];
        }

        $idGuru  = (int) $guru->id_guru;
        $hariIni = self::getHariIndo(date('l'));
        $tp      = $this->dashModel->getTahunActive();
        $smt     = $this->dashModel->getSemesterActive();

        $isPiket = false;

        // a. Cek jadwal piket hari ini di piket_jadwal
        if ($db->tableExists('piket_jadwal')) {
            $builder = $db->table('piket_jadwal')
                ->where('id_guru', $idGuru)
                ->where('hari', $hariIni);
            if ($tp && $smt) {
                $builder->groupStart()
                    ->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)
                    ->orWhere('id_tp', 0)
                ->groupEnd();
            }
            if ($builder->countAllResults() > 0) {
                $isPiket = true;
            }
        }

        // b. Cek apakah ada tugas tambahan piket di master_guru
        if (!$isPiket && !empty($guru->tgs_tambahan) && stripos($guru->tgs_tambahan, 'piket') !== false) {
            $isPiket = true;
        }

        // c. Cek group / role piket
        if (!$isPiket && ($this->inGroup('piket') || (string)$this->session->get('role_name') === 'piket')) {
            $isPiket = true;
        }

        // d. Cek jika guru terdaftar di piket_jadwal secara umum (jika jadwal hari ini belum diset sama sekali)
        if (!$isPiket && $db->tableExists('piket_jadwal')) {
            $totalPiketHariIni = $db->table('piket_jadwal')->where('hari', $hariIni)->countAllResults();
            if ($totalPiketHariIni === 0) {
                $isPiketTim = $db->table('piket_jadwal')->where('id_guru', $idGuru)->countAllResults() > 0;
                if ($isPiketTim) {
                    $isPiket = true;
                }
            }
        }

        if ($isPiket) {
            return [
                'authorized' => true,
                'is_admin'   => false,
                'is_kepsek'  => false,
                'is_piket'   => true,
                'is_wali'    => false,
                'wali_kelas' => [],
                'user'       => $user,
                'guru'       => $guru,
                'reason'     => 'guru_piket',
                'message'    => 'Akses sebagai Guru Piket'
            ];
        }

        // 3. Cek Wali Kelas — akses terbatas hanya pada kelas perwaliannya
        $waliKelas = $this->getWaliKelasIds($idGuru, (int) $user->id);
        if (!empty($waliKelas)) {
            return [
                'authorized' => true,
                'is_admin'   => false,
                'is_kepsek'  => false,
                'is_piket'   => false,
                'is_wali'    => true,
                'wali_kelas' => $waliKelas,
                'user'       => $user,
                'guru'       => $guru,
                'reason'     => 'wali_kelas',
                'message'    => 'Akses sebagai Wali Kelas'
            ];
        }

        return [
            'authorized' => false,
            'is_admin'   => false,
            'is_kepsek'  => false,
            'is_piket'   => false,
            'is_wali'    => false,
            'wali_kelas' => [],
            'user'       => $user,
            'guru'       => $guru,
            'reason'     => 'not_piket_today',
            'message'    => 'Hanya dapat diakses oleh Guru Piket yang bertugas hari ini (' . $hariIni . '), Wali Kelas, Kepala Sekolah, atau Admin.'
        ];
    }

    /**
     * Ambil daftar id_kelas perwalian seorang guru pada TP/SMT aktif.
     * Sumber utama: jabatan_guru id_jabatan = 4 (Wali Kelas).
     * Fallback: master_kelas.guru_id (kolom wali di master kelas).
     *
     * @return int[] daftar id_kelas unik
     */
    private function getWaliKelasIds(int $idGuru, int $userId = 0): array
    {
        $ids = [];
        if ($idGuru <= 0) {
            return $ids;
        }

        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            return $ids;
        }

        $tp  = $this->dashModel->getTahunActive();
        $smt = $this->dashModel->getSemesterActive();

        // 1. jabatan_guru id_jabatan = 4 (Wali Kelas)
        try {
            if ($db->tableExists('jabatan_guru')) {
                $builder = $db->table('jabatan_guru')->select('id_kelas')->where('id_guru', $idGuru)->where('id_jabatan', 4);
                if ($tp && $db->fieldExists('id_tp', 'jabatan_guru')) {
                    $builder->where('id_tp', $tp->id_tp);
                }
                if ($smt && $db->fieldExists('id_smt', 'jabatan_guru')) {
                    $builder->where('id_smt', $smt->id_smt);
                }
                foreach ($builder->get()->getResult() as $r) {
                    if ((int) ($r->id_kelas ?? 0) > 0) {
                        $ids[] = (int) $r->id_kelas;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // 2. Fallback: master_kelas.guru_id
        try {
            if ($db->tableExists('master_kelas') && $db->fieldExists('guru_id', 'master_kelas')) {
                $builder = $db->table('master_kelas')->select('id_kelas')->where('guru_id', $idGuru);
                if ($tp && $db->fieldExists('id_tp', 'master_kelas')) {
                    $builder->where('id_tp', $tp->id_tp);
                }
                if ($smt && $db->fieldExists('id_smt', 'master_kelas')) {
                    $builder->where('id_smt', $smt->id_smt);
                }
                foreach ($builder->get()->getResult() as $r) {
                    if ((int) ($r->id_kelas ?? 0) > 0) {
                        $ids[] = (int) $r->id_kelas;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Deteksi apakah user adalah Kepala Sekolah.
     * Mencakup: tugas tambahan di users / master_guru (kolom tugas_tambahan
     * maupun tgs_tambahan), jabatan level, dan kecocokan nama/NIP dengan
     * data setting sekolah.
     */
    private function isKepalaSekolah(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            return false;
        }

        $containsKepsek = static function (?string $v): bool {
            $v = strtolower(trim((string) $v));
            return $v !== '' && (str_contains($v, 'kepala sekolah') || str_contains($v, 'kepsek'));
        };

        // 1. users.tugas_tambahan
        try {
            if ($db->tableExists('users') && $db->fieldExists('tugas_tambahan', 'users')) {
                $row = $db->table('users')->select('tugas_tambahan')->where('id', $userId)->get()->getRow();
                if ($row && $containsKepsek($row->tugas_tambahan ?? null)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        // 2. master_guru.tugas_tambahan / tgs_tambahan
        $guruRow = null;
        try {
            if ($db->tableExists('master_guru')) {
                $cols = 'id_guru, nama_guru, nip';
                if ($db->fieldExists('tugas_tambahan', 'master_guru')) {
                    $cols .= ', tugas_tambahan';
                }
                if ($db->fieldExists('tgs_tambahan', 'master_guru')) {
                    $cols .= ', tgs_tambahan';
                }
                $guruRow = $db->table('master_guru')->select($cols)->where('id_user', $userId)->get()->getRow();
                if ($guruRow) {
                    if ($containsKepsek($guruRow->tugas_tambahan ?? null) || $containsKepsek($guruRow->tgs_tambahan ?? null)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // 3. Jabatan struktural (id_jabatan = 1 atau level mengandung kepsek)
        try {
            if ($db->tableExists('jabatan_guru') && $guruRow && !empty($guruRow->id_guru)) {
                $jg = $db->table('jabatan_guru')->select('id_jabatan')->where('id_guru', (int) $guruRow->id_guru)->get()->getResult();
                foreach ($jg as $r) {
                    if ((int) ($r->id_jabatan ?? 0) === 1) {
                        return true;
                    }
                }
            }
            if ($db->tableExists('master_guru') && $db->tableExists('jabatan_guru') && $db->tableExists('level_guru')) {
                $row = $db->table('master_guru a')
                    ->select('lg.level as jabatan')
                    ->join('jabatan_guru jg', 'jg.id_guru = a.id_guru', 'left')
                    ->join('level_guru lg', 'lg.id_level = jg.id_jabatan', 'left')
                    ->where('a.id_user', $userId)
                    ->get()->getRow();
                if ($row && $containsKepsek($row->jabatan ?? null)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        // 4. Kecocokan nama / NIP dengan setting profil sekolah
        try {
            $setting = $this->dashModel->getSetting();
            $kepsekNama = strtolower(trim((string) ($setting->kepsek ?? '')));
            $kepsekNip  = trim((string) ($setting->nip ?? ''));
            if ($guruRow) {
                $namaGuru = strtolower(trim((string) ($guruRow->nama_guru ?? '')));
                if ($kepsekNama !== '' && $namaGuru !== '' && ($namaGuru === $kepsekNama || str_contains($namaGuru, $kepsekNama) || str_contains($kepsekNama, $namaGuru))) {
                    return true;
                }
                $guruNip = trim((string) ($guruRow->nip ?? ''));
                if ($kepsekNip !== '' && $guruNip !== '' && $guruNip === $kepsekNip) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN UTAMA (KHUSUS ADMIN, KEPALA SEKOLAH, GURU PIKET & WALI KELAS DENGAN URL HASH)
    // Wali kelas dikunci hanya pada kelas perwaliannya.
    // ─────────────────────────────────────────────────────────────────────────

    public function index($idKelas = null)
    {
        // 1. Otorisasi: HANYA Admin, Kepala Sekolah, Guru Piket, dan Wali Kelas
        $auth = $this->checkAuthorization();
        if (!$auth['authorized']) {
            if ($auth['reason'] === 'unauthenticated') {
                return redirect()->to('auth')->with('error', $auth['message']);
            }
            $target = ($this->inGroup('guru') || (string)$this->session->get('role') === '2') ? 'presensi' : 'dashboard';
            $this->session->setFlashdata('error', $auth['message']);
            return redirect()->to($target)->with('error', $auth['message']);
        }

        $user       = $auth['user'];
        $isAdmin    = $auth['is_admin'];
        $isKepsek   = $auth['is_kepsek'] ?? false;
        $isPiket    = $auth['is_piket'];
        $isWali     = $auth['is_wali'] ?? false;
        $waliKelas  = array_values(array_unique(array_map('intval', (array) ($auth['wali_kelas'] ?? []))));
        $guru       = $auth['guru'];
        $selectedKelasId = $idKelas !== null ? (int)$idKelas : 0;

        // 1b. Wali kelas dikunci pada kelas perwaliannya — tolak / paksa scope sejak URL
        if ($isWali && !empty($waliKelas)) {
            if ($selectedKelasId > 0 && !in_array($selectedKelasId, $waliKelas, true)) {
                $selectedKelasId = $waliKelas[0];
                $params = $this->request->getGet();
                unset($params['h'], $params['token']);
                $params['h'] = self::generateHash($user->id);
                return redirect()->to(site_url('guru/presensi-siswa/kelas/' . $selectedKelasId . '?' . http_build_query($params)));
            }
            if ($selectedKelasId <= 0) {
                $selectedKelasId = $waliKelas[0];
            }
        }

        // 2. Hash Pengaman pada URL
        $validHash = self::generateHash($user->id);
        $urlHash   = trim((string) ($this->request->getGet('h') ?? $this->request->getGet('token') ?? ''));

        // Jika hash belum ada di URL atau tidak cocok, redirect ke URL dengan hash resmi
        if (!self::isValidHash($urlHash, $user->id)) {
            $path = 'guru/presensi-siswa' . ($selectedKelasId ? '/kelas/' . $selectedKelasId : '');
            $params = $this->request->getGet();
            unset($params['h'], $params['token']);
            $params['h'] = $validHash;
            return redirect()->to(site_url($path . '?' . http_build_query($params)));
        }

        $setting = $this->dashModel->getSetting();
        $tp      = $this->dashModel->getTahunActive();
        $smt     = $this->dashModel->getSemesterActive();

        // Nama guru pencatat / petugas
        $namaGuru = $guru?->nama_guru ?? ($user->first_name ?? ($isAdmin ? 'Administrator' : ($isKepsek ? 'Kepala Sekolah' : ($isWali ? 'Wali Kelas' : 'Guru Piket'))));

        // Tanggal & kelas terpilih
        $tanggal = date('Y-m-d');
        $stats   = $this->getStatsHariIni($tanggal, $selectedKelasId ?: null);

        // Daftar kelas aktif untuk dropdown
        $kelasList = [];
        try {
            $db      = \Config\Database::connect();
            $builder = $db->table('master_kelas')->select('id_kelas, nama_kelas');
            if ($tp && $smt) {
                $builder->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt);
            }
            $kelasList = $builder->orderBy('nama_kelas', 'ASC')->get()->getResult();
            if (empty($kelasList)) {
                $kelasList = $db->table('master_kelas')
                    ->select('id_kelas, nama_kelas')
                    ->orderBy('nama_kelas', 'ASC')
                    ->get()->getResult();
            }
        } catch (\Throwable $e) {
            $kelasList = [];
        }

        // Wali kelas hanya melihat kelas perwaliannya di dropdown
        if ($isWali && !empty($waliKelas)) {
            $kelasList = array_values(array_filter($kelasList, static function ($k) use ($waliKelas) {
                return in_array((int) ($k->id_kelas ?? 0), $waliKelas, true);
            }));
        }

        $petugasLabel = $isAdmin ? 'Administrator' : ($isKepsek ? 'Kepala Sekolah' : ($isWali ? 'Wali Kelas' : 'Guru Piket'));
        $titleRole    = $isAdmin ? 'Admin' : ($isKepsek ? 'Kepala Sekolah' : ($isWali ? 'Wali Kelas' : 'Guru Piket'));

        $data = [
            'user'          => $user,
            'isLoggedIn'    => true,
            'setting'       => $setting,
            'namaGuru'      => $namaGuru,
            'tp_active'     => $tp,
            'smt_active'    => $smt,
            'tanggal'       => $tanggal,
            'stats'         => $stats,
            'kelasList'     => $kelasList,
            'selectedKelas' => $selectedKelasId,
            'judul'         => 'Presensi Siswa QR Code',
            'subjudul'      => 'Khusus Petugas Piket, Wali Kelas, Kepala Sekolah & Administrator',
            'title'         => 'Presensi Siswa QR | ' . $titleRole,
            'hash'          => $validHash,
            'token'         => $validHash,
            'scan_url'      => site_url('guru/presensi-siswa/scan'),
            'history_url'   => site_url('guru/presensi-siswa/history'),
            'is_admin'      => $isAdmin,
            'is_kepsek'     => $isKepsek,
            'is_piket'      => $isPiket,
            'is_wali'       => $isWali,
            'wali_kelas'    => $waliKelas,
            'petugas_label' => $petugasLabel,
            'mode_tab'      => $this->getModeTabState($tanggal),
        ];

        // Satu URL dua tampilan: desktop → versi kiosk, HP → versi mobile
        if (!$this->wantMobileView()) {
            $data['title'] = 'Scanner QR Siswa Desktop | ' . ($setting->nama_sekolah ?? 'Presensi & KBM');
            return view('admin/presensi/scanner_siswa_desktop', $data);
        }

        return view('members/guru/presensi_siswa_qr', $data);
    }

    /**
     * Deteksi perangkat mobile via User-Agent.
     * Bisa dioverride paksa via ?view=desktop|mobile (misal untuk uji coba).
     */
    private function wantMobileView(): bool
    {
        $force = strtolower(trim((string) ($this->request->getGet('view') ?? '')));
        if ($force === 'mobile') return true;
        if ($force === 'desktop') return false;
        try {
            return (bool) $this->request->getUserAgent()->isMobile();
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN SCANNER QR DESKTOP (ADMIN / KIOSK / PETUGAS)
    // Otomatis tampilkan versi mobile bila dibuka dari HP.
    // ─────────────────────────────────────────────────────────────────────────

    public function desktop($idKelas = null)
    {
        $isLoggedIn = $this->isLoggedIn();

        // ── Gerbang kiosk publik ─────────────────────────────────────────
        // Halaman ini dulu satu-satunya jalur publik yang TIDAK memeriksa
        // token: siapa pun bisa memuatnya dan melihat daftar kelas, statistik
        // kehadiran, dan identitas sekolah, walau scan & history sudah terkunci.
        // Perangkat yang sudah mendaftar terbawa cookie gerbang, jadi kiosk
        // tidak perlu mengetik token ulang tiap reload.
        $tokenKiosk = $isLoggedIn ? '' : $this->tokenKioskDiset();
        $gateBaru   = false;
        if ($tokenKiosk !== '') {
            $tokenUrl = trim((string) ($this->request->getGet('k') ?? ''));
            if ($tokenUrl !== '' && hash_equals($tokenKiosk, $tokenUrl)) {
                $gateBaru = true;
            } elseif (!$this->kioskGateLolos($tokenKiosk)) {
                return $this->response->setStatusCode(403)->setBody(view('admin/presensi/scanner_kiosk_kunci', [
                    'judul'   => 'Scanner QR Terkunci',
                    'action'  => $this->urlKiosk($idKelas),
                    'pesan'   => (string) (session()->get('kiosk_kunci_gagal') ?? ''),
                ]));
            }
        }

        $user       = $isLoggedIn ? $this->getUser() : null;
        $setting    = $this->dashModel->getSetting();
        $tp         = $this->dashModel->getTahunActive();
        $smt        = $this->dashModel->getSemesterActive();

        // Nama petugas pencatat / admin
        $namaGuru = '';
        if ($user) {
            try {
                $db = \Config\Database::connect();
                $guruRow = $db->table('master_guru')
                    ->select('nama_guru')
                    ->where('id_user', $user->id)
                    ->get()->getRow();
                $namaGuru = $guruRow?->nama_guru ?? ($user->first_name ?? 'Administrator');
            } catch (\Throwable $e) {
                $namaGuru = $user->first_name ?? 'Administrator';
            }
        } else {
            $namaGuru = 'Petugas Scanner (Kiosk)';
        }

        $tanggal         = date('Y-m-d');
        $selectedKelasId = $idKelas !== null ? (int)$idKelas : 0;
        if ($selectedKelasId > 0) {
            try {
                $exists = \Config\Database::connect()->table('master_kelas')
                    ->where('id_kelas', $selectedKelasId)->countAllResults() > 0;
                if (!$exists) $selectedKelasId = 0;
            } catch (\Throwable $e) {
                $selectedKelasId = 0;
            }
        }
        $stats = $this->getStatsHariIni($tanggal, $selectedKelasId ?: null);

        // Daftar kelas aktif untuk filter dropdown
        $kelasList = [];
        try {
            $db      = \Config\Database::connect();
            $builder = $db->table('master_kelas')->select('id_kelas, nama_kelas');
            if ($tp && $smt) {
                $builder->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt);
            }
            $kelasList = $builder->orderBy('nama_kelas', 'ASC')->get()->getResult();
            if (empty($kelasList)) {
                $kelasList = $db->table('master_kelas')
                    ->select('id_kelas, nama_kelas')
                    ->orderBy('nama_kelas', 'ASC')
                    ->get()->getResult();
            }
        } catch (\Throwable $e) {
            $kelasList = [];
        }

        // Jendela operasional scan untuk badge kiosk (ringan, best-effort)
        $qrWindow = null;
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('absensi_setting')) {
                $ar = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
                if ($ar && (int) ($ar->qr_scan_aktif ?? 0) === 1) {
                    $qrWindow = substr((string) ($ar->qr_scan_mulai ?? '05:30:00'), 0, 5)
                        . '–' . substr((string) ($ar->qr_scan_selesai ?? '18:00:00'), 0, 5);
                }
            }
        } catch (\Throwable $e) {
        }

        $data = [
            'user'          => $user,
            'isLoggedIn'    => $isLoggedIn,
            'setting'       => $setting,
            'namaGuru'      => $namaGuru,
            'tp_active'     => $tp,
            'smt_active'    => $smt,
            'tanggal'       => $tanggal,
            'stats'         => $stats,
            'kelasList'     => $kelasList,
            'selectedKelas' => $selectedKelasId,
            'qr_window'     => $qrWindow,
            'judul'         => 'Scanner QR Siswa (Desktop)',
            'subjudul'      => 'Presensi Siswa QR Code & Barcode Scanner Desktop',
            'title'         => 'Scanner QR Siswa Desktop | ' . ($setting->nama_sekolah ?? 'Presensi & KBM'),
            // Kiosk publik (/scanner): tanpa login & tanpa hash — samakan dengan
            // view desktop yang memakai endpoint scanner/* (tanpa otorisasi).
            'hash'          => '',
            'token'         => '',
            'scan_url'      => site_url('scanner/scan'),
            'history_url'   => site_url('scanner/history'),
            'mode_tab'      => $this->getModeTabState($tanggal),
        ];

        // Satu URL dua tampilan: HP → versi mobile, desktop → versi kiosk
        if ($this->wantMobileView()) {
            $data['title'] = 'Scan QR Presensi Siswa | ' . ($setting->nama_sekolah ?? 'Presensi & KBM');
            // CI4 tidak punya Response::setView() (itu API CI3); render view
            // lewat helper lalu tempelkan sebagai body response.
            $response = $this->response->setBody(view('members/guru/presensi_siswa_qr', $data));
        } else {
            $response = $this->response->setBody(view('admin/presensi/scanner_siswa_desktop', $data));
        }

        if ($gateBaru) {
            // Token datang lewat URL sekali untuk mendaftarkan perangkat.
            // Halaman view membuang ?k= dari address bar (lihat skripnya),
            // jadi token tidak tertinggal di riwayat browser kiosk.
            $this->pasangKioskGate($response, trim((string) $this->request->getGet('k')));
        }

        return $response;
    }

    /**
     * Pendaftaran perangkat kiosk lewat form di halaman terkunci.
     * Token dikirim sebagai POST supaya tidak tertinggal di riwayat URL.
     */
    public function unlockKiosk($idKelas = null)
    {
        $target = $this->urlKiosk($idKelas);
        $tokenKiosk = $this->tokenKioskDiset();

        if ($tokenKiosk === '') {
            return redirect()->to($target);
        }

        $token = trim((string) ($this->request->getPost('k') ?? ''));
        if ($token === '' || !hash_equals($tokenKiosk, $token)) {
            try {
                log_message('warning', '[PresensiSiswaQr] percobaan buka kunci kiosk gagal ip={ip}', [
                    'ip' => $this->request->getIPAddress(),
                ]);
            } catch (\Throwable $e) {
            }

            return redirect()->to($target)->with('kiosk_kunci_gagal', 'Token tidak cocok. Periksa kembali dengan admin.');
        }

        $response = redirect()->to($target);
        $this->pasangKioskGate($response, $token);

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Proses hasil scan QR
    // POST: qr_value, mode (masuk|keluar), id_kelas (opsional)
    // ─────────────────────────────────────────────────────────────────────────

    public function scanQr()
    {
        // Rate limit: kiosk bersama 1 IP — 240/menit (~4 scan/detik) agar antrean padat tak terblokir
        try {
            $throttler = \Config\Services::throttler();
            if ($throttler->check('qrscan_' . md5($this->request->getIPAddress()), 240, MINUTE) === false) {
                return $this->response->setStatusCode(429)->setJSON([
                    'status' => false, 'type' => 'error',
                    'message' => 'Terlalu banyak scan. Tunggu sebentar lalu coba lagi.',
                ]);
            }
        } catch (\Throwable $e) {
            // Throttler tak tersedia (mis. kurang ekstensi) → lanjut tanpa batas
        }

        // Otorisasi & validasi hash jika dipanggil dari /guru/presensi-siswa atau /presensi-siswa
        $uriStr = uri_string();
        $auth = null;
        if (strpos($uriStr, 'presensi-siswa') !== false) {
            $auth = $this->checkAuthorization();
            if (!$auth['authorized']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => false,
                    'type'    => 'error',
                    'message' => $auth['message'],
                ]);
            }
            $hash = $this->requestHash();
            if (!self::isValidHash($hash, (int) $auth['user']->id)) {
                try {
                    log_message('warning', '[PresensiSiswaQr::scanQr] hash invalid ip={ip} user={u}', ['ip' => $this->request->getIPAddress(), 'u' => (int) $auth['user']->id]);
                } catch (\Throwable $e) {
                }
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => false,
                    'type'    => 'error',
                    'message' => 'Token keamanan (hash) tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.',
                ]);
            }
        } else {
            // Jalur publik (/scanner): token kiosk opsional bila admin mengaktifkannya.
            $kioskMsg = $this->checkKioskToken();
            if ($kioskMsg !== null) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => false, 'type' => 'error', 'message' => $kioskMsg,
                ]);
            }
        }

        $rawQr = trim((string) ($this->request->getPost('qr_value') ?? ''));
        // Scanner kadang menambahkan newline/spasi — ambil token pertama saja.
        $rawQr = preg_split('/\s+/', $rawQr, 2)[0] ?? '';
        $qrValue = mb_substr(trim($rawQr), 0, 50);
        $mode    = strtolower(trim((string) ($this->request->getPost('mode') ?? 'masuk')));
        $idKelas = (int) ($this->request->getPost('id_kelas') ?? 0);
        $tanggal = date('Y-m-d');

        // ── RFID ────────────────────────────────────────────────────────
        // RFID reader (USB keyboard wedge / Wiegand via ponte) mengirim
        // UID kartu sebagai rfid_value. Jika dikirim, gunakan sebagai
        // identifier utama dan abaikan qr_value.
        $rfidValue = trim((string) ($this->request->getPost('rfid_value') ?? ''));
        $rfidValue = preg_split('/\s+/', $rfidValue, 2)[0] ?? '';
        $useRfid   = $rfidValue !== '';

        if ($useRfid) {
            // Nomor yang sama berlaku untuk semua perangkat: kartu RFID
            // (nomor_rfid), QR cetak, dan tembakan USB scanner diresolusi
            // dengan urutan lookup yang identik — nomor_rfid → NIS → NISN → id.
            $siswa = $this->findSiswaByRfid($rfidValue) ?? $this->findSiswaByQr($rfidValue);
            if ($siswa) {
                $metode = (!empty($siswa->nomor_rfid) && strcasecmp(trim((string) $siswa->nomor_rfid), $rfidValue) === 0)
                    ? 'rfid' : 'qr';
                // Validasi geofence & jam operasional sebelum proses siswa.
                $lat      = $this->request->getPost('lat');
                $lng      = $this->request->getPost('lng');
                $accuracy = $this->request->getPost('accuracy');
                $geoCheck = $this->checkScanLokasi($lat, $lng, $accuracy);
                if (!$geoCheck['ok']) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $geoCheck['message']]);
                }
                $scanLoc = ['lat' => $geoCheck['lat'], 'lng' => $geoCheck['lng'], 'akurasi' => $geoCheck['accuracy'], 'lokasi_id' => $geoCheck['lokasi_id']];

                if ($liburMsg = $this->isHariLibur($tanggal)) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $liburMsg]);
                }
                if (($winMsg = $this->isOutsideScanWindow($mode)) !== null) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $winMsg]);
                }

                $idGuru = $this->resolveGuruId();
                $db     = \Config\Database::connect();
                $jam    = date('H:i:s');

                // Lookup satu baris per siswa per hari.
                $existing = $db->table('presensi_siswa')
                    ->where('id_siswa', $siswa->id_siswa)
                    ->where('tgl', $tanggal)
                    ->get()->getRow();
                if ($existing && (int) ($existing->id_kelas ?? 0) > 0) {
                    $idKelas = (int) $existing->id_kelas;
                }
                if ($idKelas <= 0) {
                    $idKelas = $this->getKelasAktifSiswa((int)$siswa->id_siswa);
                }
                if ($idKelas <= 0) {
                    return $this->response->setJSON([
                        'status'  => false,
                        'type'    => 'error',
                        'message' => "Siswa {$siswa->nama} tidak terdaftar di kelas aktif.",
                        'siswa'   => $this->formatSiswaInfo($siswa, null, 0),
                    ]);
                }

                if (!self::isSiswaAktif((int) $siswa->id_siswa)) {
                    return $this->response->setJSON([
                        'status'  => false,
                        'type'    => 'error',
                        'message' => "Siswa {$siswa->nama} sudah tidak aktif (lulus/pindah/keluar) sehingga tidak dapat diabsen.",
                        'siswa'   => $this->formatSiswaInfo($siswa, null, $idKelas),
                    ]);
                }

                if ($mode === 'masuk') {
                    if (($jadwalMsg = $this->isOutsideJadwalMasukSiswa($tanggal, $jam)) !== null) {
                        return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $jadwalMsg]);
                    }
                    return $this->processCheckIn($db, $siswa, $existing, $idKelas, $idGuru, $tanggal, $jam, $scanLoc, $metode);
                }
                if (($pulangMsg = $this->isTooEarlyPulangSiswa($tanggal, $jam)) !== null) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $pulangMsg]);
                }
                return $this->processCheckOut($db, $siswa, $existing, $idKelas, $idGuru, $tanggal, $jam, $scanLoc, $metode);
            }

            // Fallback: cek guru/GTK dengan nomor yang sama (nomor_rfid → NIP → kode_guru → username).
            $guru = $this->findGuruByRfid($rfidValue) ?? $this->findGuruByQr($rfidValue);
            if ($guru) {
                $guruMetode = (!empty($guru->nomor_rfid) && strcasecmp(trim((string) $guru->nomor_rfid), $rfidValue) === 0)
                    ? 'rfid' : 'qr';
                return $this->processGuruScan($guru, $mode, $tanggal, [
                    'lat' => $this->request->getPost('lat'),
                    'lng' => $this->request->getPost('lng'),
                    'akurasi' => $this->request->getPost('accuracy'),
                    'lokasi_id' => null,
                ], $guruMetode);
            }

            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => 'Nomor kartu tidak terdaftar. Tempel kartu RFID, tembak QR, atau ketik NIS yang sudah didaftarkan admin.',
            ]);
        }

        // ── Fingerprint (GEISA GX330 via HTTP Push) ───────────────────────
        // Mesin sidik jari mengirim UID/UserID sebagai fingerprint_uid
        // (atau user_id). Jika dikirim, gunakan sebagai identifier utama.
        $fpUid = trim((string) ($this->request->getPost('fingerprint_uid') ?? $this->request->getPost('user_id') ?? ''));
        $fpUid = preg_split('/\s+/', $fpUid, 2)[0] ?? '';
        $useFp = $fpUid !== '';

        if ($useFp) {
            // Cari siswa berdasarkan fingerprint_uid terlebih dahulu.
            $siswa = $this->findSiswaByFingerprint($fpUid);
            if ($siswa) {
                // Validasi geofence & jam operasional sebelum proses siswa.
                $lat      = $this->request->getPost('lat');
                $lng      = $this->request->getPost('lng');
                $accuracy = $this->request->getPost('accuracy');
                $geoCheck = $this->checkScanLokasi($lat, $lng, $accuracy);
                if (!$geoCheck['ok']) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $geoCheck['message']]);
                }
                $scanLoc = ['lat' => $geoCheck['lat'], 'lng' => $geoCheck['lng'], 'akurasi' => $geoCheck['accuracy'], 'lokasi_id' => $geoCheck['lokasi_id']];

                if ($liburMsg = $this->isHariLibur($tanggal)) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $liburMsg]);
                }
                if (($winMsg = $this->isOutsideScanWindow($mode)) !== null) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $winMsg]);
                }

                $idGuru = $this->resolveGuruId();
                $db     = \Config\Database::connect();
                $jam    = date('H:i:s');

                // Lookup satu baris per siswa per hari.
                $existing = $db->table('presensi_siswa')
                    ->where('id_siswa', $siswa->id_siswa)
                    ->where('tgl', $tanggal)
                    ->get()->getRow();
                if ($existing && (int) ($existing->id_kelas ?? 0) > 0) {
                    $idKelas = (int) $existing->id_kelas;
                }
                if ($idKelas <= 0) {
                    $idKelas = $this->getKelasAktifSiswa((int)$siswa->id_siswa);
                }
                if ($idKelas <= 0) {
                    return $this->response->setJSON([
                        'status'  => false,
                        'type'    => 'error',
                        'message' => "Siswa {$siswa->nama} tidak terdaftar di kelas aktif.",
                        'siswa'   => $this->formatSiswaInfo($siswa, null, 0),
                    ]);
                }

                if (!self::isSiswaAktif((int) $siswa->id_siswa)) {
                    return $this->response->setJSON([
                        'status'  => false,
                        'type'    => 'error',
                        'message' => "Siswa {$siswa->nama} sudah tidak aktif (lulus/pindah/keluar) sehingga tidak dapat diabsen.",
                        'siswa'   => $this->formatSiswaInfo($siswa, null, $idKelas),
                    ]);
                }

                if ($mode === 'masuk') {
                    if (($jadwalMsg = $this->isOutsideJadwalMasukSiswa($tanggal, $jam)) !== null) {
                        return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $jadwalMsg]);
                    }
                    return $this->processCheckIn($db, $siswa, $existing, $idKelas, $idGuru, $tanggal, $jam, $scanLoc);
                }
                if (($pulangMsg = $this->isTooEarlyPulangSiswa($tanggal, $jam)) !== null) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $pulangMsg]);
                }
                return $this->processCheckOut($db, $siswa, $existing, $idKelas, $idGuru, $tanggal, $jam, $scanLoc);
            }

            // Fallback: cek guru/GTK berdasarkan fingerprint_uid.
            $guru = $this->findGuruByFingerprint($fpUid);
            if ($guru) {
                return $this->processGuruScan($guru, $mode, $tanggal, [
                    'lat' => $this->request->getPost('lat'),
                    'lng' => $this->request->getPost('lng'),
                    'akurasi' => $this->request->getPost('accuracy'),
                    'lokasi_id' => null,
                ]);
            }

            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => 'Sidik jari tidak terdaftar. Hubungi admin untuk pendaftaran UserID mesin.',
            ]);
        }

        if ($qrValue === '') {
            return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => 'QR Code, NIS, atau kartu RFID kosong.']);
        }
        if (!in_array($mode, ['masuk', 'keluar'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false, 'type' => 'error',
                'message' => 'Mode tidak valid. Gunakan mode masuk atau keluar.',
            ]);
        }

        // ── GEOFENCE: koordinat perangkat pemindai (kiosk) wajib di area sekolah ──
        $lat      = $this->request->getPost('lat');
        $lng      = $this->request->getPost('lng');
        $accuracy = $this->request->getPost('accuracy');
        $geoCheck = $this->checkScanLokasi($lat, $lng, $accuracy);
        if (!$geoCheck['ok']) {
            return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $geoCheck['message']]);
        }
        $scanLat  = $geoCheck['lat'];
        $scanLng  = $geoCheck['lng'];
        $scanAcc  = $geoCheck['accuracy'];
        $lokasiId = $geoCheck['lokasi_id'];

        // Tolak scan pada hari libur (konsisten dengan check-in manual)
        if ($liburMsg = $this->isHariLibur($tanggal)) {
            return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $liburMsg]);
        }

        // ── JAM OPERASIONAL: scan di luar jendela operasional qr_scan_* ditolak,
        // untuk kedua arah (masuk maupun pulang). Batasan jadwal (masuk & pulang)
        // mengikuti absen wajah dan dicek setelah siswa teridentifikasi (lihat
        // sebelum processCheckIn/CheckOut).
        if (($winMsg = $this->isOutsideScanWindow($mode)) !== null) {
            return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $winMsg]);
        }

        $scanLoc = ['lat' => $scanLat, 'lng' => $scanLng, 'akurasi' => $scanAcc, 'lokasi_id' => $lokasiId];

        // Wali kelas tidak boleh mencatat presensi guru/GTK via endpoint siswa.
        $isWaliScanner = ($auth && ($auth['reason'] ?? '') === 'wali_kelas');

        // 1. Cek prefix eksplisit guru (misal GURU:xxx atau GTK:xxx)
        $isExplicitGuru = (stripos($qrValue, 'GURU:') === 0 || stripos($qrValue, 'GTK:') === 0);
        if ($isExplicitGuru) {
            if ($isWaliScanner) {
                try {
                    log_message('warning', '[PresensiSiswaQr] wali scan guru ditolak ip={ip} qr={qr}', ['ip' => $this->request->getIPAddress(), 'qr' => self::maskQrForLog($qrValue)]);
                } catch (\Throwable $e) {
                }
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => false, 'type' => 'error',
                    'message' => 'Wali kelas hanya dapat mencatat siswa kelas perwaliannya.',
                ]);
            }
            $guru = $this->findGuruByQr($qrValue);
            if ($guru) {
                $guruSource = (!empty($guru->nomor_rfid) && $guru->nomor_rfid === $qrValue) ? 'rfid' : 'qr';
                return $this->processGuruScan($guru, $mode, $tanggal, $scanLoc, $guruSource);
            }
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => 'Guru tidak ditemukan. Pastikan QR Card Guru valid.',
            ]);
        }

        // 2. Cek siswa terlebih dahulu
        $siswa = $this->findSiswaByQr($qrValue);
        if (!$siswa) {
            // 3. Fallback: Cek apakah QR ini milik Guru/GTK (NIP, kode_guru, username)
            $guru = $this->findGuruByQr($qrValue);
            if ($guru) {
                if ($isWaliScanner) {
                    try {
                        log_message('warning', '[PresensiSiswaQr] wali scan guru ditolak ip={ip} qr={qr}', ['ip' => $this->request->getIPAddress(), 'qr' => self::maskQrForLog($qrValue)]);
                    } catch (\Throwable $e) {
                    }
                    return $this->response->setStatusCode(403)->setJSON([
                        'status' => false, 'type' => 'error',
                        'message' => 'Wali kelas hanya dapat mencatat siswa kelas perwaliannya.',
                    ]);
                }
                $guruSource = (!empty($guru->nomor_rfid) && $guru->nomor_rfid === $qrValue) ? 'rfid' : 'qr';
                return $this->processGuruScan($guru, $mode, $tanggal, $scanLoc, $guruSource);
            }

            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => 'Data tidak ditemukan. Pastikan QR Card Siswa atau Guru valid.',
            ]);
        }

        // Hanya siswa AKTIF (buku_induk.status = '1') boleh diabsen.
        // LULUS (2) / PINDAH (3) / KELUAR (4) ditolak di semua jalur scan.
        if (!self::isSiswaAktif((int) $siswa->id_siswa)) {
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => "Siswa {$siswa->nama} sudah tidak aktif (lulus/pindah/keluar) sehingga tidak dapat diabsen.",
                'siswa'   => $this->formatSiswaInfo($siswa, null, $idKelas),
            ]);
        }

        // Jika id_kelas tidak dikirim, coba ambil dari data kelas siswa
        if ($idKelas <= 0) {
            $idKelas = $this->getKelasAktifSiswa((int)$siswa->id_siswa);
        }
        if ($idKelas <= 0) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => "Siswa {$siswa->nama} tidak terdaftar di kelas aktif.",
                'siswa'   => $this->formatSiswaInfo($siswa, null, 0),
            ]);
        }

        // Wali kelas hanya boleh scan siswa dari kelas perwaliannya
        if ($auth && ($auth['reason'] ?? '') === 'wali_kelas') {
            $allowedKelas = array_values(array_unique(array_map('intval', (array) ($auth['wali_kelas'] ?? []))));
            if (!in_array($idKelas, $allowedKelas, true)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => false,
                    'type'    => 'error',
                    'message' => "Siswa {$siswa->nama} bukan siswa kelas perwalian Anda. Anda hanya dapat mencatat kelas perwalian.",
                    'siswa'   => $this->formatSiswaInfo($siswa, null, $idKelas),
                ]);
            }
        }

        // Resolve id_guru dari sesi (bisa NULL jika diakses publik)
        $idGuru = $this->resolveGuruId();

        $db  = \Config\Database::connect();
        $jam = date('H:i:s');

        try {
            // Lookup satu baris per siswa per hari (abaikan id_kelas dari client
            // agar manipulasi id_kelas / pindah kelas tidak menciptakan duplikat).
            $existing = $db->table('presensi_siswa')
                ->where('id_siswa', $siswa->id_siswa)
                ->where('tgl', $tanggal)
                ->get()->getRow();
            if ($existing && (int) ($existing->id_kelas ?? 0) > 0) {
                // Kunci ke kelas yang sudah tercatat; jangan percaya id_kelas POST.
                $idKelas = (int) $existing->id_kelas;
            }

            // Deteksi sumber pemindaian (apakah cocok nomor_rfid atau QR biasa)
            $source = (!empty($siswa->nomor_rfid) && strcasecmp(trim((string) $siswa->nomor_rfid), $qrValue) === 0) ? 'rfid' : 'qr';

            if ($mode === 'masuk') {
                // Batasan jadwal masuk ala absen wajah (buka 1 jam sebelum
                // jam_masuk, tutup 3 jam setelah jam_masuk hari ini).
                if (($jadwalMsg = $this->isOutsideJadwalMasukSiswa($tanggal, $jam)) !== null) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $jadwalMsg]);
                }
                return $this->processCheckIn($db, $siswa, $existing, $idKelas, $idGuru, $tanggal, $jam, $scanLoc, $source);
            } else {
                // Batasan jadwal pulang: tolak scan keluar sebelum pulang
                // dibuka (jendela masuk ditutup atau jam_pulang tercapai).
                if (($pulangMsg = $this->isTooEarlyPulangSiswa($tanggal, $jam)) !== null) {
                    return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => $pulangMsg]);
                }
                return $this->processCheckOut($db, $siswa, $existing, $idKelas, $idGuru, $tanggal, $jam, $scanLoc, $source);
            }

        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswaQr::scanQr] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'type' => 'error', 'message' => 'Terjadi kesalahan sistem. Coba scan ulang.']);
        }
    }

    /**
     * Validasi lokasi perangkat pemindai (kiosk) terhadap titik absensi sekolah.
     * Fail-closed: tanpa koordinat valid di dalam radius → scan ditolak.
     * @return array{ok:bool, message?:string, lat?:float, lng?:float, accuracy?:int, lokasi_id?:int}
     */
    private function checkScanLokasi($lat, $lng, $accuracy): array
    {
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return ['ok' => false, 'message' => 'Lokasi kiosk belum terkunci. Aktifkan GPS & izinkan akses lokasi di browser, lalu scan ulang.'];
        }

        $lat = (float) $lat;
        $lng = (float) $lng;
        $acc = is_numeric($accuracy) ? (int) $accuracy : 9999;

        if (($lat === 0.0 && $lng === 0.0) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return ['ok' => false, 'message' => 'Koordinat GPS tidak valid. Pindah ke area terbuka lalu scan ulang.'];
        }

        // Sinyal mock: hanya akurasi 0 yang pasti palsu. Nilai 1/5 dan
        // koordinat bulat (.0000) sering muncul dari GPS asli presisi
        // tinggi, jadi jangan ditolak agar tidak false-positive.
        if ($acc === 0) {
            return ['ok' => false, 'message' => 'Terdeteksi lokasi palsu (mock location). Matikan Fake GPS lalu scan ulang.'];
        }
        if ($acc > 1000) {
            return ['ok' => false, 'message' => 'Akurasi GPS terlalu rendah (±' . $acc . 'm). Nyalakan GPS presisi tinggi lalu scan ulang.'];
        }

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('absensi_lokasi')) {
                return ['ok' => false, 'message' => 'Titik lokasi sekolah belum dikonfigurasi admin.'];
            }
            $rows = $db->table('absensi_lokasi')->get()->getResult();
            if (empty($rows)) {
                return ['ok' => false, 'message' => 'Titik lokasi sekolah belum dikonfigurasi admin.'];
            }

            $setting = $this->dashModel->getSetting();
            $defaultRadius = (float) ($setting->radius_meter ?? 100);
            if ($defaultRadius <= 0) $defaultRadius = 100;

            $best = null;
            foreach ($rows as $loc) {
                $radius = (float) (!empty($loc->radius_meter) ? $loc->radius_meter : $defaultRadius);
                if ($radius <= 0) continue;
                $dist = distance_meter($lat, $lng, (float) $loc->lat, (float) $loc->lng);
                if ($dist <= $radius && ($best === null || $dist < $best['jarak'])) {
                    $best = ['lokasi_id' => (int) $loc->id, 'jarak' => $dist];
                }
            }

            if ($best === null) {
                return ['ok' => false, 'message' => 'Di luar area sekolah. Scan QR hanya bisa dilakukan di lokasi yang sudah ditentukan.'];
            }

            return ['ok' => true, 'lat' => $lat, 'lng' => $lng, 'accuracy' => $acc, 'lokasi_id' => $best['lokasi_id']];
        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswaQr::checkScanLokasi] ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Gagal memverifikasi lokasi. Coba scan ulang.'];
        }
    }

    /**
     * Deteksi error duplikat key lintas driver (MySQL 1062, SQLite, Postgres).
     */
    private static function isDuplicateKeyError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage() . ' ' . $e->getCode());
        return str_contains($msg, 'duplicate')
            || str_contains($msg, 'uq_ps')
            || str_contains($msg, 'unique constraint')
            || str_contains($msg, '1062');
    }

    /**
     * Cek jendela jam operasional scanner QR (absensi_setting).
     * Berlaku untuk kedua mode: dulu hanya scan masuk yang dijaga, sehingga
     * scanner yang ditinggal menyala bisa mencatat "pulang" pukul 23:00 atau
     * bahkan 05:00 keesokan harinya.
     * @return string|null pesan penolakan bila di luar jam, null bila boleh
     */
    private function isOutsideScanWindow(string $mode = 'masuk', ?string $jamSekarang = null): ?string
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('absensi_setting')) return null;
            $row = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
            if (!$row) return null;
            if ((int) ($row->qr_scan_aktif ?? 0) !== 1) return null;

            $mulai   = substr((string) ($row->qr_scan_mulai ?? '05:30:00'), 0, 5);
            $selesai = substr((string) ($row->qr_scan_selesai ?? '18:00:00'), 0, 5);
            if ($mulai === '' || $selesai === '' || $mulai >= $selesai) return null;

            // $jamSekarang dipakai getModeTabState supaya status tab dan
            // penolakan scan memakai angka yang sama.
            $now = substr($jamSekarang ?? date('H:i:s'), 0, 5);
            if ($now < $mulai || $now > $selesai) {
                $arah = $mode === 'keluar'
                    ? 'Scan pulang ditutup pukul ' . $selesai . '.'
                    : 'Scan masuk dibuka kembali pukul ' . $mulai . '.';
                return 'Di luar jam operasional scan (' . $mulai . '–' . $selesai . '). ' . $arah;
            }
        } catch (\Throwable $e) {
            // Gagal baca setting → jangan blokir
        }
        return null;
    }

    /**
     * Jadwal efektif siswa pada satu tanggal: absensi_jadwal_kerja (per hari)
     * → absensi_setting. Mendelegasikan ke AttendancePolicy agar ambang telat,
     * jendela scan, poin, dan rekap memakai angka yang sama persis.
     * @return array{jam_masuk: string, jam_pulang: string, grace_menit: int, sumber: string}
     */
    private function getJadwalEfektifSiswa(string $tanggal): array
    {
        return \App\Services\AttendancePolicy::jadwalSiswa($tanggal);
    }

    /**
     * Batasan scan MASUK siswa ala absen wajah guru (Attendance::checkIn):
     * dibuka 1 jam sebelum jam_masuk, ditutup 3 jam setelah jam_masuk.
     * @return string|null pesan penolakan bila di luar jendela, null bila boleh
     */
    private function isOutsideJadwalMasukSiswa(string $tanggal, ?string $jamSekarang = null): ?string
    {
        try {
            $jadwal      = $this->getJadwalEfektifSiswa($tanggal);
            $jamMasuk    = $jadwal['jam_masuk'];
            $bukaMasuk   = date('H:i:s', strtotime($jamMasuk) - 3600);
            $tutupMasuk  = date('H:i:s', strtotime($jamMasuk) + (3 * 3600));
            $now         = $jamSekarang ?? date('H:i:s');
            // Normalisasi ke H:i:s bila pemanggil mengirim H:i
            if (strlen($now) === 5) $now .= ':00';
            if ($now < $bukaMasuk) {
                return 'Absen Masuk belum dibuka. Jadwal: ' . substr($jamMasuk, 0, 5)
                    . ' (Dibuka ' . substr($bukaMasuk, 0, 5) . ')';
            }
            if ($now > $tutupMasuk) {
                return 'Waktu Absen Masuk habis. Batas: ' . substr($tutupMasuk, 0, 5);
            }
        } catch (\Throwable $e) {
            // Gagal hitung → jangan blokir
        }
        return null;
    }

    /**
     * Jam buka efektif scan PULANG siswa: langsung dibuka begitu jendela
     * absen masuk ditutup (tutup_masuk), atau saat jam_pulang — mana yang
     * lebih dulu. Normalnya tutup_masuk (mis. 10:15) lebih awal dari
     * jam_pulang (mis. 14:00), sehingga tab/scan pulang aktif sejak
     * jendela masuk ditutup.
     * @return string H:i:s
     */
    private function getBukaPulangEfektifSiswa(string $tanggal): string
    {
        $jadwal = $this->getJadwalEfektifSiswa($tanggal);
        $tutup  = date('H:i:s', strtotime($jadwal['jam_masuk']) + (3 * 3600));
        $plg    = $jadwal['jam_pulang'];
        if (strlen($plg) === 5) $plg .= ':00';
        return min($tutup, $plg);
    }

    /**
     * Batasan scan PULANG/KELUAR siswa: ditolak bila belum sampai jam buka
     * efektif (tutup_masuk terlewati atau jam_pulang tercapai).
     * @return string|null pesan penolakan bila terlalu awal, null bila boleh
     */
    private function isTooEarlyPulangSiswa(string $tanggal, ?string $jamSekarang = null): ?string
    {
        try {
            $buka     = $this->getBukaPulangEfektifSiswa($tanggal);
            $now      = $jamSekarang ?? date('H:i:s');
            if (strlen($now) === 5) $now .= ':00';
            if ($now < $buka) {
                return 'Belum waktunya pulang. Pulang dibuka pukul ' . substr($buka, 0, 5) . '.';
            }
        } catch (\Throwable $e) {
            // Gagal hitung → jangan blokir
        }
        return null;
    }

    /**
     * Status tab mode scanner untuk view (menyesuaikan jadwal hari ini).
     * Masuk terbuka bila: dalam jendela operasional qr_scan_* DAN dalam
     * jendela jadwal [jam_masuk−1jam, jam_masuk+3jam] (ala absen wajah).
     * Pulang terbuka bila: jendela masuk sudah ditutup ATAU jam_pulang
     * tercapai (mana yang lebih dulu) — tab Pulang langsung aktif
     * begitu scan masuk ditutup — dan tertutup lagi oleh jendela
     * operasional qr_scan_* yang sama seperti tab Masuk.
     * @return array{jam_masuk:string,jam_pulang:string,buka_masuk:string,tutup_masuk:string,buka_pulang:string,masuk_open:bool,pulang_open:bool,masuk_msg:string,pulang_msg:string,now:string,default_mode:string,qr_aktif:bool,qr_mulai:string,qr_selesai:string}
     */
    private function getModeTabState(string $tanggal, ?string $nowHms = null): array
    {
        $now = $nowHms ?? date('H:i:s');
        if (strlen($now) === 5) $now .= ':00';
        $jadwal     = $this->getJadwalEfektifSiswa($tanggal);
        $jamMasuk   = $jadwal['jam_masuk'];
        $jamPulang  = $jadwal['jam_pulang'];
        $bukaMasuk  = date('H:i:s', strtotime($jamMasuk) - 3600);
        $tutupMasuk = date('H:i:s', strtotime($jamMasuk) + (3 * 3600));

        $masukOpen  = ($now >= $bukaMasuk && $now <= $tutupMasuk);
        $masukMsg   = '';
        if ($now < $bukaMasuk) {
            $masukMsg = 'Absen Masuk belum dibuka. Jadwal: ' . substr($jamMasuk, 0, 5)
                . ' (Dibuka ' . substr($bukaMasuk, 0, 5) . ')';
        } elseif ($now > $tutupMasuk) {
            $masukMsg = 'Waktu Absen Masuk habis. Batas: ' . substr($tutupMasuk, 0, 5);
        }
        // Lapisan jendela operasional qr_scan_* (khusus masuk) + datanya
        // untuk hitung ulang di browser (auto-switch tanpa reload).
        $qrAktif = false; $qrMulai = ''; $qrSelesai = '';
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('absensi_setting')) {
                $row = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
                if ($row && (int) ($row->qr_scan_aktif ?? 0) === 1) {
                    $qrAktif   = true;
                    $qrMulai   = substr((string) ($row->qr_scan_mulai ?? '05:30:00'), 0, 5);
                    $qrSelesai = substr((string) ($row->qr_scan_selesai ?? '18:00:00'), 0, 5);
                    if ($qrMulai === '' || $qrSelesai === '' || $qrMulai >= $qrSelesai) $qrAktif = false;
                }
            }
        } catch (\Throwable $e) {
        }
        if ($masukOpen && ($winMsg = $this->isOutsideScanWindow('masuk', $now)) !== null) {
            $masukOpen = false;
            $masukMsg  = $winMsg;
        }

        $bukaPulang  = $this->getBukaPulangEfektifSiswa($tanggal);
        $pulangOpen = ($now >= $bukaPulang);
        $pulangMsg  = $pulangOpen ? '' : 'Belum waktunya pulang. Pulang dibuka pukul ' . substr($bukaPulang, 0, 5) . '.';
        // Tab pulang dulu hanya punya batas bawah, jadi scanner yang ditinggal
        // menyala tetap membuka tab pulang sampai lewat tengah malam. Jendela
        // operasional yang sama dengan tab masuk sekarang ikut menutupnya.
        if ($pulangOpen && ($winMsgKeluar = $this->isOutsideScanWindow('keluar', $now)) !== null) {
            $pulangOpen = false;
            $pulangMsg  = $winMsgKeluar;
        }
        return [
            'jam_masuk'    => substr($jamMasuk, 0, 5),
            'jam_pulang'   => substr($jamPulang, 0, 5),
            'buka_masuk'   => substr($bukaMasuk, 0, 5),
            'tutup_masuk'  => substr($tutupMasuk, 0, 5),
            'buka_pulang'  => substr($bukaPulang, 0, 5),
            'masuk_open'   => $masukOpen,
            'pulang_open'  => $pulangOpen,
            'masuk_msg'    => $masukMsg,
            'pulang_msg'   => $pulangMsg,
            'now'          => substr($now, 0, 5),
            'default_mode' => (!$masukOpen && $pulangOpen) ? 'keluar' : 'masuk',
            'qr_aktif'     => $qrAktif,
            'qr_mulai'     => $qrMulai,
            'qr_selesai'   => $qrSelesai,
        ];
    }

    /**
     * Cek hari libur mingguan / nasional. Sumber tunggal: AttendancePolicy.
     *
     * Sebelumnya fungsi ini membaca `setting.hari_libur_mingguan` — kolom yang
     * tidak pernah ada di tabel `setting` — sehingga hasilnya selalu jatuh ke
     * default 'Sun' dan bisa berbeda dari halaman absensi manual.
     * @return string|null keterangan libur, atau null bila hari kerja
     */
    private function isHariLibur(string $tgl): ?string
    {
        try {
            return \App\Services\AttendancePolicy::alasanLibur($tgl);
        } catch (\Throwable $e) {
            // Biarkan null (tidak memblok)
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Riwayat scan hari ini
    // GET: ?tanggal=YYYY-MM-DD (opsional), ?id_kelas=X (opsional), ?limit=50
    // ─────────────────────────────────────────────────────────────────────────

    public function getHistory()
    {
        // Otorisasi & validasi hash jika dipanggil dari /guru/presensi-siswa atau /presensi-siswa
        $uriStr = uri_string();
        $auth = null;
        if (strpos($uriStr, 'presensi-siswa') !== false) {
            $auth = $this->checkAuthorization();
            if (!$auth['authorized']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => false,
                    'data'    => [],
                    'message' => $auth['message'],
                ]);
            }
            $hash = $this->requestHash();
            if (!self::isValidHash($hash, (int) $auth['user']->id)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => false,
                    'data'    => [],
                    'message' => 'Token keamanan (hash) tidak valid atau telah kedaluwarsa.',
                ]);
            }
        } else {
            // Jalur publik: throttle ringan + token kiosk bila diaktifkan.
            try {
                $th = \Config\Services::throttler();
                if ($th->check('qrhist_' . md5($this->request->getIPAddress()), 120, MINUTE) === false) {
                    return $this->response->setStatusCode(429)->setJSON(['status' => false, 'data' => [], 'message' => 'Terlalu banyak permintaan. Coba lagi.']);
                }
            } catch (\Throwable $e) {
            }
            $kioskMsg = $this->checkKioskToken();
            if ($kioskMsg !== null) {
                return $this->response->setStatusCode(403)->setJSON(['status' => false, 'data' => [], 'message' => $kioskMsg]);
            }
        }

        $tanggal = trim((string) ($this->request->getGet('tanggal') ?: date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || $tanggal > date('Y-m-d')) {
            $tanggal = date('Y-m-d');
        }
        // Riwayat selain hari ini memuat data pribadi siswa — wajib login
        if ($tanggal !== date('Y-m-d') && !$this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'data' => [], 'message' => 'Akses ditolak']);
        }
        $idKelas    = (int) ($this->request->getGet('id_kelas') ?? 0);
        $filterRole = trim((string) ($this->request->getGet('filter_role') ?? ''));
        $limit      = (int) ($this->request->getGet('limit') ?? 50);
        $limit      = max(1, min($limit, 200));
        $offset     = max(0, (int) ($this->request->getGet('offset') ?? 0));
        // Ambil satu baris ekstra untuk penanda has_more (pagination "muat lebih banyak")
        $fetch      = $offset + $limit + 1;

        // Wali kelas hanya boleh melihat riwayat kelas perwaliannya
        $isWali = $auth && ($auth['reason'] ?? '') === 'wali_kelas';
        $allowedKelas = $isWali ? array_values(array_unique(array_map('intval', (array) ($auth['wali_kelas'] ?? [])))) : [];
        if ($isWali) {
            if ($idKelas > 0 && !in_array($idKelas, $allowedKelas, true)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => false,
                    'data'    => [],
                    'message' => 'Anda hanya dapat melihat riwayat kelas perwalian Anda.',
                ]);
            }
            if ($idKelas <= 0 && count($allowedKelas) === 1) {
                $idKelas = $allowedKelas[0];
            }
        }

        try {
            $db = \Config\Database::connect();
            $rows = [];

            // 1. Ambil data Siswa (kecuali difilter khusus guru)
            if ($filterRole !== 'guru') {
                $builder = $db->table('presensi_siswa ps')
                    ->select('ps.id, ps.id_siswa, ps.id_kelas, ps.tgl, ps.jam_masuk, ps.jam_keluar,
                              ps.status, ps.keterangan, ps.updated_at,
                              ms.nama, ms.nis, ms.nisn, ms.foto,
                              mk.nama_kelas, \'siswa\' as role')
                    ->join('master_siswa ms', 'ms.id_siswa = ps.id_siswa')
                    ->join('master_kelas mk', 'mk.id_kelas = ps.id_kelas', 'left')
                    ->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                    ->groupStart()
                        ->where('bi.status', '1')
                        ->orWhere('bi.id_siswa IS NULL', null, false)
                    ->groupEnd()
                    ->where('ps.tgl', $tanggal);

                if ($idKelas > 0) {
                    $builder->where('ps.id_kelas', $idKelas);
                } elseif ($isWali && !empty($allowedKelas)) {
                    // Wali kelas multi-perwalian tanpa filter: batasi ke kelas perwaliannya
                    $builder->whereIn('ps.id_kelas', $allowedKelas);
                }

                $siswaRows = $builder->orderBy('ps.updated_at', 'DESC')->limit($fetch)->get()->getResult();

                foreach ($siswaRows as $r) {
                    $r->foto = $this->resolveAvatar((object)['foto' => $r->foto, 'nama' => $r->nama]);
                    $r->role = 'siswa';
                    if (!empty($r->jam_masuk)) $r->jam_masuk = substr((string)$r->jam_masuk, 0, 5);
                    if (!empty($r->jam_keluar)) $r->jam_keluar = substr((string)$r->jam_keluar, 0, 5);
                    $rows[] = $r;
                }
            }

            // 2. Ambil data Guru dari absensi_harian (bila tidak filter kelas spesifik dan tidak filter siswa saja)
            // Wali kelas tidak melihat data guru — hanya siswa kelas perwaliannya
            if (!$isWali && $idKelas <= 0 && $filterRole !== 'siswa' && $db->tableExists('absensi_harian') && $db->tableExists('master_guru')) {
                $gBuilder = $db->table('absensi_harian ah')
                    ->select('ah.id, ah.id_guru, ah.user_id, ah.tgl, ah.jam_masuk, ah.jam_pulang as jam_keluar,
                              ah.status_masuk as status, ah.keterangan, ah.updated_at,
                              mg.nama_guru as nama, mg.nip as nis, mg.kode_guru as nisn, mg.foto,
                              \'GURU / GTK\' as nama_kelas, \'guru\' as role')
                    ->join('master_guru mg', 'mg.id_user = ah.user_id OR mg.id_guru = ah.id_guru', 'left')
                    ->where('ah.tgl', $tanggal);

                $guruRows = $gBuilder->orderBy('ah.updated_at', 'DESC')->limit($fetch)->get()->getResult();

                foreach ($guruRows as $gr) {
                    $gr->foto = $this->resolveGuruAvatar((object)['foto' => $gr->foto, 'nama_guru' => $gr->nama]);
                    $gr->role = 'guru';
                    if (!empty($gr->jam_masuk)) {
                        $gr->jam_masuk = strlen((string)$gr->jam_masuk) >= 16
                            ? substr((string)$gr->jam_masuk, 11, 5)
                            : substr((string)$gr->jam_masuk, 0, 5);
                    }
                    if (!empty($gr->jam_keluar)) {
                        $gr->jam_keluar = strlen((string)$gr->jam_keluar) >= 16
                            ? substr((string)$gr->jam_keluar, 11, 5)
                            : substr((string)$gr->jam_keluar, 0, 5);
                    }
                    $rows[] = $gr;
                }
            }

            // Urutkan gabungan dari yang paling baru
            usort($rows, function ($a, $b) {
                return strcmp((string)($b->updated_at ?? ''), (string)($a->updated_at ?? ''));
            });
            $hasMore = count($rows) > ($offset + $limit);
            $rows = array_slice($rows, $offset, $limit);

            // Kiosk publik (/scanner tanpa login): samarkan NIS/NISN/NIP agar
            // endpoint hari-ini tak jadi oracle enumerasi identitas.
            if (!$this->isLoggedIn()) {
                foreach ($rows as $r) {
                    if (isset($r->nis)) $r->nis = '***';
                    if (isset($r->nisn)) $r->nisn = '***';
                }
            }

            // Summary stats (wali kelas multi-perwalian: agregasi per kelas perwalian)
            if ($isWali && $idKelas <= 0 && count($allowedKelas) > 1) {
                $stats = ['total' => 0, 'hadir' => 0, 'masuk' => 0, 'keluar' => 0, 'siswa_hadir' => 0, 'guru_hadir' => 0];
                foreach ($allowedKelas as $kId) {
                    $s = $this->getStatsHariIni($tanggal, $kId);
                    foreach ($stats as $key => $val) {
                        $stats[$key] = $val + (int) ($s[$key] ?? 0);
                    }
                }
            } else {
                $stats = $this->getStatsHariIni($tanggal, $idKelas ?: null);
            }

            return $this->response->setJSON([
                'status' => true,
                'data'   => $rows,
                'stats'  => $stats,
                'total'  => count($rows),
                'offset' => $offset,
                'has_more' => $hasMore,
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswaQr::getHistory] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'data' => [], 'message' => 'Gagal mengambil riwayat.']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** Cari siswa berdasarkan NIS, NISN, atau id_siswa yang di-encode di QR */
    private function findSiswaByQr(string $qrValue): ?object
    {
        try {
            $db = \Config\Database::connect();

            // Satu resolusi untuk semua jalur (QR kamera, USB scanner, RFID):
            // NIS → NISN → nomor_rfid → id_siswa. nomor_rfid ikut dibaca agar
            // pemanggil bisa menyimpulkan metode scan dari nomor yang cocok,
            // dan tidak dimasukkan bila migrasi RFID belum dijalankan.
            $cols = 'id_siswa, nama, nis, nisn, foto, jenis_kelamin'
                . ($db->fieldExists('nomor_rfid', 'master_siswa') ? ', nomor_rfid' : '');

            $row = $db->table('master_siswa')
                ->select($cols)
                ->where('nis', $qrValue)
                ->get()->getRow();

            if (!$row && !empty($qrValue)) {
                $row = $db->table('master_siswa')
                    ->select($cols)
                    ->where('nisn', $qrValue)
                    ->get()->getRow();
            }

            // Nomor kartu RFID diperiksa lebih dulu: nomor inilah yang dicetak
            // sebagai QR/barcode, dan UID numerik tidak boleh tertukar dengan
            // id_siswa yang kebetulan angkanya sama.
            if (!$row && $db->fieldExists('nomor_rfid', 'master_siswa') && !empty($qrValue)) {
                $row = $db->table('master_siswa')
                    ->select($cols)
                    ->where('nomor_rfid', $qrValue)
                    ->get()->getRow();
            }

            if (!$row && is_numeric($qrValue) && (int)$qrValue > 0) {
                $row = $db->table('master_siswa')
                    ->select($cols)
                    ->where('id_siswa', (int)$qrValue)
                    ->get()->getRow();
            }

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cari siswa berdasarkan nomor_rfid (UID kartu RFID).
     */
    private function findSiswaByRfid(string $rfidValue): ?object
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('master_siswa') || !$db->fieldExists('nomor_rfid', 'master_siswa')) {
                return null;
            }
            $row = $db->table('master_siswa')
                ->select('id_siswa, nama, nis, nisn, foto, jenis_kelamin, nomor_rfid')
                ->where('nomor_rfid', $rfidValue)
                ->get()->getRow();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Status AKTIF? (buku_induk.status = '1').
     * Tanpa baris buku_induk (data lama) dianggap AKTIF.
     */
    public static function isSiswaAktif(int $idSiswa): bool
    {
        if ($idSiswa <= 0) {
            return false;
        }
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('buku_induk')) {
                return true;
            }
            $row = $db->table('buku_induk')->select('status')
                ->where('id_siswa', $idSiswa)->get()->getRow();
            if (!$row) {
                return true;
            }
            return (string) ($row->status ?? '1') === '1';
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** Ambil id_kelas aktif milik siswa pada TP/SMT aktif */
    private function getKelasAktifSiswa(int $idSiswa): int
    {
        try {
            $tp  = $this->dashModel->getTahunActive();
            $smt = $this->dashModel->getSemesterActive();
            if (!$tp || !$smt) return 0;

            $db  = \Config\Database::connect();
            $row = $db->table('kelas_siswa')
                ->select('id_kelas')
                ->where('id_siswa', $idSiswa)
                ->where('id_tp', $tp->id_tp)
                ->where('id_smt', $smt->id_smt)
                ->get()->getRow();

            return $row ? (int)$row->id_kelas : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Ambil nama kelas berdasarkan id_kelas */
    private function getKelasName(int $idKelas): string
    {
        if ($idKelas <= 0) return '';
        try {
            $row = \Config\Database::connect()->table('master_kelas')
                ->select('nama_kelas')
                ->where('id_kelas', $idKelas)
                ->get()->getRow();
            return $row?->nama_kelas ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Resolve id_guru dari user yang sedang login */
    private function resolveGuruId(): ?int
    {
        try {
            $uid = $this->getUserId();
            if (!$uid) return null;
            $row = \Config\Database::connect()->table('master_guru')
                ->select('id_guru')
                ->where('id_user', $uid)
                ->get()->getRow();
            return $row ? (int)$row->id_guru : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Proses check-in */
    private function processCheckIn(
        $db, object $siswa, ?object $existing,
        int $idKelas, ?int $idGuru, string $tanggal, string $jam, array $scanLoc = [], string $source = 'qr'
    ) {
        $hasJamCol = $db->fieldExists('jam', 'presensi_siswa');
        $locCols = [];
        foreach (['lat_masuk' => $scanLoc['lat'] ?? null, 'lng_masuk' => $scanLoc['lng'] ?? null,
                  'akurasi_masuk' => $scanLoc['akurasi'] ?? null, 'lokasi_id' => $scanLoc['lokasi_id'] ?? null] as $col => $val) {
            if ($val !== null && $db->fieldExists($col, 'presensi_siswa')) $locCols[$col] = $val;
        }
        // Audit sumber (best-effort, kolom baru dari migrasi 20261006).
        try {
            $ip = (string) $this->request->getIPAddress();
            if ($db->fieldExists('sumber_masuk', 'presensi_siswa')) $locCols['sumber_masuk'] = $source;
            if ($ip !== '' && $db->fieldExists('ip_masuk', 'presensi_siswa')) $locCols['ip_masuk'] = substr($ip, 0, 45);
        } catch (\Throwable $e) {
        }

        if ($existing) {
            if (!empty($existing->jam_masuk)) {
                return $this->response->setJSON([
                    'status'   => false,
                    'type'     => 'already_in',
                    'message'  => "{$siswa->nama} sudah absen masuk pukul " . substr($existing->jam_masuk, 0, 5) . '.',
                    'siswa'    => $this->formatSiswaInfo($siswa, $existing, $idKelas),
                ]);
            }
            if (in_array($existing->status, ['SAKIT', 'IZIN'], true)) {
                return $this->response->setJSON([
                    'status'  => false,
                    'type'    => 'izin',
                    'message' => "{$siswa->nama} berstatus {$existing->status}.",
                    'siswa'   => $this->formatSiswaInfo($siswa, $existing, $idKelas),
                ]);
            }

            $updateData = [
                'jam_masuk'  => $jam,
                'status'     => 'HADIR',
                'id_guru'    => $idGuru ?? $existing->id_guru,
                'updated_at' => date('Y-m-d H:i:s'),
            ] + $locCols;
            if ($hasJamCol && empty($existing->jam)) {
                $updateData['jam'] = $jam;
            }
            // Guard idempoten: dua scan masuk paralel atas baris yang sama
            // tidak boleh sama-sama menulis jam_masuk lalu menjalankan sinkron
            // poin/sync telat ganda. Update hanya bila jam_masuk masih kosong.
            $db->table('presensi_siswa')
                ->where('id', $existing->id)
                ->where('jam_masuk IS NULL', null, false)
                ->update($updateData);
            if ($db->affectedRows() === 0) {
                $finalIn = $db->table('presensi_siswa')->select('jam_masuk')->where('id', $existing->id)->get()->getRow();
                return $this->response->setJSON([
                    'status'  => false,
                    'type'    => 'already_in',
                    'message' => "{$siswa->nama} sudah absen masuk pukul " . substr((string) ($finalIn->jam_masuk ?? $jam), 0, 5) . '.',
                    'siswa'   => $this->formatSiswaInfo($siswa, $existing, $idKelas),
                ]);
            }
            \App\Services\SiswaPoinService::sinkron((int) $existing->id);
            \App\Services\SiswaDendaService::sinkron((int) $existing->id);
            // Sinkron otomatis status TELAT ke arsip piket (best-effort):
            // hanya mengisi bila piket belum mencatat siswa ini.
            try {
                (new \App\Models\PiketModel())->syncTelatOtomatis($tanggal, $idKelas, [(int) $siswa->id_siswa]);
            } catch (\Throwable $e) {
                log_message('error', '[PresensiSiswaQr::processCheckIn] sync telat: ' . $e->getMessage());
            }
        } else {
            $insertData = [
                'id_kelas'   => $idKelas,
                'id_siswa'   => $siswa->id_siswa,
                'id_mapel'   => null,
                'id_guru'    => $idGuru,
                'tgl'        => $tanggal,
                'jam_masuk'  => $jam,
                'status'     => 'HADIR',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ] + $locCols;
            if ($hasJamCol) {
                $insertData['jam'] = $jam;
            }
            try {
                $db->table('presensi_siswa')->insert($insertData);
            } catch (\Throwable $e) {
                // Balapan scan ganda (unique key) → anggap sudah absen
                if (self::isDuplicateKeyError($e)) {
                    $existing = $db->table('presensi_siswa')
                        ->where('id_siswa', $siswa->id_siswa)
                        ->where('tgl', $tanggal)
                        ->get()->getRow();
                    return $this->response->setJSON([
                        'status'   => false,
                        'type'     => 'already_in',
                        'message'  => "{$siswa->nama} sudah absen masuk pukul " . substr((string) ($existing->jam_masuk ?? $jam), 0, 5) . '.',
                        'siswa'    => $this->formatSiswaInfo($siswa, $existing, $idKelas),
                    ]);
                }
                throw $e;
            }
            \App\Services\SiswaPoinService::sinkron((int) $db->insertID());
            \App\Services\SiswaDendaService::sinkron((int) $db->insertID());
            // Sinkron otomatis status TELAT ke arsip piket (best-effort):
            // hanya mengisi bila piket belum mencatat siswa ini.
            try {
                (new \App\Models\PiketModel())->syncTelatOtomatis($tanggal, $idKelas, [(int) $siswa->id_siswa]);
            } catch (\Throwable $e2) {
                log_message('error', '[PresensiSiswaQr::processCheckIn] sync telat: ' . $e2->getMessage());
            }
        }

        $existing = (object)array_merge((array)($existing ?? []), ['jam_masuk' => $jam, 'status' => 'HADIR', 'id_kelas' => $idKelas]);

        // Flag telat untuk umpan balik kiosk/mobile — tanpa ini UI selalu
        // menampilkan "Hadir Tepat Waktu" meski siswa scan setelah batas masuk.
        $telatMenit = 0;
        $batasMasukLabel = '';
        try {
            $hit = \App\Services\AttendancePolicy::telat(
                $tanggal,
                $jam,
                $this->getJadwalEfektifSiswa($tanggal)
            );
            $telatMenit     = $hit['telat_menit'];
            $batasMasukLabel = $hit['batas'];
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'status'      => true,
            'type'        => 'check_in',
            'metode'      => $source,
            'message'     => "{$siswa->nama} berhasil absen masuk pukul " . substr($jam, 0, 5) . '.'
                . ($telatMenit > 0 ? ' Terlambat ' . $telatMenit . ' menit (batas ' . $batasMasukLabel . ').' : ''),
            'jam_masuk'   => substr($jam, 0, 5),
            'telat'       => $telatMenit > 0,
            'telat_menit' => $telatMenit,
            'siswa'       => $this->formatSiswaInfo($siswa, $existing, $idKelas),
        ]);
    }

    /** Proses check-out */
    private function processCheckOut(
        $db, object $siswa, ?object $existing,
        int $idKelas, ?int $idGuru, string $tanggal, string $jam, array $scanLoc = [], string $source = 'qr'
    ) {
        if (!$existing || empty($existing->jam_masuk)) {
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'not_in',
                'message' => "{$siswa->nama} belum absen masuk hari ini.",
                'siswa'   => $this->formatSiswaInfo($siswa, null, $idKelas),
            ]);
        }
        if (!empty($existing->jam_keluar)) {
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'already_out',
                'message' => "{$siswa->nama} sudah absen keluar pukul " . substr($existing->jam_keluar, 0, 5) . '.',
                'siswa'   => $this->formatSiswaInfo($siswa, $existing, $idKelas),
            ]);
        }
        if ($jam < $existing->jam_masuk) {
            $jam = $existing->jam_masuk;
        }

        $locOut = [];
        foreach (['lat_keluar' => $scanLoc['lat'] ?? null, 'lng_keluar' => $scanLoc['lng'] ?? null] as $col => $val) {
            if ($val !== null && $db->fieldExists($col, 'presensi_siswa')) $locOut[$col] = $val;
        }
        try {
            $ipOut = (string) $this->request->getIPAddress();
            if ($db->fieldExists('sumber_keluar', 'presensi_siswa')) $locOut['sumber_keluar'] = $source;
            if ($ipOut !== '' && $db->fieldExists('ip_keluar', 'presensi_siswa')) $locOut['ip_keluar'] = substr($ipOut, 0, 45);
            // Jam keluar akhirnya tercatat → penanda cron tidak berlaku lagi.
            if ($db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) $locOut['pulang_tidak_tercatat'] = 0;
        } catch (\Throwable $e) {
        }
        // Guard idempoten: update hanya bila jam_keluar masih kosong sehingga
        // dua scan keluar paralel tidak saling menimpa (last-write-wins).
        $db->table('presensi_siswa')
            ->where('id', $existing->id)
            ->where('jam_keluar IS NULL', null, false)
            ->update([
                'jam_keluar' => $jam,
                // Pertahankan pencatat masuk; jangan timpa dengan pemindai keluar.
                'id_guru'    => !empty($existing->id_guru) ? $existing->id_guru : $idGuru,
                'updated_at' => date('Y-m-d H:i:s'),
            ] + $locOut);
        if ($db->affectedRows() === 0) {
            $finalOut = $db->table('presensi_siswa')->select('jam_keluar')->where('id', $existing->id)->get()->getRow();
            $shown = !empty($finalOut->jam_keluar) ? substr($finalOut->jam_keluar, 0, 5) : substr($jam, 0, 5);
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'already_out',
                'message' => "{$siswa->nama} sudah absen keluar pukul " . $shown . '.',
                'siswa'   => $this->formatSiswaInfo($siswa, $existing, $idKelas),
            ]);
        }
        try {
            \App\Services\SiswaPoinService::sinkron((int) $existing->id);
            \App\Services\SiswaDendaService::sinkron((int) $existing->id);
        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswaQr::processCheckOut] sinkron poin: ' . $e->getMessage());
        }

        $existing = (object)array_merge((array)$existing, ['jam_keluar' => $jam, 'id_kelas' => $idKelas]);
        return $this->response->setJSON([
            'status'     => true,
            'type'       => 'check_out',
            'metode'     => $source,
            'message'    => "{$siswa->nama} berhasil absen keluar pukul " . substr($jam, 0, 5) . '.',
            'jam_keluar' => substr($jam, 0, 5),
            'siswa'      => $this->formatSiswaInfo($siswa, $existing, $idKelas),
        ]);
    }

    /** Format info ringkas siswa untuk response JSON */
    private function formatSiswaInfo(object $siswa, ?object $presensi = null, int $idKelas = 0): array
    {
        $resolvedKelasId = $idKelas > 0 ? $idKelas : (int)($presensi->id_kelas ?? 0);
        $namaKelas       = $resolvedKelasId > 0 ? $this->getKelasName($resolvedKelasId) : '';
        if ($namaKelas === '') {
            $kId = $this->getKelasAktifSiswa((int)$siswa->id_siswa);
            if ($kId > 0) $namaKelas = $this->getKelasName($kId);
        }

        return [
            'id_siswa'   => (int)$siswa->id_siswa,
            'nama'       => (string)$siswa->nama,
            'nis'        => (string)($siswa->nis ?? ''),
            'nisn'       => (string)($siswa->nisn ?? ''),
            'nama_kelas' => $namaKelas,
            'foto'       => $this->resolveAvatar($siswa),
            'jam_masuk'  => $presensi ? substr((string)($presensi->jam_masuk ?? ''), 0, 5) : null,
            'jam_keluar' => $presensi ? substr((string)($presensi->jam_keluar ?? ''), 0, 5) : null,
            'status'     => $presensi ? ($presensi->status ?? null) : null,
        ];
    }

    /** Resolusi URL foto siswa */
    private function resolveAvatar(object $siswa): string
    {
        return \App\Services\AvatarService::untukSiswa($siswa, '#e11d48');
    }

    /** Stats ringkas scan hari ini */
    private function getStatsHariIni(string $tanggal, ?int $idKelas = null): array
    {
        try {
            $db = \Config\Database::connect();

            $builder = $db->table('presensi_siswa ps')
                ->select("COUNT(ps.id) as total,
                          SUM(CASE WHEN ps.status = 'HADIR' THEN 1 ELSE 0 END) as hadir,
                          SUM(CASE WHEN ps.jam_masuk IS NOT NULL AND ps.jam_masuk != '' THEN 1 ELSE 0 END) as masuk,
                          SUM(CASE WHEN ps.jam_keluar IS NOT NULL AND ps.jam_keluar != '' THEN 1 ELSE 0 END) as keluar", false)
                ->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                ->groupStart()
                    ->where('bi.status', '1')
                    ->orWhere('bi.id_siswa IS NULL', null, false)
                ->groupEnd()
                ->where('ps.tgl', $tanggal);

            if ($idKelas !== null && $idKelas > 0) {
                $builder->where('ps.id_kelas', $idKelas);
            }

            $row = $builder->get()->getRow();

            $totalSiswa  = (int)($row->total  ?? 0);
            $hadirSiswa  = (int)($row->hadir  ?? 0);
            $masukSiswa  = (int)($row->masuk   ?? 0);
            $keluarSiswa = (int)($row->keluar  ?? 0);

            $guruHadir  = 0;
            $guruMasuk  = 0;
            $guruKeluar = 0;

            if ($idKelas === null || $idKelas <= 0) {
                if ($db->tableExists('absensi_harian')) {
                    $gRow = $db->table('absensi_harian')
                        ->select("COUNT(id) as total,
                                  SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk != '' THEN 1 ELSE 0 END) as masuk,
                                  SUM(CASE WHEN jam_pulang IS NOT NULL AND jam_pulang != '' THEN 1 ELSE 0 END) as keluar", false)
                        ->where('tgl', $tanggal)
                        ->get()->getRow();
                    if ($gRow) {
                        // Hadir = sudah jam_masuk (bukan sekadar row ada).
                        $guruMasuk  = (int)($gRow->masuk ?? 0);
                        $guruHadir  = $guruMasuk > 0 ? $guruMasuk : (int)($gRow->total ?? 0);
                        $guruKeluar = (int)($gRow->keluar ?? 0);
                    }
                }
            }

            return [
                'total'       => $totalSiswa + $guruHadir,
                'hadir'       => $hadirSiswa + $guruHadir,
                'masuk'       => $masukSiswa + $guruMasuk,
                'keluar'      => $keluarSiswa + $guruKeluar,
                'siswa_hadir' => $hadirSiswa,
                'guru_hadir'  => $guruHadir,
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'hadir' => 0, 'masuk' => 0, 'keluar' => 0, 'siswa_hadir' => 0, 'guru_hadir' => 0];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DUKUNGAN PRESENSI GURU / GTK VIA QR SCANNER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cari guru berdasarkan NIP, kode_guru, username, nuptk, no_ktp, atau id_guru
     */
    private function findGuruByQr(string $qrValue): ?object
    {
        $clean = trim($qrValue);
        if (stripos($clean, 'GURU:') === 0 || stripos($clean, 'GTK:') === 0) {
            $clean = trim(substr($clean, strpos($clean, ':') + 1));
        }

        if ($clean === '') return null;

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('master_guru')) return null;

            $builder = $db->table('master_guru');

            // 1. NIP
            $row = (clone $builder)->where('nip', $clean)->get()->getRow();
            if ($row) return $row;

            // 2. Kode Guru (mis. GR001, KEPSEK)
            $row = (clone $builder)->where('kode_guru', $clean)->get()->getRow();
            if ($row) return $row;

            // 3. Username
            $row = (clone $builder)->where('username', $clean)->get()->getRow();
            if ($row) return $row;

            // 4. NUPTK
            if ($db->fieldExists('nuptk', 'master_guru')) {
                $row = (clone $builder)->where('nuptk', $clean)->get()->getRow();
                if ($row) return $row;
            }

            // 5. No KTP / NIK
            if ($db->fieldExists('no_ktp', 'master_guru')) {
                $row = (clone $builder)->where('no_ktp', $clean)->get()->getRow();
                if ($row) return $row;
            }

            // 6. Nomor RFID Guru — nomor kartu yang sama dengan QR cetak.
            // Dicek sebelum id agar UID numerik tidak tertukar dengan id_guru.
            if ($db->fieldExists('nomor_rfid', 'master_guru')) {
                $row = (clone $builder)->where('nomor_rfid', $clean)->get()->getRow();
                if ($row) return $row;
            }

            // 7. Angka: id_guru atau id_user
            if (is_numeric($clean) && (int)$clean > 0) {
                $row = (clone $builder)->where('id_guru', (int)$clean)->get()->getRow();
                if ($row) return $row;
                $row = (clone $builder)->where('id_user', (int)$clean)->get()->getRow();
                if ($row) return $row;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cari guru/GTK berdasarkan nomor_rfid (UID kartu RFID).
     */
    private function findGuruByRfid(string $rfidValue): ?object
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('master_guru') || !$db->fieldExists('nomor_rfid', 'master_guru')) {
                return null;
            }
            $row = $db->table('master_guru')
                ->select('id_guru, id_user, nama_guru, nip, kode_guru, username, foto, nomor_rfid')
                ->where('nomor_rfid', $rfidValue)
                ->get()->getRow();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cari siswa berdasarkan fingerprint_uid (UserID mesin sidik jari GEISA).
     */
    private function findSiswaByFingerprint(string $fingerprintUid): ?object
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('master_siswa') || !$db->fieldExists('fingerprint_uid', 'master_siswa')) {
                return null;
            }
            $row = $db->table('master_siswa')
                ->select('id_siswa, nama, nis, nisn, foto, jenis_kelamin, fingerprint_uid')
                ->where('fingerprint_uid', $fingerprintUid)
                ->get()->getRow();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cari guru/GTK berdasarkan fingerprint_uid (UserID mesin sidik jari GEISA).
     */
    private function findGuruByFingerprint(string $fingerprintUid): ?object
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('master_guru') || !$db->fieldExists('fingerprint_uid', 'master_guru')) {
                return null;
            }
            $row = $db->table('master_guru')
                ->select('id_guru, id_user, nama_guru, nip, kode_guru, username, foto, fingerprint_uid')
                ->where('fingerprint_uid', $fingerprintUid)
                ->get()->getRow();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Proses absensi harian Guru via QR Scan (Masuk & Keluar)
     */
    private function processGuruScan(object $guru, string $mode, string $tanggal, array $scanLoc = [], string $source = 'qr')
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_harian')) {
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => 'Tabel absensi guru belum siap di database.',
            ]);
        }

        // Resolve userId
        $userId = (int) ($guru->id_user ?? 0);
        if ($userId <= 0 && !empty($guru->username) && $db->tableExists('users')) {
            $uRow = $db->table('users')->where('username', $guru->username)->get()->getRow();
            if ($uRow) $userId = (int) $uRow->id;
        }
        if ($userId <= 0) {
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => "Akun login untuk {$guru->nama_guru} belum terhubung ke sistem pengguna.",
                'guru'    => $this->formatGuruInfo($guru, null),
            ]);
        }

        $jam = date('H:i:s');
        $recordedTime = date('Y-m-d H:i:s');

        try {
            $existing = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl', $tanggal)
                ->get()->getRow();

            if ($mode === 'masuk') {
                return $this->processGuruCheckIn($db, $guru, $existing, $userId, $tanggal, $jam, $recordedTime, $scanLoc, $source);
            } else {
                return $this->processGuruCheckOut($db, $guru, $existing, $userId, $tanggal, $jam, $recordedTime, $scanLoc, $source);
            }
        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswaQr::processGuruScan] ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'error',
                'message' => 'Terjadi kesalahan sistem saat memproses presensi guru — periksa log aplikasi.',
            ]);
        }
    }

    /**
     * Check-in harian guru via QR
     */
    private function processGuruCheckIn(
        $db, object $guru, ?object $existing, int $userId,
        string $tanggal, string $jam, string $recordedTime, array $scanLoc = [], string $source = 'qr'
    ) {
        if ($existing && !empty($existing->jam_masuk)) {
            $masukStr = strlen((string)$existing->jam_masuk) >= 16
                ? substr((string)$existing->jam_masuk, 11, 5)
                : substr((string)$existing->jam_masuk, 0, 5);

            return $this->response->setJSON([
                'status'    => false,
                'type'      => 'already_in',
                'role'      => 'guru',
                'message'   => "{$guru->nama_guru} sudah absen masuk pukul {$masukStr}.",
                'jam_masuk' => $masukStr,
                'guru'      => $this->formatGuruInfo($guru, $existing),
            ]);
        }

        // Resolusi jadwal kerja & shift
        $dayIso = (int) date('N', strtotime($tanggal));
        $jadwalHarian = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwalHarian = $db->table('absensi_jadwal_kerja')->where('hari', $dayIso)->get()->getRow();
        }

        $attSetting = null;
        if ($db->tableExists('absensi_setting')) {
            $attSetting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        }
        if (!$attSetting) {
            $attSetting = (object) [
                'jam_masuk'       => '07:30:00',
                'jam_pulang'      => '15:00:00',
                'grace_period'    => 15,
                'denda_tier_1'    => 100,
                'point_tier_1'    => 1,
                'max_denda_bulan' => 0,
            ];
        }

        $shiftModel    = new \App\Models\ShiftModel();
        $workSchedule  = $shiftModel->getScheduleForUser($userId, $dayIso, $jadwalHarian, $attSetting);
        $jamMasukLimit = $workSchedule['jam_masuk'];
        $graceMins     = (int) ($workSchedule['grace_menit'] ?? ($attSetting->grace_period ?? 15));

        // Status masuk via rumus baku yang sama dengan absen wajah guru, scan
        // siswa, poin, dan rekap (AttendancePolicy::telat).
        $hit = \App\Services\AttendancePolicy::telat($tanggal, $recordedTime, [
            'jam_masuk'   => $jamMasukLimit,
            'grace_menit' => $graceMins,
        ]);
        $statusMasuk     = $hit['status'];
        $telatMenit      = $hit['telat_menit'];
        $datangAwalMenit = $hit['datang_awal_menit'];

        $points          = 0;
        $denda           = 0.0;
        $keteranganDenda = null;

        $penaltyService = new \App\Services\AttendancePenaltyService();

        if ($datangAwalMenit > 0) {
            $points += $penaltyService->calculateReward($datangAwalMenit, $attSetting);
        }
        if ($statusMasuk === 'LATE') {
            $calc        = $penaltyService->calculatePenalty($telatMenit, $attSetting, 'Telat Masuk');
            $points      = -1 * $calc['points'];
            $denda       = (float) $calc['denda'];
            $keteranganDenda = $calc['keterangan'];
        }

        if ($denda > 0) {
            $capped = $penaltyService->applyMonthlyCap($userId, $tanggal, $denda, $attSetting);
            $denda  = (float) $capped['total'];
            if ($capped['capped']) {
                $capRp = number_format((float) ($attSetting->max_denda_bulan ?? 0), 0, ',', '.');
                $keteranganDenda = trim(($keteranganDenda ?? '') . " (dibatasi cap bulanan Rp{$capRp})");
            }
        }

        $fields = $db->getFieldNames('absensi_harian');
        $fieldSet = array_flip($fields);

        $rawRow = [
            'user_id'           => $userId,
            'id_guru'           => (int) $guru->id_guru,
            'tgl'               => $tanggal,
            'jam_masuk'         => $recordedTime,
            'lat_masuk'         => $scanLoc['lat'] ?? null,
            'lng_masuk'         => $scanLoc['lng'] ?? null,
            'lokasi_masuk_id'   => $scanLoc['lokasi_id'] ?? null,
            'jarak_masuk'       => 0,
            'foto_masuk'        => null,
            'status_masuk'      => $statusMasuk,
            'points'            => $points,
            'denda'             => $denda,
            'keterangan_denda'  => $keteranganDenda,
            'keterangan'        => 'Absen via QR Scanner Kiosk',
            'telat_menit'       => $telatMenit,
            'datang_awal_menit' => $datangAwalMenit,
            'ip_address'        => $this->request->getIPAddress(),
            'device_info'       => substr((string) ($this->request->getUserAgent() ?? 'Kiosk QR'), 0, 150),
            'gps_accuracy'      => !empty($scanLoc['akurasi']) ? $scanLoc['akurasi'] : null,
            'is_mock_location'  => 0,
            'is_wfh'            => 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        $insertData = array_intersect_key($rawRow, $fieldSet);

        $db->transBegin();
        try {
            if ($existing) {
                $db->table('absensi_harian')->where('id', $existing->id)->update($insertData);
                $insertedId = (int) $existing->id;
            } else {
                $db->table('absensi_harian')->insert($insertData);
                $insertedId = (int) $db->insertID();
            }
            $db->transCommit();

            try {
                \App\Services\GuruTerawalService::afterCheckIn($db, $tanggal, $userId);
            } catch (\Throwable $e) {
                log_message('error', 'Pengumuman guru terawal gagal: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            if (self::isDuplicateKeyError($e)) {
                $existing = $db->table('absensi_harian')
                    ->where('user_id', $userId)
                    ->where('tgl', $tanggal)
                    ->get()->getRow();
                $masukStr = strlen((string)($existing->jam_masuk ?? $jam)) >= 16
                    ? substr((string)($existing->jam_masuk ?? $jam), 11, 5)
                    : substr((string)($existing->jam_masuk ?? $jam), 0, 5);

                return $this->response->setJSON([
                    'status'    => false,
                    'type'      => 'already_in',
                    'role'      => 'guru',
                    'message'   => "{$guru->nama_guru} sudah absen masuk pukul {$masukStr}.",
                    'jam_masuk' => $masukStr,
                    'guru'      => $this->formatGuruInfo($guru, $existing),
                ]);
            }
            throw $e;
        }

        // Sinkronisasi buku besar poin
        if ($insertedId > 0 && class_exists('\App\Models\PoinHistoryModel')) {
            try {
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($insertedId);
            } catch (\Throwable $e) {
                log_message('error', '[PresensiSiswaQr] sync poin guru: ' . $e->getMessage());
            }
        }

        $resGuru  = (object) array_merge((array) ($existing ?? []), $insertData);
        $waktuStr = substr($jam, 0, 5);

        $statusKet = ($statusMasuk === 'LATE') ? " (Terlambat {$telatMenit} mnt)" : " (Tepat Waktu)";
        return $this->response->setJSON([
            'status'    => true,
            'type'      => 'check_in',
            'role'      => 'guru',
            'metode'    => $source,
            'message'   => "{$guru->nama_guru} berhasil absen masuk pukul {$waktuStr}{$statusKet}.",
            'jam_masuk' => $waktuStr,
            'guru'      => $this->formatGuruInfo($guru, $resGuru),
        ]);
    }

    /**
     * Check-out harian guru via QR
     */
    private function processGuruCheckOut(
        $db, object $guru, ?object $existing, int $userId,
        string $tanggal, string $jam, string $recordedTime, array $scanLoc = [], string $source = 'qr'
    ) {
        if (!$existing || empty($existing->jam_masuk)) {
            return $this->response->setJSON([
                'status'  => false,
                'type'    => 'not_in',
                'role'    => 'guru',
                'message' => "{$guru->nama_guru} belum absen masuk hari ini.",
                'guru'    => $this->formatGuruInfo($guru, null),
            ]);
        }

        if (!empty($existing->jam_pulang)) {
            $pulangStr = strlen((string)$existing->jam_pulang) >= 16
                ? substr((string)$existing->jam_pulang, 11, 5)
                : substr((string)$existing->jam_pulang, 0, 5);

            return $this->response->setJSON([
                'status'     => false,
                'type'       => 'already_out',
                'role'       => 'guru',
                'message'    => "{$guru->nama_guru} sudah absen keluar/pulang pukul {$pulangStr}.",
                'jam_keluar' => $pulangStr,
                'guru'       => $this->formatGuruInfo($guru, $existing),
            ]);
        }

        // --- WAJIB JURNAL SEBELUM PULANG ---
        // Gerbang yang sama dengan absen wajah dan QR di aplikasi guru. Tanpa
        // ini, kartu guru yang scan di kiosk bisa pulang tanpa satu jurnal pun.
        // Status dinas dikecualikan karena guru tidak mengajar di sekolah; WFH
        // tidak, sama seperti jalur guru. Hari libur tidak perlu diurus di sini
        // karena scanQr() sudah menolak seluruh scan lewat isHariLibur().
        $isDinasScan = \App\Services\WajibJurnalPulangService::isStatusDinas($existing->status_masuk ?? '');

        if (!$isDinasScan) {
            $gate = \App\Services\WajibJurnalPulangService::gate(
                (int) ($guru->id_guru ?? 0),
                $tanggal,
                \App\Services\AttendancePolicy::setting(),
                $db,
                $jam,
                (string) ($guru->nama_guru ?? '')
            );

            if ($gate !== null) {
                // message = pesan lengkap (daftar sesi dibutuhkan operator di
                // gerbang); layar scanner membacakan versi pendek lewat TTS.
                return $this->response->setJSON([
                    'status'           => false,
                    'type'             => 'jurnal_kurang',
                    'role'             => 'guru',
                    'message'          => $gate['message'],
                    'message_singkat'  => $gate['message_singkat'],
                    'data'             => $gate['data'],
                    'guru'             => $this->formatGuruInfo($guru, $existing),
                ]);
            }
        }
        // --- END WAJIB JURNAL ---

        // Resolusi jadwal kerja & shift untuk jam pulang
        $dayIso = (int) date('N', strtotime($tanggal));
        $jadwalHarian = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwalHarian = $db->table('absensi_jadwal_kerja')->where('hari', $dayIso)->get()->getRow();
        }

        $attSetting = null;
        if ($db->tableExists('absensi_setting')) {
            $attSetting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        }
        if (!$attSetting) {
            $attSetting = (object) [
                'jam_masuk'       => '07:30:00',
                'jam_pulang'      => '15:00:00',
                'grace_period'    => 15,
                'denda_tier_1'    => 100,
                'point_tier_1'    => 1,
            ];
        }

        $shiftModel     = new \App\Models\ShiftModel();
        $workSchedule   = $shiftModel->getScheduleForUser($userId, $dayIso, $jadwalHarian, $attSetting);
        $jamPulangLimit = $workSchedule['jam_pulang'];

        $threshold  = strtotime($tanggal . ' ' . $jamPulangLimit);
        $checkTime  = strtotime($recordedTime);

        $pulangCepatMenit = 0;
        $pulangAkhirMenit = 0;
        $penaltyService   = new \App\Services\AttendancePenaltyService();

        $curPoints = (int) ($existing->points ?? 0);
        $curDenda  = (float) ($existing->denda ?? 0.0);
        $curKet    = (string) ($existing->keterangan_denda ?? '');

        if ($checkTime >= $threshold) {
            $statusPulang = 'ONTIME';
            $diffVal      = $checkTime - $threshold;
            $diffMins     = (int) floor($diffVal / 60);
            if ($diffMins >= 60) {
                $pulangAkhirMenit = $diffMins - 60;
                $rewardOut        = $penaltyService->calculateReward($pulangAkhirMenit, $attSetting);
                $curPoints       += $rewardOut;
            }
        } else {
            $statusPulang     = 'EARLY';
            $diffVal          = $threshold - $checkTime;
            $pulangCepatMenit = (int) ceil($diffVal / 60);
            $calc             = $penaltyService->calculatePenalty($pulangCepatMenit, $attSetting, 'Pulang Awal');
            $curPoints       -= $calc['points'];
            $curDenda        += (float) $calc['denda'];
            $curKet           = trim($curKet . ' ' . $calc['keterangan']);
        }

        $fields = $db->getFieldNames('absensi_harian');
        $fieldSet = array_flip($fields);

        // Cap bulanan juga berlaku untuk denda pulang awal. Jalur check-in di
        // atas sudah membatasi; tanpa ini total sebulan bisa melewati
        // max_denda_bulan dan angka tagihan tidak cocok lagi dengan yang
        // diterima bendahara. excludeId = baris ini sendiri, karena $curDenda
        // sudah memuat denda check-in yang tersimpan di baris yang sama.
        if ($curDenda > 0) {
            $capped   = $penaltyService->applyMonthlyCap($userId, $tanggal, $curDenda, $attSetting, (int) $existing->id);
            $curDenda = (float) $capped['total'];
            if ($capped['capped']) {
                $capRp  = number_format((float) ($attSetting->max_denda_bulan ?? 0), 0, ',', '.');
                $curKet = trim($curKet . " (dibatasi cap bulanan Rp{$capRp})");
            }
        }

        $rawUpdate = [
            'jam_pulang'         => $recordedTime,
            'lat_pulang'         => $scanLoc['lat'] ?? null,
            'lng_pulang'         => $scanLoc['lng'] ?? null,
            'lokasi_pulang_id'   => $scanLoc['lokasi_id'] ?? null,
            'jarak_pulang'       => 0,
            'status_pulang'      => $statusPulang,
            'pulang_cepat_menit' => $pulangCepatMenit,
            'pulang_akhir_menit' => $pulangAkhirMenit,
            'points'             => $curPoints,
            'denda'              => $curDenda,
            'keterangan_denda'   => $curKet ?: null,
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        $updateData = array_intersect_key($rawUpdate, $fieldSet);

        $db->table('absensi_harian')
            ->where('id', $existing->id)
            ->where('jam_pulang IS NULL', null, false)
            ->update($updateData);

        // Guard 'jam_pulang IS NULL' berarti update bisa tidak mengenai apa pun
        // kalau scan paralel (dua perangkat, atau retry dari antrean offline)
        // sudah menulis pulang lebih dulu. Tanpa dicek, responsnya tetap
        // "berhasil" padahal denda/poin yang barusan dihitung tidak tersimpan.
        if ($db->affectedRows() === 0) {
            $setelah = $db->table('absensi_harian')->where('id', $existing->id)->get()->getRow();
            $pulangTersimpan = $setelah && !empty($setelah->jam_pulang)
                ? (strlen((string) $setelah->jam_pulang) >= 16
                    ? substr((string) $setelah->jam_pulang, 11, 5)
                    : substr((string) $setelah->jam_pulang, 0, 5))
                : null;

            return $this->response->setJSON([
                'status'     => false,
                'type'       => 'already_out',
                'role'       => 'guru',
                'message'    => $pulangTersimpan
                    ? "{$guru->nama_guru} sudah absen keluar pukul {$pulangTersimpan}."
                    : "Absen keluar {$guru->nama_guru} tidak tersimpan; silakan ulangi.",
                'jam_keluar' => $pulangTersimpan,
                'guru'       => $this->formatGuruInfo($guru, $setelah ?: $existing),
            ]);
        }

        // Sinkron poin
        if (class_exists('\App\Models\PoinHistoryModel')) {
            try {
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($existing->id);
            } catch (\Throwable $e) {}
        }

        $resGuru  = (object) array_merge((array) $existing, $updateData);
        $waktuStr = substr($jam, 0, 5);

        $statusKet = ($statusPulang === 'EARLY') ? " (Pulang lebih awal {$pulangCepatMenit} mnt)" : " (Tepat Waktu)";
        return $this->response->setJSON([
            'status'     => true,
            'type'       => 'check_out',
            'role'       => 'guru',
            'metode'     => $source,
            'message'    => "{$guru->nama_guru} berhasil absen keluar pukul {$waktuStr}{$statusKet}.",
            'jam_keluar' => $waktuStr,
            'guru'       => $this->formatGuruInfo($guru, $resGuru),
        ]);
    }

    /** Format info ringkas guru untuk response JSON */
    private function formatGuruInfo(object $guru, ?object $presensi = null): array
    {
        $jamMasuk = null;
        if (!empty($presensi->jam_masuk)) {
            $jamMasuk = strlen((string)$presensi->jam_masuk) >= 16
                ? substr((string)$presensi->jam_masuk, 11, 5)
                : substr((string)$presensi->jam_masuk, 0, 5);
        }
        $jamKeluar = null;
        if (!empty($presensi->jam_pulang)) {
            $jamKeluar = strlen((string)$presensi->jam_pulang) >= 16
                ? substr((string)$presensi->jam_pulang, 11, 5)
                : substr((string)$presensi->jam_pulang, 0, 5);
        }

        return [
            'id_guru'    => (int) $guru->id_guru,
            'nama'       => (string) $guru->nama_guru,
            'nip'        => (string) ($guru->nip ?? ''),
            'kode_guru'  => (string) ($guru->kode_guru ?? ''),
            'jabatan'    => 'GURU / GTK',
            'nama_kelas' => 'GURU / GTK',
            'role'       => 'guru',
            'foto'       => $this->resolveGuruAvatar($guru),
            'jam_masuk'  => $jamMasuk,
            'jam_keluar' => $jamKeluar,
            'status'     => $presensi ? ($presensi->status_masuk ?? 'HADIR') : null,
        ];
    }

    /** Resolusi URL foto guru */
    private function resolveGuruAvatar(object $guru): string
    {
        return \App\Services\AvatarService::tampilan(
            (string) ($guru->foto ?? ''),
            (string) ($guru->nama_guru ?? 'Guru'),
            '#7c3aed'
        );
    }
}

