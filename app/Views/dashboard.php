<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    /* ── KPI Card hover ─────────────────────────── */
    .kpi-card { transition: transform 0.2s, box-shadow 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px -8px rgba(0,0,0,0.14); }

    /* ── Stat row hover ─────────────────────────── */
    .att-box { transition: transform 0.15s; }
    .att-box:hover { transform: translateY(-2px); }

    /* ── Quick action tile ──────────────────────── */
    .qa-tile { transition: background 0.15s, border-color 0.15s, transform 0.15s; }
    .qa-tile:hover { transform: translateY(-2px); }

    /* ── Timeline dot connector ─────────────────── */
    .tl-connector::before {
        content: ''; position: absolute;
        top: 28px; left: 18px; bottom: -8px;
        width: 1px; background: #f1f5f9;
    }

    /* ── Attendance donut ring ───────────────────── */
    .donut-ring {
        stroke-dasharray: var(--pct, 0) 100;
        stroke-linecap: round;
        transition: stroke-dasharray 0.6s ease;
    }

    /* ── Check-in list polish ─────────────────── */
    .checkin-row { transition: background 0.15s; }
    .checkin-row:hover { background: #f8fafc; }
    .checkin-scroll::-webkit-scrollbar { width: 6px; }
    .checkin-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 9999px; }
    .checkin-scroll::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    .tabular { font-variant-numeric: tabular-nums; }
    /* ── Pulse dot ──────────────────────────────── */
    .pulse-live::after {
        content: ''; position: absolute;
        inset: 0; border-radius: 9999px;
        background: currentColor; opacity: 0.6;
        animation: pulseLive 1.8s ease-out infinite;
    }
    @keyframes pulseLive { 75%,100% { transform: scale(2.4); opacity: 0; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$jamSekarang = (int)date('H');
$salam = $jamSekarang < 11 ? 'Selamat Pagi' : ($jamSekarang < 15 ? 'Selamat Siang' : ($jamSekarang < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$namaUser = session()->get('nama') ?? $user->first_name ?? 'Administrator';

$ph = $presensiHariIni ?? [
    'hadir' => 0, 'ontime' => 0, 'telat' => 0, 'izin' => 0, 'sakit' => 0,
    'cuti' => 0, 'dinas' => 0, 'wfh' => 0, 'alpha' => 0, 'belum_absen' => 0,
    'pending' => 0, 'total_guru' => 0, 'persen' => 0
];
$pending = $pendingSummary ?? ['dinas' => 0, 'koreksi' => 0, 'izin' => 0, 'wajah' => 0, 'kehadiran' => 0, 'total' => 0];
$persen  = min(100, max(0, (float)($ph['persen'] ?? 0)));

$hariSekolah      = $hari_sekolah ?? ['status' => 'aktif', 'keterangan' => 'Hari kerja sekolah', 'nama_hari' => ''];
$isHariSekolah    = $is_hari_sekolah ?? true;
$nextSchoolDay    = $next_school_day ?? null;
$todayLabel       = $today_label ?? buat_tanggal(date('l, d F Y'));
$isAkhirPekan     = ($hariSekolah['status'] ?? '') === 'akhir_pekan';
$liburLabel       = $isAkhirPekan ? 'Libur Mingguan' : 'Hari Libur';
$liburKeterangan  = trim((string)($hariSekolah['keterangan'] ?? '')) ?: $liburLabel;
$deskripsiLibur   = $isAkhirPekan
    ? 'Hari ' . trim((string)($hariSekolah['nama_hari'] ?? '')) . ' ditetapkan sebagai libur mingguan.'
    : 'Hari ini libur: ' . rtrim($liburKeterangan, '.') . '.';
$checkinDateLabel = $checkin_date_label ?? null;
$checkinCount     = is_array($recentPresensi ?? null) ? count($recentPresensi) : 0;

if ($isHariSekolah) {
    $salamTampil = $salam;
    $sapaan = 'Selamat datang di panel admin. Pantau kehadiran guru dan siswa secara real-time.';
} else {
    $salamTampil = $isAkhirPekan ? 'Selamat Berakhir Pekan' : 'Selamat Hari Libur';
    $sapaan = $isAkhirPekan
        ? 'Sekolah libur hari ini. Presensi tidak dihitung — nikmati waktu istirahat Anda.'
        : $deskripsiLibur . ' Presensi tidak dihitung.';
}

// Donut SVG helpers
$circumference = 100;
$pctOntime = $ph['total_guru'] > 0 ? round(($ph['ontime'] / $ph['total_guru']) * 100) : 0;
$pctTelat  = $ph['total_guru'] > 0 ? round(($ph['telat']  / $ph['total_guru']) * 100) : 0;
?>

<div class="fade-in-up">

    <!-- ══════════════════════════════════════════════════
         HERO BANNER
    ══════════════════════════════════════════════════ -->
    <div class="px-6 pt-6 pb-0">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-blue-900 text-white shadow-xl">
            <!-- Decorative blobs -->
            <div class="absolute -right-16 -bottom-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute right-40 -top-20 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute left-0 top-0 w-32 h-full bg-gradient-to-r from-blue-800/40 to-transparent pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-6 p-6 md:p-8">
                <!-- Left -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 rounded-full text-[11px] font-semibold text-blue-200 border border-white/15 backdrop-blur-sm">
                            <i class="bi bi-calendar3"></i>
                            <span><?= esc($todayLabel) ?></span>
                            <?php if (isset($tp_active) && $tp_active): ?>
                                <span class="text-white/40">·</span>
                                <span>TP <?= esc($tp_active->tahun) ?></span>
                            <?php endif; ?>
                            <?php if (isset($smt_active) && $smt_active): ?>
                                <span>Semester <?= esc($smt_active->nama_smt) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wide border backdrop-blur-sm <?= $isHariSekolah ? 'bg-emerald-400/15 text-emerald-100 border-emerald-300/30' : 'bg-amber-300/15 text-amber-100 border-amber-200/30' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $isHariSekolah ? 'bg-emerald-300' : 'bg-amber-300' ?>"></span>
                            <?= $isHariSekolah ? 'Hari Sekolah' : esc($liburLabel) ?>
                        </span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight leading-tight">
                        <?= esc($salamTampil) ?>, <?= esc($namaUser) ?>! 👋
                    </h1>
                    <p class="text-sm text-blue-100/80 mt-1.5 max-w-lg">
                        <?= esc($sapaan) ?>
                    </p>
                </div>

                <!-- Right: live attendance pill + action -->
                <div class="flex items-center gap-3 shrink-0 flex-wrap">
                    <?php if ($isHariSekolah): ?>
                        <!-- Live attendance mini pill -->
                        <div class="flex items-center gap-2.5 bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-4 py-3">
                            <div class="relative w-2.5 h-2.5 rounded-full bg-emerald-400 pulse-live flex-shrink-0"></div>
                            <div>
                                <p class="text-[10px] text-white/60 font-semibold uppercase tracking-wider leading-none">Hadir Hari Ini</p>
                                <p class="text-xl font-extrabold text-white leading-none mt-0.5">
                                    <?= (int)$ph['hadir'] ?>
                                    <span class="text-sm font-semibold text-white/50">/ <?= (int)$ph['total_guru'] ?></span>
                                </p>
                            </div>
                        </div>

                        <a href="<?= site_url('admin/presensi') ?>"
                            class="inline-flex items-center gap-2 px-4 py-3 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors shadow text-sm no-underline shrink-0">
                            <i class="bi bi-speedometer2"></i>
                            <span>Monitoring</span>
                        </a>
                    <?php else: ?>
                        <div class="flex items-center gap-2.5 bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-4 py-3">
                            <i class="bi bi-calendar-x text-2xl text-amber-200"></i>
                            <div>
                                <p class="text-[10px] text-white/60 font-semibold uppercase tracking-wider leading-none"><?= esc($liburLabel) ?></p>
                                <p class="text-sm font-extrabold text-white leading-none mt-1">Tidak ada presensi</p>
                            </div>
                        </div>

                        <a href="<?= site_url('admin/presensi/rekap') ?>"
                            class="inline-flex items-center gap-2 px-4 py-3 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors shadow text-sm no-underline shrink-0">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Rekap Presensi</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom bar inside hero -->
            <div class="relative z-10 px-6 md:px-8 pb-5">
                <?php if ($isHariSekolah): ?>
                    <div class="flex items-center justify-between text-[11px] text-white/70 mb-1.5">
                        <span>Tingkat Kehadiran Hari Ini</span>
                        <span class="font-extrabold text-white"><?= $persen ?>%</span>
                    </div>
                    <div class="h-1.5 w-full bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-300 transition-all duration-700"
                            style="width: <?= $persen ?>%"></div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-white/70 border-t border-white/10 pt-3">
                        <span class="inline-flex items-center gap-1.5">
                            <i class="bi bi-info-circle"></i>
                            Presensi tidak dihitung pada hari libur
                        </span>
                        <?php if ($nextSchoolDay): ?>
                            <span class="inline-flex items-center gap-1.5 font-semibold text-white/85">
                                <i class="bi bi-calendar-check"></i>
                                Hari sekolah berikutnya: <?= esc($nextSchoolDay['label']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending badge row (below hero, if any) -->
    <?php if (!empty($pending['total']) && $pending['total'] > 0): ?>
    <div class="px-6 mt-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-950">
                        <?= $pending['total'] ?> Pengajuan Menunggu Tindakan
                    </p>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        <?php
                        $pendingItems = [
                            ['key' => 'dinas',  'icon' => 'bi-briefcase',      'label' => 'Dinas',   'url' => 'admin/presensi/dinas'],
                            ['key' => 'koreksi','icon' => 'bi-pencil-square',  'label' => 'Koreksi', 'url' => 'admin/presensi/koreksi'],
                            ['key' => 'izin',   'icon' => 'bi-envelope-open',  'label' => 'Izin',    'url' => 'admin/presensi/izin'],
                            ['key' => 'wajah',  'icon' => 'bi-camera',         'label' => 'Wajah',   'url' => 'admin/presensi'],
                            ['key' => 'kehadiran','icon' => 'bi-person-check', 'label' => 'Kehadiran', 'url' => 'admin/presensi/verifikasi'],
                        ];
                        foreach ($pendingItems as $pi):
                            if (empty($pending[$pi['key']])) continue; ?>
                        <a href="<?= site_url($pi['url']) ?>"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 hover:bg-amber-200 border border-amber-200 text-amber-800 text-[11px] font-semibold no-underline transition-colors">
                            <i class="bi <?= $pi['icon'] ?>"></i>
                            <?= $pi['label'] ?>
                            <span class="bg-amber-600 text-white rounded-full px-1.5 py-0.5 text-[9px] font-extrabold">
                                <?= (int)$pending[$pi['key']] ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════
         KPI CARDS — Master Data
    ══════════════════════════════════════════════════ -->
    <div class="px-6 pt-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <?php
            $gradients = [
                'from-blue-500 to-indigo-600',
                'from-emerald-500 to-teal-600',
                'from-violet-500 to-purple-600',
                'from-amber-500 to-orange-500',
                'from-brand-500 to-brand-700',
                'from-cyan-500 to-sky-600',
            ];
            $iconMap = [
                'users'           => 'people-fill',
                'bell'            => 'building',
                'user-circle'     => 'person-video3',
                'user-o'          => 'person-badge',
                'book'            => 'book-half',
                'user-clock'      => 'calendar-check-fill',
                'chalkboard-teacher' => 'person-video3',
                'school'          => 'building',
                'clock'           => 'clock-fill',
                'folder'          => 'folder-fill',
            ];
            $i = 0;
            foreach ($info_box as $box):
                $grad = $gradients[$i % count($gradients)];
                $biIcon = $iconMap[$box['icon']] ?? 'star-fill';
                $i++;
            ?>
            <a href="<?= base_url($box['url']) ?>" class="kpi-card no-underline group">
                <div class="bg-gradient-to-br <?= $grad ?> rounded-2xl p-4 text-white relative overflow-hidden shadow-sm">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 rounded-full bg-white/8 pointer-events-none"></div>
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm shrink-0">
                            <i class="bi bi-<?= $biIcon ?> text-base"></i>
                        </div>
                        <i class="bi bi-arrow-up-right text-white/40 text-xs group-hover:text-white/80 transition-colors"></i>
                    </div>
                    <p class="text-2xl lg:text-3xl font-extrabold leading-none tracking-tight">
                        <?php
                        $metricHariIni = in_array($box['title'], ['Guru Hadir', 'Siswa Hadir', 'Jurnal KBM'], true);
                        echo !$isHariSekolah && $metricHariIni ? '&mdash;' : $box['total'];
                        ?>
                    </p>
                    <p class="text-[11px] font-semibold uppercase tracking-wider opacity-80 mt-1.5"><?= esc($box['title']) ?></p>
                    <?php if (!empty($box['hint'])): ?>
                        <p class="text-[10px] font-medium opacity-65 mt-1"><?= esc($box['hint']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         MAIN BODY
    ══════════════════════════════════════════════════ -->
    <div class="p-6 space-y-6">

        <!-- Row 1: Kehadiran + Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── Kehadiran Hari Ini (2/3) ─────────── -->
            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="bi bi-clipboard2-pulse-fill text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800 leading-none">Kehadiran Hari Ini</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                <?= $isHariSekolah ? 'Ringkasan seluruh guru per hari ini' : esc($todayLabel) . ' · ' . esc($liburLabel) ?>
                            </p>
                        </div>
                    </div>
                    <a href="<?= site_url($isHariSekolah ? 'admin/presensi' : 'admin/presensi/rekap') ?>"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-100 transition-colors text-xs font-bold no-underline">
                        <?= $isHariSekolah ? 'Buka' : 'Rekap' ?> <i class="bi bi-arrow-right text-xs"></i>
                    </a>
                </div>

                <?php if ($isHariSekolah): ?>
                <!-- Stat boxes 4-col -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- Tepat Waktu -->
                    <div class="att-box rounded-2xl bg-emerald-50 border border-emerald-200/70 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wide">On Time</span>
                            <i class="bi bi-check-circle-fill text-emerald-500"></i>
                        </div>
                        <p class="text-3xl font-extrabold text-emerald-700 leading-none"><?= (int)($ph['ontime'] ?? 0) ?></p>
                        <p class="text-[10px] text-emerald-600 mt-1.5 font-medium">Check-in tepat waktu</p>
                    </div>

                    <!-- Terlambat -->
                    <div class="att-box rounded-2xl bg-amber-50 border border-amber-200/70 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wide">Terlambat</span>
                            <i class="bi bi-clock-history text-amber-500"></i>
                        </div>
                        <p class="text-3xl font-extrabold text-amber-700 leading-none"><?= (int)($ph['telat'] ?? 0) ?></p>
                        <p class="text-[10px] text-amber-600 mt-1.5 font-medium">Lewat batas jam</p>
                    </div>

                    <!-- Izin / Dinas -->
                    <?php $totalIzin = (int)($ph['izin'] ?? 0) + (int)($ph['sakit'] ?? 0) + (int)($ph['cuti'] ?? 0) + (int)($ph['wfh'] ?? 0) + (int)($ph['dinas'] ?? 0); ?>
                    <div class="att-box rounded-2xl bg-sky-50 border border-sky-200/70 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wide">Izin / Dinas</span>
                            <i class="bi bi-envelope-paper-fill text-sky-500"></i>
                        </div>
                        <p class="text-3xl font-extrabold text-sky-700 leading-none"><?= $totalIzin ?></p>
                        <p class="text-[10px] text-sky-600 mt-1.5 font-medium">Izin, Sakit, Dinas, WFH</p>
                    </div>

                    <!-- Belum Absen -->
                    <div class="att-box rounded-2xl bg-rose-50 border border-rose-200/70 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold text-rose-800 uppercase tracking-wide">Belum</span>
                            <i class="bi bi-x-circle-fill text-rose-500"></i>
                        </div>
                        <p class="text-3xl font-extrabold text-rose-700 leading-none"><?= (int)($ph['belum_absen'] ?? 0) ?></p>
                        <p class="text-[10px] text-rose-600 mt-1.5 font-medium">dari <?= (int)$ph['total_guru'] ?> guru</p>
                    </div>
                </div>

                <!-- Visual bar breakdown -->
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2">
                        <span class="flex items-center gap-1.5">
                            <i class="bi bi-bar-chart-fill text-blue-600"></i>
                            Kehadiran:
                            <strong class="text-slate-800"><?= (int)$ph['hadir'] ?> / <?= (int)$ph['total_guru'] ?> Guru</strong>
                        </span>
                        <span class="font-extrabold text-blue-700"><?= $persen ?>%</span>
                    </div>

                    <!-- Stacked bar -->
                    <?php
                    $total = max(1, (int)$ph['total_guru']);
                    $wOntime = round(($ph['ontime'] / $total) * 100, 1);
                    $wTelat  = round(($ph['telat']  / $total) * 100, 1);
                    $wIzin   = round(($totalIzin    / $total) * 100, 1);
                    $wBlm    = round(($ph['belum_absen'] / $total) * 100, 1);
                    ?>
                    <div class="w-full h-3 rounded-full overflow-hidden bg-slate-100 flex">
                        <div class="h-full bg-emerald-500 transition-all" style="width:<?= $wOntime ?>%"></div>
                        <div class="h-full bg-amber-400 transition-all"   style="width:<?= $wTelat ?>%"></div>
                        <div class="h-full bg-sky-400 transition-all"     style="width:<?= $wIzin ?>%"></div>
                        <div class="h-full bg-rose-400 transition-all"    style="width:<?= $wBlm ?>%"></div>
                    </div>
                    <div class="flex items-center gap-4 mt-2 flex-wrap">
                        <span class="flex items-center gap-1 text-[10px] text-slate-500"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span>Tepat Waktu</span>
                        <span class="flex items-center gap-1 text-[10px] text-slate-500"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400 inline-block"></span>Terlambat</span>
                        <span class="flex items-center gap-1 text-[10px] text-slate-500"><span class="w-2.5 h-2.5 rounded-sm bg-sky-400 inline-block"></span>Izin/Dinas</span>
                        <span class="flex items-center gap-1 text-[10px] text-slate-500"><span class="w-2.5 h-2.5 rounded-sm bg-rose-400 inline-block"></span>Belum</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50 via-white to-slate-50 p-6 sm:p-7">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-calendar-x text-2xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-bold text-slate-800">Tidak ada presensi pada hari libur</h3>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase tracking-wide">
                                    <?= esc($liburLabel) ?>
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                                <strong class="text-slate-700"><?= esc($todayLabel) ?></strong> &mdash;
                                <?= esc($deskripsiLibur) ?> Kehadiran guru dan siswa tidak dihitung hari ini.
                            </p>
                            <?php if ($nextSchoolDay): ?>
                                <div class="mt-3 inline-flex flex-wrap items-center gap-2 px-3 py-2 rounded-xl bg-white border border-slate-200 shadow-sm text-xs font-semibold text-slate-600">
                                    <i class="bi bi-calendar-check text-emerald-600"></i>
                                    <span>Hari sekolah berikutnya</span>
                                    <span class="text-slate-800"><?= esc($nextSchoolDay['label']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-5 pt-4 border-t border-amber-100 flex flex-wrap items-center gap-2">
                        <a href="<?= site_url('admin/presensi/rekap') ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition-colors no-underline shadow-sm shadow-blue-500/20">
                            <i class="bi bi-file-earmark-bar-graph"></i> Lihat Rekap Presensi
                        </a>
                        <a href="<?= site_url('admin/kalender') ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white text-slate-600 border border-slate-200 text-xs font-bold hover:bg-slate-50 transition-colors no-underline">
                            <i class="bi bi-calendar3"></i> Kalender Akademik
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Quick Actions (1/3) ───────────────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="bi bi-grid-fill text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 leading-none">Akses Cepat</h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pintasan fitur utama</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <?php
                    $qaLinks = [
                        ['url' => 'admin/presensi',              'icon' => 'bi-speedometer2',     'bg' => 'bg-blue-50',   'ic' => 'text-blue-600',   'hover' => 'hover:bg-blue-50 hover:border-blue-200',   'label' => 'Presensi Guru',       'sub' => 'Monitoring & Absen'],
                        ['url' => 'admin/presensi/siswa/harian', 'icon' => 'bi-people-fill',      'bg' => 'bg-emerald-50','ic' => 'text-emerald-600','hover' => 'hover:bg-emerald-50 hover:border-emerald-200','label' => 'Presensi Siswa',     'sub' => 'Monitoring harian'],
                        ['url' => 'admin/jurnal',                'icon' => 'bi-journal-check',    'bg' => 'bg-purple-50', 'ic' => 'text-purple-600', 'hover' => 'hover:bg-purple-50 hover:border-purple-200', 'label' => 'Jurnal Mengajar',    'sub' => 'Pemantauan KBM'],
                        ['url' => 'admin/piket',                 'icon' => 'bi-shield-check',     'bg' => 'bg-amber-50',  'ic' => 'text-amber-600',  'hover' => 'hover:bg-amber-50 hover:border-amber-200',  'label' => 'Piket Guru',          'sub' => 'Jadwal & Tamu'],
                        ['url' => 'scanner', 'icon' => 'bi-display', 'bg' => 'bg-teal-50', 'ic' => 'text-teal-600', 'hover' => 'hover:bg-teal-50 hover:border-teal-200', 'label' => 'Scanner QR Siswa', 'sub' => 'Desktop & Kiosk'],
                        ['url' => 'admin/presensi/rekap',        'icon' => 'bi-file-earmark-spreadsheet', 'bg' => 'bg-rose-50', 'ic' => 'text-rose-600', 'hover' => 'hover:bg-rose-50 hover:border-rose-200', 'label' => 'Rekap Presensi',      'sub' => 'Laporan bulanan'],
                    ];
                    foreach ($qaLinks as $qa): ?>
                    <a href="<?= site_url($qa['url']) ?>"
                        class="qa-tile flex items-center gap-3 p-2.5 rounded-2xl bg-slate-50 <?= $qa['hover'] ?> border border-slate-200/70 no-underline group">
                        <div class="w-9 h-9 rounded-xl <?= $qa['bg'] ?> <?= $qa['ic'] ?> flex items-center justify-center shrink-0">
                            <i class="bi <?= $qa['icon'] ?> text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-700 truncate leading-none"><?= $qa['label'] ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5"><?= $qa['sub'] ?></p>
                        </div>
                        <i class="bi bi-chevron-right text-slate-300 text-xs group-hover:text-slate-500 transition-colors"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Row 2: Check-in Terbaru + Aktivitas Log -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- ── Check-in Guru Terbaru ─────────────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col">
                <div class="flex items-center justify-between gap-3 pb-4 mb-1 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-fingerprint text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-800 leading-none"><?= $isHariSekolah ? 'Check-in Guru Terbaru' : 'Check-in Guru Terakhir' ?></h2>
                            <p class="text-[11px] text-slate-400 mt-1">
                                <?php if ($isHariSekolah): ?>
                                    Absensi masuk hari ini &middot; <?= buat_tanggal(date('d F Y')) ?>
                                <?php elseif ($checkinDateLabel): ?>
                                    Data terakhir &middot; <?= esc($checkinDateLabel) ?>
                                <?php else: ?>
                                    Belum ada data check-in tercatat
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <a href="<?= site_url('admin/presensi') ?>"
                        class="inline-flex items-center gap-1 pl-3 pr-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 rounded-full text-emerald-700 text-xs font-bold no-underline transition-colors shrink-0">
                        Semua (<?= $isHariSekolah ? (int)$ph['hadir'] : $checkinCount ?>) <i class="bi bi-chevron-right text-[10px]"></i>
                    </a>
                </div>

                <div class="flex-1">
                    <?php if (!empty($recentPresensi) && count($recentPresensi) > 0): ?>
                    <?php
                    // Helper: jam_masuk tersimpan sebagai DATETIME ("2026-09-15 07:05:00"),
                    // jadi harus di-parse, bukan di-substr langsung.
                    $formatJam = function ($val) {
                        if (empty($val) || $val === '0000-00-00 00:00:00' || $val === '0000-00-00') return '--:--';
                        $ts = strtotime($val);
                        if ($ts !== false) return date('H:i', $ts);
                        // fallback: ambil segmen HH:MM terakhir (mis. "07:05:00" / "2026-.. 07:05")
                        if (preg_match('/(\d{1,2}:\d{2})/', (string)$val, $m)) return substr($m[1], 0, 5);
                        return '--:--';
                    };
                    $resolveFoto = function ($foto) {
                        if (empty($foto)) return base_url('assets/img/user.png');
                        $foto = trim((string)$foto);
                        if (preg_match('#^https?://#i', $foto)) return $foto;
                        if (str_starts_with($foto, 'uploads/') || str_starts_with($foto, 'assets/')) return base_url($foto);
                        if (str_contains($foto, '/')) return base_url(ltrim($foto, '/'));
                        return base_url('uploads/profiles/' . $foto);
                    };
                    ?>
                    <div class="divide-y divide-slate-100 checkin-scroll max-h-[460px] overflow-y-auto -mx-1 px-1">
                        <?php foreach ($recentPresensi as $idx => $att):
                            $namaGuru = trim(($att->nama_guru ?? '') ?: (($att->first_name ?? '') . ' ' . ($att->last_name ?? '')));
                            if (!$namaGuru) $namaGuru = 'Guru';
                            $fotoUrl = $resolveFoto($att->foto ?? null);
                            $st = strtoupper(trim($att->status_masuk ?? ''));
                            [$bcls, $blbl, $dotCls, $ringCls] = match(true) {
                                $st === 'ONTIME'             => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Tepat Waktu', 'bg-emerald-500', 'ring-emerald-100'],
                                $st === 'LATE'               => ['bg-amber-50 text-amber-700 border-amber-200',         'Terlambat',   'bg-amber-500',   'ring-amber-100'],
                                $st === 'WFH'                => ['bg-sky-50 text-sky-700 border-sky-200',               'WFH',         'bg-sky-500',     'ring-sky-100'],
                                str_contains($st, 'DINAS')   => ['bg-indigo-50 text-indigo-700 border-indigo-200',     'Dinas',       'bg-indigo-500',  'ring-indigo-100'],
                                $st === 'PENDING_VERIFICATION' => ['bg-slate-100 text-slate-600 border-slate-200',     'Verifikasi',  'bg-slate-400',   'ring-slate-200'],
                                default                      => ['bg-slate-100 text-slate-600 border-slate-200',       $st ? ucfirst(strtolower($st)) : '-', 'bg-slate-300', 'ring-slate-200'],
                            };
                            $jamTampil = $formatJam($att->jam_masuk ?? null);
                            $telatMnt = (int)($att->telat_menit ?? 0);
                        ?>
                        <div class="checkin-row flex items-center gap-3 py-3 px-2 -mx-2 rounded-2xl" title="<?= esc($namaGuru) ?>">
                            <div class="relative shrink-0">
                                <img src="<?= esc($fotoUrl) ?>" alt="<?= esc($namaGuru) ?>" loading="lazy"
                                    class="w-10 h-10 rounded-full object-cover ring-2 <?= $ringCls ?> bg-slate-100"
                                    onerror="this.onerror=null;this.src='<?= base_url('assets/img/user.png') ?>'">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full <?= $dotCls ?> ring-2 ring-white"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-slate-800 truncate leading-tight"><?= esc($namaGuru) ?></p>
                                <p class="text-[11px] text-slate-400 mt-1 truncate flex items-center gap-1.5 min-w-0">
                                    <?php if (!empty($att->nip)): ?>
                                        <span class="tabular font-medium text-slate-400 shrink-0"><?= esc($att->nip) ?></span>
                                        <?php if (!empty($att->nama_lokasi_masuk)): ?>
                                            <span class="text-slate-200 shrink-0">&bull;</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($att->nama_lokasi_masuk)): ?>
                                        <span class="inline-flex items-center gap-1 truncate">
                                            <i class="bi bi-geo-alt shrink-0"></i>
                                            <span class="truncate"><?= esc($att->nama_lokasi_masuk) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1">
                                            <i class="bi bi-clock"></i> <?= esc($jamTampil) ?> WIB
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $bcls ?>">
                                    <?= $blbl ?>
                                </span>
                                <span class="inline-flex items-center gap-1 text-[13px] font-extrabold text-slate-700 tabular leading-none">
                                    <i class="bi bi-clock text-[11px] text-slate-300"></i><?= esc($jamTampil) ?>
                                </span>
                                <?php if ($st === 'LATE' && $telatMnt > 0): ?>
                                    <span class="text-[10px] font-semibold text-amber-600 leading-none">+<?= $telatMnt ?> mnt</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-2xl <?= $isHariSekolah ? 'bg-slate-100 text-slate-300' : 'bg-amber-50 text-amber-400' ?> flex items-center justify-center mb-3">
                            <i class="bi <?= $isHariSekolah ? 'bi-inbox' : 'bi-calendar-x' ?> text-2xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">
                            <?= $isHariSekolah ? 'Belum ada presensi hari ini' : 'Belum ada riwayat check-in' ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            <?= $isHariSekolah
                                ? 'Data absensi guru akan muncul otomatis di sini'
                                : 'Data check-in akan tampil setelah ada presensi pada hari sekolah' ?>
                        </p>
                        <?php if ($isHariSekolah): ?>
                            <a href="<?= site_url('admin/presensi/manual') ?>"
                                class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl text-xs font-semibold hover:bg-blue-100 transition-colors no-underline">
                                <i class="bi bi-plus-circle"></i> Input Manual
                            </a>
                        <?php else: ?>
                            <a href="<?= site_url('admin/presensi/rekap') ?>"
                                class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl text-xs font-semibold hover:bg-blue-100 transition-colors no-underline">
                                <i class="bi bi-file-earmark-bar-graph"></i> Buka Rekap Presensi
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pt-3 border-t border-slate-100 mt-2 flex items-center justify-between gap-2">
                    <?php if ($isHariSekolah): ?>
                        <span class="flex items-center gap-1.5 text-[11px] text-slate-400">
                            <span class="relative w-1.5 h-1.5 rounded-full bg-emerald-500 inline-flex shrink-0">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            </span>
                            Diperbarui otomatis
                        </span>
                    <?php else: ?>
                        <span class="flex items-center gap-1.5 text-[11px] text-slate-400">
                            <i class="bi bi-clock-history text-slate-300"></i>
                            Data historis &middot; tidak diperbarui hari ini
                        </span>
                    <?php endif; ?>
                    <a href="<?= site_url('admin/presensi') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-bold no-underline transition-colors">
                        Tabel Lengkap <i class="bi bi-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- ── Aktivitas Sistem (Log) ─────────────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 flex flex-col">
                <div class="flex items-center justify-between pb-4 mb-1 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                            <i class="bi bi-activity text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800 leading-none">Aktivitas Sistem</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Log pengguna terbaru</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">
                            <i class="bi bi-shield-check text-emerald-500"></i> Audit Trail
                        </span>
                        <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
                        <button type="button" id="btnClearDashboardLogs"
                            class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-red-50 border border-red-200 text-red-500 hover:bg-red-100 hover:text-red-600 transition-colors"
                            title="Hapus seluruh log aktivitas">
                            <i class="bi bi-trash3 text-xs"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-1">
                    <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
                    <?php
                    // ── Peta hari & bulan Indonesia (mandiri, tanpa helper eksternal) ──
                    $logHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    $logBulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                    $logBulanFull = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    $todayKey = date('Y-m-d');
                    $yesterdayKey = date('Y-m-d', strtotime('-1 day'));
                    $lastDateKey = null;
                    $nLogs = count($recentLogs);
                    ?>
                    <div id="dashboardLogsBody">
                        <?php foreach ($recentLogs as $idx => $log):
                            $uNama = trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: 'Pengguna';
                            $fotoLog = !empty($log->foto) ? base_url(ltrim($log->foto, '/')) : base_url('assets/img/user.png');
                            $ts = !empty($log->waktu) ? strtotime($log->waktu) : false;
                            $dateKey = $ts ? date('Y-m-d', $ts) : '';
                            $waktuLog = $ts ? date('H:i', $ts) : '';
                            $logId = (int)($log->id_log ?? 0);

                            // ── Label relatif + tanggal Bahasa Indonesia ──
                            $isToday = ($dateKey !== '' && $dateKey === $todayKey);
                            $isYesterday = ($dateKey !== '' && $dateKey === $yesterdayKey);
                            if ($ts) {
                                $wIdx = (int)date('w', $ts);
                                $mIdx = (int)date('n', $ts);
                                $tglFull = $logHari[$wIdx] . ', ' . date('j', $ts) . ' ' . $logBulanFull[$mIdx] . ' ' . date('Y', $ts);
                                $tglShort = date('j', $ts) . ' ' . $logBulan[$mIdx] . ' ' . date('Y', $ts);
                                $relLabel = $isToday ? 'Hari ini' : ($isYesterday ? 'Kemarin' : $logHari[$wIdx]);
                            } else {
                                $tglFull = '';
                                $tglShort = '';
                                $relLabel = '';
                            }

                            // ── Header grup: tampil tiap tanggal berganti ──
                            $showDateHeader = ($dateKey !== '' && $dateKey !== $lastDateKey);
                            $lastDateKey = $dateKey;

                            // ── Garis konektor timeline hanya ke log berikutnya di tanggal yang sama ──
                            $nextSameDate = false;
                            if ($idx < $nLogs - 1 && $ts) {
                                $nextWaktu = $recentLogs[$idx + 1]->waktu ?? null;
                                $nextTs = $nextWaktu ? strtotime($nextWaktu) : false;
                                $nextSameDate = ($nextTs && date('Y-m-d', $nextTs) === $dateKey);
                            }
                        ?>
                        <?php if ($showDateHeader): ?>
                        <div class="flex items-center gap-2 pt-3 first:pt-0">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $isToday ? 'bg-emerald-50 text-emerald-600' : ($isYesterday ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500') ?>">
                                <?= esc($relLabel) ?>
                            </span>
                            <span class="text-[10px] font-medium text-slate-400"><?= esc($tglFull) ?></span>
                            <span class="flex-1 border-t border-dashed border-slate-200"></span>
                        </div>
                        <?php endif; ?>
                        <div id="dashboard-log-<?= $logId ?>" class="group flex items-start gap-3 py-3 border-b border-slate-50 last:border-b-0 relative <?= $nextSameDate ? 'tl-connector' : '' ?>">
                            <img src="<?= $fotoLog ?>" alt=""
                                class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200 shrink-0 mt-0.5 relative z-10"
                                onerror="this.src='<?= base_url('assets/img/user.png') ?>'">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 leading-none"><?= esc($uNama) ?></p>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed break-words">
                                    <?= esc($log->keterangan ?? 'Melakukan aksi di sistem') ?>
                                </p>
                            </div>
                            <?php if ($waktuLog): ?>
                            <div class="text-right shrink-0 mt-0.5" title="<?= esc($tglFull . ' • ' . date('H:i:s', $ts)) ?>">
                                <p class="text-xs font-bold text-slate-700 leading-none tabular"><?= $waktuLog ?></p>
                                <?php if (!$isToday): ?>
                                <p class="text-[10px] text-slate-400 mt-1 leading-none"><?= esc($isYesterday ? 'Kemarin' : $tglShort) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($logId > 0): ?>
                            <button type="button" class="btn-delete-dashboard-log shrink-0 w-6 h-6 inline-flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors sm:opacity-0 sm:group-hover:opacity-100 mt-0.5"
                                data-id="<?= $logId ?>" title="Hapus log ini">
                                <i class="bi bi-x-lg text-[10px]"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-300 flex items-center justify-center mb-3">
                            <i class="bi bi-clock-history text-2xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">Belum ada aktivitas tercatat</p>
                        <p class="text-xs text-slate-400 mt-1">Log sistem akan muncul saat ada aktivitas</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between gap-2">
                    <p class="text-[11px] text-slate-400">Audit trail tercatat secara aman untuk transparansi sistem</p>
                    <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
                    <button type="button" id="btnClearDashboardLogsFooter"
                        class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-red-500 transition-colors shrink-0">
                        <i class="bi bi-trash3 text-[10px]"></i> Hapus Semua
                    </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    function dashboardCsrf() { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }

    // ── Hapus satu log ──────────────────────────────────
    $(document).on('click', '.btn-delete-dashboard-log', function () {
        const id = $(this).data('id');
        const $row = $('#dashboard-log-' + id);
        $.post('<?= site_url('dashboard/hapuslog') ?>/' + encodeURIComponent(id), dashboardCsrf(), function (res) {
            if (res && res.status) {
                $row.fadeOut(250, function () {
                    $(this).remove();
                    if ($('#dashboardLogsBody > div[id^="dashboard-log-"]').length === 0) location.reload();
                });
            } else {
                Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        });
    });

    // ── Hapus seluruh log ───────────────────────────────
    function clearDashboardLogs() {
        Swal.fire({
            title: 'Hapus Seluruh Log?',
            text: 'Semua riwayat aktivitas pengguna akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('<?= site_url('dashboard/hapuslog') ?>', dashboardCsrf(), function (res) {
                if (res && res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1600, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        });
    }
    $(document).on('click', '#btnClearDashboardLogs, #btnClearDashboardLogsFooter', clearDashboardLogs);
});
</script>
<?= $this->endSection() ?>
