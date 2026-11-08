<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    .top-app-bar {
        background: var(--m3-surface);
        padding-top: calc(16px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }
    .dinas-card { transition: box-shadow 0.15s, transform 0.12s; }
    .dinas-card:active { transform: scale(0.99); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ─────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <a href="<?= site_url('presensi/notif') ?>"
            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-600 no-underline -ml-1">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold text-slate-800 m-0 leading-none">Verifikasi Dinas Luar</h1>
    </div>
    <?php $pendingCount = count($pending ?? []); ?>
    <?php if ($pendingCount > 0): ?>
    <span class="min-w-[22px] h-[22px] px-1.5 flex items-center justify-center rounded-full bg-blue-600 text-white text-[10px] font-extrabold">
        <?= $pendingCount ?>
    </span>
    <?php endif; ?>
</div>

<!-- ── Content ─────────────────────────────────────── -->
<div class="px-4 pb-8 mt-3 space-y-3">

    <?php if (empty($pending)): ?>
    <!-- Empty State -->
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
        <div class="w-24 h-24 rounded-full bg-blue-50 flex items-center justify-center mb-4">
            <i class="bi bi-shield-fill-check text-blue-400 text-4xl"></i>
        </div>
        <h2 class="font-bold text-slate-700 text-base m-0">Semua Beres!</h2>
        <p class="text-sm text-slate-400 mt-1 m-0">Tidak ada pengajuan dinas luar yang menunggu verifikasi.</p>
    </div>

    <?php else: ?>
    <p class="text-xs text-slate-400 font-medium px-1">
        <i class="bi bi-hourglass-split text-blue-500 me-1"></i>
        <?= $pendingCount ?> pengajuan menunggu persetujuan
    </p>

    <?php foreach ($pending as $p):
        /* ── Avatar ── */
        $nama = $p->nama_guru ?? trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
        if (trim((string)$nama) === '') $nama = $p->username ?? 'Guru';
        $avatarSrc = \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), $nama, '#3b82f6');

        /* ── Tanggal & jam ── */
        $tgl = !empty($p->tgl)
            ? date('d M Y', strtotime($p->tgl))
            : '-';
        $hari = !empty($p->tgl)
            ? ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][date('w', strtotime($p->tgl))]
            : '';
        $jamMasuk  = !empty($p->jam_masuk)  ? date('H:i', strtotime($p->jam_masuk))  : '--:--';
        $jamPulang = !empty($p->jam_pulang) ? date('H:i', strtotime($p->jam_pulang)) : '--:--';

        /* ── Keterangan ── */
        $ket = trim((string)($p->keterangan ?? ''));
    ?>
    <div class="dinas-card bg-white rounded-3xl border border-blue-100 shadow-sm overflow-hidden">

        <!-- Header: avatar + nama + badge -->
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-slate-50">
            <img src="<?= $avatarSrc ?>" alt="<?= esc($nama) ?>"
                class="w-12 h-12 rounded-2xl object-cover shadow-sm flex-shrink-0">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm m-0 leading-snug truncate"><?= esc($nama) ?></p>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5">
                    <?= esc($p->nip ?? ($p->username ?? 'Guru')) ?>
                </p>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200 flex-shrink-0">
                <i class="bi bi-briefcase text-[9px]"></i> Dinas
            </span>
        </div>

        <!-- Tanggal + Jam -->
        <div class="px-4 py-3 flex items-center gap-3 border-b border-slate-50">
            <div class="flex-1 flex items-center gap-2 bg-slate-50 rounded-2xl px-3 py-2">
                <div class="w-8 h-8 rounded-xl bg-blue-50 flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-sm font-extrabold text-blue-600 leading-none">
                        <?= !empty($p->tgl) ? date('d', strtotime($p->tgl)) : '-' ?>
                    </span>
                    <span class="text-[8px] font-bold text-blue-400 uppercase leading-none">
                        <?= !empty($p->tgl) ? date('M', strtotime($p->tgl)) : '' ?>
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-700 m-0"><?= $hari ? "$hari, $tgl" : $tgl ?></p>
                    <p class="text-[10px] text-slate-400 m-0">Tanggal dinas</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="flex flex-col items-center">
                    <span class="text-[9px] text-slate-400 font-semibold uppercase">Masuk</span>
                    <span class="text-sm font-extrabold text-slate-700"><?= $jamMasuk ?></span>
                </div>
                <i class="bi bi-arrow-right text-slate-300 text-xs"></i>
                <div class="flex flex-col items-center">
                    <span class="text-[9px] text-slate-400 font-semibold uppercase">Pulang</span>
                    <span class="text-sm font-extrabold text-slate-700"><?= $jamPulang ?></span>
                </div>
            </div>
        </div>

        <!-- Keterangan -->
        <?php if ($ket !== ''): ?>
        <div class="px-4 py-3 border-b border-slate-50">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide m-0 mb-1">Keterangan</p>
            <p class="text-sm text-slate-600 m-0 leading-relaxed"><?= esc($ket) ?></p>
        </div>
        <?php endif; ?>

        <!-- Foto bukti jika ada -->
        <?php if (!empty($p->foto_masuk)): ?>
        <?php
        $fotoBuktiRel = ltrim((string)$p->foto_masuk, '/');
        $fotoBuktiUrl = is_file(FCPATH . $fotoBuktiRel) ? base_url($fotoBuktiRel) . '?v=' . filemtime(FCPATH . $fotoBuktiRel) : null;
        if ($fotoBuktiUrl): ?>
        <div class="px-4 py-2.5 border-b border-slate-50">
            <a href="<?= $fotoBuktiUrl ?>" target="_blank"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 no-underline hover:text-blue-800">
                <i class="bi bi-image text-sm"></i> Lihat Foto Bukti
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Waktu pengajuan -->
        <div class="px-4 py-2.5 flex justify-end border-b border-slate-50">
            <span class="text-[10px] text-slate-400 flex items-center gap-1">
                <i class="bi bi-clock text-[9px]"></i>
                <?php
                $ts   = strtotime((string)($p->created_at ?? ''));
                $diff = time() - $ts;
                if ($diff < 3600)      echo floor($diff / 60) . ' mnt lalu';
                elseif ($diff < 86400) echo floor($diff / 3600) . ' jam lalu';
                else                   echo date('d M', $ts);
                ?>
            </span>
        </div>

        <!-- Action buttons -->
        <div class="grid grid-cols-2 gap-2 px-4 py-3">
            <button
                onclick="openVerifModal(<?= (int)$p->id ?>, 'reject')"
                class="flex items-center justify-center gap-1.5 py-2.5 rounded-2xl bg-red-50 text-red-600 border border-red-100 text-sm font-bold transition-all active:scale-95 hover:bg-red-100">
                <i class="bi bi-x-circle text-base"></i> Tolak
            </button>
            <button
                onclick="openVerifModal(<?= (int)$p->id ?>, 'approve')"
                class="flex items-center justify-center gap-1.5 py-2.5 rounded-2xl bg-blue-600 text-white border border-blue-700 text-sm font-bold shadow-sm shadow-blue-200 transition-all active:scale-95 hover:bg-blue-700">
                <i class="bi bi-check-circle text-base"></i> Setujui
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- ════════════════════════════════════════════════ -->
<!-- Modal Verifikasi (Bottom Sheet)                 -->
<!-- ════════════════════════════════════════════════ -->
<div class="tw-modal" id="verifModal" aria-hidden="true">
    <div class="tw-modal-box">
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-9 h-1 rounded-full bg-slate-200"></div>
        </div>
        <div class="px-6 pt-3 pb-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div id="verifIconWrap" class="w-9 h-9 rounded-xl flex items-center justify-center bg-blue-100">
                        <i id="verifIcon" class="bi bi-check2-circle text-blue-600 text-lg"></i>
                    </div>
                    <h2 id="verifTitle" class="font-bold text-slate-800 text-base m-0">Verifikasi</h2>
                </div>
                <button type="button" data-tw-dismiss="modal"
                    class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 text-slate-500 border-none cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Info box -->
            <div id="verifInfoBox"
                class="rounded-2xl px-4 py-3 mb-4 text-xs font-medium hidden">
            </div>

            <input type="hidden" id="verifId" value="">
            <input type="hidden" id="verifAction" value="">

            <div class="mb-5">
                <label class="form-label-tw">
                    Catatan <span class="text-slate-400 font-normal">(opsional)</span>
                </label>
                <textarea id="verifNote" rows="3"
                    class="m3-input resize-none"
                    placeholder="Tambahkan catatan untuk guru..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" data-tw-dismiss="modal"
                    class="btn-tw btn-light rounded-full py-3 font-bold">
                    Batal
                </button>
                <button type="button" id="verifSubmit" onclick="submitVerif()"
                    class="btn-tw btn-primary rounded-full py-3 font-bold">
                    <i class="bi bi-check2-circle me-1"></i> Proses
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {

    window.openVerifModal = function (id, action) {
        $('#verifId').val(id);
        $('#verifAction').val(action);
        $('#verifNote').val('');

        const isApprove = action === 'approve';
        $('#verifTitle').text(isApprove ? 'Setujui Dinas Luar' : 'Tolak Dinas Luar');

        if (isApprove) {
            $('#verifIconWrap').removeClass('bg-red-100').addClass('bg-blue-100');
            $('#verifIcon').removeClass('bi-x-circle text-red-600').addClass('bi-check2-circle text-blue-600');
            $('#verifInfoBox')
                .html('<i class="bi bi-info-circle-fill text-blue-500 me-1"></i> Status akan diubah ke <strong>DINAS_APPROVED</strong> dan poin hadir diberikan.')
                .removeClass('hidden bg-red-50 border-red-100 text-red-700')
                .addClass('bg-blue-50 border border-blue-100 text-blue-700');
        } else {
            $('#verifIconWrap').removeClass('bg-blue-100').addClass('bg-red-100');
            $('#verifIcon').removeClass('bi-check2-circle text-blue-600').addClass('bi-x-circle text-red-600');
            $('#verifInfoBox')
                .html('<i class="bi bi-exclamation-triangle-fill text-red-500 me-1"></i> Status akan diubah ke <strong>DINAS_REJECTED</strong>.')
                .removeClass('hidden bg-blue-50 border-blue-100 text-blue-700')
                .addClass('bg-red-50 border border-red-100 text-red-700');
        }

        const $btn = $('#verifSubmit');
        if (isApprove) {
            $btn.removeClass('btn-danger').addClass('btn-primary')
                .html('<i class="bi bi-check2-circle me-1"></i> Setujui');
        } else {
            $btn.removeClass('btn-primary').addClass('btn-danger')
                .html('<i class="bi bi-x-circle me-1"></i> Tolak');
        }

        twModalShow('#verifModal');
        if (window.navigator && window.navigator.vibrate) window.navigator.vibrate(10);
    };

    window.submitVerif = function () {
        const id      = $('#verifId').val();
        const action  = $('#verifAction').val();
        const catatan = $('#verifNote').val();
        const url = action === 'approve'
            ? '<?= site_url('presensi/kepsek/dinas/approve') ?>/' + id
            : '<?= site_url('presensi/kepsek/dinas/reject') ?>/' + id;

        const $btn = $('#verifSubmit');
        $btn.prop('disabled', true)
            .html('<div class="inline-flex items-center gap-1.5"><div class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></div> Memproses...</div>');

        $.post(url, { catatan, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, function (res) {
            if (res && res.status) {
                twModalHide('#verifModal');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Data telah diperbarui.',
                    timer: 1800,
                    showConfirmButton: false,
                    timerProgressBar: true
                }).then(() => location.reload());
            } else {
                Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal memproses', 'error');
                $btn.prop('disabled', false)
                    .html(action === 'approve'
                        ? '<i class="bi bi-check2-circle me-1"></i> Setujui'
                        : '<i class="bi bi-x-circle me-1"></i> Tolak');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Gagal terhubung ke server', 'error');
            $btn.prop('disabled', false)
                .html('<i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi');
        });
    };

})();
</script>
<?= $this->endSection() ?>
