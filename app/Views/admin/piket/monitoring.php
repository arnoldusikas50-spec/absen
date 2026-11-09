<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<style>
@media print {
    .no-print { display: none !important; }
    .fade-in-up { animation: none !important; }
    body { background: #fff !important; }
}
</style>
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ── Header (pola kartu/dapodik/izin) ── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-sky-50 text-sky-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-clipboard-data"></i> Laporan Harian Piket
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul) ?></h1>
            <p class="text-xs text-slate-500 mt-1">Laporan harian aktivitas piket.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                <i class="bi bi-printer"></i> Cetak
            </button>
            <a href="<?= site_url('admin/piket') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali ke Jadwal
            </a>
        </div>
    </div>

    <!-- ── Filter ── -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden no-print">
        <div class="flex items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                <i class="bi bi-funnel text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Filter Tanggal</h2>
        </div>
        <form method="get" class="p-5 flex flex-col md:flex-row items-stretch md:items-end gap-3">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="filter-tanggal">
                    <i class="bi bi-calendar3 mr-1 text-slate-400"></i>Tanggal
                </label>
                <input type="date" name="tanggal" id="filter-tanggal"
                       class="form-control-tw" value="<?= esc($tanggal) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="btn-tw btn-primary">
                <i class="bi bi-search mr-1"></i> Lihat Data
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rekap Absen -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex items-center gap-3 p-5 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-person-x-fill"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-extrabold text-slate-900">Absensi Siswa</h2>
                    <p class="text-[11px] text-slate-400">Rekap ketidakhadiran per kelas • <?= esc($tanggal) ?></p>
                </div>
            </div>
            <div>
                <?php if (empty($rekap_absen)): ?>
                    <div class="text-center py-14 text-slate-400">
                        <?php if (!empty($sudah_direkap)): ?>
                            <div class="bg-emerald-50 rounded-full inline-flex p-5 mb-3">
                                <i class="bi bi-check-circle-fill text-emerald-500 text-3xl"></i>
                            </div>
                            <h3 class="font-bold text-slate-500">Nihil</h3>
                            <p class="text-xs mt-1">Semua siswa hadir.</p>
                        <?php else: ?>
                            <div class="bg-slate-100 rounded-full inline-flex p-5 mb-3">
                                <i class="bi bi-clipboard-x text-slate-400 text-3xl"></i>
                            </div>
                            <h3 class="font-bold text-slate-500">Belum Direkap</h3>
                            <p class="text-xs mt-1">Piket belum menginput absensi tanggal ini.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table-tw">
                            <thead>
                                <tr>
                                    <th class="text-left">Kelas</th>
                                    <th class="text-right">Jumlah Ketidakhadiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rekap_absen as $r): ?>
                                    <tr>
                                        <td class="font-semibold text-slate-800">
                                            <?= esc($r->nama_kelas) ?>
                                        </td>
                                        <td class="text-right">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-100">
                                                <?= (int) $r->total_absen ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tamu & Kejadian -->
        <div class="space-y-6">
            <!-- Kejadian -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="flex items-center gap-3 p-5 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold text-slate-900">Kejadian / Pelanggaran</h2>
                        <p class="text-[11px] text-slate-400">Catatan kejadian khusus</p>
                    </div>
                    <?php if (!empty($kejadian)): ?>
                    <span class="ml-auto px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100 text-[11px] font-bold"><?= count($kejadian) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (empty($kejadian)): ?>
                        <div class="text-center py-14 text-slate-400">
                            <div class="bg-emerald-50 rounded-full inline-flex p-5 mb-3">
                                <i class="bi bi-shield-check text-emerald-500 text-3xl"></i>
                            </div>
                            <h3 class="font-bold text-slate-500">Aman</h3>
                            <p class="text-xs mt-1">Tidak ada kejadian khusus.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table-tw">
                                <thead>
                                    <tr>
                                        <th style="width: 10%">Jam</th>
                                        <th style="width: 20%">Jenis</th>
                                        <th style="width: 25%">Siswa</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kejadian as $k): ?>
                                        <tr>
                                            <td class="font-mono text-xs"><?= esc(substr($k->jam ?? '', 0, 5) ?: '—') ?></td>
                                            <td>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                    <?= esc($k->jenis ?? '-') ?>
                                                </span>
                                            </td>
                                            <td class="font-semibold text-slate-800 uppercase"><?= esc($k->nama_siswa ?? '—') ?></td>
                                            <td class="text-slate-500"><?= esc($k->keterangan ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tamu -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="flex items-center gap-3 p-5 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold text-slate-900">Buku Tamu</h2>
                        <p class="text-[11px] text-slate-400">Catatan kunjungan tamu</p>
                    </div>
                    <?php if (!empty($tamu)): ?>
                    <span class="ml-auto px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-100 text-[11px] font-bold"><?= count($tamu) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (empty($tamu)): ?>
                        <div class="text-center py-14 text-slate-400">
                            <div class="bg-slate-100 rounded-full inline-flex p-5 mb-3">
                                <i class="bi bi-person-badge text-slate-500 text-3xl"></i>
                            </div>
                            <h3 class="font-bold text-slate-500">Belum Ada Tamu</h3>
                            <p class="text-xs mt-1">Belum ada tamu yang tercatat hari ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table-tw">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Nama Tamu</th>
                                        <th>Instansi</th>
                                        <th>Bertemu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tamu as $t): ?>
                                        <tr>
                                            <td class="font-mono text-xs whitespace-nowrap">
                                                <i class="bi bi-clock mr-1 text-slate-300"></i><?= esc(substr($t->jam_masuk ?? '', 0, 5) ?: '—') ?>
                                            </td>
                                            <td class="font-semibold text-slate-800"><?= esc($t->nama ?? '—') ?></td>
                                            <td class="text-slate-500"><?= esc($t->instansi ?? '—') ?></td>
                                            <td>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                                    <?= esc($t->bertemu ?? '—') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Jurnal KBM & Kehadiran Guru ── -->
    <?php
        $guruMasuk = [];
        $guruTerlambat = [];
        $guruBelum = [];
        foreach (($guru_absensi ?? []) as $ga) {
            if (!empty($ga->jam_masuk)) {
                $guruMasuk[] = $ga;
                if (!empty($ga->status_masuk) && strtolower($ga->status_masuk) === 'late') {
                    $guruTerlambat[] = $ga;
                }
            } else {
                $guruBelum[] = $ga;
            }
        }
    ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Jurnal KBM -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex items-center gap-3 p-5 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-extrabold text-slate-900">Jurnal KBM</h2>
                    <p class="text-[11px] text-slate-400">Aktivitas mengajar • <?= esc($tanggal) ?></p>
                </div>
                <?php if (!empty($jurnal)): ?>
                <span class="ml-auto px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100 text-[11px] font-bold"><?= count($jurnal) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <?php if (empty($jurnal)): ?>
                    <div class="text-center py-14 text-slate-400">
                        <div class="bg-slate-100 rounded-full inline-flex p-5 mb-3">
                            <i class="bi bi-journal-x text-slate-400 text-3xl"></i>
                        </div>
                        <h3 class="font-bold text-slate-500">Belum Ada Jurnal</h3>
                        <p class="text-xs mt-1">Belum ada aktivitas KBM yang dicatat tanggal ini.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="table-tw">
                            <thead>
                                <tr>
                                    <th class="text-left">Jam</th>
                                    <th class="text-left">Guru</th>
                                    <th class="text-left">Kelas &amp; Mapel</th>
                                    <th class="text-center">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jurnal as $j): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                                <?= esc($j->jam_ke ?? '—') ?>
                                            </span>
                                        </td>
                                        <td class="font-semibold text-slate-800 uppercase"><?= esc($j->nama_guru ?? '—') ?></td>
                                        <td>
                                            <div class="font-semibold text-slate-700 uppercase"><?= esc($j->nama_kelas ?? '—') ?></div>
                                            <div class="text-[11px] text-slate-400 uppercase"><?= esc($j->nama_mapel ?? '—') ?></div>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= site_url('admin/jurnal/detail/' . (int) $j->id_jurnal) ?>" target="_blank"
                                               class="inline-flex items-center justify-center w-7 h-7 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-xs transition-colors no-underline"
                                               title="Baca detail jurnal">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kehadiran Guru -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex items-center gap-3 p-5 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-extrabold text-slate-900">Kehadiran Guru</h2>
                    <p class="text-[11px] text-slate-400">Check-in/out • <?= esc($tanggal) ?></p>
                </div>
            </div>
            <div class="px-5 pt-4 flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                    Hadir <?= count($guruMasuk) - count($guruTerlambat) ?>
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                    Terlambat <?= count($guruTerlambat) ?>
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                    Belum <?= count($guruBelum) ?>
                </span>
            </div>
            <div class="mt-3">
                <?php if (empty($guru_absensi)): ?>
                    <div class="text-center py-14 text-slate-400">
                        <div class="bg-slate-100 rounded-full inline-flex p-5 mb-3">
                            <i class="bi bi-person-x text-slate-400 text-3xl"></i>
                        </div>
                        <h3 class="font-bold text-slate-500">Belum Ada Data</h3>
                        <p class="text-xs mt-1">Belum ada data kehadiran guru tanggal ini.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="table-tw">
                            <thead>
                                <tr>
                                    <th class="text-left">Nama</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guru_absensi as $g):
                                    $isLate = !empty($g->status_masuk) && strtolower($g->status_masuk) === 'late';
                                    if (!empty($g->jam_masuk) && !$isLate) {
                                        $bCls = 'bg-emerald-50 text-emerald-700 border-emerald-100'; $bTxt = 'Hadir';
                                    } elseif (!empty($g->jam_masuk)) {
                                        $bCls = 'bg-amber-50 text-amber-700 border-amber-100'; $bTxt = 'Terlambat';
                                    } else {
                                        $bCls = 'bg-rose-50 text-rose-700 border-rose-100'; $bTxt = 'Belum';
                                    }
                                    $jamMasukFmt = '—';
                                    $jamPulangFmt = '—';
                                    try {
                                        if (!empty($g->jam_masuk)) $jamMasukFmt = date('H:i', strtotime((string) $g->jam_masuk));
                                        if (!empty($g->jam_pulang)) $jamPulangFmt = date('H:i', strtotime((string) $g->jam_pulang));
                                    } catch (\Throwable $e) {
                                    }
                                ?>
                                    <tr>
                                        <td class="font-semibold text-slate-800 uppercase"><?= esc($g->nama_guru ?? '—') ?></td>
                                        <td class="text-center font-mono text-xs"><?= esc($jamMasukFmt) ?></td>
                                        <td class="text-center font-mono text-xs"><?= esc($jamPulangFmt) ?></td>
                                        <td class="text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border <?= $bCls ?>">
                                                <?= $bTxt ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Selisih Piket (patokan) vs Presensi Formal ── -->
    <div class="bg-white rounded-3xl border border-amber-200 shadow-xs overflow-hidden">
        <div class="flex items-center gap-3 p-5 border-b border-slate-100">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-sm font-extrabold text-slate-900">Selisih Piket vs Presensi Formal</h2>
                <p class="text-[11px] text-slate-400">Catatan piket bertentangan dengan sistem • <?= esc($tanggal) ?></p>
            </div>
            <?php if (!empty($divergensi ?? [])): ?>
            <span class="ml-auto px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100 text-[11px] font-bold"><?= count($divergensi) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <?php if (empty($divergensi ?? [])): ?>
                <div class="text-center py-14 text-slate-400">
                    <div class="bg-emerald-50 rounded-full inline-flex p-5 mb-3">
                        <i class="bi bi-check-circle-fill text-emerald-500 text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-slate-500">Selaras</h3>
                    <p class="text-xs mt-1">Tidak ada pertentangan piket vs sistem.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="table-tw">
                        <thead>
                            <tr>
                                <th class="text-left">Siswa</th>
                                <th class="text-left">Kelas</th>
                                <th>Piket</th>
                                <th>Sistem</th>
                                <th class="text-left">Keterangan</th>
                                <th class="text-right no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($divergensi as $d): ?>
                                <tr>
                                    <td class="font-semibold text-slate-800"><?= esc($d->nama ?? '-') ?></td>
                                    <td class="text-slate-500"><?= esc($d->nama_kelas ?? '-') ?></td>
                                    <td class="text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600"><?= esc($d->piket ?? '?') ?></span></td>
                                    <td class="text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100"><?= esc(($d->formal ?? '—') . (!empty($d->jam_masuk) ? ' ' . substr($d->jam_masuk, 0, 5) : '')) ?></span></td>
                                    <td class="text-amber-700 text-xs"><?= esc($d->ket ?? '') ?></td>
                                    <td class="text-right no-print">
                                        <button type="button" onclick="selaraskanDivergensiAdmin(this, <?= (int) ($d->id_siswa ?? 0) ?>)"
                                                class="px-2.5 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800 text-[11px] font-bold border border-amber-200 transition-colors">
                                            Selaraskan
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
async function selaraskanDivergensiAdmin(btn, idSiswa) {
    if (!idSiswa) return;
    btn.disabled = true;
    const label = btn.textContent;
    btn.textContent = '…';
    const fd = new FormData();
    fd.append('tanggal', '<?= esc($tanggal) ?>');
    fd.append('id_siswa', idSiswa);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    try {
        const res = await fetch('<?= site_url('admin/piket/selaras-divergensi') ?>', {
            method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json().catch(() => null);
        if (res.ok && json && json.status) {
            const row = btn.closest('tr');
            if (row) { row.style.opacity = '0.45'; btn.textContent = 'Selaras'; }
            window.location.reload();
        } else {
            btn.disabled = false;
            btn.textContent = label;
            alert((json && json.message) || 'Gagal menyelaraskan');
        }
    } catch (e) {
        btn.disabled = false;
        btn.textContent = label;
        alert('Gagal menghubungkan ke server');
    }
}
</script>
<?= $this->endSection(); ?>
