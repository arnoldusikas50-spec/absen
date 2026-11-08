<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi Siswa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .bg-gray {
            background-color: #dddddd;
        }

        .bg-red {
            background-color: #f8d7da;
        }

        .bg-teal {
            background-color: #d1faf5;
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

        .header {
            margin-bottom: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
    $namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $bulanInt  = (int) $bulan;
    // Kop sekolah SENGAJA TIDAK dipakai di export Excel — lihat catatan pada
    // rekap_excel.php. Selalu pakai kop teks.
    ?>

    <div class="header">
        <h3 style="margin:0; text-transform:uppercase;"><?= esc($setting->nama_sekolah ?? 'SEKOLAH') ?></h3>
        <p style="margin:2px 0 0 0; font-size:10px;"><?= esc($setting->alamat_sekolah ?? '') ?></p>

        <h3 style="margin:8px 0 2px 0;">REKAPITULASI PRESENSI SISWA BULANAN</h3>
        <p style="margin:0; font-size:11px;">
            Periode: <strong><?= $namaBulan[$bulanInt] ?? '' ?> <?= $tahun ?></strong>
            &nbsp;|&nbsp; Kelas: <strong><?= esc($kelasInfo->nama_kelas ?? 'Semua Kelas') ?></strong>
            <?php if (!empty($tp_active)): ?>
                &nbsp;|&nbsp; TP: <strong><?= esc($tp_active->tahun ?? '') ?></strong> (Semester <?= esc($smt_active->smt ?? $smt_active->nama_smt ?? '') ?>)
            <?php endif; ?>
            &nbsp;|&nbsp; <?= esc($hariEfektifLabel ?? ($hariEfektif . ' hari efektif')) ?>
        </p>
    </div>

    <?php
    // Peta hari libur mingguan (gabungan Jadwal Kerja GTK + Hari Libur Mingguan)
    // untuk menandai header dan menentukan auto-alpha.
    $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
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
                <th rowspan="2" width="35">No</th>
                <th rowspan="2" width="90">NIS</th>
                <th rowspan="2" width="220">Nama Siswa</th>
                <th colspan="<?= $daysInMonth ?>">Tanggal &mdash; <?= $namaBulan[$bulanInt] ?> <?= $tahun ?></th>
                <th colspan="7" width="245">Total</th>
            </tr>
            <tr>
                <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                    <th width="32"<?= $isLiburMingguan[$d] ? ' style="background:#e5e7eb; color:#6b7280;"' : '' ?>
                        title="<?= $isLiburMingguan[$d] ? $namaHari[(int) date('N', strtotime(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d)))] . ' (libur)' : '' ?>">
                        <?= $d ?>
                    </th>
                <?php endfor; ?>
                <th width="35">H</th>
                <th width="35">TL</th>
                <th width="35">S</th>
                <th width="35">I</th>
                <th width="35">A</th>
                <th width="35">BDR</th>
                <th width="45">%</th>
                <th width="80">Denda</th>
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
                <td style='mso-number-format:"\@"'><?= esc($siswa->nis ?? $siswa->nisn ?? '-') ?></td>
                <td class="text-left">
                    <strong><?= esc($siswa->nama) ?></strong>
                    <?php if ($idKelas === 0 && !empty($siswa->nama_kelas)): ?>
                        <br><small style="color:#666;"><?= esc($siswa->nama_kelas) ?></small>
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

                    $cell  = attendance_rekap_day_cell(
                        $rec,
                        $dateStr,
                        $isLiburHariIni,
                        $isHoliday,
                        $isBdrJadwal,
                        $jamBatasMasukPerHari[$d] ?? $jamBatasMasuk,
                        null,
                        attendance_hari_tercatat($tercatatByKelas ?? [], $dateStr, $kelasSiswaId)
                    );
                    $bg  = '';
                    $val = '';

                    switch ($cell['state']) {
                        case 'holiday':
                            $bg  = 'bg-red';
                            $val = 'LBR';
                            break;

                        case 'off':
                            $bg  = 'bg-gray';
                            $val = '—';
                            break;

                        case 'present':
                        case 'late':
                            $cntH++;
                            if ($cell['state'] === 'late') $cntTL++;
                            if ($isBdrJadwal) $cntBdrHadir++;

                            $bg  = $cell['state'] === 'late' ? 'bg-yellow' : 'bg-green';
                            $val = $cell['time_in'] !== ''
                                ? substr($cell['time_in'], 0, 5)
                                : ($cell['state'] === 'late' ? 'TL' : 'H');
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
                            $val = 'BDR';
                            break;

                        case 'bdr_scheduled':
                            $cntB++;
                            $bg  = 'bg-teal';
                            $val = 'BDR';
                            break;

                        case 'alpha':
                            $cntA++;
                            $bg  = 'bg-red';
                            $val = 'A';
                            break;

                        case 'no_data':
                            $bg  = '';
                            $val = '—';
                            break;

                        default: // future
                            $val = '—';
                    }
                ?>
                <td class="<?= $bg ?>"><?= $val ?></td>
                <?php endfor; ?>

                <?php
                // Hari BDR di luar jadwal dikeluarkan dari pembagi; hari BDR
                // yang dihadiri fisik dikembalikan ke pembagi.
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
                <td class="bg-green"><strong><?= $cntH ?></strong></td>
                <td class="bg-yellow"><strong><?= $cntTL ?></strong></td>
                <td class="bg-yellow"><strong><?= $cntS ?></strong></td>
                <td class="bg-blue"><strong><?= $cntI ?></strong></td>
                <td class="bg-red"><strong><?= $cntA ?></strong></td>
                <td class="bg-teal"><strong><?= $cntB ?></strong></td>
                <td><strong><?= $persen ?>%</strong></td>
                <td><strong><?= $dendaNetto > 0 ? 'Rp ' . number_format($dendaNetto, 0, ',', '.') : '-' ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #eaeaea; font-weight: bold;">
                <td colspan="3" class="text-right">Total:</td>
                <td colspan="<?= $daysInMonth ?>" style="font-weight: normal; color: #888888;">
                    <?= count($students) ?> Siswa &middot; <?= esc($hariEfektifLabel ?? ($hariEfektif . ' hari efektif')) ?>
                </td>
                <td><?= $grandH ?></td>
                <td><?= $grandTL ?></td>
                <td><?= $grandS ?></td>
                <td><?= $grandI ?></td>
                <td><?= $grandA ?></td>
                <td class="bg-teal"><?= $grandB ?></td>
                <?php
                $grandPersen = $grandEfektif > 0 ? round(($grandH / $grandEfektif) * 100, 1) : 0;
                ?>
                <td><?= $grandPersen ?>%</td>
                <td><strong><?= $grandDenda > 0 ? 'Rp ' . number_format($grandDenda, 0, ',', '.') : '-' ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <br><br>

    <!-- ─── Tanda Tangan ─── -->
    <table style="border:none; width:100%;">
        <tr style="border:none;">
            <td style="border:none; width:50%; text-align:center; vertical-align:top;">
                Mengetahui,<br>
                Kepala <?= esc($setting->nama_sekolah ?? 'Sekolah') ?><br><br><br><br><br>
                <strong><u><?= esc($setting->kepsek ?? 'Kepala Sekolah') ?></u></strong><br>
                NIP. <?= esc($setting->nip_kepsek ?? $setting->nip ?? '-') ?>
            </td>
            <td style="border:none; width:50%; text-align:center; vertical-align:top;">
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
                <?= esc($jabatanTtd) ?><br><br><br><br><br>
                <strong><u><?= esc($namaTtd) ?></u></strong><br>
                NIP. <?= esc($nipTtd) ?>
            </td>
        </tr>
    </table>
</body>
</html>
