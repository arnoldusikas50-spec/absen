<?= $this->extend('layouts/mobile_siswa') ?>

<?= $this->section('styles') ?>
<style>
    * { box-sizing: border-box; }

    /* ── Base container ── */
    .s-wrap {
        width: 100%;
        max-width: 100%;
        margin: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background: #f8fafc;
    }

    /* ── Hero section — konsisten dengan /presensi ── */
    .s-hero-section {
        color: #fff;
        position: relative;
        overflow: hidden;
        border-radius: 0 0 32px 32px;
        background: radial-gradient(130% 110% at 85% 0%, var(--accent-300, #c4b5fd) 0%, var(--accent-400, #a78bfa) 22%, var(--accent-500, #8b5cf6) 52%, var(--accent-600, #7c3aed) 78%, var(--accent-700, #6d28d9) 100%);
        padding: calc(22px + var(--safe-area-inset-top, 0px)) 20px 52px;
        box-shadow: 0 12px 36px rgb(var(--accent-600-rgb, 124, 58, 237) / 0.25);
    }

    /* Dekorasi hero */
    .s-hero-decorations { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 1; }
    .s-hero-decor-svg   { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
    .s-hero-pattern-dots {
        position: absolute; top: 0; right: 0; pointer-events: none;
        width: 150px; height: 130px;
        background-image: radial-gradient(rgba(255,255,255,.22) 1.5px, transparent 1.5px);
        background-size: 12px 12px; opacity: .7;
        mask-image: radial-gradient(circle at 100% 0%, black, transparent 80%);
        -webkit-mask-image: radial-gradient(circle at 100% 0%, black, transparent 80%);
    }
    .s-hero-sparkle {
        position: absolute; color: #fff; line-height: 1; user-select: none;
        font-size: 14px; filter: drop-shadow(0 0 6px rgba(255,255,255,.9));
        animation: sHeroSparkle 4s ease-in-out infinite alternate;
    }
    .s-hero-sparkle.sp1 { top:16%; right:26%; font-size:14px; opacity:.9; animation-delay:0s; }
    .s-hero-sparkle.sp2 { top:60%; right:12%; font-size:11px; opacity:.65; animation-delay:1.5s; }
    .s-hero-sparkle.sp3 { top:22%; left:48%; font-size:13px; opacity:.8; animation-delay:2.3s; }
    @keyframes sHeroSparkle {
        0%   { transform: translateY(0) scale(.9); opacity: .4; }
        100% { transform: translateY(-5px) scale(1.15); opacity: 1; }
    }
    .s-hero-bubble {
        position: absolute; border-radius: 9999px;
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(4px);
    }
    .s-hero-bubble.b1 { width:46px; height:46px; top:8%; left:50%; animation: sBubbleFloat 6s ease-in-out infinite alternate; }
    .s-hero-bubble.b2 { width:30px; height:30px; bottom:20%; right:22%; animation: sBubbleFloat 5s ease-in-out infinite alternate-reverse; }
    @keyframes sBubbleFloat { 0% { transform: translate(0,0); } 100% { transform: translate(4px,-6px); } }

    /* Profil & greeting */
    .s-user-profile { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; position: relative; z-index: 10; }
    .s-avatar-wrapper { position: relative; }
    .s-avatar-main {
        object-fit: cover; width: 52px; height: 52px;
        border-radius: 18px; border: 2.5px solid rgba(255,255,255,.9);
        box-shadow: 0 6px 16px rgba(0,0,0,.15); background: #fff;
    }
    .s-avatar-initial {
        width: 52px; height: 52px; border-radius: 18px;
        border: 2.5px solid rgba(255,255,255,.9);
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
        font-weight: 900; font-size: 22px; color: #fff;
        box-shadow: 0 6px 16px rgba(0,0,0,.15);
    }
    .s-greeting-text h4 { margin: 0; font-weight: 800; font-size: 27px; letter-spacing: -.5px; text-shadow: 0 1px 3px rgba(0,0,0,.1); }
    .s-greeting-text p  { margin: 0; font-weight: 500; opacity: .92; font-size: 13.5px; letter-spacing: .1px; }
    .s-motivation-box {
        display: inline-flex; align-items: center; gap: 6px;
        font-weight: 600; color: #fff; border-radius: 14px; padding: 6px 14px;
        background: rgba(255,255,255,.18); backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.28);
        font-size: 11.5px; line-height: 1.45; max-width: 100%; text-align: left;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .s-motivation-box i { flex-shrink: 0; }

    /* ── Status card (floating over hero) — konsisten dengan /presensi ── */
    .s-status-card {
        background: #ffffff; border-radius: 22px;
        position: relative; z-index: 20;
        padding: 18px 16px 16px;
        margin: -38px 16px 0;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.10);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }

    /* Jam digital */
    .s-clock-display { text-align: center; padding-top: 2px; padding-bottom: 8px; }
    .s-server-label {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        font-size: 10px; font-weight: 800; letter-spacing: 1.6px;
        color: #94a3b8; text-transform: uppercase; margin-bottom: 2px;
    }
    .s-server-label .s-live-dot {
        width: 7px; height: 7px; border-radius: 9999px; background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34,197,94,.18);
        animation: sLivePulse 1.8s ease-in-out infinite;
    }
    @keyframes sLivePulse { 0%,100% { opacity: 1; } 50% { opacity: .45; } }
    #sDigitalClock {
        font-weight: 900; font-variant-numeric: tabular-nums; line-height: 1.1;
        margin-bottom: 8px; font-size: clamp(38px, 11.5vw, 46px);
        font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--accent-500, #8b5cf6); letter-spacing: 1px;
    }
    #sDigitalDate {
        display: inline-flex; align-items: center; gap: 4px;
        font-weight: 600; background: #f8fafc; border-radius: 9999px; padding: 3.5px 12px;
        text-transform: capitalize; font-size: 11.5px; color: #64748b; border: 1px solid #e2e8f0;
    }
    .s-clock-tz {
        display: block; margin: -4px 0 6px;
        font-size: 10.5px; font-weight: 700; letter-spacing: 1.4px; color: #94a3b8;
    }

    /* Mini status hadir/pulang */
    .s-mini-grid {
        display: grid; gap: 8px; margin-top: 12px; padding-top: 12px;
        grid-template-columns: 1fr 1fr; border-top: 1px dashed #e2e8f0;
    }
    .s-mini-box {
        padding: 12px 8px 10px; border-radius: 18px; text-align: center;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        border: 1px solid; transition: transform .15s ease;
    }
    .s-mini-box.box-masuk  { background: #f0fdf4; border-color: #dcfce7; }
    .s-mini-box.box-pulang { background: #f8fafc; border-color: #eef2f7; }
    .s-mini-box .box-title { font-weight: 800; text-transform: uppercase; margin-bottom: 4px; font-size: 10px; letter-spacing: .6px; display: inline-flex; align-items: center; gap: 4px; }
    .s-mini-box.box-masuk  .box-title { color: #15803d; }
    .s-mini-box.box-pulang .box-title { color: #64748b; }
    .s-mini-box .box-value { font-weight: 900; color: #0f172a; font-size: 21px; letter-spacing: -.3px; font-variant-numeric: tabular-nums; }
    .s-box-pill {
        display: inline-flex; align-items: center; gap: 4px;
        margin-top: 6px; padding: 3px 10px; border-radius: 9999px; font-size: 10px; font-weight: 800;
    }
    .s-box-pill.pill-ok   { background: #dcfce7; color: #15803d; }
    .s-box-pill.pill-warn { background: #fef3c7; color: #b45309; }
    .s-box-pill.pill-mute { background: #f1f5f9; color: #64748b; }

    /* ── Konten utama (di bawah floating card) ── */
    .s-main-content { padding: 16px 16px 0; }

    /* ── Progress bar bulan ── */
    .s-progress-wrap {
        margin-bottom: 16px;
        background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15,23,42,0.03); padding: 14px 16px;
    }
    .s-progress-row {
        display: flex; align-items: center; justify-content: space-between;
        font-size: 0.75rem; margin-bottom: 8px;
    }
    .s-progress-label { color: #64748b; font-weight: 700; }
    .s-progress-label span { color: #0f172a; font-weight: 900; }
    .s-progress-pct { color: var(--accent-600, #7c3aed); font-weight: 900; font-size: 0.9rem; }
    .s-hero-bar { width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .s-hero-bar-fill { height: 100%; background: #22c55e; border-radius: 999px; width: 0%; transition: width 0.5s ease; }

    /* ── Month filter ── */
    .s-month-filter {
        display: flex; align-items: center; gap: 0.75rem;
        border-radius: 14px; background: #fff; border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03); padding: 0.6rem 1rem;
        margin-bottom: 16px;
    }
    .s-month-icon { color: var(--accent-600, #7c3aed); font-size: 1.1rem; flex-shrink: 0; }
    .s-month-select {
        border: none; background: transparent; flex: 1; outline: none;
        font-weight: 700; color: var(--accent-600, #7c3aed); font-size: 0.875rem;
        padding: 0.4rem; appearance: none; cursor: pointer;
    }
    .s-month-select option { background: #fff; color: #0f172a; }
    .s-month-refresh {
        width: 2.2rem; height: 2.2rem; border-radius: 0.5rem;
        background: #f8fafc; border: 1px solid #e2e8f0;
        color: var(--accent-600, #7c3aed); display: inline-flex;
        align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0;
    }
    .s-month-refresh:hover { background: #e2e8f0; }

    /* ── Section headers ── */
    .s-section-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px; padding: 0;
    }
    .s-section-title {
        font-size: 11.5px; font-weight: 800; letter-spacing: 1.4px;
        color: #64748b; text-transform: uppercase;
        display: flex; align-items: center; gap: 6px;
    }

    /* ── Stats grid ── */
    .s-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.65rem;
        margin-bottom: 16px;
    }
    .s-stat-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        padding: 0.85rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .s-stat-card:active { transform: scale(0.98); }
    .s-stat-card.s-stat-span-2 {
        grid-column: span 2;
    }
    .s-stat-icon {
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .s-stat-value { font-weight: 900; color: #0f172a; font-size: 1.25rem; line-height: 1.2; }
    .s-stat-label {
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        font-size: 0.65rem;
        letter-spacing: 0.05em;
        margin-top: 0.1rem;
    }

    /* ── Card base ── */
    .s-card {
        background: #fff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .s-card-body {
        padding: 1.15rem 1.25rem;
    }

    /* ── Denda ── */
    .s-denda-card.denda-ada {
        border-color: #fecdd3;
        background: #fffafa;
    }

    .s-denda-card.denda-bersih {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .s-denda-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        gap: 0.5rem;
    }

    .s-denda-left { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }

    .s-denda-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.2rem;
    }

    .s-denda-title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.2;
    }

    .s-denda-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 9999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.68rem;
        font-weight: 800;
        margin-top: 0.3rem;
    }

    .s-denda-right { text-align: right; flex-shrink: 0; }

    .s-denda-amount {
        font-weight: 900;
        color: #0f172a;
        font-size: 1.35rem;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    .s-denda-cap {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 0.15rem;
    }

    .s-denda-sub {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.75rem;
        min-height: 1rem;
    }

    .s-denda-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.6rem;
    }

    .s-denda-item {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        padding: 0.65rem 0.85rem;
    }

    .s-denda-item-label {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.04em;
    }

    .s-denda-item-value {
        font-size: 0.95rem;
        font-weight: 900;
        color: #0f172a;
    }

    .s-denda-note {
        margin-top: 0.75rem;
        padding-top: 0.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
    }

    /* ── Skor Disiplin ── */
    .s-skor-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        gap: 0.5rem;
    }

    .s-skor-left { display: flex; align-items: center; gap: 0.75rem; }

    .s-skor-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.2rem;
    }

    .s-skor-title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.2;
    }

    .s-skor-label {
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        font-size: 0.68rem;
        letter-spacing: 0.05em;
        margin-top: 0.2rem;
    }

    .s-skor-ring {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 1rem;
        color: #fff;
        box-shadow: 0 6px 14px rgba(15,23,42,0.12);
        flex-shrink: 0;
    }

    .s-skor-desc {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
        margin-top: 0.5rem;
        min-height: 1rem;
    }

    /* ── Presensi Hari Ini ── */
    .s-today-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        gap: 0.5rem;
    }

    .s-today-left { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }

    .s-today-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .s-today-title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.2;
    }

    .s-today-date {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 0.2rem;
    }
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
        margin: 0;
    }

    .s-today-date {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 0.1rem;
    }

    .s-today-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border-radius: 9999px;
        padding: 0.2rem 0.6rem;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .s-today-times {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .s-today-time {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 0.75rem;
        padding: 0.75rem 0.9rem;
    }

    .s-today-time-label {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .s-today-time-value {
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
    }

    .s-today-note {
        margin-top: 0.75rem;
        padding-top: 0.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        color: #b45309;
        font-weight: 600;
    }

    /* ── Riwayat / Timeline ── */
    .s-timeline-wrap { padding: 0 0 1rem; }

    .s-timeline-title {
        font-weight: 800;
        color: #334155;
        font-size: 0.875rem;
        letter-spacing: 0.03em;
        margin: 0 0 1rem;
        text-transform: uppercase;
    }

    .s-timeline-item {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
        position: relative;
    }

    .s-timeline-item::before {
        content: '';
        position: absolute;
        left: 0.95rem;
        top: 1.1rem;
        bottom: -0.5rem;
        width: 2px;
        background: #e2e8f0;
        border-radius: 1px;
        z-index: 0;
    }

    .s-timeline-item:last-child::before { display: none; }
    .s-timeline-item:last-child { margin-bottom: 0; }

    .s-date-col {
        flex-shrink: 0;
        width: 2.2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 1;
    }

    .s-date-circle {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 0.5rem;
        background: #fff;
        border: 2px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(15,23,42,0.06);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .s-date-day {
        font-weight: 900;
        color: #334155;
        font-size: 0.95rem;
        line-height: 1;
    }

    .s-date-month {
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        font-size: 0.6rem;
        margin-top: 0.2rem;
    }

    .s-riwayat-card {
        flex: 1;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        padding: 0.75rem 1rem;
        position: relative;
    }

    .s-riwayat-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.4rem;
    }

    .s-riwayat-time {
        font-weight: 900;
        color: #0f172a;
        font-size: 0.9rem;
    }

    .s-riwayat-status {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border-radius: 9999px;
        padding: 0.2rem 0.6rem;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .s-riwayat-ket {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 0.4rem;
        min-height: 0.9rem;
    }

    /* ── Badges ── */
    .s-badge-hadir   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .s-badge-izin    { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .s-badge-sakit   { background: #fef3c7; color: #b45309; border: 1px solid #fed7aa; }
    .s-badge-alpha   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .s-badge-bdr     { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .s-badge-tanpa   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .s-badge-lainnya { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }

    /* ── Empty state ── */
    .s-empty-state {
        text-align: center;
        color: #94a3b8;
        padding: 2rem 1rem;
    }

    .s-empty-icon {
        display: block;
        font-size: 2.2rem;
        margin: 0 auto 1rem;
        opacity: 0.45;
    }

    /* ── Modal QR ── */
    .s-qr-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15,23,42,0.75);
        backdrop-filter: blur(8px);
        padding: 1rem;
        transition: opacity 0.25s ease;
    }

    .s-qr-modal.open { display: flex; }

    .s-qr-card {
        max-width: 22rem;
        width: 100%;
        background: #ffffff;
        border-radius: 20px;
        padding: 1.5rem 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 25px 60px -12px rgba(15,23,42,0.35);
        transform: translateY(1rem);
        opacity: 0;
        transition: transform 0.25s ease, opacity 0.25s ease;
        position: relative;
    }

    .s-qr-modal.open .s-qr-card { transform: translateY(0); opacity: 1; }

    .s-qr-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 20;
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 50%;
        border: none;
        background: #f1f5f9;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .s-qr-close:hover { background: #e2e8f0; color: #0f172a; }

    .s-qr-header { text-align: center; margin-bottom: 1rem; }

    .s-qr-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        background: #f3e8ff;
        color: #6d28d9;
        font-size: 0.7rem;
        font-weight: 800;
        margin-bottom: 0.6rem;
    }

    .s-qr-title {
        font-weight: 800;
        color: #111827;
        font-size: 1.15rem;
        margin: 0 0 0.25rem;
        line-height: 1.2;
    }

    .s-qr-meta {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
        margin: 0 0 0.4rem;
        font-family: ui-monospace, SFMono-Regular, monospace;
    }

    .s-qr-school {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .s-qr-box-wrap {
        padding: 0.75rem;
        border-radius: 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .s-qr-box {
        width: 6.5rem;
        height: 6.5rem;
        border-radius: 0.5rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .s-qr-value {
        font-family: ui-monospace, SFMono-Regular, monospace;
        font-weight: 800;
        color: #111827;
        font-size: 0.85rem;
        margin-top: 0.5rem;
        text-align: center;
        line-height: 1.3;
    }

    .s-qr-hint {
        font-size: 0.7rem;
        color: #4f46e5;
        font-weight: 600;
        margin-top: 0.4rem;
        text-align: center;
    }

    .s-qr-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .s-qr-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border-radius: 0.75rem;
        font-weight: 800;
        font-size: 0.75rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        padding: 0.75rem 0;
    }

    .s-qr-btn-cetak {
        background: #4338ca;
        color: #fff;
        box-shadow: 0 4px 12px rgba(55,48,163,0.3);
    }

    .s-qr-btn-cetak:hover { background: #312e81; }

    .s-qr-btn-download {
        background: #111827;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    .s-qr-btn-download:hover { background: #000; }

    .s-qr-btn-close { background: #f1f5f9; color: #374151; }

    .s-qr-btn-close:hover { background: #e5e7eb; }

    /* ── Utility ── */
    .text-\[11px\] { font-size: 11px; }
    .text-\[10px\] { font-size: 10px; }
    .bg-white\/20 { background-color: rgba(255,255,255,0.20); }
    .border-white\/30 { border-color: rgba(255,255,255,0.30); }
    .bg-indigo-600 { background-color: #4338ca; }
    .text-indigo-600 { color: #4338ca; }
    .border-indigo-200 { border-color: #c7d2fe; }
    .font-black { font-weight: 900; }
    .leading-none { line-height: 1; }
    .tracking-wider { letter-spacing: 0.05em; }
    .opacity-50 { opacity: 0.5; }

</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Jam server untuk sinkronisasi clock siswa -->
<script src="<?= base_url('assets/js/wita-clock.js?v=' . (@filemtime(FCPATH . 'assets/js/wita-clock.js') ?: time())) ?>"></script>
<script>witaInit('<?= date('Y-m-d H:i:s') ?>');</script>
<?php
$hour = (int) date('G');
$salam = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
$firstName = explode(' ', trim($siswa->nama ?? 'Siswa'))[0];
$namaSiswa = trim($siswa->nama ?? 'Siswa');
$initial = mb_strtoupper(mb_substr($namaSiswa, 0, 1));
$qrSiswaVal = !empty($siswa->qr_value) ? $siswa->qr_value : (!empty($siswa->nis) ? $siswa->nis : (!empty($siswa->nisn) ? $siswa->nisn : (string)$siswa->id_siswa));
// Status hari ini untuk mini-status-box
$todayJamMasuk = ($today && !empty($today->jam_masuk)) ? substr($today->jam_masuk, 0, 5) : null;
$todayJamKeluar = ($today && !empty($today->jam_keluar)) ? substr($today->jam_keluar, 0, 5) : null;
?>
<div class="s-wrap">

    <!-- ── Hero Section (bergradient, mirip /presensi) ── -->
    <div class="s-hero-section">
        <!-- Dekorasi latar -->
        <div class="s-hero-decorations" aria-hidden="true">
            <svg class="s-hero-decor-svg" viewBox="0 0 500 220" preserveAspectRatio="none">
                <path d="M0,70 C160,150 340,10 500,60 L500,0 L0,0 Z" fill="rgba(255,255,255,0.08)" />
                <path d="M0,130 C190,50 310,160 500,95 L500,0 L0,0 Z" fill="rgba(255,255,255,0.05)" />
                <circle cx="440" cy="30" r="55" fill="rgba(255,255,255,0.12)" />
                <circle cx="70" cy="180" r="75" fill="rgba(255,255,255,0.08)" />
            </svg>
            <div class="s-hero-pattern-dots"></div>
            <div class="s-hero-bubble b1"></div>
            <div class="s-hero-bubble b2"></div>
            <div class="s-hero-sparkle sp1">✦</div>
            <div class="s-hero-sparkle sp2">✦</div>
            <div class="s-hero-sparkle sp3">✨</div>
        </div>

        <!-- Profil & Greeting -->
        <div class="s-user-profile">
            <div class="s-greeting-text">
                <p><?= $salam ?>,</p>
                <h4><?= esc($firstName) ?>,</h4>
            </div>
            <div class="s-avatar-wrapper">
                <?php if (!empty($siswa->foto_url)): ?>
                    <img src="<?= esc($siswa->foto_url) ?>" alt="<?= esc($namaSiswa) ?>" class="s-avatar-main"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="s-avatar-initial" style="display:none;"><?= esc($initial) ?></div>
                <?php else: ?>
                    <div class="s-avatar-initial"><?= esc($initial) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kotak motivasi -->
        <div class="s-motivation-box" role="status">
            <i class="bi bi-stars" aria-hidden="true"></i>
            <span>NIS <?= esc($siswa->nis ?: '-') ?> &bull; <?= esc($siswa->nama_kelas ?: '-') ?></span>
        </div>
    </div>

    <!-- ── Status Card (floating, konsisten dengan /presensi) ── -->
    <div class="s-status-card" id="s-status-card">
        <!-- Jam digital -->
        <div class="s-clock-display">
            <div class="s-server-label">
                <span class="s-live-dot"></span>Jam Server
            </div>
            <div id="sDigitalClock"><?= date('H:i:s') ?></div>
            <span class="s-clock-tz">WITA</span>
            <div id="sDigitalDate">Memuat waktu...</div>
        </div>

        <!-- Mini status presensi hari ini -->
        <div class="s-mini-grid">
            <?php
            if ($todayJamMasuk !== null) {
                $masukBadge = '<span class="s-box-pill pill-ok"><i class="bi bi-check-circle-fill"></i>Hadir</span>';
            } else {
                $masukBadge = '<span class="s-box-pill pill-mute">Belum Absen</span>';
            }
            if ($todayJamKeluar !== null) {
                $pulangBadge = '<span class="s-box-pill pill-mute"><i class="bi bi-check-circle"></i>Selesai</span>';
            } elseif ($todayJamMasuk !== null) {
                $pulangBadge = '<span class="s-box-pill pill-mute">Belum Pulang</span>';
            } else {
                $pulangBadge = '<span class="s-box-pill pill-mute">--:--</span>';
            }
            ?>
            <div class="s-mini-box box-masuk">
                <span class="box-title"><i class="bi bi-arrow-down-left"></i>Jam Masuk</span>
                <span class="box-value"><?= $todayJamMasuk ?? '--:--' ?></span>
                <?= $masukBadge ?>
            </div>
            <div class="s-mini-box box-pulang">
                <span class="box-title"><i class="bi bi-arrow-up-right"></i>Jam Pulang</span>
                <span class="box-value"><?= $todayJamKeluar ?? '--:--' ?></span>
                <?= $pulangBadge ?>
            </div>
        </div>
    </div>

    <!-- ── Konten utama ── -->
    <div class="s-main-content" id="riwayat">

    <!-- Progress kehadiran bulan ini -->
    <div class="s-progress-wrap">
        <div class="s-progress-row">
            <span class="s-progress-label">Kehadiran Bulan <span><?= esc(date('M Y', strtotime($bulan . '-01'))) ?></span></span>
            <span class="s-progress-pct"><span id="statPersen"><?= esc($stats['persen'] ?? 0) ?></span>%</span>
        </div>
        <div class="s-hero-bar">
            <div id="barPersen" class="s-hero-bar-fill" style="width: <?= esc($stats['persen'] ?? 0) ?>%"></div>
        </div>
    </div>

    <!-- Month filter -->
    <div class="s-month-filter">
        <i class="bi bi-calendar-month s-month-icon"></i>
        <form id="filterForm" action="" method="get" class="grow">
            <select name="bulan" id="filterBulan" class="s-month-select" onchange="this.form.submit()">
                <?php
                $bulanList = [];
                for ($m = 1; $m <= 12; $m++) {
                    $key = sprintf('%04d-%02d', date('Y'), $m);
                    $bulanList[$key] = date('F Y', strtotime($key . '-01'));
                }
                $shown = []; $cnt = 0; $cur = date('Y-m');
                foreach (array_reverse(array_keys($bulanList)) as $k) {
                    if ($k <= $cur) { $shown[$k] = $bulanList[$k]; $cnt++; }
                    if ($cnt >= 6) break;
                }
                foreach ($shown as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $bulan == $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="v" value="3">
        </form>
        <button type="button" class="s-month-refresh" id="btnRefreshBulan" title="Refresh">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>

    <!-- Stats -->
    <?php
    $hasBdr   = (int) ($stats['bdr'] ?? 0) > 0;
    $hasTanpa = (int) ($stats['tanpa_keterangan'] ?? 0) > 0;
    $bdrSpan  = ($hasBdr && !$hasTanpa) ? 's-stat-span-2' : '';
    $tanpaSpan = ($hasTanpa && !$hasBdr) ? 's-stat-span-2' : '';
    ?>
    <div class="s-stats-grid">
        <div class="s-stat-card">
            <div class="s-stat-icon bg-emerald-50 text-emerald-600"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="s-stat-value" id="statHadir"><?= (int) ($stats['hadir'] ?? 0) ?></div>
                <div class="s-stat-label">Hadir</div>
            </div>
        </div>
        <div class="s-stat-card">
            <div class="s-stat-icon bg-sky-50 text-sky-600"><i class="bi bi-envelope-fill"></i></div>
            <div>
                <div class="s-stat-value" id="statIzin"><?= (int) ($stats['izin'] ?? 0) ?></div>
                <div class="s-stat-label">Izin</div>
            </div>
        </div>
        <div class="s-stat-card">
            <div class="s-stat-icon bg-amber-50 text-amber-600"><i class="bi bi-heart-pulse-fill"></i></div>
            <div>
                <div class="s-stat-value" id="statSakit"><?= (int) ($stats['sakit'] ?? 0) ?></div>
                <div class="s-stat-label">Sakit</div>
            </div>
        </div>
        <div class="s-stat-card">
            <div class="s-stat-icon bg-rose-50 text-rose-600"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="s-stat-value" id="statAlpha"><?= (int) ($stats['alpha'] ?? 0) ?></div>
                <div class="s-stat-label">Alpha</div>
            </div>
        </div>
        <?php if ($hasBdr): ?>
        <div class="s-stat-card <?= $bdrSpan ?>">
            <div class="s-stat-icon bg-teal-50 text-teal-600"><i class="bi bi-house-door-fill"></i></div>
            <div>
                <div class="s-stat-value" id="statBdr"><?= (int) ($stats['bdr'] ?? 0) ?></div>
                <div class="s-stat-label">BDR</div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($hasTanpa): ?>
        <div class="s-stat-card <?= $tanpaSpan ?>">
            <div class="s-stat-icon bg-slate-100 text-slate-500"><i class="bi bi-question-circle-fill"></i></div>
            <div>
                <div class="s-stat-value" id="statTanpaKet"><?= (int) ($stats['tanpa_keterangan'] ?? 0) ?></div>
                <div class="s-stat-label">Tanpa Ket.</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Denda -->
    <?php
    $d = is_array($denda ?? null) ? $denda : null;
    $netto   = $d ? (float) ($d['netto'] ?? 0) : 0;
    $alpha   = $d ? (int) ($d['alpha'] ?? 0) : 0;
    $bolos   = $d ? (int) ($d['bolos'] ?? 0) : 0;
    $tanpaB  = $d ? (int) ($d['tanpa_baris'] ?? 0) : 0;
    $cap     = $d && isset($d['cap']) ? (float) $d['cap'] : null;
    $ada     = $netto > 0 || $alpha > 0 || $bolos > 0;
    ?>
    <?php if ($d !== null): ?>
    <div class="s-card s-denda-card <?= $ada ? 'denda-ada' : 'denda-bersih' ?>" id="cardDenda">
        <div class="s-card-body">
            <div class="s-denda-top">
                <div class="s-denda-left">
                    <div class="s-denda-avatar <?= $ada ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' ?>" id="dendaAvatar">
                        <i class="bi bi-<?= $ada ? 'wallet2' : 'check-circle-fill' ?>"></i>
                    </div>
                    <div>
                        <div class="s-denda-title">Tagihan Denda</div>
                        <div class="s-denda-chip <?= $ada ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>" id="dendaStatus">
                            <i class="bi bi-<?= $ada ? 'clock' : 'check-circle-fill' ?>"></i>
                            <span><?= $ada ? 'Belum dibayar' : 'Bersih' ?></span>
                        </div>
                    </div>
                </div>
                <div class="s-denda-right">
                    <div class="s-denda-amount" id="dendaNetto">Rp <?= number_format($netto, 0, ',', '.') ?></div>
                    <?php if ($cap > 0): ?>
                        <div class="s-denda-cap">Plafon Rp <?= number_format($cap, 0, ',', '.') ?>/bln</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="s-denda-sub" id="dendaSubtitle">
                <?= $ada ? 'Jumlah denda yang harus dibayar bulan ini' : 'Tidak ada pelanggaran denda bulan ini' ?>
            </div>
            <?php if ($ada): ?>
            <div class="s-denda-grid">
                <div class="s-denda-item">
                    <div class="s-denda-item-label">Alpha</div>
                    <div class="s-denda-item-value" id="dendaAlpha"><?= $alpha ?> hari</div>
                </div>
                <div class="s-denda-item">
                    <div class="s-denda-item-label">Bolos</div>
                    <div class="s-denda-item-value" id="dendaBolos"><?= $bolos ?> hari</div>
                </div>
            </div>
            <?php if ($tanpaB > 0): ?>
            <div class="s-denda-note">
                <i class="bi bi-dash-circle"></i>
                <span><?= $tanpaB ?> alpha belum punya baris presensi</span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Skor Disiplin -->
    <?php
    $skorD = $skor_disiplin ?? null;
    $skorTerbaca = is_int($skorD);
    $kelasSkor = !$skorTerbaca
        ? 'bg-slate-200 text-slate-500'
        : ($skorD >= 90 ? 'bg-emerald-500 text-white'
            : ($skorD >= 75 ? 'bg-amber-500 text-white'
                : 'bg-rose-500 text-white'));
    $labelSkor = !$skorTerbaca ? 'Belum Terhitung' : ($skorD >= 90 ? 'Sangat Baik' : ($skorD >= 75 ? 'Cukup' : 'Perhatian'));
    ?>
    <div class="s-card s-skor-card">
        <div class="s-card-body">
            <div class="s-skor-top">
                <div class="s-skor-left">
                    <div class="s-skor-avatar bg-indigo-50 text-indigo-600">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="s-skor-title">Skor Disiplin</div>
                        <div class="s-skor-label"><?= $labelSkor ?></div>
                    </div>
                </div>
                <div class="s-skor-ring <?= $kelasSkor ?>">
                    <?= $skorTerbaca ? (int) $skorD : '—' ?>
                </div>
            </div>
            <div class="s-skor-desc">
                <?= $skorTerbaca ? 'Skor maksimal 100 &middot; Pertahankan kedisiplinanmu!' : 'Skor sedang tidak dapat dihitung &middot; hubungi guru piket.' ?>
            </div>
        </div>
    </div>

    <!-- Presensi Hari Ini -->
    <div class="s-card s-today-card" id="hari-ini">
        <div class="s-card-body">
            <div class="s-today-top">
                <div class="s-today-left">
                    <div class="s-today-avatar bg-indigo-50 text-indigo-600">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="s-today-title">Presensi Hari Ini</div>
                        <div class="s-today-date"><?= esc(date('d M Y')) ?></div>
                    </div>
                </div>
                <div id="todayBadge" class="text-right">
                    <?php if (!$today): ?>
                        <span class="s-today-badge bg-slate-100 text-slate-600 border border-slate-200">
                            <i class="bi bi-clock"></i> Belum Absen
                        </span>
                    <?php elseif ($today_cat === 'hadir'): ?>
                        <span class="s-today-badge s-badge-hadir"><i class="bi bi-check-circle-fill"></i> Hadir</span>
                    <?php elseif ($today_cat === 'izin'): ?>
                        <span class="s-today-badge s-badge-izin"><i class="bi bi-envelope-fill"></i> Izin</span>
                    <?php elseif ($today_cat === 'sakit'): ?>
                        <span class="s-today-badge s-badge-sakit"><i class="bi bi-heart-pulse-fill"></i> Sakit</span>
                    <?php elseif ($today_cat === 'bdr'): ?>
                        <span class="s-today-badge s-badge-bdr"><i class="bi bi-house-door-fill"></i> BDR</span>
                    <?php elseif ($today_cat === 'alpha'): ?>
                        <span class="s-today-badge s-badge-alpha"><i class="bi bi-x-circle-fill"></i> Alpha</span>
                    <?php elseif ($today_cat === 'tanpa_keterangan'): ?>
                        <span class="s-today-badge s-badge-tanpa"><i class="bi bi-question-circle-fill"></i> Tanpa Ket.</span>
                    <?php else: ?>
                        <span class="s-today-badge bg-slate-100 text-slate-700 border border-slate-200"><?= esc($today->status ?? '-') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="s-today-times">
                <div class="s-today-time">
                    <div class="s-today-time-label">Masuk</div>
                    <div class="s-today-time-value">
                        <?= ($today && !empty($today->jam_masuk)) ? esc(substr($today->jam_masuk, 0, 5)) : '--:--' ?>
                    </div>
                </div>
                <div class="s-today-time">
                    <div class="s-today-time-label">Keluar</div>
                    <div class="s-today-time-value">
                        <?= ($today && !empty($today->jam_keluar)) ? esc(substr($today->jam_keluar, 0, 5)) : '--:--' ?>
                    </div>
                </div>
            </div>
            <?php if ($today && !empty($today->keterangan)): ?>
            <div class="s-today-note">
                <i class="bi bi-info-circle-fill"></i>
                <span><?= esc($today->keterangan) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="s-timeline-wrap">
        <div class="s-timeline-title">Riwayat Presensi</div>
        <div id="riwayatList">
            <div class="s-empty-state">
                <i class="bi bi-hourglass-split s-empty-icon"></i>
                <div class="font-bold text-slate-800">Memuat riwayat...</div>
            </div>
        </div>
    </div><!-- /.s-timeline-wrap -->

    </div><!-- /.s-main-content -->
</div><!-- /.s-wrap -->

<!-- Modal QR -->
<div id="student-qr-modal" class="s-qr-modal">
    <div id="student-qr-card" class="s-qr-card">
        <button type="button" onclick="closeStudentQrModal()" class="s-qr-close" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="s-qr-header">
            <div class="s-qr-chip">
                <i class="bi bi-qr-code-scan"></i> Kartu Presensi Digital
            </div>
            <h3 class="s-qr-title"><?= esc($siswa->nama ?? 'Siswa') ?></h3>
            <p class="s-qr-meta">NIS: <?= esc($siswa->nis ?: '-') ?> &bull; Kelas <?= esc($siswa->nama_kelas ?: '-') ?></p>
            <span class="s-qr-school"><?= esc($setting->sekolah ?? 'Sekolah') ?></span>
        </div>

        <div class="s-qr-box-wrap">
            <div id="modalStudentQr" class="s-qr-box"></div>
        </div>
        <p class="s-qr-value"><?= esc($qrSiswaVal) ?></p>
        <p class="s-qr-hint">
            <i class="bi bi-camera-fill"></i> Arahkan ke kamera Kiosk Scanner di sekolah
        </p>

        <div class="s-qr-actions">
            <?php
            $idK = (int) ($siswa->id_kelas ?? 0);
            $cetakUrl = site_url('admin/presensi/siswa/kartu/cetak?id_siswa=' . $siswa->id_siswa . ($idK ? '&id_kelas=' . $idK : '') . '&desain=portrait&v=3');
            ?>
            <a href="<?= $cetakUrl ?>" target="_blank" class="s-qr-btn s-qr-btn-cetak">
                <i class="bi bi-printer-fill"></i> Cetak Kartu
            </a>
            <button type="button" class="s-qr-btn s-qr-btn-download" onclick="downloadKartuJpg()">
                <i class="bi bi-download"></i> Unduh JPG
            </button>
            <button type="button" class="s-qr-btn s-qr-btn-close" onclick="closeStudentQrModal()">Tutup</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/qrcode.min.js') ?>"></script>
<script>
    /* ── Jam digital real-time (WITA) ── */
    (function() {
        var DAYS   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        var MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        function padZ(n) { return String(n).padStart(2, '0'); }
        function updateClock() {
            var now;
            try {
                now = (window.witaAktif && witaAktif()) ? witaNow() : new Date();
            } catch(e) { now = new Date(); }
            var h  = padZ(now.getHours());
            var mi = padZ(now.getMinutes());
            var s  = padZ(now.getSeconds());
            var el = document.getElementById('sDigitalClock');
            if (el) el.textContent = h + ':' + mi + ':' + s;
            var dateEl = document.getElementById('sDigitalDate');
            if (dateEl) {
                dateEl.textContent = DAYS[now.getDay()] + ', ' + now.getDate() + ' ' + MONTHS[now.getMonth()] + ' ' + now.getFullYear();
            }
        }
        updateClock();
        setInterval(updateClock, 1000);
    })();
</script>
<script>
    let studentQrGenerated = false;
    window.openStudentQrModal = function() {
        const modal = document.getElementById('student-qr-modal');
        const card  = document.getElementById('student-qr-card');
        if (!modal) {
            console.error('Modal student-qr-modal not found');
            return;
        }
        modal.style.display = 'flex';
        modal.classList.add('open');
        requestAnimationFrame(() => {
            if (card) card.classList.add('open');
        });

        if (!studentQrGenerated) {
            const qrEl = document.getElementById('modalStudentQr');
            const qrText = <?= json_encode((string) $qrSiswaVal) ?>;
            if (qrEl) {
                if (window.QRCode) {
                    try {
                        qrEl.innerHTML = '';
                        new QRCode(qrEl, {
                            text: qrText,
                            width: 176,
                            height: 176,
                            colorDark: "#1e1b4b",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                        studentQrGenerated = true;
                    } catch (e) {
                        console.error('Error generating student QR with QRCode.js:', e);
                        renderStudentQrFallback(qrEl, qrText);
                    }
                } else {
                    renderStudentQrFallback(qrEl, qrText);
                }
            }
        }
    };

    function renderStudentQrFallback(el, text) {
        if (!el) return;
        el.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(text) + '" alt="QR Code" style="width:176px;height:176px;display:block;border-radius:8px;" onerror="this.outerHTML=\'<span class=\\\'font-mono font-bold text-sm\\\'>' + text + '</span>\'">';
        studentQrGenerated = true;
    }

    window.closeStudentQrModal = function() {
        const modal = document.getElementById('student-qr-modal');
        const card  = document.getElementById('student-qr-card');
        if (!modal) return;
        modal.classList.remove('open');
        if (card) card.classList.remove('open');
        setTimeout(() => { modal.style.display = 'none'; }, 250);
    };

    window.downloadKartuJpg = function() {
        const nama   = <?= json_encode((string) ($siswa->nama ?? 'Siswa')) ?>;
        const nis    = <?= json_encode((string) ($siswa->nis ?? '-')) ?>;
        const nisn   = <?= json_encode((string) ($siswa->nisn ?? '-')) ?>;
        const kelas  = <?= json_encode((string) ($siswa->nama_kelas ?? '-')) ?>;
        const qrVal  = <?= json_encode((string) $qrSiswaVal) ?>;
        const sekolah = <?= json_encode((string) ($setting->sekolah ?? 'Sekolah')) ?>;
        const kota   = <?= json_encode((string) ($setting->kota ?? 'Kab. Kupang')) ?>;
        const logoUrl = <?= json_encode($logo_url ?? '') ?>;

        window.openStudentQrModal();

        const W = 720;
        const H = 1140;
        const off = document.createElement('canvas');
        off.width = W;
        off.height = H;
        const ctx = off.getContext('2d');

        const fillRoundedRect = (x, y, w, h, r) => {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fill();
        };
        const strokeRoundedRect = (x, y, w, h, r) => {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.stroke();
        };
        const clipRoundedRect = (x, y, w, h, r) => {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
        };

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, W, H);

        const headerH = H * 0.27;

        const drawHeader = (logoImg) => {
            const grad = ctx.createLinearGradient(0, 0, 0, headerH);
            grad.addColorStop(0, '#4338ca');
            grad.addColorStop(1, '#312e81');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, W, headerH);

            ctx.fillStyle = '#4f46e5';
            ctx.fillRect(0, headerH, W, 4);

            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#ffffff';

            if (logoImg && logoImg.complete && logoImg.naturalWidth) {
                const logoSize = headerH * 0.42;
                const lx = (W - logoSize) / 2;
                const ly = headerH * 0.10;
                ctx.save();
                clipRoundedRect(lx, ly, logoSize, logoSize, 6);
                ctx.clip();
                ctx.drawImage(logoImg, lx, ly, logoSize, logoSize);
                ctx.restore();
                ctx.strokeStyle = 'rgba(255,255,255,0.35)';
                ctx.lineWidth = 2;
                ctx.strokeRect(lx, ly, logoSize, logoSize);
            }

            ctx.font = 'bold 21px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText(sekolah, W / 2, headerH * 0.56);

            ctx.fillStyle = '#c7c7ff';
            ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText('KARTU PRESENSI DIGITAL', W / 2, headerH * 0.79);

            const badgeText = kelas;
            ctx.fillStyle = 'rgba(255,255,255,0.22)';
            const m = ctx.measureText(badgeText);
            const badgeW = m.width + 20;
            const badgeH = 24;
            const bx = (W - badgeW) / 2;
            const by = headerH * 0.93 - badgeH / 2;
            fillRoundedRect(bx, by, badgeW, badgeH, badgeH / 2);
            ctx.strokeStyle = 'rgba(255,255,255,0.4)';
            ctx.lineWidth = 1.5;
            strokeRoundedRect(bx, by, badgeW, badgeH, badgeH / 2);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText(badgeText, W / 2, by + badgeH / 2 + 2);
        };

        const drawQr = () => {
            const qrBox = document.createElement('div');
            qrBox.style.position = 'fixed';
            qrBox.style.top = '-9999px';
            qrBox.style.left = '-9999px';
            document.body.appendChild(qrBox);

            new QRCode(qrBox, {
                text: qrVal,
                width: 300,
                height: 300,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });

            let qrSource = qrBox.querySelector('canvas') || qrBox.querySelector('img');
            if (!qrSource) {
                const tbl = qrBox.querySelector('table');
                if (tbl) {
                    const tmp = document.createElement('canvas');
                    tmp.width = 300; tmp.height = 300;
                    tmp.getContext('2d').drawImage(tbl, 0, 0);
                    qrSource = tmp;
                }
            }

            const qrSize = 360;
            const qrX = (W - qrSize) / 2;
            const qrY = headerH + (H - headerH) * 0.06;

            ctx.fillStyle = '#ffffff';
            fillRoundedRect(qrX, qrY, qrSize, qrSize, 14);
            ctx.drawImage(qrSource, qrX, qrY, qrSize, qrSize);
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 2;
            strokeRoundedRect(qrX, qrY, qrSize, qrSize, 14);

            document.body.removeChild(qrBox);

            ctx.fillStyle = '#0f172a';
            ctx.textAlign = 'center';
            ctx.font = 'bold 17px monospace';
            let codeText = qrVal;
            const maxW = W - 40;
            while (ctx.measureText(codeText).width > maxW && codeText.length > 1) {
                codeText = codeText.slice(0, -1);
            }
            ctx.fillText(codeText, W / 2, qrY + qrSize + 40);

            ctx.fillStyle = '#64748b';
            ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText('SCAN ABSEN', W / 2, qrY + qrSize + 72);
        };

        const drawName = () => {
            const nameY = headerH + (H - headerH) * 0.66;
            ctx.fillStyle = '#0f172a';
            ctx.textAlign = 'center';
            ctx.font = 'bold 26px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText(nama.toUpperCase(), W / 2, nameY);
        };

        const drawMeta = () => {
            const metaY = headerH + (H - headerH) * 0.74;
            ctx.fillStyle = '#475569';
            ctx.textAlign = 'center';
            ctx.font = '14px monospace';
            ctx.fillText('NIS ' + nis + '  •  NISN ' + nisn, W / 2, metaY);
        };

        const drawFooter = () => {
            const footerH = H * 0.13;
            const footerY = H - footerH;
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(0, footerY, W, footerH);
            ctx.strokeStyle = '#f1f5f9';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(0, footerY);
            ctx.lineTo(W, footerY);
            ctx.stroke();

            ctx.fillStyle = '#475569';
            ctx.textAlign = 'start';
            ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText('Wajib dibawa KBM / Harian', 28, footerY + footerH * 0.42);

            ctx.textAlign = 'end';
            ctx.fillStyle = '#64748b';
            ctx.font = '13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
            ctx.fillText(kota, W - 28, footerY + footerH * 0.42);
        };

        const finalize = () => {
            try {
                const link = document.createElement('a');
                link.download = 'kartu-presensi-' + qrVal + '.jpg';
                link.href = off.toDataURL('image/jpeg', 0.92);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) {
                console.error('Gagal download JPG:', e);
                alert('Gagal menghasilkan file JPG. Coba cetak kartu lalu simpan sebagai gambar/PDF.');
            }
        };

        drawQr();
        drawName();
        drawMeta();
        drawFooter();

        if (logoUrl) {
            const logoImg = new Image();
            logoImg.crossOrigin = 'anonymous';
            logoImg.src = logoUrl;
            logoImg.onload = () => { drawHeader(logoImg); finalize(); };
            logoImg.onerror = () => { drawHeader(null); finalize(); };
        } else {
            drawHeader(null);
            finalize();
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('student-qr-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) window.closeStudentQrModal();
            });
        }
        if (window.location.hash === '#qr' || window.location.hash === '#kartu-qr') {
            window.openStudentQrModal();
        }

        // Refresh button
        document.getElementById('btnRefreshBulan')?.addEventListener('click', function() {
            const btn = this;
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
            btn.classList.add('opacity-50');
            setTimeout(() => {
                window.location.reload();
            }, 300);
        });
    });
</script>
<script>
$(document).ready(function() {
    const catBadge = {
        hadir: '<span class="s-today-badge s-badge-hadir"><i class="bi bi-check-circle-fill"></i> Hadir</span>',
        izin: '<span class="s-today-badge s-badge-izin"><i class="bi bi-envelope-fill"></i> Izin</span>',
        sakit: '<span class="s-today-badge s-badge-sakit"><i class="bi bi-heart-pulse-fill"></i> Sakit</span>',
        alpha: '<span class="s-today-badge s-badge-alpha"><i class="bi bi-x-circle-fill"></i> Alpha</span>',
        bdr: '<span class="s-today-badge s-badge-bdr"><i class="bi bi-house-door-fill"></i> BDR</span>',
        tanpa_keterangan: '<span class="s-today-badge s-badge-tanpa"><i class="bi bi-question-circle-fill"></i> Tanpa Ket.</span>'
    };
    const escJs = (s) => $('<div>').text(s ?? '').html();

    function loadRiwayat() {
        const bulan = $('#filterBulan').val() || '';
        $('#riwayatList').html('<div class="s-empty-state"><i class="bi bi-hourglass-split s-empty-icon"></i><div class="font-bold text-slate-800">Memuat riwayat...</div></div>');

        $.get('<?= site_url('siswa/presensi/history') ?>', { bulan: bulan }, function(res) {
            if (!res || !res.status) {
                $('#riwayatList').html('<div class="s-empty-state"><i class="bi bi-exclamation-triangle s-empty-icon"></i><div class="font-bold text-slate-800">Gagal memuat riwayat.</div></div>');
                return;
            }
            const rows = res.data || [];
            if (!rows.length) {
                $('#riwayatList').html('<div class="s-empty-state"><i class="bi bi-inbox s-empty-icon"></i><div class="font-bold text-slate-800">Belum ada data presensi bulan ini.</div></div>');
            } else {
                let html = '';
                rows.forEach((r) => {
                    const d = new Date(r.tgl + 'T00:00:00');
                    const tglNum = isNaN(d) ? '-' : d.getDate();
                    const bln = isNaN(d) ? '' : d.toLocaleDateString('id-ID', { month: 'short' });
                    const masuk = (r.jam_masuk || '').substring(0, 5);
                    const keluar = (r.jam_keluar || '').substring(0, 5);
                    const waktu = keluar ? escJs(masuk) + ' <span class="text-slate-300 mx-1 text-[10px]">&rarr;</span> ' + escJs(keluar)
                                          : escJs(masuk || '-');
                    const sub = r.keterangan
                        ? '<p class="s-riwayat-ket">' + escJs(r.keterangan) + '</p>'
                        : (!keluar && r.kategori === 'hadir'
                            ? '<p class="s-riwayat-ket" style="color:#b45309;">Belum absen pulang</p>'
                            : '');
                    const statusBadge = catBadge[r.kategori] || '<span class="s-today-badge bg-slate-100 text-slate-600 border border-slate-200">' + escJs(r.status || '-') + '</span>';
                    html += '<div class="s-timeline-item">' +
                        '<div class="s-date-col">' +
                            '<div class="s-date-circle">' +
                                '<span class="s-date-day">' + tglNum + '</span>' +
                                '<span class="s-date-month">' + escJs(bln) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="s-riwayat-card">' +
                            '<div class="s-riwayat-row">' +
                                '<div class="s-riwayat-time">' + waktu + '</div>' +
                                statusBadge +
                            '</div>' + sub +
                            '</div>' +
                        '</div>';
                });
                $('#riwayatList').html(html);
            }
            if (res.stats) {
                $('#statHadir').text(res.stats.hadir);
                $('#statIzin').text(res.stats.izin);
                $('#statSakit').text(res.stats.sakit);
                $('#statAlpha').text(res.stats.alpha);
                $('#statBdr').text(res.stats.bdr || 0);
                $('#statTanpaKet').text(res.stats.tanpa_keterangan || 0);
                $('#statPersen').text(res.stats.persen);
                $('#barPersen').css('width', res.stats.persen + '%');
                if (res.stats.denda) {
                    const d = res.stats.denda;
                    const netto = d.netto != null ? d.netto : 0;
                    const fmt = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
                    $('#dendaNetto').text(fmt(netto));
                    $('#dendaAlpha').text(((d.alpha || 0)) + ' hari');
                    $('#dendaBolos').text(((d.bolos || 0)) + ' hari');
                    const ada = (netto > 0) || ((d.alpha || 0) > 0) || ((d.bolos || 0) > 0);
                    $('#cardDenda').removeClass('denda-ada denda-bersih').addClass(ada ? 'denda-ada' : 'denda-bersih');
                    $('#dendaAvatar').attr('class', 's-denda-avatar ' + (ada ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'))
                        .html('<i class="bi bi-' + (ada ? 'wallet2' : 'check-circle-fill') + '"></i>');
                    $('#dendaStatus').html('<i class="bi bi-' + (ada ? 'clock' : 'check-circle-fill') + '"></i> <span>' + (ada ? 'Belum dibayar' : 'Bersih') + '</span>')
                        .attr('class', 's-denda-chip ' + (ada ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'));
                    $('#dendaSubtitle').text(ada ? 'Jumlah denda yang harus dibayar bulan ini' : 'Tidak ada pelanggaran denda bulan ini');
                }
            }
        }, 'json').fail(function() {
            $('#riwayatList').html('<div class="s-empty-state"><i class="bi bi-wifi-off s-empty-icon"></i><div class="font-bold text-slate-800">Gagal menghubungi server.</div></div>');
        });
    }

    $('#filterBulan').on('change', loadRiwayat);
    loadRiwayat();
});
</script>
<?= $this->endSection() ?>