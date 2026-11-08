<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-amber-100 text-amber-600 rounded-xl shadow-sm">
                    <i class="bi bi-envelope-open text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Surat Panggilan Orang Tua') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                Surat otomatis ke orang tua/wali siswa yang kehadirannya sudah melewati ambang.
                Terbit otomatis setiap tanggal 7, 14, 21, dan akhir bulan.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi/siswa/rekap') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50"
               title="Sumber angka alpha: rekap presensi siswa">
                <i class="bi bi-file-earmark-spreadsheet mr-1"></i> Rekap Siswa
            </a>
            <a href="<?= site_url('admin/presensi/surat-teguran') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50"
               title="Surat teguran untuk guru">
                <i class="bi bi-envelope-exclamation mr-1"></i> Surat Teguran Guru
            </a>
            <button type="button" onclick="hitungUlangPanggilan()"
               class="px-3 py-1.5 rounded-lg bg-amber-500 border border-amber-500 text-white text-xs font-bold hover:bg-amber-600"
               title="Hitung ulang angka surat aktif dari data absen saat ini">
                <i class="bi bi-arrow-repeat mr-1"></i> Hitung Ulang
            </button>
            <button type="button" onclick="kirimNotifikasiPanggilan(<?= (int) $bulan ?>, <?= (int) $tahun ?>)"
               class="px-3 py-1.5 rounded-lg bg-blue-600 border border-blue-600 text-white text-xs font-bold hover:bg-blue-700"
               title="Kirim notifikasi WA/Telegram ke wali kelas untuk surat panggilan periode ini">
                <i class="bi bi-send mr-1"></i> Kirim Notifikasi
            </button>
        </div>
    </div>
</div>

<div class="content-area space-y-6">

    <?php if (!$adaTabel): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-base mt-0.5"></i>
            <div class="text-xs text-amber-800">
                <p class="font-bold m-0">Tabel surat panggilan belum dibuat</p>
                <p class="m-0 mt-1">Jalankan <code class="bg-amber-100 px-1 rounded">php spark migrate</code> untuk membuat tabel
                    <code class="bg-amber-100 px-1 rounded">absensi_surat_panggilan</code>. Sampai itu selesai, surat tidak bisa terbit.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Dipakai dua kali: filter di atas dan teks penjelasan di bawah. Scope
    // view, jadi keduanya lihat array yang sama.
    $blnIdx = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
?>
    <!-- Filter periode -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="px-4 py-3 border-b border-slate-100">
            <form action="" method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Bulan</label>
                    <select name="bulan" class="form-select-tw text-xs py-1.5">
                        <?php foreach ($blnIdx as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $k == $bulan ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Tahun</label>
                    <select name="tahun" class="form-select-tw text-xs py-1.5">
                        <?php for ($y = (int) date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-tw btn-tw-sm btn-primary">
                        <i class="bi bi-search mr-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 border-b border-slate-100">
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 m-0">Surat Aktif</p>
                <p class="text-2xl font-bold text-slate-800 m-0 mt-1">
                    <?= count(array_filter($letters, static fn($l) => ($l->status ?? 'aktif') === 'aktif')) ?>
                </p>
            </div>
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 m-0">Dibatalkan</p>
                <p class="text-2xl font-bold text-slate-400 m-0 mt-1">
                    <?= count(array_filter($letters, static fn($l) => ($l->status ?? 'aktif') === 'dibatalkan')) ?>
                </p>
            </div>
            <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-600 m-0">Belum Ada Surat</p>
                <p class="text-2xl font-bold text-emerald-700 m-0 mt-1"><?= count($kandidat) ?></p>
            </div>
            <div class="bg-amber-50 rounded-xl border border-amber-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-amber-600 m-0">Ambang</p>
                <p class="text-2xl font-bold text-amber-700 m-0 mt-1">
                    <?= implode('/', \App\Services\SuratPanggilanService::AMBANG) ?>
                </p>
                <p class="text-[10px] text-amber-600 m-0">hari alpha</p>
            </div>
        </div>

        <!-- Tabel surat -->
        <div class="p-0">
            <div class="table-responsive-tw">
                <table class="table-tw">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th>No. Surat</th>
                            <th>Nama Siswa</th>
                            <th>NIS / NISN</th>
                            <th>Penerima</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Alpha</th>
                            <th class="text-center" width="8%">Alpha<br>Bulan Ini</th>
                            <th class="text-center" width="8%">Teguran</th>
                            <th class="text-center">Terlambat</th>
                            <th>Terbit</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($letters)): ?>
                            <tr>
                                <td colspan="13" class="text-center py-8 text-slate-400">
                                    Belum ada surat panggilan untuk periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1;
                            foreach ($letters as $row):
                                $batal = ($row->status ?? 'aktif') === 'dibatalkan';
                                $level = (int) ($row->level ?? 3); ?>
                                <tr class="<?= $batal ? 'opacity-60' : '' ?>">
                                    <td class="text-center"><?= $i++ ?></td>
                                    <td>
                                        <span class="badge-tw badge-secondary font-mono text-[10px]">
                                            <?= esc(\App\Libraries\SuratNomor::build(
                                                (int) ($row->nomor_urut ?? 0),
                                                (int) ($row->bulan ?? 0),
                                                (int) ($row->tahun ?? 0),
                                                $setting,
                                                $level,
                                                !empty($row->created_at) ? date('d-m-Y', strtotime($row->created_at)) : null
                                            )) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-bold text-slate-800"><?= esc($row->nama ?? '-') ?></div>
                                        <?php if (!empty($row->kelas_awal)): ?>
                                            <div class="text-[10px] text-slate-400"><?= esc($row->kelas_awal) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-xs">
                                        <?= esc($row->nis ?? '-') ?>
                                        <?php if (!empty($row->nisn)): ?>
                                            <div class="text-[10px] text-slate-400"><?= esc($row->nisn) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-xs">
                                        <?= esc($row->penerima ?: '—') ?>
                                        <?php if (!empty($row->nohp_penerima)): ?>
                                            <div class="text-[10px] text-slate-400"><?= esc($row->nohp_penerima) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($level >= 7): ?>
                                            <span class="badge-tw badge-danger">III (7)</span>
                                        <?php elseif ($level >= 5): ?>
                                            <span class="badge-tw badge-warning">II (5)</span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-info">I (3)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-bold text-red-600"><?= (int) ($row->jumlah_alpha ?? 0) ?>
                                        <?php
                                        $liveA = isset($alpha_live[(int) $row->id_siswa]) ? (int) $alpha_live[(int) $row->id_siswa]['alpha'] : null;
                                        if (!$batal && $liveA !== null && $liveA !== (int) ($row->jumlah_alpha ?? 0)): ?>
                                            <div class="text-[10px] font-normal text-amber-600" title="Angka dari data absen saat ini — klik Hitung Ulang untuk menyelaraskan">real: <?= $liveA ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Alpha bulan ini (live) sebagai badge -->
                                    <td class="text-center">
                                        <?php
                                        $liveA = isset($alpha_live[(int) $row->id_siswa]) ? (int) $alpha_live[(int) $row->id_siswa]['alpha'] : 0;
                                        $adaTeguran = isset($teguranSiswa[(int) $row->id_siswa]);
                                        $badgeClass = $adaTeguran ? 'badge-danger' : 'badge-secondary';
                                        if ($liveA > 0): ?>
                                            <span class="badge-tw <?= $badgeClass ?>"
                                                title="<?= $adaTeguran ? 'Wali kelas sudah terima surat teguran bulan ini' : '' ?>">α <?= $liveA ?></span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-light text-slate-400">α 0</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Indikator surat teguran wali kelas -->
                                    <td class="text-center">
                                        <?php if ($adaTeguran): ?>
                                            <span class="badge-tw badge-danger" title="Wali kelas sudah terima surat teguran bulan ini">
                                                <i class="bi bi-shield-exclamation mr-0.5"></i>Teguran
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-light text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-amber-600"><?= (int) ($row->jumlah_terlambat ?? 0) ?></td>
                                    <td class="text-xs">
                                        <?= !empty($row->created_at) ? date('d-m-Y H:i', strtotime($row->created_at)) : '-' ?>
                                        <div class="text-[10px] text-slate-400">
                                            <?= ($row->sumber ?? 'cron') === 'manual' ? 'Input manual' : 'Otomatis' ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($batal): ?>
                                            <span class="badge-tw badge-secondary" title="<?= esc($row->keterangan_batal ?? 'Dibatalkan') ?>">
                                                <i class="bi bi-x-circle mr-1"></i>Dibatalkan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-success">
                                                <i class="bi bi-check2-circle mr-1"></i>Aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex gap-1 justify-center">
                                            <?php if (!$batal): ?>
                                                <a href="<?= site_url('admin/presensi/surat-panggilan/' . (int) $row->id . '/pdf') ?>"
                                                   class="btn-tw btn-tw-sm btn-success"
                                                   target="_blank"
                                                   title="Cetak PDF surat panggilan">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                                <button type="button"
                                                   class="btn-tw btn-tw-sm btn-light border text-red-600"
                                                   onclick="batalkanPanggilan(<?= (int) $row->id ?>)"
                                                   title="Batalkan surat ini">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kandidat: siswa yang perlu surat tapi belum punya -->
    <?php if (!empty($kandidat)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h6 class="font-bold text-slate-700 mb-0">
                    <i class="bi bi-person-exclamation mr-2 text-amber-500"></i>
                    Siswa yang Belum Dapat Surat
                </h6>
                <p class="text-[11px] text-slate-500 m-0 mt-0.5">
                    Alpha bulan <?= esc($blnIdx[$bulan]) ?> <?= (int) $tahun ?> sudah melewati ambang
                    <?= \App\Services\SuratPanggilanService::AMBANG[0] ?> hari, tapi belum ada surat terbit.
                </p>
            </div>
            <span class="badge-tw badge-warning"><?= count($kandidat) ?> siswa</span>
        </div>

        <div class="table-responsive-tw">
            <table class="table-tw">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th class="text-center">Alpha</th>
                        <th class="text-center">Terlambat</th>
                        <th class="text-center">Tercapai</th>
                        <th class="text-center" width="28%">Buat Surat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $j = 1;
                    foreach ($kandidat as $k): ?>
                        <tr>
                            <td class="text-center"><?= $j++ ?></td>
                            <td class="font-bold text-slate-800"><?= esc($k['nama']) ?></td>
                            <td class="text-xs"><?= esc($k['nis'] ?: '—') ?></td>
                            <td class="text-center font-bold text-red-600"><?= (int) $k['alpha'] ?></td>
                            <td class="text-center text-amber-600"><?= (int) $k['terlambat'] ?></td>
                            <td class="text-center">
                                <?php $lv = (int) $k['level']; ?>
                                <?php if ($lv >= 7): ?>
                                    <span class="badge-tw badge-danger">III (7)</span>
                                <?php elseif ($lv >= 5): ?>
                                    <span class="badge-tw badge-warning">II (5)</span>
                                <?php else: ?>
                                    <span class="badge-tw badge-info">I (3)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="flex gap-1 justify-center items-center">
                                    <select id="lvl-<?= (int) $k['id_siswa'] ?>"
                                            class="form-select-tw text-[11px] py-1 w-auto">
                                        <?php foreach (\App\Services\SuratPanggilanService::AMBANG as $lv): ?>
                                            <option value="<?= $lv ?>" <?= $lv === (int) $k['level'] ? 'selected' : '' ?>>
                                                <?= esc(\App\Services\SuratPanggilanService::LABEL_LEVEL[$lv]) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button"
                                        class="btn-tw btn-tw-sm btn-primary"
                                        onclick="buatPanggilan(<?= (int) $k['id_siswa'] ?>, '<?= esc(addslashes($k['nama'])) ?>')"
                                        title="Buat surat panggilan untuk siswa ini">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Penjelasan -->
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-info-circle-fill text-slate-400 text-base mt-0.5"></i>
            <div class="text-xs text-slate-600 space-y-1">
                <p class="font-bold m-0 text-slate-700">Cara kerja surat panggilan</p>
                <p class="m-0">
                    <b>Sumber angka</b> — dihitung dari tabel presensi siswa, hanya hari efektif
                    (bukan hari libur minggu, nasional, atau jadwal kerja libur). Hari yang belum
                    datang tidak dihitung, jadi nomor ini tidak akan melompat di awal bulan.
                </p>
                <p class="m-0">
                    <b>Alpha</b> — hari efektif tanpa baris absensi sama sekali, atau berstatus
                    ALPA / TANPA KETERANGAN. Izin dan sakit tidak dihitung.
                </p>
                <p class="m-0">
                    <b>Terbit otomatis</b> — tanggal 7, 14, 21, dan akhir bulan. Satu siswa mendapat
                    satu surat per evaluasi, yaitu tingkat terberat yang sudah dilampaui.
                </p>
                <p class="m-0">
                    <b>Angka di dalam surat adalah foto saat surat terbit</b>, bukan dihitung ulang.
                    Dicetak ulang tahun depan pun angkanya sama seperti waktu orang tua membacanya —
                    kecuali admin menekan <b>Hitung Ulang</b>, yang menyelaraskan angka surat aktif
                    dengan data absen terkini (surat yang syaratnya sudah gugur ikut dibatalkan otomatis).
                </p>
                <p class="m-0">
                    <b>Pembatalan</b> — surat dibatalkan, bukan dihapus, supaya arsip keberadaannya
                    tetap bisa dibuktikan.
                </p>
            </div>
        </div>
    </div>

</div><!-- /content-area -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    const csrfName  = '<?= csrf_token() ?>';
    const csrfHash  = '<?= csrf_hash() ?>';
    const bulan     = '<?= (int) $bulan ?>';
    const tahun     = '<?= (int) $tahun ?>';

    function toast(pesan, ok) {
        if (typeof Swal === 'undefined') {
            alert(pesan);
            return;
        }
        Swal.fire({
            icon: ok ? 'success' : 'error',
            title: ok ? 'Berhasil' : 'Gagal',
            text: pesan,
            timer: ok ? 2500 : undefined,
            showConfirmButton: !ok
        });
    }

    // ── Buat surat manual ──────────────────────────────────────────
    window.buatPanggilan = function (idSiswa, nama) {
        const select = document.getElementById('lvl-' + idSiswa);
        const level = select ? select.value : '3';

        const jalankan = function (keterangan) {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-panggilan/buat') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_test_name: csrfHash,
                    id_siswa: idSiswa,
                    bulan: bulan,
                    tahun: tahun,
                    level: level,
                    keterangan: keterangan || ''
                },
                headers: { 'X-CSRF-TOKEN': csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.status) {
                        toast(res.message || 'Surat panggilan dibuat.', true);
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        toast(res.message || 'Gagal membuat surat.', false);
                    }
                },
                error: function () {
                    toast('Gagal menghubungi server.', false);
                }
            });
        };

        if (typeof Swal === 'undefined') {
            if (confirm('Buat surat panggilan untuk ' + nama + '?')) {
                jalankan('');
            }
            return;
        }

        Swal.fire({
            title: 'Buat Surat Panggilan?',
            html: 'Untuk <strong>' + nama + '</strong><br><br>'
                + '<label class="block text-left text-xs text-slate-600 mb-1">Alasan (opsional, tersimpan di data)</label>'
                + '<input id="inputKeterangan" class="swal2-input" style="width:100%" placeholder="mis. Sudah ditindaklanjuti via telepon">',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Buat',
            cancelButtonText: 'Batal',
            preConfirm: function () {
                jalankan((document.getElementById('inputKeterangan') || {}).value || '');
                return true;
            }
        });
    };

    // ── Hitung ulang dari data absen real ──────────────────────────
    window.hitungUlangPanggilan = function () {
        const jalankan = function () {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-panggilan/hitung-ulang') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_test_name: csrfHash,
                    bulan: bulan,
                    tahun: tahun
                },
                headers: { 'X-CSRF-TOKEN': csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.status) {
                        toast(res.message || 'Hitung ulang selesai.', true);
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        toast(res.message || 'Gagal menghitung ulang.', false);
                    }
                },
                error: function () {
                    toast('Gagal menghubungi server.', false);
                }
            });
        };

        if (typeof Swal === 'undefined') {
            if (confirm('Hitung ulang angka surat aktif periode ini dari data absen saat ini?')) {
                jalankan();
            }
            return;
        }

        Swal.fire({
            title: 'Hitung Ulang Surat?',
            html: 'Angka <strong>alpha & terlambat</strong> surat aktif periode ini akan diselaraskan '
                + 'dengan data absen saat ini. Surat yang syaratnya sudah gugur akan '
                + '<strong>dibatalkan otomatis</strong>. Level dan penerima tidak diubah.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hitung Ulang',
            cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed || r.value) {
                jalankan();
            }
        });
    };

    // ── Kirim notifikasi WA/Telegram ke wali kelas ─────────────────
    window.kirimNotifikasiPanggilan = function (bln, thn) {
        const jalankan = function () {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-panggilan/notifikasi') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_test_name: csrfHash,
                    bulan: bln,
                    tahun: thn
                },
                headers: { 'X-CSRF-TOKEN': csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.status) {
                        toast(res.message || 'Notifikasi terkirim.', true);
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        toast(res.message || 'Gagal mengirim notifikasi.', false);
                    }
                },
                error: function () {
                    toast('Gagal menghubungi server.', false);
                }
            });
        };

        if (typeof Swal === 'undefined') {
            if (confirm('Kirim notifikasi WA/Telegram ke wali kelas untuk periode ini?')) {
                jalankan();
            }
            return;
        }

        Swal.fire({
            title: 'Kirim Notifikasi Wali Kelas?',
            html: 'Notifikasi akan dikirim ke wali kelas untuk semua surat panggilan '
                + 'yang sudah terbit pada bulan <strong>' + bln + '</strong>/<strong>' + thn + '</strong>.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed || r.value) {
                jalankan();
            }
        });
    };

    // ── Batalkan surat ─────────────────────────────────────────────
    window.batalkanPanggilan = function (id) {
        const jalankan = function (alasan) {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-panggilan/batal') ?>/' + id,
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_test_name: csrfHash,
                    alasan: alasan || ''
                },
                headers: { 'X-CSRF-TOKEN': csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.status) {
                        toast(res.message || 'Surat dibatalkan.', true);
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        toast(res.message || 'Gagal membatalkan.', false);
                    }
                },
                error: function () {
                    toast('Gagal menghubungi server.', false);
                }
            });
        };

        if (typeof Swal === 'undefined') {
            if (confirm('Batalkan surat panggilan ini?')) {
                jalankan('');
            }
            return;
        }

        Swal.fire({
            title: 'Batalkan Surat Panggilan?',
            html: '<label class="block text-left text-xs text-slate-600 mb-1">Alasan pembatalan (opsional)</label>'
                + '<input id="inputAlasan" class="swal2-input" style="width:100%" placeholder="mis. Sudah ada izin resmi">',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Kembali',
            preConfirm: function () {
                jalankan((document.getElementById('inputAlasan') || {}).value || '');
                return true;
            }
        });
    };
})();
</script>
<?= $this->endSection() ?>