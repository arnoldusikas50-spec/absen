<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Absen Guru - <?= esc($judul_cetak) ?> - <?= esc($setting->sekolah ?? 'Sekolah') ?></title>
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
            background-color: var(--accent-600);
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: var(--accent-700);
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

        /* Grid Cards: 2 Columns on A4 for Landscape */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5mm;
        }
        /* Grid Cards: 3 Columns on A4 for Portrait */
        .card-grid-portrait {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5mm;
            justify-items: center;
        }

        /* Single Mode */
        .single-mode {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        /* Individual Card Box Landscape */
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

        /* Individual Card Box Portrait */
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
            background: linear-gradient(180deg, #6b21a8 0%, #581c87 100%);
            color: #ffffff;
            padding: 2.5mm 3mm 2mm 3mm;
            text-align: center;
            border-bottom: 2px solid #a855f7;
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
            margin: 0 auto 1.5mm auto;
            box-shadow: 0 1px 2px rgba(0,0,0,0.15);
        }
        .portrait-logo img {
            max-width: 100%;
            max-height: 100%;
            object-contain: contain;
        }
        .portrait-sub {
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #e9d5ff;
            text-transform: uppercase;
            margin: 0;
        }
        .portrait-school {
            font-size: 7.5pt;
            font-weight: 800;
            color: #ffffff;
            margin: 0.5mm 0;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .portrait-badge {
            display: inline-block;
            background: rgba(255,255,255,0.22);
            border: 1px solid rgba(255,255,255,0.4);
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: 800;
            padding: 0.5mm 2.5mm;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .portrait-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-evenly;
            padding: 2mm 3mm;
            background: #ffffff;
        }
        .portrait-qr {
            width: 38mm;
            height: 38mm;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 1.5mm;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portrait-qr canvas, .portrait-qr img {
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
            margin: 0 auto;
        }
        .portrait-name {
            font-size: 8pt;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
            line-height: 1.15;
            margin: 0;
            max-width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .portrait-meta {
            font-size: 6.5pt;
            color: #475569;
            text-align: center;
            margin: 0.5mm 0 0 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .portrait-footer {
            background: #faf5ff;
            border-top: 1px solid #f3e8ff;
            padding: 1.5mm 3mm;
            text-align: center;
            font-size: 5.5pt;
            font-weight: 700;
            color: var(--accent-600);
            letter-spacing: 0.4px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* Landscape Card Layout */
        .card-header-bar {
            background: linear-gradient(135deg, #581c87 0%, #6b21a8 50%, var(--accent-600) 100%);
            color: #ffffff;
            padding: 2mm 3mm;
            display: flex;
            align-items: center;
            gap: 2.5mm;
            border-bottom: 2px solid #a855f7;
            flex-shrink: 0;
        }
        .card-logo {
            width: 8mm;
            height: 8mm;
            background: #ffffff;
            border-radius: 5px;
            padding: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .card-logo img {
            max-width: 100%;
            max-height: 100%;
            object-contain: contain;
        }
        .card-header-text {
            flex: 1;
            min-width: 0;
        }
        .card-header-text .app-title {
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #e9d5ff;
            text-transform: uppercase;
            margin: 0;
            line-height: 1;
        }
        .card-header-text .school-title {
            font-size: 8pt;
            font-weight: 800;
            color: #ffffff;
            margin: 0.5mm 0 0 0;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-header-badge {
            background: rgba(255,255,255,0.22);
            border: 1px solid rgba(255,255,255,0.4);
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: 800;
            padding: 0.6mm 2mm;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .card-content {
            flex: 1;
            padding: 2.5mm 3mm;
            display: flex;
            align-items: center;
            gap: 3mm;
            background: #ffffff;
        }
        .card-photo-box {
            width: 18mm;
            height: 23mm;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-details {
            flex: 1;
            min-width: 0;
        }
        .card-name {
            font-size: 8.5pt;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            margin: 0 0 1.5mm 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-table {
            font-size: 6.5pt;
            border-collapse: collapse;
            width: 100%;
        }
        .card-table td {
            padding: 0.4mm 0;
            vertical-align: top;
        }
        .card-table .label {
            color: #64748b;
            font-weight: 700;
            width: 14mm;
        }
        .card-table .sep {
            color: #94a3b8;
            width: 2mm;
            text-align: center;
        }
        .card-table .val {
            color: #0f172a;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .card-qr-box {
            width: 21mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 1px solid #e2e8f0;
            padding-left: 2.5mm;
            flex-shrink: 0;
        }
        .card-qr-render {
            width: 18mm;
            height: 18mm;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 1px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-qr-render canvas, .card-qr-render img {
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
            margin: 0 auto;
        }
        .card-qr-code {
            font-size: 5pt;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1mm;
            line-height: 1.1;
            max-width: 22mm;
            word-break: break-all;
            letter-spacing: -0.2px;
            text-align: center;
        }
        .card-qr-label {
            font-size: 4.8pt;
            font-weight: 700;
            color: var(--accent-600);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 0.4mm;
            line-height: 1;
        }
        .card-footer-bar {
            background: #faf5ff;
            border-top: 1px solid #f3e8ff;
            padding: 1mm 3mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5.5pt;
            color: #6b21a8;
            flex-shrink: 0;
        }
        .card-footer-bar .badge-instansi {
            font-weight: 700;
            color: #581c87;
        }

        /* Print Media Style */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .top-bar {
                display: none !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                padding: 4mm !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            .card-grid {
                gap: 4mm !important;
            }
            .card-grid-portrait {
                gap: 4mm !important;
            }
            .id-card {
                border: 0.5px dashed #64748b !important;
            }
            .id-card-portrait {
                border: 0.5px dashed #64748b !important;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Screen Only) -->
    <div class="top-bar">
        <div class="top-bar-info">
            <strong>Cetak Kartu Absen Guru / GTK QR Code</strong> • <?= count($guru_list) ?> Kartu Guru • Mode <?= ucfirst($desain) ?>
            <?php if (!empty($terpotong)): ?>
                <span style="font-size: 11px; color: #b45309; margin-left: 8px;">
                    ⚠ Daftar dipotong pada <?= (int) ($batas_cetak ?? 500) ?> kartu pertama — cetak sisanya dengan memilih satu guru.
                </span>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer-fill"></i> Cetak / Print Sekarang
            </button>
            <a href="<?= site_url('admin/presensi/guru/kartu?desain=' . $desain) ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Preview
            </a>
        </div>
    </div>

    <!-- Sheet Container -->
    <div class="sheet-container">
        <?php if (empty($guru_list)): ?>
            <div class="no-print" style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;">
                <p style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0;">Tidak ada kartu guru untuk dicetak</p>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px 0;">
                    <?php if (!empty($catatan)): ?>
                        <?= esc($catatan) ?>
                    <?php else: ?>
                        Guru yang dipilih tidak ditemukan pada master data guru. Periksa kembali filternya di halaman preview.
                    <?php endif; ?>
                </p>
                <a href="<?= site_url('admin/presensi/guru/kartu') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Preview</a>
            </div>
        <?php endif; ?>

        <?php if ($desain === 'portrait'): ?>
            <!-- Mode Cetak Portrait (3 Kolom per Lembar A4) -->
            <div class="<?= $is_single ? 'single-mode' : 'card-grid-portrait' ?>">
                <?php foreach ($guru_list as $g): ?>
                    <div class="id-card-portrait">
                        <!-- Kop Kartu -->
                        <div class="portrait-header">
                            <div class="portrait-logo">
                                <img src="<?= $logo_url ?>" alt="Logo" onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                            </div>
                            <p class="portrait-sub">KARTU PRESENSI DIGITAL</p>
                            <h4 class="portrait-school"><?= esc($setting->sekolah ?? 'SEKOLAH') ?></h4>
                            <span class="portrait-badge">GURU / GTK</span>
                        </div>

                        <!-- Body & Large QR -->
                        <div class="portrait-body">
                            <div class="portrait-qr qr-box" data-code="<?= esc($g->qr_value) ?>" data-size="130"></div>
                            <div>
                                <h5 class="portrait-name" title="<?= esc($g->nama_guru) ?>"><?= esc($g->nama_guru) ?></h5>
                                <p class="portrait-meta">NIP: <?= esc($g->nip ?: ($g->kode_guru ?: '-')) ?></p>
                                <p class="portrait-meta" style="color:var(--accent-600);font-weight:700;"><?= esc($g->jabatan) ?></p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="portrait-footer">
                            SCAN DI KIOSK SCANNER PRESENSI
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Mode Cetak Landscape (2 Kolom per Lembar A4) -->
            <div class="<?= $is_single ? 'single-mode' : 'card-grid' ?>">
                <?php foreach ($guru_list as $g): ?>
                    <div class="id-card">
                        <!-- Kop Kartu -->
                        <div class="card-header-bar">
                            <div class="card-logo">
                                <img src="<?= $logo_url ?>" alt="Logo" onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                            </div>
                            <div class="card-header-text">
                                <p class="app-title">KARTU PRESENSI DIGITAL GURU / GTK</p>
                                <h4 class="school-title"><?= esc($setting->sekolah ?? 'SEKOLAH') ?></h4>
                            </div>
                            <div class="card-header-badge">
                                <?= esc($g->status_pegawai ?: 'GTK') ?>
                            </div>
                        </div>

                        <!-- Isi Kartu -->
                        <div class="card-content">
                            <!-- Foto -->
                            <div class="card-photo-box">
                                <img src="<?= esc($g->foto_url, 'attr') ?>" alt="<?= esc($g->nama_guru) ?>"
                                     onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::inisial($g->nama_guru, 128, '#7c3aed'), 'attr') ?>'">
                            </div>

                            <!-- Identitas -->
                            <div class="card-details">
                                <h5 class="card-name" title="<?= esc($g->nama_guru) ?>"><?= esc($g->nama_guru) ?></h5>
                                <table class="card-table">
                                    <tr>
                                        <td class="label">NIP</td>
                                        <td class="sep">:</td>
                                        <td class="val"><?= esc($g->nip ?: '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">NUPTK</td>
                                        <td class="sep">:</td>
                                        <td class="val"><?= esc($g->nuptk ?: '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label">TUGAS</td>
                                        <td class="sep">:</td>
                                        <td class="val" style="font-family:inherit;font-weight:700;color:#6b21a8;"><?= esc($g->jabatan) ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- QR Code -->
                            <div class="card-qr-box">
                                <div class="card-qr-render qr-box" data-code="<?= esc($g->qr_value) ?>" data-size="64"></div>
                                <div class="card-qr-code"><?= esc($g->qr_value) ?></div>
                                <div class="card-qr-label">Scan Absen</div>
                            </div>
                        </div>

                        <!-- Footer Kartu -->
                        <div class="card-footer-bar">
                            <span class="badge-instansi"><?= esc($setting->sekolah ?? 'Sekolah') ?></span>
                            <span>GTK PRESENSI MANDIRI</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Script Library QRCode -->
    <script src="<?= base_url('assets/app/js/qrcode.min.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var boxes = document.querySelectorAll('.qr-box');
            boxes.forEach(function(box) {
                var code = box.getAttribute('data-code');
                var size = parseInt(box.getAttribute('data-size') || '64', 10);
                if (code) {
                    try {
                        new QRCode(box, {
                            text: code,
                            width: size,
                            height: size,
                            colorDark: "#0f172a",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (e) {
                        box.innerHTML = '<span style="font-size:7px;font-weight:bold;">' + code + '</span>';
                    }
                }
            });
        });
    </script>
</body>
</html>
