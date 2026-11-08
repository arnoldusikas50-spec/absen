<?= $this->extend('layouts/mobile_tw') ?>
<?= $this->section('styles') ?>
<style>
    /* ── Hero ─────────────────────────────────────────── */
    /* Ujung gradien berhenti di brand-600: brand-500 memberi <3:1 untuk teks
       putih pada preset terang (emerald/sunset/ocean). */
    .reward-hero {
        background: linear-gradient(135deg, var(--brand-800) 0%, var(--brand-700) 50%, var(--brand-600) 100%);
        padding: calc(20px + var(--safe-area-inset-top, 0px)) 20px 72px;
        position: relative;
        overflow: hidden;
    }
    .reward-hero::before {
        content: '';
        position: absolute;
        top: -40%; right: -12%;
        width: 260px; height: 260px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
        pointer-events: none;
    }
    .reward-hero::after {
        content: '';
        position: absolute;
        bottom: -30%; left: -10%;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
        pointer-events: none;
    }

    /* ── Tab bar ──────────────────────────────────────── */
    .tab-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
        background: white;
        border-bottom: 1px solid #f1f5f9;
        position: sticky;
        /* Saat menempel, berhenti di bawah status bar (PWA berponi) tanpa
           menambah tinggi bar ketika masih di alur normal. */
        top: var(--safe-area-inset-top, 0px);
        z-index: 100;
        padding: 8px 12px;
    }
    .tab-bar button {
        display: flex; flex-direction: column; align-items: center; gap: 3px;
        padding: 8px 4px;
        border: none; background: transparent; cursor: pointer;
        border-radius: 12px;
        font-size: 9px; font-weight: 700;
        color: #94a3b8;
        transition: background 0.15s, color 0.15s;
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .tab-bar button i { font-size: 1.1rem; }
    .tab-bar button.active,
    .tab-bar button[aria-selected="true"] {
        background: var(--brand-50);
        color: var(--brand-700);
    }
    .tab-bar button:active { transform: scale(0.95); }
    .tab-bar button:focus-visible {
        outline: 2px solid var(--brand-600);
        outline-offset: -2px;
    }

    /* Panel tab: jarak aman saat digulir ke atas agar tidak tertutup tab bar sticky. */
    [role="tabpanel"] { scroll-margin-top: 76px; }

    /* ── Poin history item ────────────────────────────── */
    .history-item { transition: background 0.12s; }
    .history-item:active { background: #f8fafc; }

    /* ── Leaderboard items ────────────────────────────── */
    .leader-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .leader-initials {
        width: 36px; height: 36px; border-radius: 50%;
        background: var(--brand-100); color: var(--brand-700);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800; flex-shrink: 0;
    }

    /* ── Reward card press ────────────────────────────── */
    .reward-card { transition: box-shadow 0.15s, transform 0.12s; }
    .reward-card:active { transform: scale(0.985); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$sisaDenda      = (float)($dendaInfo['sisa_denda']       ?? 0);
$totalDenda     = (float)($dendaInfo['total_denda']      ?? 0);
$totalBayar     = (float)($dendaInfo['total_dibayar']    ?? 0);
$potonganVoucher= (float)($dendaInfo['potongan_voucher'] ?? 0);

// Nama bulan versi Indonesia (date('F') selalu Inggris).
$namaBulanId = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$namaBulanSingkatId = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
];
$labelBulanIni = ($namaBulanId[(int) date('n')] ?? date('F')) . ' ' . date('Y');
$labelBulanSingkat = ($namaBulanSingkatId[(int) date('n')] ?? date('M')) . ' ' . date('Y');
?>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Hero                                               -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="reward-hero">
    <!-- Back button -->
    <div class="relative z-10 flex items-center justify-between mb-6">
        <a href="<?= site_url('presensi') ?>" aria-label="Kembali ke Presensi"
            class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center text-white no-underline">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-white text-sm font-bold m-0">Poin &amp; Reward</h1>
        <a href="<?= site_url('guru/reward/monthly-report') ?>" aria-label="Lihat rekap bulanan"
            class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center text-white no-underline">
            <i class="bi bi-bar-chart-line text-sm"></i>
        </a>
    </div>

    <!-- Saldo poin -->
    <div class="relative z-10 text-center mb-2">
        <p class="text-white/85 text-xs font-semibold uppercase tracking-widest m-0">Saldo Poin Disiplin</p>
        <div class="flex items-end justify-center gap-2 mt-1">
            <span id="user-saldo-poin" class="text-5xl font-extrabold text-white leading-none" style="letter-spacing:-2px">
                <?= number_format($saldo) ?>
            </span>
            <span class="text-white/80 text-base font-bold mb-1">pts</span>
        </div>
    </div>

    <!-- Tier badge -->
    <div class="relative z-10 flex justify-center mt-3">
        <?php if ($tier): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold"
            style="background: rgba(255,255,255,0.18); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.25); color: #fff;">
            <i class="bi <?= esc($tier->badge) ?>" style="color:<?= esc($tier->warna) ?>"></i>
            <?= esc($tier->nama_tier) ?>
        </span>
        <?php else: ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold"
            style="background: rgba(255,255,255,0.18); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.25); color: #fff;">
            <i class="bi bi-star-fill text-yellow-300"></i> Pemula
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Summary Cards (overlap hero)                       -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="px-4 space-y-3 -mt-10 relative z-20 mb-4">

    <!-- Denda Status -->
    <div class="bg-white rounded-3xl shadow-lg border <?= $sisaDenda > 0 ? 'border-red-100' : 'border-emerald-100' ?> p-4">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 m-0">Status Denda <?= $tahun ?></p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span id="display-sisa-denda"
                        class="text-2xl font-extrabold leading-none <?= $sisaDenda > 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                        Rp <?= number_format($sisaDenda, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="text-[10px] text-slate-400">Total: <strong class="text-slate-600">Rp <?= number_format($totalDenda, 0, ',', '.') ?></strong></span>
                    <span class="text-slate-200">•</span>
                    <span class="text-[10px] text-slate-400">Terbayar: <strong class="text-emerald-600">Rp <?= number_format($totalBayar + $potonganVoucher, 0, ',', '.') ?></strong></span>
                </div>
            </div>
            <div class="flex-shrink-0">
                <?php if ($sisaDenda <= 0): ?>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <i class="bi bi-check-circle-fill text-emerald-600 text-xl"></i>
                </div>
                <?php else: ?>
                <div class="w-11 h-11 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl"></i>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($sisaDenda <= 0): ?>
        <div class="mt-2 pt-2 border-t border-emerald-50">
            <span class="text-[10px] font-bold text-emerald-600"><i class="bi bi-check-circle me-1"></i>Bebas denda — pertahankan terus!</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Score Card -->
    <a href="<?= site_url('guru/reward/monthly-report') ?>"
        class="flex items-center justify-between bg-brand-50 border border-brand-100 rounded-3xl p-4 no-underline transition-colors hover:bg-brand-100">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wide text-brand-700 m-0">Skor Disiplin Bulan Ini</p>
            <p class="text-2xl font-extrabold text-brand-800 m-0 leading-tight">
                <?= number_format($monthlyScore['score'] ?? 0, 1) ?>
            </p>
            <p class="text-[10px] text-brand-700 m-0"><?= esc($labelBulanIni) ?></p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-500 m-0">Bonus Poin</p>
            <p class="text-2xl font-extrabold text-emerald-600 m-0 leading-tight">+<?= number_format($monthlyScore['bonus'] ?? 0) ?></p>
            <p class="text-[10px] text-slate-400 m-0">pts bulan ini</p>
        </div>
        <i class="bi bi-chevron-right text-brand-400 ms-2"></i>
    </a>

</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Tab Bar                                            -->
<!-- ═══════════════════════════════════════════════════ -->
<div class="tab-bar" role="tablist" aria-label="Navigasi Poin dan Reward">
    <button type="button" id="tab-btn-rewards" role="tab" aria-selected="true" aria-controls="poin-rewards" data-tab="rewards" class="active" onclick="switchPoinTab('rewards', this)">
        <i class="bi bi-gift-fill" aria-hidden="true"></i> Voucher
    </button>
    <button type="button" id="tab-btn-redeem" role="tab" aria-selected="false" aria-controls="poin-redeem" data-tab="redeem" onclick="switchPoinTab('redeem', this)">
        <i class="bi bi-ticket-perforated-fill" aria-hidden="true"></i> Milik Saya
    </button>
    <button type="button" id="tab-btn-riwayat" role="tab" aria-selected="false" aria-controls="poin-riwayat" data-tab="riwayat" onclick="switchPoinTab('riwayat', this)">
        <i class="bi bi-clock-history" aria-hidden="true"></i> Riwayat
    </button>
    <button type="button" id="tab-btn-leaderboard" role="tab" aria-selected="false" aria-controls="poin-leaderboard" data-tab="leaderboard" onclick="switchPoinTab('leaderboard', this)">
        <i class="bi bi-trophy-fill" aria-hidden="true"></i> Peringkat
    </button>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Tab: Voucher Catalog                               -->
<!-- ═══════════════════════════════════════════════════ -->
<div id="poin-rewards" class="px-4 py-4 space-y-3" role="tabpanel" aria-labelledby="tab-btn-rewards" tabindex="0">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-bold text-slate-800 text-sm m-0">Katalog Voucher &amp; Reward</h2>
            <p class="text-[10px] text-slate-400 m-0">Tukar poin Anda dengan voucher menarik</p>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-brand-100 text-brand-700 text-[10px] font-bold">
            <?= count($rewards) ?> item
        </span>
    </div>

    <?php if (empty($rewards)): ?>
    <div class="text-center py-12">
        <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="bi bi-box text-slate-400 text-3xl"></i>
        </div>
        <p class="font-bold text-slate-600 text-sm m-0">Belum ada reward</p>
        <p class="text-xs text-slate-400 mt-1">Reward akan muncul setelah dikonfigurasi admin.</p>
    </div>
    <?php else: ?>
    <?php foreach ($rewards as $r):
        $nominalVal = (float)($r->nominal ?? 0);
        if ($nominalVal <= 0 && preg_match('/(\d+)\s*[kK]/', $r->nama, $m)) $nominalVal = (float)$m[1] * 1000;
        $isVoucherDenda = ($nominalVal > 0);
        $canAfford      = ($saldo >= $r->poin_dibutuhkan);
        $pctNeeded      = $r->poin_dibutuhkan > 0 ? min(100, round(($saldo / $r->poin_dibutuhkan) * 100)) : 100;
    ?>
    <div class="reward-card bg-white rounded-3xl border <?= $canAfford ? 'border-brand-100' : 'border-slate-100' ?> shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="flex items-center gap-3 px-4 pt-4 pb-3">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0
                <?= $isVoucherDenda ? 'bg-emerald-50' : 'bg-brand-50' ?>">
                <i class="bi <?= $isVoucherDenda ? 'bi-ticket-detailed-fill text-emerald-600' : 'bi-gift-fill text-brand-600' ?> text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm m-0 leading-snug"><?= esc($r->nama) ?></p>
                <?php if (!empty($r->deskripsi)): ?>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5 leading-snug"><?= esc($r->deskripsi) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($isVoucherDenda): ?>
            <div class="flex-shrink-0 text-right">
                <p class="text-[10px] text-emerald-500 font-bold m-0">NILAI</p>
                <p class="text-sm font-extrabold text-emerald-600 m-0 leading-none">Rp <?= number_format($nominalVal, 0, ',', '.') ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Badges -->
        <div class="flex items-center gap-2 px-4 pb-3">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-100">
                <i class="bi bi-coin text-[9px]"></i> <?= number_format($r->poin_dibutuhkan) ?> Poin
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-50 text-slate-500 text-[10px] font-bold border border-slate-100">
                <i class="bi bi-layers text-[9px]"></i> Sisa <?= $r->stok ?>
            </span>
            <?php if ($isVoucherDenda): ?>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100">
                <i class="bi bi-lightning-charge-fill text-[9px]"></i> Potong Denda
            </span>
            <?php endif; ?>
        </div>

        <!-- Progress bar poin -->
        <div class="px-4 pb-3">
            <div class="flex justify-between text-[10px] text-slate-400 mb-1">
                <span><?= $canAfford ? '<span class="text-emerald-600 font-bold"><i class="bi bi-check-circle-fill me-0.5"></i> Poin mencukupi</span>' : 'Kurang <strong class="text-red-500">' . number_format($r->poin_dibutuhkan - $saldo) . '</strong> poin' ?></span>
                <span><?= $pctNeeded ?>%</span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full <?= $canAfford ? 'bg-emerald-500' : 'bg-brand-400' ?> transition-all"
                    style="width: <?= $pctNeeded ?>%"></div>
            </div>
        </div>

        <!-- Action -->
        <div class="px-4 pb-4">
            <?php if ($isVoucherDenda): ?>
            <button
                type="button"
                <?= !$canAfford ? 'disabled' : '' ?>
                onclick="potongDendaLangsung(<?= $r->id ?>, '<?= esc(addslashes($r->nama)) ?>', <?= (int)$r->poin_dibutuhkan ?>, <?= (int)$nominalVal ?>)"
                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-2xl text-sm font-bold transition-all active:scale-95
                    <?= $canAfford
                        ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-md shadow-emerald-200 hover:from-emerald-600 hover:to-green-600'
                        : 'bg-slate-100 text-slate-400 cursor-not-allowed' ?>">
                <i class="bi bi-cash-coin"></i>
                <?= $canAfford ? 'Pakai Sekarang' : 'Poin Tidak Cukup' ?>
            </button>
            <?php else: ?>
            <button
                type="button"
                <?= !$canAfford ? 'disabled' : '' ?>
                onclick="redeemReward(<?= $r->id ?>, '<?= esc(addslashes($r->nama)) ?>', <?= (int)$r->poin_dibutuhkan ?>, <?= (int)$nominalVal ?>)"
                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-2xl text-sm font-bold transition-all active:scale-95
                    <?= $canAfford
                        ? 'bg-gradient-to-r from-brand-700 to-brand-600 text-white shadow-md shadow-brand-200 hover:from-brand-800 hover:to-brand-700'
                        : 'bg-slate-100 text-slate-400 cursor-not-allowed' ?>">
                <i class="bi bi-bag-check"></i>
                <?= $canAfford ? 'Tukar Sekarang' : 'Poin Tidak Cukup' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Tab: Voucher Saya                                  -->
<!-- ═══════════════════════════════════════════════════ -->
<div id="poin-redeem" class="px-4 py-4 space-y-3 hidden" role="tabpanel" aria-labelledby="tab-btn-redeem" tabindex="0">
    <div class="flex items-center justify-between mb-1">
        <div>
            <h2 class="font-bold text-slate-800 text-sm m-0">Voucher &amp; Redeem Saya</h2>
            <p class="text-[10px] text-slate-400 m-0">Riwayat penukaran voucher Anda</p>
        </div>
    </div>

    <?php if (empty($redemptions)): ?>
    <div class="text-center py-12">
        <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="bi bi-ticket-perforated text-slate-400 text-3xl"></i>
        </div>
        <p class="font-bold text-slate-600 text-sm m-0">Belum ada penukaran</p>
        <p class="text-xs text-slate-400 mt-1">Tukar poin Anda dengan voucher di tab Voucher.</p>
        <button type="button" onclick="switchPoinTab('rewards', document.getElementById('tab-btn-rewards'))"
            class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-brand-50 text-brand-700 border border-brand-100 text-xs font-bold cursor-pointer active:scale-95 transition-transform">
            <i class="bi bi-gift"></i> Lihat Katalog Voucher
        </button>
    </div>
    <?php else: ?>
    <?php foreach ($redemptions as $rd):
        $nom = (float)($rd->nominal ?? 0);
        if ($nom <= 0 && preg_match('/(\d+)\s*[kK]/', $rd->reward_nama ?? '', $m)) $nom = (float)$m[1] * 1000;

        $statusCfg = match($rd->status) {
            'APPROVED' => ['cls' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'icon' => 'bi-check-circle-fill', 'label' => 'Siap Digunakan'],
            'CLAIMED'  => ['cls' => 'bg-slate-100 text-slate-600 border-slate-200',  'icon' => 'bi-check-all',         'label' => 'Sudah Digunakan'],
            'REJECTED' => ['cls' => 'bg-red-100 text-red-700 border-red-200',        'icon' => 'bi-x-circle-fill',     'label' => 'Ditolak'],
            default    => ['cls' => 'bg-amber-100 text-amber-700 border-amber-200',  'icon' => 'bi-hourglass-split',   'label' => 'Menunggu Proses'],
        };
    ?>
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <!-- Status stripe -->
        <div class="px-4 pt-4 pb-2 flex items-start justify-between gap-3">
            <div>
                <p class="font-bold text-slate-800 text-sm m-0 leading-snug">
                    <?= esc($rd->reward_nama ?? 'Reward #' . $rd->reward_id) ?>
                </p>
                <p class="text-[10px] text-slate-400 m-0 mt-0.5">
                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y H:i', strtotime($rd->created_at)) ?>
                </p>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold flex-shrink-0 border <?= $statusCfg['cls'] ?>">
                <i class="bi <?= $statusCfg['icon'] ?> text-[9px]"></i>
                <?= $statusCfg['label'] ?>
            </span>
        </div>

        <!-- Details row -->
        <div class="px-4 pb-3 flex items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-red-50 border border-red-100">
                    <span class="text-sm font-extrabold text-red-600 leading-none">-<?= number_format($rd->poin_digunakan) ?></span>
                    <span class="text-[8px] text-red-400 font-bold uppercase">pts</span>
                </div>
                <?php if ($nom > 0): ?>
                <div class="flex flex-col items-center justify-center w-14 h-12 rounded-xl bg-emerald-50 border border-emerald-100">
                    <span class="text-[9px] text-emerald-500 font-bold uppercase leading-none">Nilai</span>
                    <span class="text-xs font-extrabold text-emerald-700 leading-tight">Rp <?= number_format($nom / 1000, 0) ?>k</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- CTA for APPROVED -->
            <?php if ($nom > 0 && $rd->status === 'APPROVED'): ?>
            <button type="button" onclick="applyVoucher(<?= $rd->id ?>, <?= (int)$nom ?>)"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-500 text-white text-xs font-bold shadow-sm shadow-emerald-200 active:scale-95 border-none cursor-pointer">
                <i class="bi bi-wallet2"></i> Bayar Denda
            </button>
            <?php elseif ($rd->status === 'CLAIMED'): ?>
            <span class="flex items-center gap-1 text-[10px] text-emerald-600 font-bold">
                <i class="bi bi-check-lg"></i> Lunas
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Tab: Riwayat Poin                                  -->
<!-- ═══════════════════════════════════════════════════ -->
<div id="poin-riwayat" class="px-4 py-4 hidden" role="tabpanel" aria-labelledby="tab-btn-riwayat" tabindex="0">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-slate-800 text-sm m-0">Riwayat Transaksi Poin</h2>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold">
            <?= count($riwayat) ?> entri
        </span>
    </div>

    <?php if (empty($riwayat)): ?>
    <div class="text-center py-12">
        <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="bi bi-clock-history text-slate-400 text-3xl"></i>
        </div>
        <p class="font-bold text-slate-600 text-sm m-0">Belum ada riwayat</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
    <?php foreach ($riwayat as $h):
        $isPlus = ($h->poin > 0);
        [$iconCls, $bgCls] = match($h->tipe) {
            'HADIR','KEHADIRAN'=> ['bi-check-circle-fill text-emerald-600', 'bg-emerald-50'],
            'TELAT'  => ['bi-clock-fill text-amber-500',   'bg-amber-50'],
            'ALPA'   => ['bi-x-circle-fill text-red-600',  'bg-red-50'],
            'IZIN'   => ['bi-card-text text-sky-600',       'bg-sky-50'],
            'SAKIT'  => ['bi-heart-pulse-fill text-sky-600','bg-sky-50'],
            'REWARD' => ['bi-star-fill text-amber-500',     'bg-amber-50'],
            'REDEEM' => ['bi-bag-check-fill text-brand-600','bg-brand-50'],
            'BONUS'  => ['bi-plus-circle-fill text-emerald-600','bg-emerald-50'],
            'PENALTI'=> ['bi-dash-circle-fill text-red-600','bg-red-50'],
            default  => ['bi-record-fill text-slate-500',  'bg-slate-50'],
        };
    ?>
    <div class="history-item flex items-center gap-3 px-4 py-3">
        <div class="w-9 h-9 rounded-xl <?= $bgCls ?> flex items-center justify-center flex-shrink-0">
            <i class="bi <?= $iconCls ?> text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-700 text-sm m-0 leading-snug truncate"><?= esc($h->keterangan) ?></p>
            <p class="text-[10px] text-slate-400 m-0 mt-0.5"><?= date('d M Y H:i', strtotime($h->created_at)) ?></p>
        </div>
        <div class="flex-shrink-0 text-right">
            <span class="text-sm font-extrabold <?= $isPlus ? 'text-emerald-600' : 'text-red-500' ?>">
                <?= $isPlus ? '+' : '' ?><?= number_format($h->poin) ?>
            </span>
            <p class="text-[8px] text-slate-400 m-0 uppercase font-bold">pts</p>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- Tab: Leaderboard                                   -->
<!-- ═══════════════════════════════════════════════════ -->
<div id="poin-leaderboard" class="px-4 py-4 hidden" role="tabpanel" aria-labelledby="tab-btn-leaderboard" tabindex="0">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="font-bold text-slate-800 text-sm m-0">Peringkat Disiplin</h2>
            <p class="text-[10px] text-slate-400 m-0">Skor tertinggi bulan ini</p>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-[10px] font-bold border border-amber-100">
            <i class="bi bi-calendar3 me-1 text-[9px]"></i><?= esc($labelBulanSingkat) ?>
        </span>
    </div>
    <div id="leaderboard-content" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex flex-col items-center justify-center py-10 text-slate-400">
            <div class="w-8 h-8 rounded-full border-2 border-brand-200 border-t-brand-600 animate-spin mb-3"></div>
            <p class="text-sm m-0">Memuat peringkat...</p>
        </div>
    </div>
</div>

<!-- bottom spacer -->
<div class="h-6"></div>

<?= $this->endSection() ?>

<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>
<?= $this->section('scripts') ?>
<script>
// ── CSRF helper ────────────────────────────────────
// Tangani kegagalan jaringan/HTTP agar modal "Memproses..." tidak
// menggantung selamanya dan guru mendapat pesan yang jelas.
function postCsrf(url, data, callback) {
    data['<?= $csrfName ?>'] = '<?= $csrfHash ?>';
    return $.ajax({ url: url, type: 'POST', data: data, dataType: 'json' })
        .done(function (res) {
            if (!res || typeof res !== 'object') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Respons server tidak dikenali. Muat ulang halaman lalu coba lagi.' });
                return;
            }
            callback(res);
        })
        .fail(function (xhr) {
            let msg = 'Koneksi gagal. Periksa jaringan lalu coba lagi.';
            if (xhr.status === 401) msg = 'Sesi Anda berakhir. Silakan masuk kembali.';
            else if (xhr.status === 403) msg = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            else if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        });
}

// ── Tab switching ──────────────────────────────────
// Hash URL menyimpan tab aktif supaya tidak kembali ke tab pertama setelah
// reload (mis. seusai menukar voucher) dan bisa di-bookmark.
const TAB_HASH = { rewards: 'voucher', redeem: 'milik-saya', riwayat: 'riwayat', leaderboard: 'peringkat' };
const TAB_BY_HASH = { 'voucher': 'rewards', 'milik-saya': 'redeem', 'riwayat': 'riwayat', 'peringkat': 'leaderboard' };

function switchPoinTab(tab, btn, opts) {
    if (!TAB_HASH[tab]) tab = 'rewards';
    const activeBtn = btn || document.getElementById('tab-btn-' + tab);
    const restore = !!(opts && (opts.restore || opts.silent));

    document.querySelectorAll('.tab-bar button').forEach(function (b) {
        const on = (b === activeBtn);
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
    });

    Object.keys(TAB_HASH).forEach(function (t) {
        const panel = document.getElementById('poin-' + t);
        if (panel) panel.classList.toggle('hidden', t !== tab);
    });

    if (!restore && window.history && history.replaceState) {
        try { history.replaceState(null, '', '#' + TAB_HASH[tab]); } catch (e) { /* hash opsional */ }
    }

    if (tab === 'leaderboard') loadLeaderboard();

    // Ketukan tab saat halaman sudah discroll: bawa panel ke posisi terlihat
    // (scroll-margin-top menjaga panel tidak tertutup tab bar sticky).
    if (!restore) {
        const target = document.getElementById('poin-' + tab);
        const bar = document.querySelector('.tab-bar');
        if (target && bar && window.scrollY > bar.offsetTop) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

// Pulihkan tab terakhir saat halaman dimuat / di-reload.
(function restorePoinTab() {
    const hash = String(window.location.hash || '').replace('#', '').toLowerCase();
    const tab = TAB_BY_HASH[hash] || 'rewards';
    switchPoinTab(tab, null, { silent: true, restore: true });
})();

// ── Potong denda langsung ─────────────────────────
function potongDendaLangsung(rewardId, rewardName, points, nominal) {
    Swal.fire({
        title: 'Potong Denda Sekarang?',
        html: `Anda akan menukarkan <strong>${points.toLocaleString('id-ID')} Poin</strong> untuk memotong denda sebesar <strong>Rp ${nominal.toLocaleString('id-ID')}</strong>.<br><br><span style="font-size:12px;color:#059669;font-weight:700">⚡ Denda langsung terpotong seketika</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Ya, Potong Denda',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        postCsrf('<?= site_url('guru/reward/potong-denda') ?>', { reward_id: rewardId }, res => {
            if (res.status) {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message })
                    .then(() => {
                        // Hasil penukaran tampil di tab "Milik Saya"; pertahankan
                        // konteks itu setelah reload.
                        location.hash = '#milik-saya';
                        location.reload();
                    });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        });
    });
}

// ── Redeem reward biasa ───────────────────────────
function redeemReward(id, name, points, nominal) {
    let htmlMsg = `Kamu akan menggunakan <strong>${points.toLocaleString('id-ID')} poin</strong> untuk reward ini.`;
    if (nominal > 0) htmlMsg += `<br><br>Voucher bernilai <strong>Rp ${nominal.toLocaleString('id-ID')}</strong> untuk pemotongan denda.`;
    Swal.fire({
        title: 'Tukarkan ' + escHtml(String(name)) + '?',
        html: htmlMsg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tukar',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Memproses Penukaran...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        postCsrf('<?= site_url('guru/reward/redeem') ?>', { reward_id: id, auto_apply: (nominal > 0 ? 1 : 0) }, res => {
            if (res.status) {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message })
                    .then(() => {
                        // Hasil penukaran tampil di tab "Milik Saya"; pertahankan
                        // konteks itu setelah reload.
                        location.hash = '#milik-saya';
                        location.reload();
                    });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        });
    });
}

// ── Apply voucher approved ────────────────────────
function applyVoucher(redemptionId, nominal) {
    Swal.fire({
        title: 'Bayar Denda dengan Voucher?',
        html: 'Voucher ini akan digunakan untuk membayar denda sebesar <strong>Rp ' + nominal.toLocaleString('id-ID') + '</strong>.<br><small style="color:#94a3b8">Proses ini tidak dapat dibatalkan.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Bayar Denda',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Menerapkan Voucher...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        postCsrf('<?= site_url('guru/reward/apply-voucher') ?>', { redemption_id: redemptionId }, res => {
            if (res.status) {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message })
                    .then(() => {
                        // Hasil penukaran tampil di tab "Milik Saya"; pertahankan
                        // konteks itu setelah reload.
                        location.hash = '#milik-saya';
                        location.reload();
                    });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        });
    });
}

// ── Leaderboard (lazy load) ───────────────────────
function loadLeaderboard() {
    const el = document.getElementById('leaderboard-content');
    if (!el || el.dataset.loaded || el.dataset.loading) return;
    el.dataset.loading = '1';
    el.setAttribute('aria-busy', 'true');

    fetch('<?= site_url('guru/reward/leaderboard') ?>?bulan=<?= date('m') ?>&tahun=<?= date('Y') ?>')
        .then(r => r.json())
        .then(res => {
            if (!res.status || !res.data || !res.data.length) {
                el.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-center">
                        <i class="bi bi-trophy text-4xl mb-2"></i>
                        <p class="text-sm m-0 font-semibold">Belum ada data peringkat</p>
                    </div>`;
                el.dataset.loaded = '1';
                return;
            }

            const medals = ['🥇','🥈','🥉'];
            let html = '<div class="divide-y divide-slate-50">';
            res.data.forEach((item, i) => {
                const name = (item.nama_lengkap || item.nama_guru || ((item.first_name||'') + ' ' + (item.last_name||''))).trim() || 'Guru';
                const initials = (name.charAt(0) + (name.split(' ')[1] || '').charAt(0)).toUpperCase() || '?';
                const score = parseFloat(item.score ?? item.total_poin ?? 0);
                const ontime = parseInt(item.ontime) || 0;
                const hadir  = parseInt(item.total_hadir) || 0;

                const rankEl = i < 3
                    ? `<span class="text-xl leading-none">${medals[i]}</span>`
                    : `<span class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-extrabold flex-shrink-0">${i+1}</span>`;

                const avatarEl = item.foto
                    ? `<img src="${item.foto}" class="leader-avatar" onerror="this.style.display='none';this.nextSibling.style.display='flex'">
                       <div class="leader-initials" style="display:none">${initials}</div>`
                    : `<div class="leader-initials">${initials}</div>`;

                const topBg = i === 0 ? 'bg-amber-50' : '';

                html += `
                <div class="flex items-center gap-3 px-4 py-3 ${topBg}">
                    <div class="flex items-center justify-center w-7 flex-shrink-0">${rankEl}</div>
                    ${avatarEl}
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-slate-800 m-0 truncate">${escHtml(name)}</p>
                        <p class="text-[10px] text-slate-400 m-0">
                            <i class="bi bi-patch-check-fill text-emerald-500 me-0.5"></i>
                            ${ontime} tepat waktu &bull; ${hadir} hadir
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-extrabold text-sm m-0 ${score >= 0 ? 'text-emerald-600' : 'text-red-500'}">
                            ${score >= 0 ? '+' : ''}${score.toFixed(1)}
                        </p>
                        <p class="text-[8px] text-slate-400 m-0 uppercase font-bold">skor</p>
                    </div>
                </div>`;
            });
            html += '</div>';
            el.innerHTML = html;
            el.dataset.loaded = '1';
        })
        .catch(() => {
            el.innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-slate-400 text-center">
                    <i class="bi bi-exclamation-triangle text-3xl mb-2"></i>
                    <p class="text-sm m-0">Gagal memuat peringkat</p>
                    <button type="button" onclick="document.getElementById('leaderboard-content').removeAttribute('data-loaded'); loadLeaderboard()"
                        class="mt-3 px-4 py-1.5 rounded-full bg-slate-100 text-slate-500 text-xs font-bold border-none cursor-pointer">
                        Coba Lagi
                    </button>
                </div>`;
        })
        .finally(() => {
            // Bersihkan penanda loading agar "Coba Lagi" bisa memuat ulang.
            delete el.dataset.loading;
            el.removeAttribute('aria-busy');
        });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
<?= $this->endSection() ?>
