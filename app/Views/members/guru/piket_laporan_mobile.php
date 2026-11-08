<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Top App Bar */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Date Nav Chip */
    .date-nav-chip {
        user-select: none;
        transition: transform 0.1s ease;
    }
    .date-nav-chip:active {
        transform: scale(0.99);
    }

    /* Section head (selaras dashboard piket) */
    .section-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px; margin-bottom: 10px;
    }
    .section-head-title { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .section-head-icon {
        width: 26px; height: 26px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }
    .section-head h2 { font-size: 13px; font-weight: 800; color: #334155; margin: 0; }
    .section-action {
        font-size: 11px; font-weight: 800; color: #0d9488;
        border: none; background: transparent; cursor: pointer; white-space: nowrap;
    }
    @media (hover: hover) { .section-action:hover { color: #0f766e; } }

    /* Footer cetak/unduh: sticky, jadi ditahan di atas pill nav (bukan di 0) */
    #jd_footer {
        bottom: calc(88px + var(--safe-area-inset-bottom, 0px) * 0.5);
        z-index: 1500;
    }

    /* Anchor nav (sticky di bawah top bar) */
    .anchor-nav {
        position: sticky; top: 62px; z-index: 900;
        background: rgba(248,250,252,0.95); backdrop-filter: blur(8px);
        padding: 6px 0;
    }

    /* Class card */
    .report-class-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease;
    }
    .class-toggle-btn { cursor: pointer; }
    .class-toggle-btn:focus-visible { outline: 2px solid #0d9488; outline-offset: -2px; }
    .chev { transition: transform 0.2s ease; }
    .collapsed .chev { transform: rotate(-90deg); }

    /* Empty state (selaras dashboard) */
    .empty-box {
        text-align: center; padding: 28px 20px;
        background: #fff; border: 1px solid rgba(226,232,240,0.8);
        border-radius: 16px;
    }
    .empty-box-icon {
        width: 52px; height: 52px; border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 22px; margin-bottom: 10px;
    }

    /* Monitoring KBM — tile tautan ke Input Presensi terfilter */
    .mon-tile-link {
        display: block; text-align: center; padding: 10px 4px 9px;
        text-decoration: none; background: transparent;
        transition: background 0.15s ease;
    }
    .mon-tile-link:hover { background: #f8fafc; }
    .mon-tile-link:active { background: #eef2ff; }
    .mon-tile-link:focus-visible { outline: 2px solid #6366f1; outline-offset: -2px; }

    @media (prefers-reduced-motion: reduce) {
        .chev, .report-class-card, .date-nav-chip, .mon-tile-link { transition: none; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
    $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
    $nextDisabled = ($nextDate > date('Y-m-d'));
    $indoDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $dayName = $indoDays[(int)date('N', strtotime($tanggal))] ?? '';

    $totalSlot  = (int)($summary['total_slot']  ?? 0);
    $jurnalIsi  = (int)($summary['jurnal_isi']  ?? 0);
    $piketIsi   = (int)($summary['piket_isi']   ?? 0);
    $absenMasuk = (int)($summary['absen_masuk'] ?? 0);
    $kejadian   = (int)($summary['kejadian']    ?? 0);
    $mismatch   = (int)($summary['mismatch']    ?? 0);
    $duplikat   = (int)($summary['duplikat_jurnal'] ?? 0);

    $jurnalPct = $totalSlot > 0 ? round($jurnalIsi / $totalSlot * 100) : 0;
    $hasFilter = !empty($filter_kelas) || !empty($filter_mapel);

    $srKelas = $siswa_rekap['kelas'] ?? [];
    $srTot = $siswa_rekap['total'] ?? [];
    $srHadir = (int)($srTot['r_hadir'] ?? 0) + (int)($srTot['r_telat'] ?? 0);
    $srTotal = (int)($srTot['total'] ?? 0);
    $srPct = $srTotal > 0 ? round($srHadir / $srTotal * 100) : 0;

    $grTot = (int)($guru_rekap['total'] ?? 0);
    $grTepat = (int)($guru_rekap['tepat'] ?? 0);
    $grTelat = (int)($guru_rekap['telat'] ?? 0);
    $grBelum = (int)($guru_rekap['belum'] ?? 0);
    $grList = $guru_rekap['list'] ?? [];
    $grHadir = $grTepat + $grTelat;

    $blKelas = $belajar_rekap['kelas'] ?? [];
    $blPersen = (int)($belajar_rekap['persen'] ?? 0);
    $blTerisi = (int)($belajar_rekap['terisi'] ?? 0);
    $blTotalSlot = (int)($belajar_rekap['total_slot'] ?? 0);

    // Monitoring KBM — angka dari controller, ikut filter kelas/mapel.
    $mon = is_array($monitoring ?? null) ? $monitoring : [];
    $monTotal = (int)($mon['total_slot'] ?? 0);
    $monKosong = (int)($mon['jurnal_kosong'] ?? 0);
    $monTanpaScan = (int)($mon['tanpa_scan'] ?? 0);
    $monPengganti = (int)($mon['pengganti'] ?? 0);
    $monSiswaAbsen = (int)($mon['siswa_absen'] ?? 0);
    $monPerluNilai = (int)($mon['perlu_validasi'] ?? 0);
    $monIssueBase = site_url('guru/piket/input_laporan') . '?tanggal=' . esc($tanggal)
        . ((int)$filter_kelas > 0 ? '&kelas=' . (int)$filter_kelas : '');
?>

<!-- ── Top App Bar (ramping: 1 aksi primer) ───────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100">
    <div class="flex items-center gap-2.5 min-w-0">
        <a href="<?= site_url('guru/piket') ?>" aria-label="Kembali ke Dashboard Piket" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 flex-shrink-0" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div class="min-w-0">
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight truncate">Rekap Laporan Piket</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Kehadiran Guru • <?= esc($dayName) ?>, <?= date('d M Y', strtotime($tanggal)) ?></p>
        </div>
    </div>
    <a href="<?= site_url('guru/piket/laporan/pdf') ?>?tanggal=<?= esc($tanggal) ?><?= $filter_kelas ? '&kelas='.(int)$filter_kelas : '' ?><?= $filter_mapel ? '&mapel='.(int)$filter_mapel : '' ?>" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition-colors no-underline flex-shrink-0"
       title="Unduh PDF">
        <i class="bi bi-file-earmark-pdf-fill text-xs"></i> PDF
    </a>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-12 space-y-3.5 mt-2.5">

    <!-- ── Date Navigation Chip ─────────────────────────── -->
    <div class="date-nav-chip flex items-center justify-between bg-white border border-slate-200/80 rounded-2xl px-3 py-2 shadow-xs">
        <a href="?tanggal=<?= $prevDate ?><?= $filter_kelas ? '&kelas='.(int)$filter_kelas : '' ?><?= $filter_mapel ? '&mapel='.(int)$filter_mapel : '' ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Sebelumnya" aria-label="Hari sebelumnya">
            <i class="bi bi-chevron-left text-xs font-bold"></i>
        </a>

        <div class="flex items-center gap-2.5 cursor-pointer relative px-2 py-1 rounded-xl hover:bg-slate-50 transition-colors"
             onclick="document.getElementById('headerDatePicker').showPicker ? document.getElementById('headerDatePicker').showPicker() : document.getElementById('headerDatePicker').focus()">
            <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100/80">
                <i class="bi bi-calendar3 text-sm"></i>
            </div>
            <div class="text-center">
                <div class="font-bold text-xs text-slate-800 leading-tight">
                    <?= date('d M Y', strtotime($tanggal)) ?>
                </div>
                <div class="text-[10px] text-slate-500 font-semibold leading-tight">
                    <?= $dayName ?>
                </div>
            </div>
            <i class="bi bi-chevron-down text-[10px] text-slate-400"></i>
            <form method="get" id="dateForm" class="m-0 p-0">
                <?php if ($filter_kelas): ?><input type="hidden" name="kelas" value="<?= (int)$filter_kelas ?>"><?php endif; ?>
                <?php if ($filter_mapel): ?><input type="hidden" name="mapel" value="<?= (int)$filter_mapel ?>"><?php endif; ?>
                <input type="date" name="tanggal" value="<?= esc($tanggal) ?>" id="headerDatePicker" max="<?= date('Y-m-d') ?>"
                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" aria-label="Pilih tanggal"
                       onchange="document.getElementById('dateForm').submit()">
            </form>
        </div>

        <?php if ($nextDisabled): ?>
        <span class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 text-slate-300 border border-slate-200/70" title="Sudah hari ini">
            <i class="bi bi-chevron-right text-xs font-bold"></i>
        </span>
        <?php else: ?>
        <a href="?tanggal=<?= $nextDate ?><?= $filter_kelas ? '&kelas='.(int)$filter_kelas : '' ?><?= $filter_mapel ? '&mapel='.(int)$filter_mapel : '' ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Berikutnya" aria-label="Hari berikutnya">
            <i class="bi bi-chevron-right text-xs font-bold"></i>
        </a>
        <?php endif; ?>
    </div>

    <!-- ── Filter (naik ke atas, dekat tanggal) ─────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs space-y-2.5">
        <form method="get" id="filterToolbarForm" class="space-y-2.5 m-0">
            <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-2">
                <div class="space-y-1">
                    <label for="filterKelas" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kelas</label>
                    <select name="kelas" id="filterKelas" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold focus:bg-white focus:outline-none focus:border-teal-500"
                            onchange="document.getElementById('filterToolbarForm').submit()">
                        <option value="0">Semua Kelas</option>
                        <?php foreach (($kelas_list ?? []) as $kId => $kName): ?>
                            <option value="<?= $kId ?>" <?= ((int)$filter_kelas === (int)$kId) ? 'selected' : '' ?>>
                                Kelas <?= esc($kName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="filterMapel" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mapel</label>
                    <select name="mapel" id="filterMapel" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold focus:bg-white focus:outline-none focus:border-teal-500"
                            onchange="document.getElementById('filterToolbarForm').submit()">
                        <option value="0">Semua Mapel</option>
                        <?php foreach (($mapel_list ?? []) as $mId => $mName): ?>
                            <option value="<?= $mId ?>" <?= ((int)$filter_mapel === (int)$mId) ? 'selected' : '' ?>>
                                <?= esc($mName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="search" id="searchReportInput" placeholder="Cari guru / mapel / kelas..."
                           aria-label="Cari guru, mapel, atau kelas"
                           class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500">
                </div>
                <?php if ($hasFilter): ?>
                <a href="?tanggal=<?= esc($tanggal) ?>" class="text-[11px] font-bold text-rose-600 hover:underline whitespace-nowrap no-underline flex-shrink-0">Reset</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="flex items-center justify-between pt-1 border-t border-slate-100">
            <span class="text-[11px] text-slate-500 font-medium">
                <?= $totalSlot ?> jam KBM
                <?php if ($hasFilter): ?>
                <span class="inline-flex items-center gap-1 text-teal-600 font-bold"><i class="bi bi-funnel-fill text-[10px]"></i>Filter aktif</span>
                <?php endif; ?>
            </span>
            <button type="button" onclick="triggerAutofillReport()"
                    class="inline-flex items-center gap-1 font-bold text-teal-700 text-[11px] transition-colors border-none bg-transparent cursor-pointer">
                <i class="bi bi-magic"></i> Tarik Presensi
            </button>
        </div>
    </div>

    <!-- ── Ringkasan + progress ─────────────────────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs space-y-2.5">
        <div class="grid grid-cols-4 gap-2 text-center">
            <div class="bg-teal-50/70 border border-teal-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center">
                <span class="text-xl font-extrabold text-teal-700 leading-none"><?= $totalSlot ?></span>
                <span class="text-[9px] font-bold text-teal-600 uppercase tracking-tight mt-1.5">Total Jam</span>
            </div>
            <div class="bg-emerald-50/70 border border-emerald-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center">
                <span class="text-xl font-extrabold text-emerald-700 leading-none"><?= $jurnalPct ?>%</span>
                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-tight mt-1.5">Jurnal KBM</span>
            </div>
            <div class="bg-blue-50/70 border border-blue-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center">
                <span class="text-xl font-extrabold text-blue-700 leading-none"><?= $piketIsi ?></span>
                <span class="text-[9px] font-bold text-blue-600 uppercase tracking-tight mt-1.5">Piket Terisi</span>
            </div>
            <div class="bg-amber-50/70 border border-amber-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center">
                <span class="text-xl font-extrabold text-amber-700 leading-none"><?= $kejadian ?></span>
                <span class="text-[9px] font-bold text-amber-600 uppercase tracking-tight mt-1.5">Kejadian</span>
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mb-1">
                <span>Kelengkapan jurnal <?= $jurnalIsi ?>/<?= $totalSlot ?> slot</span>
                <span><?= $absenMasuk ?> guru check-in</span>
            </div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden" role="progressbar"
                 aria-valuenow="<?= $jurnalPct ?>" aria-valuemin="0" aria-valuemax="100"
                 aria-label="Kelengkapan jurnal <?= $jurnalPct ?> persen">
                <div class="h-full rounded-full <?= $jurnalPct >= 80 ? 'bg-emerald-500' : ($jurnalPct >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= $jurnalPct ?>%"></div>
            </div>
        </div>
        <!-- Aksi utama: kirim + input berdampingan -->
        <div class="grid grid-cols-2 gap-2">
            <a href="<?= site_url('guru/piket/input_laporan') ?>?tanggal=<?= esc($tanggal) ?>"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors no-underline">
                <i class="bi bi-pencil-square"></i> Input Presensi
            </a>
            <button type="button" id="btnKirimRekap"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors border-none cursor-pointer">
                <i class="bi bi-send-fill"></i> Kirim ke Grup
            </button>
        </div>
    </div>

    <!-- ── Monitoring KBM (deep link ke Input Presensi) ──── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="flex items-center justify-between px-3 py-2.5">
            <span class="text-[11px] font-extrabold text-slate-700 flex items-center gap-1.5">
                <i class="bi bi-activity text-indigo-500"></i> Monitoring KBM
            </span>
            <a href="<?= $monIssueBase ?>" class="text-[10px] font-bold text-indigo-600 no-underline flex items-center gap-0.5">
                Buka Input <i class="bi bi-chevron-right text-[9px]"></i>
            </a>
        </div>

        <?php if ($monTotal === 0): ?>
            <p class="text-xs text-slate-500 text-center px-3 py-3 m-0 border-t border-slate-100">Belum ada jadwal KBM pada tanggal atau filter ini.</p>
        <?php else: ?>
        <div class="grid grid-cols-5 divide-x divide-slate-100 border-t border-slate-100">
            <a href="<?= $monIssueBase ?>&issue=tanpa_jurnal" class="mon-tile-link"
               title="Slot jadwal yang belum memiliki jurnal mengajar">
                <span class="block text-base font-extrabold leading-none <?= $monKosong > 0 ? 'text-rose-600' : 'text-slate-300' ?>"><?= $monKosong ?></span>
                <span class="block text-[8px] font-bold uppercase tracking-tight mt-1 <?= $monKosong > 0 ? 'text-rose-500' : 'text-slate-400' ?>">Tanpa Jurnal</span>
            </a>
            <a href="<?= $monIssueBase ?>&issue=tanpa_scan" class="mon-tile-link"
               title="Slot berjurnal tetapi guru pelaksana belum scan masuk">
                <span class="block text-base font-extrabold leading-none <?= $monTanpaScan > 0 ? 'text-amber-600' : 'text-slate-300' ?>"><?= $monTanpaScan ?></span>
                <span class="block text-[8px] font-bold uppercase tracking-tight mt-1 <?= $monTanpaScan > 0 ? 'text-amber-500' : 'text-slate-400' ?>">Tanpa Scan</span>
            </a>
            <a href="<?= $monIssueBase ?>&issue=pengganti" class="mon-tile-link"
               title="Slot yang dijurnal oleh guru pengganti">
                <span class="block text-base font-extrabold leading-none <?= $monPengganti > 0 ? 'text-violet-700' : 'text-slate-300' ?>"><?= $monPengganti ?></span>
                <span class="block text-[8px] font-bold uppercase tracking-tight mt-1 <?= $monPengganti > 0 ? 'text-violet-500' : 'text-slate-400' ?>">Pengganti</span>
            </a>
            <a href="<?= $monIssueBase ?>&issue=absen_siswa" class="mon-tile-link"
               title="Slot yang mencatat siswa tidak hadir di jurnal">
                <span class="block text-base font-extrabold leading-none <?= $monSiswaAbsen > 0 ? 'text-sky-600' : 'text-slate-300' ?>"><?= $monSiswaAbsen ?></span>
                <span class="block text-[8px] font-bold uppercase tracking-tight mt-1 <?= $monSiswaAbsen > 0 ? 'text-sky-500' : 'text-slate-400' ?>">Siswa Absen</span>
            </a>
            <a href="<?= $monIssueBase ?>&issue=perlu_validasi" class="mon-tile-link"
               title="Jurnal yang sudah diisi tetapi belum dinilai guru piket / kepala sekolah">
                <span class="block text-base font-extrabold leading-none <?= $monPerluNilai > 0 ? 'text-emerald-600' : 'text-slate-300' ?>"><?= $monPerluNilai ?></span>
                <span class="block text-[8px] font-bold uppercase tracking-tight mt-1 <?= $monPerluNilai > 0 ? 'text-emerald-600' : 'text-slate-400' ?>">Perlu Nilai</span>
            </a>
        </div>
        <p class="px-3 py-2 border-t border-slate-100 text-[10px] text-slate-400 font-medium m-0 flex items-start gap-1.5 leading-tight">
            <i class="bi bi-info-circle mt-0.5"></i>
            <span>Ketuk angka untuk melihat slot bermasalah di Input Presensi.</span>
        </p>
        <?php endif; ?>
    </div>

    <?php if ($mismatch > 0 || $duplikat > 0): ?>
    <!-- ── Peringatan integritas data ────────────────────── -->
    <div class="space-y-2" id="integrityWarnings">
        <?php if ($mismatch > 0): ?>
        <a href="<?= site_url('guru/piket/input_laporan') ?>?tanggal=<?= esc($tanggal) ?>"
           class="flex items-center gap-2.5 bg-amber-50 border border-amber-200 rounded-2xl px-3.5 py-2.5 no-underline shadow-xs">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 flex-shrink-0"></i>
            <span class="text-[11px] font-bold text-amber-800 flex-1"><?= $mismatch ?> slot beda antara jurnal & catatan piket. Perbaiki di Input Presensi.</span>
            <i class="bi bi-chevron-right text-amber-500 text-xs flex-shrink-0"></i>
        </a>
        <?php endif; ?>
        <?php if ($duplikat > 0): ?>
        <div class="flex items-center gap-2.5 bg-rose-50 border border-rose-200 rounded-2xl px-3.5 py-2.5 shadow-xs">
            <i class="bi bi-files text-rose-500 flex-shrink-0"></i>
            <span class="text-[11px] font-bold text-rose-800 flex-1"><?= $duplikat ?> jurnal ganda di slot yang sama (yang terbaru dipakai).</span>
            <button type="button" onclick="document.getElementById('integrityWarnings').remove()" aria-label="Tutup peringatan"
                class="w-6 h-6 rounded-lg text-rose-400 hover:bg-rose-100 flex items-center justify-center border-none bg-transparent cursor-pointer flex-shrink-0">
                <i class="bi bi-x text-sm"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Navigasi seksi ───────────────────────────────── -->
    <div class="anchor-nav flex gap-1.5 overflow-x-auto no-scrollbar" aria-label="Navigasi seksi laporan">
        <?php if (!empty($divergensi ?? [])): ?>
        <a href="#seksi-selaras" class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-bold whitespace-nowrap no-underline">Selisih (<?= count($divergensi) ?>)</a>
        <?php endif; ?>
        <a href="#seksi-siswa" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-[11px] font-bold whitespace-nowrap no-underline hover:border-emerald-300">Siswa</a>
        <a href="#seksi-guru" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-[11px] font-bold whitespace-nowrap no-underline hover:border-blue-300">Guru</a>
        <a href="#seksi-belajar" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-[11px] font-bold whitespace-nowrap no-underline hover:border-indigo-300">Pembelajaran</a>
        <a href="#seksi-detail" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-[11px] font-bold whitespace-nowrap no-underline hover:border-teal-300">Detail Jam</a>
    </div>

    <?php if (!empty($divergensi ?? [])): ?>
    <!-- ── SEKSI: Selisih Piket vs Sistem (pindahan Monitoring) ── -->
    <div id="seksi-selaras" class="scroll-mt-32">
        <div class="section-head">
            <div class="section-head-title">
                <span class="section-head-icon bg-amber-50 text-amber-600"><i class="bi bi-exclamation-triangle-fill"></i></span>
                <h2>Selisih Piket vs Sistem</h2>
            </div>
            <span class="text-[10px] font-bold text-slate-500"><?= count($divergensi) ?> perlu dicek</span>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 shadow-xs space-y-2">
            <p class="text-[11px] text-amber-700 m-0">Catatan piket beda dengan presensi formal. Ketuk <b>Selaraskan</b>: S/I/A piket menjadi patokan (formal mengikuti), H/T mengikuti sistem.</p>
            <div class="space-y-1.5">
                <?php foreach (array_slice($divergensi, 0, 8) as $d): ?>
                    <div class="diver-row flex items-center gap-2 bg-white border border-amber-100 rounded-xl px-2.5 py-2"
                         data-search="<?= esc(strtolower(($d->nama ?? '') . ' ' . ($d->nama_kelas ?? ''))) ?>">
                        <a href="<?= site_url('guru/piket/absensi') ?>?tanggal=<?= esc($tanggal) ?>&kelas=<?= (int)($d->id_kelas ?? 0) ?>"
                           class="flex-1 min-w-0 no-underline">
                            <div class="font-bold text-xs text-slate-800 truncate"><?= esc($d->nama ?? '-') ?></div>
                            <div class="text-[10px] text-slate-500"><?= esc($d->nama_kelas ?? '-') ?> • <?= esc($d->ket ?? '') ?></div>
                        </a>
                        <button type="button" onclick="selaraskanDivergensi(this, <?= (int)($d->id_siswa ?? 0) ?>)"
                                class="flex-shrink-0 px-2.5 py-1.5 rounded-lg bg-amber-100 text-amber-800 text-[10px] font-extrabold border border-amber-200 active:scale-95 cursor-pointer">
                            Selaraskan
                        </button>
                    </div>
                <?php endforeach; ?>
                <?php if (count($divergensi) > 8): ?>
                    <div class="text-[11px] text-amber-700 font-semibold text-center">+<?= count($divergensi) - 8 ?> lainnya — filter per kelas di Absensi.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── SEKSI 1: Rekap Siswa per Kelas ───────────────── -->
    <div id="seksi-siswa" class="scroll-mt-32">
        <div class="section-head">
            <div class="section-head-title">
                <span class="section-head-icon bg-emerald-50 text-emerald-600"><i class="bi bi-mortarboard-fill"></i></span>
                <h2>Rekap Siswa per Kelas</h2>
            </div>
            <span class="text-[10px] font-bold text-slate-500">Hadir <?= $srHadir ?>/<?= $srTotal ?> (<?= $srPct ?>%)</span>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
            <?php if (empty($srKelas)): ?>
                <p class="text-xs text-slate-500 text-center p-4 m-0">Belum ada data siswa.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($srKelas as $rk):
                        $rkTotal = max(1, (int)$rk->total);
                        $rkHadir = (int)$rk->r_hadir + (int)$rk->r_telat;
                        $rkPct = round($rkHadir / $rkTotal * 100);
                    ?>
                        <a href="<?= site_url('guru/piket/absensi') ?>?tanggal=<?= esc($tanggal) ?>&kelas=<?= (int)$rk->id_kelas ?>"
                           class="siswa-row block px-4 py-2.5 no-underline hover:bg-slate-50 transition-colors"
                           data-search="<?= esc(strtolower($rk->nama_kelas ?? '')) ?>">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-extrabold text-xs text-slate-800"><?= esc($rk->nama_kelas) ?></span>
                                <span class="text-[10px] font-bold text-slate-500 flex-shrink-0"><?= $rkHadir ?>/<?= (int)$rk->total ?> hadir</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 mt-1.5 overflow-hidden">
                                <div class="h-full rounded-full <?= $rkPct >= 80 ? 'bg-emerald-500' : ($rkPct >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= min(100, $rkPct) ?>%"></div>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap text-[10px] font-extrabold">
                                <span class="px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">H <?= (int)$rk->r_hadir ?></span>
                                <span class="px-1.5 py-0.5 rounded-md bg-orange-50 text-orange-700 border border-orange-200">T <?= (int)$rk->r_telat ?></span>
                                <span class="px-1.5 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">S <?= (int)$rk->r_sakit ?></span>
                                <span class="px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200">I <?= (int)$rk->r_izin ?></span>
                                <span class="px-1.5 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200">A <?= (int)$rk->r_alpha ?></span>
                                <?php if ((int)$rk->r_tanpa_pulang > 0): ?>
                                    <span class="px-1.5 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200">Blm plg <?= (int)$rk->r_tanpa_pulang ?></span>
                                <?php endif; ?>
                                <?php if ((int)$rk->r_belum > 0): ?>
                                    <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-500 border border-slate-200">Blm <?= (int)$rk->r_belum ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── SEKSI 2: Rekap Kehadiran Guru ────────────────── -->
    <div id="seksi-guru" class="scroll-mt-32">
        <div class="section-head">
            <div class="section-head-title">
                <span class="section-head-icon bg-blue-50 text-blue-600"><i class="bi bi-people-fill"></i></span>
                <h2>Rekap Kehadiran Guru</h2>
            </div>
            <span class="text-[10px] font-bold text-slate-500"><?= $grHadir ?>/<?= $grTot ?> hadir</span>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
            <div class="grid grid-cols-3 gap-1.5 p-3 text-center">
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2">
                    <div class="text-base font-extrabold text-emerald-700 leading-none"><?= $grTepat ?></div>
                    <div class="text-[9px] font-bold text-emerald-600 uppercase mt-1">Tepat</div>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-2">
                    <div class="text-base font-extrabold text-amber-700 leading-none"><?= $grTelat ?></div>
                    <div class="text-[9px] font-bold text-amber-600 uppercase mt-1">Telat</div>
                </div>
                <div class="bg-rose-50 border border-rose-100 rounded-xl p-2">
                    <div class="text-base font-extrabold text-rose-700 leading-none"><?= $grBelum ?></div>
                    <div class="text-[9px] font-bold text-rose-600 uppercase mt-1">Belum</div>
                </div>
            </div>
            <?php if (!empty($grList)): ?>
                <div class="divide-y divide-slate-100 border-t border-slate-100" id="guruRekapList">
                    <?php foreach ($grList as $gi => $g):
                        $kat = $g->hadir_kat ?? 'Belum';
                        $badge = $kat === 'Tepat' ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : ($kat === 'Telat' ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : 'bg-rose-50 text-rose-700 border-rose-200');
                        $jmIn = trim((string)($g->jam_masuk ?? ''));
                        if (strpos($jmIn, ' ') !== false) $jmIn = trim(substr($jmIn, strrpos($jmIn, ' ') + 1));
                        $jmOut = trim((string)($g->jam_pulang ?? ''));
                        if (strpos($jmOut, ' ') !== false) $jmOut = trim(substr($jmOut, strrpos($jmOut, ' ') + 1));
                    ?>
                        <div class="guru-row px-4 py-2 flex items-center gap-2 <?= $gi >= 6 ? 'hidden guru-extra' : '' ?>"
                             data-search="<?= esc(strtolower($g->nama_guru ?? '')) ?>">
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-xs text-slate-900 truncate"><?= esc($g->nama_guru ?? '-') ?></div>
                                <div class="text-[10px] text-slate-400">
                                    <?php if ($jmIn !== '' && $jmIn !== '00:00:00'): ?>
                                        Masuk <?= esc(substr($jmIn, 0, 5)) ?><?= ($jmOut !== '' && $jmOut !== '00:00:00') ? ' • Pulang ' . esc(substr($jmOut, 0, 5)) : '' ?>
                                    <?php else: ?>
                                        Belum check-in
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full border <?= $badge ?> uppercase flex-shrink-0"><?= esc($kat) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($grList) > 6): ?>
                <button type="button" id="btnGuruMore"
                    class="w-full py-2.5 text-[11px] font-bold text-blue-600 hover:bg-blue-50 border-t border-slate-100 border-x-0 border-b-0 bg-transparent cursor-pointer transition-colors">
                    Tampilkan semua <?= count($grList) ?> guru <i class="bi bi-chevron-down text-[10px]"></i>
                </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── SEKSI 3: Rekap Pembelajaran ──────────────────── -->
    <div id="seksi-belajar" class="scroll-mt-32">
        <div class="section-head">
            <div class="section-head-title">
                <span class="section-head-icon bg-indigo-50 text-indigo-600"><i class="bi bi-journal-text"></i></span>
                <h2>Rekap Pembelajaran</h2>
            </div>
            <span class="text-[10px] font-bold text-slate-500"><?= $blTerisi ?>/<?= $blTotalSlot ?> slot (<?= $blPersen ?>%)</span>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
            <?php if (empty($blKelas)): ?>
                <p class="text-xs text-slate-500 text-center p-4 m-0">Tidak ada jadwal KBM tanggal ini.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($blKelas as $bk):
                        $pct = ((int)$bk->total_slot > 0) ? round(((int)$bk->jurnal_isi / (int)$bk->total_slot) * 100) : 0;
                        $materi = $bk->materi ?? [];
                    ?>
                        <div class="belajar-row px-4 py-2.5" data-search="<?= esc(strtolower($bk->nama_kelas ?? '')) ?>">
                            <button type="button" onclick="this.parentElement.classList.toggle('collapsed');this.querySelector('.chev').classList.toggle('bi-chevron-down');this.querySelector('.chev').classList.toggle('bi-chevron-right');this.nextElementSibling.nextElementSibling.classList.toggle('hidden')"
                                class="w-full flex items-center justify-between gap-2 border-none bg-transparent p-0 cursor-pointer" aria-expanded="true">
                                <span class="font-extrabold text-xs text-slate-800"><?= esc($bk->nama_kelas) ?></span>
                                <span class="flex items-center gap-1.5 flex-shrink-0">
                                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full <?= $pct >= 80 ? 'bg-emerald-100 text-emerald-700' : ($pct >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') ?>">
                                        <?= (int)$bk->jurnal_isi ?>/<?= (int)$bk->total_slot ?>
                                    </span>
                                    <i class="chev bi bi-chevron-down text-slate-400 text-[10px]"></i>
                                </span>
                            </button>
                            <div class="h-1.5 rounded-full bg-slate-100 mt-1.5 overflow-hidden">
                                <div class="h-full rounded-full <?= $pct >= 80 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= min(100, $pct) ?>%"></div>
                            </div>
                            <?php if (!empty($materi)): ?>
                                <div class="materi-box mt-1.5 space-y-1">
                                    <?php foreach (array_slice($materi, 0, 2) as $mt): ?>
                                        <div class="text-[11px] text-slate-600 bg-slate-50 border border-slate-100 rounded-lg px-2 py-1 leading-snug"><?= esc($mt) ?></div>
                                    <?php endforeach; ?>
                                    <?php if (count($materi) > 2): ?>
                                        <div class="materi-extra hidden space-y-1">
                                            <?php foreach (array_slice($materi, 2, 4) as $mt): ?>
                                                <div class="text-[11px] text-slate-600 bg-slate-50 border border-slate-100 rounded-lg px-2 py-1 leading-snug"><?= esc($mt) ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" onclick="this.previousElementSibling.classList.toggle('hidden');this.textContent=this.previousElementSibling.classList.contains('hidden')?'+<?= count($materi) - 2 ?> materi lainnya':'Ringkas'"
                                            class="text-[10px] font-bold text-indigo-600 border-none bg-transparent p-0 cursor-pointer">+<?= count($materi) - 2 ?> materi lainnya</button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-[11px] text-slate-400 mt-1">Belum ada jurnal terisi.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── SEKSI 4: Detail Jam per Kelas ────────────────── -->
    <div id="seksi-detail" class="scroll-mt-32">
        <div class="section-head">
            <div class="section-head-title">
                <span class="section-head-icon bg-teal-50 text-teal-600"><i class="bi bi-list-check"></i></span>
                <h2>Detail Jam per Kelas</h2>
            </div>
            <?php if (!empty($report)): ?>
            <button type="button" id="btnCollapseAll" class="section-action">Ciutkan semua</button>
            <?php endif; ?>
        </div>
        <?php if (!empty($report)): ?>
        <p class="text-[10px] text-slate-400 m-0 mb-2 flex items-center gap-1"><i class="bi bi-hand-index"></i> Ketuk slot yang sudah berjurnal untuk melihat detail jurnal.</p>
        <?php endif; ?>
        <div id="reportContainer" class="space-y-3">
            <?php if (empty($report)): ?>
                <div class="empty-box">
                    <div class="empty-box-icon bg-teal-50 text-teal-600"><i class="bi bi-calendar-x"></i></div>
                    <p class="font-bold text-slate-600 text-[13px] m-0">Tidak Ada Jadwal</p>
                    <p class="text-xs text-slate-400 mt-1 m-0">Tidak ditemukan jadwal KBM pada tanggal atau filter terpilih.</p>
                </div>
            <?php else: ?>
                <?php foreach ($report as $idKls => $kls):
                    $slots = $kls['slots'] ?? [];
                    $classTotal = count($slots);
                    $classFilled = 0;
                    foreach ($slots as $s) {
                        if (!empty($s['manual'])) $classFilled++;
                    }
                    $classPct = $classTotal > 0 ? round(($classFilled / $classTotal) * 100) : 0;
                ?>
                    <div class="report-class-card bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden"
                         data-kelas-id="<?= $idKls ?>" data-search="<?= esc(strtolower($kls['nama_kelas'] ?? '')) ?>">

                        <!-- Class Header (toggle) -->
                        <button type="button" onclick="toggleClassCard(this)"
                            class="class-toggle-btn w-full bg-slate-50/80 px-4 py-2.5 border-x-0 border-t-0 border-b border-slate-100 flex items-center justify-between gap-2 bg-transparent cursor-pointer" aria-expanded="true">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="w-6 h-6 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs flex-shrink-0">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </span>
                                <span class="font-extrabold text-xs text-slate-800 truncate">
                                    <?= esc($kls['nama_kelas']) ?>
                                </span>
                            </span>
                            <span class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-[10px] font-bold text-slate-500">
                                    <?= $classFilled ?>/<?= $classTotal ?>
                                </span>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full <?= $classPct >= 80 ? 'bg-emerald-100 text-emerald-700' : ($classPct >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') ?>">
                                    <?= $classPct ?>%
                                </span>
                                <i class="chev bi bi-chevron-down text-slate-400 text-[10px]"></i>
                            </span>
                        </button>

                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 h-1 class-progress">
                            <div class="h-1 <?= $classPct >= 80 ? 'bg-emerald-500' : ($classPct >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= $classPct ?>%"></div>
                        </div>

                        <!-- Slot List -->
                        <div class="slot-list divide-y divide-slate-100">
                            <?php foreach ($slots as $s):
                                $m = $s['manual'] ?? null;
                                $st = $m ? ($m->status ?? '') : '';
                                $ket = $m ? ($m->keterangan ?? '') : '';

                                if ($st === 'H') {
                                    $badgeCls = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $badgeLbl = 'Hadir';
                                } elseif ($st === 'S') {
                                    $badgeCls = 'bg-blue-50 text-blue-700 border-blue-200';
                                    $badgeLbl = 'Sakit';
                                } elseif ($st === 'I') {
                                    $badgeCls = 'bg-amber-50 text-amber-700 border-amber-200';
                                    $badgeLbl = 'Izin';
                                } elseif ($st === 'A') {
                                    $badgeCls = 'bg-rose-50 text-rose-700 border-rose-200';
                                    $badgeLbl = 'Alpa';
                                } else {
                                    $badgeCls = 'bg-slate-100 text-slate-500 border-slate-200';
                                    $badgeLbl = 'Belum Cek';
                                }

                                $searchContent = strtolower(esc(($s['nama_mapel'] ?? '') . ' ' . ($s['nama_guru'] ?? '') . ' ' . ($s['jurnal_guru_nama'] ?? '') . ' ' . $ket));
                                $jid = (int)($s['jurnal_id'] ?? 0);

                                $isPengganti = !empty($s['pengganti']);
                                $scanJam = $isPengganti
                                    ? (string)($s['pengganti_scan_jam'] ?? '')
                                    : (string)($s['scan_jam'] ?? '');
                                $absenN = (int)($s['siswa_absen_jumlah'] ?? 0);
                                $absenNama = $s['siswa_absen_nama'] ?? [];
                                $penggantiNama = trim((string)($s['jurnal_guru_nama'] ?? ''));
                            ?>
                                <div class="report-slot-item p-3 space-y-1.5 <?= $jid > 0 ? 'cursor-pointer hover:bg-slate-50' : '' ?>" data-search="<?= $searchContent ?>"
                                    <?= $jid > 0 ? 'onclick="openJurnalDetail(' . $jid . ')" role="button" tabindex="0" title="Lihat detail jurnal"' : '' ?>>
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-start gap-2.5 flex-1 min-w-0">
                                            <!-- Jam Badge -->
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-extrabold text-[10px] border border-slate-200 flex-shrink-0 mt-0.5">
                                                Jam <?= esc($s['jam_ke']) ?>
                                            </span>

                                            <div class="flex-1 min-w-0">
                                                <div class="font-extrabold text-xs text-slate-900 truncate leading-tight">
                                                    <?= esc($s['nama_mapel']) ?>
                                                </div>
                                                <div class="text-[11px] font-medium text-slate-500 mt-0.5 flex items-center gap-1.5 truncate">
                                                    <i class="bi bi-person-fill text-slate-400"></i>
                                                    <?php if (($s['nama_guru'] ?? '') === 'Belum Diset'): ?>
                                                        <span class="text-rose-600 font-bold bg-rose-50 px-1.5 py-0.5 rounded">Belum Diset</span>
                                                    <?php else: ?>
                                                        <span class="text-slate-600 truncate"><?= esc($s['nama_guru']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Badge -->
                                        <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full border <?= $badgeCls ?> uppercase tracking-wider flex-shrink-0">
                                            <?= $badgeLbl ?>
                                        </span>
                                    </div>

                                    <!-- Monitoring chips -->
                                    <?php if ($jid > 0): ?>
                                    <div class="flex flex-wrap items-center gap-1 ml-11">
                                        <?php if ($isPengganti): ?>
                                            <span class="inline-flex items-center gap-1 max-w-full text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-violet-50 text-violet-700 border border-violet-200" title="Jurnal ditulis guru pengganti">
                                                <i class="bi bi-person-plus-fill flex-shrink-0"></i>
                                                <span class="truncate"><?= esc($penggantiNama !== '' ? $penggantiNama : 'Pengganti') ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($scanJam !== ''): ?>
                                            <span class="inline-flex items-center gap-1 text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="bi bi-person-check-fill"></i> Scan <?= esc($scanJam) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200" title="Guru pelaksana belum tercatat scan masuk">
                                                <i class="bi bi-person-x"></i> Belum Scan
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($absenN > 0): ?>
                                            <span class="inline-flex items-center gap-1 text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200"
                                                  title="<?= esc($absenNama ? 'Tidak hadir: ' . implode(', ', array_slice($absenNama, 0, 20)) : 'Nama siswa tidak ditemukan') ?>">
                                                <i class="bi bi-person-x"></i> <?= $absenN ?> siswa absen
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($ket)): ?>
                                        <div class="text-[11px] text-slate-600 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1.5 ml-11">
                                            <span class="font-bold text-slate-700">Catatan:</span> <?= esc($ket) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p id="searchEmptyHint" class="hidden text-xs text-slate-400 text-center py-6 m-0">Tidak ada hasil yang cocok dengan pencarian.</p>
    </div>

</div>

<!-- ═══════════ MODAL DETAIL JURNAL (pindahan Monitoring) ═══════════ -->
<div class="tw-modal" id="modalJurnalDetail" aria-hidden="true">
    <div class="tw-modal-box">
        <div class="sticky top-0 bg-white z-10 px-5 pt-3 pb-3 border-b border-slate-100">
            <div class="w-10 h-1.5 bg-slate-300 rounded-full mx-auto mb-3"></div>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Detail Jurnal KBM</h2>
                    <p class="text-xs text-slate-500 m-0 mt-0.5 font-medium" id="jd_meta">Memuat data...</p>
                </div>
                <button type="button" data-tw-dismiss="modal" aria-label="Tutup detail jurnal" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors border-none cursor-pointer">
                    <i class="bi bi-x-lg text-xs font-bold"></i>
                </button>
            </div>
        </div>
        <div class="p-5 space-y-4">
            <div id="jd_loading" class="py-12 flex flex-col items-center justify-center gap-2">
                <div class="w-8 h-8 border-[3px] border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-semibold text-slate-500">Memuat detail jurnal...</span>
            </div>
            <div id="jd_detail" class="hidden space-y-4">
                <div class="flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
                        <i class="bi bi-person-fill text-blue-600"></i> <span id="jd_guru">-</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 text-xs font-bold border border-indigo-100">
                        <i class="bi bi-book-fill text-indigo-600"></i> <span id="jd_mapel">-</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                        <i class="bi bi-people-fill text-emerald-600"></i> <span id="jd_kelas">-</span>
                    </span>
                </div>
                <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-100 space-y-2.5">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Waktu Mengajar</div>
                        <div class="text-xs font-bold text-slate-800 mt-0.5" id="jd_waktu">-</div>
                    </div>
                    <div class="border-t border-slate-200/60 pt-2">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Topik / Materi Pokok</div>
                        <div class="text-xs font-semibold text-slate-800 mt-0.5 leading-relaxed" id="jd_materi">-</div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-2.5">
                        <div class="text-lg font-extrabold text-slate-800 leading-none" id="jd_total">0</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase mt-1">Total</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-2.5">
                        <div class="text-lg font-extrabold text-emerald-700 leading-none" id="jd_hadir">0</div>
                        <div class="text-[10px] font-bold text-emerald-600 uppercase mt-1">Hadir</div>
                    </div>
                    <div class="bg-rose-50 border border-rose-200/80 rounded-2xl p-2.5">
                        <div class="text-lg font-extrabold text-rose-700 leading-none" id="jd_absen">0</div>
                        <div class="text-[10px] font-bold text-rose-600 uppercase mt-1">Tidak Hadir</div>
                    </div>
                </div>
                <div id="jd_absen_wrap" class="hidden space-y-1.5">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Siswa Tidak Hadir</div>
                    <div id="jd_absen_list" class="flex flex-wrap gap-1.5"></div>
                </div>
                <details class="group bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <summary class="flex items-center justify-between p-3.5 cursor-pointer font-bold text-xs text-slate-800 hover:bg-slate-50 transition-colors list-none">
                        <span class="flex items-center gap-2"><i class="bi bi-list-check text-blue-600 text-sm"></i> Rincian Pembelajaran</span>
                        <i class="bi bi-chevron-down text-slate-400 text-xs transition-transform group-open:rotate-180"></i>
                    </summary>
                    <div class="p-3.5 pt-0 border-t border-slate-100 space-y-3 text-xs">
                        <div class="mt-2.5"><span class="text-[10px] font-bold text-slate-400 uppercase block">Tujuan Pembelajaran</span><span class="text-slate-800 font-medium mt-0.5 block" id="jd_tp">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Model & Media</span><span class="text-slate-800 font-medium mt-0.5 block" id="jd_model_media">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Kegiatan Belajar</span><span class="text-slate-800 font-medium mt-0.5 block whitespace-pre-line" id="jd_kegiatan">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Asesmen</span><span class="text-slate-800 font-medium mt-0.5 block" id="jd_asesmen">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Refleksi</span><span class="text-slate-800 font-medium mt-0.5 block" id="jd_refleksi">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Tindak Lanjut</span><span class="text-slate-800 font-medium mt-0.5 block" id="jd_tindak">-</span></div>
                    </div>
                </details>
                <details class="group bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <summary class="flex items-center justify-between p-3.5 cursor-pointer font-bold text-xs text-slate-800 hover:bg-slate-50 transition-colors list-none">
                        <span class="flex items-center gap-2"><i class="bi bi-sticky text-amber-500 text-sm"></i> Catatan Khusus</span>
                        <i class="bi bi-chevron-down text-slate-400 text-xs transition-transform group-open:rotate-180"></i>
                    </summary>
                    <div class="p-3.5 pt-0 border-t border-slate-100 text-xs">
                        <div class="mt-2.5 text-slate-700 whitespace-pre-line" id="jd_catatan">-</div>
                    </div>
                </details>
            </div>
        </div>
        <div id="jd_footer" class="hidden sticky bottom-0 bg-white px-5 py-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <a id="jd_print" href="#" target="_blank" rel="noopener" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition-colors no-underline">
                <i class="bi bi-printer text-xs"></i> Cetak
            </a>
            <a id="jd_pdf" href="#" target="_blank" rel="noopener" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold flex items-center gap-1.5 transition-colors no-underline shadow-xs">
                <i class="bi bi-file-earmark-pdf text-xs"></i> Unduh PDF
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const BASE = '<?= base_url() ?>';

    // ── Pencarian global: saring siswa + guru + belajar + slot ──
    const searchInput = document.getElementById('searchReportInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            let anySlotVisible = false;

            // Slot detail per kelas
            document.querySelectorAll('.report-class-card').forEach(card => {
                let hasMatch = false;
                card.querySelectorAll('.report-slot-item').forEach(slot => {
                    const text = slot.getAttribute('data-search') || '';
                    const match = !q || text.includes(q);
                    slot.style.display = match ? '' : 'none';
                    if (match) hasMatch = true;
                });
                // Kartu cocok bila nama kelasnya cocok ATAU ada slot cocok
                const cardName = (card.getAttribute('data-search') || '');
                const showCard = !q || hasMatch || cardName.includes(q);
                card.style.display = showCard ? '' : 'none';
                if (showCard && hasMatch) anySlotVisible = true;
            });

            // Baris siswa & guru & belajar & selisih
            document.querySelectorAll('.siswa-row, .guru-row, .belajar-row, .diver-row').forEach(row => {
                const text = row.getAttribute('data-search') || '';
                const match = !q || text.includes(q);
                if (row.classList.contains('guru-extra')) {
                    // Saat mencari: tampilkan yang cocok walau daftar sedang diciutkan.
                    // Saat search dikosongkan: kembalikan ke status ciut/bentang.
                    if (q) {
                        row.classList.toggle('hidden', !match);
                        row.style.display = match ? '' : 'none';
                    } else {
                        row.classList.toggle('hidden', !guruExpanded);
                        row.style.display = guruExpanded ? '' : 'none';
                    }
                    return;
                }
                row.style.display = match ? '' : 'none';
            });

            const hint = document.getElementById('searchEmptyHint');
            if (hint) {
                const anyCard = Array.from(document.querySelectorAll('.report-class-card'))
                    .some(c => c.style.display !== 'none');
                hint.classList.toggle('hidden', !q || anyCard);
            }
        });
    }

    // ── Collapse per kartu kelas ────────────────────────
    window.toggleClassCard = function(btn) {
        const card = btn.closest('.report-class-card');
        if (!card) return;
        const list = card.querySelector('.slot-list');
        const prog = card.querySelector('.class-progress');
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        if (list) list.classList.toggle('hidden', expanded);
        if (prog) prog.classList.toggle('hidden', expanded);
        card.classList.toggle('collapsed', expanded);
    };

    // ── Ciutkan / bentangkan semua ──────────────────────
    const btnAll = document.getElementById('btnCollapseAll');
    if (btnAll) {
        btnAll.addEventListener('click', function() {
            const cards = document.querySelectorAll('.report-class-card');
            const anyOpen = Array.from(cards).some(c => !c.querySelector('.slot-list')?.classList.contains('hidden'));
            cards.forEach(card => {
                const btn = card.querySelector('.class-toggle-btn');
                const list = card.querySelector('.slot-list');
                const prog = card.querySelector('.class-progress');
                if (list) list.classList.toggle('hidden', anyOpen);
                if (prog) prog.classList.toggle('hidden', anyOpen);
                card.classList.toggle('collapsed', anyOpen);
                if (btn) btn.setAttribute('aria-expanded', anyOpen ? 'false' : 'true');
            });
            btnAll.textContent = anyOpen ? 'Bentangkan semua' : 'Ciutkan semua';
        });
    }

    // ── Guru: tampil 6 dulu ─────────────────────────────
    let guruExpanded = false;
    const btnGuru = document.getElementById('btnGuruMore');
    if (btnGuru) {
        btnGuru.addEventListener('click', function() {
            guruExpanded = !guruExpanded;
            const q = (searchInput?.value || '').toLowerCase().trim();
            document.querySelectorAll('.guru-extra').forEach(el => {
                el.classList.toggle('hidden', !guruExpanded);
                // Hormati filter pencarian yang sedang aktif
                if (!q) el.style.display = guruExpanded ? '' : 'none';
                else {
                    const match = ((el.getAttribute('data-search') || '').includes(q));
                    el.style.display = (guruExpanded && match) ? '' : 'none';
                }
            });
            btnGuru.innerHTML = guruExpanded
                ? 'Ringkas <i class="bi bi-chevron-up text-[10px]"></i>'
                : 'Tampilkan semua <?= count($grList) ?> guru <i class="bi bi-chevron-down text-[10px]"></i>';
        });
    }

    // ── Akses keyboard untuk slot jurnal ──────────────
    document.getElementById('reportContainer')?.addEventListener('keydown', function(e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.classList?.contains('report-slot-item') && e.target.hasAttribute('onclick')) {
            e.preventDefault();
            e.target.click();
        }
    });

    // ── Selaraskan selisih piket vs sistem (pindahan Monitoring) ──
    window.selaraskanDivergensi = async function(btn, idSiswa) {
        btn.disabled = true;
        const orig = btn.textContent;
        btn.textContent = '…';
        const fd = new FormData();
        fd.append('tanggal', '<?= esc($tanggal) ?>');
        fd.append('id_siswa', idSiswa);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        try {
            const res = await fetch(BASE + 'guru/piket/selaras_divergensi', {
                method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json().catch(() => null);
            if (res.ok && json && json.status) {
                window.location.reload();
            } else {
                btn.disabled = false;
                btn.textContent = orig;
                alert((json && json.message) || 'Gagal menyelaraskan');
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = orig;
            alert('Gagal menghubungkan ke server');
        }
    };

    // ── Modal detail jurnal (pindahan Monitoring) ───────
    function escHtml(s) {
        const div = document.createElement('div');
        div.textContent = s ?? '';
        return div.innerHTML;
    }
    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '-';
    }
    window.openJurnalDetail = async function(id) {
        if (!id) return;
        const loadingEl = document.getElementById('jd_loading');
        const detailEl = document.getElementById('jd_detail');
        const footerEl = document.getElementById('jd_footer');
        const metaEl = document.getElementById('jd_meta');
        const absenWrap = document.getElementById('jd_absen_wrap');
        const absenList = document.getElementById('jd_absen_list');

        loadingEl.classList.remove('hidden');
        detailEl.classList.add('hidden');
        footerEl.classList.add('hidden');
        metaEl.textContent = 'Memuat detail...';
        absenWrap.classList.add('hidden');
        absenList.innerHTML = '';

        document.getElementById('jd_pdf')?.setAttribute('href', BASE + 'guru/piket/laporan/jurnal/' + id + '/pdf?download=1');
        document.getElementById('jd_print')?.setAttribute('href', BASE + 'guru/piket/laporan/jurnal/' + id + '/pdf');

        twModalShow('#modalJurnalDetail');

        try {
            const res = await fetch(BASE + 'guru/piket/laporan/jurnal/' + id, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json || !json.data) throw new Error('Format respon data tidak valid');

            const d = json.data;
            setText('jd_guru', d.nama_guru);
            setText('jd_mapel', d.nama_mapel);
            setText('jd_kelas', d.nama_kelas);

            let waktu = d.jam_ke || '-';
            if (d.jam_mulai && d.jam_selesai) {
                waktu = 'Jam ke-' + (d.jam_ke || '-') + ' (' + String(d.jam_mulai).slice(0,5) + ' – ' + String(d.jam_selesai).slice(0,5) + ')';
            }
            setText('jd_waktu', waktu);
            setText('jd_materi', d.materi_pokok);
            setText('jd_total', String(d.jumlah_siswa_total ?? 0));
            setText('jd_hadir', String(d.jumlah_hadir ?? 0));
            setText('jd_absen', String(d.jumlah_tidak_hadir ?? 0));
            metaEl.textContent = d.tanggal || '';

            setText('jd_tp', d.tujuan_pembelajaran);
            const modelMedia = [d.model_belajar, d.media_alat].filter(Boolean).join(' • ');
            setText('jd_model_media', modelMedia || '-');
            setText('jd_kegiatan', d.kegiatan_belajar);
            setText('jd_asesmen', d.jenis_asesmen);
            setText('jd_refleksi', d.refleksi);
            setText('jd_tindak', d.tindak_lanjut);
            setText('jd_catatan', d.catatan_khusus);

            if (Array.isArray(d.siswa_tidak_hadir) && d.siswa_tidak_hadir.length) {
                absenWrap.classList.remove('hidden');
                absenList.innerHTML = d.siswa_tidak_hadir
                    .map(name => '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700">' + escHtml(name) + '</span>')
                    .join('');
            }

            loadingEl.classList.add('hidden');
            detailEl.classList.remove('hidden');
            footerEl.classList.remove('hidden');
        } catch (err) {
            metaEl.textContent = 'Gagal memuat';
            loadingEl.innerHTML = '<div class="text-center py-6"><i class="bi bi-exclamation-circle text-3xl text-rose-500"></i><div class="mt-2 font-bold text-xs text-rose-700">Gagal Memuat Data</div><div class="text-[11px] text-slate-400 mt-1">' + escHtml(err.message) + '</div></div>';
        }
    };

    // ── Kirim Rekap ke Grup ─────────────────────────────
    const btnKirim = document.getElementById('btnKirimRekap');
    if (btnKirim) {
        btnKirim.addEventListener('click', async function() {
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Kirim Rekap ke Grup?',
                    text: 'Rekap siswa per kelas + kehadiran guru tanggal <?= esc($tanggal) ?> akan dikirim ke grup WA & Telegram.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#059669',
                    confirmButtonText: 'Ya, Kirim',
                    cancelButtonText: 'Batal'
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm('Kirim rekap ke grup?')) return;
            }

            btnKirim.disabled = true;
            const origHtml = btnKirim.innerHTML;
            btnKirim.innerHTML = '<span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Mengirim...';

            const formData = new FormData();
            formData.append('tanggal', '<?= esc($tanggal) ?>');
            const csrfInput = document.querySelector('#filterToolbarForm input[name="<?= csrf_token() ?>"]');
            if (csrfInput) formData.append(csrfInput.name, csrfInput.value);

            try {
                const res = await fetch(BASE + 'guru/piket/kirim_rekap', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                let json = null;
                try { json = await res.json(); } catch (e) { json = null; }
                if (res.ok && json && json.status) {
                    if (window.Swal) Swal.fire({ title: 'Terkirim!', text: json.message || 'Rekap terkirim ke grup', icon: 'success', timer: 1800, showConfirmButton: false });
                    else alert(json.message || 'Terkirim');
                } else {
                    const msg = (json && json.message) || 'Gagal mengirim (HTTP ' + res.status + ')';
                    if (window.Swal) Swal.fire({ title: 'Gagal', text: msg, icon: 'error' });
                    else alert(msg);
                }
            } catch (err) {
                alert('Gagal menghubungkan ke server');
            } finally {
                btnKirim.disabled = false;
                btnKirim.innerHTML = origHtml;
            }
        });
    }

    // ── Autofill Report ───────────────────────────────────
    window.triggerAutofillReport = async function() {
        if (window.Swal) {
            const confirmRes = await Swal.fire({
                title: 'Tarik Presensi Otomatis?',
                text: 'Data guru yang sudah mengisi Jurnal KBM atau Check-in Presensi akan disinkronkan ke laporan hari ini.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0f766e',
                confirmButtonText: 'Ya, Sinkronkan',
                cancelButtonText: 'Batal'
            });
            if (!confirmRes.isConfirmed) return;
        }

        const formData = new FormData();
        formData.append('tanggal', '<?= esc($tanggal) ?>');
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const res = await fetch(BASE + 'guru/piket/laporan/autofill', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                if (window.Swal) {
                    await Swal.fire({
                        title: 'Sinkronisasi Selesai',
                        text: json.message || 'Presensi guru berhasil disinkronkan',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
                window.location.reload();
            } else {
                alert(json.message || 'Gagal sinkronisasi');
            }
        } catch (err) {
            alert('Gagal menghubungkan ke server');
        }
    };

});
</script>
<?= $this->endSection() ?>
