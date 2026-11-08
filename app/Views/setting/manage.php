<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
        <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-database-gear text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Manage Database') ?></span>
            </h3>
            </div>
            <nav class="breadcrumb-tw">
                    <a href="<?= site_url('dashboard') ?>" class="no-underline"><i class="bi bi-house-door mr-1"></i>Dashboard</a><span class="sep">/</span>
                    <span class="active">Database</span>
                    </nav>
    </div>
</div>

<div class="content-area">
<div class="card-tw">
            <div class="card-tw-header">
                <h6 class="font-bold text-slate-700">
                    <i class="bi bi-gear mr-2"></i><?= esc($subjudul ?? 'Pengelolaan Tabel') ?>
                </h6>
            </div>
            <div class="card-tw-body">
                <div class="alert-tw alert-info" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-4xl mr-3"></i>
                    <div>
                        <h5 class="alert-tw alert-info">PENTING!</h5>
                        <p class="mb-0">Pastikan Anda telah melakukan <b>BACKUP DATABASE</b> sebelum menghapus data apapun. Penghapusan data bersifat permanen dan tidak dapat dikembalikan tanpa backup.</p>
                    </div>
                </div>

                <?php
                $titles = [
                    '1' => 'Data Master & Konfigurasi',
                    '2' => 'Data Transaksional & Periodik',
                    '3' => 'Data Usang / Tidak Digunakan'
                ];

                foreach ($tables as $ket => $table_group):
                    if ($ket == '1') {
                        $keterangan = 'Data ini digunakan secara berkelanjutan (Jangka Panjang). Menghapus data ini dapat mempengaruhi operasional sistem.';
                        $badge = 'bg-primary';
                        $border = 'border-primary';
                    } elseif ($ket == '2') {
                        $keterangan = 'Data yang terikat dengan Tahun Ajaran atau Semester. Aman dihapus jika sudah berganti tahun ajaran (Otomatis dibackup).';
                        $badge = 'bg-info';
                        $border = 'border-info';
                    } else {
                        $keterangan = 'Tabel dari versi sebelumnya atau modul yang sudah tidak aktif. Aman untuk dihapus.';
                        $badge = 'bg-secondary';
                        $border = 'border-secondary';
                    }
                    ?>
                    <div class="mb-5">
                        <div class="flex items-center mb-6">
                            <div class="badge <?= $badge ?> p-2 rounded-full mr-2 flex items-center justify-center shadow-sm"
                                style="width: 32px; height: 32px;">
                                <i class="bi bi-database text-white"></i>
                            </div>
                            <h5 class="mb-0 font-bold text-slate-800">
                                <?= $titles[$ket] ?? 'Data Lainnya' ?>
                            </h5>
                        </div>

                        <div class="card-tw">
                            <div class="card-tw-body">
                                <p class="mb-0 text-slate-500 text-sm">
                                    <i class="bi bi-info-circle-fill mr-1 text-<?= str_replace('bg-', '', $badge) ?>"></i>
                                    <?= $keterangan ?>
                                </p>
                            </div>
                        </div>

                        <div class="table-responsive-tw"><table class="table-tw">
                                <thead class="<?= str_replace('bg-', 'table-', $badge) ?>">
                                    <tr>
                                        <th width="50" class="text-center py-4">No.</th>
                                        <th class="py-4">Nama Tabel</th>
                                        <th class="text-center py-4">Total Rows</th>
                                        <th class="text-center py-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($table_group as $info): ?>
                                        <tr>
                                            <td class="text-center"><?= $no ?></td>
                                            <td class="font-medium text-slate-800 font-mono"><?= $info['table'] ?></td>
                                            <td class="text-center">
                                                <span class="badge-tw badge-secondary">
                                                    <?= $info['size'] ?> Data
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php $disable = $info['size'] == 0 ? 'disabled' : ''; ?>
                                                <button class="btn-tw btn-tw-sm btn-outline-danger"
                                                    onclick="hapus('<?= $info['table'] ?>')" <?= $disable ?>>
                                                    <i class="bi bi-trash mr-1"></i> Kosongkan
                                                </button>
                                            </td>
                                        </tr>
                                        <?php $no++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?= form_open('', array('id' => 'delete-table')) ?>
<?= form_close() ?>

<script>
    function hapus(src) {
        Swal.fire({
            title: "Anda yakin?",
            html: "Tabel <span class='font-mono font-bold text-red-500'>" + src + "</span> akan dikosongkan secara permanen!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Ya, Kosongkan!",
            cancelButtonText: "Batal"
        }).then(result => {
            if (result.value) {
                Swal.fire({
                    title: "Memproses...",
                    text: "Mohon tunggu sebentar.",
                    allowEscapeKey: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                var form = $('#delete-table');
                var data = form.serialize() + '&table=' + src;

                $.ajax({
                    url: base_url + '/dbclear/hapustable',
                    type: "POST",
                    data: data,
                    success: function (respon) {
                        Swal.fire({
                            title: "Berhasil",
                            text: respon.message,
                            icon: "success"
                        }).then(result => {
                            if (result.value) {
                                window.location.reload(); // Reload page to update counts
                            }
                        });
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                        let errMsg = "Terjadi kesalahan saat menghapus data.";
                        try {
                            let err = JSON.parse(xhr.responseText);
                            if(err.message) errMsg = err.message;
                        } catch (e) { }
                        
                        Swal.fire({
                            title: "Gagal",
                            text: errMsg,
                            icon: "error"
                        });
                    }
                });
            }
        })
    }
</script>
<?= $this->endSection() ?>