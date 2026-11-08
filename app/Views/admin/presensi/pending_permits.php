<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="content-header fade-in-up">
        <div class="page-header">
        <div class="min-w-0">
                <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs shrink-0">
                        <i class="bi bi-file-earmark-medical text-xl"></i>
                    </span>
                    <span>Verifikasi Izin/Sakit</span>
                </h3>
            </div>
            <nav class="breadcrumb-tw">
                    
                        <a href="<?= base_url('admin') ?>" class="no-underline">
                            <i class="bi bi-house-door mr-1"></i>Admin
                        </a>
                    <span class="sep">/</span>
                    <a href="<?= base_url('admin/presensi') ?>" class="no-underline">
                            Presensi
                        </a>
                    
                    <span class="sep">/</span>
                    <span class="active">Izin & Sakit</span>
                    </nav>
        </div>
    </div>

<div class="content-area">
<div class="card-tw"><div class="card-tw-header"><div class="flex items-center">
                            <div class="bg-blue-50 p-2 rounded-lg mr-3">
                                <i class="bi bi-list-check text-blue-600"></i>
                            </div>
                            <h5 class="font-bold text-slate-700">Daftar Permintaan Izin/Sakit</h5>
                        </div>
                        <div class="ml-auto flex items-center gap-2">
                            <a href="<?= site_url('admin/presensi') ?>" class="btn-tw btn-tw-sm btn-light">
                                <i class="bi bi-arrow-left mr-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="p-0">
                        <?php if (empty($pending)): ?>
                            <div class="text-center py-12">
                                <div class="bg-slate-100 rounded-full inline-flex p-4 mb-6">
                                    <i class="bi bi-check-lg text-emerald-600" style="font-size: 3rem"></i>
                                </div>
                                <h5 class="font-bold text-slate-400">Tidak ada permintaan pending</h5>
                                <p class="text-slate-400 text-sm">Semua permintaan izin/sakit telah diverifikasi.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive-tw"><table class="table-tw">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="pl-6">Tanggal</th>
                                            <th>Guru</th>
                                            <th>Jenis & Keterangan</th>
                                            <th>Bukti</th>
                                            <th class="text-right pr-6">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending as $p):
                                            $nama = $p->nama_guru;
                                            $avatar = \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), (string) $nama);
                                            ?>
                                            <tr id="row-<?= $p->id ?>">
                                                <td class="pl-6">
                                                    <div class="flex items-center">
                                                        <div class="bg-blue-50 rounded p-2 mr-2">
                                                            <i class="bi bi-calendar-range text-blue-600"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium">
                                                                <?= date('d-m-Y', strtotime($p->tgl_mulai)) ?>
                                                            </div>
                                                            <div class="text-slate-400 text-sm">
                                                                s/d <?= date('d-m-Y', strtotime($p->tgl_selesai)) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <img src="<?= esc($avatar, 'attr') ?>" class="w-10 h-10 rounded-full object-cover shadow-sm flex-shrink-0"
                                                            alt="Avatar">
                                                        <div>
                                                            <div class="font-bold text-slate-800">
                                                                <?= esc($nama) ?>
                                                            </div>
                                                            <span class="text-sm class="text-slate-400"">
                                                                NIP: <?= esc($p->nip ?? '-') ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= (strtoupper($p->jenis) == 'SAKIT') ? 'bg-danger' : 'bg-warning text-slate-800' ?> mb-1">
                                                        <?= strtoupper($p->jenis) ?>
                                                    </span>
                                                    <p class="mb-0 text-slate-400 text-sm text-wrap" style="max-width: 250px;">
                                                        <?= esc($p->keterangan) ?>
                                                    </p>
                                                </td>
                                                <td>
                                                    <?php if (!empty($p->bukti)): ?>
                                                        <?php
                                                            $pRaw = trim((string) $p->bukti);
                                                            if (preg_match('#^https?://#i', $pRaw)) {
                                                                $pUrl = $pRaw;
                                                            } elseif (file_exists(FCPATH . ltrim($pRaw, '/'))) {
                                                                $pUrl = base_url(ltrim($pRaw, '/'));
                                                            } else {
                                                                $pUrl = base_url('uploads/izin/' . basename($pRaw));
                                                            }
                                                        ?>
                                                        <a href="<?= esc($pUrl, 'attr') ?>" target="_blank" class="btn-tw btn-tw-sm btn-outline-secondary">
                                                            <i class="bi bi-image mr-1"></i> Lihat Bukti
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-slate-400 text-sm">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right pr-6">
                                                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                        <button type="button" class="btn-tw btn-success"
                                                            data-ui-toggle="tooltip" title="Setujui"
                                                            onclick="approvePermit(<?= $p->id ?>)">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button type="button" class="btn-tw btn-danger"
                                                            data-ui-toggle="tooltip" title="Tolak"
                                                            onclick="rejectPermit(<?= $p->id ?>)">
                                                            <i class="bi bi-x-lg"></i>
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

<script>
    const CSRF_TOKEN = CSRF_HASH;

    function approvePermit(id) {
        Swal.fire({
            title: 'Setujui Permintaan?',
            text: 'Absensi akan otomatis diupdate untuk rentang tanggal tersebut.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-check-lg mr-1"></i> Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const data = {};
                data[CSRF_NAME] = CSRF_TOKEN;

                $.post('<?= site_url('admin/presensi/approve-permit') ?>/' + id, data, function (res) {
                    if (res.status) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#row-' + id).fadeOut(500, function () { $(this).remove(); });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }

    function rejectPermit(id) {
        Swal.fire({
            title: 'Tolak Permintaan?',
            text: 'Permintaan izin/sakit akan ditolak.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-x-lg mr-1"></i> Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const data = {};
                data[CSRF_NAME] = CSRF_TOKEN;

                $.post('<?= site_url('admin/presensi/reject-permit') ?>/' + id, data, function (res) {
                    if (res.status) {
                        Swal.fire({
                            title: 'Ditolak',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#row-' + id).fadeOut(500, function () { $(this).remove(); });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>

<?= $this->endSection() ?>