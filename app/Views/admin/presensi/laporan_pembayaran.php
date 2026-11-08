<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.num-tabular { font-variant-numeric: tabular-nums; }

/* ── Filter chips (pola monitoring siswa) ── */
.filter-chip {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.75rem; border-radius: 9999px;
    font-size: 0.75rem; font-weight: 600; cursor: pointer;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    transition: all 0.15s ease; white-space: nowrap; user-select: none;
}
.filter-chip:hover  { border-color: #0284c7; color: #0284c7; background: #f0f9ff; }
.filter-chip.active { background: #0284c7; color: #fff; border-color: #0284c7; }
.status-dot {
    width:8px; height:8px; border-radius:50%; flex-shrink:0; display:inline-block;
}
.dot-TUNAI { background:#22c55e; }
.dot-POIN  { background:#a855f7; }
.dot-SALDO { background:#38bdf8; }

.m-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 0.15rem 0.55rem; border-radius: 9999px;
    font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.m-periode { background:#eef2ff; color:#4338ca; }
.m-tunai { background: #dcfce7; color: #15803d; }
.m-poin  { background: #f3e8ff; color: #7e22ce; }
.m-saldo { background: #e0f2fe; color: #0369a1; }
.s-lunas    { background: #dcfce7; color: #15803d; }
.s-sebagian { background: #fef3c7; color: #b45309; }
.s-lain     { background: #f1f5f9; color: #475569; }

@media print {
    .no-print { display: none !important; }
    .fade-in-up { animation: none !important; }
    body { background: #fff !important; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ── Header (pola monitoring siswa) ── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs no-print">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-sky-50 text-sky-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-journal-text"></i> Laporan Denda
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Laporan Pembayaran Denda</h1>
            <p class="text-xs text-slate-500 mt-1">Riwayat pembayaran denda guru/pegawai. <span id="filterInfo" class="font-bold text-slate-700"></span></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/denda') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-cash-coin"></i> Kelola Denda
            </a>
            <a href="<?= site_url('admin/presensi') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-speedometer2"></i> Monitoring
            </a>
            <button type="button" id="btn-print"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
            </button>
        </div>
    </div>

    <!-- ── Stat cards (pola monitoring: border-l accent) ── -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-slate-400 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-cash-coin"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                <p class="text-xl font-extrabold text-slate-900 leading-tight num-tabular truncate">Rp <?= number_format($totalNominal, 0, ',', '.') ?></p>
                <p class="text-[11px] text-slate-400"><?= count($payments) ?> transaksi</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-cash-stack"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tunai</p>
                <p class="text-xl font-extrabold text-emerald-600 leading-tight num-tabular truncate">Rp <?= number_format($totalTunai, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-violet-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-stars"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Poin</p>
                <p class="text-xl font-extrabold text-violet-600 leading-tight num-tabular truncate">Rp <?= number_format($totalPoin, 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-sky-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-wallet2"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Saldo</p>
                <p class="text-xl font-extrabold text-sky-600 leading-tight num-tabular truncate">Rp <?= number_format($totalSaldo ?? 0, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- ── Filter ── -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden no-print">
        <div class="flex items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <i class="bi bi-funnel text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Filter Laporan</h2>
        </div>
        <form action="" method="get" class="p-5 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="fGuru">Guru / Pegawai</label>
                <select name="user_id" id="fGuru" class="form-select-tw" style="width:220px">
                    <option value="">— Semua Guru —</option>
                    <?php foreach ($guruList as $g): ?>
                        <option value="<?= $g->id ?>" <?= $filterUserId == $g->id ? 'selected' : '' ?>>
                            <?= esc($g->nama_guru) ?> <?= esc($g->nip ? '(' . $g->nip . ')' : '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="fDari">Dari</label>
                <input type="date" id="fDari" name="tgl_dari" class="form-control-tw" style="width:150px" value="<?= esc($filterTglDari) ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="fSampai">Sampai</label>
                <input type="date" id="fSampai" name="tgl_sampai" class="form-control-tw" style="width:150px" value="<?= esc($filterTglSampai) ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="fMetode">Metode</label>
                <select name="metode" id="fMetode" class="form-select-tw" style="width:150px">
                    <option value="">— Semua —</option>
                    <option value="TUNAI" <?= $filterMetode === 'TUNAI' ? 'selected' : '' ?>>Tunai</option>
                    <option value="POIN" <?= $filterMetode === 'POIN' ? 'selected' : '' ?>>Poin</option>
                    <option value="SALDO" <?= $filterMetode === 'SALDO' ? 'selected' : '' ?>>Saldo</option>
                </select>
            </div>
            <button type="submit" class="btn-tw btn-primary">
                <i class="bi bi-funnel mr-1"></i> Filter
            </button>
            <a href="<?= site_url('admin/presensi/denda/laporan') ?>" class="btn-tw btn-tw-sm btn-light no-underline">
                <i class="bi bi-arrow-counterclockwise mr-1"></i> Reset
            </a>
            <!-- Search -->
            <div class="relative ml-0 md:ml-auto" style="min-width:220px;">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="qNama" placeholder="Cari nama guru..." autocomplete="off"
                       class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                <button type="button" id="qClear"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
        </form>
        <!-- Metode chips -->
        <div class="px-5 py-3 bg-slate-50/60 border-t border-slate-100 flex flex-wrap gap-1.5 items-center" id="metode-chips">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1">Metode</span>
            <button type="button" class="filter-chip<?= empty($filterMetode) ? ' active' : '' ?>" data-metode="">Semua</button>
            <button type="button" class="filter-chip<?= ($filterMetode ?? '') === 'TUNAI' ? ' active' : '' ?>" data-metode="TUNAI"><span class="status-dot dot-TUNAI"></span>Tunai</button>
            <button type="button" class="filter-chip<?= ($filterMetode ?? '') === 'POIN' ? ' active' : '' ?>" data-metode="POIN"><span class="status-dot dot-POIN"></span>Poin</button>
            <button type="button" class="filter-chip<?= ($filterMetode ?? '') === 'SALDO' ? ' active' : '' ?>" data-metode="SALDO"><span class="status-dot dot-SALDO"></span>Saldo</button>
        </div>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <i class="bi bi-table text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Riwayat Pembayaran</h2>
            <span class="ml-auto px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold" id="rowCountWrap"><span id="rowCount"><?= count($payments) ?></span> transaksi • <span id="visibleTotal" class="num-tabular">Rp 0</span></span>
        </div>
        <div class="overflow-x-auto" id="tabelWrap" style="max-height: 60vh;" tabindex="0">
            <table class="table-tw" id="tabelLaporan" style="font-size: 0.825rem;">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px">#</th>
                        <th>Tanggal</th>
                        <th style="min-width:160px">Guru</th>
                        <th class="text-center">Periode</th>
                        <th class="text-right" style="min-width:110px">Nominal</th>
                        <th class="text-center">Metode</th>
                        <th class="text-center">Status</th>
                        <th class="hidden lg:table-cell" style="min-width:150px">Keterangan</th>
                        <th class="hidden lg:table-cell" style="min-width:130px">Pencatat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">
                                <i class="bi bi-inbox text-4xl block mb-2"></i>
                                <p class="font-semibold text-slate-500 text-sm mb-0">Tidak ada pembayaran</p>
                                <p class="text-xs mb-0">Ubah filter tanggal / guru / metode.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($payments as $p):
                            $isPoin = ($p->metode_bayar === 'POIN');
                            $isSaldo = ($p->metode_bayar === 'SALDO');
                            $nominal = (float)($p->nominal_bayar ?: $p->total_denda);
                            $namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            $tglBayar = $p->tanggal_bayar ?: ($p->paid_at ?? null);
                            $adminNm = trim(($p->admin_first ?? '') . ' ' . ($p->admin_last ?? ''));
                        ?>
                            <tr data-nama="<?= esc(strtolower($p->nama_guru ?? '')) ?>" data-nominal="<?= $nominal ?>">
                                <td class="text-center text-xs text-slate-400 font-mono"><?= $no++ ?></td>
                                <td class="font-semibold text-sm text-slate-800 whitespace-nowrap"><?= $tglBayar ? date('d M Y', strtotime($tglBayar)) : '-' ?></td>
                                <td>
                                    <div class="font-semibold text-slate-800 text-sm leading-tight"><?= esc($p->nama_guru ?? '-') ?></div>
                                    <?php if (!empty($p->nip)): ?><div class="text-[11px] text-slate-400"><?= esc($p->nip) ?></div><?php endif; ?>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <span class="m-badge m-periode"><?= $namaBulan[(int)$p->bulan] ?? $p->bulan ?> <?= $p->tahun ?></span>
                                </td>
                                <td class="text-right num-tabular font-bold whitespace-nowrap <?= $isPoin ? 'text-violet-600' : ($isSaldo ? 'text-sky-600' : 'text-emerald-600') ?>">
                                    <?= number_format($nominal, 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isPoin): ?>
                                        <span class="m-badge m-poin">Poin</span>
                                    <?php elseif ($isSaldo): ?>
                                        <span class="m-badge m-saldo">Saldo</span>
                                    <?php else: ?>
                                        <span class="m-badge m-tunai">Tunai</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p->status === 'lunas'): ?>
                                        <span class="m-badge s-lunas">Lunas</span>
                                    <?php elseif ($p->status === 'sebagian'): ?>
                                        <span class="m-badge s-sebagian">Sebagian</span>
                                    <?php else: ?>
                                        <span class="m-badge s-lain"><?= esc($p->status) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-xs text-slate-400 hidden lg:table-cell" title="<?= esc($p->keterangan ?? '') ?>"><?= esc(mb_strimwidth($p->keterangan ?? '-', 0, 60, '…')) ?></td>
                                <td class="text-xs hidden lg:table-cell">
                                    <div class="text-slate-700 font-medium leading-tight"><?= $adminNm !== '' ? esc($adminNm) : '<span class="text-slate-300">-</span>' ?></div>
                                    <div class="text-[11px] text-slate-400"><?= $p->paid_at ? date('d M Y H:i', strtotime($p->paid_at)) : '' ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 no-print">
            <span id="lap-count"></span>
            <span id="lap-periode"><?= esc($filterTglDari) ?> — <?= esc($filterTglSampai) ?></span>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    function fmtRp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }

    function applyLaporanFilter() {
        var kw = ($('#qNama').val() || '').toLowerCase().trim();
        $('#qClear').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
        var shown = 0, total = 0, sum = 0;
        $('#tabelLaporan tbody tr[data-nama]').each(function() {
            total++;
            var ok = !kw || String($(this).data('nama') || '').includes(kw);
            $(this).toggle(ok);
            if (ok) {
                shown++;
                sum += parseFloat($(this).data('nominal') || 0);
            }
        });
        $('#rowCount').text(shown + (kw ? ' / ' + total : ''));
        $('#visibleTotal').text(fmtRp(sum));
        $('#lap-count').text('Menampilkan ' + shown + ' dari ' + total + ' transaksi');
    }

    $('#qNama').on('input', applyLaporanFilter);
    $('#qClear').on('click', function() {
        $('#qNama').val('');
        applyLaporanFilter();
        $('#qNama').focus();
    });

    // Chips metode → isi select lalu submit form
    $('#metode-chips').on('click', '.filter-chip', function() {
        $('#fMetode').val($(this).data('metode'));
        $('#fMetode').closest('form').submit();
    });

    $('#btn-print').on('click', function() {
        var q = [];
        if ($('#fGuru').val()) q.push('user_id=' + encodeURIComponent($('#fGuru').val()));
        if ($('#fDari').val()) q.push('tgl_dari=' + encodeURIComponent($('#fDari').val()));
        if ($('#fSampai').val()) q.push('tgl_sampai=' + encodeURIComponent($('#fSampai').val()));
        if ($('#fMetode').val()) q.push('metode=' + encodeURIComponent($('#fMetode').val()));
        window.location.href = '<?= site_url('admin/presensi/denda/laporan/pdf') ?>' + (q.length ? '?' + q.join('&') : '');
    });

    (function activeInfo() {
        var parts = [];
        var g = $('#fGuru option:selected').text();
        if ($('#fGuru').val()) parts.push(g.trim());
        if ($('#fDari').val()) parts.push('Dari ' + $('#fDari').val());
        if ($('#fSampai').val()) parts.push('Sampai ' + $('#fSampai').val());
        if ($('#fMetode').val()) parts.push($('#fMetode option:selected').text());
        if (parts.length) $('#filterInfo').text('• ' + parts.join(' • '));
    })();

    applyLaporanFilter();
});
</script>
<?= $this->endSection() ?>
