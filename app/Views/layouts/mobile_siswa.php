<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="<?= \App\Libraries\Theme::metaColor('siswa') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Presensi Siswa">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="application-name" content="Presensi Siswa">
    <meta name="description" content="Aplikasi presensi siswa: status kehadiran, statistik bulanan, dan kartu QR digital.">

    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('icons/apple-touch-icon.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('icons/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('icons/icon-192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= base_url('icons/icon-512.png') ?>">
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
            padding-bottom: calc(var(--bottom-nav-offset, 108px) + var(--safe-area-inset-bottom, 0px));
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

        /* ── Bottom nav: floating pill ─────────────────────────── */
        .bottom-nav {
            position: fixed;
            bottom: calc(10px + var(--safe-area-inset-bottom, 0px) * 0.5);
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 24px);
            max-width: 456px;
            z-index: 2000;
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 68px;
            padding: 0 8px;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow:
                0 24px 48px -24px rgba(15, 23, 42, 0.35),
                0 2px 10px rgba(15, 23, 42, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            position: relative;
            flex: 1;
            padding: 4px 0;
            transition: color 0.22s ease;
            color: #94a3b8;
            -webkit-tap-highlight-color: transparent;
        }
        /* Chip ikon — wadah pill yang membesar saat aktif */
        .nav-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 32px;
            border-radius: 16px;
            background: transparent;
            transition: background 0.25s ease, box-shadow 0.25s ease, transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        /* Ikon SVG nav: garis tipis (outline) → terisi duotone saat aktif */
        .nav-ico {
            display: block;
            width: 23px;
            height: 23px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.75;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: stroke-width .25s ease;
        }
        .nav-ico .duo {
            fill: currentColor;
            fill-opacity: 0;
            transition: fill-opacity .25s ease;
        }
        .nav-item.active .nav-ico { stroke-width: 1.95; }
        .nav-item.active .nav-ico .duo { fill-opacity: .18; }
        .nav-center-btn .nav-ico { width: 28px; height: 28px; stroke-width: 1.85; }
        .nav-center-btn .nav-ico .duo { fill: #fff; fill-opacity: .28; }
        .nav-label { font-weight: 700; margin-top: 3px; font-size: 10px; letter-spacing: 0.2px; color: #64748b; transition: color 0.22s ease; }
        .nav-item.active { color: var(--accent-600); }
        .nav-item.active .nav-label { color: var(--accent-600); }
        .nav-item.active .nav-icon-wrap {
            background: linear-gradient(135deg, rgb(var(--accent-600-rgb) / 0.15) 0%, rgb(var(--accent-500-rgb) / 0.10) 55%, rgb(var(--indigo-600-rgb, 79, 70, 229) / 0.18) 100%);
            box-shadow: inset 0 0 0 1px rgb(var(--accent-600-rgb) / 0.14), 0 10px 18px -12px rgb(var(--accent-600-rgb) / 0.6);
        }
        .nav-item:active .nav-icon-wrap { transform: scale(0.9); }

        @media (hover: hover) {
            .nav-item:hover { color: #64748b; }
            .nav-item:hover .nav-icon-wrap { background: rgba(15, 23, 42, 0.05); }
            .nav-item.active:hover { color: var(--accent-600); }
            .nav-item.active:hover .nav-icon-wrap { background: linear-gradient(135deg, rgb(var(--accent-600-rgb) / 0.20) 0%, rgb(var(--accent-500-rgb) / 0.14) 55%, rgb(var(--indigo-600-rgb, 79, 70, 229) / 0.22) 100%); }
        }

        .nav-center-btn {
            position: absolute;
            top: -24px;
            width: 56px; height: 56px;
            border-radius: 20px;
            background: linear-gradient(140deg, var(--indigo-600, #4f46e5) 0%, var(--accent-600) 55%, var(--accent-500) 100%);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 14px 28px -10px rgb(var(--accent-600-rgb) / 0.55), 0 3px 8px rgb(var(--accent-600-rgb) / 0.22);
            border: 3px solid #fff;
            overflow: hidden;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
        }
        /* Kilap lembut di tepi atas tombol QR */
        .nav-center-btn::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0) 55%);
            pointer-events: none;
        }
        .nav-item:active .nav-center-btn { transform: scale(0.92) translateY(2px); box-shadow: 0 6px 14px -6px rgb(var(--accent-600-rgb) / 0.45); }
        .nav-item-center { position: relative; }
        .nav-label-center { margin-top: 38px; color: var(--accent-700); }

        @media (prefers-reduced-motion: reduce) {
            .nav-icon-wrap, .nav-center-btn, .nav-item { transition: none; }
            .nav-ico, .nav-ico .duo { transition: none; }
            .nav-item:active .nav-icon-wrap, .nav-item:active .nav-center-btn { transform: none; }
        }

        /* ── Pelengkap utility ───────────────────────────────── */
        .shadow-2xs      { box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); }
        .shadow-xs       { box-shadow: 0 1px 3px rgba(15, 23, 42, 0.07), 0 1px 2px rgba(15, 23, 42, 0.04); }
        .drop-shadow-xs  { filter: drop-shadow(0 1px 2px rgba(15, 23, 42, 0.08)); }
        .active\:scale-98:active     { transform: scale(0.98); }
        .focus\:outline-hidden:focus { outline: 2px solid transparent; outline-offset: 2px; }
        .animate-spin-slow           { animation: spin 2.4s linear infinite; }
        .flex-2            { flex: 2 1 0%; }
        .border-3          { border-width: 3px; }

        /* Utility spasi logis Bootstrap yang dipakai view mobile. */
        .me-0 { margin-right: 0; } .me-1 { margin-right: .25rem; } .me-2 { margin-right: .5rem; } .me-3 { margin-right: 1rem; }
        .ms-1 { margin-left: .25rem; } .ms-2 { margin-left: .5rem; } .ms-3 { margin-left: 1rem; } .ms-auto { margin-left: auto; }
        .pe-2 { padding-right: .5rem; } .ps-1 { padding-left: .25rem; }

        @media (hover: hover) {
            .hover\:bg-amber-50\/60:hover   { background-color: rgba(255, 251, 235, 0.6); }
            .hover\:bg-rose-50\/60:hover    { background-color: rgba(255, 241, 242, 0.6); }
            .hover\:bg-purple-50\/60:hover  { background-color: rgba(250, 245, 255, 0.6); }
            .hover\:border-indigo-300:hover { border-color: #a5b4fc; }
            .hover\:border-teal-300:hover   { border-color: #5eead4; }
        }

        /* iOS Safari auto-zoom saat input fokus */
        .app-container input:not([type="hidden"]),
        .app-container select,
        .app-container textarea { font-size: 16px; }

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
        .badge-primary-subtle { background: rgb(var(--accent-600-rgb) / 0.08); }

        /* ── Form ──────────────────────────────────────────────── */
        .m3-input {
            width: 100%; color: #1e293b; background: #fff; outline: none;
            border: 1.5px solid #e2e8f0; border-radius: 0.75rem;
            padding: 0.55rem 0.85rem; font-size: 0.8125rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
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
            position: fixed; inset: 0; z-index: 3500;
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
        .empty-state > i { font-size: 2rem; display: block; margin: 0 auto 8px; }
        .empty-state h6  { font-size: 15px; font-weight: 700; color: #475569; margin: 0 0 2px; }

        /* SweetAlert di atas bottom-nav (z 2000) */
        .swal2-container { z-index: 4000 !important; }

        /* ── SweetAlert2: brand theme (semua popup /siswa) ── */
        .swal2-popup {
            border-radius: 26px !important;
            padding: 1.5rem 1.25rem 1.25rem !important;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif !important;
            box-shadow: 0 24px 64px rgba(15,23,42,.22) !important;
        }
        .swal2-title {
            font-weight: 800 !important; color: #1e293b !important;
            font-size: 1.15rem !important; letter-spacing: -.3px !important;
            padding-top: .25em !important;
        }
        .swal2-html-container { color: #64748b !important; font-size: .85rem !important; line-height: 1.6 !important; }
        .swal2-icon { border-width: 2.5px !important; margin: .6em auto 1em !important; transform: scale(.92); }
        .swal2-icon.swal2-warning { border-color: #fed7aa !important; color: #f59e0b !important; }
        .swal2-icon.swal2-success { border-color: #a7f3d0 !important; color: #10b981 !important; }
        .swal2-icon.swal2-success [class^=swal2-success-line] { background-color: #10b981 !important; }
        .swal2-icon.swal2-success .swal2-success-ring { border-color: #a7f3d0 !important; }
        .swal2-icon.swal2-error { border-color: #fecaca !important; color: #ef4444 !important; }
        .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background-color: #ef4444 !important; }
        .swal2-icon.swal2-info, .swal2-icon.swal2-question { border-color: var(--accent-200, #c7d2fe) !important; color: var(--accent-600) !important; }
        .swal2-actions { gap: 8px !important; width: 100% !important; margin-top: 1.1em !important; }
        .swal2-styled.swal2-confirm {
            background: var(--accent-600) !important; border-radius: 9999px !important;
            font-weight: 800 !important; padding: .65em 1.7em !important;
            box-shadow: 0 6px 16px rgb(var(--accent-600-rgb) / .32) !important;
        }
        .swal2-styled.swal2-cancel {
            background: #f1f5f9 !important; color: #64748b !important; border-radius: 9999px !important;
            font-weight: 700 !important; padding: .65em 1.7em !important;
        }
        .swal2-styled:focus { box-shadow: 0 0 0 3px rgb(var(--accent-600-rgb) / .18) !important; }
        .swal2-input, .swal2-textarea {
            border-radius: 14px !important; border: 1.5px solid #e2e8f0 !important;
            font-size: .85rem !important; color: #1e293b !important;
        }
        .swal2-input:focus, .swal2-textarea:focus {
            border-color: var(--accent-600) !important; box-shadow: 0 0 0 3px rgb(var(--accent-600-rgb) / .12) !important;
        }
        .swal2-validation-message { border-radius: 12px !important; font-size: .78rem !important; }
        .swal2-timer-progress-bar { background: rgb(var(--accent-600-rgb) / .35) !important; }

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
            --m3-primary: var(--accent-600);
            --m3-on-primary: #FFFFFF;
            --m3-primary-container: var(--accent-100);
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
    <div class="app-container" style="--bottom-nav-offset: <?= !empty($hideBottomNav) ? '0px' : '108px' ?>;">
        <?= $this->renderSection('content') ?>

        <?php if (empty($hideBottomNav)): ?>
        <?php
            $navUri   = uri_string();
            $navHome  = ($navUri === 'siswa/presensi');
            $navRiwayat = strpos($navUri, 'riwayat') !== false;
        ?>
        <nav class="bottom-nav" aria-label="Navigasi utama siswa">
            <!-- Beranda -->
            <a href="<?= site_url('siswa/presensi') ?>" class="nav-item <?= $navHome ? 'active' : '' ?>" <?= $navHome ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon-wrap">
                    <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                        <path class="duo" d="M3.6 10.4 12 3.6l8.4 6.8V19a2.4 2.4 0 0 1-2.4 2.4H6A2.4 2.4 0 0 1 3.6 19z"/>
                        <path d="M9.6 21.4v-5.3a1 1 0 0 1 1-1h2.8a1 1 0 0 1 1 1v5.3"/>
                    </svg>
                </span>
                <span class="nav-label">Beranda</span>
            </a>

            <!-- Riwayat -->
            <a href="<?= site_url('siswa/presensi') ?>#riwayat" class="nav-item">
                <span class="nav-icon-wrap">
                    <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="duo" cx="12" cy="12" r="8.6"/>
                        <path d="M12 7.3v4.9l3.4 2"/>
                    </svg>
                </span>
                <span class="nav-label">Riwayat</span>
            </a>

            <!-- Kartu QR — tengah melayang -->
            <a href="javascript:void(0)" onclick="typeof openStudentQrModal === 'function' ? openStudentQrModal() : (location.href='<?= site_url('siswa/presensi') ?>#qr')" class="nav-item nav-item-center" id="nav-btn-student-qr" aria-label="Buka kartu QR presensi">
                <span class="nav-center-btn">
                    <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                        <rect class="duo" x="3" y="3" width="7" height="7" rx="1"/>
                        <rect class="duo" x="14" y="3" width="7" height="7" rx="1"/>
                        <rect class="duo" x="3" y="14" width="7" height="7" rx="1"/>
                        <path d="M14 14h2v2h-2zM18 14h3v3h-3zM14 18v3M18 20h3"/>
                    </svg>
                </span>
                <span class="nav-label nav-label-center">Kartu QR</span>
            </a>

            <!-- Keluar -->
            <a href="<?= site_url('auth/logout') ?>" class="nav-item" onclick="return confirm('Yakin ingin keluar?')">
                <span class="nav-icon-wrap">
                    <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                        <path class="duo" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </span>
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
        // Common JS for Mobile Siswa
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
