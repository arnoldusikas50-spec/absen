<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-primary"><i class="bi bi-calendar-check-fill me-2"></i><?= $judul ?></h3>
                <p class="text-muted small mb-0"><?= $subjudul ?></p>
            </div>
            <div class="w-full sm:w-1/2 px-2 text-sm-end mt-2 mt-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb sm:float-right mb-0 bg-transparent">
                        <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Absensi</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content-area">
    <div class="container-fluid px-3 px-lg-4">
        
        <?php if (!$guru): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Data guru tidak ditemukan. Silakan hubungi administrator.
        </div>
        <?php else: ?>

        <!-- Filter Section -->
        <div class="flex flex-wrap gap-4 mb-4">
            <div class="w-full px-2">
                <div class="card card-outline card-primary border-0 shadow-sm">
                    <div class="card-body">
                        <form method="get" action="<?= base_url('guru/absensi') ?>" class="flex flex-wrap gap-4 gap-3 align-items-end">
                            <div class="w-full md:w-1/4 px-2">
                                <label for="bulan" class="form-label-tw fw-bold">Bulan</label>
                                <select name="bulan" id="bulan" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                                    <?php foreach ($bulan_ini as $key => $nama): ?>
                                    <option value="<?= $key ?>" <?= ($bulan == $key) ? 'selected' : '' ?>><?= $nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="w-full md:w-1/4 px-2">
                                <label for="tahun" class="form-label-tw fw-bold">Tahun</label>
                                <select name="tahun" id="tahun" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                                    <?php for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                                    <option value="<?= $y ?>" <?= ($tahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="w-full md:w-1/4 px-2">
                                <button type="submit" class="btn-tw btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                            <div class="w-full md:w-1/4 px-2">
                                <a href="<?= base_url('guru/absensi/export?bulan=' . $bulan . '&tahun=' . $tahun . '&format=excel') ?>" class="btn-tw btn-success w-100">
                                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export Excel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Policy Section -->
        <div class="flex flex-wrap gap-4 mb-4">
            <div class="w-full px-2">
                <div class="collapse-tw-group space-y-2 shadow-sm" id="policyAccordion">
                    <div class="collapse-tw border border-slate-200 rounded-xl overflow-hidden bg-white border-0">
                        <h2 class="collapse-tw-header" id="headingPolicy">
                            <button class="collapse-tw-toggle w-full flex items-center justify-between gap-2 p-4 font-bold text-left bg-white collapsed bg-white text-primary fw-bold" type="button" data-ui-toggle="collapse" data-ui-target="#collapsePolicy" aria-expanded="false" aria-controls="collapsePolicy">
                                <i class="bi bi-info-circle-fill me-2"></i> Kebijakan Absensi & Sanksi
                            </button>
                        </h2>
                        <div id="collapsePolicy" class="collapse-tw-panel hidden" aria-labelledby="headingPolicy" data-ui-parent="#policyAccordion">
                            <div class="collapse-tw-body p-4 bg-light bg-opacity-25">
                                <div class="flex flex-wrap gap-4 gap-4">
                                    <div class="w-full md:w-1/2 px-2">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <div class="card-header bg-primary text-white py-2">
                                                <h6 class="mb-0 small fw-bold"><i class="bi bi-clock me-2"></i>Aturan Jam Kerja</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <ul class="list-group list-group-flush small">
                                                    <li class="list-group-item flex justify-content-between align-items-center">
                                                        <span>Jam Masuk</span>
                                                        <span class="badge-tw badge-primary rounded-pill"><?= substr($attendance_setting->jam_masuk ?? '07:30:00', 0, 5) ?></span>
                                                    </li>
                                                    <li class="list-group-item flex justify-content-between align-items-center">
                                                        <span>Jam Pulang</span>
                                                        <span class="badge-tw badge-success rounded-pill"><?= substr($attendance_setting->jam_pulang ?? '15:00:00', 0, 5) ?></span>
                                                    </li>
                                                    <li class="list-group-item flex justify-content-between align-items-center">
                                                        <span>Toleransi Keterlambatan</span>
                                                        <span class="badge-tw badge-secondary rounded-pill"><?= $attendance_setting->grace_period ?? '15' ?> menit</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-full md:w-1/2 px-2">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <div class="card-header bg-danger text-white py-2">
                                                <h6 class="mb-0 small fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Poin & Denda Pelanggaran</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-striped mb-0 small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Jenis</th>
                                                                <th class="text-center">Poin</th>
                                                                <th class="text-end">Denda (Rp)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            // Tarif yang ditampilkan harus sama dengan yang dipakai
                                                            // perhitungan. Aturan sebenarnya AttendancePenaltyService::calculatePenalty()
                                                            // memakai tiga bertingkat (batas 30 dan 60 menit) dari
                                                            // denda_tier_1/2/3 — BUKAN denda_per_menit maupun
                                                            // denda_pulang_awal, yang keduanya tidak terpakai
                                                            // perhitungan sama sekali. Kalau yang ditampilkan tetap
                                                            // kedua field itu, guru melihat nominal yang salah.
                                                            $d1 = (int) ($attendance_setting->denda_tier_1 ?? 100);
                                                            $d2 = (int) ($attendance_setting->denda_tier_2 ?? 200);
                                                            $d3 = (int) ($attendance_setting->denda_tier_3 ?? 500);
                                                            $p1 = (int) ($attendance_setting->point_tier_1 ?? 1);
                                                            $p2 = (int) ($attendance_setting->point_tier_2 ?? 2);
                                                            $p3 = (int) ($attendance_setting->point_tier_3 ?? 5);
                                                            $adaTier = ($d1 > 0 || $d2 > 0 || $d3 > 0);
                                                            ?>
                                                            <?php if ($adaTier): ?>
                                                                <tr>
                                                                    <td>Telat 1&ndash;30 menit</td>
                                                                    <td class="text-center text-danger fw-bold">-<?= $p1 ?>/mnt</td>
                                                                    <td class="text-end"><?= number_format($d1, 0, ',', '.') ?>/mnt</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Telat 31&ndash;60 menit</td>
                                                                    <td class="text-center text-danger fw-bold">-<?= $p2 ?>/mnt</td>
                                                                    <td class="text-end"><?= number_format($d2, 0, ',', '.') ?>/mnt</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Telat &gt; 60 menit</td>
                                                                    <td class="text-center text-danger fw-bold">-<?= $p3 ?>/mnt</td>
                                                                    <td class="text-end"><?= number_format($d3, 0, ',', '.') ?>/mnt</td>
                                                                </tr>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td>Telat (per menit)</td>
                                                                    <td class="text-center text-danger fw-bold">-<?= $attendance_setting->point_telat ?? 1 ?></td>
                                                                    <td class="text-end">0</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                            <tr>
                                                                <td>Alpha (per hari)</td>
                                                                <td class="text-center text-danger fw-bold">-<?= $attendance_setting->point_alpa ?? 5 ?></td>
                                                                <td class="text-end text-muted">-</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Sakit</td>
                                                                <td class="text-center text-warning fw-bold"><?= $attendance_setting->point_sakit ?? 0 ?></td>
                                                                <td class="text-end">0</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Izin</td>
                                                                <td class="text-center text-warning fw-bold"><?= $attendance_setting->point_izin ?? 0 ?></td>
                                                                <td class="text-end">0</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                </div>
                            </div>
                            <div class="w-full px-2">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-success text-white py-2">
                                        <h6 class="mb-0 small fw-bold"><i class="bi bi-check-circle me-2"></i>Prosedur Izin & Sakit</h6>
                                    </div>
                                    <div class="card-body bg-white small">
                                        <div class="flex align-items-start mb-2">
                                            <i class="bi bi-info-circle-fill text-primary me-2 mt-1"></i>
                                            <div>
                                                <strong>Otomatisasi Status:</strong> 
                                                Pengajuan izin atau sakit yang telah <u>disetujui oleh admin</u> akan secara otomatis tercatat dalam sistem absensi harian. 
                                                Anda tidak perlu melakukan absen manual untuk hari-hari yang telah disetujui izinnya.
                                            </div>
                                        </div>
                                        <div class="flex align-items-start">
                                            <i class="bi bi-exclamation-circle-fill text-warning me-2 mt-1"></i>
                                            <div>
                                                <strong>Bukti Pendukung:</strong>
                                                Setiap pengajuan sakit wajib melampirkan surat dokter. Izin pribadi harus diajukan minimal H-1 kecuali kondisi darurat.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 small text-muted fst-italic border-top pt-2">
                                    <i class="bi bi-info-circle me-1"></i> Catatan: Denda keterlambatan & pulang awal dihitung berjenjang (tier) per menit. Alpha dikenakan denda flat per hari.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="flex flex-wrap gap-4 gap-3 mb-4">
            <div class="w-full lg:w-1/4 px-2 w-1/2 px-2">
                <div class="small-box bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm rounded-3">
                    <div class="inner p-3">
                        <h3 class="fw-bold text-success mb-1"><?= $statistik['hadir'] ?></h3>
                        <p class="text-muted mb-0">Hadir</p>
                    </div>
                    <div class="icon text-success opacity-25">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/4 px-2 w-1/2 px-2">
                <div class="small-box bg-warning bg-opacity-10 border border-warning border-opacity-25 shadow-sm rounded-3">
                    <div class="inner p-3">
                        <h3 class="fw-bold text-warning mb-1"><?= $statistik['izin'] ?></h3>
                        <p class="text-muted mb-0">Izin / Cuti</p>
                    </div>
                    <div class="icon text-warning opacity-25">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/4 px-2 w-1/2 px-2">
                <div class="small-box bg-info bg-opacity-10 border border-info border-opacity-25 shadow-sm rounded-3">
                    <div class="inner p-3">
                        <h3 class="fw-bold text-info mb-1"><?= $statistik['sakit'] ?></h3>
                        <p class="text-muted mb-0">Sakit</p>
                    </div>
                    <div class="icon text-info opacity-25">
                        <i class="bi bi-hospital-fill"></i>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/4 px-2 w-1/2 px-2">
                <div class="small-box bg-danger bg-opacity-10 border border-danger border-opacity-25 shadow-sm rounded-3">
                    <div class="inner p-3">
                        <h3 class="fw-bold text-danger mb-1"><?= $statistik['alpha'] ?></h3>
                        <p class="text-muted mb-0">Alpha</p>
                    </div>
                    <div class="icon text-danger opacity-25">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $totalPoin = 0;
        $totalDendaHarian = 0;
        foreach ($absensi as $a) {
            if (isset($a->points))
                $totalPoin += (int) $a->points;
            if (isset($a->denda) && (float)$a->denda > 0)
                $totalDendaHarian += (float)$a->denda;
        }
        // $totalDendaHarian di atas sudah mencakup denda ALPHA yang tercatat,
        // jadi hanya tambahkan estimasi tanggal bolong (anti double-count).
        $alphaMissingCount = (int) ($alpha_missing_count ?? 0);
        $alphaMissingDenda = (float) ($alpha_missing_denda ?? 0);
        $totalPoin += ($alphaMissingCount * -abs((int) ($attendance_setting->point_alpa ?? 5)));

        $dendaAlpha = $alphaMissingDenda;
        $totalDenda = $totalDendaHarian + $dendaAlpha;
        ?>

        <div class="flex flex-wrap gap-4 gap-3 mb-4">
            <div class="w-full px-2">
                <div class="card border-0 shadow-sm bg-primary bg-opacity-10 border-start border-primary border-4">
                    <div class="card-body flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="text-primary fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i>Ringkasan Denda Bulan Ini</h6>
                            <p class="text-muted small mb-0">Denda harian (telat/pulang awal) + denda alpha.</p>
                        </div>
                        <div class="text-end">
                            <div class="flex align-items-center justify-content-end gap-3">
                                <div class="text-center">
                                    <span class="block small text-muted">Total Poin</span>
                                    <span class="fw-bold fs-5 text-dark"><?= $totalPoin ?></span>
                                </div>
                                <div class="vr mx-2 h-100 py-3"></div>
                                <div class="text-end">
                                    <span class="block small text-muted">Total Denda</span>
                                    <span class="fw-bold fs-4 text-primary">Rp <?= number_format($totalDenda, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="flex flex-wrap gap-4 mb-4">
            <div class="w-full px-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="card-title fw-bold">
                            <i class="bi bi-table me-2"></i> Riwayat Absensi - 
                            <?= $bulan_ini[$bulan] ?? '' ?> <?= $tahun ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($absensi)): ?>
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info">
                            <i class="bi bi-info-circle-fill me-2"></i> Belum ada data absensi untuk bulan ini.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="tableAbsensi">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">Tanggal</th>
                                        <th width="15%">Masuk</th>
                                        <th width="15%">Pulang</th>
                                        <th width="15%">Status Masuk</th>
                                        <th width="15%">Telat</th>
                                        <th width="10%">Poin</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($absensi as $a): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d/m/Y', strtotime($a->tgl ?? '')) ?></td>
                                        <td>
                                            <?php if (isset($a->jam_masuk)): ?>
                                                <span class="badge-tw badge-info fw-normal fst-normal"><?= $a->jam_masuk ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($a->jam_pulang)): ?>
                                                <span class="badge-tw badge-secondary fw-normal fst-normal"><?= $a->jam_pulang ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = isset($a->status_masuk) ? strtolower($a->status_masuk) : 'unknown';
                                            $badgeClass = 'secondary';
                                            
                                            if (strpos($status, 'hadir') !== false || strpos($status, 'tepat') !== false) {
                                                $badgeClass = 'success';
                                            } elseif (strpos($status, 'telat') !== false) {
                                                $badgeClass = 'warning';
                                            } elseif (strpos($status, 'izin') !== false) {
                                                $badgeClass = 'info';
                                            }
                                            ?>
                                            <span class="badge-tw badge-<?= $badgeClass ?>"><?= ucfirst($a->status_masuk ?? '-') ?></span>
                                        </td>
                                        <td>
                                            <?php if (isset($a->telat_menit) && $a->telat_menit > 0): ?>
                                                <span class="text-danger fw-bold"><?= $a->telat_menit ?> menit</span>
                                            <?php else: ?>
                                                <small class="text-muted">Tepat Waktu</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($a->points) && $a->points > 0): ?>
                                                <span class="badge-tw badge-danger rounded-pill"><?= $a->points ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('guru/absensi/detail/' . ($a->tgl ?? '')) ?>" 
                                               class="btn-tw btn-tw-sm btn-info text-white" 
                                               title="Lihat Detail">
                                                <i class="bi bi-eye-fill"></i>
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
            </div>
        </div>

        <!-- Leave/Permission Requests -->
        <div class="flex flex-wrap gap-4">
            <div class="w-full px-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="card-title fw-bold">
                            <i class="bi bi-file-earmark-medical me-2"></i> Riwayat Pengajuan Izin/Cuti
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($izin_list)): ?>
                            <div class="text-muted fst-italic">Tidak ada riwayat pengajuan izin.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">Tanggal Pengajuan</th>
                                        <th width="20%">Tanggal Izin</th>
                                        <th width="10%">Jenis</th>
                                        <th width="30%">Keterangan</th>
                                        <th width="15%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($izin_list as $izin): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($izin->created_at ?? '')) ?></td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($izin->tgl_mulai ?? '')) ?>
                                            <?php if($izin->tgl_mulai != $izin->tgl_selesai): ?>
                                                s/d <br><?= date('d/m/Y', strtotime($izin->tgl_selesai ?? '')) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= ($izin->jenis ?? '') == 'sakit' ? 'info' : 'warning' ?>">
                                                <?= ucfirst($izin->jenis ?? '') ?>
                                            </span>
                                            <?php if (($izin->jenis ?? '') === 'cuti'
                                                && !empty($izin->kategori_cuti)
                                                && \App\Services\CutiKategoriService::valid($izin->kategori_cuti)): ?>
                                                <div class="mt-1"><span class="badge badge-light border text-dark fw-normal small text-wrap">
                                                    <?= esc(\App\Services\CutiKategoriService::label($izin->kategori_cuti)) ?>
                                                </span></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?= esc($izin->keterangan ?? '-') ?></small></td>
                                        <td>
                                            <?php
                                            $statusIzin = $izin->status ?? 0;
                                            $statusBadge = 'secondary';
                                            $statusLabel = 'Pending';
                                            
                                            if ($statusIzin == 1) {
                                                $statusBadge = 'success';
                                                $statusLabel = 'Disetujui';
                                            } elseif ($statusIzin == 2) {
                                                $statusBadge = 'danger';
                                                $statusLabel = 'Ditolak';
                                            }
                                            ?>
                                            <span class="badge-tw badge-<?= $statusBadge ?>"><?= $statusLabel ?></span>
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

        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#tableAbsensi').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[1, "desc"]], // Sort by date descending
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});
</script>
<?= $this->endSection() ?>
