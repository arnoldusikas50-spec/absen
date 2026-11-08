<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .num-tabular { font-variant-numeric: tabular-nums; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$pending = $pending ?? [];
$hariTinjau = \App\Services\AttendancePolicy::PENDING_REVIEW_HARI;
$fmtJam = function ($v) {
    $s = trim((string) $v);
    if ($s === '' || $s === '0') return null;
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})(?::\d{2})?$/', $s, $m)) return $m[2];
    return strlen($s) > 5 ? substr($s, 0, 5) : $s;
};
?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-violet-50 text-violet-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-check-fill"></i> Verifikasi Kehadiran
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Verifikasi Kehadiran</h1>
            <p class="text-xs text-slate-500 mt-1">Absensi guru dengan alpha ≥ 10 hari ditahan di sini. Terima untuk menghitung ulang status (ONTIME/LATE), tolak untuk menjadikannya ALPHA.</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <a href="<?= site_url('admin/presensi') ?>" class="hover:text-blue-600 font-semibold no-underline">Presensi</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-violet-600">Verifikasi</span>
        </nav>
    </div>

    <?php if (!empty($pending)): ?>
    <div class="alert-tw alert-warning">
        <i class="bi bi-info-circle-fill mt-0.5"></i>
        <div>Baris <strong>PENDING_VERIFICATION</strong> dihitung <strong>belum tentu</strong>: tidak masuk alpha maupun hadir di rekap &amp; surat teguran selama <strong><?= (int) $hariTinjau ?> hari</strong> sejak tanggal kehadirannya. Setelah itu cron harian menutupnya menjadi <strong>ALPHA</strong> (denda + poin + surat disamakan dengan jalur tolak). Menerima akan <strong>menghitung ulang status, poin, dan denda</strong> dari jam tersimpan (termasuk cap bulanan); menolak menjadikannya ALPHA sekarang juga.</div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-list-check"></i>
                </div>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Antrean Verifikasi (<?= count($pending) ?>)</h2>
                    <p class="text-[11px] text-slate-400">Urut tanggal terdahulu</p>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div id="emptyState" class="<?= empty($pending) ? '' : 'hidden' ?> text-center py-16">
                <div class="bg-emerald-100 rounded-full inline-flex p-6 mb-6">
                    <i class="bi bi-check-lg text-emerald-600 text-5xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-slate-400 mb-2">Tidak ada antrean verifikasi</h3>
                <p class="text-slate-400 text-sm">Semua kehadiran yang ditahan telah diproses.</p>
            </div>

            <?php if (!empty($pending)): ?>
            <div class="table-responsive-tw">
                <table class="table-tw" id="verifikasiTable" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th class="pl-4 w-10">#</th>
                            <th>Tanggal</th>
                            <th>Guru</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th class="text-right pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($pending as $p): ?>
                        <tr id="row-<?= (int) $p->id ?>">
                            <td class="pl-4 text-slate-400 num-tabular"><?= $no++ ?></td>
                            <td>
                                <div class="font-semibold text-slate-800 whitespace-nowrap"><?= date('d/m/Y', strtotime($p->tgl)) ?></div>
                                <div class="text-xs text-slate-400 mt-0.5"><?= date('l', strtotime($p->tgl)) ?></div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800"><?= esc($p->nama_guru ?? 'Guru #' . ($p->user_id ?? '-')) ?></div>
                                <div class="text-sm text-slate-400">NIP: <?= esc($p->nip ?? '-') ?></div>
                            </td>
                            <td class="num-tabular"><code class="px-2 py-1 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded"><?= esc($fmtJam($p->jam_masuk ?? null) ?? '-') ?></code></td>
                            <td class="num-tabular"><code class="px-2 py-1 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded"><?= esc($fmtJam($p->jam_pulang ?? null) ?? '-') ?></code></td>
                            <td class="pr-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn-tw btn-tw-sm btn-success js-terima" data-id="<?= (int) $p->id ?>"
                                        data-url="<?= site_url('admin/presensi/verifikasi-terima/' . $p->id) ?>" title="Verifikasi & hitung ulang">
                                        <i class="bi bi-check-lg mr-1"></i>Terima
                                    </button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-danger js-tolak" data-id="<?= (int) $p->id ?>"
                                        data-url="<?= site_url('admin/presensi/verifikasi-tolak/' . $p->id) ?>" title="Tolak menjadi ALPHA">
                                        <i class="bi bi-x-lg mr-1"></i>Tolak
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

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>
<script>
$(document).ready(function () {
    var CSRF = {};
    CSRF['<?= $csrfName ?>'] = '<?= $csrfHash ?>';

    if (window.jQuery && $.fn.DataTable) {
        if ($('#verifikasiTable').length && !$.fn.DataTable.isDataTable('#verifikasiTable')) {
            $('#verifikasiTable').DataTable({
                pageLength: 15, lengthMenu: [15, 30, 50],
                order: [[1, 'asc']], language: window.dt_lang_id || {},
                columnDefs: [{ orderable: false, targets: [5] }]
            });
        }
    }

    var setLoading = function ($btn, busy) {
        $btn.prop('disabled', busy);
        var $icon = $btn.find('i');
        if (busy) {
            $btn.data('orig', $icon.attr('class'));
            $icon.attr('class', 'spinner-tw mr-1');
        } else if ($btn.data('orig')) {
            $icon.attr('class', $btn.data('orig'));
        }
    };

    // Terima (dengan catatan opsional)
    $(document).on('click', '.js-terima', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Terima Verifikasi?',
            text: 'Status akan dihitung ulang dari jam tersimpan (ONTIME/LATE + denda + cap).',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Catatan (Opsional)',
            inputPlaceholder: 'Catatan verifikasi...',
            inputAttributes: { 'aria-label': 'Catatan', 'maxlength': '500' },
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Terima',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setLoading($btn, true);
            var data = Object.assign({ note: (result.value || '').trim() }, CSRF);
            $.post(url, data, function (res) {
                setLoading($btn, false);
                if (res.status) {
                    Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 1400, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json').fail(function () {
                setLoading($btn, false);
                Swal.fire('Gagal', 'Terjadi kesalahan komunikasi dengan server.', 'error');
            });
        });
    });

    // Tolak (dengan alasan)
    $(document).on('click', '.js-tolak', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Tolak Menjadi ALPHA?',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Tulis alasan penolakan di sini...',
            inputAttributes: { 'aria-label': 'Alasan Penolakan', 'maxlength': '500' },
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Tolak Menjadi ALPHA',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setLoading($btn, true);
            var data = Object.assign({ note: (result.value || '').trim() }, CSRF);
            $.post(url, data, function (res) {
                setLoading($btn, false);
                if (res.status) {
                    Swal.fire({ title: 'Ditolak', text: res.message, icon: 'success', timer: 1400, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json').fail(function () {
                setLoading($btn, false);
                Swal.fire('Gagal', 'Terjadi kesalahan komunikasi dengan server.', 'error');
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
