<?php
/**
 * Cangkang bersama halaman error HTML.
 *
 * Semua styling ditulis inline dan tanpa aset pihak ketiga: halaman ini justru
 * muncul saat aplikasi sedang rusak (CDN mati, server sekolah tanpa internet,
 * asset belum ter-deploy), jadi tidak boleh bergantung pada apa pun di luar
 * satu file respons ini.
 *
 * Dipakai: error_400, error_403, error_404, error_500, production.
 *
 * @var int         $kode
 * @var string      $judul
 * @var string      $pesanUmum
 * @var string|null $pesan        detail internal; hanya tampil di luar produksi
 * @var string      $ref          kode referensi ErrorResponder
 * @var bool        $muatUlang
 */

$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$judul        = !empty($judul) ? $judul : 'Terjadi Kesalahan';
$pesanUmum    = !empty($pesanUmum) ? $pesanUmum : 'Permintaan belum bisa diproses. Silakan coba lagi.';
$tampilDetail = ENVIRONMENT !== 'production' && !empty($pesan) ? (string) $pesan : '';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $e($kode) ?> - <?= $e($judul) ?></title>
    <style>
        :root {
            --inci: #4f46e5;
            --ungu: #7c3aed;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: radial-gradient(circle at 50% 0, #eef2ff, #f8fafc 45%);
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        .card {
            background: #fff;
            border-radius: 28px;
            padding: 48px 40px;
            box-shadow: 0 35px 80px rgba(15, 23, 42, .2);
            max-width: 560px;
            width: 100%;
            text-align: center;
        }

        .status {
            font-size: 88px;
            line-height: 1;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(120deg, var(--inci), var(--ungu));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h1 {
            margin: 12px 0 16px;
            font-size: 30px;
        }

        p {
            margin: 0 0 24px;
            color: #475569;
            line-height: 1.6;
        }

        .detail {
            display: block;
            margin: -12px 0 24px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 12.5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            text-align: left;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .ref {
            display: inline-block;
            margin: 0 0 24px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 12.5px;
            font-weight: 600;
        }

        .ref b {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            letter-spacing: .06em;
            color: #0f172a;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 26px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14.5px;
        }

        .primary {
            background: var(--inci);
            color: #fff;
        }

        .secondary {
            border: 1px solid #cbd5f5;
            color: var(--inci);
            background: #fff;
        }

        small {
            display: block;
            margin-top: 32px;
            color: #94a3b8;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .card {
                padding: 36px 22px;
                border-radius: 22px;
            }

            .status {
                font-size: 68px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>
    <main class="card" role="alert">
        <p class="status"><?= $e($kode) ?></p>
        <h1><?= $e($judul) ?></h1>
        <p><?= $e($pesanUmum) ?></p>
        <?php if ($tampilDetail !== ''): ?>
            <code class="detail"><?= $e($tampilDetail) ?></code>
        <?php endif ?>
        <?php if (!empty($ref)): ?>
            <p class="ref">Kode referensi: <b><?= $e($ref) ?></b></p>
        <?php endif ?>
        <div class="actions">
            <?php if (!empty($muatUlang)): ?>
                <a href="javascript:location.reload()" class="primary">Muat Ulang</a>
            <?php endif ?>
            <a href="/" class="<?= empty($muatUlang) ? 'primary' : 'secondary' ?>">Halaman Awal</a>
            <a href="javascript:history.back()" class="secondary">Kembali</a>
        </div>
        <small>-- IKASMEDIA --</small>
    </main>
</body>

</html>
