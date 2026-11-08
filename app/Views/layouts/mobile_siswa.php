<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="<?= \App\Libraries\Theme::metaColor('siswa') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
    <title><?= $title ?? 'Presensi' ?> - Siswa</title>

    <!-- Google Fonts: Plus Jakarta Sans + Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Ikon SVG Lucide (mask, tanpa icon font) -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/lucide-icons.css') ?>?v=<?= @filemtime(FCPATH . 'assets/app/css/lucide-icons.css') ?>">
    <!-- Tailwind CSS (build lokal) -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/tailwind.css') ?>?v=<?= @filemtime(FCPATH . 'assets/app/css/tailwind.css') ?>">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.css') ?>">

    <style>
        /* Utility spasi logis Bootstrap yang dipakai view mobile. */
        .me-0 { margin-right: 0; } .me-1 { margin-right: .25rem; } .me-2 { margin-right: .5rem; } .me-3 { margin-right: 1rem; }
        .ms-1 { margin-left: .25rem; } .ms-2 { margin-left: .5rem; } .ms-3 { margin-left: 1rem; } .ms-auto { margin-left: auto; }
        .pe-2 { padding-right: .5rem; } .ps-1 { padding-left: .25rem; }

        /* ── App shell ─────────────────────────────────────────── */
        .app-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            background-color: #f8fafc;
            box-shadow: 0 0 40px rgba(0,0,0,0.04);
            padding-bottom: calc(var(--bottom-nav-offset, 80px) + var(--safe-area-inset-bottom, 0px));
        }
        .top-app-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #ffffff;
            transition: box-shadow 0.2s;
            padding: calc(16px + var(--safe-area-inset-top, 0px)) 16px 16px;
        }
        .top-app-bar.scrolled { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }

        /* ── Bottom nav ────────────────────────────────────────── */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            z-index: 2000;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 10px;
            height: calc(66px + var(--safe-area-inset-bottom, 0px));
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
            border-top: 1px solid rgba(226, 232, 240, 0.8);
            padding-bottom: var(--safe-area-inset-bottom, 0px);
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            position: relative;
            flex: 1;
            padding-top: 6px;
            transition: all 0.25s ease;
            color: #64748b;
        }
        .nav-item i {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.35rem;
            width: 44px;
            height: 30px;
            transition: all 0.25s ease;
        }
        .nav-item.active { color: var(--accent-600); }
        .nav-item.active i { background: var(--accent-100); color: var(--accent-600); font-weight: 800; }
        .nav-label { font-weight: 700; margin-top: 3px; font-size: 10px; letter-spacing: 0.2px; }

        .nav-item-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            position: relative;
            flex: 1;
            margin-top: -18px;
        }
        .nav-center-bubble {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--indigo-600) 0%, var(--accent-600) 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 8px 18px rgb(var(--accent-600-rgb) / 0.35);
            border: 3px solid #ffffff;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .nav-center-bubble:active { transform: scale(0.92); box-shadow: 0 4px 10px rgb(var(--accent-600-rgb) / 0.25); }
        .nav-label-center { font-weight: 800; margin-top: 3px; font-size: 10px; letter-spacing: 0.2px; color: var(--accent-700); }

        /* ── Cards ─────────────────────────────────────────────── */
        .m3-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.15rem;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .m3-card-elevated {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
        }

        /* ── Buttons ───────────────────────────────────────────── */
        .btn-tw {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            font-weight: 700; border-radius: 9999px;
            transition: all 0.15s; cursor: pointer;
            text-decoration: none; user-select: none;
            padding: 0.55rem 1.1rem; font-size: 0.8125rem;
            border: 1.5px solid transparent; letter-spacing: 0.01em;
        }
        .btn-tw:active { transform: scale(0.97); }
        .btn-tw:disabled { opacity: 0.6; cursor: not-allowed; pointer-events: none; }
        .btn-tw-sm { padding: 0.35rem 0.8rem; font-size: 0.72rem; }
        .btn-tw-lg { padding: 0.7rem 1.4rem; font-size: 0.9rem; }
        .btn-primary   { color: #fff; background: var(--accent-600); border-color: var(--accent-600); box-shadow: 0 4px 10px rgb(var(--accent-600-rgb) / 0.25); }
        .btn-primary:hover { background: var(--accent-700); border-color: var(--accent-700); }
        .btn-success   { color: #fff; background: #16a34a; border-color: #16a34a; box-shadow: 0 4px 6px -1px rgba(22,163,74,.3); }
        .btn-danger    { color: #fff; background: #dc2626; border-color: #dc2626; box-shadow: 0 4px 6px -1px rgba(220,38,38,.3); }
        .btn-warning   { color: #fff; background: #d97706; border-color: #d97706; }
        .btn-info      { color: #fff; background: #0891b2; border-color: #0891b2; }
        .btn-secondary { color: #fff; background: #475569; border-color: #475569; }
        .btn-light     { color: #334155; background: #f8fafc; border-color: #e2e8f0; }
        .btn-brand     { color: #fff; background: var(--accent-600); border-color: var(--accent-600); box-shadow: 0 4px 6px -1px rgb(var(--accent-600-rgb) / .3); }
        .btn-outline-primary   { color: var(--primary-600); background: transparent; border-color: var(--primary-200); }
        .btn-outline-success   { color: #16a34a; background: transparent; border-color: #bbf7d0; }
        .btn-outline-danger    { color: #dc2626; background: transparent; border-color: #fecaca; }
        .btn-outline-secondary { color: #475569; background: transparent; border-color: #e2e8f0; }

        /* ── Badges ────────────────────────────────────────────── */
        .badge-tw {
            display: inline-flex; align-items: center; gap: 4px;
            font-weight: 700; border-radius: 9999px; white-space: nowrap;
            padding: 0.2rem 0.6rem; font-size: 0.68rem; letter-spacing: 0.015em;
        }
        .badge-success   { background: #dcfce7; color: #15803d; }
        .badge-danger    { background: #fee2e2; color: #b91c1c; }
        .badge-warning   { background: #fef3c7; color: #b45309; }
        .badge-info      { background: #e0f2fe; color: #0369a1; }
        .badge-primary   { background: var(--primary-100); color: var(--primary-700); }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .badge-purple    { background: #f3e8ff; color: #7e22ce; }

        /* ── Form ──────────────────────────────────────────────── */
        .m3-input {
            width: 100%; color: #1e293b; background: #fff; outline: none;
            border: 1.5px solid #e2e8f0; border-radius: 0.75rem;
            padding: 0.55rem 0.85rem; font-size: 0.8125rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        /* Ruang ikon di dalam input: shorthand padding .m3-input (unlayered)
           mengalahkan .pl-*/.pr-* Tailwind (layered) — kunci via spesifisitas. */
        .m3-input.pl-8  { padding-left: 2rem; }
        .m3-input.pl-9  { padding-left: 2.5rem; }
        .m3-input.pl-10 { padding-left: 2.75rem; }
        .m3-input.pr-9  { padding-right: 2.5rem; }
        .m3-input.pr-10 { padding-right: 2.75rem; }
        .m3-input:focus { border-color: var(--accent-600); box-shadow: 0 0 0 3px rgb(var(--accent-600-rgb) / 0.12); }
        select.m3-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.85rem center;
            background-color: #fff;
            padding-right: 2.25rem;
        }
        .m3-check {
            cursor: pointer; flex-shrink: 0;
            width: 1.15rem; height: 1.15rem; accent-color: var(--accent-600);
        }
        .form-label-tw {
            display: block; font-weight: 600; color: #334155;
            margin-bottom: 6px; font-size: 0.78rem;
        }

        /* ── Modal bottom-sheet ────────────────────────────────── */
        .tw-modal {
            position: fixed; inset: 0; z-index: 1050;
            display: none; align-items: flex-end; justify-content: center;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(3px);
        }
        .tw-modal.open { display: flex; }
        .tw-modal-box {
            background: #fff; width: 100%;
            border-radius: 28px 28px 0 0;
            max-height: 92vh; overflow-y: auto;
            animation: sheetUp 0.25s ease-out;
        }
        @media (min-width: 640px) {
            .tw-modal { align-items: center; padding: 1rem; }
            .tw-modal-box { border-radius: 1.25rem; max-width: 32rem; }
        }
        @keyframes sheetUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }

        /* ── Misc ──────────────────────────────────────────────── */
        .skeleton {
            background: #E7E0EB; overflow: hidden;
            position: relative; border-radius: 8px;
        }
        .skeleton::after {
            content: ""; position: absolute; inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer { 100% { transform: translateX(100%); } }

        .ptr-indicator {
            height: 0; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            color: var(--accent-600); font-size: 1.25rem;
        }
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .pulse-dot {
            position: relative; display: inline-flex;
            border-radius: 9999px; width: 8px; height: 8px;
        }
        .pulse-dot::after {
            content: ''; position: absolute;
            width: 100%; height: 100%;
            border-radius: 9999px; opacity: 0.75;
            background: currentColor;
            animation: pingSoft 1.4s cubic-bezier(0,0,0.2,1) infinite;
        }
        @keyframes pingSoft { 75%, 100% { transform: scale(2.4); opacity: 0; } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { scrollbar-width: none; }
        .hidden { display: none !important; }
    </style>
    <style>
        :root {
            --m3-primary: var(--brand-600);
            --m3-on-primary: #FFFFFF;
            --m3-primary-container: var(--brand-100);
            --m3-on-primary-container: #880022;
            --m3-secondary: #625B71;
            --m3-on-secondary: #FFFFFF;
            --m3-secondary-container: #E8DEF8;
            --m3-on-secondary-container: #1D192B;
            --m3-surface: #FEF7FF;
            --m3-on-surface: #1D1B20;
            --m3-surface-variant: #E7E0EB;
            --m3-on-surface-variant: #49454F;
            --m3-outline: #79747E;
            --m3-background: #FEF7FF;
            --safe-area-inset-top: env(safe-area-inset-top, 0px);
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0px);
        }
        html { -webkit-tap-highlight-color: transparent; }
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            background-color: var(--m3-background);
            color: var(--m3-on-surface);
        }
    </style>
    <?= $this->renderSection('styles') ?>
    <?= \App\Libraries\Theme::styleTag() ?>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased selection:bg-violet-500 selection:text-white">
    <div class="app-container" style="--bottom-nav-offset: <?= !empty($hideBottomNav) ? '0px' : '80px' ?>;">
        <?= $this->renderSection('content') ?>

        <?php if (empty($hideBottomNav)): ?>
        <nav class="bottom-nav">
            <a href="<?= site_url('siswa/presensi') ?>" class="nav-item <?= uri_string() == 'siswa/presensi' ? 'active' : '' ?>">
                <i class="bi bi-house"></i>
                <span class="nav-label">Beranda</span>
            </a>

            <a href="javascript:void(0)" onclick="typeof openStudentQrModal === 'function' ? openStudentQrModal() : (location.href='<?= site_url('siswa/presensi') ?>#qr')" class="nav-item-center" id="nav-btn-student-qr">
                <div class="nav-center-bubble">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <span class="nav-label-center">Kartu QR</span>
            </a>

            <a href="<?= site_url('siswa/presensi') ?>#riwayat" class="nav-item">
                <i class="bi bi-clock-history"></i>
                <span class="nav-label">Riwayat</span>
            </a>

            <a href="<?= site_url('auth/logout') ?>" class="nav-item">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-label">Keluar</span>
            </a>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.all.min.js') ?>"></script>

    <script>
        (function() {
            function showFlashAlerts() {
                if (window.__mobileSiswaFlashShown) return;
                window.__mobileSiswaFlashShown = true;
                <?php
                $flashError   = session()->getFlashdata('error');
                $flashWarning = session()->getFlashdata('warning');
                $flashSuccess = session()->getFlashdata('success');
                $flashMessage = session()->getFlashdata('message');
                ?>
                <?php if (!empty($flashError)): ?>
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Akses Ditolak',
                            text: '<?= addslashes($flashError) ?>',
                            confirmButtonColor: '#6366f1',
                            confirmButtonText: 'Mengerti'
                        });
                    } else {
                        alert('<?= addslashes($flashError) ?>');
                    }
                <?php elseif (!empty($flashWarning)): ?>
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan',
                            text: '<?= addslashes($flashWarning) ?>',
                            confirmButtonColor: '#f59e0b',
                            confirmButtonText: 'Mengerti'
                        });
                    } else {
                        alert('<?= addslashes($flashWarning) ?>');
                    }
                <?php elseif (!empty($flashSuccess)): ?>
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: '<?= addslashes($flashSuccess) ?>',
                            confirmButtonColor: '#10b981',
                            confirmButtonText: 'OK',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                <?php elseif (!empty($flashMessage)): ?>
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Informasi',
                            text: '<?= addslashes($flashMessage) ?>',
                            confirmButtonColor: '#6366f1',
                            confirmButtonText: 'OK'
                        });
                    }
                <?php endif; ?>
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showFlashAlerts);
            } else {
                showFlashAlerts();
            }
        })();
    </script>

    <script>
        // Common JS for Mobile App (tanpa Bootstrap)
        $(window).scroll(function() {
            if ($(this).scrollTop() > 10) {
                $('.top-app-bar').addClass('scrolled');
            } else {
                $('.top-app-bar').removeClass('scrolled');
            }
        });

        function haptic() {
            if (window.navigator.vibrate) {
                window.navigator.vibrate(10);
            }
        }
        $(document).on('click', 'button, a', haptic);

        // Pengganti bootstrap.Modal untuk bottom-sheet .tw-modal
        function twModalShow(sel) {
            var m = typeof sel === 'string' ? document.querySelector(sel) : sel;
            if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
        }
        function twModalHide(sel) {
            var m = typeof sel === 'string' ? document.querySelector(sel) : (sel instanceof jQuery ? sel.get(0) : sel);
            if (m) {
                m.classList.remove('open');
                if (!document.querySelector('.tw-modal.open')) document.body.style.overflow = '';
            }
        }
        document.addEventListener('click', function (e) {
            var closer = e.target.closest('[data-tw-dismiss="modal"]');
            if (closer) {
                var modal = closer.closest('.tw-modal');
                if (modal) twModalHide(modal);
                return;
            }
            if (e.target.classList && e.target.classList.contains('tw-modal')) twModalHide(e.target);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.querySelectorAll('.tw-modal.open').forEach(twModalHide);
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
