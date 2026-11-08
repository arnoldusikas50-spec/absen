<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .card-preview {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .card-preview:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px -6px rgb(var(--primary-700-rgb) / 0.15), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
    }
    .qr-box canvas, .qr-box img {
        margin: 0 auto;
        display: block;
    }
    .card-header-siswa-landscape {
        background: linear-gradient(135deg, var(--primary-700) 0%, var(--indigo-700) 50%, var(--primary-800) 100%) !important;
        color: #ffffff !important;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid var(--primary-500);
    }
    .badge-siswa-glass {
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
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-qr-code-scan"></i> Kartu Presensi Siswa
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Cetak Kartu Absen Siswa QR</h1>
            <p class="text-xs text-slate-500 mt-1">Cetak kartu ID presensi siswa dengan QR Code standar untuk scan absensi harian & KBM.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <?php $basePath = $base_path ?? 'admin/presensi/siswa/kartu'; ?>
            <?php $desainAktif = $desain ?? 'landscape'; ?>
            <a id="btnCetakLembar"
               href="<?= site_url($basePath . '/cetak?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=' . $desainAktif . '&v=3') ?>"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20 no-underline">
                <i class="bi bi-printer-fill"></i> Cetak Lembar Kartu (A4)
            </a>
            <a href="<?= site_url('guru/presensi-siswa' . (session()->get('user_id') ? '?h=' . \App\Controllers\Guru\PresensiSiswaQr::generateHash((int)session()->get('user_id')) : '')) ?>"
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
                <a href="<?= site_url(($base_path ?? 'admin/presensi/siswa/kartu') . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=landscape&v=3') ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline <?= $desainAktif === 'landscape' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' ?>">
                    <i class="bi bi-credit-card-2-front"></i> Landscape
                    <span class="hidden sm:inline font-semibold <?= $desainAktif === 'landscape' ? 'text-blue-500' : 'text-slate-400' ?>">• Foto</span>
                </a>
                <a href="<?= site_url(($base_path ?? 'admin/presensi/siswa/kartu') . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=portrait&v=3') ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline <?= $desainAktif === 'portrait' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' ?>">
                    <i class="bi bi-credit-card"></i> Portrait
                    <span class="hidden sm:inline font-semibold <?= $desainAktif === 'portrait' ? 'text-blue-500' : 'text-slate-400' ?>">• Tanpa Foto</span>
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                <?= $desainAktif === 'portrait'
                    ? 'Portrait: kartu vertikal tanpa foto siswa, QR besar di tengah — hemat tinta & cocok untuk kalung ID.'
                    : 'Landscape: kartu horizontal standar dengan foto siswa + QR di samping.' ?>
            </p>
        </div>
        <form method="get" action="<?= site_url($base_path ?? 'admin/presensi/siswa/kartu') ?>" id="filterForm" data-base="<?= site_url($base_path ?? 'admin/presensi/siswa/kartu') ?>" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="desain" id="inputDesain" value="<?= esc($desainAktif) ?>">
            <!-- Pilih Kelas -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Kelas</label>
                <select name="id_kelas" id="selectKelas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="0">-- Semua Kelas --</option>
                    <?php foreach ($kelases as $k): ?>
                        <option value="<?= $k->id_kelas ?>" <?= ($id_kelas == $k->id_kelas) ? 'selected' : '' ?>>
                            Kelas <?= esc($k->nama_kelas) ?> (<?= $k->jumlah_siswa ?> Siswa)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pilih Siswa (Per Orang) -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Siswa (Opsional / Per Orang)</label>
                <select name="id_siswa" id="selectSiswa" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="0">-- Semua Siswa di Kelas Ini --</option>
                    <?php foreach ($siswa_options as $so): ?>
                        <option value="<?= $so->id_siswa ?>" <?= ($id_siswa == $so->id_siswa) ? 'selected' : '' ?>>
                            <?= esc($so->nama) ?> (NIS: <?= esc($so->nis ?? '-') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pencarian Cepat Nama / NIS -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Filter Pencarian</label>
                <div class="relative">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama atau NIS siswa..."
                           class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
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
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                <p class="font-semibold leading-relaxed">
                    Daftar dipotong pada <?= (int) ($batas_cetak ?? 500) ?> kartu pertama supaya halaman tidak macet.
                    Pilih satu kelas atau satu siswa untuk mencetak sisanya.
                </p>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 font-bold text-[10px]">
                    <?= count($siswa_list) ?>
                </span>
                <span>kartu ditampilkan<?= !empty($terpotong) ? ' (dari daftar yang dipotong)' : '' ?></span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-slate-400">TP: <strong class="text-slate-700"><?= esc($tp_active->tahun ?? '-') ?></strong></span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-400">Semester: <strong class="text-slate-700"><?= esc($smt_active->smt ?? '-') ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Live Card Grid Preview -->
    <?php if (empty($siswa_list)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-2xl mx-auto mb-3">
                <i class="bi bi-person-x"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Siswa Ditemukan</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                <?php if (!empty($catatan)): ?>
                    <?= esc($catatan) ?>
                <?php else: ?>
                    Silakan pilih kelas lain atau periksa data penempatan kelas siswa pada Tahun Pelajaran aktif.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <?php $desainAktif = $desain ?? 'landscape'; ?>
        <?php if ($desainAktif === 'portrait'): ?>
        <!-- Preview Portrait: vertikal, tanpa foto, QR besar -->
        <div id="cardGrid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-6 justify-items-center">
            <?php foreach ($siswa_list as $s): ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col w-full max-w-[220px]"
                     data-name="<?= strtolower(esc($s->nama)) ?>"
                     data-nis="<?= strtolower(esc($s->nis ?? '')) ?>"
                     data-nisn="<?= strtolower(esc($s->nisn ?? '')) ?>">
                    <!-- Kop vertikal (inline style agar tidak tergantung build Tailwind) -->
                    <div class="px-3 pt-3 pb-2.5 text-center" style="background:linear-gradient(180deg,var(--primary-700) 0%,var(--indigo-800) 55%,var(--primary-900) 100%);color:#fff;">
                        <div class="w-9 h-9 rounded-xl p-1 mx-auto flex items-center justify-center" style="background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.15);">
                            <img src="<?= $logo_url ?>" alt="Logo" class="max-h-full max-w-full object-contain"
                                 onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                        </div>
                        <p class="tracking-wider uppercase leading-none mt-1.5" style="font-size:8px;font-weight:600;color:var(--primary-200);">KARTU PRESENSI DIGITAL</p>
                        <h4 class="leading-tight mt-0.5" style="font-size:11px;font-weight:800;color:#fff;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= esc($setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR') ?></h4>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-md uppercase" style="font-size:10px;font-weight:800;letter-spacing:.05em;color:#fff;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);">
                            <?= esc($s->nama_kelas) ?>
                        </span>
                    </div>
                    <!-- QR besar -->
                    <div class="px-3 pt-2 pb-1 flex flex-col items-center">
                        <div class="qr-box p-2 bg-white border border-slate-200 rounded-xl shadow-2xs"
                             data-code="<?= esc($s->qr_value) ?>" data-size="150"
                             id="qr-preview-<?= $s->id_siswa ?>" style="width:166px;height:166px;display:flex;align-items:center;justify-content:center;"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider">Scan Absen</span>
                    </div>
                    <!-- Identitas -->
                    <div class="px-3 pb-2 pt-1 text-center flex-1">
                        <h5 class="text-xs font-extrabold text-slate-900 leading-snug line-clamp-2" title="<?= esc($s->nama) ?>">
                            <?= esc($s->nama) ?>
                        </h5>
                        <p class="text-[10px] text-slate-500 mt-0.5 font-mono"> NISN <?= esc($s->nisn ?: '-') ?></p>
                        <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold rounded-full <?= ($s->jenis_kelamin == 'L') ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' ?>">
                            <?= ($s->jenis_kelamin == 'L') ? 'LAKI-LAKI' : 'PEREMPUAN' ?>
                        </span>
                    </div>
                    <div class="px-3 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-center">
                        <a href="<?= site_url(($base_path ?? 'admin/presensi/siswa/kartu') . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=portrait&v=3') ?>"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-700 bg-white hover:bg-blue-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline text-[10px]">
                            <i class="bi bi-printer"></i> Cetak Kartu Ini
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div id="cardGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($siswa_list as $s): ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col"
                     data-name="<?= strtolower(esc($s->nama)) ?>"
                     data-nis="<?= strtolower(esc($s->nis ?? '')) ?>"
                     data-nisn="<?= strtolower(esc($s->nisn ?? '')) ?>">

                    <!-- Card Header (Kop Kartu) -->
                    <div class="card-header-siswa-landscape" style="background:linear-gradient(135deg,var(--primary-700) 0%,#4338ca 50%,var(--primary-800) 100%);color:#fff;">
                        <div class="w-10 h-10 bg-white rounded-xl p-1 shrink-0 flex items-center justify-center shadow-xs">
                            <img src="<?= $logo_url ?>" alt="Logo" class="max-h-full max-w-full object-contain"
                                 onerror="this.src='<?= base_url('assets/img/logo.png') ?>'">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[8.5px] font-bold tracking-wider uppercase leading-none" style="color:var(--primary-200);">KARTU PRESENSI DIGITAL</p>
                            <h4 class="text-xs font-black text-white truncate mt-0.5 leading-snug" style="color:#fff;" title="<?= esc($setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR') ?>"><?= esc($setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR') ?></h4>
                            <p class="text-[8.5px] font-medium leading-tight truncate mt-0.5" style="color:var(--primary-100);"><?= esc($setting->kota ?? 'Kab. Kupang') ?> • TP <?= esc($tp_active->tahun ?? '-') ?></p>
                        </div>
                        <div class="shrink-0 badge-siswa-glass px-2.5 py-1 rounded-md text-[9px] font-extrabold uppercase tracking-wider text-white">
                            <?= esc($s->nama_kelas) ?>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 flex gap-4 items-center flex-1">
                        <!-- Foto Siswa -->
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-20 h-24 rounded-xl overflow-hidden border-2 border-slate-100 shadow-xs bg-slate-50 flex items-center justify-center">
                                <img src="<?= $s->foto_url ?>" alt="<?= esc($s->nama) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.src='<?= base_url('assets/img/siswa.png') ?>'">
                            </div>
                            <span class="mt-1.5 px-2 py-0.5 text-[9px] font-bold rounded-full <?= ($s->jenis_kelamin == 'L') ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' ?>">
                                <?= ($s->jenis_kelamin == 'L') ? 'LAKI-LAKI' : 'PEREMPUAN' ?>
                            </span>
                        </div>

                        <!-- Info Siswa -->
                        <div class="flex-1 min-w-0">
                            <h5 class="text-sm font-extrabold text-slate-900 leading-snug line-clamp-2" title="<?= esc($s->nama) ?>">
                                <?= esc($s->nama) ?>
                            </h5>
                            <div class="mt-2 space-y-1 text-xs">
                                <div class="flex items-center text-slate-600">
                                    <span class="w-12 text-[10px] font-bold text-slate-400 uppercase">NIS</span>
                                    <span class="font-mono font-bold text-slate-900">: <?= esc($s->nis ?: '-') ?></span>
                                </div>
                                <div class="flex items-center text-slate-600">
                                    <span class="w-12 text-[10px] font-bold text-slate-400 uppercase">NISN</span>
                                    <span class="font-mono text-slate-700">: <?= esc($s->nisn ?: '-') ?></span>
                                </div>
                                <div class="flex items-center text-slate-600">
                                    <span class="w-12 text-[10px] font-bold text-slate-400 uppercase">KELAS</span>
                                    <span class="font-semibold text-slate-800">: <?= esc($s->nama_kelas) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code Box -->
                        <div class="shrink-0 flex flex-col items-center justify-center pl-2 border-l border-slate-100">
                            <div class="qr-box p-1.5 bg-white border border-slate-200 rounded-xl shadow-2xs"
                                 data-code="<?= esc($s->qr_value) ?>"
                                 id="qr-preview-<?= $s->id_siswa ?>"></div>
                            <span class="font-mono font-bold text-slate-700 text-[10px] mt-1"><?= esc($s->qr_value) ?></span>
                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider">Scan Absen</span>
                        </div>
                    </div>

                    <!-- Card Footer & Single Print Button -->
                    <div class="px-4 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-[10px]">
                        <span class="text-slate-400 truncate max-w-[170px]">Gunakan untuk presensi harian / KBM</span>
                        <a href="<?= site_url(($base_path ?? 'admin/presensi/siswa/kartu') . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=landscape&v=3') ?>"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-700 bg-white hover:bg-blue-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline">
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
                    colorDark: "#0f172a",
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
        const idKelas = $('#selectKelas').val() || '0';
        const idSiswa = $('#selectSiswa').val() || '0';
        const desain  = $('#inputDesain').val() || 'landscape';
        let url = basePath + '/cetak?id_kelas=' + encodeURIComponent(idKelas);
        if (idSiswa !== '0') url += '&id_siswa=' + encodeURIComponent(idSiswa);
        url += '&desain=' + encodeURIComponent(desain);
        url += '&v=3';
        $('#btnCetakLembar').attr('href', url);
    }

    // Muat opsi siswa via AJAX saat kelas diganti (tanpa reload penuh bila memungkinkan)
    $('#selectKelas').on('change', function() {
        const idKelas = $(this).val();
        $('#selectSiswa').val('0');
        refreshCetakLink();
        if (idKelas === '0') {
            $('#filterForm').submit();
            return;
        }
        const $siswa = $('#selectSiswa');
        $siswa.prop('disabled', true);
        $.get(basePath + '/siswa/' + encodeURIComponent(idKelas))
            .done(function(res) {
                if (res && res.status && Array.isArray(res.data)) {
                    $siswa.empty().append('<option value="0">-- Semua Siswa di Kelas Ini --</option>');
                    res.data.forEach(function(row) {
                        $siswa.append('<option value="' + row.id_siswa + '">' + $('<div>').text(row.nama).html() + ' (NIS: ' + $('<div>').text(row.nis || '-').html() + ')</option>');
                    });
                    // Tetap submit agar grid kartu ikut terfilter server-side
                    $('#filterForm').submit();
                } else {
                    $('#filterForm').submit();
                }
            })
            .fail(function() { $('#filterForm').submit(); })
            .always(function() { $siswa.prop('disabled', false); });
    });

    // Auto submit filter saat ganti siswa
    $('#selectSiswa').on('change', function() {
        refreshCetakLink();
        $('#filterForm').submit();
    });

    // Realtime search filter nama / NIS di preview grid
    $('#searchInput').on('keyup', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.student-card-item').each(function() {
            const name = $(this).data('name') || '';
            const nis  = $(this).data('nis') || '';
            const nisn = $(this).data('nisn') || '';

            if (name.includes(query) || nis.includes(query) || nisn.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
