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
    @media (max-width: 1023px) { .profile-sticky { position: static !important; top: auto !important; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$fotoSiswa = trim((string) ($siswa->foto ?? ''));
$defaultFoto = ($siswa->jenis_kelamin ?? '') == 'P' ? 'assets/img/siswa-p.png' : 'assets/img/siswa-m.png';
$displayFoto = ($fotoSiswa !== '' && strpos($fotoSiswa, '..') === false && file_exists(FCPATH . ltrim($fotoSiswa, '/')))
    ? base_url(ltrim($fotoSiswa, '/'))
    : base_url($defaultFoto);

// Status akademik dinamis (1=Aktif, 2=Lulus, 3=Pindah, 4=Keluar)
$statusVal = (string) ($siswa->status ?? '1');
$statusMap = [
    '1' => ['Aktif', 'badge-success'],
    '2' => ['Lulus', 'badge-primary'],
    '3' => ['Pindah', 'badge-warning'],
    '4' => ['Keluar', 'badge-danger'],
];
$statusLbl = $statusMap[$statusVal][0] ?? 'Aktif';
$statusCls = $statusMap[$statusVal][1] ?? 'badge-success';

// Peta field by name agar bisa dirender per seksi tanpa ubah controller
$mapData = []; foreach (($input_data ?? []) as $it) { $mapData[$it->name] = $it; }
$mapBio  = []; foreach (($input_bio ?? []) as $it) { $mapBio[$it->name] = $it; }
$mapOrtu = []; foreach (($input_ortu ?? []) as $it) { $mapOrtu[$it->name] = $it; }
$mapWali = []; foreach (($input_wali ?? []) as $it) { $mapWali[$it->name] = $it; }
$v = fn($m, $k) => esc($m[$k]->value ?? '');
$ic = fn($m, $k, $fb) => esc($m[$k]->icon ?? $fb);

// Kelengkapan data (progress)
$checkKeys = ['nama','nis','nisn','jenis_kelamin','tempat_lahir','tanggal_lahir','agama','alamat','hp','nama_ayah','nama_ibu'];
$filled = 0;
foreach ($checkKeys as $ck) {
    $vv = $mapData[$ck]->value ?? $mapBio[$ck]->value ?? $mapOrtu[$ck]->value ?? null;
    if (trim((string) $vv) !== '') $filled++;
}
$pct = (int) round($filled / count($checkKeys) * 100);

// Tanggal lahir → normalisasi Y-m-d untuk input date
$tglLahir = trim((string) ($mapBio['tanggal_lahir']->value ?? ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir) || $tglLahir === '0000-00-00') $tglLahir = '';
?>

<div class="fade-in-up p-4 md:p-6 space-y-4 w-full max-w-none">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold" aria-label="Breadcrumb">
        <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 no-underline">Dashboard</a>
        <span>/</span>
        <a href="<?= site_url('datasiswa') ?>" class="hover:text-blue-600 no-underline">Data Siswa</a>
        <span>/</span>
        <span class="text-slate-600">Edit</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[11px] font-bold">
                    <i class="bi bi-pencil-square"></i> Edit Data Siswa
                </span>
                <span class="badge-tw <?= $statusCls ?>"><?= $statusLbl ?></span>
                <?php if (!empty($kelas_siswa_nama)): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-bold">
                        <i class="bi bi-mortarboard"></i> <?= esc($kelas_siswa_nama) ?>
                    </span>
                <?php endif; ?>
            </div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight truncate">Edit Siswa — <?= esc($siswa->nama ?? '-') ?></h1>
            <p class="text-xs text-slate-500 mt-1 font-mono">NIS <?= esc($siswa->nis ?? '-') ?> · NISN <?= esc($siswa->nisn ?? '-') ?> · TP <?= esc($tp_active->tahun ?? '-') ?> <?= esc($smt_active->smt ?? '') ?></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="<?= site_url('datasiswa') ?>" class="btn-tw btn-light">
                <i class="bi bi-arrow-left mr-1"></i> Kembali
            </a>
            <button type="button" id="btn-save-top" class="btn-tw btn-primary">
                <i class="bi bi-save mr-1"></i> Simpan
            </button>
        </div>
    </div>

    <!-- Inline error alert -->
    <div id="form-alert" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700" role="alert"></div>

    <div class="grid grid-cols-1 lg:grid-cols-[300px_minmax(0,1fr)] gap-4 items-start w-full">
        <!-- ═══ Kartu profil (sticky) ═══ -->
        <aside class="w-full min-w-0 profile-sticky lg:sticky lg:top-4 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="flex items-center gap-2 p-4 border-b border-slate-100">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-person-badge text-sm"></i>
                    </span>
                    <h2 class="text-sm font-extrabold text-slate-900">Detail Profil</h2>
                </div>
                <div class="p-5">
                    <div class="mb-5 text-center">
                        <div class="relative inline-block">
                            <img src="<?= esc($displayFoto, 'attr') ?>"
                                class="rounded-full shadow-sm ring-4 ring-slate-100"
                                style="width: 132px; height: 132px; object-fit: cover;"
                                alt="Foto <?= esc($siswa->nama ?? 'siswa') ?>"
                                onerror="this.onerror=null;this.src='<?= base_url('assets/img/siswa.png') ?>'">
                            <span class="absolute bottom-2 right-2 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white" title="Aktif"></span>
                            <button type="button"
                                class="btn-tw btn-tw-sm btn-primary !rounded-full !p-0 absolute bottom-0 right-0 shadow-md"
                                style="width:32px;height:32px;"
                                data-ui-toggle="modal" data-ui-target="#editFotoModal"
                                title="Ganti Foto" aria-label="Ganti foto">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <h4 id="profile-nama" class="mt-3 font-bold text-slate-900"><?= esc($siswa->nama ?? '-') ?></h4>
                        <p class="text-slate-400 text-xs mt-0.5 font-mono">NIS <?= esc($siswa->nis ?? '-') ?> · NISN <?= esc($siswa->nisn ?? '-') ?></p>
                        <?php if (!empty($siswa->username)): ?>
                            <p class="mt-1.5"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[11px] font-mono"><i class="bi bi-person-circle"></i><?= esc($siswa->username) ?></span></p>
                        <?php endif; ?>
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
                            <dt class="text-slate-400">Status</dt>
                            <dd><span class="badge-tw <?= $statusCls ?>"><?= $statusLbl ?></span></dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Kelas saat ini</dt>
                            <dd class="font-bold text-slate-700 text-right"><?= !empty($kelas_siswa_nama) ? esc($kelas_siswa_nama) : '<span class="text-amber-600">Belum ada kelas</span>' ?></dd>
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
        </aside>

        <!-- ═══ Form ═══ -->
        <div class="min-w-0 w-full">
            <form id="form-siswa" action="<?= site_url('datasiswa/updateData') ?>" method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id_siswa" value="<?= (int) $siswa->id_siswa ?>">
                <input type="hidden" name="method" value="edit">

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs w-full">
                    <!-- Tab bar -->
                    <div class="p-3 border-b border-slate-100 lg:sticky lg:top-0 bg-white/95 backdrop-blur rounded-t-3xl z-20">
                        <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl overflow-x-auto" id="editTab" role="tablist" aria-label="Bagian data siswa">
                            <button type="button" class="tab-pill active flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap" id="main-tab" data-ui-toggle="tab" data-ui-target="#datasiswa" role="tab" aria-selected="true" aria-controls="datasiswa">
                                <i class="bi bi-person"></i> Siswa <span class="tab-err" data-err-for="datasiswa">!</span>
                            </button>
                            <button type="button" class="tab-pill flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-slate-500 whitespace-nowrap" id="detail-tab" data-ui-toggle="tab" data-ui-target="#biosiswa" role="tab" aria-selected="false" aria-controls="biosiswa">
                                <i class="bi bi-card-list"></i> Detail <span class="tab-err" data-err-for="biosiswa">!</span>
                            </button>
                            <button type="button" class="tab-pill flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-slate-500 whitespace-nowrap" id="family-tab" data-ui-toggle="tab" data-ui-target="#ortusiswa" role="tab" aria-selected="false" aria-controls="ortusiswa">
                                <i class="bi bi-people"></i> Keluarga <span class="tab-err" data-err-for="ortusiswa">!</span>
                            </button>
                            <button type="button" class="tab-pill flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-slate-500 whitespace-nowrap" id="wali-tab" data-ui-toggle="tab" data-ui-target="#walisiswa" role="tab" aria-selected="false" aria-controls="walisiswa">
                                <i class="bi bi-person-check"></i> Wali <span class="tab-err" data-err-for="walisiswa">!</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="tab-content pt-1 w-full">
                            <!-- ── TAB: SISWA ── -->
                            <div class="tab-pane show active" id="datasiswa" role="tabpanel" aria-labelledby="main-tab">
                                <div class="mb-4 grid grid-cols-1 md:grid-cols-12 gap-2 items-center rounded-2xl border border-blue-100 bg-blue-50/50 p-3">
                                    <label for="f-id_kelas" class="md:col-span-3 form-label-tw md:mb-0 font-bold text-blue-800">Kelas <span class="font-normal">(TP <?= esc($tp_active->tahun ?? '-') ?>)</span></label>
                                    <div class="md:col-span-9">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-sm pointer-events-none"><i class="bi bi-mortarboard"></i></span>
                                            <select id="f-id_kelas" class="form-select-tw pl-9 bg-white" name="id_kelas">
                                                <option value="">— Belum ada kelas —</option>
                                                <?php foreach (($kelas_list ?? []) as $kls): ?>
                                                    <option value="<?= (int) $kls->id_kelas ?>" <?= isset($kelas_siswa_id) && (int) $kelas_siswa_id === (int) $kls->id_kelas ? 'selected' : '' ?>>
                                                        <?= esc($kls->nama_kelas ?? ('Kelas #' . $kls->id_kelas)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <p class="text-[11px] text-blue-500/80 mt-1 mb-0">Pindah kelas hanya berlaku untuk TP/Semester aktif. Kosongkan untuk keluarkan dari kelas.</p>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-blue-50 text-blue-600"><i class="bi bi-person"></i></span>
                                    <div><h3>Data Pokok</h3><p>Identitas utama — wajib diisi</p></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="f-nama" class="form-label-tw req">Nama Lengkap</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person"></i></span>
                                            <input type="text" id="f-nama" name="nama" value="<?= $v($mapData, 'nama') ?>" class="form-control-tw pl-9" placeholder="Nama lengkap sesuai ijazah" required minlength="3" maxlength="100" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nama"></p>
                                    </div>
                                    <div>
                                        <label for="f-nis" class="form-label-tw req">NIS</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" id="f-nis" name="nis" inputmode="numeric" value="<?= $v($mapData, 'nis') ?>" class="form-control-tw pl-9 font-mono" placeholder="Min. 4 digit angka" required maxlength="20" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nis"></p>
                                    </div>
                                    <div>
                                        <label for="f-nisn" class="form-label-tw req">NISN</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" id="f-nisn" name="nisn" inputmode="numeric" value="<?= $v($mapData, 'nisn') ?>" class="form-control-tw pl-9 font-mono" placeholder="10 digit NISN" required maxlength="20" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nisn"></p>
                                    </div>
                                    <div>
                                        <label for="f-jk" class="form-label-tw">Jenis Kelamin</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-gender-ambiguous"></i></span>
                                            <select id="f-jk" class="form-select-tw pl-9" name="jenis_kelamin">
                                                <option value="L" <?= ($mapData['jenis_kelamin']->value ?? '') == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                                <option value="P" <?= ($mapData['jenis_kelamin']->value ?? '') == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-status" class="form-label-tw">Status</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-check"></i></span>
                                            <select id="f-status" class="form-select-tw pl-9" name="status">
                                                <option value="1" <?= ($mapData['status']->value ?? '') == '1' ? 'selected' : '' ?>>AKTIF</option>
                                                <option value="2" <?= ($mapData['status']->value ?? '') == '2' ? 'selected' : '' ?>>LULUS</option>
                                                <option value="3" <?= ($mapData['status']->value ?? '') == '3' ? 'selected' : '' ?>>PINDAH</option>
                                                <option value="4" <?= ($mapData['status']->value ?? '') == '4' ? 'selected' : '' ?>>KELUAR</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-nomor_rfid" class="form-label-tw">Nomor Kartu RFID</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-card-id"></i></span>
                                            <input type="text" id="f-nomor_rfid" name="nomor_rfid" inputmode="text" value="<?= $v($mapData, 'nomor_rfid') ?>" class="form-control-tw pl-9 font-mono text-uppercase" placeholder="04A1B2C3D" maxlength="64" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nomor_rfid"></p>
                                        <p class="text-[11px] text-slate-400 mt-1 mb-0">UID kartu RFID siswa (dicetak pada kartu fisik). Kosongkan bila tidak pakai RFID.</p>
                                    </div>
                                    <div>
                                        <label for="f-fingerprint_uid" class="form-label-tw">UserID Mesin Sidik Jari</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-fingerprint"></i></span>
                                            <input type="text" id="f-fingerprint_uid" name="fingerprint_uid" inputmode="text" value="<?= $v($mapData, 'fingerprint_uid') ?>" class="form-control-tw pl-9 font-mono" placeholder="1001" maxlength="64" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-fingerprint_uid"></p>
                                        <p class="text-[11px] text-slate-400 mt-1 mb-0">UserID yang terdaftar di mesin sidik jari GEISA GX330. Kosongkan bila tidak pakai fingerprint.</p>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-emerald-50 text-emerald-600"><i class="bi bi-mortarboard"></i></span>
                                    <div><h3>Riwayat Akademik</h3><p>Asal sekolah &amp; penerimaan</p></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="f-kelas_awal" class="form-label-tw">Diterima di Kelas</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-mortarboard"></i></span>
                                            <select id="f-kelas_awal" class="form-select-tw pl-9" name="kelas_awal">
                                                <?php
                                                $opsis = ($setting->jenjang == 1) ? [1,2,3,4,5,6] : (($setting->jenjang == 2) ? [7,8,9] : [10,11,12]);
                                                foreach ($opsis as $kls): ?>
                                                    <option value="<?= $kls ?>" <?= ($mapData['kelas_awal']->value ?? '') == $kls ? 'selected' : '' ?>>Kelas <?= $kls ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-tahun_masuk" class="form-label-tw">Tanggal Diterima</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-calendar-date"></i></span>
                                            <input type="date" id="f-tahun_masuk" name="tahun_masuk" value="<?= $v($mapData, 'tahun_masuk') ?>" class="form-control-tw pl-9">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-tahun_masuk"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="f-sekolah_asal" class="form-label-tw">Sekolah Asal</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-3.5 text-slate-400 text-sm pointer-events-none"><i class="bi bi-bank"></i></span>
                                            <input type="text" id="f-sekolah_asal" name="sekolah_asal" value="<?= $v($mapData, 'sekolah_asal') ?>" class="form-control-tw pl-9" placeholder="Contoh: SDN 1 Kupang" maxlength="100" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── TAB: DETAIL ── -->
                            <div class="tab-pane" id="biosiswa" role="tabpanel" aria-labelledby="detail-tab">
                                <div class="sec-title">
                                    <span class="sec-ic bg-violet-50 text-violet-600"><i class="bi bi-cake2"></i></span>
                                    <div><h3>Kelahiran &amp; Identitas</h3><p>Tempat, tanggal lahir &amp; agama</p></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="f-tempat_lahir" class="form-label-tw">Tempat Lahir</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" id="f-tempat_lahir" name="tempat_lahir" value="<?= $v($mapBio, 'tempat_lahir') ?>" class="form-control-tw pl-9" placeholder="Kota kelahiran" maxlength="50" autocomplete="off">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-tanggal_lahir" class="form-label-tw">Tanggal Lahir</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-calendar-date"></i></span>
                                            <input type="date" id="f-tanggal_lahir" name="tanggal_lahir" value="<?= esc($tglLahir, 'attr') ?>" class="form-control-tw pl-9" max="<?= date('Y-m-d') ?>">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-tanggal_lahir"></p>
                                    </div>
                                    <div>
                                        <label for="f-agama" class="form-label-tw">Agama</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-star"></i></span>
                                            <select id="f-agama" class="form-select-tw pl-9" name="agama">
                                                <option value="">— Pilih Agama —</option>
                                                <?php foreach (["Islam","Katolik","Protestan","Hindu","Budha","Konghucu"] as $agm): ?>
                                                    <option value="<?= $agm ?>" <?= ($mapBio['agama']->value ?? '') == $agm ? 'selected' : '' ?>><?= $agm ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-hp" class="form-label-tw">No. HP Siswa</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-telephone"></i></span>
                                            <input type="tel" id="f-hp" name="hp" inputmode="tel" value="<?= $v($mapBio, 'hp') ?>" class="form-control-tw pl-9 font-mono" placeholder="08xxxxxxxxxx" maxlength="20" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-hp"></p>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-amber-50 text-amber-600"><i class="bi bi-house-door"></i></span>
                                    <div><h3>Alamat Domisili</h3><p>Alamat lengkap tempat tinggal</p></div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="f-alamat" class="form-label-tw">Alamat (Jalan / Dusun)</label>
                                        <textarea id="f-alamat" name="alamat" rows="2" class="form-control-tw" placeholder="Nama jalan, nomor rumah, RT/RW, dusun..."><?= $v($mapBio, 'alamat') ?></textarea>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                    <div>
                                        <label for="f-rt" class="form-label-tw">RT</label>
                                        <input type="text" id="f-rt" name="rt" inputmode="numeric" value="<?= $v($mapBio, 'rt') ?>" class="form-control-tw font-mono text-center" placeholder="001" maxlength="5">
                                    </div>
                                    <div>
                                        <label for="f-rw" class="form-label-tw">RW</label>
                                        <input type="text" id="f-rw" name="rw" inputmode="numeric" value="<?= $v($mapBio, 'rw') ?>" class="form-control-tw font-mono text-center" placeholder="002" maxlength="5">
                                    </div>
                                    <div class="col-span-2">
                                        <label for="f-kode_pos" class="form-label-tw">Kode Pos</label>
                                        <input type="text" id="f-kode_pos" name="kode_pos" inputmode="numeric" value="<?= $v($mapBio, 'kode_pos') ?>" class="form-control-tw font-mono" placeholder="85xxx" maxlength="10">
                                    </div>
                                    <div class="col-span-2 md:col-span-2">
                                        <label for="f-kelurahan" class="form-label-tw">Kelurahan / Desa</label>
                                        <input type="text" id="f-kelurahan" name="kelurahan" value="<?= $v($mapBio, 'kelurahan') ?>" class="form-control-tw" placeholder="Kelurahan / desa" maxlength="50">
                                    </div>
                                    <div class="col-span-2 md:col-span-2">
                                        <label for="f-kecamatan" class="form-label-tw">Kecamatan</label>
                                        <input type="text" id="f-kecamatan" name="kecamatan" value="<?= $v($mapBio, 'kecamatan') ?>" class="form-control-tw" placeholder="Kecamatan" maxlength="50">
                                    </div>
                                    <div class="col-span-2 md:col-span-4">
                                        <label for="f-kabupaten" class="form-label-tw">Kabupaten / Kota</label>
                                        <input type="text" id="f-kabupaten" name="kabupaten" value="<?= $v($mapBio, 'kabupaten') ?>" class="form-control-tw" placeholder="Kabupaten / kota" maxlength="50">
                                    </div>
                                </div>
                            </div>

                            <!-- ── TAB: KELUARGA ── -->
                            <div class="tab-pane" id="ortusiswa" role="tabpanel" aria-labelledby="family-tab">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="f-status_keluarga" class="form-label-tw">Status Keluarga</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-people"></i></span>
                                            <select id="f-status_keluarga" class="form-select-tw pl-9" name="status_keluarga">
                                                <option value="1" <?= ($mapOrtu['status_keluarga']->value ?? '') == '1' ? 'selected' : '' ?>>Anak Kandung</option>
                                                <option value="2" <?= ($mapOrtu['status_keluarga']->value ?? '') == '2' ? 'selected' : '' ?>>Anak Tiri</option>
                                                <option value="3" <?= ($mapOrtu['status_keluarga']->value ?? '') == '3' ? 'selected' : '' ?>>Anak Angkat</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-anak_ke" class="form-label-tw">Anak Ke-</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-sort-numeric-down"></i></span>
                                            <input type="number" id="f-anak_ke" name="anak_ke" value="<?= $v($mapOrtu, 'anak_ke') ?>" class="form-control-tw pl-9" placeholder="1" min="1" max="30">
                                        </div>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-blue-50 text-blue-600"><i class="bi bi-person"></i></span>
                                    <div><h3>Data Ayah</h3><p>Identitas &amp; kontak ayah kandung</p></div>
                                </div>
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="f-nama_ayah" class="form-label-tw">Nama Ayah</label>
                                        <input type="text" id="f-nama_ayah" name="nama_ayah" value="<?= $v($mapOrtu, 'nama_ayah') ?>" class="form-control-tw" placeholder="Nama lengkap ayah" maxlength="100" autocomplete="off">
                                    </div>
                                    <div>
                                        <label for="f-pekerjaan_ayah" class="form-label-tw">Pekerjaan Ayah</label>
                                        <input type="text" id="f-pekerjaan_ayah" name="pekerjaan_ayah" value="<?= $v($mapOrtu, 'pekerjaan_ayah') ?>" class="form-control-tw" placeholder="Contoh: Wiraswasta" maxlength="50" autocomplete="off">
                                    </div>
                                    <div>
                                        <label for="f-nohp_ayah" class="form-label-tw">No. HP Ayah</label>
                                        <input type="tel" id="f-nohp_ayah" name="nohp_ayah" inputmode="tel" value="<?= $v($mapOrtu, 'nohp_ayah') ?>" class="form-control-tw font-mono" placeholder="08xxxxxxxxxx" maxlength="20" autocomplete="off">
                                        <p class="field-msg" data-msg-for="f-nohp_ayah"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="f-alamat_ayah" class="form-label-tw">Alamat Ayah <span class="font-normal text-slate-400">(kosongkan jika sama dengan siswa)</span></label>
                                        <textarea id="f-alamat_ayah" name="alamat_ayah" rows="2" class="form-control-tw" placeholder="Alamat ayah..."><?= $v($mapOrtu, 'alamat_ayah') ?></textarea>
                                    </div>
                                </div>

                                <div class="sec-title">
                                    <span class="sec-ic bg-pink-50 text-pink-600"><i class="bi bi-person"></i></span>
                                    <div><h3>Data Ibu</h3><p>Identitas &amp; kontak ibu kandung</p></div>
                                </div>
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="f-nama_ibu" class="form-label-tw">Nama Ibu</label>
                                        <input type="text" id="f-nama_ibu" name="nama_ibu" value="<?= $v($mapOrtu, 'nama_ibu') ?>" class="form-control-tw" placeholder="Nama lengkap ibu" maxlength="100" autocomplete="off">
                                    </div>
                                    <div>
                                        <label for="f-pekerjaan_ibu" class="form-label-tw">Pekerjaan Ibu</label>
                                        <input type="text" id="f-pekerjaan_ibu" name="pekerjaan_ibu" value="<?= $v($mapOrtu, 'pekerjaan_ibu') ?>" class="form-control-tw" placeholder="Contoh: Ibu Rumah Tangga" maxlength="50" autocomplete="off">
                                    </div>
                                    <div>
                                        <label for="f-nohp_ibu" class="form-label-tw">No. HP Ibu</label>
                                        <input type="tel" id="f-nohp_ibu" name="nohp_ibu" inputmode="tel" value="<?= $v($mapOrtu, 'nohp_ibu') ?>" class="form-control-tw font-mono" placeholder="08xxxxxxxxxx" maxlength="20" autocomplete="off">
                                        <p class="field-msg" data-msg-for="f-nohp_ibu"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="f-alamat_ibu" class="form-label-tw">Alamat Ibu <span class="font-normal text-slate-400">(kosongkan jika sama dengan siswa)</span></label>
                                        <textarea id="f-alamat_ibu" name="alamat_ibu" rows="2" class="form-control-tw" placeholder="Alamat ibu..."><?= $v($mapOrtu, 'alamat_ibu') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- ── TAB: WALI ── -->
                            <div class="tab-pane" id="walisiswa" role="tabpanel" aria-labelledby="wali-tab">
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 px-4 py-3 text-xs text-blue-700 mb-4 flex items-start gap-2">
                                    <i class="bi bi-info-circle mt-0.5 shrink-0"></i>
                                    <span><b>Opsional.</b> Isi hanya jika siswa memiliki wali selain orang tua kandung (mis. kakek, paman, atau pengasuh pondok).</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="f-nama_wali" class="form-label-tw">Nama Wali</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-person-check"></i></span>
                                            <input type="text" id="f-nama_wali" name="nama_wali" value="<?= $v($mapWali, 'nama_wali') ?>" class="form-control-tw pl-9" placeholder="Nama lengkap wali" maxlength="100" autocomplete="off">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-pekerjaan_wali" class="form-label-tw">Pekerjaan Wali</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-briefcase"></i></span>
                                            <input type="text" id="f-pekerjaan_wali" name="pekerjaan_wali" value="<?= $v($mapWali, 'pekerjaan_wali') ?>" class="form-control-tw pl-9" placeholder="Pekerjaan wali" maxlength="50" autocomplete="off">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="f-nohp_wali" class="form-label-tw">No. HP Wali</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"><i class="bi bi-telephone"></i></span>
                                            <input type="tel" id="f-nohp_wali" name="nohp_wali" inputmode="tel" value="<?= $v($mapWali, 'nohp_wali') ?>" class="form-control-tw pl-9 font-mono" placeholder="08xxxxxxxxxx" maxlength="20" autocomplete="off">
                                        </div>
                                        <p class="field-msg" data-msg-for="f-nohp_wali"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="f-alamat_wali" class="form-label-tw">Alamat Wali</label>
                                        <textarea id="f-alamat_wali" name="alamat_wali" rows="2" class="form-control-tw" placeholder="Alamat lengkap wali..."><?= $v($mapWali, 'alamat_wali') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky save bar -->
                    <div class="px-4 pb-4">
                        <div class="savebar px-4 py-3 flex flex-wrap items-center gap-2">
                            <span class="flex items-center gap-2 text-xs font-semibold text-slate-500 mr-auto">
                                <span class="dirty-dot" id="dirty-dot"></span>
                                <span id="dirty-text">Belum ada perubahan</span>
                            </span>
                            <button type="reset" id="btn-reset" class="btn-tw btn-light">
                                <i class="bi bi-arrow-repeat mr-1"></i> Reset
                            </button>
                            <a href="<?= site_url('datasiswa') ?>" class="btn-tw btn-outline-danger no-underline">
                                <i class="bi bi-x-lg mr-1"></i> Batal
                            </a>
                            <button type="submit" id="btn-save" class="btn-tw btn-primary">
                                <i class="bi bi-save mr-1"></i> Simpan
                            </button>
                            <button id="btn-back" type="button" class="hidden">Kembali</button>
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
                    <h5 class="font-bold text-slate-800">Update Foto Siswa</h5>
                    <p class="text-[11px] text-slate-400 mb-0">JPG/PNG · maksimal 2MB · tampil lingkaran di profil</p>
                </div>
                <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
            </div>
            <div class="modal-body-tw">
                <form id="form-foto" enctype="multipart/form-data">
                    <div id="dz-foto" class="dz p-5 text-center" role="button" tabindex="0" aria-label="Pilih atau seret foto ke sini">
                        <img id="preview-foto" src="<?= esc($displayFoto, 'attr') ?>"
                            class="rounded-2xl border shadow-sm mb-3 mx-auto block"
                            style="width: 180px; height: 180px; object-fit: cover;" alt="Preview foto">
                        <p class="text-xs font-bold text-slate-600 mb-0.5">Seret &amp; letakkan foto di sini, atau <span class="text-blue-600 underline">klik untuk memilih</span></p>
                        <p id="dz-filename" class="hidden mt-1.5 font-mono text-[11px] font-bold text-blue-700 break-all"></p>
                        <input type="file" id="input-foto" name="foto" class="hidden" accept="image/jpeg,image/png">
                    </div>
                </form>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="button" id="btn-upload-foto" class="btn-tw btn-primary"><i class="bi bi-cloud-arrow-up mr-1"></i>Upload Foto</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal Login ═══ -->
<div id="editLoginModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <form id="form-login" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id_siswa" value="<?= (int) $siswa->id_siswa ?>">
            <div class="bg-white rounded-2xl overflow-hidden">
                <div class="modal-header-tw">
                    <div>
                        <h5 class="font-bold text-slate-800">Update Akun Siswa</h5>
                        <p class="text-[11px] text-slate-400 mb-0">Username terkunci · hanya password yang diubah</p>
                    </div>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
                </div>
                <div class="modal-body-tw space-y-4">
                    <div>
                        <label class="form-label-tw">Username</label>
                        <input type="text" class="form-control-tw font-mono bg-slate-50" value="<?= esc($siswa->username ?? '') ?>" readonly disabled>
                    </div>
                    <div>
                        <label class="form-label-tw req" for="login-password">Password Baru</label>
                        <div class="relative">
                            <input type="password" id="login-password" name="password" minlength="6" maxlength="72" required class="form-control-tw pr-11" placeholder="Minimal 6 karakter" autocomplete="new-password">
                            <button type="button" id="btn-toggle-pw" class="absolute inset-y-0 right-0 px-3.5 text-slate-400 hover:text-slate-600 bg-transparent border-none cursor-pointer" title="Tampilkan/sembunyikan" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="pw-bar mt-2"><div id="pw-strength"></div></div>
                        <ul class="mt-2 space-y-1 text-[11px] text-slate-500">
                            <li id="pw-req-len"><i class="bi bi-circle mr-1"></i>Minimal 6 karakter</li>
                            <li id="pw-req-mix"><i class="bi bi-circle mr-1"></i>Lebih kuat jika campur huruf + angka</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-warning" id="btn-save-login"><i class="bi bi-key mr-1"></i>Update Password</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    const SISWA_ID = '<?= (int) $siswa->id_siswa ?>';
    const SAVE_HTML = '<i class="bi bi-save mr-1"></i> Simpan';
    const spin = () => '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>';
    const $form = $('#form-siswa');
    const initial = $form.serialize();
    let isDirty = false;

    const setDirty = (d) => {
        isDirty = d;
        $('#dirty-dot').toggleClass('on', d);
        $('#dirty-text').text(d ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan');
    };
    $form.on('input change', function () {
        setDirty($form.serialize() !== initial);
        const nm = $('#f-nama').val();
        if (nm) $('#profile-nama').text(nm);
    });
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) { e.preventDefault(); e.returnValue = ''; }
    });
    // Ctrl/Cmd+S = simpan cepat
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            $form.trigger('submit');
        }
    });
    $('#btn-back').on('click', function () { window.history.back(); });
    $('#btn-save-top').on('click', function () { $form.trigger('submit'); });
    $('#btn-reset').on('click', function (e) {
        if (isDirty && !confirm('Batalkan semua perubahan yang belum disimpan?')) e.preventDefault();
        else setTimeout(() => { setDirty(false); clearErrors(); }, 50);
    });

    // ── Validasi client (selaras aturan server) ──
    const TAB_OF = { 'f-nama':'datasiswa','f-nis':'datasiswa','f-nisn':'datasiswa','f-tahun_masuk':'datasiswa','f-tanggal_lahir':'biosiswa','f-hp':'biosiswa','f-nohp_ayah':'ortusiswa','f-nohp_ibu':'ortusiswa','f-nohp_wali':'walisiswa' };
    function setErr(id, msg) {
        const $i = $('#' + id);
        $i.addClass('field-err');
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
    const phoneOk = (v) => v === '' || /^[0-9+\-\s]{8,20}$/.test(v);
    function validateClient() {
        clearErrors();
        const errs = [];
        const nama = $('#f-nama').val().trim();
        if (nama.length < 3) { setErr('f-nama', 'Nama minimal 3 karakter.'); errs.push('Nama minimal 3 karakter'); }
        const nis = $('#f-nis').val().trim();
        if (!/^[0-9]{4,20}$/.test(nis)) { setErr('f-nis', 'NIS wajib angka, min. 4 digit.'); errs.push('NIS tidak valid'); }
        const nisn = $('#f-nisn').val().trim();
        if (!/^[0-9]{9,20}$/.test(nisn)) { setErr('f-nisn', 'NISN wajib angka, min. 9 digit.'); errs.push('NISN tidak valid'); }
        const thn = $('#f-tahun_masuk').val().trim();
        // <input type="date"> mengirim YYYY-MM-DD. Tahun saja (YYYY) juga
        // diterima supaya data lama yang tersimpan sebagai tahun tidak gagal.
        if (thn !== '' && !/^\d{4}(-\d{2}-\d{2})?$/.test(thn)) {
            setErr('f-tahun_masuk', 'Tanggal tidak valid.');
            errs.push('Tanggal diterima tidak valid');
        }
        const tgl = $('#f-tanggal_lahir').val();
        if (tgl !== '' && isNaN(Date.parse(tgl))) { setErr('f-tanggal_lahir', 'Tanggal lahir tidak valid.'); errs.push('Tanggal lahir tidak valid'); }
        ['f-hp','f-nohp_ayah','f-nohp_ibu','f-nohp_wali'].forEach(id => {
            const vv = ($('#' + id).val() || '').trim();
            if (!phoneOk(vv)) { setErr(id, 'Nomor HP 8–20 digit.'); errs.push('Nomor HP tidak valid'); }
        });
        return errs;
    }
    function gotoTab(id) {
        const $btn = $('[data-ui-target="#' + id + '"]');
        if ($btn.length) $btn.trigger('click');
    }

    $form.on('submit', function(e) {
        e.preventDefault();
        const errs = validateClient();
        if (errs.length) {
            const firstTab = TAB_OF[$('.field-err').first().attr('id')] || 'datasiswa';
            gotoTab(firstTab);
            $('#form-alert').removeClass('hidden').html('<b>Periksa kembali:</b><br>• ' + errs.join('<br>• '));
            $('#form-alert')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        const btn = $('#btn-save');
        btn.prop('disabled', true).html(spin() + ' Menyimpan...');
        $('#btn-save-top').prop('disabled', true);

        Swal.fire({
            title: 'Memproses data',
            text: 'Silahkan tunggu....',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: $form.attr('action'),
            data: $form.serialize(),
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                // updateData mengembalikan {insert, text} — bukan {status, message}
                if (data && data.insert) {
                    setDirty(false);
                    Swal.fire('Berhasil', data.text || 'Tersimpan.', 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.close();
                    $('#form-alert').removeClass('hidden').html('<b>Gagal menyimpan:</b><br>' + ((data && data.text) || 'Terjadi kesalahan'));
                    Swal.fire('Gagal', 'Periksa pesan di atas form.', 'error');
                    btn.prop('disabled', false).html(SAVE_HTML);
                    $('#btn-save-top').prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
                btn.prop('disabled', false).html(SAVE_HTML);
                $('#btn-save-top').prop('disabled', false);
            }
        });
    });

    // ── Foto: dropzone klik + seret ──
    const dz = $('#dz-foto'), fi = $('#input-foto');
    dz.on('click keydown', function (e) {
        if (e.type === 'click' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fi.trigger('click'); }
    });
    dz.on('dragover dragenter', function (e) { e.preventDefault(); dz.addClass('over'); });
    dz.on('dragleave drop', function (e) { e.preventDefault(); dz.removeClass('over'); });
    dz.on('drop', function (e) {
        const files = (e.originalEvent.dataTransfer || {}).files;
        if (files && files[0]) { fi[0].files = files; fi.trigger('change'); }
    });
    function handleFoto(file) {
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire('Terlalu Besar', 'Ukuran foto maksimal 2MB.', 'warning');
            fi.val('');
            return;
        }
        if (!/jpe?g|png/i.test(file.type + ' ' + file.name)) {
            Swal.fire('Format Salah', 'Foto harus JPG/PNG.', 'warning');
            fi.val('');
            return;
        }
        $('#dz-filename').removeClass('hidden').text(file.name + ' (' + Math.round(file.size / 1024) + ' KB)');
        const reader = new FileReader();
        reader.onload = function(e) { $('#preview-foto').attr('src', e.target.result); };
        reader.readAsDataURL(file);
    }
    fi.change(function() { handleFoto(this.files[0]); });

    $('#btn-upload-foto').click(function() {
        const fileInput = fi[0];
        if (!fileInput.files[0]) {
            Swal.fire('Peringatan', 'Pilih file foto terlebih dahulu', 'warning');
            return;
        }
        const $btn = $(this);
        const orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Mengunggah...');
        const formData = new FormData();
        formData.append('foto', fileInput.files[0]);
        formData.append(CSRF_NAME, CSRF_HASH);

        Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: '<?= site_url('datasiswa/uploadFile/' . $siswa->id_siswa) ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data && data.status) {
                    Swal.fire('Berhasil', 'Foto berhasil diperbarui', 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Gagal', (data && data.message) || 'Gagal upload foto', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
            },
            complete: function() { $btn.prop('disabled', false).html(orig); }
        });
    });

    $('#btn-delete-foto').on('click', function () {
        Swal.fire({
            title: 'Hapus Foto?',
            text: 'Foto akan dihapus dan kembali ke default.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const payload = {};
                payload[CSRF_NAME] = CSRF_HASH;
                $.ajax({
                    url: '<?= site_url('datasiswa/delete_foto/' . $siswa->id_siswa) ?>',
                    data: payload,
                    type: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        if (res && res.status) {
                            Swal.fire('Terhapus', res.message || 'Foto dihapus.', 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus foto.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    }
                });
            }
        });
    });

    // ── Password: toggle + strength ──
    $('#btn-toggle-pw').on('click', function () {
        const $p = $('#login-password');
        const show = $p.attr('type') === 'password';
        $p.attr('type', show ? 'text' : 'password');
        $(this).html(show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>');
    });
    $('#login-password').on('input', function () {
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
        const okLen = v.length >= 6, okMix = /[0-9]/.test(v) && /[a-zA-Z]/.test(v);
        $('#pw-req-len').html('<i class="bi ' + (okLen ? 'bi-check-circle-fill text-emerald-500' : 'bi-circle') + ' mr-1"></i>Minimal 6 karakter');
        $('#pw-req-mix').html('<i class="bi ' + (okMix ? 'bi-check-circle-fill text-emerald-500' : 'bi-circle') + ' mr-1"></i>Lebih kuat jika campur huruf + angka');
    });

    // Update password login siswa
    $('#form-login').on('submit', function (e) {
        e.preventDefault();
        const pwd = $('#login-password').val() || '';
        if (pwd.length < 6) {
            Swal.fire('Perhatian', 'Password minimal 6 karakter.', 'warning');
            return;
        }
        const $btn = $('#btn-save-login');
        const orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Menyimpan...');
        const payload = { id_siswa: SISWA_ID, password: pwd };
        payload[CSRF_NAME] = CSRF_HASH;
        $.ajax({
            url: '<?= site_url('datasiswa/editLogin') ?>',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).html(orig);
                if (res && res.status) {
                    AdminUI.modal.close('#editLoginModal');
                    $('#login-password').val('');
                    $('#pw-strength').css('width', '0');
                    Swal.fire('Berhasil', res.message || 'Password diperbarui.', 'success');
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal memperbarui password.', 'error');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(orig);
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            }
        });
    });
});

function deleteImage() {
    $('#btn-delete-foto').trigger('click');
}
window.deleteImage = deleteImage;
</script>

<?= $this->endSection() ?>
