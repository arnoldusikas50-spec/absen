<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-emerald-100 text-emerald-600 rounded-xl shadow-sm">
                    <i class="bi bi-whatsapp text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Tautan WhatsApp Guru') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                <b><?= (int) ($verifiedCount ?? 0) ?></b> guru terverifikasi menerima notifikasi personal via WhatsApp.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/notif-config') ?>" class="px-3 py-1.5 rounded-lg bg-violet-600 text-white text-xs font-bold no-underline hover:bg-violet-700">
                <i class="bi bi-sliders2 mr-1"></i> Channel Notifikasi
            </a>
            <a href="<?= site_url('admin/presensi/telegram') ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-telegram mr-1"></i> Telegram
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
    <!-- Status gateway OpenWA -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-2">
            <i class="bi bi-activity text-emerald-600"></i>
            <p class="font-bold text-slate-800 text-sm m-0">Status Gateway OpenWA</p>
            <?php if (!empty($status['ok'])): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Siap</span>
            <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">Belum siap</span>
            <?php endif; ?>
        </div>

        <?php if (empty($status['ok'])): ?>
            <p class="text-xs text-red-600 m-0"><?= esc($status['error'] ?? 'Tidak diketahui') ?></p>
        <?php endif; ?>

        <p class="text-[11px] text-slate-500 m-0 mt-1">
            Base URL: <code class="font-mono break-all"><?= esc($status['base_url'] ?? '-') ?></code>
            <?php if (!empty($status['session_name'])): ?>
                · Sesi dikunci ke <code class="font-mono"><?= esc($status['session_name']) ?></code>
            <?php endif; ?>
        </p>

        <?php if (!empty($status['sessions'])): ?>
            <div class="overflow-x-auto mt-3">
                <table class="w-full text-xs" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th class="text-left px-3 py-2 font-bold uppercase text-slate-500" style="font-size:.65rem;">Sesi</th>
                            <th class="text-left px-3 py-2 font-bold uppercase text-slate-500" style="font-size:.65rem;">Status</th>
                            <th class="text-left px-3 py-2 font-bold uppercase text-slate-500" style="font-size:.65rem;">Nomor</th>
                            <th class="text-left px-3 py-2 font-bold uppercase text-slate-500" style="font-size:.65rem;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status['sessions'] as $s):
                            $ready = $s['status'] === 'ready';
                            $badge = $ready
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : 'bg-amber-50 text-amber-700 border-amber-200';
                            // restriction = pembatasan yang imposed WhatsApp
                            // sendiri, bukan kegagalan gateway. Nomor
                            // seperti ini tidak boleh dipakai untuk broadcast.
                            $note = $s['last_error'];
                            if ($note === '' && !empty($s['restriction'])) {
                                $r = $s['restriction'];
                                $note = 'Dibatasi WhatsApp: ' . ($r['kind'] ?? 'tidak diketahui')
                                    . (isset($r['expiresAt']) && $r['expiresAt'] ? ' (sampai ' . esc(date('d/m/Y H:i', strtotime($r['expiresAt']))) . ')' : '');
                            }
                            if ($note === '') $note = '—';
                        ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2 font-mono text-slate-700"><?= esc($s['name'] ?: substr($s['id'], 0, 8)) ?></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold border <?= $badge ?>" style="font-size:.65rem;">
                                    <?= esc($s['status']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 font-mono text-slate-600"><?= esc($s['phone'] ?: '—') ?></td>
                            <td class="px-3 py-2 text-slate-500"><?= esc($note) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 mb-0">
                Hanya sesi berstatus <code>ready</code> yang dipakai untuk kirim. QR di-pindai lewat dashboard OpenWA.
            </p>
        <?php endif; ?>
    </div>

    <!-- Daftar nomor guru -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto" style="max-height:68vh;overflow-y:auto;">
            <table class="w-full text-sm" id="waTable" style="border-collapse:collapse;font-size:.825rem;">
                <thead class="sticky top-0 z-10 shadow-sm" style="background:#f8fafc;">
                    <tr>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">No</th>
                        <th class="text-left px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;min-width:180px;">Guru</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Nomor WhatsApp</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Status</th>
                        <th class="text-center px-3 py-3 text-slate-500 font-bold uppercase" style="font-size:.7rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($linked)): ?>
                    <tr><td colspan="5" class="text-center text-slate-400 py-6 text-xs">
                        Belum ada guru yang mengisi nomor WhatsApp. Guru menambahkannya sendiri dari aplikasi → menu Notifikasi.
                    </td></tr>
                    <?php else: ?>
                    <?php $no = 1; foreach ($linked as $row):
                        $verified = (int) ($row->wa_verified ?? 0) === 1;
                        $nama = trim((string) ($row->nama_guru ?? ''));
                        if ($nama === '') {
                            $nama = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? ''));
                        }
                        if ($nama === '') $nama = (string) ($row->username ?? 'Guru');
                    ?>
                    <tr>
                        <td class="text-center px-3 py-2.5 text-slate-400"><?= $no++ ?></td>
                        <td class="px-3 py-2.5 text-slate-700 font-medium"><?= esc($nama) ?></td>
                        <td class="text-center px-3 py-2.5 font-mono text-slate-600">+<?= esc($row->wa_number) ?></td>
                        <td class="text-center px-3 py-2.5">
                            <?php if ($verified): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center px-3 py-2.5">
                            <div class="inline-flex gap-1">
                                <?php if ($verified): ?>
                                <button type="button" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-amber-600 text-xs hover:bg-amber-50"
                                    onclick="waVerify(<?= (int) $row->user_id ?>, 0, this)" title="Nonaktifkan">
                                    <i class="bi bi-pause-circle"></i>
                                </button>
                                <?php else: ?>
                                <button type="button" class="px-2.5 py-1.5 rounded-lg bg-white border border-emerald-200 text-emerald-600 text-xs hover:bg-emerald-50"
                                    onclick="waVerify(<?= (int) $row->user_id ?>, 1, this)" title="Konfirmasi nomor ini milik guru">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <?php endif; ?>
                                <button type="button" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-red-600 text-xs hover:bg-red-50"
                                    onclick="waVerify(<?= (int) $row->user_id ?>, 0, this, true)" title="Hapus nomor">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
        <p class="text-[11px] text-amber-800 m-0">
            <i class="bi bi-info-circle mr-1"></i>
            Hanya nomor berstatus <b>Aktif</b> yang menerima kiriman. Konfirmasi dulu nomornya bersama guru:
            OpenWA membalas sukses untuk nomor format-benar yang ternyata bukan miliknya,
            sehingga salah ketik satu digit berarti data absensi terkirim ke orang yang tidak berhak.
        </p>
    </div>
</div>

<script>
if (typeof ajaxcsrf === 'function') ajaxcsrf();
function waVerify(userId, verify, btn, remove) {
    const ask = remove
        ? Swal.fire({ title: 'Hapus nomor?', text: 'Guru tidak lagi menerima notifikasi WhatsApp personal.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
        : (verify
            ? Swal.fire({ title: 'Konfirmasi nomor?', text: 'Pastikan nomor ini benar-benar milik guru tersebut.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, konfirmasi', cancelButtonText: 'Batal' })
            : Promise.resolve({ isConfirmed: true }));

    ask.then(function (r) {
        if (!r.isConfirmed) return;
        btn.disabled = true;
        $.post('<?= site_url('admin/presensi/whatsapp/verify') ?>', {
            user_id: userId,
            verify: remove ? 0 : verify
        }, function (res) {
            btn.disabled = false;
            if (res && res.status) {
                // Baris hilang saat dihapus; badge berubah saat statusnya berubah.
                if (remove) {
                    btn.closest('tr').remove();
                } else {
                    window.location.reload();
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: (res && res.message) || '' });
            }
        }, 'json').fail(function () {
            btn.disabled = false;
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal terhubung ke server.' });
        });
    });
}
</script>
<?= $this->endSection() ?>