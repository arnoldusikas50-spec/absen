<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $bulanPilih = (string) $bulan;
    $queryBase = [
        'bulan' => $bulanPilih,
        'tahun' => $tahun,
    ];
    if (!empty($q)) $queryBase['q'] = $q;
    if (!empty($hanya_belum)) $queryBase['hanya_belum'] = '1';

    $exportUrl = site_url('admin/jurnal/rekap/export') . '?' . http_build_query($queryBase);
    $printUrl  = site_url('admin/jurnal/rekap/cetak') . '?' . http_build_query($queryBase);

    $belum = max(0, (int) $total_guru - (int) $guru_mengisi);
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-file-earmark-check"></i> Rekap Jurnal Mengajar
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Rekapitulasi Keterisian Jurnal Guru</h1>
            <p class="text-xs text-slate-500 mt-1">Akumulasi jurnal, hari aktif, dan pengisian terakhir setiap guru per periode</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= esc($exportUrl) ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-filetype-csv"></i> Ekspor CSV
            </a>
            <a href="<?= esc($printUrl) ?>" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-printer"></i> Cetak
            </a>
            <a href="<?= site_url('admin/jurnal') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Monitoring Harian
            </a>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">Total Guru</p>
            <p class="text-2xl font-extrabold text-slate-900 leading-tight m-0"><?= (int) $total_guru ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200/70 shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-500 m-0">Sudah Mengisi</p>
            <p class="text-2xl font-extrabold text-emerald-700 leading-tight m-0"><?= (int) $guru_mengisi ?></p>
        </div>
        <div class="bg-white rounded-2xl border <?= $belum > 0 ? 'border-amber-200/80' : 'border-slate-200/80' ?> shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">Belum Mengisi</p>
            <p class="text-2xl font-extrabold <?= $belum > 0 ? 'text-amber-700' : 'text-slate-400' ?> leading-tight m-0"><?= $belum ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">Total Jurnal</p>
            <p class="text-2xl font-extrabold text-indigo-700 leading-tight m-0"><?= (int) $total_jurnal ?></p>
        </div>
    </div>

    <?php
        $persenTotal = (int) $total_scheduled > 0
            ? min(100, (int) round((int) $total_jurnal / (int) $total_scheduled * 100))
            : null;
    ?>
    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-[11px] font-semibold text-indigo-900">
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar2-week"></i> Sesi terjadwal: <b><?= (int) $total_scheduled ?></b></span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-journal-check"></i> Sudah dijurnal: <b><?= (int) $total_jurnal ?></b></span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-graph-up-arrow"></i> Kepatuhan: <b><?= $persenTotal === null ? '—' : $persenTotal . '%' ?></b></span>
        <span class="text-indigo-700/70 font-medium">Sesi terjadwal dihitung hingga hari ini.</span>
    </div>

    <?php if (!empty($ada_validasi)): ?>
    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-[11px] font-semibold text-slate-700">
        <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Status validasi</span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-shield-check text-emerald-600"></i> Disetujui: <b><?= (int) ($total_disetujui ?? 0) ?></b></span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-hourglass-split text-indigo-600"></i> Menunggu: <b><?= (int) ($total_menunggu ?? 0) ?></b></span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-x-circle text-rose-600"></i> Ditolak: <b><?= (int) ($total_ditolak ?? 0) ?></b></span>
        <?php if (!empty($mode_validasi_ketat)): ?>
        <span class="text-indigo-700 font-bold">Mode ketat aktif: hanya jurnal Disetujui yang melepaskan guru dari absen pulang.</span>
        <?php else: ?>
        <span class="text-slate-400 font-medium">Mode ketat mati — jurnal terisi sudah cukup untuk absen pulang.</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Filter Bulan & Tahun -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <form method="GET" action="<?= site_url('admin/jurnal/rekap') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Bulan</label>
                <select name="bulan" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <?php foreach ($months as $k => $m): ?>
                    <option value="<?= $k ?>" <?= $bulanPilih === (string) $k ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Tahun</label>
                <select name="tahun" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= (int) $tahun === (int) $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Cari Guru</label>
                <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Nama atau NIP..."
                       class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <label class="inline-flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 cursor-pointer select-none">
                <input type="checkbox" name="hanya_belum" value="1" <?= !empty($hanya_belum) ? 'checked' : '' ?>
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                Hanya belum mengisi
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>
                <a href="<?= site_url('admin/jurnal/rekap') ?>" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors no-underline" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <h3 class="text-sm font-bold text-slate-800 m-0">Daftar Guru</h3>
            </div>
            <span class="text-xs font-semibold text-slate-500">
                <?= esc($months[$bulanPilih] ?? $bulanPilih) ?> <?= (int) $tahun ?>
                &middot; <?= count($gurus) ?> baris ditampilkan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
                        <th class="px-5 py-3.5 text-center" width="50">No</th>
                        <th class="px-5 py-3.5">Nama Guru</th>
                        <th class="px-5 py-3.5" width="150">NIP</th>
                        <th class="px-5 py-3.5 text-center" width="120">Sesi Terjadwal</th>
                        <th class="px-5 py-3.5 text-center" width="110">Total Jurnal</th>
                        <?php if (!empty($ada_validasi)): ?>
                        <th class="px-5 py-3.5 text-center" width="150">Status Validasi</th>
                        <?php endif; ?>
                        <th class="px-5 py-3.5 text-center" width="110">Kepatuhan</th>
                        <th class="px-5 py-3.5 text-center" width="100">Hari Aktif</th>
                        <th class="px-5 py-3.5 text-center" width="140">Terakhir Mengisi</th>
                        <th class="px-5 py-3.5 text-center" width="90">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($gurus)): ?>
                    <tr>
                        <td colspan="<?= !empty($ada_validasi) ? 10 : 9 ?>" class="py-10 text-center text-slate-400">
                            <?= !empty($hanya_belum) ? 'Semua guru sudah mengisi jurnal pada periode ini.' : 'Tidak ada data guru yang cocok dengan filter.' ?>
                        </td>
                    </tr>
                    <?php else: $no = 1; foreach ($gurus as $g):
                        $stat = $stat_map[(int) $g->id_guru] ?? null;
                        $tot = $stat ? (int) $stat->total_jurnal : 0;
                        $hariAktif = $stat ? (int) $stat->hari_aktif : 0;
                        $terjadwal = (int) ($scheduled_map[(int) $g->id_guru] ?? 0);
                        $kepatuhan = $terjadwal > 0 ? min(100, (int) round($tot / $terjadwal * 100)) : null;
                        $kepatuhanClass = $kepatuhan === null
                            ? 'bg-slate-100 text-slate-400'
                            : ($kepatuhan >= 80 ? 'bg-emerald-100 text-emerald-800' : ($kepatuhan >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700'));
                        $last = $stat->last_tanggal ?? '';
                        $lastLabel = $last !== ''
                            ? ($last === date('Y-m-d') ? 'Hari ini' : date('d/m/Y', strtotime($last)))
                            : '-';
                    ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-3 text-center text-slate-400"><?= $no++ ?></td>
                        <td class="px-5 py-3">
                            <p class="font-bold text-slate-800 m-0"><?= esc($g->nama_guru) ?></p>
                        </td>
                        <td class="px-5 py-3 text-slate-500"><?= esc($g->nip ?: '-') ?></td>
                        <td class="px-5 py-3 text-center font-bold <?= $terjadwal > 0 ? 'text-slate-700' : 'text-slate-300' ?>">
                            <?= $terjadwal > 0 ? $terjadwal . ' sesi' : '—' ?>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <?php if ($tot > 0): ?>
                            <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full font-black text-xs">
                                <?= $tot ?> Sesi
                            </span>
                            <?php else: ?>
                            <span class="inline-block px-3 py-1 bg-slate-100 text-slate-400 rounded-full font-bold text-xs">
                                0
                            </span>
                            <?php endif; ?>
                        </td>
                        <?php if (!empty($ada_validasi)): ?>
                        <?php
                            $disetujui = $stat ? (int) ($stat->jumlah_disetujui ?? 0) : 0;
                            $ditolak   = $stat ? (int) ($stat->jumlah_ditolak ?? 0) : 0;
                            $menungguStat = max(0, $tot - $disetujui - $ditolak);
                        ?>
                        <td class="px-5 py-3 text-center whitespace-nowrap">
                            <?php if ($tot > 0): ?>
                            <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-black text-[11px]" title="Disetujui"><?= $disetujui ?></span>
                            <span class="inline-block px-2 py-0.5 <?= $menungguStat > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-400' ?> rounded-full font-black text-[11px]" title="Menunggu validasi"><?= $menungguStat ?></span>
                            <span class="inline-block px-2 py-0.5 <?= $ditolak > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-400' ?> rounded-full font-black text-[11px]" title="Ditolak, perlu diperbaiki"><?= $ditolak ?></span>
                            <?php else: ?>
                            <span class="text-slate-300">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-block px-2.5 py-1 <?= $kepatuhanClass ?> rounded-full font-black text-xs">
                                <?= $kepatuhan === null ? '—' : $kepatuhan . '%' ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center font-bold <?= $hariAktif > 0 ? 'text-slate-700' : 'text-slate-300' ?>">
                            <?= $hariAktif ?> hari
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-[11px] font-bold <?= $tot > 0 ? 'text-slate-600' : 'text-slate-300' ?>">
                                <?= esc($lastLabel) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="<?= site_url('admin/jurnal?id_guru=' . (int) $g->id_guru . '&bulan=' . (int) $bulan . '&tahun=' . (int) $tahun) ?>"
                               class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 no-underline">
                                Rincian <i class="bi bi-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-[10px] text-slate-400 text-center m-0 flex items-center justify-center gap-1">
        <i class="bi bi-info-circle"></i>
        Rekap dihitung dari jurnal pada tahun pelajaran dan semester aktif. Hari aktif = jumlah tanggal berbeda yang memiliki jurnal. Sesi terjadwal = slot jadwal pada hari Senin–Sabtu dikali jumlah hari tersebut hingga hari ini; kepatuhan = jurnal &divide; sesi terjadwal.
    </p>

</div>
<?= $this->endSection() ?>
