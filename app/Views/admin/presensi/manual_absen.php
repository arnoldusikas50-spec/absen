<?= $this->extend('layouts/main') ?>
<?php
// Default jam datang/pulang diambil dari jadwal kerja hari ini, bukan angka
// tetap 07:30/15:00: guru shift sore yang diabsen manual dengan default ini
// langsung tercatat "telat" ratusan menit.
$jadwalHariIni = ($jadwalKerja ?? [])[(int) date('N')] ?? null;
$jamHarian = static function ($nilai, $fallback) {
    $n = substr(trim((string) ($nilai ?? '')), 0, 5);
    return ($n === '' || $n === '00:00') ? $fallback : $n;
};
$jamMasukDefault  = $jamHarian($jadwalHariIni->jam_masuk ?? null, '07:30');
$jamPulangDefault = $jamHarian($jadwalHariIni->jam_pulang ?? null, '15:00');
?>

<?= $this->section('styles') ?>
<style>
    /* ============================================================
       ABSENSI MANUAL — Improved UX Styles
       ============================================================ */

    /* ---- Mode switcher (top-level tabs) ---- */
    .mode-switcher-container {
        background: #f1f5f9; padding: 4px; border-radius: 12px;
        display: inline-flex; gap: 4px; border: 1px solid #e2e8f0;
    }
    .mode-tab-btn {
        padding: 0.5rem 1.1rem; min-height: 40px; font-weight: 700;
        border-radius: 9px; border: none; color: #64748b; background: transparent;
        font-size: 0.82rem; white-space: nowrap; cursor: pointer;
        transition: background .15s, color .15s, box-shadow .15s;
    }
    .mode-tab-btn.active {
        background: linear-gradient(135deg, var(--primary-600), var(--indigo-600)); color: #fff !important;
        box-shadow: 0 2px 10px rgb(var(--primary-600-rgb) / .35);
    }
    .mode-tab-btn.is-disabled { opacity: .38; pointer-events: none; cursor: not-allowed; }
    .mode-tab-btn:not(.active):not(.is-disabled):hover { background: #e2e8f0; }

    /* ---- Status tile radio buttons ---- */
    .status-tile-radio { position: absolute; opacity: 0; pointer-events: none; }
    .status-tile {
        display: flex; align-items: center; gap: 6px;
        border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 0.5rem 0.85rem;
        cursor: pointer; background: #fff; font-size: 0.8rem; font-weight: 700; color: #475569;
        transition: border-color .15s, background .15s, box-shadow .15s;
        user-select: none;
    }
    .status-tile:hover { border-color: #94a3b8; background: #f8fafc; }
    .status-tile-radio[value="HADIR"]:checked + .status-tile  { border-color: #10b981; background: #ecfdf5; color: #047857; box-shadow: 0 2px 8px rgba(16,185,129,.2); }
    .status-tile-radio[value="IJIN"]:checked  + .status-tile  { border-color: #0ea5e9; background: #f0f9ff; color: #0369a1; box-shadow: 0 2px 8px rgba(14,165,233,.2); }
    .status-tile-radio[value="SAKIT"]:checked + .status-tile  { border-color: #f59e0b; background: #fffbeb; color: #b45309; box-shadow: 0 2px 8px rgba(245,158,11,.2); }
    .status-tile-radio[value="CUTI"]:checked  + .status-tile  { border-color: var(--accent-500); background: var(--accent-50); color: var(--accent-700); box-shadow: 0 2px 8px rgb(var(--accent-500-rgb) / .2); }
    .status-tile-radio[value="DINAS"]:checked + .status-tile  { border-color: var(--primary-500); background: var(--primary-50); color: var(--primary-700); box-shadow: 0 2px 8px rgb(var(--primary-500-rgb) / .2); }
    .status-tile-radio:focus-visible + .status-tile { outline: 2px solid var(--primary-600); outline-offset: 3px; }

    /* ---- Jenis absen switcher (Datang / Pulang / Keduanya) ---- */
    #jenisAbsenSwitcher .mode-tab-btn.active[data-jenis="masuk"]    { background: linear-gradient(135deg, #10b981, #059669); }
    #jenisAbsenSwitcher .mode-tab-btn.active[data-jenis="pulang"]   { background: linear-gradient(135deg, #f59e0b, #ea580c); }
    #jenisAbsenSwitcher .mode-tab-btn.active[data-jenis="keduanya"] { background: linear-gradient(135deg, var(--primary-600), var(--accent-600)); }

    /* ---- Hint/info box for jenis absen ---- */
    .jenis-hint-box {
        display: flex; align-items: flex-start; gap: 8px;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
        padding: 8px 12px; font-size: 0.78rem; color: #475569; margin-top: 8px;
    }
    .jenis-hint-box.hint-aktif { background: #fffbeb; border-color: #fcd34d; color: #92400e; }

    /* ---- Save button ---- */
    .btn-save {
        background: linear-gradient(135deg, var(--primary-600), var(--indigo-600));
        box-shadow: 0 4px 14px rgb(var(--primary-600-rgb) / .35);
        transition: filter .15s, box-shadow .15s;
    }
    .btn-save:hover:not(:disabled) { filter: brightness(1.06); box-shadow: 0 6px 18px rgb(var(--primary-600-rgb) / .4); }
    .btn-save:disabled { opacity: .5; cursor: not-allowed; filter: none; }
    .btn-today { background: linear-gradient(135deg, var(--primary-600), var(--primary-700)); box-shadow: 0 2px 8px rgb(var(--primary-600-rgb) / .3); }
    .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 2px 8px rgba(239,68,68,.3); }

    /* ---- Date shortcut buttons ---- */
    .date-shortcut {
        padding: 0.45rem 0.85rem; min-height: 40px; font-size: 0.8rem; font-weight: 600;
        border: 1.5px solid #e2e8f0; border-radius: 9px; background: #fff; color: #475569;
        cursor: pointer; transition: border-color .15s, background .15s;
    }
    .date-shortcut:hover { border-color: #94a3b8; background: #f8fafc; }

    /* ---- Date info strip ---- */
    #tanggalInfoStrip:not(.hidden) {
        font-size: 0.82rem; font-weight: 600; color: #334155;
        padding: 6px 12px; background: #f1f5f9; border-radius: 8px;
        display: inline-flex; align-items: center; gap: 6px;
    }
    #tanggalInfoStrip.is-today { background: var(--primary-100); color: var(--primary-700); }
    #tanggalInfoStrip.is-libur { background: #fef3c7; color: #92400e; }

    /* ---- Summary / status panel (right sidebar) ---- */
    .summary-panel { padding: 0; overflow: hidden; }
    .summary-avatar {
        width: 48px; height: 48px; border-radius: 14px;
        background: linear-gradient(135deg, var(--primary-600), var(--accent-600));
        color: #fff; font-size: 1.3rem; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .summary-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 999px; font-size: 0.73rem; font-weight: 700;
        background: #f1f5f9; color: #64748b;
    }
    .summary-chip.chip-hadir   { background: #dcfce7; color: #15803d; }
    .summary-chip.chip-masuk   { background: #fef9c3; color: #a16207; }
    .summary-chip.chip-ijin    { background: #e0f2fe; color: #0369a1; }
    .summary-chip.chip-belum   { background: #f1f5f9; color: #94a3b8; }
    .summary-jam-row {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem;
    }
    .summary-jam-dot {
        width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    }
    .summary-jadwal-row {
        font-size: 0.73rem; color: #64748b;
        padding: 8px 12px; background: #f8fafc; border-radius: 8px; margin-top: 8px;
    }

    /* ---- Filter pills (batch mode) ---- */
    .filter-pill {
        border-radius: 999px; font-size: 0.78rem; padding: 0.42rem 0.85rem;
        font-weight: 700; cursor: pointer; border: 1.5px solid #e2e8f0;
        background: #fff; color: #64748b; transition: background .12s, border-color .12s, color .12s;
    }
    .filter-pill:hover { border-color: #94a3b8; }
    .filter-pill[data-filter="all"].active    { background: #0f172a; border-color: #0f172a; color: #fff; }
    .filter-pill[data-filter="belum"].active  { background: #ef4444; border-color: #ef4444; color: #fff; }
    .filter-pill[data-filter="lengkap"].active{ background: #10b981; border-color: #10b981; color: #fff; }
    .filter-pill[data-filter="izin"].active   { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }
    .badge-count { font-weight: 800; opacity: .80; }

    /* ---- Batch stats strip ---- */
    .batch-stat-item {
        display: flex; flex-direction: column; align-items: center;
        flex: 1 1 96px; padding: 6px 14px; border-radius: 10px;
        min-width: 68px; text-align: center;
    }
    .batch-stat-num { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
    .batch-stat-lbl { font-size: 0.68rem; font-weight: 600; margin-top: 1px; opacity: .72; }
    .stat-total   { background: #f1f5f9; color: #334155; }
    .stat-lengkap { background: #dcfce7; color: #15803d; }
    .stat-masuk   { background: #fef9c3; color: #a16207; }
    .stat-izin    { background: #e0f2fe; color: #0369a1; }
    .stat-belum   { background: #fee2e2; color: #b91c1c; }

    /* ---- Progress bar (batch mode) ---- */
    .batch-progress-bar { height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
    .batch-progress-fill { height: 100%; border-radius: 2px; background: #10b981; transition: width .35s ease; }

    /* ---- Table ---- */
    #tabelGuru thead th {
        position: sticky; top: 0; z-index: 10;
        background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;
        font-size: 0.71rem; text-transform: uppercase; letter-spacing: 0.05em;
        color: #64748b; white-space: nowrap; font-weight: 800; padding: 10px 12px;
    }
    #tabelGuru tbody tr { border-left: 3px solid transparent; transition: background .12s; }
    #tabelGuru tbody tr[data-category="lengkap"] { border-left-color: #10b981; }
    #tabelGuru tbody tr[data-category="masuk"]   { border-left-color: #f59e0b; }
    #tabelGuru tbody tr[data-category="izin"]    { border-left-color: #0ea5e9; }
    #tabelGuru tbody tr.row-edited               { background: var(--primary-50) !important; }
    #tabelGuru input, #tabelGuru select          { min-height: 38px; font-size: 0.82rem; }
    .real-badge { font-weight: 700; font-size: 0.72rem; }
    tr[data-category="lengkap"] .real-badge { color: #059669; }
    tr[data-category="masuk"]   .real-badge { color: #d97706; }
    tr[data-category="izin"]    .real-badge { color: #0284c7; }
    tr[data-category="belum"]   .real-badge { color: #94a3b8; }
    .teacher-initial {
        width: 30px; height: 30px; border-radius: 8px;
        background: linear-gradient(135deg, var(--accent-200), var(--primary-200));
        color: var(--indigo-700); font-size: 0.75rem; font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    /* ---- Sticky bottom bar (batch save) ---- */
    .sticky-bottom-bar {
        position: sticky; bottom: 0; z-index: 20;
        background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
        border-top: 1px solid #e2e8f0;
        padding: 10px 20px;
    }

    /* ---- Loading skeleton ---- */
    .skeleton { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 6px; }
    @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    /* ---- Hari aktif warning ----
       :not(.hidden) wajib — display:flex custom menimpa class utilitas
       .hidden (spesifisitas sama) sehingga banner selalu tampil. */
    .hari-aktif-notice:not(.hidden) {
        display: flex; align-items: flex-start; gap: 8px;
        background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px;
        padding: 10px 14px; font-size: 0.8rem; color: #92400e; font-weight: 500;
    }
    .libur-notice:not(.hidden) {
        display: flex; align-items: flex-start; gap: 8px;
        background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px;
        padding: 10px 14px; font-size: 0.8rem; color: #c2410c; font-weight: 500;
    }

    /* ---- Edited badge (batch) ---- */
    .edited-badge {
        display: inline-flex; align-items: center; gap: 4px;
        background: var(--primary-100); color: var(--primary-700); border-radius: 999px;
        padding: 2px 10px; font-size: 0.72rem; font-weight: 700;
    }

    /* ---- Responsive ---- */
    @media (max-width: 640px) {
        #tabelGuru { min-width: 680px; }
        .batch-stat-item { min-width: 56px; padding: 5px 10px; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ================================================================= -->
    <!-- PAGE HEADER                                                        -->
    <!-- ================================================================= -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-pencil-square"></i> Absensi Manual
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Input Kehadiran Guru</h1>
            <p class="text-xs text-slate-500 mt-1">Catat kehadiran guru secara manual oleh admin / panitia, per guru maupun massal.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= base_url('admin/presensi') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-speedometer2"></i> Monitoring
            </a>
            <a href="<?= base_url('admin/presensi/rekap') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-table"></i> Rekap
            </a>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- DATE & MODE BAR                                                    -->
    <!-- ================================================================= -->
    <div class="bg-white rounded-2xl border border-slate-200 px-5 py-4 space-y-3">
        <div class="flex flex-col md:flex-row md:items-center gap-3 flex-wrap">
            <!-- Date picker + shortcuts -->
            <div class="grid grid-cols-2 gap-2 md:flex md:items-center md:gap-2">
                <div class="relative col-span-2 md:col-span-1">
                    <input type="date"
                        class="w-full md:w-auto px-3 py-2 min-h-[40px] border border-slate-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none"
                        id="inputTanggal"
                        value="<?= date('Y-m-d') ?>"
                        max="<?= date('Y-m-d') ?>"
                        aria-label="Tanggal absensi">
                </div>
                <button type="button" class="date-shortcut w-full md:w-auto" id="btnKemarin">Kemarin</button>
                <button type="button" class="btn-today date-shortcut w-full md:w-auto text-white border-transparent" id="btnHariIni">Hari Ini</button>
            </div>

            <!-- Date human-readable info -->
            <div id="tanggalInfoStrip" class="hidden">
                <i class="bi bi-calendar3 text-xs"></i>
                <span id="tanggalHumanText">—</span>
            </div>

            <!-- Mode tabs -->
            <div class="mode-switcher-container md:ml-auto" id="modeSwitcher" role="tablist" aria-label="Mode pengisian">
                <button type="button" class="mode-tab-btn active" id="tabModeSingle" data-mode="single" role="tab" aria-selected="true">
                    <i class="bi bi-person me-1"></i>Per Guru
                </button>
                <button type="button" class="mode-tab-btn" id="tabModeBatch" data-mode="batch" role="tab" aria-selected="false">
                    <i class="bi bi-people me-1"></i>Massal
                </button>
            </div>
        </div>

        <!-- Notices (hari aktif / libur) -->
        <div id="aturanHariAktif" class="hari-aktif-notice hidden" role="alert">
            <i class="bi bi-clock-history text-sm flex-shrink-0 mt-0.5"></i>
            <span><strong>Hari ini (hari aktif):</strong> hanya boleh absen <em>Datang saja</em> atau <em>Pulang saja</em>. Datang + Pulang sekaligus hanya untuk tanggal kemarin atau sebelumnya.</span>
        </div>
        <div id="liburWarning" class="libur-notice hidden" role="alert">
            <i class="bi bi-exclamation-triangle text-sm flex-shrink-0 mt-0.5"></i>
            <span id="liburWarningText"><strong>Peringatan hari libur</strong> — Tanggal yang dipilih adalah hari libur. Input tetap diperbolehkan namun pastikan sesuai kebutuhan.</span>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODE 1: SINGLE MODE (PER GURU)                                    -->
    <!-- ================================================================= -->
    <div id="modeSingleView" role="tabpanel" aria-labelledby="tabModeSingle">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Left: Form Presensi -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200">
                    <!-- Card header -->
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="bi bi-pencil-square text-blue-600 text-xs"></i>
                            </span>
                            Form Presensi
                        </h3>
                        <div id="singleStatusBadge" class="summary-chip">Pilih guru</div>
                    </div>

                    <div class="p-5 space-y-5">
                        <!-- 1. Pilih Guru -->
                        <div>
                            <label for="selectGuru" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Guru <span class="text-red-500">*</span>
                            </label>
                            <select class="select2-guru w-full" id="selectGuru">
                                <option value="">-- Ketik nama atau NIP --</option>
                                <?php foreach ($guru as $g):
                                    $nama = esc($g->nama_guru ?? ($g->first_name . ' ' . $g->last_name));
                                    $nip  = $g->nip ? ' — NIP: ' . esc($g->nip) : '';
                                ?>
                                    <option value="<?= $g->id ?>"
                                        data-nip="<?= esc($g->nip ?? '-') ?>"
                                        data-name="<?= $nama ?>">
                                        <?= $nama . $nip ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 2. Status tiles -->
                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-700 mb-2">Status Kehadiran</legend>
                            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Status kehadiran">
                                <span>
                                    <input type="radio" class="status-tile-radio" name="singleStatus" id="statusHadir" value="HADIR" checked>
                                    <label class="status-tile" for="statusHadir">
                                        <i class="bi bi-check-circle text-sm"></i> Hadir
                                    </label>
                                </span>
                                <span>
                                    <input type="radio" class="status-tile-radio" name="singleStatus" id="statusIzin" value="IJIN">
                                    <label class="status-tile" for="statusIzin">
                                        <i class="bi bi-file-text text-sm"></i> Izin
                                    </label>
                                </span>
                                <span>
                                    <input type="radio" class="status-tile-radio" name="singleStatus" id="statusSakit" value="SAKIT">
                                    <label class="status-tile" for="statusSakit">
                                        <i class="bi bi-heart-pulse text-sm"></i> Sakit
                                    </label>
                                </span>
                                <span>
                                    <input type="radio" class="status-tile-radio" name="singleStatus" id="statusCuti" value="CUTI">
                                    <label class="status-tile" for="statusCuti">
                                        <i class="bi bi-calendar-check text-sm"></i> Cuti
                                    </label>
                                </span>
                                <span>
                                    <input type="radio" class="status-tile-radio" name="singleStatus" id="statusDinas" value="DINAS">
                                    <label class="status-tile" for="statusDinas">
                                        <i class="bi bi-briefcase text-sm"></i> Dinas
                                    </label>
                                </span>
                            </div>
                        </fieldset>

                        <!-- 3. Jenis absen (hanya muncul saat HADIR) -->
                        <div id="sectionJenisAbsen">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Jenis Absen</label>
                            <div class="mode-switcher-container" id="jenisAbsenSwitcher" role="group" aria-label="Jenis absen">
                                <button type="button" class="mode-tab-btn" data-jenis="masuk" aria-pressed="false">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Datang
                                </button>
                                <button type="button" class="mode-tab-btn" data-jenis="pulang" aria-pressed="false">
                                    <i class="bi bi-box-arrow-left me-1"></i>Pulang
                                </button>
                                <button type="button" class="mode-tab-btn active" data-jenis="keduanya" aria-pressed="true">
                                    <i class="bi bi-arrow-left-right me-1"></i>Keduanya
                                </button>
                            </div>
                            <!-- Hint box — selalu visible -->
                            <div id="jenisHintBox" class="jenis-hint-box mt-2">
                                <i class="bi bi-info-circle text-sm flex-shrink-0 mt-0.5"></i>
                                <span id="jenisHintText">Mencatat jam datang dan jam pulang sekaligus.</span>
                            </div>
                        </div>

                        <!-- 4. Jam input (hanya muncul saat HADIR) -->
                        <div id="sectionJamHadir">
                            <div class="grid grid-cols-2 gap-3">
                                <div id="boxJamMasuk">
                                    <label for="inputJamMasuk" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        <i class="bi bi-box-arrow-in-right text-emerald-600 me-1"></i>Jam Datang
                                    </label>
                                    <input type="time"
                                        class="w-full px-3 py-2.5 min-h-[42px] border border-slate-200 rounded-lg text-base font-mono focus:ring-2 focus:ring-emerald-300 focus:border-emerald-400 outline-none"
                                        id="inputJamMasuk" value="<?= esc($jamMasukDefault) ?>">
                                    <span id="batasMasukHint" class="hidden"><?= esc($jamMasukDefault) ?></span>
                                </div>
                                <div id="boxJamPulang">
                                    <label for="inputJamPulang" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        <i class="bi bi-box-arrow-left text-orange-500 me-1"></i>Jam Pulang
                                    </label>
                                    <input type="time"
                                        class="w-full px-3 py-2.5 min-h-[42px] border border-slate-200 rounded-lg text-base font-mono focus:ring-2 focus:ring-orange-300 focus:border-orange-400 outline-none"
                                        id="inputJamPulang" value="<?= esc($jamPulangDefault) ?>">
                                    <span id="batasPulangHint" class="hidden"><?= esc($jamPulangDefault) ?></span>
                                </div>
                            </div>
                            <!-- Jadwal kerja hint -->
                            <div id="jadwalKerjaHint" class="summary-jadwal-row hidden">
                                <i class="bi bi-clock text-xs me-1"></i>
                                <span id="jadwalKerjaText"></span>
                            </div>
                        </div>

                        <!-- 5. Catatan -->
                        <div>
                            <label for="inputKeterangan" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Catatan <span class="text-slate-400 font-normal text-xs">(opsional)</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                    class="w-full px-3 py-2.5 min-h-[42px] pr-16 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none"
                                    id="inputKeterangan" maxlength="500" placeholder="Mis: Keperluan keluarga, sakit mendadak…">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none">
                                    <span id="keteranganCounter">0</span>/500
                                </span>
                            </div>
                        </div>

                        <!-- 6. Action buttons -->
                        <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                            <button type="button"
                                class="hidden px-4 py-2.5 min-h-[42px] text-xs font-bold text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                                id="btnResetSingle"
                                title="Hapus data presensi guru ini pada tanggal yang dipilih">
                                <i class="bi bi-trash me-1"></i>Hapus Presensi
                            </button>
                            <button type="button"
                                class="btn-save flex-1 px-6 py-2.5 min-h-[44px] text-sm font-bold text-white rounded-xl"
                                id="btnSaveSingle">
                                <i class="bi bi-floppy me-1"></i><span>Simpan Presensi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Status Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-slate-200 summary-panel lg:sticky lg:top-4">
                    <!-- Card header -->
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center">
                                <i class="bi bi-person-check text-violet-600 text-xs"></i>
                            </span>
                            Status Terkini
                        </h3>
                    </div>

                    <!-- Teacher identity -->
                    <div class="px-5 pt-4 pb-3 flex items-center gap-3 border-b border-slate-100">
                        <div class="summary-avatar" id="summaryAvatar">?</div>
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900 truncate text-sm leading-snug" id="summaryTeacherName">Pilih guru terlebih dulu</p>
                            <p class="text-xs text-slate-400 mt-0.5" id="summaryTeacherNip">—</p>
                        </div>
                    </div>

                    <!-- Status info -->
                    <div class="px-5 py-4 space-y-1" id="summaryInfoBlock">
                        <!-- Loading state -->
                        <div id="summaryLoading" class="hidden space-y-2 py-1">
                            <div class="skeleton h-4 w-3/4"></div>
                            <div class="skeleton h-4 w-1/2"></div>
                            <div class="skeleton h-4 w-2/3"></div>
                        </div>

                        <!-- Empty state -->
                        <div id="summaryEmpty" class="py-4 text-center">
                            <i class="bi bi-person-dash text-3xl text-slate-200"></i>
                            <p class="text-xs text-slate-400 mt-1">Pilih guru untuk melihat status kehadiran hari ini</p>
                        </div>

                        <!-- Filled state -->
                        <div id="summaryFilled" class="hidden space-y-1">
                            <!-- Status badge row -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500">Status</span>
                                <span id="summaryStatusText" class="summary-chip chip-belum">—</span>
                            </div>
                            <!-- Datang -->
                            <div class="summary-jam-row">
                                <div class="summary-jam-dot bg-emerald-400" id="dotMasuk"></div>
                                <span class="text-xs text-slate-500 w-14 flex-shrink-0">Datang</span>
                                <span class="text-sm font-bold text-emerald-700 ml-auto font-mono" id="summaryJamMasuk">—</span>
                            </div>
                            <!-- Pulang -->
                            <div class="summary-jam-row" style="border-bottom: none;">
                                <div class="summary-jam-dot bg-orange-400" id="dotPulang"></div>
                                <span class="text-xs text-slate-500 w-14 flex-shrink-0">Pulang</span>
                                <span class="text-sm font-bold text-orange-600 ml-auto font-mono" id="summaryJamPulang">—</span>
                            </div>
                            <!-- Catatan -->
                            <div id="summaryKeteranganRow" class="hidden pt-2">
                                <p class="text-xs text-slate-400 mb-1">Catatan</p>
                                <p class="text-xs text-slate-600 italic" id="summaryKeterangan">—</p>
                            </div>
                        </div>
                    </div>

                    <!-- Jadwal kerja hint -->
                    <div id="summaryJadwalRow" class="hidden px-5 pb-4">
                        <div class="summary-jadwal-row">
                            <i class="bi bi-alarm text-xs me-1.5"></i>
                            <span class="text-xs" id="summaryJadwal"></span>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /grid -->
    </div><!-- /modeSingleView -->

    <!-- ================================================================= -->
    <!-- MODE 2: BATCH MODE (MASSAL)                                        -->
    <!-- ================================================================= -->
    <div id="modeBatchView" class="hidden" role="tabpanel" aria-labelledby="tabModeBatch">
        <div class="bg-white rounded-2xl border border-slate-200">

            <!-- Toolbar top: stats + actions -->
            <div class="px-5 py-4 border-b border-slate-100">
                <!-- Stats strip -->
                <div class="flex items-center gap-2 flex-wrap mb-3" aria-live="polite" aria-label="Statistik presensi massal">
                    <div class="batch-stat-item stat-total">
                        <span class="batch-stat-num" id="statTotal"><?= count($guru) ?></span>
                        <span class="batch-stat-lbl">Total</span>
                    </div>
                    <div class="batch-stat-item stat-lengkap">
                        <span class="batch-stat-num" id="statLengkap">0</span>
                        <span class="batch-stat-lbl">Lengkap</span>
                    </div>
                    <div class="batch-stat-item stat-masuk">
                        <span class="batch-stat-num" id="statMasuk">0</span>
                        <span class="batch-stat-lbl">Masuk</span>
                    </div>
                    <div class="batch-stat-item stat-izin">
                        <span class="batch-stat-num" id="statIzin">0</span>
                        <span class="batch-stat-lbl">Izin</span>
                    </div>
                    <div class="batch-stat-item stat-belum">
                        <span class="batch-stat-num" id="statBelum">0</span>
                        <span class="batch-stat-lbl">Belum</span>
                    </div>
                    <!-- Edited count badge -->
                    <div class="ml-auto">
                        <span id="editedBadgeWrap" class="hidden">
                            <span class="edited-badge"><i class="bi bi-pencil me-1"></i><span id="editedCount">0</span> diubah</span>
                        </span>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="batch-progress-bar mb-3">
                    <div class="batch-progress-fill" id="batchProgressFill" style="width:0%"></div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-semibold text-slate-500 me-1">Aksi massal:</span>
                    <button type="button"
                        class="px-3 py-1.5 text-xs font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors"
                        id="btnBatchFillEmpty"
                        title="Isi Hadir <?= esc($jamMasukDefault) ?> untuk guru yang belum absen">
                        <i class="bi bi-plus-circle me-1"></i>Isi yang Belum
                    </button>
                    <button type="button"
                        class="px-3 py-1.5 text-xs font-semibold border border-orange-200 bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition-colors"
                        id="btnBatchPulangSaja"
                        title="Isi jam pulang untuk guru yang sudah datang">
                        <i class="bi bi-box-arrow-left me-1"></i>Pulang Saja
                    </button>
                    <button type="button"
                        class="px-3 py-1.5 text-xs font-semibold border border-slate-200 bg-white text-slate-500 rounded-lg hover:bg-slate-50 transition-colors"
                        id="btnBatchReset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                    <!-- hidden / kept for JS compatibility -->
                    <button type="button" class="hidden" id="btnBatchAllHadir">Semua</button>
                </div>
            </div>

            <!-- Search + Filter -->
            <div class="px-5 py-3 border-b border-slate-100 flex flex-col md:flex-row gap-2 md:items-center">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                    <input type="search"
                        class="w-full pl-9 pr-3 py-2 min-h-[38px] border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none"
                        id="searchTeacherInput"
                        placeholder="Cari nama guru atau NIP…"
                        autocomplete="off">
                    <button class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" type="button" id="btnClearSearch" aria-label="Bersihkan pencarian">
                        <i class="bi bi-x-circle text-sm"></i>
                    </button>
                </div>
                <div class="flex gap-1.5 flex-wrap items-center" role="group" aria-label="Filter kategori">
                    <button type="button" class="filter-pill active" data-filter="all">Semua <span class="badge-count" id="pillCountAll"><?= count($guru) ?></span></button>
                    <button type="button" class="filter-pill" data-filter="belum">Belum <span class="badge-count" id="pillCountBelum">0</span></button>
                    <button type="button" class="filter-pill" data-filter="lengkap">Lengkap <span class="badge-count" id="pillCountLengkap">0</span></button>
                    <button type="button" class="filter-pill" data-filter="izin">Izin <span class="badge-count" id="pillCountIzin">0</span></button>
                </div>
                <p id="searchResultCount" class="text-xs text-slate-400 hidden mt-1 md:mt-0"></p>
            </div>

            <!-- Table -->
            <div class="overflow-auto" style="max-height: 58vh;" tabindex="0" aria-label="Tabel presensi massal">
                <table class="w-full text-sm" id="tabelGuru">
                    <thead>
                        <tr>
                            <th class="text-left px-4 py-3" scope="col" style="width: 46%; min-width: 200px;">Guru</th>
                            <th class="px-3 py-3" style="width: 18%;" scope="col">Status</th>
                            <th class="px-3 py-3 text-center" style="width: 18%;" scope="col">Datang</th>
                            <th class="px-3 py-3 text-center" style="width: 18%;" scope="col">Pulang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tabelGuruBody">
                        <?php $no = 1; foreach ($guru as $g):
                            $att          = $todayAtt[$g->id] ?? null;
                            $realStatus   = $att->status_masuk ?? '';
                            $realStatusLabel = 'Belum Absen';
                            $realStatusBadge = 'bg-slate-100 text-slate-500';
                            $filterCategory  = 'belum';

                            if ($att && $att->jam_masuk && $att->jam_pulang) {
                                $realStatusLabel = 'Lengkap';
                                $realStatusBadge = 'bg-emerald-100 text-emerald-700';
                                $filterCategory  = 'lengkap';
                            } elseif ($att && in_array($realStatus, ['IJIN','SAKIT','CUTI','DINAS'])) {
                                $realStatusLabel = $realStatus;
                                $realStatusBadge = 'bg-sky-100 text-sky-700';
                                $filterCategory  = 'izin';
                            } elseif ($att && $att->jam_masuk) {
                                $realStatusLabel = 'Masuk Saja';
                                $realStatusBadge = 'bg-amber-100 text-amber-700';
                                $filterCategory  = 'masuk';
                            }

                            $jamMasukVal    = $att && $att->jam_masuk  ? date('H:i', strtotime($att->jam_masuk))  : '';
                            $jamPulangVal   = $att && $att->jam_pulang ? date('H:i', strtotime($att->jam_pulang)) : '';
                            $keteranganVal  = $att && isset($att->keterangan) ? $att->keterangan : '';
                            $selectedStatus = in_array($realStatus, ['IJIN','SAKIT','CUTI','DINAS']) ? $realStatus : 'HADIR';
                            $namaGuru       = esc($g->nama_guru ?? ($g->first_name . ' ' . $g->last_name));
                            $nipGuru        = esc($g->nip ?? '');
                            $rawNama        = trim((string) ($g->nama_guru ?? trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? ''))));
                            $initial        = esc(mb_strtoupper(mb_substr($rawNama !== '' ? $rawNama : '?', 0, 1)));
                        ?>
                        <tr data-user-id="<?= $g->id ?>"
                            data-category="<?= $filterCategory ?>"
                            data-search="<?= strtolower($namaGuru . ' ' . $nipGuru) ?>">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="teacher-initial flex-shrink-0"><?= $initial ?></div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-800 teacher-name text-sm leading-tight truncate"><?= $namaGuru ?></div>
                                        <?php if ($nipGuru): ?>
                                            <div class="text-xs text-slate-400 font-mono"><?= $nipGuru ?></div>
                                        <?php endif; ?>
                                        <div class="text-xs mt-0.5">
                                            <span class="real-badge <?= $realStatusBadge ?> px-1.5 py-0.5 rounded-md"><?= $realStatusLabel ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 py-2.5">
                                <select class="batch-status w-full px-2 py-1.5 border border-slate-200 rounded-lg" aria-label="Status <?= $namaGuru ?>">
                                    <option value="HADIR"  <?= $selectedStatus === 'HADIR'  ? 'selected' : '' ?>>Hadir</option>
                                    <option value="IJIN"   <?= $selectedStatus === 'IJIN'   ? 'selected' : '' ?>>Izin</option>
                                    <option value="SAKIT"  <?= $selectedStatus === 'SAKIT'  ? 'selected' : '' ?>>Sakit</option>
                                    <option value="CUTI"   <?= $selectedStatus === 'CUTI'   ? 'selected' : '' ?>>Cuti</option>
                                    <option value="DINAS"  <?= $selectedStatus === 'DINAS'  ? 'selected' : '' ?>>Dinas</option>
                                </select>
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" class="jam-masuk w-full px-2 py-1.5 border border-slate-200 rounded-lg font-mono text-center"
                                    aria-label="Datang <?= $namaGuru ?>"
                                    value="<?= $selectedStatus === 'HADIR' ? $jamMasukVal : '' ?>"
                                    <?= $selectedStatus !== 'HADIR' ? 'disabled' : '' ?>>
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" class="jam-pulang w-full px-2 py-1.5 border border-slate-200 rounded-lg font-mono text-center"
                                    aria-label="Pulang <?= $namaGuru ?>"
                                    value="<?= $selectedStatus === 'HADIR' ? $jamPulangVal : '' ?>"
                                    <?= $selectedStatus !== 'HADIR' ? 'disabled' : '' ?>>
                                <input type="hidden" class="keterangan" value="<?= esc($keteranganVal) ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <tr id="emptyBatchRow" class="hidden">
                            <td colspan="4" class="px-4 py-10 text-center">
                                <i class="bi bi-search text-2xl text-slate-200 block mb-2"></i>
                                <span class="text-sm text-slate-400">Tidak ada hasil yang cocok.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sticky save bar -->
            <div class="sticky-bottom-bar flex items-center gap-3">
                <div class="text-xs text-slate-500 flex-1">
                    <span id="batchSaveHint">Ubah minimal 1 baris untuk mengaktifkan tombol simpan.</span>
                </div>
                <button type="button"
                    class="px-6 py-2.5 min-h-[42px] text-sm font-bold text-white rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                    id="btnSimpanSemua"
                    style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16,185,129,.35);"
                    disabled>
                    <i class="bi bi-cloud-upload me-1.5"></i>
                    <span>Simpan <span id="btnSimpanCount">0</span> Perubahan</span>
                </button>
            </div>
        </div>
    </div><!-- /modeBatchView -->

</div><!-- /page container -->

<!-- ================================================================= -->
<!-- SCRIPTS                                                            -->
<!-- ================================================================= -->
<script>
    /* -------------------------------------------------------
       Config from PHP
    ------------------------------------------------------- */
    // Penanda revisi untuk diagnosis cache: ketik MANUAL_ABSEN_BUILD di console.
    // Harus '20261006-rev3' — bila tidak, browser masih menyajikan halaman lama.
    var MANUAL_ABSEN_BUILD   = '20261006-rev3';
    window.MANUAL_ABSEN_BUILD = MANUAL_ABSEN_BUILD;
    var HOLIDAYS_DATA        = <?= json_encode($holidays ?? []) ?>;
    var JADWAL_DATA          = <?= json_encode($jadwalKerja ?? []) ?>;
    var HARI_LIBUR_MINGGUAN  = <?= json_encode($hariLiburMingguan ?? ['Sun']) ?>;
    var currentMode          = 'single';
    var activeFilter         = 'all';
    var TODAY_STR            = '<?= date('Y-m-d') ?>';

    /* -------------------------------------------------------
       Helpers
    ------------------------------------------------------- */
    function getCsrfToken() { return (typeof CSRF_HASH !== 'undefined') ? CSRF_HASH : ''; }
    function getCsrfName()  { return (typeof CSRF_NAME !== 'undefined') ? CSRF_NAME : 'csrf_test_name'; }

    function spinTW() {
        return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>';
    }

    // Alasan libur terperinci agar banner bisa menjelaskan, bukan sekadar
    // "hari libur" (server menolak presensi HADIR & semua simpan single).
    function liburInfo(tanggal) {
        if (!tanggal) return { libur: false, alasan: '' };
        var d       = new Date(tanggal + 'T00:00:00');
        if (isNaN(d.getTime())) return { libur: false, alasan: '' };
        var dayNum  = d.getDay();
        var isoDay  = dayNum === 0 ? 7 : dayNum;
        var dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
        var dayLong = d.toLocaleDateString('id-ID', { weekday: 'long' });
        if (JADWAL_DATA[isoDay] && parseInt(JADWAL_DATA[isoDay].is_libur) === 1) {
            return { libur: true, alasan: 'jadwal kerja menetapkan ' + dayLong + ' sebagai libur' };
        }
        if (HARI_LIBUR_MINGGUAN.indexOf(dayName) !== -1) {
            return { libur: true, alasan: dayLong + ' adalah hari libur mingguan' };
        }
        if (HOLIDAYS_DATA && Object.prototype.hasOwnProperty.call(HOLIDAYS_DATA, tanggal)) {
            var ket = HOLIDAYS_DATA[tanggal] || 'Hari libur nasional';
            return { libur: true, alasan: 'libur nasional: ' + ket };
        }
        return { libur: false, alasan: '' };
    }

    function isHariLibur(tanggal) {
        return liburInfo(tanggal).libur;
    }

    function isHariAktif(tanggal) { return !!tanggal && tanggal === TODAY_STR; }

    /* Jam kerja hari terpilih dari jadwal kerja (JADWAL_DATA), bukan angka
       tetap: default 07:30/15:00 memaksa guru shift sore "telat" saat admin
       memakai aksi massal. Fallback hanya bila jadwal hari itu tidak ada. */
    function jamKerja(tanggal, kolom, fallback) {
        var iso = 0;
        if (tanggal && /^\d{4}-\d{2}-\d{2}$/.test(tanggal)) {
            var d = new Date(tanggal + 'T00:00:00');
            iso = isNaN(d) ? 0 : (d.getDay() === 0 ? 7 : d.getDay());
        } else {
            var n = new Date();
            iso = n.getDay() === 0 ? 7 : n.getDay();
        }
        var j = JADWAL_DATA[iso] || JADWAL_DATA[String(iso)] || null;
        var v = j ? String(j[kolom] || '').slice(0, 5) : '';
        return (v === '' || v === '00:00') ? fallback : v;
    }
    function jamKerjaMasuk(tanggal)  { return jamKerja(tanggal, 'jam_masuk',  '07:30'); }
    function jamKerjaPulang(tanggal) { return jamKerja(tanggal, 'jam_pulang', '15:00'); }
    function tanggalForm() {
        var el = document.getElementById('inputTanggal');
        return el ? el.value : '';
    }

    function formatTanggalID(tanggal) {
        try {
            var d = new Date(tanggal + 'T00:00:00');
            return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        } catch(e) { return tanggal; }
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function(c) {
            return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }

    /* -------------------------------------------------------
       Date bar
    ------------------------------------------------------- */
    function quickSetDate(offset) {
        var d = new Date();
        if (offset !== 0) d.setDate(d.getDate() + offset);
        var yyyy = d.getFullYear();
        var mm   = String(d.getMonth() + 1).padStart(2, '0');
        var dd   = String(d.getDate()).padStart(2, '0');
        var dateStr = yyyy + '-' + mm + '-' + dd;
        if (dateStr > TODAY_STR) dateStr = TODAY_STR;
        var inputTgl = document.getElementById('inputTanggal');
        if (inputTgl) { inputTgl.value = dateStr; onTanggalChange(); }
    }

    function onTanggalChange() {
        var inputTgl = document.getElementById('inputTanggal');
        if (inputTgl && inputTgl.value > TODAY_STR) {
            inputTgl.value = TODAY_STR;
            Swal.fire('Perhatian', 'Tanggal masa depan tidak diizinkan — dikembalikan ke hari ini.', 'warning');
        }
        var tanggal = inputTgl?.value;
        var infoLibur = liburInfo(tanggal);
        var isLibur = infoLibur.libur;
        var isAktif = isHariAktif(tanggal);

        // Tanggal info strip
        var strip = document.getElementById('tanggalInfoStrip');
        var stripText = document.getElementById('tanggalHumanText');
        if (strip && stripText && tanggal) {
            stripText.textContent = formatTanggalID(tanggal) + (isAktif ? ' — Hari ini' : '');
            strip.classList.remove('hidden', 'is-today', 'is-libur');
            if (isLibur)  strip.classList.add('is-libur');
            else if (isAktif) strip.classList.add('is-today');
        }

        // Notices — teks libur menyebut alasannya + konsekuensi sesungguhnya
        // (server menolak simpan single & HADIR massal di hari libur)
        document.getElementById('aturanHariAktif')?.classList.toggle('hidden', !isAktif);
        var liburBox = document.getElementById('liburWarning');
        if (liburBox) {
            liburBox.classList.toggle('hidden', !isLibur);
            if (isLibur) {
                var liburText = document.getElementById('liburWarningText') || liburBox.querySelector('span');
                if (liburText) {
                    liburText.innerHTML = '<strong>Hari libur</strong> — '
                        + escHtml(infoLibur.alasan)
                        + '. Simpan per guru & baris HADIR massal akan ditolak server.';
                }
            }
        }
        // Kunci simpan single di hari libur (server pasti menolak)
        var btnSingle = document.getElementById('btnSaveSingle');
        if (btnSingle) {
            btnSingle.disabled = isLibur;
            btnSingle.title = isLibur ? 'Nonaktif: server menolak presensi di hari libur' : '';
        }

        if (currentMode === 'single') {
            loadTeacherStatus();
        } else {
            refreshBatchTable(tanggal);
        }
        enforceJenisAbsenHariAktif();
    }

    /* -------------------------------------------------------
       Mode switcher
    ------------------------------------------------------- */
    function switchMode(mode) {
        currentMode = mode;
        var btnSingle  = document.getElementById('tabModeSingle');
        var btnBatch   = document.getElementById('tabModeBatch');
        var viewSingle = document.getElementById('modeSingleView');
        var viewBatch  = document.getElementById('modeBatchView');

        if (mode === 'single') {
            btnSingle?.classList.add('active');
            btnSingle?.setAttribute('aria-selected', 'true');
            btnBatch?.classList.remove('active');
            btnBatch?.setAttribute('aria-selected', 'false');
            viewSingle?.classList.remove('hidden');
            viewBatch?.classList.add('hidden');
            loadTeacherStatus();
            enforceJenisAbsenHariAktif();
        } else {
            btnBatch?.classList.add('active');
            btnBatch?.setAttribute('aria-selected', 'true');
            btnSingle?.classList.remove('active');
            btnSingle?.setAttribute('aria-selected', 'false');
            viewBatch?.classList.remove('hidden');
            viewSingle?.classList.add('hidden');
            refreshBatchTable(document.getElementById('inputTanggal')?.value);
        }
    }

    /* -------------------------------------------------------
       Jenis absen enforcer (hari aktif)
    ------------------------------------------------------- */
    function enforceJenisAbsenHariAktif() {
        var tanggal    = document.getElementById('inputTanggal')?.value;
        var btnKeduanya = document.querySelector('#jenisAbsenSwitcher [data-jenis="keduanya"]');
        if (!btnKeduanya) return;
        var aktif = isHariAktif(tanggal);
        btnKeduanya.disabled = aktif;
        btnKeduanya.classList.toggle('is-disabled', aktif);
        btnKeduanya.title = aktif ? 'Tidak tersedia untuk hari aktif — pilih Datang atau Pulang' : '';
        if (aktif && getJenisAbsen() === 'keduanya') setJenisAbsen('masuk');
        onJenisAbsenChange();
    }

    /* -------------------------------------------------------
       Status paint helper (summary panel)
    ------------------------------------------------------- */
    function applySummaryStatusChip(el, val) {
        if (!el) return;
        var v = String(val || '').toUpperCase();
        el.className = 'summary-chip';
        if (v === 'HADIR' || v === 'LENGKAP' || v === 'HADIR LENGKAP') el.classList.add('chip-hadir');
        else if (v.indexOf('MASUK') >= 0) el.classList.add('chip-masuk');
        else if (['IJIN','IZIN','SAKIT','CUTI','DINAS'].indexOf(v) >= 0) el.classList.add('chip-ijin');
        else el.classList.add('chip-belum');
        el.textContent = val || '—';
    }

    /* -------------------------------------------------------
       Single mode: jenis absen
    ------------------------------------------------------- */
    function getJenisAbsen() {
        var active = document.querySelector('#jenisAbsenSwitcher .mode-tab-btn.active');
        return active ? active.dataset.jenis : 'keduanya';
    }

    function setJenisAbsen(jenis) {
        document.querySelectorAll('#jenisAbsenSwitcher .mode-tab-btn').forEach(function(btn) {
            var on = btn.dataset.jenis === jenis;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        onJenisAbsenChange();
    }

    function onJenisAbsenChange() {
        var jenis      = getJenisAbsen();
        var boxMasuk   = document.getElementById('boxJamMasuk');
        var boxPulang  = document.getElementById('boxJamPulang');
        var hintBox    = document.getElementById('jenisHintBox');
        var hintText   = document.getElementById('jenisHintText');
        var tanggal    = document.getElementById('inputTanggal')?.value;
        var hints = {
            masuk:    'Hanya mencatat jam datang. Jam pulang dapat dilengkapi belakangan.',
            pulang:   'Hanya mencatat jam pulang. Pastikan guru sudah memiliki catatan jam datang.',
            keduanya: 'Mencatat jam datang dan jam pulang sekaligus.'
        };
        if (boxMasuk)  boxMasuk.style.display  = (jenis === 'pulang')   ? 'none' : 'block';
        if (boxPulang) boxPulang.style.display  = (jenis === 'masuk')    ? 'none' : 'block';
        document.querySelectorAll('#jenisAbsenSwitcher .mode-tab-btn').forEach(function(btn) {
            btn.setAttribute('aria-pressed', btn.dataset.jenis === jenis ? 'true' : 'false');
        });
        if (hintText) {
            // Teks aturan hari aktif sudah ada di banner atas — di sini fokus
            // ke pilihan saat ini agar tidak tampil dua kali.
            var aktif = isHariAktif(tanggal);
            hintText.textContent = hints[jenis] || hints.keduanya;
            if (hintBox) {
                hintBox.classList.toggle('hint-aktif', aktif);
            }
        }
    }

    function onSingleStatusChange() {
        var status     = document.querySelector('input[name="singleStatus"]:checked')?.value || 'HADIR';
        var sectionJam = document.getElementById('sectionJamHadir');
        var sectionJen = document.getElementById('sectionJenisAbsen');
        var isHadir    = status === 'HADIR';
        if (sectionJam) sectionJam.style.display = isHadir ? 'block' : 'none';
        if (sectionJen) sectionJen.style.display = isHadir ? 'block' : 'none';
    }

    function setTimeVal(inputId, val) {
        if (val === 'now') {
            var now = new Date();
            val = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
        }
        var el = document.getElementById(inputId);
        if (el) el.value = val;
    }

    /* -------------------------------------------------------
       Single mode: load teacher status (AJAX)
    ------------------------------------------------------- */
    function loadTeacherStatus() {
        var userId  = document.getElementById('selectGuru')?.value;
        var tanggal = document.getElementById('inputTanggal')?.value;

        var badge       = document.getElementById('singleStatusBadge');
        var summaryEmpty   = document.getElementById('summaryEmpty');
        var summaryFilled  = document.getElementById('summaryFilled');
        var summaryLoading = document.getElementById('summaryLoading');
        var summaryJadwalRow = document.getElementById('summaryJadwalRow');
        var btnReset    = document.getElementById('btnResetSingle');
        var avatarEl    = document.getElementById('summaryAvatar');

        if (!userId || !tanggal) {
            if (badge) { badge.className = 'summary-chip'; badge.textContent = 'Pilih guru'; }
            if (summaryEmpty)  summaryEmpty.classList.remove('hidden');
            if (summaryFilled) summaryFilled.classList.add('hidden');
            if (summaryLoading) summaryLoading.classList.add('hidden');
            if (summaryJadwalRow) summaryJadwalRow.classList.add('hidden');
            if (btnReset) btnReset.classList.add('hidden');
            document.getElementById('summaryTeacherName').textContent = 'Pilih guru terlebih dulu';
            document.getElementById('summaryTeacherNip').textContent  = '—';
            if (avatarEl) avatarEl.textContent = '?';
            return;
        }

        var selOpt    = document.getElementById('selectGuru')?.selectedOptions[0];
        var teacherName = selOpt ? (selOpt.dataset.name || 'Guru')  : 'Guru';
        var teacherNip  = selOpt ? (selOpt.dataset.nip  || '—')     : '—';

        document.getElementById('summaryTeacherName').textContent = teacherName;
        document.getElementById('summaryTeacherNip').textContent  = teacherNip !== '-' ? 'NIP: ' + teacherNip : '—';
        if (avatarEl) avatarEl.textContent = teacherName.charAt(0).toUpperCase();

        // Show loading
        if (summaryLoading)  summaryLoading.classList.remove('hidden');
        if (summaryEmpty)    summaryEmpty.classList.add('hidden');
        if (summaryFilled)   summaryFilled.classList.add('hidden');
        if (badge) { badge.className = 'summary-chip'; badge.textContent = '…'; }

        $.get('<?= site_url("admin/presensi/attendance-status") ?>', { user_id: userId, tanggal: tanggal }, function(res) {
            if (summaryLoading) summaryLoading.classList.add('hidden');

            if (res.status && res.data) {
                var att     = res.data;
                var statusM = att.status_masuk || '';
                var jamM    = att.jam_masuk  ? att.jam_masuk.split(' ')[1].substring(0, 5)  : '—';
                var jamP    = att.jam_pulang ? att.jam_pulang.split(' ')[1].substring(0, 5) : '—';

                // Show filled state
                if (summaryFilled) summaryFilled.classList.remove('hidden');

                // Status chip
                var statusLabel;
                if (['IJIN','SAKIT','CUTI','DINAS','DINAS_APPROVED','DINAS_OUT','DINAS_PENDING'].includes(statusM)) {
                    statusLabel = statusM.indexOf('DINAS') === 0 ? 'DINAS' : statusM;
                } else if (att.jam_masuk && att.jam_pulang) {
                    statusLabel = 'Lengkap';
                } else if (att.jam_masuk) {
                    statusLabel = 'Masuk Saja';
                } else {
                    statusLabel = 'Belum Absen';
                }
                applySummaryStatusChip(document.getElementById('summaryStatusText'), statusLabel);
                if (badge) { badge.className = 'summary-chip'; badge.textContent = statusLabel; }

                // Jam rows
                document.getElementById('summaryJamMasuk').textContent = jamM;
                document.getElementById('summaryJamPulang').textContent = jamP;

                // Dot colors (filled vs empty)
                var dotM = document.getElementById('dotMasuk');
                var dotP = document.getElementById('dotPulang');
                if (dotM) dotM.style.background = att.jam_masuk  ? '#34d399' : '#e2e8f0';
                if (dotP) dotP.style.background = att.jam_pulang ? '#fb923c' : '#e2e8f0';

                // Catatan
                var ketRow = document.getElementById('summaryKeteranganRow');
                var ketEl  = document.getElementById('summaryKeterangan');
                if (att.keterangan && ketRow && ketEl) {
                    ketEl.textContent = att.keterangan;
                    ketRow.classList.remove('hidden');
                } else if (ketRow) {
                    ketRow.classList.add('hidden');
                }

                // Form pre-fill
                if (['IJIN','SAKIT','CUTI','DINAS','DINAS_APPROVED','DINAS_OUT','DINAS_PENDING'].includes(statusM)) {
                    var sl = statusM.indexOf('DINAS') === 0 ? 'DINAS' : statusM;
                    var rid = document.getElementById('status' + (sl.charAt(0).toUpperCase() + sl.slice(1).toLowerCase()));
                    if (rid) { rid.checked = true; onSingleStatusChange(); }
                } else if (att.jam_masuk && att.jam_pulang) {
                    document.getElementById('statusHadir').checked = true;
                    document.getElementById('inputJamMasuk').value = jamM;
                    document.getElementById('inputJamPulang').value = jamP;
                    setJenisAbsen('keduanya');
                    onSingleStatusChange();
                } else if (att.jam_masuk) {
                    document.getElementById('statusHadir').checked = true;
                    document.getElementById('inputJamMasuk').value = jamM;
                    setJenisAbsen('pulang');
                    onSingleStatusChange();
                }

                if (att.keterangan && document.getElementById('inputKeterangan')) {
                    document.getElementById('inputKeterangan').value = att.keterangan;
                    var cc = document.getElementById('keteranganCounter');
                    if (cc) cc.textContent = String(att.keterangan.length);
                }

                if (btnReset) btnReset.classList.remove('hidden');
            } else {
                // No attendance record yet
                if (summaryFilled) summaryFilled.classList.remove('hidden');
                var dotM2 = document.getElementById('dotMasuk');
                var dotP2 = document.getElementById('dotPulang');
                if (dotM2) dotM2.style.background = '#e2e8f0';
                if (dotP2) dotP2.style.background = '#e2e8f0';
                document.getElementById('summaryJamMasuk').textContent  = '—';
                document.getElementById('summaryJamPulang').textContent = '—';
                applySummaryStatusChip(document.getElementById('summaryStatusText'), 'Belum Absen');
                if (badge) { badge.className = 'summary-chip chip-belum'; badge.textContent = 'Belum Absen'; }
                var ketRow2 = document.getElementById('summaryKeteranganRow');
                if (ketRow2) ketRow2.classList.add('hidden');
                if (btnReset) btnReset.classList.add('hidden');
                setJenisAbsen(isHariAktif(tanggal) ? 'masuk' : 'keduanya');
            }

            // Jadwal kerja hint
            if (res.jadwal) {
                var jadwalText = document.getElementById('jadwalKerjaText');
                if (jadwalText) jadwalText.textContent = 'Jadwal: ' + res.jadwal.jam_masuk + ' – ' + res.jadwal.jam_pulang + ' (' + res.jadwal.sumber + ')';
                var jadwalHint = document.getElementById('jadwalKerjaHint');
                if (jadwalHint) jadwalHint.classList.remove('hidden');

                var jadwalRow = document.getElementById('summaryJadwalRow');
                var jadwalEl  = document.getElementById('summaryJadwal');
                if (jadwalEl) jadwalEl.textContent = 'Jadwal kerja: ' + res.jadwal.jam_masuk + ' – ' + res.jadwal.jam_pulang + ' (' + res.jadwal.sumber + ')';
                if (jadwalRow) jadwalRow.classList.remove('hidden');

                var bM = document.getElementById('batasMasukHint');
                var bP = document.getElementById('batasPulangHint');
                if (bM) bM.textContent = String(res.jadwal.jam_masuk).substring(0, 5);
                if (bP) bP.textContent = String(res.jadwal.jam_pulang).substring(0, 5);
            } else {
                var jadwalHint2 = document.getElementById('jadwalKerjaHint');
                if (jadwalHint2) jadwalHint2.classList.add('hidden');
                var jadwalRow2 = document.getElementById('summaryJadwalRow');
                if (jadwalRow2) jadwalRow2.classList.add('hidden');
            }

        }).fail(function() {
            if (summaryLoading) summaryLoading.classList.add('hidden');
            if (summaryEmpty)   summaryEmpty.classList.remove('hidden');
            if (summaryFilled)  summaryFilled.classList.add('hidden');
            if (badge) { badge.className = 'summary-chip'; badge.textContent = 'Error'; }
        });
    }

    /* -------------------------------------------------------
       Single mode: confirm HTML
    ------------------------------------------------------- */
    function singleConfirmHtml(nama, tanggal, status, jenis, jamMasuk, jamPulang, keterangan) {
        var jenisTxt = status !== 'HADIR' ? status : (jenis === 'masuk' ? 'Datang saja' : jenis === 'pulang' ? 'Pulang saja' : 'Datang + Pulang');
        var rows = [
            ['Guru',    escHtml(nama)],
            ['Tanggal', escHtml(formatTanggalID(tanggal))],
            ['Status',  escHtml(status)],
            ['Jenis',   escHtml(jenisTxt)]
        ];
        if (status === 'HADIR' && jenis !== 'pulang')  rows.push(['Jam datang', escHtml(jamMasuk || '—')]);
        if (status === 'HADIR' && jenis !== 'masuk')   rows.push(['Jam pulang', escHtml(jamPulang || '—')]);
        if (keterangan) rows.push(['Catatan', escHtml(keterangan)]);
        return '<div style="text-align:left;font-size:13px">'
            + rows.map(function(r) {
                return '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9">'
                    + '<div style="width:96px;color:#64748b;flex-shrink:0">' + r[0] + '</div>'
                    + '<div style="font-weight:700">' + r[1] + '</div></div>';
            }).join('') + '</div>';
    }

    /* -------------------------------------------------------
       Single mode: AJAX wrappers
    ------------------------------------------------------- */
    function postManualCheckin(postData) {
        return $.ajax({ url: '<?= site_url("admin/presensi/manual-checkin") ?>', type: 'POST', data: postData, dataType: 'json' });
    }
    function postManualCheckout(postData) {
        return $.post('<?= site_url("admin/presensi/manual-checkout") ?>', postData, null, 'json');
    }

    /* -------------------------------------------------------
       Gagal karena token CSRF kedaluwarsa (filter melempar 403 HTML
       untuk AJAX) — tawarkan muat ulang, bukan error generik.
    ------------------------------------------------------- */
    function isCsrfExpired(xhr) { return !!(xhr && xhr.status === 403); }

    function csrfExpiredSwal(recap) {
        Swal.fire({
            icon: 'warning',
            title: 'Sesi Kedaluwarsa',
            html: (recap || '') + '<p style="margin-top:8px;font-size:12px;color:#b45309">Token keamanan kedaluwarsa (tab terbuka lama). Muat ulang halaman lalu ulangi penyimpanan.</p>',
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Muat Ulang Halaman',
            cancelButtonText: 'Nanti',
            showCancelButton: true
        }).then(function(r) { if (r.isConfirmed) location.reload(); });
    }

    /* -------------------------------------------------------
       Single mode: finish save
    ------------------------------------------------------- */
    function finishSingleSave(btn, originalText, ok, title, html) {
        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        if (ok) {
            Swal.fire({ icon: 'success', title: title || 'Berhasil!', html: html, timer: 2600, showConfirmButton: false });
            loadTeacherStatus();
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', html: html || 'Gagal menyimpan presensi.' });
        }
    }

    /* -------------------------------------------------------
       Single mode: process save
    ------------------------------------------------------- */
    function processSingleSave() {
        var userId     = document.getElementById('selectGuru')?.value;
        var tanggal    = document.getElementById('inputTanggal')?.value;
        var status     = document.querySelector('input[name="singleStatus"]:checked')?.value || 'HADIR';
        var jenis      = (status === 'HADIR') ? getJenisAbsen() : 'masuk';
        var jamMasuk   = document.getElementById('inputJamMasuk')?.value;
        var jamPulang  = document.getElementById('inputJamPulang')?.value;
        var keterangan = document.getElementById('inputKeterangan')?.value;

        if (!userId)  { Swal.fire('Perhatian', 'Silakan pilih guru terlebih dahulu.', 'warning'); return; }
        if (!tanggal) { Swal.fire('Perhatian', 'Silakan tentukan tanggal absensi.', 'warning'); return; }
        var infoL = liburInfo(tanggal);
        if (infoL.libur) { Swal.fire('Hari Libur', 'Server menolak presensi di hari libur (' + infoL.alasan + ').', 'warning'); return; }
        if (status === 'HADIR' && jenis !== 'pulang' && !jamMasuk)  { Swal.fire('Perhatian', 'Jam datang harus diisi.', 'warning'); return; }
        if (status === 'HADIR' && jenis !== 'masuk'  && !jamPulang) { Swal.fire('Perhatian', 'Jam pulang harus diisi.', 'warning'); return; }
        if (status === 'HADIR' && jenis === 'keduanya' && isHariAktif(tanggal)) {
            Swal.fire('Tidak Diizinkan', 'Pada hari aktif hanya boleh Datang saja atau Pulang saja. Datang + Pulang sekaligus hanya untuk tanggal kemarin atau sebelumnya.', 'warning');
            return;
        }

        var selOpt   = document.getElementById('selectGuru')?.selectedOptions[0];
        var namaGuru = selOpt ? (selOpt.dataset.name || selOpt.text) : '—';

        Swal.fire({
            title: 'Simpan Presensi?',
            html: singleConfirmHtml(namaGuru, tanggal, status, jenis, jamMasuk, jamPulang, keterangan),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Cek Lagi'
        }).then(function(conf) {
            if (!conf.isConfirmed) return;
            doSingleSave(userId, tanggal, status, jenis, jamMasuk, jamPulang, keterangan, namaGuru);
        });
    }

    function doSingleSave(userId, tanggal, status, jenis, jamMasuk, jamPulang, keterangan, namaGuru) {
        var btn = document.getElementById('btnSaveSingle');
        var originalText = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = spinTW() + ' Menyimpan…'; }
        var recap = singleConfirmHtml(namaGuru, tanggal, status, jenis, jamMasuk, jamPulang, keterangan);

        // Pulang saja → checkout only
        if (status === 'HADIR' && jenis === 'pulang') {
            var outData = { user_id: userId, tanggal: tanggal, jam_pulang: jamPulang };
            outData[getCsrfName()] = getCsrfToken();
            postManualCheckout(outData)
                .done(function(cRes) {
                    finishSingleSave(btn, originalText, cRes.status, cRes.status ? 'Absen Pulang Tersimpan' : 'Gagal',
                        recap + '<p style="margin-top:8px;font-size:12px;color:' + (cRes.status ? '#059669' : '#dc2626') + '">' + escHtml(cRes.message || '') + '</p>');
                })
                .fail(function(xhr) {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                    if (isCsrfExpired(xhr)) { csrfExpiredSwal(recap); return; }
                    finishSingleSave(btn, originalText, false, 'Gagal', recap + '<p style="margin-top:8px;font-size:12px;color:#dc2626">Terjadi kesalahan saat mencatat absen pulang.</p>');
                });
            return;
        }

        var postData = { user_id: userId, tanggal: tanggal, status: status, jam_masuk: status === 'HADIR' ? jamMasuk : '', keterangan: keterangan };
        postData[getCsrfName()] = getCsrfToken();

        postManualCheckin(postData)
            .done(function(res) {
                if (status !== 'HADIR' || jenis === 'masuk' || !jamPulang) {
                    finishSingleSave(btn, originalText, res.status, res.status ? 'Presensi Tersimpan' : 'Gagal',
                        recap + '<p style="margin-top:8px;font-size:12px;color:' + (res.status ? '#059669' : '#dc2626') + '">' + escHtml(res.message || '') + '</p>');
                    return;
                }
                if (!res.status) {
                    finishSingleSave(btn, originalText, false, 'Gagal', recap + '<p style="margin-top:8px;font-size:12px;color:#dc2626">' + escHtml(res.message || '') + '</p>');
                    return;
                }
                var checkoutData = { user_id: userId, tanggal: tanggal, jam_pulang: jamPulang };
                checkoutData[getCsrfName()] = getCsrfToken();
                postManualCheckout(checkoutData)
                    .done(function(cRes) {
                        finishSingleSave(btn, originalText, cRes.status, cRes.status ? 'Datang + Pulang Tersimpan' : 'Sebagian Gagal',
                            recap + '<p style="margin-top:8px;font-size:12px;color:' + (cRes.status ? '#059669' : '#dc2626') + '">' + escHtml(cRes.status ? 'Absensi masuk & pulang berhasil disimpan.' : (cRes.message || 'Absen masuk tersimpan, pulang gagal.')) + '</p>');
                    })
                    .fail(function(xhr) {
                        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                        if (isCsrfExpired(xhr)) { csrfExpiredSwal(recap); return; }
                        finishSingleSave(btn, originalText, false, 'Sebagian Gagal', recap + '<p style="margin-top:8px;font-size:12px;color:#dc2626">Absen masuk tersimpan, namun terjadi kesalahan saat mencatat absen pulang.</p>');
                    });
            })
            .fail(function(xhr) {
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                if (isCsrfExpired(xhr)) { csrfExpiredSwal(recap); return; }
                finishSingleSave(btn, originalText, false, 'Gagal', recap + '<p style="margin-top:8px;font-size:12px;color:#dc2626">Terjadi kesalahan sistem saat menyimpan presensi.</p>');
            });
    }

    /* -------------------------------------------------------
       Single mode: delete
    ------------------------------------------------------- */
    function deleteSingleAttendance() {
        var userId  = document.getElementById('selectGuru')?.value;
        var tanggal = document.getElementById('inputTanggal')?.value;
        if (!userId || !tanggal) return;

        var selOpt   = document.getElementById('selectGuru')?.selectedOptions[0];
        var namaGuru = selOpt ? (selOpt.dataset.name || selOpt.text) : '—';

        Swal.fire({
            title: 'Hapus Presensi?',
            html: '<div style="text-align:left;font-size:13px">'
                + '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9"><div style="width:96px;color:#64748b">Guru</div><div style="font-weight:700">' + escHtml(namaGuru) + '</div></div>'
                + '<div style="display:flex;gap:8px;padding:5px 0"><div style="width:96px;color:#64748b">Tanggal</div><div style="font-weight:700">' + escHtml(formatTanggalID(tanggal)) + '</div></div>'
                + '<p style="margin-top:10px;font-size:12px;color:#b45309;background:#fffbeb;padding:8px 12px;border-radius:8px;border:1px solid #fcd34d">Data akan direset ke Belum Absen. Poin & denda ikut dihitung ulang.</p></div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                var postData = { user_id: userId, tanggal: tanggal };
                postData[getCsrfName()] = getCsrfToken();
                $.post('<?= site_url("admin/presensi/delete-attendance") ?>', postData, function(res) {
                    if (res.status) {
                        Swal.fire({ icon: 'success', title: 'Terhapus', html: '<p style="font-size:13px">' + escHtml(namaGuru) + '</p><p style="font-size:12px;color:#64748b">' + escHtml(formatTanggalID(tanggal)) + '</p><p style="font-size:12px;color:#64748b">' + escHtml(res.message || '') + '</p>', timer: 2200, showConfirmButton: false });
                        loadTeacherStatus();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json').fail(function(xhr) {
                    if (isCsrfExpired(xhr)) { csrfExpiredSwal(); return; }
                    Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error');
                });
            }
        });
    }

    /* -------------------------------------------------------
       Batch mode: statistics & progress
    ------------------------------------------------------- */
    function updateBatchStatistics() {
        var total = 0, lengkap = 0, masuk = 0, izin = 0, belum = 0;
        $('#tabelGuru tbody tr[data-user-id]').each(function() {
            total++;
            var cat = $(this).attr('data-category');
            if (cat === 'lengkap') lengkap++;
            else if (cat === 'masuk') masuk++;
            else if (cat === 'izin') izin++;
            else belum++;
        });
        document.getElementById('statTotal')?.setAttribute('data-val', total);
        document.getElementById('statTotal').textContent   = total;
        document.getElementById('statLengkap').textContent = lengkap;
        document.getElementById('statMasuk').textContent   = masuk;
        document.getElementById('statBelum').textContent   = belum;
        if (document.getElementById('statIzin')) document.getElementById('statIzin').textContent = izin;

        document.getElementById('pillCountAll').textContent    = total;
        document.getElementById('pillCountLengkap').textContent= lengkap;
        document.getElementById('pillCountBelum').textContent  = belum;
        document.getElementById('pillCountIzin').textContent   = izin;

        // Progress bar (lengkap + izin / total)
        var done = lengkap + izin;
        var pct  = total > 0 ? Math.round(done / total * 100) : 0;
        var fill = document.getElementById('batchProgressFill');
        if (fill) fill.style.width = pct + '%';

        updateEditedCount();
    }

    function updateEditedCount() {
        var n   = $('#tabelGuru tbody tr[data-user-id].row-edited').length;
        var btn = document.getElementById('btnSimpanSemua');
        var countEl = document.getElementById('btnSimpanCount');
        var badge   = document.getElementById('editedBadgeWrap');
        var editedCountEl = document.getElementById('editedCount');
        var hintEl  = document.getElementById('batchSaveHint');

        if (countEl) countEl.textContent = n;
        if (editedCountEl) editedCountEl.textContent = n;
        if (badge) badge.classList.toggle('hidden', n === 0);
        if (btn)   btn.disabled = (n === 0);
        if (hintEl) hintEl.textContent = n > 0
            ? n + ' baris telah diubah dan siap disimpan.'
            : 'Ubah minimal 1 baris untuk mengaktifkan tombol simpan.';
    }

    function markRowEdited(row) {
        var $row = $(row).closest('tr');
        if ($row.length && $row.attr('data-user-id')) {
            $row.addClass('row-edited');
            updateEditedCount();
        }
    }

    /* -------------------------------------------------------
       Batch mode: refresh from server
    ------------------------------------------------------- */
    function refreshBatchTable(tanggal) {
        var $tbody = $('#tabelGuru tbody');
        $tbody.addClass('opacity-60');
        $.get('<?= site_url("admin/presensi/batch-attendance-status") ?>', { tanggal: tanggal }, function(res) {
            if (!res.status) { $tbody.removeClass('opacity-60'); return; }
            var data = res.data || {};
            $('#tabelGuru tbody tr[data-user-id]').each(function() {
                var userId     = $(this).data('user-id');
                var att        = data[userId];
                var $jm        = $(this).find('.jam-masuk');
                var $jp        = $(this).find('.jam-pulang');
                var $ket       = $(this).find('.keterangan');
                var $status    = $(this).find('.batch-status');
                var $realBadge = $(this).find('.real-badge');
                var $tr        = $(this);

                var statusVal = 'HADIR', jamMVal = '', jamPVal = '', ketVal = '';
                var realText  = 'Belum Absen';
                var realClass = 'bg-slate-100 text-slate-500';
                var category  = 'belum';

                if (att) {
                    ketVal = att.keterangan || '';
                    if (att.status_masuk && ['IJIN','SAKIT','CUTI','DINAS','DINAS_APPROVED','DINAS_OUT','DINAS_PENDING'].includes(att.status_masuk)) {
                        statusVal = att.status_masuk.indexOf('DINAS') === 0 ? 'DINAS' : att.status_masuk;
                        realText  = statusVal;
                        realClass = 'bg-sky-100 text-sky-700';
                        category  = 'izin';
                    } else if (att.jam_masuk_raw && att.jam_pulang_raw) {
                        jamMVal   = att.jam_masuk;
                        jamPVal   = att.jam_pulang;
                        realText  = 'Lengkap';
                        realClass = 'bg-emerald-100 text-emerald-700';
                        category  = 'lengkap';
                    } else if (att.jam_masuk_raw) {
                        jamMVal   = att.jam_masuk;
                        realText  = 'Masuk Saja';
                        realClass = 'bg-amber-100 text-amber-700';
                        category  = 'masuk';
                    }
                }

                $tr.attr('data-category', category);
                $realBadge.text(realText).attr('class', 'real-badge ' + realClass + ' px-1.5 py-0.5 rounded-md');
                $status.val(statusVal);
                $jm.val(statusVal === 'HADIR' ? jamMVal : '').prop('disabled', statusVal !== 'HADIR');
                $jp.val(statusVal === 'HADIR' ? jamPVal : '').prop('disabled', statusVal !== 'HADIR');
                $ket.val(ketVal);
                $tr.removeClass('row-edited');
            });
            $tbody.removeClass('opacity-60');
            updateBatchStatistics();
            applyTableFilters();
        }).fail(function() { $tbody.removeClass('opacity-60'); });
    }

    /* -------------------------------------------------------
       Batch mode: row toggle (status select change)
    ------------------------------------------------------- */
    function toggleBatchRow(el) {
        var $row    = $(el).closest('tr');
        var status  = $(el).val();
        var $jm     = $row.find('.jam-masuk');
        var $jp     = $row.find('.jam-pulang');
        var tanggal = document.getElementById('inputTanggal')?.value;

        if (status === 'HADIR') {
            $jm.prop('disabled', false);
            if (!$jm.val()) $jm.val(jamKerjaMasuk(tanggal));
            $jp.prop('disabled', false);
            if (!isHariAktif(tanggal) && !$jp.val()) $jp.val(jamKerjaPulang(tanggal));
        } else {
            $jm.prop('disabled', true).val('');
            $jp.prop('disabled', true).val('');
        }
        markRowEdited(el);
    }

    /* -------------------------------------------------------
       Batch mode: bulk fill actions
    ------------------------------------------------------- */
    function setAllBatchStatus(status, masukTime, pulangTime) {
        var tanggal = document.getElementById('inputTanggal')?.value;
        if (status === 'HADIR' && isHariAktif(tanggal)) {
            pulangTime = '';
            Swal.fire('Info', 'Hari aktif: pengisian massal hanya mengisi jam datang saja.', 'info');
        }
        $('#tabelGuru tbody tr:visible[data-user-id]').each(function() {
            var $st = $(this).find('.batch-status');
            var $jm = $(this).find('.jam-masuk');
            var $jp = $(this).find('.jam-pulang');
            $st.val(status);
            if (status === 'HADIR') {
                $jm.prop('disabled', false).val(masukTime || jamKerjaMasuk(tanggalForm()));
                $jp.prop('disabled', false).val(pulangTime || '');
            } else {
                $jm.prop('disabled', true).val('');
                $jp.prop('disabled', true).val('');
            }
            $(this).addClass('row-edited');
        });
        updateEditedCount();
    }

    function setEmptyBatchHadir(masukTime, pulangTime) {
        var tanggal = document.getElementById('inputTanggal')?.value;
        if (isHariAktif(tanggal)) {
            pulangTime = '';
            Swal.fire('Info', 'Hari aktif: hanya jam datang yang diisi otomatis. Jam pulang diisi terpisah.', 'info');
        }
        $('#tabelGuru tbody tr:visible[data-user-id]').each(function() {
            if ($(this).attr('data-category') !== 'belum') return;
            $(this).find('.batch-status').val('HADIR');
            $(this).find('.jam-masuk').prop('disabled', false).val(masukTime || jamKerjaMasuk(tanggalForm()));
            $(this).find('.jam-pulang').prop('disabled', false).val(pulangTime || '');
            $(this).addClass('row-edited');
        });
        updateEditedCount();
    }

    function setPulangSajaBatch(pulangTime) {
        var count = 0;
        $('#tabelGuru tbody tr:visible[data-user-id]').each(function() {
            if ($(this).attr('data-category') !== 'masuk') return;
            $(this).find('.batch-status').val('HADIR');
            $(this).find('.jam-masuk').val('').prop('disabled', false);
            var $jp = $(this).find('.jam-pulang');
            if (!$jp.val()) $jp.val(pulangTime || jamKerjaPulang(tanggalForm()));
            $jp.prop('disabled', false);
            $(this).addClass('row-edited');
            count++;
        });
        if (count === 0) {
            Swal.fire('Info', 'Tidak ada guru berstatus "Masuk Saja" yang tampil saat ini.', 'info');
        } else {
            updateEditedCount();
        }
    }

    function resetAllBatchInputs() {
        refreshBatchTable(document.getElementById('inputTanggal')?.value);
    }

    function deleteBatchRowAttendance(userId) {
        var tanggal = document.getElementById('inputTanggal')?.value;
        if (!userId || !tanggal) return;
        Swal.fire({
            title: 'Hapus Presensi?',
            text: 'Data absensi guru ini pada ' + tanggal + ' akan direset.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                var postData = { user_id: userId, tanggal: tanggal };
                postData[getCsrfName()] = getCsrfToken();
                $.post('<?= site_url("admin/presensi/delete-attendance") ?>', postData, function(res) {
                    if (res.status) { refreshBatchTable(tanggal); }
                    else { Swal.fire('Gagal', res.message, 'error'); }
                }, 'json');
            }
        });
    }

    /* -------------------------------------------------------
       Batch mode: filter + search
    ------------------------------------------------------- */
    function setBatchFilter(filterType, el) {
        activeFilter = filterType;
        document.querySelectorAll('.filter-pill').forEach(function(pill) { pill.classList.remove('active'); });
        if (el) el.classList.add('active');
        applyTableFilters();
    }

    function applyTableFilters() {
        var kw    = (document.getElementById('searchTeacherInput')?.value || '').toLowerCase().trim();
        var shown = 0, total = 0;
        $('#tabelGuru tbody tr[data-user-id]').each(function() {
            total++;
            var searchData = $(this).attr('data-search') || '';
            var category   = $(this).attr('data-category') || 'belum';
            var matchSearch = !kw || searchData.includes(kw);
            var matchFilter = activeFilter === 'all' || category === activeFilter;
            if (matchSearch && matchFilter) { $(this).show(); shown++; }
            else $(this).hide();
        });
        var emptyRow = document.getElementById('emptyBatchRow');
        if (emptyRow) emptyRow.classList.toggle('hidden', shown > 0);
        var countEl = document.getElementById('searchResultCount');
        if (countEl) {
            var active = kw || activeFilter !== 'all';
            countEl.textContent = active ? ('Menampilkan ' + shown + ' dari ' + total + ' guru') : '';
            countEl.classList.toggle('hidden', !active);
        }
        // Clear search button
        var clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) clearBtn.classList.toggle('hidden', !kw);
    }

    /* -------------------------------------------------------
       Batch mode: save all
    ------------------------------------------------------- */
    function batchConfirmHtml(tanggal, rows, infoLibur) {
        var perStatus = {}, datang = 0, pulang = 0, keduanya = 0;
        rows.forEach(function(e) {
            var st = String(e.status || 'HADIR').toUpperCase();
            perStatus[st] = (perStatus[st] || 0) + 1;
            if (st === 'HADIR') {
                if (e.jam_masuk && e.jam_pulang) keduanya++;
                else if (e.jam_pulang) pulang++;
                else datang++;
            }
        });
        var chips = Object.keys(perStatus).sort().map(function(k) {
            var colors = { HADIR: '#059669', IJIN: '#0284c7', SAKIT: '#d97706', CUTI: '#7c3aed', DINAS: '#2563eb' };
            return '<span style="display:inline-block;padding:2px 8px;margin:2px;border-radius:999px;font-size:11px;font-weight:800;background:#f1f5f9;color:' + (colors[k] || '#334155') + '">' + escHtml(k) + ' × ' + perStatus[k] + '</span>';
        }).join('');
        var names = rows.slice(0, 5).map(function(e) {
            return '<li>' + escHtml(e.nama || 'ID ' + e.user_id) + ' — ' + escHtml(String(e.status || '')) + (e.status === 'HADIR' ? ' (' + escHtml(e.jam_masuk || '–') + ' / ' + escHtml(e.jam_pulang || '–') + ')' : '') + '</li>';
        }).join('');
        if (rows.length > 5) names += '<li style="color:#64748b">… dan ' + (rows.length - 5) + ' lainnya</li>';
        return '<div style="text-align:left;font-size:13px">'
            + '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9"><div style="width:88px;color:#64748b">Tanggal</div><div style="font-weight:700">' + escHtml(formatTanggalID(tanggal)) + '</div></div>'
            + '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9"><div style="width:88px;color:#64748b">Diubah</div><div style="font-weight:700">' + rows.length + ' guru</div></div>'
            + '<div style="padding:6px 0">' + chips + '</div>'
            + '<div style="color:#64748b;font-size:11px;margin:2px 0">Datang: ' + datang + ' • Pulang: ' + pulang + ' • Keduanya: ' + keduanya + '</div>'
            + '<ul style="margin:6px 0 0 16px;padding:0;color:#0f172a">' + names + '</ul>'
            + ((infoLibur && infoLibur.libur)
                ? '<p style="margin:8px 0 0;font-size:12px;color:#b45309;background:#fffbeb;padding:8px 12px;border-radius:8px;border:1px solid #fcd34d">Hari libur (' + escHtml(infoLibur.alasan) + '): baris HADIR akan dilewati server, status lain tetap tersimpan.</p>'
                : '')
            + '</div>';
    }

    function batchResultHtml(res, tanggal) {
        var msg = '<p style="font-size:13px;color:#0f172a">' + escHtml(res.message || 'Presensi massal berhasil diperbarui.') + '</p>'
            + '<p style="font-size:11px;color:#64748b">' + escHtml(formatTanggalID(tanggal)) + '</p>';
        if (res.errors && res.errors.length) {
            msg += '<div style="text-align:left;margin-top:8px;max-height:140px;overflow:auto;font-size:12px;color:#b45309">'
                + '<strong>' + res.errors.length + ' baris dilewati:</strong>'
                + '<ul style="margin:4px 0 0 16px;padding:0">'
                + res.errors.slice(0, 8).map(function(e) { return '<li>' + escHtml(e) + '</li>'; }).join('')
                + (res.errors.length > 8 ? '<li>… dan ' + (res.errors.length - 8) + ' lainnya</li>' : '')
                + '</ul></div>';
        }
        return msg;
    }

    function simpanSemua() {
        var tanggal = document.getElementById('inputTanggal')?.value;
        if (!tanggal) { Swal.fire('Perhatian', 'Silakan pilih tanggal absensi.', 'warning'); return; }

        if (isHariAktif(tanggal)) {
            var violated = 0;
            $('#tabelGuru tbody tr[data-user-id].row-edited').each(function() {
                var st = $(this).find('.batch-status').val();
                var jm = $(this).find('.jam-masuk').val();
                var jp = $(this).find('.jam-pulang').val();
                if (st === 'HADIR' && jm && jp) violated++;
            });
            if (violated > 0) {
                Swal.fire('Tidak Diizinkan', violated + ' baris memiliki datang + pulang sekaligus. Pada hari aktif, kosongkan salah satunya.', 'warning');
                return;
            }
        }

        var entries = [];
        $('#tabelGuru tbody tr[data-user-id].row-edited').each(function() {
            var userId = $(this).data('user-id');
            if (!userId) return;
            entries.push({
                user_id:    userId,
                nama:       ($(this).find('.teacher-name').text() || '').trim(),
                status:     $(this).find('.batch-status').val(),
                jam_masuk:  $(this).find('.jam-masuk').val(),
                jam_pulang: $(this).find('.jam-pulang').val(),
                keterangan: $(this).find('.keterangan').val()
            });
        });

        if (entries.length === 0) {
            Swal.fire('Perhatian', 'Belum ada perubahan. Baris yang diubah akan ditandai biru.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Simpan ' + entries.length + ' Perubahan?',
            html: batchConfirmHtml(tanggal, entries, liburInfo(tanggal)),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Cek Lagi'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            var btn = document.getElementById('btnSimpanSemua');
            var originalText = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = spinTW() + ' Menyimpan…'; }

            var postData = { tanggal: tanggal, entries: entries };
            postData[getCsrfName()] = getCsrfToken();

            $.ajax({
                url: '<?= site_url("admin/presensi/batch-manual") ?>',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire({
                            icon: (res.errors && res.errors.length) ? 'warning' : 'success',
                            title: (res.errors && res.errors.length) ? 'Tersimpan dengan Catatan' : 'Berhasil!',
                            html: batchResultHtml(res, tanggal),
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        });
                        refreshBatchTable(tanggal);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', html: batchResultHtml(res, tanggal) });
                    }
                },
                error: function(xhr) {
                    if (isCsrfExpired(xhr)) { csrfExpiredSwal(); return; }
                    Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan presensi massal.', 'error');
                },
                complete: function() {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                }
            });
        });
    }

    /* -------------------------------------------------------
       filterBatchTable (alias)
    ------------------------------------------------------- */
    function filterBatchTable() { applyTableFilters(); }

    /* -------------------------------------------------------
       Expose to global scope (onclick attrs & external usage)
    ------------------------------------------------------- */
    window.switchMode           = switchMode;
    window.quickSetDate         = quickSetDate;
    window.onTanggalChange      = onTanggalChange;
    window.setTimeVal           = setTimeVal;
    window.onSingleStatusChange = onSingleStatusChange;
    window.loadTeacherStatus    = loadTeacherStatus;
    window.processSingleSave    = processSingleSave;
    window.deleteSingleAttendance   = deleteSingleAttendance;
    window.refreshBatchTable    = refreshBatchTable;
    window.toggleBatchRow       = toggleBatchRow;
    window.setAllBatchStatus    = setAllBatchStatus;
    window.setEmptyBatchHadir   = setEmptyBatchHadir;
    window.setPulangSajaBatch   = setPulangSajaBatch;
    window.resetAllBatchInputs  = resetAllBatchInputs;
    window.deleteBatchRowAttendance = deleteBatchRowAttendance;
    window.setBatchFilter       = setBatchFilter;
    window.filterBatchTable     = filterBatchTable;
    window.simpanSemua          = simpanSemua;

    /* -------------------------------------------------------
       DOM Ready — init
    ------------------------------------------------------- */
    $(document).ready(function() {

        /* Select2 */
        if ($.fn.select2) {
            $('#selectGuru').select2({
                placeholder: '— Ketik nama atau NIP guru —',
                allowClear: true,
                width: '100%'
            }).on('change', function() { loadTeacherStatus(); });
        } else {
            $('#selectGuru').on('change', loadTeacherStatus);
        }

        /* Date bar */
        $('#inputTanggal').on('change', onTanggalChange);
        $('#btnKemarin').on('click', function() { quickSetDate(-1); });
        $('#btnHariIni').on('click', function() { quickSetDate(0); });

        /* Mode switcher */
        $('#modeSwitcher').on('click', '[data-mode]', function() { switchMode($(this).data('mode')); });

        /* Single: status & jenis */
        $(document).on('change', 'input[name="singleStatus"]', onSingleStatusChange);
        $('#jenisAbsenSwitcher').on('click', '[data-jenis]', function() {
            if ($(this).prop('disabled')) return;
            setJenisAbsen($(this).data('jenis'));
        });

        /* Single: keterangan counter */
        $('#inputKeterangan').on('input', function() {
            var cc = document.getElementById('keteranganCounter');
            if (cc) cc.textContent = String($(this).val().length);
        });

        /* Single: save & delete */
        $('#btnSaveSingle').on('click', processSingleSave);
        $('#btnResetSingle').on('click', deleteSingleAttendance);

        /* Batch toolbar */
        $('#btnBatchAllHadir').on('click',   function() { setAllBatchStatus('HADIR', '', ''); });
        $('#btnBatchFillEmpty').on('click',  function() { setEmptyBatchHadir('', ''); });
        $('#btnBatchPulangSaja').on('click', function() { setPulangSajaBatch(''); });
        $('#btnBatchReset').on('click', resetAllBatchInputs);
        $('#btnSimpanSemua').on('click', simpanSemua);

        /* Search */
        var searchTimer = null;
        $('#searchTeacherInput').on('keyup input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(filterBatchTable, 180);
        });
        $('#btnClearSearch').on('click', function() {
            $('#searchTeacherInput').val('').trigger('input');
            $('#searchTeacherInput').focus();
        });

        /* Filter pills */
        $(document).on('click', '.filter-pill', function() { setBatchFilter($(this).data('filter'), this); });

        /* Batch table events */
        $('#tabelGuru').on('change', '.batch-status', function() { toggleBatchRow(this); });
        $('#tabelGuru').on('change input', '.jam-masuk, .jam-pulang, .keterangan', function() { markRowEdited(this); });
        $('#tabelGuru').on('click', '[data-del-batch]', function() { deleteBatchRowAttendance($(this).data('del-batch')); });

        /* Initial render */
        onTanggalChange();
        updateBatchStatistics();
    });
</script>
<?= $this->endSection() ?>
