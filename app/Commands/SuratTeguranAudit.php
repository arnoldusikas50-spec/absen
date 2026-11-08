<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Audit & perbaikan data surat teguran yang rusak oleh bug lama.
 *
 * Dua kerusakan berasal dari logika penerbitan yang salah dan sudah
 * Produksi. Perintah ini memperbaiki yang bisa dibuktikan dari data yang
 * ada, dan MELAPORKAN yang tidak — menebak dokumen legal lebih berbahaya
 * daripada membiarkannya tidak terlacak.
 *
 * Yang diperbaiki (aman, hanya menyelaraskan angka nonsensical):
 *   - jumlah_alpha < level. PDF menampilkan "Alpha N hari" berdampingan
 *     dengan "TINGKAT III (10 Hari)"; kalau N lebih kecil dari ambangnya,
 *     suratnya kontradiktif dengan dirinya sendiri. Diperbaiki ke ambang.
 *
 * Yang dilaporkan, TIDAK diubah otomatis:
 *   - id_guru = 0. Surat tidak bisa diatribusikan ke guru mana pun tanpa
 *     menebak: daftar admin memakai INNER JOIN ke master_guru, jadi nama
 *     dan NIP guru yang benar tidak pernah ikut tersimpan. Data presensi
     *     Mei–Juli 2026 juga tidak ada untuk memverifikasi. Barisnya tidak
     *     dihapus — arsip sekolah tidak boleh hilang tanpa keputusan manusia.
 *
 * Jalankan dulu tanpa --apply untuk melihat apa yang akan berubah.
 */
class SuratTeguranAudit extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'teguran:audit';
    protected $description = 'Audit dan perbaiki data surat teguran (jumlah_alpha rusak, surat yatim).';
    protected $usage       = 'teguran:audit [--apply]';
    protected $arguments   = [
        'apply' => 'Terapkan perbaikan. Tanpa flag ini hanya laporan.',
    ];

    public function run(array $params): int
    {
        // CLI::run() merakit $params dari getSegments() (positional) dan
        // getOptions() (key => nilai). Jadi `php spark teguran:audit --apply`
        // sampai di sini sebagai ['apply' => true] — flag ada di KEY, bukan
        // di value. Yang tetap diperiksa juga value-nya supaya 'apply'
        // tanpa tanda hubung (dipakai di crontab) ikut dikenali.
        $apply = array_key_exists('apply', $params);

        if (! $apply) {
            foreach ($params as $key => $value) {
                $k = is_string($key) ? $key : (string) $value;
                $v = is_string($value) ? $value : '';

                if (ltrim($k, '-') === 'apply' || ltrim($v, '-') === 'apply') {
                    $apply = true;
                    break;
                }
            }
        }

        $db = \Config\Database::connect();
        if (! $db->tableExists('absensi_surat_teguran')) {
            CLI::error('Tabel absensi_surat_teguran tidak ada — jalankan php spark migrate.');

            return 1;
        }

        CLI::write(
            $apply
                ? 'Mode: TERAPKAN'
                : 'Mode: LAPORAN (gunakan --apply untuk mengubah data)',
            $apply ? 'green' : 'yellow'
        );
        CLI::newline();

        $diperbaiki = $this->perbaikiSnapshot($db, $apply);
        $this->laporanYatim($db);

        CLI::newline();
        if (! $apply) {
            CLI::write('Tidak ada data yang diubah. Jalankan ulang dengan --apply untuk menerapkan.');

            return 0;
        }

        CLI::write('Snapshot jumlah_alpha diperbaiki: ' . $diperbaiki . ' baris', 'green');

        return 0;
    }

    /**
     * jumlah_alpha tidak boleh di bawah ambang surat.
     *
     * Sources of truth: level di baris itu sendiri. Nilai aslinya sudah
     * hilang (0 = kolom tidak diisi saat surat terbit lewat jalur check-in),
     * jadi yang bisa dilakukan hanya menyelaraskan ke batas yang pasti
     * benar — bukan menebak angka alpha sebenarnya.
     */
    private function perbaikiSnapshot($db, bool $apply): int
    {
        $rows = $db->table('absensi_surat_teguran')
            ->select('id, id_guru, bulan, tahun, level, jumlah_alpha')
            ->where('jumlah_alpha <', 'level', false)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            CLI::write('Snapshot jumlah_alpha: semua baris sudah >= ambang surat.');

            return 0;
        }

        $n = 0;
        foreach ($rows as $row) {
            $level = (int) $row['level'];
            $alpha = (int) ($row['jumlah_alpha'] ?? 0);
            $label = sprintf(
                'id=%d guru=%d %d/%d level=%d alpha=%d',
                (int) $row['id'],
                (int) $row['id_guru'],
                (int) $row['bulan'],
                (int) $row['tahun'],
                $level,
                $alpha
            );

            if (! $apply) {
                CLI::write('  akan diselaraskan ke ' . $level . ': ' . $label);
                $n++;
                continue;
            }

            $db->table('absensi_surat_teguran')
                ->where('id', $row['id'])
                ->update(['jumlah_alpha' => $level]);

            CLI::write('  diperbaiki: ' . $label . ' -> alpha ' . $level);
            $n++;
        }

        return $n;
    }

    /**
     * Surat tanpa guru yang bisa ditelusuri.
     *
     * Hanya dilaporkan. Menghapus atau menebaknya sama-sama merusak: hapus
     * menghilangkan arsip, tebak bisa menempelkan surat orang yang keliru
     * ke kepala orang lain.
     */
    private function laporanYatim($db): void
    {
        $rows = $db->table('absensi_surat_teguran a')
            ->select('a.id, a.id_guru, a.bulan, a.tahun, a.level, a.status, a.nomor_urut')
            ->join('master_guru g', 'g.id_guru = a.id_guru', 'left')
            ->where('g.id_guru IS NULL', null, false)
            ->orderBy('a.id', 'ASC')
            ->get()
            ->getResultArray();

        CLI::newline();
        if ($rows === []) {
            CLI::write('Surat yatim: tidak ada.');

            return;
        }

        CLI::write(
            sprintf(
                '%d surat tidak bisa ditelusuri ke guru (id_guru tidak ada di master_guru):',
                count($rows)
            ),
            'yellow'
        );
        foreach ($rows as $row) {
            CLI::write(sprintf(
                '  id=%d id_guru=%d %d/%d level=%d status=%s no=%d',
                (int) $row['id'],
                (int) $row['id_guru'],
                (int) $row['bulan'],
                (int) $row['tahun'],
                (int) $row['level'],
                (string) $row['status'],
                (int) $row['nomor_urut']
            ));
        }
        CLI::write('');
        CLI::write('  Tidak diubah otomatis. Surat ini tidak muncul di daftar admin dan');
        CLI::write('  tidak bisa diunduh. Perbaiki master_guru guru terkait, atau hapus');
        CLI::write('  manual lewat SQL kalau memang tidak ada pemiliknya.');
        CLI::write('  Sumber: id_guru tidak ada di tabel master_guru (INNER JOIN di daftar admin).');
    }
}
