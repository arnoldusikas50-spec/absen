<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= \App\Libraries\Theme::metaColor('guru') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Masuk | <?= esc($setting->nama_sekolah ?? 'Presensi & KBM') ?></title>

    <?php
        $logo_app = !empty($setting->logo_kiri) ? base_url(ltrim($setting->logo_kiri, '/')) : base_url('assets/img/favicon.png');
        $app_name = !empty($setting->nama_aplikasi ?? null) ? $setting->nama_aplikasi : ($setting->nama_sekolah ?? 'Presensi');
    ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <!-- PWA -->
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Presensi">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="application-name" content="Presensi">
    <meta name="description" content="Aplikasi presensi guru mobile: check-in/out GPS, verifikasi wajah, izin, riwayat & notifikasi.">
    <link rel="apple-touch-icon" href="<?= base_url('icons/apple-touch-icon.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('icons/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('icons/icon-192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= base_url('icons/icon-512.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
            --text:        #111827;
            --muted:       #6b7789;
            --surface:     #ffffff;
            --field:       #f1f2f4;
            --field-hover: #ebedf0;
            --font: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            --font-display: 'Outfit', 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 90px 20px 44px;
            font-family: var(--font);
            color: var(--text);
            background:
                radial-gradient(90% 70% at 16% 0%, rgb(var(--brand-200-rgb) / .85) 0%, transparent 62%),
                radial-gradient(75% 60% at 90% 102%, rgb(var(--brand-tertiary-rgb) / .14) 0%, transparent 60%),
                linear-gradient(180deg, #fdf7f6 0%, #fcf8ef 100%);
            -webkit-font-smoothing: antialiased;
        }

        ::selection { background: rgb(var(--brand-600-rgb) / .2); }

        :where(a, button, input):focus-visible {
            outline: 2px solid var(--brand-600);
            outline-offset: 3px;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .skip-link {
            position: absolute;
            left: -9999px;
            top: 10px;
            z-index: 20;
            padding: 10px 14px;
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(2, 6, 23, .18);
        }
        .skip-link:focus { left: 16px; }

        .login-shell {
            width: 100%;
            max-width: 376px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── Kartu ── */
        .login-card {
            position: relative;
            width: 100%;
            padding: 62px 30px 30px;
            border-radius: 40px;
            background: var(--surface);
            box-shadow:
                0 34px 70px -34px rgba(76, 26, 40, .38),
                0 14px 30px -20px rgba(76, 26, 40, .18);
            animation: cardIn .55s cubic-bezier(.16, 1, .3, 1) both;
        }

        .avatar {
            position: absolute;
            top: -48px;
            left: 50%;
            transform: translateX(-50%);
            display: grid;
            place-items: center;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #fff;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgb(var(--brand-600-rgb) / .1),
                0 16px 30px -14px rgba(76, 26, 40, .35);
        }
        .avatar img {
            width: 100%;
            height: 100%;
            padding: 15px;
            object-fit: contain;
        }

        .brand-head {
            margin: 2px 0 20px;
            text-align: center;
        }
        .brand-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: .06em;
            color: var(--text);
            overflow-wrap: anywhere;
        }

        /* ── Field ── */
        .field + .field { margin-top: 12px; }

        .control { position: relative; }

        .field-icon {
            position: absolute;
            left: 17px;
            top: 50%;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            color: #3f4756;
            pointer-events: none;
            transition: color .15s;
        }
        .control:focus-within .field-icon { color: var(--brand-600); }

        .input {
            width: 100%;
            height: 46px;
            padding: 0 46px 0 47px;
            border: 0;
            border-radius: 999px;
            background: var(--field);
            font-family: var(--font);
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            transition: background .15s, box-shadow .15s;
        }
        .input::placeholder { color: #6f7785; font-weight: 500; }
        .input:hover { background: var(--field-hover); }
        .input:focus {
            outline: none;
            background: #fff;
            box-shadow:
                0 0 0 1.5px var(--brand-600),
                0 0 0 4px rgb(var(--brand-600-rgb) / .14);
        }
        .input:focus-visible { outline: none; }
        .input:-webkit-autofill,
        .input:-webkit-autofill:hover {
            -webkit-box-shadow: 0 0 0 1000px var(--field) inset;
            -webkit-text-fill-color: var(--text);
        }
        .input:-webkit-autofill:focus {
            -webkit-box-shadow:
                0 0 0 1000px #fff inset,
                0 0 0 1.5px var(--brand-600),
                0 0 0 4px rgb(var(--brand-600-rgb) / .14);
            -webkit-text-fill-color: var(--text);
        }

        .reveal {
            position: absolute;
            right: 8px;
            top: 50%;
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            padding: 0;
            transform: translateY(-50%);
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            transition: background .15s, color .15s;
        }
        .reveal svg { width: 17px; height: 17px; }
        .reveal:hover { background: rgba(15, 23, 42, .06); color: #111827; }

        .caps-hint {
            display: block;
            margin-top: 6px;
            padding-right: 6px;
            font-size: 10.5px;
            font-weight: 600;
            text-align: right;
            color: #b45309;
        }
        .caps-hint[hidden] { display: none; }

        /* ── Ingat username & bantuan ── */
        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 15px;
            font-size: 12px;
            color: var(--muted);
        }
        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
        }
        .remember input {
            appearance: none;
            -webkit-appearance: none;
            flex: none;
            display: grid;
            place-items: center;
            width: 17px;
            height: 17px;
            margin: 0;
            border: 1.5px solid #c9ced6;
            border-radius: 5px;
            background: #fff;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }
        .remember input::after {
            content: "";
            width: 9px;
            height: 5px;
            margin-top: -2px;
            border-left: 2px solid #fff;
            border-bottom: 2px solid #fff;
            transform: rotate(-45deg) scale(0);
            transition: transform .15s;
        }
        .remember input:checked {
            border-color: var(--brand-600);
            background: var(--brand-600);
        }
        .remember input:checked::after { transform: rotate(-45deg) scale(1); }
        .link {
            padding: 0;
            border: 0;
            background: none;
            font-family: var(--font);
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
        }
        .link:hover { color: var(--brand-700); text-decoration: underline; }

        .forgot-help {
            margin: 10px 0 0;
            padding: 8px 12px;
            border-radius: 12px;
            background: #f6f8fb;
            font-size: 11.5px;
            line-height: 1.5;
            color: #556070;
        }
        .forgot-help[hidden] { display: none; }

        /* ── Peringatan inline ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px solid;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.5;
        }
        .alert[hidden] { display: none; }
        .alert[data-kind="error"]   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .alert[data-kind="success"] { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
        .alert .alert__icon { width: 15px; height: 15px; flex: none; margin-top: 1px; }
        .alert[data-kind="error"] .alert__icon--success,
        .alert[data-kind="success"] .alert__icon--error { display: none; }

        /* ── Tombol ── */
        .btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 46px;
            margin-top: 20px;
            border: 0;
            border-radius: 999px;
            background: var(--brand-600);
            color: #fff;
            font-family: var(--font);
            font-size: 13.5px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 14px 26px -14px rgb(var(--brand-600-rgb) / .75);
            transition: background .18s, transform .18s, box-shadow .18s;
        }
        .btn-login:not(:disabled):hover {
            background: var(--brand-700);
            transform: translateY(-1px);
            box-shadow: 0 18px 30px -14px rgb(var(--brand-600-rgb) / .8);
        }
        .btn-login:active { transform: translateY(0) scale(.997); }
        .btn-login:disabled { opacity: .72; cursor: progress; transform: none; }
        .btn-login.is-success {
            background: #047857;
            box-shadow: 0 14px 26px -14px rgba(4, 120, 87, .6);
        }

        .btn-spinner {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255, 255, 255, .35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        /* ── Google ── */
        .google-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 18px;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
        }
        .google-link img { width: 16px; height: 16px; }
        .google-link:hover { color: var(--brand-700); }

        /* ── Footer ── */
        .page-foot {
            margin: 0;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            text-align: center;
            color: #7b8494;
        }
        .page-footer {
            margin-top: 22px;
            text-align: center;
        }
        .page-version {
            margin: 6px 0 0;
            font-size: 10px;
            color: #98a1ae;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 560px) {
            body { padding: 82px 16px 34px; }
            .login-card {
                padding: 58px 24px 26px;
                border-radius: 34px;
            }
            .avatar { top: -44px; width: 88px; height: 88px; }
            .avatar img { padding: 13px; }
            .input { height: 48px; font-size: 16px; }
            .btn-login { height: 48px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
            }
        }
    </style>
    <?= \App\Libraries\Theme::loginStyleTag() ?>
</head>
<body>

<a class="skip-link" href="#formLogin">Lewati ke formulir masuk</a>

<?php $flashError = session()->getFlashdata('error'); $flashSuccess = session()->getFlashdata('success'); $flashMsg = $flashError ?: $flashSuccess; ?>

<div class="login-shell">

    <div class="login-card">

        <div class="avatar">
            <img src="<?= esc($logo_app) ?>"
                 alt="<?= esc($setting->nama_sekolah ?? 'Logo sekolah') ?>"
                 onerror="this.onerror=null;this.src='<?= base_url('assets/img/favicon.png') ?>'">
        </div>

        <div class="brand-head">
            <h1 class="brand-title"><?= esc($app_name) ?></h1>
        </div>

        <form id="formLogin" method="post" action="<?= site_url('auth/login') ?>">
            <?= csrf_field() ?>

            <div class="field">
                <label class="sr-only" for="usernameInput">Username</label>
                <div class="control">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/></svg>
                    <input class="input" type="text"
                           name="username" id="usernameInput"
                           placeholder="Username"
                           required autocomplete="username"
                           autocapitalize="none" spellcheck="false">
                </div>
            </div>

            <div class="field">
                <label class="sr-only" for="passwordInput">Kata sandi</label>
                <div class="control">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
                    <input class="input" type="password"
                           name="password" id="passwordInput"
                           placeholder="Kata sandi"
                           required autocomplete="current-password">
                    <button type="button" class="reveal" id="togglePw"
                            aria-pressed="false" aria-label="Tampilkan kata sandi">
                        <svg id="eyeOn" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg id="eyeOff" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none"><path d="M3 3l18 18"/><path d="M10.6 6.2A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3 3.6"/><path d="M6.3 6.5A16.8 16.8 0 0 0 2.5 12S6 18 12 18a9.6 9.6 0 0 0 4.2-.95"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                <span class="caps-hint" id="capsHint" hidden>Caps Lock aktif</span>
            </div>

            <div class="meta-row">
                <label class="remember" for="rememberUsername">
                    <input type="checkbox" id="rememberUsername">
                    <span>Ingat username</span>
                </label>
                <button type="button" class="link" id="forgotBtn" aria-expanded="false" aria-controls="forgotHelp">Lupa kata sandi?</button>
            </div>
            <p class="forgot-help" id="forgotHelp" role="status" hidden>Hubungi admin sekolah untuk mengatur ulang kata sandi.</p>

            <div class="alert" id="formAlert" data-kind="<?= $flashError ? 'error' : 'success' ?>" role="alert"<?= $flashMsg ? '' : ' hidden' ?>>
                <svg class="alert__icon alert__icon--error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                <svg class="alert__icon alert__icon--success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.7-5"/></svg>
                <span class="alert__text" id="formAlertText"><?= esc($flashMsg ?? '') ?></span>
            </div>

            <button type="submit" class="btn-login" id="btnLogin">
                <span id="btn-text">Masuk</span>
                <span id="btn-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </span>
            </button>
        </form>

        <?php if (($setting->google_login_enabled ?? 1)): ?>
        <div style="text-align:center">
            <a href="<?= site_url('presensi/google') ?>" class="google-link">
                <img src="<?= base_url('assets/img/google-icon.svg') ?>"
                     onerror="this.src='https://www.svgrepo.com/show/475656/google-color.svg'"
                     width="16" height="16" alt="">
                <span>Masuk dengan Google</span>
            </a>
        </div>
        <?php endif; ?>

    </div>

    <footer class="page-footer">
        <p class="page-foot"><?= esc($setting->nama_sekolah ?? 'Presensi & KBM') ?></p>
        <p class="page-version">IKASMEDIA &middot; v2.2.0</p>
    </footer>
</div>

<script>
// ── Form login ────────────────────────────────────────────────────────────────
(function () {
    var form = document.getElementById('formLogin');
    if (!form) return;

    var btn = document.getElementById('btnLogin');
    var btnText = document.getElementById('btn-text');
    var btnIcon = document.getElementById('btn-icon');
    var alertBox = document.getElementById('formAlert');
    var alertText = document.getElementById('formAlertText');
    var username = document.getElementById('usernameInput');
    var password = document.getElementById('passwordInput');
    var remember = document.getElementById('rememberUsername');
    var REMEMBER_KEY = 'presensi.login.username';

    var defaultLabel = btnText.textContent;
    var defaultIcon = btnIcon.innerHTML;

    // Prefill username yang pernah diingat (hanya username, bukan sesi login).
    try {
        var savedUser = localStorage.getItem(REMEMBER_KEY);
        if (savedUser) {
            username.value = savedUser;
            remember.checked = true;
        }
    } catch (e) {}

    remember.addEventListener('change', function () {
        if (!remember.checked) {
            try { localStorage.removeItem(REMEMBER_KEY); } catch (e) {}
        }
    });

    function setAlert(kind, message) {
        alertBox.dataset.kind = kind;
        alertText.textContent = message;
        alertBox.hidden = false;
    }

    function busy() {
        btn.disabled = true;
        btn.classList.remove('is-success');
        btnText.textContent = 'Memeriksa…';
        btnIcon.innerHTML = '<span class="btn-spinner"></span>';
    }

    function reset() {
        btn.disabled = false;
        btn.classList.remove('is-success');
        btnText.textContent = defaultLabel;
        btnIcon.innerHTML = defaultIcon;
    }

    function succeed() {
        btn.classList.add('is-success');
        btnText.textContent = 'Berhasil';
        btnIcon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="m5 12.5 4.5 4.5L19 7"/></svg>';
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        alertBox.hidden = true;
        busy();

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (resp) {
            if (!resp.ok) throw { http: resp.status };
            return resp.json().then(function (json) { return json; }, function () { throw { http: resp.status, parse: true }; });
        }).then(function (res) {
            if (res && res.status) {
                try {
                    if (remember.checked) localStorage.setItem(REMEMBER_KEY, username.value.trim());
                    else localStorage.removeItem(REMEMBER_KEY);
                } catch (e) {}
                succeed();
                var go = (res.data && res.data.redirect) ? res.data.redirect : <?= json_encode(site_url('dashboard')) ?>;
                setTimeout(function () { window.location.href = go; }, 600);
                return;
            }
            reset();
            setAlert('error', (res && res.message) || 'Username atau kata sandi salah.');
        }).catch(function (err) {
            reset();
            var msg;
            if (err && err.http === 403) {
                msg = 'Sesi halaman sudah berakhir. Muat ulang halaman lalu coba lagi.';
            } else if (err && err.http === 419) {
                msg = 'Token keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            } else if (err && err.parse) {
                msg = 'Respons server tidak dikenali (' + err.http + '). Muat ulang halaman.';
            } else if (err && err.http) {
                msg = 'Server menolak permintaan (' + err.http + '). Coba beberapa saat lagi.';
            } else {
                msg = 'Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.';
            }
            setAlert('error', msg);
        });
    });

    [username, password].forEach(function (input) {
        input.addEventListener('input', function () {
            if (alertBox.dataset.kind === 'error' && !alertBox.hidden) alertBox.hidden = true;
        });
    });
})();

// ── Tampilkan / sembunyikan kata sandi ───────────────────────────────────────
(function () {
    var toggle = document.getElementById('togglePw');
    var input = document.getElementById('passwordInput');
    var eyeOn = document.getElementById('eyeOn');
    var eyeOff = document.getElementById('eyeOff');
    if (!toggle || !input) return;

    toggle.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        toggle.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        eyeOn.style.display = show ? 'none' : '';
        eyeOff.style.display = show ? '' : 'none';
    });
})();

// ── Peringatan Caps Lock ─────────────────────────────────────────────────────
(function () {
    var input = document.getElementById('passwordInput');
    var hint = document.getElementById('capsHint');
    if (!input || !hint) return;

    function update(e) {
        if (typeof e.getModifierState !== 'function') return;
        hint.hidden = !e.getModifierState('CapsLock');
    }
    input.addEventListener('keydown', update);
    input.addEventListener('keyup', update);
    input.addEventListener('blur', function () { hint.hidden = true; });
})();

// ── Bantuan lupa kata sandi ──────────────────────────────────────────────────
(function () {
    var btn = document.getElementById('forgotBtn');
    var help = document.getElementById('forgotHelp');
    if (!btn || !help) return;

    btn.addEventListener('click', function () {
        var open = help.hidden;
        help.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();

// ── PWA: daftarkan service worker same-origin ────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register(location.origin + '/sw.js', { scope: '/' }).then(function (reg) {
            if (reg && reg.update) reg.update();
        }).catch(function (err) {
            console.warn('SW register gagal:', err);
        });
    });
}
</script>
</body>
</html>
