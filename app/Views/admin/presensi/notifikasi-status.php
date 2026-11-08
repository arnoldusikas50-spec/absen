<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-indigo-100 text-indigo-600 rounded-xl shadow-sm">
                    <i class="bi bi-activity text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Status Notifikasi') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                Kegagalan pengiriman yang sebelumnya hanya tersimpan di file log server.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/notif-config') ?>" class="px-3 py-1.5 rounded-lg bg-violet-600 text-white text-xs font-bold no-underline hover:bg-violet-700">
                <i class="bi bi-sliders2 mr-1"></i> Channel Notifikasi
            </a>
            <a href="<?= site_url('admin/presensi/whatsapp') ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-whatsapp mr-1"></i> WhatsApp
            </a>
            <a href="<?= site_url('admin/presensi/telegram') ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-telegram mr-1"></i> Telegram
            </a>
        </div>
    </div>
</div>

<div class="content-area space-y-6">
    <!-- Kanal -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- WhatsApp -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-2">
                <i class="bi bi-whatsapp text-emerald-600"></i>
                <p class="font-bold text-slate-800 text-sm m-0">Kanal WhatsApp</p>
                <?php if (($waMode ?? '') === 'openwa'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">mode: openwa</span>
                <?php elseif (($waMode ?? '') === 'fonnte'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">mode: fonnte</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">mode: nonaktif</span>
                <?php endif; ?>
                <?php if (!empty($wa['ok'])): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Siap</span>
                <?php elseif (($waMode ?? '') === 'off'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Tidak aktif</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">Bermasalah</span>
                <?php endif; ?>
            </div>

            <?php if (($waMode ?? '') === 'off'): ?>
                <p class="text-xs text-slate-500 m-0">Kanal WhatsApp dimatikan di Pengaturan → Notifikasi.</p>
            <?php elseif (empty($wa['ok'])): ?>
                <p class="text-xs text-red-600 m-0"><?= esc($wa['error'] ?? 'Tidak diketahui') ?></p>
            <?php else: ?>
                <p class="text-[11px] text-slate-500 m-0">
                    <?= count(array_filter($wa['sessions'] ?? [], static fn($s) => ($s['status'] ?? '') === 'ready')) ?>
                    sesi siap dari <?= count($wa['sessions'] ?? []) ?> total.
                </p>
                <?php foreach (($wa['sessions'] ?? []) as $s): ?>
                    <?php if (($s['status'] ?? '') !== 'ready'): ?>
                    <p class="text-[11px] text-amber-700 mt-1 mb-0">
                        Sesi <b><?= esc($s['name'] ?: substr((string) ($s['id'] ?? ''), 0, 8)) ?></b>
                        berstatus <code><?= esc($s['status'] ?? '') ?></code>
                        <?php if (!empty($s['restriction'])): ?>
                        — dibatasi WhatsApp (<?= esc($s['restriction']['kind'] ?? '') ?>)
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Telegram -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-2">
                <i class="bi bi-telegram text-sky-600"></i>
                <p class="font-bold text-slate-800 text-sm m-0">Kanal Telegram</p>
                <?php if (!empty($tg['ok'])): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Siap</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">Bermasalah</span>
                <?php endif; ?>
            </div>

            <?php if (empty($tg['ok'])): ?>
                <p class="text-xs text-red-600 m-0"><?= esc($tg['error'] ?? 'Tidak diketahui') ?></p>
            <?php else: ?>
                <p class="text-[11px] text-slate-500 m-0">
                    Antrean tertunda: <b><?= (int) ($tg['pending'] ?? 0) ?></b>
                </p>
                <?php if (!empty($tg['last_error'])): ?>
                <p class="text-[11px] text-red-600 mt-1 mb-0">Error terakhir: <?= esc($tg['last_error']) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Channel per jenis (dari Pengaturan → Channel Notifikasi) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2 flex-wrap">
            <p class="font-bold text-slate-800 text-sm m-0">
                <i class="bi bi-sliders2 text-slate-500 mr-1"></i> Channel aktif per jenis
            </p>
            <a href="<?= site_url('admin/presensi/notif-config') ?>" class="text-[11px] font-bold text-violet-600 no-underline hover:underline">Ubah →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" style="border-collapse:collapse;font-size:.8rem;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Jenis</th>
                        <?php foreach (['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'] as $ch): ?>
                        <th class="text-center px-2 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;"><?= esc($chLabels[$ch] ?? $ch) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($chJenis ?? []) as $jenis => $meta):
                        $cfg = ($chConfig ?? [])[$jenis] ?? null;
                        $grupMati = ((int) ($cfg->tg_grup ?? 1) !== 1) && ((int) ($cfg->wa_grup ?? 1) !== 1);
                    ?>
                    <tr class="border-t border-slate-100" <?= $grupMati ? 'style="background:#fffbeb;"' : '' ?>>
                        <td class="px-4 py-2 text-slate-700">
                            <?= esc($meta['label'] ?? $jenis) ?>
                            <?php if ($grupMati): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 ml-1">cron dilewati (Skip)</span>
                            <?php endif; ?>
                        </td>
                        <?php foreach (['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'] as $ch):
                            $nyala = ((int) ($cfg->$ch ?? 1) === 1);
                            $takBerlaku = ! ($meta['personal'] ?? true) && in_array($ch, ['tg_personal', 'wa_personal'], true);
                        ?>
                        <td class="text-center px-2 py-2">
                            <?php if ($takBerlaku): ?>
                            <span class="text-slate-300">—</span>
                            <?php elseif ($nyala): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Mati</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2.5 border-t border-slate-100">
            <p class="text-[11px] text-slate-400 m-0">
                Baris berlabel <b>Skip</b> berarti kedua channel grupnya mati — cron mencatat
                <i>dilewati</i> (HTTP 200), bukan gagal. Ini bukan error gateway.
            </p>
        </div>
    </div>

    <!-- Pengiriman cron -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <p class="font-bold text-slate-800 text-sm m-0">
                <i class="bi bi-clock-history text-slate-500 mr-1"></i> Riwayat pengiriman cron
            </p>
            <?php if (!empty($cronGagal)): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                    <?= (int) $cronGagal ?> belum berhasil
                </span>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="border-collapse:collapse;font-size:.8rem;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Jenis</th>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Periode</th>
                        <th class="text-center px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Status</th>
                        <th class="text-center px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Percobaan</th>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cron)): ?>
                    <tr><td colspan="5" class="text-center text-slate-400 py-6 text-xs">
                        Belum ada riwayat. Baris muncul setelah endpoint cron pertama kali mengirim.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($cron as $r): ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 font-mono text-slate-700"><?= esc($r->jenis) ?></td>
                        <td class="px-4 py-2 font-mono text-slate-500"><?= esc($r->periode) ?></td>
                        <td class="text-center px-4 py-2">
                            <?php if ((int) $r->terkirim === 1): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Terkirim</span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">Gagal</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-4 py-2 text-slate-500"><?= (int) $r->percobaan ?></td>
                        <td class="px-4 py-2 text-slate-500">
                            <?= $r->catatan ? esc(mb_strimwidth((string) $r->catatan, 0, 70, '…')) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2.5 border-t border-slate-100">
            <p class="text-[11px] text-slate-400 m-0">
                Hanya pengiriman yang <b>berhasil</b> yang mengunci periode. Yang gagal boleh dicoba lagi —
                perbaiki dulu, lalu panggil endpoint cron-nya lagi (tambahkan <code>&amp;force=1</code> bila memang
                ingin mengirim ulang yang sudah terkirim).
            </p>
        </div>
    </div>

    <!-- DM guru tercepat -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
            <p class="font-bold text-slate-800 text-sm m-0">
                <i class="bi bi-person-check text-slate-500 mr-1"></i> DM guru tercepat yang gagal terkirim
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="border-collapse:collapse;font-size:.8rem;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Tanggal</th>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Guru</th>
                        <th class="text-left px-4 py-2.5 text-slate-500 font-bold uppercase" style="font-size:.68rem;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dmGagal)): ?>
                    <tr><td colspan="3" class="text-center text-slate-400 py-6 text-xs">
                        Tidak ada DM guru tercepat yang gagal terkirim.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($dmGagal as $r): ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 font-mono text-slate-500"><?= esc($r->tgl) ?></td>
                        <td class="px-4 py-2 text-slate-700"><?= esc($r->nama_guru) ?></td>
                        <td class="px-4 py-2 text-slate-500">
                            <?= $r->catatan ? esc($r->catatan) : 'DM tidak diterima gateway' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2.5 border-t border-slate-100">
            <p class="text-[11px] text-slate-400 m-0">
                Penyebab paling umum: guru belum menautkan akun Telegram maupun WhatsApp.
                Hubungi guru tersebut agar mengisinya di halaman Profil.
            </p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
