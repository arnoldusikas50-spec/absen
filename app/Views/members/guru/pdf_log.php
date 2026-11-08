<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0 0; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .data-table th { background-color: #f2f2f2; }
        .status-late { color: #d9534f; font-weight: bold; }
        .footer { text-align: right; margin-top: 30px; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rekapitulasi Kehadiran Guru</h2>
        <p>Periode: <?= date('F Y', strtotime($month . '-01')) ?></p>
    </div>

    <table class="info-table">
        <tr>
            <td width="100"><strong>Nama</strong></td>
            <td>: <?= esc($user->first_name . ' ' . $user->last_name) ?></td>
        </tr>
        <tr>
            <td><strong>NIP/Username</strong></td>
            <td>: <?= esc($user->username) ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="80">Tanggal</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Status</th>
                <th>Ket</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada data absensi.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data as $i => $row): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= date('d/m/Y', strtotime($row->tgl)) ?></td>
                        <td><?= $row->jam_masuk ? date('H:i', strtotime($row->jam_masuk)) : '-' ?></td>
                        <td><?= $row->jam_pulang ? date('H:i', strtotime($row->jam_pulang)) : '-' ?></td>
                        <td>
                            <?php 
                            if ($row->status_masuk === 'LATE') {
                                echo '<span class="status-late">Terlambat (' . ($row->telat_menit ?? 0) . 'm)</span>';
                            } elseif (strpos($row->status_masuk, 'DINAS') !== false) {
                                echo 'Dinas Luar';
                            } else {
                                echo 'Hadir';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $ket = [];
                            if (($row->datang_awal_menit ?? 0) > 0) $ket[] = "Awal: {$row->datang_awal_menit}m";
                            if (($row->pulang_akhir_menit ?? 0) > 0) $ket[] = "Akhir: {$row->pulang_akhir_menit}m";
                            echo implode(', ', $ket);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: <?= date('d/m/Y H:i:s') ?>
    </div>
</body>
</html>
