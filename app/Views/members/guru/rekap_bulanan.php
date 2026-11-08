<?= $this->extend('layouts/mobile_tw') ?>
<?= $this->section('styles') ?>
<style>
    .rekap-hero {
        background: linear-gradient(135deg, #059669 0%, #0d9488 50%, #065f46 100%);
        padding: calc(20px + var(--safe-area-inset-top, 0px)) 20px 48px;
        position: relative;
        overflow: hidden;
    }
    .rekap-hero::before {
        content: '';
        position: absolute;
        top: -30%; right: -15%;
        width: 240px; height: 240px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        pointer-events: none;
    }
    .rekap-hero::after {
        content: '';
        position: absolute;
        bottom: -25%; left: -10%;
        width: 180px; height: 180px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        pointer-events: none;
    }
    .rekap-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
        transition: all 0.2s ease;
    }
    .stat-pill {
        border-radius: 14px;
        transition: transform 0.15s ease;
    }
    .stat-pill:active {
        transform: scale(0.97);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// Guru information & avatar
$guruName = $guru->nama_guru ?? ($user->first_name . ' ' . $user->last_name);
$guruNip = $guru->nip ?? $guru->nuptk ?? '-';
$avatarSrc = \App\Services\AvatarService::url($guru->foto ?? '')
    ?? \App\Services\AvatarService::url($user->foto ?? '')
    ?? \App\Services\AvatarService::inisial($guruName, 128, '#047857');

// Previous & Next navigation
$prevB = (int)$bulan - 1;
$prevY = (int)$tahun;
if ($prevB < 1) { $prevB = 12; $prevY--; }
$prevUrl = site_url("guru/reward/monthly-report/{$prevB}/{$prevY}");

$nextB = (int)$bulan + 1;
$nextY = (int)$tahun;
if ($nextB > 12) { $nextB = 1; $nextY++; }
$isNextFuture = ($nextY > (int)date('Y')) || ($nextY == (int)date('Y') && $nextB > (int)date('m'));
$nextUrl = site_url("guru/reward/monthly-report/{$nextB}/{$nextY}");

// Attendance calculations
$totalHari = (int)($attStats->total_hari ?? 0);
$ontime    = (int)($attStats->ontime ?? 0);
$telat     = (int)($attStats->telat ?? 0);
$alpha     = (int)($attStats->alpha ?? 0);
$izinSakit = (int)($attStats->izin_sakit ?? 0);
$dinas     = (int)($attStats->dinas ?? 0);
$hadirTotal = $ontime + $telat + $dinas;
$persenHadir = $totalHari > 0 ? round(($hadirTotal / $totalHari) * 100) : 0;

// Score evaluation
$score = (float)($monthlyScore['score'] ?? 0);
if ($score >= 90) {
    $scoreBadge = ['label' => 'Sangat Baik', 'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'];
} elseif ($score >= 75) {
    $scoreBadge = ['label' => 'Baik', 'class' => 'bg-teal-100 text-teal-700 border-teal-200'];
} elseif ($score >= 60) {
    $scoreBadge = ['label' => 'Cukup', 'class' => 'bg-amber-100 text-amber-700 border-amber-200'];
} else {
    $scoreBadge = ['label' => 'Perlu Evaluasi', 'class' => 'bg-rose-100 text-rose-700 border-rose-200'];
}

$diffPoin = $currentPoin - $prevPoin;
$sisaDendaBulan = max(0, $dendaTotal - $voucherTotal);
?>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Hero Header                                         -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="rekap-hero text-white">
    <!-- Action Bar -->
    <div class="relative z-10 flex items-center justify-between mb-5">
        <a href="<?= site_url('guru/reward') ?>" 
           class="w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 active:scale-95 transition-all flex items-center justify-center text-white no-underline shadow-sm">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold tracking-wide m-0 text-white flex items-center gap-2">
            <i class="bi bi-file-earmark-bar-graph text-teal-200"></i> Rekap Performa
        </h1>
        <a href="<?= site_url('presensi') ?>" 
           class="w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 active:scale-95 transition-all flex items-center justify-center text-white no-underline shadow-sm"
           title="Presensi Hari Ini">
            <i class="bi bi-qr-code-scan text-base"></i>
        </a>
    </div>

    <!-- Teacher Identity -->
    <div class="relative z-10 flex items-center gap-3.5 mb-5 bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/15">
        <img src="<?= esc($avatarSrc, 'attr') ?>" 
             alt="<?= esc($guruName) ?>"
             class="w-12 h-12 rounded-full object-cover border-2 border-white/60 shadow-md flex-shrink-0"
             onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::cadangan(), 'attr') ?>'">
        <div class="min-w-0 flex-1">
            <div class="font-bold text-white text-sm truncate leading-tight"><?= esc($guruName) ?></div>
            <div class="text-xs text-teal-100 opacity-90 mt-0.5 truncate">NIP: <?= esc($guruNip) ?></div>
        </div>
        <div class="flex-shrink-0">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-400/25 border border-emerald-300/40 text-white">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                Guru
            </span>
        </div>
    </div>

    <!-- Month Navigator Bar -->
    <div class="relative z-10 flex items-center justify-between bg-black/20 backdrop-blur-md rounded-2xl p-1.5 border border-white/10">
        <a href="<?= $prevUrl ?>" 
           class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 active:scale-90 flex items-center justify-center text-white no-underline transition-all"
           title="Bulan Sebelumnya">
            <i class="bi bi-chevron-left text-sm"></i>
        </a>

        <div class="flex items-center gap-2 font-bold text-sm tracking-wide text-white">
            <i class="bi bi-calendar3 text-teal-200 text-xs"></i>
            <span><?= $namaBulan ?> <?= $tahun ?></span>
        </div>

        <?php if ($isNextFuture): ?>
        <button disabled class="w-9 h-9 rounded-xl bg-white/5 opacity-30 flex items-center justify-center text-white/50 cursor-not-allowed">
            <i class="bi bi-chevron-right text-sm"></i>
        </button>
        <?php else: ?>
        <a href="<?= $nextUrl ?>" 
           class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 active:scale-90 flex items-center justify-center text-white no-underline transition-all"
           title="Bulan Selanjutnya">
            <i class="bi bi-chevron-right text-sm"></i>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Main Content Area                                   -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="px-4 -mt-6 pb-28 relative z-20 space-y-4">

    <!-- Primary Metric Cards (Overlapping) -->
    <div class="grid grid-cols-2 gap-3">
        <!-- Card 1: Total Poin -->
        <div class="rekap-card p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Poin</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                        <i class="bi bi-gem"></i>
                    </span>
                </div>
                <div class="text-2xl font-extrabold <?= $currentPoin >= 0 ? 'text-emerald-600' : 'text-rose-600' ?> leading-tight">
                    <?= $currentPoin >= 0 ? '+' : '' ?><?= number_format($currentPoin, 1) ?>
                </div>
                <div class="text-[11px] text-slate-400 font-medium mt-0.5">Poin Bulan Ini</div>
            </div>

            <!-- Comparison pill -->
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <span class="text-slate-400">Vs Bln Lalu:</span>
                <?php if ($diffPoin > 0): ?>
                    <span class="font-bold text-emerald-600 flex items-center gap-0.5">
                        <i class="bi bi-arrow-up-short text-sm"></i>+<?= number_format($diffPoin, 1) ?>
                    </span>
                <?php elseif ($diffPoin < 0): ?>
                    <span class="font-bold text-rose-600 flex items-center gap-0.5">
                        <i class="bi bi-arrow-down-short text-sm"></i><?= number_format($diffPoin, 1) ?>
                    </span>
                <?php else: ?>
                    <span class="font-medium text-slate-400">Tetap (0.0)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card 2: Skor Disiplin -->
        <div class="rekap-card p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kedisiplinan</span>
                    <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm font-bold">
                        <i class="bi bi-speedometer2"></i>
                    </span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-extrabold text-slate-800 leading-tight">
                        <?= number_format($score, 1) ?>
                    </span>
                    <span class="text-xs font-bold text-slate-400">/ 100</span>
                </div>
                <div class="text-[11px] text-slate-400 font-medium mt-0.5">Indeks Kehadiran</div>
            </div>

            <!-- Performance badge -->
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Status:</span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border <?= $scoreBadge['class'] ?>">
                    <?= $scoreBadge['label'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Denda & Voucher Notification Card (Conditional) -->
    <?php if ($dendaTotal > 0 || $voucherTotal > 0): ?>
    <div class="rekap-card p-4 bg-gradient-to-br from-amber-50/80 to-rose-50/50 border border-amber-200/70">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-amber-900 m-0">Ringkasan Denda Bulanan</h2>
                    <?php if ($sisaDendaBulan <= 0 && $dendaTotal > 0): ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                            Lunas Terbayar
                        </span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-amber-200/50 text-xs">
                    <div>
                        <div class="text-slate-500 text-[11px]">Total Denda:</div>
                        <div class="font-bold text-rose-600">Rp <?= number_format($dendaTotal, 0, ',', '.') ?></div>
                    </div>
                    <?php if ($voucherTotal > 0): ?>
                    <div>
                        <div class="text-slate-500 text-[11px]">Voucher Dipakai:</div>
                        <div class="font-bold text-emerald-600">-Rp <?= number_format($voucherTotal, 0, ',', '.') ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($sisaDendaBulan > 0): ?>
                <div class="mt-3 flex items-center justify-between pt-2 border-t border-amber-200/50">
                    <div>
                        <span class="text-[11px] text-slate-500">Sisa Tanggungan:</span>
                        <div class="text-sm font-black text-rose-700">Rp <?= number_format($sisaDendaBulan, 0, ',', '.') ?></div>
                    </div>
                    <a href="<?= site_url('presensi/rekap-denda') ?>" 
                       class="px-3 py-1.5 rounded-xl bg-amber-600 text-white text-xs font-bold no-underline hover:bg-amber-700 active:scale-95 transition-all shadow-sm flex items-center gap-1.5">
                        Bayar Denda <i class="bi bi-arrow-right text-[11px]"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attendance Overview Card -->
    <div class="rekap-card p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm font-bold">
                    <i class="bi bi-person-check"></i>
                </span>
                <h2 class="text-sm font-bold text-slate-800 m-0">Statistik Kehadiran</h2>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-teal-50 text-teal-700 border border-teal-100">
                <?= $persenHadir ?>% Hadir
            </span>
        </div>

        <!-- Visual Progress Proportion Bar -->
        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden flex mb-4">
            <?php if ($totalHari > 0): ?>
                <div style="width: <?= ($ontime / $totalHari) * 100 ?>%" class="bg-emerald-500 h-full" title="Ontime"></div>
                <div style="width: <?= ($telat / $totalHari) * 100 ?>%" class="bg-amber-400 h-full" title="Terlambat"></div>
                <div style="width: <?= ($dinas / $totalHari) * 100 ?>%" class="bg-indigo-500 h-full" title="Dinas"></div>
                <div style="width: <?= ($izinSakit / $totalHari) * 100 ?>%" class="bg-sky-400 h-full" title="Izin/Sakit"></div>
                <div style="width: <?= ($alpha / $totalHari) * 100 ?>%" class="bg-rose-500 h-full" title="Alpha"></div>
            <?php else: ?>
                <div class="w-full bg-slate-200 h-full"></div>
            <?php endif; ?>
        </div>

        <!-- 6-Grid Stats -->
        <div class="grid grid-cols-3 gap-2">
            <!-- Tepat Waktu -->
            <div class="stat-pill p-3 bg-emerald-50/70 border border-emerald-100/80 text-center">
                <div class="text-lg font-black text-emerald-700 leading-tight"><?= $ontime ?></div>
                <div class="text-[11px] font-semibold text-emerald-600 mt-0.5">Tepat Waktu</div>
            </div>

            <!-- Terlambat -->
            <div class="stat-pill p-3 bg-amber-50/70 border border-amber-100/80 text-center">
                <div class="text-lg font-black text-amber-600 leading-tight"><?= $telat ?></div>
                <div class="text-[11px] font-semibold text-amber-600 mt-0.5">Terlambat</div>
            </div>

            <!-- Alpha -->
            <div class="stat-pill p-3 bg-rose-50/70 border border-rose-100/80 text-center">
                <div class="text-lg font-black text-rose-600 leading-tight"><?= $alpha ?></div>
                <div class="text-[11px] font-semibold text-rose-600 mt-0.5">Alpha</div>
            </div>

            <!-- Izin & Sakit -->
            <div class="stat-pill p-3 bg-sky-50/70 border border-sky-100/80 text-center">
                <div class="text-lg font-black text-sky-600 leading-tight"><?= $izinSakit ?></div>
                <div class="text-[11px] font-semibold text-sky-600 mt-0.5">Izin / Sakit</div>
            </div>

            <!-- Dinas Luar -->
            <div class="stat-pill p-3 bg-indigo-50/70 border border-indigo-100/80 text-center">
                <div class="text-lg font-black text-indigo-600 leading-tight"><?= $dinas ?></div>
                <div class="text-[11px] font-semibold text-indigo-600 mt-0.5">Dinas Luar</div>
            </div>

            <!-- Total Hari -->
            <div class="stat-pill p-3 bg-slate-100/80 border border-slate-200/80 text-center">
                <div class="text-lg font-black text-slate-700 leading-tight"><?= $totalHari ?></div>
                <div class="text-[11px] font-semibold text-slate-500 mt-0.5">Total Hari</div>
            </div>
        </div>
    </div>

    <!-- Poin Breakdown Section -->
    <div class="rekap-card p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                    <i class="bi bi-clock-history"></i>
                </span>
                <h2 class="text-sm font-bold text-slate-800 m-0">Rincian Perolehan Poin</h2>
            </div>
            <span class="text-xs text-slate-400 font-medium">Bulan <?= $namaBulan ?></span>
        </div>

        <?php if (!empty($poinBreakdown)): ?>
            <?php
            $tipeConfig = [
                'HADIR'   => ['label' => 'Hadir Tepat Waktu', 'icon' => 'bi-check-circle-fill', 'color' => 'emerald', 'bg' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
                'TELAT'   => ['label' => 'Terlambat Masuk', 'icon' => 'bi-exclamation-triangle-fill', 'color' => 'amber', 'bg' => 'bg-amber-50 text-amber-600 border-amber-100'],
                'PENALTI' => ['label' => 'Pulang Lebih Awal', 'icon' => 'bi-arrow-left-circle-fill', 'color' => 'rose', 'bg' => 'bg-rose-50 text-rose-600 border-rose-100'],
                'REWARD'  => ['label' => 'Pulang Akhir / Lembur', 'icon' => 'bi-star-fill', 'color' => 'emerald', 'bg' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
                'ALPA'    => ['label' => 'Alpha / Tanpa Keterangan', 'icon' => 'bi-x-circle-fill', 'color' => 'rose', 'bg' => 'bg-rose-50 text-rose-600 border-rose-100'],
                'BONUS'   => ['label' => 'Bonus Prestasi Skor', 'icon' => 'bi-trophy-fill', 'color' => 'indigo', 'bg' => 'bg-indigo-50 text-indigo-600 border-indigo-100'],
                'REDEEM'  => ['label' => 'Tukar Reward Katalog', 'icon' => 'bi-gift-fill', 'color' => 'purple', 'bg' => 'bg-purple-50 text-purple-600 border-purple-100'],
                'CLAIMED' => ['label' => 'Voucher Dipakai', 'icon' => 'bi-ticket-perforated-fill', 'color' => 'teal', 'bg' => 'bg-teal-50 text-teal-600 border-teal-100'],
                'REFUND'  => ['label' => 'Pengembalian Poin', 'icon' => 'bi-arrow-repeat', 'color' => 'sky', 'bg' => 'bg-sky-50 text-sky-600 border-sky-100'],
                'IZIN'    => ['label' => 'Izin Resmi', 'icon' => 'bi-file-text-fill', 'color' => 'sky', 'bg' => 'bg-sky-50 text-sky-600 border-sky-100'],
                'SAKIT'   => ['label' => 'Sakit dengan Surat', 'icon' => 'bi-heart-pulse-fill', 'color' => 'sky', 'bg' => 'bg-sky-50 text-sky-600 border-sky-100'],
            ];
            ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($poinBreakdown as $b): ?>
                    <?php
                    $conf = $tipeConfig[$b->tipe] ?? ['label' => $b->tipe, 'icon' => 'bi-dot', 'color' => 'slate', 'bg' => 'bg-slate-50 text-slate-600 border-slate-100'];
                    $isPositive = (float)$b->total >= 0;
                    ?>
                    <div class="py-3 flex items-center justify-between first:pt-1 last:pb-1">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-sm border flex-shrink-0 <?= $conf['bg'] ?>">
                                <i class="bi <?= $conf['icon'] ?>"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 truncate"><?= $conf['label'] ?></div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">
                                        <?= $b->tipe ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-medium">
                                        <?= $b->jumlah ?> kali tercatat
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 pl-3">
                            <div class="text-sm font-extrabold <?= $isPositive ? 'text-emerald-600' : 'text-rose-600' ?>">
                                <?= $isPositive ? '+' : '' ?><?= number_format((float)$b->total, 1) ?>
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold">poin</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-2.5">
                    <i class="bi bi-inbox"></i>
                </div>
                <div class="text-xs font-bold text-slate-700">Belum Ada Riwayat Poin</div>
                <div class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto">
                    Data transaksi poin untuk bulan <?= $namaBulan ?> <?= $tahun ?> belum tersedia.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Discipline Score Details -->
    <?php if (!empty($monthlyScore['detail'])): ?>
    <div class="rekap-card p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm font-bold">
                    <i class="bi bi-calculator"></i>
                </span>
                <h2 class="text-sm font-bold text-slate-800 m-0">Kalkulasi Skor Disiplin</h2>
            </div>
            <span class="text-xs font-bold text-teal-600">Max 100 Pts</span>
        </div>

        <div class="space-y-2.5 text-xs">
            <!-- Ontime Component -->
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <div>
                        <div class="font-semibold text-slate-700">Tepat Waktu</div>
                        <div class="text-[11px] text-slate-400"><?= (int)($monthlyScore['detail']['ontime'] ?? 0) ?> hari &times; 10 poin</div>
                    </div>
                </div>
                <span class="font-bold text-emerald-600">+<?= (int)($monthlyScore['detail']['ontime'] ?? 0) * 10 ?></span>
            </div>

            <!-- Late Occurrences -->
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">
                        <i class="bi bi-dash-lg"></i>
                    </span>
                    <div>
                        <div class="font-semibold text-slate-700">Keterlambatan</div>
                        <div class="text-[11px] text-slate-400"><?= (int)($monthlyScore['detail']['late'] ?? 0) ?> kali &times; 5 poin</div>
                    </div>
                </div>
                <span class="font-bold text-amber-600">-<?= (int)($monthlyScore['detail']['late'] ?? 0) * 5 ?></span>
            </div>

            <!-- Minute Penalty -->
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-xs font-bold">
                        <i class="bi bi-dash-lg"></i>
                    </span>
                    <div>
                        <div class="font-semibold text-slate-700">Durasi Menit Terlambat</div>
                        <div class="text-[11px] text-slate-400"><?= (float)($monthlyScore['detail']['total_telat_menit'] ?? 0) ?> menit &times; 0.1 poin</div>
                    </div>
                </div>
                <span class="font-bold text-rose-600">-<?= number_format((float)($monthlyScore['detail']['total_telat_menit'] ?? 0) * 0.1, 1) ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bottom Action CTA -->
    <div class="pt-2">
        <a href="<?= site_url('guru/reward') ?>" 
           class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-teal-600/20 hover:from-teal-700 hover:to-emerald-700 active:scale-98 transition-all no-underline">
            <i class="bi bi-gift text-base"></i>
            <span>Tukar Reward &amp; Voucher</span>
            <i class="bi bi-arrow-right text-sm"></i>
        </a>
    </div>

</div>
<?= $this->endSection() ?>

