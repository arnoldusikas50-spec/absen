<?php
/**
 * View: Rekap Semester Excel Export
 * Format: XLS (HTML table — compatible with MS Excel & LibreOffice)
 */
$namaSekolah = $setting->nama_sekolah ?? $setting->nama_aplikasi ?? 'SEKOLAH';
$semLabel    = $semester == 1 ? 'GANJIL' : 'GENAP';
$tpStart     = ($semester == 1) ? $tahun : $tahun - 1;
$tpEnd       = $tpStart + 1;
$periodeLabel = ($semester == 1)
    ? "Juli {$tahun} – Desember {$tahun}"
    : "Januari {$tahun} – Juni {$tahun}";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
<x:Name>Rekap Semester</x:Name>
<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml>
<![endif]-->
<style>
    body  { font-family: Arial, sans-serif; font-size: 10pt; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #333; padding: 4px 6px; }
    .header-row th { background: #2d5f8a; color: #fff; text-align: center; font-size: 11pt; }
    .title-row td  { font-size: 12pt; font-weight: bold; text-align: center; border: none; }
    .sub-title td  { font-size: 10pt; text-align: center; border: none; }
    .layak         { background: #d4edda; }
    .perhatian     { background: #fff3cd; }
    .tidak-layak   { background: #f8d7da; }
    .text-center   { text-align: center; }
    .text-right    { text-align: right; }
    .fw-bold       { font-weight: bold; }
</style>
</head>
<body>
<table>
    <!-- Kop / Header Dokumen -->
    <tr class="title-row"><td colspan="14">REKAP KEHADIRAN GURU</td></tr>
    <tr class="sub-title"><td colspan="14"><?= strtoupper($namaSekolah) ?></td></tr>
    <tr class="sub-title"><td colspan="14">SEMESTER <?= $semLabel ?> TAHUN PELAJARAN <?= $tpStart ?>/<?= $tpEnd ?></td></tr>
    <tr class="sub-title"><td colspan="14">Periode: <?= $periodeLabel ?></td></tr>
    <tr><td colspan="14">&nbsp;</td></tr>

    <!-- Header Tabel -->
    <tr class="header-row">
        <th rowspan="2">No</th>
        <th rowspan="2">Nama Guru</th>
        <th rowspan="2">NIP</th>
        <th rowspan="2">Hari Efektif</th>
        <th colspan="6">Kehadiran</th>
        <th rowspan="2">Persentase (%)</th>
        <th rowspan="2">Status</th>
        <th rowspan="2">Total Poin</th>
        <th rowspan="2">Total Denda (Rp)</th>
    </tr>
    <tr class="header-row">
        <th>Hadir</th>
        <th>Telat</th>
        <th>Izin</th>
        <th>Sakit</th>
        <th>Dinas</th>
        <th>Alpha</th>
    </tr>

    <!-- Data (sudah diurutkan controller: tidak_layak -> perhatian -> layak) -->
    <?php
    $no = 1;
    $totalHadir  = 0; $totalTelat = 0; $totalIzin  = 0;
    $totalSakit  = 0; $totalDinas = 0; $totalAlpha = 0;
    $totalDenda  = 0; $totalPoin  = 0;

    foreach ($rekap as $r):
        $rowClass = match($r->status_kelayakan) {
            'tidak_layak' => 'tidak-layak',
            'perhatian'   => 'perhatian',
            default       => 'layak',
        };
        $statusLabel = match($r->status_kelayakan) {
            'tidak_layak' => 'TIDAK LAYAK',
            'perhatian'   => 'PERHATIAN',
            default       => 'LAYAK',
        };
        $persen = number_format((float)$r->persentase_kehadiran, 1);

        $totalHadir += (int)$r->total_hadir;
        $totalTelat += (int)$r->total_telat;
        $totalIzin  += (int)$r->total_izin;
        $totalSakit += (int)$r->total_sakit;
        $totalDinas += (int)$r->total_dinas;
        $totalAlpha += (int)$r->total_alpha;
        $totalDenda += (float)$r->total_denda;
        $totalPoin  += (int)$r->total_poin;
    ?>
    <tr class="<?= $rowClass ?>">
        <td class="text-center"><?= $no++ ?></td>
        <td class="fw-bold"><?= esc($r->nama_guru) ?></td>
        <td class="text-center"><?= esc($r->nip ?? '-') ?></td>
        <td class="text-center"><?= $r->total_hari_efektif ?></td>
        <td class="text-center fw-bold"><?= $r->total_hadir ?></td>
        <td class="text-center"><?= $r->total_telat ?></td>
        <td class="text-center"><?= $r->total_izin ?></td>
        <td class="text-center"><?= $r->total_sakit ?></td>
        <td class="text-center"><?= $r->total_dinas ?></td>
        <td class="text-center fw-bold"><?= $r->total_alpha ?></td>
        <td class="text-center fw-bold"><?= $persen ?>%</td>
        <td class="text-center fw-bold"><?= $statusLabel ?></td>
        <td class="text-center <?= $r->total_poin >= 0 ? '' : 'fw-bold' ?>"><?= ($r->total_poin >= 0 ? '+' : '') . $r->total_poin ?></td>
        <td class="text-right"><?= $r->total_denda > 0 ? number_format($r->total_denda, 0, ',', '.') : '-' ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- Row Total -->
    <?php if (!empty($rekap)): ?>
    <tr style="background:#e9ecef; font-weight:bold;">
        <td colspan="4" class="text-center fw-bold">TOTAL</td>
        <td class="text-center"><?= $totalHadir ?></td>
        <td class="text-center"><?= $totalTelat ?></td>
        <td class="text-center"><?= $totalIzin ?></td>
        <td class="text-center"><?= $totalSakit ?></td>
        <td class="text-center"><?= $totalDinas ?></td>
        <td class="text-center"><?= $totalAlpha ?></td>
        <td class="text-center">
            <?= isset($rata) ? number_format((float) $rata, 1) : (count($rekap) > 0 ? number_format(array_sum(array_map(fn($r) => (float) $r->persentase_kehadiran, (array) $rekap)) / count($rekap), 1) : '0') ?>%
        </td>
        <td></td>
        <td class="text-center"><?= ($totalPoin >= 0 ? '+' : '') . $totalPoin ?></td>
        <td class="text-right"><?= $totalDenda > 0 ? number_format($totalDenda, 0, ',', '.') : '-' ?></td>
    </tr>
    <?php endif; ?>

    <!-- Keterangan -->
    <tr><td colspan="14">&nbsp;</td></tr>
    <tr>
        <td colspan="14" style="border:none; font-size:9pt; color:#555;">
            Keterangan: LAYAK = kehadiran ≥ <?= $minPersen ?>% &nbsp;|&nbsp;
            PERHATIAN = kehadiran <?= $minPersen - 10 ?>–<?= $minPersen ?>% &nbsp;|&nbsp;
            TIDAK LAYAK = kehadiran &lt; <?= $minPersen - 10 ?>%
        </td>
    </tr>
    <tr>
        <td colspan="14" style="border:none; font-size:9pt; color:#555;">
            Standar : Minimal <?= $minPersen ?>% kehadiran per semester &nbsp;|&nbsp;
            Dicetak: <?= date('d/m/Y H:i') ?>
        </td>
    </tr>
</table>
</body>
</html>
