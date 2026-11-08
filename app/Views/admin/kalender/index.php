<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
    /* ── FullCalendar theme ─────────────────────── */
    #kalender.fc { --fc-border-color: #e2e8f0; font-size: .8125rem; }
    #kalender .fc-toolbar { gap: .5rem; flex-wrap: wrap; }
    #kalender .fc-toolbar-title { font-size: 1rem; font-weight: 800; color: #0f172a; text-transform: capitalize; }
    #kalender .fc-button {
        font-size: .7rem !important; font-weight: 700; border-radius: .65rem !important;
        padding: .42rem .7rem !important; text-transform: capitalize; box-shadow: none !important;
    }
    #kalender .fc-button-primary { background: #fff !important; color: #334155 !important; border-color: #e2e8f0 !important; }
    #kalender .fc-button-primary:hover { background: #f1f5f9 !important; color: #0f172a !important; border-color: #cbd5e1 !important; }
    #kalender .fc-button-primary:not(:disabled).fc-button-active,
    #kalender .fc-button-primary:not(:disabled):active { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
    #kalender .fc-today-button:disabled { opacity: .45; }
    #kalender .fc-col-header-cell { font-size: .66rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: .4rem 0; }
    #kalender .fc-col-header-cell-cushion { color: inherit; text-decoration: none; }
    #kalender .fc-daygrid-day-number { font-size: .72rem; font-weight: 700; color: #475569; padding: 4px 6px; text-decoration: none; }
    #kalender .fc-daygrid-day.fc-day-today { background: #eff6ff; }
    #kalender .fc-daygrid-day.fc-day-today .fc-daygrid-day-number { color: #1d4ed8; }
    #kalender .fc-scrollgrid { border-radius: .9rem; overflow: hidden; border-color: #eef2f7; }
    #kalender .fc-theme-standard td, #kalender .fc-theme-standard th { border-color: #eef2f7; }
    #kalender .kalender-akhir-pekan { background: #f8fafc; }
    #kalender .kalender-akhir-pekan .fc-daygrid-day-number { color: #94a3b8; }
    #kalender .fc-event {
        border-radius: .45rem; font-size: .68rem; font-weight: 700;
        padding: 1px 5px; cursor: pointer; border-width: 1px; box-shadow: none;
    }
    #kalender .kalender-libur { border-style: dashed; }
    #kalender .fc-list-event-title { font-size: .8rem; font-weight: 600; }
    #kalender .fc-list-day-cushion { background: #f8fafc; }
    @media (max-width: 640px) {
        #kalender .fc-toolbar { justify-content: flex-start; }
        #kalender .fc-toolbar-chunk { display: flex; align-items: center; }
        #kalender .fc-toolbar-title { font-size: .9rem; width: 100%; }
        #kalender .fc-button { padding: .35rem .55rem !important; }
    }

    /* ── Tab pills ──────────────────────────────── */
    .tab-pill { transition: background .15s, color .15s, box-shadow .15s; }
    .tab-pill.active { background: #fff; color: #2563eb; box-shadow: 0 1px 6px rgba(0,0,0,.08); }

    /* ── Date badge ─────────────────────────────── */
    .date-icon { width: 46px; height: 46px; border-radius: 13px; display: flex; flex-direction: column;
                 align-items: center; justify-content: center; line-height: 1.05; flex-shrink: 0; }

    /* ── Mode radio ─────────────────────────────── */
    .mode-radio-label { transition: border-color .15s, background .15s, color .15s; }
    input[type=radio].peer:checked ~ .mode-radio-label {
        border-color: #3b82f6; background: #eff6ff; color: #1d4ed8;
    }

    /* ── Agenda rows ────────────────────────────── */
    .agenda-item { transition: background .12s; }
    .agenda-item:focus-visible { outline: 2px solid #93c5fd; outline-offset: -2px; }

    /* ── Sticky table header ────────────────────── */
    #holidayTable thead th { position: sticky; top: 0; z-index: 5; background: #f8fafc; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$events        = $events ?? [];
$holidays      = $holidays ?? [];
$holidaysBulan = $holidays_bulan ?? [];
$weeklyOff     = $weekly_off ?? [];
$liburIso      = $libur_iso ?? [];
$todayStatus   = $today_status ?? [];
$stats         = $stats ?? [];
$bulan         = (int) ($bulan ?? date('n'));
$tahun         = (int) ($tahun ?? date('Y'));

$liburTahun  = (int) ($stats['libur_tahun'] ?? 0);
$liburBulan  = (int) ($stats['libur_bulan'] ?? 0);
$cutiTahun   = (int) ($stats['cuti_tahun'] ?? 0);
$eventBulan  = (int) ($stats['event_bulan'] ?? count($events));
$hariSekolah = (int) ($stats['hari_sekolah'] ?? 0);
$totalHari   = (int) ($stats['total_hari'] ?? date('t'));

$namaBulanId = ['','Januari','Februari','Maret','April','Mei','Juni',
                'Juli','Agustus','September','Oktober','November','Desember'];
$bulanSingkat = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

$nama_bulan      = $nama_bulan ?? $namaBulanId[$bulan];
$weeklyOffLabel  = $weeklyOff ? implode(' & ', array_column($weeklyOff, 'nama')) : 'Tidak ada';
$isHariSekolah   = ($todayStatus['status'] ?? '') === 'aktif';
$statusKeterangan = trim((string) ($todayStatus['keterangan'] ?? ''));
if (($todayStatus['status'] ?? '') === 'akhir_pekan') {
    $statusKeterangan = 'Akhir pekan';
} elseif ($statusKeterangan === '') {
    $statusKeterangan = 'Libur';
}

$tipeColors = [
    'EVENT'   => '#3b82f6',
    'UJIAN'   => '#8b5cf6',
    'LIBUR'   => '#ef4444',
    'RAPAT'   => '#f59e0b',
    'DINAS'   => '#06b6d4',
    'LAINNYA' => '#64748b',
];
$tipeBadge = [
    'EVENT'   => 'bg-blue-50 text-blue-700 border-blue-100',
    'UJIAN'   => 'bg-purple-50 text-purple-700 border-purple-100',
    'LIBUR'   => 'bg-red-50 text-red-700 border-red-100',
    'RAPAT'   => 'bg-amber-50 text-amber-700 border-amber-100',
    'DINAS'   => 'bg-cyan-50 text-cyan-700 border-cyan-100',
    'LAINNYA' => 'bg-slate-100 text-slate-600 border-slate-200',
];

$formatRentang = function (?string $mulai, ?string $selesai) use ($bulanSingkat): string {
    if (! $mulai) {
        return '-';
    }
    $ts1 = strtotime($mulai);
    $ts2 = $selesai ? strtotime($selesai) : $ts1;
    if ($ts2 < $ts1) {
        $ts2 = $ts1;
    }
    $d1 = (int) date('j', $ts1);
    $d2 = (int) date('j', $ts2);
    $m1 = (int) date('n', $ts1);
    $m2 = (int) date('n', $ts2);
    $y1 = date('Y', $ts1);
    $y2 = date('Y', $ts2);
    if ($d1 === $d2 && $m1 === $m2 && $y1 === $y2) {
        return buat_tanggal(date('l', $ts1)) . ', ' . $d1 . ' ' . $bulanSingkat[$m1] . ' ' . $y1;
    }
    if ($m1 === $m2 && $y1 === $y2) {
        return $d1 . '–' . $d2 . ' ' . $bulanSingkat[$m1] . ' ' . $y1;
    }
    return $d1 . ' ' . $bulanSingkat[$m1] . ' – ' . $d2 . ' ' . $bulanSingkat[$m2] . ' ' . $y2;
};

// Agenda awal (server-render) — bulan terpilih
$agenda = [];
foreach ($events as $e) {
    $agenda[] = [
        'kind'   => 'event',
        'id'     => (int) $e->id,
        'date'   => $e->tgl_mulai,
        'end'    => $e->tgl_selesai,
        'title'  => $e->judul,
        'tipe'   => $e->tipe,
        'color'  => $e->warna ?: ($tipeColors[$e->tipe] ?? '#3b82f6'),
    ];
}
foreach ($holidaysBulan as $h) {
    $agenda[] = [
        'kind'   => 'libur',
        'id'     => (int) $h->id,
        'date'   => $h->tanggal,
        'end'    => $h->tanggal,
        'title'  => $h->keterangan,
        'tipe'   => 'LIBUR',
        'color'  => '#ef4444',
    ];
}
usort($agenda, static fn($a, $b) => [$a['date'], $a['title']] <=> [$b['date'], $b['title']]);

$tahunList = [];
foreach ($holidays as $h) {
    $yr = substr((string) $h->tanggal, 0, 4);
    if ($yr !== '' && ! in_array($yr, $tahunList, true)) {
        $tahunList[] = $yr;
    }
}
rsort($tahunList);
$tahunAda = in_array((string) $tahun, $tahunList, true);

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>

<div class="fade-in-up p-4 md:p-6 space-y-5">

    <!-- ── Header ─────────────────────────────────── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-calendar3"></i> Kalender Akademik
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kalender &amp; Hari Libur</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold <?= $isHariSekolah ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                    <i class="bi <?= $isHariSekolah ? 'bi-check-circle-fill' : 'bi-calendar-x-fill' ?>"></i>
                    <?= $isHariSekolah ? 'Hari sekolah' : ($statusKeterangan !== '' ? esc($statusKeterangan) : 'Libur') ?>
                </span>
                <span class="text-[11px] text-slate-400"><?= buat_tanggal(date('l, j F Y')) ?></span>
                <?php if ($tp_active || $smt_active): ?>
                <span class="text-[11px] text-slate-400">
                    <span class="text-slate-300 mx-1">•</span>
                    TP <?= esc($tp_active->tahun ?? '-') ?> &middot; <?= esc($smt_active->smt ?? '-') ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" id="btnTambahLibur"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-600 hover:border-red-200 hover:text-red-600 hover:bg-red-50 rounded-xl text-xs font-bold transition-colors">
                <i class="bi bi-calendar-x"></i> Tambah Libur
            </button>
            <button type="button" data-ui-toggle="modal" data-ui-target="#modalEvent"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-plus-lg"></i> Tambah Event
            </button>
        </div>
    </div>

    <!-- ── Tab ────────────────────────────────────── -->
    <div class="flex gap-1 p-1 bg-slate-100 rounded-xl w-full sm:w-fit overflow-x-auto max-w-full" id="kalTabs">
        <button type="button" id="tabBtnKalender" data-tab="kalender"
            class="tab-pill active flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-lg text-sm font-semibold whitespace-nowrap flex-1 sm:flex-none">
            <i class="bi bi-calendar3"></i> Kalender
        </button>
        <button type="button" id="tabBtnLibur" data-tab="libur"
            class="tab-pill flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-lg text-sm font-semibold text-slate-500 hover:text-slate-700 whitespace-nowrap flex-1 sm:flex-none">
            <i class="bi bi-calendar-x"></i> Hari Libur
            <span class="inline-flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full w-5 h-5"><?= $liburTahun ?></span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════
         TAB: KALENDER
    ══════════════════════════════════════════════ -->
    <div id="tabKalender">

        <!-- KPI -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-5">
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 shadow-xs p-4 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="kpiEventBulanLabel">Event <?= esc($nama_bulan) ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 leading-none mt-0.5"><span id="kpiEventBulan"><?= $eventBulan ?></span></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-red-500 shadow-xs p-4 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="kpiLiburBulanLabel">Libur <?= esc($nama_bulan) ?></p>
                    <p class="text-2xl font-extrabold text-red-500 leading-none mt-0.5"><span id="kpiLiburBulan"><?= $liburBulan ?></span></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 shadow-xs p-4 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hari Sekolah</p>
                    <p class="text-2xl font-extrabold text-emerald-600 leading-none mt-0.5">
                        <span id="kpiHariSekolah"><?= $hariSekolah ?></span>
                        <span class="text-xs font-semibold text-slate-400" id="kpiTotalHari">/ <?= $totalHari ?></span>
                    </p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-amber-500 shadow-xs p-4 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-calendar2-range"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="kpiLiburTahunLabel">Total Libur <?= $tahun ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 leading-none mt-0.5">
                        <span id="kpiLiburTahun"><?= $liburTahun ?></span>
                        <span class="text-xs font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- Kalender -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-calendar3"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-extrabold text-slate-900">Kalender Akademik</h2>
                                <p class="text-[11px] text-slate-400">Klik tanggal untuk menambah event. Hari libur absensi tampil merah.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-[11px] font-bold text-slate-600 shrink-0">
                            <i class="bi bi-calendar2-week text-slate-400"></i>
                            <span id="kalenderBulanBadge"><?= esc($nama_bulan . ' ' . $tahun) ?></span>
                        </span>
                    </div>
                    <div class="relative p-2 sm:p-4">
                        <div id="kalender" class="min-h-[460px]"></div>
                        <div id="kalenderLoading" class="absolute inset-0 bg-white/80 backdrop-blur-[1px] flex flex-col items-center justify-center gap-2 z-10 rounded-2xl">
                            <span class="inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                            <span class="text-xs font-semibold text-slate-500">Memuat kalender…</span>
                        </div>
                        <div id="kalenderError" class="hidden absolute inset-0 bg-white/95 flex-col items-center justify-center gap-2 z-10 rounded-2xl text-center px-4">
                            <i class="bi bi-wifi-off text-2xl text-slate-300"></i>
                            <p class="text-xs font-semibold text-slate-600 m-0" id="kalenderErrorText">Gagal memuat kalender.</p>
                            <button type="button" id="btnRetryFeed" class="mt-1 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-[11px] font-bold">Coba Lagi</button>
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/60 flex flex-wrap gap-x-4 gap-y-1.5 text-[10px] font-semibold text-slate-500">
                        <?php foreach ($tipeColors as $tipe => $warna): ?>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:<?= $warna ?>"></span><?= ucfirst(strtolower($tipe)) ?></span>
                        <?php endforeach; ?>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-200 border border-red-400 border-dashed"></span>Libur absensi</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-slate-100 border border-slate-200"></span>Akhir pekan</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-4">

                <!-- Agenda -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="flex items-center justify-between gap-2 px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="bi bi-list-ul text-blue-500"></i>
                            <h6 class="font-bold text-slate-700 mb-0 truncate">
                                Agenda <span id="agendaMonthLabel" class="text-slate-400 font-semibold"><?= esc($nama_bulan . ' ' . $tahun) ?></span>
                            </h6>
                        </div>
                        <span class="badge-tw badge-secondary text-xs shrink-0" id="agendaCount"><?= count($agenda) ?></span>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto" id="agendaList">
                        <?php if ($agenda): ?>
                            <div class="divide-y divide-slate-50">
                                <?php foreach ($agenda as $item): ?>
                                    <?php
                                    $isLiburItem = $item['kind'] === 'libur';
                                    $dateTs      = strtotime($item['date']);
                                    $cssWarna    = $isLiburItem ? '#ef4444' : $item['color'];
                                    ?>
                                    <div class="agenda-item group flex items-start gap-2.5 px-4 py-2.5 hover:bg-slate-50 cursor-pointer"
                                        role="button" tabindex="0"
                                        data-kind="<?= $item['kind'] ?>"
                                        data-id="<?= (int) $item['id'] ?>"
                                        data-date="<?= esc($item['date'], 'attr') ?>"
                                        data-desc="<?= esc($item['title'], 'attr') ?>">
                                        <div class="w-10 h-11 rounded-xl border flex flex-col items-center justify-center shrink-0"
                                            style="background: <?= esc($cssWarna, 'attr') ?>14; border-color: <?= esc($cssWarna, 'attr') ?>33; color: <?= esc($cssWarna, 'attr') ?>">
                                            <span class="text-sm font-extrabold leading-none"><?= date('d', $dateTs) ?></span>
                                            <span class="text-[9px] font-bold uppercase mt-0.5"><?= $bulanSingkat[(int) date('n', $dateTs)] ?></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-[13px] text-slate-800 truncate leading-snug m-0"><?= esc($item['title']) ?></p>
                                            <p class="text-[11px] text-slate-400 m-0 truncate"><?= esc($formatRentang($item['date'], $item['end'])) ?></p>
                                            <span class="inline-flex px-1.5 py-0.5 rounded border text-[9px] font-bold mt-1 <?= $isLiburItem ? 'bg-red-50 text-red-600 border-red-100' : ($tipeBadge[$item['tipe']] ?? 'bg-slate-100 text-slate-600 border-slate-200') ?>">
                                                <?= esc($item['tipe']) ?>
                                            </span>
                                        </div>
                                        <div class="agenda-actions flex items-center gap-1 shrink-0 opacity-60 group-hover:opacity-100 transition-opacity">
                                            <?php if ($isLiburItem): ?>
                                                <button type="button" class="agenda-holiday-edit w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center"
                                                    title="Kelola hari libur"
                                                    data-id="<?= (int) $item['id'] ?>"
                                                    data-date="<?= esc($item['date'], 'attr') ?>"
                                                    data-desc="<?= esc($item['title'], 'attr') ?>">
                                                    <i class="bi bi-pencil text-[11px]"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="edit-event w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center"
                                                    title="Edit event" data-id="<?= (int) $item['id'] ?>">
                                                    <i class="bi bi-pencil text-[11px]"></i>
                                                </button>
                                                <button type="button" class="delete-event w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 flex items-center justify-center"
                                                    title="Hapus event" data-id="<?= (int) $item['id'] ?>">
                                                    <i class="bi bi-trash3 text-[11px]"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-10 text-slate-400 text-xs px-4" id="agendaEmpty">
                                <i class="bi bi-calendar-plus block text-3xl mb-2 opacity-20"></i>
                                Belum ada agenda bulan ini.<br>Klik tanggal di kalender untuk menambah event.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Libur bulan ini -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="flex items-center justify-between gap-2 px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="bi bi-calendar-x text-red-500"></i>
                            <h6 class="font-bold text-slate-700 mb-0 truncate">
                                Libur <span class="text-slate-400 font-semibold" id="liburBulanLabel"><?= esc($nama_bulan) ?></span>
                            </h6>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center justify-center bg-red-50 text-red-600 border border-red-100 text-[10px] font-bold rounded-full w-5 h-5" id="liburBulanCount"><?= $liburBulan ?></span>
                            <button type="button" data-goto-tab="libur" class="text-[11px] text-blue-500 hover:underline font-semibold border-none bg-transparent cursor-pointer">Kelola →</button>
                        </div>
                    </div>
                    <div class="max-h-48 overflow-y-auto" id="liburBulanList">
                        <?php if ($holidaysBulan): ?>
                            <div class="divide-y divide-slate-50">
                                <?php foreach ($holidaysBulan as $h): ?>
                                    <button type="button"
                                        class="agenda-holiday-edit w-full text-left flex items-start gap-2.5 px-4 py-2.5 hover:bg-red-50/40 transition-colors border-none bg-transparent cursor-pointer"
                                        data-id="<?= (int) $h->id ?>"
                                        data-date="<?= esc($h->tanggal, 'attr') ?>"
                                        data-desc="<?= esc($h->keterangan, 'attr') ?>">
                                        <i class="bi bi-circle-fill text-red-400 text-[8px] mt-1.5 shrink-0"></i>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-500"><?= date('d/m', strtotime($h->tanggal)) ?></span>
                                            <span class="text-xs text-slate-600 ml-1"><?= esc($h->keterangan) ?></span>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-slate-400 text-xs" id="liburBulanEmpty">Tidak ada libur bulan ini</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info akhir pekan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Hari Libur Mingguan</p>
                    <div class="flex flex-wrap gap-1.5">
                        <?php if ($weeklyOff): ?>
                            <?php foreach ($weeklyOff as $wo): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-[11px] font-bold text-slate-600">
                                    <i class="bi bi-calendar-week text-slate-400"></i> <?= esc($wo['nama']) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-[11px] text-slate-400">Tidak ada hari libur mingguan.</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">
                        Diatur di <a href="<?= site_url('settings') ?>" class="text-blue-500 hover:underline">Pengaturan Absensi</a>.
                        Tanggal merah di tab Hari Libur otomatis menonaktifkan alfa &amp; denda.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════
         TAB: HARI LIBUR
    ══════════════════════════════════════════════ -->
    <div id="tabLibur" class="hidden">

        <!-- Peringatan kalender kosong -->
        <div id="bannerPeringatan" class="<?= empty($peringatan) ? 'hidden' : '' ?> mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 mt-0.5"></i>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-amber-800 m-0">Kalender libur <span id="bannerYear"><?= $tahun ?></span> masih kosong</p>
                <p class="text-[11px] text-amber-700 m-0 mt-0.5 leading-relaxed">
                    Tanpa data ini, tanggal merah akan dianggap hari kerja dan absensi guru tetap dihitung.
                </p>
            </div>
            <button type="button" class="btn-sync-holiday shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold">
                <i class="bi bi-cloud-arrow-down"></i> Sinkron
            </button>
        </div>

        <!-- KPI -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mb-5">
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 shadow-xs p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="kpiHolidayYearLabel">Total Libur <?= $tahun ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 leading-none mt-0.5">
                        <span id="kpiHolidayYear"><?= $liburTahun ?></span> <span class="text-sm font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-red-500 shadow-xs p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl shrink-0">
                    <i class="bi bi-calendar2-day"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="kpiHolidayMonthLabel">Libur <?= esc($nama_bulan) ?></p>
                    <p class="text-2xl font-extrabold text-red-500 leading-none mt-0.5">
                        <span id="kpiHolidayMonth"><?= $liburBulan ?></span> <span class="text-sm font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-amber-500 shadow-xs p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl shrink-0">
                    <i class="bi bi-calendar2-range"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" id="kpiCutiLabel">Cuti Bersama <?= $tahun ?></p>
                    <p class="text-2xl font-extrabold text-slate-900 leading-none mt-0.5">
                        <span id="kpiCuti"><?= $cutiTahun ?></span> <span class="text-sm font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- Form -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden sticky top-4">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="bi bi-plus-circle-fill text-sm" id="formIconHoliday"></i>
                        </div>
                        <h6 class="font-bold text-slate-700 mb-0" id="formCardTitle">Tambah Hari Libur</h6>
                    </div>

                    <div class="p-5">
                        <!-- Banner edit -->
                        <div id="formEditInfo" class="hidden mb-4 flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5">
                            <i class="bi bi-pencil-square text-blue-500 mt-0.5"></i>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold text-blue-800 m-0">Mode edit</p>
                                <p class="text-[10px] text-blue-600 m-0 leading-relaxed">Ubah tanggal atau keterangan, lalu klik Perbarui.</p>
                            </div>
                            <button type="button" id="btnCancelEdit" class="text-[10px] font-bold text-blue-600 hover:underline shrink-0 border-none bg-transparent cursor-pointer">Batal</button>
                        </div>

                        <form id="formHoliday" novalidate>
                            <input type="hidden" name="id" id="holidayId">
                            <input type="hidden" name="is_range" id="isRangeInput" value="0">

                            <div class="flex gap-2 mb-5" id="modeSelectorGroup">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" class="peer sr-only holiday-mode-radio" name="holiday_mode" id="modeSingle" value="0" checked>
                                    <div class="mode-radio-label flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border-2 border-slate-200 text-slate-500 text-xs font-semibold">
                                        <i class="bi bi-calendar-event"></i> Satu Tanggal
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" class="peer sr-only holiday-mode-radio" name="holiday_mode" id="modeRange" value="1">
                                    <div class="mode-radio-label flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border-2 border-slate-200 text-slate-500 text-xs font-semibold">
                                        <i class="bi bi-calendar-range"></i> Rentang
                                    </div>
                                </label>
                            </div>

                            <div class="mb-4" id="singleDateGroup">
                                <label class="form-label-tw">Tanggal Libur <span class="text-red-500">*</span></label>
                                <input type="date" class="form-control-tw" name="tanggal" id="holidayDate">
                            </div>

                            <div class="grid grid-cols-2 gap-2 mb-4 hidden" id="rangeDateGroup">
                                <div>
                                    <label class="form-label-tw">Mulai <span class="text-red-500">*</span></label>
                                    <input type="date" class="form-control-tw" name="tanggal_mulai" id="holidayDateStart">
                                </div>
                                <div>
                                    <label class="form-label-tw">Selesai <span class="text-red-500">*</span></label>
                                    <input type="date" class="form-control-tw" name="tanggal_selesai" id="holidayDateEnd">
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[11px] text-slate-400">Semua hari dalam rentang ditambahkan (maks. 366 hari).</p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-tw">Keterangan / Nama Libur <span class="text-red-500">*</span></label>
                                <textarea class="form-control-tw" name="keterangan" id="holidayDesc" rows="3" maxlength="255"
                                    placeholder="Contoh: Hari Raya Idul Fitri / Libur Semester Ganjil" required></textarea>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <?php foreach (['Libur Nasional', 'Cuti Bersama', 'Libur Semester', 'Libur Sekolah', 'Libur Awal Puasa'] as $preset): ?>
                                        <button type="button" class="preset-libur px-2 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10px] font-semibold text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-colors" data-text="<?= esc($preset, 'attr') ?>"><?= esc($preset) ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="flex gap-2 mt-5">
                                <button type="submit" class="btn-tw btn-primary flex-1" id="btnSubmitHoliday">
                                    <i class="bi bi-save mr-1"></i> Simpan
                                </button>
                                <button type="button" class="btn-tw btn-light" id="btnResetHoliday">
                                    <i class="bi bi-x mr-1"></i> Batal
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-100">
                        <p class="text-[11px] text-slate-500 mb-0 leading-relaxed">
                            <i class="bi bi-info-circle text-blue-500 mr-1"></i>
                            Tanggal di sini otomatis menonaktifkan alfa &amp; denda absensi guru.
                            Akhir pekan (<?= esc($weeklyOffLabel) ?>) sudah libur secara otomatis.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabel -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

                    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <i class="bi bi-list-check text-blue-600"></i>
                            <h6 class="font-bold text-slate-700 mb-0">Daftar Hari Libur</h6>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold" id="tableCounterBadge">
                                <?= count($holidays) ?> Data
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <select class="form-select-tw" id="syncYearSelect" style="width:92px;" aria-label="Tahun sinkronisasi">
                                <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                                <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="button" class="btn-tw btn-outline-primary flex items-center gap-1.5 btn-sync-holiday" id="btnSync">
                                <i class="bi bi-cloud-arrow-down-fill"></i>
                                <span class="hidden sm:inline">Sinkron Nasional</span>
                            </button>
                        </div>
                    </div>

                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-1.5 bg-white flex-1 min-w-[170px] max-w-[260px]">
                            <i class="bi bi-search text-slate-400 text-xs"></i>
                            <input type="text" id="filterHolidaySearch" class="border-0 outline-none bg-transparent text-xs flex-1"
                                placeholder="Cari keterangan...">
                        </div>
                        <select class="form-select-tw" id="filterYear" style="width:120px;" data-default-year="<?= $tahunAda ? $tahun : '' ?>" aria-label="Filter tahun">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahunList as $yr): ?>
                                <option value="<?= esc($yr, 'attr') ?>" <?= $yr === (string) $tahun ? 'selected' : '' ?>><?= esc($yr) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select-tw" id="filterMonth" style="width:130px;" aria-label="Filter bulan">
                            <option value="">Semua Bulan</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= sprintf('%02d', $m) ?>"><?= $namaBulanId[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="button" id="btnResetHolidayFilter" class="text-xs text-slate-400 hover:text-slate-600 font-medium px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors border-none bg-transparent cursor-pointer">
                            Reset
                        </button>
                    </div>

                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="table-tw" id="holidayTable">
                            <thead>
                                <tr>
                                    <th class="text-center w-10">#</th>
                                    <th class="w-44">Tanggal</th>
                                    <th>Keterangan Hari Libur</th>
                                    <th class="text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($holidays)): ?>
                                <tr id="emptyRow">
                                    <td colspan="4" class="text-center text-slate-400 py-14">
                                        <i class="bi bi-calendar-x block text-4xl mb-3 opacity-20"></i>
                                        <p class="font-semibold text-slate-600 mb-2">Belum ada data hari libur</p>
                                        <button type="button" class="btn-tw btn-outline-primary btn-tw-sm btn-sync-holiday">
                                            <i class="bi bi-cloud-arrow-down mr-1"></i> Sinkronkan Hari Libur Nasional
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($holidays as $idx => $h):
                                    $tglTs  = strtotime($h->tanggal);
                                    $isCuti = stripos((string) $h->keterangan, 'cuti') !== false;
                                    $isSun  = date('N', $tglTs) == 7;
                                    [$iconBg, $iconText] = $isCuti
                                        ? ['bg-amber-50 border border-amber-200', 'text-amber-700']
                                        : ($isSun
                                            ? ['bg-red-50 border border-red-200', 'text-red-600']
                                            : ['bg-blue-50 border border-blue-200', 'text-blue-600']);
                                ?>
                                <tr class="holiday-row hover:bg-slate-50 transition-colors"
                                    data-date="<?= esc($h->tanggal, 'attr') ?>"
                                    data-year="<?= date('Y', $tglTs) ?>"
                                    data-month="<?= date('m', $tglTs) ?>"
                                    data-desc="<?= mb_strtolower(esc($h->keterangan, 'attr')) ?>">

                                    <td class="px-4 py-3 text-center text-slate-400 font-semibold text-xs row-num"><?= $idx + 1 ?></td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="date-icon <?= $iconBg ?> <?= $iconText ?>">
                                                <span class="font-bold text-base leading-none"><?= date('d', $tglTs) ?></span>
                                                <span class="text-[9px] uppercase font-semibold"><?= $bulanSingkat[(int) date('n', $tglTs)] ?></span>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-slate-800 block text-sm"><?= date('d/m/Y', $tglTs) ?></span>
                                                <span class="text-xs text-slate-400"><?= buat_tanggal(date('l', $tglTs)) ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-800 text-sm m-0"><?= esc($h->keterangan) ?></p>
                                        <?php if ($isCuti): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold mt-0.5">
                                            <i class="bi bi-tag-fill text-[9px]"></i> Cuti Bersama
                                        </span>
                                        <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold mt-0.5">
                                            <i class="bi bi-flag-fill text-[9px]"></i> Libur Resmi
                                        </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button"
                                                class="btn-edit-holiday w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 flex items-center justify-center transition-colors"
                                                title="Edit"
                                                data-id="<?= (int) $h->id ?>"
                                                data-date="<?= esc($h->tanggal, 'attr') ?>"
                                                data-desc="<?= esc($h->keterangan, 'attr') ?>">
                                                <i class="bi bi-pencil text-xs"></i>
                                            </button>
                                            <button type="button"
                                                class="btn-del-holiday w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 border border-red-200 flex items-center justify-center transition-colors"
                                                title="Hapus"
                                                data-id="<?= (int) $h->id ?>"
                                                data-desc="<?= esc(date('d/m/Y', $tglTs) . ' - ' . $h->keterangan, 'attr') ?>">
                                                <i class="bi bi-trash3 text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="noFilterResult" class="hidden text-center py-10 text-slate-400">
                        <i class="bi bi-search block text-3xl mb-2 opacity-20"></i>
                        <p class="text-sm font-semibold text-slate-600">Tidak ada data yang cocok</p>
                        <button type="button" id="btnResetHolidayFilter2" class="mt-2 text-xs text-blue-500 hover:underline border-none bg-transparent cursor-pointer">Reset Filter</button>
                    </div>

                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-400">
                        <i class="bi bi-info-circle mr-1 text-blue-500"></i>
                        Tanggal terdaftar di sini akan meniadakan denda &amp; alfa absensi guru secara otomatis.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     Modal: Tambah / Edit Event
══════════════════════════════════════════════ -->
<div id="modalEvent" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw max-w-lg w-full">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
            <form id="formEvent">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-calendar-plus text-sm"></i>
                        </span>
                        <h5 class="font-bold text-slate-800 text-sm mb-0 truncate" id="modalEventTitle">Tambah Event</h5>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none shrink-0" data-ui-dismiss="modal">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="id" id="eventId">

                    <div>
                        <label class="form-label-tw">Judul <span class="text-red-500">*</span></label>
                        <input type="text" class="form-control-tw" name="judul" id="eventJudul" required maxlength="255" placeholder="Nama event / kegiatan">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label-tw">Tgl Mulai <span class="text-red-500">*</span></label>
                            <input type="date" class="form-control-tw" name="tgl_mulai" id="eventTglMulai" required>
                        </div>
                        <div>
                            <label class="form-label-tw">Tgl Selesai <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="date" class="form-control-tw" name="tgl_selesai" id="eventTglSelesai">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label-tw">Tipe Event</label>
                            <select class="form-select-tw" name="tipe" id="eventTipe">
                                <option value="EVENT">Event / Kegiatan</option>
                                <option value="UJIAN">Ujian / Penilaian</option>
                                <option value="LIBUR">Libur Sekolah</option>
                                <option value="RAPAT">Rapat / Pertemuan</option>
                                <option value="DINAS">Dinas / Kunjungan</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label-tw">Warna Penanda</label>
                            <div class="flex items-center gap-2">
                                <input type="color" class="h-10 w-14 rounded border border-slate-200 cursor-pointer" name="warna" id="eventWarna" value="#3b82f6">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($tipeColors as $warna): ?>
                                    <button type="button" class="event-color-swatch w-5 h-5 rounded-full border-2 border-white ring-1 ring-slate-200" data-color="<?= $warna ?>" style="background:<?= $warna ?>" aria-label="Warna <?= $warna ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="eventTipeNote" class="hidden flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                        <i class="bi bi-info-circle-fill text-amber-500 mt-0.5"></i>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-amber-800 m-0">Event LIBUR tidak otomatis meliburkan absensi</p>
                            <p class="text-[10px] text-amber-700 m-0 mt-0.5 leading-relaxed">
                                Agar alfa &amp; denda guru ikut nonaktif, daftarkan tanggalnya di tab Hari Libur.
                            </p>
                            <button type="button" id="btnKeHariLibur" class="mt-1 text-[10px] font-bold text-amber-700 underline border-none bg-transparent cursor-pointer p-0">Kelola Hari Libur</button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label-tw">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <textarea class="form-control-tw" name="deskripsi" id="eventDeskripsi" rows="3" maxlength="2000" placeholder="Catatan tambahan tentang event ini..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-2">
                    <button type="button" id="btnDeleteEvent" class="hidden inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold transition-colors">
                        <i class="bi bi-trash3"></i> Hapus
                    </button>
                    <div class="flex items-center justify-end gap-2 ml-auto">
                        <button type="button" class="btn-tw btn-light" data-ui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-tw btn-primary" id="btnSimpanEvent">
                            <i class="bi bi-save mr-1"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
<script>
const KAL = {
    feedUrl:      <?= json_encode(site_url('admin/kalender/get-events'), $jsonFlags) ?>,
    createUrl:    <?= json_encode(site_url('admin/kalender/create'), $jsonFlags) ?>,
    updateUrl:    <?= json_encode(site_url('admin/kalender/update'), $jsonFlags) ?>,
    deleteUrl:    <?= json_encode(site_url('admin/kalender/delete'), $jsonFlags) ?>,
    getEventUrl:  <?= json_encode(site_url('admin/kalender/get-event'), $jsonFlags) ?>,
    holidaySave:  <?= json_encode(site_url('admin/kalender/holiday/save'), $jsonFlags) ?>,
    holidayDelete:<?= json_encode(site_url('admin/kalender/holiday/delete'), $jsonFlags) ?>,
    holidaySync:  <?= json_encode(site_url('admin/kalender/holiday/sync'), $jsonFlags) ?>,
    csrfName:     <?= json_encode(csrf_token(), $jsonFlags) ?>,
    csrfHash:     <?= json_encode(csrf_hash(), $jsonFlags) ?>,
    initialDate:  <?= json_encode(sprintf('%04d-%02d-01', $tahun, $bulan), $jsonFlags) ?>,
    liburIso:     <?= json_encode(array_values($liburIso), $jsonFlags) ?>,
    bulanNama:    <?= json_encode(array_slice($namaBulanId, 1), $jsonFlags) ?>,
    bulanSingkat: <?= json_encode(array_slice($bulanSingkat, 1), $jsonFlags) ?>,
    viewYear:     <?= (int) $tahun ?>,
    viewMonth:    <?= (int) $bulan ?>,
    lastSyncedYear: null
};

const HARI_INDO = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
let lastFeedEvents = [];
let feedLoadedOnce = false;

/* ── Utilities ─────────────────────────────────── */
const pad2 = n => String(n).padStart(2, '0');
const ymd = d => d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
}

function hexAlpha(hex, alpha) {
    const clean = String(hex || '').replace('#', '');
    if (!/^[0-9a-fA-F]{6}$/.test(clean)) return 'rgba(59,130,246,' + alpha + ')';
    const r = parseInt(clean.slice(0, 2), 16);
    const g = parseInt(clean.slice(2, 4), 16);
    const b = parseInt(clean.slice(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function eventStartDate(ev) {
    if (ev.start instanceof Date) return ymd(ev.start);
    return String(ev.start || '').slice(0, 10);
}

function eventEndInclusive(ev) {
    if (!ev.end) return eventStartDate(ev);
    const d = ev.end instanceof Date
        ? new Date(ev.end.getFullYear(), ev.end.getMonth(), ev.end.getDate())
        : new Date(String(ev.end).slice(0, 10) + 'T00:00:00');
    d.setDate(d.getDate() - 1);
    return ymd(d);
}

function monthBounds(year, month) {
    return {
        first: ymd(new Date(year, month - 1, 1)),
        last: ymd(new Date(year, month, 0)),
        total: new Date(year, month, 0).getDate()
    };
}

function formatRange(start, end) {
    const [y1, m1, d1] = start.split('-').map(Number);
    const [y2, m2, d2] = end.split('-').map(Number);
    const short = m => KAL.bulanSingkat[m - 1];
    if (start === end) {
        return HARI_INDO[new Date(y1, m1 - 1, d1).getDay()] + ', ' + d1 + ' ' + short(m1) + ' ' + y1;
    }
    if (y1 === y2 && m1 === m2) {
        return d1 + '–' + d2 + ' ' + short(m1) + ' ' + y1;
    }
    return d1 + ' ' + short(m1) + ' – ' + d2 + ' ' + short(m2) + ' ' + y2;
}

function normalizeAgenda(events, year, month) {
    const b = monthBounds(year, month);
    return events.filter(ev => {
        const start = eventStartDate(ev);
        const end = eventEndInclusive(ev);
        return start <= b.last && end >= b.first;
    }).sort((a, c) =>
        eventStartDate(a).localeCompare(eventStartDate(c)) ||
        String(a.title).localeCompare(String(c.title), 'id')
    );
}

function apiPost(url, formData) {
    formData.append(KAL.csrfName, KAL.csrfHash);
    return fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json());
}

/* ── Tab ───────────────────────────────────────── */
function switchTab(tab) {
    const showKalender = tab !== 'libur';
    document.getElementById('tabKalender').classList.toggle('hidden', !showKalender);
    document.getElementById('tabLibur').classList.toggle('hidden', showKalender);

    document.getElementById('tabBtnKalender').classList.toggle('active', showKalender);
    document.getElementById('tabBtnKalender').classList.toggle('text-slate-500', !showKalender);
    document.getElementById('tabBtnLibur').classList.toggle('active', !showKalender);
    document.getElementById('tabBtnLibur').classList.toggle('text-slate-500', showKalender);

    if (showKalender && window._calendar) window._calendar.updateSize();
    if (!showKalender) filterHolidayTable();

    try { localStorage.setItem('kalenderTab', showKalender ? 'kalender' : 'libur'); } catch (e) {}
}
window.switchTab = switchTab;

/* ── Agenda rendering ──────────────────────────── */
function agendaItemHtml(ev) {
    const props = ev.extendedProps || {};
    const isLibur = props.sumber === 'absensi_libur';
    const start = eventStartDate(ev);
    const end = eventEndInclusive(ev);
    const [y, m, d] = start.split('-').map(Number);
    const color = isLibur ? '#ef4444' : (ev.backgroundColor || '#3b82f6');
    const badgeClass = isLibur
        ? 'bg-red-50 text-red-600 border-red-100'
        : (props.tipe === 'UJIAN' ? 'bg-purple-50 text-purple-700 border-purple-100'
            : props.tipe === 'RAPAT' ? 'bg-amber-50 text-amber-700 border-amber-100'
            : props.tipe === 'DINAS' ? 'bg-cyan-50 text-cyan-700 border-cyan-100'
            : props.tipe === 'LIBUR' ? 'bg-red-50 text-red-700 border-red-100'
            : 'bg-blue-50 text-blue-700 border-blue-100');
    const actions = isLibur
        ? `<button type="button" class="agenda-holiday-edit w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center" title="Kelola hari libur" data-id="${props.holiday_id}" data-date="${start}" data-desc="${escapeHtml(ev.title)}"><i class="bi bi-pencil text-[11px]"></i></button>`
        : `<button type="button" class="edit-event w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center" title="Edit event" data-id="${props.event_id}"><i class="bi bi-pencil text-[11px]"></i></button>
           <button type="button" class="delete-event w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 flex items-center justify-center" title="Hapus event" data-id="${props.event_id}"><i class="bi bi-trash3 text-[11px]"></i></button>`;

    return `<div class="agenda-item group flex items-start gap-2.5 px-4 py-2.5 hover:bg-slate-50 cursor-pointer"
        role="button" tabindex="0"
        data-kind="${isLibur ? 'libur' : 'event'}"
        data-id="${isLibur ? props.holiday_id : props.event_id}"
        data-date="${start}"
        data-desc="${escapeHtml(ev.title)}">
        <div class="w-10 h-11 rounded-xl border flex flex-col items-center justify-center shrink-0"
            style="background:${hexAlpha(color, .08)};border-color:${hexAlpha(color, .2)};color:${color}">
            <span class="text-sm font-extrabold leading-none">${pad2(d)}</span>
            <span class="text-[9px] font-bold uppercase mt-0.5">${KAL.bulanSingkat[m - 1]}</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-[13px] text-slate-800 truncate leading-snug m-0">${escapeHtml(ev.title)}</p>
            <p class="text-[11px] text-slate-400 m-0 truncate">${escapeHtml(formatRange(start, end))}</p>
            <span class="inline-flex px-1.5 py-0.5 rounded border text-[9px] font-bold mt-1 ${badgeClass}">${escapeHtml(props.tipe || 'EVENT')}</span>
        </div>
        <div class="agenda-actions flex items-center gap-1 shrink-0 opacity-60 group-hover:opacity-100 transition-opacity">${actions}</div>
    </div>`;
}

function renderAgenda(events) {
    const list = document.getElementById('agendaList');
    if (!list) return;
    const items = normalizeAgenda(events, KAL.viewYear, KAL.viewMonth);

    setText('agendaCount', items.length);
    setText('agendaMonthLabel', KAL.bulanNama[KAL.viewMonth - 1] + ' ' + KAL.viewYear);
    setText('kalenderBulanBadge', KAL.bulanNama[KAL.viewMonth - 1] + ' ' + KAL.viewYear);

    if (!items.length) {
        list.innerHTML = `<div class="text-center py-10 text-slate-400 text-xs px-4" id="agendaEmpty">
            <i class="bi bi-calendar-plus block text-3xl mb-2 opacity-20"></i>
            Belum ada agenda bulan ini.<br>Klik tanggal di kalender untuk menambah event.
        </div>`;
        return;
    }

    list.innerHTML = '<div class="divide-y divide-slate-50">' + items.map(agendaItemHtml).join('') + '</div>';
}

function renderLiburBulan(events) {
    const list = document.getElementById('liburBulanList');
    if (!list) return;
    const items = normalizeAgenda(events, KAL.viewYear, KAL.viewMonth)
        .filter(ev => (ev.extendedProps || {}).sumber === 'absensi_libur');

    setText('liburBulanCount', items.length);
    setText('liburBulanLabel', KAL.bulanNama[KAL.viewMonth - 1]);

    if (!items.length) {
        list.innerHTML = '<div class="text-center py-5 text-slate-400 text-xs" id="liburBulanEmpty">Tidak ada libur bulan ini</div>';
        return;
    }

    list.innerHTML = '<div class="divide-y divide-slate-50">' + items.map(ev => {
        const start = eventStartDate(ev);
        const [, m, d] = start.split('-');
        return `<button type="button"
            class="agenda-holiday-edit w-full text-left flex items-start gap-2.5 px-4 py-2.5 hover:bg-red-50/40 transition-colors border-none bg-transparent cursor-pointer"
            data-id="${(ev.extendedProps || {}).holiday_id}"
            data-date="${start}"
            data-desc="${escapeHtml(ev.title)}">
            <i class="bi bi-circle-fill text-red-400 text-[8px] mt-1.5 shrink-0"></i>
            <div class="min-w-0">
                <span class="text-xs font-bold text-slate-500">${d}/${m}</span>
                <span class="text-xs text-slate-600 ml-1">${escapeHtml(ev.title)}</span>
            </div>
        </button>`;
    }).join('') + '</div>';
}

function updateKpis(events) {
    const year = KAL.viewYear;
    const month = KAL.viewMonth;
    const b = monthBounds(year, month);
    const monthly = normalizeAgenda(events, year, month);
    const eventsOnly = monthly.filter(ev => (ev.extendedProps || {}).sumber === 'kalender');
    const liburOnly = monthly.filter(ev => (ev.extendedProps || {}).sumber === 'absensi_libur');

    setText('kpiEventBulanLabel', 'Event ' + KAL.bulanNama[month - 1]);
    setText('kpiEventBulan', eventsOnly.length);
    setText('kpiLiburBulanLabel', 'Libur ' + KAL.bulanNama[month - 1]);
    setText('kpiLiburBulan', liburOnly.length);

    const tanggalLibur = new Set(liburOnly.map(eventStartDate));
    let hariSekolah = 0;
    for (let d = 1; d <= b.total; d++) {
        const dt = new Date(year, month - 1, d);
        const iso = dt.getDay() === 0 ? 7 : dt.getDay();
        if (KAL.liburIso.includes(iso)) continue;
        if (tanggalLibur.has(ymd(dt))) continue;
        hariSekolah++;
    }
    setText('kpiHariSekolah', hariSekolah);
    setText('kpiTotalHari', '/ ' + b.total);

    let yearCount = 0;
    let cutiCount = 0;
    document.querySelectorAll('.holiday-row').forEach(row => {
        if (row.dataset.year !== String(year)) return;
        yearCount++;
        if ((row.dataset.desc || '').includes('cuti')) cutiCount++;
    });

    setText('kpiLiburTahunLabel', 'Total Libur ' + year);
    setText('kpiLiburTahun', yearCount);
    setText('kpiHolidayYearLabel', 'Total Libur ' + year);
    setText('kpiHolidayYear', yearCount);
    setText('kpiHolidayMonthLabel', 'Libur ' + KAL.bulanNama[month - 1]);
    setText('kpiHolidayMonth', liburOnly.length);
    setText('kpiCutiLabel', 'Cuti Bersama ' + year);
    setText('kpiCuti', cutiCount);

    const banner = document.getElementById('bannerPeringatan');
    if (banner) {
        banner.classList.toggle('hidden', yearCount > 0);
        setText('bannerYear', year);
    }
}

function syncMonthViews(viewStart, events) {
    KAL.viewYear = viewStart.getFullYear();
    KAL.viewMonth = viewStart.getMonth() + 1;
    renderAgenda(events);
    renderLiburBulan(events);
    updateKpis(events);

    // Ikutkan tabel & pilihan sync libur saat navigasi melewati tahun.
    if (KAL.lastSyncedYear !== KAL.viewYear) {
        KAL.lastSyncedYear = KAL.viewYear;

        const yearSelect = document.getElementById('filterYear');
        if (yearSelect) {
            const ada = Array.from(yearSelect.options).some(o => o.value === String(KAL.viewYear));
            yearSelect.value = ada ? String(KAL.viewYear) : '';
            filterHolidayTable();
        }

        const syncYear = document.getElementById('syncYearSelect');
        if (syncYear && Array.from(syncYear.options).some(o => o.value === String(KAL.viewYear))) {
            syncYear.value = String(KAL.viewYear);
        }
    }

    const url = new URL(window.location.href);
    url.searchParams.set('bulan', KAL.viewMonth);
    url.searchParams.set('tahun', KAL.viewYear);
    history.replaceState({}, '', url.toString());
}

/* ── Calendar ──────────────────────────────────── */
function showCalendarLoading() {
    document.getElementById('kalenderLoading')?.classList.remove('hidden');
    document.getElementById('kalenderError')?.classList.add('hidden');
    document.getElementById('kalenderError')?.classList.remove('flex');
}
function hideCalendarLoading() {
    document.getElementById('kalenderLoading')?.classList.add('hidden');
}
function showCalendarError(message) {
    const box = document.getElementById('kalenderError');
    if (!box) return;
    setText('kalenderErrorText', message || 'Gagal memuat kalender.');
    box.classList.remove('hidden');
    box.classList.add('flex');
}

function loadFeed(fetchInfo) {
    const params = new URLSearchParams({
        start: ymd(fetchInfo.start),
        end: ymd(fetchInfo.end)
    });
    return fetch(KAL.feedUrl + '?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    }).then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    }).then(data => {
        if (!Array.isArray(data)) throw new Error('Format feed tidak valid');
        return data;
    });
}

function initCalendar() {
    const el = document.getElementById('kalender');
    if (!el) return;

    if (typeof FullCalendar === 'undefined') {
        hideCalendarLoading();
        showCalendarError('Modul kalender gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.');
        return;
    }

    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        initialDate: KAL.initialDate,
        locale: 'id',
        firstDay: 1,
        height: 'auto',
        expandRows: true,
        dayMaxEventRows: 3,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        buttonText: { today: 'Hari ini', month: 'Bulan', list: 'Daftar' },
        dayCellClassNames: function (arg) {
            const iso = arg.date.getDay() === 0 ? 7 : arg.date.getDay();
            return KAL.liburIso.includes(iso) ? ['kalender-akhir-pekan'] : [];
        },
        events: function (fetchInfo, successCallback, failureCallback) {
            showCalendarLoading();
            loadFeed(fetchInfo).then(events => {
                lastFeedEvents = events;
                feedLoadedOnce = true;
                successCallback(events);
                syncMonthViews(calendar.view.currentStart, events);
                hideCalendarLoading();
            }).catch(err => {
                console.error('[Kalender]', err);
                hideCalendarLoading();
                showCalendarError('Gagal memuat agenda. Periksa koneksi server.');
                failureCallback(err);
            });
        },
        datesSet: function (info) {
            if (!feedLoadedOnce) {
                setText('kalenderBulanBadge', KAL.bulanNama[info.view.currentStart.getMonth()] + ' ' + info.view.currentStart.getFullYear());
                return;
            }
            syncMonthViews(info.view.currentStart, lastFeedEvents);
        },
        eventClick: function (info) {
            const props = info.event.extendedProps || {};
            if (props.sumber === 'absensi_libur') {
                switchTab('libur');
                editHoliday(props.holiday_id, eventStartDate(info.event), info.event.title);
                return;
            }
            fetchEventAndOpen(props.event_id);
        },
        dateClick: function (info) {
            openEventModal('add', { tgl_mulai: info.dateStr });
        },
        eventDidMount: function (info) {
            const desc = (info.event.extendedProps || {}).description;
            info.el.title = desc ? desc : info.event.title;
        }
    });

    calendar.render();
    window._calendar = calendar;

    document.getElementById('btnRetryFeed')?.addEventListener('click', function () {
        showCalendarLoading();
        calendar.refetchEvents();
    });
}

function refetchCalendar() {
    if (window._calendar) {
        window._calendar.refetchEvents();
    } else {
        window.location.reload();
    }
}

/* ── Event modal ───────────────────────────────── */
const EVENT_MODAL = document.getElementById('modalEvent');
const EVENT_FORM = document.getElementById('formEvent');

function updateTipeNote() {
    const tipe = document.getElementById('eventTipe').value;
    document.getElementById('eventTipeNote').classList.toggle('hidden', tipe !== 'LIBUR');
}

function updateEndMin() {
    const start = document.getElementById('eventTglMulai').value;
    document.getElementById('eventTglSelesai').min = start || '';
}

function openEventModal(mode, data = {}) {
    setText('modalEventTitle', mode === 'edit' ? 'Edit Event' : 'Tambah Event');
    document.getElementById('eventId').value         = data.id || '';
    document.getElementById('eventJudul').value      = data.judul || '';
    document.getElementById('eventTglMulai').value   = data.tgl_mulai || '';
    document.getElementById('eventTglSelesai').value = data.tgl_selesai || '';
    document.getElementById('eventTipe').value       = data.tipe || 'EVENT';
    document.getElementById('eventWarna').value      = data.warna || '#3b82f6';
    document.getElementById('eventDeskripsi').value  = data.deskripsi || '';
    document.getElementById('btnDeleteEvent').classList.toggle('hidden', mode !== 'edit');
    updateTipeNote();
    updateEndMin();
    AdminUI.modal.open(EVENT_MODAL);
}

function fetchEventAndOpen(id) {
    if (!id) return;
    fetch(KAL.getEventUrl + '/' + encodeURIComponent(id), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(res => {
        if (!res.status) {
            Swal.fire('Gagal', res.message || 'Event tidak ditemukan', 'error');
            return;
        }
        const ev = res.data;
        openEventModal('edit', {
            id: ev.id,
            judul: ev.judul,
            tgl_mulai: ev.tgl_mulai,
            tgl_selesai: ev.tgl_selesai || '',
            tipe: ev.tipe,
            warna: ev.warna,
            deskripsi: ev.deskripsi || ''
        });
    }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
}

EVENT_FORM.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!EVENT_FORM.checkValidity()) {
        EVENT_FORM.reportValidity();
        return;
    }

    const id = document.getElementById('eventId').value;
    const tglMulai = document.getElementById('eventTglMulai').value;
    const tglSelesai = document.getElementById('eventTglSelesai').value;
    if (tglSelesai && tglSelesai < tglMulai) {
        Swal.fire('Periksa tanggal', 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSimpanEvent');
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span> Menyimpan...';

    const formData = new FormData(EVENT_FORM);
    apiPost(id ? KAL.updateUrl + '/' + id : KAL.createUrl, formData)
        .then(res => {
            if (!res.status) {
                Swal.fire('Gagal', res.message || 'Event gagal disimpan', 'error');
                return;
            }
            AdminUI.modal.close(EVENT_MODAL);
            refetchCalendar();
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1400, showConfirmButton: false });
        })
        .catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = original;
        });
});

document.getElementById('btnDeleteEvent').addEventListener('click', function () {
    const id = document.getElementById('eventId').value;
    if (!id) return;
    confirmDeleteEvent(id);
});

document.getElementById('eventTipe').addEventListener('change', function () {
    updateTipeNote();
    const swatch = document.querySelector('.event-color-swatch[data-color="' + this.value + '"]');
    const map = { EVENT: '#3b82f6', UJIAN: '#8b5cf6', LIBUR: '#ef4444', RAPAT: '#f59e0b', DINAS: '#06b6d4', LAINNYA: '#64748b' };
    if (map[this.value]) document.getElementById('eventWarna').value = map[this.value];
});
document.getElementById('eventTglMulai').addEventListener('change', updateEndMin);
document.querySelectorAll('.event-color-swatch').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('eventWarna').value = btn.dataset.color;
    });
});
document.getElementById('btnKeHariLibur').addEventListener('click', function () {
    AdminUI.modal.close(EVENT_MODAL);
    switchTab('libur');
    document.getElementById('holidayDesc').focus();
});

function confirmDeleteEvent(id) {
    Swal.fire({
        title: 'Hapus Event?',
        text: 'Event akan dihapus dari kalender akademik.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        apiPost(KAL.deleteUrl + '/' + id, fd).then(res => {
            if (!res.status) {
                Swal.fire('Gagal', res.message || 'Event gagal dihapus', 'error');
                return;
            }
            if (document.getElementById('eventId').value === String(id)) {
                AdminUI.modal.close(EVENT_MODAL);
            }
            refetchCalendar();
            Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message, timer: 1300, showConfirmButton: false });
        }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}

/* ── Hari libur ────────────────────────────────── */
function toggleHolidayMode(isRange) {
    document.getElementById('isRangeInput').value = isRange;
    const single = document.getElementById('singleDateGroup');
    const range = document.getElementById('rangeDateGroup');
    if (String(isRange) === '1') {
        single.classList.add('hidden');
        range.classList.remove('hidden');
        document.getElementById('holidayDate').removeAttribute('required');
        document.getElementById('holidayDateStart').setAttribute('required', 'required');
        document.getElementById('holidayDateEnd').setAttribute('required', 'required');
    } else {
        single.classList.remove('hidden');
        range.classList.add('hidden');
        document.getElementById('holidayDate').setAttribute('required', 'required');
        document.getElementById('holidayDateStart').removeAttribute('required');
        document.getElementById('holidayDateEnd').removeAttribute('required');
    }
}

function setHolidayToday() {
    const now = new Date();
    document.getElementById('holidayDate').value = ymd(now);
}

function editHoliday(id, date, desc) {
    switchTab('libur');
    document.getElementById('holidayId').value = id;
    document.getElementById('holidayDate').value = date;
    document.getElementById('holidayDesc').value = desc;
    document.getElementById('modeSingle').checked = true;
    toggleHolidayMode(0);
    document.getElementById('modeSelectorGroup').classList.add('hidden');
    document.getElementById('formEditInfo').classList.remove('hidden');
    setText('formCardTitle', 'Edit Hari Libur');
    document.getElementById('formIconHoliday').className = 'bi bi-pencil-square text-sm';
    document.getElementById('btnSubmitHoliday').innerHTML = '<i class="bi bi-save mr-1"></i> Perbarui';
    document.getElementById('formHoliday').scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('holidayDesc').focus({ preventScroll: true });
}

function resetHolidayForm() {
    document.getElementById('formHoliday').reset();
    document.getElementById('holidayId').value = '';
    document.getElementById('modeSelectorGroup').classList.remove('hidden');
    document.getElementById('formEditInfo').classList.add('hidden');
    document.getElementById('modeSingle').checked = true;
    toggleHolidayMode(0);
    setText('formCardTitle', 'Tambah Hari Libur');
    document.getElementById('formIconHoliday').className = 'bi bi-plus-circle-fill text-sm';
    document.getElementById('btnSubmitHoliday').innerHTML = '<i class="bi bi-save mr-1"></i> Simpan';
    setHolidayToday();
}
window.resetHolidayForm = resetHolidayForm;
window.toggleHolidayMode = toggleHolidayMode;
window.editHoliday = editHoliday;

document.getElementById('formHoliday').addEventListener('submit', function (e) {
    e.preventDefault();

    const isRange = document.getElementById('isRangeInput').value === '1';
    const keterangan = document.getElementById('holidayDesc').value.trim();
    if (!keterangan) {
        Swal.fire('Belum lengkap', 'Keterangan hari libur wajib diisi.', 'warning');
        document.getElementById('holidayDesc').focus();
        return;
    }
    if (isRange) {
        const mulai = document.getElementById('holidayDateStart').value;
        const selesai = document.getElementById('holidayDateEnd').value;
        if (!mulai || !selesai) {
            Swal.fire('Belum lengkap', 'Tanggal mulai dan selesai wajib diisi.', 'warning');
            return;
        }
        if (mulai > selesai) {
            Swal.fire('Periksa tanggal', 'Tanggal mulai tidak boleh setelah tanggal selesai.', 'warning');
            return;
        }
    } else if (!document.getElementById('holidayDate').value) {
        Swal.fire('Belum lengkap', 'Tanggal libur wajib diisi.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSubmitHoliday');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span> Menyimpan...';

    const formData = new FormData(e.target);
    apiPost(KAL.holidaySave, formData)
        .then(res => {
            if (!res.status) {
                Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');
                return;
            }
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        })
        .catch(() => Swal.fire('Error', 'Kesalahan koneksi server', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = original;
        });
});

function deleteHoliday(id, desc) {
    Swal.fire({
        title: 'Hapus Hari Libur?',
        text: 'Yakin hapus "' + desc + '"? Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('id', id);
        apiPost(KAL.holidayDelete, fd).then(res => {
            if (!res.status) {
                Swal.fire('Gagal', res.message || 'Gagal menghapus', 'error');
                return;
            }
            Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message, timer: 1300, showConfirmButton: false })
                .then(() => window.location.reload());
        }).catch(() => Swal.fire('Error', 'Kesalahan koneksi server', 'error'));
    });
}

function syncHolidays() {
    const year = document.getElementById('syncYearSelect').value || new Date().getFullYear();
    Swal.fire({
        title: 'Sinkronisasi Libur Nasional ' + year,
        html: 'Mengambil data hari libur resmi pemerintah Indonesia tahun <strong>' + year + '</strong> dari API publik.<br><small class="text-slate-400">Membutuhkan koneksi internet aktif di server.</small>',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Mulai Sinkron',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const fd = new FormData();
            fd.append('year', year);
            return apiPost(KAL.holidaySync, fd)
                .catch(() => ({ status: false, message: 'Tidak dapat terhubung ke server' }));
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (!result.isConfirmed || !result.value) return;
        if (result.value.status) {
            Swal.fire({ icon: 'success', title: 'Sinkronisasi Berhasil!', text: result.value.message })
                .then(() => window.location.reload());
        } else {
            Swal.fire('Gagal', result.value.message || 'Gagal sinkronisasi', 'error');
        }
    });
}

/* ── Filter tabel libur ────────────────────────── */
function filterHolidayTable() {
    const keyword = (document.getElementById('filterHolidaySearch').value || '').toLowerCase().trim();
    const year = document.getElementById('filterYear').value;
    const month = document.getElementById('filterMonth').value;
    let visible = 0;

    document.querySelectorAll('.holiday-row').forEach(row => {
        const ok = (!keyword || row.dataset.desc.includes(keyword))
            && (!year || row.dataset.year === year)
            && (!month || row.dataset.month === month);
        row.style.display = ok ? '' : 'none';
        if (ok) {
            visible++;
            const num = row.querySelector('.row-num');
            if (num) num.textContent = visible;
        }
    });

    setText('tableCounterBadge', visible + ' Data');

    const noRes = document.getElementById('noFilterResult');
    const totalRows = document.querySelectorAll('.holiday-row').length;
    if (noRes) noRes.classList.toggle('hidden', visible > 0 || totalRows === 0);
}

function resetHolidayFilter() {
    document.getElementById('filterHolidaySearch').value = '';
    const yearSelect = document.getElementById('filterYear');
    yearSelect.value = yearSelect.dataset.defaultYear || '';
    document.getElementById('filterMonth').value = '';
    filterHolidayTable();
}

/* ── Delegated bindings ────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    switchTab(localStorage.getItem('kalenderTab') || 'kalender');

    initCalendar();

    document.getElementById('kalTabs').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-tab]');
        if (btn) switchTab(btn.dataset.tab);
    });

    document.querySelectorAll('[data-goto-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () { switchTab(btn.dataset.gotoTab); });
    });

    document.getElementById('btnTambahLibur').addEventListener('click', function () {
        switchTab('libur');
        document.getElementById('holidayDate').focus();
    });

    document.querySelectorAll('.holiday-mode-radio').forEach(function (radio) {
        radio.addEventListener('change', function () { toggleHolidayMode(radio.value); });
    });
    document.querySelectorAll('.preset-libur').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('holidayDesc').value = btn.dataset.text;
        });
    });

    document.getElementById('btnResetHoliday').addEventListener('click', resetHolidayForm);
    document.getElementById('btnCancelEdit').addEventListener('click', resetHolidayForm);
    document.getElementById('filterHolidaySearch').addEventListener('input', filterHolidayTable);
    document.getElementById('filterYear').addEventListener('change', filterHolidayTable);
    document.getElementById('filterMonth').addEventListener('change', filterHolidayTable);
    document.getElementById('btnResetHolidayFilter').addEventListener('click', resetHolidayFilter);
    document.getElementById('btnResetHolidayFilter2').addEventListener('click', resetHolidayFilter);
    document.querySelectorAll('.btn-sync-holiday').forEach(function (btn) {
        btn.addEventListener('click', syncHolidays);
    });

    // Baris tabel libur
    document.querySelectorAll('.btn-edit-holiday').forEach(function (btn) {
        btn.addEventListener('click', function () {
            editHoliday(btn.dataset.id, btn.dataset.date, btn.dataset.desc);
        });
    });
    document.querySelectorAll('.btn-del-holiday').forEach(function (btn) {
        btn.addEventListener('click', function () {
            deleteHoliday(btn.dataset.id, btn.dataset.desc);
        });
    });

    setHolidayToday();
    filterHolidayTable();
});

// Edit / hapus event — termasuk item agenda yang dirender ulang via JS
document.addEventListener('click', function (e) {
    const edit = e.target.closest('.edit-event');
    if (edit) {
        e.preventDefault();
        fetchEventAndOpen(edit.dataset.id);
        return;
    }

    const del = e.target.closest('.delete-event');
    if (del) {
        e.preventDefault();
        confirmDeleteEvent(del.dataset.id);
        return;
    }

    const holidayEdit = e.target.closest('.agenda-holiday-edit');
    if (holidayEdit) {
        e.preventDefault();
        editHoliday(holidayEdit.dataset.id, holidayEdit.dataset.date, holidayEdit.dataset.desc);
        return;
    }

    if (e.target.closest('.agenda-actions')) return;

    const item = e.target.closest('.agenda-item');
    if (!item) return;

    if (item.dataset.kind === 'event') {
        fetchEventAndOpen(item.dataset.id);
    } else {
        editHoliday(item.dataset.id, item.dataset.date, item.dataset.desc);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const item = e.target.closest('.agenda-item');
    if (!item) return;
    e.preventDefault();
    if (item.dataset.kind === 'event') {
        fetchEventAndOpen(item.dataset.id);
    } else {
        editHoliday(item.dataset.id, item.dataset.date, item.dataset.desc);
    }
});
</script>
<?= $this->endSection() ?>
