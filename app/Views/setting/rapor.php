<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$satuan = [
    "1" => ["I ~ V", "VI"],
    "2" => ["VII-VIII", "IX"],
    "3" => [
        "X-XI",
        "XII"
    ]
];
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-clipboard-data text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Halaman') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600"><i class="bi bi-house-door mr-1"></i> Dashboard</a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?php echo esc($subjudul ?? 'Data') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">
<div class="card-tw">
            <div class="card-tw-body">
                <div class="alert-tw alert-info" role="alert">
                    <span><b>KKM</b></span>
                    <ul>
                        <li>KKM Tunggal: mengatur semua mapel mempunyai KKM yang sama
                        <li>Total BOBOT harus 100
                        <li>Jangan lupa untuk menyimpan perubahan
                    </ul>
                    <span><b>TAMPILKAN NIP</b></span>
                    <ul class="m-0">
                        <li>Pilih <b>YA</b> jika NIP kepala sekolah / walikelas diisi NIP
                        <li>Jika NIP kepala sekolah / walikelas diisi NUPTK atau nomor lain selain NIP maka sebaiknya
                            pilih <b>TIDAK</b> ditampilkan
                    </ul>
                </div>
                <hr>
                <?= form_open('rapor/saveraporadmin', array('id' => 'editsetting'), array('id_setting' => $rapor != null ? $rapor->id_setting : '')) ?>
                <div class="grid grid-cols-1 gap-4">
                    <div class="">
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">Tgl Rapor PTS</label>
                            <div class="col-7">
                                <input type="text" name="tgl_rapor_pts"
                                    value="<?= $rapor != null ? $rapor->tgl_rapor_pts : '' ?>" class="form-control-tw"
                                    <span class="sep">/</span>
                                    <span class="active">autocomplete="off" required></span>
                                    </nav>
                            </div>
                        </div>
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">Tgl Rapor Akhir Kls
                                <?= $satuan[$setting->jenjang][0] ?></label>
                            <div class="col-7">
                                <input type="text" name="tgl_rapor_akhir"
                                    value="<?= $rapor != null ? $rapor->tgl_rapor_akhir : '' ?>"
                                    class="form-control-tw" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">Tgl Rapor Akhir Kls
                                <?= $satuan[$setting->jenjang][1] ?></label>
                            <div class="col-7">
                                <input type="text" name="tgl_rapor_kelas_akhir"
                                    value="<?= $rapor != null ? $rapor->tgl_rapor_kelas_akhir : '' ?>"
                                    class="form-control-tw" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="mb-6 row">
                            <label class="col-5 form-label-tw">Tampilkan NIP Kepala Sekolah</label>
                            <div class="col-7">
                                <?php
                                echo form_dropdown(
                                    'nip_kepsek',
                                    $kkm_drop,
                                    $rapor != null && isset($rapor->nip_kepsek) ? $rapor->nip_kepsek : '',
                                    'class="form-control-tw" required'
                                ); ?>
                            </div>
                        </div>
                        <div class="mb-6 row">
                            <label class="col-5 form-label-tw">Tampilkan NIP Walikelas</label>
                            <div class="col-7">
                                <?php
                                echo form_dropdown(
                                    'nip_walikelas',
                                    $kkm_drop,
                                    $rapor != null && isset($rapor->nip_walikelas) ? $rapor->nip_walikelas : '',
                                    'class="form-control-tw" required'
                                ); ?>
                            </div>
                        </div>
                    </div>

                    <div class=""></div>
                    <div class="w-full md:w-5/12">
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">KKM Tunggal</label>
                            <div class="col-7">
                                <?php
                                echo form_dropdown(
                                    'kkm_tunggal',
                                    $kkm_drop,
                                    $rapor != null ? $rapor->kkm_tunggal : '',
                                    'id="tunggal" class="form-select-tw" required'
                                ); ?>
                            </div>
                        </div>
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">KKM</label>
                            <div class="col-7">
                                <input type="number" name="kkm" value="<?= $rapor != null ? $rapor->kkm : '' ?>"
                                    class="form-control-tw" required>
                            </div>
                        </div>
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">Bobot PH</label>
                            <div class="col-7">
                                <input type="number" name="bobot_ph"
                                    value="<?= $rapor != null ? $rapor->bobot_ph : '' ?>" class="form-control-tw"
                                    required>
                            </div>
                        </div>
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">Bobot PTS</label>
                            <div class="col-7">
                                <input type="number" name="bobot_pts"
                                    value="<?= $rapor != null ? $rapor->bobot_pts : '' ?>" class="form-control-tw"
                                    required>
                            </div>
                        </div>
                        <div class="mb-6 row items-center">
                            <label class="col-5 form-label-tw">Bobot PAS</label>
                            <div class="col-7">
                                <input type="number" name="bobot_pas"
                                    value="<?= $rapor != null ? $rapor->bobot_pas : '' ?>" class="form-control-tw"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="w-full">
                        <button type="submit" class='btn-tw btn-primary'><i class='bi bi-save mr-1'></i>
                            Simpan
                        </button>
                    </div>
                </div>
                <?= form_close() ?>
            </div>
            <div class="overlay hidden absolute top-0 start-0 w-full h-full flex items-center justify-center bg-white bg-opacity-75"
                id="loading" style="z-index: 10;">
                <div class="spinner-border text-blue-600" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>

<script>
    $(document).ready(function () {
        function onChangeKkm() {
            console.log('change');
            var tunggal = $('#tunggal').val();
            if (tunggal == '1') {
                $('.kkm').removeAttr('readonly');
            } else {
                $('.kkm').attr('readonly', 'true');
            }
        }

        onChangeKkm();

        $.datetimepicker.setLocale('id');
        $('.tgl').datetimepicker({
            i18n: {
                id: {
                    months: [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei',
                        'Juni', 'Juli', 'Agustus', 'September',
                        'Oktober', 'November', 'Desember'
                    ],
                    dayOfWeek: [
                        'Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'
                    ]
                }
            },
            icons:
            {
                next: 'bi bi-chevron-right',
                previous: 'bi bi-chevron-left'
            },
            timepicker: false,
            scrollInput: false,
            scrollMonth: false,
            format: 'Y-m-d',
            //disabledWeekDays: [0],
            widgetPositioning: {
                horizontal: 'left',
                vertical: 'bottom'
            }
        }).change(function () {
            // buat_tanggal_indonesia might be a global function
            // if not, this will throw error, but sticking to original.
            if (typeof buat_tanggal_indonesia === 'function') {
                $(this).val(buat_tanggal_indonesia($(this).val()));
            }
        });

        $('#editsetting').submit(function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();

            console.log($(this).serialize());
            $('#loading').removeClass('hidden');

            $.ajax({
                url: base_url + 'rapor/saveRaporAdmin', // Updated URL casing or keep lowercase often safer. "saveraporadmin" in CI3/CI4 default routes may act differently. CI4 routes often case-sensitive if not AutoRoute. I'll use method name camelCase if route isn't defined, but URL usually lowercase. The controller method is `saveRaporAdmin`.
                type: "POST",
                dataType: "json",
                data: $(this).serialize(),
                success: function (response) {
                    setTimeout(function () {
                        $('#loading').addClass('hidden');
                        if (response.status) {
                            showSuccessToast("Sukses")
                        } else {
                            showSuccessToast("gagal")
                        }
                    }, 500);
                }, error: function (xhr, status, error) {
                    $('#loading').addClass('hidden');
                    console.log("response:", xhr.responseText);
                    showDangerToast('gagal disimpan')
                }
            });
        });

        $('#tunggal').on('change', function () {
            onChangeKkm();
        });

    })
</script>
<?= $this->endSection() ?>