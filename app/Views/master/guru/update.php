<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1">
                <h3 class="mb-0"><?= esc($judul ?? 'Halaman') ?></h3>
            </div>
            <nav class="breadcrumb-tw">
                    <a href="<?= site_url('dashboard') ?>">Dashboard</a><span class="sep">/</span>
                    <?php echo esc($subjudul ?? 'Data') ?></span>
            </nav>
</div>

    <div class="content-area">
            <div class="flex flex-wrap gap-4">
                <div class="w-full">
                    <div class="alert-tw alert-info">
                        <strong>Catatan!</strong> Untuk update data bulk, silakan download data terlebih dahulu, edit di
                        file Excel/Word, lalu upload kembali.
                    </div>
                </div>

                <div class="">
                    <div class="card-tw"><div class="card-tw-header">
                            <h6 class="font-bold text-slate-700">Bulk Update (Excel)</h6>
                        </div>
                        <div class="card-tw-body">
                            <div class="mb-6">
                                <label class="form-label-tw">1. Unduh Data Saat Ini</label>
                                <?php if (!empty($gurus)): ?>
                                    <button onclick="download()" class="btn-tw btn-tw-sm btn-success">
                                        <i class="bi bi-download mr-1"></i> Download Excel
                                    </button>
                                <?php else: ?>
                                    <div class="alert-tw alert-info">Belum ada data guru</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-6">
                                <label class="form-label-tw">2. Unggah File Terkoreksi</label>
                                <input type="file" id="input-file-excel" class="form-control-tw"
                                    accept=".xlsx" />
                                <div class="form-text text-sm mt-1">Gunakan format file yang diunduh di atas.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <div class="card-tw"><div class="card-tw-header">
                            <h6 class="font-bold text-slate-700">Update Foto (Word)</h6>
                        </div>
                        <div class="card-tw-body">
                            <div class="mb-6">
                                <label class="form-label-tw">1. Unduh Format Foto</label>
                                <?php if (!empty($gurus)): ?>
                                    <button onclick="downloadWord()" class="btn-tw btn-tw-sm btn-success">
                                        <i class="bi bi-download mr-1"></i> Download Word
                                    </button>
                                <?php else: ?>
                                    <div class="alert-tw alert-info">Belum ada data guru</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-6">
                                <label class="form-label-tw">2. Unggah File Baru</label>
                                <input type="file" id="input-file-word" class="form-control-tw"
                                    accept=".docx" />
                                <div class="form-text text-sm mt-1">Ganti gambar pada file yang diunduh di atas.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <div class="card-tw"><div class="card-tw-header">
                            <h6 class="font-bold text-slate-700">Aksi & Info</h6>
                        </div>
                        <div class="card-tw-body">
                            <div id="status-container" class="text-center py-8 text-slate-400">
                                <i class="bi bi-info-circle text-5xl block mb-6"></i>
                                <p>Silakan upload file untuk melihat pratinjau data.</p>
                            </div>
                            <div id="action-container" class="hidden">
                                <div class="alert-tw alert-info">
                                    <span id="data-count">0</span> data siap untuk diupdate.
                                </div>
                                <button id="btn-upload" type="button" class="btn-tw btn-primary">
                                    <i class="bi bi-cloud-upload mr-1"></i> Update Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="card-tw"><div class="card-tw-header">
                            <h6 class="font-bold text-slate-700">Pratinjau Data</h6>
                        </div>
                        <div class="p-0">
                            <div id="file-preview" class="table-tw" style="max-height: 500px">
                                <table class="table-tw" id="tbl-preview">
                                    <thead class="bg-slate-50 sticky top-0">
                                        <tr id="preview-header"></tr>
                                    </thead>
                                    <tbody id="preview-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Download Structure -->
            <div id="download-tbl" class="hidden">
                <style>
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        font-family: sans-serif;
                    }

                    th,
                    td {
                        border: 1px solid #000;
                        padding: 5px;
                    }

                    th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }

                    .center {
                        text-align: center;
                    }
                </style>
                <div class="doc-header">
                    <h2>Update Data Guru</h2>
                    <ul>
                        <li>Jangan merubah header dan kolom NO/ID
                        <li>Update data pada kolom yang tersedia
                        <li>Untuk foto, ganti gambar pada kolom FOTO di file Word
                    </ul>
                </div>
                <table id="tbl-export">
                    <thead>
                        <tr id="export-header"></tr>
                    </thead>
                    <tbody id="export-body"></tbody>
                </table>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/app/js/excel/exceljs.js') ?>"></script>
<script src="<?= base_url('assets/app/js/FileSaver.min.js') ?>"></script>
<script src="<?= base_url('assets/app/js/mammoth.browser.min.js') ?>"></script>
<script src="<?= base_url('assets/app/js/html-docx.js') ?>"></script>

<script>
    let dataGuru = [];
    let isUploadType; // 'excel' or 'word'

    $(document).ready(function () {
        $('#input-file-excel').change(async function (e) {
            const file = e.target.files[0];
            if (!file) return;

            showLoadingPreview('Membaca Excel...');

            try {
                const data = await getDataFromExcel(file);
                dataGuru = data[data.sheets[0]].rows;
                isUploadType = 'excel';
                displayExcelPreview(data[data.sheets[0]]);
                showAction(dataGuru.length);
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal membaca file Excel', 'error');
                resetPreview();
            }
        });

        $('#input-file-word').change(function (e) {
            const file = e.target.files[0];
            if (!file) return;

            showLoadingPreview('Membaca Word...');
            parseWordDocxFile(file);
        });

        $('#btn-upload').click(function () {
            if (dataGuru.length === 0) return;
            uploadData();
        });
    });

    function showLoadingPreview(text) {
        $('#status-container').html(`
            <div class="spinner-border text-blue-600 mb-6"></div>
            <p>${text}</p>
        `);
        $('#action-container').addClass('hidden');
        $('#tbl-preview').addClass('hidden');
    }

    function showAction(count) {
        $('#status-container').addClass('hidden');
        $('#action-container').removeClass('hidden');
        $('#data-count').text(count);
    }

    function resetPreview() {
        $('#status-container').removeClass('hidden').html(`
            <i class="bi bi-info-circle text-5xl block mb-6"></i>
            <p>Silakan upload file untuk melihat pratinjau data.</p>
        `);
        $('#action-container').addClass('hidden');
        $('#tbl-preview').addClass('hidden');
        dataGuru = [];
    }

    async function getDataFromExcel(file) {
        const wb = new ExcelJS.Workbook();
        const reader = new FileReader();
        return new Promise((resolve) => {
            reader.onload = () => {
                wb.xlsx.load(reader.result).then(workbook => {
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
                                for (let i = 1; i <= row.cellCount; i++) {
                                    obj[i] = row.getCell(i).text;
                                }
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

    function displayExcelPreview(sheet) {
        let headerHtml = '';
        sheet.header.forEach(h => headerHtml += `<th class="whitespace-nowrap">${h.label}</th>`);
        $('#preview-header').html(headerHtml);

        let bodyHtml = '';
        sheet.rows.forEach(row => {
            bodyHtml += '<tr>';
            sheet.header.forEach(h => {
                bodyHtml += `<td>${row[h.value] || ''}</td>`;
            });
            bodyHtml += '</tr>';
        });

        $('#preview-body').html(bodyHtml);
        $('#tbl-preview').removeClass('hidden');
    }

    function parseWordDocxFile(file) {
        let reader = new FileReader();
        reader.onload = function (e) {
            let arrayBuffer = e.target.result;
            mammoth.convertToHtml({ arrayBuffer: arrayBuffer }).then(function (result) {
                let html = result.value;
                const $wrap = $('<div>').append(html);
                const $table = $wrap.find('table');

                if ($table.length) {
                    dataGuru = [];
                    let headerHtml = '';
                    $table.find('tr').first().find('td,th').each(function () {
                        headerHtml += `<th class="whitespace-nowrap">${$(this).text().trim()}</th>`;
                    });
                    $('#preview-header').html(headerHtml);

                    let bodyHtml = '';
                    $table.find('tr').each(function (i, tr) {
                        if (i === 0) return;
                        const $tds = $(tr).find('td');
                        bodyHtml += '<tr>';
                        $tds.each(function () {
                            bodyHtml += `<td>${$(this).html()}</td>`;
                        });
                        bodyHtml += '</tr>';

                        const fotoImg = $(tr).find('img');
                        const fotoSrc = fotoImg.attr('src');
                        dataGuru.push({
                            no: $tds.eq(0).text().trim(),
                            id_guru: $tds.eq(1).text().trim(),
                            nip: $tds.eq(2).text().trim(),
                            nama: $tds.eq(3).text().trim(),
                            jenis_kelamin: $tds.eq(4).text().trim(),
                            foto: fotoSrc ? fotoSrc.split(',')[1] : null,
                            ext: fotoSrc ? (fotoSrc.substring("data:image/".length, fotoSrc.indexOf(";base64")) || 'jpg') : 'jpg'
                        });
                    });

                    $('#preview-body').html(bodyHtml);
                    $('#tbl-preview').removeClass('hidden');
                    isUploadType = 'word';
                    showAction(dataGuru.length);
                } else {
                    Swal.fire('Error', 'Format Word tidak sesuai (Tabel data tidak ditemukan)', 'error');
                    resetPreview();
                }
            });
        };
        reader.readAsArrayBuffer(file);
    }

    function uploadData() {
        Swal.fire({
            title: 'Konfirmasi Update',
            text: `Apakah Anda yakin ingin mengupdate ${dataGuru.length} data guru?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Update!'
        }).then(result => {
            if (result.value) {
                Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

                const mapping = [
                    { key: 1, value: "no" },
                    { key: 2, value: "id_guru" },
                    { key: 3, value: "nip" },
                    { key: 4, value: "nama_guru" },
                    { key: 5, value: "jenis_kelamin" },
                    { key: 6, value: "username" },
                    { key: 7, value: "password" }
                ];

                let payload = [];
                let ids = [];

                if (isUploadType === 'excel') {
                    dataGuru.forEach(row => {
                        let guru = {};
                        mapping.forEach(m => {
                            if (row[m.key]) guru[m.value] = row[m.key];
                        });
                        if (guru.id_guru) {
                            payload.push(guru);
                            ids.push(guru.id_guru);
                        }
                    });
                } else {
                    payload = dataGuru;
                }

                const url = isUploadType === 'excel' ? '<?= site_url("dataguru/update_all") ?>' : '<?= site_url("dataguru/update_foto") ?>';

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        guru: payload,
                        ids: JSON.stringify(ids),
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    success: (res) => {
                        if (res.status) {
                            Swal.fire('Berhasil', `Berhasil mengupdate ${res.user} data guru`, 'success').then(() => {
                                window.location.href = '<?= site_url("dataguru") ?>';
                            });
                        } else {
                            Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');
                        }
                    },
                    error: () => { Swal.fire('Gagal', 'Terjadi kesalahan server', 'error'); }
                });
    }
        });
    }

    function download() {
        Swal.fire({ title: 'Menyiapkan Data...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        $.get('<?= site_url("dataguru/downloadData") ?>', function (res) {
            if (res.status) {
                const wb = new ExcelJS.Workbook();
                const sheet = wb.addWorksheet('Update Data Guru');

                sheet.addRow(['NO', 'ID', 'NIP', 'NAMA GURU', 'L/P', 'USERNAME', 'PASSWORD']);
                res.guru.forEach(g => {
                    sheet.addRow([g.no, g.id_guru, g.nip, g.nama_guru, g.jenis_kelamin, g.username, g.password]);
                });

                sheet.getRow(1).font = { bold: true };
                sheet.getRow(1).alignment = { horizontal: 'center' };
                sheet.columns.forEach(col => col.width = 20);
                sheet.getColumn(1).width = 5;
                sheet.getColumn(2).width = 10;
                sheet.getColumn(5).width = 5;

                wb.xlsx.writeBuffer().then(buffer => {
                    saveAs(new Blob([buffer]), 'Update_Data_Guru.xlsx');
                    Swal.close();
                });
            }
        });
    }

    async function downloadWord() {
        Swal.fire({ title: 'Menyiapkan Data...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        $.get('<?= site_url("dataguru/downloadData") ?>', function (res) {
            if (res.status) {
                $('#export-header').html('<th>NO</th><th>ID</th><th>NIP</th><th>NAMA GURU</th><th>L/P</th><th>FOTO</th>');
                let body = '';
                res.guru.forEach(g => {
                    body += `<tr>
                        <td align="center">${g.no}</td>
                        <td align="center">${g.id_guru}</td>
                        <td>${g.nip || ''}</td>
                        <td>${g.nama_guru}</td>
                        <td align="center">${g.jenis_kelamin}</td>
                        <td align="center"><img src="${g.foto}" width="80" /></td>
                    </tr>`;
                });
                $('#export-body').html(body);

                setTimeout(() => {
                    const content = document.getElementById('download-tbl').innerHTML;
                    const converted = htmlDocx.asBlob(`<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>${content}</body></html>`);
                    saveAs(converted, 'Update_Foto_Guru.docx');
                    Swal.close();
                }, 1000);
            }
        });
    }
</script>
<?= $this->endSection() ?>