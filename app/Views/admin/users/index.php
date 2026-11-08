<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Tab pill ───────────────────────────────── */
    .tab-pill { transition: background .15s, color .15s, box-shadow .15s; }
    .tab-pill.active {
        background: #fff;
        color: var(--primary-600);
        box-shadow: 0 1px 6px rgba(0,0,0,.08);
    }

    /* ── User avatar ────────────────────────────── */
    .u-avatar {
        width: 36px; height: 36px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
    }

    /* ── Status dot ─────────────────────────────── */
    .status-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    /* ── Table row hover ────────────────────────── */
    #tblAdmin tbody tr:hover,
    #tblGuru  tbody tr:hover,
    #tblSiswa tbody tr:hover { background: #f8fafc; }

    /* ── Skeleton shimmer ───────────────────────── */
    .skel {
        background: linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);
        background-size: 200% 100%;
        animation: sk 1.3s infinite;
        border-radius: 4px;
    }
    @keyframes sk { to { background-position: -200% 0; } }

    /* ── Pagination btn ─────────────────────────── */
    .pg-btn {
        min-width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; font-size: .78rem; font-weight: 700;
        border: 1px solid #e2e8f0;
        background: #fff; color: #475569;
        cursor: pointer; transition: background .12s, color .12s;
    }
    .pg-btn:hover { background: var(--primary-50); color: var(--primary-600); border-color: var(--primary-200); }
    .pg-btn.active { background: var(--primary-600); color: #fff; border-color: var(--primary-600); }
    .pg-btn:disabled { opacity: .4; cursor: not-allowed; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$currentUserId = $user->id ?? 0;
$isSuperAdmin  = ($currentUserId == 1);
?>

<!-- ── Header (pola kartu/dapodik/izin) ────────── -->
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-people-fill"></i> Kelola Akun Pengguna
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Pengguna</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola akun Admin, Guru, dan Siswa dalam satu halaman.</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-blue-600">Manajemen Pengguna</span>
        </nav>
    </div>

    <!-- ── Tab Navigation ────────────────────────── -->
    <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl w-fit overflow-x-auto max-w-full" id="userTabs">
        <button type="button" id="tabBtnAdmin" data-tab="admin"
            class="tab-pill active flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold whitespace-nowrap">
            <i class="bi bi-shield-lock-fill text-blue-500"></i> Admin
            <span class="ml-0.5 inline-flex items-center justify-center bg-blue-100 text-blue-700 text-[10px] font-extrabold rounded-full min-w-[18px] h-[18px] px-1"><?= $totalAdmin ?></span>
        </button>
        <button type="button" id="tabBtnGuru" data-tab="guru"
            class="tab-pill flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-slate-500 hover:text-slate-700 whitespace-nowrap">
            <i class="bi bi-person-video3 text-emerald-500"></i> Guru
            <span class="ml-0.5 inline-flex items-center justify-center bg-emerald-100 text-emerald-700 text-[10px] font-extrabold rounded-full min-w-[18px] h-[18px] px-1"><?= $totalGuru ?></span>
        </button>
        <button type="button" id="tabBtnSiswa" data-tab="siswa"
            class="tab-pill flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-slate-500 hover:text-slate-700 whitespace-nowrap">
            <i class="bi bi-mortarboard-fill text-violet-500"></i> Siswa
            <span class="ml-0.5 inline-flex items-center justify-center bg-violet-100 text-violet-700 text-[10px] font-extrabold rounded-full min-w-[18px] h-[18px] px-1"><?= $totalSiswa ?></span>
        </button>
    </div>

    <!-- ════════════════════════════════════════════
         TAB: ADMIN
    ════════════════════════════════════════════ -->
    <div id="tabAdmin">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Card header -->
            <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 leading-none">Daftar Administrator</h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">Akun dengan akses penuh sistem</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnReloadAdmin"
                        class="w-9 h-9 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100 flex items-center justify-center transition-colors" title="Reload">
                        <i class="bi bi-arrow-clockwise text-sm"></i>
                    </button>
                    <button type="button" data-ui-toggle="modal" data-ui-target="#modalTambahAdmin"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                        <i class="bi bi-plus-lg"></i> Tambah Admin
                    </button>
                </div>
            </div>

            <!-- Search bar admin -->
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                <div class="relative max-w-xs">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="adminSearch" placeholder="Cari nama / username..." autocomplete="off"
                        class="w-full pl-8 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" id="adminSearchClear"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size:.8125rem;" id="tblAdmin">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 w-10">#</th>
                            <th class="px-5 py-3.5">Nama</th>
                            <th class="px-4 py-3.5">Username</th>
                            <th class="px-4 py-3.5 hidden md:table-cell">Email</th>
                            <th class="px-4 py-3.5 text-center w-24 hidden sm:table-cell">Level</th>
                            <th class="px-4 py-3.5 text-center w-24">Status</th>
                            <th class="px-4 py-3.5 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tblAdminBody">
                        <?php foreach ($admins as $idx => $adm): ?>
                        <tr class="admin-row hover:bg-blue-50/30 transition-colors"
                            data-name="<?= strtolower(esc($adm->first_name . ' ' . $adm->last_name)) ?>"
                            data-username="<?= strtolower(esc($adm->username)) ?>">
                            <td class="px-5 py-3.5 text-slate-400 font-mono text-xs"><?= $idx + 1 ?></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">
                                        <?= strtoupper(substr($adm->first_name, 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate leading-none"><?= esc(trim($adm->first_name . ' ' . $adm->last_name)) ?></p>
                                        <?php if (!empty($adm->email)): ?>
                                        <p class="text-[10px] text-slate-400 mt-0.5 truncate md:hidden"><?= esc($adm->email) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md"><?= esc($adm->username) ?></span>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell text-slate-500 text-xs"><?= esc($adm->email ?? '-') ?></td>
                            <td class="px-4 py-3.5 text-center hidden sm:table-cell">
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold"><?= esc($adm->level ?? 'Admin') ?></span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <?php if ($adm->active == 1): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                                    <span class="status-dot bg-emerald-500"></span> Aktif
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">
                                    <span class="status-dot bg-red-500"></span> Nonaktif
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1.5">
                                    <?php if ((int)$adm->id === (int)$currentUserId): ?>
                                    <a href="<?= site_url('useradmin/edit/' . $adm->id) ?>"
                                        class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 flex items-center justify-center transition-colors"
                                        title="Edit Profil Saya">
                                        <i class="bi bi-person-gear text-xs"></i>
                                    </a>
                                    <?php elseif ($isSuperAdmin): ?>
                                    <a href="<?= site_url('useradmin/edit/' . $adm->id) ?>"
                                        class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-200 flex items-center justify-center transition-colors"
                                        title="Edit">
                                        <i class="bi bi-pencil text-xs"></i>
                                    </a>
                                    <button type="button" data-id="<?= (int) $adm->id ?>" data-nama="<?= esc(trim($adm->first_name . ' ' . $adm->last_name), 'attr') ?>"
                                        class="btn-hapus-admin w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 border border-red-200 flex items-center justify-center transition-colors"
                                        title="Hapus">
                                        <i class="bi bi-trash3 text-xs"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-slate-300" title="Tidak ada akses">
                                        <i class="bi bi-ban text-sm"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <i class="bi bi-shield-x block text-4xl mb-2 opacity-20"></i>
                                <p class="text-sm font-semibold">Belum ada administrator</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- No result -->
            <div id="adminNoResult" class="hidden px-5 py-10 text-center text-slate-400">
                <i class="bi bi-search block text-3xl mb-2 opacity-20"></i>
                <p class="text-sm font-semibold text-slate-600">Tidak ditemukan</p>
                <button type="button" id="adminResetSearch"
                    class="mt-2 text-xs text-blue-500 hover:underline border-none bg-transparent cursor-pointer">Reset pencarian</button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         TAB: GURU
    ════════════════════════════════════════════ -->
    <div id="tabGuru" class="hidden">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Card header -->
            <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 leading-none">Daftar Guru</h2>
                        <p class="text-[11px] text-slate-400 mt-0.5" id="guruSubtitle">Memuat data...</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" data-bulk-guru="aktifkan"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-200 hover:bg-emerald-100 transition-colors">
                        <i class="bi bi-people-fill"></i> Aktifkan Semua
                    </button>
                    <button type="button" data-bulk-guru="nonaktifkan"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-600 text-xs font-bold rounded-xl border border-red-200 hover:bg-red-100 transition-colors">
                        <i class="bi bi-slash-circle"></i> Nonaktifkan Semua
                    </button>
                    <button type="button" id="btnReloadGuru"
                        class="w-9 h-9 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100 flex items-center justify-center" title="Reload">
                        <i class="bi bi-arrow-clockwise text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Search guru -->
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                <div class="relative max-w-xs">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="guruSearch" placeholder="Cari nama guru..." autocomplete="off"
                        class="w-full pl-8 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <button type="button" id="guruSearchClear"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">&times;</button>
                </div>
            </div>

            <!-- Table guru (dibuild via JS) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size:.8125rem;" id="tblGuru">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 w-10">#</th>
                            <th class="px-5 py-3.5">Nama Guru</th>
                            <th class="px-4 py-3.5 hidden sm:table-cell">Username</th>
                            <th class="px-4 py-3.5 text-center hidden md:table-cell w-28">Jabatan</th>
                            <th class="px-4 py-3.5 text-center w-28">Reset</th>
                            <th class="px-4 py-3.5 text-center w-24">Status</th>
                            <th class="px-4 py-3.5 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tblGuruBody" class="divide-y divide-slate-100">
                        <!-- Skeleton rows -->
                        <?php for ($s = 0; $s < 5; $s++): ?>
                        <tr>
                            <td class="px-5 py-3.5"><div class="skel h-3 w-6"></div></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="skel w-8 h-8 rounded-lg"></div>
                                    <div class="skel h-3 w-32 rounded"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 hidden sm:table-cell"><div class="skel h-3 w-24 rounded"></div></td>
                            <td class="px-4 py-3.5 hidden md:table-cell"><div class="skel h-4 w-16 rounded-full mx-auto"></div></td>
                            <td class="px-4 py-3.5 text-center"><div class="skel h-7 w-7 rounded-lg mx-auto"></div></td>
                            <td class="px-4 py-3.5 text-center"><div class="skel h-4 w-14 rounded-full mx-auto"></div></td>
                            <td class="px-4 py-3.5 text-center"><div class="skel h-7 w-14 rounded-lg mx-auto"></div></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <div id="guruEmptyState" class="hidden px-5 py-12 text-center text-slate-400">
                <i class="bi bi-person-x block text-4xl mb-2 opacity-20"></i>
                <p class="text-sm font-semibold text-slate-600">Tidak ada data guru</p>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         TAB: SISWA
    ════════════════════════════════════════════ -->
    <div id="tabSiswa" class="hidden">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Card header -->
            <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 leading-none">Daftar Siswa</h2>
                        <p class="text-[11px] text-slate-400 mt-0.5" id="siswaSubtitle">Memuat data...</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" data-bulk-siswa="aktifkan"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-violet-50 text-violet-700 text-xs font-bold rounded-xl border border-violet-200 hover:bg-violet-100 transition-colors">
                        <i class="bi bi-people-fill"></i> Aktifkan Semua
                    </button>
                    <button type="button" data-bulk-siswa="nonaktifkan"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-600 text-xs font-bold rounded-xl border border-red-200 hover:bg-red-100 transition-colors">
                        <i class="bi bi-slash-circle"></i> Nonaktifkan Semua
                    </button>
                </div>
            </div>

            <!-- Filter siswa -->
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[180px]" style="max-width:280px;">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="siswaSearch" placeholder="Cari nama / NIS / NISN..." autocomplete="off"
                        class="w-full pl-8 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                    <button type="button" id="siswaClear"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">&times;</button>
                </div>
                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-medium cursor-pointer select-none">
                    <input type="checkbox" id="siswaResetFilter" class="rounded border-slate-300 accent-violet-600"> Perlu reset login
                </label>
                <select id="siswaPerPage" class="form-select-tw text-xs" style="width:90px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <button type="button" id="btnSiswaSearch"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-violet-600 text-white text-xs font-bold rounded-xl hover:bg-violet-700 transition-colors shadow-sm">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>

            <!-- Table siswa (JS build) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size:.8125rem;" id="tblSiswa">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 w-10">#</th>
                            <th class="px-4 py-3.5 hidden sm:table-cell w-24">NIS</th>
                            <th class="px-5 py-3.5">Nama Siswa</th>
                            <th class="px-4 py-3.5 hidden md:table-cell w-28">Kelas</th>
                            <th class="px-4 py-3.5 hidden lg:table-cell">Username</th>
                            <th class="px-4 py-3.5 text-center w-28">Reset</th>
                            <th class="px-4 py-3.5 text-center w-24">Status</th>
                            <th class="px-4 py-3.5 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tblSiswaBody" class="divide-y divide-slate-100">
                        <?php for ($s = 0; $s < 6; $s++): ?>
                        <tr>
                            <td class="px-5 py-3.5"><div class="skel h-3 w-6"></div></td>
                            <td class="px-4 py-3.5 hidden sm:table-cell"><div class="skel h-3 w-16"></div></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="skel w-8 h-8 rounded-lg"></div>
                                    <div class="skel h-3 w-32 rounded"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell"><div class="skel h-4 w-14 rounded-full"></div></td>
                            <td class="px-4 py-3.5 hidden lg:table-cell"><div class="skel h-3 w-20 rounded"></div></td>
                            <td class="px-4 py-3.5 text-center"><div class="skel h-7 w-7 rounded-lg mx-auto"></div></td>
                            <td class="px-4 py-3.5 text-center"><div class="skel h-4 w-14 rounded-full mx-auto"></div></td>
                            <td class="px-4 py-3.5 text-center"><div class="skel h-7 w-20 rounded-lg mx-auto"></div></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Siswa footer: info + pagination -->
            <div id="siswaFooter" class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500 font-medium" id="siswaInfo"></p>
                <div class="flex items-center gap-1" id="siswaPagination"></div>
            </div>
        </div>
    </div>

</div><!-- /page wrapper -->

<!-- ══════════════════════════════════════════════
     Modal: Tambah Admin (pola permits)
══════════════════════════════════════════════ -->
<div id="modalTambahAdmin" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
            <form id="formTambahAdmin">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="bi bi-person-plus-fill text-sm"></i>
                        </div>
                        <h5 class="font-bold text-slate-800 text-sm m-0">Tambah Administrator Baru</h5>
                    </div>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup"
                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">
                        &times;
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label-tw">Nama Depan <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" class="form-control-tw" placeholder="Budi" required>
                        </div>
                        <div>
                            <label class="form-label-tw">Nama Belakang</label>
                            <input type="text" name="last_name" class="form-control-tw" placeholder="Santoso">
                        </div>
                    </div>
                    <div>
                        <label class="form-label-tw">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" class="form-control-tw" placeholder="budi.admin" required autocomplete="off">
                    </div>
                    <div>
                        <label class="form-label-tw">Email</label>
                        <input type="email" name="email" class="form-control-tw" placeholder="budi@sekolah.sch.id">
                    </div>
                    <div>
                        <label class="form-label-tw">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="admPwd" class="form-control-tw pr-10" placeholder="Min. 6 karakter" required autocomplete="new-password">
                            <button type="button" data-toggle-pwd="admPwd"
                                class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600 border-none bg-transparent cursor-pointer" title="Tampilkan password">
                                <i class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="form-label-tw">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="admPwdConfirm" class="form-control-tw pr-10" placeholder="Ulangi password" required>
                            <button type="button" data-toggle-pwd="admPwdConfirm"
                                class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600 border-none bg-transparent cursor-pointer" title="Tampilkan password">
                                <i class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-2">
                    <button type="button" data-ui-dismiss="modal" class="btn-tw btn-light">Batal</button>
                    <button type="reset" class="btn-tw btn-light"><i class="bi bi-arrow-repeat mr-1"></i>Reset</button>
                    <button type="submit" id="btnSimpanAdmin" class="btn-tw btn-primary">
                        <i class="bi bi-check-lg mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/ajax_request.js') ?>"></script>
<script>
/* ════════════════════════════════════════════
   GLOBALS — reuse dari layout (let CSRF_NAME, CSRF_HASH, base_url sudah ada)
════════════════════════════════════════════ */
// CSRF_NAME & CSRF_HASH sudah dideklarasikan di layouts/main.php sebagai `let`,
// jadi kita hanya perlu fungsi helper di bawah ini tanpa redeklararsi.

function csrfBody(extra = {}) {
    return { ...extra, [CSRF_NAME]: CSRF_HASH };
}
function postJson(url, data) {
    return fetch(base_url + url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ ...data, [CSRF_NAME]: CSRF_HASH })
    }).then(r => r.json()).then(res => {
        if (res[CSRF_NAME]) CSRF_HASH = res[CSRF_NAME];
        return res;
    });
}
function postForm(url, formData) {
    formData.set(CSRF_NAME, CSRF_HASH);
    return fetch(base_url + url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => { if (res[CSRF_NAME]) CSRF_HASH = res[CSRF_NAME]; return res; });
}

/* ════════════════════════════════════════════
   TAB SWITCHER
════════════════════════════════════════════ */
function switchTab(tab) {
    ['admin','guru','siswa'].forEach(t => {
        document.getElementById('tab' + cap(t)).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tabBtn' + cap(t));
        btn.classList.toggle('active', t === tab);
        btn.classList.toggle('text-slate-500', t !== tab);
    });
    if (tab === 'guru'  && !window._guruLoaded)  loadGuru();
    if (tab === 'siswa' && !window._siswaLoaded) { siswaPageChange(1); }
    localStorage.setItem('umTab', tab);
}
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

/* ════════════════════════════════════════════
   ADMIN TAB
════════════════════════════════════════════ */
function reloadAdmin() { location.reload(); }

function filterAdminTable() {
    const q = ($('#adminSearch').val() || '').toLowerCase().trim();
    $('#adminSearchClear').toggleClass('hidden', !q).toggleClass('inline-flex', !!q);
    let vis = 0;
    document.querySelectorAll('.admin-row').forEach(row => {
        const ok = !q || row.dataset.name.includes(q) || row.dataset.username.includes(q);
        row.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    document.getElementById('adminNoResult').classList.toggle('hidden', vis > 0 || !q);
}
window.filterAdminTable = filterAdminTable;

function hapusAdmin(id, nama) {
    Swal.fire({
        title: 'Hapus Administrator?',
        text: 'Akun "' + nama + '" akan dihapus permanen.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        fetch(base_url + 'useradmin/delete/' + encodeURIComponent(id), {
            method: 'POST',
            body: new URLSearchParams({ [CSRF_NAME]: CSRF_HASH })
        }).then(r => r.json()).then(res => {
            if (res.status) {
                Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.msg || '', timer: 1200, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Gagal', res.msg || 'Gagal menghapus', 'error');
            }
        }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.hapusAdmin = hapusAdmin;

document.getElementById('formTambahAdmin').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSimpanAdmin');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>Menyimpan...';

    const fd = new FormData(this);
    postForm('useradmin/create', fd).then(res => {
        if (res.status) {
            AdminUI.modal.close(document.getElementById('modalTambahAdmin'));
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.msg, timer: 1400, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            const errs = res.errors ? Object.values(res.errors).join('<br>') : (res.msg || 'Terjadi kesalahan');
            Swal.fire({ icon: 'error', title: 'Gagal', html: errs });
        }
    }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'))
      .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
});

/* ════════════════════════════════════════════
   GURU TAB
════════════════════════════════════════════ */
window._guruLoaded = false;
window._guruData   = [];

function loadGuru() {
    document.getElementById('guruSubtitle').textContent = 'Memuat data...';
    fetch(base_url + 'userguru/data')
        .then(r => r.json())
        .then(data => {
            window._guruData   = data;
            window._guruLoaded = true;
            renderGuruTable(data);
            document.getElementById('guruSubtitle').textContent = data.length + ' guru terdaftar';
        })
        .catch(() => {
            document.getElementById('guruSubtitle').textContent = 'Gagal memuat data';
        });
}

function renderGuruTable(data) {
    const tbody = document.getElementById('tblGuruBody');
    const empty = document.getElementById('guruEmptyState');
    if (!data || data.length === 0) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    const fallbackFoto = base_url + 'assets/img/siswa_sm.png';
    let html = '';
    data.forEach((g, idx) => {
        const isActive = g.active == '1' || g.active === 1;
        const hasReset = g.reset && g.reset !== '0';
        const fotoSrc  = g.foto || fallbackFoto;
        const jabatan  = g.level || '-';

        html += `
        <tr class="guru-row hover:bg-emerald-50/30 transition-colors" data-name="${escAttr((g.nama_guru || '').toLowerCase())}">
            <td class="px-5 py-3 text-slate-400 font-mono text-xs">${idx + 1}</td>
            <td class="px-5 py-3">
                <div class="flex items-center gap-2.5">
                    <img src="${escAttr(fotoSrc)}" class="u-avatar border border-slate-200"
                        onerror="this.onerror=null;this.src='${fallbackFoto}'" alt="">
                    <span class="font-semibold text-slate-800 truncate max-w-[180px]">${escHtml(g.nama_guru)}</span>
                </div>
            </td>
            <td class="px-4 py-3 hidden sm:table-cell">
                <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">${escHtml(g.username || '-')}</span>
            </td>
            <td class="px-4 py-3 text-center hidden md:table-cell">
                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">${escHtml(jabatan)}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" data-reset-guru="${escAttr(g.username || '')}" data-nama="${escAttr(g.nama_guru || '')}"
                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center border transition-colors
                        ${hasReset ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100' : 'bg-slate-100 text-slate-300 border-slate-200 cursor-not-allowed'}"
                    ${hasReset ? '' : 'disabled'}
                    title="${hasReset ? 'Reset ' + escAttr(g.reset) + ' login gagal' : 'Tidak ada login gagal'}">
                    <i class="bi bi-arrow-repeat text-xs"></i>
                </button>
                <button type="button" data-pw-guru="${escAttr(g.username || '')}" data-nama="${escAttr(g.nama_guru || '')}"
                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center border transition-colors bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100 ml-1"
                    title="Reset password ke default (123456)">
                    <i class="bi bi-key-fill text-xs"></i>
                </button>
            </td>
            <td class="px-4 py-3 text-center">
                ${isActive
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                           <span class="status-dot bg-emerald-500"></span>Aktif</span>`
                    : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">
                           <span class="status-dot bg-red-500"></span>Nonaktif</span>`
                }
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1.5">
                    <button type="button" data-toggle-guru="${escAttr(g.id ?? '')}" data-gid="${escAttr(g.id_guru ?? '')}" data-nama="${escAttr(g.nama_guru || '')}" data-action="${isActive ? 'nonaktifkan' : 'aktifkan'}"
                        class="w-8 h-8 rounded-lg inline-flex items-center justify-center border transition-colors
                            ${isActive ? 'bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-200 hover:bg-emerald-100'}"
                        title="${isActive ? 'Nonaktifkan' : 'Aktifkan'}">
                        <i class="bi bi-${isActive ? 'slash-circle' : 'person-check-fill'} text-xs"></i>
                    </button>
                    <a href="${base_url}userguru/edit/${encodeURIComponent(g.id_guru)}"
                        class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 inline-flex items-center justify-center transition-colors"
                        title="Edit">
                        <i class="bi bi-pencil text-xs"></i>
                    </a>
                </div>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function filterGuruTable() {
    const q = ($('#guruSearch').val() || '').toLowerCase().trim();
    $('#guruSearchClear').toggleClass('hidden', !q).toggleClass('inline-flex', !!q);
    const filtered = !q ? window._guruData : window._guruData.filter(g => (g.nama_guru || '').toLowerCase().includes(q));
    renderGuruTable(filtered);
    document.getElementById('guruSubtitle').textContent = filtered.length + ' guru' + (q ? ' (filter: "' + q + '")' : ' terdaftar');
}
window.filterGuruTable = filterGuruTable;
function clearGuruSearch() {
    document.getElementById('guruSearch').value = '';
    filterGuruTable();
}
window.clearGuruSearch = clearGuruSearch;

function toggleGuru(userId, guruId, nama, action) {
    Swal.fire({
        title: (action === 'aktifkan' ? 'Aktifkan' : 'Nonaktifkan') + ' "' + nama + '"?',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: action === 'aktifkan' ? '#16a34a' : '#d97706',
        cancelButtonColor: '#64748b',
        confirmButtonText: action === 'aktifkan' ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        postJson('userguru/activate', { id: userId, id_guru: guruId, nama, is_active: action })
            .then(r => {
                if (r.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: r.msg, timer: 1300, showConfirmButton: false })
                        .then(() => { window._guruLoaded = false; loadGuru(); });
                } else {
                    Swal.fire('Gagal', r.msg || 'Gagal mengubah status', 'error');
                }
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.toggleGuru = toggleGuru;

function resetLoginGuru(username, nama) {
    Swal.fire({
        title: 'Reset Login?',
        text: 'Hapus catatan login gagal untuk "' + nama + '"?',
        icon: 'question', showCancelButton: true,
        confirmButtonText: 'Ya, Reset', cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        postJson('userguru/reset_login', { username, nama })
            .then(r => {
                Swal.fire({ icon: r.status ? 'success' : 'error', title: r.status ? 'Berhasil' : 'Gagal', text: r.msg, timer: 1400, showConfirmButton: false })
                    .then(() => { window._guruLoaded = false; loadGuru(); });
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.resetLoginGuru = resetLoginGuru;

function resetPasswordGuru(username, nama) {
    if (!username) { Swal.fire('Perhatian', 'Username kosong.', 'warning'); return; }
    Swal.fire({
        title: 'Reset Password?',
        html: 'Password <strong>"' + escHtml(nama) + '"</strong> akan direset ke default:<br><span class="font-mono font-bold text-lg">123456</span>',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d97706', cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Reset', cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        postJson('userguru/reset_password', { username })
            .then(r => {
                Swal.fire({ icon: r.status ? 'success' : 'error', title: r.status ? 'Berhasil' : 'Gagal', text: r.msg || '', timer: r.status ? 2500 : undefined, showConfirmButton: !r.status });
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.resetPasswordGuru = resetPasswordGuru;

function aktifkanSemuaGuru(action) {
    Swal.fire({
        title: action === 'aktifkan' ? 'Aktifkan Semua Guru?' : 'Nonaktifkan Semua Guru?',
        text: 'Tindakan ini akan mempengaruhi semua akun guru.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: action === 'aktifkan' ? '#16a34a' : '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Lanjutkan', cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => postJson('userguru/aktifkansemua', { is_active: action }),
        allowOutsideClick: () => !Swal.isLoading()
    }).then(res => {
        if (res.isConfirmed && res.value) {
            Swal.fire({ icon: res.value.status ? 'success' : 'error', title: res.value.status ? 'Berhasil!' : 'Gagal', text: res.value.msg })
                .then(() => { window._guruLoaded = false; loadGuru(); });
        }
    });
}

/* ════════════════════════════════════════════
   SISWA TAB
════════════════════════════════════════════ */
window._siswaLoaded = false;
let siswaPage   = 1;

function siswaPageChange(page) {
    siswaPage = Math.max(1, parseInt(page) || 1);
    loadSiswa();
}
window.siswaPageChange = siswaPageChange;
function clearSiswaSearch() {
    document.getElementById('siswaSearch').value = '';
    document.getElementById('siswaClear').classList.add('hidden');
    siswaPageChange(1);
}
window.clearSiswaSearch = clearSiswaSearch;

function loadSiswa() {
    const q       = document.getElementById('siswaSearch').value.trim();
    const perPage = parseInt(document.getElementById('siswaPerPage').value);
    const reset   = document.getElementById('siswaResetFilter').checked ? '1' : '0';

    document.getElementById('siswaClear').classList.toggle('hidden', !q);
    document.getElementById('siswaSubtitle').textContent = 'Memuat data...';

    const fd = new FormData();
    fd.append('page', siswaPage);
    fd.append('limit', perPage);
    fd.append('reset', reset);
    if (q) fd.append('search', q);

    postForm('usersiswa/list', fd).then(data => {
        window._siswaLoaded = true;
        renderSiswaTable(data, perPage);
        buildSiswaPagination(data.pages, perPage, data.total);
        document.getElementById('siswaSubtitle').textContent =
            data.total + ' siswa' + (q ? ` (filter: "${q}")` : '');
    }).catch(() => {
        document.getElementById('siswaSubtitle').textContent = 'Gagal memuat data';
    });
}

function renderSiswaTable(data, perPage) {
    const tbody = document.getElementById('tblSiswaBody');
    const lists = data.lists || [];

    if (!lists.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-5 py-12 text-center text-slate-400">
            <i class="bi bi-person-x block text-4xl mb-2 opacity-20"></i>
            <p class="text-sm font-semibold text-slate-600">Tidak ada data siswa</p></td></tr>`;
        return;
    }

    const offset = (siswaPage - 1) * perPage;
    const fallbackFoto = base_url + 'assets/img/siswa_sm.png';
    let html = '';
    lists.forEach((s, i) => {
        const isActive  = s.active == '1' || s.active === 1;
        const hasReset  = s.reset && parseInt(s.reset) > 0;
        const kls       = s.nama_kelas || '-';
        const fotoSrc   = s.foto || fallbackFoto;

        html += `
        <tr class="hover:bg-violet-50/30 transition-colors">
            <td class="px-5 py-3 text-slate-400 font-mono text-xs">${offset + i + 1}</td>
            <td class="px-4 py-3 hidden sm:table-cell text-xs text-slate-500 font-mono">${escHtml(s.nis || '-')}</td>
            <td class="px-5 py-3">
                <div class="flex items-center gap-2.5">
                    <img src="${escAttr(fotoSrc)}" class="u-avatar border border-slate-200"
                        onerror="this.onerror=null;this.src='${fallbackFoto}'" alt="">
                    <span class="font-semibold text-slate-800 truncate max-w-[160px]">${escHtml(s.nama)}</span>
                </div>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
                <span class="inline-flex px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 text-[10px] font-bold">${escHtml(kls)}</span>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell">
                <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">${escHtml(s.username || '-')}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" data-reset-siswa="${escAttr(s.username || '')}" data-nama="${escAttr(s.nama || '')}"
                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center border transition-colors
                        ${hasReset ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100' : 'bg-slate-100 text-slate-300 border-slate-200 cursor-not-allowed'}"
                    ${hasReset ? '' : 'disabled'}
                    title="${hasReset ? 'Reset ' + escAttr(s.reset) + ' login gagal' : 'Tidak ada login gagal'}">
                    <i class="bi bi-arrow-repeat text-xs"></i>
                </button>
                <button type="button" data-pw-siswa="${escAttr(s.username || '')}" data-nama="${escAttr(s.nama || '')}"
                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center border transition-colors bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100 ml-1"
                    title="Reset password ke default (NISN)">
                    <i class="bi bi-key-fill text-xs"></i>
                </button>
            </td>
            <td class="px-4 py-3 text-center">
                ${isActive
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                           <span class="status-dot bg-emerald-500"></span>Aktif</span>`
                    : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">
                           <span class="status-dot bg-red-500"></span>Nonaktif</span>`
                }
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1.5">
                    ${isActive
                        ? `<button type="button" data-toggle-siswa="${escAttr(s.id_siswa ?? '')}" data-nama="${escAttr(s.nama || '')}" data-action="nonaktifkan"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 text-[10px] font-bold transition-colors">
                                <i class="bi bi-slash-circle text-[10px]"></i>Nonaktif</button>`
                        : `<button type="button" data-toggle-siswa="${escAttr(s.id_siswa ?? '')}" data-nama="${escAttr(s.nama || '')}" data-action="aktifkan"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 text-[10px] font-bold transition-colors">
                                <i class="bi bi-person-check-fill text-[10px]"></i>Aktifkan</button>`
                    }
                </div>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function buildSiswaPagination(totalPages, perPage, totalData) {
    const el   = document.getElementById('siswaPagination');
    const info = document.getElementById('siswaInfo');
    const from = totalData > 0 ? (siswaPage - 1) * perPage + 1 : 0;
    const to   = Math.min(siswaPage * perPage, totalData);
    info.textContent = `Menampilkan ${from}–${to} dari ${totalData} siswa`;

    if (totalPages <= 1) { el.innerHTML = ''; return; }

    let btns = '';
    btns += `<button type="button" class="pg-btn" data-page="${siswaPage - 1}" ${siswaPage <= 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left text-xs"></i></button>`;

    const range = 2;
    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= siswaPage - range && p <= siswaPage + range)) {
            btns += `<button type="button" class="pg-btn ${p === siswaPage ? 'active' : ''}" data-page="${p}">${p}</button>`;
        } else if (p === siswaPage - range - 1 || p === siswaPage + range + 1) {
            btns += `<span class="px-1 text-slate-400 text-xs">…</span>`;
        }
    }

    btns += `<button type="button" class="pg-btn" data-page="${siswaPage + 1}" ${siswaPage >= totalPages ? 'disabled' : ''}>
                <i class="bi bi-chevron-right text-xs"></i></button>`;
    el.innerHTML = btns;
}

function toggleSiswa(idSiswa, nama, action) {
    Swal.fire({
        title: (action === 'aktifkan' ? 'Aktifkan' : 'Nonaktifkan') + ' "' + nama + '"?',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: action === 'aktifkan' ? '#16a34a' : '#d97706',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya', cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        postJson('usersiswa/activate', { id_siswa: idSiswa, is_active: action, nama })
            .then(r => {
                if (r.status) {
                    loadSiswa();
                } else {
                    Swal.fire('Gagal', r.msg || 'Gagal mengubah status', 'error');
                }
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.toggleSiswa = toggleSiswa;

function resetLoginSiswa(username, nama) {
    Swal.fire({
        title: 'Reset Login?',
        text: 'Hapus catatan login gagal untuk "' + nama + '"?',
        icon: 'question', showCancelButton: true,
        confirmButtonText: 'Ya, Reset', cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        postJson('usersiswa/reset_login', { username })
            .then((r) => {
                Swal.fire({ icon: r.status ? 'success' : 'error', title: r.status ? 'Berhasil' : 'Gagal', text: r.msg || '', timer: 1200, showConfirmButton: false });
                loadSiswa();
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.resetLoginSiswa = resetLoginSiswa;

function resetPasswordSiswa(username, nama) {
    if (!username) { Swal.fire('Perhatian', 'Username kosong.', 'warning'); return; }
    Swal.fire({
        title: 'Reset Password?',
        html: 'Password <strong>"' + escHtml(nama) + '"</strong> akan direset ke default:<br><span class="font-mono font-bold text-lg">NISN siswa</span>',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d97706', cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Reset', cancelButtonText: 'Batal'
    }).then(res => {
        if (!res.isConfirmed) return;
        postJson('usersiswa/reset_password', { username })
            .then(r => {
                Swal.fire({ icon: r.status ? 'success' : 'error', title: r.status ? 'Berhasil' : 'Gagal', text: r.msg || '', timer: r.status ? 2500 : undefined, showConfirmButton: !r.status });
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
    });
}
window.resetPasswordSiswa = resetPasswordSiswa;

function aktifkanSemuaSiswa(action) {
    Swal.fire({
        title: action === 'aktifkan' ? 'Aktifkan Semua Siswa?' : 'Nonaktifkan Semua Siswa?',
        text: 'Tindakan ini mempengaruhi seluruh akun siswa yang sudah terdaftar.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: action === 'aktifkan' ? '#7c3aed' : '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Lanjutkan', cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => postJson('usersiswa/aktifkansemua', { is_active: action }),
        allowOutsideClick: () => !Swal.isLoading()
    }).then(res => {
        if (res.isConfirmed && res.value) {
            Swal.fire({ icon: res.value.status ? 'success' : 'error', title: res.value.status ? 'Berhasil!' : 'Gagal', text: res.value.msg })
                .then(() => loadSiswa());
        }
    });
}

/* ════════════════════════════════════════════
   UTILITIES
════════════════════════════════════════════ */
function togglePwd(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    const ic = btn.querySelector('i');
    el.type  = el.type === 'password' ? 'text' : 'password';
    if (ic) ic.className = el.type === 'text' ? 'bi bi-eye-slash text-sm' : 'bi bi-eye text-sm';
}
window.togglePwd = togglePwd;
function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}
function escAttr(s) {
    return String(s ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* ════════════════════════════════════════════
   INIT
════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    // Tab switching (delegated, pola dapodik)
    $('#userTabs').on('click', '[data-tab]', function () {
        switchTab(this.dataset.tab);
    });

    // Restore last active tab
    switchTab(localStorage.getItem('umTab') || 'admin');

    // ── Admin tab bindings ──
    $('#btnReloadAdmin').on('click', reloadAdmin);
    $('#adminSearch').on('input', filterAdminTable);
    $('#adminSearchClear').on('click', function () {
        $('#adminSearch').val('');
        filterAdminTable();
        $('#adminSearch').focus();
    });
    $('#adminResetSearch').on('click', function () {
        $('#adminSearch').val('');
        filterAdminTable();
    });
    $(document).on('click', '.btn-hapus-admin', function () {
        hapusAdmin($(this).data('id'), $(this).data('nama'));
    });

    // ── Guru tab bindings ──
    $('#btnReloadGuru').on('click', function () { window._guruLoaded = false; loadGuru(); });
    $('#guruSearch').on('input', filterGuruTable);
    $('#guruSearchClear').on('click', clearGuruSearch);
    $(document).on('click', '[data-bulk-guru]', function () {
        aktifkanSemuaGuru($(this).data('bulk-guru'));
    });
    $(document).on('click', '[data-reset-guru]', function () {
        if ($(this).prop('disabled')) return;
        resetLoginGuru($(this).data('reset-guru'), $(this).data('nama'));
    });
    $(document).on('click', '[data-pw-guru]', function () {
        resetPasswordGuru($(this).data('pw-guru'), $(this).data('nama'));
    });
    $(document).on('click', '[data-toggle-guru]', function () {
        const $b = $(this);
        toggleGuru($b.data('toggle-guru'), $b.data('gid'), $b.data('nama'), $b.data('action'));
    });

    // ── Siswa tab bindings ──
    $('#btnSiswaSearch').on('click', () => siswaPageChange(1));
    $('#siswaPerPage').on('change', () => siswaPageChange(1));
    $('#siswaClear').on('click', clearSiswaSearch);
    $(document).on('click', '[data-bulk-siswa]', function () {
        aktifkanSemuaSiswa($(this).data('bulk-siswa'));
    });
    $(document).on('click', '[data-reset-siswa]', function () {
        if ($(this).prop('disabled')) return;
        resetLoginSiswa($(this).data('reset-siswa'), $(this).data('nama'));
    });
    $(document).on('click', '[data-pw-siswa]', function () {
        resetPasswordSiswa($(this).data('pw-siswa'), $(this).data('nama'));
    });
    $(document).on('click', '[data-toggle-siswa]', function () {
        const $b = $(this);
        toggleSiswa($b.data('toggle-siswa'), $b.data('nama'), $b.data('action'));
    });
    $('#siswaPagination').on('click', '.pg-btn[data-page]', function () {
        if ($(this).prop('disabled')) return;
        siswaPageChange($(this).data('page'));
    });

    // ── Modal helpers ──
    $(document).on('click', '[data-toggle-pwd]', function () {
        togglePwd($(this).data('toggle-pwd'), this);
    });

    // Siswa search — Enter key trigger
    document.getElementById('siswaSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') siswaPageChange(1);
    });
    document.getElementById('siswaSearch').addEventListener('input', function() {
        document.getElementById('siswaClear').classList.toggle('hidden', !this.value);
    });
    document.getElementById('siswaResetFilter').addEventListener('change', () => siswaPageChange(1));
});
</script>
<?= $this->endSection() ?>
