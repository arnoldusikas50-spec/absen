<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Absen Siswa - <?= esc($nama_kelas) ?> - <?= esc($setting->sekolah ?? 'Sekolah') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?= \App\Libraries\Theme::styleTag() ?>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        /* Top Action Bar (Screen Only) */
        .top-bar {
            max-width: 1000px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid #e2e8f0;
        }
        .top-bar-info {
            font-size: 13px;
            color: #475569;
        }
        .top-bar-info strong {
            color: #0f172a;
        }
        .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background-color: var(--primary-600);
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: var(--primary-700);
        }
        .btn-secondary {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        /* Sheet Container (A4 Printable Area) */
        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 6mm;
        }

        /* Grid Cards: 2 Columns on A4 */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5mm;
        }
        .card-grid-portrait {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5mm;
            justify-items: center;
        }

        /* Single Student Mode */
        .single-mode {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        /* Individual Card Box */
        .id-card {
            width: 92mm;
            height: 58mm;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Portrait Card Box: vertikal 58 x 92mm, tanpa foto */
        .id-card-portrait {
            width: 58mm;
            height: 92mm;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .portrait-header {
            background: linear-gradient(180deg, var(--primary-800) 0%, var(--primary-900) 100%);
            color: #ffffff;
            padding: 2.5mm 3mm 2mm 3mm;
            text-align: center;
            border-bottom: 2px solid var(--primary-500);
            flex-shrink: 0;
        }
        .portrait-logo {
            width: 8mm;
            height: 8mm;
            background: #ffffff;
            border-radius: 5px;
            padding: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .portrait-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .portrait-title { font-size: 6.5pt; font-weight: 800; text-transform: uppercase; margin: 1.2mm 0 0 0; line-height: 1.15; }
        .portrait-subtitle { font-size: 5pt; color: var(--primary-200); margin: 0.5mm 0 0 0; }
        .portrait-badge {
            display: inline-block;
            margin-top: 1mm;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            padding: 0.5px 6px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
        }
        .portrait-qr {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 2mm 2mm 1mm 2mm;
            background: #fff;
            overflow: hidden;
        }
        .portrait-qr .portrait-qr-box { width: 40mm !important; height: 40mm !important; min-height: 40mm; padding: 2mm; background: #fff; border: 1px solid #e2e8f0; border-radius: 2mm; display: flex; align-items: center; justify-content: center; box-sizing: border-box; }
        .portrait-qr .portrait-qr-box canvas, .portrait-qr .portrait-qr-box img, .portrait-qr .portrait-qr-box table { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; display: block; image-rendering: pixelated; }
        .portrait-code { font-size: 7.5pt; font-weight: 800; color: #0f172a; font-family: monospace; margin-top: 1mm; }
        .portrait-scan { font-size: 4.8pt; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .portrait-name { font-size: 7.5pt; font-weight: 800; color: #0f172a; text-transform: uppercase; text-align: center; margin: 0; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .portrait-meta { font-size: 5.5pt; color: #475569; text-align: center; margin: 0.5mm 0 0 0; font-family: monospace; }
        .portrait-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 1.5mm 3mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5pt;
            color: #64748b;
        }

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, var(--primary-800) 0%, var(--primary-900) 100%);
            color: #ffffff;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 15mm;
            border-bottom: 2px solid var(--primary-500);
        }
        .card-logo {
            width: 11mm;
            height: 11mm;
            background: #ffffff;
            border-radius: 6px;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .card-header-text {
            flex: 1;
            min-width: 0;
        }
        .card-header-title {
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }
        .card-header-subtitle {
            font-size: 5.8pt;
            color: var(--primary-200);
            margin: 1px 0 0 0;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-class-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* Card Body */
        .card-body {
            flex: 1;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
        }
        .photo-container {
            width: 17mm;
            height: 22mm;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background: #f1f5f9;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-details {
            flex: 1;
            min-width: 0;
        }
        .student-name {
            font-size: 8pt;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            line-height: 1.15;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-transform: uppercase;
        }
        .info-table {
            font-size: 6pt;
            line-height: 1.35;
            color: #334155;
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 0.5px 0;
            vertical-align: middle;
        }
        .info-table .label {
            width: 12mm;
            font-weight: 700;
            color: #64748b;
        }
        .info-table .val {
            font-weight: 600;
            color: #0f172a;
        }

        /* QR Code Container */
        .qr-container {
            width: 22mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-left: 1px dotted #e2e8f0;
            padding-left: 5px;
        }
        .qr-print-box {
            width: 19mm;
            height: 19mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-print-box canvas, .qr-print-box img {
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
        }
        .qr-caption {
            font-size: 4.8pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
            letter-spacing: 0.2px;
        }

        /* Card Footer */
        .card-footer {
            height: 5.5mm;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 0 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5pt;
            color: #64748b;
        }
        .card-footer-left {
            font-weight: 600;
            color: #475569;
        }

        /* Print Media Queries */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }
            .card-grid {
                gap: 3.5mm !important;
            }
            .card-grid-portrait {
                gap: 3.5mm !important;
            }
            .id-card, .id-card-portrait {
                border: 1px dashed #94a3b8 !important;
                box-shadow: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 7mm 6mm;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Screen Only) -->
    <?php
    $desainAktif = $desain ?? 'landscape';
    $qKelas = (int) ($_GET['id_kelas'] ?? 0);
    $qSiswa = (int) ($_GET['id_siswa'] ?? 0);
    $baseQs = 'id_kelas=' . $qKelas . ($qSiswa ? '&id_siswa=' . $qSiswa : '');
    $curQs  = $_SERVER['QUERY_STRING'] ?? '';
    ?>
    <div class="top-bar no-print">
        <div class="top-bar-info">
            <i class="bi bi-printer-fill text-primary"></i>
            Siap Cetak: <strong><?= count($siswa_list) ?> Kartu</strong> (<?= esc($nama_kelas) ?>)
            <?php if (!empty($terpotong)): ?>
                <span style="font-size: 11px; color: #b45309; margin-left: 8px;">
                    ⚠ Daftar dipotong pada <?= (int) ($batas_cetak ?? 500) ?> kartu pertama — cetak sisanya dengan memilih kelas/siswa yang lebih kecil.
                </span>
            <?php endif; ?>
            <span style="margin-left: 8px; color: #94a3b8;">|</span>
            <span style="display:inline-flex;gap:6px;margin-left:8px;vertical-align:middle;">
                <a href="?<?= $baseQs ?>&desain=landscape" class="btn <?= $desainAktif === 'landscape' ? 'btn-primary' : 'btn-secondary' ?>" style="<?= $desainAktif === 'landscape' ? '' : 'background:#fff;' ?>">
                    <i class="bi bi-credit-card-2-front"></i> Landscape + Foto
                </a>
                <a href="?<?= $baseQs ?>&desain=portrait" class="btn <?= $desainAktif === 'portrait' ? 'btn-primary' : 'btn-secondary' ?>" style="<?= $desainAktif === 'portrait' ? '' : 'background:#fff;' ?>">
                    <i class="bi bi-credit-card"></i> Portrait − Foto
                </a>
            </span>
            <span style="font-size: 11px; color: #64748b; margin-left: 8px;">
                💡 Tips: Centang <strong>"Background graphics"</strong> pada dialog print browser agar warna kartu tercetak penuh.
            </span>
        </div>
        <div class="btn-group">
            <button onclick="window.history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Cetak Sekarang
            </button>
        </div>
    </div>

    <!-- Sheet Container -->
    <div class="sheet-container">
        <?php if (empty($siswa_list)): ?>
            <div class="no-print" style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;">
                <p style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0;">Tidak ada kartu untuk dicetak</p>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px 0;">
                    <?php if (!empty($catatan)): ?>
                        <?= esc($catatan) ?>
                    <?php else: ?>
                        Pilih kelas / siswa lain pada halaman preview kartu, lalu buka kembali halaman cetak ini.
                    <?php endif; ?>
                </p>
                <button onclick="window.history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Preview</button>
            </div>
        <?php endif; ?>
        <?php if ($desainAktif === 'portrait'): ?>
        <div class="<?= $is_single ? 'single-mode' : 'card-grid-portrait' ?>">
            <?php foreach ($siswa_list as $s): ?>
                <div class="id-card-portrait">
                    <div class="portrait-header">
                        <div class="portrait-logo">
                            <img src="<?= $logo_url ?>" alt="Logo" onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                        </div>
                        <h4 class="portrait-title"><?= esc($setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR') ?></h4>
                        <p class="portrait-subtitle">KARTU PRESENSI DIGITAL • TP <?= esc($tp_active->tahun ?? '-') ?></p>
                        <span class="portrait-badge"><?= esc($s->nama_kelas) ?></span>
                    </div>
                    <div class="portrait-qr">
                        <div class="portrait-qr-box qr-print-box" data-code="<?= esc($s->qr_value) ?>" data-size="200"></div>
                        <span class="portrait-code"><?= esc($s->qr_value) ?></span>
                        <span class="portrait-scan">Scans Absen</span>
                        <p class="portrait-name" style="margin-top:2mm;" title="<?= esc($s->nama) ?>"><?= esc($s->nama) ?></p>
                        <p class="portrait-meta">NIS <?= esc($s->nis ?: '-') ?> • NISN <?= esc($s->nisn ?: '-') ?></p>
                    </div>
                    <div class="portrait-footer">
                        <span>Wajib dibawa KBM / Harian</span>
                        <span><?= esc($setting->kota ?? 'Kab. Kupang') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="<?= $is_single ? 'single-mode' : 'card-grid' ?>">
            <?php foreach ($siswa_list as $s): ?>
                <div class="id-card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="card-logo">
                            <img src="<?= $logo_url ?>" alt="Logo" onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                        </div>
                        <div class="card-header-text">
                            <h4 class="card-header-title"><?= esc($setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR') ?></h4>
                            <p class="card-header-subtitle">KARTU PRESENSI DIGITAL • TP <?= esc($tp_active->tahun ?? '-') ?></p>
                        </div>
                        <div class="card-class-badge">
                            <?= esc($s->nama_kelas) ?>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body">
                        <!-- Foto Siswa -->
                        <div class="photo-container">
                            <img src="<?= $s->foto_url ?>" alt="<?= esc($s->nama) ?>"
                                 onerror="this.src='<?= base_url('assets/img/siswa.png') ?>'">
                        </div>

                        <!-- Data Siswa -->
                        <div class="student-details">
                            <div class="student-name" title="<?= esc($s->nama) ?>"><?= esc($s->nama) ?></div>
                            <table class="info-table">
                                <tr>
                                    <td class="label">NIS</td>
                                    <td class="val">: <strong><?= esc($s->nis ?: '-') ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="label">NISN</td>
                                    <td class="val">: <?= esc($s->nisn ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="label">KELAS</td>
                                    <td class="val">: <?= esc($s->nama_kelas) ?></td>
                                </tr>
                                <tr>
                                    <td class="label">JK</td>
                                    <td class="val">: <?= ($s->jenis_kelamin == 'L') ? 'Laki-laki' : 'Perempuan' ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- QR Code -->
                        <div class="qr-container">
                            <div class="qr-print-box" data-code="<?= esc($s->qr_value) ?>"></div>
                            <span class="qr-caption font-mono" style="font-size:6pt;color:#0f172a;"><?= esc($s->qr_value) ?></span>
                            <span class="qr-caption">SCAN ABSEN</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer">
                        <span class="card-footer-left">Wajib dibawa saat KBM / Presensi Harian</span>
                        <span><?= esc($setting->kota ?? 'Kab. Kupang') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- QR Code Script -->
    <script src="<?= base_url('assets/app/js/qrcode.min.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qrBoxes = document.querySelectorAll('.qr-print-box');
            qrBoxes.forEach(function(box) {
                const code = box.getAttribute('data-code');
                const size = parseInt(box.getAttribute('data-size') || '68', 10) || 68;
                if (code) {
                    try {
                        new QRCode(box, {
                            text: String(code),
                            width: size,
                            height: size,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (e) {
                        box.textContent = code;
                        box.style.fontSize = '7pt';
                        box.style.fontWeight = '700';
                    }
                } else {
                    box.textContent = '-';
                    box.style.fontSize = '7pt';
                    box.style.color = '#94a3b8';
                }
            });
        });
    </script>
</body>
</html>
