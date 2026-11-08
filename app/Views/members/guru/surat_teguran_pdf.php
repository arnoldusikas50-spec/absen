<!DOCTYPE html>
<html>

<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Surat Teguran</title>
    <style>
        body {
            font-family: serif;
            font-size: 12pt;
            margin: 0.5cm 1.5cm;
        }

        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .content {
            margin-top: 30px;
            line-height: 1.5;
        }

        .footer {
            margin-top: 50px;
            text-align: right;
        }

        .ttd {
            margin-top: 70px;
        }
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

    <?php if (isset($kop_base64) && $kop_base64): ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="<?= $kop_base64 ?>" style="width: 100%; height: auto;">
        </div>
    <?php else: ?>
        <div style="border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 20px;">
            <table width="100%">
                <tr>
                    <td width="15%" align="center" valign="middle">
                        <?php if (isset($logo_base64) && $logo_base64): ?>
                            <img src="<?= $logo_base64 ?>" style="width: 80px; height: auto;">
                        <?php endif; ?>
                    </td>
                    <td width="85%" align="center" valign="middle">
                        <!-- Optional Headers (Dinas/Yayasan) -->
                        <?php if (!empty($setting->header_1)): ?>
                            <div style="font-size: 11pt;"><?= strtoupper($setting->header_1) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($setting->header_2)): ?>
                            <div style="font-size: 11pt;"><?= strtoupper($setting->header_2) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($setting->header_3)): ?>
                            <div style="font-size: 11pt;"><?= strtoupper($setting->header_3) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($setting->header_4)): ?>
                            <div style="font-size: 11pt;"><?= strtoupper($setting->header_4) ?></div>
                        <?php endif; ?>

                        <!-- School Name -->
                        <div style="font-size: 14pt; font-weight: bold; margin: 5px 0;">
                            <?= esc(strtoupper($setting->nama_sekolah ?? 'SEKOLAH')) ?>
                        </div>

                        <!-- Address & Contact -->
                        <div style="font-size: 9pt; font-weight: normal;">
                            <?= $alamatLengkap ?>
                        </div>
                        <div style="font-size: 9pt; font-weight: normal;">
                            <?= $contactStr ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 12pt; font-weight: bold; text-decoration: underline;">SURAT TEGURAN</div>
        <div style="font-size: 12pt;">Nomor: <?= $nomor_surat ?></div>
    </div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini, Kepala Sekolah
            <?= esc($setting->nama_sekolah ?? 'Sekolah') ?>, memberikan teguran kepada:
        </p>

        <table style="margin-left: 20px;">
            <tr>
                <td width="120">Nama</td>
                <td>:
                    <?= esc($guru->nama_guru) ?>
                </td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>:
                    <?= esc($guru->nip ?? '-') ?>
                </td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: Guru</td>
            </tr>
        </table>

        <p>
            Berdasarkan data absensi bulan <strong>
                <?php
                $bulanIndo = [
                    'January' => 'Januari',
                    'February' => 'Februari',
                    'March' => 'Maret',
                    'April' => 'April',
                    'May' => 'Mei',
                    'June' => 'Juni',
                    'July' => 'Juli',
                    'August' => 'Agustus',
                    'September' => 'September',
                    'October' => 'Oktober',
                    'November' => 'November',
                    'December' => 'Desember'
                ];
                // Periode surat bila disediakan controller (cetak ulang arsip),
                // jika tidak ada pakai bulan berjalan.
                $namaBulanPdf = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $periodeBulan = isset($periode_bulan) ? (int) $periode_bulan : (int) date('n');
                $periodeTahun = isset($periode_tahun) ? (int) $periode_tahun : (int) date('Y');
                if ($periodeBulan < 1 || $periodeBulan > 12) $periodeBulan = (int) date('n');
                echo $namaBulanPdf[$periodeBulan] . ' ' . $periodeTahun;

                // Rincian tanggal alpha: "tanggal 1, 3 dan 4 Oktober 2026".
                // Dikelompokkan per bulan bila rentang melewati bulan.
                $rinciAlpha = '';
                $daftarAlpha = $alpha_dates ?? [];
                if (!empty($daftarAlpha) && is_array($daftarAlpha)) {
                    $perBulan = [];
                    foreach ($daftarAlpha as $d) {
                        $ts = strtotime((string) $d);
                        if ($ts === false) continue;
                        $perBulan[date('Y-m', $ts)][] = (int) date('j', $ts);
                    }
                    $bagian = [];
                    foreach ($perBulan as $ym => $days) {
                        $days = array_values(array_unique($days));
                        sort($days);
                        $n = count($days);
                        if ($n === 1) {
                            $tglStr = (string) $days[0];
                        } elseif ($n === 2) {
                            $tglStr = $days[0] . ' dan ' . $days[1];
                        } else {
                            $tglStr = implode(', ', array_slice($days, 0, -1)) . ' dan ' . end($days);
                        }
                        [$y, $m] = explode('-', $ym);
                        $bagian[] = $tglStr . ' ' . $namaBulanPdf[(int) $m] . ' ' . $y;
                    }
                    if (!empty($bagian)) {
                        $rinciAlpha = ', yaitu pada tanggal ' . implode('; ', $bagian);
                    }
                }
                ?>
            </strong>, Saudara tercatat tidak hadir tanpa keterangan (Alpha) selama <strong>
                <?= $jumlah_alpha ?> hari<?= $rinciAlpha ?>
            </strong>.
        </p>

        <p>
            Sesuai dengan peraturan kedisiplinan kepegawaian, dengan ini kami sampaikan <strong>TEGURAN TERTULIS
                <?= $tingkat_teguran ?>
            </strong>.
            Kami mengharapkan Saudara untuk meningkatkan kedisiplinan dan kehadiran di masa mendatang. Ketidakhadiran
            lebih lanjut akan berdampak pada tindakan administratif yang lebih tegas sesuai peraturan yang berlaku.
        </p>

        <p>Demikian surat teguran ini dibuat untuk menjadi perhatian dan dilaksanakan sebagaimana mestinya.</p>
    </div>

    <div class="footer">
        <div>
            <?= $setting->kota ?? 'Tempat' ?>,
            <?php
            // Tanggal surat = tanggal terbit (created_at baris surat), BUKAN
            // tanggal cetak. Arsip yang dicetak ulang harus tetap bertanggal
            // saat diterbitkan. Prioritas: $tanggal_terbit eksplisit dari
            // controller, lalu created_at baris surat (jalur admin/kepsek
            // mengoper baris surat sebagai $guru), terakhir hari ini.
            $tsTerbit = false;
            $kandidat = $tanggal_terbit ?? null;
            if (empty($kandidat) && !empty($guru->created_at ?? null)) {
                $kandidat = $guru->created_at;
            }
            if (!empty($kandidat)) {
                $tsTerbit = strtotime((string) $kandidat);
            }
            if ($tsTerbit === false) {
                $tsTerbit = time();
            }
            $tgl = date('d', $tsTerbit);
            $bln = $bulanIndo[date('F', $tsTerbit)];
            $thn = date('Y', $tsTerbit);
            echo "$tgl $bln $thn";
            ?>
        </div>
        <div>Kepala Sekolah,</div>
        <?php if (!empty($tte_qr)): ?>
            <div style="margin: 10px 0 4px;">
                <img src="<?= $tte_qr ?>" style="width: 110px; height: 110px;">
            </div>
            <div style="font-size: 9pt; font-style: italic;">Ditandatangani secara elektronik</div>
        <?php endif; ?>
        <div class="ttd" style="<?= !empty($tte_qr) ? 'margin-top: 6px;' : '' ?>">
            <u>
                <?= $setting->kepsek ?? '..........................' ?>
            </u><br>
            NIP.
            <?= esc($setting->nip ?? '..........................') ?>
        </div>
        <?php if (!empty($tte_kode)): ?>
            <div style="font-size: 8pt; color: #444; margin-top: 4px;">Kode verifikasi: <?= $tte_kode ?></div>
        <?php endif; ?>
    </div>

    <div
        style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 8pt; color: #555; border-top: 1px solid #ccc; padding-top: 5px;">
        Dokumen ini dibuat secara otomatis oleh sistem. Hubungi administrator apabila terdapat ketidaksesuaian.
    </div>
</body>

</html>