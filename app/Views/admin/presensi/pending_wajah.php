<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .num-tabular {
        font-variant-numeric: tabular-nums;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-camera-fill"></i> Verifikasi Biometrik
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Pengajuan Registrasi Ulang Wajah') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Persetujuan perubahan data wajah guru sebelum diterapkan ke verifikasi absensi.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                <i class="bi bi-hourglass-split text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Permintaan Registrasi Ulang Wajah</h2>
            <span class="ml-auto badge-tw badge-warning rounded-full text-[11px]">
                <?= $stats['pending'] ?? 0 ?> Menunggu
            </span>
        </div>
        <div class="p-5">
            <?php if (empty($pending)): ?>
                <div class="text-center py-12">
                    <div class="bg-emerald-50 rounded-full inline-flex p-4 mb-4">
                        <i class="bi bi-check-lg text-emerald-600 text-4xl"></i>
                    </div>
                    <h6 class="font-bold text-slate-500 mb-1">Tidak ada pengajuan menunggu</h6>
                    <p class="text-slate-400 text-sm mb-0">Semua pengajuan registrasi ulang wajah telah diproses.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw">
                        <thead>
                            <tr>
                                <th class="text-center w-10">#</th>
                                <th>Nama Guru</th>
                                <th>NIP</th>
                                <th>Tanggal Pengajuan</th>
                                <th class="text-center">Status Wajah Lama</th>
                                <th class="text-right" style="min-width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pendingWajahBody">
                            <?php foreach ($pending as $i => $p): ?>
                            <tr id="row-<?= (int) $p->id ?>" class="pending-row">
                                <td class="text-center text-slate-400 text-xs"><?= $i + 1 ?></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <?php
                                        $foto = '';
                                        $fotoRaw = trim((string) ($p->foto ?? ''));
                                        if ($fotoRaw !== '') {
                                            // Dukung path relatif penuh maupun nama file saja
                                            $fotoPath = (strpos($fotoRaw, '/') !== false || strpos($fotoRaw, 'uploads/') === 0)
                                                ? ltrim($fotoRaw, '/')
                                                : 'uploads/profile_guru/' . $fotoRaw;
                                            if (file_exists(FCPATH . $fotoPath)) {
                                                $foto = base_url($fotoPath);
                                            }
                                        }
                                        ?>
                                        <?php if ($foto): ?>
                                            <img src="<?= esc($foto, 'attr') ?>" class="rounded-full object-cover" width="36" height="36" alt="Foto guru" loading="lazy">
                                        <?php else: ?>
                                            <div class="rounded-full bg-blue-50 flex items-center justify-center shrink-0" style="width:36px;height:36px;">
                                                <i class="bi bi-person text-blue-600"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <span class="font-bold text-slate-800 text-sm block truncate"><?= esc($p->nama_guru ?: trim($p->first_name . ' ' . $p->last_name)) ?></span>
                                            <span class="text-xs text-slate-400">@<?= esc($p->username) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-slate-400 text-sm font-mono"><?= esc($p->nip ?? '-') ?></span></td>
                                <td>
                                    <span class="text-slate-700 text-sm whitespace-nowrap">
                                        <i class="bi bi-clock mr-1 text-slate-400"></i><?= date('d/m/Y H:i', strtotime($p->created_at)) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-tw badge-warning rounded-full text-[11px]">
                                        <i class="bi bi-exclamation-triangle mr-1"></i>Sudah pernah daftar
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex gap-2 items-center">
                                        <button type="button" class="btn-tw btn-tw-sm btn-success btn-approve-wajah" data-id="<?= (int) $p->id ?>" title="Setujui & aktifkan wajah baru">
                                            <i class="bi bi-check-lg mr-1"></i> Setujui
                                        </button>
                                        <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-reject-wajah" data-id="<?= (int) $p->id ?>" title="Tolak, wajah lama tetap berlaku">
                                            <i class="bi bi-x-lg mr-1"></i> Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit History -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex items-center gap-2 p-5 border-b border-slate-100">
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="bi bi-clock-history text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Riwayat Pengajuan yang Telah Diproses</h2>
            <?php if (!empty($history)): ?>
            <span class="badge-tw badge-secondary rounded-full text-[11px]"><?= count($history) ?> data</span>
            <button type="button" id="btnClearWajahHistory"
                class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-red-600 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-colors"
                title="Hapus seluruh riwayat yang telah diproses">
                <i class="bi bi-trash3"></i> Hapus Riwayat
            </button>
            <?php endif; ?>
        </div>
        <div class="p-5">
            <?php if (empty($history)): ?>
                <p class="text-slate-400 text-sm mb-0">Belum ada pengajuan wajah yang diproses.</p>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw">
                        <thead>
                            <tr>
                                <th>Guru</th>
                                <th>Tanggal Diproses</th>
                                <th class="text-center">Status</th>
                                <th>Diproses Oleh / Catatan</th>
                                <th class="text-center" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="wajahHistoryBody">
                            <?php foreach ($history as $h): ?>
                            <tr id="history-row-<?= (int) $h->id ?>">
                                <td>
                                    <span class="font-bold text-slate-800 text-sm block"><?= esc($h->nama_guru ?: trim($h->first_name . ' ' . $h->last_name)) ?></span>
                                    <span class="text-xs text-slate-400">@<?= esc($h->username) ?></span>
                                </td>
                                <td class="text-sm text-slate-400 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($h->updated_at)) ?></td>
                                <td class="text-center">
                                    <?php if ($h->status === 'approved'): ?>
                                        <span class="badge-tw badge-success rounded-full text-[11px]"><i class="bi bi-check-lg mr-1"></i>Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge-tw badge-danger rounded-full text-[11px]"><i class="bi bi-x-lg mr-1"></i>Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm">
                                    <?php if (!empty($h->admin_first)): ?>
                                        <div class="text-slate-700"><i class="bi bi-person-check mr-1"></i><?= esc(trim($h->admin_first . ' ' . ($h->admin_last ?? ''))) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($h->keterangan)): ?>
                                        <div class="text-xs text-slate-500"><?= esc($h->keterangan) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn-tw btn-tw-sm btn-light btn-delete-wajah-history"
                                        data-id="<?= (int) $h->id ?>"
                                        title="Hapus riwayat ini">
                                        <i class="bi bi-trash3 text-red-400"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div><!-- /page wrapper -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    function spinTW() {
        return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>';
    }

    // Reload hanya bila antrean benar-benar habis (perbaikan: cek sisa baris, bukan id yang baru dihapus)
    function afterRowProcessed(id) {
        const $row = $('#row-' + id);
        $row.fadeOut(300, function () {
            $(this).remove();
            if ($('#pendingWajahBody .pending-row').length === 0) {
                location.reload();
            }
        });
    }

    function approveWajah(id) {
        Swal.fire({
            title: 'Setujui Pengajuan Ulang Wajah?',
            text: 'Wajah lama guru akan segera diganti. Guru dapat memakai wajah baru untuk verifikasi absensi.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-check-lg mr-1"></i> Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const data = {};
            data[CSRF_NAME] = CSRF_HASH;

            Swal.fire({
                title: 'Memproses...',
                html: spinTW() + ' Mengaktifkan wajah baru guru.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.post('<?= site_url('admin/presensi/wajah/approve') ?>/' + encodeURIComponent(id), data, function (res) {
                if (res && res.status) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () { afterRowProcessed(id); });
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        });
    }

    function rejectWajah(id) {
        Swal.fire({
            title: 'Tolak Pengajuan?',
            text: 'Wajah lama guru akan tetap berlaku dan tidak diubah.',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan (Maks 255 karakter)',
            inputPlaceholder: 'Tulis alasan penolakan disini...',
            inputAttributes: { 'aria-label': 'Alasan Penolakan', maxlength: 255 },
            inputValidator: (value) => {
                if (value && value.length > 255) return 'Alasan maksimal 255 karakter';
            },
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-x-lg mr-1"></i> Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;
            const data = { keterangan: (result.value || '').trim() };
            data[CSRF_NAME] = CSRF_HASH;

            Swal.fire({
                title: 'Memproses...',
                html: spinTW() + ' Menolak pengajuan wajah.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.post('<?= site_url('admin/presensi/wajah/reject') ?>/' + encodeURIComponent(id), data, function (res) {
                if (res && res.status) {
                    Swal.fire({
                        title: 'Ditolak',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () { afterRowProcessed(id); });
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        });
    }

    // Delegated bindings (ganti onclick inline)
    $(document).on('click', '.btn-approve-wajah', function () { approveWajah($(this).data('id')); });
    $(document).on('click', '.btn-reject-wajah', function () { rejectWajah($(this).data('id')); });

    // ── Hapus satu riwayat ──────────────────────────────────
    $(document).on('click', '.btn-delete-wajah-history', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Riwayat Ini?',
            text: 'Baris riwayat pengajuan wajah ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            const data = {};
            data[CSRF_NAME] = CSRF_HASH;
            $.post('<?= site_url('admin/presensi/wajah/delete') ?>/' + encodeURIComponent(id), data, function (res) {
                if (res && res.status) {
                    $('#history-row-' + id).fadeOut(300, function () {
                        $(this).remove();
                        if ($('#wajahHistoryBody tr').length === 0) location.reload();
                    });
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        });
    });

    // ── Hapus seluruh riwayat ───────────────────────────────
    $(document).on('click', '#btnClearWajahHistory', function () {
        Swal.fire({
            title: 'Hapus Seluruh Riwayat?',
            text: 'Semua riwayat pengajuan yang telah diproses akan dihapus permanen. Pengajuan pending tidak ikut terhapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            const data = {};
            data[CSRF_NAME] = CSRF_HASH;
            $.post('<?= site_url('admin/presensi/wajah/clear-history') ?>', data, function (res) {
                if (res && res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1600, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        });
    });

    window.approveWajah = approveWajah;
    window.rejectWajah = rejectWajah;
});
</script>
<?= $this->endSection() ?>