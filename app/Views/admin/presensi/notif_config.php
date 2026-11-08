<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ── Matrix table ──────────────────────────────── */
.notif-matrix th, .notif-matrix td {
    padding: 10px 12px;
    vertical-align: middle;
    white-space: nowrap;
}
.notif-matrix thead th {
    background: #f8fafc;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
}
.notif-matrix tbody tr:hover > td { background: #f8fafc; }
.notif-matrix tbody td:first-child {
    font-weight: 600;
    font-size: .825rem;
    color: #1e293b;
    white-space: normal;
    min-width: 200px;
}
.notif-matrix tbody td.ch-cell {
    text-align: center;
    width: 110px;
}
/* Toggle switch */
.ch-toggle {
    appearance: none;
    -webkit-appearance: none;
    width: 40px; height: 22px;
    border-radius: 22px;
    background: #cbd5e1;
    cursor: pointer;
    transition: background .2s;
    position: relative;
    flex-shrink: 0;
    display: inline-block;
}
.ch-toggle::after {
    content: '';
    position: absolute;
    top: 3px; left: 3px;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: #fff;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.ch-toggle:checked { background: var(--primary-600); }
.ch-toggle:checked::after { transform: translateX(18px); }
.ch-toggle:disabled { opacity: .35; cursor: not-allowed; }

/* Kolom TG vs WA warna berbeda */
.ch-tg-grup:checked, .ch-tg-personal:checked { background: #0ea5e9; }
.ch-wa-grup:checked, .ch-wa-personal:checked { background: #22c55e; }

/* Separator grup vs personal */
.notif-matrix th.sep-personal,
.notif-matrix td.sep-personal {
    border-left: 2px dashed #e2e8f0;
}

/* Baris grup-only */
tr.grup-only td.ch-cell.personal-col {
    background: repeating-linear-gradient(
        45deg,
        #f8fafc,
        #f8fafc 3px,
        #f1f5f9 3px,
        #f1f5f9 6px
    );
    pointer-events: none;
}

/* Toast */
#toast-notif {
    position: fixed;
    bottom: 24px; right: 24px;
    z-index: 9999;
    transition: all .3s;
    transform: translateY(80px);
    opacity: 0;
}
#toast-notif.show {
    transform: translateY(0);
    opacity: 1;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Page Header ──────────────────────────────── -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-violet-100 text-violet-600 rounded-xl shadow-sm">
                    <i class="bi bi-sliders2 text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Pengaturan Channel Notifikasi') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                Pilih kombinasi channel pengiriman untuk setiap jenis notifikasi.
                Perubahan aktif seketika tanpa restart.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/notifikasi-status') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-activity mr-1"></i> Status
            </a>
            <a href="<?= site_url('admin/presensi/telegram') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-telegram mr-1"></i> Telegram
            </a>
            <a href="<?= site_url('admin/presensi/whatsapp') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50">
                <i class="bi bi-whatsapp mr-1"></i> WhatsApp
            </a>
        </div>
    </div>
</div>

<div class="content-area space-y-6">

    <!-- ── Legenda ──────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <p class="text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Legenda Channel</p>
        <div class="flex flex-wrap gap-4 text-xs text-slate-600">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-sky-400 inline-block"></span>
                <b>TG Grup</b> — Telegram ke grup/channel sekolah
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block"></span>
                <b>WA Grup</b> — WhatsApp ke nomor/grup broadcast
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-sky-300 inline-block border border-sky-400" style="border-style:dashed"></span>
                <b>TG Personal</b> — Telegram DM ke guru
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-emerald-300 inline-block border border-emerald-400" style="border-style:dashed"></span>
                <b>WA Personal</b> — WhatsApp ke nomor pribadi guru
            </span>
            <span class="flex items-center gap-1.5 ml-auto">
                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 text-[10px] font-bold border">—</span>
                Sel bergaris = tidak berlaku (notifikasi jenis ini tidak punya target personal)
            </span>
        </div>
    </div>

    <!-- ── Matrix Tabel ─────────────────────────── -->
    <form id="notifConfigForm">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full notif-matrix" style="border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="text-left px-4 py-3" style="min-width:210px;">Jenis Notifikasi</th>
                        <!-- Grup -->
                        <th class="text-center">
                            <span class="flex flex-col items-center gap-0.5">
                                <i class="bi bi-telegram text-sky-500 text-sm"></i>
                                <span>TG Grup</span>
                            </span>
                        </th>
                        <th class="text-center">
                            <span class="flex flex-col items-center gap-0.5">
                                <i class="bi bi-whatsapp text-emerald-500 text-sm"></i>
                                <span>WA Grup</span>
                            </span>
                        </th>
                        <!-- Personal -->
                        <th class="text-center sep-personal">
                            <span class="flex flex-col items-center gap-0.5">
                                <i class="bi bi-telegram text-sky-400 text-sm"></i>
                                <span>TG Personal</span>
                            </span>
                        </th>
                        <th class="text-center">
                            <span class="flex flex-col items-center gap-0.5">
                                <i class="bi bi-whatsapp text-emerald-400 text-sm"></i>
                                <span>WA Personal</span>
                            </span>
                        </th>
                        <!-- Aksi baris -->
                        <th class="text-center px-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $groupLabels = [
                        'personal' => ['label' => 'Notifikasi Real-time (ke Grup + Personal Guru)', 'rows' => []],
                        'grup_only' => ['label' => 'Notifikasi Broadcast (Grup Saja)', 'rows' => []],
                    ];
                    foreach ($jenisList as $jenis => $meta) {
                        if ($meta['personal']) {
                            $groupLabels['personal']['rows'][$jenis] = $meta;
                        } else {
                            $groupLabels['grup_only']['rows'][$jenis] = $meta;
                        }
                    }
                ?>
                <?php foreach ($groupLabels as $groupKey => $group): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-2 bg-slate-50 border-y border-slate-200">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <?= esc($group['label']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php foreach ($group['rows'] as $jenis => $meta):
                        $cfg = $config[$jenis] ?? null;
                        $isPersonal = $meta['personal'];
                    ?>
                    <tr class="border-b border-slate-100 <?= $isPersonal ? '' : 'grup-only' ?>">
                        <!-- Label -->
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <span><?= esc($meta['label']) ?></span>
                                <?php if ($meta['pref_key'] !== ''): ?>
                                <span class="text-[10px] text-slate-400 font-normal">
                                    Preferensi guru: <code class="bg-slate-100 px-1 rounded"><?= esc($meta['pref_key']) ?></code>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- TG Grup -->
                        <td class="ch-cell">
                            <label class="flex justify-center items-center cursor-pointer">
                                <input type="checkbox"
                                    class="ch-toggle ch-tg-grup"
                                    name="notif[<?= esc($jenis) ?>][tg_grup]"
                                    value="1"
                                    <?= ((int)($cfg->tg_grup ?? 1)) ? 'checked' : '' ?>>
                            </label>
                        </td>

                        <!-- WA Grup -->
                        <td class="ch-cell">
                            <label class="flex justify-center items-center cursor-pointer">
                                <input type="checkbox"
                                    class="ch-toggle ch-wa-grup"
                                    name="notif[<?= esc($jenis) ?>][wa_grup]"
                                    value="1"
                                    <?= ((int)($cfg->wa_grup ?? 1)) ? 'checked' : '' ?>>
                            </label>
                        </td>

                        <!-- TG Personal -->
                        <td class="ch-cell sep-personal <?= $isPersonal ? '' : 'personal-col' ?>">
                            <label class="flex justify-center items-center <?= $isPersonal ? 'cursor-pointer' : '' ?>">
                                <input type="checkbox"
                                    class="ch-toggle ch-tg-personal"
                                    name="notif[<?= esc($jenis) ?>][tg_personal]"
                                    value="1"
                                    <?= ((int)($cfg->tg_personal ?? ($isPersonal ? 1 : 0))) ? 'checked' : '' ?>
                                    <?= $isPersonal ? '' : 'disabled' ?>>
                            </label>
                        </td>

                        <!-- WA Personal -->
                        <td class="ch-cell <?= $isPersonal ? '' : 'personal-col' ?>">
                            <label class="flex justify-center items-center <?= $isPersonal ? 'cursor-pointer' : '' ?>">
                                <input type="checkbox"
                                    class="ch-toggle ch-wa-personal"
                                    name="notif[<?= esc($jenis) ?>][wa_personal]"
                                    value="1"
                                    <?= ((int)($cfg->wa_personal ?? ($isPersonal ? 1 : 0))) ? 'checked' : '' ?>
                                    <?= $isPersonal ? '' : 'disabled' ?>>
                            </label>
                        </td>

                        <!-- Aksi baris: aktifkan semua / matikan semua -->
                        <td class="ch-cell px-3">
                            <div class="flex gap-1 justify-center">
                                <button type="button"
                                    class="btn-row-all px-2 py-1 rounded-md bg-violet-50 text-violet-700 text-[10px] font-bold hover:bg-violet-100 border border-violet-200"
                                    data-jenis="<?= esc($jenis) ?>"
                                    data-personal="<?= $isPersonal ? '1' : '0' ?>"
                                    data-action="on"
                                    title="Aktifkan semua channel">
                                    <i class="bi bi-check2-all"></i>
                                </button>
                                <button type="button"
                                    class="btn-row-all px-2 py-1 rounded-md bg-slate-50 text-slate-500 text-[10px] font-bold hover:bg-slate-100 border border-slate-200"
                                    data-jenis="<?= esc($jenis) ?>"
                                    data-personal="<?= $isPersonal ? '1' : '0' ?>"
                                    data-action="off"
                                    title="Matikan semua channel">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Toolbar bawah tabel -->
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50">
            <div class="flex gap-2">
                <button type="button" id="btnAllOn"
                    class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-100">
                    <i class="bi bi-check2-all mr-1"></i>Aktifkan Semua
                </button>
                <button type="button" id="btnAllOff"
                    class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-500 text-xs font-bold hover:bg-slate-100">
                    <i class="bi bi-x-circle mr-1"></i>Matikan Semua
                </button>
            </div>
            <button type="submit" id="btnSave"
                class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                <i class="bi bi-floppy2-fill"></i>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </div>
    </form>

    <!-- ── Info Hierarki ─────────────────────────── -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-info-circle-fill text-amber-500 text-base mt-0.5"></i>
            <div class="text-xs text-amber-800 space-y-1">
                <p class="font-bold m-0">Hierarki kontrol notifikasi (3 level)</p>
                <p class="m-0">
                    <b>Level 1 (halaman ini)</b> — Admin mengatur channel mana yang aktif per jenis.
                    Bila semua channel mati, notifikasi tidak terkirim sama sekali.
                </p>
                <p class="m-0">
                    <b>Level 2</b> — Guru bisa mematikan jenis notifikasi personal tertentu dari
                    halaman profil mereka. Ini hanya berpengaruh bila kolom TG/WA Personal di atas aktif.
                </p>
                <p class="m-0">
                    <b>Level 3</b> — Kanal personal guru: WA hanya aktif bila nomor sudah dikonfirmasi
                    admin. Bila guru tidak punya kanal personal dan fallback ke grup diizinkan, pesan
                    tetap terkirim ke grup.
                </p>
                <p class="m-0">
                    <b>Catatan</b> — <i>Kehadiran Rendah (Cron)</i> punya saklar utama tambahan di
                    <b>Pengaturan → Presensi</b> (<i>alert kehadiran</i>): cron hanya mengirim bila
                    saklar itu DAN channel di halaman ini sama-sama aktif. Mematikan semua channel
                    grup suatu jenis membuat cron mencatat <i>dilewati</i> (bukan gagal).
                </p>
            </div>
        </div>
    </div>

</div><!-- /content-area -->

<!-- ── Toast ─────────────────────────────────────── -->
<div id="toast-notif" class="px-4 py-3 rounded-xl shadow-lg text-sm font-bold text-white flex items-center gap-2 bg-emerald-600">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toast-msg">Disimpan.</span>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    const form    = document.getElementById('notifConfigForm');
    const btnSave = document.getElementById('btnSave');
    const toast   = document.getElementById('toast-notif');
    const toastMsg = document.getElementById('toast-msg');

    // ── Toast helper ───────────────────────────────
    function showToast(msg, ok = true) {
        toastMsg.textContent = msg;
        toast.className = 'px-4 py-3 rounded-xl shadow-lg text-sm font-bold text-white flex items-center gap-2 '
            + (ok ? 'bg-emerald-600' : 'bg-red-500');
        toast.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ── Aktifkan / matikan satu baris ─────────────
    document.querySelectorAll('.btn-row-all').forEach(btn => {
        btn.addEventListener('click', () => {
            const jenis    = btn.dataset.jenis;
            const personal = btn.dataset.personal === '1';
            const action   = btn.dataset.action;
            const checked  = action === 'on';

            const row = btn.closest('tr');
            row.querySelectorAll('.ch-toggle:not([disabled])').forEach(cb => {
                cb.checked = checked;
            });
        });
    });

    // ── Aktifkan / matikan semua baris ─────────────
    document.getElementById('btnAllOn').addEventListener('click', () => {
        document.querySelectorAll('.ch-toggle:not([disabled])').forEach(cb => cb.checked = true);
    });
    document.getElementById('btnAllOff').addEventListener('click', () => {
        document.querySelectorAll('.ch-toggle:not([disabled])').forEach(cb => cb.checked = false);
    });

    // ── Submit AJAX ────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        btnSave.disabled = true;
        btnSave.querySelector('span').textContent = 'Menyimpan…';

        const body = new FormData(form);
        body.append(CSRF_NAME, CSRF_HASH);

        try {
            const res = await fetch('<?= site_url('admin/presensi/notif-config/save') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_HASH,
                },
                body,
            });
            const json = await res.json();
            showToast(json.message ?? (json.status ? 'Tersimpan.' : 'Gagal.'), json.status === true);
        } catch (err) {
            showToast('Gagal menghubungi server.', false);
        } finally {
            btnSave.disabled = false;
            btnSave.querySelector('span').textContent = 'Simpan Pengaturan';
        }
    });
})();
</script>
<?= $this->endSection() ?>
