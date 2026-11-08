<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-people-fill"></i> Rekap Peserta QR
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul) ?></h1>
            <p class="text-xs text-slate-500 mt-1">Riwayat scan masuk dan pulang pada QR acara ini.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/qr') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali ke QR
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 p-5 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-info-circle text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900"><?= esc($event->nama_acara) ?></h2>
                <span class="badge-tw badge-secondary rounded-full text-[11px]"><?= date('d/m/Y', strtotime($event->tanggal)) ?></span>
                <span class="badge-tw badge-info rounded-full text-[11px]"><?= substr($event->jam_mulai, 0, 5) ?> - <?= substr($event->jam_selesai, 0, 5) ?></span>
                <span class="ml-auto badge-tw badge-primary rounded-full text-[11px]"><?= (int) ($peserta_count ?? count($logs)) ?> peserta</span>
                <?php if (!empty($scan_pulang_count)): ?>
                <span class="badge-tw badge-secondary rounded-full text-[11px]"><?= (int) $scan_pulang_count ?> scan pulang</span>
                <?php endif; ?>
            </div>
            <div class="p-5">
                <div class="table-responsive-tw"><table class="table-tw">
                        <thead class="bg-slate-50 text-slate-500 text-sm uppercase">
                            <tr>
                                <th>#</th>
                                <th>Nama Guru</th>
                                <th>NIP</th>
                                <th>Jenis</th>
                                <th>Scan Pada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada peserta</td></tr>
                            <?php else: ?>
                            <?php $i=1; foreach ($logs as $l): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= esc($l->nama_guru ?? 'Guru #'.$l->user_id) ?></td>
                                <td><?= esc($l->nip ?? '-') ?></td>
                                <td>
                                    <?php if (($l->jenis ?? 'masuk') === 'pulang'): ?>
                                    <span class="badge-tw badge-warning rounded-full text-[10px]">Pulang</span>
                                    <?php else: ?>
                                    <span class="badge-tw badge-success rounded-full text-[10px]">Masuk</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($l->scanned_at)) ?></td>
                            </tr>
                            <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-xs text-slate-400">Total scan: <?= count($logs) ?></div>
            </div>
        </div>
</div>
<?= $this->endSection() ?>
