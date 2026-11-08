<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    .top-app-bar {
        background: var(--m3-surface);
        padding-top: calc(16px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }
    .letter-card { transition: box-shadow 0.15s, transform 0.12s; }
    .letter-card:active { transform: scale(0.99); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Top App Bar ─────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <a href="<?= site_url('presensi/notif') ?>"
            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-rose-50 text-slate-600 no-underline -ml-1">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold text-slate-800 m-0 leading-none">Surat Teguran</h1>
    </div>
    <?php if (!empty($letters)): ?>
    <span class="min-w-[22px] h-[22px] px-1.5 flex items-center justify-center rounded-full bg-orange-500 text-white text-[10px] font-extrabold">
        <?= count($letters) ?>
    </span>
    <?php endif; ?>
</div>

<!-- ── Filter & Search ─────────────────────────────── -->
<div class="px-4 mt-3 space-y-3">

    <!-- Period selector -->
    <form method="get" action="<?= site_url('presensi/kepsek/teguran') ?>"
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest m-0 mb-2">
            <i class="bi bi-funnel-fill me-1 text-orange-400"></i> Filter Periode
        </p>
        <div class="grid grid-cols-2 gap-2">
            <!-- Bulan -->
            <div>
                <label class="form-label-tw">Bulan</label>
                <select name="bulan" class="m3-input" onchange="this.form.submit()">
                    <?php
                    $blnIdx = [
                        1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
                        5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
                        9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                    ];
                    foreach ($blnIdx as $k => $v): ?>
                        <option value="<?= $k ?>" <?= (int)$k === (int)$bulan ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Tahun -->
            <div>
                <label class="form-label-tw">Tahun</label>
                <select name="tahun" class="m3-input" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y'); $y >= attendance_tahun_tertua(); $y--): ?>
                        <option value="<?= $y ?>" <?= (int)$y === (int)$tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Search -->
    <div class="relative">
        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
        <input type="text" id="searchInput"
            class="m3-input pl-9"
            placeholder="Cari nama pegawai...">
    </div>

</div>

<!-- ── List ────────────────────────────────────────── -->
<div class="px-4 py-4 space-y-3" id="lettersList">

    <?php if (empty($letters)): ?>
    <!-- Empty state -->
    <div class="flex flex-col items-center justify-center text-center py-14 px-6">
        <div class="w-24 h-24 rounded-full bg-green-50 flex items-center justify-center mb-4">
            <i class="bi bi-envelope-check-fill text-green-400 text-4xl"></i>
        </div>
        <h2 class="font-bold text-slate-700 text-base m-0">Periode Ini Bersih!</h2>
        <p class="text-sm text-slate-400 mt-1 m-0">
            Belum ada surat teguran yang diterbitkan pada
            <?php
            $blnNames = [
                1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
                5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
                9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
            ];
            echo ($blnNames[(int)$bulan] ?? $bulan) . ' ' . $tahun;
            ?>.
        </p>
    </div>

    <?php else: ?>

    <p class="text-xs text-slate-400 font-medium px-1" id="result-count">
        <i class="bi bi-envelope-exclamation-fill text-orange-400 me-1"></i>
        <?= count($letters) ?> surat teguran diterbitkan
    </p>

    <?php foreach ($letters as $row):
        /* ── Level badge ── */
        $level = (int)($row->level ?? 0);
        [$levelLabel, $levelCls, $levelIcon, $levelBarCls] = match(true) {
            $level >= 10 => ['Tingkat III', 'bg-red-100 text-red-700 border-red-200',       'bi-exclamation-octagon-fill', 'bg-red-500'],
            $level >= 7  => ['Tingkat II',  'bg-orange-100 text-orange-700 border-orange-200','bi-exclamation-triangle-fill','bg-orange-500'],
            default      => ['Tingkat I',   'bg-amber-100 text-amber-700 border-amber-200',  'bi-exclamation-circle-fill', 'bg-amber-400'],
        };

        /* ── Nama & NIP ── */
        $nama = (string)($row->nama_guru ?? 'Pegawai');
        $nip  = (string)($row->nip ?? '-');

        /* ── Tanggal ── */
        $tglStr = !empty($row->created_at) ? date('d M Y', strtotime($row->created_at)) : '-';
        $tglRel = !empty($row->created_at) ? (function($ts) {
            $diff = time() - $ts;
            if ($diff < 3600)      return floor($diff/60) . ' mnt lalu';
            if ($diff < 86400)     return floor($diff/3600) . ' jam lalu';
            if ($diff < 2592000)   return floor($diff/86400) . ' hari lalu';
            return date('d M Y', $ts);
        })(strtotime($row->created_at)) : '-';

        /* ── Jumlah hari (dari snapshot jumlah_alpha, fallback ke level) ── */
        $isBatal    = (($row->status ?? 'aktif') === 'dibatalkan');
        $jumlahHari = (int)($row->jumlah_alpha ?? $row->absen_count ?? $row->jumlah_alpa ?? $level);

        /* ── Avatar initial ── */
        $avatarSrc = \App\Services\AvatarService::tampilan((string) ($row->guru_foto ?? ''), $nama, '#f97316');
    ?>
    <div class="letter-card letter-item bg-white rounded-3xl border border-orange-100 shadow-sm overflow-hidden <?= $isBatal ? 'opacity-60' : '' ?>"
        data-name="<?= esc(strtolower($nama)) ?>">

        <!-- Left accent bar -->
        <div class="flex items-stretch">
            <div class="w-1 flex-shrink-0 <?= $isBatal ? 'bg-slate-400' : $levelBarCls ?> rounded-l-3xl"></div>

            <div class="flex-1 p-4">
                <!-- Header row -->
                <div class="flex items-center gap-3 mb-3">
                    <img src="<?= $avatarSrc ?>" alt="<?= esc($nama) ?>"
                        class="w-11 h-11 rounded-xl object-cover flex-shrink-0 shadow-sm">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 text-sm m-0 leading-snug truncate"><?= esc($nama) ?></p>
                        <p class="text-[11px] text-slate-400 m-0 mt-0.5">
                            <i class="bi bi-person-badge text-[10px] me-0.5"></i><?= esc($nip) ?>
                        </p>
                    </div>
                    <?php if ($isBatal): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold flex-shrink-0 border bg-slate-100 text-slate-600 border-slate-200">
                        <i class="bi bi-x-circle-fill text-[9px]"></i> Dibatalkan
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold flex-shrink-0 border <?= $levelCls ?>">
                        <i class="bi <?= $levelIcon ?> text-[9px]"></i>
                        <?= $levelLabel ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Stats row -->
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 flex items-center gap-2 bg-slate-50 rounded-xl px-3 py-2">
                        <i class="bi bi-calendar3 text-slate-400 text-sm flex-shrink-0"></i>
                        <div>
                            <p class="text-[9px] text-slate-400 font-semibold uppercase m-0">Diterbitkan</p>
                            <p class="text-xs font-bold text-slate-700 m-0"><?= $tglStr ?></p>
                        </div>
                    </div>
                    <?php if ($jumlahHari > 0): ?>
                    <div class="flex flex-col items-center justify-center w-14 h-[52px] rounded-xl <?= str_replace(['bg-red-', 'bg-orange-', 'bg-amber-'], ['bg-red-5', 'bg-orange-5', 'bg-amber-5'], $levelBarCls) ?>0 border border-current border-opacity-20 flex-shrink-0"
                        style="<?= $level >= 10 ? 'background:#fef2f2' : ($level >= 7 ? 'background:#fff7ed' : 'background:#fffbeb') ?>">
                        <span class="text-xl font-extrabold leading-none <?= $level >= 10 ? 'text-red-600' : ($level >= 7 ? 'text-orange-600' : 'text-amber-600') ?>"><?= $jumlahHari ?></span>
                        <span class="text-[9px] font-bold uppercase <?= $level >= 10 ? 'text-red-400' : ($level >= 7 ? 'text-orange-400' : 'text-amber-400') ?>">hari</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Timestamp & Download -->
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-slate-400 flex items-center gap-1">
                        <i class="bi bi-clock text-[9px]"></i><?= $tglRel ?>
                    </span>
                    <a href="<?= site_url('presensi/kepsek/teguran/download/' . (int)$row->id) ?>"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-600 text-white text-[11px] font-bold no-underline shadow-sm shadow-rose-200 hover:bg-rose-700 active:scale-95 transition-all">
                        <i class="bi bi-file-earmark-pdf-fill text-sm"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- No search results placeholder (hidden by default) -->
    <div id="no-results" class="hidden text-center py-10 px-6">
        <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="bi bi-search text-slate-400 text-2xl"></i>
        </div>
        <p class="font-bold text-slate-600 text-sm m-0">Tidak ditemukan</p>
        <p class="text-xs text-slate-400 mt-1">Coba kata kunci yang berbeda.</p>
    </div>

    <?php endif; ?>
</div>

<!-- bottom spacer -->
<div class="h-4"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    const $input   = $('#searchInput');
    const $items   = $('.letter-item');
    const $noRes   = $('#no-results');
    const $count   = $('#result-count');
    const total    = $items.length;

    $input.on('input', function () {
        const q = $.trim($(this).val()).toLowerCase();
        let visible = 0;

        if (!q) {
            $items.removeClass('hidden');
            $noRes.addClass('hidden');
            if ($count.length) $count.html(
                '<i class="bi bi-envelope-exclamation-fill text-orange-400 me-1"></i>' +
                total + ' surat teguran diterbitkan'
            );
            return;
        }

        $items.each(function () {
            const name = ($(this).data('name') || '').toString();
            if (name.includes(q)) {
                $(this).removeClass('hidden');
                visible++;
            } else {
                $(this).addClass('hidden');
            }
        });

        $noRes.toggleClass('hidden', visible > 0);
        if ($count.length) $count.html(
            '<i class="bi bi-search text-slate-400 me-1"></i>' +
            visible + ' hasil untuk "<strong>' + $('<div>').text(q).html() + '</strong>"'
        );
    });
});
</script>
<?= $this->endSection() ?>
