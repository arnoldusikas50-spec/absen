<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">
    <!-- Page Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-clock-history"></i> Jadwal Fleksibel
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Shift Kerja Guru') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Guru tanpa shift memakai jadwal harian global. Penugasan hari spesifik mengalahkan penugasan semua-hari.</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <a href="<?= site_url('admin/presensi') ?>" class="hover:text-blue-600 font-semibold no-underline">Presensi</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-blue-600">Shift Kerja</span>
        </nav>
    </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-clock text-sm"></i>
                    </span>
                    <h2 class="text-sm font-extrabold text-slate-900">Daftar Shift</h2>
                    <?php if (!empty($shifts)): ?>
                    <span class="badge-tw badge-secondary rounded-full text-[11px]"><?= count($shifts) ?> shift</span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-tw btn-tw-sm btn-primary btn-add-shift">
                    <i class="bi bi-plus-lg mr-1"></i> Tambah Shift
                </button>
            </div>
            <div class="p-5">
                <?php if (empty($shifts)): ?>
                <div class="text-center py-12">
                    <i class="bi bi-clock-history text-slate-300 text-5xl block mb-3"></i>
                    <p class="text-slate-400 mb-0">Belum ada shift. Semua guru memakai jadwal global.</p>
                    <button type="button" class="btn-tw btn-tw-sm btn-primary mt-5 btn-add-shift">
                        <i class="bi bi-plus-lg mr-1"></i> Buat Shift Pertama
                    </button>
                </div>
                <?php else: ?>
                <?php
                $hariNama = [0 => 'Semua hari', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                ?>
                <div class="space-y-4">
                    <?php foreach ($shifts as $s): ?>
                    <?php
                    $isActive = (int) ($s->active ?? 1) === 1;
                    // ENT_QUOTES: nama dengan kutip satu (mis. O'Brien) merusak onclick tanpa ini
                    $shiftJson = htmlspecialchars(json_encode(["id" => $s->id, "nama" => $s->nama ?? '', "jam_masuk" => substr($s->jam_masuk ?? '', 0, 5), "jam_pulang" => substr($s->jam_pulang ?? '', 0, 5), "grace_menit" => $s->grace_menit ?? null]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="rounded-xl border p-4 transition-colors <?= $isActive ? 'border-slate-200 bg-white' : 'border-dashed border-slate-300 bg-slate-50/70' ?>">
                        <!-- Baris utama shift -->
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2 flex-wrap min-w-0">
                                <span class="shrink-0 w-2.5 h-2.5 rounded-full <?= $isActive ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                <strong class="text-slate-800"><?= esc($s->nama) ?></strong>
                                <?php if (!$isActive): ?><span class="badge-tw badge-secondary">Nonaktif</span><?php endif; ?>
                                <span class="text-slate-400 text-sm font-mono"><?= substr($s->jam_masuk ?? '', 0, 5) ?> – <?= substr($s->jam_pulang ?? '', 0, 5) ?></span>
                                <span class="badge-tw badge-secondary">Tol. <?= $s->grace_menit !== null ? ((int) $s->grace_menit . ' mnt') : 'global' ?></span>
                                <span class="badge-tw badge-info"><i class="bi bi-people mr-0.5"></i><?= (int) ($counts[$s->id] ?? 0) ?> guru</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <label class="inline-flex cursor-pointer items-center gap-2" title="<?= $isActive ? 'Klik untuk menonaktifkan shift' : 'Klik untuk mengaktifkan shift' ?>"><input type="checkbox" class="peer sr-only toggle-shift" data-id="<?= $s->id ?>" <?= $isActive ? 'checked' : '' ?>><span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500/40 after:content-[''] after:absolute after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-4"></span></label>
                                <button type="button" class="btn-tw btn-tw-sm btn-outline-primary btn-edit-shift" data-shift='<?= $shiftJson ?>'>
                                    <i class="bi bi-pencil-square mr-1"></i> Ubah
                                </button>
                                <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-del-shift" data-id="<?= $s->id ?>" data-nama="<?= esc($s->nama, 'attr') ?>">
                                    <i class="bi bi-trash mr-1"></i> Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Form penugasan guru -->
                        <form class="form-assign mt-4" data-shift="<?= $s->id ?>" <?= $isActive ? '' : 'data-disabled="1"' ?>>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                                <div class="md:col-span-6">
                                    <label class="form-label-tw">Guru</label>
                                    <select name="user_id" class="form-select-tw" <?= $isActive ? 'required' : 'disabled' ?>>
                                        <option value="">— Pilih guru —</option>
                                        <?php foreach ($guruList as $g): ?>
                                        <option value="<?= $g->id ?>"><?= esc($g->nama) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="form-label-tw">Hari</label>
                                    <select name="hari" class="form-select-tw" <?= $isActive ? '' : 'disabled' ?>>
                                        <?php foreach ($hariNama as $h => $label): ?>
                                        <option value="<?= $h ?>"><?= $label ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <button type="submit" class="btn-tw w-full justify-center btn-success" <?= $isActive ? '' : 'disabled' ?>>
                                        <i class="bi bi-person-plus mr-1"></i> Tugaskan
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if (!$isActive): ?>
                        <div class="mt-2.5 text-xs text-slate-500 flex items-center gap-1.5 rounded-lg bg-slate-100 border border-slate-200 px-3 py-2">
                            <i class="bi bi-info-circle text-slate-400 shrink-0"></i>
                            Shift nonaktif diabaikan sistem dan tidak menerima penugasan baru. Aktifkan kembali untuk menugaskan guru.
                        </div>
                        <?php endif; ?>

                        <!-- Daftar guru yang ditugaskan -->
                        <div class="mt-3">
                            <?php if (empty($anggota[$s->id])): ?>
                            <p class="text-xs text-slate-400 mb-0">Belum ada guru yang ditugaskan ke shift ini.</p>
                            <?php else: ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($anggota[$s->id] as $a): ?>
                                <span class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-full px-3 py-1.5 text-xs text-slate-600">
                                    <i class="bi bi-person text-slate-400"></i>
                                    <span class="font-medium text-slate-700"><?= esc($a->nama_guru) ?></span>
                                    <span class="text-slate-400">· <?= $hariNama[(int) $a->hari] ?? $a->hari ?></span>
                                    <button type="button" title="Lepaskan penugasan"
                                        class="btn-unassign inline-flex items-center justify-center w-4 h-4 ml-0.5 rounded-full text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer p-0 border-0 bg-transparent"
                                        data-id="<?= (int) $a->id ?>" data-nama="<?= esc($a->nama_guru ?? '', 'attr') ?>">
                                        <i class="bi bi-x-lg text-xs"></i>
                                    </button>
                                </span>
                                <?php endforeach ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </div>
        </div>
</div>

<!-- Modal Tambah / Ubah Shift -->
<div id="modalShift" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white">
            <form id="formShift" autocomplete="off">
                <input type="hidden" name="id" id="shiftId">
                <div class="modal-header-tw">
                    <h5 class="font-bold text-slate-800" id="shiftModalTitle">Tambah Shift</h5>
                    <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body-tw">
                    <div class="mb-6">
                        <label class="form-label-tw">Nama Shift <span class="text-red-500">*</span></label>
                        <input type="text" class="form-control-tw" name="nama" id="shiftNama" placeholder="Contoh: Shift Sore" required maxlength="100">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-6">
                        <div>
                            <label class="form-label-tw">Jam Masuk <span class="text-red-500">*</span></label>
                            <input type="time" class="form-control-tw" name="jam_masuk" id="shiftMasuk" value="13:00" required>
                        </div>
                        <div>
                            <label class="form-label-tw">Jam Pulang <span class="text-red-500">*</span></label>
                            <input type="time" class="form-control-tw" name="jam_pulang" id="shiftPulang" value="18:00" required>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="form-label-tw">Toleransi (menit, opsional)</label>
                        <input type="number" class="form-control-tw" name="grace_menit" id="shiftGrace" min="0" max="120" placeholder="Kosong = ikut global">
                        <span class="text-xs text-slate-400 mt-1.5 block">
                            Jam pulang harus setelah jam masuk (shift overnight belum didukung).
                        </span>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary"><i class="bi bi-check2-circle mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const csrfPair = () => ({ ['<?= csrf_token() ?>']: '<?= csrf_hash() ?>' });

    // ── Buka modal tambah / ubah (delegated) ──
    $(document).on('click', '.btn-add-shift', function () { openShiftModal(); });
    $(document).on('click', '.btn-edit-shift', function () {
        let s = null;
        try { s = JSON.parse($(this).attr('data-shift') || 'null'); } catch (e) { s = null; }
        openShiftModal(s);
    });
    window.openShiftModal = openShiftModal;
    function openShiftModal(s) {
        $('#shiftId').val(s?.id || '');
        $('#shiftNama').val(s?.nama || '');
        $('#shiftMasuk').val(s?.jam_masuk || '13:00');
        $('#shiftPulang').val(s?.jam_pulang || '18:00');
        $('#shiftGrace').val(s?.grace_menit ?? '');
        $('#shiftModalTitle').text(s ? 'Ubah Shift' : 'Tambah Shift');
        $('#formShift input').removeClass('is-invalid');
        $('#modalShift').appModal('show');
        setTimeout(() => $('#shiftNama').trigger('focus'), 150);
    }

    // ── Simpan / ubah shift ──
    $('#formShift').submit(function(e) {
        e.preventDefault();

        const nama = $('#shiftNama').val().trim();
        const masuk = $('#shiftMasuk').val() || '';
        const pulang = $('#shiftPulang').val() || '';

        $('#formShift input').removeClass('is-invalid');
        if (!nama) {
            $('#shiftNama').addClass('is-invalid').trigger('focus');
            Swal.fire('Perhatian', 'Nama shift wajib diisi.', 'warning');
            return;
        }
        if (!masuk || !pulang) {
            Swal.fire('Perhatian', 'Jam masuk dan jam pulang wajib diisi.', 'warning');
            return;
        }
        if (pulang <= masuk) {
            Swal.fire('Perhatian', 'Jam pulang harus setelah jam masuk (shift overnight belum didukung).', 'warning');
            return;
        }

        const $btn = $('#formShift button[type="submit"]');
        const origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-tw mr-1"></span> Menyimpan...');

        $.post('<?= site_url('admin/presensi/shift/save') ?>',
            $('#formShift').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>',
            function(res) {
                if (res.status) {
                    Swal.fire({ icon: 'success', title: 'Tersimpan!', text: res.message, timer: 1000, showConfirmButton: false });
                    setTimeout(() => location.reload(), 450);
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    $btn.prop('disabled', false).html(origHtml);
                }
            }).fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
                $btn.prop('disabled', false).html(origHtml);
            });
    });

    // ── Hapus shift (delegated) ──
    function deleteShift(id, nama) {
        Swal.fire({
            title: 'Hapus shift' + (nama ? ' "' + nama + '"' : '') + '?',
            text: 'Semua penugasan guru pada shift ini ikut terhapus. Tindakan ini tidak bisa dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post('<?= site_url('admin/presensi/shift/delete') ?>', { id, ...csrfPair() }, function(res) {
                if (res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message, timer: 1300, showConfirmButton: false })
                        .then(() => location.reload());
                } else Swal.fire('Gagal', res.message, 'error');
            }).fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
            });
        });
    }
    $(document).on('click', '.btn-del-shift', function () {
        deleteShift($(this).data('id'), $(this).data('nama'));
    });
    window.deleteShift = deleteShift;

    // ── Tugaskan guru ke shift (delegasi: form dicetak via PHP) ──
    $(document).on('submit', '.form-assign', function(e) {
        e.preventDefault();
        const $form = $(this);

        if ($form.attr('data-disabled')) {
            Swal.fire('Perhatian', 'Aktifkan shift terlebih dahulu untuk menugaskan guru.', 'warning');
            return;
        }
        const shiftId = $form.data('shift');
        const userId = $form.find('[name="user_id"]').val();
        if (!userId) {
            Swal.fire('Perhatian', 'Pilih guru terlebih dahulu.', 'warning');
            return;
        }

        const $btn = $form.find('button[type="submit"]');
        const origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-tw spinner-tw-dark mr-1"></span> Menyimpan...');

        $.post('<?= site_url('admin/presensi/shift/assign') ?>',
            $form.serialize() + '&shift_id=' + shiftId + '&<?= csrf_token() ?>=<?= csrf_hash() ?>',
            function(res) {
                if (res.status) location.reload();
                else {
                    Swal.fire('Gagal', res.message, 'error');
                    $btn.prop('disabled', false).html(origHtml);
                }
            }).fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
                $btn.prop('disabled', false).html(origHtml);
            });
    });

    // ── Lepaskan penugasan guru (delegated) ──
    function unassignShift(id, nama) {
        Swal.fire({
            title: 'Lepaskan penugasan' + (nama ? ' "' + nama + '"' : '') + '?',
            text: 'Guru ini akan kembali memakai jadwal default untuk hari tersebut.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Ya, Lepaskan',
            cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post('<?= site_url('admin/presensi/shift/unassign') ?>', { id, ...csrfPair() }, function(res) {
                if (res.status) location.reload();
                else Swal.fire('Gagal', res.message, 'error');
            }).fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
            });
        });
    }
    $(document).on('click', '.btn-unassign', function () {
        unassignShift($(this).data('id'), $(this).data('nama'));
    });
    window.unassignShift = unassignShift;

    // ── Aktif / nonaktif shift (konfirmasi hanya saat menonaktifkan) ──
    $('.toggle-shift').change(function() {
        const $cb = $(this);
        const id = $cb.data('id');
        const on = $cb.is(':checked');

        if (!on) {
            Swal.fire({
                title: 'Nonaktifkan shift?',
                text: 'Guru yang ditugaskan akan kembali memakai jadwal default dan shift tidak menerima penugasan baru.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                confirmButtonText: 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal'
            }).then(r => {
                if (r.isConfirmed) toggleShift(id, 0, $cb);
                else $cb.prop('checked', true);
            });
        } else {
            toggleShift(id, 1, $cb);
        }
    });

    function toggleShift(id, active, $cb) {
        $cb.prop('disabled', true);
        $.post('<?= site_url('admin/presensi/shift/toggle') ?>', { id, active, ...csrfPair() }, function(res) {
            if (res.status) location.reload();
            else {
                $cb.prop('disabled', false).prop('checked', active !== 1);
                Swal.fire('Gagal', res.message, 'error');
            }
        }).fail(function() {
            $cb.prop('disabled', false).prop('checked', active !== 1);
            Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
        });
    }
</script>
<?= $this->endSection() ?>