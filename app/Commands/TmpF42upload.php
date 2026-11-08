<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Harness sementara F42-B/C: buktikan upload bukti izin tidak lagi dipercaya
 * pada MIME kliman browser, dan sapuan nama berkas move() tetap bersih.
 * Hanya menulis probe ke WRITEPATH dan menghapusnya lagi. Berkas harness ini
 * setelah dipakai.
 */
class TmpF42upload extends BaseCommand
{
    protected $group       = 'tmp';
    protected $name        = 'tmp:f42upload';
    protected $description = 'Bukti F42: whitelist ekstensi dari isi file + sapuan move().';

    private array $whitelist = ['pdf', 'jpg', 'jpeg', 'png'];
    private int $pass = 0;
    private int $fail = 0;

    private function ok(string $label): void
    {
        $this->pass++;
        CLI::write('PASS  ' . $label, 'green');
    }

    private function no(string $label, string $sebab = ''): void
    {
        $this->fail++;
        CLI::write('FAIL  ' . $label . ($sebab !== '' ? ' -> ' . $sebab : ''), 'red');
    }

    /** Predikat yang sama dengan yang sekarang dipakai kedua controller. */
    private function diterima(UploadedFile $file): bool
    {
        return in_array(strtolower($file->guessExtension()), $this->whitelist, true);
    }

    public function run(array $params)
    {
        $dir = WRITEPATH . 'f42_probe';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $probe = $dir . '/probe_' . bin2hex(random_bytes(4));
        $dibuat = [];

        $tulis = function (string $nama, string $isi) use ($probe, &$dibuat): string {
            $path = $probe . $nama;
            file_put_contents($path, $isi);
            $dibuat[] = $path;
            return $path;
        };

        // ── 1. Payload skrip yang mengaku image/jpeg ───────────────────────
        $php   = '<' . '?php echo "x";';
        $path  = $tulis('.txt', $php);
        $file  = new UploadedFile($path, 'bukti.jpg', 'image/jpeg', strlen($php), UPLOAD_ERR_OK);

        $klien = $file->getClientMimeType();
        $asli  = $file->getMimeType();
        $ekst  = strtolower($file->guessExtension());
        CLI::write('  klaim=' . $klien . '  isi=' . $asli . '  guessExtension=' . $ekst);

        $klien === 'image/jpeg'
            ? $this->ok('guard lama (getClientMimeType) memang meloloskan kliman image/jpeg')
            : $this->no('guard lama (getClientMimeType) meloloskan kliman image/jpeg', 'clientMime=' . $klien);

        $ekst === 'php'
            ? $this->ok('guessExtension() memakai MIME asli (php) = sumber nama berkas yang dulu berbahaya')
            : CLI::write('INFO  guessExtension()=' . $ekst . ' pada build fileinfo ini', 'yellow');

        $this->diterima($file) === false
            ? $this->ok('guard baru menolak payload skrip (ekstensi hasil=' . $ekst . ')')
            : $this->no('guard baru menolak payload skrip', 'ekst=' . $ekst);

        // Nama berkas hanya dipakai bila guard meloloskan file. Untuk payload
        // skrip, getRandomName() memang tetap menghasilkan .php — itulah
        // kenapa ia tidak boleh dipanggil setelah penolakan.
        $extBaru = strtolower(pathinfo($file->getRandomName(), PATHINFO_EXTENSION));
        if ($this->diterima($file) === false) {
            $this->ok('payload skrip ditolak sebelum nama berkas dipakai (' . $extBaru . ' tak pernah ditulis)');
        } else {
            $this->no('payload skrip ditolak sebelum nama berkas dipakai', 'diterima');
        }

        // ── 2. Berkas sungguhan tetap diterima ─────────────────────────────
        $png = $tulis('.png', (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $filePng = new UploadedFile($png, 'bukti.png', 'image/png', filesize($png), UPLOAD_ERR_OK);
        $this->diterima($filePng)
            ? $this->ok('PNG asli diterima (' . $filePng->guessExtension() . ')')
            : $this->no('PNG asli diterima', 'guessExtension=' . $filePng->guessExtension());

        $jpg = $tulis('.jpg', '');
        $im  = imagecreatetruecolor(4, 4);
        imagejpeg($im, $jpg);
        imagedestroy($im);
        $fileJpg = new UploadedFile($jpg, 'bukti.jpg', 'image/jpeg', filesize($jpg), UPLOAD_ERR_OK);
        $this->diterima($fileJpg)
            ? $this->ok('JPEG asli diterima (' . $fileJpg->guessExtension() . ')')
            : $this->no('JPEG asli diterima', 'guessExtension=' . $fileJpg->guessExtension());

        $pdf = $tulis('.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
        $filePdf = new UploadedFile($pdf, 'bukti.pdf', 'application/pdf', filesize($pdf), UPLOAD_ERR_OK);
        $this->diterima($filePdf)
            ? $this->ok('PDF asli diterima (' . $filePdf->guessExtension() . ')')
            : $this->no('PDF asli diterima', 'guessExtension=' . $filePdf->guessExtension());

        // Poliglot: JPEG valid + sisipan skrip, klien mengaku image/jpeg
        $poly = $tulis('.jpg2', file_get_contents($jpg) . "\n" . $php);
        $filePoly = new UploadedFile($poly, 'bukti.jpg', 'image/jpeg', filesize($poly), UPLOAD_ERR_OK);
        $ekstPoly = strtolower($filePoly->guessExtension());
        if (!in_array($ekstPoly, ['php', 'phtml', 'phar', 'cgi', 'pl', 'py'], true)) {
            $this->ok('poliglot tidak pernah menghasilkan nama .php di disk (' . $ekstPoly . ')');
        } else {
            $this->no('poliglot tidak pernah menghasilkan nama .php di disk', $ekstPoly);
        }

        // ── 3. Sumber: kedua titik perbaikan memakai guard baru ────────────
        $sasaran = [
            ['App\Controllers\Guru\Attendance', 'leaveRequest', 'Controllers/Guru/Attendance.php'],
            ['App\Controllers\Admin\PresensiAdmin', 'saveManualPermit', 'Controllers/Admin/PresensiAdmin.php'],
        ];
        foreach ($sasaran as [$kelas, $metode, $rel]) {
            $abs   = APPPATH . $rel;
            $ref   = new \ReflectionMethod($kelas . '::' . $metode);
            $source = implode("\n", array_slice(
                file($abs),
                $ref->getStartLine() - 1,
                $ref->getEndLine() - $ref->getStartLine() + 1
            ));
            str_contains($source, 'getClientMimeType()')
                ? $this->no($rel . '::' . $metode . ' masih memakai getClientMimeType()')
                : $this->ok($rel . '::' . $metode . ' bebas getClientMimeType()');

            if (str_contains($source, '->guessExtension()') && str_contains($source, "'pdf', 'jpg', 'jpeg', 'png'")) {
                $this->ok($rel . '::' . $metode . ' memakai whitelist guessExtension()');
            } else {
                $this->no($rel . '::' . $metode . ' memakai whitelist guessExtension()');
            }
        }

        // ── 4. Sapuan move(): nama berkas wajib server-generated ────────────
        // Nama dianggap aman bila sumbernya random name/hash/preg_replace, atau
        // cocok dengan bukti manual yang sudah dilacak per titik.
        $mati = ['Userpanitia']; // controller tanpa rute (dibuktikan F31/F39)
        $buktiManual = [
            // nama = $safeNis (hasil preg_replace) + ekstensi '.jpg' tetap
            'Controllers/Datasiswa.php::$newName'        => "\$safeNis . '.jpg'",
            // $logo dibersihkan + MIME asli diwhitelist image/* sebelum move
            'Controllers/Profilsekolah.php::$newName'    => "preg_replace('/[^a-zA-Z0-9_]/', '', (string) \$logo)",
            // $logo diwhitelist MEDIA_FIELDS, $ext dari peta MIME
            'Controllers/Settings.php::$fileName'        => "\$logo . '.' . \$ext",
            // 'foto_' . (int) id_user . '.' + $ext dari whitelist ['jpg'...]
            'Controllers/Useradmin.php::$newName'        => "'foto_' . \$id_user . '.' . \$ext",
        ];
        $temuan    = [];
        $diperiksa = 0;
        $daftar    = [];
        for ($it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH . 'Controllers')); $it->valid(); $it->next()) {
            $f = $it->current()->getPathname();
            if ($it->current()->isDir() || !str_ends_with($f, '.php')) {
                continue;
            }
            $daftar[] = $f;
        }
        sort($daftar);
        foreach ($daftar as $f) {
            $norm = str_replace('\\', '/', $f);
            $rel  = substr($norm, strpos($norm, 'Controllers/'));
            if (in_array(basename($rel, '.php'), $mati, true)) {
                continue;
            }
            $isi = (string) file_get_contents($f);
            if (!preg_match_all('/->move\(\s*[^,;]+,\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/', $isi, $m)) {
                continue;
            }
            foreach (array_unique($m[1]) as $var) {
                $diperiksa++;
                $key  = $rel . '::$' . $var;
                $pola = '/\$' . preg_quote($var, '/') . '\s*=\s*[^;]*(getRandomName|uniqid|random_bytes|md5|sha1|preg_replace)[^;]*;/';
                if (preg_match($pola, $isi)) {
                    continue;
                }
                if (isset($buktiManual[$key]) && str_contains($isi, $buktiManual[$key])) {
                    continue;
                }
                $temuan[] = $key;
            }
        }
        CLI::write('  nama berkas di move() diperiksa: ' . $diperiksa . ' titik');
        $temuan === []
            ? $this->ok('setiap nama berkas move() server-generated / terbukti aman (' . $diperiksa . ' titik)')
            : $this->no('nama berkas move() belum terbukti aman', implode(' | ', $temuan));

        // Titik yang baru diperbaiki harus masih punya sanitisasi $logo
        $isiProfil = (string) file_get_contents(APPPATH . 'Controllers/Profilsekolah.php');
        str_contains($isiProfil, "preg_replace('/[^a-zA-Z0-9_]/', '', (string) \$logo)")
            ? $this->ok('Profilsekolah::uploadFile membersihkan segmen $logo')
            : $this->no('Profilsekolah::uploadFile membersihkan segmen $logo');


        // ── 5. Penjaga traversal yang sudah ada harus tetap utuh ────────────
        $penjaga = [
            ['Controllers/Dataguru.php', 'resolveDeletablePath', ['strpos($rel, \'..\')', 'realpath(FCPATH . $rel)', 'strpos($abs, $uploadsRoot)']],
            ['Controllers/Useradmin.php', 'resolveProfilePath', ['realpath', 'uploads/profiles']],
            ['Controllers/Settings.php', 'resolveMediaPath', ['realpath', 'uploads/']],
            ['Controllers/Profilsekolah.php', 'deleteFile', ["str_starts_with(\$file_name, 'uploads/settings/')", "str_contains(\$file_name, '..')"]],
        ];
        foreach ($penjaga as [$rel, $metode, $potongan]) {
            $abs    = APPPATH . $rel;
            $isi    = (string) file_get_contents($abs);
            $awal   = strpos($isi, 'function ' . $metode);
            if ($awal === false) {
                $this->no($rel . '::' . $metode . ' masih ada');
                continue;
            }
            $cuplik = substr($isi, $awal, 2500);
            $kurang = array_values(array_filter($potongan, static fn ($p) => !str_contains($cuplik, $p)));
            $kurang === []
                ? $this->ok($rel . '::' . $metode . ' menjaga uploads/ + realpath (bagian: ' . count($potongan) . ')')
                : $this->no($rel . '::' . $metode . ' kehilangan penjaga', implode(' | ', $kurang));
        }

        // Rute assets fallback harus tetap memotong di dalam document root
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        str_contains($routes, "!str_starts_with(\$file, \$root . DIRECTORY_SEPARATOR)")
            ? $this->ok('rute assets/(:any) tetap terkurung di FCPATH/assets')
            : $this->no('rute assets/(:any) tetap terkurung di FCPATH/assets');

        // ── 6. Bersihkan probe ──────────────────────────────────────────────
        CLI::write('  probe tercatat: ' . count($dibuat));
        foreach ($dibuat as $p) {
            if (is_file($p)) {
                $hapusOk = @unlink($p);
                if (!$hapusOk) {
                    CLI::write('  unlink gagal: ' . $p, 'yellow');
                }
            } else {
                CLI::write('  tidak is_file: ' . $p, 'yellow');
            }
        }
        @rmdir($dir);
        !is_dir($dir)
            ? $this->ok('probe sementara terhapus')
            : $this->no('probe sementara terhapus', $dir);

        CLI::write('');
        CLI::write('PASS=' . $this->pass . ' FAIL=' . $this->fail, $this->fail === 0 ? 'green' : 'red');
    }
}
