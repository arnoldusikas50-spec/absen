<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- â”€â”€ Top App Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs">
    <div class="flex items-center gap-2.5">
        <a href="<?= site_url('guru/jurnal') ?>" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="Kembali ke Riwayat">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Detail Jurnal</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">Rincian Pelaksanaan KBM</p>
        </div>
    </div>
    <div class="flex items-center gap-1.5">
        <a href="<?= site_url('guru/jurnal/cetak/' . $jurnal->id_jurnal) ?>" target="_blank"
           class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition-colors no-underline" title="Cetak Jurnal">
            <i class="bi bi-printer"></i>
        </a>
        <a href="<?= site_url('guru/jurnal/edit/' . $jurnal->id_jurnal) ?>"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white text-xs font-bold no-underline transition-all shadow-xs active:scale-95">
            <i class="bi bi-pencil text-xs"></i>
            <span>Edit</span>
        </a>
    </div>
</div>

<!-- â”€â”€ Main Content Container â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="px-4 pb-20 space-y-3.5 mt-2.5">

    <!-- â”€â”€ Hero Info Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-2.5">
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-brand-50 text-brand-700 rounded-lg text-[11px] font-bold">
                <i class="bi bi-calendar3 text-[10px]"></i>
                <span><?= buat_tanggal(date('l, d F Y', strtotime($jurnal->tanggal))) ?></span>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                Jam <?= esc($jurnal->jam_ke) ?>
            </span>
        </div>

        <div>
            <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md text-[10px] font-bold mb-1">
                <i class="bi bi-building text-[9px]"></i> <?= esc($jurnal->nama_kelas ?? 'Kelas') ?>
            </div>
            <h1 class="text-base font-black text-slate-900 leading-snug m-0">
                <?= esc($jurnal->nama_mapel ?? 'Mata Pelajaran') ?>
            </h1>
        </div>

        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span class="font-semibold text-slate-700"><?= esc($jurnal->nama_guru ?? 'Guru') ?></span>
            <span class="text-[11px]">NIP: <?= esc($jurnal->nip ?: '-') ?></span>
        </div>
    </div>

    <?php
        $vStatus = (string) ($jurnal->validasi_status ?? '');
        $vCatatan = trim((string) ($jurnal->validasi_catatan ?? ''));
        $vPada = trim((string) ($jurnal->validasi_pada ?? ''));
    ?>
    <?php if (in_array($vStatus, ['APPROVED', 'REJECTED', 'PENDING'], true)): ?>
        <?php
            $vClass = match ($vStatus) {
                'APPROVED' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                'REJECTED' => 'text-orange-700 bg-orange-50 border-orange-200',
                default   => 'text-slate-500 bg-slate-50 border-slate-200',
            };
            $vIcon = match ($vStatus) {
                'APPROVED' => 'bi-patch-check-fill',
                'REJECTED' => 'bi-exclamation-octagon-fill',
                default   => 'bi-shield-exclamation',
            };
        ?>
        <div class="rounded-2xl border px-3.5 py-2.5 text-[11px] font-bold flex items-start gap-2 <?= $vClass ?>">
            <i class="bi <?= $vIcon ?> text-xs mt-0.5 shrink-0"></i>
            <span class="leading-snug">
                <?php if ($vStatus === 'APPROVED'): ?>
                    Isi jurnal ini sudah divalidasi oleh guru piket / kepala sekolah<?= $vPada !== '' ? ' pada ' . date('d/m/Y H:i', strtotime($vPada)) : '' ?>.
                <?php elseif ($vStatus === 'REJECTED'): ?>
                    Perlu perbaikan<?= $vCatatan !== '' ? ': ' . esc($vCatatan) : ' menurut validasi guru piket / kepala sekolah.' ?>
                    Sunting jurnal ini — setelah tersimpan, isinya kembali masuk antrean validasi.<?= $vPada !== '' ? ' (dinilai ' . date('d/m/Y H:i', strtotime($vPada)) . ')' : '' ?>
                <?php else: ?>
                    Menunggu validasi guru piket / kepala sekolah.
                    <?= $vCatatan !== '' ? ' Catatan validator sebelumnya: ' . esc($vCatatan) : '' ?>
                <?php endif; ?>
            </span>
        </div>
    <?php endif; ?>

    <!-- â”€â”€ Materi Pokok â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1">
            <i class="bi bi-bookmark-fill text-brand-500 text-[9px]"></i> Materi Pokok
        </span>
        <p class="text-xs sm:text-sm font-bold text-slate-800 leading-relaxed m-0">
            <?= nl2br(esc($jurnal->materi_pokok)) ?>
        </p>
    </div>

    <!-- â”€â”€ Tujuan Pembelajaran â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <?php if (!empty($jurnal->tujuan_pembelajaran)): ?>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1">
            <i class="bi bi-bullseye text-indigo-500 text-[9px]"></i> Tujuan Pembelajaran (TP)
        </span>
        <p class="text-xs font-semibold text-slate-700 leading-relaxed m-0">
            <?= nl2br(esc($jurnal->tujuan_pembelajaran)) ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- â”€â”€ Model Belajar & Asesmen â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="grid grid-cols-2 gap-2">
        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs">
            <span class="text-[9px] font-bold uppercase tracking-wide text-slate-400 block mb-0.5">Model Belajar</span>
            <span class="text-xs font-bold text-purple-700 line-clamp-2"><?= esc($jurnal->model_belajar ?: 'Reguler') ?></span>
        </div>
        <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs">
            <span class="text-[9px] font-bold uppercase tracking-wide text-slate-400 block mb-0.5">Bentuk Asesmen</span>
            <span class="text-xs font-bold text-emerald-700 line-clamp-2"><?= esc($jurnal->jenis_asesmen ?: 'Formatif') ?></span>
        </div>
    </div>

    <!-- â”€â”€ Profil Pelajar Pancasila â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <?php if (!empty($profil_list)): ?>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-2">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">
            Dimensi Profil Pelajar Pancasila
        </span>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach ($profil_list as $p): ?>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs font-bold shadow-2xs">
                <i class="bi bi-star-fill text-amber-500 text-[9px]"></i> <?= esc($p) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- â”€â”€ Alur Kegiatan Belajar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <?php if (!empty($jurnal->kegiatan_belajar)): ?>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1">
            <i class="bi bi-list-task text-brand-500 text-[9px]"></i> Alur Kegiatan Belajar
        </span>
        <p class="text-xs font-medium text-slate-700 leading-relaxed whitespace-pre-line m-0">
            <?= esc($jurnal->kegiatan_belajar) ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- â”€â”€ Refleksi & Tindak Lanjut â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <?php if (!empty($jurnal->refleksi) || !empty($jurnal->tindak_lanjut)): ?>
    <div class="space-y-2">
        <?php if (!empty($jurnal->refleksi)): ?>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                <i class="bi bi-lightbulb text-amber-500 text-[9px]"></i> Refleksi Guru
            </span>
            <p class="text-xs font-medium text-slate-700 leading-relaxed whitespace-pre-line m-0">
                <?= esc($jurnal->refleksi) ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if (!empty($jurnal->tindak_lanjut)): ?>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrow-right-circle text-brand-500 text-[9px]"></i> Rencana Tindak Lanjut
            </span>
            <p class="text-xs font-medium text-slate-700 leading-relaxed whitespace-pre-line m-0">
                <?= esc($jurnal->tindak_lanjut) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- â”€â”€ Kehadiran Siswa & Media â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-2.5">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">
                Status Kehadiran Siswa:
            </span>
            <?php $absenLabelTampil = (string) ($absen_label ?? ''); ?>
            <?php if (!empty($absen_jumlah)): ?>
            <div class="inline-flex items-center gap-1.5 max-w-full px-3 py-1 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-bold">
                <i class="bi bi-person-x text-sm shrink-0"></i>
                <span class="truncate" title="<?= esc($absenLabelTampil) ?>">Tidak Hadir (<?= (int) $absen_jumlah ?>): <?= esc($absenLabelTampil ?: ((int) $absen_jumlah . ' siswa')) ?></span>
            </div>
            <?php else: ?>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-xs font-bold">
                <i class="bi bi-check-circle-fill text-sm"></i>
                <span>Semua Siswa Hadir (Nihil)</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">
                Media &amp; Alat Belajar:
            </span>
            <span class="text-xs font-bold text-slate-700">
                <?= esc($jurnal->media_alat ?: 'Standar Kelas (Papan Tulis & Modul)') ?>
            </span>
        </div>

        <?php if (!empty($jurnal->catatan_khusus)): ?>
        <div class="pt-2 border-t border-slate-100">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-0.5">
                Catatan Khusus:
            </span>
            <p class="text-xs text-slate-700 font-medium m-0">
                <?= esc($jurnal->catatan_khusus) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- â”€â”€ Bottom Action Buttons â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="flex items-center gap-2 pt-2">
        <a href="<?= site_url('guru/jurnal/cetak/' . $jurnal->id_jurnal) ?>" target="_blank"
           class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold text-center transition-all no-underline active:scale-95 flex items-center justify-center gap-1.5">
            <i class="bi bi-printer"></i>
            <span>Cetak PDF</span>
        </a>
        <a href="<?= site_url('guru/jurnal/edit/' . $jurnal->id_jurnal) ?>"
           class="flex-1 py-2.5 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white rounded-xl text-xs font-bold text-center transition-all shadow-xs no-underline active:scale-95 flex items-center justify-center gap-1.5">
            <i class="bi bi-pencil"></i>
            <span>Edit Jurnal</span>
        </a>
    </div>

</div>
<?= $this->endSection() ?>
