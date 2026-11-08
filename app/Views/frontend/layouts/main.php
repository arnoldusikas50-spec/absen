<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $title ?? 'Student Portal' ?> -
        <?= esc($setting->nama_sekolah ?? 'Aplikasi Asesmen') ?>
    </title>

    <!-- Favicon -->
    <?php
    $logo = ($setting->logo_kiri ?? null) ? base_url($setting->logo_kiri) : base_url('assets/img/favicon.png');
    ?>
    <link rel="icon" href="<?= $logo ?>" type="image/png">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/tailwind.css') ?>">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/bootstrap-icon/bootstrap-icons.css') ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Additional Styles -->
    <?= $this->renderSection('styles') ?>

    <script>
        const base_url = "<?= base_url() ?>";
    </script>
    <?= \App\Libraries\Theme::styleTag() ?>
</head>

<body class="bg-slate-50 text-slate-800 antialiased font-sans min-h-screen">
    <!-- Main Content -->
    <?= $this->renderSection('content') ?>

    <!-- jQuery -->
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>

    <!-- Custom Frontend JS (optional - uncomment when file exists) -->
    <!-- <script src="<?= base_url('assets/frontend/js/app.js') ?>"></script> -->

    <!-- Additional Scripts -->
    <?= $this->renderSection('scripts') ?>
</body>

</html>