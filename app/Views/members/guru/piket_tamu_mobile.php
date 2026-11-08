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

    /* Guest card press state */
    .guest-card {
        transition: transform 0.12s ease, box-shadow 0.15s ease;
    }
    .guest-card:active {
        transform: scale(0.985);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
    $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
    $indoDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $dayName = $indoDays[(int)date('N', strtotime($tanggal))] ?? '';

    $totalTamu = count($data_tamu ?? []);
    $masihDiLokasi = 0;
    $sudahPulang = 0;

    foreach (($data_tamu ?? []) as $t) {
        if (empty($t->jam_keluar)) {
            $masihDiLokasi++;
        } else {
            $sudahPulang++;
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
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Buku Tamu</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Buku Kunjungan & Tamu Piket</p>
        </div>
    </div>
    <div class="flex items-center gap-1.5">
        <button type="button" onclick="twModalShow('#modalTamu')"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs transition-colors">
            <i class="bi bi-person-plus-fill text-xs"></i> Tamu Baru
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
            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100/80">
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
    <div class="grid grid-cols-3 gap-2">
        <div class="bg-purple-50/70 border border-purple-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span class="text-xl font-extrabold text-purple-700 leading-none"><?= $totalTamu ?></span>
            <span class="text-[9px] font-bold text-purple-600 uppercase tracking-tight mt-1.5">Total Tamu</span>
        </div>
        <div class="bg-amber-50/70 border border-amber-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span id="statDiLokasi" class="text-xl font-extrabold text-amber-700 leading-none"><?= $masihDiLokasi ?></span>
            <span class="text-[9px] font-bold text-amber-600 uppercase tracking-tight mt-1.5">Di Sekolah</span>
        </div>
        <div class="bg-emerald-50/70 border border-emerald-100/80 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center shadow-xs">
            <span id="statPulang" class="text-xl font-extrabold text-emerald-700 leading-none"><?= $sudahPulang ?></span>
            <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-tight mt-1.5">Sudah Keluar</span>
        </div>
    </div>

    <!-- ── Search Toolbar ───────────────────────────────── -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs">
        <div class="relative">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="searchTamuInput" placeholder="Cari nama tamu, instansi, keperluan..."
                   class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500 transition-colors">
        </div>
    </div>

    <!-- ── Guest List Container ─────────────────────────── -->
    <div id="tamuList" class="space-y-3">
        <?php if (empty($data_tamu)): ?>
            <div class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="bi bi-door-closed"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 m-0">Belum Ada Tamu</h3>
                <p class="text-xs text-slate-500 mt-1 m-0">Belum ada kunjungan tamu yang dicatat pada tanggal ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($data_tamu as $t):
                $isKeluar = !empty($t->jam_keluar);
                $searchContent = strtolower(esc(($t->nama ?? '') . ' ' . ($t->instansi ?? '') . ' ' . ($t->bertemu ?? '') . ' ' . ($t->keperluan ?? '')));
            ?>
                <div class="guest-card bg-white border border-slate-200/80 rounded-2xl p-3.5 shadow-xs space-y-2.5"
                     id="tamu-card-<?= (int)$t->id_tamu ?>"
                     data-search="<?= $searchContent ?>">

                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <!-- Avatar Icon -->
                            <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0 text-lg">
                                <i class="bi bi-person-badge"></i>
                            </div>

                            <!-- Name & Instansi -->
                            <div class="flex-1 min-w-0">
                                <div class="font-extrabold text-xs text-slate-900 leading-tight truncate">
                                    <?= esc($t->nama) ?>
                                </div>
                                <div class="text-[11px] font-semibold text-slate-500 mt-0.5 truncate">
                                    <i class="bi bi-building text-[10px] text-slate-400"></i> <?= esc($t->instansi ?? '-') ?>
                                </div>
                                <?php if (!empty($t->bertemu)): ?>
                                    <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                                        <span class="text-slate-400">Bertemu:</span> <span class="font-medium text-slate-700"><?= esc($t->bertemu) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Delete Button -->
                        <button type="button" onclick="deleteTamu(<?= (int)$t->id_tamu ?>)"
                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors flex-shrink-0"
                                title="Hapus Data Tamu">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>

                    <!-- Keperluan Box -->
                    <?php if (!empty($t->keperluan)): ?>
                        <div class="text-xs text-slate-700 bg-slate-50 border border-slate-100 rounded-xl p-2.5 leading-relaxed">
                            <span class="font-bold text-slate-700">Keperluan:</span> <?= esc($t->keperluan) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Time Info & Out Action Button -->
                    <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-2 text-[11px] text-slate-500">
                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk: <?= date('H:i', strtotime((string)$t->jam_masuk)) ?>
                            </span>
                            <span class="text-slate-300">•</span>
                            <span id="out-time-<?= (int)$t->id_tamu ?>" class="inline-flex items-center gap-1 <?= $isKeluar ? 'font-semibold text-slate-600' : 'font-bold text-amber-600' ?>">
                                <?php if ($isKeluar): ?>
                                    <i class="bi bi-box-arrow-right text-rose-500"></i> Keluar: <?= date('H:i', strtotime((string)$t->jam_keluar)) ?>
                                <?php else: ?>
                                    <i class="bi bi-clock-history"></i> Masih di sekolah
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Action Button -->
                        <div id="out-btn-wrap-<?= (int)$t->id_tamu ?>">
                            <?php if (!$isKeluar): ?>
                                <button type="button" onclick="catatTamuKeluar(<?= (int)$t->id_tamu ?>)"
                                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 rounded-lg text-[11px] font-bold flex items-center gap-1 transition-colors">
                                    <i class="bi bi-box-arrow-right"></i> Set Keluar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- ════════════════════ MODAL INPUT TAMU (BOTTOM SHEET) ════════════════════ -->
<div class="tw-modal" id="modalTamu" aria-hidden="true">
    <div class="tw-modal-box">
        <!-- Sheet Handle & Header -->
        <div class="sticky top-0 bg-white z-10 px-5 pt-3 pb-3 border-b border-slate-100">
            <div class="w-10 h-1.5 bg-slate-300 rounded-full mx-auto mb-3"></div>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Pendaftaran Tamu Baru</h2>
                    <p class="text-xs text-slate-500 m-0 mt-0.5 font-medium">Buku tamu & kunjungan sekolah</p>
                </div>
                <button type="button" data-tw-dismiss="modal" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors">
                    <i class="bi bi-x-lg text-xs font-bold"></i>
                </button>
            </div>
        </div>

        <!-- Form Body -->
        <form id="formTamu" class="p-5 space-y-3.5">
            <?= csrf_field() ?>
            <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">

            <!-- Jam Masuk -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jam Masuk</label>
                <input type="time" name="jam_masuk" value="<?= date('H:i') ?>" required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500">
            </div>

            <!-- Nama Tamu -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Lengkap Tamu</label>
                <input type="text" name="nama" placeholder="Contoh: Bapak Ahmad Fauzi" required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500">
            </div>

            <!-- Instansi / Asal -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Instansi / Lembaga / Asal</label>
                <input type="text" name="instansi" placeholder="Contoh: Dinas Pendidikan / Orang Tua Siswa" required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500">
            </div>

            <!-- Bertemu Siapa -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tujuan Bertemu (Pejabat / Guru / Siswa)</label>
                <input type="text" name="bertemu" placeholder="Contoh: Kepala Sekolah / Ibu Siti" required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500">
            </div>

            <!-- Keperluan -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keperluan Kunjungan</label>
                <textarea name="keperluan" rows="3" required placeholder="Jelaskan maksud & keperluan kedatangan..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500 resize-none"></textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" id="btnSubmitTamu"
                        class="w-full py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center justify-center gap-2 transition-colors">
                    <i class="bi bi-check-circle-fill"></i> Simpan Buku Tamu
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

    function haptic(ms = 10) {
        if (window.navigator.vibrate) window.navigator.vibrate(ms);
    }

    // ── Search Tamu ───────────────────────────────────────
    document.getElementById('searchTamuInput')?.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.guest-card').forEach(card => {
            const search = card.getAttribute('data-search') || '';
            card.style.display = (!q || search.includes(q)) ? '' : 'none';
        });
    });

    // ── Submit Tamu Form ──────────────────────────────────
    document.getElementById('formTamu')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitTamu');
        btn.disabled = true;
        btn.innerHTML = '<span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Menyimpan...';

        const formData = new FormData(this);

        try {
            const res = await fetch(BASE + 'guru/piket/save_tamu', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                if (window.Swal) {
                    await Swal.fire({
                        title: 'Tersimpan',
                        text: json.message || 'Data tamu berhasil disimpan',
                        icon: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
                twModalHide('#modalTamu');
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
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Simpan Buku Tamu';
        }
    });

    // ── Catat Jam Keluar ──────────────────────────────────
    window.catatTamuKeluar = async function(id) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const res = await fetch(BASE + 'guru/piket/tamu_keluar', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                haptic(10);
                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: json.message || 'Jam keluar dicatat',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                // Update UI immediately
                const jamKeluar = json.jam_keluar || 'Sekarang';
                const timeEl = document.getElementById('out-time-' + id);
                if (timeEl) {
                    timeEl.className = 'inline-flex items-center gap-1 font-semibold text-slate-600';
                    timeEl.innerHTML = `<i class="bi bi-box-arrow-right text-rose-500"></i> Keluar: ${jamKeluar}`;
                }
                const btnWrap = document.getElementById('out-btn-wrap-' + id);
                if (btnWrap) btnWrap.innerHTML = '';

                // Update counters
                const elDiLokasi = document.getElementById('statDiLokasi');
                const elPulang = document.getElementById('statPulang');
                if (elDiLokasi) elDiLokasi.textContent = Math.max(0, parseInt(elDiLokasi.textContent || '0') - 1);
                if (elPulang) elPulang.textContent = parseInt(elPulang.textContent || '0') + 1;

            } else {
                alert(json.message || 'Gagal mencatat jam keluar');
            }
        } catch (err) {
            alert('Gagal menghubungkan ke server');
        }
    };

    // ── Delete Tamu ───────────────────────────────────────
    window.deleteTamu = async function(id) {
        if (window.Swal) {
            const result = await Swal.fire({
                title: 'Hapus Data Tamu?',
                text: 'Data kunjungan tamu ini akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
        } else {
            if (!confirm('Hapus tamu ini?')) return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const res = await fetch(BASE + 'guru/piket/delete_tamu', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (json.status) {
                const card = document.getElementById('tamu-card-' + id);
                if (card) card.remove();
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
