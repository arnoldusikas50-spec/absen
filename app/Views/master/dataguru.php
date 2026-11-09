<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-person-video3 text-xl"></i>
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
        <!-- Welcome Banner -->
        <div class="welcome-banner mb-6 border-0 rounded-xl overflow-hidden relative">
            <div class="flex flex-wrap gap-4" style="z-index: 2;">
                <div class="flex-1 p-4 p-md-5">
                    <h1 class="display-6 font-bold text-slate-800 mb-2">Master Data Guru 👨‍🏫</h1>
                    <p class="lead text-slate-800-50 mb-0 opacity-75">
                        Kelola informasi tenaga pendidik, termasuk data pribadi dan kontak secara terpusat.
                    </p>
                </div>
                <div class="">
                    <i class="bi bi-person-badge-fill text-blue-600" style="font-size: 8rem;"></i>
                </div>
            </div>
        </div>

        <div class="card-tw"><div class="card-tw-header"><div class="flex items-center">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="bi bi-people-fill text-blue-600"></i>
                    </div>
                    <h5 class="font-bold text-slate-700">Daftar Guru</h5>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" onclick="reload_ajax()" class="btn-tw btn-tw-sm btn-light">
                        <i class="bi bi-arrow-repeat mr-1"></i> Reload
                    </button>
                    <button type="button" data-ui-toggle="modal" data-ui-target="#modalTambah"
                        class="btn-tw btn-tw-sm btn-primary">
                        <i class="bi bi-plus-lg mr-1"></i> Tambah Guru
                    </button>
                </div>
            </div>
            <div class="card-tw-body">
                <div class="table-tw">
                    <table id="tableGuru" class="table-tw">
                        <thead class="table-tw">
                            <tr>
                                <th width="40" class="text-center py-4">No</th>
                                <th class="py-4">Nama Guru</th>
                                <th class="py-4">NIP</th>
                                <th class="py-4">NUPTK</th>
                                <th class="py-4">Email</th>
                                <th class="py-4">No HP</th>
                                <th class="text-center py-4" width="100">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Create -->
        <div id="modalTambah" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
            <div class="modal-box-tw max-w-3xl">
                <div class="bg-white">
                    <div class="modal-header-tw">
                        <h5 class="font-bold text-slate-800" id="createModalLabel">
                            <i class="bi bi-person-plus-fill mr-2"></i>Tambah Guru Baru
                        </h5>
                        <button type="button" class="btn-close" data-ui-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form id="formTambah">
                        <div class="modal-body-tw">
                            <div class="grid grid-cols-1 gap-4">
                                <div class="">
                                    <label class="form-label-tw">Nama
                                        Guru*</label>
                                    <div class="input-group-tw">
                                        <span class="input-group-tw"><i
                                                class="bi bi-person text-slate-400"></i></span>
                                        <input type="text" name="nama_guru"
                                            class="form-control-tw" required
                                            placeholder="Nama lengkap & gelar">
                                    </div>
                                </div>
                                <div class="">
                                    <label class="form-label-tw">NIP</label>
                                    <input type="text" name="nip"
                                        class="form-control-tw"
                                        placeholder="Nomor Induk Pegawai">
                                </div>
                                <div class="">
                                    <label class="form-label-tw">NUPTK</label>
                                    <input type="text" name="nuptk"
                                        class="form-control-tw" placeholder="NUPTK">
                                </div>
                                <div class="">
                                    <label class="form-label-tw">Jenis
                                        Kelamin</label>
                                    <select name="jenis_kelamin"
                                        class="form-select-tw">
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="">
                                    <label class="form-label-tw">Email</label>
                                    <div class="input-group-tw">
                                        <span class="input-group-tw"><i
                                                class="bi bi-envelope text-slate-400"></i></span>
                                        <input type="email" name="email" class="form-control-tw"
                                            placeholder="example@mail.com">
                                    </div>
                                </div>
                                <div class="">
                                    <label class="form-label-tw">No HP</label>
                                    <div class="input-group-tw">
                                        <span class="input-group-tw"><i
                                                class="bi bi-telephone text-slate-400"></i></span>
                                        <input type="text" name="no_hp" class="form-control-tw"
                                            placeholder="08xxxxxx">
                                    </div>
                                </div>
                                <div class="">
                                    <label class="form-label-tw">Tempat
                                        Lahir</label>
                                    <input type="text" name="tempat_lahir"
                                        class="form-control-tw">
                                </div>
                                <div class="">
                                    <label class="form-label-tw">Tanggal
                                        Lahir</label>
                                    <input type="date" name="tanggal_lahir"
                                        class="form-control-tw">
                                </div>
                                <div class="w-full">
                                    <label class="form-label-tw">Alamat
                                        Lengkap</label>
                                    <textarea name="alamat" class="form-control-tw"
                                        rows="3" placeholder="Alamat tinggal saat ini"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer-tw">
                            <button type="button"
                                class="btn-tw btn-light"
                                data-ui-dismiss="modal">BATAL</button>
                            <button type="submit" class="btn-tw btn-primary">
                                <i class="bi bi-save mr-2"></i>SIMPAN DATA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>

        <?= $this->endSection() ?>

        <?= $this->section('scripts') ?>
        <script>
            const csrfToken = '<?= csrf_hash() ?>';
            let table;

            $(document).ready(function () {
                table = $('#tableGuru').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: '<?= site_url('dataguru/data') ?>',
                    columns: [
                        { data: 'no', className: 'text-center' },
                        { data: 'nama_guru', className: 'uppercase' },
                        { data: 'nip' },
                        { data: 'nuptk' },
                        { data: 'email' },
                        { data: 'no_hp' },
                        {
                            data: 'id_guru',
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function (data) {
                                return `
                            <div class="flex justify-center gap-1">
                                <button class="btn-tw btn-tw-sm btn-info" title="Edit">
                                    <i class="bi bi-pencil text-white"></i>
                                </button>
                                <button class="btn-tw btn-tw-sm btn-danger" onclick="deleteGuru(${data})" title="Hapus">
                                    <i class="bi bi-trash text-white"></i>
                                </button>
                            </div>
                        `;
                            }
                        }
                    ],
                    order: [[1, "asc"]],
                    language: dt_lang_id
                });
            });

            $('#formTambah').on('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('<?= csrf_token() ?>', csrfToken);

                const res = await fetch('<?= site_url('dataguru/save') ?>', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.status) {
                    Swal.fire('Berhasil', data.message, 'success');
                    $('#modalTambah').appModal('hide');
                    this.reset();
                    table.ajax.reload();
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            });

            async function deleteGuru(id) {
                const result = await Swal.fire({
                    title: 'Hapus Guru?', icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
                });
                if (!result.isConfirmed) return;

                const formData = new FormData();
                formData.append('id_guru', id);
                formData.append('<?= csrf_token() ?>', csrfToken);

                const res = await fetch('<?= site_url('dataguru/delete') ?>', { method: 'POST', body: formData });
                const data = await res.json();

                data.status ? Swal.fire('Berhasil', data.message, 'success') : Swal.fire('Gagal', data.message, 'error');
                table.ajax.reload();
            }

            function reload_ajax() {
                table.ajax.reload();
            }
        </script>
        <?= $this->endSection() ?>