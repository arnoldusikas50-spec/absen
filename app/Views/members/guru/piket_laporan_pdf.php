<!doctype html>
<html lang="id">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta charset="utf-8">
    <title>Laporan Piket - <?= esc($tanggal) ?></title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.35;
            margin: 15px 20px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px double #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td { vertical-align: middle; }
        .school-name {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin: 0 0 2px;
        }
        .school-sub {
            font-size: 10px;
            color: #475569;
            margin: 0;
        }
        .report-title {
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 10px 0 4px;
            color: #0f172a;
        }
        .report-subtitle {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            margin-bottom: 12px;
        }
        
        .info-box {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 0; vertical-align: top; font-size: 10px; }
        .info-label { width: 18%; color: #64748b; font-weight: 600; }
        .info-val { font-weight: 700; color: #0f172a; }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary-grid td {
            width: 25%;
            padding: 6px 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .summary-num {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
            display: block;
        }
        .summary-lbl {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }

        .section-title {
            font-size: 11px;
            font-weight: 800;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 12px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #0f766e;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th {
            background: #0f766e;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
            padding: 5px 6px;
            text-align: left;
            text-transform: uppercase;
            border: 1px solid #0f766e;
        }
        .data-table td {
            padding: 5px 6px;
            font-size: 9.5px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .text-center { text-align: center; }
        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 8.5px;
        }
        .bg-hadir { background: #dcfce7; color: #166534; }
        .bg-sakit { background: #dbeafe; color: #1e40af; }
        .bg-izin  { background: #fef9c3; color: #854d0e; }
        .bg-alpa  { background: #fee2e2; color: #991b1b; }
        .bg-none  { background: #f1f5f9; color: #64748b; }

        .signature-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 10px;
        }
        .sig-space { height: 55px; }
        .sig-name { font-weight: 700; text-decoration: underline; }
    </style>
</head>
<body>

    <!-- Header / Kop Surat -->
    <table class="header-table">
        <tr>
            <td style="text-align: center;">
                <div class="school-name"><?= esc($setting->nama_sekolah ?? 'SEKOLAH') ?></div>
                <div class="school-sub"><?= esc($setting->alamat ?? '') ?> <?= !empty($setting->telepon) ? '• Telp: ' . esc($setting->telepon) : '' ?></div>
                <div class="school-sub"><?= !empty($setting->website) ? esc($setting->website) : '' ?></div>
            </td>
        </tr>
    </table>

    <div class="report-title">Laporan Harian Guru Piket & Monitoring KBM</div>
    <div class="report-subtitle">
        Tahun Pelajaran: <?= esc($tp_active->tahun ?? '-') ?> &bull; Semester: <?= esc($smt_active->smt ?? '-') ?>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td class="info-label">Hari / Tanggal</td>
                <td class="info-val">: <?= esc($hari_ini) ?>, <?= date('d F Y', strtotime($tanggal)) ?></td>
                <td class="info-label">Petugas Piket</td>
                <td class="info-val">: 
                    <?php 
                    if (!empty($piket_crew)) {
                        $crews = [];
                        foreach ($piket_crew as $c) $crews[] = esc($c->nama_guru);
                        echo implode(', ', $crews);
                    } else {
                        echo 'Belum dijadwalkan';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Box (satu sumber: $summary dari controller, selaras tampilan web) -->
    <?php
    $totalSlots  = (int)($summary['total_slot']  ?? 0);
    $jurnalCount = (int)($summary['jurnal_isi']  ?? 0);
    $mismatchPdf = (int)($summary['mismatch']    ?? 0);
    $duplikatPdf = (int)($summary['duplikat_jurnal'] ?? 0);
    ?>
    <table class="summary-grid">
        <tr>
            <td>
                <span class="summary-num"><?= $totalSlots ?></span>
                <span class="summary-lbl">Total Slot KBM</span>
            </td>
            <td>
                <span class="summary-num"><?= $jurnalCount ?></span>
                <span class="summary-lbl">Jurnal Terisi</span>
            </td>
            <td>
                <span class="summary-num"><?= count($tamu ?? []) ?></span>
                <span class="summary-lbl">Buku Tamu</span>
            </td>
            <td>
                <span class="summary-num"><?= count($kejadian ?? []) ?></span>
                <span class="summary-lbl">Catatan Kejadian</span>
            </td>
        </tr>
    </table>
    <?php if ($mismatchPdf > 0 || $duplikatPdf > 0): ?>
    <p style="font-size: 9px; color: #b45309; margin: 4px 0 0;">
        Catatan integritas:
        <?= $mismatchPdf > 0 ? $mismatchPdf . ' slot beda antara jurnal & catatan piket. ' : '' ?>
        <?= $duplikatPdf > 0 ? $duplikatPdf . ' jurnal ganda pada slot yang sama (yang terbaru dipakai).' : '' ?>
    </p>
    <?php endif; ?>

    <!-- Rekap Kehadiran Siswa per Kelas (data scan real) -->
    <?php
        $pdfSrKelas = $siswa_rekap['kelas'] ?? [];
        $pdfSrTot = $siswa_rekap['total'] ?? [];
    ?>
    <div class="section-title">Rekap Kehadiran Siswa per Kelas</div>
    <?php if (empty($pdfSrKelas)): ?>
        <p style="text-align: center; color: #94a3b8; padding: 8px;">Belum ada data siswa.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th style="width: 40px; text-align: center;">Total</th>
                    <th style="width: 40px; text-align: center;">Hadir</th>
                    <th style="width: 40px; text-align: center;">Telat</th>
                    <th style="width: 30px; text-align: center;">S</th>
                    <th style="width: 30px; text-align: center;">I</th>
                    <th style="width: 30px; text-align: center;">A</th>
                    <th style="width: 55px; text-align: center;">Blm Plg</th>
                    <th style="width: 55px; text-align: center;">Blm Scan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pdfSrKelas as $rk): ?>
                <tr>
                    <td><b><?= esc($rk->nama_kelas) ?></b></td>
                    <td class="text-center"><?= (int)$rk->total ?></td>
                    <td class="text-center"><?= (int)$rk->r_hadir ?></td>
                    <td class="text-center"><?= (int)$rk->r_telat ?></td>
                    <td class="text-center"><?= (int)$rk->r_sakit ?></td>
                    <td class="text-center"><?= (int)$rk->r_izin ?></td>
                    <td class="text-center"><?= (int)$rk->r_alpha ?></td>
                    <td class="text-center"><?= (int)$rk->r_tanpa_pulang ?></td>
                    <td class="text-center"><?= (int)$rk->r_belum ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><b>Jumlah</b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['total'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_hadir'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_telat'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_sakit'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_izin'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_alpha'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_tanpa_pulang'] ?? 0) ?></b></td>
                    <td class="text-center"><b><?= (int)($pdfSrTot['r_belum'] ?? 0) ?></b></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Rekap Kehadiran Guru -->
    <?php
        $pdfGrList = $guru_rekap['list'] ?? [];
    ?>
    <div class="section-title">Rekap Kehadiran Guru</div>
    <p style="font-size: 9.5px; color: #475569; margin: 0 0 6px;">
        Tepat: <b><?= (int)($guru_rekap['tepat'] ?? 0) ?></b> &bull;
        Telat: <b><?= (int)($guru_rekap['telat'] ?? 0) ?></b> &bull;
        Belum check-in: <b><?= (int)($guru_rekap['belum'] ?? 0) ?></b> &bull;
        Total: <b><?= (int)($guru_rekap['total'] ?? 0) ?></b>
    </p>
    <?php if (!empty($pdfGrList)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Guru</th>
                    <th style="width: 70px; text-align: center;">Masuk</th>
                    <th style="width: 70px; text-align: center;">Pulang</th>
                    <th style="width: 80px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pdfGrList as $g):
                    $kat = $g->hadir_kat ?? 'Belum';
                    $badge = $kat === 'Tepat' ? 'bg-hadir' : ($kat === 'Telat' ? 'bg-izin' : 'bg-alpa');
                ?>
                <tr>
                    <td><?= esc($g->nama_guru ?? '-') ?></td>
                    <td class="text-center"><?= !empty($g->jam_masuk) ? esc(substr((string)$g->jam_masuk, 0, 5)) : '-' ?></td>
                    <td class="text-center"><?= !empty($g->jam_pulang) ? esc(substr((string)$g->jam_pulang, 0, 5)) : '-' ?></td>
                    <td class="text-center"><span class="badge-status <?= $badge ?>"><?= $kat ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Rekap Pembelajaran di Kelas -->
    <?php $pdfBlKelas = $belajar_rekap['kelas'] ?? []; ?>
    <div class="section-title">Rekap Pembelajaran di Kelas</div>
    <p style="font-size: 9.5px; color: #475569; margin: 0 0 6px;">
        Slot terisi jurnal: <b><?= (int)($belajar_rekap['terisi'] ?? 0) ?>/<?= (int)($belajar_rekap['total_slot'] ?? 0) ?></b>
        (<?= (int)($belajar_rekap['persen'] ?? 0) ?>%) &bull; Materi tercatat: <b><?= (int)($belajar_rekap['materi_count'] ?? 0) ?></b>
    </p>
    <?php if (!empty($pdfBlKelas)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Kelas</th>
                    <th style="width: 70px; text-align: center;">Jurnal</th>
                    <th>Materi per Jam</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pdfBlKelas as $bk): ?>
                <tr>
                    <td><b><?= esc($bk->nama_kelas) ?></b></td>
                    <td class="text-center"><?= (int)$bk->jurnal_isi ?>/<?= (int)$bk->total_slot ?></td>
                    <td>
                        <?php if (!empty($bk->materi)): ?>
                            <?php foreach ($bk->materi as $mt): ?>
                                &bull; <?= esc($mt) ?><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: #94a3b8;">Belum ada jurnal terisi.</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Detail KBM per Kelas -->
    <div class="section-title">Rekapitulasi KBM & Kehadiran Guru per Kelas</div>
    <?php if (empty($report)): ?>
        <p style="text-align: center; color: #94a3b8; padding: 12px;">Tidak ada jadwal pelajaran untuk tanggal ini.</p>
    <?php else: ?>
        <?php foreach ($report as $idKls => $kls): ?>
            <div style="font-weight: 700; font-size: 10px; margin: 8px 0 3px; color: #0f172a;">
                Kelas: <?= esc($kls['nama_kelas']) ?>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 35px; text-align: center;">Jam</th>
                        <th style="width: 140px;">Mata Pelajaran</th>
                        <th>Guru Pengampu</th>
                        <th style="width: 80px; text-align: center;">Status KBM</th>
                        <th style="width: 110px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kls['slots'] as $s): 
                        $piket = $s['piket'];
                        $statusText = 'Belum Ada';
                        $statusBadge = 'bg-none';
                        $ket = '-';

                        if ($piket) {
                            $ket = esc($piket->keterangan ?? '-');
                            if ($piket->status === 'H') { $statusText = 'Hadir'; $statusBadge = 'bg-hadir'; }
                            elseif ($piket->status === 'S') { $statusText = 'Sakit'; $statusBadge = 'bg-sakit'; }
                            elseif ($piket->status === 'I') { $statusText = 'Izin'; $statusBadge = 'bg-izin'; }
                            elseif ($piket->status === 'A') { $statusText = 'Alpa'; $statusBadge = 'bg-alpa'; }
                        } elseif (!empty($s['jurnal'])) {
                            $statusText = 'Jurnal Terisi';
                            $statusBadge = 'bg-hadir';
                        } elseif (!empty($s['absensi']) && !empty($s['absensi']->jam_masuk)) {
                            $statusText = 'Check-in Absen';
                            $statusBadge = 'bg-hadir';
                            $ket = 'Masuk ' . substr($s['absensi']->jam_masuk, 0, 5);
                        }
                    ?>
                    <tr>
                        <td class="text-center" style="font-weight: 700;"><?= (int)$s['jam_ke'] ?></td>
                        <td><?= esc($s['nama_mapel']) ?></td>
                        <td><?= esc($s['nama_guru']) ?></td>
                        <td class="text-center">
                            <span class="badge-status <?= $statusBadge ?>"><?= $statusText ?></span>
                        </td>
                        <td><?= $ket ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Catatan Kejadian (jika ada) -->
    <?php if (!empty($kejadian)): ?>
        <div class="section-title">Catatan Kejadian / Pelanggaran Khusus</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">Jam</th>
                    <th style="width: 80px;">Jenis</th>
                    <th>Uraian Kejadian & Penanganan</th>
                    <th style="width: 80px;">Pelapor</th>
                    <th style="width: 60px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kejadian as $k): ?>
                <tr>
                    <td class="text-center"><?= esc(substr($k->jam ?? '', 0, 5)) ?></td>
                    <td><b><?= esc(ucfirst($k->jenis ?? '')) ?></b> (<?= esc($k->tingkat ?? 'ringan') ?>)</td>
                    <td>
                        <?= esc($k->keterangan ?? '-') ?>
                        <?php if (!empty($k->penanganan)): ?>
                            <br><small><i>Penanganan: <?= esc($k->penanganan) ?></i></small>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($k->pelapor ?? '-') ?></td>
                    <td class="text-center"><?= esc(ucfirst($k->status ?? 'belum')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Buku Tamu (jika ada) -->
    <?php if (!empty($tamu)): ?>
        <div class="section-title">Buku Tamu</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;">Waktu</th>
                    <th style="width: 110px;">Nama Tamu</th>
                    <th style="width: 100px;">Instansi</th>
                    <th>Bertemu & Keperluan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tamu as $t): ?>
                <tr>
                    <td class="text-center"><?= esc(substr($t->jam_masuk ?? '', 0, 5)) ?> <?= !empty($t->jam_keluar) ? '- ' . esc(substr($t->jam_keluar, 0, 5)) : '' ?></td>
                    <td><b><?= esc($t->nama) ?></b></td>
                    <td><?= esc($t->instansi ?? '-') ?></td>
                    <td>Bertemu: <b><?= esc($t->bertemu ?? '-') ?></b><br><small><?= esc($t->keperluan ?? '-') ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah
                <div class="sig-space"></div>
                <div class="sig-name"><?= esc(!empty($setting->nama_kepsek) ? $setting->nama_kepsek : (!empty($setting->kepsek) ? $setting->kepsek : 'Kepala Sekolah')) ?></div>
                <div>NIP. <?= esc(!empty($setting->nip_kepsek) ? $setting->nip_kepsek : (!empty($setting->nip) ? $setting->nip : '..................................')) ?></div>
            </td>
            <td>
                <?= esc($setting->kota ?? 'Tempat') ?>, <?= date('d F Y', strtotime($tanggal)) ?><br>
                Petugas Piket
                <div class="sig-space"></div>
                <div class="sig-name">
                    <?php
                    if (!empty($piket_crew)) {
                        echo esc($piket_crew[0]->nama_guru);
                    } elseif (!empty($guru->nama_guru)) {
                        echo esc($guru->nama_guru);
                    } else {
                        echo '..................................';
                    }
                    ?>
                </div>
                <div>NIP. <?= (!empty($piket_crew[0]->nip) ? esc($piket_crew[0]->nip) : (!empty($guru->nip) ? esc($guru->nip) : '..................................')) ?></div>
            </td>
        </tr>
    </table>

</body>
</html>
