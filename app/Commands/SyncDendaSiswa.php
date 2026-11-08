<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Hitung ulang denda siswa satu bulan.
 *
 * Tanpa argumen bulan berjalan yang dikerjakan. Nominal hanya bisa tersimpan
 * pada baris presensi yang ada; alpha tanpa baris tetap muncul di kolom Denda
 * rekap (dihitung saat rekap dibaca) dan dilaporkan di sini supaya selisihnya
 * tidak disangka bug.
 */
class SyncDendaSiswa extends BaseCommand
{
    protected $group       = 'Absensi';
    protected $name        = 'denda:sync-siswa';
    protected $description = 'Hitung ulang denda siswa satu bulan (default: bulan berjalan)';
    protected $usage       = 'denda:sync-siswa [bulan] [tahun]';

    public function run(array $params)
    {
        $bulan = isset($params[0]) ? (int) $params[0] : null;
        $tahun = isset($params[1]) ? (int) $params[1] : null;

        $svc   = new \App\Services\SiswaDendaService();
        $hasil = $svc->recalcBulanBerjalan($bulan, $tahun);

        CLI::write($hasil['message'], 'green');
        CLI::write(sprintf(
            'baris: periksa=%d diubah=%d tertagih=%d | alpha tanpa baris=%d | total baris=Rp %s | total rekap=Rp %s',
            $hasil['teriksa'],
            $hasil['diubah'],
            $hasil['tertagih'],
            $hasil['tanpa_baris'],
            number_format($hasil['total'], 0, ',', '.'),
            number_format($hasil['total_rekap'], 0, ',', '.')
        ));
        CLI::write(sprintf(
            'tarif: alpha Rp %s/hari · bolos Rp %s/hari · plafon %s/siswa/bulan · siswa dinilai=%d',
            number_format($svc->tarif('alpha'), 0, ',', '.'),
            number_format($svc->tarif('bolos'), 0, ',', '.'),
            $svc->capBulan() > 0 ? 'Rp ' . number_format($svc->capBulan(), 0, ',', '.') : 'tanpa batas',
            $hasil['siswa']
        ));

        if ($svc->tarif('alpha') <= 0 && $svc->tarif('bolos') <= 0) {
            CLI::write(
                'Semua tarif 0: fitur denda siswa masih mati, jadi tidak ada nominal yang ditulis maupun tertagih.',
                'yellow'
            );
        }
    }
}
