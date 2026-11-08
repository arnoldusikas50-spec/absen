<?php

namespace App\Traits;

use CodeIgniter\CLI\CLI;

/**
 * CliOptionTrait - Pembaca opsi baris perintah yang andal untuk perintah spark.
 *
 * Kenapa perlu: CLI::parseCommandLine() (system/CLI/CLI.php:885-910) tidak
 * memisah tanda "=" pada opsi. Bentuk `--keep=999` tersimpan sebagai KEY
 * 'keep=999' dengan nilai null, sedangkan `--keep 999` menjadi key 'keep'
 * bernilai '999'. Akibatnya dua pola yang kelihatannya benar sama-sama gagal:
 *   - CLI::getOption('keep')      -> null untuk bentuk `--keep=999`
 *   - foreach ($params as $p) { if (str_starts_with($p, 'keep=')) ... }
 *     -> tidak pernah match, karena $params hasil array_merge(segments, options)
 *        menaruh nama opsi di KEY, sedang $p hanya berisi nilainya.
 *
 * Saat perintah dijalankan dari kode (Commands::run) CLI::$options tidak terisi,
 * jadi pemanggil seperti itu tetap mendapat nilai bawaan, bukan error.
 */
trait CliOptionTrait
{
    /**
     * Nilai mentah sebuah opsi: string | true (flag tanpa nilai) | null (tidak ada).
     */
    protected function opt(string $nama)
    {
        $opsi = CLI::getOptions();

        if (array_key_exists($nama, $opsi)) {
            return $opsi[$nama] ?? true;
        }

        $prefix = $nama . '=';
        foreach ($opsi as $key => $value) {
            if (is_string($key) && str_starts_with($key, $prefix)) {
                return $value === null ? substr($key, strlen($prefix)) : $value;
            }
        }

        return null;
    }

    /**
     * Opsi integer. Tanpa nilai, non-numerik, atau < 1 tidak boleh diam-diam
     * jadi 1 — retensi 1 hari berarti menghapus hampir semua yang seharusnya
     * disimpan — jadi nilai rusak kembali ke bawaan dengan peringatan.
     */
    protected function optInt(string $nama, int $bawaan): int
    {
        $nilai = $this->opt($nama);

        if ($nilai === null || $nilai === true) {
            return $bawaan;
        }

        if (! is_numeric($nilai) || (int) $nilai < 1) {
            CLI::write("--{$nama}=\"{$nilai}\" bukan angka ≥ 1, pakai bawaan {$bawaan}.", 'yellow');

            return $bawaan;
        }

        return (int) $nilai;
    }

    /**
     * Opsi flag. `--dry`, `--dry=1`, `--dry=true` aktif; `--dry=0` tidak.
     */
    protected function optBool(string $nama): bool
    {
        $nilai = $this->opt($nama);

        if ($nilai === null) {
            return false;
        }

        return $nilai === true || filter_var($nilai, FILTER_VALIDATE_BOOLEAN);
    }
}
