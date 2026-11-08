<?php

namespace App\Libraries;

use CodeIgniter\Config\Services;
use Exception;

/**
 * DapodikClient — HTTP REST API Client untuk Webservice Dapodik Local.
 *
 * Webservice Dapodik berjalan secara lokal pada aplikasi Dapodik sekolah (port default 5774/5773)
 * atau dimuka publik melalui reverse proxy server sekolah.
 */
class DapodikClient
{
    protected string $serverUrl;
    protected string $npsn;
    protected string $secretToken;
    protected int $timeout = 15;

    public function __construct(?string $serverUrl = null, ?string $npsn = null, ?string $secretToken = null)
    {
        $db = \Config\Database::connect();
        $settings = [];
        if ($db->tableExists('dapodik_settings')) {
            $settings = $db->table('dapodik_settings')->get()->getRowArray() ?? [];
        }

        $this->serverUrl   = rtrim($serverUrl ?? ($settings['server_url'] ?? 'http://localhost:5774'), '/');
        $this->npsn        = $npsn ?? ($settings['npsn'] ?? '');
        $this->secretToken = $secretToken ?? ($settings['secret_token'] ?? '');
    }

    /**
     * Test koneksi HTTP ke server Dapodik Web Service.
     */
    public function testConnection(): array
    {
        if (empty($this->serverUrl)) {
            return [
                'status'  => false,
                'message' => 'URL Server Dapodik belum dikonfigurasi.',
            ];
        }

        $result = $this->request('/WebService/getSekolah');

        if ($result['status']) {
            return [
                'status'  => true,
                'message' => 'Koneksi ke Web Service Dapodik berhasil!',
                'code'    => 200,
            ];
        }

        return [
            'status'  => false,
            'message' => $result['message'] ?? 'Gagal menghubungi endpoint Dapodik.',
            'code'    => $result['code'] ?? 500,
        ];
    }

    /**
     * Fetch Data Sekolah
     */
    public function getSekolah(): array
    {
        return $this->request('/WebService/getSekolah');
    }

    /**
     * Fetch Data GTK (Guru & Tenaga Kependidikan)
     */
    public function getGtk(): array
    {
        return $this->request('/WebService/getGtk');
    }

    /**
     * Fetch Data Peserta Didik (Siswa)
     */
    public function getPesertaDidik(): array
    {
        return $this->request('/WebService/getPesertaDidik');
    }

    /**
     * Fetch Data Rombongan Belajar (Kelas)
     */
    public function getRombonganBelajar(): array
    {
        return $this->request('/WebService/getRombonganBelajar');
    }

    /**
     * Fetch Data Anggota Rombel (Mapping Siswa ke Kelas)
     */
    public function getAnggotaRombel(): array
    {
        return $this->request('/WebService/getAnggotaRombel');
    }

    /**
     * Fetch Data Pembelajaran (Mata Pelajaran Guru)
     */
    public function getPembelajaran(): array
    {
        $res = $this->request('/WebService/getPembelajaran');
        if (!$res['status'] || empty($res['data'])) {
            $res = $this->request('/WebService/getJamPembelajaran');
        }
        if (!$res['status'] || empty($res['data'])) {
            $res = $this->request('/WebService/getPembelajaranRombel');
        }
        return $res;
    }

    /**
     * Fetch Data Nilai Siswa
     */
    public function getNilai(string $pembelajaranId, string $table = 'rapor'): array
    {
        $endpoint = '/WebService/getNilai?table=' . urlencode($table) . '&id_evaluasi=' . urlencode($pembelajaranId);
        return $this->request($endpoint);
    }

    /**
     * Fetch Data Akun Pengguna / User Portal Dapodik
     */
    public function getPengguna(): array
    {
        return $this->request('/WebService/getPengguna');
    }

    /**
     * Fetch Data Ruang Kelas / Laboratorium Dapodik
     */
    public function getRuang(): array
    {
        return $this->request('/WebService/getRuang');
    }

    /**
     * Fetch Data Sarana Prasarana Sekolah
     */
    public function getSarpras(): array
    {
        return $this->request('/WebService/getSarpras');
    }

    /**
     * Generic HTTP Request ke Dapodik Web Service API
     */
    protected function request(string $endpoint): array
    {
        if (empty($this->serverUrl)) {
            return ['status' => false, 'message' => 'URL Server Dapodik belum diatur.', 'data' => []];
        }

        // URL berasal dari kolom konfigurasi yang bisa diedit, jadi batasinya
        // di sini: hanya http/https. Tanpa ini alamat seperti file:// atau
        // gopher:// ikut dieksekusi curl dan server yang diajak bicara
        // bukan lagi web service Dapodik.
        if (!$this->skemaDiizinkan()) {
            log_message('error', '[DapodikClient] skema URL ditolak: ' . $this->serverUrl);
            return ['status' => false, 'message' => 'URL Server Dapodik tidak sah. Hanya http:// atau https:// yang didukung.', 'data' => []];
        }

        try {
            $sep = str_contains($endpoint, '?') ? '&' : '?';
            $npsnParam = !empty($this->npsn) ? ($sep . 'npsn=' . urlencode($this->npsn)) : '';
            $fullUrl = rtrim($this->serverUrl, '/') . $endpoint . $npsnParam;

            $client = Services::curlrequest([
                'timeout'     => $this->timeout,
                'http_errors' => false,
                'verify'      => $this->verifikasiTls(),
            ]);

            $headers = [
                'Authorization' => 'Bearer ' . $this->secretToken,
                'Accept'        => 'application/json',
            ];

            $response = $client->get($fullUrl, ['headers' => $headers]);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            if ($statusCode === 200) {
                $json = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $rows = $json['rows'] ?? $json['data'] ?? $json;
                    return [
                        'status'  => true,
                        'message' => 'Data berhasil diambil dari Dapodik.',
                        'total'   => is_array($rows) ? count($rows) : 0,
                        'data'    => $rows,
                    ];
                }

                return [
                    'status'  => false,
                    'message' => 'Jawaban server Dapodik bukan JSON yang valid; periksa URL dan port web service.',
                    'code'    => $statusCode,
                    'data'    => [],
                ];
            }

            return [
                'status'  => false,
                'message' => "Gagal mengambil data dari Dapodik (HTTP {$statusCode}).",
                'code'    => $statusCode,
                'data'    => [],
            ];
        } catch (\Throwable $e) {
            // Pesan curl memuat host/port internal; simpan di log, tampilkan
            // pesan manusianya saja ke pemanggil.
            log_message('error', '[DapodikClient] request ' . $endpoint . ' gagal: ' . $e->getMessage());
            return [
                'status'  => false,
                'message' => 'Tidak dapat menghubungi web service Dapodik. Pastikan aplikasi Dapodik berjalan dan URL/port sudah benar.',
                'code'    => 500,
                'data'    => [],
            ];
        }
    }

    /**
     * Hanya http/https yang boleh dipanggil.
     */
    private function skemaDiizinkan(): bool
    {
        $scheme = strtolower((string) parse_url($this->serverUrl, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Token Bearer ikut terkirim pada setiap request, jadi koneksi TLS harus
     * terverifikasi. Layanan Dapodik berjalan di localhost/jaringan sekolah
     * dengan sertifikat self-signed, sehingga alamat privat tetap dilonggarkan.
     */
    private function verifikasiTls(): bool
    {
        if (strtolower((string) parse_url($this->serverUrl, PHP_URL_SCHEME)) !== 'https') {
            return true;
        }

        $host = strtolower((string) parse_url($this->serverUrl, PHP_URL_HOST));
        $host = trim($host, '[]');
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.localdomain')) {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            // nama host tidak terpecah -> anggap layanan lokal, jangan kunci admin
            return false;
        }

        // IP publik: sertifikat harus valid (token Bearer ikut terkirim di
        // koneksi ini). IP privat/loopback/link-local: sekolah lazim memakai
        // sertifikat self-signed di dalam jaringan sendiri.
        $publik = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        return $publik !== false;
    }
}
