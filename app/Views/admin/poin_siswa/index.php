<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .tab-pill { transition: background .15s, color .15s, box-shadow .15s; }
    .tab-pill.active { background: #fff; color: var(--primary-600); box-shadow: 0 1px 6px rgba(0,0,0,.08); }
    .skor-ring { width: 44px; height: 44px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0; }
    .skor-baik { background: #dcfce7; color: #15803d; }
    .skor-waspada { background: #fef9c3; color: #a16207; }
    .skor-bahaya { background: #fee2e2; color: #b91c1c; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="fade-in-up p-4 md:p-6 space-y-4 w-full max-w-none">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold" aria-label="Breadcrumb">
        <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 no-underline">Dashboard</a>
        <span>/</span>
        <span class="text-slate-600">Poin Disiplin Siswa</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-50 text-red-700 rounded-full text-[11px] font-bold mb-2">
                <i class="bi bi-exclamation-diamond-fill"></i> Disiplin Siswa
            </div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Poin Disiplin Siswa') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Skor awal 100 · pelanggaran mengurangi · penghargaan menambah · TP <?= esc($tp_active->tahun ?? '-') ?> <?= esc($smt_active->smt ?? '') ?></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <button type="button" class="btn-tw btn-light" data-ui-toggle="modal" data-ui-target="#modalSync">
                <i class="bi bi-arrow-repeat mr-1"></i> Sinkron Presensi
            </button>
            <button type="button" class="btn-tw btn-primary" onclick="openRecordModal()">
                <i class="bi bi-plus-lg mr-1"></i> Catat Poin
            </button>
        </div>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Total Siswa</div>
            <div class="text-2xl font-extrabold text-slate-900"><?= number_format($stats['total_siswa'] ?? 0) ?></div>
            <div class="text-[11px] text-slate-400">Terdata di leaderboard</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Rata-rata Skor</div>
            <div class="text-2xl font-extrabold text-blue-700"><?= number_format($stats['rata_rata'] ?? 100, 1) ?></div>
            <div class="text-[11px] text-slate-400">Dari 100 poin</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Skor &lt; 75</div>
            <div class="text-2xl font-extrabold text-red-600"><?= number_format($stats['di_bawah_75'] ?? 0) ?></div>
            <div class="text-[11px] text-slate-400">Perlu pembinaan</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Kasus Bulan Ini</div>
            <div class="text-2xl font-extrabold text-amber-600"><?= number_format($stats['kasus_bulan_ini'] ?? 0) ?></div>
            <div class="text-[11px] text-slate-400"><?= date('F Y') ?></div>
        </div>
    </div>

    <!-- Kartu utama: tabs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs">
        <div class="p-3 border-b border-slate-100">
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl" role="tablist" aria-label="Tab poin siswa">
                    <button type="button" class="tab-pill active px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap" data-ui-toggle="tab" data-ui-target="#pane-leaderboard" role="tab">
                        <i class="bi bi-trophy mr-1"></i> Leaderboard
                    </button>
                    <button type="button" class="tab-pill px-4 py-2 rounded-lg text-xs font-bold text-slate-500 whitespace-nowrap" data-ui-toggle="tab" data-ui-target="#pane-kategori" role="tab">
                        <i class="bi bi-list-check mr-1"></i> Kategori <span class="badge-tw badge-secondary"><?= count($kategori ?? []) ?></span>
                    </button>
                </div>
                <form method="get" action="<?= site_url('admin/poin-siswa') ?>" class="ml-auto flex items-center gap-2">
                    <select name="kelas" class="form-select-tw !w-auto text-xs" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        <?php foreach (($kelas_list ?? []) as $kls): ?>
                            <option value="<?= (int) $kls->id_kelas ?>" <?= ((int) ($filter_kelas ?? 0) === (int) $kls->id_kelas) ? 'selected' : '' ?>>
                                <?= esc($kls->nama_kelas ?? ('Kelas #' . $kls->id_kelas)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="p-4 md:p-5">
            <div class="tab-content">
                <!-- Leaderboard -->
                <div class="tab-pane show active" id="pane-leaderboard" role="tabpanel">
                    <?php if (empty($leaderboard)): ?>
                        <div class="text-center py-10 text-slate-400 text-sm">
                            <i class="bi bi-inbox text-3xl block mb-2"></i>
                            Belum ada data siswa<?= !empty($filter_kelas) ? ' di kelas ini' : '' ?>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive-tw">
                            <table class="table-tw" id="table-poin-siswa">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:60px">Rank</th>
                                        <th>Siswa</th>
                                        <th>Kelas</th>
                                        <th class="text-center">Kasus</th>
                                        <th class="text-center">Skor</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 0; foreach ($leaderboard as $r): $rank++; $skor = (int) ($r->skor ?? 100); ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php if ($rank <= 3): ?>
                                                    <span class="badge-tw <?= $rank === 1 ? 'badge-warning' : ($rank === 2 ? 'badge-secondary' : 'badge-info') ?> font-extrabold">#<?= $rank ?></span>
                                                <?php else: ?>
                                                    <span class="text-slate-400 font-bold">#<?= $rank ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="font-bold text-slate-800"><?= esc($r->nama ?? '-') ?></div>
                                                <div class="text-[11px] text-slate-400 font-mono">NIS <?= esc($r->nis ?? '-') ?></div>
                                            </td>
                                            <td><span class="badge-tw badge-primary"><?= esc($r->nama_kelas ?? '-') ?></span></td>
                                            <td class="text-center font-bold text-slate-600"><?= (int) ($r->total_kasus ?? 0) ?>×</td>
                                            <td class="text-center">
                                                <span class="skor-ring mx-auto <?= $skor >= 90 ? 'skor-baik' : ($skor >= 75 ? 'skor-waspada' : 'skor-bahaya') ?>"><?= $skor ?></span>
                                            </td>
                                            <td class="text-right whitespace-nowrap">
                                                <button type="button" class="btn-tw btn-tw-sm btn-light" title="Riwayat" onclick="openHistory(<?= (int) $r->id_siswa ?>, '<?= esc($r->nama ?? '', 'attr') ?>')">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                                <button type="button" class="btn-tw btn-tw-sm btn-warning" title="Catat poin" onclick="openRecordModal(<?= (int) $r->id_siswa ?>, '<?= esc($r->nama ?? '', 'attr') ?>')">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                                <a href="<?= site_url('admin/poin-siswa/detail/' . (int) $r->id_siswa) ?>" class="btn-tw btn-tw-sm btn-primary" title="Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Kategori master -->
                <div class="tab-pane" id="pane-kategori" role="tabpanel">
                    <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
                        <p class="text-xs text-slate-500 mb-0">Pencatatan manual <b>hanya</b> dari kategori aktif di bawah. Nonaktif = tidak bisa dipilih.</p>
                        <button type="button" class="btn-tw btn-tw-sm btn-primary" onclick="openKategoriModal()">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Kategori
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        <?php foreach (($kategori ?? []) as $k): ?>
                            <div class="rounded-2xl border <?= (int) $k->is_active ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-60' ?> p-3.5 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $k->jenis === 'PENGHARGAAN' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?>">
                                    <i class="bi <?= $k->jenis === 'PENGHARGAAN' ? 'bi-award' : 'bi-exclamation-triangle' ?>"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-slate-800 text-sm truncate"><?= esc($k->nama) ?></div>
                                    <div class="text-[11px] text-slate-400">
                                        <span class="badge-tw <?= $k->jenis === 'PENGHARGAAN' ? 'badge-success' : 'badge-danger' ?>"><?= $k->jenis === 'PENGHARGAAN' ? '+' . (int) $k->bobot : '-' . (int) $k->bobot ?></span>
                                        <?= (int) $k->is_active ? '' : '<span class="badge-tw badge-secondary ml-1">Nonaktif</span>' ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 shrink-0">
                                    <button type="button" class="btn-tw btn-tw-sm btn-light" title="Edit" onclick='openKategoriModal(<?= json_encode($k) ?>)'><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-outline-danger" title="Hapus" onclick="deleteKategori(<?= (int) $k->id ?>, '<?= esc($k->nama, 'attr') ?>')"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal Catat Poin ═══ -->
<div id="modalRecord" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <form id="form-record" novalidate>
            <div class="bg-white rounded-2xl overflow-hidden">
                <div class="modal-header-tw">
                    <div>
                        <h5 class="font-bold text-slate-800">Catat Poin Disiplin</h5>
                        <p class="text-[11px] text-slate-400 mb-0" id="record-sub">Pilih siswa &amp; kategori pelanggaran/penghargaan</p>
                    </div>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
                </div>
                <div class="modal-body-tw space-y-4">
                    <div>
                        <label class="form-label-tw req" for="rec-siswa">Siswa</label>
                        <select id="rec-siswa" name="id_siswa" class="form-select-tw" required>
                            <option value="">— Pilih siswa —</option>
                            <?php foreach ($leaderboard as $r): ?>
                                <option value="<?= (int) $r->id_siswa ?>"><?= esc($r->nama ?? '-') ?> (<?= esc($r->nama_kelas ?? '-') ?> · skor <?= (int) ($r->skor ?? 100) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-tw req" for="rec-kategori">Kategori</label>
                        <select id="rec-kategori" name="kategori_id" class="form-select-tw" required>
                            <option value="">— Pilih kategori —</option>
                            <optgroup label="Pelanggaran (mengurangi)">
                                <?php foreach (($kategori ?? []) as $k): if ($k->jenis !== 'PELANGGARAN' || !(int) $k->is_active) continue; ?>
                                    <option value="<?= (int) $k->id ?>" data-bobot="-<?= (int) $k->bobot ?>"><?= esc($k->nama) ?> (−<?= (int) $k->bobot ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Penghargaan (menambah)">
                                <?php foreach (($kategori ?? []) as $k): if ($k->jenis !== 'PENGHARGAAN' || !(int) $k->is_active) continue; ?>
                                    <option value="<?= (int) $k->id ?>" data-bobot="+<?= (int) $k->bobot ?>"><?= esc($k->nama) ?> (+<?= (int) $k->bobot ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <p class="text-[11px] mt-1 mb-0" id="rec-preview"></p>
                    </div>
                    <div>
                        <label class="form-label-tw req" for="rec-ket">Keterangan</label>
                        <textarea id="rec-ket" name="keterangan" rows="2" class="form-control-tw" required minlength="5" maxlength="255" placeholder="Contoh: Terlambat apel pagi Senin, 23 Sep (min. 5 karakter)"></textarea>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-warning" id="btn-record-save"><i class="bi bi-save mr-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Modal Kategori ═══ -->
<div id="modalKategori" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <form id="form-kategori" novalidate>
            <input type="hidden" name="id" id="kat-id">
            <div class="bg-white rounded-2xl overflow-hidden">
                <div class="modal-header-tw">
                    <div>
                        <h5 class="font-bold text-slate-800" id="kat-title">Tambah Kategori</h5>
                        <p class="text-[11px] text-slate-400 mb-0">Bobot 1–100 poin</p>
                    </div>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
                </div>
                <div class="modal-body-tw space-y-4">
                    <div>
                        <label class="form-label-tw req" for="kat-nama">Nama Kategori</label>
                        <input type="text" id="kat-nama" name="nama" class="form-control-tw" required minlength="3" maxlength="100" placeholder="Contoh: Merokok di lingkungan sekolah">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label-tw req" for="kat-jenis">Jenis</label>
                            <select id="kat-jenis" name="jenis" class="form-select-tw" required>
                                <option value="PELANGGARAN">Pelanggaran (−)</option>
                                <option value="PENGHARGAAN">Penghargaan (+)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label-tw req" for="kat-bobot">Bobot Poin</label>
                            <input type="number" id="kat-bobot" name="bobot" class="form-control-tw" required min="1" max="100" value="5">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 cursor-pointer">
                        <input type="checkbox" id="kat-aktif" name="is_active" value="1" checked class="accent-blue-600"> Aktif (bisa dipilih saat mencatat)
                    </label>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary" id="btn-kat-save"><i class="bi bi-save mr-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Modal Riwayat ═══ -->
<div id="modalHistory" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="modal-header-tw">
                <div>
                    <h5 class="font-bold text-slate-800 mb-0" id="hist-nama">–</h5>
                    <span class="text-[11px] text-slate-400">Skor saat ini: <b id="hist-skor">100</b> · transaksi terakhir</span>
                </div>
                <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
            </div>
            <div class="modal-body-tw">
                <div id="hist-list" class="space-y-2 max-h-[50vh] overflow-y-auto"></div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal Sinkron Presensi ═══ -->
<div id="modalSync" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-md">
        <form id="form-sync">
            <div class="bg-white rounded-2xl overflow-hidden">
                <div class="modal-header-tw">
                    <div>
                        <h5 class="font-bold text-slate-800">Sinkron Presensi → Poin</h5>
                        <p class="text-[11px] text-slate-400 mb-0">Terlambat memakai bobot kategori "Terlambat", alpha memakai "Bolos". Aman dijalankan berulang; pengurangan yang tak lagi berlaku ikut dicabut.</p>
                    </div>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-lg leading-none border-none cursor-pointer">&times;</button>
                </div>
                <div class="modal-body-tw grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label-tw" for="sync-bulan">Bulan</label>
                        <input type="number" id="sync-bulan" name="bulan" class="form-control-tw" min="1" max="12" value="<?= date('n') ?>">
                    </div>
                    <div>
                        <label class="form-label-tw" for="sync-tahun">Tahun</label>
                        <input type="number" id="sync-tahun" name="tahun" class="form-control-tw" min="2020" max="<?= date('Y') + 1 ?>" value="<?= date('Y') ?>">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label-tw" for="sync-kelas">Kelas (opsional)</label>
                        <select id="sync-kelas" name="id_kelas" class="form-select-tw">
                            <option value="0">Semua kelas</option>
                            <?php foreach (($kelas_list ?? []) as $kls): ?>
                                <option value="<?= (int) $kls->id_kelas ?>"><?= esc($kls->nama_kelas ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary" id="btn-sync-save"><i class="bi bi-arrow-repeat mr-1"></i>Jalankan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    if ($.fn.DataTable) {
        $('#table-poin-siswa').DataTable({ ordering: true, order: [[4, 'asc']], pageLength: 25, language: dt_lang_id });
    }

    const escHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const spin = () => '<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span>';
    const csrf = () => { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; };

    // Preview bobot kategori
    $('#rec-kategori').on('change', function () {
        const b = $(this).find('option:selected').data('bobot');
        $('#rec-preview').html(b ? ('Efek ke skor: <b class="' + (String(b).startsWith('+') ? 'text-emerald-600' : 'text-red-600') + '">' + escHtml(b) + ' poin</b>') : '');
    });

    window.openRecordModal = function (idSiswa, nama) {
        $('#form-record')[0].reset();
        $('#rec-preview').html('');
        if (idSiswa) {
            $('#rec-siswa').val(String(idSiswa));
            $('#record-sub').text('Catat untuk: ' + (nama || ''));
        } else {
            $('#record-sub').text('Pilih siswa & kategori pelanggaran/penghargaan');
        }
        $('#modalRecord').appModal('show');
    };

    $('#form-record').on('submit', function (e) {
        e.preventDefault();
        const idSiswa = $('#rec-siswa').val(), kat = $('#rec-kategori').val(), ket = $('#rec-ket').val().trim();
        if (!idSiswa) { Swal.fire('Perhatian', 'Pilih siswa terlebih dahulu.', 'warning'); return; }
        if (!kat) { Swal.fire('Perhatian', 'Pilih kategori terlebih dahulu.', 'warning'); return; }
        if (ket.length < 5) { Swal.fire('Perhatian', 'Keterangan minimal 5 karakter.', 'warning'); return; }
        const $btn = $('#btn-record-save'), orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Menyimpan...');
        const payload = Object.assign(csrf(), { id_siswa: idSiswa, kategori_id: kat, keterangan: ket });
        $.ajax({
            url: '<?= site_url('admin/poin-siswa/record') ?>', type: 'POST', data: payload, dataType: 'json',
            success: (res) => {
                $btn.prop('disabled', false).html(orig);
                if (res && res.status) {
                    $('#modalRecord').appModal('hide');
                    Swal.fire('Berhasil', (res.message || 'Tersimpan.') + (res.data != null ? ' Skor kini: ' + res.data : ''), 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan.', 'error');
                }
            },
            error: () => { $btn.prop('disabled', false).html(orig); Swal.fire('Error', 'Gagal menghubungi server.', 'error'); }
        });
    });

    window.openKategoriModal = function (k) {
        $('#form-kategori')[0].reset();
        if (k && k.id) {
            $('#kat-title').text('Edit Kategori');
            $('#kat-id').val(k.id);
            $('#kat-nama').val(k.nama);
            $('#kat-jenis').val(k.jenis);
            $('#kat-bobot').val(k.bobot);
            $('#kat-aktif').prop('checked', String(k.is_active) === '1');
        } else {
            $('#kat-title').text('Tambah Kategori');
            $('#kat-id').val('');
            $('#kat-aktif').prop('checked', true);
        }
        $('#modalKategori').appModal('show');
    };

    $('#form-kategori').on('submit', function (e) {
        e.preventDefault();
        const nama = $('#kat-nama').val().trim(), bobot = parseInt($('#kat-bobot').val() || '0', 10);
        if (nama.length < 3) { Swal.fire('Perhatian', 'Nama kategori minimal 3 karakter.', 'warning'); return; }
        if (!(bobot >= 1 && bobot <= 100)) { Swal.fire('Perhatian', 'Bobot harus 1–100.', 'warning'); return; }
        const $btn = $('#btn-kat-save'), orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Menyimpan...');
        const payload = Object.assign(csrf(), {
            id: $('#kat-id').val(), nama: nama, jenis: $('#kat-jenis').val(),
            bobot: bobot, is_active: $('#kat-aktif').is(':checked') ? 1 : 0
        });
        $.ajax({
            url: '<?= site_url('admin/poin-siswa/kategori/save') ?>', type: 'POST', data: payload, dataType: 'json',
            success: (res) => {
                $btn.prop('disabled', false).html(orig);
                if (res && res.status) {
                    $('#modalKategori').appModal('hide');
                    Swal.fire('Berhasil', res.message || 'Tersimpan.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan.', 'error');
                }
            },
            error: () => { $btn.prop('disabled', false).html(orig); Swal.fire('Error', 'Gagal menghubungi server.', 'error'); }
        });
    });

    window.deleteKategori = function (id, nama) {
        Swal.fire({
            title: 'Hapus "' + nama + '"?', text: 'Kategori yang sudah dipakai riwayat tidak bisa dihapus.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            const payload = Object.assign(csrf(), { id: id });
            $.ajax({
                url: '<?= site_url('admin/poin-siswa/kategori/delete') ?>', type: 'POST', data: payload, dataType: 'json',
                success: (res) => {
                    if (res && res.status) Swal.fire('Terhapus', res.message, 'success').then(() => location.reload());
                    else Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus.', 'error');
                },
                error: () => Swal.fire('Error', 'Gagal menghubungi server.', 'error')
            });
        });
    };

    window.openHistory = function (idSiswa, nama) {
        $('#hist-nama').text(nama || 'Riwayat');
        $('#hist-list').html('<p class="text-center text-slate-400 text-xs py-6">Memuat...</p>');
        $('#modalHistory').appModal('show');
        $.ajax({
            url: '<?= site_url('admin/poin-siswa/history/') ?>' + idSiswa, type: 'GET', dataType: 'json',
            success: (res) => {
                if (!(res && res.status)) { $('#hist-list').html('<p class="text-center text-red-500 text-xs py-6">Gagal memuat.</p>'); return; }
                $('#hist-skor').text(res.data.skor ?? 100);
                const list = res.data.history || [];
                if (!list.length) { $('#hist-list').html('<p class="text-center text-slate-400 text-xs py-6">Belum ada transaksi.</p>'); return; }
                $('#hist-list').html(list.map(h => {
                    const p = parseInt(h.poin || '0', 10);
                    return '<div class="flex items-center gap-2.5 rounded-xl border border-slate-100 px-3 py-2">'
                        + '<span class="badge-tw ' + (p >= 0 ? 'badge-success' : 'badge-danger') + ' font-mono">' + (p > 0 ? '+' : '') + p + '</span>'
                        + '<div class="min-w-0 flex-1"><div class="text-xs font-bold text-slate-700 truncate">' + escHtml(h.kategori_nama || h.tipe) + '</div>'
                        + '<div class="text-[11px] text-slate-400 truncate">' + escHtml(h.keterangan || '') + ' · ' + escHtml(h.created_at || '') + '</div></div></div>';
                }).join(''));
            },
            error: () => $('#hist-list').html('<p class="text-center text-red-500 text-xs py-6">Gagal memuat.</p>')
        });
    };

    $('#form-sync').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-sync-save'), orig = $btn.html();
        $btn.prop('disabled', true).html(spin() + ' Menjalankan...');
        const payload = Object.assign(csrf(), { bulan: $('#sync-bulan').val(), tahun: $('#sync-tahun').val(), id_kelas: $('#sync-kelas').val() });
        $.ajax({
            url: '<?= site_url('admin/poin-siswa/sync') ?>', type: 'POST', data: payload, dataType: 'json',
            success: (res) => {
                $btn.prop('disabled', false).html(orig);
                if (res && res.status) {
                    $('#modalSync').appModal('hide');
                    Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal sinkron.', 'error');
                }
            },
            error: () => { $btn.prop('disabled', false).html(orig); Swal.fire('Error', 'Gagal menghubungi server.', 'error'); }
        });
    });
});
</script>
<?= $this->endSection() ?>
