<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Content Header (pola kartu/dapodik/izin) -->
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-database-gear"></i> Update Massal
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= esc($judul ?? 'Update Data Siswa') ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= esc($subjudul ?? 'Update Data Siswa') ?> — download per kelas, edit file, upload kembali.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= site_url('datasiswa') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

        <div class="alert-tw alert-info" role="alert">
            <i class="bi bi-exclamation-triangle-fill mr-2"></i>
            <div><strong>Penting!</strong> Untuk memperbarui data massal, download data per kelas terlebih dahulu, edit
            filenya, lalu upload kembali. Maksimal 2.000 baris data / 500 foto per batch.</div>
        </div>

        <div class="flex flex-wrap gap-4">
            <div class="w-full md:w-5/12 space-y-4">
                <div class="card-tw"><div class="card-tw-header">
                        <h3 class="font-bold text-slate-700"><i class="bi bi-database-gear mr-2"></i>Pengelolaan Data & Foto</h3>
                    </div>
                    <div class="card-tw-body space-y-5">
                        <div>
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">1</span>
                                <label class="font-bold block !mb-0"><i class="bi bi-download mr-1"></i>Download Data Per Kelas</label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 xl:grid-cols-2 gap-2 overflow-auto pr-1" style="max-height: 220px;">
                                <?php if (isset($kelas) && count($kelas) > 0): ?>
                                    <?php foreach ($kelas as $id => $kls): ?>
                                        <button type="button" data-dl-excel="<?= (int) $id ?>" data-nama="<?= esc($kls, 'attr') ?>"
                                            class="btn-tw btn-success btn-tw-sm w-full justify-center" title="Download Excel <?= esc($kls, 'attr') ?>">
                                            <i class="bi bi-file-earmark-excel mr-1"></i>
                                            <?= esc($kls) ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="w-full text-xs text-slate-400">Belum ada data kelas</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="opacity-10">

                        <div>
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-7 h-7 rounded-lg bg-cyan-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">2</span>
                                <label class="font-bold block !mb-0 text-cyan-700"><i class="bi bi-image mr-1"></i>Update Foto (Batch Word)</label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 xl:grid-cols-2 gap-2 overflow-auto pr-1" style="max-height: 180px;">
                                <?php if (isset($kelas) && count($kelas) > 0): ?>
                                    <?php foreach ($kelas as $id => $kls): ?>
                                        <button type="button" data-dl-word="<?= (int) $id ?>" data-nama="<?= esc($kls, 'attr') ?>"
                                            class="btn-tw btn-info btn-tw-sm w-full justify-center" title="Download Word <?= esc($kls, 'attr') ?>">
                                            <i class="bi bi-file-earmark-word mr-1"></i>
                                            <?= esc($kls) ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="w-full text-xs text-slate-400">Belum ada data kelas</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="opacity-10">

                        <div>
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">3</span>
                                <label class="font-bold !mb-0 block"><i class="bi bi-cloud-arrow-up mr-1"></i>Upload File Perubahan</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-tw">Excel (.xlsx) untuk update data profil</label>
                                <div id="dzExcel" class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 hover:border-emerald-400 hover:bg-emerald-50/40 transition-colors cursor-pointer p-4 text-center">
                                    <i class="bi bi-file-earmark-excel text-2xl text-slate-300 block mb-1"></i>
                                    <p class="text-[11px] font-bold text-slate-600">Klik / seret file Excel ke sini</p>
                                    <p id="dzExcelName" class="hidden mt-1 font-mono text-[11px] font-bold text-emerald-700 break-all"></p>
                                </div>
                                <input type="file" id="input-excel" class="hidden" accept=".xlsx">
                            </div>
                            <div>
                                <label class="form-label-tw">Word (.docx) untuk update foto</label>
                                <div id="dzWord" class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 hover:border-cyan-400 hover:bg-cyan-50/40 transition-colors cursor-pointer p-4 text-center">
                                    <i class="bi bi-file-earmark-word text-2xl text-slate-300 block mb-1"></i>
                                    <p class="text-[11px] font-bold text-slate-600">Klik / seret file Word ke sini</p>
                                    <p id="dzWordName" class="hidden mt-1 font-mono text-[11px] font-bold text-cyan-700 break-all"></p>
                                </div>
                                <input type="file" id="input-word" class="hidden" accept=".docx">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1">
                <div class="card-tw"><div class="card-tw-header !flex !items-center !justify-between !gap-2 flex-wrap">
                        <h6 class="font-bold text-slate-700"><i class="bi bi-eye mr-2"></i>Preview Perubahan <span id="preview-count-update" class="badge-tw badge-secondary ml-1">0 baris</span></h6>
                        <button id="btn-submit-update" class="btn-tw btn-tw-sm btn-primary" disabled>
                            <i class="bi bi-cloud-upload mr-1"></i> Simpan
                        </button>
                    </div>
                    <div class="p-0">
                        <div id="preview-container" class="table-tw" style="max-height: 600px; min-height: 300px; overflow-y: auto;">
                            <div class="text-center py-12 text-slate-400">
                                <i class="bi bi-file-earmark-arrow-up text-5xl mb-6 block opacity-25"></i>
                                <p class="text-xs font-semibold">File preview akan tampil di sini setelah Anda memilih file.</p>
                                <p class="text-[11px] mt-1">Baris valid hijau • baris tanpa ID dilewati</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<div id="hidden-word-content" class="hidden"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/excel/exceljs.js') ?>"></script>
<script src="<?= base_url('assets/app/js/excel/js-excel-template.min.js') ?>"></script>
<script src="<?= base_url('assets/app/js/mammoth.browser.min.js') ?>"></script>
<script src="<?= base_url('assets/app/js/FileSaver.min.js') ?>"></script>
<script src="<?= base_url('assets/app/js/html-docx.js') ?>"></script>
<script src="<?= base_url('assets/app/js/convert-area.js') ?>"></script>

<script>
    let dataSiswa = [];
    let uploadType = null;

    $(document).ready(function () {
        // Dropzone: klik + drag & drop untuk kedua input
        [['#dzExcel', '#input-excel', '#dzExcelName'], ['#dzWord', '#input-word', '#dzWordName']].forEach(([dz, inp, lbl]) => {
            const $dz = $(dz), $in = $(inp);
            $dz.on('click', () => $in.trigger('click'));
            $dz.on('dragover dragenter', (e) => { e.preventDefault(); $dz.addClass('!border-emerald-500 !bg-emerald-50'); });
            $dz.on('dragleave drop', (e) => { e.preventDefault(); $dz.removeClass('!border-emerald-500 !bg-emerald-50'); });
            $dz.on('drop', (e) => {
                const files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
                if (files && files.length) { $in[0].files = files; $in.trigger('change'); }
            });
        });

        $('#input-excel').on('change', async function (e) {
            const file = e.target.files[0];
            if (!file) return;
            $('#dzExcelName').removeClass('hidden').text('📄 ' + file.name);
            $('#dzWordName').addClass('hidden').text('');
            if (!/\.xlsx$/i.test(file.name)) {
                Swal.fire('Format Salah', 'Gunakan file .xlsx hasil download per kelas.', 'warning');
                e.target.value = '';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 10MB.', 'warning');
                e.target.value = '';
                return;
            }

            Swal.fire({ title: 'Membaca Excel...', didOpen: () => Swal.showLoading() });

            try {
                const workbook = new ExcelJS.Workbook();
                await workbook.xlsx.load(await file.arrayBuffer());
                const sheet = workbook.worksheets[0];
                let header = [];
                let rows = [];

                sheet.eachRow((row, idx) => {
                    const values = row.values.slice(1);
                    if (idx === 1) header = values;
                    else rows.push(values);
                });

                renderPreview(header, rows);
                processExcelData(rows, header);
                uploadType = 'excel';
                $('#preview-count-update').html(
                    `${rows.length} baris &nbsp;•&nbsp; <span class="text-emerald-600 font-bold">${dataSiswa.length} siap update</span>`);
                $('#btn-submit-update').prop('disabled', dataSiswa.length === 0);
                Swal.close();
            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Gagal membaca Excel", "error");
            }
        });

        $('#input-word').on('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            $('#dzWordName').removeClass('hidden').text('📄 ' + file.name);
            $('#dzExcelName').addClass('hidden').text('');
            if (!/\.docx$/i.test(file.name)) {
                Swal.fire('Format Salah', 'Gunakan file .docx hasil download per kelas.', 'warning');
                e.target.value = '';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 10MB.', 'warning');
                e.target.value = '';
                return;
            }

            Swal.fire({ title: 'Membaca Word...', didOpen: () => Swal.showLoading() });

            const reader = new FileReader();
            reader.onload = function (event) {
                mammoth.convertToHtml({ arrayBuffer: event.target.result })
                    .then(result => {
                        const html = result.value;
                        $('#preview-container').html(html).find('table').addClass('table table-sm table-bordered');
                        extractWordData();
                        uploadType = 'word';
                        $('#preview-count-update').html(`${dataSiswa.length} foto terdeteksi`);
                        $('#btn-submit-update').prop('disabled', dataSiswa.length === 0);
                        Swal.close();
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire("Error", "Gagal membaca Word", "error");
                    });
            };
            reader.readAsArrayBuffer(file);
        });

        $('#btn-submit-update').click(function () {
            submitUpdate();
        });
    });

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function renderPreview(header, rows) {
        let html = '<table class="table-tw"><thead class="sticky top-0 bg-white shadow-sm"><tr>';
        header.forEach(h => html += `<th class="whitespace-nowrap">${escHtml(h)}</th>`);
        html += '<th class="whitespace-nowrap">Status</th></tr></thead><tbody>';

        // Tandai baris tanpa ID (akan dilewati server) — kolom ID_SISWA/ID di posisi berapa pun
        const norm = (h) => String(h == null ? '' : h).toLowerCase().replace(/[^a-z]/g, '');
        let cId = header.findIndex(h => ['idsiswa', 'id'].includes(norm(h)));
        if (cId < 0) cId = 43;
        let okCount = 0, badCount = 0;

        rows.slice(0, 50).forEach(row => {
            const hasId = String(row[cId] ?? '').trim() !== '';
            if (hasId) okCount++; else badCount++;
            html += `<tr class="${hasId ? '' : 'bg-red-50'}">`;
            row.forEach(cell => html += `<td class="whitespace-nowrap">${escHtml(cell)}</td>`);
            html += `<td class="whitespace-nowrap text-center">` +
                (hasId
                    ? `<span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">Valid</span>`
                    : `<span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold" title="Tanpa ID_SISWA — dilewati">Invalid</span>`) +
                `</td></tr>`;
        });

        if (rows.length > 50) html += `<tr><td colspan="${header.length + 1}" class="text-center text-slate-400">... ${rows.length - 50} baris lainnya ...</td></tr>`;

        html += '</tbody></table>';
        $('#preview-container').html(html);
        $('#preview-count-update').html(
            `${rows.length} baris &nbsp;•&nbsp; <span class="text-emerald-600 font-bold">${okCount}+ valid</span>` +
            (badCount ? ` &nbsp;•&nbsp; <span class="text-red-600 font-bold">${badCount}+ tanpa ID</span>` : ''));
    }

    function processExcelData(rows, header) {
        dataSiswa = [];
        // Petakan kolom berdasarkan nama header (toleran file unduhan maupun template):
        // ID_SISWA/ID | NISN | NIS | NAMA... — fallback ke indeks legacy bila header tak dikenal
        const norm = (h) => String(h == null ? '' : h).toLowerCase().replace(/[^a-z]/g, '');
        const findCol = (...names) => {
            for (let i = 0; i < header.length; i++) {
                if (names.includes(norm(header[i]))) return i;
            }
            return -1;
        };
        let cId = findCol('idsiswa', 'id');
        let cNisn = findCol('nisn');
        let cNis = findCol('nis');
        let cNama = findCol('namalengkap', 'nama', 'namasiswa');
        if (cId < 0) cId = 43; // layout template legacy
        if (cNisn < 0) cNisn = 1;
        if (cNis < 0) cNis = 2;
        if (cNama < 0) cNama = 3;

        rows.forEach(row => {
            const id = String(row[cId] ?? '').trim();
            if (!id) return; // tanpa ID = baris baru/legenda → lewati
            dataSiswa.push({
                id_siswa: id,
                nisn: String(row[cNisn] ?? '').trim(),
                nis: String(row[cNis] ?? '').trim(),
                nama: String(row[cNama] ?? '').trim(),
            });
        });
    }

    function extractWordData() {
        dataSiswa = [];
        $('#preview-container table tr').each(function (idx) {
            if (idx === 0) return; // Skip header
            const tds = $(this).find('td');
            const id = $(tds[1]).text().trim();
            const nis = $(tds[2]).text().trim();
            const img = $(tds[5]).find('img');

            if (id && img.length > 0) {
                const src = img.attr('src');
                if (src.startsWith('data:image/')) {
                    const ext = src.substring("data:image/".length, src.indexOf(";base64"));
                    dataSiswa.push({
                        id_siswa: id,
                        nis: nis,
                        foto: src.split(';base64,')[1],
                        ext: ext
                    });
                }
            }
        });
    }

    $(document).on('click', '[data-dl-excel]', function () {
        downloadExcel($(this).data('dl-excel'), $(this).data('nama'));
    });
    $(document).on('click', '[data-dl-word]', function () {
        downloadWord($(this).data('dl-word'), $(this).data('nama'));
    });

    async function downloadExcel(idKelas, namaKelas) {
        Swal.fire({ title: 'Menyiapkan data...', didOpen: () => Swal.showLoading() });

        try {
            const response = await fetch(`<?= site_url('datasiswa/downloadData') ?>/${encodeURIComponent(idKelas)}`);
            const result = await response.json();
            if (!result.status || !Array.isArray(result.siswa)) {
                Swal.fire('Gagal', result.message || 'Gagal memuat data', 'error');
                return;
            }

            // Bangun file langsung dari JSON (template statis tidak mendukung fill)
            const wb = new ExcelJS.Workbook();
            const ws = wb.addWorksheet('Data Siswa');
            ws.columns = [
                { header: 'ID_SISWA', key: 'id_siswa', width: 10 },
                { header: 'No', key: 'no', width: 6 },
                { header: 'NISN', key: 'nisn', width: 16 },
                { header: 'NIS', key: 'nis', width: 14 },
                { header: 'Nama Lengkap', key: 'nama', width: 32 },
                { header: 'JK', key: 'jenis_kelamin', width: 8 },
                { header: 'Username', key: 'username', width: 18 },
                { header: 'Kelas', key: 'nama_kelas', width: 12 },
            ];
            result.siswa.forEach(s => ws.addRow({
                id_siswa: s.id_siswa, no: s.no, nisn: s.nisn, nis: s.nis,
                nama: s.nama, jenis_kelamin: s.jenis_kelamin,
                username: s.username, nama_kelas: s.nama_kelas,
            }));
            const head = ws.getRow(1);
            head.font = { bold: true, color: { argb: 'FFFFFFFF' } };
            head.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF059669' } };
            head.alignment = { horizontal: 'center', vertical: 'middle' };
            ws.views = [{ state: 'frozen', ySplit: 1 }];
            ws.autoFilter = { from: 'A1', to: 'H1' };
            ['A', 'C', 'D'].forEach(col => {
                ws.getColumn(col).numFmt = '@';
            });

            const buf = await wb.xlsx.writeBuffer();
            const blob = new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `Update_Siswa_${namaKelas}.xlsx`;
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 5000);
            Swal.close();
            Swal.fire({ icon: 'success', title: 'Terdownload', text: result.siswa.length + ' siswa kelas ' + namaKelas + '. Edit kolom NISN/NIS/Nama lalu upload kembali.', timer: 2500, showConfirmButton: false });
        } catch (err) {
            console.error(err);
            Swal.fire("Error", "Gagal download data", "error");
        }
    }

    async function downloadWord(idKelas, namaKelas) {
        Swal.fire({ title: 'Menyiapkan Foto...', didOpen: () => Swal.showLoading() });

        try {
            const response = await fetch(`<?= site_url('datasiswa/downloadData') ?>/${encodeURIComponent(idKelas)}`);
            const result = await response.json();
            if (!result.status) {
                Swal.fire('Gagal', result.message || 'Gagal memuat data', 'error');
                return;
            }

            let html = `<h3>Daftar Foto Siswa - ${namaKelas}</h3><table border="1" style="border-collapse: collapse; width: 100%;">`;
            html += `<tr style="background: #f0f0f0;"><th>No</th><th>ID</th><th>NIS</th><th>NAMA</th><th>JK</th><th>FOTO</th></tr>`;

            result.siswa.forEach((s, i) => {
                html += `<tr>
                    <td align="center">${i + 1}</td>
                    <td align="center">${escHtml(s.id_siswa)}</td>
                    <td align="center">${escHtml(s.nis)}</td>
                    <td>${escHtml(s.nama)}</td>
                    <td align="center">${escHtml(s.jenis_kelamin)}</td>
                    <td align="center"><img src="${escHtml(s.foto)}" width="60" height="auto" alt=""></td>
                </tr>`;
            });
            html += '</table>';

            // Mammoth/html-docx magic
            const converted = htmlDocx.asBlob(html, { orientation: 'portrait', margins: { top: 720 } });
            saveAs(converted, `Update_Foto_${namaKelas}.docx`);
            Swal.close();
        } catch (err) {
            console.error(err);
            Swal.fire("Error", "Gagal menyiapkan file Word", "error");
        }
    }

    async function submitUpdate() {
        Swal.fire({
            title: "Memperbarui Data",
            text: `Sedang mengirim ${dataSiswa.length} data...`,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const url = uploadType === 'excel' ? '<?= site_url('datasiswa/updateData') ?>' : '<?= site_url('datasiswa/update_foto') ?>';

        const formData = new FormData();
        formData.append(CSRF_NAME, CSRF_HASH);
        formData.append('method', 'bulk');

        dataSiswa.forEach((s, i) => {
            for (let key in s) {
                formData.append(`siswa[${i}][${key}]`, s[key]);
            }
        });

        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status) {
                Swal.fire("Berhasil", result.message || "Data berhasil diperbarui", "success").then(() => {
                    window.location.href = '<?= site_url('datasiswa') ?>';
                });
            } else {
                Swal.fire("Gagal", result.message || "Gagal update data", "error");
            }
        } catch (err) {
            console.error(err);
            Swal.fire("Error", "Koneksi terputus", "error");
        }
    }
</script>
<?= $this->endSection() ?>