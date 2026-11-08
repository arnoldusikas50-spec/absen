<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    .presence-hero {
        position: relative; z-index: 10; color: #fff;
        padding: calc(16px + var(--safe-area-inset-top,0px)) 20px 60px;
        border-radius: 0 0 36px 36px;
        background: linear-gradient(135deg,#10b981 0%,#059669 50%,#047857 100%);
        box-shadow: 0 8px 24px rgba(16,185,129,.25);
    }
    .hero-title { font-weight: 800; letter-spacing: -.5px; font-size: 22px; margin-bottom: 2px; }
    .hero-subtitle { font-weight: 500; opacity: .9; font-size: 13px; }
    .month-filter-card {
        position: relative; z-index: 20;
        display: flex; align-items: center;
        border-radius: 20px;
        background: rgba(255,255,255,.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,.4);
        box-shadow: 0 8px 24px rgba(0,0,0,.07);
        margin: -40px 20px 24px; padding: 6px;
    }
    .month-select {
        border: none; background: transparent;
        font-weight: 700; color: #064e3b;
        flex: 1; outline: none; padding: 8px 16px 8px 8px; font-size: 14px;
    }
    .btn-download {
        display: flex; align-items: center; justify-content: center;
        background: #ecfdf5; color: #059669;
        border: none; border-radius: 14px;
        width: 40px; height: 40px; margin-right: 4px; cursor: pointer;
    }
    .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 20px 24px; }
    .stat-card  {
        display: flex; flex-direction: column; justify-content: space-between;
        background: #fff; border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 12px rgba(15,23,42,.05);
        padding: 16px; height: 100px;
    }
    .stat-icon  {
        display: flex; align-items: center; justify-content: center;
        border-radius: 10px; width: 36px; height: 36px;
        font-size: 1.125rem; margin-bottom: 8px;
    }
    .stat-value { font-weight: 800; color: #1e293b; font-size: 22px; line-height: 1.25; }
    .stat-label { font-weight: 700; text-transform: uppercase; color: #64748b; font-size: 11px; letter-spacing: .5px; }
    .timeline-container { padding: 0 20px 100px; }
    .timeline-item { display: flex; gap: 16px; margin-bottom: 20px; position: relative; }
    .timeline-item::before {
        content: ''; position: absolute;
        left: 24px; top: 48px; bottom: -24px;
        width: 2px; background: #e2e8f0; border-radius: 2px; z-index: 0;
    }
    .timeline-item:last-child::before { display: none; }
    .date-column { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; z-index: 1; width: 48px; }
    .date-circle {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        font-weight: 800; color: #334155;
        background: #fff; border: 2px solid #e2e8f0;
        border-radius: 14px; box-shadow: 0 2px 8px rgba(15,23,42,.06);
        width: 48px; height: 48px; line-height: 1;
        font-size: 15px;
    }
    .date-month { font-weight: 600; text-transform: uppercase; margin-top: 2px; font-size: 10px; color: #64748b; }
    .attendance-card {
        flex: 1; position: relative; overflow: hidden;
        background: #fff; border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 12px rgba(15,23,42,.05);
        padding: 16px;
    }
    .attendance-card::after {
        content: ''; position: absolute;
        top: 0; left: 0; bottom: 0; width: 4px;
        background: #cbd5e1; border-radius: 2px 0 0 2px;
    }
    .card-status-hadir::after  { background: #10b981; }
    .card-status-telat::after  { background: #f59e0b; }
    .card-status-izin::after   { background: var(--primary-500); }
    .card-status-alpha::after  { background: #ef4444; }
    .time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9; }
    .time-item  { display: flex; flex-direction: column; }
    .time-label { font-weight: 700; text-transform: uppercase; color: #94a3b8; font-size: 10px; }
    .time-value { font-weight: 700; color: #334155; font-size: 14px; }
    .status-badge { display: inline-flex; align-items: center; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-radius: 8px; padding: 4px 8px; font-size: 11px; margin-bottom: 4px; }
    .bg-hadir { background: #dcfce7; color: #15803d; }
    .bg-telat  { background: #fef3c7; color: #b45309; }
    .bg-izin   { background: var(--primary-100); color: var(--primary-700); }
    .bg-alpha  { background: #fee2e2; color: #b91c1c; }
    .empty-state { text-align: center; color: #94a3b8; padding: 60px 20px; }
    .empty-icon  { display: block; font-size: 3rem; margin: 0 auto 16px; opacity: .5; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero -->
<div class="presence-hero">
    <div class="flex items-center mb-3">
        <a href="<?= site_url('dashboard') ?>" class="text-white opacity-75 me-3"><i class="bi bi-arrow-left text-2xl"></i></a>
        <div>
            <div class="hero-title">Absensi Saya</div>
            <div class="hero-subtitle"><?= esc($guru->nama_guru) ?></div>
        </div>
        <div class="ms-auto">
             <div class="bg-white bg-opacity-25 rounded-full p-2">
                 <img src="<?= base_url($guru->foto ? $guru->foto : 'assets/img/guru.png') ?>" class="rounded-full" width="32" height="32" style="object-fit: cover;">
             </div>
        </div>
    </div>
</div>

<!-- Filter & Export -->
<div class="month-filter-card">
    <i class="bi bi-calendar-month text-emerald-600 ms-3 text-xl"></i>
    <form id="filterForm" action="" method="get" class="grow">
        <select name="bulan" class="month-select" onchange="this.form.submit()">
            <?php foreach ($bulan_ini as $k => $v): ?>
                <option value="<?= $k ?>" <?= $bulan == $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="tahun" value="<?= $tahun ?>">
    </form>
    <a href="<?= site_url('guru/absensi/export?bulan='.$bulan.'&tahun='.$tahun) ?>" class="btn-download shadow-sm">
        <i class="bi bi-cloud-download-fill text-xl"></i>
    </a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-emerald-50 text-emerald-600">
            <i class="bi bi-calendar-check"></i>
        </div>
        <div>
            <div class="stat-value"><?= $statistik['hadir'] ?></div>
            <div class="stat-label">Kehadiran</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber-50 text-amber-500">
            <i class="bi bi-alarm"></i>
        </div>
        <div>
            <div class="stat-value"><?= $statistik['hadir'] > 0 ? (isset($statistik['telat']) ? $statistik['telat'] : '0') : '0' ?></div>
            <div class="stat-label">Terlambat</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-blue-50 text-brand-600">
            <i class="bi bi-envelope-paper"></i>
        </div>
        <div>
            <div class="stat-value"><?= $statistik['izin'] + $statistik['sakit'] ?></div>
            <div class="stat-label">Izin / Sakit / Cuti</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red-50 text-red-600">
            <i class="bi bi-x-circle"></i>
        </div>
        <div>
            <div class="stat-value"><?= $statistik['alpha'] ?></div>
            <div class="stat-label">Tanpa Ket.</div>
        </div>
    </div>
</div>

<!-- Timeline -->
<div class="timeline-container">
    <h6 class="font-bold px-4 mb-3 text-slate-500 text-sm">RIWAYAT BULAN INI</h6>

    <?php if (empty($absensi) && empty($izin_list)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x empty-icon"></i>
            <h6 class="font-bold text-slate-800">Tidak Ada Data</h6>
            <p class="text-sm">Belum ada riwayat absensi untuk bulan yang dipilih.</p>
        </div>
    <?php else: ?>
        <?php foreach ($absensi as $a): ?>
            <?php
                $tgl = date('d', strtotime($a->tgl));
                $blnStr = date('M', strtotime($a->tgl));

                // Status Logic
                $statusClass = 'card-status-alpha';
                $badgeClass = 'bg-alpha';
                $statusLabel = 'Alpa';

                $statusRaw = strtoupper($a->status_masuk ?? '');
                if (strpos($statusRaw, 'HADIR') !== false || strpos($statusRaw, 'ONTIME') !== false) {
                    $statusClass = 'card-status-hadir';
                    $badgeClass = 'bg-hadir';
                    $statusLabel = 'Hadir Tepat Waktu';
                } elseif (strpos($statusRaw, 'LATE') !== false || strpos($statusRaw, 'TELAT') !== false) {
                    $statusClass = 'card-status-telat';
                    $badgeClass = 'bg-telat';
                    $statusLabel = 'Terlambat';
                } elseif (strpos($statusRaw, 'IZIN') !== false || strpos($statusRaw, 'SAKIT') !== false || strpos($statusRaw, 'DINAS') !== false) {
                    $statusClass = 'card-status-izin';
                    $badgeClass = 'bg-izin';
                    $statusLabel = $a->status_masuk;
                }

                $jamMasuk = ($a->jam_masuk && $a->jam_masuk != '0000-00-00 00:00:00') ? date('H:i', strtotime($a->jam_masuk)) : '--:--';
                $jamPulang = ($a->jam_pulang && $a->jam_pulang != '0000-00-00 00:00:00') ? date('H:i', strtotime($a->jam_pulang)) : '--:--';
            ?>
            <div class="timeline-item">
                <div class="date-column">
                    <div class="date-circle">
                        <?= $tgl ?>
                        <span class="date-month"><?= $blnStr ?></span>
                    </div>
                </div>
                <div class="attendance-card <?= $statusClass ?>">
                    <div class="flex justify-between items-start">
                        <span class="status-badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                        <?php if ($a->telat_menit > 0): ?>
                            <span class="text-red-600 text-[11px] font-bold">+<?= $a->telat_menit ?>m</span>
                        <?php endif; ?>
                    </div>

                    <div class="time-grid">
                        <div class="time-item">
                            <span class="time-label">Masuk</span>
                            <span class="time-value"><?= $jamMasuk ?></span>
                        </div>
                        <div class="time-item">
                            <span class="time-label">Pulang</span>
                            <span class="time-value"><?= $jamPulang ?></span>
                        </div>
                    </div>

                    <?php if ($a->keterangan): ?>
                        <div class="mt-2 pt-2 border-t border-slate-100">
                            <small class="text-slate-400 italic block" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1"></i> <?= esc($a->keterangan) ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Floating Action Button for Manual Refresh or Note -->
<!-- <div class="fixed bottom-0 right-0 p-4 mb-4" style="z-index: 100;">
    <button class="inline-flex items-center justify-center rounded-full bg-slate-900 text-white shadow-lg" style="width: 56px; height: 56px;" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise text-2xl text-white"></i>
    </button>
</div> -->

<?= $this->endSection() ?>
