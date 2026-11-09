<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    .profile-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
    }
    .profile-img {
        width: 120px;
        height: 120px;
        border-radius: 24px;
        border: 4px solid rgba(255,255,255,0.2);
        object-fit: cover;
    }
    .card-glass {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .table-detail tr {
        transition: all 0.2s;
    }
    .table-detail tr:hover {
        background: rgba(118, 75, 162, 0.05);
    }
    .point-tag {
        font-weight: 800;
        font-size: 0.9rem;
        padding: 5px 12px;
        border-radius: 10px;
    }
    .history-card {
        border-radius: 20px;
        overflow: hidden;
    }
</style>

<div class="content-area">
    <!-- Back Button -->
    <a href="<?= site_url('admin/poinguru') ?>" class="btn-tw btn-outline-secondary btn-sm mb-6 rounded-full px-4">
        <i class="bi bi-arrow-left mr-1"></i> Kembali ke Daftar Peringkat
    </a>

    <!-- Header / Profile -->
    <div class="card-tw">
        <div class="card-tw-body">
            <div class="flex flex-wrap gap-4">
                <div class="text-center">
                    <?php 
                        $photo = $teacher->foto ?: $teacher->user_foto;
                        $initials = substr($teacher->nama_guru, 0, 1) . (strpos($teacher->nama_guru, ' ') ? substr($teacher->nama_guru, strpos($teacher->nama_guru, ' ') + 1, 1) : '');
                        $fotoUrl = '';
                        if ($photo) {
                            if (strpos($photo, 'uploads/') === false) $photo = 'uploads/profile_guru/' . $photo;
                            if (file_exists(FCPATH . $photo)) $fotoUrl = base_url($photo);
                        }
                    ?>
                    <?php if ($fotoUrl): ?>
                         <img src="<?= $fotoUrl ?>" class="profile-img shadow" alt="Profile">
                    <?php else: ?>
                         <div class="bg-white text-blue-600 flex items-center justify-center font-bold text-4xl shadow" style="width:120px; height:120px; border-radius:24px;">
                             <?= $initials ?>
                         </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 pl-0 md:pl-4">
                    <span class="badge-tw badge-secondary">PROFIL GURU</span>
                    <h1 class="font-bold mb-1 uppercase"><?= esc($teacher->nama_guru) ?></h1>
                    <p class="mb-0 opacity-75 text-xl">NIP: <?= esc($teacher->nip ?: '-') ?></p>
                    <div class="mt-6">
                        <div class="grid grid-cols-1 gap-2">
                             <div class="">
                                <div class="bg-white bg-opacity-20 rounded-lg px-4 py-3">
                                    <span class="text-sm block opacity-75"">Total Poin <?= $currentYear ?></span>
                                    <?php 
                                        $totalPoin = 0;
                                        foreach ($poinHistory as $row) $totalPoin += (float)$row->poin;
                                    ?>
                                    <span class="text-2xl font-bold"><?= number_format($totalPoin, 1) ?></span>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-4">
        <!-- Chart Section -->
        <div class="">
            <div class="card-tw"><div class="card-tw-header"><h5 class="font-bold mb-0">Trend Poin Bulanan</h5>
                </div>
                <div class="card-tw-body">
                    <div style="height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Type Summary -->
        <div class="">
            <div class="card-tw"><div class="card-tw-header"><h5 class="font-bold mb-0">Ringkasan per Tipe</h5>
                </div>
                <div class="card-tw-body">
                    <?php if (!empty($typeSummary)): ?>
                        <?php foreach ($typeSummary as $tipe => $info): 
                            $isPos = $info['poin'] >= 0;
                            $tipeUpper = strtoupper($tipe);
                            if ($tipeUpper === 'HADIR' || $tipeUpper === 'REWARD' || $tipeUpper === 'BONUS') $tipeClass = 'success';
                            elseif ($tipeUpper === 'TELAT' || $tipeUpper === 'PENALTI' || $tipeUpper === 'ALPA') $tipeClass = 'danger';
                            elseif ($tipeUpper === 'REDEEM' || $tipeUpper === 'CLAIMED') $tipeClass = 'warning';
                            else $tipeClass = 'secondary';
                        ?>
                            <div class="flex justify-between items-center mb-2 pb-2 border-b border-slate-100">
                                <div>
                                    <span class="badge-tw badge-<?= $tipeClass ?>"><?= $tipe ?></span>
                                    <span class="text-sm text-slate-400 ml-2""><?= $info['count'] ?>x</span>
                                </div>
                                <span class="font-bold <?= $isPos ? 'text-emerald-600' : 'text-red-500' ?>">
                                    <?= $isPos ? '+' : '' ?><?= number_format($info['poin'], 1) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-slate-400 text-sm mb-0">Belum ada data poin.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed History -->
        <div class="w-full">
            <div class="card-tw"><div class="card-tw-header"><div class="flex items-center">
                    <div class="flex justify-between items-center">
                        <h5 class="font-bold mb-0">Riwayat Poin History</h5>
                        <span class="badge-tw badge-secondary">
                            <i class="bi bi-calendar-event mr-1"></i> <?= $currentYear ?>
                        </span>
                    </div>
                </div>
                <div class="p-0">
                    <div class="table-responsive-tw"><table class="table-tw">
                            <thead class="bg-slate-50 text-slate-400 text-sm">
                                <tr>
                                    <th class="px-6 py-4">TANGGAL</th>
                                    <th>TIPE</th>
                                    <th>KETERANGAN</th>
                                    <th class="text-right px-6">POIN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($poinHistory as $row): ?>
                                    <?php
                                        $isPos = (float)$row->poin >= 0;
                                        $tipeUpper = strtoupper($row->tipe);
                                        if ($tipeUpper === 'HADIR' || $tipeUpper === 'REWARD' || $tipeUpper === 'BONUS') $tipeClass = 'success';
                                        elseif ($tipeUpper === 'TELAT' || $tipeUpper === 'PENALTI' || $tipeUpper === 'ALPA') $tipeClass = 'danger';
                                        elseif ($tipeUpper === 'REDEEM' || $tipeUpper === 'CLAIMED') $tipeClass = 'warning';
                                        else $tipeClass = 'secondary';
                                    ?>
                                    <tr>
                                        <td class="px-6">
                                            <div class="font-bold text-slate-800"><?= date('d M Y', strtotime($row->created_at)) ?></div>
                                            <div class="text-sm text-slate-400"><?= date('H:i', strtotime($row->created_at)) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-full bg-<?= $tipeClass ?> opacity-10 text-<?= $tipeClass ?> px-2 py-1 text-sm">
                                                <?= esc($row->tipe) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-sm"><?= esc($row->keterangan) ?></div>
                                        </td>
                                        <td class="text-right px-6">
                                            <span class="point-tag <?= $isPos ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' ?>">
                                                <?= $isPos ? '+' : '' ?><?= number_format((float)$row->poin, 1) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($poinHistory)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-12 text-slate-400">Belum ada riwayat poin untuk tahun <?= $currentYear ?>.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formula Reference -->
    <div class="mt-6 p-4 bg-white rounded-xl shadow-sm border-0">
        <h6 class="font-bold mb-6 flex items-center text-slate-400 text-sm">
            <i class="bi bi-info-circle mr-2"></i> SISTEM POIN TERINTEGRASI
        </h6>
        <div class="grid grid-cols-1 gap-4">
            <div class="">
                <div class="p-3 bg-slate-50 rounded-lg h-full">
                    <div class="font-bold text-slate-800 mb-1">HADIR / TELAT</div>
                    <ul class="mb-0 pl-4">
                        <li>ONTIME: poin progresif (+10, +12, +15, +20)
                        <li>LATE: -5 tetap
                        <li>ALPA: -10 tetap
                    </ul>
                </div>
            </div>
            <div class="">
                <div class="p-3 bg-slate-50 rounded-lg h-full">
                    <div class="font-bold text-slate-800 mb-1">CHECK-OUT</div>
                    <ul class="mb-0 pl-4">
                        <li>EARLY: PENALTI progresif (-2, -3, -5)
                        <li>After hours: REWARD progresif (+1, +2, +3)
                    </ul>
                </div>
            </div>
            <div class="">
                <div class="p-3 bg-slate-50 rounded-lg h-full">
                    <div class="font-bold text-slate-800 mb-1">BONUS + REDEEM</div>
                    <ul class="mb-0 pl-4">
                        <li>BONUS: skor disiplin bulanan (1 skor = 1 poin)
                        <li>REDEEM: pengurangan saat tukar voucher
                        <li>CLAIMED: poin voucher dikembalikan
                    </ul>
                </div>
            </div>
            <div class="">
                <div class="p-3 bg-slate-50 rounded-lg h-full">
                    <div class="font-bold text-slate-800 mb-1">Sinkronisasi</div>
                    <ul class="mb-0 pl-4">
                        <li>Real-time saat check-in/out
                        <li>Cron markAlpha untuk ALPA
                        <li>Seed ulang via tombol Update
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('monthlyChart');
    
    // Monthly labels in order
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const stats = <?= json_encode($monthly_stats) ?>;
    
    const data = months.map(m => stats[m] ? stats[m].points : 0);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Total Poin',
                data: data,
                borderColor: '#764ba2',
                backgroundColor: 'rgba(118, 75, 162, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#764ba2',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>

<?= $this->endSection() ?>
