<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran Denda <?= esc($filterTglDari) ?> - <?= esc($filterTglSampai) ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 12px; color: #111; }
        h2, h3, p { margin: 0; padding: 0; }
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
        .bold { font-weight: bold; }
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
    $namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $periodeTxt = date('d M Y', strtotime($filterTglDari)) . ' - ' . date('d M Y', strtotime($filterTglSampai));
    $filterTxt = [];
    if (!empty($filterGuruNama)) $filterTxt[] = 'Guru: ' . $filterGuruNama;
    if (!empty($filterMetode)) $filterTxt[] = 'Metode: ' . strtoupper($filterMetode);
    ?>

    <table width="100%" class="kop">
        <tr>
            <td width="12%" align="center" valign="middle" style="border:none;">
                <?php if ($logoBase64): ?><img src="<?= $logoBase64 ?>" style="width:60px;"><?php endif; ?>
            </td>
            <td align="center" valign="middle" style="border:none;">
                <div class="kop-title"><?= esc($setting->nama_sekolah ?? 'SEKOLAH') ?></div>
                <?php if (!empty($setting->alamat)): ?><div class="kop-sub"><?= esc($setting->alamat) ?></div><?php endif; ?>
                <?php
                $kontak = [];
                if (!empty($setting->telp)) $kontak[] = 'Telp: ' . $setting->telp;
                if (!empty($setting->email)) $kontak[] = 'Email: ' . $setting->email;
                if ($kontak): ?><div class="kop-sub"><?= esc(implode(' | ', $kontak)) ?></div><?php endif; ?>
            </td>
            <td width="12%" style="border:none;"></td>
        </tr>
    </table>

    <div class="doc-title">Laporan Pembayaran Denda</div>
    <div class="doc-sub">Periode <?= esc($periodeTxt) ?><?= $filterTxt ? ' • ' . esc(implode(' • ', $filterTxt)) : '' ?></div>

    <table class="summary" width="100%">
        <tr>
            <td width="25%">Total: Rp <?= number_format($totalNominal, 0, ',', '.') ?> (<?= count($payments) ?> transaksi)</td>
            <td width="25%">Tunai: Rp <?= number_format($totalTunai, 0, ',', '.') ?></td>
            <td width="25%">Poin: Rp <?= number_format($totalPoin, 0, ',', '.') ?></td>
            <td width="25%">Saldo: Rp <?= number_format($totalSaldo, 0, ',', '.') ?></td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="10%">Tanggal</th>
                <th>Guru</th>
                <th width="9%">Periode</th>
                <th width="12%">Nominal</th>
                <th width="8%">Metode</th>
                <th width="8%">Status</th>
                <th width="22%">Keterangan</th>
                <th width="12%">Pencatat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="9" class="center">Tidak ada data pembayaran untuk filter yang dipilih.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($payments as $p):
                    $nominal = (float)($p->nominal_bayar ?: $p->total_denda);
                    $tglBayar = $p->tanggal_bayar ?: ($p->paid_at ?? null);
                    $adminNm = trim(($p->admin_first ?? '') . ' ' . ($p->admin_last ?? ''));
                ?>
                <tr>
                    <td class="center"><?= $no++ ?></td>
                    <td><?= $tglBayar ? date('d/m/Y', strtotime($tglBayar)) : '-' ?></td>
                    <td><?= esc($p->nama_guru ?? '-') ?><?= !empty($p->nip) ? '<br><small>' . esc($p->nip) . '</small>' : '' ?></td>
                    <td class="center"><?= ($namaBulan[(int)$p->bulan] ?? $p->bulan) ?> <?= $p->tahun ?></td>
                    <td class="right"><?= number_format($nominal, 0, ',', '.') ?></td>
                    <td class="center"><?= esc($p->metode_bayar ?? 'TUNAI') ?></td>
                    <td class="center"><?= esc(ucfirst($p->status ?? '-')) ?></td>
                    <td><?= esc($p->keterangan ?? '-') ?></td>
                    <td><?= esc($adminNm !== '' ? $adminNm : '-') ?></td>
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
                Mengetahui,<br><br><br><br>
                <b>( ........................................ )</b>
            </td>
        </tr>
    </table>
</body>
</html>
