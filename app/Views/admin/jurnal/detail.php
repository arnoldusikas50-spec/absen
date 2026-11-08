<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="fade-in-up p-4 md:p-6 space-y-5 max-w-4xl">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-start gap-3 min-w-0">
            <a href="<?= site_url('admin/jurnal') ?>"
               class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors no-underline shrink-0"
               title="Kembali ke Monitoring">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-[10px] font-bold mb-1">
                    <i class="bi bi-journal-text"></i> Baca Jurnal (Monitoring)
                </div>
                <h1 class="text-lg md:text-xl font-extrabold text-slate-900 m-0 leading-tight">Detail Jurnal Mengajar</h1>
                <p class="text-[11px] text-slate-500 m-0 mt-0.5">
                    <?= esc($tp_active->tahun ?? '-') ?> &middot; Semester <?= esc($smt_active->smt ?? '-') ?>
                </p>
            </div>
        </div>
        <?php if (!empty($can_print)): ?>
        <a href="<?= site_url('guru/jurnal/cetak/' . (int) $jurnal->id_jurnal) ?>" target="_blank"
           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline shrink-0">
            <i class="bi bi-printer"></i> Cetak
        </a>
        <?php endif; ?>
    </div>

    <!-- Identitas KBM -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-[11px] font-bold">
                <i class="bi bi-calendar3 text-[10px]"></i>
                <?= esc(buat_tanggal(date('l, d F Y', strtotime($jurnal->tanggal)))) ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                Jam <?= esc($jurnal->jam_ke) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Guru Pengajar</span>
                <span class="font-bold text-slate-800"><?= esc($jurnal->nama_guru ?? '-') ?></span>
                <span class="block text-[10px] text-slate-400 mt-0.5">NIP: <?= esc($jurnal->nip ?: '-') ?></span>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Kelas / Mata Pelajaran</span>
                <span class="font-bold text-indigo-700"><?= esc($jurnal->nama_kelas ?? '-') ?></span>
                <span class="block text-[11px] font-semibold text-slate-600 mt-0.5"><?= esc($jurnal->nama_mapel ?? '-') ?></span>
            </div>
        </div>
    </div>

    <!-- Status validasi -->
    <?php if (!empty($ada_validasi)): ?>
    <?php
        $stValidasi = (string) ($status_validasi ?? '');
        $validGaya = [
            'APPROVED' => ['Disetujui guru piket', 'border-emerald-200 bg-emerald-50/70 text-emerald-800', 'bi-shield-check'],
            'PENDING'  => ['Menunggu validasi guru piket', 'border-indigo-200 bg-indigo-50/70 text-indigo-700', 'bi-hourglass-split'],
            'REJECTED' => ['Ditolak — perlu diperbaiki guru', 'border-rose-200 bg-rose-50/70 text-rose-700', 'bi-x-circle-fill'],
        ];
        $gi = $validGaya[$stValidasi] ?? $validGaya['PENDING'];
    ?>
    <div class="rounded-3xl border <?= $gi[1] ?> px-4 py-3 flex items-start gap-2.5">
        <i class="bi <?= esc($gi[2]) ?> mt-0.5"></i>
        <div class="min-w-0">
            <span class="block text-xs font-extrabold"><?= esc($gi[0]) ?></span>
            <span class="block text-[10px] font-semibold opacity-80 mt-0.5">
                <?php if ($stValidasi !== 'PENDING' && !empty($nama_validator)): ?>
                Oleh <?= esc($nama_validator) ?><?= !empty($jurnal->validasi_pada) ? ' · ' . esc((string) $jurnal->validasi_pada) : '' ?>
                <?php elseif (!empty($jurnal->updated_at)): ?>
                Jurnal terakhir diubah <?= esc((string) $jurnal->updated_at) ?>
                <?php endif; ?>
            </span>
            <?php if (!empty($jurnal->validasi_catatan)): ?>
            <p class="text-[11px] font-semibold mt-1.5 mb-0 leading-relaxed whitespace-pre-line">
                Catatan: <?= esc((string) $jurnal->validasi_catatan) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Materi Pokok -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-1">
            <i class="bi bi-bookmark-fill text-blue-500 text-[9px]"></i> Materi Pokok
        </span>
        <p class="text-sm font-bold text-slate-800 leading-relaxed m-0">
            <?= nl2br(esc((string) ($jurnal->materi_pokok ?? '-'))) ?>
        </p>
    </div>

    <!-- Tujuan Pembelajaran -->
    <?php if (!empty($jurnal->tujuan_pembelajaran)): ?>
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-1">
            <i class="bi bi-bullseye text-indigo-500 text-[9px]"></i> Tujuan Pembelajaran
        </span>
        <p class="text-xs font-semibold text-slate-700 leading-relaxed m-0">
            <?= nl2br(esc((string) $jurnal->tujuan_pembelajaran)) ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Model & Asesmen -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-xs">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Model / Metode</span>
            <span class="text-xs font-bold text-purple-700"><?= esc($jurnal->model_belajar ?: 'Reguler') ?></span>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-xs">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Bentuk Asesmen</span>
            <span class="text-xs font-bold text-emerald-700"><?= esc($jurnal->jenis_asesmen ?: 'Formatif') ?></span>
        </div>
    </div>

    <!-- Profil Pancasila -->
    <?php if (!empty($profil_list)): ?>
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
            Dimensi Profil Pelajar Pancasila
        </span>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($profil_list as $p): ?>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs font-bold">
                <i class="bi bi-star-fill text-amber-500 text-[9px]"></i> <?= esc($p) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Kegiatan / Media -->
    <?php if (!empty($jurnal->kegiatan_belajar) || !empty($jurnal->media_alat)): ?>
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
        <?php if (!empty($jurnal->kegiatan_belajar)): ?>
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-1">
                <i class="bi bi-list-task text-blue-500 text-[9px]"></i> Alur Kegiatan Belajar
            </span>
            <p class="text-xs font-medium text-slate-700 leading-relaxed whitespace-pre-line m-0">
                <?= esc((string) $jurnal->kegiatan_belajar) ?>
            </p>
        </div>
        <?php endif; ?>
        <?php if (!empty($jurnal->media_alat)): ?>
        <div class="<?= !empty($jurnal->kegiatan_belajar) ? 'pt-3 border-t border-slate-100' : '' ?>">
            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Media &amp; Alat</span>
            <span class="text-xs font-bold text-slate-700"><?= esc($jurnal->media_alat) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Refleksi & Tindak Lanjut -->
    <?php if (!empty($jurnal->refleksi) || !empty($jurnal->tindak_lanjut)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php if (!empty($jurnal->refleksi)): ?>
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-1">
                <i class="bi bi-lightbulb text-amber-500 text-[9px]"></i> Refleksi Guru
            </span>
            <p class="text-xs font-medium text-slate-700 leading-relaxed whitespace-pre-line m-0">
                <?= esc((string) $jurnal->refleksi) ?>
            </p>
        </div>
        <?php endif; ?>
        <?php if (!empty($jurnal->tindak_lanjut)): ?>
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-1">
                <i class="bi bi-arrow-right-circle text-blue-500 text-[9px]"></i> Rencana Tindak Lanjut
            </span>
            <p class="text-xs font-medium text-slate-700 leading-relaxed whitespace-pre-line m-0">
                <?= esc((string) $jurnal->tindak_lanjut) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Kehadiran Siswa & Catatan -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
        <div>
            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                Status Kehadiran Siswa
            </span>
            <?php if (!empty($jurnal->siswa_absent) && strtolower(trim((string) $jurnal->siswa_absent)) !== 'nihil'): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-bold">
                <i class="bi bi-person-x"></i> Tidak Hadir: <?= esc($jurnal->siswa_absent) ?>
            </span>
            <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-xs font-bold">
                <i class="bi bi-check-circle-fill"></i> Semua siswa hadir
            </span>
            <?php endif; ?>
        </div>

        <?php if (!empty($jurnal->catatan_khusus)): ?>
        <div class="pt-3 border-t border-slate-100">
            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                Catatan Khusus
            </span>
            <p class="text-xs font-medium text-slate-700 m-0"><?= nl2br(esc((string) $jurnal->catatan_khusus)) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <p class="text-[10px] text-slate-400 text-center m-0 flex items-center justify-center gap-1">
        <i class="bi bi-info-circle"></i>
        Halaman ini hanya untuk membaca. Perubahan jurnal dilakukan oleh guru pemilik jurnal.
    </p>
</div>
<?= $this->endSection() ?>
