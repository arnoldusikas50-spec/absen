<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$totalGuru = count($gurus ?? []);
$isAdmin = (bool) ($is_admin ?? false);
$guruAktif = 0;
foreach ($gurus ?? [] as $g) {
    if ((string) ($g->active ?? '') === '1') {
        $guruAktif++;
    }
}
$guruNonaktif = $totalGuru - $guruAktif;
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="bi bi-person-video3 text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Data Guru') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="text-slate-400">Data Umum</span>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($judul ?? 'Guru') ?></span>
        </nav>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-area mt-4 space-y-6">

    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <!-- Card Header Toolbar -->
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                    <i class="bi bi-person-lines-fill text-blue-600"></i>
                    Daftar Guru
                </h4>
                <p class="text-xs text-slate-500 mb-0">Kelola profil guru, jabatan, serta mata pelajaran yang diampu</p>
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">
                        <i class="bi bi-people-fill text-slate-400"></i> Total <span class="text-slate-800"><?= $totalGuru ?></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-semibold border border-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif <span><?= $guruAktif ?></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 text-[11px] font-semibold border border-rose-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Nonaktif <span><?= $guruNonaktif ?></span>
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-auto">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="search" id="filterGuruInput" placeholder="Cari nama, NIP, atau jabatan..."
                        aria-label="Cari guru"
                        class="w-full sm:w-64 pl-8 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                    <button type="button" id="clearGuruSearch"
                        class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                        aria-label="Hapus pencarian">
                        <i class="bi bi-x-circle-fill text-xs"></i>
                    </button>
                </div>
                <select id="filterStatus" aria-label="Filter status guru"
                    class="px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
                <button type="button" onclick="window.location.reload()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
                <?php if ($isAdmin): ?>
                    <a href="<?= site_url('dataguru/add') ?>"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                        <i class="bi bi-plus-lg"></i> Tambah / Import
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6">
            <?php if ($totalGuru > 0): ?>
                <div class="overflow-x-auto">
                    <table id="tableGuru" class="table-tw min-w-[980px]">
                        <thead>
                            <tr>
                                <th width="56" class="text-center">No</th>
                                <th>Nama Guru</th>
                                <th>NIP</th>
                                <th>Jabatan</th>
                                <th>Mapel &amp; Ekstra</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($gurus as $guru):
                                $mapels_guru = $guru->mapel_kelas ?? [];
                                $ekstras_guru = $guru->ekstra_kelas ?? [];
                                $isActive = (string) ($guru->active ?? '') === '1';

                                $mapelNama = [];
                                foreach ($mapels_guru as $m) {
                                    $mapelNama[] = $m->nama_mapel ?? '-';
                                }
                                $ekstraNama = [];
                                foreach ($ekstras_guru as $e) {
                                    $ekstraNama[] = $e->nama_ekstra ?? '-';
                                }

                                $foto = $guru->foto && file_exists(FCPATH . $guru->foto)
                                    ? base_url($guru->foto)
                                    : base_url('assets/img/guru.png');

                                $jk = match (strtoupper((string) ($guru->jenis_kelamin ?? ''))) {
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                    default => '',
                                };
                                ?>
                                <tr data-status="<?= $isActive ? '1' : '0' ?>">
                                    <td class="text-center font-bold text-slate-400" data-order="<?= $no ?>"><?= $no++ ?></td>

                                    <td data-order="<?= esc($guru->nama_guru, 'attr') ?>">
                                        <div class="flex items-center gap-3 min-w-[230px]">
                                            <div class="relative shrink-0">
                                                <img src="<?= $foto ?>" alt="<?= esc($guru->nama_guru, 'attr') ?>"
                                                    class="w-10 h-10 rounded-full object-cover border-2 <?= $isActive ? 'border-emerald-400' : 'border-rose-300' ?> shadow-xs">
                                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full <?= $isActive ? 'bg-emerald-500' : 'bg-rose-500' ?> border-2 border-white"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 text-sm truncate max-w-[220px]"
                                                    title="<?= esc($guru->nama_guru, 'attr') ?>"><?= esc($guru->nama_guru) ?></div>
                                                <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-medium">
                                                    <i class="bi bi-person-vcard"></i>
                                                    <span><?= !empty($guru->kode_guru) ? 'Kode: ' . esc($guru->kode_guru) : 'Kode belum diisi' ?></span>
                                                    <?php if ($jk !== ''): ?>
                                                        <span class="text-slate-300">•</span>
                                                        <span><?= $jk ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($guru->nip)): ?>
                                            <span class="font-mono text-xs font-semibold text-slate-700"><?= esc($guru->nip) ?></span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Belum diisi</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($guru->level)): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-violet-50 text-violet-700 border border-violet-100 text-[11px] font-semibold whitespace-nowrap">
                                                <i class="bi bi-briefcase-fill text-[10px]"></i><?= esc($guru->level) ?>
                                            </span>
                                            <?php if (!empty($guru->nama_kelas)): ?>
                                                <div class="mt-1 text-[11px] text-slate-500 font-medium">Kelas <?= esc($guru->nama_kelas) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 text-[11px] font-medium whitespace-nowrap">
                                                <i class="bi bi-book text-[10px]"></i>Guru Mata Pelajaran
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($mapels_guru) || !empty($ekstras_guru)): ?>
                                            <div class="flex flex-wrap items-center gap-1 max-w-[250px]">
                                                <?php foreach (array_slice($mapels_guru, 0, 2) as $mapel):
                                                    $klsList = [];
                                                    foreach (($mapel->kelas_mapel ?? []) as $kls_mpl) {
                                                        $klsId = $kls_mpl->id_kelas ?? $kls_mpl->kelas ?? null;
                                                        if ($klsId && isset($kelass[$klsId])) {
                                                            $klsList[] = $kelass[$klsId];
                                                        }
                                                    }
                                                    $title = ($mapel->nama_mapel ?? '-') . (!empty($klsList) ? ' (' . implode(', ', $klsList) . ')' : '');
                                                    ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-blue-50/80 text-blue-700 border border-blue-100 text-[11px] font-medium"
                                                        title="<?= esc($title, 'attr') ?>">
                                                        <i class="bi bi-book text-[10px] mr-1"></i>
                                                        <span class="truncate max-w-[110px]"><?= esc($mapel->nama_mapel ?? '-') ?></span>
                                                        <?php if (!empty($klsList)): ?>
                                                            <span class="ml-1 text-[10px] font-bold text-blue-500">[<?= count($klsList) ?>]</span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>

                                                <?php if (count($mapels_guru) > 2): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 text-[11px] font-semibold"
                                                        title="<?= esc(implode(', ', $mapelNama), 'attr') ?>">
                                                        +<?= count($mapels_guru) - 2 ?> mapel
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!empty($ekstras_guru)): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-amber-50/80 text-amber-700 border border-amber-100 text-[11px] font-medium"
                                                        title="<?= esc(implode(', ', $ekstraNama), 'attr') ?>">
                                                        <i class="bi bi-star text-[10px] mr-1"></i><?= count($ekstras_guru) ?> ekstra
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Belum ada</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wide whitespace-nowrap <?= $isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100' ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $isActive ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                                            <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <a href="<?= site_url('dataguru/detail/' . $guru->id_guru) ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100 transition-colors"
                                                title="Lihat detail guru">
                                                <i class="bi bi-eye text-xs"></i>
                                            </a>
                                            <?php if ($isAdmin): ?>
                                                <a href="<?= site_url('dataguru/edit/' . $guru->id_guru) ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-colors"
                                                    title="Ubah profil & data pribadi">
                                                    <i class="bi bi-person-gear text-xs"></i>
                                                </a>
                                                <a href="<?= site_url('dataguru/editJabatan/' . $guru->id_guru) ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 hover:bg-amber-100 transition-colors"
                                                    title="Atur wali kelas & mapel diampu">
                                                    <i class="bi bi-briefcase text-xs"></i>
                                                </a>
                                                <button type="button"
                                                    onclick="hapus('<?= (int) $guru->id_guru ?>', this)"
                                                    data-nama="<?= esc($guru->nama_guru, 'attr') ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 transition-colors"
                                                    title="Hapus data guru">
                                                    <i class="bi bi-trash3 text-xs"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="font-bold text-slate-700 mb-1">Belum Ada Data Guru</h5>
                    <p class="text-sm text-slate-400 max-w-sm mx-auto mb-4">
                        Belum ada guru yang terdaftar. Tambahkan guru secara manual atau import dari format Excel.
                    </p>
                    <a href="<?= site_url('dataguru/add') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 shadow-xs shadow-blue-500/20">
                        <i class="bi bi-plus-lg"></i> Tambah / Import Guru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        if (!$('#tableGuru').length) return;

        const tableGuru = $('#tableGuru').DataTable({
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
            language: dt_lang_id,
            dom: "<'flex flex-wrap items-center justify-between gap-3 mb-4'l>rt<'flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4'<'text-xs text-slate-500'i><'dt-pagination'p>>",
            columnDefs: [
                { targets: [0, 4, 6], orderable: false },
                { targets: [0, 6], searchable: false }
            ]
        });

        // Nomor urut mengikuti hasil filter/urutan yang sedang tampil.
        tableGuru.on('draw.dt', function () {
            tableGuru.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.textContent = i + 1;
            });
        }).draw();

        let searchTimer;
        $('#filterGuruInput').on('input', function () {
            const value = this.value;
            $('#clearGuruSearch').toggleClass('hidden', value === '');
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                tableGuru.search(value).draw();
            }, 200);
        });

        $('#clearGuruSearch').on('click', function () {
            $('#filterGuruInput').val('').trigger('focus');
            $(this).addClass('hidden');
            tableGuru.search('').draw();
        });

        let statusFilter = '';
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableGuru' || statusFilter === '') return true;
            const row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            return row ? row.getAttribute('data-status') === statusFilter : true;
        });

        $('#filterStatus').on('change', function () {
            statusFilter = this.value;
            tableGuru.draw();
        });
    });

    function hapus(idGuru, trigger) {
        const namaGuru = trigger && trigger.dataset ? trigger.dataset.nama : '';
        Swal.fire({
            title: "Hapus " + (namaGuru || "Data Guru") + "?",
            text: "Data guru akan dihapus bila tidak lagi memiliki data terkait.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#e11d48",
            cancelButtonColor: "#64748b",
            confirmButtonText: '<i class="bi bi-trash mr-1"></i> Ya, Hapus!',
            cancelButtonText: "Batal",
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= site_url('dataguru/delete') ?>',
                    data: {
                        id_guru: idGuru,
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    type: "POST",
                    dataType: "json",
                    success: function (respon) {
                        if (respon.status) {
                            Swal.fire({
                                title: "Berhasil!",
                                text: "Data guru berhasil dihapus",
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            if (respon.count > 0) {
                                Swal.fire({
                                    title: "Tidak Dapat Dihapus",
                                    text: respon.message || "Guru ini masih memiliki data jabatan, materi, jurnal, atau forum terkait.",
                                    icon: "warning",
                                    showCancelButton: true,
                                    confirmButtonText: "Lihat Detail",
                                    cancelButtonText: "Batal"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = '<?= site_url('dataguru/detail/') ?>' + idGuru;
                                    }
                                });
                            } else {
                                Swal.fire("Gagal", respon.message || "Gagal menghapus data", "error");
                            }
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Terjadi kesalahan pada server", "error");
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>
