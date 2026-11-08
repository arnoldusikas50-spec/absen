<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Top Bar ────────────────────────────────────────────── */
    .top-app-bar {
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
        position: sticky;
        top: 0;
        z-index: 50;
    }

    /* ── Hero section ───────────────────────────────────────── */
    .denda-hero {
        background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-600) 45%, var(--brand-500) 100%);
        color: #ffffff;
        padding: 24px 20px 32px;
        position: relative;
        overflow: hidden;
    }
    .denda-hero::before {
        content: '';
        position: absolute;
        top: -30%; right: -10%;
        width: 220px; height: 220px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        pointer-events: none;
    }
    .denda-hero::after {
        content: '';
        position: absolute;
        bottom: -40%; left: -10%;
        width: 180px; height: 180px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 50%;
        pointer-events: none;
    }

    /* ── Filter pills ───────────────────────────────────────── */
    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        white-space: nowrap;
        user-select: none;
    }
    .filter-chip.active {
        background: var(--brand-600);
        color: #ffffff;
        border-color: var(--brand-600);
        box-shadow: 0 2px 8px rgb(var(--brand-600-rgb) / 0.25);
    }
    .filter-chip:active { transform: scale(0.95); }

    /* ── Item card ──────────────────────────────────────────── */
    .denda-item {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .denda-item:active {
        transform: scale(0.985);
    }
    .alpha-badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:2px 8px; border-radius:9999px;
        font-size:10.5px; font-weight:800; letter-spacing:.1px;
        border:1px solid transparent; line-height:1.4;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Top App Bar -->
<div class="top-app-bar px-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= site_url('presensi') ?>" class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center no-underline hover:bg-slate-200 transition-colors shadow-2xs">
                <i class="bi bi-chevron-left text-base font-bold"></i>
            </a>
            <div>
                <h5 class="m-0 font-extrabold text-[16px] text-slate-900 tracking-tight leading-tight">Rekap Denda Absensi</h5>
                <span class="text-[11px] font-semibold text-slate-400">Tahun Periode <?= esc($year) ?></span>
            </div>
        </div>
        <a href="<?= site_url('presensi/denda-detail') ?>" class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full no-underline hover:bg-rose-100 transition">
            Bulan Ini
        </a>
    </div>
</div>

<!-- Hero Card -->
<div class="denda-hero text-center">
    <div class="relative z-10 max-w-sm mx-auto">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold mb-3 shadow-2xs <?= ($sisaDenda > 0) ? 'bg-white/20 text-white backdrop-blur-md' : 'bg-emerald-500 text-white' ?>">
            <i class="bi <?= ($sisaDenda > 0) ? 'bi-exclamation-circle-fill text-amber-300' : 'bi-check-circle-fill' ?>"></i>
            <span><?= ($sisaDenda > 0) ? 'Sisa Denda Belum Lunas' : 'Semua Denda Telah Lunas' ?></span>
        </div>

        <div class="text-[32px] font-black tracking-tight leading-none mb-1 drop-shadow-xs">
            Rp <?= number_format($sisaDenda, 0, ',', '.') ?>
        </div>
        <p class="text-xs text-rose-100/90 font-medium m-0">
            <?= ($sisaDenda > 0) ? 'Akumulasi sisa kewajiban denda tahun berjalan' : 'Tidak ada tanggungan denda aktif saat ini' ?>
        </p>
    </div>
</div>

<!-- Main Body -->
<div class="px-3 -mt-4 relative z-20 pb-16">
    <!-- 2 Metric KPI Cards -->
    <div class="grid grid-cols-2 gap-2.5 mb-3">
        <div class="p-3.5 rounded-2xl bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-400">Total 1 Tahun</span>
                <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <div class="text-[15px] font-extrabold text-slate-900 tracking-tight">
                Rp <?= number_format($totalYtd, 0, ',', '.') ?>
            </div>
            <span class="text-[10px] text-slate-400">Total denda tercatat</span>
        </div>

        <div class="p-3.5 rounded-2xl bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-400">Total Dibayar</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
            <div class="text-[15px] font-extrabold text-emerald-700 tracking-tight">
                Rp <?= number_format($totalDibayar, 0, ',', '.') ?>
            </div>
            <span class="text-[10px] text-emerald-600 font-semibold">Termasuk voucher poin</span>
        </div>
    </div>

    <!-- Voucher / Reward Banner Shortcut -->
    <a href="<?= site_url('guru/reward') ?>" class="block p-3.5 rounded-2xl bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-100 shadow-2xs mb-4 no-underline text-slate-800 hover:from-purple-100 hover:to-indigo-100 transition">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-lg shrink-0 shadow-xs">
                    <i class="bi bi-gift-fill"></i>
                </div>
                <div>
                    <h6 class="m-0 text-[13px] font-extrabold text-purple-950">Tukar Poin Jadi Pemotong Denda</h6>
                    <p class="m-0 text-[11px] text-purple-700/80 font-medium">Gunakan poin kedisiplinan Anda di menu Reward Guru</p>
                </div>
            </div>
            <i class="bi bi-chevron-right text-purple-400 text-xs"></i>
        </div>
    </a>

    <?php
    $baseAlphaView = (float)($setting->denda_alpha_per_hari ?? 5000);
    $alphaItems = array_filter($items, fn($it) => !empty($it['is_alpha']) || strpos(strtolower($it['jenis'] ?? ''), 'alpha') !== false);
    $cntAlpha = count($alphaItems);
    $totalAlphaNominal = array_sum(array_map(fn($it) => (!empty($it['is_alpha']) ? (float)$it['nominal'] : 0), $items));
    ?>
    <!-- Info Tarif Alpa Flat -->
    <div class="mb-4 p-3.5 rounded-2xl border bg-slate-50 border-slate-200 shadow-2xs">
        <div class="flex items-start gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-slate-400 text-white flex items-center justify-center shrink-0 text-sm shadow-xs">
                <i class="bi bi-dash-circle"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h6 class="m-0 text-[13px] font-extrabold text-slate-800">
                        Denda Alpa Tarif Flat
                    </h6>
                    <span class="text-[10px] font-bold text-slate-500"><?= $cntAlpha ?> kejadian • Total Rp <?= number_format($totalAlphaNominal, 0, ',', '.') ?></span>
                </div>
                <p class="m-0 mt-1 text-[11.5px] font-medium leading-relaxed text-slate-500">
                    Tarif alpa <b>flat Rp <?= number_format($baseAlphaView, 0, ',', '.') ?></b> per hari kerja tanpa kelipatan.
                </p>
            </div>
        </div>
    </div>

    <!-- Header & Filter Chips -->
    <div class="mb-3">
        <div class="flex items-center justify-between mb-2.5">
            <h6 class="m-0 font-extrabold text-[14px] text-slate-900 flex items-center gap-1.5">
                <i class="bi bi-clock-history text-rose-600"></i> Riwayat Harian
            </h6>
            <span class="text-[11px] font-bold text-slate-400"><?= count($items) ?> catatan</span>
        </div>

        <!-- Filter Chips Carousel -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1" style="-webkit-overflow-scrolling: touch;">
            <button type="button" class="filter-chip active" data-filter="all">Semua</button>
            <button type="button" class="filter-chip" data-filter="telat">Keterlambatan</button>
            <button type="button" class="filter-chip" data-filter="alpha">Alpha</button>
            <button type="button" class="filter-chip" data-filter="bayar">Pembayaran</button>
        </div>
        <?php if ($cntAlpha > 0): ?>
        <p class="text-[10.5px] text-slate-400 mt-1.5"><i class="bi bi-info-circle me-1"></i> Denda Alpha dihitung <b>flat</b> Rp <?= number_format($baseAlphaView, 0, ',', '.') ?> per hari.</p>
        <?php endif; ?>
    </div>

    <!-- Feed Daftar Riwayat -->
    <?php if (empty($items)): ?>
        <div class="p-8 text-center bg-white rounded-3xl border border-slate-100 shadow-xs my-4">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-3 shadow-2xs">
                <i class="bi bi-emoji-smile-fill"></i>
            </div>
            <h6 class="text-[15px] font-extrabold text-slate-800 m-0">Tidak Ada Denda!</h6>
            <p class="text-xs text-slate-400 max-w-xs mx-auto mt-1.5 leading-relaxed font-medium">
                Luar biasa! Anda selalu hadir tepat waktu dan menjaga kedisiplinan dengan sangat baik.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-2.5" id="dendaListContainer">
            <?php foreach ($items as $item): 
                $isPayment = ($item['nominal'] < 0);
                $isAlpha = !empty($item['is_alpha']) ? true : (strpos(strtolower($item['jenis'] ?? ''), 'alpha') !== false);
                $isLate = (!$isPayment && !$isAlpha);

                $categoryClass = $isPayment ? 'cat-bayar' : ($isAlpha ? 'cat-alpha' : 'cat-telat');

                $iconClass = $isPayment ? 'bi-wallet2' : ($isAlpha ? 'bi-calendar-x-fill' : 'bi-clock-history');
                $iconBg = $isPayment ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : ($isAlpha ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-amber-50 text-amber-600 border-amber-100');
                $nominalColor = $isPayment ? 'text-emerald-700' : ($isAlpha ? 'text-rose-600' : 'text-amber-600');

                $isMissing = !empty($item['is_missing']);
            ?>
            <div class="denda-item p-3.5 flex items-start gap-3 <?= $categoryClass ?>">
                <!-- Icon Box -->
                <div class="w-10 h-10 rounded-xl border flex items-center justify-center text-base shrink-0 <?= $iconBg ?>">
                    <i class="bi <?= $iconClass ?>"></i>
                </div>

                <!-- Detail Text -->
                <div class="min-w-0 grow">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-extrabold text-[13px] text-slate-900 line-clamp-1 flex items-center gap-1.5">
                            <?= esc($item['jenis']) ?>
                            <?php if ($isMissing): ?>
                                <span class="alpha-badge bg-slate-100 text-slate-500 border-slate-200" title="Estimasi — belum ada baris absensi_harian">Estimasi</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-[13px] font-black shrink-0 <?= $nominalColor ?>">
                            <?= $isPayment ? '- Rp ' . number_format(abs($item['nominal']), 0, ',', '.') : 'Rp ' . number_format($item['nominal'], 0, ',', '.') ?>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1"><i class="bi bi-calendar3 text-[10px]"></i> <?= date('d F Y', strtotime($item['tgl'])) ?></span>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <span class="text-slate-500"><?= date('l', strtotime($item['tgl'])) ?></span>
                    </div>

                    <?php if ($isAlpha): ?>
                        <!-- Alpha flat detail block -->
                        <div class="mt-2 p-2.5 rounded-xl border bg-slate-50 border-slate-200">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-extrabold text-slate-700">
                                    <?= esc($item['keterangan'] ?? 'Alpha') ?>
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($item['keterangan']) && $item['keterangan'] !== '-'): ?>
                        <div class="mt-1.5 text-[11px] text-slate-600 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100 font-medium">
                            <?= esc($item['keterangan']) ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="noFilterResult" class="hidden p-8 text-center bg-white rounded-3xl border border-slate-100 shadow-xs my-3">
            <i class="bi bi-search text-2xl text-slate-300"></i>
            <p class="text-xs text-slate-400 mt-2 font-medium">Tidak ada transaksi pada kategori yang dipilih.</p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-6 text-[11px] text-slate-400">
        Data ini disinkronkan langsung dengan sistem presensi tahun ajaran aktif.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chips = document.querySelectorAll('.filter-chip');
    const items = document.querySelectorAll('.denda-item');
    const noResult = document.getElementById('noFilterResult');

    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            chips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');
            let visibleCount = 0;

            items.forEach(item => {
                if (filter === 'all') {
                    item.style.display = 'flex';
                    visibleCount++;
                } else if (filter === 'telat' && item.classList.contains('cat-telat')) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else if (filter === 'alpha' && item.classList.contains('cat-alpha')) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else if (filter === 'bayar' && item.classList.contains('cat-bayar')) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (noResult) {
                if (visibleCount === 0) {
                    noResult.classList.remove('hidden');
                } else {
                    noResult.classList.add('hidden');
                }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>

