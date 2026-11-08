<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Top bar override ───────────────────────────────── */
    .top-app-bar {
        background: var(--m3-surface);
        padding-top: calc(16px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* ── Month chip ─────────────────────────────────────── */
    #month-display { user-select: none; }
    #btn-pdf { min-height: 36px; }
    /* Target sentuh minimal 36px untuk tombol bulan */
    .month-nav-btn {
        width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center;
        border: none; background: transparent; cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: background .15s ease, transform .12s ease;
    }
    .month-nav-btn:active { transform: scale(.9); }
    .month-nav-btn:disabled { cursor: not-allowed; }

    /* ── Log item press state ───────────────────────────── */
    .log-item { transition: background 0.15s, transform 0.12s; }
    .log-item:active { transform: scale(0.98); background: #f1f0f4 !important; }

    /* ── Keterangan 2 baris ───────────────────────────────── */
    .clamp-2 {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* ── Skeleton shimmer ───────────────────────────────── */
    .skel {
        border-radius: 8px;
        background: linear-gradient(90deg, #ede9f0 25%, #f5f3f7 50%, #ede9f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
    }
    @keyframes shimmer { to { background-position: -200% 0; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <a href="<?= site_url('presensi') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-600 no-underline -ml-1">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold text-slate-800 m-0 leading-none">Riwayat Absensi</h1>
    </div>
    <a id="btn-pdf" href="<?= site_url('presensi/log/pdf') ?>?month=<?= date('Y-m') ?>"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-50 text-rose-600 text-xs font-bold no-underline border border-rose-100 hover:bg-rose-100 transition-colors">
        <i class="bi bi-file-earmark-pdf-fill text-sm"></i>
        Cetak PDF
    </a>
</div>

<!-- ── Main Content ─────────────────────────────────────── -->
<div class="px-4 pb-6 space-y-4 mt-2">

    <!-- Month Picker Chip -->
    <div id="month-display"
        class="flex items-center justify-between bg-purple-50 border border-purple-100 text-purple-700 rounded-2xl px-4 py-3">
        <div class="flex items-center gap-2">
            <i class="bi bi-calendar3 text-sm"></i>
            <span id="month-label" class="font-semibold text-sm"><?= date('F Y') ?></span>
        </div>
        <div class="flex items-center gap-1">
            <button id="btn-prev-month" class="month-nav-btn rounded-full hover:bg-purple-100 text-purple-600 transition-colors" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
                <i class="bi bi-chevron-left text-sm"></i>
            </button>
            <button id="btn-next-month" class="month-nav-btn rounded-full hover:bg-purple-100 text-purple-600 transition-colors" aria-label="Bulan berikutnya" title="Bulan berikutnya">
                <i class="bi bi-chevron-right text-sm"></i>
            </button>
        </div>
    </div>

    <!-- ── Summary Cards ─────────────────────────────────── -->
    <div id="monthly-summary" class="hidden">
        <div class="grid grid-cols-3 gap-2.5">
            <!-- Hadir -->
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-3 flex flex-col items-center gap-1">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center mb-0.5">
                    <i class="bi bi-check-circle-fill text-emerald-600 text-sm"></i>
                </div>
                <span id="stat-hadir" class="text-2xl font-extrabold text-emerald-700 leading-none">0</span>
                <span class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wide">Hadir</span>
            </div>
            <!-- Terlambat -->
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-3 flex flex-col items-center gap-1">
                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center mb-0.5">
                    <i class="bi bi-clock-history text-amber-600 text-sm"></i>
                </div>
                <span id="stat-telat" class="text-2xl font-extrabold text-amber-600 leading-none">0</span>
                <span class="text-[10px] font-semibold text-amber-600 uppercase tracking-wide">Telat</span>
            </div>
            <!-- Persentase -->
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-3 flex flex-col items-center gap-1">
                <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center mb-0.5">
                    <i class="bi bi-bar-chart-fill text-rose-600 text-sm"></i>
                </div>
                <span id="stat-persen" class="text-2xl font-extrabold text-rose-600 leading-none">0%</span>
                <span class="text-[10px] font-semibold text-rose-600 uppercase tracking-wide">Kehadiran</span>
            </div>
        </div>

        <!-- Progress bar kehadiran -->
        <div class="mt-3 bg-white border border-slate-100 rounded-2xl px-4 py-3">
            <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                <span class="font-medium">Tingkat Kehadiran</span>
                <span id="progress-label" class="font-bold text-rose-600">0%</span>
            </div>
            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-brand-500 to-brand-400 rounded-full transition-all duration-700" style="width:0%"></div>
            </div>
        </div>
    </div>

    <!-- ── Log List ───────────────────────────────────────── -->
    <div id="history-list" class="space-y-2.5">
        <!-- Skeleton Loading -->
        <div class="space-y-2.5" id="skeleton-loader">
            <?php for($i=0; $i<5; $i++): ?>
            <div class="bg-white rounded-2xl border border-slate-100 p-4 flex items-center gap-3 shadow-sm">
                <div class="skel w-12 h-12 rounded-xl flex-shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="skel h-3.5 w-2/3 rounded"></div>
                    <div class="skel h-3 w-1/2 rounded"></div>
                </div>
                <div class="skel w-14 h-6 rounded-full flex-shrink-0"></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    // ── State ──────────────────────────────────────────────
    const today   = new Date();
    let curYear   = today.getFullYear();
    let curMonth  = today.getMonth(); // 0-indexed

    const monthNames = ["Januari","Februari","Maret","April","Mei","Juni",
                        "Juli","Agustus","September","Oktober","November","Desember"];
    const monthNamesShort = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agt","Sep","Okt","Nov","Des"];
    const dayNames = ["Min","Sen","Sel","Rab","Kam","Jum","Sab"];

    // ── Helpers ────────────────────────────────────────────
    function padZero(n) { return String(n).padStart(2, '0'); }
    function toMonthParam() { return `${curYear}-${padZero(curMonth + 1)}`; }
    function updateMonthLabel() {
        $('#month-label').text(`${monthNames[curMonth]} ${curYear}`);
        // Disable "next" if we're on current or future month
        const isCurrentMonth = curYear === today.getFullYear() && curMonth === today.getMonth();
        $('#btn-next-month').prop('disabled', isCurrentMonth)
            .toggleClass('opacity-40 cursor-not-allowed', isCurrentMonth);
        // Update PDF link
        $('#btn-pdf').attr('href', `<?= site_url('presensi/log/pdf') ?>?month=${toMonthParam()}`);
    }

    // ── Month Navigation ───────────────────────────────────
    $('#btn-prev-month').on('click', function (e) {
        e.stopPropagation();
        curMonth--;
        if (curMonth < 0) { curMonth = 11; curYear--; }
        updateMonthLabel();
        loadHistory();
    });

    $('#btn-next-month').on('click', function (e) {
        e.stopPropagation();
        const isCurrentMonth = curYear === today.getFullYear() && curMonth === today.getMonth();
        if (isCurrentMonth) return;
        curMonth++;
        if (curMonth > 11) { curMonth = 0; curYear++; }
        updateMonthLabel();
        loadHistory();
    });

    // ── Load History ───────────────────────────────────────
    function loadHistory() {
        // Show skeleton
        $('#history-list').html(`
            <div class="space-y-2.5" id="skeleton-loader">
                ${[...Array(4)].map(() => `
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex items-center gap-3 shadow-sm">
                    <div class="skel w-12 h-12 rounded-xl flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="skel h-3.5 w-2/3 rounded"></div>
                        <div class="skel h-3 w-1/2 rounded"></div>
                    </div>
                    <div class="skel w-14 h-6 rounded-full flex-shrink-0"></div>
                </div>`).join('')}
            </div>`);
        $('#monthly-summary').addClass('hidden');

        $.get('<?= site_url('presensi/history') ?>', { month: toMonthParam() }, function (res) {
            if (res.status && res.data) {
                renderSummary(res.data.stats);
                renderList(res.data.month);
            } else {
                renderEmpty();
            }
        }).fail(function () {
            $('#history-list').html(`
                <div class="bg-red-50 border border-red-100 rounded-2xl p-6 text-center">
                    <i class="bi bi-wifi-off text-red-400 text-3xl"></i>
                    <p class="text-sm font-semibold text-red-600 mt-2 mb-0">Gagal memuat data</p>
                    <p class="text-xs text-red-400 mt-1">Periksa koneksi internet Anda</p>
                    <button onclick="loadHistory()" class="mt-3 px-4 py-1.5 bg-red-100 text-red-600 text-xs font-bold rounded-full">Coba Lagi</button>
                </div>`);
        });
    }

    // ── Render Summary ─────────────────────────────────────
    function renderSummary(stats) {
        $('#stat-hadir').text(stats.hadir ?? 0);
        $('#stat-telat').text(stats.telat_kali ?? 0);
        const pct = stats.percentage ?? 0;
        $('#stat-persen').text(pct + '%');
        $('#progress-label').text(pct + '%');
        $('#progress-bar').css('width', Math.min(pct, 100) + '%');
        $('#monthly-summary').removeClass('hidden');
    }

    // ── Render List ────────────────────────────────────────
    function renderList(data) {
        if (!data || data.length === 0) {
            renderEmpty();
            return;
        }

        data.sort((a, b) => new Date(b.tgl) - new Date(a.tgl));

        let html = '';
        data.forEach(item => {
            const d   = new Date(item.tgl + 'T00:00:00');
            const day = d.getDate();
            const dow = dayNames[d.getDay()];

            // ── Jam (abaikan NULL / '0000-00-00 00:00:00') ──
            const cleanTime = (v) => {
                if (!v || String(v).startsWith('0000-00-00')) return null;
                return String(v).includes(' ') ? String(v).split(' ')[1].substr(0,5) : String(v).substr(0,5);
            };
            const inTime  = cleanTime(item.jam_masuk);
            const outTime = cleanTime(item.jam_pulang);

            // ── Belum absen pulang? (masuk ada, pulang kosong) ──
            const tglStr = String(item.tgl ?? '').substr(0, 10);
            const todayStr = `${today.getFullYear()}-${padZero(today.getMonth() + 1)}-${padZero(today.getDate())}`;
            const missingOut = !!inTime && !outTime;
            const isTodayRow = tglStr === todayStr;

            // ── Status badge ───────────────────────────────
            let badgeHtml = '';
            const st = (item.status_masuk ?? '').toUpperCase();
            const isDinas = st.includes('DINAS');
            const devLower = String(item.device_info ?? '').toLowerCase();
            const ketLower = String(item.keterangan ?? '').toLowerCase();
            const isManual = devLower.includes('manual') || ketLower.includes('manual') || ketLower.includes('admin');
            const isQr = !isManual && (devLower.includes('kiosk') || devLower.includes('qr') || ketLower.includes('kiosk') || ketLower.includes('qr'));

            if (st === 'LATE') {
                const mnt = item.telat_menit ? `+${item.telat_menit}m` : '';
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"><i class="bi bi-clock"></i>Telat ${mnt}</span>`;
            } else if (st === 'DINAS_PENDING') {
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"><i class="bi bi-hourglass-split"></i>Dinas • Menunggu</span>`;
            } else if (isDinas) {
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700"><i class="bi bi-briefcase"></i>Dinas</span>`;
            } else if (st === 'IZIN') {
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700"><i class="bi bi-card-text"></i>Izin</span>`;
            } else if (st === 'SAKIT') {
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-700"><i class="bi bi-hospital"></i>Sakit</span>`;
            } else if (inTime) {
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"><i class="bi bi-check-circle"></i>Hadir</span>`;
            } else {
                badgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500"><i class="bi bi-dash-circle"></i>Absen</span>`;
            }

            if (isManual) {
                badgeHtml += ` <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><i class="bi bi-person-gear"></i>Manual</span>`;
            } else if (isQr) {
                badgeHtml += ` <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"><i class="bi bi-qr-code"></i>QR</span>`;
            }

            // ── Date icon color ────────────────────────────
            let iconBg   = 'bg-rose-50 text-rose-600';
            if (st === 'LATE')             { iconBg = 'bg-amber-50 text-amber-600'; }
            else if (isDinas)              { iconBg = 'bg-blue-50 text-blue-600';  }
            else if (!inTime)              { iconBg = 'bg-slate-50 text-slate-400'; }

            // ── Poin & Denda ───────────────────────────────
            const poin  = parseInt(item.points ?? 0);
            const denda = parseInt(item.denda ?? 0);
            let extraHtml = '';
            if (poin > 0) {
                extraHtml += `<span class="text-[10px] font-semibold text-emerald-600"><i class="bi bi-star-fill text-[9px]"></i> +${poin} poin</span>`;
            }
            if (denda > 0) {
                extraHtml += `<span class="text-[10px] font-semibold text-red-500 ml-2"><i class="bi bi-exclamation-triangle-fill text-[9px]"></i> Rp${denda.toLocaleString('id-ID')}</span>`;
            }

            // ── Detail: jam bila ada, keterangan bila ada ──
            // (Baris dinas tanpa jam menampilkan alasan tugas agar kartu tidak identik)
            const ket = String(item.keterangan ?? '').trim();
            let detailHtml = '';
            if (inTime || outTime) {
                const outChip = outTime
                    ? `<span class="font-semibold text-slate-700">${outTime}</span>`
                    : (missingOut
                        ? (isTodayRow
                            ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"><span class="pulse-dot bg-amber-500 text-amber-500" style="width:6px;height:6px;"></span>Belum pulang</span>`
                            : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-400"><i class="bi bi-dash-circle text-[10px]"></i>Tidak absen pulang</span>`)
                        : `<span class="font-semibold text-slate-300">--:--</span>`);
                detailHtml += `
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span class="flex items-center gap-1">
                                <i class="bi bi-box-arrow-in-right text-emerald-500 text-sm"></i>
                                <span class="font-semibold text-slate-700">${inTime ?? '--:--'}</span>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="flex items-center gap-1">
                                <i class="bi bi-box-arrow-right ${outTime ? 'text-rose-500' : 'text-slate-300'} text-sm"></i>
                                ${outChip}
                            </span>
                        </div>`;
            }
            if (ket) {
                detailHtml += `
                        <div class="flex items-start gap-1 mt-1 text-[11px] text-slate-500">
                            <i class="bi bi-card-text mt-px flex-shrink-0"></i>
                            <span class="clamp-2 italic">${$('<div>').text(ket).html()}</span>
                        </div>`;
            }
            // CTA khusus hari ini yang belum pulang → arahkan absen pulang
            let ctaHtml = '';
            if (missingOut && isTodayRow) {
                ctaHtml = `
                        <a href="<?= site_url('presensi') ?>" class="mt-2 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-white text-[11px] font-bold no-underline shadow-sm active:scale-95 transition-transform" style="background:linear-gradient(135deg,var(--brand-600),var(--brand-500));">
                            <i class="bi bi-box-arrow-right"></i>Absen Pulang Sekarang
                        </a>`;
            }

            html += `
            <div class="log-item bg-white rounded-2xl border ${missingOut && isTodayRow ? 'border-amber-200' : 'border-slate-100'} shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-3 py-3 flex-1 min-w-0">
                    <!-- Date icon -->
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl ${iconBg} flex flex-col items-center justify-center leading-none border border-current border-opacity-10">
                        <span class="text-lg font-extrabold leading-none">${padZero(day)}</span>
                        <span class="text-[9px] font-bold uppercase mt-1 opacity-80">${dow}</span>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                            ${badgeHtml}
                        </div>
                        ${detailHtml}
                        ${ctaHtml}
                        ${extraHtml ? `<div class="flex items-center mt-1 flex-wrap">${extraHtml}</div>` : ''}
                    </div>
                </div>
            </div>`;
        });

        $('#history-list').html(html);
    }

    // ── Empty State ────────────────────────────────────────
    function renderEmpty() {
        $('#history-list').html(`
            <div class="text-center py-12 px-6">
                <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <i class="bi bi-calendar-x text-slate-400 text-3xl"></i>
                </div>
                <p class="font-bold text-slate-600 text-sm">Tidak ada data absensi</p>
                <p class="text-xs text-slate-400 mt-1">Belum ada catatan kehadiran untuk bulan ini.</p>
            </div>`);
    }

    // ── Init ───────────────────────────────────────────────
    updateMonthLabel();
    loadHistory();

    // expose for retry button
    window.loadHistory = loadHistory;
})();
</script>
<?= $this->endSection() ?>
