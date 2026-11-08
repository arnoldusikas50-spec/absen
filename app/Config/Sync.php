<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi Sinkronisasi Data absensi by ikasmedia
 * 
 * Sistem sinkronisasi untuk 1 sekolah:
 * - Server PUSAT (cloud/hosting) - menyimpan master data & backup
 * - Server LOKAL (sekolah) - menjalankan ujian
 */
class Sync extends BaseConfig
{
    /**
     * Mode Server
     * - 'pusat' : Server ini adalah server pusat
     * - 'lokal' : Server ini adalah server lokal
     */
    public string $serverMode = 'lokal';

    /**
     * URL Server Pusat
     * Diisi jika serverMode = 'lokal'
     * Contoh: 'https://absensi-pusat.sekolah.sch.id'
     */
    public string $pusatUrl = '';

    /**
     * API Key untuk autentikasi
     * Harus sama di server pusat dan lokal
     */
    public string $apiKey = '';

    /**
     * Timeout koneksi dalam detik
     */
    public int $timeout = 60;

    /**
     * Retry attempts jika sync gagal
     */
    public int $retryAttempts = 3;

    /**
     * Aktifkan logging detail
     */
    public bool $enableLogging = true;

    /**
     * Paksa setiap request sync menggunakan signature HMAC
     */
    public bool $enforceSignature = true;

    /**
     * Batas toleransi timestamp signature (detik)
     */
    public int $signatureDrift = 300;

    /**
     * Verifikasi checksum file saat download (pusat -> lokal)
     */
    public bool $enableChecksum = true;

    /**
     * Batasi ukuran batch sync (jumlah record per request)
     */
    public int $batchSize = 100;

    /**
     * Tabel-tabel yang akan disinkronkan
     */
    public array $syncTables = [
        // Data Master (Pusat → Lokal) - Sebelum Ujian
        'push_to_local' => [
            'master_siswa',
            'master_kelas',
            'master_mapel',
            'master_guru',
            'cbt_jenis',
            'cbt_bank_soal',
            'cbt_soal',
            'cbt_jadwal',
            'cbt_sesi',
            'cbt_ruang',
            'cbt_sesi_siswa',
            'kelas_siswa',
        ],

        // Hasil Ujian (Lokal → Pusat) - Setelah Ujian
        'push_to_pusat' => [
            'cbt_siswa',
            'cbt_jawaban',
            'cbt_nilai',
            'cbt_rekap_siswa',
            'cbt_rekap_jadwal',
        ]
    ];
}
