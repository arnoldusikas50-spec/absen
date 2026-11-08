<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
        }

        .text-left {
            text-align: left;
        }

        .bg-gray {
            background-color: #dddddd;
        }

        .bg-red {
            background-color: #f8d7da;
        }

        .bg-green {
            background-color: #d1e7dd;
        }

        .bg-yellow {
            background-color: #fff3cd;
        }

        .bg-blue {
            background-color: #cff4fc;
        }

        br {
            mso-data-placement: same-cell;
        }

        /* Kop Style */
        .header {
            margin-bottom: 10px;
        }

        .instansi {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .alamat {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <?php
    // Kop sekolah SENGAJA TIDAK dipakai di export Excel.
    // Excel membuka file ini sebagai HTML (Content-Type vnd.ms-excel), dan
    // <img src="data:image/...;base64,..."> tidak selalu ditampilkan dengan
    // benar — hasilnya gambar rusak atau sel bergeser. Karena itu di sini
    // selalu pakai kop teks, sama seperti view PDF yang memang meant untuk
    // dicetak.
    // Kalau butuh kop bergambar, gunakan export PDF.
    ?>

    <div class="header" align="center">
        <h3 style="margin: 0;"><?= esc(strtoupper($setting->nama_sekolah ?? 'SEKOLAH')) ?></h3>
        <p style="margin: 0; font-size: 10px;"><?= $setting->alamat_sekolah ?? '' ?></p>

        <h4 style="margin-top: 10px;">REKAP PRESENSI HARIAN<br>Periode:
            <?= buat_tanggal(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?></h4>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="40">No</th>
                <th rowspan="2" width="250">Nama Guru / Pegawai</th>
                <?php
                $daysInMonth = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));

                // ── Status libur: satu sumber kebenaran ─────────────────
                // Libur mingguan dari AttendancePolicy (absensi_setting, nama
                // hari ternormalisasi) — digabung dengan Status Libur Jadwal
                // Kerja GTK lewat attendance_is_weekly_holiday() di bawah,
                // sama seperti Rekap di layar dan PDF.
                $hariLiburMingguan = \App\Services\AttendancePolicy::hariLiburMingguan();
                $jadwalKerjaMap = $jadwalKerja ?? [];
                $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                $isLiburMingguan = [];
                foreach (range(1, $daysInMonth) as $d) {
                    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    $isLiburMingguan[$d] = attendance_is_weekly_holiday(
                        date('D', strtotime($tgl)),
                        (int) date('N', strtotime($tgl)),
                        $jadwalKerjaMap,
                        $hariLiburMingguan
                    );
                }
                ?>
                <th colspan="<?= $daysInMonth ?>">Tanggal</th>
                <th colspan="8" width="240">Total</th>
            </tr>
            <tr>
                <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                    <th width="40"<?= $isLiburMingguan[$d] ? ' style="background:#e5e7eb; color:#6b7280;"' : '' ?>
                        title="<?= $isLiburMingguan[$d] ? $namaHari[(int) date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d)))] . ' (libur)' : '' ?>">
                        <?= $d ?>
                    </th>
                <?php endfor; ?>
                <th width="30">H</th>
                <th width="30">T</th>
                <th width="30">D</th>
                <th width="30">W</th>
                <th width="30">I/S</th>
                <th width="30">A</th>
                <th width="30">Poin</th>
                <th width="40">Denda</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $totalUsers = count($users);
            foreach ($users as $i => $user):
                $cntHadir = 0;
                $cntTelat = 0;
                $cntDinas = 0;
                $cntWfh = 0;
                $cntIzinSakit = 0;
                $cntAlpha = 0;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-left" height="40">
                        <b style="font-size: 11px;"><?= esc($user->nama_guru) ?></b><br>
                        <span style='mso-number-format:"\@"'>NIP. <?= esc($user->nip ?? '-') ?></span>
                    </td>

                    <?php for ($d = 1; $d <= $daysInMonth; $d++):
                        $date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                        $rec = $mapByUser[$user->id][$date] ?? null;
                        $isHoliday = isset($holidays[$date]);

                        // --- KOLOM LIBUR (label vertikal per nama hari) ---
                        if ($isLiburMingguan[$d]) {
                            // Label dinamis: dulu hardcode "MINGGU", jadi kalau
                            // Sabtu ikut libur kolom kedua akan menulis MINGGU
                            // lagi dan membingungkan.
                            $huruf = str_split(strtoupper($namaHari[(int) date('N', strtotime($date))]));
                            $char = $huruf[$i % count($huruf)] ?? '';

                            $style = "background-color: #dddddd; font-size: 9px; border-t: none; border-b: none;";
                            if ($i === 0)
                                $style .= "border-t: 1px solid black;";
                            if ($i === $totalUsers - 1)
                                $style .= "border-b: 1px solid black;";
                            ?>
                            <td style="<?= $style ?>"><b><?= $char ?></b></td>
                            <?php
                            continue;
                        }

                        $val = '';
                        $bg = '';

                        // Skip counting attendance on holidays/weekends
                        if ($isHoliday) {
                            $bg = 'bg-red';
                            $val = 'LBR';
                        } elseif ($rec) {
                            $st = $rec->status_masuk;

                            if (in_array($st, ['ONTIME', 'LATE'])) {
                                $cntHadir++;
                                $in = date('H:i', strtotime($rec->jam_masuk));
                                $out = $rec->jam_pulang ? date('H:i', strtotime($rec->jam_pulang)) : '-';
                                $val = "$in<br>$out";

                                if ($st == 'LATE') {
                                    $cntTelat++;
                                    $bg = 'bg-yellow';
                                } else {
                                    $bg = 'bg-green';
                                }
                            } elseif (strpos($st, 'DINAS') !== false) {
                                $cntDinas++;
                                $val = "DNS";
                                $bg = 'bg-blue';
                            } elseif ($st == 'WFH') {
                                $cntWfh++;
                                $val = 'WFH';
                                $bg = 'bg-green';
                            } elseif (in_array($st, ['IZIN', 'IJIN', 'SAKIT', 'CUTI'])) {
                                $cntIzinSakit++;
                                $val = in_array($st, ['IZIN','IJIN']) ? 'IZIN' : $st;
                                $bg = 'bg-blue';
                            } elseif (in_array($st, ['ALPHA', 'ALPA'], true)) {
                                $cntAlpha++;
                                $val = 'X';
                                $bg = 'bg-red';
                            }
                        } else {
                            // Tanpa record: alpha hanya bila hari kerja lampau,
                            // bukan hari libur dan bukan izin approved.
                            // Libur mingguan sudah dilewati lewat kolom libur di
                            // atas; di sini tinggal libur nasional.
                            $isLeave = !empty($leaveMap[$user->id][$date]);
                            if (!$isHoliday && !$isLeave && $date < date('Y-m-d')) {
                                $cntAlpha++;
                                $val = 'X';
                                $bg = 'bg-red';
                            }
                        }
                        ?>
                        <td class="<?= $bg ?>" style="font-size: 8px;"><?= $val ?></td>
                    <?php endfor; ?>

                    <td><?= $cntHadir ?></td>
                    <td><?= $cntTelat ?></td>
                    <td><?= $cntDinas ?></td>
                    <td><?= $cntWfh ?></td>
                    <td><?= $cntIzinSakit ?></td>
                    <td><?= $cntAlpha ?></td>
                    <?php
                    $totalPoin = 0;
                    $totalDendaHarian = 0;
                    $recordedAlphaCount = 0;
                    foreach ($mapByUser[$user->id] ?? [] as $date => $rec) {
                        $dKey = (int) date('j', strtotime($date));
                        $isWeekendDate = !empty($isLiburMingguan[$dKey]);
                        $isHolidayDate = isset($holidays[$date]);
                        if ($isHolidayDate || $isWeekendDate) continue;
                        if (isset($rec->points))
                            $totalPoin += (int) $rec->points;
                        if (isset($rec->denda))
                            $totalDendaHarian += (float) $rec->denda;
                        $stMasuk = strtoupper(trim((string)($rec->status_masuk ?? '')));
                        if (in_array($stMasuk, ['ALPHA', 'ALPA'], true)) {
                            $recordedAlphaCount++;
                        }
                    }
                    // Hanya kenakan penalti poin/denda tambahan untuk alpha yang BELUM tercatat di database
                    $unrecordedAlpha = max(0, $cntAlpha - $recordedAlphaCount);
                    $totalPoin += ($unrecordedAlpha * -abs((int) ($absenSetting->point_alpa ?? 5)));

                    $dendaAlpha = $unrecordedAlpha * (int)($absenSetting->denda_alpha_per_hari ?? 0);
                    $totalDendaExport = $totalDendaHarian + $dendaAlpha;
                    ?>
                    <td><?= $totalPoin ?></td>
                    <td><?= $totalDendaExport > 0 ? 'Rp ' . number_format($totalDendaExport, 0, ',', '.') : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <table style="width: 100%; border: none;">
        <tr>
            <td style="border: none; width: 70%;"></td>
            <td style="border: none; width: 30%; text-align: center;">
                <div style="font-size: 11px;">
                    <?= $setting->kota ?? 'Kota' ?>, <?= date('d-m-Y') ?>
                </div>
                <div style="font-size: 11px; margin-bottom: 50px;">Kepala Sekolah</div>
                <div style="font-weight: bold; text-decoration: underline; font-size: 11px;">
                    <?= $setting->kepsek ?? '(.........................)' ?>
                </div>
                <div style="font-size: 11px;">NIP. <?= esc($setting->nip ?? '-') ?></div>
            </td>
        </tr>
    </table>
</body>

</html>