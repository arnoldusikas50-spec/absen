<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }
    .quick-action-item {
        transition: all 0.2s ease;
    }
    .quick-action-item:hover {
        transform: translateX(4px);
    }
    .ranking-row {
        transition: background-color 0.2s ease;
    }
    .ranking-row:hover {
        background-color: #f8fafc;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up">
    <!-- Page Header -->
    <div class="content-header fade-in-up">
        <div class="page-header">
            <div class="min-w-0">
                <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs shrink-0">
                        <i class="bi bi-bar-chart-line-fill text-xl"></i>
                    </span>
                    <span>Dashboard Presensi</span>
                </h3>
                <p class="mt-1 text-xs text-slate-400 ml-12">Monitoring dan analisis kehadiran guru</p>
            </div>
            <nav class="breadcrumb-tw">
                <a href="<?= base_url('admin') ?>" class="no-underline text-slate-500 hover:text-blue-600">Admin</a>
                <span class="sep">/</span>
                <span class="active font-medium text-slate-800">Dashboard Presensi</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-area">
        <div class="w-full">
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-100 rounded-xl p-3">
                            <i class="bi bi-people-fill text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold tracking-wide mb-1">Total Guru</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $summary['total_guru'] ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="bg-emerald-100 rounded-xl p-3">
                            <i class="bi bi-check-circle-fill text-emerald-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold tracking-wide mb-1">Hadir Hari Ini</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $summary['hadir_hari_ini'] ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="bg-amber-100 rounded-xl p-3">
                            <i class="bi bi-clock-fill text-amber-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold tracking-wide mb-1">Telat Bulan Ini</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $summary['telat_bulan_ini'] ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center gap-4">
                        <div class="bg-red-100 rounded-xl p-3">
                            <i class="bi bi-exclamation-triangle-fill text-red-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold tracking-wide mb-1">Pending Review</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $summary['pending_dinas'] + $summary['pending_koreksi'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Late Trend Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-100 rounded-xl p-2">
                                <i class="bi bi-graph-up text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Trend Keterlambatan</h3>
                                <p class="text-sm text-slate-500">12 Bulan Terakhir</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <canvas id="lateTrendChart" style="min-height:280px;height:280px;max-height:280px;width:100%;"></canvas>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-amber-100 rounded-xl p-2">
                                <i class="bi bi-lightning-charge-fill text-amber-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Menu Cepat</h3>
                                <p class="text-sm text-slate-500">Akses cepat fitur</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-2">
                        <?php
                        $quickLinks = [
                            ['url' => 'scanner', 'icon' => 'bi-display', 'color' => 'emerald', 'label' => 'Scanner QR Siswa', 'badge' => null],
                            ['url' => 'admin/presensi/manual',  'icon' => 'bi-person-check-fill', 'color' => 'blue',   'label' => 'Absen Manual',     'badge' => null],
                            ['url' => 'admin/presensi/dinas',   'icon' => 'bi-briefcase-fill',    'color' => 'cyan',   'label' => 'Verifikasi Dinas', 'badge' => $summary['pending_dinas'] > 0 ? $summary['pending_dinas'] : null],
                            ['url' => 'admin/presensi/koreksi', 'icon' => 'bi-pencil-square',     'color' => 'amber',  'label' => 'Koreksi Manual',   'badge' => $summary['pending_koreksi'] > 0 ? $summary['pending_koreksi'] : null],
                            ['url' => 'admin/presensi/rekap',   'icon' => 'bi-file-earmark-spreadsheet-fill', 'color' => 'emerald', 'label' => 'Rekap Bulanan', 'badge' => null],
                            ['url' => 'admin/presensi/setting', 'icon' => 'bi-gear-fill',         'color' => 'slate',  'label' => 'Pengaturan Absen', 'badge' => null],
                        ];
                        $colorMap = [
                            'blue'=>'bg-blue-50 text-blue-600 hover:bg-blue-100',
                            'cyan'=>'bg-cyan-50 text-cyan-600 hover:bg-cyan-100',
                            'amber'=>'bg-amber-50 text-amber-600 hover:bg-amber-100',
                            'emerald'=>'bg-emerald-50 text-emerald-600 hover:bg-emerald-100',
                            'slate'=>'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ];
                        foreach ($quickLinks as $ql):
                        ?>
                        <a href="<?= site_url($ql['url']) ?>"
                           class="quick-action-item flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/40 transition-all group text-slate-700 no-underline">
                            <div class="flex items-center gap-3">
                                <span class="<?= $colorMap[$ql['color']] ?> w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                                    <i class="bi <?= $ql['icon'] ?> text-lg"></i>
                                </span>
                                <span class="text-sm font-semibold"><?= $ql['label'] ?></span>
                            </div>
                            <?php if ($ql['badge']): ?>
                                <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full"><?= $ql['badge'] ?></span>
                            <?php else: ?>
                                <i class="bi bi-chevron-right text-slate-300 text-sm group-hover:text-blue-400 transition-colors"></i>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Attendance Comparison -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-cyan-100 rounded-xl p-2">
                                <i class="bi bi-bar-chart-fill text-cyan-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Komparasi Kehadiran</h3>
                                <p class="text-sm text-slate-500">Perbandingan status kehadiran</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <canvas id="comparisonChart" style="min-height:280px;height:280px;max-height:280px;width:100%;"></canvas>
                    </div>
                </div>

                <!-- Discipline Ranking -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-amber-100 rounded-xl p-2">
                                <i class="bi bi-trophy-fill text-amber-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-800">Ranking Kedisiplinan</h3>
                                <p class="text-sm text-slate-500">Top 20 Guru Terbaik</p>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-auto" style="max-height:320px;">
                        <table class="w-full">
                            <thead class="bg-slate-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Nama Guru</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Hadir</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Tepat</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Telat</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Skor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php $rank = 1; foreach ($ranking as $r): ?>
                                <tr class="ranking-row">
                                    <td class="px-4 py-3">
                                        <?php if ($rank==1): ?><i class="bi bi-trophy-fill text-amber-400 text-xl"></i>
                                        <?php elseif ($rank==2): ?><i class="bi bi-trophy-fill text-slate-400 text-xl"></i>
                                        <?php elseif ($rank==3): ?><i class="bi bi-trophy-fill text-orange-400 text-xl"></i>
                                        <?php else: ?><span class="text-slate-400 font-bold text-lg"><?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-700 max-w-[150px] truncate"><?= esc($r->first_name . ' ' . $r->last_name) ?></td>
                                    <td class="px-4 py-3 text-center text-slate-600"><?= $r->total_hadir ?></td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-600"><?= $r->ontime ?></td>
                                    <td class="px-4 py-3 text-center font-bold text-red-500"><?= $r->late ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                            <?= $r->score>=80 ? 'bg-emerald-100 text-emerald-700' : ($r->score>=50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                                            <?= number_format($r->score,1) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php $rank++; endforeach; ?>
                                <?php if (empty($ranking)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-slate-400 py-12">
                                        <i class="bi bi-inbox text-4xl block mb-3 opacity-50"></i>
                                        <p class="text-sm">Belum ada data</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Shared Options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13 },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" } } },
                y: { border: { dash: [4, 4] }, grid: { color: '#f3f4f6' }, ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" }, maxTicksLimit: 5 } }
            }
        };

        // Late Trend Chart
        const lateTrendData = <?= json_encode($lateTrend) ?>;
        const lateTrendCtx = document.getElementById('lateTrendChart').getContext('2d');
        new Chart(lateTrendCtx, {
            type: 'line',
            data: {
                labels: lateTrendData.map(d => d.month),
                datasets: [
                    {
                        label: 'Jumlah Terlambat',
                        data: lateTrendData.map(d => d.total_late),
                        borderColor: '#f59e0b',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                            gradient.addColorStop(0, 'rgba(245, 158, 11, 0.2)');
                            gradient.addColorStop(1, 'rgba(245, 158, 11, 0)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: { ...commonOptions.scales.y, beginAtZero: true }
                }
            }
        });

        // Comparison Chart
        const comparisonData = <?= json_encode($comparison) ?>;
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: comparisonData.slice(0, 10).map(d => d.first_name),
                datasets: [
                    { label: 'Tepat Waktu', data: comparisonData.slice(0, 10).map(d => parseInt(d.total_tepat)), backgroundColor: '#10b981', borderRadius: 6 },
                    { label: 'Terlambat', data: comparisonData.slice(0, 10).map(d => parseInt(d.total_telat)), backgroundColor: '#f59e0b', borderRadius: 6 },
                    { label: 'Dinas', data: comparisonData.slice(0, 10).map(d => parseInt(d.total_dinas)), backgroundColor: '#3b82f6', borderRadius: 6 }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    x: { ...commonOptions.scales.x, stacked: true },
                    y: { ...commonOptions.scales.y, stacked: true, beginAtZero: true }
                }
            }
        });
    });
</script>

<?= $this->endSection() ?>