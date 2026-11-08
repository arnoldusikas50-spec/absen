<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ── Status button group ── */
.btn-status {
    flex: 1;
    padding: 0.35rem 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    border-radius: 0.5rem;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    background: #f1f5f9;
    color: #64748b;
    white-space: nowrap;
}
.btn-status:hover { filter: brightness(0.94); }
.btn-status.selected-HADIR            { background:#dcfce7; color:#15803d; border-color:#22c55e; box-shadow:0 2px 8px rgba(34,197,94,.2); }
.btn-status.selected-SAKIT            { background:#fef9c3; color:#a16207; border-color:#eab308; }
.btn-status.selected-IZIN             { background:#e0f2fe; color:#0369a1; border-color:#38bdf8; }
.btn-status.selected-ALPA             { background:#fee2e2; color:#b91c1c; border-color:#ef4444; box-shadow:0 2px 8px rgba(239,68,68,.2); }
.btn-status.selected-BDR              { background:#ccfbf1; color:#0f766e; border-color:#14b8a6; }
.btn-status.selected-TANPA_KETERANGAN { background:#f1f5f9; color:#64748b; border-color:#94a3b8; }

/* ── Student row ── */
.student-row { transition: background 0.1s; }
.student-row:hover { background: #f8fafc; }

/* ── Avatar ── */
.avatar-initials {
    width:38px; height:38px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:0.8rem; font-weight:700; color:#fff;
    flex-shrink:0;
}

/* ── Jam badge ── */
.jam-badge {
    display:inline-flex; align-items:center; gap:0.25rem;
    padding:0.2rem 0.55rem; border-radius:9999px;
    font-size:0.7rem; font-weight:700;
}
.jam-masuk-badge  { background:#dcfce7; color:#15803d; }
.jam-keluar-badge { background:#e0f2fe; color:#0369a1; }
.jam-empty-badge  { background:#f1f5f9; color:#94a3b8; }

/* ── Jam input group ── */
.jam-group { display:flex; align-items:center; gap:0.375rem; }
.jam-group .lbl { font-size:0.65rem; color:#94a3b8; font-weight:600; width:14px; }

/* ── Count chips ── */
.count-chip {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:2rem; height:1.375rem;
    border-radius:9999px; font-size:0.7rem; font-weight:800;
    padding:0 0.4rem;
}

/* ── Rekap ── */
.rekap-cell { font-variant-numeric:tabular-nums; font-weight:700; }

@media print {
    .no-print { display: none !important; }
    .fade-in-up { animation: none !important; }
    body { background: #fff !important; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- PAGE HEADER (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs no-print">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-people-fill"></i> Absensi Harian Siswa
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul) ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul) ?></p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-indigo-600">Presensi Siswa</span>
        </nav>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-white p-1 rounded-xl border border-slate-200/80 w-fit shadow-xs no-print" id="ps-tabs">
        <button type="button" id="tab-absen-btn" data-tab="absen"
                class="tab-switch-btn px-4 py-1.5 rounded-lg text-sm font-semibold transition-all bg-indigo-600 text-white shadow-sm">
            <i class="bi bi-ui-checks mr-1.5"></i>Input Absensi
        </button>
        <button type="button" id="tab-rekap-btn" data-tab="rekap"
                class="tab-switch-btn px-4 py-1.5 rounded-lg text-sm font-semibold transition-all text-slate-500 hover:text-slate-700">
            <i class="bi bi-file-earmark-bar-graph mr-1.5"></i>Rekap Bulanan
        </button>
    </div>


    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB 1: INPUT ABSENSI                                     -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div id="tab-absen" class="space-y-4">

        <!-- Filter -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden no-print">
            <div class="flex items-center gap-2 p-5 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-funnel text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Filter Kelas &amp; Tanggal</h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Kelas -->
                    <div>
                        <label class="form-label-tw">Kelas <span class="text-red-500">*</span></label>
                        <select id="id_kelas" class="form-select-tw">
                            <option value="">— Pilih Kelas —</option>
                            <?php foreach ($kelass as $id => $nama): ?>
                            <option value="<?= $id ?>"><?= esc($nama) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Tanggal -->
                    <div>
                        <label class="form-label-tw">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="tgl" class="form-control-tw"
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <!-- Jam Masuk default (dari /settings) -->
                    <div>
                        <label class="form-label-tw">Jam Masuk Default</label>
                        <input type="time" id="jam_masuk_default" class="form-control-tw" value="<?= esc($jam_masuk_default ?? '07:15') ?>">
                        <p class="text-xs text-slate-400 mt-1">Otomatis dari /settings</p>
                    </div>
                    <!-- Jam Pulang default (dari /settings) -->
                    <div>
                        <label class="form-label-tw">Jam Pulang Default</label>
                        <input type="time" id="jam_pulang_default" class="form-control-tw" value="<?= esc($jam_pulang_default ?? '14:00') ?>">
                        <p class="text-xs text-slate-400 mt-1">Otomatis dari /settings</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-4">
                    <button type="button" id="btn-load" class="btn-tw btn-primary">
                        <i class="bi bi-search mr-1"></i> Tampilkan Siswa
                    </button>
                    <button type="button" id="btn-save" class="btn-tw btn-success hidden">
                        <i class="bi bi-check2-circle mr-1"></i> Simpan Absensi
                    </button>
                    <button type="button" id="btn-all-hadir" class="btn-tw btn-light hidden">
                        <i class="bi bi-check-all mr-1"></i> Semua Hadir
                    </button>
                    <button type="button" id="btn-all-bdr" class="btn-tw btn-light hidden">
                        <i class="bi bi-house-door mr-1"></i> Semua BDR
                    </button>
                    <div id="count-chips" class="hidden flex flex-wrap gap-1.5 ml-auto text-xs">
                        <span class="count-chip bg-emerald-100 text-emerald-700" id="chip-hadir">H:0</span>
                        <span class="count-chip bg-yellow-100 text-yellow-700"   id="chip-sakit">S:0</span>
                        <span class="count-chip bg-sky-100 text-sky-700"         id="chip-izin">I:0</span>
                        <span class="count-chip bg-red-100 text-red-700"         id="chip-alpa">A:0</span>
                        <span class="count-chip bg-teal-100 text-teal-700"       id="chip-bdr">BDR:0</span>
                        <span class="count-chip bg-slate-100 text-slate-600"     id="chip-tanpa">T:0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pemberitahuan kelas sedang BDR -->
        <div id="bdr-banner" class="hidden mb-4 flex items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-teal-800">
            <i class="bi bi-house-door-fill text-lg leading-none mt-0.5"></i>
            <div class="text-sm">
                <p class="font-bold mb-0.5">Kelas ini sedang Belajar dari Rumah (BDR)</p>
                <p id="bdr-banner-ket" class="text-xs text-teal-700"></p>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading-indicator" class="hidden text-center py-12">
            <div class="inline-flex flex-col items-center gap-3">
                <div class="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-400">Memuat data siswa…</p>
            </div>
        </div>

        <!-- Empty state -->
        <div id="empty-state" class="hidden bg-white rounded-3xl border border-slate-200/80 shadow-xs py-16 text-center">
            <i class="bi bi-people text-5xl text-slate-300"></i>
            <p class="mt-3 text-slate-400 font-medium">Pilih kelas lalu klik <strong>Tampilkan Siswa</strong></p>
        </div>

        <!-- Daftar Siswa -->
        <div id="student-section" class="hidden bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-people-fill text-sm"></i>
                    </span>
                    <h2 class="text-sm font-extrabold text-slate-900 truncate" id="list-title">Daftar Siswa</h2>
                </div>
                <span class="text-xs text-slate-400 font-medium" id="list-subtitle"></span>
                <div class="relative ml-auto no-print" style="min-width:200px;">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="student-search" placeholder="Cari nama / NIS..." autocomplete="off"
                           class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <button type="button" id="student-clear"
                            class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                            title="Bersihkan pencarian">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Kolom header desktop -->
            <div class="hidden lg:grid px-5 py-2 bg-slate-50 border-b border-slate-100
                        text-[10px] font-bold uppercase tracking-widest text-slate-400"
                 style="grid-template-columns:2rem 1fr 9rem 18rem 14rem;">
                <span class="text-center">#</span>
                <span>Nama Siswa</span>
                <span class="text-center">Jam Masuk / Keluar</span>
                <span class="text-center">Status Kehadiran</span>
                <span class="text-center">Keterangan</span>
            </div>

            <div id="student-list" class="divide-y divide-slate-50"></div>
        </div>

    </div><!-- end #tab-absen -->


    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB 2: REKAP BULANAN                                     -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div id="tab-rekap" class="hidden space-y-4">

        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden no-print">
            <div class="flex items-center gap-2 p-5 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-file-earmark-bar-graph text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Rekap Bulanan</h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label-tw">Kelas <span class="text-red-500">*</span></label>
                        <select id="rekap_kelas" class="form-select-tw">
                            <option value="">— Pilih Kelas —</option>
                            <?php foreach ($kelass as $id => $nama): ?>
                            <option value="<?= $id ?>"><?= esc($nama) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-tw">Bulan</label>
                        <select id="rekap_bulan" class="form-select-tw">
                            <?php
                            $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                                         'Juli','Agustus','September','Oktober','November','Desember'];
                            for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= $namaBulan[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-tw">Tahun</label>
                        <select id="rekap_tahun" class="form-select-tw">
                            <?php for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-4 flex-wrap">
                    <button type="button" id="btn-rekap" class="btn-tw btn-primary">
                        <i class="bi bi-search mr-1"></i> Tampilkan Ringkasan
                    </button>
                    <a href="<?= site_url('admin/presensi/siswa/rekap') ?>" class="btn-tw btn-outline-primary no-underline">
                        <i class="bi bi-file-earmark-spreadsheet mr-1"></i> Matriks Rekap Bulanan Lengkap
                    </a>
                </div>
            </div>
        </div>

        <div id="rekap-loading" class="hidden text-center py-10">
            <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p class="text-sm text-slate-400 mt-3">Menghitung rekap…</p>
        </div>

        <div id="rekap-section" class="hidden bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 p-5 border-b border-slate-100">
                <h2 class="text-sm font-extrabold text-slate-900" id="rekap-title">Rekap Kehadiran</h2>
                <button type="button" id="btn-print-rekap" class="btn-tw btn-tw-sm btn-light ml-auto no-print">
                    <i class="bi bi-printer mr-1"></i> Cetak
                </button>
            </div>
            <div class="table-responsive-tw">
                <table class="table-tw" id="rekap-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:40px">#</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">NIS</th>
                            <th class="text-center bg-emerald-50 text-emerald-700">H</th>
                            <th class="text-center bg-yellow-50 text-yellow-700">S</th>
                            <th class="text-center bg-sky-50 text-sky-700">I</th>
                            <th class="text-center bg-red-50 text-red-700">A</th>
                            <th class="text-center bg-teal-50 text-teal-700">BDR</th>
                            <th class="text-center bg-slate-50">T</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">% Hadir</th>
                        </tr>
                    </thead>
                    <tbody id="rekap-tbody"></tbody>
                </table>
            </div>
        </div>

    </div><!-- end #tab-rekap -->

</div><!-- end page wrapper -->
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
// ─── Config ───────────────────────────────────────────────────────────────────
// CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
function csrf() { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }
function spinTW() {
    return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>';
}
// Parse YYYY-MM-DD sebagai tanggal lokal (hindari geser hari ala `new Date(str)`)
function parseYmdLocal(str) {
    const parts = String(str || '').split('-').map(Number);
    if (parts.length !== 3 || parts.some(isNaN)) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
}
function tglFmtId(str) {
    const d = parseYmdLocal(str);
    if (!d) return str;
    return d.toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });
}

const STATUS_LABELS = {
    HADIR:'Hadir', SAKIT:'Sakit', IZIN:'Izin', ALPA:'Alpa', BDR:'BDR', TANPA_KETERANGAN:'-/-',
};

// Jam default "Semua Hadir" mengikuti /settings (absensi_setting),
// dengan override jadwal per hari bila ada (1=Senin … 7=Minggu).
const JAM_SETTING = {
    masuk:  <?= json_encode($jam_masuk_default ?? '07:15') ?>,
    pulang: <?= json_encode($jam_pulang_default ?? '14:00') ?>,
};
const JADWAL_MAP = <?= json_encode($jadwal_kerja_map ?? [], JSON_FORCE_OBJECT) ?>;
function jamValid(v) { return /^([01]\d|2[0-3]):[0-5]\d$/.test(String(v || '')); }
// Default jam untuk tanggal terpilih (jadwal hari itu → /settings).
function defaultJam(tipe) {
    const tgl = document.getElementById('tgl').value;
    let fb = tipe === 'pulang' ? JAM_SETTING.pulang : JAM_SETTING.masuk;
    if (!jamValid(fb)) fb = tipe === 'pulang' ? '14:00' : '07:15';
    if (!/^\d{4}-\d{2}-\d{2}$/.test(tgl || '')) return fb;
    const iso = ((parseYmdLocal(tgl).getDay() + 6) % 7) + 1; // Senin=1 … Minggu=7
    const row = JADWAL_MAP[iso] || JADWAL_MAP[String(iso)];
    const v = row ? row[tipe === 'pulang' ? 'pulang' : 'masuk'] : '';
    return jamValid(v) ? v : fb;
}
// Segarkan kedua input default mengikuti tanggal terpilih.
function refreshDefaultJam() {
    const m = document.getElementById('jam_masuk_default');
    const p = document.getElementById('jam_pulang_default');
    if (m) m.value = defaultJam('masuk');
    if (p) p.value = defaultJam('pulang');
}
const AVATAR_COLORS = [
    'bg-indigo-500','bg-violet-500','bg-sky-500','bg-teal-500',
    'bg-emerald-500','bg-amber-500','bg-rose-500','bg-pink-500',
];

// Escape HTML untuk data dari server (nama/NIS bisa mengandung karakter khusus)
function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
}

// Normalisasi URL foto: dukung URL absolut, path uploads, atau nama file saja.
// Kolom foto kadang berisi NIS mentah (mis. "20240367") tanpa ekstensi & tanpa file —
// nilai seperti itu harus dianggap kosong agar browser tidak request 404.
function fotoUrl(f, nis) {
    if (!f) return null;
    f = String(f).trim();
    if (!f || f.toLowerCase() === 'siswa.png' || f.toLowerCase() === 'default.png' || f.toLowerCase() === 'null') {
        return null;
    }
    if (/^https?:\/\//i.test(f)) return f;
    if (f.startsWith('/')) return base_url + f.replace(/^\//, '');
    if (f.startsWith('uploads/')) return base_url + f;
    if (!f.includes('.')) return null;
    return base_url + 'uploads/foto_siswa/' + f;
}

function initialsOf(nama) {
    const parts = String(nama || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
}

// ─── Tab switch ───────────────────────────────────────────────────────────────
function switchTab(tab) {
    ['absen','rekap'].forEach(t => {
        const panel = document.getElementById('tab-' + t);
        const btn   = document.getElementById('tab-' + t + '-btn');
        if (t === tab) {
            panel.classList.remove('hidden');
            btn.classList.add('bg-indigo-600','text-white','shadow-sm');
            btn.classList.remove('text-slate-500');
        } else {
            panel.classList.add('hidden');
            btn.classList.remove('bg-indigo-600','text-white','shadow-sm');
            btn.classList.add('text-slate-500');
        }
    });
}

// ─── Pencarian client-side ────────────────────────────────────────────────────
function applyStudentSearch() {
    const kw = ($('#student-search').val() || '').toLowerCase().trim();
    $('#student-clear').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
    let shown = 0;
    $('#student-list .student-row').each(function() {
        const visible = !kw || String($(this).data('search') || '').includes(kw);
        $(this).toggle(visible);
        if (visible) shown++;
    });
    const total = $('#student-list .student-row').length;
    document.getElementById('list-subtitle').dataset.count = shown + '/' + total;
}

// ─── Load daftar siswa ────────────────────────────────────────────────────────
function loadSiswa() {
    const idKelas = document.getElementById('id_kelas').value;
    const tgl     = document.getElementById('tgl').value;

    if (!idKelas) { Swal.fire('Perhatian','Pilih kelas terlebih dahulu','warning'); return; }
    if (!tgl)     { Swal.fire('Perhatian','Pilih tanggal terlebih dahulu','warning'); return; }

    refreshDefaultJam();
    loadBdrInfo(idKelas, tgl);

    $('#loading-indicator').show();
    $('#empty-state, #student-section').addClass('hidden');
    $('#btn-save, #btn-all-hadir, #btn-all-bdr, #count-chips').addClass('hidden');

    const payload = csrf();
    payload.id_kelas = idKelas;
    $.post(base_url + 'presensisiswa/getSiswa', payload, function (res) {
        $('#loading-indicator').hide();

        if (!res || !res.status || !res.data.length) {
            Swal.fire('Info', (res && res.message) || 'Tidak ada siswa di kelas ini', 'info');
            $('#empty-state').removeClass('hidden');
            return;
        }

        renderStudents(res.data);
        loadExistingPresensi(idKelas, tgl);

        $('#student-section').removeClass('hidden');
        $('#btn-save, #btn-all-hadir, #btn-all-bdr, #count-chips').removeClass('hidden');

        const namaKelas = $('#id_kelas option:selected').text();
        document.getElementById('list-title').textContent   = 'Daftar Siswa — ' + namaKelas;
        document.getElementById('list-subtitle').textContent = tglFmtId(tgl);

    }, 'json').fail(() => {
        $('#loading-indicator').hide();
        Swal.fire('Error','Gagal memuat data siswa','error');
    });
}

// ─── Info BDR kelas ini ──────────────────────────────────────────────────────
// Jadwal BDR dibuat admin; kalau kelas ini termasuk, tampilkan spanduk dan
// tombol "Semua BDR" supaya guru tak perlu menandai satu per satu.
function loadBdrInfo(idKelas, tgl) {
    $('#bdr-banner, #btn-all-bdr').addClass('hidden');

    const payload = csrf();
    payload.tgl = tgl;
    $.post(base_url + 'presensisiswa/getBdr', payload, function (res) {
        const judul = res && res.status && res.data ? res.data[String(idKelas)] : null;
        if (!judul) return;

        document.getElementById('bdr-banner-ket').textContent =
            judul + ' — siswa belajar dari rumah, tidak perlu scan di sekolah.';
        $('#bdr-banner').removeClass('hidden');
        $('#btn-all-bdr').removeClass('hidden');
    }, 'json');
}

// ─── Render daftar siswa ──────────────────────────────────────────────────────
function renderStudents(students) {
    const container = document.getElementById('student-list');
    let html = '';

    students.forEach((s, idx) => {
        const colorClass = AVATAR_COLORS[idx % AVATAR_COLORS.length];
        const initials   = initialsOf(s.nama);
        const photoUrl   = s.foto_url || fotoUrl(s.foto, s.nis);
        const photo      = photoUrl
            ? `<img src="${escHtml(photoUrl)}" alt="" class="w-9 h-9 rounded-full object-cover" loading="lazy" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\\'avatar-initials ${colorClass}\\'>${escHtml(initials)}</div>'">`
            : `<div class="avatar-initials ${colorClass}">${escHtml(initials)}</div>`;

        const statusBtns = Object.entries(STATUS_LABELS).map(([val, lbl]) => `
            <button type="button" class="btn-status" data-status="${val}" data-siswa="${s.id_siswa}">${lbl}</button>`).join('');

        const searchKw = ((s.nama || '') + ' ' + (s.nis || '')).toLowerCase();

        html += `
        <div class="student-row px-4 py-3 grid items-start gap-3"
             style="grid-template-columns:2rem 1fr"
             id="row-${s.id_siswa}" data-siswa="${s.id_siswa}" data-status="TANPA_KETERANGAN" data-search="${escHtml(searchKw)}">

            <span class="text-center text-xs text-slate-400 font-mono pt-2">${idx+1}</span>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_9rem_18rem_14rem] items-center gap-2">

                <!-- Info siswa -->
                <div class="flex items-center gap-2.5 min-w-0">
                    ${photo}
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate" title="${escHtml(s.nama || '')}">${escHtml(s.nama || '-')}</p>
                        <p class="text-xs text-slate-400">NIS: ${escHtml(s.nis || '—')}</p>
                    </div>
                </div>

                <!-- Jam masuk / keluar -->
                <div class="flex flex-col gap-1.5" id="jam-col-${s.id_siswa}">
                    <div class="jam-group">
                        <span class="lbl">M</span>
                        <input type="time" id="jam_masuk_${s.id_siswa}" data-jam-siswa="${s.id_siswa}" data-jam-tipe="masuk"
                               class="form-control-tw text-xs py-1" style="width:100px"
                               title="Jam Masuk">
                    </div>
                    <div class="jam-group">
                        <span class="lbl">K</span>
                        <input type="time" id="jam_keluar_${s.id_siswa}" data-jam-siswa="${s.id_siswa}" data-jam-tipe="keluar"
                               class="form-control-tw text-xs py-1" style="width:100px"
                               title="Jam Keluar">
                    </div>
                </div>

                <!-- Status -->
                <div class="flex gap-1" id="status-btns-${s.id_siswa}">${statusBtns}</div>

                <!-- Keterangan -->
                <input type="text" id="ket-${s.id_siswa}"
                       class="form-control-tw text-xs py-1.5" maxlength="500"
                       placeholder="Keterangan (opsional)">
            </div>
        </div>`;
    });

    container.innerHTML = html;
    $('#student-search').val('');
    applyStudentSearch();
    updateCountChips();
}

// ─── Set status satu siswa ────────────────────────────────────────────────────
function setStatus(idSiswa, status, clickedBtn) {
    document.querySelectorAll(`#status-btns-${idSiswa} .btn-status`).forEach(btn => {
        Object.keys(STATUS_LABELS).forEach(s => btn.classList.remove('selected-' + s));
    });
    clickedBtn.classList.add('selected-' + status);
    document.getElementById('row-' + idSiswa).dataset.status = status;

    // Otomatis isi jam masuk + pulang default (/settings) jika HADIR dan belum ada.
    // Berlaku untuk klik satuan maupun tombol "Semua Hadir".
    if (status === 'HADIR') {
        const jamMasukEl = document.getElementById('jam_masuk_' + idSiswa);
        if (jamMasukEl && !jamMasukEl.value) {
            jamMasukEl.value = defaultJam('masuk');
        }
        const jamKeluarEl = document.getElementById('jam_keluar_' + idSiswa);
        if (jamKeluarEl && !jamKeluarEl.value) {
            const jamM = jamMasukEl ? jamMasukEl.value : '';
            let jamK = defaultJam('pulang');
            if (jamM && jamK < jamM) jamK = jamM; // pulang tak boleh lebih awal dari masuk
            jamKeluarEl.value = jamK;
        }
    }

    updateCountChips();
}

// ─── Mark all ─────────────────────────────────────────────────────────────────
// Konfirmasi bila sebagian siswa sudah punya status non-default (anti-timpa izin/sakit)
function markAllStatus(status) {
    const rows = document.querySelectorAll('[id^="row-"]');
    let filled = 0;
    rows.forEach(row => {
        if ((row.dataset.status || 'TANPA_KETERANGAN') !== 'TANPA_KETERANGAN') filled++;
    });
    const apply = () => rows.forEach(row => {
        const idSiswa = row.dataset.siswa;
        const btn = document.querySelector(`#status-btns-${CSS.escape(String(idSiswa))} [data-status="${status}"]`);
        if (btn) setStatus(idSiswa, status, btn);
    });
    if (filled > 0) {
        Swal.fire({
            title: 'Tandai Semua?',
            html: `<strong>${filled} siswa</strong> sudah punya status (termasuk izin/sakit) dan akan ikut tertimpa.`,
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#2563eb', confirmButtonText: 'Ya, Timpa Semua', cancelButtonText: 'Batal'
        }).then(r => { if (r.isConfirmed) apply(); });
        return;
    }
    apply();
}

// ─── Counter chips ────────────────────────────────────────────────────────────
function updateCountChips() {
    const counts = {HADIR:0, SAKIT:0, IZIN:0, ALPA:0, BDR:0, TANPA_KETERANGAN:0};
    document.querySelectorAll('[id^="row-"]').forEach(row => {
        const s = row.dataset.status || 'TANPA_KETERANGAN';
        if (counts[s] !== undefined) counts[s]++;
    });
    document.getElementById('chip-hadir').textContent = 'H:' + counts.HADIR;
    document.getElementById('chip-sakit').textContent = 'S:' + counts.SAKIT;
    document.getElementById('chip-izin').textContent  = 'I:' + counts.IZIN;
    document.getElementById('chip-alpa').textContent  = 'A:' + counts.ALPA;
    document.getElementById('chip-bdr').textContent   = 'BDR:' + counts.BDR;
    document.getElementById('chip-tanpa').textContent = 'T:' + counts.TANPA_KETERANGAN;
}

// ─── Pre-fill dari data yang sudah ada ───────────────────────────────────────
function loadExistingPresensi(idKelas, tgl) {
    const payload = csrf();
    payload.id_kelas = idKelas;
    payload.tgl = tgl;
    $.post(base_url + 'presensisiswa/getPresensi', payload, function (res) {
        if (!res.status || !res.data.length) return;

        res.data.forEach(p => {
            // Status
            const btn = document.querySelector(`#status-btns-${p.id_siswa} [data-status="${p.status}"]`);
            if (btn) setStatus(p.id_siswa, p.status, btn);

            // Jam masuk
            if (p.jam_masuk) {
                const el = document.getElementById('jam_masuk_' + p.id_siswa);
                if (el) el.value = p.jam_masuk.substring(0,5);
            }
            // Jam keluar
            if (p.jam_keluar) {
                const el = document.getElementById('jam_keluar_' + p.id_siswa);
                if (el) el.value = p.jam_keluar.substring(0,5);
            }
            // Keterangan
            if (p.keterangan) {
                const el = document.getElementById('ket-' + p.id_siswa);
                if (el) el.value = p.keterangan;
            }
        });
    }, 'json');
}

// ─── Update jam satu siswa secara langsung (delegated, debounced) ─────────────
const _jamTimers = {};
function updateJamSatu(idSiswa, tipe, nilai) {
    const idKelas = document.getElementById('id_kelas').value;
    const tgl     = document.getElementById('tgl').value;
    if (!idKelas || !tgl || !nilai) return;
    if (!/^\d{2}:\d{2}(:\d{2})?$/.test(nilai)) return;

    clearTimeout(_jamTimers[idSiswa + '-' + tipe]);
    _jamTimers[idSiswa + '-' + tipe] = setTimeout(() => {
        const payload = csrf();
        payload.id_siswa = idSiswa;
        payload.id_kelas = idKelas;
        payload.tgl = tgl;
        if (tipe === 'masuk')  payload.jam_masuk  = nilai;
        if (tipe === 'keluar') payload.jam_keluar = nilai;

        $.post(base_url + 'presensisiswa/updateJam', payload, function (res) {
            if (res && res.promoted) {
                // Baris TANPA_KETERANGAN yang diisi jam → otomatis HADIR
                const btn = document.querySelector(`#status-btns-${CSS.escape(String(idSiswa))} [data-status="HADIR"]`);
                if (btn) setStatus(idSiswa, 'HADIR', btn);
            }
            if (res && !res.status && res.message) {
                // Belum ada baris presensi → buat via check-in (khusus jam masuk)
                if (tipe === 'masuk' && /tidak ditemukan/i.test(res.message)) {
                    const ci = csrf();
                    ci.id_siswa = idSiswa;
                    ci.id_kelas = idKelas;
                    ci.tgl = tgl;
                    ci.jam_masuk = nilai;
                    $.post(base_url + 'presensisiswa/checkIn', ci, function (r2) {
                        if (r2 && r2.status) {
                            const btn = document.querySelector(`#status-btns-${CSS.escape(String(idSiswa))} [data-status="HADIR"]`);
                            if (btn) setStatus(idSiswa, 'HADIR', btn);
                        } else if (r2 && r2.message) {
                            Swal.fire('Gagal', r2.message, 'error');
                        }
                    }, 'json');
                    return;
                }
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json');
    }, 400);
}

// ─── Simpan absensi batch ─────────────────────────────────────────────────────
function savePresensi() {
    const idKelas  = document.getElementById('id_kelas').value;
    const tgl      = document.getElementById('tgl').value;
    const jamMasuk = document.getElementById('jam_masuk_default').value;

    if (!idKelas || !tgl) {
        Swal.fire('Perhatian','Kelas dan tanggal wajib diisi','warning'); return;
    }

    const rows = document.querySelectorAll('[id^="row-"]');
    if (!rows.length) {
        Swal.fire('Perhatian','Belum ada data siswa untuk disimpan','warning'); return;
    }

    const presensi = [];
    rows.forEach(row => {
        const idSiswa    = row.dataset.siswa;
        const status     = row.dataset.status || 'TANPA_KETERANGAN';
        const keterangan = (document.getElementById('ket-' + idSiswa)?.value || '').trim();
        // Ambil jam per-siswa jika sudah diisi, fallback ke default
        const jamM = document.getElementById('jam_masuk_'  + idSiswa)?.value || '';
        const jamK = document.getElementById('jam_keluar_' + idSiswa)?.value || '';
        presensi.push({ id_siswa: idSiswa, status, keterangan, jam_masuk: jamM, jam_keluar: jamK });
    });

    Swal.fire({
        title: 'Simpan Absensi?',
        html:  `Menyimpan absensi <strong>${presensi.length} siswa</strong> pada <strong>${tgl}</strong>`,
        icon:  'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor:  '#94a3b8',
        confirmButtonText:  '<i class="bi bi-check2-circle mr-1"></i> Simpan',
        cancelButtonText:   'Batal',
    }).then(result => {
        if (!result.isConfirmed) return;

        const btn = document.getElementById('btn-save');
        btn.disabled = true;
        btn.innerHTML = spinTW() + ' Menyimpan…';

        const payload = csrf();
        payload.id_kelas = idKelas;
        payload.tgl = tgl;
        payload.jam_masuk = jamMasuk;
        payload.presensi = presensi;
        $.post(base_url + 'presensisiswa/save', payload, function (res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle mr-1"></i> Simpan Absensi';

            if (res.status) {
                Swal.fire({ icon:'success', title:'Berhasil!', text:res.message, timer:2000, showConfirmButton:false });
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json').fail(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle mr-1"></i> Simpan Absensi';
            Swal.fire('Error','Gagal menghubungi server','error');
        });
    });
}

// ─── Rekap Bulanan ────────────────────────────────────────────────────────────
function loadRekap() {
    const idKelas = document.getElementById('rekap_kelas').value;
    const bulan   = document.getElementById('rekap_bulan').value;
    const tahun   = document.getElementById('rekap_tahun').value;

    if (!idKelas) { Swal.fire('Perhatian','Pilih kelas terlebih dahulu','warning'); return; }

    $('#rekap-loading').show();
    $('#rekap-section').addClass('hidden');

    const payload = csrf();
    payload.id_kelas = idKelas;
    payload.bulan = bulan;
    payload.tahun = tahun;
    $.post(base_url + 'presensisiswa/rekapBulanan', payload, function (res) {
        $('#rekap-loading').hide();

        if (!res.status || !res.data.length) {
            Swal.fire('Info','Belum ada data absensi untuk periode ini','info'); return;
        }

        renderRekap(res.data, bulan, tahun, idKelas);
        $('#rekap-section').removeClass('hidden');
    }, 'json').fail(() => {
        $('#rekap-loading').hide();
        Swal.fire('Error','Gagal memuat rekap','error');
    });
}

function renderRekap(data, bulan, tahun, idKelas) {
    const namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
    const namaKelas = document.querySelector(`#rekap_kelas option[value="${idKelas}"]`)?.textContent || '';

    document.getElementById('rekap-title').textContent =
        `Rekap Kehadiran — ${namaKelas} — ${namaBulan[parseInt(bulan)]} ${tahun}`;

    const tbody = document.getElementById('rekap-tbody');
    let html = '';
    data.forEach((s, idx) => {
        const total  = parseInt(s.total)  || 0;
        const hadir  = parseInt(s.hadir)  || 0;
        const bdr    = parseInt(s.bdr)    || 0;
        // BDR (belajar dari rumah) tidak menurunkan persentase kehadiran:
        // hari BDR dikeluarkan dari pembagi, sama seperti hari libur.
        const efektif = total - bdr;
        const persen = efektif > 0 ? Math.round((hadir / efektif) * 100) : 0;
        const barCls = persen >= 75 ? 'bg-emerald-500' : persen >= 50 ? 'bg-amber-400' : 'bg-red-500';
        const txtCls = persen >= 75 ? 'text-emerald-600' : persen >= 50 ? 'text-amber-500' : 'text-red-500';
        html += `
        <tr>
            <td class="text-center text-slate-400 text-xs font-mono">${idx+1}</td>
            <td class="font-semibold text-slate-800" title="${escHtml(s.nama || '')}">${escHtml(s.nama || '-')}</td>
            <td class="text-center text-xs text-slate-400">${escHtml(s.nis || '—')}</td>
            <td class="rekap-cell text-center text-emerald-600">${escHtml(s.hadir)}</td>
            <td class="rekap-cell text-center text-yellow-600">${escHtml(s.sakit)}</td>
            <td class="rekap-cell text-center text-sky-600">${escHtml(s.izin)}</td>
            <td class="rekap-cell text-center text-red-600">${escHtml(s.alpa)}</td>
            <td class="rekap-cell text-center text-teal-600">${escHtml(s.bdr ?? bdr)}</td>
            <td class="rekap-cell text-center text-slate-400">${escHtml(s.tanpa_keterangan)}</td>
            <td class="rekap-cell text-center">${total}</td>
            <td style="min-width:100px" title="Dihitung dari ${hadir}/${efektif} hari (hari BDR dikecualikan)">
                <div class="flex items-center gap-1.5">
                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full ${barCls} rounded-full" style="width:${persen}%"></div>
                    </div>
                    <span class="text-xs font-bold ${txtCls}">${persen}%</span>
                </div>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// ─── Init (delegated, pola permits/users) ───────────────────────────────────────
    $('#ps-tabs').on('click', '[data-tab]', function() {
        switchTab($(this).data('tab'));
    });
    $('#btn-load').on('click', loadSiswa);
    $('#btn-save').on('click', savePresensi);
    $('#btn-all-hadir').on('click', function() { markAllStatus('HADIR'); });
    $('#btn-all-bdr').on('click', function() { markAllStatus('BDR'); });
    $('#btn-rekap').on('click', loadRekap);
    $('#btn-print-rekap').on('click', function() { window.print(); });

    // Delegasi baris siswa (status + jam) — ganti onclick inline
    $('#student-list').on('click', '.btn-status', function() {
        setStatus($(this).data('siswa'), $(this).data('status'), this);
    });
    $('#student-list').on('change', '[data-jam-siswa]', function() {
        updateJamSatu($(this).data('jam-siswa'), $(this).data('jam-tipe'), this.value);
    });

    // Pencarian siswa
    $('#student-search').on('input', applyStudentSearch);
    $('#student-clear').on('click', function() {
        $('#student-search').val('');
        applyStudentSearch();
        $('#student-search').focus();
    });

    $('#empty-state').removeClass('hidden');

    // Ingat kelas terakhir
    try {
        const lastKelas = localStorage.getItem('presensi_siswa_kelas');
        if (lastKelas && document.querySelector(`#id_kelas option[value="${CSS.escape(lastKelas)}"]`)) {
            document.getElementById('id_kelas').value = lastKelas;
        }
        const lastRekap = localStorage.getItem('presensi_siswa_rekap_kelas');
        if (lastRekap && document.querySelector(`#rekap_kelas option[value="${CSS.escape(lastRekap)}"]`)) {
            document.getElementById('rekap_kelas').value = lastRekap;
        }
    } catch (e) {}
    $('#id_kelas').on('change', function () {
        try { localStorage.setItem('presensi_siswa_kelas', this.value); } catch (e) {}
    });
    $('#rekap_kelas').on('change', function () {
        try { localStorage.setItem('presensi_siswa_rekap_kelas', this.value); } catch (e) {}
    });

    // Jika tanggal berubah dan siswa sudah tampil → reload presensi
    $('#tgl').on('change', function () {
        if (this.value > new Date().toLocaleDateString('en-CA')) {
            this.value = new Date().toLocaleDateString('en-CA');
        }
        refreshDefaultJam();
        const idKelas = $('#id_kelas').val();
        if (idKelas && $('#student-section').is(':visible')) {
            loadExistingPresensi(idKelas, this.value);
        }
    });

    // Kompatibilitas: fungsi lama tetap tersedia global
    window.switchTab = switchTab;
    window.loadSiswa = loadSiswa;
    window.savePresensi = savePresensi;
    window.loadRekap = loadRekap;
    window.setStatus = setStatus;
    window.markAllStatus = markAllStatus;
    window.updateJamSatu = updateJamSatu;
});
</script>
<?= $this->endSection() ?>
