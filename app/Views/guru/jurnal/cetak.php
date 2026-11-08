<?php
/**
 * View: Cetak Jurnal Mengajar Guru (single) & Rekap Bulanan.
 * Standalone tanpa layout admin — semua styling inline agar rapi saat dicetak.
 */
$namaSekolah = $setting->nama_sekolah ?? $setting->sekolah ?? 'SEKOLAH';
$namaKepsek  = $setting->kepsek ?? '';
$nipKepsek   = $setting->nip ?? '';
$smtLabel    = trim((string) ($smt_active->smt ?? '')) ?: '-';
$tpLabel     = trim((string) ($tp_active->tahun ?? '')) ?: '-';
$tanggalCetak = buat_tanggal(date('d F Y'));

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

$hariNama = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$guruCetak = $is_single ? $jurnal : ($guru ?? null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_single ? 'Jurnal Mengajar Guru' : 'Rekap Jurnal Mengajar Bulanan' ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11.5px;
            color: #1e293b;
            margin: 18px;
            line-height: 1.45;
        }
        h1, h2, h3, p { margin: 0; padding: 0; }
        .kop { border-bottom: 3px double #0f172a; padding-bottom: 8px; margin-bottom: 10px; }
        .kop-title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .kop-sub { font-size: 10px; color: #475569; }
        .doc-title { text-align: center; margin: 10px 0 2px; font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .doc-sub { text-align: center; font-size: 10px; color: #475569; margin-bottom: 12px; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.data th, table.data td {
            border: 1px solid #94a3b8;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background-color: #eef2f7;
            font-size: 9.5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        table.data tbody tr:nth-child(even) td { background: #f8fafc; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .muted { color: #64748b; }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: bold;
            background: #e2e8f0;
        }
        .badge-hadir { background: #d1fae5; color: #047857; }
        .badge-absent { background: #fee2e2; color: #b91c1c; }
        .section-label {
            display: block;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-table td { border: 1px solid #94a3b8; padding: 6px 8px; vertical-align: top; }
        .sign { width: 100%; margin-top: 26px; page-break-inside: avoid; }
        .sign td { vertical-align: top; font-size: 11px; }
        .sign .space { height: 62px; }
        .foot { font-size: 9px; color: #64748b; margin-top: 8px; }
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

<div class="doc-title"><?= $is_single ? 'Jurnal Mengajar Guru' : 'Rekapitulasi Jurnal Mengajar Bulanan' ?></div>
<div class="doc-sub">
    <?php if ($is_single): ?>
        <?= esc(buat_tanggal(date('l, d F Y', strtotime($jurnal->tanggal)))) ?>
    <?php else: ?>
        Bulan <?= esc(buat_tanggal(date('F', mktime(0, 0, 0, (int) $bulan, 10)))) ?> <?= esc($tahun) ?>
    <?php endif; ?>
    &middot; Tahun Pelajaran <?= esc($tpLabel) ?> &middot; Semester <?= esc($smtLabel) ?>
</div>

<?php if ($is_single): ?>
<!-- ================= JURNAL TUNGGAL ================= -->
<?php
$profilArr = !empty($jurnal->profil_pancasila)
    ? (json_decode((string) $jurnal->profil_pancasila, true) ?: [])
    : [];
$siswaAbsent = trim((string) ($jurnal->siswa_absent ?? ''));
$semuaHadir  = $siswaAbsent === '' || strtolower($siswaAbsent) === 'nihil';
?>
<table class="data">
    <tr>
        <th width="180">Guru Pengajar</th>
        <td>
            <strong><?= esc($jurnal->nama_guru ?? '-') ?></strong>
            <span class="muted">&middot; NIP <?= esc($jurnal->nip ?: '-') ?></span>
        </td>
    </tr>
    <tr>
        <th>Hari, Tanggal</th>
        <td><?= esc($hariNama[(int) date('N', strtotime($jurnal->tanggal))] ?? '') ?>, <?= esc(buat_tanggal(date('d F Y', strtotime($jurnal->tanggal)))) ?></td>
    </tr>
    <tr>
        <th>Kelas / Jam Ke</th>
        <td><?= esc($jurnal->nama_kelas ?? '-') ?> / Jam Ke-<?= esc($jurnal->jam_ke) ?></td>
    </tr>
    <tr>
        <th>Mata Pelajaran</th>
        <td><?= esc($jurnal->nama_mapel ?? '-') ?></td>
    </tr>
    <tr>
        <th>Materi Pokok</th>
        <td><strong><?= nl2br(esc((string) ($jurnal->materi_pokok ?? '-'))) ?></strong></td>
    </tr>
    <?php if (!empty($jurnal->tujuan_pembelajaran)): ?>
    <tr>
        <th>Tujuan Pembelajaran</th>
        <td><?= nl2br(esc((string) $jurnal->tujuan_pembelajaran)) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <th>Model &amp; Media Belajar</th>
        <td>
            Model: <?= esc($jurnal->model_belajar ?: '-') ?><br>
            Media: <?= esc($jurnal->media_alat ?: '-') ?>
        </td>
    </tr>
    <?php if (!empty($jurnal->kegiatan_belajar)): ?>
    <tr>
        <th>Alur Kegiatan Belajar</th>
        <td><?= nl2br(esc((string) $jurnal->kegiatan_belajar)) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($profilArr)): ?>
    <tr>
        <th>Profil Pelajar Pancasila</th>
        <td><?= esc(implode(', ', $profilArr)) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <th>Bentuk Asesmen</th>
        <td><?= esc($jurnal->jenis_asesmen ?: '-') ?></td>
    </tr>
    <tr>
        <th>Kehadiran Siswa</th>
        <td>
            <?php if ($semuaHadir): ?>
            <span class="badge badge-hadir">Semua siswa hadir</span>
            <?php else: ?>
            <span class="badge badge-absent">Tidak hadir: <?= esc($siswaAbsent) ?></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php if (!empty($jurnal->refleksi)): ?>
    <tr>
        <th>Refleksi Guru</th>
        <td><?= nl2br(esc((string) $jurnal->refleksi)) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($jurnal->tindak_lanjut)): ?>
    <tr>
        <th>Rencana Tindak Lanjut</th>
        <td><?= nl2br(esc((string) $jurnal->tindak_lanjut)) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($jurnal->catatan_khusus)): ?>
    <tr>
        <th>Catatan Khusus</th>
        <td><?= nl2br(esc((string) $jurnal->catatan_khusus)) ?></td>
    </tr>
    <?php endif; ?>
</table>

<?php else: ?>
<!-- ================= REKAP BULANAN ================= -->
<table class="meta-table">
    <tr>
        <td width="55%">
            <span class="section-label">Nama Guru</span>
            <strong><?= esc($guru->nama_guru ?? '-') ?></strong><br>
            <span class="muted">NIP <?= esc($guru->nip ?: '-') ?></span>
        </td>
        <td width="45%">
            <span class="section-label">Periode</span>
            <?= esc(buat_tanggal(date('F', mktime(0, 0, 0, (int) $bulan, 10)))) ?> <?= esc($tahun) ?><br>
            <span class="muted">Total <?= count($jurnals) ?> pertemuan &middot; <?= esc($smtLabel) ?> <?= esc($tpLabel) ?></span>
        </td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th width="30" class="text-center">No</th>
            <th width="110" class="text-center">Hari, Tanggal</th>
            <th width="46" class="text-center">Jam</th>
            <th width="80">Kelas</th>
            <th width="115">Mata Pelajaran</th>
            <th>Materi Pokok</th>
            <th width="92">Asesmen</th>
            <th width="88">Ket. Hadir</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($jurnals)): ?>
        <tr>
            <td colspan="8" class="text-center muted">Tidak ada catatan jurnal mengajar pada periode ini.</td>
        </tr>
        <?php else: $no = 1; foreach ($jurnals as $j): ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td class="text-center"><?= esc($hariNama[(int) date('N', strtotime($j->tanggal))] ?? '') ?>, <?= esc(date('d/m/Y', strtotime($j->tanggal))) ?></td>
            <td class="text-center"><?= esc($j->jam_ke) ?></td>
            <td><?= esc($j->nama_kelas ?? '-') ?></td>
            <td><?= esc($j->nama_mapel ?? '-') ?></td>
            <td><?= esc((string) ($j->materi_pokok ?? '-')) ?></td>
            <td><?= esc($j->jenis_asesmen ?: '-') ?></td>
            <td><?= esc($j->siswa_absent ?: 'Lengkap') ?></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
<?php endif; ?>

<p class="foot">
    Dicetak dari sistem <?= esc($setting->nama_aplikasi ?? $namaSekolah) ?> pada <?= esc($tanggalCetak) ?>.
</p>

<!-- Tanda Tangan -->
<table class="sign">
    <tr>
        <td width="50%" align="center">
            Mengetahui,<br>Kepala Sekolah
            <div class="space">
                <?php if ($ttdBase64): ?>
                <img src="<?= $ttdBase64 ?>" style="max-height:58px; max-width:180px;">
                <?php endif; ?>
            </div>
            <span class="bold"><?= esc($namaKepsek ?: '( _______________________ )') ?></span><br>
            <?php if ($namaKepsek): ?>NIP. <?= esc($nipKepsek ?: '-') ?><?php endif; ?>
        </td>
        <td width="50%" align="center">
            <?php if (!empty($setting->tempat)): ?><?= esc($setting->tempat) ?>, <?php endif; ?><?= esc($tanggalCetak) ?><br>
            Guru Mata Pelajaran
            <div class="space"></div>
            <span class="bold"><?= esc($guruCetak->nama_guru ?? 'Guru') ?></span><br>
            NIP. <?= esc($guruCetak->nip ?: '-') ?>
        </td>
    </tr>
</table>

</body>
</html>
