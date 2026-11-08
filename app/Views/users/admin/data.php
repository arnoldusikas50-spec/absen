<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3>
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600 mr-2 shrink-0">
                    <i class="bi bi-person-badge text-sm"></i>
                </span>
                <?= esc($judul ?? 'Manajemen Admin') ?>
            </h3>
            <p class="mt-0.5 text-xs text-slate-400 ml-10">Kelola akun administrator sistem</p>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline">
                <i class="bi bi-house-door mr-1"></i>Dashboard
            </a>
            <span class="sep">/</span>
            <span class="active"><?= esc($subjudul ?? 'Data') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">
    <div class="card-tw">
        <div class="card-tw-header">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="bi bi-person-badge text-blue-600 text-sm"></i>
                </div>
                <h6><?= esc($subjudul ?? 'Daftar Admin') ?></h6>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" onclick="reload_ajax()"
                        class="btn-tw btn-tw-sm btn-light"
                        title="Refresh data">
                    <i class="bi bi-arrow-repeat"></i>
                    <span class="hidden sm:inline">Reload</span>
                </button>
                <button type="button" data-ui-toggle="modal" data-ui-target="#createAdminModal"
                        class="btn-tw btn-tw-sm btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span class="hidden sm:inline">Tambah Admin</span>
                </button>
            </div>
        </div>
        <div class="card-tw-body p-0">
            <div class="table-responsive-tw">
                <table id="users" class="table-tw w-full">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:48px">No.</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th class="hidden md:table-cell">Dibuat</th>
                            <th class="hidden sm:table-cell text-center">Level</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $num => $admin): ?>
                        <tr>
                            <td class="text-center text-slate-400 font-mono text-xs"><?= $num + 1 ?></td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600
                                                flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        <?= strtoupper(substr($admin->first_name, 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate leading-tight">
                                            <?= esc($admin->first_name . ' ' . $admin->last_name) ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">
                                    <?= esc($admin->username) ?>
                                </span>
                            </td>
                            <td class="hidden md:table-cell text-slate-400 text-xs">
                                <?= esc($admin->created_on) ?>
                            </td>
                            <td class="hidden sm:table-cell text-center">
                                <span class="badge-tw badge-primary"><?= esc($admin->level) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($admin->active == 1): ?>
                                    <span class="badge-tw badge-success">
                                        <i class="bi bi-check-circle-fill text-[9px]"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge-tw badge-danger">
                                        <i class="bi bi-x-circle-fill text-[9px]"></i> Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($admin->id === $user->id): ?>
                                    <div class="flex justify-center">
                                        <a href="<?= base_url('useradmin/edit/' . $admin->id) ?>"
                                           class="btn-tw btn-tw-sm btn-primary" title="Edit Profil">
                                            <i class="bi bi-gear-fill"></i>
                                        </a>
                                    </div>
                                <?php elseif ($user->id == "1"): ?>
                                    <div class="flex justify-center gap-1">
                                        <a href="<?= base_url('useradmin/edit/' . $admin->id) ?>"
                                           class="btn-tw btn-tw-sm btn-info" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                                class="btn-tw btn-tw-sm btn-danger"
                                                onclick="hapus(<?= $admin->id ?>)" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-center">
                                        <span class="text-slate-300" title="Tidak ada akses">
                                            <i class="bi bi-ban"></i>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create -->
<?= form_open('useradmin/create', array('id' => 'create')) ?>
<div class="modal fade" id="createAdminModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-box-tw max-w-md" role="document">
        <div class="modal-header-tw">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                    <i class="bi bi-person-plus-fill text-blue-600"></i>
                </div>
                <div>
                    <h5 id="createModalLabel">Tambah Admin Baru</h5>
                    <p class="text-xs text-slate-400 mt-0.5">Isi data akun administrator</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body-tw">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label-tw" for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name"
                           class="form-control-tw" placeholder="Budi" required>
                </div>
                <div>
                    <label class="form-label-tw" for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name"
                           class="form-control-tw" placeholder="Santoso" required>
                </div>
            </div>
            <div class="mt-4">
                <label class="form-label-tw" for="username">Username</label>
                <input type="text" name="username" id="username"
                       class="form-control-tw" placeholder="budi.admin" required>
            </div>
            <div class="mt-4">
                <label class="form-label-tw" for="password">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password"
                           class="form-control-tw pr-10" placeholder="Min. 8 karakter" required>
                    <button type="button" onclick="togglePwd('password', this)"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="bi bi-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <div class="mt-4">
                <label class="form-label-tw" for="confirm_password">Konfirmasi Password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirm_password"
                           class="form-control-tw pr-10" placeholder="Ulangi password" required>
                    <button type="button" onclick="togglePwd('confirm_password', this)"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="bi bi-eye text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer-tw">
            <button type="button" class="btn-tw btn-light" data-ui-dismiss="modal">Batal</button>
            <button type="reset"  class="btn-tw btn-light">
                <i class="bi bi-arrow-repeat"></i> Reset
            </button>
            <button type="submit" class="btn-tw btn-primary">
                <i class="bi bi-check-lg"></i> Simpan
            </button>
        </div>
    </div>
</div>
<?= form_close() ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url() ?>/assets/app/js/ajax_request.js" type="text/javascript"></script>
<script>
    // Toggle password visibility
    function togglePwd(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash text-sm';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye text-sm';
        }
    }
    // reload_ajax — global scope (called from button onclick)
    function reload_ajax() {
        if ($.fn.DataTable.isDataTable('#users')) {
            $('#users').DataTable().ajax.reload();
        } else {
            window.location.reload();
        }
    }

    $(document).ready(function () {

        $("#users").DataTable({
            order: [[0, "asc"]]
        });

        $('#create').on('submit', async function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            console.log("data:", $(this).serialize());

            let formData = new FormData($(this)[0]);
            await ajaxPost(base_url + "useradmin/create", formData, function (error, response) {
                $('#createAdminModal').appModal('hide');

                if (error) {
                    console.log('error', error);
                    Swal.fire({
                        title: "Error",
                        text: error,
                        icon: "error"
                    });
                } else {
                    console.log('response', response);
                    if (response.status) {
                        Swal.fire({
                            title: "Sukses",
                            text: response.msg, // Changed from response.text to response.msg based on controller
                            icon: "success"
                        }).then(result => {
                            if (result.value) {
                                window.location.reload();
                            }
                        });
                    } else {
                        var html = '<ul>';
                        if (response.errors.username != null && response.errors.username !== "") {
                            html += '<li>' + response.errors.username + '';
                        }
                        if (response.errors.first_name != null && response.errors.first_name !== "") {
                            html += '<li>' + response.errors.first_name + '';
                        }
                        if (response.errors.password != null && response.errors.password !== "") {
                            html += '<li>' + response.errors.password + '';
                        }
                        if (response.errors.confirm_password != null && response.errors.confirm_password !== "") {
                            html += '<li>' + response.errors.confirm_password + '';
                        }
                        html += '</ul>';
                        Swal.fire({
                            title: "ERROR",
                            html: html,
                            icon: "error",
                            showCancelButton: false,
                        });
                    }
                }
            });
        });
    });

    function hapus(id) {
        Swal.fire({
            title: "Anda yakin?",
            text: "Data akan dihapus.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Hapus!"
        }).then(async result => {
            if (result.value) {
                ajaxGet(base_url + "useradmin/delete/" + id, function (error, response) {
                    if (error) {
                        console.log('error', error);
                        Swal.fire({
                            title: "Gagal",
                            text: "User gagal dihapus",
                            icon: "error"
                        });
                    } else {
                        console.log('response', response);
                        Swal.fire({
                            title: response.status ? "Berhasil" : "Gagal",
                            text: response.status ? "User berhasil dihapus" : "User gagal dihapus",
                            icon: response.status ? "success" : "error"
                        }).then(result => {
                            if (result.value) {
                                window.location.reload();
                            }
                        });
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>
