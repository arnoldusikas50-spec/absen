<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-sky-100 text-sky-600 rounded-xl shadow-sm">
                    <i class="bi bi-telegram text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Tautan Telegram Guru') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1"><?= count($linked ?? []) ?> dari <?= (int) ($totalGuru ?? 0) ?> guru menautkan Telegram untuk notifikasi personal.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/notif-config') ?>" class="px-3 py-1.5 rounded-lg bg-violet-600 text-white text-xs font-bold no-underline hover:bg-violet-700">
                <i class="bi bi-sliders2 mr-1"></i> Channel Notifikasi
            </a>
            <a href="<?= site_url('admin/presensi/whatsapp') ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-whatsapp mr-1"></i> WhatsApp
            </a>
            <a href="<?= site_url('admin/presensi/notifikasi-status') ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-activity mr-1"></i> Status
            </a>
            <a href="<?= site_url('admin/presensi') ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-speedometer2 mr-1"></i> Monitoring
            </a>
        </div>
    </div>
</div>

<div class="content-area space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-2">
            <i class="bi bi-activity text-sky-600"></i>
            <p class="font-bold text-slate-800 text-sm m-0">Status Webhook Bot</p>
            <?php if (!empty($wh['ok'])): ?>
                <?php if (!empty($wh['url'])): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Terdaftar</span>
                <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Belum terdaftar</span>
                <?php endif; ?>
            <?php else: ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">Bermasalah</span>
            <?php endif; ?>
        </div>
        <?php if (empty($wh['ok'])): ?>
        <p class="text-xs text-red-600 m-0"><?= esc($wh['error'] ?? 'Tidak diketahui') ?></p>
        <?php else: ?>
        <p class="text-[11px] text-slate-500 m-0">URL terdaftar: <code class="font-mono break-all"><?= esc($wh['url'] !== '' ? $wh['url'] : '(kosong — webhook belum dipasang)') ?></code></p>
        <p class="text-[11px] text-slate-500 mt-1 mb-0">Antrean tertunda: <b><?= (int) ($wh['pending'] ?? 0) ?></b>
        <?php if (!empty($wh['last_error'])): ?> · Error terakhir Telegram: <b class="text-red-600"><?= esc($wh['last_error']) ?></b><?php endif; ?></p>
        <?php endif; ?>
        <?php if (!empty($whSetUrl)): ?>
        <p class="text-[11px] text-slate-500 mt-2 mb-1">Belum terdaftar / URL tidak cocok? Buka tautan ini sekali (login sebagai admin tidak diperlukan — token bot di URL):</p>
        <a href="<?= esc($whSetUrl) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-sky-600 text-white text-[11px] font-bold no-underline hover:bg-sky-700">
            <i class="bi bi-plug-fill text-xs"></i>Pasang Webhook Sekarang
        </a>
        <p class="text-[10px] text-slate-400 mt-1.5 mb-0">Wajib HTTPS dan dapat diakses publik. Setelah dipasang, kirim /start ulang ke bot.</p>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto" style="max-height:68vh;overflow-y:auto;">
            <table class="w-full text-sm" id="tgTable" style="border-collapse:collapse;font-size:.825rem;">
                <thead class="sticky top-0 z-10 shadow-sm" style="background:#f8fafc;">
                    <tr>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">No</th>
                        <th class="text-left px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;min-width:180px;">Guru</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Chat ID</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Denda</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Teguran</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Alpha</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Kehadiran</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($linked)): ?>
                    <tr><td colspan="8" class="text-center text-slate-400 py-6 text-xs">Belum ada guru yang menautkan Telegram.</td></tr>
                    <?php else: ?>
                    <?php $no = 1; foreach ($linked as $row):
                        $prefs = json_decode((string) ($row->prefs ?? ''), true);
                        if (!is_array($prefs)) $prefs = [];
                        // Default semua aktif bila belum pernah diatur
                        foreach (['denda', 'teguran', 'alpha', 'kehadiran'] as $k) {
                            if (!array_key_exists($k, $prefs)) $prefs[$k] = true;
                        }
                        $nama = $row->nama_guru ?: trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                        if ($nama === '') $nama = $row->username ?? ('User ' . $row->user_id);
                        $badge = static fn($on) => $on
                            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>'
                            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Mati</span>';
                    ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="text-center px-3 py-2.5"><?= $no++ ?></td>
                        <td class="px-3 py-2.5 font-bold text-slate-800"><?= esc($nama) ?></td>
                        <td class="text-center px-3 py-2.5 font-mono text-xs"><?= esc($row->telegram_chat_id) ?></td>
                        <td class="text-center px-3 py-2.5"><?= $badge(!empty($prefs['denda'])) ?></td>
                        <td class="text-center px-3 py-2.5"><?= $badge(!empty($prefs['teguran'])) ?></td>
                        <td class="text-center px-3 py-2.5"><?= $badge(!empty($prefs['alpha'])) ?></td>
                        <td class="text-center px-3 py-2.5"><?= $badge(!empty($prefs['kehadiran'])) ?></td>
                        <td class="text-center px-3 py-2.5 whitespace-nowrap">
                            <button type="button" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-sky-600 text-xs hover:bg-sky-50" onclick="tgTest(<?= (int) $row->user_id ?>, this)" title="Kirim pesan tes">
                                <i class="bi bi-send"></i>
                            </button>
                            <button type="button" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-red-600 text-xs hover:bg-red-50" onclick="tgUnlink(<?= (int) $row->user_id ?>, this)" title="Lepas tautan">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
if (typeof ajaxcsrf === 'function') ajaxcsrf();
function tgTest(userId, btn) {
    btn.disabled = true;
    $.post('<?= site_url('admin/presensi/telegram/test') ?>', { user_id: userId }, function (res) {
        btn.disabled = false;
        Swal.fire({ icon: res && res.status ? 'success' : 'error', title: res && res.status ? 'Terkirim' : 'Gagal', text: (res && res.message) || '', timer: 2500, showConfirmButton: !(res && res.status) });
    }, 'json').fail(function () {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal terhubung ke server.' });
    });
}
function tgUnlink(userId, btn) {
    Swal.fire({ title: 'Lepas tautan?', text: 'Guru tidak lagi menerima notifikasi personal.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, lepas', cancelButtonText: 'Batal' }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('<?= site_url('admin/presensi/telegram/unlink') ?>', { user_id: userId }, function (res) {
            if (res && res.status) {
                btn.closest('tr').remove();
                Swal.fire({ icon: 'success', title: 'Dilepas', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: (res && res.message) || '' });
            }
        }, 'json').fail(function () {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal terhubung ke server.' });
        });
    });
}
</script>
<?= $this->endSection() ?>
