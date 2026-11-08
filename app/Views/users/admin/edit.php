<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-gear"></i> Akun Administrator
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Administrator') ?> — <?= esc(trim(($users->first_name ?? '') . ' ' . ($users->last_name ?? '')) ?: ($users->username ?? '')) ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul ?? 'Edit Data Admin') ?> · username <span class="font-mono font-bold text-slate-700"><?= esc($users->username ?? '-') ?></span></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/users') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
            <?php if ($is_admin): ?>
                <div class="">
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <div class="flex items-center gap-2 p-5 border-b border-slate-100">
                            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-person-badge text-sm"></i>
                            </span>
                            <h2 class="text-sm font-extrabold text-slate-900"><?= esc($subjudul) ?></h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <?php if ((int) $user->id === (int) $users->id || $is_admin): ?>
                                    <div class="md:col-span-5">
                                        <?php
                                        $rawFoto = trim((string) ($users->foto ?? ''));
                                        $fotoPath = '';
                                        if ($rawFoto !== '' && !str_contains($rawFoto, '..')) {
                                            $cleanPath = ltrim($rawFoto, '/');
                                            if (file_exists(FCPATH . $cleanPath) && is_file(FCPATH . $cleanPath)) {
                                                $fotoPath = $cleanPath;
                                            } elseif (file_exists(FCPATH . 'uploads/profiles/' . $cleanPath) && is_file(FCPATH . 'uploads/profiles/' . $cleanPath)) {
                                                $fotoPath = 'uploads/profiles/' . $cleanPath;
                                            }
                                        }
                                        $defaultPhotoUrl = ($fotoPath !== '') ? base_url($fotoPath) : base_url('assets/img/admin.png');
                                        ?>
                                        <?= form_open_multipart('', array('id' => 'set-foto'), ['id_user' => $users->id]) ?>
                                        <div class="mb-4">
                                            <label class="form-label-tw" for="foto">Foto Profil</label>
                                            <input type="file" id="foto" name="foto" class="dropify"
                                                data-max-file-size="5M" data-allowed-file-extensions="jpg jpeg png webp gif"
                                                data-default-file="<?= esc($defaultPhotoUrl, 'attr') ?>" />
                                            <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                                                <i class="bi bi-info-circle"></i> Format: JPG, PNG, WEBP, GIF. Maksimal 5MB.
                                            </p>
                                        </div>
                                        <?= form_close() ?>
                                    </div>
                                <?php endif; ?>
                                <div class="md:col-span-7">
                                    <?= form_open('useradmin/edit_info', array('id' => 'user_info'), array('id' => $users->id)) ?>
                                    <div class="mb-4">
                                        <label class="form-label-tw" for="first_name">First Name <span class="text-red-500">*</span></label>
                                        <input type="text" id="first_name" name="first_name" maxlength="50" required class="form-control-tw"
                                            value="<?= esc($users->first_name ?? '') ?>">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label-tw" for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" maxlength="50" class="form-control-tw"
                                            value="<?= esc($users->last_name ?? '') ?>">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label-tw" for="email">Email</label>
                                        <input type="email" id="email" name="email" maxlength="100" class="form-control-tw" value="<?= esc($users->email ?? '') ?>">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label-tw" for="jabatan">Jabatan</label>
                                        <input type="text" id="jabatan" name="jabatan" maxlength="50" placeholder="Jabatan" class="form-control-tw"
                                            value="<?= esc($users->jabatan ?? '') ?>">
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="reset" class="btn-tw btn-light"><i
                                                class="bi bi-arrow-counterclockwise"></i> Reset</button>
                                        <button type="submit" id="btn-info" class="btn-tw btn-primary"><i
                                                class="bi bi-save mr-1"></i> Simpan</button>
                                    </div>
                                    <?= form_close() ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ((int) $user->id === (int) $users->id || $is_admin): ?>
                <div class="">
                    <?= form_open('useradmin/change_password', array('id' => 'change_password'), array('id' => $users->id, 'username' => $users->username)) ?>
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <div class="flex items-center gap-2 p-5 border-b border-slate-100">
                            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-key text-sm"></i>
                            </span>
                            <h2 class="text-sm font-extrabold text-slate-900">Edit Data Login</h2>
                        </div>
                        <div class="p-5">
                            <div class="mb-4">
                                <label class="form-label-tw" for="username">Username</label>
                                <input type="text" id="username" name="username" maxlength="30" class="form-control-tw font-mono" value="<?= esc($users->username ?? '') ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label-tw" for="old">Password Lama</label>
                                <input type="password" id="old" placeholder="Password Lama" name="old" autocomplete="current-password" class="form-control-tw">
                            </div>
                            <div class="mb-4">
                                <label class="form-label-tw" for="new">Password Baru</label>
                                <input type="password" id="new" placeholder="Password Baru (min. 6)" name="new" minlength="6" maxlength="72" autocomplete="new-password" class="form-control-tw">
                            </div>
                            <div class="mb-4">
                                <label class="form-label-tw" for="new_confirm">Konfirmasi Password</label>
                                <input type="password" id="new_confirm" placeholder="Konfirmasi Password Baru" name="new_confirm" autocomplete="new-password" class="form-control-tw">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="reset" class="btn-tw btn-light"><i
                                        class="bi bi-arrow-counterclockwise"></i>
                                    Reset</button>
                                <button type="submit" id="btn-pass" class="btn-tw btn-primary"><i
                                        class="bi bi-key mr-1"></i> Ganti Password</button>
                            </div>
                        </div>
                    </div>
                    <?= form_close() ?>
                </div>
            <?php endif; ?>
        </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/plugins/dropify/js/dropify.min.js') ?>"></script>
<script src="<?= base_url('assets/app/js/ajax_request.js') ?>" type="text/javascript"></script>

<script>
$(document).ready(function () {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    const idUser = '<?= (int) $users->id ?>';
    const curUser = '<?= (int) $user->id ?>';

    function spinBtn($btn, busy, label) {
        if (busy) {
            $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span> Memproses...');
        } else {
            $btn.prop('disabled', false).html(label);
        }
    }
    function errList(errors, keys) {
        if (!errors) return 'Periksa kembali inputan Anda.';
        const out = [];
        (keys || Object.keys(errors)).forEach(k => {
            if (errors[k] != null && errors[k] !== '') out.push('<li>' + errors[k] + '</li>');
        });
        return out.length ? '<ul style="list-style:none;margin:0;padding:0">' + out.join('') + '</ul>' : 'Periksa kembali inputan Anda.';
    }

    if ($.fn.dropify) {
        var drEvent = $('.dropify').dropify({
            messages: {
                'default': 'Seret foto ke sini atau klik',
                'replace': 'Seret atau klik untuk mengganti foto',
                'remove': 'Hapus',
                'error': 'Ukuran atau format tidak valid.'
            },
            error: {
                'fileSize': 'Ukuran file terlalu besar (maks {{ value }}).',
                'imageFormat': 'Format gambar tidak diizinkan ({{ value }} saja).'
            }
        });

        drEvent.on('dropify.afterClear', function (event, element) {
            deleteImage();
        });

        drEvent.on('dropify.errors', function () {
            Swal.fire('Error', 'File rusak atau tidak memenuhi syarat (JPG/PNG/WEBP, maks 5MB).', 'warning');
        });
    }

    $("#foto").change(function () {
        var input = $(this)[0];
        if (input.files && input.files[0]) {
            if (input.files[0].size > 5 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran foto maksimal 5MB.', 'warning');
                $(this).val('');
                return;
            }
            var form = new FormData($('#set-foto')[0]);
            uploadAttach(base_url + 'useradmin/uploadfile/' + idUser, form);
        }
    });

    function uploadAttach(action, data) {
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            url: action,
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 600000,
            success: function (res) {
                if (res && res.status) {
                    Swal.fire('Berhasil', (res && res.message) || 'Foto profil diperbarui.', 'success');
                    if (res.src) {
                        $('#foto').attr('data-default-file', res.src);
                    }
                } else {
                    Swal.fire('Gagal', (res && res.message) || (res && res.msg) || 'Gagal mengunggah foto.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            }
        });
    }

    function deleteImage() {
        var src = $('#foto').attr('data-default-file') || '';
        var form = new FormData($('#set-foto')[0]);
        form.append('src', src);
        form.append('id_user', idUser);
        $.ajax({
            data: form,
            type: "POST",
            url: base_url + "useradmin/deletefile",
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json',
            success: function (res) {
                if (!(res && res.status)) {
                    Swal.fire('Gagal', (res && res.message) || (res && res.msg) || 'Gagal menghapus foto.', 'error');
                } else {
                    $('#foto').attr('data-default-file', '');
                    Swal.fire('Berhasil', (res && res.message) || 'Foto profil berhasil dihapus.', 'success');
                }
            },
            error: function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            }
        });
    }

    $('form#change_password').on('submit', async function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let btn = $('#btn-pass');
        const origLabel = btn.html();
        spinBtn(btn, true);

        const url = $(this).attr('action'); //useradmin/change_password
        let formData = new FormData($(this)[0]);

        Swal.fire({
            text: "Silahkan tunggu....",
            allowEscapeKey: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        await ajaxPost(url, formData, function (error, response) {
            if (error) {
                Swal.fire({ title: "Error", text: error, icon: "error" });
            } else {
                if (response && response.status) {
                    Swal.fire({
                        title: "Sukses",
                        text: response.msg || 'Password diperbarui.',
                        icon: "success"
                    }).then(result => {
                        if (result.isConfirmed || result.value) {
                            if (idUser === curUser) {
                                window.location.href = base_url + 'auth/logout';
                            } else {
                                window.location.reload();
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        title: "ERROR",
                        html: errList(response && response.errors, ['username', 'old', 'new', 'new_confirm']),
                        icon: "error",
                        showCancelButton: false,
                    });
                }
            }
        });

        spinBtn(btn, false, origLabel);
    });

    $('form#user_info').on('submit', async function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        let btn = $('#btn-info');
        const origLabel = btn.html();
        spinBtn(btn, true);

        const url = $(this).attr('action'); //useradmin/edit_info
        let formData = new FormData($(this)[0]);

        Swal.fire({
            text: "Silahkan tunggu....",
            allowEscapeKey: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        await ajaxPost(url, formData, function (error, response) {
            spinBtn(btn, false, origLabel);
            if (error) {
                Swal.fire({ title: "Error", text: error, icon: "error" });
            } else {
                if (response && response.status) {
                    Swal.fire({
                        title: "Sukses",
                        text: "Data Admin berhasil diupdate",
                        icon: "success"
                    }).then(result => {
                        if (result.isConfirmed || result.value) {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: "ERROR",
                        html: errList(response && response.errors, ['first_name']),
                        icon: "error",
                        showCancelButton: false,
                    });
                }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>