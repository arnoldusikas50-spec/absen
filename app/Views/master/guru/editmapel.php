<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="fade-in-up p-4 md:p-6 space-y-6">
    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-badge"></i> Penugasan Guru
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($subjudul ?? 'Edit Jabatan Guru') ?> — <?= esc($guru->nama_guru ?? '') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Atur jabatan, wali kelas, dan mata pelajaran yang diampu · TP <?= esc($tp_active->tahun ?? '-') ?> · <?= esc($smt_active->nama_smt ?? $smt_active->smt ?? '-') ?></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('dataguru') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" form="editjabatan" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20">
                <i class="bi bi-save"></i> Simpan
            </button>
        </div>
    </div>

        <div class="flex flex-wrap gap-4">
            <div class="w-full">
                <div class="alert-tw alert-info">
                    <i class="bi bi-info-circle-fill mr-2"></i>
                    <span>Tentukan jabatan dan mata pelajaran yang diampu oleh <b><?= esc($guru->nama_guru ?? '-') ?></b>
                        pada tahun pelajaran <b><?= esc($tp_active->tahun ?? '-') ?></b>
                        semester <b><?= esc($smt_active->nama_smt ?? $smt_active->smt ?? '-') ?></b>.
                        Mapel yang sudah terpasang di jadwal tidak bisa dilepas dari guru ini; tugaskan guru pengganti
                        lewat halaman Edit Jabatan guru tersebut atau hapus slot jadwalnya lebih dulu.</span>
                </div>
            </div>

            <div class="w-full">
                <form id="editjabatan">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_guru" value="<?= (int) $guru->id_guru ?>">
                    <div class="card-tw"><div class="card-tw-header">
                            <h6 class="font-bold text-slate-700">Edit Jabatan & Mapel</h6>
                            <div class="flex gap-2 ml-auto">
                                <a href="<?= site_url('dataguru') ?>" class="btn-tw btn-tw-sm btn-light">
                                    <i class="bi bi-arrow-left mr-1"></i>
                                    <span class="hidden sm:inline">Kembali</span>
                                </a>
                                <button type="submit" class="btn-tw btn-tw-sm btn-primary">
                                    <i class="bi bi-save mr-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                        <div class="card-tw-body">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                <div class="lg:col-span-4">
                                    <div class="border rounded p-3 mb-6 bg-slate-50">
                                        <h6 class="font-bold mb-6"><i class="bi bi-briefcase mr-2"></i>Jabatan Utama
                                        </h6>
                                        <div class="mb-6">
                                            <label class="form-label-tw">Jabatan</label>
                                            <?= form_dropdown('level', $levels, $guru->id_level, 'id="level" class="form-select-tw" required') ?>
                                        </div>
                                        <div id="div-wali" class="<?= $guru->id_level == '4' ? '' : 'hidden' ?>">
                                            <label class="form-label-tw">Wali
                                                Kelas</label>
                                            <?= form_dropdown('kelas_wali', $kelass, $guru->id_kelas, 'id="kelas_wali" class="form-select-tw"') ?>
                                        </div>
                                    </div>

                                    <div class="border rounded p-3">
                                        <h6 class="font-bold mb-6"><i class="bi bi-book mr-2"></i>Mata Pelajaran &
                                            Ekstra</h6>
                                        <div class="mb-6">
                                            <label class="form-label-tw">Mata Pelajaran
                                                yang Diampu</label>
                                            <?= form_dropdown('mapel[]', $mapels, array_column($guru->mapel_kelas, 'id_mapel'), 'id="mapel" class="form-control-tw select2" multiple="multiple" data-placeholder="Ketik untuk cari mapel..."') ?>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label-tw">Ekstrakurikuler
                                                yang Diampu</label>
                                            <?= form_dropdown('ekstra[]', $ekskul, array_column($guru->ekstra_kelas, 'id_ekstra'), 'id="ekstra" class="form-control-tw select2" multiple="multiple" data-placeholder="Ketik untuk cari ekstra..."') ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="lg:col-span-8">
                                    <div class="border rounded p-3 h-full">
                                        <h6 class="font-bold mb-6"><i class="bi bi-ui-checks-grid mr-2"></i>Mapping
                                            Kelas</h6>
                                        <div id="mapping-container">
                                            <div id="input-mapel" class="mb-6"></div>
                                            <div id="input-ekstra"></div>
                                            <div id="empty-mapping" class="text-center text-slate-400 py-12">
                                                <i class="bi bi-layers text-5xl block mb-2"></i>
                                                <span class="text-sm">Pilih mata pelajaran atau ekstra di samping untuk mengatur
                                                    kelas.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    const kelass = <?= json_encode($kelass) ?>;
    const initialMapels = Array.isArray(<?= json_encode($guru->mapel_kelas) ?>) ? <?= json_encode($guru->mapel_kelas) ?> : [];
    const initialEkstras = Array.isArray(<?= json_encode($guru->ekstra_kelas) ?>) ? <?= json_encode($guru->ekstra_kelas) ?> : [];

    // Key objek angka selalu dienumerasi naik oleh JavaScript, sehingga urutan
    // nama kelas dari server harus dipulihkan secara eksplisit.
    const kelasCollator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
    function sortKelasEntries(map) {
        return Object.entries(map || {}).sort((a, b) => {
            const byName = kelasCollator.compare(String(a[1] ?? ''), String(b[1] ?? ''));
            return byName !== 0 ? byName : (Number(a[0]) - Number(b[0]));
        });
    }

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function createMappingRow(type, id, name, selected = []) {
        const title = type === 'mapel' ? 'Mapel' : 'Ekstra';
        const prefix = type === 'mapel' ? 'kelasmapel' : 'kelasekstra';
        const namePrefix = type === 'mapel' ? 'nama_mapel' : 'nama_ekstra';
        const selSet = new Set((selected || []).map(String));

        let options = '';
        sortKelasEntries(kelass).forEach(([val, label]) => {
            const isSelected = selSet.has(String(val)) ? 'selected' : '';
            options += `<option value="${escHtml(val)}" ${isSelected}>${escHtml(label)}</option>`;
        });

        return `
            <div class="card-tw mapping-row mb-3" id="row-${type}-${escHtml(id)}">
                <div class="card-tw-body">
                    <label class="form-label-tw">${escHtml(title)}: ${escHtml(name)}</label>
                    <input type="hidden" name="${namePrefix}${escHtml(id)}" value="${escHtml(name)}">
                    <select name="${prefix}${escHtml(id)}[]" class="form-control-tw select2" multiple="multiple" data-placeholder="Pilih Kelas">
                        ${options}
                    </select>
                </div>
            </div>
        `;
    }

    function initRowSelect2(scope) {
        $(scope).find('select.select2').each(function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({ width: '100%' });
            }
        });
    }

        if ($.fn.select2) {
            $('#mapel.select2, #ekstra.select2').select2({ width: '100%' });
        }

        function updateEmptyState() {
            if ($('.mapping-row').length === 0) {
                $('#empty-mapping').show();
            } else {
                $('#empty-mapping').hide();
            }
        }

        // Handle initial data
        initialMapels.forEach(m => {
            if (!m || m.id_mapel == null) return;
            const selected = Array.isArray(m.kelas_mapel) ? m.kelas_mapel.map(k => k.kelas) : [];
            $('#input-mapel').append(createMappingRow('mapel', m.id_mapel, m.nama_mapel || '', selected));
        });

        initialEkstras.forEach(e => {
            if (!e || e.id_ekstra == null) return;
            const selected = Array.isArray(e.kelas_ekstra) ? e.kelas_ekstra.map(k => k.kelas) : [];
            $('#input-ekstra').append(createMappingRow('ekstra', e.id_ekstra, e.nama_ekstra || '', selected));
        });

        initRowSelect2('#mapping-container');
        updateEmptyState();

        // Level change handling
        $('#level').change(function () {
            if ($(this).val() == '4') {
                $('#div-wali').removeClass('hidden');
            } else {
                $('#div-wali').addClass('hidden');
                const kw = $('#kelas_wali');
                if (kw.find('option[value=""]').length) kw.val('');
                else kw.prop('selectedIndex', 0);
            }
        });

        // Mapel change handling (delegated: select2 events)
        $('#mapel').on('select2:select', function (e) {
            const data = e.params.data;
            $('#input-mapel').append(createMappingRow('mapel', data.id, data.text));
            initRowSelect2('#input-mapel');
            updateEmptyState();
        });

        $('#mapel').on('select2:unselect', function (e) {
            $(`#row-mapel-${CSS.escape(String(e.params.data.id))}`).remove();
            updateEmptyState();
        });

        // Ekstra change handling
        $('#ekstra').on('select2:select', function (e) {
            const data = e.params.data;
            $('#input-ekstra').append(createMappingRow('ekstra', data.id, data.text));
            initRowSelect2('#input-ekstra');
            updateEmptyState();
        });

        $('#ekstra').on('select2:unselect', function (e) {
            $(`#row-ekstra-${CSS.escape(String(e.params.data.id))}`).remove();
            updateEmptyState();
        });

        // Fallback bila Select2 gagal dimuat: sinkronkan mapping via change biasa
        $('#mapel, #ekstra').on('change', function () {
            if ($.fn.select2) return;
            const isMapel = this.id === 'mapel';
            const type = isMapel ? 'mapel' : 'ekstra';
            const chosen = Array.from(this.selectedOptions).map(o => ({ id: o.value, text: o.text }));
            const box = isMapel ? '#input-mapel' : '#input-ekstra';
            $(box).empty();
            chosen.forEach(c => $(box).append(createMappingRow(type, c.id, c.text)));
            updateEmptyState();
        });

        // Form Submission
        $('#editjabatan').submit(function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Simpan?',
                text: 'Jabatan dan mapel guru akan diperbarui',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            }).then(res => {
                if (res.isConfirmed) {
                    const $btn = $(form).find('button[type="submit"]');
                    const orig = $btn.html();
                    $btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span> Menyimpan...');
                    $.ajax({
                        url: '<?= site_url("dataguru/saveJabatan") ?>',
                        type: 'POST',
                        data: $(form).serialize(),
                        dataType: 'json',
                        success: (res) => {
                            $btn.prop('disabled', false).html(orig);
                            if (res && res.status) {
                                Swal.fire('Berhasil', res.message || 'Tersimpan.', 'success');
                            } else {
                                Swal.fire('Gagal', (res && res.message) || 'Gagal menyimpan.', 'error');
                            }
                        },
                        error: () => {
                            $btn.prop('disabled', false).html(orig);
                            Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>