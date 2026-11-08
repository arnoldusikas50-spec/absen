<?= $this->extend('layouts/mobile_siswa') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Hero Digital Pass ── */
    .student-pass-card {
        background: linear-gradient(135deg, var(--indigo-800) 0%, var(--accent-800) 55%, var(--accent-600) 100%);
        border-radius: 28px;
        padding: 22px 20px 20px;
        position: relative;
        overflow: hidden;
        color: #ffffff;
        box-shadow: 0 14px 34px -6px rgba(91, 33, 182, 0.38), 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .student-pass-pattern {
        position: absolute;
        top: -20px;
        right: -20px;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(255,255,255,0.12) 10%, transparent 10%);
        background-size: 14px 14px;
        pointer-events: none;
        opacity: 0.8;
    }
    .student-pass-glow {
        position: absolute;
        bottom: -40px;
        left: -30px;
        width: 140px;
        height: 140px;
        border-radius: 9999px;
        background: rgba(236, 72, 153, 0.25);
        filter: blur(40px);
        pointer-events: none;
    }
    .student-avatar {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        border: 2px solid rgba(255, 255, 255, 0.85);
        box-shadow: 0 6px 16px rgba(0,0,0,0.18);
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 22px;
        color: #fff;
        flex-shrink: 0;
        object-fit: cover;
    }
    .qr-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .qr-pill-btn:hover, .qr-pill-btn:active {
        background: #ffffff;
        color: var(--accent-800);
        border-color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* ── Stat Micro-Cards ── */
    .stat-card-micro {
        background: #ffffff;
        border-radius: 20px;
        padding: 14px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card-micro:active {
        transform: scale(0.98);
    }
    .stat-icon-squircle {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    /* ── Riwayat Items ── */
    .riwayat-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 6px;
        border-bottom: 1px solid #f8fafc;
        transition: background 0.15s ease;
    }
    .riwayat-row:last-child {
        border-bottom: none;
    }
    .riwayat-date-badge {
        width: 44px;
        height: 48px;
        border-radius: 14px;
        background: var(--accent-50);
        color: var(--accent-700);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid var(--accent-100);
    }

    /* Month input styling */
    .input-month-modern {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        outline: none;
        transition: all 0.2s ease;
    }
    .input-month-modern:focus {
        background: #ffffff;
        border-color: var(--accent-600);
        box-shadow: 0 0 0 3px rgb(var(--accent-600-rgb) / 0.15);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$hour = (int) date('G');
$salam = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
$namaSiswa = trim($siswa->nama ?? 'Siswa');
$namaPanggilan = explode(' ', $namaSiswa)[0] ?? $namaSiswa;
$initial = mb_strtoupper(mb_substr($namaSiswa, 0, 1));
$qrSiswaVal = !empty($siswa->qr_value) ? $siswa->qr_value : (!empty($siswa->nis) ? $siswa->nis : (!empty($siswa->nisn) ? $siswa->nisn : (string)$siswa->id_siswa));
?>

<!-- Minimalist Clean Top App Bar -->
<div class="top-app-bar flex items-center justify-between px-4 py-3 bg-white/90 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100">
    <div class="flex items-center gap-2.5">
        <?php if (!empty($logo_url)): ?>
            <img src="<?= esc($logo_url) ?>" alt="Logo" class="w-8 h-8 rounded-xl object-contain bg-slate-50 p-1 border border-slate-100">
        <?php else: ?>
            <span class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center border border-violet-100/60 shadow-xs">
                <i class="bi bi-mortarboard-fill text-sm"></i>
            </span>
        <?php endif; ?>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 leading-none mb-0.5"><?= esc($setting->sekolah ?? 'Portal Presensi') ?></p>
            <h1 class="text-sm font-extrabold text-slate-800 leading-tight m-0">Presensi Siswa</h1>
        </div>
    </div>
    
    <div class="flex items-center gap-1.5">
        <a href="<?= site_url('auth/logout') ?>" class="w-9 h-9 rounded-xl bg-slate-50 text-slate-500 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors border border-slate-100" title="Keluar">
            <i class="bi bi-box-arrow-right text-sm"></i>
        </a>
    </div>
</div>

<div class="p-4 space-y-4">
    <!-- Digital Student ID Pass Card (Apple Wallet / Pass style) -->
    <div class="student-pass-card cursor-pointer" onclick="openStudentQrModal()" title="Klik untuk menampilkan Kartu QR">
        <div class="student-pass-pattern"></div>
        <div class="student-pass-glow"></div>
        
        <!-- Top Pass: Identity & Action -->
        <div class="relative z-10 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <?php if (!empty($siswa->foto_url)): ?>
                    <img src="<?= esc($siswa->foto_url) ?>" alt="<?= esc($namaSiswa) ?>" class="student-avatar" onerror="this.onerror=null;this.replaceWith(document.createElement('div'));this.className='student-avatar';this.innerText='<?= esc($initial) ?>';">
                <?php else: ?>
                    <div class="student-avatar"><?= esc($initial) ?></div>
                <?php endif; ?>
                
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 text-white/75 text-[11px] font-medium">
                        <span><?= $salam ?>,</span>
                    </div>
                    <h2 class="text-white font-extrabold text-base leading-snug truncate m-0"><?= esc($namaSiswa) ?></h2>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-white/20 text-white backdrop-blur-sm">
                            <?= esc($siswa->nis ?: '-') ?>
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-sm">
                            <?= esc($siswa->nama_kelas ?: '-') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tap for QR Button -->
            <button type="button" class="qr-pill-btn shrink-0" onclick="event.stopPropagation(); openStudentQrModal();">
                <i class="bi bi-qr-code text-xs"></i>
                <span>Kartu QR</span>
            </button>
        </div>

        <!-- Attendance Progress Inside Pass -->
        <div class="relative z-10 mt-5 pt-3 border-t border-white/15">
            <div class="flex items-center justify-between text-[11px] mb-1.5">
                <span class="text-white/80 font-medium">Kehadiran Bulan <span id="labelBulanHero"><?= esc(date('M Y', strtotime($bulan . '-01'))) ?></span></span>
                <span class="text-white font-black"><span id="statPersen"><?= esc($stats['persen']) ?></span>%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-white/20 overflow-hidden">
                <div id="barPersen" class="h-full rounded-full bg-emerald-400 transition-all duration-500 ease-out" style="width: <?= esc($stats['persen']) ?>%"></div>
            </div>
        </div>
    </div>

    <!-- 4 Stats Micro-Cards -->
    <div class="grid grid-cols-2 gap-3">
        <!-- Hadir -->
        <div class="stat-card-micro">
            <div class="stat-icon-squircle bg-emerald-50 text-emerald-600 border border-emerald-100/80">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">HADIR</p>
                <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statHadir"><?= (int) $stats['hadir'] ?></p>
            </div>
        </div>

        <!-- Izin -->
        <div class="stat-card-micro">
            <div class="stat-icon-squircle bg-sky-50 text-sky-600 border border-sky-100/80">
                <i class="bi bi-envelope-paper-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">IZIN</p>
                <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statIzin"><?= (int) $stats['izin'] ?></p>
            </div>
        </div>

        <!-- Sakit -->
        <div class="stat-card-micro">
            <div class="stat-icon-squircle bg-amber-50 text-amber-600 border border-amber-100/80">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">SAKIT</p>
                <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statSakit"><?= (int) $stats['sakit'] ?></p>
            </div>
        </div>

        <!-- Alpha -->
        <div class="stat-card-micro">
            <div class="stat-icon-squircle bg-rose-50 text-rose-600 border border-rose-100/80">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">ALPHA</p>
                <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statAlpha"><?= (int) $stats['alpha'] ?></p>
            </div>
        </div>

        <!-- BDR (belajar dari rumah) -->
        <div class="stat-card-micro">
            <div class="stat-icon-squircle bg-teal-50 text-teal-600 border border-teal-100/80">
                <i class="bi bi-house-door-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">BDR</p>
                <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statBdr"><?= (int) ($stats['bdr'] ?? 0) ?></p>
            </div>
        </div>

        <!-- Tanpa keterangan -->
        <div class="stat-card-micro">
            <div class="stat-icon-squircle bg-slate-100 text-slate-500 border border-slate-200/80">
                <i class="bi bi-question-circle-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">TANPA KT.</p>
                <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statTanpaKet"><?= (int) ($stats['tanpa_keterangan'] ?? 0) ?></p>
            </div>
        </div>

        <?php if ((int) ($stats['lainnya'] ?? 0) > 0): ?>
            <!-- Status yang belum dikenal sistem: tetap dihitung agar jumlah
                 kartu sama dengan total hari yang memotong persentase. -->
            <div class="stat-card-micro">
                <div class="stat-icon-squircle bg-slate-100 text-slate-500 border border-slate-200/80">
                    <i class="bi bi-three-dots"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">LAINNYA</p>
                    <p class="text-lg font-black text-slate-800 leading-tight m-0" id="statLainnya"><?= (int) $stats['lainnya'] ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Presensi Hari Ini Card -->
    <div class="m3-card m3-card-elevated" id="hari-ini">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-sm">
                    <i class="bi bi-calendar-check"></i>
                </span>
                <div>
                    <h3 class="text-xs font-extrabold text-slate-800 m-0">Presensi Hari Ini</h3>
                    <p class="text-[11px] text-slate-400 font-medium m-0"><?= esc(date('d M Y')) ?></p>
                </div>
            </div>
            <div id="todayBadge">
                <?php if (!$today): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                        <i class="bi bi-clock"></i> Belum Absen
                    </span>
                <?php elseif ($today_cat === 'hadir'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="bi bi-check-circle-fill text-emerald-600"></i> Hadir
                    </span>
                <?php elseif ($today_cat === 'izin'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                        <i class="bi bi-envelope-fill text-sky-600"></i> Izin
                    </span>
                <?php elseif ($today_cat === 'sakit'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <i class="bi bi-heart-pulse-fill text-amber-600"></i> Sakit
                    </span>
                <?php elseif ($today_cat === 'bdr'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                        <i class="bi bi-house-door-fill text-teal-600"></i> Belajar dari Rumah
                    </span>
                <?php elseif ($today_cat === 'alpha'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <i class="bi bi-x-circle-fill text-rose-600"></i> Alpha
                    </span>
                <?php elseif ($today_cat === 'tanpa_keterangan'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        <i class="bi bi-question-circle-fill text-slate-400"></i> Tanpa Keterangan
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                        <?= esc($today->status ?? '-') ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Masuk & Pulang Time Boxes -->
        <div class="grid grid-cols-2 gap-3">
            <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100/70 text-emerald-600 flex items-center justify-center shrink-0 text-base">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">JAM MASUK</span>
                    <span class="text-base font-black text-slate-800 leading-tight">
                        <?= ($today && !empty($today->jam_masuk)) ? esc(substr($today->jam_masuk, 0, 5)) : '--:--' ?>
                    </span>
                </div>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100/70 text-indigo-600 flex items-center justify-center shrink-0 text-base">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">JAM PULANG</span>
                    <span class="text-base font-black text-slate-800 leading-tight">
                        <?= ($today && !empty($today->jam_keluar)) ? esc(substr($today->jam_keluar, 0, 5)) : '--:--' ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($today && !empty($today->keterangan)): ?>
            <div class="mt-3 p-2.5 rounded-xl bg-amber-50/60 border border-amber-100 text-amber-800 text-xs flex items-start gap-2">
                <i class="bi bi-info-circle-fill text-amber-500 mt-0.5"></i>
                <span class="font-medium"><?= esc($today->keterangan) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Skor Disiplin Card -->
    <?php
    // Skor null berarti ledgernya tidak terbaca. Tampilkan apa adanya —
    // memakai 100 sebagai penampung membuat siswa bermasalah terlihat bersih.
    $skorD = $skor_disiplin ?? null;
    $skorTerbaca = is_int($skorD);
    $kelasSkor = !$skorTerbaca
        ? 'bg-slate-100 text-slate-400 border border-slate-200'
        : ($skorD >= 90 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100'
            : ($skorD >= 75 ? 'bg-amber-50 text-amber-600 border border-amber-100'
                : 'bg-rose-50 text-rose-600 border border-rose-100'));
    $labelSkor = !$skorTerbaca
        ? 'Belum Terhitung'
        : ($skorD >= 90 ? 'Sangat Baik' : ($skorD >= 75 ? 'Cukup' : 'Perhatian'));
    $kelasLabel = !$skorTerbaca
        ? 'bg-slate-100 text-slate-500'
        : ($skorD >= 90 ? 'bg-emerald-100 text-emerald-700' : ($skorD >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'));
    ?>
    <div class="m3-card m3-card-elevated">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg font-black shrink-0 <?= $kelasSkor ?>">
                <?= $skorTerbaca ? (int) $skorD : '—' ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-extrabold text-slate-800 m-0">Skor Disiplin</h3>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold <?= $kelasLabel ?>">
                        <?= $labelSkor ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 font-medium m-0 mt-0.5">
                    <?= $skorTerbaca ? 'Skor maksimal 100 &middot; Pertahankan kedisiplinanmu!' : 'Skor sedang tidak dapat dihitung &middot; hubungi guru piket.' ?>
                </p>
            </div>
            <i class="bi bi-shield-check text-slate-300 text-xl shrink-0"></i>
        </div>

        <?php if (!empty($riwayat_disiplin)): ?>
            <div class="mt-3 pt-3 space-y-2 border-t border-dashed border-slate-100">
                <?php foreach (array_slice($riwayat_disiplin, 0, 3) as $rd): $p = (int) ($rd->poin ?? 0); ?>
                    <div class="flex items-center justify-between text-xs py-1">
                        <span class="text-slate-600 truncate mr-2 font-medium"><?= esc($rd->kategori_nama ?? $rd->tipe) ?> &middot; <?= esc($rd->keterangan ?? '') ?></span>
                        <span class="font-mono font-bold <?= $p >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50' ?> px-2 py-0.5 rounded-md text-[10px]">
                            <?= $p > 0 ? '+' : '' ?><?= $p ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Riwayat Kehadiran Card -->
    <div class="m3-card m3-card-elevated" id="riwayat">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-sm">
                    <i class="bi bi-clock-history"></i>
                </span>
                <h3 class="text-xs font-extrabold text-slate-800 m-0">Riwayat Presensi</h3>
            </div>
            <input type="month" id="filterBulan" value="<?= esc($bulan) ?>" class="input-month-modern text-xs">
        </div>

        <div id="riwayatList">
            <div class="text-center py-6 text-slate-400 text-xs">
                <div class="spinner-border spinner-border-sm text-violet-600 mb-2" role="status"></div>
                <p class="m-0">Memuat riwayat...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kartu Presensi Digital Siswa (Modern Bottom Sheet / Centered Card) -->
<div id="student-qr-modal" class="fixed inset-0 w-full h-full items-center justify-center" style="display:none;opacity:0;z-index:9999;background:rgba(15,23,42,0.75);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);padding:16px;">
    <div id="student-qr-card" class="bg-white w-full relative" style="max-width:23rem;border-radius:28px;padding:24px;transform:translateY(20px);border:1px solid #f1f5f9;box-shadow:0 25px 60px -12px rgba(15,23,42,0.35);transition:transform .25s ease, opacity .25s ease;">
        <button type="button" onclick="closeStudentQrModal()" class="absolute flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500" style="top:16px;right:16px;width:34px;height:34px;z-index:20;border:none;cursor:pointer;" aria-label="Tutup">
            <i class="bi bi-x-lg text-xs"></i>
        </button>

        <!-- Header Modal: Identity -->
        <div class="text-center mb-4 px-2">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-violet-50 text-violet-700 rounded-full text-[11px] font-bold mb-2.5">
                <i class="bi bi-qr-code-scan"></i> Kartu Presensi Digital
            </div>
            <h3 class="text-base font-extrabold text-slate-900 m-0 line-clamp-1"><?= esc($siswa->nama ?? 'Siswa') ?></h3>
            <p class="text-xs text-slate-500 font-mono mt-1 mb-1.5">NIS: <?= esc($siswa->nis ?: '-') ?> &middot; Kelas <?= esc($siswa->nama_kelas ?: '-') ?></p>
            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                <?= esc($setting->sekolah ?? 'Sekolah') ?>
            </span>
        </div>

        <!-- High-Contrast QR Code Card -->
        <div class="p-4 bg-slate-50/90 rounded-2xl border border-slate-200/80 flex flex-col items-center justify-center">
            <div id="modalStudentQr" class="p-2.5 bg-white rounded-xl shadow-xs border border-slate-200" style="width:196px;height:196px;display:flex;align-items:center;justify-content:center;"></div>
            <p class="text-xs font-extrabold text-slate-800 mt-2.5 font-mono mb-0 tracking-wider"><?= esc($qrSiswaVal) ?></p>
            <p class="text-[10px] text-violet-700 font-medium mt-1 mb-0 text-center">
                <i class="bi bi-camera-fill mr-1"></i>Arahkan ke kamera Kiosk Scanner di sekolah
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2.5 mt-4">
            <?php 
            $idK = (int) ($siswa->id_kelas ?? 0);
            $cetakUrl = site_url('admin/presensi/siswa/kartu/cetak?id_siswa=' . $siswa->id_siswa . ($idK ? '&id_kelas=' . $idK : '') . '&desain=portrait&v=3');
            ?>
            <a href="<?= $cetakUrl ?>" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold no-underline transition-all shadow-sm shadow-violet-500/25">
                <i class="bi bi-printer-fill"></i> Cetak Kartu
            </a>
            <button type="button" onclick="closeStudentQrModal()" class="flex-1 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all border-0 cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/qrcode.min.js') ?>"></script>
<script>
    let studentQrGenerated = false;
    window.openStudentQrModal = function() {
        const modal = document.getElementById('student-qr-modal');
        const card  = document.getElementById('student-qr-card');
        if (!modal) {
            console.error('Modal student-qr-modal not found');
            return;
        }
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
            if (card) card.style.transform = 'translateY(0)';
        });

        if (!studentQrGenerated) {
            const qrEl = document.getElementById('modalStudentQr');
            const qrText = <?= json_encode((string) $qrSiswaVal) ?>;
            if (qrEl) {
                if (window.QRCode) {
                    try {
                        qrEl.innerHTML = '';
                        new QRCode(qrEl, {
                            text: qrText,
                            width: 176,
                            height: 176,
                            colorDark: "#1e1b4b",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                        studentQrGenerated = true;
                    } catch (e) {
                        console.error('Error generating student QR with QRCode.js:', e);
                        renderStudentQrFallback(qrEl, qrText);
                    }
                } else {
                    renderStudentQrFallback(qrEl, qrText);
                }
            }
        }
    };

    function renderStudentQrFallback(el, text) {
        if (!el) return;
        el.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(text) + '" alt="QR Code" style="width:176px;height:176px;display:block;border-radius:8px;" onerror="this.outerHTML=\'<span class=\\\'font-mono font-bold text-sm\\\'>' + text + '</span>\'">';
        studentQrGenerated = true;
    }

    window.closeStudentQrModal = function() {
        const modal = document.getElementById('student-qr-modal');
        const card  = document.getElementById('student-qr-card');
        if (!modal) return;
        modal.style.opacity = '0';
        if (card) card.style.transform = 'translateY(20px)';
        setTimeout(() => { modal.style.display = 'none'; }, 250);
    };

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('student-qr-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) window.closeStudentQrModal();
            });
        }
        if (window.location.hash === '#qr' || window.location.hash === '#kartu-qr') {
            window.openStudentQrModal();
        }
    });
</script>
<script>
$(document).ready(function() {
    const catBadge = {
        hadir: '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Hadir</span>',
        izin: '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Izin</span>',
        sakit: '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Sakit</span>',
        alpha: '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Alpha</span>',
        bdr: '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">BDR</span>',
        tanpa_keterangan: '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Tanpa Ket.</span>'
    };
    const escJs = (s) => $('<div>').text(s ?? '').html();

    function loadRiwayat() {
        const bulan = $('#filterBulan').val() || '';
        $('#riwayatList').html('<div class="text-center py-6 text-slate-400 text-xs"><div class="spinner-border spinner-border-sm text-violet-600 mb-2"></div><p class="m-0">Memuat riwayat...</p></div>');
        
        $.get('<?= site_url('siswa/presensi/history') ?>', { bulan: bulan }, function(res) {
            if (!res || !res.status) {
                $('#riwayatList').html('<div class="text-center py-6 text-rose-500 text-xs"><p class="m-0">Gagal memuat riwayat.</p></div>');
                return;
            }
            const rows = res.data || [];
            if (!rows.length) {
                $('#riwayatList').html('<div class="text-center py-8 text-slate-400 text-xs"><i class="bi bi-inbox text-3xl text-slate-300 block mb-2"></i><p class="m-0">Belum ada data presensi bulan ini.</p></div>');
            } else {
                let html = '';
                rows.forEach((r) => {
                    const d = new Date(r.tgl + 'T00:00:00');
                    const tglNum = isNaN(d) ? '-' : d.getDate();
                    const bln = isNaN(d) ? '' : d.toLocaleDateString('id-ID', { month: 'short' });
                    const masuk = (r.jam_masuk || '').substring(0, 5);
                    const keluar = (r.jam_keluar || '').substring(0, 5);
                    const waktu = keluar ? escJs(masuk) + ' <span class="text-slate-300 mx-1">→</span> ' + escJs(keluar)
                                          : escJs(masuk || '-');
                    const sub = r.keterangan
                        ? '<p class="text-slate-400 truncate text-[10px] m-0 mt-0.5 font-medium">' + escJs(r.keterangan) + '</p>'
                        : (!keluar && r.kategori === 'hadir'
                            ? '<p class="text-amber-500 font-bold text-[10px] m-0 mt-0.5">Belum absen pulang</p>'
                            : '');
                    html += '<div class="riwayat-row">' +
                        '<div class="riwayat-date-badge"><span class="font-black text-sm leading-none">' + tglNum + '</span>' +
                        '<span class="font-bold text-[9px] uppercase mt-0.5">' + escJs(bln) + '</span></div>' +
                        '<div class="flex-1 min-w-0">' +
                            '<p class="font-extrabold text-slate-800 text-xs m-0">' + waktu + '</p>' + sub +
                        '</div>' +
                        '<div class="shrink-0">' + (catBadge[r.kategori] || '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">' + escJs(r.status || '-') + '</span>') + '</div>' +
                        '</div>';
                });
                $('#riwayatList').html(html);
            }
            if (res.stats) {
                $('#statHadir').text(res.stats.hadir);
                $('#statIzin').text(res.stats.izin);
                $('#statSakit').text(res.stats.sakit);
                $('#statAlpha').text(res.stats.alpha);
                $('#statBdr').text(res.stats.bdr || 0);
                $('#statTanpaKet').text(res.stats.tanpa_keterangan || 0);
                if ($('#statLainnya').length) {
                    $('#statLainnya').text(res.stats.lainnya || 0);
                }
                $('#statPersen').text(res.stats.persen);
                $('#barPersen').css('width', res.stats.persen + '%');
            }
        }, 'json').fail(function() {
            $('#riwayatList').html('<div class="text-center py-6 text-rose-500 text-xs"><p class="m-0">Gagal menghubungi server.</p></div>');
        });
    }

    $('#filterBulan').on('change', loadRiwayat);
    loadRiwayat();
});
</script>
<?= $this->endSection() ?>
