<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Hero (ramping) ─────────────────────────── */
    .piket-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #0f172a 100%);
        padding: calc(14px + var(--safe-area-inset-top, 0px)) 16px 48px;
        position: relative; overflow: hidden;
    }
    .piket-hero::before {
        content: ''; position: absolute;
        top: -40%; right: -12%; width: 260px; height: 260px;
        background: radial-gradient(circle, rgb(var(--brand-600-rgb) / 0.15) 0%, transparent 70%);
        border-radius: 50%; pointer-events: none;
    }
    .piket-hero::after {
        content: ''; position: absolute;
        bottom: -30%; left: -10%; width: 220px; height: 220px;
        background: radial-gradient(circle, rgb(var(--primary-500-rgb) / 0.12) 0%, transparent 70%);
        border-radius: 50%; pointer-events: none;
    }

    /* ── Section head ───────────────────────────── */
    .section-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 10px;
    }
    .section-head-title {
        display: flex; align-items: center; gap: 8px;
    }
    .section-head-icon {
        width: 26px; height: 26px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }
    .section-head h2 {
        font-size: 13px; font-weight: 800; color: #334155; margin: 0;
    }
    .section-link {
        font-size: 11px; font-weight: 800; color: var(--brand-600);
        text-decoration: none; white-space: nowrap;
    }
    @media (hover: hover) { .section-link:hover { color: #9f1239; } }

    /* ── Action card ────────────────────────────── */
    .action-card { transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.15s ease; }
    @media (hover: hover) { .action-card:hover { border-color: #fecdd3; box-shadow: 0 4px 12px rgb(var(--brand-600-rgb) / 0.08); } }
    .action-card:active { transform: scale(0.95); }
    .action-card:focus-visible { outline: 2px solid var(--brand-600); outline-offset: 2px; }

    /* ── Locked menu (non-duty) ─────────────────── */
    .menu-locked .action-card { opacity: .55; }

    /* ── Empty state (seragam sub-halaman) ──────── */
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

    /* ── Segmented pills (selaras monitoring) ───── */
    .alpha-seg-wrap, .seg-wrap {
        display: flex; gap: 4px; padding: 4px;
        background: rgba(226,232,240,0.7); border-radius: 16px;
    }
    .alpha-seg-btn, .seg-btn {
        flex: 1; padding: 8px 4px; border-radius: 12px;
        font-size: 11px; font-weight: 800; color: #64748b;
        border: none; background: transparent; cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @media (hover: hover) { .alpha-seg-btn:hover, .seg-btn:hover { color: #0f172a; } }
    .alpha-seg-active, .seg-btn-active {
        background: #fff; color: #1e293b;
        box-shadow: 0 1px 4px rgba(0,0,0,0.10);
    }
    .alpha-seg-btn:focus-visible, .seg-btn:focus-visible { outline: 2px solid var(--brand-600); outline-offset: 1px; }

    /* ── Lipatan (progressive disclosure) ───────── */
    details.piket-fold > summary { list-style: none; cursor: pointer; }
    details.piket-fold > summary::-webkit-details-marker { display: none; }
    details.piket-fold > summary .fold-chev { transition: transform 0.2s ease; }
    details.piket-fold[open] > summary .fold-chev { transform: rotate(90deg); }

    @media (prefers-reduced-motion: reduce) {
        .action-card { transition: none; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
/* ── Pre-render calculations (tak diubah: kontrak controller tetap) ── */
$isOnDuty = (bool) ($is_on_duty ?? false);
$isKepsek = (bool) ($is_kepsek ?? false);
$isHariSekolah = (bool) ($is_hari_sekolah ?? true);
$liburKeterangan = trim((string) ($libur_keterangan ?? '')) ?: 'Libur';
$piketTanggalLabel = (string) ($piket_tanggal_label ?? '');
if (!$isOnDuty && !empty($piket_hari_ini) && !empty($guru)) {
    foreach ($piket_hari_ini as $p) {
        $sameId = isset($p->id_guru, $guru->id_guru) && (int) $p->id_guru === (int) $guru->id_guru;
        $sameName = isset($p->nama_guru, $guru->nama_guru) && $p->nama_guru === $guru->nama_guru;
        if ($sameId || $sameName) {
            $isOnDuty = true; break;
        }
    }
}
$hariList  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulanList = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tanggalIndo = $hariList[date('w')] . ', ' . (int)date('d') . ' ' . $bulanList[(int)date('m')] . ' ' . date('Y');

$guruMasuk = (int)($stats['guru_masuk'] ?? 0);
$guruBelum = (int)($stats['guru_belum'] ?? 0);
$guruTotal = $guruMasuk + $guruBelum;
$guruPersen = $guruTotal > 0 ? round(($guruMasuk / $guruTotal) * 100) : 0;

$statJurnal   = (int)($stats['jurnal'] ?? 0);
$statKejadian = (int)($stats['kejadian'] ?? 0);
$statTamu     = (int)($stats['tamu'] ?? 0);
$statSiswaAbsen = (int)($stats['siswa_absen'] ?? 0);

/* ── Ringkasan monitoring KBM (selaras halaman input_laporan) ── */
$mon = is_array($monitoring ?? null) ? $monitoring : [];
$monTotal       = (int) ($mon['total_slot'] ?? 0);
$monJurnal      = (int) ($mon['jurnal_isi'] ?? 0);
$monKosong      = (int) ($mon['jurnal_kosong'] ?? 0);
$monPersen      = (int) ($mon['persen_jurnal'] ?? 0);
$monTanpaScan   = (int) ($mon['tanpa_scan'] ?? 0);
$monPerluNilai  = (int) ($mon['perlu_validasi'] ?? 0);
$monBarClass    = $monPersen >= 80
    ? 'bg-gradient-to-r from-emerald-500 to-green-400'
    : ($monPersen >= 50 ? 'bg-gradient-to-r from-amber-500 to-yellow-400' : 'bg-gradient-to-r from-rose-500 to-red-400');

if (!function_exists('piketInitials')) {
    function piketInitials(string $name): string {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') return '?';
        $parts = explode(' ', $name);
        $i = strtoupper(substr($parts[0], 0, 1));
        if (isset($parts[1]) && $parts[1] !== '') $i .= strtoupper(substr($parts[1], 0, 1));
        return $i;
    }
}

/* ── Siswa sering tidak hadir (> batas hari) untuk ditindaklanjuti ── */
$alphaData   = $siswa_sering_alpha ?? [];
$alphaBulan  = array_values($alphaData['bulan'] ?? []);
$alphaMinggu = array_values($alphaData['minggu'] ?? []);
$alphaBatas  = (int) ($alphaData['batas'] ?? 3);
$alphaLabelBulan  = (string) ($alphaData['label_bulan'] ?? '');
$alphaLabelMinggu = (string) ($alphaData['label_minggu'] ?? '');
$alphaTotal = count($alphaBulan) + count($alphaMinggu);
$alphaPreview = array_slice($alphaBulan, 0, 3);
if (empty($alphaPreview)) $alphaPreview = array_slice($alphaMinggu, 0, 3);

$hariUrutan = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
$hariIniIndo = $hariList[date('w')];
$jurnalList = array_slice($jurnal_terbaru ?? [], 0, 3);
?>

<!-- ═══════════════════════════════════════════════ -->
<!-- Hero (ramping)                                 -->
<!-- ═══════════════════════════════════════════════ -->
<div class="piket-hero">
    <!-- Top bar -->
    <div class="relative z-10 flex items-center justify-between mb-3">
        <a href="<?= base_url('presensi') ?>"
            aria-label="Kembali ke Presensi"
            class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white no-underline hover:bg-white/20 transition-colors">
            <i class="bi bi-chevron-left text-lg"></i>
        </a>
        <div class="text-center">
            <h1 class="text-white text-sm font-extrabold m-0 leading-tight">Dashboard Piket</h1>
            <?php if (!empty($setting->nama_sekolah)): ?>
            <p class="text-white/50 text-[10px] font-bold uppercase tracking-widest m-0 mt-0.5"><?= esc($setting->nama_sekolah) ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= base_url('guru/piket/laporan') ?>"
            aria-label="Rekap Laporan"
            class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white no-underline hover:bg-white/20 transition-colors"
            title="Rekap Laporan">
            <i class="bi bi-file-earmark-bar-graph text-sm"></i>
        </a>
    </div>

    <!-- Clock & date -->
    <div class="relative z-10 text-center">
        <p class="text-white/70 text-xs m-0 mb-1 font-semibold"><?= $tanggalIndo ?></p>
        <div id="digital-clock"
            class="text-white font-extrabold text-3xl leading-none tabular-nums"
            style="letter-spacing:2px;" aria-live="off">00:00:00</div>

        <!-- Duty badge -->
        <div class="mt-2.5">
            <?php if ($isKepsek): ?>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold"
                style="background: rgb(var(--primary-500-rgb) / 0.25); color:var(--primary-300); border: 1px solid rgba(147,197,253,0.35);">
                <i class="bi bi-mortarboard-fill"></i> Akses Kepala Sekolah
            </span>
            <?php elseif ($isOnDuty): ?>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold"
                style="background: rgba(22,163,74,0.25); color:#4ade80; border: 1px solid rgba(74,222,128,0.3);">
                <i class="bi bi-shield-fill-check"></i> Anda Bertugas Hari Ini
            </span>
            <?php elseif (! $isHariSekolah): ?>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold"
                style="background: rgba(245,158,11,0.2); color:#fcd34d; border: 1px solid rgba(252,211,77,0.3);">
                <i class="bi bi-calendar-x"></i> Libur — Tidak Ada Piket
            </span>
            <?php else: ?>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold"
                style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);">
                <i class="bi bi-shield"></i> Tidak Bertugas Hari Ini
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Status hari ini (satu kartu gabungan)          -->
<!-- ═══════════════════════════════════════════════ -->
<div class="px-4 -mt-10 relative z-20 mb-5">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-200/80 px-4 py-3.5">
        <?php if ($isHariSekolah): ?>
        <a href="<?= base_url('guru/piket/input_laporan') ?>" class="flex items-center justify-between gap-2 no-underline">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 m-0">Kehadiran Guru</p>
                <p class="text-[11px] font-semibold text-slate-500 m-0 mt-0.5">Catat per jam pelajaran</p>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <span class="text-lg font-extrabold text-slate-800 leading-none tabular-nums"><?= $guruMasuk ?></span>
                <span class="text-xs font-semibold text-slate-400 leading-none">/ <?= $guruTotal ?></span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $guruPersen >= 80 ? 'bg-emerald-50 text-emerald-700' : ($guruPersen >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-600') ?>"><?= $guruPersen ?>%</span>
                <i class="bi bi-chevron-right text-slate-300 text-xs"></i>
            </div>
        </a>
        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-2.5" role="progressbar"
              aria-valuenow="<?= $guruPersen ?>" aria-valuemin="0" aria-valuemax="100"
              aria-label="Persentase kehadiran guru <?= $guruPersen ?> persen">
            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-green-400 transition-all duration-700"
                style="width: <?= $guruPersen ?>%"></div>
        </div>
        <?php else: ?>
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 m-0">Kehadiran Guru</p>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">
                <i class="bi bi-calendar-x"></i> Libur
            </span>
        </div>
        <div class="flex items-start gap-2 px-3 py-2 rounded-xl bg-amber-50/70 border border-amber-100">
            <i class="bi bi-info-circle-fill text-amber-500 text-[11px] mt-0.5"></i>
            <p class="text-[10px] text-amber-800 m-0 leading-relaxed">
                Hari ini <?= esc($liburKeterangan) ?>. Presensi guru tidak dihitung; data terakhir tersedia di Rekap Laporan.
            </p>
        </div>
        <?php endif; ?>
        <div class="grid grid-cols-4 gap-1 mt-3 pt-3 border-t border-slate-100 text-center">
            <a href="<?= base_url('guru/piket/laporan') ?>#seksi-belajar" class="no-underline py-1 rounded-xl hover:bg-blue-50/60 transition-colors">
                <span class="block text-lg font-extrabold text-blue-700 leading-none tabular-nums"><?= $statJurnal ?></span>
                <span class="block text-[9px] font-bold text-blue-500 uppercase tracking-tight mt-1">Jurnal</span>
            </a>
            <a href="<?= base_url('guru/piket/kejadian') ?>" class="no-underline py-1 rounded-xl hover:bg-amber-50/60 transition-colors">
                <span class="block text-lg font-extrabold text-amber-600 leading-none tabular-nums"><?= $statKejadian ?></span>
                <span class="block text-[9px] font-bold text-amber-500 uppercase tracking-tight mt-1">Kejadian</span>
            </a>
            <a href="<?= base_url('guru/piket/tamu') ?>" class="no-underline py-1 rounded-xl hover:bg-purple-50/60 transition-colors">
                <span class="block text-lg font-extrabold text-purple-700 leading-none tabular-nums"><?= $statTamu ?></span>
                <span class="block text-[9px] font-bold text-purple-500 uppercase tracking-tight mt-1">Tamu</span>
            </a>
            <a href="#seksi-alpha" class="no-underline py-1 rounded-xl hover:bg-rose-50/60 transition-colors">
                <span class="block text-lg font-extrabold text-rose-600 leading-none tabular-nums"><?= count($alphaBulan) ?></span>
                <span class="block text-[9px] font-bold text-rose-500 uppercase tracking-tight mt-1">Absen &gt;<?= $alphaBatas ?></span>
            </a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Monitoring KBM (ringkas)                       -->
<!-- ═══════════════════════════════════════════════ -->
<?php if ($isHariSekolah): ?>
<div class="px-4 mb-5">
    <div class="section-head">
        <div class="section-head-title">
            <span class="section-head-icon bg-blue-50 text-blue-600"><i class="bi bi-journal-text"></i></span>
            <h2>Monitoring KBM</h2>
        </div>
        <a href="<?= base_url('guru/piket/input_laporan') ?>" class="section-link inline-flex items-center gap-1">
            Detail <i class="bi bi-chevron-right text-[9px]"></i>
        </a>
    </div>

    <?php if ($monTotal === 0): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200/80">
        <i class="bi bi-calendar-x text-slate-400 flex-shrink-0"></i>
        <p class="text-[11px] text-slate-500 font-semibold m-0">Belum ada jadwal pelajaran untuk hari ini.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Cakupan jurnal mengajar -->
        <div class="px-4 pt-3.5 pb-3">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 m-0">Jurnal Pembelajaran</p>
                <span class="flex items-center gap-1.5 flex-shrink-0">
                    <span class="text-lg font-extrabold text-slate-800 leading-none tabular-nums"><?= $monJurnal ?><span class="text-[11px] font-bold text-slate-400">/<?= $monTotal ?></span></span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-extrabold <?= $monPersen >= 80 ? 'bg-emerald-50 text-emerald-700' : ($monPersen >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-600') ?>"><?= $monPersen ?>%</span>
                </span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-2" role="progressbar"
                aria-valuenow="<?= $monPersen ?>" aria-valuemin="0" aria-valuemax="100"
                aria-label="Cakupan jurnal KBM <?= $monPersen ?> persen">
                <div class="h-full rounded-full transition-all duration-700 <?= $monBarClass ?>"
                    style="width: <?= $monPersen ?>%"></div>
            </div>
        </div>

        <!-- Tiga temuan paling perlu ditindak -->
        <div class="grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100 text-center">
            <a href="<?= base_url('guru/piket/input_laporan?issue=tanpa_jurnal') ?>"
                class="no-underline px-1 py-2.5 hover:bg-rose-50/60 transition-colors"
                title="Slot jadwal yang belum memiliki jurnal mengajar">
                <span class="block text-base font-extrabold leading-none tabular-nums <?= $monKosong > 0 ? 'text-rose-600' : 'text-slate-300' ?>"><?= $monKosong ?></span>
                <span class="block text-[9px] font-bold uppercase tracking-tight mt-1 <?= $monKosong > 0 ? 'text-rose-500' : 'text-slate-400' ?>">Tanpa Jurnal</span>
            </a>
            <a href="<?= base_url('guru/piket/input_laporan?issue=tanpa_scan') ?>"
                class="no-underline px-1 py-2.5 hover:bg-amber-50/60 transition-colors"
                title="Slot berjurnal tetapi guru pelaksana belum scan masuk">
                <span class="block text-base font-extrabold leading-none tabular-nums <?= $monTanpaScan > 0 ? 'text-amber-600' : 'text-slate-300' ?>"><?= $monTanpaScan ?></span>
                <span class="block text-[9px] font-bold uppercase tracking-tight mt-1 <?= $monTanpaScan > 0 ? 'text-amber-500' : 'text-slate-400' ?>">Tanpa Scan</span>
            </a>
            <a href="<?= base_url('guru/piket/input_laporan?issue=perlu_validasi') ?>"
                class="no-underline px-1 py-2.5 hover:bg-emerald-50/60 transition-colors"
                title="Jurnal yang sudah diisi tetapi belum dinilai guru piket / kepala sekolah">
                <span class="block text-base font-extrabold leading-none tabular-nums <?= $monPerluNilai > 0 ? 'text-emerald-600' : 'text-slate-300' ?>"><?= $monPerluNilai ?></span>
                <span class="block text-[9px] font-bold uppercase tracking-tight mt-1 <?= $monPerluNilai > 0 ? 'text-emerald-600' : 'text-slate-400' ?>">Perlu Nilai</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════ -->
<!-- Tugas Hari Ini                                 -->
<!-- ═══════════════════════════════════════════════ -->
<div class="px-4 mb-5">
    <div class="section-head">
        <div class="section-head-title">
            <span class="section-head-icon bg-slate-100 text-slate-600"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <h2>Tugas Hari Ini</h2>
        </div>
    </div>
    <?php if (!$isOnDuty): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 mb-3 rounded-2xl border border-amber-200 bg-amber-50">
        <i class="bi bi-lock-fill text-amber-500 text-sm flex-shrink-0"></i>
        <p class="text-[11px] text-amber-700 font-semibold m-0 leading-snug">
            Menu terkunci — hanya petugas piket hari ini yang dapat mengaksesnya.
        </p>
    </div>
    <?php endif; ?>
    <?php
    $menuItems = [
        ['url' => 'guru/piket/input_laporan',  'label' => 'Kehadiran Guru', 'icon' => 'bi-clipboard-check',       'bg' => 'bg-violet-50', 'fg' => 'text-violet-600', 'badge' => null],
        ['url' => 'guru/piket/absensi',        'label' => 'Absensi',     'icon' => 'bi-people',                 'bg' => 'bg-rose-50',   'fg' => 'text-rose-600',   'badge' => $statSiswaAbsen > 0 ? $statSiswaAbsen : null],
        ['url' => 'guru/piket/kejadian',       'label' => 'Kejadian',    'icon' => 'bi-exclamation-triangle',  'bg' => 'bg-orange-50', 'fg' => 'text-orange-500', 'badge' => $statKejadian > 0 ? $statKejadian : null],
        ['url' => 'guru/piket/tamu',           'label' => 'Buku Tamu',   'icon' => 'bi-person-badge',           'bg' => 'bg-blue-50',   'fg' => 'text-blue-600',   'badge' => $statTamu > 0 ? $statTamu : null],
    ];
    ?>
    <div class="<?= $isOnDuty ? '' : 'menu-locked' ?> space-y-2.5">
    <div class="grid grid-cols-2 gap-2.5">
        <?php foreach ($menuItems as $mi): ?>
        <a href="<?= base_url($mi['url']) ?>"
            class="action-card relative flex items-center gap-2.5 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-3 no-underline hover:border-rose-200">
            <?php if (!empty($mi['badge'])): ?>
            <span class="absolute top-2 right-2 min-w-[20px] h-5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-extrabold inline-flex items-center justify-center">
                <?= $mi['badge'] > 99 ? '99+' : (int)$mi['badge'] ?>
            </span>
            <?php endif; ?>
            <div class="w-10 h-10 rounded-xl <?= $mi['bg'] ?> flex items-center justify-center flex-shrink-0">
                <i class="bi <?= $mi['icon'] ?> <?= $mi['fg'] ?> text-base"></i>
            </div>
            <span class="text-xs font-bold text-slate-600 leading-tight"><?= $mi['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <!-- Rekap: aksi full-width -->
    <a href="<?= base_url('guru/piket/laporan') ?>"
        class="action-card flex items-center gap-3 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-3 no-underline hover:border-teal-200">
        <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-file-earmark-bar-graph text-teal-600 text-base"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-slate-800 text-[13px] font-extrabold m-0 leading-tight">Rekap Laporan</p>
            <p class="text-slate-500 text-[11px] m-0 mt-0.5 truncate">Jurnal, selisih, rekap &amp; kirim grup</p>
        </div>
        <i class="bi bi-chevron-right text-slate-300 flex-shrink-0"></i>
    </a>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Perlu ditindaklanjuti                          -->
<!-- ═══════════════════════════════════════════════ -->
<div id="seksi-alpha" class="px-4 mb-5" style="scroll-margin-top:80px;">
    <div class="section-head">
        <div class="section-head-title">
            <span class="section-head-icon bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <h2>Perlu Ditindaklanjuti</h2>
        </div>
        <?php if ($alphaTotal > 0): ?>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 text-[10px] font-extrabold"><?= $alphaTotal ?> siswa</span>
        <?php endif; ?>
    </div>

    <?php if ($alphaTotal > 0): ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <p class="text-[11px] text-slate-500 m-0 px-4 pt-3 leading-relaxed">
            <strong class="text-slate-700">Siswa aktif</strong> absen
            <strong class="text-rose-600">lebih dari <?= $alphaBatas ?> hari</strong> — hubungi wali kelas / orang tua.
        </p>
        <div class="divide-y divide-slate-50 mt-1">
            <?php foreach ($alphaPreview as $sa):
                $tglInfo = !empty($sa['tanggal']) ? 'Tidak hadir: ' . implode(', ', array_map(static fn($t) => date('d/m', strtotime($t)), (array) $sa['tanggal'])) : '';
            ?>
            <div class="flex items-center gap-3 px-4 py-2.5" <?= $tglInfo !== '' ? 'title="' . esc($tglInfo) . '"' : '' ?>>
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-extrabold flex-shrink-0">
                    <?= esc(piketInitials($sa['nama'] ?? '?')) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 text-xs m-0 truncate"><?= esc($sa['nama'] ?? '-') ?></p>
                    <p class="text-[10px] text-slate-400 m-0 mt-0.5 truncate"><?= esc($sa['nama_kelas'] ?? '—') ?><?php if (!empty($sa['nis'])): ?> • <?= esc($sa['nis']) ?><?php endif; ?></p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-extrabold flex-shrink-0">
                    <?= (int) ($sa['alpha'] ?? 0) ?> hari
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <details class="piket-fold border-t border-slate-100">
            <summary class="flex items-center justify-center gap-1.5 px-4 py-2.5 text-[11px] font-extrabold text-rose-600">
                Lihat semua (bulan <?= count($alphaBulan) ?> • minggu <?= count($alphaMinggu) ?>)
                <i class="bi bi-chevron-right text-[10px] fold-chev"></i>
            </summary>
            <div class="px-2 pb-2">
                <div class="alpha-seg-wrap" role="tablist" aria-label="Periode ketidakhadiran">
                    <button type="button" role="tab" aria-selected="true" onclick="switchAlphaTab('bulan', this)"
                        class="alpha-seg-btn alpha-seg-active" data-atab="bulan">
                        Bulan Ini <?= count($alphaBulan) ?>
                    </button>
                    <button type="button" role="tab" aria-selected="false" onclick="switchAlphaTab('minggu', this)"
                        class="alpha-seg-btn" data-atab="minggu">
                        Minggu Ini <?= count($alphaMinggu) ?>
                    </button>
                </div>
                <div id="alpha-list-bulan" class="divide-y divide-slate-50 max-h-80 overflow-y-auto mt-1">
                    <?php if (!empty($alphaBulan)): ?>
                    <?php foreach ($alphaBulan as $sa):
                        $tglInfo = !empty($sa['tanggal']) ? 'Tidak hadir: ' . implode(', ', array_map(static fn($t) => date('d/m', strtotime($t)), (array) $sa['tanggal'])) : '';
                    ?>
                    <div class="flex items-center gap-3 px-2 py-2.5" <?= $tglInfo !== '' ? 'title="' . esc($tglInfo) . '"' : '' ?>>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-extrabold flex-shrink-0">
                            <?= esc(piketInitials($sa['nama'] ?? '?')) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 text-xs m-0 truncate"><?= esc($sa['nama'] ?? '-') ?></p>
                            <p class="text-[10px] text-slate-400 m-0 mt-0.5 truncate"><?= esc($sa['nama_kelas'] ?? '—') ?><?php if (!empty($sa['nis'])): ?> • <?= esc($sa['nis']) ?><?php endif; ?></p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-extrabold flex-shrink-0">
                            <?= (int) ($sa['alpha'] ?? 0) ?> hari
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-[11px] text-slate-400 text-center m-0 py-5">Nihil bulan ini<?= $alphaLabelBulan !== '' ? ' (' . esc($alphaLabelBulan) . ')' : '' ?>.</p>
                    <?php endif; ?>
                </div>
                <div id="alpha-list-minggu" class="hidden divide-y divide-slate-50 max-h-80 overflow-y-auto mt-1">
                    <?php if (!empty($alphaMinggu)): ?>
                    <?php foreach ($alphaMinggu as $sa):
                        $tglInfo = !empty($sa['tanggal']) ? 'Tidak hadir: ' . implode(', ', array_map(static fn($t) => date('d/m', strtotime($t)), (array) $sa['tanggal'])) : '';
                    ?>
                    <div class="flex items-center gap-3 px-2 py-2.5" <?= $tglInfo !== '' ? 'title="' . esc($tglInfo) . '"' : '' ?>>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-extrabold flex-shrink-0">
                            <?= esc(piketInitials($sa['nama'] ?? '?')) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 text-xs m-0 truncate"><?= esc($sa['nama'] ?? '-') ?></p>
                            <p class="text-[10px] text-slate-400 m-0 mt-0.5 truncate"><?= esc($sa['nama_kelas'] ?? '—') ?><?php if (!empty($sa['nis'])): ?> • <?= esc($sa['nis']) ?><?php endif; ?></p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-extrabold flex-shrink-0">
                            <?= (int) ($sa['alpha'] ?? 0) ?> hari
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-[11px] text-slate-400 text-center m-0 py-5">Nihil minggu ini<?= $alphaLabelMinggu !== '' ? ' (' . esc($alphaLabelMinggu) . ')' : '' ?>.</p>
                    <?php endif; ?>
                </div>
                <p class="text-[10px] text-slate-400 m-0 px-2 py-2 leading-relaxed">
                    Hari efektif, libur dikecualikan. Hari ini belum dihitung.
                </p>
            </div>
        </details>
    </div>

    <?php else: ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-100">
        <i class="bi bi-check-circle-fill text-emerald-500 flex-shrink-0"></i>
        <p class="text-[11px] font-bold text-emerald-700 m-0">Semua terkendali — nihil siswa &gt; <?= $alphaBatas ?> hari.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Petugas Piket                                  -->
<!-- ═══════════════════════════════════════════════ -->
<div class="px-4 mb-5">
    <div class="section-head">
        <div class="section-head-title">
            <span class="section-head-icon bg-emerald-50 text-emerald-600"><i class="bi bi-shield-fill-check"></i></span>
            <h2><?= $isHariSekolah ? 'Petugas Piket' : 'Petugas Piket Berikutnya' ?></h2>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <?php if (!empty($piket_hari_ini)): ?>
            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700"><?= count($piket_hari_ini) ?> orang</span>
            <?php endif; ?>
            <button type="button" id="btnToggleJadwal"
                class="text-[11px] font-bold text-rose-600 bg-rose-50 border border-rose-100 rounded-full px-2.5 py-1 hover:bg-rose-100 transition-colors cursor-pointer">
                Lihat Jadwal
            </button>
        </div>
    </div>

    <?php if (! $isHariSekolah): ?>
    <div class="flex items-start gap-2 px-3 py-2 mb-2 rounded-xl bg-amber-50 border border-amber-100">
        <i class="bi bi-calendar-x text-amber-500 text-[11px] mt-0.5"></i>
        <p class="text-[10px] text-amber-800 m-0 leading-relaxed">
            Hari ini <?= esc($liburKeterangan) ?>. Menampilkan jadwal piket <?= esc($piketTanggalLabel) ?>.
        </p>
    </div>
    <?php endif; ?>

    <?php if (!empty($jadwal_saya)): ?>
    <div class="rounded-2xl border border-emerald-200 shadow-sm px-4 py-3 mb-2.5"
        style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%);">
        <p class="font-bold text-emerald-800 text-xs m-0">Jadwal Piket Saya</p>
        <div class="flex flex-wrap gap-1.5 mt-2">
            <?php foreach ($jadwal_saya as $js): ?>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500 text-white shadow-sm">
                <i class="bi bi-check-circle-fill text-[9px]"></i><?= esc($js->hari) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <?php if (!empty($piket_hari_ini)): ?>
        <div class="divide-y divide-slate-50">
            <?php foreach ($piket_hari_ini as $p): ?>
            <div class="flex items-center gap-3 px-4 py-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-white text-xs font-extrabold flex-shrink-0">
                    <?= esc(piketInitials($p->nama_guru ?? '?')) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-800 text-xs m-0 leading-snug truncate"><?= esc($p->nama_guru) ?></p>
                    <p class="text-[10px] text-slate-400 m-0 mt-0.5 truncate">
                        <?= !empty($p->keterangan) ? esc($p->keterangan) : 'Petugas Piket' ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="px-4 py-5 text-center">
            <i class="bi bi-shield-x text-slate-300 text-2xl"></i>
            <p class="font-bold text-slate-500 text-[13px] m-0 mt-1.5">
                <?= $isHariSekolah ? 'Belum Ada Petugas Hari Ini' : 'Belum Ada Petugas Berikutnya' ?>
            </p>
            <p class="text-[11px] text-slate-400 mt-0.5 m-0">
                <?= $isHariSekolah
                    ? 'Jadwal petugas belum diisi untuk hari ini.'
                    : 'Jadwal petugas belum diisi untuk hari sekolah berikutnya.' ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Jadwal hari lain (hari ini sudah terwakili daftar di atas) -->
        <div id="jadwalList" class="hidden border-t border-slate-100">
            <p class="px-4 pt-2.5 m-0 text-[10px] font-bold uppercase tracking-wider text-slate-400">Jadwal hari lain</p>
            <div class="divide-y divide-slate-50">
                <?php foreach ($hariUrutan as $h): ?>
                <?php if ($h === $hariIniIndo) continue; ?>
                <?php $crew = $jadwal_mingguan[$h] ?? []; ?>
                <div class="px-4 py-2.5">
                    <span class="font-bold text-slate-700 text-[11px] uppercase tracking-wide"><?= esc($h) ?></span>
                    <?php if (!empty($crew)): ?>
                        <?php foreach ($crew as $c): ?>
                        <?php $isMe = !empty($guru) && isset($c->id_guru, $guru->id_guru) && (int) $c->id_guru === (int) $guru->id_guru; ?>
                        <div class="flex items-center gap-2.5 py-1.5">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-[11px] font-extrabold flex-shrink-0
                                <?= $isMe ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600' ?>">
                                <?= esc(piketInitials($c->nama_guru ?? '?')) ?>
                            </div>
                            <p class="flex-1 min-w-0 font-semibold text-slate-700 text-xs m-0 truncate"><?= esc($c->nama_guru) ?></p>
                            <?php if ($isMe): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-bold flex-shrink-0">
                                Anda
                            </span>
                            <?php elseif (!empty($c->keterangan)): ?>
                            <span class="text-[10px] text-slate-400 flex-shrink-0"><?= esc($c->keterangan) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-[11px] text-slate-300 m-0 py-0.5">— Belum ada petugas —</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Aktivitas Terkini (ringkas)                    -->
<!-- ═══════════════════════════════════════════════ -->
<div class="px-4 mb-5">
    <div class="section-head">
        <div class="section-head-title">
            <span class="section-head-icon bg-slate-100 text-slate-600"><i class="bi bi-activity"></i></span>
            <h2>Aktivitas Terkini</h2>
        </div>
        <a href="<?= base_url('guru/piket/laporan') ?>#seksi-detail" class="section-link">Lihat Semua</a>
    </div>

    <?php if (!empty($jurnalList)): ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-50">
            <?php foreach ($jurnalList as $j): ?>
            <div class="flex items-center gap-3 px-4 py-2.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex flex-col items-center justify-center flex-shrink-0 text-blue-700">
                    <span class="text-sm font-extrabold leading-none"><?= esc($j->jam_ke) ?></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-blue-500 mt-0.5">Jam</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-800 text-xs m-0 leading-snug truncate"><?= esc($j->nama_guru) ?></p>
                    <p class="text-[10px] text-slate-400 m-0 mt-0.5 truncate">
                        <?= esc($j->nama_kelas) ?> • <?= esc($j->nama_mapel) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php else: ?>
    <div class="empty-box">
        <div class="empty-box-icon bg-slate-100 text-slate-400"><i class="bi bi-journal-x"></i></div>
        <p class="font-bold text-slate-600 text-[13px] m-0">Belum Ada Jurnal</p>
        <p class="text-xs text-slate-400 mt-1 m-0">Jurnal mengajar akan muncul di sini secara otomatis.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- Kehadiran Guru (dilipat)                       -->
<!-- ═══════════════════════════════════════════════ -->
<div class="px-4 mb-8">
    <?php if (!empty($guru_absensi)): ?>
    <details class="piket-fold bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <summary class="flex items-center gap-2.5 px-4 py-3">
            <span class="section-head-icon bg-violet-50 text-violet-600"><i class="bi bi-person-lines-fill"></i></span>
            <span class="text-[13px] font-extrabold text-slate-700">Kehadiran Guru</span>
            <span class="ml-auto text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700"><?= $guruMasuk ?>/<?= $guruTotal ?></span>
            <i class="bi bi-chevron-right text-slate-400 text-xs fold-chev flex-shrink-0"></i>
        </summary>
        <div class="px-2 pb-2">
            <!-- Segmented tabs (selaras monitoring) -->
            <div class="seg-wrap" role="tablist" aria-label="Filter kehadiran guru">
                <button type="button" role="tab" aria-selected="true" onclick="switchTab('semua', this)" data-tab="semua"
                    class="seg-btn seg-btn-active">
                    Semua <?= count($guru_absensi) ?>
                </button>
                <button type="button" role="tab" aria-selected="false" onclick="switchTab('masuk', this)" data-tab="masuk"
                    class="seg-btn">
                    Masuk <?= $guruMasuk ?>
                </button>
                <button type="button" role="tab" aria-selected="false" onclick="switchTab('belum', this)" data-tab="belum"
                    class="seg-btn">
                    Belum <?= $guruBelum ?>
                </button>
            </div>

            <!-- Search -->
            <div class="py-2.5">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="search" id="guruSearchInput" placeholder="Cari nama guru..."
                        aria-label="Cari nama guru"
                        class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-rose-400 transition-colors">
                </div>
            </div>

            <!-- List -->
            <div class="divide-y divide-slate-50 max-h-80 overflow-y-auto border-t border-slate-100" id="guru-att-list">
                <?php foreach ($guru_absensi as $ga):
                    $sudahMasuk = !empty($ga->jam_masuk);
                    $jam = $sudahMasuk && $ga->jam_masuk
                        ? date('H:i', strtotime($ga->jam_masuk))
                        : null;
                    $jamPulang = (!empty($ga->jam_pulang) && $ga->jam_pulang !== '0000-00-00 00:00:00')
                        ? date('H:i', strtotime($ga->jam_pulang))
                        : null;
                ?>
                <div class="att-item flex items-center gap-3 px-2 py-2.5"
                    data-status="<?= $sudahMasuk ? 'masuk' : 'belum' ?>"
                    data-search="<?= esc(strtolower($ga->nama_guru ?? '')) ?>">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-extrabold flex-shrink-0
                        <?= $sudahMasuk ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-500' ?>">
                        <?= esc(piketInitials($ga->nama_guru ?? '?')) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-800 text-xs m-0 truncate"><?= esc($ga->nama_guru) ?></p>
                        <?php if (!empty($ga->nip)): ?>
                        <p class="text-[10px] text-slate-400 m-0"><?= esc($ga->nip) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($sudahMasuk): ?>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs font-extrabold text-emerald-600 m-0 leading-none"><?= $jam ?></p>
                        <?php if ($jamPulang): ?>
                        <p class="text-[9px] text-slate-400 m-0 mt-0.5">s/d <?= $jamPulang ?></p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-bold flex-shrink-0">
                        BELUM
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <p id="guruEmptyHint" class="hidden text-xs text-slate-400 text-center m-0 py-6">Tidak ada guru yang cocok dengan filter.</p>
            <a href="<?= base_url('guru/piket/input_laporan') ?>" class="flex items-center justify-center gap-1.5 py-2.5 text-[11px] font-extrabold text-rose-600 no-underline">
                Buka halaman detail <i class="bi bi-arrow-right text-[10px]"></i>
            </a>
        </div>
    </details>

    <?php else: ?>
    <div class="empty-box">
        <div class="empty-box-icon bg-slate-100 text-slate-400"><i class="bi bi-people"></i></div>
        <p class="font-bold text-slate-600 text-[13px] m-0">Data Belum Tersedia</p>
        <p class="text-xs text-slate-400 mt-1 m-0">Tidak ada data kehadiran guru untuk hari ini.</p>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    // ── Digital clock ────────────────────────────
    function updateClock() {
        var now = new Date();
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var el = document.getElementById('digital-clock');
        if (el) el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ── Tab + search filter for guru attendance ──
    var currentTab = 'semua';
    function applyGuruFilter() {
        var q = '';
        var searchEl = document.getElementById('guruSearchInput');
        if (searchEl) q = searchEl.value.toLowerCase().trim();
        var visible = 0;
        document.querySelectorAll('.att-item').forEach(function (item) {
            var status = item.dataset.status || '';
            var name = item.dataset.search || '';
            var okTab = (currentTab === 'semua' || status === currentTab);
            var okQ = (!q || name.indexOf(q) !== -1);
            var show = okTab && okQ;
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        var hint = document.getElementById('guruEmptyHint');
        var list = document.getElementById('guru-att-list');
        if (hint && list) {
            var empty = visible === 0;
            hint.classList.toggle('hidden', !empty);
            list.classList.toggle('hidden', empty);
        }
    }
    window.switchTab = function (tab, btn) {
        currentTab = tab;
        document.querySelectorAll('.seg-btn').forEach(function (t) {
            t.classList.remove('seg-btn-active');
            t.setAttribute('aria-selected', 'false');
        });
        var active = btn || document.querySelector('.seg-btn[data-tab="' + tab + '"]');
        if (active) {
            active.classList.add('seg-btn-active');
            active.setAttribute('aria-selected', 'true');
        }
        applyGuruFilter();
    };
    document.getElementById('guruSearchInput')?.addEventListener('input', applyGuruFilter);

    // ── Tab periode siswa sering tidak hadir ──
    window.switchAlphaTab = function (tab, btn) {
        document.querySelectorAll('.alpha-seg-btn').forEach(function (t) {
            t.classList.remove('alpha-seg-active');
            t.setAttribute('aria-selected', 'false');
        });
        var active = btn || document.querySelector('.alpha-seg-btn[data-atab="' + tab + '"]');
        if (active) {
            active.classList.add('alpha-seg-active');
            active.setAttribute('aria-selected', 'true');
        }
        ['bulan', 'minggu'].forEach(function (k) {
            var el = document.getElementById('alpha-list-' + k);
            if (el) el.classList.toggle('hidden', k !== tab);
        });
    };

    // ── Jadwal hari lain: collapsed by default ────
    var jadwalExpanded = false;
    var jadwalList = document.getElementById('jadwalList');
    var btnJadwal = document.getElementById('btnToggleJadwal');
    function applyJadwal() {
        if (jadwalList) jadwalList.classList.toggle('hidden', !jadwalExpanded);
        if (btnJadwal) btnJadwal.textContent = jadwalExpanded ? 'Ringkas' : 'Lihat Jadwal';
    }
    if (btnJadwal && jadwalList) {
        btnJadwal.addEventListener('click', function () {
            jadwalExpanded = !jadwalExpanded;
            applyJadwal();
        });
        applyJadwal();
    }

    // ── Menu lock for non-duty teachers ──────────
    var IS_ON_DUTY = <?= $isOnDuty ? 'true' : 'false' ?>;
    if (!IS_ON_DUTY) {
        document.querySelectorAll('.menu-locked .action-card').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Menu Terkunci',
                        text: 'Hanya petugas piket hari ini yang dapat mengakses menu ini.',
                        confirmButtonText: 'Mengerti'
                    });
                }
            });
        });
    }

    // ── Flash message (redirect guard) ─────────────
    // (Layout mobile_tw juga punya guard global dengan flag yang sama —
    //  blok ini hanya jalan bila layout belum menampilkannya.)
    if (!window.__mobileTwFlashShown) {
        <?php if (session()->getFlashdata('error')): ?>
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Akses Ditolak',
                text: '<?= addslashes(session()->getFlashdata('error')) ?>',
                confirmButtonText: 'Mengerti'
            });
        }
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '<?= addslashes(session()->getFlashdata('success')) ?>',
                confirmButtonText: 'OK'
            });
        }
        <?php endif; ?>
    }

    // ── Action card entrance (hormati reduced-motion) ──
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('.action-card').forEach(function (card, i) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(12px)';
            card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
            setTimeout(function () {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 60 + i * 45);
        });
    }
})();
</script>
<?= $this->endSection() ?>
