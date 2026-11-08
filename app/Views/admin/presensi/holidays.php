﻿﻿<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles') ?>
<style>
    .holiday-card-badge {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .date-badge {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        line-height: 1.1;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content'); ?>
<div class="content-header fade-in-up">
        <div class="page-header">
        <div class="min-w-0">
                <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2">
                    <span class="p-2 bg-red-50 text-red-500 rounded-lg">
                        <i class="bi bi-calendar-event-fill"></i>
                    </span>
                    <?= esc($judul ?? 'Hari Libur & Kalender Absensi') ?>
                </h3>
            </div>
            <nav class="breadcrumb-tw">
                    <a href="<?= base_url('admin') ?>" class="no-underline"><i class="bi bi-house-door mr-1"></i>Admin</a><span class="sep">/</span>
                    <a href="<?= base_url('admin/presensi') ?>" class="no-underline">Presensi</a><span class="sep">/</span>
                    <span class="active">Hari Libur</span>
                    </nav>
    </div>
</div>

<div class="content-area">
        <!-- Summary Cards -->
        <?php
            $currentYear = date('Y');
            $currentMonth = date('Y-m');
            $thisYearCount = 0;
            $thisMonthCount = 0;
            $cutiCount = 0;
            if (!empty($holidays)) {
                foreach ($holidays as $h) {
                    if (substr($h->tanggal, 0, 4) == $currentYear) $thisYearCount++;
                    if (substr($h->tanggal, 0, 7) == $currentMonth) $thisMonthCount++;
                    if (stripos($h->keterangan, 'cuti') !== false) $cutiCount++;
                }
            }
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
            <div class="flex-1">
                <div class="card-tw">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-lg text-3xl">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <div>
                        <span class="text-sm class="text-slate-400 block font-semibold"">Total Libur Tahun <?= $currentYear ?></span>
                        <h4 class="mb-0 font-bold text-slate-800"><?= $thisYearCount ?> <span class="text-base font-normal text-slate-400">Hari</span></h4>
                    </div>
                </div>
            </div>
            <div class="flex-1">
                <div class="card-tw">
                    <div class="p-3 bg-red-50 text-red-500 rounded-lg text-3xl">
                        <i class="bi bi-calendar2-day"></i>
                    </div>
                    <div>
                        <span class="text-sm class="text-slate-400 block font-semibold"">Libur Bulan Ini (<?= date('F') ?>)</span>
                        <h4 class="mb-0 font-bold text-red-500"><?= $thisMonthCount ?> <span class="text-base font-normal text-slate-400">Hari</span></h4>
                    </div>
                </div>
            </div>
            <div class="flex-1">
                <div class="card-tw">
                    <div class="p-3 bg-amber-50 text-amber-500 rounded-lg text-3xl">
                        <i class="bi bi-calendar2-range"></i>
                    </div>
                    <div>
                        <span class="text-sm class="text-slate-400 block font-semibold"">Cuti Bersama Terdaftar</span>
                        <h4 class="mb-0 font-bold text-slate-800"><?= $cutiCount ?> <span class="text-base font-normal text-slate-400">Hari</span></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <!-- Form Input -->
            <div class="">
                <div class="card-tw"><div class="card-tw-header">
                        <h6 class="font-bold text-slate-700" id="formCardTitle">
                            <i class="bi bi-plus-circle-fill text-blue-600 mr-1"></i> Tambah Hari Libur
                        </h6>
                    </div>
                    <div class="card-tw-body">
                        <form id="formHoliday">
                            <input type="hidden" name="id" id="holidayId">
                            <input type="hidden" name="is_range" id="isRangeInput" value="0">

                            <!-- Mode Selector -->
                            <div class="btn-group w-full mb-6" role="group" id="modeSelectorGroup">
                                <input type="radio" class="btn-check" name="holiday_mode" id="modeSingle" value="0" checked onchange="toggleHolidayMode(0)">
                                <label class="btn-tw btn-outline-primary btn-sm py-3 rounded-start-3" for="modeSingle">
                                    <i class="bi bi-calendar-event mr-1"></i> Satu Tanggal
                                </label>

                                <input type="radio" class="btn-check" name="holiday_mode" id="modeRange" value="1" onchange="toggleHolidayMode(1)">
                                <label class="btn-tw btn-outline-primary btn-sm py-3 rounded-end-3" for="modeRange">
                                    <i class="bi bi-calendar-range mr-1"></i> Rentang Tanggal
                                </label>
                            </div>

                            <!-- Single Date Input -->
                            <div class="mb-6" id="singleDateGroup">
                                <label class="form-label-tw">Tanggal Libur <span class="text-red-500">*</span></label>
                                <input type="date" class="form-control-tw" name="tanggal" id="holidayDate">
                            </div>

                            <!-- Date Range Inputs -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-6" id="rangeDateGroup">
                                <div class="">
                                    <label class="form-label-tw">Mulai <span class="text-red-500">*</span></label>
                                    <input type="date" class="form-control-tw" name="tanggal_mulai" id="holidayDateStart">
                                </div>
                                <div class="">
                                    <label class="form-label-tw">Selesai <span class="text-red-500">*</span></label>
                                    <input type="date" class="form-control-tw" name="tanggal_selesai" id="holidayDateEnd">
                                </div>
                                <div class="w-full">
                                    <span class="text-sm class="text-slate-400 extra-text-sm"">Contoh: Libur Akhir Semester (20 Des s/d 5 Jan)</span>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="form-label-tw">Keterangan / Nama Libur <span class="text-red-500">*</span></label>
                                <textarea class="form-control-tw" name="keterangan" id="holidayDesc" rows="3"
                                    placeholder="Contoh: Hari Raya Idul Fitri / Libur Semester Ganjil" required></textarea>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button type="submit" class="btn-tw btn-primary" id="btnSubmitHoliday">
                                    <i class="bi bi-save mr-1"></i> Simpan
                                </button>
                                <button type="button" class="btn-tw btn-light" onclick="resetForm()">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100">
                        <span class="text-sm class="text-slate-400 block"">
                            <i class="bi bi-info-circle text-blue-600 mr-1"></i> Pada tanggal libur yang terdaftar, sistem akan otomatis meniadakan penalti denda atau alfa pada absensi guru.
                        </span>
                    </div>
                </div>
            </div>

            <!-- List Holidays -->
            <div class="">
                <div class="card-tw"><div class="card-tw-header"><div class="flex items-center">
                        <div class="flex items-center gap-2">
                            <h6 class="font-bold text-slate-700">
                                <i class="bi bi-list-check text-blue-600 mr-1"></i> Daftar Hari Libur
                            </h6>
                            <span class="badge-tw badge-secondary" id="tableCounterBadge">
                                <?= count($holidays ?? []) ?> Data
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <div class="input-group-tw">
                                <select class="form-select-tw" id="syncYearSelect" style="max-width: 100px;">
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button type="button" class="btn-tw btn-outline-primary rounded-end-pill flex items-center gap-1.5" onclick="syncHolidays()" id="btnSync">
                                    <i class="bi bi-cloud-arrow-down-fill"></i> Sinkron Nasional
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Toolbar -->
                    <div class="p-3 bg-slate-50 border-b flex gap-2 flex-wrap">
                        <div class="input-group-tw" style="max-width: 260px;">
                            <span class="input-group-tw"><i class="bi bi-search text-slate-400"></i></span>
                            <input type="text" id="filterHolidaySearch" class="form-control-tw" placeholder="Cari keterangan..." oninput="filterHolidayTable()">
                        </div>

                        <select class="form-select-tw" id="filterYear" style="max-width: 140px;" onchange="filterHolidayTable()">
                            <option value="">Semua Tahun</option>
                            <?php
                                $years = [];
                                if (!empty($holidays)) {
                                    foreach ($holidays as $h) {
                                        $yr = substr($h->tanggal, 0, 4);
                                        if (!in_array($yr, $years)) $years[] = $yr;
                                    }
                                    rsort($years);
                                    foreach ($years as $yr) {
                                        echo "<option value='{$yr}'" . ($yr == date('Y') ? ' selected' : '') . ">{$yr}</option>";
                                    }
                                }
                            ?>
                        </select>

                        <select class="form-select-tw" id="filterMonth" style="max-width: 150px;" onchange="filterHolidayTable()">
                            <option value="">Semua Bulan</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= sprintf('%02d', $m) ?>"><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="card-tw-body">
                        <table class="table-tw" id="holidayTable">
                            <thead class="table-tw">
                                <tr>
                                    <th width="60" class="text-center">#</th>
                                    <th width="200">Tanggal</th>
                                    <th>Keterangan Hari Libur</th>
                                    <th width="100" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($holidays)): ?>
                                    <tr id="emptyRow">
                                        <td colspan="4" class="text-center text-slate-400 py-12">
                                            <i class="bi bi-calendar-x display-6 block mb-6 opacity-25"></i>
                                            Belum ada data hari libur.<br>
                                            <button class="btn-tw btn-tw-sm btn-outline-primary" onclick="syncHolidays()">
                                                <i class="bi bi-cloud-arrow-down mr-1"></i> Sinkronkan Hari Libur Nasional
                                            </button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($holidays as $i => $h): 
                                        $tglTime = strtotime($h->tanggal);
                                        $isCuti = stripos($h->keterangan, 'cuti') !== false;
                                        $isSunday = date('N', $tglTime) == 7;
                                    ?>
                                        <tr class="holiday-row" 
                                            data-date="<?= $h->tanggal ?>" 
                                            data-year="<?= date('Y', $tglTime) ?>" 
                                            data-month="<?= date('m', $tglTime) ?>" 
                                            data-desc="<?= strtolower(esc($h->keterangan)) ?>">
                                            <td class="text-center text-slate-400 text-sm font-semibold row-num"><?= $i + 1 ?></td>
                                            <td>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="date-badge <?= $isCuti ? 'bg-amber-50 text-slate-800 border border-warning' : ($isSunday ? 'bg-red-50 text-red-500 border border-danger' : 'bg-blue-50 text-blue-600 border border-primary') ?>">
                                                        <span class="font-bold text-base"><?= date('d', $tglTime) ?></span>
                                                        <span style="font-size: 9px; text-transform: uppercase;"><?= date('M', $tglTime) ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="font-bold text-slate-800 block"><?= date('d/m/Y', $tglTime) ?></span>
                                                        <span class="text-sm class="text-slate-400""><?= buat_tanggal(date('l', $tglTime)) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-semibold text-slate-800"><?= esc($h->keterangan) ?></div>
                                                <?php if ($isCuti): ?>
                                                    <span class="badge-tw badge-warning">
                                                        <i class="bi bi-tag-fill mr-1"></i> Cuti Bersama
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-tw badge-danger">
                                                        <i class="bi bi-flag-fill mr-1"></i> Libur Resmi
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn-tw btn-outline-primary border-0 rounded-full"
                                                        title="Edit Hari Libur"
                                                        onclick="editHoliday(<?= $h->id ?>, '<?= $h->tanggal ?>', '<?= esc(addslashes($h->keterangan)) ?>')">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn-tw btn-outline-danger border-0 rounded-full"
                                                        title="Hapus Hari Libur"
                                                        onclick="deleteHoliday(<?= $h->id ?>, '<?= date('d/m/Y', $tglTime) ?> - <?= esc(addslashes($h->keterangan)) ?>')">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // Set default date for input
        if (!$('#holidayDate').val()) {
            $('#holidayDate').val(new Date().toLocaleDateString('en-CA'));
        }
        filterHolidayTable();
    });

    function toggleHolidayMode(isRange) {
        $('#isRangeInput').val(isRange);
        if (isRange == 1) {
            $('#singleDateGroup').addClass('hidden');
            $('#rangeDateGroup').removeClass('hidden');
            $('#holidayDate').prop('required', false);
            $('#holidayDateStart').prop('required', true);
            $('#holidayDateEnd').prop('required', true);
        } else {
            $('#singleDateGroup').removeClass('hidden');
            $('#rangeDateGroup').addClass('hidden');
            $('#holidayDate').prop('required', true);
            $('#holidayDateStart').prop('required', false);
            $('#holidayDateEnd').prop('required', false);
        }
    }

    function editHoliday(id, date, desc) {
        $('#holidayId').val(id);
        $('#holidayDate').val(date);
        $('#holidayDesc').val(desc);
        
        // Switch to single mode
        $('#modeSingle').prop('checked', true);
        toggleHolidayMode(0);
        $('#modeSelectorGroup').addClass('hidden');

        $('#formCardTitle').html('<i class="bi bi-pencil-square text-blue-600 mr-1"></i> Edit Hari Libur');
        $('#btnSubmitHoliday').html('<i class="bi bi-save mr-1"></i> Perbarui');

        // Scroll to form smoothly
        $('html, body').animate({
            scrollTop: $("#formHoliday").offset().top - 100
        }, 300);
    }

    function resetForm() {
        $('#formHoliday')[0].reset();
        $('#holidayId').val('');
        $('#modeSelectorGroup').removeClass('hidden');
        $('#modeSingle').prop('checked', true);
        toggleHolidayMode(0);
        $('#formCardTitle').html('<i class="bi bi-plus-circle-fill text-blue-600 mr-1"></i> Tambah Hari Libur');
        $('#btnSubmitHoliday').html('<i class="bi bi-save mr-1"></i> Simpan');
        $('#holidayDate').val(new Date().toLocaleDateString('en-CA'));
    }

    $('#formHoliday').on('submit', function (e) {
        e.preventDefault();

        const btn = $('#btnSubmitHoliday');
        const origText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');

        const data = $(this).serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>';

        $.post('<?= base_url('admin/presensi/libur/save') ?>', data, function (res) {
            if (res.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disimpan!',
                    text: res.message || 'Data hari libur berhasil diperbarui.',
                    timer: 1400,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat menyimpan', 'error');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Terjadi kesalahan jaringan atau server', 'error');
        }).always(function () {
            btn.prop('disabled', false).html(origText);
        });
    });

    function deleteHoliday(id, desc) {
        Swal.fire({
            title: 'Hapus Hari Libur?',
            html: `Apakah Anda yakin ingin menghapus <strong>${desc}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= base_url('admin/presensi/libur/delete') ?>', {
                    id: id,
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                }, function (res) {
                    if (res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: res.message || 'Data hari libur berhasil dihapus.',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', res.message || 'Gagal menghapus', 'error');
                    }
                }, 'json').fail(function () {
                    Swal.fire('Error', 'Terjadi kesalahan koneksi server', 'error');
                });
            }
        });
    }

    function syncHolidays() {
        const year = $('#syncYearSelect').val() || new Date().getFullYear();
        const btn = $('#btnSync');

        Swal.fire({
            title: `Sinkronisasi Libur ${year}`,
            html: `Mengambil daftar hari libur nasional & cuti bersama resmi pemerintah Indonesia untuk tahun <strong>${year}</strong>...`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Mulai Sinkron',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.post('<?= base_url('admin/presensi/libur/sync') ?>', {
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                    year: year
                }).catch(error => {
                    Swal.showValidationMessage(`Gagal: ${error.statusText || 'Koneksi error'}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                if (result.value.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sinkronisasi Berhasil!',
                        text: result.value.message,
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', result.value.message || 'Gagal sinkronisasi data', 'error');
                }
            }
        });
    }

    function filterHolidayTable() {
        const keyword = ($('#filterHolidaySearch').val() || '').toLowerCase();
        const year = $('#filterYear').val();
        const month = $('#filterMonth').val();

        let visibleCount = 0;

        $('.holiday-row').each(function () {
            const rowYear = $(this).data('year').toString();
            const rowMonth = $(this).data('month').toString();
            const rowDesc = $(this).data('desc').toString();

            const matchSearch = !keyword || rowDesc.includes(keyword);
            const matchYear = !year || rowYear === year;
            const matchMonth = !month || rowMonth === month;

            if (matchSearch && matchYear && matchMonth) {
                $(this).show();
                visibleCount++;
                $(this).find('.row-num').text(visibleCount);
            } else {
                $(this).hide();
            }
        });

        $('#tableCounterBadge').text(visibleCount + ' Data');
    }
</script>
<?= $this->endSection() ?>
<?= $this->endSection(); ?>