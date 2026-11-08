<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$searchId = array_search('1', array_column($tp, 'active'));
?>

<div class="content-header bg-white">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1">
                <h1 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs shrink-0">
                        <i class="bi bi-box-arrow-up-right text-xl"></i>
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
                    <h6 class="mb-0 font-bold"><?= esc($subjudul) ?></h6>
                </div>
                <div class="card-tw-body">
                    <?php if ($tpBefore): ?>
                        <div class="alert-tw alert-info">
                            <h6 class="font-bold"><i class="bi bi-info-circle mr-2"></i>Fitur Kenaikan Kelas</h6>
                            <p class="mb-2">Gunakan fitur ini untuk menaikkan siswa dari Tahun Pelajaran:
                                <b><?= $tpBefore->tahun ?></b> ke <b><?= $tp_active->tahun ?></b>.</p>
                            <ul class="mb-0 text-sm">
                                <li>Pilih kelas asal dari TP sebelumnya.
                                <li>Pilih mode kenaikan (Persiswa, Perkelas, atau Semua Kelas).
                                <li>Siswa yang di rapor ditandai "Tidak Naik" akan tetap di kelas lamanya secara otomatis.
                                
                            </ul>
                        </div>

                        <div class="flex flex-wrap gap-4">
                            <div class="">
                                <label class="form-label-tw">Kelas TP <?= $tpBefore->tahun ?></label>
                                <select id="opsi-kelas-lama" class="form-select-tw">
                                    <option value="" disabled <?= empty($kelas_selected) ? 'selected' : '' ?>>Pilih Kelas
                                        Asal...</option>
                                    <?php foreach ($kelas_lama as $id => $name): ?>
                                        <option value="<?= $id ?>" <?= $id == $kelas_selected ? 'selected' : '' ?>><?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if (!empty($siswas) && !empty($kelases)): ?>
                                <div class="flex-1 text-right">
                                    <label class="form-label-tw">Mode Kenaikan</label>
                                    <div id="mode-selector" class="btn-group shadow-sm">
                                        <button type="button"
                                            class="btn-tw btn-outline-primary <?= ($type_selected ?? 'persiswa') == 'persiswa' ? 'active' : '' ?>"
                                            data-mode="persiswa">Persiswa</button>
                                        <button type="button"
                                            class="btn-tw btn-outline-primary <?= ($type_selected ?? '') == 'perkelas' ? 'active' : '' ?>"
                                            data-mode="perkelas">Perkelas</button>
                                        <button type="button"
                                            class="btn-tw btn-outline-primary <?= ($type_selected ?? '') == 'allkelas' ? 'active' : '' ?>"
                                            data-mode="allkelas">Semua Kelas</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($kelas_selected)): ?>
                            <div class="tab-content pt-4">
                                <!-- Tab Persiswa -->
                                <div id="tab-persiswa"
                                    class="mode-tab <?= ($type_selected ?? 'persiswa') == 'persiswa' ? '' : 'hidden' ?>">
                                    <div class="table-responsive-tw"><table class="table-tw" id="tbl-siswa">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th class="text-center align-middle" style="width: 50px">No</th>
                                                    <th class="text-center align-middle" style="width: 150px">NIS / NISN</th>
                                                    <th class="align-middle px-4">Nama Siswa</th>
                                                    <th class="text-center align-middle">Status Rapor</th>
                                                    <th class="text-center align-middle" style="width: 250px">
                                                        Target Kelas Baru
                                                        <select class="form-select-tw" id="opsi-all-kelas">
                                                            <option value="0">Set Semua...</option>
                                                            <?php foreach ($kelases as $id => $name): ?>
                                                                <option value="<?= $id ?>"><?= $name ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1;
                                                foreach ($siswas as $siswa):
                                                    $promoted = $siswa_kelas_baru[$siswa->id_siswa] ?? null;
                                                    $is_naik = $siswa->naik ?? '1';
                                                    ?>
                                                    <tr data-id="<?= $siswa->id_siswa ?>">
                                                        <td class="text-center align-middle"><?= $no++ ?></td>
                                                        <td class="text-center align-middle text-sm"><?= esc($siswa->nis) ?> /
                                                            <?= $siswa->nisn ?></td>
                                                        <td class="align-middle px-4 font-semibold"><?= esc($siswa->nama) ?></td>
                                                        <td class="text-center align-middle">
                                                            <?php if ($is_naik == '1'): ?>
                                                                <span class="badge-tw badge-success">Naik</span>
                                                            <?php else: ?>
                                                                <span class="badge-tw badge-danger">TIDAK NAIK</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="align-middle">
                                                            <?php if ($is_naik == '0'): ?>
                                                                <input type="text" class="form-control-tw"
                                                                    value="Tetap di <?= esc($siswa->nama_kelas) ?>" disabled>
                                                                <input type="hidden" class="sel-target-kelas"
                                                                    value="<?= $siswa->id_kelas ?>">
                                                            <?php else: ?>
                                                                <select class="form-select-tw">
                                                                    <option value="0">Pilih Kelas...</option>
                                                                    <?php foreach ($kelases as $id => $name): ?>
                                                                        <option value="<?= $id ?>" <?= ($promoted && $promoted->nama_kelas == $name) ? 'selected' : '' ?>><?= $name ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-right mt-6">
                                        <button type="button" class="btn-tw btn-primary"
                                            data-mode="persiswa">
                                            <i class="bi bi-save mr-1"></i> Simpan Kenaikan Persiswa
                                        </button>
                                    </div>
                                </div>

                                <!-- Tab Perkelas -->
                                <div id="tab-perkelas"
                                    class="mode-tab <?= ($type_selected ?? '') == 'perkelas' ? '' : 'hidden' ?>">
                                    <div class="alert-tw alert-info">
                                        <i class="bi bi-exclamation-triangle mr-2"></i>
                                        Fitur ini akan menaikkan <b>SELURUH SISWA</b> di kelas ini yang status rapor-nya "Naik"
                                        ke kelas baru yang sama.
                                    </div>
                                    <div class="flex flex-wrap gap-4">
                                        <div class="">
                                            <label class="form-label-tw">Naikkan Seluruh Siswa ke Kelas:</label>
                                            <select id="bulk-target-kelas" class="form-select-tw">
                                                <option value="0">Pilih Kelas Baru...</option>
                                                <?php foreach ($kelases as $id => $name): ?>
                                                    <option value="<?= $id ?>"><?= $name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button"
                                                class="btn-tw btn-primary"
                                                data-mode="perkelas">
                                                <i class="bi bi-save mr-1"></i> Simpan Kenaikan Perkelas
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Semua Kelas -->
                                <div id="tab-allkelas"
                                    class="mode-tab <?= ($type_selected ?? '') == 'allkelas' ? '' : 'hidden' ?>">
                                    <div class="table-tw">
                                        <form id="form-allkelas">
                                            <table class="table-tw">
                                                <thead class="bg-slate-50">
                                                    <tr>
                                                        <th class="text-center align-middle" style="width: 50px">No</th>
                                                        <th class="align-middle px-4">Kelas TP <?= $tpBefore->tahun ?></th>
                                                        <th class="align-middle px-4">Target Kelas TP <?= $tp_active->tahun ?>
                                                        </th>
                                                        <th class="text-center align-middle" style="width: 80px">
                                                            Pilih<br>
                                                            <input type="checkbox" class="form-check-input"
                                                                id="check-all-kelas">
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $no = 1;
                                                    foreach ($kelas_lama as $id_lama => $name_lama):
                                                        $id_baru = $all_kelas_baru[$id_lama] ?? '0';
                                                        $name_baru = $kelases[$id_baru] ?? '-';
                                                        ?>
                                                        <tr>
                                                            <td class="text-center align-middle"><?= $no++ ?></td>
                                                            <td class="align-middle px-4"><?= $name_lama ?></td>
                                                            <td class="align-middle px-4 font-bold">
                                                                <?= $name_baru != '-' ? $name_baru : '?? (Level Tidak Sesuai)' ?>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <?php if ($id_baru != '0'): ?>
                                                                    <input type="checkbox" name="kelas_baru[<?= $id_lama ?>]"
                                                                        value="<?= $id_baru ?>"
                                                                        class="form-check-input check-per-kelas">
                                                                <?php else: ?>
                                                                    <i class="bi bi-x-circle text-red-500"></i>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </form>
                                    </div>
                                    <div class="text-right mt-6">
                                        <button type="button" class="btn-tw btn-primary"
                                            data-mode="allkelas">
                                            <i class="bi bi-save mr-1"></i> Simpan Semua Kelas Terpilih
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 text-slate-400 fst-italic border rounded bg-slate-50">
                                <i class="bi bi-arrow-up-circle text-5xl block mb-6"></i>
                                Silakan pilih kelas asal terlebih dahulu untuk memulai proses kenaikan.
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert-tw alert-info">
                            Tidak ada data Tahun Pelajaran sebelumnya. Proses kenaikan tidak dapat dilakukan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const base_url = '<?= base_url() ?>/';
    const klsSelected = '<?= $kelas_selected ?? '' ?>';
    let currentMode = '<?= $type_selected ?? 'persiswa' ?>';

    $(document).ready(function () {
        $('.select2').select2({ theme: 'bootstrap-5' });

        $('#opsi-kelas-lama').change(function () {
            let id = $(this).val();
            window.location.href = `${base_url}datakelas/kenaikan?kelas=${id}&type=${currentMode}`;
        });

        $('#mode-selector button').click(function () {
            let mode = $(this).data('mode');
            currentMode = mode;
            $('#mode-selector button').removeClass('active');
            $(this).addClass('active');
            $('.mode-tab').addClass('hidden');
            $(`#tab-${mode}`).removeClass('hidden');
        });

        $('#opsi-all-kelas').change(function () {
            let val = $(this).val();
            $('.sel-target-kelas').not(':disabled').val(val);
        });

        $('#check-all-kelas').change(function () {
            $('.check-per-kelas').prop('checked', this.checked);
        });

        $('.btn-save-promotion').click(function () {
            let mode = $(this).data('mode');
            let data = {};

            if (mode === 'persiswa') {
                let items = [];
                $('#tbl-siswa tbody tr').each(function () {
                    let idSiswa = $(this).data('id');
                    let targetKls = $(this).find('.sel-target-kelas').val();
                    let targetName = $(this).find('.sel-target-kelas option:selected').text();

                    if (targetKls != "0") {
                        items.push({
                            id_siswa: idSiswa,
                            nama_kls_baru: targetName,
                            id_kls_lama: klsSelected
                        });
                    }
                });
                if (items.length === 0) return Swal.fire("Peringatan", "Pilih minimal satu kelas baru untuk siswa!", "warning");
                data = { mode: 'persiswa', kelas: JSON.stringify(items) };
            }
            else if (mode === 'perkelas') {
                let targetId = $('#bulk-target-kelas').val();
                let targetName = $('#bulk-target-kelas option:selected').text();
                if (targetId == "0") return Swal.fire("Peringatan", "Pilih kelas target!", "warning");

                let items = [];
                $('#tbl-siswa tbody tr').each(function () {
                    items.push({
                        id_siswa: $(this).data('id'),
                        nama_kls_baru: targetName,
                        id_kls_lama: klsSelected
                    });
                });
                data = { mode: 'perkelas', kelas: JSON.stringify(items) };
            }
            else if (mode === 'allkelas') {
                if ($('.check-per-kelas:checked').length === 0) return Swal.fire("Peringatan", "Pilih minimal satu kelas!", "warning");
                data = $('#form-allkelas').serialize();
                saveAllKelas(data);
                return;
            }

            savePromotion(data);
        });

        function savePromotion(dataPost) {
            Swal.fire({
                title: 'Sedang memproses...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: base_url + 'datakelas/naikkelas',
                type: "POST",
                data: dataPost,
                success: function (res) {
                    var jumlah = parseInt(res && res.res) || 0;
                    var pesan = (res && res.message) ? res.message : '';
                    if (jumlah > 0) {
                        Swal.fire("Berhasil", pesan || (jumlah + " siswa dipindahkan"), "success").then(() => location.reload());
                    } else {
                        Swal.fire("Gagal", pesan || "Tidak ada siswa yang dipindahkan", "error");
                    }
                },
                error: function () { Swal.fire("Error", "Gagal menghubungi server", "error"); }
            });
        }

        function saveAllKelas(dataPost) {
            Swal.fire({
                title: "Naikkan Semua Kelas?",
                text: "Siswa di kelas terpilih akan dinaikkan ke kelas baru secara otomatis.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Naikkan!"
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', didOpen: () => { Swal.showLoading(); } });
                    $.ajax({
                        url: base_url + 'datakelas/naikkan_semua',
                        type: "POST",
                        data: dataPost,
                        success: function (res) {
                            var jumlah = parseInt(res && res.res) || 0;
                            var pesan = (res && res.message) ? res.message : '';
                            if (jumlah > 0) {
                                Swal.fire("Berhasil", pesan || (jumlah + " siswa berhasil dinaikkan"), "success").then(() => location.reload());
                            } else {
                                Swal.fire("Gagal", pesan || "Tidak ada siswa yang dinaikkan", "error");
                            }
                        },
                        error: function () { Swal.fire("Error", "Gagal menghubungi server", "error"); }
                    });
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .mode-tab.hidden {
        display: none !important;
    }
</style>
<?= $this->endSection() ?>