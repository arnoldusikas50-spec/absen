<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$arrHari = ['0' => 'Tidak ada', "7" => "Minggu", "1" => "Senin", "2" => "Selasa", "3" => "Rabu", "4" => "Kamis", "5" => "Jumat", "6" => "Sabtu"];
$setting_kbm = $setting_kbm ?? (object) [];
if (!isset($setting_kbm->istirahat) || !is_array($setting_kbm->istirahat)) {
    $setting_kbm->istirahat = [];
}
$totalKelas = count($kelas ?? []);
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="bi bi-calendar-week text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Jadwal Pelajaran') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="text-slate-400">Data Umum</span>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($judul ?? 'Jadwal Pelajaran') ?></span>
        </nav>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-area mt-4 space-y-6">

    <?php if (!empty($penugasan_kosong)): ?>
    <?php
        $previewLabels = array_map(static function ($row) {
            return trim((string) ($row->nama_kelas ?? ('Kelas #' . (int) $row->id_kelas)))
                . ' - ' . trim((string) ($row->nama_mapel ?? ('Mapel #' . (int) $row->id_mapel)));
        }, array_slice($penugasan_kosong, 0, 5));
    ?>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5 shadow-xs flex items-start gap-3">
        <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </span>
        <div class="min-w-0 text-xs text-amber-900">
            <p class="font-bold m-0 mb-0.5">
                <?= count($penugasan_kosong) ?> pasangan kelas-mapel belum punya guru pengampu
            </p>
            <p class="m-0 text-amber-800/80 leading-relaxed">
                Sesi ini tidak muncul di quick-pick jurnal dan tidak ikut dihitung pada gerbang wajib jurnal sebelum pulang.
                <?= esc(implode(', ', $previewLabels)) ?><?= count($penugasan_kosong) > count($previewLabels) ? ', dan lainnya' : '' ?>.
            </p>
            <a href="<?= site_url('dataguru') ?>" class="inline-flex items-center gap-1 mt-1.5 font-bold text-amber-800 hover:text-amber-900 no-underline">
                <i class="bi bi-person-check"></i> Atur penugasan guru
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                    <i class="bi bi-calendar-range text-blue-600"></i>
                    Matriks Jadwal KBM
                </h4>
                <p class="text-xs text-slate-500 mb-0">Atur alokasi jam belajar mengajar per hari untuk masing-masing rombel</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?= site_url('admin/jurnal') ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs no-underline">
                    <i class="bi bi-journal-check"></i> Monitoring Jurnal
                </a>
                <button type="button" onclick="reload()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
                <?php if ($is_admin ?? false): ?>
                    <button id="copy-jadwal" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors shadow-xs" onclick="copyJadwal()">
                        <i class="bi bi-copy"></i> Copy Jadwal
                    </button>
                    <button id="edit-jadwal" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20" data-ui-toggle="modal" data-ui-target="#createJadwalModal">
                        <i class="bi bi-gear-fill"></i> Set Jadwal
                    </button>
                    <a href="<?= site_url('jadwal/export-template') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs no-underline">
                        <i class="bi bi-file-earmark-excel"></i> Template Excel
                    </a>
                    <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs" onclick="document.getElementById('importJadwalFile').click()">
                        <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
                    </button>
                    <input type="file" id="importJadwalFile" accept=".xlsx,.xls,.xml,.csv" class="hidden" onchange="importJadwal(this)">
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6">
            <?php if (empty($kelas)): ?>
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h5 class="font-bold text-slate-700 mb-1">Belum Ada Data Rombel</h5>
                    <p class="text-sm text-slate-400 max-w-sm mx-auto mb-4">
                        Belum ada data kelas untuk Tahun Pelajaran <b><?= esc($tp_active->tahun ?? '-') ?></b> Semester <b><?= esc($smt_active->smt ?? '-') ?></b>. Silakan tambahkan kelas terlebih dahulu.
                    </p>
                    <a href="<?= site_url('datakelas') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 shadow-xs shadow-blue-500/20">
                        <i class="bi bi-plus-lg"></i> Kelola Kelas
                    </a>
                </div>
            <?php else: ?>
                <!-- Guide Callout -->
                <div class="mb-5 p-4 rounded-2xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 flex items-start gap-3">
                    <i class="bi bi-info-circle-fill text-blue-600 text-base shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <p class="font-semibold text-blue-950 mb-0.5">Petunjuk Pengaturan Jadwal:</p>
                        <p class="mb-0 text-blue-800">1. Klik tombol hari di bawah untuk menampilkan atau mengatur jadwal pada hari tersebut.</p>
                        <p class="mb-0 text-blue-800">2. Klik salah satu slot waktu di tabel, pilih mata pelajaran, lalu tentukan <b>jumlah jam pelajaran</b> — cukup diisi sekali meski 2 jam pelajaran.</p>
                        <?php if ($is_admin ?? false): ?>
                            <p class="mb-0 text-blue-800">3. Gunakan <b>Set Jadwal</b> untuk mengatur slot jam pelajaran sekaligus untuk seluruh kelas.</p>
                            <p class="mb-0 text-blue-800">4. Jadwal dengan guru pengajar otomatis muncul sebagai pilihan cepat saat guru menulis jurnal.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Day Selector Pills -->
                <div class="mb-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5">Pilih Hari Pembelajaran:</p>
                    <div id="arr-kelas" class="flex flex-wrap gap-2">
                        <!-- Rendered by renderHariButtons() in JS -->
                    </div>
                </div>

                <!-- Empty Day Alert -->
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200" id="alert-select-day">
                    <i class="bi bi-hand-index-thumb text-3xl text-blue-500 mb-2 block"></i>
                    <h5 class="font-bold text-slate-700 mb-1">Silakan Pilih Hari</h5>
                    <p class="text-xs text-slate-400 mb-0">Pilih salah satu tombol hari di atas untuk memuat matriks jadwal harian.</p>
                </div>

                <!-- Timetable Target Container -->
                <div id="table-jadwal" class="hidden mt-4"></div>

                <?php if ($is_admin ?? false): ?>
                    <div class="mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hidden" id="reformat">
                        <div class="text-xs text-slate-600">
                            <span class="font-bold text-slate-800 block mb-0.5">Reformat Garis Waktu:</span>
                            Jika susunan slot jam pada tabel tidak sejajar, sinkronkan kembali dengan tombol Reformat.
                        </div>
                        <button type="button" onclick="reformat()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors shadow-2xs shrink-0">
                            <i class="bi bi-arrow-repeat"></i> Reformat Jadwal
                        </button>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- Loading Overlay -->
        <div class="overlay hidden absolute inset-0 bg-white/75 flex items-center justify-center rounded-2xl z-30" id="loading">
            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 shadow-xl text-blue-600 text-xs font-semibold">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                <span>Memuat data jadwal...</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal Set Jadwal -->
<div id="createJadwalModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="createJadwalModalLabel" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-xl">
            <?= form_open('admin/jadwal/save_setting', ['id' => 'setjadwal']); ?>
            <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between">
                <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="createJadwalModalLabel">
                    <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm">
                        <i class="bi bi-gear-fill"></i>
                    </span>
                    <span>Pengaturan Jadwal KBM</span>
                </h5>
                <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="p-6 space-y-4" id="inputs">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Hari Libur Mingguan <span class="text-rose-500">*</span></label>
                    <div id="hari_libur">
                        <?= form_dropdown('hari_libur', $arrHari, $setting_kbm->libur ?? '', 'class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required'); ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jam Masuk KBM <span class="text-rose-500">*</span></label>
                        <input type="time" name="jam_mulai" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jam Pulang KBM <span class="text-rose-500">*</span></label>
                        <input type="time" name="jam_selesai" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <label class="flex items-center gap-2.5 cursor-pointer mb-0">
                        <input class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer" type="checkbox" name="format" id="formatted">
                        <span class="text-xs font-medium text-slate-700">Terapkan format pembagian slot otomatis ke seluruh kelas</span>
                    </label>
                </div>

                <div id="format-jadwal" class="hidden border-t border-slate-100 pt-4 space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 block">Hari Upacara</label>
                            <?= form_dropdown('hari_upacara', $arrHari, $setting_kbm->upacara->id_hari ?? '', 'class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl"'); ?>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 block">Jam Ke</label>
                            <select name="jam_upacara" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 block">Durasi (Mnt)</label>
                            <input type="number" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl" value="45" name="durasi_upacara">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 block">Durasi Mapel (Mnt)</label>
                            <input type="number" name="jam_mapel" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl" value="45" id="jam_mapel">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 block">Jml Mapel/Hari</label>
                            <input type="number" name="jml_mapel" id="jml_mapel" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl" value="10">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 block">Jml Istirahat</label>
                            <select name="jum_ist" id="jum_ist" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                                <option value="0">Tidak ada</option>
                                <option value="1">1 Kali</option>
                                <option value="2">2 Kali</option>
                                <option value="3">3 Kali</option>
                            </select>
                        </div>
                    </div>

                    <div id="list-istirahat" class="space-y-2"></div>
                </div>
            </div>
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>
            <?= form_close() ?>
    </div>
</div>

<!-- Modal Edit Mapel per Slot -->
<div id="hariJadwalModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-labelledby="hariJadwalModalLabel" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
            <?= form_open('admin/jadwal/save_mapel_slot', ['id' => 'setMapel']); ?>
            <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between">
                <h5 class="font-bold text-slate-800 text-base mb-0 flex items-center gap-2" id="hariJadwalModalLabel">
                    <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <span>Set Mata Pelajaran Slot</span>
                </h5>
                <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors" data-ui-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div id="konten-input"></div>
                <input type="hidden" id="kelas" name="kelas" value="">
                <input type="hidden" id="hari" name="hari" value="">
                <input type="hidden" id="jadwal" name="jadwal" value="">

                <input type="hidden" id="id_kbm" name="id_kbm" value="">
                <input type="hidden" id="kbm_mulai" name="kbm_mulai" value="">
                <input type="hidden" id="kbm_selesai" name="kbm_selesai" value="">

                <div class="p-3 bg-blue-50/80 rounded-xl border border-blue-100 text-xs text-blue-800 flex items-center gap-2">
                    <i class="bi bi-info-circle text-blue-600 shrink-0"></i>
                    <span>Jumlah jam menentukan durasi mapel. Rentang yang bentrok dengan Istirahat, Upacara, atau jadwal lain akan ditolak.</span>
                </div>
            </div>
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                <button type="button" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors" id="hapus-jadwal">
                    <i class="bi bi-trash3"></i> Hapus
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </div>
            <?= form_close() ?>
    </div>
</div>

<style>
    /* Custom Styles for Timetable Grid */
    .my-timetable th,
    .my-timetable td {
        border: 1px solid #e2e8f0;
        padding: 0.35rem 0.5rem;
        font-size: 0.8rem;
    }

    .my-timetable tbody tr {
        height: 34px;
    }

    .my-timetable thead th {
        position: sticky;
        top: 0;
        z-index: 6;
        background-color: #1e293b !important;
    }

    .frozen-header {
        position: sticky;
        left: 0;
        z-index: 10;
        background-color: #1e293b !important;
        color: white;
    }

    .my-timetable thead th.frozen-header {
        top: 0;
        left: 0;
        z-index: 12;
    }

    .frozen-column {
        position: sticky;
        left: 0;
        z-index: 5;
        background-color: #f8fafc;
        border-right: 2px solid #cbd5e1;
    }

    .day-pill.active {
        background-color: var(--primary-600) !important;
        color: white !important;
        border-color: var(--primary-600) !important;
        box-shadow: 0 4px 12px rgb(var(--primary-600-rgb) / 0.25);
    }

    .add-mapel .cell-add-icon {
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .add-mapel:hover .cell-add-icon {
        opacity: 1;
    }

    @media (hover: none) {
        .add-mapel .cell-add-icon {
            opacity: 0.45;
        }
    }
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Pass PHP data to JS
    let settingKbm = <?= json_encode($setting_kbm) ?>;
    let kelasList = <?= isset($kelas) ? json_encode($kelas) : '{}' ?>;
    let mapelList = <?= isset($mapels) ? json_encode($mapels) : '{}' ?>;
    let guruList = <?= isset($gurus) ? json_encode($gurus) : '[]' ?>;
    let isAdmin = <?= ($is_admin ?? false) ? 'true' : 'false' ?>;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[ch]);
    }
    const arrHari = { "7": "Minggu", "1": "Senin", "2": "Selasa", "3": "Rabu", "4": "Kamis", "5": "Jumat", "6": "Sabtu" };

    // Global State
    let activeHari = null;
    let minTime = '07:00';
    let maxTime = '15:00';
    let mapelData = {};
    let kbmData = {};

    // Config from PHP (TP/SMT)
    const tp = {
        id_tp: '<?= $tp_active->id_tp ?? '' ?>',
        tahun: '<?= $tp_active->tahun ?? '' ?>'
    };
    const smt = {
        id_smt: '<?= $smt_active->id_smt ?? '' ?>',
        smt: '<?= $smt_active->smt ?? '' ?>'
    };

    $(document).ready(function () {
        // Render Hari Buttons
        renderHariButtons();
        fillSettingForm();

        // Event Listeners for Modal Logic
        $('#formatted').change(function () {
            if ($(this).is(':checked')) {
                $('#format-jadwal').removeClass('hidden');
                $('#jam_mapel, #jml_mapel').prop('required', true);
            } else {
                $('#format-jadwal').addClass('hidden');
                $('#jam_mapel, #jml_mapel').prop('required', false);
            }
        });

        $('#jum_ist').change(function () {
            renderInputIstirahat($(this).val());
        });

        // Modal Set Jadwal shown
        $('#createJadwalModal').on('shown', fillSettingForm);

        // Form Submit Setting
        $('#setjadwal').submit(function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            var modalEl = document.getElementById('createJadwalModal');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false, contentType: false,
                dataType: 'json',
                success: function (res) {
                    AdminUI.modal.close(modalEl);
                    if (res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function () {
                    AdminUI.modal.close(modalEl);
                    Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                }
            });
        });

        // Form Submit Mapel Slot
        $('#setMapel').submit(function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            var modalEl = document.getElementById('hariJadwalModal');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false, contentType: false,
                dataType: 'json',
                success: function (res) {
                    AdminUI.modal.close(modalEl);
                    if (res.status) {
                        if (activeHari) loadJadwalHarian(activeHari);
                        Swal.fire({
                            toast: true, position: 'top-end',
                            icon: 'success', title: res.message,
                            showConfirmButton: false, timer: 2000
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                },
                error: function () {
                    AdminUI.modal.close(modalEl);
                    Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                }
            });
        });

        // Hapus Jadwal Mapel (Per Slot)
        $('#hapus-jadwal').click(function () {
            const idJadwal = $('#jadwal').val();
            if (!idJadwal) return;

            var modalEl = document.getElementById('hariJadwalModal');

            Swal.fire({
                title: 'Hapus Jadwal?',
                text: "Mapel ini akan dihapus dari slot waktu terpilih.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('admin/jadwal/delete_mapel') ?>',
                        type: 'POST',
                        data: { id_jadwal: idJadwal, <?= csrf_token() ?>: '<?= csrf_hash() ?>' },
                        dataType: 'json',
                        success: function (res) {
                            AdminUI.modal.close(modalEl);
                            if (res.status) {
                                if (activeHari) loadJadwalHarian(activeHari);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        },
                        error: function () {
                            AdminUI.modal.close(modalEl);
                            Swal.fire('Error', 'Gagal menghubungi server', 'error');
                        }
                    });
                }
            });
        });

        // Setup Global Event Listener for Cell Click
        document.addEventListener('click', onCellClick);
    });

    function settingLibur() {
        const value = settingKbm.libur;
        return (value === undefined || value === null || value === '') ? '7' : String(value);
    }

    function fillSettingForm() {
        const upacara = (settingKbm.upacara && typeof settingKbm.upacara === 'object') ? settingKbm.upacara : {};
        const istirahat = Array.isArray(settingKbm.istirahat) ? settingKbm.istirahat : [];

        $('select[name="hari_libur"]').val(settingLibur());
        $("#inputs input[name='jam_mulai']").val(settingKbm.kbm_jam_mulai || '07:00');
        $("#inputs input[name='jam_selesai']").val(settingKbm.kbm_jam_selesai || '15:00');

        $('select[name="hari_upacara"]').val(String(upacara.id_hari || '0'));
        $('select[name="jam_upacara"]').val(String(upacara.jam_ke || '1'));
        $('input[name="durasi_upacara"]').val(upacara.durasi || 45);

        $('input[name="jam_mapel"]').val(settingKbm.kbm_jam_pel || 45);
        $('input[name="jml_mapel"]').val(settingKbm.kbm_jml_mapel_hari || 10);

        const jumIst = $('select[name="jum_ist"]');
        for (let i = jumIst.find('option').length; i <= istirahat.length; i++) {
            jumIst.append(`<option value="${i}">${i} Kali</option>`);
        }
        jumIst.val(String(istirahat.length));
        renderInputIstirahat(istirahat.length, istirahat);
    }

    function renderHariButtons() {
        const libur = settingLibur();
        const hasLibur = libur !== '0';
        const container = $('#arr-kelas');
        container.empty();

        const dayOrder = ['1', '2', '3', '4', '5', '6', '7'];

        dayOrder.forEach(key => {
            if (hasLibur && key === libur) return;
            const btn = $(`<button type="button" class="day-pill px-4 py-2 text-xs font-bold rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition-all shadow-2xs" data-hari="${key}">
                <i class="bi bi-calendar-event mr-1.5 text-blue-500"></i> ${arrHari[key]}
            </button>`);

            btn.click(function () {
                $('.day-pill').removeClass('active');
                $(this).addClass('active');
                activeHari = key;
                loadJadwalHarian(key);
            });

            container.append(btn);
        });
    }

    function loadJadwalHarian(hari) {
        if (!hari) return;
        $('#alert-select-day').addClass('hidden');
        $('#loading').removeClass('hidden');
        $('#table-jadwal').addClass('hidden').empty();

        $.ajax({
            url: `<?= base_url(($is_admin ?? false) ? 'admin/jadwal/get_jadwal' : 'guru/jadwal/get_jadwal') ?>/${hari}`,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    minTime = res.data.min_time || '07:00';
                    maxTime = res.data.max_time || '15:00';
                    mapelData = res.data.mapel || {};
                    kbmData = res.data.kbm || {};

                    if (isAdmin) {
                        $('#edit-jadwal').prop('disabled', false);
                        $('#copy-jadwal').prop('disabled', false);
                        $('#reformat').removeClass('hidden');
                    }

                    try {
                        renderTimetable();
                        $('#table-jadwal').removeClass('hidden');
                    } catch (err) {
                        console.error('[jadwal] gagal merender tabel', err);
                        $('#table-jadwal')
                            .html('<div class="p-4 text-sm text-rose-600">Gagal menampilkan jadwal. Silakan muat ulang halaman.</div>')
                            .removeClass('hidden');
                    }
                } else {
                    Swal.fire('Info', res.message, 'info');
                }
            },
            complete: function () {
                $('#loading').addClass('hidden');
            },
            error: function () {
                Swal.fire('Error', 'Gagal memuat jadwal', 'error');
                $('#loading').addClass('hidden');
            }
        });
    }

    function timeToMinutes(time) {
        const match = String(time ?? '').match(/^(\d{1,2}):(\d{2})/);
        if (!match) return null;
        return (parseInt(match[1], 10) * 60) + parseInt(match[2], 10);
    }

    function minutesToTime(minutes) {
        const value = Math.max(0, Math.min((24 * 60) - 1, Math.round(minutes)));
        return `${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}`;
    }

    function parseKbmList(value) {
        if (Array.isArray(value)) return value;
        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }
        return [];
    }

    function parseKbmObject(value) {
        if (value && typeof value === 'object') return value;
        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    /**
     * Baris jadwal dibentuk dari pengaturan KBM (jam mulai, durasi per jam,
     * istirahat, upacara), sehingga satu baris = satu jam pelajaran dan bisa
     * diberi label "Jam ke-1", "Jam ke-2", dst.
     */
    function buildPeriodRows() {
        const kbm = settingKbm || {};
        const mulai = timeToMinutes(kbm.kbm_jam_mulai) ?? timeToMinutes(minTime);
        const selesai = timeToMinutes(kbm.kbm_jam_selesai) ?? timeToMinutes(maxTime);
        const jamPel = parseInt(kbm.kbm_jam_pel, 10) || 0;
        if (mulai === null || selesai === null || jamPel <= 0 || selesai <= mulai) return null;

        const breaks = {};
        parseKbmList(kbm.istirahat).forEach(item => {
            const ke = parseInt(item && item.ist, 10);
            const dur = parseInt(item && item.dur, 10);
            if (!(ke >= 1) || !(dur >= 1)) return;
            breaks[ke] = (breaks[ke] || 0) + dur;
        });

        const upacara = parseKbmObject(kbm.upacara);
        const upacaraDur = parseInt(upacara.durasi, 10) || 0;
        const upacaraJamKe = Math.max(1, parseInt(upacara.jam_ke, 10) || 1);
        const hasUpacara = parseInt(upacara.id_hari, 10) === parseInt(activeHari, 10) && upacaraDur > 0;

        const rows = [];
        let cursor = mulai;
        const push = (from, to, kind, jamKe) => {
            if (from >= selesai) return;
            to = Math.min(to, selesai);
            if (to <= from) return;
            rows.push({
                startMin: from,
                endMin: to,
                start: minutesToTime(from),
                end: minutesToTime(to),
                kind: kind,
                jamKe: jamKe,
            });
        };

        if (hasUpacara && upacaraJamKe === 1) {
            push(cursor, cursor + upacaraDur, 'upacara', 0);
            cursor += upacaraDur;
        }

        // Batas 30 jam mencegah loop panjang bila data KBM tidak wajar.
        for (let i = 1; i <= 30 && cursor < selesai; i++) {
            push(cursor, cursor + jamPel, 'jam', i);
            cursor += jamPel;

            if (hasUpacara && upacaraJamKe === i + 1) {
                push(cursor, cursor + upacaraDur, 'upacara', 0);
                cursor += upacaraDur;
            }

            if (breaks[i]) {
                push(cursor, cursor + breaks[i], 'istirahat', 0);
                cursor += breaks[i];
            }
        }

        return rows.length ? rows : null;
    }

    function periodLabel(row) {
        if (row.kind === 'istirahat') return 'Istirahat';
        if (row.kind === 'upacara') return 'Upacara';
        return `Jam ke-${row.jamKe}`;
    }

    function buildFallbackSegments() {
        const marks = new Set();
        const addMark = (time) => {
            const value = timeToMinutes(time);
            if (value !== null) marks.add(value);
        };

        addMark(minTime);
        addMark(maxTime);
        Object.values(mapelData || {}).forEach(list => {
            (list || []).forEach(slot => {
                addMark(slot.dari);
                addMark(slot.sampai);
            });
        });

        const boundaries = Array.from(marks).sort((a, b) => a - b);
        if (boundaries.length < 2) return [];

        const axis = [];
        boundaries.forEach((start, index) => {
            axis.push(start);
            const next = boundaries[index + 1];
            if (next === undefined) return;

            let tick = Math.floor(start / 30) * 30 + 30;
            while (tick < next) {
                axis.push(tick);
                tick += 30;
            }
        });

        const unique = Array.from(new Set(axis)).sort((a, b) => a - b);
        const segments = [];
        for (let i = 0; i < unique.length - 1; i++) {
            segments.push({
                start: minutesToTime(unique[i]),
                end: minutesToTime(unique[i + 1]),
                startMin: unique[i],
                endMin: unique[i + 1],
                kind: '',
                jamKe: 0,
                label: '',
            });
        }
        return segments;
    }

    function buildTimeSegments() {
        const rows = buildPeriodRows();
        if (!rows) return buildFallbackSegments();

        const marks = new Set();
        rows.forEach(row => {
            marks.add(row.startMin);
            marks.add(row.endMin);
        });

        // Batas slot lama yang tidak sejajar tetap dimasukkan agar jadwal
        // tersebut tidak hilang dari tabel (label lanjutan dikosongkan).
        Object.values(mapelData || {}).forEach(list => {
            (list || []).forEach(slot => {
                const from = timeToMinutes(slot.dari);
                const to = timeToMinutes(slot.sampai);
                if (from !== null) marks.add(from);
                if (to !== null) marks.add(to);
            });
        });

        const boundaries = Array.from(marks).sort((a, b) => a - b);
        const segments = [];
        for (let i = 0; i < boundaries.length - 1; i++) {
            const start = boundaries[i];
            const end = boundaries[i + 1];
            if (end <= start) continue;

            const owner = rows.find(row => start >= row.startMin && start < row.endMin);
            const isRowStart = owner && start === owner.startMin;

            segments.push({
                start: minutesToTime(start),
                end: minutesToTime(end),
                startMin: start,
                endMin: end,
                kind: owner ? owner.kind : '',
                jamKe: isRowStart ? owner.jamKe : 0,
                label: isRowStart ? periodLabel(owner) : '',
            });
        }
        return segments;
    }

    // THE CORE LOGIC ported from CI3 createTable()
    function renderTimetable() {
        // Kunci objek angka selalu dienumerasi naik oleh JavaScript, sehingga
        // urutan nama kelas dari server harus dipulihkan secara eksplisit.
        const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
        const arrKelasKeys = Object.keys(kelasList).sort((a, b) => {
            const byName = collator.compare(String(kelasList[a] ?? ''), String(kelasList[b] ?? ''));
            return byName !== 0 ? byName : (Number(a) - Number(b));
        });
        const segments = buildTimeSegments();
        const indexByStart = new Map(segments.map((segment, index) => [segment.startMin, index]));

        const tableContainer = document.createElement('div');
        tableContainer.className = 'table-responsive-tw mb-4 rounded-2xl border border-slate-200 overflow-auto shadow-xs';
        tableContainer.style.maxHeight = '68vh';

        if (arrKelasKeys.length > 0 && segments.length > 0) {
            const table = document.createElement('table');
            table.className = 'my-timetable w-full border-collapse';
            table.id = 'tbl';

            // THEAD
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            headerRow.className = 'bg-slate-800 text-white';

            const thJam = document.createElement('th');
            thJam.colSpan = 2;
            thJam.style.cssText = 'width: 82px; min-width: 82px; max-width: 82px;';
            thJam.className = 'text-center frozen-header py-3 font-bold text-xs tracking-wider';
            thJam.textContent = segments.some(segment => segment.label) ? 'JAM KE' : 'JAM';
            headerRow.appendChild(thJam);

            arrKelasKeys.forEach(key => {
                const th = document.createElement('th');
                th.className = 'align-middle text-center py-3 px-3 font-bold text-xs tracking-wider bg-slate-800 text-white border-slate-700';
                th.style.minWidth = '110px';
                th.textContent = kelasList[key];
                th.setAttribute('data-kelas', key);
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // TBODY
            const tbody = document.createElement('tbody');
            const jadwalRowSpans = {};

            segments.forEach((segment, rowIndex) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/50';
                tr.setAttribute('data-time', segment.start);
                tr.setAttribute('data-time-end', segment.end);

                // Time Column
                const tdTime = document.createElement('td');
                tdTime.className = 'text-center align-middle bg-slate-50 border-r-2 border-slate-300';
                tdTime.style.cssText = 'position:sticky; left:0; z-index:5';
                const labelClass = segment.kind === 'istirahat'
                    ? 'text-amber-700'
                    : (segment.kind === 'upacara' ? 'text-indigo-700' : 'text-slate-700');
                const labelLine = segment.label
                    ? `<span class="block text-[11px] font-bold leading-tight ${labelClass}">${escapeHtml(segment.label)}</span>`
                    : '';
                tdTime.innerHTML = `${labelLine}
                    <span class="block text-[9px] font-medium leading-tight text-slate-400">${segment.start}-${segment.end}</span>`;
                tr.appendChild(tdTime);

                const tdDot = document.createElement('td');
                tdDot.style.cssText = 'width:4px; background:#e2e8f0; padding:0;';
                tr.appendChild(tdDot);

                // Class Columns
                arrKelasKeys.forEach(key => {
                    if (jadwalRowSpans[key] && jadwalRowSpans[key] > 0) {
                        jadwalRowSpans[key]--;
                        return;
                    }

                    const scheduleList = mapelData[key] || [];
                    const currentSlot = scheduleList.find(s => timeToMinutes(s.dari) === segment.startMin);

                    const td = document.createElement('td');
                    td.className = 'align-middle text-xs p-1';
                    td.setAttribute('data-dari', segment.start);
                    td.setAttribute('data-kelas', key);

                    const div = document.createElement('div');
                    div.className = 'h-full p-1.5 add-mapel rounded-xl transition-all';
                    div.style.minHeight = '24px';
                    div.style.cursor = 'pointer';
                    div.setAttribute('data-kelas', key);
                    div.setAttribute('data-dari', segment.start);
                    div.setAttribute('data-segmen-selesai', segment.end);
                    div.setAttribute('data-hari', activeHari);

                    const kbm = kbmData[key] || {};
                    div.setAttribute('data-kbmmulai', kbm.kbm_jam_mulai || '07:00');
                    div.setAttribute('data-kbmselesai', kbm.kbm_jam_selesai || '15:00');

                    if (currentSlot) {
                        const mapelId = parseInt(currentSlot.id_mapel, 10);
                        const endIndex = indexByStart.get(timeToMinutes(currentSlot.sampai));
                        const slotsCount = (endIndex !== undefined && endIndex > rowIndex)
                            ? (endIndex - rowIndex)
                            : 1;
                        if (slotsCount > 1) {
                            td.rowSpan = slotsCount;
                            jadwalRowSpans[key] = slotsCount - 1;
                        }

                        div.setAttribute('data-id', currentSlot.id_jadwal);
                        div.setAttribute('data-mapel', currentSlot.id_mapel);
                        div.setAttribute('data-sampai', currentSlot.sampai);
                        div.setAttribute('data-guru', (currentSlot.guru_ids || []).join(','));

                        const guruLabel = currentSlot.guru_nama
                            ? `<div class="text-[10px] text-slate-600 truncate" title="${escapeHtml(currentSlot.guru_nama)}">${escapeHtml(currentSlot.guru_nama)}</div>`
                            : '';

                        let content = '';
                        if (mapelId === -1) {
                            content = `<div class="p-1.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-medium text-center text-xs">
                                <i class="bi bi-cup-hot text-[11px] mr-1"></i> Istirahat
                                <div class="text-[10px] text-amber-600 font-normal">${currentSlot.dari}-${currentSlot.sampai}</div>
                            </div>`;
                        } else if (mapelId === -2) {
                            content = `<div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-800 border border-indigo-200 font-medium text-center text-xs">
                                <i class="bi bi-flag text-[11px] mr-1"></i> Upacara
                                <div class="text-[10px] text-indigo-600 font-normal">${currentSlot.dari}-${currentSlot.sampai}</div>
                            </div>`;
                        } else if (mapelId > 0) {
                            const durasi = (timeToMinutes(currentSlot.sampai) ?? 0) - (timeToMinutes(currentSlot.dari) ?? 0);
                            const jamPelKelas = parseInt((kbmData[key] || {}).kbm_jam_pel, 10);
                            const jp = (jamPelKelas > 0 && durasi > 0 && durasi % jamPelKelas === 0) ? (durasi / jamPelKelas) : 0;
                            const jpLabel = jp > 0 ? ` &middot; ${jp} JP` : '';
                            content = `<div class="p-1.5 rounded-lg bg-blue-50 text-blue-900 border border-blue-200 font-semibold text-center text-xs shadow-2xs hover:bg-blue-100/80 transition-colors">
                                <div class="truncate">${escapeHtml(currentSlot.kode || 'Mapel')}</div>
                                <div class="text-[10px] text-blue-600 font-normal">${currentSlot.dari}-${currentSlot.sampai}${jpLabel}</div>
                                ${guruLabel || '<div class="text-[10px] text-rose-500 font-medium">Guru belum diset</div>'}
                            </div>`;
                        } else {
                            content = '<div class="h-full w-full rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-300 text-xs transition-colors"><i class="bi bi-plus-lg cell-add-icon"></i></div>';
                        }
                        div.innerHTML = content;
                    } else {
                        div.innerHTML = '<div class="h-full w-full rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-300 text-xs transition-colors"><i class="bi bi-plus-lg cell-add-icon"></i></div>';
                    }

                    td.appendChild(div);
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            tableContainer.appendChild(table);

            const target = document.getElementById("table-jadwal");
            target.innerHTML = '';
            target.appendChild(tableContainer);
        }
    }

    function onCellClick(event) {
        if (!isAdmin) return;
        const div = event.target.closest('.add-mapel');
        if (!div) return;

        const kelasId = div.getAttribute('data-kelas');
        const dari = div.getAttribute('data-dari');
        const mapel = div.getAttribute('data-mapel') || '0';
        const sampai = div.getAttribute('data-sampai') || div.getAttribute('data-segmen-selesai') || '';
        const idJadwal = div.getAttribute('data-id') || '';
        const guruIds = (div.getAttribute('data-guru') || '').split(',').filter(Boolean);

        const konten = $('#konten-input').empty();

        const kbm = kbmData[kelasId] || {};
        const jamPel = parseInt(kbm.kbm_jam_pel, 10) > 0
            ? parseInt(kbm.kbm_jam_pel, 10)
            : (parseInt(settingKbm.kbm_jam_pel, 10) > 0 ? parseInt(settingKbm.kbm_jam_pel, 10) : 0);
        const startMin = timeToMinutes(dari);
        const dayEndMin = timeToMinutes(kbm.kbm_jam_selesai || settingKbm.kbm_jam_selesai || maxTime);
        const maxJam = (jamPel > 0 && startMin !== null && dayEndMin !== null)
            ? Math.max(1, Math.min(20, Math.floor((dayEndMin - startMin) / jamPel)))
            : 12;

        // Slot baru langsung diisi satu jam pelajaran penuh. Jumlah jam
        // menentukan jam selesai, jadi sesi 2 jam cukup diisi satu kali.
        let jumlah = 1;
        let akhir = sampai || dari;
        if (idJadwal) {
            const durasi = (timeToMinutes(sampai) ?? 0) - (startMin ?? 0);
            jumlah = (jamPel > 0 && durasi > 0 && durasi % jamPel === 0) ? (durasi / jamPel) : 0;
            if (jumlah > maxJam) jumlah = 0;
        } else if (jamPel > 0 && startMin !== null) {
            akhir = minutesToTime(startMin + jamPel);
        }

        let opts = `<option value="0">-- Tanpa Mapel --</option>
                    <option value="-1">Istirahat</option>
                    <option value="-2">Upacara Bendera</option>`;
        // Key objek angka selalu dienumerasi naik oleh JavaScript, sehingga
        // urutan nama mapel dari server harus dipulihkan secara eksplisit.
        const mapelCollator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
        Object.entries(mapelList || {})
            .sort((a, b) => {
                const byName = mapelCollator.compare(String(a[1] ?? ''), String(b[1] ?? ''));
                return byName !== 0 ? byName : (Number(a[0]) - Number(b[0]));
            })
            .forEach(([id, nama]) => {
                opts += `<option value="${id}">${escapeHtml(nama)}</option>`;
            });

        let guruOpts = `<option value="0">-- Pilih Guru Pengajar --</option>`;
        (guruList || []).forEach(g => {
            guruOpts += `<option value="${g.id_guru}">${escapeHtml(g.nama_guru)}${g.nip ? ' (' + escapeHtml(g.nip) + ')' : ''}</option>`;
        });

        let jamOpts = '';
        for (let n = 1; n <= maxJam; n++) {
            jamOpts += `<option value="${n}">${n} jam pelajaran (${n * jamPel} menit)</option>`;
        }
        jamOpts += `<option value="0">Kustom (atur jam selesai manual)</option>`;

        const showJamCount = parseInt(mapel, 10) > 0 && jamPel > 0;

        const html = `
           <div class="mb-4">
             <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Mata Pelajaran <span class="text-rose-500">*</span></label>
             <select name="id_mapel" id="select-mapel" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden" required>
                ${opts}
             </select>
           </div>
           <div class="mb-4" id="guru-wrap">
             <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Guru Pengajar <span class="text-rose-500">*</span></label>
             <select name="id_guru" id="select-guru" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                ${guruOpts}
             </select>
             <p class="text-[10px] text-slate-400 mt-1 mb-0">Jadwal ini otomatis menjadi pilihan cepat pada form jurnal guru.</p>
           </div>
           <div class="mb-4${showJamCount ? '' : ' hidden'}" id="jam-count-wrap">
             <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah Jam Pelajaran <span class="text-rose-500">*</span></label>
             <select name="jumlah_jam" id="jumlah-jam" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                ${jamOpts}
             </select>
             <p class="text-[10px] text-slate-400 mt-1 mb-0">Pilih jumlah jam, jam selesai dihitung otomatis. Satu jam pelajaran = ${jamPel} menit.</p>
           </div>
           <div class="grid grid-cols-2 gap-4">
              <div>
                 <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Dari Jam</label>
                 <input type="text" name="jam_mulai" class="w-full px-3.5 py-2 text-sm bg-slate-100 border border-slate-200 rounded-xl text-slate-600 font-bold" value="${dari}" readonly>
              </div>
              <div>
                 <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Sampai Jam <span class="text-rose-500">*</span></label>
                 <input type="time" name="jam_selesai" id="jam-selesai" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden font-bold" value="${akhir}" required>
                 <p class="text-[10px] text-slate-400 mt-1 mb-0" id="durasi-hint"></p>
              </div>
           </div>
        `;
        konten.append(html);

        const $mapelSelect = $('#select-mapel');
        const $guruSelect = $('#select-guru');
        const $jamWrap = $('#jam-count-wrap');
        const $jamCount = $('#jumlah-jam');
        const $jamSelesai = $('#jam-selesai');
        const $durasiHint = $('#durasi-hint');

        function updateDurasiHint() {
            const endMin = timeToMinutes($jamSelesai.val());
            if (endMin === null || startMin === null || endMin <= startMin) {
                $durasiHint.text('');
                return;
            }
            const durasi = endMin - startMin;
            const n = (jamPel > 0 && durasi % jamPel === 0) ? (durasi / jamPel) : 0;
            $durasiHint.text((n > 0 ? n + ' jam pelajaran · ' : '') + durasi + ' menit');
        }

        function applyJumlahJam() {
            const n = parseInt($jamCount.val(), 10);
            if (n > 0 && jamPel > 0 && startMin !== null) {
                $jamSelesai.val(minutesToTime(startMin + (n * jamPel)));
            }
            updateDurasiHint();
        }

        function syncJumlahDariJamSelesai() {
            if (jamPel > 0 && startMin !== null) {
                const endMin = timeToMinutes($jamSelesai.val());
                const durasi = endMin !== null ? (endMin - startMin) : 0;
                const exact = (durasi > 0 && durasi % jamPel === 0) ? (durasi / jamPel) : 0;
                $jamCount.val(exact > 0 && exact <= maxJam ? String(exact) : '0');
            }
            updateDurasiHint();
        }

        let mapelSebelumnya = parseInt(mapel, 10) > 0;

        function toggleMapelSections() {
            const isMapel = parseInt($mapelSelect.val(), 10) > 0;
            const showCount = isMapel && jamPel > 0;
            $jamWrap.toggleClass('hidden', !showCount);
            $jamCount.prop('disabled', !showCount);
            if (showCount && !mapelSebelumnya) {
                // Mapel pertama kali dipilih pada slot kosong: mulai dari 1 jam.
                $jamCount.val('1');
                applyJumlahJam();
            }
            updateDurasiHint();
        }

        $mapelSelect.val(mapel);
        $guruSelect.val(guruIds.length ? guruIds[0] : '0');
        $jamCount.val(String(jumlah));
        $jamCount.prop('disabled', !showJamCount);
        $guruSelect.prop('disabled', parseInt(mapel, 10) <= 0);
        if (parseInt(mapel, 10) <= 0) $guruSelect.val('0');
        $('#guru-wrap').toggleClass('opacity-60', parseInt(mapel, 10) <= 0);
        updateDurasiHint();

        $mapelSelect.on('change', function () {
            const isMapel = parseInt($(this).val(), 10) > 0;
            $guruSelect.prop('disabled', !isMapel);
            $('#guru-wrap').toggleClass('opacity-60', !isMapel);
            if (!isMapel) $guruSelect.val('0');
            toggleMapelSections();
            mapelSebelumnya = isMapel;
        });
        $jamCount.on('change', applyJumlahJam);
        $jamSelesai.on('input change', syncJumlahDariJamSelesai);

        $('#kelas').val(kelasId);
        $('#hari').val(activeHari);
        $('#jadwal').val(idJadwal);

        if (idJadwal) $('#hapus-jadwal').removeClass('hidden');
        else $('#hapus-jadwal').addClass('hidden');

        var modalEl = document.getElementById('hariJadwalModal');
        AdminUI.modal.open(modalEl);
    }

    function renderInputIstirahat(count, values) {
        const existing = Array.isArray(values) ? values : [];
        const container = $('#list-istirahat');
        container.empty();
        for (let i = 1; i <= count; i++) {
            const current = existing[i - 1] || {};
            const jamKe = String(current.ist || '');
            const html = `
            <div class="grid grid-cols-2 gap-3 p-3 bg-slate-100/70 rounded-xl border border-slate-200/80">
                <div>
                    <label class="text-[11px] font-semibold text-slate-600 uppercase mb-1 block">Istirahat Ke-${i}</label>
                    <select name="ist${i - 1}" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg">
                        <option value="">Pilih Jam Ke...</option>
                        ${[...Array(15).keys()].map(k => `<option value="${k + 1}"${jamKe === String(k + 1) ? ' selected' : ''}>Jam Ke ${k + 1}</option>`).join('')}
                    </select>
                </div>
                <div>
                     <label class="text-[11px] font-semibold text-slate-600 uppercase mb-1 block">Durasi (Menit)</label>
                     <input type="number" name="dur_ist${i - 1}" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg" value="${current.dur || 15}">
                </div>
            </div>`;
            container.append(html);
        }
    }

    function reload() {
        if (activeHari) loadJadwalHarian(activeHari);
        else location.reload();
    }

    function reformat() {
        Swal.fire({
            title: "Reformat Jadwal?",
            text: "Susunan slot garis waktu harian akan diformat ulang dan disesuaikan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#2563eb",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Format Ulang",
            cancelButtonText: "Batal",
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({ title: "Memproses...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: '<?= base_url('admin/jadwal/reformat_jadwal') ?>',
                    type: "POST",
                    data: { <?= csrf_token() ?>: '<?= csrf_hash() ?>' },
                    dataType: 'json',
                    success: function (data) {
                        if (data.status) {
                            Swal.fire({
                                icon: "success",
                                title: "Sukses",
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => reload());
                        } else {
                            Swal.fire("Gagal", data.message || "Gagal reformat", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Gagal menghubungi server", "error");
                    }
                });
            }
        });
    }

    function copyJadwal() {
        const sm = smt.id_smt;
        const smtLalu = sm === '1' ? '2' : '1';

        const tahun = tp.tahun?.split('/');
        let thnLalu = '';
        if (sm === '1') {
            const t1 = parseInt(tahun[0]) - 1;
            const t2 = parseInt(tahun[1]) - 1;
            thnLalu = `${t1}/${t2}`;
        } else {
            thnLalu = tp.tahun;
        }

        Swal.fire({
            title: "Copy Jadwal Semester Lalu?",
            html: `Sumber Data: <b>Tahun ${thnLalu} Semester ${smtLalu === '1' ? 'Ganjil' : 'Genap'}</b>.<br>
                   <span class='text-rose-600 font-bold'>PERHATIAN:</span> Jadwal saat ini akan ditimpa.<br>
                   Jadwal hanya tersalin jika <b>kode kelas</b> sesuai.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#e11d48",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Copy Jadwal",
            cancelButtonText: "Batal",
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({ title: "Menyalin Jadwal...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: '<?= base_url('admin/jadwal/copy_jadwal') ?>',
                    type: "POST",
                    data: {
                        thnLalu: thnLalu,
                        smtLalu: smtLalu,
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.status) {
                            Swal.fire({
                                icon: "success",
                                title: "Sukses",
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => reload());
                        } else {
                            Swal.fire("Gagal", data.message || "Gagal menyalin jadwal", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Gagal menghubungi server", "error");
                    }
                });
            }
        });
    }

    function importJadwal(input) {
        const file = input.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('jadwal_file', file);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        Swal.fire({
            title: "Mengimpor Jadwal...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: '<?= base_url('jadwal/import') ?>',
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                input.value = '';
                if (data.status) {
                    let msg = data.message;
                    if (data.errors && data.errors.length > 0) {
                        msg += '\n\nPeringatan:\n' + data.errors.slice(0, 5).map(e => '• Baris ' + e.baris + ': ' + e.pesan).join('\n');
                        if (data.errors.length > 5) msg += '\n... dan ' + (data.errors.length - 5) + ' baris lainnya';
                    }
                    Swal.fire({
                        icon: "success",
                        title: "Sukses Impor",
                        text: msg,
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => reload());
                } else {
                    let msg = data.message || 'Gagal mengimpor jadwal';
                    if (data.errors && data.errors.length > 0) {
                        msg += '\n\nDetail:\n' + data.errors.slice(0, 5).map(e => '• Baris ' + e.baris + ': ' + e.pesan).join('\n');
                        if (data.errors.length > 5) msg += '\n... dan ' + (data.errors.length - 5) + ' baris lainnya';
                    }
                    Swal.fire("Gagal", msg, "error");
                }
            },
            error: function () {
                input.value = '';
                Swal.fire("Error", "Gagal menghubungi server", "error");
            }
        });
    }
</script>

<?= $this->endSection() ?>