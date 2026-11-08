<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>QR Presensi — <?= $qr ? esc($qr->nama_acara) : 'Tidak Valid' ?></title>
    <link href="<?= base_url('assets/plugins/bootstrap-icon/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/app/css/tailwind.css') ?>?v=<?= @filemtime(FCPATH . 'assets/app/css/tailwind.css') ?>">
    <style type="text/css">
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .qr-card {
            max-width: 400px;
            width: 100%;
            background: #fff;
            border-radius: 24px;
            padding: 28px 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }
        .qr-icon {
            width: 72px; height: 72px;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin: 0 auto 12px;
        }
        .step-row {
            display: flex; align-items: center; gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 12px;
            font-size: 12px;
        }
        .step-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .btn-scan:active { transform: scale(.97); }
        .btn-scan:disabled { opacity: .6; }
        .spin {
            width: 18px; height: 18px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,.4); border-top-color: #fff;
            animation: spin .7s linear infinite; display: inline-block; vertical-align: -3px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    <?= \App\Libraries\Theme::styleTag() ?>
</head>
<body>
    <?php
    $mode = $mode ?? 'checkin';
    $modeLabel = $mode === 'checkout' ? 'Absen Pulang' : ($mode === 'done' ? 'Absen Selesai' : 'Absen Masuk');
    $modeBadge = $mode === 'checkout'
        ? 'bg-amber-50 text-amber-700 border-amber-200'
        : ($mode === 'done' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200');
    $modeIcon = $mode === 'checkout' ? 'bi-box-arrow-right' : ($mode === 'done' ? 'bi-check2-circle' : 'bi-box-arrow-in-right');
    ?>
    <div class="qr-card">
        <div class="qr-icon <?= $qr ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' ?>">
            <i class="bi <?= $qr ? 'bi-qr-code-scan' : 'bi-x-circle-fill' ?>"></i>
        </div>
        <h1 class="text-center text-lg font-extrabold text-slate-900 leading-snug"><?= $qr ? esc($qr->nama_acara) : 'QR Tidak Valid' ?></h1>
        <?php if ($qr): ?>
        <p class="text-center text-xs text-slate-500 mt-1">
            <?= date('d/m/Y', strtotime($qr->tanggal)) ?> &middot; <?= substr($qr->jam_mulai, 0, 5) ?> - <?= substr($qr->jam_selesai, 0, 5) ?>
        </p>
        <?php if (!empty($qr->lokasi)): ?>
        <p class="text-center text-xs text-slate-500 mt-0.5"><i class="bi bi-geo-alt"></i> <?= esc($qr->lokasi) ?></p>
        <?php endif; ?>

        <div class="mt-3 flex justify-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-[11px] font-bold <?= $modeBadge ?>">
                <i class="bi <?= $modeIcon ?>"></i> <?= esc($modeLabel) ?>
            </span>
        </div>

        <?php if ($mode === 'checkout' && !empty($today_attendance) && !empty($today_attendance->jam_masuk)): ?>
        <p class="text-center text-[11px] text-slate-500 mt-2">
            Masuk tercatat pukul <?= date('H:i', strtotime($today_attendance->jam_masuk)) ?>. Jurnal sesi yang sudah dimulai wajib lengkap sebelum pulang.
        </p>
        <?php elseif ($mode === 'done'): ?>
        <p class="text-center text-[11px] text-emerald-600 mt-2">
            Absen masuk dan pulang hari ini sudah tercatat. Scan ini tidak mengubah data.
        </p>
        <?php endif; ?>

        <!-- Langkah 1: GPS -->
        <div class="step-row mt-4">
            <span class="step-dot bg-amber-400" id="gps-dot"></span>
            <span class="font-bold text-slate-700" id="gps-text">Menunggu kunci GPS...</span>
        </div>
        <button type="button" id="btnAbsen" class="mt-4 w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-extrabold transition-all shadow-lg shadow-blue-500/25 btn-scan" <?= $mode === 'done' ? 'disabled' : '' ?>>
            <i class="bi <?= $mode === 'done' ? 'bi-check-circle-fill' : 'bi-check-circle-fill' ?>"></i>
            <span id="btnText"><?= $mode === 'checkout' ? 'Absen Pulang' : ($mode === 'done' ? 'Sudah Absen Pulang' : 'Absen Sekarang') ?></span>
        </button>
        <div id="resultArea" class="mt-3"></div>
        <?php else: ?>
        <p class="text-center text-xs text-slate-400 mt-3">QR Code ini sudah tidak aktif, kedaluwarsa, atau tidak valid. Minta QR terbaru kepada panitia.</p>
        <?php endif; ?>
    </div>

    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script>
    $(document).ready(function () {
        const TOKEN = <?= json_encode($token, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const MODE = <?= json_encode($mode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const CHECKIN_URL = '<?= site_url('presensi/qr-checkin') ?>';
        const CHECKOUT_URL = '<?= site_url('presensi/qr-checkout') ?>';
        const CSRF_REFRESH_URL = '<?= site_url('presensi/csrf-refresh') ?>';
        const JURNAL_CREATE_URL = '<?= site_url('guru/jurnal/create') ?>';
        const JURNAL_DETAIL_URL = '<?= site_url('guru/jurnal/detail') ?>';
        const JURNAL_LIST_URL = '<?= site_url('guru/jurnal') ?>';
        const CSRF_NAME = '<?= csrf_token() ?>';
        let CSRF_HASH = '<?= csrf_hash() ?>';

        let gpsFix = null;          // {lat, lng, accuracy}

        function escHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function defaultLabel() {
            if (MODE === 'checkout') return 'Absen Pulang';
            if (MODE === 'done') return 'Sudah Absen Pulang';
            return 'Absen Sekarang';
        }
        function resultBox(ok, msg) {
            $('#resultArea').html(
                '<div class="flex items-start gap-2 px-4 py-3 rounded-2xl text-xs font-bold ' +
                (ok ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200') + '">' +
                '<i class="bi ' + (ok ? 'bi-check-circle-fill' : 'bi-x-circle-fill') + ' mt-0.5"></i>' +
                '<span>' + escHtml(msg) + '</span></div>'
            );
        }
        function setBusy(busy, label) {
            const $btn = $('#btnAbsen');
            $btn.prop('disabled', busy);
            $('#btnText').html(busy ? '<span class="spin"></span> Memproses...' : escHtml(label || defaultLabel()));
        }

        // ── Gerbang jurnal ──
        // Absen pulang ditolak karena jurnal sesi hari ini belum lengkap.
        // Halaman ini tidak memuat SweetAlert, jadi gerbangnya ditulis inline ke
        // #resultArea, tetapi baris & tautannya sengaja disamakan dengan
        // showJurnalGate() di absen_mobile: sesi belum diisi dapat tautan "Isi",
        // sesi yang jurnalnya sudah ada dapat "Lihat"/"Perbaiki" (bukan "Isi",
        // yang akan menawarkan form ganda untuk sesi yang sudah terisi).
        function gerbangJurnal(res) {
            const d = (res && res.data) ? res.data : {};
            const belum = Array.isArray(d.sesi_belum_dijurnal) ? d.sesi_belum_dijurnal : [];
            const menunggu = Array.isArray(d.sesi_menunggu_validasi) ? d.sesi_menunggu_validasi : [];
            if (belum.length === 0 && menunggu.length === 0) return false;
            const petugas = Array.isArray(d.petugas_validasi) ? d.petugas_validasi : [];

            const meta = function (s) {
                const jamKe = String(s.jam_ke || '').trim();
                const dari = String(s.dari || '').trim();
                const sampai = String(s.sampai || '').trim();
                return [jamKe ? 'Jam ' + jamKe : '', sampai ? dari + '–' + sampai : dari].filter(Boolean).join(' · ');
            };
            const namaSesi = function (s) {
                return escHtml((s.nama_kelas || 'Kelas') + ' · ' + (s.nama_mapel || 'Mapel'));
            };
            const gayaBaris = 'display:flex;align-items:center;justify-content:space-between;gap:8px;'
                + 'padding:10px 12px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;margin-bottom:8px;text-decoration:none;';
            const gayaJudul = 'display:block;font-size:13px;font-weight:800;color:#1e293b;'
                + 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
            const labelGrup = function (teks, jumlah, palingAtas) {
                return '<div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;'
                    + 'letter-spacing:.03em;margin:' + (palingAtas ? 2 : 10) + 'px 0 6px;">'
                    + escHtml(teks + ' (' + jumlah + ')') + '</div>';
            };

            const rowIsi = function (s) {
                const idJadwal = parseInt(s.id_jadwal, 10) || 0;
                const href = JURNAL_CREATE_URL + (idJadwal > 0 ? '?id_jadwal=' + idJadwal + '&kembali=absen' : '?kembali=absen');
                const infonya = meta(s);
                return '<a href="' + href + '" style="' + gayaBaris + '"><span style="min-width:0;">'
                    + '<span style="' + gayaJudul + '">' + namaSesi(s) + '</span>'
                    + (infonya ? '<span style="display:block;font-size:11px;color:#64748b;">' + escHtml(infonya) + '</span>' : '')
                    + '</span><span style="flex-shrink:0;font-size:11px;font-weight:800;color:#4f46e5;">'
                    + '<i class="bi bi-pencil-square" style="margin-right:4px;"></i>Isi</span></a>';
            };
            const rowValidasi = function (s) {
                const idJurnal = parseInt(s.id_jurnal, 10) || 0;
                const href = idJurnal > 0 ? JURNAL_DETAIL_URL + '/' + idJurnal : JURNAL_LIST_URL;
                const ditolak = String(s.validasi_status || '').toUpperCase() === 'REJECTED';
                const warna = ditolak ? '#e11d48' : '#d97706';
                const infonya = meta(s);
                return '<a href="' + href + '" style="' + gayaBaris + '"><span style="min-width:0;">'
                    + '<span style="' + gayaJudul + '">' + namaSesi(s) + '</span>'
                    + '<span style="display:block;font-size:11px;font-weight:700;color:' + warna + ';">'
                    + escHtml(ditolak ? 'Jurnal ditolak — perlu diperbaiki' : 'Menunggu validasi piket')
                    + (infonya ? ' · ' + escHtml(infonya) : '') + '</span>'
                    + '</span><span style="flex-shrink:0;font-size:11px;font-weight:800;color:' + warna + ';">'
                    + '<i class="bi bi-eye" style="margin-right:4px;"></i>' + (ditolak ? 'Perbaiki' : 'Lihat') + '</span></a>';
            };

            const judulUtama = menunggu.length > 0 && belum.length === 0
                ? 'Jurnal Menunggu Validasi'
                : 'Jurnal Mengajar Belum Lengkap';
            const subjudul = menunggu.length > 0 && belum.length === 0
                ? 'Jurnal sudah diisi; absen pulang terbuka setelah piket menyetujui.'
                : (menunggu.length > 0
                    ? 'Isi yang belum terisi, lalu minta validasi untuk yang sudah diisi.'
                    : 'Isi jurnal sesi berikut, lalu ulangi absen pulang.');

            const namaPetugas = petugas
                .map(function (p) { return String(p && p.nama ? p.nama : '').trim(); })
                .filter(function (n) { return n !== ''; });
            let catatan;
            if (menunggu.length > 0) {
                catatan = namaPetugas.length > 0
                    ? '<i class="bi bi-person-check"></i> Validasi oleh guru piket hari ini: '
                        + escHtml(namaPetugas.slice(0, 3).join(' / '))
                        + (namaPetugas.length > 3 ? ' dan ' + (namaPetugas.length - 3) + ' guru piket lainnya' : '') + '.'
                    : '<i class="bi bi-exclamation-triangle"></i> Belum ada guru piket terjadwal hari ini sebagai penanggung jawab validasi — hubungi admin presensi.';
            } else {
                catatan = '<i class="bi bi-info-circle"></i> Setelah jurnal tersimpan, kembali ke halaman ini (tombol back) dan ulangi absen pulang.';
            }

            const isi = [];
            if (belum.length > 0) {
                isi.push(labelGrup('Belum diisi', belum.length, true) + belum.map(rowIsi).join(''));
            }
            if (menunggu.length > 0) {
                isi.push(labelGrup('Menunggu validasi', menunggu.length, isi.length === 0) + menunggu.map(rowValidasi).join(''));
            }

            $('#resultArea').html(
                '<div class="px-4 py-3 rounded-2xl bg-amber-50 border border-amber-200">' +
                '<div class="flex items-start gap-2">' +
                '<i class="bi bi-journal-text mt-0.5" style="font-size:16px;color:#b45309;"></i>' +
                '<div style="min-width:0;">' +
                '<div class="text-xs font-extrabold text-amber-700">' + escHtml(judulUtama) + '</div>' +
                '<div style="font-size:11px;font-weight:700;color:#64748b;">' + escHtml(subjudul) + '</div>' +
                '</div></div>' +
                '<div class="mt-2">' + isi.join('') + '</div>' +
                '<div style="font-size:11px;font-weight:600;color:#92400e;">' + catatan + '</div>' +
                '</div>'
            );
            return true;
        }

        // ── GPS: kunci sekali + refresh tiap 30 dtk ──
        function refreshGps() {
            if (!('geolocation' in navigator)) {
                $('#gps-dot').attr('class', 'step-dot bg-red-500');
                $('#gps-text').text('Perangkat tak mendukung GPS');
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                gpsFix = { lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy };
                $('#gps-dot').attr('class', 'step-dot bg-emerald-500');
                $('#gps-text').text('GPS terkunci (±' + Math.round(pos.coords.accuracy) + 'm)');
            }, function () {
                $('#gps-dot').attr('class', 'step-dot bg-red-500');
                $('#gps-text').text('GPS belum aktif — izinkan akses lokasi');
            }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 });
        }
        refreshGps();
        setInterval(refreshGps, 30000);

        // ── Absen masuk / pulang ──
        $('#btnAbsen').on('click', function () {
            if (MODE === 'done') {
                resultBox(false, 'Absen masuk dan pulang hari ini sudah tercatat.');
                return;
            }
            if (!gpsFix) {
                resultBox(false, 'Tunggu kunci GPS (atau aktifkan GPS presisi tinggi), lalu coba lagi.');
                refreshGps();
                return;
            }
            setBusy(true);
            const payload = {
                token: TOKEN,
                lat: gpsFix.lat,
                lng: gpsFix.lng,
                accuracy: gpsFix.accuracy
            };
            payload[CSRF_NAME] = CSRF_HASH;

            const endpoint = MODE === 'checkout' ? CHECKOUT_URL : CHECKIN_URL;
            var kirim = function (cobaUlang) {
                $.post(endpoint, payload, function (res) {
                    setBusy(false);
                    if (res && res.status) {
                        resultBox(true, res.message || 'Absen berhasil.');
                        $('#btnText').text(MODE === 'checkout' ? '✔ Absen Pulang Berhasil' : '✔ Absen Berhasil');
                        $('#btnAbsen').prop('disabled', true);
                    } else {
                        if (!gerbangJurnal(res)) {
                            resultBox(false, (res && res.message) || 'Gagal memproses absen.');
                        }
                    }
                }, 'json').fail(function (jqXHR, textStatus) {
                    const code = jqXHR ? jqXHR.status : 0;
                    // Token basi (halaman lama terbuka): segarkan sekali lalu ulangi.
                    if (code === 403 && !cobaUlang) {
                        $.getJSON(CSRF_REFRESH_URL, function (segar) {
                            if (segar && segar.status && segar.csrfHash) {
                                CSRF_HASH = segar.csrfHash;
                                payload[CSRF_NAME] = CSRF_HASH;
                                kirim(true);
                            } else {
                                setBusy(false);
                                resultBox(false, 'Sesi berakhir. Muat ulang halaman lalu coba lagi.');
                            }
                        }).fail(function () {
                            setBusy(false);
                            resultBox(false, 'Sesi berakhir. Muat ulang halaman lalu coba lagi.');
                        });
                        return;
                    }
                    setBusy(false);
                    if (textStatus === 'parsererror' || code === 401 || code === 419) {
                        resultBox(false, 'Sesi Anda berakhir atau respons server tidak valid. Muat ulang halaman lalu coba lagi.');
                        return;
                    }
                    if (code === 403) {
                        resultBox(false, 'Token keamanan kedaluwarsa (HTTP 403). Muat ulang halaman lalu coba lagi.');
                        return;
                    }
                    if (code === 429) {
                        resultBox(false, 'Terlalu banyak percobaan. Tunggu 1 menit lalu coba lagi.');
                        return;
                    }
                    let detail = '';
                    const resErr = jqXHR && jqXHR.responseJSON;
                    if (resErr && resErr.message) detail = ': ' + resErr.message;
                    resultBox(false, 'Gagal terhubung ke server (HTTP ' + (code || '0') + ')' + detail);
                });
            };
            kirim(false);
        });
    });
    </script>
</body>
</html>
