<?php
/**
 * View: Cetak Rekapitulasi Jurnal Mengajar (standalone, tanpa layout admin).
 * Semua styling inline karena halaman ini dicetak / disimpan sebagai PDF.
 */
$namaSekolah = $setting->nama_sekolah ?? $setting->sekolah ?? 'SEKOLAH';
$namaKepsek  = $setting->kepsek ?? '';
$nipKepsek   = $setting->nip ?? '';
$namaBulan   = buat_tanggal(date('F', mktime(0, 0, 0, (int) $bulan, 10)));
$smtLabel    = trim((string) ($smt_active->smt ?? '')) ?: '-';
$tpLabel     = trim((string) ($tp_active->tahun ?? '')) ?: '-';

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
$belum      = max(0, $total_guru - $guru_mengisi);
$tanggalCetak = buat_tanggal(date('d F Y'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Jurnal Mengajar <?= esc($namaBulan) ?> <?= esc($tahun) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; margin: 16px; }
        h1, h2, h3, p { margin: 0; padding: 0; }
        .kop { border-bottom: 3px double #0f172a; padding-bottom: 8px; margin-bottom: 10px; }
        .kop-title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .kop-sub { font-size: 10px; color: #475569; }
        .doc-title { text-align: center; margin: 10px 0 2px; font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .doc-sub { text-align: center; font-size: 10px; color: #475569; margin-bottom: 10px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary td { border: 1px solid #94a3b8; padding: 5px 8px; }
        .summary .lbl { display: block; font-size: 8.5px; text-transform: uppercase; color: #64748b; }
        .summary .val { font-size: 13px; font-weight: bold; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #94a3b8; padding: 4px 6px; }
        table.grid th { background: #eef2f7; font-size: 9.5px; text-transform: uppercase; }
        table.grid tbody tr:nth-child(even) td { background: #f8fafc; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .status-sudah { color: #047857; font-weight: bold; }
        .status-belum { color: #b91c1c; font-weight: bold; }
        .foot { font-size: 9px; color: #64748b; margin-top: 8px; }
        .sign { width: 100%; margin-top: 26px; page-break-inside: avoid; }
        .sign td { vertical-align: top; font-size: 11px; }
        .sign .space { height: 62px; }
        .no-print { text-align: right; margin-bottom: 12px; }
        .no-print button {
            padding: 8px 18px; background: #4f46e5; color: #fff; border: none;
            border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Cetak Dokumen</button>
</div>

<!-- Kop -->
<?php if ($kopBase64): ?>
<div style="text-align:center; margin-bottom:10px;">
    <img src="<?= $kopBase64 ?>" style="max-height:100px; width:auto; max-width:100%;">
</div>
<?php else: ?>
<table width="100%" class="kop">
    <tr>
        <td width="10%" align="center" valign="middle" style="border:none;">
            <?php if ($logoBase64): ?><img src="<?= $logoBase64 ?>" style="width:56px;"><?php endif; ?>
        </td>
        <td align="center" valign="middle" style="border:none;">
            <div class="kop-title"><?= esc($namaSekolah) ?></div>
            <?php if (!empty($setting->alamat)): ?>
            <div class="kop-sub"><?= esc($setting->alamat) ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>
<?php endif; ?>

<div class="doc-title">Rekapitulasi Jurnal Mengajar Guru</div>
<div class="doc-sub">
    Bulan <?= esc($namaBulan) ?> <?= esc($tahun) ?>
    &middot; Tahun Pelajaran <?= esc($tpLabel) ?>
    &middot; Semester <?= esc($smtLabel) ?>
</div>

<!-- Ringkasan -->
<table class="summary">
    <tr>
        <td width="20%"><span class="lbl">Total Guru</span><span class="val"><?= (int) $total_guru ?></span></td>
        <td width="20%"><span class="lbl">Sudah Mengisi</span><span class="val" style="color:#047857;"><?= (int) $guru_mengisi ?></span></td>
        <td width="20%"><span class="lbl">Belum Mengisi</span><span class="val" style="color:#b91c1c;"><?= (int) $belum ?></span></td>
        <td width="20%"><span class="lbl">Sesi Terjadwal</span><span class="val"><?= (int) $total_scheduled ?></span></td>
        <td width="20%"><span class="lbl">Total Jurnal</span><span class="val"><?= (int) $total_jurnal ?></span></td>
    </tr>
</table>

<?php if (!empty($q) || !empty($hanya_belum)): ?>
<p style="margin-bottom:8px; font-size:10px; color:#475569;">
    Filter:
    <?php if (!empty($q)): ?>pencarian &ldquo;<?= esc($q) ?>&rdquo;<?php endif; ?>
    <?php if (!empty($q) && !empty($hanya_belum)): ?> &middot; <?php endif; ?>
    <?php if (!empty($hanya_belum)): ?>hanya guru yang belum mengisi<?php endif; ?>
</p>
<?php endif; ?>

<!-- Tabel -->
<table class="grid">
    <thead>
        <tr>
            <th width="28" class="center">No</th>
            <th>Nama Guru</th>
            <th width="95">NIP</th>
            <th width="60" class="center">Terjadwal</th>
            <th width="58" class="center">Jurnal</th>
            <th width="58" class="center">Kepatuhan</th>
            <th width="50" class="center">Hari Aktif</th>
            <th width="78" class="center">Terakhir Mengisi</th>
            <th width="62" class="center">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($gurus)): ?>
        <tr>
            <td colspan="9" class="center">Tidak ada data guru untuk filter ini.</td>
        </tr>
        <?php else: $no = 1; foreach ($gurus as $g):
            $stat = $stat_map[(int) $g->id_guru] ?? null;
            $totalJurnal = $stat ? (int) $stat->total_jurnal : 0;
            $hariAktif = $stat ? (int) $stat->hari_aktif : 0;
            $terjadwal = (int) ($scheduled_map[(int) $g->id_guru] ?? 0);
            $kepatuhan = $terjadwal > 0 ? min(100, (int) round($totalJurnal / $terjadwal * 100)) : null;
            $terakhir = $stat && !empty($stat->last_tanggal)
                ? date('d/m/Y', strtotime($stat->last_tanggal))
                : '-';
            $disetujui = $stat ? (int) ($stat->jumlah_disetujui ?? 0) : 0;
            $ditolak   = $stat ? (int) ($stat->jumlah_ditolak ?? 0) : 0;
            $statusLabel = $totalJurnal > 0 ? 'Sudah' : 'Belum';
            if (!empty($ada_validasi) && $totalJurnal > 0) {
                $statusLabel = 'Sudah &middot; ' . $disetujui . ' disetujui'
                    . ($ditolak > 0 ? ', ' . $ditolak . ' ditolak' : '');
            }
        ?>
        <tr>
            <td class="center"><?= $no++ ?></td>
            <td class="bold"><?= esc($g->nama_guru) ?></td>
            <td><?= esc($g->nip ?: '-') ?></td>
            <td class="center"><?= $terjadwal > 0 ? $terjadwal : '&mdash;' ?></td>
            <td class="center bold"><?= $totalJurnal ?></td>
            <td class="center"><?= $kepatuhan === null ? '&mdash;' : $kepatuhan . '%' ?></td>
            <td class="center"><?= $hariAktif ?></td>
            <td class="center"><?= esc($terakhir) ?></td>
            <td class="center <?= $totalJurnal > 0 ? 'status-sudah' : 'status-belum' ?>">
                <?= $statusLabel ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<p class="foot">
    Sesi terjadwal dihitung dari jadwal pelajaran pada hari Senin&ndash;Sabtu hingga hari ini; kepatuhan = jurnal &divide; sesi terjadwal.
    Dicetak dari sistem <?= esc($setting->nama_aplikasi ?? $namaSekolah) ?> pada <?= esc($tanggalCetak) ?>.
</p>

<!-- Tanda Tangan -->
<table class="sign">
    <tr>
        <td width="55%"></td>
        <td width="45%" align="center">
            <?php if (!empty($setting->tempat)): ?><?= esc($setting->tempat) ?>, <?php endif; ?><?= esc($tanggalCetak) ?><br>
            Mengetahui,<br>Kepala Sekolah
            <div class="space">
                <?php if ($ttdBase64): ?>
                <img src="<?= $ttdBase64 ?>" style="max-height:58px; max-width:180px;">
                <?php endif; ?>
            </div>
            <span class="bold"><?= esc($namaKepsek ?: '( _______________________ )') ?></span><br>
            <?php if ($namaKepsek): ?>NIP. <?= esc($nipKepsek ?: '-') ?><?php endif; ?>
        </td>
    </tr>
</table>

</body>
</html>
