<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Rekap Presensi Siswa') ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 10mm 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .text-center { text-align: center; }
        .text-left   { text-align: left; }
        .text-right  { text-align: right; }
        .text-bold   { font-weight: bold; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th, td {
            border: 0.5px solid #333;
            padding: 2px 1px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
        }

        th {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8px;
        }

        .bg-gray   { background-color: #e2e8f0; }
        .bg-red    { background-color: #fee2e2; }
        .bg-teal   { background-color: #ccfbf1; }
        .bg-green  { background-color: #dcfce7; }
        .bg-yellow { background-color: #fef9c3; }
        .bg-blue   { background-color: #e0f2fe; }

        .header {
            margin-bottom: 8px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 6px;
        }

        .kop-table {
            width: 100%;
            border: none;
            margin: 0;
        }

        .kop-table td {
            border: none;
            padding: 0;
            text-align: center;
        }

        .kop-img {
            height: 55px;
            width: auto;
        }

        .instansi {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .alamat {
            font-size: 8.5px;
            color: #444;
        }

        .judul-laporan {
            font-size: 11px;
            font-weight: bold;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .subjudul-laporan {
            font-size: 9px;
            color: #333;
            margin-top: 1px;
        }

        .ttd-table {
            width: 100%;
            border: none;
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .ttd-table td {
            border: none;
            padding: 0;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <?php
    $namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $bulanInt  = (int) $bulan;
    $hasKop = !empty($setting->kopsekolah) && file_exists(FCPATH . $setting->kopsekolah);
    ?>

    <div class="header" <?= $hasKop ? 'style="border-bottom: none; padding-bottom: 0;"' : '' ?>>
        <?php if ($hasKop):
            $path = FCPATH . $setting->kopsekolah;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        ?>
            <center>
                <img src="<?= $base64 ?>" style="max-height: 70px; width: auto; max-width: 100%;">
            </center>
        <?php else: ?>
            <table class="kop-table">
                <tr>
                    <td style="width: 12%; text-align: left;">
                        <?php if (!empty($setting->logo_kiri)):
                            $path = FCPATH . $setting->logo_kiri;
                            if (file_exists($path)) {
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                echo '<img src="' . $base64 . '" class="kop-img">';
                            }
                        endif; ?>
                    </td>
                    <td style="width: 76%;">
                        <div class="instansi"><?= esc($setting->nama_sekolah ?? 'SEKOLAH') ?></div>
                        <div class="alamat"><?= esc($setting->alamat_sekolah ?? $setting->alamat ?? '') ?></div>
                        <?php if (!empty($setting->kota)): ?>
                            <div class="alamat"><?= esc($setting->kota) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 12%;"></td>
                </tr>
            </table>
        <?php endif; ?>

        <div class="text-center">
            <div class="judul-laporan">REKAPITULASI PRESENSI SISWA BULANAN</div>
            <div class="subjudul-laporan">
                Periode: <strong><?= $namaBulan[$bulanInt] ?? '' ?> <?= $tahun ?></strong>
                &nbsp;&bull;&nbsp; Kelas: <strong><?= esc($kelasInfo->nama_kelas ?? 'Semua Kelas') ?></strong>
                <?php if (!empty($tp_active)): ?>
                    &nbsp;&bull;&nbsp; TP: <strong><?= esc($tp_active->tahun ?? '') ?></strong> (Semester <?= esc($smt_active->smt ?? $smt_active->nama_smt ?? '') ?>)
                <?php endif; ?>
                &nbsp;&bull;&nbsp; <?= esc($hariEfektifLabel ?? ($hariEfektif . ' hari efektif')) ?>
            </div>
        </div>
    </div>

    <?php
    // Peta hari libur mingguan (gabungan Jadwal Kerja GTK + Hari Libur Mingguan)
    // dipakai untuk menandai header dan untuk menentukan auto-alpha.
    $isLiburMingguan = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
        $isLiburMingguan[$d] = attendance_is_weekly_holiday(
            date('D', strtotime($tgl)),
            (int) date('N', strtotime($tgl)),
            $jadwalKerja ?? [],
            $hariLiburMingguan ?? []
        );
    }
    ?>
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 18px;">No</th>
                <th rowspan="2" style="width: 50px;">NIS</th>
                <th rowspan="2" style="width: 130px; text-align:left; padding-left:3px;">Nama Siswa</th>
                <th colspan="<?= $daysInMonth ?>">Tanggal &mdash; <?= $namaBulan[$bulanInt] ?> <?= $tahun ?></th>
                <th colspan="8">Total</th>
            </tr>
            <tr>
                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $isLib = !empty($isLiburMingguan[$d]);
                    $namaH = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                    ?>
                    <th style="width: <?= round(540 / $daysInMonth) ?>px; font-size: 7.5px;<?= $isLib ? ' background:#e5e7eb; color:#6b7280;' : '' ?>"
                        title="<?= $isLib ? $namaH[(int) date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d)))] . ' (libur)' : '' ?>">
                        <?= $d ?>
                    </th>
                <?php endfor; ?>
                <th style="width: 18px;" title="Hadir">H</th>
                <th style="width: 18px;" title="Terlambat">TL</th>
                <th style="width: 18px;" title="Sakit">S</th>
                <th style="width: 18px;" title="Izin">I</th>
                <th style="width: 18px;" title="Alpa">A</th>
                <th style="width: 18px;" title="Belajar dari Rumah">R</th>
                <th style="width: 26px;" title="Persentase">%</th>
                <th style="width: 52px; font-size:7px;" title="Total denda alpha &amp; bolos, sudah dipotong batas denda siswa per bulan">Denda</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grandH = $grandTL = $grandS = $grandI = $grandA = $grandB = $grandBLuarJadwal = 0;
            $grandEfektif = 0;
            $grandDenda = 0.0;

            foreach ($students as $siswa):
                $cntH = $cntTL = $cntS = $cntI = $cntA = $cntB = 0;
                $cntBLuarJadwal = 0;
                $cntBdrHadir = 0;
                $kelasSiswaId     = (int) ($siswa->id_kelas ?? 0);
                $bdrTanggalSiswa  = $bdrByKelas[$kelasSiswaId] ?? [];
                $hariEfektifSiswa = $hariEfektifByKelas[$kelasSiswaId] ?? 0;
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= esc($siswa->nis ?? $siswa->nisn ?? '-') ?></td>
                <td class="text-left" style="padding-left:3px;">
                    <div style="font-weight:bold; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:128px;">
                        <?= esc($siswa->nama) ?>
                    </div>
                    <?php if ($idKelas === 0 && !empty($siswa->nama_kelas)): ?>
                        <div style="font-size:6.5px; color:#555; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:128px;">
                            <?= esc($siswa->nama_kelas) ?>
                        </div>
                    <?php endif; ?>
                </td>

                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    $isHoliday = isset($holidays[$dateStr]);
                    $isBdrJadwal = isset($bdrTanggalSiswa[$dateStr]);
                    // Nama variabel sengaja berbeda dari peta array di atas,
                    // jangan sampai menimpanya (bool vs array).
                    $isLiburHariIni = !empty($isLiburMingguan[$d]);
                    $rec       = $mapBySiswa[$siswa->id_siswa][$dateStr] ?? null;

                    $cell = attendance_rekap_day_cell(
                        $rec,
                        $dateStr,
                        $isLiburHariIni,
                        $isHoliday,
                        $isBdrJadwal,
                        $jamBatasMasukPerHari[$d] ?? $jamBatasMasuk,
                        null,
                        attendance_hari_tercatat($tercatatByKelas ?? [], $dateStr, $kelasSiswaId)
                    );

                    $bg = '';
                    $val = '';

                    switch ($cell['state']) {
                        case 'holiday':
                            $bg  = 'bg-red';
                            $val = 'L';
                            break;

                        case 'off':
                            $bg  = 'bg-gray';
                            $val = '&bull;';
                            break;

                        case 'present':
                            $cntH++;
                            if ($isBdrJadwal) $cntBdrHadir++;
                            $bg  = 'bg-green';
                            $val = 'H';
                            break;

                        case 'late':
                            $cntH++;
                            $cntTL++;
                            if ($isBdrJadwal) $cntBdrHadir++;
                            $bg  = 'bg-yellow';
                            $val = 'TL';
                            break;

                        case 'sakit':
                            $cntS++;
                            $bg  = 'bg-yellow';
                            $val = 'S';
                            break;

                        case 'izin':
                            $cntI++;
                            $bg  = 'bg-blue';
                            $val = 'I';
                            break;

                        case 'bdr_outside':
                            $cntB++;
                            $cntBLuarJadwal++;
                            $bg  = 'bg-teal';
                            $val = 'R';
                            break;

                        case 'bdr_scheduled':
                            $cntB++;
                            $bg  = 'bg-teal';
                            $val = 'R';
                            break;

                        case 'alpha':
                            $cntA++;
                            $bg  = 'bg-red';
                            $val = 'A';
                            break;

                        case 'no_data':
                            // Presensi kelas ini belum tercatat hari itu.
                            $val = '&mdash;';
                            break;

                        default: // future
                            $val = '&mdash;';
                    }
                ?>
                <td class="<?= $bg ?>" style="font-size:7px;"><?= $val ?></td>
                <?php endfor; ?>

                <?php
                // Hari BDR tidak menurunkan persen: BDR di luar jadwal tetap
                // dikurangi dari pembagi, yang sudah terjadwal sudah dipotong
                // dari hari efektif kelas. Hadir fisik di hari BDR dikembalikan.
                $efektifSiswa = max(0, $hariEfektifSiswa + $cntBdrHadir - $cntBLuarJadwal);
                $persen = $efektifSiswa > 0 ? round(($cntH / $efektifSiswa) * 100, 1) : 0;
                $grandH  += $cntH;
                $grandTL += $cntTL;
                $grandS  += $cntS;
                $grandI  += $cntI;
                $grandA  += $cntA;
                $grandB  += $cntB;
                $grandBLuarJadwal += $cntBLuarJadwal;
                $grandEfektif += $efektifSiswa;
                // Netto sudah dipotong plafon bulanan per siswa (SiswaDendaService).
                $dendaNetto = (float) ($dendaSiswa[(int) $siswa->id_siswa]['netto'] ?? 0);
                $grandDenda += $dendaNetto;
                ?>
                <td class="bg-green text-bold"><?= $cntH ?></td>
                <td class="bg-yellow"><?= $cntTL ?></td>
                <td class="bg-yellow"><?= $cntS ?></td>
                <td class="bg-blue"><?= $cntI ?></td>
                <td class="bg-red text-bold"><?= $cntA ?></td>
                <td class="bg-teal text-bold"><?= $cntB ?></td>
                <td class="text-bold"><?= $persen ?>%</td>
                <td class="text-bold" style="font-size:7px;"><?= $dendaNetto > 0 ? 'Rp ' . number_format($dendaNetto, 0, ',', '.') : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="3" class="text-right" style="padding-right:4px;">Total Kehadiran:</td>
                <td colspan="<?= $daysInMonth ?>" class="text-center" style="color:#666; font-size:7.5px;">
                    <?= count($students) ?> Siswa &bull; <?= esc($hariEfektifLabel ?? ($hariEfektif . ' hari efektif')) ?>
                </td>
                <td class="bg-green"><?= $grandH ?></td>
                <td class="bg-yellow"><?= $grandTL ?></td>
                <td class="bg-yellow"><?= $grandS ?></td>
                <td class="bg-blue"><?= $grandI ?></td>
                <td class="bg-red"><?= $grandA ?></td>
                <td class="bg-teal"><?= $grandB ?></td>
                <?php
                $grandPersen = $grandEfektif > 0 ? round(($grandH / $grandEfektif) * 100, 1) : 0;
                ?>
                <td><?= $grandPersen ?>%</td>
                <td style="font-size:7px;"><?= $grandDenda > 0 ? 'Rp ' . number_format($grandDenda, 0, ',', '.') : '-' ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- ─── Tanda Tangan ─── -->
    <table class="ttd-table">
        <tr>
            <td style="width: 50%;">
                Mengetahui,<br>
                Kepala <?= esc($setting->nama_sekolah ?? 'Sekolah') ?>
                <br><br><br><br>
                <strong><u><?= esc($setting->kepsek ?? 'Kepala Sekolah') ?></u></strong><br>
                NIP. <?= esc($setting->nip_kepsek ?? $setting->nip ?? '-') ?>
            </td>
            <td style="width: 50%;">
                <?php
                $tglCetak = date('d') . ' ' . $namaBulan[(int) date('n')] . ' ' . date('Y');
                if (!empty($kelasInfo)) {
                    $jabatanTtd   = 'Wali Kelas ' . $kelasInfo->nama_kelas;
                    $namaTtd      = $waliKelas->nama_guru ?? 'Wali Kelas';
                    $nipTtd       = $waliKelas->nip ?? '-';
                } else {
                    $jabatanTtd   = 'Petugas Rekap';
                    $namaTtd      = $user->nama_lengkap ?? $user->username ?? '-';
                    $nipTtd       = '-';
                }
                ?>
                <?= esc($setting->kota ?? 'Tempat') ?>, <?= esc($tglCetak) ?><br>
                <?= esc($jabatanTtd) ?>
                <br><br><br><br>
                <strong><u><?= esc($namaTtd) ?></u></strong><br>
                NIP. <?= esc($nipTtd) ?>
            </td>
        </tr>
    </table>
</body>
</html>
