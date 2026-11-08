<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/plugins/dropify/css/dropify.min.css') ?>" />
<style>
    #sig-canvas {
        border: 2px dotted #CCCCCC;
        border-radius: 15px;
        cursor: crosshair;
        background-color: #ffffff;
        width: 100%;
        max-width: 400px;
        height: 200px;
        touch-action: none;
    }
</style>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="container-fluid">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-person-circle text-primary me-2"></i>
                    <?= esc($judul ?? 'Profil Guru') ?>
                </h3>
            </div>
            <div class="w-full sm:w-1/2 px-2">
                <ol class="breadcrumb sm:float-right mb-0 bg-transparent">
                    <li class="breadcrumb-item">
                        <a href="<?= site_url('dashboard') ?>" class="text-decoration-none">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active"><?= esc($subjudul ?? 'Data Profil Saya') ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
        <div class="container-fluid">
            <div class="flex flex-wrap gap-4">
                <div class="col-xl-4 w-full md:w-1/3 px-2">
                    <div class="card shadow-sm border-0 card-primary card-outline">
                        <div class="card-body text-center">
                            <?php
                            $foto = $guru->foto && file_exists(FCPATH . $guru->foto)
                                ? base_url($guru->foto)
                                : base_url('assets/img/guru.png');
                            ?>
                            <img id="foto-guru" src="<?= $foto ?>" class="rounded-circle img-thumbnail mb-3"
                                alt="User avatar" style="width: 150px; height: 150px; object-fit: cover;">
                            <h4 class="fw-bold mb-1">
                                <?= esc($guru->nama_guru) ?>
                            </h4>
                            <p class="text-muted mb-4">
                                <?= esc(($guru->level ?? '') . ' ' . ($guru->nama_kelas ?? '')) ?>
                            </p>

                            <div class="flex flex-wrap gap-4 gap-2 mb-3">
                                <div class="w-1/2 px-2">
                                    <button type="button" data-ui-toggle="modal" data-ui-target="#editFotoModal"
                                        class="btn-tw btn-tw-sm btn-primary w-100"><i class="bi bi-image me-1"></i>
                                        Ganti Foto
                                    </button>
                                </div>
                                <div class="w-1/2 px-2">
                                    <button type="button" class="btn-tw btn-tw-sm btn-danger w-100"
                                        onclick="deleteImage(true)"><i class="bi bi-trash me-1"></i> Hapus Foto
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="btn-tw btn-tw-sm btn-warning w-100 mb-4" data-ui-toggle="modal"
                                data-ui-target="#editLoginModal"><i class="bi bi-pencil me-1"></i> Edit Username /
                                Password
                            </button>

                            <div class="text-start">
                                <h6 class="text-muted small fw-bold text-uppercase mb-2">Tanda Tangan</h6>
                                <div class="border rounded p-3 bg-light">
                                    <div id="ttd-placeholder"
                                        class="flex align-items-center justify-content-center text-muted <?= !empty($guru->ttd) ? 'hidden' : '' ?>"
                                        style="height: 100px; border: 2px dashed #ccc;">
                                        <small>Belum ada TTD</small>
                                    </div>
                                    <img src="<?= !empty($guru->ttd) ? base_url($guru->ttd) : '' ?>" alt="TTD"
                                        id="img-ttd-preview"
                                        class="img-fluid mx-auto block <?= empty($guru->ttd) ? 'hidden' : '' ?>"
                                        style="max-height: 100px;">
                                    <div class="mt-2 text-center">
                                        <button type="button" class="btn-tw btn-tw-sm btn-success" id="btn-create-ttd"
                                            title="Buat TTD Baru">
                                            <i class="bi bi-pencil-square me-1"></i> Buat
                                        </button>
                                        <button type="button"
                                            class="btn-tw btn-tw-sm btn-danger <?= empty($guru->ttd) ? 'hidden' : '' ?>"
                                            id="btn-delete-ttd" title="Hapus TTD">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8 w-full md:w-2/3 px-2">
                    <div class="card shadow-sm border-0 card-primary card-outline card-outline-tabs">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs-tw" id="guruEditTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link-tw active" id="profile-tab" data-ui-toggle="tab"
                                        data-ui-target="#profile-pane" type="button" role="tab">Profile</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link-tw" id="address-tab" data-ui-toggle="tab"
                                        data-ui-target="#address-pane" type="button" role="tab">Data Lengkap</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link-tw" id="docs-tab" data-ui-toggle="tab"
                                        data-ui-target="#docs-pane" type="button" role="tab">Dokumen</button>
                                </li>
                            </ul>
                        </div>
                        <form id="formguru" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_guru" value="<?= $id_active ?>">
                            <div class="card-body">
                                <div class="tab-content-tw" id="guruEditTabsContent">
                                    <!-- Profile Tab -->
                                    <div class="tab-pane-tw fade show active pt-3" id="profile-pane" role="tabpanel">
                                        <?php foreach ($input_profile as $input): ?>
                                            <div class="mb-3 flex flex-wrap gap-4">
                                                <label class="w-full sm:w-1/4 px-2 form-label-tw">
                                                    <i class="<?= $input->icon ?> me-2 text-secondary"></i> <?= $input->label ?>
                                                </label>
                                                <div class="w-full sm:w-3/4 px-2">
                                                    <?php if ($input->name == 'jenis_kelamin'): ?>
                                                        <select class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white" name="jenis_kelamin" required>
                                                            <option value="" disabled <?= empty($input->value) ? 'selected' : '' ?>>Pilih Jenis Kelamin</option>
                                                            <option value="L" <?= $input->value == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                                            <option value="P" <?= $input->value == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                                        </select>
                                                    <?php elseif ($input->name == 'agama'): ?>
                                                        <select class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white" name="agama">
                                                            <?php $agamas = ["Islam", "Katolik", "Protestan", "Hindu", "Budha", "Konghucu"]; ?>
                                                            <?php foreach ($agamas as $agama): ?>
                                                                <option value="<?= $agama ?>" <?= $input->value == $agama ? 'selected' : '' ?>>
                                                                    <?= $agama ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php else: ?>
                                                        <input type="<?= $input->type ?>" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                            name="<?= $input->name ?>" value="<?= $input->value ?>"
                                                            placeholder="<?= $input->label ?>"
                                                            <?= ($input->name == 'api_key') ? 'readonly' : '' ?>>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Address Tab -->
                                    <div class="tab-pane-tw fade pt-3" id="address-pane" role="tabpanel">
                                        <?php foreach ($input_alamat as $input): ?>
                                            <div class="mb-3 flex flex-wrap gap-4">
                                                <label class="w-full sm:w-1/4 px-2 form-label-tw">
                                                    <i class="<?= $input->icon ?> me-2 text-secondary"></i> <?= $input->label ?>
                                                </label>
                                                <div class="w-full sm:w-3/4 px-2">
                                                    <input type="<?= $input->type ?>" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                                        name="<?= $input->name ?>" value="<?= $input->value ?>"
                                                        placeholder="<?= $input->label ?>"
                                                        id="<?= $input->name ?>">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Documents Tab -->
                                    <div class="tab-pane-tw fade pt-3" id="docs-pane" role="tabpanel">
                                        <div class="flex flex-wrap gap-4">
                                            <?php foreach ($input_dokumen as $doc): ?>
                                                <div class="w-full md:w-1/2 px-2 mb-4">
                                                    <label class="form-label-tw fw-bold">
                                                        <?= $doc->label ?>
                                                    </label>
                                                    <input type="file" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm mb-2" id="<?= $doc->id ?>"
                                                        name="<?= $doc->name ?>" <?= $doc->id == 'sertifikat' ? 'multiple' : '' ?>>
                                                    <div id="preview-<?= $doc->id ?>" class="flex flex-wrap gap-2 mt-2">
                                                        <!-- Thumbnails will be rendered here -->
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top flex align-items-center">
                                <small class="text-danger"><i>* Semua kolom profil wajib diisi</i></small>
                                <button type="button" id="submit-profil" class="btn-tw btn-primary ms-auto">
                                    <i class="bi bi-save me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Photo Modal -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="editFotoModal" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="modal-content-tw bg-white w-full rounded-2xl shadow-xl overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title">Ganti Foto Profil</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="foto-form">
                    <input type="file" id="foto-input" name="foto" class="dropify"
                        data-allowed-file-extensions="jpg jpeg png" data-default-file="<?= $foto ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="button" class="btn-tw btn-primary" id="btn-save-foto">Simpan Foto</button>
            </div>
        </div>
    </div>
</div>

<!-- Login Modal -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="editLoginModal" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="login-form">
            <?= csrf_field() ?>
            <div class="modal-content-tw bg-white w-full rounded-2xl shadow-xl overflow-hidden">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Akun Login</h5>
                    <button type="button" class="btn-close" data-ui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-tw">Username</label>
                        <input type="text" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" name="username" value="<?= esc($user->username) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-tw">Password Baru</label>
                        <input type="password" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" name="new"
                            placeholder="Kosongkan jika tidak ingin ganti">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-tw">Konfirmasi Password Baru</label>
                        <input type="password" class="input-tw w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" name="new_confirm"
                            placeholder="Ulangi password baru">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-warning">Update Login</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Signature Modal -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="modal-signature" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="modal-content-tw bg-white w-full rounded-2xl shadow-xl overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title">Buat Tanda Tangan</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <canvas id="sig-canvas"></canvas>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-tw btn-secondary" id="sig-clear">Bersihkan</button>
                <button type="button" class="btn-tw btn-primary" id="sig-submit">Simpan TTD</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/plugins/dropify/js/dropify.min.js') ?>"></script>
<script>
    // Endpoint profil di bawah ini dipanggil via $.ajax POST. Bentuk datanya
    // beragam (FormData, object, tanpa data), sehingga ajaxSetup berbasis
    // `data` akan tertimpa oleh `data:` per-panggilan. Header X-CSRF-TOKEN
    // dibaca CI4 CSRF filter dan tidak tertimpa, jadi dipakai untuk semua call.
    <?php if (csrf_inline()) : ?>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } });
    <?php endif; ?>

    const idGuru = '<?= $id_active ?>';
    let dokumen = <?= json_encode($guru->dokumen ?? new stdClass()) ?>;

    function deleteImage(confirm) {
         Swal.fire({
            title: 'Hapus Foto?',
            text: "Foto profil akan dihapus dan kembali ke default",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= site_url("guru/deleteFoto") ?>',
                    type: 'POST',
                    success: function (res) {
                         if (res.status) {
                            $('#foto-guru').attr('src', '<?= base_url("assets/img/guru.png") ?>');
                            Swal.fire('Terhapus!', 'Foto profil berhasil dihapus.', 'success');
                         } else {
                            Swal.fire('Gagal', 'Gagal menghapus foto.', 'error');
                         }
                    },
                    error: function() {
                         Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            }
        });
    }

    function renderDocs() {
        ['ktp_id', 'kk_id', 'sertifikat'].forEach(id => {
            const container = $(`#preview-${id}`);
            container.empty();
            let key = id === 'sertifikat' ? 'sertifikat' : (id === 'ktp_id' ? 'ktp_id' : 'kk_id');
            // Mapping key names from controller definition to actual db json key might differ?
            // Controller defined names: ktp_id, kk_id, serti[]
            // But documents saved in DB with keys: ktp_id, kk_id, serti
            
            // Correction based on Controller implementation:
            if (id === 'sertifikat') key = 'serti';
            
            if (dokumen && dokumen[key]) {
                const docList = Array.isArray(dokumen[key]) ? dokumen[key] : [dokumen[key]];
                docList.forEach(src => {
                    const fullSrc = base_url + src;
                    container.append(`
                        <div class="position-relative">
                            <img src="${fullSrc}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                            <button type="button" class="btn-tw btn-tw-sm btn-danger position-absolute top-0 end-0" onclick="deleteDoc('${src}')">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    `);
                });
            }
        });
    }

    function deleteDoc(src) {
        Swal.fire({
            title: 'Hapus Dokumen?',
            icon: 'warning',
            showCancelButton: true
        }).then(res => {
            if (res.value) {
                $.ajax({
                    url: '<?= site_url("guru/deleteDokumen") ?>',
                    data: { src: src },
                    type: 'POST',
                    success: (res) => {
                        if (res.status) {
                            dokumen = res.dokumen;
                            renderDocs();
                        }
                    }
                });
            }
        });
    }

    $(document).ready(function () {
        renderDocs();

        $('#tgl_lahir').datetimepicker({
            format: 'Y-m-d',
            timepicker: false
        });

        $('.dropify').dropify();

        // Photo Upload
        $('#btn-save-foto').click(function () {
            let formData = new FormData($('#foto-form')[0]);
            $.ajax({
                url: '<?= site_url("guru/uploadFoto") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (res) => {
                    if (res.status) {
                        $('#foto-guru').attr('src', res.src);
                        AdminUI.modal.close('#editFotoModal');
                        Swal.fire('Berhasil', 'Foto berhasil diperbarui', 'success');
                    }
                }
            });
        });

        // Submit Profile
        $('#submit-profil').click(function () {
            let formData = new FormData($('#formguru')[0]);
            $.ajax({
                url: '<?= site_url("guru/updateProfil") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (res) => {
                    if (res.status) {
                        Swal.fire('Berhasil', 'Data profile berhasil disimpan', 'success');
                    } else {
                        Swal.fire('Gagal', res.message || 'Periksa kembali inputan Anda', 'error');
                    }
                }
            });
        });

        // Login Update
        $('#login-form').submit(function (e) {
            e.preventDefault();
            $.ajax({
                url: '<?= site_url("guru/updateLogin") ?>',
                type: 'POST',
                data: $(this).serialize(),
                success: (res) => {
                    if (res.status) {
                        AdminUI.modal.close('#editLoginModal');
                        Swal.fire('Berhasil', res.text, 'success');
                    } else {
                        let msg = res.errors ? Object.values(res.errors).join('<br>') : res.message;
                        Swal.fire('Gagal', msg, 'error');
                    }
                }
            });
        });

        // Signature Logic
        const canvas = document.getElementById("sig-canvas");
        const ctx = canvas.getContext("2d");
        let drawing = false;
        let prevX, prevY;

        $('#btn-create-ttd').click(() => AdminUI.modal.open('#modal-signature'));

        canvas.addEventListener("mousedown", (e) => {
            drawing = true;
            [prevX, prevY] = [e.offsetX, e.offsetY];
        });

        canvas.addEventListener("mousemove", (e) => {
            if (!drawing) return;
            ctx.beginPath();
            ctx.moveTo(prevX, prevY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();
            [prevX, prevY] = [e.offsetX, e.offsetY];
        });

        canvas.addEventListener("mouseup", () => drawing = false);

        $('#sig-clear').click(() => ctx.clearRect(0, 0, canvas.width, canvas.height));

        $('#sig-submit').click(() => {
            const dataUrl = canvas.toDataURL("image/png");
            $.ajax({
                url: '<?= site_url("guru/saveSignature") ?>',
                type: 'POST',
                data: { image: dataUrl },
                success: (res) => {
                    if (res.status) {
                        $('#img-ttd-preview').attr('src', res.src).removeClass('hidden');
                        $('#ttd-placeholder').addClass('hidden');
                        $('#btn-delete-ttd').removeClass('hidden');
                        AdminUI.modal.close('#modal-signature');
                        Swal.fire('Berhasil', res.message, 'success');
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            });
        });

        $('#btn-delete-ttd').click(() => {
            $.ajax({
                url: '<?= site_url("guru/deleteTtd") ?>',
                type: 'POST',
                success: (res) => {
                    if (res.status) {
                        $('#img-ttd-preview').addClass('hidden');
                        $('#ttd-placeholder').removeClass('hidden');
                        $('#btn-delete-ttd').addClass('hidden');
                    }
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>