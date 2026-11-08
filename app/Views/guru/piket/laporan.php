<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<style>
    .report-card { border-radius: 20px; transition: all 0.3s; border: none; }
    .status-box { 
        padding: 5px 12px; 
        border-radius: 8px; 
        font-size: 0.75rem; 
        font-weight: 700; 
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .check-success { background: #dcfce7; color: #166534; }
    .check-danger { background: #fee2e2; color: #991b1b; }
    .check-warning { background: #fef3c7; color: #92400e; }
    .check-neutral { background: #f1f5f9; color: #475569; }

    .cross-badge {
        width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }
</style>

<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-diff me-2"></i><?= $judul ?></h3>
                <p class="text-muted small mb-0">Perbandingan Kehadiran Guru (Piket vs Jurnal Guru)</p>
            </div>
            <div class="w-full sm:w-1/2 px-2 text-sm-end mt-2 mt-sm-0">
                <a href="<?= base_url('guru/piket/input_laporan') ?>" class="btn-tw btn-primary rounded-pill px-4 shadow-sm me-2">
                    <i class="bi bi-pencil-square me-1"></i> Input Laporan Piket
                </a>
                <a href="<?= base_url('guru/piket') ?>" class="btn-tw btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content-area">
    <div class="container-fluid px-3 px-lg-4">
        
        <div class="card report-card shadow-sm mb-4 p-4">
            <form action="" method="get" class="flex flex-wrap gap-4 gap-3 align-items-end">
                <div class="w-full md:w-1/4 px-2">
                    <label class="form-label-tw small fw-bold">Tanggal</label>
                    <input type="date" name="tanggal" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill" value="<?= $tanggal ?>" onchange="this.form.submit()">
                </div>
                <div class="w-full md:w-3/4 px-2 text-md-end">
                    <div class="d-inline-flex gap-3 small fw-bold text-muted bg-light p-2 px-3 rounded-pill border">
                        <span><i class="bi bi-person-check-fill text-success me-1"></i> Jurnal = Guru isi mandiri</span>
                        <span class="border-start ps-3"><i class="bi bi-clipboard-check-fill text-primary me-1"></i> Piket = Petugas piket cek manual</span>
                    </div>
                </div>
            </form>
        </div>

        <?php if (empty($report)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 opacity-10"></i>
                <h5 class="text-muted">Tidak ada jadwal KBM ditemukan.</h5>
            </div>
        <?php else: ?>
            <div class="flex flex-wrap gap-4 gap-4">
                <?php foreach ($report as $kls): ?>
                    <div class="col-xl-6">
                        <div class="card report-card shadow-sm h-100">
                            <div class="card-header bg-white border-0 py-3 flex justify-content-between">
                                <h6 class="mb-0 fw-bold">Kelas: <?= esc($kls['nama_kelas']) ?></h6>
                                <span class="badge-tw badge-secondary text-dark border rounded-pill">Sesuai Jadwal</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="bg-light x-small text-uppercase fw-bold text-muted">
                                            <tr>
                                                <th class="ps-3" width="50">Jam</th>
                                                <th>Mata Pelajaran / Guru</th>
                                                <th class="text-center" width="100">Jurnal Guru</th>
                                                <th class="text-center" width="100">Laporan Piket</th>
                                                <th class="pe-3 text-center" width="60">Match?</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($kls['slots'] as $s): ?>
                                                <?php 
                                                    $isFilled = $s['jurnal'];
                                                    $piket = $s['piket'];
                                                    $isPiketHadir = ($piket && $piket->status == 'H');
                                                    $match = ($isFilled == $isPiketHadir);
                                                ?>
                                                <tr class="<?= !$match ? 'bg-danger bg-opacity-10' : '' ?>">
                                                    <td class="ps-3 text-center"><small class="fw-bold"><?= $s['jam_ke'] ?></small></td>
                                                    <td>
                                                        <div class="fw-bold small"><?= esc($s['nama_mapel']) ?></div>
                                                        <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-person me-1"></i><?= esc($s['nama_guru']) ?></div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($isFilled): ?>
                                                            <span class="status-box check-success"><i class="bi bi-check-circle"></i> ISI</span>
                                                        <?php else: ?>
                                                            <span class="status-box check-danger"><i class="bi bi-x-circle"></i> KOSONG</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($piket): ?>
                                                            <span class="status-box <?= $isPiketHadir ? 'check-success' : 'check-warning' ?>">
                                                                <i class="bi bi-<?= $isPiketHadir ? 'person-check' : 'person-x' ?>"></i> <?= $piket->status ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-box check-neutral small text-opacity-50">? BELUM</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="pe-3 text-center">
                                                        <?php if (!$piket): ?>
                                                            <i class="bi bi-question-circle text-muted"></i>
                                                        <?php elseif ($match): ?>
                                                            <i class="bi bi-check-all text-success fs-4"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-exclamation-triangle-fill text-danger" title="Data Tidak Sesuai!"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php if ($piket && $piket->keterangan): ?>
                                                    <tr class="<?= !$match ? 'bg-danger bg-opacity-10' : '' ?>">
                                                        <td colspan="5" class="ps-3 border-0 pb-2">
                                                            <div class="p-2 x-small bg-white bg-opacity-50 rounded border" style="font-size: 0.7rem;">
                                                                <i class="bi bi-info-circle me-1"></i>Catatan Piket: <?= esc($piket->keterangan) ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<div class="container-fluid mb-5 mt-4">
    <div class="alert alert-info border-0 shadow-sm rounded-4 ps-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="col-auto"><i class="bi bi-lightbulb fs-2"></i></div>
            <div class="col">
                <h6 class="fw-bold mb-1">Tips Cross-Check</h6>
                <p class="small mb-0">Baris berwarna <span class="bg-danger bg-opacity-50 px-1 rounded">merah</span> menandakan adanya ketidaksesuaian antara jurnal yang diisi guru dengan pantauan manual petugas piket. Hal ini berguna untuk memastikan objektivitas data kehadiran guru.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>
