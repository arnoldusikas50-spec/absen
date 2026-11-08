<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Top App Bar */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Day Chips Scroll */
    .day-selector-scroll {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .day-selector-scroll::-webkit-scrollbar {
        display: none;
    }

    /* Day Chip Button */
    .day-chip-btn {
        flex: 0 0 auto;
        padding: 8px 16px;
        border-radius: 9999px;
        font-size: 13px;
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
    .day-chip-btn:active {
        transform: scale(0.96);
    }
    .day-chip-btn.active {
        background: var(--primary-600);
        color: #ffffff;
        border-color: var(--primary-600);
        box-shadow: 0 4px 12px rgb(var(--primary-600-rgb) / 0.25);
    }
    .day-chip-btn.is-today-chip:not(.active) {
        border-color: var(--primary-300);
        background: var(--primary-50);
        color: var(--primary-700);
    }

    /* Timeline Structure */
    .timeline-wrap {
        position: relative;
        padding-left: 20px;
    }
    .timeline-wrap::before {
        content: '';
        position: absolute;
        top: 14px;
        bottom: 14px;
        left: 7px;
        width: 2px;
        background: #e2e8f0;
        border-radius: 9999px;
    }

    .timeline-node {
        position: absolute;
        left: -20px;
        top: 18px;
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background: #ffffff;
        border: 3px solid var(--primary-500);
        box-shadow: 0 0 0 3px var(--primary-50);
        z-index: 1;
    }
    .timeline-node.break-node {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px #fef3c7;
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .fade-in-item {
        animation: fadeInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Card press */
    .schedule-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease;
    }
    .schedule-card:active {
        transform: scale(0.99);
    }
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
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Jadwal Mengajar</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">TP <?= esc($tp_active->tahun ?? '-') ?> • Smt <?= esc($smt_active->smt ?? '-') ?></p>
        </div>
    </div>
    <div>
        <button type="button" id="btnHeaderTambah" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-full text-xs font-bold transition-all active:scale-95 border border-blue-100/80 cursor-pointer">
            <i class="bi bi-plus-lg text-xs font-bold"></i>
            <span>Jadwal Mandiri</span>
        </button>
    </div>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-24 space-y-4 mt-3">

    <!-- ── Segmented Tab Switcher ────────────────────────── -->
    <div class="bg-slate-100/90 p-1 rounded-2xl flex items-center gap-1 border border-slate-200/60 shadow-inner">
        <button type="button" id="tabBtnKbm" class="flex-1 py-2 rounded-xl text-xs font-bold text-center transition-all bg-white text-blue-700 shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
            <i class="bi bi-calendar3 text-sm"></i>
            <span>Jadwal Harian (KBM)</span>
        </button>
        <button type="button" id="tabBtnMandiri" class="flex-1 py-2 rounded-xl text-xs font-semibold text-center transition-all text-slate-500 hover:text-slate-700 flex items-center justify-center gap-1.5 cursor-pointer">
            <i class="bi bi-person-workspace text-sm"></i>
            <span>Jadwal Mandiri</span>
            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700" id="mandiriCountBadge"><?= count($jadwal_pribadi ?? []) ?></span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- TAB 1: JADWAL HARIAN KBM                               -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div id="sectionKbm" class="space-y-3.5">

        <!-- Day Selector Chips -->
        <div>
            <div class="day-selector-scroll py-1" role="tablist" aria-label="Pilih hari">
                <?php 
                $days = ['1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu'];
                $today = date('N');
                if ($today == 7) $today = 1; // Default ke Senin jika Minggu
                foreach ($days as $k => $v): 
                    $isToday = ((int)$k === (int)date('N'));
                ?>
                <button type="button" 
                        class="day-chip-btn <?= $k == $today ? 'active' : '' ?> <?= $isToday ? 'is-today-chip' : '' ?>" 
                        data-day="<?= $k ?>" 
                        role="tab" 
                        aria-selected="<?= $k == $today ? 'true' : 'false' ?>">
                    <span><?= $v ?></span>
                    <?php if ($isToday): ?>
                        <span class="w-1.5 h-1.5 rounded-full <?= $k == $today ? 'bg-white' : 'bg-blue-600' ?>"></span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search & Filter Card -->
        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs space-y-2.5">
            <!-- Search Bar -->
            <div class="relative flex items-center">
                <i class="bi bi-search absolute left-3.5 text-slate-400 text-xs pointer-events-none"></i>
                <input id="searchInput" 
                       type="search" 
                       autocomplete="off" 
                       placeholder="Cari mata pelajaran, kelas, atau jam..." 
                       class="w-full pl-9 pr-9 py-2 bg-slate-50 hover:bg-slate-100/70 focus:bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                <button id="clearSearchBtn" type="button" class="absolute right-2.5 w-6 h-6 rounded-lg text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors hidden" title="Hapus pencarian">
                    <i class="bi bi-x text-base font-bold"></i>
                </button>
            </div>

            <!-- Filters (Kelas & Mapel) -->
            <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                    <select id="filterKelas" class="w-full appearance-none pl-3 pr-7 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="">Semua Kelas</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                </div>
                <div class="relative">
                    <select id="filterMapel" class="w-full appearance-none pl-3 pr-7 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="">Semua Mapel</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            <!-- Meta summary -->
            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 pt-1 border-t border-slate-100">
                <span id="resultCount" class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    <span>Memuat jadwal...</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]" id="activeDayChip">
                    <i class="bi bi-calendar2-day text-[10px]"></i>
                    <span id="activeDayLabel">-</span>
                </span>
            </div>
        </div>

        <!-- Schedule Timeline List -->
        <div id="schedule-container" class="space-y-3">
            <!-- Skeleton Loader -->
            <div id="skeleton-container" class="space-y-3">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-3 animate-pulse">
                    <div class="flex justify-between items-center">
                        <div class="h-4 bg-slate-200 rounded w-28"></div>
                        <div class="h-4 bg-slate-200 rounded w-16"></div>
                    </div>
                    <div class="h-5 bg-slate-200 rounded w-48"></div>
                    <div class="flex gap-2">
                        <div class="h-4 bg-slate-200 rounded w-24"></div>
                        <div class="h-4 bg-slate-200 rounded w-20"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- TAB 2: JADWAL MANDIRI SAYA                             -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div id="sectionMandiri" class="space-y-3 hidden">
        <!-- Info Banner -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-3.5 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="bi bi-info-circle text-sm"></i>
            </div>
            <div class="grow">
                <h4 class="text-xs font-bold text-blue-900 m-0">Jadwal Mandiri Guru</h4>
                <p class="text-[11px] text-blue-700/90 m-0 mt-0.5 leading-relaxed">
                    Jadwal tambahan yang Anda atur secara mandiri. Jadwal ini otomatis tampil pada linimasa harian KBM Anda.
                </p>
            </div>
        </div>

        <!-- Add Button -->
        <button type="button" id="btnTambahJadwalPribadi" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-98 text-white rounded-2xl text-xs font-bold shadow-sm shadow-blue-500/20 flex items-center justify-center gap-2 transition-all cursor-pointer">
            <i class="bi bi-plus-circle-fill text-sm"></i>
            <span>Tambah Jadwal Mandiri Baru</span>
        </button>

        <!-- Personal Schedule List -->
        <div class="space-y-2.5" id="myScheduleList">
            <?php
            $dayLabelsMy = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            ?>
            <?php if (empty($jadwal_pribadi)): ?>
                <div class="bg-white rounded-2xl p-8 border border-slate-200/80 text-center space-y-2 shadow-2xs">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div class="text-sm font-bold text-slate-700">Belum Ada Jadwal Mandiri</div>
                    <p class="text-xs text-slate-500 max-w-xs mx-auto">
                        Anda belum menambahkan jadwal mengajar mandiri untuk semester aktif ini.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($jadwal_pribadi as $j): ?>
                    <?php
                    $namaMapel = $j->nama_mapel ?? ($mapel_options[$j->id_mapel] ?? 'Mata Pelajaran');
                    $namaKelas = $j->nama_kelas ?? ($kelas_options[$j->id_kelas] ?? 'Kelas ' . $j->id_kelas);
                    $ruang = $j->ruangan ?? '';
                    $hariName = $dayLabelsMy[(int)$j->hari] ?? 'Hari ' . $j->hari;
                    ?>
                    <div class="my-schedule-item bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-2xs flex items-center justify-between gap-3 hover:border-blue-200 transition-all"
                         data-id="<?= (int) $j->id ?>"
                         data-hari="<?= (int) $j->hari ?>"
                         data-jam-mulai="<?= esc(substr($j->jam_mulai ?? '', 0, 5)) ?>"
                         data-jam-selesai="<?= esc(substr($j->jam_selesai ?? '', 0, 5)) ?>"
                         data-id-mapel="<?= (int) $j->id_mapel ?>"
                         data-id-kelas="<?= (int) $j->id_kelas ?>"
                         data-ruangan="<?= esc($ruang) ?>">
                        
                        <div class="grow space-y-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-bold text-[10px] border border-blue-100">
                                    <?= esc($hariName) ?>
                                </span>
                                <span class="text-xs font-extrabold text-slate-700">
                                    <?= esc(substr($j->jam_mulai ?? '', 0, 5)) ?> – <?= esc(substr($j->jam_selesai ?? '', 0, 5)) ?>
                                </span>
                            </div>

                            <h3 class="text-sm font-bold text-slate-900 m-0"><?= esc($namaMapel) ?></h3>

                            <div class="flex items-center gap-3 text-[11px] font-semibold text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="bi bi-people-fill text-slate-400"></i>
                                    <?= esc($namaKelas) ?>
                                </span>
                                <?php if ($ruang): ?>
                                <span class="flex items-center gap-1">
                                    <i class="bi bi-door-open-fill text-slate-400"></i>
                                    <?= esc($ruang) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="btn-edit-pribadi w-8 h-8 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 flex items-center justify-center transition-colors cursor-pointer" title="Edit Jadwal">
                                <i class="bi bi-pencil-square text-xs"></i>
                            </button>
                            <button type="button" class="btn-delete-pribadi w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer" title="Hapus Jadwal">
                                <i class="bi bi-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Modal Bottom Sheet: Tambah / Edit Jadwal Pribadi ─── -->
<div class="tw-modal" id="modalJadwalPribadi" tabindex="-1" aria-hidden="true">
    <div class="tw-modal-box max-w-lg">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 m-0" id="modalTitle">Tambah Jadwal Mandiri</h3>
                <p class="text-[11px] font-semibold text-slate-500 m-0 mt-0.5">Sistem otomatis mendeteksi bentrok jadwal</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer" data-tw-dismiss="modal">
                <i class="bi bi-x-lg text-xs font-bold"></i>
            </button>
        </div>

        <div class="p-4 overflow-y-auto max-h-[75vh]">
            <form id="formJadwalPribadi" autocomplete="off" class="space-y-3.5">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="jp_id" value="">

                <!-- Hari -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari <span class="text-rose-500">*</span></label>
                    <select class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" name="hari" id="jp_hari" required>
                        <?php foreach ($dayLabelsMy as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jam Mulai & Selesai -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Mulai <span class="text-rose-500">*</span></label>
                        <input class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" type="time" name="jam_mulai" id="jp_jam_mulai" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam Selesai <span class="text-rose-500">*</span></label>
                        <input class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" type="time" name="jam_selesai" id="jp_jam_selesai" required>
                    </div>
                </div>

                <!-- Mata Pelajaran -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mata Pelajaran <span class="text-rose-500">*</span></label>
                    <select class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" name="id_mapel" id="jp_id_mapel" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <?php foreach (($mapel_options ?? []) as $id => $nama): ?>
                            <option value="<?= (int) $id ?>"><?= esc($nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Kelas -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kelas <span class="text-rose-500">*</span></label>
                    <select class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" name="id_kelas" id="jp_id_kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach (($kelas_options ?? []) as $id => $nama): ?>
                            <option value="<?= (int) $id ?>"><?= esc($nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ruangan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Ruangan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" type="text" name="ruangan" id="jp_ruangan" placeholder="Contoh: R. Lab Komputer 1, R. 204">
                </div>
            </form>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
            <button type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer" data-tw-dismiss="modal">
                Batal
            </button>
            <button type="submit" form="formJadwalPribadi" id="btnSimpanJadwal" class="px-5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 active:scale-95 text-white transition-all shadow-sm shadow-blue-500/20 cursor-pointer flex items-center gap-1.5">
                <i class="bi bi-check-lg text-sm"></i>
                <span>Simpan Jadwal</span>
            </button>
        </div>
    </div>
</div>

<!-- ── Card Templates for Dynamic Rendering ─────────────── -->

<!-- Template 1: Regular KBM / Personal Schedule Card -->
<template id="schedule-item-template">
    <div class="schedule-card bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-3 hover:border-blue-300 transition-all fade-in-item">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-extrabold text-xs border border-blue-100">
                    <i class="bi bi-clock text-[11px]"></i>
                    <span class="time-label">07:00 - 08:35</span>
                </span>
                <span class="jam-ke-badge inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">
                    Jam 1
                </span>
            </div>
            <div class="schedule-tag-wrap">
                <!-- is_pribadi badge will be injected here if applicable -->
            </div>
        </div>

        <div>
            <h3 class="subject-name text-sm font-extrabold text-slate-900 m-0 leading-snug">Matematika</h3>
            <div class="class-info flex items-center gap-3 text-xs font-semibold text-slate-500 mt-1.5 flex-wrap">
                <span class="class-name flex items-center gap-1 text-slate-700">
                    <i class="bi bi-building text-slate-400"></i>
                    <span>Kelas X-1</span>
                </span>
                <span class="room-info flex items-center gap-1 text-slate-500">
                    <i class="bi bi-door-open text-slate-400"></i>
                    <span class="room-name">R. Lab</span>
                </span>
            </div>
        </div>

        <div class="action-footer pt-2.5 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[10px] font-semibold text-slate-400 status-hint">Jadwal Mengajar</span>
            <a href="#" class="btn-jurnal-link inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors no-underline">
                <span>Isi Jurnal</span>
                <i class="bi bi-arrow-right text-[11px]"></i>
            </a>
        </div>
    </div>
</template>

<!-- Template 2: Break Time Card -->
<template id="break-item-template">
    <div class="bg-amber-50/80 rounded-2xl p-3.5 border border-amber-200/70 shadow-2xs flex items-center justify-between gap-3 fade-in-item">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="bi bi-cup-hot-fill text-sm"></i>
            </div>
            <div>
                <h4 class="text-xs font-extrabold text-amber-900 m-0">Waktu Istirahat</h4>
                <p class="text-[11px] font-semibold text-amber-700/80 m-0 mt-0.5">Rehat sejenak sebelum sesi berikutnya</p>
            </div>
        </div>
        <div class="text-right shrink-0">
            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-extrabold text-xs">
                <span class="time-label">10:00 - 10:30</span>
            </span>
        </div>
    </div>
</template>

<!-- Template 3: Empty Day Schedule -->
<template id="empty-state-template">
    <div class="bg-white rounded-2xl p-8 border border-slate-200/80 text-center space-y-2.5 shadow-2xs fade-in-item">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-2xl">
            <i class="bi bi-emoji-smile"></i>
        </div>
        <h3 class="text-sm font-extrabold text-slate-800 m-0">Tidak Ada Jadwal Mengajar</h3>
        <p class="text-xs text-slate-500 max-w-xs mx-auto">
            Anda tidak memiliki jadwal KBM di hari ini. Manfaatkan waktu untuk evaluasi atau persiapan materi.
        </p>
    </div>
</template>

<!-- Template 4: No Search Results -->
<template id="no-results-template">
    <div class="bg-white rounded-2xl p-8 border border-slate-200/80 text-center space-y-2.5 shadow-2xs fade-in-item">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
            <i class="bi bi-search"></i>
        </div>
        <h3 class="text-sm font-extrabold text-slate-800 m-0">Jadwal Tidak Ditemukan</h3>
        <p class="text-xs text-slate-500 max-w-xs mx-auto">
            Tidak ada jadwal yang cocok dengan kata kunci atau filter yang dipilih.
        </p>
        <div>
            <button type="button" id="btnResetFilter" class="px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all cursor-pointer">
                Reset Pencarian
            </button>
        </div>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let currentDaySelected = '<?= $today ?>';
    const dayLabels = { '1': 'Senin', '2': 'Selasa', '3': 'Rabu', '4': 'Kamis', '5': 'Jumat', '6': 'Sabtu' };

    const mapelsTeachers = <?= json_encode($mapels ?? []) ?>;
    const allKelas = <?= json_encode($kelas ?? []) ?>;

    // Key objek angka selalu dienumerasi naik oleh JavaScript, sehingga urutan
    // nama kelas dari server harus dipulihkan secara eksplisit.
    const kelasCollator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

    const scheduleState = {
        day: String(currentDaySelected),
        raw: [],
        query: '',
        filterKelas: '',
        filterMapel: ''
    };

    function debounce(fn, wait) {
        let t = null;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function isMyMapel(idMapel, idKelas) {
        const k = String(idKelas);
        if (!mapelsTeachers[k]) return false;
        return Object.prototype.hasOwnProperty.call(mapelsTeachers[k], String(idMapel));
    }

    function getClassName(id) {
        const k = String(id);
        return (allKelas && allKelas[k]) ? allKelas[k] : id;
    }

    function updateActiveDayUI() {
        $('#activeDayLabel').text(dayLabels[scheduleState.day] || '-');
        $('.day-chip-btn').each(function() {
            const isActive = String($(this).data('day')) === String(scheduleState.day);
            $(this).toggleClass('active', isActive);
            $(this).attr('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function rebuildFilterOptions() {
        const $kelas = $('#filterKelas');
        const $mapel = $('#filterMapel');

        const selectedKelas = scheduleState.filterKelas;
        const selectedMapel = scheduleState.filterMapel;

        // Populate Kelas options if empty
        if ($kelas.find('option').length <= 1) {
            $kelas.find('option:not(:first)').remove();
            Object.entries(allKelas || {})
                .sort((a, b) => {
                    const byName = kelasCollator.compare(String(a[1] ?? ''), String(b[1] ?? ''));
                    return byName !== 0 ? byName : (Number(a[0]) - Number(b[0]));
                })
                .forEach(([id, name]) => {
                    $kelas.append(new Option(`Kelas ${name}`, id));
                });
        }

        // Calculate allowed mapels
        const allowedMapels = {};
        if (selectedKelas) {
            const m = (mapelsTeachers && mapelsTeachers[selectedKelas]) || {};
            Object.keys(m).forEach(idMapel => { allowedMapels[idMapel] = m[idMapel]; });
        } else {
            Object.keys(mapelsTeachers || {}).forEach(idKelas => {
                const m = mapelsTeachers[idKelas] || {};
                Object.keys(m).forEach(idMapel => { allowedMapels[idMapel] = m[idMapel]; });
            });
        }

        $mapel.find('option:not(:first)').remove();
        Object.entries(allowedMapels)
            .sort((a, b) => String(a[1]).localeCompare(String(b[1])))
            .forEach(([id, name]) => {
                $mapel.append(new Option(name, id));
            });

        if (selectedMapel && allowedMapels[selectedMapel]) {
            $mapel.val(selectedMapel);
        } else {
            scheduleState.filterMapel = '';
            $mapel.val('');
        }
    }

    function loadJadwal(day) {
        $('#skeleton-container').show();
        $('#schedule-container').find('.schedule-card, .fade-in-item').remove();
        $('#resultCount').html('<span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span><span>Memuat jadwal...</span>');

        $.ajax({
            url: `<?= site_url('guru/jadwal/get_jadwal') ?>/${day}`,
            method: 'GET',
            success: function(res) {
                $('#skeleton-container').hide();
                scheduleState.day = String(day);
                updateActiveDayUI();
                if (res && res.status) {
                    ingestJadwal(res.data);
                } else {
                    scheduleState.raw = [];
                    applyFiltersAndRender();
                }
            },
            error: function() {
                $('#skeleton-container').hide();
                $('#resultCount').text('Gagal memuat');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Kesalahan',
                        text: 'Gagal mengambil data jadwal dari server.',
                        icon: 'error'
                    });
                }
            }
        });
    }

    function ingestJadwal(data) {
        let mySchedule = [];

        if (Array.isArray(data.list) && data.list.length > 0) {
            data.list.forEach(item => {
                mySchedule.push({
                    ...item,
                    id_kelas: String(item.id_kelas),
                    kelasName: item.kelasName || getClassName(item.id_kelas),
                    mapelName: item.mapelName || item.nama_mapel || '',
                    timeLabel: item.timeLabel || `${item.dari} - ${item.sampai}`
                });
            });
        } else if (data && typeof data === 'object') {
            const mapelData = data.mapel || data;
            Object.keys(mapelData).forEach(kelasId => {
                const list = mapelData[kelasId] || [];
                list.forEach(item => {
                    if (isMyMapel(item.id_mapel, kelasId)) {
                        mySchedule.push({
                            ...item,
                            id_kelas: String(kelasId),
                            kelasName: getClassName(kelasId),
                            mapelName: item.nama_mapel || '',
                            timeLabel: `${item.dari} - ${item.sampai}`
                        });
                    }
                });
            });
        }

        const uniqueSchedule = mySchedule
            .filter((v, i, a) => a.findIndex(t => (t.dari === v.dari && t.sampai === v.sampai && String(t.id_kelas) === String(v.id_kelas))) === i)
            .sort((a, b) => {
                const byTime = String(a.dari).localeCompare(String(b.dari));
                if (byTime !== 0) return byTime;

                // Jam sama: urutkan kartu berdasarkan nama kelas, bukan ID kelas.
                const namaA = String(a.kelasName ?? '');
                const namaB = String(b.kelasName ?? '');
                if (namaA && namaB) {
                    const byClass = kelasCollator.compare(namaA, namaB);
                    if (byClass !== 0) return byClass;
                }
                return (Number(a.id_kelas) || 0) - (Number(b.id_kelas) || 0);
            });

        scheduleState.raw = uniqueSchedule;
        rebuildFilterOptions();
        applyFiltersAndRender();
    }

    function applyFiltersAndRender() {
        const container = $('#schedule-container');
        container.find('.schedule-card, .fade-in-item').remove();

        const q = (scheduleState.query || '').trim().toLowerCase();
        const kelasFilter = scheduleState.filterKelas;
        const mapelFilter = scheduleState.filterMapel;

        const filtered = scheduleState.raw.filter(item => {
            if (kelasFilter && String(item.id_kelas) !== String(kelasFilter)) return false;
            if (mapelFilter && String(item.id_mapel) !== String(mapelFilter)) return false;
            if (!q) return true;
            const hay = `${item.mapelName} ${item.kelasName} ${item.timeLabel} ${item.jam_ke} ${item.ruangan || ''}`.toLowerCase();
            return hay.includes(q);
        });

        $('#clearSearchBtn').toggleClass('hidden', !q);
        $('#resultCount').html(`<span>${filtered.length} jadwal ditemukan</span>`);

        if (scheduleState.raw.length === 0) {
            const template = document.getElementById('empty-state-template').content.cloneNode(true);
            container.append(template);
            return;
        }

        if (filtered.length === 0) {
            const template = document.getElementById('no-results-template').content.cloneNode(true);
            container.append(template);
            return;
        }

        filtered.forEach((item, index) => {
            if (item.id_mapel < 0) {
                // Break item
                const template = document.getElementById('break-item-template').content.cloneNode(true);
                const $item = $(template.firstElementChild);
                $item.find('.time-label').text(`${item.dari} - ${item.sampai}`);
                container.append($item);
            } else {
                // Regular KBM or personal schedule item
                const template = document.getElementById('schedule-item-template').content.cloneNode(true);
                const $item = $(template.firstElementChild);

                $item.find('.time-label').text(`${item.dari} - ${item.sampai}`);
                $item.find('.subject-name').text(item.mapelName);
                $item.find('.jam-ke-badge').text(item.jam_ke ? `Jam ${item.jam_ke}` : 'KBM');
                $item.find('.class-name span').text(`Kelas ${item.kelasName}`);

                if (item.ruangan) {
                    $item.find('.room-info').show().find('.room-name').text(item.ruangan);
                } else {
                    $item.find('.room-info').remove();
                }

                // Personal badge
                if (item.is_pribadi == 1) {
                    $item.find('.schedule-tag-wrap').html('<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold text-[10px] border border-indigo-100"><i class="bi bi-person-fill"></i> Mandiri</span>');
                    $item.find('.status-hint').text('Jadwal Mandiri');
                }

                // Link shortcut to Isi Jurnal (form langsung terisi)
                const jurnalParams = new URLSearchParams();
                if (item.id_kelas) jurnalParams.set('kelas', item.id_kelas);
                if (item.id_mapel) jurnalParams.set('mapel', item.id_mapel);
                const jadwalId = item.id_jadwal || item.id;
                if (item.is_pribadi != 1 && jadwalId) jurnalParams.set('id_jadwal', jadwalId);
                $item.find('.btn-jurnal-link').attr('href', `<?= site_url('guru/jurnal/create') ?>?${jurnalParams.toString()}`);

                container.append($item);
            }
        });
    }

    $(document).ready(function() {
        updateActiveDayUI();
        rebuildFilterOptions();
        loadJadwal(currentDaySelected);

        // Segmented Tab Switcher
        $('#tabBtnKbm').on('click', function() {
            $(this).addClass('bg-white text-blue-700 shadow-sm font-bold').removeClass('text-slate-500 font-semibold');
            $('#tabBtnMandiri').removeClass('bg-white text-blue-700 shadow-sm font-bold').addClass('text-slate-500 font-semibold');
            $('#sectionKbm').removeClass('hidden');
            $('#sectionMandiri').addClass('hidden');
            if (typeof haptic === 'function') haptic();
        });

        $('#tabBtnMandiri, #btnHeaderTambah').on('click', function(e) {
            if (e.target.id === 'btnHeaderTambah' && $('#sectionMandiri').is(':visible')) {
                // If already on mandiri tab, open modal directly
                resetPribadiForm();
                twModalShow('#modalJadwalPribadi');
                return;
            }
            $('#tabBtnMandiri').addClass('bg-white text-blue-700 shadow-sm font-bold').removeClass('text-slate-500 font-semibold');
            $('#tabBtnKbm').removeClass('bg-white text-blue-700 shadow-sm font-bold').addClass('text-slate-500 font-semibold');
            $('#sectionMandiri').removeClass('hidden');
            $('#sectionKbm').addClass('hidden');
            if (typeof haptic === 'function') haptic();
        });

        // Day Selector Button
        $('.day-chip-btn').on('click', function() {
            $('.day-chip-btn').removeClass('active');
            $(this).addClass('active');
            currentDaySelected = $(this).data('day');

            scheduleState.query = '';
            scheduleState.filterKelas = '';
            scheduleState.filterMapel = '';
            $('#searchInput').val('');
            $('#filterKelas').val('');
            $('#filterMapel').val('');

            loadJadwal(currentDaySelected);
            if (typeof haptic === 'function') haptic();
        });

        // Search Input
        $('#searchInput').on('input', debounce(function() {
            scheduleState.query = String($(this).val() || '');
            applyFiltersAndRender();
        }, 120));

        $('#clearSearchBtn').on('click', function() {
            $('#searchInput').val('');
            scheduleState.query = '';
            applyFiltersAndRender();
            $('#searchInput').trigger('focus');
        });

        // Filters
        $('#filterKelas').on('change', function() {
            scheduleState.filterKelas = String($(this).val() || '');
            rebuildFilterOptions();
            applyFiltersAndRender();
        });

        $('#filterMapel').on('change', function() {
            scheduleState.filterMapel = String($(this).val() || '');
            applyFiltersAndRender();
        });

        $(document).on('click', '#btnResetFilter', function() {
            $('#searchInput').val('');
            $('#filterKelas').val('');
            $('#filterMapel').val('');
            scheduleState.query = '';
            scheduleState.filterKelas = '';
            scheduleState.filterMapel = '';
            rebuildFilterOptions();
            applyFiltersAndRender();
        });

        // Personal Schedule Modal Form
        function resetPribadiForm() {
            $('#jp_id').val('');
            $('#modalTitle').text('Tambah Jadwal Mandiri');
            $('#jp_hari').val(currentDaySelected || '1');
            $('#jp_jam_mulai').val('');
            $('#jp_jam_selesai').val('');
            $('#jp_id_mapel').val('');
            $('#jp_id_kelas').val('');
            $('#jp_ruangan').val('');
        }

        $('#btnTambahJadwalPribadi').on('click', function() {
            resetPribadiForm();
            twModalShow('#modalJadwalPribadi');
        });

        // Edit Personal Schedule
        $('#myScheduleList').on('click', '.btn-edit-pribadi', function() {
            const $item = $(this).closest('.my-schedule-item');
            $('#jp_id').val(String($item.data('id') || ''));
            $('#modalTitle').text('Edit Jadwal Mandiri');
            $('#jp_hari').val(String($item.data('hari') || '1'));
            $('#jp_jam_mulai').val(String($item.data('jam-mulai') || ''));
            $('#jp_jam_selesai').val(String($item.data('jam-selesai') || ''));
            $('#jp_id_mapel').val(String($item.data('id-mapel') || ''));
            $('#jp_id_kelas').val(String($item.data('id-kelas') || ''));
            $('#jp_ruangan').val(String($item.data('ruangan') || ''));
            twModalShow('#modalJadwalPribadi');
        });

        // Delete Personal Schedule
        $('#myScheduleList').on('click', '.btn-delete-pribadi', function() {
            const $item = $(this).closest('.my-schedule-item');
            const id = String($item.data('id') || '');
            if (!id) return;

            Swal.fire({
                title: 'Hapus Jadwal?',
                text: 'Jadwal mandiri yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'rounded-xl px-4 py-2 text-xs font-bold',
                    cancelButton: 'rounded-xl px-4 py-2 text-xs font-bold'
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                Swal.fire({ title: 'Menghapus...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
                $.post('<?= site_url('guru/jadwal/pribadi/delete') ?>', {
                    id: id,
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                }, function(res) {
                    if (res && res.status) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: res.message || 'Jadwal berhasil dihapus',
                            icon: 'success',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal menghapus jadwal', 'error');
                    }
                }).fail(() => Swal.fire('Error', 'Gagal terhubung ke server', 'error'));
            });
        });

        // Submit Personal Schedule Form
        $('#formJadwalPribadi').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
            $.post('<?= site_url('guru/jadwal/pribadi/save') ?>', $(this).serialize(), function(res) {
                if (res && res.status) {
                    Swal.fire({
                        title: 'Berhasil',
                        text: res.message || 'Jadwal berhasil disimpan',
                        icon: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal menyimpan jadwal', 'error');
                }
            }).fail(() => Swal.fire('Error', 'Gagal terhubung ke server', 'error'));
        });
    });
</script>
<?= $this->endSection() ?>
