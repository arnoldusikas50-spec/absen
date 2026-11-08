<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<style>
    /* Premium Dashboard Styles */
    .welcome-banner {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::after {
        content: '';
        position: absolute;
        right: -50px;
        top: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 100px;
    }

    .stat-card-premium {
        border-radius: 20px;
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
    }

    .stat-card-premium:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
    }

    .stat-card-premium .icon-blob {
        position: absolute;
        right: -15px;
        bottom: -15px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .card-tamu {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
    }

    .card-kejadian {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .card-jurnal {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .card-absen {
        background: linear-gradient(135deg, var(--brand-500) 0%, var(--brand-600) 100%);
        color: white;
    }

    .service-card {
        border: 2px dashed #e2e8f0;
        border-radius: 24px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
        cursor: pointer;
        text-decoration: none !important;
        display: block;
        overflow: hidden;
        position: relative;
    }

    .service-card:hover {
        border-color: transparent;
        transform: scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        background: #fff;
    }

    .service-card .icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        transition: all 0.4s;
    }

    .sc-tamu:hover .icon-wrapper {
        background: #6366f1;
        color: white !important;
    }

    .sc-tamu:hover {
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
    }

    .sc-kejadian:hover .icon-wrapper {
        background: #f59e0b;
        color: white !important;
    }

    .sc-kejadian:hover {
        box-shadow: 0 20px 40px rgba(245, 158, 11, 0.15);
    }

    .sc-absen:hover .icon-wrapper {
        background: var(--brand-500);
        color: white !important;
    }

    .sc-absen:hover {
        box-shadow: 0 20px 40px rgb(var(--brand-500-rgb) / 0.15);
    }

    .sc-monitoring:hover .icon-wrapper {
        background: #0ea5e9;
        color: white !important;
    }

    .sc-monitoring:hover {
        box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
    }

    .table-premium thead {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-premium tbody tr {
        transition: all 0.2s;
    }

    .table-premium tbody tr:hover {
        background-color: #f1f5f9;
    }
</style>

<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="welcome-banner shadow">
            <div class="flex flex-wrap gap-4 align-items-center">
                <div class="w-full md:w-2/3 px-2">
                    <h2 class="text-white fw-bold mb-1">Selamat Datang, Bapak/Ibu Guru!</h2>
                    <p class="text-white text-opacity-75 mb-0">Halaman Dashboard Petugas Piket - <?= date('l, d F Y') ?>
                    </p>
                </div>
                <div class="w-full md:w-1/3 px-2 text-md-end">
                    <div id="digital-clock" class="text-white fw-extrabold h3 mb-0 opacity-50">00:00:00</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-area">
    <div class="container-fluid px-3 px-lg-4">

        <!-- Header Row -->
        <div class="flex flex-wrap gap-4 gap-4 mb-5">
            <div class="w-full md:w-1/4 px-2">
                <div class="card stat-card-premium card-tamu shadow-sm h-100 p-4">
                    <div class="icon-blob"><i class="bi bi-person-badge"></i></div>
                    <div class="position-relative">
                        <h6 class="text-uppercase small fw-bold opacity-75 mb-2">Tamu Masuk</h6>
                        <h2 class="fw-extrabold mb-0"><?= $stats['tamu'] ?> <small
                                class="fs-6 opacity-75 fw-normal">Orang</small></h2>
                    </div>
                </div>
            </div>
            <div class="w-full md:w-1/4 px-2">
                <div class="card stat-card-premium card-kejadian shadow-sm h-100 p-4">
                    <div class="icon-blob"><i class="bi bi-info-circle"></i></div>
                    <div class="position-relative">
                        <h6 class="text-uppercase small fw-bold opacity-75 mb-2">Kejadian</h6>
                        <h2 class="fw-extrabold mb-0"><?= $stats['kejadian'] ?> <small
                                class="fs-6 opacity-75 fw-normal">Kasus</small></h2>
                    </div>
                </div>
            </div>
            <div class="w-full md:w-1/4 px-2">
                <div class="card stat-card-premium card-jurnal shadow-sm h-100 p-4">
                    <div class="icon-blob"><i class="bi bi-journal-check"></i></div>
                    <div class="position-relative">
                        <h6 class="text-uppercase small fw-bold opacity-75 mb-2">Jurnal Terisi</h6>
                        <h2 class="fw-extrabold mb-0"><?= $stats['jurnal'] ?> <small
                                class="fs-6 opacity-75 fw-normal">Kelas</small></h2>
                    </div>
                </div>
            </div>
            <div class="w-full md:w-1/4 px-2">
                <div class="card stat-card-premium card-absen shadow-sm h-100 p-4">
                    <div class="icon-blob"><i class="bi bi-person-x"></i></div>
                    <div class="position-relative">
                        <h6 class="text-uppercase small fw-bold opacity-75 mb-2">Siswa Absent</h6>
                        <h2 class="fw-extrabold mb-0"><?= $stats['siswa_absen'] ?> <small
                                class="fs-6 opacity-75 fw-normal">Siswa</small></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Grid -->
        <div class="mb-4">
            <h4 class="fw-bold text-slate-800 mb-4 px-1"><i
                    class="bi bi-lightning-charge-fill text-warning me-2"></i>Layanan Utama Piket</h4>
            <div class="flex flex-wrap gap-4 gap-4">
                <div class="w-full md:w-1/4 px-2">
                    <a href="<?= base_url('guru/piket/tamu') ?>" class="service-card sc-tamu p-4 text-center">
                        <div class="icon-wrapper bg-indigo-100 text-indigo-600">
                            <i class="bi bi-person-rolodex fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Buku Tamu</h5>
                        <p class="text-muted small mb-0 px-2">Catat tamu yang berkunjung ke sekolah</p>
                    </a>
                </div>
                <div class="w-full md:w-1/4 px-2">
                    <a href="<?= base_url('guru/piket/kejadian') ?>" class="service-card sc-kejadian p-4 text-center">
                        <div class="icon-wrapper bg-amber-100 text-amber-600">
                            <i class="bi bi-exclamation-octagon fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Kejadian</h5>
                        <p class="text-muted small mb-0 px-2">Catat masalah/kejadian khusus siswa</p>
                    </a>
                </div>
                <div class="w-full md:w-1/4 px-2">
                    <a href="<?= base_url('guru/piket/absensi') ?>" class="service-card sc-absen p-4 text-center">
                        <div class="icon-wrapper bg-rose-100 text-rose-600">
                            <i class="bi bi-calendar2-check fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Absen Kelas</h5>
                        <p class="text-muted small mb-0 px-2">Lihat dan bantu rekap absensi per kelas</p>
                    </a>
                </div>
                <div class="w-full md:w-1/4 px-2">
                    <a href="<?= base_url('guru/piket/laporan') ?>"
                        class="service-card sc-monitoring p-4 text-center">
                        <div class="icon-wrapper bg-sky-100 text-sky-600">
                            <i class="bi bi-display fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Monitoring</h5>
                        <p class="text-muted small mb-0 px-2">Aktivitas Guru & Siswa terpusat</p>
                    </a>
                </div>
                <div class="w-full md:w-1/4 px-2">
                    <a href="<?= base_url('guru/piket/laporan') ?>"
                        class="service-card p-4 text-center border-primary border-opacity-25"
                        style="border-style: solid !important;">
                        <div class="icon-wrapper bg-light text-primary border border-primary border-opacity-10">
                            <i class="bi bi-file-earmark-bar-graph fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Laporan Guru</h5>
                        <p class="text-muted small mb-0 px-2">Rekap kehadiran guru di kelas</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Table Recent Activities -->
        <div class="flex flex-wrap gap-4 mt-5">
            <div class="w-full px-2">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-4 border-0 flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i
                                class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Mengajar Terbaru</h5>
                        <a href="<?= base_url('guru/piket/laporan') ?>#seksi-detail"
                            class="btn-tw btn-tw-sm btn-light text-primary fw-bold rounded-pill px-3">Lihat Semua <i
                                class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-premium align-middle mb-0">
                                <thead class="small text-uppercase fw-bold text-muted">
                                    <tr>
                                        <th class="ps-4">Jam</th>
                                        <th>Nama Guru</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Kelas</th>
                                        <th class="pe-4 text-center">Siswa Absent</th>
                                    </tr>
                                <tbody>
                                    <?php if (empty($jurnal_terbaru)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <i class="bi bi-journal-x fs-1 opacity-10 block mb-2"></i>
                                                <span class="text-muted">Belum ada aktivitas mengajar hari ini.</span>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($jurnal_terbaru as $j): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold"><?= $j->jam_ke ?></td>
                                                <td class="fw-bold text-primary"><?= esc($j->nama_guru) ?></td>
                                                <td class="text-muted"><?= esc($j->nama_mapel) ?></td>
                                                <td><span
                                                        class="badge-tw badge-secondary text-dark border px-3 rounded-pill fw-normal"><?= esc($j->nama_kelas) ?></span>
                                                </td>
                                                <td class="pe-4 text-center">
                                                    <?php if (!\App\Models\PiketModel::parseSiswaAbsent($j->siswa_absent ?? null)['nihil']): ?>
                                                        <span class="badge-tw badge-danger bg-opacity-10 text-danger rounded-pill px-3"><i
                                                                class="bi bi-person-x me-1"></i> Ada Siswa</span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">- Nihil -</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Real-time Clock
    function updateClock() {
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0') + ':' +
            now.getSeconds().toString().padStart(2, '0');
        document.getElementById('digital-clock').textContent = timeStr;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
<?= $this->endSection(); ?>