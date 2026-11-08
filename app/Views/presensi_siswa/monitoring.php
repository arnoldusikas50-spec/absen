<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.status-dot {
    width:8px; height:8px; border-radius:50%; flex-shrink:0; display:inline-block;
}
.dot-HADIR            { background:#22c55e; }
.dot-SAKIT            { background:#eab308; }
.dot-IZIN             { background:#38bdf8; }
.dot-ALPA             { background:#ef4444; }
.dot-BDR              { background:#14b8a6; }
.dot-TANPA_KETERANGAN { background:#94a3b8; }

.jam-pill {
    display:inline-flex; align-items:center; gap:0.25rem;
    padding:0.15rem 0.5rem; border-radius:9999px;
    font-size:0.68rem; font-weight:700;
}
.jam-pill-masuk  { background:#dcfce7; color:#15803d; }
.jam-pill-keluar { background:#e0f2fe; color:#0369a1; }
.jam-pill-empty  { background:#f1f5f9; color:#94a3b8; }

/* ── Filter chips (pola permits) ── */
.filter-chip {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.75rem; border-radius: 9999px;
    font-size: 0.75rem; font-weight: 600; cursor: pointer;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    transition: all 0.15s ease; white-space: nowrap; user-select: none;
}
.filter-chip:hover  { border-color: #0284c7; color: #0284c7; background: #f0f9ff; }
.filter-chip.active { background: #0284c7; color: #fff; border-color: #0284c7; }
.filter-chip.active .chip-count { background: rgba(255,255,255,0.25); color: #fff; }
.chip-count {
    background: #f1f5f9; color: #475569;
    font-size: 0.68rem; font-weight: 800;
    padding: 0.05rem 0.4rem; border-radius: 9999px;
    min-width: 18px; text-align: center;
}
.pulse-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.pulse-pending { background: #f59e0b; animation: pulse 1.8s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

@media print {
    .no-print { display: none !important; }
    .fade-in-up { animation: none !important; }
    body { background: #fff !important; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ── Header (pola kartu/dapodik/izin) ── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs no-print">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-sky-50 text-sky-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-calendar-day"></i> Monitoring Harian
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul) ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul) ?></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/siswa/rekap') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-file-earmark-spreadsheet"></i> Rekap Bulanan
            </a>
            <button type="button" id="btn-print"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
            </button>
            <a href="<?= site_url('presensisiswa') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20 no-underline">
                <i class="bi bi-pencil-square"></i> Input Absensi
            </a>
        </div>
    </div>

    <!-- ── Filter ── -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden no-print">
        <div class="flex items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <i class="bi bi-funnel text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Filter Monitoring</h2>
        </div>
        <div class="p-5 flex flex-wrap gap-3 items-end">
            <!-- Navigasi tanggal -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="mon-tgl">Tanggal</label>
                <div class="flex items-center gap-1">
                    <button type="button" id="btn-prev" class="btn-tw btn-tw-sm btn-light px-2" title="Kemarin">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <input type="date" id="mon-tgl" class="form-control-tw"
                           value="<?= esc($tgl) ?>" max="<?= date('Y-m-d') ?>"
                           style="width:150px">
                    <button type="button" id="btn-next" class="btn-tw btn-tw-sm btn-light px-2"
                            title="Besok">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" id="btn-today" class="btn-tw btn-tw-sm btn-outline-primary ml-1">
                        Hari Ini
                    </button>
                </div>
            </div>
            <!-- Kelas -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="mon-kelas">Kelas</label>
                <select id="mon-kelas" class="form-select-tw" style="width:180px">
                    <option value="">— Pilih Kelas —</option>
                    <?php foreach ($kelass as $id => $nama): ?>
                    <option value="<?= $id ?>"><?= esc($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" id="btn-refresh" class="btn-tw btn-primary">
                <i class="bi bi-arrow-clockwise mr-1"></i> Refresh
            </button>
            <!-- Search -->
            <div class="relative ml-0 md:ml-auto" style="min-width:220px;">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="mon-search" placeholder="Cari nama / NIS..." autocomplete="off"
                       class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                <button type="button" id="mon-clear"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
        </div>
        <!-- Status chips -->
        <div class="px-5 py-3 bg-slate-50/60 border-t border-slate-100 flex flex-wrap gap-1.5 items-center" id="mon-chips">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1">STATUS</span>
            <button type="button" class="filter-chip active" data-status="all">Semua</button>
            <button type="button" class="filter-chip" data-status="HADIR"><span class="status-dot dot-HADIR"></span>Hadir</button>
            <button type="button" class="filter-chip" data-status="SAKIT"><span class="status-dot dot-SAKIT"></span>Sakit</button>
            <button type="button" class="filter-chip" data-status="IZIN"><span class="status-dot dot-IZIN"></span>Izin</button>
            <button type="button" class="filter-chip" data-status="ALPA"><span class="status-dot dot-ALPA"></span>Alpa</button>
            <button type="button" class="filter-chip" data-status="BDR"><span class="status-dot dot-BDR"></span>BDR</button>
            <button type="button" class="filter-chip" data-status="TANPA_KETERANGAN"><span class="pulse-dot pulse-pending"></span>Belum</button>
        </div>
    </div>

    <!-- ── Stat cards (pola dapodik: border-l accent) ── -->
    <div id="stat-section" class="hidden grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-7 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-slate-400 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-people-fill"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                <p class="text-xl font-extrabold text-slate-900 leading-tight" id="s-total">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-check-circle-fill"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Hadir</p>
                <p class="text-xl font-extrabold text-emerald-600 leading-tight" id="s-hadir">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-yellow-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-thermometer-half"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sakit</p>
                <p class="text-xl font-extrabold text-yellow-600 leading-tight" id="s-sakit">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-sky-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-envelope-open"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Izin</p>
                <p class="text-xl font-extrabold text-sky-600 leading-tight" id="s-izin">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-red-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-x-circle-fill"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Alpa</p>
                <p class="text-xl font-extrabold text-red-600 leading-tight" id="s-alpa">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-teal-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-house-door-fill"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">BDR</p>
                <p class="text-xl font-extrabold text-teal-600 leading-tight" id="s-bdr">0</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-slate-300 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-lg shrink-0"><i class="bi bi-dash-circle"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Belum</p>
                <p class="text-xl font-extrabold text-slate-500 leading-tight" id="s-tanpa">0</p>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="mon-loading" class="hidden text-center py-12">
        <div class="w-10 h-10 border-4 border-sky-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-sm text-slate-400 mt-3">Memuat data monitoring…</p>
    </div>

    <!-- Empty -->
    <div id="mon-empty" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs py-14 text-center">
        <i class="bi bi-calendar-day text-5xl text-slate-300"></i>
        <p class="mt-3 text-slate-400 font-medium">Pilih kelas untuk melihat monitoring harian</p>
    </div>

    <!-- Tabel monitoring -->
    <div id="mon-table-wrap" class="hidden bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <i class="bi bi-table text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900" id="mon-title">Monitoring Kehadiran Siswa</h2>
            <span class="ml-auto px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold" id="mon-last-update"></span>
        </div>
        <div class="overflow-x-auto">
            <table class="table-tw" id="mon-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px">#</th>
                        <th>Nama Siswa</th>
                        <th class="text-center hidden sm:table-cell">NIS</th>
                        <th class="text-center" style="width:90px">Status</th>
                        <th class="text-center hidden md:table-cell" style="width:100px">Masuk</th>
                        <th class="text-center hidden md:table-cell" style="width:100px">Keluar</th>
                        <th class="hidden lg:table-cell">Dicatat Oleh</th>
                        <th class="hidden lg:table-cell">Keterangan</th>
                    </tr>
                </thead>
                <tbody id="mon-tbody"></tbody>
            </table>
        </div>
        <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 no-print">
            <span id="mon-count"></span>
            <span id="mon-periode"></span>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    function csrf() { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }

    const STATUS_INFO = {
        HADIR:            { label:'Hadir', dot:'dot-HADIR',            badge:'bg-emerald-100 text-emerald-700' },
        SAKIT:            { label:'Sakit', dot:'dot-SAKIT',            badge:'bg-yellow-100 text-yellow-700'   },
        IZIN:             { label:'Izin',  dot:'dot-IZIN',             badge:'bg-sky-100 text-sky-700'         },
        ALPA:             { label:'Alpa',  dot:'dot-ALPA',             badge:'bg-red-100 text-red-700'         },
        BDR:              { label:'BDR',   dot:'dot-BDR',              badge:'bg-teal-100 text-teal-700'       },
        TANPA_KETERANGAN: { label:'Belum', dot:'dot-TANPA_KETERANGAN', badge:'bg-slate-100 text-slate-500'     },
    };
    const AVATAR_COLORS = ['bg-indigo-500','bg-violet-500','bg-sky-500','bg-teal-500',
                           'bg-emerald-500','bg-amber-500','bg-rose-500','bg-pink-500'];
    const TODAY = '<?= date('Y-m-d') ?>';
    let activeStatus = 'all';

    function escHtmlM(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    function fotoUrlM(f, nis) {
        if (!f) return null;
        f = String(f).trim();
        if (!f || f.toLowerCase() === 'siswa.png' || f.toLowerCase() === 'default.png' || f.toLowerCase() === 'null') {
            return null;
        }
        if (/^https?:\/\//i.test(f)) return f;
        if (f.startsWith('/')) return base_url + f.replace(/^\//, '');
        if (f.startsWith('uploads/')) return base_url + f;
        // Kolom foto kadang berisi NIS mentah tanpa ekstensi — anggap kosong agar tidak 404.
        if (!f.includes('.')) return null;
        return base_url + 'uploads/foto_siswa/' + f;
    }

    function initialsOfM(nama) {
        const parts = String(nama || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
    }

    // Format tanggal lokal (hindari geser hari ala toISOString/UTC)
    function fmtYmdLocal(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    // Parse YYYY-MM-DD sebagai tanggal lokal (hindari bug timezone `new Date(str)`)
    function parseYmdLocal(str) {
        const parts = String(str || '').split('-').map(Number);
        if (parts.length !== 3 || parts.some(isNaN)) return null;
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function tglFmtId(str) {
        const d = parseYmdLocal(str);
        if (!d) return str;
        return d.toLocaleDateString('id-ID', { weekday:'long', day:'2-digit', month:'long', year:'numeric' });
    }

    function loadMonitoring() {
        const idKelas = $('#mon-kelas').val();
        const tgl     = $('#mon-tgl').val();

        if (!idKelas) return;

        try {
            localStorage.setItem('presensi_siswa_mon_kelas', idKelas);
            // Teruskan ke halaman input absensi yang memakai key ini
            localStorage.setItem('presensi_siswa_kelas', idKelas);
        } catch (e) {}

        $('#mon-loading').show();
        $('#mon-empty, #mon-table-wrap, #stat-section').addClass('hidden');

        const payload = csrf();
        payload.id_kelas = idKelas;
        payload.tgl = tgl;
        $.post(base_url + 'presensisiswa/getMonitoringHarian', payload, function (res) {
            $('#mon-loading').hide();

            if (!res || !res.status) {
                Swal.fire('Error', (res && res.message) || 'Gagal memuat data', 'error');
                return;
            }

            const s = res.stat || {};
            $('#s-total').text(s.total || 0);
            $('#s-hadir').text(s.hadir || 0);
            $('#s-sakit').text(s.sakit || 0);
            $('#s-izin').text(s.izin   || 0);
            $('#s-alpa').text(s.alpa   || 0);
            $('#s-bdr').text(s.bdr    || 0);
            $('#s-tanpa').text(s.tanpa || 0);
            $('#stat-section').removeClass('hidden');

            renderMonitoring(res.data || [], tgl, idKelas);

        }, 'json').fail(() => {
            $('#mon-loading').hide();
            Swal.fire('Error','Gagal menghubungi server','error');
        });
    }
    window.loadMonitoring = loadMonitoring;

    function renderMonitoring(data, tgl, idKelas) {
        const namaKelas = $('#mon-kelas option[value="' + idKelas + '"]').text() || '';

        $('#mon-title').text('Kehadiran — ' + namaKelas);
        $('#mon-last-update').text(tglFmtId(tgl));
        $('#mon-periode').text(tglFmtId(tgl) + ' • ' + namaKelas);

        const tbody = document.getElementById('mon-tbody');

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400">
                <i class="bi bi-inbox text-3xl block mb-2"></i>Belum ada data siswa di kelas ini</td></tr>`;
            $('#mon-count').text('Menampilkan 0 data');
            $('#mon-table-wrap').removeClass('hidden');
            return;
        }

        let html = '';

        data.forEach((r, idx) => {
            const status   = r.status || 'TANPA_KETERANGAN';
            const info     = STATUS_INFO[status] || STATUS_INFO.TANPA_KETERANGAN;
            const colorCls = AVATAR_COLORS[idx % AVATAR_COLORS.length];
            const initials = initialsOfM(r.nama);
            const photoUrl = r.foto_url || fotoUrlM(r.foto, r.nis);
            const fallbackBadge = `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold ${colorCls} shrink-0">${escHtmlM(initials)}</div>`;
            const photo    = photoUrl
                ? `<img src="${escHtmlM(photoUrl)}" class="w-8 h-8 rounded-full object-cover shrink-0" loading="lazy" alt="${escHtmlM(initials)}" onerror="this.onerror=null;this.replaceWith(document.createRange().createContextualFragment('${fallbackBadge.replace(/'/g, "\\'")}'));">`
                : fallbackBadge;

            const jamMasukHtml = r.jam_masuk
                ? `<span class="jam-pill jam-pill-masuk"><i class="bi bi-box-arrow-in-right"></i>${escHtmlM(String(r.jam_masuk).substring(0,5))}</span>`
                : `<span class="jam-pill jam-pill-empty">—</span>`;

            const jamKeluarHtml = r.jam_keluar
                ? `<span class="jam-pill jam-pill-keluar"><i class="bi bi-box-arrow-right"></i>${escHtmlM(String(r.jam_keluar).substring(0,5))}</span>`
                : (Number(r.pulang_tidak_tercatat) === 1
                    ? `<span class="jam-pill jam-pill-empty" title="Jam pulang operasional sudah lewat tanpa absen pulang">tidak tercatat</span>`
                    : `<span class="jam-pill jam-pill-empty">—</span>`);

            const searchKw = ((r.nama || '') + ' ' + (r.nis || '')).toLowerCase();

            html += `
            <tr data-search="${escHtmlM(searchKw)}" data-status="${escHtmlM(status)}">
                <td class="text-center text-xs text-slate-400 font-mono">${idx+1}</td>
                <td>
                    <div class="flex items-center gap-2">
                        ${photo}
                        <span class="font-semibold text-slate-800 text-sm" title="${escHtmlM(r.nama || '')}">${escHtmlM(r.nama || '-')}</span>
                    </div>
                </td>
                <td class="text-center text-xs text-slate-400 hidden sm:table-cell">${escHtmlM(r.nis || '—')}</td>
                <td class="text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold ${info.badge}">
                        <span class="status-dot ${info.dot}"></span>${info.label}
                    </span>
                </td>
                <td class="text-center hidden md:table-cell">${jamMasukHtml}</td>
                <td class="text-center hidden md:table-cell">${jamKeluarHtml}</td>
                <td class="text-xs text-slate-400 hidden lg:table-cell" title="${escHtmlM(r.nama_guru || '')}">${escHtmlM(r.nama_guru || '—')}</td>
                <td class="text-xs text-slate-400 hidden lg:table-cell" title="${escHtmlM(r.keterangan || '')}">${escHtmlM(r.keterangan || '—')}</td>
            </tr>`;
        });

        tbody.innerHTML = html;
        applyMonFilter();
        $('#mon-table-wrap').removeClass('hidden');
    }

    // ── Client filter: search + chips status ──
    function applyMonFilter() {
        const kw = ($('#mon-search').val() || '').toLowerCase().trim();
        $('#mon-clear').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
        let shown = 0;
        $('#mon-tbody tr[data-search]').each(function() {
            const matchStatus = activeStatus === 'all' || $(this).data('status') === activeStatus;
            const matchSearch = !kw || String($(this).data('search') || '').includes(kw);
            const visible = matchStatus && matchSearch;
            $(this).toggle(visible);
            if (visible) shown++;
        });
        const total = $('#mon-tbody tr[data-search]').length;
        $('#mon-count').text('Menampilkan ' + shown + ' dari ' + total + ' siswa');
    }

    $('#mon-chips').on('click', '.filter-chip', function() {
        activeStatus = $(this).data('status');
        $('#mon-chips .filter-chip').removeClass('active');
        $(this).addClass('active');
        applyMonFilter();
    });
    $('#mon-search').on('input', applyMonFilter);
    $('#mon-clear').on('click', function() {
        $('#mon-search').val('');
        applyMonFilter();
        $('#mon-search').focus();
    });

    // ── Navigasi tanggal & filter (delegated, pola permits) ──
    function changeDate(delta) {
        const inp = document.getElementById('mon-tgl');
        const d = parseYmdLocal(inp.value);
        if (!d) return;
        d.setDate(d.getDate() + delta);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (d > today) return;
        inp.value = fmtYmdLocal(d);
        syncNextBtn();
        loadMonitoring();
    }
    window.changeDate = changeDate;

    function goToday() {
        document.getElementById('mon-tgl').value = TODAY;
        syncNextBtn();
        loadMonitoring();
    }
    window.goToday = goToday;

    $('#btn-prev').on('click', () => changeDate(-1));
    $('#btn-next').on('click', () => changeDate(1));
    $('#btn-today').on('click', goToday);
    $('#btn-refresh').on('click', loadMonitoring);
    $('#btn-print').on('click', function() {
        const idKelas = $('#mon-kelas').val();
        const tgl = $('#mon-tgl').val();
        if (!idKelas) {
            Swal.fire('Peringatan', 'Pilih kelas terlebih dahulu untuk mengunduh PDF.', 'warning');
            return;
        }
        window.location.href = base_url + 'admin/presensi/siswa/harian/pdf?id_kelas='
            + encodeURIComponent(idKelas) + '&tgl=' + encodeURIComponent(tgl);
    });
    $('#mon-tgl').on('change', function() {
        if (this.value > TODAY) this.value = TODAY;
        syncNextBtn();
        loadMonitoring();
    });
    $('#mon-kelas').on('change', loadMonitoring);

    function syncNextBtn() {
        document.getElementById('btn-next').disabled = (document.getElementById('mon-tgl').value >= TODAY);
    }
    syncNextBtn();

    // Ingat kelas terakhir
    try {
        const last = localStorage.getItem('presensi_siswa_mon_kelas');
        const sel = document.getElementById('mon-kelas');
        if (last && sel.querySelector('option[value="' + CSS.escape(last) + '"]')) {
            sel.value = last;
            loadMonitoring();
        }
    } catch (e) {}
});
</script>
<?= $this->endSection() ?>
