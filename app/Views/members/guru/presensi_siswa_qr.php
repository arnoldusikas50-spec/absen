<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Ikon di dalam field: kunci padding-kiri via ID (spesifisitas 1-1-0)
       agar teks tak menumpuk ikon walau shorthand .m3-input menimpa .pl-* */
    #select-kelas.m3-input,
    #input-search-history.m3-input { padding-left: 2.25rem; }
    #manual-nis-input.m3-input { padding-left: 2.5rem; }
    /* ── Hero section (Consistent with /presensi) ─────────── */
    .hero-section {
        color: #fff; position: relative;
        overflow: hidden; border-radius: 0 0 36px 36px;
        background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-600) 35%, var(--brand-500) 65%, #f59e0b 100%);
        padding: calc(20px + var(--safe-area-inset-top, 0px)) 20px 48px;
        box-shadow: 0 12px 36px rgb(var(--brand-600-rgb) / .22);
    }
    .hero-decorations { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 1; }
    .hero-decor-svg   { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
    .hero-pattern-dots {
        position: absolute; top: 0; right: 0; pointer-events: none;
        width: 150px; height: 130px;
        background-image: radial-gradient(rgba(255,255,255,.22) 1.5px, transparent 1.5px);
        background-size: 12px 12px; opacity: .7;
        mask-image: radial-gradient(circle at 100% 0%, black, transparent 80%);
        -webkit-mask-image: radial-gradient(circle at 100% 0%, black, transparent 80%);
    }
    .hero-sparkle {
        position: absolute; color: #fff; line-height: 1; user-select: none;
        font-size: 14px;
        filter: drop-shadow(0 0 6px rgba(255,255,255,.9));
        animation: heroSparkleFloat 4s ease-in-out infinite alternate;
    }
    .hero-sparkle.spark-1 { top:16%; right:26%; font-size:14px; opacity:.9;  animation-delay:0s; }
    .hero-sparkle.spark-2 { top:58%; right:12%; font-size:11px; opacity:.65; animation-delay:1.5s; }
    .hero-sparkle.spark-3 { top:22%; left:48%; font-size:13px; opacity:.8;  animation-delay:2.3s; }
    @keyframes heroSparkleFloat {
        0%   { transform: translateY(0) scale(.9); opacity: .4; }
        100% { transform: translateY(-5px) scale(1.15); opacity: 1; }
    }
    .hero-bubble {
        position: absolute; border-radius: 9999px;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(4px);
    }
    .hero-bubble.bubble-1 { width:46px; height:46px; top:8%; left:50%; animation: bubbleFloat 6s ease-in-out infinite alternate; }
    .hero-bubble.bubble-2 { width:30px; height:30px; bottom:20%; right:22%; animation: bubbleFloat 5s ease-in-out infinite alternate-reverse; }
    @keyframes bubbleFloat { 0% { transform: translate(0,0); } 100% { transform: translate(4px,-6px); } }

    .user-profile { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; position: relative; z-index: 10; }
    .avatar-wrapper { position: relative; }
    .avatar-main {
        object-fit: cover;
        width: 52px; height: 52px;
        border-radius: 18px;
        border: 2.5px solid rgba(255,255,255,.85);
        box-shadow: 0 6px 16px rgba(0,0,0,.15);
        background: #fff;
    }
    .greeting-text h4  { margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -.5px; text-shadow: 0 1px 3px rgba(0,0,0,.1); }
    .greeting-text p   { margin: 0; font-weight: 500; opacity: .9; font-size: 12.5px; letter-spacing: .1px; }
    .motivation-box {
        display: inline-flex; align-items: center;
        font-weight: 500; color: #fff;
        border-radius: 14px; padding: 6px 14px;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,.28);
        font-size: 11px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }

    /* ── Floating Status Card (Consistent with /presensi) ─ */
    .status-card {
        background: #fff; border-radius: 26px;
        position: relative; z-index: 20;
        padding: 18px 18px 14px;
        margin: -38px 16px 0;
        box-shadow: 0 12px 32px rgba(0,0,0,.07);
        border: 1px solid rgba(226,232,240,.9);
    }
    .clock-display { text-align: center; padding-top: 2px; padding-bottom: 8px; }
    #digitalClock {
        font-weight: 800; font-variant-numeric: tabular-nums;
        line-height: 1.2; margin-bottom: 4px;
        font-size: 34px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-700) 100%);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -1px;
    }
    #digitalDate {
        display: inline-flex; align-items: center;
        font-weight: 600; background: #f8fafc;
        border-radius: 9999px; padding: 3px 12px;
        text-transform: capitalize; font-size: 11.5px;
        color: #64748b; border: 1px solid #f1f5f9;
    }
    /* Jam di kartu ini mengikuti jam server, bukan jam perangkat — labelnya jujur. */
    .clock-tz {
        display: block; margin: -2px 0 6px;
        font-size: 10.5px; font-weight: 700; letter-spacing: 1.4px;
        color: #94a3b8;
    }
    .mini-status-grid {
        display: grid; gap: 8px; margin-top: 10px; padding-top: 10px;
        grid-template-columns: 1fr 1fr 1fr;
        border-top: 1px dashed #e2e8f0;
    }
    .mini-status-box {
        padding: 8px 4px; border-radius: 14px; text-align: center;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        border: 1px solid;
    }
    .mini-status-box.box-masuk  { background: #f0fdf4; border-color: #dcfce7; }
    .mini-status-box.box-pulang { background: #fffbeb; border-color: #fef3c7; }
    .mini-status-box.box-status { background: var(--primary-50); border-color: var(--primary-100); }
    .mini-status-box .box-title { font-weight: 700; text-transform: uppercase; margin-bottom: 2px; font-size: 9.5px; letter-spacing: .3px; }
    .mini-status-box.box-masuk  .box-title { color: #16a34a; }
    .mini-status-box.box-pulang .box-title { color: #d97706; }
    .mini-status-box.box-status .box-title { color: var(--primary-600); }
    .mini-status-box .box-value  { font-weight: 800; color: #1e293b; font-size: 15px; letter-spacing: -.2px; }

    /* ── Mode selector pills ────────────────────────── */
    .mode-container {
        background: #f1f5f9; padding: 4px;
        border-radius: 18px; display: flex; gap: 4px;
    }
    .mode-tab-btn {
        flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 12px; border-radius: 14px; font-size: 13px; font-weight: 700;
        border: none; cursor: pointer; transition: all 0.2s cubic-bezier(.4,0,.2,1);
        color: #64748b; background: transparent;
    }
    .mode-tab-btn:active { transform: scale(0.97); }
    .mode-tab-btn.active-masuk {
        background: #10b981; color: #fff;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    }
    .mode-tab-btn.active-keluar {
        background: #f59e0b; color: #fff;
        box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
    }
    .mode-tab-btn:disabled {
        opacity: .45; cursor: not-allowed; box-shadow: none;
    }
    .mode-hint {
        font-size: 10.5px; color: #94a3b8; text-align: center;
        margin: -6px 0 12px; font-weight: 600;
    }

    /* Chip mode pada dialog NIS manual. Dulu memakai utility has-[:checked]
       (sintaks v4) yang tidak pernah ter-build, jadi mode terpilih maupun mode
       terkunci sama sekali tidak terlihat beda. */
    .mode-chip { cursor: pointer; transition: background-color .15s ease, border-color .15s ease, color .15s ease; }
    .mode-opt:checked + .mode-chip-masuk  { background: #ecfdf5; border-color: #10b981; color: #047857; }
    .mode-opt:checked + .mode-chip-keluar { background: #fffbeb; border-color: #f59e0b; color: #b45309; }
    .mode-opt:disabled + .mode-chip       { opacity: .45; cursor: not-allowed; }
    .mode-opt:focus-visible + .mode-chip  { outline: 2px solid var(--brand-600); outline-offset: 2px; }

    /* ── Scanner Viewport Card ──────────────────────── */
    .scanner-card {
        background: #fff; border-radius: 26px;
        padding: 16px; margin: 16px;
        box-shadow: 0 10px 28px rgba(0,0,0,.05);
        border: 1px solid rgba(226,232,240,.9);
    }
    .camera-frame {
        width: 100%; position: relative; background: #090d16;
        border-radius: 22px; overflow: hidden;
        aspect-ratio: 1 / 1;
        box-shadow: inset 0 0 24px rgba(0,0,0,.8), 0 8px 24px rgba(0,0,0,.15);
        border: 2px solid rgba(255,255,255,.08);
    }
    #qr-reader { width: 100% !important; height: 100% !important; border: none !important; }
    #qr-reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; border-radius: 20px; }
    #qr-reader img   { display: none !important; }
    #qr-reader__scan_region { border: none !important; }
    #qr-reader__header_message,
    #qr-reader__status_span,
    #qr-reader__filescan_input,
    #qr-reader__dashboard_section_csr button { display: none !important; }

    /* Corner brackets */
    .scanner-corner {
        position: absolute; width: 32px; height: 32px;
        border-color: #10b981; border-style: solid;
        pointer-events: none; z-index: 6; transition: border-color 0.25s;
    }
    .scanner-corner-tl { top: 16px; left: 16px; border-width: 4px 0 0 4px; border-radius: 8px 0 0 0; }
    .scanner-corner-tr { top: 16px; right: 16px; border-width: 4px 4px 0 0; border-radius: 0 8px 0 0; }
    .scanner-corner-bl { bottom: 16px; left: 16px; border-width: 0 0 4px 4px; border-radius: 0 0 0 8px; }
    .scanner-corner-br { bottom: 16px; right: 16px; border-width: 0 4px 4px 0; border-radius: 0 0 8px 0; }
    .mode-keluar-active .scanner-corner { border-color: #f59e0b; }

    /* Laser scan line */
    .scanner-laser {
        position: absolute; left: 8%; right: 8%; height: 3px;
        background: linear-gradient(90deg, transparent, #10b981 30%, #34d399 50%, #10b981 70%, transparent);
        box-shadow: 0 0 12px #10b981, 0 0 24px rgba(16,185,129,0.6);
        border-radius: 9999px; z-index: 5; pointer-events: none;
        animation: laserScan 2.4s ease-in-out infinite;
    }
    .mode-keluar-active .scanner-laser {
        background: linear-gradient(90deg, transparent, #f59e0b 30%, #fbbf24 50%, #f59e0b 70%, transparent);
        box-shadow: 0 0 12px #f59e0b, 0 0 24px rgba(245,158,11,0.6);
    }
    @keyframes laserScan {
        0%   { top: 12%; opacity: 0.9; }
        50%  { top: 86%; opacity: 0.9; }
        100% { top: 12%; opacity: 0.9; }
    }

    /* Floating camera controls over viewport */
    .camera-hud-top {
        position: absolute; top: 12px; left: 12px; right: 12px;
        display: flex; justify-content: space-between; align-items: center;
        z-index: 35; pointer-events: auto;
    }
    .hud-btn {
        width: 38px; height: 38px; border-radius: 12px;
        background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1.5px solid rgba(255, 255, 255, 0.3);
        color: #fff; display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.2s ease; font-size: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }
    @media (hover: hover) { .hud-btn:hover { background: rgba(30, 41, 59, 0.9); border-color: rgba(255, 255, 255, 0.5); } }
    .hud-btn:active { transform: scale(0.92); }
    .hud-btn.active { background: var(--brand-600); border-color: var(--brand-500); color: #fff; }
    .hud-btn.sound-muted { background: rgb(var(--brand-600-rgb) / 0.85); border-color: rgba(255, 255, 255, 0.4); }

    .camera-hud-bottom {
        position: absolute; bottom: 10px; left: 12px; right: 12px;
        display: flex; justify-content: center; align-items: center;
        z-index: 10; pointer-events: none;
    }
    .hud-hint-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(15,23,42,0.72); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 9999px; padding: 5px 14px;
        color: #f8fafc; font-size: 11px; font-weight: 600;
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    }

    /* ── Scan Result Animation ──────────────────────── */
    #scan-result-container {
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
    }
    @keyframes resultSlideIn {
        from { opacity: 0; transform: translateY(-10px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .result-animated { animation: resultSlideIn 0.3s ease-out; }

    /* ── Attendance Scan Popup Modal ─────────────────── */
    #modal-scan-popup .tw-modal-box {
        animation: popupSpring 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    }
    @keyframes popupSpring {
        0%   { transform: scale(0.82) translateY(24px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    .popup-ring-masuk {
        box-shadow: 0 0 0 4px #10b981, 0 10px 25px -5px rgba(16, 185, 129, 0.45);
    }
    .popup-ring-keluar {
        box-shadow: 0 0 0 4px #f59e0b, 0 10px 25px -5px rgba(245, 158, 11, 0.45);
    }
    .popup-ring-warning {
        box-shadow: 0 0 0 4px #eab308, 0 10px 25px -5px rgba(234, 179, 8, 0.45);
    }
    .popup-ring-error {
        box-shadow: 0 0 0 4px var(--brand-500), 0 10px 25px -5px rgb(var(--brand-500-rgb) / 0.45);
    }

    /* ── Activity cards (Consistent with /presensi) ─── */
    .section-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 12px;
    }
    .section-header h5 { margin: 0; font-weight: 700; font-size: 16px; color: #1e293b; }

    .activity-card {
        border-radius: 1rem; padding: 12px 14px; margin-bottom: 8px;
        display: flex; align-items: center; gap: 12px;
        transition: transform 0.12s, background 0.12s;
        background: #fff;
        border: 1px solid rgba(226,232,240,0.85);
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .activity-card:active { transform: scale(0.99); background: #f8fafc; }
    .activity-avatar {
        width: 44px; height: 44px; border-radius: 14px;
        object-fit: cover; flex-shrink: 0;
        border: 1.5px solid #e2e8f0;
        background: #f1f5f9;
    }
    .activity-info h6 { margin: 0; font-weight: 700; font-size: 13.5px; color: #0f172a; line-height: 1.3; }
    .activity-info p  { margin: 2px 0 0; font-size: 11px; color: #64748b; font-weight: 500; }
    .activity-time-wrap { margin-left: auto; text-align: right; flex-shrink: 0; }

    /* ── Skeleton ──────────────────────────────────── */
    .skel-item {
        background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skelShimmer 1.4s infinite;
        border-radius: 8px;
    }
    @keyframes skelShimmer { to { background-position: -200% 0; } }

    /* ── Bottom Nav (mengikuti gaya floating pill mobile_tw) ── */
    .bottom-nav { display: none !important; } /* Hide default so our tuned QR bar displays */
    .custom-bottom-bar {
        position: fixed; bottom: calc(10px + var(--safe-area-inset-bottom, 0px) * 0.5); left: 50%; transform: translateX(-50%);
        width: calc(100% - 24px); max-width: 456px; z-index: 1000;
        display: flex; justify-content: space-around; align-items: center;
        padding: 0 6px;
        height: 64px;
        border-radius: 24px;
        background: rgba(255,255,255,0.82);
        backdrop-filter: blur(22px) saturate(180%); -webkit-backdrop-filter: blur(22px) saturate(180%);
        border: 1px solid rgba(255,255,255,0.7);
        box-shadow: 0 24px 48px -24px rgba(15,23,42,0.35), 0 2px 10px rgba(15,23,42,0.06), inset 0 1px 0 rgba(255,255,255,0.9);
    }
    /* Status aktif, chip, dan ikon SVG mengikuti gaya layout (mobile_tw) */
    .app-container {
        padding-bottom: calc(92px + var(--safe-area-inset-bottom, 0px) * 0.5) !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$hariList   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulanList  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tanggalIndo = $hariList[date('w')] . ', ' . (int)date('d') . ' ' . $bulanList[(int)date('m')] . ' ' . date('Y');

$hour = (int)date('H');
$greetTime = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$schoolName = $setting->nama_sekolah ?? ($setting->sekolah ?? 'Madrasah / Sekolah');

// Avatar info
$avatarSrc = \App\Services\AvatarService::url($user->foto ?? '')
    ?? \App\Services\AvatarService::url($setting->logo ?? '')
    ?? \App\Services\AvatarService::inisial($namaGuru, 128, '#e11d48');

$csrfField = csrf_token();
$csrfHash  = csrf_hash();
?>

<!-- ═══════════════════════════════════════════════ -->
<!-- Hero Section (Consistent with /presensi)       -->
<!-- ═══════════════════════════════════════════════ -->
<div class="hero-section">
    <!-- Graphic Decorations -->
    <div class="hero-decorations" aria-hidden="true">
        <svg class="hero-decor-svg" viewBox="0 0 500 220" preserveAspectRatio="none">
            <path d="M0,70 C160,150 340,10 500,60 L500,0 L0,0 Z" fill="rgba(255,255,255,0.08)" />
            <path d="M0,130 C190,50 310,160 500,95 L500,0 L0,0 Z" fill="rgba(255,255,255,0.05)" />
            <circle cx="440" cy="30" r="55" fill="rgba(255,255,255,0.12)" />
            <circle cx="70" cy="180" r="75" fill="rgba(251,191,36,0.18)" />
        </svg>
        <div class="hero-pattern-dots"></div>
        <div class="hero-bubble bubble-1"></div>
        <div class="hero-bubble bubble-2"></div>
        <div class="hero-sparkle spark-1">✦</div>
        <div class="hero-sparkle spark-2">✦</div>
        <div class="hero-sparkle spark-3">✨</div>
    </div>

    <!-- User / App Profile Header -->
    <div class="user-profile">
        <div class="greeting-text">
            <p class="mb-0.5"><?= $greetTime ?>,</p>
            <h4>Presensi Scanner <span class="text-yellow-300">✦</span></h4>
            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/20 text-white text-[9px] font-medium backdrop-blur-sm">
                    <i class="bi bi-person-badge"></i> <?= esc($namaGuru) ?>
                </span>
                <?php if (!empty($petugas_label)): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full <?= !empty($is_admin) ? 'bg-amber-400 text-amber-950 font-bold' : 'bg-emerald-500 text-white font-semibold' ?> text-[9px] shadow-sm">
                    <i class="bi <?= !empty($is_admin) ? 'bi-shield-lock-fill' : 'bi-shield-check' ?>"></i> <?= esc($petugas_label) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="avatar-wrapper">
            <?php if (!empty($isLoggedIn)): ?>
            <a href="<?= site_url('presensi/profile') ?>" class="block relative no-underline">
                <img src="<?= $avatarSrc ?>" alt="Avatar" class="avatar-main">
                <span class="absolute bottom-0 end-0 rounded-full" style="width: 13px; height: 13px; background: #10b981; border: 2.5px solid #fff; margin-bottom: 2px; margin-right: 2px;"></span>
            </a>
            <?php else: ?>
            <a href="<?= site_url('auth') ?>" title="Klik untuk Login GTK" class="block relative no-underline">
                <img src="<?= $avatarSrc ?>" alt="Logo" class="avatar-main">
                <span class="absolute bottom-0 end-0 rounded-full" style="width: 13px; height: 13px; background: #f59e0b; border: 2.5px solid #fff; margin-bottom: 2px; margin-right: 2px;" title="Mode Publik"></span>
            </a>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Floating Status & Clock Card                   -->
<!-- ═══════════════════════════════════════════════ -->
<div class="status-card">
    <div class="clock-display">
        <div id="digitalClock"><?= date('H:i:s') ?></div>
        <span class="clock-tz">WITA</span>
        <div id="digitalDate">
            <i class="bi bi-calendar2-day text-rose-600 me-1"></i> <?= $tanggalIndo ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Scanner Card Component                         -->
<!-- ═══════════════════════════════════════════════ -->
<div class="scanner-card" id="scanner-section">
    <!-- Mode Toggle Segmented Pills -->
    <?php
    $modeTab    = $mode_tab ?? ['masuk_open' => true, 'pulang_open' => true, 'default_mode' => 'masuk', 'buka_masuk' => '', 'tutup_masuk' => '', 'jam_pulang' => '', 'masuk_msg' => '', 'pulang_msg' => ''];
    $masukOpen  = (bool) ($modeTab['masuk_open'] ?? true);
    $pulangOpen = (bool) ($modeTab['pulang_open'] ?? true);
    $defMode    = ($modeTab['default_mode'] ?? 'masuk') === 'keluar' ? 'keluar' : 'masuk';
    ?>
    <div class="mode-container mb-3.5">
        <button id="btn-mode-masuk" type="button" onclick="setMode('masuk')"
            class="mode-tab-btn <?= $defMode === 'masuk' ? 'active-masuk' : '' ?>"
            <?= $masukOpen ? '' : 'disabled title="' . esc($modeTab['masuk_msg'] ?? 'Di luar jadwal absen masuk') . '"' ?>>
            <i class="bi bi-box-arrow-in-right text-base"></i>
            <span>Absen Masuk</span>
        </button>
        <button id="btn-mode-keluar" type="button" onclick="setMode('keluar')"
            class="mode-tab-btn <?= $defMode === 'keluar' ? 'active-keluar' : '' ?>"
            <?= $pulangOpen ? '' : 'disabled title="' . esc($modeTab['pulang_msg'] ?? 'Belum waktunya pulang') . '"' ?>>
            <i class="bi bi-box-arrow-right text-base"></i>
            <span>Absen Pulang</span>
        </button>
    </div>
    <?php if (!empty($modeTab['buka_masuk']) || !empty($modeTab['jam_pulang'])): ?>
    <p class="mode-hint" id="mode-hint">
        Masuk <?= esc(($modeTab['buka_masuk'] ?? '') . '–' . ($modeTab['tutup_masuk'] ?? '')) ?>
        &middot; Pulang &ge;<?= esc(($modeTab['buka_pulang'] ?? $modeTab['jam_pulang'] ?? '')) ?> WITA
    </p>
    <?php endif; ?>

    <!-- Filter Kelas & Input Manual NIS (disembunyikan: sudah ada di footer menu) -->
    <div class="flex items-center gap-2 mb-3" style="display:none !important;">
        <?php if (!empty($kelasList)): ?>
        <div class="relative flex-1">
            <i class="bi bi-building absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            <select id="select-kelas" class="m3-input pl-8 text-xs py-2 w-full">
                <option value="">Semua Kelas (Otomatis)</option>
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= (int)$k->id_kelas ?>" <?= ($selectedKelas == $k->id_kelas) ? 'selected' : '' ?>>
                    <?= esc($k->nama_kelas) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="button" onclick="openManualModal()"
            class="py-2 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 active:scale-95 text-slate-700 font-bold text-xs flex items-center gap-1.5 shadow-xs transition-all <?= empty($kelasList) ? 'w-full justify-center' : 'shrink-0' ?>"
            title="Ketik NIS/NISN Manual jika QR bermasalah">
            <i class="bi bi-keyboard text-rose-600 text-sm"></i>
            <span>Ketik NIS</span>
        </button>
    </div>

    <!-- Camera Viewport Frame -->
    <div class="camera-frame relative" id="camera-frame-box">
        <div id="qr-reader"></div>

        <!-- Neon Corner Brackets -->
        <div class="scanner-corner scanner-corner-tl"></div>
        <div class="scanner-corner scanner-corner-tr"></div>
        <div class="scanner-corner scanner-corner-bl"></div>
        <div class="scanner-corner scanner-corner-br"></div>

        <!-- Laser Scanning Line -->
        <div class="scanner-laser" id="scanner-laser"></div>

        <!-- Top Camera Toolbar HUD -->
        <div class="camera-hud-top">
            <button type="button" class="hud-btn" id="btn-switch-cam" onclick="switchCamera()" title="Ganti Kamera Depan / Belakang">
                <i class="bi bi-arrow-repeat"></i>
            </button>
            <div class="flex items-center gap-2">
                <button type="button" class="hud-btn" id="btn-toggle-torch" onclick="toggleTorch()" title="Nyalakan / Matikan Senter">
                    <i class="bi bi-lightning-charge-fill" id="icon-hud-torch"></i>
                </button>
                <button type="button" class="hud-btn" id="btn-toggle-sound" onclick="openAudioSettings()" title="Pengaturan Suara & TTS">
                    <i class="bi bi-volume-up-fill" id="icon-sound"></i>
                </button>
            </div>
        </div>

        <!-- Bottom Camera Hint Pill HUD -->
        <div class="camera-hud-bottom">
            <div class="hud-hint-pill">
                <span class="pulse-dot text-emerald-400"></span>
                <span id="scan-hint-text">Arahkan QR Card Siswa ke kamera</span>
            </div>
        </div>

        <!-- Camera Loading Overlay -->
        <div id="camera-loading"
            class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-950/90 gap-3 text-center px-4">
            <div class="w-9 h-9 rounded-full border-3 border-rose-500 border-t-transparent animate-spin"></div>
            <p class="text-white/80 text-xs font-semibold m-0">Menghubungkan sensor kamera...</p>
        </div>

        <!-- Camera Error Overlay -->
        <div id="camera-error"
            class="absolute inset-0 z-20 hidden flex-col items-center justify-center bg-slate-950/95 text-center px-6 gap-3">
            <div class="w-14 h-14 rounded-2xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center">
                <i class="bi bi-camera-video-off text-rose-400 text-2xl"></i>
            </div>
            <div>
                <p class="font-bold text-white text-sm m-0">Kamera Belum Aktif</p>
                <p class="text-slate-400 text-xs mt-1 m-0">Pastikan browser mengizinkan akses kamera atau gunakan tombol Ketik NIS.</p>
            </div>
            <div class="flex items-center gap-2 mt-1">
                <button type="button" onclick="initCamera()"
                    class="btn-tw btn-tw-sm btn-brand">
                    <i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi
                </button>
                <button type="button" onclick="openManualModal()"
                    class="btn-tw btn-tw-sm btn-light">
                    <i class="bi bi-keyboard me-1"></i> Ketik NIS/NISN/NIP/NUPTK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Scan Result Animated Card (Below Scanner)      -->
<!-- ═══════════════════════════════════════════════ -->
<div id="scan-result-container" class="mx-4 mb-4 hidden">
    <!-- Populated dynamically via JS -->
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Activity / Riwayat Presensi Hari Ini           -->
<!-- ═══════════════════════════════════════════════ -->
<div class="px-4 mt-2 mb-6" id="history-section">
    <div class="section-header">
        <div class="flex items-center gap-2">
            <h5>Riwayat Scan Hari Ini</h5>
            <span id="history-badge-count" class="badge-tw badge-secondary text-[10px]">0</span>
        </div>
        <button type="button" onclick="loadHistory(true)" class="text-xs text-rose-600 font-bold border-none bg-transparent cursor-pointer hover:underline flex items-center gap-1">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Quick Search in Today's Log -->
    <div class="relative mb-2.5">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" id="input-search-history" placeholder="Cari nama atau NIS siswa..."
            class="m3-input pl-8 text-xs py-2" oninput="filterHistoryList(this.value)">
    </div>

    <!-- Tab Filter: Semua / Belum Pulang -->
    <div class="flex p-1 bg-slate-200/70 rounded-2xl gap-1 mb-3">
        <button type="button" id="tab-history-all" onclick="setHistoryTab('all', this)"
                class="flex-1 py-1.5 px-1 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 bg-white text-blue-600 shadow-xs transition-all">
            <i class="bi bi-people text-sm"></i>
            <span>Semua</span>
            <span id="tab-count-all" class="text-[9px] px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-extrabold leading-none">0</span>
        </button>
        <button type="button" id="tab-history-belum" onclick="setHistoryTab('belum_pulang', this)"
                class="flex-1 py-1.5 px-1 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 text-slate-600 transition-all">
            <i class="bi bi-box-arrow-right text-sm"></i>
            <span>Belum Pulang</span>
            <span id="tab-count-belum" class="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-300/70 text-slate-700 font-extrabold leading-none">0</span>
        </button>
    </div>

    <!-- History List Items -->
    <div id="history-list" class="space-y-2">
        <!-- Skeleton Loaders -->
        <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="activity-card">
            <div class="skel-item w-11 h-11 flex-shrink-0"></div>
            <div class="flex-1 space-y-2">
                <div class="skel-item h-3.5 w-3/4"></div>
                <div class="skel-item h-2.5 w-1/2"></div>
            </div>
            <div class="skel-item h-6 w-16"></div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Load More -->
    <div id="history-load-more-wrap" class="hidden text-center mt-3">
        <p id="history-shown-info" class="text-[11px] text-slate-400 font-semibold mb-2"></p>
        <button type="button" id="btn-history-more" onclick="loadMoreHistory()"
                class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-blue-600 text-xs font-bold shadow-xs active:scale-95 transition-all">
            <i class="bi bi-chevron-down me-1"></i> Muat Lebih Banyak
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Modal: Input Manual NIS / NISN                 -->
<!-- ═══════════════════════════════════════════════ -->
<div class="tw-modal" id="modal-manual-input">
    <div class="tw-modal-box p-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                    <i class="bi bi-keyboard"></i>
                </div>
                <div>
                    <h6 class="font-bold text-slate-900 text-sm m-0">Input Presensi Manual</h6>
                    <p class="text-[11px] text-slate-400 m-0">Ketik NIS/NISN/NIP/NUPTK</p>
                </div>
            </div>
            <button type="button" class="btn-tw btn-tw-sm btn-light p-1.5" data-tw-dismiss="modal">
                <i class="bi bi-x-lg text-slate-500"></i>
            </button>
        </div>

        <form id="form-manual-input" onsubmit="handleManualSubmit(event)">
            <div class="mb-4">
                <label class="form-label-tw">NIS/NISN/NIP/NUPTK <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <i class="bi bi-upc-scan absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="manual-nis-input" class="m3-input pl-9 text-base font-bold tracking-wider"
                        placeholder="Contoh: 12345" autocomplete="off" required>
                    <button type="button" id="btn-toggle-rfid-mobile"
                        class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300 transition-all"
                        title="Mode RFID (kirim sebagai rfid_value)">RFID</button>
                    <button type="button" id="btn-toggle-fp-mobile"
                        class="absolute right-48 top-1/2 -translate-y-1/2 px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300 transition-all"
                        title="Mode Fingerprint (kirim sebagai fingerprint_uid)">FP</button>
                </div>
                <p class="text-[10px] text-slate-400 mt-1 mb-0">Nomor yang tercantum pada Kartu Absen. Aktifkan RFID atau FP jika ini UID kartu RFID / UserID mesin sidik jari.</p>
            </div>

            <div class="mb-4">
                <label class="form-label-tw">Mode Presensi</label>
                <div class="flex gap-2">
                    <input type="radio" name="manual_mode" id="mm-masuk" value="masuk" checked class="sr-only mode-opt">
                    <label for="mm-masuk" class="mode-chip mode-chip-masuk flex-1 flex items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 cursor-pointer text-xs font-bold">
                        <i class="bi bi-box-arrow-in-right text-emerald-600"></i> Absen Masuk
                    </label>
                    <input type="radio" name="manual_mode" id="mm-keluar" value="keluar" class="sr-only mode-opt">
                    <label for="mm-keluar" class="mode-chip mode-chip-keluar flex-1 flex items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 cursor-pointer text-xs font-bold">
                        <i class="bi bi-box-arrow-right text-amber-600"></i> Absen Pulang
                    </label>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" class="btn-tw btn-light flex-1" data-tw-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-brand flex-1" id="btn-submit-manual">
                    <i class="bi bi-check2-circle me-1"></i> Proses Absen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Modal: Notifikasi Hasil Scan Presensi (Popup)   -->
<!-- ═══════════════════════════════════════════════ -->
<div class="tw-modal" id="modal-scan-popup" style="z-index: 9999;">
    <div class="tw-modal-box p-0 overflow-hidden relative" style="max-width: 380px;">
        <!-- Top Status Banner with dynamic gradient & animation -->
        <div id="popup-header-banner" class="p-4 text-white text-center relative overflow-hidden transition-colors duration-300">
            <!-- Close Button -->
            <button type="button" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/20 hover:bg-black/30 active:scale-95 flex items-center justify-center text-white border-none cursor-pointer" onclick="closeScanPopup()" title="Tutup">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
            
            <!-- Animated Icon Bubble -->
            <div id="popup-icon-wrapper" class="w-14 h-14 mx-auto rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-3xl mb-2 shadow-inner border border-white/30">
                <i id="popup-icon" class="bi bi-check-circle-fill"></i>
            </div>
            
            <!-- Status Titles -->
            <div id="popup-badge-title" class="inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-black/20 mb-1">
                ABSEN MASUK BERHASIL
            </div>
            <h5 id="popup-main-message" class="text-sm font-extrabold m-0 leading-tight">
                Presensi Berhasil Dicatat!
            </h5>
        </div>

        <!-- Student Information Body -->
        <div class="p-5 bg-white text-center">
            <!-- Student Avatar with Colored Glow Ring -->
            <div class="relative inline-block mb-3">
                <img id="popup-siswa-foto" src="<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>" alt="Siswa"
                     class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-xl mx-auto"
                     onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
                <span id="popup-siswa-status-badge" class="absolute -bottom-2 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase text-white shadow-sm border border-white">
                    HADIR
                </span>
            </div>

            <!-- Student Name -->
            <h4 id="popup-siswa-nama" class="text-base font-black text-slate-800 m-0 leading-tight">
                Nama Siswa
            </h4>

            <!-- Badges: Kelas & NIS -->
            <div class="flex items-center justify-center gap-1.5 mt-2">
                <span id="popup-siswa-kelas" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                    Kelas
                </span>
                <span id="popup-siswa-nis" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-50 border border-slate-200 text-slate-600">
                    NIS: -
                </span>
            </div>

            <!-- Timestamp Box -->
            <div id="popup-time-box" class="mt-4 p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-around">
                <div class="text-center flex-1">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Waktu Tercatat</span>
                    <span id="popup-recorded-time" class="text-sm font-black text-emerald-600 flex items-center justify-center gap-1">
                        <i class="bi bi-clock-history text-xs"></i> <span id="popup-time-text">--:-- WITA</span>
                    </span>
                </div>
                <div class="h-7 w-px bg-slate-200"></div>
                <div class="text-center flex-1">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Keterangan</span>
                    <span id="popup-status-text" class="text-xs font-bold text-slate-700">Tepat Waktu</span>
                </div>
            </div>

            <!-- Auto-dismiss Countdown Progress Bar -->
            <div class="mt-4">
                <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1.5 px-1 font-medium">
                    <span>Otomatis tertutup dalam:</span>
                    <span id="popup-countdown-text" class="font-bold text-slate-600">2.8s</span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="popup-progress-bar" class="h-full bg-emerald-500 rounded-full transition-all duration-75" style="width: 100%;"></div>
                </div>
            </div>

            <!-- Action Button -->
            <button type="button" onclick="closeScanPopup()"
                    class="mt-4 w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-98 text-white font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-md">
                <i class="bi bi-qr-code-scan"></i>
                <span>Lanjut Scan Berikutnya</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Modal: Pengaturan Suara Notifikasi              -->
<!-- ═══════════════════════════════════════════════ -->
<div class="tw-modal" id="modal-audio-settings" style="z-index: 9999;">
    <div class="tw-modal-box p-5" style="max-width: 380px;">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                    <i class="bi bi-soundwave"></i>
                </div>
                <div>
                    <h6 class="font-bold text-slate-900 text-sm m-0">Suara Notifikasi Presensi</h6>
                    <p class="text-[11px] text-slate-400 m-0">Efek bel audio &amp; pengucapan nama</p>
                </div>
            </div>
            <button type="button" class="btn-tw btn-tw-sm btn-light p-1.5" data-tw-dismiss="modal">
                <i class="bi bi-x-lg text-slate-500"></i>
            </button>
        </div>

        <div class="space-y-3 mb-4">
            <!-- Toggle Beep Chime -->
            <label class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100/70 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-base">
                        <i class="bi bi-bell-fill"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Suara Bel / Chime Notifikasi</div>
                        <div class="text-[11px] text-slate-400">Melodi nada saat presensi berhasil</div>
                    </div>
                </div>
                <input type="checkbox" id="setting-sound-enabled" class="w-5 h-5 accent-rose-600 rounded cursor-pointer" onchange="toggleSoundSetting(this.checked)">
            </label>

            <!-- Toggle Voice TTS -->
            <label class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100/70 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-base">
                        <i class="bi bi-megaphone-fill"></i>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Sebut Nama Siswa (Voice TTS)</div>
                        <div class="text-[11px] text-slate-400">Membacakan nama siswa &amp; status</div>
                    </div>
                </div>
                <input type="checkbox" id="setting-voice-enabled" class="w-5 h-5 accent-rose-600 rounded cursor-pointer" onchange="toggleVoiceSetting(this.checked)">
            </label>
        </div>

        <!-- Tes Suara Buttons -->
        <div class="pt-2 border-t border-slate-100">
            <span class="text-[11px] font-bold text-slate-400 block mb-2">Uji Coba Suara:</span>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="testSound('in')" class="btn-tw btn-tw-sm bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold hover:bg-emerald-100 flex items-center justify-center gap-1.5 py-2">
                    <i class="bi bi-play-circle-fill"></i> Tes Masuk
                </button>
                <button type="button" onclick="testSound('out')" class="btn-tw btn-tw-sm bg-amber-50 text-amber-700 border border-amber-200 font-bold hover:bg-amber-100 flex items-center justify-center gap-1.5 py-2">
                    <i class="bi bi-play-circle-fill"></i> Tes Pulang
                </button>
            </div>
        </div>

        <div class="mt-4">
            <button type="button" class="btn-tw btn-brand w-full py-2.5" data-tw-dismiss="modal">
                Simpan &amp; Tutup
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Custom Bottom Navigation (Consistent with App) -->
<!-- ═══════════════════════════════════════════════ -->
<nav class="custom-bottom-bar" aria-label="Navigasi presensi siswa">
    <?php if (!empty($isLoggedIn)): ?>
    <a href="<?= site_url('presensi') ?>" class="nav-item">
        <span class="nav-icon-wrap">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <path class="duo" d="M3.6 10.4 12 3.6l8.4 6.8V19a2.4 2.4 0 0 1-2.4 2.4H6A2.4 2.4 0 0 1 3.6 19z"/>
                <path d="M9.6 21.4v-5.3a1 1 0 0 1 1-1h2.8a1 1 0 0 1 1 1v5.3"/>
            </svg>
        </span>
        <span class="nav-label">Home</span>
    </a>
    <?php else: ?>
    <a href="<?= site_url('guru/presensi-siswa' . (!empty($hash) ? '?h=' . urlencode($hash) : '')) ?>" class="nav-item active">
        <span class="nav-icon-wrap">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <rect class="duo" x="3.6" y="3.6" width="16.8" height="16.8" rx="4.4"/>
                <path d="M8.2 8.4v7.2M12 8.4v7.2M15.8 8.4v7.2"/>
            </svg>
        </span>
        <span class="nav-label">Presensi</span>
    </a>
    <?php endif; ?>

    <a href="javascript:void(0)" onclick="scrollToHistory()" class="nav-item">
        <span class="nav-icon-wrap">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="duo" cx="12" cy="12" r="8.6"/>
                <path d="M12 7.3v4.9l3.4 2"/>
            </svg>
        </span>
        <span class="nav-label">Riwayat</span>
    </a>

    <!-- Center Scanner Button -->
    <a href="javascript:void(0)" onclick="scrollToScanner()" class="nav-item nav-item-center">
        <span class="nav-center-btn">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <path class="duo" d="M3.4 9.6a2 2 0 0 1 2-2h1.8l1.1-1.9h6.4l1.1 1.9h1.8a2 2 0 0 1 2 2v7.8a2 2 0 0 1-2 2H5.4a2 2 0 0 1-2-2z"/>
                <circle cx="12" cy="13.5" r="3.4"/>
            </svg>
        </span>
        <span class="nav-label nav-label-center">Scan</span>
    </a>

    <a href="javascript:void(0)" onclick="openManualModal()" class="nav-item">
        <span class="nav-icon-wrap">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <rect class="duo" x="2.8" y="6.4" width="18.4" height="11.2" rx="2.6"/>
                <path d="M6.4 10h.01M9.6 10h.01M12.8 10h.01M16 10h.01M6.4 13h.01M9.6 13h.01M12.8 13h.01M16 13h.01"/>
                <path d="M8.4 15h7.2"/>
            </svg>
        </span>
        <span class="nav-label">Manual</span>
    </a>

    <?php if (!empty($isLoggedIn)): ?>
    <a href="<?= site_url('presensi/profile') ?>" class="nav-item">
        <span class="nav-icon-wrap">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="duo" cx="12" cy="12" r="9"/>
                <circle cx="12" cy="10" r="2.6"/>
                <path d="M7.4 17.4a4.9 4.9 0 0 1 9.2 0"/>
            </svg>
        </span>
        <span class="nav-label">Profil</span>
    </a>
    <?php else: ?>
    <a href="<?= site_url('auth') ?>" class="nav-item">
        <span class="nav-icon-wrap">
            <svg class="nav-ico" viewBox="0 0 24 24" aria-hidden="true">
                <path class="duo" d="M10.4 4.4H6.2a2 2 0 0 0-2 2v11.2a2 2 0 0 0 2 2h4.2"/>
                <path d="M14.6 8.4 18.2 12l-3.6 3.6"/>
                <path d="M18.2 12H9.4"/>
            </svg>
        </span>
        <span class="nav-label">Login GTK</span>
    </a>
    <?php endif; ?>
</nav>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- html5-qrcode library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= base_url('assets/js/wita-clock.js?v=' . (@filemtime(FCPATH . 'assets/js/wita-clock.js') ?: time())) ?>"></script>

<script>
(function () {
    'use strict';

    // Jam perangkat ≠ WITA. Semua keputusan jendela scan dan label "WITA"
    // dibaca dari jam server, bukan new Date() lokal. Sekali saja — stamp
    // dibekukan saat render, jadi witaInit() ulang dengan stamp identik
    // justru membekukan jam di waktu load.
    witaInit('<?= date('Y-m-d H:i:s') ?>');

    // ── State ──────────────────────────────────────────
    let scanner        = null;
    const MODE_TAB = {
        masukOpen:  <?= !empty($modeTab['masuk_open']) ? 'true' : 'false' ?>,
        pulangOpen: <?= !empty($modeTab['pulang_open']) ? 'true' : 'false' ?>,
        masukMsg:   <?= json_encode($modeTab['masuk_msg'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        pulangMsg:  <?= json_encode($modeTab['pulang_msg'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        jamMasuk:   <?= json_encode($modeTab['jam_masuk'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        bukaMasuk:  <?= json_encode($modeTab['buka_masuk'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        tutupMasuk: <?= json_encode($modeTab['tutup_masuk'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        jamPulang:  <?= json_encode($modeTab['jam_pulang'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        bukaPulang: <?= json_encode($modeTab['buka_pulang'] ?? ($modeTab['jam_pulang'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
        qrAktif:    <?= !empty($modeTab['qr_aktif']) ? 'true' : 'false' ?>,
        qrMulai:    <?= json_encode($modeTab['qr_mulai'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        qrSelesai:  <?= json_encode($modeTab['qr_selesai'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    };
    let mode           = <?= json_encode($defMode ?? 'masuk') ?>; // 'masuk' | 'keluar', bawaan mengikuti jadwal
    let isScanning     = false;
    let lastQrValue    = '';
    let debounceTimer  = null;
    const DEBOUNCE_MS  = 2500;
    let selectedKelas  = <?= (int)$selectedKelas ?>;
    let allHistoryRows = [];
    const FOTO_CADANGAN = <?= json_encode(\App\Services\AvatarService::cadangan(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    // ── GPS perangkat untuk geofence server ───
    let kioskLoc = { lat: null, lng: null, accuracy: null };
    function startKioskGpsWatch() {
        if (!('geolocation' in navigator)) return;
        try {
            navigator.geolocation.watchPosition(
                function (pos) {
                    kioskLoc = { lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy };
                },
                function () {},
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
            );
        } catch (e) {}
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startKioskGpsWatch);
    } else {
        startKioskGpsWatch();
    }

    // Camera selection & torch
    let availableCameras = [];
    let currentCamIndex  = 0;
    let isTorchOn        = false;
    let soundEnabled     = localStorage.getItem('presensi_qr_sound') !== '0';
    let voiceEnabled     = localStorage.getItem('presensi_qr_voice') !== '0';
    let rfidMode         = localStorage.getItem('presensi_qr_rfid') === '1';
    let audioCtx         = null;

    // Popup & Scanner control
    let isPopupOpen      = false;
    let popupTimer       = null;
    let popupInterval    = null;
    const POPUP_DURATION_MS = 2800;

    const CSRF_NAME = '<?= $csrfField ?>';
    let   csrfHash  = '<?= $csrfHash ?>';

    // Endpoint mengikuti halaman pembuka: /guru/presensi-siswa (wajib login+hash)
    // atau /scanner kiosk publik (tanpa login/hash).
    const SCAN_URL    = '<?= $scan_url ?? site_url('guru/presensi-siswa/scan') ?>';
    const HISTORY_URL = '<?= $history_url ?? site_url('guru/presensi-siswa/history') ?>';

    function refreshCsrf(hash) { if (hash) csrfHash = hash; }

    const PAGE_QR_HASH = '<?= esc($hash ?? '') ?>';
    function postJson(url, data) {
        data[CSRF_NAME] = csrfHash;
        return $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            headers: PAGE_QR_HASH ? { 'X-QR-Hash': PAGE_QR_HASH } : {},
            data: data
        }).then(res => {
            if (res && res[CSRF_NAME]) refreshCsrf(res[CSRF_NAME]);
            return res;
        });
    }

    // ── Audio Engine (Zero-latency Web Audio & TTS) ───
    function getAudioContext() {
        try {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) audioCtx = new AudioCtxClass();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }
            return audioCtx;
        } catch (e) {
            return null;
        }
    }

    // Unlock audio context on first user interaction
    function unlockAudio() {
        const ctx = getAudioContext();
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().catch(() => {});
        }
    }
    document.addEventListener('touchstart', unlockAudio, { once: true, passive: true });
    document.addEventListener('click', unlockAudio, { once: true, passive: true });

    function playChime(type = 'success_in') {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            if (!ctx) return;
            if (ctx.state === 'suspended') ctx.resume();

            const now = ctx.currentTime;

            if (type === 'success_in') {
                // Melodic rising 3-tone chime for Check-in: E5 (659.25Hz) -> G#5 (830.61Hz) -> B5 (987.77Hz)
                const notes = [659.25, 830.61, 987.77];
                notes.forEach((freq, i) => {
                    const startTime = now + (i * 0.08);
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, startTime);

                    // Add sparkling overtone
                    const overtone = ctx.createOscillator();
                    const overGain = ctx.createGain();
                    overtone.type = 'triangle';
                    overtone.frequency.setValueAtTime(freq * 2, startTime);
                    overGain.gain.setValueAtTime(0.08, startTime);
                    overGain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.35);

                    gain.gain.setValueAtTime(0.25, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, startTime + (i === 2 ? 0.6 : 0.25));

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    overtone.connect(overGain);
                    overGain.connect(ctx.destination);

                    osc.start(startTime);
                    osc.stop(startTime + (i === 2 ? 0.6 : 0.25));
                    overtone.start(startTime);
                    overtone.stop(startTime + 0.35);
                });
            } else if (type === 'success_out') {
                // Warm descending 3-tone chime for Check-out: B5 (987.77Hz) -> G#5 (830.61Hz) -> E5 (659.25Hz)
                const notes = [987.77, 830.61, 659.25];
                notes.forEach((freq, i) => {
                    const startTime = now + (i * 0.09);
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, startTime);

                    gain.gain.setValueAtTime(0.22, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, startTime + (i === 2 ? 0.55 : 0.22));

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(startTime);
                    osc.stop(startTime + (i === 2 ? 0.55 : 0.22));
                });
            } else if (type === 'warning') {
                // Crisp double warning ping
                [440, 349.23].forEach((freq, i) => {
                    const startTime = now + (i * 0.12);
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, startTime);
                    gain.gain.setValueAtTime(0.2, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, startTime + 0.18);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(startTime);
                    osc.stop(startTime + 0.18);
                });
            } else {
                // Error low tone
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
        // Bersihkan tanda petik / simbol non-huruf yang memicu spelling per karakter
        let clean = String(fullName).replace(/['"`]/g, '')
                                    .replace(/[^\w\s]/gi, ' ')
                                    .trim();
        let words = clean.toLowerCase().split(/\s+/).filter(Boolean);
        if (words.length === 0) return '';
        
        // Ambil maksimal 2 kata (atau 3 kata jika kata pertama singkat seperti M / Muh)
        const maxWords = (words[0].length <= 2 && words.length > 2) ? 3 : 2;
        const picked = words.slice(0, Math.min(words.length, maxWords));
        
        // Buat format Titlecase alami (misal "Jordan Argin")
        return picked.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    }

    // Cache voice bahasa Indonesia
    let indonesianVoice = null;
    function getIndonesianVoice() {
        if (!('speechSynthesis' in window)) return null;
        if (indonesianVoice) return indonesianVoice;

        const voices = window.speechSynthesis.getVoices();
        if (!voices || voices.length === 0) return null;

        // Cari suara Bahasa Indonesia asli (id-ID, id_ID, Google Bahasa Indonesia, dsb.)
        indonesianVoice = voices.find(v => v.lang === 'id-ID' || v.lang === 'id_ID')
            || voices.find(v => v.lang && v.lang.toLowerCase().startsWith('id'))
            || voices.find(v => v.name && (v.name.toLowerCase().includes('indonesia') || v.name.toLowerCase().includes('indonesian')))
            || null;

        return indonesianVoice;
    }

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = function() {
            indonesianVoice = null;
            getIndonesianVoice();
        };
        try { window.speechSynthesis.getVoices(); } catch(e) {}
    }

    // Text-to-Speech (TTS) voice announcer in Indonesian
    function speakText(rawText) {
        if (!voiceEnabled || !('speechSynthesis' in window) || !rawText) return;
        try {
            window.speechSynthesis.cancel(); // batalkan pengucapan yang tertunda

            // Normalisasi teks: Hapus tanda petik tunggal & hindari kata ALL-CAPS agar tidak dieja huruf demi huruf
            let text = String(rawText).replace(/['"`]/g, '').trim();
            text = text.replace(/\b[A-Z]{2,}\b/g, match => match.charAt(0) + match.slice(1).toLowerCase());

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 1.0; // Kecepatan normal natural
            utterance.pitch = 1.0;

            const voice = getIndonesianVoice();
            if (voice) {
                utterance.voice = voice;
            }

            setTimeout(() => {
                try {
                    window.speechSynthesis.speak(utterance);
                } catch(err) {
                    console.warn('speechSynthesis.speak error:', err);
                }
            }, 60);
        } catch (e) {
            console.warn('speakText error:', e);
        }
    }

    // ── Audio Settings & Toggles ───────────────────────
    window.openAudioSettings = function () {
        $('#setting-sound-enabled').prop('checked', soundEnabled);
        $('#setting-voice-enabled').prop('checked', voiceEnabled);
        twModalShow('#modal-audio-settings');
    };

    window.toggleSoundSetting = function (checked) {
        soundEnabled = !!checked;
        localStorage.setItem('presensi_qr_sound', soundEnabled ? '1' : '0');
        updateAudioIcons();
        if (soundEnabled) playChime('success_in');
    };

    window.toggleVoiceSetting = function (checked) {
        voiceEnabled = !!checked;
        localStorage.setItem('presensi_qr_voice', voiceEnabled ? '1' : '0');
        if (voiceEnabled) speakText('Suara aktif');
    };

    window.testSound = function (modeType) {
        unlockAudio();
        if (modeType === 'in') {
            playChime('success_in');
            setTimeout(() => speakText('Jordan Argin. Absen masuk.'), 300);
        } else {
            playChime('success_out');
            setTimeout(() => speakText('Jordan Argin. Absen pulang.'), 300);
        }
    };

    function updateAudioIcons() {
        const btnHud = document.getElementById('btn-toggle-sound');
        const iconHud = document.getElementById('icon-sound');

        if (soundEnabled || voiceEnabled) {
            if (btnHud) btnHud.classList.remove('sound-muted');
            if (iconHud) iconHud.className = 'bi bi-volume-up-fill';
        } else {
            if (btnHud) btnHud.classList.add('sound-muted');
            if (iconHud) iconHud.className = 'bi bi-volume-mute-fill';
        }
    }
    updateAudioIcons();

    // ── Mode Toggle (menyesuaikan jadwal; tab tertutup tak bisa dipilih) ──
    // ── Tab mode mengikuti jadwal (hitung ulang murni dari jam + jendela server) ──
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
    function blockedMsg(m) {
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
        const rMasuk = document.querySelector('input[name="manual_mode"][value="masuk"]');
        const rKeluar = document.querySelector('input[name="manual_mode"][value="keluar"]');
        if (rMasuk) rMasuk.disabled = !MODE_TAB.masukOpen;
        if (rKeluar) rKeluar.disabled = !MODE_TAB.pulangOpen;
    }
    function applyMode(m) {
        mode = m;
        const btnMasuk  = document.getElementById('btn-mode-masuk');
        const btnKeluar = document.getElementById('btn-mode-keluar');
        const frameBox  = document.getElementById('camera-frame-box');
        if (btnMasuk) btnMasuk.classList.remove('active-masuk', 'active-keluar');
        if (btnKeluar) btnKeluar.classList.remove('active-masuk', 'active-keluar');
        if (m === 'masuk') {
            if (btnMasuk) btnMasuk.classList.add('active-masuk');
            if (frameBox) frameBox.classList.remove('mode-keluar-active');
            const rMasuk = document.querySelector('input[name="manual_mode"][value="masuk"]');
            if (rMasuk && !rMasuk.disabled) rMasuk.checked = true;
        } else {
            if (btnKeluar) btnKeluar.classList.add('active-keluar');
            if (frameBox) frameBox.classList.add('mode-keluar-active');
            const rKeluar = document.querySelector('input[name="manual_mode"][value="keluar"]');
            if (rKeluar && !rKeluar.disabled) rKeluar.checked = true;
        }
    }
    // Hitung ulang + pindah tab otomatis (diam-diam, tanpa popup) saat
    // waktu melewati batas: buka/tutup masuk, jam pulang, jendela operasional.
    function refreshModeTabs() {
        try {
            const st = computeModeOpen(nowHMS());
            MODE_TAB.masukOpen = st.mOpen;   MODE_TAB.masukMsg = st.mMsg;
            MODE_TAB.pulangOpen = st.pOpen;  MODE_TAB.pulangMsg = st.pMsg;
            paintModeTabs();
            const curOpen = (mode === 'masuk') ? st.mOpen : st.pOpen;
            if (!curOpen) {
                if (st.mOpen) applyMode('masuk');
                else if (st.pOpen) applyMode('keluar');
            }
            scheduleNextSwitch();
        } catch (e) {}
    }
    // Jadwalkan refresh tepat pada detik batas berikutnya (presisi detik),
    // bukan menunggu interval — interval hanya pengaman.
    function scheduleNextSwitch() {
        try {
            if (scheduleNextSwitch._t) clearTimeout(scheduleNextSwitch._t);
            const cands = [MODE_TAB.bukaMasuk, MODE_TAB.tutupMasuk, MODE_TAB.bukaPulang || MODE_TAB.jamPulang];
            if (MODE_TAB.qrAktif) cands.push(MODE_TAB.qrMulai, MODE_TAB.qrSelesai);
            let best = null;
            // Selisih dalam detik-jam-WITA, bukan Date lokal: batas '07:30'
            // adalah jam server, di perangkat WIB timer lama meleset satu jam.
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
    window.setMode = function (m) {
        const msg = blockedMsg(m);
        if (msg) {
            if (typeof showScanPopup === 'function') showScanPopup({ status: false, message: msg }, m);
            else alert(msg);
            return;
        }
        applyMode(m);
    };
    // Inisialisasi senyap (tanpa popup): pastikan mode yang tampil sedang terbuka
    refreshModeTabs();
    setInterval(refreshModeTabs, 15000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshModeTabs(); });

    // ── Kelas Selector ─────────────────────────────────
    const selKelas = document.getElementById('select-kelas');
    if (selKelas) {
        selKelas.addEventListener('change', function () {
            selectedKelas = parseInt(this.value) || 0;
            loadHistory(true);
        });
    }

    // ── Camera Initialization & Switching ─────────────
    window.initCamera = function () {
        $('#camera-loading').css('display', 'flex');
        $('#camera-error').addClass('hidden');

        if (scanner) {
            scanner.clear().catch(() => {}).finally(() => {
                scanner = null;
                startScannerEngine();
            });
        } else {
            startScannerEngine();
        }
    };

    function startScannerEngine() {
        scanner = new Html5Qrcode('qr-reader', { verbose: false });

        Html5Qrcode.getCameras()
            .then(cameras => {
                if (!cameras || cameras.length === 0) {
                    showCameraError();
                    return;
                }
                availableCameras = cameras;

                let preferredIndex = cameras.findIndex(c => {
                    const label = (c.label || '').toLowerCase();
                    return label.includes('back') || label.includes('rear') || label.includes('environment');
                });
                if (preferredIndex < 0) preferredIndex = cameras.length - 1;
                currentCamIndex = preferredIndex;

                if (availableCameras.length > 1) {
                    $('#btn-switch-cam').show();
                } else {
                    $('#btn-switch-cam').show();
                }

                startStream(availableCameras[currentCamIndex].id);
            })
            .catch(err => {
                console.warn('Camera lookup failed, attempting default facing mode:', err);
                // Fallback to startStream by facingMode if getCameras failed
                if (scanner) {
                    scanner.start(
                        { facingMode: currentFacingMode },
                        {
                            fps: 15,
                            qrbox: function(viewfinderWidth, viewfinderHeight) {
                                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                                const boxSize = Math.floor(minEdge * 0.76);
                                return { width: boxSize, height: boxSize };
                            },
                            aspectRatio: 1.0,
                        },
                        onScanSuccess,
                        () => {}
                    ).then(() => {
                        $('#camera-loading').hide();
                        isScanning = true;
                        checkTorchSupport();
                    }).catch(() => showCameraError());
                } else {
                    showCameraError();
                }
            });
    }

    function startStream(cameraId) {
        scanner.start(
            cameraId,
            {
                fps: 15,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    const boxSize = Math.floor(minEdge * 0.76);
                    return { width: boxSize, height: boxSize };
                },
                aspectRatio: 1.0,
            },
            onScanSuccess,
            () => {}
        )
        .then(() => {
            $('#camera-loading').hide();
            isScanning = true;
            checkTorchSupport();
        })
        .catch(err => {
            console.warn('Camera start stream failed:', err);
            showCameraError();
        });
    }

    let currentFacingMode = 'environment'; // 'environment' | 'user'

    window.switchCamera = function () {
        if (!scanner) {
            initCamera();
            return;
        }

        $('#camera-loading').css('display', 'flex');
        $('#camera-error').addClass('hidden');

        if (availableCameras.length > 1) {
            currentCamIndex = (currentCamIndex + 1) % availableCameras.length;
            const targetId = availableCameras[currentCamIndex].id;
            scanner.stop()
                .then(() => startStream(targetId))
                .catch(() => initCamera());
        } else {
            currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
            scanner.stop()
                .then(() => {
                    scanner.start(
                        { facingMode: currentFacingMode },
                        {
                            fps: 15,
                            qrbox: function(viewfinderWidth, viewfinderHeight) {
                                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                                const boxSize = Math.floor(minEdge * 0.76);
                                return { width: boxSize, height: boxSize };
                            },
                            aspectRatio: 1.0,
                        },
                        onScanSuccess,
                        () => {}
                    ).then(() => {
                        $('#camera-loading').hide();
                        isScanning = true;
                        checkTorchSupport();
                    }).catch(err => {
                        console.warn('Switch facing mode failed:', err);
                        initCamera();
                    });
                })
                .catch(() => initCamera());
        }
    };

    function updateTorchUI(isOn) {
        isTorchOn = !!isOn;
        const btnHud = document.getElementById('btn-toggle-torch');

        if (isTorchOn) {
            if (btnHud) {
                btnHud.classList.add('active');
                btnHud.style.background = '#f59e0b';
                btnHud.style.borderColor = '#fbbf24';
            }
        } else {
            if (btnHud) {
                btnHud.classList.remove('active');
                btnHud.style.background = '';
                btnHud.style.borderColor = '';
            }
        }
    }

    function checkTorchSupport() {
        try {
            const videoEl = document.querySelector('#qr-reader video');
            if (videoEl && videoEl.srcObject) {
                const track = videoEl.srcObject.getVideoTracks()[0];
                const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                if (capabilities.torch) {
                    $('#btn-toggle-torch').show();
                    return true;
                }
            }
        } catch (e) {}
        return false;
    }

    window.toggleTorch = function () {
        try {
            const videoEl = document.querySelector('#qr-reader video');
            if (!videoEl || !videoEl.srcObject) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Kamera Belum Aktif',
                        text: 'Aktifkan kamera terlebih dahulu untuk menggunakan lampu senter.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                return;
            }
            const track = videoEl.srcObject.getVideoTracks()[0];
            if (!track) return;

            const capabilities = track.getCapabilities ? track.getCapabilities() : {};
            if (!capabilities.torch) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Senter Tidak Didukung',
                        text: 'Kamera yang aktif saat ini tidak mendukung lampu senter (biasanya hanya ada pada kamera belakang handphone). Coba ganti ke kamera belakang.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
                return;
            }

            const nextTorch = !isTorchOn;
            track.applyConstraints({
                advanced: [{ torch: nextTorch }]
            }).then(() => {
                updateTorchUI(nextTorch);
            }).catch(err => {
                console.warn('Torch constraint error:', err);
                updateTorchUI(false);
            });
        } catch (e) {
            console.warn('toggleTorch error:', e);
        }
    };

    function showCameraError() {
        $('#camera-loading').hide();
        $('#camera-error').removeClass('hidden').css('display', 'flex');
    }

    // ── On QR Decoded ──────────────────────────────────
    function onScanSuccess(decodedText) {
        const qrValue = decodedText.trim();
        if (!qrValue) return;

        // Prevent scanning while popup dialog is open or during debounce
        if (isPopupOpen) return;
        if (qrValue === lastQrValue && debounceTimer) return;

        lastQrValue  = qrValue;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { lastQrValue = ''; }, DEBOUNCE_MS);

        if (navigator.vibrate) navigator.vibrate([40, 30, 40]);

        processScan(qrValue, mode);
    }

    // ── Process Scan via AJAX ──────────────────────────
    function processScan(qrValue, scanMode) {
        // Segarkan status tab dulu (kios bisa idle melewati batas jadwal),
        // tolak langsung bila mode sudah tertutup — tanpa request sia-sia.
        refreshModeTabs();
        const gateMsg = blockedMsg(scanMode);
        if (gateMsg) {
            const gateRes = { status: false, message: gateMsg };
            showScanPopup(gateRes, scanMode);
            renderScanResult(gateRes);
            return;
        }
        $('#scan-hint-text').text('Memproses presensi...');

        postJson(SCAN_URL, {
            qr_value: qrValue,
            mode:     scanMode,
            id_kelas: selectedKelas || 0,
            lat:      kioskLoc.lat,
            lng:      kioskLoc.lng,
            accuracy: kioskLoc.accuracy,
            h:        '<?= esc($hash ?? '') ?>',
        })
        .done(res => {
            showScanPopup(res, scanMode);
            renderScanResult(res);
            if (res.status && res.siswa) {
                prependToHistory(res.siswa, scanMode);
            }
            loadHistory(false);
        })
        .fail((jqXHR, textStatus) => {
            const code = jqXHR ? jqXHR.status : 0;
            const resErr = jqXHR && jqXHR.responseJSON;
            const serverMsg = resErr && resErr.message ? String(resErr.message) : '';
            let msg;
            if (code === 0) {
                msg = 'Tidak ada koneksi ke server. Periksa internet lalu coba lagi.';
            } else if (code === 401) {
                msg = 'Sesi login habis. Silakan login ulang lalu scan kembali.';
            } else if (code === 403) {
                // Hash keamanan kedaluwarsa (mis. halaman terbuka lewat tengah malam):
                // muat ulang sekali agar index() menerbitkan hash baru. Dibatasi
                // sekali per 2 menit agar tidak looping bila server tetap menolak.
                if (/hash|kedaluwarsa/i.test(serverMsg)) {
                    const lastReload = parseInt(sessionStorage.getItem('qr_hash_reload_ts') || '0', 10);
                    if (Date.now() - lastReload > 120000) {
                        sessionStorage.setItem('qr_hash_reload_ts', String(Date.now()));
                        msg = 'Token keamanan kedaluwarsa. Memuat ulang halaman...';
                        showScanPopup({ status: false, message: msg }, scanMode);
                        renderScanResult({ status: false, message: msg });
                        setTimeout(() => location.reload(), 1200);
                        return;
                    }
                }
                msg = serverMsg || 'Akses ditolak / token keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            } else if (code === 429) {
                msg = serverMsg || 'Terlalu banyak scan. Tunggu sebentar lalu coba lagi.';
            } else if (serverMsg) {
                msg = serverMsg + ' (HTTP ' + code + ')';
            } else if (textStatus === 'parsererror') {
                msg = 'Respons server tidak valid (kemungkinan sesi habis). Muat ulang halaman lalu coba lagi.';
            } else {
                msg = 'Gagal terhubung ke server presensi (HTTP ' + (code || '?') + ').';
            }
            showScanPopup({ status: false, message: msg }, scanMode);
            renderScanResult({ status: false, message: msg });
        })
        .always(() => {
            $('#scan-hint-text').text('Arahkan QR Card Siswa ke kamera');
        });
    }

    // ── Attendance Popup Modal with Sound & Timer ──────
    function showScanPopup(res, scanMode) {
        const ok   = res.status === true;
        const type = res.type || (ok ? (scanMode === 'masuk' ? 'check_in' : 'check_out') : 'error');
        const s    = res.siswa || null;
        const nowTimeStr = witaHM() + ' WITA';

        clearTimeout(popupTimer);
        clearInterval(popupInterval);

        // Gerbang jurnal bisa menahan karena sesi belum diisi ATAU jurnalnya
        // sudah ada tapi belum disetujui piket — layarnya harus bedakan keduanya.
        const menungguValidasi = !!(res.data && Array.isArray(res.data.sesi_menunggu_validasi) && res.data.sesi_menunggu_validasi.length > 0);

        const configs = {
            check_in: {
                headerBg: 'bg-gradient-to-r from-emerald-600 to-teal-600',
                badgeBg: 'bg-emerald-700/60 text-white',
                badgeTitle: 'ABSEN MASUK BERHASIL',
                icon: 'bi-check-circle-fill',
                ringClass: 'popup-ring-masuk',
                statusBadgeBg: 'bg-emerald-600',
                statusText: 'Hadir Tepat Waktu',
                soundType: 'success_in',
                ttsText: s ? `${formatSpeechName(s.nama)}. Absen masuk.` : 'Absen masuk.',
            },
            check_out: {
                headerBg: 'bg-gradient-to-r from-amber-500 to-orange-500',
                badgeBg: 'bg-amber-700/60 text-white',
                badgeTitle: 'ABSEN PULANG BERHASIL',
                icon: 'bi-box-arrow-right',
                ringClass: 'popup-ring-keluar',
                statusBadgeBg: 'bg-amber-500',
                statusText: 'Absen Pulang Selesai',
                soundType: 'success_out',
                ttsText: s ? `${formatSpeechName(s.nama)}. Absen pulang.` : 'Absen pulang.',
            },
            already_in: {
                headerBg: 'bg-gradient-to-r from-amber-500 to-yellow-500',
                badgeBg: 'bg-amber-700/60 text-white',
                badgeTitle: 'SUDAH ABSEN MASUK',
                icon: 'bi-exclamation-triangle-fill',
                ringClass: 'popup-ring-warning',
                statusBadgeBg: 'bg-amber-500',
                statusText: 'Sudah Absen Hari Ini',
                soundType: 'warning',
                ttsText: s ? `${formatSpeechName(s.nama)}. Sudah absen masuk.` : 'Sudah absen masuk.',
            },
            already_out: {
                headerBg: 'bg-gradient-to-r from-amber-500 to-yellow-500',
                badgeBg: 'bg-amber-700/60 text-white',
                badgeTitle: 'SUDAH ABSEN PULANG',
                icon: 'bi-exclamation-triangle-fill',
                ringClass: 'popup-ring-warning',
                statusBadgeBg: 'bg-amber-500',
                statusText: 'Sudah Absen Keluar',
                soundType: 'warning',
                ttsText: s ? `${formatSpeechName(s.nama)}. Sudah absen keluar.` : 'Sudah absen keluar.',
            },
            not_in: {
                headerBg: 'bg-gradient-to-r from-orange-500 to-orange-600',
                badgeBg: 'bg-orange-700/60 text-white',
                badgeTitle: 'BELUM ABSEN MASUK',
                icon: 'bi-info-circle-fill',
                ringClass: 'popup-ring-warning',
                statusBadgeBg: 'bg-orange-500',
                statusText: 'Belum Ada Absen Masuk',
                soundType: 'warning',
                ttsText: s ? `${formatSpeechName(s.nama)}. Belum absen masuk.` : 'Belum absen masuk.',
            },
            izin: {
                headerBg: 'bg-gradient-to-r from-purple-600 to-indigo-600',
                badgeBg: 'bg-purple-800/60 text-white',
                badgeTitle: 'STATUS IZIN / SAKIT',
                icon: 'bi-calendar-check',
                ringClass: 'popup-ring-warning',
                statusBadgeBg: 'bg-purple-600',
                statusText: 'Izin Resmi',
                soundType: 'warning',
                ttsText: s ? `${formatSpeechName(s.nama)}. Berstatus izin.` : 'Status izin.',
            },
            jurnal_kurang: {
                headerBg: 'bg-gradient-to-r from-rose-600 to-orange-600',
                badgeBg: 'bg-rose-800/60 text-white',
                badgeTitle: menungguValidasi ? 'JURNAL MENUNGGU VALIDASI PIKET' : 'JURNAL MENGAJAR BELUM LENGKAP',
                icon: 'bi-journal-text',
                ringClass: 'popup-ring-error',
                statusBadgeBg: 'bg-rose-600',
                statusText: 'Absen Pulang Ditolak',
                soundType: 'warning',
                // Daftar sesinya ada di res.message — cukup bacakan yang pendek.
                ttsText: s
                    ? `${formatSpeechName(s.nama)}. ${menungguValidasi ? 'Jurnal menunggu validasi piket.' : 'Jurnal mengajar belum lengkap.'}`
                    : (menungguValidasi ? 'Jurnal menunggu validasi piket.' : 'Jurnal mengajar belum lengkap.'),
            },
            error: {
                headerBg: 'bg-gradient-to-r from-red-600 to-red-700',
                badgeBg: 'bg-red-800/60 text-white',
                badgeTitle: 'PRESENSI GAGAL',
                icon: 'bi-x-circle-fill',
                ringClass: 'popup-ring-error',
                statusBadgeBg: 'bg-rose-600',
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
            cfg.badgeTitle = 'ABSEN MASUK (TELAT)';
        }

        // Play chime & speak name via TTS
        playChime(cfg.soundType);
        speakText(cfg.ttsText);

        // Header & Icon
        const $header = $('#popup-header-banner');
        $header.attr('class', `p-4 text-white text-center relative overflow-hidden transition-colors duration-300 ${cfg.headerBg}`);
        $('#popup-icon').attr('class', `bi ${cfg.icon}`);
        $('#popup-badge-title').attr('class', `inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1 ${cfg.badgeBg}`).text(cfg.badgeTitle);
        $('#popup-main-message').text(res.message || 'Presensi berhasil dicatat');

        // Student Data
        const $avatar = $('#popup-siswa-foto');
        $avatar.removeClass('popup-ring-masuk popup-ring-keluar popup-ring-warning popup-ring-error').addClass(cfg.ringClass);
        $('#popup-siswa-status-badge').attr('class', `absolute -bottom-2 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase text-white shadow-sm border border-white ${cfg.statusBadgeBg}`);

        if (s) {
            $avatar.attr('src', s.foto || FOTO_CADANGAN);
            $('#popup-siswa-nama').text(s.nama);
            $('#popup-siswa-kelas').text(s.nama_kelas || 'Kelas Siswa').show();
            $('#popup-siswa-nis').text(s.nis ? `NIS: ${s.nis}` : (s.nisn ? `NISN: ${s.nisn}` : '-')).show();
            $('#popup-siswa-status-badge').text(s.status || (scanMode === 'masuk' ? 'HADIR' : 'PULANG'));

            const recordedTime = (scanMode === 'keluar' ? s.jam_keluar : s.jam_masuk) || nowTimeStr;
            $('#popup-time-text').text(recordedTime);
            $('#popup-status-text').text(cfg.statusText);
        } else {
            $avatar.attr('src', FOTO_CADANGAN);
            $('#popup-siswa-nama').text('Informasi Presensi');
            $('#popup-siswa-kelas').hide();
            $('#popup-siswa-nis').hide();
            $('#popup-siswa-status-badge').text('PERHATIAN');
            $('#popup-time-text').text(nowTimeStr);
            $('#popup-status-text').text(cfg.statusText);
        }

        // Auto-dismiss Countdown Progress Bar
        const totalMs = POPUP_DURATION_MS;
        let remainingMs = totalMs;
        const progressEl = document.getElementById('popup-progress-bar');
        const countTextEl = document.getElementById('popup-countdown-text');

        if (progressEl) progressEl.style.width = '100%';
        if (countTextEl) countTextEl.textContent = (remainingMs / 1000).toFixed(1) + 's';

        const stepMs = 50;
        popupInterval = setInterval(() => {
            remainingMs -= stepMs;
            if (remainingMs <= 0) {
                remainingMs = 0;
                clearInterval(popupInterval);
            }
            if (progressEl) {
                const pct = Math.max(0, (remainingMs / totalMs) * 100);
                progressEl.style.width = pct + '%';
            }
            if (countTextEl) {
                countTextEl.textContent = (remainingMs / 1000).toFixed(1) + 's';
            }
        }, stepMs);

        isPopupOpen = true;
        twModalShow('#modal-scan-popup');

        popupTimer = setTimeout(() => {
            closeScanPopup();
        }, totalMs);
    }

    window.closeScanPopup = function () {
        clearTimeout(popupTimer);
        clearInterval(popupInterval);
        twModalHide('#modal-scan-popup');
        setTimeout(() => {
            isPopupOpen = false;
        }, 220);
    };

    // ── Render Inline Scan Result Banner ───────────────
    function renderScanResult(res) {
        const $container = $('#scan-result-container');
        const ok   = res.status === true;
        const type = res.type || (ok ? 'success' : 'error');
        const s    = res.siswa || null;
        const menungguValidasiBanner = !!(res.data && Array.isArray(res.data.sesi_menunggu_validasi) && res.data.sesi_menunggu_validasi.length > 0);

        const theme = {
            check_in:    { bg: 'bg-emerald-50 border-emerald-200 text-emerald-900', badge: 'bg-emerald-600 text-white', icon: 'bi-check-circle-fill text-emerald-600', title: 'Absen Masuk Berhasil' },
            check_out:   { bg: 'bg-amber-50 border-amber-200 text-amber-900',       badge: 'bg-amber-600 text-white',   icon: 'bi-box-arrow-right text-amber-600',  title: 'Absen Pulang Berhasil' },
            already_in:  { bg: 'bg-amber-50 border-amber-200 text-amber-900',       badge: 'bg-amber-500 text-white',   icon: 'bi-exclamation-circle-fill text-amber-500', title: 'Sudah Absen Masuk' },
            already_out: { bg: 'bg-amber-50 border-amber-200 text-amber-900',       badge: 'bg-amber-500 text-white',   icon: 'bi-exclamation-circle-fill text-amber-500', title: 'Sudah Absen Pulang' },
            not_in:      { bg: 'bg-orange-50 border-orange-200 text-orange-900',    badge: 'bg-orange-500 text-white',  icon: 'bi-info-circle-fill text-orange-500',  title: 'Belum Absen Masuk' },
            izin:        { bg: 'bg-purple-50 border-purple-200 text-purple-900',    badge: 'bg-purple-600 text-white',  icon: 'bi-calendar-check text-purple-600',    title: 'Status Izin / Sakit' },
            jurnal_kurang: { bg: 'bg-rose-50 border-rose-200 text-rose-900',        badge: 'bg-rose-600 text-white',    icon: 'bi-journal-text text-rose-600',        title: menungguValidasiBanner ? 'Jurnal Menunggu Validasi Piket' : 'Jurnal Mengajar Belum Lengkap' },
            error:       { bg: 'bg-rose-50 border-rose-200 text-rose-900',          badge: 'bg-rose-600 text-white',    icon: 'bi-x-circle-fill text-rose-600',        title: 'Presensi Gagal' },
        }[type] || { bg: 'bg-slate-50 border-slate-200 text-slate-800', badge: 'bg-slate-600 text-white', icon: 'bi-info-circle-fill text-slate-600', title: 'Informasi' };

        let siswaHtml = '';
        if (s) {
            const timeInfo = s.jam_masuk
                ? `<span class="badge-tw badge-success"><i class="bi bi-box-arrow-in-right"></i> ${s.jam_masuk}</span>`
                : '';
            const outInfo = s.jam_keluar
                ? `<span class="badge-tw badge-warning"><i class="bi bi-box-arrow-right"></i> ${s.jam_keluar}</span>`
                : '';

            siswaHtml = `
            <div class="flex items-center gap-3 mt-3 pt-3 border-t border-black/5">
                <img src="${escHtml(s.foto)}" alt=""
                    class="w-12 h-12 rounded-2xl object-cover flex-shrink-0 shadow-sm border border-white"
                    onerror="this.onerror=null;this.src='${FOTO_CADANGAN}'">
                <div class="flex-1 min-w-0">
                    <h6 class="font-extrabold text-sm text-slate-900 m-0 leading-tight truncate">${escHtml(s.nama)}</h6>
                    <div class="flex items-center gap-2 mt-1">
                        ${s.nama_kelas ? `<span class="badge-tw badge-secondary text-[10px]">${escHtml(s.nama_kelas)}</span>` : ''}
                        <span class="text-[11px] text-slate-500 font-semibold">NIS: ${escHtml(s.nis || '-')}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    ${timeInfo}
                    ${outInfo}
                </div>
            </div>`;
        }

        $container.html(`
        <div class="result-animated rounded-2xl border p-4 shadow-md ${theme.bg}">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="bi ${theme.icon} text-2xl flex-shrink-0"></i>
                    <div>
                        <span class="font-bold text-xs uppercase tracking-wider block opacity-75">${theme.title}</span>
                        <p class="font-bold text-sm m-0 leading-tight">${escHtml(res.message || '')}</p>
                    </div>
                </div>
                <button type="button" onclick="$('#scan-result-container').addClass('hidden')"
                    class="text-slate-400 hover:text-slate-600 border-none bg-transparent cursor-pointer p-1">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
            ${siswaHtml}
        </div>`).removeClass('hidden');

        clearTimeout($container.data('hideTimer'));
        $container.data('hideTimer', setTimeout(() => $container.addClass('hidden'), 5000));
    }

    // ── Load History ───────────────────────────────────
    const HISTORY_PAGE = 100;
    let historyHasMore = false;
    let historyLoadingMore = false;

    function historyRowKey(r) {
        if (r.id_siswa) return 's:' + r.id_siswa;
        return 'g:' + (r.id_guru || r.user_id || r.id || '?');
    }

    function refreshLoadMoreUI() {
        const wrap = $('#history-load-more-wrap');
        if (historyHasMore && allHistoryRows.length) {
            $('#history-shown-info').text('Menampilkan ' + allHistoryRows.length + ' — masih ada lagi');
            $('#btn-history-more').show().prop('disabled', false).html('<i class="bi bi-chevron-down me-1"></i> Muat Lebih Banyak');
            wrap.removeClass('hidden');
        } else if (allHistoryRows.length > HISTORY_PAGE) {
            $('#history-shown-info').text('Menampilkan ' + allHistoryRows.length + ' — semua sudah tampil');
            $('#btn-history-more').prop('disabled', true).hide();
            wrap.removeClass('hidden');
        } else {
            wrap.addClass('hidden');
        }
    }

    window.loadMoreHistory = function () {
        if (historyLoadingMore || !historyHasMore) return;
        loadHistory(false, true);
    };

    window.loadHistory = function (showSkeleton = false, append = false) {
        if (showSkeleton && !append) {
            $('#history-list').html(`
                <div class="activity-card"><div class="skel-item w-11 h-11 flex-shrink-0"></div><div class="flex-1 space-y-2"><div class="skel-item h-3.5 w-3/4"></div><div class="skel-item h-2.5 w-1/2"></div></div></div>
                <div class="activity-card"><div class="skel-item w-11 h-11 flex-shrink-0"></div><div class="flex-1 space-y-2"><div class="skel-item h-3.5 w-3/4"></div><div class="skel-item h-2.5 w-1/2"></div></div></div>
            `);
        }

        const offset = append ? allHistoryRows.length : 0;
        if (append) {
            historyLoadingMore = true;
            $('#btn-history-more').prop('disabled', true).html('<i class="bi bi-arrow-clockwise me-1"></i> Memuat...');
        }

        const params = new URLSearchParams({
            tanggal: '<?= $tanggal ?>',
            id_kelas: selectedKelas || 0,
            limit: HISTORY_PAGE,
            offset: offset,
            h: '<?= esc($hash ?? '') ?>',
        });

        $.ajax({
                url: HISTORY_URL + '?' + params.toString(),
                type: 'GET',
                dataType: 'json',
                headers: PAGE_QR_HASH ? { 'X-QR-Hash': PAGE_QR_HASH } : {}
            })
            .done(res => {
                if (!res || !res.status) return;
                const incoming = res.data || [];
                if (append) {
                    // Hindari duplikat bila ada scan baru masuk di antara dua halaman
                    const seen = new Set(allHistoryRows.map(historyRowKey));
                    incoming.forEach(r => {
                        const k = historyRowKey(r);
                        if (!seen.has(k)) { seen.add(k); allHistoryRows.push(r); }
                    });
                } else {
                    allHistoryRows = incoming;
                }
                historyHasMore = !!res.has_more;
                renderHistory(allHistoryRows);
                refreshLoadMoreUI();
                updateStats(res.stats || {});
                // Terapkan kembali pencarian yang sedang aktif
                const q = $('#input-search-history').val() || '';
                if (q.trim() !== '') filterHistoryList(q);
            })
            .fail(() => {
                if (showSkeleton && !append) {
                    $('#history-list').html(`
                        <div class="text-center py-6 text-slate-400 text-xs">
                            <i class="bi bi-exclamation-circle text-xl block mb-1"></i>
                            Gagal memuat riwayat presensi.
                        </div>
                    `);
                }
            })
            .always(() => { historyLoadingMore = false; });
    };

    // ── Prepend Item Directly to History ──────────────
    function prependToHistory(siswa, scanMode) {
        const nowTime = witaHM();
        const existingIdx = allHistoryRows.findIndex(r => r.id_siswa == siswa.id_siswa);

        if (existingIdx >= 0) {
            if (scanMode === 'masuk') allHistoryRows[existingIdx].jam_masuk = nowTime;
            if (scanMode === 'keluar') allHistoryRows[existingIdx].jam_keluar = nowTime;
            const updatedItem = allHistoryRows.splice(existingIdx, 1)[0];
            allHistoryRows.unshift(updatedItem);
        } else {
            allHistoryRows.unshift({
                id_siswa: siswa.id_siswa,
                nama: siswa.nama,
                nis: siswa.nis,
                nama_kelas: siswa.nama_kelas,
                foto: siswa.foto,
                status: 'HADIR',
                jam_masuk: scanMode === 'masuk' ? nowTime : null,
                jam_keluar: scanMode === 'keluar' ? nowTime : null,
            });
        }
        renderHistory(allHistoryRows);
    }

    // ── Tab Filter Riwayat: Semua / Belum Pulang ─────
    // "Belum pulang" = sudah ada jam_masuk tapi jam_keluar masih kosong.
    let historyTab = 'all';

    function isBelumPulang(row) {
        return !!(row.jam_masuk) && !(row.jam_keluar);
    }

    function updateHistoryTabCounts(rows) {
        const all = (rows || []).length;
        const belum = (rows || []).filter(isBelumPulang).length;
        $('#tab-count-all').text(all);
        $('#tab-count-belum').text(belum);
    }

    window.setHistoryTab = function (tab, btn) {
        historyTab = tab;
        document.querySelectorAll('#tab-history-all, #tab-history-belum').forEach(b => {
            b.classList.remove('bg-white', 'text-blue-600', 'shadow-xs');
            b.classList.add('text-slate-600');
            const badge = b.querySelector('span[id^="tab-count"]');
            if (badge) {
                badge.classList.remove('bg-blue-100', 'text-blue-700');
                badge.classList.add('bg-slate-300/70', 'text-slate-700');
            }
        });
        if (btn) {
            btn.classList.add('bg-white', 'text-blue-600', 'shadow-xs');
            btn.classList.remove('text-slate-600');
            const activeBadge = btn.querySelector('span[id^="tab-count"]');
            if (activeBadge) {
                activeBadge.classList.add('bg-blue-100', 'text-blue-700');
                activeBadge.classList.remove('bg-slate-300/70', 'text-slate-700');
            }
        }
        renderHistory(allHistoryRows);
        // Terapkan kembali kata kunci pencarian yang sedang aktif
        const q = $('#input-search-history').val() || '';
        if (q.trim() !== '') filterHistoryList(q);
    };

    // ── Render History List ────────────────────────────
    function renderHistory(rows) {
        const $list = $('#history-list');
        const source = rows || [];
        $('#history-badge-count').text(source.length);
        updateHistoryTabCounts(source);

        const visible = (historyTab === 'belum_pulang') ? source.filter(isBelumPulang) : source;

        if (!source.length) {
            $list.html(`
            <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-slate-400 shadow-sm">
                <i class="bi bi-person-check text-4xl text-slate-300 block mb-2"></i>
                <p class="font-bold text-sm text-slate-600 m-0">Belum Ada Presensi Hari Ini</p>
                <p class="text-xs text-slate-400 mt-1 m-0">Arahkan kamera ke QR Card Siswa atau input manual.</p>
            </div>`);
            return;
        }

        if (!visible.length) {
            $list.html(`
            <div class="bg-white rounded-2xl border border-emerald-100 p-8 text-center text-slate-400 shadow-sm">
                <i class="bi bi-check-circle-fill text-4xl text-emerald-400 block mb-2"></i>
                <p class="font-bold text-sm text-slate-600 m-0">Semua Sudah Absen Pulang</p>
                <p class="text-xs text-slate-400 mt-1 m-0">Tidak ada siswa yang belum checkout hari ini.</p>
            </div>`);
            return;
        }

        let html = '';
        visible.forEach(row => {
            const jamMasuk  = row.jam_masuk  ? String(row.jam_masuk).substr(0, 5)  : '--:--';
            const jamKeluar = row.jam_keluar ? String(row.jam_keluar).substr(0, 5) : '--:--';
            const foto = row.foto ? escHtml(row.foto) : FOTO_CADANGAN;

            const statusMap = {
                'HADIR': { cls: 'badge-success', label: 'Hadir' },
                'SAKIT': { cls: 'badge-warning', label: 'Sakit' },
                'IZIN':  { cls: 'badge-purple',  label: 'Izin'  },
                'ALPA':  { cls: 'badge-danger',  label: 'Alpa'  },
                'BDR':   { cls: 'badge-info',    label: 'BDR'   },
            };
            const st = statusMap[row.status] || { cls: 'badge-secondary', label: row.status || 'Hadir' };

            html += `
            <div class="activity-card" data-nama="${escHtml((row.nama || '').toLowerCase())}" data-nis="${escHtml(row.nis || '')}">
                <img src="${foto}" alt="${escHtml(row.nama)}" class="activity-avatar"
                    onerror="this.onerror=null;this.src='${FOTO_CADANGAN}'">
                <div class="activity-info flex-1 min-w-0">
                    <h6 class="truncate">${escHtml(row.nama)}</h6>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="badge-tw ${st.cls} text-[9px] py-0 px-1.5">${st.label}</span>
                        <span class="text-[11px] text-slate-400 font-medium">${escHtml(row.nama_kelas || '-')}</span>
                        ${row.nis ? `<span class="text-[11px] text-slate-400">• ${escHtml(row.nis)}</span>` : ''}
                    </div>
                </div>
                <div class="activity-time-wrap">
                    <div class="flex flex-col items-end gap-1">
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            <i class="bi bi-box-arrow-in-right"></i> ${escHtml(jamMasuk)}
                        </span>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">
                            <i class="bi bi-box-arrow-right"></i> ${escHtml(jamKeluar)}
                        </span>
                    </div>
                </div>
            </div>`;
        });

        $list.html(html);
        if (typeof refreshLoadMoreUI === 'function') refreshLoadMoreUI();
    }

    // ── Filter History on Search Input ─────────────────
    window.filterHistoryList = function (query) {
        const q = (query || '').toLowerCase().trim();
        if (!q) {
            $('#history-list .activity-card').show();
            return;
        }
        $('#history-list .activity-card').each(function () {
            const nama = $(this).data('nama') || '';
            const nis  = $(this).data('nis') || '';
            if (nama.includes(q) || String(nis).includes(q)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    };

    // ── Update Stat Badges (dihapus dari UI — no-op agar pemanggil tetap aman) ──
    function updateStats(stats) {}

    // ── Manual Input Modal ─────────────────────────────
    window.openManualModal = function () {
        $('#manual-nis-input').val('');
        twModalShow('#modal-manual-input');
        setTimeout(() => $('#manual-nis-input').focus(), 250);
    };

    window.handleManualSubmit = function (e) {
        e.preventDefault();
        const nis = $('#manual-nis-input').val().trim();
        if (!nis) return;

        refreshModeTabs();
        const selectedMode = $('input[name="manual_mode"]:checked').val() || mode;
        const msg = blockedMsg(selectedMode);
        if (msg) {
            showScanPopup({ status: false, message: msg }, selectedMode);
            return;
        }
        twModalHide('#modal-manual-input');

        // Mode Fingerprint: kirim sebagai fingerprint_uid agar backend lookup fingerprint_uid.
        if (fpMode) {
            processFpScan(nis, selectedMode);
        }
        // Mode RFID: kirim sebagai rfid_value agar backend lookup nomor_rfid.
        else if (rfidMode) {
            processRfidScan(nis, selectedMode);
        } else {
            processScan(nis, selectedMode);
        }
    };

    // ── PROSES SCAN KARTU RFID (Mobile Manual) ─────────
    function processRfidScan(rfidValue, scanMode) {
        refreshModeTabs();
        const gateMsg = blockedMsg(scanMode);
        if (gateMsg) {
            const gateRes = { status: false, message: gateMsg };
            showScanPopup(gateRes, scanMode);
            renderScanResult(gateRes);
            return;
        }
        $('#scan-hint-text').text('Memproses kartu RFID...');

        postJson(SCAN_URL, {
            rfid_value: rfidValue,
            mode:       scanMode,
            id_kelas:   selectedKelas || 0,
            lat:        kioskLoc.lat,
            lng:        kioskLoc.lng,
            accuracy:   kioskLoc.accuracy,
            h:          '<?= esc($hash ?? '') ?>',
        })
        .done(res => {
            showScanPopup(res, scanMode);
            renderScanResult(res);
            if (res.status && res.siswa) {
                prependToHistory(res.siswa, scanMode);
            }
            loadHistory(false);
        })
        .fail((jqXHR, textStatus) => {
            const code = jqXHR ? jqXHR.status : 0;
            const resErr = jqXHR && jqXHR.responseJSON;
            const serverMsg = resErr && resErr.message ? String(resErr.message) : '';
            let msg;
            if (code === 0) {
                msg = 'Tidak ada koneksi ke server. Periksa internet lalu coba lagi.';
            } else if (code === 401) {
                msg = 'Sesi login habis. Silakan login ulang lalu scan kembali.';
            } else if (code === 403) {
                msg = serverMsg || 'Akses ditolak / token keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            } else if (code === 429) {
                msg = serverMsg || 'Terlalu banyak scan. Tunggu sebentar lalu coba lagi.';
            } else if (serverMsg) {
                msg = serverMsg + ' (HTTP ' + code + ')';
            } else {
                msg = 'Gagal terhubung ke server presensi (HTTP ' + (code || '?') + ').';
            }
            showScanPopup({ status: false, message: msg }, scanMode);
            renderScanResult({ status: false, message: msg });
        })
        .always(() => {
            $('#scan-hint-text').text('Arahkan QR Card Siswa ke kamera');
        });
    }

    // ── Smooth Scroll Helpers ──────────────────────────
    window.scrollToScanner = function () {
        document.getElementById('scanner-section').scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    window.scrollToHistory = function () {
        document.getElementById('history-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    // ── HTML Escape Helper ─────────────────────────────
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    // ── Live Digital Clock ─────────────────────────────
    // Jam server (WITA), bukan jam perangkat: halaman ini menampilkan batas
    // buka/tutup berlabel WITA, jadi jamnya harus memakai acuan yang sama.
    function updateClock() {
        const now = witaNow();
        const pad = n => String(n).padStart(2, '0');
        const clockEl = document.getElementById('digitalClock');
        if (clockEl) {
            clockEl.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ── RFID / FP MODE UI (Mobile Manual) ──────────────
    let fpMode = localStorage.getItem('presensi_qr_fp') === '1';
    function updateRfidModeMobileUI() {
        const $btnRfid = $('#btn-toggle-rfid-mobile');
        const $btnFp = $('#btn-toggle-fp-mobile');
        if (rfidMode) {
            $btnRfid.addClass('bg-emerald-500 text-white border-emerald-400').removeClass('bg-slate-100 text-slate-600 border-slate-300');
        } else {
            $btnRfid.removeClass('bg-emerald-500 text-white border-emerald-400').addClass('bg-slate-100 text-slate-600 border-slate-300');
        }
        if (fpMode) {
            $btnFp.addClass('bg-purple-500 text-white border-purple-400').removeClass('bg-slate-100 text-slate-600 border-slate-300');
        } else {
            $btnFp.removeClass('bg-purple-500 text-white border-purple-400').addClass('bg-slate-100 text-slate-600 border-slate-300');
        }
    }
    $('#btn-toggle-rfid-mobile').on('click', function () {
        rfidMode = !rfidMode;
        fpMode = false; // saling eksklusif
        localStorage.setItem('presensi_qr_rfid', rfidMode ? '1' : '0');
        localStorage.setItem('presensi_qr_fp', fpMode ? '1' : '0');
        updateRfidModeMobileUI();
    });
    $('#btn-toggle-fp-mobile').on('click', function () {
        fpMode = !fpMode;
        rfidMode = false; // saling eksklusif
        localStorage.setItem('presensi_qr_rfid', rfidMode ? '1' : '0');
        localStorage.setItem('presensi_qr_fp', fpMode ? '1' : '0');
        updateRfidModeMobileUI();
    });
    updateRfidModeMobileUI();

    // ── PROSES SCAN FINGERPRINT (Mobile Manual) ─────────
    function processFpScan(fpUid, scanMode) {
        refreshModeTabs();
        const gateMsg = blockedMsg(scanMode);
        if (gateMsg) {
            const gateRes = { status: false, message: gateMsg };
            showScanPopup(gateRes, scanMode);
            renderScanResult(gateRes);
            return;
        }
        $('#scan-hint-text').text('Memproses fingerprint...');

        postJson(SCAN_URL, {
            fingerprint_uid: fpUid,
            mode:            scanMode,
            id_kelas:        selectedKelas || 0,
            lat:             kioskLoc.lat,
            lng:             kioskLoc.lng,
            accuracy:        kioskLoc.accuracy,
            h:               '<?= esc($hash ?? '') ?>',
        })
        .done(res => {
            showScanPopup(res, scanMode);
            renderScanResult(res);
            if (res.status && res.siswa) {
                prependToHistory(res.siswa, scanMode);
            }
            loadHistory(false);
        })
        .fail((jqXHR, textStatus) => {
            const code = jqXHR ? jqXHR.status : 0;
            const resErr = jqXHR && jqXHR.responseJSON;
            const serverMsg = resErr && resErr.message ? String(resErr.message) : '';
            let msg;
            if (code === 0) {
                msg = 'Tidak ada koneksi ke server. Periksa internet lalu coba lagi.';
            } else if (code === 401) {
                msg = 'Sesi login habis. Silakan login ulang lalu scan kembali.';
            } else if (code === 403) {
                msg = serverMsg || 'Akses ditolak / token keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            } else if (code === 429) {
                msg = serverMsg || 'Terlalu banyak scan. Tunggu sebentar lalu coba lagi.';
            } else if (serverMsg) {
                msg = serverMsg + ' (HTTP ' + code + ')';
            } else {
                msg = 'Gagal terhubung ke server presensi (HTTP ' + (code || '?') + ').';
            }
            showScanPopup({ status: false, message: msg }, scanMode);
            renderScanResult({ status: false, message: msg });
        })
        .always(() => {
            $('#scan-hint-text').text('Arahkan QR Card Siswa ke kamera');
        });
    }

    // ── Initialization ─────────────────────────────────
    $(function () {
        initCamera();
        loadHistory(false);
    });

})();
</script>
<?= $this->endSection() ?>
