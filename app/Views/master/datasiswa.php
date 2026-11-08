<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<!-- DataTables specific styles can go here if needed, but they are mostly handled by partials -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-people-fill text-xl"></i>
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
    <div class="w-full px-2">
        <div class="card-tw"><div class="card-tw-header">
                <h3 class="font-bold text-slate-700"><i class="bi bi-people mr-2"></i>Daftar Siswa</h3>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" onclick="reload_ajax()" class="btn-tw btn-tw-sm btn-light">
                        <i class="bi bi-arrow-repeat mr-1"></i> Reload
                    </button>
                    <button type="button" data-ui-toggle="modal" data-ui-target="#modalTambah"
                        class="btn-tw btn-tw-sm btn-primary">
                        <i class="bi bi-plus mr-1"></i> Tambah Siswa
                    </button>
                </div>
            </div>
            <div class="card-tw-body">
                <div class="table-tw">
                    <table id="tableSiswa" class="table-tw w-full">
                        <thead>
                            <tr>
                                <th width="40" class="align-middle text-center p-0">No</th>
                                <th class="align-middle">Nama</th>
                                <th class="align-middle">NIS</th>
                                <th class="align-middle">NISN</th>
                                <th class="align-middle">Kelas</th>
                                <th class="align-middle text-center">L/P</th>
                                <th class="align-middle text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create -->
<div id="modalTambah" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-box-tw max-w-3xl" role="document">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5 class="font-bold text-slate-800" id="createModalLabel"><i class="bi bi-plus-circle mr-2"></i>Tambah Siswa</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="formTambah">
                <div class="modal-body-tw">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="">
                            <label class="form-label-tw">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama" class="form-control-tw" required>
                        </div>
                        <div class="">
                            <label class="form-label-tw">NIS <span class="text-red-500">*</span></label>
                            <input type="text" name="nis" class="form-control-tw" required>
                        </div>
                        <div class="">
                            <label class="form-label-tw">NISN</label>
                            <input type="text" name="nisn" class="form-control-tw">
                        </div>
                        <div class="">
                            <label class="form-label-tw">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select-tw">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="">
                            <label class="form-label-tw">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control-tw">
                        </div>
                        <div class="">
                            <label class="form-label-tw">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control-tw">
                        </div>
                        <div class="">
                            <label class="form-label-tw">Agama</label>
                            <select name="agama" class="form-select-tw">
                                <option value="1">Islam</option>
                                <option value="2">Kristen</option>
                                <option value="3">Katolik</option>
                                <option value="4">Hindu</option>
                                <option value="5">Buddha</option>
                                <option value="6">Konghucu</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="form-label-tw">Username</label>
                            <input type="text" name="username" class="form-control-tw"
                                <span class="sep">/</span>
                                <span class="active">placeholder="Kosongkan untuk auto dari NIS"></span>
                                </nav>
                        </div>
                        <div class="w-full">
                            <label class="form-label-tw">Alamat</label>
                            <textarea name="alamat" class="form-control-tw" rows="2"></textarea>
                        </div>
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

<!-- Modal Edit -->
<div id="modalEdit" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw max-w-3xl">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5 class="font-bold text-slate-800"><i class="bi bi-pencil-square mr-2"></i>Edit Siswa</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                <input type="hidden" name="id_siswa" id="editId">
                <div class="modal-body-tw">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="">
                            <label class="form-label-tw">Nama Lengkap</label>
                            <input type="text" name="nama" id="editNama" class="form-control-tw" required>
                        </div>
                        <div class="">
                            <label class="form-label-tw">NIS</label>
                            <input type="text" name="nis" id="editNis" class="form-control-tw" required>
                        </div>
                        <div class="">
                            <label class="form-label-tw">NISN</label>
                            <input type="text" name="nisn" id="editNisn" class="form-control-tw">
                        </div>
                        <div class="">
                            <label class="form-label-tw">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="editJk" class="form-select-tw">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="">
                            <label class="form-label-tw">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="editTempat" class="form-control-tw">
                        </div>
                        <div class="">
                            <label class="form-label-tw">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="editTanggal" class="form-control-tw">
                        </div>
                        <div class="w-full">
                            <label class="form-label-tw">Alamat</label>
                            <textarea name="alamat" id="editAlamat" class="form-control-tw" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary">
                        <i class="bi bi-save mr-1"></i>Update
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
    let table;

    $(document).ready(function () {
        table = $('#tableSiswa').DataTable({
            processing: true,
            serverSide: true,
            ajax: '<?= site_url('datasiswa/data') ?>',
            columns: [
                { data: 'no' },
                { data: 'nama' },
                { data: 'nis' },
                { data: 'nisn' },
                { data: 'kelas' },
                { data: 'jk' },
                {
                    data: 'id_siswa',
                    render: function (data, type, row) {
                        return `
                            <button class="btn-tw btn-tw-sm btn-warning" onclick="editSiswa(${data})">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn-tw btn-tw-sm btn-danger" onclick="deleteSiswa(${data})">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }
            ],
            language: dt_lang_id
        });
    });

    // Tambah
    $('#formTambah').on('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('<?= csrf_token() ?>', csrfToken);

        try {
            const res = await fetch('<?= site_url('datasiswa/save') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status) {
                Swal.fire('Berhasil', data.message, 'success');
                $('#modalTambah').appModal('hide');
                this.reset();
                table.ajax.reload();
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan', 'error');
        }
    });

    // Edit
    function editSiswa(id) {
        // Ideally fetch data from server, for now use table data
        const rowData = table.rows().data().toArray().find(r => r.id_siswa == id);
        if (rowData) {
            $('#editId').val(rowData.id_siswa);
            $('#editNama').val(rowData.nama);
            $('#editNis').val(rowData.nis);
            $('#editNisn').val(rowData.nisn);
            $('#modalEdit').appModal('show');
        }
    }

    $('#formEdit').on('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('<?= csrf_token() ?>', csrfToken);

        try {
            const res = await fetch('<?= site_url('datasiswa/update') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status) {
                Swal.fire('Berhasil', data.message, 'success');
                $('#modalEdit').appModal('hide');
                table.ajax.reload();
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan', 'error');
        }
    });

    // Delete
    async function deleteSiswa(id) {
        const result = await Swal.fire({
            title: 'Hapus Siswa?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        });

        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('id_siswa', id);
        formData.append('<?= csrf_token() ?>', csrfToken);

        try {
            const res = await fetch('<?= site_url('datasiswa/delete') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status) {
                Swal.fire('Berhasil', data.message, 'success');
                table.ajax.reload();
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan', 'error');
        }
    }
</script>
<?= $this->endSection() ?>