<?php
/**
 * Halaman kunci perangkat kiosk.
 *
 * Sengaja TIDAK memakai layouts/main: halaman ini bisa diminta siapa saja,
 * dan tidak boleh ikut membawa data sekolah apa pun (daftar kelas, statistik,
 * nama sekolah) sebelum token terbukti benar.
 */
$pesan    = (string) ($pesan ?? '');
$action   = (string) ($action ?? site_url('scanner'));
$judul    = (string) ($judul ?? 'Scanner QR Terkunci');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($judul) ?></title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f1f5f9; color: #0f172a; padding: 24px;
        }
        .card {
            width: 100%; max-width: 430px; background: #fff; border: 1px solid #e2e8f0;
            border-radius: 18px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, .07);
        }
        .badge {
            display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700;
            color: #b45309; background: #fffbeb; border: 1px solid #fde68a;
            padding: 6px 12px; border-radius: 999px; margin-bottom: 16px;
        }
        h1 { font-size: 20px; margin: 0 0 8px; letter-spacing: -.01em; }
        p { font-size: 13px; line-height: 1.6; color: #475569; margin: 0 0 18px; }
        label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }
        input[type=password] {
            width: 100%; padding: 12px 14px; font-size: 15px; font-family: ui-monospace, monospace;
            border: 1px solid #cbd5e1; border-radius: 12px; background: #f8fafc;
        }
        input[type=password]:focus { outline: 2px solid #2563eb; outline-offset: 1px; background: #fff; }
        button {
            margin-top: 14px; width: 100%; padding: 12px 16px; font-size: 14px; font-weight: 700;
            color: #fff; background: #2563eb; border: 0; border-radius: 12px; cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .error {
            font-size: 13px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 12px; padding: 10px 12px; margin: 0 0 16px;
        }
        .hint { font-size: 11px; color: #94a3b8; margin: 14px 0 0; }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">&#128274; Perangkat belum didaftarkan</span>
        <h1><?= esc($judul) ?></h1>
        <p>Scanner QR sekolah ini dikunci dengan token kiosk. Masukkan token dari
            admin (<b>Pengaturan &rarr; Presensi &rarr; Token Kiosk</b>) satu kali untuk
            perangkat ini; setelah tersimpan, kiosk langsung terbuka seperti biasa.</p>

        <?php if ($pesan !== ''): ?>
            <div class="error"><?= esc($pesan) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= esc($action) ?>">
            <?= csrf_field() ?>
            <label for="k">Token kiosk</label>
            <input type="password" id="k" name="k" autocomplete="off" spellcheck="false" maxlength="64" required>
            <button type="submit">Daftarkan perangkat ini</button>
        </form>

        <p class="hint">Alternatif: buka <code><?= esc($action) ?>?k=TOKEN</code>.
            Halaman akan membuang token dari address bar setelah perangkat terdaftar.</p>
    </main>
</body>
</html>
