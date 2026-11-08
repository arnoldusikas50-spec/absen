<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="container-fluid">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                    Absensi Harian
                </h3>
            </div>
            <div class="w-full sm:w-1/2 px-2">
                <ol class="breadcrumb sm:float-right mb-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>" class="text-decoration-none"><i
                                class="bi bi-house-door me-1"></i>Dashboard</a></li>
                    <li class="breadcrumb-item active">Absensi</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content-area">
    <div class="container-fluid">
        <div class="flex flex-wrap gap-4 justify-content-center">
            <div class="w-full md:w-1/2 px-2">
                <div class="card my-shadow">
                    <div class="card-body text-center">
                        <h4 class="mb-3">Status Hari Ini (<?= date('d M Y') ?>)</h4>
                        <?php if ($attendance): ?>
                            <p class="mb-1">Jam Masuk:
                                <strong><?= $attendance->jam_masuk ? date('H:i:s', strtotime($attendance->jam_masuk)) : '-' ?></strong>
                            </p>
                            <p class="mb-3">Jam Pulang:
                                <strong><?= $attendance->jam_pulang ? date('H:i:s', strtotime($attendance->jam_pulang)) : '-' ?></strong>
                            </p>
                        <?php else: ?>
                            <p class="text-muted">Belum ada data absensi hari ini</p>
                        <?php endif; ?>

                        <div id="actionArea">
                            <?php if (!$attendance || !$attendance->jam_masuk): ?>
                                <button id="btnCheckIn" class="btn-tw btn-success px-4 shadow-sm"><i
                                        class="bi bi-check2-circle me-1"></i> Absen Masuk</button>
                            <?php elseif (!$attendance->jam_pulang): ?>
                                <button id="btnCheckOut" class="btn-tw btn-danger px-4 shadow-sm"><i
                                        class="bi bi-door-open me-1"></i> Absen Pulang</button>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">Anda sudah absen masuk dan pulang.</div>
                            <?php endif; ?>
                        </div>
                        <small class="block mt-3 text-muted">Pastikan GPS aktif dan izinkan akses kamera bila
                            diminta.</small>

                        <div class="mt-4 border-top pt-3">
                            <a href="<?= site_url('presensi/rekap-denda') ?>" class="btn-tw btn-outline-danger w-100 shadow-sm"><i class="bi bi-wallet2 me-1"></i> Cek Detail Rekap Denda</a>
                        </div>

                        <!-- Preview Foto -->
                        <div class="mt-3" id="cameraContainer" style="display:none;">
                            <video id="videoPreview" class="rounded shadow-sm" width="100%" playsinline
                                autoplay></video>
                            <button id="btnCapture" class="btn-tw btn-primary mt-2"><i class="bi bi-camera-fill me-1"></i>
                                Ambil Foto</button>
                        </div>
                        <canvas id="canvasCapture" class="hidden"></canvas>
                        <img id="capturedImage" class="img-fluid rounded mt-2 hidden" alt="foto absen">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const settingFotoWajib = <?= isset($setting) && $setting->foto_wajib ? 'true' : 'false' ?>;
    const radiusMeter = <?= $setting->radius_meter ?? 100 ?>;
    const csrfTokenName = '<?= csrf_token() ?>';
    let csrfTokenValue = '<?= csrf_hash() ?>';

    let currentAction = null; // 'checkin' or 'checkout'
    let fotoBase64 = '';

    // Enhanced event handlers with error handling
    $(document).ready(function() {
        console.log('Absensi script loaded');
        
        // Cek denda yang belum dibayar
        $.get('<?= site_url('presensi/denda-ytd') ?>', function(res) {
            if (res.status && res.data && res.data.sisa_denda > 0) {
                if (sessionStorage.getItem('denda_alert_dismissed_' + res.data.sisa_denda)) {
                    return;
                }
                let sisaDenda = parseInt(res.data.sisa_denda).toLocaleString('id-ID');
                let totalDenda = parseInt(res.data.total_denda || res.data.sisa_denda).toLocaleString('id-ID');
                let terbayar = parseInt(res.data.terbayar || 0).toLocaleString('id-ID');

                Swal.fire({
                    html: `
                        <div class="px-2 py-2 text-center">
                            <div class="mb-3 d-inline-flex align-items-center justify-content-center" 
                                 style="width: 64px; height: 64px; background: #fee2e2; color: #dc2626; border-radius: 22px; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);">
                                <i class="bi bi-receipt-cutoff" style="font-size: 30px;"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Pemberitahuan Denda</h5>
                            <p class="text-muted small mb-3">Terdapat akumulasi denda absensi yang perlu diselesaikan.</p>
                            <div class="p-3 mb-3 text-start" 
                                 style="background: #fff5f5; border: 1.5px dashed #fca5a5; border-radius: 18px;">
                                <div class="flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" style="font-size: 10.5px; font-weight: 600; text-transform: uppercase;">Sisa Denda Belum Lunas</span>
                                    <span class="badge-tw badge-danger text-white rounded-pill px-2 py-0" style="font-size: 9.5px;">Belum Lunas</span>
                                </div>
                                <div class="fw-bold text-danger my-1" style="font-size: 24px;">Rp ${sisaDenda}</div>
                                <div class="flex justify-content-between text-muted pt-2 border-top" style="border-color: #fee2e2 !important; font-size: 11px;">
                                    <span>Total: <strong>Rp ${totalDenda}</strong></span>
                                    <span>Terbayar/Voucher: <strong class="text-success">Rp ${terbayar}</strong></span>
                                </div>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-wallet2 me-1"></i> Lihat Detail & Bayar',
                    cancelButtonText: 'Nanti Saja',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-5 border-0 shadow-lg px-2 py-3',
                        confirmButton: 'btn-tw btn-danger py-2 px-4 rounded-pill fw-bold shadow-sm me-2',
                        cancelButton: 'btn-tw btn-light py-2 px-4 rounded-pill text-muted fw-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '<?= site_url('presensi/rekap-denda') ?>';
                    } else {
                        sessionStorage.setItem('denda_alert_dismissed_' + res.data.sisa_denda, '1');
                    }
                });
            }
        }).fail(function() {
            console.error('Gagal mengambil data denda');
        });
        
        $(document).on('click', '#btnCheckIn', function () { 
            console.log('Check-in button clicked');
            currentAction = 'checkin'; 
            startProcess(); 
        });
        
        $(document).on('click', '#btnCheckOut', function () { 
            console.log('Check-out button clicked');
            currentAction = 'checkout'; 
            startProcess(); 
        });
        
        // Fallback for direct button binding
        $('#btnCheckIn').on('click', function () { 
            console.log('Check-in button clicked (fallback)');
            currentAction = 'checkin'; 
            startProcess(); 
        });
        
        $('#btnCheckOut').on('click', function () { 
            console.log('Check-out button clicked (fallback)');
            currentAction = 'checkout'; 
            startProcess(); 
        });
    });

    function startProcess() {
        if (settingFotoWajib) {
            initCamera();
        } else {
            requestGeolocation();
        }
    }

    function initCamera() {
        $('#cameraContainer').show();
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
                const video = document.getElementById('videoPreview');
                video.srcObject = stream;
                video.play();
            })
            .catch(err => {
                Swal.fire('Gagal akses kamera', err.message, 'error');
            });
    }

    $('#btnCapture').on('click', function () {
        const video = document.getElementById('videoPreview');
        const canvas = document.getElementById('canvasCapture');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        fotoBase64 = canvas.toDataURL('image/jpeg', 0.8);
        $('#capturedImage').attr('src', fotoBase64).removeClass('hidden');
        // stop camera
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(t => t.stop());
        }
        $('#cameraContainer').hide();
        requestGeolocation();
    });

    function requestGeolocation() {
        if (!navigator.geolocation) {
            Swal.fire('Error', 'Geolocation tidak didukung browser', 'error');
            return;
        }
        Swal.showLoading();
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            sendAttendance(lat, lng);
        }, err => {
            Swal.fire('Error', err.message, 'error');
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    function sendAttendance(lat, lng, confirm = 0) {
        // Enhanced error handling and debugging
        console.log('Sending attendance:', currentAction, { lat, lng, confirm });
        
        if (!lat || !lng) {
            Swal.fire('Error', 'Lokasi tidak valid. Pastikan GPS aktif.', 'error');
            return;
        }
        
        if (!currentAction) {
            Swal.fire('Error', 'Aksi tidak valid. Silakan refresh halaman.', 'error');
            return;
        }

        const url = `<?= site_url('absensi/') ?>${currentAction}`;
        console.log('Request URL:', url);
        
        Swal.showLoading();
        
        $.ajax({
            url: url,
            method: 'POST',
            data: { 
                lat: lat, 
                lng: lng, 
                foto: fotoBase64, 
                confirm: confirm,
                [csrfTokenName]: csrfTokenValue
            },
            dataType: 'json',
            success: res => {
                console.log('Response:', res);
                Swal.close();
                if (res.status) {
                    Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                } else {
                    let requireConfirm = res.require_confirm || (res.data && res.data.require_confirm);
                    if (res.data && res.data[csrfTokenName]) {
                        csrfTokenValue = res.data[csrfTokenName];
                    } else if (res[csrfTokenName]) {
                        csrfTokenValue = res[csrfTokenName];
                    }
                    if (requireConfirm) {
                        Swal.fire({
                            title: 'Peringatan Absensi',
                            text: res.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.showLoading();
                                sendAttendance(lat, lng, 1);
                            }
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            },
            error: (xhr, status, error) => {
                console.error('AJAX Error:', { xhr, status, error });
                Swal.close();
                let errorMsg = 'Terjadi kesalahan sistem';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMsg = xhr.statusText;
                }
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    }
</script>
<?= $this->endSection() ?>