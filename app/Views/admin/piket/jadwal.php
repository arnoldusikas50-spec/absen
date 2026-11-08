<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- ── Header (pola kartu/dapodik/izin) ── -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-calendar-week"></i> Jadwal Piket Mingguan
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul) ?></h1>
            <p class="text-xs text-slate-500 mt-1">
                Tahun Pelajaran: <strong class="text-slate-700"><?= esc($tp_active->tahun ?? '-') ?></strong>
                <span class="text-slate-300 mx-1">•</span>
                Semester: <strong class="text-slate-700"><?= esc($smt_active->smt ?? '-') ?></strong>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/piket/monitoring') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-sky-500/20 no-underline">
                <i class="bi bi-eye"></i> Monitoring Harian
            </a>
            <button type="button" id="btn-generate-piket"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-emerald-500/20">
                <i class="bi bi-magic"></i> Generate Otomatis
            </button>
            <button type="button" id="btn-add-piket"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-plus-lg"></i> Tambah Petugas
            </button>
        </div>
    </div>

    <!-- ── Petugas hari ini + statistik ── -->
    <?php
    $isHariSekolah   = $is_hari_sekolah ?? true;
    $piketTanggal    = $piket_tanggal_label ?? date('d M Y');
    $piketHariLabel  = $piket_hari_label ?? ($hari_ini_indo ?? '');
    ?>
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 shadow-xs p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Petugas Piket <?= $isHariSekolah ? 'Hari Ini' : 'Hari Sekolah Berikutnya' ?>
                </p>
                <?php if (! $isHariSekolah): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold shrink-0">
                        <i class="bi bi-calendar-x"></i> Libur
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs font-bold text-slate-700 mt-1"><?= esc($piketTanggal) ?></p>
            <?php if (! $isHariSekolah): ?>
                <p class="text-[10px] text-amber-600 mt-1 leading-relaxed">
                    Hari ini <?= esc($libur_keterangan ?? 'libur') ?>. Jadwal di bawah untuk <?= esc($piketHariLabel) ?> berikutnya.
                </p>
            <?php endif; ?>
            <div class="mt-2.5">
                <?php if (empty($piket_hari_ini)): ?>
                    <span class="text-slate-400 text-sm italic">
                        <?= $isHariSekolah ? 'Belum ada jadwal.' : 'Belum ada jadwal untuk hari sekolah berikutnya.' ?>
                    </span>
                <?php else: ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($piket_hari_ini as $p): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                <i class="bi bi-person-circle"></i> <?= esc($p->nama_guru) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $miniStats = [
            ['label' => 'Tamu', 'value' => $stats_hari_ini['tamu'] ?? 0, 'icon' => 'bi-people-fill', 'soft' => 'bg-emerald-50 text-emerald-600', 'num' => 'text-emerald-600', 'bar' => 'border-l-emerald-500'],
            ['label' => 'Kejadian', 'value' => $stats_hari_ini['kejadian'] ?? 0, 'icon' => 'bi-exclamation-triangle-fill', 'soft' => 'bg-amber-50 text-amber-500', 'num' => 'text-amber-500', 'bar' => 'border-l-amber-500'],
            ['label' => 'Absen Siswa', 'value' => $stats_hari_ini['siswa_absen'] ?? 0, 'icon' => 'bi-person-x-fill', 'soft' => 'bg-red-50 text-red-500', 'num' => 'text-red-500', 'bar' => 'border-l-red-500'],
        ];
        foreach ($miniStats as $ms): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 border-l-4 <?= $ms['bar'] ?> shadow-xs p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl <?= $ms['soft'] ?> flex items-center justify-center text-lg shrink-0"><i class="bi <?= $ms['icon'] ?>"></i></div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= $ms['label'] ?></p>
                <p class="text-xl font-extrabold <?= $ms['num'] ?> leading-tight"><?= (int) $ms['value'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Jadwal mingguan ── -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="bi bi-calendar-week"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-sm font-extrabold text-slate-900">Jadwal Guru Piket Mingguan</h2>
                <p class="text-[11px] text-slate-400">Kelola jadwal petugas piket per hari</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider">
                        <?php foreach ($hari_list as $h): ?>
                            <th class="px-4 py-3 text-center font-bold" style="width: 16.66%"><?= esc($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($hari_list as $h): ?>
                            <td class="align-top p-3 bg-slate-50/60 border-t border-slate-100">
                                <?php if (!empty($jadwal[$h])): ?>
                                    <div class="space-y-2">
                                        <?php foreach ($jadwal[$h] as $item): ?>
                                            <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-xs relative">
                                                <div class="font-bold text-slate-800 text-[13px] mb-1 pr-6">
                                                    <?= esc($item->nama_guru) ?>
                                                </div>
                                                <?php if (!empty($item->keterangan)): ?>
                                                    <div class="text-slate-400 text-[11px] italic border-t border-slate-100 pt-1 mt-1">
                                                        <?= esc($item->keterangan) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" data-del-jadwal="<?= (int) $item->id ?>"
                                                    class="absolute top-2 right-2 w-6 h-6 inline-flex items-center justify-center rounded-full text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                                                    title="Hapus">
                                                    <i class="bi bi-x-circle-fill text-sm"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8 text-slate-300">
                                        <i class="bi bi-dash-lg text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── Modal tambah (pola permits: hidden awal) ── -->
<div id="modalAdd" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="modal-header-tw">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h5>Tambah Petugas Piket</h5>
            </div>
            <button type="button" data-ui-dismiss="modal" aria-label="Tutup"
                class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">
                &times;
            </button>
        </div>
        <form id="formJadwal">
            <div class="modal-body-tw space-y-4">
                <div>
                    <label class="form-label-tw" for="jadwal-hari">Hari</label>
                    <select name="hari" id="jadwal-hari" class="form-select-tw" required>
                        <?php foreach ($hari_list as $h): ?>
                            <option value="<?= esc($h) ?>"><?= esc($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label-tw" for="jadwal-guru">Guru</label>
                    <select name="id_guru" id="jadwal-guru" class="form-select-tw" style="width:100%" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($guru_list as $g): ?>
                            <option value="<?= (int) $g->id_guru ?>"><?= esc($g->nama_guru) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label-tw" for="jadwal-ket">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="keterangan" id="jadwal-ket" maxlength="500" class="form-control-tw" placeholder="Contoh: Shift Pagi (07:00 - 12:00)">
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-light" data-ui-dismiss="modal">Batal</button>
                <button type="submit" id="btn-save-jadwal" class="btn-tw btn-primary">
                    <i class="bi bi-check-lg mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal Generate Otomatis ── -->
<div id="modalGenerate" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="modal-header-tw">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-magic text-lg"></i>
                </div>
                <div>
                    <h5 class="text-sm font-extrabold text-slate-900">Generate Petugas Piket</h5>
                    <p class="text-[11px] text-slate-400">Distribusi otomatis guru piket per hari</p>
                </div>
            </div>
            <button type="button" data-ui-dismiss="modal" aria-label="Tutup"
                class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">
                &times;
            </button>
        </div>
        <form id="formGenerateJadwal">
            <div class="modal-body-tw space-y-4">
                <!-- Info Pengecualian Kepala Sekolah -->
                <div class="flex items-start gap-3 p-3.5 bg-amber-50/90 border border-amber-200/80 rounded-2xl text-amber-800 text-xs">
                    <i class="bi bi-shield-exclamation text-amber-600 text-lg shrink-0 mt-0.5"></i>
                    <div>
                        <strong class="font-bold text-amber-900">Pengecualian Khusus Kepala Sekolah</strong>
                        <p class="mt-0.5 text-amber-700 leading-relaxed">
                            Kepala Sekolah <strong>(<?= esc($setting->kepsek ?? 'Kepala Sekolah') ?>)</strong> otomatis dikecualikan dari jadwal petugas piket.
                        </p>
                    </div>
                </div>

                <!-- Jumlah Guru per Hari -->
                <div>
                    <label class="form-label-tw" for="gen-jumlah">Jumlah Guru Piket per Hari</label>
                    <div class="relative">
                        <input type="number" name="jumlah_per_hari" id="gen-jumlah" min="1" max="10" value="2" class="form-control-tw pr-24 font-bold text-slate-800" required>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">guru / hari</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Disarankan 2 - 3 guru per hari sesuai kebutuhan sekolah.</p>
                </div>

                <!-- Pilihan Hari -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="form-label-tw mb-0">Hari yang Dijadwalkan</label>
                        <button type="button" id="btn-toggle-all-days" class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700">Pilih Semua</button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ($hari_list as $h): ?>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-100 cursor-pointer text-xs font-semibold text-slate-700 transition-colors">
                                <input type="checkbox" name="hari[]" value="<?= esc($h) ?>" class="day-check rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4" checked>
                                <span><?= esc($h) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Mode Penjadwalan -->
                <div>
                    <label class="form-label-tw mb-1.5">Metode Generate</label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:border-emerald-400 cursor-pointer text-xs text-slate-700 transition-all">
                            <input type="radio" name="mode" value="replace" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" checked>
                            <div>
                                <span class="font-bold block text-slate-800">Ganti Jadwal Lama (Disarankan)</span>
                                <span class="text-slate-500 text-[11px]">Hapus jadwal pada hari yang dipilih dan acak ulang seluruh pembagian tugas secara merata dari awal.</span>
                            </div>
                        </label>
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 bg-white hover:border-emerald-400 cursor-pointer text-xs text-slate-700 transition-all">
                            <input type="radio" name="mode" value="append" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold block text-slate-800">Lengkapi Slot Kosong Saja</span>
                                <span class="text-slate-500 text-[11px]">Pertahankan petugas yang sudah ada dan hanya tambahkan guru hingga target per hari terpenuhi.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Keterangan Label -->
                <div>
                    <label class="form-label-tw" for="gen-keterangan">Keterangan Label <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="keterangan" id="gen-keterangan" maxlength="255" class="form-control-tw" value="Petugas Piket" placeholder="Contoh: Petugas Piket">
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-light" data-ui-dismiss="modal">Batal</button>
                <button type="submit" id="btn-submit-generate" class="btn-tw bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-500/20">
                    <i class="bi bi-magic mr-1"></i> Mulai Generate
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    function spin() {
        return '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>';
    }

    $('#btn-add-piket').on('click', function() {
        $('#formJadwal').get(0).reset();
        AdminUI.modal.open('#modalAdd');
    });

    $('#btn-generate-piket').on('click', function() {
        AdminUI.modal.open('#modalGenerate');
    });

    $('#btn-toggle-all-days').on('click', function() {
        const $checks = $('.day-check');
        const allChecked = $checks.filter(':checked').length === $checks.length;
        $checks.prop('checked', !allChecked);
        $(this).text(allChecked ? 'Pilih Semua' : 'Batal Semua');
    });

    $('#formGenerateJadwal').on('submit', function(e) {
        e.preventDefault();

        const checkedDays = $('.day-check:checked').length;
        if (checkedDays === 0) {
            Swal.fire('Perhatian', 'Pilih minimal satu hari untuk dijadwalkan', 'warning');
            return;
        }

        const mode = $('input[name="mode"]:checked').val();
        const confirmMsg = mode === 'replace'
            ? 'Jadwal lama pada hari terpilih akan digantikan dengan pembagian tugas baru. Lanjutkan generate otomatis?'
            : 'Sistem akan menambahkan guru ke slot yang masih kurang. Lanjutkan?';

        Swal.fire({
            title: 'Generate Jadwal Piket?',
            text: confirmMsg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;

            const $btn = $('#btn-submit-generate');
            const orig = $btn.html();
            $btn.prop('disabled', true).html(spin() + ' Memproses...');

            const formData = $('#formGenerateJadwal').serializeArray();
            const payload = {};
            const hariArr = [];

            $.each(formData, function(i, item) {
                if (item.name === 'hari[]') {
                    hariArr.push(item.value);
                } else {
                    payload[item.name] = item.value;
                }
            });
            payload['hari'] = hariArr;
            payload[CSRF_NAME] = CSRF_HASH;

            $.post('<?= site_url('admin/piket/generate-jadwal') ?>', payload, function(res) {
                $btn.prop('disabled', false).html(orig);
                if (res && res.status) {
                    AdminUI.modal.close('#modalGenerate');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Jadwal piket berhasil digenerate.',
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal generate jadwal', 'error');
                }
            }, 'json').fail(function(xhr) {
                $btn.prop('disabled', false).html(orig);
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal menghubungi server';
                Swal.fire('Error', msg, 'error');
            });
        });
    });

    $('#formJadwal').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-save-jadwal');
        const orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Menyimpan...');

        const payload = $(this).serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});
        payload[CSRF_NAME] = CSRF_HASH;

        $.post('<?= site_url('admin/piket/save_jadwal') ?>', payload, function (res) {
            $btn.prop('disabled', false).html(orig);
            if (res && res.status) {
                AdminUI.modal.close('#modalAdd');
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message || 'Jadwal tersimpan.',
                            timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan jadwal', 'error');
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false).html(orig);
            Swal.fire('Error', 'Gagal menghubungi server', 'error');
        });
    });

    $(document).on('click', '[data-del-jadwal]', function() {
        const id = $(this).data('del-jadwal');
        Swal.fire({
            title: 'Hapus petugas ini dari jadwal?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            const payload = { id: id };
            payload[CSRF_NAME] = CSRF_HASH;
            $.post('<?= site_url('admin/piket/delete_jadwal') ?>', payload, function (res) {
                if (res && res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus!', timer: 1200, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus', 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
            });
        });
    });
});
</script>
<?= $this->endSection(); ?>
