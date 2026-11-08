<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$canManage = (bool) ($can_manage ?? false);
$namaGuru  = trim((string) ($guru->nama_guru ?? '')) ?: 'Guru tanpa nama';
$fotoPath  = !empty($guru->foto) && is_file(FCPATH . $guru->foto)
    ? base_url($guru->foto)
    : base_url('assets/img/guru.png');

$jenisKelamin = match (strtoupper((string) ($guru->jenis_kelamin ?? ''))) {
    'L' => 'Laki-laki',
    'P' => 'Perempuan',
    default => '',
};

$akunAktif = !empty($akun) && (string) ($akun->active ?? '') === '1';
$statusAkunText = $akun ? ($akunAktif ? 'Akun aktif' : 'Akun nonaktif') : 'Tanpa akun';
$lastLogin = '';
if (!empty($akun->last_login)) {
    $tsLogin   = (int) $akun->last_login;
    $lastLogin = $tsLogin > 0 ? date('d/m/Y H:i', $tsLogin) : '';
}

$formatTanggal = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00') {
        return '';
    }
    $ts = strtotime($value);

    return $ts ? date('d/m/Y', $ts) : $value;
};

$tglLahir = $formatTanggal($guru->tgl_lahir ?? '');
$tmt      = $formatTanggal($guru->tmt ?? '');

$periodeSet = [];
$mapelSet   = [];
$ekstraSet  = [];
$kelasSet   = [];

foreach ($jabatan as $row) {
    $periodeSet[$row->id_tp . '-' . $row->id_smt] = true;

    foreach ($row->mapel_kelas as $mapel) {
        $namaMapel = trim((string) ($mapel->nama_mapel ?? ''));
        $idMapel   = (int) ($mapel->id_mapel ?? 0);
        $keyMapel  = $idMapel > 0 ? (string) $idMapel : $namaMapel;
        if ($keyMapel !== '') {
            $mapelSet[$keyMapel] = $namaMapel !== '' ? $namaMapel : 'Mapel #' . $idMapel;
        }

        foreach (($mapel->kelas_mapel ?? []) as $kelas) {
            $idKelas = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
            if ($idKelas > 0) {
                $kelasSet[$idKelas] = true;
            }
        }
    }

    foreach ($row->ekstra_kelas as $ekstra) {
        $namaEkstra = trim((string) ($ekstra->nama_ekstra ?? ''));
        $idEkstra   = (int) ($ekstra->id_ekstra ?? 0);
        $keyEkstra  = $idEkstra > 0 ? (string) $idEkstra : $namaEkstra;
        if ($keyEkstra !== '') {
            $ekstraSet[$keyEkstra] = $namaEkstra !== '' ? $namaEkstra : 'Ekstra #' . $idEkstra;
        }

        foreach (($ekstra->kelas_ekstra ?? []) as $kelas) {
            $idKelas = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
            if ($idKelas > 0) {
                $kelasSet[$idKelas] = true;
            }
        }
    }

    if (!empty($row->id_kelas)) {
        $kelasSet[(int) $row->id_kelas] = true;
    }
}

$riwayat = [];
foreach ($jabatan as $row) {
    $riwayat[$row->id_tp][] = $row;
}

$mapelAktif  = $jabatan_aktif->mapel_kelas ?? [];
$ekstraAktif = $jabatan_aktif->ekstra_kelas ?? [];

$linkedLabels = [
    'jabatan'    => ['label' => 'Jabatan & penugasan', 'icon' => 'bi-briefcase', 'action' => 'dihapus'],
    'materi'     => ['label' => 'Materi siswa', 'icon' => 'bi-journal-bookmark', 'action' => 'dihapus'],
    'catatan'    => ['label' => 'Catatan mapel', 'icon' => 'bi-chat-left-text', 'action' => 'dihapus'],
    'jurnal'     => ['label' => 'Jurnal guru', 'icon' => 'bi-clipboard-check', 'action' => 'dihapus'],
    'post'       => ['label' => 'Postingan forum', 'icon' => 'bi-chat-square-text', 'action' => 'dihapus'],
    'komentar'   => ['label' => 'Komentar forum', 'icon' => 'bi-chat-dots', 'action' => 'dihapus'],
    'wali_kelas' => ['label' => 'Wali kelas', 'icon' => 'bi-people', 'action' => 'dilepas'],
];
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="bi bi-person-vcard text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Detail Guru') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <a href="<?= site_url('dataguru') ?>" class="no-underline text-slate-500 hover:text-blue-600">Data Guru</a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($namaGuru) ?></span>
        </nav>
    </div>
</div>

<div class="content-area mt-4 space-y-5">

    <!-- Profil ringkas -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <div class="h-24 bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 relative">
            <div class="absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 20% 120%, #fff 0, transparent 45%), radial-gradient(circle at 80% -20%, #fff 0, transparent 40%);"></div>
        </div>
        <div class="px-5 md:px-7 pb-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 -mt-12">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4 min-w-0">
                    <div class="relative shrink-0">
                        <img src="<?= esc($fotoPath, 'attr') ?>" alt="<?= esc($namaGuru, 'attr') ?>"
                            class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-lg bg-slate-100 ring-1 ring-slate-900/5">
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full <?= $akunAktif ? 'bg-emerald-500' : 'bg-slate-400' ?> border-4 border-white"
                            title="<?= $akunAktif ? 'Akun login aktif' : 'Akun login tidak aktif' ?>"></span>
                    </div>
                    <div class="min-w-0 sm:mt-16">
                        <h4 class="text-xl md:text-2xl font-bold text-slate-800 leading-tight mb-1 break-words"><?= esc($namaGuru) ?></h4>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <?php if (!empty($guru->kode_guru)): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 font-semibold">
                                    <i class="bi bi-hash"></i><?= esc($guru->kode_guru) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($guru->nip)): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 font-semibold">
                                    <i class="bi bi-person-badge"></i>NIP <?= esc($guru->nip) ?>
                                </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border font-bold uppercase tracking-wide <?= $akunAktif ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-slate-500 border-slate-200' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $akunAktif ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                <?= esc($statusAkunText) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:mt-16">
                    <a href="<?= site_url('dataguru') ?>"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-xs">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <?php if ($canManage): ?>
                        <a href="<?= site_url('dataguru/editJabatan/' . (int) $id_guru) ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition-colors shadow-xs">
                            <i class="bi bi-briefcase"></i> Atur Jabatan
                        </a>
                        <a href="<?= site_url('dataguru/edit/' . (int) $id_guru) ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                            <i class="bi bi-pencil-square"></i> Edit Profil
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 mt-6 pt-5 border-t border-slate-100">
                <div class="flex items-center gap-2.5 text-xs min-w-0">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><i class="bi bi-envelope"></i></span>
                    <div class="min-w-0">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</span>
                        <span class="block text-slate-700 font-medium truncate"><?= !empty($guru->email) ? esc($guru->email) : 'Belum diisi' ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 text-xs min-w-0">
                    <span class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center shrink-0"><i class="bi bi-briefcase"></i></span>
                    <div class="min-w-0">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Jabatan</span>
                        <span class="block text-slate-700 font-medium truncate"><?= !empty($jabatan_aktif->level) ? esc($jabatan_aktif->level) : 'Guru Mata Pelajaran' ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 text-xs min-w-0">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i class="bi bi-person-vcard"></i></span>
                    <div class="min-w-0">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Status Pegawai</span>
                        <span class="block text-slate-700 font-medium truncate"><?= !empty($guru->status_pegawai) ? esc($guru->status_pegawai) : 'Belum diisi' ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 text-xs min-w-0">
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"><i class="bi bi-calendar3"></i></span>
                    <div class="min-w-0">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">TMT</span>
                        <span class="block text-slate-700 font-medium truncate"><?= $tmt !== '' ? esc($tmt) : 'Belum diisi' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik nyata dari penugasan & relasi -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-calendar-range"></i></span>
            <div>
                <div class="text-2xl font-bold text-slate-800 leading-none"><?= count($periodeSet) ?></div>
                <div class="text-[11px] font-semibold text-slate-500 mt-1">Periode penugasan</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-book"></i></span>
            <div>
                <div class="text-2xl font-bold text-slate-800 leading-none"><?= count($mapelSet) ?></div>
                <div class="text-[11px] font-semibold text-slate-500 mt-1">Mata pelajaran</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-people"></i></span>
            <div>
                <div class="text-2xl font-bold text-slate-800 leading-none"><?= count($kelasSet) ?></div>
                <div class="text-[11px] font-semibold text-slate-500 mt-1">Kelas pernah diampu</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0"><i class="bi bi-star"></i></span>
            <div>
                <div class="text-2xl font-bold text-slate-800 leading-none"><?= count($ekstraSet) ?></div>
                <div class="text-[11px] font-semibold text-slate-500 mt-1">Ekstrakurikuler</div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5 items-start">
        <div class="lg:col-span-2 space-y-5">

            <!-- Penugasan periode aktif -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2 bg-slate-50/50">
                    <div>
                        <h5 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                            <i class="bi bi-briefcase-fill text-blue-600"></i> Penugasan Periode Aktif
                        </h5>
                        <p class="text-xs text-slate-500 mb-0">
                            TP <?= esc($tp_active->tahun ?? '-') ?> &middot; <?= esc($smt_active->nama_smt ?? $smt_active->smt ?? '-') ?>
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-bold uppercase tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Semester berjalan
                    </span>
                </div>

                <div class="p-5">
                    <?php if ($jabatan_aktif): ?>
                        <?php
                        $kelasWali = $jabatan_aktif->nama_kelas ?? '';
                        if ($kelasWali === '' && !empty($jabatan_aktif->id_kelas)) {
                            $kelasWali = $kelas_map[(int) $jabatan_aktif->id_kelas] ?? '';
                        }
                        ?>
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 text-violet-700 border border-violet-100 text-xs font-semibold">
                                <i class="bi bi-briefcase-fill"></i>
                                <?= esc($jabatan_aktif->level ?? 'Guru Mata Pelajaran') ?>
                            </span>
                            <?php if ($kelasWali !== ''): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-semibold">
                                    <i class="bi bi-people-fill"></i> Wali Kelas <?= esc($kelasWali) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-2.5">
                            <?php if (empty($mapelAktif)): ?>
                                <div class="sm:col-span-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-3 text-xs text-slate-500">
                                    <i class="bi bi-info-circle mr-1"></i> Belum ada mata pelajaran yang diampu pada periode ini.
                                </div>
                            <?php else: ?>
                                <?php foreach ($mapelAktif as $mapel):
                                    $namaMapel = trim((string) ($mapel->nama_mapel ?? ''));
                                    if ($namaMapel === '') {
                                        $namaMapel = 'Mapel #' . (int) ($mapel->id_mapel ?? 0);
                                    }
                                    $kelasMapel = [];
                                    foreach (($mapel->kelas_mapel ?? []) as $kelas) {
                                        $idKelas = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
                                        if ($idKelas > 0 && isset($kelas_map[$idKelas])) {
                                            $kelasMapel[] = $kelas_map[$idKelas];
                                        }
                                    }
                                    ?>
                                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <i class="bi bi-book text-blue-500 shrink-0"></i>
                                                <span class="text-sm font-semibold text-slate-800 truncate" title="<?= esc($namaMapel, 'attr') ?>"><?= esc($namaMapel) ?></span>
                                            </div>
                                            <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-white text-blue-700 border border-blue-100"><?= count(array_unique($kelasMapel)) ?> kelas</span>
                                        </div>
                                        <?php if ($kelasMapel): ?>
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                <?php foreach (array_unique($kelasMapel) as $namaKelas): ?>
                                                    <span class="px-1.5 py-0.5 rounded-md bg-white text-[10px] font-semibold text-slate-600 border border-slate-200"><?= esc($namaKelas) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-[11px] text-amber-600 mt-1.5"><i class="bi bi-exclamation-circle mr-1"></i>Kelas belum dipilih</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($ekstraAktif)): ?>
                            <div class="mt-4">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Ekstrakurikuler</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($ekstraAktif as $ekstra):
                                        $namaEkstra = trim((string) ($ekstra->nama_ekstra ?? ''));
                                        if ($namaEkstra === '') {
                                            $namaEkstra = 'Ekstra #' . (int) ($ekstra->id_ekstra ?? 0);
                                        }
                                        ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-100 text-xs font-semibold">
                                            <i class="bi bi-star-fill text-[10px]"></i><?= esc($namaEkstra) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-100">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xl shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-amber-800 mb-0.5">Belum ada penugasan pada periode aktif</p>
                                <p class="text-xs text-amber-700">Atur jabatan, wali kelas, dan mata pelajaran agar guru ini muncul pada jadwal, materi, dan penilaian semester berjalan.</p>
                            </div>
                            <?php if ($canManage): ?>
                                <a href="<?= site_url('dataguru/editJabatan/' . (int) $id_guru) ?>"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-semibold hover:bg-amber-600 transition-colors shrink-0">
                                    <i class="bi bi-plus-lg"></i> Atur sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Riwayat penugasan -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h5 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                            <i class="bi bi-clock-history text-indigo-600"></i> Riwayat Penugasan
                        </h5>
                        <p class="text-xs text-slate-500 mb-0">Seluruh jabatan dan mata pelajaran, terbaru lebih dulu</p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold">
                        <?= count($jabatan) ?> data
                    </span>
                </div>

                <div class="p-5">
                    <?php if (empty($riwayat)): ?>
                        <div class="p-8 text-center rounded-2xl bg-slate-50 border border-dashed border-slate-200">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-white text-slate-300 border border-slate-200 flex items-center justify-center text-xl">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-600 mb-1">Belum ada riwayat penugasan</p>
                            <p class="text-xs text-slate-400 mb-0">Data jabatan guru akan tampil di sini setelah diatur pada menu Atur Jabatan.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($riwayat as $idTp => $rows):
                                $tahunAjaran = trim((string) ($rows[0]->tahun ?? ''));
                                if ($tahunAjaran === '') {
                                    $tahunAjaran = 'TP #' . (int) $idTp;
                                }
                                ?>
                                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                                        <h6 class="text-sm font-bold text-slate-700 mb-0 flex items-center gap-2">
                                            <i class="bi bi-calendar3 text-slate-400"></i><?= esc($tahunAjaran) ?>
                                        </h6>
                                        <span class="text-[10px] font-semibold text-slate-400"><?= count($rows) ?> semester</span>
                                    </div>
                                    <div class="divide-y divide-slate-100">
                                        <?php foreach ($rows as $row):
                                            $isPeriodeAktif = $jabatan_aktif
                                                && (int) $row->id_tp === (int) $jabatan_aktif->id_tp
                                                && (int) $row->id_smt === (int) $jabatan_aktif->id_smt;

                                            $labelSmt = trim((string) ($row->nama_smt ?? ''));
                                            if ($labelSmt === '') {
                                                $labelSmt = 'Semester ' . (int) $row->id_smt;
                                            }

                                            $kelasWali = trim((string) ($row->nama_kelas ?? ''));
                                            if ($kelasWali === '' && !empty($row->id_kelas)) {
                                                $kelasWali = $kelas_map[(int) $row->id_kelas] ?? '';
                                            }
                                            ?>
                                            <div class="p-4 <?= $isPeriodeAktif ? 'bg-blue-50/30' : 'bg-white' ?>">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border text-[11px] font-bold <?= $isPeriodeAktif ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200' ?>">
                                                            <i class="bi bi-calendar-week text-[10px]"></i><?= esc($labelSmt) ?>
                                                        </span>
                                                        <span class="text-sm font-semibold text-slate-800"><?= esc($row->level ?? 'Guru Mata Pelajaran') ?></span>
                                                        <?php if ($kelasWali !== ''): ?>
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 text-[11px] font-semibold">
                                                                <i class="bi bi-people-fill text-[10px]"></i>Kelas <?= esc($kelasWali) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($isPeriodeAktif): ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wide">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Periode aktif
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="grid md:grid-cols-2 gap-3 mt-3">
                                                    <div>
                                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Mata Pelajaran</p>
                                                        <?php if (empty($row->mapel_kelas)): ?>
                                                            <p class="text-xs text-slate-400 italic">Tidak ada</p>
                                                        <?php else: ?>
                                                            <ul class="space-y-1.5 mb-0">
                                                                <?php foreach ($row->mapel_kelas as $mapel):
                                                                    $namaMapel = trim((string) ($mapel->nama_mapel ?? ''));
                                                                    if ($namaMapel === '') {
                                                                        $namaMapel = 'Mapel #' . (int) ($mapel->id_mapel ?? 0);
                                                                    }
                                                                    $kelasMapel = [];
                                                                    foreach (($mapel->kelas_mapel ?? []) as $kelas) {
                                                                        $idKelas = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
                                                                        if ($idKelas > 0 && isset($kelas_map[$idKelas])) {
                                                                            $kelasMapel[] = $kelas_map[$idKelas];
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <li class="flex items-start gap-2 text-xs text-slate-700">
                                                                        <i class="bi bi-book text-blue-400 mt-0.5 shrink-0"></i>
                                                                        <div class="min-w-0">
                                                                            <span class="font-semibold"><?= esc($namaMapel) ?></span>
                                                                            <?php if ($kelasMapel): ?>
                                                                                <span class="block text-[11px] text-slate-400"><?= esc(implode(', ', array_unique($kelasMapel))) ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div>
                                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Ekstrakurikuler</p>
                                                        <?php if (empty($row->ekstra_kelas)): ?>
                                                            <p class="text-xs text-slate-400 italic">Tidak ada</p>
                                                        <?php else: ?>
                                                            <ul class="space-y-1.5 mb-0">
                                                                <?php foreach ($row->ekstra_kelas as $ekstra):
                                                                    $namaEkstra = trim((string) ($ekstra->nama_ekstra ?? ''));
                                                                    if ($namaEkstra === '') {
                                                                        $namaEkstra = 'Ekstra #' . (int) ($ekstra->id_ekstra ?? 0);
                                                                    }
                                                                    $kelasEkstra = [];
                                                                    foreach (($ekstra->kelas_ekstra ?? []) as $kelas) {
                                                                        $idKelas = (int) ($kelas->id_kelas ?? $kelas->kelas ?? 0);
                                                                        if ($idKelas > 0 && isset($kelas_map[$idKelas])) {
                                                                            $kelasEkstra[] = $kelas_map[$idKelas];
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <li class="flex items-start gap-2 text-xs text-slate-700">
                                                                        <i class="bi bi-star text-amber-400 mt-0.5 shrink-0"></i>
                                                                        <div class="min-w-0">
                                                                            <span class="font-semibold"><?= esc($namaEkstra) ?></span>
                                                                            <?php if ($kelasEkstra): ?>
                                                                                <span class="block text-[11px] text-slate-400"><?= esc(implode(', ', array_unique($kelasEkstra))) ?></span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <!-- Informasi kepegawaian (semua pengguna yang boleh melihat data guru) -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                    <h5 class="font-bold text-slate-800 flex items-center gap-2 mb-0">
                        <i class="bi bi-person-badge text-violet-600"></i> Kepegawaian
                    </h5>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start justify-between gap-3 text-xs">
                        <span class="text-slate-500">NIP</span>
                        <span class="font-semibold text-slate-800 text-right font-mono"><?= !empty($guru->nip) ? esc($guru->nip) : '-' ?></span>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-xs">
                        <span class="text-slate-500">Kode Guru</span>
                        <span class="font-semibold text-slate-800 text-right font-mono"><?= !empty($guru->kode_guru) ? esc($guru->kode_guru) : '-' ?></span>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-xs">
                        <span class="text-slate-500">Status Pegawai</span>
                        <span class="font-semibold text-slate-800 text-right"><?= !empty($guru->status_pegawai) ? esc($guru->status_pegawai) : '-' ?></span>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-xs">
                        <span class="text-slate-500">TMT Pengangkatan</span>
                        <span class="font-semibold text-slate-800 text-right"><?= $tmt !== '' ? esc($tmt) : '-' ?></span>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-xs">
                        <span class="text-slate-500">Jenis Kelamin</span>
                        <span class="font-semibold text-slate-800 text-right"><?= $jenisKelamin !== '' ? esc($jenisKelamin) : '-' ?></span>
                    </div>
                </div>
            </div>

            <?php if ($canManage): ?>
                <!-- Data pribadi & kontak (khusus admin: mengandung PII) -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                        <h5 class="font-bold text-slate-800 flex items-center gap-2 mb-0">
                            <i class="bi bi-person-lines-fill text-blue-600"></i> Data Pribadi &amp; Kontak
                        </h5>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex items-start justify-between gap-3 text-xs">
                            <span class="text-slate-500">NIK</span>
                            <span class="font-semibold text-slate-800 text-right font-mono"><?= !empty($guru->no_ktp) ? esc($guru->no_ktp) : '-' ?></span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-xs">
                            <span class="text-slate-500">Agama</span>
                            <span class="font-semibold text-slate-800 text-right"><?= !empty($guru->agama) ? esc($guru->agama) : '-' ?></span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-xs">
                            <span class="text-slate-500">Tempat / Tgl. Lahir</span>
                            <span class="font-semibold text-slate-800 text-right">
                                <?php
                                $ttl = trim((string) ($guru->tempat_lahir ?? ''));
                                if ($tglLahir !== '') {
                                    $ttl = ($ttl !== '' ? $ttl . ', ' : '') . $tglLahir;
                                }
                                echo $ttl !== '' ? esc($ttl) : '-';
                                ?>
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-xs">
                            <span class="text-slate-500">No. HP</span>
                            <span class="font-semibold text-slate-800 text-right"><?= !empty($guru->no_hp) ? esc($guru->no_hp) : '-' ?></span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-xs">
                            <span class="text-slate-500">Telegram ID</span>
                            <span class="font-semibold text-slate-800 text-right font-mono break-all"><?= !empty($guru->telegram_id) ? esc($guru->telegram_id) : '-' ?></span>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Alamat</p>
                            <p class="text-xs text-slate-700 mb-1"><?= !empty($guru->alamat_jalan) ? esc($guru->alamat_jalan) : '<span class="text-slate-400 italic">Belum diisi</span>' ?></p>
                            <?php
                            $wilayah = array_filter([
                                trim((string) ($guru->kecamatan ?? '')),
                                trim((string) ($guru->kabupaten ?? '')),
                                trim((string) ($guru->provinsi ?? '')),
                            ]);
                            $kodePos = trim((string) ($guru->kode_pos ?? ''));
                            if ($kodePos !== '') {
                                $wilayah[] = $kodePos;
                            }
                            ?>
                            <p class="text-[11px] text-slate-500 mb-0"><?= $wilayah ? esc(implode(', ', $wilayah)) : 'Wilayah belum diisi' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Akun login -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2">
                        <h5 class="font-bold text-slate-800 flex items-center gap-2 mb-0">
                            <i class="bi bi-shield-lock text-emerald-600"></i> Akun Login
                        </h5>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $akunAktif ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200' ?>">
                            <?= $akun ? ($akunAktif ? 'Aktif' : 'Nonaktif') : 'Belum tertaut' ?>
                        </span>
                    </div>
                    <div class="p-5 space-y-3">
                        <?php if ($akun): ?>
                            <div class="flex items-start justify-between gap-3 text-xs">
                                <span class="text-slate-500">Username</span>
                                <span class="font-semibold text-slate-800 text-right font-mono break-all"><?= esc($akun->username ?? '-') ?></span>
                            </div>
                            <div class="flex items-start justify-between gap-3 text-xs">
                                <span class="text-slate-500">Email akun</span>
                                <span class="font-semibold text-slate-800 text-right break-all"><?= !empty($akun->email) ? esc($akun->email) : '-' ?></span>
                            </div>
                            <div class="flex items-start justify-between gap-3 text-xs">
                                <span class="text-slate-500">Login terakhir</span>
                                <span class="font-semibold text-slate-800 text-right"><?= $lastLogin !== '' ? esc($lastLogin) : 'Belum pernah' ?></span>
                            </div>
                            <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-100 mb-0">
                                <i class="bi bi-info-circle mr-1"></i>Bila guru dihapus, akun non-admin akan dinonaktifkan (tidak dihapus) agar jejak data lain tetap utuh.
                            </p>
                        <?php else: ?>
                            <div class="flex items-start gap-2 p-3 rounded-xl bg-amber-50 border border-amber-100 text-xs text-amber-700">
                                <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0"></i>
                                <span>Belum ada akun login yang tertaut ke data guru ini. Notifikasi personal dan akses mandiri belum dapat digunakan.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Zona hapus permanen -->
                <div class="bg-white rounded-2xl border border-rose-200 shadow-xs overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-rose-100 bg-rose-50/60">
                        <h5 class="font-bold text-rose-700 flex items-center gap-2 mb-0">
                            <i class="bi bi-exclamation-octagon"></i> Zona Hapus Permanen
                        </h5>
                    </div>
                    <div class="p-5 space-y-4">
                        <p class="text-xs text-slate-600 mb-0">
                            Menghapus guru akan <b>menghapus permanen</b> seluruh data terkait dan <b>melepas</b> penunjukan wali kelas.
                            Tindakan ini <b>tidak dapat dibatalkan</b>.
                        </p>
                        <p class="text-[11px] text-slate-400 mb-0">
                            <i class="bi bi-shield-exclamation mr-1"></i>Data guru yang tertaut dengan akun Anda sendiri tidak dapat dihapus dari sini.
                        </p>

                        <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                            <?php foreach ($linkedLabels as $key => $meta):
                                $jumlah = (int) ($linked[$key] ?? 0);
                                ?>
                                <div class="flex items-center justify-between gap-3 px-3 py-2 text-xs <?= $jumlah === 0 ? 'opacity-50' : '' ?>">
                                    <span class="flex items-center gap-2 text-slate-600">
                                        <i class="bi <?= esc($meta['icon'], 'attr') ?> text-slate-400"></i><?= esc($meta['label']) ?>
                                    </span>
                                    <span class="flex items-center gap-2">
                                        <span class="font-bold <?= $jumlah > 0 ? 'text-slate-800' : 'text-slate-400' ?>"><?= $jumlah ?></span>
                                        <span class="text-[10px] text-slate-400"><?= esc($meta['action']) ?></span>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div>
                            <label for="confirmNama" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ketik <span class="font-mono text-rose-700"><?= esc($namaGuru) ?></span> untuk mengonfirmasi
                            </label>
                            <input type="text" id="confirmNama" autocomplete="off" spellcheck="false"
                                class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20 transition-all outline-hidden"
                                placeholder="Nama guru harus sama persis">
                            <p id="confirmHint" class="text-[11px] text-slate-400 mt-1 mb-0">Tombol hapus aktif setelah nama diketik sama persis.</p>
                        </div>

                        <button type="button" id="btnForceDelete" disabled
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-xs shadow-rose-500/20">
                            <i class="bi bi-trash3"></i> Hapus Guru Secara Permanen
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    var $input = $('#confirmNama');
    var $btn = $('#btnForceDelete');
    var $hint = $('#confirmHint');

    if (!$input.length || !$btn.length) return;

    var expected = <?= json_encode($namaGuru, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var originalHtml = $btn.html();
    var submitting = false;

    function isValid() {
        return $.trim($input.val()) === expected;
    }

    function refreshState() {
        var ok = isValid();
        $btn.prop('disabled', !ok || submitting);

        if (ok) {
            $input.removeClass('border-rose-300 focus:border-rose-400').addClass('border-emerald-300 focus:border-emerald-400');
            $hint.removeClass('text-slate-400 text-rose-600').addClass('text-emerald-600')
                .html('<i class="bi bi-check-circle mr-1"></i>Nama cocok. Tombol hapus sudah aktif.');
        } else if ($.trim($input.val()) === '') {
            $input.removeClass('border-emerald-300 border-rose-300 focus:border-emerald-400').addClass('border-slate-200');
            $hint.removeClass('text-emerald-600 text-rose-600').addClass('text-slate-400')
                .text('Tombol hapus aktif setelah nama diketik sama persis.');
        } else {
            $input.removeClass('border-emerald-300 focus:border-emerald-400').addClass('border-rose-300 focus:border-rose-400');
            $hint.removeClass('text-slate-400 text-emerald-600').addClass('text-rose-600')
                .html('<i class="bi bi-x-circle mr-1"></i>Nama belum sama persis.');
        }
    }

    $input.on('input', refreshState);
    refreshState();

    $btn.on('click', function () {
        if (!isValid() || submitting) return;

        Swal.fire({
            title: 'Hapus permanen?',
            html: 'Seluruh data terkait <b>' + $('<div>').text(expected).html() + '</b> akan dihapus dan tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus permanen',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (result) {
            if (!(result.isConfirmed || result.value) || submitting) return;

            submitting = true;
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span> Menghapus...');

            $.ajax({
                url: '<?= site_url('dataguru/forcedelete') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    id_guru: <?= (int) $id_guru ?>,
                    confirm_nama: expected,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                success: function (res) {
                    if (res && res.status) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: res.message || 'Data guru berhasil dihapus permanen.',
                            icon: 'success',
                            confirmButtonColor: '#059669'
                        }).then(function () {
                            window.location.href = '<?= site_url('dataguru') ?>';
                        });
                        return;
                    }

                    submitting = false;
                    $btn.html(originalHtml);
                    refreshState();
                    Swal.fire('Gagal', (res && res.message) || 'Data guru gagal dihapus.', 'error');
                },
                error: function (xhr) {
                    submitting = false;
                    $btn.html(originalHtml);
                    refreshState();

                    var message = 'Terjadi kesalahan saat menghapus data guru.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 403) {
                        message = 'Sesi atau akses Anda tidak valid. Muat ulang halaman lalu coba lagi.';
                    }
                    Swal.fire('Gagal', message, 'error');
                }
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
