﻿<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
        <div class="page-header">
        <div class="min-w-0">
                <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs shrink-0">
                        <i class="bi bi-door-open text-xl"></i>
                    </span>
                    <span><?= esc($judul ?? 'Detail Data') ?></span>
                </h3>
            </div>
            <nav class="breadcrumb-tw">
                <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                    <i class="bi bi-house-door mr-1"></i> Dashboard
                </a>
                <span class="sep">/</span>
                <a href="<?= site_url('datakelas') ?>" class="no-underline text-slate-500 hover:text-blue-600">Data Kelas</a>
                <span class="sep">/</span>
                <span class="active font-medium text-slate-800">Detail</span>
            </nav>
        </div>
    </div>

<!-- Main Content -->
<div class="content-area">
<div class="card-tw">
            <div class="card-tw-header">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                        <i class="bi bi-info-circle text-emerald-600 text-sm"></i>
                    </div>
                    <h6><?= esc($subjudul . ' ' . ($kelas->nama_kelas ?? '-')) ?></h6>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <a href="<?= site_url('datakelas') ?>" class="btn-tw btn-tw-sm btn-warning">
                        <i class="bi bi-arrow-left mr-1"></i>Kembali
                    </a>
                </div>
            </div>
            <div class="card-tw-body">
                <div class="flex flex-wrap gap-4">
                    <div class="w-full">
                        <div class="table-responsive-tw"><table class="table-tw">
                                <tr>
                                    <td style="width: 200px" class="font-bold bg-body-tertiary">Nama Kelas</td>
                                    <td><?= esc($kelas->nama_kelas ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="font-bold bg-body-tertiary">Kode Kelas</td>
                                    <td><?= $kelas->kode_kelas ?? '-' ?></td>
                                </tr>
                                <?php if ($setting->jenjang == '3'): ?>
                                    <tr>
                                        <td class="font-bold bg-body-tertiary">Jurusan</td>
                                        <td><?= esc($kelas->nama_jurusan ?? '-') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="font-bold bg-body-tertiary">Level Kelas</td>
                                    <td><?= $kelas->level ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="font-bold bg-body-tertiary">Wali Kelas</td>
                                    <td><?= esc($kelas->nama_guru ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="font-bold bg-body-tertiary text-emerald-600">Jumlah Siswa</td>
                                    <td class="font-bold text-emerald-600"><?= count($siswas) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="card-tw">
                            <div class="card-tw-header">
                                <h3 class="font-bold text-slate-700">
                                    <i class="bi bi-people mr-2"></i>Daftar Siswa
                                </h3>
                            </div>
                            <div class="p-0">
                                <div class="table-tw" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table-tw">
                                        <thead class="sticky top-0 bg-white">
                                            <tr>
                                                <th class="text-center" style="width: 50px">No</th>
                                                <th class="text-center" style="width: 150px">NIS / NISN</th>
                                                <th>Nama Siswa</th>
                                                <th class="text-center" style="width: 80px">L/P</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($siswas) > 0): ?>
                                                <?php $no = 1;
                                                foreach ($siswas as $siswa): ?>
                                                    <tr>
                                                        <td class="text-center align-middle"><?= $no++ ?></td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge-tw badge-secondary"><?= esc($siswa->nis) ?></span>
                                                            <span class="badge-tw badge-secondary"><?= $siswa->nisn ?></span>
                                                        </td>
                                                        <td class="align-middle font-medium"><?= esc($siswa->nama) ?></td>
                                                        <td class="text-center align-middle"><?= $siswa->jenis_kelamin ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-8 text-slate-400 fst-italic">
                                                        <i class="bi bi-emoji-frown text-2xl block mb-2"></i>
                                                        Belum ada siswa di kelas ini
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-5/12">
                        <div class="card-tw">
                            <div class="card-tw-header">
                                <h3 class="font-bold text-slate-700">
                                    <i class="bi bi-diagram-3 mr-2"></i>Struktur Organisasi
                                </h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <span class="badge-tw badge-success">Wali Kelas</span>
                                </div>
                            </div>
                            <div class="p-0">
                                <div class="table-tw">
                                    <?php
                                    $arrJabatan = [
                                        'wali_kelas' => 'Wali Kelas',
                                        'ketua' => 'Ketua Kelas',
                                        'wakil_ketua' => 'Wakil Ketua',
                                        'sekretaris_1' => 'Sekretaris I',
                                        'sekretaris_2' => 'Sekretaris II',
                                        'bendahara_1' => 'Bendahara I',
                                        'bendahara_2' => 'Bendahara II',
                                        'sie_upacara' => 'Sie Upacara',
                                        'sie_ekstrakurikuler' => 'Sie Ekstrakurikuler',
                                        'sie_olahraga' => 'Sie Olahraga',
                                        'sie_keagamaan' => 'Sie Keagamaan',
                                        'sie_keamanan' => 'Sie Keamanan',
                                        'sie_ketertiban' => 'Sie Ketertiban',
                                        'sie_kebersihan' => 'Sie Kebersihan',
                                        'sie_keindahan' => 'Sie Keindahan',
                                        'sie_kesehatan' => 'Sie Kesehatan',
                                        'sie_kekeluargaan' => 'Sie Kekeluargaan',
                                        'sie_humas' => 'Sie Humas'
                                    ];
                                    ?>
                                    <table class="table-tw">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 50px">No</th>
                                                <th>Jabatan</th>
                                                <th>Nama / Petugas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1;
                                            foreach ($arrJabatan as $key => $j): ?>
                                                <tr>
                                                    <td class="text-center align-middle"><?= $no++ ?></td>
                                                    <td class="align-middle font-semibold text-slate-500"><?= $j ?></td>
                                                    <td class="align-middle">
                                                        <?php
                                                        if ($key === 'wali_kelas') {
                                                            echo '<span class="font-bold text-emerald-600">' . ($kelas->nama_guru ?? '-') . '</span>';
                                                        } else {
                                                            $siswa_id = $struktur->$key ?? null;
                                                            $found = false;
                                                            if ($siswa_id) {
                                                                foreach ($siswas as $s) {
                                                                    if ($s->id_siswa == $siswa_id) {
                                                                        echo $s->nama;
                                                                        $found = true;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            if (!$found)
                                                                echo '<span class="text-slate-400 fst-italic text-sm">-</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>