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

    /* Floating Toast */
    .save-toast-tw {
        transform: translate(-50%, -40px);
        opacity: 0;
        transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }
    .save-toast-tw.show {
        transform: translate(-50%, 0);
        opacity: 1;
    }

    /* Slot Item State */
    .slot-item {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .slot-item.is-saved {
        border-left: 3.5px solid #10b981 !important;
        background-color: #f0fdf4;
    }
    .slot-item.is-error {
        border-left: 3.5px solid #ef4444 !important;
        background-color: #fef2f2;
    }

    /* Status Radio Buttons Styling */
    .status-btn-radio {
        transition: all 0.15s ease;
        user-select: none;
    }
    .status-btn-radio input[type="radio"] {
        display: none;
    }
    
    /* Checked State Styles */
    .status-btn-radio:has(input[value="H"]:checked) {
        background-color: #ecfdf5;
        border-color: #10b981;
        color: #047857;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.18);
    }
    .status-btn-radio:has(input[value="S"]:checked) {
        background-color: var(--primary-50);
        border-color: var(--primary-500);
        color: var(--primary-700);
        box-shadow: 0 2px 6px rgb(var(--primary-500-rgb) / 0.18);
    }
    .status-btn-radio:has(input[value="I"]:checked) {
        background-color: #fffbeb;
        border-color: #f59e0b;
        color: #b45309;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.18);
    }
    .status-btn-radio:has(input[value="A"]:checked) {
        background-color: #fef2f2;
        border-color: #ef4444;
        color: #b91c1c;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.18);
    }

    /* Badge ringkasan jurnal & kehadiran */
    .mon-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2.5px 7px;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 800;
        line-height: 1.5;
        border: 1px solid transparent;
        white-space: nowrap;
        text-decoration: none;
    }
    .mon-badge i { font-size: 10px; }
    button.mon-badge { appearance: none; font-family: inherit; cursor: pointer; }
    .mon-jurnal { background: #ecfdf5; border-color: #d1fae5; color: #047857; }
    .mon-siswa { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
    .mon-absen { background: #fef2f2; border-color: #fee2e2; color: #b91c1c; }
    .mon-pengganti { background: #fffbeb; border-color: #fde68a; color: #b45309; }
    .mon-scan { background: #f0f9ff; border-color: #e0f2fe; color: #0369a1; }
    .mon-noscan { background: #f8fafc; border-color: #e2e8f0; color: #64748b; }

    /* Hasil validasi jurnal oleh piket / kepsek */
    .mon-pending  { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }
    .mon-approved { background: #ecfdf5; border-color: #6ee7b7; color: #047857; }
    .mon-rejected { background: #fff7ed; border-color: #fdba74; color: #c2410c; }

    /* Aksi validasi */
    .val-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 11px;
        border-radius: 10px;
        border: 1px solid transparent;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
        cursor: pointer;
        transition: background-color 0.15s ease, opacity 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }
    .val-btn:disabled { opacity: 0.5; cursor: wait; }
    .val-btn:focus-visible { outline: 2px solid #6366f1; outline-offset: 1px; }
    .val-btn-approve { background: #10b981; color: #ffffff; }
    .val-btn-approve:hover { background: #059669; }
    .val-btn-reject { background: #ffffff; border-color: #fdba74; color: #c2410c; }
    .val-btn-reject:hover { background: #fff7ed; }
    .val-btn-reset { background: #ffffff; border-color: #e2e8f0; color: #64748b; font-weight: 700; }
    .val-btn-reset:hover { background: #f8fafc; }

    .mon-detail {
        display: flex;
        align-items: flex-start;
        gap: 5px;
        font-size: 10px;
        line-height: 1.35;
        color: #475569;
    }
    .mon-detail i { flex-shrink: 0; margin-top: 1px; }
    /* Tailwind .hidden bisa kalah urut dari blok style halaman ini. */
    .mon-detail.hidden,
    .val-catatan.hidden { display: none; }
    .mon-detail .txt {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mon-warn {
        display: flex;
        align-items: flex-start;
        gap: 5px;
        padding: 5px 7px;
        border-radius: 9px;
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #b45309;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.35;
    }
    .mon-warn i { flex-shrink: 0; margin-top: 1px; }
    .mon-warn.hidden { display: none; }

    /* Filter temuan monitoring */
    .issue-chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 7px 11px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 10.5px;
        font-weight: 800;
        line-height: 1.4;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .issue-chip.is-active {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #ffffff;
    }
    .set-all-more { display: none; }
    @media (min-width: 380px) {
        .set-all-more { display: inline; }
    }
    /* Di layar sangat sempit, sembunyikan jumlah siswa dulu agar nama
       kelas tidak terpotong oleh tombol Set Hadir. */
    .class-size { display: none; }
    @media (min-width: 360px) {
        .class-size { display: inline; }
    }

    /* Deretan chip temuan: satu baris, digeser mendatar agar tidak
       memakan banyak tinggi di layar ponsel. */
    .issue-scroll {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .issue-scroll::-webkit-scrollbar { display: none; }
    .issue-scroll .issue-chip { flex: 0 0 auto; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
    $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
    $indoDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $dayName = $indoDays[(int)date('N', strtotime($tanggal))] ?? '';
    $totalSIA = ((int)($summary['sakit'] ?? 0) + (int)($summary['izin'] ?? 0) + (int)($summary['alpa'] ?? 0));
?>

<!-- ── Floating Save Toast ─────────────────────────────── -->
<div id="saveToast" class="save-toast-tw fixed top-4 left-1/2 z-[3500] px-4 py-2 rounded-full bg-emerald-600 text-white text-xs font-bold shadow-lg flex items-center gap-2">
    <i class="bi bi-check-circle-fill text-sm"></i>
    <span id="saveToastMsg">Tersimpan</span>
</div>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('guru/piket') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Input Kehadiran Guru</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Catat kehadiran tiap jam pelajaran</p>
        </div>
    </div>
    <div class="flex items-center gap-1.5">
        <a href="<?= site_url('guru/piket/laporan') ?>?tanggal=<?= esc($tanggal) ?>"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold border border-indigo-100 hover:bg-indigo-100 transition-colors no-underline"
           title="Lihat Rekap Laporan">
            <i class="bi bi-file-earmark-bar-graph text-xs"></i> Rekap
        </a>
    </div>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-12 space-y-3 mt-2.5">

    <!-- ── Date Navigation Chip ─────────────────────────── -->
    <div class="date-nav-chip flex items-center justify-between bg-white border border-slate-200/80 rounded-2xl px-3 py-2 shadow-xs">
        <a href="?tanggal=<?= $prevDate ?><?= $filter_kelas ? '&kelas='.$filter_kelas : '' ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Sebelumnya">
            <i class="bi bi-chevron-left text-xs font-bold"></i>
        </a>

        <div class="flex items-center gap-2.5 cursor-pointer relative px-2 py-1 rounded-xl hover:bg-slate-50 transition-colors"
             onclick="document.getElementById('headerDatePicker').showPicker ? document.getElementById('headerDatePicker').showPicker() : document.getElementById('headerDatePicker').focus()">
            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100/80">
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
                <?php if ($filter_kelas): ?>
                    <input type="hidden" name="kelas" value="<?= (int)$filter_kelas ?>">
                <?php endif; ?>
                <input type="date" name="tanggal" value="<?= esc($tanggal) ?>" id="headerDatePicker"
                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                       onchange="document.getElementById('dateForm').submit()">
            </form>
        </div>

        <a href="?tanggal=<?= $nextDate ?><?= $filter_kelas ? '&kelas='.$filter_kelas : '' ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Berikutnya">
            <i class="bi bi-chevron-right text-xs font-bold"></i>
        </a>
    </div>

    <!-- ── Ringkasan cepat ─────────────────────────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs grid grid-cols-4 divide-x divide-slate-100">
        <div class="py-2.5 flex flex-col items-center justify-center text-center">
            <span id="sumTotal" class="text-lg font-extrabold text-slate-800 leading-none"><?= (int)($summary['total_slot'] ?? 0) ?></span>
            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-tight mt-1">Total</span>
        </div>
        <div class="py-2.5 flex flex-col items-center justify-center text-center">
            <span id="sumHadir" class="text-lg font-extrabold text-emerald-600 leading-none"><?= (int)($summary['hadir'] ?? 0) ?></span>
            <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-tight mt-1">Hadir</span>
        </div>
        <div class="py-2.5 flex flex-col items-center justify-center text-center">
            <span id="sumAbsen" class="text-lg font-extrabold text-rose-600 leading-none"><?= $totalSIA ?></span>
            <span class="text-[9px] font-bold text-rose-600 uppercase tracking-tight mt-1">S / I / A</span>
        </div>
        <div class="py-2.5 flex flex-col items-center justify-center text-center">
            <span id="sumBelum" class="text-lg font-extrabold text-amber-600 leading-none"><?= (int)($summary['belum_dicek'] ?? 0) ?></span>
            <span class="text-[9px] font-bold text-amber-600 uppercase tracking-tight mt-1">Belum Cek</span>
        </div>
    </div>

    <!-- ── Filter & Aksi Cepat ─────────────────────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs space-y-2.5">
        <div class="grid grid-cols-2 gap-2">
            <!-- Filter Kelas -->
            <div class="relative">
                <select id="filterKelasSelect" onchange="filterByClass(this.value)"
                        class="w-full appearance-none pl-3 pr-7 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold focus:outline-none focus:border-indigo-500">
                    <option value="">Semua Kelas (<?= count($kelas_list ?? []) ?>)</option>
                    <?php if (!empty($kelas_list)): ?>
                        <?php foreach ($kelas_list as $kId => $kName): ?>
                            <option value="<?= $kId ?>" <?= ($filter_kelas == $kId) ? 'selected' : '' ?>>
                                Kelas <?= esc($kName) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
            </div>

            <!-- Search Slot -->
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchSlotInput" placeholder="Cari mapel / guru..."
                       class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        <!-- Filter temuan: satu baris, geser mendatar -->
        <div class="issue-scroll -mx-3 px-3">
            <button type="button" class="issue-chip is-active" data-issue="" onclick="setIssueFilter(this)">Semua</button>
            <button type="button" class="issue-chip" data-issue="tanpa_jurnal" onclick="setIssueFilter(this)">Tanpa Jurnal</button>
            <button type="button" class="issue-chip" data-issue="tanpa_scan" onclick="setIssueFilter(this)">Tanpa Scan</button>
            <button type="button" class="issue-chip" data-issue="pengganti" onclick="setIssueFilter(this)">Pengganti</button>
            <button type="button" class="issue-chip" data-issue="absen_siswa" onclick="setIssueFilter(this)">Absen Siswa</button>
            <button type="button" class="issue-chip" data-issue="perlu_validasi" onclick="setIssueFilter(this)">Perlu Dinilai</button>
            <button type="button" class="issue-chip" data-issue="jurnal_valid" onclick="setIssueFilter(this)">Tervalidasi</button>
            <button type="button" class="issue-chip" data-issue="jurnal_perlu_perbaikan" onclick="setIssueFilter(this)">Perlu Perbaikan</button>
            <button type="button" class="issue-chip" data-issue="mismatch" onclick="setIssueFilter(this)">Mismatch</button>
            <button type="button" class="issue-chip" data-issue="belum" onclick="setIssueFilter(this)">Belum Cek</button>
        </div>

        <!-- Hint bar & Autofill Action -->
        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
            <span class="text-[10px] text-slate-500 font-medium flex items-center gap-1 min-w-0">
                <i class="bi bi-cloud-check text-emerald-500"></i>
                <span class="truncate">Tersimpan otomatis</span>
            </span>
            <button type="button" onclick="triggerAutofill()"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 text-[11px] font-bold active:scale-95 transition-transform">
                <i class="bi bi-magic"></i> Tarik Presensi
            </button>
        </div>
    </div>

    <!-- ── Class Cards Container ────────────────────────── -->
    <div class="space-y-3" id="classCardsContainer">
        <?php if (empty($report)): ?>
            <div class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 m-0">Tidak Ada Jadwal</h3>
                <p class="text-xs text-slate-500 mt-1 m-0">Tidak ditemukan jadwal pelajaran KBM untuk tanggal ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($report as $idKls => $kls): ?>
                <div class="class-card bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden"
                     id="class-card-<?= $idKls ?>"
                     data-kelas-id="<?= $idKls ?>">
                    
                    <!-- Class Header -->
                    <div class="bg-slate-50/80 px-4 py-2.5 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs flex-shrink-0">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                            <span class="font-extrabold text-xs text-slate-800 truncate">
                                <?= esc($kls['nama_kelas']) ?>
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 bg-white border border-slate-200/80 rounded-md px-1.5 py-0.5 whitespace-nowrap flex-shrink-0">
                                <?= count($kls['slots'] ?? []) ?> Jam<span class="class-size"><?php if (!empty($kls['jumlah_siswa'])): ?> · <?= (int)$kls['jumlah_siswa'] ?> Siswa<?php endif; ?></span>
                            </span>
                        </div>

                        <button type="button" onclick="setAllClassPresent(<?= $idKls ?>)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-[11px] font-bold border border-emerald-200 transition-colors whitespace-nowrap flex-shrink-0">
                            <i class="bi bi-check-all text-xs"></i> Set Hadir<span class="set-all-more"> Semua</span>
                        </button>
                    </div>

                    <!-- Slots List -->
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($kls['slots'] as $s):
                            $isSaved = !empty($s['manual']);
                            $manualStatus = $s['manual']->status ?? '';
                            $manualKet = $s['manual']->keterangan ?? '';
                            $statusLabel = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'][$manualStatus] ?? $manualStatus;
                            $jurnalOk = !empty($s['jurnal']);
                            $jurnalIdVal = (int) ($s['jurnal_id'] ?? 0);
                            $validasiStatus = (string) ($s['validasi_status'] ?? '');
                            $validasiCatatan = trim((string) ($s['validasi_catatan'] ?? ''));
                            $validasiNama = trim((string) ($s['validasi_nama'] ?? ''));
                            $validasiPada = trim((string) ($s['validasi_pada'] ?? ''));
                            $validasiMeta = [
                                'PENDING'  => ['mon-pending', 'bi-shield-exclamation', 'Perlu validasi'],
                                'APPROVED' => ['mon-approved', 'bi-patch-check-fill', 'Tervalidasi'],
                                'REJECTED' => ['mon-rejected', 'bi-exclamation-octagon-fill', 'Perlu perbaikan'],
                            ][$validasiStatus] ?? null;
                            $validasiTitle = $validasiStatus === 'PENDING'
                                ? 'Jurnal ini belum dinilai oleh guru piket / kepala sekolah.'
                                : 'Divalidasi oleh ' . ($validasiNama !== '' ? $validasiNama : 'petugas')
                                    . ($validasiPada !== '' ? ' pada ' . date('d/m/Y H:i', strtotime($validasiPada)) : '.')
                                    . ($validasiCatatan !== '' ? ' Catatan: ' . $validasiCatatan : '');
                            $scanOk = !empty($s['scan_masuk']);
                            $absenN = (int) ($s['siswa_absen_jumlah'] ?? 0);
                            $absenNama = $s['siswa_absen_nama'] ?? [];
                            $materiText = trim((string) ($s['materi'] ?? ''));
                            $pelaksanaScan = !empty($s['pengganti']) ? !empty($s['pengganti_scan_masuk']) : $scanOk;
                        ?>
                            <div class="slot-item p-3 space-y-2 <?= $isSaved ? 'is-saved' : '' ?>"
                                 data-id-kls="<?= $idKls ?>"
                                 data-jam="<?= $s['jam_ke'] ?>"
                                 data-mapel="<?= $s['id_mapel'] ?>"
                                 data-guru="<?= $s['id_guru'] ?: 0 ?>"
                                 data-jurnal="<?= $jurnalOk ? '1' : '0' ?>"
                                 data-pengganti="<?= !empty($s['pengganti']) ? '1' : '0' ?>"
                                 data-scan-pelaksana="<?= $pelaksanaScan ? '1' : '0' ?>"
                                 data-absen="<?= $absenN ?>"
                                 data-validasi="<?= esc($jurnalOk ? ($validasiStatus ?: 'PENDING') : '') ?>"
                                 data-jurnal-id="<?= $jurnalOk ? $jurnalIdVal : 0 ?>"
                                 data-search="<?= strtolower(esc($s['nama_mapel'] . ' ' . $s['nama_guru'] . ' ' . ($s['jurnal_guru_nama'] ?? '') . ' ' . $materiText)) ?>">

                                <!-- Slot Top: Jam Badge, Mapel, & Guru -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-extrabold text-xs text-slate-900 truncate leading-tight">
                                            <?= esc($s['nama_mapel']) ?>
                                        </div>
                                        <div class="text-[11px] font-medium text-slate-500 mt-0.5 flex items-center gap-1.5 truncate">
                                            <i class="bi bi-person-fill text-slate-400"></i>
                                            <?php if ($s['nama_guru'] === 'Belum Diset'): ?>
                                                <span class="text-rose-600 font-bold bg-rose-50 px-1.5 py-0.5 rounded">Belum Diset</span>
                                            <?php else: ?>
                                                <span class="text-slate-600 truncate"><?= esc($s['nama_guru']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold text-[10px] border border-indigo-100 flex-shrink-0">
                                        Jam <?= $s['jam_ke'] ?>
                                    </span>
                                </div>

                                <!-- Ringkasan jurnal & kehadiran (satu baris badge) -->
                                <div class="flex flex-wrap items-center gap-1">
                                    <?php if ($jurnalOk): ?>
                                        <button type="button" onclick="bukaJurnal(<?= $jurnalIdVal ?>)"
                                                class="mon-badge mon-jurnal" title="Baca isi jurnal sebelum memvalidasi">
                                            <i class="bi bi-clipboard-check"></i> Jurnal
                                        </button>
                                        <span class="mon-badge mon-siswa">
                                            <i class="bi bi-people-fill"></i>
                                            <?= (int)($s['siswa_hadir'] ?? 0) ?>/<?= (int)($s['siswa_total'] ?? 0) ?> hadir
                                        </span>
                                        <?php if ($absenN > 0): ?>
                                            <span class="mon-badge mon-absen"><i class="bi bi-person-x"></i> <?= $absenN ?> absen</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="mon-badge mon-absen"><i class="bi bi-journal-x"></i> Tanpa jurnal</span>
                                    <?php endif; ?>

                                    <?php if (!empty($s['pengganti'])): ?>
                                        <span class="mon-badge mon-pengganti">
                                            <i class="bi bi-person-plus-fill"></i>
                                            Pengganti: <?= esc($s['jurnal_guru_nama']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($jurnalOk): ?>
                                        <?php if ($pelaksanaScan): ?>
                                            <span class="mon-badge mon-scan">
                                                <i class="bi bi-person-check-fill"></i>
                                                Scan <?= esc(!empty($s['pengganti']) ? $s['pengganti_scan_jam'] : $s['scan_jam']) ?>
                                            </span>
                                        <?php elseif (!empty($s['id_guru']) || !empty($s['pengganti'])): ?>
                                            <span class="mon-badge mon-noscan">
                                                <i class="bi bi-person-x"></i>
                                                <?= !empty($s['pengganti']) ? 'Pengganti belum scan' : 'Belum scan' ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($jurnalOk && $validasiMeta): ?>
                                        <span class="mon-badge val-badge <?= $validasiMeta[0] ?>"
                                              title="<?= esc($validasiTitle) ?>"
                                              data-status="<?= esc($validasiStatus) ?>">
                                            <i class="bi <?= $validasiMeta[1] ?>"></i>
                                            <span class="val-badge-text"><?= $validasiMeta[2] ?></span>
                                            <?php if ($validasiStatus !== 'PENDING' && $validasiNama !== ''): ?>
                                                <span class="val-badge-by">· <?= esc($validasiNama) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($jurnalOk && ($materiText !== '' || $absenN > 0)): ?>
                                    <?php if ($materiText !== ''): ?>
                                        <div class="mon-detail" title="<?= esc($materiText) ?>">
                                            <i class="bi bi-book text-slate-400"></i>
                                            <span class="txt"><?= esc($materiText) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($absenN > 0): ?>
                                        <?php $absenLabel = implode(', ', $absenNama) ?: ($absenN . ' siswa (nama tidak tercatat)'); ?>
                                        <div class="mon-detail" style="color:#b91c1c" title="<?= esc($absenLabel) ?>">
                                            <i class="bi bi-person-x" style="color:#f87171"></i>
                                            <span class="txt">Absen: <?= esc($absenLabel) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Validasi isian jurnal (guru piket / kepala sekolah) -->
                                <?php if ($jurnalOk && $jurnalIdVal > 0): ?>
                                    <div class="val-area border-t border-slate-100 pt-2 space-y-1.5"
                                         data-jurnal-id="<?= $jurnalIdVal ?>"
                                         data-milik-sendiri="<?= (($val_guru_sendiri ?? 0) > 0 && (int) ($s['jurnal_id_guru'] ?? 0) === (int) $val_guru_sendiri) ? '1' : '0' ?>"
                                         data-validasi="<?= esc($validasiStatus ?: 'PENDING') ?>">
                                        <div class="val-catatan mon-detail<?= $validasiCatatan === '' ? ' hidden' : '' ?>"
                                             style="color:#c2410c"
                                             title="<?= esc($validasiCatatan) ?>">
                                            <i class="bi bi-chat-left-text" style="color:#fb923c"></i>
                                            <span class="txt">Catatan validator: <?= esc($validasiCatatan) ?></span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Validasi</span>
                                            <div class="val-actions flex flex-wrap items-center gap-1.5"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mon-warn mismatch-warning <?= empty($s['piket_mismatch']) ? 'hidden' : '' ?>">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span class="mismatch-text">
                                        <?= empty($s['piket_mismatch']) ? '' : ($jurnalOk
                                            ? 'Piket mencatat ' . esc($statusLabel) . ', padahal jurnal sudah terisi.'
                                            : 'Piket mencatat Hadir, padahal jurnal belum diisi.') ?>
                                    </span>
                                </div>

                                <!-- 4 Segmented Status Buttons (H, S, I, A) -->
                                <div class="grid grid-cols-4 gap-1.5">
                                    <!-- Hadir -->
                                    <label class="status-btn-radio flex flex-col items-center justify-center py-2 px-1 rounded-xl border border-slate-200 bg-white text-slate-600 font-extrabold text-[11px] cursor-pointer"
                                           title="Hadir">
                                        <input type="radio" value="H" name="st-<?= $idKls ?>-<?= $s['jam_ke'] ?>" <?= ($manualStatus === 'H') ? 'checked' : '' ?>>
                                        <div class="flex items-center gap-1">
                                            <i class="bi bi-check-circle text-xs"></i>
                                            <span>Hadir</span>
                                        </div>
                                    </label>

                                    <!-- Sakit -->
                                    <label class="status-btn-radio flex flex-col items-center justify-center py-2 px-1 rounded-xl border border-slate-200 bg-white text-slate-600 font-extrabold text-[11px] cursor-pointer"
                                           title="Sakit">
                                        <input type="radio" value="S" name="st-<?= $idKls ?>-<?= $s['jam_ke'] ?>" <?= ($manualStatus === 'S') ? 'checked' : '' ?>>
                                        <div class="flex items-center gap-1">
                                            <i class="bi bi-heart-pulse text-xs"></i>
                                            <span>Sakit</span>
                                        </div>
                                    </label>

                                    <!-- Izin -->
                                    <label class="status-btn-radio flex flex-col items-center justify-center py-2 px-1 rounded-xl border border-slate-200 bg-white text-slate-600 font-extrabold text-[11px] cursor-pointer"
                                           title="Izin">
                                        <input type="radio" value="I" name="st-<?= $idKls ?>-<?= $s['jam_ke'] ?>" <?= ($manualStatus === 'I') ? 'checked' : '' ?>>
                                        <div class="flex items-center gap-1">
                                            <i class="bi bi-envelope-paper text-xs"></i>
                                            <span>Izin</span>
                                        </div>
                                    </label>

                                    <!-- Alpa -->
                                    <label class="status-btn-radio flex flex-col items-center justify-center py-2 px-1 rounded-xl border border-slate-200 bg-white text-slate-600 font-extrabold text-[11px] cursor-pointer"
                                           title="Alpa / Tanpa Keterangan">
                                        <input type="radio" value="A" name="st-<?= $idKls ?>-<?= $s['jam_ke'] ?>" <?= ($manualStatus === 'A') ? 'checked' : '' ?>>
                                        <div class="flex items-center gap-1">
                                            <i class="bi bi-x-circle text-xs"></i>
                                            <span>Alpa</span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Note Input -->
                                <div class="relative">
                                    <i class="bi bi-pen absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input type="text"
                                           class="note-input w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-700 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-indigo-500 transition-colors"
                                           placeholder="Catatan / guru pengganti…"
                                           value="<?= esc($manualKet) ?>">
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Empty state saat filter menyembunyikan semua slot ── -->
    <div id="slotEmptyHint" class="hidden bg-white border border-slate-200/80 rounded-2xl p-8 text-center shadow-xs">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="bi bi-funnel"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-800 m-0">Tidak Ada Slot Cocok</h3>
        <p class="text-xs text-slate-500 mt-1 m-0">Ubah filter temuan, kelas, atau kata pencarian.</p>
    </div>

    <!-- ── Modal isi jurnal (dibaca sebelum memvalidasi) ── -->
    <div class="tw-modal" id="modalJurnalValidasi" aria-hidden="true">
        <div class="tw-modal-box">
            <div class="sticky top-0 bg-white z-10 px-5 pt-3 pb-3 border-b border-slate-100">
                <div class="w-10 h-1.5 bg-slate-300 rounded-full mx-auto mb-3"></div>
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Isi Jurnal KBM</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5 font-medium truncate" id="jdMeta">Memuat data...</p>
                    </div>
                    <button type="button" data-tw-dismiss="modal" aria-label="Tutup detail jurnal"
                            class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors border-none cursor-pointer flex-shrink-0">
                        <i class="bi bi-x-lg text-xs font-bold"></i>
                    </button>
                </div>
            </div>

            <div class="p-5 space-y-3.5">
                <div id="jdLoading" class="py-12 flex flex-col items-center justify-center gap-2">
                    <div class="w-8 h-8 border-[3px] border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-semibold text-slate-500">Memuat isi jurnal...</span>
                </div>

                <div id="jdBody" class="hidden space-y-3.5">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-100 text-blue-700 text-[11px] font-bold">
                            <i class="bi bi-person-fill"></i> <span id="jdGuru">-</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 text-[11px] font-bold">
                            <i class="bi bi-book-fill"></i> <span id="jdMapel">-</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-700 text-[11px] font-bold">
                            <i class="bi bi-people-fill"></i> <span id="jdKelas">-</span>
                        </span>
                    </div>

                    <div id="jdValidasi" class="rounded-xl px-3 py-2 text-[11px] font-bold leading-snug flex items-start gap-1.5 hidden"></div>

                    <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-100 space-y-2.5 text-xs">
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Waktu Mengajar</span><span class="text-slate-800 font-semibold mt-0.5 block" id="jdWaktu">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Materi Pokok</span><span class="text-slate-800 font-semibold mt-0.5 block whitespace-pre-line" id="jdMateri">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Tujuan Pembelajaran</span><span class="text-slate-700 font-medium mt-0.5 block whitespace-pre-line" id="jdTujuan">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Model &amp; Media</span><span class="text-slate-700 font-medium mt-0.5 block" id="jdModelMedia">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Kegiatan Belajar</span><span class="text-slate-700 font-medium mt-0.5 block whitespace-pre-line" id="jdKegiatan">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Asesmen</span><span class="text-slate-700 font-medium mt-0.5 block" id="jdAsesmen">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Refleksi</span><span class="text-slate-700 font-medium mt-0.5 block whitespace-pre-line" id="jdRefleksi">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Tindak Lanjut</span><span class="text-slate-700 font-medium mt-0.5 block whitespace-pre-line" id="jdTindak">-</span></div>
                        <div><span class="text-[10px] font-bold text-slate-400 uppercase block">Catatan Khusus</span><span class="text-slate-700 font-medium mt-0.5 block whitespace-pre-line" id="jdCatatanKhusus">-</span></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-2.5">
                            <div class="text-lg font-extrabold text-slate-800 leading-none" id="jdTotal">0</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase mt-1">Total</div>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-2.5">
                            <div class="text-lg font-extrabold text-emerald-700 leading-none" id="jdHadir">0</div>
                            <div class="text-[10px] font-bold text-emerald-600 uppercase mt-1">Hadir</div>
                        </div>
                        <div class="bg-rose-50 border border-rose-200/80 rounded-2xl p-2.5">
                            <div class="text-lg font-extrabold text-rose-700 leading-none" id="jdTidak">0</div>
                            <div class="text-[10px] font-bold text-rose-600 uppercase mt-1">Tidak Hadir</div>
                        </div>
                    </div>

                    <div id="jdTidakWrap" class="hidden space-y-1.5">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Siswa Tidak Hadir</div>
                        <div id="jdTidakList" class="flex flex-wrap gap-1.5"></div>
                    </div>

                    <div class="val-area border-t border-slate-100 pt-3 flex flex-wrap items-center gap-1.5"
                         data-jurnal-id="0" data-milik-sendiri="0" data-validasi="PENDING">
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Validasi</span>
                        <div class="val-actions flex flex-wrap items-center gap-1.5"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const BASE = '<?= base_url() ?>';
    const GURU_SENDIRI = <?= (int) ($val_guru_sendiri ?? 0) ?>;
    const toast = document.getElementById('saveToast');
    const toastMsg = document.getElementById('saveToastMsg');
    let toastTimer = null;

    function showToast(msg, isSuccess = true) {
        if (!toast) return;
        toastMsg.textContent = msg || (isSuccess ? 'Tersimpan' : 'Gagal');
        toast.className = 'save-toast-tw fixed top-4 left-1/2 z-[3500] px-4 py-2 rounded-full text-white text-xs font-bold shadow-lg flex items-center gap-2 ' +
                          (isSuccess ? 'bg-emerald-600' : 'bg-rose-600') + ' show';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 1800);
    }

    function haptic(ms = 10) {
        if (window.navigator.vibrate) window.navigator.vibrate(ms);
    }

    // ── Live Summary Counters Recalculation ───────────────
    const STATUS_LABEL = { H: 'Hadir', S: 'Sakit', I: 'Izin', A: 'Alpa' };

    function refreshMismatch(slot) {
        const warn = slot.querySelector('.mismatch-warning');
        if (!warn) return;

        const checked = slot.querySelector('input[type="radio"]:checked');
        const status = checked ? checked.value : '';
        const hasJurnal = slot.dataset.jurnal === '1';

        let msg = '';
        if (status === 'H' && !hasJurnal) {
            msg = 'Piket mencatat Hadir, padahal jurnal belum diisi.';
        } else if (status && status !== 'H' && hasJurnal) {
            msg = 'Piket mencatat ' + (STATUS_LABEL[status] || status) + ', padahal jurnal sudah terisi.';
        }

        const text = warn.querySelector('.mismatch-text');
        if (text) text.textContent = msg;
        warn.classList.toggle('hidden', msg === '');
    }

    window.updateStatsCounters = function() {
        let total = 0;
        let hadir = 0;
        let sia = 0;
        let belum = 0;

        document.querySelectorAll('.slot-item').forEach(slot => {
            total++;
            refreshMismatch(slot);
            const checked = slot.querySelector('input[type="radio"]:checked');
            if (checked) {
                const val = checked.value;
                if (val === 'H') hadir++;
                else if (val === 'S' || val === 'I' || val === 'A') sia++;
            } else {
                belum++;
            }
        });

        const elTotal = document.getElementById('sumTotal');
        const elHadir = document.getElementById('sumHadir');
        const elAbsen = document.getElementById('sumAbsen');
        const elBelum = document.getElementById('sumBelum');

        if (elTotal) elTotal.textContent = total;
        if (elHadir) elHadir.textContent = hadir;
        if (elAbsen) elAbsen.textContent = sia;
        if (elBelum) elBelum.textContent = belum;

        applyFilters();
    };

    // ── Save Row Function ─────────────────────────────────
    window.saveRow = function(slotEl, customStatus = null) {
        if (!slotEl) return Promise.resolve();

        const idKls = slotEl.dataset.idKls;
        const jam = slotEl.dataset.jam;
        const idMapel = slotEl.dataset.mapel;
        const idGuru = slotEl.dataset.guru;

        let status = customStatus;
        if (!status) {
            const checked = slotEl.querySelector('input[type="radio"]:checked');
            status = checked ? checked.value : '';
        }

        const noteInput = slotEl.querySelector('.note-input');
        const note = noteInput ? noteInput.value : '';
        const date = '<?= esc($tanggal) ?>';

        if (!status) return Promise.resolve();

        const formData = new FormData();
        formData.append('tanggal', date);
        formData.append('id_kelas', idKls);
        formData.append('jam_ke', jam);
        formData.append('id_mapel', idMapel);
        formData.append('id_guru', idGuru);
        formData.append('status', status);
        formData.append('keterangan', note);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        return fetch(BASE + 'guru/piket/save_laporan_guru', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(res => {
            slotEl.classList.remove('is-saved', 'is-error');
            if (res.status) {
                slotEl.classList.add('is-saved');
                showToast(res.message || 'Tersimpan', true);
                haptic(10);
            } else {
                slotEl.classList.add('is-error');
                showToast(res.message || 'Gagal menyimpan', false);
            }
            updateStatsCounters();
            return res;
        })
        .catch(err => {
            slotEl.classList.add('is-error');
            showToast('Gagal terhubung ke server', false);
            throw err;
        });
    };

    // ── Auto-save on radio button change ──────────────────
    document.querySelectorAll('.status-btn-radio input').forEach(r => {
        r.addEventListener('change', function() {
            const slotEl = this.closest('.slot-item');
            if (slotEl) saveRow(slotEl);
        });
    });

    // ── Auto-save on note input blur ──────────────────────
    document.querySelectorAll('.note-input').forEach(input => {
        input.addEventListener('blur', function() {
            const slotEl = this.closest('.slot-item');
            if (slotEl && slotEl.querySelector('input[type="radio"]:checked')) {
                saveRow(slotEl);
            }
        });
    });

    // ── Unified filters (kelas + pencarian + temuan) ──────
    const filterState = { kelas: '', q: '', issue: '' };

    function slotHasIssue(slot) {
        const checked = slot.querySelector('input[type="radio"]:checked');
        const status = checked ? checked.value : '';
        const hasJurnal = slot.dataset.jurnal === '1';

        switch (filterState.issue) {
            case 'tanpa_jurnal':
                return !hasJurnal;
            case 'tanpa_scan':
                return hasJurnal && slot.dataset.scanPelaksana !== '1';
            case 'pengganti':
                return slot.dataset.pengganti === '1';
            case 'absen_siswa':
                return Number(slot.dataset.absen || 0) > 0;
            case 'mismatch':
                return (status === 'H' && !hasJurnal) || (status !== '' && status !== 'H' && hasJurnal);
            case 'belum':
                return !checked;
            case 'perlu_validasi':
                return hasJurnal && slot.dataset.validasi === 'PENDING';
            case 'jurnal_valid':
                return hasJurnal && slot.dataset.validasi === 'APPROVED';
            case 'jurnal_perlu_perbaikan':
                return hasJurnal && slot.dataset.validasi === 'REJECTED';
            default:
                return true;
        }
    }

    function applyFilters() {
        let visibleCount = 0;
        document.querySelectorAll('.class-card').forEach(card => {
            const classMatch = !filterState.kelas || card.dataset.kelasId === filterState.kelas;
            let hasMatch = false;

            card.querySelectorAll('.slot-item').forEach(slot => {
                const text = slot.dataset.search || '';
                const textMatch = !filterState.q || text.includes(filterState.q);
                const visible = classMatch && textMatch && slotHasIssue(slot);
                slot.classList.toggle('hidden', !visible);
                if (visible) {
                    hasMatch = true;
                    visibleCount++;
                }
            });

            card.classList.toggle('hidden', !classMatch || !hasMatch);
        });

        const hint = document.getElementById('slotEmptyHint');
        if (hint) {
            const hasCards = document.querySelectorAll('.class-card').length > 0;
            hint.classList.toggle('hidden', !(hasCards && visibleCount === 0));
        }
    }

    window.filterByClass = function(kelasId) {
        filterState.kelas = kelasId ? String(kelasId) : '';
        applyFilters();
    };

    function syncIssueUrl() {
        const url = new URL(window.location.href);
        if (filterState.issue) url.searchParams.set('issue', filterState.issue);
        else url.searchParams.delete('issue');
        window.history.replaceState(null, '', url);
    }

    window.setIssueFilter = function(btn) {
        document.querySelectorAll('.issue-chip').forEach(chip => {
            chip.classList.toggle('is-active', chip === btn);
        });
        filterState.issue = btn.dataset.issue || '';
        applyFilters();
        syncIssueUrl();
    };

    window.setIssueFilterSlug = function(slug) {
        const chip = document.querySelector('.issue-chip[data-issue="' + slug + '"]');
        if (!chip) return;
        // Ketuk tile yang sudah aktif → kembali ke semua slot.
        if (filterState.issue === slug) {
            const allChip = document.querySelector('.issue-chip[data-issue=""]');
            if (allChip) window.setIssueFilter(allChip);
            return;
        }
        window.setIssueFilter(chip);
    };

    // ── Validasi jurnal oleh guru piket / kepala sekolah ──
    const VALIDASI_META = {
        PENDING:  { cls: 'mon-pending',  icon: 'bi-shield-exclamation',       text: 'Perlu validasi' },
        APPROVED: { cls: 'mon-approved', icon: 'bi-patch-check-fill',         text: 'Tervalidasi' },
        REJECTED: { cls: 'mon-rejected', icon: 'bi-exclamation-octagon-fill',  text: 'Perlu perbaikan' },
    };

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    // Aksi yang ditawarkan mengikuti status terakhir: jurnal yang sudah
    // disetujui masih boleh ditolak (dan sebaliknya) tanpa memuat ulang.
    // bolehNilai=false dipakai untuk jurnal milik guru piket itu sendiri —
    // server menolaknya, jadi tombolnya tidak ditawarkan.
    function valActionsHtml(status, bolehNilai = true) {
        if (!bolehNilai) {
            return '<span class="text-[10px] font-semibold text-slate-400">Jurnal Anda sendiri — dinilai oleh piket lain / admin.</span>';
        }
        const btn = (aksi, cls, icon, label) =>
            '<button type="button" class="val-btn ' + cls + '" data-aksi="' + aksi + '">' +
            '<i class="bi ' + icon + '"></i> ' + label + '</button>';
        const approve = btn('setujui', 'val-btn-approve', 'bi-check2-circle', 'Setujui');
        const reject = btn('tolak', 'val-btn-reject', 'bi-x-circle', 'Tolak');
        const reset = btn('batalkan', 'val-btn-reset', 'bi-arrow-counterclockwise', 'Batalkan');
        if (status === 'APPROVED') return reject + reset;
        if (status === 'REJECTED') return approve + reset;
        return approve + reject;
    }

    function areaBolehNilai(area) {
        return !area || area.dataset.milikSendiri !== '1';
    }

    function fmtWaktu(value) {
        const m = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : String(value || '');
    }

    function renderValArea(slot, status, catatan, olehNama, pada) {
        if (!VALIDASI_META[status]) return;
        slot.dataset.validasi = status;

        const area = slot.querySelector('.val-area');
        if (area) {
            area.dataset.validasi = status;
            const actions = area.querySelector('.val-actions');
            if (actions) actions.innerHTML = valActionsHtml(status, areaBolehNilai(area));
        }

        const badge = slot.querySelector('.val-badge');
        if (badge) {
            const meta = VALIDASI_META[status];
            badge.className = 'mon-badge val-badge ' + meta.cls;
            badge.dataset.status = status;
            badge.innerHTML = '<i class="bi ' + meta.icon + '"></i>' +
                ' <span class="val-badge-text">' + meta.text + '</span>' +
                (status !== 'PENDING' && olehNama
                    ? ' <span class="val-badge-by">· ' + escapeHtml(olehNama) + '</span>' : '');
            badge.title = status === 'PENDING'
                ? 'Jurnal ini belum dinilai oleh guru piket / kepala sekolah.'
                : 'Divalidasi oleh ' + (olehNama || 'petugas') + (pada ? ' pada ' + pada : '.') +
                  (catatan ? ' Catatan: ' + catatan : '');
        }

        const cat = slot.querySelector('.val-catatan');
        if (cat) {
            const ada = status !== 'PENDING' && !!catatan;
            cat.classList.toggle('hidden', !ada);
            const txt = cat.querySelector('.txt');
            if (txt) txt.textContent = ada ? 'Catatan validator: ' + catatan : '';
            cat.title = ada ? catatan : '';
        }
    }

    window.validasiJurnal = async function(btn) {
        const area = btn.closest('.val-area');
        if (!area) return;

        const aksi = btn.dataset.aksi;
        const idJurnal = String(area.dataset.jurnalId || '0');
        if (idJurnal === '0') return;

        let catatan = '';
        if (aksi === 'tolak') {
            if (!window.Swal) {
                showToast('Dialog belum termuat — muat ulang halaman.', false);
                return;
            }
            const hasil = await Swal.fire({
                title: 'Tolak jurnal ini?',
                html: 'Isi jurnal akan kembali ke antrean dan guru pemiliknya menerima catatan Anda.',
                input: 'textarea',
                inputPlaceholder: 'Mis. Materi pokok & refleksi masih kosong, lengkapi ya.',
                inputValidator: (v) => (v && v.trim().length >= 5)
                    ? null
                    : 'Tulis catatan minimal 5 karakter supaya jelas bagi gurunya.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f97316',
                confirmButtonText: 'Tolak jurnal',
                cancelButtonText: 'Batal'
            });
            if (!hasil.isConfirmed) return;
            catatan = String(hasil.value || '').trim();
        }

        const buttons = Array.from(area.querySelectorAll('.val-btn'));
        buttons.forEach(b => { b.disabled = true; });

        const formData = new FormData();
        formData.append('id_jurnal', idJurnal);
        formData.append('aksi', aksi);
        formData.append('catatan', catatan);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const r = await fetch(BASE + 'guru/piket/validasi_jurnal', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await r.json();
            if (res.status) {
                if (res.validasi_status) {
                    terapkanValidasi(idJurnal, res.validasi_status, res.validasi_catatan || '',
                        res.validasi_nama || '', res.validasi_pada || '');
                }
                showToast(res.message || 'Tersimpan', true);
                haptic(10);
            } else {
                showToast(res.message || 'Gagal memvalidasi', false);
                if (res.validasi_status) terapkanValidasi(idJurnal, res.validasi_status, '', '', '');
            }
        } catch (err) {
            showToast('Gagal terhubung ke server', false);
        }
        area.querySelectorAll('.val-btn').forEach(b => { b.disabled = false; });
    };

    // Satu jurnal bisa tampil di dua tempat (baris slot dan modal baca),
    // jadi status selalu diterapkan ke keduanya lewat id jurnal.
    function terapkanValidasi(idJurnal, status, catatan, olehNama, pada) {
        if (!VALIDASI_META[status]) return;
        document.querySelectorAll('.slot-item[data-jurnal-id="' + idJurnal + '"]')
            .forEach(slot => renderValArea(slot, status, catatan, olehNama, fmtWaktu(pada)));

        const modalArea = document.querySelector('#modalJurnalValidasi .val-area');
        if (modalArea && String(modalArea.dataset.jurnalId) === String(idJurnal)) {
            modalArea.dataset.validasi = status;
            const actions = modalArea.querySelector('.val-actions');
            if (actions) actions.innerHTML = valActionsHtml(status, areaBolehNilai(modalArea));
            renderJdValidasi(status, catatan, olehNama, fmtWaktu(pada));
        }

        applyFilters();
    }

    document.querySelectorAll('.val-area').forEach(area => {
        const actions = area.querySelector('.val-actions');
        if (!actions) return;
        // Area di modal baca diisi saat jurnal dibuka (status & kepemilikan
        // belum diketahui sebelumnya).
        actions.innerHTML = area.closest('#modalJurnalValidasi')
            ? ''
            : valActionsHtml(area.dataset.validasi, areaBolehNilai(area));
        actions.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-aksi]');
            if (btn) window.validasiJurnal(btn);
        });
    });

    // ── Baca isi jurnal sebelum memvalidasi ───────────────
    const jdLoadingEl = document.getElementById('jdLoading');
    const jdLoadingHtml = jdLoadingEl ? jdLoadingEl.innerHTML : '';

    function renderJdValidasi(status, catatan, olehNama, pada) {
        const el = document.getElementById('jdValidasi');
        if (!el) return;
        const meta = VALIDASI_META[status];
        if (!meta) {
            el.classList.add('hidden');
            return;
        }
        el.classList.remove('hidden');
        el.className = 'rounded-xl px-3 py-2 text-[11px] font-bold leading-snug flex items-start gap-1.5 ' + (
            status === 'APPROVED' ? 'text-emerald-700 bg-emerald-50 border border-emerald-200'
                : status === 'REJECTED' ? 'text-orange-700 bg-orange-50 border border-orange-200'
                    : 'text-slate-600 bg-slate-50 border border-slate-200');
        el.innerHTML = '<i class="bi ' + meta.icon + ' mt-0.5 shrink-0"></i><span>' +
            (status === 'PENDING'
                ? 'Belum divalidasi. Baca isinya, lalu setujui atau tolak.'
                : meta.text + ' oleh ' + escapeHtml(olehNama || 'guru piket / kepala sekolah') +
                  (pada ? ' · ' + escapeHtml(pada) : '') +
                  (catatan ? '<br>Catatan: ' + escapeHtml(catatan) : '')) +
            '</span>';
    }

    function jdSetText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = (value && String(value).trim() !== '') ? String(value) : '-';
    }

    window.bukaJurnal = async function (id) {
        const modal = document.getElementById('modalJurnalValidasi');
        if (!modal || !id) return;

        const area = modal.querySelector('.val-area');
        if (area) {
            const sumber = document.querySelector('.slot-item[data-jurnal-id="' + id + '"] .val-area');
            area.dataset.jurnalId = String(id);
            area.dataset.milikSendiri = (sumber && sumber.dataset.milikSendiri) || area.dataset.milikSendiri || '0';
            area.dataset.validasi = (sumber && VALIDASI_META[sumber.dataset.validasi]) ? sumber.dataset.validasi : 'PENDING';
            const actions = area.querySelector('.val-actions');
            if (actions) actions.innerHTML = valActionsHtml(area.dataset.validasi, areaBolehNilai(area));
        }
        const banner = document.getElementById('jdValidasi');
        if (banner) banner.classList.add('hidden');
        document.getElementById('jdBody')?.classList.add('hidden');
        document.getElementById('jdTidakWrap')?.classList.add('hidden');
        document.getElementById('jdMeta').textContent = 'Memuat detail...';
        if (jdLoadingEl) {
            jdLoadingEl.innerHTML = jdLoadingHtml;
            jdLoadingEl.classList.remove('hidden');
        }
        twModalShow(modal);

        try {
            const r = await fetch(BASE + 'guru/piket/laporan/jurnal/' + id, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            const json = await r.json();
            if (!json || !json.data) throw new Error('Format respon tidak valid');

            const d = json.data;
            document.getElementById('jdMeta').textContent =
                (d.tanggal || '') + (d.jam_ke ? ' · Jam ke-' + d.jam_ke : '');
            jdSetText('jdGuru', d.nama_guru);
            jdSetText('jdMapel', d.nama_mapel);
            jdSetText('jdKelas', d.nama_kelas);

            let waktu = d.jam_ke ? 'Jam ke-' + d.jam_ke : '-';
            if (d.jam_mulai && d.jam_selesai) {
                waktu += ' (' + String(d.jam_mulai).slice(0, 5) + ' – ' + String(d.jam_selesai).slice(0, 5) + ')';
            }
            jdSetText('jdWaktu', waktu);
            jdSetText('jdMateri', d.materi_pokok);
            jdSetText('jdTujuan', d.tujuan_pembelajaran);
            jdSetText('jdModelMedia', [d.model_belajar, d.media_alat].filter(Boolean).join(' • '));
            jdSetText('jdKegiatan', d.kegiatan_belajar);
            jdSetText('jdAsesmen', d.jenis_asesmen);
            jdSetText('jdRefleksi', d.refleksi);
            jdSetText('jdTindak', d.tindak_lanjut);
            jdSetText('jdCatatanKhusus', d.catatan_khusus);

            jdSetText('jdTotal', String(d.jumlah_siswa_total ?? 0));
            jdSetText('jdHadir', String(d.jumlah_hadir ?? 0));
            jdSetText('jdTidak', String(d.jumlah_tidak_hadir ?? 0));

            const tidakHadir = Array.isArray(d.siswa_tidak_hadir) ? d.siswa_tidak_hadir : [];
            const wrap = document.getElementById('jdTidakWrap');
            if (tidakHadir.length && wrap) {
                wrap.classList.remove('hidden');
                document.getElementById('jdTidakList').innerHTML = tidakHadir
                    .map(n => '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700">' +
                        escapeHtml(n) + '</span>')
                    .join('');
            }

            const status = VALIDASI_META[d.validasi_status] ? d.validasi_status : 'PENDING';
            if (area) {
                area.dataset.validasi = status;
                // Sumber kebenaran kepemilikan: id_guru dari server, bukan dataset slot.
                area.dataset.milikSendiri = (GURU_SENDIRI > 0 && Number(d.id_guru) === GURU_SENDIRI) ? '1' : '0';
                const actions = area.querySelector('.val-actions');
                if (actions) actions.innerHTML = valActionsHtml(status, areaBolehNilai(area));
            }
            renderJdValidasi(status, String(d.validasi_catatan || '').trim(), '', fmtWaktu(d.validasi_pada));

            jdLoadingEl?.classList.add('hidden');
            document.getElementById('jdBody')?.classList.remove('hidden');
        } catch (err) {
            document.getElementById('jdMeta').textContent = 'Gagal memuat';
            if (jdLoadingEl) {
                jdLoadingEl.innerHTML = '<div class="text-center py-6">' +
                    '<i class="bi bi-exclamation-circle text-3xl text-rose-500"></i>' +
                    '<div class="mt-2 font-bold text-xs text-rose-700">Gagal memuat jurnal</div>' +
                    '<div class="text-[11px] text-slate-400 mt-1">' + escapeHtml(err.message) + '</div></div>';
            }
        }
    };

    // ── Instant Search across slots ───────────────────────
    const searchInput = document.getElementById('searchSlotInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterState.q = this.value.toLowerCase().trim();
            applyFilters();
        });
    }

    // ── Deep link filter temuan dari dashboard piket ──────
    const initialIssue = <?= json_encode($issue_filter ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    if (initialIssue) {
        const issueChip = document.querySelector('.issue-chip[data-issue="' + initialIssue + '"]');
        if (issueChip) {
            window.setIssueFilter(issueChip);
            // Chip bisa berada di luar layar karena barisnya digeser mendatar.
            issueChip.scrollIntoView({ inline: 'center', block: 'nearest' });
        }
    }

    // ── Set All Slots in Class to Present (Hadir) ─────────
    window.setAllClassPresent = async function(kelasId) {
        const card = document.getElementById('class-card-' + kelasId);
        if (!card) return;

        const slots = card.querySelectorAll('.slot-item');
        if (!slots.length) return;

        if (window.Swal) {
            const result = await Swal.fire({
                title: 'Set Semua Hadir?',
                text: 'Semua jam pelajaran pada kelas ini akan ditandai Hadir.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Ya, Tandai Hadir',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
        }

        let okCount = 0;
        let failCount = 0;
        for (const slot of slots) {
            const radioH = slot.querySelector('input[value="H"]');
            if (!radioH) continue;
            radioH.checked = true;
            try {
                const res = await saveRow(slot, 'H');
                if (res && res.status) okCount++;
                else failCount++;
            } catch (e) {
                failCount++;
            }
        }
        if (failCount === 0) showToast(`${okCount} slot berhasil diset Hadir`, true);
        else showToast(`${okCount} tersimpan, ${failCount} gagal — periksa yang bertanda merah`, false);
    };

    // ── Autofill from Jurnal & Absensi ────────────────────
    window.triggerAutofill = async function() {
        if (window.Swal) {
            const confirmRes = await Swal.fire({
                title: 'Tarik Presensi Otomatis?',
                text: 'Data guru yang sudah mengisi Jurnal KBM atau Presensi Masuk akan disinkronkan ke jadwal mengajar hari ini.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Ya, Sinkronkan',
                cancelButtonText: 'Batal'
            });
            if (!confirmRes.isConfirmed) return;
        }

        showToast('Menyinkronkan presensi...', true);

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
                showToast(json.message || 'Gagal sinkronisasi', false);
            }
        } catch (err) {
            showToast('Gagal menghubungkan ke server', false);
        }
    };

});
</script>
<?= $this->endSection() ?>
