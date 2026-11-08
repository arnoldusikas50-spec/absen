<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Top app bar custom adjustments */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Month nav chip */
    .month-nav-chip {
        user-select: none;
        transition: transform 0.12s ease;
    }
    .month-nav-chip:active {
        transform: scale(0.985);
    }

    /* Calendar Grid Styles */
    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 4px;
    }

    .cal-cell {
        min-height: 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding: 4px 2px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        position: relative;
    }
    .cal-cell:active {
        transform: scale(0.94);
    }
    .cal-cell.today-cell {
        background: #f1f5f9;
        font-weight: 800;
    }
    .cal-cell.today-cell .cell-num {
        color: var(--primary-600);
    }
    .cal-cell.selected-cell {
        background: var(--primary-600) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgb(var(--primary-600-rgb) / 0.25);
    }
    .cal-cell.selected-cell .cell-num {
        color: #ffffff !important;
    }
    .cal-cell.selected-cell .dot {
        background-color: #ffffff !important;
    }

    /* Event Card press */
    .mobile-event-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease;
    }
    .mobile-event-card:active {
        transform: scale(0.985);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('presensi') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="Kembali ke Presensi">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Kalender Akademik</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Agenda, Ujian &amp; Hari Libur</p>
        </div>
    </div>
    <div>
        <button type="button" onclick="goToToday()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-full text-xs font-bold transition-all active:scale-95 border border-blue-100/80 cursor-pointer">
            <i class="bi bi-calendar2-check text-xs"></i>
            <span>Hari Ini</span>
        </button>
    </div>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-24 space-y-3.5 mt-2.5">

    <!-- ── Month Navigation Chip ─────────────────────────── -->
    <div class="month-nav-chip flex items-center justify-between bg-white border border-slate-200/80 rounded-2xl p-2.5 shadow-2xs">
        <button type="button" onclick="gantiBulan(-1)" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 text-slate-600 transition-colors cursor-pointer" title="Bulan sebelumnya">
            <i class="bi bi-chevron-left font-bold text-sm"></i>
        </button>

        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="bi bi-calendar3"></i>
            </div>
            <span id="bulanLabel" class="font-extrabold text-xs sm:text-sm text-slate-800">
                <?= date('F Y', mktime(0, 0, 0, (int)$bulan, 1, (int)$tahun)) ?>
            </span>
        </div>

        <button type="button" onclick="gantiBulan(1)" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 text-slate-600 transition-colors cursor-pointer" title="Bulan berikutnya">
            <i class="bi bi-chevron-right font-bold text-sm"></i>
        </button>
    </div>

    <!-- ── KPI Metric Summary ─────────────────────────────── -->
    <div class="grid grid-cols-3 gap-2">
        <div class="bg-white rounded-2xl p-2.5 border border-slate-200/80 text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block">Kegiatan</span>
            <span id="stat-kegiatan" class="text-xl font-black text-indigo-600 leading-tight">0</span>
            <span class="text-[9px] text-slate-400 block">Agenda</span>
        </div>
        <div class="bg-white rounded-2xl p-2.5 border border-slate-200/80 text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block">Hari Libur</span>
            <span id="stat-libur" class="text-xl font-black text-rose-600 leading-tight">0</span>
            <span class="text-[9px] text-slate-400 block">Libur Resmi</span>
        </div>
        <div class="bg-white rounded-2xl p-2.5 border border-slate-200/80 text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block">Ujian</span>
            <span id="stat-ujian" class="text-xl font-black text-amber-600 leading-tight">0</span>
            <span class="text-[9px] text-slate-400 block">Asesmen</span>
        </div>
    </div>

    <!-- ── Calendar Card ─────────────────────────────────── -->
    <div class="bg-white rounded-3xl p-3.5 sm:p-4 border border-slate-200/80 shadow-2xs space-y-2.5">
        <!-- Day Names Header -->
        <div class="cal-grid text-center font-extrabold text-[11px] pb-1 border-b border-slate-100">
            <div class="text-rose-500 py-1">Min</div>
            <div class="text-slate-600 py-1">Sen</div>
            <div class="text-slate-600 py-1">Sel</div>
            <div class="text-slate-600 py-1">Rab</div>
            <div class="text-slate-600 py-1">Kam</div>
            <div class="text-slate-600 py-1">Jum</div>
            <div class="text-slate-600 py-1">Sab</div>
        </div>

        <!-- Date Grid Cells -->
        <div id="calendarGrid" class="cal-grid">
            <!-- Dynamically populated via JS -->
            <div class="col-span-7 py-8 text-center text-slate-400 text-xs">
                <i class="bi bi-arrow-repeat animate-spin text-xl inline-block mb-1 text-blue-600"></i>
                <p>Memuat kalender...</p>
            </div>
        </div>

        <!-- Legend -->
        <div class="pt-2 border-t border-slate-100 flex items-center justify-center gap-3 text-[11px] font-semibold text-slate-500 flex-wrap">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Event
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Libur
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Ujian
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Rapat
            </span>
        </div>
    </div>

    <!-- ── Filter Category Tabs ───────────────────────────── -->
    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
        <button type="button" onclick="setFilter('ALL', this)" class="cat-pill active px-3 py-1.5 rounded-full text-xs font-bold transition-all bg-blue-600 text-white shadow-2xs shrink-0 cursor-pointer">
            Semua (<span id="count-all">0</span>)
        </button>
        <button type="button" onclick="setFilter('EVENT', this)" class="cat-pill px-3 py-1.5 rounded-full text-xs font-bold transition-all bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shadow-2xs shrink-0 cursor-pointer">
            Agenda
        </button>
        <button type="button" onclick="setFilter('LIBUR', this)" class="cat-pill px-3 py-1.5 rounded-full text-xs font-bold transition-all bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shadow-2xs shrink-0 cursor-pointer">
            Hari Libur
        </button>
        <button type="button" onclick="setFilter('UJIAN', this)" class="cat-pill px-3 py-1.5 rounded-full text-xs font-bold transition-all bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shadow-2xs shrink-0 cursor-pointer">
            Ujian
        </button>
    </div>

    <!-- ── Event Feed / Cards List ────────────────────────── -->
    <div class="space-y-2">
        <div class="flex items-center justify-between px-1">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400" id="feedTitle">
                Agenda &amp; Libur Bulan Ini
            </span>
            <button type="button" id="btnClearDateFilter" onclick="resetDateSelection()" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 hidden cursor-pointer">
                Tampilkan Semua
            </button>
        </div>

        <div id="eventList" class="space-y-2.5">
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 text-center text-slate-400 text-xs">
                <i class="bi bi-calendar-check text-2xl text-slate-300 block mb-1"></i>
                <p>Memuat daftar agenda...</p>
            </div>
        </div>
    </div>

</div>

<!-- ── Bottom Sheet Modal Detail Agenda ─────────────────── -->
<div id="modal-event-detail" class="tw-modal">
    <div class="tw-modal-box p-0 flex flex-col">
        <!-- Drag handle & header -->
        <div class="pt-3 pb-2 px-5 flex flex-col items-center border-b border-slate-100 bg-slate-50/50">
            <div class="w-10 h-1 rounded-full bg-slate-300 mb-3"></div>
            <div class="w-full flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm" id="modal-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800 m-0" id="modal-title">Rincian Agenda</h3>
                        <p class="text-[10px] text-slate-400 m-0" id="modal-date">Tanggal</p>
                    </div>
                </div>
                <button type="button" onclick="closeEventDetail()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors cursor-pointer">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="p-4 sm:p-5 overflow-y-auto space-y-3.5 text-xs max-h-[70vh]" id="modal-body">
            <!-- Populated via JS -->
        </div>

        <!-- Modal Footer -->
        <div class="p-3.5 border-t border-slate-100 flex items-center justify-end bg-slate-50/80">
            <button type="button" onclick="closeEventDetail()" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let currentBulan = <?= (int) $bulan ?>;
let currentTahun = <?= (int) $tahun ?>;
let allEvents = [];
let activeFilter = 'ALL';
let selectedDate = null;

const tipeConfig = {
    LIBUR:   { label: 'Libur', badge: 'bg-rose-50 text-rose-700 border-rose-200', dot: 'bg-rose-500', icon: 'bi-umbrella-fill', border: 'border-l-rose-500' },
    UJIAN:   { label: 'Ujian / Asesmen', badge: 'bg-amber-50 text-amber-700 border-amber-200', dot: 'bg-amber-500', icon: 'bi-pencil-square', border: 'border-l-amber-500' },
    EVENT:   { label: 'Kegiatan', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200', dot: 'bg-indigo-500', icon: 'bi-calendar-event', border: 'border-l-indigo-500' },
    RAPAT:   { label: 'Rapat', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500', icon: 'bi-people-fill', border: 'border-l-emerald-500' },
    DINAS:   { label: 'Dinas Luar', badge: 'bg-sky-50 text-sky-700 border-sky-200', dot: 'bg-sky-500', icon: 'bi-briefcase-fill', border: 'border-l-sky-500' },
    LAINNYA: { label: 'Lainnya', badge: 'bg-purple-50 text-purple-700 border-purple-200', dot: 'bg-purple-500', icon: 'bi-bookmark-fill', border: 'border-l-purple-500' },
};

function formatIndoDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length < 3) return dateStr;
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

function formatShortIndoDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length < 3) return dateStr;
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

function gantiBulan(delta) {
    currentBulan += delta;
    if (currentBulan > 12) {
        currentBulan = 1;
        currentTahun++;
    } else if (currentBulan < 1) {
        currentBulan = 12;
        currentTahun--;
    }
    selectedDate = null;
    loadEvents();
}

function goToToday() {
    const today = new Date();
    currentBulan = today.getMonth() + 1;
    currentTahun = today.getFullYear();
    selectedDate = `${currentTahun}-${String(currentBulan).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
    loadEvents();
}

function setFilter(tipe, btnEl) {
    activeFilter = tipe;
    document.querySelectorAll('.cat-pill').forEach(el => {
        el.classList.remove('bg-blue-600', 'text-white');
        el.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-600');
    });
    if (btnEl) {
        btnEl.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-600');
        btnEl.classList.add('bg-blue-600', 'text-white');
    }
    renderEvents();
}

function resetDateSelection() {
    selectedDate = null;
    document.getElementById('btnClearDateFilter').classList.add('hidden');
    document.getElementById('feedTitle').textContent = 'Agenda & Libur Bulan Ini';
    document.querySelectorAll('.cal-cell').forEach(el => el.classList.remove('selected-cell'));
    renderEvents();
}

function pilihTanggal(dateStr) {
    if (selectedDate === dateStr) {
        // Toggle unselect
        resetDateSelection();
        return;
    }

    selectedDate = dateStr;
    document.getElementById('btnClearDateFilter').classList.remove('hidden');
    document.getElementById('feedTitle').textContent = 'Agenda: ' + formatIndoDate(dateStr);

    document.querySelectorAll('.cal-cell').forEach(el => {
        if (el.dataset.date === dateStr) {
            el.classList.add('selected-cell');
        } else {
            el.classList.remove('selected-cell');
        }
    });

    const dayEvents = getEventsForDate(dateStr);
    if (dayEvents.length > 0) {
        // If has events, show quick bottom sheet modal with full details
        showDayDetailModal(dateStr, dayEvents);
    }
    renderEvents();
}

function getEventsForDate(dateStr) {
    return allEvents.filter(e => {
        const start = e.start;
        const end = e.end || e.start;
        return dateStr >= start && dateStr <= end;
    });
}

function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    const bulanNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    document.getElementById('bulanLabel').textContent = `${bulanNames[currentBulan - 1]} ${currentTahun}`;

    const firstDay = new Date(currentTahun, currentBulan - 1, 1).getDay(); // 0 = Min, 1 = Sen ...
    const daysInMonth = new Date(currentTahun, currentBulan, 0).getDate();
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

    // Compute metrics
    const liburCount = allEvents.filter(e => e.tipe === 'LIBUR').length;
    const ujianCount = allEvents.filter(e => e.tipe === 'UJIAN').length;
    const kegiatanCount = allEvents.filter(e => e.tipe !== 'LIBUR' && e.tipe !== 'UJIAN').length;

    document.getElementById('stat-kegiatan').textContent = kegiatanCount;
    document.getElementById('stat-libur').textContent = liburCount;
    document.getElementById('stat-ujian').textContent = ujianCount;
    document.getElementById('count-all').textContent = allEvents.length;

    let html = '';

    // Empty cells for days before the 1st
    for (let i = 0; i < firstDay; i++) {
        html += '<div class="cal-cell opacity-0 pointer-events-none"></div>';
    }

    // Days in current month
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${currentTahun}-${String(currentBulan).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isToday = dateStr === todayStr;
        const isSelected = dateStr === selectedDate;
        const eventsOnDay = getEventsForDate(dateStr);

        let cellClasses = 'cal-cell';
        if (isToday) cellClasses += ' today-cell';
        if (isSelected) cellClasses += ' selected-cell';

        // Dots indicator
        let dotsHtml = '';
        if (eventsOnDay.length > 0) {
            dotsHtml = '<div class="flex items-center gap-1 mt-1 justify-center">';
            eventsOnDay.slice(0, 3).forEach(ev => {
                const cfg = tipeConfig[ev.tipe] || tipeConfig.EVENT;
                dotsHtml += `<span class="dot w-1.5 h-1.5 rounded-full ${cfg.dot}"></span>`;
            });
            dotsHtml += '</div>';
        }

        // Sunday color styling
        const dayOfWeek = (firstDay + d - 1) % 7;
        const numColor = dayOfWeek === 0 ? 'text-rose-500 font-bold' : 'text-slate-800 font-semibold';

        html += `
            <div class="${cellClasses}" data-date="${dateStr}" onclick="pilihTanggal('${dateStr}')">
                <span class="cell-num text-xs ${numColor}">${d}</span>
                ${dotsHtml}
            </div>
        `;
    }

    grid.innerHTML = html;
    renderEvents();
}

function renderEvents() {
    const container = document.getElementById('eventList');
    let filtered = allEvents;

    // Filter by selected date
    if (selectedDate) {
        filtered = getEventsForDate(selectedDate);
    }

    // Filter by category tab
    if (activeFilter !== 'ALL') {
        if (activeFilter === 'EVENT') {
            filtered = filtered.filter(e => e.tipe !== 'LIBUR' && e.tipe !== 'UJIAN');
        } else {
            filtered = filtered.filter(e => e.tipe === activeFilter);
        }
    }

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="bg-white rounded-2xl p-8 border border-slate-200/80 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2.5 text-xl">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h4 class="text-xs sm:text-sm font-bold text-slate-800">Tidak Ada Agenda</h4>
                <p class="text-[11px] text-slate-400 mt-0.5 max-w-xs mx-auto">
                    ${selectedDate ? 'Tidak ada agenda atau hari libur pada tanggal ini.' : 'Tidak ada agenda pada periode bulan ini.'}
                </p>
            </div>
        `;
        return;
    }

    const todayStr = new Date().toLocaleDateString('en-CA');

    container.innerHTML = filtered.map(e => {
        const cfg = tipeConfig[e.tipe] || tipeConfig.EVENT;
        const isCurrent = todayStr >= e.start && todayStr <= (e.end || e.start);
        const isMultiDay = e.end && e.end !== e.start;

        return `
            <div class="mobile-event-card bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-2xs border-l-4 ${cfg.border} cursor-pointer space-y-2"
                 onclick="showSingleEventModal('${e.id}')">
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border ${cfg.badge}">
                                <i class="bi ${cfg.icon}"></i> ${cfg.label}
                            </span>
                            ${isCurrent ? `
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-blue-600 text-white">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> Hari Ini
                            </span>` : ''}
                        </div>
                        <h4 class="text-xs sm:text-sm font-black text-slate-800 leading-snug pt-1">${e.title}</h4>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                    <div class="flex items-center gap-1.5 font-semibold">
                        <i class="bi bi-calendar3 text-slate-400 text-xs"></i>
                        <span>${formatShortIndoDate(e.start)}${isMultiDay ? ' - ' + formatShortIndoDate(e.end) : ''}</span>
                    </div>
                    <span class="text-blue-600 font-bold flex items-center gap-1">
                        Rincian <i class="bi bi-chevron-right text-[9px]"></i>
                    </span>
                </div>
            </div>
        `;
    }).join('');
}

function showDayDetailModal(dateStr, events) {
    const modal = document.getElementById('modal-event-detail');
    const title = document.getElementById('modal-title');
    const dateLabel = document.getElementById('modal-date');
    const body = document.getElementById('modal-body');

    title.textContent = 'Agenda Hari Ini';
    dateLabel.textContent = formatIndoDate(dateStr);

    body.innerHTML = events.map(e => {
        const cfg = tipeConfig[e.tipe] || tipeConfig.EVENT;
        return `
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border ${cfg.badge}">
                        <i class="bi ${cfg.icon}"></i> ${cfg.label}
                    </span>
                    <span class="text-[11px] font-semibold text-slate-500">${formatShortIndoDate(e.start)}${e.end && e.end !== e.start ? ' - ' + formatShortIndoDate(e.end) : ''}</span>
                </div>
                <h4 class="text-xs sm:text-sm font-black text-slate-900 leading-snug">${e.title}</h4>
                ${e.description ? `<p class="text-xs text-slate-600 leading-relaxed">${e.description}</p>` : ''}
            </div>
        `;
    }).join('');

    twModalShow('#modal-event-detail');
}

function showSingleEventModal(id) {
    const e = allEvents.find(x => String(x.id) === String(id));
    if (!e) return;

    const modal = document.getElementById('modal-event-detail');
    const title = document.getElementById('modal-title');
    const dateLabel = document.getElementById('modal-date');
    const body = document.getElementById('modal-body');

    const cfg = tipeConfig[e.tipe] || tipeConfig.EVENT;
    const isMultiDay = e.end && e.end !== e.start;

    title.textContent = e.title;
    dateLabel.textContent = formatIndoDate(e.start) + (isMultiDay ? ' s/d ' + formatIndoDate(e.end) : '');

    body.innerHTML = `
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border ${cfg.badge}">
                    <i class="bi ${cfg.icon}"></i> ${cfg.label}
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Nama Kegiatan / Agenda</span>
                <p class="text-sm font-black text-slate-800 leading-snug">${e.title}</p>
            </div>

            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Waktu Pelaksanaan</span>
                <p class="text-xs font-semibold text-slate-700">
                    ${formatIndoDate(e.start)}${isMultiDay ? '<br>sampai dengan ' + formatIndoDate(e.end) : ''}
                </p>
            </div>

            ${e.description ? `
            <div class="pt-2 border-t border-slate-200">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Keterangan / Catatan</span>
                <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">${e.description}</p>
            </div>` : ''}
        </div>
    `;

    twModalShow('#modal-event-detail');
}

function closeEventDetail() {
    twModalHide('#modal-event-detail');
}

function loadEvents() {
    const grid = document.getElementById('calendarGrid');
    const container = document.getElementById('eventList');

    grid.innerHTML = `
        <div class="col-span-7 py-8 text-center text-slate-400 text-xs">
            <i class="bi bi-arrow-repeat animate-spin text-xl inline-block mb-1 text-blue-600"></i>
            <p>Memuat kalender...</p>
        </div>
    `;

    fetch(`<?= base_url('guru/kalender/get-events') ?>?bulan=${String(currentBulan).padStart(2,'0')}&tahun=${currentTahun}`)
        .then(r => r.json())
        .then(data => {
            allEvents = data || [];
            renderCalendar();
        })
        .catch(() => {
            container.innerHTML = '<div class="bg-rose-50 text-rose-600 p-4 rounded-xl text-center text-xs font-semibold">Gagal memuat agenda kalender.</div>';
        });
}

// Initial load
loadEvents();
</script>
<?= $this->endSection() ?>
