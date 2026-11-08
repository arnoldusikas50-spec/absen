<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\AttendanceModel;
use App\Models\AttendanceSettingModel;
use App\Models\AttendanceLocationModel;
use App\Models\LeaveModel;
use App\Models\AuditLogModel;
use App\Models\PoinHistoryModel;
use Dompdf\Dompdf;
use App\Models\DashboardModel;



/**
 * Guru Mobile Attendance Controller
 */
class Attendance extends BaseController
{
    protected $attendanceModel;
    protected $settingModel;
    protected $leaveModel;

    private function isGuruKepalaSekolah(): bool
    {
        if (!$this->inGroup('guru')) {
            return false;
        }

        $userId = session()->get('user_id');
        if (!$userId) {
            return false;
        }

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        if (!$db) {
            return false;
        }

        try {
            if ($db->tableExists('users') && $db->fieldExists('tugas_tambahan', 'users')) {
                $row = $db->table('users')
                    ->select('tugas_tambahan')
                    ->where('id', $userId)
                    ->get()->getRow();
                if ($row && isset($row->tugas_tambahan) && strtolower(trim((string) $row->tugas_tambahan)) === 'kepala sekolah') {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            if ($db->tableExists('master_guru') && $db->fieldExists('tugas_tambahan', 'master_guru')) {
                $row = $db->table('master_guru')
                    ->select('tugas_tambahan')
                    ->where('id_user', $userId)
                    ->get()->getRow();
                if ($row && isset($row->tugas_tambahan) && strtolower(trim((string) $row->tugas_tambahan)) === 'kepala sekolah') {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            if ($db->tableExists('master_guru') && $db->tableExists('jabatan_guru') && $db->tableExists('level_guru')) {
                $row = $db->table('master_guru a')
                    ->select('lg.level as jabatan')
                    ->join('jabatan_guru jg', 'jg.id_guru = a.id_guru', 'left')
                    ->join('level_guru lg', 'lg.id_level = jg.id_jabatan', 'left')
                    ->where('a.id_user', $userId)
                    ->get()->getRow();
                $jabatan = strtolower(trim((string) ($row->jabatan ?? '')));
                if ($jabatan !== '' && (str_contains($jabatan, 'kepala sekolah') || str_contains($jabatan, 'kepsek'))) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $dashboardModel = new DashboardModel();
            $globalSetting = $dashboardModel->getSetting();
            $kepsekNama = strtolower(trim((string) ($globalSetting->kepsek ?? '')));
            if ($kepsekNama !== '' && $db->tableExists('master_guru')) {
                $guru = $db->table('master_guru')->select('nama_guru')->where('id_user', $userId)->get()->getRow();
                $namaGuru = strtolower(trim((string) ($guru->nama_guru ?? '')));
                if ($namaGuru !== '' && $namaGuru === $kepsekNama) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    private function ensureKepsekAccess()
    {
        if (!$this->inGroup('guru')) {
            // Endpoint kepsek ada yang JSON (AJAX): jangan balas redirect HTML.
            if ($this->request->isAJAX() || strpos((string) $this->request->getHeaderLine('Accept'), 'application/json') !== false) {
                return $this->respond(false, 'Akses ditolak', null, null, 403);
            }
            return redirect()->to('auth');
        }
        if (!$this->isGuruKepalaSekolah()) {
            if ($this->request->isAJAX() || strpos((string) $this->request->getHeaderLine('Accept'), 'application/json') !== false) {
                return $this->respond(false, 'Akses ditolak', null, null, 403);
            }
            return redirect()->to('presensi/notif')->with('error', 'Akses ditolak');
        }
        return null;
    }

    /**
     * Pengajuan ini milik approver yang sedang login? Approver tidak boleh
     * memproses permintaannya sendiri — keputusan itulah yang menulis baris
     * absensi atas namanya (IJIN/SAKIT/CUTI/DINAS, atau ALPHA + denda saat
     * ditolak), dan tidak ada orang lain yang meninjaunya.
     */
    private function adalahPemohon($row): bool
    {
        $approver = (int) (session()->get('user_id') ?? 0);

        return $approver > 0 && (int) ($row->user_id ?? 0) === $approver;
    }

    public function __construct()
    {
        helper(['attendance', 'filesystem', 'text']);
        $this->attendanceModel = new AttendanceModel();
        $this->settingModel = new AttendanceSettingModel();
        $this->leaveModel = new LeaveModel();
        $this->authModel = new \App\Models\AuthModel();
    }

    private function getDefaultSettings()
    {
        return (object) [
            'nama_sekolah' => 'Absensi App',
            'logo_kiri' => null,
            'foto_wajib' => 0,
            'jam_masuk' => '07:30:00',
            'jam_pulang' => '15:00:00',
            'hari_libur_mingguan' => 'Sun',
            'radius_meter' => 100,
            'denda_tier_1' => 100,
            'denda_tier_2' => 200,
            'denda_tier_3' => 500,
            'denda_alpha_per_hari' => 5000,
            'point_tier_1' => 1,
            'point_tier_2' => 2,
            'point_tier_3' => 5,
            'reward_cap_menit' => 30,
            'reward_point_tier_1' => 1,
            'reward_point_tier_2' => 1,
            'reward_point_tier_3' => 2,
            // Selaras dengan default kolom absensi_setting dan checkbox di
            // halaman Pengaturan, supaya UI dan penegakan tidak berbeda.
            'wajib_jurnal_sebelum_pulang' => 1,
            // Opt-in: jurnal harus divalidasi piket dulu sebelum boleh pulang.
            'wajib_jurnal_tervalidasi_sebelum_pulang' => 0
        ];
    }

    /**
     * Show attendance page (mobile)
     */
    public function loginPage()
    {
        if (session()->get('is_login')) {
            if ($this->isAdmin()) {
                return redirect()->to('admin/presensi');
            }
            return redirect()->to('presensi');
        }

        $setting = $this->settingModel->first();
        if (!$setting) {
            $setting = $this->getDefaultSettings();
        }
        return view('members/guru/absen_login', ['setting' => $setting]);
    }

    public function loginAuth()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) ($this->request->getPost('password') ?? '');

        if ($username === '' || $password === '') {
            return $this->respond(false, 'Username dan Password wajib diisi');
        }

        // Brute-force: batasi per IP+username seperti endpoint presensi lain.
        try {
            $throttler = \Config\Services::throttler();
            $throttleKey = 'applogin_' . md5($this->request->getIPAddress() . '|' . strtolower($username));
            if ($throttler->check($throttleKey, 5, MINUTE) === false) {
                return $this->respond(false, 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.', null, null, 429);
            }
        } catch (\Throwable $e) {
            // Throttler tidak tersedia → lanjut; verifikasi password tetap berlaku.
        }

        $user = $this->authModel->login($username);

        // Pesan disamakan agar tidak bisa dipakai menebak username terdaftar.
        if (!$user || (string) $user->active !== '1' || !password_verify($password, $user->password)) {
            return $this->respond(false, 'Username atau Password salah!');
        }

        // Check if user is Guru or Admin
        $db = \Config\Database::connect();
        $userGroup = $db->table('users_groups ug')
            ->select('g.name as group_name')
            ->join('groups g', 'g.id = ug.group_id')
            ->where('ug.user_id', $user->id)
            ->get()->getRow();

        $groupName = strtolower((string) ($userGroup->group_name ?? $user->role_name ?? ''));
        if (!in_array($groupName, ['guru', 'admin', 'panitia'], true)) {
            return $this->respond(false, 'Akses ditolak: Hanya akun Guru atau Admin yang diizinkan.');
        }

        // Identitas guru diambil dari relasi id_user. Pencarian berdasarkan
        // nama bisa menempelkan sesi ke guru yang salah.
        $guru = $db->table('master_guru')->where('id_user', $user->id)->get()->getRow();
        if (!$guru) {
            $guru = $db->table('master_guru')->groupStart()
                ->where('nip', $username)
                ->orWhere('kode_guru', $username)
                ->groupEnd()->get()->getRow();
        }

        // Cegah session fixation.
        $this->session->regenerate(true);

        $this->session->set([
            'user_id' => $user->id,
            'username' => $user->username,
            'nama' => $user->first_name . ($user->last_name ? ' ' . $user->last_name : ''),
            'email' => $user->email,
            'role' => $user->group_id,
            'role_name' => $groupName,
            'foto' => $user->foto,
            'is_login' => true,
            'login_variant' => 'staff',
            'id_guru' => $guru ? $guru->id_guru : 0
        ]);

        $redirectUrl = $groupName === 'guru' ? site_url('presensi') : site_url('dashboard');

        // PWA "tetap login" 30 hari — hanya guru (admin/panitia dikecualikan).
        if ($groupName === 'guru') {
            \App\Libraries\RememberLogin::issue((int) $user->id);
        }

        return $this->respond(true, 'Login berhasil', ['redirect' => $redirectUrl]);
    }

    public function logoutApp()
    {
        \App\Libraries\RememberLogin::clear();
        session()->destroy();
        return redirect()->to('/');
    }

    // ==========================================
    // GOOGLE AUTH INTEGRATION
    // ==========================================

    private function getGoogleClientId(): string
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('absensi_setting') && $db->fieldExists('google_client_id', 'absensi_setting')) {
            $row = $db->table('absensi_setting')->select('google_client_id')->orderBy('id', 'ASC')->limit(1)->get()->getRow();
            if (!empty($row->google_client_id)) {
                return trim($row->google_client_id);
            }
        }
        $env = getenv('GOOGLE_OAUTH_CLIENT_ID') ?: ($_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? ($_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? ''));
        return trim((string) $env);
    }

    private function getGoogleClientSecret(): string
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('absensi_setting') && $db->fieldExists('google_client_secret', 'absensi_setting')) {
            $row = $db->table('absensi_setting')->select('google_client_secret')->orderBy('id', 'ASC')->limit(1)->get()->getRow();
            if (!empty($row->google_client_secret)) {
                return trim($row->google_client_secret);
            }
        }
        $env = getenv('GOOGLE_OAUTH_CLIENT_SECRET') ?: ($_ENV['GOOGLE_OAUTH_CLIENT_SECRET'] ?? ($_SERVER['GOOGLE_OAUTH_CLIENT_SECRET'] ?? ''));
        return trim((string) $env);
    }

    public function google()
    {
        // 1. Check if configured
        $clientId = $this->getGoogleClientId();
        $clientSecret = $this->getGoogleClientSecret();

        $placeholders = ['YOUR_CLIENT_ID_HERE', 'YOUR_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID', 'YOUR_CLIENT_SECRET_HERE', 'YOUR_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET', ''];

        if (in_array($clientId, $placeholders) || in_array($clientSecret, $placeholders)) {
            return redirect()->to('auth')->with('error', 'Google Auth belum dikonfigurasi. Harap isi Client ID & Client Secret di Pengaturan Presensi atau file .env.');
        }

        // 2. Prepare Params
        $redirectUri = site_url('presensi/google-callback');

        // Generate Random State
        $state = bin2hex(random_bytes(16));
        session()->set('oauth2_state', $state);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account'
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        return redirect()->to($url);
    }

    public function googleCallback()
    {
        $code = $this->request->getGet('code');
        $state = $this->request->getGet('state');
        $error = $this->request->getGet('error');
        $savedState = session()->get('oauth2_state');

        if ($error) {
            return redirect()->to('auth')->with('error', 'Login Google dibatalkan: ' . esc($error));
        }

        if (!$code) {
            return redirect()->to('auth')->with('error', 'Gagal login dengan Google (Otorisasi tidak memberikan kode verifikasi).');
        }

        if (!$state || !$savedState || !hash_equals((string) $savedState, (string) $state)) {
            session()->remove('oauth2_state');
            return redirect()->to('auth')->with('error', 'Sesi login Google telah kedaluwarsa. Silakan klik tombol Google lagi.');
        }

        session()->remove('oauth2_state');

        $redirectUri = site_url('presensi/google-callback');

        // 2. Exchange Code for Token
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->getGoogleClientId(),
                    'client_secret' => $this->getGoogleClientSecret(),
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri
                ],
                'http_errors' => false
            ]);

            $tokenData = json_decode($response->getBody(), true);

            if (!isset($tokenData['access_token'])) {
                log_message('error', 'Google Auth Token Error: ' . json_encode($tokenData));
                $errMsg = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Gagal verifikasi token ke Google');
                return redirect()->to('auth')->with('error', 'Gagal verifikasi ke Google: ' . $errMsg);
            }

            // 3. Get User Info
            $userResponse = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenData['access_token']
                ],
                'http_errors' => false
            ]);

            $userData = json_decode($userResponse->getBody(), true);
            $email = trim((string) ($userData['email'] ?? ''));

            if (empty($email)) {
                return redirect()->to('auth')->with('error', 'Email tidak ditemukan dari profil akun Google Anda.');
            }

            // 4. Check DB for matching user
            $db = \Config\Database::connect();

            // 4a. Find by users.email
            $user = $db->table('users a')
                ->select('a.*, b.group_id, c.name as role_name')
                ->join('users_groups b', 'a.id = b.user_id', 'left')
                ->join('groups c', 'b.group_id = c.id', 'left')
                ->where('LOWER(a.email)', strtolower($email))
                ->get()->getRow();

            // 4b. If not found, check master_guru.email
            if (!$user) {
                $guruRow = $db->table('master_guru')
                    ->where('LOWER(email)', strtolower($email))
                    ->get()->getRow();

                if ($guruRow && !empty($guruRow->id_user)) {
                    $user = $db->table('users a')
                        ->select('a.*, b.group_id, c.name as role_name')
                        ->join('users_groups b', 'a.id = b.user_id', 'left')
                        ->join('groups c', 'b.group_id = c.id', 'left')
                        ->where('a.id', $guruRow->id_user)
                        ->get()->getRow();

                    // Backfill email to users table if missing
                    if ($user && empty($user->email)) {
                        $db->table('users')->where('id', $user->id)->update(['email' => $email]);
                    }
                }
            }

            if (!$user) {
                return redirect()->to('auth')->with('error', 'Email Google (' . esc($email) . ') belum terdaftar pada akun Guru / Pengguna.');
            }

            if ((string)$user->active !== '1') {
                return redirect()->to('auth')->with('error', 'Akun Anda (' . esc($email) . ') saat ini dinonaktifkan.');
            }

            // 5. Get Guru Info
            $guru = $db->table('master_guru')->where('id_user', $user->id)->get()->getRow();
            $groupName = strtolower(trim((string) ($user->role_name ?? '')));
            if ($groupName === '') {
                $groupName = ['1' => 'admin', '2' => 'guru', '3' => 'siswa', '4' => 'panitia'][(string) $user->group_id] ?? '';
            }

            // Login Google hanya untuk staf. Tanpa whitelist ini akun siswa
            // (atau user tanpa grup) akan diberi role_name 'Guru' dan lolos
            // pemeriksaan inGroup('guru') di seluruh endpoint /presensi.
            if (!in_array($groupName, ['guru', 'admin', 'panitia'], true)) {
                return redirect()->to('auth')->with('error', 'Akun ini tidak diizinkan login melalui Google.');
            }

            // 6. Login Success — ID sesi baru sebelum data login ditulis.
            $this->session->regenerate(true);

            $this->session->set([
                'user_id' => $user->id,
                'username' => $user->username,
                'nama' => $user->first_name . ($user->last_name ? ' ' . $user->last_name : ''),
                'email' => $user->email ?: $email,
                'role' => $user->group_id,
                'role_name' => $groupName,
                'foto' => $user->foto ?: ($userData['picture'] ?? null),
                'is_login' => true,
                'login_variant' => 'staff',
                'id_guru' => $guru ? $guru->id_guru : 0,
                'login_provider' => 'google'
            ]);

            // PWA "tetap login" 30 hari — hanya guru (admin/panitia dikecualikan).
            if ($groupName === 'guru') {
                \App\Libraries\RememberLogin::issue((int) $user->id);
            }

            if ($groupName === 'admin') {
                return redirect()->to('admin/presensi');
            }
            if ($groupName === 'panitia') {
                return redirect()->to('dashboard');
            }

            return redirect()->to('presensi');

        } catch (\Throwable $e) {
            log_message('error', 'Google Auth Exception: ' . $e->getMessage());
            return redirect()->to('auth')->with('error', 'Terjadi kesalahan sistem saat login Google. Silakan coba lagi.');
        }
    }

    /**
     * Show attendance page (mobile)
     */
    public function index()
    {
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth');
        }

        $setting = $this->settingModel->first();
        if (!$setting) {
            $setting = $this->getDefaultSettings();
        }

        $today = date('Y-m-d');
        $userId = session()->get('user_id');

        $todayAttendance = $this->attendanceModel
            ->select('absensi_harian.*, loc_in.nama_lokasi as nama_lokasi_masuk, loc_out.nama_lokasi as nama_lokasi_pulang')
            ->join('absensi_lokasi loc_in', 'loc_in.id = absensi_harian.lokasi_masuk_id', 'left')
            ->join('absensi_lokasi loc_out', 'loc_out.id = absensi_harian.lokasi_pulang_id', 'left')
            ->where('absensi_harian.user_id', $userId)
            ->where('absensi_harian.tgl', $today)
            ->first();

        return view('members/guru/absen', [
            'setting' => $setting,
            'attendance' => $todayAttendance,
        ]);
    }

    private function calculateProgressivePenalty(int $minutes, $setting, string $type = 'Telat'): array
    {
        // Sumber tunggal di AttendancePenaltyService (dulu duplikat di 2 controller).
        // Seluruh call-site $this->... tetap berfungsi tanpa perubahan.
        if (is_array($setting)) {
            $setting = (object) $setting;
        }
        return (new \App\Services\AttendancePenaltyService())->calculatePenalty($minutes, $setting, $type);
    }

    private function calculateProgressiveRewardPoints(int $minutes, $setting): int
    {
        if (is_array($setting)) {
            $setting = (object) $setting;
        }
        return (new \App\Services\AttendancePenaltyService())->calculateReward($minutes, $setting);
    }

    private function syncPoinHistory($userId, $tipe, $poin, $keterangan, $refId = null)
    {
        try {
            $model = new PoinHistoryModel();
            $model->insert([
                'user_id' => $userId,
                'poin' => $poin,
                'tipe' => $tipe,
                'keterangan' => $keterangan,
                'ref_id' => $refId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'syncPoinHistory failed: ' . $e->getMessage());
        }
    }

    private function countConsecutiveAlphaDays($userId, $todayDate)
    {
        try {
            $penaltyService = new \App\Services\AttendancePenaltyService();
            return $penaltyService->getConsecutiveAlphaStreak((int) $userId, $todayDate);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Catat surat teguran saat guru check-in melewati ambang.
     *
     * Terbitkan lewat SuratTeguranService supaya aturan monotonic dan slot
     * permanen berlaku sama seperti di cron — jalur ini punya implementasi
     * sendiri yang tidak sengaja salah aturan.
     *
     * @return array{id:int,level:int}|null Surat yang terbit, null kalau tidak
     */
    private function recordSuratTeguran($db, $userId, $idGuru, $level, $bulan, $tahun)
    {
        $svc = new \App\Services\SuratTeguranService($db);

        if (!$svc->tabelAda()) {
            return null;
        }

        // id_guru = 0 berarti guru tidak punya baris master_guru. Surat
        // tidak bisa diatribusikan ke siapa pun: tidak muncul di daftar admin
        // (INNER JOIN), tidak bisa diunduh, tidak bisa dibatalkan — tapi tetap
        // membakar nomor urut serta memblokir slot guru lain lewat unique key.
        // Sembilan baris seperti ini sudah terlanjur ada (Juni–Agustus 2026);
        // itu sebabnya jalur ini menolak menebaknya.
        if ($idGuru <= 0) {
            log_message('error', '[recordSuratTeguran] dilewati: guru user '
                . (int) $userId . ' belum punya baris master_guru, surat tidak bisa '
                . 'diatribusikan (ambang level ' . (int) $level . ').');

            return null;
        }

        $alpha = $this->countAlphaCurrentMonth((int) $userId);

        // Selaraskan dulu, supaya surat yang kembali sah hidup kembali
        // sebelum percobaan menerbitkan yang baru.
        $svc->sinkronkan($idGuru, $alpha, (string) $bulan, (string) $tahun);

        return $svc->terbitkan($idGuru, $alpha, (string) $bulan, (string) $tahun);
    }

    /**
     * Normalisasi nilai tanggal izin (DateTime|int|string|null) ke Y-m-d.
     * date() mentah melempar TypeError bila diberi DateTime — pernah
     * tercatat di log ("Notifikasi pengajuan izin gagal: date(): ...").
     */
    private function tglIzinYmd($val, string $fallback = ''): string
    {
        try {
            if ($val instanceof \DateTimeInterface) {
                return $val->format('Y-m-d');
            }
            if (is_int($val) || (is_string($val) && ctype_digit(trim($val)))) {
                return date('Y-m-d', (int) $val);
            }
            $s = trim((string) ($val !== null && $val !== '' ? $val : $fallback));
            if ($s !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m) === 1) {
                return "{$m[1]}-{$m[2]}-{$m[3]}";
            }
            $ts = $s !== '' ? strtotime($s) : false;
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        } catch (\Throwable $e) {
        }
        return date('Y-m-d');
    }

    /** Label d/m/Y dari nilai tanggal izin campuran. */
    private function tglIzinLabel($val, string $fallback = ''): string
    {
        $ymd = $this->tglIzinYmd($val, $fallback);
        $ts = strtotime($ymd);
        return $ts !== false ? date('d/m/Y', $ts) : $ymd;
    }

    /** [tahun, bulan] dari nilai tanggal izin campuran. */
    private function tahunBulanIzin($val, string $fallback = ''): array
    {
        $ymd = $this->tglIzinYmd($val, $fallback);
        $parts = explode('-', $ymd);
        return [(int) ($parts[0] ?? date('Y')), (int) ($parts[1] ?? date('n'))];
    }

    /**
     * Resolve id_guru for user ID.
     * Checks session, master_guru table, or creates linkage if missing.
     */
    private function resolveIdGuru(int $userId): int
    {        $idGuru = session()->get('id_guru');
        if (!empty($idGuru)) {
            return (int) $idGuru;
        }

        try {
            $db = \Config\Database::connect();
            if ($db && $db->tableExists('master_guru')) {
                $guru = $db->table('master_guru')->where('id_user', $userId)->get()->getRow();
                if ($guru && !empty($guru->id_guru)) {
                    $idGuru = (int) $guru->id_guru;
                    session()->set('id_guru', $idGuru);
                    return $idGuru;
                }

                // If not yet linked in master_guru, create a matching row
                if ($db->tableExists('users')) {
                    $u = $db->table('users')->where('id', $userId)->get()->getRow();
                    if ($u) {
                        $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                        if (empty($fullName)) {
                            $fullName = $u->username ?? ('User ' . $userId);
                        }
                        $db->table('master_guru')->insert([
                            'id_user'   => $userId,
                            'nama_guru' => $fullName,
                            'username'  => $u->username ?? '',
                            'nip'       => '',
                            'foto'      => $u->foto ?? null
                        ]);
                        $newId = (int) $db->insertID();
                        if ($newId > 0) {
                            session()->set('id_guru', $newId);
                            return $newId;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'resolveIdGuru error: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Guru check-in endpoint (AJAX)
     * expects POST lat, lng, foto(optional base64)
     */
    public function checkIn()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        try {
            // Rate Limiting - Max 5 requests per minute
            $throttler = \Config\Services::throttler();
            $ipKey = 'attendance_' . md5($this->request->getIPAddress() . session()->get('user_id'));
            if ($throttler->check($ipKey, 5, MINUTE) === false) {
                return $this->respond(false, 'Terlalu banyak permintaan. Silakan tunggu 1 menit.', null, null, 429);
            }

            $lat = (float) $this->request->getPost('lat');
            $lng = (float) $this->request->getPost('lng');
            $foto = $this->request->getPost('foto');
            $accuracy = (float) $this->request->getPost('accuracy');
            $faceDescriptor = $this->request->getPost('face_descriptor');
            $isWfh = $this->request->getPost('is_wfh') === '1';

            $db = \Config\Database::connect();
            $this->patchAbsensiHarianTable();
            $setting = $this->settingModel->first();

            // --- GATE SURAT TEGURAN (absen Face ID) ---
            // Guru tetap boleh absen seperti biasa, TETAPI harus lebih dulu
            // membaca dan mengonfirmasi surat teguran bulan berjalan. Gate ini
            // hanya berlaku untuk check-in Face ID; absen QR (qrCheckin) dan
            // absen Pulang tidak tersentuh.
            //
            // Filter bulan: surat bulan lalu tidak lagi memblokir. Filter
            // ack_at: begitu dikonfirmasi, guru lanjut absen normal.
            $gateIdGuru = $this->resolveIdGuru((int) session()->get('user_id'));
            $pendingTeguran = $this->pendingTeguranGate($gateIdGuru, (int) date('n'), (int) date('Y'), $db);
            if ($pendingTeguran !== []) {
                return $this->respond(false, 'Anda memiliki surat teguran yang belum dikonfirmasi. Baca dan konfirmasi terlebih dahulu sebelum absen.', [
                    'require_teguran_ack' => true,
                    'letters' => $this->teguranListPayload($pendingTeguran),
                ], null, 403);
            }

            // WFH validation
            if ($isWfh) {
                if (!$setting || !(int) ($setting->wfh_enabled ?? 0)) {
                    return $this->respond(false, 'Fitur WFH tidak aktif. Hubungi admin.');
                }
                // Check WFH quota
                $bulanIni = date('Y-m');
                $wfhCount = $db->table('absensi_harian')
                    ->where('user_id', session()->get('user_id'))
                    ->where('is_wfh', 1)
                    ->like('tgl', $bulanIni, 'after')
                    ->countAllResults();
                $maxWfh = (int) ($setting->wfh_max_per_bulan ?? 5);
                if ($wfhCount >= $maxWfh) {
                    return $this->respond(false, "Kuota WFH bulan ini habis ($maxWfh hari). Absen di kantor atau hubungi admin.");
                }
                // Skip GPS accuracy checks for WFH
            }

            // GPS & Mock Location Detection (skip for WFH).
            // Sinyal mock KUAT langsung ditolak; akurasi rendah (>100m) hanya ditandai
            // untuk verifikasi admin agar absen sah di dalam gedung tidak ikut gagal.
            $isMockLocation = 0;
            $mockWarning = '';

            if (!$isWfh) {
                if (!$this->isValidCoordinate($lat, $lng)) {
                    return $this->respond(false, 'Lokasi tidak valid');
                }
                if ($accuracy < 0) {
                    return $this->respond(false, 'Akurasi GPS tidak valid');
                }
                if ($accuracy > 10000) {
                    return $this->respond(false, 'Akurasi GPS terlalu rendah. Nyalakan GPS dan coba lagi.');
                }

                $mockCheck = $this->detectMockLocation($lat, $lng, $accuracy);
                $isMockLocation = $mockCheck['is_mock'];
                if ($mockCheck['strong']) {
                    return $this->respond(false, 'Terdeteksi lokasi palsu (mock location). Matikan aplikasi Fake GPS dan coba lagi.', null, null, 400);
                }
                if ($isMockLocation) {
                    $mockWarning = ' ⚠️ Akurasi GPS rendah, data tercatat untuk verifikasi.';
                }
            }

            // Mode dinas HANYA dari flag eksplisit aplikasi + wajib keterangan.
            // (Dulu: kata kunci di keterangan otomatis memicu dinas → celah bypass
            // geofence & verifikasi wajah cukup dengan mengetik "dinas".)
            $keteranganInput = (string) ($this->request->getPost('keterangan') ?? '');
            $isDinas = ($this->request->getPost('is_dinas') === '1')
                || ($this->request->getPost('is_dinas') === 'true')
                || ($this->request->getPost('is_dinas') === 1)
                || ($this->request->getPost('is_dinas') === true);

            if ($isDinas && $isWfh) {
                return $this->respond(false, 'Pilih salah satu antara Mode Dinas atau WFH.');
            }

            if ($isDinas && empty(trim($keteranganInput))) {
                return $this->respond(false, 'Absen dinas di luar lokasi wajib mengisi keterangan / alasan tugas luar.');
            }

            $faceRequired = false;
            if ($isDinas) {
                $faceRequired = false;
            } elseif ($isWfh) {
                $faceRequired = $setting && (int) ($setting->wfh_wajib_wajah ?? 1) === 1;
            } else {
                $faceRequired = $setting && (int) $setting->foto_wajib === 1;
            }

            $isAiMatch = ($setting->face_match_mode ?? 'ai_match') === 'ai_match';
            if ($faceRequired && $isAiMatch) {
                if (empty($faceDescriptor)) {
                    return $this->respond(false, 'Verifikasi wajah gagal: data wajah tidak ditemukan. Silakan coba lagi.');
                }
                $descriptorArr = json_decode($faceDescriptor, true);
                if (!is_array($descriptorArr) || count($descriptorArr) !== 128) {
                    return $this->respond(false, 'Verifikasi wajah gagal: data wajah tidak valid.');
                }
                $storedRow = $db->table('users')
                    ->select('face_descriptor')
                    ->where('id', session()->get('user_id'))
                    ->get()->getRow();
                $storedDescriptor = $storedRow->face_descriptor ?? null;

                if (empty($storedDescriptor)) {
                    return $this->respond(false, 'Silakan daftarkan wajah terlebih dahulu di menu Profil sebelum absen.');
                }

                $storedArr = json_decode($storedDescriptor, true);
                if (!is_array($storedArr) || count($storedArr) !== 128) {
                    return $this->respond(false, 'Data wajah profil rusak. Silakan daftarkan ulang di menu Profil.');
                }

                $distance = $this->euclideanDistance($descriptorArr, $storedArr);
                $faceThreshold = isset($setting->face_threshold) && (float)$setting->face_threshold > 0 ? (float)$setting->face_threshold : 0.72;
                $matchScore = $this->calculateSimilarity($distance);

                if ($distance > $faceThreshold) {
                    return $this->respond(false, 'Wajah tidak cocok dengan data profil (Tingkat kemiripan: ' . $matchScore . '%). Pastikan pencahayaan cukup dan wajah menghadap kamera.', [
                        'face_match_score' => $matchScore,
                        'face_distance' => round($distance, 3),
                        'face_match' => false
                    ]);
                }
            }

            $userId = (int) session()->get('user_id');
            $idGuru = $this->resolveIdGuru($userId);
            $teguranLevel = null; // diisi di blok warning bila ambang alpha tercapai

            // Handle Offline Timestamp — toleransi ±5 menit dari jam server.
            // Mencegah backdate: timestamp mundur >5 menit (mis. telat dimundurkan ke pagi) ditolak.
            $timestampLocal = $this->request->getPost('timestamp');
            if ($timestampLocal && strtotime($timestampLocal)) {
                $ts = strtotime($timestampLocal);
                $serverNow = time();
                if (abs($serverNow - $ts) > 300) {
                    return $this->respond(false, 'Waktu perangkat tidak valid (selisih lebih dari 5 menit dari server)');
                }
                if (date('Y-m-d', $ts) !== date('Y-m-d', $serverNow)) {
                    return $this->respond(false, 'Sync offline hanya bisa untuk hari ini');
                }
                $today = date('Y-m-d', $ts);
                $now = date('H:i:s', $ts);
                $recordedTime = date('Y-m-d H:i:s', $ts);
            } else {
                $today = date('Y-m-d');
                $now = date('H:i:s');
                $recordedTime = date('Y-m-d H:i:s');
            }

            // --- VALIDASI RENTANG WAKTU ---
            $dayOfWeek = date('N', strtotime($today));
            $jadwal = null;

            // Ambil Jadwal Kerja
            if ($db->tableExists('absensi_jadwal_kerja')) {
                $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
            }

            if ($isWfh) {
                // WFH: use WFH schedule
                $jamMasukLimit = ($setting->wfh_mulai ?? '08:00:00');
                $waktuBukaMasuk = date('H:i:s', strtotime($jamMasukLimit) - (1 * 3600));
                $waktuTutupMasuk = date('H:i:s', strtotime($jamMasukLimit) + (4 * 3600));
            } else {
                // Jadwal efektif: shift per guru (bila ditugaskan) → jadwal harian → setting global
                $shiftModel = new \App\Models\ShiftModel();
                $workSchedule = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
                $jamMasukLimit = $workSchedule['jam_masuk'];

                // Hitung Rentang Waktu (Buka 1 jam sblm, Tutup 3 jam stlh)
                $waktuBukaMasuk = date('H:i:s', strtotime($jamMasukLimit) - (1 * 3600));
                $waktuTutupMasuk = date('H:i:s', strtotime($jamMasukLimit) + (3 * 3600));
            }

            if (!$isDinas) {
                if ($now < $waktuBukaMasuk) {
                    return $this->respond(false, "Absen Masuk belum dibuka. Jadwal: $jamMasukLimit (Dibuka $waktuBukaMasuk)");
                }
                if ($now > $waktuTutupMasuk) {
                    return $this->respond(false, "Waktu Absen Masuk habis. Batas: $waktuTutupMasuk");
                }
            }
            // --- END VALIDASI ---
            if ($this->attendanceModel->where('user_id', $userId)->where('tgl', $today)->first()) {
                return $this->respond(false, 'Anda sudah absen masuk hari ini (Tgl: ' . $today . ')');
            }

            if (!$isDinas) {
                $holidayMsg = $this->checkIsHoliday($today);
                if ($holidayMsg) {
                    return $this->respond(false, $holidayMsg);
                }
            }

            // Validate geofence (skip for Dinas & WFH)
            $validDistance = 0;
            $lokasiId = null;

            if (!$isDinas && !$isWfh) {
                $locationRows = [];
                try {
                    $locationRows = $db->table('absensi_lokasi')->get()->getResult();
                } catch (\Throwable $ex) {
                    // Ignore, means table missing or empty
                }

                if (empty($locationRows)) {
                    return $this->respond(false, 'Titik lokasi kantor belum dikonfigurasi oleh admin. Hubungi admin sekolah.');
                }

                $validDistance = null;
                foreach ($locationRows as $loc) {
                    $locLat = (float) $loc->lat;
                    $locLng = (float) $loc->lng;
                    $radius = (float) (!empty($loc->radius_meter) ? $loc->radius_meter : ($setting->radius_meter ?? 100));
                    $dist = distance_meter($lat, $lng, $locLat, $locLng);
                    if ($dist <= $radius) {
                        $validDistance = $dist;
                        $lokasiId = $loc->id;
                        break;
                    }
                }

                if ($validDistance === null) {
                    return $this->respond(false, 'Di luar area lokasi absensi. Absen di luar lokasi hanya diperbolehkan jika menggunakan Mode Dinas atau WFH.');
                }
            }

            // handle foto upload if provided & required
            // Nama file unik per hari: tidak menimpa foto hari sebelumnya.
            $fotoPath = null;
            if (!empty($foto)) {
                $fotoPath = $this->saveAbsenPhoto($foto, (int) $userId, 'masuk');
            }

            if ($fotoPath === null && !$isWfh && $setting && (int) $setting->foto_wajib === 1) {
                return $this->respond(false, 'Foto wajib diunggah dan harus valid');
            }

            if ($isDinas) {
                $statusMasuk = 'DINAS_PENDING';
                $telatMenit = 0;
                $points = 0;
                $denda = 0;
                $keteranganDenda = null;
                $datangAwalMenit = 0;
            } elseif ($isWfh) {
                $statusMasuk = 'WFH';
                $telatMenit = 0;
                $points = 0;
                $denda = 0;
                $keteranganDenda = null;
            } else {
                // Jadwal efektif per guru (shift → jadwal harian → setting)
                $dayOfWeek = date('N', strtotime($today));
                $jadwal = null;
                // $db already defined above
                if ($db->tableExists('absensi_jadwal_kerja')) {
                    $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
                }

                $shiftModel = new \App\Models\ShiftModel();
                $workSchedule = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
                $jamMasukLimit = $workSchedule['jam_masuk'];
                $graceMins = (int) ($workSchedule['grace_menit'] ?? ($setting->grace_period ?? 15));

                // Status masuk dari rumus baku AttendancePolicy (sama dengan
                // scan QR, poin siswa, rekap, dan koreksi admin).
                $hit = \App\Services\AttendancePolicy::telat($today, $recordedTime, [
                    'jam_masuk'   => $jamMasukLimit,
                    'grace_menit' => $graceMins,
                ]);
                $statusMasuk     = $hit['status'];
                $telatMenit      = $hit['telat_menit'];
                $datangAwalMenit = $hit['datang_awal_menit'];

                $points = 0;
                $denda = 0;
                $keteranganDenda = null;

                if ($datangAwalMenit > 0) {
                    $points += $this->calculateProgressiveRewardPoints($datangAwalMenit, $setting);
                }
                if ($statusMasuk === 'LATE') {
                    // Points & Fine Calculation (Progressive)
                    $calc = $this->calculateProgressivePenalty($telatMenit, $setting, 'Telat Masuk');
                    $points = -1 * $calc['points'];
                    $denda = $calc['denda'];
                    $keteranganDenda = $calc['keterangan'];
                }

                // --- WARNING SYSTEM FOR ABSENCES (3, 7, 10 Days) ---
                // Cap denda dihitung ulang DI DALAM transaksi (dengan kunci) tepat
                // sebelum insert, agar dua check-in paralel tidak sama-sama lolos cap.
                // Level teguran hanya dicatat SETELAH insert absen sukses (lihat bawah),
                // agar teguran tidak tercatat bila insert gagal.
                $teguranLevel = null;
                $confirm = $this->request->getPost('confirm');
                
                // 1. Cek berturut-turut (Peringatan jika >= 3)
                $consecutiveAlpha = $this->countConsecutiveAlphaDays($userId, $today);
                if ($consecutiveAlpha >= 3) {
                    if (!$confirm) {
                        return $this->respond(false, "Peringatan Keras: Anda tidak hadir selama {$consecutiveAlpha} hari kerja berturut-turut. Mohon perbaiki tingkat kehadiran Anda. Lanjutkan absen?", [
                            'warning_level' => 'consecutive',
                            'require_confirm' => true,
                            csrf_token() => csrf_hash()
                        ]);
                    }
                }

                $alphaCount = $this->countAlphaCurrentMonth($userId);

                // If 10+ days, force status to pending verification regardless of confirmation
                if ($alphaCount >= 10) {
                    $teguranLevel = 10;
                    // But first, we must warn the user if they haven't confirmed yet
                    if (!$confirm) {
                        return $this->respond(false, "Peringatan Tingkat III: Anda memiliki $alphaCount hari ketidakhadiran (Alpha) bulan ini. Absensi ini MEMERLUKAN VERIFIKASI sebelum tercatat. Lanjutkan?", [
                            'warning_level' => 10,
                            'require_confirm' => true,
                            csrf_token() => csrf_hash()
                        ]);
                    }
                    // If confirmed, proceed but set status
                    $statusMasuk = 'PENDING_VERIFICATION';
                }
                // If 7-9 days
                elseif ($alphaCount >= 7) {
                    $teguranLevel = 7;
                    if (!$confirm) {
                        return $this->respond(false, "Peringatan Tingkat II: Anda memiliki $alphaCount hari ketidakhadiran (Alpha) bulan ini. Harap tingkatkan kehadiran Anda. Lanjutkan?", [
                            'warning_level' => 7,
                            'require_confirm' => true,
                            csrf_token() => csrf_hash()
                        ]);
                    }
                }
                // If 3-6 days
                elseif ($alphaCount >= 3) {
                    $teguranLevel = 3;
                    if (!$confirm) {
                        return $this->respond(false, "Peringatan Tingkat I: Anda memiliki $alphaCount hari ketidakhadiran (Alpha) bulan ini. Lanjutkan?", [
                            'warning_level' => 3,
                            'require_confirm' => true,
                            csrf_token() => csrf_hash()
                        ]);
                    }
                }
                // --- END WARNING SYSTEM ---
            }

            $data = [
                'user_id' => $userId,
                'id_guru' => $idGuru ?: 0,
                'tgl' => $today,
                'jam_masuk' => $recordedTime,
                'lat_masuk' => $lat,
                'lng_masuk' => $lng,
                'lokasi_masuk_id' => $lokasiId,
                'jarak_masuk' => (int) round($validDistance === null ? 0 : $validDistance),
                'foto_masuk' => $fotoPath,
                'status_masuk' => $statusMasuk,
                'points' => $points ?? 0,
                'denda' => $denda ?? 0,
                'keterangan_denda' => $keteranganDenda ?? null,
                'keterangan' => $this->request->getPost('keterangan')
            ];

            if ($db->fieldExists('telat_menit', 'absensi_harian')) {
                $data['telat_menit'] = $telatMenit;
            }
            if ($db->fieldExists('datang_awal_menit', 'absensi_harian')) {
                $data['datang_awal_menit'] = $datangAwalMenit ?? 0;
            }
            if ($db->fieldExists('ip_address', 'absensi_harian')) {
                $data['ip_address'] = $this->request->getIPAddress();
            }
            if ($db->fieldExists('device_info', 'absensi_harian')) {
                $userAgent = (string) $this->request->getUserAgent();
                if ($timestampLocal) {
                    $userAgent .= ' (Offline Mode Sync)';
                }
                $data['device_info'] = $userAgent;
            }
            if ($db->fieldExists('gps_accuracy', 'absensi_harian')) {
                $data['gps_accuracy'] = $accuracy > 0 ? $accuracy : null;
            }
            if ($db->fieldExists('is_mock_location', 'absensi_harian')) {
                $data['is_mock_location'] = $isMockLocation;
            }
            if ($db->fieldExists('is_wfh', 'absensi_harian')) {
                $data['is_wfh'] = $isWfh ? 1 : 0;
            }

            // Insert protektif terhadap race condition: UNIQUE(user_id, tgl) di DB
            // adalah penjaga akhir bila dua request paralel lolos cek di atas.
            $db->transBegin();
            // Cap denda dihitung di sini (terkunci FOR UPDATE) agar konsisten
            // dengan baris yang akan di-insert tepat di bawah.
            if (($denda ?? 0) > 0) {
                $penaltyService = new \App\Services\AttendancePenaltyService();
                $capped = $penaltyService->applyMonthlyCap((int) $userId, $today, (float) $denda, $setting, null, true);
                if ($capped['capped']) {
                    $denda = $capped['total'];
                    $capRp = number_format((float) ($setting->max_denda_bulan ?? 0), 0, ',', '.');
                    $keteranganDenda = trim(($keteranganDenda ?? '') . " (dibatasi cap bulanan Rp{$capRp})");
                    $data['denda'] = $denda;
                    $data['keterangan_denda'] = $keteranganDenda;
                }
            }
            try {
                $insertedId = $this->attendanceModel->insert($data);
            } catch (\Throwable $e) {
                $db->transRollback();
                if ($this->isDuplicateKeyError($e)) {
                    return $this->respond(false, 'Anda sudah absen masuk hari ini (Tgl: ' . $today . ')');
                }
                throw $e;
            }
            if ($insertedId === false) {
                $db->transRollback();
                if ($this->attendanceModel->where('user_id', $userId)->where('tgl', $today)->first()) {
                    return $this->respond(false, 'Anda sudah absen masuk hari ini (Tgl: ' . $today . ')');
                }
                return $this->respond(false, 'Gagal menyimpan absensi, silakan coba lagi.');
            }
            $db->transCommit();

            // Pengumuman "guru paling awal hari ini" — dikirim tepat di saat
            // absensi tercatat, bukan lewat cron terpisah. Idempoten per hari,
            // jadi aman dipanggil dari semua jalur check-in.
            try {
                \App\Services\GuruTerawalService::afterCheckIn($db, $today, $userId);
            } catch (\Throwable $e) {
                log_message('error', 'Pengumuman guru terawal gagal: ' . $e->getMessage());
            }

            // Surat teguran dicatat SETELAH insert sukses (bukan sebelumnya).
            if (!empty($teguranLevel)) {
                try {
                    $this->recordSuratTeguran($db, $userId, $idGuru, $teguranLevel, date('m', strtotime($today)), date('Y', strtotime($today)));
                } catch (\Throwable $e) {
                    log_message('error', 'Gagal mencatat surat teguran: ' . $e->getMessage());
                }
            }

            // AUDIT LOG
            try {
                $auditModel = new AuditLogModel();
                $auditModel->log(
                    'absensi_harian',
                    $insertedId,
                    'CREATE',
                    $userId,
                    $userId,
                    null,
                    $data,
                    $isDinas ? 'Absen Masuk (Dinas)' : ($isWfh ? 'Absen Masuk (WFH)' : 'Absen Masuk')
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
            }

            // POIN HISTORY: Sync to poin_history ledger (skip for WFH & Dinas)
            if (!$isDinas && !$isWfh) {
                $poinModel = new \App\Models\PoinHistoryModel();
                $poinModel->syncAttendancePoint($insertedId);
            }

            // MIKROTIK INTEGRATION: Enable Hotspot User (skip for WFH)
            if (!$isWfh) {
                $this->enableHotspotUser($userId ?? 0);
            }

            $msg = 'Check-in berhasil';
            if ($isDinas) $msg = 'Absen Dinas dicatat. Menunggu verifikasi admin.';
            if ($isWfh) $msg = 'Absen WFH berhasil dicatat.';
            $msg .= $mockWarning;

            if ($timestampLocal) {
                $msg .= ' (Data Offline Tersimpan)';
            }

            // Add Motivation if Late (not for WFH/Dinas)
            if (!$isDinas && !$isWfh && ($statusMasuk === 'LATE' || $telatMenit > 0)) {
                $msg .= "\n\n" . $this->getRandomMotivation();
            }

            // Siapkan informasi panggilan & suara ucapan (Text-to-Speech)
            $namaGuruLengkap = '';
            $sapaan = 'Bapak atau Ibu';
            if ($idGuru && $db->tableExists('master_guru')) {
                $guruRow = $db->table('master_guru')->select('nama_guru, jenis_kelamin')->where('id_guru', $idGuru)->get()->getRow();
                if ($guruRow) {
                    $namaGuruLengkap = $guruRow->nama_guru ?? '';
                    $jk = strtoupper(trim((string) ($guruRow->jenis_kelamin ?? '')));
                    if ($jk === 'L' || strpos($jk, 'LAKI') !== false) {
                        $sapaan = 'Bapak';
                    } elseif ($jk === 'P' || strpos($jk, 'PEREMPUAN') !== false) {
                        $sapaan = 'Ibu';
                    }
                }
            }
            if (empty($namaGuruLengkap)) {
                $uRow = $db->table('users')->select('first_name, last_name')->where('id', $userId)->get()->getRow();
                if ($uRow) {
                    $namaGuruLengkap = trim(($uRow->first_name ?? '') . ' ' . ($uRow->last_name ?? ''));
                }
            }

            // Bersihkan gelar akademik agar pelafalan TTS terdengar wajar dan alami
            $namaBersih = preg_replace('/,.*$/', '', $namaGuruLengkap);
            $namaBersih = preg_replace('/\b(Drs|Dra|Ir|Prof|Dr|H|Hj|Ust|Kh)\.?\b/i', '', $namaBersih);
            $namaBersih = trim(preg_replace('/\s+/', ' ', $namaBersih));

            // Ambil hanya nama depan saja untuk panggilan suara TTS
            $namaWords = array_values(array_filter(explode(' ', $namaBersih), static function ($w) {
                return strlen(rtrim($w, '.')) > 1;
            }));
            $namaDepan = $namaWords[0] ?? (explode(' ', $namaBersih)[0] ?? '');
            if ($namaDepan !== '') {
                $namaDepan = mb_convert_case($namaDepan, MB_CASE_TITLE, 'UTF-8');
            }
            if ($namaBersih !== '') {
                $namaBersih = mb_convert_case($namaBersih, MB_CASE_TITLE, 'UTF-8');
            }

            $isLate = (!$isDinas && !$isWfh && ($statusMasuk === 'LATE' || $telatMenit > 0));
            $panggilanLengkap = $namaDepan !== '' ? "$sapaan $namaDepan" : $sapaan;

            if ($isDinas) {
                $voiceText = "Terima kasih $panggilanLengkap. Presensi dinas luar berhasil dicatat. Selamat bertugas.";
            } elseif ($isWfh) {
                $voiceText = "Terima kasih $panggilanLengkap. Presensi WFH berhasil dicatat. Selamat bertugas.";
            } elseif ($isLate) {
                $voiceText = "Perhatian $panggilanLengkap. Presensi masuk berhasil, Anda terlambat $telatMenit menit. Mohon tingkatkan kedisiplinan.";
            } else {
                $voiceText = "Terima kasih $panggilanLengkap. Presensi masuk berhasil, hadir tepat waktu. Selamat bertugas!";
            }

            // Notifikasi keterlambatan ke grup/channel (best-effort, jangan gagalkan check-in)
            if ($isLate) {
                try {
                    (new \App\Services\NotificationService())->notifyLateCheckin(
                        $namaGuruLengkap !== '' ? $namaGuruLengkap : ('User ' . $userId),
                        substr((string) ($recordedTime ?? date('Y-m-d H:i:s')), 11, 5),
                        (int) $telatMenit,
                        // Dipakai untuk memberi tahu guru yang telat secara
                        // personal, bukan hanya ke grup.
                        $userId
                    );
                } catch (\Throwable $e) {
                    log_message('error', 'Notifikasi telat gagal: ' . $e->getMessage());
                }
            }

            // Absen Mode Dinas → DINAS_PENDING: kabari grup + guru bahwa ada
            // antrean verifikasi baru. Tanpa ini admin tidak tahu pengajuan
            // dinas masuk kecuali membuka menu pending secara manual.
            if ($isDinas) {
                try {
                    (new \App\Services\NotificationService())->notifyDinasSubmitted(
                        $namaGuruLengkap !== '' ? $namaGuruLengkap : ('User ' . $userId),
                        $today,
                        $keteranganInput,
                        (int) $userId
                    );
                } catch (\Throwable $e) {
                    log_message('error', 'Notifikasi pengajuan dinas gagal: ' . $e->getMessage());
                }
            }

            return $this->respond(true, $msg, [
                'status_masuk' => $statusMasuk,
                'telat_menit'  => (int) $telatMenit,
                'is_late'      => $isLate,
                'nama_guru'    => $namaGuruLengkap,
                'nama_bersih'  => $namaBersih,
                'nama_depan'   => $namaDepan,
                'sapaan'       => $sapaan,
                'voice_text'   => $voiceText,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Check-in Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            return $this->respond(false, 'Terjadi kesalahan sistem — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Guru check-out endpoint
     */
    public function checkOut()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        try {
            // Rate Limiting - Max 5 requests per minute
            $throttler = \Config\Services::throttler();
            $ipKey = 'attendance_out_' . md5($this->request->getIPAddress() . session()->get('user_id'));
            if ($throttler->check($ipKey, 5, MINUTE) === false) {
                return $this->respond(false, 'Terlalu banyak permintaan. Silakan tunggu 1 menit.', null, null, 429);
            }

            $lat = (float) $this->request->getPost('lat');
            $lng = (float) $this->request->getPost('lng');
            $accuracy = (float) ($this->request->getPost('accuracy') ?? 0);
            $foto = $this->request->getPost('foto');
            $faceDescriptor = $this->request->getPost('face_descriptor');

            $userId = (int) session()->get('user_id');
            $idGuru = $this->resolveIdGuru($userId);

        // Handle Offline Timestamp — toleransi ±5 menit dari jam server (anti backdate).
        $timestampLocal = $this->request->getPost('timestamp');
        if ($timestampLocal && strtotime($timestampLocal)) {
            $ts = strtotime($timestampLocal);
            $serverNow = time();
            if (abs($serverNow - $ts) > 300) {
                return $this->respond(false, 'Waktu perangkat tidak valid (selisih lebih dari 5 menit dari server)');
            }
            if (date('Y-m-d', $ts) !== date('Y-m-d', $serverNow)) {
                return $this->respond(false, 'Sync offline hanya bisa untuk hari ini');
            }
            $checkOutDate = date('Y-m-d', $ts);
            $recordedTime = date('Y-m-d H:i:s', $ts);
            $now = date('H:i:s', $ts);
        } else {
            $checkOutDate = date('Y-m-d');
            $recordedTime = date('Y-m-d H:i:s');
            $now = date('H:i:s');
        }

        $todayAttendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('tgl', $checkOutDate)
            ->first();

        if (!$todayAttendance) {
            return $this->respond(false, 'Anda belum absen masuk pada tanggal ' . $checkOutDate);
        }

        // Baris bisa sudah ada tanpa jam_masuk: alpha dari cron, izin/sakit/
        // cuti yang disetujui admin, atau dinas yang disetujui retroactive.
        // Keberadaan baris BUKAN bukti sudah absen masuk — hanya jam_masuk
        // yang membuktikan. Tanpa guard ini guru bisa absen pulang di baris
        // yang tidak pernah ia masuki.
        if (empty($todayAttendance->jam_masuk)) {
            return $this->respond(false, 'Anda belum absen masuk pada tanggal ' . $checkOutDate
                . '. Absen pulang hanya bisa dilakukan setelah absen masuk tercatat.');
        }

        if ($todayAttendance->jam_pulang) {
            return $this->respond(false, 'Anda sudah absen pulang');
        }

        $isWfh = (int) ($todayAttendance->is_wfh ?? 0) === 1;

        // GPS Validation (skip for WFH)
        if ($isWfh) {
            $isMockLocation = 0;
            $mockWarning = '';
        } else {
            if (!$this->isValidCoordinate($lat, $lng)) {
                return $this->respond(false, 'Lokasi tidak valid');
            }
            $accuracy = (float) $this->request->getPost('accuracy');
            if ($accuracy < 0) {
                return $this->respond(false, 'Akurasi GPS tidak valid');
            }
            if ($accuracy > 10000) {
                return $this->respond(false, 'Akurasi GPS terlalu rendah. Nyalakan GPS dan coba lagi.');
            }

            $isMockLocation = 0;
            $mockWarning = '';

            $mockCheck = $this->detectMockLocation($lat, $lng, $accuracy);
            $isMockLocation = $mockCheck['is_mock'];
            if ($mockCheck['strong']) {
                return $this->respond(false, 'Terdeteksi lokasi palsu (mock location). Matikan aplikasi Fake GPS dan coba lagi.', null, null, 400);
            }
            if ($isMockLocation) {
                $mockWarning = ' ⚠️ Akurasi GPS rendah, data tercatat untuk verifikasi.';
            }
        }

        // --- FACE VERIFICATION (same as checkIn) ---
        $db = \Config\Database::connect();
        $setting = $this->settingModel->first();
        if (!$setting) {
            $setting = $this->getDefaultSettings();
        }
        // Status dinas saat check-out HANYA mengikuti record check-in yang sudah
        // berstatus DINAS*. Client tidak bisa menaikkan status jadi dinas lewat
        // POST is_dinas maupun kata kunci di keterangan (celah bypass geofence).
        $isDinas = \App\Services\WajibJurnalPulangService::isStatusDinas($todayAttendance->status_masuk ?? '');

        $faceRequired = false;
        if ($isDinas) {
            $faceRequired = false;
        } elseif ($isWfh) {
            $faceRequired = $setting && (int) ($setting->wfh_wajib_wajah ?? 1) === 1;
        } else {
            $faceRequired = $setting && (int) $setting->foto_wajib === 1;
        }

        $isAiMatch = ($setting->face_match_mode ?? 'ai_match') === 'ai_match';
        if ($faceRequired && $isAiMatch) {
            $faceCheck = $this->verifyFaceDescriptor((string) ($faceDescriptor ?? ''), $db, $setting);
            if (!$faceCheck['ok']) {
                return $this->respond(false, $faceCheck['message'], $faceCheck['data'] ?? null);
            }
        }

        // --- VALIDASI JAM PULANG ---
        $dayOfWeek = date('N', strtotime($checkOutDate));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }

        // Izin pulang awal: flag eksplisit + wajib alasan tertulis (teraudit di kolom keterangan).
        // Menerima flag baru 'izin_pulang_awal' maupun nilai legacy 'is_dinas=2' dari aplikasi lama.
        $isEarly = ($this->request->getPost('izin_pulang_awal') === '1')
            || ($this->request->getPost('is_dinas') === '2');
        if ($isEarly && empty(trim((string) ($this->request->getPost('keterangan') ?? '')))) {
            return $this->respond(false, 'Pulang awal wajib mengisi alasan pada kolom keterangan.');
        }

        if ($isWfh) {
            // WFH: use WFH schedule
            $jamPulangLimit = ($setting->wfh_selesai ?? '16:00:00');
        } else {
            // Jadwal efektif per guru (shift → jadwal harian → setting)
            $shiftModel = new \App\Models\ShiftModel();
            $workSchedule = $shiftModel->getScheduleForUser((int) $userId, (int) $dayOfWeek, $jadwal, $setting);
            $jamPulangLimit = $workSchedule['jam_pulang'];
        }

        // STRICT: Tidak boleh pulang sebelum waktunya (kecuali Dinas ATAU WFH ATAU Izin/Early)
        if (!$isDinas && !$isWfh && !$isEarly && $now < $jamPulangLimit) {
            return $this->respond(false, "Belum waktunya pulang. Jadwal: $jamPulangLimit");
        }
        // --- END VALIDASI ---

        // --- WAJIB JURNAL SEBELUM PULANG ---
        // Kebijakan opsional dari Pengaturan. Guru dengan sesi terjadwal hari
        // ini wajib sudah mengisi jurnal untuk tiap sesi yang sudah dimulai.
        // Dinas dikecualikan karena guru tidak mengajar di sekolah; hari libur
        // juga dikecualikan karena tidak ada KBM yang harus dijurnal.
        if (!$isDinas) {
            $sesiKurang = $this->getSessionsBlockingCheckout(
                (int) $idGuru,
                $checkOutDate,
                (int) $dayOfWeek,
                $now,
                $setting,
                $db
            );

            if (!empty($sesiKurang)) {
                $gate = $this->formatBlockingSessions($sesiKurang, $setting, $checkOutDate, $db);
                return $this->respond(false, $gate['message'], $gate['data']);
            }
        }
        // --- END WAJIB JURNAL ---

        // Validate geofence (skip for Dinas & WFH)
        $validDistance = 0;
        $lokasiId = null;

        if (!$isDinas && !$isWfh) {
            $locationRows = [];
            try {
                $locationRows = $db->table('absensi_lokasi')->get()->getResult();
            } catch (\Throwable $e) {
                log_message('error', 'Failed to load absensi_lokasi table: ' . $e->getMessage());
            }

            if (empty($locationRows)) {
                return $this->respond(false, 'Titik lokasi kantor belum dikonfigurasi oleh admin. Hubungi admin sekolah.');
            }

            $validDistance = null;
            foreach ($locationRows as $loc) {
                $locLat = (float) $loc->lat;
                $locLng = (float) $loc->lng;
                $radius = (float) (!empty($loc->radius_meter) ? $loc->radius_meter : ($setting->radius_meter ?? 100));
                $dist = distance_meter($lat, $lng, $locLat, $locLng);
                if ($dist <= $radius) {
                    $validDistance = $dist;
                    $lokasiId = $loc->id;
                    break;
                }
            }

            if ($validDistance === null) {
                return $this->respond(false, 'Di luar area lokasi absensi. Absen pulang di luar lokasi hanya diperbolehkan jika menggunakan Mode Dinas atau WFH.');
            }
        }

        $fotoPath = null;
        if (!empty($foto)) {
            $fotoPath = $this->saveAbsenPhoto($foto, (int) $userId, 'pulang');
        }

        if ($fotoPath === null && !$isWfh && $setting && (int) $setting->foto_wajib === 1) {
            return $this->respond(false, 'Foto wajib diunggah dan harus valid');
        }

        $checkoutResult = $this->storeCheckout(
            (int) $userId,
            $todayAttendance,
            $checkOutDate,
            $recordedTime,
            $jamPulangLimit,
            $isWfh,
            $isDinas,
            $lat,
            $lng,
            $lokasiId,
            $validDistance,
            $fotoPath,
            $accuracy,
            (int) $isMockLocation,
            $this->request->getPost('keterangan'),
            $setting,
            $isDinas ? 'Absen Pulang (Dinas)' : ($isWfh ? 'Absen Pulang (WFH)' : 'Absen Pulang')
        );

        if (!$checkoutResult['ok']) {
            return $this->respond(false, $checkoutResult['message']);
        }

        $statusPulang = $checkoutResult['status_pulang'];
        $pulangCepatMenit = $checkoutResult['pulang_cepat_menit'];
        $totalDenda = $checkoutResult['total_denda'];
        $existingDenda = $checkoutResult['existing_denda'];

        $msg = 'Absen Pulang berhasil';
        if ($isWfh) $msg = 'Absen Pulang WFH berhasil.';
        if ($timestampLocal)
            $msg .= ' (Sync Offline)';
        $msg .= $mockWarning;

        // Add Motivation if Early
        if (!$isWfh && ($statusPulang === 'EARLY' || $pulangCepatMenit > 0)) {
            $msg .= "\n\n" . $this->getRandomMotivation();
        }

        // Siapkan informasi panggilan & suara ucapan pulang (Text-to-Speech)
        $namaGuruLengkap = '';
        $sapaan = 'Bapak atau Ibu';
        $idGuru = $idGuru ?: (int) ($todayAttendance->id_guru ?? 0);
        if ($idGuru && $db->tableExists('master_guru')) {
            $guruRow = $db->table('master_guru')->select('nama_guru, jenis_kelamin')->where('id_guru', $idGuru)->get()->getRow();
            if ($guruRow) {
                $namaGuruLengkap = $guruRow->nama_guru ?? '';
                $jk = strtoupper(trim((string) ($guruRow->jenis_kelamin ?? '')));
                if ($jk === 'L' || strpos($jk, 'LAKI') !== false) {
                    $sapaan = 'Bapak';
                } elseif ($jk === 'P' || strpos($jk, 'PEREMPUAN') !== false) {
                    $sapaan = 'Ibu';
                }
            }
        }
        if (empty($namaGuruLengkap)) {
            $uRow = $db->table('users')->select('first_name, last_name')->where('id', $userId)->get()->getRow();
            if ($uRow) {
                $namaGuruLengkap = trim(($uRow->first_name ?? '') . ' ' . ($uRow->last_name ?? ''));
            }
        }
        $namaBersih = preg_replace('/,.*$/', '', $namaGuruLengkap);
        $namaBersih = preg_replace('/\b(Drs|Dra|Ir|Prof|Dr|H|Hj|Ust|Kh)\.?\b/i', '', $namaBersih);
        $namaBersih = trim(preg_replace('/\s+/', ' ', $namaBersih));

        // Ambil hanya nama depan saja untuk panggilan suara TTS
        $namaWords = array_values(array_filter(explode(' ', $namaBersih), static function ($w) {
            return strlen(rtrim($w, '.')) > 1;
        }));
        $namaDepan = $namaWords[0] ?? (explode(' ', $namaBersih)[0] ?? '');
        if ($namaDepan !== '') {
            $namaDepan = mb_convert_case($namaDepan, MB_CASE_TITLE, 'UTF-8');
        }
        if ($namaBersih !== '') {
            $namaBersih = mb_convert_case($namaBersih, MB_CASE_TITLE, 'UTF-8');
        }
        $panggilanLengkap = $namaDepan !== '' ? "$sapaan $namaDepan" : $sapaan;
        if (!$isWfh && ($statusPulang === 'EARLY' || $pulangCepatMenit > 0)) {
            $voiceText = "Perhatian $panggilanLengkap. Presensi pulang berhasil, Anda pulang " . (int) $pulangCepatMenit . " menit lebih awal. Hati-hati di jalan .";
        } else {
            $voiceText = "Terima kasih $panggilanLengkap. Presensi pulang berhasil. Hati-hati di jalan .";
        }

        $this->notifyEarlyCheckoutDenda(
            (int) $userId,
            (int) $idGuru,
            $isDinas,
            $isWfh,
            $statusPulang,
            (int) $pulangCepatMenit,
            (float) $totalDenda,
            (float) $existingDenda,
            $namaGuruLengkap
        );

        return $this->respond(true, $msg, [
            'status_pulang' => $statusPulang,
            'pulang_cepat_menit' => (int) $pulangCepatMenit,
            'nama_guru' => $namaGuruLengkap,
            'nama_bersih' => $namaBersih,
            'nama_depan' => $namaDepan,
            'sapaan' => $sapaan,
            'voice_text' => $voiceText,
        ]);
    } catch (\Throwable $e) {
        log_message('error', 'Check-out Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
        return $this->respond(false, 'Terjadi kesalahan sistem saat check-out — periksa log aplikasi.', null, null, 500);
    }
}

    /**
     * Notifikasi denda pulang awal (hanya porsi baru hari ini, best-effort).
     * Dipakai jalur check-out face maupun QR agar efek sampingnya sama.
     */
    private function notifyEarlyCheckoutDenda(
        int $userId,
        int $idGuru,
        bool $isDinas,
        bool $isWfh,
        string $statusPulang,
        int $pulangCepatMenit,
        float $totalDenda,
        float $existingDenda,
        string $namaGuru = ''
    ): void {
        if ($isDinas || $isWfh || $statusPulang !== 'EARLY') {
            return;
        }
        $dendaBaru = $totalDenda - $existingDenda;
        if ($dendaBaru <= 0) {
            return;
        }

        if ($namaGuru === '') {
            try {
                $db = \Config\Database::connect();
                if ($idGuru > 0) {
                    $row = $db->table('master_guru')->select('nama_guru')->where('id_guru', $idGuru)->get()->getRow();
                    $namaGuru = trim((string) ($row->nama_guru ?? ''));
                }
                if ($namaGuru === '') {
                    $row = $db->table('users')->select('first_name, last_name')->where('id', $userId)->get()->getRow();
                    $namaGuru = trim(((string) ($row->first_name ?? '')) . ' ' . ((string) ($row->last_name ?? '')));
                }
            } catch (\Throwable $e) {
                $namaGuru = '';
            }
        }

        try {
            (new \App\Services\NotificationService())->notifyDenda(
                $namaGuru !== '' ? $namaGuru : ('User ' . $userId),
                $dendaBaru,
                'Pulang Awal ' . $pulangCepatMenit . ' menit',
                $userId
            );
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi denda pulang awal gagal: ' . $e->getMessage());
        }
    }

    /**
     * Sesi jadwal hari ini yang menahan absen pulang.
     *
     * Aturan aslinya ada di WajibJurnalPulangService agar scanner kiosk menilai
     * hal yang sama; di sini hanya dibacakan dengan $setting milik jalur guru.
     *
     * @return array<int,object>
     */
    private function getSessionsBlockingCheckout(
        int $idGuru,
        string $checkOutDate,
        int $dayOfWeek,
        string $now,
        $setting,
        $db
    ): array {
        return \App\Services\WajibJurnalPulangService::sesiYangMenahan(
            $idGuru,
            $checkOutDate,
            $setting,
            $db,
            $now,
            $dayOfWeek
        );
    }

    /**
     * Pesan + payload sesi yang menghalangi absen pulang.
     *
     * Pada mode ketat, nama guru piket penanggung jawab validasi ikut disebut
     * agar guru tahu harus minta persetujuan ke siapa.
     *
     * @param array<int,object> $sesiKurang
     * @return array{message:string,data:array}
     */
    private function formatBlockingSessions(array $sesiKurang, $setting = null, string $tanggal = '', $db = null): array
    {
        $petugas = \App\Services\WajibJurnalPulangService::butuhValidasi($setting)
            ? \App\Services\WajibJurnalPulangService::petugasValidasi($tanggal, $db)
            : [];

        return \App\Services\WajibJurnalPulangService::pesanLengkap($sesiKurang, $petugas);
    }

    /**
     * Hitung status/poin/denda lalu tulis jam_pulang secara idempoten.
     * Dipakai jalur face dan QR agar aturan pulang tidak bercabang.
     *
     * @return array{ok:bool,message?:string,status_pulang?:string,pulang_cepat_menit?:int,total_denda?:float,existing_denda?:float}
     */
    private function storeCheckout(
        int $userId,
        object $todayAttendance,
        string $checkOutDate,
        string $recordedTime,
        string $jamPulangLimit,
        bool $isWfh,
        bool $isDinas,
        float $lat,
        float $lng,
        $lokasiId,
        $validDistance,
        ?string $fotoPath,
        float $accuracy,
        int $isMockLocation,
        ?string $keterangan,
        $setting,
        string $auditNote
    ): array {
        $db = \Config\Database::connect();

        $threshold = strtotime($checkOutDate . ' ' . $jamPulangLimit);
        $checkTime = strtotime($recordedTime);

        $earlyPoints = 0;
        $earlyDenda = 0;
        $rewardPointsOut = 0;
        $pulangAkhirMenit = 0;
        $pulangCepatMenit = 0;
        $keteranganDendaUpdate = null;

        if ($isDinas) {
            // Dinas: tidak dinilai cepat/lamban, status mengikuti check-in.
            $statusPulang = ($todayAttendance->status_masuk === 'DINAS_APPROVED') ? 'DINAS_APPROVED' : 'DINAS_OUT';
        } elseif ($checkTime < $threshold) {
            // Pulang cepat kini dihitung terhadap batas pulang yang berlaku
            // termasuk WFH (wfh_selesai). Sebelumnya WFH dipaksa
            // pulangCepatMenit=0 berapa pun waktunya sehingga kepulangan
            // sebelum jadwal tak pernah tercatat. Status 'WFH' dipertahankan
            // agar laporan tidak berubah, dan denda tetap hanya untuk
            // kepulangan cepat non-WFH (kebijakan lama tidak diubah).
            $pulangCepatMenit = (int) ceil(($threshold - $checkTime) / 60);
            if ($isWfh) {
                $statusPulang = 'WFH';
            } else {
                $statusPulang = 'EARLY';
                $calc = $this->calculateProgressivePenalty($pulangCepatMenit, $setting, 'Pulang Awal');
                $earlyPoints = -1 * $calc['points'];
                $earlyDenda = $calc['denda'];
                $keteranganDendaUpdate = $calc['keterangan'];
            }
        } else {
            $statusPulang = $isWfh ? 'WFH' : 'ONTIME';
            // Lembur dihitung 1 jam (60 menit) setelah jam batas kepulangan
            $diffMinutes = (int) floor(($checkTime - $threshold) / 60);
            if ($diffMinutes >= 60) {
                $pulangAkhirMenit = $diffMinutes - 60;
                $rewardPointsOut = $this->calculateProgressiveRewardPoints($pulangAkhirMenit, $setting);
            }
        }

        // Accumulate with existing check-in points
        $existingPoints = (int) ($todayAttendance->points ?? 0);
        $totalPoints = $isDinas ? $existingPoints : ($existingPoints + $earlyPoints + $rewardPointsOut);

        // Accumulate with existing denda (Dinas never gets charged denda)
        $existingDenda = (float) ($todayAttendance->denda ?? 0);
        $totalDenda = $isDinas ? 0 : ($existingDenda + $earlyDenda);

        // Update keterangan denda
        $existingKeterangan = $isDinas ? null : ($todayAttendance->keterangan_denda ?? null);
        if ($keteranganDendaUpdate && !$isDinas) {
            if ($existingKeterangan) {
                $existingKeterangan .= "; " . $keteranganDendaUpdate;
            } else {
                $existingKeterangan = $keteranganDendaUpdate;
            }
        }

        // Tegakkan cap denda bulanan pada total check-out (existing + baru).
        // Baris ini dikecualikan dari penjumlahan agar tidak dihitung ganda.
        // Seluruh baca-cap + tulis dibungkus transaksi agar dua check-out
        // paralel tidak sama-sama lolos cap.
        $db->transBegin();
        try {
            if (!$isDinas && $totalDenda > 0) {
                $penaltyService = new \App\Services\AttendancePenaltyService();
                $capped = $penaltyService->applyMonthlyCap($userId, $checkOutDate, (float) $totalDenda, $setting, (int) $todayAttendance->id, true);
                if ($capped['capped']) {
                    $totalDenda = $capped['total'];
                    $capNote = '(dibatasi cap bulanan Rp ' . number_format($totalDenda, 0, ',', '.') . ')';
                    $existingKeterangan = $existingKeterangan ? $existingKeterangan . ' ' . $capNote : $capNote;
                }
            }

            // Jangan hapus alasan/keterangan dari check-in (izin, dinas,
            // WFH) saat absen pulang tidak membawa keterangan baru.
            $ketLama = trim((string) ($todayAttendance->keterangan ?? ''));
            $ketBaru = trim((string) ($keterangan ?? ''));
            $keteranganFinal = ($ketBaru === '') ? ($ketLama !== '' ? $ketLama : null)
                : ($ketLama !== '' && strpos($ketLama, $ketBaru) === false ? $ketLama . ' | Pulang: ' . $ketBaru : $ketBaru);

            $updateData = [
                'jam_pulang' => $recordedTime,
                'lat_pulang' => $lat,
                'lng_pulang' => $lng,
                'lokasi_pulang_id' => $lokasiId,
                'jarak_pulang' => (int) round($validDistance === null ? 0 : $validDistance),
                'foto_pulang' => $fotoPath,
                'status_pulang' => $statusPulang,
                'pulang_cepat_menit' => $pulangCepatMenit,
                'points' => $totalPoints,
                'denda' => $totalDenda,
                'keterangan_denda' => $existingKeterangan,
                'keterangan' => $keteranganFinal,
            ];

            if ($db->fieldExists('pulang_akhir_menit', 'absensi_harian')) {
                $updateData['pulang_akhir_menit'] = $pulangAkhirMenit;
            }
            if ($db->fieldExists('gps_accuracy', 'absensi_harian')) {
                $updateData['gps_accuracy'] = $accuracy > 0 ? $accuracy : null;
            }
            if ($db->fieldExists('is_mock_location', 'absensi_harian')) {
                $updateData['is_mock_location'] = $isMockLocation;
            }

            // Idempoten + anti race: hanya update bila jam_pulang masih NULL.
            // Request paralel/retry kedua akan mendapat affectedRows=0 → "sudah absen pulang",
            // sehingga poin/denda tidak terakumulasi ganda.
            $checkoutBuilder = $db->table('absensi_harian');
            $checkoutBuilder->where('id', $todayAttendance->id);
            $checkoutBuilder->where('jam_pulang IS NULL', null, false);
            $checkoutBuilder->update($updateData);
            if ($db->affectedRows() === 0) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Anda sudah absen pulang'];
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            try {
                $db->transRollback();
            } catch (\Throwable $e2) {
            }
            throw $e;
        }

        // AUDIT LOG
        try {
            $auditModel = new AuditLogModel();
            $auditModel->log(
                'absensi_harian',
                $todayAttendance->id,
                'UPDATE',
                $userId,
                $userId,
                $todayAttendance,
                $updateData,
                $auditNote
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Failed to create audit log: ' . $ex->getMessage());
        }

        // POIN HISTORY: Sync checkout delta to poin_history ledger (skip WFH)
        if (!$isDinas && !$isWfh) {
            try {
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($todayAttendance->id);
            } catch (\Throwable $ex) {
                log_message('error', 'Sync poin check-out gagal: ' . $ex->getMessage());
            }
        }

        return [
            'ok' => true,
            'status_pulang' => $statusPulang,
            'pulang_cepat_menit' => (int) $pulangCepatMenit,
            'total_denda' => (float) $totalDenda,
            'existing_denda' => $existingDenda,
        ];
    }

    private function isValidCoordinate(float $lat, float $lng): bool
    {
        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }
        if ($lat < -90 || $lat > 90) {
            return false;
        }
        if ($lng < -180 || $lng > 180) {
            return false;
        }
        return true;
    }

    /**
     * Deteksi pola mock location.
     * Return: ['is_mock' => 0|1, 'strong' => bool]
     * - strong=true (accuracy 0/1/5, koordinat dev, pola .0000) → tolak langsung.
     * - accuracy >100m → lemah, hanya ditandai untuk verifikasi admin.
     */
    private function detectMockLocation(float $lat, float $lng, float $accuracy): array
    {
        // Sinyal kuat pola aplikasi Fake GPS
        if ($accuracy == 0 || $accuracy == 1 || $accuracy == 5) {
            return ['is_mock' => 1, 'strong' => true];
        }
        $latStr = (string) $lat;
        $lngStr = (string) $lng;
        if (preg_match('/\.0{4,}$/', $latStr) || preg_match('/\.0{4,}$/', $lngStr)) {
            return ['is_mock' => 1, 'strong' => true];
        }
        // Koordinat dev yang umum dipakai emulator/mock
        if (abs($lat - (-6.175110)) < 0.0001 && abs($lng - 106.865036) < 0.0001) {
            return ['is_mock' => 1, 'strong' => true];
        }
        // Akurasi rendah: mencurigakan tapi bisa terjadi di dalam gedung
        if ($accuracy > 100) {
            return ['is_mock' => 1, 'strong' => false];
        }
        return ['is_mock' => 0, 'strong' => false];
    }

    /**
     * True bila exception adalah pelanggaran UNIQUE KEY (MySQL 1062).
     */
    private function isDuplicateKeyError(\Throwable $e): bool
    {
        if ((int) $e->getCode() === 1062) {
            return true;
        }
        return stripos($e->getMessage(), 'uplicate') !== false;
    }

    /**
     * Verifikasi deskriptor wajah (128 float) terhadap profil tersimpan.
     * Return: ['ok' => bool, 'message' => string, 'data' => ?array]
     */
    private function verifyFaceDescriptor(string $faceDescriptor, $db, $setting): array
    {
        if (empty($faceDescriptor)) {
            return ['ok' => false, 'message' => 'Verifikasi wajah gagal: data wajah tidak ditemukan. Silakan coba lagi.'];
        }
        $descriptorArr = json_decode($faceDescriptor, true);
        if (!is_array($descriptorArr) || count($descriptorArr) !== 128) {
            return ['ok' => false, 'message' => 'Verifikasi wajah gagal: data wajah tidak valid.'];
        }
        $storedRow = $db->table('users')
            ->select('face_descriptor')
            ->where('id', session()->get('user_id'))
            ->get()->getRow();
        $storedDescriptor = $storedRow->face_descriptor ?? null;
        if (empty($storedDescriptor)) {
            return ['ok' => false, 'message' => 'Silakan daftarkan wajah terlebih dahulu di menu Profil sebelum absen.'];
        }
        $storedArr = json_decode($storedDescriptor, true);
        if (!is_array($storedArr) || count($storedArr) !== 128) {
            return ['ok' => false, 'message' => 'Data wajah profil rusak. Silakan daftarkan ulang di menu Profil.'];
        }
        $distance = $this->euclideanDistance($descriptorArr, $storedArr);
        $faceThreshold = isset($setting->face_threshold) && (float) $setting->face_threshold > 0 ? (float) $setting->face_threshold : 0.72;
        $matchScore = $this->calculateSimilarity($distance);
        if ($distance > $faceThreshold) {
            return [
                'ok' => false,
                'message' => 'Wajah tidak cocok dengan data profil (Tingkat kemiripan: ' . $matchScore . '%). Pastikan pencahayaan cukup dan wajah menghadap kamera.',
                'data' => [
                    'face_match_score' => $matchScore,
                    'face_distance' => round($distance, 3),
                    'face_match' => false,
                ],
            ];
        }
        return ['ok' => true, 'message' => 'Wajah cocok.'];
    }

    /**
     * Full-screen mobile page
     */
    /**
     * Kartu "Aktivitas Rekan" hanya butuh satu entri terbaru per guru.
     * Menghilangkan duplikat akibat guru absen dua kali sehari, izin ganda pada tanggal sama, atau join master_guru yang mengembalikan >1 baris,
     * lalu memastikan urutan "Terbaru" benar setelah event izin digabung.
     */
    private function unikkanKartuActivity(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            if (!is_object($row)) {
                continue;
            }
            $uid = (int) ($row->user_id ?? 0);
            if ($uid > 0 && isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $out[] = $row;
        }
        usort($out, static fn ($a, $b) => strcmp((string) ($b->updated_at ?? ''), (string) ($a->updated_at ?? '')));
        return array_slice($out, 0, 10);
    }

    public function mobile()
    {
        if (!$this->inGroup('guru'))
            return redirect()->to('auth');

        $db = null;
        try {
            $db = \Config\Database::connect();
            $this->patchAbsensiHarianTable();
            $this->patchAbsensiKoreksiTable();
        } catch (\Throwable $e) {
            $db = null;
        }

        $setting = $this->getDefaultSettings();
        try {
            if ($db && $db->tableExists('absensi_setting')) {
                $loadedSetting = $this->settingModel->first();
                if ($loadedSetting) {
                    $setting = $loadedSetting;
                }
            }
        } catch (\Throwable $e) {
        }

        $todayAttendance = null;
        try {
            if ($db && $db->tableExists('absensi_harian')) {
                $builder = $this->attendanceModel
                    ->select('absensi_harian.*')
                    ->where('absensi_harian.user_id', session()->get('user_id'))
                    ->where('absensi_harian.tgl', date('Y-m-d'));

                if ($db->tableExists('absensi_lokasi')) {
                    $builder
                        ->select('loc_in.nama_lokasi as nama_lokasi_masuk, loc_out.nama_lokasi as nama_lokasi_pulang')
                        ->join('absensi_lokasi loc_in', 'loc_in.id = absensi_harian.lokasi_masuk_id', 'left')
                        ->join('absensi_lokasi loc_out', 'loc_out.id = absensi_harian.lokasi_pulang_id', 'left');
                }

                $todayAttendance = $builder->first();

                // Auto-normalize lembur (pulang_akhir_menit) jika kurang dari 1 jam (60 menit)
                if ($todayAttendance && !empty($todayAttendance->jam_pulang) && !empty($todayAttendance->pulang_akhir_menit)) {
                    $jadwalPulang = $todayAttendance->jadwal_pulang ?? ($setting->jam_pulang ?? '15:00:00');
                    $dayOfWeek = date('N', strtotime($todayAttendance->tgl));
                    if ($db->tableExists('absensi_jadwal_kerja')) {
                        $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
                        if ($jadwal && !empty($jadwal->jam_pulang)) $jadwalPulang = $jadwal->jam_pulang;
                    }
                    $tsThreshold = strtotime($todayAttendance->tgl . ' ' . $jadwalPulang);
                    $tsPulang = strtotime($todayAttendance->jam_pulang);
                    if ($tsPulang > $tsThreshold) {
                        $diffMin = (int) floor(($tsPulang - $tsThreshold) / 60);
                        $correctLembur = ($diffMin >= 60) ? ($diffMin - 60) : 0;
                        if ((int)$todayAttendance->pulang_akhir_menit !== $correctLembur) {
                            $todayAttendance->pulang_akhir_menit = $correctLembur;
                            $db->table('absensi_harian')->where('id', $todayAttendance->id)->update(['pulang_akhir_menit' => $correctLembur]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $todayAttendance = null;
        }

        $todayPermit = null;
        try {
            if ($db && $db->tableExists('absensi_izin')) {
                $todayDate = date('Y-m-d');
                $todayPermit = $db->table('absensi_izin')
                    ->where('user_id', session()->get('user_id'))
                    ->where('status', 'approved')
                    ->where("'$todayDate' BETWEEN tgl_mulai AND tgl_selesai")
                    ->orderBy('id', 'DESC')
                    ->get()->getRow();

                if (!$todayPermit) {
                    $todayPermit = $db->table('absensi_izin')
                        ->where('user_id', session()->get('user_id'))
                        ->whereIn('status', \App\Models\LeaveModel::STATUS_PENDING)
                        ->where("'$todayDate' BETWEEN tgl_mulai AND tgl_selesai")
                        ->orderBy('id', 'DESC')
                        ->get()->getRow();
                }
            }
        } catch (\Throwable $e) {
            $todayPermit = null;
        }

        try {
            $user = $this->authModel->user(session()->get('user_id'));
        } catch (\Throwable $e) {
            $user = null;
        }
        if (!$user) {
            return redirect()->to('auth')->with('error', 'Sesi tidak valid');
        }

        if ($user) {
            $this->resolveIdGuru((int)$user->id);
            if ($db && $db->tableExists('master_guru')) {
                $guruRow = $db->table('master_guru')->where('id_user', $user->id)->get()->getRow();
                if ($guruRow) {
                    if (!empty($guruRow->nama_guru)) $user->nama_guru = $guruRow->nama_guru;
                    if (!empty($guruRow->foto)) $user->foto = $guruRow->foto;
                    $user->nip = $guruRow->nip ?? '';
                    $user->kode_guru = $guruRow->kode_guru ?? '';
                    $user->nuptk = $guruRow->nuptk ?? '';
                    $user->id_guru = (int) ($guruRow->id_guru ?? 0);
                    $user->jenis_ptk = $guruRow->jenis_ptk ?? '';
                    $user->tgs_tambahan = $guruRow->tgs_tambahan ?? '';
                }
            }
        }

        // Check if face is registered
        $faceRegistered = !empty($user->face_descriptor);

        // Dynamic Greeting
        $holidayReason = null;
        try {
            $holidayReason = $this->checkIsHoliday(date('Y-m-d'));
        } catch (\Throwable $e) {
            $holidayReason = null;
        }
        $greetingMsg = "Selamat Beraktivitas!";

        if ($holidayReason) {
            if (strpos($holidayReason, 'mingguan') !== false) {
                $greetingMsg = "Selamat berakhir pekan! Nikmati waktu istirahat Anda.";
            } else {
                $cleanReason = str_replace('Hari ini libur: ', '', $holidayReason);
                $greetingMsg = "Selamat berlibur! " . $cleanReason;
            }
        }

        // Data Absensi Guru Lain Hari Ini (Comparison)
        $others = [];
        try {
            if ($db && $db->tableExists('absensi_harian')) {
                $hasIzinTable = $db->tableExists('absensi_izin');
                $selectFields = 'ah.*, users.first_name, users.last_name, users.foto as user_foto, users.username, mg.nama_guru, mg.foto as guru_foto';
                
                if ($hasIzinTable) {
                    if ($db->fieldExists('keterangan', 'absensi_izin')) {
                        $selectFields .= ', ai.keterangan as izin_keterangan';
                    }
                    if ($db->fieldExists('alasan', 'absensi_izin')) {
                        $selectFields .= ', ai.alasan as izin_alasan';
                    }
                    if ($db->fieldExists('jenis', 'absensi_izin')) {
                        $selectFields .= ', ai.jenis as izin_jenis';
                    }

                    $builder = $db->table('absensi_harian ah')
                        ->select($selectFields)
                        ->join('users', 'users.id = ah.user_id')
                        ->join('master_guru mg', 'mg.id_user = users.id OR mg.id_guru = ah.id_guru', 'left')
                        ->join('absensi_izin ai', "ai.user_id = ah.user_id AND ai.status = 'approved' AND '" . date('Y-m-d') . "' BETWEEN ai.tgl_mulai AND ai.tgl_selesai", 'left')
                        ->where('ah.tgl', date('Y-m-d'))
                        ->where('ah.user_id !=', $user->id)
                        ->orderBy('ah.updated_at', 'DESC')
                        ->limit(10);
                    $others = $builder->get()->getResultObject();
                    $others = $this->unikkanKartuActivity($others);

                    // Juga sertakan guru yang izin/sakit/cuti disetujui hari ini namun belum tercatat di absensi_harian
                    $existingUserIds = array_filter(array_map(fn($item) => (int)($item->user_id ?? 0), $others));
                    $existingUserIds[] = (int)$user->id;

                    $leaveSelect = 'ai.user_id, ai.id_guru, ai.jenis as izin_jenis, ai.updated_at, users.first_name, users.last_name, users.foto as user_foto, users.username, mg.nama_guru, mg.foto as guru_foto';
                    if ($db->fieldExists('keterangan', 'absensi_izin')) {
                        $leaveSelect .= ', ai.keterangan as izin_keterangan';
                    }
                    if ($db->fieldExists('alasan', 'absensi_izin')) {
                        $leaveSelect .= ', ai.alasan as izin_alasan';
                    }

                    $leavesTodayBuilder = $db->table('absensi_izin ai')
                        ->select($leaveSelect)
                        ->join('users', 'users.id = ai.user_id')
                        ->join('master_guru mg', 'mg.id_user = users.id OR mg.id_guru = ai.id_guru', 'left')
                        ->where('ai.status', 'approved')
                        ->where("'" . date('Y-m-d') . "' BETWEEN ai.tgl_mulai AND ai.tgl_selesai");

                    if (!empty($existingUserIds)) {
                        $leavesTodayBuilder->whereNotIn('ai.user_id', $existingUserIds);
                    }

                    $leavesToday = $leavesTodayBuilder->limit(5)->get()->getResultObject();
                    foreach ($leavesToday as $l) {
                        $jUpper = strtoupper($l->izin_jenis ?? 'IZIN');
                        if (in_array($jUpper, ['IJIN', 'IZIN'])) $jUpper = 'IJIN';
                        $l->status_masuk = $jUpper;
                        $l->status_pulang = $jUpper;
                        $l->keterangan = $l->izin_keterangan ?? $l->izin_alasan ?? '';
                        $l->jam_masuk = null;
                        $l->jam_pulang = null;
                        $others[] = $l;
                    }

                    // Gabungan aktivitas + izin disatukan lagi: satu kartu per
                    // guru, entri terbaru, maksimal 10.
                    $others = $this->unikkanKartuActivity($others);
                } else {
                    $others = $this->attendanceModel
                        ->select('absensi_harian.*, users.first_name, users.last_name, users.foto as user_foto, users.username, mg.nama_guru, mg.foto as guru_foto')
                        ->join('users', 'users.id = absensi_harian.user_id')
                        ->join('master_guru mg', 'mg.id_user = users.id OR mg.id_guru = absensi_harian.id_guru', 'left')
                        ->where('absensi_harian.tgl', date('Y-m-d'))
                        ->where('absensi_harian.user_id !=', $user->id)
                        ->orderBy('absensi_harian.updated_at', 'DESC')
                        ->limit(10)
                        ->findAll();
                    $others = $this->unikkanKartuActivity($others);
                }
            }
        } catch (\Throwable $e) {
            $others = [];
        }

        $disciplineRanking = [];
        $lazyRanking = [];
        try {
            if ($db && $db->tableExists('absensi_harian') && $db->tableExists('users') && $db->tableExists('users_groups')) {
                $monthPrefix = date('Y-m');
                $hasTelatMenit = $db->fieldExists('telat_menit', 'absensi_harian');
                $hasPulangCepat = $db->fieldExists('pulang_cepat_menit', 'absensi_harian');
                $hasDatangAwal = $db->fieldExists('datang_awal_menit', 'absensi_harian');
                $hasPulangAkhir = $db->fieldExists('pulang_akhir_menit', 'absensi_harian');

                // Rumus skor kanonik ada di AttendancePolicy supaya angka di
                // presensi, ranking, dan poin_history guru tidak berbeda lagi.
                $scoreExpr = \App\Services\AttendancePolicy::sqlExprSkorHarian('a', [
                    'telat_menit'        => $hasTelatMenit,
                    'pulang_cepat_menit' => $hasPulangCepat,
                    'datang_awal_menit'  => $hasDatangAwal,
                    'pulang_akhir_menit' => $hasPulangAkhir,
                ]);

                $builder = $db->table('absensi_harian a')
                    ->select("a.user_id, u.first_name, u.last_name, u.username, u.foto as user_foto, mg.nama_guru, mg.foto as guru_foto")
                    ->select(\App\Services\AttendancePolicy::sqlTermHadir('a') . ' as total_hadir', false)
                    ->select("SUM(CASE WHEN a.status_masuk = 'ONTIME' OR a.status_masuk LIKE 'DINAS%' THEN 1 ELSE 0 END) as ontime", false)
                    ->select("SUM(CASE WHEN a.status_masuk = 'LATE' AND a.status_masuk NOT LIKE 'DINAS%' THEN 1 ELSE 0 END) as late", false)
                    ->select($hasTelatMenit ? "COALESCE(SUM(CASE WHEN a.status_masuk LIKE 'DINAS%' THEN 0 ELSE a.telat_menit END), 0) as total_telat_menit" : "0 as total_telat_menit", false)
                    ->select($hasDatangAwal ? "COALESCE(SUM(a.datang_awal_menit), 0) as total_datang_awal" : "0 as total_datang_awal", false)
                    ->select($hasPulangAkhir ? "COALESCE(SUM(a.pulang_akhir_menit), 0) as total_pulang_akhir" : "0 as total_pulang_akhir", false)
                    ->select("SUM(COALESCE(CASE WHEN a.status_masuk LIKE 'DINAS%' OR a.status_pulang LIKE 'DINAS%' THEN 0 ELSE a.denda END, 0)) as total_denda", false)
                    ->select("$scoreExpr as score", false)
                    ->join('users u', 'u.id = a.user_id')
                    ->join('users_groups ug', 'ug.user_id = u.id')
                    ->join('master_guru mg', 'mg.id_user = u.id', 'left')
                    ->where('ug.group_id', 2)
                    // Kecualikan akun yang juga terdaftar sebagai siswa (cegah siswa nyasar ke ranking guru)
                    ->where('u.id NOT IN (SELECT user_id FROM users_groups WHERE group_id = 3)', null, false)
                    ->where('u.active', 1)
                    ->like('a.tgl', $monthPrefix, 'after')
                    ->groupBy('a.user_id, u.first_name, u.last_name, u.username, u.foto, mg.nama_guru, mg.foto');

                // 3 Guru Paling Disiplin (Top Skor)
                $disciplineRanking = (clone $builder)->orderBy('score', 'DESC', false)->limit(3)->get()->getResult();

                // Kriteria Guru Perlu Perhatian: diutamakan jumlah alpha terbanyak, lalu jumlah terlambat terbanyak (ambil 3 teratas)
                try {
                    $candidates = $db->table('users u')
                        ->select("u.id as user_id, u.first_name, u.last_name, u.username, u.foto as user_foto, mg.nama_guru, mg.foto as guru_foto")
                        ->select(\App\Services\AttendancePolicy::sqlTermHadir('a') . ' as total_hadir', false)
                        ->select("SUM(CASE WHEN a.status_masuk = 'ONTIME' OR a.status_masuk LIKE 'DINAS%' THEN 1 ELSE 0 END) as ontime", false)
                        ->select("SUM(CASE WHEN a.status_masuk = 'LATE' AND a.status_masuk NOT LIKE 'DINAS%' THEN 1 ELSE 0 END) as late", false)
                        ->select($hasTelatMenit ? "COALESCE(SUM(CASE WHEN a.status_masuk LIKE 'DINAS%' THEN 0 ELSE a.telat_menit END), 0) as total_telat_menit" : "0 as total_telat_menit", false)
                        ->select("SUM(COALESCE(CASE WHEN a.status_masuk LIKE 'DINAS%' OR a.status_pulang LIKE 'DINAS%' THEN 0 ELSE a.denda END, 0)) as total_denda", false)
                        ->select("COALESCE($scoreExpr, 0) as score", false)
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->join('master_guru mg', 'mg.id_user = u.id', 'left')
                        ->join('absensi_harian a', "a.user_id = u.id AND a.tgl LIKE '{$monthPrefix}%'", 'left')
                        ->where('ug.group_id', 2)
                        // Kecualikan akun yang juga terdaftar sebagai siswa (cegah siswa nyasar ke ranking guru)
                        ->where('u.id NOT IN (SELECT user_id FROM users_groups WHERE group_id = 3)', null, false)
                        ->where('u.active', 1)
                        ->groupBy('u.id, u.first_name, u.last_name, u.username, u.foto, mg.nama_guru, mg.foto')
                        ->get()->getResult();

                    // Hitung alpha per kandidat menggunakan AttendancePenaltyService
                    $candIds = array_values(array_filter(array_map(static fn($c) => (int) ($c->user_id ?? 0), $candidates)));
                    $candAlphaMap = $this->getAlphaCountMapForUsersMonth($candIds, $monthPrefix, $db, $setting);
                    foreach ($candidates as $c) {
                        $alpha = (int) ($candAlphaMap[(int) ($c->user_id ?? 0)] ?? 0);
                        $c->alpha = $alpha;
                        $lateCount = (int) ($c->late ?? 0);
                        $totalMenit = (float) ($c->total_telat_menit ?? 0);
                        $c->rata_telat = $lateCount > 0 ? ($totalMenit / $lateCount) : 0;
                    }

                    // Kandidat yang memiliki alpha > 0 atau terlambat > 0
                    $filtered = array_values(array_filter($candidates, static function ($c) {
                        return ((int) ($c->alpha ?? 0) > 0) || ((int) ($c->late ?? 0) > 0);
                    }));

                    // Urutan: 1. Jumlah alpha terbanyak, 2. Jumlah terlambat terbanyak, 3. Total menit telat terbanyak, 4. Total denda terbanyak
                    usort($filtered, static function ($a, $b) {
                        $cmpAlpha = ((int) ($b->alpha ?? 0)) <=> ((int) ($a->alpha ?? 0));
                        if ($cmpAlpha !== 0) {
                            return $cmpAlpha;
                        }

                        $cmpLate = ((int) ($b->late ?? 0)) <=> ((int) ($a->late ?? 0));
                        if ($cmpLate !== 0) {
                            return $cmpLate;
                        }

                        $cmpMenit = ((float) ($b->total_telat_menit ?? 0)) <=> ((float) ($a->total_telat_menit ?? 0));
                        if ($cmpMenit !== 0) {
                            return $cmpMenit;
                        }

                        return ((float) ($b->total_denda ?? 0)) <=> ((float) ($a->total_denda ?? 0));
                    });

                    $lazyRanking = array_slice($filtered, 0, 3);
                } catch (\Throwable $ex) {
                    $lazyRanking = [];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Ranking Homepage] Error: ' . $e->getMessage());
            $disciplineRanking = [];
            $lazyRanking = [];
        }

        try {
            $alphaFine = (int) ($setting->denda_alpha_per_hari ?? 0);
            $allRankingUsers = array_merge($disciplineRanking, $lazyRanking);
            if ($db && !empty($allRankingUsers)) {
                $userIds = array_map(static fn($r) => (int) ($r->user_id ?? 0), $allRankingUsers);
                $userIds = array_values(array_filter(array_unique($userIds)));
                $alphaMap = $this->getAlphaCountMapForUsersMonth($userIds, date('Y-m'), $db, $setting);

                $recordedAlphaDenda = [];
                $alphaRows = $db->table('absensi_harian')
                    ->select('user_id, SUM(COALESCE(denda, 0)) as total_alpha_denda')
                    ->whereIn('user_id', $userIds)
                    ->like('tgl', date('Y-m'), 'after')
                    ->whereIn('status_masuk', ['ALPHA', 'ALPA'])
                    ->groupBy('user_id')
                    ->get()->getResult();
                foreach ($alphaRows as $ar) {
                    $recordedAlphaDenda[(int) $ar->user_id] = (float) $ar->total_alpha_denda;
                }

                foreach ($allRankingUsers as $r) {
                    $alphaCount = (int) ($alphaMap[(int) ($r->user_id ?? 0)] ?? 0);
                    $r->alpha = $alphaCount;
                    $r->alpha_denda = $alphaCount * $alphaFine;
                    if ($alphaFine > 0) {
                        $unrecorded = max(0.0, ($alphaCount * $alphaFine) - ($recordedAlphaDenda[(int) ($r->user_id ?? 0)] ?? 0.0));
                        $r->total_denda = (float) ($r->total_denda ?? 0) + $unrecorded;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // Avatar leaderboard home tab memakai resolver yang sama dengan API
        // /presensi/ranking — keduanya kini dijamin mengarah ke file yang ada
        // atau avatar inisial lokal.
        foreach ([$disciplineRanking, $lazyRanking] as $barisRanking) {
            foreach ($barisRanking as $r) {
                $r->avatar = \App\Services\AvatarService::untukBaris($r);
            }
        }

        // Fetch Locations: Strong Fallback
        $locations = [];
        try {
            if ($db && $db->tableExists('absensi_lokasi')) {
                $locationModel = new \App\Models\AttendanceLocationModel();
                $locations = $locationModel->findAll();

                if (empty($locations)) {
                    $locations = $db->table('absensi_lokasi')->get()->getResult();
                }
            }
        } catch (\Throwable $e) {
            $locations = [];
        }

        // Jadwal Hari Ini — hanya pasangan kelas–mapel yang diampu guru ini.
        $jadwal = [];
        try {
            if ($db) {
                $guru = $db->table('master_guru')->where('id_user', $user->id)->get()->getRow();
                $tp = $db->table('master_tp')->where('active', 1)->get()->getRow();
                $smt = $db->table('master_smt')->where('active', 1)->get()->getRow();
                if ($guru && $tp && $smt) {
                    $jadwalRows = (new \App\Models\ElearningModel())->getJadwalKbmGuruHariIni(
                        (int) $guru->id_guru,
                        (int) date('N'),
                        (int) $tp->id_tp,
                        (int) $smt->id_smt
                    );

                    // Status jurnal per slot untuk menandai sesi yang sudah
                    // diisi, sehingga guru tahu sisa sesi sebelum absen pulang.
                    // Ikut aturan gerbang pulang: pada mode ketat sesi yang
                    // jurnalnya belum disetujui piket ditandai menunggu.
                    $statusJurnal = [];
                    try {
                        foreach ((new \App\Models\JurnalGuruModel())->getStatusJurnalHarian(
                            (int) $guru->id_guru,
                            (int) $tp->id_tp,
                            (int) $smt->id_smt,
                            (int) date('N'),
                            date('Y-m-d'),
                            \App\Services\WajibJurnalPulangService::butuhValidasi($setting)
                        ) as $row) {
                            $statusJurnal[(int) $row['slot']->id_jadwal] = $row;
                        }
                    } catch (\Throwable $e) {
                        $statusJurnal = [];
                    }

                    foreach ($jadwalRows as $j) {
                        $status = $statusJurnal[(int) $j->id_jadwal] ?? null;
                        $jadwal[] = [
                            'id_jadwal' => (int) $j->id_jadwal,
                            'id_kelas' => (int) $j->id_kelas,
                            'id_mapel' => (int) $j->id_mapel,
                            'jam_ke' => (string) ($j->jam_ke ?? ''),
                            'nama_kelas' => $j->nama_kelas,
                            'nama_mapel' => $j->nama_mapel,
                            'dari' => substr((string) $j->dari, 0, 5),
                            'sampai' => substr((string) $j->sampai, 0, 5),
                            'sudah_dijurnal' => (bool) ($status['sudah_dijurnal'] ?? false),
                            'menunggu_validasi' => (bool) ($status['menunggu_validasi'] ?? false),
                            'validasi_status' => (string) ($status['validasi_status'] ?? ''),
                            'id_jurnal' => (int) ($status['id_jurnal'] ?? 0),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $jadwal = [];
        }

        // Upcoming Events
        $upcomingEvents = [];
        try {
            if ($db && $db->tableExists('kalender_akademik')) {
                $upcomingEvents = $db->table('kalender_akademik')
                    ->where('tgl_mulai >=', date('Y-m-d'))
                    ->orderBy('tgl_mulai', 'ASC')
                    ->limit(3)
                    ->get()->getResult();
            }
        } catch (\Throwable $e) {
            $upcomingEvents = [];
        }

        // Jam kerja efektif hari ini (shift → jadwal harian → setting) untuk card Jam Kerja.
        $jamKerja = [
            'masuk' => substr((string) ($setting->jam_masuk ?? '07:00:00'), 0, 5),
            'pulang' => substr((string) ($setting->jam_pulang ?? '15:00:00'), 0, 5),
        ];
        try {
            if ($db && $user) {
                $dayIso = (int) date('N');
                $jadwalHarian = $db->tableExists('absensi_jadwal_kerja')
                    ? $db->table('absensi_jadwal_kerja')->where('hari', $dayIso)->get()->getRow()
                    : null;
                $shiftModel = new \App\Models\ShiftModel();
                $schedHariIni = $shiftModel->getScheduleForUser((int) $user->id, $dayIso, $jadwalHarian, $setting);
                if (!empty($schedHariIni['jam_masuk'])) {
                    $jamKerja['masuk'] = substr((string) $schedHariIni['jam_masuk'], 0, 5);
                }
                if (!empty($schedHariIni['jam_pulang'])) {
                    $jamKerja['pulang'] = substr((string) $schedHariIni['jam_pulang'], 0, 5);
                }
            }
        } catch (\Throwable $e) {}

        return view('members/guru/absen_mobile', [
            'setting' => $setting,
            'attendance' => $todayAttendance,
            'todayPermit' => $todayPermit,
            'user' => $user,
            'greeting' => $greetingMsg,
            'others' => $others,
            'disciplineRanking' => $disciplineRanking,
            'lazyRanking' => $lazyRanking,
            'is_holiday' => !empty($holidayReason),
            'holiday_reason' => $holidayReason,
            'locations' => $locations,
            'jadwal' => $jadwal,
            'jamKerja' => $jamKerja,
            'upcomingEvents' => $upcomingEvents,
            'face_registered' => $faceRegistered,
        ]);
    }

    public function logPage()
    {
        if (!$this->inGroup('guru'))
            return redirect()->to('auth');
        return view('members/guru/absen_log');
    }

    public function logPdf()
    {
        if (!$this->inGroup('guru'))
            return redirect()->to('auth');

        $userId = session()->get('user_id');
        $user = $this->authModel->user($userId);
        
        $month = $this->request->getGet('month') ?? date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $data = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('tgl >=', $start)
            ->where('tgl <=', $end)
            ->orderBy('tgl', 'ASC')
            ->findAll();

        $dompdf = new Dompdf();
        $html = view('members/guru/pdf_log', [
            'user' => $user,
            'data' => $data,
            'month' => $month
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Rekap_Absensi_" . ($user->username ?? 'Guru') . "_" . $month . ".pdf", ["Attachment" => true]);
        exit;
    }

    /**
     * Rekap absensi bulanan seluruh guru (PDF) dari halaman home mobile.
     * Render-nya memakai RekapPdfService yang sama dengan kiriman cron
     * agar format dokumen konsisten.
     */
    public function rekapGuruPdf()
    {
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth');
        }

        $bulan = (string) ($this->request->getGet('bulan') ?? date('m'));
        $tahun = (string) ($this->request->getGet('tahun') ?? date('Y'));
        $bulan = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $bulan)) {
            $bulan = date('m');
        }
        if (!preg_match('/^\d{4}$/', $tahun)) {
            $tahun = date('Y');
        }

        try {
            $rekap = (new \App\Services\RekapPdfService())->buildRekapBulananPdf($bulan, $tahun);
        } catch (\Throwable $e) {
            log_message('error', '[rekapGuruPdf] ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setBody('Gagal membuat PDF rekap guru. Silakan coba lagi.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $rekap['filename'] . '"')
            ->setBody($rekap['pdf']);
    }

    public function notifPage()
    {
        if (!$this->inGroup('guru'))
            return redirect()->to('auth');

        $showKepsekMenu = false;
        try {
            $showKepsekMenu = $this->isGuruKepalaSekolah();
        } catch (\Throwable $e) {
            $showKepsekMenu = false;
        }

        $kepsekPendingDinas = 0;
        $kepsekPendingIzin = 0;
        if ($showKepsekMenu) {
            try {
                $db = \Config\Database::connect();
                if ($db && $db->tableExists('absensi_harian')) {
                    $kepsekPendingDinas = (int) $db->table('absensi_harian')->where('status_masuk', 'DINAS_PENDING')->countAllResults();
                }
                if ($db && $db->tableExists('absensi_izin')) {
                    $kepsekPendingIzin = (int) $db->table('absensi_izin')->whereIn('status', \App\Models\LeaveModel::STATUS_PENDING)->countAllResults();
                }
            } catch (\Throwable $e) {
                $kepsekPendingDinas = 0;
                $kepsekPendingIzin = 0;
            }
        }

        return view('members/guru/absen_notif', [
            'showKepsekMenu' => $showKepsekMenu,
            'kepsekPendingDinas' => $kepsekPendingDinas,
            'kepsekPendingIzin' => $kepsekPendingIzin
        ]);
    }

    public function kepsekDinasPage()
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $guard;
        }

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        $pending = [];
        try {
            if ($db && $db->tableExists('absensi_harian')) {
                $builder = $db->table('absensi_harian a')
                    ->select('a.*')
                    ->orderBy('a.created_at', 'DESC')
                    ->where('a.status_masuk', 'DINAS_PENDING');

                if ($db->tableExists('users')) {
                    $builder->select('u.first_name, u.last_name, u.username, u.foto')->join('users u', 'u.id = a.user_id', 'left');
                }
                if ($db->tableExists('master_guru')) {
                    $builder->select('mg.nama_guru, mg.nip')->join('master_guru mg', 'mg.id_user = a.user_id', 'left');
                }

                $pending = $builder->get()->getResult();
            }
        } catch (\Throwable $e) {
            $pending = [];
        }

        return view('members/guru/kepsek/verifikasi_dinas', [
            'pending' => $pending
        ]);
    }

    public function kepsekApproveDinas($id)
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $attendance = $this->attendanceModel->find($id);
        if (!$attendance) {
            return $this->respond(false, 'Data tidak ditemukan');
        }
        if (($attendance->status_masuk ?? '') !== 'DINAS_PENDING') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        $catatan = (string) ($this->request->getPost('catatan') ?? '');

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        if ($this->adalahPemohon($attendance)) {
            return $this->respond(false, 'Anda tidak dapat menyetujui pengajuan Anda sendiri', null, null, 403);
        }

        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $setting = $absenSettingModel->first();
        $pointHadir = (int) ($setting->point_hadir ?? 10);

        $data = [
            'status_masuk' => 'DINAS_APPROVED',
            'status_pulang' => 'DINAS_APPROVED',
            'points' => $pointHadir,
            'denda' => 0,
            'keterangan_denda' => null,
            'telat_menit' => 0,
            'pulang_cepat_menit' => 0,
        ];
        if ($db && $db->tableExists('absensi_harian')) {
            if ($db->fieldExists('verifikasi_catatan', 'absensi_harian')) {
                $data['verifikasi_catatan'] = $catatan;
            }
            if ($db->fieldExists('verified_by', 'absensi_harian')) {
                $data['verified_by'] = (int) (session()->get('user_id') ?? 0);
            }
            if ($db->fieldExists('verified_at', 'absensi_harian')) {
                $data['verified_at'] = date('Y-m-d H:i:s');
            }
        }

        // Atomik seperti Admin\PresensiAdmin::approveDinas: hanya DINAS_PENDING
        // yang bisa berpindah, cegah double-approve kepsek vs admin paralel.
        $db->table('absensi_harian')
            ->where('id', $id)->where('status_masuk', 'DINAS_PENDING')
            ->update($data);
        if ($db->affectedRows() !== 1) {
            return $this->respond(false, 'Status sudah diproses admin lain. Muat ulang halaman.');
        }
        (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);

        // Jalur admin mencatat keputusan dinas di audit log; jalur kepsek
        // sebelumnya diam total, padahal yang menyetujui di sini kepsek.
        try {
            (new AuditLogModel())->log('absensi_harian', (int) $id, 'KEPSEK_APPROVE_DINAS',
                (int) ($attendance->user_id ?? 0), (int) (session()->get('user_id') ?? 0),
                ['status_masuk' => (string) ($attendance->status_masuk ?? '')],
                ['status_masuk' => 'DINAS_APPROVED', 'catatan' => (string) $catatan],
                'Dinas disetujui kepsek');
        } catch (\Throwable $ex) {
            log_message('error', 'Audit kepsekApproveDinas gagal: ' . $ex->getMessage());
        }

        // Evaluasi dan sinkronisasi pembatalan surat teguran jika hari alpha gugur
        try {
            $tglDinas = $attendance->tgl ?? date('Y-m-d');
            $blnDinas = (int) date('n', strtotime($tglDinas));
            $thnDinas = (int) date('Y', strtotime($tglDinas));
            $penaltySvc = new \App\Services\AttendancePenaltyService();
            $penaltySvc->syncSuratTeguranOnAttendanceChange((int) ($attendance->user_id ?? 0), $blnDinas, $thnDinas, $setting);
        } catch (\Throwable $ex) {}

        // Kabari guru + grup (best-effort, setelah penyimpanan selesai).
        try {
            $dinasUserId = (int) ($attendance->user_id ?? 0);
            $guruRow = $db
                ? $db->table('master_guru')->select('nama_guru')->where('id_user', $dinasUserId)->get()->getRow()
                : null;
            (new \App\Services\NotificationService())->notifyDinasApproved(
                $guruRow->nama_guru ?? 'Guru',
                (string) ($attendance->tgl ?? date('Y-m-d')),
                $dinasUserId,
                $catatan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi kepsek approve dinas gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Dinas disetujui');
    }

    public function kepsekRejectDinas($id)
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $attendance = $this->attendanceModel->find($id);
        if (!$attendance) {
            return $this->respond(false, 'Data tidak ditemukan');
        }
        if (($attendance->status_masuk ?? '') !== 'DINAS_PENDING') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        $catatan = (string) ($this->request->getPost('catatan') ?? '');

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        if ($this->adalahPemohon($attendance)) {
            return $this->respond(false, 'Anda tidak dapat menolak pengajuan Anda sendiri', null, null, 403);
        }

        $data = [
            'status_masuk' => 'DINAS_REJECTED',
            'status_pulang' => 'DINAS_REJECTED',
            'points' => 0,
            'denda' => 0,
            'keterangan_denda' => null,
            'telat_menit' => 0,
            'pulang_cepat_menit' => 0,
        ];
        if ($db && $db->tableExists('absensi_harian')) {
            if ($db->fieldExists('verifikasi_catatan', 'absensi_harian')) {
                $data['verifikasi_catatan'] = $catatan;
            }
            if ($db->fieldExists('verified_by', 'absensi_harian')) {
                $data['verified_by'] = (int) (session()->get('user_id') ?? 0);
            }
            if ($db->fieldExists('verified_at', 'absensi_harian')) {
                $data['verified_at'] = date('Y-m-d H:i:s');
            }
        }

        // Atomik + reset ledger seperti Admin::rejectDinas: klaim palsu
        // tidak boleh sisakan poin/denda, dan refresh ledger poin.
        $db->table('absensi_harian')
            ->where('id', $id)->where('status_masuk', 'DINAS_PENDING')
            ->update($data);
        if ($db->affectedRows() !== 1) {
            return $this->respond(false, 'Status sudah diproses admin lain. Muat ulang halaman.');
        }
        (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);

        try {
            (new AuditLogModel())->log('absensi_harian', (int) $id, 'KEPSEK_REJECT_DINAS',
                (int) ($attendance->user_id ?? 0), (int) (session()->get('user_id') ?? 0),
                ['status_masuk' => (string) ($attendance->status_masuk ?? '')],
                ['status_masuk' => 'DINAS_REJECTED', 'catatan' => (string) $catatan],
                'Dinas ditolak kepsek');
        } catch (\Throwable $ex) {
            log_message('error', 'Audit kepsekRejectDinas gagal: ' . $ex->getMessage());
        }

        // Kabari guru + grup (best-effort). Sama seperti versi admin, alasan
        // penolakan ikut dikirim agar guru tahu dasar keputusannya.
        try {
            $dinasUserId = (int) ($attendance->user_id ?? 0);
            $guruRow = $db
                ? $db->table('master_guru')->select('nama_guru')->where('id_user', $dinasUserId)->get()->getRow()
                : null;
            (new \App\Services\NotificationService())->notifyDinasRejected(
                $guruRow->nama_guru ?? 'Guru',
                (string) ($attendance->tgl ?? date('Y-m-d')),
                $dinasUserId,
                $catatan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi kepsek reject dinas gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Dinas ditolak');
    }

    public function kepsekPermitsPage()
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $guard;
        }

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        $permits = [];
        try {
            if ($db && $db->tableExists('absensi_izin')) {
                $builder = $db->table('absensi_izin i')
                    ->select('i.*')
                    ->orderBy('i.created_at', 'DESC')
                    ->where('i.status', 'pending');
                if ($db->tableExists('users')) {
                    $builder->select('u.first_name, u.last_name, u.username, u.foto')->join('users u', 'u.id = i.user_id', 'left');
                }
                $permits = $builder->get()->getResult();
            }
        } catch (\Throwable $e) {
            $permits = [];
        }

        return view('members/guru/kepsek/verifikasi_izin', [
            'permits' => $permits
        ]);
    }

    public function kepsekApprovePermit($id)
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $permit = $this->leaveModel->find($id);
        if (!$permit) {
            return $this->respond(false, 'Data tidak ditemukan');
        }
        if (($permit->status ?? '') !== 'pending') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        $catatan = (string) ($this->request->getPost('catatan') ?? '');

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        if ($this->adalahPemohon($permit)) {
            return $this->respond(false, 'Anda tidak dapat menyetujui pengajuan Anda sendiri', null, null, 403);
        }

        $update = ['status' => 'approved'];
        if ($db && $db->tableExists('absensi_izin')) {
            if ($db->fieldExists('verifikasi_catatan', 'absensi_izin')) {
                $update['verifikasi_catatan'] = $catatan;
            }
            if ($db->fieldExists('verified_by', 'absensi_izin')) {
                $update['verified_by'] = (int) (session()->get('user_id') ?? 0);
            }
            if ($db->fieldExists('verified_at', 'absensi_izin')) {
                $update['verified_at'] = date('Y-m-d H:i:s');
            }
        }

        if ($db && $db->tableExists('absensi_harian')) {            $start = strtotime((string) $permit->tgl_mulai);
            $end = strtotime((string) $permit->tgl_selesai);

            // Samakan enum dengan Admin\PresensiAdmin::approvePermit ('IJIN', bukan 'IZIN')
            $statusMasuk = 'IJIN';
            $jenisLower = strtolower(trim((string)($permit->jenis ?? '')));
            if ($jenisLower === 'sakit') {
                $statusMasuk = 'SAKIT';
            } elseif ($jenisLower === 'cuti') {
                $statusMasuk = 'CUTI';
            } elseif (in_array($jenisLower, ['dinas', 'dinas luar', 'tugas luar']) || strpos($jenisLower, 'dinas') !== false) {
                $statusMasuk = 'DINAS_APPROVED';
            }

            // Ambil pengaturan dulu SEBELUM dipakai
            $globalSetting = $this->settingModel->first();
            $point = 0;
            if ($statusMasuk === 'DINAS_APPROVED') {
                $point = (int) ($globalSetting->point_hadir_ontime ?? $globalSetting->point_hadir ?? 10);
            } elseif ($statusMasuk === 'SAKIT') {
                $point = (int) ($globalSetting->point_sakit ?? 0);
            } elseif ($statusMasuk === 'IJIN') {
                $point = (int) ($globalSetting->point_izin ?? 0);
            } elseif ($statusMasuk === 'CUTI') {
                $point = (int) ($globalSetting->point_cuti ?? 0);
            }

            $hasKeterangan = $db->fieldExists('keterangan', 'absensi_harian');
            $punyaIzinId  = $db->fieldExists('izin_id', 'absensi_harian');
            $svcLibur     = new \App\Services\IzinKuotaService($db);

            // Sama seperti approvePermit admin: hari yang sudah berisi scan
            // asli guru tidak ditimpa, dan baris yang ditulis dicap izin_id
            // supaya reject/delete mengembalikannya dengan tepat.
            $isAbsenNetral  = in_array($statusMasuk, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true);
            $dilewatiKecak  = [];

            $db->transBegin();
            try {
                // Status permit diubah dalam transaksi yang sama dengan penulisan
                // absensi harian: gagal di tengah → rollback penuh, bisa retry.
                // Atomik: hanya baris pending yang bisa berpindah.
                //
                // Sengaja TETAP ketat ke bentuk kanonik 'pending', tidak
                // memakai LeaveModel::STATUS_PENDING. Ini guard transisi,
                // bukan pembacaan daftar: membukanya ke '0' berarti pengajuan
                // yang kondisinya sudah tidak jelas ikut berubah status diam-
                // diam. Baris legacy ditangani migrasi, bukan dibiarkan
                // lolos ke jalur tulis.
                $db->table('absensi_izin')
                    ->where('id', $id)->where('status', 'pending')
                    ->update($update);
                if ($db->affectedRows() !== 1) {
                    throw new \RuntimeException('Status sudah diproses admin lain. Muat ulang halaman.');
                }

            for ($current = $start; $current <= $end; $current = strtotime('+1 day', $current)) {
                $dateStr = date('Y-m-d', $current);

                if ($svcLibur->isLibur($dateStr)) continue;

                $exist = $db->table('absensi_harian')
                    ->select('id' . ($punyaIzinId ? ', izin_id' : '') . ', jam_masuk')
                    ->where('user_id', $permit->user_id)
                    ->where('tgl', $dateStr)
                    ->get()->getRow();

                // Hari dengan scan asli dibiarkan utuh untuk semua jenis
                // pengajuan — sama seperti Admin\PresensiAdmin::approvePermit.
                // Menimpanya menghapus bukti kehadiran dan membuka kredit
                // hantu (denda + telat_menit dinol-kan).
                if ($exist && ! empty($exist->jam_masuk)) {
                    $dilewatiKecak[] = $dateStr;
                    continue;
                }

                $tambahan = [];
                if ($punyaIzinId) {
                    $tambahan['izin_id'] = (int) $id;
                }

                if ($exist) {
                    $updateAbsen = [
                        'status_masuk' => $statusMasuk,
                        'status_pulang' => $statusMasuk,
                        'points' => $point,
                        'denda' => 0,
                        'keterangan_denda' => null,
                        'telat_menit' => 0,
                        'pulang_cepat_menit' => 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ] + $tambahan;
                    if ($hasKeterangan) {
                        $updateAbsen['keterangan'] = (string) ($permit->keterangan ?? '');
                    }
                    if ($isAbsenNetral) {
                        $updateAbsen['jam_masuk']  = null;
                        $updateAbsen['jam_pulang'] = null;
                    }
                    $db->table('absensi_harian')->where('id', $exist->id)->update($updateAbsen);
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($exist->id);
                } else {
                    $insert = [
                        'user_id' => (int) $permit->user_id,
                        'id_guru' => (int) ($permit->id_guru ?? 0),
                        'tgl' => $dateStr,
                        'jam_masuk' => null,
                        'jam_pulang' => null,
                        'status_masuk' => $statusMasuk,
                        'status_pulang' => $statusMasuk,
                        'points' => $point,
                        'denda' => 0,
                        'keterangan_denda' => null,
                        'telat_menit' => 0,
                        'pulang_cepat_menit' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ] + $tambahan;
                    if ($hasKeterangan) {
                        $insert['keterangan'] = (string) ($permit->keterangan ?? '');
                    }
                    $db->table('absensi_harian')->insert($insert);
                    $newId = $db->insertID();
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($newId);
                }
            }
            $db->transCommit();

            try {
                (new AuditLogModel())->log('absensi_izin', (int) $id, 'KEPSEK_APPROVE',
                    (int) ($permit->user_id ?? 0), (int) (session()->get('user_id') ?? 0),
                    ['status' => (string) ($permit->status ?? '')],
                    [
                        'status'      => 'approved',
                        'hari_dilewati' => $dilewatiKecak ?? [],
                    ],
                    'Izin disetujui kepsek: ' . $permit->jenis);
            } catch (\Throwable $ex) {
                log_message('error', 'Audit kepsekApprovePermit gagal: ' . $ex->getMessage());
            }
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', 'Kepsek approve permit gagal: ' . $e->getMessage());
                return $this->respond(false, 'Gagal menyetujui pengajuan.', null, null, 500);
            }

            // Evaluasi dan sinkronisasi pembatalan surat teguran jika hari alpha gugur
            try {
                $penaltySvc = new \App\Services\AttendancePenaltyService();
                [$thnPermit, $blnPermit] = $this->tahunBulanIzin($start ?? null, (string) ($permit->tgl_mulai ?? ''));
                $penaltySvc->syncSuratTeguranOnAttendanceChange((int) $permit->user_id, $blnPermit, $thnPermit, $globalSetting);
            } catch (\Throwable $ex) {}
        }
            // Kabari guru + grup. Best-effort, setelah transaksi selesai.
            try {
                $notif = new \App\Services\NotificationService();
                $permitUserId = (int) ($permit->user_id ?? 0);
                $guruRow = $db->table('master_guru')->select('nama_guru')
                    ->where('id_user', $permitUserId)->get()->getRow();
                $namaGuru = $guruRow->nama_guru ?? 'Guru';
                // $start/$end dihitung di dalam blok absensi_harian; fallback
                // ke kolom izin agar notifikasi tetap benar bila blok dilewati.
                // Normalizer tahan DateTime|int|string (date() mentah melempar
                // TypeError untuk DateTime — pernah tercatat di log).
                $notif->notifyPermitApproved(
                    $namaGuru,
                    (string) ($permit->jenis ?? ''),
                    $this->tglIzinLabel($start ?? null, (string) ($permit->tgl_mulai ?? '')),
                    $this->tglIzinLabel($end ?? null, (string) ($permit->tgl_selesai ?? '')),
                    $permitUserId,
                    empty($dilewatiKecak)
                        ? $catatan
                        : trim($catatan . ' | ' . count($dilewatiKecak)
                            . ' hari dilewati karena sudah ada scan kehadiran: '
                            . implode(', ', $dilewatiKecak))
                );
            } catch (\Throwable $ex) {
                log_message('error', 'Notifikasi kepsek approve izin gagal: ' . $ex->getMessage());
            }

            $pesan = 'Pengajuan disetujui';
            if (! empty($dilewatiKecak)) {
                $pesan .= ' (catatan: ' . count($dilewatiKecak)
                    . ' hari tidak diubah karena sudah ada scan kehadiran: '
                    . implode(', ', $dilewatiKecak) . ')';
            }

            return $this->respond(true, $pesan);
    }

    public function kepsekRejectPermit($id)
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $permit = $this->leaveModel->find($id);
        if (!$permit) {
            return $this->respond(false, 'Data tidak ditemukan');
        }
        if (($permit->status ?? '') !== 'pending') {
            return $this->respond(false, 'Status sudah diproses sebelumnya');
        }

        $catatan = (string) ($this->request->getPost('catatan') ?? '');

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }
        if (!$db) {
            return $this->respond(false, 'Koneksi database gagal', null, null, 500);
        }

        if ($this->adalahPemohon($permit)) {
            return $this->respond(false, 'Anda tidak dapat menolak pengajuan Anda sendiri', null, null, 403);
        }

        $update = ['status' => 'rejected'];
        if ($db->tableExists('absensi_izin')) {
            if ($db->fieldExists('verifikasi_catatan', 'absensi_izin')) {
                $update['verifikasi_catatan'] = $catatan;
            }
            if ($db->fieldExists('verified_by', 'absensi_izin')) {
                $update['verified_by'] = (int) (session()->get('user_id') ?? 0);
            }
            if ($db->fieldExists('verified_at', 'absensi_izin')) {
                $update['verified_at'] = date('Y-m-d H:i:s');
            }
        }

        // Atomik + revert baris izin menjadi ALPHA seperti
        // Admin\PresensiAdmin::rejectPermit.
        //
        // `where('status', 'pending')` di bawah juga sengaja ketat ke
        // bentuk kanonik — lihat catatan pada kepsekApprovePermit().
        //
        // Catatan penting: method ini HANYA menerima pengajuan berstatus
        // 'pending' (dijaga guard di atas). Baris absensi baru ditulis saat
        // APPROVE — jadi pada jalur ini tidak ada satu pun baris yang
        // ditulis oleh pengajuan ini, dan tidak ada yang perlu dikembalikan.
        //
        // Loop revert yang ada sebelumnya justru membaca baris IJIN/SAKIT/
        // CUTI milik orang lain pada tanggal yang sama — misalnya hasil
        // input absen manual admin, atau izin disetujui manual lewat
        // saveManualPermit — lalu mengubahnya jadi ALPHA dan menagih denda
        // ke guru yang tidak melakukan apa-apa. Karena itu loop di bawah
        // hanya jalan bila pengajuan ini benar-benar pernah disetujui.
        $db->transBegin();
        try {
            $db->table('absensi_izin')
                ->where('id', $id)->where('status', 'pending')
                ->update($update);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Status sudah diproses admin lain. Muat ulang halaman.');
            }

            $setTolak = $this->settingModel->first() ?? (object) [];
            $flatAlpha = (float) ($setTolak->denda_alpha_per_hari ?? 5000);
            $poinAlpha = abs((int) ($setTolak->point_alpa ?? 5));
            $svcTolak = new \App\Services\AttendancePenaltyService();
            $mulai = strtotime((string) ($permit->tgl_mulai ?? ''));
            $selesai = strtotime((string) ($permit->tgl_selesai ?? ''));
            $svcLibur = new \App\Services\IzinKuotaService($db);
            $permitStatusTolak = $svcTolak->normalizeIzinStatus((string) ($permit->jenis ?? ''));
            $pernahDisetujuiTolak = strtolower(trim((string) ($permit->status ?? ''))) === 'approved';
            $blnSentuh = [];
            if ($pernahDisetujuiTolak && $mulai && $selesai && $mulai <= $selesai) {
                for ($i = $mulai; $i <= $selesai; $i = strtotime('+1 day', $i)) {
                    $tglStr = date('Y-m-d', $i);
                    if ($svcLibur->isLibur($tglStr)) continue;
                    $baris = $db->table('absensi_harian')
                        ->where('user_id', $permit->user_id)->where('tgl', $tglStr)->get()->getRow();
                    if (!$baris) continue;
                    $st = strtoupper((string) ($baris->status_masuk ?? ''));
                    if (!in_array($st, ['IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS_APPROVED', 'DINAS'], true) && strpos($st, 'DINAS') !== 0) {
                        continue;
                    }
                    // Hanya baris milik pengajuan ini yang jadi ALPHA —
                    // lihat catatan pada Admin\PresensiAdmin::rejectPermit.
                    if (! \App\Models\AttendanceModel::apakahMilikIzin($baris, (int) $id, $permitStatusTolak)) {
                        continue;
                    }
                    $cap = $svcTolak->applyMonthlyCap((int) $permit->user_id, $tglStr, $flatAlpha, $setTolak, (int) $baris->id, true);
                    $db->table('absensi_harian')->where('id', $baris->id)->update([
                        'status_masuk' => 'ALPHA',
                        'status_pulang' => 'ALPHA',
                        'points' => -$poinAlpha,
                        'denda' => (float) $cap['total'],
                        'keterangan_denda' => 'Alpa (Rp ' . number_format($flatAlpha, 0, ',', '.') . ')' . ($cap['capped'] ? ' (dibatasi cap bulanan Rp ' . number_format((float) $cap['total'], 0, ',', '.') . ')' : ''),
                        'keterangan' => 'Ditolak: ' . $catatan,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($baris->id);
                    $blnSentuh[substr($tglStr, 0, 7)] = true;
                }
            }
            $db->transCommit();

            try {
                (new AuditLogModel())->log('absensi_izin', (int) $id, 'KEPSEK_REJECT',
                    (int) ($permit->user_id ?? 0), (int) (session()->get('user_id') ?? 0),
                    ['status' => (string) ($permit->status ?? '')],
                    [
                        'status'           => 'rejected',
                        'alasan'           => (string) $catatan,
                        'bulan_terpengaruh' => array_keys($blnSentuh),
                    ],
                    'Izin ditolak kepsek: ' . $permit->jenis);
            } catch (\Throwable $ex) {
                log_message('error', 'Audit kepsekRejectPermit gagal: ' . $ex->getMessage());
            }

            foreach (array_keys($blnSentuh) as $ym) {
                try {
                    [$thS, $blS] = array_map('intval', explode('-', $ym));
                    $svcTolak->syncSuratTeguranOnAttendanceChange((int) $permit->user_id, $blS, $thS, $setTolak);
                } catch (\Throwable $ex2) {}
            }
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', 'Kepsek reject permit gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menolak pengajuan — periksa log aplikasi.', null, null, 500);
        }

        // Kabari guru + grup. Best-effort, tidak boleh menggagalkan penolakan.
        try {
            $notif = new \App\Services\NotificationService();
            $permitUserId = (int) ($permit->user_id ?? 0);
            $guruRow = $db ? $db->table('master_guru')->select('nama_guru')
                ->where('id_user', $permitUserId)->get()->getRow() : null;
            $namaGuru = $guruRow->nama_guru ?? 'Guru';
            $notif->notifyPermitRejected(
                $namaGuru,
                (string) ($permit->jenis ?? ''),
                $permitUserId,
                $catatan
            );
        } catch (\Throwable $ex) {
            log_message('error', 'Notifikasi kepsek reject izin gagal: ' . $ex->getMessage());
        }

        return $this->respond(true, 'Pengajuan ditolak');
    }

    public function kepsekTeguranPage()
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $guard;
        }

        $bulan = (int) ($this->request->getGet('bulan') ?? date('n'));
        $tahun = (int) ($this->request->getGet('tahun') ?? date('Y'));

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        $letters = [];
        try {
            if ($db && $db->tableExists('absensi_surat_teguran')) {
                $builder = $db->table('absensi_surat_teguran a')
                    ->select('a.*')
                    ->orderBy('a.created_at', 'DESC')
                    ->where('a.bulan', $bulan)
                    ->where('a.tahun', $tahun);

                if ($db->tableExists('master_guru')) {
                    $builder->select('g.nama_guru, g.nip, g.id_user, g.foto as guru_foto')->join('master_guru g', 'g.id_guru = a.id_guru', 'left');
                }

                $letters = $builder->get()->getResult();
            }
        } catch (\Throwable $e) {
            $letters = [];
        }

        return view('members/guru/kepsek/surat_teguran', [
            'letters' => $letters,
            'bulan' => $bulan,
            'tahun' => $tahun
        ]);
    }

    public function kepsekDownloadTeguran($id)
    {
        $guard = $this->ensureKepsekAccess();
        if ($guard) {
            return $guard;
        }

        $db = \Config\Database::connect();
        $letter = $db->table('absensi_surat_teguran a')
            ->join('master_guru g', 'g.id_guru = a.id_guru', 'left')
            ->where('a.id', $id)
            ->get()->getRow();

        if (!$letter) {
            return redirect()->back()->with('error', 'Data surat tidak ditemukan');
        }

        if (($letter->status ?? 'aktif') === 'dibatalkan') {
            return redirect()->back()->with('error', 'Surat teguran ini telah dibatalkan karena izin/dispensasi resmi disetujui');
        }

        $baseSetting = $this->settingModel->first();
        if (!$baseSetting) {
            $baseSetting = (object) [];
        }
        $dashboardModel = new DashboardModel();
        $globalSetting = $dashboardModel->getSetting();

        $setting = clone $baseSetting;
        if ($globalSetting) {
            foreach (get_object_vars($globalSetting) as $key => $val) {
                if (!isset($setting->$key)) {
                    $setting->$key = $val;
                }
            }
        }

        // Gunakan snapshot jumlah_alpha dari database agar dokumen arsip konsisten
        $jumlahAlpha = (int) ($letter->jumlah_alpha ?? 0);
        if ($jumlahAlpha <= 0) {
            try {
                $letterUserId = (int) ($letter->id_user ?? 0);
                if ($letterUserId <= 0 && !empty($letter->id_guru) && $db->tableExists('master_guru')) {
                    $grow = $db->table('master_guru')->where('id_guru', $letter->id_guru)->get()->getRow();
                    if ($grow && !empty($grow->id_user))
                        $letterUserId = (int) $grow->id_user;
                }
                if ($letterUserId > 0) {
                    $calcAlpha = $this->alphaForLetterPeriod(
                        $letterUserId, (int) ($letter->bulan ?? date('n')), (int) ($letter->tahun ?? date('Y')), $setting, $db
                    );
                    $jumlahAlpha = max((int) ($letter->level ?? 0), $calcAlpha);
                } else {
                    $jumlahAlpha = (int) ($letter->level ?? 3);
                }
            } catch (\Throwable $e) {
                log_message('error', 'kepsekDownloadTeguran alpha gagal: ' . $e->getMessage());
                $jumlahAlpha = (int) ($letter->level ?? 3);
            }
        }

        $teguranText = \App\Libraries\SuratTeguranLevel::label((int) ($letter->level ?? 0));

        // Rincian tanggal alpha untuk kalimat rincian di badan surat.
        $alphaDates = [];
        try {
            $uidSurat = (int) ($letter->id_user ?? 0);
            if ($uidSurat <= 0 && !empty($letter->id_guru) && $db->tableExists('master_guru')) {
                $grow2 = $db->table('master_guru')->where('id_guru', $letter->id_guru)->get()->getRow();
                if ($grow2 && !empty($grow2->id_user)) $uidSurat = (int) $grow2->id_user;
            }
            if ($uidSurat > 0) {
                $alphaDates = (new \App\Services\AttendancePenaltyService())->teacherMonthlyAlphaDates(
                    $uidSurat, (int) ($letter->bulan ?? date('n')), (int) ($letter->tahun ?? date('Y')), $setting
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'kepsekDownloadTeguran alpha dates gagal: ' . $e->getMessage());
        }

        $logoBase64 = '';
        if (isset($setting->logo_kiri) && $setting->logo_kiri && file_exists(FCPATH . $setting->logo_kiri)) {
            $type = pathinfo(FCPATH . $setting->logo_kiri, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->logo_kiri);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $kopBase64 = '';
        if (isset($setting->kopsekolah) && $setting->kopsekolah && file_exists(FCPATH . $setting->kopsekolah)) {
            $type = pathinfo(FCPATH . $setting->kopsekolah, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->kopsekolah);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $nomorSurat = \App\Libraries\SuratNomor::build(
            (int) ($letter->nomor_urut ?? 0),
            (int) ($letter->bulan ?? date('n')),
            (int) ($letter->tahun ?? date('Y')),
            $setting,
            (int) ($letter->level ?? 0),
            $letter->created_at ? date('d-m-Y', strtotime($letter->created_at)) : null
        );

        $viewData = [
            'setting' => $setting,
            'guru' => $letter,
            'jumlah_alpha' => $jumlahAlpha,
            'tingkat_teguran' => $teguranText,
            'kepsek_nama' => $setting->kepsek ?? ($setting->nama_kepsek ?? '..........................'),
            'kepsek_nip' => $setting->nip_kepsek ?? ($setting->nip ?? '..........................'),
            'nip' => $setting->nip_kepsek ?? ($setting->nip ?? '..........................'),
            'logo_base64' => $logoBase64,
            'kop_base64' => $kopBase64,
            'nomor_surat' => $nomorSurat,
            'periode_bulan' => (int) ($letter->bulan ?? date('n')),
            'periode_tahun' => (int) ($letter->tahun ?? date('Y')),
            // Tanggal surat = tanggal terbit arsip, bukan tanggal cetak.
            'tanggal_terbit' => !empty($letter->created_at) ? (string) $letter->created_at : null,
            // Daftar tanggal alpha periode surat untuk rincian di badan surat.
            'alpha_dates' => $alphaDates
        ];

        $html = view('members/guru/surat_teguran_pdf', $viewData);
        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Surat_Teguran_{$letter->level}_Hari_{$letter->nama_guru}.pdf";
        $dompdf->stream($filename, ["Attachment" => false]);
        exit;
    }

    /**
     * Update Profile Data
     */
    public function updateProfile()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        try {
            $userId = (int) session()->get('user_id');
            $idGuru = $this->resolveIdGuru($userId);
            $db = \Config\Database::connect();

            // Validate input
            $rules = [
                'nama_guru' => 'required|min_length[3]',
                'email' => 'permit_empty|valid_email',
                'no_hp' => 'permit_empty|numeric|max_length[15]',
            ];

            if (!$this->validate($rules)) {
                return $this->respond(false, 'Data tidak valid: ' . implode(', ', $this->validator->getErrors()));
            }

            // ID Telegram: opsional, divalidasi terpisah karena formatnya
            // tidak cocok aturan 'numeric' (boleh diawali tanda minus).
            $telegramRaw = null;
            $telegramSent = array_key_exists('telegram_id', $this->request->getPost());
            if ($telegramSent) {
                $telegramRaw = (string) ($this->request->getPost('telegram_id') ?? '');
                try {
                    $telegramRaw = \App\Libraries\TelegramLink::normalizeChatId($telegramRaw);
                } catch (\InvalidArgumentException $e) {
                    return $this->respond(false, $e->getMessage());
                }
            }

            // Prepare update data
            $data = [
                'nama_guru' => $this->request->getPost('nama_guru'),
                'email' => $this->request->getPost('email'),
                'no_hp' => $this->request->getPost('no_hp'),
                'tempat_lahir' => $this->request->getPost('tempat_lahir'),
                'tgl_lahir' => $this->request->getPost('tgl_lahir'),
                'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
                'alamat_jalan' => $this->request->getPost('alamat_jalan')
            ];

            // Remove empty values
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });

            $hasTgColumn = $db->fieldExists('telegram_id', 'master_guru');
            if ($telegramSent && $hasTgColumn) {
                // Biarkan string kosong tersimpan supaya tautan bisa dilepas.
                $data['telegram_id'] = $telegramRaw;
            }

            // Update master_guru
            $updated = $db->table('master_guru')
                ->where('id_guru', $idGuru)
                ->update($data);

            $telegramNote = '';
            if ($telegramSent) {
                if (!$hasTgColumn) {
                    $telegramNote = ' Kolom ID Telegram belum tersedia, jalankan: php spark migrate';
                } elseif ($telegramRaw === '') {
                    $ok = \App\Libraries\TelegramLink::detachFromGuru($idGuru, $userId);
                    $telegramNote = $ok
                        ? ' Tautan Telegram dilepas.'
                        : ' Gagal melepas tautan Telegram — coba simpan lagi.';
                } else {
                    $r = \App\Libraries\TelegramLink::attachToGuru($idGuru, $userId, $telegramRaw);
                    $telegramNote = $r['ok'] ? ' ' . $r['message'] : ' Gagal menautkan Telegram: ' . $r['message'];
                }
            }

            // Nomor WhatsApp notifikasi personal. Berbeda dari Telegram,
            // kolom ini TIDAK masuk $data master_guru lewat update() biasa:
            // hanya kolom catatannya yang ditulis, sedangkan sumber
            // runtime-nya user_notifikasi.wa_number (lihat WhatsAppLink).
            $waNote = '';
            if (array_key_exists('wa_number', $this->request->getPost())) {
                $waRaw = (string) ($this->request->getPost('wa_number') ?? '');
                $hasWaColumn = $db->fieldExists('wa_number', 'master_guru');

                if (! $hasWaColumn || ! $db->fieldExists('wa_number', 'user_notifikasi')) {
                    $waNote = ' Kolom WhatsApp belum tersedia, jalankan: php spark migrate';
                } else {
                    $r = \App\Libraries\WhatsAppLink::attachToGuru($idGuru, $userId, $waRaw);
                    $waNote = $r['ok'] ? ' ' . $r['message'] : ' Gagal menyimpan nomor WhatsApp: ' . $r['message'];
                }
            }

            $note = trim($telegramNote . $waNote);

            if ($updated || $waNote !== '') {
                return $this->respond(true, 'Profil berhasil diupdate.' . $note);
            }
            return $this->respond(false, 'Tidak ada perubahan data');

        } catch (\Throwable $e) {
            log_message('error', 'Update Profile Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal update profil.', null, null, 500);
        }
    }

    /**
     * Upload Profile Photo
     */
    public function uploadFoto()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        try {
            $userId = (int) session()->get('user_id');
            $idGuru = $this->resolveIdGuru($userId);
            $file = $this->request->getFile('foto');

            if (!$file || !$file->isValid()) {
                return $this->respond(false, 'File tidak valid');
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                return $this->respond(false, 'File harus berupa gambar (JPG, JPEG, PNG)');
            }

            // Validate file size (max 2MB)
            if ($file->getSize() > 2 * 1024 * 1024) {
                return $this->respond(false, 'Ukuran file maksimal 2MB');
            }

            // Create upload directory if not exists
            // Create upload directory if not exists
            $userId = session()->get('user_id');
            $uploadPath = FCPATH . 'uploads/users/' . $userId . '/profile/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Get old photo to delete
            $db = \Config\Database::connect();
            $oldData = $db->table('master_guru')->where('id_guru', $idGuru)->get()->getRow();

            // Generate new filename
            // Generate new filename
            $newName = $file->getRandomName();

            // Move file
            if ($file->move($uploadPath, $newName)) {
                $fullPath = 'uploads/users/' . $userId . '/profile/' . $newName;

                // Update master_guru (Store FULL PATH like Dataguru controller)
                $db->table('master_guru')
                    ->where('id_guru', $idGuru)
                    ->update(['foto' => $fullPath]);
                $okMguru = $db->affectedRows() > 0 || ($oldData && ($oldData->foto ?? null) === $fullPath);

                // Update users table (Store FULL PATH)
                $okUsers = true;
                if ($userId) {
                    $db->table('users')
                        ->where('id', $userId)
                        ->update(['foto' => $fullPath]);
                    $okUsers = $db->affectedRows() > 0;
                }

                if (!$okMguru || !$okUsers) {
                    // DB gagal — jangan tinggalkan file yatim, dan jangan hapus foto lama.
                    @unlink($uploadPath . $newName);
                    log_message('error', 'Upload Foto: update DB gagal (master_guru=' . var_export($okMguru, true)
                        . ', users=' . var_export($okUsers, true) . ') utk user ' . $userId);
                    return $this->respond(false, 'Foto terunggah tapi gagal disimpan ke profil. Silakan coba lagi.', null, null, 500);
                }

                // Baru hapus foto lama setelah kedua tulis DB berhasil
                if ($oldData && $oldData->foto && $oldData->foto !== $fullPath && file_exists(FCPATH . $oldData->foto)) {
                    // Avoid deleting default assets if accidentally stored
                    if (strpos($oldData->foto, 'assets/') === false) {
                        @unlink(FCPATH . $oldData->foto);
                    }
                }

                return $this->respond(true, 'Foto profil berhasil diupdate');
            } else {
                return $this->respond(false, 'Gagal upload file');
            }


        } catch (\Throwable $e) {
            log_message('error', 'Upload Foto Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal upload foto.', null, null, 500);
        }
    }

    /**
     * Register Face Descriptor for Face Recognition
     */
    public function registerFace()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $descriptor = $this->request->getPost('face_descriptor');
        if (empty($descriptor)) {
            return $this->respond(false, 'Data wajah tidak ditemukan');
        }

        $arr = json_decode($descriptor, true);
        if (!is_array($arr) || count($arr) !== 128) {
            return $this->respond(false, 'Data wajah tidak valid');
        }

        try {
            $db = \Config\Database::connect();
            $forge = \Config\Database::forge();
            $userId = session()->get('user_id');

            // Auto-create face_descriptor column if missing
            if (!$db->fieldExists('face_descriptor', 'users')) {
                $forge->addColumn('users', [
                    'face_descriptor' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ]
                ]);
            }

            // Check if user already has a face descriptor
            $user = $db->table('users')
                ->select('face_descriptor')
                ->where('id', $userId)
                ->get()
                ->getRow();

            $existingFace = !empty($user->face_descriptor);

            // Pendaftaran pertama (belum punya wajah): langsung aktif agar user baru bisa absen.
            if (!$existingFace) {
                $db->transBegin();
                $db->table('users')
                    ->where('id', $userId)
                    ->update(['face_descriptor' => $descriptor]);
                $okUpdate = $db->affectedRows() > 0;

                $okLog = true;
                if ($okUpdate && $db->tableExists('face_registration_requests')) {
                    $db->table('face_registration_requests')->insert([
                        'user_id' => $userId,
                        'face_descriptor' => $descriptor,
                        'status' => 'approved',
                        'keterangan' => 'Pendaftaran pertama (otomatis)',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $okLog = true;
                }

                if (!$okUpdate || !$okLog || $db->transStatus() === false) {
                    $db->transRollback();
                    return $this->respond(false, 'Gagal mendaftarkan wajah. Silakan coba lagi.', null, null, 500);
                }
                $db->transCommit();

                return $this->respond(true, 'Data wajah berhasil didaftarkan dan langsung aktif untuk verifikasi absensi.');
            }

            // Perubahan wajah berikutnya: WAJIB persetujuan admin (anti titip absen —
            // sebelumnya overwrite langsung + auto-approve request pending).
            if ($db->tableExists('face_registration_requests')) {
                $pending = $db->table('face_registration_requests')
                    ->where('user_id', $userId)
                    ->where('status', 'pending')
                    ->countAllResults();
                if ($pending > 0) {
                    return $this->respond(false, 'Pengajuan perubahan wajah Anda masih menunggu persetujuan admin.');
                }

                // Batasi pergantian wajah: maksimal 1x per 30 hari
                $lastApproved = $db->table('face_registration_requests')
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->orderBy('updated_at', 'DESC')
                    ->get()->getRow();
                if ($lastApproved && !empty($lastApproved->updated_at)
                    && (time() - strtotime($lastApproved->updated_at)) < 30 * 86400) {
                    return $this->respond(false, 'Data wajah hanya dapat diganti 1x per 30 hari. Hubungi admin bila mendesak.');
                }

                $db->table('face_registration_requests')->insert([
                    'user_id' => $userId,
                    'face_descriptor' => $descriptor,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                return $this->respond(true, 'Pengajuan perubahan wajah terkirim. Wajah lama tetap berlaku hingga admin menyetujui.');
            }

            // Fallback bila tabel request belum ada: tolak perubahan demi keamanan
            return $this->respond(false, 'Perubahan wajah membutuhkan persetujuan admin. Hubungi admin sekolah.');
        } catch (\Throwable $e) {
            log_message('error', 'Register Face Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal mendaftarkan wajah', null, null, 500);
        }
    }

    public function getWfhQuota()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $userId = session()->get('user_id');

        try {
            $db = \Config\Database::connect();
            $setting = $this->settingModel->first();

            $wfhEnabled = $setting && (int) ($setting->wfh_enabled ?? 0) === 1;
            $maxWfh = (int) ($setting->wfh_max_per_bulan ?? 5);

            $wfhCount = 0;
            if ($wfhEnabled && $db->tableExists('absensi_harian') && $db->fieldExists('is_wfh', 'absensi_harian')) {
                $wfhCount = $db->table('absensi_harian')
                    ->where('user_id', $userId)
                    ->where('is_wfh', 1)
                    ->like('tgl', date('Y-m'), 'after')
                    ->countAllResults();
            }

            // Check if already checked in today
            $today = date('Y-m-d');
            $todayRecord = $this->attendanceModel
                ->where('user_id', $userId)
                ->where('tgl', $today)
                ->first();
            $alreadyCheckedIn = !empty($todayRecord);
            $todayIsWfh = $alreadyCheckedIn && (int) ($todayRecord->is_wfh ?? 0) === 1;

            // Cek apakah sudah check-in dan bukan WFH — tidak bisa WFH lagi
            $alreadyCheckedInNonWfh = $alreadyCheckedIn && !$todayIsWfh;

            return $this->respond(true, 'Kuota WFH', [
                'wfh_enabled' => $wfhEnabled,
                'wfh_count' => (int) $wfhCount,
                'wfh_max' => $maxWfh,
                'wfh_remaining' => max(0, $maxWfh - $wfhCount),
                'already_checked_in' => $alreadyCheckedIn,
                'today_is_wfh' => $todayIsWfh,
                'already_checked_in_non_wfh' => $alreadyCheckedInNonWfh,
            ]);
        } catch (\Throwable $e) {
            return $this->respond(false, 'Gagal mendapatkan kuota WFH', null, null, 500);
        }
    }

    public function getFaceRegistrationStatus()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $userId = session()->get('user_id');

        try {
            $db = \Config\Database::connect();

            $user = $db->table('users')
                ->select('face_descriptor')
                ->where('id', $userId)
                ->get()
                ->getRow();

            $hasFace = !empty($user->face_descriptor);
            $pending = null;

            if ($db->tableExists('face_registration_requests')) {
                $pending = $db->table('face_registration_requests')
                    ->select('id, status, keterangan, created_at, updated_at')
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRow();
            }

            return $this->respond(true, 'Status wajah', [
                'has_face' => $hasFace,
                'pending_request' => $pending ? $pending->status : null,
                'request_data' => $pending,
            ]);
        } catch (\Throwable $e) {
            return $this->respond(false, 'Gagal mendapatkan status', null, null, 500);
        }
    }

    /**
     * QR Code Check-in for Events/Meetings
     */
    public function qrCheckin()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        // Rate limit seperti check-in/out: cegah brute-force token & spam scan
        $throttler = \Config\Services::throttler();
        $qrKey = 'qr_checkin_' . md5($this->request->getIPAddress() . session()->get('user_id'));
        if ($throttler->check($qrKey, 5, MINUTE) === false) {
            return $this->respond(false, 'Terlalu banyak permintaan. Silakan tunggu 1 menit.', null, null, 429);
        }

        $token = $this->request->getPost('token');
        $lat = (float) $this->request->getPost('lat');
        $lng = (float) $this->request->getPost('lng');

        if (empty($token)) {
            return $this->respond(false, 'Token QR tidak valid');
        }

        // QR tidak boleh tanpa lokasi: cegah token diteruskan via screenshot/chat
        // lalu di-scan dari luar lokasi acara.
        if (!$this->isValidCoordinate($lat, $lng)) {
            return $this->respond(false, 'Lokasi wajib diaktifkan untuk scan QR');
        }
        // Akurasi wajib dikirim klien; 0/hilang = sinyal mock atau GPS mati.
        // Dulu: (float) null → 0.0 → selalu ditolak sebagai mock bahkan saat sah.
        $qrAccuracyRaw = $this->request->getPost('accuracy');
        if ($qrAccuracyRaw === null || $qrAccuracyRaw === '') {
            return $this->respond(false, 'Data akurasi GPS tidak terbaca. Aktifkan GPS presisi tinggi lalu coba lagi.');
        }
        $qrAccuracy = (float) $qrAccuracyRaw;
        $mockCheck = $this->detectMockLocation($lat, $lng, $qrAccuracy);
        if ($mockCheck['strong']) {
            return $this->respond(false, 'Terdeteksi lokasi palsu (mock location). Matikan aplikasi Fake GPS dan coba lagi.', null, null, 400);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_qr')) {
            return $this->respond(false, 'Fitur QR belum diaktifkan');
        }
        $this->patchAbsensiQrLogJenis($db);

        // Resolve event: token rotating (window ±1 interval) atau static.
        // Token rotating kedaluwarsa tiap 30–120 detik → screenshot/chat basi.
        $qr = \App\Libraries\QrToken::findEvent($db, $token, date('Y-m-d'));

        if (!$qr) {
            return $this->respond(false, 'QR Code tidak valid, kedaluwarsa, atau sudah tidak aktif');
        }

        $now = date('H:i:s');
        if ($now < $qr->jam_mulai || $now > $qr->jam_selesai) {
            return $this->respond(false, "Waktu absen: {$qr->jam_mulai} - {$qr->jam_selesai}");
        }

        $userId = session()->get('user_id');
        $scanBuilder = $db->table('absensi_qr_log')
            ->where('qr_id', $qr->id)
            ->where('user_id', $userId);
        if ($db->fieldExists('jenis', 'absensi_qr_log')) {
            $scanBuilder->where('jenis', 'masuk');
        }
        $alreadyScanned = $scanBuilder->countAllResults();

        if ($alreadyScanned > 0) {
            return $this->respond(false, 'Anda sudah absen untuk acara ini');
        }

        // Kebijakan QR: tanpa pencocokan wajah. Pengaman tetap token berotasi,
        // sesi login, geofence, dan satu scan per event.
        $idGuru = $this->resolveIdGuru((int) $userId);
        try {
            $logData = [
                'qr_id' => $qr->id,
                'user_id' => $userId,
                'id_guru' => $idGuru ?: 0,
                'scanned_at' => date('Y-m-d H:i:s'),
                'lat' => $lat ?: null,
                'lng' => $lng ?: null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            if ($db->fieldExists('jenis', 'absensi_qr_log')) {
                $logData['jenis'] = 'masuk';
            }
            $db->table('absensi_qr_log')->insert($logData);
        } catch (\Throwable $e) {
            // UNIQUE(qr_id, user_id, jenis): scan ganda paralel tetap satu baris
            if ($this->isDuplicateKeyError($e)) {
                return $this->respond(false, 'Anda sudah absen untuk acara ini');
            }
            throw $e;
        }

        // Jembatani scan QR menjadi absen masuk harian (agar tercatat di
        // rekap, denda & poin — bukan sekadar log acara).
        $bridge = $this->bridgeQrCheckinToHarian(
            (int) $userId, (int) $idGuru, $lat, $lng, $qrAccuracy, $qr,
            (int) ($mockCheck['is_mock'] ?? 0)
        );

        $msg = "Absen {$qr->nama_acara} berhasil";
        $data = ['qr_event' => $qr->nama_acara];
        if (!empty($bridge['bridged'])) {
            $msg .= $bridge['status_masuk'] === 'LATE'
                ? " + tercatat absen masuk (Terlambat {$bridge['telat_menit']} mnt)"
                : " + tercatat absen masuk (Tepat Waktu)";
            $data['status_masuk'] = $bridge['status_masuk'];
            $data['telat_menit'] = (int) $bridge['telat_menit'];
            // Notifikasi keterlambatan via QR (best-effort)
            if ($bridge['status_masuk'] === 'LATE') {
                try {
                    $guruQr = $db->table('master_guru')->select('nama_guru')->where('id_user', $userId)->get()->getRow();
                    $namaQr = $guruQr->nama_guru ?? ('User ' . $userId);
                    (new \App\Services\NotificationService())->notifyLateCheckin(
                        $namaQr,
                        date('H:i'),
                        (int) $bridge['telat_menit'],
                        $userId
                    );
                } catch (\Throwable $e) {
                    log_message('error', 'Notifikasi telat QR gagal: ' . $e->getMessage());
                }
            }
        } elseif (!empty($bridge['reason'])) {
            $data['harian_note'] = $bridge['reason'];
        }

        return $this->respond(true, $msg, $data);
    }

    /**
     * QR check-out: scan event yang sama dipakai untuk absen pulang.
     *
     * Gerbang sama dengan checkOut face kecuali kamera/verifikasi wajah:
     * jam pulang efektif, wajib jurnal, geofence, serta perhitungan
     * status/poin/denda. Absen QR tidak meminta foto sama sekali.
     */
    public function qrCheckout()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $throttler = \Config\Services::throttler();
        $qrKey = 'qr_checkout_' . md5($this->request->getIPAddress() . session()->get('user_id'));
        if ($throttler->check($qrKey, 5, MINUTE) === false) {
            return $this->respond(false, 'Terlalu banyak permintaan. Silakan tunggu 1 menit.', null, null, 429);
        }

        $token = (string) ($this->request->getPost('token') ?? '');
        $lat = (float) $this->request->getPost('lat');
        $lng = (float) $this->request->getPost('lng');

        if ($token === '') {
            return $this->respond(false, 'Token QR tidak valid');
        }
        if (!$this->isValidCoordinate($lat, $lng)) {
            return $this->respond(false, 'Lokasi wajib diaktifkan untuk scan QR');
        }
        $qrAccuracyRaw = $this->request->getPost('accuracy');
        if ($qrAccuracyRaw === null || $qrAccuracyRaw === '') {
            return $this->respond(false, 'Data akurasi GPS tidak terbaca. Aktifkan GPS presisi tinggi lalu coba lagi.');
        }
        $accuracy = (float) $qrAccuracyRaw;
        if ($accuracy < 0) {
            return $this->respond(false, 'Akurasi GPS tidak valid');
        }
        if ($accuracy > 10000) {
            return $this->respond(false, 'Akurasi GPS terlalu rendah. Nyalakan GPS dan coba lagi.');
        }
        $mockCheck = $this->detectMockLocation($lat, $lng, $accuracy);
        if ($mockCheck['strong']) {
            return $this->respond(false, 'Terdeteksi lokasi palsu (mock location). Matikan aplikasi Fake GPS dan coba lagi.', null, null, 400);
        }
        $isMockLocation = (int) ($mockCheck['is_mock'] ?? 0);
        $mockWarning = $isMockLocation ? ' ⚠️ Akurasi GPS rendah, data tercatat untuk verifikasi.' : '';

        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_qr')) {
            return $this->respond(false, 'Fitur QR belum diaktifkan');
        }
        $this->patchAbsensiQrLogJenis($db);

        $qr = \App\Libraries\QrToken::findEvent($db, $token, date('Y-m-d'));
        if (!$qr) {
            return $this->respond(false, 'QR Code tidak valid, kedaluwarsa, atau sudah tidak aktif');
        }

        $now = date('H:i:s');
        if ($now < $qr->jam_mulai || $now > $qr->jam_selesai) {
            return $this->respond(false, "Waktu absen: {$qr->jam_mulai} - {$qr->jam_selesai}");
        }

        $userId = (int) session()->get('user_id');
        $idGuru = $this->resolveIdGuru($userId);
        $checkOutDate = date('Y-m-d');
        $recordedTime = date('Y-m-d H:i:s');

        $todayAttendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('tgl', $checkOutDate)
            ->first();

        if (!$todayAttendance) {
            return $this->respond(false, 'Anda belum absen masuk pada tanggal ' . $checkOutDate);
        }
        if (empty($todayAttendance->jam_masuk)) {
            return $this->respond(false, 'Anda belum absen masuk pada tanggal ' . $checkOutDate
                . '. Absen pulang hanya bisa dilakukan setelah absen masuk tercatat.');
        }
        if ($todayAttendance->jam_pulang) {
            return $this->respond(false, 'Anda sudah absen pulang');
        }

        $isWfh = (int) ($todayAttendance->is_wfh ?? 0) === 1;
        $setting = $this->settingModel->first();
        if (!$setting) {
            $setting = $this->getDefaultSettings();
        }

        // Status dinas hanya mengikuti record check-in (client tidak bisa menaikkannya).
        $isDinas = \App\Services\WajibJurnalPulangService::isStatusDinas($todayAttendance->status_masuk ?? '');

        // Kebijakan QR: tanpa kamera, foto, atau verifikasi wajah. Pengaman
        // tetap token, sesi login, jam pulang, jurnal, dan geofence.

        // Izin pulang awal: sama dengan checkOut — flag eksplisit + alasan.
        $isEarly = ($this->request->getPost('izin_pulang_awal') === '1')
            || ($this->request->getPost('is_dinas') === '2');
        $keterangan = trim((string) ($this->request->getPost('keterangan') ?? ''));
        if ($isEarly && $keterangan === '') {
            return $this->respond(false, 'Pulang awal wajib mengisi alasan pada kolom keterangan.');
        }

        $dayOfWeek = (int) date('N', strtotime($checkOutDate));
        $jadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
        }

        if ($isWfh) {
            $jamPulangLimit = ($setting->wfh_selesai ?? '16:00:00');
        } else {
            $shiftModel = new \App\Models\ShiftModel();
            $workSchedule = $shiftModel->getScheduleForUser($userId, $dayOfWeek, $jadwal, $setting);
            $jamPulangLimit = $workSchedule['jam_pulang'];
        }

        if (!$isDinas && !$isWfh && !$isEarly && $now < $jamPulangLimit) {
            return $this->respond(false, "Belum waktunya pulang. Jadwal: $jamPulangLimit");
        }

        if (!$isDinas) {
            $sesiKurang = $this->getSessionsBlockingCheckout(
                $idGuru,
                $checkOutDate,
                $dayOfWeek,
                $now,
                $setting,
                $db
            );
            if (!empty($sesiKurang)) {
                $gate = $this->formatBlockingSessions($sesiKurang, $setting, $checkOutDate, $db);
                return $this->respond(false, $gate['message'], $gate['data']);
            }
        }

        $validDistance = 0;
        $lokasiId = null;
        if (!$isDinas && !$isWfh) {
            $locationRows = [];
            try {
                $locationRows = $db->table('absensi_lokasi')->get()->getResult();
            } catch (\Throwable $e) {
                log_message('error', 'Failed to load absensi_lokasi table: ' . $e->getMessage());
            }
            if (empty($locationRows)) {
                return $this->respond(false, 'Titik lokasi kantor belum dikonfigurasi oleh admin. Hubungi admin sekolah.');
            }

            $validDistance = null;
            foreach ($locationRows as $loc) {
                $locLat = (float) $loc->lat;
                $locLng = (float) $loc->lng;
                $radius = (float) (!empty($loc->radius_meter) ? $loc->radius_meter : ($setting->radius_meter ?? 100));
                $dist = distance_meter($lat, $lng, $locLat, $locLng);
                if ($dist <= $radius) {
                    $validDistance = $dist;
                    $lokasiId = $loc->id;
                    break;
                }
            }
            if ($validDistance === null) {
                return $this->respond(false, 'Di luar area lokasi absensi. Absen pulang di luar lokasi hanya diperbolehkan jika menggunakan Mode Dinas atau WFH.');
            }
        }

        // Absen QR murni token + GPS: tanpa kamera, foto, atau pencocokan
        // wajah. Seluruh verifikasi biometrik hanya untuk jalur Face ID.
        $checkoutResult = $this->storeCheckout(
            $userId,
            $todayAttendance,
            $checkOutDate,
            $recordedTime,
            $jamPulangLimit,
            $isWfh,
            $isDinas,
            $lat,
            $lng,
            $lokasiId,
            $validDistance,
            null,
            $accuracy,
            $isMockLocation,
            $keterangan !== '' ? $keterangan : null,
            $setting,
            $isDinas ? 'Absen Pulang QR (Dinas)' : ($isWfh ? 'Absen Pulang QR (WFH)' : 'Absen Pulang QR')
        );

        if (!$checkoutResult['ok']) {
            return $this->respond(false, $checkoutResult['message']);
        }

        $this->notifyEarlyCheckoutDenda(
            $userId,
            (int) ($idGuru ?: ($todayAttendance->id_guru ?? 0)),
            $isDinas,
            $isWfh,
            (string) $checkoutResult['status_pulang'],
            (int) $checkoutResult['pulang_cepat_menit'],
            (float) $checkoutResult['total_denda'],
            (float) $checkoutResult['existing_denda']
        );

        // Log scan pulang bersifat best-effort: absen harian sudah tercatat.
        try {
            $logData = [
                'qr_id' => $qr->id,
                'user_id' => $userId,
                'id_guru' => $idGuru ?: 0,
                'scanned_at' => date('Y-m-d H:i:s'),
                'lat' => $lat ?: null,
                'lng' => $lng ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if ($db->fieldExists('jenis', 'absensi_qr_log')) {
                $logData['jenis'] = 'pulang';
            }
            $db->table('absensi_qr_log')->insert($logData);
        } catch (\Throwable $e) {
            log_message('error', '[qrCheckout] gagal mencatat log QR pulang: ' . $e->getMessage());
        }

        $msg = 'Absen Pulang via QR berhasil: ' . ($qr->nama_acara ?? 'acara');
        if ($isWfh) {
            $msg = 'Absen Pulang WFH via QR berhasil: ' . ($qr->nama_acara ?? 'acara');
        }
        $msg .= $mockWarning;
        if (!$isWfh && ($checkoutResult['status_pulang'] === 'EARLY' || (int) $checkoutResult['pulang_cepat_menit'] > 0)) {
            $msg .= "\n\n" . $this->getRandomMotivation();
        }

        return $this->respond(true, $msg, [
            'qr_event' => $qr->nama_acara ?? '',
            'status_pulang' => $checkoutResult['status_pulang'],
            'pulang_cepat_menit' => (int) $checkoutResult['pulang_cepat_menit'],
        ]);
    }

    /**
     * Pastikan absensi_qr_log punya kolom jenis dan unique (qr_id,user_id,jenis).
     * Idempoten; menjadi jaring pengaman bila migrasi belum dijalankan.
     */
    private function patchAbsensiQrLogJenis($db): void
    {
        static $done = false;
        if ($done || !$db->tableExists('absensi_qr_log')) {
            return;
        }

        try {
            if (!$db->fieldExists('jenis', 'absensi_qr_log')) {
                \Config\Database::forge()->addColumn('absensi_qr_log', [
                    'jenis' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'masuk', 'null' => false],
                ]);
            }
            if (!$db->fieldExists('jenis', 'absensi_qr_log')) {
                return;
            }

            $target = ['jenis', 'qr_id', 'user_id'];
            sort($target);
            $hasTarget = false;
            $indexes = [];
            foreach ($db->query('SHOW INDEX FROM `absensi_qr_log`')->getResult() as $row) {
                if ((int) ($row->Non_unique ?? 1) !== 0) {
                    continue;
                }
                $indexes[$row->Key_name][(int) $row->Seq_in_index] = $row->Column_name;
            }
            foreach ($indexes as $name => $cols) {
                ksort($cols);
                $sorted = array_values($cols);
                sort($sorted);
                if ($sorted === $target) {
                    $hasTarget = true;
                } elseif ($sorted === ['qr_id', 'user_id']) {
                    $db->query('ALTER TABLE `absensi_qr_log` DROP INDEX `' . str_replace('`', '``', $name) . '`');
                }
            }
            if (!$hasTarget) {
                $db->query('ALTER TABLE `absensi_qr_log` ADD UNIQUE KEY `uq_qr_user_jenis` (`qr_id`, `user_id`, `jenis`)');
            }
            $done = true;
        } catch (\Throwable $e) {
            log_message('error', '[patchAbsensiQrLogJenis] ' . $e->getMessage());
        }
    }

    /**
     * Jembatani scan QR menjadi absen masuk harian.
     *
     * Aturan sama dengan check-in face reguler (jadwal shift, ONTIME/LATE,
     * denda progresif + cap bulanan, geofence, hari libur, sinkron poin).
     * WFH/Dinas tidak berlaku untuk jalur QR.
     *
     * Return: ['bridged'=>bool, 'status_masuk'=>?, 'telat_menit'=>?, 'reason'=>?]
     * 'reason' adalah pesan ramah untuk UI bila harian tidak ikut tercatat
     * (scan acara QR tetap sah).
     */
    private function bridgeQrCheckinToHarian(
        int $userId, int $idGuru, float $lat, float $lng,
        float $accuracy, object $qr, int $isMock = 0
    ): array {
        try {
            $db = \Config\Database::connect();
            $this->patchAbsensiHarianTable();
            $setting = $this->settingModel->first();

            $today = date('Y-m-d');
            $now = date('H:i:s');
            $recordedTime = date('Y-m-d H:i:s');

            // Sudah absen masuk hari ini (mis. via face) → cukup catat acara.
            if ($this->attendanceModel->where('user_id', $userId)->where('tgl', $today)->first()) {
                return ['bridged' => false, 'reason' => 'Absen masuk hari ini sudah tercatat sebelumnya.'];
            }

            // Hari libur → acara tercatat, harian tidak.
            if ($this->checkIsHoliday($today)) {
                return ['bridged' => false, 'reason' => 'Hari libur: hanya tercatat sebagai peserta acara.'];
            }

            // Jendela absen masuk: jadwal shift → harian → setting (buka 1 jam
            // sebelum, tutup 3 jam sesudah — sama seperti check-in face).
            $dayOfWeek = date('N', strtotime($today));
            $jadwal = null;
            if ($db->tableExists('absensi_jadwal_kerja')) {
                $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->get()->getRow();
            }
            $shiftModel = new \App\Models\ShiftModel();
            $workSchedule = $shiftModel->getScheduleForUser($userId, (int) $dayOfWeek, $jadwal, $setting);
            $jamMasukLimit = $workSchedule['jam_masuk'];
            $waktuBukaMasuk = date('H:i:s', strtotime($jamMasukLimit) - (1 * 3600));
            $waktuTutupMasuk = date('H:i:s', strtotime($jamMasukLimit) + (3 * 3600));
            if ($now < $waktuBukaMasuk || $now > $waktuTutupMasuk) {
                return ['bridged' => false, 'reason' => 'Di luar jam absen masuk. Hanya tercatat sebagai peserta acara.'];
            }

            // Geofence kantor (wajib — QR tanpa geofence = celah titip scan).
            $locationRows = [];
            try {
                $locationRows = $db->table('absensi_lokasi')->get()->getResult();
            } catch (\Throwable $ex) {
            }
            if (empty($locationRows)) {
                return ['bridged' => false, 'reason' => 'Titik lokasi kantor belum dikonfigurasi. Hanya tercatat sebagai peserta acara.'];
            }
            $validDistance = null;
            $lokasiId = null;
            foreach ($locationRows as $loc) {
                $dist = distance_meter($lat, $lng, (float) $loc->lat, (float) $loc->lng);
                $radius = (float) (!empty($loc->radius_meter) ? $loc->radius_meter : ($setting->radius_meter ?? 100));
                if ($dist <= $radius) {
                    $validDistance = $dist;
                    $lokasiId = $loc->id;
                    break;
                }
            }
            if ($validDistance === null) {
                return ['bridged' => false, 'reason' => 'Di luar area absensi. Hanya tercatat sebagai peserta acara.'];
            }

            // ONTIME / LATE + denda progresif (rumus baku AttendancePolicy,
            // identik dengan checkIn wajah).
            $graceMins = (int) ($workSchedule['grace_menit'] ?? ($setting->grace_period ?? 15));
            $hit = \App\Services\AttendancePolicy::telat($today, $recordedTime, [
                'jam_masuk'   => $jamMasukLimit,
                'grace_menit' => $graceMins,
            ]);
            $statusMasuk     = $hit['status'];
            $telatMenit      = $hit['telat_menit'];
            $datangAwalMenit = $hit['datang_awal_menit'];

            $points = 0;
            $denda = 0;
            $keteranganDenda = null;

            if ($datangAwalMenit > 0) {
                $points += $this->calculateProgressiveRewardPoints($datangAwalMenit, $setting);
            }
            if ($statusMasuk === 'LATE') {
                $calc = $this->calculateProgressivePenalty($telatMenit, $setting, 'Telat Masuk');
                $points = -1 * $calc['points'];
                $denda = $calc['denda'];
                $keteranganDenda = $calc['keterangan'];
            }
            // Cap dihitung ulang DI DALAM transaksi (terkunci) tepat sebelum insert.

            $data = [
                'user_id' => $userId,
                'id_guru' => $idGuru ?: 0,
                'tgl' => $today,
                'jam_masuk' => $recordedTime,
                'lat_masuk' => $lat,
                'lng_masuk' => $lng,
                'lokasi_masuk_id' => $lokasiId,
                'jarak_masuk' => (int) round($validDistance),
                'foto_masuk' => null,
                'status_masuk' => $statusMasuk,
                'points' => $points ?? 0,
                'denda' => $denda ?? 0,
                'keterangan_denda' => $keteranganDenda ?? null,
                'keterangan' => 'Absen via QR: ' . ($qr->nama_acara ?? '-'),
            ];
            if ($db->fieldExists('telat_menit', 'absensi_harian')) {
                $data['telat_menit'] = $telatMenit;
            }
            if ($db->fieldExists('datang_awal_menit', 'absensi_harian')) {
                $data['datang_awal_menit'] = $datangAwalMenit;
            }
            $reqIp = $this->request ? $this->request->getIPAddress() : '127.0.0.1';
            $reqUa = $this->request ? (string) $this->request->getUserAgent() : 'CLI';
            if ($db->fieldExists('ip_address', 'absensi_harian')) {
                $data['ip_address'] = $reqIp;
            }
            if ($db->fieldExists('device_info', 'absensi_harian')) {
                $data['device_info'] = $reqUa . ' (QR Scan)';
            }
            if ($db->fieldExists('gps_accuracy', 'absensi_harian')) {
                $data['gps_accuracy'] = $accuracy > 0 ? $accuracy : null;
            }
            if ($db->fieldExists('is_mock_location', 'absensi_harian')) {
                $data['is_mock_location'] = $isMock;
            }

            $db->transBegin();
            // Cap denda dihitung di sini (terkunci FOR UPDATE) agar konsisten
            // dengan baris yang akan di-insert tepat di bawah.
            if (($denda ?? 0) > 0) {
                $penaltyService = new \App\Services\AttendancePenaltyService();
                $capped = $penaltyService->applyMonthlyCap($userId, $today, (float) $denda, $setting, null, true);
                if ($capped['capped']) {
                    $denda = $capped['total'];
                    $capRp = number_format((float) ($setting->max_denda_bulan ?? 0), 0, ',', '.');
                    $keteranganDenda = trim(($keteranganDenda ?? '') . " (dibatasi cap bulanan Rp{$capRp})");
                    $data['denda'] = $denda;
                    $data['keterangan_denda'] = $keteranganDenda;
                }
            }
            try {
                $insertedId = $this->attendanceModel->insert($data);
            } catch (\Throwable $e) {
                $db->transRollback();
                if ($this->isDuplicateKeyError($e)) {
                    return ['bridged' => false, 'reason' => 'Absen masuk hari ini sudah tercatat sebelumnya.'];
                }
                throw $e;
            }
            if ($insertedId === false) {
                $db->transRollback();
                return ['bridged' => false, 'reason' => 'Gagal mencatat absen harian. Hanya tercatat sebagai peserta acara.'];
            }
            $db->transCommit();

            try {
                \App\Services\GuruTerawalService::afterCheckIn($db, $today, $userId);
            } catch (\Throwable $e) {
                log_message('error', 'Pengumuman guru terawal gagal: ' . $e->getMessage());
            }

            // Sinkron poin (cermin checkIn).
            try {
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($insertedId);
            } catch (\Throwable $e) {
                log_message('error', '[bridgeQrCheckinToHarian] sync poin: ' . $e->getMessage());
            }

            return ['bridged' => true, 'status_masuk' => $statusMasuk, 'telat_menit' => $telatMenit];
        } catch (\Throwable $e) {
            log_message('error', '[bridgeQrCheckinToHarian] ' . $e->getMessage());
            return ['bridged' => false, 'reason' => 'Hanya tercatat sebagai peserta acara.'];
        }
    }

    /**
     * QR Page - Teacher scans this URL to check in or out for today.
     */
    public function qrPage($token)
    {
        if (!session()->get('is_login')) {
            return redirect()->to('auth');
        }
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth')->with('error', 'Halaman QR hanya untuk guru.');
        }

        $db = \Config\Database::connect();
        $qr = \App\Libraries\QrToken::findEvent($db, $token, date('Y-m-d'));

        // Mode ditentukan dari absensi hari ini: belum masuk → check-in,
        // sudah masuk belum pulang → check-out, sudah lengkap → selesai.
        $userId = (int) session()->get('user_id');
        $todayAttendance = null;
        if ($db->tableExists('absensi_harian')) {
            try {
                $todayAttendance = $this->attendanceModel
                    ->where('user_id', $userId)
                    ->where('tgl', date('Y-m-d'))
                    ->first();
            } catch (\Throwable $e) {
                $todayAttendance = null;
            }
        }

        $mode = 'checkin';
        if ($todayAttendance) {
            if (!empty($todayAttendance->jam_pulang)) {
                $mode = 'done';
            } elseif (!empty($todayAttendance->jam_masuk)) {
                $mode = 'checkout';
            }
        }

        // Absen QR murni token + GPS: tanpa kamera, foto, atau verifikasi wajah.
        return view('members/guru/absen_qr', [
            'token' => $token,
            'qr' => $qr,
            'mode' => $mode,
            'today_attendance' => $todayAttendance,
        ]);
    }

    /**
     * Get Active QR Events for Today
     */
    public function getActiveQrEvents()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('absensi_qr')) {
            return $this->respond(true, '', []);
        }

        $events = $db->table('absensi_qr')
            ->where('active', 1)
            ->where('tanggal', date('Y-m-d'))
            ->orderBy('jam_mulai', 'ASC')
            ->get()->getResult();

        // Sertakan token kini per event (secret tidak ikut). Untuk rotating,
        // token berubah tiap interval — klien wajib refresh berkala.
        foreach ($events as $e) {
            $e->current_token = \App\Libraries\QrToken::displayToken($e);
            unset($e->secret);
        }

        return $this->respond(true, '', $events);
    }

    /**
     * Update Login Credentials from Profile
     */
    public function updateLogin()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $userId = (int) session()->get('user_id');
        $idGuru = $this->resolveIdGuru($userId);
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) ($this->request->getPost('new_password') ?? '');

        if ($username === '') {
            return $this->respond(false, 'Username tidak boleh kosong');
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            return $this->respond(false, 'Username 3-50 karakter, hanya huruf, angka, titik, garis bawah, atau tanda hubung.');
        }
        if ($password !== '' && strlen($password) < 8) {
            return $this->respond(false, 'Password baru minimal 8 karakter.');
        }

        $db = \Config\Database::connect();
        $guru = $db->table('master_guru')->where('id_guru', $idGuru)->get()->getRow();

        if (!$guru) {
            return $this->respond(false, 'Data guru tidak ditemukan');
        }

        try {
            // Username harus unik agar AuthModel::login() tidak ambigu.
            $taken = $db->table('users')
                ->where('username', $username)
                ->where('id !=', (int) $guru->id_user)
                ->countAllResults();
            if ($taken > 0) {
                return $this->respond(false, 'Username sudah digunakan akun lain.');
            }

            // Skema tabel users memakai kolom `password` (hash BCRYPT).
            $userUpdate = ['username' => $username];
            if ($password !== '') {
                $userUpdate['password'] = password_hash($password, PASSWORD_BCRYPT);
                // Rotasi ID sesi tanpa menghapus data login (jangan logout paksa).
                $this->session->regenerate(false);
            }

            $db->table('users')->where('id', $guru->id_user)->update($userUpdate);

            // Kolom password di master_guru bersifat legacy plaintext dan
            // tidak dibaca kode mana pun — jangan disimpan.
            $db->table('master_guru')->where('id_guru', $idGuru)->update(['username' => $username]);

            return $this->respond(true, 'Kredensial login berhasil diperbarui.');
        } catch (\Throwable $e) {
            log_message('error', 'Update Login Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal memperbarui kredensial login.', null, null, 500);
        }
    }

        /**
     * Terbitkan kode tautan Telegram 15 menit + deep-link bot.
     * POST presensi/telegram-link (guru, CSRF seperti endpoint lain).
     */
    public function telegramLink()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }
        $userId = (int) session()->get('user_id');
        try {
            $model = new \App\Models\UserNotifikasiModel();
            $existing = $model->getByUserId($userId);
            if ($existing && !empty($existing->telegram_chat_id)) {
                return $this->respond(true, 'Telegram sudah tertaut.', ['linked' => true]);
            }
            $token = $model->issueLinkToken($userId);
            if (!$token) {
                return $this->respond(false, 'Gagal membuat kode tautan.');
            }
            $bot = (new \App\Services\NotificationService())->getBotUsername();
            if ($bot === '') {
                return $this->respond(false, 'Bot Telegram belum dikonfigurasi admin.');
            }
            return $this->respond(true, 'Kode tautan dibuat (berlaku 15 menit).', [
                'linked' => false,
                'token'  => $token,
                'link'   => 'https://t.me/' . $bot . '?start=' . $token,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Attendance::buatKodeTautan] ' . $e->getMessage());
            return $this->respond(false, 'Gagal membuat kode tautan — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Lepas tautan Telegram akun ini. POST presensi/telegram-unlink.
     */
    public function telegramUnlink()
    {        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }
        $userId = (int) session()->get('user_id');
        try {
            $idGuru = $this->resolveIdGuru($userId);
            $ok = \App\Libraries\TelegramLink::detachFromGuru($idGuru, $userId);

            // detachFromGuru() dulu selalu true; sekarang hasilnya jujur.
            // Melaporkan sukses saat penulisan database gagal berarti guru
            // masih menerima pesan padahal mengira tautannya sudah dicabut.
            if (! $ok) {
                return $this->respond(
                    false,
                    'Gagal melepas tautan. Coba lagi; bila tetap gagal, hubungi admin.'
                );
            }

            return $this->respond(true, 'Tautan Telegram dilepas.');
        } catch (\Throwable $e) {
            log_message('error', '[Attendance::lepasTautan] ' . $e->getMessage());
            return $this->respond(false, 'Gagal melepas tautan — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Simpan ID Telegram yang diketik guru sendiri.
     * Tidak memerlukan webhook bot — cukup chat id yang valid.
     * POST presensi/telegram-set-id {telegram_id: "123456789"}
     */
    public function telegramSetId()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }
        $userId = (int) session()->get('user_id');
        try {
            $idGuru = $this->resolveIdGuru($userId);
            $raw = (string) ($this->request->getPost('telegram_id') ?? '');

            if (trim($raw) === '') {
                \App\Libraries\TelegramLink::detachFromGuru($idGuru, $userId);
                return $this->respond(true, 'Tautan Telegram dilepas.', ['chat_id' => '']);
            }

            $result = \App\Libraries\TelegramLink::attachToGuru($idGuru, $userId, $raw);
            if (!$result['ok']) {
                return $this->respond(false, $result['message']);
            }

            return $this->respond(true, $result['message'], ['chat_id' => $result['chat_id']]);
        } catch (\Throwable $e) {
            log_message('error', 'telegramSetId gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyimpan ID Telegram.', null, null, 500);
        }
    }

    /**
     * Status tautan + preferensi notifikasi personal.
     * GET presensi/telegram-prefs.
     */
    public function telegramPrefs()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }
        $userId = (int) session()->get('user_id');
        try {
            $model = new \App\Models\UserNotifikasiModel();
            $row = $model->getByUserId($userId);
            $idGuru = $this->resolveIdGuru($userId);
            return $this->respond(true, 'OK', [
                'linked'  => !empty($row->telegram_chat_id),
                'chat_id' => \App\Libraries\TelegramLink::chatIdForGuru($idGuru, $userId),
                'prefs'   => $model->getPrefs($userId),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Attendance::telegramPrefs] ' . $e->getMessage());
            return $this->respond(false, 'Gagal memuat preferensi — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Uji kiriman pesan ke chat Telegram milik guru yang sedang login.
     *
     * Men-benar-benar memanggil Bot API, jadi ini satu-satunya cara memastikan
     * tautan bukan sekadar ada di database tapi juga bisa dikirimi pesan.
     * Endpoint ini TIDAK menghormati preferensi notifikasi — tujuannya diagnosis.
     *
     * POST presensi/telegram-test
     */
    public function telegramTest()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $userId = (int) session()->get('user_id');

        try {
            // Rate limit: 1 request / 20 detik per akun
            $throttler = \Config\Services::throttler();
            if ($throttler->check('tg_test_' . $userId, 1, 20) === false) {
                return $this->respond(false, 'Terlalu sering. Tunggu 20 detik sebelum mengetes lagi.');
            }

            $idGuru = $this->resolveIdGuru($userId);
            $chatId = \App\Libraries\TelegramLink::chatIdForGuru($idGuru, $userId);

            if ($chatId === '') {
                return $this->respond(false, 'Belum ada ID Telegram tersimpan. Isi kolom ID Telegram di profil terlebih dahulu.', [
                    'stage' => 'belum-taut',
                ]);
            }

            $svc = new \App\Services\NotificationService();

            // Token bot harus ada; tanpa ini mustahil menguji apa pun.
            $setting = (new \App\Models\AttendanceSettingModel())->first();
            if (empty($setting->telegram_bot_token)) {
                return $this->respond(false, 'Bot Token belum diisi admin di Pengaturan → Notifikasi.', [
                    'stage' => 'tanpa-token',
                ]);
            }

            $nama = trim((string) session()->get('name') ?? '');
            if ($nama === '') {
                $nama = 'Guru';
            }

            $msg = "✅ <b>PESAN TES BERHASIL</b>\n"
                 . "Halo <b>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</b>,\n"
                 . "Tautan Telegram Anda aktif. Notifikasi denda, surat teguran, dan "
                 . "peringatan alpha akan dikirim ke chat ini.\n"
                 . "─────────────────\n"
                 . "Chat ID: <code>" . htmlspecialchars($chatId, ENT_QUOTES, 'UTF-8') . "</code>\n"
                 . "Waktu: " . date('d/m/Y H:i');

            $ok = $svc->sendTelegramTo($chatId, $msg);

            if ($ok) {
                return $this->respond(true, 'Pesan tes terkirim. Cek chat Telegram Anda.', [
                    'stage'   => 'terkirim',
                    'chat_id' => $chatId,
                    'bot'     => $svc->getBotUsername(),
                ]);
            }

            // Kegagalan selalu accompanied alasan yang bisa ditindaklanjuti.
            $reason = trim($svc->lastError) !== '' ? $svc->lastError : 'Telegram menolak pengiriman tanpa keterangan.';
            log_message('notice', '[telegram-test] guru user_id=' . $userId . ' chat=' . $chatId . ' gagal: ' . $reason);

            return $this->respond(false, $reason, [
                'stage'   => 'gagal-kirim',
                'chat_id' => $chatId,
            ], null, 200);
        } catch (\Throwable $e) {
            log_message('error', 'telegramTest gagal: ' . $e->getMessage());
            return $this->respond(false, 'Terjadi kesalahan sistem.', null, null, 500);
        }
    }

    /**
     * Simpan preferensi notifikasi personal.
     * POST presensi/telegram-prefs {prefs: {denda, teguran, alpha, kehadiran}}.
     */
    public function telegramPrefsSave()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }
        $userId = (int) session()->get('user_id');
        try {
            $raw = $this->request->getPost('prefs');
            if (is_string($raw)) {
                $raw = json_decode($raw, true);
            }
            if (!is_array($raw)) {
                return $this->respond(false, 'Format preferensi tidak valid.');
            }
            $ok = (new \App\Models\UserNotifikasiModel())->setPrefs($userId, $raw);
            return $ok
                ? $this->respond(true, 'Preferensi disimpan.')
                : $this->respond(false, 'Gagal menyimpan preferensi.');
        } catch (\Throwable $e) {
            log_message('error', '[Attendance::simpanTelegramPrefs] ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyimpan preferensi — periksa log aplikasi.', null, null, 500);
        }
    }

    // ── WhatsApp personal (OpenWA) ────────────────────────────────────

    /**
     * Simpan / lepas nomor WhatsApp guru.
     * POST presensi/wa-link {wa_number: "081234567890"}  (kosong = lepas)
     *
     * Berbeda dari Telegram tidak ada kode tautan: guru mengetik
     * nomornya sendiri. Nomornya TIDAK langsung aktif — menunggu
     * konfirmasi admin, karena OpenWA membalas sukses untuk nomor
     * format benar yang sebenarnya bukan miliknya, tanpa peringatan.
     */
    public function waLink()
    {
        if (! $this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $userId = (int) session()->get('user_id');

        try {
            $idGuru = $this->resolveIdGuru($userId);
            if ($idGuru <= 0) {
                return $this->respond(false, 'Data guru tidak ditemukan.');
            }

            // Kolom tidak terkirim = user menekan "Lepas".
            $raw = $this->request->getPost('wa_number');
            $raw = $raw === null ? '' : (string) $raw;

            $r = \App\Libraries\WhatsAppLink::attachToGuru($idGuru, $userId, $raw);
            if (! $r['ok']) {
                return $this->respond(false, $r['message']);
            }

            return $this->respond(true, $r['message'], [
                'number'   => $r['number'],
                'verified' => $r['number'] !== '' && (new \App\Models\UserNotifikasiModel())->getVerifiedWaNumber($userId) !== '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'waLink gagal: ' . $e->getMessage());
            return $this->respond(false, 'Terjadi kesalahan sistem.', null, null, 500);
        }
    }

    /**
     * POST presensi/wa-test
     *
     * Rate limited dan hanya jalan bila kanal OpenWA aktif.
     */
    public function waTest()
    {
        if (! $this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $userId = (int) session()->get('user_id');

        try {
            $throttler = \Config\Services::throttler();
            if ($throttler->check('wa_test_' . $userId, 1, 20) === false) {
                return $this->respond(false, 'Terlalu sering. Tunggu 20 detik sebelum mengetes lagi.');
            }

            $idGuru = $this->resolveIdGuru($userId);
            $number = \App\Libraries\WhatsAppLink::numberForGuru($idGuru, $userId);

            if ($number === '') {
                return $this->respond(false, 'Belum ada nomor WhatsApp tersimpan. Isi kolomnya di halaman Notifikasi terlebih dahulu.', [
                    'stage' => 'belum-taut',
                ]);
            }

            $svc = new \App\Services\NotificationService();
            if ($svc->waMode() !== 'openwa') {
                return $this->respond(false, 'Gateway WhatsApp belum diaktifkan admin di Pengaturan → Notifikasi.', [
                    'stage' => 'kanal-off',
                ]);
            }

            // Nomor yang belum dikonfirmasi admin tetap boleh diuji: ini
            // justru gunanya, supaya guru memastikan nomornya benar sebelum
            //dinonaktifkan. Pesan tes tidak memuat data absensi pribadi.
            $nama = trim((string) (session()->get('name') ?? '')) ?: 'Guru';
            $msg = "<b>PESAN TES</b>\n"
                 . "Halo <b>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</b>,\n"
                 . "Jika pesan ini sampai, nomor WhatsApp Anda sudah benar dan notifikasi "
                 . "denda, surat teguran, serta peringatan alpha akan dikirim ke sini.\n"
                 . "-----------------\n"
                 . "Nomor: <code>" . htmlspecialchars($number, ENT_QUOTES, 'UTF-8') . "</code>\n"
                 . "Waktu: " . date('d/m/Y H:i');

            $openwa = new \App\Services\OpenWAService();
            $ok = $openwa->sendText($number, $msg);

            if ($ok) {
                return $this->respond(true, 'Pesan tes terkirim. Cek WhatsApp Anda.', [
                    'stage'  => 'terkirim',
                    'number' => $number,
                ]);
            }

            log_message('notice', '[wa-test] guru user_id=' . $userId . ' nomor=' . $number . ' gagal: ' . $openwa->lastError);
            return $this->respond(false, $openwa->lastError !== '' ? $openwa->lastError : 'Gateway menolak pengiriman.', [
                'stage'  => 'gagal-kirim',
                'number' => $number,
            ], null, 200);
        } catch (\Throwable $e) {
            log_message('error', 'waTest gagal: ' . $e->getMessage());
            return $this->respond(false, 'Terjadi kesalahan sistem.', null, null, 500);
        }
    }

    /**
     * Get Notifications API
     * Returns notifications for: leave requests, field duty, corrections, announcements, warnings
     */
    public function getNotifications()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        try {
            $userId = session()->get('user_id');
            $db = \Config\Database::connect();
            $notifications = [];

            // 1. Izin/Cuti Status Notifications
            if ($db->tableExists('absensi_izin')) {
                $permits = $db->table('absensi_izin')
                    ->where('user_id', $userId)
                    ->orderBy('updated_at', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResult();

                foreach ($permits as $permit) {
                    $icon = 'bi-calendar-check-fill';
                    $bgClass = 'bg-blue-soft';
                    $title = '';

                    if ($permit->status === 'approved') {
                        $icon = 'bi-check-circle-fill';
                        $bgClass = 'bg-green-soft';
                        $title = 'Izin Disetujui';
                    } elseif ($permit->status === 'rejected') {
                        $icon = 'bi-x-circle-fill';
                        $bgClass = 'bg-red-soft';
                        $title = 'Izin Ditolak';
                    } else {
                        $title = 'Izin Menunggu Persetujuan';
                    }

                    $notifications[] = [
                        'type' => 'permit',
                        'icon' => $icon,
                        'bgClass' => $bgClass,
                        'title' => $title,
                        'text' => $permit->jenis . ' dari ' . date('d M', strtotime($permit->tgl_mulai)) .
                            ' - ' . date('d M Y', strtotime($permit->tgl_selesai)),
                        'time' => $this->timeAgo($permit->updated_at ?? $permit->created_at),
                        'status' => $permit->status,
                        'created_at' => $permit->created_at ?? date('Y-m-d H:i:s')
                    ];
                }
            }

            // 2. Dinas Status Notifications
            $dinasRecords = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('status_masuk LIKE', 'DINAS%')
                ->orderBy('updated_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResult();

            foreach ($dinasRecords as $dinas) {
                $icon = 'bi-geo-alt-fill';
                $bgClass = 'bg-blue-soft';
                $title = '';

                if ($dinas->status_masuk === 'DINAS_APPROVED') {
                    $icon = 'bi-check-circle-fill';
                    $bgClass = 'bg-green-soft';
                    $title = 'Dinas Luar Disetujui';
                } elseif ($dinas->status_masuk === 'DINAS_REJECTED') {
                    $icon = 'bi-x-circle-fill';
                    $bgClass = 'bg-red-soft';
                    $title = 'Dinas Luar Ditolak';
                } else {
                    $title = 'Dinas Luar Menunggu';
                }

                $notifications[] = [
                    'type' => 'dinas',
                    'icon' => $icon,
                    'bgClass' => $bgClass,
                    'title' => $title,
                    'text' => 'Absensi dinas luar pada ' . date('d M Y', strtotime($dinas->tgl)),
                    'time' => $this->timeAgo($dinas->updated_at ?? $dinas->created_at),
                    'status' => $dinas->status_masuk,
                    'created_at' => $dinas->created_at ?? date('Y-m-d H:i:s')
                ];
            }

            // 3. Correction Status Notifications
            if ($db->tableExists('absensi_koreksi')) {
                $corrections = $db->table('absensi_koreksi')
                    ->where('user_id', $userId)
                    ->orderBy('updated_at', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResult();

                foreach ($corrections as $corr) {
                    $icon = 'bi-pencil-square';
                    $bgClass = 'bg-purple-soft';
                    $title = '';

                    if ($corr->status === 'approved') {
                        $icon = 'bi-check-circle-fill';
                        $bgClass = 'bg-green-soft';
                        $title = 'Koreksi Disetujui';
                    } elseif ($corr->status === 'rejected') {
                        $icon = 'bi-x-circle-fill';
                        $bgClass = 'bg-red-soft';
                        $title = 'Koreksi Ditolak';
                    } else {
                        $title = 'Koreksi Menunggu';
                    }

                    $notifications[] = [
                        'type' => 'correction',
                        'icon' => $icon,
                        'bgClass' => $bgClass,
                        'title' => $title,
                        'text' => 'Koreksi ' . str_replace('_', ' ', $corr->field_name) . ': ' .
                            ($corr->admin_note ?? $corr->reason),
                        'time' => $this->timeAgo($corr->updated_at ?? $corr->created_at),
                        'status' => $corr->status,
                        'created_at' => $corr->created_at ?? date('Y-m-d H:i:s')
                    ];
                }
            }

            $setting = $this->settingModel->first();
            $todayDate = date('Y-m-d');
            $today = $todayDate; // Restore $today variable for usage below
            $startOfMonth = date('Y-m-01');

            // A. Consecutive Absence Check (Check last 14 days)
            $mapRecent = [];
            $recs = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl >=', date('Y-m-d', strtotime('-14 days')))
                ->get()->getResult();
            foreach ($recs as $r)
                $mapRecent[$r->tgl] = true;

            $consecutiveAbsent = 0;
            // Check backward from yesterday to avoid crying about today if not yet checked in
            // Or check from today if it's already past check-in time? Let's check from yesterday to be safe/fair.
            // Actually user request implies current state. Let's check from TODAY if jamming > jam_pulang or check yesterday.
            // Safest is checking from yesterday backwards.
            for ($i = 0; $i < 14; $i++) {
                // Should we count today? If user hasn't checked in yet today, it counts as absent if we check today.
                // Let's assume we check up to yesterday for consecutive alerts to be fair.
                // UNLESS it's already night time.
                // Let's stick to $i=0 means TODAY.
                $d = date('Y-m-d', strtotime("-{$i} days"));

                // If today, and it's still morning, don't count as absent yet.
                if ($d == $todayDate && date('H:i') < ($setting->jam_pulang ?? '15:00')) {
                    continue;
                }

                // Skip hari libur (mingguan + jadwal sekolah + kalender).
                if (\App\Services\AttendancePolicy::isLibur($d)) {
                    continue;
                }

                if (!isset($mapRecent[$d])) {
                    $consecutiveAbsent++;
                } else {
                    // Stop if attended
                    break;
                }
            }

            if ($consecutiveAbsent >= 7) {
                $notifications[] = [
                    'type' => 'warning_severe',
                    'icon' => 'bi-exclamation-octagon-fill',
                    'bgClass' => 'bg-red-soft',
                    'title' => 'Peringatan Keras',
                    'text' => "Anda tidak hadir selama {$consecutiveAbsent} hari kerja berturut-turut. Mohon hubungi admin.",
                    'time' => 'Penting',
                    'status' => 'danger',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } elseif ($consecutiveAbsent >= 3) {
                $notifications[] = [
                    'type' => 'warning',
                    'icon' => 'bi-exclamation-triangle-fill',
                    'bgClass' => 'bg-orange-soft',
                    'title' => 'Peringatan Absensi',
                    'text' => "Anda tidak hadir {$consecutiveAbsent} hari kerja berturut-turut.",
                    'time' => 'Perhatian',
                    'status' => 'warning',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            // B. Monthly Accumulation Check
            $statsRecs = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl >=', $startOfMonth)
                ->where('tgl <=', $todayDate) // Up to today
                ->get()->getResult();

            $totalHadirBulanIni = count($statsRecs);
            $lateCount = 0;
            $hadirEfektifBulanIni = 0;
            foreach ($statsRecs as $s) {
                if ($s->status_masuk === 'LATE')
                    $lateCount++;
                // ALPHA/ALPA BUKAN kehadiran — jangan dihitung hadir
                if (!in_array($s->status_masuk ?? '', ['ALPHA', 'ALPA'], true))
                    $hadirEfektifBulanIni++;
            }

            // Calculate Effective Working Days in this month so far
            $effectiveDays = 0;
            $startTs = strtotime($startOfMonth);
            $endTs = strtotime($todayDate);
            // If today is not over (before jam pulang), maybe exclude today from effective days for fair calculation?
            // Let's include today for simplicity.

            while ($startTs <= $endTs) {
                $checkD = date('Y-m-d', $startTs);

                if (!\App\Services\AttendancePolicy::isLibur($checkD)) {
                    $effectiveDays++;
                }
                $startTs = strtotime('+1 day', $startTs);
            }

            $totalAbsentMonth = $effectiveDays - $hadirEfektifBulanIni;
            // Adjustment: If today is effective day but user hasn't clocked in yet (and it's morning), 
            // effectiveDays counted it, totalHadir didn't. So absent++. 
            // But if it's morning, we shouldn't scold them yet.
            // Let's reduce 1 absent day if today is effective AND time < jam_pulang AND user not present yet.
            if (!isset($mapRecent[$todayDate]) && date('H:i') < ($setting->jam_pulang ?? '15:00')) {
                if (!\App\Services\AttendancePolicy::isLibur($todayDate)) {
                    $totalAbsentMonth = max(0, $totalAbsentMonth - 1);
                }
            }

            // B2. Surat teguran resmi milik guru ini (sumber kebenaran: tabel resmi).
            // Ditampilkan apa pun hasil hitungan kasar di atas supaya guru yang
            // mendapatkan surat pasti melihat & bisa mengunduhnya.
            $bandLevel = 0;
            if ($totalAbsentMonth >= 10) {
                $bandLevel = 10;
            } elseif ($totalAbsentMonth >= 7) {
                $bandLevel = 7;
            } elseif ($totalAbsentMonth >= 3) {
                $bandLevel = 3;
            }

            try {
                $idGuruNotif = 0;
                if ($db && $db->tableExists('master_guru')) {
                    $grow = $db->table('master_guru')->select('id_guru')
                        ->where('id_user', $userId)->get()->getRow();
                    if ($grow)
                        $idGuruNotif = (int) ($grow->id_guru ?? 0);
                }
                if ($idGuruNotif > 0 && $db && $db->tableExists('absensi_surat_teguran')) {
                    $blnIni = (int) date('n');
                    $thnIni = (int) date('Y');
                    $namaBulanPendek = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                    $namaBulanPenuh = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    $levelMap = [
                        10 => ['type' => 'warning_month_10', 'bgClass' => 'bg-red-soft', 'status' => 'danger', 'romawi' => 'III'],
                        7 => ['type' => 'warning_month_7', 'bgClass' => 'bg-orange-soft', 'status' => 'warning', 'romawi' => 'II'],
                        3 => ['type' => 'warning_month_3', 'bgClass' => 'bg-yellow-soft', 'status' => 'warning', 'romawi' => 'I'],
                    ];
                    $letters = $db->table('absensi_surat_teguran')
                        ->where('id_guru', $idGuruNotif)
                        ->where('tahun', $thnIni)
                        ->orderBy('bulan', 'DESC')->orderBy('level', 'DESC')
                        ->limit(3)->get()->getResult();
                    foreach ($letters as $lt) {
                        $lvl = (int) ($lt->level ?? 0);
                        if (!isset($levelMap[$lvl]))
                            continue;
                        
                        // Abaikan surat yang sudah dibatalkan
                        if (($lt->status ?? 'aktif') === 'dibatalkan') {
                            continue;
                        }

                        $blnSurat = (int) ($lt->bulan ?? $blnIni);
                        if ($lvl === $bandLevel && $blnSurat === $blnIni) {
                            $bandLevel = 0; // sudah diwakili surat resmi bulan ini
                        }
                        $meta = $levelMap[$lvl];
                        $blnLbl = $namaBulanPendek[$blnSurat] ?? '';
                        $periode = ($namaBulanPenuh[$blnSurat] ?? '') . ' ' . $thnIni;

                        // Periksa alpha terkini untuk mendeteksi apakah izin disetujui retroaktif
                        $alphaSurat = $this->alphaForLetterPeriod($userId, $blnSurat, $thnIni, $setting, $db);
                        if ($alphaSurat <= 0) {
                            // Jika alpha sudah 0 (izin resmi disetujui setelah surat terbit),
                            // otomatis tandai surat sebagai dibatalkan di DB agar arsip rapi
                            try {
                                if ($db->fieldExists('status', 'absensi_surat_teguran')) {
                                    $db->table('absensi_surat_teguran')
                                        ->where('id', $lt->id)
                                        ->update([
                                            'status' => 'dibatalkan',
                                            'keterangan_batal' => 'Izin/dispensasi resmi disetujui'
                                        ]);
                                }
                            } catch (\Throwable $e) {}
                            continue;
                        }

                        // Gunakan snapshot jumlah_alpha dari database agar angka konsisten dan berkekuatan tetap
                        $displayAlpha = (int) ($lt->jumlah_alpha ?? 0);
                        if ($displayAlpha <= 0) {
                            $displayAlpha = max($lvl, $alphaSurat);
                        }

                        $notifications[] = [
                            'type' => $meta['type'],
                            'icon' => 'bi-file-earmark-pdf-fill',
                            'bgClass' => $meta['bgClass'],
                            'title' => "Teguran Tingkat {$meta['romawi']} ({$blnLbl})",
                            'text' => "{$displayAlpha} hari tidak hadir • {$periode}",
                            'time' => ($blnSurat === $blnIni) ? 'Bulan ini' : "{$blnLbl} {$thnIni}",
                            'status' => $meta['status'],
                            'action_label' => 'Unduh PDF',
                            'action_url' => site_url('presensi/surat/' . (int) ($lt->id ?? 0)),
                            'action_icon' => 'bi-file-earmark-pdf-fill',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Surat resmi gagal dimuat — lanjut ke notifikasi hitungan kasar
            }

            // Fallback: hitungan kasar bila belum ada surat resmi selevel bulan ini
            if ($bandLevel >= 10) {
                $notifications[] = [
                    'type' => 'warning_month_10',
                    'icon' => 'bi-file-earmark-pdf-fill',
                    'bgClass' => 'bg-red-soft',
                    'title' => 'Surat Teguran (10 Hari)',
                    'text' => "Absensi Anda mencapai {$totalAbsentMonth} hari tidak hadir bulan ini.",
                    'time' => 'Bulan ini',
                    'status' => 'danger',
                    'action_label' => 'Unduh PDF',
                    'action_url' => site_url('presensi/download-teguran/10'),
                    'action_icon' => 'bi-file-earmark-pdf-fill',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } elseif ($bandLevel >= 7) {
                $notifications[] = [
                    'type' => 'warning_month_7',
                    'icon' => 'bi-file-earmark-pdf-fill',
                    'bgClass' => 'bg-orange-soft',
                    'title' => 'Surat Teguran (7 Hari)',
                    'text' => "Absensi Anda mencapai {$totalAbsentMonth} hari tidak hadir bulan ini.",
                    'time' => 'Bulan ini',
                    'status' => 'warning',
                    'action_label' => 'Unduh PDF',
                    'action_url' => site_url('presensi/download-teguran/7'),
                    'action_icon' => 'bi-file-earmark-pdf-fill',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } elseif ($bandLevel >= 3) {
                $notifications[] = [
                    'type' => 'warning_month_3',
                    'icon' => 'bi-file-earmark-pdf-fill',
                    'bgClass' => 'bg-yellow-soft',
                    'title' => 'Surat Teguran (3 Hari)',
                    'text' => "Absensi Anda mencapai {$totalAbsentMonth} hari tidak hadir bulan ini.",
                    'time' => 'Bulan ini',
                    'status' => 'warning',
                    'action_label' => 'Unduh PDF',
                    'action_url' => site_url('presensi/download-teguran/3'),
                    'action_icon' => 'bi-file-earmark-pdf-fill',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }


            // C. Late Warning (e.g. > 5 times a month)
            if ($lateCount >= 5) {
                $notifications[] = [
                    'type' => 'warning_late',
                    'icon' => 'bi-alarm-fill',
                    'bgClass' => 'bg-yellow-soft',
                    'title' => 'Sering Terlambat',
                    'text' => "Anda tercatat terlambat {$lateCount} kali bulan ini. Harap perbaiki ketepatan waktu.",
                    'time' => 'Bulan ini',
                    'status' => 'warning',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            // 5. Upcoming Holidays/Announcements from hari libur
            if ($db->tableExists('absensi_hari_libur')) {
                $upcomingHolidays = $db->table('absensi_hari_libur')
                    ->where('tanggal >=', $today)
                    ->where('tanggal <=', date('Y-m-d', strtotime('+30 days')))
                    ->orderBy('tanggal', 'ASC')
                    ->limit(5)
                    ->get()
                    ->getResult();

                foreach ($upcomingHolidays as $holiday) {
                    $notifications[] = [
                        'type' => 'announcement',
                        'icon' => 'bi-calendar-event',
                        'bgClass' => 'bg-blue-soft',
                        'title' => 'Hari Libur',
                        'text' => $holiday->keterangan . ' - ' . date('d M Y', strtotime($holiday->tanggal)),
                        'time' => $this->timeAgo($holiday->tanggal),
                        'status' => 'info',
                        'created_at' => $holiday->tanggal
                    ];
                }
            }

            // Sort all notifications by created_at DESC
            usort($notifications, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Limit to 50 most recent
            $notifications = array_slice($notifications, 0, 50);

            return $this->respond(true, 'Notifications fetched', $notifications);

        } catch (\Throwable $e) {
            log_message('error', 'Get Notifications Error: ' . $e->getMessage());
            return $this->respond(false, 'Gagal mengambil notifikasi', null, null, 500);
        }
    }

    /**
     * Helper to convert datetime to "time ago" format
     */
    private function timeAgo($datetime)
    {
        if (!$datetime)
            return 'Baru saja';

        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' menit yang lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam yang lalu';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' hari yang lalu';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' minggu yang lalu';
        } else {
            return date('d M Y', $timestamp);
        }
    }

    /**
     * JSON history (weekly & monthly) + warnings + calendar data
     */
    public function history()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->respond(false, 'Sesi tidak valid', null, null, 401);
        }

        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-6 days'));

        // Support optional ?month=YYYY-MM param for browsing past months
        $monthParam = $this->request->getGet('month');
        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $monthStart = $monthParam . '-01';
            $monthEnd   = date('Y-m-t', strtotime($monthStart));
        } else {
            $monthStart = date('Y-m-01');
            $monthEnd   = date('Y-m-t');
        }

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }

        $recordsWeek = [];
        $recordsMonth = [];
        try {
            if ($db && $db->tableExists('absensi_harian')) {
                $recordsWeek = $this->attendanceModel
                    ->where('user_id', $userId)
                    ->where('tgl >=', $weekStart)
                    ->findAll();
                $recordsMonth = $this->attendanceModel
                    ->where('user_id', $userId)
                    ->where('tgl >=', $monthStart)
                    ->findAll();
            }
        } catch (\Throwable $e) {
            $recordsWeek = [];
            $recordsMonth = [];
        }

        // Map by date
        $mapWeek = [];
        foreach ($recordsWeek as $r) {
            $mapWeek[$r->tgl] = $r;
        }
        $mapMonth = [];
        foreach ($recordsMonth as $r) {
            $mapMonth[$r->tgl] = $r;
        }

        // count absent days in last 7 days
        $absentConsecutive = 0;
        $warningAbsent = false;
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            if (isset($mapWeek[$d])) {
                $absentConsecutive = 0;
            } else {
                $absentConsecutive++;
            }
            if ($absentConsecutive >= 3) {
                $warningAbsent = true;
                break;
            }
        }

        $jamMasuk = '07:00:00';
        $jamPulang = '15:00:00';
        try {
            if ($db && $db->tableExists('absensi_setting')) {
                $setting = $this->settingModel->first();
                if ($setting) {
                    $jamMasuk = $setting->jam_masuk ?? $jamMasuk;
                    $jamPulang = $setting->jam_pulang ?? $jamPulang;
                }
            }
        } catch (\Throwable $e) {
        }

        $todayRec = $mapWeek[$today] ?? null;
        $warningLate = $todayRec && $todayRec->status_masuk === 'LATE';
        $warningEarly = $todayRec && $todayRec->status_pulang === 'EARLY';
        $thanksOver = $todayRec && strtotime($todayRec->jam_pulang ?? '') > strtotime(date('Y-m-d ') . $jamPulang) + 3600;

        // Fetch Approved Leaves (Overlapping Month)
        $leavesMonth = [];
        $izinBulan = [];
        try {
            if ($db && $db->tableExists('absensi_izin')) {
                $izinBulan = $this->leaveModel
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->groupStart()
                    ->where("tgl_mulai <= '$monthEnd'")
                    ->where("tgl_selesai >= '$monthStart'")
                    ->groupEnd()
                    ->findAll();
            }
        } catch (\Throwable $e) {
            $izinBulan = [];
        }

        // Calculate Stats
        $stats = [
            'hadir' => count($recordsMonth),
            'telat_kali' => 0,
            'telat_menit' => 0,
            'dinas' => 0,
            'izin_hari' => 0,
            'percentage' => 0
        ];

        foreach ($recordsMonth as $r) {
            $statusMasuk = $r->status_masuk ?? '';
            $telatMenit = $r->telat_menit ?? 0;

            if ($statusMasuk === 'LATE' || $telatMenit > 0) {
                $stats['telat_kali']++;
                $stats['telat_menit'] += $telatMenit;
            }
            if (strpos($statusMasuk, 'DINAS') !== false) {
                $stats['dinas']++;
            }
        }

        // Map Leave Days
        $leaveMap = [];
        foreach ($izinBulan as $iz) {
            $start = strtotime($iz->tgl_mulai);
            $end = strtotime($iz->tgl_selesai);

            $currL = $start;
            while ($currL <= $end) {
                $d = date('Y-m-d', $currL);
                if ($d >= $monthStart && $d <= $monthEnd) {
                    $leaveMap[$d] = true;
                }
                $currL = strtotime('+1 day', $currL);
            }
        }
        $stats['izin_hari'] = count($leaveMap);

        // Percentage Calculation
        $effectiveWorkDays = 0;
        $currentTime = strtotime($monthStart);
        $nowTime = strtotime($today);

        while ($currentTime <= $nowTime) {
            $dateStr = date('Y-m-d', $currentTime);

            // Libur = mingguan + jadwal sekolah (is_libur) + kalender,
            // satu sumber dengan modul absensi lain.
            if (!\App\Services\AttendancePolicy::isLibur($dateStr)) {
                if (!isset($leaveMap[$dateStr])) {
                    $effectiveWorkDays++;
                }
            }
            $currentTime = strtotime('+1 day', $currentTime);
        }

        if ($effectiveWorkDays > 0) {
            $stats['percentage'] = round(($stats['hadir'] / $effectiveWorkDays) * 100, 1);
            if ($stats['percentage'] > 100)
                $stats['percentage'] = 100;
        } else {
            $stats['percentage'] = $stats['hadir'] > 0 ? 100 : 0;
        }

        $disciplineRank = null;
        try {
            if ($db && $db->tableExists('absensi_harian') && $db->tableExists('users_groups')) {
                $monthPrefix = substr($monthStart, 0, 7);
                $hasTelatMenit = $db->fieldExists('telat_menit', 'absensi_harian');
                $hasPulangCepat = $db->fieldExists('pulang_cepat_menit', 'absensi_harian');
                $hasDatangAwal = $db->fieldExists('datang_awal_menit', 'absensi_harian');
                $hasPulangAkhir = $db->fieldExists('pulang_akhir_menit', 'absensi_harian');

                $scoreExpr = \App\Services\AttendancePolicy::sqlExprSkorHarian('a', [
                    'telat_menit'        => $hasTelatMenit,
                    'pulang_cepat_menit' => $hasPulangCepat,
                    'datang_awal_menit'  => $hasDatangAwal,
                    'pulang_akhir_menit' => $hasPulangAkhir,
                ]);

                $userAgg = $db->table('absensi_harian a')
                    ->select('a.user_id')
                    ->select("SUM(CASE WHEN a.status_masuk = 'ONTIME' OR a.status_masuk LIKE 'DINAS%' THEN 1 ELSE 0 END) as ontime", false)
                    ->select("SUM(CASE WHEN a.status_masuk = 'LATE' AND a.status_masuk NOT LIKE 'DINAS%' THEN 1 ELSE 0 END) as late", false)
                    ->select($hasTelatMenit ? "COALESCE(SUM(CASE WHEN a.status_masuk LIKE 'DINAS%' THEN 0 ELSE a.telat_menit END), 0) as total_telat_menit" : "0 as total_telat_menit", false)
                    ->select($hasDatangAwal ? "COALESCE(SUM(a.datang_awal_menit), 0) as total_datang_awal" : "0 as total_datang_awal", false)
                    ->select($hasPulangAkhir ? "COALESCE(SUM(a.pulang_akhir_menit), 0) as total_pulang_akhir" : "0 as total_pulang_akhir", false)
                    ->select("$scoreExpr as score", false)
                    ->join('users_groups ug', 'ug.user_id = a.user_id')
                    ->where('ug.group_id', 2)
                    ->where('a.user_id', $userId)
                    ->like('a.tgl', $monthPrefix, 'after')
                    ->groupBy('a.user_id')
                    ->get()->getRow();

                if ($userAgg) {
                    $monthLike = $monthPrefix . '%';
                    $baseFrom = "FROM absensi_harian a JOIN users_groups ug ON ug.user_id = a.user_id WHERE ug.group_id = 2 AND a.tgl LIKE ?";

                    $totalSql = "SELECT COUNT(*) as total FROM (SELECT a.user_id $baseFrom GROUP BY a.user_id) t";
                    $totalRow = $db->query($totalSql, [$monthLike])->getRow();
                    $total = (int) ($totalRow->total ?? 0);

                    $rankSql = "SELECT COUNT(*) + 1 as rank FROM (SELECT a.user_id, $scoreExpr as score $baseFrom GROUP BY a.user_id) t WHERE t.score > ?";
                    $rankRow = $db->query($rankSql, [$monthLike, (float) $userAgg->score])->getRow();
                    $rank = (int) ($rankRow->rank ?? 0);

                    $disciplineRank = [
                        'rank' => $rank,
                        'total' => $total,
                        'score' => (float) $userAgg->score,
                        'ontime' => (int) $userAgg->ontime,
                        'late' => (int) $userAgg->late,
                        'telat_menit' => (int) $userAgg->total_telat_menit,
                        'datang_awal' => (int) $userAgg->total_datang_awal,
                        'pulang_akhir' => (int) $userAgg->total_pulang_akhir
                    ];
                }
            }
        } catch (\Throwable $e) {
            $disciplineRank = null;
        }

        // Build Calendar
        $calendarData = [];
        $currentDate = strtotime($monthStart);
        $endDate = strtotime($monthEnd);

        while ($currentDate <= $endDate) {
            $dateStr = date('Y-m-d', $currentDate);
            $dayName = date('D', $currentDate);
            $dayData = [
                'date' => $dateStr,
                'day' => date('j', $currentDate),
                'dayName' => $dayName,
                'status' => 'none',
                'color' => '#e5e7eb' // gray
            ];

            // Holiday (mingguan + jadwal sekolah + kalender)
            if (\App\Services\AttendancePolicy::isLibur($dateStr)) {
                $dayData['status'] = 'holiday';
                $dayData['color'] = '#94a3b8';
            }

            // Attendance
            if (isset($mapMonth[$dateStr])) {
                $rec = $mapMonth[$dateStr];
                $status = $rec->status_masuk ?? '';

                if (strpos($status, 'DINAS') !== false) {
                    $dayData['status'] = 'dinas';
                    $dayData['color'] = '#3b82f6';
                } elseif ($status === 'LATE') {
                    $dayData['status'] = 'late';
                    $dayData['color'] = '#f59e0b';
                } elseif ($status === 'ONTIME') {
                    $dayData['status'] = 'present';
                    $dayData['color'] = '#10b981';
                }
            }

            if ($currentDate > time()) {
                $dayData['status'] = 'future';
                $dayData['color'] = '#f3f4f6';
            }

            $calendarData[] = $dayData;
            $currentDate = strtotime('+1 day', $currentDate);
        }

        return $this->respond(true, 'Data history', [
            'week' => $recordsWeek,
            'month' => $recordsMonth,
            'stats' => $stats,
            'discipline_rank' => $disciplineRank,
            'calendar' => $calendarData,
            'warning_absent' => $warningAbsent,
            'warning_late' => $warningLate,
            'warning_early' => $warningEarly,
            'thanks_over' => $thanksOver
        ]);
    }

    public function ranking()
    {
        log_message('debug', 'Ranking API called: ' . $this->request->getUri());
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $db = \Config\Database::connect();
        $this->patchAbsensiHarianTable();
        $monthPrefix = date('Y-m');

        $hasTelatMenit = $db->fieldExists('telat_menit', 'absensi_harian');
        $hasPulangCepat = $db->fieldExists('pulang_cepat_menit', 'absensi_harian');
        $hasDatangAwal = $db->fieldExists('datang_awal_menit', 'absensi_harian');
        $hasPulangAkhir = $db->fieldExists('pulang_akhir_menit', 'absensi_harian');

        $scoreExpr = \App\Services\AttendancePolicy::sqlExprSkorHarian('a', [
            'telat_menit'        => $hasTelatMenit,
            'pulang_cepat_menit' => $hasPulangCepat,
            'datang_awal_menit'  => $hasDatangAwal,
            'pulang_akhir_menit' => $hasPulangAkhir,
        ]);

        try {
            $ranking = $db->table('absensi_harian a')
                ->select("a.user_id, u.first_name, u.last_name, u.username, u.foto as user_foto, mg.nama_guru, mg.foto as guru_foto")
                ->select(\App\Services\AttendancePolicy::sqlTermHadir('a') . ' as total_hadir', false)
                ->select("SUM(CASE WHEN a.status_masuk NOT LIKE 'DINAS%' AND (a.status_pulang IS NULL OR a.status_pulang NOT LIKE 'DINAS%') THEN 1 ELSE 0 END) as hadir_reguler", false)
                ->select("SUM(CASE WHEN a.status_masuk LIKE 'DINAS%' OR a.status_pulang LIKE 'DINAS%' THEN 1 ELSE 0 END) as hadir_dinas", false)
                ->select("SUM(CASE WHEN a.status_masuk = 'ONTIME' OR a.status_masuk LIKE 'DINAS%' THEN 1 ELSE 0 END) as ontime", false)
                ->select("SUM(CASE WHEN a.status_masuk = 'LATE' AND a.status_masuk NOT LIKE 'DINAS%' THEN 1 ELSE 0 END) as late", false)
                ->select($hasTelatMenit ? "COALESCE(SUM(CASE WHEN a.status_masuk LIKE 'DINAS%' THEN 0 ELSE a.telat_menit END), 0) as total_telat_menit" : "0 as total_telat_menit", false)
                ->select($hasDatangAwal ? "COALESCE(SUM(a.datang_awal_menit), 0) as total_datang_awal" : "0 as total_datang_awal", false)
                ->select($hasPulangAkhir ? "COALESCE(SUM(a.pulang_akhir_menit), 0) as total_pulang_akhir" : "0 as total_pulang_akhir", false)
                ->select("SUM(COALESCE(CASE WHEN a.status_masuk LIKE 'DINAS%' OR a.status_pulang LIKE 'DINAS%' THEN 0 ELSE a.denda END, 0)) as total_denda", false)
                ->select("$scoreExpr as score", false)
                ->join('users u', 'u.id = a.user_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->join('master_guru mg', 'mg.id_user = u.id', 'left')
                ->where('ug.group_id', 2)
                ->where('u.active', 1)
                ->like('a.tgl', $monthPrefix, 'after')
                ->groupBy('a.user_id, u.first_name, u.last_name, u.username, u.foto, mg.nama_guru, mg.foto')
                ->orderBy('score', 'DESC', false)
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', 'Ranking API query gagal: ' . $e->getMessage());
            return $this->respond(false, 'Data ranking belum dapat dimuat.', null, null, 500);
        }

        try {
            $setting = $this->settingModel->first();
        } catch (\Throwable $e) {
            $setting = $this->getDefaultSettings();
        }

        try {
            $alphaFine = (int) ($setting->denda_alpha_per_hari ?? 0);
            if (!empty($ranking)) {
                $userIds = array_map(static fn($r) => (int) ($r->user_id ?? 0), $ranking);
                $userIds = array_values(array_filter(array_unique($userIds)));

                if ($alphaFine > 0) {
                    $penaltySvcMonth = new \App\Services\AttendancePenaltyService();
                    $mStart = $monthPrefix . '-01';
                    $mEnd = date('Y-m-t', strtotime($mStart));
                    foreach ($ranking as $r) {
                        $uid = (int) ($r->user_id ?? 0);
                        $missMonth = $penaltySvcMonth->missingAlphaFines($uid, $mStart, $mEnd, $setting, false);
                        $alphaCount = (int) ($missMonth['count'] ?? 0);
                        $r->alpha = $alphaCount;
                        $r->alpha_denda = (float) ($missMonth['total'] ?? 0);
                        $r->total_denda = (float) ($r->total_denda ?? 0) + (float) ($missMonth['total'] ?? 0);
                    }
                }

                // Calculate 1-Year YTD Fine, Payments (Cash vs Poin), and Sisa
                $yearPrefix = date('Y');
                $yearStart = $yearPrefix . '-01-01';
                $yearEnd = date('Y-m-d');

                $ytdFines = [];
                if ($db->tableExists('absensi_harian')) {
                    $ytdRows = $db->table('absensi_harian')
                        ->select('user_id, SUM(COALESCE(denda, 0)) as total_denda_harian')
                        ->where('tgl >=', $yearStart)
                        ->where('tgl <=', $yearEnd)
                        ->groupBy('user_id')
                        ->get()->getResult();
                    foreach ($ytdRows as $yr) {
                        $ytdFines[(int)$yr->user_id] = (float)$yr->total_denda_harian;
                    }
                }

                $cashPayments = [];
                $poinPayments = [];
                $saldoPayments = [];
                if ($db->tableExists('absensi_pembayaran_denda')) {
                    $pays = $db->table('absensi_pembayaran_denda')
                        ->where('tahun', $yearPrefix)
                        ->where('status !=', 'batal')
                        ->get()->getResult();
                    foreach ($pays as $p) {
                        $uid = (int)$p->user_id;
                        $nom = (float)(!empty($p->nominal_bayar) ? $p->nominal_bayar : $p->total_denda);
                        $kelas = \App\Services\AttendancePenaltyService::kelasPembayaranDenda($p->metode_bayar, $p->keterangan ?? null);
                        if ($kelas === 'POIN') {
                            $poinPayments[$uid] = ($poinPayments[$uid] ?? 0.0) + $nom;
                        } elseif ($kelas === 'SALDO') {
                            $saldoPayments[$uid] = ($saldoPayments[$uid] ?? 0.0) + $nom;
                        } else {
                            $cashPayments[$uid] = ($cashPayments[$uid] ?? 0.0) + $nom;
                        }
                    }
                }

                if ($db->tableExists('reward_redemptions') && $db->tableExists('rewards')) {
                    try {
                        $hasRedemptionLink = $db->tableExists('absensi_pembayaran_denda') && $db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda');
                        $sql = "SELECT rr.user_id, COALESCE(r.nominal, 0) as nominal 
                                FROM reward_redemptions rr 
                                JOIN rewards r ON rr.reward_id = r.id 
                                WHERE rr.status = 'APPROVED' AND YEAR(rr.updated_at) = '{$yearPrefix}'";
                        if ($hasRedemptionLink) {
                            $sql .= " AND rr.id NOT IN (SELECT COALESCE(reward_redemption_id, 0) FROM absensi_pembayaran_denda WHERE reward_redemption_id IS NOT NULL)";
                        }
                        $rws = $db->query($sql)->getResult();
                        foreach ($rws as $rw) {
                            $uid = (int)$rw->user_id;
                            $poinPayments[$uid] = ($poinPayments[$uid] ?? 0.0) + (float)$rw->nominal;
                        }
                    } catch (\Throwable $e) {}
                }

                // Progressive alpha YTD — SATU rentang penuh agar streak kontinu
                // + filter recorded agar tidak double-count denda yang sudah ada di absensi_harian.
                $userAlphaMapYear = [];
                $userAlphaDendaMapYear = [];
                if ($alphaFine > 0) {
                    $penaltyRanking = new \App\Services\AttendancePenaltyService();
                    $scanStartRank = date('Y-m-d', strtotime($yearStart . ' -62 days'));
                    try {
                        $allSummaryRank = $penaltyRanking->calculateAllUsersPeriodAlphaSummary($scanStartRank, $yearEnd, $setting);
                    } catch (\Throwable $e) {
                        $allSummaryRank = [];
                    }

                    // Recorded: tanggal yang sudah ada denda di absensi_harian — skip agar tidak double-count
                    $recordedRank = [];
                    try {
                        $recRowsRank = $db->table('absensi_harian')
                            ->select('user_id, tgl')
                            ->where('tgl >=', $yearStart)
                            ->where('tgl <=', $yearEnd)
                            ->where('denda >', 0)
                            ->get()->getResult();
                        foreach ($recRowsRank as $rr) {
                            $recordedRank[(int) $rr->user_id][substr($rr->tgl, 0, 10)] = true;
                        }
                    } catch (\Throwable $e) {}

                    $todayRank = date('Y-m-d');
                    foreach ($allSummaryRank as $uidTmp => $infoTmp) {
                        $uidTmp = (int) $uidTmp;
                        $missDenda = 0.0;
                        $missCount = 0;
                        foreach (($infoTmp['items'] ?? []) as $it) {
                            $t = substr($it['tgl'] ?? '', 0, 10);
                            if ($t < $yearStart || $t > $yearEnd || $t >= $todayRank) {
                                continue;
                            }
                            if (isset($recordedRank[$uidTmp][$t])) {
                                continue;
                            }
                            $missDenda += (float)($it['denda'] ?? 0);
                            $missCount++;
                        }
                        if ($missCount > 0) {
                            $userAlphaMapYear[$uidTmp] = $missCount;
                            $userAlphaDendaMapYear[$uidTmp] = $missDenda;
                        }
                    }
                }

                foreach ($ranking as $r) {
                    $uid = (int) ($r->user_id ?? 0);
                    $totDendaHarian = $ytdFines[$uid] ?? 0.0;
                    $totAlphaYear = (float)($userAlphaDendaMapYear[$uid] ?? 0);
                    $totalYtd = $totDendaHarian + $totAlphaYear;
                    
                    $cash = $cashPayments[$uid] ?? 0.0;
                    $poin = $poinPayments[$uid] ?? 0.0;
                    $saldo = $saldoPayments[$uid] ?? 0.0;
                    $dibayar = $cash + $poin + $saldo;
                    $sisa = max(0, $totalYtd - $dibayar);

                    $r->total_denda_ytd = $totalYtd;
                    $r->total_dibayar_ytd = $dibayar;
                    $r->dibayar_cash_ytd = $cash;
                    $r->dibayar_poin_ytd = $poin;
                    $r->dibayar_saldo_ytd = $saldo;
                    $r->sisa_denda_ytd = $sisa;
                }
            }
        } catch (\Throwable $e) {
        }

        // Foto dan nama diselesaikan sekali di sini: view dan tab Leaderboard
        // tinggal memakai, tidak lagi menebak letak file di disk.
        foreach ($ranking as $r) {
            $r->nama   = \App\Services\AvatarService::namaUtama($r);
            $r->avatar = \App\Services\AvatarService::untukBaris($r);
        }

        return $this->respond(true, 'Ranking data', $ranking);
    }

    public function dendaDetail()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $targetUserId = (int) ($this->request->getGet('user_id') ?? 0);
        if ($targetUserId <= 0) {
            $targetUserId = (int) session()->get('user_id');
        }
        if ($targetUserId <= 0) {
            return $this->respond(false, 'Parameter user_id tidak valid', null, null, 400);
        }
        $sessionUid = (int) session()->get('user_id');
        if ($targetUserId !== $sessionUid && !$this->isAdmin() && !$this->inGroup('admin')) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $month = (string) ($this->request->getGet('month') ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiHarianTable();

        if (!$db->tableExists('absensi_harian') || !$db->tableExists('users_groups')) {
            return $this->respond(true, 'Detail denda', [
                'user_id' => $targetUserId,
                'month' => $month,
                'total_denda' => 0,
                'items' => []
            ]);
        }

        $hasTelatMenit = $db->fieldExists('telat_menit', 'absensi_harian');
        $hasPulangCepat = $db->fieldExists('pulang_cepat_menit', 'absensi_harian');

        $rows = $db->table('absensi_harian a')
            ->select('a.tgl')
            ->select('COALESCE(a.denda, 0) as denda', false)
            ->select('COALESCE(a.points, 0) as points', false)
            ->select('a.keterangan_denda')
            ->select($hasTelatMenit ? 'COALESCE(a.telat_menit, 0) as telat_menit' : '0 as telat_menit', false)
            ->select($hasPulangCepat ? 'COALESCE(a.pulang_cepat_menit, 0) as pulang_cepat_menit' : '0 as pulang_cepat_menit', false)
            ->select('u.first_name, u.last_name, u.username')
            ->join('users u', 'u.id = a.user_id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('a.user_id', $targetUserId)
            ->like('a.tgl', $month, 'after')
            ->where('a.denda >', 0)
            ->orderBy('a.tgl', 'ASC')
            ->get()->getResult();

        $items = [];
        $totalDenda = 0.0;
        $displayName = '';
        $existingDates = [];
        foreach ($rows as $r) {
            $totalDenda += (float) ($r->denda ?? 0);
            if (!empty($r->tgl)) {
                $existingDates[$r->tgl] = true;
            }
            $displayName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
            if ($displayName === '') {
                $displayName = $r->username ?? '';
            }

            $items[] = [
                'tgl' => $r->tgl ?? null,
                'denda' => (float) ($r->denda ?? 0),
                'points' => (int) ($r->points ?? 0),
                'telat_menit' => (int) ($r->telat_menit ?? 0),
                'pulang_cepat_menit' => (int) ($r->pulang_cepat_menit ?? 0),
                'keterangan_denda' => $r->keterangan_denda ?? ''
            ];
        }

        $setting = null;
        try {
            $setting = $this->settingModel->first();
        } catch (\Throwable $e) {
            $setting = null;
        }
        if (!$setting) {
            $setting = $this->getDefaultSettings();
        }

        $alphaFine = (int) ($setting->denda_alpha_per_hari ?? 0);
        $alphaCount = 0;
        if ($alphaFine > 0) {
            // Estimasi kanonik tanggal bolong (recorded-aware, streak kontinu, tanpa hari ini).
            $startM = $month . '-01';
            $endM = date('Y-m-t', strtotime($startM));
            $penaltySvcDenda = new \App\Services\AttendancePenaltyService();
            $miss = $penaltySvcDenda->missingAlphaFines($targetUserId, $startM, $endM, $setting, false);
            $alphaCount = (int) ($miss['count'] ?? 0);
            foreach (($miss['items'] ?? []) as $mi) {
                $items[] = [
                    'tgl' => $mi['tgl'],
                    'denda' => (float) ($mi['denda'] ?? 0),
                    'points' => -abs((int) ($setting->point_alpa ?? 5)),
                    'telat_menit' => 0,
                    'pulang_cepat_menit' => 0,
                    'keterangan_denda' => ($mi['keterangan'] ?? '') . ' — Belum tercatat di absensi_harian'
                ];
                $totalDenda += (float) ($mi['denda'] ?? 0);
            }
        }

        if ($displayName === '') {
            try {
                $u = $db->table('users')->select('first_name, last_name, username')->where('id', $targetUserId)->get()->getRow();
                if ($u) {
                    $displayName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    if ($displayName === '') {
                        $displayName = $u->username ?? '';
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        usort($items, static fn($a, $b) => strcmp((string)($a['tgl'] ?? ''), (string)($b['tgl'] ?? '')));

        return $this->respond(true, 'Detail denda', [
            'user_id' => $targetUserId,
            'display_name' => $displayName,
            'month' => $month,
            'total_denda' => $totalDenda,
            'alpha' => $alphaCount,
            'items' => $items
        ]);
    }

    public function rekapDenda()
    {
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth');
        }

        $userId = session()->get('user_id');
        $db = \Config\Database::connect();
        $this->patchAbsensiHarianTable();
        
        $yearPrefix = date('Y');
        $startDate = $yearPrefix . '-01-01';
        $endDate = date('Y-m-d');

        // Get basic setting
        $setting = null;
        try {
            $setting = $this->settingModel->first();
        } catch (\Throwable $e) {}
        if (!$setting) $setting = $this->getDefaultSettings();

        // Items array untuk timeline rekap denda (alpha flat per hari)
        $items = [];
        $totalYtd = 0.0;
        $penaltySvc = new \App\Services\AttendancePenaltyService();
        $baseAlpha = (float) ($setting->denda_alpha_per_hari ?? 5000);

        if ($db->tableExists('absensi_harian')) {
            $records = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl >=', $startDate)
                ->where('tgl <=', $endDate)
                ->where('denda >', 0)
                ->orderBy('tgl', 'DESC')
                ->get()->getResult();

            foreach ($records as $r) {
                $totalYtd += (float) $r->denda;
                $statusUpper = strtoupper(trim((string) ($r->status_masuk ?? '')));
                $isAlphaRecord = in_array($statusUpper, ['ALPHA', 'ALPA'], true);

                if ($isAlphaRecord) {
                    // Alpha yang sudah tercatat (oleh cron/manual) — tarif flat
                    $ketDenda = trim((string) ($r->keterangan_denda ?? ''));
                    if ($ketDenda === '') {
                        $ketDenda = 'Alpa (Rp ' . number_format($baseAlpha, 0, ',', '.') . ')';
                    }

                    $items[] = [
                        'tgl' => $r->tgl,
                        'jenis' => 'Alpha / Tanpa Keterangan',
                        'nominal' => (float) $r->denda,
                        'keterangan' => $ketDenda,
                        'is_alpha' => true,
                        'alpha_hari_ke' => 1,
                        'alpha_multiplier' => 1.0,
                        'alpha_base' => $baseAlpha,
                        'status' => $statusUpper,
                    ];
                } else {
                    // Keterlambatan / pulang cepat — label asli
                    $keterangan = [];
                    if (isset($r->telat_menit) && $r->telat_menit > 0) $keterangan[] = "Telat {$r->telat_menit} menit";
                    if (isset($r->pulang_cepat_menit) && $r->pulang_cepat_menit > 0) $keterangan[] = "Cepat pulang {$r->pulang_cepat_menit} menit";
                    if (!empty($r->status_masuk) && $r->status_masuk !== 'ONTIME') $keterangan[] = "Status: {$r->status_masuk}";
                    // gunakan keterangan_denda jika ada (sudah berisi breakdown progresif telat)
                    $keteranganDenda = trim((string) ($r->keterangan_denda ?? ''));
                    $keteranganStr = $keteranganDenda !== '' ? $keteranganDenda : (empty($keterangan) ? '-' : implode(', ', $keterangan));

                    $items[] = [
                        'tgl' => $r->tgl,
                        'jenis' => 'Keterlambatan / Cepat Pulang',
                        'nominal' => (float) $r->denda,
                        'keterangan' => $keteranganStr,
                        'is_alpha' => false,
                        'status' => $statusUpper,
                        'telat_menit' => (int) ($r->telat_menit ?? 0),
                        'pulang_cepat_menit' => (int) ($r->pulang_cepat_menit ?? 0),
                    ];
                }
            }
        }

        // Alpha yang belum tercatat (missing row) — estimasi kanonik flat:
        // hanya tanggal bolong (tanpa hari ini).
        $alphaFineInt = (int) ($setting->denda_alpha_per_hari ?? 0);
        if ($alphaFineInt > 0 && $db->tableExists('absensi_harian')) {
            $miss = $penaltySvc->missingAlphaFines((int) $userId, $startDate, $endDate, $setting, false);
            foreach (($miss['items'] ?? []) as $mi) {
                $fineDenda = (float) ($mi['denda'] ?? 0);
                $items[] = [
                    'tgl' => $mi['tgl'],
                    'jenis' => 'Alpha / Tanpa Keterangan',
                    'nominal' => $fineDenda,
                    'keterangan' => ($mi['keterangan'] ?? '') . ' — Belum tercatat di absensi_harian',
                    'is_alpha' => true,
                    'alpha_hari_ke' => 1,
                    'alpha_multiplier' => 1.0,
                    'alpha_base' => $baseAlpha,
                    'is_missing' => true,
                    'status' => 'ALPHA',
                ];
                $totalYtd += $fineDenda;
            }
        }

        // Sort items by date desc
        usort($items, static fn($a, $b) => strcmp((string)($b['tgl'] ?? ''), (string)($a['tgl'] ?? '')));

        // Get all payments (TUNAI, POIN, SALDO)
        $totalDibayar = 0.0;
        if ($db->tableExists('absensi_pembayaran_denda')) {
            $allPayments = $db->table('absensi_pembayaran_denda')
                ->where('user_id', $userId)
                ->where('tahun', $yearPrefix)
                ->where('status !=', 'batal')
                ->orderBy('created_at', 'DESC')
                ->get()->getResult();

            foreach ($allPayments as $p) {
                $nom = (float)(!empty($p->nominal_bayar) ? $p->nominal_bayar : $p->total_denda);
                $totalDibayar += $nom;
                $metode = strtoupper(trim((string)($p->metode_bayar ?? 'TUNAI')));
                $tgl = !empty($p->tanggal_bayar) ? $p->tanggal_bayar : substr($p->created_at ?? date('Y-m-d'), 0, 10);
                $label = !empty($p->keterangan) ? $p->keterangan : ('Pembayaran Denda (' . $metode . ')');
                $items[] = [
                    'tgl' => $tgl,
                    'jenis' => $label,
                    'nominal' => (-1) * abs($nom),
                    'keterangan' => 'Periode: Bln ' . $p->bulan . '/' . $p->tahun,
                ];
            }
        }

        // 4. Calculate Potongan Reward
        $potonganReward = 0.0;
        if ($db->tableExists('reward_redemptions') && $db->tableExists('rewards')) {
            try {
                $userIdQuery = isset($targetUserId) ? (int)$targetUserId : (int)$userId;
                $hasNominal = $db->fieldExists('nominal', 'rewards');
                $nominalCol = $hasNominal ? 'COALESCE(r.nominal, 0)' : '0';

                // Cegah pemotongan ganda: voucher yang sudah masuk ke absensi_pembayaran_denda
                // tidak dihitung ulang di potonganReward
                $hasRedemptionLink = $db->tableExists('absensi_pembayaran_denda') && $db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda');
                $excludeRedeemedSql = $hasRedemptionLink
                    ? " AND rr.id NOT IN (SELECT COALESCE(reward_redemption_id, 0) FROM absensi_pembayaran_denda WHERE user_id = {$userIdQuery} AND reward_redemption_id IS NOT NULL)"
                    : "";

                $rewardRedemptions = $db->query("
                    SELECT rr.updated_at as created_at, {$nominalCol} as nominal, r.nama as keterangan
                    FROM reward_redemptions rr
                    JOIN rewards r ON rr.reward_id = r.id
                    WHERE rr.user_id = {$userIdQuery} AND rr.status = 'APPROVED' AND YEAR(rr.updated_at) = '{$yearPrefix}'
                    {$excludeRedeemedSql}
                ")->getResult();
                
                foreach ($rewardRedemptions as $rr) {
                    $potonganReward += (float)$rr->nominal;
                    if (isset($items)) {
                        $items[] = [
                            'tgl' => substr($rr->created_at, 0, 10),
                            'jenis' => 'Potongan Reward',
                            'nominal' => (-1) * abs((float)$rr->nominal),
                            'keterangan' => 'Reward: ' . $rr->keterangan,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        }

        $sisaDenda = $totalYtd - $totalDibayar - $potonganReward;
        if ($sisaDenda < 0) $sisaDenda = 0;

        // Re-sort items by date desc
        usort($items, static fn($a, $b) => strcmp((string)($b['tgl'] ?? ''), (string)($a['tgl'] ?? '')));

        return view('members/guru/rekap_denda', [
            'setting' => $setting,
            'totalYtd' => $totalYtd,
            'totalDibayar' => $totalDibayar,
            'sisaDenda' => $sisaDenda,
            'items' => $items,
            'year' => $yearPrefix
        ]);
    }

    public function dendaYtd()
    {
        if (!$this->inGroup(['guru', 'admin']) && !$this->isAdmin()) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $targetUserId = (int) ($this->request->getGet('user_id') ?? session()->get('user_id'));
        if ($targetUserId <= 0) {
            return $this->respond(false, 'Parameter user_id tidak valid', null, null, 400);
        }
        $sessionUid2 = (int) session()->get('user_id');
        if ($targetUserId !== $sessionUid2 && !$this->isAdmin() && !$this->inGroup('admin')) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $db = \Config\Database::connect();
        $this->patchAbsensiHarianTable();
        
        $yearPrefix = date('Y');
        $startDate = $yearPrefix . '-01-01';
        $endDate = date('Y-m-d');

        // 1. Calculate YTD Denda from absensi_harian
        $totalYtd = 0.0;
        if ($db->tableExists('absensi_harian')) {
            $row = $db->table('absensi_harian')
                ->select('SUM(COALESCE(denda, 0)) as total')
                ->where('user_id', $targetUserId)
                ->where('tgl >=', $startDate)
                ->where('tgl <=', $endDate)
                ->get()->getRow();
            $totalYtd += (float) ($row->total ?? 0);
        }

        // 2. Calculate YTD Alpha — flat per hari (via service)
        $setting = null;
        try {
            $setting = $this->settingModel->first();
        } catch (\Throwable $e) {}
        if (!$setting) {
            $setting = $this->getDefaultSettings();
        }

        if ($db->tableExists('absensi_harian')) {
            // Estimasi kanonik: hanya tanggal bolong (recorded-aware, tanpa hari ini).
            $penaltyYtd = new \App\Services\AttendancePenaltyService();
            $missYtd = $penaltyYtd->missingAlphaFines((int) $targetUserId, $startDate, $endDate, $setting, false);
            $totalAlphaProg = (float) ($missYtd['total'] ?? 0);
            $totalYtd += $totalAlphaProg;
        }

        // 3. Calculate Total Dibayar (Cash vs Poin/Voucher vs Saldo)
        $dibayarCash = 0.0;
        $dibayarPoin = 0.0;
        $dibayarSaldo = 0.0;
        if ($db->tableExists('absensi_pembayaran_denda')) {
            $records = $db->table('absensi_pembayaran_denda')
                ->where('user_id', $targetUserId)
                ->where('tahun', $yearPrefix)
                ->where('status !=', 'batal')
                ->get()->getResult();
            
            foreach ($records as $pay) {
                $nominal = (float) (!empty($pay->nominal_bayar) ? $pay->nominal_bayar : $pay->total_denda);
                $kelas = \App\Services\AttendancePenaltyService::kelasPembayaranDenda($pay->metode_bayar, $pay->keterangan ?? null);
                if ($kelas === 'POIN') {
                    $dibayarPoin += $nominal;
                } else if ($kelas === 'SALDO') {
                    $dibayarSaldo += $nominal;
                } else {
                    $dibayarCash += $nominal;
                }
            }
        }

        // 4. Calculate Potongan Reward from reward_redemptions (if not linked)
        $potonganReward = 0.0;
        if ($db->tableExists('reward_redemptions') && $db->tableExists('rewards')) {
            try {
                $hasRedemptionLink = $db->tableExists('absensi_pembayaran_denda') && $db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda');
                if ($hasRedemptionLink) {
                    $rewardRedemptions = $db->query("
                        SELECT COALESCE(r.nominal, 0) as nominal
                        FROM reward_redemptions rr
                        JOIN rewards r ON rr.reward_id = r.id
                        WHERE rr.user_id = {$targetUserId} AND rr.status = 'APPROVED' AND YEAR(rr.updated_at) = '{$yearPrefix}'
                          AND rr.id NOT IN (SELECT COALESCE(reward_redemption_id, 0) FROM absensi_pembayaran_denda WHERE user_id = {$targetUserId} AND reward_redemption_id IS NOT NULL)
                    ")->getResult();
                } else {
                    $rewardRedemptions = $db->query("
                        SELECT COALESCE(r.nominal, 0) as nominal
                        FROM reward_redemptions rr
                        JOIN rewards r ON rr.reward_id = r.id
                        WHERE rr.user_id = {$targetUserId} AND rr.status = 'APPROVED' AND YEAR(rr.updated_at) = '{$yearPrefix}'
                    ")->getResult();
                }
                
                foreach ($rewardRedemptions as $rr) {
                    $potonganReward += (float)($rr->nominal ?? 0);
                }
            } catch (\Throwable $e) {}
        }
        $dibayarPoin += $potonganReward;
        $totalDibayar = $dibayarCash + $dibayarPoin + $dibayarSaldo;

        // Klamp sama seperti RekapPdfService::getYTDDendaStats(). Bayar lebih
        // (voucher Rp 50k untuk tunggakan Rp 20k, atau pembayaran tahun lalu
        // yang tercatat di tahun ini) jangan tampil sebagai "sisa denda -Rp 30rb".
        $sisaDenda = max(0, $totalYtd - $totalDibayar);
        // 5. Calculate Denda Bulan Ini (Harian + Alpha Bulan Ini) agar identik dengan Admin
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $dendaHarianBulanIni = 0.0;
        if ($db->tableExists('absensi_harian')) {
            $rowBln = $db->table('absensi_harian')
                ->select('SUM(COALESCE(denda, 0)) as total')
                ->where('user_id', $targetUserId)
                ->where('tgl >=', $monthStart)
                ->where('tgl <=', $monthEnd)
                ->get()->getRow();
            $dendaHarianBulanIni = (float) ($rowBln->total ?? 0);
        }
        $missBln = $penaltyYtd->missingAlphaFines((int) $targetUserId, $monthStart, $monthEnd, $setting, false);
        $dendaBulanIni = $dendaHarianBulanIni + (float) ($missBln['total'] ?? 0);

        return $this->respond(true, 'Data Denda YTD', [
            'denda_bulan_ini' => $dendaBulanIni,
            'total_denda_ytd' => $totalYtd,
            'total_dibayar' => $totalDibayar,
            'dibayar_cash' => $dibayarCash,
            'dibayar_poin' => $dibayarPoin,
            'dibayar_saldo' => $dibayarSaldo,
            'sisa_denda' => $sisaDenda
        ]);
    }

    /**
     * Ajukan izin/cuti
     */
    public function leaveRequest()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $userId = session()->get('user_id');

        // Check pending requests - maksimal 3 pending.
        // Bentuk angka legacy ikut dihitung supaya pengajuan lama tidak
        // lolos dari pembatas tiga ini di instalasi yang statusnya belum
        // dinormalisasi.
        $pendingCount = $this->leaveModel
            ->where('user_id', $userId)
            ->whereIn('status', \App\Models\LeaveModel::STATUS_PENDING)
            ->countAllResults();

        if ($pendingCount >= 3) {
            return $this->respond(false, 'Anda memiliki ' . $pendingCount . ' pengajuan yang belum disetujui. Mohon hubungi administrator untuk pengecekan pengajuan sebelumnya.');
        }

        // Check Duration
        $tglMulai = $this->request->getPost('tgl_mulai');
        $tglSelesai = $this->request->getPost('tgl_selesai');
        $jenis = strtolower(trim((string) $this->request->getPost('jenis')));

        // Validasi format tanggal & jenis (dulu: DateTime mentah bisa 500,
        // tgl_mulai > tgl_selesai tetap dihitung positif, jenis bebas vs ENUM DB)
        $allowedJenis = ['ijin', 'izin', 'sakit', 'cuti', 'dinas'];
        if (!in_array($jenis, $allowedJenis, true)) {
            return $this->respond(false, 'Jenis pengajuan tidak valid.');
        }
        if ($jenis === 'izin') {
            $jenis = 'ijin'; // normalisasi ke ENUM absensi_izin
        }
        try {
            $start = new \DateTime($tglMulai);
            $end = new \DateTime($tglSelesai);
        } catch (\Throwable $e) {
            return $this->respond(false, 'Format tanggal tidak valid (YYYY-MM-DD).');
        }
        if ($start->format('Y-m-d') !== substr((string) $tglMulai, 0, 10)
            || $end->format('Y-m-d') !== substr((string) $tglSelesai, 0, 10)) {
            return $this->respond(false, 'Format tanggal tidak valid (YYYY-MM-DD).');
        }
        if ($start > $end) {
            return $this->respond(false, 'Tanggal mulai tidak boleh setelah tanggal selesai.');
        }

        // Pengajuan tidak boleh mundur: minimal hari ini (appTimezone). Guru
        // yang meninggalkan tab lama terbuka, atau mengirim POST langsung,
        // tetap berhenti di sini — bukan hanya di browser.
        $hariIni = new \DateTime(date('Y-m-d'));
        if ($start < $hariIni) {
            return $this->respond(false, 'Tanggal pengajuan tidak boleh mundur. Ajukan mulai '
                . date('d/m/Y') . ' ke depan; untuk tanggal yang sudah lewat, gunakan pengajuan koreksi.');
        }

        // Cuti punya batas lebih ketat daripada jenis lain: harus diajukan
        // beberapa hari sebelum tanggal mulai (HARI_MAJU_PENGAJUAN), kecuali
        // kategori kedaruratan — ceknya di bawah, setelah kategori diketahui.
        $diff = $end->diff($start)->days + 1;

        // Bentuk kanonik dipakai seluruh query di bawah — bukan string
        // POST mentah, supaya tidak ada ruang berbeda antara yang
        // divalidasi dan yang disimpan.
        $tglMulai   = $start->format('Y-m-d');
        $tglSelesai = $end->format('Y-m-d');

        // ── Kategori cuti (Cuti Tahunan/Besar/Melahirkan/Alasan Penting) ──
        //
        // Sesuai aturan hak cuti PNS, kini jenis `cuti` dibekali kategori
        // (kolom `kategori_cuti`). Kategori NULL tetap berlaku untuk
        // pegawai non-ASN: cuti generik yang memotong kuota cuti tahunan.
        $kategoriCuti = null;
        if ($jenis === 'cuti') {
            $kategoriCuti = strtolower(trim((string) $this->request->getPost('kategori_cuti')));
            if ($kategoriCuti !== '' && ! \App\Services\CutiKategoriService::valid($kategoriCuti)) {
                return $this->respond(false, 'Kategori cuti tidak valid.');
            }
            if ($kategoriCuti === '') {
                $kategoriCuti = null; // cuti generik (tanpa kategori)
            } else {
                // Eligibilitas: status kepegawaian, jenis kelamin, dan
                // masa kerja (TMT) dibaca langsung dari master_guru.
                $profilCuti = \App\Services\CutiKategoriService::profil((int) $userId);
                $cekElig = \App\Services\CutiKategoriService::cekEligibilitas($kategoriCuti, $profilCuti, $tglMulai);
                if (! $cekElig['ok']) {
                    return $this->respond(false, $cekElig['pesan']);
                }

                // Batas durasi kategori memakai HARI KALENDER: "3 bulan"
                // tetap 3 bulan walau defisit hari kerja.
                $batasKat = \App\Services\CutiKategoriService::batasHariKategori($kategoriCuti);
                if ($batasKat > 0) {
                    $hariKal = \App\Services\CutiKategoriService::hariKalender($tglMulai, $tglSelesai);
                    if ($hariKal > $batasKat) {
                        $lblKat = \App\Services\CutiKategoriService::label($kategoriCuti);
                        return $this->respond(false, 'Maksimal durasi ' . $lblKat . ' adalah ' . $batasKat . ' hari. Pengajuan Anda: ' . $hariKal . ' hari.');
                    }
                }

                // Peminjam Cuti Besar hangus hak Cuti Tahunan pada tahun
                // yang berjalan — pengajuan kategori tahunan/generik pada
                // tahun yang sama ditolak.
                if (\App\Services\CutiKategoriService::pakaiKuotaTahunan((string) $kategoriCuti)
                    && \App\Services\CutiKategoriService::besarAktifTahun(
                        (int) $userId,
                        (int) substr($tglMulai, 0, 4)
                    )) {
                    return $this->respond(false, 'Anda sedang/akan menjalankan Cuti Besar pada tahun ini, sehingga Cuti Tahunan tahun berjalan tidak dapat diajukan.');
                }
            }
        }

        // Cuti biasa (tahunan/besar/generik) wajib diajukan beberapa hari
        // maju; kategori kedaruratan (cap, melahirkan) dikecualikan karena
        // kejadiannya tidak bisa direncanakan — sumber daftarnya satu tempat
        // di CutiKategoriService, ikut dipakai frontend.
        if ($jenis === 'cuti' && \App\Services\CutiKategoriService::butuhHariMaju($kategoriCuti)) {
            $majuCuti = \App\Services\CutiKategoriService::HARI_MAJU_PENGAJUAN;
            $palingCepat = (clone $hariIni)->modify('+' . $majuCuti . ' days');
            if ($start < $palingCepat) {
                return $this->respond(false, 'Cuti harus diajukan minimal ' . $majuCuti . ' hari sebelum tanggal mulai. '
                    . 'Tanggal mulai paling cepat ' . $palingCepat->format('d/m/Y') . '.');
            }
        }

        // Satu instance dipakai untuk seluruh perhitungan di method ini
        // (konteks libur cukup dibaca satu kali).
        $kuota = new \App\Services\IzinKuotaService(\Config\Database::connect(), $this->settingModel->first());

        // Lampiran: dicek KETERSEDIAANNYA dulu, file-nya belum disentuh.
        $fileBukti     = $this->request->getFile('bukti');
        $errorLampiran = '';

        if ($jenis === 'cuti') {
            $errorLampiran = 'Untuk pengajuan Cuti, wajib melampirkan surat cuti.';
        } elseif ($jenis === 'ijin') {
            $errorLampiran = 'Untuk pengajuan Izin, wajib melampirkan bukti screenshot ijin via wa/sms ke kepala sekolah.';
        } elseif ($jenis === 'sakit' && $kuota->hitungHari($tglMulai, $tglSelesai) > 3) {
            // Pakai hitungan yang sama dengan kuota. Kalau aturan ini memakai
            // hari kalender sementara kuota memakai hari kerja, sakit 3 hari
            // kerja yang membentang sampai akhir pekan tetap dipaksa lampirkan
            // surat dokter padahal tidak menyentuh kuota.
            $errorLampiran = 'Untuk sakit lebih dari 3 hari kerja, wajib melampirkan surat keterangan dokter.';
        }
        if ($errorLampiran !== '' && (!$fileBukti || !$fileBukti->isValid() || $fileBukti->hasMoved())) {
            return $this->respond(false, $errorLampiran);
        }

        // Validasi mime + ukuran sebelum file dipindahkan, supaya file
        // tidak pernah menyentuh disk hanya untuk ditolak.
        if ($fileBukti && $fileBukti->isValid() && !$fileBukti->hasMoved()) {
            // Tipe dibaca dari ISI file (guessExtension), bukan dari header
            // Content-Type kiriman browser: berkas skrip yang mengaku image/jpeg
            // dulu lolos whitelist klien, lalu getRandomName() memakai MIME asli
            // sehingga berkas yang tersimpan di uploads/izin ikut berekstensi php.
            if (!in_array(strtolower($fileBukti->guessExtension()), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return $this->respond(false, 'Bukti harus berupa PDF/JPG/PNG.');
            }
            if ($fileBukti->getSize() > 2 * 1024 * 1024) {
                return $this->respond(false, 'Ukuran bukti maksimal 2 MB.');
            }
        }

        // ── Kuota: dicek SEBELUM file diunggah dan sebelum insert ──────────
        //
        // Urutannya penting. Sebelumnya file dipindahkan ke uploads/izin
        // lebih dulu, baru batas durasi dan kuota diperiksa; setiap
        // penolakan setelah titik itu meninggalkan berkas yatim di disk
        // yang tidak pernah dirujuk baris DB mana pun.
        //
        // Batas per pengajuan, kuota tahunan, dan hitungan hari kerja
        // efektif kini dipegang IzinKuotaService — sama persis dengan yang
        // dipakai Admin\PresensiAdmin::saveManualPermit(), jadi guru dan
        // admin tidak lagi bisa melihat angka berbeda.
        //
        // Hitung + insert dibungkus transaksi yang mengunci baris guru:
        // dua request yang datang bersamaan untuk guru yang sama tidak
        // lagi bisa membaca snapshot kuota yang sama lalu dua-duanya lolos.
        $db = \Config\Database::connect();
        $cek      = null;
        $namaBaru = '';
        $db->transBegin();
        try {
            // Serialisasi per guru. Tanpa lock ini, dua tab yang dikirim
            // bersamaan sama-sama membaca kuota yang sama lalu sama-sama
            // lolos, sehingga kuota terlampaui.
            if ($db->tableExists('users')) {
                try {
                    $db->query('SELECT id FROM ' . $db->prefixTable('users') . ' WHERE id = ? FOR UPDATE', [$userId]);
                } catch (\Throwable $e) {
                    // SQLite tidak mendukung FOR UPDATE; transaksi single-
                    // writer-nya sudah cukup untuk unit test SQLite.
                }
            }

            // Overlap diperiksa DI DALAM lock juga. Kalau dicek di luar,
            // dua request bersamaan bisa sama-sama lolos overlap maupun
            // kuota pada waktu yang sama.
            $overlap = $this->leaveModel
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('tgl_mulai <=', $tglSelesai)
                ->where('tgl_selesai >=', $tglMulai)
                ->countAllResults();
            if ($overlap > 0) {
                $db->transRollback();
                return $this->respond(false, 'Rentang tanggal bertabrakan dengan pengajuan lain yang masih aktif.');
            }

            // Kuota dihitung ulang di dalam transaksi: nilai yang dipakai
            // adalah nilai saat lock didapat, bukan hasil baca di luar.
            // Kategori khusus (besar/melahirkan/cap) tidak makan kuota
            // tahunan — batasnya sudah divalidasi di atas.
            $cek = $kuota->cek((int) $userId, $jenis, $tglMulai, $tglSelesai, 0, $kategoriCuti);
            if (! $cek['ok']) {
                $db->transRollback();
                return $this->respond(false, $cek['pesan']);
            }

            $buktiPath = null;
            if ($fileBukti && $fileBukti->isValid() && !$fileBukti->hasMoved()) {
                $uploadDir = FCPATH . 'uploads/izin/';
                if (! is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $newName     = $fileBukti->getRandomName();
                $fileBukti->move($uploadDir, $newName);
                $buktiPath   = 'uploads/izin/' . $newName;
                $namaBaru    = $newName;
            }

            $this->leaveModel->insert([
                'user_id'     => $userId,
                'id_guru'     => $this->resolveIdGuru((int) $userId),
                'tgl_mulai'   => $tglMulai,
                'tgl_selesai' => $tglSelesai,
                'jenis'       => $jenis,
                // Cuti generik non-ASN → NULL, tidak dipaksa ''
                'kategori_cuti' => $kategoriCuti,
                'keterangan'  => $this->request->getPost('keterangan'),
                'status'      => 'pending',
                'bukti'       => $buktiPath,
            ]);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            // Berkas sudah sempat naik ke disk sebelum insert gagal —
            // bersihkan supaya tidak jadi yatim.
            if (isset($namaBaru) && $namaBaru !== '' && file_exists(FCPATH . 'uploads/izin/' . $namaBaru)) {
                @unlink(FCPATH . 'uploads/izin/' . $namaBaru);
            }
            log_message('error', 'Leave request gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal menyimpan pengajuan. Silakan coba lagi.', null, null, 500);
        }


        // Kabari grup agar admin tahu ada antrean menunggu penilaian.
        // Best-effort: kegagalan Telegram tidak boleh membatalkan pengajuan.
        try {
            $guruRow = $db->table('master_guru')->select('nama_guru')
                ->where('id_user', $userId)->get()->getRow();
            $namaGuru = $guruRow->nama_guru ?? ('User ' . $userId);
            (new \App\Services\NotificationService())->notifyPermitSubmitted(
                $namaGuru,
                $jenis,
                $start->format('d/m/Y'),
                $end->format('d/m/Y'),
                // Hari kerja efektif, bukan hari kalender: inilah angka
                // yang akan memotong kuota dan menghasilkan baris absensi.
                (int) ($cek['hari'] ?? $diff),
                // Kategori disertakan ke jalur notifikasi agar verifikator
                // tahu hak mana yang dipakai tanpa membuka DB.
                trim(($kategoriCuti !== null && \App\Services\CutiKategoriService::valid($kategoriCuti)
                        ? '[' . \App\Services\CutiKategoriService::label($kategoriCuti) . '] '
                        : '') . (string) ($this->request->getPost('keterangan') ?? '')),
                (int) $userId
            );
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi pengajuan izin gagal: ' . $e->getMessage());
        }

        return $this->respond(true, 'Permohonan terkirim');
    }

    /**
     * Get pending leave request count for current user
     */
    public function getPendingLeaveCount()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $userId = session()->get('user_id');

        $pendingCount = $this->leaveModel
            ->where('user_id', $userId)
            ->whereIn('status', \App\Models\LeaveModel::STATUS_PENDING)
            ->countAllResults();

        return $this->respond(true, 'Pending count fetched', ['count' => $pendingCount]);
    }

    /**
     * Sisa kuota izin/cuti/sakit/dinas untuk guru yang sedang login.
     *
     * Sebelumnya guru hanya bisa tahu sisa kuotanya setelah submit
     * ditolak — jadi form sudah terisi, bukti sudah dipilih, baru muncul
     * error. Endpoint ini supaya form bisa menampilkan sisa kuota sejak
     * awal dan kategori yang sudah habis otomatis disables.
     */
    public function leaveQuota()
    {
        if (! $this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $userId = (int) session()->get('user_id');
        $tahun  = (int) ($this->request->getGet('tahun') ?? date('Y'));
        if ($tahun < 2000 || $tahun > (int) date('Y') + 1) {
            $tahun = (int) date('Y');
        }

        try {
            $svc    = new \App\Services\IzinKuotaService();
            $kuota  = $svc->ringkasan($userId, $tahun);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memuat sisa kuota izin: ' . $e->getMessage());
            return $this->respond(false, 'Gagal memuat sisa kuota.', null, null, 500);
        }

        return $this->respond(true, 'Sisa kuota Loaded', [
            'tahun' => $tahun,
            'kuota' => $kuota,
        ]);
    }


    private function saveBase64Image(string $base64, string $folder): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                return null;
            }
            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                return null;
            }
            if (strlen($decoded) > 2 * 1024 * 1024) {
                return null;
            }
            $folderPath = FCPATH . $folder;
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $fileName = uniqid('absen_', true) . '.' . $type;
            $filePath = $folderPath . '/' . $fileName;
            if (file_put_contents($filePath, $decoded, LOCK_EX) === false) {
                return null;
            }
            return $folder . '/' . $fileName;
        }
        return null;
    }

    /**
     * Simpan foto selfie absen dengan nama file UNIK per user, slot & waktu
     * (masuk_{userId}_{Ymd_His}_{rand}.ext) sehingga URL foto tiap hari
     * berbeda — browser / Service Worker PWA tidak akan menyajikan foto
     * kemarin dari cache. Berkas lama dibersihkan otomatis (>30 hari)
     * saat upload baru + job bulanan PresensiAdmin.
     */
    private function saveAbsenPhoto(string $base64, int $userId, string $slot): ?string
    {
        $slot = ($slot === 'pulang') ? 'pulang' : 'masuk';
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'], true)) {
                return null;
            }
            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                return null;
            }
            if (strlen($decoded) > 2 * 1024 * 1024) {
                return null;
            }
            $folder = 'uploads/absen';
            $folderPath = FCPATH . $folder;
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // --- Resize + kompres (max 800px, quality 70%) ---
            $img = @imagecreatefromstring($decoded);
            if ($img !== false) {
                $w = imagesx($img); $h = imagesy($img);
                $max = 800;
                if ($w > $max || $h > $max) {
                    $ratio = min($max / $w, $max / $h);
                    $nw = (int)($w * $ratio); $nh = (int)($h * $ratio);
                    $thumb = imagecreatetruecolor($nw, $nh);
                    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagedestroy($img);
                    $img = $thumb;
                }
                ob_start();
                imagejpeg($img, null, 70);
                $decoded = ob_get_clean();
                imagedestroy($img);
                $type = 'jpg'; // hasil selalu jpg
            }
            // --------------------------------------------------

            try {
                $rand = bin2hex(random_bytes(3));
            } catch (\Throwable $e) {
                $rand = uniqid();
            }
            $fileName = $slot . '_' . $userId . '_' . date('Ymd_His') . '_' . $rand . '.' . $type;
            $filePath = $folderPath . '/' . $fileName;
            if (file_put_contents($filePath, $decoded, LOCK_EX) === false) {
                return null;
            }

            // Auto-hapus file user ini > 30 hari
            $cutoff = time() - (30 * 86400);
            foreach (glob($folderPath . '/' . $slot . '_' . $userId . '_*.*') as $old) {
                if (is_file($old) && filemtime($old) < $cutoff) @unlink($old);
            }

            return $folder . '/' . $fileName;
        }
        return null;
    }

    /**
     * @deprecated Tidak lagi dipakai sejak foto memakai nama unik per hari.
     * Dibiarkan sebagai no-op agar riwayat foto hari lama tetap utuh.
     */
    private function clearOldAbsenPhotos(int $userId, string $today): void
    {
        return;
    }

    /**
     * Profile page
     */
    public function profile()
    {
        try {
            if (!$this->inGroup('guru'))
                return redirect()->to('auth');

            $userId = (int) session()->get('user_id');
            $idGuru = $this->resolveIdGuru($userId);

            $db = \Config\Database::connect();

            // Prioritize fetching from master_guru
            $builder = $db->table('master_guru a')
                ->select('a.*, lg.level as jabatan')
                ->join('jabatan_guru jg', 'jg.id_guru = a.id_guru', 'left')
                ->join('level_guru lg', 'lg.id_level = jg.id_jabatan', 'left');

            if ($idGuru) {
                $builder->where('a.id_guru', $idGuru);
            } elseif ($userId) {
                $builder->where('a.id_user', $userId);
            } else {
                return redirect()->to('auth');
            }

            $guru = $builder->get()->getRow();

            // Fallback only if master_guru record is completely missing
            if (!$guru) {
                $user = $this->authModel->user($userId);
                $guru = (object) [
                    'id_guru' => 0,
                    'nama_guru' => $user->first_name . ' ' . $user->last_name,
                    'foto' => $user->foto,
                    'nip' => '-', // Fix: Do not use username as NIP
                    'jenis_kelamin' => '-',
                    'kode_guru' => '-',
                    'jabatan' => $user->company ?? 'Staff',
                    'username' => $user->username
                ];
            } else {
                // Ensure photo path is correct if missing
                if (empty($guru->foto)) {
                    $user = $this->authModel->user($userId);
                    $guru->foto = $user->foto ?? '';
                }
            }

            // Nomor WA notifikasi personal. Kolom master_guru.wa_number hanya
            // catatan; sumber runtime-nya user_notifikasi (itu yang dibaca
            // NotificationService), jadi ambil dari sana lebih dulu.
            $waNumber = '';
            $waVerified = false;
            try {
                $userIdForWa = (int) session()->get('user_id');
                $waNumber = \App\Libraries\WhatsAppLink::numberForGuru(
                    (int) ($guru->id_guru ?? 0),
                    $userIdForWa
                );
                $waVerified = $waNumber !== ''
                    && (new \App\Models\UserNotifikasiModel())->getVerifiedWaNumber($userIdForWa) !== '';
            } catch (\Throwable $e) {
                log_message('error', 'Gagal membaca nomor WA notifikasi: ' . $e->getMessage());
            }

            return view('members/guru/absen_profile', [
                'title'          => 'Profil Guru',
                'guru'           => $guru,
                'waNotifNumber'  => $waNumber,
                'waNotifVerified' => $waVerified,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Profile Error: ' . $e->getMessage());
            return redirect()->to('presensi')->with('error', 'Terjadi kesalahan saat membuka profil');
        }
    }

    /**
     * Pesan penolakan bila tanggal ini libur, atau null bila hari kerja.
     * Sumber libur: absensi_setting.hari_libur_mingguan (ternormalisasi),
     * absensi_jadwal_kerja.is_libur, dan absensi_hari_libur — lewat
     * AttendancePolicy supaya sama dengan jalur siswa, rekap, dan cron.
     */
    private function checkIsHoliday(string $date): ?string
    {
        if (!\App\Services\AttendancePolicy::isLibur($date)) {
            return null;
        }

        if (\App\Services\AttendancePolicy::isLiburNasional($date)) {
            $ket = \App\Services\AttendancePolicy::liburNasional()[$date] ?? '';
            return 'Hari ini libur: ' . ($ket !== '' ? $ket : 'hari libur');
        }

        $jadwal = \App\Services\AttendancePolicy::jadwalKerja()[\App\Services\AttendancePolicy::isoHari($date)] ?? null;
        if ($jadwal !== null && (int) ($jadwal->is_libur ?? 0) === 1) {
            return 'Hari ini libur sesuai Jadwal Kerja GTK.';
        }

        return 'Hari ini adalah hari libur mingguan ('
            . \App\Services\AttendancePolicy::namaHariId($date) . ').';
    }

    private function getRandomMotivation(): string
    {
        $quotes = [
            "Waktu adalah aset berharga. Mari hargai dengan ketepatan waktu.",
            "Ketepatan waktu mencerminkan profesionalisme dan dedikasi Anda.",
            "Menjadi teladan bagi siswa dimulai dari hal kecil seperti tepat waktu.",
            "Jangan menyerah, esok adalah kesempatan untuk lebih disiplin!",
            "Keterlambatan bukan kegagalan, tapi pengingat untuk memperbaiki diri.",
            "Satu menit sangat berharga. Mari manfaatkan setiap detik untuk pendidikan.",
            "Disiplin adalah jembatan antara tujuan dan pencapaian. Semangat!",
            "Guru yang disiplin akan melahirkan murid yang berkarakter kuat."
        ];
        return $quotes[array_rand($quotes)];
    }

    /**
     * Request Attendance Correction
     */
    public function correctionRequest()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        try {
            // Ensure table schema is up to date
            $this->patchAbsensiKoreksiTable();
            $this->patchAbsensiHarianTable();

            $attendanceId = $this->request->getPost('attendance_id');
            $fieldName = $this->request->getPost('field_name');
            $newValue = $this->request->getPost('new_value');
            $reason = $this->request->getPost('reason');

            if (empty($attendanceId) || empty($fieldName) || empty($reason)) {
                return $this->respond(false, 'Data tidak lengkap');
            }

            $userId = session()->get('user_id');

            // Whitelist field: hanya jam masuk/pulang yang bisa dikoreksi.
            // (Dulu: field_name bebas → mass-assignment kolom points/denda/status/user_id.)
            $allowedFields = ['jam_masuk', 'jam_pulang'];
            if (!in_array($fieldName, $allowedFields, true)) {
                return $this->respond(false, 'Field koreksi tidak didukung. Hanya jam masuk / jam pulang.');
            }

            // Validasi format jam HH:MM / HH:MM:SS
            $timeOnly = $newValue;
            if (strlen($newValue) > 8) {
                $parts = explode(' ', $newValue);
                $timeOnly = end($parts);
            }
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $timeOnly)) {
                return $this->respond(false, 'Format jam tidak valid. Gunakan HH:MM (00:00–23:59).');
            }

            // --- LIMIT CHECK (rentang tanggal eksplisit, hanya pending+approved yang makan kuota) ---
            $db = \Config\Database::connect();
            $today = date('Y-m-d');
            $todayStart = $today . ' 00:00:00';
            $todayEnd = $today . ' 23:59:59';
            $countToday = $db->table('absensi_koreksi')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('created_at >=', $todayStart)
                ->where('created_at <=', $todayEnd)
                ->countAllResults();

            if ($countToday >= 1) {
                return $this->respond(false, 'Batas pengajuan koreksi harian tercapai (Max 1x sehari).');
            }

            // 2. Monthly Limit (Max 5)
            $monthStart = date('Y-m-01 00:00:00');
            $monthEnd = date('Y-m-t 23:59:59');
            $countMonth = $db->table('absensi_koreksi')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('created_at >=', $monthStart)
                ->where('created_at <=', $monthEnd)
                ->countAllResults();

            if ($countMonth >= 5) {
                return $this->respond(false, 'Batas pengajuan koreksi bulanan tercapai (Max 5x sebulan).');
            }
            // --- END LIMIT CHECK ---

            // Tolak duplikat pending untuk (attendance, field) yang sama
            $dupPending = $db->table('absensi_koreksi')
                ->where('user_id', $userId)
                ->where('attendance_id', $attendanceId)
                ->where('field_name', $fieldName)
                ->where('status', 'pending')
                ->countAllResults();
            if ($dupPending > 0) {
                return $this->respond(false, 'Sudah ada pengajuan koreksi pending untuk data ini.');
            }

            // Verify the attendance belongs to the user
            $attendance = $this->attendanceModel->find($attendanceId);
            if (!$attendance || $attendance->user_id != session()->get('user_id')) {
                return $this->respond(false, 'Data absensi tidak ditemukan');
            }

            // Batas umur koreksi: absensi lebih tua dari 30 hari kalender
            // tidak bisa dikoreksi mandiri (hindari ubah arsip; ajukan via
            // admin dengan bukti). Batas longgar & terdokumentasi.
            $tglAbsen = substr((string) ($attendance->tgl ?? ''), 0, 10);
            if ($tglAbsen !== '' && $tglAbsen < date('Y-m-d', strtotime('-30 days'))) {
                return $this->respond(false, 'Koreksi mandiri hanya untuk absensi maksimal 30 hari ke belakang. Hubungi admin untuk absensi lebih lama.');
            }

            // Get old value
            $oldValue = $attendance->{$fieldName} ?? null;

            // Format new value based on field type
            $formattedNewValue = $newValue;
            if ($fieldName === 'jam_masuk' || $fieldName === 'jam_pulang') {
                // Ensure time format is HH:MM:SS
                if (strlen($newValue) === 5) {
                    // If user sent HH:MM (from time input), add :00
                    $formattedNewValue = $newValue . ':00';
                }
                // If it's a full datetime field (from old value), preserve date
                if ($oldValue && strlen($oldValue) > 8) {
                    // Old value is datetime (2026-01-10 07:30:00)
                    $date = substr($oldValue, 0, 10);
                    $formattedNewValue = $date . ' ' . $formattedNewValue;
                }
            }

            // Create correction request
            $correctionModel = new \App\Models\AttendanceCorrectionModel();
            $correctionModel->insert([
                'attendance_id' => $attendanceId,
                'user_id' => session()->get('user_id'),
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => $formattedNewValue,
                'reason' => $reason,
                'status' => 'pending'
            ]);

            // Kabari grup agar admin tahu ada koreksi yang perlu diperiksa.
            // Best-effort: kegagalan Telegram tidak boleh membatalkan pengajuan.
            try {
                $notif = new \App\Services\NotificationService();
                $guruRow = $db->table('master_guru')->select('nama_guru')
                    ->where('id_user', $userId)->get()->getRow();
                $namaGuru = $guruRow->nama_guru ?? ('User ' . $userId);
                $tanggal = $oldValue && preg_match('/(\d{4}-\d{2}-\d{2})/', (string) $oldValue, $m) === 1
                    ? date('d/m/Y', strtotime($m[1]))
                    : date('d/m/Y');
                $notif->notifyKoreksiSubmitted(
                    $namaGuru,
                    $tanggal,
                    $notif->koreksiFieldLabel($fieldName),
                    $notif->normalizeKoreksiValue((string) ($oldValue ?? '')),
                    $notif->normalizeKoreksiValue((string) $formattedNewValue),
                    (string) $reason,
                    (int) $userId
                );
            } catch (\Throwable $e) {
                log_message('error', 'Notifikasi pengajuan koreksi gagal: ' . $e->getMessage());
            }

            return $this->respond(true, 'Permintaan koreksi terkirim. Menunggu persetujuan admin.');
        } catch (\Throwable $e) {
            log_message('error', 'Correction Request Error: ' . $e->getMessage());
            return $this->respond(false, 'Terjadi kesalahan sistem — periksa log aplikasi.', null, null, 500);
        }
    }

    /**
     * Patch absensi_harian table schema
     */
    /**
     * Kolom id_guru sudah nullable INT default 0? Kalau sudah, tidak ada
     * ALTER yang perlu dijalankan.
     */
    private function idGuruPerluDiperbaiki(string $tabel): bool
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($tabel)) {
            return false;
        }
        foreach ($db->getFieldData($tabel) as $f) {
            if ($f->name !== 'id_guru') {
                continue;
            }
            $tipe = strtolower((string) ($f->type ?? ''));

            return !($tipe === 'int' && !empty($f->nullable) && (string) ($f->default ?? '') === '0');
        }

        return false;
    }

    private function patchAbsensiHarianTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $harianFields = [
            'denda' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'after' => 'status_masuk'],
            'keterangan_denda' => ['type' => 'TEXT', 'null' => true, 'after' => 'denda'],
            'points' => ['type' => 'INT', 'default' => 0, 'after' => 'keterangan_denda']
        ];

        // Ensure id_guru column allows NULL / default 0
        // Hanya bila bentuknya memang belum sesuai. Dulu modifyColumn jalan tanpa
        // syarat di SETIAP request absen (jalur scan ikut memanggil method ini):
        // ALTER TABLE absensi_harian mengunci dan menulis ulang tabel tepat di jam
        // sibuk check-in, dan kegagalannya ditelan catch.
        if ($this->idGuruPerluDiperbaiki('absensi_harian')) {
            try {
                $forge->modifyColumn('absensi_harian', [
                    'id_guru' => [
                        'name' => 'id_guru',
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => 0,
                    ],
                ]);
            } catch (\Throwable $e) {
                log_message('error', '[patchAbsensiHarianTable] id_guru absensi_harian: ' . $e->getMessage());
            }
        }

        if ($db->tableExists('absensi_izin') && $this->idGuruPerluDiperbaiki('absensi_izin')) {
            try {
                $forge->modifyColumn('absensi_izin', [
                    'id_guru' => [
                        'name' => 'id_guru',
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => 0,
                    ],
                ]);
            } catch (\Throwable $e) {
                log_message('error', '[patchAbsensiHarianTable] id_guru absensi_izin: ' . $e->getMessage());
            }
        }

        foreach ($harianFields as $fieldName => $config) {
            if (!$db->fieldExists($fieldName, 'absensi_harian')) {
                try {
                    $forge->addColumn('absensi_harian', [$fieldName => $config]);
                } catch (\Throwable $e) {
                    log_message('error', '[patchAbsensiHarianTable] kolom absensi_harian.' . $fieldName . ': ' . $e->getMessage());
                }
            }
        }

        $settingFields = [
            'denda_tier_1' => ['type' => 'INT', 'default' => 100],
            'denda_tier_2' => ['type' => 'INT', 'default' => 200],
            'denda_tier_3' => ['type' => 'INT', 'default' => 500],
            'denda_alpha_per_hari' => ['type' => 'INT', 'default' => 5000],
            'point_tier_1' => ['type' => 'INT', 'default' => 1],
            'point_tier_2' => ['type' => 'INT', 'default' => 2],
            'point_tier_3' => ['type' => 'INT', 'default' => 5],
            'reward_cap_menit' => ['type' => 'INT', 'default' => 30],
            'reward_point_tier_1' => ['type' => 'INT', 'default' => 1],
            'reward_point_tier_2' => ['type' => 'INT', 'default' => 1],
            'reward_point_tier_3' => ['type' => 'INT', 'default' => 2],
        ];

        foreach ($settingFields as $fieldName => $config) {
            if ($db->tableExists('absensi_setting') && !$db->fieldExists($fieldName, 'absensi_setting')) {
                try {
                    $forge->addColumn('absensi_setting', [$fieldName => $config]);
                } catch (\Throwable $e) {
                    log_message('error', '[patchAbsensiHarianTable] kolom absensi_setting.' . $fieldName . ': ' . $e->getMessage());
                }
            }
        }

        if ($db->tableExists('rewards') && !$db->fieldExists('nominal', 'rewards')) {
            try {
                $forge->addColumn('rewards', [
                    'nominal' => [
                        'type' => 'DECIMAL',
                        'constraint' => '15,2',
                        'default' => 0,
                        'after' => 'poin_dibutuhkan',
                        'null' => false,
                    ],
                ]);
            } catch (\Throwable $e) {}
        }

        if ($db->tableExists('absensi_pembayaran_denda') && !$db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda')) {
            try {
                $forge->addColumn('absensi_pembayaran_denda', [
                    'reward_redemption_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'keterangan',
                    ],
                ]);
            } catch (\Throwable $e) {}
        }

        // NOTE: Jangan pernah menimpa nilai setting di sini (GET harus idempoten).
        // Nilai 0 adalah setting sah (artinya: nonaktifkan denda) dan tidak boleh
        // diubah kembali ke default. Default hanya berlaku via skema kolom
        // (addColumn di atas) dan fallback `??` di kode pembaca.

        if ($db->tableExists('absensi_harian')) {
            try {
                // Clear denda, telat_menit, pulang_cepat_menit on any record marked as Dinas
                $db->query("UPDATE absensi_harian SET denda = 0, keterangan_denda = NULL, telat_menit = 0, pulang_cepat_menit = 0 WHERE status_masuk LIKE 'DINAS%' OR status_pulang LIKE 'DINAS%'");
                // If keterangan explicitly mentions dinas, ensure status is DINAS and clean penalties
                $db->query("UPDATE absensi_harian SET status_masuk = 'DINAS_APPROVED', status_pulang = 'DINAS_APPROVED', denda = 0, keterangan_denda = NULL, telat_menit = 0, pulang_cepat_menit = 0, points = 10 WHERE (keterangan LIKE '%dinas%' OR keterangan LIKE '%tugas luar%') AND (denda > 0 OR telat_menit > 0 OR status_masuk = 'LATE')");
            } catch (\Throwable $e) {}
        }
    }

    private function getAlphaDatesForUserMonth(int $userId, string $monthPrefix, $db, $setting): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $monthPrefix)) {
            $monthPrefix = date('Y-m');
        }

        $startDate = $monthPrefix . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        try {
            $penaltyService = new \App\Services\AttendancePenaltyService();
            $absenSetting = $db->tableExists('absensi_setting') ? $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow() : $setting;
            $summary = $penaltyService->calculateAllUsersPeriodAlphaSummary($startDate, $endDate, $absenSetting);
            $items = $summary[$userId]['items'] ?? [];
            return array_values(array_filter(array_column($items, 'tgl')));
        } catch (\Throwable $e) {
            log_message('error', '[getAlphaDatesForUserMonth] ' . $e->getMessage());
            return [];
        }
    }

    private function getAlphaCountMapForUsersMonth(array $userIds, string $monthPrefix, $db, $setting): array
    {
        if (empty($userIds)) return [];
        if (!preg_match('/^\d{4}-\d{2}$/', $monthPrefix)) {
            $monthPrefix = date('Y-m');
        }

        $startDate = $monthPrefix . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        try {
            $penaltyService = new \App\Services\AttendancePenaltyService();
            $absenSetting = $db->tableExists('absensi_setting') ? $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow() : $setting;
            $summary = $penaltyService->calculateAllUsersPeriodAlphaSummary($startDate, $endDate, $absenSetting);

            $alphaMap = [];
            foreach ($userIds as $uid) {
                $alphaMap[(int) $uid] = (int) ($summary[(int) $uid]['count'] ?? 0);
            }
            return $alphaMap;
        } catch (\Throwable $e) {
            log_message('error', '[getAlphaCountMapForUsersMonth] ' . $e->getMessage());
            $alphaMap = [];
            foreach ($userIds as $uid) {
                $alphaMap[(int) $uid] = 0;
            }
            return $alphaMap;
        }
    }

    /**
     * Patch absensi_koreksi table schema
     */
    private function patchAbsensiKoreksiTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists('absensi_koreksi')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'attendance_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'field_name' => ['type' => 'VARCHAR', 'constraint' => 50],
                'old_value' => ['type' => 'TEXT', 'null' => true],
                'new_value' => ['type' => 'TEXT'],
                'reason' => ['type' => 'TEXT'],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
                'processed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'processed_at' => ['type' => 'DATETIME', 'null' => true],
                'admin_note' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->createTable('absensi_koreksi', true);
            return;
        }

        // Check and add missing columns
        $requiredColumns = [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];

        foreach ($requiredColumns as $field => $def) {
            if (!$db->fieldExists($field, 'absensi_koreksi')) {
                $forge->addColumn('absensi_koreksi', [$field => $def]);
            }
        }
    }

    /**
     * Get user's correction history
     */
    public function myCorrections()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $correctionModel = new \App\Models\AttendanceCorrectionModel();
        $corrections = $correctionModel
            ->select('absensi_koreksi.*, a.tgl')
            ->join('absensi_harian a', 'a.id = absensi_koreksi.attendance_id')
            ->where('absensi_koreksi.user_id', session()->get('user_id'))
            ->orderBy('absensi_koreksi.created_at', 'DESC')
            ->findAll();

        return $this->respond(true, 'Data koreksi', $corrections);
    }

    /**
     * Get user's attendance records for correction selection
     */
    public function myAttendance()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $records = $this->attendanceModel
            ->where('user_id', session()->get('user_id'))
            ->orderBy('tgl', 'DESC')
            ->limit(30)
            ->findAll();

        // Check for existing corrections
        $db = \Config\Database::connect();
        // Check if table exists first to avoid error during migration/setup
        if ($db->tableExists('absensi_koreksi')) {
            $corrections = $db->table('absensi_koreksi')
                ->select('attendance_id')
                ->where('user_id', session()->get('user_id'))
                ->get()
                ->getResultArray();

            $attendanceIdsWithCorrection = array_column($corrections, 'attendance_id');

            foreach ($records as &$rec) {
                // If return type is object
                if (is_object($rec)) {
                    $rec->has_correction = in_array($rec->id, $attendanceIdsWithCorrection);
                } else {
                    $rec['has_correction'] = in_array($rec['id'], $attendanceIdsWithCorrection);
                }
            }
        }

        return $this->respond(true, 'Data absensi', $records);
    }
    // ==========================================
    // MIKROTIK INTEGRATION HOOKS
    // ==========================================

    private function enableHotspotUser($userId)
    {
        try {
            $setting = (new DashboardModel())->getSetting();

            if (empty($setting->mikrotik_host) || empty($setting->mikrotik_user)) {
                return;
            }

            // Get User Identify (Username)
            $db = \Config\Database::connect();
            $user = $db->table('users')->select('username')->where('id', $userId)->get()->getRow();

            if (!$user || empty($user->username))
                return;

            $router = new \App\Libraries\RouterosAPI();
            $router->debug = false;

            if ($router->connect($setting->mikrotik_host, $setting->mikrotik_user, $setting->mikrotik_pass, (int) ($setting->mikrotik_port ?? 8728))) {

                // 1. Find the Hotspot User ID by name
                $router->write('/ip/hotspot/user/print', false);
                $router->write('?name=' . $user->username);
                $hotspotUser = $router->read();

                if (!empty($hotspotUser) && isset($hotspotUser[0]['.id'])) {
                    // 2. Enable the User
                    $router->write('/ip/hotspot/user/enable', false);
                    $router->write('=.id=' . $hotspotUser[0]['.id']);
                    $router->read();
                    // log_message('info', 'MikroTik Hotspot Enabled for: ' . $user->username);
                }

                $router->disconnect();
            }
        } catch (\Exception $e) {
            log_message('error', 'MikroTik Hook Failed: ' . $e->getMessage());
        }
    }
    /**

     * Download Surat Teguran PDF
     */
    public function downloadTeguran($level)
    {
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth');
        }

        $userId = (int) session()->get('user_id');
        $idGuru = $this->resolveIdGuru($userId);
        $db = \Config\Database::connect();

        // Models
        $dashboardModel = new DashboardModel();

        // 1. Get Settings
        $attSetting = $this->settingModel->first();
        if (!$attSetting)
            $attSetting = $this->getDefaultSettings();

        // Global Setting (for logo, school name, kepsek)
        $globalSetting = $dashboardModel->getSetting();
        // Merge settings
        $setting = clone $attSetting;
        if ($globalSetting) {
            foreach (get_object_vars($globalSetting) as $key => $val) {
                if (!isset($setting->$key))
                    $setting->$key = $val;
            }
        }
        // Ensure defaults if missing from global
        if (!isset($setting->logo_kiri))
            $setting->logo_kiri = null;
        if (!isset($setting->nama_sekolah))
            $setting->nama_sekolah = 'Sekolah';
        if (!isset($setting->kota))
            $setting->kota = 'Kota';

        $currentMonth = (int) date('n');
        $currentYear  = (int) date('Y');

        // Hitung alpha bulan berjalan secara kanonik (single source of truth)
        $penaltyService = new \App\Services\AttendancePenaltyService();
        $calcAlpha = $penaltyService->calculateTeacherMonthlyAlpha((int) $userId, $currentMonth, $currentYear, $setting);

        // Get Teacher Data
        $guru = $db->table('master_guru')->where('id_guru', $idGuru)->get()->getRow();
        if (!$guru) {
            $guru = $db->table('master_guru')->where('id_user', $userId)->get()->getRow();
        }

        if (!$guru) {
            return redirect()->to('presensi')->with('error', 'Data profil guru tidak ditemukan. Hubungi operator.');
        }

        // Get Kepsek Data
        $kepsekNama = $setting->kepsek ?? ($setting->nama_kepsek ?? '..........................');
        $kepsekNip = $setting->nip_kepsek ?? '..........................';

        // Determine Teguran Level Text
        $teguranText = \App\Libraries\SuratTeguranLevel::label((int) $level);

        // Lazy Migration — hanya kalau tabel belum ada sama sekali.
        // Skema yang dibuat di sini sengaja SINGKAP dengan versi migrasi
        // (tanpa unique key, tanpa kolom ack/dibatalkan_otomatis), jadi ini
        // hanya jaring pengaman untuk instalasi yang belum migrate; jalur
        // normalnya tetap php spark migrate.
        if (!$db->tableExists('absensi_surat_teguran')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'bulan' => ['type' => 'INT', 'constraint' => 2],
                'tahun' => ['type' => 'INT', 'constraint' => 4],
                'level' => ['type' => 'INT', 'constraint' => 2],
                'jumlah_alpha' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 0],
                'nomor_urut' => ['type' => 'INT', 'constraint' => 11],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'aktif'],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('absensi_surat_teguran', true);
        }

        // Alur penerbitan lewat service — aturan monotonic dan slot
        // permanen berlaku sama seperti cron. Versi lama memakai DOWNLOAD
        // dari sisi guru sebagai pemicu penerbitan: guru yang meminta
        // level lebih rendah dari yang sudah terbit tetap bisa membuat
        // surat baru, dan slot yang sudah dibatalkan bisa diisi lagi.
        $svcTeguran = new \App\Services\SuratTeguranService($db);
        $svcTeguran->sinkronkan((int) $guru->id_guru, $calcAlpha, (string) $currentMonth, (string) $currentYear);

        $existingLetter = $db->table('absensi_surat_teguran')
            ->where('id_guru', $guru->id_guru)
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->where('level', $level)
            ->get()
            ->getRow();

        if ($existingLetter) {
            // Surat yang dibatalkan tidak boleh diunduh sebagai surat aktif.
            if (($existingLetter->status ?? 'aktif') === 'dibatalkan') {
                return redirect()->to('presensi')->with('error', 'Surat teguran ini telah dibatalkan karena dispensasi/izin resmi disetujui.');
            }

            $noUrut = $existingLetter->nomor_urut;

            // Pakai snapshot jumlah_alpha yang tersimpan (isi arsip tidak
            // boleh ikut berubah). Kalau 0 — surat lama yang tidak
            //olerının mengisi kolomnya — pakai angka kanonik.
            $jumlahAlpha = (int) ($existingLetter->jumlah_alpha ?? 0);
            if ($jumlahAlpha <= 0) {
                $jumlahAlpha = max((int) $level, $calcAlpha);
            }
        } else {
            // Cegah penerbitan liar: alpha riil harus memenuhi ambang level
            // yang DIMINTA. Level lebih ringan dari yang berhak ditolak.
            if ($calcAlpha < (int) $level) {
                return redirect()->to('presensi')->with('error', "Anda tidak memiliki Surat Teguran {$teguranText}.");
            }

            // Terbitkan lewat service. Kalau level yang diminta bukan slot
            // yang bebas (mis. sudah terisi surat yang lebih berat), service
            // menolak — dan yang dimuat adalah surat yang sebenarnya ada.
            $terbit = $svcTeguran->terbitkan(
                (int) $guru->id_guru,
                $calcAlpha,
                (string) $currentMonth,
                (string) $currentYear
            );

            $baris = $terbit !== null
                ? $db->table('absensi_surat_teguran')->where('id', $terbit['id'])->get()->getRow()
                : $db->table('absensi_surat_teguran')
                    ->where('id_guru', $guru->id_guru)
                    ->where('bulan', $currentMonth)
                    ->where('tahun', $currentYear)
                    ->orderBy('level', 'DESC')
                    ->get()
                    ->getRow();

            if ($baris === null) {
                return redirect()->to('presensi')->with('error', 'Surat teguran gagal diterbitkan. Hubungi operator.');
            }

            // Level yang terbit boleh berbeda dari yang diminta (mis. guru
            // minta tingkat I padahal sudah punya tingkat II). Muat yang
            // benar supaya PDF tidak berlabel_level keliru.
            $level        = (int) $baris->level;
            $teguranText  = \App\Libraries\SuratTeguranLevel::label($level);
            $noUrut       = (int) $baris->nomor_urut;
            $jumlahAlpha  = (int) ($baris->jumlah_alpha ?? 0);
            if ($jumlahAlpha <= 0) {
                $jumlahAlpha = max($level, $calcAlpha);
            }
        }

        // Baris surat yang dipakai PDF: yang sudah ada, atau yang baru
        // diterbitkan/diambil pada cabang di atas. Tanggalnya dipakai untuk
        // nomor surat ({tanggal}) dan tanggal surat — selalu tanggal terbit
        // arsip, bukan tanggal cetak.
        $barisTerbit = $existingLetter ?? ($baris ?? null);
        $tglTerbitSurat = !empty($barisTerbit->created_at) ? date('d-m-Y', strtotime($barisTerbit->created_at)) : null;

        // Format Number (template dapat dikonfigurasi di /settings)
        $nomorSurat = \App\Libraries\SuratNomor::build(
            (int) $noUrut,
            (int) $currentMonth,
            (int) $currentYear,
            $setting,
            (int) $level,
            $tglTerbitSurat
        );

        // Rincian tanggal alpha untuk kalimat rincian di badan surat.
        $alphaDates = [];
        try {
            $alphaDates = (new \App\Services\AttendancePenaltyService())->teacherMonthlyAlphaDates(
                (int) $userId, (int) $currentMonth, (int) $currentYear, $setting
            );
        } catch (\Throwable $e) {
            log_message('error', 'downloadTeguran alpha dates gagal: ' . $e->getMessage());
        }

        // QR TTE pengganti tanda tangan basah Kepsek
        $tteKode = \App\Libraries\TteQr::verificationCode(
            $nomorSurat, (int) $guru->id_guru, (int) $level, (int) $currentMonth, (int) $currentYear
        );
        $tteQr = \App\Libraries\TteQr::dataUri(
            \App\Libraries\TteQr::payload(
                $nomorSurat, (string) ($guru->nama_guru ?? '-'), (string) ($guru->nip ?? '-'),
                $teguranText, (int) $jumlahAlpha, $tteKode,
                (string) $kepsekNama, $kepsekNip,
                (string) ($setting->sekolah ?? '')
            )
        );

        // Logo Processing
        $logoBase64 = '';
        if (isset($setting->logo_kiri) && $setting->logo_kiri && file_exists(FCPATH . $setting->logo_kiri)) {
            $type = pathinfo(FCPATH . $setting->logo_kiri, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->logo_kiri);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Kop Sekolah Processing
        $kopBase64 = '';
        if (isset($setting->kopsekolah) && $setting->kopsekolah && file_exists(FCPATH . $setting->kopsekolah)) {
            $type = pathinfo(FCPATH . $setting->kopsekolah, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->kopsekolah);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Baris surat yang dipakai PDF sudah ditentukan di atas
        // ($barisTerbit): tanggal surat = tanggal terbit arsip.
        $data = [
            'setting' => $setting,
            'guru' => $guru,
            'jumlah_alpha' => $jumlahAlpha,
            'tingkat_teguran' => $teguranText,
            'kepsek_nama' => $kepsekNama,
            'kepsek_nip' => $kepsekNip,
            'logo_base64' => $logoBase64,
            'kop_base64' => $kopBase64,
            'nomor_surat' => $nomorSurat,
            'tte_kode' => $tteKode,
            'tte_qr' => $tteQr,
            'periode_bulan' => (int) $currentMonth,
            'periode_tahun' => (int) $currentYear,
            // Tanggal surat = tanggal terbit arsip, bukan tanggal cetak.
            'tanggal_terbit' => !empty($barisTerbit->created_at) ? (string) $barisTerbit->created_at : null,
            // Daftar tanggal alpha periode surat untuk rincian di badan surat.
            'alpha_dates' => $alphaDates,
        ];

        $html = view('members/guru/surat_teguran_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Surat_Teguran_{$level}_Hari_{$guru->nama_guru}.pdf";

        $dompdf->stream($filename, ["Attachment" => false]);
        exit;
    }

    /**
     * Hitung alpha untuk periode (bulan/tahun) seorang guru — menggunakan
     * AttendancePenaltyService::calculateTeacherMonthlyAlpha sebagai standar tunggal.
     */
    private function alphaForLetterPeriod(int $userId, int $bulan, int $tahun, $setting, $db): int
    {
        try {
            $penaltyService = new \App\Services\AttendancePenaltyService();
            return $penaltyService->calculateTeacherMonthlyAlpha($userId, $bulan, $tahun, $setting);
        } catch (\Throwable $e) {
            log_message('error', 'alphaForLetterPeriod gagal: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Download Surat Teguran PDF berbasis ID surat (milik sendiri).
     * Jumlah alpha + label bulan selalu mengikuti periode surat tersebut,
     * dan memakai nomor surat yang sudah tersimpan (tidak menerbitkan baru).
     * GET /presensi/surat/(:num)
     */
    public function downloadTeguranSurat($id)
    {
        if (!$this->inGroup('guru')) {
            return redirect()->to('auth');
        }

        $userId = (int) session()->get('user_id');
        $idGuru = $this->resolveIdGuru($userId);
        $db = \Config\Database::connect();

        if (!$db->tableExists('absensi_surat_teguran')) {
            return redirect()->to('presensi')->with('error', 'Data surat tidak ditemukan.');
        }

        $letter = $db->table('absensi_surat_teguran a')
            ->select('a.*, g.nama_guru, g.nip')
            ->join('master_guru g', 'g.id_guru = a.id_guru', 'left')
            ->where('a.id', (int) $id)
            ->get()->getRow();

        if (!$letter || (int) ($letter->id_guru ?? 0) !== $idGuru) {
            return redirect()->to('presensi')->with('error', 'Data surat tidak ditemukan.');
        }

        if (($letter->status ?? 'aktif') === 'dibatalkan') {
            return redirect()->to('presensi')->with('error', 'Surat teguran ini telah dibatalkan karena dispensasi/izin resmi disetujui.');
        }

        $level = (int) ($letter->level ?? 0);
        $bulan = (int) ($letter->bulan ?? date('n'));
        $tahun = (int) ($letter->tahun ?? date('Y'));

        // Models & settings (sama seperti downloadTeguran level)
        $dashboardModel = new DashboardModel();
        $attSetting = $this->settingModel->first();
        if (!$attSetting)
            $attSetting = $this->getDefaultSettings();
        $globalSetting = $dashboardModel->getSetting();
        $setting = clone $attSetting;
        if ($globalSetting) {
            foreach (get_object_vars($globalSetting) as $key => $val) {
                if (!isset($setting->$key))
                    $setting->$key = $val;
            }
        }
        if (!isset($setting->logo_kiri))
            $setting->logo_kiri = null;
        if (!isset($setting->nama_sekolah))
            $setting->nama_sekolah = 'Sekolah';
        if (!isset($setting->kota))
            $setting->kota = 'Kota';

        // Alpha sesuai snapshot surat agar dokumen arsip berkekuatan tetap (immutable)
        $jumlahAlpha = (int) ($letter->jumlah_alpha ?? 0);
        if ($jumlahAlpha <= 0) {
            $calcAlpha = $this->alphaForLetterPeriod($userId, $bulan, $tahun, $setting, $db);
            $jumlahAlpha = max($level, $calcAlpha);
        }

        // Rincian tanggal alpha untuk kalimat rincian di badan surat.
        $alphaDates = [];
        try {
            $alphaDates = (new \App\Services\AttendancePenaltyService())->teacherMonthlyAlphaDates(
                $userId, $bulan, $tahun, $setting
            );
        } catch (\Throwable $e) {
            log_message('error', 'downloadTeguranSurat alpha dates gagal: ' . $e->getMessage());
        }

        // Data guru untuk kop surat
        $guru = $db->table('master_guru')->where('id_guru', $idGuru)->get()->getRow();
        if (!$guru) {
            return redirect()->to('presensi')->with('error', 'Data profil guru tidak ditemukan. Hubungi operator.');
        }
        if (!empty($letter->nama_guru))
            $guru->nama_guru = $letter->nama_guru;
        if (!empty($letter->nip))
            $guru->nip = $letter->nip;

        $kepsekNama = $setting->kepsek ?? ($setting->nama_kepsek ?? '..........................');
        $kepsekNip = $setting->nip_kepsek ?? '..........................';

        $teguranText = \App\Libraries\SuratTeguranLevel::label((int) $level);

        // Nomor surat yang SUDAH tersimpan — jangan menerbitkan nomor baru
        $nomorSurat = \App\Libraries\SuratNomor::build(
            (int) ($letter->nomor_urut ?? 0),
            (int) $bulan,
            (int) $tahun,
            $setting,
            (int) $level,
            !empty($letter->created_at) ? date('d-m-Y', strtotime($letter->created_at)) : null
        );

        $tteKode = \App\Libraries\TteQr::verificationCode(
            $nomorSurat, (int) $guru->id_guru, (int) $level, (int) $bulan, (int) $tahun
        );
        $tteQr = \App\Libraries\TteQr::dataUri(
            \App\Libraries\TteQr::payload(
                $nomorSurat, (string) ($guru->nama_guru ?? '-'), (string) ($guru->nip ?? '-'),
                $teguranText, (int) $jumlahAlpha, $tteKode,
                (string) $kepsekNama, $kepsekNip,
                (string) ($setting->sekolah ?? '')
            )
        );

        $logoBase64 = '';
        if (isset($setting->logo_kiri) && $setting->logo_kiri && file_exists(FCPATH . $setting->logo_kiri)) {
            $type = pathinfo(FCPATH . $setting->logo_kiri, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->logo_kiri);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $kopBase64 = '';
        if (isset($setting->kopsekolah) && $setting->kopsekolah && file_exists(FCPATH . $setting->kopsekolah)) {
            $type = pathinfo(FCPATH . $setting->kopsekolah, PATHINFO_EXTENSION);
            $data = file_get_contents(FCPATH . $setting->kopsekolah);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $data = [
            'setting' => $setting,
            'guru' => $guru,
            'jumlah_alpha' => $jumlahAlpha,
            'tingkat_teguran' => $teguranText,
            'kepsek_nama' => $kepsekNama,
            'kepsek_nip' => $kepsekNip,
            'logo_base64' => $logoBase64,
            'kop_base64' => $kopBase64,
            'nomor_surat' => $nomorSurat,
            'tte_kode' => $tteKode,
            'tte_qr' => $tteQr,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            // Tanggal surat = tanggal terbit arsip, bukan tanggal cetak.
            'tanggal_terbit' => !empty($letter->created_at) ? (string) $letter->created_at : null,
            // Daftar tanggal alpha periode surat untuk rincian di badan surat.
            'alpha_dates' => $alphaDates,
        ];

        $html = view('members/guru/surat_teguran_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Surat_Teguran_{$level}_Hari_{$guru->nama_guru}.pdf";

        $dompdf->stream($filename, ["Attachment" => false]);
        exit;
    }

    /**
     * Surat teguran aktif milik guru yang BELUM dikonfirmasi untuk satu bulan.
     *
     * Syarat: status != dibatalkan, ack_at IS NULL, bulan & tahun yang sedang
     * berjalan. Tanpa filter bulan, surat lama bulan lalu ikut memunculkan
     * popup setiap hari selamanya.
     *
     * Setiap level (3/7/10) adalah baris terpisah dengan ack_at sendiri, jadi
     * guru menerima notifikasi berjenjang: 3 hari -> konfirmasi, 7 hari ->
     * notifikasi lagi, 10 hari -> notifikasi lagi, untuk bulan yang sama.
     *
     * @return array<int, object>
     */
    private function pendingTeguranGate(int $idGuru, int $bulan, int $tahun, $db): array
    {
        if ($idGuru <= 0 || $bulan < 1 || $bulan > 12 || $tahun < 2000) {
            return [];
        }

        try {
            if (!$db->tableExists('absensi_surat_teguran')) {
                return [];
            }

            $builder = $db->table('absensi_surat_teguran')
                ->where('id_guru', $idGuru)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun);

            if ($db->fieldExists('status', 'absensi_surat_teguran')) {
                $builder->where('status !=', 'dibatalkan');
            }
            if ($db->fieldExists('ack_at', 'absensi_surat_teguran')) {
                $builder->where('ack_at', null);
            }

            return $builder->orderBy('level', 'ASC')
                ->orderBy('nomor_urut', 'ASC')
                ->limit(10)
                ->get()
                ->getResult();
        } catch (\Throwable $e) {
            log_message('error', '[pendingTeguranGate] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Bentuk payload JSON daftar surat teguran untuk UI.
     */
    private function teguranListPayload(array $rows): array
    {
        $namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $data = [];
        foreach ($rows as $r) {
            $lvl = (int) ($r->level ?? 0);
            $data[] = [
                'id' => (int) $r->id,
                'nomor' => \App\Libraries\SuratNomor::build(
                    (int) ($r->nomor_urut ?? 0),
                    (int) ($r->bulan ?? 0),
                    (int) ($r->tahun ?? 0),
                    null,
                    $lvl,
                    !empty($r->created_at) ? date('d-m-Y', strtotime($r->created_at)) : null
                ),
                'level' => $lvl,
                'level_text' => isset(\App\Libraries\SuratTeguranLevel::list()[$lvl])
                    ? \App\Libraries\SuratTeguranLevel::label($lvl)
                    : "TINGKAT ({$lvl} Hari)",
                'bulan' => (int) ($r->bulan ?? 0),
                'bulan_text' => $namaBulan[(int) ($r->bulan ?? 0)] ?? '',
                'tahun' => (int) ($r->tahun ?? 0),
                'download_url' => site_url('presensi/surat/' . (int) $r->id),
            ];
        }

        return $data;
    }

    /**
     * Daftar surat teguran milik guru yang BELUM dikonfirmasi baca.
     * GET /presensi/surat-saya — dipakai gate modal wajib sebelum absen.
     */
    public function myTeguran()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        $userId = (int) session()->get('user_id');
        $idGuru = $this->resolveIdGuru($userId);
        if ($idGuru <= 0) {
            return $this->respond(true, 'Tidak ada surat', []);
        }

        $db = \Config\Database::connect();

        $rows = $this->pendingTeguranGate($idGuru, (int) date('n'), (int) date('Y'), $db);

        return $this->respond(true, 'OK', $this->teguranListPayload($rows));
    }

    /**
     * Konfirmasi baca surat teguran dengan mengetik frasa wajib.
     * POST /presensi/surat-ack { ids: [], konfirmasi: "ya, saya akan meningkatkan disiplin" }
     */
    public function ackTeguran()
    {
        if (!$this->inGroup('guru')) {
            return $this->respond(false, 'Unauthorized', null, null, 401);
        }

        try {
            $throttler = \Config\Services::throttler();
            $ipKey = 'teguran_ack_' . md5($this->request->getIPAddress() . session()->get('user_id'));
            if ($throttler->check($ipKey, 10, MINUTE) === false) {
                return $this->respond(false, 'Terlalu banyak permintaan. Coba lagi sebentar.', null, null, 429);
            }

            $frasaWajib = 'ya, saya akan meningkatkan disiplin';
            $konfirmasi = strtolower(trim((string) $this->request->getPost('konfirmasi')));
            if ($konfirmasi !== $frasaWajib) {
                return $this->respond(false, 'Konfirmasi salah. Ketik persis: "ya, saya akan meningkatkan disiplin".');
            }

            $ids = $this->request->getPost('ids');
            if (!is_array($ids)) {
                $ids = $ids !== null ? [(int) $ids] : [];
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (empty($ids)) {
                return $this->respond(false, 'Tidak ada surat yang dikonfirmasi.');
            }

            $userId = (int) session()->get('user_id');
            $idGuru = $this->resolveIdGuru($userId);
            if ($idGuru <= 0) {
                return $this->respond(false, 'Data guru tidak ditemukan.', null, null, 403);
            }

            $db = \Config\Database::connect();
            if (!$db->tableExists('absensi_surat_teguran') || !$db->fieldExists('ack_at', 'absensi_surat_teguran')) {
                return $this->respond(false, 'Fitur konfirmasi belum tersedia.', null, null, 500);
            }

            $updated = $db->table('absensi_surat_teguran')
                ->where('id_guru', $idGuru)
                ->whereIn('id', $ids)
                ->where('ack_at', null)
                ->update([
                    'ack_at' => date('Y-m-d H:i:s'),
                    'ack_text' => substr((string) $this->request->getPost('konfirmasi'), 0, 60),
                ]);

            if (!$updated && $db->affectedRows() === 0) {
                return $this->respond(false, 'Surat tidak ditemukan atau sudah dikonfirmasi.');
            }

            return $this->respond(true, 'Terima kasih. Konfirmasi Anda telah dicatat. Silakan lanjutkan absensi.');
        } catch (\Throwable $e) {
            log_message('error', 'ackTeguran gagal: ' . $e->getMessage());
            return $this->respond(false, 'Terjadi kesalahan sistem.', null, null, 500);
        }
    }

    /**
     * Hitung alpha bulan berjalan untuk ambang peringatan — memakai sumber yang sama
     * dengan penerbitan surat teguran (calculateTeacherMonthlyAlpha: hormati hari ini
     * yang belum selesai & tanggal masuk guru), agar warning konsisten dengan SP terbit.
     */
    private function countAlphaCurrentMonth($userId)
    {
        try {
            $penaltyService = new \App\Services\AttendancePenaltyService();
            return (int) $penaltyService->calculateTeacherMonthlyAlpha(
                (int) $userId,
                (int) date('m'),
                (int) date('Y')
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0;
        for ($i = 0; $i < 128; $i++) {
            $diff = ($a[$i] ?? 0) - ($b[$i] ?? 0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    private function calculateSimilarity(float $distance): float
    {
        // Distance 0.0 -> 100%, Distance 0.50 -> 88%, Distance 0.72 -> 68%, Distance >= 1.1 -> 0%
        if ($distance <= 0.50) {
            return round(100 - ($distance / 0.50) * 12, 1);
        } elseif ($distance <= 0.72) {
            return round(88 - (($distance - 0.50) / 0.22) * 20, 1);
        } else {
            return max(0, round(68 - (($distance - 0.72) / 0.38) * 68, 1));
        }
    }

    // ── Preferensi Notifikasi Personal Guru ──────────────────────────

    /**
     * Ambil preferensi notifikasi personal guru saat ini (JSON untuk AJAX).
     * GET /presensi/notif/prefs
     */
    public function getNotifPrefs()
    {
        if (! $this->inGroup('guru')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $userId = (int) ($this->session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Sesi tidak valid']);
        }

        try {
            $model = new \App\Models\UserNotifikasiModel();
            $prefs = $model->getPrefs($userId);
            return $this->response->setJSON(['status' => true, 'prefs' => $prefs]);
        } catch (\Throwable $e) {
            log_message('error', '[NotifPrefs] getNotifPrefs gagal: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Gagal memuat preferensi']);
        }
    }

    /**
     * Token CSRF segar untuk halaman mobile yang lama terbuka.
     * GET /presensi/csrf-refresh — cookie CSRF kedaluwarsa 2 jam sementara
     * halaman PWA bisa terbuka semalaman; submit dengan token basi menjawab
     * 403 tanpa pesan yang bisa ditindaklanjuti. Mobile memanggil ini sekali
     * saat submit ditolak 403 lalu mengulang submit dengan token baru.
     * GET tidak melewati filter CSRF sehingga aman dipanggil kapan pun.
     */
    public function csrfRefresh()
    {
        if (! $this->inGroup('guru')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON([
                'status'   => true,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
    }

    /**
     * Simpan preferensi notifikasi personal guru (AJAX).
     * POST /presensi/notif/prefs/save
     *
     * Body POST: prefs[denda]=1, prefs[teguran]=0, dst.
     * Hanya kunci yang ada di PREF_DEFAULTS yang diproses;
     * kunci asing diabaikan.
     */
    public function saveNotifPrefs()
    {
        if (! $this->inGroup('guru')) {
            return $this->response->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)
                ->setJSON(['status' => false, 'message' => 'Method tidak diizinkan']);
        }

        $userId = (int) ($this->session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Sesi tidak valid']);
        }

        $raw = $this->request->getPost('prefs') ?? [];
        if (! is_array($raw)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak valid']);
        }

        // Sanitasi: hanya kunci dari PREF_DEFAULTS yang diizinkan
        $allowed = array_keys(\App\Models\UserNotifikasiModel::PREF_DEFAULTS);
        $clean   = [];
        foreach ($allowed as $key) {
            // Checkbox yang tidak dicentang tidak dikirim → default false
            $clean[$key] = isset($raw[$key]) && ((int) $raw[$key] === 1 || $raw[$key] === 'on' || $raw[$key] === true);
        }

        try {
            $model = new \App\Models\UserNotifikasiModel();
            $ok    = $model->setPrefs($userId, $clean);

            return $this->response->setJSON([
                'status'  => $ok,
                'message' => $ok
                    ? 'Preferensi notifikasi berhasil disimpan.'
                    : 'Gagal menyimpan preferensi.',
                'prefs'   => $ok ? $clean : [],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[NotifPrefs] saveNotifPrefs gagal: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(['status' => false, 'message' => 'Gagal menyimpan — periksa log aplikasi.']);
        }
    }
}
