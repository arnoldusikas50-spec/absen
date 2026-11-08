<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<?php $list = $list ?? []; ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-database text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Backup dan Restore') ?></span>
            </h3>
            <p class="text-sm text-slate-500 mt-1 mb-0">Kelola cadangan database &amp; file uploads aplikasi.</p>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline">
                <i class="bi bi-house-door mr-1"></i>Dashboard
            </a>
            <span class="sep">/</span>
            <a href="<?= site_url('settings') ?>" class="no-underline">Pengaturan</a>
            <span class="sep">/</span>
            <span class="active"><?= esc($subjudul ?? 'Backup') ?></span>
        </nav>
    </div>
</div>

<div class="content-area space-y-4">

    <!-- Aksi backup -->
    <div class="card-tw">
        <div class="card-tw-body">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-tw btn-primary js-backup-btn" onclick="processBackup(this)">
                    <i class="bi bi-cloud-upload"></i><span>Backup Semua</span>
                </button>
                <button type="button" class="btn-tw btn-light js-backup-btn" onclick="processBackupDb(this)">
                    <i class="bi bi-database"></i><span>Backup Database</span>
                </button>
                <button type="button" class="btn-tw btn-light js-backup-btn" onclick="processBackupFile(this)">
                    <i class="bi bi-file-earmark-zip"></i><span>Backup File</span>
                </button>
            </div>
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span id="progressMsg" class="text-xs text-slate-500">Siap. Pilih jenis backup.</span>
                    <span id="progressPct" class="text-xs font-semibold text-slate-400">0%</span>
                </div>
                <div class="progress-tw" style="height:6px">
                    <div id="backupProgressBar" class="progress-bar-tw" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-2 mb-0">Jangan tutup halaman selama proses berjalan. Restore .sql akan menimpa data saat ini.</p>
            </div>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="card-tw">
        <div class="card-tw-header justify-between gap-3">
            <h6><i class="bi bi-clock-history text-slate-400"></i>Riwayat <span class="font-normal text-slate-400">· <?= count($list) ?></span></h6>
            <input type="search" id="searchBackup" class="form-control-tw !w-44 !py-1.5 !text-xs" placeholder="Cari file…" autocomplete="off">
        </div>
        <div class="card-tw-body !p-0">
            <?php if (!empty($list)): ?>
            <div class="table-responsive-tw">
                <table id="database" class="table-tw">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th class="text-right" style="width:90px">Ukuran</th>
                            <th class="text-right" style="width:130px">Waktu</th>
                            <th class="text-right" style="width:200px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="backupTableBody">
                        <?php foreach ($list as $value):
                            $fname = $value['src'] ?? ($value['nama'] . '.' . $value['type']);
                            $isSql = !empty($value['is_sql']);
                        ?>
                        <tr data-name="<?= esc(strtolower($fname)) ?>">
                            <td>
                                <div class="font-medium text-slate-700 text-[13px] truncate" title="<?= esc($fname) ?>"><?= esc($fname) ?></div>
                                <div class="text-[11px] text-slate-400"><?= date('d M Y H:i', (int) $value['tgl']) ?></div>
                            </td>
                            <td class="text-right text-slate-500 whitespace-nowrap"><?= esc($value['size'] ?? '-') ?></td>
                            <td class="text-right text-slate-400 text-xs whitespace-nowrap"><?= date('d M Y', (int) $value['tgl']) ?></td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="<?= site_url('settings/dbManager/download') ?>?src=<?= esc(rawurlencode($value['src']), 'attr') ?>"
                                        class="btn-tw btn-tw-sm btn-light" title="Download">
                                        <i class="bi bi-download"></i>Download
                                    </a>
                                    <?php if ($isSql): ?>
                                    <button type="button" data-src="<?= esc($fname, 'attr') ?>" onclick="restore(this.dataset.src)"
                                        class="btn-tw btn-tw-sm btn-light" title="Restore">
                                        <i class="bi bi-arrow-counterclockwise"></i>Restore
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" data-src="<?= esc($fname, 'attr') ?>" onclick="hapus(this.dataset.src)"
                                        class="btn-tw btn-tw-sm btn-light !text-red-600" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="backupEmptySearch" class="hidden">
                            <td colspan="4" class="text-center !py-6 text-xs text-slate-400">Tidak ada file yang cocok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between px-4 py-2.5 border-t border-slate-100">
                <span class="text-xs text-slate-400"><?= count($list) ?> file tersimpan di writable/backups</span>
                <button type="button" class="btn-tw btn-tw-sm btn-light !text-red-600" onclick="hapusBackup()">
                    <i class="bi bi-trash3"></i>Hapus Semua
                </button>
            </div>
            <?php else: ?>
            <div class="text-center px-6 py-10">
                <p class="text-sm text-slate-500 mb-3">Belum ada backup. Klik <b>Backup Semua</b> untuk mulai.</p>
                <button type="button" class="btn-tw btn-primary js-backup-btn" onclick="processBackup(this)">
                    <i class="bi bi-cloud-upload"></i><span>Backup Sekarang</span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
(function () {
    const BASE = (typeof base_url !== 'undefined' ? base_url : '<?= rtrim(base_url(), '/') ?>/');
    const URL = {
        db: BASE + 'settings/dbManager/backupdb',
        files: BASE + 'settings/dbManager/backupdata',
        delAll: BASE + 'settings/dbManager/del_backup',
        delOne: BASE + 'settings/dbManager/hapusbackup',
        restore: BASE + 'settings/dbManager/restore'
    };
    let busy = false;

    function csrfData(extra) {
        const data = Object.assign({}, extra || {});
        if (typeof CSRF_NAME !== 'undefined' && typeof CSRF_HASH !== 'undefined') {
            data[CSRF_NAME] = CSRF_HASH;
        }
        return data;
    }

    function refreshCsrf(resp) {
        if (!resp) return;
        const newHash = resp.csrfHash || resp.csrf_hash;
        const newName = resp.csrfName || resp.csrf_name;
        if (typeof CSRF_HASH !== 'undefined' && newHash) CSRF_HASH = newHash;
        if (typeof CSRF_NAME !== 'undefined' && newName) CSRF_NAME = newName;
    }

    function errMsg(xhr, fallback) {
        try {
            const j = xhr.responseJSON;
            if (j && j.message) return j.message;
        } catch (e) {}
        return (xhr && xhr.statusText ? xhr.statusText : fallback) || fallback;
    }

    window.updateProgress = function (pct, message) {
        pct = Math.max(0, Math.min(100, Number(pct) || 0));
        const bar = document.getElementById('backupProgressBar');
        const label = document.getElementById('progressPct');
        const msg = document.getElementById('progressMsg');
        if (bar) {
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', String(pct));
        }
        if (label) label.textContent = pct + '%';
        if (msg && message !== undefined) msg.textContent = message;
    };

    function setBusy(isBusy, activeBtn) {
        busy = isBusy;
        document.querySelectorAll('.js-backup-btn').forEach(function (btn) {
            if (isBusy) {
                if (!btn.dataset.orig) btn.dataset.orig = btn.innerHTML;
                btn.setAttribute('disabled', 'disabled');
                if (btn === activeBtn) {
                    btn.innerHTML = '<span class="spinner-tw"></span><span>Memproses…</span>';
                }
            } else {
                btn.removeAttribute('disabled');
                if (btn.dataset.orig) btn.innerHTML = btn.dataset.orig;
            }
        });
    }

    function ajaxPost(url, data) {
        return $.ajax({ type: 'POST', url: url, data: data, dataType: 'json' });
    }

    function tampilkanHasilBackup(respon, fallback) {
        // HTTP 200 belum berarti backup lengkap: server menandai dump
        // parsial/zip yang gagal ditutup dengan status false.
        const ok = !!(respon && respon.status);
        updateProgress(ok ? 100 : 0, (respon && respon.message) || fallback);
        Swal.fire({
            title: ok ? 'Berhasil' : 'Backup Tidak Lengkap',
            text: (respon && respon.message) || fallback,
            icon: ok ? 'success' : 'warning'
        }).then(() => window.location.reload());
    }

    window.processBackupDb = function (btn) {
        if (busy) return;
        setBusy(true, btn || null);
        updateProgress(10, 'Memulai backup database…');
        Swal.fire({
            title: 'Backup Database',
            text: 'Silakan tunggu, jangan tutup halaman ini…',
            allowEscapeKey: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        ajaxPost(URL.db, csrfData({})).done(function (response) {
            refreshCsrf(response);
            tampilkanHasilBackup(response, 'Backup database selesai.');
        }).fail(function (xhr) {
            updateProgress(0, 'Backup database gagal.');
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + errMsg(xhr, 'Backup gagal'), 'error');
        }).always(function () { setBusy(false); });
    };

    window.processBackupFile = function (btn) {
        if (busy) return;
        setBusy(true, btn || null);
        updateProgress(10, 'Memulai backup file uploads…');
        Swal.fire({
            title: 'Backup File Uploads',
            text: 'Silakan tunggu, jangan tutup halaman ini…',
            allowEscapeKey: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        ajaxPost(URL.files, csrfData({})).done(function (response) {
            refreshCsrf(response);
            tampilkanHasilBackup(response, 'Backup file selesai.');
        }).fail(function (xhr) {
            updateProgress(0, 'Backup file gagal.');
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + errMsg(xhr, 'Backup gagal'), 'error');
        }).always(function () { setBusy(false); });
    };

    window.processBackup = function (btn) {
        if (busy) return;
        setBusy(true, btn || null);
        updateProgress(5, 'Langkah 1/2: backup database…');
        Swal.fire({
            title: 'Backup Semua',
            text: 'Langkah 1/2: database… jangan tutup halaman ini.',
            allowEscapeKey: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        ajaxPost(URL.db, csrfData({})).done(function (response) {
            refreshCsrf(response);
            if (!response || !response.status) {
                updateProgress(0, response && response.message ? response.message : 'Backup database tidak lengkap.');
                Swal.fire({ title: 'Backup Tidak Lengkap', text: (response && response.message) || 'Backup database gagal.', icon: 'warning' })
                    .then(() => window.location.reload());
                setBusy(false);
                return;
            }
            updateProgress(50, (response.message || 'Database OK.') + ' Lanjut backup file…');
            Swal.update({ text: 'Langkah 2/2: file uploads…' });
            ajaxPost(URL.files, csrfData({})).done(function (resp2) {
                refreshCsrf(resp2);
                if (!resp2 || !resp2.status) {
                    updateProgress(50, resp2 && resp2.message ? resp2.message : 'Backup file tidak lengkap.');
                    Swal.fire({ title: 'Backup Tidak Lengkap', text: (resp2 && resp2.message) || 'Database berhasil, tetapi backup file gagal.', icon: 'warning' })
                        .then(() => window.location.reload());
                } else {
                    updateProgress(100, resp2.message || 'Semua backup selesai.');
                    Swal.fire({ title: 'Berhasil', text: 'Database & file berhasil dibackup.', icon: 'success' })
                        .then(() => window.location.reload());
                }
            }).fail(function (xhr) {
                updateProgress(50, 'Database OK, backup file gagal.');
                Swal.fire('Sebagian Gagal', 'Database berhasil, tetapi file gagal: ' + errMsg(xhr, 'Backup file gagal'), 'warning')
                    .then(() => window.location.reload());
            }).always(function () { setBusy(false); });
        }).fail(function (xhr) {
            updateProgress(0, 'Backup gagal.');
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + errMsg(xhr, 'Backup gagal'), 'error');
            setBusy(false);
        });
    };

    window.hapusBackup = function () {
        Swal.fire({
            title: 'Hapus Semua Backup?',
            html: "<span class='text-red-600'><b>Semua backup manual (db_backup_ / files_backup_) dihapus permanen dan tidak bisa dikembalikan!</b></span><br><br>"
                + "Arsip otomatis dari cron (<code>db_*.sql.gz</code>) <b>tidak disentuh</b> — hapus satu per satu lewat tombol di tabel bila memang perlu.",
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Semua', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            Swal.fire({ title: 'Menghapus…', text: 'Silakan tunggu.', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            ajaxPost(URL.delAll, csrfData({})).done(function (respon) {
                refreshCsrf(respon);
                if (respon.status) {
                    Swal.fire({ title: 'Berhasil', text: respon.message || 'Semua backup dihapus.', icon: 'success' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ title: 'Gagal', text: respon.message || 'Gagal menghapus.', icon: 'error' });
                }
            }).fail(function (xhr) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan: ' + errMsg(xhr, 'Gagal menghapus'), icon: 'error' });
            });
        });
    };

    window.hapus = function (src) {
        Swal.fire({
            title: 'Hapus file ini?',
            html: 'File <b>' + $('<div>').text(src).html() + '</b> akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Hapus', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            Swal.fire({ text: 'Menghapus…', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            ajaxPost(URL.delOne, csrfData({ src: src })).done(function (respon) {
                refreshCsrf(respon);
                if (respon.status) {
                    Swal.fire({ title: 'Berhasil', text: respon.message || 'File dihapus.', icon: 'success' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ title: 'Gagal', text: respon.message || 'Gagal menghapus.', icon: 'error' });
                }
            }).fail(function (xhr) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan: ' + errMsg(xhr, 'Gagal menghapus'), icon: 'error' });
            });
        });
    };

    window.restore = function (src) {
        Swal.fire({
            title: 'Restore Database?',
            html: 'Restore file <b>' + $('<div>').text(src).html() + '</b>?<br><br><span class="text-red-600"><b>PERINGATAN:</b><br>1. Data saat ini akan DITIMPA.<br>2. Wajib backup dulu.<br>3. Tidak dapat dibatalkan.</span>',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#16a34a', cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Restore!', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            Swal.fire({ title: 'Merestore…', text: 'Jangan tutup halaman ini.', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            ajaxPost(URL.restore, csrfData({ src: src })).done(function (respon) {
                refreshCsrf(respon);
                if (respon.status) {
                    Swal.fire({ title: 'Berhasil', text: respon.message || 'Restore selesai.', icon: 'success' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ title: 'Gagal', text: respon.message || 'Restore gagal.', icon: 'error' });
                }
            }).fail(function (xhr) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan server: ' + errMsg(xhr, 'Restore gagal'), icon: 'error' });
            });
        });
    };

    // Pencarian client-side
    function applyFilter() {
        const q = (document.getElementById('searchBackup')?.value || '').toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('#backupTableBody tr[data-name]').forEach(function (row) {
            const show = !q || (row.dataset.name || '').includes(q);
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        const emptyRow = document.getElementById('backupEmptySearch');
        if (emptyRow) emptyRow.classList.toggle('hidden', visible !== 0);
    }
    document.getElementById('searchBackup')?.addEventListener('input', applyFilter);

    updateProgress(0, 'Siap. Pilih jenis backup.');
})();
</script>
<?= $this->endSection(); ?>
