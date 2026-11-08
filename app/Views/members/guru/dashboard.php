<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.12);
    }
    .attendance-tile {
        transition: all 0.2s ease;
    }
    .attendance-tile:hover {
        transform: translateY(-2px);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header / Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-900 text-white p-6 md:p-8 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-bold text-blue-200 mb-2.5 backdrop-blur-sm border border-white/15">
                    <i class="bi bi-calendar3"></i>
                    <span><?= buat_tanggal(date('l, d F Y')) ?></span>
                    <?php if (isset($tp_active) && $tp_active): ?>
                        <span class="text-white/40">&middot;</span>
                        <span>TP <?= esc($tp_active->tahun) ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight">
                    Halo, <?= esc($guru->nama_guru ?? ($user->first_name ?? 'Bapak/Ibu Guru')) ?>! 👋
                </h1>
                <p class="text-xs md:text-sm text-blue-100/80 mt-1 max-w-xl">
                    Selamat beraktivitas. Pantau kehadiran pribadi, isi jurnal mengajar harian, dan laksanakan tugas piket sekolah.
                </p>
            </div>

            <!-- Quick Action Button -->
            <div class="flex items-center gap-3 shrink-0 flex-wrap">
                <a href="<?= site_url('presensi') ?>"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-white hover:bg-blue-50 text-blue-800 rounded-2xl text-xs font-extrabold shadow-lg shadow-black/10 transition-all no-underline">
                    <i class="bi bi-camera-fill text-blue-600 text-sm"></i> Presensi Selfie (Absen)
                </a>
                <a href="<?= site_url('guru/jurnal/create') ?>"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-blue-500/30 hover:bg-blue-500/40 text-white border border-white/20 rounded-2xl text-xs font-bold backdrop-blur-sm transition-all no-underline">
                    <i class="bi bi-plus-circle text-sm"></i> Tulis Jurnal
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Banner Jika Guru Bertugas Piket Hari Ini -->
    <?php if (!empty($is_piket_hari_ini)): ?>
    <div class="p-5 rounded-3xl bg-amber-500/10 border-2 border-amber-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-amber-500/30">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <h4 class="text-sm font-extrabold text-amber-950">Anda Bertugas Sebagai Petugas Piket Hari Ini!</h4>
                <p class="text-xs text-amber-900/80 mt-0.5">Bantu mencatat buku tamu, memantau kehadiran guru di kelas, dan mencatat laporan kejadian.</p>
            </div>
        </div>
        <a href="<?= site_url('guru/piket') ?>"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-amber-600/20 no-underline shrink-0">
            Buka Panel Piket <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>

    <!-- Row 1: Presensi Hari Ini & Jurnal Mengajar Hari Ini -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Status Presensi Guru Hari Ini -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                            <i class="bi bi-fingerprint"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Status Kehadiran Hari Ini</h2>
                            <p class="text-[11px] text-slate-400">Pencatatan absensi masuk & pulang</p>
                        </div>
                    </div>
                    <a href="<?= site_url('guru/absensi') ?>" class="text-xs font-bold text-blue-600 hover:text-blue-700 no-underline">
                        Riwayat &rarr;
                    </a>
                </div>

                <?php if (!empty($presensi_hari_ini)): ?>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Jam Masuk</span>
                        <p class="text-xl font-extrabold text-slate-800"><?= substr($presensi_hari_ini->jam_masuk ?? '--:--', 0, 5) ?></p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                            <?= esc($presensi_hari_ini->status_masuk ?? 'HADIR') ?>
                        </span>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Jam Pulang</span>
                        <p class="text-xl font-extrabold text-slate-800"><?= !empty($presensi_hari_ini->jam_pulang) ? substr($presensi_hari_ini->jam_pulang, 0, 5) : '--:--' ?></p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-extrabold <?= !empty($presensi_hari_ini->jam_pulang) ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-600' ?>">
                            <?= !empty($presensi_hari_ini->jam_pulang) ? 'SUDAH PULANG' : 'BELUM PULANG' ?>
                        </span>
                    </div>
                </div>
                <?php else: ?>
                <div class="py-6 px-4 rounded-2xl bg-rose-50/70 border border-rose-100 text-center mb-4">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-2 text-lg">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <p class="text-xs font-bold text-rose-900">Anda Belum Mengisi Absen Masuk Hari Ini</p>
                    <p class="text-[11px] text-rose-700/80 mt-0.5">Segera lakukan presensi selfie atau scan lokasi sekolah</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500 flex items-center gap-1.5">
                    <i class="bi bi-geo-alt-fill text-blue-600"></i> Geofence Sekolah Aktif
                </span>
                <a href="<?= site_url('presensi') ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs no-underline">
                    <i class="bi bi-box-arrow-in-right"></i> Buka Layar Absen
                </a>
            </div>
        </div>

        <!-- Jurnal Mengajar Hari Ini -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Jurnal Mengajar Hari Ini</h2>
                            <p class="text-[11px] text-slate-400">Catatan materi KBM mandiri</p>
                        </div>
                    </div>
                    <a href="<?= site_url('guru/jurnal') ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 no-underline">
                        Semua Jurnal &rarr;
                    </a>
                </div>

                <?php if (!empty($jurnal_hari_ini) && count($jurnal_hari_ini) > 0): ?>
                <div class="space-y-2 mb-4">
                    <?php foreach ($jurnal_hari_ini as $jh): ?>
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate">
                                <?= esc($jh->nama_mapel) ?> &middot; <span class="text-indigo-600"><?= esc($jh->nama_kelas) ?></span>
                            </p>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5"><?= esc($jh->materi_pokok) ?></p>
                        </div>
                        <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-lg text-[10px] font-black shrink-0">
                            Jam <?= esc($jh->jam_ke) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="py-6 px-4 rounded-2xl bg-slate-50 text-center mb-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-600">Belum ada jurnal mengajar yang diisi hari ini</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Catat agenda pembelajaran untuk setiap jam kelas yang Anda ajar</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    <?= count($jurnal_hari_ini ?? []) ?> Sesi Tercatat
                </span>
                <a href="<?= site_url('guru/jurnal/create') ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs no-underline">
                    <i class="bi bi-plus-lg"></i> Tulis Jurnal Baru
                </a>
            </div>
        </div>

    </div>

    <!-- Row 2: Kehadiran Bulan Ini -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Akumulasi Kehadiran Bulan Ini</h3>
                    <p class="text-[11px] text-slate-400">Ringkasan hari kerja dan kepatuhan waktu</p>
                </div>
            </div>
            <a href="<?= site_url('guru/absensi') ?>" class="text-xs font-bold text-blue-600 hover:text-blue-700 no-underline">
                Rincian Absen &rarr;
            </a>
        </div>

        <?php $rk = $ringkasan ?? ['hadir' => 0, 'telat' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'dinas' => 0, 'wfh' => 0, 'alpha' => 0]; ?>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <?php
            $rkTiles = [
                ['Hadir', $rk['hadir'], 'bg-emerald-50 text-emerald-700 border-emerald-200/70', 'bi-check-circle-fill text-emerald-500'],
                ['Terlambat', $rk['telat'], 'bg-amber-50 text-amber-700 border-amber-200/70', 'bi-clock-history text-amber-500'],
                ['Izin', $rk['izin'], 'bg-sky-50 text-sky-700 border-sky-200/70', 'bi-envelope-paper-fill text-sky-500'],
                ['Sakit', $rk['sakit'], 'bg-purple-50 text-purple-700 border-purple-200/70', 'bi-heart-pulse-fill text-purple-500'],
                ['Dinas Luar', $rk['dinas'], 'bg-indigo-50 text-indigo-700 border-indigo-200/70', 'bi-briefcase-fill text-indigo-500'],
                ['WFH', $rk['wfh'], 'bg-teal-50 text-teal-700 border-teal-200/70', 'bi-house-door-fill text-teal-500'],
                ['Alpha', $rk['alpha'], 'bg-rose-50 text-rose-700 border-rose-200/70', 'bi-x-circle-fill text-rose-500'],
            ];
            foreach ($rkTiles as [$label, $val, $classes, $iconCls]):
            ?>
            <div class="attendance-tile <?= $classes ?> border rounded-2xl p-4 text-center">
                <div class="flex items-center justify-center gap-1 text-xs mb-1">
                    <i class="bi <?= $iconCls ?>"></i>
                </div>
                <div class="text-2xl font-black leading-none"><?= (int) $val ?></div>
                <div class="text-[11px] font-bold mt-1.5 uppercase tracking-wide opacity-80"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>