<?php

namespace App\Controllers\Integrasi;

use App\Controllers\BaseController;
use App\Libraries\DapodikClient;
use App\Models\DashboardModel;
use App\Services\DapodikSyncService;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

/**
 * Controller Dapodik — Pengelolaan Integrasi, Setting API, Direct Sync, & Offline Import Dapodik.
 */
class Dapodik extends BaseController
{
    protected DapodikSyncService $syncService;
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->syncService    = new DapodikSyncService();
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Lapis kedua setelah route filter: seluruh halaman integrasi khusus admin.
     * Mengembalikan response penolakan bila bukan admin, atau null bila boleh lanjut.
     */
    private function tolakNonAdmin()
    {
        if ($this->isLoggedIn() && $this->isAdmin()) {
            return null;
        }

        if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat mengelola integrasi Dapodik.',
            ]);
        }

        return redirect()->to('dashboard')->with('error', 'Akses ditolak. Halaman ini khusus admin.');
    }

    /**
     * Halaman Utama Integrasi Dapodik (Dashboard & Tabs)
     */
    public function index()
    {
        if ($ditolak = $this->tolakNonAdmin()) {
            return $ditolak;
        }

        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');

        $db = \Config\Database::connect();

        $settings = null;
        if ($db->tableExists('dapodik_settings')) {
            $settings = $db->table('dapodik_settings')->get()->getRowArray();
        }

        if (!$settings && $db->tableExists('dapodik_settings')) {
            $db->table('dapodik_settings')->insert([
                'server_url'   => 'http://localhost:5774',
                'npsn'         => '',
                'secret_token' => '',
                'semester_id'  => '20251',
                'auto_sync'    => 0,
                'is_active'    => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            $settings = $db->table('dapodik_settings')->get()->getRowArray();
        }

        // App/School settings for layouts/main.php compatibility
        $appSetting = $this->dashboardModel->getSetting();

        // Stats per perbandingan data terhubung dapo_id
        $stats = [
            'total_guru'  => $db->tableExists('master_guru') ? $db->table('master_guru')->countAllResults() : 0,
            'guru_dapo'   => ($db->tableExists('master_guru') && $db->fieldExists('dapo_id', 'master_guru')) ? $db->table('master_guru')->where('dapo_id IS NOT NULL', null, false)->where('dapo_id !=', '')->countAllResults() : 0,
            'total_siswa' => $db->tableExists('master_siswa') ? $db->table('master_siswa')->countAllResults() : 0,
            'siswa_dapo'  => ($db->tableExists('master_siswa') && $db->fieldExists('dapo_id', 'master_siswa')) ? $db->table('master_siswa')->where('dapo_id IS NOT NULL', null, false)->where('dapo_id !=', '')->countAllResults() : 0,
            'total_kelas' => $db->tableExists('master_kelas') ? $db->table('master_kelas')->countAllResults() : 0,
            'kelas_dapo'  => ($db->tableExists('master_kelas') && $db->fieldExists('dapo_id', 'master_kelas')) ? $db->table('master_kelas')->where('dapo_id IS NOT NULL', null, false)->where('dapo_id !=', '')->countAllResults() : 0,
            'total_mapel' => $db->tableExists('master_mapel') ? $db->table('master_mapel')->countAllResults() : 0,
            'mapel_dapo'  => ($db->tableExists('master_mapel') && $db->fieldExists('dapo_id', 'master_mapel')) ? $db->table('master_mapel')->where('dapo_id IS NOT NULL', null, false)->where('dapo_id !=', '')->countAllResults() : 0,
        ];

        // Logs
        $logs = [];
        if ($db->tableExists('dapodik_log')) {
            $logs = $db->table('dapodik_log')
                ->orderBy('id', 'DESC')
                ->limit(20)
                ->get()->getResultArray();
        }

        $data = [
            'title'      => 'Integrasi Data Dapodik',
            'judul'      => 'Integrasi Dapodik',
            'subjudul'   => 'Sinkronisasi Data Sekolah, Guru, Siswa, Rombel & Pembelajaran dari Dapodik',
            'user'       => $user,
            'setting'    => $appSetting,
            'settings'   => $settings,
            'stats'      => $stats,
            'logs'       => $logs,
            'tp_active'  => $this->dashboardModel->getTahunActive(),
            'smt_active' => $this->dashboardModel->getSemesterActive(),
        ];

        return view('integrasi/dapodik/index', $data);
    }

    /**
     * Simpan Konfigurasi Server API Dapodik
     */
    public function saveSettings()
    {
        if ($ditolak = $this->tolakNonAdmin()) {
            return $ditolak;
        }

        $db = \Config\Database::connect();
        $serverUrl   = rtrim(trim((string) ($this->request->getPost('server_url') ?? '')), '/');
        $npsn        = trim((string) ($this->request->getPost('npsn') ?? ''));
        $secretToken = trim((string) ($this->request->getPost('secret_token') ?? ''));
        $semesterId  = trim((string) ($this->request->getPost('semester_id') ?? '')) ?: '20251';
        $autoSync    = $this->request->getPost('auto_sync') ? 1 : 0;
        $isActive    = $this->request->getPost('is_active') ? 1 : 0;

        if (empty($serverUrl) || !filter_var($serverUrl, FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('error', 'URL Server Dapodik tidak valid. Harap masukkan URL yang benar (contoh: http://localhost:5774).');
        }

        // Skema dibatasi: URL ini dipakai server untuk memanggil web service,
        // jadi nilai seperti file:// atau gopher:// tidak pernah sah.
        if (!in_array(strtolower((string) parse_url($serverUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return redirect()->back()->withInput()->with('error', 'URL Server Dapodik harus memakai http:// atau https://.');
        }

        if (empty($npsn) || !preg_match('/^[0-9]{8}$/', $npsn)) {
            return redirect()->back()->withInput()->with('error', 'NPSN Sekolah tidak valid. NPSN harus 8 digit angka.');
        }

        if (!preg_match('/^\d{5}$/', $semesterId)) {
            return redirect()->back()->withInput()->with('error', 'Semester ID tidak valid. Format: YYYY + 1/2 (contoh: 20251 ganjil, 20252 genap).');
        }

        if (!$db->tableExists('dapodik_settings')) {
            return redirect()->back()->withInput()->with('error', 'Tabel konfigurasi Dapodik belum ada. Jalankan migrasi/integrasi terlebih dahulu.');
        }

        // Token tidak dikirim balik ke browser (lihat view), jadi kolomnya
        // hanya diubah bila admin mengetik nilai baru.
        $data = [
            'server_url'   => $serverUrl,
            'npsn'         => $npsn,
            'semester_id'  => $semesterId,
            'auto_sync'    => $autoSync,
            'is_active'    => $isActive,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        if ($secretToken !== '') {
            $data['secret_token'] = $secretToken;
        } else {
            $tersimpan = $db->table('dapodik_settings')->where('id >', 0)
                ->select('secret_token')->get()->getRowArray();
            if (empty($tersimpan['secret_token'])) {
                return redirect()->back()->withInput()->with('error', 'Token Rahasia Web Service belum pernah diisi. Masukkan token dari Dapodik → Pengaturan → Web Service.');
            }
        }

        $hasil = $db->table('dapodik_settings')->where('id >', 0)->update($data);
        if ($hasil === false) {
            log_message('error', '[Dapodik::saveSettings] update dapodik_settings gagal: ' . json_encode($db->error()));
            return redirect()->back()->withInput()->with('error', 'Konfigurasi Dapodik gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->back()->with('success', 'Konfigurasi Web Service Dapodik berhasil disimpan.');
    }

    /**
     * AJAX — Tes Koneksi HTTP ke API Server Dapodik
     */
    public function testConnection(): ResponseInterface
    {
        if ($ditolak = $this->tolakNonAdmin()) {
            return $ditolak;
        }

        $serverUrl = trim((string) ($this->request->getPost('server_url') ?? ''));
        if ($serverUrl !== '' && !filter_var($serverUrl, FILTER_VALIDATE_URL)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'URL Server Dapodik tidak valid. Gunakan alamat lengkap, contoh http://localhost:5774.',
            ]);
        }

        $client = new DapodikClient(
            $serverUrl !== '' ? $serverUrl : null,
            trim((string) ($this->request->getPost('npsn') ?? '')) ?: null,
            trim((string) ($this->request->getPost('secret_token') ?? '')) ?: null
        );
        $result = $client->testConnection();

        // Isi respons Dapodik TIDAK diteruskan ke browser: endpoint ini
        // memanggil alamat bebas, jadi badan respons bisa berisi data internal.
        return $this->response->setJSON([
            'status'  => (bool) ($result['status'] ?? false),
            'message' => $result['message'] ?? 'Gagal menghubungi endpoint Dapodik.',
            'code'    => $result['code'] ?? null,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * AJAX/POST — Jalankan Direct Sync via API Dapodik
     */
    public function syncApi(): ResponseInterface
    {
        if ($ditolak = $this->tolakNonAdmin()) {
            return $ditolak;
        }

        // Nilai entitas harus dari daftar yang dikenal. Sebelumnya apa pun yang
        // tidak dikenal jatuh ke default => syncAll, sehingga satu typo di form
        // menarik SELURUH master data.
        $entitasDikenal = ['gtk', 'siswa', 'rombel', 'mapel', 'sekolah', 'pembelajaran', 'ruang', 'all'];
        $entitas = strtolower(trim((string) ($this->request->getPost('entitas') ?? 'all')));
        if (!in_array($entitas, $entitasDikenal, true)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Jenis data sinkronisasi tidak dikenal. Pilih salah satu: ' . implode(', ', $entitasDikenal) . '.',
            ]);
        }

        $client  = new DapodikClient();
        $userId  = session()->get('id_user') ?? session()->get('user_id');

        try {
            switch ($entitas) {
                case 'gtk':
                    $res = $this->syncService->syncGtkFromApi($client, $userId);
                    break;
                case 'siswa':
                    $res = $this->syncService->syncSiswaFromApi($client, $userId);
                    break;
                case 'rombel':
                    $res = $this->syncService->syncRombelFromApi($client, $userId);
                    break;
                case 'mapel':
                    $res = $this->syncService->syncMapelFromApi($client, $userId);
                    break;
                case 'sekolah':
                    $res = $this->syncService->syncSekolahFromApi($client, $userId);
                    break;
                case 'pembelajaran':
                    $res = $this->syncService->syncPembelajaranFromApi($client, $userId);
                    break;
                case 'ruang':
                    $res = $this->syncService->syncRuangFromApi($client, $userId);
                    break;
                default:
                    $res = $this->syncService->syncAllFromApi($client, $userId);
                    break;
            }

            $res['csrfName'] = csrf_token();
            $res['csrfHash'] = csrf_hash();
            return $this->response->setJSON($res);
        } catch (\Throwable $e) {
            log_message('error', 'Dapodik::syncApi Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Sinkronisasi Dapodik berhenti karena galat sistem. Detail sudah dicatat di log; data yang sudah masuk tidak diurut ulang.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }



    /**
     * Hapus Riwayat Log Sinkronisasi (khusus POST — truncate jejak audit)
     */
    public function clearLogs()
    {
        if ($ditolak = $this->tolakNonAdmin()) {
            return $ditolak;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('dapodik_log')) {
            return $this->response->setJSON([
                'status'   => false,
                'message'  => 'Tabel riwayat Dapodik tidak ditemukan, jadi tidak ada yang dibersihkan.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $jumlahSebelum = (int) $db->table('dapodik_log')->countAllResults();
        $hasil         = $db->table('dapodik_log')->truncate();
        $sisa          = (int) $db->table('dapodik_log')->countAllResults();

        if ($hasil === false || $sisa > 0) {
            log_message('error', '[Dapodik::clearLogs] truncate dapodik_log gagal/tidak lengkap: ' . json_encode($db->error()));
            return $this->response->setJSON([
                'status'   => false,
                'message'  => 'Riwayat log gagal dibersihkan (' . $sisa . ' baris masih tersisa).',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status'   => true,
            'message'  => $jumlahSebelum > 0
                ? 'Riwayat log sinkronisasi Dapodik telah dibersihkan (' . $jumlahSebelum . ' baris).'
                : 'Riwayat log sudah kosong, tidak ada yang dibersihkan.',
            'dihapus'  => $jumlahSebelum,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}
