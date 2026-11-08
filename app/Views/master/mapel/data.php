<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$kelompok_mapel = $kelompok_mapel ?? [];
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="bi bi-book text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Mata Pelajaran') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="text-slate-400">Data Umum</span>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($judul ?? 'Mata Pelajaran') ?></span>
        </nav>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-area mt-4 space-y-6">

    <!-- 2-Column Grid: Kelompok Utama & Sub Kelompok -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card Kelompok Utama -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                        <i class="bi bi-folder2-open"></i>
                    </span>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm mb-0">Kelompok Utama</h4>
                        <p class="text-xs text-slate-400 mb-0">Kategori induk mapel</p>
                    </div>
                </div>
                <button type="button" data-ui-toggle="modal" data-ui-target="#editKelompokModal"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20"
                    onclick="resetKelompokForm()">
                    <i class="bi bi-plus-lg"></i> Tambah
                </button>
            </div>
            <div class="p-4 grow">
                <div class="table-responsive">
                    <table id="tableKelompok" class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200/80">
                                <th class="py-2.5 px-3 text-center font-bold">Kategori</th>
                                <th class="py-2.5 px-3 text-center font-bold">Kode</th>
                                <th class="py-2.5 px-3 font-bold">Nama</th>
                                <th class="py-2.5 px-3 text-center font-bold w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <!-- Filled by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card Sub Kelompok -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
                        <i class="bi bi-diagram-2"></i>
                    </span>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm mb-0">Sub Kelompok</h4>
                        <p class="text-xs text-slate-400 mb-0">Cabang peminatan / kelompok khusus</p>
                    </div>
                </div>
                <button type="button" data-ui-toggle="modal" data-ui-target="#editSubKelompokModal"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-500/20"
                    onclick="resetSubKelompokForm()">
                    <i class="bi bi-plus-lg"></i> Tambah
                </button>
            </div>
            <div class="p-4 grow">
                <div class="table-responsive">
                    <table id="tableSubKelompok" class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200/80">
                                <th class="py-2.5 px-3 text-center font-bold">Kode</th>
                                <th class="py-2.5 px-3 font-bold">Nama Sub Kelompok</th>
                                <th class="py-2.5 px-3 text-center font-bold">Kategori</th>
                                <th class="py-2.5 px-3 text-center font-bold w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <!-- Filled by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card: Daftar Mata Pelajaran -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                    <i class="bi bi-journal-text text-blue-600"></i>
                    Daftar Mata Pelajaran
                </h4>
                <p class="text-xs text-slate-500 mb-0">Kelola mata pelajaran, status keaktifan, dan nomor urut tampil di raport</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="reload_ajax()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
                <button id="hapusterpilih" onclick="bulk_delete()" type="button"
                    class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors shadow-xs shadow-rose-500/20">
                    <i class="bi bi-trash3"></i> Hapus Terpilih
                </button>
                <button type="button" data-ui-toggle="modal" data-ui-target="#createMapelModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                    <i class="bi bi-plus-lg"></i> Tambah Mapel
                </button>
            </div>
        </div>

        <div class="p-6">
            <form id="bulk">
                <?= csrf_field() ?>
                <div class="table-responsive">
                    <table id="tableMapel" class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-200/80">
                                <th class="py-3 px-3 text-center w-10">
                                    <input type="checkbox" class="select_all rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </th>
                                <th class="py-3 px-3 text-center font-bold w-16">Urut</th>
                                <th class="py-3 px-4 font-bold">Mata Pelajaran</th>
                                <th class="py-3 px-3 text-center font-bold w-24">Kode</th>
                                <th class="py-3 px-3 text-center font-bold">Kelompok</th>
                                <th class="py-3 px-3 text-center font-bold w-28">Status</th>
                                <th class="py-3 px-3 text-center font-bold w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <!-- Filled by DataTables -->
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Kelompok Utama -->
<div id="editKelompokModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="modalKelompokTitle" aria-hidden="true">
    <div class="modal-box-tw max-w-md">
        <div class="bg-white rounded-3xl overflow-hidden">
            <form id="create-kelompok">
                <?= csrf_field() ?>
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between">
                    <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="modalKelompokTitle">
                        <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm">
                            <i class="bi bi-folder2-open"></i>
                        </span>
                        <span>Kelompok Utama</span>
                    </h5>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" id="id_kel_mapel" name="id_kel_mapel">
                    <input type="hidden" name="id_parent" value="0">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kode Kelompok <span class="text-rose-500">*</span></label>
                        <input type="text" id="createkodekel" name="kode_kel_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" placeholder="Contoh: A, B, C" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Kelompok <span class="text-rose-500">*</span></label>
                        <input type="text" id="createnamakel" name="nama_kel_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" placeholder="Contoh: Muatan Nasional" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Standar <span class="text-rose-500">*</span></label>
                        <select id="kategori" name="kategori" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                            <?php foreach ($kategori as $kat): ?>
                                <option value="<?= $kat ?>"><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sub Kelompok -->
<div id="editSubKelompokModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="modalSubKelompokTitle" aria-hidden="true">
    <div class="modal-box-tw max-w-md">
        <div class="bg-white rounded-3xl overflow-hidden">
            <form id="create-sub-kelompok">
                <?= csrf_field() ?>
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-blue-50/30 flex items-center justify-between">
                    <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="modalSubKelompokTitle">
                        <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm">
                            <i class="bi bi-diagram-2"></i>
                        </span>
                        <span>Sub Kelompok Mapel</span>
                    </h5>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" id="id_kel_sub" name="id_kel_mapel">
                    <input type="hidden" id="kategori_sub" name="kategori">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kode Sub Kelompok <span class="text-rose-500">*</span></label>
                        <input type="text" id="createkodesub" name="kode_kel_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" placeholder="Contoh: C1, C2, C3" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Sub Kelompok <span class="text-rose-500">*</span></label>
                        <input type="text" id="createnamasub" name="nama_kel_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" placeholder="Contoh: Dasar Bidang Keahlian" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kelompok Utama (Induk) <span class="text-rose-500">*</span></label>
                        <select id="id_parent_sub" name="id_parent" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                            <option value="">-- Pilih Kelompok Utama --</option>
                            <?php foreach ($kelompok_mapel as $km):
                                if ($km->id_parent == 0): ?>
                                    <option value="<?= $km->id_kel_mapel ?>"><?= esc($km->nama_kel_mapel) ?> (<?= esc($km->kode_kel_mapel) ?>)</option>
                                <?php endif; endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-500/20">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Mapel -->
<div id="createMapelModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-box-tw max-w-lg">
        <div class="bg-white rounded-3xl overflow-hidden">
            <form id="form-create-mapel">
                <?= csrf_field() ?>
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between">
                    <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="createModalLabel">
                        <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm">
                            <i class="bi bi-plus-lg"></i>
                        </span>
                        <span>Tambah Mata Pelajaran</span>
                    </h5>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                        <input type="text" id="createnama" name="nama_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required placeholder="Contoh: Matematika">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kode Mapel <span class="text-rose-500">*</span></label>
                            <input type="text" id="createkode" name="kode_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required placeholder="Contoh: MTK">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">No. Urut Rapor</label>
                            <input type="number" name="urutan_tampil" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" value="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kelompok Mapel <span class="text-rose-500">*</span></label>
                        <?= form_dropdown('kelompok', $kelompok, '', 'class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required') ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status Keaktifan <span class="text-rose-500">*</span></label>
                        <?= form_dropdown('status', $status, '1', 'class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required') ?>
                    </div>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Mapel -->
<div id="editMapelModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-box-tw max-w-lg">
        <div class="bg-white rounded-3xl overflow-hidden">
            <form id="form-update-mapel">
                <?= csrf_field() ?>
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-amber-50 to-orange-50/30 flex items-center justify-between">
                    <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="editModalLabel">
                        <span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-sm">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <span>Edit Mata Pelajaran</span>
                    </h5>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" id="editIdMapel" name="id_mapel">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                        <input type="text" id="namaEdit" name="nama_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kode Mapel <span class="text-rose-500">*</span></label>
                            <input type="text" id="kodeEdit" name="kode_mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">No. Urut Rapor</label>
                            <input type="number" id="kodeUrut" name="urutan_tampil" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kelompok Mapel <span class="text-rose-500">*</span></label>
                        <?= form_dropdown('kelompok', $kelompok, '', 'id="kelompokEdit" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required') ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Status Keaktifan <span class="text-rose-500">*</span></label>
                        <?= form_dropdown('status', $status, '1', 'id="statusEdit" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required') ?>
                    </div>
                </div>
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-amber-500 rounded-xl hover:bg-amber-600 transition-colors shadow-xs shadow-amber-500/20">
                        <i class="bi bi-save"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    var jsonkelompokMapel = <?= json_encode($kelompok_mapel) ?>;
    var table, tableKelompok, tableSubKelompok;

    function resetKelompokForm() {
        $('#create-kelompok')[0].reset();
        $('#id_kel_mapel').val('');
        $('#modalKelompokTitle span:last').text('Tambah Kelompok Utama');
    }

    function resetSubKelompokForm() {
        $('#create-sub-kelompok')[0].reset();
        $('#id_kel_sub').val('');
        $('#id_parent_sub').val('');
        $('#modalSubKelompokTitle span:last').text('Tambah Sub Kelompok');
    }

    $(document).ready(function () {
        // Table Kelompok Utama
        tableKelompok = $("#tableKelompok").DataTable({
            searching: false,
            paging: false,
            info: false,
            ajax: {
                url: "<?= base_url('datamapel/getdatakelompok') ?>",
                type: "POST",
                data: { [CSRF_NAME]: CSRF_HASH }
            },
            columns: [
                {
                    data: "kategori",
                    className: "text-center",
                    render: function (data) {
                        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: "kode_kel_mapel",
                    className: "text-center font-bold text-blue-600"
                },
                {
                    data: "nama_kel_mapel",
                    render: function (data) {
                        return '<span class="font-semibold text-slate-800">' + data + '</span>';
                    }
                },
                {
                    data: null,
                    className: "text-center",
                    orderable: false,
                    render: function (data) {
                        return `<div class="inline-flex items-center gap-1">
                            <button type="button" class="btn-edit-kel inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors"
                                data-ui-toggle="modal" data-ui-target="#editKelompokModal"
                                data-id="${data.id_kel_mapel}" data-nama="${data.nama_kel_mapel}" data-kode="${data.kode_kel_mapel}" data-kategori="${data.kategori}" title="Edit">
                                <i class="bi bi-pencil-square text-xs"></i>
                            </button>
                            <button type="button" onclick="hapusKelompok('${data.id_kel_mapel}')" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="Hapus">
                                <i class="bi bi-trash3 text-xs"></i>
                            </button>
                        </div>`;
                    }
                }
            ],
            language: typeof dt_lang_id !== 'undefined' ? dt_lang_id : {}
        });

        // Table Sub Kelompok
        tableSubKelompok = $("#tableSubKelompok").DataTable({
            searching: false,
            paging: false,
            info: false,
            ajax: {
                url: "<?= base_url('datamapel/getdatasubkelompok') ?>",
                type: "POST",
                data: { [CSRF_NAME]: CSRF_HASH }
            },
            columns: [
                {
                    data: "kode_kel_mapel",
                    className: "text-center font-bold text-indigo-600"
                },
                {
                    data: "nama_kel_mapel",
                    render: function (data) {
                        return '<span class="font-semibold text-slate-800">' + data + '</span>';
                    }
                },
                {
                    data: "kategori",
                    className: "text-center",
                    render: function (data) {
                        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: null,
                    className: "text-center",
                    orderable: false,
                    render: function (data) {
                        return `<div class="inline-flex items-center gap-1">
                            <button type="button" class="btn-edit-sub inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors"
                                data-ui-toggle="modal" data-ui-target="#editSubKelompokModal"
                                data-id="${data.id_kel_mapel}" data-nama="${data.nama_kel_mapel}" data-kode="${data.kode_kel_mapel}" data-utama="${data.id_parent}" data-kategori="${data.kategori}" title="Edit">
                                <i class="bi bi-pencil-square text-xs"></i>
                            </button>
                            <button type="button" onclick="hapusKelompok('${data.id_kel_mapel}')" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="Hapus">
                                <i class="bi bi-trash3 text-xs"></i>
                            </button>
                        </div>`;
                    }
                }
            ],
            language: typeof dt_lang_id !== 'undefined' ? dt_lang_id : {}
        });

        // Table Mapel
        var groupColumn = 4;
        table = $("#tableMapel").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "<?= base_url('datamapel/read') ?>",
                type: "POST",
                data: { [CSRF_NAME]: CSRF_HASH }
            },
            columns: [
                { data: "id_mapel", className: "text-center", orderable: false, searchable: false },
                { data: "urutan_tampil", className: "text-center font-bold text-slate-400" },
                {
                    data: "nama_mapel",
                    render: function (data, type, row) {
                        return `<div class="font-bold text-slate-800">${data}</div>`;
                    }
                },
                {
                    data: "kode",
                    className: "text-center",
                    render: function (data) {
                        return `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">${data || '-'}</span>`;
                    }
                },
                { data: "kelompok", className: "text-center" },
                { data: "status", className: "text-center" },
                { data: null, className: "text-center", orderable: false }
            ],
            columnDefs: [
                {
                    targets: 0,
                    render: function (data, type, row) {
                        // Mapel bawaan (deletable = 0) memang ditolak server saat
                        // dihapus — jangan tawarkan checkbox yang selalu gagal.
                        if (String(row.deletable) === '0') {
                            return '<span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-400" title="Mapel bawaan sistem — tidak dapat dihapus"><i class="bi bi-lock-fill text-xs"></i></span>';
                        }
                        return `<input name="checked[]" class="check rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" value="${data}" type="checkbox">`;
                    }
                },
                {
                    targets: 5,
                    render: function (data) {
                        return data === '0'
                            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">Nonaktif</span>'
                            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Aktif</span>';
                    }
                },
                {
                    targets: 6,
                    render: function (data, type, row) {
                        return `<button type="button" class="btn-edit-mapel inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors shadow-xs"
                            data-id="${row.id_mapel}" data-nama="${row.nama_mapel}" data-kode="${row.kode}" data-kelompok="${row.kelompok}"
                            data-urutan="${row.urutan_tampil}" data-status="${row.status}" title="Edit Mapel">
                            <i class="bi bi-pencil-square text-sm"></i>
                        </button>`;
                    }
                }
            ],
            order: [[groupColumn, 'asc']],
            drawCallback: function (settings) {
                var api = this.api();
                var rows = api.rows({ page: 'current' }).nodes();
                var last = null;

                api.column(groupColumn, { page: 'current' }).data().each(function (group, i) {
                    if (last !== group) {
                        $(rows).eq(i).before(
                            '<tr class="group bg-slate-100/80 font-bold text-slate-700 text-xs tracking-wider"><td class="py-2 px-4 text-blue-800" colspan="7"><i class="bi bi-folder mr-1.5"></i> ' + group + '</td></tr>'
                        );
                        last = group;
                    }
                });
            },
            language: typeof dt_lang_id !== 'undefined' ? dt_lang_id : {}
        });

        // Checkbox Select All
        $('.select_all').on('click', function () {
            $('.check').prop('checked', this.checked);
            toggleBulkButton();
        });

        $('#tableMapel').on('change', '.check', function () {
            toggleBulkButton();
        });

        function toggleBulkButton() {
            var n = $('.check:checked').length;
            if (n > 0) {
                $('#hapusterpilih').removeClass('hidden');
            } else {
                $('#hapusterpilih').addClass('hidden');
            }
        }

        // Parent Sub change
        $('#id_parent_sub').on('change', function () {
            var val = $(this).val();
            var kel = jsonkelompokMapel[val];
            if (kel) $('#kategori_sub').val(kel.kategori);
        });

        // Edit Modal Data Passing: Kelompok Utama
        $(document).on('click', '.btn-edit-kel', function () {
            $('#id_kel_mapel').val($(this).data('id'));
            $('#createnamakel').val($(this).data('nama'));
            $('#createkodekel').val($(this).data('kode'));
            $('#kategori').val($(this).data('kategori'));
            $('#modalKelompokTitle span:last').text('Edit Kelompok Utama');
        });

        // Edit Modal Data Passing: Sub Kelompok
        $(document).on('click', '.btn-edit-sub', function () {
            $('#id_kel_sub').val($(this).data('id'));
            $('#createnamasub').val($(this).data('nama'));
            $('#createkodesub').val($(this).data('kode'));
            $('#id_parent_sub').val($(this).data('utama'));
            $('#kategori_sub').val($(this).data('kategori'));
            $('#modalSubKelompokTitle span:last').text('Edit Sub Kelompok');
        });

        // Edit Mapel Click Handler (Fixed)
        $(document).on('click', '.btn-edit-mapel', function () {
            var btn = $(this);
            $('#editIdMapel').val(btn.data('id'));
            $('#namaEdit').val(btn.data('nama'));
            $('#kodeEdit').val(btn.data('kode'));
            $('#kelompokEdit').val(btn.data('kelompok'));
            $('#statusEdit').val(btn.data('status'));
            $('#kodeUrut').val(btn.data('urutan'));

            var modalEl = document.getElementById('editMapelModal');
            AdminUI.modal.open(modalEl);
        });

        // Form Submissions: Kelompok
        $('#create-kelompok, #create-sub-kelompok').on('submit', function (e) {
            e.preventDefault();
            $.post("<?= base_url('datamapel/addkelompokmapel') ?>", $(this).serialize(), function (res) {
                if (res && res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Kelompok tersimpan',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                    return;
                }
                // Modal dibiarkan terbuka supaya isian bisa dikoreksi.
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (res && res.message) || 'Kelompok tidak tersimpan — periksa isian lalu coba lagi.'
                });
            }, 'json').fail(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            });
        });

        // Form Submissions: Create Mapel
        $('#form-create-mapel').on('submit', function (e) {
            e.preventDefault();
            $.post("<?= base_url('datamapel/create') ?>", $(this).serialize(), function (res) {
                if (res && res.status) {
                    AdminUI.modal.close('#createMapelModal');
                    $('#form-create-mapel')[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Mata pelajaran berhasil ditambahkan',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        table.ajax.reload();
                    });
                    return;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (res && res.message) || 'Mapel tidak tersimpan — periksa isian lalu coba lagi.'
                });
            }, 'json').fail(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            });
        });

        // Form Submissions: Update Mapel
        $('#form-update-mapel').on('submit', function (e) {
            e.preventDefault();
            $.post("<?= base_url('datamapel/update') ?>", $(this).serialize(), function (res) {
                if (res && res.status) {
                    AdminUI.modal.close('#editMapelModal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Mata pelajaran berhasil diperbarui',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        table.ajax.reload();
                    });
                    return;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (res && res.message) || 'Mapel tidak tersimpan — periksa isian lalu coba lagi.'
                });
            }, 'json').fail(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            });
        });
    });

    function reload_ajax() {
        table.ajax.reload();
        tableKelompok.ajax.reload();
        tableSubKelompok.ajax.reload();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Data berhasil dimuat ulang',
            showConfirmButton: false,
            timer: 1500
        });
    }

    function hapusKelompok(id) {
        Swal.fire({
            title: 'Hapus Kelompok?',
            text: "Data mapel dalam kelompok ini akan kehilangan referensi kelompoknya.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-trash mr-1"></i> Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?= base_url('datamapel/hapuskelompok') ?>", { id_kel: id, [CSRF_NAME]: CSRF_HASH }, function (res) {
                    if (res && res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message || 'Kelompok berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                        return;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: (res && res.message) || 'Kelompok tidak terhapus — data terkait harus dipindahkan dulu.'
                    });
                }, 'json').fail(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
                });
            }
        });
    }

    function bulk_delete() {
        Swal.fire({
            title: 'Hapus Terpilih?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-trash mr-1"></i> Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?= base_url('datamapel/delete') ?>", $('#bulk').serialize(), function (res) {
                    if (res && res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message || ((res.total || 0) + ' data berhasil dihapus'),
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            table.ajax.reload();
                            $('#hapusterpilih').addClass('hidden');
                            $('.select_all').prop('checked', false);
                        });
                        return;
                    }
                    // Tidak ada yang terhapus — jangan reload, biarkan pilihan utuh.
                    Swal.fire({
                        icon: 'error',
                        title: 'Tidak ada yang terhapus',
                        text: (res && res.message) || 'Pilih mapel yang boleh dihapus, atau bersihkan data terkait dulu.'
                    });
                }, 'json').fail(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>