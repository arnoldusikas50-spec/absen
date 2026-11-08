<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak -
        <?= esc($setting->nama_sekolah ?? 'absensi by ikasmedia') ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 20px;
        }

        .error-container {
            text-align: center;
            max-width: 450px;
        }

        .error-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        p {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1>Akses Ditolak</h1>
        <p>
            <?= esc($message ?? 'Akun Anda tidak memiliki akses ke aplikasi ini atau data profil tidak ditemukan. Silakan hubungi administrator untuk informasi lebih lanjut.') ?>
        </p>
        <a href="<?= site_url('auth/logout') ?>" class="btn">
            Kembali ke Login
        </a>
    </div>


</body>

</html>