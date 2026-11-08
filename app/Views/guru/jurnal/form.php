<?= $this->extend('layouts/mobile_tw') ?>

<?php
    $isEdit  = (bool) $jurnal;
    $isCopy  = !empty($copy_source);
    $today   = date('Y-m-d');
    $flashError = session()->getFlashdata('error');
    $flashSuccess = session()->getFlashdata('success');
    $prefillData = $prefill_data ?? [];
    // Saat validasi gagal, input lama jadi sumber kebenaran — termasuk field yang
    // sengaja dikosongkan guru (jangan diisi ulang dari data jurnal).
    $hasOld = session()->has('_ci_old_input');

    // Nilai efektif: old() (saat validasi gagal) → data jurnal (edit) → salinan → default.
    $val = static function (string $field, $default = '') use ($jurnal, $hasOld, $prefillData) {
        if ($hasOld) {
            $old = old($field);
            return $old !== null ? $old : '';
        }
        if ($jurnal) {
            return $jurnal->{$field} ?? $default;
        }
        if (array_key_exists($field, $prefillData)) {
            return $prefillData[$field];
        }
        return $default;
    };

    $copyKelasNama = 'Kelas';
    if ($isCopy) {
        foreach ($kelases as $k) {
            if ((int) (is_object($k) ? $k->id_kelas : 0) === (int) $copy_source->id_kelas) {
                $copyKelasNama = is_object($k) ? $k->nama_kelas : (string) $k;
                break;
            }
        }
    }
    $copyMapelNama = $isCopy ? ($mapels[(int) $copy_source->id_mapel] ?? 'Mata Pelajaran') : '';

    $selKelas = (int) $val('id_kelas', 0);
    if (!$isEdit && $selKelas === 0) {
        $selKelas = (int) ($prefill_kelas ?? 0);
    }
    $selMapel = (int) $val('id_mapel', 0);
    if (!$isEdit && $selMapel === 0) {
        $selMapel = (int) ($prefill_mapel ?? 0);
    }
    $selJam = (string) $val('jam_ke', '');
    if (!$isEdit && $selJam === '') {
        $selJam = (string) ($prefill_jam ?? '');
    }

    // Checkbox yang tidak dikirim berarti guru mengosongkan pilihan, bukan
    // kembali ke pilihan lama dari database.
    $selectedProfil = $hasOld ? (array) old('profil_pancasila') : ($selected_profil ?? []);

    $asesmenOptions = [
        'Formatif (Observasi / Penugasan)'   => 'Formatif',
        'Sumatif (Tes Tertulis / Ulangan)'   => 'Sumatif',
        'Asesmen Diagnostik'                 => 'Diagnostik',
        'Unjuk Kerja / Praktik / Proyek'     => 'Praktik',
        'Non-Tes (Portofolio / Refleksi)'    => 'Non-Tes',
    ];
    $selAsesmen = (string) $val('jenis_asesmen', '');
    $exactAsesmen = in_array($selAsesmen, array_keys($asesmenOptions), true);
    $matchedAsesmen = false;
    foreach ($asesmenOptions as $optValue => $key) {
        if ($exactAsesmen ? $selAsesmen === $optValue : ($selAsesmen !== '' && str_contains($selAsesmen, $key))) {
            $matchedAsesmen = true;
            break;
        }
    }

    // Nilai lama di luar daftar (mis. "PBL") tetap ditampilkan agar tidak hilang.
    $modelOptions = $model_options;
    $selModel = (string) $val('model_belajar', '');
    if ($selModel !== '' && !in_array($selModel, $modelOptions, true)) {
        array_unshift($modelOptions, $selModel);
    }

    // Buka "Pelengkap" bila memang sudah ada isinya (siswa_absent sudah ada di kartu utama).
    $hasExtra = !empty($selectedProfil);
    if (!$hasExtra) {
        foreach (['tujuan_pembelajaran', 'model_belajar', 'media_alat', 'kegiatan_belajar',
                  'jenis_asesmen', 'refleksi', 'tindak_lanjut', 'catatan_khusus'] as $f) {
            if ((string) $val($f, '') !== '') {
                $hasExtra = true;
                break;
            }
        }
    }

    $inputClass = 'w-full text-xs font-semibold px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-hidden';
    $selectWrap = 'relative';
    $selectClass = $inputClass . ' appearance-none cursor-pointer pr-7';
?>

<?= $this->section('styles') ?>
<style>
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    .profil-check-card {
        transition: all 0.15s ease;
    }
    .profil-check-card:has(input:checked) {
        background-color: var(--primary-50);
        border-color: var(--primary-300);
        color: var(--primary-800);
    }

    /* Halaman form menyembunyikan bottom nav, jadi bar aksi cukup duduk di bawah. */
    .sticky-bottom-bar {
        position: fixed;
        bottom: calc(12px + var(--safe-area-inset-bottom, 0px));
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 24px);
        max-width: 456px;
        z-index: 2100;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(18px) saturate(180%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 22px;
        box-shadow: 0 18px 36px -18px rgba(15, 23, 42, 0.35), 0 2px 8px rgba(15, 23, 42, 0.06);
        padding: 10px 12px;
    }
    /* Beri ruang agar bar aksi tidak menutupi field terakhir. */
    .app-container { padding-bottom: calc(104px + var(--safe-area-inset-bottom, 0px)); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url(($kembali ?? '') === 'absen' ? 'presensi' : 'guru/jurnal') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="<?= ($kembali ?? '') === 'absen' ? 'Kembali ke Absen' : 'Kembali ke Riwayat' ?>">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">
                <?= $isEdit ? 'Edit Jurnal' : ($isCopy ? 'Salin Jurnal' : 'Tulis Jurnal') ?>
            </h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">
                <?= $isEdit ? 'Ubah Catatan KBM' : ($isCopy ? 'Salin & sesuaikan isian' : 'Catatan Harian KBM') ?>
            </p>
        </div>
    </div>
    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-brand-50 text-brand-700 rounded-full text-[11px] font-bold">
        <i class="bi bi-<?= $isEdit ? 'pencil' : ($isCopy ? 'files' : 'plus-circle') ?>"></i>
        <?= $isEdit ? '#' . $jurnal->id_jurnal : ($isCopy ? 'Salinan' : 'Baru') ?>
    </span>
</div>

<!-- ── Main Form Container ──────────────────────────────────────────── -->
<div class="px-4 pb-28 space-y-4 mt-2.5">

    <!-- Flash Alert Error -->
    <?php if ($flashError): ?>
    <div id="form-error-alert" class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-sm shrink-0"></i>
            <span><?= esc($flashError) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1" aria-label="Tutup pesan">
            <i class="bi bi-x-lg text-xs"></i>
        </button>
    </div>
    <?php endif; ?>

    <?php if ($flashSuccess): ?>
    <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
        <i class="bi bi-check-circle-fill text-emerald-500 text-sm shrink-0"></i>
        <span><?= esc($flashSuccess) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($isEdit && in_array((string) ($jurnal->validasi_status ?? ''), ['APPROVED', 'REJECTED'], true)): ?>
        <?php
            $vDitolak = (string) $jurnal->validasi_status === 'REJECTED';
            $vCatatan = trim((string) ($jurnal->validasi_catatan ?? ''));
        ?>
        <div class="p-3.5 rounded-2xl text-xs shadow-2xs space-y-1 <?= $vDitolak ? 'bg-orange-50 border border-orange-200 text-orange-900' : 'bg-emerald-50 border border-emerald-200 text-emerald-900' ?>">
            <p class="font-bold flex items-center gap-1.5 m-0">
                <i class="bi bi-<?= $vDitolak ? 'exclamation-octagon-fill text-orange-600' : 'patch-check-fill text-emerald-600' ?>"></i>
                <?= $vDitolak ? 'Jurnal ini perlu perbaikan' : 'Jurnal ini sudah divalidasi' ?>
            </p>
            <p class="text-[11px] leading-relaxed opacity-80 m-0">
                <?php if ($vDitolak): ?>
                    Catatan validator<?= $vCatatan !== '' ? ': ' . esc($vCatatan) : ' belum diisi.' ?>
                <?php else: ?>
                    Isinya sudah disetujui guru piket / kepala sekolah.
                <?php endif; ?>
                Jika Anda menyimpan perubahan, jurnal kembali masuk antrean validasi.
            </p>
        </div>
    <?php elseif ($isEdit && trim((string) ($jurnal->validasi_catatan ?? '')) !== ''): ?>
        <div class="p-3.5 rounded-2xl text-xs shadow-2xs bg-slate-50 border border-slate-200 text-slate-700">
            <p class="font-bold flex items-center gap-1.5 m-0">
                <i class="bi bi-chat-left-text text-slate-500 text-sm shrink-0"></i>
                Catatan validator sebelumnya
            </p>
            <p class="text-[11px] leading-relaxed opacity-80 m-0"><?= esc(trim((string) $jurnal->validasi_catatan)) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($tanpa_penugasan)): ?>
    <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs shadow-2xs space-y-1">
        <p class="font-bold flex items-center gap-1.5 m-0">
            <i class="bi bi-exclamation-triangle-fill text-amber-600"></i>
            Penugasan mengajar belum diatur
        </p>
        <p class="text-[11px] text-amber-800/80 leading-relaxed m-0">
            Admin belum menetapkan kelas dan mapel untuk Anda pada tahun pelajaran/semester ini.
            Jurnal baru hanya bisa disimpan setelah penugasan diatur. Jurnal lama tetap bisa diedit tanpa mengubah kelas/mapel.
        </p>
    </div>
    <?php endif; ?>

    <?php if (($kembali ?? '') === 'absen'): ?>
    <div class="p-3.5 rounded-2xl bg-brand-50 border border-brand-100 text-brand-900 text-xs shadow-2xs space-y-1">
        <p class="font-bold flex items-center gap-1.5 m-0">
            <i class="bi bi-box-arrow-right text-brand-600"></i>
            Syarat absen pulang
        </p>
        <p class="text-[11px] text-brand-700/80 leading-relaxed m-0">
            Simpan jurnal sesi ini untuk melengkapi syarat absen pulang. Setelah tersimpan, Anda akan diarahkan kembali ke halaman absen.
        </p>
    </div>
    <?php endif; ?>

    <?php if ($isCopy): ?>
    <div class="p-3.5 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-900 text-xs shadow-2xs space-y-1">
        <p class="font-bold flex items-center gap-1.5 m-0">
            <i class="bi bi-files text-indigo-600"></i>
            Disalin dari jurnal <?= date('d/m/Y', strtotime($copy_source->tanggal)) ?>
        </p>
        <p class="text-[11px] text-indigo-700/80 leading-relaxed m-0">
            <?= esc($copyKelasNama) ?> &middot; <?= esc($copyMapelNama) ?>.
            Tanggal, kehadiran siswa, dan tautan jadwal dikosongkan. Periksa kembali sebelum menyimpan.
        </p>
    </div>
    <?php elseif (!$isEdit && !empty($last_jurnal)): ?>
    <a href="<?= site_url('guru/jurnal/create?copy=' . (int) $last_jurnal->id_jurnal) ?>"
       class="flex items-center justify-between gap-2 p-3 rounded-2xl bg-white border border-slate-200/80 text-slate-700 no-underline shadow-2xs hover:bg-slate-50 transition-colors">
        <span class="flex items-center gap-2 min-w-0">
            <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs shrink-0">
                <i class="bi bi-files"></i>
            </span>
            <span class="min-w-0">
                <span class="block text-[11px] font-bold">Salin jurnal terakhir</span>
                <span class="block text-[10px] text-slate-400 truncate">
                    <?= date('d/m/Y', strtotime($last_jurnal->tanggal)) ?> &middot; isi otomatis, tinggal sesuaikan
                </span>
            </span>
        </span>
        <i class="bi bi-chevron-right text-slate-400 text-xs shrink-0"></i>
    </a>
    <?php endif; ?>

    <form id="form-jurnal" action="<?= site_url('guru/jurnal/save') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="id_jurnal" value="<?= $isEdit ? $jurnal->id_jurnal : '' ?>">
        <input type="hidden" name="id_jadwal" id="input_id_jadwal" value="<?= $isEdit ? esc($jurnal->id_jadwal ?? '') : ((int) ($prefill_jadwal ?? 0) ?: '') ?>">
        <?php if (($kembali ?? '') === 'absen'): ?>
        <input type="hidden" name="kembali" value="absen">
        <?php endif; ?>

        <!-- Quick pick dari jadwal hari ini -->
        <?php if (!empty($jadwal_hari_ini) && !$isEdit && !$isCopy): ?>
        <div id="quickpick-wrap" data-today="<?= $today ?>" class="p-3.5 rounded-2xl bg-brand-50 border border-brand-100/60 shadow-2xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-brand-900 flex items-center gap-1.5">
                    <i class="bi bi-lightning-charge-fill text-amber-500"></i>
                    <span>Jadwal Hari Ini</span>
                </span>
                <span class="text-[10px] text-brand-600 font-semibold bg-white/80 px-2 py-0.5 rounded-md">Ketuk untuk isi</span>
            </div>
            <div class="space-y-1.5">
                <?php foreach ($jadwal_hari_ini as $jh): ?>
                <button type="button"
                        class="schedule-chip w-full text-left p-2.5 bg-white hover:bg-brand-600 hover:text-white text-slate-700 border border-brand-200/80 rounded-xl text-xs font-semibold transition-all shadow-2xs group flex items-center justify-between"
                        aria-pressed="false"
                        data-kelas="<?= (int) ($jh->id_kelas ?? 0) ?>"
                        data-mapel="<?= (int) ($jh->id_mapel ?? 0) ?>"
                        data-jam="<?= esc((string) ($jh->jam_ke ?? '1')) ?>"
                        data-jadwal="<?= (int) ($jh->id_jadwal ?? 0) ?>">
                    <div>
                        <span class="inline-block px-1.5 py-0.5 bg-brand-100 group-hover:bg-white/20 text-brand-700 group-hover:text-white rounded text-[10px] font-bold mb-0.5">
                            Jam <?= esc($jh->jam_ke ?? '-') ?>
                            <?php if (!empty($jh->dari) && !empty($jh->sampai)): ?>
                            <span class="font-semibold opacity-80">&middot; <?= esc(substr((string) $jh->dari, 0, 5)) ?>-<?= esc(substr((string) $jh->sampai, 0, 5)) ?></span>
                            <?php endif; ?>
                        </span>
                        <div class="font-bold text-xs"><?= esc($jh->nama_kelas ?? 'Kelas') ?></div>
                        <div class="text-[11px] text-slate-500 group-hover:text-brand-100"><?= esc($jh->nama_mapel ?? '') ?></div>
                    </div>
                    <i class="bi bi-check-circle-fill text-transparent group-hover:text-white text-base transition-colors shrink-0 ml-1.5" aria-hidden="true"></i>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Detail Pertemuan (standar jurnal mengajar) ────────────────── -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-3.5">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h2 class="text-xs sm:text-sm font-bold text-slate-800 m-0">Detail Pertemuan</h2>
                <span class="text-[10px] font-semibold text-slate-400"><span class="text-rose-500">*</span> wajib diisi</span>
            </div>

            <!-- Tanggal & Jam Ke- -->
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="input_tanggal" class="block text-[11px] font-bold text-slate-600 mb-1">
                        Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" id="input_tanggal" required
                           max="<?= $today ?>"
                           value="<?= esc($val('tanggal', $today)) ?>"
                           class="<?= $inputClass ?>">
                </div>

                <div>
                    <label for="input_jam_ke" class="block text-[11px] font-bold text-slate-600 mb-1">
                        Jam Ke- <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="jam_ke" id="input_jam_ke" required maxlength="20"
                           placeholder="Contoh: 1-2"
                           value="<?= esc($selJam) ?>"
                           class="<?= $inputClass ?>">
                </div>
            </div>

            <!-- Kelas & Mata Pelajaran -->
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="input_id_kelas" class="block text-[11px] font-bold text-slate-600 mb-1">
                        Kelas <span class="text-rose-500">*</span>
                    </label>
                    <div class="<?= $selectWrap ?>">
                        <select name="id_kelas" id="input_id_kelas" required class="<?= $selectClass ?>">
                            <option value="">-- Pilih --</option>
                            <?php if (!empty($kelases)): foreach ($kelases as $key => $k):
                                $kId = is_object($k) ? (int) $k->id_kelas : (int) $key;
                                $kNama = is_object($k) ? $k->nama_kelas : $k;
                            ?>
                            <option value="<?= $kId ?>" <?= $selKelas === $kId ? 'selected' : '' ?>>
                                <?= esc($kNama) ?>
                            </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-2.5 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label for="input_id_mapel" class="block text-[11px] font-bold text-slate-600 mb-1">
                        Mata Pelajaran <span class="text-rose-500">*</span>
                    </label>
                    <div class="<?= $selectWrap ?>">
                        <select name="id_mapel" id="input_id_mapel" required class="<?= $selectClass ?>">
                            <option value="">-- Pilih --</option>
                            <?php if (!empty($mapels)): foreach ($mapels as $idM => $nmM): ?>
                            <option value="<?= (int) $idM ?>" <?= $selMapel === (int) $idM ? 'selected' : '' ?>>
                                <?= esc($nmM) ?>
                            </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-2.5 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Materi Pokok -->
            <div>
                <label for="input_materi_pokok" class="block text-[11px] font-bold text-slate-600 mb-1">
                    Materi Pokok <span class="text-rose-500">*</span>
                </label>
                <textarea name="materi_pokok" id="input_materi_pokok" rows="3" required
                          placeholder="Pokok bahasan yang diajarkan hari ini..."
                          class="<?= $inputClass ?>"><?= esc($val('materi_pokok')) ?></textarea>
            </div>

            <!-- Siswa Tidak Hadir: dipilih dari daftar siswa kelas terpilih -->
            <div id="absen-picker">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-[11px] font-bold text-slate-600 m-0">
                        Siswa Tidak Hadir
                    </label>
                    <button type="button" id="absen-toggle"
                            class="text-[11px] font-bold text-brand-600 hover:text-brand-800 transition-colors">
                        Pilih siswa
                    </button>
                </div>
                <input type="hidden" name="siswa_absent" id="input_siswa_absent" value="<?= esc($val('siswa_absent')) ?>">

                <div id="absen-badge-wrap" class="hidden flex-wrap gap-1.5 mb-2"></div>
                <div id="absen-panel" class="hidden max-h-56 overflow-y-auto bg-slate-50 border border-slate-200 rounded-xl p-2 space-y-0.5"></div>
                <p id="absen-status" class="text-[10px] text-slate-400 font-medium m-0 mt-1">
                    Pilih kelas untuk menampilkan daftar siswa.
                </p>
            </div>
        </div>

        <!-- ── Pelengkap (opsional, dilipat) ────────────────────────────── -->
        <details class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden group" <?= $hasExtra ? 'open' : '' ?>>
            <summary class="flex items-center gap-2 p-4 cursor-pointer list-none select-none">
                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-sm shrink-0">
                    <i class="bi bi-sliders"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-xs sm:text-sm font-bold text-slate-800 m-0">Pelengkap</h2>
                    <p class="text-[10px] text-slate-400 m-0">Opsional — boleh dikosongkan</p>
                </div>
                <i class="bi bi-chevron-down text-slate-400 text-sm transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="px-4 pb-4 space-y-3.5 border-t border-slate-100 pt-3.5">

            <!-- Tujuan Pembelajaran -->
            <div>
                <label for="input_tujuan" class="block text-[11px] font-bold text-slate-600 mb-1">Tujuan Pembelajaran</label>
                <textarea name="tujuan_pembelajaran" id="input_tujuan" rows="2" placeholder="Contoh: Siswa mampu mengidentifikasi komponen..."
                          class="<?= $inputClass ?>"><?= esc($val('tujuan_pembelajaran')) ?></textarea>
            </div>

            <!-- Model Belajar & Media -->
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label for="input_model_belajar" class="block text-[11px] font-bold text-slate-600 mb-1">Model Belajar</label>
                    <div class="<?= $selectWrap ?>">
                        <select name="model_belajar" id="input_model_belajar" class="<?= $selectClass ?>">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($modelOptions as $mo): ?>
                            <option value="<?= esc($mo) ?>" <?= $selModel === $mo ? 'selected' : '' ?>><?= esc($mo) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="bi bi-chevron-down absolute right-2.5 top-2.5 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label for="input_media_alat" class="block text-[11px] font-bold text-slate-600 mb-1">Media &amp; Alat</label>
                    <input type="text" name="media_alat" id="input_media_alat" placeholder="PPT, LKPD..."
                           value="<?= esc($val('media_alat')) ?>"
                           class="<?= $inputClass ?>">
                </div>
            </div>

            <!-- Ringkasan Kegiatan -->
            <div>
                <label for="input_kegiatan" class="block text-[11px] font-bold text-slate-600 mb-1">Alur Kegiatan Pembelajaran</label>
                <textarea name="kegiatan_belajar" id="input_kegiatan" rows="3" placeholder="Contoh:&#10;1. Apersepsi &amp; presensi&#10;2. Diskusi kelompok materi inti&#10;3. Penutup &amp; kesimpulan"
                          class="<?= $inputClass ?>"><?= esc($val('kegiatan_belajar')) ?></textarea>
            </div>

            <!-- Dimensi Profil Pancasila -->
            <div>
                <span class="block text-[11px] font-bold text-slate-600 mb-2">
                    Dimensi Profil Pancasila yang Dikuatkan:
                </span>
                <div class="space-y-1.5">
                    <?php foreach ($profil_options as $po):
                        $checked = in_array($po, $selectedProfil, true);
                    ?>
                    <label class="profil-check-card flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 hover:bg-slate-50 cursor-pointer transition-all text-xs font-semibold text-slate-700 select-none">
                        <input type="checkbox" name="profil_pancasila[]" value="<?= esc($po) ?>" <?= $checked ? 'checked' : '' ?>
                               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                        <span class="leading-tight text-[11px]"><?= esc($po) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bentuk Asesmen -->
            <div>
                <label for="input_jenis_asesmen" class="block text-[11px] font-bold text-slate-600 mb-1">Bentuk / Jenis Asesmen</label>
                <div class="<?= $selectWrap ?>">
                    <select name="jenis_asesmen" id="input_jenis_asesmen" class="<?= $selectClass ?>">
                        <option value="">-- Pilih --</option>
                        <?php if ($selAsesmen !== '' && !$matchedAsesmen): ?>
                        <option value="<?= esc($selAsesmen) ?>" selected><?= esc($selAsesmen) ?></option>
                        <?php endif; ?>
                        <?php foreach ($asesmenOptions as $optValue => $key): ?>
                        <option value="<?= esc($optValue) ?>" <?= ($exactAsesmen ? $selAsesmen === $optValue : ($selAsesmen !== '' && str_contains($selAsesmen, $key))) ? 'selected' : '' ?>><?= esc($optValue) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-2.5 text-slate-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <!-- Refleksi Guru -->
            <div>
                <label for="input_refleksi" class="block text-[11px] font-bold text-slate-600 mb-1">Refleksi Pembelajaran</label>
                <textarea name="refleksi" id="input_refleksi" rows="2" placeholder="Keberhasilan atau kendala pembelajaran..."
                          class="<?= $inputClass ?>"><?= esc($val('refleksi')) ?></textarea>
            </div>

            <!-- Tindak Lanjut -->
            <div>
                <label for="input_tindak_lanjut" class="block text-[11px] font-bold text-slate-600 mb-1">Rencana Tindak Lanjut</label>
                <textarea name="tindak_lanjut" id="input_tindak_lanjut" rows="2" placeholder="Pengayaan, remedial, atau penugasan mandiri..."
                          class="<?= $inputClass ?>"><?= esc($val('tindak_lanjut')) ?></textarea>
            </div>

            <!-- Catatan Khusus -->
            <div>
                <label for="input_catatan_khusus" class="block text-[11px] font-bold text-slate-600 mb-1">Catatan Khusus</label>
                <input type="text" name="catatan_khusus" id="input_catatan_khusus" placeholder="Insiden khusus di kelas jika ada..."
                       value="<?= esc($val('catatan_khusus')) ?>"
                       class="<?= $inputClass ?>">
            </div>
        </div>
        </details>

        <!-- ── Sticky Bottom Action Bar ─────────────────────────────────── -->
        <div class="sticky-bottom-bar flex items-center gap-2">
            <a href="<?= site_url('guru/jurnal') ?>"
               class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full text-xs font-bold text-center transition-all no-underline active:scale-95">
                Batal
            </a>
            <?php if (!$isEdit): ?>
            <button type="submit" name="after" value="again" aria-label="Simpan dan tulis jurnal berikutnya"
                    title="Simpan & tulis jurnal berikutnya"
                    class="w-11 h-10 shrink-0 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center transition-all active:scale-95">
                <i class="bi bi-plus-lg"></i>
            </button>
            <?php endif; ?>
            <button type="submit" id="btn-submit-jurnal"
                    class="flex-1 py-2.5 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white rounded-full text-xs font-bold transition-all shadow-md shadow-brand-500/20 active:scale-95 flex items-center justify-center gap-1.5">
                <i class="bi bi-check-circle-fill"></i>
                <span id="btn-submit-text"><?= $isEdit ? 'Perbarui Jurnal' : 'Simpan Jurnal' ?></span>
            </button>
        </div>
    </form>

</div>

<script>
(function () {
    const form     = document.getElementById('form-jurnal');
    const kelasEl  = document.getElementById('input_id_kelas');
    const mapelEl  = document.getElementById('input_id_mapel');
    const jamEl    = document.getElementById('input_jam_ke');
    const jadwalEl = document.getElementById('input_id_jadwal');
    const todayEl  = document.getElementById('input_tanggal');
    const wrap     = document.getElementById('quickpick-wrap');

    if (!form) return;

    const btnSubmitText = document.getElementById('btn-submit-text');
    const TEKS_SUBMIT_AWAL = btnSubmitText ? btnSubmitText.textContent : 'Simpan Jurnal';

    function chips() {
        return Array.from(document.querySelectorAll('.schedule-chip'));
    }

    // Sorot chip yang cocok dengan kelas/mapel/jam yang sedang terpilih.
    function syncChips() {
        const kelas = kelasEl?.value || '';
        const mapel = mapelEl?.value || '';
        const jam   = (jamEl?.value || '').trim();
        let matchedJadwal = null;

        chips().forEach(function (el) {
            const match = el.dataset.kelas === kelas
                && el.dataset.mapel === mapel
                && (!jam || el.dataset.jam === jam);

            if (match && !matchedJadwal) matchedJadwal = el.dataset.jadwal || null;

            el.classList.toggle('ring-2', match);
            el.classList.toggle('ring-brand-600', match);
            el.classList.toggle('bg-brand-50', match);
            el.setAttribute('aria-pressed', match ? 'true' : 'false');

            const icon = el.querySelector('i');
            if (icon) {
                icon.classList.toggle('text-brand-600', match);
                icon.classList.toggle('text-transparent', !match);
            }
        });

        // Tautan id_jadwal hanya dipertahankan bila isian cocok dengan slot hari ini.
        if (chips().length && jadwalEl) {
            jadwalEl.value = matchedJadwal || '';
        }
    }

    wrap?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.schedule-chip');
        if (!btn) return;

        if (kelasEl)  kelasEl.value  = btn.dataset.kelas;
        if (mapelEl)  mapelEl.value  = btn.dataset.mapel;
        if (jamEl)    jamEl.value    = btn.dataset.jam;
        if (jadwalEl) jadwalEl.value = btn.dataset.jadwal;

        syncChips();

        // Daftar "Siswa Tidak Hadir" mengikuti kelas yang baru terpilih.
        if (kelasEl) kelasEl.dispatchEvent(new Event('change'));
    });

    [kelasEl, mapelEl, jamEl].forEach(function (el) {
        if (!el) return;
        el.addEventListener('change', syncChips);
        el.addEventListener('input', syncChips);
    });

    // Quick-pick hanya relevan untuk tanggal hari ini.
    function syncQuickpickVisibility() {
        if (!wrap || !todayEl) return;
        wrap.style.display = (todayEl.value === wrap.dataset.today) ? '' : 'none';
    }
    todayEl?.addEventListener('change', syncQuickpickVisibility);

    // Cegah klik ganda saat menyimpan: semua tombol submit dimatikan,
    // teks tombol utama diganti "Menyimpan..." sebagai feedback.
    form.addEventListener('submit', function () {
        form.querySelectorAll('button[type="submit"]').forEach(function (b) {
            b.disabled = true;
            b.classList.add('opacity-75', 'cursor-not-allowed');
        });
        const txt = document.getElementById('btn-submit-text');
        if (txt) txt.textContent = 'Menyimpan...';
    });

    // Kembali ke form via back-navigation tombol bisa terkunci di DOM cache;
    // aktifkan kembali agar guru bisa memperbaiki lalu simpan lagi.
    window.addEventListener('pageshow', function () {
        form.querySelectorAll('button[type="submit"]:disabled').forEach(function (b) {
            b.disabled = false;
            b.classList.remove('opacity-75', 'cursor-not-allowed');
        });
        const txt = document.getElementById('btn-submit-text');
        if (txt && txt.textContent === 'Menyimpan...') txt.textContent = TEKS_SUBMIT_AWAL;
    });

    syncChips();
    syncQuickpickVisibility();

    // ── Siswa Tidak Hadir: dipilih dari daftar siswa kelas terpilih.
    const absenHidden = document.getElementById('input_siswa_absent');
    const absenPanel  = document.getElementById('absen-panel');
    const absenBadges = document.getElementById('absen-badge-wrap');
    const absenStatus = document.getElementById('absen-status');
    const absenToggle = document.getElementById('absen-toggle');

    const absenSelected = new Map(); // "id_siswa" → nama
    let absenTeksLama  = '';
    let absenTeksAktif = false;
    const absenCache = {};
    let absenLoading = 0;

    (function seedAbsen() {
        if (!absenHidden) return;
        const initial = (absenHidden.value || '').trim();
        if (initial.startsWith('[')) {
            let ids = [];
            try { ids = JSON.parse(initial) || []; } catch (e) { ids = []; }
            ids.forEach(function (id) {
                const n = Number(id);
                if (n > 0) absenSelected.set(String(n), ''); // nama diisi saat daftar kelas termuat
            });
        } else if (initial !== '') {
            // Teks manual dari data lama ("Nihil" atau nama)
            absenTeksLama = initial;
            absenTeksAktif = true;
        }
    })();

    const escAbsen = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    function absenKelasId() {
        return Number((kelasEl?.value || 0)) || 0;
    }

    function absenBadgeHtml(content, attr) {
        return '<span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-[11px] font-bold">'
            + '<span class="max-w-[180px] truncate">' + content + '</span>'
            + '<button type="button" class="absen-badge-x w-4 h-4 rounded-full bg-rose-100 hover:bg-rose-200 text-rose-600 flex items-center justify-center" ' + attr + ' aria-label="Hapus">'
            + '<i class="bi bi-x text-[9px]"></i></button></span>';
    }

    function renderAbsen() {
        let html = '';
        absenSelected.forEach(function (nama, id) {
            html += absenBadgeHtml(escAbsen(nama || ('Siswa #' + id)), 'data-id="' + escAbsen(id) + '"');
        });
        if (absenTeksAktif) {
            html += absenBadgeHtml(escAbsen(absenTeksLama), 'data-teks="1"');
        }
        if (absenBadges) {
            absenBadges.innerHTML = html;
            absenBadges.classList.toggle('hidden', !html);
            absenBadges.classList.toggle('flex', !!html);
            absenBadges.classList.toggle('mb-2', !!html);
        }
    }

    function syncAbsenHidden() {
        if (!absenHidden) return;
        if (absenSelected.size > 0) {
            absenHidden.value = JSON.stringify(Array.from(absenSelected.keys()).map(Number));
        } else if (absenTeksAktif) {
            absenHidden.value = absenTeksLama;
        } else {
            absenHidden.value = '';
        }
    }

    async function loadAbsen() {
        if (!absenPanel || !absenStatus) return;

        const idKelas = absenKelasId();
        if (!idKelas) {
            absenPanel.innerHTML = '';
            absenPanel.classList.add('hidden');
            absenStatus.textContent = 'Pilih kelas untuk menampilkan daftar siswa.';
            return;
        }

        absenStatus.textContent = 'Memuat daftar siswa...';
        const token = ++absenLoading;
        try {
            if (!absenCache[idKelas]) {
                const res = await fetch(`<?= site_url('guru/jurnal/siswa') ?>/${idKelas}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (json.status && Array.isArray(json.data)) {
                    absenCache[idKelas] = json.data;
                } else {
                    // Gagal tidak di-cache agar mengubah kelas/kondisi bisa langsung mencoba lagi.
                    throw new Error(String(json.message || 'Gagal memuat daftar siswa.'));
                }
            }
            if (token !== absenLoading) return;

            const rows = absenCache[idKelas];
            rows.forEach(function (r) {
                const id = String(r.id_siswa);
                if (absenSelected.has(id)) absenSelected.set(id, r.nama); // lengkapi nama badge awal
            });
            renderAbsen();
            syncAbsenHidden();

            if (!rows.length) {
                absenPanel.innerHTML = '';
                absenStatus.textContent = 'Tidak ada siswa aktif pada kelas ini.';
                return;
            }

            absenPanel.innerHTML = rows.map(function (r) {
                const id = String(r.id_siswa);
                const ok = absenSelected.has(id);
                return '<label class="absen-item flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white cursor-pointer text-[11px] font-semibold '
                    + (ok ? 'bg-white text-rose-700' : 'text-slate-700') + '"'
                    + ' data-id="' + escAbsen(id) + '" data-nama="' + escAbsen(r.nama) + '">'
                    + '<input type="checkbox" class="absen-check rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-3.5 h-3.5 shrink-0" ' + (ok ? 'checked' : '') + '>'
                    + '<span class="truncate">' + escAbsen(r.nama) + '</span></label>';
            }).join('');
            absenPanel.classList.remove('hidden');
            absenStatus.textContent = 'Ketuk nama siswa yang tidak hadir. Kosongkan bila semua hadir.';
        } catch (e) {
            if (token !== absenLoading) return;
            absenStatus.textContent = (e instanceof Error && e.message)
                ? e.message
                : 'Gagal memuat daftar siswa.';
        }
    }

    absenToggle?.addEventListener('click', function () {
        if (!absenPanel) return;
        if (absenPanel.classList.contains('hidden') && absenKelasId() && !absenPanel.innerHTML) {
            loadAbsen();
        }
        absenPanel.classList.toggle('hidden');
    });

    absenPanel?.addEventListener('change', function (e) {
        const cb = e.target.closest('.absen-check');
        if (!cb) return;
        const item = cb.closest('.absen-item');
        const id = String(item?.dataset.id || '');
        if (!id) return;
        if (cb.checked) {
            absenSelected.set(id, item.dataset.nama || id);
            absenTeksAktif = false; // memilih siswa menggantikan teks manual lama
        } else {
            absenSelected.delete(id);
        }
        renderAbsen();
        syncAbsenHidden();
    });

    absenBadges?.addEventListener('click', function (e) {
        const x = e.target.closest('.absen-badge-x');
        if (!x) return;
        if (x.dataset.teks) {
            absenTeksAktif = false;
        } else {
            const id = String(x.dataset.id || '');
            absenSelected.delete(id);
            const cb = absenPanel?.querySelector('.absen-item[data-id="' + id + '"] .absen-check');
            if (cb) {
                cb.checked = false;
                cb.closest('.absen-item')?.classList.remove('bg-white', 'text-rose-700');
            }
        }
        renderAbsen();
        syncAbsenHidden();
    });

    // Daftar siswa berubah mengikuti pilihan kelas.
    kelasEl?.addEventListener('change', loadAbsen);
    if (absenKelasId() > 0) loadAbsen();

    // Gulir ke pesan error agar tidak terlewat di layar kecil.
    const alertBox = document.getElementById('form-error-alert');
    if (alertBox) {
        requestAnimationFrame(function () {
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }
})();
</script>
<?= $this->endSection() ?>
