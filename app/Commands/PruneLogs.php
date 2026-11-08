<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Traits\CliOptionTrait;

/**
 * Retensi file log aplikasi.
 *
 *   php spark logs:prune [--keep=60] [--cron-max-mb=20] [--dry]
 *
 * Kenapa perlu: CI4 menulis satu berkas baru per hari di writable/logs
 * (log-YYYY-MM-DD.log) dan crontab menempel ke satu berkas writable/logs/cron.log.
 * Tidak ada mekanisme penghapusan di tempat lain, jadi keduanya tumbuh terus
 * tanpa batas. Sisa disk nol berarti absen harian berhenti tersimpan —
 * health:check hanya berbunyi saat sisa tinggal < 200 MB.
 *
 * Yang disentuh HANYA pola nama berkas di writable/logs:
 *   - cron.log  : dirotasi (rename) saat melebihi batas ukuran, tidak dihapus
 *   - log-*.log / log-*.php / cron-*.log : dihapus bila umurnya lewat --keep hari
 * Berkas pelindung direktori (index.html, .htaccess), probe health
 * (.health_probe), symlink, dan seluruh isi tabel database tidak pernah disentuh.
 *
 * Cron harian yang disarankan (03:30, setelah backup 02:00):
 *   30 3 * * * cd /www/wwwroot/absensi.ikas.my.id && php spark logs:prune >> writable/logs/cron.log 2>&1
 */
class PruneLogs extends BaseCommand
{
    use CliOptionTrait;

    protected $group       = 'Custom';
    protected $name        = 'logs:prune';
    protected $description = 'Rotasi cron.log & hapus file log lama (retensi disk)';
    protected $usage       = 'logs:prune [--keep=60] [--cron-max-mb=20] [--dry]';

    /** Pola berkas usang yang boleh dihapus. */
    private const POLA_HAPUS = ['log-*.log', 'log-*.php', 'cron-*.log'];

    public function run(array $params)
    {
        $keep       = $this->optInt('keep', 60);
        $cronMaksMb = $this->optInt('cron-max-mb', 20);
        $dry        = $this->optBool('dry');

        $dir = WRITEPATH . 'logs';
        if (!is_dir($dir)) {
            CLI::error('Direktori log tidak ada: ' . $dir);
            return;
        }

        $batas = time() - ($keep * 86400);
        $dihapus = 0;
        $byteDibebaskan = 0;
        $gagal = [];

        // 1. Rotasi cron.log bila membengkak. Nama hasil rotasi ikut pola
        //    cron-*.log, jadi umur --keep berlaku untuknya di langkah 2.
        $cron = $dir . '/cron.log';
        if (is_file($cron) && !is_link($cron)) {
            $ukur = (int) (@filesize($cron) ?: 0);
            if ($ukur > $cronMaksMb * 1024 * 1024) {
                $tujuan = $this->namaRotasi($dir, $ukur);
                CLI::write('cron.log ' . round($ukur / 1048576, 1) . ' MB → rotasi ke ' . basename($tujuan));
                if ($dry) {
                    log_message('info', '[logs:prune] dry-run: akan merotasi cron.log ke ' . $tujuan);
                } elseif (!@rename($cron, $tujuan)) {
                    // Jangan pernah men-truncate cron.log: shell cron membuka
                    // berkas ini per panggilan, dan menimpa inode yang sedang
                    // dipakai membuat tulisan berikutnya hilang diam-diam.
                    $gagal[] = 'rotasi cron.log';
                    log_message('error', '[logs:prune] rotasi cron.log gagal → ' . $tujuan);
                }
            }
        }

        // 2. Hapus berkas log usang.
        foreach (self::POLA_HAPUS as $pola) {
            foreach (glob($dir . '/' . $pola) ?: [] as $file) {
                // Symlink tidak pernah dihapus: unlink lewat symlink menembus
                // direktori lain dan mencabut berkas milik orang lain.
                if (!is_file($file) || is_link($file)) {
                    continue;
                }
                $umur = @filemtime($file);
                if ($umur === false || $umur >= $batas) {
                    continue;
                }
                $byte = (int) (@filesize($file) ?: 0);
                if ($dry) {
                    CLI::write('akan dihapus: ' . basename($file) . ' (' . round($byte / 1024, 1) . ' KB, ' . date('Y-m-d', $umur) . ')', 'yellow');
                    $dihapus++;
                    $byteDibebaskan += $byte;
                    continue;
                }
                if (@unlink($file)) {
                    $dihapus++;
                    $byteDibebaskan += $byte;
                } else {
                    $gagal[] = basename($file);
                }
            }
        }

        $ringkas = sprintf(
            '%s: %d berkas (%s), retensi %d hari%s',
            $dry ? 'DRY-RUN' : 'OK',
            $dihapus,
            $this->fmtByte($byteDibebaskan),
            $keep,
            $gagal === [] ? '' : ', GAGAL: ' . implode(', ', $gagal)
        );
        $gagal === [] ? CLI::write($ringkas, 'green') : CLI::error($ringkas);
        log_message($gagal === [] ? 'info' : 'error', '[logs:prune] ' . $ringkas);
    }

    /**
     * Nama tujuan rotasi yang tidak menimpa hasil rotasi sebelumnya.
     */
    private function namaRotasi(string $dir, int $ukur): string
    {
        $dasar = sprintf('%s/cron-%s_%dMB.log', $dir, date('Ymd-His'), round($ukur / 1048576));
        $tujuan = $dasar;
        for ($i = 2; is_file($tujuan) && $i < 100; $i++) {
            $tujuan = sprintf('%s/cron-%s_%dMB-%d.log', $dir, date('Ymd-His'), round($ukur / 1048576), $i);
        }
        return $tujuan;
    }

    private function fmtByte(int $byte): string
    {
        if ($byte >= 1048576) {
            return round($byte / 1048576, 2) . ' MB';
        }
        return round($byte / 1024, 1) . ' KB';
    }
}
