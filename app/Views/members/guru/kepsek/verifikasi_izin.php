<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Top bar ─────────────────────────────────── */
    .top-app-bar {
        background: var(--m3-surface);
        padding-top: calc(16px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* ── Card press ──────────────────────────────── */
    .permit-card { transition: box-shadow 0.15s, transform 0.12s; }
    .permit-card:active { transform: scale(0.99); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <a href="<?= site_url('presensi/notif') ?>"
            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-600 no-underline -ml-1">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold text-slate-800 m-0 leading-none">Verifikasi Izin &amp; Cuti</h1>
    </div>
    <?php $pendingCount = count($permits ?? []); ?>
    <?php if ($pendingCount > 0): ?>
    <span class="min-w-[22px] h-[22px] px-1.5 flex items-center justify-center rounded-full bg-rose-600 text-white text-[10px] font-extrabold">
        <?= $pendingCount ?>
    </span>
    <?php endif; ?>
</div>

<!-- ── Content ───────────────────────────────────── -->
<div class="px-4 pb-8 mt-3 space-y-3">

    <?php if (empty($permits)): ?>
    <!-- Empty State -->
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
        <div class="w-24 h-24 rounded-full bg-emerald-50 flex items-center justify-center mb-4">
            <i class="bi bi-clipboard2-check-fill text-emerald-500 text-4xl"></i>
        </div>
        <h2 class="font-bold text-slate-700 text-base m-0">Semua Terverifikasi!</h2>
        <p class="text-sm text-slate-400 mt-1 m-0">Tidak ada pengajuan izin yang sedang menunggu.</p>
    </div>

    <?php else: ?>
    <!-- Sub-header count -->
    <p class="text-xs text-slate-400 font-medium px-1">
        <i class="bi bi-hourglass-split text-amber-500 me-1"></i>
        <?= $pendingCount ?> pengajuan menunggu persetujuan
    </p>

    <?php foreach ($permits as $p):
        /* ── Avatar ── */
        $nama = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
        if (trim($nama) === '') $nama = $p->username ?? 'Guru';
        $avatarSrc = \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), $nama, '#e11d48');

        /* ── Jenis badge ── */
        $jenis = strtoupper(trim((string)($p->jenis ?? 'IZIN')));
        $jenisLabel = match($jenis) {
            'SAKIT'  => 'Sakit',
            'CUTI'   => 'Cuti',
            'DINAS'  => 'Dinas',
            'IJIN', 'IZIN' => 'Izin',
            default  => ucfirst(strtolower($jenis)),
        };
        $jenisCls = match($jenis) {
            'SAKIT'  => 'bg-red-100 text-red-700',
            'CUTI'   => 'bg-blue-100 text-blue-700',
            'DINAS'  => 'bg-indigo-100 text-indigo-700',
            default  => 'bg-purple-100 text-purple-700',
        };
        $jenisIcon = match($jenis) {
            'SAKIT'  => 'bi-hospital',
            'CUTI'   => 'bi-umbrella',
            'DINAS'  => 'bi-briefcase',
            default  => 'bi-card-text',
        };

        /* ── Kategori cuti (Tahunan/Besar/Melahirkan/CAP) ── */
        $kategoriCutiLabel = '';
        if ($jenis === 'CUTI'
            && !empty($p->kategori_cuti)
            && \App\Services\CutiKategoriService::valid((string) $p->kategori_cuti)) {
            $kategoriCutiLabel = \App\Services\CutiKategoriService::label((string) $p->kategori_cuti);
        }

        /* ── Tanggal ── */
        $mulai   = $p->tgl_mulai   ? date('d M Y', strtotime($p->tgl_mulai))   : '-';
        $selesai = $p->tgl_selesai ? date('d M Y', strtotime($p->tgl_selesai)) : '-';
        $hariDiff = ($p->tgl_mulai && $p->tgl_selesai)
            ? (int) round((strtotime($p->tgl_selesai) - strtotime($p->tgl_mulai)) / 86400) + 1
            : 1;
        $samaHari = ($p->tgl_mulai === $p->tgl_selesai);

        /* ── Bukti lampiran ── */
        $buktiUrl = '';
        if (!empty($p->bukti)) {
            $rel = ltrim((string)$p->bukti, '/');
            if (preg_match('#^https?://#i', (string)$p->bukti)) $buktiUrl = (string)$p->bukti;
            elseif (is_file(FCPATH . $rel)) $buktiUrl = base_url($rel);
        }

        /* ── Durasi label ── */
        $durasiLabel = $hariDiff === 1 ? '1 hari' : "{$hariDiff} hari";
    ?>
    <div class="permit-card bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

        <!-- Card header: avatar + nama + badge -->
        <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-slate-50">
            <div class="relative flex-shrink-0">
                <img src="<?= $avatarSrc ?>" alt="<?= esc($nama) ?>"
                    class="w-12 h-12 rounded-2xl object-cover shadow-sm">
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm m-0 leading-snug truncate"><?= esc($nama) ?></p>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5">
                    <?= esc($p->nip ?? ($p->username ?? 'Guru')) ?>
                </p>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold flex-shrink-0 <?= $jenisCls ?>">
                <i class="bi <?= $jenisIcon ?> text-[10px]"></i>
                <?= $jenisLabel ?>
            </span>
        </div>
        <?php if ($kategoriCutiLabel !== ''): ?>
        <div class="px-4 pt-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                <i class="bi bi-umbrella text-[9px]"></i><?= esc($kategoriCutiLabel) ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Tanggal + Durasi -->
        <div class="px-4 py-3 flex items-center gap-2 border-b border-slate-50">
            <div class="flex-1 flex items-center gap-2 bg-slate-50 rounded-2xl px-3 py-2">
                <i class="bi bi-calendar-event text-slate-400 text-sm flex-shrink-0"></i>
                <div class="min-w-0">
                    <?php if ($samaHari): ?>
                        <p class="text-xs font-bold text-slate-700 m-0"><?= $mulai ?></p>
                        <p class="text-[10px] text-slate-400 m-0">1 hari</p>
                    <?php else: ?>
                        <p class="text-xs font-bold text-slate-700 m-0"><?= $mulai ?> &ndash; <?= $selesai ?></p>
                        <p class="text-[10px] text-slate-400 m-0"><?= $durasiLabel ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex-shrink-0 flex flex-col items-center justify-center w-12 h-12 bg-amber-50 rounded-2xl border border-amber-100">
                <span class="text-xl font-extrabold text-amber-600 leading-none"><?= $hariDiff ?></span>
                <span class="text-[9px] text-amber-500 font-semibold uppercase">hari</span>
            </div>
        </div>

        <!-- Keterangan / alasan -->
        <?php if (!empty($p->keterangan)): ?>
        <div class="px-4 py-3 border-b border-slate-50">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide m-0 mb-1">Alasan</p>
            <p class="text-sm text-slate-600 m-0 leading-relaxed"><?= esc($p->keterangan) ?></p>
        </div>
        <?php endif; ?>

        <!-- Lampiran + waktu pengajuan -->
        <div class="px-4 py-2.5 flex items-center justify-between border-b border-slate-50">
            <?php if ($buktiUrl !== ''): ?>
            <a href="<?= $buktiUrl ?>" target="_blank"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 no-underline hover:text-blue-800">
                <i class="bi bi-paperclip text-sm"></i> Lihat Lampiran
            </a>
            <?php else: ?>
            <span class="text-[11px] text-slate-300 italic">Tidak ada lampiran</span>
            <?php endif; ?>
            <span class="text-[10px] text-slate-400 flex items-center gap-1">
                <i class="bi bi-clock text-[9px]"></i>
                <?php
                $ts = strtotime((string)($p->created_at ?? ''));
                $diff = time() - $ts;
                if ($diff < 3600)      echo floor($diff/60) . ' mnt lalu';
                elseif ($diff < 86400) echo floor($diff/3600) . ' jam lalu';
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
                class="flex items-center justify-center gap-1.5 py-2.5 rounded-2xl bg-emerald-500 text-white border border-emerald-600 text-sm font-bold shadow-sm shadow-emerald-200 transition-all active:scale-95 hover:bg-emerald-600">
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
        <!-- Handle -->
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-9 h-1 rounded-full bg-slate-200"></div>
        </div>

        <div class="px-6 pt-3 pb-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div id="verifIconWrap"
                        class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-100">
                        <i id="verifIcon" class="bi bi-check2-circle text-emerald-600 text-lg"></i>
                    </div>
                    <h2 id="verifTitle" class="font-bold text-slate-800 text-base m-0">Verifikasi</h2>
                </div>
                <button type="button" data-tw-dismiss="modal"
                    class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 text-slate-500 border-none cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Context info -->
            <div id="verifInfoBox"
                class="bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 mb-4 text-sm text-slate-600 hidden">
            </div>

            <input type="hidden" id="verifId" value="">
            <input type="hidden" id="verifAction" value="">

            <!-- Catatan -->
            <div class="mb-5">
                <label class="form-label-tw">
                    Catatan <span class="text-slate-400 font-normal">(opsional)</span>
                </label>
                <textarea id="verifNote" rows="3"
                    class="m3-input resize-none"
                    placeholder="Tambahkan catatan untuk guru..."></textarea>
            </div>

            <!-- Buttons -->
            <div class="grid grid-cols-2 gap-2">
                <button type="button" data-tw-dismiss="modal"
                    class="btn-tw btn-light rounded-full py-3 font-bold">
                    Batal
                </button>
                <button type="button" id="verifSubmit" onclick="submitVerif()"
                    class="btn-tw btn-success rounded-full py-3 font-bold">
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

        // Title & icon
        $('#verifTitle').text(isApprove ? 'Setujui Pengajuan' : 'Tolak Pengajuan');

        if (isApprove) {
            $('#verifIconWrap').removeClass('bg-red-100').addClass('bg-emerald-100');
            $('#verifIcon').removeClass('bi-x-circle text-red-600').addClass('bi-check2-circle text-emerald-600');
            $('#verifInfoBox')
                .html('<i class="bi bi-info-circle-fill text-emerald-500 me-1"></i> Izin akan disetujui dan absensi harian akan diperbarui.')
                .removeClass('hidden border-slate-100').addClass('bg-emerald-50 border-emerald-100 text-emerald-700');
        } else {
            $('#verifIconWrap').removeClass('bg-emerald-100').addClass('bg-red-100');
            $('#verifIcon').removeClass('bi-check2-circle text-emerald-600').addClass('bi-x-circle text-red-600');
            $('#verifInfoBox')
                .html('<i class="bi bi-exclamation-triangle-fill text-red-500 me-1"></i> Pengajuan akan ditolak dan guru akan mendapat notifikasi.')
                .removeClass('hidden border-slate-100 bg-emerald-50 border-emerald-100 text-emerald-700')
                .addClass('bg-red-50 border-red-100 text-red-700');
        }

        // Submit button color
        const $btn = $('#verifSubmit');
        if (isApprove) {
            $btn.removeClass('btn-danger').addClass('btn-success')
                .html('<i class="bi bi-check2-circle me-1"></i> Setujui');
        } else {
            $btn.removeClass('btn-success').addClass('btn-danger')
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
            ? '<?= site_url('presensi/kepsek/izin/approve') ?>/' + id
            : '<?= site_url('presensi/kepsek/izin/reject') ?>/' + id;

        const $btn = $('#verifSubmit');
        $btn.prop('disabled', true)
            .html('<div class="inline-flex items-center gap-1.5"><div class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></div> Memproses...</div>');

        $.post(url, { catatan, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, function (res) {
            if (res && res.status) {
                twModalHide('#verifModal');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Status pengajuan telah diperbarui.',
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
