<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?= $title ?? 'Rekap Presensi' ?>
    </title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .mb-1 {
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 0.5px solid #000;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .bg-gray {
            background-color: #ddd;
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

        .bg-dark-red {
            background-color: #f5c6cb;
        }

        .header {
            margin-bottom: 10px;
            border-b: 2px solid #000;
            padding-bottom: 10px;
        }

        .kop-table {
            width: 100%;
            border: none;
            margin-bottom: 0;
        }

        .kop-table td {
            border: none;
            padding: 0;
            text-align: center;
        }

        .kop-img {
            height: 60px;
            width: auto;
        }

        .instansi {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .alamat {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <?php
    $hasKop = !empty($setting->kopsekolah) && file_exists(FCPATH . $setting->kopsekolah);
    ?>
    <div class="header" <?= $hasKop ? 'style="border-b: none; padding-bottom: 0;"' : '' ?>>
        <?php if ($hasKop):
            $path = FCPATH . $setting->kopsekolah;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            ?>
            <center>
                <img src="<?= $base64 ?>" style="width: auto; max-width: 100%; height: 90px;">
            </center>
        <?php else: ?>
            <table class="kop-table">
                <tr>
                    <td style="width: 15%; text-align: left;">
                        <?php if (!empty($setting->logo_kiri)): ?>
                            <?php
                            // Convert to base64 for reliable PDF rendering
                            $path = FCPATH . $setting->logo_kiri;
                            if (file_exists($path)) {
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                echo '<img src="' . $base64 . '" class="kop-img">';
                            }
                            ?>
                        <?php endif; ?>
                    </td>
                    <td style="width: 70%;">
                        <div class="instansi">
                            <?= esc($setting->nama_sekolah ?? 'NAMA SEKOLAH') ?>
                        </div>
                        <div class="alamat">
                            <?= $setting->alamat_sekolah ?? $setting->alamat ?? '' ?>
                        </div>
                        <?php if (!empty($setting->kota)): ?>
                            <div class="alamat">
                                <?= $setting->kota ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 15%;"></td>
                </tr>
            </table>
        <?php endif; ?>

        <div class="text-center" style="margin-top: 10px;">
            <div class="text-bold" style="font-size: 12px;">REKAP PRESENSI HARIAN</div>
            <div style="font-size: 10px;">Periode:
                <?= buat_tanggal(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))) ?>
            </div>
        </div>
    </div>

    <?php
    $days = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));

    // ── Status libur: satu sumber kebenaran ──────────────────────────
    // Daftar hari libur mingguan diambil dari AttendancePolicy (kolom
    // absensi_setting.hari_libur_mingguan, nama hari ternormalisasi) dan
    // digabung dengan Status Libur Jadwal Kerja GTK lewat
    // attendance_is_weekly_holiday(), sama seperti Rekap di layar, gate QR,
    // dan mesin penalty.
    $hariLiburMingguan = \App\Services\AttendancePolicy::hariLiburMingguan();
    $jadwalKerjaMap = $jadwalKerja ?? [];

    $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $isLiburMingguan = [];
    $labelLibur = [];
    for ($d = 1; $d <= $days; $d++) {
        $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
        $iso = (int) date('N', strtotime($tgl));
        $isLiburMingguan[$d] = attendance_is_weekly_holiday(date('D', strtotime($tgl)), $iso, $jadwalKerjaMap, $hariLiburMingguan);
        $labelLibur[$d] = strtoupper($namaHari[$iso] ?? 'LIBUR');
    }
    ?>
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 20px;">No</th>
                <th rowspan="2">Nama Guru / Pegawai</th>
                <th colspan="<?= $days ?>">Tanggal</th>
                <th colspan="6">Total</th>
            </tr>
            <tr>
                <?php for ($d = 1; $d <= $days; $d++): ?>
                    <th style="font-size: 8px; width: 22px;<?= $isLiburMingguan[$d] ? ' background:#e5e7eb; color:#6b7280;' : '' ?>"
                        title="<?= $isLiburMingguan[$d] ? $labelLibur[$d] . ' (libur)' : date('d', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d))) ?>">
                        <?= $d ?>
                    </th>
                <?php endfor; ?>
                <th style="width: 18px; font-size: 8px;" title="Hadir">H</th>
                <th style="width: 18px; font-size: 8px;" title="Telat">T</th>
                <th style="width: 18px; font-size: 8px;" title="Dinas">D</th>
                <th style="width: 18px; font-size: 8px;" title="WFH">W</th>
                <th style="width: 18px; font-size: 8px;" title="Izin/Sakit">I/S</th>
                <th style="width: 18px; font-size: 8px;" title="Alpha">A</th>
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
                    <td>
                        <?= $no++ ?>
                    </td>
                    <td class="text-left" style="font-size: 9px; padding-left: 5px;">
                        <div class="text-bold">
                            <?= esc($user->nama_guru) ?>
                        </div>
                        <div style="font-size: 8px; color: #555;">
                            <?= esc($user->nip ?? '') ?>
                        </div>
                    </td>
                    <?php for ($d = 1; $d <= $days; $d++):
                        $date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                        $rec = $mapByUser[$user->id][$date] ?? null;
                        $isHoliday = isset($holidays[$date]);

                        // --- KOLOM LIBUR (vertikal, aman saat page break) ---
                        if ($isLiburMingguan[$d]) {
                            // Pakai manipulasi border, bukan rowspan, karena rowspan
                            // rusak begitu PDF pecah halaman.
                            $huruf = str_split($labelLibur[$d]);
                            $charIndex = $i % count($huruf);
                            $char = $huruf[$charIndex] ?? '&nbsp;';

                            $style = "font-size: 8px; vertical-align: middle; border-top: none; border-bottom: none;";
                            if ($i === 0) {
                                // Baris pertama tetap butuh border atas (dari table collapse)
                                $style = "font-size: 8px; vertical-align: middle; border-bottom: none;";
                            }
                            if ($i === $totalUsers - 1) {
                                $style = "font-size: 8px; vertical-align: middle; border-top: none; border-bottom: 0.5px solid #000;";
                            }
                            ?>
                            <td class="bg-gray text-bold" style="<?= $style ?>">
                                <?= $char ?>
                            </td>
                            <?php
                            continue;
                        }

                        // --- NORMAL DAYS ---
                        $bg = '';
                        $val = '';
                        $fontSize = '7px';

                        // Libur nasional → LBR, tidak dihitung sebagai kehadiran.
                        if ($isHoliday) {
                            $bg = 'bg-red';
                            $val = 'LBR';
                        } elseif ($rec) {
                            $s = $rec->status_masuk;

                            if (in_array($s, ['ONTIME', 'LATE'])) {
                                $cntHadir++;
                                if ($s == 'LATE') {
                                    $cntTelat++;
                                    $bg = 'bg-yellow';
                                } else {
                                    $bg = 'bg-green';
                                }

                                $msTs  = \App\Services\AttendancePolicy::pickTime($rec->jam_masuk ?? null);
                                $plTs  = \App\Services\AttendancePolicy::pickTime($rec->jam_pulang ?? null);
                                $masuk = $msTs !== null ? substr($msTs, 0, 5) : '-';
                                $pulang = $plTs !== null ? substr($plTs, 0, 5) : '-';
                                $val = $masuk . '<br>' . $pulang;

                            } elseif (strpos($s, 'DINAS') !== false) {
                                $cntDinas++;
                                $val = 'DNS';
                                $bg = 'bg-blue';
                            } elseif ($s == 'WFH') {
                                $cntWfh++;
                                $val = 'WFH';
                                $bg = 'bg-green';
                            } elseif ($s == 'IJIN' || $s == 'IZIN') {
                                $cntIzinSakit++;
                                $val = 'IZIN';
                                $bg = 'bg-blue';
                            } elseif ($s == 'SAKIT') {
                                $cntIzinSakit++;
                                $val = 'SAKIT';
                                $bg = 'bg-blue';
                            } elseif ($s == 'CUTI') {
                                $cntIzinSakit++;
                                $val = 'CUTI';
                                $bg = 'bg-blue';
                            } elseif ($s === 'PENDING_VERIFICATION') {
                                // Menunggu verifikasi admin: belum hadir, tapi
                                // juga belum terbukti alpha — jangan ditandai X.
                                $val = 'PE';
                                $bg = 'bg-yellow';
                            } elseif (in_array($s, ['ALPHA', 'ALPA'], true)) {
                                $cntAlpha++;
                                $val = 'X';
                                $bg = 'bg-red';
                            }
                        } else {
                            // Tanpa record: tandai alpha hanya bila hari kerja yang
                            // sudah lewat. Libur mingguan sudah dilewati lewat
                            // spanner di atas; yang dicek di sini adalah libur
                            // nasional, supaya hari merah tidak muncul sebagai X.
                            //
                            // leaveMap ikut dipakai supaya guru yang izinnya sudah
                            // disetujui tidak ditandai alpha — sama seperti Rekap di
                            // layar. Sebelumnya PDF Ignore izin dan menampilkan X.
                            $isLeave = !empty($leaveMap[$user->id][$date]);
                            $isLiburNasional = isset($holidays[$date]);
                            // `<` bukan `<=`: hari berjalan belum selesai, dan
                            // PDF dibagikan/arsip. Rekap layar (rekap.php:411)
                            // dan Excel (:229) sudah memakai `<` lebih dulu.
                            if (!$isLiburNasional && !$isLeave && $date < date('Y-m-d')) {
                                $cntAlpha++;
                                $val = 'X';
                                $bg = 'bg-red';
                            }
                        }
                        ?>
                        <td class="<?= $bg ?>" style="font-size: <?= $fontSize ?>; line-height: 1.1;">
                            <?= $val ?>
                        </td>
                    <?php endfor; ?>

                    <td class="text-bold" style="font-size: 9px;">
                        <?= $cntHadir ?>
                    </td>
                    <td style="font-size: 9px;">
                        <?= $cntTelat ?>
                    </td>
                    <td style="font-size: 9px;">
                        <?= $cntDinas ?>
                    </td>
                    <td style="font-size: 9px;">
                        <?= $cntWfh ?>
                    </td>
                    <td style="font-size: 9px;">
                        <?= $cntIzinSakit ?>
                    </td>
                    <td style="font-size: 9px;">
                        <?= $cntAlpha ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 20px; border: none;">
        <tr>
            <td style="border: none; width: 70%;"></td>
            <td style="border: none; width: 30%; text-align: center;">
                <div style="font-size: 10px;">
                    <?= $setting->kota ?? 'Kota' ?>,
                    <?= date('d-m-Y') ?>
                </div>
                <div style="font-size: 10px; margin-bottom: 50px;">Kepala Sekolah</div>
                <div class="text-bold text-underline" style="text-decoration: underline; font-size: 11px;">
                    <?= $setting->kepsek ?? '(.........................)' ?>
                </div>
                <div style="font-size: 10px;">NIP.
                    <?= esc($setting->nip ?? '-') ?>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>