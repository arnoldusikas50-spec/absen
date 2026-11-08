<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Top App Bar */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Date Nav Chip */
    .date-nav-chip {
        user-select: none;
        transition: transform 0.1s ease;
    }
    .date-nav-chip:active {
        transform: scale(0.99);
    }

    /* Card active state */
    .incident-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease;
    }
    .incident-card:active {
        transform: scale(0.985);
    }

    /* Pill filter active styles */
    .filter-pill.active {
        background-color: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
    $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
    $indoDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $dayName = $indoDays[(int)date('N', strtotime($tanggal))] ?? '';

    // Calculate metrics
    $totalKejadian = count($data_kejadian ?? []);
    $countPelanggaran = 0;
    $countPositif = 0;
    $countLainnya = 0;

    foreach (($data_kejadian ?? []) as $k) {
        $jLower = strtolower($k->jenis ?? '');
        if (strpos($jLower, 'pelanggaran') !== false) {
            $countPelanggaran++;
        } elseif (strpos($jLower, 'positif') !== false || strpos($jLower, 'prestasi') !== false) {
            $countPositif++;
        } else {
            $countLainnya++;
        }
    }
?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('guru/piket') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Catatan Kejadian</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Buku Catatan Insiden Piket</p>
        </div>
    </div>
    <div class="flex items-center gap-1.5">
        <button type="button" onclick="twModalShow('#modalKejadian')"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-colors">
            <i class="bi bi-plus-lg text-xs"></i> Catat
        </button>
    </div>
</div>

<!-- ── Main Content Container ───────────────────────────── -->
<div class="px-4 pb-12 space-y-3.5 mt-2.5">

    <!-- ── Date Navigation Chip ─────────────────────────── -->
    <div class="date-nav-chip flex items-center justify-between bg-white border border-slate-200/80 rounded-2xl px-3 py-2 shadow-xs">
        <a href="?tanggal=<?= $prevDate ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Sebelumnya">
            <i class="bi bi-chevron-left text-xs font-bold"></i>
        </a>

        <div class="flex items-center gap-2.5 cursor-pointer relative px-2 py-1 rounded-xl hover:bg-slate-50 transition-colors"
             onclick="document.getElementById('headerDatePicker').showPicker ? document.getElementById('headerDatePicker').showPicker() : document.getElementById('headerDatePicker').focus()">
            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100/80">
                <i class="bi bi-calendar3 text-sm"></i>
            </div>
            <div class="text-center">
                <div class="font-bold text-xs text-slate-800 leading-tight">
                    <?= date('d M Y', strtotime($tanggal)) ?>
                </div>
                <div class="text-[10px] text-slate-500 font-semibold leading-tight">
                    <?= $dayName ?>
                </div>
            </div>
            <i class="bi bi-chevron-down text-[10px] text-slate-400"></i>
            <form method="get" id="dateForm" class="m-0 p-0">
                <input type="date" name="tanggal" value="<?= esc($tanggal) ?>" id="headerDatePicker"
                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                       onchange="document.getElementById('dateForm').submit()">
            </form>
        </div>

        <a href="?tanggal=<?= $nextDate ?>"
           class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/70 transition-colors"
           title="Hari Berikutnya">
            <i class="bi bi-chevron-right text-xs font-bold"></i>
        </a>
    </div>

    <!-- ── Summary Metric Cards ─────────────────────────── -->
    <div class="grid grid-cols-4 gap-2">
        <div class="bg-blue-50/70 border border-blue-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span class="text-xl font-extrabold text-blue-700 leading-none"><?= $totalKejadian ?></span>
            <span class="text-[9px] font-bold text-blue-600 uppercase tracking-tight mt-1.5">Total</span>
        </div>
        <div class="bg-rose-50/70 border border-rose-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span class="text-xl font-extrabold text-rose-700 leading-none"><?= $countPelanggaran ?></span>
            <span class="text-[9px] font-bold text-rose-600 uppercase tracking-tight mt-1.5">Pelanggaran</span>
        </div>
        <div class="bg-emerald-50/70 border border-emerald-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span class="text-xl font-extrabold text-emerald-700 leading-none"><?= $countPositif ?></span>
            <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-tight mt-1.5">Positif</span>
        </div>
        <div class="bg-slate-100/80 border border-slate-200/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span class="text-xl font-extrabold text-slate-700 leading-none"><?= $countLainnya ?></span>
            <span class="text-[9px] font-bold text-slate-600 uppercase tracking-tight mt-1.5">Lainnya</span>
        </div>
    </div>

    <!-- ── Filter & Search Toolbar ──────────────────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs space-y-2.5">
        <!-- Status Filter Pills -->
        <div class="flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            <button type="button" onclick="setFilterStatus('all', this)"
                    class="filter-pill active px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-extrabold whitespace-nowrap transition-all">
                Semua
            </button>
            <button type="button" onclick="setFilterStatus('belum', this)"
                    class="filter-pill px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-extrabold whitespace-nowrap transition-all">
                Belum Selesai
            </button>
            <button type="button" onclick="setFilterStatus('proses', this)"
                    class="filter-pill px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-extrabold whitespace-nowrap transition-all">
                Dalam Proses
            </button>
            <button type="button" onclick="setFilterStatus('selesai', this)"
                    class="filter-pill px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-extrabold whitespace-nowrap transition-all">
                Selesai
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="searchKejadianInput" placeholder="Cari siswa, keterangan, pelapor..."
                   class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500 transition-colors">
        </div>
    </div>

    <!-- ── Incident List Container ──────────────────────── -->
    <div id="incidentList" class="space-y-3">
        <?php if (empty($data_kejadian)): ?>
            <div class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 m-0">Kondisi Aman</h3>
                <p class="text-xs text-slate-500 mt-1 m-0">Tidak ada kejadian atau insiden yang tercatat hari ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($data_kejadian as $k):
                $jLower = strtolower($k->jenis ?? '');
                if (strpos($jLower, 'pelanggaran') !== false) {
                    $jenisBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                } elseif (strpos($jLower, 'positif') !== false || strpos($jLower, 'prestasi') !== false) {
                    $jenisBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                } else {
                    $jenisBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                }

                $statusVal = strtolower($k->status ?? 'belum');
                if ($statusVal === 'selesai') {
                    $statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    $statusLabel = 'Selesai';
                } elseif ($statusVal === 'proses') {
                    $statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    $statusLabel = 'Proses';
                } else {
                    $statusBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                    $statusLabel = 'Belum';
                }

                $searchContent = strtolower(esc(($k->nama_siswa ?? '') . ' ' . ($k->nama_kelas ?? '') . ' ' . ($k->keterangan ?? '') . ' ' . ($k->pelapor ?? '')));
            ?>
                <div class="incident-card bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-xs space-y-2.5"
                     data-status="<?= $statusVal ?>"
                     data-search="<?= $searchContent ?>">

                    <!-- Header: Time, Name, Badges -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-extrabold text-[10px] border border-slate-200">
                                <i class="bi bi-clock mr-1 text-[9px]"></i> <?= date('H:i', strtotime((string)$k->jam)) ?>
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border <?= $jenisBadge ?> uppercase">
                                <?= esc($k->jenis) ?>
                            </span>
                            <?php if (!empty($k->tingkat)): ?>
                                <span class="text-[9px] font-semibold text-slate-400 capitalize">
                                    • <?= esc($k->tingkat) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Status Action Dropdown / Button -->
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <select onchange="updateKejadianStatus(<?= (int)$k->id_kejadian ?>, this.value)"
                                    class="text-[10px] font-bold px-2 py-1 rounded-lg border <?= $statusBadge ?> bg-white cursor-pointer focus:outline-none">
                                <option value="belum" <?= $statusVal === 'belum' ? 'selected' : '' ?>>Belum</option>
                                <option value="proses" <?= $statusVal === 'proses' ? 'selected' : '' ?>>Proses</option>
                                <option value="selesai" <?= $statusVal === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            </select>

                            <button type="button" onclick="deleteKejadian(<?= (int)$k->id_kejadian ?>)"
                                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors"
                                    title="Hapus Kejadian">
                                <i class="bi bi-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Target Siswa / Kelas -->
                    <div>
                        <div class="font-extrabold text-xs text-slate-900 leading-tight">
                            <?= esc($k->nama_siswa ?? 'Umum / Tidak Ditentukan') ?>
                        </div>
                        <?php if (!empty($k->nama_kelas)): ?>
                            <div class="text-[11px] font-medium text-slate-500 mt-0.5 flex items-center gap-1">
                                <i class="bi bi-mortarboard text-slate-400 text-xs"></i> Kelas <?= esc($k->nama_kelas) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Keterangan -->
                    <div class="text-xs text-slate-700 bg-slate-50 border border-slate-100 rounded-xl p-2.5 leading-relaxed">
                        <?= esc((string)$k->keterangan) ?>
                    </div>

                    <!-- Penanganan & Tindak Lanjut (jika ada) -->
                    <?php if (!empty($k->penanganan) || !empty($k->tindak_lanjut)): ?>
                        <div class="space-y-1 text-[11px] pt-1 border-t border-slate-100">
                            <?php if (!empty($k->penanganan)): ?>
                                <div class="text-slate-600">
                                    <span class="font-bold text-slate-700">Penanganan:</span> <?= esc($k->penanganan) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($k->tindak_lanjut)): ?>
                                <div class="text-slate-600">
                                    <span class="font-bold text-slate-700">Tindak Lanjut:</span> <?= esc($k->tindak_lanjut) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Footer: Pelapor -->
                    <?php if (!empty($k->pelapor)): ?>
                        <div class="text-[10px] text-slate-400 flex items-center gap-1 pt-0.5">
                            <i class="bi bi-person-check"></i> Dilaporkan oleh: <span class="font-medium text-slate-600"><?= esc($k->pelapor) ?></span>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- ════════════════════ MODAL INPUT KEJADIAN (BOTTOM SHEET) ════════════════════ -->
<div class="tw-modal" id="modalKejadian" aria-hidden="true">
    <div class="tw-modal-box">
        <!-- Sheet Handle & Header -->
        <div class="sticky top-0 bg-white z-10 px-5 pt-3 pb-3 border-b border-slate-100">
            <div class="w-10 h-1.5 bg-slate-300 rounded-full mx-auto mb-3"></div>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Catat Kejadian Baru</h2>
                    <p class="text-xs text-slate-500 m-0 mt-0.5 font-medium">Dokumentasi insiden & catatan harian siswa</p>
                </div>
                <button type="button" data-tw-dismiss="modal" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors">
                    <i class="bi bi-x-lg text-xs font-bold"></i>
                </button>
            </div>
        </div>

        <!-- Form Body -->
        <form id="formKejadian" class="p-5 space-y-3.5">
            <?= csrf_field() ?>
            <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">

            <!-- Jam & Tingkat -->
            <div class="grid grid-cols-2 gap-2.5">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jam Kejadian</label>
                    <input type="time" name="jam" value="<?= date('H:i') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tingkat</label>
                    <select name="tingkat" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-blue-500">
                        <option value="ringan">Ringan</option>
                        <option value="sedang">Sedang</option>
                        <option value="berat">Berat</option>
                    </select>
                </div>
            </div>

            <!-- Jenis Kejadian -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori / Jenis</label>
                <select name="jenis" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-blue-500">
                    <option value="Pelanggaran Tata Tertib">Pelanggaran Tata Tertib</option>
                    <option value="Keterlambatan">Keterlambatan Massal / Khusus</option>
                    <option value="Prestasi / Tindakan Positif">Prestasi / Tindakan Positif</option>
                    <option value="Insiden Kesehatan / Sakit">Insiden Kesehatan / Sakit</option>
                    <option value="Kejadian Umum / Lainnya">Kejadian Umum / Lainnya</option>
                </select>
            </div>

            <!-- Siswa Terlapor (Dropdown) -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Siswa Terkait (Opsional)</label>
                <select name="terlapor_siswa_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-blue-500">
                    <option value="">-- Umum / Bukan Siswa Tertentu --</option>
                    <?php if (!empty($siswa_list)): ?>
                        <?php foreach ($siswa_list as $s): ?>
                            <option value="<?= (int)$s->id_siswa ?>">
                                <?= esc($s->nama) ?> (Kelas <?= esc($s->nama_kelas) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Pelapor -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Pelapor</label>
                <input type="text" name="pelapor" placeholder="Guru Piket / Satpam / Siswa..." required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500">
            </div>

            <!-- Keterangan -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keterangan Kronologi</label>
                <textarea name="keterangan" rows="3" required placeholder="Jelaskan detail insiden yang terjadi..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500 resize-none"></textarea>
            </div>

            <!-- Penanganan & Tindak Lanjut -->
            <div class="grid grid-cols-2 gap-2.5">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Penanganan Saat Itu</label>
                    <textarea name="penanganan" rows="2" placeholder="Tindakan awal..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500 resize-none"></textarea>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tindak Lanjut</label>
                    <textarea name="tindak_lanjut" rows="2" placeholder="Rencana lanjutan..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-blue-500 resize-none"></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" id="btnSubmitKejadian"
                        class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center justify-center gap-2 transition-colors">
                    <i class="bi bi-check-circle-fill"></i> Simpan Catatan Kejadian
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const BASE = '<?= base_url() ?>';
    let currentStatusFilter = 'all';

    // ── Filter Status ─────────────────────────────────────
    window.setFilterStatus = function(status, btn) {
        currentStatusFilter = status;
        document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
        if (btn) btn.classList.add('active');
        applyFilter();
    };

    function applyFilter() {
        const q = (document.getElementById('searchKejadianInput')?.value || '').toLowerCase().trim();
        document.querySelectorAll('.incident-card').forEach(card => {
            const st = card.getAttribute('data-status') || '';
            const search = card.getAttribute('data-search') || '';
            const matchStatus = (currentStatusFilter === 'all') || (st === currentStatusFilter);
            const matchQuery = !q || search.includes(q);
            card.style.display = (matchStatus && matchQuery) ? '' : 'none';
        });
    }

    document.getElementById('searchKejadianInput')?.addEventListener('input', applyFilter);

    // ── Submit Kejadian Form ──────────────────────────────
    document.getElementById('formKejadian')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitKejadian');
        btn.disabled = true;
        btn.innerHTML = '<span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Menyimpan...';

        const formData = new FormData(this);

        try {
            const res = await fetch(BASE + 'guru/piket/save_kejadian', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                if (window.Swal) {
                    await Swal.fire({
                        title: 'Tersimpan',
                        text: json.message || 'Kejadian berhasil dicatat',
                        icon: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
                twModalHide('#modalKejadian');
                window.location.reload();
            } else {
                if (window.Swal) {
                    Swal.fire('Gagal', json.message || 'Terjadi kesalahan saat menyimpan', 'error');
                } else {
                    alert(json.message || 'Gagal menyimpan');
                }
            }
        } catch (err) {
            alert('Gagal menghubungkan ke server');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Simpan Catatan Kejadian';
        }
    });

    // ── Update Kejadian Status ────────────────────────────
    window.updateKejadianStatus = async function(id, status) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const res = await fetch(BASE + 'guru/piket/update_kejadian_status', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: json.message || 'Status diperbarui',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                const card = document.querySelector(`.incident-card select[onchange*="${id}"]`)?.closest('.incident-card');
                if (card) {
                    card.setAttribute('data-status', status);
                }
            } else {
                alert(json.message || 'Gagal mengubah status');
            }
        } catch (err) {
            alert('Gagal menghubungkan ke server');
        }
    };

    // ── Delete Kejadian ───────────────────────────────────
    window.deleteKejadian = async function(id) {
        if (window.Swal) {
            const result = await Swal.fire({
                title: 'Hapus Catatan Kejadian?',
                text: 'Data kejadian ini akan dihapus secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
        } else {
            if (!confirm('Hapus kejadian ini?')) return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const res = await fetch(BASE + 'guru/piket/delete_kejadian', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                window.location.reload();
            } else {
                alert('Gagal menghapus data');
            }
        } catch (err) {
            alert('Gagal menghubungkan ke server');
        }
    };

});
</script>
<?= $this->endSection() ?>
