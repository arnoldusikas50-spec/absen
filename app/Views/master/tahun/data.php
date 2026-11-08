<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .btn-action-icon {
        transition: all 0.15s ease;
    }
    .btn-action-icon:hover {
        transform: scale(1.08);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$tpActiveYear = $tp_active ? $tp_active->tahun : 'Belum Diatur';
$smtActiveName = $smt_active ? $smt_active->smt : 'Belum Diatur';

// Build safe JSON payloads for AJAX activation
$tpJsonData = json_encode(array_map(function($t) {
    return ['id' => $t->id_tp, 'tp' => $t->tahun];
}, $tp));

$smtJsonData = json_encode(array_map(function($s) {
    return ['id' => $s->id_smt, 'Semester' => $s->smt];
}, $smt));
?>

<div class="fade-in-up">
    <!-- Header & Breadcrumb -->
    <div class="px-6 pt-6 pb-2">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                    <i class="bi bi-calendar2-range-fill text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight">Tahun Pelajaran & Semester</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium">
                        Kelola periode kalender akademik aktif dan jumlah hari efektif pembelajaran
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <nav aria-label="breadcrumb">
                    <ol class="flex items-center gap-2 text-xs font-semibold bg-white/80 px-3.5 py-2 rounded-xl border border-slate-200/80 shadow-sm">
                        <li><a href="<?= site_url('dashboard') ?>" class="text-slate-500 hover:text-blue-600 transition-colors">Dashboard</a></li>
                        <li class="text-slate-300">/</li>
                        <li class="text-slate-400">Data Umum</li>
                        <li class="text-slate-300">/</li>
                        <li class="text-blue-600">Tahun Pelajaran</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Info Notice Box -->
    <div class="px-6 pt-4">
        <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <i class="bi bi-info-circle-fill text-sm"></i>
                </div>
                <div class="text-xs text-slate-600 leading-relaxed">
                    <b class="text-blue-950">Petunjuk Penting:</b> Mengubah tahun pelajaran atau semester aktif akan mempengaruhi data kelas, jadwal pelajaran, presensi harian, dan pelaporan semester yang tampil di sistem. Periode yang sedang aktif dilindungi dan tidak dapat dihapus.
                </div>
            </div>
            <div class="shrink-0">
                <button type="button" onclick="location.reload()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-200 text-blue-700 text-xs font-bold rounded-xl hover:bg-blue-50 transition-colors shadow-2xs">
                    <i class="bi bi-arrow-clockwise"></i> Segarkan Halaman
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content: Two Balanced Cards -->
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Card 1: Kelola Tahun Pelajaran (7 Cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col justify-between">
                <div>
                    <!-- Header -->
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-calendar3 text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-800">Daftar Tahun Pelajaran</h2>
                                <p class="text-xs text-slate-400">Pilih tahun pelajaran aktif untuk operasional sekolah</p>
                            </div>
                        </div>

                        <button type="button" data-from="add" data-ui-toggle="modal" data-ui-target="#createTahunModal"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                            <i class="bi bi-plus-lg text-sm"></i> Tambah Periode
                        </button>
                    </div>

                    <!-- Table Tahun Pelajaran -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="tahunTable">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[11px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3.5 text-center" style="width: 50px;">No</th>
                                    <th class="px-4 py-3.5">Tahun Pelajaran</th>
                                    <th class="px-4 py-3.5 text-center" style="width: 140px;">Status</th>
                                    <th class="px-4 py-3.5 text-center" style="width: 110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs bg-white">
                                <?php foreach ($tp as $key => $value): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors <?= $value->active ? 'bg-blue-50/20' : '' ?>">
                                        <td class="px-4 py-3.5 text-center font-bold text-slate-400">
                                            <?= $key + 1 ?>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-extrabold text-sm text-slate-800 tracking-tight">
                                                    <?= esc($value->tahun) ?>
                                                </span>
                                                <?php if ($value->active): ?>
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700">
                                                        Aktif
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <?php if ($value->active): ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <button type="button" 
                                                        data-id="<?= $value->id_tp ?>" 
                                                        data-name="<?= esc($value->tahun) ?>"
                                                        class="btn-aktif-tp inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-slate-100 hover:bg-blue-600 text-slate-700 hover:text-white transition-all border border-slate-200 hover:border-blue-600 shadow-2xs">
                                                    <i class="bi bi-check2"></i> Aktifkan
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <div class="inline-flex items-center gap-1.5">
                                                <!-- Edit button -->
                                                <button type="button" 
                                                        data-id="<?= $value->id_tp ?>"
                                                        data-tahun="<?= esc($value->tahun) ?>" 
                                                        data-from="edit"
                                                        data-ui-toggle="modal" 
                                                        data-ui-target="#createTahunModal"
                                                        class="btn-action-icon w-8 h-8 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-600 flex items-center justify-center transition-colors shadow-2xs"
                                                        title="Edit Periode">
                                                    <i class="bi bi-pencil-fill text-xs"></i>
                                                </button>

                                                <!-- Delete button -->
                                                <?php if ($value->active): ?>
                                                    <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-300 flex items-center justify-center cursor-not-allowed" title="Tahun aktif tidak dapat dihapus">
                                                        <i class="bi bi-trash text-xs"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            data-id="<?= $value->id_tp ?>" 
                                                            data-name="<?= esc($value->tahun) ?>"
                                                            class="btn-hapus-tp btn-action-icon w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors shadow-2xs"
                                                            title="Hapus Periode">
                                                        <i class="bi bi-trash-fill text-xs"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50/50 text-xs text-slate-400 text-center">
                    Klik tombol <b>Aktifkan</b> pada baris tahun untuk mengganti tahun pelajaran yang sedang berjalan
                </div>
            </div>

            <!-- Card 2: Kelola Semester & Hari Efektif (5 Cols) -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Semester Box -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-clock-history text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Daftar Semester</h2>
                            <p class="text-xs text-slate-400">Pilih semester berjalan (Ganjil / Genap)</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="semesterTable">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[11px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3.5 text-center" style="width: 50px;">No</th>
                                    <th class="px-4 py-3.5">Nama Semester</th>
                                    <th class="px-4 py-3.5 text-center" style="width: 140px;">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs bg-white">
                                <?php foreach ($smt as $key => $value): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors <?= $value->active ? 'bg-indigo-50/20' : '' ?>">
                                        <td class="px-4 py-3.5 text-center font-bold text-slate-400">
                                            <?= $key + 1 ?>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="font-extrabold text-sm text-slate-800 tracking-tight">
                                                <?= esc($value->smt) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <?php if ($value->active): ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <button type="button" 
                                                        data-id="<?= $value->id_smt ?>" 
                                                        data-name="<?= esc($value->smt) ?>"
                                                        class="btn-aktif-smt inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-slate-100 hover:bg-indigo-600 text-slate-700 hover:text-white transition-all border border-slate-200 hover:border-indigo-600 shadow-2xs">
                                                    <i class="bi bi-check2"></i> Aktifkan
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Hari Efektif Belajar (HEB) -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6" id="formHariEfektif">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-4">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-calculator-fill text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Hari Efektif Semester Ini</h2>
                            <p class="text-xs text-slate-400">TP <?= esc($tpActiveYear) ?> &middot; <?= esc($smtActiveName) ?></p>
                        </div>
                    </div>

                    <form id="formHeb">
                        <?= csrf_field() ?>
                        <div class="space-y-3">
                            <div>
                                <label for="inputJmlHari" class="block text-xs font-bold text-slate-700 mb-1">
                                    Jumlah Hari Efektif Pembelajaran
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                           id="inputJmlHari" 
                                           name="jml_hari" 
                                           value="<?= (int)($jml_hari ?? 0) ?>" 
                                           min="1" 
                                           max="365"
                                           required 
                                           class="w-full pl-4 pr-14 py-2.5 text-sm font-extrabold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                                        Hari
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Total hari efektif belajar resmi yang digunakan untuk menghitung persentase kehadiran guru & siswa pada semester aktif.
                                </p>
                            </div>

                            <button type="submit" id="btnSaveHeb" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                <i class="bi bi-save2-fill"></i> Simpan Hari Efektif
                            </button>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Tahun Pelajaran -->
<div id="createTahunModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-box-tw max-w-md" role="document">
        <div class="bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-100">
            <form id="createForm">
                <?= csrf_field() ?>
                
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between" id="modalHeader">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center" id="modalIcon">
                            <i class="bi bi-calendar-plus-fill text-sm"></i>
                        </div>
                        <h5 class="font-bold text-slate-800 text-sm" id="createModalLabel">Tambah Tahun Pelajaran</h5>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none" data-ui-dismiss="modal" aria-label="Close">
                        &times;
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="createtahun">
                            Periode Tahun Pelajaran <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="createtahun" 
                               name="tahun" 
                               class="w-full px-4 py-2.5 text-sm font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all placeholder:text-slate-400" 
                               required
                               placeholder="Format: YYYY/YYYY (contoh: 2025/2026)">
                        <p class="text-[11px] text-slate-400 mt-1">
                            Gunakan format 4 digit tahun garis miring 4 digit tahun berikutnya.
                        </p>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-2">
                    <input type="hidden" id="editIdTahun" name="id_tahun">
                    <input type="hidden" id="method" name="method" value="add">
                    
                    <button type="button" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-colors" data-ui-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5" id="submit-tp">
                        <i class="bi bi-check-lg"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Data list from backend for reliable serialization
    const rawTpData = <?= $tpJsonData ?>;
    const rawSmtData = <?= $smtJsonData ?>;

    $(document).ready(function () {
        // Ganti Tahun Pelajaran Aktif
        $(document).on("click", ".btn-aktif-tp", function () {
            let id = $(this).data("id");
            let name = $(this).data("name");

            Swal.fire({
                title: "Ganti Tahun Pelajaran?",
                text: "Aktifkan tahun pelajaran " + name + " sebagai periode berjalan?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#2563eb",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Aktifkan",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        text: "Menyimpan perubahan...",
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    $.post(base_url + "datatahun/gantitahun", {
                        active: id,
                        tahun: JSON.stringify(rawTpData),
                        [CSRF_NAME]: CSRF_HASH
                    }, function (res) {
                        Swal.fire({
                            title: res.status ? "Berhasil" : "Gagal",
                            text: res.msg,
                            icon: res.status ? "success" : "error"
                        }).then(() => { if (res.status) location.reload(); });
                    }).fail(function() {
                        Swal.fire("Gagal", "Terjadi kesalahan pada server", "error");
                    });
                }
            });
        });

        // Ganti Semester Aktif
        $(document).on("click", ".btn-aktif-smt", function () {
            let id = $(this).data("id");
            let name = $(this).data("name");

            Swal.fire({
                title: "Ganti Semester Aktif?",
                text: "Aktifkan " + name + " sebagai semester berjalan?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#4f46e5",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Aktifkan",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        text: "Menyimpan perubahan...",
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    $.post(base_url + "datatahun/gantisemester", {
                        active: id,
                        semester: JSON.stringify(rawSmtData),
                        [CSRF_NAME]: CSRF_HASH
                    }, function (res) {
                        Swal.fire({
                            title: res.status ? "Berhasil" : "Gagal",
                            text: res.msg,
                            icon: res.status ? "success" : "error"
                        }).then(() => { if (res.status) location.reload(); });
                    }).fail(function() {
                        Swal.fire("Gagal", "Terjadi kesalahan pada server", "error");
                    });
                }
            });
        });

        // Modal Create / Edit Tahun Pelajaran
        $('#createTahunModal').on('show.bs.modal', function (e) {
            let btn = $(e.relatedTarget);
            let method = btn.data('from') || 'add';
            $('#method').val(method);

            if (method === 'edit') {
                $('#modalIcon').html('<i class="bi bi-pencil-fill text-sm"></i>');
                $('#createModalLabel').text('Edit Tahun Pelajaran');
                $('#editIdTahun').val(btn.data('id'));
                $('#createtahun').val(btn.data('tahun'));
            } else {
                $('#modalIcon').html('<i class="bi bi-calendar-plus-fill text-sm"></i>');
                $('#createModalLabel').text('Tambah Tahun Pelajaran');
                $('#editIdTahun').val('');
                $('#createtahun').val('');
            }
        });

        // Submit Form Tambah / Edit Tahun
        $('#createForm').on('submit', function (e) {
            e.preventDefault();
            let submitBtn = $('#submit-tp');
            submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

            $.post(base_url + "datatahun/add", $(this).serialize(), function (res) {
                if (res.status) {
                    location.reload();
                } else {
                    Swal.fire("Gagal", "Gagal memproses data tahun pelajaran", "error");
                    submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Simpan');
                }
            }).fail(function() {
                Swal.fire("Gagal", "Terjadi kesalahan pada server", "error");
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Simpan');
            });
        });

        // Hapus Tahun Pelajaran
        $(document).on("click", ".btn-hapus-tp", function () {
            let id = $(this).data("id");
            let name = $(this).data("name");

            Swal.fire({
                title: 'Hapus Tahun Pelajaran?',
                text: 'Hapus periode ' + name + '? Data yang berhubungan akan ikut terpengaruh.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: "#e11d48",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        text: "Menghapus data...",
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    $.post(base_url + "datatahun/hapustahun", { 
                        hapus: id, 
                        [CSRF_NAME]: CSRF_HASH 
                    }, function (res) {
                        Swal.fire(res.status ? 'Berhasil' : 'Gagal', res.msg, res.status ? 'success' : 'error')
                            .then(() => { if (res.status) location.reload(); });
                    }).fail(function() {
                        Swal.fire("Gagal", "Terjadi kesalahan pada server", "error");
                    });
                }
            });
        });

        // Simpan Hari Efektif Belajar (HEB)
        $('#formHeb').on('submit', function (e) {
            e.preventDefault();
            let btn = $('#btnSaveHeb');
            let origHtml = btn.html();
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

            $.post(base_url + "datatahun/saveHariEfektif", $(this).serialize(), function (res) {
                btn.prop('disabled', false).html(origHtml);
                if (res.status) {
                    Swal.fire({
                        title: "Tersimpan",
                        text: "Jumlah hari efektif pembelajaran berhasil diperbarui",
                        icon: "success",
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => { location.reload(); });
                } else {
                    Swal.fire("Gagal", "Gagal menyimpan hari efektif", "error");
                }
            }).fail(function() {
                btn.prop('disabled', false).html(origHtml);
                Swal.fire("Gagal", "Terjadi kesalahan pada server", "error");
            });
        });
    });
</script>
<?= $this->endSection() ?>