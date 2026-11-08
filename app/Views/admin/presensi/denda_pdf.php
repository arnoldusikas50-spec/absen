<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Denda Presensi - <?= buat_tanggal(date('F Y', strtotime($tahun . '-' . $bulan . '-01'))) ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 10px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #dc3545; }
        .text-success { color: #198754; }
        .text-purple { color: #7e22ce; }
        h2, h3 { margin: 0; padding: 0; }
        h4 { margin-top: 5px; margin-bottom: 15px; font-weight: normal; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 5px; }
        th { background-color: #f0f0f0; }
        .footer { margin-top: 30px; width: 100%; }
        .footer td { border: none; padding: 5px; }
    </style>
</head>
<body>
    <?php
    // Prepare address parts
    $alamat = $setting->alamat ?? '';
    $alamatParts = [];
    if (!empty($setting->kelurahan))
        $alamatParts[] = 'Kel. ' . $setting->kelurahan;
    if (!empty($setting->kecamatan))
        $alamatParts[] = 'Kec. ' . $setting->kecamatan;
    if (!empty($setting->kabupaten))
        $alamatParts[] = $setting->kabupaten;
    if (!empty($setting->provinsi))
        $alamatParts[] = $setting->provinsi;
    $alamatLengkap = $alamat . ($alamat && $alamatParts ? ', ' : '') . implode(', ', $alamatParts);

    // Contact info
    $contact = [];
    if (!empty($setting->telp))
        $contact[] = 'Telp: ' . $setting->telp;
    if (!empty($setting->email))
        $contact[] = 'Email: ' . $setting->email;
    if (!empty($setting->website))
        $contact[] = 'Web: ' . $setting->website;
    $contactStr = implode(' | ', $contact);
    ?>

    <?php if (!empty($kopBase64)): ?>
        <div style="text-align: center; margin-bottom: 15px;">
            <img src="<?= $kopBase64 ?>" style="width: 100%; height: auto;">
        </div>
    <?php else: ?>
        <div style="border-b: 3px double black; padding-bottom: 8px; margin-bottom: 15px;">
            <table width="100%" style="border:none; margin:0; padding:0; border-collapse: collapse;">
                <tr>
                    <td width="15%" align="center" valign="middle" style="border:none;">
                        <?php if (!empty($logoBase64)): ?>
                            <img src="<?= $logoBase64 ?>" style="width: 70px; height: auto;">
                        <?php endif; ?>
                    </td>
                    <td width="85%" align="center" valign="middle" style="border:none;">
                        <?php if (!empty($setting->header_1)): ?>
                            <div style="font-size: 12px; text-transform: uppercase;"><?= esc($setting->header_1) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($setting->header_2)): ?>
                            <div style="font-size: 12px; text-transform: uppercase;"><?= esc($setting->header_2) ?></div>
                        <?php endif; ?>
                        <div style="font-size: 16px; font-weight: bold; margin: 4px 0; text-transform: uppercase;">
                            <?= esc($setting->nama_sekolah ?? 'SEKOLAH') ?>
                        </div>
                        <div style="font-size: 10px; font-weight: normal;">
                            <?= esc($alamatLengkap) ?>
                        </div>
                        <div style="font-size: 10px; font-weight: normal;">
                            <?= esc($contactStr) ?>
                        </div>
                    </td>
                </tr>
    </table>
        </div>
    <?php endif; ?>
    
    <div class="text-center">
        <h3 style="font-size: 14px;">REKAPITULASI DENDA PRESENSI GURU</h3>
        <h4>Bulan: <?= buat_tanggal(date('F Y', strtotime($tahun . '-' . $bulan . '-01'))) ?></h4>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>Nama / NIP</th>
                <th class="text-end" style="width: 65px;">Denda Kotor</th>
                <th class="text-end" style="width: 65px; color: #7e22ce;">Potong Poin</th>
                <th class="text-end" style="width: 65px; color: #198754;">Bayar Tunai</th>
                <th class="text-end" style="width: 65px;">Sisa Bln Ini</th>
                <th class="text-end text-success" style="width: 92px;">Dibayar (YTD)<br><span style="font-size: 7px; font-weight: normal;">Tunai / Poin / Saldo</span></th>
                <th class="text-end text-danger" style="width: 70px;">Sisa (YTD)</th>
                <th class="text-center" style="width: 65px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $grandTotalKotor = 0;
            $grandTotalPoin = 0;
            $grandTotalTunai = 0;
            $grandTotalSaldo = 0;
            $grandTotalSisaBln = 0;
            $grandTotalYTD = 0;
            $grandTotalDibayarYTD = 0;
            $grandTotalDibayarTunaiYTD = 0;
            $grandTotalDibayarPoinYTD = 0;
            $grandTotalDibayarSaldoYTD = 0;
            ?>
            <?php foreach ($users as $user): ?>
                <?php
                $dendaHarian = 0;
                if (isset($mapByUser[$user->id])) {
                    foreach ($mapByUser[$user->id] as $att) {
                        $dendaHarian += (float)($att->denda ?? 0);
                    }
                }
                // Denda alpha flat per hari (tanggal bolong yang belum tercatat)
                $dendaAlpha = 0;
                if (isset($alphaDetailMap[$user->id]['total_denda'])) {
                    // alphaDetailMap berisi total flat untuk alpha yang belum tercatat
                    $dendaAlpha = (float)$alphaDetailMap[$user->id]['total_denda'];
                    // Jika ada recorded alpha di mapByUser, dendaHarian sudah include recorded; alphaDetail hanya missing, jadi total = dendaHarian + missing flat
                } else {
                    $alphaCount = $alphaMap[$user->id] ?? 0;
                    $dendaAlpha = $alphaCount * (float)($absenSetting->denda_alpha_per_hari ?? 0);
                }
                $totalDendaKotor = $dendaHarian + $dendaAlpha;

                $bayarTunai = $bayarTunaiMap[$user->id] ?? 0;
                $potonganPoin = $potonganPoinMap[$user->id] ?? 0;
                $potonganSaldo = ($potonganSaldoMap ?? [])[$user->id] ?? 0;
                // Saldo denda adalah deposit, bukan kas — dipisah dari "Bayar Tunai"
                // agar cocok dengan rincian YTD di bawah ("cash/transfer" vs "Potong Saldo").
                $totalSudahBayar = $bayarTunai + $potonganPoin + $potonganSaldo;
                $sisaBlnIni = max(0, $totalDendaKotor - $totalSudahBayar);

                $grandTotalKotor += $totalDendaKotor;
                $grandTotalPoin += $potonganPoin;
                $grandTotalTunai += $bayarTunai;
                $grandTotalSaldo += $potonganSaldo;
                $grandTotalSisaBln += $sisaBlnIni;

                $isLunas = ($totalDendaKotor > 0 && $sisaBlnIni == 0) || isset($lunasMap[$user->id]);

                // Hitung akumulasi YTD (rincian per metode bayar)
                $ytd = $ytdStats[$user->id] ?? ['sisa' => 0, 'total_dibayar' => 0];
                $sisaYTD = $ytd['sisa'];
                $dibayarYTD = $ytd['total_dibayar'] ?? 0;
                $dibayarTunaiYTD = $ytd['dibayar_tunai'] ?? 0;
                $dibayarPoinYTD = $ytd['potongan_poin'] ?? 0;
                $dibayarSaldoYTD = $ytd['potongan_saldo'] ?? 0;
                $grandTotalYTD += $sisaYTD;
                $grandTotalDibayarYTD += $dibayarYTD;
                $grandTotalDibayarTunaiYTD += $dibayarTunaiYTD;
                $grandTotalDibayarPoinYTD += $dibayarPoinYTD;
                $grandTotalDibayarSaldoYTD += $dibayarSaldoYTD;

                $statusLabel = 'Nihil';
                if ($totalDendaKotor > 0) {
                    if ($sisaBlnIni == 0) {
                        if ($bayarTunai > 0 && $potonganPoin + $potonganSaldo > 0) $statusLabel = 'Lunas (Mix)';
                        elseif ($potonganPoin > 0) $statusLabel = 'Lunas (Poin)';
                        elseif ($potonganSaldo > 0) $statusLabel = 'Lunas (Saldo)';
                        else $statusLabel = 'Lunas (Tunai)';
                    } elseif ($totalSudahBayar > 0) {
                        $statusLabel = 'Sebagian';
                    } else {
                        $statusLabel = 'Belum';
                    }
                }
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <div class="fw-bold"><?= esc($user->nama_guru ?: ($user->first_name . ' ' . $user->last_name)) ?></div>
                        <div style="font-size: 8px; color: #555;">NIP: <?= esc($user->nip ?: '-') ?></div>
                    </td>
                    <td class="text-end"><?= number_format($totalDendaKotor, 0, ',', '.') ?></td>
                    <td class="text-end text-purple"><?= $potonganPoin > 0 ? number_format($potonganPoin, 0, ',', '.') : '-' ?></td>
                    <td class="text-end text-success">
                        <div><?= $bayarTunai > 0 ? number_format($bayarTunai, 0, ',', '.') : '-' ?></div>
                        <?php if ($potonganSaldo > 0): ?>
                        <div style="font-size: 7.5px; color: #0369a1;">Saldo: <?= number_format($potonganSaldo, 0, ',', '.') ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold <?= $sisaBlnIni > 0 ? 'text-danger' : '' ?>"><?= number_format($sisaBlnIni, 0, ',', '.') ?></td>
                    <td class="text-end">
                        <div class="fw-bold text-success"><?= number_format($dibayarYTD, 0, ',', '.') ?></div>
                        <?php if ($dibayarYTD > 0): ?>
                        <div style="font-size: 7.5px; color: #333; margin-top: 2px;">
                            <?php if ($dibayarTunaiYTD > 0): ?>
                            <div>Tunai: Rp <?= number_format($dibayarTunaiYTD, 0, ',', '.') ?></div>
                            <?php endif; ?>
                            <?php if ($dibayarPoinYTD > 0): ?>
                            <div>Poin: Rp <?= number_format($dibayarPoinYTD, 0, ',', '.') ?></div>
                            <?php endif; ?>
                            <?php if ($dibayarSaldoYTD > 0): ?>
                            <div>Saldo: Rp <?= number_format($dibayarSaldoYTD, 0, ',', '.') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold text-danger"><?= number_format($sisaYTD, 0, ',', '.') ?></td>
                    <td class="text-center fw-bold">
                        <?= $statusLabel ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data guru.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="2" class="text-end">TOTAL:</td>
                <td class="text-end">Rp <?= number_format($grandTotalKotor, 0, ',', '.') ?></td>
                <td class="text-end text-purple">Rp <?= number_format($grandTotalPoin, 0, ',', '.') ?></td>
                <td class="text-end text-success">
                    <div>Rp <?= number_format($grandTotalTunai, 0, ',', '.') ?></div>
                    <?php if ($grandTotalSaldo > 0): ?>
                    <div style="font-size: 7.5px; color: #0369a1;">Saldo: Rp <?= number_format($grandTotalSaldo, 0, ',', '.') ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-end">Rp <?= number_format($grandTotalSisaBln, 0, ',', '.') ?></td>
                <td class="text-end text-success">
                    <div>Rp <?= number_format($grandTotalDibayarYTD, 0, ',', '.') ?></div>
                    <div style="font-size: 7.5px; color: #333; font-weight: normal; margin-top: 2px;">
                        <div>Tunai: Rp <?= number_format($grandTotalDibayarTunaiYTD, 0, ',', '.') ?></div>
                        <div>Poin: Rp <?= number_format($grandTotalDibayarPoinYTD, 0, ',', '.') ?></div>
                        <?php if ($grandTotalDibayarSaldoYTD > 0): ?>
                        <div>Saldo: Rp <?= number_format($grandTotalDibayarSaldoYTD, 0, ',', '.') ?></div>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="text-end text-danger">Rp <?= number_format($grandTotalYTD, 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 12px; border: 1px solid #333; padding: 8px 10px; font-size: 9px;">
        <div class="fw-bold" style="margin-bottom: 4px;">RINCIAN PEMBAYARAN DENDA (YTD) TAHUN <?= esc($tahun) ?>:</div>
        <div>1. Bayar Tunai (cash/transfer): <strong>Rp <?= number_format($grandTotalDibayarTunaiYTD, 0, ',', '.') ?></strong></div>
        <div>2. Potong Poin (reward/poin kedisiplinan): <strong>Rp <?= number_format($grandTotalDibayarPoinYTD, 0, ',', '.') ?></strong></div>
        <?php if ($grandTotalDibayarSaldoYTD > 0): ?>
        <div>3. Potong Saldo Denda: <strong>Rp <?= number_format($grandTotalDibayarSaldoYTD, 0, ',', '.') ?></strong></div>
        <?php endif; ?>
        <div style="margin-top: 4px;">Total Dibayar (YTD): <strong>Rp <?= number_format($grandTotalDibayarYTD, 0, ',', '.') ?></strong>
            &nbsp;|&nbsp; Total Sisa (YTD): <strong>Rp <?= number_format($grandTotalYTD, 0, ',', '.') ?></strong></div>
        <div style="margin-top: 4px; color: #555;">Dibayar (YTD) adalah akumulasi seluruh pembayaran denda tahun berjalan per kanal. Perincian per guru ada pada kolom Dibayar (YTD) tiap baris (Tunai / Poin / Saldo).</div>
    </div>

    <table class="footer">
        <tr>
            <td style="width: 60%;"></td>
            <td class="text-center" style="width: 40%;">
                <?= esc($setting->kota ?? 'Kota') ?>, <?= date('d-m-Y') ?><br>
                Kepala Sekolah / Bendahara<br>
                <?php if (!empty($ttdBase64)): ?>
                <div style="text-align: center; margin: 2px 0;">
                    <img src="<?= $ttdBase64 ?>" style="height: 70px; width: auto;">
                </div>
                <?php else: ?>
                <br><br><br><br>
                <?php endif; ?>
                <strong><?= esc(!empty($setting->kepsek) ? $setting->kepsek : (!empty($setting->nama_kepsek) ? $setting->nama_kepsek : '_______________________')) ?></strong><br>
                NIP. <?= esc(!empty($setting->nip_kepsek) ? $setting->nip_kepsek : (!empty($setting->nip) ? $setting->nip : '_______________________')) ?>
            </td>
        </tr>
    </table>
</body>
</html>
