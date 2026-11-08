<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.bdr-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:9999px; font-size:.68rem; font-weight:800; }
.bdr-semua   { background:#e0e7ff; color:#4338ca; }
.bdr-tingkat { background:#dcfce7; color:#15803d; }
.bdr-kelas   { background:#e0f2fe; color:#0369a1; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="fade-in-up p-4 md:p-6 space-y-4 w-full max-w-none">
    <nav class="flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold" aria-label="Breadcrumb">
        <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 no-underline">Dashboard</a>
        <span>/</span>
        <span class="text-slate-600">Jadwal BDR</span>
    </nav>

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-teal-50 text-teal-700 rounded-full text-[11px] font-bold mb-2">
                <i class="bi bi-house-door-fill"></i> Belajar Dari Rumah
            </div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Jadwal BDR') ?></h1>
            <p class="text-xs text-slate-500 mt-1">
                Tandai kelas yang tidak KBM di sekolah (mis. kelas X &amp; XI diliburkan saat kelas XII ujian).
                Siswanya tercatat <strong>BDR</strong>, bukan Alpha, dan hari itu tidak menurunkan persen kehadiran.
            </p>
        </div>
        <?php if (!empty($boleh)): ?>
        <div class="shrink-0">
            <button type="button" class="btn-tw btn-primary" id="btn-tambah">
                <i class="bi bi-plus-lg mr-1"></i> Tambah Jadwal BDR
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Kelas yang BDR hari ini -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs">
        <h2 class="text-sm font-extrabold text-slate-900 mb-2">
            <i class="bi bi-calendar-day text-teal-600 mr-1"></i> Hari ini, <?= date('d-m-Y') ?>
        </h2>
        <?php if (empty($hari_ini)): ?>
            <p class="text-xs text-slate-400">Tidak ada jadwal BDR hari ini.</p>
        <?php else: ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($hari_ini as $h): ?>
                    <span class="bdr-badge bdr-<?= strtolower($h->lingkup ?? 'kelas') ?>">
                        <i class="bi bi-dot"></i><?= esc($h->judul ?: 'Belajar dari rumah') ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Cron pukul 18:00 menandai siswa kelas tersebut BDR, bukan Alpha.</p>
        <?php endif; ?>
    </div>

    <!-- Daftar jadwal -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h2 class="text-sm font-extrabold text-slate-900">Daftar Jadwal</h2>
        </div>
        <div class="table-responsive-tw">
            <table class="table-tw">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px">#</th>
                        <th>Lingkup</th>
                        <th>Kelas / Tingkat</th>
                        <th>Tanggal</th>
                        <th>Keperluan</th>
                        <?php if (!empty($boleh)): ?><th class="text-center">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($daftar)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8 text-xs">
                            Belum ada jadwal BDR. Klik <strong>Tambah Jadwal BDR</strong> untuk membuat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($daftar as $d):
                        $selesai = $d->tgl_selesai ?: $d->tgl_mulai;
                        $sudahLewat = $selesai < date('Y-m-d');
                    ?>
                    <tr>
                        <td class="text-center text-slate-400 text-xs font-mono"><?= $no++ ?></td>
                        <td><span class="bdr-badge bdr-<?= strtolower($d->lingkup ?? 'kelas') ?>"><?= esc($d->lingkup) ?></span></td>
                        <td class="text-xs font-semibold text-slate-700">
                            <?php if ($d->lingkup === 'KELAS'): ?>
                                <?= esc($d->nama_kelas ?: ('Kelas #' . $d->id_kelas)) ?>
                            <?php elseif ($d->lingkup === 'TINGKAT'): ?>
                                Tingkat <?= esc($d->level ?: $d->level_id) ?>
                            <?php else: ?>
                                Semua kelas
                            <?php endif; ?>
                        </td>
                        <td class="text-xs text-slate-600 whitespace-nowrap">
                            <?= date('d/m/Y', strtotime($d->tgl_mulai)) ?>
                            <?php if ($d->tgl_selesai && $d->tgl_selesai !== $d->tgl_mulai): ?>
                                &ndash; <?= date('d/m/Y', strtotime($d->tgl_selesai)) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs">
                            <div class="font-semibold text-slate-800"><?= esc($d->judul) ?></div>
                            <?php if (!empty($d->keterangan)): ?>
                                <div class="text-slate-400"><?= esc($d->keterangan) ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if (!empty($boleh)): ?>
                        <td class="text-center whitespace-nowrap">
                            <?php if ($sudahLewat): ?>
                                <button type="button" class="btn-tw btn-tw-sm btn-light" data-terapkan="<?= (int) $d->id ?>"
                                        title="Ubah absensi Alpha yang sudah terlanjur tercatat menjadi BDR">
                                    <i class="bi bi-magic mr-1"></i> Terapkan
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn-tw btn-tw-sm btn-light" data-edit='<?= json_encode([
                                "id" => (int) $d->id, "lingkup" => $d->lingkup, "id_kelas" => $d->id_kelas,
                                "level_id" => $d->level_id, "tgl_mulai" => $d->tgl_mulai, "tgl_selesai" => $d->tgl_selesai,
                                "judul" => $d->judul, "keterangan" => $d->keterangan,
                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn-tw btn-tw-sm btn-danger" data-hapus="<?= (int) $d->id ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($boleh)): ?>
<!-- ─── Modal form ─── -->
<div id="modalBdr" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="form-bdr" class="flex flex-col gap-4">
            <div class="modal-header-tw">
                <h3 class="text-sm font-extrabold text-slate-900" id="modal-title">Tambah Jadwal BDR</h3>
                <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
            </div>
            <div class="modal-body-tw space-y-4">
                <input type="hidden" name="id" id="f_id" value="">

                <div>
                    <label class="form-label-tw">Lingkup yang diliburkan</label>
                    <select name="lingkup" id="f_lingkup" class="form-control-tw">
                        <option value="KELAS">Satu kelas tertentu</option>
                        <option value="TINGKAT">Satu tingkat (kelas paralel, mis. semua kelas X)</option>
                        <option value="SEMUA">Semua kelas</option>
                    </select>
                </div>

                <div id="wrap_kelas">
                    <label class="form-label-tw">Kelas</label>
                    <select name="id_kelas" id="f_kelas" class="form-control-tw">
                        <option value="">Pilih kelas :</option>
                        <?php foreach (($kelass ?? []) as $idK => $namaKelas): ?>
                            <option value="<?= (int) $idK ?>"><?= esc($namaKelas) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="wrap_tingkat" class="hidden">
                    <label class="form-label-tw">Tingkat</label>
                    <select name="level_id" id="f_level" class="form-control-tw">
                        <?php foreach (($levels ?? []) as $lv => $label): if ($lv === '') continue; ?>
                            <option value="<?= (int) $lv ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Mis. 10 = kelas X, 11 = kelas XI, 12 = kelas XII.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label-tw">Dari tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="tgl_mulai" id="f_mulai" class="form-control-tw" required>
                    </div>
                    <div>
                        <label class="form-label-tw">Sampai tanggal</label>
                        <input type="date" name="tgl_selesai" id="f_selesai" class="form-control-tw">
                        <p class="text-[11px] text-slate-400 mt-1">Kosongkan bila hanya satu hari.</p>
                    </div>
                </div>

                <div>
                    <label class="form-label-tw">Keperluan</label>
                    <input type="text" name="judul" id="f_judul" class="form-control-tw" maxlength="255"
                           placeholder="Ujian Sekolah Kelas XII — kelas X & XI belajar dari rumah">
                </div>

                <div>
                    <label class="form-label-tw">Keterangan</label>
                    <textarea name="keterangan" id="f_ket" rows="2" class="form-control-tw"
                              placeholder="Tugas yang dikerjakan dari rumah, catatan wali kelas, dsb."></textarea>
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-primary" id="btn-simpan">
                    <i class="bi bi-check2-circle mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    const csrf = () => { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; };
    const BASE = '<?= esc(base_url('admin/bdr'), 'js') ?>';

    function toggleLingkup() {
        const v = $('#f_lingkup').val();
        $('#wrap_kelas').toggleClass('hidden', v !== 'KELAS');
        $('#wrap_tingkat').toggleClass('hidden', v !== 'TINGKAT');
    }

    function openForm(rec) {
        rec = rec || {};
        $('#modal-title').text(rec.id ? 'Edit Jadwal BDR' : 'Tambah Jadwal BDR');
        $('#f_id').val(rec.id || '');
        $('#f_lingkup').val(rec.lingkup || 'KELAS');
        $('#f_kelas').val(rec.id_kelas || '');
        $('#f_level').val(rec.level_id || '10');
        $('#f_mulai').val(rec.tgl_mulai || new Date().toLocaleDateString('en-CA'));
        $('#f_selesai').val(rec.tgl_selesai || '');
        $('#f_judul').val(rec.judul || '');
        $('#f_ket').val(rec.keterangan || '');
        toggleLingkup();
        $('#modalBdr').appModal('show');
    }

    $('#f_lingkup').on('change', toggleLingkup);
    $('#btn-tambah').on('click', () => openForm());

    $(document).on('click', '[data-edit]', function() {
        try { openForm(JSON.parse($(this).attr('data-edit'))); } catch (e) {}
    });

    $('#form-bdr').on('submit', function(e) {
        e.preventDefault();
        const payload = csrf();
        ['id','lingkup','id_kelas','level_id','tgl_mulai','tgl_selesai','judul','keterangan'].forEach(n => {
            payload[n] = $('[name="' + n + '"]').val() || '';
        });

        const $btn = $('#btn-simpan'); const orig = $btn.html();
        $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full mr-1"></span> Menyimpan…');

        $.ajax({
            url: BASE + '/save', type: 'POST', data: payload, dataType: 'json',
            success: res => {
                if (res.status) {
                    $('#modalBdr').appModal('hide');
                    Swal.fire('Berhasil', res.message || 'Tersimpan.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan.', 'error');
                }
            },
            error: xhr => Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghubungi server.', 'error'),
            complete: () => $btn.prop('disabled', false).html(orig)
        });
    });

    $(document).on('click', '[data-terapkan]', function() {
        const id = $(this).data('terapkan');
        Swal.fire({
            title: 'Terapkan jadwal ini?',
            html: 'Absensi <strong>Alpha</strong> pada tanggal tersebut (yang belum ada jam masuknya) akan diubah menjadi <strong>BDR</strong>.',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#2563eb', confirmButtonText: 'Ya, terapkan', cancelButtonText: 'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: BASE + '/terapkan/' + id, type: 'POST', data: csrf(), dataType: 'json',
                success: res => Swal.fire(res.status ? 'Selesai' : 'Gagal', res.message || '', res.status ? 'success' : 'error').then(() => { if (res.status) location.reload(); }),
                error: () => Swal.fire('Error', 'Gagal menghubungi server.', 'error')
            });
        });
    });

    $(document).on('click', '[data-hapus]', function() {
        const id = $(this).data('hapus');
        Swal.fire({ title: 'Hapus jadwal BDR?', icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc2626', confirmButtonText: 'Hapus', cancelButtonText: 'Batal' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: BASE + '/delete/' + id, type: 'POST', data: csrf(), dataType: 'json',
                    success: res => Swal.fire(res.status ? 'Terhapus' : 'Gagal', res.message || '', res.status ? 'success' : 'error').then(() => { if (res.status) location.reload(); }),
                    error: () => Swal.fire('Error', 'Gagal menghubungi server.', 'error')
                });
            });
    });
});
</script>
<?= $this->endSection() ?>
