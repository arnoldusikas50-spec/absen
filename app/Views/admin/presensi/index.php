<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card-widget { transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card-widget:hover { transform: translateY(-3px); box-shadow: 0 14px 28px -8px rgba(0, 0, 0, 0.12); }

    .filter-btn-pill {
        border-radius: 9999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 700;
        transition: all 0.15s;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    .filter-btn-pill.active { box-shadow: 0 3px 10px -2px rgb(var(--primary-500-rgb) / 0.3); }

    .avatar-teacher { width: 38px; height: 38px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }

    .table-sticky-wrap { max-height: 70vh; overflow: auto; }
    .table-sticky-wrap thead th { position: sticky; top: 0; z-index: 20; box-shadow: 0 1px 0 #e2e8f0; }

    .sort-btn { cursor: pointer; user-select: none; display: inline-flex; align-items: center; gap: 0.25rem; }
    .sort-btn .sort-icon { opacity: 0.35; font-size: 10px; }
    .sort-btn:hover { color: #2563eb; }
    .sort-btn.active { color: #2563eb; }
    .sort-btn.active .sort-icon { opacity: 1; }

    .teacher-row { transition: background 0.1s; }

    @media (min-width: 768px) {
        .col-sticky-no {
            position: sticky;
            left: 0;
            z-index: 5;
            background: #fff;
            box-shadow: 1px 0 0 #e2e8f0;
        }
        .col-sticky-guru {
            position: sticky;
            left: 48px;
            z-index: 5;
            background: #fff;
            box-shadow: 1px 0 0 #e2e8f0;
        }
        thead .col-sticky-no,
        thead .col-sticky-guru { z-index: 30; background: #f8fafc; }
        tbody tr:hover .col-sticky-no,
        tbody tr:hover .col-sticky-guru { background: #eff6ff; }
    }

    /* Tailwind punya utility .collapse{visibility:collapse}; .show dari
       tailwind-admin hanya mengubah display sehingga isi tetap tak terlihat. */
    #collapseAlertKehadiran.show { visibility: visible; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$jamSekarang = (int) date('H');
$salam = $jamSekarang < 11 ? 'Selamat Pagi' : ($jamSekarang < 15 ? 'Selamat Siang' : ($jamSekarang < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

$tanggalLabel = str_replace(
    ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
    buat_tanggal(date('l, d F Y', strtotime($date)))
);

$hariIcon = $hariStatus === 'aktif' ? 'bi-briefcase-fill' : ($hariStatus === 'libur' ? 'bi-calendar-x' : 'bi-calendar-week');
$hariLabel = $hariStatus === 'aktif' ? 'Hari Kerja' : ($hariStatus === 'libur' ? 'Libur Sekolah' : 'Akhir Pekan');

$bulanAngka = (int) date('n', strtotime($bulanAwal));
$bulanTahun = (int) date('Y', strtotime($bulanAwal));
$bulanLabel = buat_tanggal(date('F Y', strtotime($bulanAwal)));
$rekapUrl = site_url('admin/presensi/rekap?bulan=' . $bulanAngka . '&tahun=' . $bulanTahun);

$persenTerverifikasi = $totalGuru > 0 ? round(($hadirTerverifikasi / $totalGuru) * 100, 1) : 0;
$belumAbsenHari = (int) ($rekapHari['belum_absen'] ?? 0);

$pendingItems = [
    [
        'key' => 'dinas',
        'label' => 'Dinas Luar',
        'count' => (int) ($pendingReview['dinas'] ?? 0),
        'url' => site_url('admin/presensi/dinas'),
        'icon' => 'bi-briefcase-fill',
        'class' => 'text-indigo-700 bg-indigo-50 border-indigo-200 hover:bg-indigo-100',
        'num' => 'text-indigo-700',
    ],
    [
        'key' => 'koreksi',
        'label' => 'Koreksi Absen',
        'count' => (int) ($pendingReview['koreksi'] ?? 0),
        'url' => site_url('admin/presensi/koreksi'),
        'icon' => 'bi-pencil-square',
        'class' => 'text-amber-700 bg-amber-50 border-amber-200 hover:bg-amber-100',
        'num' => 'text-amber-700',
    ],
    [
        'key' => 'izin',
        'label' => 'Izin / Cuti',
        'count' => (int) ($pendingReview['izin'] ?? 0),
        'url' => site_url('admin/presensi/izin'),
        'icon' => 'bi-envelope-paper-fill',
        'class' => 'text-sky-700 bg-sky-50 border-sky-200 hover:bg-sky-100',
        'num' => 'text-sky-700',
    ],
    [
        'key' => 'verifikasi',
        'label' => 'Verifikasi Presensi',
        'count' => (int) ($pendingReview['verifikasi'] ?? 0),
        'url' => site_url('admin/presensi/verifikasi'),
        'icon' => 'bi-patch-check-fill',
        'class' => 'text-emerald-700 bg-emerald-50 border-emerald-200 hover:bg-emerald-100',
        'num' => 'text-emerald-700',
    ],
    [
        'key' => 'wajah',
        'label' => 'Registrasi Wajah',
        'count' => (int) ($pendingReview['wajah'] ?? 0),
        'url' => site_url('admin/presensi/wajah'),
        'icon' => 'bi-person-bounding-box',
        'class' => 'text-purple-700 bg-purple-50 border-purple-200 hover:bg-purple-100',
        'num' => 'text-purple-700',
    ],
];

$statusRankMap = [
    'HADIR' => 1,
    'TERLAMBAT' => 2,
    'VERIFIKASI' => 3,
    'IZIN_SAKIT' => 4,
    'DINAS' => 5,
    'WFH' => 6,
    'ALPHA' => 7,
    'DITOLAK' => 8,
    'LIBUR' => 9,
    'FUTURE' => 10,
    'LAINNYA' => 11,
];

$fotoUrl = function ($path) {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (strpos($path, 'uploads/') === false) {
        $path = 'uploads/absen/' . $path;
    }
    $absolute = FCPATH . $path;
    $version = file_exists($absolute) ? filemtime($absolute) : time();
    return base_url($path) . '?v=' . $version;
};
?>

<div class="fade-in-up pb-8">
    <!-- Header -->
    <div class="px-6 pt-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                    <i class="bi bi-calendar2-check-fill text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800 tracking-tight leading-none">Monitoring Presensi Guru</h1>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <?= $salam ?> &middot; Pantau kehadiran, keterlambatan, antrean verifikasi, dan rekap bulanan
                    </p>
                </div>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="flex items-center gap-1.5 text-xs font-semibold bg-white px-3.5 py-2 rounded-xl border border-slate-200/80">
                    <li><a href="<?= base_url('dashboard') ?>" class="text-slate-500 hover:text-blue-600 transition-colors no-underline">Dashboard</a></li>
                    <li class="text-slate-300">/</li>
                    <li class="text-blue-600">Monitoring Presensi</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Tanggal & aksi -->
    <div class="px-6 pt-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm px-4 py-3">
            <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                        <a href="?date=<?= $prevDate ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm transition-all no-underline" title="Hari sebelumnya">
                            <i class="bi bi-chevron-left text-sm"></i>
                        </a>
                        <a href="?date=<?= date('Y-m-d') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all no-underline <?= $isToday ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-700 hover:bg-white' ?>">
                            <i class="bi bi-calendar-check mr-1"></i>Hari Ini
                        </a>
                        <a href="?date=<?= $nextDate ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm transition-all no-underline" title="Hari berikutnya">
                            <i class="bi bi-chevron-right text-sm"></i>
                        </a>
                    </div>

                    <form action="" method="get" class="flex items-center">
                        <div class="relative">
                            <input type="date" name="date"
                                class="pl-8 pr-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all cursor-pointer"
                                value="<?= $date ?>" onchange="this.form.submit()">
                            <i class="bi bi-calendar3 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </form>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold <?= $hariStatus === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                        <i class="bi <?= $hariIcon ?>"></i>
                        <?= $tanggalLabel ?>
                        <span class="opacity-70">&middot; <?= $hariLabel ?></span>
                    </span>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" data-ui-toggle="modal" data-ui-target="#policyModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 rounded-xl hover:bg-sky-100 transition-colors">
                        <i class="bi bi-shield-check"></i> Kebijakan
                    </button>
                    <a href="<?= $rekapUrl ?>"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors no-underline">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i> Rekap Bulan
                    </a>
                    <a href="<?= site_url('admin/presensi/manual') ?>"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors no-underline shadow-sm">
                        <i class="bi bi-plus-circle-fill"></i> Absen Manual
                    </a>
                    <button type="button" onclick="recalculateDayStatus()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition-colors"
                        title="Hitung ulang status presensi (tepat waktu/terlambat/denda) memakai toleransi dan jadwal aktif">
                        <i class="bi bi-arrow-repeat"></i> Hitung Ulang
                    </button>
                    <button type="button" onclick="location.reload()"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 bg-slate-50 border border-slate-200 hover:bg-slate-100 transition-colors"
                        title="Segarkan">
                        <i class="bi bi-arrow-clockwise text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pemberitahuan kontekstual -->
    <?php if ($isFuture): ?>
        <div class="px-6 pt-4">
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 flex items-start gap-3" role="status">
                <i class="bi bi-calendar-plus-fill text-sky-600 text-lg shrink-0 mt-0.5"></i>
                <div class="text-xs text-sky-900 leading-relaxed">
                    <b>Tanggal ini belum berjalan.</b> Presensi belum dapat diisi, sehingga guru tanpa data tidak dihitung sebagai alpha atau belum absen.
                </div>
            </div>
        </div>
    <?php elseif (!$isWorkingDay): ?>
        <div class="px-6 pt-4">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-start gap-3" role="status">
                <i class="bi bi-calendar-x-fill text-amber-600 text-lg shrink-0 mt-0.5"></i>
                <div class="text-xs text-amber-900 leading-relaxed">
                    <b><?= esc($hariLabel) ?><?= $hariKeterangan ? ' — ' . esc($hariKeterangan) : '' ?>.</b>
                    Tidak ada kewajiban presensi pada tanggal ini; guru tanpa data tidak dihitung sebagai alpha atau belum absen.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($peringatanKalender)): ?>
        <div class="px-6 pt-4">
            <div class="rounded-2xl border border-amber-200 bg-white px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3" role="alert">
                <div class="flex items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-amber-500 text-lg shrink-0 mt-0.5"></i>
                    <div class="text-xs text-slate-700 leading-relaxed">
                        <b class="text-amber-800">Kalender libur perlu diperiksa.</b> <?= esc($peringatanKalender) ?>
                    </div>
                </div>
                <a href="<?= site_url('admin/kalender') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition-colors no-underline shrink-0">
                    <i class="bi bi-calendar3"></i> Buka Kalender
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 px-6 pt-4">
        <div class="stat-card-widget bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Guru Aktif</p>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-800 leading-none"><?= (int) $totalGuru ?></h3>
            <p class="text-[11px] text-slate-400 mt-2">Guru aktif terdaftar di sistem</p>
        </div>

        <div class="stat-card-widget bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hadir Terverifikasi</p>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2 leading-none">
                <h3 class="text-3xl font-extrabold text-emerald-600"><?= (int) $hadirTerverifikasi ?></h3>
                <span class="text-[11px] font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full"><?= $persenTerverifikasi ?>%</span>
            </div>
            <div class="mt-2 h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all" style="width:<?= min(100, $persenTerverifikasi) ?>%"></div>
            </div>
            <p class="text-[11px] mt-1.5">
                <?php if ($isFuture): ?>
                    <span class="text-slate-400 font-semibold">Belum berjalan</span>
                <?php elseif (!$isWorkingDay): ?>
                    <span class="text-slate-400 font-semibold">Hari libur — tidak ada kewajiban presensi</span>
                <?php elseif ($menungguVerifikasi > 0): ?>
                    <a href="<?= site_url('admin/presensi/verifikasi') ?>" class="text-amber-600 font-bold no-underline hover:underline">
                        <?= (int) $menungguVerifikasi ?> menunggu verifikasi &rarr;
                    </a>
                <?php elseif ($belumAbsenHari > 0): ?>
                    <button type="button" onclick="filterBelumAbsen()" class="text-rose-600 font-bold bg-transparent border-0 p-0 hover:underline">
                        <?= $belumAbsenHari ?> guru belum absen &rarr;
                    </button>
                <?php else: ?>
                    <span class="text-emerald-600 font-semibold">Semua guru sudah tercatat &check;</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="stat-card-widget bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-sky-500 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Izin / Sakit / Cuti</p>
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-envelope-paper-fill"></i>
                </div>
            </div>
            <h3 class="text-3xl font-extrabold text-sky-600 leading-none"><?= (int) $izinSah ?></h3>
            <p class="text-[11px] text-slate-400 mt-2">
                Izin <?= (int) ($rekapHari['izin'] ?? 0) ?> &middot; Sakit <?= (int) ($rekapHari['sakit'] ?? 0) ?> &middot; Cuti <?= (int) ($rekapHari['cuti'] ?? 0) ?>
            </p>
        </div>

        <div class="stat-card-widget bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-rose-500 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tidak Hadir</p>
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-person-x-fill"></i>
                </div>
            </div>
            <h3 class="text-3xl font-extrabold <?= $tidakHadir > 0 ? 'text-rose-600' : 'text-slate-400' ?> leading-none"><?= (int) $tidakHadir ?></h3>
            <p class="text-[11px] mt-2">
                <?php if ($belumAbsenHari > 0): ?>
                    <button type="button" onclick="filterBelumAbsen()" class="text-rose-600 font-bold bg-transparent border-0 p-0 hover:underline">
                        Lihat <?= $belumAbsenHari ?> guru belum absen &rarr;
                    </button>
                <?php elseif ((int) ($rekapHari['ditolak'] ?? 0) > 0): ?>
                    <span class="text-rose-500 font-semibold"><?= (int) $rekapHari['ditolak'] ?> pengajuan dinas ditolak</span>
                <?php else: ?>
                    <span class="text-slate-400">Tidak ada ketidakhadiran tercatat</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="stat-card-widget col-span-2 lg:col-span-1 bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-amber-500 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Telat <?= esc($bulanLabel) ?></p>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <h3 class="text-3xl font-extrabold text-amber-500 leading-none"><?= (int) $telatBulanIni ?></h3>
            <p class="text-[11px] mt-2">
                <span class="text-slate-400">Kejadian telat bulan ini &middot;</span>
                <a href="<?= $rekapUrl ?>" class="text-amber-600 font-bold no-underline hover:underline">
                    Rekap &rarr;
                </a>
            </p>
        </div>
    </div>

    <!-- Antrean persetujuan -->
    <div class="px-6 pt-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl <?= $pendingReview['total'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center shrink-0">
                        <i class="bi bi-inbox-fill text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 leading-none">Antrean Persetujuan</h2>
                        <p class="text-[11px] text-slate-400 mt-1">Seluruh pengajuan yang menunggu tindakan admin</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-extrabold <?= $pendingReview['total'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700' ?>">
                    <i class="bi <?= $pendingReview['total'] > 0 ? 'bi-hourglass-split' : 'bi-check2-circle' ?>"></i>
                    <?= $pendingReview['total'] > 0 ? $pendingReview['total'] . ' menunggu' : 'Tidak ada antrean' ?>
                </span>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 mt-4">
                <?php foreach ($pendingItems as $item): ?>
                    <?php $hasPending = $item['count'] > 0; ?>
                    <a href="<?= $item['url'] ?>" class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl border transition-colors no-underline <?= $hasPending ? $item['class'] : 'text-slate-400 bg-slate-50 border-slate-200 hover:bg-slate-100' ?>">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold min-w-0">
                            <i class="bi <?= $item['icon'] ?> shrink-0"></i>
                            <span class="truncate"><?= esc($item['label']) ?></span>
                        </span>
                        <span class="text-sm font-extrabold shrink-0 <?= $hasPending ? $item['num'] : 'text-slate-400' ?>"><?= $item['count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tabel monitoring -->
    <div class="px-6 pt-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-table text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Daftar Presensi Guru</h2>
                            <p class="text-xs text-slate-400">
                                <?= $tanggalLabel ?> &middot; <span id="tableCount" class="font-semibold text-slate-600">Memuat data...</span>
                            </p>
                        </div>
                    </div>

                    <div class="relative min-w-[260px]">
                        <input type="text" id="tableSearch"
                               class="w-full pl-9 pr-8 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all placeholder:text-slate-400"
                               placeholder="Cari nama atau NIP guru..." autocomplete="off">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        <button type="button" id="clearSearchBtn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm leading-none" title="Hapus pencarian">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pt-4 mt-3 border-t border-slate-100 flex-wrap text-xs">
                    <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] shrink-0 mr-1">
                        <i class="bi bi-funnel mr-0.5"></i> Status:
                    </span>
                    <button type="button" class="filter-btn-pill bg-slate-100 text-slate-700 hover:bg-slate-200 active" data-filter="all">Semua</button>
                    <button type="button" class="filter-btn-pill bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-200" data-filter="HADIR">
                        <i class="bi bi-check-circle-fill text-emerald-500"></i> Hadir
                    </button>
                    <button type="button" class="filter-btn-pill bg-amber-50 text-amber-700 hover:bg-amber-100 border-amber-200" data-filter="TERLAMBAT">
                        <i class="bi bi-clock-history text-amber-500"></i> Terlambat
                    </button>
                    <button type="button" class="filter-btn-pill bg-sky-50 text-sky-700 hover:bg-sky-100 border-sky-200" data-filter="IZIN_SAKIT">
                        <i class="bi bi-envelope-paper-fill text-sky-500"></i> Izin / Sakit
                    </button>
                    <button type="button" class="filter-btn-pill bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-200" data-filter="DINAS">
                        <i class="bi bi-briefcase-fill text-indigo-500"></i> Dinas Luar
                    </button>
                    <button type="button" class="filter-btn-pill bg-teal-50 text-teal-700 hover:bg-teal-100 border-teal-200" data-filter="WFH">
                        <i class="bi bi-laptop text-teal-500"></i> WFH
                    </button>
                    <button type="button" class="filter-btn-pill bg-amber-50 text-amber-700 hover:bg-amber-100 border-amber-200" data-filter="VERIFIKASI">
                        <i class="bi bi-patch-question-fill text-amber-500"></i> Verifikasi
                    </button>
                    <button type="button" class="filter-btn-pill bg-rose-50 text-rose-700 hover:bg-rose-100 border-rose-200" data-filter="ALPHA">
                        <i class="bi bi-x-circle-fill text-rose-500"></i> Belum Absen / Alpha
                    </button>
                </div>
            </div>

            <div class="table-sticky-wrap">
                <table class="w-full text-left border-collapse" id="monitoringTable" style="font-size: 0.8125rem;">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[11px] tracking-wider select-none">
                        <tr>
                            <th class="px-2 py-3.5 text-center col-sticky-no" style="width: 48px; min-width: 48px;">No</th>
                            <th class="px-4 py-3.5 col-sticky-guru" style="min-width: 230px;">
                                <button type="button" class="sort-btn" data-sort-key="name">
                                    Guru <i class="bi bi-arrow-down-up sort-icon"></i>
                                </button>
                            </th>
                            <th class="px-3 py-3.5 text-center" style="width: 92px;">
                                <button type="button" class="sort-btn" data-sort-key="in">
                                    Masuk <i class="bi bi-arrow-down-up sort-icon"></i>
                                </button>
                            </th>
                            <th class="px-3 py-3.5 text-center" style="width: 92px;">
                                <button type="button" class="sort-btn" data-sort-key="out">
                                    Pulang <i class="bi bi-arrow-down-up sort-icon"></i>
                                </button>
                            </th>
                            <th class="px-3 py-3.5 text-center" style="width: 118px;">
                                <button type="button" class="sort-btn" data-sort-key="status">
                                    Status <i class="bi bi-arrow-down-up sort-icon"></i>
                                </button>
                            </th>

                            <th class="px-2 py-3.5 text-center bg-slate-100 border-l border-r border-slate-200/80 text-emerald-700" style="width: 34px;" title="Hadir (tepat waktu) bulan <?= esc($bulanLabel, 'attr') ?>">H</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-amber-700" style="width: 34px;" title="Terlambat bulan <?= esc($bulanLabel, 'attr') ?>">T</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-sky-700" style="width: 34px;" title="Izin bulan <?= esc($bulanLabel, 'attr') ?>">I</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-sky-700" style="width: 34px;" title="Sakit bulan <?= esc($bulanLabel, 'attr') ?>">S</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-purple-700" style="width: 34px;" title="Cuti bulan <?= esc($bulanLabel, 'attr') ?>">C</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-indigo-700" style="width: 34px;" title="Dinas luar bulan <?= esc($bulanLabel, 'attr') ?>">D</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-teal-700" style="width: 34px;" title="WFH bulan <?= esc($bulanLabel, 'attr') ?>">W</th>
                            <th class="px-2 py-3.5 text-center bg-slate-100 border-r border-slate-200/80 text-rose-700" style="width: 34px;" title="Alpha (tercatat + belum tercatat) bulan <?= esc($bulanLabel, 'attr') ?>">A</th>

                            <th class="px-3 py-3.5 text-center bg-blue-50/70 border-r border-slate-200/80" style="width: 106px;">
                                <button type="button" class="sort-btn text-blue-700" data-sort-key="denda">
                                    Denda <i class="bi bi-arrow-down-up sort-icon"></i>
                                </button>
                            </th>
                            <th class="px-3 py-3.5 text-center" style="width: 64px;" title="Poin harian dari presensi">Poin</th>
                            <th class="px-3 py-3.5 text-center" style="width: 100px;">Lokasi</th>
                            <th class="px-3 py-3.5 text-center" style="width: 64px;">Bukti</th>
                            <th class="px-3 py-3.5 text-center" style="width: 150px;">Keterangan</th>
                            <th class="px-3 py-3.5 text-center" style="width: 88px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="19" class="px-6 py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                                        <i class="bi bi-people text-2xl"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-700">Belum ada guru aktif</h4>
                                    <p class="text-xs mt-1">Tambahkan pengguna dengan role guru terlebih dahulu.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php
                        $no = 1;
                        foreach ($users as $guruRow):
                            $att = $map[$guruRow->id] ?? null;
                            $stat = $rekapBulanan[$guruRow->id] ?? [
                                'hadir' => 0, 'telat' => 0, 'ijin' => 0, 'sakit' => 0,
                                'cuti' => 0, 'dinas' => 0, 'wfh' => 0, 'alpha' => 0, 'pending' => 0,
                            ];

                            $statusRaw = $att ? strtoupper(trim((string) ($att->status_masuk ?? ''))) : null;

                            $filterCategory = 'LAINNYA';
                            $statusLabel = 'Tidak diketahui';
                            $badgeClass = 'bg-slate-100 text-slate-600 border border-slate-200';

                            if ($att === null) {
                                if ($isFuture) {
                                    $filterCategory = 'FUTURE';
                                    $statusLabel = 'Masa Depan';
                                    $badgeClass = 'bg-slate-100 text-slate-500 border border-slate-200';
                                } elseif (!$isWorkingDay) {
                                    $filterCategory = 'LIBUR';
                                    $statusLabel = 'Libur';
                                    $badgeClass = 'bg-slate-100 text-slate-500 border border-slate-200';
                                } else {
                                    $filterCategory = 'ALPHA';
                                    $statusLabel = 'Belum Absen';
                                    $badgeClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                                }
                            } else {
                                switch ($statusRaw) {
                                    case 'ONTIME':
                                        $filterCategory = 'HADIR';
                                        $statusLabel = 'Hadir';
                                        $badgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                                        break;
                                    case 'LATE':
                                        $filterCategory = 'TERLAMBAT';
                                        $statusLabel = 'Terlambat';
                                        $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';
                                        break;
                                    case 'IJIN':
                                    case 'IZIN':
                                        $filterCategory = 'IZIN_SAKIT';
                                        $statusLabel = 'Izin';
                                        $badgeClass = 'bg-sky-100 text-sky-800 border border-sky-200';
                                        break;
                                    case 'SAKIT':
                                        $filterCategory = 'IZIN_SAKIT';
                                        $statusLabel = 'Sakit';
                                        $badgeClass = 'bg-sky-100 text-sky-800 border border-sky-200';
                                        break;
                                    case 'CUTI':
                                        $filterCategory = 'IZIN_SAKIT';
                                        $statusLabel = 'Cuti';
                                        $badgeClass = 'bg-purple-100 text-purple-800 border border-purple-200';
                                        break;
                                    case 'ALPHA':
                                    case 'ALPA':
                                        $filterCategory = 'ALPHA';
                                        $statusLabel = 'Alpha';
                                        $badgeClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                                        break;
                                    case 'PENDING_VERIFICATION':
                                        $filterCategory = 'VERIFIKASI';
                                        $statusLabel = 'Menunggu Verifikasi';
                                        $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';
                                        break;
                                    case 'DINAS_REJECTED':
                                        $filterCategory = 'DITOLAK';
                                        $statusLabel = 'Dinas Ditolak';
                                        $badgeClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                                        break;
                                    case 'WFH':
                                        $filterCategory = 'WFH';
                                        $statusLabel = 'WFH';
                                        $badgeClass = 'bg-teal-100 text-teal-800 border border-teal-200';
                                        break;
                                    case 'DINAS_OUT':
                                        $filterCategory = 'DINAS';
                                        $statusLabel = 'Dinas (Pulang)';
                                        $badgeClass = 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                                        break;
                                    default:
                                        if (strpos((string) $statusRaw, 'DINAS') !== false) {
                                            $filterCategory = 'DINAS';
                                            $statusLabel = $statusRaw === 'DINAS_PENDING' ? 'Dinas (Menunggu)' : 'Dinas';
                                            $badgeClass = 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                                        } elseif ($statusRaw !== '') {
                                            $filterCategory = 'LAINNYA';
                                            $statusLabel = ucwords(strtolower(str_replace('_', ' ', $statusRaw)));
                                            $badgeClass = 'bg-slate-100 text-slate-600 border border-slate-200';
                                        }
                                }
                            }

                            $statusRank = $statusRankMap[$filterCategory] ?? 11;

                            $jamMasuk = ($att && !empty($att->jam_masuk)) ? date('H:i', strtotime($att->jam_masuk)) : '';
                            $jamPulang = ($att && !empty($att->jam_pulang)) ? date('H:i', strtotime($att->jam_pulang)) : '';
                            $telatMenit = (int) ($att->telat_menit ?? 0);
                            $pulangCepat = (int) ($att->pulang_cepat_menit ?? 0);
                            $lemburMenit = (int) ($att->pulang_akhir_menit ?? 0);
                            $poinHarian = $att ? (float) ($att->points ?? 0) : 0.0;

                            $latIn = $att->lat_masuk ?? null;
                            $lngIn = $att->lng_masuk ?? null;
                            if ((!$latIn || !$lngIn) && !empty($att->lokasi_masuk)) {
                                $coords = explode(',', (string) $att->lokasi_masuk);
                                $latIn = $latIn ?: trim($coords[0] ?? '');
                                $lngIn = $lngIn ?: trim($coords[1] ?? '');
                            }

                            $latOut = $att->lat_pulang ?? null;
                            $lngOut = $att->lng_pulang ?? null;
                            if ((!$latOut || !$lngOut) && !empty($att->lokasi_pulang)) {
                                $coordsOut = explode(',', (string) $att->lokasi_pulang);
                                $latOut = $latOut ?: trim($coordsOut[0] ?? '');
                                $lngOut = $lngOut ?: trim($coordsOut[1] ?? '');
                            }

                            $locInName = trim((string) ($att->nama_lokasi_masuk ?? ''));
                            $locOutName = trim((string) ($att->nama_lokasi_pulang ?? ''));

                            $fotoInUrl = $att ? $fotoUrl($att->foto_masuk ?? '') : '';
                            $fotoOutUrl = $att ? $fotoUrl($att->foto_pulang ?? '') : '';

                            $catatan = $att ? trim((string) ($att->keterangan ?? '')) : '';
                            $catatanDenda = $att ? trim((string) ($att->keterangan_denda ?? '')) : '';

                            $dendaHarianVal = (float) ($dendaMap[$guruRow->id] ?? 0);
                            $dendaEstimasiVal = (float) ($alphaDendaMap[$guruRow->id] ?? 0);
                            $dendaBulanVal = (float) ($dendaTotalMap[$guruRow->id] ?? 0);

                            $alphaBulan = max((int) ($stat['alpha'] ?? 0), (int) ($alphaMap[$guruRow->id] ?? 0));
                            $alphaMissing = (int) ($alphaMissingMap[$guruRow->id] ?? 0);

                            $dendaTooltip = 'Denda harian: Rp ' . number_format($dendaHarianVal, 0, ',', '.')
                                . ' · Estimasi alpha: Rp ' . number_format($dendaEstimasiVal, 0, ',', '.');
                            $alphaTooltip = 'Alpha tercatat: ' . (int) ($stat['alpha'] ?? 0) . ' hari'
                                . ($alphaMissing > 0 ? ' · Belum tercatat: ' . $alphaMissing . ' hari' : '');

                            $inStatusText = !$att ? 'Tidak ada data' : ($jamMasuk === '' ? 'Tidak ada data' : ($telatMenit > 0 ? 'Terlambat ' . $telatMenit . ' menit' : ($statusRaw === 'PENDING_VERIFICATION' ? 'Menunggu verifikasi' : ($statusRaw === 'DINAS_PENDING' ? 'Menunggu persetujuan dinas' : (strpos((string) $statusRaw, 'DINAS') !== false ? 'Dinas luar' : 'Tepat waktu')))));
                            $outStatusText = !$att ? 'Tidak ada data' : ($jamPulang === '' ? 'Belum absen pulang' : ($pulangCepat > 0 ? 'Pulang cepat ' . $pulangCepat . ' menit' : ($lemburMenit > 0 ? 'Lembur ' . $lemburMenit . ' menit' : 'Pulang tercatat')));

                            $keteranganHtml = '<span class="text-slate-300">-</span>';
                            if ($att && $telatMenit > 0) {
                                $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200"><i class="bi bi-arrow-up-right"></i> +' . $telatMenit . ' mnt</span>';
                            } elseif ($att && $pulangCepat > 0) {
                                $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200"><i class="bi bi-arrow-down-left"></i> -' . $pulangCepat . ' mnt</span>';
                            } elseif ($att && in_array($statusRaw, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true)) {
                                $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200" title="' . esc($catatan, 'attr') . '"><i class="bi bi-file-text"></i> ' . esc($statusLabel) . '</span>';
                            } elseif ($att && $statusRaw === 'PENDING_VERIFICATION') {
                                $keteranganHtml = '<a href="' . site_url('admin/presensi/verifikasi') . '" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 no-underline hover:bg-amber-100"><i class="bi bi-patch-question"></i> Verifikasi</a>';
                            } elseif ($att && $statusRaw === 'DINAS_REJECTED') {
                                $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="' . esc($catatan, 'attr') . '"><i class="bi bi-x-octagon"></i> Ditolak</span>';
                            } elseif ($att && strpos((string) $statusRaw, 'DINAS') !== false) {
                                $keteranganHtml = '<a href="' . site_url('admin/presensi/dinas') . '" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-200 no-underline hover:bg-indigo-100" title="' . esc($catatan ?: 'Tugas dinas luar', 'attr') . '"><i class="bi bi-briefcase"></i> Dinas Luar</a>';
                            } elseif ($att && $statusRaw === 'WFH') {
                                $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200" title="' . esc($catatan, 'attr') . '"><i class="bi bi-laptop"></i> Bekerja WFH</span>';
                            } elseif ($att && $statusRaw === 'ONTIME') {
                                $keteranganHtml = '<span class="text-emerald-600 text-[11px] font-bold inline-flex items-center gap-1"><i class="bi bi-check2"></i> Tepat waktu</span>';
                            } elseif (!$att && !$isFuture && $isWorkingDay) {
                                $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200"><i class="bi bi-dash-circle"></i> Belum absen</span>';
                            }

                            $dotClass = 'bg-slate-300';
                            if ($filterCategory === 'HADIR') $dotClass = 'bg-emerald-500';
                            elseif ($filterCategory === 'TERLAMBAT' || $filterCategory === 'VERIFIKASI') $dotClass = 'bg-amber-500';
                            elseif ($filterCategory === 'DINAS') $dotClass = 'bg-indigo-500';
                            elseif ($filterCategory === 'WFH') $dotClass = 'bg-teal-500';
                            elseif ($filterCategory === 'IZIN_SAKIT') $dotClass = 'bg-sky-500';
                            elseif ($filterCategory === 'ALPHA' || $filterCategory === 'DITOLAK') $dotClass = 'bg-rose-500';

                            $userAvatar = \App\Services\AvatarService::tampilan($guruRow->foto ?? '', $guruRow->nama_guru ?? '', '#3b82f6');
                            $avatarFallback = \App\Services\AvatarService::cadangan();

                            $detailData = [
                                'uid' => (int) $guruRow->id,
                                'name' => $guruRow->nama_guru,
                                'nip' => $guruRow->nip ?: '-',
                                'avatar' => $userAvatar,
                                'date' => date('d/m/Y', strtotime($date)),
                                'status' => $statusLabel,
                                'status_class' => $badgeClass,
                                'in' => $jamMasuk,
                                'out' => $jamPulang,
                                'in_status' => $inStatusText,
                                'out_status' => $outStatusText,
                                'late' => $telatMenit,
                                'early' => $pulangCepat,
                                'overtime' => $lemburMenit,
                                'points' => $poinHarian,
                                'denda_day' => (float) ($att->denda ?? 0),
                                'denda_month' => $dendaBulanVal,
                                'denda_note' => $catatanDenda,
                                'alpha' => $alphaBulan,
                                'alpha_missing' => $alphaMissing,
                                'loc_in' => $locInName,
                                'lat_in' => $latIn,
                                'lng_in' => $lngIn,
                                'loc_out' => $locOutName,
                                'lat_out' => $latOut,
                                'lng_out' => $lngOut,
                                'note' => $catatan,
                                'photo_in' => $fotoInUrl,
                                'photo_out' => $fotoOutUrl,
                                't_in' => $jamMasuk !== '' ? $jamMasuk . ' WITA' : '-',
                                't_out' => $jamPulang !== '' ? $jamPulang . ' WITA' : '-',
                                'has_photo' => ($fotoInUrl !== '' || $fotoOutUrl !== ''),
                            ];
                            $detailJson = json_encode($detailData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                            $sortIn = $jamMasuk !== '' ? ((int) substr($jamMasuk, 0, 2) * 60 + (int) substr($jamMasuk, 3, 2)) : '';
                            $sortOut = $jamPulang !== '' ? ((int) substr($jamPulang, 0, 2) * 60 + (int) substr($jamPulang, 3, 2)) : '';
                        ?>
                        <tr class="teacher-row hover:bg-blue-50/40 transition-colors"
                            data-status="<?= $filterCategory ?>"
                            data-name="<?= esc(strtolower((string) $guruRow->nama_guru), 'attr') ?>"
                            data-nip="<?= esc(strtolower((string) ($guruRow->nip ?? '')), 'attr') ?>"
                            data-sort-name="<?= esc(strtolower((string) $guruRow->nama_guru), 'attr') ?>"
                            data-sort-in="<?= $sortIn ?>"
                            data-sort-out="<?= $sortOut ?>"
                            data-sort-status="<?= (int) $statusRank ?>"
                            data-sort-denda="<?= $dendaBulanVal ?>">

                            <td class="px-2 py-3 text-center text-xs font-bold text-slate-400 col-sticky-no">
                                <span class="row-no"><?= $no++ ?></span>
                            </td>

                            <td class="px-4 py-3 col-sticky-guru">
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        <img src="<?= esc($userAvatar, 'attr') ?>" class="avatar-teacher border border-slate-200" alt="Avatar <?= esc($guruRow->nama_guru, 'attr') ?>" data-avatar-fallback="<?= esc($avatarFallback, 'attr') ?>" onerror="this.onerror=null;this.src=this.dataset.avatarFallback">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 <?= $dotClass ?> border-2 border-white rounded-full" title="<?= esc($statusLabel, 'attr') ?>"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 truncate" style="max-width: 220px;" title="<?= esc($guruRow->nama_guru, 'attr') ?>">
                                            <?= esc($guruRow->nama_guru) ?>
                                        </div>
                                        <div class="text-slate-400 text-[11px] truncate">
                                            NIP: <?= esc($guruRow->nip ?: '-') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <?php if ($jamMasuk !== ''): ?>
                                    <div class="font-extrabold text-slate-800 text-xs tracking-tight"><?= $jamMasuk ?></div>
                                    <?php if ($telatMenit > 0): ?>
                                        <span class="text-[10px] text-rose-500 font-bold">+<?= $telatMenit ?>m</span>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400 font-semibold">WITA</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-300 font-bold">--:--</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <?php if ($jamPulang !== ''): ?>
                                    <div class="font-extrabold text-slate-800 text-xs tracking-tight"><?= $jamPulang ?></div>
                                    <?php if ($pulangCepat > 0): ?>
                                        <span class="text-[10px] text-orange-500 font-bold">-<?= $pulangCepat ?>m</span>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400 font-semibold">WITA</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-300 font-bold">--:--</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $badgeClass ?>"><?= esc($statusLabel) ?></span>
                            </td>

                            <td class="px-2 py-3 text-center bg-slate-50/60 border-l border-r border-slate-100 text-xs font-extrabold <?= $stat['hadir'] > 0 ? 'text-emerald-600' : 'text-slate-300' ?>">
                                <?= $stat['hadir'] > 0 ? (int) $stat['hadir'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-extrabold <?= $stat['telat'] > 0 ? 'text-amber-600' : 'text-slate-300' ?>">
                                <?= $stat['telat'] > 0 ? (int) $stat['telat'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-semibold <?= $stat['ijin'] > 0 ? 'text-sky-600' : 'text-slate-300' ?>">
                                <?= $stat['ijin'] > 0 ? (int) $stat['ijin'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-semibold <?= $stat['sakit'] > 0 ? 'text-sky-600' : 'text-slate-300' ?>">
                                <?= $stat['sakit'] > 0 ? (int) $stat['sakit'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-semibold <?= $stat['cuti'] > 0 ? 'text-purple-600' : 'text-slate-300' ?>">
                                <?= $stat['cuti'] > 0 ? (int) $stat['cuti'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-semibold <?= $stat['dinas'] > 0 ? 'text-indigo-600' : 'text-slate-300' ?>">
                                <?= $stat['dinas'] > 0 ? (int) $stat['dinas'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-semibold <?= $stat['wfh'] > 0 ? 'text-teal-600' : 'text-slate-300' ?>">
                                <?= $stat['wfh'] > 0 ? (int) $stat['wfh'] : '-' ?>
                            </td>
                            <td class="px-2 py-3 text-center bg-slate-50/60 border-r border-slate-100 text-xs font-extrabold <?= $alphaBulan > 0 ? 'text-rose-600' : 'text-slate-300' ?>" title="<?= esc($alphaTooltip, 'attr') ?>">
                                <?= $alphaBulan > 0 ? $alphaBulan : '-' ?>
                            </td>

                            <td class="px-3 py-3 text-center bg-blue-50/40 text-xs font-bold whitespace-nowrap" title="<?= esc($dendaTooltip, 'attr') ?>">
                                <?php if ($dendaBulanVal > 0): ?>
                                    <span class="text-rose-600">Rp <?= number_format($dendaBulanVal, 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-center text-xs font-bold whitespace-nowrap">
                                <?php if ($poinHarian < 0): ?>
                                    <span class="text-rose-600"><?= rtrim(rtrim(number_format($poinHarian, 1, ',', '.'), '0'), ',') ?></span>
                                <?php elseif ($poinHarian > 0): ?>
                                    <span class="text-emerald-600">+<?= rtrim(rtrim(number_format($poinHarian, 1, ',', '.'), '0'), ',') ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-center">
                                <?php if ($latIn && $lngIn): ?>
                                    <a href="https://www.google.com/maps?q=<?= esc($latIn, 'attr') ?>,<?= esc($lngIn, 'attr') ?>" target="_blank" rel="noopener"
                                       class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 inline-flex items-center justify-center transition-colors"
                                       title="Buka lokasi masuk di Google Maps">
                                        <i class="bi bi-geo-alt-fill text-xs"></i>
                                    </a>
                                    <?php if ($locInName !== ''): ?>
                                        <div class="text-[10px] font-bold text-slate-500 mt-1 max-w-[80px] truncate mx-auto" title="<?= esc($locInName, 'attr') ?>">
                                            <?= esc($locInName) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <?php if ($fotoInUrl !== '' || $fotoOutUrl !== ''): ?>
                                    <button type="button"
                                            class="btn-lihat-foto w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 inline-flex items-center justify-center transition-colors"
                                            data-foto-in="<?= esc($fotoInUrl, 'attr') ?>"
                                            data-foto-out="<?= esc($fotoOutUrl, 'attr') ?>"
                                            data-nama="<?= esc($guruRow->nama_guru, 'attr') ?>"
                                            data-t-in="<?= esc($detailData['t_in'], 'attr') ?>"
                                            data-t-out="<?= esc($detailData['t_out'], 'attr') ?>"
                                            data-loc="<?= esc($locInName ?: '-', 'attr') ?>"
                                            title="Lihat foto presensi">
                                        <i class="bi bi-camera-fill text-xs"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-300 inline-flex items-center justify-center" title="Tidak ada foto">
                                        <i class="bi bi-camera-video-off text-xs"></i>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-center">
                                <?= $keteranganHtml ?>
                            </td>

                            <td class="px-3 py-3 text-center">
                                <button type="button"
                                        class="btn-detail inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-colors"
                                        data-detail="<?= esc($detailJson, 'attr') ?>">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($users)): ?>
                <div id="emptyTableState" class="hidden p-10 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                        <i class="bi bi-search text-2xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-700">Tidak ada data yang cocok</h4>
                    <p class="text-xs text-slate-400 mt-1">Sesuaikan kata kunci pencarian atau ganti filter status.</p>
                    <button type="button" onclick="resetTableFilter()" class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl text-xs font-bold hover:bg-blue-100 transition-colors border border-blue-200">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </button>
                </div>
            <?php endif; ?>

            <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
                <div>
                    Menampilkan <b id="tableFooterCount"><?= count($users) ?></b> dari <b><?= (int) $totalGuru ?></b> guru aktif &middot; <b><?= date('d/m/Y', strtotime($date)) ?></b>
                </div>
                <div class="flex items-center gap-3 flex-wrap text-[11px]">
                    <span class="inline-flex items-center gap-1"><b class="text-emerald-600">H</b> Hadir</span>
                    <span class="inline-flex items-center gap-1"><b class="text-amber-600">T</b> Telat</span>
                    <span class="inline-flex items-center gap-1"><b class="text-sky-600">I</b> Izin</span>
                    <span class="inline-flex items-center gap-1"><b class="text-sky-600">S</b> Sakit</span>
                    <span class="inline-flex items-center gap-1"><b class="text-purple-600">C</b> Cuti</span>
                    <span class="inline-flex items-center gap-1"><b class="text-indigo-600">D</b> Dinas</span>
                    <span class="inline-flex items-center gap-1"><b class="text-teal-600">W</b> WFH</span>
                    <span class="inline-flex items-center gap-1"><b class="text-rose-600">A</b> Alpha</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert kehadiran rendah -->
    <?php if (!empty($alertKehadiran)): ?>
        <div class="px-6 pt-4">
            <div class="bg-red-50 border border-red-200 rounded-2xl overflow-hidden shadow-sm" role="alert">
                <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                        </div>
                        <div>
                            <div class="text-sm font-extrabold text-red-950">
                                <?= count($alertKehadiran) ?> Guru di Bawah Standar Kehadiran (<?= $minKehadiranPersen ?>%)
                            </div>
                            <div class="text-xs text-red-800">
                                Berdasarkan hari efektif bulan <?= esc($bulanLabel) ?>. Klik detail untuk melihat rincian.
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors"
                                data-ui-toggle="collapse" data-ui-target="#collapseAlertKehadiran" aria-expanded="false">
                            <i class="bi bi-chevron-down text-xs"></i> <span>Detail Rincian</span>
                        </button>
                        <a href="<?= $rekapUrl ?>"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors no-underline">
                            Rekap Bulanan
                        </a>
                    </div>
                </div>

                <div class="collapse border-t border-red-200/60 bg-white/60" id="collapseAlertKehadiran">
                    <div class="p-4 overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 font-bold uppercase border-b border-red-100 text-[11px]">
                                    <th class="pb-2 text-left">No</th>
                                    <th class="pb-2 text-left">Nama Guru</th>
                                    <th class="pb-2 text-center">Hadir</th>
                                    <th class="pb-2 text-center">Efektif</th>
                                    <th class="pb-2 text-center">Alpha</th>
                                    <th class="pb-2 text-center" style="width: 180px;">Persentase</th>
                                    <th class="pb-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-100/60">
                                <?php foreach ($alertKehadiran as $i => $ak): ?>
                                    <?php
                                    $badgeClassAk = $ak['status'] === 'kritis' ? 'bg-red-600 text-white' : 'bg-amber-500 text-white';
                                    $badgeTextAk = $ak['status'] === 'kritis' ? 'KRITIS' : 'PERHATIAN';
                                    $barClassAk = $ak['persen'] < 75 ? 'bg-red-500' : 'bg-amber-500';
                                    ?>
                                    <tr class="hover:bg-red-50/50 transition-colors">
                                        <td class="py-2 text-slate-400 font-semibold"><?= $i + 1 ?></td>
                                        <td class="py-2 font-bold text-slate-800"><?= esc($ak['nama_guru']) ?></td>
                                        <td class="py-2 text-center font-semibold text-slate-700"><?= (int) $ak['hadir'] ?></td>
                                        <td class="py-2 text-center text-slate-500"><?= (int) $ak['efektif'] ?></td>
                                        <td class="py-2 text-center font-bold text-red-600"><?= (int) $ak['alpha'] ?></td>
                                        <td class="py-2 text-center">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="h-full <?= $barClassAk ?> rounded-full" style="width:<?= min($ak['persen'], 100) ?>%"></div>
                                                </div>
                                                <span class="font-extrabold text-[11px] <?= $ak['status'] === 'kritis' ? 'text-red-600' : 'text-amber-600' ?>">
                                                    <?= number_format($ak['persen'], 1) ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-2 text-center">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $badgeClassAk ?>"><?= $badgeTextAk ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal detail presensi -->
<div id="detailModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-box-tw w-full max-w-2xl">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img id="dtlAvatar" src="" alt="Avatar" class="avatar-teacher border border-slate-200">
                    <div class="min-w-0">
                        <h5 id="dtlName" class="font-bold text-slate-800 text-sm truncate">-</h5>
                        <p class="text-[11px] text-slate-400" id="dtlSub">-</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span id="dtlStatus" class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">-</span>
                    <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none" data-ui-dismiss="modal" aria-label="Tutup">
                        &times;
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jam Masuk</div>
                        <div id="dtlIn" class="text-lg font-extrabold text-slate-800 leading-none mt-1.5">--:--</div>
                        <div id="dtlInStatus" class="text-[11px] text-slate-500 mt-1">-</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jam Pulang</div>
                        <div id="dtlOut" class="text-lg font-extrabold text-slate-800 leading-none mt-1.5">--:--</div>
                        <div id="dtlOutStatus" class="text-[11px] text-slate-500 mt-1">-</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="p-3 rounded-xl border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Terlambat</div>
                        <div id="dtlLate" class="text-sm font-extrabold text-rose-600 mt-1">0 mnt</div>
                    </div>
                    <div class="p-3 rounded-xl border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pulang Cepat</div>
                        <div id="dtlEarly" class="text-sm font-extrabold text-orange-600 mt-1">0 mnt</div>
                    </div>
                    <div class="p-3 rounded-xl border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lembur</div>
                        <div id="dtlOvertime" class="text-sm font-extrabold text-emerald-600 mt-1">0 mnt</div>
                    </div>
                    <div class="p-3 rounded-xl border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Poin Harian</div>
                        <div id="dtlPoints" class="text-sm font-extrabold text-slate-700 mt-1">0</div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Denda</div>
                            <div class="text-xs text-blue-900 mt-1">
                                Hari ini <b id="dtlDendaDay">Rp 0</b> &middot; Bulan ini <b id="dtlDendaMonth">Rp 0</b>
                                &middot; Alpha bulan ini <b id="dtlAlpha">0</b> hari
                            </div>
                        </div>
                        <i class="bi bi-cash-coin text-blue-400 text-lg shrink-0"></i>
                    </div>
                    <div id="dtlDendaNoteWrap" class="hidden mt-2 text-[11px] text-blue-800 bg-white/70 rounded-lg px-2.5 py-1.5 border border-blue-100">
                        <i class="bi bi-info-circle mr-1"></i><span id="dtlDendaNote"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lokasi Masuk</div>
                        <div class="flex items-center justify-between gap-2 mt-1.5">
                            <span id="dtlLocInName" class="text-xs font-semibold text-slate-700 truncate">-</span>
                            <a id="dtlLocIn" href="#" target="_blank" rel="noopener" class="hidden inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 no-underline hover:underline shrink-0">
                                <i class="bi bi-geo-alt-fill"></i> Peta
                            </a>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lokasi Pulang</div>
                        <div class="flex items-center justify-between gap-2 mt-1.5">
                            <span id="dtlLocOutName" class="text-xs font-semibold text-slate-700 truncate">-</span>
                            <a id="dtlLocOut" href="#" target="_blank" rel="noopener" class="hidden inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 no-underline hover:underline shrink-0">
                                <i class="bi bi-geo-alt-fill"></i> Peta
                            </a>
                        </div>
                    </div>
                </div>

                <div id="dtlNoteWrap" class="hidden p-3.5 rounded-xl bg-amber-50 border border-amber-200">
                    <div class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Keterangan</div>
                    <p id="dtlNote" class="text-xs text-amber-900 mt-1 leading-relaxed whitespace-pre-line"></p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="button" id="dtlPhotoBtn"
                            class="hidden btn-lihat-foto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <i class="bi bi-camera-fill"></i> Lihat Foto Presensi
                    </button>
                    <a href="<?= site_url('admin/presensi/manual') ?>"
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors no-underline">
                        <i class="bi bi-pencil-square"></i> Absen Manual
                    </a>
                    <button type="button" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors sm:ml-auto" data-ui-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal foto -->
<div id="photoModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h6 class="font-bold text-slate-800 text-sm" id="photoTitle">Foto Absen</h6>
                    <span class="text-xs text-slate-400" id="photoMeta"></span>
                </div>
                <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none" data-ui-dismiss="modal" aria-label="Tutup">
                    &times;
                </button>
            </div>
            <div class="p-6 bg-slate-50 flex flex-col items-center justify-center gap-3">
                <div class="inline-flex p-1 bg-white rounded-xl gap-1 border border-slate-200" id="photoTabs">
                    <button type="button" id="tabFotoIn" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all">
                        <i class="bi bi-box-arrow-in-right mr-1"></i>Datang <span id="tabFotoInTime" class="font-semibold opacity-70"></span>
                    </button>
                    <button type="button" id="tabFotoOut" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all">
                        <i class="bi bi-box-arrow-left mr-1"></i>Pulang <span id="tabFotoOutTime" class="font-semibold opacity-70"></span>
                    </button>
                </div>
                <img src="" id="photoPreview" class="max-w-full h-auto rounded-2xl shadow-md border border-white" alt="Foto Absen" style="max-height: 400px; object-fit: contain;">
            </div>
            <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                <button type="button" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition-colors" data-ui-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal kebijakan -->
<div id="policyModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true" style="display:none">
    <div class="modal-box-tw w-full max-w-2xl">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="bi bi-shield-check text-base"></i>
                    </div>
                    <h5 class="font-bold text-slate-800 text-sm">Kebijakan &amp; Aturan Absensi Guru</h5>
                </div>
                <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none" data-ui-dismiss="modal" aria-label="Tutup">
                    &times;
                </button>
            </div>
            <div class="p-6 max-h-[75vh] overflow-y-auto space-y-5 text-xs text-slate-600">
                <div class="p-4 bg-blue-50/80 border border-blue-200 rounded-xl flex items-start gap-3">
                    <i class="bi bi-info-circle-fill text-blue-600 text-lg shrink-0 mt-0.5"></i>
                    <div class="leading-relaxed">
                        Sistem mencatat presensi berdasarkan koordinat GPS valid (radius sekolah), verifikasi foto wajah, dan waktu server. Tindakan kecurangan tersimpan otomatis di audit log.
                    </div>
                </div>

                <div>
                    <h6 class="font-bold text-blue-700 text-sm mb-2 flex items-center gap-1.5">
                        <i class="bi bi-clock-history"></i> 1. Denda Keterlambatan (3 Tier)
                    </h6>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-4 py-2.5">Menit Terlambat</th>
                                    <th class="px-4 py-2.5">Denda / Menit</th>
                                    <th class="px-4 py-2.5">Poin / Menit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="px-4 py-2 font-bold text-slate-700">Menit 1 &ndash; 30</td>
                                    <td class="px-4 py-2 text-rose-600 font-semibold">Rp <?= number_format((float) ($absenSetting->denda_tier_1 ?? 0), 0, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-slate-600"><?= (int) ($absenSetting->point_tier_1 ?? 1) ?> poin</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-bold text-slate-700">Menit 31 &ndash; 60</td>
                                    <td class="px-4 py-2 text-rose-600 font-semibold">Rp <?= number_format((float) ($absenSetting->denda_tier_2 ?? 0), 0, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-slate-600"><?= (int) ($absenSetting->point_tier_2 ?? 2) ?> poin</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-bold text-slate-700">Menit 61+</td>
                                    <td class="px-4 py-2 text-rose-600 font-semibold">Rp <?= number_format((float) ($absenSetting->denda_tier_3 ?? 0), 0, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-slate-600"><?= (int) ($absenSetting->point_tier_3 ?? 5) ?> poin</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-slate-400 mt-2 text-[11px]">
                        Toleransi keterlambatan <b><?= (int) ($absenSetting->grace_period ?? 15) ?> menit</b>. Batas denda per bulan <b>Rp <?= number_format((float) ($absenSetting->max_denda_bulan ?? 0), 0, ',', '.') ?></b>.
                    </p>
                </div>

                <div>
                    <h6 class="font-bold text-rose-700 text-sm mb-2 flex items-center gap-1.5">
                        <i class="bi bi-x-circle-fill"></i> 2. Alpha / Tanpa Keterangan
                    </h6>
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between gap-3">
                        <div>
                            <span class="font-bold text-rose-900">Alpha (A)</span>
                            <p class="text-[11px] text-rose-700 mt-0.5">Tidak melakukan presensi tanpa pengajuan izin yang sah.</p>
                        </div>
                        <span class="px-2.5 py-1 bg-rose-600 text-white rounded-xl font-extrabold text-xs shrink-0">
                            -<?= (int) ($absenSetting->point_alpa ?? 5) ?> poin/hari
                        </span>
                    </div>
                    <p class="text-slate-400 mt-2 text-[11px]">
                        Denda alpha <b>Rp <?= number_format((float) ($absenSetting->denda_alpha_per_hari ?? 0), 0, ',', '.') ?></b> per hari.
                    </p>
                </div>

                <div>
                    <h6 class="font-bold text-emerald-700 text-sm mb-2 flex items-center gap-1.5">
                        <i class="bi bi-file-earmark-check-fill"></i> 3. Izin, Sakit, Dinas &amp; WFH
                    </h6>
                    <ul class="space-y-1.5 list-disc pl-4 text-slate-600 leading-relaxed">
                        <li><b>Izin / Sakit / Cuti:</b> diajukan melalui portal guru dengan bukti sah, aktif setelah disetujui admin.</li>
                        <li><b>Tugas Dinas Luar:</b> bebas radius sekolah dan verifikasi wajah, menunggu persetujuan admin.</li>
                        <li><b>WFH:</b> dibatasi <?= (int) ($absenSetting->wfh_max_per_bulan ?? 5) ?> hari per bulan, tanpa radius GPS.</li>
                    </ul>
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                <button type="button" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition-colors" data-ui-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        'use strict';

        const MON_DATE = <?= json_encode($date) ?>;
        const STATE_KEY = 'presensiMonitoringState';
        const table = document.getElementById('monitoringTable');
        const tbody = table ? table.querySelector('tbody') : null;
        const rows = tbody ? Array.from(tbody.querySelectorAll('.teacher-row')) : [];
        rows.forEach(function (row, i) { row.dataset.idx = String(i); });

        var sortState = { key: '', dir: 'asc' };

        function loadState() {
            try {
                var s = JSON.parse(sessionStorage.getItem(STATE_KEY) || '{}');
                if (!s || s.d !== MON_DATE) return {};
                return s;
            } catch (e) {
                return {};
            }
        }

        function saveState(patch) {
            try {
                var s = loadState();
                Object.keys(patch).forEach(function (k) { s[k] = patch[k]; });
                s.d = MON_DATE;
                sessionStorage.setItem(STATE_KEY, JSON.stringify(s));
            } catch (e) {}
        }

        function renumberRows() {
            var n = 0;
            rows.forEach(function (r) {
                if (r.style.display === 'none') return;
                n++;
                var cell = r.querySelector('.row-no');
                if (cell) cell.textContent = String(n);
            });
            var footer = document.getElementById('tableFooterCount');
            if (footer) footer.textContent = String(n);
        }

        function applyTableFilter() {
            var query = (document.getElementById('tableSearch').value || '').toLowerCase().trim();
            var activeBtn = document.querySelector('.filter-btn-pill.active');
            var filter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
            var shown = 0;

            rows.forEach(function (row) {
                var name = row.getAttribute('data-name') || '';
                var nip = row.getAttribute('data-nip') || '';
                var status = row.getAttribute('data-status');
                var matchQuery = !query || name.indexOf(query) !== -1 || nip.indexOf(query) !== -1;
                var matchFilter = filter === 'all' || status === filter;
                var visible = matchQuery && matchFilter;
                row.style.display = visible ? '' : 'none';
                if (visible) shown++;
            });

            var counter = document.getElementById('tableCount');
            if (counter) counter.textContent = 'Menampilkan ' + shown + ' dari ' + rows.length + ' guru';

            var emptyState = document.getElementById('emptyTableState');
            if (emptyState) emptyState.classList.toggle('hidden', shown > 0);

            var clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', query.length === 0);

            renumberRows();
        }

        function renderPillCounts() {
            var counts = {};
            rows.forEach(function (row) {
                var s = row.getAttribute('data-status') || 'LAINNYA';
                counts[s] = (counts[s] || 0) + 1;
            });

            document.querySelectorAll('.filter-btn-pill').forEach(function (btn) {
                if (!btn.dataset.label) {
                    btn.dataset.label = btn.textContent.trim().replace(/\s*\(\d+\)\s*$/, '');
                }
                var filter = btn.getAttribute('data-filter');
                var count = filter === 'all' ? rows.length : (counts[filter] || 0);
                var icon = btn.querySelector('i');

                btn.textContent = '';
                if (icon) btn.appendChild(icon);
                btn.appendChild(document.createTextNode(' ' + btn.dataset.label + ' (' + count + ')'));
            });
        }

        function sortRows(key, dir) {
            rows.sort(function (a, b) {
                var av = a.getAttribute('data-sort-' + key);
                var bv = b.getAttribute('data-sort-' + key);
                av = av === null ? '' : av;
                bv = bv === null ? '' : bv;

                if (av === '' && bv === '') return Number(a.dataset.idx) - Number(b.dataset.idx);
                if (av === '') return 1;
                if (bv === '') return -1;

                var result;
                if (key === 'name') {
                    result = av.localeCompare(bv, 'id', { sensitivity: 'base' });
                } else {
                    result = parseFloat(av) - parseFloat(bv);
                }
                return dir === 'asc' ? result : -result;
            });

            rows.forEach(function (row) { tbody.appendChild(row); });
            updateSortIndicators();
            renumberRows();
        }

        function updateSortIndicators() {
            document.querySelectorAll('.sort-btn').forEach(function (btn) {
                var key = btn.getAttribute('data-sort-key');
                var icon = btn.querySelector('.sort-icon');
                var active = sortState.key === key;
                btn.classList.toggle('active', active);
                if (!icon) return;
                icon.className = 'bi sort-icon ' + (!active ? 'bi-arrow-down-up' : (sortState.dir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up'));
            });
        }

        function restoreOriginalOrder() {
            rows.sort(function (a, b) { return Number(a.dataset.idx) - Number(b.dataset.idx); });
            rows.forEach(function (row) { tbody.appendChild(row); });
        }

        function setActiveFilter(filter) {
            document.querySelectorAll('.filter-btn-pill').forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-filter') === filter);
            });
        }

        function resetTableFilter() {
            var input = document.getElementById('tableSearch');
            if (input) input.value = '';
            setActiveFilter('all');
            sortState = { key: '', dir: 'asc' };
            restoreOriginalOrder();
            updateSortIndicators();
            saveState({ q: '', f: 'all', sortKey: '', sortDir: 'asc' });
            applyTableFilter();
        }

        window.resetTableFilter = resetTableFilter;

        window.filterBelumAbsen = function () {
            var input = document.getElementById('tableSearch');
            if (input) input.value = '';
            setActiveFilter('ALPHA');
            saveState({ q: '', f: 'ALPHA' });
            applyTableFilter();
            var target = document.getElementById('monitoringTable');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        var searchInput = document.getElementById('tableSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                saveState({ q: this.value });
                applyTableFilter();
            });
        }

        var clearSearchBtn = document.getElementById('clearSearchBtn');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                if (!searchInput) return;
                searchInput.value = '';
                saveState({ q: '' });
                applyTableFilter();
                searchInput.focus();
            });
        }

        document.querySelectorAll('.filter-btn-pill').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setActiveFilter(this.getAttribute('data-filter'));
                saveState({ f: this.getAttribute('data-filter') });
                applyTableFilter();
            });
        });

        document.querySelectorAll('.sort-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = this.getAttribute('data-sort-key');
                if (sortState.key === key) {
                    sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState = { key: key, dir: 'asc' };
                }
                sortRows(key, sortState.dir);
                saveState({ sortKey: sortState.key, sortDir: sortState.dir });
            });
        });

        window.addEventListener('beforeunload', function () {
            saveState({ scrollY: window.scrollY });
        });

        // Foto presensi
        var photoState = { in: '', out: '', name: '', tIn: '-', tOut: '-', loc: '-' };

        function paintPhotoTab(which) {
            var tabIn = document.getElementById('tabFotoIn');
            var tabOut = document.getElementById('tabFotoOut');
            var url = which === 'out' ? photoState.out : photoState.in;

            document.getElementById('photoPreview').src = url;
            document.getElementById('photoTitle').textContent = 'Foto ' + (which === 'out' ? 'Pulang' : 'Datang') + ' - ' + photoState.name;
            document.getElementById('photoMeta').textContent = 'Waktu: ' + (which === 'out' ? photoState.tOut : photoState.tIn) + ' | Lokasi: ' + photoState.loc;

            if (tabIn) {
                tabIn.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ' + (which === 'in' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800');
                tabIn.style.display = photoState.in ? '' : 'none';
                document.getElementById('tabFotoInTime').textContent = photoState.in ? '(' + photoState.tIn + ')' : '';
            }
            if (tabOut) {
                tabOut.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all ' + (which === 'out' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800');
                tabOut.style.display = photoState.out ? '' : 'none';
                document.getElementById('tabFotoOutTime').textContent = photoState.out ? '(' + photoState.tOut + ')' : '';
            }
        }

        function showPhotoFromButton(btn) {
            photoState = {
                in: btn.dataset.fotoIn || '',
                out: btn.dataset.fotoOut || '',
                name: btn.dataset.nama || '',
                tIn: btn.dataset.tIn || '-',
                tOut: btn.dataset.tOut || '-',
                loc: btn.dataset.loc || '-'
            };
            paintPhotoTab(photoState.in ? 'in' : 'out');
            AdminUI.modal.open(document.getElementById('photoModal'));
        }

        var tabFotoIn = document.getElementById('tabFotoIn');
        var tabFotoOut = document.getElementById('tabFotoOut');
        if (tabFotoIn) tabFotoIn.addEventListener('click', function () { paintPhotoTab('in'); });
        if (tabFotoOut) tabFotoOut.addEventListener('click', function () { paintPhotoTab('out'); });

        var fmtRp = function (value) {
            var n = Number(value) || 0;
            return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        };

        var fmtMin = function (value) {
            return (Number(value) || 0) + ' mnt';
        };

        function setLoc(nameId, linkId, name, lat, lng) {
            var nameEl = document.getElementById(nameId);
            var linkEl = document.getElementById(linkId);
            if (nameEl) nameEl.textContent = name || '-';
            if (!linkEl) return;
            if (lat && lng) {
                linkEl.href = 'https://www.google.com/maps?q=' + encodeURIComponent(lat) + ',' + encodeURIComponent(lng);
                linkEl.classList.remove('hidden');
            } else {
                linkEl.classList.add('hidden');
            }
        }

        function openDetail(btn) {
            var data;
            try {
                data = JSON.parse(btn.getAttribute('data-detail') || '{}');
            } catch (e) {
                return;
            }

            document.getElementById('dtlAvatar').src = data.avatar || '';
            document.getElementById('dtlName').textContent = data.name || '-';
            document.getElementById('dtlSub').textContent = 'NIP: ' + (data.nip || '-') + ' · ' + (data.date || '-');

            var statusEl = document.getElementById('dtlStatus');
            statusEl.className = 'inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold ' + (data.status_class || 'bg-slate-100 text-slate-600 border border-slate-200');
            statusEl.textContent = data.status || '-';

            document.getElementById('dtlIn').textContent = data.in || '--:--';
            document.getElementById('dtlOut').textContent = data.out || '--:--';
            document.getElementById('dtlInStatus').textContent = data.in_status || '-';
            document.getElementById('dtlOutStatus').textContent = data.out_status || '-';

            document.getElementById('dtlLate').textContent = fmtMin(data.late);
            document.getElementById('dtlEarly').textContent = fmtMin(data.early);
            document.getElementById('dtlOvertime').textContent = fmtMin(data.overtime);

            var points = Number(data.points) || 0;
            var pointsEl = document.getElementById('dtlPoints');
            pointsEl.textContent = (points > 0 ? '+' : '') + points;
            pointsEl.className = 'text-sm font-extrabold mt-1 ' + (points < 0 ? 'text-rose-600' : (points > 0 ? 'text-emerald-600' : 'text-slate-700'));

            document.getElementById('dtlDendaDay').textContent = fmtRp(data.denda_day);
            document.getElementById('dtlDendaMonth').textContent = fmtRp(data.denda_month);
            document.getElementById('dtlAlpha').textContent = String(Number(data.alpha) || 0);

            var dendaNoteWrap = document.getElementById('dtlDendaNoteWrap');
            if (data.denda_note) {
                dendaNoteWrap.classList.remove('hidden');
                document.getElementById('dtlDendaNote').textContent = data.denda_note;
            } else {
                dendaNoteWrap.classList.add('hidden');
            }

            setLoc('dtlLocInName', 'dtlLocIn', data.loc_in, data.lat_in, data.lng_in);
            setLoc('dtlLocOutName', 'dtlLocOut', data.loc_out, data.lat_out, data.lng_out);

            var noteWrap = document.getElementById('dtlNoteWrap');
            if (data.note) {
                noteWrap.classList.remove('hidden');
                document.getElementById('dtlNote').textContent = data.note;
            } else {
                noteWrap.classList.add('hidden');
            }

            var photoBtn = document.getElementById('dtlPhotoBtn');
            if (data.has_photo) {
                photoBtn.classList.remove('hidden');
                photoBtn.dataset.fotoIn = data.photo_in || '';
                photoBtn.dataset.fotoOut = data.photo_out || '';
                photoBtn.dataset.nama = data.name || '';
                photoBtn.dataset.tIn = data.t_in || '-';
                photoBtn.dataset.tOut = data.t_out || '-';
                photoBtn.dataset.loc = data.loc_in || '-';
            } else {
                photoBtn.classList.add('hidden');
            }

            AdminUI.modal.open(document.getElementById('detailModal'));
        }

        document.addEventListener('click', function (e) {
            var photoBtn = e.target.closest ? e.target.closest('.btn-lihat-foto') : null;
            if (photoBtn) {
                if (photoBtn.id === 'dtlPhotoBtn') {
                    AdminUI.modal.close(document.getElementById('detailModal'));
                }
                showPhotoFromButton(photoBtn);
                return;
            }

            var detailBtn = e.target.closest ? e.target.closest('.btn-detail') : null;
            if (detailBtn) openDetail(detailBtn);
        });

        window.recalculateDayStatus = function () {
            var url = <?= json_encode(site_url('admin/presensi/recalculate-day')) ?>;
            var payload = new URLSearchParams({
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                'date': MON_DATE
            });

            if (typeof Swal === 'undefined') {
                if (!confirm('Hitung ulang status presensi tanggal ' + MON_DATE + ' berdasarkan toleransi aktif?')) return;
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload
                }).then(function (r) { return r.json(); })
                  .then(function (d) { alert(d.message || 'Selesai'); window.location.reload(); })
                  .catch(function (e) { alert('Error: ' + e.message); });
                return;
            }

            Swal.fire({
                title: 'Hitung Ulang Status Presensi?',
                html: 'Sistem akan mengevaluasi ulang status kehadiran serta denda seluruh guru pada <strong><?= date('d/m/Y', strtotime($date)) ?></strong> sesuai toleransi dan jadwal aktif.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-arrow-repeat mr-1"></i> Ya, Hitung Ulang',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang mengevaluasi ulang data presensi...',
                    allowOutsideClick: false,
                    didOpen: function () { Swal.showLoading(); }
                });

                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: payload
                }).then(function (res) { return res.json(); })
                  .then(function (data) {
                      if (data.status) {
                          Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, timer: 1500, showConfirmButton: false })
                              .then(function () { window.location.reload(); });
                      } else {
                          Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Terjadi kesalahan saat menghitung ulang.' });
                      }
                  })
                  .catch(function (err) {
                      Swal.fire({ icon: 'error', title: 'Kesalahan Jaringan', text: 'Gagal menghubungi server: ' + err.message });
                  });
            });
        };

        (function init() {
            var saved = loadState();

            if (saved.q && searchInput) searchInput.value = saved.q;
            if (saved.f) setActiveFilter(saved.f);

            renderPillCounts();
            applyTableFilter();

            if (saved.sortKey) {
                sortState = { key: saved.sortKey, dir: saved.sortDir === 'desc' ? 'desc' : 'asc' };
                sortRows(sortState.key, sortState.dir);
            }

            if (saved.scrollY) window.scrollTo(0, parseInt(saved.scrollY, 10) || 0);
        })();

        <?php if ($isToday): ?>
        setInterval(function () {
            if (document.hidden) return;
            if (document.querySelector('.modal.is-open')) return;
            if ((searchInput && searchInput.value.trim()) || sortState.key) return;
            var active = document.querySelector('.filter-btn-pill.active');
            if (!active || active.getAttribute('data-filter') !== 'all') return;
            window.location.reload();
        }, 60000);
        <?php endif; ?>
    })();
</script>

<?= $this->endSection() ?>
