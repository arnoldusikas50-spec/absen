<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* SweetAlert di atas bottom-nav (z 2000) */
    .swal2-container { z-index: 4000 !important; }
    /* ── Progress bar Jam Kerja: kilau + denyut ─────────────── */
    #jk-progress { position: relative; overflow: hidden; box-shadow: 0 0 8px rgb(var(--brand-600-rgb) / .35); }
    #jk-progress::after {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.55), transparent);
        transform: translateX(-100%);
        animation: jkSheen 2.4s ease-in-out infinite;
    }
    @keyframes jkSheen { 55% { transform: translateX(100%); } 100% { transform: translateX(100%); } }
    /* ── Face Scanner v2 — 100% mandiri, tanpa Tailwind ────────── */
    .camera-overlay {
        --fs-ok: #34d399;
        --fs-brand: var(--brand-400);
        --fs-line: rgba(255,255,255,.10);
        --fs-glass: rgba(255,255,255,.055);
        position: fixed; top:0; left:0; right:0; bottom:0; z-index: 9999;
        display: none; flex-direction: column;
        overflow-y: auto; overscroll-behavior: contain;
        background:
            radial-gradient(86% 30% at 50% -8%, rgb(var(--brand-500-rgb) / .34) 0%, rgb(var(--brand-500-rgb) / 0) 72%),
            radial-gradient(70% 26% at 50% 108%, rgba(45,212,191,.16) 0%, rgba(45,212,191,0) 70%),
            radial-gradient(130% 100% at 50% 0%, #16233d 0%, #0b1224 54%, #04070f 100%);
        -webkit-overflow-scrolling: touch;
        padding-bottom: env(safe-area-inset-bottom, 0px);
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    }
    .camera-overlay.show { display: flex; }
    .camera-overlay * { -webkit-tap-highlight-color: transparent; }

    /* Header — glass bar */
    .fs-head { width:100%; max-width:26rem; margin:0 auto; display:flex; align-items:center; gap:12px; padding:calc(.85rem + env(safe-area-inset-top,0px)) 16px 0; color:#fff; }
    .fs-close { width:40px; height:40px; flex-shrink:0; display:flex; align-items:center; justify-content:center; border-radius:999px; border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.07); -webkit-backdrop-filter:blur(12px); backdrop-filter:blur(12px); color:#fff; font-size:16px; cursor:pointer; transition:transform .15s ease, background .2s ease; }
    .fs-close:active { transform: scale(.9); background:rgba(255,255,255,.16); }
    .fs-close:focus-visible { outline:2px solid #fff; outline-offset:2px; }
    .fs-titlewrap { flex:1; min-width:0; text-align:center; }
    .fs-title { margin:0; font-weight:800; font-size:16.5px; letter-spacing:-.2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .fs-sub { margin:3px 0 0; font-weight:500; font-size:12px; color:#8fa0b8; }
    .fs-head-spacer { width:40px; flex-shrink:0; }

    /* Step rail */
    .camera-sensor-box { flex:1; display:flex; flex-direction:column; align-items:center; width:100%; max-width:26rem; margin:0 auto; padding:14px 20px calc(1.25rem + env(safe-area-inset-bottom,0px)); }
    .fs-dots { display:flex; align-items:center; justify-content:center; gap:5px; margin:0 0 14px; }
    .fs-dots i { width:20px; height:3px; border-radius:999px; background:rgba(255,255,255,.16); transition:background .35s ease, box-shadow .35s ease; }
    .fs-dots i.on { background:linear-gradient(90deg,var(--fs-brand),var(--brand-600)); box-shadow:0 0 10px rgb(var(--brand-600-rgb) / .6); }

    /* Stage */
    .camera-video-container { width:100%; max-width:20rem; position:relative; background:radial-gradient(120% 90% at 50% 0%, #101c31 0%, #050a14 72%); border-radius:28px; overflow:hidden; aspect-ratio:3/4; max-height:44vh; border:1px solid var(--fs-line); box-shadow:0 26px 60px -18px rgba(0,0,0,.9), 0 0 0 5px rgba(255,255,255,.018); isolation:isolate; }
    .camera-video-container > video { position:absolute; inset:0; z-index:0; }
    .camera-video-container > canvas { z-index:1; }
    .fs-stage-ring { position:absolute; inset:0; z-index:4; border-radius:inherit; pointer-events:none; box-shadow:inset 0 0 0 1px rgba(255,255,255,.05), inset 0 -60px 90px -60px rgba(2,6,23,.9); }

    /* Face frame — spotlight: sebar 100vmax untuk Gelapkan area di luar
       oval sehingga mata langsung terarah ke bingkai wajah. */
    .face-guide { position:absolute; left:50%; top:47%; transform:translate(-50%,-50%); width:58%; aspect-ratio:.88; border-radius:50%; border:2px solid rgba(255,255,255,.72); box-shadow:0 0 0 100vmax rgba(2,6,23,.55); overflow:hidden; z-index:3; transition:border-color .3s ease, box-shadow .35s ease, width .35s cubic-bezier(.34,1.3,.64,1), top .35s cubic-bezier(.34,1.3,.64,1); }
    /* Scanline: garis laser tipis + ekor gradien pendek (bukan blok besar) */
    .face-guide .fs-scan { position:absolute; left:2%; right:2%; top:-14%; height:14%; background:linear-gradient(180deg, rgb(var(--brand-400-rgb) / 0) 0%, rgb(var(--brand-400-rgb) / .20) 100%); animation:fsScan 2.4s cubic-bezier(.5,0,.5,1) infinite; }
    .face-guide .fs-scan::after { content:''; position:absolute; left:-2%; right:-2%; bottom:0; height:1.5px; background:#fff; opacity:.95; box-shadow:0 0 8px 1.5px rgb(var(--brand-400-rgb) / .60); }
    @keyframes fsScan { 0% { top:-16%; opacity:0; } 18% { opacity:1; } 64% { top:100%; opacity:1; } 100% { top:100%; opacity:0; } }
    .face-guide.is-ok { border-color:var(--fs-ok); box-shadow:0 0 0 4px rgba(52,211,153,.12), 0 0 36px rgba(52,211,153,.34), 0 0 0 100vmax rgba(2,6,23,.55); }
    .face-guide.is-ok .fs-scan { background:linear-gradient(180deg, rgba(52,211,153,0) 0%, rgba(52,211,153,.26) 100%); }
    .face-guide.is-ok .fs-scan::after { box-shadow:0 0 8px 1.5px rgba(52,211,153,.60); }
    /* Wajah terdeteksi tapi belum pas bingkai (terlalu dekat / jauh / geser) */
    .face-guide.is-warn { border-color:#fbbf24; box-shadow:0 0 0 4px rgba(251,191,36,.12), 0 0 30px rgba(251,191,36,.28), 0 0 0 100vmax rgba(2,6,23,.55); }

    /* (Corner brackets dihapus — oval + spotlight sudah cukup sebagai
       satu-satunya frame fokus, tanpa elemen dekorasi ganda.) */

    /* Stage chips */
    .fs-topchips { position:absolute; left:12px; right:12px; top:12px; z-index:7; display:flex; align-items:center; justify-content:space-between; gap:8px; pointer-events:none; }
    .fs-chip { display:inline-flex; align-items:center; gap:6px; border-radius:999px; border:1px solid rgba(255,255,255,.12); -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px); background:rgba(2,6,23,.55); color:#fff; font-size:11px; font-weight:800; letter-spacing:1px; padding:6px 11px; }
    .fs-chip.ai { color:#cbd5e1; }
    .fs-livedot { display:inline-block; width:7px; height:7px; border-radius:999px; background:#34d399; animation:livePulse 1.6s ease-in-out infinite; }
    .fs-livedot.teal { background:#2dd4bf; }
    @keyframes livePulse { 0%,100% { opacity:1; } 50% { opacity:.35; } }

    /* Camera-off placeholder (menutup ikon default browser) */
    .fs-camoff { position:absolute; inset:0; z-index:2; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:0 24px; text-align:center; background:radial-gradient(80% 60% at 50% 45%, rgba(15,23,42,.35) 0%, rgba(2,6,23,.92) 100%); transition:opacity .5s ease, visibility .5s ease; }
    .fs-camoff.is-hidden { opacity:0; visibility:hidden; }
    .fs-camoff-ic { width:62px; height:62px; border-radius:20px; display:flex; align-items:center; justify-content:center; font-size:26px; color:#64748b; background:rgba(255,255,255,.045); border:1px solid rgba(255,255,255,.08); box-shadow:inset 0 1px 0 rgba(255,255,255,.06); }
    .fs-camoff-tx { font-size:12px; font-weight:700; color:#7b8ba3; line-height:1.5; }
    .fs-camoff-tx b { display:block; color:#cbd5e1; font-size:13px; margin-bottom:2px; }

    /* Bottom scrim + tips */
    .fs-scrim { position:absolute; left:0; right:0; bottom:0; z-index:2; padding:0 12px 14px; display:flex; align-items:flex-end; justify-content:center; pointer-events:none; background:linear-gradient(180deg, rgba(2,6,23,0) 0%, rgba(2,6,23,.78) 62%); }
    .fs-scrim span { font-size:11.5px; font-weight:700; color:#cbd5e1; letter-spacing:.2px; background:rgba(2,6,23,.5); border:1px solid rgba(255,255,255,.1); border-radius:999px; padding:6px 13px; display:inline-flex; align-items:center; gap:6px; }
    .fs-scrim span i { color:var(--fs-brand); font-size:12px; }

    /* Status pill — lebar penuh & tetap supaya tidak bergeser saat teks berganti */
    #facedetect-status { margin-top:14px; width:100%; max-width:22rem; min-height:40px; display:flex; align-items:center; justify-content:center; gap:9px; color:#e2e8f0; font-size:13px; font-weight:700; text-align:center; line-height:1.35; padding:9px 18px; border-radius:999px; background:var(--fs-glass); border:1px solid var(--fs-line); box-shadow:0 10px 24px -16px rgba(0,0,0,.9); }
    #facedetect-status small { color:inherit !important; font-size:inherit !important; font-weight:inherit !important; display:inline-flex; align-items:center; gap:6px; }
    #facedetect-status .dot { width:8px; height:8px; border-radius:999px; background:#fbbf24; flex-shrink:0; box-shadow:0 0 0 3px rgba(251,191,36,.16); animation:livePulse 1.6s ease-in-out infinite; }
    #facedetect-status .fs-blink { animation:fsBlink 1.3s ease-in-out infinite; }
    @keyframes fsBlink { 0%,100% { opacity:1; } 50% { opacity:.32; } }
    @media (prefers-reduced-motion: reduce) {
        #facedetect-status .fs-blink, #facedetect-status .dot { animation:none; }
        .face-guide .fs-scan { animation:none; top:34%; opacity:1; }
    }
    #auto-progress-label { font-weight:800 !important; color:#94a3b8 !important; letter-spacing:.6px; text-transform:uppercase; }
    #locText i { margin-right:2px; }
    #locText .text-emerald-500, #locText i.text-emerald-500 { color:#34d399 !important; }
    #locText .text-red-500, #locText i.text-red-500 { color:#f87171 !important; }
    #locText .text-amber-500, #locText i.text-amber-500 { color:#fbbf24 !important; }

    /* Shutter */
    .shutter-btn-wrapper { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:11px; margin-top:18px; }
    .btn-camera-shutter { position:relative; width:76px; height:76px; border-radius:9999px; background:radial-gradient(circle at 50% 25%, rgba(255,255,255,.10), rgba(255,255,255,.02) 70%); display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; border:1.5px solid rgba(255,255,255,.32); box-shadow:0 12px 30px -10px rgb(var(--brand-600-rgb) / .6); transition:transform .16s ease, box-shadow .2s ease; }
    .btn-camera-shutter::before { content:''; position:absolute; inset:-9px; border-radius:9999px; border:1px solid rgb(var(--brand-400-rgb) / .5); animation:fsPulse 2.6s cubic-bezier(.4,0,.2,1) infinite; pointer-events:none; }
    .btn-camera-shutter .shutter-core { width:60px; height:60px; border-radius:9999px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:23px; background:linear-gradient(145deg,var(--brand-400) 0%,var(--brand-600) 52%,var(--brand-800) 100%); box-shadow:inset 0 1px 0 rgba(255,255,255,.35), 0 6px 16px -6px rgba(0,0,0,.65); transition:transform .16s ease, filter .16s ease; }
    .btn-camera-shutter:active { transform:scale(.94); }
    .btn-camera-shutter:active .shutter-core { transform:scale(.92); filter:brightness(.94); }
    .btn-camera-shutter:active::before { animation:none; opacity:0; }
    .btn-camera-shutter:disabled { opacity:.5; filter:grayscale(.4); cursor:not-allowed; }
    .btn-camera-shutter:disabled::before { animation:none; }
    .btn-camera-shutter:focus-visible { outline:3px solid #fff; outline-offset:4px; }
    .shutter-btn-label { display:inline-flex; align-items:center; gap:6px; font-weight:800; color:#e2e8f0; font-size:12.5px; letter-spacing:.2px; }
    .shutter-btn-label i { color:var(--fs-brand); }
    .shutter-btn-hint { font-size:11px; font-weight:600; color:#64748b; margin-top:-6px; }

    /* Location card */
    .fs-loc { margin-top:16px; width:100%; max-width:22rem; padding:11px 13px; border-radius:16px; background:var(--fs-glass); border:1px solid var(--fs-line); box-shadow:0 10px 26px -18px rgba(0,0,0,.9); }
    .fs-loc-main { display:flex; align-items:center; gap:11px; }
    .fs-loc-txt { flex:1; min-width:0; text-align:left; overflow:hidden; }
    .fs-loc-label { display:block; font-size:10.5px; font-weight:800; letter-spacing:1.3px; color:#5b6b83; margin-bottom:2px; }
    #locText { margin:0; font-size:12.5px; font-weight:700; color:#cbd5e1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
    #locText.loc-error { white-space:normal; color:#fca5a5 !important; }
    .fs-loc-actions { display:none; width:100%; align-items:center; justify-content:center; gap:8px; margin-top:10px; padding-top:10px; border-top:1px dashed rgba(255,255,255,.10); flex-wrap:wrap; }
    .fs-loc-actions.show { display:flex; }
    .fs-loc-btn { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:7px 14px; font-size:11.5px; font-weight:800; cursor:pointer; border:1px solid transparent; transition:transform .15s ease, opacity .15s ease; }
    .fs-loc-btn:active { transform:scale(.95); }
    .fs-loc-btn-retry { background:var(--brand-600); border-color:var(--brand-400); color:#fff; box-shadow:0 4px 14px rgb(var(--brand-600-rgb) / .4); }
    .fs-loc-btn-permit { background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.2); color:#e2e8f0; }
    .fs-loc-btn:focus-visible { outline:2px solid #fff; outline-offset:2px; }
    .fs-loc-btn .bi-spin { animation:fsSpin 1s linear infinite; }
    @keyframes fsSpin { to { transform:rotate(360deg); } }

    .fs-alert { margin-top:12px; width:100%; max-width:22rem; border-radius:14px; border:1px solid rgba(251,191,36,.35); background:rgba(245,158,11,.14); color:#fde68a; font-size:11.5px; font-weight:700; text-align:center; line-height:1.45; padding:9px 12px; }
    .fs-alert + .fs-more { margin-top:10px; }

    /* Opsi dinas / WFH */
    .fs-more { margin-top:12px; width:100%; max-width:22rem; border-radius:16px; border:1px solid var(--fs-line); background:rgba(255,255,255,.04); overflow:hidden; transition:border-color .3s ease, background .3s ease; }
    .fs-more:has(input:checked) { border-color:rgba(52,211,153,.30); background:rgba(16,185,129,.07); }
    .fs-more > summary { list-style:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; font-size:12px; font-weight:800; color:#94a3b8; transition:color .2s ease; }
    .fs-more > summary::-webkit-details-marker { display:none; }
    .fs-more > summary:active { color:#e2e8f0; }
    .fs-more > summary:focus-visible { outline:2px solid #fff; outline-offset:-2px; }
    .fs-more-l { display:inline-flex; align-items:center; gap:8px; }
    .fs-chev { font-size:11px; transition:transform .25s ease; }
    .fs-more[open] > summary { color:#fff; border-bottom:1px solid rgba(255,255,255,.08); }
    .fs-more[open] .fs-chev { transform:rotate(180deg); }
    .camera-options-card { padding:6px 8px 8px; }
    .mode-row { display:flex; align-items:center; gap:11px; cursor:pointer; border-radius:12px; padding:10px; border:1px solid transparent; transition:background .2s, border-color .2s; }
    .mode-row + .mode-row { margin-top:2px; }
    .mode-row:has(input:checked) { background:rgba(34,197,94,.12); border-color:rgba(34,197,94,.35); }
    .mode-ic { width:34px; height:34px; flex-shrink:0; display:flex; align-items:center; justify-content:center; border-radius:11px; font-size:15px; }
    .mode-ic.amber { background:rgba(251,191,36,.15); color:#fbbf24; }
    .mode-ic.sky { background:rgba(56,189,248,.15); color:#7dd3fc; }
    .mode-tx { flex:1; min-width:0; text-align:left; }
    .mode-tt { display:block; font-size:13px; font-weight:800; color:#fff; }
    .mode-sub { display:block; font-size:11px; color:#94a3b8; margin-top:1px; }
    .mode-toggle { position:relative; width:42px; height:23px; border-radius:999px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.15); transition:background .2s; flex-shrink:0; }
    .mode-toggle::after { content:''; position:absolute; top:2px; left:2px; width:17px; height:17px; border-radius:999px; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,.35); transition:transform .2s cubic-bezier(.34,1.4,.64,1); }
    .mode-row:has(input:disabled) { opacity:.55; }
    input:checked + .mode-toggle { background:#22c55e; border-color:#22c55e; }
    input:checked + .mode-toggle::after { transform:translateX(19px); }
    input:focus-visible + .mode-toggle { outline:2px solid #fff; outline-offset:2px; }
    .fs-sep { border-top:1px solid rgba(255,255,255,.08); margin-top:6px; padding-top:6px; }
    .fs-foot { margin:14px 0 0; text-align:center; color:#3f4d61; font-size:10px; font-weight:600; line-height:1.6; }
    .fs-foot i { margin-right:3px; }
    .fs-hideinput { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }

    /* ── Hero section ───────────────────────────────────────── */
    .hero-section {
        color: #fff; position: relative;
        overflow: hidden; border-radius: 0 0 32px 32px;
        background: radial-gradient(130% 110% at 85% 0%, var(--brand-300) 0%, var(--brand-400) 22%, var(--brand-500) 52%, var(--brand-600) 78%, var(--brand-700) 100%);
        padding: calc(22px + var(--safe-area-inset-top, 0px)) 20px 52px;
        box-shadow: 0 12px 36px rgb(var(--brand-600-rgb) / 0.25);
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
    .hero-sparkle.spark-2 { top:60%; right:12%; font-size:11px; opacity:.65; animation-delay:1.5s; }
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
        border: 2.5px solid rgba(255,255,255,.9);
        box-shadow: 0 6px 16px rgba(0,0,0,.15);
        background: #fff;
    }
    .greeting-text h4  { margin: 0; font-weight: 800; font-size: 27px; letter-spacing: -.5px; text-shadow: 0 1px 3px rgba(0,0,0,.1); }
    .greeting-text p   { margin: 0; font-weight: 500; opacity: .92; font-size: 13.5px; letter-spacing: .1px; }
    .motivation-box {
        display: inline-flex; align-items: center; gap: 6px;
        font-weight: 600; color: #fff;
        border-radius: 14px; padding: 6px 14px;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,.28);
        font-size: 11.5px; line-height: 1.45;
        max-width: 100%; text-align: left;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .motivation-box i { flex-shrink: 0; }

    /* ── Status card (floating over hero) ──────────────────── */
    .status-card {
        background: #ffffff; border-radius: 22px;
        position: relative; z-index: 20;
        padding: 18px 16px 16px;
        margin: -38px 16px 0;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.10);
        border: 1px solid rgba(226, 232, 240, 0.9);
    }
    .server-label {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        font-size: 10px; font-weight: 800; letter-spacing: 1.6px;
        color: #94a3b8; text-transform: uppercase; margin-bottom: 2px;
    }
    .server-label .live-dot {
        width: 7px; height: 7px; border-radius: 9999px; background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34,197,94,.18);
        animation: livePulse 1.8s ease-in-out infinite;
    }
    @keyframes livePulse { 0%,100% { opacity: 1; } 50% { opacity: .45; } }
    .clock-display { text-align: center; padding-top: 2px; padding-bottom: 8px; }
    #digitalClock {
        font-weight: 900; font-variant-numeric: tabular-nums;
        line-height: 1.1; margin-bottom: 8px;
        font-size: clamp(38px, 11.5vw, 46px);
        font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--brand-500);
        letter-spacing: 1px;
    }
    #digitalDate {
        display: inline-flex; align-items: center; gap: 4px;
        font-weight: 600; background: #f8fafc;
        border-radius: 9999px; padding: 3.5px 12px;
        text-transform: capitalize; font-size: 11.5px;
        color: #64748b; border: 1px solid #e2e8f0;
    }
    /* Jam di kartu ini mengikuti jam server, bukan jam perangkat — labelnya jujur. */
    .clock-tz {
        display: block; margin: -4px 0 6px;
        font-size: 10.5px; font-weight: 700; letter-spacing: 1.4px;
        color: #94a3b8;
    }
    .mini-status-grid {
        display: grid; gap: 8px; margin-top: 12px; padding-top: 12px;
        grid-template-columns: 1fr 1fr;
        border-top: 1px dashed #e2e8f0;
    }
    .mini-status-box {
        padding: 12px 8px 10px; border-radius: 18px; text-align: center;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        border: 1px solid;
        transition: transform .15s ease;
    }
    .mini-status-box.box-masuk  { background: #f0fdf4; border-color: #dcfce7; }
    .mini-status-box.box-pulang { background: #f8fafc; border-color: #eef2f7; }
    .mini-status-box .box-title { font-weight: 800; text-transform: uppercase; margin-bottom: 4px; font-size: 10px; letter-spacing: .6px; display: inline-flex; align-items: center; gap: 4px; }
    .mini-status-box.box-masuk  .box-title { color: #15803d; }
    .mini-status-box.box-pulang .box-title { color: #64748b; }
    .mini-status-box .box-value  { font-weight: 900; color: #0f172a; font-size: 21px; letter-spacing: -.3px; font-variant-numeric: tabular-nums; }
    .box-pill {
        display: inline-flex; align-items: center; gap: 4px;
        margin-top: 6px; padding: 3px 10px; border-radius: 9999px;
        font-size: 10px; font-weight: 800;
    }
    .box-pill.pill-ok   { background: #dcfce7; color: #15803d; }
    .box-pill.pill-warn { background: #fef3c7; color: #b45309; }
    .box-pill.pill-mute { background: #f1f5f9; color: #64748b; }

    /* ── Mini section titles (AKSES PINTAR dkk) ──────────── */
    .mini-section-title {
        padding: 20px 18px 0;
        font-size: 11.5px; font-weight: 800; letter-spacing: 1.4px;
        color: #64748b; text-transform: uppercase;
        display: flex; align-items: center; justify-content: space-between;
    }

    /* ── Action grid ────────────────────────────────────────── */
    .action-grid {
        display: grid; grid-template-columns: repeat(4, 1fr);
        column-gap: 12px; row-gap: 16px;
        padding: 16px 18px 10px;
    }
    .action-item {
        display: flex; flex-direction: column;
        align-items: center; text-decoration: none;
        cursor: pointer; transition: transform .15s ease;
        width: 100%; margin: 0; padding: 0;
        background: transparent; border: 0;
        font: inherit; color: inherit;
        -webkit-tap-highlight-color: transparent;
    }
    /* Angkat tile hanya untuk pointer nyata: di layar sentuh :hover tertahan
       setelah tapped sehingga tile terlihat "terangkat" permanen. */
    @media (hover: hover) { .action-item:hover { transform: translateY(-2px); } }
    .action-item:focus-visible { outline: none; }
    .action-item:focus-visible .action-icon {
        box-shadow: 0 0 0 3px rgb(var(--brand-600-rgb) / 0.35), 0 6px 16px rgba(0, 0, 0, 0.10);
    }
    .action-icon {
        position: relative;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 7px;
        width: 58px; height: 58px;
        border-radius: 20px;
        font-size: 24px;
        color: #fff;
        background-image: linear-gradient(180deg, rgba(255,255,255,.26) 0%, rgba(255,255,255,0) 62%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.35), 0 10px 20px -10px rgba(15, 23, 42, 0.45);
        border: none;
        transition: transform .2s cubic-bezier(.4,0,.2,1), box-shadow .2s ease;
    }
    .action-icon .bi { width: 1em; height: 1em; }
    .action-item:active .action-icon { transform: scale(.90); box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02); }
    .action-label { font-weight: 700; color: #334155; text-align: center; line-height: 1.3; font-size: 12.5px; letter-spacing: -.1px; }

    .btn-toggle-menu {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        font-weight: 700; background: #ffffff; border-radius: 9999px;
        cursor: pointer; border: 1px solid #e2e8f0;
        min-height: 42px; padding: 9px 20px; font-size: 12.5px; color: #475569;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03); transition: all .2s ease;
    }
    .btn-toggle-menu:active { background: #f8fafc; color: var(--brand-600); border-color: #cbd5e1; }
    @media (hover: hover) { .btn-toggle-menu:hover { background: #f8fafc; color: var(--brand-600); border-color: #cbd5e1; } }
    .btn-toggle-menu i { font-size: 12px; transition: transform .3s ease; }
    .btn-toggle-menu.active i { transform: rotate(180deg); }

    /* ── Koreksi Absensi ────────────────────────────────────── */
    .koreksi-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .koreksi-back { width: 40px; height: 40px; border-radius: 14px; display: flex; align-items: center; justify-content: center; padding: 0; flex-shrink: 0; font-size: 17px; }
    .koreksi-head h5 { margin: 0; font-size: 16.5px; font-weight: 800; color: #0f172a; letter-spacing: -.2px; }
    .koreksi-head p { margin: 2px 0 0; font-size: 11.5px; font-weight: 500; color: #94a3b8; }
    .koreksi-note {
        display: flex; align-items: flex-start; gap: 10px;
        margin-bottom: 16px; padding: 12px 14px;
        border-radius: 18px; border: 1px solid var(--primary-100); background: var(--primary-50);
    }
    .koreksi-note-ic {
        width: 32px; height: 32px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 11px; background: var(--primary-100); color: var(--primary-600); font-size: 16px;
    }
    .koreksi-note p { margin: 1px 0 0; font-size: 12.5px; line-height: 1.55; font-weight: 500; color: var(--primary-900); }
    .koreksi-card { padding: 18px; }
    .koreksi-card .form-label-tw {
        display: flex; align-items: center; gap: 6px;
        margin-bottom: 7px; font-size: 11px; font-weight: 800;
        letter-spacing: .5px; text-transform: uppercase; color: #64748b;
    }
    .koreksi-card .form-label-tw .bi { font-size: 13px; color: #94a3b8; }
    .koreksi-submit { min-height: 50px; font-size: 14px; border-radius: 16px !important; }
    .koreksi-history-head {
        display: flex; align-items: center; gap: 7px;
        padding: 0 4px; margin: 2px 0 10px;
        font-size: 11px; font-weight: 800; letter-spacing: 1px;
        text-transform: uppercase; color: #94a3b8;
    }
    .koreksi-history-head .bi { font-size: 14px; color: #cbd5e1; }

    /* Tombol "Coba lagi" pada state gagal-muat (riwayat, ranking, koreksi). */
    [data-muat-history], [data-muat-ranking], [data-muat-koreksi] {
        display: inline-flex; align-items: center; justify-content: center;
        vertical-align: middle; margin-top: 6px; padding: 7px 16px;
        border-radius: 9999px; border: 1px solid #fecdd3;
        background: var(--brand-50); color: var(--brand-600) !important;
        font-size: 12px !important; font-weight: 800; line-height: 1.1;
        text-decoration: none !important;
        transition: transform .15s ease, background .15s ease;
    }
    [data-muat-history]:active, [data-muat-ranking]:active, [data-muat-koreksi]:active {
        transform: scale(.96); background: var(--brand-100);
    }
    /* Di dalam pesan gagal-muat yang sebaris teks, tombol dibuat lebih ringkas. */
    #koreksiMuatPesan [data-muat-koreksi] { margin: 0 0 0 6px; padding: 3px 10px; font-size: 10.5px !important; }
    @media (hover: hover) {
        [data-muat-history]:hover, [data-muat-ranking]:hover, [data-muat-koreksi]:hover { background: var(--brand-100); }
    }

    /* ── FaceScan biometric modal ────────────────────────────── */
    @keyframes facescanLaser {
        0% { top: 8%; opacity: .15; }
        50% { top: 88%; opacity: .6; }
        100% { top: 8%; opacity: .15; }
    }
    .facescan-laser { animation: facescanLaser 2.2s cubic-bezier(.4,0,.2,1) infinite; }
    @keyframes facescanPop {
        0% { transform: scale(.5); opacity: 0; }
        70% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(1); opacity: 1; }
    }
    .facescan-pop { animation: facescanPop .4s cubic-bezier(.34,1.56,.64,1) forwards; }
    #facescan-modal { transition: opacity .3s ease; }
    #facescan-modal-card { transition: transform .3s ease; }
    #facescan-modal, #facescan-modal-card { font-family: 'Plus Jakarta Sans', sans-serif; }
    #facescan-modal .facescan-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }

    /* Header — ringkas, aksi di kanan, judul tidak bertabrakan */
    .fs-modal-head { display: flex; align-items: center; gap: 11px; }
    .fs-modal-icon { width: 40px; height: 40px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 14px; background: var(--brand-100); color: var(--brand-600); font-size: 19px; box-shadow: inset 0 1px 0 rgba(255,255,255,.7); }
    .fs-modal-title { margin: 0; font-size: 16px; line-height: 1.2; font-weight: 800; color: #0f172a; letter-spacing: -.2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fs-modal-sub { margin: 3px 0 0; font-size: 10.5px; line-height: 1.35; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Viewport kamera */
    .fs-viewport { position: relative; width: 100%; height: clamp(236px, 38vh, 300px); display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 20px; border: 1px solid #0f172a; background: #020617; box-shadow: inset 0 0 0 1px rgba(255,255,255,.04); }
    /* Preview selfie: di-mirror agar arah gerakan sama dengan pengguna
       (toleh kiri = tampil kiri). Deteksi wajah & foto absen tetap memakai
       frame asli video, jadi mirror ini hanya untuk tampilan. */
    #facescan-video { transform: scaleX(-1); }
    .fs-fallback { position: absolute; inset: 0; z-index: 2; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 7px; padding: 42px 22px 54px; text-align: center; background: radial-gradient(80% 65% at 50% 42%, #1e293b 0%, #0b1220 78%); }
    .fs-fallback-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; border-radius: 18px; background: rgba(148,163,184,.08); border: 1px solid rgba(148,163,184,.14); color: #64748b; font-size: 24px; transition: color .25s ease, border-color .25s ease; }
    .fs-fallback-title { margin: 0; font-size: 12.5px; font-weight: 800; color: #cbd5e1; }
    .fs-fallback-sub { margin: 0; max-width: 15rem; font-size: 10.5px; line-height: 1.45; color: #64748b; }
    .fs-deco { pointer-events: none; opacity: 0; visibility: hidden; transition: opacity .3s ease; }
    #facescan-modal.is-scanning .fs-deco { opacity: 1; visibility: visible; }
    #facescan-modal.is-error .fs-fallback-icon { color: #f87171; border-color: rgba(248,113,113,.28); background: rgba(248,113,113,.08); }
    #facescan-modal.is-error #facescan-gpsbar { opacity: .45; }
    .fs-ring { width: min(170px, 58%); aspect-ratio: 1 / 1; }

    /* Chip target + step di dalam viewport */
    .fs-step-pill { position: absolute; top: 10px; left: 10px; z-index: 9; display: inline-flex; align-items: center; gap: 6px; max-width: calc(100% - 160px); padding: 6px 10px; border-radius: 999px; background: rgba(2,6,23,.72); border: 1px solid rgba(255,255,255,.14); color: #fff; font-size: 10px; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
    #facescan-step-text { min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .fs-viewport-actions { position: absolute; top: 10px; right: 10px; z-index: 9; display: flex; align-items: center; gap: 6px; }
    .fs-target-chip { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid transparent; font-size: 9.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; white-space: nowrap; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
    .fs-target-chip.is-neutral { background: rgba(15,23,42,.72); border-color: rgba(255,255,255,.16); color: #cbd5e1; }
    .fs-target-chip.is-masuk { background: rgba(6,78,59,.78); border-color: rgba(52,211,153,.45); color: #6ee7b7; }
    .fs-target-chip.is-pulang { background: rgba(76,5,25,.78); border-color: rgba(251,113,133,.45); color: #fda4af; }
    .fs-sound-btn { width: 29px; height: 29px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; border: 1px solid rgba(255,255,255,.16); background: rgba(15,23,42,.72); color: #cbd5e1; font-size: 13px; cursor: pointer; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); transition: background .15s ease, color .15s ease, border-color .15s ease; }
    .fs-sound-btn:hover { background: rgba(30,41,59,.9); color: #fff; }
    .fs-sound-btn:active { transform: scale(.94); }
    .fs-sound-btn[aria-pressed="false"] { color: #fda4af; border-color: rgba(251,113,133,.4); background: rgba(76,5,25,.72); }
    #facescan-gpsbar { position: absolute; left: 10px; right: 10px; bottom: 10px; z-index: 9; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 10px; border-radius: 13px; background: rgba(15,23,42,.92); border: 1px solid rgba(255,255,255,.1); color: #fff; font-size: 10.5px; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
    #facescan-loc-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #facescan-loc-dist { flex-shrink: 0; padding: 2px 8px; border-radius: 999px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); font-size: 9.5px; letter-spacing: .2px; white-space: nowrap; }

    /* Panel progres */
    .fs-panel { margin-top: 12px; padding: 12px 14px; border-radius: 18px; background: #f8fafc; border: 1px solid #eef2f7; }
    .fs-status-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .fs-status-label { display: flex; align-items: flex-start; gap: 7px; min-width: 0; font-size: 12px; font-weight: 800; line-height: 1.35; color: #0f172a; }
    .fs-status-label i { flex-shrink: 0; margin-top: 1px; }
    #facescan-progress-pct { flex-shrink: 0; font-size: 11.5px; font-weight: 700; }
    .fs-bar { height: 6px; margin-top: 8px; overflow: hidden; border-radius: 999px; background: #fff; border: 1px solid #e2e8f0; }
    .fs-hint-row { display: flex; align-items: flex-start; gap: 6px; margin-top: 9px; padding-top: 9px; border-top: 1px solid #e8eef6; font-size: 10.5px; line-height: 1.45; color: #64748b; }
    .fs-hint-row i { flex-shrink: 0; margin-top: 1px; color: #94a3b8; }
    .fs-hint-label { flex-shrink: 0; font-weight: 700; color: #475569; }
    #facescan-explainer { font-weight: 700; color: #334155; }

    /* Error + aksi */
    #facescan-error { display: flex; align-items: flex-start; gap: 7px; margin-top: 10px; padding: 10px 12px; border-radius: 16px; background: #fef2f2; border: 1px solid #fecaca; color: #be123c; font-size: 11.5px; font-weight: 600; line-height: 1.45; }
    #facescan-error.hidden { display: none; }
    #facescan-error i { flex-shrink: 0; margin-top: 1px; }
    .fs-actions { display: flex; gap: 8px; margin-top: 12px; }
    .fs-btn { flex: 1; height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: none; border-radius: 15px; font-size: 12.5px; font-weight: 800; letter-spacing: .1px; cursor: pointer; transition: transform .15s ease, filter .15s ease, opacity .15s ease; }
    .fs-btn:active { transform: scale(.97); }
    .fs-btn-secondary { background: #f1f5f9; color: #475569; }
    .fs-btn-primary { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; box-shadow: 0 10px 22px -14px rgba(15,23,42,.9); }
    .fs-btn-brand { background: linear-gradient(135deg, var(--brand-500) 0%, var(--brand-600) 100%); color: #fff; box-shadow: 0 10px 22px -14px rgb(var(--brand-600-rgb) / .9); }
    .fs-btn-primary:active, .fs-btn-brand:active { filter: brightness(.94); }
    .fs-privacy { display: flex; align-items: center; justify-content: center; gap: 5px; margin: 10px 0 0; font-size: 10px; font-weight: 600; line-height: 1.4; color: #94a3b8; text-align: center; }
    .fs-privacy i { color: #10b981; font-size: 11px; }
    .fs-legacy-btn { width: 100%; height: 38px; margin-top: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid #fecdd3; border-radius: 13px; background: #fff; color: #be123c; font-size: 11px; font-weight: 800; cursor: pointer; }
    .fs-legacy-btn.hidden { display: none; }
    #facescan-ring.facescan-ring-ok { border-color: rgba(45,212,191,.95); box-shadow: 0 0 28px rgba(45,212,191,.45); }

    /* Aksi menyesuaikan keadaan: saat memindai hanya "Batalkan",
       saat penolakan informatif hanya "Mengerti", saat sukses footer hilang.
       Tombol "Pindai Ulang" hanya muncul bila memang ada yang bisa diulang. */
    #facescan-modal #facescan-retry { display: none; }
    #facescan-modal.is-error #facescan-retry,
    #facescan-modal.is-warn #facescan-retry { display: inline-flex; }
    #facescan-modal.is-success .fs-panel,
    #facescan-modal.is-success .fs-actions,
    #facescan-modal.is-success .fs-privacy,
    #facescan-modal.is-success .fs-step-pill,
    #facescan-modal.is-success .fs-viewport-actions,
    #facescan-modal.is-success #facescan-gpsbar,
    #facescan-modal.is-success #facescan-legacy { display: none; }
    #facescan-modal.is-success #facescan-viewport { border-color: transparent; }
    /* Saat ditolak, panel progres tidak perlu: status + alasan sudah tampil
       di viewport dan kotak error, jadi hilangkan duplikasi teks. */
    #facescan-modal.is-error .fs-panel,
    #facescan-modal.is-warn .fs-panel,
    #facescan-modal.is-info .fs-panel { display: none; }
    /* Viewport dipadatkan agar tidak menyisakan ruang kosong besar
       ketika pemindaian berhenti karena lokasi/wajah/kamera. */
    #facescan-modal.is-error .fs-viewport,
    #facescan-modal.is-warn .fs-viewport,
    #facescan-modal.is-info .fs-viewport { height: clamp(196px, 28vh, 238px); }
    #facescan-modal.is-error .fs-step-pill,
    #facescan-modal.is-warn .fs-step-pill,
    #facescan-modal.is-info .fs-step-pill { display: none; }

    /* Nada notifikasi — tidak semua penolakan adalah kegagalan.
       'warn' = guru bisa lanjut setelah paham (belum waktunya, sudah absen),
       'info' = tidak ada yang salah, cukup ditutup. Hanya 'error' yang merah. */
    #facescan-error.is-warn { background: #fffbeb; border-color: #fde68a; color: #b45309; }
    #facescan-error.is-info { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    #facescan-modal.is-warn .fs-fallback-icon { color: #fbbf24; border-color: rgba(251,191,36,.3); background: rgba(251,191,36,.1); }
    #facescan-modal.is-info .fs-fallback-icon { color: #60a5fa; border-color: rgba(96,165,250,.3); background: rgba(96,165,250,.1); }
    #facescan-modal.is-warn .fs-fallback-title,
    #facescan-modal.is-info .fs-fallback-title { color: #e2e8f0; }

    /* Struk keberhasilan */
    .fs-receipt { display: flex; flex-direction: column; align-items: center; gap: 7px; width: 100%; padding: 0 18px; }
    .fs-receipt-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 999px; font-size: 10px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; background: rgba(16,185,129,.18); border: 1px solid rgba(52,211,153,.5); color: #6ee7b7; }
    .fs-receipt-pill.is-warn { background: rgba(245,158,11,.18); border-color: rgba(251,191,36,.5); color: #fcd34d; }
    .fs-receipt-pill.is-info { background: rgba(59,130,246,.18); border-color: rgba(147,197,253,.5); color: #bfdbfe; }
    .fs-receipt-note { margin: 0; font-size: 11.5px; color: #cbd5e1; text-align: center; line-height: 1.45; max-width: 19rem; }
    .fs-receipt-meta { display: flex; flex-direction: column; gap: 3px; margin-top: 3px; font-size: 10.5px; color: #94a3b8; text-align: center; }
    .fs-receipt-meta b { color: #e2e8f0; font-weight: 700; }
    #facescan-success-close { margin-top: 12px; height: 40px; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid rgba(255,255,255,.24); border-radius: 999px; background: rgba(255,255,255,.12); color: #fff; font-size: 12px; font-weight: 800; cursor: pointer; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
    #facescan-success-close:active { transform: scale(.97); }
    @media (max-height: 680px) {
        .fs-viewport { height: clamp(210px, 32vh, 240px); }
        .fs-panel { padding: 10px 12px; }
        .fs-fallback-icon { width: 46px; height: 46px; font-size: 21px; }
        .fs-fallback-sub { display: none; }
        .fs-privacy { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .facescan-laser, .facescan-pop { animation: none !important; }
    }

    /* ── Section headers ────────────────────────────────────── */
    .section-header { padding: 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .section-header h5 { margin: 0; font-weight: 800; font-size: 17px; color: #0f172a; letter-spacing: -.3px; }
    .timeline-container { padding: 0; }

    /* ── Clean White Tailwind Cards ─────────────────────────── */
    .m3-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 16px;
        margin-bottom: 12px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 3px 14px rgba(15, 23, 42, 0.03);
    }
    .m3-card-elevated {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
    }

    /* ── Avatar orang ───────────────────────────────────────── */
    /* Ukuran dikunci lewat CSS, bukan atribut width/height: preflight Tailwind
       menyetel img { height: auto }, sehingga atribut height kalah dan tiap
       foto tampil dengan rasio aslinya — ada yang tampak besar, ada yang kecil. */
    .foto-orang {
        flex-shrink: 0;
        width: 44px; height: 44px;
        object-fit: cover;
        border-radius: 12px;
        border: 1.5px solid #fff;
        background: #f1f5f9;
    }
    .foto-orang-lg { width: 64px; height: 64px; border-radius: 16px; }

    /* ── Activity cards ─────────────────────────────────────── */
    .activity-card {
        border-radius: 18px; padding: 14px 16px; margin-bottom: 10px;
        display: flex; align-items: center; gap: 14px;
        transition: all .15s ease;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
    }
    .activity-card:active { background: #f8fafc; }
    .activity-icon { border-radius: 14px; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; font-size: 20px; flex-shrink: 0; }
    .bg-checkin  { background: #E8F5E9; color: #2E7D32; }
    .bg-checkout { background: #FFF3E0; color: #EF6C00; }
    .bg-dinas    { background: #E3F2FD; color: #1565C0; }
    .activity-info h6 { margin: 0; font-weight: 700; font-size: 13.5px; color: #0f172a; }
    .activity-info p  { margin: 0; font-size: 11.5px; color: #64748b; }
    .activity-time    { font-weight: 800; margin-left: auto; font-size: 13.5px; color: var(--brand-600); }

    /* ── Attendance cards (detailed check-in/out) ───────────── */
    .attendance-card {
        border-radius: 20px; padding: 16px;
        overflow: hidden; position: relative;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    }
    .attendance-card-head {
        display: flex; align-items: center; gap: 12px;
        padding-bottom: 12px; margin-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .attendance-card-icon {
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; width: 40px; height: 40px;
        background: #f8fafc; color: var(--accent, var(--brand-600));
        border: 1px solid #f1f5f9;
    }
    .attendance-card-title    { font-weight: 800; line-height: 1.25; margin: 0; letter-spacing: -.2px; font-size: 14.5px; color: #0f172a; }
    .attendance-card-subtitle { font-weight: 600; font-size: 11px; color: #64748b; margin: 2px 0 0; }
    .attendance-time          { margin-left: auto; text-align: right; line-height: 1.25; }
    .attendance-time-main     { font-weight: 900; font-size: 20px; letter-spacing: -.8px; color: var(--accent, var(--brand-600)); }
    .attendance-time-sub      { font-weight: 700; margin-top: 2px; font-size: 10px; color: #94a3b8; }
    .attendance-photo {
        border-radius: 16px; overflow: hidden; aspect-ratio: 1;
        border: 1px solid #f1f5f9; background: #f8fafc;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    }
    .attendance-photo img  { width: 100%; height: 100%; object-fit: cover; display: block; }
    .attendance-label      { font-weight: 800; text-transform: uppercase; margin-bottom: 2px; font-size: 9.5px; letter-spacing: .08em; color: #94a3b8; }
    .attendance-location   { font-weight: 800; line-height: 1.35; margin-bottom: 4px; color: #0f172a; font-size: 13.5px; }
    .attendance-meta       { display: flex; align-items: center; gap: 5px; font-weight: 600; color: #64748b; font-size: 11px; }

    /* ── Chips ──────────────────────────────────────────────── */
    .chip {
        display: inline-flex; align-items: center; gap: 3px;
        font-weight: 700; border-radius: 9999px; white-space: nowrap;
        padding: 3px 8px; font-size: 10px; letter-spacing: .01em;
    }
    .chip-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .chip-danger  { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .chip-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .chip-primary { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .chip-solid   { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25); }

    /* ── Calendar ───────────────────────────────────────────── */
    .calendar-grid    { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-top: 10px; }
    .cal-day-header   { text-align: center; font-weight: 700; padding-bottom: 6px; font-size: 10px; color: #64748b; }
    .cal-day {
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; border-radius: 12px;
        position: relative; transition: all .2s;
        aspect-ratio: 1; font-size: 12.5px;
        color: #1e293b; background: #f8fafc;
        border: 1px solid #f1f5f9;
    }
    .cal-day.today { background: var(--brand-600); color: #fff; border-color: var(--brand-600); box-shadow: 0 3px 10px rgb(var(--brand-600-rgb) / 0.35); }
    .cal-day.has-status::after {
        content: ''; position: absolute; bottom: 4px;
        width: 4px; height: 4px; border-radius: 50%; background: currentColor;
    }

    /* ── Form Inputs ────────────────────────────────────────── */
    /* 16px = ambang iOS agar fokus input tidak memicu zoom otomatis */
    .m3-input {
        width: 100%; min-width: 0; color: #0f172a; background: #f8fafc; outline: none;
        border: 1.5px solid #e2e8f0; border-radius: 14px;
        padding: 10px 14px; font-size: 16px; font-weight: 500; line-height: 1.35;
        transition: all 0.2s ease;
    }
    .m3-input::placeholder { font-size: 14px; color: #94a3b8; }
    .m3-input:focus {
        background: #ffffff; border-color: var(--brand-600);
        box-shadow: 0 0 0 3.5px rgb(var(--brand-600-rgb) / 0.12);
    }
    select.m3-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 2l5 4 5-4' stroke='%2364748b' stroke-width='1.75' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 36px;
    }

    /* Input terkunci ikut terlihat terkunci — dipakai antara lain oleh
       pemilih tanggal koreksi saat datanya gagal dimuat. */
    .m3-input:disabled { opacity: .6; cursor: not-allowed; }

    /* ── Tab content ──────────────────────────────────────────
       Perpindahan tab diganti sinkron (lihat showTab) lalu diberi animasi
       masuk lewat .fade-in-up, bukan fadeIn/fadeOut jQuery: animasi asinkron
       meninggalkan jendela balapan bila guru mengetuk nav cepat.
       Versi tab sengaja memulai dari opacity .35 (bukan 0): kalau animasi
       tidak sempat berjalan — webview ditahan di latar — konten tetap terbaca. */
    .main-content { margin-top: 14px; }
    .tab-content-tw.fade-in-up { animation: tabIn .28s ease-out; }
    @keyframes tabIn {
        0%   { opacity: .35; transform: translateY(10px); }
        100% { opacity: 1;   transform: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .fade-in-up, .tab-content-tw.fade-in-up { animation: none; }
    }

    /* Tombol kembali ikon di header tab — target sentuh minimal 42px */
    .btn-back {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 42px; min-height: 42px; padding: 9px;
        border-radius: 9999px; flex-shrink: 0;
    }
    .btn-back:active { transform: scale(.94); }
    .btn-back i { pointer-events: none; }

    /* Tombol submit form — umpan balik tekan (Tailwind scale-98 tidak tersedia) */
    .btn-submit-mobile { transition: transform .15s ease, filter .15s ease; }
    .btn-submit-mobile:active { transform: scale(.98); filter: brightness(.97); }

    /* Nominal denda di kartu ranking — target sentuh lebih besar dari teksnya */
    .score-denda {
        display: inline-flex; align-items: center; gap: 2px;
        margin: 2px 0 -2px auto; padding: 3px 0; min-height: 22px;
        background: none; border: 0; cursor: pointer;
        font: inherit; font-size: 10.5px; font-weight: 800; line-height: 1;
        color: #dc2626; text-decoration: none;
        -webkit-tap-highlight-color: transparent;
    }
    .score-denda:active { opacity: .65; }

    /* ── Stats Cards & Material UI ───────────────────────────── */
    .stat-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 16px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card:active { transform: scale(0.99); }
    .stat-label {
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        display: block;
        margin-bottom: 4px;
    }
    .stat-value {
        font-size: 26px;
        font-weight: 900;
        color: #0f172a;
        line-height: 1.15;
        letter-spacing: -0.5px;
    }
    .stat-sub {
        font-size: 11.5px;
        color: #94a3b8;
        font-weight: 500;
        margin-top: 4px;
    }
    .progress-m3 {
        background: #f1f5f9;
        border-radius: 9999px;
        height: 8px;
        overflow: hidden;
        margin-top: 10px;
        width: 100%;
    }
    .progress-m3-bar {
        background: linear-gradient(90deg, #10b981 0%, #14b8a6 100%);
        height: 100%;
        border-radius: 9999px;
        transition: width 0.5s ease;
    }
    .action-item.active .action-icon {
        box-shadow: 0 0 0 2.5px var(--brand-600), 0 4px 12px rgb(var(--brand-600-rgb) / 0.25);
        transform: scale(1.06);
    }
    .action-item.active .action-label {
        color: var(--brand-600);
        font-weight: 800;
    }

    /* ── Skeleton loading ───────────────────────────────────── */
    .skeleton-text { height: 12px; border-radius: 6px; }
</style>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&display=swap" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Dimuat di awal konten: skrip inline bagian bawah halaman sudah memanggil witaNow(). -->
<script src="<?= base_url('assets/js/wita-clock.js?v=' . (@filemtime(FCPATH . 'assets/js/wita-clock.js') ?: time())) ?>"></script>
<script>
    // Jam perangkat bisa WIB/WIT; server selalu WITA. Sekali saja: stamp
    // dibekukan saat render, jadi witaInit() ulang dengan stamp identik justru
    // membekukan jam di waktu load.
    witaInit('<?= date('Y-m-d H:i:s') ?>');
</script>
<div id="ptr-element">
    <!-- Hero Section -->
    <div class="hero-section">
        <!-- Background Graphic Decorations -->
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

        <div class="user-profile">
            <div class="greeting-text">
                <?php
                $hour = date('H');
                $greetTime = $hour < 12 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
                $fullName = !empty($user->nama_guru) ? $user->nama_guru : (!empty($user->first_name) ? trim($user->first_name . ' ' . $user->last_name) : $user->username);
                $firstName = explode(' ', trim($fullName))[0];
                ?>
                <p class="mb-1"><?= $greetTime ?>,</p>
                <h4><?= esc($firstName) ?>,</h4>

            </div>
            <div class="avatar-wrapper">
                <a href="<?= site_url('presensi/profile') ?>" class="block relative">
                    <?php $avatar = \App\Services\AvatarService::tampilan($user->foto ?? '', $fullName); ?>
                    <img src="<?= esc($avatar, 'attr') ?>" alt="Avatar" class="avatar-main shadow-sm" onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
                    <?php if ($attendance && $attendance->jam_masuk && !$attendance->jam_pulang): ?>
                        <span class="absolute bottom-0 end-0 rounded-full" style="width: 13px; height: 13px; background: #10b981; border: 2.5px solid #fff; margin-bottom: 2px; margin-right: 2px;"></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <?php
        // ── Deteksi Status Kehadiran Khusus (Izin, Sakit, Cuti, Dinas Luar) ──
        $statusMasukRaw  = strtoupper(trim((string) ($attendance->status_masuk ?? '')));
        $statusPulangRaw = strtoupper(trim((string) ($attendance->status_pulang ?? '')));
        $permitJenis     = strtolower(trim((string) ($todayPermit->jenis ?? '')));
        $permitStatus    = strtolower(trim((string) ($todayPermit->status ?? '')));

        $isIzin  = ($statusMasukRaw === 'IJIN' || $statusMasukRaw === 'IZIN' || ($todayPermit && in_array($permitJenis, ['ijin', 'izin']) && $permitStatus === 'approved'));
        $isSakit = ($statusMasukRaw === 'SAKIT' || ($todayPermit && $permitJenis === 'sakit' && $permitStatus === 'approved'));
        $isCuti  = ($statusMasukRaw === 'CUTI' || ($todayPermit && $permitJenis === 'cuti' && $permitStatus === 'approved'));
        $isDinas = (strpos($statusMasukRaw, 'DINAS') !== false || ($todayPermit && in_array($permitJenis, ['dinas', 'dinas luar', 'tugas luar']) && $permitStatus === 'approved'));
        $isSpecialLeave = ($isIzin || $isSakit || $isCuti || $isDinas);
        $isPendingPermit = (!$isSpecialLeave && $todayPermit && $permitStatus === 'pending');

        $specialType   = null;
        $specialTitle  = '';
        $specialBadge  = '';
        $specialColor  = '#64748b';
        $specialBg     = '#f8fafc';
        $specialBorder = '#e2e8f0';
        $specialIcon   = 'bi-info-circle-fill';
        $specialReason = $attendance->keterangan ?? ($todayPermit->keterangan ?? ($todayPermit->alasan ?? ''));

        if ($isIzin) {
            $specialType   = 'izin';
            $specialTitle  = 'Izin Tidak Hadir';
            $specialBadge  = 'Izin Resmi';
            $specialColor  = '#d97706';
            $specialBg     = '#fffbeb';
            $specialBorder = '#fde68a';
            $specialIcon   = 'bi-calendar2-check-fill';
        } elseif ($isSakit) {
            $specialType   = 'sakit';
            $specialTitle  = 'Keterangan Sakit';
            $specialBadge  = 'Surat Sakit';
            $specialColor  = '#e11d48';
            $specialBg     = '#fff1f2';
            $specialBorder = '#fecdd3';
            $specialIcon   = 'bi-heart-pulse-fill';
        } elseif ($isCuti) {
            $specialType   = 'cuti';
            $specialTitle  = 'Cuti Kerja';
            $specialBadge  = 'Cuti Resmi';
            $specialColor  = '#7c3aed';
            $specialBg     = '#f5f3ff';
            $specialBorder = '#ddd6fe';
            $specialIcon   = 'bi-umbrella-fill';
        } elseif ($isDinas) {
            $specialType   = 'dinas';
            $specialTitle  = 'Tugas Dinas Luar';
            $specialBadge  = ($statusMasukRaw === 'DINAS_PENDING') ? 'Dinas (Pending)' : 'Dinas Luar';
            $specialColor  = '#0284c7';
            $specialBg     = '#f0f9ff';
            $specialBorder = '#bae6fd';
            $specialIcon   = 'bi-briefcase-fill';
        }

        // Custom greeting jika ada izin/sakit/cuti/dinas
        $effectiveGreeting = $greeting;
        if ($isSakit) {
            $effectiveGreeting = "Semoga lekas sembuh dan pulih kembali!";
        } elseif ($isIzin) {
            $effectiveGreeting = "Izin Anda tercatat resmi. Selamat beraktivitas.";
        } elseif ($isCuti) {
            $effectiveGreeting = "Cuti Anda tercatat resmi. Nikmati waktu istirahat.";
        } elseif ($isDinas) {
            $effectiveGreeting = "Selamat bertugas dinas luar hari ini!";
        }
        ?>

        <?php if (!empty($effectiveGreeting)): ?>
        <div class="motivation-box" role="status">
            <i class="bi bi-stars" aria-hidden="true"></i>
            <span><?= esc($effectiveGreeting) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Clock & Status Card -->
    <div class="status-card">
        <div class="clock-display">
            <div id="digitalClock"><?= date('H:i:s') ?></div>
            <span class="clock-tz">WITA</span>
            <div id="digitalDate">Mendeteksi waktu...</div>
        </div>
        
        <div class="mini-status-grid">
            <?php
            // Badge status masuk/pulang ala referensi
            $jamMasukTampil = ($attendance && !empty($attendance->jam_masuk) && strpos((string) $attendance->jam_masuk, '0000-00-00') !== 0)
                ? date('H:i', strtotime($attendance->jam_masuk)) : null;
            $jamPulangTampil = ($attendance && !empty($attendance->jam_pulang) && strpos((string) $attendance->jam_pulang, '0000-00-00') !== 0)
                ? date('H:i', strtotime($attendance->jam_pulang)) : null;

            $stMasuk = strtoupper(trim((string) ($attendance->status_masuk ?? '')));
            $telatMnt = (int) ($attendance->telat_menit ?? 0);
            if ($jamMasukTampil !== null) {
                if ($stMasuk === 'ONTIME') {
                    $masukBadge = '<span class="box-pill pill-ok"><i class="bi bi-check-circle-fill"></i>Tepat Waktu</span>';
                } elseif ($stMasuk === 'LATE') {
                    $masukBadge = '<span class="box-pill pill-warn"><i class="bi bi-exclamation-circle-fill"></i>Terlambat' . ($telatMnt > 0 ? ' ' . $telatMnt . ' mnt' : '') . '</span>';
                } else {
                    $statusLabel = ucwords(strtolower(str_replace('_', ' ', (string) $attendance->status_masuk)));
                    $masukBadge = '<span class="box-pill pill-ok"><i class="bi bi-check-circle-fill"></i>' . esc($statusLabel) . '</span>';
                }
            } else {
                $masukBadge = '<span class="box-pill pill-mute">Belum Absen</span>';
            }
            if ($jamPulangTampil !== null) {
                $pulangBadge = '<span class="box-pill pill-mute"><i class="bi bi-check-circle"></i>Selesai Shift</span>';
            } elseif ($jamMasukTampil !== null) {
                $pulangBadge = '<span class="box-pill pill-mute">Belum Pulang</span>';
            } else {
                $pulangBadge = '<span class="box-pill pill-mute">--:--</span>';
            }
            ?>
            <div class="mini-status-box box-masuk">
                <span class="box-title"><i class="bi bi-arrow-down-left"></i>Jam Masuk</span>
                <span class="box-value"><?= $jamMasukTampil ?? '--:--' ?></span>
                <?= $masukBadge ?>
            </div>
            <div class="mini-status-box box-pulang">
                <span class="box-title"><i class="bi bi-arrow-up-right"></i>Jam Pulang</span>
                <span class="box-value"><?= $jamPulangTampil ?? '--:--' ?></span>
                <?= $pulangBadge ?>
            </div>
        </div>
    </div>

    <?php if (!$face_registered): ?>
    <div class="mx-4 mt-3">
        <div class="flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-[13px] text-amber-900 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 shrink-0"></i>
            <span class="grow">Wajah belum didaftarkan. <a href="<?= base_url('presensi/profile') ?>" class="font-bold text-amber-800 underline ml-1 hover:text-amber-900">Daftar sekarang</a></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Grid: Utama -->
    <div class="mini-section-title">Akses Pintar</div>
    <div class="action-grid pb-1">
        <button type="button" class="action-item" id="btn-absen">
            <span class="action-icon" style="background: #ef4444;">
                <i class="bi bi-person-bounding-box" aria-hidden="true"></i>
            </span>
            <span class="action-label">Absen</span>
        </button>
        <a class="action-item" href="<?= base_url('guru/presensi-siswa' . (session()->get('user_id') ? '?h=' . \App\Controllers\Guru\PresensiSiswaQr::generateHash((int)session()->get('user_id')) : '')) ?>">
            <span class="action-icon" style="background: #16a34a;">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
            </span>
            <span class="action-label">Scan QR</span>
        </a>
        <button type="button" class="action-item" data-tab="izin" aria-controls="content-izin">
            <span class="action-icon" style="background: var(--brand-secondary);">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
            </span>
            <span class="action-label">Izin/Sakit</span>
        </button>
        <button type="button" class="action-item" data-tab="koreksi" aria-controls="content-koreksi">
            <span class="action-icon" style="background: #22c55e;">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
            </span>
            <span class="action-label">Koreksi</span>
        </button>
    </div>

    <!-- Toggle Button -->
    <div class="flex justify-center px-4 mb-1">
        <button type="button" class="btn-toggle-menu" id="btnToggleMenu" onclick="toggleExtraMenu()" aria-expanded="false" aria-controls="extraMenuContainer">
            <span id="toggleMenuText">Menu Lainnya</span>
            <i class="bi bi-chevron-down ms-1" id="toggleMenuIcon" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Action Grid: Menu Tambahan (Toggleable) -->
    <div id="extraMenuContainer" style="display: none;">
        <div class="action-grid pt-2 pb-2">
            <a class="action-item" href="<?= base_url('guru/jurnal') ?>">
                <span class="action-icon" style="background: #ECFEFF; color: #0891b2;">
                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                </span>
                <span class="action-label">Jurnal</span>
            </a>
            <button type="button" class="action-item" data-tab="history" aria-controls="content-history">
                <span class="action-icon" style="background: #f59e0b;">
                    <i class="bi bi-graph-up" aria-hidden="true"></i>
                </span>
                <span class="action-label">Statistik</span>
            </button>
            <a class="action-item" href="<?= base_url('guru/piket') ?>">
                <span class="action-icon" style="background: #F0FDF4; color: #16a34a;">
                    <i class="bi bi-people" aria-hidden="true"></i>
                </span>
                <span class="action-label">Piket</span>
            </a>
            <a class="action-item" href="<?= base_url('guru/reward') ?>">
                <span class="action-icon" style="background: #F3E8FF; color: var(--accent-600);">
                    <i class="bi bi-award-fill" aria-hidden="true"></i>
                </span>
                <span class="action-label">Poin</span>
            </a>
            <a class="action-item" href="<?= base_url('guru/lembur') ?>">
                <span class="action-icon" style="background: #FFFBEB; color: #d97706;">
                    <i class="bi bi-clock-fill" aria-hidden="true"></i>
                </span>
                <span class="action-label">Lembur</span>
            </a>
            <a class="action-item" href="<?= base_url('guru/kalender') ?>">
                <span class="action-icon" style="background: var(--primary-50); color: var(--primary-600);">
                    <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
                </span>
                <span class="action-label">Kalender</span>
            </a>
            <button type="button" class="action-item" id="btn-teacher-qr">
                <span class="action-icon" style="background: #FAF5FF; color: var(--accent-600);">
                    <i class="bi bi-qr-code" aria-hidden="true"></i>
                </span>
                <span class="action-label">Kartu QR</span>
            </button>
            <button type="button" class="action-item" id="btn-rekap-pdf" onclick="openRekapModal()">
                <span class="action-icon" style="background: #FEF2F2; color: #dc2626;">
                    <i class="bi bi-file-earmark-pdf-fill" aria-hidden="true"></i>
                </span>
                <span class="action-label">Rekap PDF</span>
            </button>
        </div>
    </div>

    <!-- Jadwal Hari Ini -->
    <?php if (!empty($jadwal)): ?>
    <?php $belumDijurnal = count(array_filter($jadwal, static fn($x) => empty($x['sudah_dijurnal']) && empty($x['menunggu_validasi']))); ?>
    <?php $menungguValidasi = count(array_filter($jadwal, static fn($x) => !empty($x['menunggu_validasi']))); ?>
    <div class="px-4 mt-3">
        <div class="section-header px-0">
            <h5><i class="bi bi-calendar-week me-2 text-brand-600"></i>Jadwal Hari Ini</h5>
            <div class="flex items-center gap-2">
                <?php if (!empty($is_holiday)): ?>
                <span class="badge-tw badge-secondary text-[10px] font-semibold"><i class="bi bi-calendar-x me-1"></i>Hari libur</span>
                <?php elseif ($belumDijurnal > 0): ?>
                <span class="badge-tw badge-info text-[10px] font-semibold"><?= (int) $belumDijurnal ?> belum dijurnal</span>
                <?php elseif ($menungguValidasi > 0): ?>
                <span class="badge-tw badge-warning text-[10px] font-semibold"><i class="bi bi-shield-check me-1"></i><?= (int) $menungguValidasi ?> menunggu validasi</span>
                <?php else: ?>
                <span class="badge-tw badge-success text-[10px] font-semibold"><i class="bi bi-check2-circle me-1"></i>Semua terjurnal</span>
                <?php endif; ?>
                <a href="<?= base_url('guru/jadwal') ?>" class="text-sm font-semibold text-brand-600 no-underline">Lihat Semua</a>
            </div>
        </div>
        <?php 
        $waktuSekarang = date('H:i');
        $kelasSekarang = null;
        foreach ($jadwal as $j):
            if ($waktuSekarang >= $j['dari'] && $waktuSekarang <= $j['sampai']) {
                $kelasSekarang = $j;
            }
        endforeach;
        ?>
        <?php if ($kelasSekarang): ?>
        <div class="mb-3 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-900" role="status">
            <i class="bi bi-bell-fill text-emerald-600"></i>
            <div>Sedang jam mengajar <b><?= esc($kelasSekarang['nama_mapel']) ?></b> di <b><?= esc($kelasSekarang['nama_kelas']) ?></b></div>
        </div>
        <?php endif; ?>
        <div class="flex gap-2 overflow-x-auto pb-2" style="-webkit-overflow-scrolling: touch;">
            <?php foreach ($jadwal as $j):
                $isSekarang = ($waktuSekarang >= $j['dari'] && $waktuSekarang <= $j['sampai']);
                $isAkanDatang = ($waktuSekarang < $j['dari']);
            ?>
            <div class="shrink-0 w-[215px] rounded-2xl bg-white border border-slate-100 p-3.5 shadow-sm transition-all duration-200 <?= $isSekarang ? 'shadow-md ring-1 ring-emerald-500/20' : '' ?>">
                <div>
                    <div class="text-[13.5px] font-bold text-slate-800 line-clamp-1"><?= esc($j['nama_mapel']) ?></div>
                    <div class="text-xs text-slate-400 mt-0.5"><?= esc($j['nama_kelas']) ?></div>
                    <div class="mt-2.5 flex justify-between items-center">
                        <span class="badge-tw badge-secondary text-[11px] font-medium"><?= $j['dari'] ?> - <?= $j['sampai'] ?></span>
                        <?php if ($isSekarang): ?>
                        <span class="badge-tw badge-success text-[10px] font-semibold"><i class="bi bi-play-fill"></i> Sekarang</span>
                        <?php elseif ($isAkanDatang): ?>
                        <span class="badge-tw badge-info text-[10px] font-semibold">Akan Datang</span>
                        <?php else: ?>
                        <span class="badge-tw badge-secondary text-[10px]">Selesai</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2.5 flex items-center gap-1.5">
                        <?php if ($isSekarang): ?>
                        <a href="<?= base_url("guru/presensi-siswa/kelas/{$j['id_kelas']}?id_mapel=" . (int) ($j['id_mapel'] ?? 0)) ?>" class="btn-tw btn-tw-sm bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex-1 rounded-xl flex items-center justify-center gap-1 shadow-xs">
                            <i class="bi bi-people-fill me-1"></i>Presensi
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($j['sudah_dijurnal']) && (int) ($j['id_jurnal'] ?? 0) > 0): ?>
                        <a href="<?= base_url('guru/jurnal/edit/' . (int) $j['id_jurnal']) ?>" class="btn-tw btn-tw-sm font-semibold flex-1 rounded-xl flex items-center justify-center gap-1 shadow-xs" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                            <i class="bi bi-check2-circle me-1"></i>Sudah Dijurnal
                        </a>
                        <?php elseif (!empty($j['menunggu_validasi']) && (int) ($j['id_jurnal'] ?? 0) > 0): ?>
                        <?php $ditolak = strtoupper((string) ($j['validasi_status'] ?? '')) === 'REJECTED'; ?>
                        <a href="<?= base_url(($ditolak ? 'guru/jurnal/edit/' : 'guru/jurnal/detail/') . (int) $j['id_jurnal']) ?>" class="btn-tw btn-tw-sm font-semibold flex-1 rounded-xl flex items-center justify-center gap-1 shadow-xs" style="background: <?= $ditolak ? '#fff1f2' : '#fffbeb' ?>; color: <?= $ditolak ? '#be123c' : '#b45309' ?>; border: 1px solid <?= $ditolak ? '#fecdd3' : '#fde68a' ?>;">
                            <i class="bi <?= $ditolak ? 'bi-x-circle' : 'bi-shield-check' ?> me-1"></i><?= $ditolak ? 'Perbaiki' : 'Menunggu Validasi' ?>
                        </a>
                        <?php else: ?>
                        <a href="<?= base_url('guru/jurnal/create') . '?' . http_build_query(['id_jadwal' => (int) ($j['id_jadwal'] ?? 0), 'kelas' => (int) $j['id_kelas'], 'mapel' => (int) ($j['id_mapel'] ?? 0), 'kembali' => 'absen']) ?>" class="btn-tw btn-tw-sm font-semibold flex-1 rounded-xl flex items-center justify-center gap-1 shadow-xs" style="background: var(--primary-600); color: #fff;">
                            <i class="bi bi-journal-text me-1"></i>Isi Jurnal
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Events -->
    <?php if (!empty($upcomingEvents)): ?>
    <div class="px-4 mt-3">
        <div class="section-header px-0">
            <h5><i class="bi bi-megaphone me-2 text-amber-500"></i>Event Mendatang</h5>
            <a href="<?= base_url('guru/kalender') ?>" class="text-sm font-semibold text-brand-600 no-underline">Kalender</a>
        </div>
        <div class="space-y-2">
            <?php foreach ($upcomingEvents as $ev): ?>
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border border-slate-100 shadow-xs hover:shadow-sm transition">
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex flex-col items-center justify-center shrink-0">
                    <div class="font-bold leading-none text-base"><?= date('d', strtotime($ev->tgl_mulai)) ?></div>
                    <div class="text-[9.5px] uppercase font-bold text-amber-600/75 mt-0.5"><?= date('M', strtotime($ev->tgl_mulai)) ?></div>
                </div>
                <div class="min-w-0 grow">
                    <div class="font-bold text-[13.5px] text-slate-800 truncate"><?= esc($ev->judul) ?></div>
                    <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5">
                        <span class="badge-tw badge-secondary text-[10px] font-semibold text-slate-600 px-2 py-0.5 rounded-full"><?= $ev->tipe ?></span>
                        <?php if ($ev->tgl_selesai): ?>
                        <span><?= date('d/m', strtotime($ev->tgl_mulai)) ?> - <?= date('d/m', strtotime($ev->tgl_selesai)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Content Sections -->
    <div class="main-content">
        <!-- Home / Timeline -->
        <div id="content-timeline" class="tab-content-tw fade-in-up px-4 pb-5">
            <div class="section-header px-0">
                <h5>Timeline Hari Ini</h5>
                <a href="#" class="text-sm font-semibold text-brand-600 no-underline" style="padding:6px;margin:-6px;display:inline-flex;border-radius:9999px;" onclick="location.reload();return false;" aria-label="Muat ulang halaman" title="Muat ulang"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></a>
            </div>
            
            <div class="timeline-container pb-0 px-0">
                <?php if ($attendance || $isSpecialLeave || $isPendingPermit): ?>
                    <!-- Jam Kerja (penuh satu baris; poin hari ini menempel di badge status) -->
                    <div class="mb-3 mx-0">
                          <div>
                              <?php
                              $namaHariPanjang = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][(int)date('w')];
                              $jkMasuk = $jamKerja['masuk'] ?? substr((string)($setting->jam_masuk ?? '07:00'), 0, 5);
                              $jkPulang = $jamKerja['pulang'] ?? substr((string)($setting->jam_pulang ?? '15:00'), 0, 5);
                              // Durasi shift untuk label — sadar shift lintas tengah malam
                              $jkDurJam = 0;
                              try {
                                  $aMin = ((int)substr($jkMasuk, 0, 2)) * 60 + (int)substr($jkMasuk, 3, 2);
                                  $bMin = ((int)substr($jkPulang, 0, 2)) * 60 + (int)substr($jkPulang, 3, 2);
                                  $jkDurMenit = $bMin > $aMin ? ($bMin - $aMin) : ($bMin + 1440 - $aMin);
                                  $jkDurJam = (int) round($jkDurMenit / 60);
                              } catch (\Throwable $e) {}
                              $jkNormDt = function ($v) { $v = trim((string) $v); if ($v === '' || strpos($v, '0000-00-00') === 0) return null; return $v; };
                              $jkSudahMasuk = $attendance && $jkNormDt($attendance->jam_masuk) !== null;
                              $jkSudahPulang = $jkSudahMasuk && $jkNormDt($attendance->jam_pulang) !== null;
                              $jkLibur = !empty($is_holiday);
                              // Waktu kerja REAL user (HH:MM) — fondasi mode dinamis.
                              // Format DB: 'Y-m-d H:i:s' (atau 'H:i:s' / 'H:i').
                              $jkRealHM = function ($v) {
                                  $v = trim((string) $v);
                                  if (strlen($v) >= 16) return substr($v, 11, 5);
                                  return substr($v, 0, 5);
                              };
                              $jkMasukReal = $jkSudahMasuk ? $jkRealHM($jkNormDt($attendance->jam_masuk)) : '';
                              $jkPulangReal = $jkSudahPulang ? $jkRealHM($jkNormDt($attendance->jam_pulang)) : '';
                              $jkState = $jkLibur ? 'libur' : (!$jkSudahMasuk ? 'belum' : (!$jkSudahPulang ? 'bekerja' : 'selesai'));
                              // Poin hari ini (dahulu card tersendiri) — kini menempel di badge.
                              $jkPoinText = '';
                              $jkPoinColor = '#15803d';
                              if ($jkSudahMasuk) {
                                  if ($isDinas) {
                                      $jkPoinText = '+10.0 pts';
                                      $jkPoinColor = '#0284c7';
                                  } else {
                                      $jkPoin = 0.0;
                                      if ($attendance->status_masuk === 'ONTIME') {
                                          $jkPoin += 10.0;
                                      } elseif ($attendance->status_masuk === 'LATE') {
                                          $jkPoin -= 5.0;
                                          if (!empty($attendance->telat_menit)) {
                                              $jkPoin -= ($attendance->telat_menit * 0.1);
                                          }
                                      }
                                      if (!empty($attendance->datang_awal_menit)) {
                                          $jkPoin += ($attendance->datang_awal_menit * 0.05);
                                      }
                                      if (!empty($attendance->pulang_akhir_menit)) {
                                          $jkPoin += ($attendance->pulang_akhir_menit * 0.05);
                                      }
                                      if (!empty($attendance->pulang_cepat_menit)) {
                                          $jkPoin -= ($attendance->pulang_cepat_menit * 0.1);
                                      }
                                      $jkPoinText = ($jkPoin > 0 ? '+' : '') . number_format($jkPoin, 1) . ' pts';
                                      $jkPoinColor = $jkPoin >= 0 ? '#15803d' : '#b91c1c';
                                  }
                              }
                              $jkPoinChip = $jkPoinText !== '' ? '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 border border-emerald-100" style="color:' . $jkPoinColor . ';" title="Poin hari ini"><i class="bi bi-award-fill text-[10px]"></i>' . $jkPoinText . '</span>' : '';
                              if ($jkLibur) {
                                  $jkBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500"><i class="bi bi-moon-stars text-[10px]"></i>Libur</span>';
                              } elseif (!$jkSudahMasuk) {
                                  $jkBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Belum Absen</span>';
                              } elseif (!$jkSudahPulang) {
                                  $jkBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"><span class="pulse-dot text-emerald-500" style="width:6px;height:6px;"></span>' . ($isDinas ? 'Bertugas' : 'Bekerja') . '</span>';
                              } else {
                                  $jkBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"><i class="bi bi-check-circle-fill text-[10px]"></i>Selesai</span>';
                              }
                              ?>

                              <div class="p-3.5 rounded-2xl border bg-white shadow-sm" id="jk-card" style="border-color: #eef2f7 !important;"
                                  data-masuk="<?= esc($jkMasuk) ?>" data-pulang="<?= esc($jkPulang) ?>"
                                  data-real-masuk="<?= esc($jkMasukReal) ?>" data-real-pulang="<?= esc($jkPulangReal) ?>"
                                  data-state="<?= esc($jkState) ?>">
                                  <div class="flex items-center gap-2.5">
                                      <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:#ffedd5;color:#f97316;">
                                          <i class="bi bi-briefcase-fill text-lg"></i>
                                      </div>
                                      <div class="min-w-0 grow">
                                          <div class="font-extrabold text-slate-800 truncate" style="font-size:13.5px;"><?= esc($namaHariPanjang) ?></div>
                                          <div class="text-xs text-slate-400 font-semibold truncate"><?= $jkMasuk ?> - <?= $jkPulang ?> WITA<?= $jkDurJam > 0 ? ' (' . $jkDurJam . ' jam)' : '' ?></div>
                                      </div>
                                       <div class="shrink-0 flex flex-wrap items-center justify-end gap-1.5"><?= $jkBadge ?><?= $jkPoinChip ?></div>
                                  </div>
                                  <?php if (!$jkLibur): ?>
                                  <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
                                      <div id="jk-progress" class="h-full rounded-full" role="progressbar" aria-label="Progres jam kerja" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%;background:linear-gradient(90deg,var(--brand-500) 0%,#f59e0b 55%,#22c55e 100%);transition:width .6s;"></div>
                                  </div>
                                  <div class="mt-1.5 flex items-center justify-between font-bold" style="font-size:10px;color:#94a3b8;">
                                      <span><?= $jkMasuk ?> WITA</span>
                                      <span id="jk-sisa" style="color:#16a34a;">…</span>
                                      <span><?= $jkPulang ?> WITA</span>
                                 </div>
                                  <script>
                                  (function () {
                                      // Progres jam kerja DINAMIS: jadwal kantor (A→B) x waktu real user.
                                      // state: belum (belum absen) · bekerja (sudah masuk) · selesai (sudah pulang)
                                      var card = document.getElementById('jk-card');
                                      var bar = document.getElementById('jk-progress');
                                      var lbl = document.getElementById('jk-sisa');
                                      if (!card || !bar || !lbl) return;
                                      function toMin(s) {
                                          if (!s) return null;
                                          var a = String(s).split(':');
                                          if (a.length < 2 || isNaN(+a[0]) || isNaN(+a[1])) return null;
                                          return (+a[0]) * 60 + (+a[1]);
                                      }
                                      function fmt(m) {
                                          m = Math.max(0, Math.round(m));
                                          var j = Math.floor(m / 60), s = m % 60;
                                          return j > 0 ? (s > 0 ? j + 'j ' + s + 'm' : j + ' jam') : m + 'm';
                                      }
                                      var A = toMin(card.dataset.masuk), B = toMin(card.dataset.pulang);
                                      var rM = toMin(card.dataset.realMasuk), rP = toMin(card.dataset.realPulang);
                                      var state = card.dataset.state || 'belum';
                                      if (A == null || B == null) { lbl.textContent = 'Jadwal belum disetting'; return; }
                                      // Normalisasi shift lintas tengah malam (mis. 22:00–06:00)
                                      var cross = B <= A;
                                      if (cross) B += 1440;
                                      if (rM != null && cross && rM < A) rM += 1440;
                                      if (rP != null && cross && rP < A) rP += 1440;
                                      var target = Math.max(1, B - A);
                                      var GRAD = 'linear-gradient(90deg,var(--brand-500) 0%,#f59e0b 55%,#22c55e 100%)';
                                      var DONE = 'linear-gradient(90deg,#34d399,#10b981)';
                                      var IDLE = 'linear-gradient(90deg,#cbd5e1,#94a3b8)';
                                      function paint(pct, bg, col, txt) {
                                          pct = Math.min(100, Math.max(0, pct));
                                          bar.style.width = pct + '%';
                                          if (bg) bar.style.background = bg;
                                          bar.setAttribute('aria-valuenow', Math.round(pct));
                                          lbl.style.color = col;
                                          lbl.textContent = txt;
                                      }
                                      function tick() {
                                          var now = witaNow();
                                          var cur = now.getHours() * 60 + now.getMinutes();
                                          if (cross && cur < A) cur += 1440; // ekor shift kemarin dini hari
                                          if (state === 'bekerja' && rM != null) {
                                              var worked = Math.max(0, cur - rM);
                                              if (cur >= B) {
                                                  paint(100, GRAD, '#16a34a', 'Sudah ' + fmt(worked) + ' • waktunya pulang');
                                              } else {
                                                  paint((cur - A) / target * 100, GRAD, '#16a34a',
                                                      'Sudah ' + fmt(worked) + ' • Sisa ' + fmt(B - cur));
                                              }
                                          } else if (state === 'bekerja') {
                                              // Fallback bila jam masuk real tak terbaca: ikut jadwal
                                              if (cur >= B) paint(100, GRAD, '#16a34a', '100% • waktunya pulang');
                                              else if (cur <= A) paint(0, GRAD, '#94a3b8', 'Shift segera mulai');
                                              else paint((cur - A) / target * 100, GRAD, '#16a34a',
                                                  Math.round((cur - A) / target * 100) + '% • Sisa ' + fmt(B - cur));
                                          } else if (state === 'selesai' && rM != null && rP != null) {
                                              // Bekukan di durasi real vs target — tidak ikut jam berjalan
                                              var tot = Math.max(0, rP - rM);
                                              paint(Math.min(100, tot / target * 100), DONE, '#059669',
                                                  'Total ' + fmt(tot) + ' dari target ' + fmt(target));
                                          } else if (state === 'selesai') {
                                              paint(100, DONE, '#059669', 'Selesai • 100% terpenuhi');
                                          } else { // belum absen
                                              if (cur <= A) {
                                                  var d = A - cur;
                                                  paint(0, GRAD, '#94a3b8', d <= 0 ? 'Mulai sekarang' : 'Mulai ' + fmt(d) + ' lagi');
                                              } else if (cur < B) {
                                                  paint((cur - A) / target * 100, GRAD, '#d97706',
                                                      Math.round((cur - A) / target * 100) + '% • shift berjalan, segera absen');
                                              } else {
                                                  paint(100, IDLE, '#94a3b8', 'Shift berakhir • belum absen');
                                              }
                                          }
                                      }
                                      tick();
                                      setInterval(tick, 30000);
                                  })();
                                  </script>
                                  <?php else: ?>
                                  <div class="mt-2 text-[11px] font-semibold text-slate-400 truncate"><?= esc($holiday_reason ?? 'Hari libur') ?></div>
                                  <?php endif; ?>
                              </div>
                          </div>
                    </div>

                    <!-- Hero Card Khusus jika Izin / Sakit / Cuti (Atau Dinas tanpa scan masuk) -->
                    <?php if ($isSpecialLeave && (!$attendance || !$attendance->jam_masuk)): ?>
                    <div class="attendance-card mb-3" style="--accent: <?= $specialColor ?>;">
                        <div class="attendance-card-head">
                            <div class="attendance-card-icon" style="background: <?= $specialBg ?>; color: <?= $specialColor ?>;">
                                <i class="bi <?= $specialIcon ?>"></i>
                            </div>
                            <div class="grow">
                                <div class="attendance-card-title"><?= esc($specialTitle) ?></div>
                                <div class="attendance-card-subtitle"><?= buat_tanggal(date('l, d F Y')) ?></div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold" style="background: <?= $specialBg ?>; color: <?= $specialColor ?>; border: 1px solid <?= $specialBorder ?>;">
                                    <i class="bi bi-check-circle-fill text-[11px]"></i>
                                    <span><?= $specialBadge ?></span>
                                </span>
                            </div>
                        </div>

                        <div class="p-3.5 bg-slate-50/70 border border-slate-100 rounded-xl space-y-2.5 mt-2">
                            <!-- Keterangan / Alasan -->
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                    Keterangan / Alasan
                                </div>
                                <div class="text-xs font-semibold text-slate-700 bg-white p-2.5 rounded-lg border border-slate-200/80 leading-relaxed italic">
                                    "<?= esc($specialReason ?: 'Tercatat dispensasi resmi dari sistem.') ?>"
                                </div>
                            </div>

                            <!-- Rentang Tanggal jika dari todayPermit -->
                            <?php if ($todayPermit && !empty($todayPermit->tgl_mulai) && !empty($todayPermit->tgl_selesai)): ?>
                            <div class="flex items-center justify-between text-xs text-slate-600 pt-0.5">
                                <span class="text-[11px] font-semibold text-slate-400 flex items-center gap-1">
                                    <i class="bi bi-calendar-range text-slate-400"></i>
                                    Periode:
                                </span>
                                <span class="font-bold text-slate-700">
                                    <?= date('d M Y', strtotime($todayPermit->tgl_mulai)) ?> – <?= date('d M Y', strtotime($todayPermit->tgl_selesai)) ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <!-- Status Chips -->
                            <div class="flex flex-wrap items-center gap-1.5 pt-1 border-t border-slate-200/60">
                                <span class="chip chip-success">
                                    <i class="bi bi-shield-check me-1"></i>Bebas Denda
                                </span>
                                <span class="chip chip-solid">
                                    <i class="bi bi-check2-all me-1"></i>Tercatat Resmi
                                </span>
                                <?php if ($isDinas): ?>
                                <span class="chip chip-primary">
                                    <i class="bi bi-award-fill me-1"></i>Poin Hadir Penuh
                                </span>
                                <?php endif; ?>
                                <?php if ($todayPermit && !empty($todayPermit->bukti)): ?>
                                    <?php
                                    $buktiPath = $todayPermit->bukti;
                                    $buktiUrl = (strpos($buktiPath, 'uploads/') !== false) ? base_url($buktiPath) : base_url('uploads/bukti/' . $buktiPath);
                                    ?>
                                    <a href="<?= $buktiUrl ?>" target="_blank" class="chip chip-warning no-underline font-bold text-amber-800 hover:bg-amber-100 transition-colors">
                                        <i class="bi bi-paperclip me-1"></i>Lihat Dokumen
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($isPendingPermit && (!$attendance || !$attendance->jam_masuk)): ?>
                    <div class="bg-amber-50 border border-amber-200/80 rounded-2xl p-4 text-center space-y-2 mb-3 shadow-2xs">
                        <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center mx-auto text-lg shadow-xs">
                            <i class="bi bi-hourglass-split animate-spin-slow"></i>
                        </div>
                        <h6 class="text-xs font-extrabold text-amber-950 m-0">Pengajuan <?= ucfirst($permitJenis) ?> Menunggu Verifikasi</h6>
                        <p class="text-[11px] text-amber-800/90 max-w-xs mx-auto m-0 leading-relaxed font-medium">
                            Pengajuan Anda untuk hari ini masih dalam proses peninjauan oleh kepala sekolah / admin.
                        </p>
                        <?php if ($todayPermit && !empty($todayPermit->keterangan)): ?>
                        <div class="text-xs italic bg-white/80 p-2 rounded-lg border border-amber-200 text-amber-900 max-w-sm mx-auto">
                            "<?= esc($todayPermit->keterangan) ?>"
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Entry Section -->
                    <!-- Entry Section -->
                    <?php if ($attendance && $attendance->jam_masuk): ?>
                    <?php
                    $devIn   = strtolower((string) ($attendance->device_info ?? ''));
                    $ketIn   = strtolower((string) ($attendance->keterangan ?? ''));
                    $fotoIn  = $attendance->foto_masuk ?? null;
                    if ($fotoIn && strpos($fotoIn, 'uploads/') === false) $fotoIn = 'uploads/absen/' . $fotoIn;
                    $hasFotoIn = !empty($fotoIn) && file_exists(FCPATH . $fotoIn);

                    $isEntryDinas = ($isDinas || strpos($attendance->status_masuk ?? '', 'DINAS') !== false);
                    $isManualIn   = (strpos($devIn, 'manual') !== false || strpos($ketIn, 'manual') !== false || strpos($ketIn, 'admin') !== false || (!empty($attendance->approved_by) && !$hasFotoIn));
                    $isQrIn       = !$isManualIn && (strpos($devIn, 'kiosk') !== false || strpos($devIn, 'qr') !== false || strpos($ketIn, 'kiosk') !== false || strpos($ketIn, 'qr') !== false);
                    $isWfhIn      = !$isManualIn && !$isQrIn && (($attendance->is_wfh ?? 0) == 1 || ($attendance->status_masuk ?? '') === 'WFH');

                    $entryAccent = $isEntryDinas ? '#0284c7' : '#10b981';
                    $entryTitle  = $isEntryDinas ? 'Absen Masuk (Dinas Luar)' : 'Absen Masuk';
                    $entryIcon   = $isEntryDinas ? 'bi-briefcase-fill' : 'bi-box-arrow-in-right';
                    ?>
                    <div class="attendance-card mb-3" style="--accent: <?= $entryAccent ?>;">
                        <div class="attendance-card-head">
                            <div class="attendance-card-icon" style="<?= $isEntryDinas ? 'background: #f0f9ff; color: #0284c7;' : '' ?>">
                                <i class="bi <?= $entryIcon ?>"></i>
                            </div>
                            <div class="grow">
                                <div class="attendance-card-title"><?= $entryTitle ?></div>
                                <div class="attendance-card-subtitle"><?= buat_tanggal(date('l, d F Y', strtotime($attendance->tgl))) ?></div>
                            </div>
                            <div class="attendance-time">
                                <?php // Jam korup ('0000-00-00 ...' / epoch 1970) tidak ditampilkan sebagai jam sah. ?>
<?php $tsMasukAman = (!empty($attendance->jam_masuk) && !str_starts_with((string) $attendance->jam_masuk, '0000-00-00')) ? strtotime((string) $attendance->jam_masuk) : 0; ?>
<div class="attendance-time-main" style="<?= $isEntryDinas ? 'color: #0284c7;' : '' ?>"><?= ($tsMasukAman > 0 && date('Y', $tsMasukAman) !== '1970') ? date('H:i', $tsMasukAman) : '--:--' ?></div>
                                <div class="attendance-time-sub">WITA</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-3">
                            <div>
                                <div class="attendance-photo">
                                    <?php if ($hasFotoIn): ?>
                                        <img src="<?= base_url($fotoIn) ?>?v=<?= filemtime(FCPATH . $fotoIn) ?>" onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
                                    <?php elseif ($isManualIn): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-amber-50/70 text-amber-500 rounded-2xl">
                                            <i class="bi bi-person-gear text-2xl"></i>
                                        </div>
                                    <?php elseif ($isQrIn): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-indigo-50/70 text-indigo-500 rounded-2xl">
                                            <i class="bi bi-qr-code-scan text-2xl"></i>
                                        </div>
                                    <?php elseif ($isWfhIn): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-sky-50/70 text-sky-500 rounded-2xl">
                                            <i class="bi bi-house-door-fill text-2xl"></i>
                                        </div>
                                    <?php elseif ($isEntryDinas): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-sky-50/70 text-sky-500 rounded-2xl">
                                            <i class="bi bi-briefcase-fill text-2xl"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-slate-50 text-slate-300 rounded-2xl">
                                            <i class="bi bi-person text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-span-3">
                                <div class="mb-2">
                                    <?php if ($isManualIn): ?>
                                        <div class="attendance-label">Metode Presensi</div>
                                        <div class="attendance-location text-slate-800">
                                            <i class="bi bi-shield-check text-amber-500 me-1"></i>Input Manual Admin
                                        </div>
                                        <?php if (!empty($attendance->keterangan) && strtolower(trim($attendance->keterangan)) !== 'absen manual oleh admin'): ?>
                                            <div class="attendance-meta text-slate-500">
                                                <i class="bi bi-chat-left-text text-[10px]"></i>
                                                <span class="truncate italic"><?= esc($attendance->keterangan) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($isQrIn): ?>
                                        <div class="attendance-label">Metode Presensi</div>
                                        <div class="attendance-location text-slate-800">
                                            <i class="bi bi-qr-code text-indigo-500 me-1"></i>Scan QR Kiosk
                                        </div>
                                        <div class="attendance-meta text-slate-500">
                                            <i class="bi bi-display text-[10px]"></i>
                                            <span><?= esc($attendance->nama_lokasi_masuk ?? 'Kiosk Scanner') ?></span>
                                        </div>
                                    <?php elseif ($isWfhIn): ?>
                                        <div class="attendance-label">Metode Presensi</div>
                                        <div class="attendance-location text-slate-800">
                                            <i class="bi bi-house-door text-sky-500 me-1"></i>Work From Home
                                        </div>
                                    <?php elseif ($isEntryDinas): ?>
                                        <div class="attendance-label">Titik Lokasi Tugas Dinas</div>
                                        <div class="attendance-location"><i class="bi bi-geo-alt-fill text-sky-600 me-1"></i><?= esc($attendance->nama_lokasi_masuk ?? 'Lokasi Luar Sekolah') ?></div>
                                    <?php else: ?>
                                        <div class="attendance-label">Titik Lokasi</div>
                                        <div class="attendance-location"><i class="bi bi-geo-alt-fill text-red-600 me-1"></i><?= esc($attendance->nama_lokasi_masuk ?? '-') ?></div>
                                        <div class="attendance-meta">
                                            <i class="bi bi-radar"></i>
                                            <span>Akurasi <?= isset($attendance->jarak_masuk) ? round($attendance->jarak_masuk) . 'm' : '-' ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap gap-1 pt-1">
                                    <?php if ($isEntryDinas): ?>
                                        <span class="chip chip-primary" style="background:#e0f2fe; color:#0369a1;"><i class="bi bi-briefcase-fill me-1"></i>Tugas Luar</span>
                                        <?php if ($attendance->status_masuk === 'DINAS_PENDING'): ?>
                                            <span class="chip chip-warning"><i class="bi bi-hourglass-split me-1"></i>Menunggu Approval</span>
                                        <?php else: ?>
                                            <span class="chip chip-success"><i class="bi bi-check-circle-fill me-1"></i>Disetujui</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (($attendance->status_masuk ?? '') === 'LATE'): ?>
                                            <span class="chip chip-danger"><i class="bi bi-exclamation-triangle-fill"></i>Telat <?= $attendance->telat_menit ?? 0 ?>m</span>
                                        <?php elseif (isset($attendance->datang_awal_menit) && $attendance->datang_awal_menit > 0): ?>
                                            <span class="chip chip-success"><i class="bi bi-arrow-up-right"></i>Hadir Awal <?= $attendance->datang_awal_menit ?>m</span>
                                        <?php else: ?>
                                            <span class="chip chip-solid"><i class="bi bi-check-circle-fill"></i>Tepat Waktu</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Exit Section -->
                    <?php if ($attendance && $attendance->jam_pulang): ?>
                    <?php
                    // $devIn/$ketIn didefinisikan di blok Entry Section, yang
                    // hanya jalan bila jam_masuk terisi. Baris approve dinas
                    // bisa punya jam_pulang tanpa jam_masuk, jadi di sini
                    // dihitung ulang dengan default kosong agar tidak
                    // triggering "Undefined variable".
                    $devOut  = isset($devIn) ? $devIn : strtolower((string) ($attendance->device_info ?? ''));
                    $ketOut  = isset($ketIn) ? $ketIn : strtolower((string) ($attendance->keterangan ?? ''));

                    $fotoOut  = $attendance->foto_pulang ?? null;
                    if ($fotoOut && strpos($fotoOut, 'uploads/') === false) $fotoOut = 'uploads/absen/' . $fotoOut;
                    $hasFotoOut = !empty($fotoOut) && file_exists(FCPATH . $fotoOut);

                    $isExitDinas = ($isDinas || strpos($attendance->status_pulang ?? '', 'DINAS') !== false);
                    $isManualOut = (strpos($devOut, 'manual') !== false || strpos($ketOut, 'manual') !== false || strpos($ketOut, 'admin') !== false || (!empty($attendance->approved_by) && !$hasFotoOut));
                    $isQrOut     = !$isManualOut && (strpos($devOut, 'kiosk') !== false || strpos($devOut, 'qr') !== false || strpos($ketOut, 'kiosk') !== false || strpos($ketOut, 'qr') !== false);

                    $exitAccent = '#ef4444';
                    $exitTitle  = $isExitDinas ? 'Absen Pulang (Dinas Luar)' : 'Absen Pulang';
                    $exitIcon   = $isExitDinas ? 'bi-briefcase-fill' : 'bi-box-arrow-right';
                    ?>
                    <div class="attendance-card mb-3" style="--accent: <?= $exitAccent ?>;">
                        <div class="attendance-card-head">
                            <div class="attendance-card-icon">
                                <i class="bi <?= $exitIcon ?>"></i>
                            </div>
                            <div class="grow">
                                <div class="attendance-card-title"><?= $exitTitle ?></div>
                                <div class="attendance-card-subtitle"><?= buat_tanggal(date('l, d F Y', strtotime($attendance->tgl))) ?></div>
                            </div>
                            <div class="attendance-time">
                                <?php $tsPulangAman = (!empty($attendance->jam_pulang) && !str_starts_with((string) $attendance->jam_pulang, '0000-00-00')) ? strtotime((string) $attendance->jam_pulang) : 0; ?>
<div class="attendance-time-main"><?= ($tsPulangAman > 0 && date('Y', $tsPulangAman) !== '1970') ? date('H:i', $tsPulangAman) : '--:--' ?></div>
                                <div class="attendance-time-sub">WITA</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-3">
                            <div>
                                <div class="attendance-photo">
                                    <?php if ($hasFotoOut): ?>
                                        <img src="<?= base_url($fotoOut) ?>?v=<?= filemtime(FCPATH . $fotoOut) ?>" onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
                                    <?php elseif ($isManualOut): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-amber-50/70 text-amber-500 rounded-2xl">
                                            <i class="bi bi-person-gear text-2xl"></i>
                                        </div>
                                    <?php elseif ($isQrOut): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-indigo-50/70 text-indigo-500 rounded-2xl">
                                            <i class="bi bi-qr-code-scan text-2xl"></i>
                                        </div>
                                    <?php elseif ($isExitDinas): ?>
                                        <div class="w-full h-full flex items-center justify-center bg-sky-50/70 text-sky-500 rounded-2xl">
                                            <i class="bi bi-briefcase-fill text-2xl"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-slate-50 text-slate-300 rounded-2xl">
                                            <i class="bi bi-person text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-span-3">
                                <div class="mb-2">
                                    <?php if ($isManualOut): ?>
                                        <div class="attendance-label">Metode Presensi</div>
                                        <div class="attendance-location text-slate-800">
                                            <i class="bi bi-shield-check text-amber-500 me-1"></i>Input Manual Admin
                                        </div>
                                    <?php elseif ($isQrOut): ?>
                                        <div class="attendance-label">Metode Presensi</div>
                                        <div class="attendance-location text-slate-800">
                                            <i class="bi bi-qr-code text-indigo-500 me-1"></i>Scan QR Kiosk
                                        </div>
                                        <div class="attendance-meta text-slate-500">
                                            <i class="bi bi-display text-[10px]"></i>
                                            <span><?= esc($attendance->nama_lokasi_pulang ?? 'Kiosk Scanner') ?></span>
                                        </div>
                                    <?php elseif ($isExitDinas): ?>
                                        <div class="attendance-label">Lokasi Kepulangan Dinas</div>
                                        <div class="attendance-location"><i class="bi bi-geo-alt-fill text-sky-600 me-1"></i><?= esc($attendance->nama_lokasi_pulang ?? 'Lokasi Luar Sekolah') ?></div>
                                    <?php else: ?>
                                        <div class="attendance-label">Lokasi Kepulangan</div>
                                        <div class="attendance-location"><i class="bi bi-geo-alt-fill text-red-600 me-1"></i><?= esc($attendance->nama_lokasi_pulang ?? '-') ?></div>
                                        <div class="attendance-meta">
                                            <i class="bi bi-radar"></i>
                                            <span>Jarak <?= isset($attendance->jarak_pulang) ? round($attendance->jarak_pulang) . 'm' : '-' ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap gap-1 pt-1">
                                    <?php if (isset($attendance->pulang_akhir_menit) && $attendance->pulang_akhir_menit > 0): ?>
                                        <?php
                                        $lMenit = (int) $attendance->pulang_akhir_menit;
                                        $lJam = floor($lMenit / 60);
                                        $lSisa = $lMenit % 60;
                                        $lText = $lJam > 0 ? ($lSisa > 0 ? "{$lJam}j {$lSisa}m" : "{$lJam} Jam") : "{$lMenit}m";
                                        ?>
                                        <span class="chip chip-primary"><i class="bi bi-clock-history"></i>Lembur <?= $lText ?></span>
                                    <?php endif; ?>
                                    <?php if (($attendance->status_pulang ?? '') === 'EARLY'): ?>
                                        <?php
                                        $jadwalPulang = $attendance->jadwal_pulang ?? ($setting->jam_pulang ?? ($setting->jadwal_pulang ?? '15:00:00'));
                                        $pulangAwalMenit = 0;
                                        $tglPulang = $attendance->tgl ?? date('Y-m-d');
                                        $tsAktual = !empty($attendance->jam_pulang) ? strtotime($tglPulang . ' ' . $attendance->jam_pulang) : false;
                                        $tsJadwal = !empty($jadwalPulang) ? strtotime($tglPulang . ' ' . $jadwalPulang) : false;
                                        if ($tsAktual !== false && $tsJadwal !== false) {
                                             $pulangAwalMenit = (int) (($tsJadwal - $tsAktual) / 60);
                                        }
                                        ?>
                                        <span class="chip chip-warning"><i class="bi bi-exclamation-circle-fill"></i>Pulang Awal <?= $pulangAwalMenit ?>m</span>
                                    <?php elseif (($attendance->status_pulang ?? '') === 'ONTIME' || $isExitDinas): ?>
                                        <span class="chip chip-success"><i class="bi bi-check-circle-fill"></i>Tepat Waktu</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h6>Belum ada aktivitas</h6>
                        <p class="text-sm text-slate-400">Selamat berjuang untuk hari ini!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Absen via QR Section (event QR hari ini) -->
            <div id="qr-event-card" class="m3-card m3-card-elevated mt-3 mx-0 hidden">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="font-bold mb-0 text-slate-800" style="font-size: 14px; letter-spacing: -0.2px;">
                        <i class="bi bi-qr-code-scan me-2 text-brand-600"></i>Absen via QR
                    </h6>
                    <span class="badge-tw badge-primary-subtle text-brand-600 rounded-full px-2 py-1" style="font-size: 10px; font-weight: 600;">Hari ini</span>
                </div>
                <p class="text-slate-400 mb-2" style="font-size: 11px;">Ketuk acara untuk absen — tercatat sebagai absen masuk harian bila dalam jam &amp; area.</p>
                <div id="qr-event-list" class="space-y-2"></div>
            </div>

            <!-- Aktivitas Rekan Section -->
            <?php if (!empty($others)): ?>
                <div class="m3-card m3-card-elevated mt-3 mx-0">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="font-bold mb-0 text-slate-800" style="font-size: 14px; letter-spacing: -0.2px;">
                            <i class="bi bi-people-fill me-2 text-brand-600"></i>Aktivitas Rekan
                        </h6>
                        <span class="badge-tw badge-primary-subtle text-brand-600 rounded-full px-2 py-1" style="font-size: 10px; font-weight: 600;">Terbaru</span>
                    </div>
                    
                    <div>
                        <?php foreach ($others as $idx => $o): 
                             $oName = !empty($o->nama_guru) ? trim($o->nama_guru) : trim(($o->first_name ?? '') . ' ' . ($o->last_name ?? ''));
                             if ($oName === '') $oName = $o->username ?? 'Guru';
                             
                             $statusMasuk = strtoupper($o->status_masuk ?? '');
                             $statusPulang = strtoupper($o->status_pulang ?? '');
                             $keterangan  = $o->keterangan ?? '';   // alasan dinas
                             $izinAlasan  = $o->izin_alasan ?? '';  // alasan izin/sakit/cuti
                             $izinJenis   = strtoupper($o->izin_jenis ?? '');

                             // Determine effective status label & colour
                             $isDinas  = (strpos($statusMasuk, 'DINAS') !== false);
                             $isWfh    = ($statusMasuk === 'WFH' || ($o->is_wfh ?? 0) == 1);
                             $isIjin   = ($statusMasuk === 'IJIN'  || $izinJenis === 'IJIN');
                             $isSakit  = ($statusMasuk === 'SAKIT' || $izinJenis === 'SAKIT');
                             $isCuti   = ($statusMasuk === 'CUTI'  || $izinJenis === 'CUTI');
                             $isLate   = ($statusMasuk === 'LATE');
                             $isAlpha  = ($statusMasuk === 'ALPHA');
                             $isPulang = (!empty($o->jam_pulang));
                             $rawTime  = $isPulang ? $o->jam_pulang : $o->jam_masuk;
                             $isLeave  = ($isIjin || $isSakit || $isCuti);
                             // Jam korup ('0000-00-00 ...) atau epoch jam 1970
                             // tidak boleh tampil sebagai jam absen sah.
                             $tsAkt = ($rawTime && !str_starts_with((string) $rawTime, '0000-00-00'))
                                 ? strtotime((string) $rawTime) : 0;
                             if ($tsAkt > 0 && date('Y', $tsAkt) !== '1970') {
                                 $actionTime = date('H:i', $tsAkt) . ' WITA';
                             } else {
                                 $actionTime = $isLeave ? 'Disetujui' : ($isAlpha ? '—' : '--:-- WITA');
                             }

                             // Resolve reason text
                             $reasonText = '';
                             if ($isAlpha) {
                                 $reasonText = $keterangan ?: '';
                             } elseif ($isDinas) {
                                 $reasonText = $keterangan ?: '';
                             } elseif ($isLeave) {
                                 $reasonText = $o->izin_keterangan ?? $o->izin_alasan ?? $keterangan ?? '';
                             } elseif ($isWfh) {
                                 $reasonText = $keterangan ?: '';
                             }

                             // Lateness in minutes
                             $telatMnt = $o->telat_menit ?? 0;

                             // Ikon status (lebih hidup dari foto/inisial) — warna mengikuti jenis aktivitas.
                             if ($isDinas)            { $oIcon = ['bi-briefcase-fill', '#fef3c7', '#92400e']; }
                             elseif ($isWfh)          { $oIcon = ['bi-house-fill', '#ede9fe', '#5b21b6']; }
                             elseif ($isIjin)         { $oIcon = ['bi-file-earmark-text-fill', '#fce7f3', '#9d174d']; }
                             elseif ($isSakit)        { $oIcon = ['bi-thermometer-half', '#fee2e2', '#991b1b']; }
                             elseif ($isCuti)         { $oIcon = ['bi-calendar-check-fill', '#e0f2fe', '#075985']; }
                             elseif ($isAlpha)        { $oIcon = ['bi-x-circle-fill', '#fee2e2', '#991b1b']; }
                             elseif ($isLate)         { $oIcon = ['bi-alarm-fill', '#fef3c7', '#b45309']; }
                             elseif ($isPulang)       { $oIcon = ['bi-box-arrow-right', '#e0f2fe', '#0369a1']; }
                             else                     { $oIcon = ['bi-check-lg', '#dcfce7', '#15803d']; }
                        ?>
                        <div class="flex items-start py-2">
                            <div class="relative me-3 flex-shrink-0">
                                <div class="flex items-center justify-center shadow-sm" style="width:42px;height:42px;border-radius:14px;background:<?= $oIcon[1] ?>;color:<?= $oIcon[2] ?>;font-size:19px;"><i class="bi <?= $oIcon[0] ?>"></i></div>
                            </div>
                            <div class="grow min-w-0">
                                <div class="flex justify-between items-center mb-1">
                                    <div class="font-bold text-slate-800 truncate pe-2" style="font-size: 13.5px;"><?= esc($oName) ?></div>
                                    <div class="text-slate-400 whitespace-nowrap" style="font-size: 11px; font-weight: 600;"><?= $actionTime ?></div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1">
                                    <?php if ($isDinas): ?>
                                        <span class="badge-tw rounded-full px-2 py-0" style="font-size: 10px; font-weight: 700; background: #fef3c7; color: #92400e;">
                                            <i class="bi bi-briefcase-fill me-1"></i>DINAS
                                        </span>
                                        <?php if ($statusMasuk === 'DINAS_PENDING'): ?>
                                            <span class="badge-tw rounded-full px-2 py-0" style="font-size: 9px; font-weight: 600; background: #fff3cd; color: #856404;">Menunggu Persetujuan</span>
                                        <?php elseif ($statusMasuk === 'DINAS_APPROVED'): ?>
                                            <span class="badge-tw rounded-full px-2 py-0" style="font-size: 9px; font-weight: 600; background: #d1fae5; color: #065f46;">Disetujui</span>
                                        <?php endif; ?>
                                    <?php elseif ($isWfh): ?>
                                        <span class="badge-tw rounded-full px-2 py-0" style="font-size: 10px; font-weight: 700; background: var(--accent-100); color: var(--accent-800);">
                                            <i class="bi bi-house-fill me-1"></i>WFH
                                        </span>
                                        <span class="text-slate-400" style="font-size: 10px;">Dari Rumah</span>
                                    <?php elseif ($isIjin): ?>
                                        <span class="badge-tw rounded-full px-2 py-0" style="font-size: 10px; font-weight: 700; background: #fce7f3; color: #9d174d;">
                                            <i class="bi bi-file-earmark-text-fill me-1"></i>IZIN
                                        </span>
                                    <?php elseif ($isSakit): ?>
                                        <span class="badge-tw rounded-full px-2 py-0" style="font-size: 10px; font-weight: 700; background: #fee2e2; color: #991b1b;">
                                            <i class="bi bi-thermometer-half me-1"></i>SAKIT
                                        </span>
                                    <?php elseif ($isCuti): ?>
                                        <span class="badge-tw rounded-full px-2 py-0" style="font-size: 10px; font-weight: 700; background: #e0f2fe; color: #075985;">
                                            <i class="bi bi-calendar-check-fill me-1"></i>CUTI
                                        </span>
                                    <?php elseif ($isAlpha): ?>
                                        <span class="badge-tw rounded-full px-2 py-0" style="font-size: 10px; font-weight: 700; background: #fee2e2; color: #991b1b;">
                                            <i class="bi bi-x-circle-fill me-1"></i>Alpha
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-tw <?= $isPulang ? 'bg-sky-50 text-sky-600' : 'bg-emerald-50 text-emerald-600' ?> rounded-full px-2 py-0" style="font-size: 10px; font-weight: 600;">
                                            Absen <?= $isPulang ? 'Pulang' : 'Masuk' ?>
                                        </span>
                                        <?php 
                                        $oDev = strtolower((string)($o->device_info ?? ''));
                                        $oKet = strtolower((string)($o->keterangan ?? ''));
                                        if (strpos($oDev, 'manual') !== false || strpos($oKet, 'manual') !== false || strpos($oKet, 'admin') !== false): ?>
                                            <span class="badge-tw rounded-full px-2 py-0" style="font-size: 9px; font-weight: 700; background: #fef3c7; color: #92400e;">
                                                <i class="bi bi-person-gear me-0.5"></i>Manual
                                            </span>
                                        <?php elseif (strpos($oDev, 'kiosk') !== false || strpos($oDev, 'qr') !== false || strpos($oKet, 'kiosk') !== false || strpos($oKet, 'qr') !== false): ?>
                                            <span class="badge-tw rounded-full px-2 py-0" style="font-size: 9px; font-weight: 700; background: var(--accent-100); color: var(--accent-800);">
                                                <i class="bi bi-qr-code me-0.5"></i>QR
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-slate-400" style="font-size: 10px;">•</span>
                                        <span style="font-size: 11px;">
                                            <?php if ($isLate): ?>
                                                <span class="text-amber-500 font-semibold">Terlambat<?= $telatMnt > 0 ? " {$telatMnt}m" : '' ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-400">Tepat Waktu</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($reasonText !== ''): ?>
                                    <div class="text-slate-400 mt-1" style="font-size: 10.5px; font-style: italic; line-height: 1.3; word-break: break-word;">
                                        <i class="bi bi-chat-left-quote me-1" style="font-size: 9px;"></i><?= esc($reasonText) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($idx < count($others) - 1 && $idx < 4): ?>
                            <hr class="my-1" style="border-top: 1px solid #f1f5f9; opacity: 1;">
                        <?php endif; ?>
                        <?php if ($idx >= 4) break; // Limit to 5 for clean UI ?>
                        <?php endforeach; ?>

                    </div>
                </div>
            <?php endif; ?>

                <!-- Rekan & Ranking in Card style -->
                <?php if (!empty($disciplineRanking)): ?>
                    <div class="m3-card m3-card-elevated mt-4 mx-0">
                        <div class="flex items-center justify-between mb-3">
                            <h6 class="font-bold mb-0 text-slate-800" style="font-size: 14px; letter-spacing: -0.2px;">
                                <i class="bi bi-award-fill me-2 text-brand-600"></i>Leaderboard Kedisiplinan
                            </h6>
                            <span class="badge-tw badge-secondary text-slate-400 rounded-full px-2 py-1" style="font-size: 10px; font-weight: 600;"><?= date('M Y') ?></span>
                        </div>
                        
                        <div>
                            <?php foreach ($disciplineRanking as $idx => $r):
                                 $rName = \App\Services\AvatarService::namaUtama($r);

                                 $rank = $idx + 1;
                                 
                                 // Medals and Rank styling
                                 $rankBadgeBg = '#f1f5f9';
                                 $rankBadgeColor = '#64748b';
                                 $rankBorder = 'border: 1px solid #f1f5f9;';
                                  if ($rank === 1) {
                                      $rankBadgeBg = 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)';
                                      $rankBadgeColor = '#b45309';
                                      $rankBorder = 'border: 1px solid #fde68a; background: linear-gradient(90deg, rgba(254, 243, 199, 0.25) 0%, #ffffff 100%);';
                                  } else if ($rank === 2) {
                                      $rankBadgeBg = 'linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%)';
                                      $rankBadgeColor = '#475569';
                                      $rankBorder = 'border: 1px solid #e2e8f0; background: linear-gradient(90deg, rgba(241, 245, 249, 0.35) 0%, #ffffff 100%);';
                                  } else if ($rank === 3) {
                                      $rankBadgeBg = 'linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%)';
                                      $rankBadgeColor = '#c2410c';
                                      $rankBorder = 'border: 1px solid #fed7aa; background: linear-gradient(90deg, rgba(255, 237, 213, 0.35) 0%, #ffffff 100%);';
                                  }
                            ?>
                            <div class="flex items-center py-2 px-2 rounded-xl mb-2" style="<?= $rankBorder ?>">
                                <div class="relative me-3 shrink-0">
                                    <img src="<?= esc($r->avatar, 'attr') ?>" alt="<?= esc($rName, 'attr') ?>" class="foto-orang shadow-sm" onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
                                    <div class="absolute bottom-0 end-0 rounded-full shadow-sm flex items-center justify-center" style="width: 20px; height: 20px; margin-bottom: -4px; margin-right: -4px; background: <?= $rankBadgeBg ?>; color: <?= $rankBadgeColor ?>; font-weight: 800; font-size: 10px; border: 1.5px solid #fff;">
                                        <?= $rank ?>
                                    </div>
                                </div>
                                <div class="grow min-w-0">
                                    <div class="flex justify-between items-start">
                                        <div class="min-w-0 pe-2">
                                            <div class="font-bold text-slate-800 truncate" style="font-size: 13.5px;"><?= esc($rName) ?></div>
                                            <div class="flex items-center text-slate-400 mt-1" style="font-size: 11px;">
                                                <i class="bi bi-patch-check-fill text-emerald-600 me-1"></i>
                                                <span><?= $r->ontime ?> Hari Tepat Waktu</span>
                                            </div>
                                        </div>
                                        <div class="text-right whitespace-nowrap shrink-0 ps-1">
                                            <div class="font-bold text-brand-600" style="font-size: 15px; letter-spacing: -0.3px;"><?= number_format($r->score, 1) ?></div>
                                            <div class="text-slate-400" style="font-size: 10px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase;">Skor</div>
                                            <?php if (($r->total_denda ?? 0) > 0): ?>
                                                <button type="button" class="score-denda" onclick="window.showDendaDetail(<?= (int)($r->user_id ?? 0) ?>, '<?= date('Y-m') ?>')">Rp<?= number_format($r->total_denda, 0, ',', '.') ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($idx < count($disciplineRanking) - 1): ?>
                                <hr class="my-1" style="border-top: 1px dashed #e2e8f0; opacity: 1;">
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-3 pt-2 border-t text-center" style="border-color: #f1f5f9 !important;">
                            <button class="btn-tw btn-tw-sm btn-light no-underline" onclick="showTab('ranking')">
                                <span>Lihat Selengkapnya</span>
                                <i class="bi bi-chevron-right" style="font-size: 11px;"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Guru Perlu Perhatian / Perlu Pendampingan -->
                <?php if (!empty($lazyRanking)): ?>
                    <div class="m3-card m3-card-elevated mt-3 mx-0" style="border-color: #fee2e2; box-shadow: 0 4px 16px rgba(239, 68, 68, 0.05);">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h6 class="font-bold mb-0 text-red-600" style="font-size: 14px; letter-spacing: -0.2px;">
                                    <i class="bi bi-exclamation-octagon-fill me-2 text-red-600"></i>Perlu Perhatian!
                                </h6>
                                <div class="text-slate-400" style="font-size: 10.5px; margin-top: 2px;">Mari saling memotivasi untuk meningkatkan kedisiplinan</div>
                            </div>
                        </div>
                        
                        <div>
                            <?php foreach ($lazyRanking as $idx => $r):
                                 $rName = \App\Services\AvatarService::namaUtama($r);

                                 $rank = $idx + 1;
                                 
                                  // Rank styling with warning/danger gradients
                                  if ($rank === 1) {
                                      $rankBadgeBg = 'linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%)';
                                      $rankBadgeColor = '#991b1b';
                                      $rankBorder = 'background: linear-gradient(90deg, rgba(254, 202, 202, 0.25) 0%, #ffffff 100%); border: 1px solid #fee2e2;';
                                  } else if ($rank === 2) {
                                      $rankBadgeBg = 'linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%)';
                                      $rankBadgeColor = '#c2410c';
                                      $rankBorder = 'background: linear-gradient(90deg, rgba(255, 237, 213, 0.25) 0%, #ffffff 100%); border: 1px solid #ffedd5;';
                                  } else {
                                      $rankBadgeBg = 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)';
                                      $rankBadgeColor = '#b45309';
                                      $rankBorder = 'background: linear-gradient(90deg, rgba(254, 243, 199, 0.25) 0%, #ffffff 100%); border: 1px solid #fef3c7;';
                                  }
                            ?>
                            <div class="flex items-center py-2 px-2 rounded-xl mb-2" style="<?= $rankBorder ?>">
                                <div class="relative me-3 shrink-0">
                                    <img src="<?= esc($r->avatar, 'attr') ?>" alt="<?= esc($rName, 'attr') ?>" class="foto-orang shadow-sm" onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
                                    <div class="absolute bottom-0 end-0 rounded-full shadow-sm flex items-center justify-center" style="width: 20px; height: 20px; margin-bottom: -4px; margin-right: -4px; background: <?= $rankBadgeBg ?>; color: <?= $rankBadgeColor ?>; font-weight: 800; font-size: 10px; border: 1.5px solid #fff;">
                                        <?= $rank ?>
                                    </div>
                                </div>
                                <div class="grow min-w-0">
                                    <div class="flex justify-between items-start">
                                        <div class="min-w-0 pe-2">
                                            <div class="font-bold text-slate-800 truncate" style="font-size: 13.5px;"><?= esc($rName) ?></div>
                                            <div class="flex flex-wrap items-center text-slate-400 mt-1 gap-1" style="font-size: 11px;">
                                                <?php if (($r->alpha ?? 0) > 0): ?>
                                                    <span class="text-red-600 font-semibold">
                                                        <i class="bi bi-x-circle-fill me-1"></i><?= $r->alpha ?>x Alpha
                                                    </span>
                                                    <?php if (($r->late ?? 0) > 0): ?>
                                                        <span class="text-slate-400">•</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if (($r->late ?? 0) > 0): ?>
                                                    <span class="text-amber-600 font-semibold">
                                                        <i class="bi bi-alarm-fill me-1"></i><?= $r->late ?>x Telat (<?= $r->total_telat_menit ?? 0 ?>m)
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (($r->alpha ?? 0) === 0 && ($r->late ?? 0) === 0): ?>
                                                    <span class="text-slate-400">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-right whitespace-nowrap shrink-0 ps-1">
                                            <div class="font-bold text-red-600" style="font-size: 15px; letter-spacing: -0.3px;"><?= number_format($r->score, 1) ?></div>
                                            <div class="text-slate-400" style="font-size: 10px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase;">Skor</div>
                                            <?php if (($r->total_denda ?? 0) > 0): ?>
                                                <button type="button" class="score-denda" onclick="window.showDendaDetail(<?= (int)($r->user_id ?? 0) ?>, '<?= date('Y-m') ?>')">Rp<?= number_format($r->total_denda, 0, ',', '.') ?></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($idx < count($lazyRanking) - 1): ?>
                                <hr class="my-1" style="border-top: 1px dashed #fecaca; opacity: 1;">
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Izin -->
        <div id="content-izin" class="tab-content-tw hidden px-4 pb-5">
            <?php
            // Badge status mengikuti pengajuan hari ini, bukan label statis.
            $izinBadgeText  = 'Siap Diisi';
            $izinBadgeClass = 'badge-secondary text-slate-500';
            if ($todayPermit && $permitStatus === 'pending') {
                $izinBadgeText  = 'Menunggu';
                $izinBadgeClass = 'badge-warning text-amber-700';
            } elseif ($todayPermit && $permitStatus === 'approved') {
                $izinBadgeText  = 'Disetujui';
                $izinBadgeClass = 'badge-success text-emerald-700';
            }
            ?>
            <div class="flex items-center justify-between mb-3 gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <button type="button" class="btn-tw btn-tw-sm btn-light btn-back text-slate-600 hover:text-slate-800" onclick="showTab('home')" title="Kembali" aria-label="Kembali">
                        <i class="bi bi-arrow-left text-lg" aria-hidden="true"></i>
                    </button>
                    <h5 class="font-bold mb-0 truncate">Pengajuan Izin</h5>
                </div>
                <span class="badge-tw <?= $izinBadgeClass ?> rounded-full px-3 py-2 shrink-0" style="font-size: 10px;"><?= esc($izinBadgeText) ?></span>
            </div>

            <div class="mb-4 flex gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-3 text-emerald-900" role="note">
                <i class="bi bi-shield-fill-check text-xl opacity-50 shrink-0"></i>
                <div class="text-[13px] leading-snug">
                    Silakan unggah dokumen pendukung (Surat Dokter/Cuti) untuk mempercepat proses persetujuan oleh Kepala Sekolah.
                </div>
            </div>

            <div class="m3-card m3-card-elevated">
                 <form id="formIzin">
                    <div class="mb-3">
                        <label class="form-label-tw text-sm font-bold">Kategori Izin</label>
                        <select class="m3-input" name="jenis" id="jenisIzin" required onchange="updateLampiranLabel(); updateSisaKuota(); updateKategoriCuti(); updateBatasTanggalIzin()">
                            <option value="">Pilih Kategori...</option>
                            <option value="cuti">Cuti</option>
                            <option value="ijin">Izin Keperluan</option>
                            <option value="sakit">Sakit / Kesehatan</option>
                            <option value="dinas">Dinas Luar</option>
                        </select>
                        <div id="sisaKuota" class="mt-1.5 text-[11px] font-semibold text-slate-500">
                            Memuat sisa kuota&hellip;
                        </div>
                    </div>

                    <?php
                        // Kategori cuti mengikuti hak cuti PNS/PPPK
                        // (CutiKategoriService): opsi yang tidak berlaku untuk
                        // status kepegawaian/jenis kelamin pegawai ini tidak
                        // ditampilkan sama sekali.
                        $opsiKategoriCuti = \App\Services\CutiKategoriService::opsiUntukUser((int) (session()->get('user_id') ?? 0));
                    ?>
                    <div class="mb-3 hidden" id="wrapKategoriCuti">
                        <label class="form-label-tw text-sm font-bold">Jenis Cuti</label>
                        <select class="m3-input" name="kategori_cuti" id="kategoriCuti" onchange="updateKetentuanCuti(); updateBatasTanggalIzin()">
                            <option value="">Pilih Jenis Cuti...</option>
                            <?php foreach ($opsiKategoriCuti as $optKat): ?>
                                <option value="<?= esc($optKat['kode']) ?>" data-ketentuan="<?= esc($optKat['ketentuan'], 'attr') ?>"><?= esc($optKat['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="ketentuanCuti" class="mt-1.5 text-[11px] text-slate-500 leading-snug hidden"></div>
                    </div>
                    <?php if (empty($opsiKategoriCuti)): ?>
                        <div class="mb-3 text-[11px] text-slate-400 leading-snug">
                            Cuti Anda mengikuti kuota cuti tahunan umum. Kategori cuti resmi (Tahunan, Besar, Melahirkan, Karena Alasan Penting) hanya tersedia bagi pegawai ASN.
                        </div>
                    <?php endif; ?>

                    <?php
                        // Batas bawah tanggal = "hari ini" versi aplikasi (appTimezone),
                        // bukan jam browser: JS membacanya dari atribut ini supaya
                        // perangkat dengan jam/tanggal berbeda tidak bisa menembus.
                        $hariMajuCuti = \App\Services\CutiKategoriService::HARI_MAJU_PENGAJUAN;
                        $minTglIzin   = date('Y-m-d');
                        $minTglCuti   = date('Y-m-d', strtotime('+' . $hariMajuCuti . ' days'));
                    ?>
                    <div class="grid grid-cols-2 gap-2 mb-1.5" id="izinTanggal"
                         data-min-dasar="<?= $minTglIzin ?>" data-min-cuti="<?= $minTglCuti ?>"
                         data-kategori-bebas-maju="<?= esc(implode(' ', \App\Services\CutiKategoriService::KATEGORI_BEBAS_HARI_MAJU)) ?>">
                        <div>
                            <label class="form-label-tw text-sm font-bold">Mulai Tanggal</label>
                            <input type="date" class="m3-input" name="tgl_mulai" id="izinTglMulai" min="<?= $minTglIzin ?>" required>
                        </div>
                        <div>
                            <label class="form-label-tw text-sm font-bold">Sampai Tanggal</label>
                            <input type="date" class="m3-input" name="tgl_selesai" id="izinTglSelesai" min="<?= $minTglIzin ?>" required>
                        </div>
                    </div>
                    <div class="mb-3 text-[11px] text-slate-400 leading-snug" id="izinTanggalHint">
                        Tanggal pengajuan tidak boleh mundur: mulai <?= date('d/m/Y') ?> dan seterusnya.
                    </div>

                    <div class="mb-3">
                        <label class="form-label-tw text-sm font-bold">Alasan Lengkap</label>
                        <textarea class="m3-input" name="keterangan" rows="3" placeholder="Berikan alasan yang jelas..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-tw text-sm font-bold" id="lampiranLabel">Dokumen Pendukung (Foto/PDF)</label>
                        <label class="block w-full cursor-pointer rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-3 text-center">
                            <i class="bi bi-cloud-arrow-up text-3xl text-blue-500 opacity-50"></i>
                            <div class="mt-1 text-[13px] text-slate-500">Ketuk untuk pilih file</div>
                            <input type="file" class="m3-input hidden" name="bukti" accept="image/*,application/pdf">
                        </label>
                    </div>

                    <button type="button" class="btn-submit-mobile w-full py-3.5 px-4 rounded-2xl font-bold text-white bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 shadow-md transition-all flex items-center justify-center gap-2" onclick="submitIzin()">
                        <i class="bi bi-check2-circle text-lg"></i>
                        <span>Kirim Pengajuan Izin</span>
                    </button>
                 </form>
            </div>

            <div class="mt-4">
                <h6 class="font-bold mb-2.5 text-xs uppercase tracking-wider text-slate-400 px-1">Ketentuan Umum</h6>
                <div class="p-3.5 rounded-2xl bg-white border border-slate-100 shadow-xs">
                    <div class="text-sm font-bold text-slate-800">Batas Waktu Pengajuan</div>
                    <div class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                        <?php
                            // Label kategori yang dikecualikan dari aturan hari
                            // maju ikut dibaca dari CutiKategoriService, supaya
                            // teks ini tidak tertinggal saat pengecualian berubah.
                            $labelBebasMaju = array_map(
                                static fn ($k) => \App\Services\CutiKategoriService::label($k),
                                \App\Services\CutiKategoriService::KATEGORI_BEBAS_HARI_MAJU
                            );
                        ?>
                        Cuti diajukan minimal <?= $hariMajuCuti ?> hari sebelum tanggal mulai (paling cepat
                        <?= date('d/m/Y', strtotime('+' . $hariMajuCuti . ' days')) ?>). Dikecualikan:
                        <?= implode(' dan ', $labelBebasMaju) ?> — kejadiannya memang tidak bisa direncanakan,
                        jadi boleh dimulai hari ini. Izin, sakit, dan dinas juga boleh mulai hari ini.
                        Tidak ada jenis yang boleh memakai tanggal yang sudah lewat; gunakan tab Koreksi.
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: History -->
        <div id="content-history" class="tab-content-tw hidden px-4 pb-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <button type="button" class="btn-tw btn-tw-sm btn-light btn-back text-slate-600 hover:text-slate-800 shadow-2xs" onclick="showTab('home')" title="Kembali" aria-label="Kembali">
                        <i class="bi bi-arrow-left text-lg" aria-hidden="true"></i>
                    </button>
                    <div>
                        <h5 class="font-bold mb-0 text-slate-800">Statistik Personal</h5>
                        <p class="text-xs text-slate-400 mb-0">Performa & Kehadiran Saya</p>
                    </div>
                </div>
                <div class="bg-brand-50 text-brand-600 rounded-2xl px-3 py-1.5 text-center border border-brand-100/60 shadow-2xs">
                    <div class="font-black text-sm"><?= date('M') ?></div>
                    <div class="text-[9px] uppercase font-bold tracking-wider opacity-75"><?= date('Y') ?></div>
                </div>
            </div>
            <div id="history-loading" class="text-center py-5">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600"></div>
            </div>
            <div id="history-data" class="hidden">
                <div id="history-stats"></div>
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm my-4">
                     <div class="flex items-center justify-between mb-3">
                         <h6 class="font-bold mb-0 text-sm text-slate-800 flex items-center gap-2">
                             <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                                 <i class="bi bi-calendar3"></i>
                             </span>
                             Kalender Kehadiran
                         </h6>
                         <span class="text-[11px] font-bold text-slate-500 bg-slate-50 px-2.5 py-1 rounded-full border border-slate-100"><?= date('F Y') ?></span>
                     </div>
                     <div id="history-calendar" class="calendar-grid"></div>
                     <div class="flex flex-wrap gap-3 mt-4 pt-3 border-t border-slate-100">
                         <div class="flex items-center gap-1.5">
                             <span style="width:9px; height:9px; border-radius:50%; background:#10b981" class="shadow-2xs"></span>
                             <span class="text-[11px] text-slate-500 font-semibold">Hadir</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span style="width:9px; height:9px; border-radius:50%; background:#f59e0b" class="shadow-2xs"></span>
                             <span class="text-[11px] text-slate-500 font-semibold">Telat</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span style="width:9px; height:9px; border-radius:50%; background:#ef4444" class="shadow-2xs"></span>
                             <span class="text-[11px] text-slate-500 font-semibold">Alpa</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span style="width:9px; height:9px; border-radius:50%; background:var(--primary-500)" class="shadow-2xs"></span>
                             <span class="text-[11px] text-slate-500 font-semibold">Izin/Dinas</span>
                         </div>
                     </div>
                </div>
                <div id="history-list"></div>
            </div>
        </div>

        <!-- Tab: Koreksi -->
        <div id="content-koreksi" class="tab-content-tw hidden px-4 pb-5">
             <div class="koreksi-head">
                 <button type="button" class="btn-tw btn-tw-sm btn-light btn-back koreksi-back text-slate-600 hover:text-slate-800" onclick="showTab('home')" title="Kembali" aria-label="Kembali">
                     <i class="bi bi-arrow-left" aria-hidden="true"></i>
                 </button>
                 <div class="min-w-0 grow">
                     <h5>Koreksi Absensi</h5>
                     <p>Perbaiki jam absensi yang keliru</p>
                 </div>
                 <span class="badge-tw badge-primary-subtle text-brand-600 rounded-full px-3 py-2 shrink-0" style="font-size: 10px;"><i class="bi bi-clock-history"></i>Review Admin</span>
             </div>

              <div class="koreksi-note" role="note">
                 <span class="koreksi-note-ic"><i class="bi bi-info-circle-fill" aria-hidden="true"></i></span>
                 <p>Gunakan fitur ini jika terdapat kesalahan pencatatan waktu atau sistem. Perubahan memerlukan persetujuan Admin.</p>
              </div>

             <div class="m3-card m3-card-elevated koreksi-card mb-4">
                <form id="formKoreksi">
                    <div class="mb-3">
                        <label class="form-label-tw"><i class="bi bi-calendar-event" aria-hidden="true"></i> Pilih Tanggal Absensi</label>
                        <select class="m3-input" id="koreksiAttendanceId" required>
                            <option value="">Memuat data...</option>
                        </select>
                        <div id="koreksiMuatPesan" class="mt-1.5 text-[11px] text-slate-400 leading-snug hidden"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div>
                            <label class="form-label-tw">Jenis Koreksi</label>
                            <select class="m3-input" name="field_name" required>
                                <option value="jam_masuk">Jam Masuk</option>
                                <option value="jam_pulang">Jam Pulang</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label-tw">Jam Baru</label>
                            <input type="time" class="m3-input" name="new_value" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-tw">Alasan Perubahan</label>
                        <textarea class="m3-input" name="reason" rows="3" placeholder="Contoh: Lupa absen pulang atau kendala jaringan..." required></textarea>
                    </div>

                    <button type="button" class="btn-submit-mobile koreksi-submit w-full py-3.5 px-4 rounded-2xl font-bold text-white bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 shadow-md transition-all flex items-center justify-center gap-2" onclick="submitKoreksi()">
                        <i class="bi bi-send-fill" aria-hidden="true"></i>
                        <span>Ajukan Koreksi</span>
                    </button>
                </form>
             </div>

             <div class="koreksi-history-head"><i class="bi bi-clock-history" aria-hidden="true"></i> Riwayat Pengajuan</div>
             <div id="my-corrections-list"></div>
        </div>

        <!-- Tab: Ranking -->
        <div id="content-ranking" class="tab-content-tw hidden px-4 pb-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-tw btn-tw-sm btn-light btn-back text-slate-600 hover:text-slate-800" onclick="showTab('home')" title="Kembali" aria-label="Kembali">
                        <i class="bi bi-arrow-left text-lg" aria-hidden="true"></i>
                    </button>
                    <div>
                        <h5 class="font-bold mb-0">Leaderboard</h5>
                        <p class="text-sm text-slate-400 mb-0">Kedisiplinan Bulan Ini</p>
                    </div>
                </div>
                <div class="bg-brand-50 text-brand-600 rounded-2xl p-2 text-center" style="min-width: 60px;">
                    <div class="font-bold" style="font-size: 14px;"><?= date('M') ?></div>
                    <div style="font-size: 9px; text-transform: uppercase;"><?= date('Y') ?></div>
                </div>
            </div>

            <div id="ranking-list-full" class="pb-4">
                <div class="text-center py-5">
                    <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Ranking (Bottom Sheet Tailwind) -->
<div class="tw-modal" id="rankingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="tw-modal-box">
        <div class="max-h-[92vh] overflow-y-auto no-scrollbar">
            <div class="flex justify-center pt-2">
                <div class="h-1 w-10 rounded-full bg-slate-200"></div>
            </div>
            <div class="p-4">
                <div class="flex items-center gap-3 mb-4">
                    <div id="rd-avatar-container" class="shrink-0"></div>
                    <div class="grow min-w-0">
                        <h5 class="font-bold mb-0 truncate" id="rd-name">---</h5>
                        <p class="text-[13px] text-slate-400 mb-0 truncate" id="rd-username">@---</p>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="font-bold text-xl text-brand-600 mb-0" id="rd-score">0.0</div>
                        <div class="text-slate-400 uppercase" style="font-size: 9.5px; letter-spacing: .3px;">TOTAL SKOR</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="col-span-2 sm:col-span-1">
                        <div class="p-3 rounded-2xl bg-slate-100">
                            <div class="text-slate-400 text-[13px] mb-1" style="font-size: 10px;">KEHADIRAN</div>
                            <div class="font-bold text-base"><span id="rd-hadir">0</span> Hari</div>
                            <div class="text-[13px] text-slate-400 mt-1" style="font-size: 10px; line-height: 1.2;">
                                Hadir <span id="rd-hadir-reguler" class="font-bold text-slate-800">0</span> / Dinas <span id="rd-hadir-dinas" class="font-bold text-brand-600">0</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="p-3 rounded-2xl bg-slate-100">
                            <div class="text-slate-400 text-[13px] mb-1" style="font-size: 10px;">TEPAT WAKTU</div>
                            <div class="font-bold text-base text-emerald-600"><span id="rd-ontime">0</span> Hari</div>
                        </div>
                    </div>
                    <div>
                        <div class="p-3 rounded-2xl bg-slate-100">
                            <div class="text-slate-400 text-[13px] mb-1" style="font-size: 10px;">TERLAMBAT</div>
                            <div class="font-bold text-base text-amber-500"><span id="rd-late">0</span> Kali</div>
                        </div>
                    </div>
                    <div>
                        <div class="p-3 rounded-2xl bg-slate-100">
                            <div class="text-slate-400 text-[13px] mb-1" style="font-size: 10px;">MENIT TELAT</div>
                            <div class="font-bold text-base text-red-600"><span id="rd-menit">0</span> Menit</div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="p-3 rounded-2xl bg-red-50">
                            <div class="text-red-600 text-[13px] mb-2 font-bold" style="font-size: 10px;">DETAIL DENDA</div>

                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[13px] text-red-600 opacity-75" style="font-size: 11px;">Denda Bulan Ini</span>
                                <span class="font-bold text-red-600">Rp <span id="rd-denda-bulan-ini">0</span></span>
                            </div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[13px] text-red-600 opacity-75" style="font-size: 11px;">Total Denda</span>
                                <span class="font-bold text-red-600">Rp <span id="rd-denda-total">0</span></span>
                            </div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[13px] text-emerald-600 opacity-75" style="font-size: 11px;">Total Dibayar</span>
                                <span class="font-bold text-emerald-600">Rp <span id="rd-denda-dibayar">0</span></span>
                            </div>
                            <div class="bg-white/75 rounded-xl p-2 my-1 border border-red-200" style="font-size: 10.5px;">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-slate-400"><i class="bi bi-cash-stack me-1 text-emerald-600"></i>Bayar Cash</span>
                                    <span class="font-bold text-slate-800">Rp <span id="rd-denda-cash">0</span></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400"><i class="bi bi-star-fill me-1 text-amber-500"></i>Bayar dari Point</span>
                                    <span class="font-bold text-brand-600">Rp <span id="rd-denda-poin">0</span></span>
                                </div>
                                <div class="flex justify-between items-center mt-1" id="box-rd-denda-saldo" style="display: none;">
                                    <span class="text-slate-400"><i class="bi bi-wallet2 me-1 text-sky-600"></i>Potong Saldo Denda</span>
                                    <span class="font-bold text-sky-600">Rp <span id="rd-denda-saldo">0</span></span>
                                </div>
                            </div>
                            <hr class="border-red-200 opacity-25 my-2">
                            <div class="flex justify-between items-center">
                                <span class="text-[13px] text-red-600 font-bold" style="font-size: 12px;">Sisa Denda</span>
                                <button type="button" id="rd-denda-btn" class="font-bold text-red-600 text-xl no-underline" style="padding:8px 4px;margin:-8px -4px;" title="Lihat detail denda">Rp <span id="rd-denda">0</span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4 p-3 rounded-2xl bg-blue-50">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="bi bi-info-circle-fill text-blue-600"></i>
                        <h6 class="mb-0 font-bold text-[13px] text-blue-600">Informasi Tambahan</h6>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="text-[13px] opacity-75">Datang Awal: <span id="rd-awal">0</span>m</div>
                        <div class="text-[13px] opacity-75">Pulang Akhir: <span id="rd-akhir">0</span>m</div>
                    </div>
                </div>

                <button type="button" class="btn-tw btn-secondary w-full py-3 font-bold" data-tw-dismiss="modal">Tutup Detail</button>
            </div>
        </div>
    </div>
</div>

<!-- Face Scanner v2 — mandiri (redesign 2026-09-28) -->
<div id="cameraModal" class="camera-overlay hidden" data-scanner="v2">
    <div class="fs-head">
        <button type="button" onclick="closeCamera()" aria-label="Tutup pemindai" class="fs-close">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="fs-titlewrap">
            <h6 id="cameraTitle" class="fs-title">Absen Masuk</h6>
            <p class="fs-sub">Sejajarkan wajah di dalam bingkai</p>
        </div>
        <span class="fs-head-spacer" aria-hidden="true"></span>
    </div>

    <div class="camera-sensor-box">
        <div class="camera-video-container">
            <video id="videoElement" autoplay playsinline muted style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
            <canvas id="canvasElement" style="position:absolute;left:0;top:0;width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></canvas>

            <div id="fsCamOff" class="fs-camoff" role="status">
                <div class="fs-camoff-ic"><i class="bi bi-person-bounding-box"></i></div>
                <div class="fs-camoff-tx">
                    <b>Menyiapkan kamera…</b>
                </div>
            </div>

            <div class="fs-scrim" role="note">
                <span><i class="bi bi-brightness-high"></i> Cahaya merata &bull; tanpa masker</span>
            </div>

            <div id="cameraFlash" style="position:absolute;left:0;top:0;width:100%;height:100%;background:#fff;opacity:0;pointer-events:none;z-index:5;"></div>

            <div id="faceGuide" class="face-guide">
                <span class="fs-scan" aria-hidden="true"></span>
            </div>

            <div class="fs-topchips">
                <span class="fs-chip"><span class="fs-livedot"></span>FACE ID</span>
            </div>

            <div class="fs-stage-ring"></div>
        </div>

        <div id="facedetect-status" role="status" aria-live="polite" aria-atomic="true">
            <span class="dot"></span><span class="fs-blink">Memuat deteksi wajah...</span>
        </div>

        <div id="auto-progress" style="display:none;margin-top:12px;width:100%;max-width:20rem;text-align:center;">
            <div style="width:11rem;height:6px;background:rgba(255,255,255,.15);border-radius:999px;overflow:hidden;margin:0 auto 6px;">
                <div id="auto-progress-bar" style="width:0%;height:100%;border-radius:999px;background:linear-gradient(90deg,var(--brand-400),#34d399);"></div>
            </div>
            <div id="auto-progress-label" style="font-size:11px;font-weight:500;color:#cbd5e1;">Menganalisis wajah...</div>
        </div>

        <div class="shutter-btn-wrapper">
            <button type="button" id="btnManualCapture" class="btn-camera-shutter" onclick="AbsensiApp.triggerManualCapture()" title="Ambil foto absen" aria-label="Ketuk untuk ambil foto absen">
                <span class="shutter-core"><i class="bi bi-camera-fill"></i></span>
            </button>
            <div class="shutter-btn-label"><i class="bi bi-hand-index-thumb"></i> Ketuk untuk absen</div>
            <div class="shutter-btn-hint">atau tunggu otomatis saat wajah stabil</div>
        </div>

        <div class="fs-loc">
            <div class="fs-loc-main">
                <div class="fs-loc-txt">
                    <span class="fs-loc-label">LOKASI ABSEN</span>
                    <p id="locText"><i class="bi bi-geo-alt"></i> Mendeteksi lokasi...</p>
                </div>
            </div>
            <div class="fs-loc-actions" id="locActions">
                <button type="button" id="locRetryBtn" class="fs-loc-btn fs-loc-btn-retry" onclick="AbsensiApp.detectLocation()" aria-label="Muat ulang lokasi">
                    <i class="bi bi-arrow-clockwise"></i><span>Muat Ulang Lokasi</span>
                </button>
                <button type="button" id="locPermissionBtn" class="fs-loc-btn fs-loc-btn-permit" onclick="AbsensiApp.showLocationHelp()" aria-label="Cara mengizinkan lokasi">
                    <i class="bi bi-question-circle"></i><span>Izinkan Lokasi</span>
                </button>
            </div>
        </div>

        <div id="outsideAreaAlert" class="fs-alert hidden">
            <i class="bi bi-exclamation-triangle-fill"></i> Di luar area — buka opsi di bawah.
        </div>

        <details class="fs-more">
            <summary>
                <span class="fs-more-l"><i class="bi bi-sliders2"></i> Opsi dinas / WFH</span>
                <i class="bi bi-chevron-down fs-chev"></i>
            </summary>
            <div class="camera-options-card">
            <label for="dinasCheck" class="mode-row">
                <input type="checkbox" id="dinasCheck" class="fs-hideinput">
                <span class="mode-toggle" aria-hidden="true"></span>
                <span class="mode-ic amber"><i class="bi bi-briefcase"></i></span>
                <span class="mode-tx">
                    <span class="mode-tt">Mode Dinas</span>
                    <span class="mode-sub">Tugas luar — tanpa batas radius</span>
                </span>
            </label>
            <div id="wfhCheckContainer" class="fs-sep">
                <label for="wfhCheck" class="mode-row">
                    <input type="checkbox" id="wfhCheck" class="fs-hideinput">
                    <span class="mode-toggle" aria-hidden="true"></span>
                    <span class="mode-ic sky"><i class="bi bi-house-door"></i></span>
                    <span class="mode-tx">
                        <span class="mode-tt">WFH</span>
                        <span id="wfhQuotaText" class="mode-sub">Work from home sesuai kuota</span>
                    </span>
                </label>
            </div>
            <div id="dinasKetContainer" class="hidden" style="padding:10px 2px 2px;">
                <label for="dinasKeterangan" style="display:block;font-size:11px;font-weight:800;color:#334155;margin-bottom:6px;">
                    Alasan tugas luar <span style="color:#b45309;">(wajib)</span>
                </label>
                <input type="text" id="dinasKeterangan" maxlength="200" autocomplete="off"
                       placeholder="mis. Pendampingan lomba di Dinas Pendidikan"
                       style="width:100%;padding:10px 12px;font-size:13px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;color:#0f172a;">
            </div>
            </div>
        </details>
    </div>
</div>

<!-- ══ FaceScan biometric modal ══ -->
<div id="facescan-modal" class="fixed top-0 left-0 w-full h-full items-center justify-center" style="display:none;opacity:0;z-index:3600;background:rgba(2,6,23,.86);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);padding:16px;padding-top:calc(16px + env(safe-area-inset-top,0px));padding-bottom:calc(16px + env(safe-area-inset-bottom,0px));overflow-y:auto;overscroll-behavior:contain;" role="dialog" aria-modal="true" aria-labelledby="facescan-title">
    <div id="facescan-modal-card" class="bg-white w-full relative" style="max-width:25rem;border-radius:26px;padding:18px;transform:translateY(24px);border:1px solid #f1f5f9;box-shadow:0 25px 60px rgba(2,6,23,.35);max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);overflow-y:auto;overscroll-behavior:contain;">
        <!-- Kepala modal: judul yang dibaca aria-labelledby + tombol senyap -->
        <div class="fs-modal-head">
            <div class="fs-modal-icon"><i class="bi bi-person-bounding-box"></i></div>
            <div style="min-width:0;">
                <h4 id="facescan-title" class="fs-modal-title m-0">Verifikasi Wajah</h4>
                <p id="facescan-subtitle" class="fs-modal-sub">Ikuti panduan sampai selesai</p>
            </div>
        </div>

        <!-- Camera viewport -->
        <div id="facescan-viewport" class="fs-viewport" style="margin-top:14px;">
            <video id="facescan-video" class="w-full h-full hidden" style="object-fit:cover;" autoplay playsinline muted></video>
            <div id="facescan-fallback" class="fs-fallback">
                <div class="fs-fallback-icon"><i id="facescan-fallback-icon" class="bi bi-camera-video-off"></i></div>
                <p id="facescan-fallback-title" class="fs-fallback-title">Kamera belum aktif</p>
                <p id="facescan-fallback-sub" class="fs-fallback-sub">Pemindaian dimulai otomatis</p>
            </div>
            <canvas id="facescan-canvas" class="hidden"></canvas>

            <!-- Target ring + laser: satu bingkai tipis, tanpa mesh/radar -->
            <div id="facescan-ring" class="fs-deco fs-ring absolute rounded-full flex items-center justify-center" style="border:1.5px solid rgba(45,212,191,.55);transition:border-color .3s,box-shadow .3s;">
                <div class="absolute w-full facescan-laser" style="height:2px;background:linear-gradient(90deg,transparent,rgba(45,212,191,.9),transparent);box-shadow:0 0 10px rgba(45,212,191,.55);"></div>
                <div class="absolute" style="top:-3px;left:-3px;width:18px;height:18px;border-top:2px solid rgba(45,212,191,.75);border-left:2px solid rgba(45,212,191,.75);border-top-left-radius:7px;"></div>
                <div class="absolute" style="top:-3px;right:-3px;width:18px;height:18px;border-top:2px solid rgba(45,212,191,.75);border-right:2px solid rgba(45,212,191,.75);border-top-right-radius:7px;"></div>
                <div class="absolute" style="bottom:-3px;left:-3px;width:18px;height:18px;border-bottom:2px solid rgba(45,212,191,.75);border-left:2px solid rgba(45,212,191,.75);border-bottom-left-radius:7px;"></div>
                <div class="absolute" style="bottom:-3px;right:-3px;width:18px;height:18px;border-bottom:2px solid rgba(45,212,191,.75);border-right:2px solid rgba(45,212,191,.75);border-bottom-right-radius:7px;"></div>
            </div>

            <!-- Step capsule -->
            <div class="fs-step-pill">
                <span class="rounded-full bg-emerald-500 animate-pulse" style="display:inline-block;width:7px;height:7px;flex-shrink:0;"></span>
                <span id="facescan-step-text" class="font-bold facescan-mono">Menyiapkan...</span>
            </div>

            <!-- Target chip + kontrol suara, menyatu dengan kanvas kamera -->
            <div class="fs-viewport-actions">
                <span id="facescan-target-badge" class="fs-target-chip is-neutral">Menyiapkan</span>
                <button type="button" id="facescan-sound" class="fs-sound-btn" onclick="FaceScan.toggleSound()" aria-pressed="true" aria-label="Suara notifikasi aktif" title="Suara notifikasi">
                    <i class="bi bi-volume-up-fill"></i>
                </button>
            </div>

            <!-- GPS bar -->
            <div id="facescan-gpsbar">
                <span class="font-semibold truncate flex items-center gap-1" style="min-width:0;">
                    <i class="bi bi-geo-alt-fill text-rose-500"></i><span id="facescan-loc-name">Mendeteksi GPS...</span>
                </span>
                <span id="facescan-loc-dist" class="font-bold facescan-mono" style="white-space:nowrap;">—</span>
            </div>

            <!-- Success overlay: struk singkat hasil absen -->
            <div id="facescan-success" class="absolute top-0 left-0 w-full h-full text-white" style="display:none;z-index:30;background:radial-gradient(120% 100% at 50% 0%, #0b3d2e 0%, #052e22 55%, #021a13 100%);">
                <div class="w-full h-full flex flex-col items-center justify-center fs-receipt">
                    <div id="facescan-success-icon" class="rounded-full bg-emerald-500 flex items-center justify-center text-white facescan-pop" style="width:54px;height:54px;font-size:25px;box-shadow:0 8px 22px -6px rgba(16,185,129,.55);">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h4 id="facescan-success-title" class="font-extrabold m-0" style="font-size:16px;letter-spacing:-.2px;">Presensi Berhasil</h4>
                    <span id="facescan-success-pill" class="fs-receipt-pill"><i class="bi bi-check-circle-fill"></i><span>Tepat Waktu</span></span>
                    <p id="facescan-success-note" class="fs-receipt-note"></p>
                    <div id="facescan-success-meta" class="fs-receipt-meta"></div>
                    <button type="button" id="facescan-success-close" onclick="FaceScan.selesai()">
                        <i class="bi bi-check2-all"></i> Selesai
                    </button>
                </div>
            </div>
        </div>

        <!-- Progress panel -->
        <div class="fs-panel">
            <div class="fs-status-row" aria-live="polite" aria-atomic="true">
                <span class="fs-status-label">
                    <i id="facescan-status-icon" class="bi bi-arrow-repeat animate-spin text-slate-400"></i>
                    <span id="facescan-status-label">Menyiapkan pemindai...</span>
                </span>
                <span id="facescan-progress-pct" class="text-slate-400 facescan-mono">0%</span>
            </div>
            <div class="fs-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="facescan-progress">
                <div id="facescan-progress-bar" class="h-full rounded-full" style="width:0%;background:linear-gradient(90deg,var(--brand-500),#f59e0b,#22c55e);transition:width .3s;"></div>
            </div>
            <div class="fs-hint-row">
                <i class="bi bi-info-circle"></i>
                <span><span class="fs-hint-label">Panduan:</span> <span id="facescan-explainer" class="facescan-mono">Posisikan wajah &amp; tatap kamera</span></span>
            </div>
        </div>

        <!-- Error box -->
        <div id="facescan-error" class="hidden" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-triangle-fill"></i><span id="facescan-error-text"></span>
        </div>

        <!-- Footer -->
        <div class="fs-actions">
            <button type="button" id="facescan-cancel" onclick="FaceScan.close()" class="fs-btn fs-btn-secondary">Batalkan</button>
            <button type="button" id="facescan-retry" onclick="FaceScan.restart()" class="fs-btn fs-btn-primary">
                <i class="bi bi-arrow-clockwise"></i> <span id="facescan-retry-label">Pindai Ulang</span>
            </button>
        </div>
        <button type="button" id="facescan-legacy" onclick="FaceScan.legacy()" class="fs-legacy-btn hidden">
            <i class="bi bi-shield-check"></i> Mode Dinas / WFH
        </button>
        <p class="fs-privacy"><i class="bi bi-shield-lock-fill"></i> Foto hanya dipakai untuk verifikasi presensi.</p>
    </div>
</div>

<!-- ══ Modal Kartu Presensi Digital Guru / GTK ══ -->
<?php $qrTeacherVal = !empty($user->nip) ? $user->nip : (!empty($user->kode_guru) ? $user->kode_guru : $user->username); ?>
<div id="teacher-qr-modal" class="fixed top-0 left-0 w-full h-full items-center justify-center" style="display:none;opacity:0;z-index:9999;background:rgba(2,6,23,.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);padding:16px;overflow-y:auto;overscroll-behavior:contain;">
    <div id="teacher-qr-card" class="bg-white w-full relative" style="max-width:24rem;border-radius:28px;padding:24px;transform:translateY(24px);border:1px solid #f1f5f9;box-shadow:0 25px 60px rgba(2,6,23,.35);transition:transform .3s ease;max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);overflow-y:auto;overscroll-behavior:contain;">
        <button type="button" onclick="closeTeacherQrModal()" class="absolute items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500" style="display:flex;top:16px;right:16px;width:42px;height:42px;z-index:20;border:none;cursor:pointer;" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>

        <!-- Header Modal -->
        <div class="text-center mb-4" style="padding-right:24px;padding-left:24px;">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-50 text-purple-700 rounded-full text-[11px] font-bold mb-2">
                <i class="bi bi-person-badge-fill"></i> Kartu Presensi Digital GTK
            </div>
            <h3 class="text-base font-extrabold text-slate-900 m-0 line-clamp-2"><?= esc($fullName ?? ($user->nama_guru ?? 'Guru')) ?></h3>
            <p class="text-xs text-slate-500 font-mono mt-1 mb-0">NIP: <?= esc($qrTeacherVal) ?></p>
            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                <?= esc(!empty($user->tgs_tambahan) ? $user->tgs_tambahan : (!empty($user->jenis_ptk) ? $user->jenis_ptk : 'Guru / GTK')) ?>
            </span>
        </div>

        <!-- QR Code Body -->
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 flex flex-col items-center justify-center">
            <div id="modalTeacherQr" class="p-2 bg-white rounded-xl shadow-xs border border-slate-200" style="width:196px;height:196px;display:flex;align-items:center;justify-content:center;"></div>
            <p class="text-[12px] font-extrabold text-slate-800 mt-2.5 font-mono mb-0"><?= esc($qrTeacherVal) ?></p>
            <p class="text-[10px] text-purple-700 font-semibold mt-1 mb-0 text-center">
                <i class="bi bi-info-circle-fill me-1"></i>Arahkan QR Code ini ke kamera Kiosk Scanner untuk absensi masuk &amp; pulang
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2.5 mt-4">
            <button type="button" onclick="downloadTeacherCard('jpg')" class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-all border-0 cursor-pointer">
                <i class="bi bi-download"></i> Download Kartu
            </button>
            <button type="button" onclick="closeTeacherQrModal()" class="flex-1 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all border-0 cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- ══ Modal Unduh Rekap Bulanan (PDF) ══ -->
<div id="rekap-modal" class="fixed top-0 left-0 w-full h-full items-center justify-center" style="display:none;opacity:0;z-index:9999;background:rgba(2,6,23,.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);padding:16px;overflow-y:auto;overscroll-behavior:contain;">
    <div id="rekap-modal-card" class="bg-white w-full relative" style="max-width:24rem;border-radius:28px;padding:24px;transform:translateY(24px);border:1px solid #f1f5f9;box-shadow:0 25px 60px rgba(2,6,23,.35);transition:transform .3s ease;max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);overflow-y:auto;overscroll-behavior:contain;">
        <button type="button" onclick="closeRekapModal()" class="absolute items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500" style="display:flex;top:16px;right:16px;width:42px;height:42px;z-index:20;border:none;cursor:pointer;" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="mb-4" style="padding-right:36px;">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-700 rounded-full text-[11px] font-bold mb-2">
                <i class="bi bi-file-earmark-pdf-fill"></i> Rekap Bulanan
            </div>
            <h3 class="text-base font-extrabold text-slate-900 m-0">Unduh Rekap PDF</h3>
            <p class="text-xs text-slate-500 mt-1 mb-0">Pilih bulan, lalu unduh rekap absensi siswa atau guru dalam format PDF.</p>
        </div>

        <label for="rekapMonth" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Bulan</label>
        <input type="month" id="rekapMonth" value="<?= date('Y-m') ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 outline-none focus:border-rose-400 focus:bg-white transition-all mb-3" style="appearance:none;">

        <label for="rekapKelas" class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Kelas (rekap siswa)</label>
        <select id="rekapKelas" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 outline-none focus:border-rose-400 focus:bg-white transition-all">
            <option value="">Kelas default</option>
        </select>

        <div class="grid grid-cols-1 gap-2.5 mt-4">
            <a id="btnRekapSiswa" href="#" target="_blank" rel="noopener" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-extrabold shadow-md flex items-center justify-center gap-2 transition-all no-underline">
                <i class="bi bi-people-fill text-sm"></i>
                <span>Rekap Siswa (PDF)</span>
            </a>
            <a id="btnRekapGuru" href="#" target="_blank" rel="noopener" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white rounded-xl text-xs font-extrabold shadow-md flex items-center justify-center gap-2 transition-all no-underline">
                <i class="bi bi-person-badge-fill text-sm"></i>
                <span>Rekap Guru (PDF)</span>
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/qrcode.min.js') ?>"></script>
<script src="<?= base_url('assets/js/face-api.min.js') ?>"></script>
<script defer src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="<?= base_url('assets/js/absensi-mobile.js?v=' . (@filemtime(FCPATH . 'assets/js/absensi-mobile.js') ?: time())) ?>"></script>
<script>
    // Digital Teacher QR Modal handlers
    let teacherQrGenerated = false;
    window.openTeacherQrModal = function() {
        const modal = document.getElementById('teacher-qr-modal');
        const card  = document.getElementById('teacher-qr-card');
        if (!modal) {
            console.error('Modal teacher-qr-modal not found');
            return;
        }
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
            if (card) card.style.transform = 'translateY(0)';
        });

        if (!teacherQrGenerated) {
            const qrEl = document.getElementById('modalTeacherQr');
            const qrText = <?= json_encode((string) ($qrTeacherVal ?? '')) ?>;
            if (qrEl) {
                if (window.QRCode) {
                    try {
                        qrEl.innerHTML = '';
                        new QRCode(qrEl, {
                            text: qrText,
                            width: 180,
                            height: 180,
                            colorDark: "#1e1b4b",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                        teacherQrGenerated = true;
                    } catch (e) {
                        console.error('Error generating teacher QR with QRCode.js:', e);
                        renderQrFallback(qrEl, qrText);
                    }
                } else {
                    renderQrFallback(qrEl, qrText);
                }
            }
        }
    };

    function renderQrFallback(el, text) {
        if (!el) return;
        el.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(text) + '" alt="QR Code" style="width:180px;height:180px;display:block;border-radius:8px;" onerror="this.outerHTML=\'<span class=\\\'font-mono font-bold text-sm\\\'>' + text + '</span>\'">';
        teacherQrGenerated = true;
    }

    window.closeTeacherQrModal = function() {
        const modal = document.getElementById('teacher-qr-modal');
        const card  = document.getElementById('teacher-qr-card');
        if (!modal) return;
        modal.style.opacity = '0';
        if (card) card.style.transform = 'translateY(24px)';
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    };

    // ── Modal Unduh Rekap Bulanan (PDF) ─────────────────────────
    (function () {
        const URL_SISWA = <?= json_encode(site_url('guru/presensi-siswa/rekap/pdf')) ?>;
        const URL_GURU  = <?= json_encode(site_url('presensi/rekap-guru/pdf')) ?>;
        const URL_KELAS = <?= json_encode(site_url('guru/presensi-siswa/rekap/kelas')) ?>;
        let kelasLoaded = false;

        function parseMonth() {
            const v = document.getElementById('rekapMonth')?.value || '';
            const m = v.match(/^(\d{4})-(\d{2})$/);
            return m ? { bulan: m[2], tahun: m[1] } : { bulan: '<?= date('m') ?>', tahun: '<?= date('Y') ?>' };
        }

        function refreshRekapLinks() {
            const { bulan, tahun } = parseMonth();
            const kelas = document.getElementById('rekapKelas')?.value || '';
            const q = `?bulan=${encodeURIComponent(bulan)}&tahun=${encodeURIComponent(tahun)}`;
            const siswa = document.getElementById('btnRekapSiswa');
            const guru  = document.getElementById('btnRekapGuru');
            if (siswa) siswa.href = URL_SISWA + q + (kelas ? '&id_kelas=' + encodeURIComponent(kelas) : '') + '&download=1';
            if (guru)  guru.href  = URL_GURU + q + '&download=1';
        }

        function loadKelas() {
            if (kelasLoaded) return;
            kelasLoaded = true;
            fetch(URL_KELAS, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => r.json())
                .then((d) => {
                    const sel = document.getElementById('rekapKelas');
                    if (!sel || !d || !d.status || !Array.isArray(d.kelas)) return;
                    d.kelas.forEach((k) => {
                        const opt = document.createElement('option');
                        opt.value = k.id;
                        opt.textContent = k.nama + (k.jumlah ? ' (' + k.jumlah + ' siswa)' : '');
                        sel.appendChild(opt);
                    });
                    if (d.kelas.length === 1) sel.value = String(d.kelas[0].id);
                    refreshRekapLinks();
                })
                .catch(() => { kelasLoaded = false; });
        }

        window.openRekapModal = function () {
            const modal = document.getElementById('rekap-modal');
            const card  = document.getElementById('rekap-modal-card');
            if (!modal) return;
            modal.style.display = 'flex';
            requestAnimationFrame(() => {
                modal.style.opacity = '1';
                if (card) card.style.transform = 'translateY(0)';
            });
            loadKelas();
            refreshRekapLinks();
        };

        window.closeRekapModal = function () {
            const modal = document.getElementById('rekap-modal');
            const card  = document.getElementById('rekap-modal-card');
            if (!modal) return;
            modal.style.opacity = '0';
            if (card) card.style.transform = 'translateY(24px)';
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        };

        document.getElementById('rekapMonth')?.addEventListener('change', refreshRekapLinks);
        document.getElementById('rekapKelas')?.addEventListener('change', refreshRekapLinks);
    })();

    // ── Unduh kartu presensi sebagai gambar (PNG/JPG) ───────────
    // Kartu digambar ulang di <canvas> (bukan screenshot DOM) supaya hasil
    //Always tajam di layar retina dan tidak bergantung html2canvas/CDN.
    window.TEACHER_CARD = <?= json_encode([
        'nama'  => trim((string) ($fullName ?? ($user->nama_guru ?? 'Guru'))),
        'nip'   => (string) $qrTeacherVal,
        'jabatan' => (string) (!empty($user->tgs_tambahan) ? $user->tgs_tambahan : (!empty($user->jenis_ptk) ? $user->jenis_ptk : 'Guru / GTK')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function tcRoundRect(ctx, x, y, w, h, r) {
        if (typeof ctx.roundRect === 'function') { ctx.beginPath(); ctx.roundRect(x, y, w, h, r); return; }
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    // Pecah teks jadi baris-baris yang muat dalam maxWidth.
    function tcWrap(ctx, text, maxWidth) {
        const words = String(text).split(/\s+/);
        const lines = [];
        let line = '';
        words.forEach(function (w) {
            const test = line ? line + ' ' + w : w;
            if (ctx.measureText(test).width > maxWidth && line) { lines.push(line); line = w; }
            else { line = test; }
        });
        if (line) lines.push(line);
        return lines;
    }

    function tcFail(msg) {
        if (window.Swal) { Swal.fire({ icon: 'error', title: 'Gagal mengunduh', text: msg, confirmButtonColor: '#e11d48' }); }
        else { alert(msg); }
    }

    // Ambil QR yang sudah dirender menjadi data-URL (canvas / img / svg).
    function tcQrDataUrl() {
        const host = document.getElementById('modalTeacherQr');
        if (!host) return '';
        const cv = host.querySelector('canvas');
        if (cv) { try { return cv.toDataURL('image/png'); } catch (e) { /* tainted canvas */ } }
        const img = host.querySelector('img');
        if (img && img.src) return img.src;
        const svg = host.querySelector('svg');
        if (svg) {
            try {
                const xml = new XMLSerializer().serializeToString(svg);
                return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(xml)));
            } catch (e) { /* abaikan */ }
        }
        return '';
    }

    window.downloadTeacherCard = function (format) {
        // Default JPG: format yang dipakai tombol "Download Kartu".
        const isJpg = (format === undefined || format === null || format === '' || format === 'jpg' || format === 'jpeg');
        const meta = window.TEACHER_CARD || {};
        const qrSrc = tcQrDataUrl();

        if (!qrSrc) {
            tcFail('QR Code belum siap. Tutup kartu ini, lalu buka kembali.');
            return;
        }

        const W = 400, H = 566, S = 2;              // S = skala untuk layar retina
        const canvas = document.createElement('canvas');
        canvas.width = W * S; canvas.height = H * S;
        const ctx = canvas.getContext('2d');
        ctx.scale(S, S);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        const qrImg = new Image();
        qrImg.onload = function () { tcPaint(qrImg, isJpg); };
        qrImg.onerror = function () { tcFail('Gagal memuat gambar QR Code.'); };
        qrImg.crossOrigin = 'anonymous';
        if (qrSrc.indexOf('data:') === 0) { qrImg.src = qrSrc; }
        else { tcFail('QR Code belum siap. Tutup kartu ini, lalu buka kembali.'); }

        function tcPaint(qrImg, isJpg) {
            // Latar kartu
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, W, H);
            ctx.strokeStyle = '#f1f5f9'; ctx.lineWidth = 1;
            tcRoundRect(ctx, 0.5, 0.5, W - 1, H - 1, 28); ctx.stroke();

            // Badge
            ctx.font = '700 11px system-ui, -apple-system, "Segoe UI", sans-serif';
            const badgeTxt = 'Kartu Presensi Digital GTK';
            const badgeW = ctx.measureText(badgeTxt).width + 26;
            ctx.fillStyle = '#f5f3ff';
            tcRoundRect(ctx, (W - badgeW) / 2, 26, badgeW, 24, 12); ctx.fill();
            ctx.fillStyle = '#6d28d9';
            ctx.fillText(badgeTxt, W / 2, 38.5);

            // Nama (boleh dua baris)
            ctx.fillStyle = '#0f172a';
            ctx.font = '800 17px system-ui, -apple-system, "Segoe UI", sans-serif';
            const nameLines = tcWrap(ctx, meta.nama || 'Guru', W - 48).slice(0, 2);
            nameLines.forEach(function (l, i) { ctx.fillText(l, W / 2, 82 + i * 22); });
            const afterName = 82 + nameLines.length * 22;

            // NIP
            ctx.fillStyle = '#64748b';
            ctx.font = '12px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';
            ctx.fillText('NIP: ' + (meta.nip || '-'), W / 2, afterName + 6);

            // Chip jabatan
            const job = meta.jabatan || 'Guru / GTK';
            ctx.font = '700 10px system-ui, -apple-system, "Segoe UI", sans-serif';
            const jobW = ctx.measureText(job).width + 20;
            ctx.fillStyle = '#ede9fe';
            tcRoundRect(ctx, (W - jobW) / 2, afterName + 20, jobW, 20, 10); ctx.fill();
            ctx.fillStyle = '#5b21b6';
            ctx.fillText(job, W / 2, afterName + 30.5);

            // Panel QR
            const panelY = afterName + 52, panelH = H - panelY - 20;
            ctx.fillStyle = '#f8fafc'; ctx.strokeStyle = '#e2e8f0'; ctx.lineWidth = 1;
            tcRoundRect(ctx, 24, panelY, W - 48, panelH, 20); ctx.fill(); ctx.stroke();

            // Kotak putih QR
            const qrSize = 200, qrX = (W - qrSize) / 2, qrY = panelY + 14;
            ctx.fillStyle = '#ffffff'; ctx.strokeStyle = '#e2e8f0';
            tcRoundRect(ctx, qrX, qrY, qrSize, qrSize, 12); ctx.fill(); ctx.stroke();
            ctx.drawImage(qrImg, qrX + 10, qrY + 10, qrSize - 20, qrSize - 20);

            // Kode & petunjuk
            ctx.fillStyle = '#1e293b';
            ctx.font = '800 12px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';
            ctx.fillText(meta.nip || '-', W / 2, qrY + qrSize + 20);

            ctx.fillStyle = '#7c3aed';
            ctx.font = '600 10px system-ui, -apple-system, "Segoe UI", sans-serif';
            const hint = 'Arahkan QR Code ini ke kamera Kiosk Scanner untuk absensi masuk & pulang';
            tcWrap(ctx, hint, W - 76).slice(0, 3).forEach(function (l, i) {
                ctx.fillText(l, W / 2, qrY + qrSize + 40 + i * 13);
            });

            // Unduh
            const slug = String(meta.nip || 'guru').replace(/[^0-9A-Za-z_-]/g, '');
            const filename = 'Kartu-Presensi-' + (slug || 'GTK') + (isJpg ? '.jpg' : '.png');
            const mime = isJpg ? 'image/jpeg' : 'image/png';

            canvas.toBlob(function (blob) {
                if (!blob) { tcFail('Browser tidak dapat membuat file gambar.'); return; }
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
            }, mime, isJpg ? 0.92 : undefined);
        }
    };

    if (window.jQuery) {
        jQuery(document).on('click', '#btn-teacher-qr', function(e) {
            e.preventDefault();
            window.openTeacherQrModal();
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('teacher-qr-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) window.closeTeacherQrModal();
            });
        }
    });
</script>
<script>
    // Integration logic
    AbsensiApp.init({
        baseUrl: '<?= site_url() ?>',
        assetsUrl: '<?= base_url('assets') ?>',
        faceRegistered: <?= (!empty($face_registered)) ? 'true' : 'false' ?>,
        csrfToken: '<?= csrf_hash() ?>',
        csrfName: '<?= csrf_token() ?>',
        fotoWajib: <?= isset($setting->foto_wajib) ? (int) $setting->foto_wajib : 0 ?>,
        currentMode: '<?= (!$attendance) ? "checkin" : ((!$attendance->jam_pulang) ? "checkout" : "done") ?>',
        isHoliday: <?= ($is_holiday) ? 'true' : 'false' ?>,
        holidayReason: '<?= isset($holiday_reason) ? esc($holiday_reason) : '' ?>',
        locations: <?= json_encode($locations ?? []) ?>,
        userId: '<?= session()->get('user_id') ?>',
        // "Hari ini" versi aplikasi (appTimezone), dipakai penanda
        // "Hari Ini" pada daftar koreksi agar tidak bergantung jam perangkat.
        hariIni: '<?= date('Y-m-d') ?>',
        endpoints: {
            checkin: '/presensi/check-in',
            checkout: '/presensi/check-out',
            myAttendance: '/presensi/my-attendance',
            koreksi: '/presensi/koreksi',
            myCorrections: '/presensi/my-corrections',
            history: '/presensi/history',
            dendaDetail: '/presensi/denda-detail'
        }
    });

    // Muat event QR hari ini → tampilkan kartu "Absen via QR" (sembunyi bila kosong)
    $(document).ready(function() {
        const baseUrl = (AbsensiApp.config.baseUrl || '').replace(/\/+$/, '');
        const escQr = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        $.get(baseUrl + '/presensi/qr-events', function(res) {
            const list = (res && res.status && Array.isArray(res.data)) ? res.data : [];
            if (!list.length) return;
            const html = list.map(function(ev) {
                const jam = escQr((ev.jam_mulai || '').slice(0, 5)) + '–' + escQr((ev.jam_selesai || '').slice(0, 5));
                const lok = ev.lokasi ? ' · ' + escQr(ev.lokasi) : '';
                return '<a href="' + baseUrl + '/qr/' + encodeURIComponent(ev.current_token || '') + '" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 px-3 py-2.5 no-underline hover:border-blue-200 hover:bg-blue-50/50 transition-colors">'
                    + '<span class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base shrink-0"><i class="bi bi-qr-code-scan"></i></span>'
                    + '<span class="min-w-0 flex-1"><span class="block text-[13px] font-extrabold text-slate-800 truncate">' + escQr(ev.nama_acara || 'Acara') + '</span>'
                    + '<span class="block text-[11px] text-slate-400">' + jam + lok + ' · Masuk / Pulang</span></span>'
                    + '<i class="bi bi-chevron-right text-slate-300"></i></a>';
            }).join('');
            $('#qr-event-list').html(html);
            $('#qr-event-card').removeClass('hidden');
        });
    });

    // Cek denda yang belum dibayar saat dashboard absen mobile dimuat
    $(document).ready(function() {
        const baseUrl = (AbsensiApp.config.baseUrl || '').replace(/\/+$/, '');
        $.get(baseUrl + '/presensi/denda-ytd', function(res) {
            if (res.status && res.data && res.data.sisa_denda > 0) {
                // Prevent alert fatigue if dismissed within the same browsing session
                if (sessionStorage.getItem('denda_alert_dismissed_' + res.data.sisa_denda)) {
                    return;
                }

                let sisaDenda = parseInt(res.data.sisa_denda).toLocaleString('id-ID');
                let totalDenda = parseInt(res.data.total_denda || res.data.sisa_denda).toLocaleString('id-ID');
                let terbayar = parseInt(res.data.terbayar || 0).toLocaleString('id-ID');

                Swal.fire({
                    html: `
                        <div class="px-1 py-2 text-center">
                            <!-- Glowing Icon Header -->
                            <div class="mb-3 inline-flex items-center justify-center" 
                                 style="width: 64px; height: 64px; background: #fee2e2; color: #dc2626; border-radius: 22px; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);">
                                <i class="bi bi-receipt-cutoff" style="font-size: 30px;"></i>
                            </div>

                            <h5 class="font-bold text-slate-800 mb-1" style="font-size: 18px; letter-spacing: -0.3px;">Pemberitahuan Denda</h5>
                            <p class="text-slate-400 text-sm mb-3">Terdapat akumulasi denda absensi yang perlu diselesaikan.</p>

                            <!-- Fine Card Box -->
                            <div class="p-3 mb-3 text-left"
                                 style="background: #fff5f5; border: 1.5px dashed #fca5a5; border-radius: 18px;">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-slate-400" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Sisa Denda Belum Lunas</span>
                                    <span class="badge-tw badge-danger text-white rounded-full px-2 py-0" style="font-size: 9.5px;">Belum Lunas</span>
                                </div>
                                <div class="font-bold text-red-600 my-1" style="font-size: 24px; letter-spacing: -0.5px;">Rp ${sisaDenda}</div>
                                <div class="flex justify-between text-slate-400 pt-2 border-t" style="border-color: #fee2e2 !important; font-size: 11px;">
                                    <span>Total: <strong>Rp ${totalDenda}</strong></span>
                                    <span>Terbayar/Voucher: <strong class="text-emerald-600">Rp ${terbayar}</strong></span>
                                </div>
                            </div>

                            <div class="border-0 py-2 px-3 mb-1 text-left flex items-center gap-2 rounded-xl bg-slate-50" style="background: #f8fafc;">
                                <i class="bi bi-info-circle-fill text-brand-600 flex-shrink-0" style="font-size: 14px;"></i>
                                <span class="text-slate-400" style="font-size: 11px; line-height: 1.35;">
                                    Gunakan poin reward untuk memotong denda atau selesaikan via administrasi sekolah.
                                </span>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-wallet2 me-1.5"></i> Lihat Rincian & Bayar',
                    cancelButtonText: 'Nanti Saja',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-3xl border-0 shadow-lg px-2 py-3',
                        confirmButton: 'btn-tw btn-danger w-full py-2.5 rounded-full font-bold shadow-sm mb-2 order-1',
                        cancelButton: 'btn-tw btn-light w-full py-2 rounded-full text-slate-400 font-semibold order-2',
                        actions: 'flex flex-col w-full px-3 mt-1 gap-1'
                    },
                    focusConfirm: false,
                    backdrop: 'rgba(15, 23, 42, 0.65)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = baseUrl + '/presensi/rekap-denda';
                    } else {
                        sessionStorage.setItem('denda_alert_dismissed_' + res.data.sisa_denda, '1');
                    }
                });
            }
        }).fail(function() {
            console.error('Gagal memuat status denda YTD untuk notifikasi');
        });
    });

    // Clock Logic
    function updateClock() {
        const now = witaNow();
        const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
        const dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        $('#digitalClock').text(timeStr);
        $('#digitalDate').text(dateStr);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Toggle Extra Menu
    function toggleExtraMenu() {
        const container = $('#extraMenuContainer');
        const btn = $('#btnToggleMenu');
        const text = $('#toggleMenuText');

        container.slideToggle(220, function() {
            const isVisible = container.is(':visible');
            btn.attr('aria-expanded', isVisible ? 'true' : 'false');
            if (isVisible) {
                btn.addClass('active');
                text.text('Tutup Menu');
            } else {
                btn.removeClass('active');
                text.text('Menu Lainnya');
            }
        });
    }

    // Pull to Refresh logic (Minimal implementation)
    let startY = 0;
    const ptrElement = document.getElementById('ptr-element');
    ptrElement.addEventListener('touchstart', e => {
        if (window.scrollY === 0) startY = e.touches[0].pageY;
    }, {passive: true});
    
    ptrElement.addEventListener('touchmove', e => {
        const y = e.touches[0].pageY;
        if (window.scrollY === 0 && y > startY + 50) {
            // Simulated pull effect
        }
    }, {passive: true});
    
    ptrElement.addEventListener('touchend', e => {
        const y = e.changedTouches[0].pageY;
        // Jangan reload saat overlay kamera terbuka (swipe di dalam kamera)
        const cameraOpen = !document.getElementById('cameraModal').classList.contains('hidden');
        if (window.scrollY === 0 && y > startY + 100 && !cameraOpen) {
            haptic();
            location.reload();
        }
    }, {passive: true});

    // Perpindahan tab: dilakukan sinkron, animasi masuk memakai .fade-in-up
    // dari Tailwind. Dulu memakai fadeOut().promise().done() — kalau guru
    // mengetuk nav dengan cepat, dua callback bisa balapan dan meninggalkan
    // dua tab (atau justru tidak ada) yang terlihat.
    window.showTab = function(tab) {
        const targetId = (tab === 'home' || tab === 'timeline') ? 'content-timeline' : 'content-' + tab;
        const target = document.getElementById(targetId);
        if (!target) return;

        const baruDibuka = target.classList.contains('hidden');

        if (baruDibuka) {
            document.querySelectorAll('.tab-content-tw').forEach(function(pane) {
                pane.classList.add('hidden');
                pane.classList.remove('fade-in-up');
            });
            target.classList.remove('hidden');
            // Reflow paksa: tanpa ini kelas animasi yang sama dianggap tidak
            // berubah, jadi animasinya cuma jalan sekali.
            void target.offsetWidth;
            target.classList.add('fade-in-up');

            // Data dimuat saat tab benar-benar dibuka, bukan tiap ketukan.
            if (typeof AbsensiApp !== 'undefined') {
                if (tab === 'history') AbsensiApp.loadHistory();
                if (tab === 'koreksi') AbsensiApp.loadKoreksiData();
            }
            if (tab === 'ranking' && typeof loadRankingData === 'function') {
                loadRankingData();
            }
        }

        // Ketuk ulang tab yang sedang aktif tetap mengembalikan posisi lihat,
        // sama seperti sebelumnya.
        if (!baruDibuka && (tab === 'home' || tab === 'timeline')) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else if (baruDibuka) {
            if (tab !== 'home' && tab !== 'timeline') {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // Update Nav UI
        $('.nav-item').removeClass('active');
        if (tab === 'home' || tab === 'timeline') {
            $('.bottom-nav .nav-item').first().addClass('active');
        } else {
            const $navMatch = $(`.bottom-nav .nav-item[data-tab="${tab}"]`);
            if ($navMatch.length) {
                $navMatch.addClass('active');
            } else {
                // Tab izin/koreksi/statistik masih bagian dari menu Absen
                $('#nav-btn-absen').addClass('active');
            }
        }

        // Update Action Grid active state
        $('.action-item').removeClass('active');
        $(`.action-item[data-tab="${tab}"]`).addClass('active');
    };

    // Tombol grid ber-data-tab (Izin/Sakit, Koreksi, Statistik) membuka tab terkait
    if (window.jQuery) {
        jQuery(document).off('click', '.action-item[data-tab]');
        jQuery(document).on('click', '.action-item[data-tab]', function (e) {
            e.preventDefault();
            const menu = document.getElementById('extraMenuContainer');
            if (menu && menu.style.display !== 'none') toggleExtraMenu();
            const tab = jQuery(this).attr('data-tab');
            if (tab && typeof window.showTab === 'function') window.showTab(tab);
        });
    }

    // Ranking full logic
    let rankingDataGlobal = [];
    const fotoCadangan = '<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>';

    /** Nama/username disisipkan lewat .html() — wajib di-escape dulu. */
    function teks(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    /** Tanpa ini, permintaan yang gagal membuat daftar membeku pada spinner. */
    function renderRankingGagal() {
        $('#ranking-list-full').html('<div class="text-center py-6 text-slate-400">'
            + '<i class="bi bi-cloud-slash text-3xl mb-2 block"></i>'
            + '<div class="text-[13px]">Gagal memuat leaderboard kedisiplinan.</div>'
            + '<button type="button" class="mt-1.5 text-[13px] font-bold text-blue-600 underline" data-muat-ranking>Coba lagi</button>'
            + '</div>');
    }

    function loadRankingData() {
        $('#ranking-list-full').html('<div class="text-center py-5"><div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600"></div></div>');
        const baseUrl = (AbsensiApp.config.baseUrl || '').replace(/\/$/, '');
        $.get(baseUrl + '/presensi/ranking', function(res) {
            if (!res || !res.status || !Array.isArray(res.data)) {
                renderRankingGagal();
                return;
            }
            rankingDataGlobal = res.data;
            if (!res.data.length) {
                $('#ranking-list-full').html('<div class="text-center py-6 text-[13px] text-slate-400">Belum ada data kedisiplinan bulan ini.</div>');
                return;
            }
            renderRankingList(res.data);
        }).fail(renderRankingGagal);
    }

    jQuery(document).on('click', '[data-muat-ranking]', function (e) {
        e.preventDefault();
        loadRankingData();
    });

    function renderRankingList(data) {
        let html = '';
        data.forEach((r, idx) => {
            const rank = idx + 1;
            const name = r.nama;
            const avatar = r.avatar;

            let badgeClass = '';
            if (rank === 1) badgeClass = 'ring-2 ring-amber-400';
            else if (rank === 2) badgeClass = 'ring-2 ring-slate-300';
            else if (rank === 3) badgeClass = 'ring-2 ring-orange-400';

            html += `
            <div class="activity-card m3-card-elevated mb-3 ${badgeClass}" onclick="showRankingDetail(${idx})" style="cursor:pointer">
                <div class="relative me-3 shrink-0">
                    <img src="${avatar}" alt="${teks(name)}" class="foto-orang shadow-sm" onerror="this.onerror=null;this.src='${fotoCadangan}'">
                    <div class="absolute bottom-0 end-0 bg-white rounded-full shadow-sm flex items-center justify-center" style="width: 20px; height: 20px; margin-bottom: -5px; margin-right: -5px;">
                        <span class="font-bold text-slate-800" style="font-size: 10px;">${rank}</span>
                    </div>
                </div>
                <div class="grow min-w-0">
                    <div class="flex justify-between items-center gap-2">
                        <div class="min-w-0 pe-2">
                            <div class="font-bold text-slate-800 truncate" style="font-size: 13px;">${teks(name)}</div>
                            <div class="text-slate-400 truncate" style="font-size: 10px;">@${teks(r.username)}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-bold text-brand-600" style="font-size: 15px;">${parseFloat(r.score).toFixed(1)}</div>
                            <div class="text-slate-400" style="font-size: 9.5px; letter-spacing: .3px; text-transform: uppercase;">SKOR</div>
                            ${parseFloat(r.total_denda) > 0 ? `<button type="button" class="score-denda" onclick="event.stopPropagation(); window.showDendaDetail(${parseInt(r.user_id || 0)}, '<?= date('Y-m') ?>')">Rp${parseInt(r.total_denda).toLocaleString('id-ID')}</button>` : ''}
                        </div>
                    </div>
                </div>
                <i class="bi bi-chevron-right ms-2 opacity-25"></i>
            </div>`;
        });
        $('#ranking-list-full').html(html);
    }

    window.showRankingDetail = function(idx) {
        const r = rankingDataGlobal[idx];
        if (!r) return;

        const name = r.nama;
        const avatar = r.avatar;

        $('#rd-name').text(name);
        $('#rd-username').text('@' + r.username);
        $('#rd-score').text(parseFloat(r.score).toFixed(1));
        $('#rd-hadir').text(r.total_hadir || 0);
        
        const totalHadir = parseInt(r.total_hadir || 0);
        const hadirDinas = parseInt(r.hadir_dinas || 0);
        const hadirReguler = (r.hadir_reguler !== undefined) ? parseInt(r.hadir_reguler || 0) : Math.max(0, totalHadir - hadirDinas);
        $('#rd-hadir-reguler').text(hadirReguler);
        $('#rd-hadir-dinas').text(hadirDinas);

        $('#rd-ontime').text(r.ontime);
        $('#rd-late').text(r.late);
        $('#rd-menit').text(r.total_telat_menit);
        $('#rd-awal').text(r.total_datang_awal);
        $('#rd-akhir').text(r.total_pulang_akhir);
        $('#rd-denda-bulan-ini').text(parseInt(r.total_denda || 0).toLocaleString('id-ID'));
        
        // Instant populate from ranking payload
        const initTotalYtd = parseInt(r.total_denda_ytd !== undefined ? r.total_denda_ytd : 0);
        const initDibayar = parseInt(r.total_dibayar_ytd !== undefined ? r.total_dibayar_ytd : 0);
        const initCash = parseInt(r.dibayar_cash_ytd !== undefined ? r.dibayar_cash_ytd : 0);
        const initPoin = parseInt(r.dibayar_poin_ytd !== undefined ? r.dibayar_poin_ytd : 0);
        const initSaldo = parseInt(r.dibayar_saldo_ytd !== undefined ? r.dibayar_saldo_ytd : 0);
        const initSisa = parseInt(r.sisa_denda_ytd !== undefined ? r.sisa_denda_ytd : Math.max(0, initTotalYtd - initDibayar));

        $('#rd-denda-total').text(initTotalYtd.toLocaleString('id-ID'));
        $('#rd-denda-dibayar').text(initDibayar.toLocaleString('id-ID'));
        $('#rd-denda-cash').text(initCash.toLocaleString('id-ID'));
        $('#rd-denda-poin').text(initPoin.toLocaleString('id-ID'));
        $('#rd-denda-saldo').text(initSaldo.toLocaleString('id-ID'));
        if (initSaldo > 0) {
            $('#box-rd-denda-saldo').show();
        } else {
            $('#box-rd-denda-saldo').hide();
        }
        $('#rd-denda').text(initSisa.toLocaleString('id-ID'));
        
        const baseUrl = (AbsensiApp.config.baseUrl || '').replace(/\/$/, '');
        $.get(baseUrl + '/presensi/denda-ytd?user_id=' + r.user_id, function(res) {
            if (res.status && res.data) {
                if (res.data.denda_bulan_ini !== undefined) {
                    $('#rd-denda-bulan-ini').text(parseInt(res.data.denda_bulan_ini || 0).toLocaleString('id-ID'));
                }
                $('#rd-denda-total').text(parseInt(res.data.total_denda_ytd || 0).toLocaleString('id-ID'));
                $('#rd-denda-dibayar').text(parseInt(res.data.total_dibayar || 0).toLocaleString('id-ID'));
                $('#rd-denda-cash').text(parseInt(res.data.dibayar_cash || 0).toLocaleString('id-ID'));
                $('#rd-denda-poin').text(parseInt(res.data.dibayar_poin || 0).toLocaleString('id-ID'));
                const sld = parseInt(res.data.dibayar_saldo || 0);
                $('#rd-denda-saldo').text(sld.toLocaleString('id-ID'));
                if (sld > 0) {
                    $('#box-rd-denda-saldo').show();
                } else {
                    $('#box-rd-denda-saldo').hide();
                }
                $('#rd-denda').text(parseInt(res.data.sisa_denda || 0).toLocaleString('id-ID'));
            }
        });

        $('#rd-denda-btn').off('click').on('click', function(e) {
            e.preventDefault();
            window.showDendaDetail(parseInt(r.user_id || 0), '<?= date('Y-m') ?>');
        });
        $('#rd-avatar-container').html(`<img src="${avatar}" alt="${teks(name)}" class="foto-orang foto-orang-lg shadow-sm" onerror="this.onerror=null;this.src='${fotoCadangan}'">`);

        twModalShow('#rankingDetailModal');
    };

    // Detail Denda Modal via SweetAlert2
    window.showDendaDetail = function(userId, month) {
        month = month || '<?= date('Y-m') ?>';
        if (!userId) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'ID pengguna tidak valid.',
                confirmButtonColor: '#e11d48'
            });
            return;
        }

        Swal.fire({
            title: 'Memuat data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.getJSON('<?= site_url('presensi/denda-detail') ?>', { user_id: userId, month: month })
            .done(function(res) {
                if (!res.status || !res.data) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Detail Denda',
                        text: (res && res.message) ? res.message : 'Tidak ada data denda ditemukan.',
                        confirmButtonColor: '#e11d48'
                    });
                    return;
                }

                const data = res.data;
                const items = data.items || [];
                let itemsHtml = '';

                if (items.length === 0) {
                    itemsHtml = '<div class="text-center py-4 text-slate-400 text-sm">Tidak ada rincian denda pada bulan ini.</div>';
                } else {
                    itemsHtml = '<div class="space-y-2 text-left" style="max-height: 240px; overflow-y: auto;">';
                    items.forEach(function(item) {
                        const tglFmt = item.tgl ? new Date(item.tgl).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-';
                        const dendaFmt = 'Rp' + parseInt(item.denda || 0).toLocaleString('id-ID');
                        let desc = item.keterangan_denda || '';
                        if (!desc && item.telat_menit > 0) desc = 'Terlambat ' + item.telat_menit + ' menit';
                        if (!desc && item.pulang_cepat_menit > 0) desc = 'Pulang cepat ' + item.pulang_cepat_menit + ' menit';
                        if (!desc) desc = 'Denda kehadiran';

                        itemsHtml += `
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                <div>
                                    <div class="font-bold text-slate-700">${tglFmt}</div>
                                    <div class="text-[11px] text-slate-500">${desc}</div>
                                </div>
                                <div class="font-bold text-rose-600">${dendaFmt}</div>
                            </div>`;
                    });
                    itemsHtml += '</div>';
                }

                const totalFmt = 'Rp' + parseInt(data.total_denda || 0).toLocaleString('id-ID');
                const contentHtml = `
                    <div class="text-left mb-3">
                        <div class="text-xs text-slate-500">Guru: <span class="font-semibold text-slate-700">${data.display_name || 'Guru'}</span></div>
                        <div class="text-xs text-slate-500">Periode: <span class="font-semibold text-slate-700">${data.month}</span></div>
                        <div class="mt-2 p-2.5 rounded-xl bg-rose-50 border border-rose-100 flex justify-between items-center">
                            <span class="text-xs font-semibold text-rose-800">Total Denda:</span>
                            <span class="text-sm font-bold text-rose-600">${totalFmt}</span>
                        </div>
                    </div>
                    ${itemsHtml}
                `;

                Swal.fire({
                    title: 'Rincian Denda',
                    html: contentHtml,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#e11d48',
                    customClass: {
                        popup: 'rounded-2xl',
                        confirmButton: 'rounded-xl px-5 py-2 font-medium text-sm'
                    }
                });
            })
            .fail(function(xhr) {
                let msg = 'Gagal memuat detail denda.';
                try {
                    const err = JSON.parse(xhr.responseText);
                    if (err.message) msg = err.message;
                } catch(e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                    confirmButtonColor: '#e11d48'
                });
            });
    };

    /* ═══════════════════════════════════════════════════════════
       FaceScan — modal biometrik (diport dari public/ui_mobile.html),
       disambungkan ke endpoint asli /presensi/check-in & /check-out.
       Alur: GPS+geofence → liveness/face → submit + face_descriptor.
       ═══════════════════════════════════════════════════════════ */
    window.FaceScan = (function () {
        const BASE = '<?= rtrim(site_url(), '/') ?>';
        const CSRF_NAME = '<?= csrf_token() ?>';
        let CSRF_HASH = '<?= csrf_hash() ?>';

        // Token CSRF menempel di HTML; halaman PWA yang lama terbuka atau
        // disajikan service worker bisa membawa token kedaluwarsa. Segarkan
        // saat aplikasi kembali aktif dan sebelum submit (maks. tiap 15 menit)
        // agar sesi ikut diperpanjang dan submit tidak berakhir 403.
        let LAST_CSRF_REFRESH = 0;
        let CSRF_REFRESHING = null;
        async function refreshCsrf(force) {
            // Offline: jangan tunda submit, antrean offline yang menangani.
            if (typeof navigator !== 'undefined' && navigator.onLine === false) return false;
            if (!force && Date.now() - LAST_CSRF_REFRESH < 15 * 60 * 1000) return true;
            // Satu permintaan saja: dua refresh paralel bisa menghasilkan
            // hash berbeda (cookie baru) dan membuat submit berikutnya 403.
            if (CSRF_REFRESHING) return CSRF_REFRESHING;
            const task = (async () => {
                try {
                    const ctrl = new AbortController();
                    const timer = setTimeout(() => ctrl.abort(), 6000);
                    const res = await fetch(BASE + '/presensi/csrf-refresh?_=' + Date.now(), {
                        credentials: 'same-origin', cache: 'no-store', signal: ctrl.signal
                    });
                    clearTimeout(timer);
                    // Redirect = sesi habis dan diarahkan ke login; jangan
                    // anggap token baru valid.
                    if (!res.ok || res.redirected) return false;
                    const segar = await res.json();
                    if (!segar || !segar.status || !segar.csrfHash) return false;
                    CSRF_HASH = segar.csrfHash;
                    LAST_CSRF_REFRESH = Date.now();
                    if (window.AbsensiApp && AbsensiApp.config) {
                        AbsensiApp.config.csrfToken = segar.csrfHash;
                        if (segar.csrfName) AbsensiApp.config.csrfName = segar.csrfName;
                    }
                    return true;
                } catch (e) {
                    return false;
                }
            })();
            CSRF_REFRESHING = task;
            try {
                return await task;
            } finally {
                if (CSRF_REFRESHING === task) CSRF_REFRESHING = null;
            }
        }
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') refreshCsrf(false);
        });
        window.addEventListener('pageshow', function (e) { if (e.persisted) refreshCsrf(false); });
        window.addEventListener('online', function () { refreshCsrf(true); });
        // Dipakai jalur submit lama (AbsensiApp/handleScanClick) yang punya
        // penanganan sendiri di public/assets/js/absensi-mobile.js.
        window.presensiRefreshCsrf = refreshCsrf;

        const TARGET0 = '<?= (!$attendance ? 'MASUK' : ((!empty($attendance->jam_pulang) && $attendance->jam_pulang !== '0000-00-00 00:00:00') ? 'DONE' : 'PULANG')) ?>';
        const FOTO_WAJIB = <?= isset($setting->foto_wajib) ? (int) $setting->foto_wajib : 0 ?>;
        const IS_HOLIDAY = <?= ($is_holiday) ? 'true' : 'false' ?>;
        const HOLIDAY_REASON = '<?= isset($holiday_reason) ? esc($holiday_reason, 'js') : '' ?>';
        const LOCATIONS = <?= json_encode($locations ?? []) ?>;
        const MODEL_LOCAL = '<?= base_url('assets/models') ?>';
        const MODEL_CDN = 'https://justadudewhohacks.github.io/face-api.js/models';
        const TEGURAN_FRASA = 'ya, saya akan meningkatkan disiplin';

        const S = {
            stream: null, detectTimer: null, target: 'MASUK',
            lat: null, lng: null, acc: 0, inRange: false,
            locName: '', locDist: 0, foto: null, descriptor: null,
            busy: false, modelsLoaded: false, stable: 0,
            dwellTimer: null, sudahMuatUlang: false
        };

        const $ = (id) => document.getElementById(id);

        // ── Suara notifikasi ─────────────────────────────────────────────
        // beep() lama membuat AudioContext BARU tiap panggilan dan memainkan
        // satu nada 880 Hz — sama untuk "tahap dimulai" dan untuk "berhasil",
        // jadi absen masuk, absen pulang, dan keberhasilan terdengar identik,
        // dan browser (Safari/iOS) sering memblokir context yang dibuat di
        // luar gesture pengguna. Sekarang: satu context dipakai bersama, dibuka
        // pada sentuhan pertama, dan tiap hasil punya nadanya sendiri.
        const SND_KEY = 'presensi_guru_sound';
        let sndOn = true;
        try { sndOn = localStorage.getItem(SND_KEY) !== '0'; } catch (e) { sndOn = true; }
        let actx = null;

        function getActx() {
            try {
                const C = window.AudioContext || window.webkitAudioContext;
                if (!C) return null;
                if (!actx) actx = new C();
                if (actx.state === 'suspended') actx.resume().catch(() => {});
                return actx;
            } catch (e) { return null; }
        }
        // Safari tidak mengizinkan audio mulai tanpa gesture; buka di tap pertama.
        // Mesin TTS punya aturan sendiri: di iOS Safari, speak() yang dipanggil
        // dari setTimeout (hasil presensi datang dari POST async, bukan dari
        // sentuhan) dibisukan SELAMANYA kecuali engine pernah dipakai di dalam
        // gesture. Maka satu sentuhan pertama dipakai untuk membuka keduanya.
        let ttsTerbuka = false;
        function siapTts() {
            if (ttsTerbuka || !('speechSynthesis' in window)
                || typeof window.SpeechSynthesisUtterance !== 'function') return;
            try {
                window.speechSynthesis.getVoices();
                const u = new SpeechSynthesisUtterance(' ');
                u.volume = 0;
                window.speechSynthesis.speak(u);
                ttsTerbuka = true;
            } catch (e) {}
        }
        function bukaAudio() {
            getActx();
            siapTts();
        }
        document.addEventListener('touchstart', bukaAudio, { once: true, passive: true });
        document.addEventListener('click', bukaAudio, { once: true, passive: true });

        function nada(ctx, freq, mulai, durus, tipe, volume, frekuensiAkhir) {
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.type = tipe || 'sine';
            o.frequency.setValueAtTime(freq, mulai);
            if (frekuensiAkhir) o.frequency.exponentialRampToValueAtTime(frekuensiAkhir, mulai + durus);
            g.gain.setValueAtTime(volume, mulai);
            g.gain.exponentialRampToValueAtTime(0.001, mulai + durus);
            o.connect(g); g.connect(ctx.destination);
            o.start(mulai); o.stop(mulai + durus);
            return o;
        }

        // tipe: 'masuk' | 'pulang' | 'catatan' | 'gagal' | 'peringatan' | 'tik'
        function bunyi(tipe) {
            if (!sndOn) return;
            const ctx = getActx();
            if (!ctx) return;
            try {
                const t = ctx.currentTime;
                if (tipe === 'masuk') {
                    // Menaik: presensi masuk tercatat
                    [659.25, 830.61, 987.77].forEach((f, i) => {
                        const m = t + i * 0.08;
                        nada(ctx, f, m, i === 2 ? 0.55 : 0.22, 'sine', 0.2);
                        nada(ctx, f * 2, m, 0.3, 'triangle', 0.06);
                    });
                } else if (tipe === 'pulang') {
                    // Menurun: hari selesai
                    [987.77, 830.61, 659.25].forEach((f, i) => {
                        nada(ctx, f, t + i * 0.09, i === 2 ? 0.5 : 0.2, 'sine', 0.18);
                    });
                } else if (tipe === 'catatan') {
                    // Nada pelan sesudah 'masuk' bila terlambat/pulang cepat.
                    nada(ctx, 523.25, t, 0.16, 'triangle', 0.12);
                    nada(ctx, 440, t + 0.18, 0.22, 'triangle', 0.12);
                } else if (tipe === 'gagal') {
                    nada(ctx, 220, t, 0.28, 'sawtooth', 0.2, 130);
                } else if (tipe === 'peringatan') {
                    nada(ctx, 440, t, 0.16, 'triangle', 0.16);
                    nada(ctx, 349.23, t + 0.14, 0.2, 'triangle', 0.16);
                } else {
                    // 'tik' — penanda pergantian tahap, sengaja sangat pelan agar
                    // tidak lagi menyaingi nada keberhasilan.
                    nada(ctx, 660, t, 0.05, 'sine', 0.045);
                }
            } catch (e) {}
        }

        // ── Ucapan hasil presensi ────────────────────────────────────────
        // Nada bel saja masih ambigu (masuk dan pulang hanya beda arah nada),
        // jadi hasilnya dibacakan: "Absen Masuk Berhasil" / "Absen Pulang
        // Berhasil". Pembacaan mengikuti tombol suara — bila dibisukan, tidak
        // ada ucapan sama sekali.
        let suaraId = null;
        function pilihSuaraId() {
            try {
                const daftar = window.speechSynthesis.getVoices() || [];
                if (!daftar.length) return null;
                return daftar.find(v => /^id([-_]|$)/i.test(v.lang || ''))
                    || daftar.find(v => /indonesia|bahasa/i.test(v.name || ''))
                    || null;
            } catch (e) { return null; }
        }
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.onvoiceschanged = function () { suaraId = pilihSuaraId(); };
                suaraId = pilihSuaraId();
            } catch (e) {}
        }
        function ucapHasil(teks, tunda) {
            if (!sndOn || !('speechSynthesis' in window) || typeof window.SpeechSynthesisUtterance !== 'function') return;
            teks = String(teks || '').trim();
            if (!teks) return;
            const mainkan = function () {
                if (!sndOn) return;
                try {
                    siapTts();
                    window.speechSynthesis.cancel();
                    const u = new SpeechSynthesisUtterance(teks);
                    u.lang = 'id-ID';
                    u.rate = 0.95;
                    u.pitch = 1;
                    u.volume = 1;
                    if (!suaraId) suaraId = pilihSuaraId();
                    if (suaraId) u.voice = suaraId;
                    let sudahMulai = false;
                    try { u.onstart = function () { sudahMulai = true; }; } catch (e) {}
                    // Simpan referensi: Chrome Android kadang menghentikan
                    // utterance yang tidak lagi dipegang variabel mana pun.
                    window._facescanUtterance = u;
                    window.speechSynthesis.speak(u);
                    // Di beberapa Chrome Android, speak() yang mengikuti
                    // cancel() hilang tanpa error. Bila 400 ms kemudian
                    // ucapan belum pernah mulai, ucapkan sekali lagi.
                    // Penanda di event onstart (bukan flag speaking) dipilih
                    // karena flag itu bisa tetap false walau ucapan berjalan,
                    // dan bisa pula sudah false lagi walau ucapannya normal.
                    let sudahUlang = false;
                    setTimeout(function () {
                        try {
                            if (sudahUlang || sudahMulai || !sndOn) return;
                            sudahUlang = true;
                            window.speechSynthesis.speak(u);
                        } catch (e) {}
                    }, 400);
                } catch (e) {}
            };
            if (tunda) setTimeout(mainkan, tunda); else mainkan();
        }

        function sinkronTombolSuara() {
            const b = $('facescan-sound');
            if (!b) return;
            b.setAttribute('aria-pressed', sndOn ? 'true' : 'false');
            b.setAttribute('aria-label', sndOn ? 'Suara notifikasi aktif' : 'Suara notifikasi dimatikan');
            b.title = sndOn ? 'Suara notifikasi aktif — ketuk untuk membisukan' : 'Suara dibisukan — ketuk untuk mengaktifkan';
            const i = b.querySelector('i');
            if (i) i.className = 'bi ' + (sndOn ? 'bi-volume-up-fill' : 'bi-volume-mute-fill');
        }
        function toggleSuara() {
            sndOn = !sndOn;
            try { localStorage.setItem(SND_KEY, sndOn ? '1' : '0'); } catch (e) {}
            sinkronTombolSuara();
            if (sndOn) bunyi('tik');
            else { try { window.speechSynthesis.cancel(); } catch (e) {} }
        }
        function setProgress(p) {
            p = Math.max(0, Math.min(100, Math.round(p)));
            $('facescan-progress-bar').style.width = p + '%';
            $('facescan-progress-pct').textContent = p + '%';
            const bar = $('facescan-progress');
            if (bar) bar.setAttribute('aria-valuenow', String(p));
        }
        function setStep(t) { $('facescan-step-text').textContent = t; }
        function setHint(t) { $('facescan-explainer').textContent = t; }
        function setStatus(t, ok) {
            $('facescan-status-label').textContent = t;
            $('facescan-status-icon').className = ok === true
                ? 'bi bi-check-circle-fill text-emerald-500'
                : (ok === false
                    ? 'bi bi-exclamation-circle-fill text-rose-500'
                    : 'bi bi-arrow-repeat animate-spin text-slate-400');
        }
        function setViewportState(state, title, sub) {
            const m = $('facescan-modal');
            if (!m) return;
            m.classList.remove('is-idle', 'is-scanning', 'is-error', 'is-warn', 'is-info');
            m.classList.add('is-' + state);
            if (title !== undefined) $('facescan-fallback-title').textContent = title;
            if (sub !== undefined) $('facescan-fallback-sub').textContent = sub;
        }
        const ERROR_TITLES = {
            CAMERA_DENIED: 'Kamera tidak bisa diakses',
            NO_FACE: 'Wajah belum terdeteksi',
            NO_GPS: 'GPS belum aktif',
            NO_LOC: 'Lokasi belum diatur',
            OUT_OF_RANGE: 'Di luar area sekolah',
            NO_MODEL: 'AI wajah gagal dimuat',
            SESI: 'Sesi berakhir',
            SERVER: 'Presensi belum tersimpan',
            INPUT: 'Data absen belum lengkap',
            FACE_MISMATCH: 'Wajah tidak cocok',
            FACE_PROFILE: 'Data wajah belum ada',
            GPS_MOCK: 'Lokasi dicurigai palsu',
            SUDAH_MASUK: 'Sudah absen masuk',
            SUDAH_PULANG: 'Sudah absen pulang',
            BELUM_MASUK: 'Belum absen masuk',
            BELUM_WAKTU: 'Belum waktunya absen',
            LEWAT_WAKTU: 'Batas waktu absen lewat',
            LIBUR: 'Hari libur',
            THROTTLE: 'Terlalu sering mencoba',
            MODE_DITOLAK: 'Mode Dinas/WFH ditolak',
            JURNAL: 'Jurnal belum lengkap',
            QUEUED: 'Tersimpan di perangkat',
            BATAL: 'Absen dibatalkan',
            TEGURAN: 'Belum dikonfirmasi',
            ABORTED: 'Pemindaian dihentikan'
        };
        const ERROR_HINTS = {
            CAMERA_DENIED: 'Izinkan kamera lewat ikon gembok di address bar, lalu tekan Pindai Ulang.',
            NO_FACE: 'Cari tempat lebih terang dan hadapkan wajah ke kamera.',
            NO_GPS: 'Aktifkan GPS dan izin lokasi, lalu coba lagi.',
            NO_LOC: 'Minta admin mengatur titik lokasi sekolah.',
            OUT_OF_RANGE: 'Dekati area sekolah, atau gunakan Mode Dinas / WFH bila bertugas di luar.',
            NO_MODEL: 'Periksa koneksi internet lalu tekan Pindai Ulang.',
            SESI: 'Muat ulang halaman lalu login kembali.',
            SERVER: 'Pemindaian wajah Anda berhasil — server yang menolak. Baca alasannya di atas.',
            INPUT: 'Buka "Opsi dinas / WFH" di halaman utama, lengkapi isian, lalu ulangi.',
            FACE_MISMATCH: 'Coba lagi di tempat terang, menghadap kamera, tanpa masker/kacamata hitam.',
            FACE_PROFILE: 'Buka menu Profil, daftarkan foto wajah Anda, lalu absen lagi.',
            GPS_MOCK: 'Matikan aplikasi lokasi palsu, gunakan GPS asli, lalu ulangi.',
            SUDAH_MASUK: 'Tidak perlu absen dua kali. Tekan Selesai untuk melihat status hari ini.',
            SUDAH_PULANG: 'Absen pulang hari ini sudah tercatat. Tidak perlu diulang.',
            BELUM_MASUK: 'Lakukan absen masuk terlebih dahulu, baru absen pulang.',
            BELUM_WAKTU: 'Tunggu sampai jendela absen dibuka, lalu pindai lagi.',
            LEWAT_WAKTU: 'Lewat batas waktu — absen perlu dibantu admin. Hubungi operator.',
            LIBUR: 'Hari ini libur. Absen hanya bila Anda memang bertugas.',
            THROTTLE: 'Tunggu sekitar satu menit, lalu tekan Pindai Ulang.',
            MODE_DITOLAK: 'Ubah pilihan Mode Dinas / WFH, atau hubungi admin.',
            JURNAL: 'Isi jurnal sesi yang sudah dimulai, lalu ulangi absen pulang.',
            QUEUED: 'Presensi akan terkirim otomatis saat online.',
            BATAL: 'Tekan tombol Absen lagi bila ingin melanjutkan.',
            TEGURAN: 'Konfirmasi surat teguran untuk melanjutkan absen.',
            ABORTED: 'Tekan Pindai Ulang untuk memulai lagi.'
        };
        // Sub-teks viewport per kode. Default "Silakan coba lagi." menyesatkan
        // untuk penolakan server (jam absen belum masuk, dsb.) — mencoba ulang
        // tidak menyelesaikannya, dan pemindaian wajahnya justru sukses.
        const ERROR_SUBS = {
            CAMERA_DENIED: 'Izinkan kamera lewat pengaturan browser, lalu coba lagi.',
            NO_FACE: 'Cari tempat lebih terang, lalu pindai ulang.',
            NO_GPS: 'Aktifkan GPS dan izin lokasi, lalu coba lagi.',
            NO_LOC: 'Minta admin mengatur titik lokasi sekolah.',
            OUT_OF_RANGE: 'Gunakan Mode Dinas / WFH bila bertugas di luar.',
            NO_MODEL: 'Periksa koneksi, lalu pindai ulang.',
            SERVER: 'Pemindaian berhasil — lihat alasannya di bawah.',
            SESI: 'Anda perlu login ulang untuk melanjutkan.',
            INPUT: 'Lengkapi data yang diminta, lalu ulangi absen.',
            FACE_MISMATCH: 'Pemindaian selesai, tapi hasilnya tidak cocok.',
            FACE_PROFILE: 'Belum ada wajah yang bisa dibandingkan.',
            GPS_MOCK: 'Sistem menolak koordinat dari lokasi palsu.',
            SUDAH_MASUK: 'Catatan Anda sudah ada, jadi tidak ditimpa.',
            SUDAH_PULANG: 'Catatan Anda sudah lengkap hari ini.',
            BELUM_MASUK: 'Urutannya belum pas, bukan wajahnya yang gagal.',
            BELUM_WAKTU: 'Wajahnya berhasil — waktunya yang belum pas.',
            LEWAT_WAKTU: 'Wajahnya berhasil — waktunya sudah lewat.',
            LIBUR: 'Silakan lanjut bila Anda memang bertugas.',
            THROTTLE: 'Tunggu sebentar, lalu coba lagi.',
            MODE_DITOLAK: 'Pilih mode yang diizinkan sekolah.',
            JURNAL: 'Daftar sesi ada di dialog berikutnya.',
            QUEUED: 'Jangan tutup aplikasi sebelum terkirim.',
            BATAL: 'Tidak ada yang tersimpan.',
            TEGURAN: 'Tidak ada yang tersimpan.',
            ABORTED: 'Tidak ada yang tersimpan.'
        };
        // Label status bar per kode. Default "Gagal memindai" salah untuk kasus
        // non-pemindaian (server menolak / sesi habis / data kurang).
        const ERROR_STATUS = {
            CAMERA_DENIED: 'Kamera tidak aktif',
            NO_FACE: 'Wajah tidak terdeteksi',
            NO_GPS: 'GPS belum terbaca',
            NO_LOC: 'Lokasi sekolah belum ada',
            NO_MODEL: 'Model AI belum termuat',
            SERVER: 'Presensi ditolak server',
            SESI: 'Sesi berakhir',
            INPUT: 'Data belum lengkap',
            OUT_OF_RANGE: 'Tidak dalam radius sekolah',
            FACE_MISMATCH: 'Wajah tidak dikenali',
            FACE_PROFILE: 'Data wajah belum terdaftar',
            GPS_MOCK: 'Lokasi ditolak sistem',
            SUDAH_MASUK: 'Sudah tercatat hari ini',
            SUDAH_PULANG: 'Sudah tercatat hari ini',
            BELUM_MASUK: 'Belum ada absen masuk',
            BELUM_WAKTU: 'Belum masuk jadwal',
            LEWAT_WAKTU: 'Melewati jadwal',
            LIBUR: 'Hari libur',
            THROTTLE: 'Tunggu sebentar',
            MODE_DITOLAK: 'Mode ditolak',
            JURNAL: 'Jurnal mengajar belum lengkap',
            QUEUED: 'Menunggu koneksi',
            BATAL: 'Absen dibatalkan',
            TEGURAN: 'Menunggu konfirmasi surat',
            ABORTED: 'Pemindaian dihentikan'
        };
        // Nada presentasi per kode. Penolakan kebijakan (sudah absen, belum
        // waktunya, hari libur) TIDAK sama dengan kegagalan perangkat: membingungkan
        // guru bila keduanya merah dan berbunyi "gagal".
        const ERROR_TONES = {
            SUDAH_MASUK: 'info', SUDAH_PULANG: 'info', LIBUR: 'info', QUEUED: 'info',
            BATAL: 'info', ABORTED: 'info', BELUM_MASUK: 'info',
            BELUM_WAKTU: 'warn', LEWAT_WAKTU: 'warn', OUT_OF_RANGE: 'warn',
            FACE_MISMATCH: 'warn', THROTTLE: 'warn', MODE_DITOLAK: 'warn',
            JURNAL: 'warn', TEGURAN: 'warn', INPUT: 'warn',
            CAMERA_DENIED: 'error', NO_FACE: 'error', NO_GPS: 'error', NO_LOC: 'error',
            NO_MODEL: 'error', SESI: 'error', SERVER: 'error',
            FACE_PROFILE: 'error', GPS_MOCK: 'error'
        };
        // Ikon viewport per kode agar kategori masalah langsung terbaca
        // sebelum guru membaca teks (kamera, GPS, jaringan, sesi).
        const ERROR_ICONS = {
            CAMERA_DENIED: 'bi-camera-video-off',
            NO_FACE: 'bi-person-bounding-box',
            NO_GPS: 'bi-geo-alt',
            NO_LOC: 'bi-geo-alt',
            OUT_OF_RANGE: 'bi-geo-alt',
            GPS_MOCK: 'bi-geo-alt',
            NO_MODEL: 'bi-cpu',
            SESI: 'bi-person-x',
            SERVER: 'bi-wifi',
            INPUT: 'bi-clipboard-x',
            FACE_MISMATCH: 'bi-person-dash',
            FACE_PROFILE: 'bi-person-video3',
            SUDAH_MASUK: 'bi-check2-circle',
            SUDAH_PULANG: 'bi-check2-circle',
            BELUM_MASUK: 'bi-box-arrow-in-right',
            BELUM_WAKTU: 'bi-clock-history',
            LEWAT_WAKTU: 'bi-alarm',
            LIBUR: 'bi-calendar-x',
            THROTTLE: 'bi-hourglass-split',
            MODE_DITOLAK: 'bi-slash-circle',
            JURNAL: 'bi-journal-text',
            QUEUED: 'bi-cloud-arrow-up',
            BATAL: 'bi-x-circle',
            TEGURAN: 'bi-envelope-exclamation',
            ABORTED: 'bi-stop-circle'
        };
        // Peta kosakata penolakan server (Attendance::checkIn/checkOut) ke kode +
        // kalimat yang bisa dipahami guru. Tanpa ini, teks teknis seperti
        // "Anda sudah absen masuk hari ini (Tgl: 2026-11-02)" atau
        // "Terjadi kesalahan sistem: Deadlock found..." masuk apa adanya ke
        // bingkai merah "Gagal memindai", dan guru memindai ulang berulang kali
        // padahal wajahnya sudah lolos.
        function tebakPesanServer(m) {
            const s = String(m || '');
            const l = s.toLowerCase();
            if (/terlalu banyak permintaan/.test(l)) return { code: 'THROTTLE', msg: 'Terlalu sering mencoba. Tunggu sekitar satu menit, lalu pindai lagi.' };
            if (/sudah absen pulang/.test(l)) return { code: 'SUDAH_PULANG', msg: 'Absen pulang hari ini sudah tercatat, jadi tidak perlu diulang.' };
            if (/sudah absen masuk/.test(l)) return { code: 'SUDAH_MASUK', msg: 'Absen masuk hari ini sudah tercatat, jadi tidak ditimpa lagi.' };
            if (/belum absen masuk/.test(l)) return { code: 'BELUM_MASUK', msg: 'Absen pulang baru bisa dilakukan setelah absen masuk tercatat.' };
            if (/waktu absen masuk habis/.test(l)) return { code: 'LEWAT_WAKTU', msg: s.replace(/\s+/g, ' ').trim() };
            if (/belum dibuka/.test(l)) return { code: 'BELUM_WAKTU', msg: s.replace(/\s+/g, ' ').trim() };
            if (/belum waktunya pulang/.test(l)) return { code: 'BELUM_WAKTU', msg: s.replace(/\s+/g, ' ').trim() };
            if (/hari libur|hari ini libur/.test(l)) return { code: 'LIBUR', msg: s.replace(/\s+/g, ' ').trim() };
            if (/di luar area lokasi/.test(l)) return { code: 'OUT_OF_RANGE', msg: 'Anda berada di luar area sekolah. Gunakan Mode Dinas / WFH bila sedang bertugas di luar.' };
            if (/titik lokasi .*(belum dikonfigurasi|belum disetting)/.test(l)) return { code: 'NO_LOC', msg: 'Titik lokasi sekolah belum diatur admin. Hubungi operator.' };
            if (/wajah tidak cocok/.test(l)) {
                const skor = (s.match(/\(([^)]*)\)/) || [])[1] || '';
                return { code: 'FACE_MISMATCH', msg: 'Wajah Anda tidak cukup mirip dengan foto di profil' + (skor ? ' (' + skor + ')' : '') + '. Coba lagi di tempat terang dan menghadap kamera.' };
            }
            if (/daftarkan wajah/.test(l)) return { code: 'FACE_PROFILE', msg: 'Foto wajah Anda belum terdaftar. Buka menu Profil, daftarkan wajah, lalu absen lagi.' };
            if (/data wajah profil rusak/.test(l)) return { code: 'FACE_PROFILE', msg: 'Data wajah di profil Anda rusak. Daftarkan ulang foto wajah di menu Profil.' };
            if (/verifikasi wajah gagal/.test(l)) return { code: 'FACE_PROFILE', msg: 'Verifikasi wajah gagal karena data wajah tidak tersedia. Daftarkan ulang foto wajah di menu Profil.' };
            if (/palsu|mock location/.test(l)) return { code: 'GPS_MOCK', msg: 'Lokasi terdeteksi berasal dari aplikasi lokasi palsu. Matikan, gunakan GPS asli, lalu ulangi.' };
            if (/akurasi gps/i.test(l)) return { code: 'NO_GPS', msg: 'Sinyal GPS kurang akurat. Buka jendela atau keluar ruangan, lalu ulangi.' };
            if (/fitur wfh tidak aktif/.test(l)) return { code: 'MODE_DITOLAK', msg: 'Mode WFH sedang tidak diaktifkan oleh sekolah.' };
            if (/kuota wfh/.test(l)) return { code: 'MODE_DITOLAK', msg: s.replace(/\s+/g, ' ').trim() };
            if (/pilih salah satu antara mode dinas/.test(l)) return { code: 'INPUT', msg: 'Pilih salah satu saja: Mode Dinas atau WFH, bukan keduanya.' };
            if (/(wajib mengisi keterangan|wajib mengisi alasan)/.test(l)) return { code: 'INPUT', msg: s.replace(/\s+/g, ' ').trim() };
            if (/foto wajib/.test(l)) return { code: 'INPUT', msg: 'Presensi ini mewajibkan foto. Biarkan kamera aktif, lalu ulangi.' };
            if (/waktu perangkat tidak valid/.test(l)) return { code: 'SERVER', msg: 'Jam perangkat Anda berbeda lebih dari 5 menit dari jam server. Aktifkan pengaturan waktu otomatis, lalu ulangi.' };
            if (/sync offline hanya bisa untuk hari ini/.test(l)) return { code: 'SERVER', msg: 'Antrean offline hanya berlaku untuk hari yang sama. Connect internet lalu kirim ulang, atau absen lewat admin.' };
            if (/terlalu banyak|gagal menyimpan absensi/.test(l)) return { code: 'SERVER', msg: 'Belum bisa disimpan sekarang. Tunggu sebentar lalu tekan Pindai Ulang.' };
            if (/terjadi kesalahan sistem/.test(l)) return { code: 'SERVER', msg: 'Sistem bermasalah saat menyimpan presensi Anda. Coba lagi sebentar; bila terus gagal, hubungi admin.' };
            if (/jurnal/i.test(l)) return { code: 'JURNAL', msg: s.replace(/\s+/g, ' ').trim() };
            if (/jaringan|tidak merespons/.test(l)) return { code: 'SERVER', msg: 'Koneksi terputus. Periksa internet Anda, lalu ulangi.' };
            return null;
        }

        // Bersihkan teks yang tidak pantas muncul di layar guru.
        function rapikanPesan(m) {
            return String(m || '')
                // tanggal mentah "(Tgl: 2026-11-02)" / "(2026-11-02)"
                .replace(/\(\s*Tgl:?\s*\d{4}-\d{2}-\d{2}\s*\)/gi, '')
                .replace(/\(\s*\d{4}-\d{2}-\d{2}\s*\)/g, '')
                // kutipan motivasi multi-baris yang ditempel server di akhir pesan
                .replace(/\n{2,}[\s\S]*$/, '')
                // sisa detail exception server
                .replace(/:(.*)\b(?:SQL|SQLSTATE|stack trace|on line \d+).*$/i, ':')
                .replace(/\s{2,}/g, ' ')
                .trim();
        }

        function showError(msg, showLegacy, code) {
            const box = $('facescan-error');
            const teks = rapikanPesan(msg);
            box.classList.remove('hidden', 'is-warn', 'is-info');
            const ton = ERROR_TONES[code] || 'error';
            if (ton === 'warn') box.classList.add('is-warn');
            if (ton === 'info') box.classList.add('is-info');
            const isRange = code === 'OUT_OF_RANGE';
            const cancel = $('facescan-cancel');
            if (cancel) cancel.textContent = ton === 'info' ? 'Mengerti' : (isRange ? 'Tutup' : 'Batalkan');
            const retry = $('facescan-retry');
            if (retry) {
                if (isRange) {
                    // Di luar radius, memindai ulang tidak akan mengubah hasil.
                    // Jadikan aksi utama sebagai pintu Mode Dinas / WFH.
                    retry.className = 'fs-btn fs-btn-brand';
                    retry.innerHTML = '<i class="bi bi-shield-check"></i> Mode Dinas / WFH';
                    retry.onclick = function () { closeModal(); if (window.handleScanClick) window.handleScanClick(); };
                } else {
                    retry.className = 'fs-btn fs-btn-primary';
                    retry.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Pindai Ulang';
                    retry.onclick = function () { if (!S.busy) start(); };
                }
            }
            $('facescan-error-text').textContent = teks;
            $('facescan-legacy').classList.toggle('hidden', !showLegacy || isRange);
            setStatus(ERROR_STATUS[code] || 'Gagal memindai', ton === 'error' ? false : null);
            setHint(ERROR_HINTS[code] || 'Periksa izin perangkat dan koneksi, lalu coba lagi.');
            setViewportState(ton, ERROR_TITLES[code] || 'Pemindaian terhenti', ERROR_SUBS[code] || 'Silakan coba lagi.');
            const v = $('facescan-video');
            v.classList.add('hidden'); v.srcObject = null;
            $('facescan-fallback').style.display = 'flex';
            const ic = $('facescan-fallback-icon');
            if (ic) {
                ic.className = 'bi ' + (ERROR_ICONS[code] || 'bi-exclamation-triangle');
            }
            return ton;
        }
        // Titik masuk seragam untuk semua penolakan: tebak kode dari pesan server
        // bila pemanggil belum menyertakannya, lalu tampilkan dengan nada yang pas.
        function tampilkanPenolakan(err) {
            let code = err.code || 'UNKNOWN';
            let msg = err.msg || 'Presensi belum tersimpan.';
            if (code === 'SERVER') {
                const t = tebakPesanServer(err.msg);
                if (t) { code = t.code; msg = t.msg; }
            }
            if (code === 'UNKNOWN' || !ERROR_TITLES[code]) {
                const t = tebakPesanServer(err.msg);
                if (t) { code = t.code; msg = t.msg; }
            }
            const ton = showError(msg, !!err.legacy, code);
            bunyi(ton === 'error' ? 'gagal' : 'peringatan');
            setProgress(0);
            return ton;
        }
        function hideError() {
            $('facescan-error').classList.add('hidden');
            $('facescan-legacy').classList.add('hidden');
        }
        // Absen pulang ditolak karena sesi terjadwal belum selesai jurnalnya.
        // Dua kelompok dibedakan: "belum diisi" (tautan Isi) dan "menunggu
        // validasi" (jurnalnya sudah ada — tautan Lihat, bukan Isi).
        function showJurnalGate(payload) {
            const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const data = Array.isArray(payload) ? { sesi_belum_dijurnal: payload } : (payload || {});
            const belum = Array.isArray(data.sesi_belum_dijurnal) ? data.sesi_belum_dijurnal : [];
            const menunggu = Array.isArray(data.sesi_menunggu_validasi) ? data.sesi_menunggu_validasi : [];
            const petugas = Array.isArray(data.petugas_validasi) ? data.petugas_validasi : [];

            const metaSesi = function (s) {
                const jamKe = esc(String(s.jam_ke || '').trim());
                const dari = String(s.dari || '').trim();
                const sampai = String(s.sampai || '').trim();
                const rentang = esc(sampai ? (dari + '–' + sampai) : dari);
                return [jamKe ? 'Jam ' + jamKe : '', rentang].filter(Boolean).join(' · ');
            };
            const judul = function (s) {
                return esc((s.nama_kelas || 'Kelas') + ' · ' + (s.nama_mapel || 'Mapel'));
            };
            const rowIsi = function (s) {
                const idJadwal = parseInt(s.id_jadwal, 10) || 0;
                const href = BASE + '/guru/jurnal/create' + (idJadwal > 0 ? '?id_jadwal=' + idJadwal + '&kembali=absen' : '?kembali=absen');

                return `
                    <a href="${href}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;margin-bottom:8px;text-decoration:none;">
                        <span style="min-width:0;">
                            <span style="display:block;font-size:13px;font-weight:800;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${judul(s)}</span>
                            ${metaSesi(s) ? `<span style="display:block;font-size:11px;color:#64748b;">${metaSesi(s)}</span>` : ''}
                        </span>
                        <span style="flex-shrink:0;font-size:11px;font-weight:800;color:#4f46e5;"><i class="bi bi-pencil-square me-1"></i> Isi</span>
                    </a>`;
            };
            const rowValidasi = function (s) {
                const idJurnal = parseInt(s.id_jurnal, 10) || 0;
                const href = idJurnal > 0 ? BASE + '/guru/jurnal/detail/' + idJurnal : BASE + '/guru/jurnal';
                const ditolak = String(s.validasi_status || '').toUpperCase() === 'REJECTED';
                const warna = ditolak ? '#e11d48' : '#d97706';
                const label = ditolak ? 'Perbaiki' : 'Lihat';

                return `
                    <a href="${href}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;margin-bottom:8px;text-decoration:none;">
                        <span style="min-width:0;">
                            <span style="display:block;font-size:13px;font-weight:800;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${judul(s)}</span>
                            <span style="display:block;font-size:11px;color:${warna};font-weight:700;">${ditolak ? 'Jurnal ditolak — perlu diperbaiki' : 'Menunggu validasi piket'}${metaSesi(s) ? ' · ' + metaSesi(s) : ''}</span>
                        </span>
                        <span style="flex-shrink:0;font-size:11px;font-weight:800;color:${warna};"><i class="bi bi-eye me-1"></i> ${label}</span>
                    </a>`;
            };

            const judulMain = menunggu.length
                ? (belum.length ? 'Jurnal Mengajar Belum Lengkap' : 'Jurnal Menunggu Validasi')
                : 'Jurnal Mengajar Belum Lengkap';
            const subjudul = menunggu.length && !belum.length
                ? 'Jurnal sudah diisi; absen pulang terbuka setelah piket menyetujui.'
                : (menunggu.length ? 'Isi yang belum terisi, lalu minta validasi untuk yang sudah diisi.' : 'Isi jurnal sesi berikut, lalu ulangi absen pulang.');

            const namaPetugas = petugas.map((p) => String(p && p.nama ? p.nama : '').trim()).filter(Boolean);
            const catatan = menunggu.length
                ? (namaPetugas.length
                    ? `<i class="bi bi-person-check"></i> Validasi oleh guru piket hari ini: ${esc(namaPetugas.slice(0, 3).join(' / '))}${namaPetugas.length > 3 ? ' dan ' + (namaPetugas.length - 3) + ' guru piket lainnya' : ''}.`
                    : '<i class="bi bi-exclamation-triangle"></i> Belum ada guru piket terjadwal hari ini sebagai penanggung jawab validasi — hubungi admin presensi.')
                : '<i class="bi bi-info-circle"></i> Setelah jurnal tersimpan, ulangi absen pulang.';

            const bagian = [];
            if (belum.length) {
                bagian.push(`<div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em;margin:2px 0 6px;">Belum diisi (${belum.length})</div>${belum.map(rowIsi).join('')}`);
            }
            if (menunggu.length) {
                bagian.push(`<div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em;margin:10px 0 6px;">Menunggu validasi (${menunggu.length})</div>${menunggu.map(rowValidasi).join('')}`);
            }

            Swal.fire({
                html: `
                    <div style="text-align:left;padding:0 2px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                            <div style="flex-shrink:0;width:44px;height:44px;background:#eef2ff;color:#4f46e5;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-journal-text" style="font-size:22px;"></i>
                            </div>
                            <div>
                                <h5 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;">${judulMain}</h5>
                                <p style="font-size:11px;color:#64748b;margin:0;">${subjudul}</p>
                            </div>
                        </div>
                        <div style="margin-top:12px;">${bagian.join('')}</div>
                        <p style="font-size:11px;color:#94a3b8;margin:0;">${catatan}</p>
                    </div>`,
                showConfirmButton: true,
                confirmButtonText: 'Tutup',
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-3xl border-0 shadow-lg px-2 py-3',
                    confirmButton: 'btn-tw btn-light w-full py-2.5 rounded-full font-bold text-slate-700 mt-2',
                    actions: 'flex flex-column w-100 px-3 mt-2'
                }
            });
        }
        function distM(a, b, c, d) {
            const R = 6371000, t = Math.PI / 180;
            const h = Math.sin((c - a) * t / 2) ** 2 + Math.cos(a * t) * Math.cos(c * t) * Math.sin((d - b) * t / 2) ** 2;
            return 2 * R * Math.asin(Math.sqrt(h));
        }
        // Jarak ramah dibaca: 850 m, 1,2 km, 5,5 km.
        function formatJarak(m) {
            m = Math.max(0, Number(m) || 0);
            if (m < 1000) return Math.round(m) + ' m';
            return (m / 1000).toFixed(1).replace('.', ',') + ' km';
        }
        function stopStream() {
            if (S.detectTimer) { clearInterval(S.detectTimer); S.detectTimer = null; }
            if (S.stream) { S.stream.getTracks().forEach((t) => t.stop()); S.stream = null; }
        }
        function resetUI() {
            hideError();
            setProgress(0);
            const m = $('facescan-modal');
            if (m) m.classList.remove('is-success');
            const sub = $('facescan-subtitle');
            if (sub) sub.textContent = 'Ikuti panduan sampai selesai';
            const cancel = $('facescan-cancel');
            if (cancel) cancel.textContent = 'Batalkan';
            const retry = $('facescan-retry');
            if (retry) {
                retry.className = 'fs-btn fs-btn-primary';
                retry.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Pindai Ulang';
                retry.onclick = function () { if (!S.busy) start(); };
            }
            $('facescan-success').style.display = 'none';
            $('facescan-ring').classList.remove('facescan-ring-ok');
            const v = $('facescan-video');
            v.classList.add('hidden'); v.srcObject = null;
            $('facescan-fallback').style.display = 'flex';
            applyTargetBadge();
            setStep('Menyiapkan...');
            setStatus('Menyiapkan pemindai...');
            setHint('Posisikan wajah & tatap kamera.');
            setViewportState('idle', 'Kamera belum aktif', 'Pemindaian dimulai otomatis');
            $('facescan-loc-name').textContent = 'Mendeteksi GPS...';
            $('facescan-loc-dist').textContent = '—';
            $('facescan-loc-dist').style.color = '';
        }
        function applyTargetBadge() {
            const b = $('facescan-target-badge');
            const masuk = S.target === 'MASUK';
            b.textContent = masuk ? 'Absen Masuk' : 'Absen Pulang';
            b.className = 'fs-target-chip ' + (masuk ? 'is-masuk' : 'is-pulang');
            $('facescan-explainer').textContent = masuk
                ? 'Terdeteksi: Anda belum melakukan Absen Masuk'
                : 'Terdeteksi: Anda siap melakukan Absen Pulang';
        }

        async function teguranGate() {
            let list = [];
            try {
                const res = await jQuery.get(BASE + '/presensi/surat-saya');
                list = (res && res.status && Array.isArray(res.data)) ? res.data : [];
            } catch (e) { list = []; }
            if (!list.length) return true;
            return await showTeguranModal(list);
        }

        /**
         * Modal komitmen surat teguran. Non-dismissable: hanya tertutup setelah
         * frasa persis diketik dan /presensi/surat-ack berhasil menyimpan.
         * Setelah konfirmasi, pemanggil boleh melanjutkan absen seperti biasa.
         */
        async function showTeguranModal(list) {
            if (!list || !list.length) return true;
            const ids = list.map((L) => L.id);
            const items = list.map((L) => (
                '<div style="display:flex;align-items:center;gap:10px;text-align:left;background:#fff;border:1px solid #fecdd3;border-left:4px solid var(--brand-600);border-radius:12px;padding:8px 10px;margin-bottom:8px;">' +
                '<div style="flex:1;min-width:0;"><div style="font-size:12px;font-weight:800;color:#1e293b;">' + String(L.level_text || '').replace(/[<>&"']/g, '') + '</div>' +
                '<div style="font-size:10.5px;color:#64748b;">' + String(L.bulan_text || '') + ' ' + String(L.tahun || '') + '</div></div>' +
                '<a href="' + String(L.download_url || '#') + '" target="_blank" style="flex-shrink:0;width:38px;height:38px;border-radius:12px;background:var(--primary-50);color:var(--primary-600);display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;"><i class="bi bi-file-earmark-pdf"></i></a></div>'
            )).join('');
            const r = await Swal.fire({
                html: '<div style="text-align:center;margin-bottom:10px;"><div style="display:inline-flex;width:52px;height:52px;border-radius:18px;background:#fee2e2;color:#dc2626;align-items:center;justify-content:center;font-size:24px;"><i class="bi bi-envelope-exclamation-fill"></i></div>' +
                    '<div style="font-size:16px;font-weight:800;color:#1e293b;margin-top:8px;">Surat Teguran</div>' +
                    '<p style="font-size:12px;color:#64748b;margin:4px 0 0;">Anda memiliki <b style="color:#b91c1c;">' + list.length + ' surat</b> yang wajib dibaca sebelum absen.</p></div>' +
                    '<div style="max-height:26vh;overflow-y:auto;">' + items + '</div>' +
                    '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:12px;margin-top:6px;">' +
                    '<div style="font-size:11.5px;font-weight:700;color:#334155;">Ketik persis kalimat berikut:</div>' +
                    '<div style="background:#fff;border:1px dashed var(--brand-300);border-radius:10px;padding:8px 10px;margin:8px 0 10px;font-size:13px;font-weight:800;color:var(--brand-600);">ya, saya akan meningkatkan disiplin</div>' +
                    '<input id="facescan-teguran-frasa" class="swal2-input" style="margin:0;width:100%;text-align:center;" placeholder="ketik di sini..." autocomplete="off"></div>',
                width: '26rem', allowOutsideClick: false, allowEscapeKey: false,
                showCancelButton: false, confirmButtonText: 'Konfirmasi & Lanjutkan Absen', confirmButtonColor: '#e11d48',
                didOpen: () => {
                    const btn = Swal.getConfirmButton(), input = document.getElementById('facescan-teguran-frasa');
                    btn.disabled = true; btn.style.opacity = 0.5;
                    input.addEventListener('input', () => {
                        const ok = input.value.trim().toLowerCase() === TEGURAN_FRASA;
                        btn.disabled = !ok; btn.style.opacity = ok ? 1 : 0.5;
                    });
                    setTimeout(() => input.focus(), 300);
                },
                preConfirm: () => {
                    const val = (document.getElementById('facescan-teguran-frasa').value || '');
                    if (val.trim().toLowerCase() !== TEGURAN_FRASA) {
                        Swal.showValidationMessage('Teks belum tepat.');
                        return false;
                    }
                    const data = { ids: ids, konfirmasi: val };
                    data[CSRF_NAME] = CSRF_HASH;
                    return jQuery.post(BASE + '/presensi/surat-ack', data).then((res) => {
                        if (res && res.status) return true;
                        Swal.showValidationMessage((res && res.message) || 'Gagal menyimpan konfirmasi.');
                        return false;
                    }).catch(() => { Swal.showValidationMessage('Gagal terhubung ke server.'); return false; });
                }
            });
            return !!(r && r.isConfirmed && r.value);
        }

        function gpsStage() {
            setStep('Cek Lokasi');
            setStatus('Memeriksa koordinat GPS...'); setProgress(8);
            setHint('Pastikan GPS aktif dan Anda berada di area sekolah.');
            bunyi('tik');
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) return reject({ code: 'NO_GPS', msg: 'Perangkat tidak mendukung GPS', legacy: true });
                navigator.geolocation.getCurrentPosition((pos) => {
                    S.lat = pos.coords.latitude; S.lng = pos.coords.longitude; S.acc = pos.coords.accuracy || 0;
                    let best = null, bd = Infinity;
                    (LOCATIONS || []).forEach((l) => {
                        const d = distM(S.lat, S.lng, parseFloat(l.lat), parseFloat(l.lng));
                        if (d < bd) { bd = d; best = l; }
                    });
                    if (!best) {
                        $('facescan-loc-name').textContent = 'Lokasi belum disetting';
                        $('facescan-loc-dist').textContent = '—';
                        $('facescan-loc-dist').style.color = '';
                        return reject({ code: 'NO_LOC', msg: 'Titik lokasi belum disetting admin. Hubungi admin sekolah.' });
                    }
                    const r = parseFloat(best.radius_meter) || 0;
                    S.locName = best.nama_lokasi || 'Lokasi Kantor';
                    S.locDist = Math.round(bd);
                    S.inRange = r > 0 ? bd <= r : true;
                    $('facescan-loc-name').textContent = S.locName;
                    const dd = $('facescan-loc-dist');
                    dd.textContent = formatJarak(S.locDist) + ' · ' + (S.inRange ? 'Dalam radius' : 'Di luar radius');
                    dd.style.color = S.inRange ? '#2dd4bf' : '#f87171';
                    setProgress(30);
                    if (!S.inRange) {
                        return reject({
                            code: 'OUT_OF_RANGE',
                            msg: 'Jarak ' + formatJarak(S.locDist) + ' — melebihi batas ' + formatJarak(r) + '. Gunakan Mode Dinas / WFH bila bertugas di luar.',
                            legacy: true
                        });
                    }
                    setHint('Lokasi sesuai. Menyiapkan kamera...');
                    resolve();
                }, (err) => {
                    // err.message dari browser berbahasa Inggris ("User denied
                    // Geolocation") dan tidak dipahami guru — terjemahkan
                    // berdasarkan kode agar langsung tahu yang harus dilakukan.
                    const gpsMsg = {
                        1: 'Izin lokasi ditolak. Ketuk ikon gembok di address bar browser, izinkan akses lokasi, lalu ulangi.',
                        2: 'Sinyal GPS tidak terdeteksi. Coba bergerak ke tempat terbuka, atau matikan-nyalakan GPS.',
                        3: 'GPS terlalu lama merespons. Pastikan GPS aktif, lalu ulangi.'
                    }[err && err.code] || 'GPS gagal dideteksi. Pastikan GPS aktif dan izin lokasi diberikan, lalu ulangi.';
                    reject({ code: 'NO_GPS', msg: gpsMsg, legacy: true });
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
            });
        }

        async function ensureModels() {
            if (S.modelsLoaded || (window.faceapi && window.faceapi.nets && window.faceapi.nets.tinyFaceDetector.isLoaded)) {
                S.modelsLoaded = true; return;
            }
            if (!window.faceapi) throw { code: 'NO_MODEL', msg: 'Library AI wajah belum termuat. Muat ulang halaman.' };
            const loads = (u) => Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(u),
                faceapi.nets.faceLandmark68Net.loadFromUri(u),
                faceapi.nets.faceRecognitionNet.loadFromUri(u)
            ]);
            try { await loads(MODEL_LOCAL); }
            catch (e) { await loads(MODEL_CDN); }
            S.modelsLoaded = true;
        }

        function faceStage() {
            setStep('Pindai Wajah');
            setStatus('Menyiapkan kamera...'); setProgress(36);
            setHint('Menyiapkan kamera dan AI wajah...');
            bunyi('tik');
            return (async () => {
                const video = $('facescan-video');
                try {
                    S.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false
                    });
                    video.srcObject = S.stream;
                    video.classList.remove('hidden');
                    $('facescan-fallback').style.display = 'none';
                    setViewportState('scanning');
                    await video.play().catch(() => {});
                } catch (e) {
                    if (FOTO_WAJIB !== 1) {
                        S.foto = null;
                        setStatus('Kamera dilewati (foto tidak wajib)', true);
                        setHint('Foto tidak wajib. Melanjutkan verifikasi presensi.');
                        setViewportState('idle', 'Kamera dilewati', 'Foto tidak wajib untuk absensi ini');
                        setProgress(70);
                        return;
                    }
                    // Nama error browser (NotAllowedError, dst.) tidak dipahami
                    // guru — terjemahkan ke penyebab yang bisa ditindaklanjuti.
                    const namaErr = String((e && e.name) || '');
                    const kameraMsg =
                        (namaErr === 'NotAllowedError' || namaErr === 'PermissionDeniedError')
                            ? 'Izin kamera ditolak. Ketuk ikon gembok di address bar browser, izinkan kamera, lalu tekan Pindai Ulang.'
                        : (namaErr === 'NotFoundError' || namaErr === 'DevicesNotFoundError')
                            ? 'Kamera tidak ditemukan di perangkat ini. Gunakan perangkat yang memiliki kamera, atau absen melalui admin.'
                        : (namaErr === 'NotReadableError' || namaErr === 'TrackStartError')
                            ? 'Kamera sedang dipakai aplikasi lain (mis. Zoom / Google Meet). Tutup aplikasi tersebut, lalu tekan Pindai Ulang.'
                            : 'Kamera tidak bisa diakses. Pastikan kamera tidak diblokir browser, lalu tekan Pindai Ulang.';
                    throw { code: 'CAMERA_DENIED', msg: kameraMsg, legacy: true };
                }
                setStatus('Memuat AI wajah...');
                setHint('Memuat model verifikasi wajah...');
                await ensureModels().catch(() => {
                    throw { code: 'NO_MODEL', msg: 'Gagal memuat AI wajah. Periksa koneksi.', legacy: FOTO_WAJIB !== 1 };
                });
                setStatus('Posisikan wajah di dalam lingkaran...'); setProgress(44);
                setHint('Tatap kamera dan tahan posisi hingga verifikasi selesai.');
                const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.25 });
                S.stable = 0; S.descriptor = null;
                const t0 = Date.now();
                await new Promise((resolve, reject) => {
                    S.detectTimer = setInterval(async () => {
                        if (!S.stream) { clearInterval(S.detectTimer); S.detectTimer = null; return reject({ code: 'ABORTED', msg: 'Dibatalkan' }); }
                        if (Date.now() - t0 > 25000) {
                            clearInterval(S.detectTimer); S.detectTimer = null;
                            return reject({ code: 'NO_FACE', msg: 'Wajah tidak terdeteksi. Coba lagi di tempat terang.', legacy: FOTO_WAJIB !== 1 });
                        }
                        try {
                            const det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
                            if (det && det.descriptor) {
                                S.stable++;
                                S.descriptor = Array.from(det.descriptor);
                                const pct = 44 + Math.min(24, Math.round((S.stable / 6) * 24));
                                setProgress(pct);
                                setStatus('Wajah terdeteksi (' + Math.min(S.stable, 6) + '/6)');
                                if (S.stable >= 6) {
                                    clearInterval(S.detectTimer); S.detectTimer = null;
                                    // Capture frame
                                    const cv = $('facescan-canvas');
                                    cv.width = video.videoWidth || 640; cv.height = video.videoHeight || 480;
                                    cv.getContext('2d').drawImage(video, 0, 0, cv.width, cv.height);
                                    S.foto = cv.toDataURL('image/jpeg', 0.8);
                                    $('facescan-ring').classList.add('facescan-ring-ok');
                                    bunyi('tik');
                                    setProgress(70);
                                    setHint('Wajah terverifikasi. Mengirim presensi...');
                                    return resolve();
                                }
                            } else {
                                S.stable = 0;
                                setStatus('Posisikan wajah di dalam lingkaran...');
                            }
                        } catch (e) { /* abaikan frame gagal */ }
                    }, 300);
                });
            })();
        }

        // ── Outbox offline: antrean presensi saat koneksi putus ──
        // Server hanya menerima timestamp ±5 menit, jadi item kedaluwarsa
        // setelah 4 menit dan wajib dikirim ulang manual bila terlewat.
        const Outbox = (function () {
            const KEY = 'presensi_outbox_v1';
            const MAX_ITEMS = 3;
            const MAX_AGE_MS = 4 * 60 * 1000;
            // Server membandingkan timestamp dengan jam server (WITA), jadi
            // kirim jam WITA — bukan waktu lokal perangkat (selisih 1-2 jam di
            // WIB/WIT pasti ditolak) dan bukan UTC (toISOString, selisih ±8 jam).
            function localStamp(d) {
                const p = (n) => String(n).padStart(2, '0');
                return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate())
                    + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
            }
            function load() {
                try {
                    const arr = JSON.parse(localStorage.getItem(KEY) || '[]');
                    return Array.isArray(arr) ? arr : [];
                } catch (e) { return []; }
            }
            function save(arr) {
                try { localStorage.setItem(KEY, JSON.stringify(arr)); } catch (e) {}
            }
            function prune(arr) {
                const now = Date.now();
                return arr.filter(it => it && (now - (it.ts || 0)) < MAX_AGE_MS);
            }
            return {
                enqueue: function (url, data) {
                    let arr = prune(load());
                    if (arr.length >= MAX_ITEMS) arr.shift();
                    const payload = Object.assign({}, data, {
                        timestamp: localStamp(witaNow())
                    });
                    payload[CSRF_NAME] = CSRF_HASH;
                    arr.push({ url: url, data: payload, ts: Date.now() });
                    save(arr);
                    return true;
                },
                flush: async function () {
                    let arr = prune(load());
                    if (!arr.length || typeof jQuery === 'undefined') { save(arr); return; }
                    if (typeof navigator !== 'undefined' && navigator.onLine === false) return;
                    // Token item antrean bisa basi (disimpan sebelum refresh):
                    // segarkan sekali lalu tulis ulang token semua item. Tanpa
                    // ini tiap replay menjawab 403 dan mengotori log server.
                    if (!(await refreshCsrf(true))) { save(arr); return; } // sesi habis — jangan kirim token basi
                    arr.forEach(function (it) {
                        if (it && it.data) {
                            it.data[CSRF_NAME] = CSRF_HASH;
                            delete it.data._csrfRetried;
                        }
                    });
                    const gagalKirim = [];
                    const ditolakServer = [];
                    let settled = 0;
                    const total = arr.length;
                    function settle() {
                        settled++;
                        if (settled < total) return;
                        // Item yang ditolak server tidak ada gunanya dikirim ulang
                        // (alasannya tidak berubah dalam hitungan detik), jadi hanya
                        // item dengan kegagalan transport yang dipertahankan.
                        save(prune(gagalKirim));
                        if (typeof Swal === 'undefined') return;
                        if (!gagalKirim.length && !ditolakServer.length) {
                            Swal.fire({ icon: 'success', title: 'Presensi Terkirim', text: total + ' presensi offline berhasil dikirim.', timer: 2500, showConfirmButton: false });
                            bunyi('masuk');
                            try { location.reload(); } catch (e) {}
                            return;
                        }
                        // HTTP 200 belum berarti presensi tersimpan: server bisa
                        // membalas status:false (belum waktunya pulang, jurnal
                        // belum lengkap, radius, dsb). Mengaku "berhasil" di sini
                        // membuat guru tidak pernah tahu absennya ditolak.
                        const alasan = ditolakServer.concat(gagalKirim.map(() => 'Presensi tidak terkirim karena gangguan jaringan.'))
                            .map(d => rapikanPesan((tebakPesanServer(d.msg) || { msg: d.msg }).msg))
                            .filter(Boolean);
                        const unik = Array.from(new Set(alasan));
                        const teks = unik.length
                            ? (total > 1 ? unik.map((a, i) => (i + 1) + '. ' + a).join('\n') : unik[0])
                            : 'Presensi belum berhasil dikirim. Coba absen manual.';
                        bunyi(ditolakServer.length ? 'gagal' : 'peringatan');
                        Swal.fire({
                            icon: 'warning',
                            title: ditolakServer.length ? 'Presensi Ditolak Server' : 'Presensi Belum Terkirim',
                            text: teks + (ditolakServer.length ? '\n\nPresensi yang ditolak tidak dikirim ulang. Absen manual lewat admin bila masih perlu.' : ''),
                            confirmButtonText: 'Mengerti'
                        }).then(function () {
                            try { location.reload(); } catch (e) {}
                        });
                    }
                    arr.forEach(function (it) {
                        jQuery.post(it.url, it.data)
                            .done(function (res) {
                                if (res && res.status) return settle();
                                ditolakServer.push({ msg: (res && res.message) || 'Presensi ditolak server.' });
                                settle();
                            })
                            .fail(function (xhr) {
                                // Jangan buang item saat 403: antrean kedaluwarsa
                                // otomatis (4 menit) dan dibuang hanya setelah
                                // gagal. Menghapus di sini bisa menghilangkan
                                // presensi tanpa jejak.
                                gagalKirim.push(it);
                                settle();
                            });
                    });
                }
            };
        })();
        if (typeof window !== 'undefined') {
            window.addEventListener('online', function () { try { Outbox.flush(); } catch (e) {} });
        }

        // ── Opsi dinas / WFH ─────────────────────────────────────────────
        // Dulu kedua sakelar ini hanya hiasan: submitStage selalu mengirim
        // is_dinas:0, is_wfh:0, keterangan:'' sehingga guru yang benar-benar
        // tugas luar tercatat telat/di luar radius, dan WFH tidak pernah
        // mengikuti jadwal serta kuota WFH di server.
        function modeAbsenDipilih() {
            const dinasEl = $('dinasCheck'), wfhEl = $('wfhCheck'), ketEl = $('dinasKeterangan');
            const isDinas = !!(dinasEl && dinasEl.checked);
            const isWfh = !!(wfhEl && wfhEl.checked);
            const ket = isDinas ? String((ketEl && ketEl.value) || '').trim().slice(0, 200) : '';

            return { is_dinas: isDinas ? 1 : 0, is_wfh: isWfh ? 1 : 0, keterangan: ket };
        }

        (function bindModeAbsen() {
            const dinasEl = $('dinasCheck'), wfhEl = $('wfhCheck'), ketWrap = $('dinasKetContainer'), ketEl = $('dinasKeterangan');
            if (!dinasEl || !wfhEl) return;
            const sync = function () {
                // Server menolak dinas + WFH sekaligus; lebih baik dibersihkan
                // di tempat daripada guru menerima error setelah memindai wajah.
                if (wfhEl.checked && dinasEl.checked) {
                    dinasEl.checked = false;
                    if (ketEl) ketEl.value = '';
                }
                if (ketWrap) ketWrap.classList.toggle('hidden', !dinasEl.checked);
            };
            dinasEl.addEventListener('change', sync);
            wfhEl.addEventListener('change', sync);
            sync();
        })();

        async function confirmPeringatanAbsen(message) {
            const r = await Swal.fire({
                title: 'Peringatan Kehadiran',
                text: rapikanPesan(message) || 'Absensi Anda perlu konfirmasi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan absen',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
            });

            return !!(r && r.isConfirmed);
        }

        async function submitStage() {
            setStep('Verifikasi');
            setStatus('Mengirim presensi...'); setProgress(85);
            setHint('Mengirim data presensi ke server...');
            bunyi('tik');
            await refreshCsrf(false);
            const url = BASE + (S.target === 'MASUK' ? '/presensi/check-in' : '/presensi/check-out');
            // Pulang TIDAK mengambil mode dari client: server membaca status
            // dinas/WFH dari record check-in (Attendance::checkOut), jadi flag
            // di sini hanya akan menimpa keterangan pulang dengan alasan dinas.
            const mode = (S.target === 'MASUK') ? modeAbsenDipilih() : { is_dinas: 0, is_wfh: 0, keterangan: '' };
            if (mode.is_dinas && mode.keterangan === '') {
                throw { code: 'INPUT', msg: 'Mode dinas wajib diisi alasannya. Buka "Opsi dinas / WFH" lalu isi Keterangan.' };
            }

            const data = {
                lat: S.lat, lng: S.lng, foto: S.foto,
                is_dinas: mode.is_dinas, is_wfh: mode.is_wfh,
                accuracy: S.acc, confirm: 0, keterangan: mode.keterangan
            };
            if (S.descriptor) data.face_descriptor = JSON.stringify(S.descriptor);
            data[CSRF_NAME] = CSRF_HASH;

            // Payload peringatan dari server selalu membawa token CSRF baru;
            // dipakai ulang supaya kiriman konfirmasi tidak kena 403.
            const serapCsrf = function (r) {
                if (!r) return;
                const sumber = (r.data && r.data[CSRF_NAME]) ? r.data : r;
                if (sumber && sumber[CSRF_NAME]) CSRF_HASH = String(sumber[CSRF_NAME]);
            };
            const kirim = async function () {
                data[CSRF_NAME] = CSRF_HASH;
                const r = await jQuery.post(url, data);
                serapCsrf(r);
                return r;
            };
            const perluAck = function (r) {
                return !!(r && !r.status && ((r.data && r.data.require_confirm) || r.require_confirm));
            };

            let res = null;
            try { res = await kirim(); }
            catch (xhr) {
                // Gate surat teguran dikembalikan sebagai HTTP 403, jadi branching
                // ini juga harus membaca responseJSON dari error jQuery.
                const body = (xhr && xhr.responseJSON) || null;
                if (body && body.data && body.data.require_teguran_ack) {
                    const okAck = await showTeguranModal(body.data.letters || []);
                    if (!okAck) throw { code: 'TEGURAN', msg: 'Konfirmasi surat teguran dibatalkan.' };
                    res = await kirim();
                } else if (xhr && xhr.status === 403 && !data._csrfRetried) {
                    // Token CSRF basi: ambil token segar sekali lalu ulangi
                    // submit. Bila refresh gagal, berarti sesi habis — minta
                    // login ulang, bukan error misterius.
                    if (await refreshCsrf(true)) {
                        data._csrfRetried = 1;
                        setStatus('Memperbarui sesi, mengirim ulang...');
                        res = await kirim();
                    } else {
                        throw { code: 'SESI', msg: 'Sesi kedaluwarsa. Muat ulang halaman lalu login kembali.' };
                    }
                } else {
                    // Koneksi putus (status 0 / offline): simpan ke outbox, kirim otomatis
                    // saat online kembali. Server hanya menerima timestamp ±5 menit,
                    // jadi antrean kedaluwarsa otomatis setelah ~4 menit.
                    const offline = (typeof navigator !== 'undefined' && navigator.onLine === false) || (xhr && xhr.status === 0);
                    if (offline) {
                        // Antrean offline tidak bisa menampilkan dialog, jadi
                        // peringatan kehadiran tidak dapat diakui di sini.
                        // Presensi tetap disimpan (catatannya yang penting —
                        // status & verifikasinya masih diputuskan server),
                        // dan Outbox.flush() di bawah tidak lagi mengaku
                        // "sukses" bila server menolak.
                        data.confirm = 1;
                        if (Outbox.enqueue(url, data)) {
                            throw { code: 'QUEUED', msg: 'Koneksi terputus. Presensi tersimpan di HP dan akan dikirim otomatis saat online.' };
                        }
                    }
                    const m = (body && body.message) || 'Server tidak merespons. Periksa koneksi internet Anda, lalu ulangi.';
                    throw { code: 'SERVER', msg: m };
                }
            }

            // Acknowledgement: server menahan absen guru dengan alpha >= 3 hari
            // berturut-turut / 3 / 7 / 10 hari dan membalas HTTP 200 dengan
            // status false + require_confirm. Selama submitStage selalu
            // mengirim confirm:1, peringatan itu tidak pernah dilihat guru —
            // dan absen >= 10 hari berubah jadi PENDING_VERIFICATION diam-diam.
            for (let ulangi = 0; ulangi < 3; ulangi++) {
                if (res && !res.status && res.data && res.data.require_teguran_ack) {
                    const okAck = await showTeguranModal(res.data.letters || []);
                    if (!okAck) throw { code: 'TEGURAN', msg: 'Konfirmasi surat teguran dibatalkan.' };
                    res = await kirim();
                    continue;
                }
                if (perluAck(res)) {
                    const lanjut = await confirmPeringatanAbsen(res.message);
                    if (!lanjut) throw { code: 'BATAL', msg: 'Absen dibatalkan — peringatan belum Anda akui.' };
                    data.confirm = 1;
                    res = await kirim();
                    continue;
                }
                break;
            }

            if (res && !res.status && res.data && ((Array.isArray(res.data.sesi_belum_dijurnal) && res.data.sesi_belum_dijurnal.length > 0)
                    || (Array.isArray(res.data.sesi_menunggu_validasi) && res.data.sesi_menunggu_validasi.length > 0))) {
                throw { code: 'JURNAL', msg: (res.message || 'Jurnal mengajar belum lengkap.'), data: res.data };
            }
            if (!(res && res.status)) throw { code: 'SERVER', msg: (res && res.message) || 'Presensi belum tersimpan. Silakan ulangi beberapa saat lagi.' };
            if ($('facescan-modal').style.display !== 'flex') return;
            tampilSukses(res, {
                masuk: S.target === 'MASUK',
                dinas: mode.is_dinas === 1,
                wfh: mode.is_wfh === 1,
                offline: /offline/i.test(String(res.message || '')),
                lokasi: S.locName
            });
        }

        // Struk keberhasilan. Server sudah mengirim is_late / telat_menit /
        // status_pulang / pulang_cepat_menit / sapaan / nama_depan, tapi modal
        // FaceScan dulu membuang semuanya dan hanya menulis "Absen Masuk
        // Berhasil!" — guru tidak tahu ia tercatat telat sampai membuka rekap.
        function tampilSukses(res, ctx) {
            setProgress(100);
            $('facescan-modal').classList.add('is-success');
            const sub = $('facescan-subtitle');
            if (sub) sub.textContent = 'Presensi berhasil dicatat';
            const d = (res && res.data) || {};
            const menit = Math.max(0, parseInt(ctx.masuk ? d.telat_menit : d.pulang_cepat_menit, 10) || 0);
            const perluVerif = String(d.status_masuk || '').toUpperCase() === 'PENDING_VERIFICATION';
            const bukanMode = !ctx.dinas && !ctx.wfh;
            const telat = ctx.masuk && bukanMode && (d.is_late === true || menit > 0);
            const cepat = !ctx.masuk && bukanMode && (String(d.status_pulang || '').toUpperCase() === 'EARLY' || menit > 0);

            let ton = 'ok', teks = ctx.masuk ? 'Tepat Waktu' : 'Pulang Tepat Waktu';
            // Kalimat yang dibacakan. Default = judul struk, tapi untuk hasil
            // 'info' membacakan "berhasil" saat layarnya bilang "menunggu
            // verifikasi" justru membingungkan, jadi ucapannya ikut menyesuaikan.
            let ucapan = ctx.masuk ? 'Absen Masuk Berhasil' : 'Absen Pulang Berhasil';
            if (ctx.dinas) { ton = 'info'; teks = 'Tugas Luar · Menunggu Verifikasi'; ucapan = 'Pengajuan tugas luar terkirim, menunggu verifikasi admin'; }
            else if (ctx.wfh) { ton = 'info'; teks = 'WFH Tercatat'; ucapan = 'Absen WFH tercatat'; }
            else if (perluVerif) { ton = 'info'; teks = 'Menunggu Verifikasi Admin'; ucapan = 'Presensi tercatat, menunggu verifikasi admin'; }
            else if (telat) { ton = 'warn'; teks = 'Terlambat ' + menit + ' Menit'; }
            else if (cepat) { ton = 'warn'; teks = 'Pulang ' + menit + ' Menit Lebih Awal'; }
            else if (ctx.offline) { ton = 'info'; teks = 'Tersimpan Offline'; ucapan = 'Presensi tersimpan, akan terkirim saat jaringan pulih'; }

            $('facescan-success-title').textContent = ctx.masuk ? 'Absen Masuk Berhasil' : 'Absen Pulang Berhasil';

            const pill = $('facescan-success-pill');
            pill.className = 'fs-receipt-pill' + (ton === 'ok' ? '' : ' is-' + ton);
            pill.textContent = '';
            const ikonPill = document.createElement('i');
            ikonPill.className = 'bi ' + (ton === 'warn' ? 'bi-exclamation-circle-fill'
                : (ton === 'info' ? 'bi-info-circle-fill' : 'bi-check-circle-fill'));
            const teksPill = document.createElement('span');
            teksPill.textContent = teks;
            pill.appendChild(ikonPill);
            pill.appendChild(teksPill);

            const ic = $('facescan-success-icon');
            ic.className = 'rounded-full flex items-center justify-center text-white facescan-pop '
                + (ton === 'warn' ? 'bg-amber-500' : (ton === 'info' ? 'bg-sky-500' : 'bg-emerald-500'));

            const nama = [d.sapaan, d.nama_depan].filter(Boolean).join(' ').trim();
            let catatan = '';
            if (nama) {
                if (telat) catatan = 'Catat, ' + nama + ' — terlambat ' + menit + ' menit. Besok lebih awal, ya.';
                else if (cepat) catatan = 'Catat, ' + nama + ' — pulang ' + menit + ' menit lebih awal.';
                else catatan = 'Terima kasih, ' + nama + '. ' + (ctx.masuk ? 'Selamat bertugas.' : 'Hati-hati di jalan.');
            } else if (ctx.dinas) {
                catatan = 'Pengajuan tugas luar Anda dikirim ke admin untuk diverifikasi.';
            } else if (perluVerif) {
                catatan = 'Presensi Anda tercatat dan menunggu verifikasi admin.';
            }
            $('facescan-success-note').textContent = catatan;

            const baris = [['Pukul', witaHM() + ' WITA']];
            if (ctx.lokasi) baris.push(['Lokasi', ctx.lokasi]);
            const kotak = $('facescan-success-meta');
            kotak.textContent = '';
            baris.forEach(function (b) {
                const p = document.createElement('div');
                const ikon = document.createElement('i');
                ikon.className = 'bi ' + (b[0] === 'Pukul' ? 'bi-clock' : 'bi-geo-alt');
                const label = document.createElement('span');
                label.textContent = b[0] + ' ';
                const nilai = document.createElement('b');
                nilai.textContent = b[1];
                p.appendChild(ikon); p.appendChild(label); p.appendChild(nilai);
                kotak.appendChild(p);
            });

            $('facescan-success').style.display = 'block';
            setStatus('Berhasil', true);
            setHint(ton === 'warn' ? 'Presensi tersimpan — lihat statusnya di atas.' : 'Presensi tersimpan.');

            bunyi(ctx.masuk ? 'masuk' : 'pulang');
            if (ton === 'warn') setTimeout(() => bunyi('catatan'), 540);
            // Bacakan hasil setelah nada bel selesai agar tidak bertumpuk.
            ucapHasil(ucapan, ton === 'warn' ? 900 : 720);
            if (ton === 'ok' && typeof confetti === 'function') {
                try { confetti({ particleCount: 80, spread: 70, origin: { y: 0.6 } }); } catch (e) {}
            }
            if (S.dwellTimer) clearTimeout(S.dwellTimer);
            S.dwellTimer = setTimeout(() => { selesai(); }, 6000);
        }

        // Tutup struk lalu muat ulang agar kartu hari ini ikut diperbarui.
        function selesai() {
            if (S.sudahMuatUlang) return;
            S.sudahMuatUlang = true;
            if (S.dwellTimer) { clearTimeout(S.dwellTimer); S.dwellTimer = null; }
            closeModal();
            location.reload();
        }

        async function start() {
            if (S.busy) return;
            S.busy = true;
            resetUI();
            setStatus('Memulai pemindaian...'); setProgress(4);
            try {
                await gpsStage();
                await faceStage();
                await submitStage();
            } catch (e) {
                stopStream();
                const err = (e && e.msg) ? e : { code: 'UNKNOWN', msg: String((e && e.message) || e) };
                if (err.code === 'QUEUED') {
                    showError(rapikanPesan(err.msg), false, 'QUEUED');
                    setProgress(0);
                    bunyi('catatan');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Tersimpan di Perangkat',
                            text: 'Belum terkirim karena tidak ada koneksi. Jangan tutup aplikasi ini — presensi akan terkirim otomatis begitu online kembali.',
                            confirmButtonText: 'Mengerti',
                            confirmButtonColor: '#2563eb'
                        });
                    }
                } else if (err.code === 'JURNAL') {
                    bunyi('peringatan');
                    closeModal();
                    showJurnalGate(err.data || err.sessions || []);
                    setProgress(0);
                } else if (err.code === 'BATAL' || err.code === 'TEGURAN') {
                    // Guru membatalkan sendiri (tidak mengakui peringatan /
                    // menutup modal teguran): ini bukan kegagalan sistem —
                    // jangan tampilkan bingkai merah "Gagal memindai" yang
                    // membuatnya mengira absensi error.
                    closeModal();
                    bunyi('tik');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Absen Belum Dikirim',
                            text: err.msg + ' Ketuk tombol Absen lagi bila ingin melanjutkan.',
                            confirmButtonText: 'Mengerti',
                            confirmButtonColor: '#2563eb'
                        });
                    }
                } else {
                    tampilkanPenolakan(err);
                }
            } finally {
                S.busy = false;
            }
        }

        let scrollLocked = false;
        let hideTimer = null;
        function lockBody() {
            if (scrollLocked) return;
            scrollLocked = true;
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        }
        function unlockBody() {
            if (!scrollLocked) return;
            scrollLocked = false;
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }
        function openModal() {
            const m = $('facescan-modal');
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
            lockBody();
            m.style.display = 'flex';
            requestAnimationFrame(() => requestAnimationFrame(() => {
                m.style.opacity = '1';
                const c = $('facescan-cancel');
                if (c) c.focus({ preventScroll: true });
            }));
            requestAnimationFrame(() => {
                $('facescan-modal-card').style.transform = 'translateY(0)';
            });
        }
        function closeModal() {
            stopStream();
            unlockBody();
            $('facescan-modal').style.opacity = '0';
            $('facescan-modal-card').style.transform = 'translateY(24px)';
            if (hideTimer) clearTimeout(hideTimer);
            hideTimer = setTimeout(() => {
                hideTimer = null;
                $('facescan-modal').style.display = 'none';
            }, 300);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (window.Swal && Swal.isVisible && Swal.isVisible()) return;
            if ($('facescan-modal').style.display === 'flex') {
                e.preventDefault();
                if ($('facescan-modal').classList.contains('is-success')) selesai();
                else closeModal();
            }
        });

        async function open() {
            if (TARGET0 === 'DONE') {
                Swal.fire({ icon: 'info', title: 'Selesai', text: 'Anda sudah menyelesaikan absensi hari ini.' });
                return;
            }
            S.target = TARGET0;
            resetUI();
            openModal();
            setStatus('Menyiapkan...');
            const okT = await teguranGate();
            if (!okT) { closeModal(); return; }
            if (IS_HOLIDAY && S.target === 'MASUK') {
                const c = await Swal.fire({
                    title: 'Konfirmasi Hari Libur',
                    html: '<p>' + HOLIDAY_REASON + '</p><small style="color:#64748b;">Yakin tetap absen?</small>',
                    icon: 'question', showCancelButton: true,
                    confirmButtonColor: '#f59e0b', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal'
                });
                if (!c.isConfirmed) { closeModal(); return; }
            }
            applyTargetBadge();
            start();
        }

        // Alihkan tombol Absen (aplikasi & navbar) ke modal FaceScan
        if (window.jQuery) {
            jQuery(document).off('click', '#btn-absen');
            jQuery(document).off('click', '#nav-btn-absen');
            jQuery(document).on('click', '#btn-absen', (e) => { e.preventDefault(); open(); });
            jQuery(document).on('click', '#nav-btn-absen', (e) => { e.preventDefault(); open(); });
        }

        // Coba kirim antrean offline saat halaman dibuka
        try { Outbox.flush(); } catch (e) {}
        sinkronTombolSuara();

        return {
            open: open,
            close: closeModal,
            selesai: selesai,
            restart: () => { if (!S.busy) start(); },
            toggleSound: toggleSuara,
            legacy: () => { closeModal(); if (window.handleScanClick) window.handleScanClick(); }
        };
    })();

</script>
<?= $this->endSection() ?>
