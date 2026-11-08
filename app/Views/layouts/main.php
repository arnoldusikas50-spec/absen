<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title><?= esc($judul ?? 'Dashboard') ?> | <?= esc($setting->nama_sekolah ?? 'Presensi & KBM') ?></title>

    <?php
    $setting = $setting ?? new \stdClass();
    $setting->logo_kiri = $setting->logo_kiri ?? null;
    $logo_app = ($setting->logo_kiri == null) ? base_url('assets/img/favicon.png') : base_url('' . $setting->logo_kiri);
    ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= base_url('assets/plugins/bootstrap-icon/bootstrap-icons.css') ?>">

    <!-- Tailwind CSS (build lokal — npm run build) -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/tailwind.css') ?>?v=<?= filemtime(FCPATH . 'assets/app/css/tailwind.css') ?>">

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
    <!-- Toastr show -->
    <link rel="stylesheet" href="<?= base_url('assets/app/css/show.toast.css') ?>">
    <!-- jQuery -->
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/jquery-ui/jquery-ui.min.js') ?>"></script>

    <!-- App Custom CSS + Tailwind overrides -->
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/app/css/tailwind-admin.css') ?>">

    <style>
        /* Modal Anti-Flash Critical CSS (mencegah modal muncul sekilas saat load halaman) */
        .modal:not(.is-open):not(.show):not(.active) {
            display: none !important;
        }
        .modal.hidden {
            display: none !important;
        }
        .modal.is-open, .modal.show, .modal.active {
            display: flex !important;
        }

        /* Sidebar transition */
        #main-sidebar {
            transition: transform 0.28s cubic-bezier(.4,0,.2,1), width 0.28s cubic-bezier(.4,0,.2,1);
        }
        /* Sidebar collapsed state on desktop */
        #main-sidebar.collapsed { width: 4.5rem !important; }
        #main-sidebar.collapsed .sidebar-label { display: none !important; }
        #main-sidebar.collapsed .treeview-arrow { display: none !important; }
        #main-sidebar.collapsed nav { padding-left: 0.5rem; padding-right: 0.5rem; }
        #main-sidebar.collapsed li a,
        #main-sidebar.collapsed li button { justify-content: center; padding-left: 0; padding-right: 0; }
        #main-sidebar.collapsed .sidebar-submenu { display: none !important; }
        /* Sidebar bg gradient */
        #main-sidebar {
            background: linear-gradient(160deg, #0f1729 0%, #111827 50%, #0c1322 100%);
        }
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

        /* ── SweetAlert2: brand theme (seluruh halaman admin) ── */
        .swal2-popup {
            border-radius: 22px !important;
            padding: 1.5rem 1.5rem 1.25rem !important;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif !important;
            box-shadow: 0 24px 64px rgba(15,23,42,.22) !important;
        }
        .swal2-title {
            font-weight: 800 !important; color: #1e293b !important;
            font-size: 1.15rem !important; letter-spacing: -.3px !important;
            padding-top: .25em !important;
        }
        .swal2-html-container { color: #64748b !important; font-size: .875rem !important; line-height: 1.6 !important; }
        .swal2-icon { border-width: 2.5px !important; margin: .6em auto 1em !important; transform: scale(.92); }
        .swal2-icon.swal2-warning { border-color: #fed7aa !important; color: #f59e0b !important; }
        .swal2-icon.swal2-success { border-color: #a7f3d0 !important; color: #10b981 !important; }
        .swal2-icon.swal2-success [class^=swal2-success-line] { background-color: #10b981 !important; }
        .swal2-icon.swal2-success .swal2-success-ring { border-color: #a7f3d0 !important; }
        .swal2-icon.swal2-error { border-color: #fecaca !important; color: #ef4444 !important; }
        .swal2-icon.swal2-error [class^=swal2-x-mark-line] { background-color: #ef4444 !important; }
        .swal2-icon.swal2-info, .swal2-icon.swal2-question { border-color: var(--primary-200) !important; color: var(--primary-600) !important; }
        .swal2-actions { gap: 8px !important; margin-top: 1.1em !important; }
        .swal2-styled.swal2-confirm {
            background: var(--primary-600) !important; border-radius: 9999px !important;
            font-weight: 800 !important; padding: .65em 1.7em !important;
            box-shadow: 0 6px 16px rgb(var(--primary-600-rgb) / .32) !important;
        }
        .swal2-styled.swal2-cancel {
            background: #f1f5f9 !important; color: #64748b !important; border-radius: 9999px !important;
            font-weight: 700 !important; padding: .65em 1.7em !important;
        }
        .swal2-styled:focus { box-shadow: 0 0 0 3px rgb(var(--primary-600-rgb) / .2) !important; }
        .swal2-input, .swal2-textarea {
            border-radius: 14px !important; border: 1.5px solid #e2e8f0 !important;
            font-size: .875rem !important; color: #1e293b !important;
        }
        .swal2-input:focus, .swal2-textarea:focus {
            border-color: var(--primary-600) !important; box-shadow: 0 0 0 3px rgb(var(--primary-600-rgb) / .14) !important;
        }
        .swal2-validation-message { border-radius: 12px !important; font-size: .8rem !important; }

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

        /* Tab Panes */
        .tab-content, .tab-content-tw { width: 100%; }
        .tab-pane, .tab-pane-tw { display: none; }
        .tab-pane.active, .tab-pane.show.active,
        .tab-pane-tw.active, .tab-pane-tw.show.active { display: block; animation: fadeInUp 0.2s ease-out; }
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
    </script>

    <?= $this->renderSection('styles') ?>
    <?= \App\Libraries\Theme::styleTag() ?>
</head>

<body class="bg-[#f4f6fb] font-sans text-slate-800 h-full antialiased" onload="startTime()">

    <!-- Sidebar overlay (mobile) -->
    <div id="sidebar-overlay"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity"
         onclick="closeSidebar()"></div>

    <div class="flex h-screen overflow-hidden">

        <!-- ===== SIDEBAR ===== -->
        <aside id="main-sidebar"
               class="fixed lg:static z-40 flex flex-col w-64 h-screen shrink-0
                      -translate-x-full lg:translate-x-0 shadow-2xl shadow-black/30">
            <?= $this->include('partials/sidebar') ?>
        </aside>

        <!-- ===== MAIN AREA ===== -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <!-- Topbar -->
            <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md
                           border-b border-slate-200/80 shadow-sm shadow-slate-200/50 shrink-0">
                <?= $this->include('partials/navbar') ?>
            </header>

            <!-- Scrollable content -->
            <main id="main-content" class="flex-1 overflow-y-auto">
                <?= $this->renderSection('content') ?>
            </main>

            <!-- Footer -->
            <footer class="shrink-0 bg-white border-t border-slate-200 px-6 py-2.5
                           flex items-center justify-between text-xs text-slate-400">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span><strong class="font-semibold text-slate-600">Absen Online</strong>
                        &nbsp;v2.2.0-CI4 &middot; Ikasmedia</span>
                </div>
                <span class="text-slate-300 font-mono">PHP <?= phpversion() ?></span>
            </footer>
        </div>
    </div>

    <?= $this->renderSection('modals') ?>

    <!-- ===== JS ===== -->
    <!-- DataTables -->
    <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/jszip/jszip.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/pdfmake/pdfmake.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/pdfmake/vfs_fonts.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.html5.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/datatables-buttons/js/buttons.print.min.js') ?>"></script>
    <!-- SweetAlert2 -->
    <script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.min.js') ?>"></script>
    <!-- Toastr -->
    <script src="<?= base_url('assets/plugins/toastr/toastr.min.js') ?>"></script>
    <!-- Select2 -->
    <script src="<?= base_url('assets/plugins/select2/js/select2.full.min.js') ?>"></script>
    <!-- Moment -->
    <script src="<?= base_url('assets/plugins/moment/moment.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/moment/moment-with-locales.min.js') ?>"></script>
    <!-- Tempusdominus -->
    <script src="<?= base_url('assets/plugins/tempus-dominus/tempus-dominus.min.js') ?>"></script>
    <!-- Summernote -->
    <script src="<?= base_url('assets/plugins/summernote/summernote-lite.min.js') ?>"></script>
    <!-- Chart.js -->
    <script src="<?= base_url('assets/plugins/chart.js/Chart.min.js') ?>"></script>
    <!-- Datetime picker -->
    <script src="<?= base_url('assets/plugins/jquery-datetimepicker/jquery.datetimepicker.full.js') ?>"></script>
    <!-- TimeAgo -->
    <script src="<?= base_url('assets/plugins/jquery-timeago/jquery.timeago.js') ?>"></script>
    <!-- Toastr show -->
    <script src="<?= base_url('assets/app/js/show.toast.js') ?>"></script>

    <!-- App UI interactions -->
    <script>
    window.AdminUI = (function () {
        const openModals = new Set();

        function resolveElement(target) {
            if (!target) return null;
            if (target instanceof HTMLElement) return target;
            if (target.jquery) return target.get(0);
            if (typeof target === 'string') {
                const selector = target.trim();
                if (!selector) return null;
                if (selector.startsWith('#')) return document.getElementById(selector.slice(1));
                return document.querySelector(selector);
            }
            return null;
        }

        function getTargetSelector(trigger) {
            return trigger?.getAttribute('data-ui-target')
                || trigger?.getAttribute('data-bs-target')
                || trigger?.getAttribute('href');
        }

        function syncBodyScroll() {
            document.body.style.overflow = openModals.size > 0 ? 'hidden' : '';
        }

        function showModal(target) {
            const modal = resolveElement(target);
            if (!modal) return;

            modal.classList.add('is-open', 'show');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            openModals.add(modal);
            syncBodyScroll();
            modal.dispatchEvent(new CustomEvent('app:modal-shown'));
            if (window.jQuery) window.jQuery(modal).trigger('shown');
        }

        function hideModal(target) {
            const modal = resolveElement(target);
            if (!modal) return;

            modal.classList.remove('is-open', 'show', 'active');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            openModals.delete(modal);
            syncBodyScroll();
            modal.dispatchEvent(new CustomEvent('app:modal-hidden'));
            if (window.jQuery) window.jQuery(modal).trigger('hidden');
        }

        function toggleModal(target) {
            const modal = resolveElement(target);
            if (!modal) return;
            if (openModals.has(modal)) hideModal(modal);
            else showModal(modal);
        }

        function activateTab(trigger) {
            const target = getTargetSelector(trigger);
            if (!target) return;

            const pane = resolveElement(target);
            if (!pane) return;

            const tablist = trigger.closest('[role="tablist"], .nav-tabs, .nav-pills, .nav-pills-tw, .card-tw-header ul');
            if (tablist) {
                tablist.querySelectorAll('[data-ui-toggle="tab"], [data-ui-toggle="pill"], [data-bs-toggle="tab"], [data-bs-toggle="pill"]').forEach(function (btn) {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });
            }

            trigger.classList.add('active');
            trigger.setAttribute('aria-selected', 'true');

            const tabContent = pane.closest('.tab-content, .tab-content-tw');
            if (tabContent) {
                tabContent.querySelectorAll('.tab-pane, .tab-pane-tw').forEach(function (tabPane) {
                    tabPane.classList.remove('show', 'active');
                });
            }

            pane.classList.add('show', 'active');
        }

        function toggleDropdown(trigger) {
            const menu = trigger.nextElementSibling || trigger.parentElement?.querySelector('.dropdown-menu');
            if (!menu) return;

            document.querySelectorAll('.dropdown-menu.show').forEach(function (openMenu) {
                if (openMenu !== menu) openMenu.classList.remove('show');
            });

            menu.classList.toggle('show');
        }

        function toggleCollapse(trigger) {
            const target = getTargetSelector(trigger);
            if (!target) return;

            const panel = resolveElement(target);
            if (!panel) return;

            panel.classList.toggle('show');
            const expanded = panel.classList.contains('show');
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');

            const icon = trigger.querySelector('.bi-chevron-down, .bi-chevron-up');
            if (icon) {
                icon.classList.toggle('bi-chevron-down', !expanded);
                icon.classList.toggle('bi-chevron-up', expanded);
            }
        }

        function attachTooltip(el) {
            if (!el) return;
            const title = el.getAttribute('title');
            if (title && !el.getAttribute('aria-label')) {
                el.setAttribute('aria-label', title);
            }
        }

        function initTooltips(scope) {
            (scope || document).querySelectorAll('[data-ui-toggle="tooltip"], [data-bs-toggle="tooltip"]').forEach(attachTooltip);
        }

        document.addEventListener('click', function (e) {
            const modalTrigger = e.target.closest('[data-ui-toggle="modal"], [data-bs-toggle="modal"]');
            if (modalTrigger) {
                e.preventDefault();
                toggleModal(getTargetSelector(modalTrigger));
                return;
            }

            const dismissTrigger = e.target.closest('[data-ui-dismiss="modal"], [data-bs-dismiss="modal"]');
            if (dismissTrigger) {
                const modal = dismissTrigger.closest('.modal');
                if (modal) hideModal(modal);
                return;
            }

            const tabTrigger = e.target.closest('[data-ui-toggle="tab"], [data-ui-toggle="pill"], [data-bs-toggle="tab"], [data-bs-toggle="pill"]');
            if (tabTrigger) {
                e.preventDefault();
                activateTab(tabTrigger);
                return;
            }

            const dropdownTrigger = e.target.closest('[data-ui-toggle="dropdown"], [data-bs-toggle="dropdown"]');
            if (dropdownTrigger) {
                e.preventDefault();
                e.stopPropagation();
                toggleDropdown(dropdownTrigger);
                return;
            }

            const collapseTrigger = e.target.closest('[data-ui-toggle="collapse"], [data-bs-toggle="collapse"]');
            if (collapseTrigger) {
                e.preventDefault();
                toggleCollapse(collapseTrigger);
                return;
            }

            document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
                menu.classList.remove('show');
            });

            const modalBackdrop = e.target.closest('.modal');
            if (modalBackdrop && e.target === modalBackdrop) {
                const staticBackdrop = modalBackdrop.getAttribute('data-ui-backdrop') === 'static'
                    || modalBackdrop.getAttribute('data-bs-backdrop') === 'static';
                if (!staticBackdrop) hideModal(modalBackdrop);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || openModals.size === 0) return;
            const modals = Array.from(openModals);
            hideModal(modals[modals.length - 1]);
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.modal').forEach(function (modal) {
                if (!modal.classList.contains('is-open') && !modal.classList.contains('show') && !modal.classList.contains('active')) {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });

            initTooltips(document);
        });

        if (window.jQuery && !window.jQuery.fn.appModal) {
            window.jQuery.fn.appModal = function (action) {
                return this.each(function () {
                    if (action === 'show') {
                        showModal(this);
                        return;
                    }
                    if (action === 'toggle') {
                        toggleModal(this);
                        return;
                    }

                    hideModal(this);
                });
            };
        }

        return {
            modal: {
                open: showModal,
                close: hideModal,
                toggle: toggleModal,
            },
            tabs: {
                activate: activateTab,
            },
            dropdown: {
                toggle: toggleDropdown,
            },
            collapse: {
                toggle: toggleCollapse,
            },
            tooltip: {
                attach: attachTooltip,
                initAll: initTooltips,
            },
        };
    })();

    // ── Bootstrap 5 API compatibility shim ──────────────────────────────
    // Allows legacy code using new bootstrap.Modal(el).show() / .hide() / .toggle()
    window.bootstrap = window.bootstrap || {};
    window.bootstrap.Modal = function (el) {
        this.el = typeof el === 'string'
            ? document.querySelector(el)
            : (el instanceof jQuery ? el.get(0) : el);
    };
    window.bootstrap.Modal.prototype.show   = function () { AdminUI.modal.open(this.el);   };
    window.bootstrap.Modal.prototype.hide   = function () { AdminUI.modal.close(this.el);  };
    window.bootstrap.Modal.prototype.toggle = function () { AdminUI.modal.toggle(this.el); };
    window.bootstrap.Modal.getInstance = function (el) { return new window.bootstrap.Modal(el); };
    window.bootstrap.Modal.getOrCreateInstance = function (el) { return new window.bootstrap.Modal(el); };

    // jQuery .modal() shim
    if (window.jQuery) {
        jQuery.fn.modal = function (action) {
            return this.each(function () {
                const m = this;
                if (action === 'show')   AdminUI.modal.open(m);
                else if (action === 'toggle') AdminUI.modal.toggle(m);
                else AdminUI.modal.close(m);
            });
        };
    }
    </script>

    <!-- Sidebar JS -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (window.innerWidth < 1024) {
                // Mobile: slide in/out
                const isOpen = !sidebar.classList.contains('-translate-x-full');
                if (isOpen) {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                } else {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                }
            } else {
                // Desktop: collapse/expand
                sidebar.classList.toggle('collapsed');
            }
        }

        function closeSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        // Treeview accordion for sidebar submenus
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.sidebar-treeview-btn').forEach(function(btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const sub = this.nextElementSibling;
                    const icon = this.querySelector('.treeview-arrow');
                    if (sub) {
                        const isHidden = sub.classList.contains('hidden');
                        if (isHidden) {
                            sub.classList.remove('hidden');
                            if (icon) {
                                icon.classList.add('rotate-90', 'text-blue-400');
                                icon.classList.remove('text-slate-500');
                            }
                            this.classList.add('bg-white/10', 'text-white');
                            this.classList.remove('text-slate-400');
                        } else {
                            sub.classList.add('hidden');
                            if (icon) {
                                icon.classList.remove('rotate-90', 'text-blue-400');
                                icon.classList.add('text-slate-500');
                            }
                            const hasActive = sub.querySelector('a.active-link');
                            if (!hasActive) {
                                this.classList.remove('bg-white/10', 'text-white');
                                this.classList.add('text-slate-400');
                            }
                        }
                    }
                });
            });

            // Auto-open active parent
            document.querySelectorAll('.sidebar-submenu a.active-link').forEach(function(link) {
                const sub = link.closest('.sidebar-submenu');
                const btn = sub ? sub.previousElementSibling : null;
                if (sub) sub.classList.remove('hidden');
                if (btn) {
                    const icon = btn.querySelector('.treeview-arrow');
                    if (icon) {
                        icon.classList.add('rotate-90', 'text-blue-400');
                        icon.classList.remove('text-slate-500');
                    }
                    btn.classList.add('bg-white/10', 'text-white');
                    btn.classList.remove('text-slate-400');
                }
            });

            // Flash messages
            <?php if (session()->getFlashdata('success')): ?>
                showSuccessToast('<?= addslashes(session()->getFlashdata('success')) ?>');
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                showDangerToast('<?= addslashes(session()->getFlashdata('error')) ?>');
            <?php endif; ?>
        });
    </script>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
