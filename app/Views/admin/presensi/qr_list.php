<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-qr-code-scan"></i> QR Event Presensi
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'QR Code Presensi') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Buat QR acara, tampilkan token rotasi di layar, dan pantau peserta.</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="<?= site_url('dashboard') ?>" class="hover:text-blue-600 font-semibold no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <a href="<?= site_url('admin/presensi') ?>" class="hover:text-blue-600 font-semibold no-underline">Presensi</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-blue-600">QR Code</span>
        </nav>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-calendar-event text-sm"></i>
                    </span>
                    <h2 class="text-sm font-extrabold text-slate-900">Daftar Acara / QR Presensi</h2>
                    <?php if (!empty($events)): ?>
                    <span class="badge-tw badge-secondary rounded-full text-[11px]"><?= count($events) ?> acara</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="qr-search" placeholder="Cari acara / lokasi..." autocomplete="off"
                               class="w-full md:w-56 pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="button" class="btn-tw btn-tw-sm btn-primary" id="btn-open-generate">
                        <i class="bi bi-plus-lg mr-1"></i> Buat QR Baru
                    </button>
                </div>
            </div>
            <div class="p-5">
                <div class="table-responsive-tw"><table class="table-tw">
                        <thead class="bg-slate-50 text-slate-500 text-sm uppercase">
                            <tr>
                                <th>Acara</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Lokasi</th>
                                <th>Mode</th>
                                <th>Status</th>
                                <th>Peserta</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                            <tr><td colspan="8" class="text-center text-slate-400 py-8">Belum ada acara QR</td></tr>
                            <?php else: ?>
                            <?php foreach ($events as $e):
                                $isPast = ($e->tanggal ?? '') !== '' && $e->tanggal < ($today ?? date('Y-m-d'));
                                $jmlPeserta = (int) ($pesertaCounts[(int) $e->id] ?? 0);
                            ?>
                            <tr class="qr-row" data-search="<?= esc(strtolower(trim(($e->nama_acara ?? '') . ' ' . ($e->lokasi ?? ''))), 'attr') ?>">
                                <td class="font-bold">
                                    <?= esc($e->nama_acara) ?>
                                    <?php if ($isPast): ?>
                                    <span class="badge-tw badge-secondary rounded-full text-[10px] ml-1" title="Tanggal acara sudah lewat">Berlalu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($e->tanggal)) ?></td>
                                <td class="whitespace-nowrap font-mono text-xs"><?= substr($e->jam_mulai, 0, 5) ?> - <?= substr($e->jam_selesai, 0, 5) ?></td>
                                <td><?= esc($e->lokasi ?? '-') ?></td>
                                <td>
                                    <?php $qrMode = $e->mode ?? (!empty($e->secret) ? 'rotating' : 'static'); ?>
                                    <?php if ($qrMode === 'rotating'): ?>
                                    <span class="badge-tw badge-success rounded-full text-[10px]" title="Token 6 digit berganti tiap <?= (int) ($e->interval_detik ?? 60) ?> detik">Rotasi <?= (int) ($e->interval_detik ?? 60) ?>s</span>
                                    <?php else: ?>
                                    <span class="badge-tw badge-secondary rounded-full text-[10px]" title="Token tetap — aman untuk cetak/poster">Statis</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <label class="inline-flex cursor-pointer items-center gap-2" title="Aktif / nonaktif event"><input type="checkbox" class="peer sr-only toggle-qr" data-id="<?= $e->id ?>" <?= $e->active ? 'checked' : '' ?>><span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500/40 after:content-[''] after:absolute after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-4"></span></label>
                                </td>
                                <td>
                                    <a href="<?= base_url("admin/presensi/qr/rekap/{$e->id}") ?>" class="btn-tw btn-tw-sm btn-outline-info" title="Lihat peserta">
                                        <i class="bi bi-people mr-1"></i><?= $jmlPeserta ?>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">
                                    <button type="button" class="btn-tw btn-tw-sm btn-outline-secondary btn-show-qr" data-id="<?= $e->id ?>" data-mode="<?= esc($e->mode ?? 'static') ?>" data-url="<?= base_url("qr/{$e->token}") ?>" data-title="<?= esc($e->nama_acara, 'attr') ?>" title="Tampilkan QR">
                                        <i class="bi bi-qr-code"></i>
                                    </button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-del-qr" data-id="<?= $e->id ?>" data-nama="<?= esc($e->nama_acara, 'attr') ?>" data-peserta="<?= $jmlPeserta ?>" title="Hapus event">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach ?>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>
<div id="modalGenerate" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white">
            <form id="formGenerate">
                <div class="modal-header-tw">
                    <h5 class="font-bold text-slate-800">Buat QR Code Baru</h5>
                    <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body-tw">
                    <div class="mb-6">
                        <label class="form-label-tw">Nama Acara <span class="text-red-500">*</span></label>
                        <input type="text" class="form-control-tw" name="nama_acara" placeholder="Contoh: Rapat Bulanan" required minlength="3" maxlength="255">
                    </div>
                    <div class="mb-6">
                        <label class="form-label-tw">Tanggal Acara <span class="text-red-500">*</span></label>
                        <input type="date" class="form-control-tw" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-6">
                        <div class="">
                            <label class="form-label-tw">Jam Mulai</label>
                            <?php $qrMulai = substr(trim((string) ($setting->qr_scan_mulai ?? '')), 0, 5); ?>
                            <input type="time" class="form-control-tw" name="jam_mulai" value="<?= esc($qrMulai !== '' && $qrMulai !== '00:00' ? $qrMulai : '07:00') ?>">
                        </div>
                        <div class="">
                            <label class="form-label-tw">Jam Selesai</label>
                            <?php $qrSelesai = substr(trim((string) ($setting->qr_scan_selesai ?? '')), 0, 5); ?>
                            <input type="time" class="form-control-tw" name="jam_selesai" value="<?= esc($qrSelesai !== '' && $qrSelesai !== '00:00' ? $qrSelesai : '16:00') ?>">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="form-label-tw">Lokasi <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" class="form-control-tw" name="lokasi" maxlength="255" placeholder="Aula / Ruang Guru / Online">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-6">
                        <div class="">
                            <label class="form-label-tw">Mode QR</label>
                            <select name="mode" id="qrModeSelect" class="form-select-tw">
                                <option value="rotating">Rotasi (layar — token ganti otomatis)</option>
                                <option value="static">Statis (cetak/poster)</option>
                            </select>
                        </div>
                        <div class="" id="qrIntervalWrap">
                            <label class="form-label-tw">Interval rotasi</label>
                            <select name="interval_detik" class="form-select-tw">
                                <option value="30">30 detik</option>
                                <option value="60" selected>60 detik</option>
                                <option value="120">120 detik</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert-tw alert-info">Mode rotasi wajib ditampilkan di layar (proyektor/TV) — QR cetak akan kedaluwarsa dalam hitungan detik.</div>
                </div>
                <div class="modal-footer-tw">
                    <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-tw btn-primary"><i class="bi bi-qr-code mr-1"></i> Generate QR</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="modalQr" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white">
            <div class="modal-body-tw">
                <h6 class="font-bold mb-6" id="qrTitle"></h6>
                <div id="qrContainer" class="mb-6"></div>
                <div id="qrCountdown" class="text-sm text-slate-400 mb-6"></div>
                <button type="button" class="btn-tw btn-tw-sm btn-outline-primary" id="btn-print-qr"><i class="bi bi-printer mr-1"></i> Cetak</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
$(document).ready(function() {
    let qrInstance = null;
    let qrTimer = null;
    function renderQr(url) {
        $('#qrContainer').empty();
        try {
            qrInstance = new QRCode(document.getElementById('qrContainer'), { text: url, width: 200, height: 200 });
        } catch (e) {
            $('#qrContainer').html('<p class="text-sm text-red-500">Gagal merender QR. Buka tautan manual:<br><a class="text-blue-600 underline break-all" target="_blank" href="' + $('<div>').text(url).html() + '">' + $('<div>').text(url).html() + '</a></p>');
        }
    }
    function stopQrTimer() {
        if (qrTimer) { clearInterval(qrTimer); qrTimer = null; }
        $('#qrCountdown').text('');
    }
    // QR rotating: ambil token kini dari server lalu refresh otomatis tiap interval.
    function showQr($btn) {
        const id = $btn.data('id'), mode = $btn.data('mode') || 'static';
        const isRotating = mode === 'rotating';
        $('#qrTitle').text($btn.data('title') || '');
        // QR rotasi kedaluwarsa dalam hitungan detik — sembunyikan tombol cetak agar tak disalahgunakan
        $('#btn-print-qr').toggle(!isRotating);
        stopQrTimer();
        if (!isRotating) {
            renderQr($btn.data('url'));
            $('#qrCountdown').text('');
            AdminUI.modal.open('#modalQr');
            return;
        }
        const tick = function() {
            $.get('<?= site_url('admin/presensi/qr/token') ?>/' + encodeURIComponent(id), function(res) {
                if (res && res.status) {
                    renderQr(res.data.url);
                    $('#qrCountdown').text('Token berganti dalam ±' + ((res.data && res.data.expires_in) != null ? res.data.expires_in : '-') + ' dtk');
                    stopQrTimer();
                    if (res.data.refresh_ms > 0) qrTimer = setInterval(tick, res.data.refresh_ms);
                } else {
                    $('#qrCountdown').text((res && res.message) || 'Gagal memuat token');
                }
            }).fail(function() {
                $('#qrCountdown').text('Gagal menghubungi server');
            });
        };
        tick();
        AdminUI.modal.open('#modalQr');
    }
    $('#modalQr').on('hidden', stopQrTimer);
    function printQr() {
        const w = window.open('', '', 'width=400,height=400');
        if (!w) {
            Swal.fire('Gagal', 'Popup diblokir browser. Izinkan popup untuk mencetak.', 'warning');
            return;
        }
        const html = $('#qrContainer').html();
        w.document.write('<html><body style="text-align:center;padding:20px"><h3></h3>' + html + '</body></html>');
        w.document.querySelector('h3').textContent = $('#qrTitle').text();
        w.document.close();
        w.print();
    }
    // Delegated bindings (ganti onclick inline)
    function syncIntervalVisibility() {
        const isRotating = $('#qrModeSelect').val() !== 'static';
        $('#qrIntervalWrap').toggle(isRotating);
    }
    $(document).on('change', '#qrModeSelect', syncIntervalVisibility);
    // Pencarian client-side (nama/lokasi)
    $(document).on('input', '#qr-search', function () {
        const kw = ($(this).val() || '').toLowerCase().trim();
        $('table.table-tw tbody tr.qr-row').each(function () {
            const visible = !kw || String($(this).data('search') || '').includes(kw);
            $(this).toggle(visible);
        });
    });
    // Hapus event (ditolak server bila sudah ada riwayat scan)
    $(document).on('click', '.btn-del-qr', function () {
        const id = $(this).data('id'), nama = $(this).data('nama') || '';
        const peserta = parseInt($(this).data('peserta') || 0, 10);
        Swal.fire({
            title: 'Hapus event "' + nama + '"?',
            text: peserta > 0
                ? 'Event memiliki ' + peserta + ' riwayat scan dan kemungkinan besar akan ditolak server demi audit.'
                : 'Event yang belum pernah dipindai akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('<?= site_url('admin/presensi/qr/delete') ?>', {
                id: id,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            }, function (res) {
                if (res && res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message, timer: 1300, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus', 'error');
                }
            }).fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
            });
        });
    });
    $(document).on('click', '#btn-open-generate', function () {
        const form = $('#formGenerate').get(0);
        if (form) form.reset();
        syncIntervalVisibility();
        AdminUI.modal.open('#modalGenerate');
    });
    $(document).on('click', '.btn-show-qr', function () { showQr($(this)); });
    $(document).on('click', '#btn-print-qr', printQr);
    window.showQr = function (btn) { showQr($(btn)); };
    window.printQr = printQr;
    $('#formGenerate').submit(function(e) {
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) { form.reportValidity(); return; }
        const btn = $(this).find('button[type="submit"]');
        const orig = btn.html();
        btn.prop('disabled', true).html('<span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full"></span> Membuat...');
        $.ajax({
            url: '<?= site_url('admin/presensi/qr/generate') ?>',
            type: 'POST',
            data: $(this).serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>',
            dataType: 'json',
            success: function(res) {
                if (res && res.status) {
                    AdminUI.modal.close('#modalGenerate');
                    Swal.fire('Berhasil', res.message || 'QR Code dibuat', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal membuat QR', 'error');
                    btn.prop('disabled', false).html(orig);
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
                btn.prop('disabled', false).html(orig);
            }
        });
    });
    $('.toggle-qr').change(function() {
        const $cb = $(this);
        $.post('<?= site_url('admin/presensi/qr/toggle') ?>', {
            id: $cb.data('id'),
            active: $cb.is(':checked') ? 1 : 0,
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        }, function(res) {
            if (res && res.status) {
                Swal.fire({ icon: 'success', title: res.message || 'Status diubah', timer: 1200, showConfirmButton: false });
            } else {
                $cb.prop('checked', !$cb.is(':checked'));
                Swal.fire('Gagal', (res && res.message) || 'Gagal mengubah status', 'error');
            }
        }).fail(function() {
            $cb.prop('checked', !$cb.is(':checked'));
            Swal.fire('Error', 'Terjadi kesalahan koneksi server.', 'error');
        });
    });
});
</script>
<?= $this->endSection() ?>
