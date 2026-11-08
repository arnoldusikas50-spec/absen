<!doctype html>
<html lang="id">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta charset="utf-8">
    <title>Detail Jurnal</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #0f172a; }
        .title { font-size: 16px; font-weight: 800; margin: 0 0 6px; }
        .meta { color: #475569; margin: 0 0 14px; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 6px 0; vertical-align: top; }
        .label { color: #64748b; width: 32%; }
        .value { font-weight: 700; }
        .section { font-weight: 800; margin: 14px 0 6px; }
        .muted { color: #475569; font-weight: 400; }
        .hr { height: 1px; background: #e2e8f0; margin: 10px 0; }
        .list { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>
    <div class="title">Rincian Detail Jurnal Guru</div>
    <div class="meta">
        Tahun Pelajaran: <?= esc($tp_active->tahun ?? '-') ?> • Semester: <?= esc($smt_active->smt ?? '-') ?>
    </div>

    <div class="box">
        <table class="grid">
            <tr>
                <td class="label">Nama Guru</td>
                <td class="value"><?= esc($j->nama_guru ?? '-') ?></td>
            </tr>
            <tr>
                <td class="label">Mata Pelajaran</td>
                <td class="value"><?= esc($j->nama_mapel ?? '-') ?></td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td class="value"><?= esc($j->nama_kelas ?? '-') ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal</td>
                <td class="value"><?= esc($j->tanggal ?? '-') ?></td>
            </tr>
            <tr>
                <td class="label">Waktu Mengajar</td>
                <td class="value">
                    <?= esc($j->jam_ke ?? '-') ?>
                    <?php if (!empty($j->jam_mulai_slot) && !empty($j->jam_selesai_slot)): ?>
                        <span class="muted">(<?= esc(substr($j->jam_mulai_slot, 0, 5)) ?>–<?= esc(substr($j->jam_selesai_slot, 0, 5)) ?>)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="label">Topik / Materi</td>
                <td class="value"><?= esc($j->materi_pokok ?? '-') ?></td>
            </tr>
        </table>

        <div class="hr"></div>

        <table class="grid">
            <tr>
                <td class="label">Jumlah Siswa (Total)</td>
                <td class="value"><?= (int) $total_siswa ?></td>
            </tr>
            <tr>
                <td class="label">Jumlah Hadir</td>
                <td class="value"><?= (int) $jumlah_hadir ?></td>
            </tr>
            <tr>
                <td class="label">Jumlah Tidak Hadir</td>
                <td class="value"><?= (int) $jumlah_tidak_hadir ?></td>
            </tr>
        </table>

        <?php if (!empty($siswa_tidak_hadir)): ?>
            <div class="section">Daftar Tidak Hadir</div>
            <ul class="list">
                <?php foreach ($siswa_tidak_hadir as $n): ?>
                    <li><?= esc($n) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="box">
        <div class="section">Catatan Guru</div>
        <div><?= nl2br(esc((string)($j->catatan_khusus ?? ''))) ?></div>

        <div class="section">Tujuan Pembelajaran</div>
        <div><?= nl2br(esc((string)($j->tujuan_pembelajaran ?? ''))) ?></div>

        <div class="section">Kegiatan Belajar</div>
        <div><?= nl2br(esc((string)($j->kegiatan_belajar ?? ''))) ?></div>

        <div class="section">Refleksi</div>
        <div><?= nl2br(esc((string)($j->refleksi ?? ''))) ?></div>

        <div class="section">Tindak Lanjut</div>
        <div><?= nl2br(esc((string)($j->tindak_lanjut ?? ''))) ?></div>
    </div>
</body>
</html>

