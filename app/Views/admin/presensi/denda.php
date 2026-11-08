<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    /* Card & Container */
    .denda-card {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .denda-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    }

    /* Soft Badges */
    .badge-tw.badge-success   { background-color: #dcfce7; color: #15803d; font-weight: 600; }
    .badge-tw.badge-danger    { background-color: #fee2e2; color: #b91c1c; font-weight: 600; }
    .badge-tw.badge-warning   { background-color: #fef3c7; color: #b45309; font-weight: 600; }
    .badge-tw.badge-info      { background-color: #e0f2fe; color: #0369a1; font-weight: 600; }
    .badge-tw.badge-purple    { background-color: #f3e8ff; color: #7e22ce; font-weight: 600; }
    .badge-tw.badge-secondary { background-color: #f1f5f9; color: #475569; font-weight: 600; }

    /* Stat Box */
    .stat-box {
        border-radius: 14px;
        padding: 1rem 1.15rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        transition: all 0.2s ease;
    }
    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    }
    .stat-box-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    /* Table Styling */
    .table-denda {
        font-size: 0.825rem;
        border-collapse: collapse;
        width: 100%;
    }
    .table-denda thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.725rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle;
        white-space: nowrap;
        padding: 0.75rem 0.65rem;
        border: 1px solid #e2e8f0;
        border-bottom: 2px solid #cbd5e1;
    }
    .table-denda tbody td {
        padding: 0.65rem 0.65rem;
        vertical-align: middle;
        white-space: nowrap;
        border: 1px solid #f1f5f9;
    }
    .table-denda tbody tr:hover td {
        background-color: #f8fafc;
    }
    /* Inner detail tables */
    .detail-table {
        font-size: 0.75rem;
        border-collapse: collapse;
        width: 100%;
    }
    .detail-table thead th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 700;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.35rem 0.5rem;
        border: 1px solid #e2e8f0;
        border-bottom: 2px solid #cbd5e1;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .detail-table tbody td {
        padding: 0.35rem 0.5rem;
        border: 1px solid #f1f5f9;
        vertical-align: middle;
        white-space: nowrap;
    }
    .detail-table tbody tr:hover td { background-color: #fafafa; }

    .num-tabular {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    .status-pill {
        font-size: 0.725rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        white-space: nowrap;
    }

    .filter-chip {
        border-radius: 9999px;
        font-size: 0.75rem;
        padding: 0.35rem 0.85rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        user-select: none;
    }
    .filter-chip:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    .filter-chip.active {
        background: var(--primary-600);
        color: #fff;
        border-color: var(--primary-600);
        box-shadow: 0 2px 8px rgb(var(--primary-600-rgb) / 0.25);
    }
    .filter-chip.active .badge-sub {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }
    .badge-sub {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.7rem;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        font-weight: 700;
    }

    /* Radio Box for Methods */
    .method-radio-box {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
        background: #fff;
        display: block;
    }
    .method-radio-box:hover {
        border-color: #cbd5e1;
    }
    .btn-check:checked + .method-radio-box.box-tunai {
        border-color: #10b981;
        background: #ecfdf5;
        color: #047857;
    }
    .btn-check:checked + .method-radio-box.box-poin {
        border-color: var(--accent-500);
        background: var(--accent-50);
        color: var(--accent-700);
    }
    .btn-check:checked + .method-radio-box.box-saldo {
        border-color: #0ea5e9;
        background: #f0f9ff;
        color: #0369a1;
    }

    .table-container-scroll {
        max-height: 68vh;
        overflow-y: auto;
    }
    .table-container-scroll::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-container-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-emerald-100 text-emerald-600 rounded-xl shadow-sm">
                    <i class="bi bi-cash-coin text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Rekapitulasi Denda Presensi') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">Pembukuan pelunasan denda secara Tunai (Kas), Potongan Poin Kedisiplinan, maupun Saldo Denda.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/denda/laporan') ?>" class="btn-tw btn-tw-sm btn-outline-primary">
                <i class="bi bi-journal-text mr-1"></i> Laporan Pembayaran
            </a>
            <a href="<?= site_url('settings?tab=poin') ?>" class="btn-tw btn-tw-sm btn-light border text-slate-600">
                <i class="bi bi-gear mr-1"></i> Aturan Denda
            </a>
            <a href="<?= site_url('admin/presensi') ?>" class="btn-tw btn-tw-sm btn-light border text-slate-600">
                <i class="bi bi-speedometer2 mr-1"></i> Monitoring
            </a>
        </div>
    </div>
</div>

<div class="content-area space-y-6">

    <?php
    $daysInMonth = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));

    // Compute summary metrics
    $totalDendaKotorAll = 0;
    $totalBayarTunaiAll = 0;
    $totalPotonganPoinAll = 0;
    $totalPotonganSaldoAll = 0;
    $totalSisaBelumAll = 0;
    $countLunas = 0;
    $countSebagian = 0;
    $countBelum = 0;
    $countNihil = 0;
    $countHasPoin = 0;
    $countHasTunai = 0;
    $countHasSaldo = 0;
    $potonganSaldoMap = $potonganSaldoMap ?? [];
    $totalGuru = count($users);

    $statsByUser = [];
    foreach ($users as $user) {
        $cntHadir = 0; $cntTelat = 0; $cntDinas = 0; $cntIzinSakit = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $rec = $mapByUser[$user->id][$date] ?? null;

            if ($rec) {
                $status = $rec->status_masuk;
                if (in_array($status, ['ONTIME', 'LATE'])) { 
                    $cntHadir++; 
                    if ($status == 'LATE') $cntTelat++; 
                }
                elseif (strpos($status, 'DINAS') !== false) { $cntDinas++; }
                elseif (in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'IZIN'])) { $cntIzinSakit++; }
            }
        }
        
        // Accurate database-driven alpha count
        $cntAlpha = $alphaMap[$user->id] ?? 0;

        $totalPoinBulanIni = 0; 
        $totalDendaHarian = 0; 
        $dendaItems = [];

        foreach ($mapByUser[$user->id] ?? [] as $tgl => $rec) {
            if (isset($rec->points)) $totalPoinBulanIni += (int)$rec->points;
            if (isset($rec->denda) && (float)$rec->denda > 0) {
                $totalDendaHarian += (float)$rec->denda;
                $dendaItems[] = [
                    'tgl' => $tgl,
                    'jam_masuk' => $rec->jam_masuk ?? '-',
                    'jam_pulang' => $rec->jam_pulang ?? '-',
                    'status_masuk' => $rec->status_masuk ?? '-',
                    'telat_menit' => $rec->telat_menit ?? 0,
                    'pulang_cepat_menit' => $rec->pulang_cepat_menit ?? 0,
                    'denda' => (float)$rec->denda,
                    'keterangan_denda' => $rec->keterangan_denda ?? ''
                ];
            }
        }

        $totalPoinBulanIni += (($alphaMissingMap[$user->id] ?? $cntAlpha) * -abs((int)($absenSetting->point_alpa ?? 5)));
        
        // Denda Alpa flat per hari (hanya tanggal bolong; tercatat tidak dihitung lagi).
        // $alphaDetailMap berisi missing-only; 'total_denda' selalu ada bila entry ada
        // (bahkan 0 bila semua alpha sudah tercatat) — samakan dengan denda_pdf.
        $dendaAlpha = 0;
        if (isset($alphaDetailMap[$user->id]['total_denda'])) {
            $dendaAlpha = (float) $alphaDetailMap[$user->id]['total_denda'];
            foreach ($alphaDetailMap[$user->id]['items'] ?? [] as $aItem) {
                $dendaItems[] = [
                    'tgl' => $aItem['tgl'],
                    'jam_masuk' => '-',
                    'jam_pulang' => '-',
                    'status_masuk' => 'ALPHA',
                    'telat_menit' => 0,
                    'pulang_cepat_menit' => 0,
                    'denda' => (float)$aItem['denda'],
                    'keterangan_denda' => $aItem['keterangan']
                ];
            }
        } else {
            $dendaAlpha = 0;
        }
        $totalDendaKotor = $totalDendaHarian + $dendaAlpha;

        // Payments
        $bayarTunai = $bayarTunaiMap[$user->id] ?? 0;
        $potonganPoin = $potonganPoinMap[$user->id] ?? 0;
        $potonganSaldo = $potonganSaldoMap[$user->id] ?? 0;
        $poinDigunakan = $poinDigunakanMap[$user->id] ?? 0;
        // Saldo denda ikut menghitung "sudah dibayar" (dibayar dari deposit),
        // tapi tidak masuk kas sekolah — jadi dipisah dari $bayarTunai.
        $totalSudahBayar = $bayarTunai + $potonganPoin + $potonganSaldo;

        $sisaDendaBulanIni = max(0, $totalDendaKotor - $totalSudahBayar);

        $isLunas = ($totalDendaKotor > 0 && $sisaDendaBulanIni == 0);
        $isSebagian = ($totalDendaKotor > 0 && $totalSudahBayar > 0 && $sisaDendaBulanIni > 0);
        $isBelum = ($totalDendaKotor > 0 && $totalSudahBayar == 0);
        $isNihil = ($totalDendaKotor == 0);

        if ($isLunas) $countLunas++;
        elseif ($isSebagian) $countSebagian++;
        elseif ($isBelum) $countBelum++;
        elseif ($isNihil) $countNihil++;

        if ($potonganPoin > 0) $countHasPoin++;
        if ($bayarTunai > 0) $countHasTunai++;
        if ($potonganSaldo > 0) $countHasSaldo++;

        $totalDendaKotorAll += $totalDendaKotor;
        $totalBayarTunaiAll += $bayarTunai;
        $totalPotonganPoinAll += $potonganPoin;
        $totalPotonganSaldoAll += $potonganSaldo;
        $totalSisaBelumAll += $sisaDendaBulanIni;

        $saldoPoinAktif = $saldoPoinMap[$user->id] ?? 0;

        $statsByUser[$user->id] = compact(
            'cntHadir', 'cntTelat', 'cntDinas', 'cntIzinSakit', 'cntAlpha',
            'totalPoinBulanIni', 'totalDendaHarian', 'dendaAlpha', 'totalDendaKotor',
            'bayarTunai', 'potonganPoin', 'potonganSaldo', 'poinDigunakan', 'totalSudahBayar',
            'sisaDendaBulanIni', 'isLunas', 'isSebagian', 'isBelum', 'isNihil', 'saldoPoinAktif', 'dendaItems'
        );
    }

    $totalTerkumpul = $totalBayarTunaiAll + $totalPotonganPoinAll + $totalPotonganSaldoAll;
    $persenLunas = $totalDendaKotorAll > 0 ? round(($totalTerkumpul / $totalDendaKotorAll) * 100) : 0;
    ?>

    <!-- 5 Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- 1. Total Guru -->
        <div class="stat-box denda-card border-l-4 border-l-blue-500">
            <div class="stat-box-icon bg-blue-50 text-blue-600">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="min-w-0">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total GTK</span>
                <h4 class="text-xl font-bold text-slate-800 leading-tight mt-0.5"><?= $totalGuru ?> <span class="text-xs font-normal text-slate-500">Orang</span></h4>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate"><?= $countLunas ?> Lunas &middot; <?= $countBelum + $countSebagian ?> Belum</p>
            </div>
        </div>

        <!-- 2. Total Denda Kotor -->
        <div class="stat-box denda-card border-l-4 border-l-slate-600">
            <div class="stat-box-icon bg-slate-100 text-slate-700">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div class="min-w-0">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Denda Kotor</span>
                <h4 class="text-base sm:text-lg font-bold text-slate-800 leading-tight mt-0.5 num-tabular">Rp <?= number_format($totalDendaKotorAll, 0, ',', '.') ?></h4>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Harian + Alpa Bulan Ini</p>
            </div>
        </div>

        <!-- 3. Dibayar Tunai -->
        <div class="stat-box denda-card border-l-4 border-l-emerald-500">
            <div class="stat-box-icon bg-emerald-50 text-emerald-600">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="min-w-0">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Bayar Tunai</span>
                <h4 class="text-base sm:text-lg font-bold text-emerald-600 leading-tight mt-0.5 num-tabular">Rp <?= number_format($totalBayarTunaiAll, 0, ',', '.') ?></h4>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Kas Masuk Sekolah</p>
                <?php if ($totalPotonganSaldoAll > 0): ?>
                    <p class="text-[11px] text-sky-600 mt-0.5 truncate num-tabular">+ Saldo Denda Rp <?= number_format($totalPotonganSaldoAll, 0, ',', '.') ?> (<?= $countHasSaldo ?> GTK)</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Potongan Poin -->
        <div class="stat-box denda-card border-l-4 border-l-purple-500">
            <div class="stat-box-icon bg-purple-50 text-purple-600">
                <i class="bi bi-stars"></i>
            </div>
            <div class="min-w-0">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Potongan Poin</span>
                <h4 class="text-base sm:text-lg font-bold text-purple-700 leading-tight mt-0.5 num-tabular">Rp <?= number_format($totalPotonganPoinAll, 0, ',', '.') ?></h4>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Voucher Reward GTK</p>
            </div>
        </div>

        <!-- 5. Sisa Belum Lunas -->
        <div class="stat-box denda-card border-l-4 border-l-red-500">
            <div class="stat-box-icon bg-red-50 text-red-500">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Sisa Belum Lunas</span>
                <h4 class="text-base sm:text-lg font-bold text-red-600 leading-tight mt-0.5 num-tabular">Rp <?= number_format($totalSisaBelumAll, 0, ',', '.') ?></h4>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: <?= $persenLunas ?>%"></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5"><?= $persenLunas ?>% Terselesaikan</p>
            </div>
        </div>
    </div>

    <!-- Info Denda Alpa Flat -->
    <?php $baseAlphaAdmin = (float)($absenSetting->denda_alpha_per_hari ?? 5000); ?>
    <div class="p-3.5 rounded-xl border bg-slate-50 border-slate-200 flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-slate-400 text-white flex items-center justify-center shrink-0"><i class="bi bi-dash-circle"></i></div>
        <div class="flex-1 min-w-0">
            <div class="text-[13px] font-extrabold text-slate-800">Denda Alpa Flat <span class="text-[11px] font-normal text-slate-500">Rp <?= number_format($baseAlphaAdmin,0,',','.') ?>/hari</span></div>
            <p class="m-0 mt-1 text-[11.5px] font-medium leading-relaxed text-slate-500">
                Tarif flat <b>Rp <?= number_format($baseAlphaAdmin,0,',','.') ?></b> per hari kerja tanpa kelipatan.
            </p>
        </div>
    </div>

    <!-- Filter, Search & Table Card -->
    <div class="card-tw overflow-hidden shadow-sm">
        
        <!-- Period Filter & Action Header -->
        <div class="card-tw-header flex-wrap gap-4 justify-between">
            <!-- Filter Period Form -->
            <form action="" method="get" class="flex items-center gap-2.5 flex-wrap">
                <span class="font-bold text-xs uppercase tracking-wider text-slate-500 mr-1 flex items-center gap-1.5">
                    <i class="bi bi-calendar-event text-blue-600 text-sm"></i> Periode:
                </span>
                <select name="bulan" class="form-control-tw text-xs py-1.5 w-auto">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $bulan ? 'selected' : '' ?>>
                            <?= buat_tanggal(date('F', mktime(0, 0, 0, $m, 1))) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="tahun" class="form-control-tw text-xs py-1.5 w-auto">
                    <?php for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-tw btn-tw-sm btn-primary">
                    <i class="bi bi-arrow-repeat mr-1"></i> Tampilkan
                </button>
                <a href="<?= site_url('admin/presensi/denda/pdf?bulan=' . $bulan . '&tahun=' . $tahun) ?>" target="_blank" class="btn-tw btn-tw-sm btn-light border text-rose-600 hover:bg-rose-50" title="Cetak Rekap Denda ke format PDF">
                    <i class="bi bi-file-earmark-pdf-fill mr-1"></i> Cetak PDF
                </a>
                <button type="button"
                    id="btnKirimRekap"
                    class="btn-tw btn-tw-sm btn-light border border-emerald-300 text-emerald-700 hover:bg-emerald-50 shadow-sm"
                    data-bulan="<?= esc((string) $bulan) ?>"
                    data-tahun="<?= esc((string) $tahun) ?>"
                    title="Kirim file PDF rekap denda ke grup WhatsApp dan grup Telegram"
                    onclick="kirimRekapDendaKeGrup()">
                    <i class="bi bi-send-fill mr-1"></i> Kirim ke Grup
                </button>
                <button type="button" onclick="recalculateDendaPeriode()" class="btn-tw btn-tw-sm btn-light border border-amber-300 text-amber-700 hover:bg-amber-50 shadow-sm" title="Hitung ulang seluruh denda bulan aktif (keterlambatan, pulang cepat, dan alpa flat)">
                    <i class="bi bi-arrow-clockwise text-amber-600 mr-1"></i> Hitung Ulang Denda
                </button>
            </form>

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="bi bi-search text-xs"></i>
                </div>
                <input type="text" class="form-control-tw pl-8 pr-8 text-xs py-1.5 w-full bg-slate-50 focus:bg-white" 
                    id="searchDendaInput" placeholder="Cari nama guru atau NIP..." onkeyup="filterDendaTable()">
                <button type="button" id="btnClearDendaSearch" class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs" onclick="clearDendaSearch()">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>

        <!-- Quick Filter Status Chips -->
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center gap-2 flex-wrap">
            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider mr-1">Filter Status:</span>
            <span class="filter-chip active" data-filter="all" onclick="setDendaFilter('all', this)">
                Semua <span class="badge-sub"><?= $totalGuru ?></span>
            </span>
            <span class="filter-chip" data-filter="belum" onclick="setDendaFilter('belum', this)">
                Belum Lunas <span class="badge-sub"><?= $countBelum + $countSebagian ?></span>
            </span>
            <span class="filter-chip" data-filter="lunas" onclick="setDendaFilter('lunas', this)">
                Lunas <span class="badge-sub"><?= $countLunas ?></span>
            </span>
            <span class="filter-chip" data-filter="poin" onclick="setDendaFilter('poin', this)">
                Ada Potongan Poin <span class="badge-sub"><?= $countHasPoin ?></span>
            </span>
            <span class="filter-chip" data-filter="tunai" onclick="setDendaFilter('tunai', this)">
                Ada Bayar Tunai <span class="badge-sub"><?= $countHasTunai ?></span>
            </span>
            <span class="filter-chip" data-filter="nihil" onclick="setDendaFilter('nihil', this)">
                Nihil (Rp 0) <span class="badge-sub"><?= $countNihil ?></span>
            </span>
        </div>

        <!-- Table Container -->
        <div class="table-container-scroll">
            <table class="table-denda w-full" id="dendaTable">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="text-center w-10">No</th>
                        <th class="min-w-[180px]">Guru / Pegawai</th>
                        <th class="text-center w-10" title="Jumlah Hadir Bulan Ini">H</th>
                        <th class="text-center w-10" title="Terlambat Masuk Bulan Ini">TL</th>
                        <th class="text-center w-10" title="Alpa / Tanpa Keterangan">A</th>
                        <th class="text-center w-24" title="Saldo Poin Kedisiplinan GTK">Saldo Poin</th>
                        <th class="text-right min-w-[110px]" title="Denda Kotor Bulan Ini (Harian + Alpa)">Denda Kotor</th>
                        <th class="text-right min-w-[105px] text-purple-700" title="Potongan denda via reward/poin">
                            <i class="bi bi-stars mr-1"></i>Potong Poin
                        </th>
                        <th class="text-right min-w-[105px] text-emerald-600" title="Denda dibayar tunai/transfer">
                            <i class="bi bi-cash mr-1"></i>Bayar Tunai
                        </th>
                        <th class="text-right min-w-[105px] text-red-500" title="Sisa denda yang belum dibayar bulan ini">
                            Sisa (Bln Ini)
                        </th>
                        <th class="text-right min-w-[115px] bg-emerald-50/70 text-emerald-700" title="Total akumulasi pembayaran denda YTD">
                            Dibayar (YTD)
                        </th>
                        <th class="text-right min-w-[115px] bg-red-50/70 text-red-600" title="Sisa akumulasi denda belum bayar YTD">
                            Sisa (YTD)
                        </th>
                        <th class="text-center min-w-[120px]">Status</th>
                        <th class="text-center w-28">Aksi</th>
                        <th class="text-center w-10"><i class="bi bi-chevron-expand"></i></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="15" class="text-center text-slate-400 py-12">
                                <i class="bi bi-people text-4xl block mb-2 opacity-30"></i>
                                <p class="text-sm font-medium">Belum ada data guru terdaftar.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($users as $user):
                            $s = $statsByUser[$user->id];
                            $history = $historyByUser[$user->id] ?? [];
                            $ytd = $ytdStats[$user->id] ?? ['total_denda' => 0, 'dibayar_tunai' => 0, 'potongan_poin' => 0, 'total_dibayar' => 0, 'sisa' => 0];

                            // Category for quick filtering
                            $filterCat = 'nihil';
                            $statusPillClass = 'badge-tw badge-secondary';
                            $statusPillText = 'Nihil (Rp 0)';
                            $statusPillIcon = 'bi-check-all';

                            if ($s['totalDendaKotor'] > 0) {
                                if ($s['sisaDendaBulanIni'] == 0) {
                                    $filterCat = 'lunas';
                                    $kanalBayar = ($s['bayarTunai'] > 0 ? 1 : 0) + ($s['potonganPoin'] > 0 ? 1 : 0) + ($s['potonganSaldo'] > 0 ? 1 : 0);
                                    if ($kanalBayar > 1) {
                                        $statusPillClass = 'badge-tw badge-info';
                                        $statusPillText = 'Lunas (Mix)';
                                        $statusPillIcon = 'bi-check-circle-fill';
                                    } elseif ($s['potonganPoin'] > 0) {
                                        $statusPillClass = 'badge-tw badge-purple';
                                        $statusPillText = 'Lunas (Poin)';
                                        $statusPillIcon = 'bi-stars';
                                    } elseif ($s['potonganSaldo'] > 0) {
                                        $statusPillClass = 'badge-tw badge-info';
                                        $statusPillText = 'Lunas (Saldo)';
                                        $statusPillIcon = 'bi-wallet2';
                                    } else {
                                        $statusPillClass = 'badge-tw badge-success';
                                        $statusPillText = 'Lunas (Tunai)';
                                        $statusPillIcon = 'bi-check-circle-fill';
                                    }
                                } elseif ($s['totalSudahBayar'] > 0) {
                                    $filterCat = 'sebagian';
                                    $statusPillClass = 'badge-tw badge-warning';
                                    $statusPillText = 'Sebagian';
                                    $statusPillIcon = 'bi-pie-chart-fill';
                                } else {
                                    $filterCat = 'belum';
                                    $statusPillClass = 'badge-tw badge-danger';
                                    $statusPillText = 'Belum Bayar';
                                    $statusPillIcon = 'bi-x-circle-fill';
                                }
                            }

                            $hasPoin = $s['potonganPoin'] > 0 ? '1' : '0';
                            $hasTunai = $s['bayarTunai'] > 0 ? '1' : '0';
                            $searchKeyword = strtolower($user->nama_guru . ' ' . ($user->nip ?? ''));
                            $guruSaldoDenda = (float)($saldoDendaMap[$user->id] ?? 0);
                            ?>
                            <tr class="align-middle hover:bg-slate-50/80 transition-colors" 
                                data-search="<?= esc($searchKeyword) ?>" 
                                data-status-cat="<?= $filterCat ?>" 
                                data-has-poin="<?= $hasPoin ?>" 
                                data-has-tunai="<?= $hasTunai ?>">
                                
                                <td class="text-center text-slate-400 text-xs"><?= $no++ ?></td>
                                
                                <!-- Teacher Info -->
                                <td>
                                    <div class="font-bold text-slate-800 text-sm truncate max-w-[190px]" title="<?= esc($user->nama_guru) ?>">
                                        <?= esc($user->nama_guru) ?>
                                    </div>
                                    <div class="text-slate-400 text-[11px]">NIP: <?= esc($user->nip ?? '-') ?></div>
                                    <?php if ($guruSaldoDenda > 0): ?>
                                        <div class="mt-1">
                                            <span class="badge-tw badge-info text-[10px] rounded-full px-2 py-0.5" title="Saldo Denda Tersedia">
                                                <i class="bi bi-wallet2 mr-1"></i>Saldo: Rp <?= number_format($guruSaldoDenda, 0, ',', '.') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Attendance Counters -->
                                <td class="text-center font-bold text-emerald-600 num-tabular"><?= $s['cntHadir'] ?></td>
                                <td class="text-center font-bold text-amber-500 num-tabular"><?= $s['cntTelat'] ?></td>
                                <td class="text-center font-bold text-red-500 num-tabular"><?= $s['cntAlpha'] ?></td>
                                
                                <!-- Saldo Poin -->
                                <td class="text-center">
                                    <?php 
                                        $saldoVal = (int) $s['saldoPoinAktif'];
                                        $poinBadge = $saldoVal < 0 ? 'badge-tw badge-danger' : ($saldoVal > 0 ? 'badge-tw badge-success' : 'badge-tw badge-secondary');
                                    ?>
                                    <span class="badge-tw <?= $poinBadge ?> rounded-full px-2.5 py-0.5 text-xs num-tabular">
                                        <i class="bi bi-star-fill text-amber-400 mr-1 text-[10px]"></i><?= $saldoVal > 0 ? '+' . number_format($saldoVal) : number_format($saldoVal) ?>
                                    </span>
                                </td>

                                <!-- Denda Kotor (Bulan Ini) -->
                                <td class="text-right">
                                    <div class="font-bold text-slate-800 num-tabular">Rp <?= number_format($s['totalDendaKotor'], 0, ',', '.') ?></div>
                                    <?php if ($s['totalDendaKotor'] > 0): ?>
                                        <div class="text-[10px] text-slate-400">
                                            <?= number_format($s['totalDendaHarian']/1000, 1) ?>k + <?= number_format($s['dendaAlpha']/1000, 1) ?>k (A)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Potongan Poin -->
                                <td class="text-right">
                                    <span class="num-tabular <?= $s['potonganPoin'] > 0 ? 'font-bold text-purple-700' : 'text-slate-400' ?>">
                                        <?= $s['potonganPoin'] > 0 ? 'Rp ' . number_format($s['potonganPoin'], 0, ',', '.') : '-' ?>
                                    </span>
                                    <?php if ($s['poinDigunakan'] > 0): ?>
                                        <div class="text-[10px] text-slate-400">
                                            (<?= number_format($s['poinDigunakan']) ?> pts)
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Bayar Tunai -->
                                <td class="text-right" title="Kolom ini hanya kas tunai. Pembayaran dari saldo denda (deposit) ditandai biru.">
                                    <span class="num-tabular <?= $s['bayarTunai'] > 0 ? 'text-emerald-600 font-bold' : 'text-slate-400' ?>">
                                        <?= $s['bayarTunai'] > 0 ? 'Rp ' . number_format($s['bayarTunai'], 0, ',', '.') : '-' ?>
                                    </span>
                                    <?php if ($s['potonganSaldo'] > 0): ?>
                                        <div class="text-[10px] text-sky-600 num-tabular">
                                            (Saldo: <?= number_format($s['potonganSaldo'], 0, ',', '.') ?>)
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Sisa Denda Bulan Ini -->
                                <td class="text-right">
                                    <span class="font-bold num-tabular <?= $s['sisaDendaBulanIni'] > 0 ? 'text-red-500' : 'text-slate-400' ?>">
                                        <?= $s['sisaDendaBulanIni'] > 0 ? 'Rp ' . number_format($s['sisaDendaBulanIni'], 0, ',', '.') : '-' ?>
                                    </span>
                                </td>

                                <!-- YTD Sudah Dibayar -->
                                <td class="text-right num-tabular text-emerald-600 bg-emerald-50/50" title="Rincian akumulasi pembayaran YTD: Tunai / Poin / Saldo">
                                    <div class="font-bold">Rp <?= number_format($ytd['total_dibayar'], 0, ',', '.') ?></div>
                                    <?php if (($ytd['dibayar_tunai'] ?? 0) > 0): ?>
                                        <div class="text-[10px] text-emerald-700">
                                            (Tunai: <?= number_format($ytd['dibayar_tunai']/1000, 1) ?>k)
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($ytd['potongan_poin'] > 0): ?>
                                        <div class="text-[10px] text-purple-600">
                                            (Poin: <?= number_format($ytd['potongan_poin']/1000, 1) ?>k)
                                        </div>
                                    <?php endif; ?>
                                    <?php if (($ytd['potongan_saldo'] ?? 0) > 0): ?>
                                        <div class="text-[10px] text-sky-600">
                                            (Saldo: <?= number_format($ytd['potongan_saldo']/1000, 1) ?>k)
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- YTD Sisa -->
                                <td class="text-right border-l bg-red-50/40">
                                    <span class="font-bold num-tabular <?= ($ytd['sisa'] ?? 0) > 0 ? 'text-red-500' : 'text-slate-400' ?>" title="Akumulasi Belum Lunas Tahun <?= $tahun ?>">
                                        <?= ($ytd['sisa'] ?? 0) > 0 ? 'Rp ' . number_format($ytd['sisa'], 0, ',', '.') : '-' ?>
                                    </span>
                                </td>

                                <!-- Status Pill -->
                                <td class="text-center">
                                    <span class="status-pill <?= $statusPillClass ?>">
                                        <i class="bi <?= $statusPillIcon ?>"></i> <?= $statusPillText ?>
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="text-center">
                                    <?php 
                                        $hasSisaBulanIni = ($s['sisaDendaBulanIni'] > 0);
                                        $hasYtdSisa = (($ytd['sisa'] ?? 0) > 0);
                                    ?>
                                    <?php if ($hasSisaBulanIni || $hasYtdSisa): ?>
                                        <div class="inline-flex items-center rounded-lg shadow-sm">
                                            <?php if ($hasSisaBulanIni): ?>
                                                <button class="btn-tw btn-tw-sm btn-success rounded-r-none px-2 py-1 text-xs btn-mark-lunas"
                                                    data-user-id="<?= $user->id ?>"
                                                    data-bulan="<?= $bulan ?>"
                                                    data-tahun="<?= $tahun ?>"
                                                    data-total-denda="<?= $s['sisaDendaBulanIni'] ?>"
                                                    data-saldo-poin="<?= $s['saldoPoinAktif'] ?>"
                                                    data-saldo-denda="<?= $guruSaldoDenda ?>"
                                                    data-nama="<?= esc($user->nama_guru) ?>"
                                                    title="Lunasi Denda Bulan Ini">
                                                    <i class="bi bi-check2 mr-1"></i>Lunas
                                                </button>
                                            <?php elseif ($s['isLunas']): ?>
                                                <button class="btn-tw btn-tw-sm btn-light border text-red-600 hover:bg-red-50 rounded-l-lg rounded-r-none px-2 py-1 text-xs btn-batal-lunas"
                                                    data-user-id="<?= $user->id ?>"
                                                    data-bulan="<?= $bulan ?>"
                                                    data-tahun="<?= $tahun ?>"
                                                    data-nama="<?= esc($user->nama_guru) ?>"
                                                    title="Batalkan Pelunasan">
                                                    <i class="bi bi-x-circle mr-1"></i>Batal
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-tw btn-tw-sm btn-primary <?= $hasSisaBulanIni || $s['isLunas'] ? 'rounded-l-none' : 'rounded-lg' ?> px-2 py-1 text-xs btn-bayar-nominal"
                                                data-user-id="<?= $user->id ?>"
                                                data-tahun="<?= $tahun ?>"
                                                data-saldo-poin="<?= $s['saldoPoinAktif'] ?>"
                                                data-saldo-denda="<?= $guruSaldoDenda ?>"
                                                data-ytd-sisa="<?= $ytd['sisa'] ?? 0 ?>"
                                                data-nama="<?= esc($user->nama_guru) ?>"
                                                title="Bayar dengan Nominal Tertentu (Multi-Bulan FIFO)">
                                                <i class="bi bi-wallet2 <?= $hasSisaBulanIni || $s['isLunas'] ? '' : 'mr-1' ?>"></i><?= $hasSisaBulanIni || $s['isLunas'] ? '' : 'Nominal' ?>
                                            </button>
                                        </div>
                                    <?php elseif ($s['isLunas']): ?>
                                        <button class="btn-tw btn-tw-sm btn-light border text-red-600 hover:bg-red-50 rounded-lg px-2.5 py-1 text-xs btn-batal-lunas"
                                            data-user-id="<?= $user->id ?>"
                                            data-bulan="<?= $bulan ?>"
                                            data-tahun="<?= $tahun ?>"
                                            data-nama="<?= esc($user->nama_guru) ?>"
                                            title="Batalkan Pelunasan">
                                            <i class="bi bi-x-circle mr-1"></i>Batal
                                        </button>
                                    <?php else: ?>
                                        <span class="text-slate-300 text-xs">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Toggle Details -->
                                <td class="text-center">
                                    <button class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition toggle-detail"
                                        data-target="#detail-<?= $user->id ?>" title="Lihat Rincian Harian & Pembayaran">
                                        <i class="bi bi-chevron-down text-xs"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Detail Accordion Row -->
                            <tr id="detail-<?= $user->id ?>" class="hidden">
                                <td colspan="15" class="bg-slate-50/70 p-0 border-b border-slate-200">
                                    <div class="p-4 space-y-3">
                                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                            
                                            <!-- Left: Per-day denda breakdown -->
                                            <div class="lg:col-span-7">
                                                <div class="bg-white rounded-xl border border-slate-200/80 p-3 shadow-sm h-full">
                                                    <h6 class="font-bold mb-2 text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                                        <i class="bi bi-list-check text-blue-600"></i> Rincian Keterlambatan & Denda Harian
                                                    </h6>
                                                    <?php if (empty($s['dendaItems'])): ?>
                                                        <div class="text-center py-6 text-slate-400 text-xs">
                                                            <i class="bi bi-shield-check text-2xl text-emerald-500 block mb-1"></i>
                                                            Tidak ada catatan keterlambatan atau denda harian pada bulan ini.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="overflow-x-auto max-h-48 overflow-y-auto">
                                                            <table class="detail-table w-full">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Tanggal</th>
                                                                        <th>Status</th>
                                                                        <th>Masuk</th>
                                                                        <th>Pulang</th>
                                                                        <th class="text-center">Telat</th>
                                                                        <th class="text-center">P.Cepat</th>
                                                                        <th class="text-right">Denda</th>
                                                                        <th>Keterangan</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    <?php foreach ($s['dendaItems'] as $di):
                                                                        $isDiAlpha = (strtoupper($di['status_masuk']) === 'ALPHA');
                                                                    ?>
                                                                        <tr class="<?= $isDiAlpha ? 'bg-amber-50/30' : '' ?>">
                                                                            <td class="font-mono"><?= date('d/m/Y', strtotime($di['tgl'])) ?></td>
                                                                            <td>
                                                                                <?php if ($isDiAlpha): ?>
                                                                                    <span class="badge-tw badge-danger text-[10px] py-0.5 px-1.5">ALPHA</span>
                                                                                <?php else: ?>
                                                                                    <span class="badge-tw badge-warning text-[10px] py-0.5 px-1.5"><?= $di['status_masuk'] ?></span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="font-mono"><?= !empty($di['jam_masuk']) && $di['jam_masuk'] != '-' ? date('H:i', strtotime($di['jam_masuk'])) : '-' ?></td>
                                                                            <td class="font-mono"><?= !empty($di['jam_pulang']) && $di['jam_pulang'] != '-' ? date('H:i', strtotime($di['jam_pulang'])) : '-' ?></td>
                                                                            <td class="text-center"><?= $di['telat_menit'] ? $di['telat_menit'] . 'm' : '-' ?></td>
                                                                            <td class="text-center"><?= $di['pulang_cepat_menit'] ? $di['pulang_cepat_menit'] . 'm' : '-' ?></td>
                                                                            <td class="text-right font-bold num-tabular <?= $isDiAlpha ? 'text-amber-600' : 'text-red-500' ?>">Rp <?= number_format($di['denda'], 0, ',', '.') ?></td>
                                                                            <td class="text-slate-600 max-w-[180px] whitespace-normal leading-tight" title="<?= esc($di['keterangan_denda'] ?: '-') ?>"><?= esc($di['keterangan_denda'] ?: '-') ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Right: Payment history with distinct payment methods -->
                                            <div class="lg:col-span-5">
                                                <div class="bg-white rounded-xl border border-slate-200/80 p-3 shadow-sm h-full">
                                                    <h6 class="font-bold mb-2 text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                                        <i class="bi bi-clock-history text-blue-600"></i> Riwayat Pembayaran & Potongan
                                                    </h6>
                                                    <?php if (empty($history)): ?>
                                                        <div class="text-center py-6 text-slate-400 text-xs">
                                                            <i class="bi bi-inbox text-2xl block mb-1 opacity-30"></i>
                                                            Belum ada riwayat pembayaran tercatat untuk periode ini.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="overflow-x-auto max-h-48 overflow-y-auto">
                                                            <table class="detail-table w-full">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Metode</th>
                                                                        <th>Tgl Bayar</th>
                                                                        <th class="text-right">Jumlah</th>
                                                                        <th>Keterangan</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    <?php foreach ($history as $h): 
                                                                        $isPoin = ($h->metode_bayar === 'POIN' || !empty($h->reward_redemption_id));
                                                                        $isSaldo = ($h->metode_bayar === 'SALDO');
                                                                    ?>
                                                                        <tr>
                                                                            <td>
                                                                                <?php if ($isPoin): ?>
                                                                                    <span class="badge-tw badge-purple text-[10px] rounded-full px-2 py-0.5">
                                                                                        <i class="bi bi-stars mr-1"></i> Potongan Poin
                                                                                    </span>
                                                                                <?php elseif ($isSaldo): ?>
                                                                                    <span class="badge-tw badge-info text-[10px] rounded-full px-2 py-0.5">
                                                                                        <i class="bi bi-wallet2 mr-1"></i> Saldo Denda
                                                                                    </span>
                                                                                <?php else: ?>
                                                                                    <span class="badge-tw badge-success text-[10px] rounded-full px-2 py-0.5">
                                                                                        <i class="bi bi-cash mr-1"></i> Tunai / Kas
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="font-mono">
                                                                                <?php if (!empty($h->tanggal_bayar)): ?>
                                                                                    <?= date('d/m/Y', strtotime($h->tanggal_bayar)) ?>
                                                                                <?php elseif (!empty($h->paid_at)): ?>
                                                                                    <?= date('d/m/Y', strtotime($h->paid_at)) ?>
                                                                                <?php else: ?>
                                                                                    -
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="text-right font-bold num-tabular <?= $isPoin ? 'text-purple-700' : ($isSaldo ? 'text-cyan-700' : 'text-emerald-600') ?>">
                                                                                Rp <?= number_format((float)($h->nominal_bayar ?: $h->total_denda), 0, ',', '.') ?>
                                                                            </td>
                                                                            <td class="text-slate-400 truncate max-w-[120px]"><?= esc($h->keterangan ?? '-') ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Menampilkan <?= count($users) ?> guru & staf untuk periode <?= buat_tanggal(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?></span>
            <span class="text-slate-400">Gunakan tombol <strong>Nominal</strong> untuk melunasi tunggakan lintas bulan secara FIFO.</span>
        </div>
    </div>

</div>

<!-- Modal Mark Lunas dengan Pilihan Metode Pembayaran (Single Bulan) -->
<div id="modalMarkLunas" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <form id="formMarkLunas" method="post" action="<?= site_url('admin/presensi/denda/mark-lunas') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="mark_user_id">
                <input type="hidden" name="bulan" id="mark_bulan">
                <input type="hidden" name="tahun" id="mark_tahun">
                <input type="hidden" name="total_denda" id="mark_total_denda">
                <input type="hidden" name="idempotency_key" id="mark_idempotency_key">

                <!-- Header -->
                <div class="modal-header-tw border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-base leading-tight">Konfirmasi Pembayaran Denda</h5>
                            <span class="text-xs text-slate-400">Penyelesaian denda presensi bulanan</span>
                        </div>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" data-ui-dismiss="modal">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body-tw p-6 space-y-4">
                    
                    <!-- Teacher Name & Amount Card -->
                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-center">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Pelunasan Denda GTK</span>
                        <h4 class="font-bold text-slate-800 text-base mt-0.5" id="mark_nama">-</h4>
                        <div class="text-xs text-slate-400 mt-0.5">Periode: <?= buat_tanggal(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?></div>
                        
                        <div class="mt-3 py-2 px-4 bg-emerald-50 border border-emerald-200/70 rounded-xl inline-block">
                            <span class="text-[11px] uppercase font-bold text-emerald-700 block">Total Nominal Yang Diselesaikan</span>
                            <div class="text-2xl font-bold text-emerald-600 num-tabular mt-0.5" id="mark_total_text">Rp 0</div>
                        </div>
                    </div>

                    <!-- Payment Method Radio Buttons -->
                    <div>
                        <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Pilih Metode Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <input type="radio" class="btn-check" name="metode_bayar" id="metodeTunai" value="TUNAI" checked onchange="toggleMetodePayment()">
                                <label class="method-radio-box box-tunai" for="metodeTunai">
                                    <i class="bi bi-cash-stack text-xl block mb-1 text-emerald-600"></i>
                                    <div class="font-bold text-xs">Tunai</div>
                                    <div class="text-[10px] text-slate-400">Kas Sekolah</div>
                                </label>
                            </div>
                            <div>
                                <input type="radio" class="btn-check" name="metode_bayar" id="metodePoin" value="POIN" onchange="toggleMetodePayment()">
                                <label class="method-radio-box box-poin" for="metodePoin">
                                    <i class="bi bi-stars text-xl block mb-1 text-purple-600"></i>
                                    <div class="font-bold text-xs text-purple-700">Poin</div>
                                    <div class="text-[10px] text-slate-400">Potong Poin</div>
                                </label>
                            </div>
                            <div>
                                <input type="radio" class="btn-check" name="metode_bayar" id="metodeSaldo" value="SALDO" onchange="toggleMetodePayment()">
                                <label class="method-radio-box box-saldo" for="metodeSaldo">
                                    <i class="bi bi-wallet2 text-xl block mb-1 text-cyan-600"></i>
                                    <div class="font-bold text-xs text-cyan-700">Saldo</div>
                                    <div class="text-[10px] text-slate-400">Saldo Denda</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo Denda Helper Box -->
                    <div id="boxDetailSaldo" class="p-3 bg-cyan-50/70 border border-cyan-200/80 rounded-xl hidden space-y-1">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-cyan-800 font-medium">Saldo Denda Guru:</span>
                            <span class="font-bold text-cyan-700 text-sm num-tabular" id="mark_saldo_denda_text">Rp 0</span>
                        </div>
                        <p class="text-[11px] text-cyan-700/80">Saldo denda guru akan dipotong otomatis untuk melunasi periode ini.</p>
                    </div>

                    <!-- Point Deduction Helper Box -->
                    <div id="boxDetailPoin" class="p-3 bg-purple-50/70 border border-purple-200/80 rounded-xl hidden space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-purple-800 font-medium">Saldo Poin Kedisiplinan:</span>
                            <span class="font-bold text-purple-700 text-sm num-tabular" id="mark_saldo_poin_text">0 pts</span>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-purple-900 block mb-1">Jumlah Poin Yang Dipotong:</label>
                            <input type="number" name="poin_digunakan" id="mark_poin_digunakan" class="form-control-tw text-xs py-1.5 w-full bg-slate-50" value="0" min="0" readonly>
                            <span class="text-[10px] text-purple-600 mt-1 block">Dihitung dari nominal &times; tarif Rp <?= number_format((float) ($dendaPerPoin ?? 1000), 0, ',', '.') ?>/poin, lalu dipotong dari saldo kedisiplinan guru.</span>
                        </div>
                    </div>

                    <!-- Date & Notes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Pembayaran</label>
                            <input type="date" name="tanggal_bayar" id="mark_tanggal_bayar" class="form-control-tw text-xs py-2 w-full" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan / Bukti</label>
                            <input type="text" name="keterangan" id="mark_keterangan" class="form-control-tw text-xs py-2 w-full" placeholder="Diterima bendahara / voucher">
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer-tw border-t border-slate-100 px-6 py-4 bg-slate-50 flex items-center justify-end gap-2">
                    <button type="button" class="btn-tw btn-light text-xs" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-success text-xs shadow-sm">
                        <i class="bi bi-check2-circle mr-1.5"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Batal Lunas -->
<div id="modalBatalLunas" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <form id="formBatalLunas" method="post" action="<?= site_url('admin/presensi/denda/batal-lunas') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="batal_user_id">
                <input type="hidden" name="bulan" id="batal_bulan">
                <input type="hidden" name="tahun" id="batal_tahun">

                <div class="modal-header-tw border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-base leading-tight">Batalkan Pelunasan Denda</h5>
                            <span class="text-xs text-slate-400">Pembatalan status pembayaran</span>
                        </div>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" data-ui-dismiss="modal">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <div class="modal-body-tw p-6 space-y-4">
                    <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-xs text-slate-500 uppercase font-semibold block">Guru / Pegawai</span>
                        <h5 class="font-bold text-slate-800 text-base mt-0.5" id="batal_nama">-</h5>
                        <div class="text-xs text-slate-400">Periode: <?= buat_tanggal(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?></div>
                    </div>

                    <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 space-y-1 leading-relaxed">
                        <div class="font-bold flex items-center gap-1">
                            <i class="bi bi-info-circle-fill text-amber-600"></i> Catatan Pembatalan:
                        </div>
                        <p>Status lunas untuk guru ini akan dibatalkan. Jika sebelumnya dibayar dengan potongan poin, poin akan dikembalikan ke saldo poin guru secara otomatis.</p>
                    </div>
                </div>

                <div class="modal-footer-tw border-t border-slate-100 px-6 py-4 bg-slate-50 flex items-center justify-end gap-2">
                    <button type="button" class="btn-tw btn-light text-xs" data-ui-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn-tw btn-danger text-xs shadow-sm">
                        <i class="bi bi-arrow-counterclockwise mr-1.5"></i> Batalkan Pelunasan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bayar Nominal (Multi-Bulan Auto-Distribusi FIFO) -->
<div id="modalBayarNominal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-3xl">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <form id="formBayarNominal" method="post" action="<?= site_url('admin/presensi/denda/bayar-nominal') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="nominal_user_id">
                <input type="hidden" name="tahun" id="nominal_tahun">
                <input type="hidden" name="idempotency_key" id="nominal_idempotency_key">

                <!-- Header -->
                <div class="modal-header-tw border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-base leading-tight">Pembayaran Nominal (Multi-Bulan)</h5>
                            <span class="text-xs text-slate-400">Distribusi otomatis secara FIFO ke bulan yang belum lunas</span>
                        </div>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" data-ui-dismiss="modal">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body-tw p-6 space-y-4">
                    
                    <!-- Teacher Banner -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Pelunasan Akumulasi</span>
                            <h5 class="font-bold text-slate-800 text-sm leading-tight" id="nominal_nama">-</h5>
                            <span class="text-xs text-slate-400">Tahun: <span id="nominal_tahun_text"><?= $tahun ?></span></span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge-tw badge-danger text-xs rounded-full px-3 py-1 font-bold">
                                Sisa YTD: <span class="num-tabular ml-1" id="nominal_total_sisa_text">Rp 0</span>
                            </span>
                            <span class="badge-tw badge-info text-xs rounded-full px-3 py-1 font-bold">
                                Saldo: <span class="num-tabular ml-1" id="nominal_saldo_denda_badge">Rp 0</span>
                            </span>
                        </div>
                    </div>

                    <!-- Split Grid: Form Input Left, Preview Distribution Right -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                        
                        <!-- Left Form Inputs -->
                        <div class="lg:col-span-5 space-y-3.5">
                            
                            <!-- Nominal Input -->
                            <div>
                                <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nominal Pembayaran <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                        Rp
                                    </div>
                                    <input type="number" name="nominal_bayar" id="nominal_bayar_input" 
                                        class="form-control-tw pl-9 text-xs py-2 w-full font-bold num-tabular" 
                                        min="1" step="any" placeholder="Contoh: 150000" required>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1 block">Kelebihan nominal akan otomatis disimpan menjadi <strong>Saldo Denda</strong>.</span>
                            </div>

                            <!-- Tanggal Bayar -->
                            <div>
                                <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tanggal Pembayaran
                                </label>
                                <input type="date" name="tanggal_bayar" id="nominal_tanggal_bayar" 
                                    class="form-control-tw text-xs py-2 w-full" value="<?= date('Y-m-d') ?>">
                            </div>

                            <!-- Metode Bayar -->
                            <div>
                                <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Metode Pembayaran <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <div>
                                        <input type="radio" class="btn-check" name="metode_bayar" id="nominalMetodeTunai" value="TUNAI" checked onchange="toggleNominalMetode()">
                                        <label class="method-radio-box box-tunai py-2 px-1" for="nominalMetodeTunai">
                                            <i class="bi bi-cash-stack text-lg block text-emerald-600"></i>
                                            <div class="font-bold text-[11px] mt-0.5">Tunai</div>
                                        </label>
                                    </div>
                                    <div>
                                        <input type="radio" class="btn-check" name="metode_bayar" id="nominalMetodePoin" value="POIN" onchange="toggleNominalMetode()">
                                        <label class="method-radio-box box-poin py-2 px-1" for="nominalMetodePoin">
                                            <i class="bi bi-stars text-lg block text-purple-600"></i>
                                            <div class="font-bold text-[11px] mt-0.5 text-purple-700">Poin</div>
                                        </label>
                                    </div>
                                    <div>
                                        <input type="radio" class="btn-check" name="metode_bayar" id="nominalMetodeSaldo" value="SALDO" onchange="toggleNominalMetode()">
                                        <label class="method-radio-box box-saldo py-2 px-1" for="nominalMetodeSaldo">
                                            <i class="bi bi-wallet2 text-lg block text-cyan-600"></i>
                                            <div class="font-bold text-[11px] mt-0.5 text-cyan-700">Saldo</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Saldo Denda Info -->
                            <div id="boxNominalSaldo" class="p-2.5 bg-cyan-50/70 border border-cyan-200/80 rounded-xl hidden text-xs">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-cyan-800">Saldo Denda Tersedia:</span>
                                    <span class="font-bold text-cyan-700 num-tabular" id="nominal_saldo_denda_text">Rp 0</span>
                                </div>
                                <span class="text-[10px] text-cyan-600">Denda dipotong langsung dari saldo denda guru.</span>
                            </div>

                            <!-- Poin Input -->
                            <div id="boxNominalPoin" class="p-2.5 bg-purple-50/70 border border-purple-200/80 rounded-xl hidden text-xs space-y-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-purple-800">Saldo Poin:</span>
                                    <span class="font-bold text-purple-700 num-tabular" id="nominal_saldo_poin_text">0 pts</span>
                                </div>
                                <input type="number" name="poin_digunakan" id="nominal_poin_digunakan" class="form-control-tw text-xs py-1.5 w-full bg-slate-50" value="0" min="0" readonly>
                                <span class="text-[10px] text-purple-600 block">Ikut nominal: Rp &divide; tarif Rp <?= number_format((float) ($dendaPerPoin ?? 1000), 0, ',', '.') ?>/poin. Server yang menghitungnya.</span>
                            </div>

                            <!-- Keterangan -->
                            <div>
                                <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Keterangan (Opsional)
                                </label>
                                <input type="text" name="keterangan" id="nominal_keterangan" class="form-control-tw text-xs py-2 w-full" placeholder="Catatan pembayaran">
                            </div>

                            <button type="button" class="btn-tw btn-tw-sm btn-outline-primary w-full justify-center text-xs py-2 font-semibold" onclick="previewDistribusi()">
                                <i class="bi bi-eye mr-1.5"></i> Hitung Preview Distribusi
                            </button>
                        </div>

                        <!-- Right Live Preview -->
                        <div class="lg:col-span-7">
                            <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50 h-full flex flex-col">
                                <h6 class="font-bold text-xs uppercase tracking-wider text-slate-700 mb-2.5 flex items-center gap-1.5">
                                    <i class="bi bi-diagram-3 text-blue-600"></i> Preview Distribusi Pembayaran FIFO
                                </h6>
                                <div id="previewDistribusiContainer" class="flex-1 flex flex-col justify-center">
                                    <div class="text-center text-slate-400 py-8">
                                        <i class="bi bi-arrow-left-circle text-3xl block mb-2 text-slate-300"></i>
                                        <span class="text-xs max-w-xs block mx-auto leading-relaxed">Masukkan nominal lalu klik <strong>Hitung Preview Distribusi</strong> untuk melihat rincian alokasi ke bulan-bulan yang menunggak.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer-tw border-t border-slate-100 px-6 py-4 bg-slate-50 flex items-center justify-end gap-2">
                    <button type="button" class="btn-tw btn-light text-xs" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary text-xs shadow-sm" id="btnSubmitNominal" disabled>
                        <i class="bi bi-check2-circle mr-1.5"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var activeDendaFilter = 'all';

    // Tarif Rupiah per poin dari Pengaturan > Presensi. Server menghitung
    // ulang jumlah poin dari nominal (markLunas & bayarDendaNominal), jadi
    // angka di layar hanya gambaran — bukan sumber kebenaran.
    var DENDA_PER_POIN = <?= (int) max(1, (float) ($dendaPerPoin ?? 1000)) ?>;

    function toggleMetodePayment() {
        var isPoin = document.getElementById('metodePoin')?.checked;
        var isSaldo = document.getElementById('metodeSaldo')?.checked;
        var boxPoin = document.getElementById('boxDetailPoin');
        var boxSaldo = document.getElementById('boxDetailSaldo');
        if (boxPoin) {
            isPoin ? boxPoin.classList.remove('hidden') : boxPoin.classList.add('hidden');
        }
        if (boxSaldo) {
            isSaldo ? boxSaldo.classList.remove('hidden') : boxSaldo.classList.add('hidden');
        }
    }

    function toggleNominalMetode() {
        var isPoin = document.getElementById('nominalMetodePoin')?.checked;
        var isSaldo = document.getElementById('nominalMetodeSaldo')?.checked;
        var boxPoin = document.getElementById('boxNominalPoin');
        var boxSaldo = document.getElementById('boxNominalSaldo');
        if (boxPoin) {
            isPoin ? boxPoin.classList.remove('hidden') : boxPoin.classList.add('hidden');
        }
        if (boxSaldo) {
            isSaldo ? boxSaldo.classList.remove('hidden') : boxSaldo.classList.add('hidden');
        }
    }

    function setDendaFilter(filterType, el) {
        activeDendaFilter = filterType;
        document.querySelectorAll('.filter-chip').forEach(function(chip) {
            chip.classList.remove('active');
        });
        if (el) el.classList.add('active');
        filterDendaTable();
    }

    function filterDendaTable() {
        var keyword = (document.getElementById('searchDendaInput')?.value || '').toLowerCase().trim();
        var clearBtn = document.getElementById('btnClearDendaSearch');

        if (clearBtn) {
            keyword.length > 0 ? clearBtn.classList.remove('hidden') : clearBtn.classList.add('hidden');
        }

        $('#dendaTable tbody tr:not([id^="detail-"])').each(function() {
            var searchData = $(this).attr('data-search') || '';
            var statusCat = $(this).attr('data-status-cat') || 'belum';
            var hasPoin = $(this).attr('data-has-poin') === '1';
            var hasTunai = $(this).attr('data-has-tunai') === '1';

            var matchSearch = !keyword || searchData.includes(keyword);
            var matchFilter = true;

            if (activeDendaFilter === 'belum') {
                matchFilter = (statusCat === 'belum' || statusCat === 'sebagian');
            } else if (activeDendaFilter === 'lunas') {
                matchFilter = (statusCat === 'lunas');
            } else if (activeDendaFilter === 'poin') {
                matchFilter = hasPoin;
            } else if (activeDendaFilter === 'tunai') {
                matchFilter = hasTunai;
            } else if (activeDendaFilter === 'nihil') {
                matchFilter = (statusCat === 'nihil');
            }

            if (matchSearch && matchFilter) {
                $(this).show();
            } else {
                $(this).hide();
                var detailId = $(this).find('.toggle-detail').data('target');
                if (detailId) $(detailId).addClass('hidden');
            }
        });
    }

    function clearDendaSearch() {
        var input = document.getElementById('searchDendaInput');
        if (input) {
            input.value = '';
            filterDendaTable();
        }
    }

    function formatRupiah(angka) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
    }

    // Nonce unik per aksi bayar untuk kunci idempotensi server.
    // crypto.randomUUID bila tersedia, fallback acak alfanumerik.
    function nonceBayar() {
        try {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID().replace(/-/g, '').slice(0, 32);
            }
        } catch (e) {}
        var abjad = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var hasil = '';
        for (var i = 0; i < 32; i++) {
            hasil += abjad.charAt(Math.floor(Math.random() * abjad.length));
        }
        return hasil;
    }

    function previewDistribusi() {
        var userId = $('#nominal_user_id').val();
        var nominal = parseFloat($('#nominal_bayar_input').val()) || 0;
        var tahun = $('#nominal_tahun').val();

        if (!userId || nominal <= 0) {
            Swal.fire('Peringatan', 'Masukkan nominal pembayaran yang valid terlebih dahulu.', 'warning');
            return;
        }

        var container = $('#previewDistribusiContainer');
        container.html('<div class="text-center py-6"><span class="spinner-border spinner-border-sm text-blue-600 mr-2"></span><span class="text-xs text-slate-500">Menghitung distribusi...</span></div>');
        $('#btnSubmitNominal').prop('disabled', true);

        $.ajax({
            url: '<?= site_url("admin/presensi/denda/preview-distribusi") ?>',
            type: 'GET',
            data: { user_id: userId, nominal_bayar: nominal, tahun: tahun },
            dataType: 'json',
            success: function(res) {
                if (res.status && res.data) {
                    var namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    
                    // Sync header total sisa badge
                    $('#nominal_total_sisa_text').text(formatRupiah(res.data.total_sisa));

                    var html = '';

                    if (res.data.distribusi && res.data.distribusi.length > 0) {
                        html += '<div class="overflow-x-auto rounded-lg border border-slate-200 max-h-44 overflow-y-auto mb-3">';
                        html += '<table class="table-tw w-full text-xs">';
                        html += '<thead class="sticky top-0 bg-slate-100 text-slate-600"><tr><th class="py-1 px-2">Periode</th><th class="py-1 px-2 text-right">Sisa Denda</th><th class="py-1 px-2 text-right">Dibayar</th><th class="py-1 px-2 text-right">Sisa Sesudah</th><th class="py-1 px-2 text-center">Status</th></tr></thead><tbody class="divide-y divide-slate-100">';

                        res.data.distribusi.forEach(function(d) {
                            var statusBadge = d.status === 'LUNAS' 
                                ? '<span class="badge-tw badge-success rounded-full text-[10px] py-0.5 px-2">LUNAS</span>' 
                                : '<span class="badge-tw badge-warning rounded-full text-[10px] py-0.5 px-2">SEBAGIAN</span>';
                            html += '<tr class="bg-white">';
                            html += '<td class="py-1 px-2 font-bold">' + namaBulan[d.bulan] + ' ' + d.tahun + '</td>';
                            html += '<td class="py-1 px-2 text-right num-tabular">' + formatRupiah(d.sisa_sebelum) + '</td>';
                            html += '<td class="py-1 px-2 text-right num-tabular font-bold text-emerald-600">' + formatRupiah(d.nominal_bayar) + '</td>';
                            html += '<td class="py-1 px-2 text-right num-tabular ' + (d.sisa_sesudah > 0 ? 'text-red-500' : 'text-slate-400') + '">' + formatRupiah(d.sisa_sesudah) + '</td>';
                            html += '<td class="py-1 px-2 text-center">' + statusBadge + '</td>';
                            html += '</tr>';
                        });

                        html += '</tbody></table></div>';
                    } else {
                        html += '<div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 mb-3"><i class="bi bi-info-circle mr-1"></i> Tidak ada tunggakan denda. Seluruh nominal akan disimpan sebagai Saldo Denda.</div>';
                    }

                    // Summary breakdown block
                    html += '<div class="p-3 bg-white rounded-xl border border-slate-200 text-xs space-y-1.5 shadow-sm">';
                    html += '<div class="flex justify-between text-slate-600"><span>Total Tunggakan:</span><strong>' + formatRupiah(res.data.total_sisa) + '</strong></div>';
                    html += '<div class="flex justify-between text-blue-600"><span>Nominal Dibayarkan:</span><strong>' + formatRupiah(nominal) + '</strong></div>';
                    html += '<div class="flex justify-between text-emerald-600"><span>Alokasi Pelunasan:</span><strong>' + formatRupiah(res.data.alokasi_denda) + '</strong></div>';
                    
                    if (res.data.lebih_bayar > 0) {
                        html += '<div class="flex justify-between text-cyan-600 font-bold border-t pt-1"><span>Kelebihan (Masuk Saldo):</span><strong>+' + formatRupiah(res.data.lebih_bayar) + '</strong></div>';
                    }
                    
                    html += '<div class="flex justify-between pt-1 border-t ' + (res.data.sisa_setelah_bayar > 0 ? 'text-red-500 font-bold' : 'text-slate-500') + '"><span>Sisa Setelah Bayar:</span><strong>' + formatRupiah(res.data.sisa_setelah_bayar) + '</strong></div>';
                    html += '</div>';

                    if (res.data.lebih_bayar > 0) {
                        html += '<div class="mt-2.5 p-2 bg-cyan-50 border border-cyan-200 rounded-lg text-[11px] text-cyan-800"><i class="bi bi-wallet2 mr-1"></i> Kelebihan <strong>' + formatRupiah(res.data.lebih_bayar) + '</strong> otomatis disimpan menjadi <strong>Saldo Denda</strong> guru.</div>';
                    }

                    container.html(html);
                    $('#btnSubmitNominal').prop('disabled', false);
                } else {
                    container.html('<div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">' + (res.message || 'Tidak dapat memproses distribusi.') + '</div>');
                    $('#btnSubmitNominal').prop('disabled', true);
                }
            },
            error: function() {
                container.html('<div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700">Gagal mengambil kalkulasi distribusi dari server.</div>');
                $('#btnSubmitNominal').prop('disabled', true);
            }
        });
    }

    function recalculateDendaPeriode() {
        // Guard double-submit: satu proses per klik (penekanan ganda/ulang
        // saat loading tidak boleh menumpuk request hitung ulang).
        if (window.__recalcDendaRunning) {
            return;
        }
        // Hitung ulang dikunci ke BULAN AKTIF (aturan server) — selalu kirim periode berjalan.
        var bulan = '<?= date('m') ?>';
        var tahun = '<?= date('Y') ?>';
        var bulanText = '<?= date('F Y') ?>';

        var promptHtml = 'Sistem akan mengevaluasi dan menghitung ulang seluruh denda presensi periode <strong>' + bulanText + '</strong> (bulan aktif — bulan lampau dikunci sebagai arsip):<br><br>' +
            '<div class="text-left text-xs text-slate-600 space-y-1.5 bg-slate-50 p-3 rounded-lg border border-slate-200">' +
            '<div><i class="bi bi-check2-circle text-emerald-600 mr-1.5"></i>Status keterlambatan masuk sesuai toleransi & jadwal aktif</div>' +
            '<div><i class="bi bi-check2-circle text-emerald-600 mr-1.5"></i>Status pulang cepat sesuai batas jam pulang aktif</div>' +
            '<div><i class="bi bi-check2-circle text-emerald-600 mr-1.5"></i>Denda Alpa flat per hari otomatis</div>' +
            '<div><i class="bi bi-check2-circle text-emerald-600 mr-1.5"></i>Batas maksimal denda bulanan (cap) jika dikonfigurasi</div>' +
            '</div>';

        if (typeof Swal === 'undefined') {
            if (confirm('Hitung ulang denda presensi periode ' + bulanText + ' ' + tahun + '?')) {
                var postData = { bulan: bulan, tahun: tahun };
                var csrfName = '<?= csrf_token() ?>';
                var csrfHash = $('input[name="' + csrfName + '"]').val() || '<?= csrf_hash() ?>';
                postData[csrfName] = csrfHash;

                window.__recalcDendaRunning = true;
                $.post('<?= site_url('admin/presensi/denda/recalculate') ?>', postData, function(res) {
                    window.__recalcDendaRunning = false;
                    alert(res.message || 'Selesai');
                    location.reload();
                }, 'json').fail(function() {
                    window.__recalcDendaRunning = false;
                    alert('Gagal memproses hitung ulang denda');
                });
            }
            return;
        }

        Swal.fire({
            title: 'Hitung Ulang Denda Presensi?',
            html: promptHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-arrow-clockwise mr-1"></i> Ya, Hitung Ulang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.__recalcDendaRunning = true;
                Swal.fire({
                    title: 'Sedang Menghitung Ulang...',
                    text: 'Mohon tunggu, sistem sedang memproses data presensi & denda...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                var postData = {
                    bulan: bulan,
                    tahun: tahun
                };
                var csrfName = '<?= csrf_token() ?>';
                var csrfHash = $('input[name="' + csrfName + '"]').val() || '<?= csrf_hash() ?>';
                postData[csrfName] = csrfHash;

                $.ajax({
                    url: '<?= site_url('admin/presensi/denda/recalculate') ?>',
                    type: 'POST',
                    data: postData,
                    dataType: 'json',
                    success: function(res) {
                        window.__recalcDendaRunning = false;
                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Dihitung Ulang!',
                                text: res.message,
                                timer: 2500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Gagal Menghitung Ulang', res.message || 'Terjadi kesalahan.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        window.__recalcDendaRunning = false;
                        Swal.fire('Error Sistem', 'Terjadi kesalahan saat memproses data: ' + error, 'error');
                    }
                });
            }
        });
    }

    /**
     * Kirim file PDF rekap denda ke grup WhatsApp + grup Telegram.
     *
     * PDF dirender ulang di server dari periode yang sama dengan tabel yang
     * sedang dilihat, jadi berkas yang diterima grup identik dengan yang
     * akan dihasilkan tombol "Cetak PDF".
     */
    function kirimRekapDendaKeGrup() {
        // Guard double-submit: render PDF + dua unggahan jaringan butuh
        // beberapa detik. Penekanan ganda akan mengirim dokumen yang sama
        // dua kali ke grup, dan itu terlihat oleh semua anggota grup.
        if (window.__kirimRekapRunning) {
            return;
        }

        // Tombolnya type="button" di dalam <form>. Kalau elemennya tidak
        // ada (halaman di-render sebagian, atau handler dipanggil dari
        // console), berhenti di sini — tanpa btn, sisa kode di bawah akan
        // melempar error dan tombol form tetap bisa men-submit halaman.
        var btn = document.getElementById('btnKirimRekap');
        if (!btn) {
            return;
        }

        var bulan = btn.dataset.bulan;
        var tahun = btn.dataset.tahun;

        var labelAwal = btn.innerHTML;

        // Penanda dinaikkan DI SINI, sebelum request dikirim. Kalau tidak,
        // guard di atas tidak pernah bernilai benar dan klik ganda tetap
        // lolos walau tombol sudah disabled.
        window.__kirimRekapRunning = true;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Mengirim…';

        var csrfName = '<?= csrf_token() ?>';
        var csrfHash = $('input[name="' + csrfName + '"]').val() || '<?= csrf_hash() ?>';

        function selesai() {
            window.__kirimRekapRunning = false;
            btn.disabled = false;
            btn.innerHTML = labelAwal;
        }

        $.ajax({
            url: '<?= site_url('admin/presensi/denda/kirim-rekap') ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                bulan: bulan,
                tahun: tahun
            },
            headers: {
                // Token lewat header DAN body: body dipakai kalau filter
                // membaca dari POST, header jadi cadangan kalau cookie
                // session sudah berganti.
                'X-CSRF-TOKEN': csrfHash,
                'X-Requested-With': 'XMLHttpRequest'
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader(csrfName, csrfHash);
            },
            success: function(res) {
                selesai();

                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rekap Denda Terkirim',
                        text: res.message || 'Berkas PDF telah dikirim ke grup.',
                        timer: 4500,
                        showConfirmButton: false
                    });
                } else {
                    // Pesan server sudah menyebut kanal mana yang gagal,
                    // jadi tampilkan apa adanya — admin butuh tahu
                    // Telegram jalan tapi WhatsApp tidak.
                    Swal.fire({
                        icon: 'error',
                        title: 'Rekap Tidak Lengkap Dikirim',
                        text: res.message || 'Gagal mengirim rekap denda.',
                        showConfirmButton: true
                    });
                }
            },
            error: function(xhr) {
                selesai();

                var pesan = 'Gagal menghubungi server.';
                if (xhr && xhr.status === 403) {
                    pesan = 'Sesi tidak valid atau token CSRF kedaluwarsa. Muat ulang halaman lalu coba lagi.';
                } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    pesan = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengirim Rekap',
                    text: pesan,
                    showConfirmButton: true
                });
            }
        });
    }

    window.toggleMetodePayment = toggleMetodePayment;
    window.toggleNominalMetode = toggleNominalMetode;
    window.setDendaFilter = setDendaFilter;
    window.filterDendaTable = filterDendaTable;
    window.clearDendaSearch = clearDendaSearch;
    window.previewDistribusi = previewDistribusi;
    window.recalculateDendaPeriode = recalculateDendaPeriode;
    window.kirimRekapDendaKeGrup = kirimRekapDendaKeGrup;

    $(document).ready(function() {
        // Toggle Accordion Details
        $(document).on('click', '.toggle-detail', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $(target).toggleClass('hidden');
            var icon = $(this).find('i');
            if ($(target).hasClass('hidden')) {
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
            }
        });

        // Open Modal Mark Lunas (Per Bulan)
        $(document).on('click', '.btn-mark-lunas', function(e) {
            e.preventDefault();
            var btn = $(this).closest('.btn-mark-lunas');
            var userId = btn.data('user-id');
            var bulan = btn.data('bulan');
            var tahun = btn.data('tahun');
            var totalDenda = parseFloat(btn.data('total-denda')) || 0;
            var saldoPoin = parseInt(btn.data('saldo-poin')) || 0;
            var saldoDenda = parseFloat(btn.data('saldo-denda')) || 0;
            var nama = btn.data('nama');

            $('#mark_user_id').val(userId);
            $('#mark_bulan').val(bulan);
            $('#mark_tahun').val(tahun);
            $('#mark_total_denda').val(totalDenda);
            $('#mark_nama').text(nama);
            $('#mark_total_text').text(formatRupiah(totalDenda));
            $('#mark_saldo_poin_text').text(new Intl.NumberFormat('id-ID').format(saldoPoin) + ' pts');
            $('#mark_saldo_denda_text').text(formatRupiah(saldoDenda));
            $('#mark_tanggal_bayar').val(new Date().toLocaleDateString('en-CA'));

            $('#metodeTunai').prop('checked', true);
            $('#mark_poin_digunakan').val(Math.max(0, Math.ceil(totalDenda / DENDA_PER_POIN)));
            toggleMetodePayment();

            // Kunci idempotensi baru tiap buka modal: klik ganda / retry
            // memakai kunci sama sehingga server menolak penulisan kedua.
            $('#mark_idempotency_key').val(nonceBayar());

            $('#modalMarkLunas').appModal('show');
        });

        // Open Modal Bayar Nominal (Multi-Bulan)
        $(document).on('click', '.btn-bayar-nominal', function(e) {
            e.preventDefault();
            var btn = $(this).closest('.btn-bayar-nominal');
            var userId = btn.data('user-id');
            var tahun = btn.data('tahun');
            var saldoPoin = parseInt(btn.data('saldo-poin')) || 0;
            var saldoDenda = parseFloat(btn.data('saldo-denda')) || 0;
            var ytdSisa = parseFloat(btn.data('ytd-sisa')) || 0;
            var nama = btn.data('nama');

            $('#nominal_user_id').val(userId);
            $('#nominal_tahun').val(tahun);
            $('#nominal_nama').text(nama);
            $('#nominal_tahun_text').text(tahun);
            $('#nominal_total_sisa_text').text(formatRupiah(ytdSisa));
            $('#nominal_saldo_poin_text').text(new Intl.NumberFormat('id-ID').format(saldoPoin) + ' pts');
            $('#nominal_saldo_denda_badge').text(formatRupiah(saldoDenda));
            $('#nominal_saldo_denda_text').text(formatRupiah(saldoDenda));
            $('#nominal_bayar_input').val('');
            $('#nominal_tanggal_bayar').val(new Date().toLocaleDateString('en-CA'));
            $('#nominal_keterangan').val('');
            $('#nominalMetodeTunai').prop('checked', true);
            toggleNominalMetode();
            $('#nominal_idempotency_key').val(nonceBayar());

            // Reset preview
            $('#previewDistribusiContainer').html(
                '<div class="text-center text-slate-400 py-8">' +
                '<i class="bi bi-arrow-left-circle text-3xl block mb-2 text-slate-300"></i>' +
                '<span class="text-xs max-w-xs block mx-auto leading-relaxed">Masukkan nominal lalu klik <strong>Hitung Preview Distribusi</strong> untuk melihat rincian alokasi ke bulan-bulan yang menunggak.</span></div>'
            );
            $('#btnSubmitNominal').prop('disabled', true);

            $('#modalBayarNominal').appModal('show');
            setTimeout(function() { $('#nominal_bayar_input').focus(); }, 150);
        });

        // Auto trigger calculate on Enter or typing debounce in nominal input
        var nominalDebounceTimer;
        $(document).on('input', '#nominal_bayar_input', function() {
            clearTimeout(nominalDebounceTimer);
            var val = parseFloat($(this).val()) || 0;
            $('#nominal_poin_digunakan').val(Math.max(0, Math.ceil(val / DENDA_PER_POIN)));
            if (val > 0) {
                $('#btnSubmitNominal').prop('disabled', false);
                nominalDebounceTimer = setTimeout(function() {
                    previewDistribusi();
                }, 600);
            } else {
                $('#btnSubmitNominal').prop('disabled', true);
            }
        });

        // Open Modal Batal Lunas
        $(document).on('click', '.btn-batal-lunas', function(e) {
            e.preventDefault();
            var btn = $(this).closest('.btn-batal-lunas');
            var userId = btn.data('user-id');
            var bulan = btn.data('bulan');
            var tahun = btn.data('tahun');
            var nama = btn.data('nama');

            $('#batal_user_id').val(userId);
            $('#batal_bulan').val(bulan);
            $('#batal_tahun').val(tahun);
            $('#batal_nama').text(nama);

            $('#modalBatalLunas').appModal('show');
        });

        // AJAX submit for Mark Lunas (Per Bulan)
        $('#formMarkLunas').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1.5"></span> Menyimpan...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        $('#modalMarkLunas').appModal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal Menyimpan', res.message, 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    Swal.fire('Error Sistem', 'Terjadi kesalahan sistem saat menghubungi server', 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // AJAX submit for Bayar Nominal (Multi-Bulan)
        $('#formBayarNominal').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#btnSubmitNominal');
            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1.5"></span> Menyimpan...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        $('#modalBayarNominal').appModal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Tercatat!',
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal Menyimpan', res.message, 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    Swal.fire('Error Sistem', 'Terjadi kesalahan sistem saat menghubungi server', 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // AJAX submit for Batal Lunas
        $('#formBatalLunas').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1.5"></span> Memproses...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        $('#modalBatalLunas').appModal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Dibatalkan!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal Membatalkan', res.message, 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    Swal.fire('Error Sistem', 'Terjadi kesalahan sistem saat menghubungi server', 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>
