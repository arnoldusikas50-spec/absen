<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-gear-fill text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Halaman') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($subjudul ?? 'Pengaturan') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">

    <div class="grid grid-cols-1 gap-4">
                <!-- Pengaturan Sekolah -->
                <div class="">
                    <div class="card-tw">
                        <div class="card-tw-header">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                    <i class="bi bi-building text-blue-600 text-sm"></i>
                                </div>
                                <h6>Pengaturan Sekolah</h6>
                            </div>
                        </div>
                        <div class="card-tw-body">
                            <form id="formSekolah">
                                <input type="hidden" name="type" value="sekolah">
                                <div class="mb-6">
                                    <label class="form-label-tw">Nama Sekolah</label>
                                    <input type="text" name="nama_sekolah" class="form-control-tw"
                                        value="<?= esc($setting->nama_sekolah ?? '') ?>">
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <div class="">
                                        <label class="form-label-tw">NPSN</label>
                                        <input type="text" name="npsn" class="form-control-tw"
                                            value="<?= esc($setting->npsn ?? '') ?>">
                                    </div>
                                    <div class="">
                                        <label class="form-label-tw">Telepon</label>
                                        <input type="text" name="telepon" class="form-control-tw"
                                            value="<?= esc($setting->telepon ?? '') ?>">
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="form-label-tw">Email</label>
                                    <input type="email" name="email" class="form-control-tw"
                                        value="<?= esc($setting->email ?? '') ?>">
                                </div>
                                <div class="mb-6">
                                    <label class="form-label-tw">Website</label>
                                    <input type="url" name="website" class="form-control-tw"
                                        value="<?= esc($setting->website ?? '') ?>">
                                </div>
                                <div class="mb-6">
                                    <label class="form-label-tw">Alamat</label>
                                    <textarea name="alamat" class="form-control-tw"
                                        rows="2"><?= esc($setting->alamat ?? '') ?></textarea>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <div class="">
                                        <label class="form-label-tw">Kota</label>
                                        <input type="text" name="kota" class="form-control-tw"
                                            value="<?= esc($setting->kota ?? '') ?>">
                                    </div>
                                    <div class="">
                                        <label class="form-label-tw">Provinsi</label>
                                        <input type="text" name="provinsi" class="form-control-tw"
                                            value="<?= esc($setting->provinsi ?? '') ?>">
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <div class="">
                                        <label class="form-label-tw">Kepala Sekolah</label>
                                        <input type="text" name="kepala_sekolah" class="form-control-tw"
                                            value="<?= esc($setting->kepala_sekolah ?? '') ?>">
                                    </div>
                                    <div class="">
                                        <label class="form-label-tw">NIP Kepsek</label>
                                        <input type="text" name="nip_kepsek" class="form-control-tw"
                                            value="<?= esc($setting->nip_kepsek ?? '') ?>">
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="form-label-tw">Logo Sekolah</label>
                                    <input type="file" name="logo" class="form-control-tw" accept="image/*">
                                    <?php if (!empty($setting->logo)): ?>
                                        <span class="text-sm text-slate-400 block mt-2">Logo saat ini:
                                            <?= esc($setting->logo) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn-tw btn-primary">
                                    <i class="bi bi-check-circle mr-1"></i>Simpan Pengaturan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan TP & Semester & absensi by ikasmedia -->
                <div class="">
                    <div class="card-tw">
                        <div class="card-tw-header">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                    <i class="bi bi-calendar-check text-emerald-600 text-sm"></i>
                                </div>
                                <h6>Tahun Pelajaran Aktif</h6>
                            </div>
                        </div>
                        <div class="card-tw-body">
                            <form id="formTp">
                                <input type="hidden" name="type" value="tp">
                                <div class="mb-6">
                                    <label class="form-label-tw">Pilih Tahun Pelajaran</label>
                                    <select name="tp_aktif" class="form-select-tw">
                                        <?php foreach ($tp_list ?? [] as $tp): ?>
                                            <option value="<?= is_object($tp) ? $tp->id_tp : '' ?>" <?= ($tp_active && is_object($tp) && $tp->id_tp == $tp_active->id_tp) ? 'selected' : '' ?>>
                                                <?= is_object($tp) ? esc($tp->tahun) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-tw btn-success">
                                    <i class="bi bi-check-circle mr-1"></i>Aktifkan
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-tw">
                        <div class="card-tw-header">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                    <i class="bi bi-clock-history text-amber-500 text-sm"></i>
                                </div>
                                <h6>Semester Aktif</h6>
                            </div>
                        </div>
                        <div class="card-tw-body">
                            <form id="formSmt">
                                <input type="hidden" name="type" value="smt">
                                <div class="mb-6">
                                    <label class="form-label-tw">Pilih Semester</label>
                                    <select name="smt_aktif" class="form-select-tw">
                                        <?php foreach ($smt_list ?? [] as $smt): ?>
                                            <option value="<?= is_object($smt) ? $smt->id_smt : '' ?>" <?= ($smt_active && is_object($smt) && $smt->id_smt == $smt_active->id_smt) ? 'selected' : '' ?>>
                                                Semester
                                                <?= is_object($smt) ? esc($smt->smt) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-tw btn-warning">
                                    <i class="bi bi-check-circle mr-1"></i>Aktifkan
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-tw">
                        <div class="card-tw-header">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-cyan-50 flex items-center justify-center shrink-0">
                                    <i class="bi bi-pc-display text-cyan-600 text-sm"></i>
                                </div>
                                <h6>Pengaturan CBT</h6>
                            </div>
                        </div>
                        <div class="card-tw-body">
                            <form id="formCbt">
                                <input type="hidden" name="type" value="cbt">
                                <div class="mb-6">
                                    <label class="form-label-tw">Durasi Default (menit)</label>
                                    <input type="number" name="durasi_default" class="form-control-tw"
                                        value="<?= esc($setting->durasi_default ?? 60) ?>">
                                </div>
                                <div class="mb-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="acak_soal" id="acakSoal"
                                            <?= ($setting->acak_soal ?? '0') === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="acakSoal">Acak Urutan Soal</label>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="acak_jawaban"
                                            id="acakJawaban" <?= ($setting->acak_jawaban ?? '0') === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="acakJawaban">Acak Urutan Jawaban</label>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tampil_nilai"
                                            id="tampilNilai" <?= ($setting->tampil_nilai ?? '0') === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="tampilNilai">Tampilkan Nilai Setelah
                                            Ujian</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn-tw btn-info">
                                    <i class="bi bi-check-circle mr-1"></i>Simpan Pengaturan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
<script>
    const csrfToken = '<?= csrf_hash() ?>';

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('<?= csrf_token() ?>', csrfToken);

            try {
                const res = await fetch('<?= site_url('setting/update') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status) {
                    Swal.fire('Berhasil', data.message, 'success').then(() => {
                        if (this.id === 'formTp' || this.id === 'formSmt') {
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan', 'error');
            }
        });
    });
</script>
<?= $this->endSection() ?>