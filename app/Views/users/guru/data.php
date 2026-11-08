<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shadow-xs shrink-0">
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
                <h6 class="font-bold text-slate-700"><i class="bi bi-person-video2 mr-2"></i><?= esc($subjudul) ?></h6>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" onclick="getDataGuru()" class="btn-tw btn-tw-sm btn-light">
                        <i class="bi bi-arrow-repeat mr-1"></i> Reload
                    </button>
                    <button type="button" class="btn-tw btn-tw-sm btn-success btn-sm shadow-sm ml-1"
                        data-action="aktifkan">
                        <i class="bi bi-people mr-1"></i> Aktifkan Semua
                    </button>
                    <button type="button" class="btn-tw btn-tw-sm btn-danger btn-sm shadow-sm ml-1"
                        data-action="nonaktifkan">
                        <i class="bi bi-slash-circle mr-1"></i> Nonaktifkan Semua
                    </button>
                </div>
            </div>
            <div class="card-tw-body">
                <div class="table-tw">
                    <div id="table-guru"></div>
                </div>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url() ?>/assets/app/js/ajax_request.js" type="text/javascript"></script>
<script type="text/javascript">
    const user_id = '<?= $user->id ?>';
    let table;

    $(document).ready(function () {
        // ajaxcsrf();

        $("#users").on("click", ".btn-reset", function () {
            $('#loading').removeClass('hidden');
            let username = $(this).data("username");
            let nama = $(this).data("nama");
            $.ajax({
                url: base_url + "userguru/reset_login",
                type: "POST", // Changed to POST as controller expects POST
                data: { username: username, nama: nama },
                success: function (response) {
                    $('#loading').addClass('hidden');
                    if (response.msg) {
                        if (response.status) {
                            Swal.fire({
                                title: "Sukses",
                                html: "<b>" + nama + response.msg + "<b>",
                                icon: "success"
                            });
                            getDataGuru()
                        } else {
                            Swal.fire({
                                title: "Error",
                                html: "<b>" + nama + response.msg + "<b>",
                                icon: "error"
                            });
                        }
                    }
                },
                error: function (xhr, status, error) {
                    $('#loading').addClass('hidden');
                    console.log(xhr);
                    Swal.fire({
                        title: "Gagal",
                        html: xhr.responseText,
                        icon: "error"
                    });
                }
            });
        });

        $(".btn-action").on("click", function () {
            console.log('click');
            let action = $(this).data("action");
            Swal.fire({
                title: action === 'aktifkan' ? "Aktifan Semua Guru" : "Nonaktifkan Semua Guru",
                text: "",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Lanjutkan"
            }).then(async result => {
                if (result.value) {
                    $('#loading').removeClass('hidden');
                    let formActivate = new FormData($('#up')[0])
                    formActivate.append('is_active', action)
                    // Updated to match controller method name if different, usually plural
                    await ajaxPost(base_url + "userguru/aktifkan_all", formActivate, function (error, response) {
                        $('#loading').addClass('hidden');
                        if (error) {
                            console.log('error', error.message);
                        } else {
                            console.log('response', response);
                            if (response.status) {
                                updateUser()
                            }
                        }
                    });
                }
            });

        });

        getDataGuru()
    });

    async function updateUser() {
        let formActivate = new FormData($('#up')[0])
        await ajaxPost(base_url + "userguru/update_iduser", formActivate, function (error, response) {
            if (error) {
                console.log('error', error.message);
            } else {
                console.log('response', response);
                // Laporan tautan akun juga perlu tampil saat status false,
                // kalau tidak admin tidak pernah tahu ada guru tanpa akun.
                Swal.fire({
                    title: response.status ? "Sukses" : "Perlu Diperiksa",
                    html: response.msg,
                    icon: response.status ? "success" : "warning"
                }).then(result => {
                    getDataGuru()
                });
            }
        });
    }

    function getDataGuru() {
        $('#loading').removeClass('hidden');
        ajaxGet(base_url + 'userguru/data', function (error, response) {
            if (error) {
                console.log('error', error.message);
            } else {
                console.log('response', response);
                populateTable(response)
            }
        });
    }

    function populateTable(data) {
        const tableContainer = document.getElementById("table-guru");
        tableContainer.innerHTML = '';

        const table = document.createElement("table");
        table.id = "users";
        table.className = "w-full table table-striped table-bordered table-hover dataTable";

        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");
        const headers = ["No.", "Nama", "Username", "Password", "Jabatan", "Reset Login", "Status", "Aksi"];

        headers.forEach((headerText, index) => {
            const th = document.createElement("th");
            if (index === 0) th.setAttribute('width', '40');
            th.className = "align-middle text-center p-0";
            th.textContent = headerText;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement("tbody");
        let num = 1;
        data.forEach(guru => {
            const row = document.createElement("tr");
            // nomor
            const cellNum = document.createElement("td");
            cellNum.className = "text-center align-middle";
            cellNum.textContent = num.toString();
            row.appendChild(cellNum);

            // nama
            const cellName = document.createElement("td");
            cellName.className = "align-middle";
            cellName.innerHTML = `<div class="flex items-center h-full">
                    <img class="avatar rounded-full justify-center align-self-center" src="${guru.foto}" width="40" height="40" alt="User Image">
                        <div class="ml-2 justify-center align-self-center">${guru.nama_guru}</div>
                </div>`;
            row.appendChild(cellName);

            // Username
            const cellUsername = document.createElement("td");
            cellUsername.className = "text-center align-middle";
            cellUsername.textContent = guru.username;
            row.appendChild(cellUsername);

            // password
            const cellPass = document.createElement("td");
            cellPass.className = "text-center align-middle";
            cellPass.textContent = guru.password;
            row.appendChild(cellPass);

            // Jabatan
            const cellLvl = document.createElement("td");
            cellLvl.className = "text-center align-middle";
            cellLvl.textContent = guru.level;
            row.appendChild(cellLvl);

            // Reset
            const cellReset = document.createElement("td");
            cellReset.className = "text-center align-middle";
            const resetButton = document.createElement("button");
            resetButton.type = "button";
            const no_reset = guru['reset'] === '0'
            resetButton.className = `btn btn-reset btn-sm ${no_reset ? 'btn-secondary' : 'btn-danger'} shadow-sm`;
            resetButton.setAttribute("title", "Reset Login");
            resetButton.setAttribute("data-username", guru.username);
            resetButton.setAttribute("data-nama", guru.nama_guru);
            if (no_reset) {
                resetButton.setAttribute("disabled", "disabled");
            }
            const icon = document.createElement("i");
            icon.className = "bi bi-arrow-repeat text-white";
            resetButton.appendChild(icon)
            cellReset.appendChild(resetButton);
            row.appendChild(cellReset);

            // Status
            const cellStatus = document.createElement("td");
            cellStatus.className = `text-center align-middle`;
            const badge = document.createElement("span");
            badge.className = `badge ${guru.active === "1" ? 'bg-success' : 'bg-danger'}`;
            badge.textContent = guru.active === "1" ? 'Aktif' : 'Nonaktif';
            cellStatus.appendChild(badge);
            row.appendChild(cellStatus);

            // Aksi
            const cellAksi = document.createElement("td");
            cellAksi.className = "text-center align-middle";

            const actionGroup = document.createElement('div');
            actionGroup.className = 'flex justify-center gap-1';

            const aksiButton = document.createElement("button");
            aksiButton.type = "button";
            const isActive = guru.active === "1";
            aksiButton.className = `btn btn-sm ${isActive ? 'btn-warning' : 'btn-success'} shadow-sm`;
            aksiButton.onclick = () => aktifkan(guru.id, guru.id_guru, guru.nama_guru, isActive ? 'nonaktifkan' : 'aktifkan');
            aksiButton.setAttribute("title", isActive ? "Nonaktifkan" : "Aktifkan");

            const iconAksi = document.createElement("i");
            if (isActive) {
                iconAksi.className = "bi bi-slash-circle text-white";
            } else {
                iconAksi.className = "bi bi-person-plus-fill text-white";
            }
            aksiButton.appendChild(iconAksi);
            actionGroup.appendChild(aksiButton);
            cellAksi.appendChild(actionGroup);
            row.appendChild(cellAksi);

            tbody.appendChild(row);
            num++;
        });

        table.appendChild(tbody);
        document.getElementById("table-guru").appendChild(table);

        $("#users").DataTable({
            order: [[0, "asc"]]
        });
    }

    function aktifkan(id, id_guru, nama_guru, isActive) {
        console.log(id, id_guru, nama_guru, isActive)
        $('#loading').removeClass('hidden');
        let formActivate = new FormData($('#up')[0])
        formActivate.append('is_active', isActive)
        if (id) formActivate.append('id', id)
        formActivate.append('id_guru', id_guru)
        formActivate.append('nama', nama_guru)
        ajaxPost(base_url + 'userguru/activate', formActivate, function (error, response) {
            $('#loading').addClass('hidden');
            if (error) {
                Swal.fire({
                    title: "Error",
                    html: error.message,
                    icon: "error"
                });
            } else {
                console.log('response_e', response);
                if (response.status) {
                    Swal.fire({
                        title: "Sukses",
                        text: response.msg,
                        icon: "success"
                    }).then(result => {
                        getDataGuru()
                    });
                } else {
                    let errs = response.msg;
                    if (response.users) {
                        for (const user of response.users) {
                            errs += `<br><b>- ${user.nama_guru}</b>`;
                        }
                    }

                    Swal.fire({
                        title: "Error",
                        html: errs,
                        icon: "error"
                    });
                }
            }
        })
    }
</script>
<?= $this->endSection() ?>