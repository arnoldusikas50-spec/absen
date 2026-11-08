<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ──────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('guru/piket') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1" title="Kembali">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Disiplin Siswa</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Catat poin pelanggaran / penghargaan</p>
        </div>
    </div>
</div>

<div class="px-4 pb-12 space-y-3 mt-2.5">
    <!-- Filter kelas -->
    <form method="get" class="m-0">
        <select name="kelas" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-700" onchange="this.form.submit()">
            <option value="">Semua Kelas</option>
            <?php foreach (($kelas_list ?? []) as $kls): ?>
                <option value="<?= (int) $kls->id_kelas ?>" <?= ((int) ($filter_kelas ?? 0) === (int) $kls->id_kelas) ? 'selected' : '' ?>>
                    <?= esc($kls->nama_kelas ?? '') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Cari siswa -->
    <div class="relative">
        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
        <input type="search" id="cari-siswa" placeholder="Cari nama / NIS..."
            class="w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
    </div>

    <!-- Daftar siswa -->
    <div id="list-siswa" class="space-y-2">
        <?php if (empty($leaderboard)): ?>
            <p class="text-center text-slate-400 text-xs py-8">Belum ada data siswa.</p>
        <?php else: ?>
            <?php foreach ($leaderboard as $r): $skor = (int) ($r->skor ?? 100); ?>
                <div class="siswa-card bg-white border border-slate-200/80 rounded-2xl p-3 flex items-center gap-3 shadow-xs"
                    data-cari="<?= esc(strtolower(($r->nama ?? '') . ' ' . ($r->nis ?? ''))) ?>">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center text-base font-extrabold shrink-0 <?= $skor >= 90 ? 'bg-emerald-50 text-emerald-600' : ($skor >= 75 ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600') ?>">
                        <?= $skor ?>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-slate-800 truncate"><?= esc($r->nama ?? '-') ?></div>
                        <div class="text-[11px] text-slate-400"><?= esc($r->nama_kelas ?? '-') ?> · <?= (int) ($r->total_kasus ?? 0) ?>× kasus</div>
                    </div>
                    <button type="button" onclick="openCatat(<?= (int) $r->id_siswa ?>, '<?= esc($r->nama ?? '', 'attr') ?>', <?= $skor ?>)"
                        class="px-3 py-1.5 rounded-full bg-amber-500 text-white text-xs font-bold shrink-0">
                        <i class="bi bi-plus-lg"></i> Catat
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <p id="cari-kosong" class="hidden text-center text-slate-400 text-xs py-6">Tidak ada siswa yang cocok.</p>
</div>

<!-- ── MODAL CATAT (BOTTOM SHEET) ── -->
<div class="tw-modal" id="modalCatat" aria-hidden="true">
    <div class="tw-modal-box">
        <form id="form-catat">
            <?= csrf_field() ?>
            <input type="hidden" name="id_siswa" id="cat-id-siswa">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-extrabold text-slate-800 text-sm m-0" id="cat-nama">Catat Poin</h3>
                    <p class="text-[11px] text-slate-400 m-0">Skor saat ini: <b id="cat-skor">100</b></p>
                </div>
                <button type="button" data-tw-dismiss="modal" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="text-[11px] font-bold text-slate-600 uppercase" for="cat-kategori">Kategori *</label>
                    <select id="cat-kategori" name="kategori_id" required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="">— Pilih kategori —</option>
                        <optgroup label="Pelanggaran (mengurangi)">
                            <?php foreach (($kategori ?? []) as $k): if ($k->jenis !== 'PELANGGARAN' || !(int) $k->is_active) continue; ?>
                                <option value="<?= (int) $k->id ?>"><?= esc($k->nama) ?> (−<?= (int) $k->bobot ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Penghargaan (menambah)">
                            <?php foreach (($kategori ?? []) as $k): if ($k->jenis !== 'PENGHARGAAN' || !(int) $k->is_active) continue; ?>
                                <option value="<?= (int) $k->id ?>"><?= esc($k->nama) ?> (+<?= (int) $k->bobot ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-600 uppercase" for="cat-ket">Keterangan *</label>
                    <textarea id="cat-ket" name="keterangan" rows="2" required minlength="5" maxlength="255"
                        placeholder="Contoh: Terlambat apel pagi (min. 5 karakter)"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400"></textarea>
                </div>
                <button type="submit" id="btn-catat" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold transition-colors">
                    <i class="bi bi-save mr-1"></i> Simpan Catatan
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('cari-siswa').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let n = 0;
    document.querySelectorAll('.siswa-card').forEach(c => {
        const hit = !q || (c.dataset.cari || '').includes(q);
        c.style.display = hit ? '' : 'none';
        if (hit) n++;
    });
    document.getElementById('cari-kosong').classList.toggle('hidden', n > 0);
});

function openCatat(id, nama, skor) {
    document.getElementById('cat-id-siswa').value = id;
    document.getElementById('cat-nama').textContent = 'Catat: ' + nama;
    document.getElementById('cat-skor').textContent = skor;
    document.getElementById('form-catat').reset();
    document.getElementById('cat-id-siswa').value = id;
    twModalShow('#modalCatat');
}
window.openCatat = openCatat;

document.getElementById('form-catat').addEventListener('submit', function (e) {
    e.preventDefault();
    const ket = document.getElementById('cat-ket').value.trim();
    if (!document.getElementById('cat-kategori').value) { Swal.fire('Perhatian', 'Pilih kategori.', 'warning'); return; }
    if (ket.length < 5) { Swal.fire('Perhatian', 'Keterangan minimal 5 karakter.', 'warning'); return; }
    const btn = document.getElementById('btn-catat');
    btn.disabled = true;
    const fd = new FormData(this);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    fetch('<?= site_url('guru/poin-siswa/record') ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (res && res.status) {
                twModalHide('#modalCatat');
                Swal.fire('Berhasil', (res.message || 'Tersimpan.') + (res.data != null ? ' Skor kini: ' + res.data : ''), 'success')
                    .then(() => location.reload());
            } else {
                Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan.', 'error');
            }
        })
        .catch(() => { btn.disabled = false; Swal.fire('Error', 'Gagal menghubungi server.', 'error'); });
});
</script>
<?= $this->endSection() ?>
