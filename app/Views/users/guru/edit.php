<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$users = $users ?? null;
$user = $user ?? null;
$guru = $guru ?? null;
$is_admin = $is_admin ?? false;
?>
<!-- Content Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-video3"></i> Akun Guru
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'User Guru') ?> — <?= esc($guru->nama_guru ?? '-') ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul ?? 'Edit Data User') ?> · username <span class="font-mono font-bold text-slate-700"><?= esc($guru->username ?? '-') ?></span></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/users') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                <?php if ($user->username === $guru->username or $is_admin): ?>
                    <?php $akunAktif = !is_null($users) && (int) ($users->active ?? 0) === 1; ?>
                    <div class="">
                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="flex flex-wrap items-center justify-between gap-2 p-5 border-b border-slate-100">
                                <?php if (is_null($users)): ?>
                                    <h2 class="text-sm font-extrabold text-slate-900"><?= esc($guru->nama_guru) ?> <span class="badge-tw badge-secondary rounded-full text-[10px]">belum aktif</span></h2>
                                    <button type="button" id="btn-aktif" class="btn-tw btn-tw-sm btn-success">
                                        <i class="bi bi-person-check-fill mr-1"></i> Aktifkan
                                    </button>
                                <?php else: ?>
                                    <h2 class="text-sm font-extrabold text-slate-900">Edit Login <?= esc($guru->nama_guru) ?></h2>
                                    <span class="badge-tw <?= $akunAktif ? 'badge-success' : 'badge-danger' ?> rounded-full text-[10px]"><?= $akunAktif ? 'Aktif' : 'Nonaktif' ?></span>
                                    <?php if ($is_admin): ?>
                                        <button type="button" id="btn-nonaktif" class="btn-tw btn-tw-sm <?= $akunAktif ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <i class="bi bi-power mr-1"></i> <?= $akunAktif ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?= form_open('userguru/editlogin', array('id' => 'change_password'), array('id' => $guru->id)) ?>
                            <div class="p-5 space-y-4">
                                <div>
                                    <label class="form-label-tw" for="first_name">Nama Depan <span class="text-red-500">*</span></label>
                                    <input type="text" id="first_name" name="first_name" maxlength="50" class="form-control-tw"
                                        value="<?= esc(is_null($users) ? '' : ($users->first_name ?? '')) ?>" placeholder="Nama Depan"
                                        <?= is_null($users) ? 'disabled' : '' ?> required>
                                </div>
                                <div>
                                    <label class="form-label-tw" for="last_name">Nama Belakang</label>
                                    <input type="text" id="last_name" name="last_name" maxlength="50" class="form-control-tw"
                                        value="<?= esc(is_null($users) ? '' : ($users->last_name ?? '')) ?>" placeholder="Nama Belakang"
                                        <?= is_null($users) ? 'disabled' : '' ?>>
                                </div>
                                <div>
                                    <label class="form-label-tw" for="email">Email <span class="text-red-500">*</span></label>
                                    <input type="email" id="email" name="email" maxlength="100" class="form-control-tw"
                                        value="<?= esc(is_null($users) ? '' : ($users->email ?? '')) ?>"
                                        placeholder="Email" required <?= is_null($users) ? 'disabled' : '' ?>>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label-tw">Username</label>
                                        <input type="text" class="form-control-tw font-mono bg-slate-50" value="<?= esc($guru->username ?? '') ?>"
                                            placeholder="Username" disabled readonly>
                                    </div>
                                    <div>
                                        <label class="form-label-tw">Password tersimpan</label>
                                        <input type="password" class="form-control-tw bg-slate-50" value="••••••••"
                                            placeholder="********" disabled readonly>
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-400">Password tidak ditampilkan demi keamanan. Gunakan kartu Ubah Password untuk mengganti.</p>
                                <div>
                                    <button type="submit" id="btn-level" class="btn-tw btn-primary w-full justify-center"
                                        <?= is_null($users) ? 'disabled' : '' ?>>
                                        <i class="bi bi-save mr-1"></i> Simpan
                                    </button>
                                </div>
                            </div>
                            <?= form_close(); ?>
                        </div>
                    </div>
                    <div class="">
                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="flex items-center gap-2 p-5 border-b border-slate-100">
                                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                    <i class="bi bi-key text-sm"></i>
                                </span>
                                <h2 class="text-sm font-extrabold text-slate-900">Ubah Password & Username</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <label class="form-label-tw" for="username">Username <span class="text-red-500">*</span></label>
                                    <input type="text" id="username" class="form-control-tw font-mono" name="username"
                                        value="<?= esc(is_null($users) ? '' : ($users->username ?? '')) ?>" placeholder="Username"
                                        <?= is_null($users) ? 'disabled' : '' ?> required minlength="4" maxlength="30">
                                </div>
                                <div>
                                    <label class="form-label-tw" for="old">Password Lama <span class="text-red-500">*</span></label>
                                    <input type="password" id="old" name="old" class="form-control-tw" placeholder="Password Lama" required autocomplete="current-password"
                                        <?= is_null($users) ? 'disabled' : '' ?>>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label-tw" for="new">Password Baru <span class="text-red-500">*</span></label>
                                        <input type="password" id="new" name="new" minlength="6" maxlength="72" class="form-control-tw" placeholder="Min. 6 karakter" required autocomplete="new-password"
                                            <?= is_null($users) ? 'disabled' : '' ?>>
                                    </div>
                                    <div>
                                        <label class="form-label-tw" for="new_confirm">Konfirmasi Password <span class="text-red-500">*</span></label>
                                        <input type="password" id="new_confirm" name="new_confirm" class="form-control-tw"
                                            placeholder="Ulangi password baru" required autocomplete="new-password" <?= is_null($users) ? 'disabled' : '' ?>>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" id="btn-pass" form="change_password" class="btn-tw btn-warning flex-1 justify-center"
                                        <?= is_null($users) ? 'disabled' : '' ?>><i class="bi bi-key mr-1"></i> Ganti Password
                                    </button>
                                    <button type="reset" class="btn-tw btn-light"
                                        <?= is_null($users) ? 'disabled' : '' ?>>
                                        <i class="bi bi-arrow-counterclockwise mr-1"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function () {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    const guruId = '<?= (int) $guru->id_guru ?>';
    const userId = '<?= (int) ($users->id ?? 0) ?>';
    const namaGuru = <?= json_encode($guru->nama_guru ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function spinBtn($btn, busy, orig) {
        if (busy) {
            $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span> Memproses...');
        } else {
            $btn.prop('disabled', false).html(orig);
        }
    }
    function errHtml(errors) {
        if (!errors || typeof errors !== 'object') return 'Periksa kembali inputan Anda.';
        const out = Object.values(errors).filter(v => v != null && v !== '');
        return out.length ? '<ul style="list-style:none;margin:0;padding:0"><li>' + out.join('</li><li>') + '</li></ul>' : 'Periksa kembali inputan Anda.';
    }
    function postCsrf(url, data) {
        data[CSRF_NAME] = CSRF_HASH;
        return $.post(url, data);
    }

    // ── Aktif / nonaktif akun (tombol sebelumnya mati tanpa handler) ──
    function setAktif(aktifkan) {
        const payload = {
            id: userId || '',
            id_guru: guruId,
            nama: namaGuru,
            is_active: aktifkan ? 'aktifkan' : 'nonaktifkan'
        };
        Swal.fire({
            title: aktifkan ? 'Aktifkan akun guru ini?' : 'Nonaktifkan akun guru ini?',
            text: aktifkan ? 'Guru dapat login kembali.' : 'Guru tidak dapat login sampai diaktifkan lagi.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: aktifkan ? '#16a34a' : '#d97706',
            confirmButtonText: aktifkan ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            postCsrf('<?= site_url('userguru/activate') ?>', payload)
                .done((res) => {
                    if (res && res.status) {
                        Swal.fire('Berhasil', res.msg || 'Status diubah.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', (res && res.msg) || 'Gagal mengubah status.', 'error');
                    }
                })
                .fail(() => Swal.fire('Error', 'Gagal menghubungi server.', 'error'));
        });
    }
    $('#btn-aktif').on('click', () => setAktif(true));
    $('#btn-nonaktif').on('click', function () {
        setAktif($(this).text().toLowerCase().includes('nonaktif') ? false : true);
    });

    // ── Simpan info dasar (nama/email) ──
    $('form#change_password').on('submit', function (e) {
        // Hanya tangani submit dari tombol Simpan info (btn-level); submit Ganti
        // Password (btn-pass) memakai handler-nya sendiri di bawah.
        if (document.activeElement && document.activeElement.id === 'btn-pass') return;
        e.preventDefault();
        e.stopImmediatePropagation();

        const btn = $('#btn-level');
        const orig = btn.html();
        spinBtn(btn, true);

        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            type: 'POST',
            dataType: 'json',
            success: function (res) {
                spinBtn(btn, false, orig);
                if (res && res.status) {
                    Swal.fire('Berhasil', 'Data login berhasil diupdate.', 'success');
                } else {
                    Swal.fire('Gagal', (res && (res.msg || errHtml(res.errors))) || 'Gagal menyimpan.', 'error');
                }
            },
            error: function () {
                spinBtn(btn, false, orig);
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // ── Ganti password + username ──
    $('#btn-pass').on('click', function (e) {
        e.preventDefault();
        const pwd = $('#new').val() || '';
        const pwd2 = $('#new_confirm').val() || '';
        if (pwd.length < 6) {
            Swal.fire('Perhatian', 'Password baru minimal 6 karakter.', 'warning');
            return;
        }
        if (pwd !== pwd2) {
            Swal.fire('Perhatian', 'Konfirmasi password tidak cocok.', 'warning');
            return;
        }
        const btn = $(this);
        const orig = btn.html();
        spinBtn(btn, true);

        const payload = {
            id: userId,
            id_guru: guruId,
            old_username: <?= json_encode($guru->username ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            username: $('#username').val(),
            old: $('#old').val(),
            new: pwd,
            new_confirm: pwd2
        };
        payload[CSRF_NAME] = CSRF_HASH;

        $.post('<?= site_url('userguru/editlogin') ?>', payload, function (res) {
            spinBtn(btn, false, orig);
            if (res && res.status) {
                Swal.fire('Berhasil', res.msg || 'Login diperbarui.', 'success');
            } else {
                Swal.fire('Gagal', (res && (res.msg || errHtml(res.errors))) || 'Gagal memperbarui login.', 'error');
            }
        }, 'json').fail(function () {
            spinBtn(btn, false, orig);
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        });
    });
});
</script>
<?= $this->endSection() ?>
