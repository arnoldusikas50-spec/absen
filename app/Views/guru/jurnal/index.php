<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Top app bar custom adjustments */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Month navigation chip */
    .month-nav-chip {
        user-select: none;
        transition: transform 0.12s ease, background-color 0.15s ease;
    }
    .month-nav-chip:active {
        transform: scale(0.985);
    }

    /* Card press animations */
    .mobile-jurnal-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .mobile-jurnal-card:active {
        transform: scale(0.985);
    }

    /* FAB Button */
    .fab-btn {
        position: fixed;
        bottom: calc(82px + var(--safe-area-inset-bottom, 0px));
        right: calc(50% - 220px);
        z-index: 1020;
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s;
    }
    @media (max-width: 480px) {
        .fab-btn {
            right: 16px;
        }
    }
    .fab-btn:active {
        transform: scale(0.92);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];

    $curM = (int)$bulan_pilih;
    $curY = (int)$tahun_pilih;

    // Previous month calculation
    $prevTimestamp = mktime(0, 0, 0, $curM - 1, 1, $curY);
    $prevM = date('m', $prevTimestamp);
    $prevY = date('Y', $prevTimestamp);

    // Next month calculation
    $nextTimestamp = mktime(0, 0, 0, $curM + 1, 1, $curY);
    $nextM = date('m', $nextTimestamp);
    $nextY = date('Y', $nextTimestamp);

    $isCurrentMonth = ($curM === (int) date('m') && $curY === (int) date('Y'));
?>

<!-- â”€â”€ Top App Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('presensi') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="Kembali ke Presensi">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Jurnal Mengajar</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">KBM Mandiri Guru</p>
        </div>
    </div>
    <div class="flex items-center gap-1.5">
        <a href="<?= site_url('guru/jurnal/cetak?bulan=' . $bulan_pilih . '&tahun=' . $tahun_pilih . ($id_kelas_pilih ? '&id_kelas=' . $id_kelas_pilih : '') . ($id_mapel_pilih ? '&id_mapel=' . $id_mapel_pilih : '')) ?>" target="_blank"
           class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition-colors no-underline" title="Cetak Rekap">
            <i class="bi bi-printer"></i>
        </a>
        <a href="<?= site_url('guru/jurnal/create') ?>"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white text-xs font-bold no-underline transition-all shadow-xs active:scale-95">
            <i class="bi bi-plus-lg text-xs"></i>
            <span>Tulis</span>
        </a>
    </div>
</div>

<!-- â”€â”€ Main Content Container â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="px-4 pb-24 space-y-3.5 mt-2.5">

    <!-- Flash Alert Notifications -->
    <?php if (session()->getFlashdata('success')): ?>
    <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center gap-2">
            <i class="bi bi-check-circle-fill text-emerald-500 text-sm"></i>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
            <i class="bi bi-x-lg text-xs"></i>
        </button>
    </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-sm"></i>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1">
            <i class="bi bi-x-lg text-xs"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- â”€â”€ Month Navigation Chip â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="month-nav-chip flex items-center justify-between bg-brand-50/80 border border-brand-100 text-brand-800 rounded-2xl px-3.5 py-2.5 shadow-2xs">
        <a href="<?= site_url('guru/jurnal?bulan=' . $prevM . '&tahun=' . $prevY . ($id_kelas_pilih ? '&id_kelas=' . $id_kelas_pilih : '') . ($id_mapel_pilih ? '&id_mapel=' . $id_mapel_pilih : '') . ($search_pilih ? '&q=' . urlencode($search_pilih) : '')) ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-100 text-brand-600 transition-colors no-underline" title="Bulan sebelumnya">
            <i class="bi bi-chevron-left text-sm font-bold"></i>
        </a>

        <label for="pick-month" class="flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded-lg hover:bg-white/70 transition-colors" title="Ketuk untuk pilih bulan & tahun">
            <i class="bi bi-calendar3 text-brand-600 text-sm"></i>
            <input type="month" id="pick-month" value="<?= $curY . '-' . $bulan_pilih ?>"
                   class="sr-only" tabindex="-1" aria-label="Pilih bulan dan tahun">
            <span class="font-bold text-xs sm:text-sm text-slate-800">
                <?= $months[$bulan_pilih] ?? $bulan_pilih ?> <?= $tahun_pilih ?>
            </span>
        </label>

        <a href="<?= site_url('guru/jurnal?bulan=' . $nextM . '&tahun=' . $nextY . ($id_kelas_pilih ? '&id_kelas=' . $id_kelas_pilih : '') . ($id_mapel_pilih ? '&id_mapel=' . $id_mapel_pilih : '') . ($search_pilih ? '&q=' . urlencode($search_pilih) : '')) ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-100 text-brand-600 transition-colors no-underline" title="Bulan berikutnya">
            <i class="bi bi-chevron-right text-sm font-bold"></i>
        </a>
    </div>

    <!-- â”€â”€ Summary KPI Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <!-- Bulan Ini -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col items-center justify-center text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide"><?= $isCurrentMonth ? 'Bulan Ini' : 'Periode' ?></span>
            <span class="text-xl font-black text-brand-600 leading-tight mt-0.5"><?= $total_jurnal ?></span>
            <span class="text-[10px] font-semibold text-slate-500">Jurnal</span>
        </div>

        <!-- Hari Ini -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col items-center justify-center text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Hari Ini</span>
            <span class="text-xl font-black text-emerald-600 leading-tight mt-0.5"><?= $jurnal_hari_ini ?></span>
            <span class="text-[10px] font-semibold text-slate-500">Terisi</span>
        </div>

        <!-- 7 Hari Terakhir -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col items-center justify-center text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">7 Hari</span>
            <span class="text-xl font-black text-indigo-600 leading-tight mt-0.5"><?= $total_minggu_ini ?></span>
            <span class="text-[10px] font-semibold text-slate-500">Jurnal</span>
        </div>

        <!-- TP / Smt -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col items-center justify-center text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Semester</span>
            <span class="text-xs font-extrabold text-amber-600 leading-tight mt-1 line-clamp-1"><?= esc($smt_active->nama_smt ?? '-') ?></span>
            <span class="text-[10px] font-semibold text-slate-400">TP <?= esc($tp_active->tahun ?? '-') ?></span>
        </div>
    </div>

    <!-- â”€â”€ Jadwal Hari Ini (Status Jurnal per Sesi) â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <?php if (!empty($jadwal_hari_ini)):
        $totalJadwalHariIni = count($jadwal_hari_ini);
        $sisaJadwalHariIni = $totalJadwalHariIni - (int) ($jadwal_hari_ini_terisi ?? 0);
        $menungguValidasiHariIni = count(array_filter($jadwal_hari_ini, static fn(array $i): bool => !empty($i['menunggu'])));
        $bulanSekarang = $months[date('m')] ?? date('M');
    ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="px-3.5 py-2.5 flex items-start justify-between gap-2 border-b border-slate-100 bg-slate-50/60">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[11px] font-extrabold text-slate-700 leading-tight">
                    <i class="bi bi-calendar2-check text-brand-600 text-sm"></i>
                    <span>Jadwal Hari Ini</span>
                </div>
                <div class="text-[9px] font-semibold text-slate-400 mt-0.5"><?= date('d') . ' ' . $bulanSekarang . ' ' . date('Y') ?></div>
            </div>
            <div class="flex items-center gap-1 flex-wrap justify-end">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[10px] font-extrabold whitespace-nowrap <?= $sisaJadwalHariIni > 0 ? 'bg-amber-50 border-amber-100 text-amber-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700' ?>">
                    <i class="bi <?= $sisaJadwalHariIni > 0 ? 'bi-hourglass-split' : 'bi-check2-all' ?> text-[9px]"></i>
                    <?= (int) ($jadwal_hari_ini_terisi ?? 0) ?>/<?= $totalJadwalHariIni ?> terjurnal
                </span>
                <?php if ($menungguValidasiHariIni > 0): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border bg-indigo-50 border-indigo-100 text-indigo-700 text-[10px] font-extrabold whitespace-nowrap"
                      title="Jurnalnya sudah diisi; absen pulang terbuka setelah guru piket menyetujui">
                    <i class="bi bi-shield-check text-[9px]"></i>
                    <?= $menungguValidasiHariIni ?> menunggu validasi
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            <?php foreach ($jadwal_hari_ini as $item):
                $slot = $item['slot'];
                $dari = substr((string) ($slot->dari ?? ''), 0, 5);
                $sampai = substr((string) ($slot->sampai ?? ''), 0, 5);
                $waktu = ($dari !== '' && $sampai !== '') ? $dari . 'â€“' . $sampai : '';
                $urlIsi = site_url('guru/jurnal/create?id_jadwal=' . (int) ($slot->id_jadwal ?? 0)
                    . '&kelas=' . (int) ($slot->id_kelas ?? 0)
                    . '&mapel=' . (int) ($slot->id_mapel ?? 0));
            ?>
            <div class="px-3.5 py-2.5 flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center px-1.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] font-extrabold whitespace-nowrap">
                    Jam <?= esc($slot->jam_ke ?? '-') ?>
                </span>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-extrabold text-slate-800 truncate"><?= esc($slot->nama_mapel ?? 'Mata Pelajaran') ?></span>
                        <?php if ($item['sudah'] && $item['absen_jumlah'] > 0): ?>
                        <span class="px-1 py-0.5 rounded border border-rose-100 bg-rose-50 text-rose-600 text-[9px] font-extrabold whitespace-nowrap">
                            <?= (int) $item['absen_jumlah'] ?> absen
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-[10px] font-semibold text-slate-500 truncate">
                        <?= esc($slot->nama_kelas ?? 'Kelas') ?><?= $waktu !== '' ? ' Â· ' . esc($waktu) : '' ?>
                    </div>
                    <?php if ($item['sudah'] || !empty($item['menunggu'])): ?>
                    <div class="text-[10px] font-medium text-slate-400 truncate flex items-center gap-1 mt-0.5">
                        <i class="bi bi-bookmark-fill text-<?= !empty($item['menunggu']) ? ($item['ditolak'] ? 'rose' : 'amber') : 'emerald' ?>-500 text-[9px] flex-shrink-0"></i>
                        <span class="truncate"><?= esc($item['materi'] !== '' ? $item['materi'] : 'Jurnal terisi') ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($item['sudah']): ?>
                <a href="<?= site_url('guru/jurnal/edit/' . (int) $item['id_jurnal']) ?>"
                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold no-underline whitespace-nowrap transition-colors"
                   title="Buka / edit jurnal sesi ini">
                    <i class="bi bi-check2-circle text-[10px]"></i> Terisi
                </a>
                <?php elseif (!empty($item['menunggu'])): ?>
                <a href="<?= site_url(($item['ditolak'] ? 'guru/jurnal/edit/' : 'guru/jurnal/detail/') . (int) $item['id_jurnal']) ?>"
                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg <?= $item['ditolak'] ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border-amber-200' ?> border text-[10px] font-extrabold no-underline whitespace-nowrap transition-colors"
                   title="<?= $item['ditolak'] ? 'Jurnal ditolak piket — perbaiki lalu kirim ulang' : 'Menunggu validasi guru piket' ?>">
                    <i class="bi <?= $item['ditolak'] ? 'bi-x-circle' : 'bi-hourglass-split' ?> text-[10px]"></i>
                    <?= $item['ditolak'] ? 'Perbaiki' : 'Menunggu validasi' ?>
                </a>
                <?php else: ?>
                <a href="<?= $urlIsi ?>"
                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[10px] font-extrabold no-underline whitespace-nowrap transition-colors"
                   title="Tulis jurnal untuk sesi ini">
                    <i class="bi bi-pencil-square text-[10px]"></i> Isi
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- â”€â”€ Search & Filter Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs space-y-2">
        <form method="GET" action="<?= site_url('guru/jurnal') ?>" class="space-y-2">
            <input type="hidden" name="bulan" value="<?= esc($bulan_pilih) ?>">
            <input type="hidden" name="tahun" value="<?= esc($tahun_pilih) ?>">

            <!-- Search input -->
            <div class="relative">
                <input type="text" name="q" value="<?= esc($search_pilih) ?>" placeholder="Cari materi / mapel..."
                       class="w-full text-xs font-semibold pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-hidden">
                <i class="bi bi-search absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
            </div>

            <!-- Class, Subject & Action Row -->
            <div class="grid grid-cols-2 gap-2">
                <div class="relative min-w-0">
                    <select name="id_kelas" onchange="this.form.submit()" class="w-full text-xs font-semibold pl-2.5 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:outline-hidden appearance-none cursor-pointer truncate">
                        <option value="">Semua Kelas</option>
                        <?php if (!empty($kelases)): foreach ($kelases as $key => $k): 
                            $kId = is_object($k) ? $k->id_kelas : $key;
                            $kNama = is_object($k) ? $k->nama_kelas : $k;
                        ?>
                        <option value="<?= $kId ?>" <?= $id_kelas_pilih == $kId ? 'selected' : '' ?>>
                            <?= esc($kNama) ?>
                        </option>
                        <?php endforeach; endif; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-2 text-slate-400 pointer-events-none text-xs"></i>
                </div>

                <div class="relative min-w-0">
                    <select name="id_mapel" onchange="this.form.submit()" class="w-full text-xs font-semibold pl-2.5 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:outline-hidden appearance-none cursor-pointer truncate">
                        <option value="">Semua Mapel</option>
                        <?php foreach (($mapels ?? []) as $idM => $nmM): ?>
                        <option value="<?= (int) $idM ?>" <?= (int) $id_mapel_pilih === (int) $idM ? 'selected' : '' ?>>
                            <?= esc($nmM) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-2 text-slate-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-2xs active:scale-95">
                    <i class="bi bi-funnel-fill text-xs"></i> Terapkan Filter
                </button>

                <?php if ($search_pilih || $id_kelas_pilih || $id_mapel_pilih): ?>
                <a href="<?= site_url('guru/jurnal?bulan=' . $bulan_pilih . '&tahun=' . $tahun_pilih) ?>"
                   class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-all no-underline shrink-0" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- â”€â”€ Journal List Cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <?php if (empty($jurnals)): ?>
    <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-2xs text-center">
        <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-500 flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="bi bi-journal-x"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-800">Belum Ada Jurnal</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto leading-relaxed">
            Tidak ada catatan jurnal mengajar pada periode atau filter yang dipilih.
        </p>
        <?php $adaFilterJurnal = ($search_pilih !== '' || $id_kelas_pilih || $id_mapel_pilih); ?>
        <?php if ($adaFilterJurnal): ?>
        <a href="<?= site_url('guru/jurnal?bulan=' . $bulan_pilih . '&tahun=' . $tahun_pilih) ?>"
           class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-all shadow-2xs no-underline active:scale-95">
            <i class="bi bi-arrow-counterclockwise"></i> Hapus Filter
        </a>
        <?php else: ?>
        <a href="<?= site_url('guru/jurnal/create') ?>"
           class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs no-underline active:scale-95">
            <i class="bi bi-plus-lg"></i> Tulis Jurnal Baru
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <?php
        $dateCounts = [];
        foreach ($jurnals as $row) {
            $dateCounts[$row->tanggal] = ($dateCounts[$row->tanggal] ?? 0) + 1;
        }
        $lastDate = null;
    ?>
    <div class="space-y-3">
        <?php foreach ($jurnals as $j): ?>
        <?php if ($lastDate !== $j->tanggal): $lastDate = $j->tanggal; ?>
        <div class="flex items-center gap-2 pt-1">
            <span class="text-[11px] font-extrabold text-slate-500">
                <?= buat_tanggal(date('l, d F Y', strtotime($j->tanggal))) ?>
            </span>
            <?php if ($j->tanggal === date('Y-m-d')): ?>
            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[9px] font-extrabold uppercase tracking-wide">Hari ini</span>
            <?php endif; ?>
            <span class="ml-auto text-[10px] font-semibold text-slate-400"><?= (int) ($dateCounts[$j->tanggal] ?? 0) ?> jurnal</span>
        </div>
        <?php endif; ?>
        <div class="mobile-jurnal-card bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex flex-col justify-between cursor-pointer hover:border-brand-200" data-jurnal="<?= (int) $j->id_jurnal ?>">
            <div class="space-y-2.5">
                <!-- Top info badge -->
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-slate-100 rounded-lg text-[11px] font-bold text-slate-700">
                        <i class="bi bi-calendar3 text-brand-600 text-[10px]"></i>
                        <?= date('d/m/Y', strtotime($j->tanggal)) ?>
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 bg-brand-50 text-brand-700 rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                        Jam <?= esc($j->jam_ke) ?>
                    </span>
                </div>

                <!-- Class & Mapel -->
                <div>
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md text-[10px] font-bold mb-1">
                        <i class="bi bi-building text-[9px]"></i> <?= esc($j->nama_kelas ?? 'Kelas') ?>
                    </div>
                    <h3 class="text-sm font-black text-slate-900 leading-snug">
                        <?= esc($j->nama_mapel ?? 'Mata Pelajaran') ?>
                    </h3>
                </div>

                <!-- Materi Pokok -->
                <div class="bg-slate-50 rounded-xl p-2.5 border border-slate-100/80">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-0.5 flex items-center gap-1">
                        <i class="bi bi-bookmark-fill text-brand-500 text-[8px]"></i> Materi Pokok
                    </p>
                    <p class="text-xs font-semibold text-slate-700 line-clamp-2 leading-relaxed">
                        <?= esc($j->materi_pokok) ?>
                    </p>
                </div>

                <!-- Model & Asesmen badges -->
                <div class="flex flex-wrap gap-1">
                    <?php if (!empty($j->model_belajar)): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md text-[10px] font-bold">
                        <i class="bi bi-diagram-3 text-[8px]"></i>
                        <?= esc($j->model_belajar) ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($j->jenis_asesmen)): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md text-[10px] font-bold">
                        <i class="bi bi-clipboard-check text-[8px]"></i>
                        <?= esc($j->jenis_asesmen) ?>
                    </span>
                    <?php endif; ?>

                    <?php 
                    $profilArr = !empty($j->profil_pancasila) ? json_decode($j->profil_pancasila, true) : [];
                    if (!empty($profilArr)): 
                    ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 rounded-md text-[10px] font-bold">
                        <i class="bi bi-star-fill text-amber-500 text-[8px]"></i>
                        <?= count($profilArr) ?> Dimensi
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Siswa Absen Status -->
                <?php if (!empty($j->absen_jumlah)): ?>
                <div class="text-[11px] text-rose-600 bg-rose-50/70 border border-rose-100 rounded-lg px-2.5 py-1 flex items-center gap-1.5 font-semibold"
                     title="<?= esc($j->absen_label ?: ((int) $j->absen_jumlah . ' siswa')) ?>">
                    <i class="bi bi-person-x text-xs shrink-0"></i>
                    <span class="truncate">Absen (<?= (int) $j->absen_jumlah ?>): <?= esc($j->absen_label ?: ((int) $j->absen_jumlah . ' siswa')) ?></span>
                </div>
                <?php else: ?>
                <div class="text-[11px] text-emerald-700 bg-emerald-50/50 border border-emerald-100/60 rounded-lg px-2.5 py-1 flex items-center gap-1.5 font-semibold">
                    <i class="bi bi-check2-circle text-xs shrink-0"></i>
                    <span>Siswa hadir lengkap</span>
                </div>
                <?php endif; ?>

                <?php $vStatus = (string) ($j->validasi_status ?? ''); ?>
                <?php if ($vStatus === 'REJECTED'): ?>
                    <?php $vCatatan = trim((string) ($j->validasi_catatan ?? '')); ?>
                    <div class="text-[11px] text-orange-700 bg-orange-50/80 border border-orange-200 rounded-lg px-2.5 py-1.5 flex items-start gap-1.5 font-semibold">
                        <i class="bi bi-exclamation-octagon-fill text-xs shrink-0 mt-0.5"></i>
                        <span class="leading-snug">Perlu perbaikan dari validasi<?= $vCatatan !== '' ? ': ' . esc($vCatatan) : '. Silakan lengkapi isian jurnalnya.' ?></span>
                    </div>
                <?php elseif ($vStatus === 'APPROVED'): ?>
                    <?php $vPada = trim((string) ($j->validasi_pada ?? '')); ?>
                    <div class="text-[10px] text-emerald-700 font-bold flex items-center gap-1.5">
                        <i class="bi bi-patch-check-fill text-xs text-emerald-500"></i>
                        <span>Jurnal sudah divalidasi<?= $vPada !== '' ? ' (' . date('d/m/Y', strtotime($vPada)) . ')' : '' ?> — tidak bisa dihapus, sunting bila ada koreksi</span>
                    </div>
                <?php elseif (trim((string) ($j->validasi_catatan ?? '')) !== ''): ?>
                    <div class="text-[10px] text-slate-500 font-semibold flex items-start gap-1.5">
                        <i class="bi bi-chat-left-text text-xs shrink-0 mt-0.5"></i>
                        <span class="leading-snug">Catatan validator: <?= esc(trim((string) $j->validasi_catatan)) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer actions -->
            <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between mt-3">
                <button type="button" onclick="showJurnalDetail(<?= (int)$j->id_jurnal ?>)"
                   class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-800 transition-colors cursor-pointer active:scale-95">
                    <span>Lihat Rincian</span>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                </button>

                <div class="flex items-center gap-1.5">
                    <a href="<?= site_url('guru/jurnal/create?copy=' . (int) $j->id_jurnal) ?>"
                       title="Salin jadi jurnal baru"
                       class="w-7 h-7 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs transition-colors no-underline active:scale-95">
                        <i class="bi bi-files"></i>
                    </a>
                    <a href="<?= site_url('guru/jurnal/cetak/' . $j->id_jurnal) ?>" target="_blank"
                       title="Cetak Jurnal"
                       class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition-colors no-underline active:scale-95">
                        <i class="bi bi-printer"></i>
                    </a>
                    <a href="<?= site_url('guru/jurnal/edit/' . $j->id_jurnal) ?>"
                       title="Edit Jurnal"
                       class="w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 flex items-center justify-center text-xs transition-colors no-underline active:scale-95">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php if ($vStatus !== 'APPROVED'): ?>
                    <button type="button"
                       onclick="confirmDeleteJurnal(<?= (int)$j->id_jurnal ?>, <?= esc(json_encode((string) ($j->nama_mapel ?? 'Jurnal'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>)"
                       title="Hapus Jurnal"
                       class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center text-xs transition-colors active:scale-95 cursor-pointer">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- â”€â”€ Floating Action Button (FAB) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<a href="<?= site_url('guru/jurnal/create') ?>"
   class="fab-btn inline-flex items-center justify-center w-12 h-12 bg-gradient-to-tr from-brand-600 via-brand-500 to-brand-400 text-white rounded-full shadow-lg shadow-brand-500/35 no-underline">
    <i class="bi bi-plus-lg text-xl"></i>
</a>

<!-- â”€â”€ Bottom Sheet Modal Detail Jurnal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div id="modal-detail-jurnal" class="tw-modal">
    <div class="tw-modal-box p-0 flex flex-col">
        <!-- Drag indicator & header -->
        <div class="pt-3 pb-2 px-5 flex flex-col items-center border-b border-slate-100 bg-slate-50/50">
            <div class="w-10 h-1 rounded-full bg-slate-300 mb-3"></div>
            <div class="w-full flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-sm">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800 m-0" id="detail-title">Detail Jurnal</h3>
                        <p class="text-[10px] text-slate-400 m-0" id="detail-subtitle">Memuat data...</p>
                    </div>
                </div>
                <button type="button" onclick="closeJurnalDetail()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="p-4 sm:p-5 overflow-y-auto space-y-3.5 text-xs max-h-[70vh]" id="detail-body">
            <div class="py-12 text-center text-slate-400">
                <i class="bi bi-arrow-repeat animate-spin text-2xl mb-2 inline-block"></i>
                <p>Memuat rincian jurnal...</p>
            </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="p-3.5 border-t border-slate-100 flex items-center justify-between bg-slate-50/80" id="detail-footer">
            <button type="button" onclick="closeJurnalDetail()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                Tutup
            </button>
            <div class="flex items-center gap-2" id="detail-actions">
                <!-- Injected via JS -->
            </div>
        </div>
    </div>
</div>

<script>
function escHtml(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// Pemilih bulan & tahun langsung (native input month): ketuk label bulan
// pada chip navigasi, lalu halaman diarahkan dengan filter yang dipertahankan.
(function () {
    const pickMonth = document.getElementById('pick-month');
    if (!pickMonth) return;
    pickMonth.addEventListener('change', function () {
        const v = String(this.value || '');
        const match = v.match(/^(\d{4})-(\d{2})$/);
        if (!match) return;
        const url = new URL(window.location.href);
        url.searchParams.set('tahun', match[1]);
        url.searchParams.set('bulan', match[2]);
        window.location.href = url.toString();
    });
    // Label memicu picker native; tanpa showPicker anggota panah tetap berfungsi.
    document.getElementById('pick-month').parentElement.addEventListener('click', function (e) {
        if (typeof pickMonth.showPicker !== 'function') return;
        e.preventDefault();
        try { pickMonth.showPicker(); } catch (err) { /* pilih lewat fokus manual */ }
    });
})();

function fmtDateTime(v) {
    const m = String(v ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : '';
}

function showJurnalDetail(id) {
    const modal = document.getElementById('modal-detail-jurnal');
    const body = document.getElementById('detail-body');
    const title = document.getElementById('detail-title');
    const subtitle = document.getElementById('detail-subtitle');
    const actions = document.getElementById('detail-actions');

    modal.classList.add('open');

    body.innerHTML = `
        <div class="py-12 text-center text-slate-400">
            <i class="bi bi-arrow-repeat animate-spin text-2xl mb-2 inline-block text-brand-600"></i>
            <p class="font-medium text-xs">Memuat rincian jurnal...</p>
        </div>
    `;

    fetch(`<?= site_url('guru/jurnal/detail') ?>/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.status || !res.data) {
            body.innerHTML = `<div class="p-4 text-center text-rose-600 font-semibold">Gagal memuat detail jurnal.</div>`;
            return;
        }

        const d = res.data;
        const createdLabel = fmtDateTime(d.created_at);
        const updatedLabel = fmtDateTime(d.updated_at);
        title.textContent = d.nama_mapel || 'Jurnal Mengajar';
        subtitle.textContent = `${d.nama_kelas || 'Kelas'} â€¢ Jam Ke-${d.jam_ke || '-'} â€¢ ${d.tanggal || ''}`;

        // Profil pancasila list
        let profilHtml = '';
        if (res.profil_list && res.profil_list.length > 0) {
            profilHtml = `
                <div>
                    <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Dimensi Profil Pancasila</h4>
                    <div class="flex flex-wrap gap-1">
                        ${res.profil_list.map(p => `
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-star-fill text-amber-500 text-[8px]"></i> ${escHtml(p)}
                            </span>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Status validasi oleh guru piket / kepala sekolah.
        const validasiHtml = (() => {
            const st = String(d.validasi_status || '');
            const ca = String(d.validasi_catatan || '').trim();
            const pa = fmtDateTime(d.validasi_pada || '');
            if (st === 'APPROVED') return `
                <div class="flex items-start gap-1.5 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                    <i class="bi bi-patch-check-fill mt-0.5"></i>
                    <span>Isi jurnal sudah divalidasi oleh guru piket / kepala sekolah${pa ? ' pada ' + escHtml(pa) : ''}.</span>
                </div>`;
            if (st === 'REJECTED') return `
                <div class="flex items-start gap-1.5 text-[11px] font-bold text-orange-700 bg-orange-50 border border-orange-200 rounded-xl px-3 py-2">
                    <i class="bi bi-exclamation-octagon-fill mt-0.5 shrink-0"></i>
                    <span class="leading-snug">Perlu perbaikan${ca ? ': ' + escHtml(ca) : ' menurut validasi guru piket / kepala sekolah.'}${pa ? ' (' + escHtml(pa) + ')' : ''} Sunting jurnal ini — setelah tersimpan, isinya kembali masuk antrean validasi.</span>
                </div>`;
            if (st === 'PENDING') return `
                <div class="flex items-start gap-1.5 text-[11px] font-semibold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                    <i class="bi bi-shield-exclamation mt-0.5"></i>
                    <span>Menunggu validasi guru piket / kepala sekolah.</span>
                </div>`;
            return '';
        })();

        body.innerHTML = `
            ${validasiHtml}

            <!-- Materi Pokok -->
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-0.5">Materi Pokok</h4>
                <p class="text-xs sm:text-sm font-bold text-slate-800 leading-relaxed">${escHtml(d.materi_pokok || '-')}</p>
            </div>

            <!-- Tujuan Pembelajaran -->
            ${d.tujuan_pembelajaran ? `
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-0.5">Tujuan Pembelajaran</h4>
                <p class="text-xs font-semibold text-slate-700 leading-relaxed">${escHtml(d.tujuan_pembelajaran)}</p>
            </div>` : ''}

            <!-- Grid Model & Asesmen -->
            <div class="grid grid-cols-2 gap-2">
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Model Belajar</span>
                    <span class="text-xs font-bold text-purple-700 line-clamp-1">${escHtml(d.model_belajar || '-')}</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Bentuk Asesmen</span>
                    <span class="text-xs font-bold text-emerald-700 line-clamp-1">${escHtml(d.jenis_asesmen || '-')}</span>
                </div>
            </div>

            ${profilHtml}

            <!-- Kegiatan Pembelajaran -->
            ${d.kegiatan_belajar ? `
            <div>
                <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Alur Kegiatan Belajar</h4>
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-slate-700 leading-relaxed whitespace-pre-line text-xs">
                    ${escHtml(d.kegiatan_belajar)}
                </div>
            </div>` : ''}

            <!-- Refleksi & Tindak Lanjut -->
            ${d.refleksi || d.tindak_lanjut ? `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                ${d.refleksi ? `
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Refleksi Guru</span>
                    <p class="text-slate-700 leading-relaxed whitespace-pre-line text-xs mt-0.5">${escHtml(d.refleksi)}</p>
                </div>` : ''}
                ${d.tindak_lanjut ? `
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Tindak Lanjut</span>
                    <p class="text-slate-700 leading-relaxed whitespace-pre-line text-xs mt-0.5">${escHtml(d.tindak_lanjut)}</p>
                </div>` : ''}
            </div>` : ''}

            ${d.catatan_khusus ? `
            <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl">
                <span class="text-[9px] font-bold uppercase text-amber-700/70 block">Catatan Khusus</span>
                <p class="text-amber-900 leading-relaxed whitespace-pre-line text-xs mt-0.5">${escHtml(d.catatan_khusus)}</p>
            </div>` : ''}

            <!-- Kehadiran Siswa & Media -->
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                <div>
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Siswa Tidak Hadir</span>
                    <span class="font-bold ${res.absen_jumlah > 0 ? 'text-rose-600' : 'text-slate-700'}">
                        ${escHtml(res.absen_jumlah > 0 ? `Tidak Hadir (${res.absen_jumlah}): ${res.absen_label || (res.absen_jumlah + ' siswa')}` : (d.siswa_absent || 'Nihil / Hadir Semua'))}
                    </span>
                </div>
                <div>
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Media & Alat</span>
                    <span class="font-bold text-slate-700 line-clamp-1">${escHtml(d.media_alat || '-')}</span>
                </div>
            </div>

            ${(createdLabel || updatedLabel) ? `
            <p class="text-[10px] text-slate-400 text-center m-0 pt-1">
                ${createdLabel ? 'Dicatat ' + createdLabel : ''}${updatedLabel && updatedLabel !== createdLabel ? ' Â· Diperbarui ' + updatedLabel : ''}
            </p>` : ''}
        `;

        actions.innerHTML = `
            <a href="<?= site_url('guru/jurnal/create') ?>?copy=${encodeURIComponent(d.id_jurnal)}"
               class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1 no-underline">
                <i class="bi bi-files"></i> Salin
            </a>
            <a href="<?= site_url('guru/jurnal/cetak') ?>/${d.id_jurnal}" target="_blank"
               class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1 no-underline">
                <i class="bi bi-printer"></i> Cetak
            </a>
            <a href="<?= site_url('guru/jurnal/edit') ?>/${d.id_jurnal}"
               class="px-3.5 py-1.5 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1 no-underline">
                <i class="bi bi-pencil"></i> Edit
            </a>
        `;
    })
    .catch(err => {
        body.innerHTML = `<div class="p-4 text-center text-rose-600 font-semibold">Terjadi kesalahan saat memuat data.</div>`;
    });
}

function closeJurnalDetail() {
    const modal = document.getElementById('modal-detail-jurnal');
    modal.classList.remove('open');
}

// Close on backdrop click
document.getElementById('modal-detail-jurnal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeJurnalDetail();
    }
});

// Ketuk kartu riwayat untuk membuka rincian (kecuali ketukan pada tombol/tautan).
document.querySelectorAll('.mobile-jurnal-card[data-jurnal]').forEach(function (card) {
    card.addEventListener('click', function (e) {
        if (e.target.closest('a,button')) return;
        const id = parseInt(card.getAttribute('data-jurnal'), 10) || 0;
        if (id > 0) showJurnalDetail(id);
    });
});

function postDeleteJurnal(id) {
    // Hapus via POST (CSRF) â€” GET sudah dihapus dari routes agar tidak
    // bisa dipicu lewat link/img.
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `<?= site_url('guru/jurnal/delete') ?>/${id}`;
    form.innerHTML = `<?= csrf_field() ?>`
        + `<input type="hidden" name="bulan" value="<?= esc($bulan_pilih) ?>">`
        + `<input type="hidden" name="tahun" value="<?= esc($tahun_pilih) ?>">`
        + `<input type="hidden" name="id_kelas" value="<?= (int) $id_kelas_pilih ?>">`
        + `<input type="hidden" name="q" value="<?= esc($search_pilih) ?>">`;
    document.body.appendChild(form);
    form.submit();
}
function confirmDeleteJurnal(id, mapel) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Jurnal?',
            text: `Hapus catatan jurnal ${mapel}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-3xl max-w-xs',
                confirmButton: 'rounded-xl text-xs font-bold px-3 py-2',
                cancelButton: 'rounded-xl text-xs font-bold px-3 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                postDeleteJurnal(id);
            }
        });
    } else {
        if (confirm(`Hapus jurnal untuk ${mapel}?`)) {
            postDeleteJurnal(id);
        }
    }
}
</script>
<?= $this->endSection() ?>
