<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-person-badge text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Halaman') ?></span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="<?= site_url('dashboard') ?>" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800"><?= esc($subjudul ?? 'Data') ?></span>
        </nav>
    </div>
</div>

<div class="content-area">
<div class="card-tw"><div class="card-tw-header">
                <h6 class="font-bold text-slate-700"><i class="bi bi-people mr-2"></i><?= esc($subjudul) ?></h6>
                <div class="ml-auto flex items-center gap-2">
                    <?php if ($is_admin): ?>
                        <button type="button" class="btn-tw btn-tw-sm btn-success btn-sm shadow-sm ml-1"
                            data-action="aktifkan">
                            <i class="bi bi-people mr-1"></i> Aktifkan Semua
                        </button>
                        <button type="button" class="btn-tw btn-tw-sm btn-danger btn-sm shadow-sm ml-1"
                            data-action="nonaktifkan">
                            <i class="bi bi-slash-circle mr-1"></i> Nonaktifkan Semua
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-tw-body">
                <div class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-6">
                        <div class="">
                            <label class="form-label-tw">Show
                                <select id="users_length" class="form-select-tw">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </label>
                        </div>
                        <div class="flex-1">
                            <div class="flex gap-2 items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="check-reset">
                                    <label class="form-check-label text-sm" for="check-reset">Hanya yang perlu
                                        reset</label>
                                </div>
                                <input id="input-search" type="search" class="form-control-tw"
                                    placeholder="Cari...">
                                <button id="btn-search" type="button" class="btn-tw btn-tw-sm btn-primary"
                                    onclick="applySearch()" disabled>
                                    <i class="bi bi-search"></i>
                                </button>
                                <button id="btn-clear" type="button" class="btn-tw btn-tw-sm btn-secondary" disabled>
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?= form_open('', array('id' => 'bulk')); ?>
                <?= csrf_field() ?>
                <div class="table-tw">
                    <table id="users" class="table-tw w-full">
                        <thead>
                            <tr>
                                <th width="40" class="align-middle text-center p-0">No.</th>
                                <th class="align-middle">NIS</th>
                                <th class="align-middle">Nama</th>
                                <th class="text-center align-middle">Kelas</th>
                                <th class="align-middle">Username</th>
                                <th class="align-middle">Password</th>
                                <th class="text-center align-middle">Reset Login</th>
                                <?php if ($is_admin): ?>
                                    <th class="align-middle text-center">Status/Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                        </tbody>
                    </table>
                </div>
                <?= form_close() ?>
                <div class="mt-6">
                    <div class="float-start" id="total-entri"></div>
                    <nav aria-label="Page navigation" class="ml-auto">
                        <ul class="pagination mb-0" id="pagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
<?= form_open('', array('id' => 'pager')); ?>
<input type="hidden" id="pager-page" name="page" value="1">
<input type="hidden" id="pager-limit" name="limit" value="10">
<?= form_close() ?>

<script src="<?= base_url() ?>assets/app/js/jquery.twbsPagination.js" type="text/javascript"></script>
<script type="text/javascript">
    let currentPage = 1;
    let perPage = 10;
    let isAdmin = "<?= $is_admin ? '1' : '0' ?>";
    let $pagination, defaultOpts, query;

    $(document).ready(async function () {
        // ajaxcsrf();

        $pagination = $('#pagination');
        defaultOpts = {
            visiblePages: 5, // CI4 pagination usually handles HTML, but here using JS plugin
            initiateStartPageClick: false,
            onPageClick: function (event, page) {
                console.info(page + ' (from options)');
                currentPage = page;
                loadSiswa();
            }
        };
        $pagination.twbsPagination(defaultOpts);

        $('#users_length').change(function () {
            $('#pager-limit').val($(this).val());
            perPage = $(this).val();
            currentPage = 1;
            loadSiswa();
        });

        $('#check-reset').on('change', function () {
            currentPage = 1;
            loadSiswa();
        });

        $('#input-search').on('change keyup', function () {
            var val = $(this).val();
            query = val === "" ? null : val;
            $('#btn-clear').attr('disabled', query == null)
            $('#btn-search').attr('disabled', query == null)
        });

        $('#btn-clear').on('click', function () {
            query = null;
            currentPage = 1;
            $(this).attr('disabled', query == null);
            $('#btn-search').attr('disabled', query == null);
            loadSiswa();
        });

        $("#users").on("click", ".btn-aktif", async function () {
            let id = $(this).data("id");
            let nama = encodeURIComponent($(this).data("nama"));
            $('#loading').removeClass('hidden');

            let formActivate = new FormData($('#pager')[0])
            formActivate.append('is_active', 'aktifkan')
            formActivate.append('id_siswa', id)
            formActivate.append('nama', nama)
            const data = await ajaxPost('usersiswa/activate', formActivate)
            console.log("response:", data);
            if (data.status) {
                Swal.fire({
                    title: "Sukses",
                    text: data.msg,
                    icon: "success"
                }).then(result => {
                    loadSiswa();
                });
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.msg,
                    icon: "error"
                });
            }
        });

        $("#users").on("click", ".btn-nonaktif", async function () {
            let id = $(this).data("id");
            let username = $(this).data("username");
            let nama = encodeURIComponent($(this).data("nama"));
            $('#loading').removeClass('hidden');

            let formActivate = new FormData($('#pager')[0])
            formActivate.append('is_active', 'nonaktifkan')
            formActivate.append('id_siswa', id)
            formActivate.append('username', username)
            formActivate.append('nama', nama)
            const data = await ajaxPost('usersiswa/activate', formActivate)
            console.log("response:", data);
            if (data.status) {
                Swal.fire({
                    title: "Sukses",
                    html: data.msg,
                    icon: "success"
                }).then(result => {
                    loadSiswa();
                });
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.msg,
                    icon: "error"
                });
            }
        });

        $("#users").on("click", ".btn-reset", async function () {
            let username = $(this).data("username");
            let id = $(this).data("id");
            let nama = encodeURIComponent($(this).data("nama"));
            $('#loading').removeClass('hidden');

            let formActivate = new FormData($('#pager')[0])
            formActivate.append('id_siswa', id)
            formActivate.append('username', username)
            formActivate.append('nama', nama)
            const response = await ajaxPost('usersiswa/reset_login', formActivate)
            if (response.status) {
                Swal.fire({
                    title: "Sukses",
                    html: "<b>" + decodeURIComponent(response.msg) + "<b>",
                    icon: "success"
                }).then(result => {
                    loadSiswa();
                });
            } else {
                Swal.fire({
                    title: "Error",
                    html: "<b>" + decodeURIComponent(response.msg) + "<b>",
                    icon: "error"
                });
            }
            $('#loading').addClass('hidden');
        });

        $(".btn-action").on("click", function () {
            let action = $(this).data("action");

            Swal.fire({
                title: action === 'aktifkan' ? "Aktifan semua siswa" : "Nonaktifkan semua siswa",
                text: "",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Lanjutkan"
            }).then(async result => {
                if (result.value) {
                    $('#loading').removeClass('hidden');
                    Swal.fire({
                        title: action === 'aktifkan' ? "Mengaktifkan semua siswa" : "Menonaktifkan semua siswa",
                        text: "Silahkan tunggu....",
                        allowEscapeKey: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    let formActivate = new FormData($('#pager')[0])
                    formActivate.append('is_active', action)

                    const response = await ajaxPost("usersiswa/aktifkansemua", formActivate)
                    console.log("result", response);
                    if (response.status) {
                        Swal.fire({
                            title: response.status ? "Sukses" : "Gagal",
                            html: response.msg,
                            icon: response.status ? "success" : "error"
                        }).then(result => {
                            loadSiswa();
                        });
                    }
                    $('#loading').addClass('hidden');
                }
            });
        });

        await loadSiswa();

    });

    async function loadSiswa() {
        $('#pager-page').val(currentPage);
        $('#loading').removeClass('hidden');

        let dataPost = new FormData($('#pager')[0])
        if (query != null) dataPost.append('search', query)
        const reset = $('#check-reset').is(':checked') ? '1' : '0';
        dataPost.append('reset', reset);

        const data = await ajaxPost('usersiswa/list', dataPost)
        if (data.pages > 0) {
            $pagination.removeClass('hidden');
            $pagination.twbsPagination('destroy');
            $pagination.twbsPagination($.extend({}, defaultOpts, {
                startPage: currentPage,
                totalPages: data.pages,
            }));
        }
        previewData(data);

        $('#loading').addClass('hidden');
        $('#input-search').val(data.search);
    }

    function previewData(data) {
        console.log(data);
        $('#input-search').val(data.search);
        var html = '';
        if (data.lists.length > 0) {
            $.each(data.lists, function (idx, siswa) {
                const kls = siswa.nama_kelas != null ? siswa.nama_kelas : '';
                html += `<tr>
                <td class="text-center align-middle">
                    <input type="hidden" name="ids[]" value="${siswa.id_siswa}">
                    ${Number((perPage * (currentPage - 1)) + (idx + 1))}</td>
            <td class="align-middle">${siswa.nis}</td>
            <td>
                <div class="flex items-center h-full">
                    <img class="avatar rounded-full justify-center align-self-center shadow-sm" src="${siswa.foto}" width="40" height="40" alt="User Image">
                        <div class="ml-2 grow justify-center align-self-center font-bold text-slate-800">${siswa.nama}</div>
                </div>
            </td>
            <td class="align-middle text-center"><span class="badge-tw badge-secondary">${kls}</span></td>
            <td class="align-middle">${siswa.username}</td>
            <td class="align-middle text-break">${siswa.password}</td>
            <td class="text-center align-middle">
                <button type="button" class="btn-tw btn-sm ${siswa.reset == '0' ? 'btn-secondary' : 'btn-danger'} shadow-sm btn-reset" ${siswa.reset == '0' ? 'disabled' : ''} data-id="${siswa.id_siswa}" data-username="${siswa.username}" data-nama="${siswa.nama}" title="Reset Login"> 
                    <i class="bi bi-arrow-repeat text-white"></i>
                </button>
            </td>`;
                if (isAdmin === '1') {
                    html += `<td class="text-center align-middle">
                        <div class="flex justify-center gap-1 items-center">`;
                    if (siswa.active === "1") {
                        html += `<span class="badge-tw badge-success">Aktif</span>
                                 <button type="button" class="btn-tw btn-nonaktif btn-warning btn-sm shadow-sm" data-id="${siswa.id_siswa}" data-username="${siswa.username}" data-nama="${siswa.nama}" title="Nonaktifkan">
                                     <i class="bi bi-slash-circle text-white"></i>
                                 </button>`;
                    } else {
                        html += `<span class="badge-tw badge-danger">Nonaktif</span>
                                 <button type="button" class="btn-tw btn-aktif btn-success btn-sm shadow-sm" data-id="${siswa.id_siswa}" data-nama="${siswa.nama}" title="Aktifkan">
                                     <i class="bi bi-person-plus-fill text-white"></i>
                                 </button>`;
                    }
                    html += `</div></td>`;
                }
                html += `</tr>`;
            });

            const loaded = Number(perPage * (currentPage - 1)) + 1;
            const to = loaded + data.lists.length;
            $('#total-entri').html(`${loaded} ~ ${to - 1} dari ${data.total} entri`);
        } else {
            $('#total-entri').html(`0 ~ 0 dari 0 entri`);
            let colCount = isAdmin === '1' ? 8 : 7;
            html += `<tr><td colspan="${colCount}" class="text-center align-middle">Tidak ada data siswa</td><tr>`;
        }

        $('#table-body').html(html);
    }

    function applySearch() {
        query = $('#input-search').val();
        currentPage = 1;
        loadSiswa();
    }

    async function ajaxPost(url, formData) {
        return new Promise((resolve, reject) => {
            setTimeout(function () {
                $.ajax({
                    url: base_url + url,
                    method: "POST",
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function (respon) {
                        resolve(respon);
                    },
                    error: function (xhr, status, error) {
                        $('#loading').addClass('hidden');
                        console.log(xhr);
                        Swal.fire({
                            title: "Gagal",
                            html: xhr.responseText,
                            icon: "error"
                        });
                        reject(error);
                    }
                });
            }, 500)
        });
    }
</script>
<?= $this->endSection() ?>