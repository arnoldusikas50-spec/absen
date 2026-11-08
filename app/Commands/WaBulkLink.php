<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Isi kolom WhatsApp notifikasi untuk sekumpulan guru sekaligus.
 *
 *   php spark wa:bulk --nomor=6285104899117 --dry-run
 *   php spark wa:bulk --nomor=6285104899117 --apply
 *
 * Ditulis lewat UserNotifikasiModel supaya normalisasi nomor dan
 * penanganan upsert-nya sama dengan jalur yang dipakai form Profil —
 bukan UPDATE mentah yang bisa melewati validasi.
 *
 * --dry-run (default) hanya melaporkan rencana tanpa menulis apa pun.
 */
class WaBulkLink extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'wa:bulk';
    protected $description = 'Isi wa_number untuk semua guru (default dry-run, --apply untuk menulis).';

    public function run(array $params)
    {
        // CLI::init() memangkas tanda '-' dari nama opsi TAPI tidak memisahkan
        // tanda '='. Jadi `--nomor=628xxx` tiba di sini sebagai key
        // 'nomor=628xxx' dengan nilai null — bukan key 'nomor'.
        // Karena itu nama opsi dicari dari kiri dengan prefix, bukan
        // lewat akses key langsung. Command check:denda di repo ini
        // tidak kena masalah ini karena argumennya positional.
        $nomorArg = $this->optionValue($params, 'nomor');
        if ($nomorArg === '') {
            $nomorArg = $this->positional($params, 0);
        }

        // CLI::init() mengubah argumen BERIKUTNYA jadi nilai opsi dan TIDAK
        // memisahkan '='. Akibatnya ada dua bentuk yang harus ditangani:
        //   `--apply=1`  -> key 'apply=1' bernilai null
        //   `--apply`    -> key 'apply' bernilai null
        // Keduanya bernilai null, jadi harus dicek dari nama kuncinya,
        // bukan dari nilainya.
        $apply = $this->hasOption($params, 'apply')
            || $this->positional($params, 1) === 'apply';


        // Validasi lewat pustaka yang sama dengan form, supaya CLI dan UI
        // tidak bisa menerima format berbeda.
        try {
            $nomor = \App\Libraries\WhatsAppLink::normalizeNumber(
                is_string($nomorArg) ? $nomorArg : ''
            );
        } catch (\InvalidArgumentException $e) {
            CLI::error('Nomor ditolak: ' . $e->getMessage());
            CLI::write('Contoh benar: --nomor=6285104899117');
            return;
        }
        if ($nomor === '') {
            // Nama opsi saja, tanpa '=' → CLI::init() menyimpan nilainya
            // sebagai elemen terpisah (atau null). Beri tahu bentuk yang
            // benar daripada diam-diam gagal.
            CLI::error('--nomor wajib diisi.');
            CLI::error('Hint: pakai tanda "=" — --nomor=6285104899117');
            return;
        }

        $db = \Config\Database::connect();

        // Guru = user aktif di group 2 yang tertaut ke master_guru.
        $guru = $db->table('master_guru g')
            ->select('g.id_guru, g.nama_guru, g.id_user, g.no_hp')
            ->join('users u', 'u.id = g.id_user')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->orderBy('g.id_guru', 'ASC')
            ->get()->getResult();

        CLI::write('Nomor tujuan : ' . $nomor . '  (OpenWA: ' . $nomor . '@c.us)');
        CLI::write('Mode         : ' . ($apply ? 'APPLY (menulis ke database)' : 'DRY-RUN (tidak menulis)'));
        CLI::write('Guru ditangani: ' . count($guru));
        CLI::write('');

        $model = new \App\Models\UserNotifikasiModel();
        $ok = 0;
        $gagal = 0;
        $tabel = [];

        foreach ($guru as $g) {
            $userId = (int) $g->id_user;
            if ($userId <= 0) {
                CLI::error('  guru id=' . $g->id_guru . ' tanpa id_user, dilewati');
                $gagal++;
                continue;
            }

            // verifying = true: nomor sudah disetujui admin lewat CLI ini,
            // jadi tidak perlu menunggu konfirmasi halaman tautan.
            if ($apply) {
                $tersimpan = $model->setWaNumber($userId, $nomor, true);
                $masterOk = true;
                try {
                    if ($db->fieldExists('wa_number', 'master_guru')) {
                        $db->table('master_guru')->where('id_guru', $g->id_guru)
                            ->update(['wa_number' => $nomor]);
                    }
                } catch (\Throwable $e) {
                    $masterOk = false;
                    log_message('error', '[wa:bulk] master_guru id=' . $g->id_guru . ': ' . $e->getMessage());
                }
                $sukses = $tersimpan && $masterOk;
            } else {
                $sukses = true;
            }

            if ($sukses) {
                $ok++;
                $tabel[] = sprintf('  OK   %-38s user=%-5d no_hp=%s', $g->nama_guru ?? '-', $userId, $g->no_hp ?: '(kosong)');
            } else {
                $gagal++;
                CLI::error('  GAGAL ' . ($g->nama_guru ?? '-') . ' user=' . $userId);
            }
        }

        CLI::write(implode("\n", $tabel));
        CLI::write('');
        CLI::write("Berhasil: {$ok}   Gagal: {$gagal}");

        if (! $apply) {
            CLI::write('');
            CLI::write('Dry-run selesai, tidak ada yang diubah. Tambahkan --apply untuk menulis.');
            return;
        }

        // Id sesi di-cache 60 detik; tidak perlu flush di sini karena
        // konfigurasi gateway tidak berubah, hanya data tujuan.
        CLI::write('');
        CLI::write('Selesai. Cek daftar di menu Kelola Tautan Guru.');
    }

/**
 * True bila opsi dengan nama tersebut ada, apa pun bentuknya
 * (`--nama`, `--nama=1`, atau key persis nama). Nilai flag selalu null
 * dari CLI::init(), jadi yang dicek adalah kehadirannya.
 */
private function hasOption(array $params, string $name): bool
{
    if (array_key_exists($name, $params)) {
        return true;
    }
    foreach (array_keys($params) as $key) {
        if (is_string($key) && str_starts_with($key, $name . '=')) {
            return true;
        }
    }
    return false;
}

/**
 * Cari nilai opsi berdasarkan nama, toleran terhadap key bergaya
 * `nama=nilai` yang muncul karena CLI::init() tidak memisahkan '='.
 *
 * @return string '' bila opsi tidak ada atau hanya flag tanpa nilai
 */
private function optionValue(array $params, string $name): string
{
    // Bentuk biasa: key persis nama, nilai di elemen terpisah.
    if (isset($params[$name]) && is_string($params[$name])) {
        return trim($params[$name]);
    }
    if (($params[$name] ?? null) === true) {
        return '1'; // flag tanpa nilai, mis. --apply
    }

    // Bentuk `nama=nilai` yang menyatu di KUNCI dengan nilai null.
    foreach ($params as $key => $value) {
        if (! is_string($key)) {
            continue;
        }
        if (str_starts_with($key, $name . '=')) {
            return trim(substr($key, strlen($name) + 1));
        }
    }

    return '';
}

/**
 * Ambil argumen positional ke-$n (tanpa option), atau string kosong.
 */
    private function positional(array $params, int $n): string
    {
        $positional = [];
        foreach ($params as $key => $value) {
            if (is_int($key) && is_string($value) && ! str_starts_with($value, '-')) {
                $positional[] = $value;
            }
        }
        return $positional[$n] ?? '';
    }
}