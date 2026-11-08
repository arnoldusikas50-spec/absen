<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .kelas-row { transition: background .12s; }
    .kelas-row:hover { background: #f8fafc; }
    .avatar-kelas { width: 36px; height: 36px; border-radius: 12px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:11px; letter-spacing:.3px; }
    .action-btn { width:32px; height:32px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; transition: all .14s; }
    .action-btn:hover { transform: translateY(-1px); }
    .filter-chip { transition: all .15s; }
    .filter-chip.active { background:var(--primary-600); color:#fff; border-color:var(--primary-600); box-shadow:0 4px 12px -4px rgb(var(--primary-600-rgb) / .4); }
    #table-kelas_wrapper .dataTables_filter { display:none; }
    #table-kelas_wrapper .dataTables_length { display:none; }
    #table-kelas_wrapper .dataTables_info { font-size:11px; color:#94a3b8; font-weight:600; padding-top: .8rem; }
    #table-kelas_wrapper .dataTables_paginate { padding-top:.8rem; }
    .table-sticky thead th { position:sticky; top:0; z-index:5; background:#f8fafc; }
    .bulk-chip { animation: chipIn .2s ease; }
    @keyframes chipIn { from { opacity:0; transform: scale(.9); } to { opacity:1; transform:scale(1);} }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$totalKelas = count($kelas ?? []);
$totalSiswa = 0;
$kosong = 0;
$levelCounts = [];
foreach ($kelas ?? [] as $k) {
    $j = intval($k->jml_siswa ?? 0);
    $totalSiswa += $j;
    if ($j === 0) $kosong++;
    $lvl = $k->level_id ?? '—';
    $levelCounts[$lvl] = ($levelCounts[$lvl] ?? 0) + 1;
}
$avgPerKelas = $totalKelas > 0 ? round($totalSiswa / $totalKelas, 1) : 0;
$tpTahun = esc($tp_active->tahun ?? '-');
$smtVal  = esc($smt_active->smt ?? '-');
?>

<div class="fade-in-up">
    <!-- ── Page Header ─────────────────────────────── -->
    <div class="px-4 md:px-6 pt-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
                    <i class="bi bi-building text-xl"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-[19px] md:text-xl font-extrabold text-slate-800 tracking-tight leading-none truncate">Data Kelas / Rombel</h1>
                    <p class="text-xs text-slate-500 font-medium mt-1 flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center gap-1"><i class="bi bi-calendar3 text-slate-400"></i> TP <?= $tpTahun ?></span>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <span>Semester <?= $smtVal ?></span>
                        <span class="hidden sm:inline-flex items-center gap-1 ml-1 px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> <?= $totalKelas ?> rombel aktif
                        </span>
                    </p>
                </div>
            </div>
            <nav aria-label="breadcrumb" class="shrink-0">
                <ol class="flex items-center gap-1.5 text-xs font-semibold bg-white px-3.5 py-2 rounded-xl border border-slate-200/80 shadow-sm">
                    <li><a href="<?= site_url('dashboard') ?>" class="text-slate-500 hover:text-blue-600 transition-colors no-underline inline-flex items-center gap-1"><i class="bi bi-house-door"></i> Dashboard</a></li>
                    <li class="text-slate-300">/</li>
                    <li class="text-slate-400">Data Umum</li>
                    <li class="text-slate-300">/</li>
                    <li class="text-blue-600">Kelas</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- ── Main Card ─────────────────────────────── -->
    <div class="p-4 md:p-6">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Card header -->
            <div class="px-5 md:px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-gradient-to-r from-slate-50/80 to-white">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-sm">
                        <i class="bi bi-collection-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold text-slate-800 leading-none flex items-center gap-2">
                            Daftar Kelas / Rombongan Belajar
                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full bg-slate-900 text-white text-[10px] font-bold"><?= $totalKelas ?> Kelas</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1 truncate">Kelola rombel, wali kelas, dan distribusi siswa · <span class="font-semibold text-slate-700"><?= number_format($totalSiswa) ?> siswa terpetakan</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap shrink-0">
                    <button type="button" onclick="location.reload()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm" title="Muat ulang data">
                        <i class="bi bi-arrow-clockwise"></i> <span class="hidden sm:inline">Reload</span>
                    </button>
                    <button type="button" data-ui-toggle="modal" data-ui-target="#bulkModal" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors">
                        <i class="bi bi-layers"></i> Tambah Sekaligus
                    </button>
                    <a href="<?= site_url('datakelas/add') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-md shadow-blue-500/20 no-underline">
                        <i class="bi bi-plus-lg"></i> Tambah Kelas
                    </a>
                </div>
            </div>

            <!-- Toolbar: Search + Filters -->
            <div class="px-5 md:px-6 py-3.5 border-b border-slate-100 bg-white">
                <div class="flex flex-col lg:flex-row gap-3 lg:items-center justify-between">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="relative flex-1 max-w-[360px]">
                            <input id="kelasSearch" type="text" placeholder="Cari kelas, wali, atau jurusan..." class="w-full pl-9 pr-8 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all placeholder:text-slate-400">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <button type="button" id="clearKelasSearch" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-600 inline-flex items-center justify-center text-xs">&times;</button>
                        </div>
                        <span id="kelasCount" class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-[11px] font-bold text-slate-600"><?= $totalKelas ?> data</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider shrink-0"><i class="bi bi-funnel"></i> Filter:</div>
                        <select id="filterLevel" class="px-3 py-1.5 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 outline-none">
                            <option value="">Semua Tingkat</option>
                            <?php foreach (($level ?? []) as $lid => $lname): ?>
                                <option value="<?= esc($lname) ?>"><?= esc($lname) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filterWali" class="px-3 py-1.5 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 outline-none">
                            <option value="">Semua Wali</option>
                            <option value="__empty__">Belum diatur</option>
                            <option value="__filled__">Sudah diatur</option>
                        </select>
                        <button id="btnResetFilter" type="button" class="px-3 py-1.5 text-xs font-bold text-slate-500 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-colors">Reset</button>
                    </div>
                </div>
                <!-- Quick level chips -->
                <?php if (!empty($level)): ?>
                <div class="flex items-center gap-1.5 mt-3 flex-wrap">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Tingkat:</span>
                    <button type="button" data-chip-level="" class="filter-chip active px-3 py-1 rounded-full text-xs font-bold bg-slate-100 border border-slate-200 text-slate-600">Semua</button>
                    <?php foreach ($level as $lid => $lname): ?>
                        <button type="button" data-chip-level="<?= esc($lname) ?>" class="filter-chip px-3 py-1 rounded-full text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600"><?= esc($lname) ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Table / Empty -->
            <div class="p-0">
                <?php if ($totalKelas === 0): ?>
                    <div class="p-8 md:p-10 text-center">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 text-blue-500 flex items-center justify-center text-3xl">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h3 class="text-base font-extrabold text-slate-800">Belum Ada Data Kelas</h3>
                            <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                                Belum ada rombel terdaftar untuk <span class="font-bold text-slate-700">TP <?= $tpTahun ?> Semester <?= $smtVal ?></span>.
                                Mulai dengan membuat kelas pertama atau generate sekaligus.
                            </p>
                            <div class="flex items-center justify-center gap-2 mt-5 flex-wrap">
                                <a href="<?= site_url('datakelas/add') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 shadow-md shadow-blue-500/20 no-underline">
                                    <i class="bi bi-plus-lg"></i> Buat Kelas Pertama
                                </a>
                                <button type="button" data-ui-toggle="modal" data-ui-target="#bulkModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50">
                                    <i class="bi bi-layers"></i> Generate Sekaligus
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-4"><i class="bi bi-lightbulb text-amber-500"></i> Tips: gunakan <b>Tambah Sekaligus</b> untuk membuat X-A, X-B, XI-A … secara otomatis.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-auto">
                        <table id="table-kelas" class="w-full text-sm text-left border-collapse min-w-[760px]">
                            <thead class="table-sticky text-[11px] font-bold tracking-wider uppercase text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-3 text-center w-12">#</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <?php if (($setting->jenjang ?? '') == '3'): ?>
                                        <th class="py-3 px-3">Jurusan</th>
                                    <?php endif; ?>
                                    <th class="py-3 px-3 text-center w-[110px]">Siswa</th>
                                    <th class="py-3 px-4">Wali Kelas</th>
                                    <th class="py-3 px-3 text-center w-[80px]">Import</th>
                                    <th class="py-3 px-4 text-center w-[140px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <?php $no = 1; foreach ($kelas as $kls):
                                    $jml = intval($kls->jml_siswa ?? 0);
                                    // color for jumlah
                                    if ($jml === 0) { $badgeCls = 'bg-rose-50 text-rose-700 border-rose-200'; $icon='bi-exclamation-circle'; }
                                    elseif ($jml < 10) { $badgeCls = 'bg-amber-50 text-amber-700 border-amber-200'; $icon='bi-people'; }
                                    else { $badgeCls = 'bg-emerald-50 text-emerald-700 border-emerald-200'; $icon='bi-people-fill'; }

                                    $initial = strtoupper(substr(trim($kls->nama_kelas ?? 'K'),0,2));
                                    // hash color
                                    $colors = ['bg-blue-600','bg-indigo-600','bg-violet-600','bg-emerald-600','bg-amber-600','bg-rose-600','bg-cyan-600','bg-slate-800'];
                                    $colorIdx = abs(crc32($kls->nama_kelas ?? '')) % count($colors);
                                    $avatarBg = $colors[$colorIdx];

                                    $jurusanBadge = esc($kls->nama_jurusan ?? '-');
                                    $waliNama = trim($kls->nama_guru ?? '');
                                    $waliInitial = $waliNama ? strtoupper(substr($waliNama,0,1)) : '?';
                                ?>
                                <tr class="kelas-row group" data-nama="<?= esc(strtolower($kls->nama_kelas ?? '')) ?>" data-jurusan="<?= esc(strtolower($kls->nama_jurusan ?? '')) ?>" data-wali="<?= esc(strtolower($waliNama)) ?>" data-level="<?= esc($kls->level_id ?? '') ?>">
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs"><?= $no++ ?></td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar-kelas <?= $avatarBg ?> text-white shadow-sm"><?= esc($initial) ?></span>
                                            <div class="min-w-0">
                                                <div class="font-extrabold text-slate-800 leading-none truncate max-w-[160px]" title="<?= esc($kls->nama_kelas) ?>"><?= esc($kls->nama_kelas) ?></div>
                                                <div class="text-[11px] text-slate-400 font-medium mt-0.5 flex items-center gap-1">
                                                    <span class="inline-flex items-center gap-0.5"><i class="bi bi-hash text-[10px]"></i><?= esc($kls->kode_kelas ?: '-') ?></span>
                                                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                    <span><?= esc($kls->level_id ? ($level[$kls->level_id] ?? 'Level '.$kls->level_id) : '-') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <?php if (($setting->jenjang ?? '') == '3'): ?>
                                        <td class="py-3.5 px-3">
                                            <?php if (!empty($kls->nama_jurusan) && $kls->nama_jurusan !== '-'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100"><?= esc($kls->nama_jurusan) ?></span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-400 border border-slate-200">Umum</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold border <?= $badgeCls ?>">
                                            <i class="bi <?= $icon ?> text-[11px]"></i> <?= $jml ?> Siswa
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($waliNama): ?>
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center text-xs font-extrabold shrink-0"><?= esc($waliInitial) ?></span>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-800 text-xs leading-none truncate max-w-[140px]" title="<?= esc($waliNama) ?>"><?= esc($waliNama) ?></div>
                                                    <div class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Wali aktif</div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold"><i class="bi bi-person-exclamation text-[11px]"></i> Belum diatur</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <a href="<?= site_url('datasiswa/add/' . $kls->id_kelas) ?>" class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 hover:border-blue-600 shadow-sm" title="Import siswa ke <?= esc($kls->nama_kelas) ?>">
                                            <i class="bi bi-upload text-xs"></i>
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="<?= site_url('datakelas/detail/' . $kls->id_kelas) ?>" class="action-btn bg-sky-50 text-sky-600 hover:bg-sky-600 hover:text-white border border-sky-100 hover:border-sky-600 shadow-sm" title="Detail">
                                                <i class="bi bi-eye text-xs"></i>
                                            </a>
                                            <a href="<?= site_url('datakelas/edit/' . $kls->id_kelas) ?>" class="action-btn bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white border border-amber-100 hover:border-amber-600 shadow-sm" title="Edit">
                                                <i class="bi bi-pencil-square text-xs"></i>
                                            </a>
                                            <button data-id="<?= $kls->id_kelas ?>" data-nama="<?= esc($kls->nama_kelas) ?>" type="button" class="hapuskelas action-btn bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 shadow-sm" title="Hapus">
                                                <i class="bi bi-trash3 text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Table footer summary -->
                    <div class="px-5 md:px-6 py-3 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <div class="text-slate-500 font-medium">
                            Menampilkan <span class="font-extrabold text-slate-700"><?= $totalKelas ?> kelas</span> &middot; <span class="font-extrabold text-slate-700"><?= number_format($totalSiswa) ?> siswa</span> &middot; Rata-rata <b class="text-slate-700"><?= $avgPerKelas ?></b> siswa/kelas
                        </div>
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Terisi</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> &lt;10</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Kosong (<?= $kosong ?>)</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Helper Links -->
        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
            <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Pintasan:</span>
            <a href="<?= site_url('datakelas/manage') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-blue-300 hover:text-blue-600 font-semibold no-underline shadow-sm"><i class="bi bi-arrow-left-right text-blue-500"></i> Atur per Semester</a>
            <a href="<?= site_url('datakelas/kenaikan') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-violet-300 hover:text-violet-600 font-semibold no-underline shadow-sm"><i class="bi bi-arrow-up-circle text-violet-500"></i> Kenaikan Kelas</a>
            <a href="<?= site_url('datasiswa') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:border-emerald-300 hover:text-emerald-600 font-semibold no-underline shadow-sm"><i class="bi bi-people text-emerald-500"></i> Data Siswa</a>
        </div>
    </div>
</div>

<!-- ── Modal Bulk Create ─────────────────────── -->
<div id="bulkModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-[560px] max-h-[90vh] flex flex-col">
        <div class="bg-white rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex items-center justify-between">
                <h5 class="font-extrabold text-white text-sm flex items-center gap-2.5 mb-0">
                    <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center"><i class="bi bi-layers-fill text-sm"></i></span>
                    Tambah Kelas Sekaligus
                </h5>
                <button type="button" class="w-8 h-8 rounded-xl bg-white/15 hover:bg-white/25 text-white inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <form id="bulk-create" class="flex flex-col flex-1 min-h-0">
                <?= csrf_field() ?>
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs leading-relaxed flex gap-2">
                        <i class="bi bi-lightbulb-fill text-amber-600 mt-0.5 shrink-0"></i>
                        <span>Buat banyak kelas sekaligus. Contoh: <b>X A – X C</b> atau <b>10 1 – 10 3</b>. Preview akan muncul otomatis di bawah.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">Format Tingkat <span class="text-rose-500">*</span></label>
                            <select id="formatAngka" name="formatAngka" class="w-full px-3.5 py-2.5 text-sm font-medium bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all" required>
                                <option value="" selected disabled>Pilih Format</option>
                                <option value="romawi">Romawi (X, XI, XII)</option>
                                <option value="biasa">Biasa (10, 11, 12)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">Tingkat / Level <span class="text-rose-500">*</span></label>
                            <select id="level" name="level" class="w-full px-3.5 py-2.5 text-sm font-medium bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all" required>
                                <option value="" selected disabled>Pilih Level</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">Suffix <span class="text-rose-500">*</span></label>
                            <select id="char" name="char" class="w-full px-3.5 py-2.5 text-sm font-medium bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all" required>
                                <option value="" selected disabled>Pilih</option>
                                <option value="huruf">Huruf (A,B,C)</option>
                                <option value="angka">Angka (1,2,3)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">Mulai Dari <span class="text-rose-500">*</span></label>
                            <select id="awal" name="awal" class="w-full px-3.5 py-2.5 text-sm font-medium bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all" required>
                                <option value="" selected disabled>Awal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold text-slate-600 uppercase tracking-wider mb-1.5">Jumlah <span class="text-rose-500">*</span></label>
                            <input type="number" id="jumlah" name="jumlah" class="w-full px-3.5 py-2.5 text-sm font-medium bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all" required min="1" max="20" placeholder="1-20">
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Preview ( <span id="previewCount" class="text-emerald-600">0</span> kelas )</span>
                            <span class="text-[11px] text-slate-400">Akan dibuat di TP <?= $tpTahun ?> Smt <?= $smtVal ?></span>
                        </div>
                        <div id="hasil-kelas" class="flex flex-wrap gap-1.5 min-h-[36px] items-center">
                            <span class="text-xs text-slate-400 italic">Lengkapi pilihan di atas untuk melihat preview…</span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2 shrink-0">
                    <button type="button" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors shadow-md shadow-emerald-500/20">
                        <i class="bi bi-check-lg"></i> Buat Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const jenjang = '<?= $setting->jenjang ?? '3' ?>';
    let lvl, jml, char, awal, formatAngka;
    let tempArrKelas = [];

    $(document).ready(function () {
        const $table = $('#table-kelas');
        let dt = null;
        if ($table.length && $table.find('tbody tr').length > 0) {
            dt = $table.DataTable({
                paging: true,
                pageLength: 12,
                lengthChange: false,
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [0, 5, 6] },
                    { searchable: false, targets: [0, 5, 6] }
                ],
                language: typeof dt_lang_id !== 'undefined' ? dt_lang_id : {},
                drawCallback: function() {
                    const info = this.api().page.info();
                    $('#kelasCount').text(info.recordsDisplay + ' data');
                }
            });

            // Custom search
            $('#kelasSearch').on('keyup', function() {
                const v = this.value;
                dt.search(v).draw();
                $('#clearKelasSearch').toggleClass('hidden', !v);
            });
            $('#clearKelasSearch').on('click', function(){
                $('#kelasSearch').val('').trigger('keyup').focus();
            });

            // Filter level
            function applyFilters() {
                const lvlText = $('#filterLevel').val();
                const waliSel = $('#filterWali').val();
                $.fn.dataTable.ext.search = [];
                $.fn.dataTable.ext.search.push(function(settings, data, idx){
                    if (settings.nTable.id !== 'table-kelas') return true;
                    // data[1] = Kelas column text, data[3] = wali
                    const rowNode = dt.row(idx).node();
                    if (!rowNode) return true;
                    const $row = $(rowNode);
                    const nama = ($row.data('nama')||'').toString();
                    const wali = ($row.data('wali')||'').toString();
                    // level filter via text match in column 1 or 2
                    if (lvlText && !data[1].toLowerCase().includes(lvlText.toLowerCase()) && !($row.data('level')||'').toString().includes(lvlText)) {
                        // also check level select text
                        if (!data[1].includes(lvlText)) return false;
                    }
                    if (waliSel === '__empty__' && wali.trim() !== '') return false;
                    if (waliSel === '__filled__' && wali.trim() === '') return false;
                    return true;
                });
                dt.draw();
            }
            $('#filterLevel, #filterWali').on('change', applyFilters);
            $('#btnResetFilter').on('click', function(){
                $('#filterLevel').val('');
                $('#filterWali').val('');
                $('.filter-chip').removeClass('active');
                $('.filter-chip[data-chip-level=""]').addClass('active');
                $.fn.dataTable.ext.search = [];
                dt.search('').draw();
                $('#kelasSearch').val('');
                $('#clearKelasSearch').addClass('hidden');
            });

            // Chip levels
            $(document).on('click', '.filter-chip', function(){
                $('.filter-chip').removeClass('active');
                $(this).addClass('active');
                const lvlChip = $(this).data('chip-level');
                $('#filterLevel').val(lvlChip);
                $.fn.dataTable.ext.search = [];
                if (lvlChip) {
                    $.fn.dataTable.ext.search.push(function(s,d,i){
                        if (s.nTable.id !== 'table-kelas') return true;
                        return d[1].toLowerCase().includes(lvlChip.toLowerCase());
                    });
                }
                dt.draw();
            });
        } else {
            $('#kelasSearch, #filterLevel, #filterWali, #btnResetFilter').prop('disabled', true).addClass('opacity-50');
        }

        // Delete handler
        $(document).on('click', '.hapuskelas', function () {
            const kelasId = $(this).data('id');
            const namaKelas = $(this).data('nama') || '';
            Swal.fire({
                title: "Hapus Kelas " + namaKelas + "?",
                html: '<div class="text-sm text-slate-500">Kelas beserta pemetaan siswa di semester ini akan dihapus.<br><b class="text-rose-600">Tindakan tidak dapat dibatalkan.</b></div>',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e11d48",
                cancelButtonColor: "#64748b",
                confirmButtonText: '<i class="bi bi-trash mr-1"></i> Ya, Hapus!',
                cancelButtonText: "Batal",
                reverseButtons: true,
                customClass: { popup: 'rounded-2xl' }
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: base_url + 'datakelas/hapus/' + kelasId,
                        method: "POST",
                        dataType: "json",
                        beforeSend: () => Swal.showLoading(),
                        success: function (respon) {
                            if (respon.kelas) {
                                Swal.fire({ icon: "success", title: "Terhapus", text: "Kelas " + namaKelas + " berhasil dihapus", timer: 1500, showConfirmButton: false }).then(() => location.reload());
                            } else {
                                Swal.fire("Gagal", respon.message || "Tidak bisa menghapus kelas", "error");
                            }
                        },
                        error: function () { Swal.fire("Error", "Gagal menghubungi server", "error"); }
                    });
                }
            });
        });

        // ── Bulk modal logic ──────────────────────
        $('#formatAngka').change(function () {
            formatAngka = $(this).val();
            const opsiLevel = $('#level');
            opsiLevel.empty().append('<option value="" selected disabled>Pilih Level Kelas</option>');
            let arrLvl = [];
            if (jenjang === '1') arrLvl = ['1','2','3','4','5','6'];
            else if (jenjang === '2') arrLvl = ['7','8','9'];
            else arrLvl = ['10','11','12'];
            const fmt = (v) => formatAngka === 'romawi' ? toRoman(v) : v;
            arrLvl.forEach(lv => opsiLevel.append(`<option value="${lv}">${fmt(lv)}</option>`));
            triggerGenerate();
        });
        $('#char').change(function () {
            char = $(this).val();
            const opsiAwal = $('#awal');
            opsiAwal.empty().append('<option disabled selected value="">Pilih awal</option>');
            if (char === 'angka') {
                for (let i=1;i<=20;i++) opsiAwal.append(`<option value="${i}">${i}</option>`);
            } else if (char === 'huruf') {
                "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("").forEach(s => opsiAwal.append(`<option value="${s}">${s}</option>`));
            }
            triggerGenerate();
        });
        $('#level, #jumlah, #char, #awal, #formatAngka').on('change input', triggerGenerate);
        $('#awal, #level, #jumlah').on('change input', triggerGenerate);

        function triggerGenerate(){
            lvl = $('#level').val();
            jml = $('#jumlah').val();
            char = $('#char').val();
            awal = $('#awal').val();
            formatAngka = $('#formatAngka').val();
            if (lvl && jml && char && awal && formatAngka) generateNamaKelas();
            else {
                $('#hasil-kelas').html('<span class="text-xs text-slate-400 italic">Lengkapi semua pilihan untuk melihat preview…</span>');
                $('#previewCount').text('0');
                tempArrKelas = [];
            }
        }

        $('#bulk-create').submit(function (e) {
            e.preventDefault();
            if (tempArrKelas.length === 0) {
                Swal.fire("Perhatian", "Tidak ada kelas yang akan dibuat. Lengkapi form terlebih dahulu.", "warning");
                return;
            }
            const modalEl = document.getElementById('bulkModal');
            let formData = new FormData(this);
            formData.append('kelas', JSON.stringify(tempArrKelas));
            $.ajax({
                url: base_url + 'datakelas/bulk_create',
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                beforeSend: function () { Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() }); },
                success: function (respon) {
                    AdminUI.modal.close(modalEl);
                    if (respon.success) {
                        Swal.fire({ icon: "success", title: "Berhasil", text: respon.message || (respon.insert + " kelas berhasil ditambahkan"), timer: 2000, showConfirmButton: false }).then(() => location.reload());
                    } else { Swal.fire("Gagal", respon.message || "Gagal menyimpan data", "error"); }
                },
                error: function () { AdminUI.modal.close(modalEl); Swal.fire("Error", "Terjadi kesalahan pada server — hubungi administrator", "error"); }
            });
        });
    });

    function generateNamaKelas() {
        tempArrKelas = [];
        const jumlah = parseInt(jml);
        if (isNaN(jumlah) || jumlah < 1 || jumlah > 20) {
            $('#hasil-kelas').html('<span class="text-xs text-rose-500">Jumlah harus 1–20</span>');
            return;
        }
        const hasil = [];
        const fmt = (v) => formatAngka === 'romawi' ? toRoman(v) : v;
        if (char === 'angka') {
            let start = parseInt(awal);
            for (let i=0;i<jumlah;i++){
                let name = `${fmt(lvl)} ${start+i}`;
                tempArrKelas.push({ level_id: lvl, nama_kelas: name, kode_kelas: `${fmt(lvl)}${start+i}`, id_tp: '<?= $tp_active->id_tp ?>', id_smt: '<?= $smt_active->id_smt ?>', jumlah_siswa: '[]' });
                hasil.push(name);
            }
        } else if (char === 'huruf') {
            const alpha = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
            let startIdx = alpha.indexOf(awal.toUpperCase());
            if (startIdx === -1) startIdx = 0;
            for (let i=0;i<jumlah;i++){
                let charVal = alpha[startIdx+i] || `Z${i}`;
                let name = `${fmt(lvl)} ${charVal}`;
                tempArrKelas.push({ level_id: lvl, nama_kelas: name, kode_kelas: `${fmt(lvl)}${charVal}`, id_tp: '<?= $tp_active->id_tp ?>', id_smt: '<?= $smt_active->id_smt ?>', jumlah_siswa: '[]' });
                hasil.push(name);
            }
        }
        $('#previewCount').text(hasil.length);
        $('#hasil-kelas').html(hasil.map(n => `<span class="bulk-chip inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-white border border-blue-200 text-blue-700 shadow-sm">${n}</span>`).join(' '));
    }
    function toRoman(num) {
        const roman=[['M',1000],['CM',900],['D',500],['CD',400],['C',100],['XC',90],['L',50],['XL',40],['X',10],['IX',9],['V',5],['IV',4],['I',1]];
        let result=''; let n=parseInt(num);
        for(const [r,v] of roman){ while(n>=v){ result+=r; n-=v; } }
        return result;
    }
</script>
<?= $this->endSection() ?>

