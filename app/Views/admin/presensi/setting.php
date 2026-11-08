<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/plugins/dropify/css/dropify.min.css') ?>">
<style>
    .setting-card {
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        background: #ffffff;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .setting-card:hover {
        border-color: rgba(203, 213, 225, 0.9);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .tier-box {
        border-radius: 14px;
        border: 1.5px solid #e2e8f0;
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .tier-box:hover {
        border-color: var(--primary-300);
        box-shadow: 0 6px 16px rgb(var(--primary-500-rgb) / 0.08);
        transform: translateY(-1px);
    }
    .theme-card {
        position: relative;
        text-align: left;
        border-radius: 16px;
        border: 1.5px solid #e2e8f0;
        background: #ffffff;
        padding: 14px;
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease, transform .2s ease;
    }
    .theme-card:hover {
        transform: translateY(-1px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .06);
    }
    .theme-card.selected {
        border-color: var(--primary-500);
        background: var(--primary-50);
        box-shadow: 0 0 0 3px rgb(var(--primary-500-rgb) / 0.18);
    }
    .theme-card .theme-check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-600);
        color: #fff;
        font-size: 11px;
        opacity: 0;
        transform: scale(.7);
        transition: opacity .18s ease, transform .18s ease;
    }
    .theme-card.selected .theme-check {
        opacity: 1;
        transform: scale(1);
    }
    .theme-swatch {
        width: 100%;
        height: 26px;
        border-radius: 8px;
        border: 1px solid rgba(15, 23, 42, .06);
    }
    .theme-custom-swatch {
        height: 26px;
        border-radius: 8px;
        border: 1px solid rgba(15, 23, 42, .06);
    }
    .dropify-wrapper {
        border-radius: 14px !important;
        border: 1.5px dashed #cbd5e1 !important;
        background-color: #f8fafc !important;
        transition: all 0.2s ease;
    }
    .dropify-wrapper:hover {
        border-color: var(--primary-500) !important;
        background-color: var(--primary-50) !important;
    }
    .dropify-wrapper .dropify-message p {
        font-size: 0.8rem !important;
        line-height: 1.3 !important;
        color: #64748b !important;
    }
    .dropify-wrapper .dropify-message span.file-icon {
        font-size: 1.8rem !important;
        color: #94a3b8 !important;
    }
    .custom-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .custom-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .custom-switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 24px;
    }
    .custom-switch-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .custom-switch input:checked + .custom-switch-slider {
        background-color: var(--primary-600);
    }
    .custom-switch input:checked + .custom-switch-slider:before {
        transform: translateX(20px);
    }
    .fixed-bottom-bar {
        position: fixed;
        right: 0;
        bottom: 0;
        left: 0;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
    }
    @media (max-width: 1023.98px) {
        .fixed-bottom-bar {
            left: 0 !important;
        }
    }
    @media (min-width: 1024px) {
        #main-sidebar:not(.collapsed) ~ div .fixed-bottom-bar {
            left: 256px !important;
        }
        #main-sidebar.collapsed ~ div .fixed-bottom-bar {
            left: 4.5rem !important;
        }
    }
    .spinner-tw {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.45);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin-tw 0.6s linear infinite;
        vertical-align: -0.15em;
        flex-shrink: 0;
    }
    .spinner-tw.spinner-tw-dark {
        border: 2px solid rgb(var(--primary-600-rgb) / 0.25);
        border-top-color: var(--primary-600);
    }
    @keyframes spin-tw {
        to { transform: rotate(360deg); }
    }
    .btn-tw:disabled,
    .btn-tw[disabled] {
        opacity: 0.55;
        cursor: not-allowed;
        pointer-events: none;
    }
    /* Tab Panes */
    .tab-content, .tab-content-tw { width: 100%; }
    .tab-pane, .tab-pane-tw { display: none !important; }
    .tab-pane.active, .tab-pane.show.active,
    .tab-pane-tw.active, .tab-pane-tw.show.active { display: block !important; animation: fadeInUp 0.2s ease-out; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up">
    <!-- Page Header -->
    <div class="content-header fade-in-up">
        <div class="page-header">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-600 text-white rounded-xl shadow-md shadow-blue-500/20">
                    <i class="bi bi-sliders text-xl"></i>
                </div>
                <div>
                    <h3 class="mb-0 font-bold text-slate-800">Pengaturan Sistem</h3>
                </div>
            </div>
            <nav class="breadcrumb-tw">
                <a href="<?= base_url('dashboard') ?>" class="no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
                <span class="sep">/</span>
                <span class="active">Pengaturan Sistem</span>
            </nav>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="content-area">
        <div class="w-full">
            
            <!-- Modern Segmented Nav Tabs Pills -->
            <div class="bg-slate-100/95 backdrop-blur-md p-1.5 rounded-2xl mb-6 border border-slate-200/80 overflow-x-auto no-scrollbar shadow-inner sticky top-0 z-20">
                <div class="flex items-center gap-1.5 min-w-max" id="settingTabs" role="tablist">
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 bg-white text-blue-600 shadow-sm" id="profil-tab" data-ui-toggle="tab" data-ui-target="#profil" aria-selected="true">
                        <i class="bi bi-building"></i> Profil Sekolah
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="jadwal-tab" data-ui-toggle="tab" data-ui-target="#jadwal" aria-selected="false">
                        <i class="bi bi-calendar-week"></i> Jadwal &amp; Lokasi
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="wajah-tab" data-ui-toggle="tab" data-ui-target="#wajah" aria-selected="false">
                        <i class="bi bi-person-bounding-box"></i> Wajah &amp; Kamera
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="poin-tab" data-ui-toggle="tab" data-ui-target="#poin" aria-selected="false">
                        <i class="bi bi-calculator"></i> Poin &amp; Denda
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="aturan-tab" data-ui-toggle="tab" data-ui-target="#aturan" aria-selected="false">
                        <i class="bi bi-shield-check"></i> Batasan &amp; WFH
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="notifikasi-tab" data-ui-toggle="tab" data-ui-target="#notifikasi" aria-selected="false">
                        <i class="bi bi-bell"></i> Notifikasi &amp; OAuth
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="tampilan-tab" data-ui-toggle="tab" data-ui-target="#tampilan" aria-selected="false">
                        <i class="bi bi-palette"></i> Tampilan
                    </button>
                    <button type="button" class="tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60" id="maintenance-tab" data-ui-toggle="tab" data-ui-target="#maintenance" aria-selected="false">
                        <i class="bi bi-database-gear"></i> Backup &amp; Database
                    </button>
                </div>
            </div>

            <div class="tab-content-tw pt-1" id="settingTabContent">
                
                <!-- ==================================================== -->
                <!-- TAB 1: PROFIL & IDENTITAS SEKOLAH                    -->
                <!-- ==================================================== -->
                <div class="tab-pane tab-pane-tw show active" id="profil" role="tabpanel">
                    <?php
                    $satuanList = ["1" => ["SD", "MI"], "2" => ["SMP", "MTs"], "3" => ["SMA", "MA", "SMK"]];
                    $curJenjang = (string)($setting->jenjang ?? '3');
                    $activeSatuan = $satuanList[$curJenjang] ?? ["SMA", "MA", "SMK"];

                    $bankSoalDateTime = '';
                    if (!empty($setting->ba_waktu) && $setting->ba_waktu !== '0000-00-00 00:00:00') {
                        $dtObj = DateTime::createFromFormat('Y-m-d H:i:s', (string) $setting->ba_waktu);
                        if ($dtObj instanceof DateTime) {
                            $bankSoalDateTime = $dtObj->format('Y-m-d\TH:i');
                        }
                    }
                    ?>
                    
                    <!-- Form Text Identitas -->
                    <form id="profileForm" class="space-y-6">
                        <?= csrf_field() ?>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Card 1: Identitas Pokok Sekolah -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                            <i class="bi bi-building text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Identitas Lembaga &amp; Aplikasi</h6>
                                            <p class="text-xs text-slate-500 mb-0">Nama resmi sekolah dan jenjang satuan pendidikan</p>
                                        </div>
                                    </div>
                                    <span class="badge-tw badge-primary text-[10px]">Wajib</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="form-label-tw">Nama Aplikasi <span class="text-red-500">*</span></label>
                                        <input type="text" name="nama_aplikasi" class="form-control-tw" value="<?= esc($setting->nama_aplikasi ?? 'absensi by ikasmedia') ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label-tw">Nama Sekolah / Madrasah <span class="text-red-500">*</span></label>
                                        <input type="text" name="nama_sekolah" class="form-control-tw" value="<?= esc($setting->sekolah ?? '') ?>" required>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="form-label-tw">NPSN</label>
                                            <input type="text" name="npsn" class="form-control-tw" value="<?= esc($setting->npsn ?? '') ?>" pattern="[0-9]{8}" title="8 digit angka" placeholder="Contoh: 12345678">
                                        </div>
                                        <div>
                                            <label class="form-label-tw">NSS / NSM</label>
                                            <input type="text" name="nss" class="form-control-tw" value="<?= esc($setting->nss ?? '') ?>" placeholder="Nomor statistik sekolah">
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Kode Sekolah</label>
                                            <input type="text" name="kode_sekolah" id="kodeSekolahInput" class="form-control-tw" value="<?= esc($setting->kode_sekolah ?? \App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH) ?>" maxlength="50" placeholder="SMAN2.AT">
                                            <span class="text-xs text-slate-400 mt-1 block">Dipakai pada nomor surat teguran.</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label-tw">Jenjang <span class="text-red-500">*</span></label>
                                            <select id="jenjangSelect" class="form-select-tw" name="jenjang" required>
                                                <option value="1" <?= $curJenjang === '1' ? 'selected' : '' ?>>SD / MI</option>
                                                <option value="2" <?= $curJenjang === '2' ? 'selected' : '' ?>>SMP / MTs</option>
                                                <option value="3" <?= $curJenjang === '3' ? 'selected' : '' ?>>SMA / MA / SMK</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Satuan Pendidikan <span class="text-red-500">*</span></label>
                                            <select id="satuanSelect" class="form-select-tw" name="satuan_pendidikan" required>
                                                <?php foreach ($activeSatuan as $idx => $label): 
                                                    $val = $idx + 1;
                                                    $selected = ((string)($setting->satuan_pendidikan ?? '')) === (string)$val ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Alamat & Wilayah -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-indigo-100/80 rounded-xl p-2.5 text-indigo-600">
                                            <i class="bi bi-geo-alt text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Alamat &amp; Domisili</h6>
                                            <p class="text-xs text-slate-500 mb-0">Lokasi geografis fisik instansi</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="form-label-tw">Alamat Lengkap Jalan <span class="text-red-500">*</span></label>
                                        <textarea name="alamat" rows="2" class="form-control-tw" placeholder="Nama jalan, nomor, RT/RW" required><?= esc($setting->alamat ?? '') ?></textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label-tw">Desa / Kelurahan <span class="text-red-500">*</span></label>
                                            <input type="text" name="desa" class="form-control-tw" value="<?= esc($setting->desa ?? '') ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Kecamatan <span class="text-red-500">*</span></label>
                                            <input type="text" name="kec" class="form-control-tw" value="<?= esc($setting->kecamatan ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="form-label-tw">Kabupaten / Kota <span class="text-red-500">*</span></label>
                                            <input type="text" name="kota" class="form-control-tw" value="<?= esc($setting->kota ?? '') ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Provinsi <span class="text-red-500">*</span></label>
                                            <input type="text" name="provinsi" class="form-control-tw" value="<?= esc($setting->provinsi ?? '') ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Kode Pos</label>
                                            <input type="text" name="kode_pos" class="form-control-tw" value="<?= esc($setting->kode_pos ?? '') ?>" placeholder="5 digit">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Kontak Resmi -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-emerald-100/80 rounded-xl p-2.5 text-emerald-600">
                                            <i class="bi bi-telephone text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Kontak Resmi Lembaga</h6>
                                            <p class="text-xs text-slate-500 mb-0">Saluran telepon, email, dan situs web sekolah</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label-tw">Nomor Telepon</label>
                                            <input type="tel" name="tlp" class="form-control-tw" value="<?= esc($setting->telp ?? '') ?>" placeholder="021-xxxxxx">
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Faksimili</label>
                                            <input type="text" name="fax" class="form-control-tw" value="<?= esc($setting->fax ?? '') ?>" placeholder="Nomor fax">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label-tw">Email Resmi</label>
                                            <input type="email" name="email" class="form-control-tw" value="<?= esc($setting->email ?? '') ?>" placeholder="admin@sekolah.sch.id">
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Website Resmi</label>
                                            <input type="url" name="web" class="form-control-tw" value="<?= esc($setting->web ?? '') ?>" placeholder="https://sekolah.sch.id">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4: Penanggung Jawab & Parameter absensi by ikasmedia/AI -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-purple-100/80 rounded-xl p-2.5 text-purple-600">
                                            <i class="bi bi-person-badge text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Pimpinan &amp; Opsi Sistem</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label-tw">Kepala Sekolah <span class="text-red-500">*</span></label>
                                            <input type="text" name="kepsek" class="form-control-tw" value="<?= esc($setting->kepsek ?? '') ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">NIP Kepala Sekolah</label>
                                            <input type="text" name="nip" class="form-control-tw" value="<?= esc($setting->nip ?? '') ?>" placeholder="NIP / NUPTK">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label-tw">API Key AI (OpenAI / Gemini)</label>
                                        <input type="password" name="api_key" class="form-control-tw" value="<?= esc($setting->api_key ?? '') ?>" placeholder="sk-...">
                                        <span class="text-xs text-slate-400 mt-1 block">Digunakan untuk fitur pembuatan soal otomatis berbasis kecerdasan buatan.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 5: Format Penomoran Surat Teguran -->
                            <div class="setting-card overflow-hidden lg:col-span-2">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600">
                                            <i class="bi bi-upc-scan text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Format Penomoran Surat Teguran</h6>
                                            <p class="text-xs text-slate-500 mb-0">Atur pola nomor surat yang tercetak pada PDF dan daftar surat teguran</p>
                                        </div>
                                    </div>
                                    <span class="badge-tw badge-primary text-[10px]">Surat Teguran</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label-tw">Format Nomor</label>
                                            <input type="text" name="surat_teguran_format" id="teguranFormatInput" class="form-control-tw font-mono" maxlength="120"
                                                value="<?= esc($setting->surat_teguran_format ?? \App\Libraries\SuratNomor::DEFAULT_FORMAT) ?>"
                                                placeholder="<?= esc(\App\Libraries\SuratNomor::DEFAULT_FORMAT) ?>">
                                            <span class="text-xs text-slate-400 mt-1 block">Gunakan placeholder di bawah. Kosongkan untuk memakai format bawaan.</span>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Jumlah Digit Nomor Urut</label>
                                            <select name="surat_teguran_padding" id="teguranPaddingInput" class="form-select-tw">
                                                <?php $curPad = (int) ($setting->surat_teguran_padding ?? \App\Libraries\SuratNomor::DEFAULT_PADDING);
                                                if ($curPad < 1 || $curPad > 6) { $curPad = \App\Libraries\SuratNomor::DEFAULT_PADDING; } ?>
                                                <?php for ($p = 1; $p <= 6; $p++): ?>
                                                    <option value="<?= $p ?>" <?= $curPad === $p ? 'selected' : '' ?>><?= $p ?> digit<?= $p === 3 ? ' (standar)' : '' ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <span class="text-xs text-slate-400 mt-1 block">Contoh: 3 digit menghasilkan 001, 002, 003.</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label-tw">Pratinjau Nomor</label>
                                        <div class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-mono text-sm text-slate-800 break-all" id="teguranPreview">
                                            <?= esc(\App\Libraries\SuratNomor::preview(
                                                (string) ($setting->surat_teguran_format ?? \App\Libraries\SuratNomor::DEFAULT_FORMAT),
                                                (int) ($setting->surat_teguran_padding ?? \App\Libraries\SuratNomor::DEFAULT_PADDING),
                                                (string) ($setting->kode_sekolah ?? \App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH),
                                                1
                                            )) ?>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label-tw">Placeholder yang Tersedia</label>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach (\App\Libraries\SuratNomor::placeholders() as $ph => $desc): ?>
                                                <button type="button" class="teguran-ph-btn px-2.5 py-1 rounded-lg text-[11px] font-mono bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 transition-colors"
                                                    title="<?= esc($desc) ?>" data-ph="<?= esc($ph) ?>"><?= esc($ph) ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-xs text-slate-400 mt-2 block">Klik placeholder untuk menyisipkannya ke field format. Perubahan berlaku untuk surat yang terbit setelah disimpan.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar Simpan Profil -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" id="btnSaveProfile" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all">
                                <i class="bi bi-save"></i> Simpan Profil Sekolah
                            </button>
                        </div>
                    </form>

                    <!-- Card: Identitas Visual & Media Upload -->
                    <div class="setting-card overflow-hidden mt-8 mb-6">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600">
                                    <i class="bi bi-images text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Identitas Visual &amp; Dokumen Branding</h6>
                                    <p class="text-xs text-slate-500 mb-0">Unggah logo resmi, cap stempel, tanda tangan digital, dan kop surat sekolah (Format: JPG/PNG/WEBP, Maks. 5MB)</p>
                                </div>
                            </div>
                            <span class="badge-tw badge-warning text-[10px]">Realtime Upload</span>
                        </div>
                        <div class="p-6">
                            <?php
                            $dropifyUploads = [
                                ['field' => 'logo_kiri',     'label' => 'Logo Kiri / Aplikasi', 'desc' => 'Logo sidebar & navbar'],
                                ['field' => 'logo_kanan',    'label' => 'Logo Kanan / Yayasan', 'desc' => 'Logo instansi pembina'],
                                ['field' => 'stampel',       'label' => 'Cap / Stempel Sekolah','desc' => 'Stempel resmi dokumen (PNG)'],
                                ['field' => 'tanda_tangan',  'label' => 'TTD Kepala Sekolah',   'desc' => 'Tanda tangan digital kepsek'],
                                ['field' => 'kopsekolah',    'label' => 'Kop Surat Sekolah',    'desc' => 'Kop resmi cetak rapor & surat'],
                            ];
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                                <?php foreach ($dropifyUploads as $item):
                                    // Tampilkan preview hanya bila file benar-benar ada di disk.
                                    // Path basi di DB (file sudah hilang) dirender kosong, bukan overlay gelap rusak.
                                    $rawPath = trim((string) ($setting->{$item['field']} ?? ''));
                                    $relPath = ltrim(str_replace(base_url(), '', $rawPath), '/');
                                    $fileVal = ($rawPath !== '' && strpos($relPath, '..') === false
                                        && strpos($relPath, 'uploads/') === 0
                                        && is_file(FCPATH . $relPath))
                                        ? base_url($relPath)
                                        : '';
                                ?>
                                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 flex flex-col justify-between hover:border-slate-300 transition-colors">
                                    <div class="mb-2.5">
                                        <label class="font-bold text-xs text-slate-800 block mb-0.5"><?= esc($item['label']) ?></label>
                                        <span class="text-[11px] text-slate-400 block truncate"><?= esc($item['desc']) ?></span>
                                    </div>
                                    <input type="file" name="logo" class="dropify" data-field="<?= $item['field'] ?>"
                                           data-max-file-size="5M" data-allowed-file-extensions="jpg jpeg png webp"
                                           data-default-file="<?= $fileVal ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Setting Presensi Wrapping Tab 2 to 6 -->
                <form id="settingForm" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- ==================================================== -->
                    <!-- TAB 2: JADWAL KERJA & LOKASI GEOFENCE                -->
                    <!-- ==================================================== -->
                    <div class="tab-pane tab-pane-tw" id="jadwal" role="tabpanel">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <!-- Jadwal Kerja Mingguan Table -->
                            <div class="lg:col-span-2">
                                <div class="setting-card overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-2 flex-wrap bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                                <i class="bi bi-calendar-check text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">Jadwal Kerja Mingguan GTK</h6>
                                                <p class="text-xs text-slate-500 mb-0">Atur jam masuk, jam pulang, dan status hari libur</p>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-tw btn-tw-sm btn-outline-primary" id="btnCopyMon">
                                            <i class="bi bi-copy mr-1"></i> Terapkan Senin ke Semua
                                        </button>
                                    </div>
                                    <div class="p-0">
                                        <div class="overflow-x-auto">
                                            <table class="table-tw w-full">
                                                <thead class="table-tw">
                                                    <tr>
                                                        <th class="w-32">Hari</th>
                                                        <th>Jam Masuk</th>
                                                        <th>Jam Pulang</th>
                                                        <th class="text-center w-28">Status Libur</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php 
                                                    $days = [1=>'Senin', 2=>'Selasa', 3=>'Rabu', 4=>'Kamis', 5=>'Jumat', 6=>'Sabtu', 7=>'Minggu'];
                                                    foreach($days as $k => $dayName): 
                                                        $row = null;
                                                        if(isset($jadwal)) {
                                                            foreach($jadwal as $j) {
                                                                if($j->hari == $k) { $row = $j; break; }
                                                            }
                                                        }
                                                        $jamMasuk = $row ? $row->jam_masuk : '07:30';
                                                        $jamPulang = $row ? $row->jam_pulang : '15:00';
                                                        $isLibur = $row ? $row->is_libur : ($k==7?1:0);
                                                    ?>
                                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                                        <td class="font-semibold text-slate-800 flex items-center gap-2">
                                                            <span class="w-2 h-2 rounded-full <?= $k == 7 ? 'bg-red-500' : 'bg-blue-500' ?>"></span>
                                                            <?= $dayName ?>
                                                        </td>
                                                        <td>
                                                            <input type="time" class="form-control-tw py-1.5 text-sm" 
                                                                name="jadwal[<?= $k ?>][jam_masuk]" id="masuk_<?= $k ?>" value="<?= substr($jamMasuk, 0, 5) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="time" class="form-control-tw py-1.5 text-sm" 
                                                                name="jadwal[<?= $k ?>][jam_pulang]" id="pulang_<?= $k ?>" value="<?= substr($jamPulang, 0, 5) ?>">
                                                        </td>
                                                        <td class="text-center">
                                                            <label class="custom-switch my-auto">
                                                                <input type="checkbox" name="jadwal[<?= $k ?>][is_libur]" value="1" <?= $isLibur ? 'checked' : '' ?>>
                                                                <span class="custom-switch-slider"></span>
                                                            </label>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="px-6 py-3 bg-blue-50/60 border-t border-blue-100 flex items-center gap-2 text-xs text-blue-700">
                                        <i class="bi bi-info-circle shrink-0"></i>
                                        Pada hari yang ditandai <b>Libur</b>, guru tidak diwajibkan presensi dan tidak akan terkena sanksi alpa/denda.
                                    </div>
                                </div>
                            </div>

                            <!-- Toleransi & Geofence GPS. Status hari libur TIDAK
                                 ada di sini: diatur lewat toggle "Status Libur" pada
                                 Jadwal Kerja Mingguan GTK di atas (satu sumber
                                 kebenaran), lalu absensi_setting.hari_libur_mingguan
                                 diturunkan otomatis saat disimpan. -->
                            <div class="space-y-6">
                                <!-- Toleransi Batas Waktu Keterlambatan (Grace Period) -->
                                <div class="setting-card overflow-hidden border-l-4 border-l-amber-500 shadow-sm">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600 shrink-0">
                                                <i class="bi bi-clock-history text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">Toleransi Batas Waktu Absensi</h6>
                                                <p class="text-xs text-slate-500 mb-0">Kelonggaran menit sebelum absensi dinyatakan terlambat</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0" id="badge-grace-preview">
                                            <i class="bi bi-stopwatch"></i> <span id="text-grace-preview"><?= (int) ($absenSetting->grace_period ?? 15) ?> Menit</span>
                                        </span>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="form-label-tw" for="input_grace_period">Toleransi Keterlambatan (Grace Period)</label>
                                            <div class="flex items-center gap-2">
                                                <div class="relative flex-1">
                                                    <i class="bi bi-stopwatch absolute left-3 top-3 text-slate-400"></i>
                                                    <input type="number" class="form-control-tw pl-9 font-bold text-slate-800 text-base" 
                                                        id="input_grace_period" name="grace_period" 
                                                        value="<?= esc($absenSetting->grace_period ?? 15) ?>" min="0" max="120" step="1" required>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-3.5 py-2 rounded-xl border border-slate-200 shrink-0">Menit</span>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1.5 mb-0">Atur 0 jika ingin langsung tercatat terlambat tepat pada jam masuk tanpa toleransi.</p>
                                        </div>

                                        <!-- Simulasi Interaktif Perhitungan -->
                                        <div class="p-4 rounded-2xl border border-amber-200/80 bg-amber-50/70 text-xs text-amber-900 space-y-2.5">
                                            <div class="font-bold flex items-center gap-1.5 text-amber-800 text-xs">
                                                <i class="bi bi-info-circle-fill text-amber-600"></i> Contoh Simulasi Batas Waktu Masuk:
                                            </div>
                                            <div class="bg-white/90 rounded-xl p-3 border border-amber-200/80 text-slate-700 leading-relaxed font-mono text-[11px] space-y-1" id="grace-simulation-box">
                                                <div class="text-slate-600 font-sans font-semibold pb-1 border-b border-slate-100 flex items-center justify-between">
                                                    <span>Jam Masuk: <strong id="sim-jam-masuk" class="text-slate-800">07:15</strong></span>
                                                    <span>Toleransi: <strong id="sim-toleransi" class="text-amber-700"><?= (int) ($absenSetting->grace_period ?? 15) ?> Menit</strong></span>
                                                </div>
                                                <div class="pt-1 flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    <span>Absensi s/d <strong class="text-emerald-700 font-bold font-mono" id="sim-batas-ontime">07:30</strong>:</span>
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-sans font-bold">Tepat Waktu (Belum Terlambat)</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                                    <span>Absensi mulai <strong class="text-red-700 font-bold font-mono" id="sim-batas-late">07:31</strong>:</span>
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-red-100 text-red-800 text-[10px] font-sans font-bold">Terlambat</span>
                                                </div>
                                            </div>
                                            <p class="text-[11px] text-amber-700 mb-0 font-sans">
                                                Aturan batas toleransi ini otomatis tersinkronisasi ke seluruh sistem presensi (GTK dan Siswa).
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Radius & Geofence GPS -->
                                <div class="setting-card overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                        <div class="bg-rose-100/80 rounded-xl p-2 text-rose-600">
                                            <i class="bi bi-geo-alt-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Radius &amp; Geofence GPS</h6>
                                            <p class="text-xs text-slate-500 mb-0">Toleransi jarak presensi dari titik sekolah</p>
                                        </div>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="form-label-tw">Toleransi Jarak Absen</label>
                                            <div class="flex items-center gap-2">
                                                <div class="relative flex-1">
                                                    <i class="bi bi-broadcast absolute left-3 top-3 text-slate-400"></i>
                                                    <input type="number" class="form-control-tw pl-9" name="radius_meter" 
                                                        value="<?= esc($absenSetting->radius_meter ?? 100) ?>" min="10" max="5000" required>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-3 py-2 rounded-xl border border-slate-200">Meter</span>
                                            </div>
                                            <span class="text-xs text-slate-400 mt-1 block">Jarak maksimal guru dari koordinat sekolah (standar: 100m).</span>
                                        </div>

                                        <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/50">
                                            <div class="font-semibold text-slate-800 text-xs mb-1 flex items-center gap-1.5">
                                                <i class="bi bi-pin-map text-blue-600"></i> Titik Lokasi Kampus / Kantor
                                            </div>
                                            <p class="text-xs text-slate-500 mb-3">Kelola koordinat GPS multi-lokasi kantor cabang atau kampus pembelajaran.</p>
                                            <a href="<?= base_url('admin/presensi/lokasi') ?>" class="btn-tw btn-tw-sm btn-primary w-full justify-center">
                                                <i class="bi bi-map mr-1"></i> Kelola Titik Lokasi (<?= count($locations ?? []) ?> Lokasi)
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrasi Jadwal & Jurnal Mengajar -->
                        <div class="setting-card overflow-hidden mt-6">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                <div class="bg-indigo-100/80 rounded-xl p-2.5 text-indigo-600">
                                    <i class="bi bi-journal-check text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Integrasi Jadwal &amp; Jurnal Mengajar</h6>
                                    <p class="text-xs text-slate-500 mb-0">Syarat jurnal sebelum guru diizinkan absen pulang</p>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                    <div class="pr-4">
                                        <span class="font-bold block text-sm text-slate-800">Wajib Isi Jurnal Sebelum Absen Pulang</span>
                                        <span class="text-xs text-slate-500">
                                            Guru yang memiliki jadwal mengajar hari ini wajib mengisi jurnal untuk setiap sesi yang sudah selesai sebelum absen pulang.
                                            Guru tanpa jadwal, sesi yang belum selesai, hari libur, dan absen Dinas dikecualikan.
                                        </span>
                                    </div>
                                    <label class="custom-switch shrink-0 ml-3">
                                        <input type="checkbox" name="wajib_jurnal_sebelum_pulang" value="1" <?= (int) ($absenSetting->wajib_jurnal_sebelum_pulang ?? 1) === 1 ? 'checked' : '' ?>>
                                        <span class="custom-switch-slider"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 mt-3 ml-4 sm:ml-6 rounded-xl border border-slate-200 bg-white">
                                    <div class="pr-4">
                                        <span class="font-bold block text-sm text-slate-800">Jurnal Harus Sudah Divalidasi</span>
                                        <span class="text-xs text-slate-500">
                                            Hanya jurnal berstatus <strong>Disetujui</strong> yang dihitung memenuhi syarat; jurnal yang masih
                                            menunggu validasi atau ditolak tetap menahan absen pulang.
                                            Yang memvalidasi adalah guru piket hari itu, jadi pastikan jadwal piket terisi &mdash; tanpa itu tidak ada
                                            guru yang bisa pulang meski jurnalnya sudah diisi.
                                            Aktif hanya bila sakelar di atas juga aktif.
                                        </span>
                                    </div>
                                    <label class="custom-switch shrink-0 ml-3">
                                        <input type="checkbox" name="wajib_jurnal_tervalidasi_sebelum_pulang" value="1" <?= (int) ($absenSetting->wajib_jurnal_tervalidasi_sebelum_pulang ?? 0) === 1 ? 'checked' : '' ?>>
                                        <span class="custom-switch-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 3: VERIFIKASI BIOMETRIK & KAMERA                 -->
                    <!-- ==================================================== -->
                    <div class="tab-pane tab-pane-tw" id="wajah" role="tabpanel">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- AI Face Recognition -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-cyan-100/80 rounded-xl p-2.5 text-cyan-600">
                                            <i class="bi bi-person-bounding-box text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Biometrik Wajah AI (Face Recognition)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Model neural network pencocokan wajah guru</p>
                                        </div>
                                    </div>
                                    <span class="badge-tw badge-info text-[10px]">ResNet-34 AI</span>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="alert-tw alert-info flex items-center gap-3">
                                        <i class="bi bi-cpu text-2xl text-blue-600 shrink-0"></i>
                                        <div class="text-xs">
                                            Sistem menggunakan modul <b>Face-API AI (128D Face Descriptor)</b> untuk membandingkan wajah selfie dengan foto profil terdaftar secara real-time di browser.
                                        </div>
                                    </div>

                                    <!-- Mode Verifikasi Radio Cards -->
                                    <div>
                                        <label class="form-label-tw">Mode Kamera Face ID</label>
                                        <?php $matchMode = $absenSetting->face_match_mode ?? 'ai_match'; ?>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <label class="p-3.5 border-2 rounded-xl block cursor-pointer transition-all <?= $matchMode === 'ai_match' ? 'border-blue-500 bg-blue-50/50 text-blue-900' : 'border-slate-200 hover:border-slate-300' ?>">
                                                <input class="sr-only" type="radio" name="face_match_mode" value="ai_match" <?= $matchMode === 'ai_match' ? 'checked' : '' ?>>
                                                <div class="font-bold text-sm flex items-center gap-2">
                                                    <i class="bi bi-shield-check text-blue-600"></i> Selfie + AI Match
                                                </div>
                                                <span class="text-xs text-slate-500 block mt-1">Verifikasi kecocokan wajah selfie dengan foto profil guru secara biometrik.</span>
                                            </label>
                                            <label class="p-3.5 border-2 rounded-xl block cursor-pointer transition-all <?= $matchMode === 'selfie_only' ? 'border-blue-500 bg-blue-50/50 text-blue-900' : 'border-slate-200 hover:border-slate-300' ?>">
                                                <input class="sr-only" type="radio" name="face_match_mode" value="selfie_only" <?= $matchMode === 'selfie_only' ? 'checked' : '' ?>>
                                                <div class="font-bold text-sm flex items-center gap-2">
                                                    <i class="bi bi-camera text-slate-600"></i> Hanya Foto Selfie
                                                </div>
                                                <span class="text-xs text-slate-500 block mt-1">Ambil bukti foto selfie saja tanpa komparasi embedding wajah.</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Sensitivitas / Threshold -->
                                    <?php $currentThreshold = (float)($absenSetting->face_threshold ?? 0.72); ?>
                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="form-label-tw mb-0">Toleransi Kemiripan (Match Threshold)</label>
                                            <span class="badge-tw badge-primary font-mono text-xs px-2.5 py-0.5" id="thresholdBadge"><?= number_format($currentThreshold, 2) ?></span>
                                        </div>

                                        <input type="range" class="w-full accent-blue-600 cursor-pointer" id="thresholdRange" min="0.55" max="0.85" step="0.01" 
                                            value="<?= $currentThreshold ?>" oninput="updateThreshold(this.value)">
                                        <input type="hidden" name="face_threshold" id="faceThresholdInput" value="<?= $currentThreshold ?>">

                                        <div class="flex justify-between text-[11px] text-slate-400 mt-1 mb-3">
                                            <span>0.55 (Ketat)</span>
                                            <span class="font-bold text-blue-600">0.72 (Rekomendasi)</span>
                                            <span>0.85 (Longgar)</span>
                                        </div>

                                        <!-- Quick Presets -->
                                        <div class="flex gap-2 flex-wrap">
                                            <button type="button" class="btn-tw btn-tw-sm <?= abs($currentThreshold - 0.65) < 0.01 ? 'btn-primary' : 'btn-light' ?> text-xs py-1" onclick="setPreset(0.65)">
                                                Ketat (0.65)
                                            </button>
                                            <button type="button" class="btn-tw btn-tw-sm <?= abs($currentThreshold - 0.72) < 0.01 ? 'btn-primary' : 'btn-light' ?> text-xs py-1" onclick="setPreset(0.72)">
                                                ⭐ Rekomendasi (0.72)
                                            </button>
                                            <button type="button" class="btn-tw btn-tw-sm <?= abs($currentThreshold - 0.78) < 0.01 ? 'btn-primary' : 'btn-light' ?> text-xs py-1" onclick="setPreset(0.78)">
                                                Fleksibel (0.78)
                                            </button>
                                        </div>

                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 mt-3" id="thresholdDesc">
                                            <div class="font-semibold text-slate-800" id="descTitle">Rekomendasi (0.72)</div>
                                            <p class="mb-0 mt-0.5" id="descDetail">Nilai seimbang terbaik untuk berbagai pencahayaan kamera smartphone (menerima variasi wajar kacamata &amp; hijab).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aturan Kamera & Foto Profil -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-indigo-100/80 rounded-xl p-2.5 text-indigo-600">
                                        <i class="bi bi-camera text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Kebijakan Kamera &amp; Foto Profil</h6>
                                        <p class="text-xs text-slate-500 mb-0">Ketentuan selfie pada jalur Face ID (absen QR tidak memakai kamera)</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Wajib Foto Selfie (Face ID)</span>
                                            <span class="text-xs text-slate-500">Berlaku hanya untuk absen masuk &amp; pulang via Face ID. Absen QR tidak memerlukan foto atau verifikasi wajah.</span>
                                        </div>
                                        <label class="custom-switch shrink-0 ml-3">
                                            <input type="checkbox" name="foto_wajib" value="1" <?= ($absenSetting->foto_wajib ?? true) ? 'checked' : '' ?>>
                                            <span class="custom-switch-slider"></span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Verifikasi Wajah Saat WFH</span>
                                            <span class="text-xs text-slate-500">Wajib memverifikasi kecocokan wajah jika melakukan presensi dari rumah (WFH).</span>
                                        </div>
                                        <label class="custom-switch shrink-0 ml-3">
                                            <input type="checkbox" name="wfh_wajib_wajah" value="1" <?= ($absenSetting->wfh_wajib_wajah ?? 1) ? 'checked' : '' ?>>
                                            <span class="custom-switch-slider"></span>
                                        </label>
                                    </div>

                                    <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/40">
                                        <div class="font-semibold text-slate-800 text-xs mb-1 flex items-center gap-1.5">
                                            <i class="bi bi-person-lines-fill text-indigo-600"></i> Pembaruan Foto Profil Guru
                                        </div>
                                        <p class="text-xs text-slate-500 mb-3">Pastikan guru memiliki foto profil jelas berlatar polos agar AI dapat mendeteksi landmark wajah secara akurat.</p>
                                        <a href="<?= site_url('dataguru') ?>" class="btn-tw btn-tw-sm btn-outline-primary">
                                            <i class="bi bi-people mr-1"></i> Buka Manajemen Data Guru
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 4: POIN, REWARD & SANKSI DENDA                   -->
                    <!-- ==================================================== -->
                    <div class="tab-pane tab-pane-tw" id="poin" role="tabpanel">
                        <div class="space-y-6">
                            
                            <!-- 1. Poin Dasar Kehadiran (Flat) -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-emerald-100/80 rounded-xl p-2.5 text-emerald-600">
                                            <i class="bi bi-plus-circle-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">1. Poin Kehadiran Harian (Reward Flat)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Poin tambahan yang diperoleh guru berdasarkan status kehadiran</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Hadir Tepat Waktu</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold text-emerald-600" name="point_hadir" 
                                                    value="<?= esc($absenSetting->point_hadir ?? 10) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Izin Resmi</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="point_izin" 
                                                    value="<?= esc($absenSetting->point_izin ?? 0) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Sakit Sah</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="point_sakit" 
                                                    value="<?= esc($absenSetting->point_sakit ?? 0) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Cuti Disetujui</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="point_cuti" 
                                                    value="<?= esc($absenSetting->point_cuti ?? 0) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Reward Datang Lebih Awal (Early Bird) -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                            <i class="bi bi-award-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">2. Reward Datang Lebih Awal (Early Bird Points)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Apresiasi kedisiplinan guru yang hadir sebelum jam masuk dimulai</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Tier 1 (1 - 15 Mnt Awal)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="reward_point_tier_1" 
                                                    value="<?= esc($absenSetting->reward_point_tier_1 ?? 1) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt/mnt</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Tier 2 (16 - 60 Mnt Awal)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="reward_point_tier_2" 
                                                    value="<?= esc($absenSetting->reward_point_tier_2 ?? 1) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt/mnt</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Tier 3 (&gt; 60 Mnt Awal)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="reward_point_tier_3" 
                                                    value="<?= esc($absenSetting->reward_point_tier_3 ?? 2) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt/mnt</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Batas Maksimal Reward</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw" name="reward_cap_menit" 
                                                    value="<?= esc($absenSetting->reward_cap_menit ?? 30) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Menit</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Sanksi & Poin Pelanggaran -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-red-100/80 rounded-xl p-2.5 text-red-600">
                                            <i class="bi bi-dash-circle-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">3. Poin Pelanggaran Dasar (Penalty Points)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Pengurangan poin kedisiplinan per kejadian pelanggaran</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Terlambat Masuk (LATE)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold text-amber-600" name="point_telat" 
                                                    value="<?= esc($absenSetting->point_telat ?? 1) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt / kejadian</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Alpha (Tanpa Keterangan)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold text-red-600" name="point_alpa" 
                                                    value="<?= esc($absenSetting->point_alpa ?? 5) ?>" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt / hari</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Tidak Absen Pulang (Lupa Check-Out)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold text-red-600" name="point_lupa_pulang" 
                                                    value="<?= esc($absenSetting->point_lupa_pulang ?? 5) ?>" min="0" max="100">
                                                <span class="text-xs font-semibold text-slate-500">pt / hari</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-3 mb-0">
                                        Poin pulang lebih awal mengikuti tier pada tabel "Tarif Progresif" di bawah, bukan tarif terpisah.
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-1.5 mb-0">
                                        "Tidak absen pulang" dipotong cron harian untuk hari yang <b>sudah lewat</b> dan jam
                                        pulangnya tetap kosong; guru yang absen pulang setelah jam kerja tidak dipotong.
                                        Hari WFH, dinas, izin, sakit, cuti, alpha, dan yang masih antre verifikasi dibebaskan.
                                        Nilai <b>0</b> mematikan aturan ini. Potongan dimulai sejak cron
                                        pertama kali menjalaninya — hari-hari lama yang jam pulangnya kosong tidak ikut dipotong ke belakang.
                                        Cron meninjau maksimal 60 hari ke belakang, jadi hari yang lebih tua dari itu tidak pernah terpotong
                                        mendadak setelah cron lama mati. Bila admin kemudian mengisi jam pulang untuk hari yang sudah
                                        terpotong, poinnya kembali penuh.
                                    </p>
                                </div>
                            </div>

                            <!-- 4. Tarif Denda Bertingkat (Tiered Penalties) -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600">
                                            <i class="bi bi-sort-numeric-down text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">4. Tarif Denda &amp; Poin Keterlambatan Berjenjang</h6>
                                            <p class="text-xs text-slate-500 mb-0">Skema denda rupiah dan poin per menit berdasarkan durasi telat</p>
                                        </div>
                                    </div>
                                    <span class="badge-tw badge-warning text-[10px]">Tiered System</span>
                                </div>
                                <div class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Tier 1 -->
                                        <div class="tier-box p-4">
                                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <span class="font-bold text-sm text-slate-800">Tier 1 (1 - 30 Menit)</span>
                                                <span class="badge-tw badge-secondary text-[10px]">Ringan</span>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="form-label-tw">Denda Rupiah / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-500">Rp</span>
                                                        <input type="number" class="form-control-tw font-semibold" id="cfg_denda_t1" name="denda_tier_1" 
                                                            value="<?= esc($absenSetting->denda_tier_1 ?? 100) ?>" oninput="calculateSimulation()">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label-tw">Poin Pelanggaran / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <input type="number" class="form-control-tw font-semibold" id="cfg_point_t1" name="point_tier_1" 
                                                            value="<?= esc($absenSetting->point_tier_1 ?? 1) ?>" oninput="calculateSimulation()">
                                                        <span class="text-xs text-slate-500">pt/mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tier 2 -->
                                        <div class="tier-box p-4">
                                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <span class="font-bold text-sm text-slate-800">Tier 2 (31 - 60 Menit)</span>
                                                <span class="badge-tw badge-warning text-[10px]">Sedang</span>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="form-label-tw">Denda Rupiah / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-500">Rp</span>
                                                        <input type="number" class="form-control-tw font-semibold" id="cfg_denda_t2" name="denda_tier_2" 
                                                            value="<?= esc($absenSetting->denda_tier_2 ?? 200) ?>" oninput="calculateSimulation()">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label-tw">Poin Pelanggaran / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <input type="number" class="form-control-tw font-semibold" id="cfg_point_t2" name="point_tier_2" 
                                                            value="<?= esc($absenSetting->point_tier_2 ?? 2) ?>" oninput="calculateSimulation()">
                                                        <span class="text-xs text-slate-500">pt/mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tier 3 -->
                                        <div class="tier-box p-4">
                                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <span class="font-bold text-sm text-slate-800">Tier 3 (&gt; 60 Menit)</span>
                                                <span class="badge-tw badge-danger text-[10px]">Berat</span>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="form-label-tw">Denda Rupiah / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-500">Rp</span>
                                                        <input type="number" class="form-control-tw font-semibold" id="cfg_denda_t3" name="denda_tier_3" 
                                                            value="<?= esc($absenSetting->denda_tier_3 ?? 500) ?>" oninput="calculateSimulation()">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label-tw">Poin Pelanggaran / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <input type="number" class="form-control-tw font-semibold" id="cfg_point_t3" name="point_tier_3" 
                                                            value="<?= esc($absenSetting->point_tier_3 ?? 5) ?>" oninput="calculateSimulation()">
                                                        <span class="text-xs text-slate-500">pt/mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                     <!-- 5. Batasan Denda & Alpha -->
                                     <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                                         <div class="font-bold text-xs text-slate-700 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                                             <i class="bi bi-shield-lock text-slate-500"></i> Batasan Maksimal &amp; Denda Alpha
                                         </div>
                                         <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                              <div>
                                                  <label class="form-label-tw">Denda Alpha / Hari (Flat)</label>
                                                  <div class="flex items-center gap-1.5">
                                                      <span class="text-xs font-bold text-slate-500">Rp</span>
                                                      <input type="number" class="form-control-tw font-bold" id="input_denda_alpha" name="denda_alpha_per_hari"
                                                          value="<?= esc($absenSetting->denda_alpha_per_hari ?? 5000) ?>" min="0" step="500">
                                                  </div>
                                                  <span class="text-[11px] text-slate-400 mt-1 block">Denda alpa flat per hari kerja, tanpa kelipatan.</span>
                                              </div>
                                             <div>
                                                 <label class="form-label-tw">Batas Denda Maksimal / Bulan</label>
                                                 <div class="flex items-center gap-1.5">
                                                     <span class="text-xs font-bold text-slate-500">Rp</span>
                                                     <input type="number" class="form-control-tw" name="max_denda_bulan" 
                                                         value="<?= esc($absenSetting->max_denda_bulan ?? 500000) ?>" min="0" step="5000">
                                                 </div>
                                                 <span class="text-[11px] text-slate-400 mt-1 block">Batas maksimal total denda guru per bulan (0 = tanpa batas).</span>
                                             </div>
                                             <div>
                                                 <label class="form-label-tw">Poin Lembur / Jam</label>
                                                 <div class="flex items-center gap-1.5">
                                                     <input type="number" class="form-control-tw" name="point_lembur_per_jam" 
                                                         value="<?= esc($absenSetting->point_lembur_per_jam ?? 5) ?>" min="0" max="100">
                                                     <span class="text-xs font-bold text-slate-500">Poin/Jam</span>
                                                 </div>
                                                 <span class="text-[11px] text-slate-400 mt-1 block">Reward poin per jam lembur yang disetujui.</span>
                                             </div>
                                         </div>

                                      </div>

                                    <!-- 6. Interactive Simulator Calculator -->
                                    <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50/60">
                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-2">
                                                <i class="bi bi-lightning-charge-fill text-blue-600"></i>
                                                <h6 class="font-bold text-slate-800 text-sm mb-0">Simulator Live Perhitungan Denda</h6>
                                            </div>
                                            <span class="badge-tw badge-primary text-[10px]">Simulasi Interaktif</span>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                            <div>
                                                <label class="form-label-tw">Simulasi Keterlambatan (Menit)</label>
                                                <input type="number" class="form-control-tw font-bold" id="sim_menit" value="45" min="1" max="300" oninput="calculateSimulation()">
                                            </div>
                                            <div class="p-3 bg-white rounded-xl border border-blue-100 shadow-sm text-center">
                                                <div class="text-xs text-slate-500 font-medium">Estimasi Denda Rupiah</div>
                                                <div class="text-lg font-bold text-red-600 mt-0.5" id="sim_res_denda">Rp 0</div>
                                            </div>
                                            <div class="p-3 bg-white rounded-xl border border-blue-100 shadow-sm text-center">
                                                <div class="text-xs text-slate-500 font-medium">Estimasi Poin Pelanggaran</div>
                                                <div class="text-lg font-bold text-amber-600 mt-0.5" id="sim_res_point">0 pt</div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-2.5" id="sim_breakdown">
                                            Breakdown perhitungan tier akan muncul otomatis sesuai perubahan angka di atas.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 7. Denda Siswa (Alpha & Bolos) -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-rose-100/80 rounded-xl p-2.5 text-rose-600">
                                            <i class="bi bi-bank text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">7. Denda Siswa (Alpha &amp; Bolos)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Tagihan rupiah per hari untuk siswa yang tidak masuk tanpa keterangan atau masuk tanpa absen pulang</p>
                                        </div>
                                    </div>
                                    <span class="badge-tw badge-danger text-[10px]">Presensi Siswa</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Denda Alpha / Hari</label>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-500">Rp</span>
                                                <input type="number" class="form-control-tw font-bold" name="denda_alpha_siswa_per_hari"
                                                    value="<?= esc($absenSetting->denda_alpha_siswa_per_hari ?? 0) ?>" min="0" step="500">
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 block">Ditagih saat status siswa ALPHA / TANPA KETERANGAN pada hari kerja. <b>0</b> = tidak ditagih.</span>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Denda Bolos / Hari</label>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-500">Rp</span>
                                                <input type="number" class="form-control-tw font-bold" name="denda_bolos_siswa_per_hari"
                                                    value="<?= esc($absenSetting->denda_bolos_siswa_per_hari ?? 0) ?>" min="0" step="500">
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 block">Ditagih saat siswa punya jam masuk tetapi tetap tanpa jam pulang setelah jam pulang lewat — orang yang sama dengan daftar "BOLOS — MASUK TANPA PULANG" di rekap piket.</span>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Batas Denda Siswa / Bulan</label>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-500">Rp</span>
                                                <input type="number" class="form-control-tw" name="max_denda_siswa_bulan"
                                                    value="<?= esc($absenSetting->max_denda_siswa_bulan ?? 0) ?>" min="0" step="5000">
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 block">Plafon total per siswa per bulan kalender (0 = tanpa batas). Terpisah dari batas denda guru di atas.</span>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-4 mb-0 leading-relaxed">
                                        Nominal tercatat pada baris presensi siswa (<i>Presensi &raquo; Rekap Siswa</i> kolom <b>Denda</b>).
                                        Hari yang sudah tertagih mempertahankan nominalnya, jadi mengubah tarif di sini tidak menulis ulang
                                        tagihan bulan-bulan sebelumnya — hanya hari-hari baru yang memakai tarif baru.
                                        Matikan fitur cukup dengan mengembalikan kedua tarif ke <b>0</b>; riwayat tagihan yang sudah tercetak tidak ikut terhapus.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 5: BATASAN & KEBIJAKAN WFH                       -->
                    <!-- ==================================================== -->
                    <div class="tab-pane tab-pane-tw" id="aturan" role="tabpanel">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Batas Izin, Cuti & Standar Kehadiran -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-violet-100/80 rounded-xl p-2.5 text-violet-600">
                                        <i class="bi bi-shield-check text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Batas Kuota Izin, Cuti &amp; Kehadiran</h6>
                                        <p class="text-xs text-slate-500 mb-0">Pembatasan durasi permohonan dispensasi guru</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <p class="text-xs text-slate-500 mb-0 leading-relaxed">
                                        Kuota dihitung dari <b>hari kerja efektif</b> — akhir pekan dan hari libur
                                        tidak memotong kuota, karena hari-hari itu memang tidak menghasilkan
                                        catatan kehadiran.
                                    </p>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Kuota Izin / Tahun</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold" name="max_izin_hari"
                                                    value="<?= esc($absenSetting->max_izin_hari ?? 3) ?>" min="0" max="365">
                                                <span class="text-xs font-semibold text-slate-500">Hari</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">0 = tanpa batas. Akumulasi izin disetujui + masih menunggu.</p>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Kuota Cuti / Tahun</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold" name="max_cuti_hari"
                                                    value="<?= esc($absenSetting->max_cuti_hari ?? 12) ?>" min="0" max="365">
                                                <span class="text-xs font-semibold text-slate-500">Hari</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">0 = tanpa batas.</p>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="form-label-tw">Kuota Sakit / Tahun</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="form-control-tw font-bold" name="max_sakit_hari"
                                                    value="<?= esc($absenSetting->max_sakit_hari ?? 12) ?>" min="0" max="365">
                                                <span class="text-xs font-semibold text-slate-500">Hari</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">0 = tanpa batas. 12 = 2 minggu.</p>
                                        </div>
                                    </div>

                                    <div class="border-t border-slate-100 pt-5">
                                        <h6 class="font-bold text-slate-700 text-sm mb-3">Maksimal durasi per pengajuan</h6>
                                        <p class="text-xs text-slate-500 mb-3 leading-relaxed">
                                            Batas ini terpisah dari kuota tahunan: mengatur durasi satu pengajuan,
                                            bukan total pemakaian setahun. Ini yang membuat “izin 3 hari” tidak lagi
                                            otomatis menghabiskan seluruh kuota 3 hari/tahun.
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="form-label-tw">Izin per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="form-control-tw font-bold" name="max_izin_per_pengajuan"
                                                        value="<?= esc($absenSetting->max_izin_per_pengajuan ?? 3) ?>" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                            </div>
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="form-label-tw">Cuti per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="form-control-tw font-bold" name="max_cuti_per_pengajuan"
                                                        value="<?= esc($absenSetting->max_cuti_per_pengajuan ?? 12) ?>" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                            </div>
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="form-label-tw">Sakit per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="form-control-tw font-bold" name="max_sakit_per_pengajuan"
                                                        value="<?= esc($absenSetting->max_sakit_per_pengajuan ?? 3) ?>" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-1 mb-0">Di atas 3 hari tetap wajib surat dokter.</p>
                                            </div>
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="form-label-tw">Dinas per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="form-control-tw font-bold" name="max_dinas_per_pengajuan"
                                                        value="<?= esc($absenSetting->max_dinas_per_pengajuan ?? 30) ?>" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-1 mb-0">Dinas tidak memakai kuota tahunan — tugas resmi dihitung sebagai hadir.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <i class="bi bi-calendar-check text-slate-500 text-lg mt-0.5"></i>
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Dasar perhitungan kuota</span>
                                            <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                                <input type="checkbox" name="kuota_hari_kerja" value="1"
                                                    <?= ((int) ($absenSetting->kuota_hari_kerja ?? 1) === 0) ? '' : 'checked' ?>>
                                                <span class="text-xs text-slate-600">Hitung hanya hari kerja efektif (hapus centang untuk memakai hitungan hari kalender)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50">
                                        <label class="form-label-tw text-amber-900 flex items-center gap-1.5">
                                            <i class="bi bi-percent text-amber-600"></i> Batas Minimum Kehadiran (Standar SMA/Disdik)
                                        </label>
                                        <div class="flex items-center gap-2 max-w-xs">
                                            <input type="number" class="form-control-tw font-bold" name="min_kehadiran_persen"
                                                value="<?= esc($absenSetting->min_kehadiran_persen ?? 90) ?>"
                                                min="50" max="100" step="0.5">
                                            <span class="text-sm font-bold text-amber-800">%</span>
                                        </div>
                                        <p class="text-xs text-amber-700 mt-2 mb-0">Standar minimal kehadiran bulanan (default 90%). Guru dengan persentase di bawah angka ini akan muncul di panel atensi khusus.</p>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
                                        <i class="bi bi-check-circle-fill text-emerald-500 text-lg mt-0.5"></i>
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Libur Kalender Nasional</span>
                                            <span class="text-xs text-slate-600">Selalu aktif — tidak ada sakelar. Tanggal merah nasional selalu dikeluarkan dari perhitungan kehadiran, rekap, dan denda.</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Alert Kehadiran Rendah</span>
                                            <span class="text-xs text-slate-500">Tampilkan indikator peringatan di monitoring jika kehadiran guru di bawah standar.</span>
                                        </div>
                                        <label class="custom-switch shrink-0 ml-3">
                                            <input type="checkbox" name="alert_kehadiran_enabled" value="1" <?= ($absenSetting->alert_kehadiran_enabled ?? 1) ? 'checked' : '' ?>>
                                            <span class="custom-switch-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Kebijakan WFH (Work From Home) -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-teal-100/80 rounded-xl p-2.5 text-teal-600">
                                        <i class="bi bi-laptop text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Kebijakan WFH (Work From Home)</h6>
                                        <p class="text-xs text-slate-500 mb-0">Bypass radius GPS untuk penugasan luar/rumah</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Aktifkan Fitur WFH</span>
                                            <span class="text-xs text-slate-500">Izinkan guru memilih opsi presensi bekerja dari rumah.</span>
                                        </div>
                                        <label class="custom-switch shrink-0 ml-3">
                                            <input type="checkbox" id="wfhEnabled" name="wfh_enabled" value="1" <?= ($absenSetting->wfh_enabled ?? 0) ? 'checked' : '' ?>>
                                            <span class="custom-switch-slider"></span>
                                        </label>
                                    </div>

                                    <div id="wfhSettings" class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4 <?= ($absenSetting->wfh_enabled ?? 0) ? '' : 'hidden' ?>">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label-tw">Jam Mulai WFH</label>
                                                <input type="time" class="form-control-tw" name="wfh_mulai"
                                                    value="<?= esc($absenSetting->wfh_mulai ?? '08:00') ?>">
                                            </div>
                                            <div>
                                                <label class="form-label-tw">Jam Selesai WFH</label>
                                                <input type="time" class="form-control-tw" name="wfh_selesai"
                                                    value="<?= esc($absenSetting->wfh_selesai ?? '16:00') ?>">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Batas Kuota WFH / Bulan</label>
                                            <div class="flex items-center gap-2 max-w-xs">
                                                <input type="number" class="form-control-tw font-bold" name="wfh_max_per_bulan"
                                                    value="<?= esc($absenSetting->wfh_max_per_bulan ?? 5) ?>" min="0" max="31">
                                                <span class="text-xs font-semibold text-slate-500">Hari / Guru</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <!-- Kebijakan Jam Operasional Scanner QR Siswa -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-rose-100/80 rounded-xl p-2.5 text-rose-600">
                                        <i class="bi bi-qr-code-scan text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Jam Operasional Scanner QR Siswa</h6>
                                        <p class="text-xs text-slate-500 mb-0">Scan <b>masuk</b> dibatasi jendela operasional + jadwal masuk (ala absen wajah); scan <b>pulang</b> dibuka begitu jendela masuk ditutup dan ditutup lagi oleh jendela operasional</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Batasi Jam Scan Masuk</span>
                                            <span class="text-xs text-slate-500">Matikan untuk scan 24 jam (tidak disarankan).</span>
                                        </div>
                                        <label class="custom-switch shrink-0 ml-3">
                                            <input type="checkbox" id="qrScanAktif" name="qr_scan_aktif" value="1" <?= ($absenSetting->qr_scan_aktif ?? 1) ? 'checked' : '' ?>>
                                            <span class="custom-switch-slider"></span>
                                        </label>
                                    </div>

                                    <div id="qrScanWindow" class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4 <?= ($absenSetting->qr_scan_aktif ?? 1) ? '' : 'hidden' ?>">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label-tw">Scan Dibuka Pukul</label>
                                                <input type="time" class="form-control-tw" name="qr_scan_mulai"
                                                    value="<?= esc(substr((string)($absenSetting->qr_scan_mulai ?? '05:30:00'), 0, 5)) ?>">
                                            </div>
                                            <div>
                                                <label class="form-label-tw">Scan Ditutup Pukul</label>
                                                <input type="time" class="form-control-tw" name="qr_scan_selesai"
                                                    value="<?= esc(substr((string)($absenSetting->qr_scan_selesai ?? '18:00:00'), 0, 5)) ?>">
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-slate-400">Contoh: 05:30–18:00 WITA. Berlaku untuk halaman <code>/scanner</code> &amp; <code>/guru/presensi-siswa</code>. Selain itu scan masuk mengikuti jadwal masuk hari ini (dibuka 1 jam sebelum, ditutup 3 jam setelah) dan scan pulang langsung dibuka setelah jendela masuk ditutup — sama seperti absen wajah. Jam selesai berlaku dua arah: lewat jam itu scan masuk <b>dan</b> pulang ditolak, dan cron <code>/cron/daily</code> menandai siswa yang masuk tanpa pulang sebagai &quot;pulang tidak tercatat&quot; (jam keluar tetap kosong, tidak diisi otomatis).</p>
                                        <div>
                                            <label class="form-label-tw">Token Kiosk /scanner (opsional)</label>
                                            <?php $tokenKioskAktif = trim((string) ($absenSetting->qr_kiosk_token ?? '')) !== ''; ?>
                                            <div class="mb-2">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold <?= $tokenKioskAktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                                                    <i class="bi <?= $tokenKioskAktif ? 'bi-shield-lock-fill' : 'bi-unlock' ?>"></i>
                                                    <?= $tokenKioskAktif ? 'Token terpasang — /scanner terkunci' : 'Belum ada token — /scanner terbuka untuk umum' ?>
                                                </span>
                                            </div>
                                            <input type="password" class="form-control-tw font-mono" name="qr_kiosk_token" value=""
                                                placeholder="<?= $tokenKioskAktif ? 'Tidak diubah — isi hanya untuk mengganti token' : 'Isi token untuk mengunci kiosk' ?>"
                                                maxlength="64" autocomplete="new-password">
                                            <?php if ($tokenKioskAktif): ?>
                                            <label class="flex items-center gap-2 text-[12px] text-slate-500 mt-2">
                                                <input type="checkbox" name="qr_kiosk_token_hapus" value="1" class="form-check-input">
                                                Hapus token (buka kembali /scanner untuk umum)
                                            </label>
                                            <?php endif; ?>
                                            <p class="text-[11px] text-slate-400 mt-1">Bila terpasang, <b>seluruh</b> jalur publik <code>/scanner</code> — halaman kiosk, <code>/scanner/scan</code>, dan <code>/scanner/history</code> — menolak permintaan tanpa token. Daftarkan perangkat kiosk sekali lewat <code>/scanner?k=TOKEN</code> atau kolom buka-kunci di halaman itu; setelahnya perangkat dikenali sendiri. Kolom ini write-only: nilai token tidak pernah ditampilkan kembali, dan menyimpan form tanpa mengisi kolom tidak mengubahnya.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 6: NOTIFIKASI GATEWAY & INTEGRASI OAUTH          -->
                    <!-- ==================================================== -->
                    <div class="tab-pane tab-pane-tw" id="notifikasi" role="tabpanel">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                
                                <!-- Bot Telegram Card -->
                                <div class="setting-card overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-sky-100/80 rounded-xl p-2.5 text-sky-600">
                                                <i class="bi bi-telegram text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">Notifikasi Bot Telegram</h6>
                                                <p class="text-xs text-slate-500 mb-0">Broadcast laporan absensi ke grup/channel</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div class="alert-tw alert-info text-xs">
                                            Kirim ringkasan harian dan alert keterlambatan otomatis. Dapatkan bot token gratis melalui <a href="https://t.me/BotFather" target="_blank" class="font-bold underline">@BotFather</a>.
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Bot Token</label>
                                            <input type="text" class="form-control-tw" name="telegram_bot_token" 
                                                value="<?= esc($absenSetting->telegram_bot_token ?? '') ?>" 
                                                placeholder="Contoh: 1234567890:AAHdqTcv...">
                                        </div>
                                        <div>
                                            <label class="form-label-tw">Chat ID Tujuan (Grup / Channel)</label>
                                            <input type="text" class="form-control-tw" name="telegram_chat_id" 
                                                value="<?= esc($absenSetting->telegram_chat_id ?? '') ?>" 
                                                placeholder="Contoh: -1001234567890">
                                        </div>
                                        <div class="pt-2">
                                            <button type="button" class="btn-tw btn-tw-sm btn-outline-primary" id="btnTestTelegram">
                                                <i class="bi bi-send mr-1"></i> Test Kirim Telegram
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- WhatsApp Gateway Card -->
                                <div class="setting-card overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-emerald-100/80 rounded-xl p-2.5 text-emerald-600">
                                                <i class="bi bi-whatsapp text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">WhatsApp Gateway</h6>
                                                <p class="text-xs text-slate-500 mb-0">Notifikasi ke nomor/grup WhatsApp</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="form-label-tw">Kanal aktif</label>
                                            <select class="form-control-tw" name="wa_gateway_mode" id="waGatewayMode">
                                                <?php
                                                $waMode = strtolower(trim((string) ($absenSetting->wa_gateway_mode ?? '')));
                                                if ($waMode === '') {
                                                    // Instalasi lama hanya punya Fonnte — jangan diam-diam jadi "off".
                                                    $waMode = (!empty($absenSetting->wa_token) && !empty($absenSetting->wa_target_number)) ? 'fonnte' : 'off';
                                                }
                                                ?>
                                                <option value="off" <?= $waMode === 'off' ? 'selected' : '' ?>>Nonaktif</option>
                                                <option value="openwa" <?= $waMode === 'openwa' ? 'selected' : '' ?>>OpenWA (self-hosted)</option>
                                                <option value="fonnte" <?= $waMode === 'fonnte' ? 'selected' : '' ?>>Fonnte</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="form-label-tw">Nomor / Grup WhatsApp tujuan laporan</label>
                                            <input type="text" class="form-control-tw" name="wa_target_number" 
                                                value="<?= esc($absenSetting->wa_target_number ?? '') ?>" 
                                                placeholder="081234567890 atau 120363...@g.us">
                                            <p class="text-xs text-slate-500 mt-1">
                                                Nomor Untuk grup tulis chat id OpenWA seperti <code>120363...@g.us</code>.
                                            </p>
                                        </div>

                                        <!-- Panel OpenWA -->
                                        <div id="panelOpenwa" class="space-y-4 hidden">
                                            <div>
                                                <label class="form-label-tw">Base URL OpenWA</label>
                                                <input type="url" class="form-control-tw" name="wa_base_url" 
                                                    value="<?= esc($absenSetting->wa_base_url ?? '') ?>" 
                                                    placeholder="http://localhost:2785">
                                            </div>
                                            <div>
                                                <label class="form-label-tw">API Key OpenWA</label>
                                                <input type="password" class="form-control-tw" name="wa_api_key" 
                                                    value="<?= esc($absenSetting->wa_api_key ?? '') ?>" 
                                                    placeholder="owa_k1_...">
                                                <p class="text-xs text-slate-500 mt-1">
                                                   Ambil dari dashboard OpenWA atau file <code>data/.api-key</code>.
                                                </p>
                                            </div>
                                            <div>
                                                <label class="form-label-tw">Nama Sesi (opsional)</label>
                                                <input type="text" class="form-control-tw" name="wa_session_name" 
                                                    value="<?= esc($absenSetting->wa_session_name ?? '') ?>" 
                                                    placeholder="ika-notif">
                                                <p class="text-xs text-slate-500 mt-1">Kosongkan untuk memakai sesi berstatus <code>ready</code> pertama.</p>
                                            </div>
                                        </div>

                                        <!-- Panel Fonnte -->
                                        <div id="panelFonnte" class="space-y-4 hidden">
                                            <div class="alert-tw alert-info text-xs">
                                                Integrasi via layanan gateway <a href="https://fonnte.com" target="_blank" class="font-bold underline text-emerald-600">Fonnte</a>.
                                            </div>
                                            <div>
                                                <label class="form-label-tw">Gateway API URL</label>
                                                <input type="url" class="form-control-tw" name="wa_gateway_url" 
                                                    value="<?= esc($absenSetting->wa_gateway_url ?? 'https://api.fonnte.com/send') ?>">
                                            </div>
                                            <div>
                                                <label class="form-label-tw">API Token</label>
                                                <input type="password" class="form-control-tw" name="wa_token" 
                                                    value="<?= esc($absenSetting->wa_token ?? '') ?>" 
                                                    placeholder="Masukkan token API Fonnte">
                                            </div>
                                        </div>

                                        <div class="pt-2 flex items-center gap-3">
                                            <button type="button" class="btn-tw btn-tw-sm btn-outline-success" id="btnTestWa">
                                                <i class="bi bi-send mr-1"></i> Test Kirim WhatsApp
                                            </button>
                                            <a href="<?= base_url('admin/presensi/whatsapp') ?>" class="btn-tw btn-tw-sm btn-outline-primary">
                                                <i class="bi bi-people mr-1"></i> Kelola Tautan Guru
                                            </a>
                                            <a href="<?= base_url('admin/presensi/notifikasi-status') ?>" class="btn-tw btn-tw-sm btn-outline-secondary">
                                                <i class="bi bi-activity mr-1"></i> Status Notifikasi
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Google OAuth Single Sign-On Integration Card -->
                            <div class="setting-card overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="22" height="22" alt="Google">
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Integrasi Login Akun Google (Single Sign-On)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Login instan bagi guru &amp; staf menggunakan akun Google Workspace / Gmail</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-slate-600">Aktifkan SSO</span>
                                        <label class="custom-switch">
                                            <input type="checkbox" id="googleLoginEnabled" name="google_login_enabled" value="1" <?= ($absenSetting->google_login_enabled ?? 1) ? 'checked' : '' ?>>
                                            <span class="custom-switch-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <div class="lg:col-span-2 space-y-4">
                                            <div>
                                                <label class="form-label-tw">Google Client ID</label>
                                                <input type="text" class="form-control-tw" name="google_client_id"
                                                    value="<?= esc($absenSetting->google_client_id ?? '') ?>"
                                                    placeholder="Contoh: 1234567890-xxx.apps.googleusercontent.com">
                                            </div>
                                            <div>
                                                <label class="form-label-tw">Google Client Secret</label>
                                                <input type="password" class="form-control-tw" name="google_client_secret"
                                                    value="<?= esc($absenSetting->google_client_secret ?? '') ?>"
                                                    placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx">
                                            </div>
                                            <div>
                                                <label class="form-label-tw">Authorized Redirect URI (Wajib didaftarkan di Google Console)</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="text" class="form-control-tw bg-slate-50 text-slate-600 font-mono text-xs" id="googleRedirectUri"
                                                        value="<?= site_url('presensi/google-callback') ?>" readonly>
                                                    <button class="btn-tw btn-outline-primary shrink-0" type="button" onclick="copyRedirectUri()">
                                                        <i class="bi bi-clipboard mr-1"></i> Salin
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-2 text-slate-600">
                                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                                <i class="bi bi-info-circle text-blue-600"></i> Panduan Singkat Google OAuth
                                            </div>
                                            <ol class="list-decimal pl-4 space-y-1 text-slate-500">
                                                <li>Buka Google Cloud Console di <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">console.cloud.google.com</a></li>
                                                <li>Pilih proyek &rarr; <b>Credentials</b> &rarr; <b>Create Credentials</b> &rarr; <b>OAuth Client ID</b></li>
                                                <li>Pilih type <b>Web Application</b></li>
                                                <li>Tempelkan URI di samping ke <b>Authorized redirect URIs</b></li>
                                                <li>Simpan dan salin Client ID &amp; Secret ke kolom di samping</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Bottom Action Bar for Attendance Settings -->
                    <div class="fixed-bottom bg-white/95 backdrop-blur-md border-t border-slate-200/80 p-4 shadow-xl fixed-bottom-bar" style="z-index: 1020; display: none;">
                        <div class="w-full flex justify-end items-center gap-3 max-w-7xl mx-auto">
                            <a href="<?= base_url('dashboard') ?>" class="btn-tw btn-light">
                                <i class="bi bi-arrow-left mr-1"></i> Dashboard
                            </a>
                            <button type="submit" id="btnSaveSetting" class="btn-tw btn-primary px-6 shadow-md shadow-blue-500/20">
                                <i class="bi bi-check2-circle mr-1"></i> Simpan Pengaturan Presensi
                            </button>
                        </div>
                    </div>
                    
                    <div style="height: 92px;"></div>
                </form>

                <!-- ==================================================== -->
                <!-- TAB 7: TAMPILAN / THEME CUSTOMIZER                   -->
                <!-- ==================================================== -->
                <div class="tab-pane tab-pane-tw" id="tampilan" role="tabpanel">
                    <?php
                    $curPreset = $theme['preset'] ?? 'default';
                    $curCustom = !empty($theme['custom']) ? $theme['custom'] : '#2563eb';
                    $customPalette = \App\Libraries\Theme::paletteFromHex($curCustom);
                    $themeJson = json_encode(
                        \App\Libraries\Theme::clientConfig(),
                        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                    );
                    ?>
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-2 space-y-6">
                            <div class="setting-card p-6">
                                <div class="flex items-start gap-4 mb-5">
                                    <div class="p-2.5 bg-primary-50 text-primary-600 rounded-xl">
                                        <i class="bi bi-palette text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800">Tema Warna Aplikasi</h4>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Pilih preset warna atau buat warna sendiri. Tema diterapkan ke panel admin,
                                            halaman login, desktop guru, serta aplikasi mobile guru &amp; siswa tanpa mengubah kode CSS.
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="themePresetGrid">
                                    <?php foreach ($themePresets as $key => $preset): ?>
                                        <button type="button" class="theme-card <?= $curPreset === $key ? 'selected' : '' ?>" data-theme-preset="<?= esc($key) ?>">
                                            <span class="theme-check"><i class="bi bi-check-lg"></i></span>
                                            <span class="grid grid-cols-3 gap-1.5 mb-3">
                                                <span class="theme-swatch" style="background: <?= esc($preset['colors']['brand']) ?>"></span>
                                                <span class="theme-swatch" style="background: <?= esc($preset['colors']['primary']) ?>"></span>
                                                <span class="theme-swatch" style="background: <?= esc($preset['colors']['accent']) ?>"></span>
                                            </span>
                                            <span class="block text-sm font-bold text-slate-800"><?= esc($preset['label']) ?></span>
                                            <span class="block text-[11px] leading-snug text-slate-500 mt-0.5"><?= esc($preset['desc']) ?></span>
                                        </button>
                                    <?php endforeach; ?>

                                    <button type="button" class="theme-card <?= $curPreset === 'custom' ? 'selected' : '' ?>" data-theme-preset="custom">
                                        <span class="theme-check"><i class="bi bi-check-lg"></i></span>
                                        <span class="grid grid-cols-3 gap-1.5 mb-3">
                                            <?php foreach ([50, 500, 700] as $shade): ?>
                                                <span class="theme-swatch" data-custom-swatch="<?= $shade ?>" style="background: <?= esc($customPalette[$shade]) ?>"></span>
                                            <?php endforeach; ?>
                                        </span>
                                        <span class="block text-sm font-bold text-slate-800">Custom</span>
                                        <span class="block text-[11px] leading-snug text-slate-500 mt-0.5">Pilih warna dasar sendiri; palet dibuat otomatis.</span>
                                    </button>
                                </div>

                                <div id="themeCustomPanel" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 <?= $curPreset === 'custom' ? '' : 'hidden' ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                                        <div>
                                            <label for="themeCustomColor" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Warna dasar</label>
                                            <input type="color" id="themeCustomColor" value="<?= esc($curCustom) ?>"
                                                class="h-11 w-16 cursor-pointer rounded-xl border border-slate-300 bg-white p-1">
                                        </div>
                                        <div class="flex-1 max-w-[180px]">
                                            <label for="themeCustomHex" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Kode HEX</label>
                                            <input type="text" id="themeCustomHex" value="<?= esc($curCustom) ?>" maxlength="7" spellcheck="false"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-mono text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Palet otomatis</div>
                                        <div class="grid grid-cols-10 gap-1" id="themeCustomSwatches"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 flex items-start gap-3">
                                <i class="bi bi-info-circle text-primary-500 mt-0.5"></i>
                                <p class="text-xs leading-relaxed text-slate-500">
                                    Warna status (hadir, izin, alpa) tetap menggunakan warna semantik agar mudah dibaca.
                                    Ekspor PDF dan Excel tidak terpengaruh tema.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="setting-card p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-slate-800 text-sm">Pratinjau Langsung</h4>
                                    <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Live</span>
                                </div>

                                <div class="rounded-xl overflow-hidden border border-slate-200">
                                    <div class="flex">
                                        <div class="w-20 bg-slate-900 p-2.5 space-y-1.5">
                                            <div class="h-2 w-8 rounded-full" style="background: var(--brand-400)"></div>
                                            <div class="h-1.5 w-12 rounded-full bg-white/25"></div>
                                            <div class="h-1.5 w-12 rounded-full" style="background: var(--brand-500)"></div>
                                            <div class="h-1.5 w-12 rounded-full bg-white/25"></div>
                                            <div class="h-1.5 w-9 rounded-full bg-white/25"></div>
                                        </div>
                                        <div class="flex-1 bg-slate-50 p-3 space-y-2">
                                            <div class="h-2 w-16 rounded-full" style="background: var(--brand-500)"></div>
                                            <div class="h-1.5 w-24 rounded-full bg-slate-200"></div>
                                            <div class="flex gap-1.5 pt-1">
                                                <div class="h-6 w-16 rounded-lg" style="background: var(--brand-600)"></div>
                                                <div class="h-6 w-16 rounded-lg" style="background: var(--primary-600)"></div>
                                                <div class="h-6 w-6 rounded-lg" style="background: var(--accent-500)"></div>
                                            </div>
                                            <div class="flex gap-1 pt-1">
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">Hadir</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">Izin</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-red-50 text-red-600">Alpa</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[11px] leading-relaxed text-slate-500 mt-3">
                                    Pratinjau memakai komponen contoh. Setelah disimpan, warna baru langsung dipakai di seluruh aplikasi.
                                </p>
                            </div>

                            <div class="setting-card p-5">
                                <button type="button" id="btnSaveTheme" class="btn-tw btn-primary w-full">
                                    <i class="bi bi-check2-circle mr-1"></i> Simpan Tema
                                </button>
                                <button type="button" id="btnResetTheme" class="btn-tw btn-light w-full mt-2">
                                    <i class="bi bi-arrow-counterclockwise mr-1"></i> Gunakan Default
                                </button>
                            </div>
                        </div>
                    </div>

                    <script type="application/json" id="themeClientConfig"><?= $themeJson ?></script>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 8: BACKUP & DATABASE MANAGER                     -->
                <!-- ==================================================== -->
                <div class="tab-pane tab-pane-tw" id="maintenance" role="tabpanel">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Unduh Data Backup -->
                        <div class="setting-card overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                    <i class="bi bi-cloud-arrow-down text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Ekspor &amp; Cadangan Database</h6>
                                    <p class="text-xs text-slate-500 mb-0">Unduh snapshot database sistem secara instan</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/80 hover:border-slate-300 transition-colors">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <i class="bi bi-filetype-sql text-blue-600 text-lg"></i> Database Penuh (SQL Dump)
                                        </div>
                                        <p class="text-xs text-slate-400 mb-0 mt-0.5">Skema tabel lengkap dan seluruh data presensi, master, serta pengaturan.</p>
                                    </div>
                                    <a href="<?= base_url('admin/presensi/backup') ?>" class="btn-tw btn-tw-sm btn-outline-primary shrink-0 ml-3" target="_blank">
                                        <i class="bi bi-download mr-1"></i> Unduh SQL
                                    </a>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/80 hover:border-slate-300 transition-colors">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <i class="bi bi-filetype-json text-emerald-600 text-lg"></i> Data Log Presensi (JSON)
                                        </div>
                                        <p class="text-xs text-slate-400 mb-0 mt-0.5">Format data portabel riwayat kehadiran GTK untuk migrasi data.</p>
                                    </div>
                                    <a href="<?= base_url('admin/presensi/backup-json') ?>" class="btn-tw btn-tw-sm btn-outline-success shrink-0 ml-3" target="_blank">
                                        <i class="bi bi-download mr-1"></i> Unduh JSON
                                    </a>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 hover:border-indigo-200 transition-colors">
                                    <div>
                                        <div class="font-bold text-indigo-900 text-sm flex items-center gap-2">
                                            <i class="bi bi-database-fill-gear text-indigo-600 text-lg"></i> Database Manager &amp; Restore
                                        </div>
                                        <p class="text-xs text-slate-500 mb-0 mt-0.5">Kelola berkas arsip backup lokal dan pulihkan tabel database.</p>
                                    </div>
                                    <a href="<?= base_url('settings/dbManager') ?>" class="btn-tw btn-tw-sm btn-primary shrink-0 ml-3">
                                        <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Manager
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Pembersihan Kapasitas Server -->
                        <div class="setting-card overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                <div class="bg-rose-100/80 rounded-xl p-2.5 text-rose-600">
                                    <i class="bi bi-trash3 text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Pembersihan Kapasitas Penyimpanan</h6>
                                    <p class="text-xs text-slate-500 mb-0">Optimalisasi ruang disk hosting server</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="p-4 bg-rose-50/50 rounded-xl border border-rose-200/80">
                                    <div class="font-bold text-sm text-rose-700 flex items-center gap-2 mb-1">
                                        <i class="bi bi-images"></i> Hapus Foto Absen Bulan-Bulan Lalu
                                    </div>
                                    <p class="text-xs text-slate-600 mb-4">
                                        Menghapus berkas gambar foto selfie dari bulan-bulan sebelumnya agar kapasitas disk server hemat. 
                                        <b>Data log kehadiran teks, jam, dan titik koordinat GPS tetap tersimpan aman</b> di database.
                                    </p>
                                    <button type="button" class="btn-tw btn-tw-sm btn-danger" id="btnCleanPhotos">
                                        <i class="bi bi-trash3 mr-1"></i> Bersihkan Foto Lama Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // Copy Monday Schedule to All Days
    function copyMondaySchedule() {
        const monIn = document.getElementById('masuk_1')?.value || '07:30';
        const monOut = document.getElementById('pulang_1')?.value || '15:00';

        for (let i = 2; i <= 6; i++) {
            const inEl = document.getElementById('masuk_' + i);
            const outEl = document.getElementById('pulang_' + i);
            if (inEl) inEl.value = monIn;
            if (outEl) outEl.value = monOut;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Tersalin!',
                text: 'Jam kerja hari Senin berhasil disalin ke Selasa s.d. Sabtu.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    // Update Simulasi Toleransi Keterlambatan
    function updateGraceSimulation() {
        const jamMasukEl = document.getElementById('masuk_1');
        const graceEl = document.getElementById('input_grace_period');
        const jamMasuk = jamMasukEl?.value || '07:15';
        const grace = parseInt(graceEl?.value, 10) || 0;

        const simMasuk = document.getElementById('sim-jam-masuk');
        const simTol = document.getElementById('sim-toleransi');
        const badgeTol = document.getElementById('text-grace-preview');
        const simOntime = document.getElementById('sim-batas-ontime');
        const simLate = document.getElementById('sim-batas-late');

        if (simMasuk) simMasuk.textContent = jamMasuk;
        if (simTol) simTol.textContent = grace + ' Menit';
        if (badgeTol) badgeTol.textContent = grace + ' Menit';

        const parts = jamMasuk.split(':');
        const h = parseInt(parts[0], 10) || 7;
        const m = parseInt(parts[1], 10) || 15;

        const baseDate = new Date();
        baseDate.setHours(h, m, 0, 0);

        const ontimeDate = new Date(baseDate.getTime() + grace * 60000);
        const lateDate = new Date(ontimeDate.getTime() + 60000);

        const pad = (n) => n < 10 ? '0' + n : n;
        const ontimeStr = pad(ontimeDate.getHours()) + ':' + pad(ontimeDate.getMinutes());
        const lateStr = pad(lateDate.getHours()) + ':' + pad(lateDate.getMinutes());

        if (simOntime) simOntime.textContent = ontimeStr;
        if (simLate) simLate.textContent = lateStr;
    }

    // Toggle WFH Settings Visibility
    document.getElementById('qrScanAktif')?.addEventListener('change', function() {
        document.getElementById('qrScanWindow')?.classList.toggle('hidden', !this.checked);
    });
    document.getElementById('wfhEnabled')?.addEventListener('change', function() {
        const box = document.getElementById('wfhSettings');
        if (box) {
            if (this.checked) box.classList.remove('hidden');
            else box.classList.add('hidden');
        }
    });

    // Face Threshold Range Slider & Presets
    function updateThreshold(val) {
        document.getElementById('thresholdBadge').innerText = parseFloat(val).toFixed(2);
        document.getElementById('faceThresholdInput').value = val;

        const title = document.getElementById('descTitle');
        const detail = document.getElementById('descDetail');

        if (val < 0.68) {
            title.innerText = 'Sangat Ketat (' + parseFloat(val).toFixed(2) + ')';
            title.className = 'font-semibold text-red-600 text-xs';
            detail.innerText = 'Hanya menerima wajah yang sangat identik. Berisiko false-reject jika guru memakai kacamata atau di ruangan redup.';
        } else if (val <= 0.75) {
            title.innerText = 'Rekomendasi Seimbang (' + parseFloat(val).toFixed(2) + ')';
            title.className = 'font-semibold text-blue-600 text-xs';
            detail.innerText = 'Nilai seimbang terbaik untuk berbagai kondisi pencahayaan dan tipe kamera smartphone.';
        } else {
            title.innerText = 'Fleksibel / Longgar (' + parseFloat(val).toFixed(2) + ')';
            title.className = 'font-semibold text-amber-600 text-xs';
            detail.innerText = 'Sangat mudah lolos verifikasi. Cocok untuk lingkungan outdoor atau device berspesifikasi rendah.';
        }
    }

    function setPreset(val) {
        document.getElementById('thresholdRange').value = val;
        updateThreshold(val);
    }

    // Interactive Simulator Calculator
    function calculateSimulation() {
        const lateMinutes = parseInt(document.getElementById('sim_menit')?.value) || 0;
        
        const d1 = parseInt(document.getElementById('cfg_denda_t1')?.value) || 0;
        const p1 = parseInt(document.getElementById('cfg_point_t1')?.value) || 0;
        
        const d2 = parseInt(document.getElementById('cfg_denda_t2')?.value) || 0;
        const p2 = parseInt(document.getElementById('cfg_point_t2')?.value) || 0;
        
        const d3 = parseInt(document.getElementById('cfg_denda_t3')?.value) || 0;
        const p3 = parseInt(document.getElementById('cfg_point_t3')?.value) || 0;

        let totalDenda = 0;
        let totalPoint = 0;
        let breakdown = [];

        if (lateMinutes > 0) {
            const m1 = Math.min(lateMinutes, 30);
            totalDenda += m1 * d1;
            totalPoint += m1 * p1;
            breakdown.push(`Tier 1 (${m1}m × Rp${d1} / ${p1}pt)`);

            if (lateMinutes > 30) {
                const m2 = Math.min(lateMinutes - 30, 30);
                totalDenda += m2 * d2;
                totalPoint += m2 * p2;
                breakdown.push(`Tier 2 (${m2}m × Rp${d2} / ${p2}pt)`);
            }

            if (lateMinutes > 60) {
                const m3 = lateMinutes - 60;
                totalDenda += m3 * d3;
                totalPoint += m3 * p3;
                breakdown.push(`Tier 3 (${m3}m × Rp${d3} / ${p3}pt)`);
            }
        }

        const resDendaEl = document.getElementById('sim_res_denda');
        const resPointEl = document.getElementById('sim_res_point');
        const breakdownEl = document.getElementById('sim_breakdown');

        if (resDendaEl) resDendaEl.innerText = 'Rp ' + totalDenda.toLocaleString('id-ID');
        if (resPointEl) resPointEl.innerText = totalPoint + ' Poin';
        if (breakdownEl) breakdownEl.innerHTML = breakdown.length > 0 ? breakdown.join(' + ') : 'Tidak ada denda.';
    }

    // Google OAuth Copy URI
    function copyRedirectUri() {
        const copyText = document.getElementById("googleRedirectUri");
        if (!copyText) return;
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Disalin!',
                text: 'Authorized Redirect URI telah disalin ke clipboard.',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }
</script>

<script src="<?= base_url('assets/plugins/dropify/js/dropify.min.js') ?>"></script>
<script>
    $(document).ready(function() {
        // Copy Monday Button
        $('#btnCopyMon').on('click', function() {
            copyMondaySchedule();
            updateGraceSimulation();
        });

        $('#input_grace_period').on('input change', updateGraceSimulation);
        $('#masuk_1').on('input change', updateGraceSimulation);
        updateGraceSimulation();

        // Dynamic Satuan Pendidikan based on Jenjang
        const satuanMap = {
            "1": ["SD", "MI"],
            "2": ["SMP", "MTs"],
            "3": ["SMA", "MA", "SMK"]
        };
        $('#jenjangSelect').on('change', function() {
            const jVal = $(this).val();
            const list = satuanMap[jVal] || [];
            let optionsHtml = '';
            list.forEach((label, idx) => {
                optionsHtml += `<option value="${idx + 1}">${label}</option>`;
            });
            $('#satuanSelect').html(optionsHtml);
        });

        // Initialize Dropify for visual branding
        if ($.fn.dropify) {
            const drEvent = $('.dropify').dropify({
                messages: {
                    default: 'Seret file ke sini atau klik',
                    replace: 'Klik untuk mengganti',
                    remove: 'Hapus',
                    error: 'Ukuran atau format tidak valid'
                }
            });

            // Handle Dropify file upload
            $('.dropify').on('change', function() {
                const input = this;
                const fieldKey = $(this).data('field');
                if (!fieldKey || !input.files || !input.files[0]) return;

                if (input.files[0].size > 5 * 1024 * 1024) {
                    Swal.fire('File Terlalu Besar', 'Batas maksimal file adalah 5MB', 'warning');
                    $(input).val('');
                    return;
                }

                const formData = new FormData();
                formData.append('logo', input.files[0]);
                formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

                $.ajax({
                    url: '<?= base_url("settings/uploadFile") ?>/' + fieldKey,
                    type: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        if (res && res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Diunggah!',
                                text: 'Media ' + fieldKey + ' berhasil diperbarui.',
                                timer: 1200,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Gagal', (res && (res.message || res.src)) || 'Gagal mengunggah file', 'error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr && xhr.status === 403) {
                            Swal.fire('Akses Ditolak', 'Sesi berakhir atau akses tidak diizinkan. Silakan login ulang.', 'error');
                        } else {
                            Swal.fire('Error', 'Terjadi kesalahan koneksi saat upload file', 'error');
                        }
                    }
                });
            });

            // Tangkap URL file SEBELUM dropify membersihkan preview,
            // agar server bisa menghapus file fisiknya (sebelumnya src tak dikirim
            // sehingga file yatim menumpuk di disk dan DB/disk tak sinkron).
            let pendingDeleteSrc = '';
            drEvent.on('dropify.beforeClear', function(event, element) {
                pendingDeleteSrc = '';
                try {
                    const $wrap = $(element.element).closest('.dropify-wrapper');
                    const bg = $wrap.find('.dropify-preview .dropify-render img').attr('src') || '';
                    if (bg) pendingDeleteSrc = bg;
                } catch (e) {}
                if (!pendingDeleteSrc) {
                    pendingDeleteSrc = $(element.element).attr('data-default-file') || '';
                }
            });

            // Handle Dropify file clear
            drEvent.on('dropify.afterClear', function(event, element) {
                const fieldKey = $(element.element).data('field');
                if (fieldKey) {
                    const payload = { field: fieldKey, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' };
                    if (pendingDeleteSrc) payload.src = pendingDeleteSrc;
                    pendingDeleteSrc = '';
                    $.ajax({
                        url: '<?= base_url("settings/deleteFile") ?>',
                        type: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        data: payload,
                        dataType: 'json',
                        success: function (res) {
                            if (res && res.status) {
                                Swal.fire({
                                    icon: 'success', title: 'Dihapus!',
                                    text: (res.fileDeleted ? 'File dihapus dari server' : 'Data dikosongkan') + ' dan tampilan disegarkan.',
                                    timer: 1400, showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus file', 'error');
                            }
                        },
                        error: function (xhr) {
                            if (xhr && xhr.status === 403) {
                                Swal.fire('Akses Ditolak', 'Sesi berakhir atau akses tidak diizinkan. Silakan login ulang.', 'error');
                            } else {
                                Swal.fire('Error', 'Gagal menghubungi server', 'error');
                            }
                        }
                    });
                }
            });
        }

        // Handle Profile Form Submit
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btnSaveProfile');
            const origHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-tw mr-1"></span> Menyimpan...');

            const formData = new FormData(this);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            $.ajax({
                url: '<?= base_url("settings/saveSetting") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan!',
                            text: res.message || 'Profil dan identitas sekolah berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat menyimpan', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan koneksi server saat menyimpan profil', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(origHtml);
                }
            });
        });

        // Live preview + sisip placeholder format nomor surat teguran
        (function() {
            const $fmt = $('#teguranFormatInput');
            const $pad = $('#teguranPaddingInput');
            const $kode = $('#kodeSekolahInput');
            const $out = $('#teguranPreview');
            if (!$out.length) { return; }

            const ROM = {1:'I',2:'II',3:'III',4:'IV',5:'V',6:'VI',7:'VII',8:'VIII',9:'IX',10:'X',11:'XI',12:'XII'};
            const NAMA = {1:'Januari',2:'Februari',3:'Maret',4:'April',5:'Mei',6:'Juni',7:'Juli',8:'Agustus',9:'September',10:'Oktober',11:'November',12:'Desember'};

            function render() {
                const tpl = (($fmt.val() || '').trim()) || <?= json_encode(\App\Libraries\SuratNomor::DEFAULT_FORMAT) ?>;
                const padRaw = parseInt($pad.val(), 10);
                const pad = (padRaw >= 1 && padRaw <= 6) ? padRaw : <?= \App\Libraries\SuratNomor::DEFAULT_PADDING ?>;
                const kode = (($kode.val() || '').trim()) || <?= json_encode(\App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH) ?>;
                const now = new Date();
                const bln = now.getMonth() + 1;
                const thn = now.getFullYear();
                const nomor = ('1').padStart(pad, '0');

                let teks = tpl
                    .split('{kode_sekolah}').join(kode)
                    .split('{sekolah}').join(<?= json_encode((string)($setting->sekolah ?? '')) ?>)
                    .split('{npsn}').join(<?= json_encode((string)($setting->npsn ?? '')) ?>)
                    .split('{nss}').join(<?= json_encode((string)($setting->nss ?? '')) ?>)
                    .split('{kepsek}').join(<?= json_encode((string)($setting->kepsek ?? '')) ?>)
                    .split('{nip_kepsek}').join(<?= json_encode((string)($setting->nip ?? '')) ?>)
                    .split('{nomor_urut}').join(nomor)
                    .split('{nomor}').join(nomor)
                    .split('{bulan_romawi}').join(ROM[bln] || '')
                    .split('{bulan_nama}').join(NAMA[bln] || '')
                    .split('{bulan}').join(('0' + bln).slice(-2))
                    .split('{tahun_2}').join(('0' + (thn % 100)).slice(-2))
                    .split('{tahun}').join(String(thn))
                    .split('{level}').join('3')
                    .split('{tanggal}').join(('0' + now.getDate()).slice(-2) + '-' + ('0' + bln).slice(-2) + '-' + thn);

                teks = teks.replace(/\/{2,}/g, '/').trim();
                $out.text(teks);
            }

            $fmt.on('input', render);
            $pad.on('change', render);
            $kode.on('input', render);
            $('.teguran-ph-btn').on('click', function() {
                const ph = $(this).data('ph');
                const start = ($fmt.val() || '').length;
                $fmt.val((($fmt.val() || '') + ph)).trigger('focus');
                try { $fmt[0].setSelectionRange(start + ph.length, start + ph.length); } catch (e) {}
                render();
            });

            render();
        })();

        // Handle Setting Form (Attendance) Submit
        $('#settingForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btnSaveSetting');
            const origHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-tw mr-1"></span> Menyimpan...');

            const formData = new FormData(this);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            $.ajax({
                url: '<?= site_url("admin/presensi/setting/save") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pengaturan Tersimpan!',
                            text: res.message || 'Kebijakan jam kerja dan presensi berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat menyimpan', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan koneksi server saat menyimpan pengaturan presensi', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(origHtml);
                }
            });
        });

        // Test Telegram Handler (uji nilai yang sedang diketik, tanpa harus Simpan dulu)
        $('#btnTestTelegram').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-tw spinner-tw-dark mr-1"></span> Mengirim...');
            $.ajax({
                url: '<?= site_url('admin/presensi/test-notification') ?>',
                type: 'POST',
                data: {
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                    channel: 'telegram',
                    telegram_bot_token: $('input[name="telegram_bot_token"]').val() || '',
                    telegram_chat_id: $('input[name="telegram_chat_id"]').val() || ''
                },
                dataType: 'json',
                success: function(res) {
                    Swal.fire(res.status ? 'Berhasil' : 'Gagal', res.message, res.status ? 'success' : 'error');
                },
                error: function() {
                    Swal.fire('Error', 'Gagal mengirim notifikasi test.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-send mr-1"></i> Test Kirim Telegram');
                }
            });
        });

        // Test WhatsApp Handler
        $('#btnTestWa').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-tw spinner-tw-dark mr-1"></span> Mengirim...');
            $.ajax({
                url: '<?= site_url('admin/presensi/test-notification') ?>',
                type: 'POST',
                data: {
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                    channel: 'whatsapp',
                    // Kirim nilai yang sedang diketik agar bisa diuji tanpa
                    // Save dulu; panel tersembunyi tidak boleh membuat test
                    // diam-diam memakai konfigurasi lama yang tersimpan.
                    wa_gateway_mode: $('#waGatewayMode').val() || '',
                    wa_base_url: $('input[name="wa_base_url"]').val() || '',
                    wa_api_key: $('input[name="wa_api_key"]').val() || '',
                    wa_session_name: $('input[name="wa_session_name"]').val() || '',
                    wa_target_number: $('input[name="wa_target_number"]').val() || '',
                    wa_gateway_url: $('input[name="wa_gateway_url"]').val() || '',
                    wa_token: $('input[name="wa_token"]').val() || ''
                },
                dataType: 'json',
                success: function(res) {
                    Swal.fire(res.status ? 'Berhasil' : 'Gagal', res.message, res.status ? 'success' : 'error');
                },
                error: function() {
                    Swal.fire('Error', 'Gagal mengirim notifikasi test.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-send mr-1"></i> Test Kirim WhatsApp');
                }
            });
        });

        // Tampilkan hanya panel kanal yang dipilih.
        function syncWaPanels() {
            const mode = $('#waGatewayMode').val();
            $('#panelOpenwa').toggleClass('hidden', mode !== 'openwa');
            $('#panelFonnte').toggleClass('hidden', mode !== 'fonnte');
        }
        $('#waGatewayMode').on('change', syncWaPanels);
        syncWaPanels();

        // Clean Photos Handler
        $('#btnCleanPhotos').on('click', function() {
            Swal.fire({
                title: 'Hapus Foto Absen Bulan Lalu?',
                text: 'File gambar selfie bulan-bulan sebelumnya akan dihapus permanen dari server untuk menghemat kapasitas disk.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Bersihkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = $('#btnCleanPhotos');
                    btn.prop('disabled', true).html('<span class="spinner-tw mr-1"></span> Menghapus...');
                    
                    $.ajax({
                        url: '<?= site_url('admin/presensi/clean-photos') ?>',
                        type: 'POST',
                        data: {
                            <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                        },
                        dataType: 'json',
                        success: function(res) {
                            Swal.fire(res.status ? 'Berhasil' : 'Gagal', res.message, res.status ? 'success' : 'error');
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem saat menghapus foto', 'error');
                            console.error(xhr);
                        },
                        complete: function() {
                            btn.prop('disabled', false).html('<i class="bi bi-trash3 mr-1"></i> Bersihkan Foto Lama Sekarang');
                        }
                    });
                }
            });
        });

        // ── Theme Customizer ────────────────────────────────────
        (function themeCustomizer() {
            const cfgEl = document.getElementById('themeClientConfig');
            if (!cfgEl) return;

            const cfg = JSON.parse(cfgEl.textContent || '{}');
            const root = document.documentElement;
            const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
            const savedPreset = <?= json_encode($curPreset) ?>;
            const savedCustom = <?= json_encode($curCustom) ?>;
            let selected = savedPreset;
            let customHex = savedCustom;

            function hexToRgb(hex) {
                hex = String(hex).replace('#', '');
                if (hex.length === 3) {
                    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
                }
                return [
                    parseInt(hex.substr(0, 2), 16),
                    parseInt(hex.substr(2, 2), 16),
                    parseInt(hex.substr(4, 2), 16)
                ];
            }

            function rgbToHsl(r, g, b) {
                r /= 255; g /= 255; b /= 255;
                const max = Math.max(r, g, b);
                const min = Math.min(r, g, b);
                let h = 0;
                let s = 0;
                const l = (max + min) / 2;
                if (max !== min) {
                    const d = max - min;
                    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                    if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
                    else if (max === g) h = (b - r) / d + 2;
                    else h = (r - g) / d + 4;
                    h *= 60;
                }
                return [h, s * 100, l * 100];
            }

            function hslToHex(h, s, l) {
                h = ((h % 360) + 360) % 360;
                s /= 100; l /= 100;
                const c = (1 - Math.abs(2 * l - 1)) * s;
                const x = c * (1 - Math.abs((h / 60) % 2 - 1));
                const m = l - c / 2;
                let r = 0, g = 0, b = 0;
                if (h < 60) { r = c; g = x; b = 0; }
                else if (h < 120) { r = x; g = c; b = 0; }
                else if (h < 180) { r = 0; g = c; b = x; }
                else if (h < 240) { r = 0; g = x; b = c; }
                else if (h < 300) { r = x; g = 0; b = c; }
                else { r = c; g = 0; b = x; }
                const to = v => Math.round((v + m) * 255).toString(16).padStart(2, '0');
                return '#' + to(r) + to(g) + to(b);
            }

            function paletteFromHex(hex) {
                const rgb = hexToRgb(hex);
                const hsl = rgbToHsl(rgb[0], rgb[1], rgb[2]);
                const h = hsl[0];
                const baseS = Math.min(95, Math.max(35, hsl[1]));
                const baseL = Math.min(62, Math.max(38, hsl[2]));
                const lightness = { 50: 96, 100: 92, 200: 84, 300: 74, 400: 64, 500: baseL, 600: baseL - 7, 700: baseL - 15, 800: baseL - 24, 900: baseL - 33 };
                const satFactor = { 50: 0.55, 100: 0.7, 200: 0.8, 300: 0.9, 400: 1, 500: 1, 600: 1, 700: 1, 800: 1, 900: 1 };
                const out = {};
                shades.forEach(function (s) {
                    out[s] = hslToHex(h, baseS * satFactor[s], Math.min(97, Math.max(8, lightness[s])));
                });
                return out;
            }

            function resolveTheme(preset, hex) {
                if (preset === 'custom') {
                    const p = paletteFromHex(hex);
                    return { brand: p, primary: p, accent: p, indigo: p, purple: p, secondary: p[400], tertiary: p[700] };
                }
                return cfg.presets && cfg.presets[preset] ? cfg.presets[preset] : cfg.presets.default;
            }

            function applyTheme(preset, hex) {
                const t = resolveTheme(preset, hex);
                if (!t) return;

                ['brand', 'primary', 'accent', 'indigo', 'purple'].forEach(function (family) {
                    const palette = t[family] || t.brand;
                    shades.forEach(function (s) {
                        const rgb = hexToRgb(palette[s]);
                        root.style.setProperty('--' + family + '-' + s + '-rgb', rgb.join(' '));
                        root.style.setProperty('--' + family + '-' + s, palette[s]);
                    });
                });

                root.style.setProperty('--brand-secondary', t.secondary);
                root.style.setProperty('--brand-tertiary', t.tertiary);
                root.style.setProperty('--brand-secondary-rgb', hexToRgb(t.secondary).join(' '));
                root.style.setProperty('--brand-tertiary-rgb', hexToRgb(t.tertiary).join(' '));

                const primaryRgb = hexToRgb(t.brand[600]);
                root.style.setProperty('--primary', t.brand[600]);
                root.style.setProperty('--primary-rgb', primaryRgb.join(', '));
                root.style.setProperty('--secondary', t.secondary);
                root.style.setProperty('--accent', t.tertiary);

                const meta = document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', t.brand[600]);
            }

            function renderCustomSwatches() {
                const palette = paletteFromHex(customHex);
                const wrap = document.getElementById('themeCustomSwatches');
                if (wrap) {
                    wrap.innerHTML = '';
                    shades.forEach(function (s) {
                        const dot = document.createElement('div');
                        dot.className = 'theme-custom-swatch';
                        dot.style.background = palette[s];
                        dot.title = String(s);
                        wrap.appendChild(dot);
                    });
                }
                document.querySelectorAll('[data-custom-swatch]').forEach(function (el) {
                    const shade = el.getAttribute('data-custom-swatch');
                    if (palette[shade]) el.style.background = palette[shade];
                });
            }

            function syncCards() {
                document.querySelectorAll('#themePresetGrid .theme-card').forEach(function (card) {
                    card.classList.toggle('selected', card.getAttribute('data-theme-preset') === selected);
                });
                const panel = document.getElementById('themeCustomPanel');
                if (panel) panel.classList.toggle('hidden', selected !== 'custom');
                renderCustomSwatches();
            }

            const colorInput = document.getElementById('themeCustomColor');
            const hexInput = document.getElementById('themeCustomHex');
            const saveBtn = document.getElementById('btnSaveTheme');
            const resetBtn = document.getElementById('btnResetTheme');

            document.querySelectorAll('#themePresetGrid .theme-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    selected = this.getAttribute('data-theme-preset');
                    syncCards();
                    applyTheme(selected, customHex);
                });
            });

            if (colorInput) {
                colorInput.addEventListener('input', function () {
                    customHex = String(this.value).toLowerCase();
                    if (hexInput) hexInput.value = customHex;
                    renderCustomSwatches();
                    if (selected === 'custom') applyTheme('custom', customHex);
                });
            }

            if (hexInput) {
                hexInput.addEventListener('input', function () {
                    let value = this.value.trim();
                    if (value.charAt(0) !== '#') value = '#' + value;
                    if (/^#[0-9a-f]{6}$/i.test(value)) {
                        customHex = value.toLowerCase();
                        if (colorInput) colorInput.value = customHex;
                        renderCustomSwatches();
                        if (selected === 'custom') applyTheme('custom', customHex);
                    }
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    selected = 'default';
                    syncCards();
                    applyTheme('default', customHex);
                });
            }

            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    if (selected === 'custom' && !/^#[0-9a-f]{6}$/i.test(customHex)) {
                        Swal.fire('Warna belum valid', 'Masukkan kode HEX 6 digit, misalnya #2563eb.', 'warning');
                        return;
                    }

                    const originalHtml = saveBtn.innerHTML;
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span class="spinner-tw mr-1"></span> Menyimpan...';

                    const payload = { preset: selected, custom: customHex };
                    payload['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.ajax({
                        url: '<?= base_url("settings/saveTheme") ?>',
                        type: 'POST',
                        data: payload,
                        dataType: 'json',
                        success: function (res) {
                            if (res.status) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Tema Disimpan!',
                                    text: 'Warna aplikasi berhasil diperbarui.',
                                    timer: 1600,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat menyimpan tema', 'error');
                            }
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Gagal menghubungi server';
                            Swal.fire('Error', msg, 'error');
                        },
                        complete: function () {
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = originalHtml;
                        }
                    });
                });
            }

            syncCards();
            applyTheme(selected, customHex);
        })();

        // Tab Switch Handling & Fixed Bottom Bar Visibility
        function updateActionBar(target) {
            if (target === '#profil' || target === '#maintenance' || target === '#tampilan') {
                $('.fixed-bottom-bar').fadeOut(120);
            } else {
                $('.fixed-bottom-bar').fadeIn(120);
            }
        }

        function showSettingTab(target) {
            if (!target) return;
            const targetEl = document.querySelector(target);
            if (!targetEl) return;

            // Hide all tab panes in settingTabContent
            document.querySelectorAll('#settingTabContent .tab-pane, #settingTabContent .tab-pane-tw').forEach(function(pane) {
                pane.classList.remove('show', 'active');
                pane.style.display = 'none';
            });

            // Show selected tab pane
            targetEl.classList.add('show', 'active');
            targetEl.style.display = 'block';

            // Update tab buttons
            document.querySelectorAll('#settingTabs button[data-ui-toggle="tab"]').forEach(function(b) {
                const bTarget = b.getAttribute('data-ui-target');
                if (bTarget === target) {
                    b.classList.add('bg-white', 'text-blue-600', 'shadow-sm', 'active');
                    b.classList.remove('text-slate-500');
                    b.setAttribute('aria-selected', 'true');
                } else {
                    b.classList.remove('bg-white', 'text-blue-600', 'shadow-sm', 'active');
                    b.classList.add('text-slate-500');
                    b.setAttribute('aria-selected', 'false');
                }
            });

            localStorage.setItem('activeSettingTab', target);
            updateActionBar(target);
        }

        document.querySelectorAll('#settingTabs button[data-ui-toggle="tab"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const target = this.getAttribute('data-ui-target');
                if (target) {
                    showSettingTab(target);

                    // Scroll back to top of the scrollable area
                    const content = document.getElementById('main-content');
                    if (content) content.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });

        // Restore saved tab or from query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const reqTab = urlParams.get('tab') ? ('#' + urlParams.get('tab')) : (window.location.hash || localStorage.getItem('activeSettingTab') || '#profil');
        showSettingTab(reqTab);

        // Run calculation once
        calculateSimulation();
    });
</script>

<?= $this->endSection() ?>
