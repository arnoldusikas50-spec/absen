<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<style>
    .radio-status {
        margin-right: 8px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid #ddd;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    .status-cell input[type="radio"] {
        display: none;
    }

    .radio-status:hover {
        background: #f8f9fa;
        border-color: var(--bs-primary);
    }

    input[type="radio"]:checked+span {
        font-weight: bold;
    }

    /* Highlight Classes */
    .bg-hadir {
        background-color: rgba(25, 135, 84, 0.05) !important;
    }

    .bg-sakit {
        background-color: rgba(13, 110, 253, 0.05) !important;
    }

    .bg-izin {
        background-color: rgba(255, 193, 7, 0.05) !important;
    }

    .bg-alpha {
        background-color: rgba(220, 53, 69, 0.05) !important;
    }

    .bg-telat {
        background-color: rgba(108, 117, 125, 0.05) !important;
    }

    .status-cell label input:checked+.status-text-H {
        color: #198754;
        font-weight: 800;
    }

    .status-cell label input:checked+.status-text-S {
        color: #0d6efd;
        font-weight: 800;
    }

    .status-cell label input:checked+.status-text-I {
        color: #ffc107;
        font-weight: 800;
    }

    .status-cell label input:checked+.status-text-A {
        color: #dc3545;
        font-weight: 800;
    }

    .status-cell label input:checked+.status-text-T {
        color: #6c757d;
        font-weight: 800;
    }

    .status-cell label:has(input:checked) {
        border-color: currentColor !important;
        background: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-danger"><i class="bi bi-calendar-check me-2"></i><?= $judul ?></h3>
                <p class="text-muted small mb-0">Input Rekapitulasi Absensi Per Kelas oleh Piket</p>
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
        <div class="card card-outline card-danger shadow-sm border-0 mb-4" style="border-radius: 15px;">
            <div class="card-header border-0 bg-transparent py-3">
                <form method="get" class="flex flex-wrap gap-4 gap-3 align-items-center">
                    <div class="w-full md:w-1/4 px-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-primary border-opacity-25"><i
                                    class="bi bi-calendar-event text-primary"></i></span>
                            <input type="date" name="tanggal" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm border-primary border-opacity-25"
                                value="<?= $tanggal ?>">
                        </div>
                    </div>
                    <div class="w-full md:w-1/4 px-2">
                        <select name="kelas"
                            class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white form-select-sm border-primary border-opacity-25 rounded-pill"
                            onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_list as $k): ?>
                                <option value="<?= $k->id_kelas ?>" <?= ($kelas_selected == $k->id_kelas) ? 'selected' : '' ?>>
                                    <?= esc($k->nama_kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn-tw btn-tw-sm btn-primary rounded-pill px-3 w-100">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($kelas_selected && !empty($siswa_absensi)): ?>
                <div class="card-body p-0">
                    <form id="formAbsensi">
                        <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
                        <input type="hidden" name="id_kelas" value="<?= $kelas_selected ?>">

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light small fw-bold text-uppercase">
                                    <tr>
                                        <th class="ps-4" width="5%">No</th>
                                        <th width="15%">NIS/Username</th>
                                        <th width="25%">Nama Lengkap Siswa</th>
                                        <th class="pe-4" width="55%">Status Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php foreach ($siswa_absensi as $i => $s): ?>
                                        <tr class="row-siswa" id="row-<?= $s->id_siswa ?>">
                                            <td class="ps-4"><?= $i + 1 ?></td>
                                            <td class="text-muted"><?= esc($s->nis) ?></td>
                                            <td class="fw-bold text-dark"><?= esc($s->nama) ?></td>
                                            <td class="status-cell pe-4">
                                                <label class="radio-status" style="color: #198754;">
                                                    <input type="radio" name="absensi[<?= $s->id_siswa ?>]" value="H"
                                                        <?= ($s->status == 'H') ? 'checked' : '' ?> onclick="highlight(this)">
                                                    <span class="status-text-H uppercase small">Hadir</span>
                                                </label>
                                                <label class="radio-status" style="color: #0d6efd;">
                                                    <input type="radio" name="absensi[<?= $s->id_siswa ?>]" value="S"
                                                        <?= ($s->status == 'S') ? 'checked' : '' ?> onclick="highlight(this)">
                                                    <span class="status-text-S uppercase small">Sakit</span>
                                                </label>
                                                <label class="radio-status" style="color: #ffc107;">
                                                    <input type="radio" name="absensi[<?= $s->id_siswa ?>]" value="I"
                                                        <?= ($s->status == 'I') ? 'checked' : '' ?> onclick="highlight(this)">
                                                    <span class="status-text-I uppercase small">Izin</span>
                                                </label>
                                                <label class="radio-status" style="color: #dc3545;">
                                                    <input type="radio" name="absensi[<?= $s->id_siswa ?>]" value="A"
                                                        <?= ($s->status == 'A') ? 'checked' : '' ?> onclick="highlight(this)">
                                                    <span class="status-text-A uppercase small">Alpha</span>
                                                </label>
                                                <label class="radio-status" style="color: #6c757d;">
                                                    <input type="radio" name="absensi[<?= $s->id_siswa ?>]" value="T"
                                                        <?= ($s->status == 'T') ? 'checked' : '' ?> onclick="highlight(this)">
                                                    <span class="status-text-T uppercase small">Telat</span>
                                                </label>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 border-top text-end bg-light bg-opacity-50">
                            <button type="submit" class="btn-tw btn-success btn-lg rounded-pill shadow px-5 fw-bold">
                                <i class="bi bi-cloud-arrow-up me-2"></i> SIMPAN REKAP ABSENSI
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="bi bi-file-earmark-spreadsheet display-1 opacity-10"></i>
                    <h5 class="mt-3 fw-bold text-muted">Belum Ada Data Terpilih</h5>
                    <p class="text-muted small">Silakan tentukan Tanggal dan Kelas pada filter di atas untuk menginput
                        absensi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function highlight(radio) {
        var flex flex-wrap gap-4 = $(radio).closest('tr');
        flex flex-wrap gap-4.removeClass('bg-hadir bg-sakit bg-izin bg-alpha bg-telat');

        switch (radio.value) {
            case 'H': flex flex-wrap gap-4.addClass('bg-hadir'); break;
            case 'S': flex flex-wrap gap-4.addClass('bg-sakit'); break;
            case 'I': flex flex-wrap gap-4.addClass('bg-izin'); break;
            case 'A': flex flex-wrap gap-4.addClass('bg-alpha'); break;
            case 'T': flex flex-wrap gap-4.addClass('bg-telat'); break;
        }
    }

    $(document).ready(function () {
        $('input[type=radio]:checked').each(function () {
            highlight(this);
        });

        $('#formAbsensi').on('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Simpan Absensi?',
                text: "Pastikan data kehadiran sudah benar hari ini.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                customClass: { confirmButton: 'btn-tw btn-success rounded-pill px-4', cancelButton: 'btn-tw btn-light rounded-pill px-4' }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });
                    $.post('<?= base_url('guru/piket/save_absensi') ?>', $(this).serialize(), function (res) {
                        if (res.status) {
                            Swal.fire('Berhasil', res.message, 'success');
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection(); ?>