<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header bg-white">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1">
                <h1 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5 m-0">
                    <span class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-xs shrink-0">
                        <i class="bi bi-arrow-left-right text-xl"></i>
                    </span>
                    <span><?= esc($judul) ?></span>
                </h1>
            </div>
            <div class="flex-1">
                <a href="<?= site_url('datakelas') ?>" class="btn-tw btn-tw-sm btn-outline-secondary self-start">
                    <i class="bi bi-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

<div class="content-area">
<div class="card-tw"><div class="card-tw-header">
                    <h6 class="mb-0 font-bold">
                        <?= esc($subjudul) ?>
                    </h6>
                </div>
                <div class="card-tw-body">
                    <div class="alert-tw alert-info">
                        <h6 class="font-bold"><i class="bi bi-info-circle mr-2"></i>Fitur Salin Semester</h6>
                        <p class="mb-2">Fitur ini digunakan untuk menyalin semua kelas dan siswa TP: <b>
                                <?= $tp_active->tahun ?>
                            </b> dari Semester 1 ke Semester 2.</p>
                        <ul class="mb-0 text-sm">
                            <li>Hanya kelas yang <b>BELUM ADA</b> di semester 2 yang akan ditampilkan untuk disalin.
                            
                            <li>Pilih kelas yang akan disalin dengan mencentang kotak di kolom Copy.
                            <li>Klik tombol <b>Simpan</b> untuk mulai proses penyalinan data.
                        </ul>
                    </div>

                    <?php if (count($kelas) == 0): ?>
                        <div class="alert-tw alert-info">
                            <i class="bi bi-exclamation-triangle display-4"></i>
                            <p class="mt-2 mb-0">Tidak ada data kelas di Semester 1 yang dapat disalin.</p>
                        </div>
                    <?php else: ?>
                        <form id="managekelas">
                            <?= csrf_field() ?>
                            <div class="flex justify-end mb-6">
                                <button type="submit" class="btn-tw btn-primary" id="save-all" disabled>
                                    <i class="bi bi-save mr-1"></i> Simpan
                                </button>
                            </div>

                            <div class="table-responsive-tw"><table class="table-tw">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-center align-middle" style="width: 50px">No</th>
                                            <th class="align-middle px-4">Kelas Semester 1</th>
                                            <th class="align-middle px-4">Target Semester 2</th>
                                            <th class="align-middle px-4">Status</th>
                                            <th class="text-center align-middle" style="width: 80px">
                                                Copy<br>
                                                <input class="form-check-input mt-1" type="checkbox" id="check-all">
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <?php $no = 1;
                                        foreach ($kelas as $key => $kls): ?>
                                            <tr>
                                                <td class="text-center align-middle">
                                                    <?= $no++ ?>
                                                </td>
                                                <td class="align-middle px-4 font-semibold">
                                                    <?= $kls ?>
                                                </td>
                                                <td class="align-middle px-4">
                                                    <?= $kls ?>
                                                </td>
                                                <td class="align-middle px-4">
                                                    <?php if (in_array($kls, $kelas2)): ?>
                                                        <span class="badge-tw badge-success"><i
                                                                class="bi bi-check-circle mr-1"></i> Sudah Ada</span>
                                                    <?php else: ?>
                                                        <span class="badge-tw badge-danger"><i
                                                                class="bi bi-x-circle mr-1"></i> Belum Ada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <?php if (!in_array($kls, $kelas2)): ?>
                                                        <input type="checkbox" name="kelas_satu[]" value="<?= $key ?>"
                                                            class="form-check-input check-kelas">
                                                    <?php else: ?>
                                                        <i class="bi bi-check-lg text-emerald-600 text-xl"></i>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const base_url = '<?= base_url() ?>/';
    $(document).ready(function () {
        $("#check-all").on("click", function () {
            $(".check-kelas").prop('checked', this.checked);
            updateSaveButton();
        });

        $("#table-body").on("change", ".check-kelas", function () {
            let allChecked = $(".check-kelas").length === $(".check-kelas:checked").length;
            $("#check-all").prop('checked', allChecked);
            $("#check-all").prop('indeterminate', $(".check-kelas:checked").length > 0 && !allChecked);
            updateSaveButton();
        });

        function updateSaveButton() {
            $("#save-all").prop('disabled', $(".check-kelas:checked").length === 0);
        }

        $('#managekelas').submit(function (e) {
            e.preventDefault();

            Swal.fire({
                title: "Salin Kelas?",
                text: "Kelas yang dipilih beserta daftar siswanya akan disalin ke Semester 2.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, Salin!"
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sedang menyalin...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: base_url + 'datakelas/copyallfromsmt1',
                        type: "POST",
                        data: $(this).serialize(),
                        success: function (data) {
                            if (data.success) {
                                Swal.fire("Berhasil", data.insert + " Kelas berhasil disalin", "success").then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire("Gagal", "Gagal menyalin data", "error");
                            }
                        },
                        error: function (xhr) {
                            Swal.fire("Error", "Gagal menghubungi server", "error");
                        }
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>