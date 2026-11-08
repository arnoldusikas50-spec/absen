<?php

/**
 * Halaman Surat Teguran Guru — /admin/presensi/surat-teguran
 *
 * Disesuaikan agar satu visual language dengan surat_panggilan.php:
 * header berikon dalam kotak, subjudul penjelas, filter + ringkasan dalam
 * satu kartu, tabel dengan badge status, daftar kandidat, lalu blok
 * penjelasan.
 *
 * @var callable $this->extend
 */

$ambang  = \App\Libraries\SuratTeguranLevel::ambang();
$ambangAwal = $ambang[0];

$jumlahAktif = count(array_filter(
    $letters,
    static fn($l) => ($l->status ?? 'aktif') === 'aktif'
));
$jumlahBatal = count(array_filter(
    $letters,
    static fn($l) => ($l->status ?? 'aktif') === 'dibatalkan'
));
$jumlahDibaca = count(array_filter(
    $letters,
    static fn($l) => ($l->status ?? 'aktif') === 'aktif' && ! empty($l->ack_at)
));
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-red-100 text-red-600 rounded-xl shadow-sm">
                    <i class="bi bi-envelope-exclamation text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Surat Teguran Guru') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                Surat resmi untuk guru yang kehadirannya sudah melewati ambang.
                Terbit otomatis setiap tanggal 7, 14, 21, dan akhir bulan.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('admin/presensi') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50"
               title="Kembali ke daftar presensi">
                <i class="bi bi-arrow-left mr-1"></i> Presensi
            </a>
            <a href="<?= site_url('admin/presensi/surat-panggilan') ?>"
               class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-bold no-underline hover:bg-slate-50"
               title="Surat panggilan untuk orang tua/wali siswa">
                <i class="bi bi-envelope-open mr-1"></i> Surat Panggilan Orang Tua
            </a>
        </div>
    </div>
</div>

<div class="content-area space-y-6">

    <?php if (!$adaTabel): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-base mt-0.5"></i>
            <div class="text-xs text-amber-800">
                <p class="font-bold m-0">Tabel surat teguran belum dibuat</p>
                <p class="m-0 mt-1">Jalankan <code class="bg-amber-100 px-1 rounded">php spark migrate</code> untuk membuat tabel
                    <code class="bg-amber-100 px-1 rounded">absensi_surat_teguran</code>. Sampai itu selesai, surat tidak bisa terbit.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Dipakai di filter dan di blok penjelasan. Scope view, jadi keduanya
    // melihat array yang sama.
    $blnIdx = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    ?>
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
                <p class="text-2xl font-bold text-slate-800 m-0 mt-1"><?= $jumlahAktif ?></p>
            </div>
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 m-0">Dibatalkan</p>
                <p class="text-2xl font-bold text-slate-400 m-0 mt-1"><?= $jumlahBatal ?></p>
            </div>
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-blue-600 m-0">Sudah Dibaca Guru</p>
                <p class="text-2xl font-bold text-blue-700 m-0 mt-1"><?= $jumlahDibaca ?></p>
            </div>
            <div class="bg-red-50 rounded-xl border border-red-200 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wide text-red-600 m-0">Ambang</p>
                <p class="text-2xl font-bold text-red-700 m-0 mt-1"><?= implode('/', $ambang) ?></p>
                <p class="text-[10px] text-red-600 m-0">hari alpha</p>
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
                            <th>Nama Guru</th>
                            <th>NIP</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Alpha</th>
                            <th>Terbit</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Dibaca</th>
                            <th class="text-center" width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($letters)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-8 text-slate-400">
                                    Belum ada surat teguran untuk periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1;
                            foreach ($letters as $row):
                                $batal = ($row->status ?? 'aktif') === 'dibatalkan';
                                $level = (int) ($row->level ?? 0); ?>
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
                                        <div class="font-bold text-slate-800"><?= esc($row->nama_guru ?? '-') ?></div>
                                    </td>
                                    <td class="text-xs"><?= esc($row->nip ?: '—') ?></td>
                                    <td class="text-center">
                                        <?php if ($batal): ?>
                                            <span class="badge-tw badge-secondary"
                                                  title="<?= esc($row->keterangan_batal ?? 'Dibatalkan') ?>">
                                                <i class="bi bi-x-circle mr-1"></i>Dibatalkan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-<?= esc(match ($level) {
                                                10 => 'danger',
                                                7  => 'warning',
                                                default => 'info',
                                            }) ?>">
                                                <?= esc(\App\Libraries\SuratTeguranLevel::label($level)) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-bold text-red-600"><?= (int) ($row->jumlah_alpha ?? 0) ?></td>
                                    <td class="text-xs">
                                        <?= !empty($row->created_at) ? date('d-m-Y H:i', strtotime($row->created_at)) : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($batal): ?>
                                            <span class="badge-tw badge-secondary">
                                                <i class="bi bi-x-circle mr-1"></i>Dibatalkan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-success">
                                                <i class="bi bi-check2-circle mr-1"></i>Aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (! empty($row->ack_at)): ?>
                                            <span class="badge-tw badge-success"
                                                  title="Dikonfirmasi: <?= esc($row->ack_text ?? '') ?>">
                                                <i class="bi bi-check2-circle mr-1"></i>Sudah
                                            </span>
                                        <?php elseif ($batal): ?>
                                            <span class="text-[10px] text-slate-400">—</span>
                                        <?php else: ?>
                                            <span class="badge-tw badge-warning">
                                                <i class="bi bi-hourglass-split mr-1"></i>Belum
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex gap-1 justify-center">
                                            <?php if (! $batal): ?>
                                                <a href="<?= site_url('admin/presensi/download-teguran/' . (int) $row->id) ?>"
                                                   class="btn-tw btn-tw-sm btn-success"
                                                   target="_blank"
                                                   title="Cetak PDF surat teguran">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn-tw btn-tw-sm btn-info"
                                                        onclick="kirimTeguran(<?= (int) $row->id ?>, '<?= esc(addslashes($row->nama_guru ?? '')) ?>')"
                                                        title="Kirim file PDF ke WA/Telegram personal guru">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                                <button type="button"
                                                        class="btn-tw btn-tw-sm btn-light border text-red-600"
                                                        onclick="batalkanTeguran(<?= (int) $row->id ?>)"
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

    <!-- Kandidat: guru yang perlu surat tapi belum punya -->
    <?php if (! empty($kandidat)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h6 class="font-bold text-slate-700 mb-0">
                    <i class="bi bi-person-exclamation mr-2 text-red-500"></i>
                    Guru yang Belum Dapat Surat
                </h6>
                <p class="text-[11px] text-slate-500 m-0 mt-0.5">
                    Alpha bulan <?= esc($blnIdx[$bulan]) ?> <?= (int) $tahun ?> sudah melewati ambang
                    <?= $ambangAwal ?> hari, tapi belum ada surat terbit.
                </p>
            </div>
            <span class="badge-tw badge-warning"><?= count($kandidat) ?> guru</span>
        </div>

        <div class="table-responsive-tw">
            <table class="table-tw">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th>Nama Guru</th>
                        <th>NIP</th>
                        <th class="text-center">Alpha</th>
                        <th class="text-center">Tercapai</th>
                        <th class="text-center" width="18%">Buat Surat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $j = 1;
                    foreach ($kandidat as $k): ?>
                        <tr>
                            <td class="text-center"><?= $j++ ?></td>
                            <td class="font-bold text-slate-800"><?= esc($k['nama_guru']) ?></td>
                            <td class="text-xs"><?= esc($k['nip'] ?: '—') ?></td>
                            <td class="text-center font-bold text-red-600"><?= (int) $k['alpha'] ?></td>
                            <td class="text-center">
                                <span class="badge-tw badge-<?= esc(match ((int) $k['level']) {
                                    10 => 'danger',
                                    7  => 'warning',
                                    default => 'info',
                                }) ?>">
                                    <?= esc(\App\Libraries\SuratTeguranLevel::label((int) $k['level'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn-tw btn-tw-sm btn-primary"
                                        onclick="buatTeguran(<?= (int) $k['id_guru'] ?>, '<?= esc(addslashes($k['nama_guru'])) ?>')"
                                        title="Buat surat teguran untuk guru ini">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
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
                <p class="font-bold m-0 text-slate-700">Cara kerja surat teguran</p>
                <p class="m-0">
                    <b>Sumber angka</b> — dihitung dari tabel presensi guru, hanya hari kerja efektif
                    (bukan hari libur minggu, nasional, atau jadwal kerja libur). Hari yang belum
                    datang tidak dihitung, jadi nomor ini tidak akan melompat di awal bulan.
                </p>
                <p class="m-0">
                    <b>Alpha</b> — hari kerja efektif tanpa baris absensi, atau berstatus ALPHA / ALPA.
                    Izin, sakit, dan dinas yang disetujui tidak dihitung.
                </p>
                <p class="m-0">
                    <b>Terbit otomatis</b> — tanggal 7, 14, 21, dan akhir bulan. Satu guru mendapat
                    satu surat per evaluasi, yaitu tingkat terberat yang sudah dilampaui. Tingkat
                    surat tidak pernah turun dalam satu bulan.
                </p>
                <p class="m-0">
                    <b>Angka di dalam surat adalah foto saat surat terbit</b>, bukan dihitung ulang.
                    Dicetak ulang tahun depan pun angkanya sama seperti waktu guru membacanya.
                </p>
                <p class="m-0">
                    <b>Pembatalan</b> — surat dibatalkan, bukan dihapus, supaya arsip keberadaannya
                    tetap bisa dibuktikan. Pembatalan otomatis karena hari alpha dilengkapi akan
                    hidup kembali kalau alpha-nya naik lagi; pembatalan manual operator tidak
                    ditimpa perhitungan.
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

    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    const bulan    = '<?= (int) $bulan ?>';
    const tahun    = '<?= (int) $tahun ?>';

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
    // Level tidak dikirim: server yang menentukan tingkat terberat dari
    // alpha terkini. Kirim level dari klien berarti operator bisa membuat
    // surat tingkat I untuk guru yang sudah punya tingkat III.
    window.buatTeguran = function (idGuru, nama) {
        const jalankan = function () {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-teguran/buat') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_test_name: csrfHash,
                    id_guru: idGuru,
                    bulan: bulan,
                    tahun: tahun
                },
                headers: { 'X-CSRF-TOKEN': csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.status) {
                        toast(res.message || 'Surat teguran dibuat.', true);
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
            if (confirm('Buat surat teguran untuk ' + nama + '?')) {
                jalankan();
            }
            return;
        }

        Swal.fire({
            title: 'Buat Surat Teguran?',
            html: 'Untuk <strong>' + nama + '</strong><br><br>'
                + '<span class="text-xs text-slate-500">Tingkat surat ditentukan otomatis dari '
                + 'jumlah alpha bulan ini — tingkat terberat yang sudah dilampaui.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Buat',
            cancelButtonText: 'Batal',
            preConfirm: function () {
                jalankan();
                return true;
            }
        });
    };

    // ── Batalkan surat ─────────────────────────────────────────────
    window.batalkanTeguran = function (id) {
        const jalankan = function (alasan) {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-teguran/batal') ?>/' + id,
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
            if (confirm('Batalkan surat teguran ini?')) {
                jalankan('');
            }
            return;
        }

        Swal.fire({
            title: 'Batalkan Surat Teguran?',
            html: '<label class="block text-left text-xs text-slate-600 mb-1">Alasan pembatalan (opsional)</label>'
                + '<input id="inputAlasan" class="swal2-input" style="width:100%" placeholder="mis. Sudah ada izin resmi">'
                + '<div class="text-left text-[11px] text-amber-700 mt-2">'
                + 'Pembatalan manual tidak akan dibatalkan otomatis, bahkan kalau alpha guru '
                + 'naik lagi di bulan berikutnya.'
                + '</div>',
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
    // ── Kirim ulang notifikasi personal ────────────────────────
    window.kirimTeguran = function (id, nama) {
        const jalankan = function () {
            $.ajax({
                url: '<?= site_url('admin/presensi/surat-teguran/kirim') ?>/' + id,
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_test_name: csrfHash
                },
                headers: { 'X-CSRF-TOKEN': csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    toast(res.message || 'Notifikasi dikirim.', !!res.status);
                },
                error: function () {
                    toast('Gagal menghubungi server.', false);
                }
            });
        };

        if (typeof Swal === 'undefined') {
            if (confirm('Kirim file PDF surat teguran ke WA/Telegram personal ' + nama + '?')) {
                jalankan();
            }
            return;
        }

        Swal.fire({
            title: 'Kirim PDF ke Personal?',
            html: 'Untuk <strong>' + nama + '</strong><br><br>'
                + '<span class="text-xs text-slate-500">File PDF surat dikirim ke WA/Telegram pribadi guru '
                + 'saja — tidak diumumkan ke grup. Surat tidak diterbitkan ulang.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0891b2',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal',
            preConfirm: function () {
                jalankan();
                return true;
            }
        });
    };
})();
</script>
<?= $this->endSection() ?>
