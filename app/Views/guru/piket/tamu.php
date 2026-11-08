<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-primary"><i class="bi bi-person-badge me-2"></i><?= $judul ?></h3>
                <p class="text-muted small mb-0">Registrasi Tamu dan Kunjungan Sekolah</p>
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
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
            <div class="card-header border-0 bg-transparent py-3 flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-calendar3 me-2 text-primary"></i>Daftar Tamu: <span
                        class="text-muted fw-normal"><?= date('d M Y', strtotime($tanggal)) ?></span></h6>
                <button class="btn-tw btn-tw-sm btn-primary rounded-pill px-3 shadow-sm" onclick="showModalAdd()">
                    <i class="bi bi-person-plus-fill me-1"></i> REGISTRASI TAMU
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="ps-4">Jam Masuk</th>
                                <th>Nama Lengkap Tamu</th>
                                <th>Asal Instansi / Alamat</th>
                                <th>Bertemu Dengan</th>
                                <th>Tujuan / Keperluan</th>
                                <th class="pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($data_tamu)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-person-vcard fs-1 opacity-10 block mb-2"></i>
                                        <span class="text-muted">Tidak ada tamu yang terdaftar hari ini.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_tamu as $t): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span
                                                class="badge-tw badge-primary bg-opacity-10 text-primary px-3 rounded-pill fw-bold"><?= date('H:i', strtotime((string) $t->jam_masuk)) ?></span>
                                        </td>
                                        <td class="fw-bold text-dark"><?= esc($t->nama) ?></td>
                                        <td><i class="bi bi-building me-1 text-muted"></i> <?= esc($t->instansi) ?></td>
                                        <td><span class="text-primary fw-bold"><?= esc($t->bertemu) ?></span></td>
                                        <td class="text-muted"><?= esc($t->keperluan) ?></td>
                                        <td class="pe-4 text-center">
                                            <button onclick="hapus(<?= $t->id ?>)"
                                                class="btn-tw btn-tw-sm btn-outline-danger border-0 rounded-circle"
                                                title="Hapus Data">
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
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="modalTamu" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg modal-box-tw">
        <div class="modal-content-tw bg-white w-full rounded-2xl shadow-xl overflow-hidden border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-person-rolodex me-2"></i>Form Registrasi
                    Tamu</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTamu">
                <div class="modal-body py-4">
                    <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
                    <div class="flex flex-wrap gap-4 gap-3">
                        <div class="w-full md:w-1/2 px-2">
                            <label class="form-label-tw small fw-bold">Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill"
                                value="<?= date('H:i') ?>" required>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Nama Lengkap Tamu</label>
                            <input type="text" name="nama"
                                class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill border-primary border-opacity-25"
                                placeholder="Masukkan nama tamu..." required>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Instansi / Alamat</label>
                            <input type="text" name="instansi" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill"
                                placeholder="e.g. Dinas Pendidikan / Alamat Rumah">
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Bertemu Dengan</label>
                            <input type="text" name="bertemu"
                                class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm rounded-pill border-primary border-opacity-25"
                                placeholder="Nama Guru / Staf yang dituju" required>
                        </div>
                        <div class="w-full px-2">
                            <label class="form-label-tw small fw-bold">Tujuan / Keperluan</label>
                            <textarea name="keperluan" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" rows="3"
                                placeholder="Deskripsikan singkat keperluan bertamu..."
                                style="border-radius: 15px;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-tw btn-light rounded-pill px-4" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary rounded-pill px-5 fw-bold shadow-sm">SIMPAN DATA
                        TAMU</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showModalAdd() {
        $('#formTamu')[0].reset();
        AdminUI.modal.open('#modalTamu');
    }

    $(document).ready(function () {
        $('#formTamu').on('submit', function (e) {
            e.preventDefault();
            Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });

            $.post('<?= base_url('guru/piket/save_tamu') ?>', $(this).serialize(), function (res) {
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
            title: 'Hapus Data Tamu?',
            text: "Data kunjungan tamu ini akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            customClass: { confirmButton: 'btn-tw btn-danger rounded-pill px-4', cancelButton: 'btn-tw btn-light rounded-pill px-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= base_url('guru/piket/delete_tamu') ?>', { id: id, <?= csrf_token() ?>: '<?= csrf_hash() ?>' }, function (res) {
                    if (res.status) {
                        Swal.fire('Terhapus!', res.message, 'success').then(() => location.reload());
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection(); ?>