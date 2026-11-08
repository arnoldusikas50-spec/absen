<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ─── Rekap table: full border grid ─── */
.rekap-wrap {
    overflow: auto;
    max-height: 76vh;
    position: relative;
    border-radius: 0 0 0.75rem 0.75rem;
}

.rekap-table {
    border-collapse: collapse;
    font-size: 0.72rem;
    min-width: 100%;
    table-layout: fixed;
}

.rekap-table th,
.rekap-table td {
    border: 1px solid #e2e8f0;
    padding: 0;
    vertical-align: middle;
    white-space: nowrap;
    text-align: center;
}

/* Sticky header rows */
.rekap-table thead th {
    background: #f8fafc;
    font-weight: 700;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #475569;
    position: sticky;
    top: 0;
    z-index: 3;
    border-bottom: 2px solid #cbd5e1;
}

/* Sticky first two columns */
.sticky-col-1,
.sticky-col-2 {
    position: sticky;
    z-index: 2;
    background: #fff;
}
.sticky-col-1 { left: 0; min-width: 42px; width: 42px; }
.sticky-col-2 { left: 42px; min-width: 190px; width: 190px; text-align: left; }

/* Both sticky + header */
thead .sticky-col-1,
thead .sticky-col-2 { z-index: 5; background: #f8fafc; }

/* Summary columns on the right */
.col-summary {
    min-width: 38px;
    width: 38px;
    font-weight: 700;
    padding: 3px 4px;
}
.col-denda  { min-width: 110px; width: 110px; }
.col-rekomendasi { min-width: 80px; width: 80px; }
.col-status-denda { min-width: 78px; width: 78px; }

/* Day columns */
.col-day {
    min-width: 46px;
    width: 46px;
    padding: 2px 1px;
    line-height: 1.2;
}

/* Row hover */
.rekap-table tbody tr:hover td { background-color: #f8fafc !important; }
.rekap-table tbody tr:hover .sticky-col-1,
.rekap-table tbody tr:hover .sticky-col-2 { background-color: #f1f5f9 !important; }

/* Cell content sizing */
.cell-time { font-size: 0.63rem; line-height: 1.15; }
.cell-in   { border-bottom: 1px solid #e2e8f0; padding-bottom: 1px; margin-bottom: 1px; }

/* Alpha (merah) row - white text */
.cell-alpha  { color: #b91c1c; font-weight: 800; }
.bg-cell-alpha { background-color: #fef2f2 !important; }
.bg-cell-hadir { background-color: #f0fdf4 !important; }
.bg-cell-telat { background-color: #fffbeb !important; }
.bg-cell-dinas { background-color: #ecfeff !important; }
.bg-cell-izin  { background-color: var(--primary-50) !important; }
.bg-cell-wfh   { background-color: #f0fdf4 !important; }
.bg-cell-libur { background-color: #f1f5f9 !important; }
.bg-cell-holiday { background-color: #fef2f2 !important; }
.bg-col-weekend { background-color: #fef9f9 !important; }

/* Poin positive/negative color */
.poin-pos { color: #059669; }
.poin-neg { color: #dc2626; }

/* Legend dots */
.legend-dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Print */
@media print {
    @page { size: landscape; margin: 8mm; }
    body { background: white !important; }
    .content-header, .card-tw-header, .no-print { display: none !important; }
    .rekap-wrap { max-height: none !important; overflow: visible !important; }
    .rekap-table th, .rekap-table td { border: 1px solid #999 !important; }
    .sticky-col-1, .sticky-col-2 { position: static !important; }
    thead th { position: static !important; background: #e2e8f0 !important; }
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ─── Page Header ─── -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3>
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 mr-2 shrink-0">
                    <i class="bi bi-file-earmark-spreadsheet text-sm"></i>
                </span>
                Rekap Presensi Bulanan
            </h3>
    <?php
    $namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $bulanInt  = (int) $bulan;   // "09" → 9, safe for array key lookup
    ?>
            <p class="text-xs text-slate-400 ml-10 mt-0.5">
                <?= $namaBulan[$bulanInt] ?? '' ?> <?= $tahun ?>
                &mdash; <?= count($users) ?> guru
            </p>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= base_url('admin') ?>" class="no-underline"><i class="bi bi-house-door mr-1"></i>Admin</a>
            <span class="sep">/</span>
            <a href="<?= base_url('admin/presensi') ?>" class="no-underline">Presensi</a>
            <span class="sep">/</span>
            <span class="active">Rekap</span>
        </nav>
    </div>
</div>

<div class="content-area">

    <!-- ─── Tab Switcher (Guru vs Siswa) ─── -->
    <div class="flex items-center gap-2 mb-4 no-print">
        <a href="<?= site_url('admin/presensi/rekap?bulan=' . $bulan . '&tahun=' . $tahun) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white shadow-sm shadow-emerald-500/20 no-underline">
            <i class="bi bi-person-badge"></i> Rekap Presensi Guru
        </a>
        <a href="<?= site_url('admin/presensi/siswa/rekap?bulan=' . $bulan . '&tahun=' . $tahun) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 transition-colors no-underline">
            <i class="bi bi-people-fill"></i> Rekap Presensi Siswa
        </a>
    </div>

<div class="card-tw">

    <!-- ─── Filter bar ─── -->
    <div class="card-tw-header flex-wrap gap-3 no-print">
        <form method="get" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center gap-2 mr-1">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <i class="bi bi-funnel text-emerald-600 text-sm"></i>
                </div>
                <h6 class="font-bold text-slate-700 mb-0">Filter Periode</h6>
            </div>

            <!-- Bulan -->
            <select name="bulan" class="form-select-tw" style="width:140px;">
                <?php
                $namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                foreach ($namaBulan as $m => $nm):
                ?>
                <option value="<?= $m ?>" <?= $m == (int)$bulan ? 'selected' : '' ?>><?= $nm ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Tahun -->
            <select name="tahun" class="form-select-tw" style="width:95px;">
                <?php for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                <option value="<?= $y ?>" <?= $y == (int)$tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="btn-tw btn-tw-sm btn-primary">
                <i class="bi bi-search mr-1"></i> Tampilkan
            </button>

            <div class="flex items-center gap-1.5 ml-auto">
                <a href="<?= site_url('admin/absensi/rekap/export?bulan=' . $bulan . '&tahun=' . $tahun) ?>"
                   target="_blank" class="btn-tw btn-tw-sm btn-success">
                    <i class="bi bi-file-earmark-excel mr-1"></i> Excel
                </a>
                <a href="<?= site_url('admin/absensi/rekap/pdf?bulan=' . $bulan . '&tahun=' . $tahun) ?>"
                   target="_blank" class="btn-tw btn-tw-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf mr-1"></i> PDF
                </a>
                <button type="button" id="btnKirimRekap"
                        class="btn-tw btn-tw-sm"
                        style="background:#0f9d58; color:#fff; border-color:#0b7a43;"
                        onclick="bukaModalKirimRekap()">
                    <i class="bi bi-send mr-1"></i> Kirim ke WA &amp; TG
                </button>
                <a href="<?= site_url('admin/presensi') ?>" class="btn-tw btn-tw-sm btn-light">
                    <i class="bi bi-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </form>
    </div>

    <?php if (empty($users)): ?>
    <!-- ─── Empty state ─── -->
    <div class="card-tw-body text-center py-16">
        <i class="bi bi-people text-5xl text-slate-300 block mb-3"></i>
        <p class="text-slate-400 font-medium">Belum ada data guru untuk periode ini</p>
    </div>

    <?php else: ?>

    <!-- ─── Legend ─── -->
    <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center gap-3 text-xs text-slate-500 no-print">
        <span class="font-semibold text-slate-600">Keterangan:</span>
        <?php
        $legends = [
            ['bg-cell-hadir border border-emerald-200',  '●', 'text-emerald-600', 'Hadir tepat waktu'],
            ['bg-cell-telat border border-amber-200',    '●', 'text-amber-500',   'Terlambat'],
            ['bg-cell-dinas border border-cyan-200',     'DNS','text-cyan-600',   'Dinas Luar'],
            ['bg-cell-wfh border border-emerald-200',    'WFH','text-emerald-600','WFH'],
            ['bg-cell-izin border border-blue-200',      'IZN','text-blue-600',   'Izin/Sakit/Cuti'],
            ['bg-cell-alpha border border-red-200',      'X',  'text-red-500',    'Alpha / Tidak hadir'],
            ['bg-cell-libur border border-slate-200',    '—',  'text-slate-400',  'Libur/Weekend'],
        ];
        foreach ($legends as [$bg, $sym, $tc, $label]):
        ?>
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold <?= $bg ?> <?= $tc ?>"><?= $sym ?></span>
            <span><?= $label ?></span>
        </span>
        <?php endforeach; ?>
    </div>

    <!-- ─── Rekap Table ─── -->
    <?php
    $daysInMonth       = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
    $dayLabels         = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    // Array kode hari dari AttendancePolicy (absensi_setting, ternormalisasi)
    // — bukan kolom di tabel `setting` sekolah yang memang tidak ada.
    $hariLiburMingguan = $hariLiburMingguan ?? [];
    ?>

    <div class="rekap-wrap">
    <table class="rekap-table">
        <thead>
            <!-- Row 1: group headers -->
            <tr>
                <th rowspan="2" class="sticky-col-1 col-day" style="width:42px;">No</th>
                <th rowspan="2" class="sticky-col-2" style="text-align:left; padding:4px 8px; width:190px;">
                    Nama Guru / NIP
                </th>
                <th colspan="<?= $daysInMonth ?>" class="py-2">
                    Kehadiran — <?= $namaBulan[$bulanInt] ?> <?= $tahun ?>
                </th>
                <!-- Summary group -->
                <th class="col-summary bg-emerald-50 text-emerald-700 py-2" title="Hadir">H</th>
                <th class="col-summary bg-amber-50 text-amber-600 py-2" title="Terlambat">TL</th>
                <th class="col-summary bg-cyan-50 text-cyan-700 py-2" title="Dinas">D</th>
                <th class="col-summary bg-green-50 text-green-700 py-2" title="WFH">W</th>
                <th class="col-summary bg-blue-50 text-blue-700 py-2" title="Izin/Sakit">I/S</th>
                <th class="col-summary bg-red-50 text-red-600 py-2" title="Alpha">A</th>
                <th class="col-summary bg-slate-100 text-slate-700 py-2" style="min-width:46px;" title="Total Poin">Poin</th>
                <th class="col-denda bg-orange-50 text-orange-700 py-2" title="Total Denda">Denda</th>
                <th class="col-rekomendasi bg-purple-50 text-purple-700 py-2">Rekomendasi</th>
                <th class="col-status-denda bg-teal-50 text-teal-700 py-2">Denda</th>
            </tr>
            <!-- Row 2: day headers -->
            <tr>
                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $ts        = mktime(0, 0, 0, $bulan, $d, $tahun);
                    $dw        = date('w', $ts);   // 0=Sun
                    $dayEn     = date('D', $ts);
                    $dayNumISO = (int) date('N', $ts);
                    $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    $isHoliday = isset($holidays[$dateStr]);
                    $hdClass   = (attendance_is_weekly_holiday($dayEn, $dayNumISO, $jadwalKerja, $hariLiburMingguan) || $isHoliday)
                                  ? 'bg-red-50 text-red-400' : '';
                ?>
                <th class="col-day <?= $hdClass ?>" style="padding:3px 2px;">
                    <div class="font-bold"><?= $dayLabels[$dw] ?></div>
                    <div style="font-size:0.64rem; font-weight:400;"><?= sprintf('%02d', $d) ?></div>
                </th>
                <?php endfor; ?>
                <!-- filler for summary cols: already covered by rowspan=1 on group th -->
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        foreach ($users as $user):
            $cntH = $cntTL = $cntD = $cntW = $cntIS = $cntA = $cntARecorded = 0;
        ?>
        <tr>
            <!-- No -->
            <td class="sticky-col-1 text-slate-400 font-mono text-xs"><?= $no++ ?></td>

            <!-- Nama -->
            <td class="sticky-col-2" style="padding: 4px 8px; text-align:left;">
                <div class="font-semibold text-slate-800 truncate" style="max-width:180px;">
                    <?= esc($user->nama_guru) ?>
                </div>
                <div class="text-slate-400" style="font-size:0.65rem;">
                    <?= esc($user->nip ?? '—') ?>
                </div>
            </td>

            <!-- Day cells -->
            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $ts        = strtotime($dateStr);
                $dayEn     = date('D', $ts);
                $dayNum    = (int) date('N', $ts); // 1=Senin .. 7=Minggu
                $isHoliday = isset($holidays[$dateStr]);
                $isJadwalLibur = isset($jadwalKerja[$dayNum]) && (int)($jadwalKerja[$dayNum]->is_libur ?? 0) === 1;
                // Sumber tunggal: libur mingguan = gabungan keduanya. Tanpa ini,
                // sel pada hari yang ditandai libur di Jadwal Kerja GTK masih
                // ikut dihitung sebagai hari kerja.
                $isLiburMingguan = attendance_is_weekly_holiday($dayEn, $dayNum, $jadwalKerja, $hariLiburMingguan);
                $rec       = $mapByUser[$user->id][$dateStr] ?? null;

                // Skip attendance on holiday (don't count)
                if ($isHoliday) $rec = null;

                // Determine cell style + content
                $bgClass   = '';
                $cellInner = '';
                $isAlpha   = false;

                if ($isLiburMingguan || $isHoliday) {
                    $bgClass   = $isHoliday ? 'bg-cell-holiday' : 'bg-col-weekend';
                    $cellInner = '<span style="color:#cbd5e1;">—</span>';

                } elseif ($rec) {
                    $status = $rec->status_masuk ?? '';

                    if (in_array($status, ['ONTIME', 'LATE'])) {
                        $cntH++;
                        $bgClass  = $status === 'LATE' ? 'bg-cell-telat' : 'bg-cell-hadir';
                        if ($status === 'LATE') $cntTL++;
                        $inT  = !empty($rec->jam_masuk)  ? date('H:i', strtotime($rec->jam_masuk))  : '—';
                        $outT = !empty($rec->jam_pulang) ? date('H:i', strtotime($rec->jam_pulang)) : '—';
                        $inColor  = $status === 'LATE' ? 'color:#b45309;' : 'color:#059669;';
                        $outColor = 'color:#94a3b8;';
                        $cellInner = '<div class="cell-time" style="padding:2px 3px;">
                            <div class="cell-in font-bold" style="'.$inColor.'">'.$inT.'</div>
                            <div style="'.$outColor.'">'.$outT.'</div>
                        </div>';

                    } elseif (str_contains($status, 'DINAS')) {
                        $cntD++; $bgClass = 'bg-cell-dinas';
                        $cellInner = '<span style="color:#0e7490; font-weight:700; font-size:0.63rem;">DNS</span>';

                    } elseif ($status === 'WFH') {
                        $cntW++; $bgClass = 'bg-cell-wfh';
                        $cellInner = '<span style="color:#059669; font-weight:700; font-size:0.63rem;">WFH</span>';

                    } elseif (in_array($status, ['IJIN','IZIN','SAKIT','CUTI'])) {
                        $cntIS++; $bgClass = 'bg-cell-izin';
                        $lbl = match($status) {
                            'SAKIT'       => 'SKT',
                            'CUTI'        => 'CTI',
                            default       => 'IZN',
                        };
                        $cellInner = '<span style="color:var(--primary-700); font-weight:700; font-size:0.63rem;">'.$lbl.'</span>';

                    } elseif ($status === 'PENDING_VERIFICATION') {
                        $cellInner = '<span style="color:#d97706; font-size:0.63rem;">PND</span>';

                    } elseif (in_array($status, ['ALPHA', 'ALPA'], true)) {
                        // Alpha tercatat: tampilkan sebagai alpha dan hitung di kolom A.
                        // Nominal & poinnya sudah masuk via SUM map (di bawah), jadi
                        // tidak menambah $cntA (milik tanggal bolong) agar tidak double.
                        $cntARecorded++;
                        $bgClass  = 'bg-cell-alpha';
                        $cellInner = '<span class="cell-alpha">✕</span>';

                    } else {
                        $cellInner = '<span style="color:#94a3b8; font-size:0.63rem;">' . esc(substr($status, 0, 3)) . '</span>';
                    }

                } else {
                    // Tanpa record: alpha hanya bila hari kerja lampau, bukan
                    // hari libur dan bukan izin approved.
                    // Libur mingguan sudah tertangani di cabang atas; yang
                    // dicek di sini libur nasional supaya hari merah tidak
                    // ditandai absen.
                    $isLeave = isset($leaveMap[$user->id][$dateStr]);
                    if (!$isHoliday && !$isLeave && $dateStr < date('Y-m-d')) {
                        $cntA++;
                        $isAlpha  = true;
                        $bgClass  = 'bg-cell-alpha';
                        $cellInner = '<span class="cell-alpha">✕</span>';
                    } else {
                        $bgClass   = 'bg-cell-libur';
                        $cellInner = '<span style="color:#e2e8f0;">—</span>';
                    }
                }
            ?>
            <td class="col-day <?= $bgClass ?>" style="height:38px;">
                <?= $cellInner ?>
            </td>
            <?php endfor; ?>

            <!-- Summary columns -->
            <?php
            $totalPoin        = 0;
            $totalDendaHarian = 0;
            foreach ($mapByUser[$user->id] ?? [] as $dt => $r) {
                // Lewati hari libur — nasional DAN mingguan. Dulu hanya
                // nasional yang dicek, jadi baris presence di hari yang
                // ditandai libur di Jadwal Kerja GTK masih ikut menambah
                // denda dan poin.
                if (attendance_is_holiday_date($dt, $jadwalKerja, $hariLiburMingguan, $holidays)) {
                    continue;
                }
                $totalPoin        += (int)   ($r->points ?? 0);
                $totalDendaHarian += (float) ($r->denda  ?? 0);
            }
            $totalPoin       += ($cntA * -abs((int)($absenSetting->point_alpa ?? 5)));
            $dendaAlpha       = $cntA * (int)($absenSetting->denda_alpha_per_hari ?? 0);
            $totalDendaRekap  = $totalDendaHarian + $dendaAlpha;

            // Kolom A & rekomendasi memakai total alpha (tercatat + bolong),
            // konsisten dengan halaman denda. Nominal/poin tercatat sudah masuk
            // via SUM di atas sehingga $cntA (bolong) tidak diubah.
            $cntATotal = $cntA + $cntARecorded;

            $rekomendasi = 'Baik'; $rekClass = 'badge-success';
            if      ($cntATotal >= 3 || $totalPoin <= -100)                      { $rekomendasi = 'SP 2';     $rekClass = 'badge-danger'; }
            elseif  ($cntATotal >= 1 || $cntTL >= 10 || $totalPoin <= -50)       { $rekomendasi = 'SP 1';     $rekClass = 'badge-warning'; }
            elseif  ($cntTL >= 5 || $totalPoin <= -20)                      { $rekomendasi = 'Pembinaan';$rekClass = 'badge-info'; }

            $isLunas = isset($lunasMap[$user->id]);
            $poinClass = $totalPoin >= 0 ? 'poin-pos' : 'poin-neg';
            ?>
            <td class="col-summary bg-emerald-50 text-emerald-700 font-bold"><?= $cntH ?></td>
            <td class="col-summary bg-amber-50 text-amber-600 font-bold"><?= $cntTL ?></td>
            <td class="col-summary bg-cyan-50 text-cyan-700 font-bold"><?= $cntD ?></td>
            <td class="col-summary bg-green-50 text-green-700 font-bold"><?= $cntW ?></td>
            <td class="col-summary bg-blue-50 text-blue-700 font-bold"><?= $cntIS ?></td>
                <td class="col-summary bg-red-50 text-red-600 font-bold"><?= $cntATotal ?></td>
            <td class="col-summary bg-slate-50 font-bold <?= $poinClass ?>" style="min-width:46px;">
                <?= ($totalPoin >= 0 ? '+' : '') . $totalPoin ?>
            </td>
            <td class="col-denda" style="padding:3px 6px; text-align:right;">
                <span class="font-semibold text-orange-700" style="font-size:0.7rem;">
                    <?= $totalDendaRekap > 0 ? 'Rp&nbsp;' . number_format($totalDendaRekap, 0, ',', '.') : '<span style="color:#cbd5e1;">—</span>' ?>
                </span>
            </td>
            <td class="col-rekomendasi">
                <span class="badge-tw <?= $rekClass ?>" style="font-size:0.65rem; padding:2px 6px;"><?= $rekomendasi ?></span>
            </td>
            <td class="col-status-denda">
                <?php if ($isLunas): ?>
                <span class="badge-tw badge-success" style="font-size:0.65rem; padding:2px 6px;">
                    <i class="bi bi-check-circle mr-0.5"></i>Lunas
                </span>
                <?php else: ?>
                <span class="badge-tw badge-secondary" style="font-size:0.65rem; padding:2px 6px;">
                    <i class="bi bi-clock mr-0.5"></i>Belum
                </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- /rekap-wrap -->

    <!-- Footer info -->
    <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-400 no-print">
        <span>
            <i class="bi bi-info-circle mr-1"></i>
            H=Hadir &nbsp;·&nbsp; TL=Terlambat &nbsp;·&nbsp; D=Dinas &nbsp;·&nbsp; W=WFH &nbsp;·&nbsp; I/S=Izin/Sakit &nbsp;·&nbsp; A=Alpha
        </span>
        <span class="font-medium text-slate-500">
            Total <?= count($users) ?> guru &nbsp;·&nbsp; <?= $namaBulan[$bulanInt] ?> <?= $tahun ?>
        </span>
    </div>

    <?php endif; // empty users ?>

</div><!-- /card-tw -->
</div><!-- /content-area -->

<!-- ─── Modal Kirim Rekap ke WA & Telegram ─── -->
<div id="modalKirimRekap"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none !important; background:rgba(15,23,42,0.5);"
     role="dialog" aria-modal="true" aria-labelledby="modalKirimRekapTitle">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" style="max-height:90vh; overflow-y:auto;">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                    <i class="bi bi-send text-emerald-600"></i>
                </div>
                <div>
                    <h6 id="modalKirimRekapTitle" class="font-bold text-slate-800 mb-0">Kirim Rekap ke Grup</h6>
                    <p class="text-xs text-slate-400 mb-0">WhatsApp &amp; Telegram</p>
                </div>
            </div>
            <button type="button" onclick="tutupModalKirimRekap()"
                    class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 space-y-4">

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 flex items-start gap-3">
                <i class="bi bi-file-earmark-pdf text-red-500 text-xl shrink-0 mt-0.5"></i>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-700 text-sm mb-0.5">PDF Rekap Presensi Guru</p>
                    <p class="text-xs text-slate-500 mb-0">
                        Periode: <span id="kirimRekapPeriode" class="font-medium text-slate-700"></span><br>
                        <span id="kirimRekapJumlahGuru" class="text-slate-500"></span>
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 flex items-start gap-2.5 text-xs text-blue-700">
                <i class="bi bi-info-circle shrink-0 mt-0.5"></i>
                <span>PDF akan dibuat ulang dari data terkini lalu dikirim ke <b>grup WhatsApp</b> dan <b>grup Telegram</b> yang dikonfigurasi di Pengaturan Notifikasi.</span>
            </div>

            <!-- Hasil pengiriman -->
            <div id="kirimRekapHasil" style="display:none;">
                <div id="kirimRekapHasilIsi" class="rounded-xl border p-4 text-sm space-y-2"></div>
            </div>

        </div>

        <!-- Footer -->
        <div class="px-6 pb-5 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4" id="kirimRekapFooter">
            <button type="button" onclick="tutupModalKirimRekap()"
                    class="btn-tw btn-tw-sm btn-light" id="btnBatalKirim">
                Batal
            </button>
            <button type="button" onclick="eksekusiKirimRekap()"
                    id="btnKonfirmasiKirim"
                    class="btn-tw btn-tw-sm"
                    style="background:#0f9d58; color:#fff; border-color:#0b7a43; min-width:120px;">
                <i class="bi bi-send mr-1"></i> Kirim Sekarang
            </button>
        </div>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    // ── Data periode dari PHP ──────────────────────────────────────
    const BULAN       = <?= json_encode((int) $bulan) ?>;
    const TAHUN       = <?= json_encode((int) $tahun) ?>;
    const JUMLAH_GURU = <?= json_encode(count($users)) ?>;
    const URL_KIRIM   = '<?= site_url('admin/presensi/rekap/kirim') ?>';

    const namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];

    // ── Elemen ────────────────────────────────────────────────────
    const modal       = document.getElementById('modalKirimRekap');
    const elPeriode   = document.getElementById('kirimRekapPeriode');
    const elJumlah    = document.getElementById('kirimRekapJumlahGuru');
    const elHasil     = document.getElementById('kirimRekapHasil');
    const elHasilIsi  = document.getElementById('kirimRekapHasilIsi');
    const btnKirim    = document.getElementById('btnKonfirmasiKirim');
    const btnBatal    = document.getElementById('btnBatalKirim');

    // ── Helpers ──────────────────────────────────────────────────
    function tampilkanModal() {
        elPeriode.textContent  = (namaBulan[BULAN] || BULAN) + ' ' + TAHUN;
        elJumlah.textContent   = JUMLAH_GURU + ' guru';
        elHasil.style.display  = 'none';
        elHasilIsi.innerHTML   = '';
        resetTombol();
        modal.style.setProperty('display', 'flex', 'important');
        document.body.style.overflow = 'hidden';
    }

    function sembunyikanModal() {
        modal.style.setProperty('display', 'none', 'important');
        document.body.style.overflow = '';
    }

    function resetTombol() {
        btnKirim.disabled    = false;
        btnKirim.innerHTML   = '<i class="bi bi-send mr-1"></i> Kirim Sekarang';
        btnBatal.disabled    = false;
    }

    function setLoading(aktif) {
        if (aktif) {
            btnKirim.disabled  = true;
            btnKirim.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1 align-middle"></span> Mengirim…';
            btnBatal.disabled  = true;
        } else {
            resetTombol();
        }
    }

    function tampilkanHasil(berhasil, pesan, detail) {
        const warna = berhasil
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-red-200 bg-red-50 text-red-800';
        const ikon  = berhasil ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle-fill text-red-500';

        let html = '<div class="flex items-start gap-2.5">'
            + '<i class="bi ' + ikon + ' text-lg shrink-0 mt-0.5"></i>'
            + '<div class="min-w-0">'
            + '<p class="font-semibold mb-1">' + escHtml(pesan) + '</p>';

        if (detail) {
            const baris = [];
            // Telegram
            const tgOk  = detail.telegram === true;
            const waOk  = detail.whatsapp === true;
            baris.push('<span class="inline-flex items-center gap-1">'
                + '<i class="bi ' + (tgOk ? 'bi-check-circle text-emerald-500' : 'bi-x-circle text-red-400') + '"></i>'
                + ' Telegram: ' + (tgOk ? 'terkirim' : 'gagal') + '</span>');
            baris.push('<span class="inline-flex items-center gap-1">'
                + '<i class="bi ' + (waOk ? 'bi-check-circle text-emerald-500' : 'bi-x-circle text-red-400') + '"></i>'
                + ' WhatsApp: ' + (waOk ? 'terkirim' : 'gagal') + '</span>');
            html += '<div class="flex flex-wrap gap-x-4 gap-y-1 text-xs mt-1">' + baris.join('') + '</div>';
        }

        html += '</div></div>';

        elHasilIsi.className = 'rounded-xl border p-4 text-sm space-y-2 ' + warna;
        elHasilIsi.innerHTML = html;
        elHasil.style.display = 'block';

        // Setelah berhasil: ganti tombol kirim jadi "Tutup"
        if (berhasil) {
            btnKirim.style.display = 'none';
            btnBatal.disabled      = false;
            btnBatal.textContent   = 'Tutup';
        } else {
            btnKirim.disabled    = false;
            btnKirim.innerHTML   = '<i class="bi bi-arrow-clockwise mr-1"></i> Coba Lagi';
            btnBatal.disabled    = false;
        }
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Aksi utama ────────────────────────────────────────────────
    async function eksekusi() {
        setLoading(true);
        elHasil.style.display = 'none';

        const form = new FormData();
        form.append('bulan', BULAN);
        form.append('tahun', TAHUN);

        // Token CSRF diambil dari layout (CSRF_NAME/CSRF_HASH), sama seperti
        // halaman notif_config. Sebelumnya script ini mencari meta tag dan
        // cookie milik CI3 — keduanya tidak ada di layout ini, jadi
        // request terkirim tanpa token dan ditolak filter CSRF.
        const csrfName = (typeof CSRF_NAME !== 'undefined') ? CSRF_NAME : 'csrf_test_name';
        const csrfHash = (typeof CSRF_HASH !== 'undefined') ? CSRF_HASH : '';
        if (csrfHash === '') {
            setLoading(false);
            tampilkanHasil(false, 'Token CSRF tidak tersedia. Muat ulang halaman lalu coba lagi.', null);
            return;
        }
        form.append(csrfName, csrfHash);

        try {
            const res = await fetch(URL_KIRIM, {
                method: 'POST',
                body: form,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfHash,
                },
            });

            let json;
            try { json = await res.json(); } catch (_) { json = null; }

            const berhasil = json && (json.status === true || json.status === 1 || json.status === 'true');
            const pesan    = json?.message || (berhasil ? 'Rekap berhasil dikirim.' : 'Gagal mengirim rekap.');
            const detail   = json?.data || null;

            tampilkanHasil(berhasil, pesan, detail);
        } catch (err) {
            tampilkanHasil(false, 'Koneksi gagal: ' + err.message, null);
        } finally {
            setLoading(false);
        }
    }

    // ── Tutup dengan klik backdrop ────────────────────────────────
    modal.addEventListener('click', function (e) {
        if (e.target === modal) tutupModalKirimRekap();
    });

    // ── Tutup dengan Esc ──────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            tutupModalKirimRekap();
        }
    });

    // ── Expose ke HTML onclick ────────────────────────────────────
    window.bukaModalKirimRekap  = tampilkanModal;
    window.tutupModalKirimRekap = sembunyikanModal;
    window.eksekusiKirimRekap   = eksekusi;
})();
</script>
<?= $this->endSection() ?>
