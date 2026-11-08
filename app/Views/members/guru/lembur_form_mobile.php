<?= $this->extend('layouts/mobile_tw') ?>
<?php
// Default jam lembur ikut jam pulang kerja guru hari ini (dikirim controller),
// bukan 16:00/18:00 tetap — guru shift sore tidak perlu mengoreksi tiap kali.
$jamMulaiLembur = isset($jamPulangKerja) && $jamPulangKerja !== '' ? substr($jamPulangKerja, 0, 5) : '16:00';
$tsMulaiLembur  = strtotime($jamMulaiLembur . ':00');
$jamSelesaiLembur = $tsMulaiLembur ? date('H:i', $tsMulaiLembur + 2 * 3600) : '18:00';
?>

<?= $this->section('styles') ?>
<style>
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs sticky top-0 z-30">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('guru/lembur') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Ajukan Lembur</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Formulir Klaim Waktu Kerja Lembur</p>
        </div>
    </div>
</div>

<!-- ── Main Form Container ──────────────────────────────── -->
<div class="px-4 pb-24 space-y-4 mt-3">

    <!-- Quick Rules Card -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl p-3.5 space-y-1.5 shadow-2xs">
        <div class="flex items-center gap-2 font-bold text-xs text-amber-950">
            <div class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shrink-0">
                <i class="bi bi-info-circle-fill"></i>
            </div>
            <span>Ketentuan &amp; Syarat Pengajuan Lembur</span>
        </div>
        <ul class="list-disc list-inside text-[11px] text-amber-900/85 space-y-1 pl-1 m-0">
            <li>Durasi lembur minimal <strong>1 jam</strong> dan maksimal <strong>16 jam</strong>.</li>
            <li>Pengajuan maksimal <strong>7 hari ke belakang</strong> (tidak dapat tanggal mendatang).</li>
            <li>Pada hari kerja (Senin–Jumat), Anda wajib memiliki status absensi hadir pada hari tersebut.</li>
            <li>Jam lembur tidak boleh tumpang tindih dengan pengajuan lembur lainnya di hari yang sama.</li>
        </ul>
    </div>

    <!-- Main Card Form -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
        <form id="formLembur" autocomplete="off" class="space-y-4">
            <?= csrf_field() ?>

            <!-- Tanggal Lembur -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Tanggal Lembur <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" 
                           type="date" 
                           name="tgl" 
                           id="tglInput" 
                           value="<?= date('Y-m-d') ?>" 
                           min="<?= date('Y-m-d', strtotime('-7 days')) ?>" 
                           max="<?= date('Y-m-d') ?>" 
                           required>
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">Pilih tanggal pelaksanaan lembur</span>
            </div>

            <!-- Jam Mulai & Selesai -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Jam Mulai <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" 
                           type="time" 
                           name="jam_mulai" 
                           id="jamMulaiInput" 
                           value="<?= esc($jamMulaiLembur) ?>"
                           required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Jam Selesai <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" 
                           type="time" 
                           name="jam_selesai" 
                           id="jamSelesaiInput" 
                           value="<?= esc($jamSelesaiLembur) ?>" 
                           required>
                </div>
            </div>

            <!-- Estimasi Durasi Live Preview -->
            <div class="bg-amber-50/70 border border-amber-200/60 rounded-xl p-3 flex items-center justify-between">
                <span class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                    <i class="bi bi-stopwatch text-amber-600"></i>
                    <span>Estimasi Total Lembur:</span>
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-100 text-amber-950 font-extrabold text-xs" id="durationLabel">
                    2 Jam
                </span>
            </div>

            <!-- Keterangan Lembur -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Aktivitas / Keterangan Lembur <span class="text-slate-400 font-normal">(Maks 1000 karakter)</span>
                </label>
                <textarea class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all resize-none" 
                          name="keterangan" 
                          id="keteranganInput" 
                          rows="4" 
                          maxlength="1000" 
                          placeholder="Jelaskan secara ringkas kegiatan lembur yang dilakukan (misal: Rapat evaluasi kurikulum merdeka, pemeriksaan hasil ujian asesmen sumatif, pendampingan lomba siswa)..."></textarea>
                <div class="text-[10px] text-right text-slate-400 mt-1" id="charCounter">0 / 1000</div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-2 space-y-2">
                <button type="submit" id="btnSubmitForm" class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 active:scale-98 text-white rounded-xl text-xs font-extrabold shadow-sm shadow-amber-500/30 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Pengajuan Lembur</span>
                </button>
                <a href="<?= site_url('guru/lembur') ?>" class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold flex items-center justify-center transition-all no-underline text-center">
                    Batal
                </a>
            </div>

        </form>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function calculateDuration() {
        const start = $('#jamMulaiInput').val();
        const end = $('#jamSelesaiInput').val();
        if (!start || !end) return;

        const [sH, sM] = start.split(':').map(Number);
        const [eH, eM] = end.split(':').map(Number);

        let startMins = sH * 60 + sM;
        let endMins = eH * 60 + eM;

        if (endMins <= startMins) {
            // crosses midnight
            endMins += 24 * 60;
        }

        const diffMins = endMins - startMins;
        const hours = Math.floor(diffMins / 60);
        const mins = diffMins % 60;
        const totalHourDec = (diffMins / 60).toFixed(1);

        let label = `${totalHourDec} Jam`;
        if (mins > 0) {
            label = `${hours} Jam ${mins} Menit (${totalHourDec} Jam)`;
        }

        const $labelEl = $('#durationLabel');
        $labelEl.text(label);

        if (totalHourDec < 1) {
            $labelEl.removeClass('bg-amber-100 text-amber-950 bg-emerald-100 text-emerald-900').addClass('bg-rose-100 text-rose-800');
            $labelEl.text(`${label} (Kurang dari 1 jam)`);
        } else if (totalHourDec > 16) {
            $labelEl.removeClass('bg-amber-100 text-amber-950 bg-emerald-100 text-emerald-900').addClass('bg-rose-100 text-rose-800');
            $labelEl.text(`${label} (Maks 16 jam)`);
        } else {
            $labelEl.removeClass('bg-rose-100 text-rose-800').addClass('bg-amber-100 text-amber-950');
        }
    }

    $(document).ready(function() {
        calculateDuration();

        $('#jamMulaiInput, #jamSelesaiInput').on('change input', calculateDuration);

        $('#keteranganInput').on('input', function() {
            const len = $(this).val().length;
            $('#charCounter').text(`${len} / 1000`);
        });

        $('#formLembur').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#btnSubmitForm');
            $btn.prop('disabled', true).html('<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span><span>Mengirim...</span>');

            $.post('<?= site_url('guru/lembur/save') ?>', $(this).serialize(), function(res) {
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i><span>Kirim Pengajuan Lembur</span>');
                if (res && res.status) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message || 'Pengajuan lembur berhasil dikirim',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?= site_url('guru/lembur') ?>';
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: (res && res.message) ? res.message : 'Gagal mengirim pengajuan lembur',
                        icon: 'error'
                    });
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i><span>Kirim Pengajuan Lembur</span>');
                let errMsg = 'Gagal menghubungi server';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                Swal.fire('Error', errMsg, 'error');
            });
        });
    });
</script>
<?= $this->endSection() ?>
