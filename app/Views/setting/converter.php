<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
        <div class="page-header">
        <div class="min-w-0">
                <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-xs shrink-0">
                        <i class="bi bi-database-gear text-xl"></i>
                    </span>
                    <span><?= esc($judul ?? 'Database Converter') ?></span>
                </h3>
            </div>
            <nav class="breadcrumb-tw">
                <a href="<?= base_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                    <i class="bi bi-house-door mr-1"></i> Dashboard
                </a>
                <span class="sep">/</span>
                <span class="active font-medium text-slate-800">Converter</span>
            </nav>
        </div>
    </div>

<!-- Main Content -->
<div class="content-area">
        
        <!-- Info Alert -->
        <div class="alert-tw alert-info">
            <div class="flex items-start">
                <div class="symbol symbol-40px mr-3 mt-1">
                    <span class="symbol-label bg-slate-50-info text-cyan-600 rounded-full p-2">
                        <i class="bi bi-info-circle text-2xl"></i>
                    </span>
                </div>
                <div>
                     <h5 class="alert-tw alert-info">Informasi Konversi</h5>
                     <p class="mb-0 text-slate-400">Fitur ini digunakan untuk mengkonversi gambar yang tersimpan sebagai <b>Base64</b> (teks panjang) di dalam database menjadi file fisik di folder <code>uploads</code>. Proses ini direkomendasikan untuk mengurangi ukuran database dan meningkatkan performa aplikasi secara signifikan.</p>
                </div>
            </div>
        </div>

        <div class="card-tw">
            <div class="card-tw-header">
                <h3 class="font-bold text-slate-700">
                    <i class="bi bi-table mr-2 text-slate-500"></i>Daftar Tabel Database
                </h3>
            </div>
            <div class="p-0">
                <div class="table-responsive-tw"><table class="table-tw">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="pl-6" style="width: 30%;">Nama Tabel</th>
                                <th class="text-center" style="width: 15%;">Total Row</th>
                                <th class="text-center" style="width: 35%;">Status Konversi</th>
                                <th class="text-right pr-6" style="width: 20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table => $count): ?>
                                <tr>
                                    <td class="pl-6 font-bold text-slate-800 font-mono">
                                        <i class="bi bi-hdd-stack mr-2 text-slate-400"></i><?= $table ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-tw badge-secondary"><?= number_format($count, 0, ',', '.') ?></span>
                                    </td>
                                    <td class="text-center px-6">
                                        <!-- Progress Bar Container -->
                                        <div class="flex flex-col items-center justify-center" id="status_container_<?= $table ?>">
                                            <span class="text-slate-400 text-sm mb-1 hidden" id="status_text_<?= $table ?>">Menunggu antrian...</span>
                                            
                                            <div class="progress-tw" id="progress_bar_container_<?= $table ?>" style="height: 18px; border-radius: 10px;">
                                                <div id="progress_bar_<?= $table ?>" 
                                                     class="progress-tw" 
                                                     role="progressbar" 
                                                     style="width: 0%; border-radius: 10px;" 
                                                     aria-valuenow="0" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">0%</div>
                                            </div>
                                            
                                            <span id="finish_badge_<?= $table ?>" class="hidden badge-tw badge-success opacity-10 text-emerald-600 border border-success px-4 py-3 rounded-full">
                                                <i class="bi bi-check-circle-fill mr-1"></i>Selesai
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-right pr-6">
                                        <button class="btn-tw btn-tw-sm btn-primary"
                                            id="btn_convert_<?= $table ?>"
                                            onclick="convertTable('<?= $table ?>', <?= $count ?>)">
                                            <i class="bi bi-magic mr-1"></i> Mulai
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($tables)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-12 text-slate-400">
                                        <i class="bi bi-check2-circle display-4 block mb-6 text-emerald-600"></i>
                                        Tidak ada tabel yang perlu dikonversi saat ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?= form_open('', array('id' => 'convert', 'class' => 'hidden')); ?><?= form_close() ?>
        </div>
    </div>

<script>
    function convertTable(table, count) {
        if (count === 0) {
            Swal.fire("Info", "Tabel kosong, tidak ada data untuk dikonversi.", "info");
            return;
        }

        const limit = 50; // Jumlah data per batch
        const totalPages = Math.ceil(count / limit);
        const delay = 300;
        
        let page = 1;
        
        // UI Elements
        const btn = $('#btn_convert_' + table);
        const prgContainer = $('#progress_bar_container_' + table);
        const prgBar = $('#progress_bar_' + table);
        const statusTxt = $('#status_text_' + table);
        const finishBadge = $('#finish_badge_' + table);
        
        // Init state
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span>Stop');
        prgContainer.removeClass('hidden');
        statusTxt.removeClass('hidden').text('Mempersiapkan data...');
        finishBadge.addClass('hidden');

        // Reset other buttons to prevent parallel chaos
        // $('.btn-convert').not(btn).prop('disabled', true);

        function sendPageData() {
            if (page > totalPages) {
                // Completed
                btn.prop('disabled', false)
                   .html('<i class="bi bi-arrow-repeat mr-1"></i> Ulangi')
                   .removeClass('btn-primary').addClass('btn-outline-secondary');
                
                prgContainer.addClass('hidden');
                statusTxt.addClass('hidden');
                finishBadge.removeClass('hidden');
                
                Swal.fire({
                    title: "Selesai!",
                    text: `Konversi tabel ${table} berhasil diselesaikan.`,
                    icon: "success",
                    timer: 3000,
                    showConfirmButton: false
                });
                
                // $('.btn-convert').not(btn).prop('disabled', false);
                return;
            }

            const percent = Math.min(Math.round((page / totalPages) * 100), 100);
            prgBar.css('width', percent + '%').text(percent + '%').attr('aria-valuenow', percent);
            statusTxt.text(`Memproses batch ${page} dari ${totalPages}...`);
            
            const offset = (page - 1) * limit;

            let formActivate = new FormData($('#convert')[0])
            formActivate.append('table', table)
            formActivate.append('limit', limit)
            formActivate.append('offset', offset)

            $.ajax({
                url: base_url + 'dbconvert/convert', // Removed leading slash to use CI4 base_url properly
                type: 'POST',
                processData: false,
                contentType: false,
                data: formActivate,
                success: function (response) {
                    // console.log(`Halaman ${page} berhasil.`, response);
                    page++;
                    setTimeout(sendPageData, delay);
                },
                error: function (xhr, status, error) {
                    console.error(`Gagal mengirim halaman ${page}:`, error);
                    statusTxt.text(`Gagal pada batch ${page}. Mencoba ulang...`);
                    // Retry strategy or Stop? Let's retry once after 2 sec
                    setTimeout(sendPageData, 2000);
                }
            });
        }

        Swal.fire({
            title: "Mulai Konversi?",
            text: `Akan memproses ${count} baris data dari tabel ${table}. Proses ini mungkin memakan waktu.`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Mulai",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                sendPageData();
            } else {
                // Reset UI if cancelled
                btn.prop('disabled', false).html('<i class="bi bi-magic mr-1"></i> Mulai');
                prgContainer.addClass('hidden');
                statusTxt.addClass('hidden');
            }
        });
    }
</script>
<?= $this->endSection(); ?>
