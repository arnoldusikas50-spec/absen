<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Lembur / Overtime<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .num-tabular { font-variant-numeric: tabular-nums; }
/* ── Filter chips (pola izin) ── */
.filter-chip {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.75rem; border-radius: 9999px;
    font-size: 0.75rem; font-weight: 600; cursor: pointer;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    transition: all 0.15s ease; white-space: nowrap; user-select: none;
}
.filter-chip:hover  { border-color: var(--primary-600); color: var(--primary-600); background: var(--primary-50); }
.filter-chip.active { background: var(--primary-600); color: #fff; border-color: var(--primary-600); }
.filter-chip.active .chip-count { background: rgba(255,255,255,0.25); color: #fff; }
.chip-count {
    background: #f1f5f9; color: #475569;
    font-size: 0.68rem; font-weight: 800;
    padding: 0.05rem 0.4rem; border-radius: 9999px;
    min-width: 18px; text-align: center;
}
    .filter-chip .dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
/* ── Row hover (pola izin) ── */
#pendingTable tbody tr:hover td, #rekapTable tbody tr:hover td, #riwayatTable tbody tr:hover td { background: #f8fafc; }
    .empty-state { text-align: center; padding: 2rem 1rem; color: #94a3b8; }
    .empty-state i { font-size: 2rem; display: block; margin-bottom: .5rem; }
    @media (max-width: 640px) {
        .content-area { padding: 0.5rem 0.75rem 1.25rem; }
        .card-tw-body { padding: 0.875rem; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$curYear = (int) date('Y');
$labelPeriode = ($namaBulan[(int) $bulan] ?? $bulan) . ' ' . $tahun;
$fmtJam = fn($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ───────────── PAGE HEADER (pola izin) ───────────── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-clock-fill"></i> Verifikasi Pengajuan Guru
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelola &amp; Verifikasi Lembur</h1>
            <p class="text-xs text-slate-500 mt-1">
                Persetujuan pengajuan lembur guru &middot; Tarif <strong><?= (int) $pointPerJam ?> poin/jam</strong>
                (diatur di <a href="<?= site_url('admin/presensi/setting') ?>" class="underline">Pengaturan Presensi</a>)
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="location.reload()" title="Muat ulang"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <a href="<?= site_url('admin/presensi') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

<div class="content-area">
    <?php if (!empty($pending)): ?>
    <div class="alert-tw alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
        <div>Ada <strong><?= count($pending) ?> pengajuan (<?= $fmtJam($summary['jam_pending'] ?? 0) ?> jam)</strong> menunggu persetujuan. Menyetujui akan menambah poin ke saldo guru (+<?= (int) $pointPerJam ?> poin/jam).</div>
    </div>
    <?php endif; ?>

    <!-- Pending Approvals -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-4">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                    <i class="bi bi-hourglass-split text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Perlu Persetujuan (<?= count($pending) ?>)</h2>
            </div>
        </div>
        <div class="p-0">
            <?php if (empty($pending)): ?>
                <div class="text-center py-16 text-slate-400">
                    <i class="bi bi-check-circle text-5xl block mb-3 opacity-20"></i>
                    <p class="font-medium text-slate-600">Tidak ada pengajuan menunggu</p>
                    <p class="text-xs mt-1">Semua pengajuan lembur sudah diproses.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw" id="pendingTable">
                        <thead>
                            <tr>
                                <th class="pl-4">#</th>
                                <th>Guru</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Estimasi Poin</th>
                                <th>Keterangan</th>
                                <th>Diajukan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($pending as $p):
                                $poinEst = (int) round((float) $p->total_jam * (int) $pointPerJam);
                            ?>
                            <tr data-id="<?= (int) $p->id ?>">
                                <td class="pl-4 text-slate-400 num-tabular"><?= $no++ ?></td>
                                <td>
                                    <div class="font-bold text-slate-800"><?= esc($p->nama_guru ?? 'Guru #' . $p->user_id) ?></div>
                                    <?php if (!empty($p->nip)): ?><div class="text-xs text-slate-400 num-tabular"><?= esc($p->nip) ?></div><?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($p->tgl)) ?><div class="text-xs text-slate-400"><?= date('l', strtotime($p->tgl)) ?></div></td>
                                <td class="whitespace-nowrap num-tabular"><?= substr((string) $p->jam_mulai, 0, 5) ?>–<?= substr((string) $p->jam_selesai, 0, 5) ?></td>
                                <td class="text-center font-bold num-tabular"><?= $fmtJam($p->total_jam) ?> jam</td>
                                <td class="text-center font-bold text-blue-600 num-tabular">+<?= number_format($poinEst) ?></td>
                                <td style="max-width:220px"><span class="text-sm text-slate-500" title="<?= esc($p->keterangan ?? '-') ?>"><?= esc($p->keterangan ? mb_strimwidth($p->keterangan, 0, 80, '…') : '-') ?></span></td>
                                <td class="text-xs text-slate-400 whitespace-nowrap" title="<?= esc($p->created_at ?? '') ?>"><?= !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '-' ?></td>
                                <td class="text-center whitespace-nowrap">
                                    <button type="button" class="btn-tw btn-tw-sm btn-success btn-approve" data-id="<?= (int) $p->id ?>" data-poin="<?= $poinEst ?>" data-nama="<?= esc($p->nama_guru ?? '') ?>" title="Setujui (+<?= $poinEst ?> poin)">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-reject" data-id="<?= (int) $p->id ?>" data-nama="<?= esc($p->nama_guru ?? '') ?>" title="Tolak dengan alasan">
                                        <i class="bi bi-x-lg"></i> Tolak
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

    <!-- Rekap Bulanan -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-4">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-bar-chart text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Rekap Lembur — <?= esc($labelPeriode) ?></h2>
            </div>
            <form method="get" class="flex items-center gap-2 ml-0 md:ml-auto flex-wrap">
                <select name="bulan" class="form-select-tw form-select-sm" style="width:130px" aria-label="Bulan">
                    <?php foreach ($namaBulan as $num => $nm): ?>
                    <option value="<?= str_pad($num, 2, '0', STR_PAD_LEFT) ?>" <?= (int) $bulan === $num ? 'selected' : '' ?>><?= $nm ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="form-select-tw form-select-sm" style="width:100px" aria-label="Tahun">
                    <?php for ($t = $curYear + 1; $t >= attendance_tahun_tertua(); $t--): ?>
                    <option value="<?= $t ?>" <?= (int) $tahun === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-tw btn-tw-sm btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
            </form>
        </div>
        <div class="p-0">
            <?php if ($rekap): ?>
                <div class="table-responsive-tw">
                    <table class="table-tw" id="rekapTable">
                        <thead>
                            <tr>
                                <th class="pl-4">#</th>
                                <th>Guru</th>
                                <th class="text-center">Pengajuan</th>
                                <th class="text-center">Jam Diajukan</th>
                                <th class="text-center text-emerald-600">Disetujui</th>
                                <th class="text-center text-amber-500">Menunggu</th>
                                <th class="text-center text-red-400">Ditolak</th>
                                <th class="text-center text-blue-600">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($rekap as $r): ?>
                            <tr>
                                <td class="pl-4 text-slate-400 num-tabular"><?= $no++ ?></td>
                                <td>
                                    <div class="font-bold text-slate-800"><?= esc($r->nama_guru ?? 'User #' . $r->user_id) ?></div>
                                    <?php if (!empty($r->nip)): ?><div class="text-xs text-slate-400 num-tabular"><?= esc($r->nip) ?></div><?php endif; ?>
                                </td>
                                <td class="text-center num-tabular"><?= (int) $r->total_lembur ?>x</td>
                                <td class="text-center font-bold num-tabular"><?= $fmtJam($r->total_jam) ?> jam</td>
                                <td class="text-center font-bold text-emerald-600 num-tabular"><?= (int) ($r->lembur_disetujui ?? 0) ?>x &middot; <?= $fmtJam($r->jam_disetujui ?? 0) ?> jam</td>
                                <td class="text-center text-amber-500 num-tabular"><?= $fmtJam($r->jam_pending ?? 0) ?> jam (<?= (int) ($r->lembur_pending ?? 0) ?>x)</td>
                                <td class="text-center text-red-400 num-tabular"><?= (int) ($r->lembur_ditolak ?? 0) ?>x</td>
                                <td class="text-center font-bold text-blue-600 num-tabular">+<?= number_format((int) round((float) ($r->jam_disetujui ?? 0) * (int) $pointPerJam)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-16 text-slate-400">
                    <i class="bi bi-inbox text-5xl block mb-3 opacity-20"></i>
                    <p class="font-medium text-slate-600">Belum ada data lembur</p>
                    <p class="text-xs mt-1">Tidak ada pengajuan pada <?= esc($labelPeriode) ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riwayat Pengajuan -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                    <i class="bi bi-clock-history text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Riwayat Pengajuan — <?= esc($labelPeriode) ?></h2>
            </div>
            <?php if (!empty($riwayat)): ?>
            <div class="flex gap-2 flex-wrap items-center ml-auto" role="group" aria-label="Filter status riwayat">
                <button type="button" class="filter-chip active" data-rstatus="">Semua</button>
                <button type="button" class="filter-chip" data-rstatus="PENDING"><span class="dot" style="background:#d97706"></span>Pending</button>
                <button type="button" class="filter-chip" data-rstatus="APPROVED"><span class="dot" style="background:#16a34a"></span>Disetujui</button>
                <button type="button" class="filter-chip" data-rstatus="REJECTED"><span class="dot" style="background:#dc2626"></span>Ditolak</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-0">
            <?php if (empty($riwayat)): ?>
                <div class="text-center py-16 text-slate-400">
                    <i class="bi bi-inbox text-5xl block mb-3 opacity-20"></i>
                    <p class="font-medium text-slate-600">Belum ada riwayat</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw" id="riwayatTable" style="font-size:.8rem">
                        <thead>
                            <tr>
                                <th class="pl-4">Tanggal</th>
                                <th>Guru</th>
                                <th>Jam</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Status</th>
                                <th>Catatan Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $h):
                                $sb = $h->status === 'APPROVED' ? 'badge-success' : ($h->status === 'REJECTED' ? 'badge-danger' : 'badge-warning');
                            ?>
                            <tr data-rstatus="<?= esc($h->status) ?>">
                                <td class="pl-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($h->tgl)) ?></td>
                                <td class="font-bold text-slate-800"><?= esc($h->nama_guru ?? 'Guru #' . $h->user_id) ?></td>
                                <td class="whitespace-nowrap num-tabular"><?= substr((string) $h->jam_mulai, 0, 5) ?>–<?= substr((string) $h->jam_selesai, 0, 5) ?></td>
                                <td class="text-center font-bold num-tabular"><?= $fmtJam($h->total_jam) ?> jam</td>
                                <td class="text-center"><span class="badge-tw <?= $sb ?> rounded-full"><?= esc($h->status) ?></span></td>
                                <td class="text-sm text-slate-500" style="max-width:260px">
                                    <?php if ($h->status === 'REJECTED'): ?>
                                        <?= esc(!empty($h->catatan_admin) ? $h->catatan_admin : 'Ditolak (tanpa catatan)') ?>
                                    <?php else: ?>
                                        <span class="text-slate-300">–</span>
                                    <?php endif; ?>
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

<!-- Modal Tolak -->
</div>
<div id="rejectModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="rejectForm" onsubmit="return submitReject(event)" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <h5><i class="bi bi-x-circle mr-2"></i>Tolak Pengajuan Lembur</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" id="reject_id">
                <p class="text-sm text-slate-500 mb-3">Pengajuan milik <strong id="reject_nama" class="text-slate-800">–</strong> akan ditolak. Alasan tercatat dan dapat dilihat guru.</p>
                <label class="form-label-tw" for="reject_alasan">Alasan penolakan <span class="text-red-500">*</span></label>
                <input type="text" class="form-control-tw" id="reject_alasan" minlength="5" maxlength="500" required placeholder="Contoh: Kebutuhan dinas tidak mendesak / bukti kurang">
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-tw-sm btn-danger" id="btnSubmitReject"><i class="bi bi-x-circle"></i> Konfirmasi Tolak</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>
<script>
$(document).ready(function () {
    var CSRF = { '<?= $csrfName ?>': '<?= $csrfHash ?>' };

    function initTable(sel, opts) {
        if (!window.jQuery || !$.fn.DataTable || !$(sel).length) return null;
        if ($.fn.DataTable.isDataTable(sel)) return $(sel).DataTable();
        return $(sel).DataTable(Object.assign({ language: window.dt_lang_id || {} }, opts || {}));
    }
    var rekapDT = initTable('#rekapTable', { pageLength: 10, lengthMenu: [10, 25, 50], order: [[3, 'desc']] });
    var riwayatDT = initTable('#riwayatTable', { pageLength: 15, lengthMenu: [15, 30, 50], order: [[0, 'desc']] });

    // Filter riwayat
    $(document).on('click', '[data-rstatus]', function (e) {
        e.preventDefault();
        var st = $(this).data('rstatus') || '';
        $('[data-rstatus]').removeClass('active');
        $(this).addClass('active');
        if (riwayatDT) {
            riwayatDT.column(4).search(st ? '^' + st + '$' : '', true, false).draw();
        } else {
            $('#riwayatTable tbody tr').each(function () {
                $(this).toggle(!st || $(this).data('rstatus') === st);
            });
        }
    });

    // Setujui
    $(document).on('click', '.btn-approve', function () {
        var btn = $(this);
        var id = btn.data('id');
        var poin = btn.data('poin');
        var nama = btn.data('nama') || 'guru';
        if (btn.is(':disabled')) return;
        Swal.fire({
            title: 'Setujui Lembur?',
            html: 'Pengajuan milik <strong>' + $('<div>').text(nama).html() + '</strong> akan disetujui.<br>Poin <strong>+' + poin + '</strong> masuk ke saldo guru.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            btn.prop('disabled', true).html('<span class="spinner-tw"></span> Memproses...');
            $.post('<?= site_url("admin/presensi/lembur/approve") ?>/' + id, CSRF, function (res) {
                if (res.status) {
                    Swal.fire({ icon: 'success', title: 'Disetujui', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Setujui');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Setujui');
            });
        });
    });

    // Tolak -> modal alasan
    $(document).on('click', '.btn-reject', function () {
        $('#reject_id').val($(this).data('id'));
        $('#reject_nama').text($(this).data('nama') || '-');
        $('#reject_alasan').val('');
        $('#rejectModal').appModal('show');
    });

    window.submitReject = function (e) {
        e.preventDefault();
        var form = $('#rejectForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return false; }
        var id = $('#reject_id').val();
        var btn = $('#btnSubmitReject');
        var orig = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-tw"></span> Memproses...');
        $.post('<?= site_url("admin/presensi/lembur/reject") ?>/' + id, {
            alasan: $('#reject_alasan').val(),
            '<?= $csrfName ?>': '<?= $csrfHash ?>'
        }, function (res) {
            if (res.status) {
                $('#rejectModal').appModal('hide');
                Swal.fire({ icon: 'success', title: 'Ditolak', text: res.message, timer: 1500, showConfirmButton: false })
                    .then(function () { location.reload(); });
            } else {
                Swal.fire('Gagal', res.message, 'error');
                btn.prop('disabled', false).html(orig);
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            btn.prop('disabled', false).html(orig);
        });
        return false;
    };
});
</script>
<?= $this->endSection() ?>
