<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Content Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-diagram-3"></i> Data Umum
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Data Jurusan') ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul ?? 'Kelola Data Jurusan/Program') ?> · TP <?= esc($tp_active->tahun ?? '-') ?> · Semester <?= esc($smt_active->smt ?? '-') ?></p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-blue-600"><?= esc($judul ?? 'Jurusan') ?></span>
        </nav>
    </div>

    <!-- Card Data Jurusan -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-card-list"></i>
                </span>
                <div>
                    <h4 class="font-extrabold text-slate-900 mb-0">Daftar Jurusan / Program</h4>
                    <p class="text-[11px] text-slate-500 mb-0">Kelola master data jurusan dan kelompok peminatan kurikulum</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs" id="btnReload">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20" id="btnTambah">
                    <i class="bi bi-plus-lg"></i> Tambah Jurusan
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="table-responsive">
                <table id="tableJurusan" class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200/80">
                            <th class="py-3.5 px-4 text-center font-bold w-14">No</th>
                            <th class="py-3.5 px-4 font-bold w-32">Kode Jurusan</th>
                            <th class="py-3.5 px-4 font-bold">Nama Jurusan</th>
                            <th class="py-3.5 px-4 font-bold">Mapel Peminatan</th>
                            <th class="py-3.5 px-4 text-center font-bold w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <!-- Filled by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Jurusan -->
<div id="modalJurusan" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="modalJurusanLabel" aria-hidden="true">
    <div class="modal-box-tw max-w-lg">
            <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between">
                <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="modalJurusanLabel">
                    <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm">
                        <i class="bi bi-diagram-3"></i>
                    </span>
                    <span>Tambah Jurusan</span>
                </h5>
                <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <form id="formJurusan">
                <div class="p-6 space-y-4">
                    <input type="hidden" name="id_jurusan" id="id_jurusan">
                    <?= csrf_field() ?>

                    <div>
                        <label for="kode_jurusan" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kode / Singkatan Jurusan
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="bi bi-tag"></i>
                            </span>
                            <input type="text" class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none" id="kode_jurusan" name="kode_jurusan"
                                placeholder="Contoh: IPA, IPS, TKJ, RPL" maxlength="20">
                        </div>
                        <p class="text-xs text-slate-400 mt-1 mb-0">Kode singkat untuk identifikasi kelas dan raport.</p>
                    </div>

                    <div>
                        <label for="nama_jurusan" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap Jurusan <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="bi bi-card-text"></i>
                            </span>
                            <input type="text" class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none" id="nama_jurusan" name="nama_jurusan" required
                                placeholder="Contoh: Teknik Komputer dan Jaringan">
                        </div>
                    </div>

                    <div>
                        <label for="mapel_peminatan" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Mata Pelajaran Peminatan
                        </label>
                        <select class="w-full form-select-tw" id="mapel_peminatan" name="mapel_peminatan[]" multiple="multiple">
                            <?php foreach ($mapel_peminatan as $mapel): ?>
                                <option value="<?= $mapel['id_mapel'] ?>"><?= esc($mapel['nama_mapel']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1.5 mb-0">
                            <i class="bi bi-info-circle mr-1"></i> Opsional. Pilih mapel yang spesifik dipelajari oleh jurusan ini.
                        </p>
                    </div>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20" id="btnSimpan">
                        <i class="bi bi-check-lg"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
        function escHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function spinSm() {
            return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full mr-1"></span>';
        }
        // =============================================
        // DataTables Initialization
        // =============================================
        var table = $('#tableJurusan').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "<?= site_url('datajurusan/data') ?>",
                type: "POST",
                data: function (d) {
                    d[CSRF_NAME] = CSRF_HASH;
                },
                error: function (xhr, error, code) {
                    console.error('DataTables AJAX Error:', error);
                }
            },
            columns: [
                { data: "no", orderable: false, className: "text-center font-semibold text-slate-400" },
                {
                    data: "kode_jurusan",
                    render: function (data) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">' + escHtml(data || '-') + '</span>';
                    }
                },
                {
                    data: "nama_jurusan",
                    render: function (data) {
                        return '<span class="font-semibold text-slate-800">' + escHtml(data || '-') + '</span>';
                    }
                },
                {
                    data: "mapel_peminatan",
                    orderable: false,
                    render: function (data, type, row) {
                        if (!data || !data.length) return '<span class="text-slate-300 text-xs">–</span>';
                        var chips = data.slice(0, 3).map(function (m) {
                            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 mr-1 mb-1">' + escHtml(m) + '</span>';
                        }).join('');
                        if (data.length > 3) chips += '<span class="text-[10px] font-bold text-slate-400">+' + (data.length - 3) + '</span>';
                        return '<span title="' + escHtml(data.join(', ')) + '">' + chips + '</span>';
                    }
                },
                { data: "aksi", orderable: false, className: "text-center" }
            ],
            drawCallback: function (settings) {
            },
            language: Object.assign({}, window.dt_lang_id || {}, {
                processing: '<div class="inline-flex items-center gap-2 text-blue-600 font-semibold text-xs"><span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-blue-500 border-t-transparent rounded-full" role="status"></span> Memuat data...</div>'
            }),
            responsive: true,
            autoWidth: false,
            order: [[2, 'asc']]
        });

        // =============================================
        // Select2 Initialization (inside modal)
        // =============================================
        function initPeminatanSelect2() {
            if (!$.fn.select2) return;
            var $sel = $('#mapel_peminatan');
            if ($sel.hasClass('select2-hidden-accessible')) return;
            $sel.select2({
                width: '100%',
                placeholder: 'Pilih Mapel Peminatan...',
                dropdownParent: $('#modalJurusan .modal-box-tw'),
                allowClear: true
            });
        }
        $('#modalJurusan').on('app:modal-shown shown', initPeminatanSelect2);

        // =============================================
        // Button: Tambah Jurusan
        // =============================================
        $('#btnTambah').on('click', function () {
            resetForm();
            $('#modalJurusanLabel').html('<span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm"><i class="bi bi-plus-lg"></i></span><span>Tambah Jurusan</span>');
            openModal();
        });

        // =============================================
        // Button: Reload Data
        // =============================================
        $('#btnReload').on('click', function () {
            table.ajax.reload(null, false);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Data berhasil dimuat ulang',
                showConfirmButton: false,
                timer: 1500
            });
        });

        // =============================================
        // Form Submit (Save/Update)
        // =============================================
        $('#formJurusan').on('submit', function (e) {
            e.preventDefault();

            var nama = ($('#nama_jurusan').val() || '').trim();
            if (nama.length < 3) {
                Swal.fire('Perhatian', 'Nama jurusan minimal 3 karakter.', 'warning');
                return;
            }
            var isEdit = $('#id_jurusan').val() !== '';
            var url = isEdit ? '<?= site_url('datajurusan/update') ?>' : '<?= site_url('datajurusan/save') ?>';
            var formData = new FormData(this);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function () {
                    $('#btnSimpan').prop('disabled', true).html(spinSm() + ' Menyimpan...');
                },
                success: function (response) {
                    if (response.status) {
                        closeModal();
                        table.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: response.message
                        });
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan pada server.'
                    });
                },
                complete: function () {
                    $('#btnSimpan').prop('disabled', false).html('<i class="bi bi-check-lg mr-1"></i> Simpan');
                }
            });
        });

        // =============================================
        // Button: Edit Jurusan
        // =============================================
        $(document).on('click', '.btn-edit', function () {
            var id = $(this).data('id');

            $.ajax({
                url: '<?= site_url('datajurusan/get_data/') ?>' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    Swal.fire({
                        title: 'Memuat data...',
                        allowOutsideClick: false,
                        didOpen: function () {
                            Swal.showLoading();
                        }
                    });
                },
                success: function (response) {
                    Swal.close();
                    if (response.status) {
                        var data = response.data;
                        $('#id_jurusan').val(data.id_jurusan);
                        $('#kode_jurusan').val(data.kode_jurusan);
                        $('#nama_jurusan').val(data.nama_jurusan);

                        // Set selected mapel peminatan (string-kan id agar cocok dengan value option)
                        initPeminatanSelect2();
                        if (data.mapel_peminatan && data.mapel_peminatan.length > 0) {
                            var mapelIds = data.mapel_peminatan.map(function (item) {
                                return String(item.id_mapel);
                            });
                            $('#mapel_peminatan').val(mapelIds).trigger('change');
                        } else {
                            $('#mapel_peminatan').val(null).trigger('change');
                        }

                        $('#modalJurusanLabel').html('<span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-sm"><i class="bi bi-pencil"></i></span><span>Edit Jurusan</span>');
                        openModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function (xhr) {
                    Swal.close();
                    console.error(xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat mengambil data.'
                    });
                }
            });
        });

        // =============================================
        // Button: Hapus Jurusan
        // =============================================
        $(document).on('click', '.btn-hapus', function () {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data jurusan ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-trash mr-1"></i> Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= site_url('datajurusan/delete') ?>',
                        type: 'POST',
                        data: {
                            <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                            id_jurusan: id
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.status) {
                                table.ajax.reload(null, false);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: response.message
                                });
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        });

        // =============================================
        // Helper Functions
        // =============================================
        function resetForm() {
            $('#formJurusan')[0].reset();
            $('#id_jurusan').val('');
            $('#mapel_peminatan').val(null).trigger('change');
        }

        function openModal() {
            AdminUI.modal.open('#modalJurusan');
        }

        function closeModal() {
            AdminUI.modal.close('#modalJurusan');
        }
    });
</script>
<?= $this->endSection() ?>