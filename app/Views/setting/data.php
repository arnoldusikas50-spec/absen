<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$satuan = ["1" => ["SD", "MI"], "2" => ["SMP", "MTS"], "3" => ["SMA", "MA", "SMK"]];

$settingData = isset($setting) && is_object($setting) ? $setting : (object) [];

$esc = static function ($value) {
    return esc($value ?? '');
};

$jenjangOptions = [
    1 => 'SD/MI',
    2 => 'SMP/MTS',
    3 => 'SMA/MA/SMK'
];

$bankSoalOptions = [
    1 => 'Aktif',
    2 => 'Non-Aktif'
];

$tokenOptions = [
    1 => 'Ya',
    2 => 'Tidak'
];

$modeOptions = [
    1 => 'CBT',
    2 => 'Elearning'
];

$bankSoalDateTime = '';
if (!empty($settingData->ba_waktu) && $settingData->ba_waktu !== '0000-00-00 00:00:00') {
    $bankSoalDateObj = DateTime::createFromFormat('Y-m-d H:i:s', (string) $settingData->ba_waktu);
    if ($bankSoalDateObj instanceof DateTime) {
        $bankSoalDateTime = $bankSoalDateObj->format('Y-m-d\TH:i');
    }
}
?>
<style>
    .dropify-wrapper .dropify-message p {
        font-size: 0.85rem;
        line-height: 1.3;
    }

    .dropify-wrapper .dropify-message span.file-icon {
        font-size: 2.5rem;
    }
</style>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-database-gear text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Halaman') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($subjudul ?? 'Data') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">
<div class="card-tw">
            <div class="card-tw-body">
                <div class="mb-6">
                    <h5 class="mb-1 font-weight-bold">Pengaturan Profil Sekolah</h5>
                    <div class="text-slate-400">Kelola identitas sekolah, kontak, serta pengaturan ujian dalam satu tempat.
                    </div>
                </div>

                <div class="callout callout-info shadow-sm border border-info mb-6">
                    <h5 class="mb-2 font-weight-bold"><i class="bi bi-info-circle mr-2"></i>Panduan Pengisian Profil
                    </h5>
                    <ul class="pl-4 mb-0">
                        <li>Isi seluruh kolom bertanda bintang (<span class="text-red-500">*</span>) untuk memastikan
                            data profil lengkap.
                        <li>Pastikan jenjang dan satuan pendidikan sesuai agar tampilan rapor dan fitur absensi by ikasmedia menyesuaikan
                            otomatis.
                        <li>Unggah logo dan dokumen pendukung dalam format JPG/PNG maksimal 2MB agar tampil tajam pada
                            laporan.
                        <li>Aktifkan token siswa hanya saat dibutuhkan untuk sesi ujian.
                    </ul>
                </div>

                <?= form_open('', ['id' => 'savesetting']) ?>
                <div class="flex flex-wrap gap-4">
                    <div class="">
                        <div class="card-tw">
                            <div class="card-tw-header"
                                style="background: linear-gradient(135deg, #f6f9ff 0%, #dbe7ff 100%); color: #111;">
                                <h6 class="mb-0">Identitas Sekolah</h6>
                            </div>
                            <div class="card-tw-body">
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Nama Aplikasi <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="nama_aplikasi" class="form-control-tw"
                                        value="<?= $esc($settingData->nama_aplikasi ?? '') ?>" required>
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Nama Sekolah <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="nama_sekolah" class="form-control-tw"
                                        value="<?= $esc($settingData->sekolah ?? '') ?>" required>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">NSS/NSM</label>
                                        <input type="text" name="nss" class="form-control-tw"
                                            value="<?= $esc($settingData->nss ?? '') ?>" pattern="[0-9]{10,12}"
                                            title="NSS/NSM harus berupa angka 10-12 digit">
                                    </div>
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">NPSN</label>
                                        <input type="text" name="npsn" class="form-control-tw"
                                            value="<?= $esc($settingData->npsn ?? '') ?>" pattern="[0-9]{8}"
                                            title="NPSN harus berupa 8 digit angka">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Jenjang <span
                                                class="text-red-500">*</span></label>
                                        <select id="jenjang" class="form-control-tw" name="jenjang" required>
                                            <option value="" disabled <?= empty($settingData->jenjang) ? 'selected' : '' ?>>Pilih Jenjang</option>
                                            <?php foreach ($jenjangOptions as $key => $label): ?>
                                                <option value="<?= $key ?>" <?= ((string) ($settingData->jenjang ?? '')) === (string) $key ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Satuan Pendidikan <span
                                                class="text-red-500">*</span></label>
                                        <select id="satuan-pend" class="form-control-tw" name="satuan_pendidikan"
                                            required>
                                            <option value="" disabled <?= empty($settingData->satuan_pendidikan) ? 'selected' : '' ?>>Pilih Satuan Pendidikan</option>
                                            <?php
                                            // Handle dynamically based on jenjang if needed, but for now loop based on current logic
                                            // Ideally this is dynamic via JS, but we need initial render
                                            // Logic ported from CI3 View
                                            $satuanOptionList = $satuan[$settingData->jenjang ?? '1'] ?? [];
                                            foreach ($satuanOptionList as $idx => $label):
                                                $optionValue = $idx + 1;
                                                ?>
                                                <option value="<?= $optionValue ?>" <?= ((string) ($settingData->satuan_pendidikan ?? '')) === (string) $optionValue ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div class="card-tw">
                            <div class="card-tw-header"
                                style="background: linear-gradient(135deg, #eef1ff 0%, #d8dcff 100%); color: #111;">
                                <h6 class="mb-0">Alamat & Wilayah</h6>
                            </div>
                            <div class="card-tw-body">
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Alamat <span
                                            class="text-red-500">*</span></label>
                                    <textarea class="form-control-tw" name="alamat" rows="4"
                                        placeholder="Masukkan alamat lengkap"
                                        required><?= $esc($settingData->alamat ?? '') ?></textarea>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Desa/Kelurahan <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="desa" class="form-control-tw"
                                            value="<?= $esc($settingData->desa ?? '') ?>" required>
                                    </div>
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Kecamatan <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="kec" class="form-control-tw"
                                            value="<?= $esc($settingData->kecamatan ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Kabupaten/Kota <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="kota" class="form-control-tw"
                                            value="<?= $esc($settingData->kota ?? '') ?>" required>
                                    </div>
                                    <div class="mb-6 col-md-3">
                                        <label class="font-weight-semibold">Kode Pos</label>
                                        <input type="text" name="kode_pos" class="form-control-tw"
                                            value="<?= $esc($settingData->kode_pos ?? '') ?>" pattern="[0-9]{5}"
                                            title="Kode pos terdiri dari 5 digit angka">
                                    </div>
                                    <div class="mb-6 col-md-3">
                                        <label class="font-weight-semibold">Provinsi <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="provinsi" class="form-control-tw"
                                            value="<?= $esc($settingData->provinsi ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div class="card-tw">
                            <div class="card-tw-header"
                                style="background: linear-gradient(135deg, #eefbf3 0%, #d6f2e2 100%); color: #111;">
                                <h6 class="mb-0">Kontak Sekolah</h6>
                            </div>
                            <div class="card-tw-body">
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Website</label>
                                    <input type="url" name="web" class="form-control-tw"
                                        value="<?= $esc($settingData->web ?? ($settingData->website ?? '')) ?>"
                                        placeholder="https://contoh-sekolah.sch.id">
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Email</label>
                                    <input type="email" name="email" class="form-control-tw"
                                        value="<?= $esc($settingData->email ?? '') ?>"
                                        placeholder="kontak@sekolah.sch.id">
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Nomor Telepon</label>
                                    <div class="input-group-tw">
                                        <div class="input-group-tw">
                                            <span class="input-group-tw">+62</span>
                                        </div>
                                        <input type="tel" name="tlp" class="form-control-tw"
                                            value="<?= $esc($settingData->telp ?? '') ?>" pattern="[0-9]{6,15}"
                                            title="Masukkan nomor telepon tanpa spasi atau tanda hubung">
                                    </div>
                                </div>
                                <div class="mb-6 mb-0">
                                    <label class="font-weight-semibold">Faksimili</label>
                                    <input type="text" name="fax" class="form-control-tw"
                                        value="<?= $esc($settingData->fax ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div class="card-tw">
                            <div class="card-tw-header"
                                style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); color: #111;">
                                <h6 class="mb-0"><i class="bi bi-chat-dots mr-2"></i>Konfigurasi Notifikasi</h6>
                            </div>
                            <div class="card-tw-body">
                                <div class="alert-tw alert-info">
                                    <i class="bi bi-info-circle text-blue-600 mr-1"></i> Notifikasi ini digunakan untuk
                                    mengirim rekap absensi harian otomatis.
                                </div>
                                <h6 class="font-bold mb-6 text-blue-600">Telegram</h6>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Bot Token</label>
                                    <input type="text" name="telegram_bot_token" class="form-control-tw"
                                        value="<?= $esc($settingData->telegram_bot_token ?? '') ?>"
                                        placeholder="123456789:ABCdefGhIjkLmNoPqRsTuVwXyZ">
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Chat ID Tujuan</label>
                                    <input type="text" name="telegram_chat_id" class="form-control-tw"
                                        value="<?= $esc($settingData->telegram_chat_id ?? '') ?>"
                                        placeholder="-100xxxxxxx atau ID User">
                                </div>
                                <hr>
                                <h6 class="font-bold mb-6 text-emerald-600">WhatsApp (Fonnte/Wablas)</h6>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">WA API Token</label>
                                    <input type="text" name="wa_token" class="form-control-tw"
                                        value="<?= $esc($settingData->wa_token ?? '') ?>"
                                        placeholder="Token API WhatsApp Gateway">
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Nomor WA Tujuan</label>
                                    <input type="text" name="wa_target_number" class="form-control-tw"
                                        value="<?= $esc($settingData->wa_target_number ?? '') ?>"
                                        placeholder="0812345xxxxx">
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Gateway URL (Opsional)</label>
                                    <input type="url" name="wa_gateway_url" class="form-control-tw"
                                        value="<?= $esc($settingData->wa_gateway_url ?? 'https://api.fonnte.com/send') ?>"
                                        placeholder="https://api.fonnte.com/send">
                                </div>
                                <hr>
                                <h6 class="font-bold mb-6 text-red-500">Integrasi MikroTik (Hotspot)</h6>
                                <div class="grid grid-cols-1 gap-2">
                                    <div class="col-8">
                                        <div class="mb-6">
                                            <label class="font-weight-semibold">IP Address MikroTik</label>
                                            <input type="text" name="mikrotik_host" class="form-control-tw"
                                                value="<?= $esc($settingData->mikrotik_host ?? '') ?>"
                                                placeholder="192.168.88.1">
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="mb-6">
                                            <label class="font-weight-semibold">Port API</label>
                                            <input type="number" name="mikrotik_port"
                                                class="form-control-tw"
                                                value="<?= $esc($settingData->mikrotik_port ?? '8728') ?>"
                                                placeholder="8728">
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div class="">
                                        <div class="mb-6">
                                            <label class="font-weight-semibold">User API</label>
                                            <input type="text" name="mikrotik_user" class="form-control-tw"
                                                value="<?= $esc($settingData->mikrotik_user ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="mb-6">
                                            <label class="font-weight-semibold">Password API</label>
                                            <input type="password" name="mikrotik_pass"
                                                class="form-control-tw"
                                                value="<?= $esc($settingData->mikrotik_pass ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div class="card-tw">
                            <div class="card-tw-header"
                                style="background: linear-gradient(135deg, #fff4e4 0%, #ffe3c3 100%); color: #111;">
                                <h6 class="mb-0">Penanggung Jawab & Pengaturan</h6>
                            </div>
                            <div class="card-tw-body">
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Kepala Sekolah <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="kepsek" class="form-control-tw"
                                            value="<?= $esc($settingData->kepsek ?? '') ?>" required>
                                    </div>
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">NIP Kepala Sekolah</label>
                                        <input type="text" name="nip" class="form-control-tw"
                                            value="<?= $esc($settingData->nip ?? '') ?>" pattern="[0-9]{8,20}"
                                            title="Masukkan 8-20 digit angka">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Proktor <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="proktor" class="form-control-tw"
                                            value="<?= $esc($settingData->proktor ?? '') ?>" required>
                                    </div>
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">NIP Proktor</label>
                                        <input type="text" name="nip_proktor" class="form-control-tw"
                                            value="<?= $esc($settingData->nip_proktor ?? '') ?>" pattern="[0-9]{8,20}"
                                            title="Masukkan 8-20 digit angka">
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Status Bank Soal</label>
                                    <select id="ba_aktif" class="form-control-tw" name="ba_aktif" required>
                                        <option value="" disabled <?= empty($settingData->ba_aktif) ? 'selected' : '' ?>>
                                            Pilih Status</option>
                                        <?php foreach ($bankSoalOptions as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= ((string) ($settingData->ba_aktif ?? '')) === (string) $key ? 'selected' : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">Waktu Bank Soal</label>
                                    <input type="datetime-local" name="ba_waktu" class="form-control-tw"
                                        value="<?= $esc($bankSoalDateTime) ?>">
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Tampilkan Token di Siswa</label>
                                        <select id="tkn_siswa" class="form-control-tw" name="tkn_siswa" required>
                                            <option value="" disabled <?= empty($settingData->tkn_siswa) ? 'selected' : '' ?>>Pilih Status</option>
                                            <?php foreach ($tokenOptions as $key => $label): ?>
                                                <option value="<?= $key ?>" <?= ((string) ($settingData->tkn_siswa ?? '')) === (string) $key ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-6 col-md-6">
                                        <label class="font-weight-semibold">Mode Aplikasi</label>
                                        <select id="mode_app" class="form-control-tw" name="mode_app" required>
                                            <option value="" disabled <?= empty($settingData->mode_app) ? 'selected' : '' ?>>Pilih Mode</option>
                                            <?php foreach ($modeOptions as $key => $label): ?>
                                                <option value="<?= $key ?>" <?= ((string) ($settingData->mode_app ?? '')) === (string) $key ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="font-weight-semibold">API Key (OpenAI / Gemini)</label>
                                    <input type="text" name="api_key" class="form-control-tw"
                                        value="<?= $esc($settingData->api_key ?? '') ?>"
                                        placeholder="Paste API Key disini...">
                                    <span class="text-sm text-slate-400">Kunci API diperlukan untuk fitur generate soal otomatis
                                        menggunakan AI.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <button type="submit" class="btn-tw btn-tw-sm btn-primary" onclick="submitSetting()"><i
                                class="bi bi-save mr-1"></i>Simpan Pengaturan</button>
                    </div>
                </div>
                <?= form_close() ?>
            </div>
        </div>

        <div class="card-tw">
            <div class="card-tw-header"
                style="background: linear-gradient(135deg, #f6f9ff 0%, #e6f2ff 100%); color: #111;">
                <h6 class="mb-0">Identitas Visual & Dokumen Pendukung</h6>
                <span class="text-slate-400 text-sm">Format file JPG/PNG maksimal 2MB</span>
            </div>
            <div class="card-tw-body">
                <div class="callout callout-warning shadow-sm border border-warning mb-6">
                    <h6 class="font-weight-bold mb-2"><i class="bi bi-images mr-2"></i>Panduan Upload</h6>
                    <ul class="pl-4 mb-0">
                        <li>Gunakan resolusi tinggi agar logo dan banner tampil tajam pada laporan.
                        <li>Banner disarankan berukuran 1020x250 piksel dengan rasio 4:1.
                        <li>Format transparan (PNG) ideal untuk tanda tangan digital dan cap sekolah.
                    </ul>
                </div>

                <div class="flex flex-wrap gap-4">
                    <?php
                    $uploadInputs = [
                        ['id' => 'set-stampel', 'input_id' => 'stampel', 'label' => 'Cap / Stampel Sekolah', 'field' => 'stampel'],
                        ['id' => 'set-tandatangan', 'input_id' => 'tanda-tangan', 'label' => 'TTD Kepala Sekolah', 'field' => 'tanda_tangan'],
                        ['id' => 'set-logo-kiri', 'input_id' => 'logo-kiri', 'label' => 'Logo Kiri / Aplikasi', 'field' => 'logo_kiri'],
                        ['id' => 'set-logo-kanan', 'input_id' => 'logo-kanan', 'label' => 'Logo Kanan', 'field' => 'logo_kanan'],
                        ['id' => 'set-kopsekolah', 'input_id' => 'kopsekolah', 'label' => 'Kop Sekolah', 'field' => 'kopsekolah'],
                    ];

                    foreach ($uploadInputs as $item):
                        $val = !empty($settingData->{$item['field']}) ? base_url($settingData->{$item['field']}) : '';
                        ?>
                        <div class="flex-1">
                            <?= form_open_multipart('', ['id' => $item['id']]) ?>
                            <label class="font-weight-semibold" for="<?= $item['input_id'] ?>">
                                <?= $item['label'] ?>
                            </label>
                            <input type="file" id="<?= $item['input_id'] ?>" name="logo" class="dropify"
                                data-max-file-size-preview="2M" data-allowed-file-extensions="jpg jpeg png"
                                data-default-file="<?= $val ?>" />
                            <?= form_close() ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/plugins/dropify/js/dropify.min.js') ?>"></script>
<script>
    const mediaMappings = {
        'logo-kanan': 'logo_kanan',
        'logo-kiri': 'logo_kiri',
        'stampel': 'stampel',
        'tanda-tangan': 'tanda_tangan',
        'ttdproktor': 'ttdproktor',
        'kodescan': 'kodescan',
        'kopsekolah': 'kopsekolah',
        'banner1': 'banner1',
        'banner2': 'banner2',
        'banner3': 'banner3'
    };

    const baseUrl = '<?= base_url() ?>';
    const baseUrlNormalized = baseUrl.replace(/\/+$/, '');
    const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    let csrf = {
        name: '<?= csrf_token() ?>',
        hash: '<?= csrf_hash() ?>'
    };

    const syncAjaxCsrf = () => {
        const payload = {};
        payload[csrf.name] = csrf.hash;
        $.ajaxSetup({ data: payload });
    };

    const updateCsrfFromResponse = (response) => {
        if (response && response.csrfName && response.csrfHash) {
            csrf = {
                name: response.csrfName,
                hash: response.csrfHash
            };
            syncAjaxCsrf();
        }
    };

    const appendCsrfToFormData = (formData) => {
        if (formData instanceof FormData && csrf?.name && csrf?.hash) {
            formData.append(csrf.name, csrf.hash);
        }
    };

    syncAjaxCsrf();

    const toRelativePath = (url) => {
        if (!url) {
            return '';
        }
        // Simplified Logic: if url starts with base_url, strip it
        if (url.startsWith(baseUrl)) {
            return url.substring(baseUrl.length);
        }
        return url;
    };

    let skipDropifyClear = false;

    // Logos object for submit
    const logos = {
        logo_kanan: '<?= $esc($settingData->logo_kanan ?? '') ?>',
        logo_kiri: '<?= $esc($settingData->logo_kiri ?? '') ?>',
        kodescan: '<?= $esc($settingData->kodescan ?? '') ?>',
        kopsekolah: '<?= $esc($settingData->kopsekolah ?? '') ?>',
        stampel: '<?= $esc($settingData->stampel ?? '') ?>',
        ttdproktor: '<?= $esc($settingData->ttdproktor ?? '') ?>',
        tanda_tangan: '<?= $esc($settingData->tanda_tangan ?? '') ?>',
        banner1: '<?= $esc($settingData->banner1 ?? '') ?>',
        banner2: '<?= $esc($settingData->banner2 ?? '') ?>',
        banner3: '<?= $esc($settingData->banner3 ?? '') ?>'
    };

    const satuanPend = <?= json_encode($satuan) ?>;

    function submitSetting() {
        $('#savesetting').submit();
    }

    $(document).ready(function () {
        // ajaxcsrf(); // Already using syncAjaxCsrf

        // Attach submit handler FIRST to ensure it works even if plugins fail
        $('#savesetting').on('submit', function (e) {
            e.preventDefault();

            const hasInput = $('.required').toArray().every(function (input) {
                return $(input).val().trim() !== '';
            });

            if (!hasInput) {
                Swal.fire({
                    title: 'Error',
                    text: 'Isi semua kolom yang bertanda bintang (*)',
                    icon: 'error'
                });
                return;
            }

            Swal.fire({
                text: 'Menyimpan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: baseUrl + 'setting/saveSetting',
                type: 'POST',
                data: $(this).serialize() + '&' + $.param(logos),
                success: function (response) {
                    if (response.status) {
                        Swal.fire({
                            title: 'Sukses',
                            text: response.message || 'Pengaturan berhasil disimpan',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Pesan server menyebut bagian mana yang tidak tersimpan
                        // (mis. pengaturan notifikasi), bukan sekadar "kesalahan".
                        Swal.fire('Gagal', response.message || 'Terjadi kesalahan saat menyimpan', 'error');
                    }
                },
                error: function (xhr) {
                    let errorMsg = 'Gagal menyimpan pengaturan';
                    try {
                        const err = JSON.parse(xhr.responseText);
                        errorMsg = err.Message || errorMsg;
                    } catch (error) {
                        // console.error('Error parsing response:', xhr.responseText);
                    }
                    Swal.fire({
                        title: 'Error',
                        text: errorMsg,
                        icon: 'error'
                    });
                }
            });
        });

        $('#jenjang').on('change', function () {
            const jenjangVal = $(this).val();
            const options = ['<option value="" disabled selected>Pilih Satuan Pendidikan</option>'];

            if (satuanPend[jenjangVal]) {
                satuanPend[jenjangVal].forEach(function (label, index) {
                    options.push(`<option value="${index + 1}">${label}</option>`);
                });
            }

            $('#satuan-pend').html(options.join(''));
        });

        // Initialize Dropify if available
        if ($.fn.dropify) {
            const drEvent = $('.dropify').dropify({
                messages: {
                    default: 'Seret logo ke sini atau klik',
                    replace: 'Seret atau klik untuk mengganti logo',
                    remove: 'Hapus',
                    error: 'Oops, ada kesalahan!'
                },
                error: {
                    fileSize: 'Ukuran file terlalu besar (maks {{ value }}).',
                    imageFormat: 'Format gambar tidak diizinkan (hanya {{ value }}).'
                }
            });

            drEvent.on('dropify.afterClear', function (event, element) {
                if (skipDropifyClear) {
                    skipDropifyClear = false;
                    return;
                }
                const inputId = element.element.id;
                const fieldKey = mediaMappings[inputId] || null;
                const $input = $(event.currentTarget);
                const defaultFile = $input.attr('data-default-file') || '';

                if (fieldKey) {
                    logos[fieldKey] = '';
                    if (defaultFile) {
                        deleteImage(defaultFile, fieldKey);
                    }
                }

                $input.attr('data-default-file', '');
            });

            drEvent.on('dropify.errors', function () {
                showWarningToast('File tidak valid atau terlalu besar');
            });

            $('.dropify').on('change', function () {
                const input = this;
                const inputId = input.id;
                const fieldKey = mediaMappings[inputId] || null;

                if (!fieldKey || !(input.files && input.files[0])) {
                    return;
                }

                if (input.files[0].size > 2 * 1024 * 1024) {
                    showDangerToast('Ukuran file maksimum adalah 2MB');
                    $(input).val('');
                    return;
                }

                const form = new FormData();
                form.append('logo', input.files[0], input.files[0].name);
                appendCsrfToFormData(form);
                uploadAttach(baseUrl + `setting/uploadFile/${fieldKey}`, form, fieldKey, inputId);
            });
        }
    });

    function uploadAttach(action, data, fieldKey, inputId) {
        $.ajax({
            type: 'POST',
            enctype: 'multipart/form-data',
            url: action,
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 600000,
            success: function (response) {
                updateCsrfFromResponse(response);
                if (response && response.status) {
                    const fileUrl = response.src || '';
                    const relativePath = response.relative_path || '';

                    logos[fieldKey] = relativePath;

                    const $input = $(`#${inputId}`);
                    if (fileUrl) {
                        $input.attr('data-default-file', fileUrl);
                    }
                    // Re-init dropify to update preview
                    const dropifyInstance = $input.data('dropify');
                    if (dropifyInstance) {
                        skipDropifyClear = true;
                        dropifyInstance.resetPreview();
                        dropifyInstance.settings.defaultFile = fileUrl;
                        dropifyInstance.destroy(); // needed to reset
                        dropifyInstance.init();
                        skipDropifyClear = false;
                    }

                    showSuccessToast('File berhasil diunggah');
                } else {
                    // Server sekarang mengirim "message" yang menjelaskan apa yang
                    // sebenarnya gagal (file tidak berpindah / DB tidak sinkron).
                    const message = response?.message || response?.src || 'Gagal mengunggah file';
                    showDangerToast(message);
                }
            },
            error: function (xhr) {
                const resp = xhr.responseJSON || {};
                const errMessage = resp?.src || resp?.message || 'Gagal mengunggah file';
                showDangerToast(errMessage);
            }
        });
    }

    function deleteImage(src, fieldKey) {
        if (!fieldKey || !src) return;

        const payload = { src: src, field: fieldKey };
        // CSRF handled by global setup or append
        // For simple object data, setup is enough, but updateCsrfFromResponse updates the global 'csrf' object
        payload[csrf.name] = csrf.hash;

        $.ajax({
            type: 'POST',
            url: baseUrl + 'setting/deleteFile',
            data: payload,
            cache: false,
            success: function (response) {
                updateCsrfFromResponse(response);
                console.log('File deleted:', response);
            },
            error: function (xhr) {
                console.error('Delete error:', xhr.responseText);
            }
        });
    }
</script>
<?= $this->endSection() ?>