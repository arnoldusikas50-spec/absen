<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="<?= \App\Libraries\Theme::metaColor('guru') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Sistem Presensi & Biometrik - Login</title>
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.png') ?>" type="image/x-icon">
    
    <!-- Tailwind Icons -->
    <link href="<?= base_url('assets/plugins/bootstrap-icon/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans / Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (preflight OFF agar tidak mereset base Tailwind) -->
    <style type="text/css">
        :root {
            --primary: var(--brand-600);
            --primary-rgb: 225, 29, 72;
            --secondary: var(--brand-secondary);
            --accent: #f59e0b;
            --surface: #ffffff;
            --on-surface: #0f172a;
            --text-muted: #64748b;
            --brand-gradient: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-secondary) 50%, #f59e0b 100%);
            --brand-glow: 0 10px 25px -5px rgb(var(--brand-600-rgb) / 0.35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        /* Ambient Dynamic Background */
                .mesh-bg::before, .mesh-bg::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.18;
            animation: meshPulse 16s infinite alternate ease-in-out;
        }

        .mesh-bg::before {
            background: radial-gradient(circle, var(--brand-600) 0%, var(--brand-secondary) 100%);
            top: -120px;
            right: -80px;
        }

        .mesh-bg::after {
            background: radial-gradient(circle, #f59e0b 0%, var(--brand-secondary) 100%);
            bottom: -120px;
            left: -80px;
            animation-delay: -8s;
        }

        @keyframes meshPulse {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(60px, 60px) scale(1.18); }
        }

        .login-container {
            max-width: 430px;
            animation: cardEntrance 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 32px;
            padding: 36px 28px 28px;
            box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.6);
        }

        /* Top Accent Rainbow Line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--brand-gradient);
        }

        /* Face ID Biometric Header Icon */
                        .face-id-glow {
            width: 76px;
            height: 76px;
            background: var(--brand-gradient);
            filter: blur(14px);
            opacity: 0.45;
            animation: glowBreath 4s infinite alternate ease-in-out;
        }

        @keyframes glowBreath {
            0% { opacity: 0.3; transform: scale(0.95); }
            100% { opacity: 0.6; transform: scale(1.1); }
        }

        .face-id-box {
            width: 72px;
            height: 72px;
            background: var(--brand-gradient);
            border-radius: 22px;
            box-shadow: 0 10px 25px -4px rgb(var(--brand-600-rgb) / 0.35);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
        }

        .face-id-svg {
            width: 44px;
            height: 44px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
            transition: all 0.3s ease;
        }

        /* Scanning Beam Animation inside Face ID */
        .scan-beam {
            height: 3px;
            background: linear-gradient(90deg, transparent, #ffffff, transparent);
            box-shadow: 0 0 10px #ffffff;
            animation: scanMove 2.8s ease-in-out infinite alternate;
            opacity: 0.75;
        }

        @keyframes scanMove {
            0% { top: 12%; opacity: 0.3; }
            50% { opacity: 0.9; }
            100% { top: 86%; opacity: 0.3; }
        }

        .brand-title {
            font-size: 24px;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            gap: 5px;
            font-size: 12px;
        }

        /* Premium Form Controls */
                .input-icon-left {
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
        }

        .form-control-custom {
            height: 52px;
            padding: 0 46px 0 46px;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            font-size: 14.5px;
        }

        .form-control-custom::placeholder {
            color: #94a3b8;
            font-weight: 500;
            font-size: 13.5px;
        }

        .form-control-custom:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.12);
            outline: none;
        }

        .input-group-custom:focus-within .input-icon-left {
            color: var(--primary);
        }

        .password-toggle {
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.15rem;
        }

        .password-toggle:hover {
            color: var(--on-surface);
        }

        /* Action Buttons */
        .btn-login {
            background: var(--brand-gradient);
            height: 52px;
            font-size: 15px;
            margin-top: 6px;
            gap: 8px;
            box-shadow: var(--brand-glow);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -4px rgb(var(--brand-600-rgb) / 0.45);
            color: #ffffff;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .divider {
            margin: 18px 0;
            font-size: 11px;
            letter-spacing: 1.2px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1.5px solid #e2e8f0;
        }

        .divider:not(:empty)::before { margin-right: 14px; }
        .divider:not(:empty)::after { margin-left: 14px; }

        .btn-google {
            border: 1.5px solid #e2e8f0;
            height: 48px;
            font-size: 14px;
            gap: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }

        .btn-google:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

                .version-badge {
            gap: 6px;
            font-size: 11px;
        }

        .security-badge {
            gap: 6px;
            font-size: 11px;
        }

        /* Custom SweetAlert */
        .swal2-popup.premium-swal {
            background: #ffffff !important;
            color: var(--on-surface) !important;
            border-radius: 28px !important;
            padding: 2rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }
        .swal2-title { color: var(--on-surface) !important; font-weight: 800 !important; }
        .swal2-confirm { background: var(--brand-gradient) !important; border-radius: 14px !important; font-weight: 700 !important; }
    </style>
    <?= \App\Libraries\Theme::loginStyleTag() ?>
</head>
<body>
    <div class="mesh-bg"></div>

    <div class="login-container">
        <div class="login-card">
            <!-- Brand Header with Face ID Biometric Icon -->
            <div class="app-brand">
                <div class="face-id-wrapper">
                    <div class="face-id-glow"></div>
                    <div class="face-id-box">
                        <!-- Face ID SVG Icon -->
                        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" class="face-id-svg">
                            <!-- Outer Corners -->
                            <path d="M12 21V15C12 13.3431 13.3431 12 15 12H21" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M43 12H49C50.6569 12 52 13.3431 52 15V21" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M52 43V49C52 50.6569 50.6569 52 49 52H43" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 52H15C13.3431 52 12 50.6569 12 49V43" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            
                            <!-- Face Silhouette & Features -->
                            <circle cx="24" cy="27" r="2.8" fill="#ffffff"/>
                            <circle cx="40" cy="27" r="2.8" fill="#ffffff"/>
                            <path d="M32 25V33L29 35" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 41C27 44.5 37 44.5 40 41" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round"/>
                        </svg>
                        <div class="scan-beam"></div>
                    </div>
                </div>
                <h1 class="brand-title">Absensi</h1>
                <div class="brand-subtitle"><i class="bi bi-mortarboard-fill"></i><?= esc($setting->nama_sekolah ?? $setting->nama_aplikasi ?? 'Presensi Guru') ?></div>
            </div>

            <!-- Login Form -->
            <form id="formLogin">
                <!-- Username / NIP -->
                <div class="input-group-custom">
                    <i class="bi bi-person-fill input-icon-left"></i>
                    <input type="text" class="form-control-custom" name="username" id="usernameInput" placeholder="Username" required autofocus autocomplete="username">
                </div>

                <!-- Password -->
                <div class="input-group-custom">
                    <i class="bi bi-key-fill input-icon-left"></i>
                    <input type="password" class="form-control-custom" name="password" id="passwordInput" placeholder="Kata Sandi" required autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Tampilkan Password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="btnLogin">
                    <span> L O G I N </span>
                </button>

                <!-- Google OAuth Button -->
                <?php if ($setting->google_login_enabled ?? 1): ?>
                <div class="divider">Atau</div>

                <a href="<?= site_url('presensi/google') ?>" class="btn-google">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="19" height="19" alt="Google">
                    <span>Masuk dengan Google</span>
                </a>
                <?php endif; ?>
            </form>

            <!-- Footer Badge -->
            <div class="login-footer">
                <div class="version-badge">
                    <span class="inline-block rounded-full bg-emerald-500 p-1"></span>
                    <span>v2.2.0 • ikasmedia</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.all.min.js') ?>"></script>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        $('#formLogin').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btnLogin');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white me-2 align-middle"></span> Memverifikasi...');

            $.post('<?= site_url('presensi/auth') ?>', $(this).serialize(), function(res) {
                if (res.status) {
                    btn.html('<i class="bi bi-check2-circle text-xl me-2"></i> Berhasil!');
                    const redirectUrl = (res.data && res.data.redirect) ? res.data.redirect : '<?= site_url('presensi') ?>';
                    setTimeout(() => window.location.href = redirectUrl, 400);
                } else {
                    btn.prop('disabled', false).html(originalHtml);
                    Swal.fire({
                        icon: 'error',
                        title: 'Akses Ditolak',
                        text: res.message,
                        confirmButtonColor: '#e11d48',
                        customClass: { popup: 'premium-swal' }
                    });
                }
            }, 'json').fail(() => {
                btn.prop('disabled', false).html(originalHtml);
                Swal.fire({
                    icon: 'error',
                    title: 'Error Jaringan',
                    text: 'Gagal menghubungi server. Silakan periksa koneksi internet Anda.',
                    confirmButtonColor: '#e11d48',
                    customClass: { popup: 'premium-swal' }
                });
            });
        });

        // Error handling for flashdata
        <?php if (session()->getFlashdata('error')): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: '<?= esc(session()->getFlashdata('error')) ?>',
                confirmButtonColor: '#e11d48',
                customClass: { popup: 'premium-swal' }
            });
        <?php endif; ?>
    </script>
</body>
</html>