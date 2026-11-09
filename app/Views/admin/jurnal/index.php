<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $exportQuery = [];
    if (!empty($mode_bulanan)) {
        $exportQuery['bulan'] = $bulan;
        $exportQuery['tahun'] = $tahun;
    } else {
        $exportQuery['tanggal'] = $tanggal;
    }
    if (!empty($id_guru_pilih)) $exportQuery['id_guru'] = $id_guru_pilih;
    if (!empty($id_kelas_pilih)) $exportQuery['id_kelas'] = $id_kelas_pilih;
    if (!empty($id_mapel_pilih)) $exportQuery['id_mapel'] = $id_mapel_pilih;
    if (!empty($validasi_pilih)) $exportQuery['validasi'] = $validasi_pilih;
    $exportUrl = site_url('admin/jurnal/export') . '?' . http_build_query($exportQuery);

    $resetFilterUrl = site_url('admin/jurnal') . '?' . http_build_query(
        !empty($mode_bulanan) ? ['bulan' => $bulan, 'tahun' => $tahun] : ['tanggal' => $tanggal]
    );

    $totalSesiView          = (int) ($total_sesi ?? count($jurnals));
    $totalTerjadwalView     = (int) ($total_terjadwal ?? 0);
    $totalTerjadwalSudah    = (int) ($total_terjadwal_sudah ?? 0);
    $totalTerjadwalBelum    = (int) ($total_terjadwal_belum ?? 0);
    $totalTerjadwalAkan     = (int) ($total_terjadwal_akan ?? 0);
    $persenCakupan          = $totalTerjadwalView > 0
        ? min(100, (int) round($totalTerjadwalSudah / $totalTerjadwalView * 100))
        : null;
    $barCakupan             = $persenCakupan === null
        ? 'bg-slate-200'
        : ($persenCakupan >= 80 ? 'bg-emerald-500' : ($persenCakupan >= 50 ? 'bg-amber-500' : 'bg-rose-500'));

    $hadirCount  = (int) ($total_guru_hadir ?? 0);
    $hadirWajibCount = (int) ($total_guru_hadir_berjadwal ?? 0);
    $wajibCount  = (int) ($total_guru_wajib_jurnal ?? 0);
    $belumCount  = (int) ($total_belum_jurnal ?? 0);
    $guruSudahJurnal = (int) ($total_guru_sudah_jurnal ?? 0);
    $guruBelum   = $guru_belum_jurnal ?? [];
    $guruBelumSesi = $guru_belum_sesi ?? [];

    // Status validasi jurnal. Angka "sudah dijurnal" hanya ikut terbelah saat
    // mode ketat aktif; di luar itu jurnal tetap dihitung terisi, sama seperti
    // gerbang absen pulang guru.
    $adaValidasi        = !empty($kolom_validasi);
    $modeValidasiKetat  = !empty($mode_validasi_ketat);
    $validasiStats      = is_array($validasi_stats ?? null) ? $validasi_stats : [];
    $validasiMenunggu   = (int) ($validasiStats['menunggu'] ?? 0);
    $validasiDitolak    = (int) ($validasiStats['ditolak'] ?? 0);
    $validasiDisetujui  = (int) ($validasiStats['disetujui'] ?? 0);
    $totalTerjadwalMenunggu = (int) ($total_terjadwal_menunggu ?? 0);
    $petugasValidasi    = $petugas_validasi ?? [];
    $validasiPilih      = (string) ($validasi_pilih ?? '');
    $labelValidasi      = [
        'APPROVED' => ['Disetujui', 'bg-emerald-100 text-emerald-800', 'bi-check-circle-fill'],
        'PENDING'  => ['Menunggu validasi', 'bg-indigo-100 text-indigo-700', 'bi-hourglass-split'],
        'REJECTED' => ['Ditolak', 'bg-rose-100 text-rose-700', 'bi-x-circle-fill'],
    ];
    $statusValidasiBaris = static function ($status) use ($labelValidasi): array {
        $st = strtoupper(trim((string) ($status ?? '')));
        return $labelValidasi[isset($labelValidasi[$st]) ? $st : 'PENDING'];
    };

    // Tautan drill-down kartu guru: tampilkan sesi milik guru tersebut saja.
    $guruFilterUrl = static function (int $idGuru) use ($tanggal, $id_guru_pilih): string {
        $query = ['tanggal' => $tanggal];
        if ($idGuru > 0 && (int) $id_guru_pilih !== $idGuru) {
            $query['id_guru'] = $idGuru;
        }
        return site_url('admin/jurnal') . '?' . http_build_query($query);
    };

    $inisial = static function (string $nama): string {
        $nama = trim(preg_replace('/\s+/', ' ', $nama));
        if ($nama === '') return '?';
        $bagian = explode(' ', $nama);
        $hasil = mb_substr($bagian[0], 0, 1);
        if (isset($bagian[1]) && $bagian[1] !== '') $hasil .= mb_substr($bagian[1], 0, 1);
        return mb_strtoupper($hasil);
    };

    $namaGuruFilter = '';
    foreach (($gurus ?? []) as $g) {
        if ((int) $g->id_guru === (int) $id_guru_pilih) { $namaGuruFilter = $g->nama_guru; break; }
    }
    $namaKelasFilter = '';
    foreach (($kelases ?? []) as $k) {
        $kId = is_object($k) ? (int) $k->id_kelas : (int) $k;
        if ($kId === (int) $id_kelas_pilih) { $namaKelasFilter = is_object($k) ? $k->nama_kelas : $k; break; }
    }
    $namaMapelFilter = '';
    foreach (($mapels ?? []) as $m) {
        if ((int) $m->id_mapel === (int) $id_mapel_pilih) { $namaMapelFilter = $m->nama_mapel; break; }
    }
    $adaFilter = !empty($id_guru_pilih) || !empty($id_kelas_pilih) || !empty($id_mapel_pilih);
?>
<style>
    .sesi-tab {
        padding: 5px 11px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        color: #64748b;
        background: transparent;
        border: 1px solid transparent;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
        white-space: nowrap;
    }
    .sesi-tab:hover { color: #0f172a; }
    .sesi-tab.is-active {
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
    }
    .data-table thead th { position: sticky; top: 0; z-index: 5; }
    /* Daftar guru dibatasi agar panel tidak memanjang saat guru banyak. */
    .guru-belum-grid { max-height: 430px; overflow-y: auto; }
    .guru-card .guru-chevron { transition: color .15s ease; }
    .guru-card:hover .guru-chevron { color: #d97706; }
    /* Scroll vertikal tabel jurnal agar header sticky berfungsi. */
    .jurnal-scroll { max-height: 68vh; }
</style>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-journal-text"></i> Monitoring KBM
            </div>
            <h1 class="text-xl md:text-2xl font-extrabold text-slate-900 tracking-tight m-0">Monitoring Jurnal Mengajar</h1>
            <p class="text-xs text-slate-500 mt-1 m-0">Pantau pelaksanaan KBM guru, materi pokok, dan cross-check dengan kehadiran harian.</p>
            <div class="flex items-center gap-1.5 mt-2.5 text-[11px] font-bold text-slate-500">
                <i class="bi bi-calendar3 text-slate-400"></i>
                <span><?= esc($periode_label ?? buat_tanggal(date('l, d F Y', strtotime($tanggal)))) ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="<?= esc($exportUrl) ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-filetype-csv"></i> Ekspor CSV
            </a>
            <a href="<?= site_url('admin/jurnal/rekap') ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-bar-chart-fill text-indigo-600"></i> Rekap Bulanan
            </a>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base shrink-0">
                    <i class="bi bi-journal-check"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">Jurnal Terisi</p>
                    <p class="text-xl font-extrabold text-slate-900 leading-tight m-0"><?= $totalSesiView ?></p>
                </div>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 m-0 mt-2.5">
                <?php if ($persenCakupan !== null): ?>
                    <?= $totalTerjadwalSudah ?> dari <?= $totalTerjadwalView ?> sesi terjadwal
                    <?php if ($totalTerjadwalAkan > 0): ?>
                    <span class="text-slate-300">·</span> <?= $totalTerjadwalAkan ?> belum dimulai
                    <?php endif; ?>
                <?php elseif (!empty($mode_bulanan)): ?>
                    selama periode ini
                <?php else: ?>
                    belum ada jadwal pada tanggal ini
                <?php endif; ?>
            </p>
            <?php if ($persenCakupan !== null): ?>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-1.5" role="progressbar"
                 aria-valuenow="<?= $persenCakupan ?>" aria-valuemin="0" aria-valuemax="100"
                 aria-label="Cakupan jurnal <?= $persenCakupan ?> persen">
                <div class="h-full rounded-full <?= $barCakupan ?>" style="width: <?= $persenCakupan ?>%"></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shrink-0">
                    <i class="bi bi-person-workspace"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">
                        <?= !empty($mode_bulanan) ? 'Guru Mengisi Jurnal' : 'Guru Sudah Jurnal' ?>
                    </p>
                    <p class="text-xl font-extrabold text-slate-900 leading-tight m-0">
                        <?= !empty($mode_bulanan) ? (int) ($total_guru_mengajar ?? 0) : $guruSudahJurnal ?>
                    </p>
                </div>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 m-0 mt-2.5">
                <?php if (!empty($mode_bulanan)): ?>
                    mengisi jurnal periode ini
                <?php elseif ($wajibCount > 0): ?>
                    dari <?= $wajibCount ?> guru berjadwal · <?= $hadirWajibCount ?> hadir
                <?php else: ?>
                    <?= $hadirCount ?> guru tercatat hadir
                <?php endif; ?>
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-base shrink-0">
                    <i class="bi bi-door-open"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">Kelas Tercakup</p>
                    <p class="text-xl font-extrabold text-slate-900 leading-tight m-0"><?= (int) ($total_kelas_tercakup ?? 0) ?></p>
                </div>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 m-0 mt-2.5">
                <?= count($kelases ?? []) > 0 ? 'dari ' . count($kelases) . ' kelas' : 'kelas memiliki jurnal' ?>
            </p>
        </div>

        <div class="bg-white rounded-2xl border <?= $belumCount > 0 ? 'border-amber-200/80' : 'border-slate-200/80' ?> shadow-xs p-4">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl <?= $belumCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center text-base shrink-0">
                    <i class="bi <?= $belumCount > 0 ? 'bi-person-exclamation' : 'bi-check2-circle' ?>"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">
                        <?= !empty($mode_bulanan) ? 'Guru Berjadwal Tanpa Jurnal' : 'Hadir Belum Jurnal' ?>
                    </p>
                    <p class="text-xl font-extrabold <?= $belumCount > 0 ? 'text-amber-700' : 'text-emerald-700' ?> leading-tight m-0"><?= $belumCount ?></p>
                </div>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 m-0 mt-2.5">
                <?php if (!empty($mode_bulanan)): ?>
                    belum mengisi bulan ini · <?= $wajibCount ?> guru berjadwal
                <?php elseif ($hadirWajibCount > 0): ?>
                    dari <?= $hadirWajibCount ?> guru berjadwal yang hadir
                <?php elseif ($wajibCount > 0): ?>
                    belum ada yang hadir dari <?= $wajibCount ?> guru berjadwal
                <?php else: ?>
                    tidak ada guru berjadwal
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Filter & Date Selector -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <?php if (!empty($mode_bulanan)): ?>
        <div class="mb-3 flex items-start gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-2 text-[11px] font-semibold text-indigo-800">
            <i class="bi bi-calendar-month mt-0.5"></i>
            <span>
                Menampilkan seluruh jurnal periode <strong><?= esc($periode_label) ?></strong>.
                Pilih tanggal lalu klik <strong>Tampilkan</strong> untuk kembali ke mode harian,
                atau <a href="<?= site_url('admin/jurnal') ?>" class="underline font-bold">kembali ke hari ini</a>.
            </span>
        </div>
        <?php endif; ?>
        <form method="GET" action="<?= site_url('admin/jurnal') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <?php if (!empty($mode_bulanan)): ?>
            <input type="hidden" name="bulan" value="<?= (int) $bulan ?>">
            <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
            <?php endif; ?>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="<?= esc($tanggal) ?>"
                       class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Guru Pengajar</label>
                <select name="id_guru" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Guru</option>
                    <?php foreach ($gurus as $g): ?>
                    <option value="<?= $g->id_guru ?>" <?= $id_guru_pilih == $g->id_guru ? 'selected' : '' ?>>
                        <?= esc($g->nama_guru) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Kelas</label>
                <select name="id_kelas" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Kelas</option>
                    <?php if (!empty($kelases)): foreach ($kelases as $key => $k): ?>
                    <?php
                        $kId = is_object($k) ? $k->id_kelas : $key;
                        $kNama = is_object($k) ? $k->nama_kelas : $k;
                    ?>
                    <option value="<?= $kId ?>" <?= $id_kelas_pilih == $kId ? 'selected' : '' ?>>
                        <?= esc($kNama) ?>
                    </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Mata Pelajaran</label>
                <select name="id_mapel" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Mapel</option>
                    <?php foreach (($mapels ?? []) as $m): ?>
                    <option value="<?= (int) $m->id_mapel ?>" <?= (int) $id_mapel_pilih === (int) $m->id_mapel ? 'selected' : '' ?>>
                        <?= esc($m->nama_mapel) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($adaValidasi): ?>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Status Validasi</label>
                <select name="validasi" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Status</option>
                    <option value="PENDING" <?= $validasiPilih === 'PENDING' ? 'selected' : '' ?>>Menunggu (<?= $validasiMenunggu ?>)</option>
                    <option value="APPROVED" <?= $validasiPilih === 'APPROVED' ? 'selected' : '' ?>>Disetujui (<?= $validasiDisetujui ?>)</option>
                    <option value="REJECTED" <?= $validasiPilih === 'REJECTED' ? 'selected' : '' ?>>Ditolak (<?= $validasiDitolak ?>)</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
                <a href="<?= site_url('admin/jurnal') ?>" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors no-underline" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>

        <?php if ($adaFilter): ?>
        <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-slate-100">
            <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400 me-1">Filter aktif</span>
            <?php if ($namaGuruFilter !== ''): ?>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 text-[10px] font-bold">
                <i class="bi bi-person-fill"></i> <?= esc($namaGuruFilter) ?>
            </span>
            <?php endif; ?>
            <?php if ($namaKelasFilter !== ''): ?>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-[10px] font-bold">
                <i class="bi bi-door-open-fill"></i> <?= esc($namaKelasFilter) ?>
            </span>
            <?php endif; ?>
            <?php if ($namaMapelFilter !== ''): ?>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-violet-50 text-violet-700 text-[10px] font-bold">
                <i class="bi bi-book-fill"></i> <?= esc($namaMapelFilter) ?>
            </span>
            <?php endif; ?>
            <a href="<?= esc($resetFilterUrl) ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold text-slate-500 hover:bg-slate-100 no-underline">
                <i class="bi bi-x-circle"></i> Hapus semua
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($adaValidasi && ((int) ($validasiStats['total'] ?? 0)) > 0): ?>
    <!-- Antrean validasi: angka ini yang dilihat guru di gerbang absen pulang -->
    <?php
        $validasiQueryBase = array_filter([
            'tanggal' => $tanggal,
            'bulan' => !empty($mode_bulanan) ? $bulan : null,
            'tahun' => !empty($mode_bulanan) ? $tahun : null,
            'id_guru' => $id_guru_pilih ?: null,
            'id_kelas' => $id_kelas_pilih ?: null,
            'id_mapel' => $id_mapel_pilih ?: null,
        ], static fn($v) => $v !== null && $v !== '');
        $namaPetugas = implode(' / ', array_map(static fn($p) => (string) ($p['nama'] ?? ''), $petugasValidasi));
    ?>
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full <?= $validasiMenunggu + $validasiDitolak > 0 ? 'bg-indigo-500' : 'bg-emerald-500' ?>"></span>
                        <h3 class="text-sm font-bold text-slate-800 m-0">Antrean Validasi Jurnal</h3>
                        <?php if ($modeValidasiKetat): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] font-extrabold">
                            <i class="bi bi-shield-check text-[9px]"></i> Mode ketat aktif
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[11px] text-slate-400 m-0 mt-1 ms-3">
                        <?php if ($modeValidasiKetat): ?>
                        Selama mode ketat aktif, guru baru bisa absen pulang setelah jurnalnya berstatus <b>Disetujui</b>.
                        <?php else: ?>
                        Mode ketat tidak aktif — jurnal terisi sudah cukup untuk absen pulang, tapi antrean validasi tetap dipantau di sini.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <?php if ($validasiMenunggu > 0): ?>
                    <a href="<?= site_url('admin/jurnal') . '?' . http_build_query($validasiQueryBase + ['validasi' => 'PENDING']) ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold no-underline transition-colors">
                        <i class="bi bi-hourglass-split"></i> Menunggu <?= $validasiMenunggu ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($validasiDitolak > 0): ?>
                    <a href="<?= site_url('admin/jurnal') . '?' . http_build_query($validasiQueryBase + ['validasi' => 'REJECTED']) ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold no-underline transition-colors">
                        <i class="bi bi-x-circle"></i> Ditolak <?= $validasiDitolak ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?= site_url('admin/jurnal') . '?' . http_build_query($validasiQueryBase + ['validasi' => 'APPROVED']) ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold no-underline transition-colors">
                        <i class="bi bi-check-circle"></i> Disetujui <?= $validasiDisetujui ?>
                    </a>
                    <a href="<?= site_url('guru/piket/laporan') ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-[11px] font-bold no-underline transition-colors">
                        <i class="bi bi-check2-all"></i> Halaman Validasi Piket
                    </a>
                </div>
            </div>

            <?php if ($modeValidasiKetat): ?>
            <p class="text-[10px] font-semibold text-slate-500 m-0 mt-3 flex items-start gap-1">
                <i class="bi bi-person-badge mt-0.5"></i>
                <span>
                    <?php if ($namaPetugas !== ''): ?>
                    Penanggung jawab validasi hari ini: <b><?= esc($namaPetugas) ?></b>.
                    <?php else: ?>
                    <span class="text-rose-600 font-bold">Belum ada guru piket terjadwal hari ini</span>
                    — jurnal yang menunggu validasi tidak bisa disetujui, dan guru terkunci di absen pulang selama mode ketat aktif.
                    <?php endif; ?>
                </span>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($mode_bulanan)): ?>
    <!-- Guru hadir tetapi belum menulis jurnal -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-start justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full <?= $belumCount > 0 ? 'bg-amber-500' : 'bg-emerald-500' ?>"></span>
                    <h3 class="text-sm font-bold text-slate-800 m-0">Guru Hadir, Belum Menulis Jurnal</h3>
                </div>
                <p class="text-[11px] text-slate-400 m-0 mt-1 ms-3">
                    Hanya guru yang punya jadwal mengajar pada tanggal ini. Tanpa jadwal berarti tidak wajib menulis jurnal.
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <?php if ($totalTerjadwalAkan > 0): ?>
                <span class="text-[11px] font-bold text-slate-500 px-2.5 py-1 rounded-lg bg-slate-100"
                      title="Sesi yang jamnya belum tiba belum menagih jurnal">
                    <i class="bi bi-clock-history me-1"></i><?= $totalTerjadwalAkan ?> sesi belum dimulai
                </span>
                <?php endif; ?>
                <span class="text-[11px] font-bold text-slate-500 px-2.5 py-1 rounded-lg bg-slate-100">
                    <?= $hadirWajibCount ?> dari <?= $wajibCount ?> guru berjadwal hadir
                </span>
            </div>
        </div>

        <?php if ($wajibCount === 0): ?>
        <div class="px-5 py-8 text-center text-xs text-slate-400">
            <i class="bi bi-calendar-x me-1"></i>
            Tidak ada jadwal mengajar pada tanggal ini, jadi tidak ada guru yang diwajibkan menulis jurnal.
        </div>
        <?php elseif ($hadirWajibCount === 0): ?>
        <div class="px-5 py-8 text-center text-xs text-slate-400">
            <i class="bi bi-person-dash me-1"></i>
            Belum ada guru berjadwal yang tercatat hadir pada tanggal ini.
        </div>
        <?php elseif (empty($guruBelum)): ?>
        <div class="px-5 py-8 text-center text-xs font-semibold text-emerald-600">
            <i class="bi bi-check-circle-fill me-1"></i> Semua guru berjadwal yang hadir sudah menulis jurnal untuk sesi yang sudah berjalan.
        </div>
        <?php else: ?>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5 guru-belum-grid pe-1">
                <?php foreach ($guruBelum as $b):
                    $gid = (int) ($b->id_guru ?? 0);
                    $jamMasuk = (string) ($b->jam_masuk ?? '');
                    $jamLabel = strlen($jamMasuk) >= 16 ? substr($jamMasuk, 11, 5) : (strlen($jamMasuk) >= 5 ? substr($jamMasuk, 0, 5) : '');
                    $sesiKurang = $guruBelumSesi[$gid] ?? [];
                    $nMenunggu = count(array_filter($sesiKurang, static fn($sk) => !empty($sk['menunggu_validasi'])));
                ?>
                <a href="<?= esc($guruFilterUrl($gid)) ?>"
                   class="guru-card block rounded-2xl border border-amber-200/70 bg-amber-50/40 hover:bg-amber-50 hover:border-amber-300 p-3 no-underline transition-colors"
                   title="Tampilkan sesi guru ini">
                    <div class="flex items-start gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-[11px] font-extrabold shrink-0">
                            <?= esc($inisial((string) ($b->nama_guru ?? ''))) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-800 text-xs m-0 leading-snug truncate uppercase" title="<?= esc($b->nama_guru ?? '') ?>">
                                <?= esc($b->nama_guru ?? ('Guru #' . $gid)) ?>
                            </p>
                            <p class="text-[10px] font-semibold text-slate-500 m-0 mt-0.5">
                                Hadir<?= $jamLabel !== '' ? ' · ' . esc($jamLabel) : '' ?>
                                · <?= count($sesiKurang) - $nMenunggu ?> sesi belum dijurnal
                                <?php if ($nMenunggu > 0): ?>
                                · <?= $nMenunggu ?> menunggu validasi
                                <?php endif; ?>
                            </p>
                        </div>
                        <i class="bi bi-chevron-right text-[10px] text-amber-400 guru-chevron mt-1 shrink-0"></i>
                    </div>

                    <?php if (!empty($sesiKurang)): ?>
                    <div class="flex flex-wrap gap-1 mt-2.5">
                        <?php foreach (array_slice($sesiKurang, 0, 3) as $sk): ?>
                        <?php $skMenunggu = !empty($sk['menunggu_validasi']); ?>
                        <span class="inline-flex items-center gap-1 max-w-full px-2 py-1 rounded-lg bg-white border <?= $skMenunggu ? 'border-indigo-200' : 'border-amber-200/70' ?> text-[10px] font-bold text-slate-700"
                              title="<?= $skMenunggu ? 'Jurnal sudah ditulis, menunggu validasi piket · ' : '' ?>Jam <?= (int) ($sk['jam_ke'] ?? 0) ?> · <?= esc($sk['nama_kelas'] ?? '') ?> · <?= esc($sk['nama_mapel'] ?? '') ?> (<?= esc(($sk['dari'] ?? '') . '–' . ($sk['sampai'] ?? '')) ?>)">
                            <i class="bi <?= $skMenunggu ? 'bi-hourglass-split text-indigo-500' : 'bi-clock text-amber-500' ?>"></i>
                            <span class="truncate">
                                Jam <?= (int) ($sk['jam_ke'] ?? 0) ?> · <?= esc($sk['nama_kelas'] ?? '') ?> · <?= esc($sk['nama_mapel'] ?? '') ?>
                            </span>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($sesiKurang) > 3):
                            $sisaLabel = implode(' | ', array_map(static function ($sk): string {
                                return 'Jam ' . (int) ($sk['jam_ke'] ?? 0) . ' '
                                    . ($sk['nama_kelas'] ?? '') . ' '
                                    . ($sk['nama_mapel'] ?? '');
                            }, array_slice($sesiKurang, 3)));
                        ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-lg bg-amber-100 text-amber-800 text-[10px] font-bold"
                              title="<?= esc($sisaLabel) ?>">
                            +<?= count($sesiKurang) - 3 ?> sesi
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <p class="text-[10px] text-slate-400 m-0 mt-3 flex items-start gap-1">
                <i class="bi bi-info-circle mt-0.5"></i>
                <span>Klik kartu guru untuk memfilter sesi miliknya. Guru tanpa jadwal pada tanggal ini tidak diwajibkan menulis jurnal,
                dan sesi yang belum dimulai belum ditagih. Rincian tiap sesi ada di panel <b>Sesi Terjadwal vs Jurnal</b> di bawah.</span>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sesi terjadwal vs jurnal -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-start justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <h3 class="text-sm font-bold text-slate-800 m-0">Sesi Terjadwal vs Jurnal</h3>
                </div>
                <p class="text-[11px] text-slate-400 m-0 mt-1 ms-3">
                    <?= $totalTerjadwalSudah ?> sudah dijurnal · <?= $totalTerjadwalBelum ?> belum
                    <?php if ($modeValidasiKetat && $totalTerjadwalMenunggu > 0): ?>
                    · <?= $totalTerjadwalMenunggu ?> menunggu validasi
                    <?php endif; ?>
                    <?php if ($totalTerjadwalAkan > 0): ?>
                    · <?= $totalTerjadwalAkan ?> belum dimulai
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1 p-1 rounded-full bg-slate-100" role="tablist" aria-label="Filter status sesi">
                    <button type="button" role="tab" aria-controls="sesiBody" class="sesi-tab is-active" data-sesi-tab="semua" aria-selected="true">Semua <?= $totalTerjadwalView ?></button>
                    <button type="button" role="tab" aria-controls="sesiBody" class="sesi-tab" data-sesi-tab="belum" aria-selected="false">Belum <?= $totalTerjadwalBelum ?></button>
                    <?php if ($modeValidasiKetat && $totalTerjadwalMenunggu > 0): ?>
                    <button type="button" role="tab" aria-controls="sesiBody" class="sesi-tab" data-sesi-tab="menunggu" aria-selected="false">Menunggu <?= $totalTerjadwalMenunggu ?></button>
                    <?php endif; ?>
                    <button type="button" role="tab" aria-controls="sesiBody" class="sesi-tab" data-sesi-tab="terisi" aria-selected="false">Sudah <?= $totalTerjadwalSudah ?></button>
                    <?php if ($totalTerjadwalAkan > 0): ?>
                    <button type="button" role="tab" aria-controls="sesiBody" class="sesi-tab" data-sesi-tab="akan" aria-selected="false">Akan <?= $totalTerjadwalAkan ?></button>
                    <?php endif; ?>
                </div>
                <a href="<?= site_url('admin/jadwal') ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-[11px] font-bold transition-colors no-underline">
                    <i class="bi bi-calendar-week"></i> Atur Jadwal
                </a>
            </div>
        </div>

        <?php if ($totalTerjadwalView === 0): ?>
        <div class="px-5 py-10 text-center text-xs text-slate-400">
            <i class="bi bi-calendar-x me-1"></i>
            Belum ada jadwal pelajaran pada tanggal ini.
            <a href="<?= site_url('admin/jadwal') ?>" class="text-blue-600 font-bold hover:underline">Atur jadwal</a>
            agar sesi KBM dapat dipantau otomatis.
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="data-table w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
                        <th class="px-4 py-3 text-center whitespace-nowrap" width="52">Jam</th>
                        <th class="px-4 py-3 whitespace-nowrap" width="104">Waktu</th>
                        <th class="px-4 py-3" width="180">Guru Pengajar</th>
                        <th class="px-4 py-3" width="110">Kelas</th>
                        <th class="px-4 py-3" width="200">Mata Pelajaran</th>
                        <th class="px-4 py-3" width="130">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="sesiBody">
                    <?php foreach ($sesi_terjadwal as $s): ?>
                    <?php
                        $filterUrl = site_url('admin/jurnal') . '?' . http_build_query(array_filter([
                            'tanggal' => $tanggal,
                            'id_guru' => (int) ($s['id_guru'] ?? 0) ?: null,
                            'id_kelas' => (int) ($s['id_kelas'] ?? 0) ?: null,
                            'id_mapel' => (int) ($s['id_mapel'] ?? 0) ?: null,
                        ], static fn($v) => $v !== null && $v !== ''));
                        $sudah = !empty($s['terisi']);
                        $berjalan = !empty($s['berjalan']);
                        $menungguValidasi = $sudah && strtoupper((string) ($s['validasi_status'] ?? '')) !== 'APPROVED';
                        $stValidasi = $menungguValidasi ? $statusValidasiBaris($s['validasi_status'] ?? '') : null;
                        $statusSesi = $sudah
                            ? ($modeValidasiKetat && $menungguValidasi ? 'menunggu' : 'terisi')
                            : ($berjalan ? 'belum' : 'akan');
                    ?>
                    <tr class="sesi-row hover:bg-slate-50/70 transition-colors" data-status="<?= esc($statusSesi) ?>">
                        <td class="px-4 py-3 text-center font-extrabold text-blue-700 bg-blue-50/40"><?= (int) ($s['jam_ke'] ?? 0) ?></td>
                        <td class="px-4 py-3 font-semibold text-slate-600 whitespace-nowrap tabular-nums"><?= esc(($s['dari'] ?? '-') . ' - ' . ($s['sampai'] ?? '-')) ?></td>
                        <td class="px-4 py-3">
                            <?php if ((int) ($s['id_guru'] ?? 0) > 0): ?>
                            <a href="<?= esc($filterUrl) ?>" class="font-bold text-slate-800 hover:text-blue-700 no-underline uppercase"><?= esc($s['nama_guru'] ?? '-') ?></a>
                            <?php else: ?>
                            <span class="text-slate-400 italic uppercase">Belum ditetapkan</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-bold text-indigo-700 uppercase"><?= esc($s['nama_kelas'] ?? '-') ?></td>
                        <td class="px-4 py-3 font-semibold text-slate-700 uppercase"><?= esc($s['nama_mapel'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php if ((int) ($s['id_guru'] ?? 0) <= 0): ?>
                            <a href="<?= site_url('admin/jadwal') ?>" class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-50 text-rose-700 rounded-md text-[10px] font-bold no-underline">
                                <i class="bi bi-person-x-fill text-[8px]"></i> Guru belum diset
                            </a>
                            <?php elseif ($sudah): ?>
                            <div class="flex items-center gap-1 flex-wrap">
                            <?php if ((int) ($s['id_jurnal'] ?? 0) > 0): ?>
                            <a href="<?= site_url('admin/jurnal/detail/' . (int) $s['id_jurnal']) ?>" target="_blank"
                               class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-md text-[10px] font-bold no-underline"
                               title="Baca jurnal sesi ini">
                                <i class="bi bi-check-circle-fill text-[8px]"></i> Dijurnal
                            </a>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-check-circle-fill text-[8px]"></i> Dijurnal
                            </span>
                            <?php endif; ?>
                            <?php if ($menungguValidasi && $adaValidasi): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 <?= $stValidasi[1] ?> rounded-md text-[10px] font-bold"
                                  title="Jurnal sudah ditulis, <?= strtolower($stValidasi[0]) ?> oleh guru piket<?= $modeValidasiKetat ? ' — selama mode ketat, guru ini belum bisa absen pulang' : '' ?>">
                                <i class="bi <?= esc($stValidasi[2]) ?> text-[8px]"></i> <?= esc($stValidasi[0]) ?>
                            </span>
                            <?php elseif ($adaValidasi): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold"
                                  title="Sudah disetujui guru piket">
                                <i class="bi bi-shield-check text-[8px]"></i> Tervalidasi
                            </span>
                            <?php endif; ?>
                            </div>
                            <?php elseif ($berjalan): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-hourglass-split text-[8px]"></i> Belum dijurnal
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 text-slate-500 rounded-md text-[10px] font-bold"
                                  title="Sesi belum dimulai, jurnal belum ditagih">
                                <i class="bi bi-clock text-[8px]"></i> Belum dimulai
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="sesiEmpty" class="hidden px-5 py-8 text-center text-xs text-slate-400">
                <i class="bi bi-funnel me-1"></i> Tidak ada sesi pada filter status ini.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <h3 class="text-sm font-bold text-slate-800 m-0">
                    Jurnal Mengajar Terisi
                </h3>
                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    <?= $totalSesiView ?> sesi
                </span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="relative">
                    <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                    <input type="search" id="jurnalSearch" placeholder="Cari guru / kelas / mapel..."
                           aria-label="Cari jurnal"
                           class="w-56 max-w-full pl-7 pr-3 py-1.5 text-[11px] font-semibold rounded-lg border border-slate-200 bg-slate-50 text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500 transition-colors">
                </div>
                <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">
                    <?= esc($periode_label ?? buat_tanggal(date('l, d F Y', strtotime($tanggal)))) ?>
                </span>
            </div>
        </div>

        <?php if (empty($jurnals)): ?>
        <div class="py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="bi bi-journal-x"></i>
            </div>
            <p class="text-sm font-bold text-slate-700">Belum ada jurnal mengajar pada periode ini</p>
            <p class="text-xs text-slate-400 mt-0.5">Jurnal yang diisi oleh guru akan muncul di sini secara real-time</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto jurnal-scroll">
            <table class="data-table w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
                        <th class="px-4 py-3 text-center whitespace-nowrap" width="50">Jam</th>
                        <?php if (!empty($mode_bulanan)): ?>
                        <th class="px-4 py-3 text-center whitespace-nowrap" width="80">Tanggal</th>
                        <?php endif; ?>
                        <th class="px-4 py-3" width="180">Guru Pengajar</th>
                        <th class="px-4 py-3" width="90">Kelas</th>
                        <th class="px-4 py-3" width="160">Mata Pelajaran</th>
                        <th class="px-4 py-3">Materi Pokok &amp; Model</th>
                        <th class="px-4 py-3" width="110">Presensi Guru</th>
                        <th class="px-4 py-3 text-center" width="70">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="jurnalBody">
                    <?php foreach ($jurnals as $j):
                        $hadirInfo = $guru_hadir_map[$j->tanggal . '|' . (int) $j->id_guru] ?? null;
                        $statusMasuk = strtoupper(trim((string) ($hadirInfo->status_masuk ?? '')));
                        $jamMasuk = trim((string) ($hadirInfo->jam_masuk ?? ''));
                        $adaJamMasuk = $jamMasuk !== ''
                            && $jamMasuk !== '00:00:00'
                            && !str_starts_with($jamMasuk, '0000-00-00');
                        $jamLabel = $adaJamMasuk
                            ? (strlen($jamMasuk) >= 16 ? substr($jamMasuk, 11, 5) : substr($jamMasuk, 0, 5))
                            : '';
                        $cari = strtolower(implode(' ', array_filter([
                            (string) ($j->nama_guru ?? ''),
                            (string) ($j->nip ?? ''),
                            (string) ($j->nama_kelas ?? ''),
                            (string) ($j->nama_mapel ?? ''),
                            (string) ($j->materi_pokok ?? ''),
                            (string) ($j->model_belajar ?? ''),
                            (string) ($j->jenis_asesmen ?? ''),
                            $adaValidasi ? strtolower($statusValidasiBaris($j->validasi_status ?? '')[0]) : '',
                        ])));
                    ?>
                    <tr class="jurnal-row hover:bg-slate-50/70 transition-colors" data-search="<?= esc($cari) ?>">
                        <td class="px-4 py-3 text-center font-extrabold text-blue-700 bg-blue-50/40">
                            <?= esc($j->jam_ke) ?>
                        </td>
                        <?php if (!empty($mode_bulanan)): ?>
                        <td class="px-4 py-3 text-center font-bold text-slate-600 whitespace-nowrap">
                            <?= esc(date('d/m/Y', strtotime($j->tanggal))) ?>
                            <span class="block text-[9px] font-semibold text-slate-400"><?= esc(buat_tanggal(date('D', strtotime($j->tanggal)))) ?></span>
                        </td>
                        <?php endif; ?>
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-800 leading-tight"><?= esc($j->nama_guru) ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">NIP: <?= esc($j->nip ?: '-') ?></p>
                        </td>
                        <td class="px-4 py-3 font-bold text-indigo-700">
                            <?= esc($j->nama_kelas) ?>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-700">
                            <?= esc($j->nama_mapel) ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-800 line-clamp-1" title="<?= esc($j->materi_pokok) ?>">
                                <?= esc($j->materi_pokok) ?>
                            </p>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <?php if ($adaValidasi): ?>
                                <?php $vLabel = $statusValidasiBaris($j->validasi_status ?? ''); ?>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded <?= $vLabel[1] ?> text-[9px] font-bold"
                                      title="<?= esc($vLabel[0]) ?><?= !empty($j->validasi_catatan) ? ' · ' . esc($j->validasi_catatan) : '' ?><?= !empty($j->validasi_pada) ? ' · ' . esc((string) $j->validasi_pada) : '' ?>">
                                    <i class="bi <?= esc($vLabel[2]) ?>"></i> <?= esc($vLabel[0]) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($j->model_belajar)): ?>
                                <span class="px-1.5 py-0.5 bg-purple-50 text-purple-700 rounded text-[9px] font-bold"><?= esc($j->model_belajar) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($j->jenis_asesmen)): ?>
                                <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[9px] font-bold"><?= esc($j->jenis_asesmen) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($j->catatan_khusus)): ?>
                                <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 rounded text-[9px] font-bold" title="Ada catatan khusus">
                                    <i class="bi bi-sticky"></i> Catatan
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($adaJamMasuk): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-check-circle-fill text-[8px]"></i> Hadir (<?= esc($jamLabel) ?>)
                            </span>
                            <?php elseif (in_array($statusMasuk, ['IJIN', 'SAKIT'], true)): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-clock-fill text-[8px]"></i> <?= $statusMasuk === 'IJIN' ? 'Izin' : 'Sakit' ?>
                            </span>
                            <?php elseif ($statusMasuk === 'DINAS_APPROVED'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-sky-100 text-sky-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-briefcase-fill text-[8px]"></i> Dinas
                            </span>
                            <?php elseif ($statusMasuk === 'ALPHA'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-100 text-rose-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-x-circle-fill text-[8px]"></i> Alpa
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-100 text-rose-800 rounded-md text-[10px] font-bold">
                                <i class="bi bi-x-circle-fill text-[8px]"></i> Belum Absen
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="<?= site_url('admin/jurnal/detail/' . $j->id_jurnal) ?>" target="_blank"
                               class="inline-flex items-center justify-center w-7 h-7 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-xs transition-colors no-underline"
                               title="Baca Detail Jurnal">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="jurnalFilterEmpty" class="hidden px-5 py-10 text-center text-xs text-slate-400">
                <i class="bi bi-search me-1"></i> Tidak ada jurnal yang cocok dengan pencarian.
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    'use strict';

    // ── Filter status sesi terjadwal ──────────────
    var sesiTabs = Array.prototype.slice.call(document.querySelectorAll('[data-sesi-tab]'));
    var sesiRows = Array.prototype.slice.call(document.querySelectorAll('.sesi-row'));
    var sesiEmpty = document.getElementById('sesiEmpty');
    var sesiMode = 'semua';

    function applySesiFilter() {
        var shown = 0;
        sesiRows.forEach(function (row) {
            var ok = sesiMode === 'semua' || row.getAttribute('data-status') === sesiMode;
            row.classList.toggle('hidden', !ok);
            if (ok) shown++;
        });
        if (sesiEmpty) sesiEmpty.classList.toggle('hidden', shown !== 0);
        sesiTabs.forEach(function (tab) {
            var active = tab.getAttribute('data-sesi-tab') === sesiMode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    sesiTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            sesiMode = tab.getAttribute('data-sesi-tab') || 'semua';
            applySesiFilter();
        });
    });
    if (sesiTabs.length) applySesiFilter();

    // ── Pencarian jurnal terisi ───────────────────
    var search = document.getElementById('jurnalSearch');
    var jurnalRows = Array.prototype.slice.call(document.querySelectorAll('.jurnal-row'));
    var jurnalEmpty = document.getElementById('jurnalFilterEmpty');

    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            var shown = 0;
            jurnalRows.forEach(function (row) {
                var ok = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
                row.classList.toggle('hidden', !ok);
                if (ok) shown++;
            });
            if (jurnalEmpty) jurnalEmpty.classList.toggle('hidden', shown !== 0);
        });
    }
})();
</script>
<?= $this->endSection() ?>
