<?php

namespace App\Libraries;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * TteQr — QR Code pengganti tanda tangan basah Kepala Sekolah
 * pada Surat Teguran (Tanda Tangan Elektronik sederhana).
 *
 * QR berisi identitas dokumen + penandatangan + kode verifikasi
 * deterministik (cetakan ulang menghasilkan kode yang sama).
 */
class TteQr
{
    /**
     * Kode verifikasi deterministik, format XXXX-XXXX-XXXX.
     */
    public static function verificationCode(string $nomorSurat, int $idGuru, int $level, int $bulan, int $tahun): string
    {
        $raw  = strtoupper(substr(hash('sha256', implode('|', [$nomorSurat, $idGuru, $level, $bulan, $tahun])), 0, 12));
        return substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
    }

    /**
     * Bangun teks payload QR.
     */
    public static function payload(string $nomorSurat, string $namaGuru, string $nipGuru, string $tingkat, int $jumlahAlpha, string $kode, string $kepsekNama, string $kepsekNip, string $namaSekolah = ''): string
    {
        $sekolah = trim($namaSekolah) !== '' ? trim($namaSekolah) : 'SEKOLAH';

        return implode("\n", [
            'DOKUMEN ELEKTRONIK - SURAT TEGURAN',
            strtoupper($sekolah),
            'Nomor: ' . $nomorSurat,
            'Kepada: ' . $namaGuru . ' (NIP. ' . $nipGuru . ')',
            'Teguran: ' . $tingkat . ' - Alpha ' . $jumlahAlpha . ' hari',
            'Ditandatangani secara elektronik oleh:',
            $kepsekNama . ' (NIP. ' . $kepsekNip . ')',
            'Kode verifikasi: ' . $kode,
        ]);
    }

    /**
     * Render QR PNG sebagai data-URI. Return '' bila gagal
     * (pemanggil harus fallback ke blok tanda tangan biasa).
     */
    public static function dataUri(string $text, int $size = 200): string
    {
        try {
            $qr = QrCode::create($text)->setSize($size)->setMargin(8);
            return (new PngWriter())->write($qr)->getDataUri();
        } catch (\Throwable $e) {
            log_message('error', 'TteQr gagal: ' . $e->getMessage());
            return '';
        }
    }
}
