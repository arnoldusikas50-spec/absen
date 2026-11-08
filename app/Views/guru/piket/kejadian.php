<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-warning"><i class="bi bi-exclamation-octagon me-2"></i><?= $judul ?></h3>
                <p class="text-muted small mb-0">Catatan Kejadian, Pelanggaran, dan Prestasi Siswa</p>
            </div>
            <div class="w-full sm:w-1/2 px-2 text-sm-end mt-2 mt-sm-0">
                <a href="<?= base_url('guru/piket') ?>" class="btn-tw btn-outline-secondary shadow-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="content-area">
    <div class="container-fluid px-3 px-lg-4">
        <div class="card card-outline card-warning shadow-sm border-0 mb-4" style="border-radius: 15px;">
            <div class="card-header border-0 bg-transparent py-3 flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-calendar3 me-2 text-warning"></i>Data Kejadian: <span
                        class="text-muted fw-normal"><?= date('d M Y', strtotime($tanggal)) ?></span></h6>
                <button class="btn-tw btn-tw-sm btn-warning rounded-pill px-3 shadow-sm" onclick="showModalAdd()">
                    <i class="bi bi-plus-circle me-1"></i> CATAT KEJADIAN
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="ps-4">Jam</th>
                                <th>Jenis</th>
                                <th>Siswa Terlapor</th>
                                <th>Keterangan</th>
                                <th>Penanganan / Tindak Lanjut</th>
                                <th class="pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($data_kejadian)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-journal-x fs-1 opacity-10 block mb-2"></i>
                                        <span class="text-muted">Tidak ada data kejadian pada hari ini.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_kejadian as $k): ?>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    if ($k->jenis == 'Pelanggaran')
                                        $badgeClass = 'bg-danger';
                                    if ($k->jenis == 'Kejadian')
                                        $badgeClass = 'bg-warning text-dark';
                                    if ($k->jenis == 'Sakit')
                                        $badgeClass = 'bg-info text-dark';
                                    if ($k->jenis == 'Prestasi')
                                        $badgeClass = 'bg-success';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= date('H:i', strtotime((string) $k->jam)) ?></td>
                                        <td><span
                                                class="badge border <?= $badgeClass ?> rounded-pill px-3 opacity-75"><?= esc($k->jenis) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($k->nama_siswa): ?>
                                                <div class="fw-bold"><?= esc($k->nama_siswa) ?></div>
                                                <div class="text-muted uppercase" style="font-size: 0.75rem;"><i
                                                        class="bi bi-door-open me-1"></i><?= esc($k->nama_kelas) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted" style="max-width: 250px;"><?= esc($k->keterangan) ?></td>
                                        <td>
                                            <div class="text-dark fw-bold small"><?= esc($k->penanganan) ?></div>
                                            <div class="text-success small mt-1 italic"><?= esc((string) $k->tindak_lanjut) ?>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <button onclick="hapus(<?= $k->id ?>)"
                                                class="btn-tw btn-tw-sm btn-outline-danger border-0 rounded-circle" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
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

<!-- Modal Tailwind -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="modalKejadian" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg modal-box-tw">
        <div class="modal-content-tw bg-white w-full rounded-2xl shadow-xl overflow-hidden border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i>Catat Kejadian
                    Siswa</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formKejadian">
                <div class="modal-body">
                    <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
                    <div class="flex flex-wrap gap-4 gap-3">
                        <div class="w-full md:w-1/2 px-2">
                            <label class="form-label-tw small fw-bold">Jam Kejadian</label>
                            <input type="time" name="jam" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill" value="<?= date('H:i') ?>"
                                required>
                        </div>
                        <div class="w-full md:w-1/2 px-2">
                            <label class="form-label-tw small fw-bold">Jenis Kejadian</label>
                            <select name="jenis" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white rounded-pill">
                                <option value="Pelanggaran">Pelanggaran Tata Tertib</option>
                                <option value="Kejadian">Kejadian Khusus / Lainnya</option>
                                <option value="Sakit">Sakit / Kesehatan</option>
                                <option value="Prestasi">Prestasi</option>
                            </select>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Siswa Terlibat (Opsional)</label>
                            <select name="terlapor_siswa_id" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white select2-basic rounded-pill">
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_list as $s): ?>
                                    <option value="<?= $s->id_siswa ?>">
                                        <?= esc($s->nama_kelas) ?> - <?= esc($s->nama) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Pelapor / Saksi</label>
                            <input type="text" name="pelapor" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill"
                                placeholder="Nama Guru/Siswa yang melapor">
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Keterangan Kejadian</label>
                            <textarea name="keterangan" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" rows="3"
                                placeholder="Jelaskan kronologi singkat..." required
                                style="border-radius: 15px;"></textarea>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Penanganan / Tindak Lanjut</label>
                            <textarea name="penanganan" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" rows="2"
                                placeholder="Apa yang dilakukan petugas piket?" style="border-radius: 15px;"></textarea>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold text-success">Status Penyelesaian Akhir</label>
                            <input type="text" name="tindak_lanjut" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill"
                                placeholder="e.g. Selesai, Perlu pembinaan BK, atau Panggil Orang Tua">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-tw btn-light rounded-pill px-4" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-warning rounded-pill px-5 fw-bold shadow-sm">SIMPAN
                        DATA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showModalAdd() {
        $('#formKejadian')[0].reset();
        AdminUI.modal.open('#modalKejadian');
    }

    $(document).ready(function () {
        // Initialize Select2 in Modal
        $('#modalKejadian').on('shown', function () {
            $('.select2-basic').select2({
                dropdownParent: $('#modalKejadian'),
                theme: 'default' /* Tailwind */
            });
        });

        $('#formKejadian').on('submit', function (e) {
            e.preventDefault();
            Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });

            $.post('<?= base_url('guru/piket/save_kejadian') ?>', $(this).serialize(), function (res) {
                if (res.status) {
                    Swal.fire('Sukses', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        });
    });

    function hapus(id) {
        Swal.fire({
            title: 'Hapus Data?',
            text: "Data kejadian ini akan dihapus secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            customClass: { confirmButton: 'btn-tw btn-danger rounded-pill px-4', cancelButton: 'btn-tw btn-light rounded-pill px-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= base_url('guru/piket/delete_kejadian') ?>', { id: id, <?= csrf_token() ?>: '<?= csrf_hash() ?>' }, function (res) {
                    if (res.status) {
                        Swal.fire('Terhapus!', res.message, 'success').then(() => location.reload());
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection(); ?>