<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$skor = (int) ($skor ?? 100);
$skorCls = $skor >= 90 ? 'badge-success' : ($skor >= 75 ? 'badge-warning' : 'badge-danger');
$namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$typeBadge = ['PELANGGARAN' => 'badge-danger', 'PENGHARGAAN' => 'badge-success', 'KOREKSI' => 'badge-warning', 'PRESENSI' => 'badge-info'];
?>
<div class="fade-in-up p-4 md:p-6 space-y-4 w-full max-w-none">
    <nav class="flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold" aria-label="Breadcrumb">
        <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 no-underline">Dashboard</a>
        <span>/</span>
        <a href="<?= site_url('admin/poin-siswa') ?>" class="hover:text-blue-600 no-underline">Poin Disiplin Siswa</a>
        <span>/</span>
        <span class="text-slate-600">Detail</span>
    </nav>

    <!-- Profil + skor -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-5 md:p-6 flex flex-col md:flex-row md:items-center gap-5">
        <div class="flex items-center gap-4 min-w-0 flex-1">
            <span class="w-16 h-16 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl font-extrabold shrink-0">
                <?= esc(mb_substr($siswa->nama ?? '?', 0, 1)) ?>
            </span>
            <div class="min-w-0">
                <h1 class="text-xl font-extrabold text-slate-900 truncate"><?= esc($siswa->nama ?? '-') ?></h1>
                <p class="text-xs text-slate-500 font-mono mt-0.5">NIS <?= esc($siswa->nis ?? '-') ?> · NISN <?= esc($siswa->nisn ?? '-') ?></p>
                <p class="text-[11px] text-slate-400 mt-1">Tahun <?= (int) $tahun ?> · TP <?= esc($tp_active->tahun ?? '-') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <div class="text-center">
                <div class="text-[11px] font-bold uppercase text-slate-400">Skor Disiplin</div>
                <span class="badge-tw <?= $skorCls ?> !text-2xl !px-5 !py-2 font-extrabold"><?= $skor ?></span>
                <div class="text-[11px] text-slate-400 mt-1">dari 100</div>
            </div>
            <div class="flex flex-col gap-2">
                <a href="<?= site_url('admin/poin-siswa') ?>" class="btn-tw btn-light btn-tw-sm"><i class="bi bi-arrow-left mr-1"></i> Kembali</a>
                <form method="get" class="flex items-center gap-1">
                    <input type="number" name="tahun" value="<?= (int) $tahun ?>" min="2020" max="<?= date('Y') + 1 ?>" class="form-control-tw !w-20 !py-1 text-xs text-center">
                    <button class="btn-tw btn-tw-sm btn-light" title="Tampilkan tahun"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start">
        <!-- Grafik + ringkasan -->
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-5">
                <h2 class="text-sm font-extrabold text-slate-900 mb-1">Pergerakan Poin <?= (int) $tahun ?></h2>
                <p class="text-[11px] text-slate-400 mb-3">Total poin tercatat per bulan (negatif = pelanggaran dominan)</p>
                <div style="height:260px"><canvas id="monthlyChart"></canvas></div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-5">
                <h2 class="text-sm font-extrabold text-slate-900 mb-3">Ringkasan per Jenis</h2>
                <?php if (empty($type_summary)): ?>
                    <p class="text-xs text-slate-400">Belum ada catatan tahun ini.</p>
                <?php else: ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($type_summary as $tipe => $s): ?>
                            <span class="badge-tw <?= $typeBadge[$tipe] ?? 'badge-secondary' ?> !text-xs !px-3 !py-1.5">
                                <?= esc($tipe) ?>: <?= $s['poin'] > 0 ? '+' : '' ?><?= (int) $s['poin'] ?> (<?= (int) $s['count'] ?>×)
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-5">
                <h2 class="text-sm font-extrabold text-slate-900 mb-3">Riwayat Transaksi</h2>
                <?php if (empty($riwayat)): ?>
                    <p class="text-xs text-slate-400 text-center py-8">Belum ada riwayat.</p>
                <?php else: ?>
                    <div class="space-y-2 max-h-[560px] overflow-y-auto">
                        <?php foreach ($riwayat as $h): $p = (int) ($h->poin ?? 0); ?>
                            <div class="flex items-center gap-2.5 rounded-xl border border-slate-100 px-3 py-2">
                                <span class="badge-tw <?= $p >= 0 ? 'badge-success' : 'badge-danger' ?> font-mono"><?= $p > 0 ? '+' : '' ?><?= $p ?></span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-bold text-slate-700 truncate"><?= esc($h->kategori_nama ?? $h->tipe) ?></div>
                                    <div class="text-[11px] text-slate-400 truncate"><?= esc($h->keterangan ?? '') ?></div>
                                    <div class="text-[10px] text-slate-300 font-mono"><?= esc($h->created_at ?? '') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    const ctx = document.getElementById('monthlyChart');
    if (ctx && window.Chart) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_values(array_slice($namaBulan, 1))) ?>,
                datasets: [{
                    label: 'Poin',
                    data: <?= json_encode(array_values($monthly ?? [])) ?>,
                    backgroundColor: (<?= json_encode(array_values($monthly ?? [])) ?>).map(v => v >= 0 ? 'rgba(16,185,129,.6)' : 'rgba(239,68,68,.6)'),
                    borderRadius: 6
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false } } }
        });
    }
});
</script>
<?= $this->endSection() ?>
