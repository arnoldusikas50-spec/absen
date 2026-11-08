<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Traits\CliOptionTrait;

/**
 * Backup database MySQL/MariaDB via mysqldump.
 *
 *   php spark db:backup [--keep=7]
 *
 * Hasil: writable/backups/db_YYYYmmdd_HHMMSS.sql.gz (di LUAR webroot,
 * jangan simpan backup di public/ agar tidak bisa diunduh publik).
 * Cron harian yang disarankan (02:00):
 *   0 2 * * * cd /www/wwwroot/absensi.ikas.my.id && php spark db:backup >> writable/logs/cron.log 2>&1
 */
class DbBackup extends BaseCommand
{
    use CliOptionTrait;

    protected $group       = 'Custom';
    protected $name        = 'db:backup';
    protected $description = 'Backup database (mysqldump + gzip + retensi)';
    protected $usage       = 'db:backup [--keep=7]';

    public function run(array $params)
    {
        // Opsi dibaca lewat trait, bukan dengan memindai $params: spark
        // menaruh nilai di KEY (hasil array_merge(segments, options)) dan
        // `--keep=30` bahkan masuk sebagai key 'keep=30' tanpa nilai, jadi
        // pemindaian lama selalu jatuh ke bawaan 7 tanpa peringatan.
        $keep = $this->optInt('keep', 7);

        $config = config('Database');
        $group = $config->defaultGroup ?? 'default';
        $db = $config->$group ?? null;
        if (!$db || !in_array($db['DBDriver'] ?? '', ['MySQLi', 'MySQL'], true)) {
            CLI::error('db:backup hanya mendukung MySQL/MariaDB.');
            return;
        }

        $dump = $this->findMysqldump();
        if ($dump === '') {
            $msg = 'mysqldump tidak ditemukan di server. Install mysql-client agar backup otomatis berjalan.';
            CLI::error($msg);
            log_message('error', '[db:backup] ' . $msg);
            $this->alert('❌ <b>BACKUP DB GAGAL</b>' . "\n" . $msg);
            return;
        }

        $dir = WRITEPATH . 'backups';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            CLI::error('Tidak bisa membuat direktori ' . $dir);
            return;
        }

        $stamp = date('Ymd_His');
        $tmpSql = $dir . '/.db_' . $stamp . '.sql';
        $final = $dir . '/db_' . $stamp . '.sql.gz';

        $host = $db['hostname'] ?? 'localhost';
        $port = (int) ($db['port'] ?? 3306);
        $name = $db['database'] ?? '';
        $user = $db['username'] ?? '';
        $pass = (string) ($db['password'] ?? '');
        if ($name === '' || $user === '') {
            CLI::error('Konfigurasi database belum lengkap.');
            return;
        }

        // Kredensial lewat file defaults 0600, bukan "MYSQL_PWD='pass' mysqldump".
        // String exec() dibaca /bin/sh -c "<semuanya>" dan whole string itu
        // terlihat di `ps` oleh user mana pun selama proses backup berjalan,
        // sehingga env-prefix tadi tidak menyembunyikan apa pun.
        $cnf = $dir . '/.my_' . $stamp . '.cnf';
        $passEsc = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', ''], $pass);
        @file_put_contents($cnf, "[client]\npassword=\"{$passEsc}\"\n");
        @chmod($cnf, 0600);

        $cmd = sprintf(
            '%s --defaults-extra-file=%s --single-transaction --quick --skip-lock-tables --routines --events -h %s -P %d -u %s %s --result-file=%s 2>&1',
            escapeshellarg($dump),
            escapeshellarg($cnf),
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($name),
            escapeshellarg($tmpSql)
        );

        $out = [];
        $code = 0;
        try {
            @exec($cmd, $out, $code);
            $ok = $code === 0 && is_file($tmpSql) && filesize($tmpSql) > 1024;
        } catch (\Throwable $e) {
            $ok = false;
            $out[] = $e->getMessage();
        } finally {
            @unlink($cnf);
        }

        if (empty($ok)) {
            @unlink($tmpSql);
            // Pesan mysqldump (stderr digabung ke stdout) bisa memuat host/nama
            // DB; kirim secukupnya ke log, jangan utuh-utuh ke channel notifikasi.
            $detail = trim(preg_replace('/\s+/', ' ', implode(' ', $out)) ?? '');
            $detail = mb_substr($detail, 0, 200);
            $msg = 'mysqldump gagal' . ($detail !== '' ? ': ' . $detail : '. Periksa kredensial DB.');
            CLI::error($msg);
            log_message('error', '[db:backup] ' . $msg . ' (exit=' . $code . ')');
            $this->alert('❌ <b>BACKUP DB GAGAL</b>' . "\n" . $msg . "\nWaktu: " . date('d/m/Y H:i'));
            return;
        }

        // Kompres dengan PHP (tanpa tergantung binary gzip)
        $fh = null;
        $gz = null;
        try {
            $gz = gzopen($final, 'wb9');
            $fh = fopen($tmpSql, 'rb');
            while (!feof($fh)) {
                gzwrite($gz, fread($fh, 1048576));
            }
        } catch (\Throwable $e) {
            CLI::error('Kompresi gagal: ' . $e->getMessage());
            // Jangan tinggal file setengah jadi + dump mentah di disk: retensi
            // hanya memindai db_*.sql.gz, dan .sql polos tidak akan pernah terhapus.
            if (is_resource($gz)) {
                gzclose($gz);
            }
            @unlink($final);
        } finally {
            if (is_resource($fh)) {
                fclose($fh);
            }
            @unlink($tmpSql);
        }

        // Retensi: simpan N terbaru
        $files = glob($dir . '/db_*.sql.gz') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }

        $size = round((filesize($final) ?: 0) / 1024 / 1024, 2);
        CLI::write("OK: {$final} ({$size} MB)", 'green');
        log_message('info', "[db:backup] OK {$final} ({$size} MB)");
    }

    private function findMysqldump(): string
    {
        $candidates = ['mysqldump'];
        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/lampp/bin/mysqldump'] as $p) {
            if (is_file($p) && is_executable($p)) {
                return $p;
            }
        }
        foreach ($candidates as $c) {
            $out = [];
            @exec('command -v ' . escapeshellarg($c) . ' 2>/dev/null', $out, $code);
            if ($code === 0 && !empty($out[0])) {
                return trim($out[0]);
            }
        }
        return '';
    }

    private function alert(string $msg): void
    {
        // Fallback WA bila TG mati: coba keduanya, jangan diam bila gagal.
        try {
            $notif = new \App\Services\NotificationService();
            $sent = $notif->sendTelegram($msg);
            try {
                $sent = $notif->sendWhatsApp($msg) || $sent;
            } catch (\Throwable $e2) {
            }
            if (!$sent) {
                log_message('error', '[db:backup] alert TG+WA gagal terkirim.');
            }
        } catch (\Throwable $e) {
            log_message('error', '[db:backup] alert gagal: ' . $e->getMessage());
        }
    }
}
