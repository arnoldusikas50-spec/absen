<?php
/**
 * View: Rekap Semester PDF Export (Dompdf — A4 landscape).
 * Dompdf mengabaikan Tailwind/layout admin, jadi semua styling inline
 * di sini (mengikuti pola laporan_pembayaran_pdf.php).
 */
$namaSekolah = $setting->nama_sekolah ?? $setting->nama_aplikasi ?? 'SEKOLAH';
$semLabel    = strtoupper($semLabel ?? ($semester == 1 ? 'GANJIL' : 'GENAP'));
$tpStart     = ($semester == 1) ? $tahun : $tahun - 1;
$tpEnd       = $tpStart + 1;
$periode     = ($semester == 1)
    ? "Juli {$tahun} – Desember {$tahun}"
    : "Januari {$tahun} – Juni {$tahun}";
$fmtTrim = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$minFmt  = $fmtTrim($minPersen);
$lowFmt  = $fmtTrim($minPersen - 10);

// Gambar external (kop, logo, TTD) di-encode base64 supaya Dompdf tidak butuh akses jaringan
$img64 = function ($rel) {
    if (empty($rel)) return '';
    $path = FCPATH . ltrim($rel, '/');
    if (!is_file($path)) return '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = ($ext === 'jpg') ? 'jpeg' : $ext;
    return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
};

$kopBase64  = $img64($setting->kopsekolah ?? '');
$logoBase64 = $img64($setting->logo_kiri ?? '');
$ttdBase64  = $img64($setting->tanda_tangan ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran Guru Semester <?= $semLabel ?> <?= $tahun ?></title>
    <style>
        body { font-family: sans-serif; font-size: 8.5px; margin: 0; padding: 12px; color: #111; }
        h1, h2, h3, p { margin: 0; padding: 0; }
        .kop { border-bottom: 3px double #000; padding-bottom: 6px; margin-bottom: 10px; }
        .kop-title { font-size: 15px; font-weight: bold; text-transform: uppercase; }
        .kop-sub { font-size: 9px; color: #444; }
        .doc-title { text-align: center; margin: 8px 0 2px; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .doc-sub { text-align: center; font-size: 9.5px; color: #444; margin-bottom: 8px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #555; padding: 3px 4px; }
        table.grid th { background: #eef2f7; font-size: 7.5px; text-transform: uppercase; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .bg-layak       { background: #e7f6ec; }
        .bg-perhatian   { background: #fdf5df; }
        .bg-tidak-layak { background: #fbe9e9; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .summary td { border: 1px solid #555; padding: 4px 6px; font-size: 8.5px; }
        .summary .lbl { color: #666; font-size: 7.5px; text-transform: uppercase; display: block; }
        .summary .val { font-size: 11px; font-weight: bold; }
        .foot { font-size: 8px; color: #555; margin-top: 6px; }
        .sign { margin-top: 20px; width: 100%; }
        .sign td { border: none; padding: 3px; font-size: 9px; }
    </style>
</head>
<body>

    <!-- Kop Surat (gambar kop dari Pengaturan; fallback kop teks) -->
    <?php if ($kopBase64): ?>
    <div style="text-align:center; margin-bottom:8px;">
        <img src="<?= $kopBase64 ?>" style="max-height:110px; width:auto; max-width:100%;">
    </div>
    <?php else: ?>
    <table width="100%" class="kop">
        <tr>
            <td width="10%" align="center" valign="middle" style="border:none;">
                <?php if ($logoBase64): ?><img src="<?= $logoBase64 ?>" style="width:52px;"><?php endif; ?>
            </td>
            <td align="center" valign="middle" style="border:none;">
                <div class="kop-title"><?= esc($namaSekolah) ?></div>
                <?php if (!empty($setting->alamat)): ?><div class="kop-sub"><?= esc($setting->alamat) ?></div><?php endif; ?>
                <?php
                $kontak = [];
                if (!empty($setting->telp))   $kontak[] = 'Telp: ' . $setting->telp;
                if (!empty($setting->email))  $kontak[] = 'Email: ' . $setting->email;
                if ($kontak): ?><div class="kop-sub"><?= esc(implode(' | ', $kontak)) ?></div><?php endif; ?>
            </td>
            <td width="10%" style="border:none;"></td>
        </tr>
    </table>
    <?php endif; ?>

    <div class="doc-title">Rekap Kehadiran Guru</div>
    <div class="doc-sub">Semester <?= $semLabel ?> Tahun Pelajaran <?= $tpStart ?>/<?= $tpEnd ?> &middot; Periode <?= esc($periode) ?></div>

    <!-- Ringkasan -->
    <?php
    $rata = (float) ($rata ?? 0);
    $cnt  = ['layak' => 0, 'perhatian' => 0, 'tidak_layak' => 0];
    $totHadir = $totTelat = $totIzin = $totSakit = $totDinas = $totAlpha = 0;
    $totPoin = 0; $totDenda = 0.0;
    foreach ($rekap as $r) {
        $cnt[$r->status_kelayakan] = ($cnt[$r->status_kelayakan] ?? 0) + 1;
        $totHadir += (int) $r->total_hadir;
        $totTelat += (int) $r->total_telat;
        $totIzin  += (int) $r->total_izin;
        $totSakit += (int) $r->total_sakit;
        $totDinas += (int) $r->total_dinas;
        $totAlpha += (int) $r->total_alpha;
        $totPoin  += (int) $r->total_poin;
        $totDenda += (float) $r->total_denda;
    }
    ?>
    <table class="summary">
        <tr>
            <td width="20%"><span class="lbl">Total Guru</span><span class="val"><?= count($rekap) ?></span></td>
            <td width="20%"><span class="lbl">Layak (&ge; <?= $minFmt ?>%)</span><span class="val"><?= (int) $cnt['layak'] ?></span></td>
            <td width="20%"><span class="lbl">Perhatian (<?= $lowFmt ?>–<?= $minFmt ?>%)</span><span class="val"><?= (int) $cnt['perhatian'] ?></span></td>
            <td width="20%"><span class="lbl">Tidak Layak (&lt; <?= $lowFmt ?>%)</span><span class="val"><?= (int) $cnt['tidak_layak'] ?></span></td>
            <td width="20%"><span class="lbl">Rata-rata Kehadiran</span><span class="val"><?= number_format($rata, 1) ?>%</span></td>
        </tr>
    </table>

    <!-- Tabel Rekap -->
    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" width="3%">No</th>
                <th rowspan="2">Nama Guru</th>
                <th rowspan="2" width="9%">NIP</th>
                <th rowspan="2" width="6%">Hari Efektif</th>
                <th colspan="6" width="24%">Kehadiran</th>
                <th rowspan="2" width="7%">Persentase</th>
                <th rowspan="2" width="7%">Status</th>
                <th rowspan="2" width="5%">Poin</th>
                <th rowspan="2" width="8%">Denda (Rp)</th>
            </tr>
            <tr>
                <th width="4%">Hadir</th>
                <th width="4%">Telat</th>
                <th width="4%">Izin</th>
                <th width="4%">Sakit</th>
                <th width="4%">Dinas</th>
                <th width="4%">Alpha</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($rekap as $r):
                $bg = match ($r->status_kelayakan) {
                    'tidak_layak' => 'bg-tidak-layak',
                    'perhatian'   => 'bg-perhatian',
                    default       => 'bg-layak',
                };
                $statusLabel = match ($r->status_kelayakan) {
                    'tidak_layak' => 'TIDAK LAYAK',
                    'perhatian'   => 'PERHATIAN',
                    default       => 'LAYAK',
                };
                $persen = (float) $r->persentase_kehadiran;
            ?>
            <tr class="<?= $bg ?>">
                <td class="center"><?= $no++ ?></td>
                <td class="bold"><?= esc($r->nama_guru) ?></td>
                <td class="center"><?= esc($r->nip ?? '-') ?></td>
                <td class="center"><?= (int) $r->total_hari_efektif ?></td>
                <td class="center bold"><?= (int) $r->total_hadir ?></td>
                <td class="center"><?= (int) $r->total_telat ?></td>
                <td class="center"><?= (int) $r->total_izin ?></td>
                <td class="center"><?= (int) $r->total_sakit ?></td>
                <td class="center"><?= (int) $r->total_dinas ?></td>
                <td class="center bold"><?= (int) $r->total_alpha ?></td>
                <td class="center bold"><?= number_format($persen, 1) ?>%</td>
                <td class="center bold"><?= $statusLabel ?></td>
                <td class="center"><?= ((int) $r->total_poin >= 0 ? '+' : '') . (int) $r->total_poin ?></td>
                <td class="right"><?= (float) $r->total_denda > 0 ? number_format((float) $r->total_denda, 0, ',', '.') : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- Total -->
            <tr style="background:#e9ecef; font-weight:bold;">
                <td colspan="3" class="center">TOTAL</td>
                <td class="center">-</td>
                <td class="center"><?= $totHadir ?></td>
                <td class="center"><?= $totTelat ?></td>
                <td class="center"><?= $totIzin ?></td>
                <td class="center"><?= $totSakit ?></td>
                <td class="center"><?= $totDinas ?></td>
                <td class="center"><?= $totAlpha ?></td>
                <td class="center"><?= number_format($rata, 1) ?>%</td>
                <td class="center">-</td>
                <td class="center"><?= ($totPoin >= 0 ? '+' : '') . $totPoin ?></td>
                <td class="right"><?= $totDenda > 0 ? number_format($totDenda, 0, ',', '.') : '-' ?></td>
            </tr>
        </tbody>
    </table>

    <div class="foot">
        Keterangan: <strong>LAYAK</strong> = kehadiran &ge; <?= $minFmt ?>% &nbsp;|&nbsp;
        <strong>PERHATIAN</strong> = <?= $lowFmt ?>–<?= $minFmt ?>% &nbsp;|&nbsp;
        <strong>TIDAK LAYAK</strong> = &lt; <?= $lowFmt ?>%.<br>
        Hadir = ONTIME + LATE + WFH + dinas disetujui &middot; Alpha = hari efektif − hadir − izin/sakit/cuti/dinas-menunggu &middot;
        Dinas resmi dihitung hadir.<br>
        Dicetak: <?= date('d/m/Y H:i') ?>
    </div>

    <table class="sign">
        <tr>
            <td width="55%"></td>
            <td align="center">
                <?= esc($setting->kota ?? 'Tempat') ?>, <?= date('d M Y') ?><br>
                Kepala Sekolah<br>
                <?php if ($ttdBase64): ?>
                    <img src="<?= $ttdBase64 ?>" style="height:64px; width:auto; margin:4px 0;"><br>
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <strong><u><?= esc(!empty($setting->kepsek) ? $setting->kepsek : (!empty($setting->nama_kepsek) ? $setting->nama_kepsek : '..........................')) ?></u></strong><br>
                NIP. <?= esc(!empty($setting->nip_kepsek) ? $setting->nip_kepsek : (!empty($setting->nip) ? $setting->nip : '..........................')) ?>
            </td>
        </tr>
    </table>
</body>
</html>
