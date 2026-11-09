<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    /* ── Tab pills ── */
    .tab-pill { transition: background .15s, color .15s, box-shadow .15s; position: relative; }
    .tab-pill.active { background: #fff; color: var(--primary-600); box-shadow: 0 1px 6px rgba(0,0,0,.08); }
    .tab-pill .tab-err {
        position: absolute; top: 4px; right: 6px;
        min-width: 16px; height: 16px; padding: 0 4px;
        background: #ef4444; color: #fff; font-size: 10px; font-weight: 800;
        border-radius: 9999px; display: none; align-items: center; justify-content: center;
    }
    .tab-pill.has-error .tab-err { display: inline-flex; }
    .tab-pill .tab-count {
        min-width: 20px; height: 20px; padding: 0 6px;
        background: #e0e7ff; color: #3730a3; font-size: 10px; font-weight: 800;
        border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center;
    }
    /* ── Section title ── */
    .sec-title { display: flex; align-items: center; gap: .6rem; margin: 1.25rem 0 .75rem; }
    .sec-title:first-child { margin-top: 0; }
    .sec-title .sec-ic {
        width: 28px; height: 28px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 13px;
    }
    .sec-title h3 { font-size: 13px; font-weight: 800; color: #0f172a; margin: 0; }
    .sec-title p { font-size: 11px; color: #94a3b8; margin: 0; }
    /* ── Field states ── */
    .field-err { border-color: #f87171 !important; box-shadow: 0 0 0 3px rgba(239,68,68,.12) !important; }
    .field-msg { display: none; font-size: 11px; color: #dc2626; margin-top: 4px; }
    .field-msg.show { display: block; }
    .req::after { content: ' *'; color: #ef4444; font-weight: 800; }
    /* ── Sticky save bar ── */
    .savebar {
        position: sticky; bottom: 12px; z-index: 30;
        background: rgba(255,255,255,.96); backdrop-filter: blur(8px);
        border: 1px solid #e2e8f0; border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15,23,42,.12);
    }
    .dirty-dot { width: 8px; height: 8px; border-radius: 9999px; background: #cbd5e1; }
    .dirty-dot.on { background: #f59e0b; animation: blink 1.2s infinite; }
    @keyframes blink { 50% { opacity: .35; } }
    /* ── Dropzone foto ── */
    .dz { border: 2px dashed #cbd5e1; border-radius: 14px; transition: all .15s; cursor: pointer; }
    .dz:hover, .dz.over { border-color: var(--primary-600); background: var(--primary-50); }
    /* ── Password strength ── */
    .pw-bar { height: 6px; border-radius: 9999px; background: #e2e8f0; overflow: hidden; }
    .pw-bar > div { height: 100%; width: 0; border-radius: 9999px; transition: width .2s, background .2s; }
    /* ── Signature canvas ── */
    #sig-canvas {
        border: 2px dotted #CCCCCC;
        border-radius: 15px;
        cursor: crosshair;
        background-color: #ffffff;
        width: 100%;
        max-width: 400px;
        height: 200px;
        touch-action: none;
        display: block;
        margin: 0 auto;
    }
    @media (max-width: 1023px) { .profile-sticky { position: static !important; top: auto !important; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$fotoRaw = trim((string) ($guru->foto ?? ''));
$foto = ($fotoRaw !== '' && strpos($fotoRaw, '..') === false && file_exists(FCPATH . ltrim($fotoRaw, '/')))
    ? base_url(ltrim($fotoRaw, '/'))
    : base_url('assets/img/guru.png');

// Peta field by name agar bisa dirender per seksi tanpa ubah controller
$mapProf = []; foreach (($input_profile ?? []) as $it) { $mapProf[$it->name] = $it; }
$mapAlm  = []; foreach (($input_alamat ?? []) as $it) { $mapAlm[$it->name] = $it; }
$v = fn($m, $k) => esc($m[$k]->value ?? '');

// Kelengkapan data (progress). no_hp sengaja TIDAK dihitung lagi: kolom
// itu milik Dapodik dan tidak lagi bisa diisi dari form ini, jadi
// menghitungnya membuat progres terlihat selalu kurang.
$checkVals = [
    $mapProf['nama_guru']->value ?? '', $mapProf['nip']->value ?? '', $mapProf['email']->value ?? '',
    $mapAlm['tempat_lahir']->value ?? '', $mapAlm['tgl_lahir']->value ?? '',
    $mapAlm['alamat_jalan']->value ?? '', $mapAlm['no_ktp']->value ?? '',
];
$filled = 0;
foreach ($checkVals as $cv) { if (trim((string) $cv) !== '') $filled++; }
$pct = (int) round($filled / count($checkVals) * 100);

// Tgl lahir → normalisasi Y-m-d untuk input date
$tglLahir = trim((string) ($mapAlm['tgl_lahir']->value ?? ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir) || $tglLahir === '0000-00-00') $tglLahir = '';

// TMT pengangkatan → normalisasi Y-m-d, caranya sama dengan tgl lahir
$tmtPegawai = trim((string) ($mapProf['tmt']->value ?? ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tmtPegawai) || $tmtPegawai === '0000-00-00') $tmtPegawai = '';

$jabatan = trim(($guru->level ?? '') . ' ' . ($guru->nama_kelas ?? ''));
if ($jabatan === '') $jabatan = 'Guru / Tenaga Kependidikan';
?>

<div class="fade-in-up p-4 md:p-6 space-y-4 w-full max-w-none">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold" aria-label="Breadcrumb">
        <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 no-underline">Dashboard</a>
        <span>/</span>
        <a href="<?= site_url('dataguru') ?>" class="hover:text-blue-600 no-underline">Data Guru</a>
        <span>/</span>
        <span class="text-slate-600">Edit</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[11px] font-bold">
                    <i class="bi bi-pencil-square"></i> Edit Data Guru
                </span>
                <span class="badge-tw badge-primary font-mono">NIP <?= esc($guru->nip ?? '-') ?></span>
            </div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight truncate"><?= esc($judul ?? 'Edit Guru') ?> — <?= esc($guru->nama_guru ?? '') ?></h1>
            <p class="text-xs text-slate-500 mt-1 font-mono"><?= esc($subjudul ?? 'Edit Data') ?> · @<?= esc($guru->username ?? '-') ?> · TP <?= esc($tp_active->tahun ?? '-') ?> <?= esc($smt_active->smt ?? '') ?></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="<?= site_url('dataguru') ?>" class="btn-tw btn-light">
                <i class="bi bi-arrow-left mr-1"></i> Kembali
            </a>
            <a href="<?= site_url('dataguru/detail/' . $id_active) ?>" class="btn-tw btn-light">
                <i class="bi bi-eye mr-1"></i> Lihat Detail
            </a>
            <button type="button" id="btn-save-top" class="btn-tw btn-primary">
                <i class="bi bi-save mr-1"></i> Simpan
            </button>
        </div>
    </div>

    <!-- Inline error alert -->
    <div id="form-alert" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700" role="alert"></div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start w-full">
        <!-- ═══ Kolom profil (sticky) — 40% ═══ -->
        <aside class="w-full min-w-0 lg:col-span-2 profile-sticky lg:sticky lg:top-4 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="flex items-center gap-2 p-4 border-b border-slate-100">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-person-badge text-sm"></i>
                    </span>
                    <h2 class="text-sm font-extrabold text-slate-900">Profil Guru</h2>
                </div>
                <div class="p-5">
                    <div class="mb-5 text-center">
                        <div class="relative inline-block">
                            <img id="foto-guru" src="<?= esc($foto, 'attr') ?>"
                                class="rounded-full shadow-sm ring-4 ring-slate-100"
                                style="width: 132px; height: 132px; object-fit: cover;"
                                alt="Foto <?= esc($guru->nama_guru ?? 'guru') ?>"
                                onerror="this.onerror=null;this.src='<?= base_url('assets/img/guru.png') ?>'">
                            <span class="absolute bottom-2 right-2 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white" title="Aktif"></span>
                            <button type="button"
                                class="btn-tw btn-tw-sm btn-primary !rounded-full !p-0 absolute bottom-0 right-0 shadow-md"
                                style="width:32px;height:32px;"
                                data-ui-toggle="modal" data-ui-target="#editFotoModal"
                                title="Ganti Foto" aria-label="Ganti foto">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <h4 id="profile-nama" class="mt-3 font-bold text-slate-900"><?= esc($guru->nama_guru ?? '-') ?></h4>
                        <p class="text-slate-400 text-xs mt-0.5"><?= esc($jabatan) ?></p>
                        <p class="mt-1.5 flex items-center justify-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[11px] font-mono"><i class="bi bi-person-circle"></i>@<?= esc($guru->username ?? '-') ?></span>
                        </p>
                    </div>

                    <!-- Kelengkapan -->
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 mb-4">
                        <div class="flex items-center justify-between text-[11px] font-bold mb-1.5">
                            <span class="text-slate-600">Kelengkapan data</span>
                            <span class="text-blue-700"><?= $pct ?>%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-emerald-500 transition-all" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>

                    <dl class="text-xs space-y-2.5 mb-4">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">NIP / NUPTK</dt>
                            <dd class="font-bold text-slate-700 font-mono text-right"><?= esc($guru->nip ?? '-') ?></dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">No. HP <span class="text-[10px] font-normal">(dari Dapodik)</span></dt>
                            <dd class="font-bold text-slate-700 font-mono text-right"><?= esc($guru->no_hp ?? '-') ?></dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Tahun aktif</dt>
                            <dd class="font-bold text-slate-700">TP <?= esc($tp_active->tahun ?? '-') ?> · <?= esc($smt_active->smt ?? '') ?></dd>
                        </div>
                    </dl>

                    <div class="grid gap-2">
                        <button type="button" class="btn-tw btn-warning w-full justify-center" data-ui-toggle="modal" data-ui-target="#editLoginModal">
                            <i class="bi bi-key mr-2"></i> Akun Login
                        </button>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="btn-tw btn-light justify-center" data-ui-toggle="modal" data-ui-target="#editFotoModal">
                                <i class="bi bi-camera mr-1"></i> Foto
                            </button>
                            <button type="button" class="btn-tw btn-outline-danger justify-center" id="btn-delete-foto">
                                <i class="bi bi-trash mr-1"></i> Hapus
                            </button>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-3 leading-relaxed"><i class="bi bi-info-circle mr-1"></i>Perubahan tersimpan via tombol <b>Simpan</b> di bawah. <kbd class="px-1 py-0.5 bg-slate-100 rounded text-[10px] font-mono">Ctrl+S</kbd> untuk simpan cepat.</p>
                </div>
            </div>

            <!-- Kartu TTD -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="flex items-center gap-2 p-4 border-b border-slate-100">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-pencil-square text-sm"></i>
                    </span>
                    <h2 class="text-sm font-extrabold text-slate-900">Tanda Tangan</h2>
                </div>
                <div class="p-4">
                    <div id="ttd-placeholder"
                        class="items-center justify-center text-slate-400 <?= !empty($guru->ttd) ? 'hidden' : 'flex' ?>"
                        style="height: 100px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc;">
                        <span class="text-xs"><i class="bi bi-pen mr-1"></i>Belum ada TTD</span>
                    </div>
                    <img src="<?= !empty($guru->ttd) ? esc(base_url(ltrim($guru->ttd, '/')), 'attr') : '' ?>" alt="TTD"
                        id="img-ttd-preview"
                        class="max-w-full h-auto mx-auto <?= empty($guru->ttd) ? 'hidden' : 'block' ?>"
                        style="max-height: 100px;">
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <button type="button" class="btn-tw btn-tw-sm btn-success justify-center" id="btn-create-ttd" title="Buat TTD Baru">
                            <i class="bi bi-pencil-square mr-1"></i> <?= empty($guru->ttd) ? 'Buat TTD' : 'Buat Ulang' ?>
                        </button>
                        <button type="button" class="btn-tw btn-tw-sm btn-outline-danger justify-center <?= empty($guru->ttd) ? 'hidden' : '' ?>"
                            id="btn-delete-ttd" title="Hapus TTD">
                            <i class="bi bi-trash mr-1"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ═══ Form — 60% ═══ -->
        <div class="min-w-0 w-full lg:col-span-3">
            <form id="formguru" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id_guru" value="<?= (int) $id_active ?>">

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs w-full">
                    <!-- Tab bar -->
                    <div class="p-3 border-b border-slate-100 lg:sticky lg:top-0 bg-white/95 backdrop-blur rounded-t-3xl z-20">
                        <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl" id="guruEditTabs" role="tablist" aria-label="Bagian data guru">
                            <button type="button" class="tab-pill active flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-[11px] font-bold min-w-0" id="profile-tab" data-ui-toggle="tab" data-ui-target="#profile-pane" role="tab" aria-selected="true" aria-controls="profile-pane">
                                <i class="bi bi-person"></i> Profil <span class="tab-err" data-err-for="profile-pane">!</span>
                            </button>
                            <button type="button" class="tab-pill flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-[11px] font-bold text-slate-500 min-w-0" id="address-tab" data-ui-toggle="tab" data-ui-target="#address-pane" role="tab" aria-selected="false" aria-controls="address-pane">
                                <i class="bi bi-geo-alt"></i> Alamat &amp; Data <span class="tab-err" data-err-for="address-pane">!</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="tab-content pt-1 w-full" id="guruEditTabsContent">
                            <!-- ── TAB: PROFIL ── -->
                            <div class="tab-pane show active" id="profile-pane" role="tabpanel" aria-labelledby="profile-tab">
                                <div class="sec-title">
                                    <span class="sec-ic bg-blue-50 text-blue-600"><i class="bi bi-person"></i></span>
                                    <div><h3>Data Pokok</h3><p>Identitas utama — nama wajib diisi</p></div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="f-nama_guru" class="form-label-tw req">Nama Lengkap</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person"></i></span>
                                            <input type="text" id="f-nama_guru" name="nama_guru" value="<?= $v($mapProf, 'nama_guru') ?>" class="form-control-tw pl-9" placeholder="Nama lengkap beserta gelar" required maxlength="100" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nama_guru"></p>
                                    </div>
                                    <div>
                                        <label for="f-nip" class="form-label-tw">NIP / NUPTK</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" id="f-nip" name="nip" inputmode="numeric" value="<?= $v($mapProf, 'nip') ?>" class="form-control-tw pl-9 font-mono" placeholder="18 digit NIP / NUPTK" maxlength="30" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nip"></p>
                                    </div>
                                    <div>
                                        <label for="f-jk" class="form-label-tw">Jenis Kelamin</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-gender-ambiguous"></i></span>
                                            <select id="f-jk" class="form-select-tw pl-9" name="jenis_kelamin">
                                                <option value="" <?= empty($mapProf['jenis_kelamin']->value ?? '') ? 'selected' : '' ?>>— Pilih —</option>
                                                <option value="L" <?= ($mapProf['jenis_kelamin']->value ?? '') == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                                <option value="P" <?= ($mapProf['jenis_kelamin']->value ?? '') == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-status_pegawai" class="form-label-tw">Status Pegawai</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-vcard"></i></span>
                                            <select id="f-status_pegawai" class="form-select-tw pl-9" name="status_pegawai">
                                                <?php
                                                    $curSp = (string) ($mapProf['status_pegawai']->value ?? '');
                                                    $opsiSp = ['PNS', 'CPNS', 'PPPK', 'Guru Honor Sekolah', 'Tenaga Honor Sekolah'];
                                                    // Nilai legacy di luar daftar tetap ditampilkan
                                                    // supaya menyimpan tidak diam-diam mengosongkannya.
                                                    if ($curSp !== '' && !in_array($curSp, $opsiSp, true)) {
                                                        array_unshift($opsiSp, $curSp);
                                                    }
                                                ?>
                                                <option value="" <?= $curSp === '' ? 'selected' : '' ?>>— Tidak Terdata —</option>
                                                <?php foreach ($opsiSp as $optSp): ?>
                                                    <option value="<?= esc($optSp) ?>" <?= $curSp === $optSp ? 'selected' : '' ?>><?= esc($optSp) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-1 mb-0">Menentukan hak cuti (PNS vs PPPK) di aplikasi absensi.</p>
                                    </div>
                                    <div>
                                        <label for="f-tmt" class="form-label-tw">TMT Pengangkatan</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-calendar-check"></i></span>
                                            <input type="date" id="f-tmt" name="tmt" value="<?= esc($tmtPegawai) ?>" class="form-control-tw pl-9" placeholder="yyyy-mm-dd">
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-1 mb-0">Basis hitungan masa kerja (syarat cuti 1/5 tahun). Kosongkan bila tidak tahu.</p>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-emerald-50 text-emerald-600"><i class="bi bi-telephone"></i></span>
                                    <div><h3>Kontak</h3><p>Email, HP &amp; agama</p></div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="f-email" class="form-label-tw">Email</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-envelope"></i></span>
                                            <input type="email" id="f-email" name="email" value="<?= $v($mapProf, 'email') ?>" class="form-control-tw pl-9" placeholder="nama@sekolah.id" maxlength="100" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-email"></p>
                                    </div>
                                    <div>
                                        <label for="f-agama" class="form-label-tw">Agama</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-star"></i></span>
                                            <select id="f-agama" class="form-select-tw pl-9" name="agama">
                                                <option value="">— Pilih Agama —</option>
                                                <?php foreach (["Islam", "Katolik", "Protestan", "Hindu", "Budha", "Konghucu"] as $agama): ?>
                                                    <option value="<?= $agama ?>" <?= ($mapProf['agama']->value ?? '') == $agama ? 'selected' : '' ?>><?= $agama ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-violet-50 text-violet-600"><i class="bi bi-key"></i></span>
                                    <div><h3>Integrasi</h3><p>Kunci API &amp; kanal notifikasi (opsional)</p></div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="f-api_key" class="form-label-tw">Google API Key</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-key"></i></span>
                                            <input type="password" id="f-api_key" name="api_key" value="<?= $v($mapProf, 'api_key') ?>" class="form-control-tw pl-9 pr-11 font-mono" placeholder="AIza..." maxlength="255" autocomplete="off">
                                            <button type="button" id="btn-toggle-apikey" class="absolute inset-y-0 right-0 px-3.5 text-slate-400 hover:text-slate-600 bg-transparent border-none cursor-pointer" title="Tampilkan/sembunyikan" aria-label="Tampilkan API key"><i class="bi bi-eye"></i></button>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-1 mb-0">Dipakai untuk fitur peta presensi. Biarkan apa adanya jika tidak tahu.</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="f-nomor_rfid" class="form-label-tw">Nomor Kartu RFID</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-card-id"></i></span>
                                                <input type="text" id="f-nomor_rfid" name="nomor_rfid" inputmode="text" value="<?= $v($mapProf, 'nomor_rfid') ?>" class="form-control-tw pl-9 font-mono text-uppercase" placeholder="04A1B2C3D" maxlength="64" autocomplete="off">
                                            </div>
                                            <p class="field-msg" data-msg-for="f-nomor_rfid"></p>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">UID kartu RFID guru/GTK (dicetak pada kartu fisik). Kosongkan bila tidak pakai RFID.</p>
                                        </div>
                                        <div>
                                            <label for="f-fingerprint_uid" class="form-label-tw">UserID Mesin Sidik Jari</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-fingerprint"></i></span>
                                                <input type="text" id="f-fingerprint_uid" name="fingerprint_uid" inputmode="text" value="<?= $v($mapProf, 'fingerprint_uid') ?>" class="form-control-tw pl-9 font-mono" placeholder="1001" maxlength="64" autocomplete="off">
                                            </div>
                                            <p class="field-msg" data-msg-for="f-fingerprint_uid"></p>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">UserID yang terdaftar di mesin sidik jari GEISA GX330. Kosongkan bila tidak pakai fingerprint.</p>
                                        </div>
                                        <div>
                                            <label for="f-telegram_id" class="form-label-tw">ID Telegram</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-telegram"></i></span>
                                                <input type="text" id="f-telegram_id" name="telegram_id" inputmode="numeric" value="<?= $v($mapProf, 'telegram_id') ?>" class="form-control-tw pl-9 font-mono" placeholder="123456789" maxlength="50" autocomplete="off">
                                            </div>
                                            <p class="field-msg" data-msg-for="f-telegram_id"></p>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">Cara mendapat ID: buka bot <b>@userinfobot</b> di Telegram, tekan Start, salin angka yang tampil. Kosongkan untuk melepas.</p>
                                        </div>
                                        <div>
                                            <label for="f-wa_number" class="form-label-tw">No. WhatsApp</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-whatsapp"></i></span>
                                                <input type="tel" id="f-wa_number" name="wa_number" inputmode="tel" value="<?= $v($mapProf, 'wa_number') ?>" class="form-control-tw pl-9 font-mono" placeholder="6281234567890" maxlength="20" autocomplete="off">
                                            </div>
                                            <p class="field-msg" data-msg-for="f-wa_number"></p>
                                            <?php if (!empty($waVerified)): ?>
                                            <p class="text-[11px] text-emerald-600 mt-1 mb-0"><i class="bi bi-check-circle"></i> Nomor ini aktif — notifikasi personal dikirim ke sini.</p>
                                            <?php elseif (!empty($waNumber) && !empty($waNumber)): ?>
                                            <p class="text-[11px] text-amber-600 mt-1 mb-0"><i class="bi bi-hourglass-split"></i> Tersimpan, menunggu konfirmasi di <b>Kelola Tautan Guru</b>.</p>
                                            <?php else: ?>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">Tujuan notifikasi: denda, surat teguran, peringatan alpha. Boleh ditulis 08 / +62 / 62 — otomatis dinormalisasi. Kosongkan untuk melepas.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /#profile-pane -->

                            <!-- ── TAB: ALAMAT & DATA ── -->
                            <div class="tab-pane" id="address-pane" role="tabpanel" aria-labelledby="address-tab">
                                <div class="sec-title">
                                    <span class="sec-ic bg-amber-50 text-amber-600"><i class="bi bi-person-badge"></i></span>
                                    <div><h3>Identitas Kependudukan</h3><p>NIK &amp; kelahiran</p></div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="f-no_ktp" class="form-label-tw">NIK (No. KTP)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" id="f-no_ktp" name="no_ktp" inputmode="numeric" value="<?= $v($mapAlm, 'no_ktp') ?>" class="form-control-tw pl-9 font-mono" placeholder="16 digit NIK" maxlength="20" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-no_ktp"></p>
                                    </div>
                                    <div class="hidden md:block"></div>
                                    <div>
                                        <label for="f-tempat_lahir" class="form-label-tw">Tempat Lahir</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" id="f-tempat_lahir" name="tempat_lahir" value="<?= $v($mapAlm, 'tempat_lahir') ?>" class="form-control-tw pl-9" placeholder="Kota kelahiran" maxlength="50" autocomplete="off">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-tgl_lahir" class="form-label-tw">Tanggal Lahir</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-calendar-date"></i></span>
                                            <input type="date" id="f-tgl_lahir" name="tgl_lahir" value="<?= esc($tglLahir, 'attr') ?>" class="form-control-tw pl-9" max="<?= date('Y-m-d') ?>">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-tgl_lahir"></p>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-blue-50 text-blue-600"><i class="bi bi-house-door"></i></span>
                                    <div><h3>Alamat Domisili</h3><p>Alamat lengkap tempat tinggal</p></div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="f-alamat_jalan" class="form-label-tw">Alamat (Jalan / Dusun)</label>
                                        <textarea id="f-alamat_jalan" name="alamat_jalan" rows="2" class="form-control-tw" placeholder="Nama jalan, nomor rumah, RT/RW, dusun..."><?= $v($mapAlm, 'alamat_jalan') ?></textarea>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 mt-4">
                                    <div>
                                        <label for="f-kecamatan" class="form-label-tw">Kecamatan</label>
                                        <input type="text" id="f-kecamatan" name="kecamatan" value="<?= $v($mapAlm, 'kecamatan') ?>" class="form-control-tw" placeholder="Kecamatan" maxlength="50">
                                    </div>
                                    <div>
                                        <label for="f-kabupaten" class="form-label-tw">Kota / Kabupaten</label>
                                        <input type="text" id="f-kabupaten" name="kabupaten" value="<?= $v($mapAlm, 'kabupaten') ?>" class="form-control-tw" placeholder="Kota / kabupaten" maxlength="50">
                                    </div>
                                    <div>
                                        <label for="f-provinsi" class="form-label-tw">Provinsi</label>
                                        <input type="text" id="f-provinsi" name="provinsi" value="<?= $v($mapAlm, 'provinsi') ?>" class="form-control-tw" placeholder="Provinsi" maxlength="50">
                                    </div>
                                    <div>
                                        <label for="f-kode_pos" class="form-label-tw">Kode Pos</label>
                                        <input type="text" id="f-kode_pos" name="kode_pos" inputmode="numeric" value="<?= $v($mapAlm, 'kode_pos') ?>" class="form-control-tw font-mono" placeholder="85xxx" maxlength="10">
                                        <p class="field-msg" data-msg-for="f-kode_pos"></p>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                    <!-- Sticky save bar -->
                    <div class="px-4 pb-4">
                        <div class="savebar px-4 py-3 space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                    <span class="dirty-dot" id="dirty-dot"></span>
                                    <span id="dirty-text">Belum ada perubahan</span>
                                </span>
                                <span class="text-[11px] text-slate-400"><span class="text-red-500 font-bold">*</span> Nama wajib diisi</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button type="reset" id="btn-reset" class="btn-tw btn-light justify-center">
                                    <i class="bi bi-arrow-repeat mr-1"></i> Reset
                                </button>
                                <a href="<?= site_url('dataguru') ?>" class="btn-tw btn-outline-danger justify-center no-underline">
                                    <i class="bi bi-x-lg mr-1"></i> Batal
                                </a>
                                <button type="submit" id="submit-profil" class="btn-tw btn-primary justify-center col-span-2 sm:col-span-1">
                                    <i class="bi bi-save mr-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══ Modal Foto ═══ -->
<div id="editFotoModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="modal-header-tw">
                <div>
                    <h5 class="font-bold text-slate-800">Ganti Foto Profil</h5>
                    <p class="text-[11px] text-slate-400 mb-0">JPG/PNG · maksimal 2MB</p>
                </div>
                <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
            </div>
            <div class="modal-body-tw">
                <form id="foto-form" enctype="multipart/form-data">
                    <div id="dz-foto" class="dz p-5 text-center" role="button" tabindex="0" aria-label="Pilih atau seret foto ke sini">
                        <img id="preview-foto" src="<?= esc($foto, 'attr') ?>"
                            class="rounded-2xl border shadow-sm mb-3 mx-auto block"
                            style="width: 180px; height: 180px; object-fit: cover;" alt="Preview foto">
                        <p class="text-xs font-bold text-slate-600 mb-0.5">Seret &amp; letakkan foto di sini, atau <span class="text-blue-600 underline">klik untuk memilih</span></p>
                        <p id="dz-filename" class="hidden mt-1.5 font-mono text-[11px] font-bold text-blue-700 break-all"></p>
                        <input type="file" id="foto-input" name="foto" class="hidden" accept="image/jpeg,image/png">
                    </div>
                </form>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="button" class="btn-tw btn-primary" id="btn-save-foto"><i class="bi bi-cloud-arrow-up mr-1"></i>Simpan Foto</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal Login ═══ -->
<div id="editLoginModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <form id="login-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id_guru" value="<?= (int) $id_active ?>">
            <div class="bg-white rounded-2xl overflow-hidden">
                <div class="modal-header-tw">
                    <div>
                        <h5 class="font-bold text-slate-800">Edit Akun Login</h5>
                        <p class="text-[11px] text-slate-400 mb-0">Kosongkan password bila hanya ganti username</p>
                    </div>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
                </div>
                <div class="modal-body-tw space-y-4">
                    <div>
                        <label class="form-label-tw req" for="login-username">Username</label>
                        <input type="text" class="form-control-tw font-mono" id="login-username" name="username" value="<?= esc($guru->username ?? '') ?>" required minlength="4" maxlength="30" autocomplete="username">
                        <p class="field-msg" data-msg-for="login-username"></p>
                    </div>
                    <div>
                        <label class="form-label-tw" for="login-new">Password Baru <span class="text-slate-400 font-normal">(opsional, min. 6)</span></label>
                        <div class="relative">
                            <input type="password" class="form-control-tw pr-11" id="login-new" name="new" minlength="6" maxlength="72" autocomplete="new-password"
                                placeholder="Kosongkan jika tidak ingin ganti">
                            <button type="button" id="btn-toggle-pw" class="absolute inset-y-0 right-0 px-3.5 text-slate-400 hover:text-slate-600 bg-transparent border-none cursor-pointer" title="Tampilkan/sembunyikan" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="pw-bar mt-2"><div id="pw-strength"></div></div>
                    </div>
                    <div>
                        <label class="form-label-tw" for="login-new-confirm">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control-tw" id="login-new-confirm" name="new_confirm" maxlength="72" autocomplete="new-password"
                            placeholder="Ulangi password baru">
                        <p class="field-msg" data-msg-for="login-new-confirm"></p>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-warning" id="btn-save-login"><i class="bi bi-key mr-1"></i>Update Login</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Modal TTD ═══ -->
<div id="modal-signature" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="modal-header-tw">
                <div>
                    <h5 class="font-bold text-slate-800">Buat Tanda Tangan</h5>
                    <p class="text-[11px] text-slate-400 mb-0">Gambar dengan mouse / jari di kanvas</p>
                </div>
                <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
            </div>
            <div class="modal-body-tw">
                <canvas id="sig-canvas"></canvas>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" id="sig-clear"><i class="bi bi-eraser mr-1"></i>Bersihkan</button>
                <button type="button" class="btn-tw btn-primary" id="sig-submit"><i class="bi bi-check-lg mr-1"></i>Simpan TTD</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    const idGuru = '<?= (int) $id_active ?>';
    const SAVE_HTML = '<i class="bi bi-save mr-1"></i> Simpan';

    function csrf() { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }
    function spin() { return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>'; }
    function spinBtn($btn, busy, orig) {
        if (busy) {
            $btn.prop('disabled', true).html(spin() + ' Menyimpan...');
        } else {
            $btn.prop('disabled', false).html(orig);
        }
    }

    // ── Dirty tracking ──
    const $form = $('#formguru');
    const initial = $form.serialize();
    let isDirty = false;
    const setDirty = (d) => {
        isDirty = d;
        $('#dirty-dot').toggleClass('on', d);
        $('#dirty-text').text(d ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan');
    };
    $form.on('input change', function () {
        setDirty($form.serialize() !== initial);
        const nm = $('#f-nama_guru').val();
        if (nm) $('#profile-nama').text(nm);
    });
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) { e.preventDefault(); e.returnValue = ''; }
    });
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            $form.trigger('submit');
        }
    });
    $('#btn-save-top').on('click', function () { $form.trigger('submit'); });
    $('#btn-reset').on('click', function (e) {
        if (isDirty && !confirm('Batalkan semua perubahan yang belum disimpan?')) e.preventDefault();
        else setTimeout(() => { setDirty(false); clearErrors(); }, 50);
    });

    // ── Validasi client (selaras aturan server) ──
    const TAB_OF = { 'f-nama_guru':'profile-pane','f-email':'profile-pane','f-nip':'profile-pane','f-wa_number':'profile-pane','f-no_ktp':'address-pane','f-tgl_lahir':'address-pane','f-kode_pos':'address-pane','f-telegram_id':'profile-pane' };
    function setErr(id, msg) {
        $('#' + id).addClass('field-err');
        const $m = $('[data-msg-for="' + id + '"]');
        if (msg) { $m.text(msg).addClass('show'); } else { $m.text('').removeClass('show'); }
        const tab = TAB_OF[id];
        if (tab && msg) $('[data-err-for="' + tab + '"]').closest('.tab-pill').addClass('has-error');
    }
    function clearErrors() {
        $('.field-err').removeClass('field-err');
        $('.field-msg').text('').removeClass('show');
        $('.tab-pill').removeClass('has-error');
        $('#form-alert').addClass('hidden').html('');
    }
    $form.on('input change', 'input,select,textarea', function () { setErr(this.id, ''); });
    // Dulu dipakai untuk f-no_hp. Field itu sudah diganti No. WhatsApp
    // dan divalidasi terpisah di bawah, jadi helper ini tak lagi dipakai.
    function validateClient() {
        clearErrors();
        const errs = [];
        if (($('#f-nama_guru').val() || '').trim() === '') { setErr('f-nama_guru', 'Nama guru wajib diisi.'); errs.push('Nama guru wajib diisi'); }
        const em = ($('#f-email').val() || '').trim();
        if (em !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { setErr('f-email', 'Format email tidak valid.'); errs.push('Email tidak valid'); }
        const nip = ($('#f-nip').val() || '').trim();
        if (nip !== '' && !/^[0-9]+$/.test(nip)) { setErr('f-nip', 'NIP/NUPTK harus angka.'); errs.push('NIP tidak valid'); }
                // Nomor WA notifikasi. Aturan longgar di sisi client (digit dan
        // tanda baca) karena normalisasi sebenarnya dilakukan server lewat
        // WhatsAppLink — 08, +62, dan 62 semuanya sah.
        const wa = ($('#f-wa_number').val() || '').trim();
        if (wa !== '' && !/^[0-9+\-\s()]{7,20}$/.test(wa)) { setErr('f-wa_number', 'Nomor WhatsApp tidak valid.'); errs.push('No. WhatsApp tidak valid'); }        const nik = ($('#f-no_ktp').val() || '').trim();
        if (nik !== '' && !/^[0-9]{8,20}$/.test(nik)) { setErr('f-no_ktp', 'NIK 8–20 digit angka.'); errs.push('NIK tidak valid'); }
        const tgl = $('#f-tgl_lahir').val();
        if (tgl !== '' && isNaN(Date.parse(tgl))) { setErr('f-tgl_lahir', 'Tanggal lahir tidak valid.'); errs.push('Tanggal lahir tidak valid'); }
        const kp = ($('#f-kode_pos').val() || '').trim();
        if (kp !== '' && !/^[0-9]{3,10}$/.test(kp)) { setErr('f-kode_pos', 'Kode pos harus angka.'); errs.push('Kode pos tidak valid'); }
        const tg = ($('#f-telegram_id').val() || '').replace(/[\s@]/g, '').trim();
        if (tg !== '' && !/^-?\d{1,20}$/.test(tg)) { setErr('f-telegram_id', 'ID Telegram harus angka saja (mis. 123456789).'); errs.push('ID Telegram tidak valid'); }
        return errs;
    }
    function gotoTab(id) {
        const $btn = $('[data-ui-target="#' + id + '"]');
        if ($btn.length) $btn.trigger('click');
    }

    // ── Submit profil ──
    $form.on('submit', function (e) {
        e.preventDefault();
        const errs = validateClient();
        if (errs.length) {
            const firstTab = TAB_OF[$('.field-err').first().attr('id')] || 'profile-pane';
            gotoTab(firstTab);
            $('#form-alert').removeClass('hidden').html('<b>Periksa kembali:</b><br>• ' + errs.join('<br>• '));
            $('#form-alert')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        const $btn = $('#submit-profil');
        const orig = SAVE_HTML;
        spinBtn($btn, true);
        $('#btn-save-top').prop('disabled', true);
        const formData = new FormData($form[0]);
        formData.set(CSRF_NAME, CSRF_HASH);
        $.ajax({
            url: '<?= site_url("dataguru/save") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: (res) => {
                spinBtn($btn, false, orig);
                $('#btn-save-top').prop('disabled', false);
                if (res && res.status) {
                    setDirty(false);

                    // Pesan utama selalu tentang profilnya. Kanal
                    // notifikasi hanya disebut kalau ADA yang benar-benar
                    // berubah — kalau tidak, admin akan melihat pesan "Tautan
                    // Telegram dilepas" setiap kali menyimpan email.
                    const notif = Array.isArray(res.notif) ? res.notif.filter(Boolean) : [];
                    const adaGagal = notif.some(c => /gagal|tidak bisa|ditolak|tidak tersedia/i.test(c));

                    if (notif.length === 0) {
                        Swal.fire('Berhasil', 'Data guru berhasil disimpan.', 'success')
                            .then(() => window.location.reload());
                    } else if (adaGagal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data guru disimpan, sebagian perlu perhatian',
                            html: notif.join('<br>')
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Data guru berhasil disimpan',
                            html: '<b>Perubahan kanal notifikasi:</b><br>' + notif.join('<br>')
                        }).then(() => window.location.reload());
                    }
                } else if (res && res.message) {
                    $('#form-alert').removeClass('hidden').html('<b>Gagal menyimpan:</b><br>' + res.message);
                    Swal.fire('Gagal', res.message, 'error');
                } else {
                    Swal.fire('Gagal', 'Periksa kembali inputan Anda', 'error');
                }
            },
            error: () => { spinBtn($btn, false, orig); $('#btn-save-top').prop('disabled', false); Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'); }
        });
    });

    // ── Foto: dropzone ringan (pengganti dropify) ──
    const dz = $('#dz-foto'), fi = $('#foto-input');
    dz.on('click keydown', function (e) {
        if (e.type === 'click' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fi.trigger('click'); }
    });
    dz.on('dragover dragenter', function (e) { e.preventDefault(); dz.addClass('over'); });
    dz.on('dragleave drop', function (e) { e.preventDefault(); dz.removeClass('over'); });
    dz.on('drop', function (e) {
        const files = (e.originalEvent.dataTransfer || {}).files;
        if (files && files[0]) { fi[0].files = files; fi.trigger('change'); }
    });
    fi.on('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { Swal.fire('Terlalu Besar', 'Ukuran foto maksimal 2MB.', 'warning'); fi.val(''); return; }
        if (!/jpe?g|png/i.test(file.type + ' ' + file.name)) { Swal.fire('Format Salah', 'Foto harus JPG/PNG.', 'warning'); fi.val(''); return; }
        $('#dz-filename').removeClass('hidden').text(file.name + ' (' + Math.round(file.size / 1024) + ' KB)');
        const reader = new FileReader();
        reader.onload = function (ev) { $('#preview-foto').attr('src', ev.target.result); };
        reader.readAsDataURL(file);
    });

    // Photo Upload
    $('#btn-save-foto').on('click', function () {
        if (!fi[0].files[0]) { Swal.fire('Peringatan', 'Pilih file foto terlebih dahulu', 'warning'); return; }
        const $btn = $(this);
        const orig = $btn.html();
        const formData = new FormData($('#foto-form')[0]);
        formData.set(CSRF_NAME, CSRF_HASH);
        spinBtn($btn, true);
        $.ajax({
            url: '<?= site_url("dataguru/uploadFile/") ?>' + idGuru,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: (res) => {
                spinBtn($btn, false, orig);
                if (res && res.status) {
                    $('#foto-guru').attr('src', res.src);
                    AdminUI.modal.close('#editFotoModal');
                    Swal.fire('Berhasil', 'Foto berhasil diperbarui', 'success');
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal mengunggah foto.', 'error');
                }
            },
            error: () => { spinBtn($btn, false, orig); Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'); }
        });
    });

    function deleteImage() {
         Swal.fire({
            title: 'Hapus Foto?',
            text: 'Foto profil akan dihapus dan kembali ke default',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= site_url("dataguru/delete_foto/") ?>' + idGuru,
                    type: 'POST',
                    data: csrf(),
                    dataType: 'json',
                    success: function (res) {
                         if (res && res.status) {
                            $('#foto-guru').attr('src', '<?= base_url("assets/img/guru.png") ?>');
                            Swal.fire('Terhapus!', 'Foto profil berhasil dihapus.', 'success');
                         } else {
                            Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus foto.', 'error');
                         }
                    },
                    error: function() {
                         Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            }
        });
    }
    window.deleteImage = deleteImage;
    $('#btn-delete-foto').on('click', deleteImage);

    // API key toggle
    $('#btn-toggle-apikey').on('click', function () {
        const $p = $('#f-api_key');
        const show = $p.attr('type') === 'password';
        $p.attr('type', show ? 'text' : 'password');
        $(this).html(show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>');
    });

    // ── Login: toggle + strength + konfirmasi ──
    $('#btn-toggle-pw').on('click', function () {
        const $p = $('#login-new');
        const show = $p.attr('type') === 'password';
        $p.attr('type', show ? 'text' : 'password');
        $(this).html(show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>');
    });
    $('#login-new').on('input', function () {
        const v = $(this).val() || '';
        let score = 0;
        if (v.length >= 6) score += 34;
        if (v.length >= 10) score += 22;
        if (/[0-9]/.test(v) && /[a-zA-Z]/.test(v)) score += 22;
        if (/[^a-zA-Z0-9]/.test(v)) score += 22;
        score = Math.min(score, 100);
        const $bar = $('#pw-strength');
        $bar.css('width', score + '%');
        $bar.css('background', score < 34 ? '#ef4444' : (score < 70 ? '#f59e0b' : '#10b981'));
    });

    // Login Update
    $('#login-form').on('submit', function (e) {
        e.preventDefault();
        const uname = ($('#login-username').val() || '').trim();
        const pwd = $('#login-new').val() || '';
        const pwd2 = $('#login-new-confirm').val() || '';
        if (uname.length < 4) {
            $('[data-msg-for="login-username"]').text('Username minimal 4 karakter.').addClass('show');
            $('#login-username').addClass('field-err');
            return;
        }
        if (pwd !== '' && pwd.length < 6) { Swal.fire('Perhatian', 'Password baru minimal 6 karakter.', 'warning'); return; }
        if (pwd !== '' && pwd !== pwd2) {
            $('[data-msg-for="login-new-confirm"]').text('Konfirmasi tidak sama dengan password baru.').addClass('show');
            $('#login-new-confirm').addClass('field-err');
            return;
        }
        const $btn = $('#btn-save-login');
        const orig = $btn.html();
        spinBtn($btn, true);
        $.ajax({
            url: '<?= site_url("dataguru/editLogin") ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: (res) => {
                spinBtn($btn, false, orig);
                if (res && res.status) {
                    AdminUI.modal.close('#editLoginModal');
                    $('#login-new, #login-new-confirm').val('');
                    $('#pw-strength').css('width', '0');
                    Swal.fire('Berhasil', res.text || 'Login diperbarui.', 'success');
                } else if (res && res.errors) {
                    Swal.fire('Gagal', Object.values(res.errors).join('<br>'), 'error');
                } else {
                    Swal.fire('Gagal', (res && (res.text || res.message)) || 'Gagal memperbarui login.', 'error');
                }
            },
            error: () => { spinBtn($btn, false, orig); Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'); }
        });
    });

    // Signature Logic (pointer events = mouse + sentuh)
    const canvas = document.getElementById('sig-canvas');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let prevX = 0, prevY = 0;

    function sigPos(e) {
        const r = canvas.getBoundingClientRect();
        return [e.clientX - r.left, e.clientY - r.top];
    }
    // Samakan resolusi kanvas dengan ukuran tampil agar goresan presisi
    function fitSigCanvas() {
        const r = canvas.getBoundingClientRect();
        const w = Math.max(200, Math.floor(r.width));
        const h = Math.max(120, Math.floor(r.height || 200));
        if (canvas.width !== w || canvas.height !== h) {
            canvas.width = w;
            canvas.height = h;
        }
    }

    $('#btn-create-ttd').on('click', () => {
        AdminUI.modal.open('#modal-signature');
        setTimeout(fitSigCanvas, 60);
    });

    canvas.addEventListener('pointerdown', (e) => {
        drawing = true;
        canvas.setPointerCapture && canvas.setPointerCapture(e.pointerId);
        [prevX, prevY] = sigPos(e);
    });
    canvas.addEventListener('pointermove', (e) => {
        if (!drawing) return;
        e.preventDefault();
        const [x, y] = sigPos(e);
        ctx.beginPath();
        ctx.moveTo(prevX, prevY);
        ctx.lineTo(x, y);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.stroke();
        [prevX, prevY] = [x, y];
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(ev =>
        canvas.addEventListener(ev, () => { drawing = false; })
    );

    $('#sig-clear').on('click', () => ctx.clearRect(0, 0, canvas.width, canvas.height));

    $('#sig-submit').on('click', function () {
        const $btn = $(this);
        const orig = $btn.html();
        const dataUrl = canvas.toDataURL('image/png');
        const payload = csrf();
        payload.id_guru = idGuru;
        payload.image = dataUrl;
        spinBtn($btn, true);
        $.ajax({
            url: '<?= site_url("dataguru/save_signature") ?>',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: (res) => {
                spinBtn($btn, false, orig);
                if (res && res.status) {
                    $('#img-ttd-preview').attr('src', res.src).removeClass('hidden').addClass('block');
                    $('#ttd-placeholder').addClass('hidden').removeClass('flex');
                    $('#btn-delete-ttd').removeClass('hidden');
                    AdminUI.modal.close('#modal-signature');
                    Swal.fire('Berhasil', res.message || 'TTD tersimpan.', 'success');
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan TTD.', 'error');
                }
            },
            error: () => { spinBtn($btn, false, orig); Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'); }
        });
    });

    $('#btn-delete-ttd').on('click', () => {
        Swal.fire({
            title: 'Hapus Tanda Tangan?', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            const payload = csrf();
            payload.id_guru = idGuru;
            $.ajax({
                url: '<?= site_url("dataguru/delete_ttd") ?>',
                type: 'POST',
                data: payload,
                dataType: 'json',
                success: (res) => {
                    if (res && res.status) {
                        $('#img-ttd-preview').addClass('hidden').removeClass('block');
                        $('#ttd-placeholder').removeClass('hidden').addClass('flex');
                        $('#btn-delete-ttd').addClass('hidden');
                        Swal.fire('Terhapus', res.message || 'TTD dihapus.', 'success');
                    } else {
                        Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus TTD.', 'error');
                    }
                },
                error: () => Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error')
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
