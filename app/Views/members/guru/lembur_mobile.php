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
    /* Top App Bar */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Filter Chips */
    .status-filter-scroll {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .status-filter-scroll::-webkit-scrollbar {
        display: none;
    }

    .filter-chip-btn {
        flex: 0 0 auto;
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.15s ease;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-chip-btn:active {
        transform: scale(0.96);
    }
    .filter-chip-btn.active {
        background: #f59e0b;
        color: #ffffff;
        border-color: #f59e0b;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    }

    /* Card press */
    .lembur-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease;
    }
    .lembur-card:active {
        transform: scale(0.99);
    }

    /* Floating Action Button — melayang di atas pill navigasi bawah */
    .fab-lembur {
        position: fixed;
        bottom: calc(96px + var(--safe-area-inset-bottom, 0px) * 0.5);
        right: 20px;
        z-index: 1900;
        box-shadow: 0 12px 26px -8px rgba(217, 119, 6, 0.55), 0 2px 8px rgba(15, 23, 42, 0.14);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .fab-lembur:active {
        transform: scale(0.92);
        box-shadow: 0 5px 14px -6px rgba(217, 119, 6, 0.5);
    }

    /* Placeholder tetap ringkas meski ukuran input 16px (anti auto-zoom iOS) */
    #formLemburModal input::placeholder,
    #formLemburModal textarea::placeholder { font-size: 14px; color: #94a3b8; }
    /* Beri ruang ekstra agar kartu terakhir tidak tertutup FAB */
    .app-container { padding-bottom: calc(176px + var(--safe-area-inset-bottom, 0px)); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs sticky top-0 z-30">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('presensi') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Pengajuan Lembur</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Riwayat &amp; Klaim Jam Kerja Lembur</p>
        </div>
    </div>
    <div>
        <button type="button" id="btnOpenModalTambah" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-full text-xs font-bold transition-all active:scale-95 border border-amber-200/80 cursor-pointer">
            <i class="bi bi-plus-lg text-xs font-bold"></i>
            <span>Ajukan</span>
        </button>
    </div>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-28 space-y-4 mt-3">

    <!-- ── Summary KPI Cards (Bulan Berjalan) ─────────────── -->
    <div class="grid grid-cols-2 gap-2.5">
        <!-- Card 1: Total Jam Bulan Ini -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl p-3.5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-900/80">Total Jam Bulan Ini</span>
                <div class="w-7 h-7 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-xl font-extrabold text-amber-950"><?= number_format($stats['total_jam_bulan_ini'] ?? 0, 1) ?></span>
                <span class="text-xs font-bold text-amber-700">Jam</span>
            </div>
            <p class="text-[10px] text-amber-700/80 m-0 mt-0.5"><?= date('F Y') ?></p>
        </div>

        <!-- Card 2: Jam Disetujui -->
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200/80 rounded-2xl p-3.5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-900/80">Jam Disetujui</span>
                <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs shadow-2xs">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-xl font-extrabold text-emerald-950"><?= number_format($stats['approved_jam_bulan_ini'] ?? 0, 1) ?></span>
                <span class="text-xs font-bold text-emerald-700">Jam</span>
            </div>
            <p class="text-[10px] text-emerald-700/80 m-0 mt-0.5"><?= (int)($stats['approved_count'] ?? 0) ?> pengajuan disetujui</p>
        </div>

        <!-- Card 3: Menunggu Konfirmasi -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-2xs flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <i class="bi bi-hourglass-split text-sm"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Menunggu</span>
                <span class="text-base font-extrabold text-slate-800 leading-tight block"><?= (int)($stats['pending_count'] ?? 0) ?> <span class="text-xs font-medium text-slate-400">Pengajuan</span></span>
            </div>
        </div>

        <!-- Card 4: Ditolak -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-2xs flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                <i class="bi bi-x-circle text-sm"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Ditolak</span>
                <span class="text-base font-extrabold text-slate-800 leading-tight block"><?= (int)($stats['rejected_count'] ?? 0) ?> <span class="text-xs font-medium text-slate-400">Pengajuan</span></span>
            </div>
        </div>
    </div>

    <!-- ── Filter Chips ──────────────────────────────────── -->
    <div class="status-filter-scroll py-1" role="tablist">
        <button type="button" class="filter-chip-btn active" data-status="ALL">
            <span>Semua</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-600/30 text-white font-bold"><?= count($lembur ?? []) ?></span>
        </button>
        <button type="button" class="filter-chip-btn" data-status="PENDING">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            <span>Menunggu</span>
            <span class="text-[10px] text-slate-400 font-bold">(<?= (int)($stats['pending_count'] ?? 0) ?>)</span>
        </button>
        <button type="button" class="filter-chip-btn" data-status="APPROVED">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>Disetujui</span>
            <span class="text-[10px] text-slate-400 font-bold">(<?= (int)($stats['approved_count'] ?? 0) ?>)</span>
        </button>
        <button type="button" class="filter-chip-btn" data-status="REJECTED">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
            <span>Ditolak</span>
            <span class="text-[10px] text-slate-400 font-bold">(<?= (int)($stats['rejected_count'] ?? 0) ?>)</span>
        </button>
    </div>

    <!-- ── Riwayat Lembur Feed ───────────────────────────── -->
    <div class="space-y-3" id="lemburFeed">
        <?php if (empty($lembur)): ?>
            <div class="bg-white rounded-2xl p-8 border border-slate-200/80 text-center space-y-2.5 shadow-2xs">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-2xl">
                    <i class="bi bi-calendar-plus"></i>
                </div>
                <h3 class="text-sm font-extrabold text-slate-800 m-0">Belum Ada Pengajuan Lembur</h3>
                <p class="text-xs text-slate-500 max-w-xs mx-auto">
                    Anda belum pernah mengajukan lembur. Catat waktu lembur Anda untuk mendapatkan persetujuan.
                </p>
                <div class="pt-2">
                    <button type="button" onclick="$('#btnOpenModalTambah').trigger('click')" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm shadow-amber-500/20 cursor-pointer inline-flex items-center gap-1.5">
                        <i class="bi bi-plus-lg"></i>
                        <span>Ajukan Lembur Sekarang</span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php
            $hariIndo = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
            ];
            $bulanIndo = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            ?>
            <?php foreach ($lembur as $l): ?>
                <?php
                $timeSt = strtotime($l->tgl);
                $dayName = $hariIndo[date('l', $timeSt)] ?? date('l', $timeSt);
                $mNum = (int)date('n', $timeSt);
                $dateFormatted = $dayName . ', ' . date('j', $timeSt) . ' ' . ($bulanIndo[$mNum] ?? '') . ' ' . date('Y', $timeSt);
                $status = strtoupper($l->status);
                ?>
                <div class="lembur-item-card lembur-card bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-3 hover:border-amber-300 transition-all"
                     data-status="<?= esc($status) ?>"
                     data-id="<?= (int) $l->id ?>">
                    
                    <!-- Header Bar: Date & Status Badge -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                <i class="bi bi-calendar-event text-amber-600"></i>
                                <span><?= esc($dateFormatted) ?></span>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-400 mt-0.5 block">
                                Diajukan: <?= date('d M Y H:i', strtotime($l->created_at ?? $l->tgl)) ?>
                            </span>
                        </div>

                        <div>
                            <?php if ($status === 'APPROVED'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-extrabold border border-emerald-200/80">
                                    <i class="bi bi-check-circle-fill text-[11px]"></i>
                                    <span>Disetujui</span>
                                </span>
                            <?php elseif ($status === 'REJECTED'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[10px] font-extrabold border border-rose-200/80">
                                    <i class="bi bi-x-circle-fill text-[11px]"></i>
                                    <span>Ditolak</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-[10px] font-extrabold border border-amber-200/80">
                                    <i class="bi bi-hourglass-split text-[11px] animate-spin-slow"></i>
                                    <span>Menunggu</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Time & Hours Details -->
                    <div class="flex items-center gap-2 bg-slate-50/80 border border-slate-100 rounded-xl p-2.5">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 grow">
                            <i class="bi bi-clock text-slate-400"></i>
                            <span><?= esc(substr($l->jam_mulai, 0, 5)) ?> – <?= esc(substr($l->jam_selesai, 0, 5)) ?> WITA</span>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800 font-extrabold text-xs">
                                <?= (float) $l->total_jam ?> Jam
                            </span>
                        </div>
                    </div>

                    <!-- Keterangan / Catatan -->
                    <?php if (!empty($l->keterangan)): ?>
                        <div class="text-xs text-slate-600 bg-amber-50/40 border-l-2 border-amber-400 pl-2.5 py-1 rounded-r-lg leading-relaxed">
                            <p class="m-0 italic font-medium">"<?= esc($l->keterangan) ?>"</p>
                        </div>
                    <?php endif; ?>

                    <!-- Alasan penolakan dari admin. Layar admin menjanjikan
                         "alasan tercatat dan dapat dilihat guru", jadi alasan
                         yang tidak pernah sampai ke guru adalah janji yang
                         dilanggar. -->
                    <?php if ($status === 'REJECTED'): ?>
                        <?php $alasan = trim((string) ($l->catatan_admin ?? '')); ?>
                        <div class="text-xs bg-rose-50/60 border-l-2 border-rose-300 pl-2.5 py-1.5 rounded-r-lg leading-relaxed">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wide text-rose-500 mb-0.5">
                                <i class="bi bi-chat-left-text"></i> Alasan ditolak
                            </span>
                            <p class="m-0 font-medium text-rose-700"><?= $alasan !== '' ? esc($alasan) : 'Tidak ada catatan dari admin.' ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Action for Pending -->
                    <?php if ($status === 'PENDING'): ?>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-amber-600 font-semibold flex items-center gap-1">
                                <i class="bi bi-info-circle"></i>
                                Belum diverifikasi admin
                            </span>
                            <button type="button" class="btn-cancel-lembur inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700 cursor-pointer transition-colors" data-id="<?= (int) $l->id ?>">
                                <i class="bi bi-trash text-xs"></i>
                                <span>Batalkan</span>
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Filter Empty State (Hidden by default) -->
        <div id="filterEmptyState" class="hidden bg-white rounded-2xl p-8 border border-slate-200/80 text-center space-y-2.5 shadow-2xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                <i class="bi bi-funnel"></i>
            </div>
            <h3 class="text-sm font-extrabold text-slate-800 m-0">Tidak Ada Data</h3>
            <p class="text-xs text-slate-500 max-w-xs mx-auto">
                Tidak ada pengajuan lembur untuk kategori status ini.
            </p>
        </div>
    </div>

</div>

<!-- ── Floating Action Button (FAB) ─────────────────────── -->
<button type="button" onclick="$('#btnOpenModalTambah').trigger('click')" class="fab-lembur w-14 h-14 rounded-full bg-gradient-to-tr from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 text-white flex items-center justify-center cursor-pointer active:scale-95" title="Ajukan Lembur Baru">
    <i class="bi bi-plus-lg text-2xl font-bold"></i>
</button>

<!-- ── Modal Bottom Sheet: Pengajuan Lembur Baru ───────── -->
<div class="tw-modal" id="modalAjukanLembur" tabindex="-1" aria-hidden="true">
    <div class="tw-modal-box max-w-lg">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 m-0 leading-tight">Pengajuan Lembur</h3>
                    <p class="text-[11px] font-semibold text-slate-500 m-0 mt-0.5">Isi form klaim waktu kerja lembur</p>
                </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer" data-tw-dismiss="modal">
                <i class="bi bi-x-lg text-xs font-bold"></i>
            </button>
        </div>

        <div class="p-4 overflow-y-auto max-h-[75vh]">
            <!-- Quick Rules Banner -->
            <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-3 mb-3.5 space-y-1 text-xs">
                <div class="font-bold text-amber-900 flex items-center gap-1.5">
                    <i class="bi bi-info-circle-fill text-amber-600"></i>
                    <span>Ketentuan Pengajuan:</span>
                </div>
                <ul class="list-disc list-inside text-[11px] text-amber-800/90 space-y-0.5 pl-1 m-0">
                    <li>Minimal lembur 1 jam, maksimal 16 jam.</li>
                    <li>Maksimal pengajuan 7 hari ke belakang (tidak bisa masa depan).</li>
                    <li>Hari kerja (Senin–Jumat) harus memiliki riwayat absensi hadir.</li>
                </ul>
            </div>

            <form id="formLemburModal" autocomplete="off" class="space-y-3.5">
                <?= csrf_field() ?>

                <!-- Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Lembur <span class="text-rose-500">*</span></label>
                    <input class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" 
                           type="date" 
                           name="tgl" 
                           id="tglInput" 
                           value="<?= date('Y-m-d') ?>" 
                           min="<?= date('Y-m-d', strtotime('-7 days')) ?>" 
                           max="<?= date('Y-m-d') ?>" 
                           required>
                </div>

                <!-- Jam Mulai & Selesai -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Mulai <span class="text-rose-500">*</span></label>
                        <input class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" 
                               type="time" 
                               name="jam_mulai" 
                               id="jamMulaiInput" 
                               value="<?= esc($jamMulaiLembur) ?>"
                               required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Selesai <span class="text-rose-500">*</span></label>
                        <input class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" 
                               type="time" 
                               name="jam_selesai" 
                               id="jamSelesaiInput" 
                               value="<?= esc($jamSelesaiLembur) ?>" 
                               required>
                    </div>
                </div>

                <!-- Live Duration Calculator Card -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5 flex items-center justify-between" id="durationPreviewWrap">
                    <span class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
                        <i class="bi bi-stopwatch text-amber-600"></i>
                        <span>Estimasi Durasi:</span>
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 font-extrabold text-xs" id="durationLabel">
                        2 Jam
                    </span>
                </div>

                <!-- Keterangan / Alasan Lembur -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Keterangan / Aktivitas <span class="text-slate-400 font-normal">(Maks 1000 karakter)</span></label>
                    <textarea class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all resize-none" 
                              name="keterangan" 
                              id="keteranganInput" 
                              rows="3" 
                              maxlength="1000" 
                              placeholder="Misal: Rapat koordinasi guru kurikulum, koreksi hasil asesmen sumatif, pendampingan ekstrakurikuler..."></textarea>
                    <div class="text-[10px] text-right text-slate-400 mt-1" id="charCounter">0 / 1000</div>
                </div>
            </form>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
            <button type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer" data-tw-dismiss="modal">
                Batal
            </button>
            <button type="submit" form="formLemburModal" id="btnSubmitModal" class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 active:scale-95 text-white transition-all shadow-sm shadow-amber-500/25 cursor-pointer flex items-center gap-1.5">
                <i class="bi bi-send-fill text-xs"></i>
                <span>Kirim Pengajuan</span>
            </button>
        </div>
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
            $labelEl.removeClass('bg-amber-100 text-amber-900 bg-emerald-100 text-emerald-900').addClass('bg-rose-100 text-rose-800');
            $labelEl.text(`${label} (Kurang dari 1 jam)`);
        } else if (totalHourDec > 16) {
            $labelEl.removeClass('bg-amber-100 text-amber-900 bg-emerald-100 text-emerald-900').addClass('bg-rose-100 text-rose-800');
            $labelEl.text(`${label} (Maks 16 jam)`);
        } else {
            $labelEl.removeClass('bg-rose-100 text-rose-800').addClass('bg-amber-100 text-amber-900');
        }
    }

    $(document).ready(function() {
        // Open Modal
        $('#btnOpenModalTambah').on('click', function() {
            $('#formLemburModal')[0].reset();
            $('#tglInput').val('<?= date('Y-m-d') ?>');
            $('#jamMulaiInput').val('<?= esc($jamMulaiLembur) ?>');
            $('#jamSelesaiInput').val('<?= esc($jamSelesaiLembur) ?>');
            $('#charCounter').text('0 / 1000');
            calculateDuration();
            twModalShow('#modalAjukanLembur');
            if (typeof haptic === 'function') haptic();
        });

        // Live Duration Calculation
        $('#jamMulaiInput, #jamSelesaiInput').on('change input', calculateDuration);

        // Character counter
        $('#keteranganInput').on('input', function() {
            const len = $(this).val().length;
            $('#charCounter').text(`${len} / 1000`);
        });

        // Filter status tabs
        $('.filter-chip-btn').on('click', function() {
            $('.filter-chip-btn').removeClass('active');
            $(this).addClass('active');

            const targetStatus = $(this).data('status');
            let visibleCount = 0;

            $('.lembur-item-card').each(function() {
                const itemStatus = $(this).data('status');
                if (targetStatus === 'ALL' || itemStatus === targetStatus) {
                    $(this).removeClass('hidden');
                    visibleCount++;
                } else {
                    $(this).addClass('hidden');
                }
            });

            if (visibleCount === 0 && $('.lembur-item-card').length > 0) {
                $('#filterEmptyState').removeClass('hidden');
            } else {
                $('#filterEmptyState').addClass('hidden');
            }

            if (typeof haptic === 'function') haptic();
        });

        // Cancel/Delete Pending Overtime
        $(document).on('click', '.btn-cancel-lembur', function() {
            const id = $(this).data('id');
            if (!id) return;

            Swal.fire({
                title: 'Batalkan Lembur?',
                text: 'Pengajuan lembur ini akan dihapus dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Kembali',
                customClass: {
                    confirmButton: 'rounded-xl px-4 py-2 text-xs font-bold',
                    cancelButton: 'rounded-xl px-4 py-2 text-xs font-bold'
                }
            }).then((res) => {
                if (!res.isConfirmed) return;
                Swal.fire({ title: 'Membatalkan...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
                $.post(`<?= site_url('guru/lembur/delete') ?>/${id}`, {
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                }, function(response) {
                    if (response && response.status) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: response.message || 'Pengajuan lembur berhasil dibatalkan',
                            icon: 'success',
                            timer: 1300,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', (response && response.message) ? response.message : 'Gagal membatalkan pengajuan', 'error');
                    }
                }).fail(() => Swal.fire('Error', 'Gagal menghubungi server', 'error'));
            });
        });

        // Submit Overtime Form
        $('#formLemburModal').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#btnSubmitModal');
            $btn.prop('disabled', true).html('<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span><span>Mengirim...</span>');

            $.post('<?= site_url('guru/lembur/save') ?>', $(this).serialize(), function(res) {
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill text-xs"></i><span>Kirim Pengajuan</span>');
                if (res && res.status) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message || 'Pengajuan lembur berhasil dikirim',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: (res && res.message) ? res.message : 'Gagal mengirim pengajuan lembur',
                        icon: 'error'
                    });
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill text-xs"></i><span>Kirim Pengajuan</span>');
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
