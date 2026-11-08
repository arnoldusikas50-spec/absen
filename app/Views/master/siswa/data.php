<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isAdmin   = (bool) ($is_admin ?? false);
$hasPeriode = (bool) ($has_periode ?? false);
$stats = array_merge([
    'total'         => 0,
    'aktif'         => 0,
    'lulus'         => 0,
    'pindah'        => 0,
    'keluar'        => 0,
    'tanpa_kelas'   => 0,
    'akun_nonaktif' => 0,
], is_array($stats ?? null) ? $stats : []);

$tpLabel = trim((string) ($tp_active->tahun ?? '')) ?: '-';
$smtLabel = trim((string) ($smt_active->nama_smt ?? '')) ?: (trim((string) ($smt_active->smt ?? '')) ?: '-');
$totalKelas = is_array($kelass ?? null) ? count($kelass) : 0;

$inputClass = 'w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden';
$labelClass = 'block text-xs font-semibold text-slate-700 mb-1.5';
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="bi bi-people-fill text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Data Siswa') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="text-slate-400">Data Umum</span>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($judul ?? 'Siswa') ?></span>
        </nav>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-area mt-4 space-y-5">

    <?php if (!$hasPeriode): ?>
        <div class="flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 shadow-xs">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-lg mt-0.5 shrink-0"></i>
            <div class="min-w-0">
                <p class="text-sm font-bold mb-0.5">Tahun Pelajaran / Semester aktif belum diset</p>
                <p class="text-xs mb-0">Daftar siswa dan filter kelas membutuhkan periode aktif. Atur TP/SMT terlebih dahulu agar data dapat ditampilkan.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Ringkasan data -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-blue-100 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-500 mb-0">Total siswa</p>
                    <p id="stat-total" class="text-2xl font-bold text-slate-800 leading-none mt-1.5"><?= number_format((int) $stats['total'], 0, ',', '.') ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-people-fill"></i>
                </span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 mb-0">Seluruh siswa terdaftar</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-emerald-100 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-500 mb-0">Siswa aktif</p>
                    <p id="stat-aktif" class="text-2xl font-bold text-emerald-700 leading-none mt-1.5"><?= number_format((int) $stats['aktif'], 0, ',', '.') ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-person-check-fill"></i>
                </span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 mb-0">Status buku induk aktif</p>
        </div>

        <div id="card-tanpa-kelas" class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-amber-300 hover:shadow-sm transition-all cursor-pointer <?= $hasPeriode ? '' : 'opacity-60 pointer-events-none' ?>" title="Klik untuk memfilter siswa yang belum ada kelas">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-500 mb-0">Tanpa kelas</p>
                    <p id="stat-tanpa-kelas" class="text-2xl font-bold text-amber-700 leading-none mt-1.5"><?= $hasPeriode ? number_format((int) $stats['tanpa_kelas'], 0, ',', '.') : '&mdash;' ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-mortarboard-fill"></i>
                </span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 mb-0">Belum punya kelas di TP aktif</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-rose-100 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-500 mb-0">Akun nonaktif</p>
                    <p id="stat-akun-nonaktif" class="text-2xl font-bold text-rose-700 leading-none mt-1.5"><?= number_format((int) $stats['akun_nonaktif'], 0, ',', '.') ?></p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-shield-slash-fill"></i>
                </span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 mb-0">Tidak dapat login ke aplikasi</p>
        </div>
    </div>

    <!-- Main Card Data Siswa -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <!-- Card Header Toolbar -->
        <div class="px-5 md:px-6 py-4 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-slate-50/50">
            <div class="min-w-0">
                <h4 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                    <i class="bi bi-person-lines-fill text-blue-600"></i>
                    Daftar Peserta Didik
                </h4>
                <p class="text-xs text-slate-500 mb-0">Kelola identitas siswa, kelas aktif, NIS/NISN, dan status akun.</p>
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">
                        <i class="bi bi-calendar3 text-slate-400"></i>
                        TP <?= esc($tpLabel) ?> &middot; <?= esc($smtLabel) ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 text-[11px] font-semibold">
                        <i class="bi bi-door-open text-blue-400"></i>
                        <?= number_format($totalKelas, 0, ',', '.') ?> kelas
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 w-full xl:w-auto">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="bi bi-search"></i>
                    </span>
                    <input id="input-search" type="search" placeholder="Cari nama, NIS, atau NISN..." aria-label="Cari siswa"
                        class="w-full pl-8 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                    <button id="btn-clear" type="button"
                        class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                        aria-label="Hapus pencarian">
                        <i class="bi bi-x-circle-fill text-xs"></i>
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="location.reload()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
                        <i class="bi bi-arrow-repeat"></i> Reload
                    </button>
                    <?php if ($isAdmin): ?>
                        <a href="<?= site_url('datasiswa/update') ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-xs">
                            <i class="bi bi-database-check text-emerald-600"></i> Update Data
                        </a>
                        <a href="<?= site_url('datasiswa/add') ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs">
                            <i class="bi bi-upload"></i> Import
                        </a>
                        <button type="button" data-ui-toggle="modal" data-ui-target="#createSiswaModal"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                            <i class="bi bi-plus-lg"></i> Tambah Siswa
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Filter & Bulk Actions -->
        <div class="px-5 md:px-6 py-3.5 border-b border-slate-100 bg-white">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                        <label for="users_length">Tampilkan</label>
                        <select id="users_length" aria-label="Jumlah data per halaman"
                            class="px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>data</span>
                    </div>

                    <span class="hidden sm:inline-block w-px h-5 bg-slate-200 mx-0.5" aria-hidden="true"></span>

                    <select id="users-status" aria-label="Filter status siswa"
                        class="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700 transition-colors">
                        <option value="0">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="2">Lulus</option>
                        <option value="3">Pindah</option>
                        <option value="4">Keluar</option>
                        <option value="5">Akun Nonaktif</option>
                    </select>

                    <select id="users-kelas" aria-label="Filter kelas"
                        class="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700 transition-colors">
                        <option value="">Semua Kelas</option>
                        <option value="tanpa">Tanpa Kelas (belum ada kelas)</option>
                        <?php foreach (($kelass ?? []) as $id => $name): ?>
                            <option value="<?= (int) $id ?>"><?= esc($name) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" id="btn-reset-filter"
                        class="hidden inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-100 transition-colors">
                        <i class="bi bi-x-circle"></i> Reset filter
                    </button>
                </div>

                <?php if ($isAdmin): ?>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="selection-bar" class="hidden inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-blue-50 border border-blue-100 text-[11px] font-semibold text-blue-700">
                            <i class="bi bi-check2-circle"></i>
                            <span id="selection-count">0</span> dipilih
                        </span>
                        <div class="relative">
                            <button id="dropdown-btn" type="button" data-ui-toggle="dropdown" aria-expanded="false" disabled
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 transition-colors shadow-2xs">
                                <i class="bi bi-check2-square"></i>
                                <span>Aksi Terpilih</span>
                                <i class="bi bi-chevron-down text-[10px] ml-1"></i>
                            </button>
                            <div id="dropdown-action" class="dropdown-menu border-0 rounded-2xl shadow-xl py-2 bg-white text-xs z-50">
                                <a class="dropdown-item px-4 py-2 hover:bg-amber-50 text-amber-700 font-medium flex items-center gap-2" id="pindah" href="#">
                                    <i class="bi bi-box-arrow-right"></i> Set sebagai PINDAH
                                </a>
                                <a class="dropdown-item px-4 py-2 hover:bg-slate-100 text-slate-700 font-medium flex items-center gap-2" id="keluar" href="#">
                                    <i class="bi bi-door-closed"></i> Set sebagai KELUAR
                                </a>
                                <div class="dropdown-divider my-1 border-slate-100"></div>
                                <a class="dropdown-item px-4 py-2 hover:bg-rose-50 text-rose-600 font-medium flex items-center gap-2" id="hapus" href="#">
                                    <i class="bi bi-trash3"></i> Hapus Terpilih
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="px-5 md:px-6 py-5">
            <form id="bulk">
                <div class="relative overflow-x-auto rounded-xl">
                    <table id="table-siswa" class="table-tw min-w-[880px]" aria-describedby="total-entri">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200/80">
                                <?php if ($isAdmin): ?>
                                    <th class="py-3 px-3 text-center w-10">
                                        <input class="select_all rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                            type="checkbox" aria-label="Pilih semua siswa pada halaman ini">
                                    </th>
                                <?php endif; ?>
                                <th class="py-3 px-3 text-center w-12 font-bold">No</th>
                                <th class="py-3 px-4 font-bold">Siswa</th>
                                <th class="py-3 px-4 font-bold">NIS &amp; NISN</th>
                                <th class="py-3 px-4 font-bold">Kelas</th>
                                <th class="py-3 px-4 text-center font-bold">Status</th>
                                <?php if ($isAdmin): ?>
                                    <th class="py-3 px-4 text-center font-bold w-24">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="table-body" class="divide-y divide-slate-100 text-slate-700">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <tr class="animate-pulse">
                                    <?php if ($isAdmin): ?>
                                        <td class="py-4 px-3"><div class="h-4 w-4 bg-slate-100 rounded mx-auto"></div></td>
                                    <?php endif; ?>
                                    <td class="py-4 px-3"><div class="h-3 w-4 bg-slate-100 rounded mx-auto"></div></td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 shrink-0"></div>
                                            <div class="space-y-2">
                                                <div class="h-3 w-36 bg-slate-100 rounded"></div>
                                                <div class="h-2.5 w-24 bg-slate-100 rounded"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="h-3 w-20 bg-slate-100 rounded"></div>
                                        <div class="h-2.5 w-24 bg-slate-100 rounded mt-2"></div>
                                    </td>
                                    <td class="py-4 px-4"><div class="h-5 w-16 bg-slate-100 rounded-md"></div></td>
                                    <td class="py-4 px-4"><div class="h-5 w-16 bg-slate-100 rounded-full mx-auto"></div></td>
                                    <?php if ($isAdmin): ?>
                                        <td class="py-4 px-4"><div class="h-8 w-8 bg-slate-100 rounded-lg mx-auto"></div></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>

                    <div id="loading" class="hidden absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center rounded-xl z-20">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 shadow-lg text-slate-600 text-xs font-semibold">
                            <span class="inline-block w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                            <span>Memuat data siswa...</span>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs font-medium text-slate-500" id="total-entri" aria-live="polite">
                    Menampilkan 0 entri
                </div>
                <nav aria-label="Navigasi halaman siswa" class="min-w-0 overflow-x-auto">
                    <ul class="pagination flex items-center gap-1 mb-0 hidden" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Modal Tambah Siswa -->
    <div id="createSiswaModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-3 sm:p-4" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-box-tw w-full max-w-2xl">
            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl max-h-[90vh] flex flex-col">
                <div class="px-5 sm:px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shrink-0">
                            <i class="bi bi-person-plus"></i>
                        </span>
                        <div class="min-w-0">
                            <h5 class="font-bold text-slate-800 text-base mb-0.5" id="createModalLabel">Tambah Siswa Baru</h5>
                            <p class="text-[11px] text-slate-500 mb-0">Data akun login dibuat otomatis setelah siswa disimpan.</p>
                        </div>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors shrink-0"
                        data-ui-dismiss="modal" aria-label="Tutup">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <form id="formsiswa" class="flex flex-col min-h-0">
                    <div class="p-5 sm:p-6 space-y-5 overflow-y-auto">
                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Identitas Siswa</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label for="nama_siswa" class="<?= $labelClass ?>">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <input id="nama_siswa" type="text" class="<?= $inputClass ?>" name="nama_siswa"
                                        placeholder="Contoh: Maria Goreti Lopes" autocomplete="off" maxlength="100" required>
                                </div>
                                <div>
                                    <label for="nis" class="<?= $labelClass ?>">NIS <span class="text-rose-500">*</span></label>
                                    <input id="nis" type="number" inputmode="numeric" class="<?= $inputClass ?>" name="nis"
                                        placeholder="Nomor Induk Siswa" autocomplete="off" minlength="4" maxlength="20" required>
                                </div>
                                <div>
                                    <label for="nisn" class="<?= $labelClass ?>">NISN <span class="text-rose-500">*</span></label>
                                    <input id="nisn" type="number" inputmode="numeric" class="<?= $inputClass ?>" name="nisn"
                                        placeholder="NISN Nasional" autocomplete="off" minlength="9" maxlength="20" required>
                                </div>
                                <div>
                                    <label for="jenis_kelamin" class="<?= $labelClass ?>">Jenis Kelamin</label>
                                    <select id="jenis_kelamin" name="jenis_kelamin" class="<?= $inputClass ?>">
                                        <option value="">Belum dipilih</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="agama" class="<?= $labelClass ?>">Agama</label>
                                    <select id="agama" name="agama" class="<?= $inputClass ?>">
                                        <option value="">Belum dipilih</option>
                                        <?php foreach (["Islam", "Katolik", "Protestan", "Hindu", "Budha", "Konghucu"] as $agama): ?>
                                            <option value="<?= $agama ?>"><?= $agama ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Data Akademik</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="kelas_awal" class="<?= $labelClass ?>">Kelas Awal</label>
                                    <select id="kelas_awal" name="kelas_awal" class="<?= $inputClass ?>">
                                        <option value="">Belum dipilih</option>
                                        <?php
                                        $opsis = [];
                                        if (($setting->jenjang ?? 0) == 1) {
                                            $opsis = [1, 2, 3, 4, 5, 6];
                                        } elseif (($setting->jenjang ?? 0) == 2) {
                                            $opsis = [7, 8, 9];
                                        } else {
                                            $opsis = [10, 11, 12];
                                        }
                                        foreach ($opsis as $kls): ?>
                                            <option value="<?= $kls ?>"><?= $kls ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="tahunmasuk" class="<?= $labelClass ?>">Tanggal Diterima <span class="text-rose-500">*</span></label>
                                    <input type="date" name="tahun_masuk" id="tahunmasuk" class="<?= $inputClass ?>" required>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Akun Login</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="username" class="<?= $labelClass ?>">Username <span class="text-rose-500">*</span></label>
                                    <input id="username" type="text" class="<?= $inputClass ?>" name="username"
                                        placeholder="Username untuk login" autocomplete="off" maxlength="30" required>
                                    <p class="text-[10px] text-slate-400 mt-1 mb-0">Hanya huruf, angka, titik, garis bawah, atau strip.</p>
                                </div>
                                <div>
                                    <label for="password" class="<?= $labelClass ?>">Password <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input id="password" type="password" class="<?= $inputClass ?> pr-10" name="password"
                                            placeholder="Minimal 6 karakter" autocomplete="new-password" minlength="6" maxlength="72" required>
                                        <button type="button" id="togglePassword"
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600"
                                            aria-label="Tampilkan password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1 mb-0">Simpan password ini untuk diberikan kepada siswa.</p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2 shrink-0">
                        <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                        <button type="reset" class="px-4 py-2 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition-colors">Reset</button>
                        <button type="submit" id="btn-save-siswa"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors shadow-xs shadow-blue-500/20">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/jquery.twbsPagination.js') ?>"></script>
<script>
    $(function () {
        const IS_ADMIN    = <?= $isAdmin ? 'true' : 'false' ?>;
        const HAS_PERIODE = <?= $hasPeriode ? 'true' : 'false' ?>;
        const URL_LIST    = '<?= site_url('datasiswa/list') ?>';
        const URL_DELETE  = '<?= site_url('datasiswa/delete') ?>';
        const URL_CREATE  = '<?= site_url('datasiswa/create') ?>';
        const URL_ADD     = '<?= site_url('datasiswa/add') ?>';
        const URL_EDIT    = '<?= site_url('datasiswa/edit/') ?>';
        const DEFAULT_FOTO = '<?= base_url('assets/img/siswa_sm.png') ?>';

        let currentPage = 1;
        let perPage = 10;
        let query = null;
        let statusFilter = '0';
        let kelasFilter = '';
        let searchTimer = null;
        let requestSeq = 0;

        const $pagination = $('#pagination');
        const $tbody = $('#table-body');
        const $loading = $('#loading');
        const $resetFilter = $('#btn-reset-filter');

        const paginationOpts = {
            visiblePages: 5,
            initiateStartPageClick: false,
            first: '<i class="bi bi-chevron-double-left"></i>',
            prev: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>',
            last: '<i class="bi bi-chevron-double-right"></i>',
            onPageClick: function (event, page) {
                currentPage = page;
                loadSiswa();
            }
        };

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function hasActiveFilter() {
            return (query !== null && query !== '') || statusFilter !== '0' || kelasFilter !== '';
        }

        function updateFilterUi() {
            $resetFilter.toggleClass('hidden', !hasActiveFilter());
            $('#btn-clear').toggleClass('hidden', !(query !== null && query !== ''));
            $('#users-status').toggleClass('border-blue-300 bg-blue-50/40 text-blue-700', statusFilter !== '0');
            $('#users-kelas').toggleClass('border-blue-300 bg-blue-50/40 text-blue-700', kelasFilter !== '');
        }

        function setStats(stats) {
            const values = {
                'stat-total': stats.total,
                'stat-aktif': stats.aktif,
                'stat-tanpa-kelas': HAS_PERIODE ? stats.tanpa_kelas : null,
                'stat-akun-nonaktif': stats.akun_nonaktif
            };
            Object.keys(values).forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                const value = values[id];
                el.textContent = value === null || value === undefined ? '—' : formatNumber(value);
            });
        }

        function updateBulkState() {
            const total = $('.check').length;
            const checked = $('.check:checked').length;

            $('.select_all')
                .prop('checked', total > 0 && checked === total)
                .prop('indeterminate', checked > 0 && checked < total);

            $('#dropdown-btn').prop('disabled', checked === 0);
            $('#selection-count').text(checked);
            $('#selection-bar').toggleClass('hidden', checked === 0);

            if (checked === 0) {
                $('#dropdown-action').removeClass('show');
                $('#dropdown-btn').attr('aria-expanded', 'false');
            }
        }

        function renderEmpty(title, detail, showReset) {
            const cols = IS_ADMIN ? 7 : 5;
            const action = showReset
                ? '<button type="button" class="js-reset-filter inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-100 transition-colors"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</button>'
                : (IS_ADMIN
                    ? '<a href="' + URL_ADD + '" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 shadow-xs shadow-blue-500/20"><i class="bi bi-plus-lg"></i> Tambah / Import Siswa</a>'
                    : '');

            $tbody.html(
                '<tr><td colspan="' + cols + '" class="px-4 py-14 text-center">' +
                    '<div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center text-2xl">' +
                        '<i class="bi ' + (showReset ? 'bi-search' : 'bi-inbox') + '"></i>' +
                    '</div>' +
                    '<p class="text-sm font-bold text-slate-700 mb-1">' + escapeHtml(title) + '</p>' +
                    '<p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">' + escapeHtml(detail) + '</p>' +
                    action +
                '</td></tr>'
            );

            $('#total-entri').text(title);
        }

        function renderPagination(totalPages) {
            if ($pagination.data('twbs-pagination')) {
                $pagination.twbsPagination('destroy');
            }

            if (totalPages > 0) {
                $pagination.twbsPagination($.extend({}, paginationOpts, {
                    startPage: currentPage,
                    totalPages: totalPages
                }));
                $pagination.removeClass('hidden');
            } else {
                $pagination.addClass('hidden');
            }
        }

        function previewData(data) {
            const lists = Array.isArray(data.lists) ? data.lists : [];

            if (!lists.length) {
                const filtered = hasActiveFilter();
                renderEmpty(
                    filtered ? 'Tidak ada siswa yang cocok' : 'Belum ada data siswa',
                    filtered
                        ? 'Ubah kata kunci, status, atau kelas lalu coba lagi.'
                        : (IS_ADMIN ? 'Tambahkan siswa baru atau import dari file Excel untuk mulai mengelola data.' : 'Data siswa belum tersedia.'),
                    filtered
                );
                return;
            }

            const statusMap = {
                '1': { label: 'Aktif', cls: 'bg-emerald-50 text-emerald-700 border-emerald-100', dot: 'bg-emerald-500' },
                '2': { label: 'Lulus', cls: 'bg-blue-50 text-blue-700 border-blue-100', dot: 'bg-blue-500' },
                '3': { label: 'Pindah', cls: 'bg-amber-50 text-amber-700 border-amber-100', dot: 'bg-amber-500' },
                '4': { label: 'Keluar', cls: 'bg-rose-50 text-rose-700 border-rose-100', dot: 'bg-rose-500' }
            };

            const startNo = perPage * (currentPage - 1);

            const rows = lists.map(function (siswa, idx) {
                const no = startNo + idx + 1;
                const idSiswa = parseInt(siswa.id_siswa, 10) || 0;
                const nama = escapeHtml(siswa.nama || 'Tanpa nama');
                const foto = escapeHtml(siswa.foto || DEFAULT_FOTO);
                const nis = escapeHtml(siswa.nis || '-');
                const nisn = escapeHtml(siswa.nisn || '-');
                const username = escapeHtml(siswa.username || '');

                const jkRaw = String(siswa.jenis_kelamin || '').toUpperCase();
                const jk = jkRaw === 'L' ? 'Laki-laki' : (jkRaw === 'P' ? 'Perempuan' : '');
                const agama = siswa.agama ? '<span>' + escapeHtml(siswa.agama) + '</span>' : '';
                const metaParts = [];
                if (jk) metaParts.push('<span>' + jk + '</span>');
                if (agama) metaParts.push(agama);
                if (username) metaParts.push('<span class="font-mono">@' + username + '</span>');
                const meta = metaParts.join('<span class="text-slate-300">&bull;</span>');

                const kelas = siswa.nama_kelas
                    ? '<span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100">' + escapeHtml(siswa.nama_kelas) + '</span>'
                    : '<span class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100"><i class="bi bi-exclamation-circle mr-1"></i>Belum ada kelas</span>';

                const statusKey = String(siswa.status ?? '');
                const st = statusMap[statusKey] || {
                    label: 'Belum terdaftar',
                    cls: 'bg-slate-50 text-slate-500 border-slate-200',
                    dot: 'bg-slate-400'
                };

                let akunChip = '';
                if (String(siswa.active) === '0') {
                    akunChip = '<div class="mt-1"><span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-rose-50 text-rose-600 border border-rose-100 text-[10px] font-semibold"><i class="bi bi-shield-slash"></i>Login nonaktif</span></div>';
                } else if (siswa.active === null || siswa.active === undefined) {
                    akunChip = '<div class="mt-1"><span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-50 text-slate-500 border border-slate-200 text-[10px] font-semibold"><i class="bi bi-person-x"></i>Tanpa akun</span></div>';
                }

                const checkbox = IS_ADMIN
                    ? '<td class="text-center align-middle py-3 px-3">' +
                        '<input name="checked[]" class="check rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" value="' + idSiswa + '" type="checkbox" aria-label="Pilih ' + nama + '">' +
                      '</td>'
                    : '';

                const aksi = IS_ADMIN
                    ? '<td class="text-center align-middle py-3 px-4">' +
                        '<a class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-colors shadow-xs" href="' + URL_EDIT + idSiswa + '" title="Edit data siswa" aria-label="Edit data ' + nama + '">' +
                            '<i class="bi bi-pencil-square text-xs"></i>' +
                        '</a>' +
                      '</td>'
                    : '';

                return '<tr class="hover:bg-slate-50/70 transition-colors">' +
                    checkbox +
                    '<td class="text-center align-middle font-bold text-slate-400 py-3 px-3">' + no + '</td>' +
                    '<td class="align-middle py-3 px-4">' +
                        '<div class="flex items-center gap-3">' +
                            '<img src="' + foto + '" alt="Foto ' + nama + '" loading="lazy" ' +
                                'class="w-10 h-10 rounded-xl object-cover border border-slate-200 bg-slate-50 shadow-xs shrink-0" ' +
                                'onerror="this.onerror=null;this.src=\'' + DEFAULT_FOTO.replace(/'/g, "\\'") + '\'">' +
                            '<div class="min-w-0">' +
                                '<div class="font-bold text-slate-800 text-sm truncate max-w-[240px]" title="' + nama + '">' + nama + '</div>' +
                                (meta ? '<div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-slate-500">' + meta + '</div>' : '') +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="align-middle py-3 px-4">' +
                        '<div class="text-[11px] text-slate-400 font-medium">NIS <span class="font-bold text-slate-700 font-mono">' + nis + '</span></div>' +
                        '<div class="text-[11px] text-slate-400 font-medium mt-0.5">NISN <span class="font-bold text-slate-700 font-mono">' + nisn + '</span></div>' +
                    '</td>' +
                    '<td class="align-middle py-3 px-4">' + kelas + '</td>' +
                    '<td class="text-center align-middle py-3 px-4">' +
                        '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wide whitespace-nowrap ' + st.cls + '">' +
                            '<span class="w-1.5 h-1.5 rounded-full ' + st.dot + '"></span>' + st.label +
                        '</span>' +
                        akunChip +
                    '</td>' +
                    aksi +
                '</tr>';
            }).join('');

            $tbody.html(rows);

            const from = startNo + 1;
            const to = startNo + lists.length;
            $('#total-entri').text('Menampilkan ' + from + '–' + to + ' dari ' + formatNumber(data.total) + ' siswa');

            updateBulkState();
        }

        function loadSiswa() {
            if (!HAS_PERIODE) {
                renderEmpty('Periode aktif belum diset', 'Atur Tahun Pelajaran dan Semester aktif terlebih dahulu agar daftar siswa dapat dimuat.', false);
                $pagination.addClass('hidden');
                return;
            }

            const seq = ++requestSeq;
            $loading.removeClass('hidden');
            $('#table-siswa').attr('aria-busy', 'true');

            const payload = {
                page: currentPage,
                limit: perPage,
                search: query || '',
                status: statusFilter,
                kelas: kelasFilter
            };
            payload[CSRF_NAME] = CSRF_HASH;

            $.ajax({
                url: URL_LIST,
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (data) {
                    if (seq !== requestSeq) return;

                    if (!data || data.status === false) {
                        renderEmpty('Gagal memuat data', (data && data.message) || 'Silakan coba lagi.', true);
                        $pagination.addClass('hidden');
                        return;
                    }

                    if (data.message) {
                        renderEmpty('Data belum dapat ditampilkan', data.message, false);
                        $pagination.addClass('hidden');
                        return;
                    }

                    if (data.stats) setStats(data.stats);

                    const totalPages = Number(data.pages) || 0;
                    if (totalPages > 0 && currentPage > totalPages) {
                        currentPage = totalPages;
                        loadSiswa();
                        return;
                    }

                    renderPagination(totalPages);
                    previewData(data);
                },
                error: function (xhr) {
                    if (seq !== requestSeq) return;

                    let message = 'Gagal memuat data siswa. Periksa koneksi lalu coba lagi.';
                    if (xhr.status === 401) message = 'Sesi Anda berakhir. Silakan login kembali.';
                    else if (xhr.status === 403) message = 'Anda tidak memiliki akses ke daftar siswa.';

                    renderEmpty('Terjadi kesalahan', message, true);
                    $pagination.addClass('hidden');
                },
                complete: function () {
                    if (seq !== requestSeq) return;
                    $loading.addClass('hidden');
                    $('#table-siswa').attr('aria-busy', 'false');
                }
            });
        }

        function resetFilters() {
            query = null;
            statusFilter = '0';
            kelasFilter = '';
            currentPage = 1;
            $('#input-search').val('');
            $('#users-status').val('0');
            $('#users-kelas').val('');
            updateFilterUi();
            loadSiswa();
        }

        function submitBulk(action) {
            const checked = $('.check:checked').length;
            if (checked === 0) return;

            $('#dropdown-action').removeClass('show');
            $('#dropdown-btn').attr('aria-expanded', 'false');

            Swal.fire({
                title: 'Memproses...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () { Swal.showLoading(); }
            });

            const payload = $('#bulk').serialize() + '&aksi=' + encodeURIComponent(action) + '&' + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(CSRF_HASH);

            $.ajax({
                url: URL_DELETE,
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (response) {
                    if (response && response.status) {
                        $('.select_all').prop('checked', false).prop('indeterminate', false);
                        updateBulkState();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadSiswa();
                    } else {
                        Swal.fire('Gagal', (response && response.message) || 'Data gagal diproses.', 'error');
                    }
                },
                error: function (xhr) {
                    const message = xhr.status === 403
                        ? 'Anda tidak memiliki akses untuk mengubah data siswa.'
                        : 'Terjadi kesalahan pada server. Coba lagi.';
                    Swal.fire('Gagal', message, 'error');
                },
                complete: function () {
                    updateBulkState();
                }
            });
        }

        // Filter & pencarian
        $('#users_length').on('change', function () {
            perPage = parseInt(this.value, 10) || 10;
            currentPage = 1;
            loadSiswa();
        });

        $('#users-status').on('change', function () {
            statusFilter = this.value;
            currentPage = 1;
            updateFilterUi();
            loadSiswa();
        });

        $('#users-kelas').on('change', function () {
            kelasFilter = this.value;
            currentPage = 1;
            updateFilterUi();
            loadSiswa();
        });

        // Kartu statistik "Tanpa kelas" memfilter daftar langsung.
        $('#card-tanpa-kelas').on('click', function () {
            kelasFilter = 'tanpa';
            $('#users-kelas').val('tanpa');
            currentPage = 1;
            updateFilterUi();
            loadSiswa();
        });

        $('#input-search').on('input', function () {
            const value = $(this).val();
            query = value.trim() === '' ? null : value.trim();
            updateFilterUi();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                currentPage = 1;
                loadSiswa();
            }, 300);
        });

        $('#btn-clear').on('click', function () {
            query = null;
            $('#input-search').val('').trigger('focus');
            currentPage = 1;
            updateFilterUi();
            loadSiswa();
        });

        $resetFilter.on('click', resetFilters);

        $(document).on('click', '.js-reset-filter', resetFilters);

        // Seleksi & aksi massal
        $(document).on('change', '.select_all', function () {
            $('.check').prop('checked', this.checked);
            updateBulkState();
        });

        $(document).on('change', '.check', updateBulkState);

        $('#dropdown-action a').on('click', function (e) {
            e.preventDefault();
            const action = this.id;
            const checked = $('.check:checked').length;
            if (checked === 0) return;

            const config = {
                hapus: {
                    title: 'Hapus siswa terpilih?',
                    html: '<b>' + checked + ' siswa</b> akan dihapus permanen dari data siswa, kelas, dan buku induk. Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    confirm: 'Ya, Hapus',
                    color: '#e11d48'
                },
                pindah: {
                    title: 'Tandai sebagai PINDAH?',
                    html: 'Status <b>' + checked + ' siswa</b> terpilih akan diubah menjadi <b>Pindah</b>.',
                    icon: 'question',
                    confirm: 'Ya, Lanjutkan',
                    color: '#d97706'
                },
                keluar: {
                    title: 'Tandai sebagai KELUAR?',
                    html: 'Status <b>' + checked + ' siswa</b> terpilih akan diubah menjadi <b>Keluar</b>.',
                    icon: 'question',
                    confirm: 'Ya, Lanjutkan',
                    color: '#475569'
                }
            }[action];

            if (!config) return;

            Swal.fire({
                title: config.title,
                html: config.html,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: config.color,
                cancelButtonColor: '#64748b',
                confirmButtonText: config.confirm,
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) submitBulk(action);
            });
        });

        // Form tambah siswa
        $('#formsiswa').on('submit', function (e) {
            e.preventDefault();

            const $btn = $('#btn-save-siswa');
            const original = $btn.html();

            $btn.prop('disabled', true).html(
                '<span class="inline-block w-3.5 h-3.5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></span> Menyimpan...'
            );

            $.ajax({
                url: URL_CREATE,
                type: 'POST',
                dataType: 'json',
                data: $(this).serialize() + '&' + CSRF_NAME + '=' + CSRF_HASH,
                success: function (response) {
                    if (response && response.insert) {
                        AdminUI.modal.close(document.getElementById('createSiswaModal'));
                        $('#formsiswa')[0].reset();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.text || 'Siswa berhasil ditambahkan.',
                            timer: 1600,
                            showConfirmButton: false
                        });
                        currentPage = 1;
                        loadSiswa();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal menyimpan',
                            html: (response && response.text) || 'Periksa kembali data siswa.'
                        });
                    }
                },
                error: function (xhr) {
                    const message = xhr.status === 403
                        ? 'Anda tidak memiliki akses untuk menambah siswa.'
                        : 'Terjadi kesalahan pada server. Coba lagi.';
                    Swal.fire('Gagal', message, 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).html(original);
                }
            });
        });

        $('#formsiswa').on('reset', function () {
            setTimeout(function () {
                $('#password').attr('type', 'password');
                $('#togglePassword i').removeClass('bi-eye-slash').addClass('bi-eye');
                $('#togglePassword').attr('aria-label', 'Tampilkan password');
            }, 0);
        });

        $('#togglePassword').on('click', function () {
            const $input = $('#password');
            const show = $input.attr('type') === 'password';
            $input.attr('type', show ? 'text' : 'password');
            $(this).find('i').toggleClass('bi-eye', !show).toggleClass('bi-eye-slash', show);
            $(this).attr('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        });

        $('#createSiswaModal').on('app:modal-shown', function () {
            setTimeout(function () { $('#nama_siswa').trigger('focus'); }, 60);
        });

        // Muat data awal
        updateFilterUi();
        if (HAS_PERIODE) {
            loadSiswa();
        } else {
            renderEmpty('Periode aktif belum diset', 'Atur Tahun Pelajaran dan Semester aktif terlebih dahulu agar daftar siswa dapat dimuat.', false);
        }
    });
</script>
<?= $this->endSection() ?>
