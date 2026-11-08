<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Top App Bar */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Date Nav Chip (selaras tamu/kejadian/laporan) */
    .date-nav-chip {
        user-select: none;
        transition: transform 0.1s ease;
    }
    .date-nav-chip:active {
        transform: scale(0.99);
    }

    /* Sticky save bar — pill melayang di atas navigasi bawah */
    .save-bar {
        position: fixed;
        left: 50%;
        transform: translateX(-50%);
        bottom: calc(88px + var(--safe-area-inset-bottom, 0px) * 0.5);
        width: calc(100% - 24px);
        max-width: 456px;
        z-index: 2100;
        padding: 10px 12px;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(18px) saturate(180%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 18px 36px -18px rgba(15, 23, 42, 0.35), 0 2px 8px rgba(15, 23, 42, 0.06);
    }
    .save-bar > * + * { margin-top: 6px; }
    /* Kartu terakhir tetap terbaca di atas save bar */
    .app-container { padding-bottom: calc(200px + var(--safe-area-inset-bottom, 0px)); }

    /* Rapi: nilai input jam/time rata kiri di WebKit */
    .jam-input::-webkit-date-and-time-value { text-align: left; }

    /* Section head (selaras dashboard/laporan) */
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

    /* Student Card */
    .student-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .student-card:active {
        transform: scale(0.99);
    }
    /* Penanda kartu dengan perubahan jam yang belum disimpan
       (diletakkan sebelum .is-dirty agar status tetap menang bila keduanya aktif) */
    .student-card.is-jam-dirty {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 1px #38bdf8, 0 2px 8px rgba(56, 189, 248, 0.15);
    }
    /* Penanda kartu yang diubah (sebelum disimpan) */
    .student-card.is-dirty {
        border-color: #fbbf24 !important;
        box-shadow: 0 0 0 1px #fbbf24, 0 2px 8px rgba(251, 191, 36, 0.15);
    }
    /* Belum scan masuk: kartu merah agar langsung terlihat.
       (is-dirty di atas memakai !important sehingga status
       "belum disimpan" tetap menang bila keduanya aktif.) */
    .student-card[data-nomasuk="1"] {
        border-color: #fca5a5;
        background-color: #fef2f2;
        box-shadow: 0 0 0 1px #fecaca, 0 2px 8px rgba(239, 68, 68, 0.12);
    }

    /* Status Buttons (5 options) */
    .status-opt-btn {
        position: relative;
        transition: all 0.15s ease;
        user-select: none;
    }
    /* Dareah fokus keyboard: input sr-only, bukan display:none */
    .status-opt-btn input {
        position: absolute; width: 1px; height: 1px;
        opacity: 0; margin: 0; pointer-events: none;
    }
    .status-opt-btn:has(input:focus-visible) {
        outline: 2px solid #10b981; outline-offset: 1px;
    }

    .status-opt-btn:has(input[value="H"]:checked) {
        background-color: #ecfdf5;
        border-color: #10b981;
        color: #047857;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
    }
    .status-opt-btn:has(input[value="S"]:checked) {
        background-color: var(--primary-50);
        border-color: var(--primary-500);
        color: var(--primary-700);
        box-shadow: 0 2px 6px rgb(var(--primary-500-rgb) / 0.2);
    }
    .status-opt-btn:has(input[value="I"]:checked) {
        background-color: #fffbeb;
        border-color: #f59e0b;
        color: #b45309;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);
    }
    .status-opt-btn:has(input[value="A"]:checked) {
        background-color: #fef2f2;
        border-color: #ef4444;
        color: #b91c1c;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
    }
    .status-opt-btn:has(input[value="T"]:checked) {
        background-color: #f1f5f9;
        border-color: #64748b;
        color: #334155;
        box-shadow: 0 2px 6px rgba(100, 116, 139, 0.2);
    }
    .status-opt-btn:has(input[value="B"]:checked) {
        background-color: #f0fdfa;
        border-color: #14b8a6;
        color: #0f766e;
        box-shadow: 0 2px 6px rgba(20, 184, 166, 0.2);
    }

    /* Filter pills */
    .filter-pill { transition: all 0.15s ease; }
    .filter-pill.active {
        background-color: #059669; color: #fff; border-color: #059669;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
    }
    .filter-pill:focus-visible { outline: 2px solid #059669; outline-offset: 1px; }

    /* Empty state (selaras halaman piket lain) */
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

    @media (prefers-reduced-motion: reduce) {
        .student-card, .status-opt-btn, .date-nav-chip, .filter-pill { transition: none; }
        .pulse-attn { animation: none !important; }
    }

    /* Penarik perhatian tombol Simpan Jam sehabis isi massal */
    @keyframes attnPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.55); }
        50% { box-shadow: 0 0 0 9px rgba(79, 70, 229, 0); }
    }
    .pulse-attn { animation: attnPulse 1.2s ease-in-out infinite; }

    /* ── Lipatan kartu info (progressive disclosure) ── */
    details.piket-fold > summary { list-style: none; cursor: pointer; }
    details.piket-fold > summary::-webkit-details-marker { display: none; }
    details.piket-fold > summary .fold-chev { transition: transform 0.2s ease; }
    details.piket-fold[open] > summary .fold-chev { transform: rotate(90deg); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
    $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
    $nextDisabled = ($nextDate > date('Y-m-d'));
    $indoDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $dayName = $indoDays[(int)date('N', strtotime($tanggal))] ?? '';
    $totalSiswa = count($siswa_absensi ?? []);
    $kelasNama = '';
    foreach (($kelas_list ?? []) as $k) {
        if ((int)($kelas_selected ?? 0) === (int)$k->id_kelas) { $kelasNama = $k->nama_kelas; break; }
    }
?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100">
    <div class="flex items-center gap-2.5 min-w-0">
        <a href="<?= site_url('guru/piket') ?>" aria-label="Kembali ke Dashboard Piket" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 flex-shrink-0" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div class="min-w-0">
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight truncate">Absensi Siswa</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none truncate"><?= $kelasNama !== '' ? 'Kelas ' . esc($kelasNama) : 'Kehadiran Siswa per Kelas' ?></p>
        </div>
    </div>
    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100 flex-shrink-0">
        <i class="bi bi-people-fill text-xs"></i> Siswa
    </span>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-28 space-y-2.5 mt-2.5">

    <!-- ── Date Navigation Chip (selaras halaman piket lain) ── -->
    <div class="date-nav-chip flex items-center justify-between bg-white border border-slate-200/80 rounded-2xl px-3 py-2 shadow-xs">
        <a href="?tanggal=<?= $prevDate ?><?= $kelas_selected ? '&kelas='.(int)$kelas_selected : '' ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Sebelumnya" aria-label="Hari sebelumnya">
            <i class="bi bi-chevron-left text-xs font-bold"></i>
        </a>

        <div class="flex items-center gap-2.5 cursor-pointer relative px-2 py-1 rounded-xl hover:bg-slate-50 transition-colors"
             onclick="document.getElementById('headerDatePicker').showPicker ? document.getElementById('headerDatePicker').showPicker() : document.getElementById('headerDatePicker').focus()">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100/80">
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
                <?php if ($kelas_selected): ?><input type="hidden" name="kelas" value="<?= (int)$kelas_selected ?>"><?php endif; ?>
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
        <a href="?tanggal=<?= $nextDate ?><?= $kelas_selected ? '&kelas='.(int)$kelas_selected : '' ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Berikutnya" aria-label="Hari berikutnya">
            <i class="bi bi-chevron-right text-xs font-bold"></i>
        </a>
        <?php endif; ?>
    </div>

    <!-- ── Filter Kelas & Cari ──────────────────────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs space-y-2.5">
        <form method="get" id="filterForm" class="space-y-2.5 m-0">
            <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
            <div class="space-y-1">
                <label for="filterKelas" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kelas</label>
                <select name="kelas" id="filterKelas" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold focus:bg-white focus:outline-none focus:border-emerald-500"
                        onchange="document.getElementById('filterForm').submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach (($kelas_list ?? []) as $k): ?>
                        <option value="<?= (int)$k->id_kelas ?>" <?= ((int)($kelas_selected ?? 0) === (int)$k->id_kelas) ? 'selected' : '' ?>>
                            <?= esc($k->nama_kelas) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <div class="relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="search" id="searchSiswaInput" placeholder="<?= $kelas_selected ? 'Cari nama atau NIS siswa...' : 'Pilih kelas dulu untuk mencari...' ?>"
                   aria-label="Cari nama atau NIS siswa" <?= $kelas_selected ? '' : 'disabled' ?>
                   class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500 transition-colors disabled:opacity-50">
        </div>
    </div>

    <?php if ($kelas_selected && !empty($siswa_absensi)): ?>
        <!-- ── Ringkasan & Aksi ─────────────────────────── -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-2.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-extrabold text-slate-700">
                    <span class="text-emerald-700"><?= $totalSiswa ?></span> siswa
                </span>
                <button type="button" onclick="setAllPresent()"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-[11px] font-bold border border-emerald-200 transition-colors cursor-pointer flex-shrink-0">
                    <i class="bi bi-check-all text-xs"></i> Semua Hadir
                </button>
            </div>
            <!-- Isi jam massal (jarang dipakai → dilipat; hanya yang belum ada jamnya & bukan scan) -->
            <details class="piket-fold rounded-xl bg-slate-50/70 border border-slate-100">
                <summary class="flex items-center justify-center gap-1.5 px-2 py-1.5 text-[10px] font-extrabold text-slate-500">
                    <i class="bi bi-clock-history"></i> Isi jam massal
                    <i class="bi bi-chevron-right text-[9px] fold-chev"></i>
                </summary>
                <div class="grid grid-cols-2 gap-1.5 px-2 pb-2">
                    <button type="button" onclick="isiJamMassal('masuk')"
                            class="inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-[11px] font-bold border border-indigo-200 transition-colors cursor-pointer">
                        <i class="bi bi-box-arrow-in-right text-xs"></i> Masuk Massal
                    </button>
                    <button type="button" onclick="isiJamMassal('pulang')"
                            class="inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 text-[11px] font-bold border border-sky-200 transition-colors cursor-pointer">
                        <i class="bi bi-box-arrow-right text-xs"></i> Pulang Massal
                    </button>
                </div>
            </details>

            <!-- 5 Status Counters -->
            <div class="grid grid-cols-6 gap-1.5 text-center">
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-1.5">
                    <div id="cH" class="text-sm font-extrabold text-emerald-700 leading-none">0</div>
                    <div class="text-[9px] font-bold text-emerald-600 uppercase mt-0.5">Hadir</div>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-1.5">
                    <div id="cS" class="text-sm font-extrabold text-blue-700 leading-none">0</div>
                    <div class="text-[9px] font-bold text-blue-600 uppercase mt-0.5">Sakit</div>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-1.5">
                    <div id="cI" class="text-sm font-extrabold text-amber-700 leading-none">0</div>
                    <div class="text-[9px] font-bold text-amber-600 uppercase mt-0.5">Izin</div>
                </div>
                <div class="bg-rose-50 border border-rose-100 rounded-xl p-1.5">
                    <div id="cA" class="text-sm font-extrabold text-rose-700 leading-none">0</div>
                    <div class="text-[9px] font-bold text-rose-600 uppercase mt-0.5">Alpha</div>
                </div>
                <div class="bg-slate-100 border border-slate-200 rounded-xl p-1.5">
                    <div id="cT" class="text-sm font-extrabold text-slate-700 leading-none">0</div>
                    <div class="text-[9px] font-bold text-slate-600 uppercase mt-0.5">Telat</div>
                </div>
                <div class="bg-teal-50 border border-teal-100 rounded-xl p-1.5">
                    <div id="cB" class="text-sm font-extrabold text-teal-700 leading-none">0</div>
                    <div class="text-[9px] font-bold text-teal-600 uppercase mt-0.5">BDR</div>
                </div>
            </div>

            <!-- Filter cepat -->
            <div class="flex gap-1.5 overflow-x-auto no-scrollbar pt-0.5" role="group" aria-label="Filter cepat daftar siswa">
                <button type="button" onclick="setPill('semua', this)" class="filter-pill active px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-[11px] font-extrabold whitespace-nowrap bg-white cursor-pointer">Semua</button>
                <button type="button" onclick="setPill('konflik', this)" class="filter-pill px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-[11px] font-extrabold whitespace-nowrap bg-white cursor-pointer">Beda data</button>
                <button type="button" onclick="setPill('belum', this)" class="filter-pill px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-[11px] font-extrabold whitespace-nowrap bg-white cursor-pointer">Belum masuk</button>
                <button type="button" onclick="setPill('sia', this)" class="filter-pill px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-[11px] font-extrabold whitespace-nowrap bg-white cursor-pointer">S / I / A</button>
            </div>
        </div>

        <!-- ── Kehadiran Real (dilipat; ringkas di judul) ─── -->
        <?php $rr = $real_ringkasan ?? null; ?>
        <details class="piket-fold bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
            <summary class="flex items-center gap-1.5 px-3 py-2.5">
                <i class="bi bi-qr-code-scan text-emerald-600 text-xs"></i>
                <span class="text-[11px] font-extrabold text-slate-700">Kehadiran Real</span>
                <?php if ($rr): ?>
                <span class="text-[10px] font-bold text-slate-500">Masuk <?= (int)$rr->masuk ?>/<?= (int)$rr->total ?></span>
                <?php if ((int)$rr->konflik > 0): ?>
                <span class="px-1.5 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 text-[9px] font-extrabold">Beda <?= (int)$rr->konflik ?></span>
                <?php endif; ?>
                <?php endif; ?>
                <i class="bi bi-chevron-right text-[10px] text-slate-400 ml-auto fold-chev"></i>
            </summary>
            <div class="px-3 pb-3 space-y-2">
            <?php if ($rr):
                $rrPct = ((int)$rr->total > 0) ? round(((int)$rr->masuk / (int)$rr->total) * 100) : 0;
            ?>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden" role="progressbar"
                     aria-valuenow="<?= $rrPct ?>" aria-valuemin="0" aria-valuemax="100"
                     aria-label="Siswa sudah masuk <?= $rrPct ?> persen">
                    <div class="h-full rounded-full bg-emerald-500" style="width: <?= $rrPct ?>%"></div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap text-[10px] font-extrabold">
                    <span class="px-1.5 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">Pulang <?= (int)$rr->pulang ?></span>
                    <span class="px-1.5 py-0.5 rounded-md bg-orange-50 text-orange-700 border border-orange-200">Telat <?= (int)($rr->telat ?? 0) ?></span>
                </div>
            <?php else: ?>
                <p class="text-[11px] text-slate-400 m-0">Data masuk belum tersedia.</p>
            <?php endif; ?>
                <p class="text-[10px] text-slate-400 m-0 leading-relaxed">
                    Jam di kartu = hasil scan gerbang (terkunci). Badge merah = isian piket beda dengan data masuk. Telat → T dan tanpa scan → A otomatis; isian manual tak ditimpa.
                </p>
            </div>
        </details>

        <!-- ── Tanpa Absen Pulang / Bolos ─────────────────── -->
        <?php if (!empty($tanpa_pulang ?? [])): ?>
            <?php $isBolos = !empty($bolos_resmi); ?>
            <div class="<?= $isBolos ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-200' ?> border rounded-2xl p-3 shadow-xs space-y-1.5">
                <div class="flex items-center gap-1.5 text-[11px] font-extrabold <?= $isBolos ? 'text-rose-800' : 'text-slate-700' ?>">
                    <i class="bi <?= $isBolos ? 'bi-exclamation-octagon-fill' : 'bi-hourglass-split' ?>"></i>
                    <?= $isBolos ? 'Bolos — masuk tanpa absen pulang (' . count($tanpa_pulang) . ')' : 'Belum pulang (' . count($tanpa_pulang) . ')' ?>
                </div>
                <div class="space-y-1">
                    <?php foreach (array_slice($tanpa_pulang, 0, 5) as $bp): ?>
                        <div class="flex items-center justify-between gap-2 text-[11px] bg-white/70 rounded-lg px-2 py-1">
                            <span class="font-bold text-slate-800 truncate"><?= esc($bp->nama ?? '-') ?></span>
                            <span class="font-semibold text-slate-500 flex-shrink-0">masuk <?= esc(substr((string)($bp->jam_masuk ?? ''), 0, 5)) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($tanpa_pulang) > 5): ?>
                        <details>
                            <summary class="text-[10px] text-slate-500 font-bold cursor-pointer text-center list-none hover:text-slate-700">+<?= count($tanpa_pulang) - 5 ?> lainnya — tampilkan</summary>
                            <div class="space-y-1 mt-1">
                                <?php foreach (array_slice($tanpa_pulang, 5, 15) as $bp): ?>
                                    <div class="flex items-center justify-between gap-2 text-[11px] bg-white/70 rounded-lg px-2 py-1">
                                        <span class="font-bold text-slate-800 truncate"><?= esc($bp->nama ?? '-') ?></span>
                                        <span class="font-semibold text-slate-500 flex-shrink-0">masuk <?= esc(substr((string)($bp->jam_masuk ?? ''), 0, 5)) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($tanpa_pulang) > 20): ?>
                                    <div class="text-[10px] text-slate-500 font-semibold text-center">+<?= count($tanpa_pulang) - 20 ?> lainnya</div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── Daftar Siswa ─────────────────────────────── -->
        <div>
            <div class="section-head">
                <div class="section-head-title">
                    <span class="section-head-icon bg-emerald-50 text-emerald-600"><i class="bi bi-list-check"></i></span>
                    <h2>Daftar Siswa</h2>
                </div>
                <span id="visibleCount" class="text-[10px] font-bold text-slate-500"><?= $totalSiswa ?> siswa</span>
            </div>
        <form id="formAbsensi" class="space-y-2">
            <?= csrf_field() ?>
            <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
            <input type="hidden" name="id_kelas" value="<?= esc($kelas_selected) ?>">

            <?php foreach ($siswa_absensi as $i => $s):
                $searchStr = esc(strtolower(($s->nama ?? '') . ' ' . ($s->nis ?? '')));
                $hasReal = !empty($s->real_masuk) || !empty($s->real_status);
                $jmMasuk = !empty($s->real_masuk) ? substr((string)$s->real_masuk, 0, 5) : '';
                $jmPulang = !empty($s->real_pulang) ? substr((string)$s->real_pulang, 0, 5) : '';
                $lockMasuk = $jmMasuk !== '' && !in_array($s->real_sumber_masuk ?? null, [null, '', 'piket'], true);
                $lockPulang = $jmPulang !== '' && !in_array($s->real_sumber_keluar ?? null, [null, '', 'piket'], true);
            ?>
                <div class="student-card bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs space-y-2"
                     data-id-siswa="<?= (int)$s->id_siswa ?>"
                     data-fstatus="<?= esc(strtoupper($s->real_status ?? '')) ?>"
                     data-search="<?= $searchStr ?>"
                     data-status="<?= esc($s->status ?? 'H') ?>"
                     data-konflik="<?= !empty($s->real_konflik) ? '1' : '0' ?>"
                     data-nomasuk="<?= $hasReal ? '0' : '1' ?>">

                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-extrabold flex items-center justify-center flex-shrink-0">
                            <?= $i + 1 ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="font-extrabold text-xs text-slate-900 truncate leading-tight">
                                <?= esc($s->nama) ?> <span class="font-semibold text-slate-400">• <?= esc($s->nis ?: '-') ?></span>
                            </div>
                            <?php if ($hasReal): ?>
                                <div class="mt-0.5 text-[10px] font-semibold text-slate-500 truncate">
                                    <span class="text-emerald-700 font-bold"><?= esc($s->real_masuk ?? '--:--') ?></span><?php if ($jmPulang !== ''): ?><span class="text-slate-300"> → </span><span class="text-blue-700 font-bold"><?= esc($jmPulang) ?></span><?php endif; ?><?php if (!empty($s->real_telat) && (int)$s->real_telat > 0): ?><span class="ml-1 px-1 py-px rounded bg-orange-50 text-orange-700 border border-orange-200 text-[9px] font-extrabold">Telat <?= (int)$s->real_telat ?> mnt</span><?php endif; ?><?php if (!empty($s->real_konflik)): ?><span class="ml-1 px-1 py-px rounded bg-rose-50 text-rose-700 border border-rose-200 text-[9px] font-extrabold" title="Isian piket (<?= esc($s->status) ?>) beda dengan data masuk. Cek di Rekap → selaraskan bila perlu.">Beda data</span><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-0.5 truncate"><span class="px-1.5 py-px rounded bg-red-600 text-white text-[9px] font-extrabold">Belum scan masuk</span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status kehadiran -->
                    <div class="grid grid-cols-6 gap-1" role="radiogroup" aria-label="Status <?= esc($s->nama) ?>">
                        <?php
                            $statuses = [
                                'H' => 'Hadir',
                                'S' => 'Sakit',
                                'I' => 'Izin',
                                'A' => 'Alpha',
                                'T' => 'Telat',
                                'B' => 'BDR'
                            ];
                            foreach ($statuses as $val => $lbl):
                        ?>
                            <label class="status-opt-btn flex flex-col items-center justify-center py-2 px-0.5 rounded-xl border border-slate-200 bg-white text-slate-600 font-extrabold text-[10px] cursor-pointer">
                                <input type="radio" name="absensi[<?= (int)$s->id_siswa ?>]" value="<?= $val ?>" <?= (($s->status ?? 'H') == $val) ? 'checked' : '' ?>>
                                <span><?= $lbl ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Jam manual (scan terkunci, tak bisa diubah di sini) -->
                    <div class="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase flex-shrink-0">Masuk
                                <?php if ($lockMasuk): ?><i class="bi bi-lock-fill text-slate-300" title="Sudah scan — tidak bisa diubah manual"></i><?php endif; ?>
                            </span>
                            <input type="time" value="<?= esc($jmMasuk) ?>" data-orig="<?= esc($jmMasuk) ?>" data-sisi="masuk"
                                   <?= $lockMasuk ? 'disabled title="Sudah scan — tidak bisa diubah manual"' : '' ?>
                                   aria-label="Jam masuk manual <?= esc($s->nama) ?>"
                                   class="jam-input w-full px-2 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold focus:bg-white focus:outline-none focus:border-emerald-500 disabled:opacity-50">
                        </label>
                        <label class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase flex-shrink-0">Pulang
                                <?php if ($lockPulang): ?><i class="bi bi-lock-fill text-slate-300" title="Sudah scan — tidak bisa diubah manual"></i><?php endif; ?>
                            </span>
                            <input type="time" value="<?= esc($jmPulang) ?>" data-orig="<?= esc($jmPulang) ?>" data-sisi="pulang"
                                   <?= $lockPulang ? 'disabled title="Sudah scan — tidak bisa diubah manual"' : '' ?>
                                   aria-label="Jam pulang manual <?= esc($s->nama) ?>"
                                   class="jam-input w-full px-2 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold focus:bg-white focus:outline-none focus:border-emerald-500 disabled:opacity-50">
                        </label>
                    </div>

                </div>
            <?php endforeach; ?>
        </form>
        <p id="filterEmptyHint" class="hidden text-xs text-slate-400 text-center py-6 m-0">Tidak ada siswa yang cocok dengan filter.</p>
        </div>

    <?php elseif ($kelas_selected): ?>
        <div class="empty-box">
            <div class="empty-box-icon bg-amber-50 text-amber-500"><i class="bi bi-people"></i></div>
            <p class="font-bold text-slate-600 text-[13px] m-0">Tidak Ada Siswa</p>
            <p class="text-xs text-slate-400 mt-1 m-0">Belum ada siswa yang terdaftar pada kelas ini.</p>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <div class="empty-box-icon bg-emerald-50 text-emerald-600"><i class="bi bi-ui-checks-grid"></i></div>
            <p class="font-bold text-slate-600 text-[13px] m-0">Pilih Kelas Terlebih Dahulu</p>
            <p class="text-xs text-slate-400 mt-1 m-0">Silakan pilih kelas pada dropdown di atas untuk memulai pengisian absensi siswa.</p>
        </div>
    <?php endif; ?>

</div>

<!-- ── Sticky Bottom Save Bar ───────────────────────────── -->
<?php if ($kelas_selected && !empty($siswa_absensi)): ?>
<div class="save-bar">
    <p id="saveBreakdown" class="text-center text-[11px] font-bold text-slate-500 m-0">Semua perubahan tersimpan</p>
    <button type="button" id="btnSaveAll"
            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-extrabold shadow-md flex items-center justify-center gap-2 transition-all border-none cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600 disabled:active:scale-100">
        <i class="bi bi-check2-circle text-base"></i><span id="btnSaveAllLabel">Simpan Perubahan</span>
    </button>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const BASE = '<?= base_url() ?>';
    const SAVE_LABEL_DEFAULT = 'Simpan Perubahan';

    // Kembali ke posisi scroll semula sehabis muat ulang pasca-simpan,
    // agar operator langsung melihat kartu yang baru disimpan.
    try {
        const sy = sessionStorage.getItem('absensiScroll');
        if (sy !== null) {
            sessionStorage.removeItem('absensiScroll');
            window.scrollTo(0, parseInt(sy, 10) || 0);
        }
    } catch (e) {}

    function haptic(ms = 10) {
        if (window.navigator.vibrate) window.navigator.vibrate(ms);
    }

    // ── Live Counters ─────────────────────────────────────
    function updateCounter() {
        const counts = { H: 0, S: 0, I: 0, A: 0, T: 0, B: 0 };
        document.querySelectorAll('.status-opt-btn input:checked').forEach(r => {
            if (counts.hasOwnProperty(r.value)) counts[r.value]++;
        });
        ['H', 'S', 'I', 'A', 'T', 'B'].forEach(k => {
            const el = document.getElementById('c' + k);
            if (el) el.textContent = counts[k];
        });
    }

    // ── Label + rincian tombol simpan gabungan ────────
    function updateSaveAll() {
        const nSt = document.querySelectorAll('.student-card.is-dirty').length;
        const nJam = document.querySelectorAll('.student-card.is-jam-dirty').length;
        const label = document.getElementById('btnSaveAllLabel');
        const sub = document.getElementById('saveBreakdown');
        const total = nSt + nJam;
        if (label) label.textContent = total > 0 ? 'Simpan Perubahan (' + total + ')' : SAVE_LABEL_DEFAULT;
        if (sub) {
            if (total === 0) sub.textContent = 'Semua perubahan tersimpan';
            else {
                const parts = [];
                if (nSt > 0) parts.push(nSt + ' status');
                if (nJam > 0) parts.push(nJam + ' jam');
                sub.textContent = 'Belum disimpan: ' + parts.join(' • ');
            }
        }
        if (total === 0) document.getElementById('btnSaveAll')?.classList.remove('pulse-attn');
        const btn = document.getElementById('btnSaveAll');
        if (btn) btn.disabled = total === 0;
    }
    function updateSaveBtn() { updateSaveAll(); }

    // ── Filter gabungan: pencarian + pill ─────────────────
    let activePill = 'semua';
    window.setPill = function(p, btn) {
        activePill = p;
        document.querySelectorAll('.filter-pill').forEach(el => el.classList.remove('active'));
        if (btn) btn.classList.add('active');
        applyCardFilter();
    };
    function applyCardFilter() {
        const searchEl = document.getElementById('searchSiswaInput');
        const q = searchEl ? searchEl.value.toLowerCase().trim() : '';
        let visible = 0;
        document.querySelectorAll('.student-card').forEach(card => {
            const search = card.getAttribute('data-search') || '';
            const okQ = !q || search.includes(q);
            let okP = true;
            if (activePill === 'konflik') okP = card.dataset.konflik === '1';
            else if (activePill === 'belum') okP = card.dataset.nomasuk === '1';
            else if (activePill === 'sia') okP = ['S', 'I', 'A'].includes(card.dataset.status || '');
            const show = okQ && okP;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const hint = document.getElementById('filterEmptyHint');
        if (hint) hint.classList.toggle('hidden', visible > 0);
        const vc = document.getElementById('visibleCount');
        if (vc) vc.textContent = visible + ' dari <?= $totalSiswa ?> tampil';
    }

    // ── Lacak perubahan (hanya yang diubah yang dikirim) ──
    function refreshCardState(card) {
        const cur = card.querySelector('.status-opt-btn input:checked');
        const curVal = cur ? cur.value : '';
        card.dataset.status = curVal;
        card.classList.toggle('is-dirty', curVal !== card.dataset.orig);
    }
    document.querySelectorAll('.student-card').forEach(card => {
        const checked = card.querySelector('.status-opt-btn input:checked');
        card.dataset.orig = checked ? checked.value : '';
        if (!card.dataset.status) card.dataset.status = card.dataset.orig;
    });
    document.querySelectorAll('.status-opt-btn input').forEach(r => {
        r.addEventListener('change', function() {
            haptic(8);
            const card = this.closest('.student-card');
            if (card) refreshCardState(card);
            updateCounter();
            updateSaveBtn();
        });
    });
    updateCounter();
    updateSaveBtn();

    // ── Jam manual: lacak perubahan per input ─────────
    function refreshJamState(card) {
        let dirty = false;
        card.querySelectorAll('.jam-input').forEach(inp => {
            if (!inp.disabled && inp.value !== '' && inp.value !== inp.dataset.orig) dirty = true;
        });
        card.classList.toggle('is-jam-dirty', dirty);
    }
    function updateSaveJamBtn() { updateSaveAll(); }
    document.querySelectorAll('.jam-input').forEach(inp => {
        inp.addEventListener('change', function() {
            haptic(8);
            const card = this.closest('.student-card');
            if (card) refreshJamState(card);
            updateSaveJamBtn();
        });
    });
    updateSaveJamBtn();

    // ── Isi jam massal (hanya input kosong yang aktif & tampil) ──
    window.isiJamMassal = async function(sisi) {
        const label = sisi === 'masuk' ? 'Jam Masuk' : 'Jam Pulang';
        const now = new Date();
        const def = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        let jam = '';
        if (window.Swal) {
            const res = await Swal.fire({
                title: label + ' Massal',
                html: '<input type="time" id="bulkJamInput" class="swal2-input" value="' + def + '">'
                    + '<p style="font-size:11px;color:#64748b;margin:8px 0 0">'
                    + (sisi === 'pulang'
                        ? 'Hanya untuk yang sudah scan masuk tapi lupa scan pulang, pada daftar yang tampil.'
                        : 'Hanya mengisi yang masih kosong dan bukan hasil scan, pada daftar yang tampil.')
                    + '</p>',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Isi',
                cancelButtonText: 'Batal',
                preConfirm: () => (document.getElementById('bulkJamInput') || {}).value || ''
            });
            if (!res.isConfirmed || !res.value) return;
            jam = res.value;
        } else {
            jam = prompt(label + ' massal (HH:MM):', def) || '';
            if (jam.trim() === '') return;
        }
        if (!/^\d{2}:\d{2}(:\d{2})?$/.test(jam)) {
            if (window.Swal) Swal.fire({ title: 'Format salah', text: 'Gunakan format HH:MM.', icon: 'error' });
            else alert('Format jam salah (HH:MM)');
            return;
        }
        jam = jam.slice(0, 5);
        let n = 0;
        let skipNoMasuk = 0;
        let skipHidden = 0;
        let skipFormal = 0;
        document.querySelectorAll('.student-card').forEach(card => {
            if (card.style.display === 'none') { skipHidden++; return; }
            const inp = card.querySelector('.jam-input[data-sisi="' + sisi + '"]');
            if (!inp || inp.disabled || inp.value !== '') return;
            // Cuti/sakit formal tak bisa diisi jam masuk (server juga menolak).
            if (sisi === 'masuk') {
                const fs = (card.dataset.fstatus || '').toUpperCase();
                if (fs === 'SAKIT' || fs === 'IZIN') { skipFormal++; return; }
            }
            // Pulang massal hanya untuk yang SUDAH SCAN MASUK tapi lupa scan
            // pulang: input masuk yang terkunci = milik scan. Jam manual
            // (milik piket) sengaja dilewati agar tak tertimpa massal.
            if (sisi === 'pulang') {
                const mInp = card.querySelector('.jam-input[data-sisi="masuk"]');
                if (!mInp || mInp.value === '' || !mInp.disabled) { skipNoMasuk++; return; }
            }
            inp.value = jam;
            refreshJamState(card);
            n++;
        });
        updateSaveJamBtn();
        haptic(15);
        if (n > 0) document.getElementById('btnSaveAll')?.classList.add('pulse-attn');
        if (window.Swal) {
            if (n > 0) {
                let info = n + ' siswa diisi ' + jam;
                const skipInfo = [];
                if (skipNoMasuk > 0) skipInfo.push(skipNoMasuk + ' bukan scan masuk / belum ada jam masuk');
                if (skipFormal > 0) skipInfo.push(skipFormal + ' cuti/sakit formal');
                if (skipInfo.length > 0) info += ' (' + skipInfo.join('; ') + ' dilewati)';
                Swal.fire({ title: info, text: 'Belum tersimpan — tekan SIMPAN PERUBAHAN di bawah untuk menyimpan.', icon: 'success' });
            } else if (skipHidden > 0) {
                Swal.fire({ title: 'Tidak ada yang diisi', text: skipHidden + ' siswa tersembunyi oleh pencarian/filter. Kosongkan pencarian dan pilih filter "Semua", lalu coba lagi.', icon: 'info' });
            } else if (skipFormal > 0) {
                Swal.fire({ title: 'Tidak ada yang diisi', text: skipFormal + ' siswa berstatus cuti/sakit formal sehingga tak bisa diisi jam masuk.', icon: 'info' });
            } else {
                Swal.fire({ title: 'Tidak ada yang diisi', text: sisi === 'pulang'
                    ? 'Tidak ada siswa yang sudah scan masuk dan belum pulang di kelas ini.'
                    : 'Semua kolom sudah terisi atau terkunci (hasil scan).', icon: 'info' });
            }
        }
    };

    // ── Search Siswa ──────────────────────────────────────
    document.getElementById('searchSiswaInput')?.addEventListener('input', applyCardFilter);

    // ── Set Semua Hadir (hanya yang tampil di filter) ────
    window.setAllPresent = function() {
        document.querySelectorAll('.student-card').forEach(card => {
            if (card.style.display === 'none') return; // lewati yang terfilter
            const radioH = card.querySelector('input[value="H"]');
            if (radioH && !radioH.checked) {
                radioH.checked = true;
                refreshCardState(card);
            }
        });
        updateCounter();
        updateSaveBtn();
        haptic(15);
    };

    // ── Pengirim status (H/S/I/A/T) ───────────────────────
    async function postStatus(cards) {
        const form = document.getElementById('formAbsensi');
        const formData = new FormData();
        formData.append('tanggal', form.querySelector('input[name="tanggal"]').value);
        formData.append('id_kelas', form.querySelector('input[name="id_kelas"]').value);
        // Token CSRF + hanya slot yang berubah (hemat & cegah timpa massal)
        form.querySelectorAll('input[type="hidden"]').forEach(el => {
            if (el.name !== 'tanggal' && el.name !== 'id_kelas') formData.append(el.name, el.value);
        });
        cards.forEach(card => {
            const checked = card.querySelector('.status-opt-btn input:checked');
            if (!checked) return;
            const m = checked.name.match(/^absensi\[(\d+)\]$/);
            if (m) formData.append('absensi[' + m[1] + ']', checked.value);
        });

        try {
            const res = await fetch(BASE + 'guru/piket/save_absensi', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let json = null;
            try { json = await res.json(); } catch (e) { json = null; }
            if (!res.ok) {
                return { ok: false, msg: (json && json.message) || (res.status === 403 ? 'Akses ditolak / sesi berakhir. Muat ulang halaman.' : 'Server menolak (HTTP ' + res.status + ')') };
            }
            if (json && json.status) {
                cards.forEach(card => {
                    const cur = card.querySelector('.status-opt-btn input:checked');
                    card.dataset.orig = cur ? cur.value : '';
                    card.classList.remove('is-dirty');
                });
                return { ok: true, msg: json.message || 'Data absensi berhasil disimpan' };
            }
            return { ok: false, msg: (json && json.message) || 'Gagal menyimpan absensi' };
        } catch (err) {
            return { ok: false, msg: 'Gagal menghubungkan ke server' };
        }
    }

    // ── Pengirim jam manual ───────────────────────────────────
    async function postJam(cards) {
        const form = document.getElementById('formAbsensi');
        const formData = new FormData();
        formData.append('tanggal', form.querySelector('input[name="tanggal"]').value);
        formData.append('id_kelas', form.querySelector('input[name="id_kelas"]').value);
        form.querySelectorAll('input[type="hidden"]').forEach(el => {
            if (el.name !== 'tanggal' && el.name !== 'id_kelas') formData.append(el.name, el.value);
        });
        cards.forEach(card => {
            const id = card.dataset.idSiswa;
            if (!id) return;
            card.querySelectorAll('.jam-input').forEach(inp => {
                if (!inp.disabled && inp.value !== '' && inp.value !== inp.dataset.orig) {
                    formData.append('jam[' + id + '][' + inp.dataset.sisi + ']', inp.value);
                }
            });
        });

        try {
            const res = await fetch(BASE + 'guru/piket/save_jam', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let json = null;
            try { json = await res.json(); } catch (e) { json = null; }
            if (!res.ok) {
                return { ok: false, msg: (json && json.message) || (res.status === 403 ? 'Akses ditolak / sesi berakhir. Muat ulang halaman.' : 'Server menolak (HTTP ' + res.status + ')') };
            }
            if (json && json.status) {
                cards.forEach(card => {
                    card.querySelectorAll('.jam-input').forEach(inp => { inp.dataset.orig = inp.value; });
                    card.classList.remove('is-jam-dirty');
                });
                return { ok: true, msg: json.message || 'Jam berhasil disimpan' };
            }
            return { ok: false, msg: (json && json.message) || 'Gagal menyimpan jam' };
        } catch (err) {
            return { ok: false, msg: 'Gagal menghubungkan ke server' };
        }
    }

    // ── Satu tombol untuk semua perubahan ─────────────────
    const btnSaveAll = document.getElementById('btnSaveAll');
    if (btnSaveAll) {
        btnSaveAll.addEventListener('click', async function() {
            const stCards = Array.from(document.querySelectorAll('.student-card.is-dirty'));
            const jamCards = Array.from(document.querySelectorAll('.student-card.is-jam-dirty'));
            if (stCards.length === 0 && jamCards.length === 0) {
                if (window.Swal) Swal.fire({ title: 'Tidak ada perubahan', text: 'Ubah status atau jam minimal satu siswa sebelum menyimpan.', icon: 'info', timer: 1500, showConfirmButton: false });
                else alert('Tidak ada perubahan untuk disimpan');
                return;
            }

            const parts = [];
            if (stCards.length > 0) parts.push(stCards.length + ' status kehadiran');
            if (jamCards.length > 0) parts.push(jamCards.length + ' jam masuk/pulang');
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Simpan Perubahan?',
                    text: parts.join(' + ') + ' akan disimpan ke sistem.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal'
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm('Simpan ' + parts.join(' + ') + '?')) return;
            }

            btnSaveAll.disabled = true;
            btnSaveAll.innerHTML = '<span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span><span>Menyimpan...</span>';

            let rSt = { ok: true, msg: '' };
            let rJam = { ok: true, msg: '' };
            if (stCards.length > 0) rSt = await postStatus(stCards);
            if (jamCards.length > 0) rJam = await postJam(jamCards);

            updateCounter();
            updateSaveAll();
            btnSaveAll.disabled = false;
            btnSaveAll.innerHTML = '<i class="bi bi-check2-circle text-base"></i><span id="btnSaveAllLabel">Simpan Perubahan</span>';
            updateSaveAll();

            const berhasil = [];
            if (stCards.length > 0 && rSt.ok) berhasil.push(rSt.msg);
            if (jamCards.length > 0 && rJam.ok) berhasil.push(rJam.msg);
            const gagal = [];
            if (stCards.length > 0 && !rSt.ok) gagal.push('Status — ' + rSt.msg);
            if (jamCards.length > 0 && !rJam.ok) gagal.push('Jam — ' + rJam.msg);

            if (gagal.length === 0) {
                haptic(15);
                if (window.Swal) {
                    await Swal.fire({
                        title: 'Semua Tersimpan!',
                        text: berhasil.join(' • ') || 'Perubahan berhasil disimpan',
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    alert(berhasil.join('\n') || 'Tersimpan');
                }
                try { sessionStorage.setItem('absensiScroll', String(window.scrollY)); } catch (e) {}
                window.location.reload();
            } else if (berhasil.length > 0) {
                if (window.Swal) Swal.fire({ title: 'Sebagian Tersimpan', text: berhasil.join(' • ') + ' | Gagal: ' + gagal.join(' • '), icon: 'warning' });
                else alert('Sebagian tersimpan. Gagal: ' + gagal.join(' • '));
            } else {
                const msg = gagal.join(' • ') || 'Gagal menyimpan';
                if (window.Swal) Swal.fire({ title: 'Gagal', text: msg, icon: 'error' });
                else alert(msg);
            }
        });
    }

});
</script>
<?= $this->endSection() ?>
