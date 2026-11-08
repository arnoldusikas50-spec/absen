<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Tab pills (pola users) ─────────────── */
    .tab-pill { transition: background .15s, color .15s, box-shadow .15s; }
    .tab-pill.active {
        background: #fff;
        color: #0284c7;
        box-shadow: 0 1px 6px rgba(0,0,0,.08);
    }

    .num-tabular {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    @media print {
        .no-print { display: none !important; }
        .fade-in-up { animation: none !important; }
        body { background: #fff !important; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
// Normalisasi URL foto absensi: kolom menyimpan path relatif "uploads/absen/xxx.jpg".
$fotoUrl = function ($v) {
    $s = trim((string) $v);
    if ($s === '') return null;
    if (preg_match('#^https?://#i', $s) || strpos($s, '/') === 0) return $s;
    if (strpos($s, 'uploads/') === false) $s = 'uploads/absen/' . ltrim($s, '/');
    $ver = file_exists(FCPATH . $s) ? filemtime(FCPATH . $s) : time();
    return base_url($s) . '?v=' . $ver;
};
$namaBulanId = ['','Januari','Februari','Maret','April','Mei','Juni',
                'Juli','Agustus','September','Oktober','November','Desember'];
$labelBulanIni = $namaBulanId[(int) date('n')] . ' ' . date('Y');
?>

<!-- Page Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-cyan-50 text-cyan-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-briefcase-fill"></i> Verifikasi Dinas Luar
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Verifikasi Dinas Luar') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Tinjau, periksa koordinat GPS, foto kehadiran, dan setujui permintaan dinas luar guru.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/izin') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-file-earmark-medical text-cyan-600"></i> Verifikasi Izin/Sakit
            </a>
            <a href="<?= site_url('admin/presensi') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-speedometer2"></i> Monitoring Presensi
            </a>
        </div>
    </div>

    <!-- KPI Stat Cards (pola dapodik: border-l accent) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Menunggu Verifikasi (Pending) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-amber-500 shadow-xs p-4 flex items-center gap-3 <?= ($stats['pending'] ?? 0) > 0 ? 'ring-1 ring-amber-200' : '' ?>">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl shrink-0">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Menunggu (Pending)</div>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-extrabold text-slate-900 num-tabular mb-0"><?= number_format($stats['pending'] ?? 0) ?></h3>
                    <?php if (($stats['pending'] ?? 0) > 0): ?>
                        <span class="badge-tw badge-warning text-[10px] rounded-full px-2 py-0.5 animate-pulse">Perlu Respon</span>
                    <?php endif; ?>
                </div>
                <span class="text-[11px] text-slate-400 block mt-0.5">Permintaan dinas luar baru</span>
            </div>
        </div>

        <!-- 2. Disetujui (Approved) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Disetujui (Bulan Ini)</div>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-extrabold text-emerald-600 num-tabular mb-0"><?= number_format($stats['approved'] ?? 0) ?></h3>
                    <span class="text-[10px] text-emerald-600 font-semibold">Tervalidasi</span>
                </div>
                <span class="text-[11px] text-slate-400 block mt-0.5">Poin kehadiran diberikan</span>
            </div>
        </div>

        <!-- 3. Ditolak (Rejected) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-red-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl shrink-0">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ditolak (Bulan Ini)</div>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-extrabold text-red-500 num-tabular mb-0"><?= number_format($stats['rejected'] ?? 0) ?></h3>
                    <span class="text-[10px] text-red-500 font-semibold">Ditolak</span>
                </div>
                <span class="text-[11px] text-slate-400 block mt-0.5">Dianggap tidak sah / ditolak</span>
            </div>
        </div>

        <!-- 4. Total Pengajuan -->
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-cyan-500 shadow-xs p-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shrink-0">
                <i class="bi bi-collection"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Pengajuan</div>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-extrabold text-slate-900 num-tabular mb-0"><?= number_format($stats['total'] ?? 0) ?></h3>
                    <span class="badge-tw badge-info text-[10px] rounded-full px-2 py-0.5"><?= esc($labelBulanIni) ?></span>
                </div>
                <span class="text-[11px] text-slate-400 block mt-0.5">Antrean + diproses bulan ini</span>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Card Tabs & Action Bar -->
        <div class="flex items-center gap-1 p-3 border-b border-slate-100 overflow-x-auto">
            <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl" id="dinasTabs">
                <button type="button" class="tab-pill active flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap" id="tabPendingBtn" data-dtab="pending">
                    <i class="bi bi-hourglass-split"></i> Menunggu Verifikasi
                    <span class="badge-tw badge-warning text-[10px] rounded-full px-2 py-0.5 num-tabular" id="badgeCountPending"><?= count($pending) ?></span>
                </button>
                <button type="button" class="tab-pill flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-slate-500 whitespace-nowrap" id="tabHistoryBtn" data-dtab="history">
                    <i class="bi bi-clock-history"></i> Riwayat (<?= esc($labelBulanIni) ?>)
                    <span class="badge-tw badge-secondary text-[10px] rounded-full px-2 py-0.5 num-tabular"><?= count($history ?? []) ?></span>
                </button>
            </div>

            <!-- Realtime Search -->
            <div class="ml-auto flex items-center gap-2 pl-2">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="bi bi-search"></i>
                    </div>
                    <input type="text" id="searchDinasInput"
                        class="form-control-tw pl-8 pr-7 text-xs py-1.5 w-full rounded-lg"
                        placeholder="Cari guru, NIP, tanggal..." autocomplete="off">
                    <button type="button" id="clearDinasSearch" class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 text-xs border-none bg-transparent cursor-pointer" title="Bersihkan">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 1: PENDING VERIFIKASI -->
        <div id="sectionPending">
            <?php if (empty($pending)): ?>
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h5 class="text-base font-bold text-slate-700 mb-1">Semua Dinas Telah Diverifikasi</h5>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                        Saat ini tidak ada permintaan presensi dinas luar yang sedang menunggu persetujuan.
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table-tw" id="tablePending">
                        <thead>
                            <tr>
                                <th class="text-center w-12">#</th>
                                <th>Guru / Pegawai</th>
                                <th>Tanggal & Waktu</th>
                                <th>Koordinat & Lokasi</th>
                                <th>Device & Jaringan</th>
                                <th class="text-center w-24">Foto</th>
                                <th class="text-right pr-6" style="min-width: 140px;">Aksi Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $no = 1;
                            foreach ($pending as $p):
                                $nama = $p->nama_guru ?? $p->username ?? 'Guru';
                                $avatar = \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), (string) $nama, '#0284c7');
                                $fotoAbsen = $fotoUrl($p->foto_masuk ?? null) ?: $fotoUrl($p->foto_pulang ?? null);
                                $searchString = strtolower($nama . ' ' . ($p->nip ?? '') . ' ' . date('d-m-Y', strtotime($p->tgl)) . ' ' . ($p->device_info ?? ''));
                            ?>
                                <tr id="row-<?= (int) $p->id ?>" class="dinas-row" data-search="<?= esc($searchString, 'attr') ?>">
                                    <!-- No -->
                                    <td class="text-center font-bold text-slate-400 text-xs num-tabular"><?= $no++ ?></td>

                                    <!-- Guru -->
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="<?= esc($avatar, 'attr') ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-sm flex-shrink-0" alt="Avatar" loading="lazy">
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 text-sm leading-tight truncate"><?= esc($nama) ?></div>
                                                <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                                                    <span>NIP: <?= esc($p->nip ?? '-') ?></span>
                                                    <span class="text-slate-300">•</span>
                                                    <span class="badge-tw badge-warning text-[10px] rounded-full px-2 py-0.2">Pending</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tanggal & Waktu -->
                                    <td>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5 text-xs text-slate-700 font-semibold">
                                                <i class="bi bi-calendar-check text-cyan-600"></i>
                                                <span><?= date('d M Y', strtotime($p->tgl)) ?></span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-slate-500 num-tabular">
                                                <i class="bi bi-clock text-slate-400"></i>
                                                <span>Masuk: <strong class="text-slate-700"><?= !empty($p->jam_masuk) ? date('H:i', strtotime($p->jam_masuk)) : '-' ?></strong></span>
                                                <?php if (!empty($p->jam_pulang)): ?>
                                                    <span class="text-slate-300">|</span>
                                                    <span>Plg: <strong class="text-slate-700"><?= date('H:i', strtotime($p->jam_pulang)) ?></strong></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Koordinat & Lokasi -->
                                    <td>
                                        <div class="space-y-1 text-xs">
                                            <?php if (!empty($p->lat_masuk) && !empty($p->lng_masuk)): ?>
                                                <div class="flex items-center gap-1.5">
                                                    <i class="bi bi-geo-alt-fill text-red-500"></i>
                                                    <a href="https://maps.google.com/?q=<?= esc($p->lat_masuk) ?>,<?= esc($p->lng_masuk) ?>"
                                                        target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 font-mono underline decoration-blue-200">
                                                        <?= esc(round($p->lat_masuk, 5)) ?>, <?= esc(round($p->lng_masuk, 5)) ?>
                                                    </a>
                                                    <button type="button" class="text-slate-400 hover:text-blue-600 ml-0.5 border-none bg-transparent cursor-pointer btn-open-maps"
                                                        title="Buka Google Maps"
                                                        data-lat="<?= esc($p->lat_masuk, 'attr') ?>" data-lng="<?= esc($p->lng_masuk, 'attr') ?>">
                                                        <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Koordinat tidak tersedia</span>
                                            <?php endif; ?>

                                            <div class="flex items-center gap-1 flex-wrap">
                                                <?php if (!empty($p->gps_accuracy)): ?>
                                                    <span class="badge-tw badge-info text-[10px] rounded-full px-2 py-0.2">
                                                        Akurasi ±<?= round($p->gps_accuracy) ?>m
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!empty($p->is_mock_location) && $p->is_mock_location == 1): ?>
                                                    <span class="badge-tw badge-danger text-[10px] rounded-full px-2 py-0.2 font-bold" title="Terdeteksi fake GPS / mock location">
                                                        <i class="bi bi-exclamation-triangle-fill mr-1"></i>Fake GPS
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!empty($p->keterangan)): ?>
                                                    <span class="text-slate-500 text-[11px] block truncate max-w-[180px]" title="<?= esc($p->keterangan) ?>">
                                                        Ket: <?= esc($p->keterangan) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Device Info -->
                                    <td>
                                        <div class="text-xs space-y-0.5">
                                            <?php if (!empty($p->device_info)): ?>
                                                <div class="flex items-center gap-1.5 text-slate-600 truncate max-w-[180px]" title="<?= esc($p->device_info) ?>">
                                                    <i class="bi bi-phone text-slate-400"></i>
                                                    <span class="truncate"><?= esc($p->device_info) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-400">-</span>
                                            <?php endif; ?>

                                            <?php if (!empty($p->ip_address)): ?>
                                                <div class="text-[11px] text-slate-400 font-mono">
                                                    IP: <?= esc($p->ip_address) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Foto Bukti -->
                                    <td class="text-center">
                                        <?php if ($fotoAbsen): ?>
                                            <button type="button" class="btn-view-foto relative group cursor-pointer inline-block border-none bg-transparent p-0"
                                                data-foto="<?= esc($fotoAbsen, 'attr') ?>" data-nama="<?= esc($nama, 'attr') ?>" title="Lihat foto">
                                                <img src="<?= esc($fotoAbsen, 'attr') ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform" alt="Foto Absen" loading="lazy">
                                                <div class="absolute inset-0 bg-black/30 rounded-lg opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition-opacity">
                                                    <i class="bi bi-zoom-in text-xs"></i>
                                                </div>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-300 text-xs" title="Guru tidak melampirkan foto">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="text-right pr-6">
                                        <div class="inline-flex items-center rounded-lg shadow-sm">
                                            <button type="button" class="btn-tw btn-tw-sm btn-success rounded-r-none px-2.5 py-1 text-xs js-approve-dinas"
                                                data-id="<?= (int) $p->id ?>" title="Setujui Dinas">
                                                <i class="bi bi-check-lg mr-1"></i>Setujui
                                            </button>
                                            <button type="button" class="btn-tw btn-tw-sm btn-danger rounded-l-none px-2.5 py-1 text-xs js-reject-dinas"
                                                data-id="<?= (int) $p->id ?>" data-nama="<?= esc($nama, 'attr') ?>" title="Tolak Dinas">
                                                <i class="bi bi-x-lg mr-1"></i>Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: RIWAYAT DINAS (HISTORY) -->
        <div id="sectionHistory" class="hidden">
            <?php if (empty($history)): ?>
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto mb-3">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h5 class="text-base font-bold text-slate-700 mb-1">Belum Ada Riwayat</h5>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Riwayat verifikasi dinas luar yang telah diproses akan ditampilkan di sini.
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table-tw" id="tableHistory">
                        <thead>
                            <tr>
                                <th class="text-center w-12">#</th>
                                <th>Guru / Pegawai</th>
                                <th>Tanggal & Waktu</th>
                                <th>Koordinat & Lokasi</th>
                                <th class="text-center w-24">Status</th>
                                <th class="text-center w-24">Foto</th>
                                <th class="text-right pr-6">Diproses Pada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            $noH = 1;
                            foreach ($history as $h):
                                $namaH = $h->nama_guru ?? $h->username ?? 'Guru';
                                $avatarH = \App\Services\AvatarService::tampilan((string) ($h->foto ?? ''), (string) $namaH, '#64748b');
                                $fotoAbsenH = $fotoUrl($h->foto_masuk ?? null) ?: $fotoUrl($h->foto_pulang ?? null);
                                $isApproved = in_array($h->status_masuk, ['DINAS_APPROVED', 'DINAS']);
                                $searchStringH = strtolower($namaH . ' ' . ($h->nip ?? '') . ' ' . date('d-m-Y', strtotime($h->tgl)));
                            ?>
                                <tr class="dinas-row" data-search="<?= esc($searchStringH, 'attr') ?>">
                                    <td class="text-center font-bold text-slate-400 text-xs num-tabular"><?= $noH++ ?></td>

                                    <!-- Guru -->
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="<?= esc($avatarH, 'attr') ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-sm flex-shrink-0" alt="Avatar" loading="lazy">
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 text-sm leading-tight truncate"><?= esc($namaH) ?></div>
                                                <div class="text-xs text-slate-400 mt-0.5">NIP: <?= esc($h->nip ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tanggal -->
                                    <td>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5 text-xs text-slate-700 font-semibold">
                                                <i class="bi bi-calendar-check text-slate-400"></i>
                                                <span><?= date('d M Y', strtotime($h->tgl)) ?></span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-slate-500 num-tabular">
                                                <span>Masuk: <?= !empty($h->jam_masuk) ? date('H:i', strtotime($h->jam_masuk)) : '-' ?></span>
                                                <?php if (!empty($h->jam_pulang)): ?>
                                                    <span class="text-slate-300">|</span>
                                                    <span>Plg: <?= date('H:i', strtotime($h->jam_pulang)) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Lokasi -->
                                    <td>
                                        <div class="text-xs space-y-1">
                                            <?php if (!empty($h->lat_masuk) && !empty($h->lng_masuk)): ?>
                                                <a href="https://maps.google.com/?q=<?= esc($h->lat_masuk) ?>,<?= esc($h->lng_masuk) ?>" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 font-mono flex items-center gap-1">
                                                    <i class="bi bi-geo-alt-fill text-red-500"></i>
                                                    <?= esc(round($h->lat_masuk, 5)) ?>, <?= esc(round($h->lng_masuk, 5)) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-400">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="text-center">
                                        <?php if ($isApproved): ?>
                                            <span class="badge-tw badge-success rounded-full text-[10px] px-2.5 py-0.5 inline-flex items-center gap-1">
                                                <i class="bi bi-check-circle-fill"></i> Disetujui
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-danger rounded-full text-[10px] px-2.5 py-0.5 inline-flex items-center gap-1">
                                                <i class="bi bi-x-circle-fill"></i> Ditolak
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Foto -->
                                    <td class="text-center">
                                        <?php if ($fotoAbsenH): ?>
                                            <button type="button" class="btn-view-foto relative group cursor-pointer inline-block border-none bg-transparent p-0"
                                                data-foto="<?= esc($fotoAbsenH, 'attr') ?>" data-nama="<?= esc($namaH, 'attr') ?>" title="Lihat foto">
                                                <img src="<?= esc($fotoAbsenH, 'attr') ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm" alt="Foto" loading="lazy">
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-300 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Updated At -->
                                    <td class="text-right pr-6 text-xs text-slate-400 num-tabular">
                                        <?= !empty($h->updated_at) ? date('d/m/Y H:i', strtotime($h->updated_at)) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div><!-- /page wrapper -->

<!-- Modal Preview Foto Absen -->
<div id="modalFotoDinas" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="modal-header-tw border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                        <i class="bi bi-camera"></i>
                    </div>
                    <div>
                        <h5 class="font-bold text-slate-800 text-sm leading-tight" id="modalFotoTitle">Foto Kehadiran Dinas</h5>
                        <span class="text-xs text-slate-400">Bukti potret saat presensi dinas luar</span>
                    </div>
                </div>
                <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" data-ui-dismiss="modal">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="modal-body-tw p-6 text-center bg-slate-50/50">
                <img id="previewFotoImg" src="" class="max-h-96 w-auto mx-auto rounded-xl border border-slate-200 shadow-sm object-contain" alt="Foto Absen">
            </div>
            <div class="modal-footer-tw border-t border-slate-100 px-6 py-3 bg-slate-50 flex items-center justify-between">
                <a id="btnBukaTabFoto" href="" target="_blank" class="btn-tw btn-light text-xs border">
                    <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Gambar Penuh
                </a>
                <button type="button" class="btn-tw btn-light text-xs" data-ui-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak Dinas -->
<div id="modalRejectDinas" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="rejectDinasForm" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <h5><i class="bi bi-x-circle mr-2"></i>Tolak Dinas Luar</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" id="rejectDinasId">
                <div class="alert-tw alert-info mb-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>Pengajuan milik <strong id="rejectDinasNama">–</strong> akan ditolak dan poinnya direset ke 0.</div>
                </div>
                <label class="form-label-tw" for="rejectDinasAlasan">Alasan penolakan <span class="text-red-500">*</span></label>
                <input type="text" class="form-control-tw" id="rejectDinasAlasan" minlength="5" maxlength="500" required placeholder="Contoh: Surat tugas tidak dilampirkan / lokasi tidak sesuai">
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-tw-sm btn-danger" id="btnSubmitRejectDinas"><i class="bi bi-x-lg mr-1"></i>Konfirmasi Tolak</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>
<script>
$(document).ready(function() {
    var currentDinasTab = 'pending';

    function switchDinasTab(tab) {
        currentDinasTab = (tab === 'history') ? 'history' : 'pending';
        var isPending = currentDinasTab === 'pending';
        $('#tabPendingBtn').toggleClass('active', isPending).toggleClass('text-slate-500', !isPending);
        $('#tabHistoryBtn').toggleClass('active', !isPending).toggleClass('text-slate-500', isPending);
        $('#sectionPending').toggleClass('hidden', !isPending);
        $('#sectionHistory').toggleClass('hidden', isPending);
        filterDinasTable();
    }

    function filterDinasTable() {
        var keyword = ($('#searchDinasInput').val() || '').toLowerCase().trim();
        var targetTable = (currentDinasTab === 'pending') ? '#tablePending' : '#tableHistory';
        var visible = 0;

        $(targetTable + ' tbody tr.dinas-row').each(function() {
            var searchData = $(this).attr('data-search') || '';
            var show = !keyword || searchData.includes(keyword);
            $(this).toggle(show);
            if (show) visible++;
        });

        var emptyId = (currentDinasTab === 'pending') ? '#emptySearchPending' : '#emptySearchHistory';
        $(emptyId).remove();
        if (visible === 0 && keyword) {
            $(targetTable).after('<div id="' + emptyId.substring(1) + '" class="text-center py-8 text-slate-400 text-sm">Tidak ada hasil untuk "' + $('<div>').text(keyword).html() + '".</div>');
        }
    }

    function clearDinasSearch() {
        $('#searchDinasInput').val('');
        filterDinasTable();
        $('#searchDinasInput').focus();
    }

    function viewFotoModal(fotoUrl, nama) {
        // Add timestamp to prevent browser caching (only if not already present)
        if (fotoUrl && !fotoUrl.includes('?t=')) {
            fotoUrl = fotoUrl + '?t=' + Date.now();
        }
        $('#modalFotoTitle').text('Foto Dinas: ' + nama);
        $('#previewFotoImg').attr('src', fotoUrl);
        $('#btnBukaTabFoto').attr('href', fotoUrl);
        AdminUI.modal.open('#modalFotoDinas');
    }

    function setDinasLoading($btn, busy, html) {
        if (busy) {
            $btn.data('orig', $btn.html());
            $btn.prop('disabled', true).html('<span class="spinner-tw spinner-tw-lite"></span> Memproses...');
        } else {
            $btn.prop('disabled', false).html($btn.data('orig') || html);
        }
    }

    function approveDinas(id, btn) {
        var $btn = btn ? $(btn) : $('.js-approve-dinas[data-id="' + id + '"]');
        if ($btn.is(':disabled')) return;
        Swal.fire({
            title: 'Setujui Dinas Luar?',
            text: 'Absensi akan diverifikasi sah sebagai Dinas Luar dan poin kehadiran akan diberikan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setDinasLoading($btn, true);
            var data = {};
            data['<?= $csrfName ?>'] = '<?= $csrfHash ?>';

            $.post('<?= site_url('admin/presensi/approve-dinas') ?>/' + id, data, function (res) {
                if (res.status) {
                    Swal.fire({ title: 'Berhasil!', text: res.message || 'Permintaan dinas berhasil disetujui.', icon: 'success', timer: 1500, showConfirmButton: false })
                        .then(function() { location.reload(); });
                } else {
                    Swal.fire('Gagal Menyetujui', res.message || 'Terjadi kesalahan sistem.', 'error');
                    setDinasLoading($btn, false);
                }
            }, 'json').fail(function() {
                Swal.fire('Error Sistem', 'Gagal menghubungi server.', 'error');
                setDinasLoading($btn, false);
            });
        });
    }

    function openRejectDinas(id, nama) {
        $('#rejectDinasId').val(id);
        $('#rejectDinasNama').text(nama || '-');
        $('#rejectDinasAlasan').val('');
        AdminUI.modal.open('#modalRejectDinas');
    }

    function submitRejectDinas(e) {
        e.preventDefault();
        var form = $('#rejectDinasForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return false; }
        var id = $('#rejectDinasId').val();
        var btn = $('#btnSubmitRejectDinas');
        var orig = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-tw"></span> Memproses...');
        $.post('<?= site_url('admin/presensi/reject-dinas') ?>/' + id, {
            alasan: $('#rejectDinasAlasan').val(),
            '<?= $csrfName ?>': '<?= $csrfHash ?>'
        }, function (res) {
            if (res.status) {
                AdminUI.modal.close('#modalRejectDinas');
                Swal.fire({ title: 'Dinas Ditolak', text: res.message || 'Permintaan dinas telah ditolak.', icon: 'success', timer: 1500, showConfirmButton: false })
                    .then(function() { location.reload(); });
            } else {
                Swal.fire('Gagal Memproses', res.message || 'Terjadi kesalahan sistem.', 'error');
                btn.prop('disabled', false).html(orig);
            }
        }, 'json').fail(function() {
            Swal.fire('Error Sistem', 'Gagal menghubungi server.', 'error');
            btn.prop('disabled', false).html(orig);
        });
        return false;
    }

    // ── Delegated bindings (ganti onclick inline) ──
    $('#dinasTabs').on('click', '[data-dtab]', function () { switchDinasTab($(this).data('dtab')); });
    $('#searchDinasInput').on('keyup input', filterDinasTable);
    $('#clearDinasSearch').on('click', clearDinasSearch);
    $('#rejectDinasForm').on('submit', submitRejectDinas);
    $(document).on('click', '.js-approve-dinas', function () { approveDinas($(this).data('id'), this); });
    $(document).on('click', '.js-reject-dinas', function () { openRejectDinas($(this).data('id'), $(this).data('nama')); });
    $(document).on('click', '.btn-view-foto', function () { viewFotoModal($(this).data('foto'), $(this).data('nama')); });
    $(document).on('click', '.btn-open-maps', function () {
        window.open('https://maps.google.com/?q=' + encodeURIComponent($(this).data('lat')) + ',' + encodeURIComponent($(this).data('lng')), '_blank', 'noopener');
    });

    window.switchDinasTab = switchDinasTab;
    window.filterDinasTable = filterDinasTable;
    window.clearDinasSearch = clearDinasSearch;
    window.viewFotoModal = viewFotoModal;
    window.approveDinas = approveDinas;
    window.openRejectDinas = openRejectDinas;
    window.submitRejectDinas = submitRejectDinas;
});
</script>
<?= $this->endSection() ?>