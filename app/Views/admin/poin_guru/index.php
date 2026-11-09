<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    :root {
        --rank-1: #FFD700;
        --rank-2: #C0C0C0;
        --rank-3: #CD7F32;
        --premium-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .hero-card {
        background: var(--premium-gradient);
        color: white;
        border: none;
        border-radius: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(118, 75, 162, 0.3);
        margin-bottom: 2rem;
        overflow: hidden;
        position: relative;
    }

    .hero-card::after {
        content: "";
        position: absolute;
        top: -20%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        z-index: 0;
    }

    .stat-card {
        border: none;
        border-radius: 1rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .ranking-table {
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .ranking-table tr {
        background: white;
        transition: all 0.2s ease;
        border-radius: 10px;
    }

    .ranking-table tr:hover {
        transform: scale(1.01);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        z-index: 10;
        position: relative;
    }

    .ranking-table td {
        border: none;
        padding: 1.2rem 1rem;
        vertical-align: middle;
    }

    .ranking-table td:first-child { border-radius: 10px 0 0 10px; }
    .ranking-table td:last-child { border-radius: 0 10px 10px 0; }

    .rank-badge {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 800;
        font-size: 0.9rem;
    }

    .rank-1 { background: var(--rank-1); color: #856404; box-shadow: 0 0 10px rgba(255,215,0,0.5); }
    .rank-2 { background: var(--rank-2); color: #383d41; }
    .rank-3 { background: var(--rank-3); color: #fff; }
    .rank-normal { background: #f8f9fa; color: #6c757d; }

    .teacher-avatar {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        object-fit: cover;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    .initials-avatar {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #495057;
        text-transform: uppercase;
    }

    .score-badge {
        font-size: 1.1rem;
        font-weight: 800;
        padding: 0.5rem 1rem;
        border-radius: 50rem;
    }

    .btn-recalc {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        backdrop-filter: blur(5px);
        transition: all 0.3s;
    }

    .btn-recalc:hover {
        background: white;
        color: #764ba2;
        transform: translateY(-2px);
    }
</style>

<div class="content-area">
    <!-- Hero Section -->
    <div class="hero-card rounded-2xl overflow-hidden mb-6">
        <div class="card-tw-body" style="z-index: 1;">
            <div class="flex flex-wrap gap-4">
                <div class="">
                    <h1 class="display-5 font-bold mb-2">Disiplin Guru <?= $currentYear ?></h1>
                    <p class="lead opacity-75 mb-0">Sistem Pemeringkatan Disiplin dan Dedikasi Otomatis Berdasarkan Data Kehadiran.</p>
                </div>
                <div class="">
                    <a href="<?= site_url('admin/poinguru/recalculate') ?>" 
                       class="btn-tw btn-tw-lg btn-recalc px-6" 
                       onclick="return confirm('Proses ini akan menghitung ulang semua poin dari data absensi tahun ini berdasarkan konfigurasi terbaru. Lanjutkan?')">
                        <i class="bi bi-arrow-repeat mr-2"></i> Update Data Poin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert-tw alert-info">
            <i class="bi bi-check-circle-fill mr-3 text-2xl"></i>
            <div><?= session()->getFlashdata('message') ?></div>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <?php 
        $avg_point = !empty($teachers) ? array_sum(array_column($teachers, 'poin_tahunan')) / count($teachers) : 0;
        $top_teacher = !empty($teachers) ? $teachers[0] : null;
    ?>
    <div class="flex flex-wrap gap-4">
        <div class="">
            <div class="stat-card shadow-sm h-full">
                <div class="card-tw-body">
                    <div class="flex items-center">
                        <div class="bg-blue-50 p-3 rounded-lg mr-3">
                            <i class="bi bi-people text-3xl text-blue-600"></i>
                        </div>
                        <div>
                            <h6 class="text-slate-400 text-sm mb-1">Total Guru Terdata</h6>
                            <h3 class="font-bold mb-0"><?= count($teachers) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="">
            <div class="stat-card shadow-sm h-full border-l-4 border-emerald-500">
                <div class="card-tw-body">
                    <div class="flex items-center">
                        <div class="bg-emerald-50 p-3 rounded-lg mr-3">
                            <i class="bi bi-trophy text-3xl text-emerald-600"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="text-slate-400 text-sm mb-1">Peringkat 1</h6>
                            <h5 class="font-bold mb-0 truncate"><?= $top_teacher ? esc($top_teacher->nama_guru) : '-' ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="">
            <div class="stat-card shadow-sm h-full">
                <div class="card-tw-body">
                    <div class="flex items-center">
                        <div class="bg-cyan-50 p-3 rounded-lg mr-3">
                            <i class="bi bi-graph-up text-3xl text-cyan-600"></i>
                        </div>
                        <div>
                            <h6 class="text-slate-400 text-sm mb-1">Rata-rata Poin</h6>
                            <h3 class="font-bold mb-0"><?= number_format($avg_point, 1) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="">
            <div class="stat-card shadow-sm h-full border-l-4 border-amber-400">
                <div class="card-tw-body">
                    <div class="flex items-center">
                        <div class="bg-amber-50 p-3 rounded-lg mr-3">
                            <i class="bi bi-coin text-3xl text-amber-500"></i>
                        </div>
                        <div>
                            <h6 class="text-slate-400 text-sm mb-1">Total Poin <?= $currentYear ?></h6>
                            <h3 class="font-bold mb-0"><?= number_format($annualTotalPoin) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ranking Table Card -->
    <div class="card-tw"><div class="card-tw-header">
                <h5 class="font-bold mb-0"><i class="bi bi-list-stars mr-2 text-blue-600"></i>Daftar Peringkat Guru</h5>
                <span class="badge-tw badge-secondary">
                    <i class="bi bi-calendar-event mr-1"></i> Data Tahun <?= $currentYear ?>
                </span>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive-tw"><table class="table-tw" id="table-points">
                    <thead>
                        <tr class="text-slate-400 text-sm">
                            <th class="border-0">RANK</th>
                            <th class="border-0">GURU</th>
                            <th class="border-0">IDENTITAS</th>
                            <th class="border-0 text-right">POIN DISIPLIN</th>
                            <th class="border-0 text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $i => $guru): ?>
                            <?php 
                                $rankClass = '';
                                if ($i == 0) $rankClass = 'rank-1';
                                elseif ($i == 1) $rankClass = 'rank-2';
                                elseif ($i == 2) $rankClass = 'rank-3';
                                else $rankClass = 'rank-normal';
                                
                                $photo = $guru->foto ?: $guru->user_foto;
                                $initials = substr($guru->nama_guru, 0, 1) . (strpos($guru->nama_guru, ' ') ? substr($guru->nama_guru, strpos($guru->nama_guru, ' ') + 1, 1) : '');
                                $fotoUrl = '';
                                if ($photo) {
                                    if (strpos($photo, 'uploads/') === false) $photo = 'uploads/profile_guru/' . $photo;
                                    if (file_exists(FCPATH . $photo)) $fotoUrl = base_url($photo);
                                }
                            ?>
                            <tr>
                                <td width="80">
                                    <div class="rank-badge <?= $rankClass ?>">
                                        <?= $i + 1 ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <?php if ($fotoUrl): ?>
                                            <img src="<?= $fotoUrl ?>" class="teacher-avatar mr-3" alt="Avatar">
                                        <?php else: ?>
                                            <div class="initials-avatar mr-3"><?= $initials ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="font-bold mb-0 uppercase"><?= esc($guru->nama_guru) ?></h6>
                                            <span class="text-slate-400 text-sm uppercase">Guru Tetap</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm font-bold text-slate-400"><?= esc($guru->nip ?: '-') ?></div>
                                    <div class="extra-text-sm text-slate-400 opacity-75">NIP / ID User</div>
                                </td>
                                <td class="text-right">
                                    <span class="score-badge <?= $guru->poin_tahunan < 0 ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-600' ?>">
                                        <?= $guru->poin_tahunan > 0 ? '+' : '' ?><?= number_format($guru->poin_tahunan, 1) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= site_url('admin/poinguru/detail/' . $guru->id_guru) ?>" class="btn-tw btn-tw-sm btn-light">
                                        <i class="bi bi-eye text-blue-600 mr-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formula Reference (text-sm Footer) -->
    <div class="mt-6 p-4 bg-white rounded-xl shadow-sm border-0">
        <h6 class="font-bold mb-6 flex items-center text-slate-400 text-sm">
            <i class="bi bi-info-circle mr-2"></i> SISTEM POIN TERINTEGRASI — POIN HISTORY
        </h6>
        <div class="grid grid-cols-1 gap-4">
            <div class="">
                <div class="flex items-start">
                    <i class="bi bi-check-circle text-emerald-600 mr-2 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800">Sumber Data</div>
                        <div>Semua poin bersumber dari <code>poin_history</code>, diisi real-time saat check-in/out dan cron.</div>
                    </div>
                </div>
            </div>
            <div class="">
                <div class="flex items-start">
                    <i class="bi bi-arrow-repeat text-blue-600 mr-2 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800">Jenis Poin</div>
                        <div>HADIR (+progresif), TELAT (-5), PENALTI (-2), REWARD (+3), ALPA (-10), BONUS (skor disiplin), REDEEM, CLAIMED, REFUND</div>
                    </div>
                </div>
            </div>
            <div class="">
                <div class="flex items-start">
                    <i class="bi bi-database text-slate-500 mr-2 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800">Sinkronisasi</div>
                        <div>Tombol "Update Data Poin" menjalankan seed ulang + menyetel <code>poin_tahunan</code> dari total <code>poin_history</code> tahun berjalan.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#table-points').DataTable({
                "ordering": false, // Keep the ranking provided by the controller
                "pageLength": 25,
                "language": {
                    "search": "Cari Guru:",
                    "lengthMenu": "Tampilkan _MENU_ entri",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ guru"
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>

