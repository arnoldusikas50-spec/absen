<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .dapodik-tab-btn { transition: all .15s ease; }
    .dapodik-tab-btn.active { color: var(--primary-700) !important; background: var(--primary-50) !important; border-color: var(--primary-200) !important; }
    .sync-card { transition: transform .15s ease, box-shadow .15s ease; }
    .sync-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -10px rgba(15,23,42,.12); }
    .dapodik-pane { display: none; }
    .dapodik-pane.active { display: block; animation: fadeInUp .2s ease-out; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$uri      = uri_string();
$isAdmin  = str_starts_with($uri, 'admin/');
$prefix   = $isAdmin ? 'admin/integrasi/dapodik' : 'integrasi/dapodik';
$saveUrl  = site_url($prefix . '/saveSettings');
$testUrl  = site_url($prefix . '/testConnection');
$syncUrl  = site_url($prefix . '/syncApi');
$clearUrl = site_url($prefix . '/clearLogs');
$percGuru  = ($stats['total_guru'] ?? 0) > 0 ? round((($stats['guru_dapo'] ?? 0) / $stats['total_guru']) * 100, 1) : 0;
$percSiswa = ($stats['total_siswa'] ?? 0) > 0 ? round((($stats['siswa_dapo'] ?? 0) / $stats['total_siswa']) * 100, 1) : 0;
$percKelas = ($stats['total_kelas'] ?? 0) > 0 ? round((($stats['kelas_dapo'] ?? 0) / $stats['total_kelas']) * 100, 1) : 0;
$percMapel = ($stats['total_mapel'] ?? 0) > 0 ? round((($stats['mapel_dapo'] ?? 0) / $stats['total_mapel']) * 100, 1) : 0;
$cronUrl   = site_url('cron/dapodik_sync?key=' . (env('CRON_SECRET_KEY') ?? ''));
?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center text-xl shadow-sm shadow-blue-500/20 shrink-0">
                <i class="bi bi-cloud-arrow-down"></i>
            </div>
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-1.5">
                    <i class="bi bi-router-fill"></i> Web Service Dapodik
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($title ?? 'Integrasi Data Dapodik') ?></h1>
                <p class="text-xs text-slate-500 mt-1">Sinkronisasi Sekolah, GTK, Siswa, Rombel, Mapel, Pembelajaran &amp; Ruang dari Dapodik lokal.</p>
            </div>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <span>Integrasi</span>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-blue-600">Dapodik</span>
        </nav>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-4 py-3 text-xs font-semibold">
            <i class="bi bi-check-circle-fill text-lg"></i>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-4 py-3 text-xs font-semibold">
            <i class="bi bi-exclamation-triangle-fill text-lg"></i>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <!-- Status koneksi -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4 justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 hidden sm:flex items-center justify-center text-xl shrink-0">
                    <i class="bi bi-router-fill"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-800 text-sm">Status Web Service:</span>
                        <span id="connectionStatusBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500">
                            <i class="bi bi-dash-circle"></i>Belum Dites
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-slate-50 text-slate-500 border border-slate-200">
                            <i class="bi bi-clock-history text-blue-500"></i>Sync Terakhir:
                            <strong class="text-slate-700"><?= !empty($settings['last_sync_at']) ? date('d M Y H:i', strtotime($settings['last_sync_at'])) : 'Belum Pernah' ?></strong>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 flex-wrap mt-1.5 text-[11px] text-slate-500">
                        <span><i class="bi bi-globe mr-1 text-blue-500"></i>Server: <code class="bg-slate-50 px-2 py-0.5 rounded-md text-blue-700 border border-slate-200 font-mono"><?= esc($settings['server_url'] ?? 'http://localhost:5774') ?></code></span>
                        <span><i class="bi bi-building mr-1 text-blue-500"></i>NPSN: <code class="bg-slate-50 px-2 py-0.5 rounded-md text-slate-700 border border-slate-200 font-mono"><?= esc($settings['npsn'] ?? 'N/A') ?></code></span>
                        <span><i class="bi bi-calendar3 mr-1 text-blue-500"></i>Semester: <code class="bg-slate-50 px-2 py-0.5 rounded-md text-slate-700 border border-slate-200 font-mono"><?= esc($settings['semester_id'] ?? '20251') ?></code></span>
                    </div>
                </div>
            </div>
            <button type="button" id="btnTestConnection"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20 shrink-0">
                <i class="bi bi-plug-fill"></i> Tes Koneksi API
            </button>
        </div>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php
        $statCards = [
            ['label' => 'Guru / GTK', 'dapo' => $stats['guru_dapo'] ?? 0, 'total' => $stats['total_guru'] ?? 0, 'perc' => $percGuru, 'icon' => 'bi-person-badge', 'bar' => 'bg-blue-500', 'text' => 'text-blue-700', 'soft' => 'bg-blue-50 text-blue-600'],
            ['label' => 'Peserta Didik', 'dapo' => $stats['siswa_dapo'] ?? 0, 'total' => $stats['total_siswa'] ?? 0, 'perc' => $percSiswa, 'icon' => 'bi-people-fill', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'soft' => 'bg-emerald-50 text-emerald-600'],
            ['label' => 'Rombel / Kelas', 'dapo' => $stats['kelas_dapo'] ?? 0, 'total' => $stats['total_kelas'] ?? 0, 'perc' => $percKelas, 'icon' => 'bi-building', 'bar' => 'bg-cyan-500', 'text' => 'text-cyan-700', 'soft' => 'bg-cyan-50 text-cyan-600'],
            ['label' => 'Mata Pelajaran', 'dapo' => $stats['mapel_dapo'] ?? 0, 'total' => $stats['total_mapel'] ?? 0, 'perc' => $percMapel, 'icon' => 'bi-book', 'bar' => 'bg-amber-500', 'text' => 'text-amber-700', 'soft' => 'bg-amber-50 text-amber-600'],
        ];
        foreach ($statCards as $c): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 border-l-4 <?= str_replace(['bg-blue-500','bg-emerald-500','bg-cyan-500','bg-amber-500'], ['border-l-blue-500','border-l-emerald-500','border-l-cyan-500','border-l-amber-500'], $c['bar']) ?>">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl <?= $c['soft'] ?> flex items-center justify-center text-xl shrink-0"><i class="bi <?= $c['icon'] ?>"></i></div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= $c['label'] ?></p>
                    <p class="text-xl font-extrabold text-slate-900 leading-tight"><?= number_format($c['dapo']) ?> <span class="text-sm font-semibold text-slate-400">/ <?= number_format($c['total']) ?></span></p>
                    <div class="h-1.5 bg-slate-100 rounded-full mt-2 overflow-hidden"><div class="h-full <?= $c['bar'] ?> rounded-full" style="width: <?= $c['perc'] ?>%"></div></div>
                    <p class="text-[11px] font-bold <?= $c['text'] ?> mt-1"><i class="bi bi-check-circle-fill mr-1"></i><?= $c['perc'] ?>% Terhubung</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex items-center gap-2 p-3 border-b border-slate-100 overflow-x-auto">
            <button type="button" data-tab="live" class="dapodik-tab-btn active inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-500 border border-transparent whitespace-nowrap">
                <i class="bi bi-arrow-repeat"></i> Live Direct Sync (API)
            </button>
            <button type="button" data-tab="settings" class="dapodik-tab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-500 border border-transparent whitespace-nowrap">
                <i class="bi bi-gear-fill"></i> Pengaturan Web Service
            </button>
            <button type="button" data-tab="logs" class="dapodik-tab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-500 border border-transparent whitespace-nowrap">
                <i class="bi bi-clock-history"></i> Riwayat &amp; Log
                <?php if (!empty($logs)): ?><span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[10px] font-extrabold"><?= count($logs) ?></span><?php endif; ?>
            </button>
        </div>

        <div class="p-5">
            <!-- TAB LIVE SYNC -->
            <div id="pane-live" class="dapodik-pane active">
                <h2 class="text-sm font-extrabold text-slate-900"><i class="bi bi-arrow-repeat text-blue-600 mr-2"></i>Sinkronisasi Langsung via Web Service API</h2>
                <p class="text-xs text-slate-500 mt-1 mb-4">Pilih entitas data yang ingin ditarik real-time dari Web Service Dapodik lokal ke database sekolah.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-4">
                    <?php
                    $entities = [
                        ['key' => 'gtk', 'label' => 'Guru / GTK', 'desc' => 'NUPTK, NIP, NIK, biodata & kepegawaian.', 'badge' => number_format($stats['guru_dapo'] ?? 0) . ' / ' . number_format($stats['total_guru'] ?? 0), 'icon' => 'bi-person-badge', 'soft' => 'bg-blue-50 text-blue-600', 'btn' => 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm shadow-blue-500/20'],
                        ['key' => 'siswa', 'label' => 'Peserta Didik', 'desc' => 'NISN, NIS, NIK, ortu & alamat.', 'badge' => number_format($stats['siswa_dapo'] ?? 0) . ' / ' . number_format($stats['total_siswa'] ?? 0), 'icon' => 'bi-people-fill', 'soft' => 'bg-emerald-50 text-emerald-600', 'btn' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-500/20'],
                        ['key' => 'rombel', 'label' => 'Rombel / Kelas', 'desc' => 'Nama kelas, tingkat & wali kelas.', 'badge' => number_format($stats['kelas_dapo'] ?? 0) . ' / ' . number_format($stats['total_kelas'] ?? 0), 'icon' => 'bi-building', 'soft' => 'bg-cyan-50 text-cyan-600', 'btn' => 'bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm shadow-cyan-500/20'],
                        ['key' => 'mapel', 'label' => 'Mata Pelajaran', 'desc' => 'Direktori mapel & kelompok mapel.', 'badge' => number_format($stats['mapel_dapo'] ?? 0) . ' / ' . number_format($stats['total_mapel'] ?? 0), 'icon' => 'bi-book', 'soft' => 'bg-amber-50 text-amber-600', 'btn' => 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm shadow-amber-500/20'],
                        ['key' => 'pembelajaran', 'label' => 'Pembelajaran', 'desc' => 'Penugasan mapel & jadwal mengajar.', 'badge' => 'Jadwal', 'icon' => 'bi-journal-bookmark-fill', 'soft' => 'bg-slate-100 text-slate-600', 'btn' => 'bg-slate-700 hover:bg-slate-800 text-white shadow-sm'],
                        ['key' => 'ruang', 'label' => 'Ruang / Sarpras', 'desc' => 'Gedung, ruang kelas & lab.', 'badge' => 'Sarpras', 'icon' => 'bi-door-open-fill', 'soft' => 'bg-rose-50 text-rose-600', 'btn' => 'bg-rose-600 hover:bg-rose-700 text-white shadow-sm shadow-rose-500/20'],
                    ];
                    foreach ($entities as $e): ?>
                    <div class="sync-card bg-white rounded-2xl border border-slate-200 p-4 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl <?= $e['soft'] ?> flex items-center justify-center text-lg shrink-0"><i class="bi <?= $e['icon'] ?>"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-[13px] font-extrabold text-slate-900 truncate"><?= $e['label'] ?></h3>
                                    <span class="px-2 py-0.5 bg-slate-50 border border-slate-200 rounded-full text-[10px] font-bold text-slate-500 whitespace-nowrap"><?= $e['badge'] ?></span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5"><?= $e['desc'] ?></p>
                            </div>
                        </div>
                        <button type="button" data-entitas="<?= $e['key'] ?>"
                            class="btn-sync inline-flex items-center justify-center gap-2 w-full px-3 py-2 rounded-xl text-xs font-bold transition-colors <?= $e['btn'] ?>">
                            <i class="bi bi-arrow-repeat"></i> Sync <?= $e['label'] ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="rounded-2xl p-5 text-center border border-slate-800" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                    <h3 class="font-extrabold text-white text-sm"><i class="bi bi-rocket-takeoff-fill text-amber-400 mr-2"></i>Sinkronisasi Seluruh Data Sekaligus</h3>
                    <p class="text-slate-300/80 text-xs mt-1 mb-3 max-w-2xl mx-auto">Tarik dan perbarui berurutan: Sekolah, GTK, Siswa, Rombel, Mapel, Pembelajaran &amp; Ruang dalam 1 proses otomatis.</p>
                    <button type="button" data-entitas="all"
                        class="btn-sync inline-flex items-center gap-2 px-5 py-2.5 bg-amber-400 hover:bg-amber-500 text-slate-900 rounded-xl text-xs font-extrabold transition-colors shadow-sm">
                        <i class="bi bi-rocket-takeoff-fill"></i> Jalankan SINKRONISASI LENGKAP (SYNC ALL)
                    </button>
                </div>
            </div>

            <!-- TAB SETTINGS -->
            <div id="pane-settings" class="dapodik-pane">
                <form action="<?= $saveUrl ?>" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?= csrf_field() ?>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">URL Server Web Service <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-globe absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="url" id="inputServerUrl" name="server_url" required
                                    value="<?= esc($settings['server_url'] ?? 'http://localhost:5774') ?>"
                                    placeholder="http://localhost:5774"
                                    class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Default port Web Service Dapodik <code class="font-mono">5774</code> (HTTP) atau URL reverse-proxy sekolah.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">NPSN Sekolah <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-building absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" id="inputNpsn" name="npsn" required maxlength="20"
                                    value="<?= esc($settings['npsn'] ?? '') ?>" placeholder="cth: 50305678"
                                    class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Semester ID <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-calendar3 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" name="semester_id" required maxlength="10"
                                    value="<?= esc($settings['semester_id'] ?? '20251') ?>" placeholder="cth: 20251 (Ganjil) / 20252 (Genap)"
                                    class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Format: <code class="font-mono">YYYY + 1 (ganjil) / 2 (genap)</code>.</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Token Rahasia (Bearer)</label>
                            <div class="relative">
                                <i class="bi bi-key-fill absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="password" id="inputSecretToken" name="secret_token" autocomplete="new-password"
                                    placeholder="<?= !empty($settings['secret_token']) ? '•••• tersimpan — kosongkan untuk mempertahankan' : 'Token dari Dapodik → Pengaturan → Web Service' ?>"
                                    class="w-full pl-9 pr-11 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <button type="button" id="btnToggleToken" title="Tampilkan/Sembunyikan"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors">
                                    <i class="bi bi-eye" id="iconToggleToken"></i>
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">
                                <?= !empty($settings['secret_token'])
                                    ? 'Token sudah tersimpan dan tidak ditampilkan lagi. Kosongkan field ini untuk menyimpan pengaturan tanpa mengganti token.'
                                    : 'Belum ada token tersimpan — wajib diisi sebelum sinkronisasi bisa berjalan.' ?>
                            </p>
                        </div>
                        <label class="flex items-center justify-between gap-3 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-3 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700">Aktifkan Integrasi Dapodik</span>
                            <input type="checkbox" name="is_active" value="1" <?= !empty($settings['is_active']) ? 'checked' : '' ?>
                                class="w-4 h-4 accent-blue-600">
                        </label>
                        <label class="flex items-center justify-between gap-3 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-3 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700">Auto Sync via Web-Cron</span>
                            <input type="checkbox" name="auto_sync" value="1" <?= !empty($settings['auto_sync']) ? 'checked' : '' ?>
                                class="w-4 h-4 accent-blue-600">
                        </label>
                        <div class="flex justify-end pt-1">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                                <i class="bi bi-save"></i> Simpan Konfigurasi
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TAB LOGS -->
            <div id="pane-logs" class="dapodik-pane">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
                    <h2 class="text-sm font-extrabold text-slate-900"><i class="bi bi-clock-history text-blue-600 mr-2"></i>Riwayat Aktivitas Sinkronisasi</h2>
                    <?php if (!empty($logs)): ?>
                        <button type="button" id="btnClearLogs"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl text-[11px] font-bold transition-colors">
                            <i class="bi bi-trash"></i> Bersihkan Log
                        </button>
                    <?php endif; ?>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-xs min-w-[820px]">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider">
                                <th class="text-left font-bold px-3 py-2.5">Waktu</th>
                                <th class="text-left font-bold px-3 py-2.5">Entitas</th>
                                <th class="text-left font-bold px-3 py-2.5">Status</th>
                                <th class="text-center font-bold px-3 py-2.5">Diproses</th>
                                <th class="text-center font-bold px-3 py-2.5">Baru</th>
                                <th class="text-center font-bold px-3 py-2.5">Diperbarui</th>
                                <th class="text-left font-bold px-3 py-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap"><?= !empty($log['created_at']) ? date('d M Y H:i:s', strtotime($log['created_at'])) : '-' ?></td>
                                    <td class="px-3 py-2.5"><span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-full text-[10px] font-extrabold uppercase"><?= esc($log['entitas'] ?? '-') ?></span></td>
                                    <td class="px-3 py-2.5">
                                        <?php if (($log['status'] ?? '') === 'sukses'): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full text-[10px] font-bold"><i class="bi bi-check-circle"></i>Sukses</span>
                                        <?php elseif (($log['status'] ?? '') === 'parsial'): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-100 rounded-full text-[10px] font-bold"><i class="bi bi-exclamation-circle"></i>Parsial</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 border border-red-100 rounded-full text-[10px] font-bold"><i class="bi bi-x-circle"></i>Gagal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold text-slate-800"><?= number_format($log['total_diperoses'] ?? 0) ?></td>
                                    <td class="px-3 py-2.5 text-center font-bold text-emerald-600">+<?= number_format($log['total_ditambah'] ?? 0) ?></td>
                                    <td class="px-3 py-2.5 text-center font-bold text-sky-600"><?= number_format($log['total_diperbarui'] ?? 0) ?></td>
                                    <td class="px-3 py-2.5 text-slate-500 max-w-[280px] truncate" title="<?= esc($log['keterangan'] ?? '-') ?>"><?= esc($log['keterangan'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="px-3 py-10 text-center text-slate-400">
                                    <i class="bi bi-inbox text-3xl block mb-2 opacity-50"></i>
                                    Belum ada riwayat aktivitas sinkronisasi.
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Web-Cron helper -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0"><i class="bi bi-clock-history"></i></div>
                <div class="min-w-0">
                    <h3 class="text-sm font-extrabold text-slate-900">Web-Cron Auto Sync (Background Job)</h3>
                    <p class="text-[11px] text-slate-500">Pasang URL ini pada cron-job server agar sinkronisasi berjalan berkala.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-1">
                <input type="text" id="cronUrlInput" readonly value="<?= esc($cronUrl) ?>"
                    class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-mono text-slate-600 focus:outline-none">
                <button type="button" id="btnCopyCronUrl"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-100 rounded-xl text-[11px] font-bold transition-colors shrink-0">
                    <i class="bi bi-clipboard"></i> Salin
                </button>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    const TEST_URL  = '<?= $testUrl ?>';
    const SYNC_URL  = '<?= $syncUrl ?>';
    const CLEAR_URL = '<?= $clearUrl ?>';

    // Server selalu mengirim csrfName/csrfHash terbaru; simpan keduanya agar
    // panggilan berikutnya tidak ditolak CSRF setelah token di-rotate.
    function segarkanCsrf(res) {
        if (res && typeof res === 'object') {
            if (res.csrfName) CSRF_NAME = res.csrfName;
            if (res.csrfHash) CSRF_HASH = res.csrfHash;
        }
    }

    // Pesan sinkronisasi berasal dari data eksternal (nama/NPSN dari Dapodik),
    // jadi escaped sebelum masuk html: Swal.
    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    }

    // Tab switching (Tailwind, tanpa Bootstrap JS)
    $('.dapodik-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.dapodik-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.dapodik-pane').removeClass('active');
        $('#pane-' + tab).addClass('active');
    });

    $('#btnCopyCronUrl').on('click', function() {
        const url = $('#cronUrlInput').val();
        const done = () => Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'URL Web-Cron disalin!', showConfirmButton: false, timer: 2000 });
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(() => { $('#cronUrlInput').select(); document.execCommand('copy'); done(); });
        } else {
            $('#cronUrlInput').select(); document.execCommand('copy'); done();
        }
    });

    $('#btnToggleToken').on('click', function() {
        const $input = $('#inputSecretToken');
        const $icon = $('#iconToggleToken');
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    function setBadge(state, html) {
        const base = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold ';
        const $b = $('#connectionStatusBadge');
        $b.removeClass().addClass(base + (
            state === 'ok' ? 'bg-emerald-100 text-emerald-700' :
            state === 'fail' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500'
        )).html(html);
    }

    // Tes koneksi: kirim nilai form saat ini (bukan hanya CSRF)
    $('#btnTestConnection').on('click', function() {
        const $btn = $(this);
        const orig = $btn.html();
        $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span> Menguji...');
        setBadge('loading', '<i class="bi bi-hourglass-split"></i>Menguji...');

        const payload = {};
        payload[CSRF_NAME] = CSRF_HASH;
        payload['server_url'] = $('#inputServerUrl').val();
        payload['npsn'] = $('#inputNpsn').val();
        payload['secret_token'] = $('#inputSecretToken').val();

        $.post(TEST_URL, payload, function(res) {
            segarkanCsrf(res);
            $btn.prop('disabled', false).html(orig);
            if (res && res.status) {
                setBadge('ok', '<i class="bi bi-check-circle-fill"></i>Terhubung (200 OK)');
                Swal.fire({ title: 'Koneksi Berhasil', text: res.message || 'Terhubung ke Dapodik.', icon: 'success', confirmButtonColor: '#2563eb' });
            } else {
                setBadge('fail', '<i class="bi bi-x-circle-fill"></i>Koneksi Gagal');
                Swal.fire({ title: 'Koneksi Gagal', text: (res && res.message) || 'Gagal menghubungi Dapodik.', icon: 'error', confirmButtonColor: '#ef4444' });
            }
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html(orig);
            setBadge('fail', xhr.status === 403 ? '<i class="bi bi-shield-lock"></i>Ditolak' : '<i class="bi bi-exclamation-triangle"></i>Error Server');
            Swal.fire({
                title: 'Tidak dapat menguji koneksi',
                text: xhr.status === 403
                    ? 'Hanya admin yang boleh menguji koneksi Dapodik. Muat ulang halaman bila Anda memang admin.'
                    : 'Gagal menghubungi server aplikasi (HTTP ' + (xhr.status || 0) + ').',
                icon: 'error', confirmButtonColor: '#ef4444'
            });
        });
    });

    // Ringkas hasil sync dari payload server (entitas tunggal maupun sync all)
    function ringkasSync(res) {
        const ringkas = res.summary || {};
        const diproses = Number(res.processed ?? ringkas.total_processed ?? 0);
        const baru     = Number(res.added     ?? ringkas.total_added     ?? 0);
        const ubah     = Number(res.updated   ?? ringkas.total_updated   ?? 0);
        const gagal    = Number(res.gagal     ?? 0);
        const bagian  = [];
        if (diproses > 0) bagian.push(diproses.toLocaleString('id-ID') + ' data dibaca');
        if (baru > 0)     bagian.push('+' + baru.toLocaleString('id-ID') + ' baru');
        if (ubah > 0)     bagian.push(ubah.toLocaleString('id-ID') + ' diperbarui');
        if (gagal > 0)    bagian.push(gagal.toLocaleString('id-ID') + ' gagal diproses');
        return { teks: bagian.join(' · '), gagal: gagal, diproses: diproses };
    }

    // Sync entitas
    $('.btn-sync').on('click', function() {
        const $btn = $(this);
        const entitas = $btn.data('entitas');
        const titleText = entitas === 'all' ? 'Sinkronkan SELURUH Data Dapodik?' : 'Sinkronkan Data ' + String(entitas).toUpperCase() + '?';

        Swal.fire({
            title: titleText,
            text: 'Data terbaru akan ditarik dari Web Service Dapodik lokal.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Jalankan Sync',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span> Memproses...');
            Swal.fire({ title: 'Memproses Sinkronisasi...', text: 'Mohon tunggu, sistem berkomunikasi dengan Dapodik.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const payload = { entitas: entitas };
            payload[CSRF_NAME] = CSRF_HASH;
            $.post(SYNC_URL, payload, function(res) {
                segarkanCsrf(res);
                $btn.prop('disabled', false).html(orig);
                const info = ringkasSync(res || {});
                const pesan = (res && res.message) ? res.message : 'Tidak ada keterangan dari server.';
                if (res && res.status) {
                    // 'status' true saja tidak cukup: sebagian baris bisa gagal,
                    // jadi tampilkan ikon peringatan agar operator tidak mengira
                    // seluruh data tersimpan.
                    const icon = info.gagal > 0 ? 'warning' : 'success';
                    const judul = info.gagal > 0 ? 'Sinkronisasi Selesai, Sebagian Gagal' : 'Sinkronisasi Berhasil!';
                    const html = '<p class="text-sm">' + escHtml(pesan) + '</p>'
                        + (info.teks ? '<p class="text-xs text-slate-500 mt-2">' + escHtml(info.teks) + '</p>' : '');
                    Swal.fire({ title: judul, html: html, icon: icon, confirmButtonColor: '#2563eb' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ title: 'Gagal Sinkronisasi', text: pesan, icon: 'error', confirmButtonColor: '#ef4444' });
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false).html(orig);
                Swal.fire({
                    title: 'Sinkronisasi tidak dijalankan',
                    text: xhr.status === 403
                        ? 'Hanya admin yang boleh menjalankan sinkronisasi Dapodik.'
                        : 'Kesalahan server saat sinkronisasi (HTTP ' + (xhr.status || 0) + ').',
                    icon: 'error', confirmButtonColor: '#ef4444'
                });
            });
        });
    });

    // Bersihkan riwayat log — POST + CSRF, bukan link GET
    $('#btnClearLogs').on('click', function() {
        const $btn = $(this);
        Swal.fire({
            title: 'Bersihkan seluruh riwayat log?',
            text: 'Jejak aktivitas sinkronisasi akan dihapus permanen dan tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Bersihkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3 h-3 border-2 border-current border-t-transparent rounded-full"></span> Membersihkan...');

            const payload = {};
            payload[CSRF_NAME] = CSRF_HASH;
            $.post(CLEAR_URL, payload, function(res) {
                segarkanCsrf(res);
                $btn.prop('disabled', false).html(orig);
                const pesan = (res && res.message) ? res.message : 'Tidak ada keterangan dari server.';
                Swal.fire({ title: res && res.status ? 'Log dibersihkan' : 'Log tidak dibersihkan', text: pesan, icon: res && res.status ? 'success' : 'error', confirmButtonColor: res && res.status ? '#2563eb' : '#ef4444' })
                    .then(() => { if (res && res.status) location.reload(); });
            }).fail(function(xhr) {
                $btn.prop('disabled', false).html(orig);
                Swal.fire({
                    title: 'Log tidak dibersihkan',
                    text: xhr.status === 403 ? 'Hanya admin yang boleh membersihkan riwayat Dapodik.' : 'Kesalahan server (HTTP ' + (xhr.status || 0) + ').',
                    icon: 'error', confirmButtonColor: '#ef4444'
                });
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
