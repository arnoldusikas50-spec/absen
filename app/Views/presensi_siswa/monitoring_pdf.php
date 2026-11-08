<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Presensi Siswa - <?= esc($kelasNama) ?> - <?= esc($tgl) ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 12px; color: #111; }
        h2, p { margin: 0; padding: 0; }
        .kop { border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 12px; }
        .kop-title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .kop-sub { font-size: 10px; color: #444; }
        .doc-title { text-align: center; margin: 10px 0 2px; font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .doc-sub { text-align: center; font-size: 10px; color: #444; margin-bottom: 10px; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid th, table.grid td { border: 1px solid #333; padding: 4px 5px; }
        table.grid th { background: #f0f0f0; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary td { border: 1px solid #333; padding: 4px 8px; font-weight: bold; }
        .sign { margin-top: 24px; width: 100%; }
        .sign td { border: none; padding: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <?php
    $logoBase64 = '';
    if (!empty($setting->logo_kiri)) {
        $logoPath = FCPATH . ltrim($setting->logo_kiri, '/');
        if (is_file($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'png' : ($ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : $ext);
            $logoBase64 = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
    $tglFmt = date('d M Y', strtotime($tgl));
    $statusLabel = ['HADIR' => 'Hadir', 'SAKIT' => 'Sakit', 'IZIN' => 'Izin', 'ALPA' => 'Alpa', 'BDR' => 'BDR', 'TANPA_KETERANGAN' => 'Belum'];
    ?>

    <table width="100%" class="kop">
        <tr>
            <td width="12%" align="center" valign="middle" style="border:none;">
                <?php if ($logoBase64): ?><img src="<?= $logoBase64 ?>" style="width:60px;"><?php endif; ?>
            </td>
            <td align="center" valign="middle" style="border:none;">
                <div class="kop-title"><?= esc($setting->nama_sekolah ?? 'SEKOLAH') ?></div>
                <?php if (!empty($setting->alamat)): ?><div class="kop-sub"><?= esc($setting->alamat) ?></div><?php endif; ?>
            </td>
            <td width="12%" style="border:none;"></td>
        </tr>
    </table>

    <div class="doc-title">Monitoring Presensi Siswa Harian</div>
    <div class="doc-sub"><?= esc($kelasNama) ?> • <?= esc($tglFmt) ?></div>

    <table class="summary" width="100%">
        <tr>
            <td width="17%">Total: <?= (int) ($stat['total'] ?? 0) ?> siswa</td>
            <td width="14%">Hadir: <?= (int) ($stat['hadir'] ?? 0) ?></td>
            <td width="14%">Sakit: <?= (int) ($stat['sakit'] ?? 0) ?></td>
            <td width="14%">Izin: <?= (int) ($stat['izin'] ?? 0) ?></td>
            <td width="14%">Alpa: <?= (int) ($stat['alpa'] ?? 0) ?></td>
            <td width="14%">BDR: <?= (int) ($stat['bdr'] ?? 0) ?></td>
            <td width="14%">Belum: <?= (int) ($stat['belum'] ?? 0) ?></td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th>Nama Siswa</th>
                <th width="12%">NIS</th>
                <th width="10%">Status</th>
                <th width="9%">Masuk</th>
                <th width="9%">Keluar</th>
                <th width="22%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="7" class="center">Belum ada data siswa di kelas ini.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($data as $r):
                    $st = $r->status ?? 'TANPA_KETERANGAN';
                    $label = $statusLabel[$st] ?? 'Belum';
                ?>
                <tr>
                    <td class="center"><?= $no++ ?></td>
                    <td><?= esc($r->nama ?? '-') ?></td>
                    <td class="center"><?= esc($r->nis ?? '-') ?></td>
                    <td class="center"><?= esc($label) ?></td>
                    <td class="center"><?= !empty($r->jam_masuk) ? esc(substr($r->jam_masuk, 0, 5)) : '-' ?></td>
                    <td class="center"><?= !empty($r->jam_keluar)
                        ? esc(substr($r->jam_keluar, 0, 5))
                        : ((int) ($r->pulang_tidak_tercatat ?? 0) === 1 ? 'tidak tercatat' : '-') ?></td>
                    <td><?= esc($r->keterangan ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td width="60%"></td>
            <td align="center">
                <?= esc($setting->kota ?? '') ?>, <?= date('d M Y') ?><br>
                Wali Kelas,<br><br><br><br>
                <b>( ........................................ )</b>
            </td>
        </tr>
    </table>
</body>
</html>
