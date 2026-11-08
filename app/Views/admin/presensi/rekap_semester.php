<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .num-tabular { font-variant-numeric: tabular-nums; }
    .filter-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 700;
        border: 1.5px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer;
        transition: all .15s ease; white-space: nowrap;
    }
    .filter-chip:hover { border-color: var(--primary-600); color: var(--primary-600); }
    .filter-chip.active { background: var(--primary-600); border-color: var(--primary-600); color: #fff; }
    .filter-chip .dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
    .stat-card.clickable { cursor: pointer; }
    .stat-card.clickable:hover { border-color: var(--primary-300); }
    .stat-card.selected { border-color: var(--primary-600); box-shadow: 0 0 0 3px rgb(var(--primary-600-rgb) / .12); }
    .empty-state { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    @media (max-width: 640px) {
        .content-area { padding: 0.5rem 0.75rem 1.25rem; }
        .card-tw-body { padding: 0.875rem; }
    }
    @media print {
        .no-print { display: none !important; }
        .card-tw { box-shadow: none; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$fmtTrim = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$minFmt  = $fmtTrim($minPersen);
$lowFmt  = $fmtTrim($minPersen - 10);
$meta    = $meta ?? [];
$periode = $meta['periode'] ?? (($semester == 1) ? "Juli {$tahun} – Desember {$tahun}" : "Januari {$tahun} – Juni {$tahun}");
$tpLabel = $meta['tahunAjaran'] ?? (($semester == 1) ? "{$tahun}/" . ($tahun + 1) : ($tahun - 1) . "/{$tahun}");
$isPartial = !empty($meta['isPartial']);
$isStale   = !empty($meta['isStale']);
$totalGuru = (int) ($meta['totalGuru'] ?? ($summary['guru'] ?? 0));
$curYear   = (int) date('Y');
$total = max(1, (int) ($summary['guru'] ?? 0));
$pct = fn($n) => number_format(((int) $n / $total) * 100, 0);
?>

<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <nav class="breadcrumb-tw" aria-label="breadcrumb">
                <a href="<?= base_url('admin') ?>" class="no-underline"><i class="bi bi-house-door mr-1"></i>Admin</a>
                <span class="sep">/</span>
                <a href="<?= base_url('admin/presensi') ?>" class="no-underline">Presensi</a>
                <span class="sep">/</span>
                <span class="active">Rekap Semester</span>
            </nav>
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-journal-check text-xl"></i>
                </span>
                <span>Rekap Kehadiran Per Semester</span>
            </h3>
            <p class="text-sm text-slate-400 mb-0">
                Semester <?= $semester == 1 ? 'Ganjil' : 'Genap' ?> &middot; TP <?= esc($tpLabel) ?> &middot; <?= esc($periode) ?>
                <?php if ($isPartial): ?><span class="badge-tw badge-warning ml-1"><i class="bi bi-clock-history"></i>Parsial s/d hari ini</span><?php endif; ?>
            </p>
        </div>
        <div class="flex gap-2 flex-wrap items-center no-print">
            <button type="button" class="btn-tw btn-tw-sm btn-light" onclick="location.reload()" title="Muat ulang">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button type="button" class="btn-tw btn-tw-sm btn-outline-secondary" onclick="window.print()" title="Cetak halaman">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </div>
    </div>
</div>

<div class="content-area">

    <!-- Filter -->
    <div class="card-tw mb-4">
        <div class="card-tw-body">
            <form method="get" class="flex flex-wrap items-end gap-3" id="filterForm">
                <div>
                    <label class="form-label-tw" for="fTahun">Tahun Periode</label>
                    <select name="tahun" id="fTahun" class="form-select-tw" style="min-width:130px">
                        <?php for ($y = $curYear + 1; $y >= attendance_tahun_tertua(); $y--): ?>
                            <option value="<?= $y ?>" <?= (int) $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label-tw" for="fSemester">Semester</label>
                    <select name="semester" id="fSemester" class="form-select-tw" style="min-width:170px">
                        <option value="1" <?= (int) $semester === 1 ? 'selected' : '' ?>>Ganjil (Jul–Des)</option>
                        <option value="2" <?= (int) $semester === 2 ? 'selected' : '' ?>>Genap (Jan–Jun)</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn-tw btn-tw-sm btn-primary">
                        <i class="bi bi-search mr-1"></i>Tampilkan
                    </button>
                    <a href="<?= site_url('admin/presensi/rekap-semester') ?>" class="btn-tw btn-tw-sm btn-light" title="Kembali ke periode berjalan">
                        <i class="bi bi-x-circle mr-1"></i>Reset
                    </a>
                    <button type="button" id="btnGenerate" data-url="<?= site_url('admin/presensi/rekap-semester/generate?tahun=' . $tahun . '&semester=' . $semester) ?>"
                        class="btn-tw btn-tw-sm btn-success" title="Hitung ulang dari data absensi_harian">
                        <i class="bi bi-arrow-repeat mr-1"></i>Generate Rekap
                    </button>
                    <?php if (!empty($rekap)): ?>
                    <a href="<?= site_url('admin/presensi/rekap-semester/export?tahun=' . $tahun . '&semester=' . $semester) ?>"
                        class="btn-tw btn-tw-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel mr-1"></i>Export Excel
                    </a>
                    <a href="<?= site_url('admin/presensi/rekap-semester/pdf?tahun=' . $tahun . '&semester=' . $semester) ?>"
                        class="btn-tw btn-tw-sm btn-outline-danger" title="Download rekap semester sebagai PDF">
                        <i class="bi bi-file-earmark-pdf mr-1"></i>Download PDF
                    </a>
                    <?php else: ?>
                    <button type="button" class="btn-tw btn-tw-sm btn-outline-success" disabled title="Belum ada data untuk diekspor">
                        <i class="bi bi-file-earmark-excel mr-1"></i>Export Excel
                    </button>
                    <button type="button" class="btn-tw btn-tw-sm btn-outline-danger" disabled title="Belum ada data untuk diekspor">
                        <i class="bi bi-file-earmark-pdf mr-1"></i>Download PDF
                    </button>
                    <?php endif; ?>
                </div>
            </form>
            <p class="text-xs text-slate-400 mb-0 mt-2">
                <i class="bi bi-info-circle mr-1"></i>Rumus: <strong>Hadir</strong> = ONTIME + LATE + WFH + dinas disetujui &middot;
                <strong>Alpha</strong> = hari efektif − hadir − izin/sakit/cuti/dinas-menunggu &middot;
                <strong>%</strong> = hadir ÷ hari efektif × 100. Dinas resmi dihitung hadir.
            </p>
        </div>
    </div>

    <?php if ($isStale): ?>
    <div class="alert-tw alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
        <div>
            <strong>Data mungkin kedaluwarsa.</strong>
            Ada absensi (s/d <?= esc($meta['maxAbsensi'] ?? '-') ?>) atau guru aktif (<?= $totalGuru ?>) yang lebih baru dari waktu generate
            (<?= esc(!empty($meta['generatedAt']) ? date('d/m/Y H:i', strtotime($meta['generatedAt'])) : '-') ?>).
            Klik <strong>Generate Rekap</strong> untuk menghitung ulang.
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="stat-card clickable" data-status-filter="" title="Tampilkan semua">
            <div class="stat-icon bg-blue-50 text-blue-600"><i class="bi bi-people-fill"></i></div>
            <div class="min-w-0">
                <div class="text-2xl font-bold text-slate-800 num-tabular"><?= (int) ($summary['guru'] ?? 0) ?></div>
                <div class="text-slate-400 text-xs font-semibold uppercase">Total Guru Direkap</div>
                <div class="text-xs text-slate-400">dari <?= $totalGuru ?> guru aktif</div>
            </div>
        </div>
        <div class="stat-card clickable" data-status-filter="layak" title="Filter: layak">
            <div class="stat-icon bg-emerald-50 text-emerald-600"><i class="bi bi-check-circle-fill"></i></div>
            <div class="min-w-0">
                <div class="text-2xl font-bold text-emerald-600 num-tabular"><?= (int) ($summary['layak'] ?? 0) ?> <span class="text-xs font-semibold text-slate-400"><?= $pct($summary['layak'] ?? 0) ?>%</span></div>
                <div class="text-slate-400 text-xs font-semibold uppercase">Layak (≥ <?= $minFmt ?>%)</div>
            </div>
        </div>
        <div class="stat-card clickable" data-status-filter="perhatian" title="Filter: perhatian">
            <div class="stat-icon bg-amber-50 text-amber-500"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="min-w-0">
                <div class="text-2xl font-bold text-amber-500 num-tabular"><?= (int) ($summary['perhatian'] ?? 0) ?> <span class="text-xs font-semibold text-slate-400"><?= $pct($summary['perhatian'] ?? 0) ?>%</span></div>
                <div class="text-slate-400 text-xs font-semibold uppercase">Perhatian (<?= $lowFmt ?>–<?= $minFmt ?>%)</div>
            </div>
        </div>
        <div class="stat-card clickable" data-status-filter="tidak_layak" title="Filter: tidak layak">
            <div class="stat-icon bg-red-50 text-red-500"><i class="bi bi-x-octagon-fill"></i></div>
            <div class="min-w-0">
                <div class="text-2xl font-bold text-red-500 num-tabular"><?= (int) ($summary['tidak_layak'] ?? 0) ?> <span class="text-xs font-semibold text-slate-400"><?= $pct($summary['tidak_layak'] ?? 0) ?>%</span></div>
                <div class="text-slate-400 text-xs font-semibold uppercase">Tidak Layak (&lt; <?= $lowFmt ?>%)</div>
            </div>
        </div>
    </div>

    <!-- Alert jika ada tidak layak -->
    <?php if (($summary['tidak_layak'] ?? 0) > 0): ?>
    <div class="alert-tw alert-danger mb-4">
        <i class="bi bi-exclamation-octagon-fill text-2xl"></i>
        <div>
            <strong><?= (int) $summary['tidak_layak'] ?> guru</strong> memiliki kehadiran di bawah
            <?= $lowFmt ?>% dan perlu tindak lanjut segera.
            <button type="button" class="underline font-bold" data-status-filter="tidak_layak">Lihat daftar</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabel Rekap -->
    <div class="card-tw">
        <div class="card-tw-header flex-wrap gap-2">
            <h6 class="font-bold mb-0">
                Rekap Semester <?= $semester == 1 ? 'Ganjil' : 'Genap' ?> <?= $tahun ?>
                <span class="badge-tw badge-info num-tabular"><?= number_format((float) ($summary['rata_kehadiran'] ?? 0), 1) ?>% rata-rata</span>
            </h6>
            <?php if (!empty($rekap)): ?>
            <div class="flex gap-2 flex-wrap items-center ml-auto no-print" role="group" aria-label="Filter status">
                <button type="button" class="filter-chip active" data-status-filter=""><span class="dot" style="background:#64748b"></span>Semua</button>
                <button type="button" class="filter-chip" data-status-filter="layak"><span class="dot" style="background:#22c55e"></span>Layak</button>
                <button type="button" class="filter-chip" data-status-filter="perhatian"><span class="dot" style="background:#f59e0b"></span>Perhatian</button>
                <button type="button" class="filter-chip" data-status-filter="tidak_layak"><span class="dot" style="background:#ef4444"></span>Tidak Layak</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-0">
            <?php if (empty($rekap)): ?>
                <div class="empty-state">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                        <i class="bi bi-inbox text-3xl text-slate-400"></i>
                    </div>
                    <p class="font-semibold text-slate-600">Belum ada data rekap untuk periode ini.</p>
                    <p class="text-sm text-slate-400 mb-4">Klik <strong>Generate Rekap</strong> untuk menghitung dari data absensi harian (<?= esc($periode) ?>).</p>
                    <button type="button" id="btnGenerateEmpty" data-url="<?= site_url('admin/presensi/rekap-semester/generate?tahun=' . $tahun . '&semester=' . $semester) ?>"
                        class="btn-tw btn-tw-sm btn-success">
                        <i class="bi bi-arrow-repeat mr-1"></i>Generate Rekap Sekarang
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw" id="rekapTable" style="min-width:980px;">
                        <thead>
                            <tr>
                                <th class="pl-4">#</th>
                                <th>Nama Guru</th>
                                <th>NIP</th>
                                <th class="text-center">Hari Efektif</th>
                                <th class="text-center text-emerald-600">Hadir</th>
                                <th class="text-center text-amber-500">Telat</th>
                                <th class="text-center text-blue-600">Izin</th>
                                <th class="text-center text-cyan-600">Sakit</th>
                                <th class="text-center text-slate-500">Dinas</th>
                                <th class="text-center text-red-500">Alpha</th>
                                <th class="text-center">Persentase</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center">Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($rekap as $r):
                                $rowClass = match ($r->status_kelayakan) {
                                    'tidak_layak' => 'bg-red-50',
                                    'perhatian'   => 'bg-amber-50',
                                    default       => '',
                                };
                                $barColor = match ($r->status_kelayakan) {
                                    'tidak_layak' => '#ef4444',
                                    'perhatian'   => '#f59e0b',
                                    default       => '#22c55e',
                                };
                                $badgeClass = match ($r->status_kelayakan) {
                                    'tidak_layak' => 'badge-danger',
                                    'perhatian'   => 'badge-warning',
                                    default       => 'badge-success',
                                };
                                $badgeText = match ($r->status_kelayakan) {
                                    'tidak_layak' => 'Tidak Layak',
                                    'perhatian'   => 'Perhatian',
                                    default       => 'Layak',
                                };
                                $barWidth = min(max((float) $r->persentase_kehadiran, 0), 100);
                                $persen   = (float) $r->persentase_kehadiran;
                            ?>
                            <tr class="<?= $rowClass ?>" data-status="<?= esc($r->status_kelayakan) ?>">
                                <td class="pl-4 text-slate-400 num-tabular"><?= $no++ ?></td>
                                <td class="font-semibold" title="<?= esc($r->nama_guru) ?>"><?= esc($r->nama_guru) ?></td>
                                <td class="text-slate-400 num-tabular"><?= esc($r->nip ?? '-') ?></td>
                                <td class="text-center num-tabular"><?= (int) $r->total_hari_efektif ?></td>
                                <td class="text-center font-bold text-emerald-600 num-tabular" title="Termasuk dinas disetujui"><?= (int) $r->total_hadir ?></td>
                                <td class="text-center font-bold text-amber-500 num-tabular"><?= (int) $r->total_telat ?></td>
                                <td class="text-center text-blue-600 num-tabular"><?= (int) $r->total_izin ?></td>
                                <td class="text-center text-cyan-600 num-tabular"><?= (int) $r->total_sakit ?></td>
                                <td class="text-center text-slate-500 num-tabular"><?= (int) $r->total_dinas ?></td>
                                <td class="text-center font-bold text-red-500 num-tabular"><?= (int) $r->total_alpha ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="progress-tw flex-1" style="height:8px;" role="progressbar" aria-valuenow="<?= $barWidth ?>" aria-valuemin="0" aria-valuemax="100" title="<?= number_format($persen, 1) ?>%">
                                            <div class="progress-bar-tw" style="width:<?= $barWidth ?>%; background:<?= $barColor ?>;"></div>
                                        </div>
                                        <span class="font-bold num-tabular" style="min-width:52px; text-align:right;">
                                            <?= number_format($persen, 1) ?>%
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-tw <?= $badgeClass ?>"><?= $badgeText ?></span>
                                </td>
                                <td class="text-center font-bold num-tabular <?= (int) $r->total_poin >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    <?= (int) $r->total_poin >= 0 ? '+' : '' ?><?= number_format((int) $r->total_poin) ?>
                                </td>
                                <td class="text-center text-red-500 font-bold num-tabular">
                                    <?= (float) $r->total_denda > 0 ? 'Rp ' . number_format((float) $r->total_denda, 0, ',', '.') : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($rekap)): ?>
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex flex-wrap gap-x-4 gap-y-1 text-sm">
            <span class="inline-flex items-center">
                <i class="bi bi-clock mr-1"></i>
                Terakhir di-generate:
                <strong class="ml-1"><?= !empty($meta['generatedAt']) ? date('d/m/Y H:i', strtotime($meta['generatedAt'])) : (!empty($rekap[0]->generated_at) ? date('d/m/Y H:i', strtotime($rekap[0]->generated_at)) : '-') ?></strong>
            </span>
            <?php if (!empty($meta['maxAbsensi'])): ?>
            <span class="inline-flex items-center" title="Absensi harian terbaru pada periode ini">
                <i class="bi bi-calendar-check mr-1"></i>
                Absensi terbaru: <strong class="ml-1"><?= date('d/m/Y', strtotime($meta['maxAbsensi'])) ?></strong>
            </span>
            <?php endif; ?>
            <span class="inline-flex items-center">
                Threshold: <strong class="ml-1">Layak ≥ <?= $minFmt ?>% &middot; Perhatian <?= $lowFmt ?>–<?= $minFmt ?>% &middot; Tidak layak &lt; <?= $lowFmt ?>%</strong>
            </span>
            <span class="inline-flex items-center">
                Standar (min. <?= $minFmt ?>% kehadiran per semester)
            </span>
        </div>
        <?php endif; ?>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    // DataTables: cari + sortir + paging
    var table = null;
    if (window.jQuery && $.fn.DataTable && $('#rekapTable').length && !$.fn.DataTable.isDataTable('#rekapTable')) {
        table = $('#rekapTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            language: window.dt_lang_id || {},
            columnDefs: [
                { orderable: false, targets: [10] },
                { type: 'num', targets: [0, 3, 4, 5, 6, 7, 8, 9, 12] }
            ]
        });
    } else if ($.fn.DataTable && $.fn.DataTable.isDataTable('#rekapTable')) {
        table = $('#rekapTable').DataTable();
    }

    // Filter status via chips + kartu ringkasan (teks badge: Layak / Perhatian / Tidak Layak)
    var statusLabels = { 'layak': 'Layak', 'perhatian': 'Perhatian', 'tidak_layak': 'Tidak Layak' };
    function setStatusFilter(status) {
        status = status || '';
        $('[data-status-filter]').removeClass('active selected');
        $('[data-status-filter="' + status + '"]').addClass(status ? 'selected' : 'active');
        $('.filter-chip[data-status-filter="' + status + '"]').addClass('active');
        if (table) {
            if (status && statusLabels[status]) {
                table.column(11).search('^' + statusLabels[status] + '$', true, false).draw();
            } else {
                table.column(11).search('').draw();
            }
        } else {
            $('#rekapTable tbody tr').each(function () {
                var show = !status || $(this).data('status') === status;
                $(this).toggle(show);
            });
        }
    }
    window.setRekapStatusFilter = setStatusFilter;
    $(document).on('click', '[data-status-filter]', function (e) {
        e.preventDefault();
        setStatusFilter($(this).data('status-filter') || '');
    });

    // Generate dengan konfirmasi
    var runGenerate = function (url, $btn) {
        Swal.fire({
            title: 'Generate Rekap?',
            html: 'Menghitung ulang kehadiran <strong><?= esc($periode) ?></strong> dari data absensi harian.<br><span class="text-sm text-slate-400">Data lama periode ini akan diperbarui.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Generate',
            cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) { if ($btn) $btn.prop('disabled', false).removeClass('opacity-60'); return; }
            Swal.fire({
                title: 'Menghitung...',
                text: 'Mohon tunggu, sedang merekap kehadiran semester.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function () { Swal.showLoading(); }
            });
            // Generate = mutasi DB → POST agar CSRF global berlaku (bukan navigasi GET).
            var $f = $('<form method="post" action="' + url + '">'
                + '<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">'
                + '</form>');
            $('body').append($f);
            $f.trigger('submit');
        });
    };

    $('#btnGenerate, #btnGenerateEmpty').on('click', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        $btn.prop('disabled', true).addClass('opacity-60');
        runGenerate($btn.data('url'), $btn);
    });
});
</script>
<?= $this->endSection() ?>
