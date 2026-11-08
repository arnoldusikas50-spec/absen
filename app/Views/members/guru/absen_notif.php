<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Top bar override ─────────────────────────── */
    .top-app-bar {
        background: var(--m3-surface);
        padding-top: calc(16px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* ── Skeleton shimmer ─────────────────────────── */
    .skel {
        border-radius: 8px;
        background: linear-gradient(90deg, #ede9f0 25%, #f5f3f7 50%, #ede9f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
    }
    @keyframes shimmer { to { background-position: -200% 0; } }

    /* ── Notif card unread bar ────────────────────── */
    .notif-card-unread::before {
        content: '';
        position: absolute;
        left: 0; top: 8px; bottom: 8px;
        width: 3px;
        border-radius: 2px;
        background: var(--m3-primary);
    }

    /* ── Kepsek grid item press ───────────────────── */
    .kepsek-menu-item { transition: transform 0.12s, background 0.12s; }
    .kepsek-menu-item:active { transform: scale(0.94); }

    /* ── Preferensi notifikasi ────────────────────── */
    .pref-toggle {
        appearance: none;
        -webkit-appearance: none;
        width: 38px; height: 20px;
        border-radius: 20px;
        background: #cbd5e1;
        cursor: pointer;
        transition: background .2s;
        position: relative;
        flex-shrink: 0;
    }
    .pref-toggle::after {
        content: '';
        position: absolute;
        top: 2px; left: 2px;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: transform .2s;
        box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }
    .pref-toggle:checked { background: var(--m3-primary, #6750a4); }
    .pref-toggle:checked::after { transform: translateX(18px); }
    .pref-toggle:disabled { opacity: .4; cursor: not-allowed; }

    /* Toast pref */
    #pref-toast {
        position: fixed;
        bottom: calc(116px + var(--safe-area-inset-bottom, 0px) * 0.5); left: 50%; transform: translateX(-50%) translateY(30px);
        z-index: 999;
        opacity: 0;
        transition: all .28s cubic-bezier(.34,1.56,.64,1);
        white-space: nowrap;
        pointer-events: none;
    }
    #pref-toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* Collapse animation */
    .pref-body {
        overflow: hidden;
        max-height: 0;
        transition: max-height .35s ease;
    }
    .pref-body.open {
        max-height: 600px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <a href="<?= site_url('presensi') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-600 no-underline -ml-1">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold text-slate-800 m-0 leading-none">Notifikasi</h1>
    </div>
    <!-- Unread badge -->
    <span id="notif-badge-top"
        class="hidden min-w-[22px] h-[22px] px-1.5 flex items-center justify-center rounded-full bg-rose-600 text-white text-[10px] font-extrabold">
        0
    </span>
</div>

<!-- ── Main Content ─────────────────────────────── -->
<div class="px-4 pb-6 space-y-4 mt-2">

    <?php if (!empty($showKepsekMenu)): ?>
    <!-- ── Kepsek Panel ──────────────────────────── -->
    <div class="bg-gradient-to-br from-brand-600 to-brand-500 rounded-3xl p-4 shadow-md">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                <i class="bi bi-shield-fill-check text-white text-sm"></i>
            </div>
            <div>
                <p class="text-white font-bold text-sm m-0 leading-none">Panel Kepala Sekolah</p>
                <p class="text-rose-100 text-[10px] m-0 mt-0.5">Verifikasi pengajuan guru</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <!-- Dinas -->
            <a href="<?= site_url('presensi/kepsek/dinas') ?>"
                class="kepsek-menu-item relative flex flex-col items-center gap-1.5 bg-white/15 hover:bg-white/25 rounded-2xl py-3 px-2 no-underline">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <i class="bi bi-geo-alt-fill text-white text-lg"></i>
                </div>
                <span class="text-white text-[11px] font-bold">Dinas</span>
                <?php if (!empty($kepsekPendingDinas)): ?>
                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-yellow-400 text-slate-900 text-[9px] font-extrabold shadow">
                    <?= $kepsekPendingDinas ?>
                </span>
                <?php endif; ?>
            </a>
            <!-- Izin -->
            <a href="<?= site_url('presensi/kepsek/izin') ?>"
                class="kepsek-menu-item relative flex flex-col items-center gap-1.5 bg-white/15 hover:bg-white/25 rounded-2xl py-3 px-2 no-underline">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <i class="bi bi-calendar-check-fill text-white text-lg"></i>
                </div>
                <span class="text-white text-[11px] font-bold">Izin</span>
                <?php if (!empty($kepsekPendingIzin)): ?>
                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-yellow-400 text-slate-900 text-[9px] font-extrabold shadow">
                    <?= $kepsekPendingIzin ?>
                </span>
                <?php endif; ?>
            </a>
            <!-- Teguran -->
            <a href="<?= site_url('presensi/kepsek/teguran') ?>"
                class="kepsek-menu-item relative flex flex-col items-center gap-1.5 bg-white/15 hover:bg-white/25 rounded-2xl py-3 px-2 no-underline">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-white text-lg"></i>
                </div>
                <span class="text-white text-[11px] font-bold">Teguran</span>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Panel Preferensi Notifikasi DM ──────────── -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden" id="prefPanel">

        <!-- Header (klik untuk buka/tutup) -->
        <button type="button" id="prefToggleBtn"
            class="w-full flex items-center justify-between px-4 py-3.5 text-left">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-2xl bg-violet-100 flex items-center justify-center">
                    <i class="bi bi-bell-slash text-violet-600 text-base"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-sm m-0 leading-none">Preferensi Notifikasi DM</p>
                    <p class="text-[10px] text-slate-400 m-0 mt-0.5">Pilih jenis yang ingin Anda terima secara personal</p>
                </div>
            </div>
            <i id="prefChevron" class="bi bi-chevron-down text-slate-400 text-sm transition-transform"></i>
        </button>

        <!-- Body (collapsible) -->
        <div class="pref-body" id="prefBody">
            <div class="px-4 pb-2 space-y-1" id="prefList">
                <!-- Diisi JS setelah fetch -->
                <div class="text-center py-4" id="prefLoadingSkel">
                    <div class="skel h-3 w-2/3 mx-auto rounded mb-2"></div>
                    <div class="skel h-3 w-1/2 mx-auto rounded"></div>
                </div>
            </div>

            <!-- Info -->
            <div class="mx-4 mb-3 px-3 py-2.5 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-[10px] text-amber-800 m-0 leading-relaxed">
                    <i class="bi bi-info-circle mr-1"></i>
                    Pengaturan ini hanya memengaruhi pesan langsung ke Telegram / WhatsApp Anda.
                    Notifikasi ke grup sekolah tetap diatur oleh admin.
                </p>
            </div>

            <!-- Simpan -->
            <div class="px-4 pb-4 flex justify-end">
                <button type="button" id="btnSavePrefs"
                    class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 active:scale-95 text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                    <i class="bi bi-floppy2-fill text-xs"></i>
                    <span id="btnSavePrefsLabel">Simpan Preferensi</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Notification List ─────────────────────── -->
    <div>
        <div class="flex items-center justify-between mb-2.5">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest m-0">Aktivitas Terbaru</h2>
            <button id="btn-mark-all" class="hidden text-[10px] font-bold text-rose-600 hover:text-rose-800 px-2 py-1 rounded-lg hover:bg-rose-50 transition-colors">
                Tandai semua dibaca
            </button>
        </div>

        <!-- Skeleton loading -->
        <div id="notif-loading" class="space-y-2.5">
            <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                <div class="skel w-11 h-11 rounded-xl flex-shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="skel h-3.5 w-3/4 rounded"></div>
                    <div class="skel h-3 w-full rounded"></div>
                    <div class="skel h-2.5 w-1/3 rounded"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Loaded content -->
        <div id="notification-list" class="space-y-2.5 hidden"></div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {

    // ── Color map ──────────────────────────────────────────
    const colorMap = {
        'bg-green-soft':  { bg: 'bg-emerald-50',  icon: 'text-emerald-600',  border: 'border-emerald-100' },
        'bg-red-soft':    { bg: 'bg-red-50',       icon: 'text-red-600',      border: 'border-red-100'     },
        'bg-blue-soft':   { bg: 'bg-blue-50',      icon: 'text-blue-600',     border: 'border-blue-100'    },
        'bg-purple-soft': { bg: 'bg-purple-50',    icon: 'text-purple-600',   border: 'border-purple-100'  },
        'bg-orange-soft': { bg: 'bg-orange-50',    icon: 'text-orange-600',   border: 'border-orange-100'  },
        'bg-yellow-soft': { bg: 'bg-amber-50',     icon: 'text-amber-600',    border: 'border-amber-100'   },
    };

    const statusMap = {
        'danger':  { label: 'Penting',   cls: 'bg-red-100 text-red-700'       },
        'warning': { label: 'Perhatian', cls: 'bg-amber-100 text-amber-700'   },
        'info':    { label: 'Info',      cls: 'bg-blue-100 text-blue-700'      },
        'pending': { label: 'Menunggu',  cls: 'bg-slate-100 text-slate-600'   },
        'approved':{ label: 'Disetujui', cls: 'bg-emerald-100 text-emerald-700'},
        'rejected':{ label: 'Ditolak',   cls: 'bg-red-100 text-red-700'       },
        'DINAS_APPROVED': { label: 'Disetujui', cls: 'bg-emerald-100 text-emerald-700' },
        'DINAS_REJECTED': { label: 'Ditolak',   cls: 'bg-red-100 text-red-700'         },
        'DINAS_PENDING':  { label: 'Menunggu',  cls: 'bg-slate-100 text-slate-600'     },
    };

    function getColors(bgClass) {
        return colorMap[bgClass] || { bg: 'bg-slate-50', icon: 'text-slate-500', border: 'border-slate-100' };
    }

    function getStatusBadge(status) {
        const s = statusMap[status] || { label: status, cls: 'bg-slate-100 text-slate-500' };
        return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wide ${s.cls}">${s.label}</span>`;
    }

    // ── Load ───────────────────────────────────────────────
    function loadNotifications() {
        $.get('<?= site_url('presensi/get-notifications') ?>', function (res) {
            $('#notif-loading').hide();
            const $list = $('#notification-list');
            $list.removeClass('hidden');

            if (!res.status || !res.data || res.data.length === 0) {
                $list.html(`
                    <div class="text-center py-14 px-6">
                        <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-4">
                            <i class="bi bi-envelope-open text-slate-400 text-3xl"></i>
                        </div>
                        <p class="font-bold text-slate-600 text-sm">Tidak ada notifikasi</p>
                        <p class="text-xs text-slate-400 mt-1">Aktivitas Anda akan muncul di sini.</p>
                    </div>`);
                return;
            }

            // ── Helpers untuk menentukan apakah notif adalah "unread/pending" ──
            const isPending = n =>
                n.status === 'pending' || n.status === 'DINAS_PENDING';
            const isUrgent = n =>
                n.status === 'danger' || n.status === 'warning';

            const unread = res.data.filter(n => isPending(n) || isUrgent(n)).length;

            if (unread > 0) {
                $('#notif-badge-top').text(unread).removeClass('hidden').addClass('flex');
                $('#btn-mark-all').removeClass('hidden');
            }

            const warningTypes = ['warning','warning_severe','warning_late','warning_month_3','warning_month_7','warning_month_10'];
            const warnings     = res.data.filter(n => warningTypes.includes(n.type));
            const announcements= res.data.filter(n => n.type === 'announcement');
            const others       = res.data.filter(n => !warningTypes.includes(n.type) && n.type !== 'announcement');

            // ── Dalam grup "Pengajuan & Status": pending selalu di atas ────
            // Priority: pending=0, warning/danger=1, lainnya=2
            // Dalam tiap priority, urutan dari server (created_at DESC) dipertahankan
            const statusPriority = n => {
                if (isPending(n))  return 0;
                if (isUrgent(n))   return 1;
                return 2;
            };
            others.sort((a, b) => statusPriority(a) - statusPriority(b));

            let html = '';

            if (warnings.length > 0) {
                html += sectionHeader('bi-exclamation-triangle-fill text-amber-500', 'Peringatan');
                warnings.forEach(n => { html += renderCard(n, true); });
            }
            if (others.length > 0) {
                html += sectionHeader('bi-bell-fill text-rose-500', 'Pengajuan & Status');
                others.forEach(n => { html += renderCard(n, isPending(n)); });
            }
            if (announcements.length > 0) {
                html += sectionHeader('bi-megaphone-fill text-blue-500', 'Pengumuman');
                announcements.forEach(n => { html += renderCard(n, false); });
            }

            $list.html(html);
        }).fail(function () {
            $('#notif-loading').hide();
            $('#notification-list').removeClass('hidden').html(`
                <div class="bg-red-50 border border-red-100 rounded-2xl p-6 text-center">
                    <i class="bi bi-wifi-off text-red-400 text-3xl"></i>
                    <p class="text-sm font-semibold text-red-600 mt-2 mb-1">Gagal memuat notifikasi</p>
                    <button onclick="location.reload()" class="mt-2 px-4 py-1.5 bg-red-100 text-red-600 text-xs font-bold rounded-full">Coba Lagi</button>
                </div>`);
        });
    }

    // ── Section header helper ──────────────────────────────
    function sectionHeader(iconClass, label) {
        return `<div class="flex items-center gap-2 mt-1 mb-2 px-1">
            <i class="bi ${iconClass} text-sm"></i>
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">${label}</span>
        </div>`;
    }

    // ── Render single card ─────────────────────────────────
    function renderCard(notif, isUnread) {
        const c = getColors(notif.bgClass);
        const unreadBar = isUnread ? 'notif-card-unread' : '';
        const unreadRing = isUnread ? 'border-rose-200 shadow-rose-50' : 'border-slate-100';
        const unreadDot  = isUnread
            ? `<span class="flex-shrink-0 w-2 h-2 rounded-full bg-rose-500 mt-1 self-start"></span>`
            : '';
        const actionBtn = notif.action_url
            ? `<a href="${notif.action_url}" target="_blank" class="mt-2 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-rose-600 text-white text-[11px] font-bold no-underline shadow-sm active:scale-95 transition-transform">
                   <i class="bi ${notif.action_icon || 'bi-file-earmark-pdf-fill'} text-xs"></i>${notif.action_label || 'Unduh'}
               </a>`
            : '';

        return `
        <div class="relative ${unreadBar} bg-white rounded-2xl border ${unreadRing} shadow-sm overflow-hidden pl-4 pr-3 py-3 flex items-start gap-3">
            <!-- Icon -->
            <div class="flex-shrink-0 w-11 h-11 rounded-xl ${c.bg} ${c.icon} flex items-center justify-center text-lg border ${c.border}">
                <i class="bi ${notif.icon}"></i>
            </div>
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-bold text-slate-800 text-sm m-0 leading-snug">${notif.title}</p>
                    ${unreadDot}
                </div>
                <p class="text-xs text-slate-500 mt-0.5 mb-1.5 leading-relaxed">${notif.text}</p>
                <div class="flex items-center gap-2 flex-wrap">
                    ${getStatusBadge(notif.status)}
                    <span class="flex items-center gap-1 text-[10px] text-slate-400">
                        <i class="bi bi-clock text-[9px]"></i>
                        ${notif.time}
                    </span>
                </div>
                ${actionBtn}
            </div>
        </div>`;
    }

    // ── Mark all read (visual only) ────────────────────────
    $('#btn-mark-all').on('click', function () {
        $('#notif-badge-top').addClass('hidden').removeClass('flex');
        $(this).addClass('hidden');
        $('.notif-card-unread').each(function () {
            $(this).removeClass('notif-card-unread border-rose-200 shadow-rose-50')
                   .addClass('border-slate-100');
            $(this).find('.w-2.h-2.rounded-full.bg-rose-500').remove();
        });
    });

    // ── Init ───────────────────────────────────────────────
    loadNotifications();

})();
</script>

<script>
/* ── Preferensi Notifikasi DM ── */
(function () {
    'use strict';

    const PREF_LABELS = {
        denda:        { label: 'Denda Baru',                   icon: 'bi-cash-coin',              desc: 'Dikirim saat ada potongan denda baru pada akun Anda.' },
        teguran:      { label: 'Surat Teguran',                icon: 'bi-exclamation-triangle',   desc: 'Notifikasi ketika surat teguran diterbitkan.' },
        alpha:        { label: 'Alpha / Tidak Hadir',          icon: 'bi-calendar-x',             desc: 'Pemberitahuan saat absen Anda tercatat ALPHA.' },
        kehadiran:    { label: 'Keterlambatan Check-in',       icon: 'bi-alarm',                  desc: 'Pesan saat check-in melewati batas toleransi.' },
        izin:         { label: 'Izin / Sakit / Cuti / Dinas', icon: 'bi-file-earmark-check',     desc: 'Semua pengajuan dan hasil keputusan izin Anda.' },
        koreksi:      { label: 'Koreksi Jam Absensi',          icon: 'bi-pencil-square',          desc: 'Pengajuan dan hasil koreksi jam masuk / pulang.' },
        guru_terawal: { label: 'Guru Paling Cepat Hadir',      icon: 'bi-trophy',                 desc: 'Notifikasi spesial saat Anda datang paling awal.' },
    };

    const prefToggleBtn = document.getElementById('prefToggleBtn');
    const prefBody      = document.getElementById('prefBody');
    const prefChevron   = document.getElementById('prefChevron');
    const prefList      = document.getElementById('prefList');
    const btnSave       = document.getElementById('btnSavePrefs');
    const btnLabel      = document.getElementById('btnSavePrefsLabel');

    let prefLoaded = false;

    // ── Toast ──────────────────────────────────────────────
    const toastEl = document.createElement('div');
    toastEl.id = 'pref-toast';
    toastEl.className = 'px-4 py-2 rounded-full text-xs font-bold text-white shadow-md';
    document.body.appendChild(toastEl);

    function showToast(msg, ok) {
        toastEl.textContent = msg;
        toastEl.style.background = ok ? '#7c3aed' : '#ef4444';
        toastEl.classList.add('show');
        clearTimeout(toastEl._t);
        toastEl._t = setTimeout(() => toastEl.classList.remove('show'), 2800);
    }

    // ── Collapse toggle ───────────────────────────────────
    prefToggleBtn.addEventListener('click', function () {
        const isOpen = prefBody.classList.contains('open');
        prefBody.classList.toggle('open', !isOpen);
        prefChevron.style.transform = isOpen ? '' : 'rotate(180deg)';
        if (!prefLoaded) { loadPrefs(); }
    });

    // ── Render baris toggle ───────────────────────────────
    function renderPrefs(prefs) {
        let html = '';
        for (const key in PREF_LABELS) {
            const meta    = PREF_LABELS[key];
            const checked = prefs[key] !== false;
            html += '<label class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-100 last:border-0 cursor-pointer">'
                + '<div class="flex items-center gap-3 min-w-0">'
                +   '<div class="w-8 h-8 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">'
                +     '<i class="bi ' + meta.icon + ' text-violet-600 text-sm"></i>'
                +   '</div>'
                +   '<div class="min-w-0">'
                +     '<p class="text-sm font-semibold text-slate-700 m-0 leading-snug">' + meta.label + '</p>'
                +     '<p class="text-[10px] text-slate-400 m-0 mt-0.5 leading-snug">' + meta.desc + '</p>'
                +   '</div>'
                + '</div>'
                + '<input type="checkbox" class="pref-toggle flex-shrink-0" data-key="' + key + '" value="1"' + (checked ? ' checked' : '') + '>'
                + '</label>';
        }
        prefList.innerHTML = html;
    }

    // ── Load preferensi dari server ───────────────────────
    function loadPrefs() {
        prefLoaded = true;
        fetch('<?= site_url('presensi/notif/prefs') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.status) {
                    renderPrefs(json.prefs || {});
                } else {
                    prefList.innerHTML = '<p class="text-xs text-red-500 text-center py-3">Gagal memuat preferensi.</p>';
                }
            })
            .catch(function () {
                prefList.innerHTML = '<p class="text-xs text-red-500 text-center py-3">Gagal menghubungi server.</p>';
            });
    }

    // ── Simpan ────────────────────────────────────────────
    btnSave.addEventListener('click', function () {
        btnSave.disabled = true;
        btnLabel.textContent = 'Menyimpan…';

        var body = new FormData();
        document.querySelectorAll('#prefList .pref-toggle').forEach(function (cb) {
            body.append('prefs[' + cb.dataset.key + ']', cb.checked ? '1' : '0');
        });
        body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= site_url('presensi/notif/prefs/save') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
            },
            body: body,
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            showToast(json.message || (json.status ? 'Tersimpan.' : 'Gagal.'), json.status === true);
        })
        .catch(function () { showToast('Gagal menghubungi server.', false); })
        .finally(function () {
            btnSave.disabled = false;
            btnLabel.textContent = 'Simpan Preferensi';
        });
    });

})();
</script>
<?= $this->endSection() ?>
