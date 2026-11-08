<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title><?= esc($judul ?? 'Dashboard') ?> | absensi by ikasmedia</title>

    <?php
    $setting = $setting ?? new \stdClass();
    $setting->logo_kiri = $setting->logo_kiri ?? null;
    $logo_app = ($setting->logo_kiri == null) ? base_url('assets/img/favicon.png') : base_url('' . $setting->logo_kiri);
    ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/bootstrap-icon/bootstrap-icons.css') ?>">

    <!-- Tailwind CSS CDN -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.4s ease-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(16px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' },
                        },
                    },
                }
            }
        }
    </script>

    <!-- DataTables CSS (vanilla - tanpa Bootstrap theme) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.css') ?>">
    <!-- Toastr -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/toastr/toastr.min.css') ?>">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/select2/css/select2.min.css') ?>">
    <!-- Tempusdominus -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/tempus-dominus/tempus-dominus.min.css') ?>">
    <!-- Summernote -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/summernote/summernote-lite.min.css') ?>">
    <!-- Dropify -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/dropify/css/dropify.min.css') ?>">
    <!-- Datetime picker -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/jquery-datetimepicker/jquery.datetimepicker.min.css') ?>">
    <!-- Pignose calendar -->
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/plugins/pignose/css/pignose.calendar.css') ?>">
    <!-- KaTeX -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/katex/katex.css') ?>">
    <!-- Toastr show -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/show.toast.css') ?>">
    <!-- jQuery -->
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/jquery-ui/jquery-ui.min.js') ?>"></script>

    <!-- App Custom CSS + Tailwind + Bootstrap shim -->
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/app/css/tailwind-admin.css') ?>">

    <style>
        /* Sidebar transition */
        #main-sidebar { transition: transform 0.3s ease, width 0.3s ease; }
        #main-content  { transition: margin-left 0.3s ease; }

        /* Scrollbar slim */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Print page */
        page { background: white; display: block; margin: 0 auto; margin-bottom: 0.5cm; }
        page[size="A4"][layout="potrait"] { width: 21cm; height: 29.7cm; }
        page[size="A4"] { width: 29.7cm; height: 21cm; }

        /* Animations */
        .fade-in-up { animation: fadeInUp 0.4s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Select2 Tailwind override */
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            padding: 0.25rem 0.75rem !important;
            display: flex; align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b; line-height: 1.5rem; padding: 0;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important; right: 8px !important;
        }
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            min-height: 38px !important;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1) !important;
            overflow: hidden;
        }
        .select2-container--default .select2-results__option--highlighted {
            background-color: var(--primary-500) !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.75rem !important;
        }

        /* DataTables Tailwind */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.25rem 0.75rem;
            outline: none;
            font-size: 0.875rem;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 2px rgb(var(--primary-500-rgb) / 0.15);
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.625rem !important;
            margin: 0 2px;
            border-radius: 0.375rem !important;
            border: 1px solid #e2e8f0 !important;
            font-size: 0.875rem;
            cursor: pointer;
            background: white !important;
            color: #374151 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            border-color: var(--primary-500) !important;
            color: var(--primary-500) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-500) !important;
            border-color: var(--primary-500) !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.4; cursor: not-allowed;
        }
        .dataTables_wrapper .dataTables_info { font-size: 0.8rem; color: #6b7280; }

        /* Toastr override */
        #toast-container > .toast { border-radius: 0.5rem !important; }

        /* Summernote override */
        .note-editor.note-frame { border: 1px solid #e2e8f0 !important; border-radius: 0.5rem !important; overflow: hidden; }

        /* Dropify override */
        .dropify-wrapper { border: 2px dashed #e2e8f0 !important; border-radius: 0.5rem !important; }
        .dropify-wrapper:hover { border-color: var(--primary-500) !important; background: var(--primary-50) !important; }
    </style>

    <script>
        let base_url = '<?= rtrim(base_url(), '/') ?>/';
        let globalToken;
        let CSRF_NAME = '<?= csrf_token() ?>';
        let CSRF_HASH = '<?= csrf_hash() ?>';

        let tp_active  = '<?= isset($tp_active)  ? $tp_active->tahun  : "" ?>';
        let smt_active = '<?= isset($smt_active) ? $smt_active->smt   : "" ?>';
        let id_tp_active  = '<?= isset($tp_active)  ? $tp_active->id_tp  : "" ?>';
        let id_smt_active = '<?= isset($smt_active) ? $smt_active->id_smt : "" ?>';

        const dt_lang_id = {
            "emptyTable":    "Tidak ada data yang tersedia",
            "info":          "Menampilkan _START_ – _END_ dari _TOTAL_ entri",
            "infoEmpty":     "Menampilkan 0 sampai 0 dari 0 entri",
            "infoFiltered":  "(difilter dari _MAX_ total entri)",
            "lengthMenu":    "Tampilkan _MENU_ entri",
            "loadingRecords":"Memuat...",
            "processing":    "Sedang memproses...",
            "search":        "Cari:",
            "zeroRecords":   "Tidak ditemukan data yang sesuai",
            "paginate": {
                "first":    "Pertama",
                "last":     "Terakhir",
                "next":     "Selanjutnya",
                "previous": "Sebelumnya"
            }
        };

        function startTime() {
            var t = new Date();
            var h = String(t.getHours()).padStart(2,'0');
            var m = String(t.getMinutes()).padStart(2,'0');
            var s = String(t.getSeconds()).padStart(2,'0');
            var el = document.getElementById('live-clock');
            if (el) el.innerHTML = '<span class="font-bold">' + h + ':' + m + '</span><span class="opacity-70">:' + s + '</span>';
            setTimeout(startTime, 1000);
        }

        function ajaxcsrf() {
            var csrf = {};
            csrf['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
            $.ajaxSetup({ "data": csrf });
        }

        function logout() {
            Swal.fire({
                title: "Logout",
                text: "Anda yakin ingin logout?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Logout!'
            }).then((result) => {
                if (result.value) location.href = "<?= site_url('auth/logout') ?>";
            });
        }

        function showToast(type, msg) {
            if (type === 'success') showSuccessToast(msg);
            else if (type === 'error') showDangerToast(msg);
            else showInfoToast(msg);
        }

        function buat_tanggal_indonesia(str) {
            if (!str) return "";
            str = str.replace("Jan", "Januari").replace("Feb", "Februari").replace("Mar", "Maret").replace("Apr", "April")
                .replace("May", "Mei").replace("Jun", "Juni").replace("Jul", "Juli").replace("Aug", "Agustus")
                .replace("Sep", "September").replace("Oct", "Oktober").replace("Nov", "Nopember").replace("Dec", "Desember");
            str = str.replace("Mon", "Senin").replace("Tue", "Selasa").replace("Wed", "Rabu").replace("Thu", "Kamis")
                .replace("Fri", "Jumat").replace("Sat", "Sabtu").replace("Sun", "Minggu");
            return str;
        }
    </script>
    <script defer src="<?= base_url('assets/plugins/katex/contrib/auto-render.min.js') ?>"
        onload="renderMathInElement(document.body);"></script>

    <?= $this->renderSection('styles') ?>
</head>

<body class="bg-slate-50 font-sans text-slate-800 h-full" onload="startTime()">

    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden" onclick="closeSidebar()"></div>

    <div class="flex h-screen overflow-hidden">