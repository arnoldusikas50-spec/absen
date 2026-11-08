<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .card-preview {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .card-preview:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px -6px rgba(107, 33, 168, 0.15), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
    }
    .qr-box canvas, .qr-box img {
        margin: 0 auto;
        display: block;
    }
    .card-header-gtk-landscape {
        background: linear-gradient(135deg, var(--accent-900) 0%, var(--purple-800) 50%, var(--indigo-700) 100%) !important;
        color: #ffffff !important;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid var(--accent-500);
    }
    .card-header-gtk-portrait {
        background: linear-gradient(180deg, var(--accent-900) 0%, var(--accent-600) 55%, #581c87 100%) !important;
        color: #ffffff !important;
        border-bottom: 2px solid var(--accent-500);
    }
    .badge-gtk-glass {
        background: rgba(255, 255, 255, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        color: #ffffff !important;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-badge-fill"></i> Kartu Presensi Guru / GTK
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Cetak Kartu Absen Guru QR</h1>
            <p class="text-xs text-slate-500 mt-1">Cetak kartu ID presensi Guru & Tenaga Kependidikan dengan QR Code standar untuk scan absensi mandiri di scanner kiosk.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <?php $basePath = $base_path ?? 'admin/presensi/guru/kartu'; ?>
            <?php $desainAktif = $desain ?? 'landscape'; ?>
            <a id="btnCetakLembar"
               href="<?= site_url($basePath . '/cetak?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=' . $desainAktif . '&v=1') ?>"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-purple-500/20 no-underline">
                <i class="bi bi-printer-fill"></i> Cetak Lembar Kartu (A4)
            </a>
            <a href="<?= site_url('scanner') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-camera-fill"></i> Buka Scanner QR
            </a>
        </div>
    </div>

    <!-- Filter & Control Panel -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <?php $desainAktif = $desain ?? 'landscape'; ?>
        <!-- Pilihan Desain Kartu -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Desain Kartu</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1" role="tablist" aria-label="Pilihan desain kartu">
                <a href="<?= site_url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=landscape') ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline <?= $desainAktif === 'landscape' ? 'bg-white text-purple-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' ?>">
                    <i class="bi bi-credit-card-2-front"></i> Landscape
                    <span class="hidden sm:inline font-semibold <?= $desainAktif === 'landscape' ? 'text-purple-500' : 'text-slate-400' ?>">• Foto & Detail</span>
                </a>
                <a href="<?= site_url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=portrait') ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline <?= $desainAktif === 'portrait' ? 'bg-white text-purple-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' ?>">
                    <i class="bi bi-credit-card"></i> Portrait
                    <span class="hidden sm:inline font-semibold <?= $desainAktif === 'portrait' ? 'text-purple-500' : 'text-slate-400' ?>">• QR Besar</span>
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                <?= $desainAktif === 'portrait'
                    ? 'Portrait: kartu vertikal dengan QR Code besar di tengah — sangat jelas terbaca dan cocok untuk kartu gantung / lanyard.'
                    : 'Landscape: kartu horizontal resmi dengan foto guru, NIP, jabatan, dan QR Code di samping kanan.' ?>
            </p>
        </div>

        <form method="get" action="<?= site_url($basePath) ?>" id="filterForm" data-base="<?= site_url($basePath) ?>" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="desain" id="inputDesain" value="<?= esc($desainAktif) ?>">
            
            <!-- Pilih Guru (Per Orang / Semua) -->
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Guru / GTK</label>
                <select name="id_guru" id="selectGuru" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    <option value="0">-- Semua Guru / GTK (<?= count($guru_options) ?> Guru) --</option>
                    <?php foreach ($guru_options as $go): ?>
                        <option value="<?= $go->id_guru ?>" <?= ($id_guru == $go->id_guru) ? 'selected' : '' ?>>
                            <?= esc($go->nama_guru) ?> (<?= !empty($go->nip) ? 'NIP: ' . esc($go->nip) : (!empty($go->kode_guru) ? 'Kode: ' . esc($go->kode_guru) : 'ID: ' . $go->id_guru) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pencarian Cepat Nama / NIP -->
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Filter Pencarian</label>
                <div class="relative">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama, NIP, atau kode guru..."
                           class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                </div>
            </div>
        </form>

        <?php if (!empty($catatan)): ?>
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs text-amber-800">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
                <p class="font-semibold leading-relaxed"><?= esc($catatan) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($terpotong)): ?>
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-purple-200 bg-purple-50 px-3.5 py-3 text-xs text-purple-800">
                <i class="bi bi-info-circle-fill mt-0.5 text-purple-500"></i>
                <p class="font-semibold leading-relaxed">
                    Daftar dipotong pada <?= (int) ($batas_cetak ?? 500) ?> kartu pertama supaya halaman tidak macet.
                    Pilih satu guru untuk mencetak sisanya.
                </p>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-purple-100 text-purple-700 font-bold text-[10px]">
                    <?= count($guru_list) ?>
                </span>
                <span>kartu guru ditampilkan<?= !empty($terpotong) ? ' (dari daftar yang dipotong)' : '' ?></span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-slate-400">Instansi: <strong class="text-slate-700"><?= esc($setting->sekolah ?? 'Sekolah') ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Live Card Grid Preview -->
    <?php if (empty($guru_list)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-2xl mx-auto mb-3">
                <i class="bi bi-person-x"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Data Guru</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                <?php if (!empty($catatan)): ?>
                    <?= esc($catatan) ?>
                <?php else: ?>
                    Silakan periksa master data guru di menu Data Guru.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <?php if ($desainAktif === 'portrait'): ?>
        <!-- Preview Portrait: vertikal, QR besar -->
        <div id="cardGrid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-6 justify-items-center">
            <?php foreach ($guru_list as $g): ?>
                <div class="teacher-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col w-full max-w-[220px]"
                     data-name="<?= strtolower(esc($g->nama_guru)) ?>"
                     data-nip="<?= strtolower(esc($g->nip ?? '')) ?>"
                     data-kode="<?= strtolower(esc($g->kode_guru ?? '')) ?>">
                    <!-- Kop vertikal -->
                    <div class="card-header-gtk-portrait px-3 pt-3 pb-2.5 text-center" style="background:linear-gradient(180deg,var(--accent-900) 0%,var(--accent-600) 55%,#581c87 100%);color:#fff;">
                        <div class="w-9 h-9 rounded-xl p-1 mx-auto flex items-center justify-center bg-white shadow-xs">
                            <img src="<?= $logo_url ?>" alt="Logo" class="max-h-full max-w-full object-contain"
                                 onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                        </div>
                        <p class="tracking-wider uppercase leading-none mt-1.5 font-bold" style="font-size:8px;color:#e9d5ff;">KARTU PRESENSI DIGITAL</p>
                        <h4 class="leading-tight mt-0.5 font-black text-white" style="font-size:11px;color:#fff;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= esc($setting->sekolah ?? 'SEKOLAH') ?></h4>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-md uppercase font-extrabold badge-gtk-glass" style="font-size:9px;letter-spacing:.05em;color:#fff;">
                            GURU / GTK
                        </span>
                    </div>
                    <!-- QR besar -->
                    <div class="px-3 pt-2 pb-1 flex flex-col items-center">
                        <div class="qr-box p-2 bg-white border border-slate-200 rounded-xl shadow-2xs"
                             data-code="<?= esc($g->qr_value) ?>" data-size="150"
                             id="qr-preview-<?= $g->id_guru ?>" style="width:166px;height:166px;display:flex;align-items:center;justify-content:center;"></div>
                        <span class="font-mono font-bold text-slate-800 text-[8.5px] mt-1 text-center tracking-tight break-all max-w-[170px]"><?= esc($g->qr_value) ?></span>
                        <span class="text-[7.5px] font-extrabold text-purple-600 uppercase tracking-wider">Scan Absen</span>
                    </div>
                    <!-- Identitas -->
                    <div class="px-3 pb-2 pt-1 text-center flex-1">
                        <h5 class="text-xs font-extrabold text-slate-900 leading-snug line-clamp-2" title="<?= esc($g->nama_guru) ?>">
                            <?= esc($g->nama_guru) ?>
                        </h5>
                        <p class="text-[10px] text-slate-500 mt-0.5 font-mono">NIP: <?= esc($g->nip ?: ($g->kode_guru ?: '-')) ?></p>
                        <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold rounded-full bg-purple-50 text-purple-700">
                            <?= esc($g->jabatan) ?>
                        </span>
                    </div>
                    <div class="px-3 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-center">
                        <a href="<?= site_url($basePath . '/cetak?id_guru=' . $g->id_guru . '&desain=portrait&v=1') ?>"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-700 bg-white hover:bg-purple-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline text-[10px]">
                            <i class="bi bi-printer"></i> Cetak Kartu Ini
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Preview Landscape: horizontal dengan foto -->
        <div id="cardGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($guru_list as $g): ?>
                <div class="teacher-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col"
                     data-name="<?= strtolower(esc($g->nama_guru)) ?>"
                     data-nip="<?= strtolower(esc($g->nip ?? '')) ?>"
                     data-kode="<?= strtolower(esc($g->kode_guru ?? '')) ?>">

                    <!-- Card Header (Kop Kartu) -->
                    <div class="card-header-gtk-landscape" style="background:linear-gradient(135deg,var(--accent-900) 0%,var(--purple-800) 50%,var(--indigo-700) 100%);color:#fff;">
                        <div class="w-10 h-10 bg-white rounded-xl p-1 shrink-0 flex items-center justify-center shadow-xs">
                            <img src="<?= $logo_url ?>" alt="Logo" class="max-h-full max-w-full object-contain"
                                 onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[8.5px] font-bold tracking-wider uppercase leading-none" style="color: #e9d5ff;">KARTU PRESENSI DIGITAL</p>
                            <h4 class="text-xs font-black text-white truncate mt-0.5 leading-snug" style="color: #ffffff;" title="<?= esc($setting->sekolah ?? 'SEKOLAH') ?>"><?= esc($setting->sekolah ?? 'SEKOLAH') ?></h4>
                            <p class="text-[8.5px] font-medium leading-tight truncate mt-0.5" style="color: var(--accent-200);"><?= esc($setting->kota ?? '') ?><?= !empty($setting->kota) ? ' • ' : '' ?>GTK DIGITAL</p>
                        </div>
                        <div class="shrink-0 badge-gtk-glass px-2.5 py-1 rounded-md text-[9px] font-extrabold uppercase tracking-wider text-white">
                            GURU / GTK
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 flex gap-3.5 items-center flex-1">
                        <!-- Foto Guru & Status Pegawai -->
                        <div class="flex flex-col items-center shrink-0 w-20">
                            <div class="w-20 h-24 rounded-xl overflow-hidden border-2 border-purple-100 shadow-xs bg-slate-50 flex items-center justify-center">
                                <img src="<?= esc($g->foto_url, 'attr') ?>" alt="<?= esc($g->nama_guru) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null;this.src='<?= esc(\App\Services\AvatarService::inisial($g->nama_guru, 128, '#7c3aed'), 'attr') ?>'">
                            </div>
                            <?php
                            $sp = strtoupper(trim((string)($g->status_pegawai ?? '')));
                            $spClass = 'bg-purple-50 text-purple-700 border-purple-200';
                            if (strpos($sp, 'PNS') !== false) {
                                $spClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } elseif (strpos($sp, 'PPPK') !== false) {
                                $spClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            } elseif (strpos($sp, 'HONOR') !== false || strpos($sp, 'GTT') !== false || strpos($sp, 'PTT') !== false) {
                                $spClass = 'bg-amber-50 text-amber-700 border-amber-200';
                            }
                            ?>
                            <span class="mt-1.5 px-1.5 py-0.5 text-[8.5px] font-bold rounded-md border text-center w-full leading-tight <?= $spClass ?>" title="<?= esc($g->status_pegawai ?: 'PENDIDIK') ?>">
                                <?= esc($g->status_pegawai ?: 'PENDIDIK') ?>
                            </span>
                        </div>

                        <!-- Info Guru -->
                        <div class="flex-1 min-w-0">
                            <h5 class="text-sm font-extrabold text-slate-900 leading-snug line-clamp-2" title="<?= esc($g->nama_guru) ?>">
                                <?= esc($g->nama_guru) ?>
                            </h5>
                            <div class="mt-2.5 space-y-1.5 text-xs">
                                <div class="flex items-center text-slate-600">
                                    <span class="w-14 shrink-0 text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIP</span>
                                    <span class="font-mono font-bold text-slate-900 truncate">: <?= esc($g->nip ?: '-') ?></span>
                                </div>
                                <div class="flex items-center text-slate-600">
                                    <span class="w-14 shrink-0 text-[10px] font-bold text-slate-400 uppercase tracking-wider">NUPTK</span>
                                    <span class="font-mono text-slate-700 truncate">: <?= esc($g->nuptk ?: '-') ?></span>
                                </div>
                                <div class="flex items-center text-slate-600">
                                    <span class="w-14 shrink-0 text-[10px] font-bold text-slate-400 uppercase tracking-wider">TUGAS</span>
                                    <span class="font-semibold text-purple-700 truncate">: <?= esc($g->jabatan) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code Box -->
                        <div class="shrink-0 flex flex-col items-center justify-center pl-3 border-l border-slate-100">
                            <div class="qr-box p-1.5 bg-white border border-slate-200 rounded-xl shadow-2xs"
                                 data-code="<?= esc($g->qr_value) ?>"
                                 data-size="80"
                                 id="qr-preview-<?= $g->id_guru ?>"
                                 style="width:92px;height:92px;display:flex;align-items:center;justify-content:center;"></div>
                            <span class="font-mono font-bold text-slate-800 text-[8.5px] mt-1 text-center tracking-tight break-all max-w-[95px]" title="<?= esc($g->qr_value) ?>"><?= esc($g->qr_value) ?></span>
                            <span class="text-[7.5px] font-extrabold text-purple-600 uppercase tracking-wider mt-0.5">Scan Absen</span>
                        </div>
                    </div>

                    <!-- Card Footer & Single Print Button -->
                    <div class="px-4 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-[10px]">
                        <span class="text-slate-400 truncate max-w-[170px]">Gunakan scan di Kiosk Presensi</span>
                        <a href="<?= site_url($basePath . '/cetak?id_guru=' . $g->id_guru . '&desain=landscape&v=1') ?>"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-700 bg-white hover:bg-purple-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline">
                            <i class="bi bi-printer"></i> Cetak Kartu Ini
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Library QRCode JS -->
<script src="<?= base_url('assets/app/js/qrcode.min.js') ?>"></script>
<script>
$(document).ready(function() {
    const basePath = $('#filterForm').data('base');

    // Generate QR Code untuk setiap kartu yang tampil
    $('.qr-box').each(function() {
        const el = this;
        const code = $(el).data('code');
        const size = parseInt($(el).data('size') || '78', 10) || 78;
        if (code) {
            try {
                new QRCode(el, {
                    text: String(code),
                    width: size,
                    height: size,
                    colorDark: "#1e1b4b",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch (e) {
                $(el).html('<span class="font-mono text-xs font-bold">' + $('<div>').text(code).html() + '</span>');
            }
        } else {
            $(el).html('<span class="text-[10px] text-slate-400">QR kosong</span>');
        }
    });

    function refreshCetakLink() {
        const idGuru = $('#selectGuru').val() || '0';
        const desain = $('#inputDesain').val() || 'landscape';
        let url = basePath + '/cetak?';
        if (idGuru !== '0') url += 'id_guru=' + encodeURIComponent(idGuru) + '&';
        url += 'desain=' + encodeURIComponent(desain) + '&v=1';
        $('#btnCetakLembar').attr('href', url);
    }

    // Auto submit filter saat ganti guru
    $('#selectGuru').on('change', function() {
        refreshCetakLink();
        $('#filterForm').submit();
    });

    // Realtime search filter nama / NIP / kode di preview grid
    $('#searchInput').on('keyup', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.teacher-card-item').each(function() {
            const name = $(this).data('name') || '';
            const nip  = $(this).data('nip') || '';
            const kode = $(this).data('kode') || '';

            if (name.includes(query) || nip.includes(query) || kode.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
