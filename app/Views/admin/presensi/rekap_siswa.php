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
.col-persen {
    min-width: 55px;
    width: 55px;
    font-weight: 700;
    padding: 3px 4px;
}

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

/* Status cell styling */
.cell-alpha   { color: #b91c1c; font-weight: 800; }
.bg-cell-alpha { background-color: #fef2f2 !important; }
.cell-bdr   { color: #0f766e; font-weight: 800; }
.bg-cell-bdr { background-color: #f0fdfa !important; }
.bg-cell-hadir { background-color: #f0fdf4 !important; }
.bg-cell-telat { background-color: #fffbeb !important; }
.bg-cell-sakit { background-color: #fefce8 !important; }
.bg-cell-izin  { background-color: var(--primary-50) !important; }
.bg-cell-libur { background-color: #f1f5f9 !important; }
.bg-cell-holiday { background-color: #fef2f2 !important; }
.bg-col-weekend { background-color: #fef9f9 !important; }

/* Print */
@media print {
    @page { size: landscape; margin: 8mm; }
    body { background: white !important; }
    .content-header, .card-tw-header, .no-print, .nav-tabs-rekap { display: none !important; }
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

<?php
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$bulanInt  = (int) $bulan;
$dayLabels = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
?>

<!-- ─── Page Header ─── -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3>
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-sky-100 text-sky-600 mr-2 shrink-0">
                    <i class="bi bi-people-fill text-sm"></i>
                </span>
                Rekap Presensi Bulanan Siswa
            </h3>
            <p class="text-xs text-slate-400 ml-10 mt-0.5">
                <?= $namaBulan[$bulanInt] ?? '' ?> <?= $tahun ?>
                &mdash; <?= count($students) ?> siswa
                <?php if (!empty($kelasInfo)): ?>
                    &middot; Kelas <strong><?= esc($kelasInfo->nama_kelas) ?></strong>
                <?php else: ?>
                    &middot; <strong>Semua Kelas</strong>
                <?php endif; ?>
                &middot; <?= esc($hariEfektifLabel ?? ($hariEfektif . ' hari efektif')) ?>
            </p>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= base_url('admin') ?>" class="no-underline"><i class="bi bi-house-door mr-1"></i>Admin</a>
            <span class="sep">/</span>
            <a href="<?= base_url('admin/presensi/siswa/harian') ?>" class="no-underline">Presensi Siswa</a>
            <span class="sep">/</span>
            <span class="active">Rekap</span>
        </nav>
    </div>
</div>

<div class="content-area">

    <!-- ─── Tab Switcher (Guru vs Siswa) ─── -->
    <div class="flex items-center gap-2 mb-4 nav-tabs-rekap no-print">
        <a href="<?= site_url('admin/presensi/rekap?bulan=' . $bulan . '&tahun=' . $tahun) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 transition-colors no-underline">
            <i class="bi bi-person-badge"></i> Rekap Presensi Guru
        </a>
        <a href="<?= site_url('admin/presensi/siswa/rekap?bulan=' . $bulan . '&tahun=' . $tahun . '&id_kelas=' . $idKelas) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-sky-600 text-white shadow-sm shadow-sky-500/20 no-underline">
            <i class="bi bi-people-fill"></i> Rekap Presensi Siswa
        </a>
    </div>

<div class="card-tw">

    <!-- ─── Filter bar ─── -->
    <div class="card-tw-header flex-wrap gap-3 no-print">
        <form method="get" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center gap-2 mr-1">
                <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center shrink-0">
                    <i class="bi bi-funnel text-sky-600 text-sm"></i>
                </div>
                <h6 class="font-bold text-slate-700 mb-0">Filter Periode & Rombel</h6>
            </div>

            <!-- Kelas -->
            <select name="id_kelas" class="form-select-tw" style="min-width:160px;">
                <option value="0" <?= $idKelas === 0 ? 'selected' : '' ?>>— Semua Kelas —</option>
                <?php foreach ($kelases as $k): ?>
                <option value="<?= $k->id_kelas ?>" <?= (int)$idKelas === (int)$k->id_kelas ? 'selected' : '' ?>>
                    <?= esc($k->nama_kelas) ?> (<?= (int)($k->jumlah_siswa ?? 0) ?> siswa)
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Bulan -->
            <select name="bulan" class="form-select-tw" style="width:140px;">
                <?php foreach ($namaBulan as $m => $nm): ?>
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
                <a href="<?= site_url('admin/presensi/siswa/rekap/export?bulan=' . $bulan . '&tahun=' . $tahun . '&id_kelas=' . $idKelas) ?>"
                   target="_blank" class="btn-tw btn-tw-sm btn-success">
                    <i class="bi bi-file-earmark-excel mr-1"></i> Excel
                </a>
                <a href="<?= site_url('admin/presensi/siswa/rekap/pdf?bulan=' . $bulan . '&tahun=' . $tahun . '&id_kelas=' . $idKelas) ?>"
                   target="_blank" class="btn-tw btn-tw-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf mr-1"></i> PDF
                </a>
                <a href="<?= site_url('admin/presensi/siswa/harian') ?>" class="btn-tw btn-tw-sm btn-light">
                    <i class="bi bi-arrow-left mr-1"></i> Monitoring Harian
                </a>
            </div>
        </form>
    </div>

    <?php if (empty($students)): ?>
    <!-- ─── Empty state ─── -->
    <div class="card-tw-body text-center py-16">
        <i class="bi bi-people text-5xl text-slate-300 block mb-3"></i>
        <p class="text-slate-400 font-medium">Belum ada data siswa untuk rombel atau periode ini</p>
    </div>

    <?php else: ?>

    <!-- ─── Legend ─── -->
    <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center gap-3 text-xs text-slate-500 no-print">
        <span class="font-semibold text-slate-600">Keterangan:</span>
        <?php
        $legends = [
            ['bg-cell-hadir border border-emerald-200', '●',  'text-emerald-600', 'Hadir tepat waktu'],
            ['bg-cell-telat border border-amber-200',   '●',  'text-amber-500',   'Terlambat'],
            ['bg-cell-sakit border border-yellow-200',  'S',  'text-yellow-600',  'Sakit'],
            ['bg-cell-izin border border-blue-200',     'I',  'text-blue-600',    'Izin'],
            ['bg-cell-alpha border border-red-200',     '✕',  'text-red-500',     'Alpa / Tanpa Keterangan'],
            ['bg-cell-bdr border border-teal-200',      'R',  'text-teal-600',    'BDR / Belajar dari Rumah'],
            ['bg-cell-libur border border-slate-200',   '—',  'text-slate-400',   'Libur / Akhir Pekan'],
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
    <div class="rekap-wrap">
    <table class="rekap-table">
        <thead>
            <!-- Row 1: group headers -->
            <tr>
                <th rowspan="2" class="sticky-col-1 col-day" style="width:42px;">No</th>
                <th rowspan="2" class="sticky-col-2" style="text-align:left; padding:4px 8px; width:190px;">
                    Nama Siswa / NIS
                </th>
                <th colspan="<?= $daysInMonth ?>" class="py-2">
                    Kehadiran &mdash; <?= $namaBulan[$bulanInt] ?> <?= $tahun ?>
                    <?php if (!empty($kelasInfo)): ?>
                        (<?= esc($kelasInfo->nama_kelas) ?>)
                    <?php endif; ?>
                </th>
                <!-- Summary group -->
                <th class="col-summary bg-emerald-50 text-emerald-700 py-2" title="Total Hadir">H</th>
                <th class="col-summary bg-amber-50 text-amber-600 py-2" title="Terlambat">TL</th>
                <th class="col-summary bg-yellow-50 text-yellow-700 py-2" title="Sakit">S</th>
                <th class="col-summary bg-blue-50 text-blue-700 py-2" title="Izin">I</th>
                <th class="col-summary bg-red-50 text-red-600 py-2" title="Alpa">A</th>
                <th class="col-summary bg-teal-50 text-teal-700 py-2" title="Belajar dari Rumah">R</th>
                <th class="col-persen bg-indigo-50 text-indigo-700 py-2" title="Persentase Kehadiran">%</th>
                <th class="col-summary bg-rose-50 text-rose-700 py-2" title="Total denda alpha &amp; bolos (sudah dipotong batas denda siswa per bulan)">Denda</th>
            </tr>
            <!-- Row 2: day headers -->
            <tr>
                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    $ts        = strtotime($dateStr);
                    $dw        = date('w', $ts); // 0=Sun
                    $dayEn     = date('D', $ts);
                    // Gabungan dua sumber, sama seperti sel di bawahnya — kalau
                    // tidak, header dan isi tabel bisabeda pendapat soal hari libur.
                    $isLibur   = attendance_is_weekly_holiday($dayEn, (int) date('N', $ts), $jadwalKerja ?? [], $hariLiburMingguan ?? []);
                    $isHoliday = isset($holidays[$dateStr]);
                    $hdClass   = $isLibur || $isHoliday ? 'bg-red-50 text-red-400' : '';
                ?>
                <th class="col-day <?= $hdClass ?>" style="padding:3px 2px;" title="<?= $isHoliday ? esc($holidays[$dateStr]) : '' ?>">
                    <div class="font-bold"><?= $dayLabels[$dw] ?></div>
                    <div style="font-size:0.64rem; font-weight:400;"><?= sprintf('%02d', $d) ?></div>
                </th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        $grandH = $grandTL = $grandS = $grandI = $grandA = $grandB = $grandBLuarJadwal = 0;
        $grandEfektif = 0;
        $grandDenda = 0.0;

        foreach ($students as $siswa):
            $cntH = $cntTL = $cntS = $cntI = $cntA = $cntB = 0;
            $cntBLuarJadwal = 0;
            $cntBdrHadir = 0;
            // Setiap siswa memakai jadwal BDR dan hari efektif kelasnya sendiri,
            // agar mode "Semua Kelas" tetap benar saat tiap kelas punya BDR
            // berbeda.
            $kelasSiswaId     = (int) ($siswa->id_kelas ?? 0);
            $bdrTanggalSiswa  = $bdrByKelas[$kelasSiswaId] ?? [];
            $hariEfektifSiswa = $hariEfektifByKelas[$kelasSiswaId] ?? 0;
        ?>
        <tr>
            <!-- No -->
            <td class="sticky-col-1 text-slate-400 font-mono text-xs"><?= $no++ ?></td>

            <!-- Nama & Identitas -->
            <td class="sticky-col-2" style="padding: 4px 8px; text-align:left;">
                <div class="font-semibold text-slate-800 truncate" style="max-width:180px;" title="<?= esc($siswa->nama) ?>">
                    <?= esc($siswa->nama) ?>
                </div>
                <div class="text-slate-400 flex items-center gap-1.5" style="font-size:0.65rem;">
                    <span><?= esc($siswa->nis ?? $siswa->nisn ?? '—') ?></span>
                    <?php if ($idKelas === 0 && !empty($siswa->nama_kelas)): ?>
                        <span class="text-sky-600 font-medium">&middot; <?= esc($siswa->nama_kelas) ?></span>
                    <?php endif; ?>
                </div>
            </td>

            <!-- Day cells -->
            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $ts        = strtotime($dateStr);
                $dayEn     = date('D', $ts);
                $dayNum    = (int) date('N', $ts); // 1=Senin..7=Minggu
                $isLiburMingguan = attendance_is_weekly_holiday($dayEn, $dayNum, $jadwalKerja ?? [], $hariLiburMingguan ?? []);
                $isHoliday = isset($holidays[$dateStr]);
                $isBdrJadwal = isset($bdrTanggalSiswa[$dateStr]);
                $rec       = $mapBySiswa[$siswa->id_siswa][$dateStr] ?? null;

                $cell  = attendance_rekap_day_cell(
                    $rec,
                    $dateStr,
                    $isLiburMingguan,
                    $isHoliday,
                    $isBdrJadwal,
                    $jamBatasMasukPerHari[$d] ?? $jamBatasMasuk,
                    null,
                    attendance_hari_tercatat($tercatatByKelas ?? [], $dateStr, $kelasSiswaId)
                );
                $state = $cell['state'];

                $bgClass   = '';
                $cellInner = '';

                switch ($state) {
                    case 'holiday':
                        $bgClass   = 'bg-cell-holiday';
                        $cellInner = '<span style="color:#cbd5e1;">—</span>';
                        break;

                    case 'off':
                        $bgClass   = 'bg-col-weekend';
                        $cellInner = '<span style="color:#cbd5e1;">—</span>';
                        break;

                    case 'present':
                    case 'late':
                        $cntH++;
                        if ($state === 'late') $cntTL++;

                        // Hadir fisik di hari BDR tetap dihitung; hari itu masuk
                        // kembali ke pembagi persen.
                        if ($isBdrJadwal) $cntBdrHadir++;

                        $bgClass = $state === 'late' ? 'bg-cell-telat' : 'bg-cell-hadir';

                        $inT  = $cell['time_in'] !== ''  ? substr($cell['time_in'], 0, 5) : 'H';
                        $outT = $cell['time_out'] !== '' ? substr($cell['time_out'], 0, 5) : '';

                        $inColor  = $state === 'late' ? 'color:#b45309;' : 'color:#059669;';
                        $outColor = 'color:#94a3b8;';

                        if ($cell['time_in'] !== '') {
                            $cellInner = '<div class="cell-time" style="padding:2px 3px;">
                                <div class="cell-in font-bold" style="' . $inColor . '">' . $inT . '</div>';
                            if ($outT !== '') {
                                $cellInner .= '<div style="' . $outColor . '">' . $outT . '</div>';
                            }
                            $cellInner .= '</div>';
                        } else {
                            $cellInner = '<span style="' . $inColor . ' font-weight:700;">H</span>';
                        }
                        break;

                    case 'sakit':
                        $cntS++;
                        $bgClass = 'bg-cell-sakit';
                        $cellInner = '<span style="color:#ca8a04; font-weight:700; font-size:0.65rem;" title="Sakit">S</span>';
                        break;

                    case 'izin':
                        $cntI++;
                        $bgClass = 'bg-cell-izin';
                        $cellInner = '<span style="color:var(--primary-600); font-weight:700; font-size:0.65rem;" title="Izin">I</span>';
                        break;

                    case 'bdr_scheduled':
                        $cntB++;
                        $bgClass = 'bg-cell-bdr';
                        $cellInner = '<span class="cell-bdr" title="Belajar dari Rumah">R</span>';
                        break;

                    case 'bdr_outside':
                        $cntB++;
                        $cntBLuarJadwal++;
                        $bgClass = 'bg-cell-bdr';
                        $cellInner = '<span class="cell-bdr" title="Belajar dari Rumah (di luar jadwal)">R</span>';
                        break;

                    case 'alpha':
                        $cntA++;
                        $bgClass = 'bg-cell-alpha';
                        $cellInner = '<span class="cell-alpha" title="Alpa / Tanpa Keterangan">✕</span>';
                        break;

                    case 'no_data':
                        // Hari kerja yang presensinya tidak tercatat sama
                        // sekali untuk kelas ini: absensi belum jalan, bukan
                        // satu kelas membolos bersama. Tidak masuk kolom Alpha
                        // dan tidak menagih denda maupun surat panggilan.
                        $bgClass   = 'bg-cell-libur';
                        $cellInner = '<span style="color:#cbd5e1;" title="Presensi hari ini belum tercatat">&mdash;</span>';
                        break;

                    default: // future
                        $bgClass   = 'bg-cell-libur';
                        $cellInner = '<span style="color:#e2e8f0;">—</span>';
                }
            ?>
            <td class="col-day <?= $bgClass ?>" style="height:38px;">
                <?= $cellInner ?>
            </td>
            <?php endfor; ?>

            <!-- Summary columns per student -->
            <?php
            // Hari BDR di luar jadwal dikeluarkan dari pembagi; hari BDR yang
            // dihadiri fisik dikembalikan ke pembagi.
            $efektifSiswa  = max(0, $hariEfektifSiswa + $cntBdrHadir - $cntBLuarJadwal);
            $persen = $efektifSiswa > 0 ? round(($cntH / $efektifSiswa) * 100, 1) : 0;
            $grandH  += $cntH;
            $grandTL += $cntTL;
            $grandB  += $cntB;
            $grandBLuarJadwal += $cntBLuarJadwal;
            $grandS  += $cntS;
            $grandI  += $cntI;
            $grandA  += $cntA;
            $grandEfektif += $efektifSiswa;
            // Netto sudah dipotong plafon bulanan per siswa (SiswaDendaService),
            // jadi jumlah baris bawah adalah total tagihan periode ini.
            $ringkasDenda  = $dendaSiswa[(int) $siswa->id_siswa] ?? [];
            $dendaNetto  = (float) ($ringkasDenda['netto'] ?? 0);
            $dendaAlpha  = (int) ($ringkasDenda['alpha'] ?? 0);
            $dendaBolos  = (int) ($ringkasDenda['bolos'] ?? 0);
            $grandDenda += $dendaNetto;
            ?>
            <td class="col-summary bg-emerald-50 text-emerald-700 font-bold"><?= $cntH ?></td>
            <td class="col-summary bg-amber-50 text-amber-600 font-bold"><?= $cntTL ?></td>
            <td class="col-summary bg-yellow-50 text-yellow-700 font-bold"><?= $cntS ?></td>
            <td class="col-summary bg-blue-50 text-blue-700 font-bold"><?= $cntI ?></td>
            <td class="col-summary bg-red-50 text-red-600 font-bold"><?= $cntA ?></td>
            <td class="col-summary bg-teal-50 text-teal-700 font-bold"><?= $cntB ?></td>
            <td class="col-persen bg-indigo-50 text-indigo-700 font-bold"><?= $persen ?>%</td>
            <td class="col-summary bg-rose-50 text-rose-700 font-bold text-right" style="font-size:0.7rem; padding:2px 6px; white-space:nowrap;"
                title="Alpha ber-denda: <?= $dendaAlpha ?> hari · Bolos ber-denda: <?= $dendaBolos ?> hari">
                <?= $dendaNetto > 0 ? 'Rp&nbsp;' . number_format($dendaNetto, 0, ',', '.') : '<span style="color:#cbd5e1;">—</span>' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="bg-slate-100 font-bold text-slate-700">
                <td colspan="2" class="py-2 px-3 text-right">Rata-rata / Total:</td>
                <td colspan="<?= $daysInMonth ?>" class="text-slate-400 font-normal text-xs">
                    <?= count($students) ?> Siswa &middot; <?= esc($hariEfektifLabel ?? ($hariEfektif . ' hari efektif')) ?>
                </td>
                <td class="col-summary bg-emerald-100 text-emerald-800"><?= $grandH ?></td>
                <td class="col-summary bg-amber-100 text-amber-800"><?= $grandTL ?></td>
                <td class="col-summary bg-yellow-100 text-yellow-800"><?= $grandS ?></td>
                <td class="col-summary bg-blue-100 text-blue-800"><?= $grandI ?></td>
                <td class="col-summary bg-red-100 text-red-800"><?= $grandA ?></td>
                <td class="col-summary bg-teal-100 text-teal-800"><?= $grandB ?></td>
                <?php
                $grandPersen = $grandEfektif > 0 ? round(($grandH / $grandEfektif) * 100, 1) : 0;
                ?>
                <td class="col-persen bg-indigo-100 text-indigo-800"><?= $grandPersen ?>%</td>
                <td class="col-summary bg-rose-100 text-rose-800 text-right" style="font-size:0.7rem; padding:2px 6px; white-space:nowrap;">
                    <?= $grandDenda > 0 ? 'Rp&nbsp;' . number_format($grandDenda, 0, ',', '.') : '—' ?>
                </td>
            </tr>
        </tfoot>
    </table>
    </div><!-- /rekap-wrap -->

    <!-- Footer info -->
    <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between text-xs text-slate-400 no-print gap-2">
        <span>
            <i class="bi bi-info-circle mr-1"></i>
            H=Hadir &nbsp;&middot;&nbsp; TL=Terlambat &nbsp;&middot;&nbsp; S=Sakit &nbsp;&middot;&nbsp; I=Izin &nbsp;&middot;&nbsp; A=Alpa / Tanpa Keterangan &nbsp;&middot;&nbsp; R=BDR (belajar dari rumah)
        </span>
        <?php
        // Tarif aktif ditampilkan supaya angka kolom Denda bisa dibaca tanpa
        // harus membuka halaman pengaturan.
        $tarifAlphaRekap = (float) ($absenSetting->denda_alpha_siswa_per_hari ?? 0);
        $tarifBolosRekap = (float) ($absenSetting->denda_bolos_siswa_per_hari ?? 0);
        $capRekap        = (float) ($absenSetting->max_denda_siswa_bulan ?? 0);
        ?>
        <?php if ($tarifAlphaRekap > 0 || $tarifBolosRekap > 0): ?>
        <span>
            Denda: alpha Rp&nbsp;<?= number_format($tarifAlphaRekap, 0, ',', '.') ?>/hari
            &nbsp;&middot;&nbsp; bolos Rp&nbsp;<?= number_format($tarifBolosRekap, 0, ',', '.') ?>/hari
            <?= $capRekap > 0 ? '&nbsp;&middot;&nbsp; maks Rp&nbsp;' . number_format($capRekap, 0, ',', '.') . '/siswa/bulan' : '' ?>
        </span>
        <?php endif; ?>
        <span class="font-medium text-slate-500">
            Total <?= count($students) ?> siswa &nbsp;&middot;&nbsp; <?= $namaBulan[$bulanInt] ?> <?= $tahun ?>
            <?php if (!empty($waliKelas)): ?>
                &nbsp;&middot;&nbsp; Wali Kelas: <strong><?= esc($waliKelas->nama_guru) ?></strong>
            <?php endif; ?>
        </span>
    </div>

    <?php endif; // empty students ?>

</div><!-- /card-tw -->
</div><!-- /content-area -->

<?= $this->endSection() ?>
