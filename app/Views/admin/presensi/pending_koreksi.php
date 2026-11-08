<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .num-tabular { font-variant-numeric: tabular-nums; }
    .filter-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 700;
        border: 1.5px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer;
        transition: all .15s ease; white-space: nowrap;
    }
    .filter-chip:hover { border-color: var(--primary-600); color: var(--primary-600); }
    .filter-chip.active { background: var(--primary-600); border-color: var(--primary-600); color: #fff; }
    .reason-text {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; max-width: 240px;
    }
    .empty-state { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    @media (max-width: 640px) {
        .content-area { padding: 0.5rem 0.75rem 1.25rem; }
        .card-tw-body { padding: 0.875rem; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$fmtWaktu = function ($v, $tgl = null) {
    $s = trim((string) $v);
    if ($s === '' || $s === '0' || $s === 'null' || strtolower($s) === 'kosong') {
        return null;
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (.+)$/', $s, $m)) {
        return ($tgl && $m[1] === substr($tgl, 0, 10)) ? substr($m[2], 0, 5) : $m[2];
    }
    return strlen($s) > 5 ? substr($s, 0, 5) : $s;
};
$toMin = function ($hm) {
    if (!preg_match('/^(\d{1,2}):(\d{2})/', (string) $hm, $m)) return null;
    return ((int) $m[1]) * 60 + ((int) $m[2]);
};
$fieldInfo = [
    'jam_masuk'  => ['label' => 'Jam Masuk',  'icon' => 'bi-box-arrow-in-right', 'cls' => 'badge-info'],
    'jam_pulang' => ['label' => 'Jam Pulang', 'icon' => 'bi-box-arrow-right',    'cls' => 'badge-warning'],
];
$statusMap = [
    'ONTIME' => ['Tepat', 'badge-success'], 'LATE' => ['Telat', 'badge-danger'],
    'IJIN' => ['Izin', 'badge-info'], 'IZIN' => ['Izin', 'badge-info'],
    'SAKIT' => ['Sakit', 'badge-info'], 'CUTI' => ['Cuti', 'badge-secondary'],
    'ALPHA' => ['Alpha', 'badge-danger'], 'ALPA' => ['Alpha', 'badge-danger'],
    'DINAS_PENDING' => ['Dinas (pengajuan)', 'badge-warning'],
    'DINAS_APPROVED' => ['Dinas', 'badge-primary'], 'DINAS' => ['Dinas', 'badge-primary'],
    'DINAS_REJECTED' => ['Dinas ditolak', 'badge-danger'], 'WFH' => ['WFH', 'badge-primary'],
];
$history = $history ?? [];
$summaryKoreksi = $summaryKoreksi ?? ['pending' => count($pending), 'oldest' => null, 'history' => count($history)];
?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-pencil-square"></i> Verifikasi Koreksi
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Koreksi Absensi</h1>
            <p class="text-xs text-slate-500 mt-1">Tinjau dan setujui permintaan koreksi kehadiran dari guru.</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <a href="<?= site_url('admin/presensi') ?>" class="hover:text-blue-600 font-semibold no-underline">Presensi</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-amber-600">Koreksi</span>
        </nav>
    </div>

    <?php if (!empty($pending)): ?>
    <div class="alert-tw alert-warning">
        <i class="bi bi-info-circle-fill mt-0.5"></i>
        <div>Menyetujui koreksi akan <strong>menghitung ulang status, poin, dan denda</strong> hari tersebut (termasuk cap denda bulanan). Pengajuan basi lain untuk absensi + field yang sama otomatis ditolak.</div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-list-check"></i>
                </div>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Daftar Permintaan Koreksi</h2>
                    <p class="text-[11px] text-slate-400">Tinjau selisih waktu sebelum menyetujui</p>
                </div>
            </div>
            <?php if (!empty($pending)): ?>
            <div class="flex gap-2 flex-wrap items-center" role="group" aria-label="Filter field">
                <button type="button" class="filter-chip active" data-field="">Semua</button>
                <button type="button" class="filter-chip" data-field="jam_masuk"><i class="bi bi-box-arrow-in-right"></i>Masuk</button>
                <button type="button" class="filter-chip" data-field="jam_pulang"><i class="bi bi-box-arrow-right"></i>Pulang</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="p-5">
            <div id="emptyState" class="<?= empty($pending) ? '' : 'hidden' ?> text-center py-16">
                <div class="bg-emerald-100 rounded-full inline-flex p-6 mb-6">
                    <i class="bi bi-check-lg text-emerald-600 text-5xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-slate-400 mb-2">Tidak ada permintaan koreksi</h3>
                <p class="text-slate-400 text-sm">Semua permintaan koreksi telah diproses.</p>
            </div>

            <?php if (!empty($pending)): ?>
            <div class="table-responsive-tw">
                <table class="table-tw" id="koreksiTable" style="min-width:1060px;">
                    <thead>
                        <tr>
                            <th class="pl-4 w-10">#</th>
                            <th>Tanggal Absen</th>
                            <th>Guru</th>
                            <th>Field</th>
                            <th>Nilai Lama</th>
                            <th>Nilai Baru</th>
                            <th class="text-center">Selisih</th>
                            <th class="min-w-[220px]">Alasan</th>
                            <th>Diajukan</th>
                            <th class="text-right pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="koreksiBody">
                        <?php
                        $no = 1;
                        foreach ($pending as $p):
                            $fInfo  = $fieldInfo[$p->field_name] ?? ['label' => ucwords(str_replace('_', ' ', (string) $p->field_name)), 'icon' => 'bi-pencil-square', 'cls' => 'badge-info'];
                            $oldHm  = $fmtWaktu($p->old_value ?? null, $p->tgl ?? null);
                            $newHm  = $fmtWaktu($p->new_value ?? null, $p->tgl ?? null);
                            $jamIn  = $fmtWaktu($p->jam_masuk ?? null, $p->tgl ?? null);
                            $jamOut = $fmtWaktu($p->jam_pulang ?? null, $p->tgl ?? null);
                            $stIn   = strtoupper((string) ($p->status_masuk ?? ''));
                            [$stInLbl, $stInCls] = $statusMap[$stIn] ?? [($stIn !== '' ? ucwords(strtolower($stIn)) : '—'), 'badge-secondary'];
                            $attMissing = empty($p->tgl);
                            $dMin = null;
                            $om = $toMin($oldHm ?? '');
                            $nm = $toMin($newHm ?? '');
                            if ($om !== null && $nm !== null) $dMin = $nm - $om;
                        ?>
                        <tr id="row-<?= (int) $p->id ?>" data-id="<?= (int) $p->id ?>" data-field="<?= esc($p->field_name ?? '') ?>">
                            <td class="pl-4 text-slate-400 num-tabular"><?= $no++ ?></td>
                            <td>
                                <?php if ($attMissing): ?>
                                    <span class="badge-tw badge-danger"><i class="bi bi-exclamation-triangle"></i>Data absen dihapus</span>
                                <?php else: ?>
                                    <div class="font-semibold text-slate-800 whitespace-nowrap"><?= date('d/m/Y', strtotime($p->tgl)) ?></div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        Masuk <?= $jamIn ?: '-' ?><span class="badge-tw <?= $stInCls ?> ml-1"><?= esc($stInLbl) ?></span>
                                        <span class="mx-1">·</span>Pulang <?= $jamOut ?: '-' ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800"><?= esc($p->nama_guru ?? 'Guru #' . ($p->user_id ?? '-')) ?></div>
                                <div class="text-sm text-slate-400">NIP: <?= esc($p->nip ?? '-') ?></div>
                            </td>
                            <td>
                                <span class="badge-tw <?= $fInfo['cls'] ?>">
                                    <i class="bi <?= $fInfo['icon'] ?> mr-1"></i><?= esc($fInfo['label']) ?>
                                </span>
                            </td>
                            <td>
                                <code class="px-2 py-1 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded" title="<?= esc($p->old_value ?? 'Kosong') ?>">
                                    <?= esc($oldHm ?? 'Kosong') ?>
                                </code>
                            </td>
                            <td>
                                <code class="px-2 py-1 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded" title="<?= esc($p->new_value ?? '-') ?>">
                                    <?= esc($newHm ?? '-') ?>
                                </code>
                            </td>
                            <td class="text-center">
                                <?php if ($dMin === null): ?>
                                    <span class="text-slate-300">–</span>
                                <?php else: ?>
                                    <span class="badge-tw <?= $dMin === 0 ? 'badge-secondary' : ($dMin > 0 ? 'badge-warning' : 'badge-info') ?> rounded-full num-tabular" title="Baru − lama">
                                        <?= $dMin === 0 ? 'sama' : (($dMin > 0 ? '+' : '−') . abs($dMin) . ' mnt') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-slate-500 text-sm italic reason-text" title="<?= esc($p->reason ?? '-') ?>">"<?= esc($p->reason ?? '-') ?>"</span>
                            </td>
                            <td class="text-slate-400 text-sm whitespace-nowrap"><?= !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '-' ?></td>
                            <td class="pr-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if ($attMissing): ?>
                                        <span class="text-xs text-slate-400" title="Data absensi sudah dihapus — hanya bisa ditolak">Hanya tolak</span>
                                    <?php endif; ?>
                                    <button type="button" class="btn-tw btn-tw-sm btn-success js-approve" data-id="<?= (int) $p->id ?>"
                                        data-url="<?= site_url('admin/presensi/approve-koreksi/' . $p->id) ?>" title="Setujui" <?= $attMissing ? 'disabled' : '' ?>>
                                        <i class="bi bi-check-lg mr-1"></i>Setujui
                                    </button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-danger js-reject" data-id="<?= (int) $p->id ?>"
                                        data-url="<?= site_url('admin/presensi/reject-koreksi/' . $p->id) ?>" title="Tolak">
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

    <!-- Riwayat -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Riwayat Keputusan</h2>
                    <p class="text-[11px] text-slate-400">100 koreksi terakhir yang disetujui / ditolak</p>
                </div>
            </div>
            <?php if (!empty($history)): ?>
            <div class="flex gap-2 flex-wrap items-center" role="group" aria-label="Filter status riwayat">
                <button type="button" class="filter-chip hfilter active" data-hstatus="">Semua</button>
                <button type="button" class="filter-chip hfilter" data-hstatus="approved">Disetujui</button>
                <button type="button" class="filter-chip hfilter" data-hstatus="rejected">Ditolak</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-5">
            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <div class="font-bold text-slate-600">Belum ada riwayat</div>
                </div>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw" id="historyTable" style="font-size:.8rem;min-width:900px;">
                        <thead>
                            <tr>
                                <th class="pl-4">Tanggal Absen</th>
                                <th>Guru</th>
                                <th>Field</th>
                                <th>Perubahan</th>
                                <th class="text-center">Status</th>
                                <th>Catatan Admin</th>
                                <th>Diproses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h):
                                $hb = ($h->status === 'approved') ? 'badge-success' : 'badge-danger';
                                $hl = ($h->status === 'approved') ? 'Disetujui' : 'Ditolak';
                                $fLbl = $fieldInfo[$h->field_name]['label'] ?? $h->field_name;
                            ?>
                            <tr data-hstatus="<?= esc($h->status) ?>">
                                <td class="pl-4 whitespace-nowrap"><?= !empty($h->tgl) ? date('d/m/Y', strtotime($h->tgl)) : '<span class="text-slate-300">absen dihapus</span>' ?></td>
                                <td class="font-bold text-slate-800"><?= esc($h->nama_guru ?? '-') ?></td>
                                <td><?= esc($fLbl) ?></td>
                                <td class="num-tabular"><span class="text-red-500"><?= esc($fmtWaktu($h->old_value ?? null, $h->tgl ?? null) ?? '∅') ?></span> → <span class="text-emerald-600 font-bold"><?= esc($fmtWaktu($h->new_value ?? null, $h->tgl ?? null) ?? '–') ?></span></td>
                                <td class="text-center"><span class="badge-tw <?= $hb ?> rounded-full"><?= $hl ?></span></td>
                                <td class="text-sm text-slate-500" style="max-width:240px" title="<?= esc($h->admin_note ?? '') ?>"><?= esc($h->admin_note ? mb_strimwidth($h->admin_note, 0, 90, '…') : '–') ?></td>
                                <td class="text-xs text-slate-400 whitespace-nowrap">
                                    <?= !empty($h->processed_at) ? date('d/m/Y H:i', strtotime($h->processed_at)) : '-' ?>
                                    <?php if (!empty($h->diproses_oleh) && trim((string) $h->diproses_oleh) !== ''): ?><div>oleh <?= esc(trim((string) $h->diproses_oleh)) ?></div><?php endif; ?>
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

    var koreksiDT = null, historyDT = null;
    if (window.jQuery && $.fn.DataTable) {
        if ($('#koreksiTable').length && !$.fn.DataTable.isDataTable('#koreksiTable')) {
            koreksiDT = $('#koreksiTable').DataTable({
                pageLength: 15, lengthMenu: [15, 30, 50],
                order: [[8, 'desc']], language: window.dt_lang_id || {},
                columnDefs: [{ orderable: false, targets: [4, 5, 6, 9] }]
            });
        }
        if ($('#historyTable').length && !$.fn.DataTable.isDataTable('#historyTable')) {
            historyDT = $('#historyTable').DataTable({
                pageLength: 15, lengthMenu: [15, 30, 50],
                order: [[6, 'desc']], language: window.dt_lang_id || {}
            });
        }
    }

    // Filter field pending (kolom Field = index 3)
    $(document).on('click', '[data-field]', function (e) {
        e.preventDefault();
        var f = $(this).data('field') || '';
        $('[data-field]').removeClass('active');
        $(this).addClass('active');
        if (koreksiDT) {
            koreksiDT.column(3).search(f === 'jam_masuk' ? 'Jam Masuk' : (f === 'jam_pulang' ? 'Jam Pulang' : ''), true, false).draw();
        } else {
            $('#koreksiBody tr').each(function () {
                $(this).toggle(!f || $(this).data('field') === f);
            });
        }
    });

    // Filter status riwayat (kolom Status = index 4)
    $(document).on('click', '[data-hstatus]', function (e) {
        e.preventDefault();
        var st = $(this).data('hstatus') || '';
        $('[data-hstatus]').removeClass('active');
        $(this).addClass('active');
        if (historyDT) {
            historyDT.column(4).search(st === 'approved' ? '^Disetujui$' : (st === 'rejected' ? '^Ditolak$' : ''), true, false).draw();
        } else {
            $('#historyTable tbody tr').each(function () {
                $(this).toggle(!st || $(this).data('hstatus') === st);
            });
        }
    });

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

    // Approve
    $(document).on('click', '.js-approve', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Setujui Koreksi?',
            text: 'Data absensi akan diperbarui + status, poin, dan denda dihitung ulang.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setLoading($btn, true);
            $.post(url, CSRF, function (res) {
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

    // Reject (dengan catatan)
    $(document).on('click', '.js-reject', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Tolak Koreksi?',
            input: 'textarea',
            inputLabel: 'Catatan Penolakan (Opsional)',
            inputPlaceholder: 'Tulis alasan penolakan di sini...',
            inputAttributes: { 'aria-label': 'Catatan Penolakan', 'maxlength': '500' },
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Tolak Permintaan',
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
