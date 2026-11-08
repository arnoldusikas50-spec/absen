<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-800 mb-0"><?= esc($subjudul ?? 'Tambah Data Guru') ?></h3>
            <p class="text-xs text-slate-400 mt-1 mb-0">Tambah akun dan profil guru baru secara manual atau import Excel</p>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>">Dashboard</a><span class="sep">/</span>
            <a href="<?= site_url('dataguru') ?>">Data Guru</a><span class="sep">/</span>
            <span class="active"><?= esc($subjudul ?? 'Tambah Guru') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">
    <div class="w-full">
        <div class="card-tw">
            <div class="card-tw-header">
                <ul class="nav-tabs" id="guruTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="manual-tab" data-ui-toggle="tab"
                            data-ui-target="#manual" type="button">
                            <i class="bi bi-person-plus mr-2"></i>Tambah Manual
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="import-tab" data-ui-toggle="tab" data-ui-target="#import"
                            type="button">
                            <i class="bi bi-file-earmark-spreadsheet mr-2"></i>Import Excel
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-tw-body">
                <div class="tab-content pt-4" id="guruTabsContent">
                    <!-- Tab Manual -->
                    <div class="tab-pane show active" id="manual" role="tabpanel">
                        <form id="formManual" autocomplete="off">
                            <?= csrf_field() ?>
                            <div class="alert-tw alert-info mb-4">
                                <i class="bi bi-info-circle mr-2"></i>
                                <span>Data Guru akan otomatis dibuatkan akun pengguna (User) dengan hak akses Guru.</span>
                            </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="form-label-tw">Nama Lengkap <span
                                                    class="text-red-500">*</span></label>
                                            <div>
                                                <input type="text" class="form-control-tw" name="nama_guru" required
                                                    maxlength="100" placeholder="Contoh: Randy Ikas, S.Pd">
                                            </div>
                                        </div>
                                        <div class="">
                                            <label class="form-label-tw">NIP / NUPTK</label>
                                            <div>
                                                <input type="text" inputmode="numeric" class="form-control-tw" name="nip"
                                                    placeholder="Angka saja (opsional)">
                                            </div>
                                        </div>
                                        <div class="">
                                            <label class="form-label-tw">Kode Guru</label>
                                            <div>
                                                <input type="text" class="form-control-tw" name="kode_guru" maxlength="30"
                                                    placeholder="Contoh: GR001 (opsional)">
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="form-label-tw">ID Telegram</label>
                                            <div>
                                                <input type="text" inputmode="numeric" class="form-control-tw font-mono" name="telegram_id"
                                                    maxlength="50" placeholder="123456789 (opsional)">
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">Ambil ID dari bot @userinfobot di Telegram. Bisa diisi nanti lewat menu Edit.</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="form-label-tw">No. WhatsApp</label>
                                            <div>
                                                <input type="tel" inputmode="tel" class="form-control-tw font-mono" name="wa_number"
                                                    maxlength="20" placeholder="6281234567890 (opsional)">
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">Tujuan notifikasi denda, surat teguran, dan alpha. Boleh ditulis 08 / +62 / 62 — otomatis dinormalisasi. Berbeda dari No. HP di data Dapodik.</p>
                                        </div>
                                        <div class="">
                                            <label class="form-label-tw">Username <span
                                                    class="text-red-500">*</span></label>
                                            <div>
                                                <input type="text" class="form-control-tw" name="username" required
                                                    minlength="4" maxlength="30" placeholder="Minimal 4 karakter">
                                            </div>
                                        </div>
                                        <div class="">
                                            <label class="form-label-tw">Password <span
                                                    class="text-red-500">*</span></label>
                                            <div>

                                                <input type="text" class="form-control-tw" name="password" required
                                                    minlength="5" maxlength="72" placeholder="Minimal 5 karakter">
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <button type="submit" class="btn-tw btn-primary w-full justify-center">
                                                <i class="bi bi-save mr-1"></i> Simpan Data Guru
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Tab Import -->
                            <div class="tab-pane" id="import" role="tabpanel">
                                <div class="flex justify-between items-center mb-6">
                                    <h6 class="font-bold text-slate-700">Import dari Excel / CSV</h6>
                                    <span class="text-[11px] text-slate-400 font-semibold">maksimal 1.000 baris per import</span>
                                </div>
                                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                                    <div class="xl:col-span-2 space-y-4">
                                        <div class="rounded-2xl border border-slate-200 p-4">
                                            <div class="flex items-center gap-2.5 mb-2.5">
                                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">1</span>
                                                <label class="form-label-tw !mb-0">Download Format</label>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mb-3">Unduh format resmi, isi data sesuai urutan kolom, lalu upload kembali.</p>
                                            <a href="<?= base_url('assets/tpl/format_guru.xlsx') ?>"
                                                class="btn-tw btn-tw-sm btn-success w-full justify-center">
                                                <i class="bi bi-download mr-1"></i> Download Format
                                            </a>
                                            <p class="text-[11px] text-slate-400 mt-2.5 leading-relaxed">
                                                Kolom: No • Nama • NIP • Kode Guru • Username • Password.<br>
                                                Password kosong = default <span class="font-mono font-bold">123456</span>.
                                            </p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 p-4">
                                            <div class="flex items-center gap-2.5 mb-2.5">
                                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">2</span>
                                                <label class="form-label-tw !mb-0">Upload File <span class="font-normal text-slate-400">(XLSX/CSV, maks 5MB)</span></label>
                                            </div>
                                            <form id="formUpload">
                                                <input type="file" id="input-file-events" name="upload_file" class="dropify"
                                                    data-allowed-file-extensions="xlsx csv"
                                                    data-max-file-size="5M" />
                                            </form>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 p-4">
                                            <div class="flex items-center gap-2.5 mb-2.5">
                                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">3</span>
                                                <label class="form-label-tw !mb-0">Proses ke Database</label>
                                            </div>
                                            <div id="import-summary" class="text-xs mb-2.5"></div>
                                            <button type="button" id="btn-import" class="btn-tw btn-primary hidden w-full justify-center">
                                                <i class="bi bi-upload mr-1"></i> Proses Import
                                            </button>
                                            <p class="text-[11px] text-slate-400 mt-2">Tombol aktif setelah file terbaca & ada baris valid.</p>
                                            <div id="import-result" class="mt-3"></div>
                                        </div>
                                        <div class="alert-tw alert-info !py-3">
                                            <p class="mb-1 font-bold text-xs">Petunjuk Import:</p>
                                            <ul class="mb-0 pl-4 text-[11px] space-y-0.5">
                                                <li>Gunakan file format yang disediakan (XLSX/CSV).
                                                <li>Kolom NIP harus angka (boleh kosong).
                                                <li>Username min 4 karakter; password kosong = default <span class="font-mono font-bold">123456</span>.
                                                <li>Urutan kolom jangan diubah.
                                                <li>Baris tak valid dilewati & dilaporkan.
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="xl:col-span-3">
                                        <div class="rounded-2xl border border-slate-200 overflow-hidden h-full flex flex-col">
                                            <div class="flex justify-between items-center px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                                                <h6 class="font-bold mb-0 text-slate-600 text-xs uppercase tracking-wider"><i class="bi bi-eye mr-2"></i>Preview Data</h6>
                                                <span id="preview-count-guru" class="badge-tw badge-secondary">0 baris terdeteksi</span>
                                            </div>
                                            <div id="preview-container" class="bg-slate-50/50 p-3 flex-1" style="min-height: 320px; max-height: 560px; overflow-y: auto;">
                                                <div id="import-placeholder" class="text-center text-slate-400 py-16">
                                                    <i class="bi bi-file-earmark-excel text-5xl block mb-4 opacity-40"></i>
                                                    <p class="text-xs font-semibold mb-0">Preview muncul di sini setelah Anda memilih file.</p>
                                                    <p class="text-[11px] mt-1 mb-0">Baris valid hijau &nbsp;•&nbsp; baris invalid merah</p>
                                                </div>
                                                <div id="file-preview"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Include libraries from assets if available or use CDN for development -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script src="<?= base_url('assets/plugins/dropify/js/dropify.min.js') ?>"></script>

<script>
    let formDataGuru = new FormData();
    let dataGuru = [];

    $(document).ready(function () {
        // Form Manual Submit
        $('#formManual').submit(function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalText = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');

            $.ajax({
                url: '<?= site_url("dataguru/create") ?>',
                type: 'POST',
                data: $(this).serialize(),
                success: function (res) {
                    if (res.status) {
                        // Kanal notifikasi dilaporkan terpisah: guru bisa
                        // tersimpan dengan baik tapi nomornya ditolak
                        // format, atau ID Telegram-nya sudah dipakai akun
                        // lain. Tanpa ini, admin mengira semuanya beres.
                        const kanal = Array.isArray(res.kanal) && res.kanal.length ? res.kanal : null;
                        if (kanal) {
                            Swal.fire('Sebagian gagal', 'Guru tersimpan, tetapi:<br>' + kanal.join('<br>'), 'warning')
                                .then(() => { window.location.href = '<?= site_url("dataguru") ?>'; });
                        } else {
                            Swal.fire('Berhasil', 'Data Guru berhasil ditambahkan', 'success').then(() => {
                                window.location.href = '<?= site_url("dataguru") ?>';
                            });
                        }
                    } else {
                        let msg = 'Gagal menyimpan data';
                        if (res.errors) {
                            msg = Object.values(res.errors).join('<br>');
                        } else if (res.message) {
                            msg = res.message;
                        }
                        Swal.fire('Gagal', msg, 'error');
                    }
                },
                error: function (xhr) {
                    console.error(xhr);
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        const dropify = $('.dropify').dropify({
            messages: {
                'default': 'Seret & letakkan file di sini atau klik',
                'replace': 'Seret & letakkan atau klik untuk mengganti',
                'remove': 'Hapus',
                'error': 'Maaf, file tidak valid.'
            },
            error: {
                'fileSize': 'Ukuran file terlalu besar (maks {{ value }}).',
                'imageFormat': 'Format tidak diizinkan ({{ value }} saja).',
                'fileExtension': 'Format tidak diizinkan ({{ value }} saja).'
            }
        });

        dropify.on('dropify.afterClear', function () {
            resetImportState();
        });
        dropify.on('dropify.errors', function () {
            Swal.fire('File Ditolak', 'Gunakan file XLSX/CSV maksimal 5MB.', 'warning');
            resetImportState();
        });

        function resetImportState() {
            $('#file-preview').empty();
            $('#import-placeholder').show();
            $('#import-summary').empty();
            $('#import-result').empty();
            $('#btn-import').addClass('hidden');
            dataGuru = [];
        }

        $('#input-file-events').on('change', function () {
            const file = this.files[0];
            if (!file) return;

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!['xlsx', 'csv'].includes(ext)) {
                Swal.fire('Format Salah', 'Gunakan file XLSX atau CSV dari format yang disediakan.', 'warning');
                resetImportState();
                $(this).val('');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 5MB.', 'warning');
                resetImportState();
                $(this).val('');
                return;
            }

            $('#import-placeholder').hide();
            $('#import-result').empty();
            $('#file-preview').html('<div class="text-center py-8"><div class="spinner-border text-blue-600" role="status"></div><p class="mt-2">Membaca file...</p></div>');

            const reader = ext === 'csv' ? readCsvFile(file) : readExcelFile(file);
            reader.then(data => {
                const sheet = data[data.sheets[0]];
                displayPreview(sheet);
            }).catch(err => {
                console.error(err);
                Swal.fire('Error', 'Gagal membaca file. Pastikan file tidak rusak.', 'error');
                resetImportState();
            });
        });

        function readExcelFile(file) {
            return getDataFromExcel(file);
        }

        // Parser CSV sederhana (pemisah ; atau , otomatis, dukung tanda kutip)
        function readCsvFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    try {
                        let text = String(reader.result || '').replace(/^\uFEFF/, '');
                        const lines = text.split(/\r\n|\r|\n/).filter(l => l.trim() !== '');
                        if (lines.length < 2) { reject(new Error('empty')); return; }
                        const delim = (lines[0].split(';').length >= lines[0].split(',').length) ? ';' : ',';
                        const parseLine = (line) => {
                            const out = [];
                            let cur = '', inQ = false;
                            for (let i = 0; i < line.length; i++) {
                                const c = line[i];
                                if (inQ) {
                                    if (c === '"') {
                                        if (line[i + 1] === '"') { cur += '"'; i++; }
                                        else inQ = false;
                                    } else cur += c;
                                } else if (c === '"') inQ = true;
                                else if (c === delim) { out.push(cur); cur = ''; }
                                else cur += c;
                            }
                            out.push(cur);
                            return out.map(s => s.trim());
                        };
                        const headerCells = parseLine(lines[0]);
                        const cols = {
                            name: 'Sheet1',
                            header: headerCells.map((h, i) => ({ label: h || ('Kolom ' + (i + 1)), value: i + 1 })),
                            rows: []
                        };
                        for (let r = 1; r < lines.length; r++) {
                            const cells = parseLine(lines[r]);
                            if (cells.every(c => c === '')) continue;
                            const obj = {};
                            cells.forEach((c, i) => { obj[i + 1] = c; });
                            cols.rows.push(obj);
                        }
                        resolve({ sheets: ['Sheet1'], Sheet1: cols });
                    } catch (e) { reject(e); }
                };
                reader.onerror = reject;
                reader.readAsText(file, 'UTF-8');
            });
        }

        function getDataFromExcel(file) {
            return new Promise((resolve, reject) => {
                const wb = new ExcelJS.Workbook();
                const reader = new FileReader();
                reader.onload = () => {
                    const buffer = reader.result;
                    wb.xlsx.load(buffer).then(workbook => {
                        let dataFiles = { sheets: [] };
                        workbook.eachSheet((sheet) => {
                            dataFiles.sheets.push(sheet.name);
                            let cols = { name: sheet.name, header: [], rows: [] };
                            sheet.eachRow({ includeEmpty: false }, (row, rowIndex) => {
                                if (rowIndex === 1) {
                                    row.eachCell((cell, colNumber) => {
                                        cols.header.push({ label: cell.text, value: colNumber });
                                    });
                                } else {
                                    let obj = {};
                                    row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                                        obj[colNumber] = cell.text;
                                    });
                                    cols.rows.push(obj);
                                }
                            });
                            dataFiles[sheet.name] = cols;
                        });
                        resolve(dataFiles);
                    });
                };
                reader.readAsArrayBuffer(file);
            });
        }

        // Validasi baris di sisi klien (cermin aturan server) untuk preview
        function validateImportRow(row, seenUser, seenNip) {
            const nama = (row['2'] || '').trim();
            const nip = (row['3'] || '').trim();
            const username = (row['5'] || '').trim();
            const password = (row['6'] || '').trim();
            if (!nama && !username && !password && !nip && !(row['4'] || '').trim()) return { empty: true };
            if (!nama) return { ok: false, msg: 'Nama wajib diisi' };
            if (username.length < 4 || username.length > 30 || !/^[A-Za-z0-9_.-]+$/.test(username)) {
                return { ok: false, msg: 'Username 4–30 karakter valid' };
            }
            const low = username.toLowerCase();
            if (seenUser[low]) return { ok: false, msg: 'Username ganda dalam file' };
            if (password !== '' && password.length < 5) return { ok: false, msg: 'Password min 5 karakter' };
            if (nip !== '' && !/^\d+$/.test(nip)) return { ok: false, msg: 'NIP harus angka' };
            if (nip !== '' && seenNip[nip]) return { ok: false, msg: 'NIP ganda dalam file' };
            seenUser[low] = true;
            if (nip !== '') seenNip[nip] = true;
            return { ok: true };
        }

        function displayPreview(sheet) {
            let html = '<table class="table-tw w-full text-xs"><thead><tr class="bg-blue-600 text-white">';
            sheet.header.forEach(h => html += `<th class="px-2 py-2 text-center">${escPreview(h.label)}</th>`);
            html += '<th class="px-2 py-2 text-center">Status</th></tr></thead><tbody>';

            dataGuru = sheet.rows;
            const seenUser = {}, seenNip = {};
            let okCount = 0, badCount = 0, emptyCount = 0;
            sheet.rows.slice(0, 100).forEach(row => {
                const v = validateImportRow(row, seenUser, seenNip);
                if (v.empty) { emptyCount++; return; }
                const bad = !v.ok;
                if (bad) badCount++; else okCount++;
                html += `<tr class="${bad ? 'bg-red-50' : ''}">`;
                sheet.header.forEach(h => {
                    const val = row[h.value] || '';
                    html += `<td class="px-2 py-1.5 border-t border-slate-100">${escPreview(val)}</td>`;
                });
                html += `<td class="px-2 py-1.5 border-t border-slate-100 text-center">` +
                    (bad
                        ? `<span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold" title="${escPreview(v.msg)}">Invalid</span>`
                        : `<span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">Valid</span>`) +
                    `</td></tr>`;
            });

            if (sheet.rows.length > 100) {
                html += `<tr><td colspan="${sheet.header.length + 1}" class="text-center italic px-2 py-2">... dan ${sheet.rows.length - 100} data lainnya</td></tr>`;
            }

            html += '</tbody></table>';
            $('#file-preview').html(html);
            $('#preview-count-guru').html(
                `${sheet.rows.length} baris terdeteksi &nbsp;•&nbsp; <span class="text-emerald-600 font-bold">${okCount} valid</span>` +
                (badCount ? ` &nbsp;•&nbsp; <span class="text-red-600 font-bold">${badCount} invalid</span>` : ''));
            $('#import-summary').html(
                `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 mr-1.5"><i class="bi bi-check-circle"></i> ${okCount} valid</span>` +
                (badCount ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-bold border border-red-200"><i class="bi bi-x-circle"></i> ${badCount} invalid (dilewati)</span>` : '')
            );
            $('#btn-import').toggleClass('hidden', okCount === 0);
            if (okCount === 0) {
                Swal.fire('Tidak Valid', 'Tidak ada baris valid untuk diimport. Periksa kembali file Anda.', 'warning');
            }
        }

        function escPreview(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        $('#btn-import').click(function () {
            if (dataGuru.length === 0) return;

            Swal.fire({
                title: 'Konfirmasi Import',
                text: `Apakah Anda yakin ingin mengimport ${dataGuru.length} data guru?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Import!'
            }).then(result => {
                if (result.value) {
                    Swal.fire({
                        title: 'Mengimport Data',
                        text: 'Silakan tunggu...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '<?= site_url("dataguru/do_import") ?>',
                        type: 'POST',
                        data: {
                            guru: dataGuru,
                            <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                        },
                        success: (res) => {
                            if (res.status) {
                                const skipped = res.errors && res.errors.length
                                    ? `<div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">`
                                      + `<p class="font-bold mb-1">${res.skipped} baris dilewati:</p><ul class="list-disc pl-4 space-y-0.5">`
                                      + res.errors.map(e => `<li>${escPreview(e)}</li>`).join('') + `</ul></div>`
                                    : '';
                                $('#import-result').html(
                                    `<div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-bold">`
                                    + `<i class="bi bi-check-circle-fill mr-1"></i> ${escPreview(res.message || ('Berhasil mengimport ' + res.total + ' data guru'))}</div>` + skipped);
                                Swal.fire('Berhasil', res.message || `Berhasil mengimport ${res.total} data guru`, 'success').then(() => {
                                    if (!res.skipped) window.location.href = '<?= site_url("dataguru") ?>';
                                });
                            } else {
                                const errs = res.errors && res.errors.length
                                    ? '<ul class="text-left inline-block list-disc pl-4">' + res.errors.map(e => `<li>${escPreview(e)}</li>`).join('') + '</ul>'
                                    : escPreview(res.message || 'Terjadi kesalahan saat mengimport data');
                                Swal.fire({ icon: 'error', title: 'Gagal', html: errs });
                            }
                        },
                        error: () => Swal.fire('Error', 'Terjadi kesalahan sistem', 'error')
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>