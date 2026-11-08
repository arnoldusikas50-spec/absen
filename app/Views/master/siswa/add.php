<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Content Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-file-earmark-excel"></i> Import Excel
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($subjudul ?? 'Import Data Siswa') ?></h1>
            <p class="text-xs text-slate-500 mt-1">Download template, isi data, upload kembali — maksimal 2.000 baris per import.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('datasiswa') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

<!-- Main Content -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 p-5 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="bi bi-file-earmark-excel text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">
                    <?= esc($subjudul ?? 'Import Data Siswa') ?>
                </h2>
                <span class="ml-auto text-[11px] text-slate-400 font-semibold">Maksimal 2.000 baris per import</span>
            </div>
            <div class="card-tw-body">
                <div class="alert-tw alert-info" role="alert">
                    <div class="flex items-center">
                        <i class="bi bi-info-circle-fill text-2xl mr-3"></i>
                        <div>
                            <strong>Catatan Penting!</strong><br>
                            Gunakan format Excel yang telah disediakan untuk mengimport data. Mohon download template
                            terlebih dahulu sebelum mengupload data.
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                    <div class="xl:col-span-2 space-y-4">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">1</span>
                                <label class="form-label-tw !mb-0">Download Template</label>
                            </div>
                            <p class="text-[11px] text-slate-500 mb-3">Unduh format resmi, isi data sesuai urutan kolom, lalu upload kembali.</p>
                            <?php $url = 'uploads/import/format/format_siswa.xlsx'; ?>
                            <a href="<?= base_url($url) ?>" class="btn-tw btn-success w-full justify-center">
                                <i class="bi bi-download mr-2"></i>Download Format
                            </a>
                            <p class="text-[11px] text-slate-400 mt-2.5 leading-relaxed">
                                Kolom: No • NISN • NIS • Nama • JK • Username • Password • Kelas Awal • Tahun Masuk.<br>
                                Username kosong = NIS, password kosong = NIS.<br>
                                <strong>Kelas Awal</strong> akan otomatis memasukkan siswa ke rombel yang sesuai (jika ada).
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">2</span>
                                <label class="form-label-tw !mb-0">Upload File <span class="font-normal text-slate-400">(XLSX/CSV, maks 10MB)</span></label>
                            </div>
                            <div id="dropzoneSiswa" class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 hover:border-emerald-400 hover:bg-emerald-50/40 transition-colors cursor-pointer p-6 text-center">
                                <i class="bi bi-cloud-arrow-up text-3xl text-slate-300 block mb-2"></i>
                                <p class="text-xs font-bold text-slate-600">Seret & letakkan file di sini atau <span class="text-emerald-600 underline">klik untuk memilih</span></p>
                                <p id="dropzoneFileName" class="hidden mt-2 font-mono text-[11px] font-bold text-emerald-700 break-all"></p>
                                <button type="button" id="btnGantiFile" class="hidden mt-2 text-[11px] font-bold text-slate-500 hover:text-red-500 bg-transparent border-none cursor-pointer">
                                    <i class="bi bi-x-circle mr-1"></i>Ganti file
                                </button>
                            </div>
                            <input type="file" id="input-file-excel" class="hidden"
                                accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv">
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">3</span>
                                <label class="form-label-tw !mb-0">Proses ke Database</label>
                            </div>
                            <?php if (isset($kelas)): ?>
                                <div class="alert-tw alert-info !py-2.5 !px-3.5 mb-3" role="alert">
                                    <div class="flex items-center gap-2 text-xs">
                                        <i class="bi bi-check-circle-fill text-base"></i>
                                        <div>Siswa otomatis masuk <strong>Kelas: <?= esc($kelas->nama_kelas ?? '-') ?></strong></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <button id="submit-excel" type="button" class="btn-tw btn-primary w-full justify-center" disabled>
                                <i class="bi bi-cloud-upload mr-2"></i>Upload ke Database
                            </button>
                            <p class="text-[11px] text-slate-400 mt-2">Tombol aktif setelah file terbaca & ada baris valid.</p>
                        </div>

                        <div id="error-excel" class="alert-tw alert-info"></div>
                    </div>

                    <div class="xl:col-span-3">
                        <div class="rounded-2xl border border-slate-200 overflow-hidden h-full flex flex-col">
                            <div class="flex justify-between items-center px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                                <h5 class="font-bold mb-0 text-slate-600 text-xs uppercase tracking-wider"><i class="bi bi-eye mr-2"></i>Preview Data</h5>
                                <span id="preview-count" class="badge-tw badge-secondary">0 baris terdeteksi</span>
                            </div>
                            <div class="overflow-auto" style="max-height: 560px; min-height: 320px;">
                                <table id="tableprev" class="table-tw">
                                    <thead class="sticky top-0 bg-white shadow-sm">
                                        <tr></tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <div id="file-preview-placeholder" class="text-center py-16">
                                    <div class="py-8 text-slate-400">
                                        <i class="bi bi-file-earmark-spreadsheet text-5xl block mb-4 opacity-40"></i>
                                        <p class="text-xs font-semibold mb-0">Preview muncul di sini setelah Anda memilih file.</p>
                                        <p class="text-[11px] mt-1 mb-0">Baris valid hijau &nbsp;•&nbsp; baris invalid merah</p>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script>
    const idKelasAktif = '<?= isset($kelas) ? $kelas->id_kelas : 0 ?>';
    let dataSiswa = [];

    $(document).ready(function () {
        const $dz = $('#dropzoneSiswa');
        const $input = $('#input-file-excel');
        $dz.on('click', function (e) {
            if ($(e.target).closest('#btnGantiFile').length) return;
            $input.trigger('click');
        });
        $dz.on('dragover dragenter', function (e) {
            e.preventDefault();
            $dz.addClass('!border-emerald-500 !bg-emerald-50');
        });
        $dz.on('dragleave drop', function (e) {
            e.preventDefault();
            $dz.removeClass('!border-emerald-500 !bg-emerald-50');
        });
        $dz.on('drop', function (e) {
            const files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
            if (files && files.length) {
                $input[0].files = files;
                $input.trigger('change');
            }
        });
        $('#btnGantiFile').on('click', function (e) {
            e.stopPropagation();
            $input.val('');
            $('#dropzoneFileName').addClass('hidden').text('');
            $(this).addClass('hidden');
            $('#tableprev thead').html('');
            $('#tableprev tbody').html('');
            $('#preview-count').text('0 baris terdeteksi');
            $('#file-preview-placeholder').removeClass('hidden');
            $('#submit-excel').prop('disabled', true);
            dataSiswa = [];
        });
        $('#input-file-excel').on('change', async function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!['xlsx', 'csv'].includes(ext)) {
                Swal.fire('Format Salah', 'Gunakan file XLSX atau CSV dari template yang disediakan.', 'warning');
                e.target.value = '';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 10MB.', 'warning');
                e.target.value = '';
                return;
            }

            $('#tableprev thead').html('');
            $('#tableprev tbody').html('');
            $('#file-preview-placeholder').addClass('hidden');
            $('#dropzoneFileName').removeClass('hidden').text('📄 ' + file.name);
            $('#btnGantiFile').removeClass('hidden');

            Swal.fire({
                title: "Membaca file",
                text: "Memproses data...",
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                let header = [], rows = [];
                if (ext === 'csv') {
                    ({ header, rows } = await readCsvSiswa(file));
                } else {
                    const workbook = new ExcelJS.Workbook();
                    const buffer = await file.arrayBuffer();
                    await workbook.xlsx.load(buffer);

                    const sheet = workbook.worksheets[0];
                    sheet.eachRow((row, rowIndex) => {
                        const cleanRow = [];
                        row.eachCell({ includeEmpty: true }, function (cell, colNumber) {
                            let val = cell.value;
                            if (typeof val === 'object' && val !== null) {
                                if (val.text) val = val.text;
                                else if (val.result) val = val.result;
                                else if (val.richText) val = val.richText.map(t => t.text).join('');
                            }
                            cleanRow[colNumber - 1] = val; // make it 0-based
                        });

                        for (let i = 0; i < cleanRow.length; i++) {
                            if (cleanRow[i] === undefined) cleanRow[i] = "";
                        }

                        if (rowIndex === 1) {
                            header = cleanRow;
                        } else {
                            rows.push(cleanRow);
                        }
                    });
                }

                if (rows.length === 0) {
                    Swal.fire("Peringatan", "File tampak kosong atau tidak berisi data.", "warning");
                    $('#submit-excel').prop('disabled', true);
                    return;
                }

                renderPreview(header, rows);
                remapAndValidate(rows);

                Swal.close();
                $('#submit-excel').prop('disabled', dataSiswa.length === 0);
            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Gagal membaca file. Pastikan format valid (.xlsx/.csv)", "error");
            }
        });

        // Parser CSV (pemisah ; atau , otomatis, dukung tanda kutip) — baris 1 = header
        async function readCsvSiswa(file) {
            const text = (await file.text()).replace(/^\uFEFF/, '');
            const lines = text.split(/\r\n|\r|\n/).filter(l => l.trim() !== '');
            if (lines.length < 2) throw new Error('empty');
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
            const header = parseLine(lines[0]);
            const rows = [];
            for (let r = 1; r < lines.length; r++) {
                const cells = parseLine(lines[r]);
                if (cells.every(c => c === '')) continue;
                rows.push(cells);
            }
            return { header, rows };
        }

        $('#submit-excel').on('click', function () {
            uploadData();
        });
    });

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Cermin aturan server: nama ≥3, NIS angka wajib; username default NIS
    function validateSiswaRow(row, seenNis, seenUser) {
        const str = (v) => String(v ?? '').trim();
        const nama = str(row[3]), nis = str(row[2]), nisn = str(row[1]);
        let username = str(row[5] || nis);
        if (!nama && !nis && !nisn && !username) return { empty: true };
        if (nama.length < 3) return { ok: false, msg: 'Nama min 3 karakter' };
        if (!nis || !/^\d+$/.test(nis)) return { ok: false, msg: 'NIS wajib angka' };
        if (seenNis[nis]) return { ok: false, msg: 'NIS ganda dalam file' };
        if (!username) return { ok: false, msg: 'Username kosong' };
        if (seenUser[username.toLowerCase()]) return { ok: false, msg: 'Username ganda dalam file' };
        seenNis[nis] = true;
        seenUser[username.toLowerCase()] = true;
        return { ok: true };
    }

    function renderPreview(header, rows) {
        let hHtml = '<tr>';
        for (let i = 0; i < header.length; i++) {
            hHtml += `<th class="whitespace-nowrap">${escHtml(header[i]) || `Col ${i + 1}`}</th>`;
        }
        hHtml += '<th class="whitespace-nowrap">Status</th></tr>';
        $('#tableprev thead').html(hHtml);

        const seenNis = {}, seenUser = {};
        const flags = rows.map(r => validateSiswaRow(r, seenNis, seenUser));
        const okCount = flags.filter(f => f.ok).length;
        const badCount = flags.filter(f => !f.ok && !f.empty).length;

        let bHtml = '';
        const limit = 50;
        rows.slice(0, limit).forEach((row, idx) => {
            const f = flags[idx];
            if (f.empty) return;
            const bad = !f.ok;
            bHtml += `<tr class="${bad ? 'bg-red-50' : ''}">`;
            for (let i = 0; i < header.length; i++) {
                bHtml += `<td class="whitespace-nowrap text-sm">${escHtml(row[i])}</td>`;
            }
            bHtml += `<td class="whitespace-nowrap text-center">` +
                (bad
                    ? `<span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold" title="${escHtml(f.msg)}">Invalid</span>`
                    : `<span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">Valid</span>`) +
                `</td></tr>`;
        });

        if (rows.length > limit) {
            bHtml += `<tr><td colspan="${header.length + 1}" class="text-center py-4 text-slate-400 italic bg-slate-50">... dan ${rows.length - limit} baris lainnya ...</td></tr>`;
        }

        $('#tableprev tbody').html(bHtml);
        $('#preview-count').html(
            `${rows.length} baris terdeteksi &nbsp;•&nbsp; <span class="text-emerald-600 font-bold">${okCount} valid</span>` +
            (badCount ? ` &nbsp;•&nbsp; <span class="text-red-600 font-bold">${badCount} invalid</span>` : ''));
    }

    // Mapping indexes based on template
    // Assuming Template: No, NISN, NIS, Nama, JK, Username, Password, ...
    // Indexes (0-based): 
    // 0: No
    // 1: NISN (Required)
    // 2: NIS (Required)
    // 3: Nama (Required)
    // 4: JK
    function remapAndValidate(rows) {
        dataSiswa = [];
        let errors = 0;

        rows.forEach((row, idx) => {
            // Check essential fields: NISN (1), NIS (2), Nama (3)
            // Adjust index if template is different. Based on view code:
            // nisn: row[1], nis: row[2], nama: row[3]

            // Loose check
            const nisn = row[1];
            const nis = row[2];
            const nama = row[3];

            if (nama && (nis || nisn)) {
                dataSiswa.push({
                    nisn: row[1] || '',
                    nis: row[2] || '',
                    nama: row[3] || '',
                    jenis_kelamin: row[4] || 'L',
                    username: row[5] || row[2], // Default user = nis
                    password: row[6] || row[2], // Default pass = nis
                    kelas_awal: row[7] || '',
                    tahun_masuk: row[8] || '',
                    sekolah_asal: row[9] || '',
                    tempat_lahir: row[10] || '',
                    tanggal_lahir: row[11] || '',
                    agama: row[12] || '',
                    hp: row[13] || '',
                    email: row[14] || '',
                    anak_ke: row[15] || '',
                    status_keluarga: row[16] || '',
                    alamat: row[17] || '',
                });
            } else {
                // Only count as error if row is not totally empty
                /* if(row.some(c => c !== '')) */ errors++;
            }
        });

        if (errors > 0 && dataSiswa.length > 0) {
            $('#error-excel').html(`<i class="bi bi-exclamation-triangle-fill mr-2"></i> <strong>Peringatan:</strong> Ada ${errors} baris yang diabaikan karena data Wajib (Nama, NIS/NISN) tidak lengkap.`).removeClass('hidden');
        } else if (dataSiswa.length === 0) {
            $('#error-excel').html(`<i class="bi bi-x-circle-fill mr-2"></i> <strong>Error:</strong> Tidak ada data valid yang ditemukan. Pastikan kolom Nama dan NIS/NISN terisi.`).removeClass('hidden');
        } else {
            $('#error-excel').addClass('hidden');
        }
    }

    async function uploadData() {
        if (dataSiswa.length === 0) return;

        Swal.fire({
            title: "Mengupload Data",
            html: `Sedang menyimpan <b>${dataSiswa.length}</b> siswa ke database...<br>Jangan tutup halaman ini.`,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Use JSON instead of FormData to avoid max_input_vars limit
        const payload = {
            id_kelas: idKelasAktif,
            siswa: dataSiswa,
            [CSRF_NAME]: CSRF_HASH
        };

        try {
            const response = await fetch('<?= site_url('datasiswa/do_import') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            // Check if response is JSON
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                const result = await response.json();
                if (result.status) {
                    const skipped = result.errors && result.errors.length
                        ? `<div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 text-left">`
                          + `<p class="font-bold mb-1">${result.skipped} baris dilewati:</p><ul class="list-disc pl-4 space-y-0.5">`
                          + result.errors.map(e => `<li>${escHtml(e)}</li>`).join('') + `</ul></div>`
                        : '';
                    
                    const kelasInfo = result.assigned_to_kelas > 0 
                        ? `<div class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800 text-center">
                             <strong>${result.assigned_to_kelas} siswa</strong> berhasil dimasukkan ke kelas sesuai kode kelas awal
                           </div>` 
                        : '';
                    
                    Swal.fire({
                        title: "Selesai!",
                        html: escHtml(result.message || `${result.total || dataSiswa.length} Data siswa berhasil diimport.`) + skipped + kelasInfo,
                        icon: "success",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#3085d6"
                    }).then(() => {
                        if (!result.skipped) window.location.href = '<?= site_url('datasiswa') ?>';
                    });
                } else {
                    const errs = result.errors && result.errors.length
                        ? '<ul class="text-left inline-block list-disc pl-4">' + result.errors.map(e => `<li>${escHtml(e)}</li>`).join('') + '</ul>'
                        : escHtml(result.message || "Terjadi kesalahan saat menyimpan data");
                    Swal.fire({
                        title: "Gagal",
                        html: errs,
                        icon: "error"
                    });
                }
            } else {
                // Handle non-JSON response (likely HTML error page)
                const text = await response.text();
                console.error("Server Error Response:", text);
                Swal.fire("Server Error", "Terjadi kesalahan di server (500). Cek Console untuk detail.", "error");
            }

        } catch (err) {
            console.error(err);
            Swal.fire("Koneksi Gagal", "Gagal menghubungi server. Periksa koneksi internet Anda.", "error");
        }
    }
</script>
<?= $this->endSection() ?>