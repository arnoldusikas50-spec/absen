<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Surat Panggilan Orang Tua</title>
    <style>
        /*
         * Dompdf mengabaikan Tailwind CDN, jadi semua styling efektifnya
         * harus di sini sebagai CSS inline. Menyalin kelas utility dari
         * halaman web tidak akan bekerja.
         */
        /* Satu lembar A4 tegak: semua konten harus muat 1 halaman. */
        @page {
            size: A4 portrait;
            margin: 1.2cm 1.5cm 1.5cm 1.5cm;
        }

        body {
            font-family: serif;
            font-size: 11pt;
            margin: 0;
        }

        p {
            margin: 6px 0;
        }

        .kop {
            text-align: center;
            margin-bottom: 8px;
        }

        .kop img {
            max-height: 2.6cm;
        }

        .isi {
            margin-top: 10px;
            line-height: 1.45;
            text-align: justify;
        }

        .ttd {
            margin-top: 24px;
            text-align: right;
            page-break-inside: avoid;
        }

        table.data td {
            padding: 1px 0;
        }

        .kotak {
            border: 1px solid #444;
            padding: 7px 10px;
            margin: 8px 0;
            page-break-inside: avoid;
        }

        .footer-tetap {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <?php
    $alamat = (string) ($setting->alamat ?? '');
    $bagian = [];
    foreach (['kelurahan' => 'Kel. ', 'kecamatan' => 'Kec. ', 'kabupaten' => '', 'provinsi' => ''] as $k => $prefix) {
        if (!empty($setting->$k)) {
            $bagian[] = $prefix . $setting->$k;
        }
    }
    $alamatLengkap = $alamat . ($alamat && $bagian ? ', ' : '') . implode(', ', $bagian);

    $kontak = [];
    if (!empty($setting->telp)) {
        $kontak[] = 'Telp: ' . $setting->telp;
    }
    if (!empty($setting->email)) {
        $kontak[] = 'Email: ' . $setting->email;
    }
    if (!empty($setting->website)) {
        $kontak[] = 'Web: ' . $setting->website;
    }

    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $periodeBulan = (int) ($periode_bulan ?? date('n'));
    $periodeTahun = (int) ($periode_tahun ?? date('Y'));
    if ($periodeBulan < 1 || $periodeBulan > 12) {
        $periodeBulan = (int) date('n');
    }
    $periodeNama = $namaBulan[$periodeBulan] . ' ' . $periodeTahun;

    $namaSekolah = (string) ($setting->nama_sekolah ?? 'Sekolah');
    $penerima    = (string) ($penerima ?? '');
    $nohp        = (string) ($nohp_penerima ?? '');
    ?>

    <?php if (!empty($kop_base64)): ?>
    <div class="kop">
        <img src="<?= $kop_base64 ?>" style="width: 100%; height: auto;">
    </div>
    <?php else: ?>
    <div style="border-bottom: 3px double black; padding-bottom: 8px; margin-bottom: 10px;">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="15%" align="center" valign="middle">
                    <?php if (!empty($logo_base64)): ?>
                        <img src="<?= $logo_base64 ?>" style="width: 80px; height: auto;">
                    <?php endif; ?>
                </td>
                <td width="85%" align="center" valign="middle">
                    <?php foreach (['header_1', 'header_2', 'header_3', 'header_4'] as $hk): ?>
                        <?php if (!empty($setting->$hk)): ?>
                            <div style="font-size: 11pt;"><?= strtoupper((string) $setting->$hk) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div style="font-size: 14pt; font-weight: bold; margin: 5px 0;"><?= strtoupper($namaSekolah) ?></div>
                    <div style="font-size: 9pt;"><?= $alamatLengkap ?></div>
                    <div style="font-size: 9pt;"><?= implode(' | ', $kontak) ?></div>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 8px;">
        <div style="font-size: 13pt; font-weight: bold; text-decoration: underline;">SURAT PANGGILAN ORANG TUA</div>
        <div style="font-size: 11pt;">Nomor: <?= esc($nomor_surat) ?></div>
    </div>

    <div class="isi">
        <p>Yth.<br>
        Bapak/Ibu Orang Tua/Wali Murid<br>
        di<br>
        Tempat</p>

        <p>Dengan hormat,</p>

        <p>
            Berdasarkan hasil rekapitulasi kehadiran peserta didik pada bulan
            <strong><?= esc($periodeNama) ?></strong>, dengan ini kami sampaikan
            data kehadiran putra/putri Bapak/Ibu, yaitu:
        </p>

        <table class="data" style="margin-left: 20px;">
            <tr>
                <td width="130">Nama</td>
                <td>: <strong><?= esc($siswa->nama ?? '-') ?></strong></td>
            </tr>
            <tr>
                <td>NIS / NISN</td>
                <td>: <?= esc($siswa->nis ?? '-') ?> / <?= esc($siswa->nisn ?? '-') ?></td>
            </tr>
            <?php if (!empty($siswa->kelas_awal)): ?>
            <tr>
                <td>Kelas</td>
                <td>: <?= esc($siswa->kelas_awal) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="kotak">
            <div><strong>Rekapitulasi kehadiran:</strong></div>
            <table class="data" style="width: 70%; margin-left: 10px;">
                <tr>
                    <td width="200">Tidak hadir tanpa keterangan (alpha)</td>
                    <td>: <strong><?= (int) $jumlah_alpha ?></strong> hari</td>
                </tr>
                <tr>
                    <td>Terlambat masuk</td>
                    <td>: <strong><?= (int) $jumlah_terlambat ?></strong> kali</td>
                </tr>
                <tr>
                    <td>Tingkat panggilan</td>
                    <td>: <strong><?= esc($label_tingkat) ?></strong></td>
                </tr>
            </table>
        </div>

        <p>
            Berdasarkan data tersebut, putra/putri Bapak/Ibu tercatat tidak
            hadir tanpa keterangan (alpha) sebanyak
            <strong><?= (int) $jumlah_alpha ?> hari</strong>.
        </p>

        <p>
            Sehubungan dengan hal tersebut, kami memanggil Bapak/Ibu untuk
            hadir ke sekolah dalam rangka <strong><?= esc($label_tingkat) ?></strong>
            guna bersama-sama meningkatkan kedisiplinan dan kehadiran
            putra/putri Bapak/Ibu, karena kehadiran di sekolah sangat
            berpengaruh terhadap keberhasilan studinya.
        </p>

        <p>
            Apabila Bapak/Ibu berhalangan hadir, mohon menghubungi wali kelas
            untuk berkoordinasi.
        </p>

        <?php if (!empty($letter->keterangan)): ?>
        <p><em>Catatan: <?= esc($letter->keterangan) ?></em></p>
        <?php endif; ?>

        <p>Demikian surat panggilan ini disampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami mengucapkan terima kasih.</p>
    </div>

    <div class="ttd">
        <div>
            <?= esc($setting->kota ?? 'Tempat') ?>,
            <?= date('d') ?> <?= $namaBulan[(int) date('n')] ?> <?= date('Y') ?>
        </div>
        <div>Kepala Sekolah,</div>
        <?php if (!empty($tte_qr)): ?>
            <div style="margin: 6px 0 2px;">
                <img src="<?= $tte_qr ?>" style="width: 88px; height: 88px;">
            </div>
            <div style="font-size: 9pt; font-style: italic;">Ditandatangani secara elektronik</div>
        <?php endif; ?>
        <div class="ttd" style="<?= !empty($tte_qr) ? 'margin-top: 6px;' : '' ?>">
            <u><?= esc($setting->kepsek ?? '..........................') ?></u><br>
            NIP. <?= esc($setting->nip ?? '..........................') ?>
        </div>
        <?php if (!empty($tte_kode)): ?>
            <div style="font-size: 8pt; color: #444; margin-top: 4px;">Kode verifikasi: <?= esc($tte_kode) ?></div>
        <?php endif; ?>
    </div>

    <div class="footer-tetap">
        Dokumen ini dibuat secara otomatis oleh sistem. Hubungi administrator apabila terdapat ketidaksesuaian.
    </div>
</body>

</html>