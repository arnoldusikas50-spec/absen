<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= esc($title ?? 'Scanner QR Presensi Siswa') ?></title>

    <?php
    $setting = $setting ?? new \stdClass();
    $setting->logo_kiri = $setting->logo_kiri ?? null;
    $logo_app = ($setting->logo_kiri == null) ? base_url('assets/img/favicon.png') : base_url('' . $setting->logo_kiri);
    ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/bootstrap-icon/bootstrap-icons.css') ?>">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/tailwind.css') ?>?v=<?= @filemtime(FCPATH . 'assets/app/css/tailwind.css') ?>">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.css') ?>">

    <!-- jQuery -->
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/wita-clock.js?v=' . (@filemtime(FCPATH . 'assets/js/wita-clock.js') ?: time())) ?>"></script>

    <script>
        // Perangkat baru mendaftar lewat /scanner?k=TOKEN. Server sudah memasang
        // cookie gerbangnya, jadi token tidak diperlukan lagi di URL — buang dari
        // address bar agar tidak tertinggal di riwayat browser kiosk.
        (function () {
            try {
                var u = new URL(window.location.href);
                if (!u.searchParams.has('k')) return;
                u.searchParams.delete('k');
                window.history.replaceState({}, document.title, u.pathname + u.search + u.hash);
            } catch (e) {
            }
        })();
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #090e17;
            color: #f1f5f9;
        }

        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        /* Ambient Glow & Background Grid */
        .ambient-glow {
            position: fixed;
            inset: 0;
            background-image: 
                radial-gradient(circle at 10% 15%, rgb(var(--brand-600-rgb) / 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 85%, rgba(14, 165, 233, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.04) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        ::-webkit-scrollbar-thumb { background: rgba(51, 65, 85, 0.8); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(71, 85, 105, 1); }

        /* Card Modern Dark */
        .kiosk-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.4);
        }

        /* Mode Switcher Tabs */
        .mode-btn-tab {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 20px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 14px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1.5px solid transparent;
            background: rgba(30, 41, 59, 0.6);
            color: #94a3b8;
            cursor: pointer;
        }
        .mode-btn-tab:hover {
            background: rgba(51, 65, 85, 0.8);
            color: #ffffff;
        }
        .mode-btn-tab.active-masuk {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff;
            border-color: #34d399;
            box-shadow: 0 8px 24px -4px rgba(16, 185, 129, 0.5);
        }
        .mode-btn-tab.active-keluar {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: #ffffff;
            border-color: #fbbf24;
            box-shadow: 0 8px 24px -4px rgba(245, 158, 11, 0.5);
        }
        .mode-btn-tab:disabled {
            opacity: .4; cursor: not-allowed;
        }
        .mode-btn-tab:disabled:hover {
            background: rgba(30, 41, 59, 0.6); color: #94a3b8;
        }
        .mode-schedule-hint {
            font-size: 11px; color: #64748b; text-align: center;
            margin: -8px 0 12px; font-weight: 600;
        }

        /* Camera Frame Viewport */
        .camera-viewport-frame {
            position: relative;
            background: #020617;
            border-radius: 20px;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 16px 36px -8px rgba(0, 0, 0, 0.6), inset 0 0 40px rgba(0, 0, 0, 0.8);
            min-height: 380px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        #qr-reader-desktop video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 18px;
        }
        #qr-reader-desktop {
            border: none !important;
            width: 100% !important;
        }
        #qr-reader-desktop img[alt="Info icon"] { display: none !important; }
        #qr-reader-desktop div[style*="text-align: center"] { display: none !important; }

        /* Sci-Fi Corners */
        .scanner-corner {
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: #10b981;
            border-style: solid;
            z-index: 15;
            pointer-events: none;
            transition: border-color 0.3s ease;
        }
        .scanner-corner-tl { top: 16px; left: 16px; border-width: 3px 0 0 3px; border-top-left-radius: 8px; }
        .scanner-corner-tr { top: 16px; right: 16px; border-width: 3px 3px 0 0; border-top-right-radius: 8px; }
        .scanner-corner-bl { bottom: 16px; left: 16px; border-width: 0 0 3px 3px; border-bottom-left-radius: 8px; }
        .scanner-corner-br { bottom: 16px; right: 16px; border-width: 0 3px 3px 0; border-bottom-right-radius: 8px; }

        /* Laser Animation */
        .scanner-laser-line {
            position: absolute;
            left: 20px;
            right: 20px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #34d399, #10b981, #34d399, transparent);
            box-shadow: 0 0 16px #10b981, 0 0 24px rgba(16, 185, 129, 0.6);
            z-index: 14;
            animation: laserScanning 2.4s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes laserScanning {
            0%   { top: 12%; opacity: 0; }
            15%  { opacity: 1; }
            85%  { opacity: 1; }
            100% { top: 88%; opacity: 0; }
        }

        /* Camera Top HUD */
        .camera-top-hud {
            position: absolute;
            top: 14px;
            left: 14px;
            right: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 25;
            pointer-events: auto;
        }
        .hud-action-pill {
            height: 38px;
            padding: 0 14px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }
        .hud-action-pill:hover {
            background: rgba(30, 41, 59, 1);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }
        .hud-action-pill.active {
            background: #f59e0b;
            border-color: #fbbf24;
            color: #ffffff;
        }

        /* Hero Result Card */
        .hero-result-card {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 12px 32px -4px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hero-ring-masuk {
            box-shadow: 0 0 0 4px #10b981, 0 10px 28px -4px rgba(16, 185, 129, 0.5);
        }
        .hero-ring-keluar {
            box-shadow: 0 0 0 4px #f59e0b, 0 10px 28px -4px rgba(245, 158, 11, 0.5);
        }
        .hero-ring-warning {
            box-shadow: 0 0 0 4px #f59e0b, 0 10px 28px -4px rgba(245, 158, 11, 0.5);
        }
        .hero-ring-error {
            box-shadow: 0 0 0 4px var(--brand-600), 0 10px 28px -4px rgb(var(--brand-600-rgb) / 0.5);
        }

        /* Countdown Progress Bar */
        .progress-countdown-bar {
            height: 4px;
            background: rgba(51, 65, 85, 0.5);
            border-radius: 9999px;
            overflow: hidden;
        }
        .progress-countdown-fill {
            height: 100%;
            width: 100%;
            background: #38bdf8;
            transition: width 0.1s linear;
        }

        /* Modal Standalone Backdrop */
        .kiosk-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 16px;
        }
        .kiosk-modal-backdrop.active {
            display: flex;
        }

        /* RFID & Multi-Scanner Controls */
        @keyframes rfidGlow {
            0%, 100% { opacity: 0.85; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.06); filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.6)); }
        }
        .rfid-pulse { animation: rfidGlow 2.5s ease-in-out infinite; }
        .active-scanner-tab {
            background: #10b981 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }
        .badge-metode-rfid {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.4);
        }
        .badge-metode-qr {
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.4);
        }
        .badge-metode-fp {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
            border: 1px solid rgba(192, 132, 252, 0.4);
        }
    </style>
    <?= \App\Libraries\Theme::styleTag() ?>
</head>
<body class="min-h-screen flex flex-col relative antialiased selection:bg-rose-500 selection:text-white">

    <div class="ambient-glow"></div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 1. STANDALONE KIOSK TOPBAR                          -->
    <!-- ═══════════════════════════════════════════════════ -->
    <header class="relative z-20 shrink-0 border-b border-white/10 bg-slate-900/90 backdrop-blur-md px-4 lg:px-8 py-3.5">
        <div class="max-w-[1600px] mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            
            <!-- School Brand & Status -->
            <div class="flex items-center gap-3.5">
                <img src="<?= $logo_app ?>" alt="Logo Sekolah" class="w-10 h-10 rounded-xl object-contain bg-white/10 p-1 border border-white/20 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base lg:text-lg font-black font-outfit text-white tracking-wide m-0">
                            <?= esc($setting->nama_sekolah ?? 'Presensi Sekolah') ?>
                        </h1>
                    </div>
                    <p class="text-xs text-slate-400 m-0">
                        Petugas: <span class="text-slate-200 font-bold"><?= esc($namaGuru) ?></span>
                        <?php if (!empty($qr_window)): ?>
                            &middot; <span class="text-emerald-400 font-bold" title="Scan masuk di luar jam ini ditolak otomatis">Jam scan <?= esc($qr_window) ?> WITA</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Realtime Clock & Action Tools -->
            <div class="flex items-center justify-between md:justify-end gap-3 flex-wrap">
                <!-- Big Digital Clock -->
                <div class="bg-slate-950/80 border border-slate-800 rounded-2xl px-4 py-2 flex items-center gap-3 shadow-inner">
                    <div class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-base">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div id="desktop-digital-clock" class="text-lg lg:text-xl font-black font-mono tracking-wider text-white leading-none">
                            --:--:--
                        </div>
                        <div id="desktop-calendar-date" class="text-[10px] font-medium text-slate-400 mt-0.5">
                            Memuat tanggal...
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-toggle-fullscreen"
                        class="px-3.5 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 active:scale-95 border border-white/10 text-white text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm"
                        title="Mode Layar Penuh Kiosk">
                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                    </button>

                    <button type="button" id="btn-open-audio"
                        class="px-3.5 py-2 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 active:scale-95 border border-emerald-400/30 text-emerald-300 text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm"
                        title="Pengaturan Suara Bel & Pengucapan Nama">
                        <i class="bi bi-volume-up-fill text-sm" id="header-audio-icon"></i>
                    </button>

                    <?php if ($isLoggedIn): ?>
                    <a href="<?= site_url('admin/presensi') ?>"
                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 border border-white/15 text-white text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm"
                        title="Kembali ke Dashboard Admin">
                        <i class="bi bi-speedometer2 text-sm"></i>
                        <span class="hidden sm:inline">Panel Admin</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 2. MAIN ONE PAGE CONTENT CONTAINER                  -->
    <!-- ═══════════════════════════════════════════════════ -->
    <main class="relative z-10 flex-1 max-w-[1600px] w-full mx-auto px-4 lg:px-8 py-5 flex flex-col gap-5">

        <!-- Metrics Row: 4 Metric Cards (selaras key backend: hadir/masuk/keluar/total) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
            <!-- Total Hadir -->
            <div class="kiosk-card p-4 flex items-center gap-3.5 border-l-4 border-l-emerald-500">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl font-bold shrink-0">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider m-0">Total Hadir Hari Ini</p>
                    <h3 class="text-2xl font-black font-outfit text-white m-0" id="stat-total-hadir"><?= (int)($stats['hadir'] ?? 0) ?></h3>
                </div>
            </div>

            <!-- Sudah Scan Masuk -->
            <div class="kiosk-card p-4 flex items-center gap-3.5 border-l-4 border-l-teal-500">
                <div class="w-12 h-12 rounded-2xl bg-teal-500/20 text-teal-400 flex items-center justify-center text-2xl font-bold shrink-0">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider m-0">Sudah Scan Masuk</p>
                    <h3 class="text-2xl font-black font-outfit text-white m-0" id="stat-masuk"><?= (int)($stats['masuk'] ?? 0) ?></h3>
                </div>
            </div>

            <!-- Sudah Scan Pulang -->
            <div class="kiosk-card p-4 flex items-center gap-3.5 border-l-4 border-l-amber-500">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl font-bold shrink-0">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider m-0">Sudah Scan Pulang</p>
                    <h3 class="text-2xl font-black font-outfit text-white m-0" id="stat-pulang"><?= (int)($stats['keluar'] ?? 0) ?></h3>
                </div>
            </div>

            <!-- Total Tercatat -->
            <div class="kiosk-card p-4 flex items-center gap-3.5 border-l-4 border-l-sky-500">
                <div class="w-12 h-12 rounded-2xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-2xl font-bold shrink-0">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider m-0">Total Tercatat</p>
                    <h3 class="text-2xl font-black font-outfit text-white m-0" id="stat-tercatat"><?= (int)($stats['total'] ?? 0) ?></h3>
                </div>
            </div>
        </div>

        <!-- Split Grid Desktop: 7 Kolom Kiri / 5 Kolom Kanan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

            <!-- ───────────────────────────────────────── -->
            <!-- KOLOM KIRI (7/12): KAMERA & BARCODE GUN   -->
            <!-- ───────────────────────────────────────── -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <div class="kiosk-card p-5">
                    
                    <!-- Mode Switcher Tabs (Absen Masuk vs Absen Pulang) -->
                    <?php
                    $modeTabD   = $mode_tab ?? ['masuk_open' => true, 'pulang_open' => true, 'default_mode' => 'masuk', 'buka_masuk' => '', 'tutup_masuk' => '', 'jam_pulang' => '', 'masuk_msg' => '', 'pulang_msg' => ''];
                    $masukOpenD = (bool) ($modeTabD['masuk_open'] ?? true);
                    $pulangOpenD = (bool) ($modeTabD['pulang_open'] ?? true);
                    $defModeD   = ($modeTabD['default_mode'] ?? 'masuk') === 'keluar' ? 'keluar' : 'masuk';
                    ?>
                    <div class="flex items-center gap-2.5 mb-4 bg-slate-950/80 p-1.5 rounded-2xl border border-slate-800">
                        <button id="btn-mode-masuk" type="button" data-scanmode="masuk"
                            class="mode-btn-tab <?= $defModeD === 'masuk' ? 'active-masuk' : '' ?>"
                            <?= $masukOpenD ? '' : 'disabled title="' . esc($modeTabD['masuk_msg'] ?? 'Di luar jadwal absen masuk') . '"' ?>>
                            <i class="bi bi-box-arrow-in-right text-lg"></i>
                            <span>MODE ABSEN MASUK</span>
                        </button>
                        <button id="btn-mode-keluar" type="button" data-scanmode="keluar"
                            class="mode-btn-tab <?= $defModeD === 'keluar' ? 'active-keluar' : '' ?>"
                            <?= $pulangOpenD ? '' : 'disabled title="' . esc($modeTabD['pulang_msg'] ?? 'Belum waktunya pulang') . '"' ?>>
                            <i class="bi bi-box-arrow-right text-lg"></i>
                            <span>MODE ABSEN PULANG</span>
                        </button>
                    </div>
                    <?php if (!empty($modeTabD['buka_masuk']) || !empty($modeTabD['jam_pulang'])): ?>
                    <p class="mode-schedule-hint" id="mode-schedule-hint">
                        Masuk <?= esc(($modeTabD['buka_masuk'] ?? '') . '–' . ($modeTabD['tutup_masuk'] ?? '')) ?>
                        &middot; Pulang &ge;<?= esc(($modeTabD['buka_pulang'] ?? $modeTabD['jam_pulang'] ?? '')) ?> WITA
                    </p>
                    <?php endif; ?>

                    <!-- Camera Viewport Box -->
                    <div class="camera-viewport-frame mb-4" id="camera-frame-box">
                        <div id="qr-reader-desktop"></div>

                        <!-- Neon Corners & Laser Scan -->
                        <div class="scanner-corner scanner-corner-tl"></div>
                        <div class="scanner-corner scanner-corner-tr"></div>
                        <div class="scanner-corner scanner-corner-bl"></div>
                        <div class="scanner-corner scanner-corner-br"></div>
                        <div class="scanner-laser-line" id="scanner-laser"></div>

                        <!-- Top HUD Controls Over Camera -->
                        <div class="camera-top-hud">
                            <div class="flex items-center gap-2">
                                <!-- Device selector dropdown -->
                                <div class="relative">
                                    <select id="select-video-device"
                                        class="hud-action-pill pr-8 appearance-none cursor-pointer border-white/20 text-white text-xs">
                                        <option value="">Deteksi Kamera...</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-white/70 text-[10px] pointer-events-none"></i>
                                </div>

                                <button type="button" id="btn-flip-cam" class="hud-action-pill" title="Flip Kamera Depan/Belakang">
                                    <i class="bi bi-arrow-repeat text-sm"></i>
                                    <span class="hidden sm:inline">Flip</span>
                                </button>

                                <span id="gps-lock-pill" class="hud-action-pill" title="Status kunci GPS kiosk (wajib untuk scan)">
                                    <i class="bi bi-geo-alt-fill text-sm text-slate-400" id="gps-lock-icon"></i>
                                    <span class="hidden sm:inline" id="gps-lock-text">GPS: mencari...</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-desktop-torch" class="hud-action-pill hidden" title="Lampu Senter">
                                    <i class="bi bi-lightning-charge-fill text-amber-400"></i>
                                    <span>Senter</span>
                                </button>

                                <button type="button" id="btn-desktop-pause" class="hud-action-pill" title="Jeda Kamera">
                                    <i class="bi bi-pause-fill" id="icon-pause-cam"></i>
                                    <span class="hidden sm:inline" id="label-pause-cam">Jeda</span>
                                </button>
                            </div>
                        </div>

                        <!-- Loading Overlay -->
                        <div id="camera-loading-overlay" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-950/90 text-white gap-3 p-4">
                            <div class="w-10 h-10 rounded-full border-4 border-rose-500 border-t-transparent animate-spin"></div>
                            <p class="text-sm font-bold m-0 text-slate-200">Menghubungkan Sensor Kamera...</p>
                            <p class="text-xs text-slate-400 m-0">Pastikan izin kamera di browser telah diberikan</p>
                        </div>

                        <!-- Error Overlay -->
                        <div id="camera-error-overlay" class="absolute inset-0 z-20 hidden flex-col items-center justify-center bg-slate-950/95 text-white gap-3 p-6 text-center">
                            <div class="w-14 h-14 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 text-2xl">
                                <i class="bi bi-camera-video-off"></i>
                            </div>
                            <div>
                                <h6 class="font-bold text-base m-0 text-white">Kamera Belum Terhubung</h6>
                                <p class="text-xs text-slate-400 mt-1 m-0">Gunakan Barcode Scanner Gun USB di bawah atau coba muat ulang kamera.</p>
                            </div>
                            <button type="button" id="btn-retry-cam" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs">
                                <i class="bi bi-arrow-clockwise mr-1"></i> Coba Hubungkan Kamera
                            </button>
                        </div>
                    </div>

                    <!-- Input Controls: USB Barcode Gun & Filter Kelas -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                        <!-- Barcode Scanner Gun USB Standby Input -->
                        <div class="md:col-span-7">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-extrabold text-slate-300 uppercase tracking-wider flex items-center gap-1.5 m-0">
                                    <i id="input-scanner-icon" class="bi bi-upc-scan text-rose-400"></i>
                                    <span id="input-scanner-label">Barcode / NIS / RFID</span>
                                    <span id="rfid-mode-badge" class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold border hidden">RFID AKTIF</span>
                                </label>
                            </div>
                            <div class="relative flex items-center">
                                <input type="text" id="input-barcode-scanner" autofocus autocomplete="off"
                                    placeholder="Tembak Barcode / Ketik NIS / Tempel RFID..."
                                    class="w-full pl-9 pr-48 py-2.5 bg-slate-950/80 border border-slate-700 focus:bg-slate-900 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30 rounded-xl text-xs font-semibold text-white placeholder:text-slate-500 transition-all">
                                <i class="bi bi-keyboard absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm pointer-events-none"></i>
                                
                                <div class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                    <!-- Toggle Mode RFID (kartu RFID via keyboard wedge / Wiegand) -->
                                    <button type="button" id="btn-toggle-rfid-mode"
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 active:scale-95 text-slate-300 font-bold text-xs border border-slate-600 shadow-xs transition-all flex items-center gap-1"
                                        title="Aktifkan mode khusus kartu RFID">
                                        <i class="bi bi-credit-card-2-front"></i> <span>RFID</span>
                                    </button>

                                    <!-- Toggle Mode Fingerprint -->
                                    <button type="button" id="btn-toggle-fp-mode"
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 active:scale-95 text-slate-300 font-bold text-xs border border-slate-600 shadow-xs transition-all flex items-center gap-1"
                                        title="Aktifkan mode khusus sidik jari">
                                        <i class="bi bi-fingerprint"></i> <span>FP</span>
                                    </button>

                                    <button type="button" id="btn-barcode-proses"
                                        class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs shadow-xs transition-all">
                                        Proses
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Kelas -->
                        <div class="md:col-span-5">
                            <label class="block text-[11px] font-extrabold text-slate-300 uppercase tracking-wider mb-1">
                                <i class="bi bi-filter text-slate-400 mr-1"></i>Filter Kategori (Opsional)
                            </label>
                            <div class="relative">
                                <select id="select-kelas-filter" class="w-full py-2.5 px-3 bg-slate-950/80 border border-slate-700 rounded-xl text-xs font-semibold text-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30">
                                    <option value="">Semua (Siswa & Guru)</option>
                                    <option value="guru">Khusus Guru / GTK</option>
                                    <?php if (!empty($kelasList)): ?>
                                        <optgroup label="Filter Per Kelas Siswa">
                                            <?php foreach ($kelasList as $k): ?>
                                                <option value="<?= (int)$k->id_kelas ?>" <?= ($selectedKelas == $k->id_kelas) ? 'selected' : '' ?>>
                                                    <?= esc($k->nama_kelas) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ───────────────────────────────────────── -->
            <!-- KOLOM KANAN (5/12): HERO LIVE & REALTIME  -->
            <!-- ───────────────────────────────────────── -->
            <div class="lg:col-span-5 flex flex-col gap-4">

                <!-- 1. Hero Live Card (Hasil Scan Terakhir) -->
                <div class="hero-result-card" id="hero-result-container">
                    <!-- Header Banner -->
                    <div id="hero-header-banner" class="p-3.5 bg-gradient-to-r from-slate-900 to-slate-950 text-white text-center transition-colors duration-300 border-b border-white/10">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <span id="hero-badge-title" class="inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/15 text-slate-200">
                                STANDBY SCANNER
                            </span>
                            <span id="hero-metode-badge" class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase"></span>
                        </div>
                        <h5 id="hero-main-message" class="text-sm lg:text-base font-black font-outfit m-0 text-white">
                            Silakan Scan QR / RFID / Sidik Jari
                        </h5>
                    </div>

                    <!-- Body Siswa/Guru Info -->
                    <div class="p-4 lg:p-5 flex items-center gap-4">
                        <div class="relative shrink-0">
                            <img id="hero-siswa-foto" src="<?= esc(\App\Services\AvatarService::inisial('Scan', 128, '#0f172a'), 'attr') ?>"
                                alt="Foto"
                                class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl object-cover border-2 border-slate-700 shadow-md hero-ring-masuk transition-all">
                            <span id="hero-status-pill" class="absolute -bottom-2 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase text-white shadow-sm border border-white/20 bg-slate-800 whitespace-nowrap">
                                STANDBY
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h4 id="hero-siswa-nama" class="text-base lg:text-lg font-black font-outfit text-white m-0 leading-tight truncate">
                                Menunggu Scan...
                            </h4>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span id="hero-siswa-kelas" class="inline-flex items-center gap-1 text-xs font-bold text-slate-300 bg-slate-800/90 px-2.5 py-0.5 rounded-lg border border-slate-700">
                                    <i class="bi bi-person-badge text-slate-400"></i> Kategori
                                </span>
                                <span id="hero-siswa-nis" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 bg-slate-800 px-2.5 py-0.5 rounded-lg border border-slate-700">
                                    NIS: -
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-xs pt-2.5 border-t border-slate-800">
                                <span class="text-slate-400 font-medium">Waktu Absen:</span>
                                <span id="hero-time-text" class="font-bold font-mono text-slate-200">--:-- WITA</span>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-reset Progress Bar -->
                    <div class="px-4 pb-3.5">
                        <div class="progress-countdown-bar">
                            <div class="progress-countdown-fill" id="hero-countdown-bar" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>

                <!-- 2. Realtime Live Feed Riwayat Presensi Hari Ini -->
                <div class="kiosk-card p-4 flex-1 flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <h6 class="font-bold font-outfit text-white text-sm m-0">Riwayat Presensi Hari Ini</h6>
                        </div>
                        <button type="button" id="btn-refresh-feed" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition-all border border-slate-700 flex items-center gap-1">
                            <i class="bi bi-arrow-clockwise text-xs"></i> Refresh
                        </button>
                    </div>

                    <!-- Feed List Container -->
                    <div class="overflow-y-auto max-h-[340px] pr-1 divide-y divide-slate-800" id="live-history-feed">
                        <div class="py-8 text-center text-slate-500 text-xs">
                            <div class="w-7 h-7 rounded-full border-2 border-rose-500 border-t-transparent animate-spin mx-auto mb-2"></div>
                            Memuat riwayat presensi hari ini...
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 3. MODAL PENGATURAN SUARA (STANDALONE)             -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div id="modal-audio-settings-desktop" class="kiosk-modal-backdrop">
        <div class="kiosk-card w-full max-w-md p-6 border border-white/15 shadow-2xl relative overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                        <i class="bi bi-soundwave"></i>
                    </div>
                    <div>
                        <h5 class="font-bold font-outfit text-white text-base m-0">Pengaturan Suara</h5>
                        <p class="text-[11px] text-slate-400 m-0">Bel audio melodi &amp; sebut nama siswa</p>
                    </div>
                </div>
                <button type="button" id="btn-close-audio" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <div class="space-y-3 mb-5">
                <!-- Chime Bel Bel -->
                <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 cursor-pointer hover:bg-slate-900 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-base">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Suara Bel / Melodi Notifikasi</div>
                            <div class="text-[11px] text-slate-400">Melodi nada saat presensi tercatat</div>
                        </div>
                    </div>
                    <input type="checkbox" id="setting-sound-chime" class="w-5 h-5 accent-rose-600 rounded cursor-pointer">
                </label>

                <!-- Voice TTS -->
                <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 cursor-pointer hover:bg-slate-900 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center text-base">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Sebut Nama Siswa (Voice TTS)</div>
                            <div class="text-[11px] text-slate-400">Pengucapan nama alami bahasa Indonesia (id-ID)</div>
                        </div>
                    </div>
                    <input type="checkbox" id="setting-voice-tts" class="w-5 h-5 accent-rose-600 rounded cursor-pointer">
                </label>
            </div>

            <div class="pt-4 border-t border-white/10">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Uji Coba Suara:</span>
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" data-soundtest="in" class="py-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-400/30 font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                        <i class="bi bi-play-circle-fill text-sm"></i> Tes Masuk
                    </button>
                    <button type="button" data-soundtest="out" class="py-2.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-400/30 font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                        <i class="bi bi-play-circle-fill text-sm"></i> Tes Pulang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 4. JAVASCRIPT LOGIC & LIBRARIES                     -->
    <!-- ═══════════════════════════════════════════════════ -->
    <!-- html5-qrcode library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
    (function () {
        'use strict';

        // ── Avatar cadangan: semuanya lokal, tidak bergantung server eksternal ──
        const FOTO_CADANGAN = <?= json_encode(\App\Services\AvatarService::cadangan(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const FOTO_STANDBY = <?= json_encode(\App\Services\AvatarService::inisial('Scan', 128, '#0f172a'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const FOTO_PERINGATAN = <?= json_encode(\App\Services\AvatarService::inisial('Alert', 128, '#e11d48'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        // ── STATE VARIABLES ──────────────────────────
        const MODE_TAB = {
            masukOpen:  <?= !empty($modeTabD['masuk_open']) ? 'true' : 'false' ?>,
            pulangOpen: <?= !empty($modeTabD['pulang_open']) ? 'true' : 'false' ?>,
            masukMsg:   <?= json_encode($modeTabD['masuk_msg'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            pulangMsg:  <?= json_encode($modeTabD['pulang_msg'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            jamMasuk:   <?= json_encode($modeTabD['jam_masuk'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            bukaMasuk:  <?= json_encode($modeTabD['buka_masuk'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            tutupMasuk: <?= json_encode($modeTabD['tutup_masuk'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            jamPulang:  <?= json_encode($modeTabD['jam_pulang'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            bukaPulang: <?= json_encode($modeTabD['buka_pulang'] ?? ($modeTabD['jam_pulang'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
            qrAktif:    <?= !empty($modeTabD['qr_aktif']) ? 'true' : 'false' ?>,
            qrMulai:    <?= json_encode($modeTabD['qr_mulai'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            qrSelesai:  <?= json_encode($modeTabD['qr_selesai'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        };
        let scanMode        = <?= json_encode($defModeD ?? 'masuk') ?>; // bawaan mengikuti jadwal
        let html5QrCode     = null;
        let isScanning      = false;
        let isProcessing    = false;
        let isCameraPaused  = false;
        let isTorchOn       = false;
        let cameraDevices   = [];
        let currentCameraId = null;
        let facingModeIndex = 0; // 0: environment, 1: user
        const facingModes   = ['environment', 'user'];

        let soundEnabled    = localStorage.getItem('presensi_qr_sound') !== '0';
        let voiceEnabled    = localStorage.getItem('presensi_qr_voice') !== '0';
        let rfidMode        = localStorage.getItem('presensi_qr_rfid') === '1';
        let audioCtx        = null;

        let heroResetTimer  = null;
        let heroInterval    = null;

        // ── GPS kiosk untuk geofence server ──────
        let kioskLoc = { lat: null, lng: null, accuracy: null, at: 0 };
        let geoWatchId = null;

        // Jam acuan = jam server (WITA). Tanpa ini jendela buka/tutup scan dan
        // label "WITA" ikut zona waktu perangkat: kiosk WIB membuka scan
        // satu jam lebih awal. Cukup sekali: stamp dibekukan saat render, jadi
        // witaInit() ulang dengan stamp identik akan membekukan jam di waktu
        // load, bukan membuatnya mengikuti server.
        witaInit('<?= date('Y-m-d H:i:s') ?>');

        // ── 1. REALTIME DIGITAL CLOCK & CALENDAR ─────
        function updateDigitalClock() {
            const now = witaNow();
            const hrs = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const sec = String(now.getSeconds()).padStart(2, '0');
            $('#desktop-digital-clock').text(`${hrs}:${min}:${sec} WITA`);

            const hariList  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulanList = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const hari  = hariList[now.getDay()];
            const tgl   = now.getDate();
            const bln   = bulanList[now.getMonth()];
            const thn   = now.getFullYear();
            $('#desktop-calendar-date').text(`${hari}, ${tgl} ${bln} ${thn}`);
        }
        setInterval(updateDigitalClock, 1000);
        updateDigitalClock();

        // ── 2. WEB AUDIO SYNTHESIZER & INDONESIAN TTS 
        function unlockAudio() {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) audioCtx = new AudioCtxClass();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        }
        $(document).one('click keydown touchstart', unlockAudio);

        function playChime(type) {
            if (!soundEnabled) return;
            unlockAudio();
            if (!audioCtx) return;

            try {
                const ctx = audioCtx;
                const now = ctx.currentTime;

                if (type === 'success_in') {
                    // Rising 3-tone chime (E5 -> G#5 -> B5)
                    [659.25, 830.61, 987.77].forEach((freq, i) => {
                        const t = now + (i * 0.08);
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.24, t);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + (i === 2 ? 0.6 : 0.25));
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + (i === 2 ? 0.6 : 0.25));
                    });
                } else if (type === 'success_out') {
                    // Warm descending 3-tone chime (B5 -> G#5 -> E5)
                    [987.77, 830.61, 659.25].forEach((freq, i) => {
                        const t = now + (i * 0.09);
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.22, t);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + (i === 2 ? 0.55 : 0.22));
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + (i === 2 ? 0.55 : 0.22));
                    });
                } else if (type === 'warning') {
                    // Double warning ping
                    [440, 349.23].forEach((freq, i) => {
                        const t = now + (i * 0.12);
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.2, t);
                        gain.gain.exponentialRampToValueAtTime(0.01, t + 0.18);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + 0.18);
                    });
                } else {
                    // Error tone
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, now);
                    osc.frequency.exponentialRampToValueAtTime(130, now + 0.28);
                    gain.gain.setValueAtTime(0.24, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.28);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.28);
                }
            } catch (e) {
                console.warn('playChime error:', e);
            }
        }

        // Format nama siswa agar diucapkan secara fonetik alami oleh TTS (tidak dieja per huruf)
        function formatSpeechName(fullName) {
            if (!fullName) return '';
            let clean = String(fullName).replace(/['"`]/g, '').replace(/[^\w\s]/gi, ' ').trim();
            let words = clean.toLowerCase().split(/\s+/).filter(Boolean);
            if (words.length === 0) return '';
            const maxWords = (words[0].length <= 2 && words.length > 2) ? 3 : 2;
            const picked = words.slice(0, Math.min(words.length, maxWords));
            return picked.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        }

        // Cache voice bahasa Indonesia
        let indonesianVoice = null;
        function getIndonesianVoice() {
            if (!('speechSynthesis' in window)) return null;
            if (indonesianVoice) return indonesianVoice;
            const voices = window.speechSynthesis.getVoices();
            if (!voices || voices.length === 0) return null;

            indonesianVoice = voices.find(v => v.lang === 'id-ID' || v.lang === 'id_ID')
                || voices.find(v => v.lang && v.lang.toLowerCase().startsWith('id'))
                || voices.find(v => v.name && (v.name.toLowerCase().includes('indonesia') || v.name.toLowerCase().includes('indonesian')))
                || null;
            return indonesianVoice;
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = function () {
                indonesianVoice = null;
                getIndonesianVoice();
            };
            try { window.speechSynthesis.getVoices(); } catch(e) {}
        }

        function speakText(rawText) {
            if (!voiceEnabled || !('speechSynthesis' in window) || !rawText) return;
            try {
                window.speechSynthesis.cancel();
                let text = String(rawText).replace(/['"`]/g, '').trim();
                text = text.replace(/\b[A-Z]{2,}\b/g, match => match.charAt(0) + match.slice(1).toLowerCase());

                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 1.0;
                utterance.pitch = 1.0;

                const voice = getIndonesianVoice();
                if (voice) utterance.voice = voice;

                setTimeout(() => {
                    try { window.speechSynthesis.speak(utterance); } catch (e) {}
                }, 60);
            } catch (e) {
                console.warn('speakText error:', e);
            }
        }

        // ── 3. AUDIO SETTINGS MODAL ──────────────────
        window.openAudioSettings = function () {
            $('#setting-sound-chime').prop('checked', soundEnabled);
            $('#setting-voice-tts').prop('checked', voiceEnabled);
            $('#modal-audio-settings-desktop').addClass('active');
        };

        window.closeAudioSettings = function () {
            $('#modal-audio-settings-desktop').removeClass('active');
            focusBarcodeGunInput();
        };

        $('#modal-audio-settings-desktop').on('click', function (e) {
            if (e.target === this) window.closeAudioSettings();
        });

        window.toggleSoundSetting = function (checked) {
            soundEnabled = !!checked;
            localStorage.setItem('presensi_qr_sound', soundEnabled ? '1' : '0');
            updateAudioIcons();
            if (soundEnabled) playChime('success_in');
        };

        // ── RFID & FINGERPRINT MODE CONTROLS ──────────
        function updateScannerModeControls() {
            const $badge      = $('#rfid-mode-badge');
            const $btnRfid    = $('#btn-toggle-rfid-mode');
            const $btnFp      = $('#btn-toggle-fp-mode');
            const $input      = $('#input-barcode-scanner');
            const $lblText    = $('#input-scanner-label');
            const $lblIcon    = $('#input-scanner-icon');

            if (rfidMode) {
                $badge.removeClass('hidden bg-purple-900/60 text-purple-200 border-purple-500/30')
                      .addClass('bg-emerald-900/60 text-emerald-200 border-emerald-500/30')
                      .html('<i class="bi bi-credit-card-2-front mr-1"></i> RFID AKTIF');
                $btnRfid.addClass('active-scanner-tab').removeClass('bg-slate-800 text-slate-300 border-slate-600');
                $btnFp.removeClass('active-scanner-tab').addClass('bg-slate-800 text-slate-300 border-slate-600');
                $lblText.text('Kartu RFID / Nomor Kartu');
                $lblIcon.attr('class', 'bi bi-credit-card-2-front text-emerald-400');
                $input.attr('placeholder', 'Tempel kartu RFID, tembak QR, atau ketik NIS…');
            } else if (fpMode) {
                $badge.removeClass('hidden bg-emerald-900/60 text-emerald-200 border-emerald-500/30')
                      .addClass('bg-purple-900/60 text-purple-200 border-purple-500/30')
                      .html('<i class="bi bi-fingerprint mr-1"></i> FP AKTIF');
                $btnFp.addClass('active-scanner-tab').removeClass('bg-slate-800 text-slate-300 border-slate-600');
                $btnRfid.removeClass('active-scanner-tab').addClass('bg-slate-800 text-slate-300 border-slate-600');
                $lblText.text('UserID Fingerprint');
                $lblIcon.attr('class', 'bi bi-fingerprint text-purple-400');
                $input.attr('placeholder', 'Tembak / ketik UserID sidik jari lalu Enter…');
            } else {
                $badge.addClass('hidden');
                $btnRfid.removeClass('active-scanner-tab').addClass('bg-slate-800 text-slate-300 border-slate-600');
                $btnFp.removeClass('active-scanner-tab').addClass('bg-slate-800 text-slate-300 border-slate-600');
                $lblText.text('Barcode / NIS / RFID');
                $lblIcon.attr('class', 'bi bi-upc-scan text-rose-400');
                $input.attr('placeholder', 'Tembak Barcode / Ketik NIS / Tempel RFID…');
            }
        }

        window.toggleRfidMode = function () {
            rfidMode = !rfidMode;
            if (rfidMode) fpMode = false; // saling eksklusif untuk input teks
            localStorage.setItem('presensi_qr_rfid', rfidMode ? '1' : '0');
            localStorage.setItem('presensi_qr_fp', fpMode ? '1' : '0');
            updateScannerModeControls();
            focusBarcodeGunInput();
            if (rfidMode) {
                showHeroResult({ status: true, message: 'Mode Kartu RFID diaktifkan. Tempelkan kartu pada reader.' }, scanMode);
                setTimeout(resetHeroStandby, 1500);
            }
        };

        let fpMode = localStorage.getItem('presensi_qr_fp') === '1';

        window.toggleFpMode = function () {
            fpMode = !fpMode;
            if (fpMode) rfidMode = false;
            localStorage.setItem('presensi_qr_fp', fpMode ? '1' : '0');
            localStorage.setItem('presensi_qr_rfid', rfidMode ? '1' : '0');
            updateScannerModeControls();
            focusBarcodeGunInput();
            if (fpMode) {
                showHeroResult({ status: true, message: 'Mode Sidik Jari diaktifkan. Masukkan ID fingerprint.' }, scanMode);
                setTimeout(resetHeroStandby, 1500);
            }
        };

        // Inisialisasi awal UI scanner mode
        updateScannerModeControls();

        window.toggleVoiceSetting = function (checked) {
            voiceEnabled = !!checked;
            localStorage.setItem('presensi_qr_voice', voiceEnabled ? '1' : '0');
            if (voiceEnabled) speakText('Suara aktif');
        };

        window.testDesktopSound = function (type) {
            unlockAudio();
            if (type === 'in') {
                playChime('success_in');
                setTimeout(() => speakText('Tes suara. Absen masuk.'), 300);
            } else {
                playChime('success_out');
                setTimeout(() => speakText('Tes suara. Absen pulang.'), 300);
            }
        };

        function updateAudioIcons() {
            const icon = document.getElementById('header-audio-icon');
            if (icon) {
                icon.className = (soundEnabled || voiceEnabled) ? 'bi bi-volume-up-fill text-sm' : 'bi bi-volume-mute-fill text-sm text-rose-400';
            }
        }
        updateAudioIcons();

        // ── 4. MODE SWITCHER (MASUK / PULANG, mengikuti jadwal otomatis) ──
        function normHMS(s) { s = String(s || ''); return s.length === 5 ? s + ':00' : s; }
        function nowHMS() {
            return witaHMS();
        }
        function computeModeOpen(now) {
            let mOpen = true, mMsg = '';
            if (MODE_TAB.bukaMasuk && MODE_TAB.tutupMasuk) {
                const b = normHMS(MODE_TAB.bukaMasuk), t = normHMS(MODE_TAB.tutupMasuk);
                if (now < b)      { mOpen = false; mMsg = 'Absen Masuk belum dibuka. Jadwal: ' + MODE_TAB.jamMasuk + ' (Dibuka ' + MODE_TAB.bukaMasuk + ')'; }
                else if (now > t) { mOpen = false; mMsg = 'Waktu Absen Masuk habis. Batas: ' + MODE_TAB.tutupMasuk; }
            }
            if (mOpen && MODE_TAB.qrAktif && MODE_TAB.qrMulai && MODE_TAB.qrSelesai) {
                const qs = normHMS(MODE_TAB.qrMulai), qe = normHMS(MODE_TAB.qrSelesai);
                if (now < qs || now > qe) { mOpen = false; mMsg = 'Di luar jam operasional scan (' + MODE_TAB.qrMulai + '–' + MODE_TAB.qrSelesai + ').'; }
            }
            let pOpen = true, pMsg = '';
            // Pulang langsung dibuka begitu jendela masuk ditutup (bukaPulang
            // = min(tutup_masuk, jam_pulang) dari server).
            const bp = MODE_TAB.bukaPulang || MODE_TAB.jamPulang;
            if (bp && now < normHMS(bp)) {
                pOpen = false; pMsg = 'Belum waktunya pulang. Pulang dibuka pukul ' + bp + '.';
            }
            // Jendela operasional yang sama menutup tab pulang — dulu tab ini
            // tetap terbuka sampai lewat tengah malam.
            if (pOpen && MODE_TAB.qrAktif && MODE_TAB.qrMulai && MODE_TAB.qrSelesai) {
                const qs = normHMS(MODE_TAB.qrMulai), qe = normHMS(MODE_TAB.qrSelesai);
                if (now < qs || now > qe) {
                    pOpen = false;
                    pMsg = 'Di luar jam operasional scan (' + MODE_TAB.qrMulai + '–' + MODE_TAB.qrSelesai + '). Scan pulang ditutup pukul ' + MODE_TAB.qrSelesai + '.';
                }
            }
            return { mOpen, mMsg, pOpen, pMsg };
        }
        function modeBlockedMsg(m) {
            if (m === 'masuk' && !MODE_TAB.masukOpen)   return MODE_TAB.masukMsg || 'Di luar jadwal absen masuk.';
            if (m === 'keluar' && !MODE_TAB.pulangOpen) return MODE_TAB.pulangMsg || 'Belum waktunya pulang.';
            return '';
        }
        function paintModeTabs() {
            const btnMasuk  = document.getElementById('btn-mode-masuk');
            const btnKeluar = document.getElementById('btn-mode-keluar');
            if (btnMasuk) {
                btnMasuk.disabled = !MODE_TAB.masukOpen;
                btnMasuk.title = MODE_TAB.masukOpen ? '' : (MODE_TAB.masukMsg || 'Di luar jadwal absen masuk');
            }
            if (btnKeluar) {
                btnKeluar.disabled = !MODE_TAB.pulangOpen;
                btnKeluar.title = MODE_TAB.pulangOpen ? '' : (MODE_TAB.pulangMsg || 'Belum waktunya pulang');
            }
        }
        function applyScanMode(m) {
            scanMode = m;
            const btnMasuk  = document.getElementById('btn-mode-masuk');
            const btnKeluar = document.getElementById('btn-mode-keluar');
            if (btnMasuk) btnMasuk.classList.remove('active-masuk', 'active-keluar');
            if (btnKeluar) btnKeluar.classList.remove('active-masuk', 'active-keluar');
            if (m === 'masuk') {
                if (btnMasuk) btnMasuk.classList.add('active-masuk');
                $('#scanner-laser').css({
                    'background': 'linear-gradient(90deg, transparent, #34d399, #10b981, #34d399, transparent)',
                    'box-shadow': '0 0 16px #10b981, 0 0 24px rgba(16, 185, 129, 0.6)'
                });
                $('.scanner-corner').css('border-color', '#10b981');
            } else {
                if (btnKeluar) btnKeluar.classList.add('active-keluar');
                $('#scanner-laser').css({
                    'background': 'linear-gradient(90deg, transparent, #fbbf24, #f59e0b, #fbbf24, transparent)',
                    'box-shadow': '0 0 16px #f59e0b, 0 0 24px rgba(245, 158, 11, 0.6)'
                });
                $('.scanner-corner').css('border-color', '#f59e0b');
            }
            focusBarcodeGunInput();
        }
        // Hitung ulang + pindah tab otomatis (diam-diam) saat melewati batas waktu.
        function refreshModeTabs() {
            try {
                const st = computeModeOpen(nowHMS());
                MODE_TAB.masukOpen = st.mOpen;   MODE_TAB.masukMsg = st.mMsg;
                MODE_TAB.pulangOpen = st.pOpen;  MODE_TAB.pulangMsg = st.pMsg;
                paintModeTabs();
                const curOpen = (scanMode === 'masuk') ? st.mOpen : st.pOpen;
                if (!curOpen) {
                    if (st.mOpen) applyScanMode('masuk');
                    else if (st.pOpen) applyScanMode('keluar');
                }
                scheduleNextSwitch();
            } catch (e) {}
        }
        // Refresh tepat pada detik batas berikutnya (presisi detik); interval hanya pengaman.
        function scheduleNextSwitch() {
            try {
                if (scheduleNextSwitch._t) clearTimeout(scheduleNextSwitch._t);
                const cands = [MODE_TAB.bukaMasuk, MODE_TAB.tutupMasuk, MODE_TAB.bukaPulang || MODE_TAB.jamPulang];
                if (MODE_TAB.qrAktif) cands.push(MODE_TAB.qrMulai, MODE_TAB.qrSelesai);
                let best = null;
                // Selisih dihitung dalam detik-jam-WITA, bukan Date lokal:
                // batas '07:30' adalah jam server, jadi di kiosk WIB timer lama
                // meleset satu jam.
                const detikHari = (hms) => { const q = String(hms).split(':'); return (+q[0] || 0) * 3600 + (+q[1] || 0) * 60 + (+q[2] || 0); };
                const nowSec = detikHari(witaHMS());
                cands.filter(Boolean).map(normHMS).forEach(h => {
                    const delta = detikHari(h) - nowSec;
                    if (delta > 0) {
                        const ms = delta * 1000;
                        if (best === null || ms < best) best = ms;
                    }
                });
                if (best !== null) scheduleNextSwitch._t = setTimeout(refreshModeTabs, best + 400);
            } catch (e) {}
        }
        window.setScanMode = function (m) {
            const msg = modeBlockedMsg(m);
            if (msg) {
                if (typeof showHeroResult === 'function') showHeroResult({ status: false, message: msg }, m);
                else alert(msg);
                return;
            }
            applyScanMode(m);
        };
        // Inisialisasi senyap (tanpa popup): pastikan mode yang tampil sedang terbuka
        refreshModeTabs();
        setInterval(refreshModeTabs, 15000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshModeTabs(); });

        // ── 5. FULLSCREEN KIOSK TOGGLE ───────────────
        window.toggleKioskFullscreen = function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    $('#label-fullscreen').text('Keluar Fullscreen');
                }).catch(err => {
                    console.warn('Fullscreen error:', err);
                });
            } else {
                document.exitFullscreen().then(() => {
                    $('#label-fullscreen').text('Layar Penuh');
                });
            }
        };

        document.addEventListener('fullscreenchange', function () {
            if (!document.fullscreenElement) {
                $('#label-fullscreen').text('Layar Penuh');
            }
        });

        // ── 6. CAMERA INITIALIZATION (HTML5-QRCODE) ──
        function initDesktopCamera() {
            $('#camera-loading-overlay').show();
            $('#camera-error-overlay').hide();

            if (html5QrCode) {
                html5QrCode.stop().catch(() => {}).then(startNewScanner);
            } else {
                startNewScanner();
            }
        }

        function startNewScanner() {
            html5QrCode = new Html5Qrcode('qr-reader-desktop');

            // Enumerasi kamera input
            Html5Qrcode.getCameras().then(devices => {
                cameraDevices = devices || [];
                const $select = $('#select-video-device');
                $select.empty();

                if (devices && devices.length > 0) {
                    devices.forEach((dev, idx) => {
                        const label = dev.label || `Kamera ${idx + 1}`;
                        $select.append(`<option value="${dev.id}">${label}</option>`);
                    });
                    currentCameraId = devices[0].id;
                    startCameraWithConfig({ deviceId: { exact: currentCameraId } });
                } else {
                    startCameraWithConfig({ facingMode: facingModes[facingModeIndex] });
                }
            }).catch(err => {
                console.warn('getCameras error, fallback to facingMode:', err);
                startCameraWithConfig({ facingMode: facingModes[facingModeIndex] });
            });
        }

        function startCameraWithConfig(cameraConfig) {
            const config = {
                fps: 20,
                qrbox: function (viewfinderWidth, viewfinderHeight) {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    const qrEdge  = Math.floor(minEdge * 0.75);
                    return { width: qrEdge, height: qrEdge };
                },
                aspectRatio: 1.777778, // 16:9 widescreen
            };

            html5QrCode.start(
                cameraConfig,
                config,
                onScanSuccess,
                onScanFailure
            ).then(() => {
                $('#camera-loading-overlay').hide();
                $('#camera-error-overlay').hide();
                isScanning = true;
                isCameraPaused = false;
                $('#label-pause-cam').text('Jeda');
                $('#icon-pause-cam').attr('class', 'bi bi-pause-fill');
                checkTorchDesktopSupport();
                focusBarcodeGunInput();
            }).catch(err => {
                console.error('Camera start error:', err);
                $('#camera-loading-overlay').hide();
                $('#camera-error-overlay').show();
                isScanning = false;
            });
        }

        window.switchCameraDevice = function (deviceId) {
            if (!deviceId || deviceId === currentCameraId) return;
            if (!html5QrCode) { currentCameraId = deviceId; initDesktopCamera(); return; }
            currentCameraId = deviceId;
            $('#camera-loading-overlay').show();
            html5QrCode.stop().then(() => {
                startCameraWithConfig({ deviceId: { exact: deviceId } });
            }).catch(() => initDesktopCamera());
        };

        window.cycleFacingMode = function () {
            if (!html5QrCode) { initDesktopCamera(); return; }
            facingModeIndex = (facingModeIndex + 1) % facingModes.length;
            $('#camera-loading-overlay').show();
            html5QrCode.stop().then(() => {
                startCameraWithConfig({ facingMode: facingModes[facingModeIndex] });
            }).catch(() => initDesktopCamera());
        };

        window.toggleCameraPause = function () {
            if (!html5QrCode || !isScanning) return;
            if (!isCameraPaused) {
                html5QrCode.pause();
                isCameraPaused = true;
                $('#label-pause-cam').text('Lanjut');
                $('#icon-pause-cam').attr('class', 'bi bi-play-fill');
            } else {
                html5QrCode.resume();
                isCameraPaused = false;
                $('#label-pause-cam').text('Jeda');
                $('#icon-pause-cam').attr('class', 'bi bi-pause-fill');
            }
        };

        function checkTorchDesktopSupport() {
            try {
                const video = document.querySelector('#qr-reader-desktop video');
                if (video && video.srcObject) {
                    const track = video.srcObject.getVideoTracks()[0];
                    const caps  = track.getCapabilities ? track.getCapabilities() : {};
                    if (caps.torch) {
                        $('#btn-desktop-torch').show();
                    } else {
                        $('#btn-desktop-torch').hide();
                    }
                }
            } catch (e) {}
        }

        window.toggleTorchDesktop = function () {
            try {
                const video = document.querySelector('#qr-reader-desktop video');
                if (!video || !video.srcObject) return;
                const track = video.srcObject.getVideoTracks()[0];
                isTorchOn = !isTorchOn;
                track.applyConstraints({ advanced: [{ torch: isTorchOn }] }).then(() => {
                    $('#btn-desktop-torch').toggleClass('active', isTorchOn);
                }).catch(e => console.warn('Torch error:', e));
            } catch (e) {}
        };

        function onScanSuccess(decodedText) {
            if (isProcessing || !decodedText) return;
            processAttendanceScan(decodedText);
        }

        function onScanFailure(error) {
            // Frame tanpa QR
        }

        // ── 6b. GPS KIOSK (wajib untuk geofence server) ──
        function updateGpsPill() {
            const $icon = $('#gps-lock-icon');
            const $text = $('#gps-lock-text');
            if (kioskLoc.lat === null) {
                $icon.attr('class', 'bi bi-geo-alt-fill text-sm text-slate-400');
                $text.text('GPS: mencari...');
            } else {
                $icon.attr('class', 'bi bi-geo-alt-fill text-sm text-emerald-400');
                $text.text('GPS lock ±' + Math.round(kioskLoc.accuracy || 0) + 'm');
            }
        }

        function startKioskGpsWatch() {
            if (!('geolocation' in navigator)) {
                $('#gps-lock-text').text('GPS: tak didukung');
                return;
            }
            try {
                geoWatchId = navigator.geolocation.watchPosition(
                    function (pos) {
                        kioskLoc = {
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                            accuracy: pos.coords.accuracy,
                            at: Date.now()
                        };
                        updateGpsPill();
                    },
                    function () { updateGpsPill(); },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
                );
            } catch (e) { updateGpsPill(); }
            updateGpsPill();
        }

        // ── 7. PROSES ABSENSI KE BACKEND ─────────────
        function processAttendanceScan(qrValue) {
            if (isProcessing || !qrValue) return;
            // Segarkan status tab dulu (kios bisa idle melewati batas jadwal),
            // tolak langsung bila mode sudah tertutup — tanpa request sia-sia.
            refreshModeTabs();
            const gateMsg = modeBlockedMsg(scanMode);
            if (gateMsg) {
                showHeroResult({ status: false, message: gateMsg }, scanMode);
                return;
            }
            isProcessing = true;
            unlockAudio();

            const filterVal = $('#select-kelas-filter').val();
            const idKelas   = (filterVal === 'guru') ? 0 : (filterVal || 0);

            const kioskToken = (function () { try { return localStorage.getItem('qr_kiosk_token') || ''; } catch (e) { return ''; } })();
            $.ajax({
                url: '<?= site_url("scanner/scan") ?>',
                type: 'POST',
                dataType: 'json',
                headers: kioskToken ? { 'X-Kiosk-Token': kioskToken } : {},
                data: {
                    qr_value: qrValue,
                    mode: scanMode,
                    id_kelas: idKelas,
                    lat: kioskLoc.lat,
                    lng: kioskLoc.lng,
                    accuracy: kioskLoc.accuracy,
                    k: kioskToken || undefined
                }
            }).done(function (res) {
                showHeroResult(res, scanMode);
                loadHistoryFeed();
            }).fail(function (xhr) {
                let msg = 'Gagal memproses presensi.';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.message) msg = r.message;
                } catch(e) {}
                showHeroResult({ status: false, message: msg }, scanMode);
            }).always(function () {
                setTimeout(() => {
                    isProcessing = false;
                    focusBarcodeGunInput();
                }, 1200);
            });
        }

        // ── 7b. PROSES SCAN KARTU RFID ─────────────────
        // Kirim UID kartu sebagai rfid_value (bukan qr_value) agar backend
        // melakukan lookup di kolom nomor_rfid master_siswa / master_guru.
        function processRfidScan(rfidValue) {
            if (isProcessing || !rfidValue) return;
            refreshModeTabs();
            const gateMsg = modeBlockedMsg(scanMode);
            if (gateMsg) {
                showHeroResult({ status: false, message: gateMsg }, scanMode);
                return;
            }
            isProcessing = true;
            unlockAudio();

            const filterVal = $('#select-kelas-filter').val();
            const idKelas   = (filterVal === 'guru') ? 0 : (filterVal || 0);

            const kioskToken = (function () { try { return localStorage.getItem('qr_kiosk_token') || ''; } catch (e) { return ''; } })();
            $.ajax({
                url: '<?= site_url("scanner/scan") ?>',
                type: 'POST',
                dataType: 'json',
                headers: kioskToken ? { 'X-Kiosk-Token': kioskToken } : {},
                data: {
                    rfid_value: rfidValue,
                    mode: scanMode,
                    id_kelas: idKelas,
                    lat: kioskLoc.lat,
                    lng: kioskLoc.lng,
                    accuracy: kioskLoc.accuracy,
                    k: kioskToken || undefined
                }
            }).done(function (res) {
                showHeroResult(res, scanMode);
                loadHistoryFeed();
            }).fail(function (xhr) {
                let msg = 'Gagal memproses kartu RFID.';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.message) msg = r.message;
                } catch(e) {}
                showHeroResult({ status: false, message: msg }, scanMode);
            }).always(function () {
                setTimeout(() => {
                    isProcessing = false;
                    focusBarcodeGunInput();
                }, 1200);
            });
        }

        // ── 7c. PROSES SCAN FINGERPRINT ─────────────────
        // Kirim UID/UserID fingerprint sebagai fingerprint_uid (bukan qr_value)
        // agar backend melakukan lookup di kolom fingerprint_uid.
        function processFpScan(fpUid) {
            if (isProcessing || !fpUid) return;
            refreshModeTabs();
            const gateMsg = modeBlockedMsg(scanMode);
            if (gateMsg) {
                showHeroResult({ status: false, message: gateMsg }, scanMode);
                return;
            }
            isProcessing = true;
            unlockAudio();

            const filterVal = $('#select-kelas-filter').val();
            const idKelas   = (filterVal === 'guru') ? 0 : (filterVal || 0);

            const kioskToken = (function () { try { return localStorage.getItem('qr_kiosk_token') || ''; } catch (e) { return ''; } })();
            $.ajax({
                url: '<?= site_url("scanner/scan") ?>',
                type: 'POST',
                dataType: 'json',
                headers: kioskToken ? { 'X-Kiosk-Token': kioskToken } : {},
                data: {
                    fingerprint_uid: fpUid,
                    mode: scanMode,
                    id_kelas: idKelas,
                    lat: kioskLoc.lat,
                    lng: kioskLoc.lng,
                    accuracy: kioskLoc.accuracy,
                    k: kioskToken || undefined
                }
            }).done(function (res) {
                showHeroResult(res, scanMode);
                loadHistoryFeed();
            }).fail(function (xhr) {
                let msg = 'Gagal memproses fingerprint.';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.message) msg = r.message;
                } catch(e) {}
                showHeroResult({ status: false, message: msg }, scanMode);
            }).always(function () {
                setTimeout(() => {
                    isProcessing = false;
                    focusBarcodeGunInput();
                }, 1200);
            });
        }

        // ── 8. HERO RESULT LIVE CARD ─────────────────
        function showHeroResult(res, currentMode) {
            const ok   = res.status === true;
            const type = res.type || (ok ? (currentMode === 'masuk' ? 'check_in' : 'check_out') : 'error');
            const s    = res.siswa || res.guru || null;
            const isGuru = res.role === 'guru' || !!res.guru;
            const nowTimeStr = witaHM() + ' WITA';

            clearTimeout(heroResetTimer);
            clearInterval(heroInterval);

            // Gerbang jurnal bisa menahan karena sesi belum diisi ATAU jurnalnya
            // sudah ada tapi belum disetujui piket — layarnya harus bedakan.
            const menungguValidasi = !!(res.data && Array.isArray(res.data.sesi_menunggu_validasi) && res.data.sesi_menunggu_validasi.length > 0);

            const configs = {
                check_in: {
                    headerBg: 'bg-gradient-to-r from-emerald-600 to-teal-600',
                    badgeBg: 'bg-emerald-700/60 text-white',
                    badgeTitle: isGuru ? 'ABSEN MASUK GURU BERHASIL' : 'ABSEN MASUK BERHASIL',
                    ringClass: 'hero-ring-masuk',
                    statusBadgeBg: 'bg-emerald-600',
                    statusText: 'Hadir Tepat Waktu',
                    soundType: 'success_in',
                    ttsText: s ? `${formatSpeechName(s.nama)}. Absen masuk.` : 'Absen masuk.',
                },
                check_out: {
                    headerBg: 'bg-gradient-to-r from-amber-500 to-orange-500',
                    badgeBg: 'bg-amber-700/60 text-white',
                    badgeTitle: isGuru ? 'ABSEN PULANG GURU BERHASIL' : 'ABSEN PULANG BERHASIL',
                    ringClass: 'hero-ring-keluar',
                    statusBadgeBg: 'bg-amber-500',
                    statusText: 'Absen Pulang Selesai',
                    soundType: 'success_out',
                    ttsText: s ? `${formatSpeechName(s.nama)}. Absen pulang.` : 'Absen pulang.',
                },
                already_in: {
                    headerBg: 'bg-gradient-to-r from-amber-500 to-yellow-500',
                    badgeBg: 'bg-amber-700/60 text-white',
                    badgeTitle: 'SUDAH ABSEN MASUK',
                    ringClass: 'hero-ring-warning',
                    statusBadgeBg: 'bg-amber-500',
                    statusText: 'Sudah Absen Hari Ini',
                    soundType: 'warning',
                    ttsText: s ? `${formatSpeechName(s.nama)}. Sudah absen masuk.` : 'Sudah absen masuk.',
                },
                already_out: {
                    headerBg: 'bg-gradient-to-r from-amber-500 to-yellow-500',
                    badgeBg: 'bg-amber-700/60 text-white',
                    badgeTitle: 'SUDAH ABSEN PULANG',
                    ringClass: 'hero-ring-warning',
                    statusBadgeBg: 'bg-amber-500',
                    statusText: 'Sudah Absen Keluar',
                    soundType: 'warning',
                    ttsText: s ? `${formatSpeechName(s.nama)}. Sudah absen keluar.` : 'Sudah absen keluar.',
                },
                not_in: {
                    headerBg: 'bg-gradient-to-r from-orange-500 to-orange-600',
                    badgeBg: 'bg-orange-700/60 text-white',
                    badgeTitle: 'BELUM ABSEN MASUK',
                    ringClass: 'hero-ring-warning',
                    statusBadgeBg: 'bg-orange-500',
                    statusText: 'Belum Ada Absen Masuk',
                    soundType: 'warning',
                    ttsText: s ? `${formatSpeechName(s.nama)}. Belum absen masuk.` : 'Belum absen masuk.',
                },
                izin: {
                    headerBg: 'bg-gradient-to-r from-purple-600 to-indigo-600',
                    badgeBg: 'bg-purple-800/60 text-white',
                    badgeTitle: 'STATUS IZIN / SAKIT',
                    ringClass: 'hero-ring-warning',
                    statusBadgeBg: 'bg-purple-600',
                    statusText: 'Izin Resmi',
                    soundType: 'warning',
                    ttsText: s ? `${formatSpeechName(s.nama)}. Berstatus izin.` : 'Status izin.',
                },
                jurnal_kurang: {
                    headerBg: 'bg-gradient-to-r from-rose-600 to-orange-600',
                    badgeBg: 'bg-rose-800/60 text-white',
                    badgeTitle: menungguValidasi ? 'JURNAL MENUNGGU VALIDASI PIKET' : 'JURNAL MENGAJAR BELUM LENGKAP',
                    ringClass: 'hero-ring-error',
                    statusBadgeBg: 'bg-rose-600',
                    statusText: 'Absen Pulang Ditolak',
                    soundType: 'warning',
                    // Sengaja ringkas: res.message memuat daftar sesi yang
                    // panjang dan tidak habis dibacakan lewat TTS.
                    ttsText: s
                        ? `${formatSpeechName(s.nama)}. ${menungguValidasi ? 'Jurnal menunggu validasi piket.' : 'Jurnal mengajar belum lengkap.'}`
                        : (menungguValidasi ? 'Jurnal menunggu validasi piket.' : 'Jurnal mengajar belum lengkap.'),
                },
                error: {
                    headerBg: 'bg-gradient-to-r from-red-600 to-red-700',
                    badgeBg: 'bg-red-800/60 text-white',
                    badgeTitle: 'PRESENSI GAGAL',
                    ringClass: 'hero-ring-error',
                    statusBadgeBg: 'bg-red-600',
                    statusText: 'Data Gagal Diproses',
                    soundType: 'error',
                    ttsText: res.message ? res.message : 'Presensi tidak berhasil.',
                }
            };

            const cfg = configs[type] || configs.error;

            // Siswa telat: jangan tampilkan "Hadir Tepat Waktu" — server
            // mengirim flag telat + menit untuk check_in.
            if (type === 'check_in' && res.telat) {
                cfg.statusText = 'Telat ' + (res.telat_menit || 0) + ' Menit';
                cfg.statusBadgeBg = 'bg-orange-500';
                cfg.badgeTitle = isGuru ? 'ABSEN MASUK GURU (TELAT)' : 'ABSEN MASUK (TELAT)';
            }

            playChime(cfg.soundType);
            speakText(cfg.ttsText);

            const $banner = $('#hero-header-banner');
            $banner.attr('class', `p-3.5 text-white text-center transition-colors duration-300 border-b border-white/10 ${cfg.headerBg}`);
            $('#hero-badge-title').attr('class', `inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1 ${cfg.badgeBg}`).text(cfg.badgeTitle);
            $('#hero-main-message').text(res.message || 'Presensi berhasil dicatat');

            const $foto = $('#hero-siswa-foto');
            $foto.removeClass('hero-ring-masuk hero-ring-keluar hero-ring-warning hero-ring-error rfid-pulse').addClass(cfg.ringClass);
            $('#hero-status-pill').attr('class', `absolute -bottom-2 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase text-white shadow-sm border border-white/20 whitespace-nowrap ${cfg.statusBadgeBg}`);

            // Tampilkan Badge Metode (RFID, QR, FP) jika scan berhasil atau dikenali
            const $metodeBadge = $('#hero-metode-badge');
            const metode = res.metode || (rfidMode ? 'rfid' : (fpMode ? 'fp' : 'qr'));
            if (ok && metode) {
                if (metode === 'rfid') {
                    $metodeBadge.removeClass('hidden badge-metode-qr badge-metode-fp')
                                .addClass('badge-metode-rfid')
                                .html('<i class="bi bi-credit-card-2-front mr-1"></i> RFID').show();
                    $foto.addClass('rfid-pulse');
                } else if (metode === 'fp' || metode === 'fingerprint') {
                    $metodeBadge.removeClass('hidden badge-metode-rfid badge-metode-qr')
                                .addClass('badge-metode-fp')
                                .html('<i class="bi bi-fingerprint mr-1"></i> FINGERPRINT').show();
                } else {
                    $metodeBadge.removeClass('hidden badge-metode-rfid badge-metode-fp')
                                .addClass('badge-metode-qr')
                                .html('<i class="bi bi-qr-code mr-1"></i> QR CODE').show();
                }
            } else {
                $metodeBadge.addClass('hidden').hide();
            }

            if (s) {
                $foto.attr('src', s.foto || FOTO_CADANGAN);
                $('#hero-siswa-nama').text(s.nama);
                if (isGuru) {
                    $('#hero-siswa-kelas').empty()
                        .append('<i class="bi bi-person-badge text-purple-400"></i> ')
                        .append(document.createTextNode(s.jabatan || 'GURU / GTK')).show();
                    $('#hero-siswa-nis').text(s.nip ? `NIP: ${s.nip}` : (s.kode_guru ? `Kode: ${s.kode_guru}` : 'GURU')).show();
                } else {
                    $('#hero-siswa-kelas').empty()
                        .append('<i class="bi bi-building text-slate-400"></i> ')
                        .append(document.createTextNode(s.nama_kelas || 'Kelas')).show();
                    $('#hero-siswa-nis').text(s.nis ? `NIS: ${s.nis}` : (s.nisn ? `NISN: ${s.nisn}` : '-')).show();
                }
                $('#hero-status-pill').text(s.status || (currentMode === 'masuk' ? 'HADIR' : 'PULANG'));

                const recordedTime = (currentMode === 'keluar' ? s.jam_keluar : s.jam_masuk) || nowTimeStr;
                $('#hero-time-text').text(recordedTime);
            } else {
                $foto.attr('src', FOTO_PERINGATAN);
                $('#hero-siswa-nama').text('Peringatan Presensi');
                $('#hero-siswa-kelas').hide();
                $('#hero-siswa-nis').hide();
                $('#hero-status-pill').text('PERHATIAN');
                $('#hero-time-text').text(nowTimeStr);
            }

            // Countdown timer animation (3 detik)
            const totalDuration = 3000;
            const intervalStep  = 50;
            let elapsed = 0;
            $('#hero-countdown-bar').css('width', '100%');

            heroInterval = setInterval(() => {
                elapsed += intervalStep;
                const remainingPct = Math.max(0, 100 - ((elapsed / totalDuration) * 100));
                $('#hero-countdown-bar').css('width', `${remainingPct}%`);
                if (elapsed >= totalDuration) {
                    clearInterval(heroInterval);
                }
            }, intervalStep);

            heroResetTimer = setTimeout(() => {
                resetHeroStandby();
            }, totalDuration);
        }

        function resetHeroStandby() {
            $('#hero-header-banner').attr('class', 'p-3.5 bg-gradient-to-r from-slate-900 to-slate-950 text-white text-center transition-colors duration-300 border-b border-white/10');
            $('#hero-badge-title').attr('class', 'inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/15 text-slate-200').text('STANDBY SCANNER');
            $('#hero-metode-badge').addClass('hidden').hide();
            $('#hero-main-message').text('Silakan Scan QR / RFID / Sidik Jari');

            $('#hero-siswa-foto').attr('src', FOTO_STANDBY)
                .removeClass('hero-ring-masuk hero-ring-keluar hero-ring-warning hero-ring-error rfid-pulse').addClass('hero-ring-masuk');
            $('#hero-status-pill').attr('class', 'absolute -bottom-2 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase text-white shadow-sm border border-white/20 bg-slate-800 whitespace-nowrap').text('STANDBY');
            $('#hero-siswa-nama').text('Menunggu Scan...');
            $('#hero-siswa-kelas').html('<i class="bi bi-person-badge text-slate-400"></i> Kategori').show();
            $('#hero-siswa-nis').text('ID: -').show();
            $('#hero-time-text').text('--:-- WITA');
            $('#hero-countdown-bar').css('width', '100%');
        }

        // ── 9. USB BARCODE SCANNER GUN & AUTO-FOCUS ──
        function focusBarcodeGunInput() {
            setTimeout(() => {
                $('#input-barcode-scanner').focus();
            }, 100);
        }

        window.submitBarcodeGunInput = function () {
            const val = $('#input-barcode-scanner').val().trim();
            if (!val) return;
            $('#input-barcode-scanner').val('');
            // Mode Fingerprint: kirim sebagai fingerprint_uid agar backend
            // melakukan lookup di kolom fingerprint_uid master_siswa / master_guru.
            if (fpMode) {
                processFpScan(val);
            }
            // Mode RFID: kirim sebagai rfid_value.
            else if (rfidMode) {
                processRfidScan(val);
            } else {
                processAttendanceScan(val);
            }
        };

        $('#input-barcode-scanner').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.submitBarcodeGunInput();
            }
        });

        // ── 10. REALTIME ATTENDANCE FEED & STATS ─────
        window.loadHistoryFeed = function () {
            const filterVal  = $('#select-kelas-filter').val();
            const idKelas    = (filterVal === 'guru') ? 0 : (filterVal || 0);
            const filterRole = (filterVal === 'guru') ? 'guru' : '';

            const kioskTokenH = (function () { try { return localStorage.getItem('qr_kiosk_token') || ''; } catch (e) { return ''; } })();
            $.ajax({
                url: '<?= site_url("scanner/history") ?>',
                type: 'GET',
                dataType: 'json',
                headers: kioskTokenH ? { 'X-Kiosk-Token': kioskTokenH } : {},
                data: {
                    id_kelas: idKelas,
                    filter_role: filterRole,
                    limit: 30,
                    k: kioskTokenH || undefined
                }
            }).done(function (res) {
                if (res.status && res.data) {
                    renderHistoryRows(res.data);
                }
                if (res.stats) {
                    $('#stat-total-hadir').text(res.stats.hadir || 0);
                    $('#stat-masuk').text(res.stats.masuk || 0);
                    $('#stat-pulang').text(res.stats.keluar || 0);
                    $('#stat-tercatat').text(res.stats.total || 0);
                }
            });
        };

        // Escape HTML untuk data server (nama siswa/guru bisa mengandung karakter khusus)
        function escHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function renderHistoryRows(list) {
            const $container = $('#live-history-feed');
            if (!list || list.length === 0) {
                $container.html(`
                    <div class="py-8 text-center text-slate-500 text-xs">
                        <i class="bi bi-inbox text-2xl text-slate-600 block mb-1"></i>
                        Belum ada yang melakukan absensi hari ini.
                    </div>
                `);
                return;
            }

            const statusCls = function (st) {
                if (st === 'HADIR' || st === 'ONTIME') return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
                if (st === 'SAKIT' || st === 'IZIN') return 'bg-sky-500/20 text-sky-300 border-sky-500/30';
                if (st === 'LATE') return 'bg-amber-500/20 text-amber-300 border-amber-500/30';
                if (st === 'ALPA') return 'bg-rose-500/20 text-rose-300 border-rose-500/30';
                if (st === 'BDR') return 'bg-teal-500/20 text-teal-300 border-teal-500/30';
                return 'bg-slate-800 text-slate-300 border-slate-700';
            };

            let html = '';
            list.forEach(item => {
                const nama    = escHtml(item.nama || '-');
                const isGuru  = (item.role === 'guru') || (item.nama_kelas === 'GURU / GTK');
                const idLabel = isGuru ? 'NIP' : 'NIS';
                const idVal   = escHtml(item.nis || '-');
                const kelas   = isGuru ? 'GURU / GTK' : escHtml(item.nama_kelas || 'Kelas');
                const st      = escHtml(item.status || '-');
                const foto    = escHtml(item.foto || '');
                const jm      = escHtml(item.jam_masuk ? String(item.jam_masuk).substr(0, 5) : '--:--');
                const jk      = item.jam_keluar ? ' &rarr; ' + escHtml(String(item.jam_keluar).substr(0, 5)) : '';
                const badgeClass = isGuru
                    ? 'bg-purple-500/20 text-purple-300 border-purple-500/30'
                    : statusCls(item.status);

                html += `
                    <div class="py-2.5 flex items-center justify-between gap-3 transition-colors hover:bg-slate-800 px-1 rounded-xl">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img src="${foto}" class="w-9 h-9 rounded-xl object-cover border border-slate-700 shrink-0" alt="Avatar" loading="lazy" onerror="this.style.visibility='hidden'">
                            <div class="min-w-0">
                                <p class="font-bold text-xs text-white m-0 truncate">${nama}</p>
                                <p class="text-[10px] text-slate-400 m-0 truncate">
                                    <span class="${isGuru ? 'text-purple-400 font-bold' : ''}">${kelas}</span> &middot; ${idLabel}: ${idVal}
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold border ${badgeClass}">
                                ${isGuru ? 'GURU' : st}
                            </span>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                ${jm}${jk}
                            </div>
                        </div>
                    </div>
                `;
            });

            $container.html(html);
        }

        // Auto-refresh feed & stats setiap 30 detik
        setInterval(loadHistoryFeed, 30000);

        // Delegated/static bindings (ganti onclick inline)
        $('#select-kelas-filter').on('change', loadHistoryFeed);
        $('#btn-toggle-fullscreen').on('click', toggleKioskFullscreen);
        $('#btn-open-audio').on('click', openAudioSettings);
        $('#btn-close-audio').on('click', closeAudioSettings);
        $('[data-scanmode]').on('click', function () { setScanMode($(this).data('scanmode')); });
        $('#select-video-device').on('change', function () { switchCameraDevice(this.value); });
        $('#btn-flip-cam').on('click', cycleFacingMode);
        $('#btn-desktop-torch').on('click', toggleTorchDesktop);
        $('#btn-desktop-pause').on('click', toggleCameraPause);
        $('#btn-retry-cam').on('click', initDesktopCamera);
        $('#btn-barcode-proses').on('click', submitBarcodeGunInput);
        $('#btn-toggle-rfid-mode').on('click', toggleRfidMode);
        $('#btn-toggle-fp-mode').on('click', toggleFpMode);
        $('#btn-refresh-feed').on('click', loadHistoryFeed);
        $('#setting-sound-chime').on('change', function () { toggleSoundSetting(this.checked); });
        $('#setting-voice-tts').on('change', function () { toggleVoiceSetting(this.checked); });
        $('[data-soundtest]').on('click', function () { testDesktopSound($(this).data('soundtest')); });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeAudioSettings();
        });

        // Initial launch
        $(document).ready(function () {
            initDesktopCamera();
            startKioskGpsWatch();
            loadHistoryFeed();
            focusBarcodeGunInput();
        });

    })();
    </script>
</body>
</html>
