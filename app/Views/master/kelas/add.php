<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$jjs = [];
// Jika edit mode dan ada siswakelas, gunakan itu untuk menandai siswa terpilih
// (CATATAN: jangan pakai $siswa sebagai variabel loop — menimpa array $siswa dari controller!)
if (isset($siswakelas) && !empty($siswakelas)) {
    foreach ($siswakelas as $sw) {
        $jjs[] = $sw->id_siswa;
    }
}
// Fallback ke jumlah_siswa dari kelas (jika ada)
if (empty($jjs) && isset($kelas->jumlah_siswa) && !empty($kelas->jumlah_siswa)) {
    $jumlahSiswa = is_string($kelas->jumlah_siswa) ? json_decode($kelas->jumlah_siswa) : $kelas->jumlah_siswa;
    if (is_array($jumlahSiswa)) {
        foreach ($jumlahSiswa as $js) {
            if (isset($js->id)) {
                $jjs[] = $js->id;
            }
        }
    }
}
?>

<!-- Content Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-door-closed"></i> <?= isset($id_kelas) ? 'Edit Kelas' : 'Kelas Baru' ?>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Kelas') ?><?= isset($kelas->nama_kelas) && $kelas->nama_kelas !== '' ? ' — ' . esc($kelas->nama_kelas) : '' ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul ?? 'Form Kelas') ?> · TP <?= esc($tp_active->tahun ?? '-') ?> · Semester <?= esc($smt_active->smt ?? '-') ?></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('datakelas') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" form="create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-save"></i> Simpan
            </button>
        </div>
    </div>

        <form id="create">
            <?= csrf_field() ?>
            <?php if (isset($id_kelas)): ?>
                <input type="hidden" name="id_kelas" value="<?= (int) $id_kelas ?>">
            <?php endif; ?>

            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-door-closed text-sm"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 leading-none"><?= esc($subjudul ?? 'Form Kelas') ?></h2>
                            <p class="text-[11px] text-slate-400 mt-1">Lengkapi identitas kelas di bawah ini</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-[11px] font-bold text-slate-600">
                        <i class="bi bi-calendar3 text-[10px]"></i> TP <?= esc($tp_active->tahun ?? '-') ?> &middot; Smt <?= esc($smt_active->smt ?? '-') ?>
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label for="nama_kelas" class="form-label-tw">Nama Kelas <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_kelas" value="<?= esc($kelas->nama_kelas ?? '') ?>"
                                id="nama_kelas" class="form-control-tw" placeholder="Contoh: X 1" required minlength="2" maxlength="30">
                        </div>
                        <div>
                            <label for="level_id" class="form-label-tw">Level <span class="text-red-500">*</span></label>
                            <?= form_dropdown('level_id', $level, $kelas->level_id ?? '', 'class="form-select-tw" id="level_id" required') ?>
                        </div>
                        <div>
                            <label for="kode_kelas" class="form-label-tw">Kode Kelas</label>
                            <input type="text" name="kode_kelas" value="<?= esc($kelas->kode_kelas ?? '') ?>"
                                id="kode_kelas" class="form-control-tw" placeholder="Contoh: X-1" maxlength="20">
                        </div>
                        <?php if (isset($guru) && is_array($guru) && count($guru) > 0): ?>
                            <div>
                                <label for="guru_id" class="form-label-tw">Wali Kelas</label>
                                <?= form_dropdown('guru_id', $guru, $kelas->guru_id ?? '', 'class="form-select-tw" id="guru_id"') ?>
                                <p class="text-[11px] text-slate-400 mt-1">Wali lama kelas ini otomatis dilepas saat diganti.</p>
                            </div>
                        <?php endif; ?>
                        <?php if (($setting->jenjang ?? '') == '3'): ?>
                            <div class="md:col-span-2">
                                <label for="jurusan_id" class="form-label-tw">Jurusan</label>
                                <?= form_dropdown('jurusan_id', $jurusan, $kelas->jurusan_id ?? '', 'class="form-select-tw" id="jurusan_id"') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-5 border-slate-100">

                    <div class="w-full">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h5 class="font-extrabold text-slate-800 text-sm flex items-center gap-2 mb-0">
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i class="bi bi-people text-xs"></i>
                                </span>
                                Daftar Siswa
                            </h5>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold">
                                <i class="bi bi-check-circle-fill text-[10px]"></i><span id="siswa-count-badge">0</span>&nbsp;terpilih
                            </span>
                        </div>
                        <div class="rounded-xl bg-blue-50/70 border border-blue-100 px-3.5 py-2.5 mb-4 flex items-start gap-2.5 text-xs text-blue-900">
                            <i class="bi bi-info-circle-fill text-blue-600 text-sm mt-px shrink-0"></i>
                            <span><strong>Catatan:</strong> Klik nama siswa untuk memindahkan antar kolom. Siswa yang sudah masuk kelas lain menampilkan nama kelasnya — memilihnya akan <strong>memindahkan</strong> siswa ke kelas ini. Gunakan pencarian di tiap kolom untuk mempercepat.</span>
                        </div>

                            <?php
                            $all_siswa = [];
                            $kelas_asal = []; // id_siswa => nama kelas saat ini (untuk label pindahan)
                            $idKelasIni = isset($id_kelas) ? (int) $id_kelas : 0;
                            // Semua siswa aktif tampil sebagai opsi (bisa pindah antar kelas).
                            // save() memakai REPLACE satu baris per siswa per TP/SMT sehingga
                            // memilih siswa dari kelas lain otomatis memindahkannya ke kelas ini.
                            if (isset($siswa) && is_array($siswa)) {
                                foreach ($siswa as $s) {
                                    // Make sure $s is an object, not string
                                    if (is_object($s) && isset($s->id_siswa)) {
                                        $all_siswa[$s->id_siswa] = $s->nama . ' (' . ($s->nis ?? '-') . ')';
                                        $sKelas = (int) ($s->id_kelas ?? 0);
                                        if ($sKelas > 0 && $sKelas !== $idKelasIni && !empty($s->nama_kelas)) {
                                            $kelas_asal[$s->id_siswa] = $s->nama_kelas;
                                        }
                                    }
                                }
                            }
                            // Pastikan anggota kelas ini selalu ada (walau status non-aktif / tak ada di daftar)
                            if (isset($siswakelas) && is_array($siswakelas)) {
                                foreach ($siswakelas as $sk) {
                                    if (is_object($sk) && isset($sk->id_siswa)) {
                                        $all_siswa[$sk->id_siswa] = $sk->nama . ' (' . ($sk->nis ?? '-') . ')';
                                        unset($kelas_asal[$sk->id_siswa]);
                                    }
                                }
                            }
                            ?>
                            <select name="siswa[]" class="form-control-tw" id="jumlah_siswa" multiple="multiple">
                                <?php foreach ($all_siswa as $id => $name): ?>
                                    <option value="<?= (int) $id ?>" <?= in_array($id, $jjs) ? 'selected' : '' ?>>
                                        <?= esc($name) ?><?= isset($kelas_asal[$id]) ? ' — ' . esc($kelas_asal[$id]) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-2">Klik nama untuk memindahkan &middot; <span class="font-bold text-slate-600"><span id="siswa-count">0</span> siswa</span> terpilih di kelas ini.</p>
                    </div>
                </div>
            </div>
        </form>
</div>

<!-- Load Library for MultiSelect -->
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/plugins/multiselect/css/multi-select.css') ?>">
<style>
    /* ── Dual-list siswa: full-width flex (override plugin 370px float) ── */
    .ms-container { width: 100%; max-width: 100%; background: none; display: flex; align-items: stretch; gap: 12px; }
    .ms-container:after { display: none; }
    .ms-container .ms-selectable, .ms-container .ms-selection { float: none; width: auto; flex: 1 1 0; min-width: 0; background: transparent; }
    .ms-container .ms-selection { display: flex; flex-direction: column; }
    .ms-container .ms-selectable { display: flex; flex-direction: column; }
    .ms-container .ms-list {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        min-height: 260px;
        max-height: 320px;
        box-shadow: none;
    }
    .ms-container .ms-list.ms-focus { border-color: var(--primary-600); box-shadow: 0 0 0 3px rgb(var(--primary-600-rgb) / .12); outline: none; }
    .ms-container .ms-selectable li.ms-elem-selectable,
    .ms-container .ms-selection li.ms-elem-selection {
        font-size: .78rem; padding: 7px 10px; border-bottom: 1px solid #f1f5f9;
        color: #334155; border-radius: 0; line-height: 1.4;
    }
    .ms-container .ms-selectable li.ms-elem-selectable:last-child,
    .ms-container .ms-selection li.ms-elem-selection:last-child { border-bottom: none; }
    .ms-container .ms-selectable li.ms-hover, .ms-container .ms-selection li.ms-hover {
        background-color: var(--primary-600);
        color: #fff;
    }
    .ms-search-input {
        border: 1px solid #e2e8f0 !important; border-radius: .65rem !important;
        font-size: .78rem !important; padding: 8px 12px !important; margin-bottom: 8px !important;
        width: 100%; background: #f8fafc;
    }
    .ms-search-input:focus { background: #fff; border-color: var(--primary-600) !important; box-shadow: 0 0 0 3px rgb(var(--primary-600-rgb) / .12); outline: none; }
    .ms-col-title {
        font-weight: 800; margin-bottom: 8px; text-align: center;
        padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: .65rem;
        font-size: .75rem; background: #f8fafc;
    }
    .ms-col-title.tersedia { color: var(--primary-700); }
    .ms-col-title.terpilih { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
    @media (max-width: 640px) {
        .ms-container { flex-direction: column; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/plugins/multiselect/js/jquery.multi-select.js') ?>"></script>
<script src="<?= base_url('assets/plugins/multiselect/js/jquery.quicksearch.js') ?>"></script>

<script>
    // base_url is already defined in main layout
    $(document).ready(function () {
        $('#jumlah_siswa').multiSelect({
            selectableHeader: "<div class='ms-col-title tersedia'>Siswa Tersedia</div><input type='text' class='ms-search-input' autocomplete='off' placeholder='Cari siswa tersedia...'>",
            selectionHeader: "<div id='totalsiswa' class='ms-col-title terpilih'>Siswa Terpilih: 0</div><input type='text' class='ms-search-input' autocomplete='off' placeholder='Cari siswa terpilih...'>",
            afterInit: function (container) {
                var that = this,
                    $selectableSearch = that.$selectableUl.prev(),
                    $selectionSearch = that.$selectionUl.prev(),
                    selectableSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selectable:not(.ms-selected)',
                    selectionSearchString = '#' + that.$container.attr('id') + ' .ms-elem-selection.ms-selected';

                that.qs1 = $selectableSearch.quicksearch(selectableSearchString).on('keydown', function (e) {
                    if (e.which === 40) {
                        that.$selectableUl.focus();
                        return false;
                    }
                });

                that.qs2 = $selectionSearch.quicksearch(selectionSearchString).on('keydown', function (e) {
                    if (e.which == 40) {
                        that.$selectionUl.focus();
                        return false;
                    }
                });

                updateTotal();
            },
            afterSelect: function (values) {
                updateTotal();
                this.qs1.cache();
                this.qs2.cache();
            },
            afterDeselect: function (values) {
                updateTotal();
                this.qs1.cache();
                this.qs2.cache();
            }
        });

        function updateTotal() {
            let count = $('#jumlah_siswa option:selected').length;
            $('#totalsiswa').text("Siswa Terpilih: " + count);
            $('#siswa-count').text(count);
            $('#siswa-count-badge').text(count);
        }

        $('#create').submit(function (e) {
            e.preventDefault();

            const nama = ($('#nama_kelas').val() || '').trim();
            if (nama.length < 2) {
                Swal.fire('Perhatian', 'Nama kelas minimal 2 karakter.', 'warning');
                $('#nama_kelas').trigger('focus');
                return;
            }

            const $btn = $(this).find('button[type="submit"]');
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span> Menyimpan...');

            Swal.fire({
                title: 'Sedang menyimpan...',
                text: 'Mohon tunggu sejenak',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formEl = this;
            $.ajax({
                url: base_url + "datakelas/save",
                type: "POST",
                data: $(formEl).serialize(),
                dataType: 'json',
                success: function (data) {
                    $btn.prop('disabled', false).html(orig);
                    if (data && data.status) {
                        Swal.fire({
                            title: "Berhasil",
                            text: data.message || "Data kelas berhasil disimpan",
                            icon: "success",
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.href = base_url + 'datakelas';
                        });
                    } else {
                        Swal.fire({
                            title: "Gagal",
                            text: (data && data.message) || "Gagal menyimpan data",
                            icon: "warning",
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(orig);
                    Swal.fire({
                        title: "Error",
                        text: "Gagal menghubungi server",
                        icon: "error",
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>