<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-door-open text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Halaman') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($subjudul ?? 'Data') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">
    <div class="card-tw">
        <div class="card-tw-header">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <i class="bi bi-door-open text-emerald-600 text-sm"></i>
                </div>
                <h6>Daftar Kelas/Rombel</h6>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" onclick="reload_ajax()" class="btn-tw btn-tw-sm btn-light" title="Refresh data">
                    <i class="bi bi-arrow-repeat"></i>
                    <span class="hidden sm:inline">Reload</span>
                </button>
                <button type="button" data-ui-toggle="modal" data-ui-target="#modalTambah"
                    class="btn-tw btn-tw-sm btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span class="hidden sm:inline">Tambah Kelas</span>
                </button>
            </div>
        </div>
        <div class="card-tw-body p-0">
                <div class="table-tw">
                    <table id="tableKelas" class="table-tw w-full">
                        <thead>
                            <tr>
                                <th width="40" class="align-middle text-center p-0">No</th>
                                <th class="align-middle">Nama Kelas</th>
                                <th class="align-middle">Kode</th>
                                <th class="align-middle">Level</th>
                                <th class="align-middle">Wali Kelas</th>
                                <th class="align-middle text-center">Jml Siswa</th>
                                <th class="align-middle text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-box-tw max-w-lg" role="document">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5 class="font-bold text-slate-800" id="createModalLabel"><i class="bi bi-plus-circle mr-2"></i>Tambah Kelas</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="formTambah">
                <div class="modal-body-tw">
                    <div class="mb-6">
                        <label class="form-label-tw">Nama Kelas <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_kelas" class="form-control-tw" required>
                    </div>
                    <div class="mb-6">
                        <label class="form-label-tw">Kode Kelas</label>
                        <input type="text" name="kode_kelas" class="form-control-tw">
                    </div>
                    <div class="mb-6">
                        <label class="form-label-tw">Level/Tingkat</label>
                        <select name="level" class="form-select-tw">
                            <option value="10">X (Kelas 10)</option>
                            <option value="11">XI (Kelas 11)</option>
                            <option value="12">XII (Kelas 12)</option>
                        </select>
                    </div>
                    <div class="mb-6">
                        <label class="form-label-tw">Wali Kelas</label>
                        <select name="wali_kelas" class="form-select-tw">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($gurus ?? [] as $g):
                                if (!is_object($g))
                                    continue; ?>
                                <option value="<?= $g->id_guru ?>">
                                    <?= esc($g->nama_guru) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="reset" class="btn-tw btn-light">
                        <i class="bi bi-arrow-repeat mr-1"></i> Reset
                    </button>
                    <button type="submit" class="btn-tw btn-primary">
                        <i class="bi bi-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const csrfToken = '<?= csrf_hash() ?>';

    $(document).ready(function () {
        $('#tableKelas').DataTable({
            ajax: '<?= site_url('datakelas/data') ?>',
            columns: [
                { data: 'no', className: 'text-center' },
                { data: 'nama_kelas' },
                { data: 'kode_kelas' },
                { data: 'level' },
                { data: 'wali_kelas' },
                { data: 'jumlah', className: 'text-center', render: d => `<span class="badge-tw badge-primary">${d}</span>` },
                {
                    data: 'id_kelas',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: d => `
                        <div class="flex justify-center gap-1">
                            <button class="btn-tw btn-tw-sm btn-info" title="Edit">
                                <i class="bi bi-pencil text-white"></i>
                            </button>
                            <button class="btn-tw btn-tw-sm btn-danger" onclick="deleteKelas(${d})" title="Hapus">
                                <i class="bi bi-trash text-white"></i>
                            </button>
                        </div>
                    `
                }
            ],
            order: [[1, "asc"]],
            language: dt_lang_id
        });
    });

    function reload_ajax() {
        $('#tableKelas').DataTable().ajax.reload();
    }

    $('#formTambah').on('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('<?= csrf_token() ?>', csrfToken);

        const res = await fetch('<?= site_url('datakelas/save') ?>', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.status) {
            Swal.fire('Berhasil', data.message, 'success');
            $('#modalTambah').appModal('hide');
            location.reload();
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    });
</script>
<?= $this->endSection() ?>