<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<style>
    .input-card { border-radius: 20px; border: none; overflow: hidden; }
    .status-select { 
        border: 2px solid #e2e8f0; 
        border-radius: 12px; 
        padding: 8px 15px; 
        font-weight: 600;
        transition: all 0.2s;
        cursor: pointer;
    }
    .status-select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .status-H { background-color: #ecfdf5; color: #059669; border-color: #10b981 !important; }
    .status-S { background-color: var(--primary-50); color: var(--primary-600); border-color: var(--primary-500) !important; }
    .status-I { background-color: #fffbeb; color: #d97706; border-color: #f59e0b !important; }
    .status-A { background-color: #fef2f2; color: #dc2626; border-color: #ef4444 !important; }
    
    .loading-overlay {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.7);
        display: none; align-items: center; justify-content: center; z-index: 10;
    }
</style>

<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i><?= $judul ?></h3>
                <p class="text-muted small mb-0">Input Laporan Kehadiran Guru Harian (Cross-Check Manual)</p>
            </div>
            <div class="w-full sm:w-1/2 px-2 text-sm-end mt-2 mt-sm-0">
                <a href="<?= base_url('guru/piket/laporan') ?>" class="btn-tw btn-outline-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Lihat Hasil Rekap
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content-area">
    <div class="container-fluid px-3 px-lg-4">
        
        <div class="card input-card shadow-sm mb-4 p-4">
            <form action="" method="get" class="flex flex-wrap gap-4 gap-3 align-items-end">
                <div class="w-full md:w-1/3 px-2">
                    <label class="form-label-tw small fw-bold">Tanggal Piket</label>
                    <input type="date" name="tanggal" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill" value="<?= $tanggal ?>" onchange="this.form.submit()">
                </div>
                <div class="w-full md:w-1/3 px-2">
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> Data disimpan otomatis saat status diubah.</p>
                </div>
            </form>
        </div>

        <?php if (empty($report)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x fs-1 opacity-10 block mb-3"></i>
                <h5 class="text-muted">Jadwal tidak ditemukan untuk tanggal ini.</h5>
            </div>
        <?php else: ?>
            <div class="flex flex-wrap gap-4 gap-4">
                <?php foreach ($report as $idKls => $kls): ?>
                    <div class="w-full px-2">
                        <div class="card input-card shadow-sm position-relative">
                            <div class="loading-overlay" id="loader-<?= $idKls ?>"><div class="spinner-border text-primary"></div></div>
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i>Kelas: <?= esc($kls['nama_kelas']) ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light small fw-bold text-muted">
                                            <tr>
                                                <th class="ps-4" width="80">Jam</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Guru (Sesuai Jadwal)</th>
                                                <th width="200">Status Kehadiran</th>
                                                <th width="300" class="pe-4">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($kls['slots'] as $s): ?>
                                                <?php $manual = $s['manual']; ?>
                                                <tr>
                                                    <td class="ps-4"><span class="badge-tw badge-secondary text-dark border"><?= $s['jam_ke'] ?></span></td>
                                                    <td>
                                                        <div class="fw-bold"><?= esc($s['nama_mapel']) ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="<?= $s['nama_guru'] == 'Belum Diset' ? 'text-danger fw-bold' : '' ?>">
                                                            <?= esc($s['nama_guru']) ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <select class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white status-select status-<?= $manual->status ?? 'none' ?>" 
                                                            onchange="saveAttendance(this, <?= $idKls ?>, <?= $s['jam_ke'] ?>, <?= $s['id_mapel'] ?>, <?= $s['id_guru'] ?: 0 ?>)">
                                                            <option value="" <?= !$manual ? 'selected' : '' ?>>-- Pilih Status --</option>
                                                            <option value="H" <?= ($manual && $manual->status == 'H') ? 'selected' : '' ?>>Hadir</option>
                                                            <option value="S" <?= ($manual && $manual->status == 'S') ? 'selected' : '' ?>>Sakit</option>
                                                            <option value="I" <?= ($manual && $manual->status == 'I') ? 'selected' : '' ?>>Izin</option>
                                                            <option value="A" <?= ($manual && $manual->status == 'A') ? 'selected' : '' ?>>Alpa</option>
                                                        </select>
                                                    </td>
                                                    <td class="pe-4">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Catatan (opsional)..." 
                                                                value="<?= esc($manual->keterangan ?? '') ?>"
                                                                onblur="saveAttendance(this, <?= $idKls ?>, <?= $s['jam_ke'] ?>, <?= $s['id_mapel'] ?>, <?= $s['id_guru'] ?: 0 ?>)">
                                                            <span class="input-group-text bg-white"><i class="bi bi-chat-dots"></i></span>
                                                        </div>
                                                    </td>
                                                </tr>
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

<script>
function saveAttendance(el, idKelas, jamKe, idMapel, idGuru) {
    const flex flex-wrap gap-4 = el.closest('tr');
    const status = flex flex-wrap gap-4.querySelector('.status-select').value;
    const keterangan = flex flex-wrap gap-4.querySelector('input').value;
    const date = '<?= $tanggal ?>';

    if (!status && !keterangan) return;
    if (!status) {
        Swal.fire({ icon: 'warning', title: 'Status wajib dipilih', text: 'Pilih Hadir/Sakit/Izin/Alpa sebelum menyimpan catatan.' });
        return;
    }

    const overlay = document.getElementById('loader-' + idKelas);
    overlay.style.display = 'flex';

    const formData = new FormData();
    formData.append('tanggal', date);
    formData.append('id_kelas', idKelas);
    formData.append('jam_ke', jamKe);
    formData.append('id_mapel', idMapel);
    formData.append('id_guru', idGuru);
    formData.append('status', status);
    formData.append('keterangan', keterangan);
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url('guru/piket/save_laporan_guru') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        overlay.style.display = 'none';
        if (data.status) {
            // Update class for styling
            const select = flex flex-wrap gap-4.querySelector('.status-select');
            select.className = 'input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white status-select status-' + status;
            
            // Subtle feedback
            const toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true
            });
            toast.fire({ icon: 'success', title: data.message });
        } else {
            Swal.fire('Eror', data.message, 'error');
        }
    })
    .catch(err => {
        overlay.style.display = 'none';
        console.error(err);
    });
}
</script>

<?= $this->endSection(); ?>
