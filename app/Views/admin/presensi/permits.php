<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ── Filter chips ── */
.filter-chip {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.75rem; border-radius: 9999px;
    font-size: 0.75rem; font-weight: 600; cursor: pointer;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    transition: all 0.15s ease; white-space: nowrap; user-select: none;
}
.filter-chip:hover  { border-color: var(--primary-600); color: var(--primary-600); background: var(--primary-50); }
.filter-chip.active { background: var(--primary-600); color: #fff; border-color: var(--primary-600); }
.filter-chip.active .chip-count { background: rgba(255,255,255,0.25); color: #fff; }

/* ── Kartu jenis pengajuan ──
   Utility has-[:checked] / peer-checked tidak ada di build Tailwind v3 saat ini,
   jadi kartu yang dipilih dulu tidak pernah tampil beda. State diambil alih
   selector saudara (input:checked + body) yang didukung semua browser target. */
.opt-jenis-body { transition: border-color 0.15s ease, background-color 0.15s ease; }
.opt-jenis input:checked + .opt-jenis-body {
    border-color: var(--primary-500); background-color: var(--primary-50);
}
.opt-jenis input:focus-visible + .opt-jenis-body {
    outline: 2px solid var(--primary-600); outline-offset: 2px;
}
.chip-count {
    background: #f1f5f9; color: #475569;
    font-size: 0.68rem; font-weight: 800;
    padding: 0.05rem 0.4rem; border-radius: 9999px;
    min-width: 18px; text-align: center;
}
/* ── Jenis color dots ── */
.dot-izin   { background: #0ea5e9; }
.dot-sakit  { background: #ef4444; }
.dot-cuti   { background: #f59e0b; }
.dot-dinas  { background: var(--accent-500); }
.badge-dinas { background: var(--accent-100); color: var(--accent-700); }
/* ── Row hover ── */
#permitsTable tbody tr:hover td { background: #f8fafc; }
#permitsTable tbody td { vertical-align: middle; }
.tabular { font-variant-numeric: tabular-nums; }
.clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
/* ── Date range ── */
.date-range { line-height: 1.35; }
.date-sep {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: 10px; font-weight: 700; color: #94a3b8;
    background: #f1f5f9; border-radius: 6px; padding: 0 5px; margin: 0 3px;
    vertical-align: 1px;
}
/* ── Status pill animation ── */
.pulse-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.pulse-pending { background: #f59e0b; animation: pulse 1.8s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$namaBulan = ['1'=>'Januari','2'=>'Februari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni',
              '7'=>'Juli','8'=>'Agustus','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$bulanKey  = ($bulan !== 'all' && is_numeric($bulan)) ? (string)(int)$bulan : 'all';
$periodeLabel = ($bulanKey !== 'all' ? ($namaBulan[$bulanKey] ?? $bulan) : 'Semua Bulan')
    . ($tahun !== 'all' ? ' ' . $tahun : '');
?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ───────────── PAGE HEADER (pola kartu/dapodik) ───────────── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-cyan-50 text-cyan-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-file-earmark-medical-fill"></i> Verifikasi Pengajuan Guru
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelola &amp; Verifikasi Izin / Cuti</h1>
            <p class="text-xs text-slate-500 mt-1">Verifikasi pengajuan izin, sakit, cuti, dan dinas luar guru.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" id="btnOpenManualPermit"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-plus-circle-fill"></i> Input Izin Manual
            </button>
            <a href="<?= site_url('admin/presensi') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- ── Main Card (pola dapodik: rounded-3xl) ── -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">

        <!-- Card header: filter periode + search -->
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-funnel text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Daftar Pengajuan Izin &amp; Cuti</h2>
            </div>
            <!-- Periode filter -->
            <form method="get" id="permitPeriodeForm" class="flex items-center gap-2 ml-0 md:ml-auto flex-wrap">
                <select name="bulan" class="form-select-tw form-select-sm" style="width:130px;" onchange="document.getElementById('permitPeriodeForm').submit()">
                    <option value="all" <?= $bulanKey === 'all' ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php foreach ($namaBulan as $num => $nama): ?>
                    <option value="<?= $num ?>" <?= $bulanKey == $num ? 'selected' : '' ?>><?= $nama ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="form-select-tw form-select-sm" style="width:100px;" onchange="document.getElementById('permitPeriodeForm').submit()">
                    <option value="all" <?= $tahun === 'all' ? 'selected' : '' ?>>Semua</option>
                    <?php for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                    <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-tw btn-tw-sm btn-primary" title="Terapkan filter periode">
                    <i class="bi bi-search"></i>
                </button>
            </form>
            <!-- Search -->
            <div class="relative" style="min-width:220px;">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchPermitInput"
                       class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Cari nama / NIP..." autocomplete="off">
                <button type="button" id="clearPermitSearch"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Filter chips -->
        <div class="px-5 py-3 bg-slate-50/60 border-b border-slate-100 flex flex-wrap gap-1.5 items-center" id="permitChips">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1">STATUS</span>
            <button type="button" class="filter-chip active" data-filter="all">
                Semua <span class="chip-count"><?= $stats['total'] ?></span>
            </button>
            <button type="button" class="filter-chip" data-filter="pending">
                <span class="pulse-dot pulse-pending"></span>
                Menunggu <span class="chip-count"><?= $stats['pending'] ?></span>
            </button>
            <button type="button" class="filter-chip" data-filter="approved">
                <i class="bi bi-check-circle-fill text-emerald-500 text-[10px]"></i>
                Disetujui <span class="chip-count"><?= $stats['approved'] ?></span>
            </button>
            <button type="button" class="filter-chip" data-filter="rejected">
                <i class="bi bi-x-circle-fill text-red-400 text-[10px]"></i>
                Ditolak <span class="chip-count"><?= $stats['rejected'] ?></span>
            </button>
            <span class="w-px h-4 bg-slate-200 mx-1"></span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1">JENIS</span>
            <button type="button" class="filter-chip" data-filter="izin">
                <span class="w-2 h-2 rounded-full dot-izin inline-block"></span>
                Izin <span class="chip-count"><?= $stats['izin'] ?></span>
            </button>
            <button type="button" class="filter-chip" data-filter="sakit">
                <span class="w-2 h-2 rounded-full dot-sakit inline-block"></span>
                Sakit <span class="chip-count"><?= $stats['sakit'] ?></span>
            </button>
            <button type="button" class="filter-chip" data-filter="cuti">
                <span class="w-2 h-2 rounded-full dot-cuti inline-block"></span>
                Cuti <span class="chip-count"><?= $stats['cuti'] ?></span>
            </button>
            <button type="button" class="filter-chip" data-filter="dinas">
                <span class="w-2 h-2 rounded-full dot-dinas inline-block"></span>
                Dinas <span class="chip-count"><?= $stats['dinas'] ?></span>
            </button>
        </div>

        <!-- Table -->
        <div class="table-responsive-tw" style="max-height:62vh;">
            <table class="table-tw" id="permitsTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width:44px">#</th>
                        <th style="min-width:200px">Guru / Pegawai</th>
                        <th class="text-center" style="width:92px">Jenis</th>
                        <th style="min-width:190px">Rentang Tanggal</th>
                        <th style="min-width:200px">Keterangan</th>
                        <th class="text-center" style="width:96px">Bukti</th>
                        <th class="text-center" style="width:112px">Status</th>
                        <th style="min-width:150px">Verifikator</th>
                        <th class="text-center" style="min-width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($permits)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-16 text-slate-400">
                            <i class="bi bi-file-earmark-check text-5xl block mb-3 opacity-20"></i>
                            <p class="font-medium">Belum ada data pengajuan pada periode ini</p>
                            <p class="text-xs mt-1">Periode: <?= esc($periodeLabel) ?></p>
                            <button type="button" class="btn-tw btn-tw-sm btn-outline-primary mt-3 btn-open-manual">
                                <i class="bi bi-plus mr-1"></i> Input Izin Manual
                            </button>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $no = 1;
                    foreach ($permits as $row):
                        // ── Nama guru
                        $namaGuru = !empty($row->nama_guru)
                            ? $row->nama_guru
                            : trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                        if (empty(trim($namaGuru))) $namaGuru = $row->username ?? '—';
                        $avatarFallback = \App\Services\AvatarService::cadangan();
                        $avatarSrc      = \App\Services\AvatarService::tampilan((string) ($row->foto ?? ''), $namaGuru, '#4338ca');

                        // ── Status
                        $statusStr = strtolower(trim((string)($row->status ?? 'pending')));
                        if ($statusStr === 'approved' || $statusStr === '1') {
                            $sBadge = 'badge-tw badge-success'; $sText = 'Disetujui';
                            $sIcon  = 'bi-check-circle-fill';   $sCat  = 'approved';
                        } elseif ($statusStr === 'rejected' || $statusStr === '2') {
                            $sBadge = 'badge-tw badge-danger'; $sText = 'Ditolak';
                            $sIcon  = 'bi-x-circle-fill';      $sCat  = 'rejected';
                        } else {
                            $sBadge = 'badge-tw badge-warning'; $sText = 'Menunggu';
                            $sIcon  = 'bi-clock-history';       $sCat  = 'pending';
                        }

                        // ── Jenis (normalise ijin → izin untuk tampil)
                        $jenisRaw   = strtolower(trim((string)($row->jenis ?? 'izin')));
                        $jenisCat   = ($jenisRaw === 'ijin') ? 'izin' : $jenisRaw;
                        switch ($jenisCat) {
                            case 'sakit':
                                $jBadge='badge-tw badge-danger';  $jIcon='bi-heart-pulse-fill';    $jText='Sakit'; break;
                            case 'cuti':
                                $jBadge='badge-tw badge-warning'; $jIcon='bi-calendar2-range-fill'; $jText='Cuti'; break;
                            case 'dinas':
                                $jBadge='badge-tw badge-dinas';   $jIcon='bi-briefcase-fill';       $jText='Dinas'; break;
                            default:
                                $jBadge='badge-tw badge-info';    $jIcon='bi-file-earmark-text';    $jText='Izin';
                        }

                        // Kategori cuti (Tahunan/Besar/Melahirkan/CAP) jika ada.
                        $kategoriCutiLabel = '';
                        if ($jenisCat === 'cuti'
                            && !empty($row->kategori_cuti)
                            && \App\Services\CutiKategoriService::valid((string) $row->kategori_cuti)) {
                            $kategoriCutiLabel = \App\Services\CutiKategoriService::label((string) $row->kategori_cuti);
                        }

                        // ── Durasi
                        $durasi = 1;
                        if (!empty($row->tgl_mulai) && !empty($row->tgl_selesai)) {
                            $durasi = max(1, (int) round((strtotime($row->tgl_selesai) - strtotime($row->tgl_mulai)) / 86400) + 1);
                        }

                        // ── Search keyword (untuk filter JS)
                        $searchKw = strtolower($namaGuru . ' ' . ($row->nip ?? '') . ' ' . ($row->keterangan ?? ''));
                    ?>
                    <tr data-search="<?= esc($searchKw) ?>"
                        data-status-cat="<?= $sCat ?>"
                        data-jenis-cat="<?= $jenisCat ?>">

                        <td class="text-center text-slate-400 text-xs font-mono"><?= $no++ ?></td>

                        <!-- Guru -->
                        <td>
                            <div class="flex items-center gap-2.5">
                                <img src="<?= $avatarSrc ?>" alt="<?= esc($namaGuru, 'attr') ?>" loading="lazy"
                                     class="w-9 h-9 rounded-full object-cover shrink-0 ring-1 ring-slate-200 bg-slate-100"
                                     onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 truncate leading-tight text-sm" title="<?= esc($namaGuru, 'attr') ?>"><?= esc($namaGuru) ?></p>
                                    <p class="text-[11px] text-slate-400 truncate tabular">NIP: <?= esc($row->nip ?? '—') ?></p>
                                </div>
                            </div>
                        </td>

                        <!-- Jenis -->
                        <td class="text-center">
                            <span class="badge-tw <?= $jBadge ?> inline-flex items-center gap-1">
                                <i class="bi <?= $jIcon ?> text-[10px]"></i><?= $jText ?>
                            </span>
                            <?php if ($kategoriCutiLabel !== ''): ?>
                                <div class="mt-1"><span class="badge-tw badge-secondary text-[10px] whitespace-nowrap" title="<?= esc($kategoriCutiLabel, 'attr') ?>"><?= esc($kategoriCutiLabel) ?></span></div>
                            <?php endif; ?>
                        </td>

                        <!-- Tanggal -->
                        <td>
                            <div class="date-range text-sm">
                                <span class="font-semibold text-slate-800 tabular whitespace-nowrap"><?= date('d/m/Y', strtotime($row->tgl_mulai)) ?></span>
                                <?php if ($row->tgl_mulai !== $row->tgl_selesai): ?>
                                    <span class="date-sep">s.d.</span>
                                    <span class="font-semibold text-slate-800 tabular whitespace-nowrap"><?= date('d/m/Y', strtotime($row->tgl_selesai)) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="badge-tw badge-secondary text-[10px] mt-1 inline-flex items-center gap-1 whitespace-nowrap">
                                <i class="bi bi-calendar3 text-[9px]"></i><?= $durasi ?> hari
                            </span>
                        </td>

                        <!-- Keterangan -->
                        <td style="max-width:220px;min-width:160px;">
                            <p class="text-[13px] text-slate-700 clamp-2 leading-snug"
                               title="<?= esc($row->keterangan ?? '') ?>">
                                <?= esc($row->keterangan ?: '—') ?>
                            </p>
                            <?php if (!empty($row->verifikasi_catatan)): ?>
                            <p class="text-xs text-red-500 truncate mt-0.5"
                               title="<?= esc($row->verifikasi_catatan) ?>">
                                <i class="bi bi-chat-left-quote mr-1"></i><?= esc($row->verifikasi_catatan) ?>
                            </p>
                            <?php endif; ?>
                        </td>

                        <!-- Bukti -->
                        <td class="text-center">
                            <?php if (!empty($row->bukti)): ?>
                            <?php
                                $buktiRaw = trim((string) $row->bukti);
                                if (preg_match('#^https?://#i', $buktiRaw)) {
                                    $buktiUrl = $buktiRaw;
                                } elseif (file_exists(FCPATH . ltrim($buktiRaw, '/'))) {
                                    $buktiUrl = base_url(ltrim($buktiRaw, '/'));
                                } elseif (file_exists(FCPATH . 'uploads/izin/' . basename($buktiRaw))) {
                                    $buktiUrl = base_url('uploads/izin/' . basename($buktiRaw));
                                } elseif (file_exists(FCPATH . 'uploads/surat_izin/' . basename($buktiRaw))) {
                                    $buktiUrl = base_url('uploads/surat_izin/' . basename($buktiRaw));
                                } else {
                                    $buktiUrl = base_url('uploads/izin/' . basename($buktiRaw));
                                }
                            ?>
                            <button type="button" class="btn-tw btn-tw-sm btn-outline-secondary btn-view-bukti whitespace-nowrap"
                                    data-url="<?= esc($buktiUrl, 'attr') ?>"
                                    data-nama="<?= esc($namaGuru, 'attr') ?>"
                                    title="Lihat lampiran bukti">
                                <i class="bi bi-paperclip mr-1 text-blue-600"></i>Bukti
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-50 border border-dashed border-slate-200 text-slate-300" title="Tanpa lampiran"><i class="bi bi-dash text-sm"></i></span>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td class="text-center">
                            <span class="badge-tw <?= $sBadge ?> inline-flex items-center gap-1">
                                <?php if ($sCat === 'pending'): ?>
                                <span class="pulse-dot pulse-pending"></span>
                                <?php else: ?>
                                <i class="bi <?= $sIcon ?> text-[10px]"></i>
                                <?php endif; ?>
                                <?= $sText ?>
                            </span>
                        </td>

                        <!-- Verifikator -->
                        <td style="min-width:140px;">
                            <?php if (!empty($row->verifier_first)): ?>
                            <p class="text-[13px] font-semibold text-slate-700 truncate leading-tight" title="<?= esc(trim($row->verifier_first . ' ' . ($row->verifier_last ?? '')), 'attr') ?>">
                                <i class="bi bi-person-check text-emerald-500 mr-1"></i><?= esc(trim($row->verifier_first . ' ' . ($row->verifier_last ?? ''))) ?>
                            </p>
                            <?php if (!empty($row->verified_at)): ?>
                            <p class="text-[11px] text-slate-400 tabular whitespace-nowrap mt-0.5">
                                <i class="bi bi-clock text-[9px] mr-0.5"></i><?= date('d/m/Y H:i', strtotime($row->verified_at)) ?>
                            </p>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-slate-300 text-xs">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Aksi -->
                        <td class="text-center">
                            <div class="inline-flex items-center gap-1.5 justify-center">
                                <?php if ($sCat === 'pending'): ?>
                                <button type="button" class="btn-tw btn-tw-sm btn-success btn-approve-permit whitespace-nowrap"
                                        data-id="<?= (int) $row->id ?>"
                                        data-nama="<?= esc($namaGuru, 'attr') ?>"
                                        title="Setujui pengajuan">
                                    <i class="bi bi-check-lg mr-1"></i>Setujui
                                </button>
                                <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-reject-permit whitespace-nowrap"
                                        data-id="<?= (int) $row->id ?>"
                                        data-nama="<?= esc($namaGuru, 'attr') ?>"
                                        title="Tolak pengajuan">
                                    <i class="bi bi-x-lg mr-1"></i>Tolak
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn-tw btn-tw-sm btn-light btn-delete-permit"
                                        data-id="<?= (int) $row->id ?>"
                                        title="Hapus data">
                                    <i class="bi bi-trash3 text-red-400"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer count -->
        <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
            <span id="tableCount">Menampilkan <?= count($permits) ?> data</span>
            <span>Periode: <?= esc($periodeLabel) ?></span>
        </div>

    </div><!-- /main card -->

</div><!-- /page wrapper -->


<!-- ══════════ MODAL: INPUT IZIN MANUAL ══════════ -->
<div id="manualPermitModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" style="display:none">
    <div class="modal-box-tw w-full max-w-xl">
        <form id="manualPermitForm" enctype="multipart/form-data" onsubmit="return submitManualPermit(event)">
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-cyan-100 flex items-center justify-center shrink-0">
                        <i class="bi bi-file-earmark-plus-fill text-cyan-600"></i>
                    </div>
                    <div>
                        <h5>Input Izin / Cuti Manual</h5>
                        <p class="text-xs text-slate-400 mt-0">Admin mengisi atas nama guru</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw space-y-4">

                <!-- Guru -->
                <div>
                    <label class="form-label-tw">Guru / Pegawai <span class="text-red-500">*</span></label>
                    <select name="user_id" class="form-select-tw" required>
                        <option value="">— Pilih Guru —</option>
                        <?php foreach ($teachers as $tc): ?>
                        <option value="<?= $tc->user_id ?>">
                            <?= esc($tc->nama_guru ?: trim(($tc->first_name ?? '') . ' ' . ($tc->last_name ?? ''))) ?>
                            <?= esc(!empty($tc->nip) ? ' · ' . $tc->nip : '') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jenis -->
                <div>
                    <label class="form-label-tw">Jenis Pengajuan <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <?php
                        $jenisOpts = [
                            'izin'  => ['icon'=>'bi-file-earmark-text',    'color'=>'text-sky-600',    'bg'=>'bg-sky-50',    'label'=>'Izin',       'sub'=>'Keperluan pribadi'],
                            'sakit' => ['icon'=>'bi-heart-pulse-fill',     'color'=>'text-red-500',    'bg'=>'bg-red-50',    'label'=>'Sakit',      'sub'=>'Surat keterangan dokter'],
                            'cuti'  => ['icon'=>'bi-calendar2-range-fill', 'color'=>'text-amber-500',  'bg'=>'bg-amber-50',  'label'=>'Cuti',       'sub'=>'Cuti tahunan / bersalin'],
                            'dinas' => ['icon'=>'bi-briefcase-fill',       'color'=>'text-violet-600', 'bg'=>'bg-violet-50', 'label'=>'Dinas Luar', 'sub'=>'Tugas/perjalanan dinas'],
                        ];
                        foreach ($jenisOpts as $val => $opt):
                        ?>
                        <label class="opt-jenis block cursor-pointer">
                            <input type="radio" name="jenis" value="<?= $val ?>" class="sr-only" <?= $val === 'izin' ? 'checked' : '' ?>>
                            <div class="opt-jenis-body flex items-center gap-2.5 p-2.5 rounded-lg border-2 border-slate-200 hover:border-blue-300">
                                <div class="w-8 h-8 rounded-lg <?= $opt['bg'] ?> flex items-center justify-center shrink-0">
                                    <i class="bi <?= $opt['icon'] ?> <?= $opt['color'] ?> text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800 leading-tight"><?= $opt['label'] ?></p>
                                    <p class="text-xs text-slate-400"><?= $opt['sub'] ?></p>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tanggal -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label-tw">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" name="tgl_mulai" id="manual_tgl_mulai"
                               class="form-control-tw" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label class="form-label-tw">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" name="tgl_selesai" id="manual_tgl_selesai"
                               class="form-control-tw" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <p class="text-xs text-slate-400 -mt-2" id="durasi-info">Durasi: 1 hari kerja (libur otomatis dilewati)</p>

                <!-- Keterangan -->
                <div>
                    <label class="form-label-tw">Keterangan / Alasan</label>
                    <textarea name="keterangan" class="form-control-tw" rows="2"
                              placeholder="Keperluan keluarga / diagnosa dokter / surat tugas..."></textarea>
                </div>

                <!-- Bukti -->
                <div>
                    <label class="form-label-tw">Lampiran Dokumen <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="file" name="bukti" class="form-control-tw"
                           accept="image/jpeg,image/png,application/pdf">
                    <p class="text-xs text-slate-400 mt-1">PDF/JPG/PNG · maks. 2 MB</p>
                </div>

                <!-- Langsung setujui -->
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="langsung_approve" value="1"
                               id="manual_auto_approve" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" checked>
                        <div>
                            <p class="text-sm font-bold text-emerald-700 leading-tight">
                                <i class="bi bi-check2-all mr-1"></i>Langsung Setujui &amp; Update Rekap Absensi
                            </p>
                            <p class="text-xs text-emerald-600 mt-0.5">
                                Absensi guru pada rentang tanggal tersebut otomatis tercatat dan poin kedisiplinan dihitung.
                                Hari libur mingguan &amp; nasional akan dilewati.
                            </p>
                        </div>
                    </label>
                </div>

            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-primary" id="btnSubmitManual">
                    <i class="bi bi-check2-circle mr-1"></i> Simpan Izin
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════ MODAL: TOLAK PENGAJUAN ══════════ -->
<div id="rejectModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" style="display:none">
    <div class="modal-box-tw w-full max-w-md">
        <form id="rejectForm" onsubmit="return submitReject(event)">
            <?= csrf_field() ?>
            <input type="hidden" name="permit_id" id="reject_id">
            <div class="modal-header-tw">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                        <i class="bi bi-x-circle-fill text-red-500"></i>
                    </div>
                    <h5>Tolak Pengajuan Izin</h5>
                </div>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw space-y-4">
                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 flex items-center gap-2">
                    <i class="bi bi-person-circle text-slate-400 text-xl shrink-0"></i>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm" id="reject_guru_name">—</p>
                        <p class="text-xs text-slate-400">Konfirmasi penolakan pengajuan izin</p>
                    </div>
                </div>
                <div>
                    <label class="form-label-tw">
                        Alasan Penolakan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="alasan" id="reject_alasan" class="form-control-tw" required
                           placeholder="cth: Bukti surat tidak valid / Sudah ada jadwal piket">
                    <p class="text-xs text-slate-400 mt-1">Alasan ini akan terlihat oleh guru</p>
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-light" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-danger">
                    <i class="bi bi-x-circle-fill mr-1"></i> Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════ MODAL: PREVIEW BUKTI ══════════ -->
<div id="buktiModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" style="display:none">
    <div class="modal-box-tw w-full max-w-2xl">
        <div class="modal-header-tw">
            <h5 id="buktiTitle"><i class="bi bi-paperclip mr-2 text-blue-600"></i>Lampiran Bukti</h5>
            <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body-tw text-center" style="min-height:200px; background:#f8fafc;">
            <img id="buktiImg" src="" alt="Bukti"
                 class="max-w-full h-auto rounded-lg shadow-sm hidden mx-auto"
                 style="max-height:65vh;">
            <div id="buktiFile" class="hidden py-10">
                <i class="bi bi-file-earmark-pdf text-5xl text-red-500 mb-3 block"></i>
                <p class="text-slate-600 font-medium mb-4">Dokumen PDF / File Lampiran</p>
                <a id="buktiFileLink" href="" target="_blank" class="btn-tw btn-primary">
                    <i class="bi bi-download mr-1"></i> Buka / Unduh Dokumen
                </a>
            </div>
        </div>
        <div class="modal-footer-tw">
            <a id="buktiOpen" href="" target="_blank" class="btn-tw btn-tw-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right mr-1"></i> Buka di Tab Baru
            </a>
            <button type="button" class="btn-tw btn-tw-sm btn-light" data-ui-dismiss="modal">Tutup</button>
        </div>
    </div>
</div>


<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // CSRF_NAME & CSRF_HASH sudah dideklarasikan global di layouts/main.php (let) —
    // JANGAN dideklarasikan ulang di sini (akan SyntaxError: redeclaration).
    function csrf() { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }
    function spin(cls) {
        return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 rounded-full ' + (cls || 'border-white/40 border-t-white') + '"></span>';
    }

    function jsonPost(url, data, cb) {
        $.ajax({ url: url, type: 'POST', data: data, dataType: 'json', success: cb,
            error: function() { Swal.fire('Error', 'Kesalahan koneksi server', 'error'); } });
    }

    // ── Filter chips + search (delegated, pola dapodik) ───────────────────
    let activeFilter = 'all';

    function applyFilter() {
        const kw = ($('#searchPermitInput').val() || '').toLowerCase().trim();
        $('#clearPermitSearch').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
        let shown = 0;
        $('#permitsTable tbody tr[data-search]').each(function() {
            const search = $(this).data('search') || '';
            const sCat   = $(this).data('status-cat') || '';
            const jCat   = $(this).data('jenis-cat') || '';

            let matchFilter = activeFilter === 'all';
            if (!matchFilter) {
                if (['pending','approved','rejected'].includes(activeFilter)) matchFilter = sCat === activeFilter;
                else if (activeFilter === 'izin')  matchFilter = jCat === 'izin' || jCat === 'ijin';
                else matchFilter = jCat === activeFilter;
            }

            const visible = matchFilter && (!kw || String(search).includes(kw));
            $(this).toggle(visible);
            if (visible) shown++;
        });
        let $empty = $('#permitEmptyFilter');
        if (shown === 0 && $('#permitsTable tbody tr[data-search]').length > 0) {
            if ($empty.length === 0) {
                $('#permitsTable tbody').append(
                    '<tr id="permitEmptyFilter"><td colspan="9" class="text-center py-12 text-slate-400">' +
                    '<i class="bi bi-search text-3xl block mb-2 opacity-30"></i>' +
                    '<p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok dengan filter</p>' +
                    '<p class="text-xs mt-1">Coba ubah kata kunci atau filter status / jenis</p></td></tr>'
                );
            } else { $empty.show(); }
        } else { $empty.hide(); }
        $('#tableCount').text('Menampilkan ' + shown + ' data');
    }

    $('#permitChips').on('click', '.filter-chip', function() {
        activeFilter = $(this).data('filter');
        $('#permitChips .filter-chip').removeClass('active');
        $(this).addClass('active');
        applyFilter();
    });

    $('#searchPermitInput').on('input', applyFilter);
    $('#clearPermitSearch').on('click', function() {
        $('#searchPermitInput').val('').trigger('input').focus();
    });
    applyFilter();

    // ── Durasi helper ─────────────────────────────────────────────────────
    function updateDurasi() {
        const a = $('#manual_tgl_mulai').val();
        const b = $('#manual_tgl_selesai').val();
        if (a && b) {
            const d = Math.round((new Date(b) - new Date(a)) / 86400000) + 1;
            $('#durasi-info').text('Durasi: ' + (d > 0 ? d : 1) + ' hari kalender (hari libur otomatis dilewati)');
        }
    }
    $('#manual_tgl_mulai, #manual_tgl_selesai').on('change', updateDurasi);

    // ── Modal: Input Manual ───────────────────────────────────────────────
    function openManualPermitModal() {
        const today = new Date().toLocaleDateString('en-CA');
        $('#manualPermitForm').get(0).reset();
        $('#manual_tgl_mulai').val(today);
        $('#manual_tgl_selesai').val(today);
        $('#manual_auto_approve').prop('checked', true);
        updateDurasi();
        AdminUI.modal.open('#manualPermitModal');
    }
    $('#btnOpenManualPermit').on('click', openManualPermitModal);
    $(document).on('click', '.btn-open-manual', openManualPermitModal);
    window.openManualPermitModal = openManualPermitModal;

    window.submitManualPermit = function (e) {
        e.preventDefault();
        const formData = new FormData($('#manualPermitForm').get(0));
        formData.set(CSRF_NAME, CSRF_HASH);
        const $btn = $('#btnSubmitManual');
        const orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Menyimpan...');

        $.ajax({
            url:         '<?= site_url('admin/presensi/izin/save-manual') ?>',
            type:        'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function (res) {
                if (res && res.status) {
                    AdminUI.modal.close('#manualPermitModal');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message,
                                timer: 1800, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                    $btn.prop('disabled', false).html(orig);
                }
            },
            error: function (xhr) {
                let msg = 'Kesalahan koneksi server (HTTP ' + (xhr && xhr.status ? xhr.status : '?') + ')';
                try {
                    const j = (xhr && xhr.responseJSON) || JSON.parse(xhr.responseText);
                    if (j && j.message) msg = j.message;
                } catch (e) {}
                // 409 = guard overlap/kuota; backend mendukung force=1 untuk override sadar.
                if (xhr && xhr.status === 409 && msg.indexOf('force=1') !== -1) {
                    Swal.fire({
                        title: 'Tetap simpan?',
                        text: msg.replace(' Ulangi dengan force=1 bila memang disengaja.', ''),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Ya, tetap simpan',
                        cancelButtonText: 'Batal'
                    }).then((r) => {
                        if (r.isConfirmed || r.value) {
                            formData.set('force', '1');
                            $btn.prop('disabled', true).html(spin() + ' Menyimpan...');
                            $.ajax({
                                url: '<?= site_url('admin/presensi/izin/save-manual') ?>',
                                type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
                                success: function (res2) {
                                    if (res2 && res2.status) {
                                        AdminUI.modal.close('#manualPermitModal');
                                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res2.message,
                                                    timer: 1800, showConfirmButton: false })
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Gagal', (res2 && res2.message) || 'Terjadi kesalahan', 'error');
                                        $btn.prop('disabled', false).html(orig);
                                    }
                                },
                                error: function () {
                                    Swal.fire('Gagal', msg, 'error');
                                    $btn.prop('disabled', false).html(orig);
                                }
                            });
                        } else {
                            $btn.prop('disabled', false).html(orig);
                        }
                    });
                    return;
                }
                Swal.fire('Gagal', msg, 'error');
                $btn.prop('disabled', false).html(orig);
            }
        });
        return false;
    };

    // ── Approve ───────────────────────────────────────────────────────────
    function approvePermit(id, nama) {
        Swal.fire({
            title: 'Setujui Pengajuan?',
            html:  'Pengajuan izin <strong></strong> akan disetujui.<br>' +
                   '<small class="text-slate-500">Data absensi pada rentang tanggal tersebut otomatis diperbarui.</small>',
            icon:  'question',
            showCancelButton:  true,
            confirmButtonColor: '#059669',
            cancelButtonColor:  '#94a3b8',
            confirmButtonText:  '<i class="bi bi-check-circle-fill mr-1"></i> Ya, Setujui',
            cancelButtonText:   'Batal',
            showLoaderOnConfirm: true,
            didOpen: (el) => { el.querySelector('strong').textContent = nama; },
            preConfirm: () =>
                $.ajax({ url: '<?= site_url('admin/presensi/izin/approve') ?>/' + encodeURIComponent(id),
                         type: 'POST', data: csrf(), dataType: 'json' })
                    .then(r => r, xhr => {
                        let msg = 'Terjadi kesalahan server (HTTP ' + xhr.status + ')';
                        try {
                            const j = xhr.responseJSON || JSON.parse(xhr.responseText);
                            if (j && j.message) msg = j.message;
                        } catch (e) {}
                        Swal.showValidationMessage(msg);
                        return false;
                    }),
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (!result.isConfirmed) return;
            const res = result.value;
            if (res && res.status) {
                Swal.fire({ icon: 'success', title: 'Disetujui!', text: res.message,
                            timer: 1600, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
            }
        });
    }
    $(document).on('click', '.btn-approve-permit', function() {
        approvePermit($(this).data('id'), $(this).data('nama'));
    });
    window.approvePermit = approvePermit;

    // ── Reject ────────────────────────────────────────────────────────────
    function openRejectModal(id, nama) {
        $('#reject_id').val(id);
        $('#reject_guru_name').text(nama);
        $('#reject_alasan').val('');
        AdminUI.modal.open('#rejectModal');
    }
    $(document).on('click', '.btn-reject-permit', function() {
        openRejectModal($(this).data('id'), $(this).data('nama'));
    });
    window.openRejectModal = openRejectModal;

    window.submitReject = function (e) {
        e.preventDefault();
        const id     = $('#reject_id').val();
        const alasan = $('#reject_alasan').val().trim();
        if (!alasan) { Swal.fire('Perhatian', 'Alasan penolakan wajib diisi', 'warning'); return false; }

        const payload = csrf();
        payload.alasan = alasan;
        jsonPost('<?= site_url('admin/presensi/izin/reject') ?>/' + encodeURIComponent(id), payload,
            function (res) {
                if (res && res.status) {
                    AdminUI.modal.close('#rejectModal');
                    Swal.fire({ icon: 'success', title: 'Ditolak!', text: res.message,
                                timer: 1600, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            });
        return false;
    };

    // ── Delete ────────────────────────────────────────────────────────────
    function deletePermit(id) {
        const doDelete = (force) => {
            const payload = csrf();
            if (force) payload.force = '1';
            jsonPost('<?= site_url('admin/presensi/izin/delete') ?>/' + encodeURIComponent(id), payload, function (res) {
                if (res && res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message,
                                timer: 2000, showConfirmButton: false })
                        .then(() => location.reload());
                } else if (!force && res && res.message && res.message.includes('approved')) {
                    // 409 — izin sudah approved, tawarkan hapus + revert
                    Swal.fire({
                        title: 'Izin Sudah Disetujui',
                        html:  $('<div>').text(res.message).html().replace(/\n/g,'<br>') +
                               '<br><br><strong>Lanjutkan? Baris absensi otomatis akan direvert.</strong>',
                        icon:  'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        confirmButtonText:  'Ya, Hapus + Revert',
                        cancelButtonText:   'Batal'
                    }).then(r => { if (r.isConfirmed) doDelete(true); });
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            });
        };

        Swal.fire({
            title: 'Hapus Data Izin?',
            text:  'Data pengajuan ini akan dihapus permanen.',
            icon:  'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor:  '#94a3b8',
            confirmButtonText:  'Ya, Hapus',
            cancelButtonText:   'Batal'
        }).then(r => { if (r.isConfirmed) doDelete(false); });
    }
    $(document).on('click', '.btn-delete-permit', function() {
        deletePermit($(this).data('id'));
    });
    window.deletePermit = deletePermit;

    // ── Preview bukti ─────────────────────────────────────────────────────
    function viewBukti(url, nama) {
        $('#buktiTitle').html('<i class="bi bi-paperclip mr-2 text-blue-600"></i>Bukti: ').append(document.createTextNode(nama));
        $('#buktiOpen').attr('href', url);
        $('#buktiFileLink').attr('href', url);

        const isImage = /\.(jpe?g|png|gif|webp)(\?.*)?$/i.test(url);
        if (isImage) {
            $('#buktiImg').attr('src', url).removeClass('hidden');
            $('#buktiFile').addClass('hidden');
        } else {
            $('#buktiImg').addClass('hidden');
            $('#buktiFile').removeClass('hidden');
        }
        AdminUI.modal.open('#buktiModal');
    }
    $(document).on('click', '.btn-view-bukti', function() {
        viewBukti($(this).data('url'), $(this).data('nama'));
    });
    window.viewBukti = viewBukti;
    window.filterPermitsTable = applyFilter;
    window.setFilter = function(f, el) {
        activeFilter = f;
        $('#permitChips .filter-chip').removeClass('active');
        if (el) $(el).addClass('active');
        else $('#permitChips .filter-chip[data-filter="' + f + '"]').addClass('active');
        applyFilter();
    };
});
</script>
<?= $this->endSection() ?>
