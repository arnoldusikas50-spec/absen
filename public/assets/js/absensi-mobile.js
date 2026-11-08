/**
 * Attendance Mobile JavaScript
 * Dependencies: jQuery, SweetAlert2
 */

const AbsensiApp = (function () {
    'use strict';

    // Configuration
    let config = {
        baseUrl: '',
        csrfToken: '',
        csrfName: 'csrf_test_name',
        fotoWajib: 0,
        currentMode: 'checkin', // checkin, checkout, done
        isHoliday: false,
        holidayReason: '',
        // "Hari ini" dari server (appTimezone); diisi halaman lewat init.
        hariIni: '',
        endpoints: {
            checkin: '/guru/absensi/checkin',
            checkout: '/guru/absensi/checkout',
            history: '/presensi/history',
            myAttendance: '/presensi/my-attendance',
            myCorrections: '/presensi/my-corrections',
            koreksi: '/presensi/koreksi'
        }
    };

    // State
    let state = {
        izinKuota: null,
        stream: null,
        latVal: null,
        lngVal: null,
        accuracyVal: null,
        isModelLoaded: false,
        isContextActive: false,
        faceDetected: false,
        lastFaceDescriptor: null,
        isAutoCapturing: false,
        stableFrames: 0,
        autoCaptureEnabled: true,
        audioCtx: null,
        dinasMode: 0,
        wfhMode: 0,
        wfhQuota: {},
        inRange: null,
        closestLocation: null
    };

    // Safe URL builder to prevent double or missing slashes
    function apiUrl(path) {
        if (!path) return config.baseUrl || '';
        if (/^https?:\/\//i.test(path)) return path;
        const base = (config.baseUrl || '').replace(/\/+$/, '');
        const cleanPath = String(path).replace(/^\/+/, '');
        return base ? (base + '/' + cleanPath) : ('/' + cleanPath);
    }

    // Initialize
    function init(options) {
        Object.assign(config, options);
        if (config.baseUrl) {
            config.baseUrl = config.baseUrl.replace(/\/+$/, '');
        }
        if (config.endpoints) {
            for (const k in config.endpoints) {
                if (typeof config.endpoints[k] === 'string') {
                    config.endpoints[k] = '/' + config.endpoints[k].replace(/^\/+/, '');
                }
            }
        }

        bindEvents();
        // Pre-load models if possible
        loadFaceModels();
        migrateLegacyOutbox();
        checkOfflineQueue();
        fetchWfhQuota();
        fetchIzinKuota();
    }

    // Modul FaceScan lama (sudah dihapus) memakai key antrean sendiri. Pindahkan
    // sisa item ke antrean utama supaya tidak ada presensi offline yang yatim.
    function migrateLegacyOutbox() {
        const OLD_KEY = 'presensi_outbox_v1';
        let old;
        try {
            old = JSON.parse(localStorage.getItem(OLD_KEY) || 'null');
        } catch (e) {
            old = null;
        }
        if (!Array.isArray(old) || !old.length) return;
        try {
            const queue = JSON.parse(localStorage.getItem('attendance_queue') || '[]');
            old.forEach(function (it) {
                if (!it || !it.url || !it.data) return;
                queue.push({
                    url: it.url,
                    data: it.data,
                    created: it.ts || Date.now(),
                    userId: config.userId
                });
            });
            localStorage.setItem('attendance_queue', JSON.stringify(queue));
            localStorage.removeItem(OLD_KEY);
            console.log('Migrated ' + old.length + ' item antrean FaceScan lama ke attendance_queue');
        } catch (e) {
            console.warn('Migrasi antrean offline gagal:', e);
        }
    }

    async function fetchWfhQuota() {
        try {
            const res = await $.getJSON(apiUrl('/presensi/wfh-quota'));
            if (res.status && res.data) {
                state.wfhQuota = res.data;
                const $container = $('#wfhCheckContainer');
                const $quotaText = $('#wfhQuotaText');
                const $checkbox = $('#wfhCheck');
                if ($container.length) {
                    const canCheckin = res.data.wfh_enabled && res.data.wfh_remaining > 0 && !res.data.already_checked_in_non_wfh;
                    if (res.data.wfh_enabled && res.data.wfh_remaining > 0 && !res.data.already_checked_in) {
                        $container.show();
                        $checkbox.prop('disabled', false);
                        $quotaText.text('Sisa kuota: ' + res.data.wfh_remaining + '/' + res.data.wfh_max + ' bulan ini');
                    } else if (!res.data.wfh_enabled) {
                        $container.hide();
                    } else if (res.data.already_checked_in) {
                        $container.show();
                        $checkbox.prop('disabled', true);
                        if (res.data.today_is_wfh) {
                            $quotaText.text('Sudah absen WFH hari ini');
                            $checkbox.prop('checked', true);
                        } else {
                            $quotaText.text('Sudah absen hari ini (non-WFH)');
                        }
                    } else {
                        $container.show();
                        $checkbox.prop('disabled', true);
                        $quotaText.text('Kuota WFH habis bulan ini');
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to fetch WFH quota:', e);
        }
    }

    async function loadFaceModels() {
        if (state.isModelLoaded) return;

        let base = (config.assetsUrl || (config.baseUrl ? config.baseUrl.replace(/\/+$/, '') + '/assets' : '/assets')).replace(/\/+$/, '');
        base = base.replace(/\/index\.php\/?/i, '');
        const localUrl = base + '/models';
        const cdnUrl = 'https://justadudewhohacks.github.io/face-api.js/models';

        // Try local first (faster, works offline), then CDN as fallback
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(localUrl),
                faceapi.nets.faceLandmark68Net.loadFromUri(localUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(localUrl)
            ]);
            console.log("FaceAPI Models Loaded (Local)");
            state.isModelLoaded = true;
            document.getElementById('btnManualCapture')?.classList.remove('loading');
            $('#facedetect-status').html('<small class="text-emerald-500 fs-blink"><i class="bi bi-camera"></i> Arahkan wajah ke lingkaran</small>');
        } catch (e) {
            console.warn("Local FaceAPI Load Failed, trying CDN...", e);
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(cdnUrl),
                    faceapi.nets.faceLandmark68Net.loadFromUri(cdnUrl),
                    faceapi.nets.faceRecognitionNet.loadFromUri(cdnUrl)
                ]);
                console.log("FaceAPI Models Loaded (CDN)");
                state.isModelLoaded = true;
                document.getElementById('btnManualCapture')?.classList.remove('loading');
                $('#facedetect-status').html('<small class="text-emerald-500 fs-blink"><i class="bi bi-camera"></i> Arahkan wajah ke lingkaran</small>');
            } catch (ex) {
                console.error("All FaceAPI Sources Failed", ex);
                document.getElementById('btnManualCapture')?.classList.remove('loading');
                $('#facedetect-status').html('<small class="text-amber-500">Gagal memuat AI Wajah. Gunakan tombol ambil foto.</small>');
            }
        }
    }

    // Bind Events
    function bindEvents() {
        // Tab navigation
        $(document).on('click', '[data-tab]', function () {
            bukaTab($(this).data('tab'));
        });

        // Attendance Button (Main & Nav)
        $(document).on('click', '#btn-absen', function() {
            handleScanClick();
        });
        $(document).on('click', '#nav-btn-absen', function(e) {
            e.preventDefault();
            handleScanClick();
        });

        // Mutual exclusivity: WFH and Dinas cannot both be checked
        $(document).on('change', '#dinasCheck', function() {
            if ($(this).is(':checked')) {
                $('#wfhCheck').prop('checked', false);
            }
        });
        $(document).on('change', '#wfhCheck', function() {
            if ($(this).is(':checked')) {
                $('#dinasCheck').prop('checked', false);
            }
        });

        // "Sampai Tanggal" ikut mengunci "Mulai Tanggal" agar rentang mundur
        // tidak bisa dipilih, tanpa melonggarkan batas bawah dari server.
        $(document).on('change', '#izinTglMulai', function() {
            const $selesai = $('#izinTglSelesai');
            if (!$selesai.length) return;
            const batasServer = $(this).attr('min') || '';
            const mulai = this.value || '';
            const batas = mulai && mulai > batasServer ? mulai : batasServer;
            $selesai.attr('min', batas);
            if ($selesai.val() && $selesai.val() < batas) $selesai.val('');
        });

        // "Coba lagi" pada state gagal-muat (riwayat & koreksi).
        $(document).on('click', '[data-muat-history]', function (e) {
            e.preventDefault();
            loadHistory();
        });
        $(document).on('click', '[data-muat-koreksi]', function (e) {
            e.preventDefault();
            loadKoreksiData();
        });
    }

    // Tab Management: manajer tab halaman /presensi adalah window.showTab
    // (didefinisikan di absen_mobile.php). Dulu modul ini punya showTab
    // sendiri, sehingga satu ketukan pada grid aksi menjalankan dua manajer:
    // data tab (riwayat/koreksi) dimuat dua kali dan animasi halaman tidak
    // pernah terlihat.
    function bukaTab(tab) {
        if (typeof window.showTab === 'function') {
            window.showTab(tab);
        }
    }

    // GPS Location
    function getLocationPromise() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                const e = new Error("Geolocation tidak didukung browser ini.");
                e.code = 0;
                reject(e);
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    resolve({
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy
                    });
                },
                (err) => {
                    reject(err);
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        });
    }

    function setLocActionsVisible(visible) {
        const $actions = $('#locActions');
        if ($actions.length) {
            $actions.toggleClass('show', !!visible);
            $actions.css('display', visible ? 'flex' : 'none');
        }
        const $retry = $('#locRetryBtn');
        if ($retry.length) {
            $retry.find('i').removeClass('bi-spin');
        }
    }

    function showLocationHelp() {
        Swal.fire({
            icon: 'info',
            title: 'Cara Mengizinkan Lokasi',
            html: `
                <div class="text-left" style="font-size:13px;line-height:1.6;">
                    <p class="mb-2">Agar absen bisa mendeteksi posisi, izinkan akses lokasi di browser/HP:</p>
                    <ol class="ps-3 mb-2">
                        <li><b>Chrome Android:</b> ketuk ikon <i class="bi bi-lock-fill"></i> / <i class="bi bi-gear-fill"></i> di address bar &rarr; <b>Permissions / Izin</b> &rarr; <b>Lokasi</b> &rarr; <b>Izinkan</b>.</li>
                        <li>Aktifkan <b>GPS / Lokasi</b> di HP, pilih mode <b>Akurasi tinggi</b>.</li>
                        <li>Kembali ke halaman ini lalu ketuk <b>Muat Ulang Lokasi</b>.</li>
                        <li>Jika masih gagal, pindah ke area terbuka (tidak di dalam gedung tertutup).</li>
                    </ol>
                    <p class="mb-0 text-slate-500">Tips: gunakan HTTPS & bukan mode Incognito yang memblokir lokasi.</p>
                </div>`,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#e11d48',
            showCancelButton: true,
            cancelButtonText: 'Muat Ulang Lokasi',
            cancelButtonColor: '#16a34a',
            reverseButtons: true
        }).then((res) => {
            if (res.dismiss === Swal.DismissReason.cancel) {
                detectLocation();
            }
        });
    }

    // Haversine Formula for Distance (Meters)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3; // metres
        const φ1 = lat1 * Math.PI / 180; // φ, λ in radians
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c; // in metres
    }

    function detectLocation() {
        const $locText = $('#locText');
        const $locDot = $('#locDot, .fs-loc-dot');
        $locText.html('<i class="bi bi-geo-alt"></i> Mendeteksi lokasi...');
        $locText.removeClass('loc-error text-emerald-500 text-red-500').addClass('text-white');
        $locDot.removeClass('dot-error dot-ok');
        setLocActionsVisible(false);
        const $retryIcon = $('#locRetryBtn i');
        if ($retryIcon.length) $retryIcon.addClass('bi-spin');

        getLocationPromise().then(pos => {
            setLocActionsVisible(false);
            $locDot.removeClass('dot-error dot-ok');
            $locText.removeClass('loc-error');
            state.latVal = pos.lat;
            state.lngVal = pos.lng;
            state.accuracyVal = pos.accuracy;

            // Start with closest = null
            let closest = null;
            let minDist = Infinity;

            if (config.locations && config.locations.length > 0) {
                config.locations.forEach(loc => {
                    const locLat = parseFloat(loc.lat);
                    const locLng = parseFloat(loc.lng);
                    const radius = parseFloat(loc.radius_meter);

                    const dist = calculateDistance(pos.lat, pos.lng, locLat, locLng);

                    // Always track the closest location
                    if (dist < minDist) {
                        minDist = dist;
                        closest = {
                            name: loc.nama_lokasi,
                            dist: dist,
                            radius: radius,
                            inRange: dist <= radius
                        };
                    }
                });
            }

            if (closest) {
                state.inRange = closest.inRange;
                state.closestLocation = closest;
                if (closest.inRange) {
                    // Inside Radius
                    $('#locText').html(`<i class="bi bi-geo-alt-fill text-emerald-500"></i> ${closest.name} (${Math.round(closest.dist)}m)`);
                    $('#locDot, .fs-loc-dot').removeClass('dot-error').addClass('dot-ok');
                    $('#outsideAreaAlert').addClass('hidden');
                    $('.camera-options-card').removeClass('border-warning');
                } else {
                    // Outside Radius
                    $('#locText').html(`<i class="bi bi-geo-alt-fill text-red-500"></i> Luar Area: ${closest.name} (${Math.round(closest.dist)}m)`);
                    $('#locDot, .fs-loc-dot').removeClass('dot-ok').addClass('dot-error');
                    $('#outsideAreaAlert').removeClass('hidden');
                    $('.camera-options-card').addClass('border-warning');
                }
            } else {
                state.inRange = false;
                state.closestLocation = null;
                if (!config.locations || config.locations.length === 0) {
                    // No locations configured at all -> Show Warning
                    $('#locText').html(`<i class="bi bi-exclamation-triangle-fill text-amber-500"></i> Lokasi Belum Disetting Admin`);
                    $('#locDot, .fs-loc-dot').removeClass('dot-ok dot-error');
                    $('#outsideAreaAlert').removeClass('hidden').html('<i class="bi bi-exclamation-triangle-fill me-1"></i> Titik lokasi belum disetting admin. Hubungi admin sekolah.');
                } else {
                    // Should not happen if logic is correct (closest will always be set if locations > 0)
                    let accText = state.accuracyVal < 50 ? '✓' : `±${Math.round(state.accuracyVal)}m`;
                    $('#locText').text(`${state.latVal.toFixed(5)}, ${state.lngVal.toFixed(5)} ${accText}`);
                    $('#locDot, .fs-loc-dot').removeClass('dot-ok dot-error');
                }
            }

        }).catch(err => {
            state.latVal = null;
            state.lngVal = null;
            state.accuracyVal = null;
            state.inRange = null;
            state.closestLocation = null;
            const code = (err && typeof err.code !== 'undefined') ? err.code : 0;
            let msg = 'Lokasi gagal dideteksi. Ketuk Muat Ulang.';
            if (code === 1) {
                msg = 'Izin lokasi ditolak. Ketuk Izinkan Lokasi lalu Muat Ulang.';
            } else if (code === 2) {
                msg = 'Sinyal GPS lemah. Pindah ke area terbuka lalu Muat Ulang.';
            } else if (code === 3) {
                msg = 'Waktu deteksi habis. Ketuk Muat Ulang Lokasi.';
            } else if (err && err.message && /didukung/i.test(err.message)) {
                msg = 'Browser tidak mendukung GPS. Gunakan Chrome terbaru.';
            }
            $('#locText').html(`<i class="bi bi-exclamation-triangle-fill text-red-500"></i> ${msg}`);
            $('#locText').addClass('loc-error');
            $('#locDot, .fs-loc-dot').addClass('dot-error');
            setLocActionsVisible(true);
            console.error('detectLocation failed:', err);
        });
    }

    // Camera Functions
    async function openCamera() {
        state.dinasMode = 0;
        state.wfhMode = 0;
        dinasReason = '';
        
        if (config.currentMode === 'done') {
            showAlert('info', 'Selesai', 'Anda sudah menyelesaikan absensi hari ini.');
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showAlert('error', 'Kamera Tidak Tersedia',
                'Browser Anda tidak mendukung akses kamera atau tidak menggunakan HTTPS.');
            return;
        }

        $('#cameraModal').removeClass('hidden').addClass('show');

        // Start GPS early, in parallel with camera request
        detectLocation();

        $('#cameraTitle').text(config.currentMode === 'checkin' ? 'Absen Masuk' : 'Absen Pulang');

        state.faceDetected = false;
        state.lastFaceDescriptor = null;   // vektor lama tidak boleh dipakai sesi baru
        state.isAutoCapturing = false;
        state.stableFrames = 0;
        $('#faceGuide').removeClass('is-ok is-warn');
        $('#auto-progress').hide();
        $('#auto-progress-bar').css('width', '0%');
        $('#fsCamOff').removeClass('is-hidden');
        $('#facedetect-status').html('Memuat deteksi wajah...');

        // Tandai shutter sebagai "loading" sampai model selesai dimuat
        if (config.fotoWajib === 1 && !state.isModelLoaded) {
            document.getElementById('btnManualCapture')?.classList.add('loading');
        }

        // Muat model dan buka kamera secara PARALEL — tidak saling tunggu.
        // Sebelumnya model di-await dulu, baru getUserMedia dipanggil;
        // ini menambah ~1-2 detik latensi ekstra pada sesi pertama.
        // Sekarang keduanya berjalan bersamaan: kamera mulai warm-up
        // sementara model selesai diunduh/dimuat dari cache.
        const modelPromise = (config.fotoWajib === 1 && !state.isModelLoaded)
            ? loadFaceModels().catch(err => { console.warn("Model load parallel failed", err); })
            : Promise.resolve();

        // Portrait (480x640) supaya aspect-nya cocok dengan stage 3/4
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 640 } },
            audio: false
        }).then(stream => {
            state.stream = stream;
            const videoEl = document.getElementById('videoElement');
            if (videoEl) {
                videoEl.srcObject = stream;

                // Sembunyikan placeholder "kamera mati" begitu video benar-benar berjalan
                const hideCamPlaceholder = () => $('#fsCamOff').addClass('is-hidden');
                videoEl.addEventListener('playing', hideCamPlaceholder, { once: true });
                videoEl.addEventListener('loadeddata', hideCamPlaceholder, { once: true });
                setTimeout(hideCamPlaceholder, 3500);

                videoEl.play().catch(() => {});

                // Hook for Face Detection — tunggu model selesai dulu
                // (paralel dengan getUserMedia, jadi biasanya sudah done)
                videoEl.onloadedmetadata = async () => {
                    await modelPromise;
                    startFaceDetection(videoEl);
                };

                // Fallback if metadata event is slow
                setTimeout(async () => {
                    if (videoEl.videoWidth > 0 && !window.faceRafRunning) {
                        await modelPromise;
                        startFaceDetection(videoEl);
                    }
                }, 2000);
            }

        }).catch(err => {
            closeCamera();
            showAlert('error', 'Gagal Akses Kamera', err.message);
        });
    }

    // ── Tuning auto-capture & toleransi kedip ───────────────────
    const TICK_MS = 180;                 // throttle rAF: deteksi box ≤ 1× per 180ms (~5-6fps)
    const STABLE_FRAMES = 10;            // ~2 detik wajah stabil
    const BLINK_ALLOWANCE = 3;           // toleransi kedip/lempar ~0,6 detik
    const DESCRIPTOR_EVERY = 5;          // pass mahal tiap 5 tick (~1 detik)

    // ── Opsi detektor bersama (dipakai di startFaceDetection & fallback processAttendance) ──
    // Definisi di scope modul agar bisa dipakai tanpa perlu instansiasi ulang setiap sesi.
    // faceapi harus sudah dimuat sebelum baris ini dieksekusi — aman karena
    // konstanta ini hanya dibaca saat fungsi dipanggil, bukan saat modul dimuat.
    const FACE_OPTS_FAST = () => new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.3 });
    const FACE_OPTS_FULL = () => new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.25 });

    // ── Panduan jarak & posisi wajah ───────────────────────────
    // Cerminkan geometri .face-guide (CSS): width 58%, aspect-ratio .88, top 47%.
    const GUIDE_W_RATIO = 0.58;
    const GUIDE_ASPECT = 0.88;
    const GUIDE_TOP_RATIO = 0.47;
    const FACE_MIN_RATIO = 0.58;         // < ini → terlalu jauh
    const FACE_MAX_RATIO = 1.30;         // > ini → terlalu dekat (dahi terpotong)

    // ── Deteksi low-light ──────────────────────────────────────
    const DARK_LUMA = 62;                // rata-rata luminansi 0-255
    const LUMA_SAMPLE_EVERY = 4;         // sampling tiap 4 tick (~1 detik)

    function createLumaProbe() {
        const cv = document.createElement('canvas');
        cv.width = 32; cv.height = 24;
        let ctx = null;
        try { ctx = cv.getContext('2d', { willReadFrequently: true }); } catch (e) {}
        return {
            // Rata-rata luminansi (Rec.709). -1 = belum bisa diukur.
            read: function (video) {
                if (!ctx || !video || !video.videoWidth) return -1;
                try {
                    ctx.drawImage(video, 0, 0, cv.width, cv.height);
                    const d = ctx.getImageData(0, 0, cv.width, cv.height).data;
                    let sum = 0;
                    for (let i = 0; i < d.length; i += 4) {
                        sum += 0.2126 * d[i] + 0.7152 * d[i + 1] + 0.0722 * d[i + 2];
                    }
                    return sum / (d.length / 4);
                } catch (e) {
                    return -1;
                }
            }
        };
    }

    function evaluateFraming(box, video) {
        const vw = video.videoWidth || 0, vh = video.videoHeight || 0;
        const cw = video.clientWidth || 0, ch = video.clientHeight || 0;
        // Tanpa metrik kotak, jangan kunci capture: wajah sudah terbukti ada
        // oleh detektor, jadi perlakukan sebagai pas-bingkai (fail-open).
        if (!box || !vw || !vh || !cw || !ch) {
            return { inFrame: true, msg: '', icon: 'bi-person-check-fill' };
        }
        // <video> & <canvas> sama-sama object-fit:cover, jadi petakan koordinat
        // intrinsik video ke px CSS lewat skala cover-crop.
        const scale = Math.max(cw / vw, ch / vh);
        const offX = (cw - vw * scale) / 2, offY = (ch - vh * scale) / 2;
        const guideW = cw * GUIDE_W_RATIO;
        const guideH = guideW / GUIDE_ASPECT;
        const guideCx = cw / 2, guideCy = ch * GUIDE_TOP_RATIO;

        const fh = box.height * scale;
        const ratio = fh / guideH;
        if (ratio > FACE_MAX_RATIO) {
            return { inFrame: false, msg: 'Terlalu dekat — mundur sedikit', icon: 'bi-zoom-out' };
        }
        if (ratio < FACE_MIN_RATIO) {
            return { inFrame: false, msg: 'Terlalu jauh — mendekat ke kamera', icon: 'bi-zoom-in' };
        }
        const fx = box.x * scale + offX, fy = box.y * scale + offY;
        const dx = Math.abs(fx + (box.width * scale / 2) - guideCx);
        const dy = Math.abs(fy + (fh / 2) - guideCy);
        if (dx > guideW * 0.30 || dy > guideH * 0.26) {
            return { inFrame: false, msg: 'Geser wajah ke tengah bingkai', icon: 'bi-bullseye' };
        }
        return { inFrame: true, msg: '', icon: 'bi-person-check-fill' };
    }

    async function startFaceDetection(video) {
        if (!state.isModelLoaded) {
            $('#facedetect-status').html('<small class="text-amber-500">Memuat AI Wajah...</small>');
            try {
                await loadFaceModels();
            } catch (e) {}
        }

        const canvas = document.getElementById('canvasElement');
        let missStreak = 0;   // toleransi kedip antar frame
        let lumaTick = 0;
        let luma = -1;         // luminansi terakhir, -1 = belum diukur
        const lumaProbe = createLumaProbe();

        if (!video || !canvas) return;

        // Samakan ukuran intrinsik canvas dengan video agar object-fit:cover
        // memotong keduanya identik — kalau tidak, kotak hasil deteksi meleset.
        const displaySize = { width: video.videoWidth || video.clientWidth || 320, height: video.videoHeight || video.clientHeight || 420 };
        faceapi.matchDimensions(canvas, displaySize);
        canvas.style.display = 'block';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';

        // Cache ctx sekali di luar loop — canvas.getContext() di dalam loop
        // (dipanggil tiap tick ~180ms) memaksa browser lookup internal setiap
        // frame; meng-cache-nya menghilangkan overhead kecil tapi konsisten.
        const ctx = canvas.getContext('2d');

        // Hentikan loop lama jika ada (ganti sesi kamera)
        if (window.faceRafRunning) window.faceRafRunning = false;
        if (window.faceRafId) cancelAnimationFrame(window.faceRafId);

        // ── Dua opsi detektor ────────────────────────────────────────────
        // detectorFast  : inputSize 160 — ~44% lebih cepat dari 224, cukup
        //                 untuk panduan bingkai (box-only per tick).
        // detectorFull  : inputSize 224 — dipakai hanya saat refreshDescriptor
        //                 (landmark + embedding) sesaat sebelum capture.
        // Keduanya memakai model yang sama (TinyFaceDetector); tidak perlu
        // file weights tambahan — hanya parameter inference yang beda.
        const detectorFast = FACE_OPTS_FAST();
        const detectorFull = FACE_OPTS_FULL();

        let detecting = false;   // cegah tick overlap
        let descriptorBusy = false;
        let faceTick = 0;
        let lastTickTs = 0;

        // ── Cache elemen DOM — jQuery lookup satu kali, bukan tiap tick ──
        const $statusEl    = $('#facedetect-status');
        const $guideEl     = $('#faceGuide');
        const $progressEl  = $('#auto-progress');
        const $progressBar = $('#auto-progress-bar');
        const $progressLbl = $('#auto-progress-label');
        const $dinasCheck  = $('#dinasCheck');
        const $wfhCheck    = $('#wfhCheck');

        // ── Dirty-check: hanya tulis DOM kalau nilai benar-benar berubah ──
        // jQuery .html()/.text()/.css() memicu reflow/repaint walaupun nilai
        // sama — pada loop 180ms ini terjadi ~5× per detik tanpa perlu.
        let _lastStatusHtml  = null;
        let _lastGuideClass  = null;   // 'ok' | 'warn' | ''
        let _lastProgressPct = -1;
        let _lastProgressVis = null;   // true | false
        let _lastProgressLbl = null;

        function setStatus(html) {
            if (html === _lastStatusHtml) return;
            _lastStatusHtml = html;
            $statusEl.html(html);
            // Update visual class berdasarkan konten status
            const el = $statusEl[0];
            if (!el) return;
            if (html.includes('text-emerald-500') || html.includes('bi-person-check')) {
                el.classList.remove('status-warn','status-err');
                el.classList.add('status-ok');
            } else if (html.includes('text-amber-500') || html.includes('bi-lightbulb') || html.includes('bi-geo') || html.includes('bi-zoom') || html.includes('bi-bullseye')) {
                el.classList.remove('status-ok','status-err');
                el.classList.add('status-warn');
            } else {
                el.classList.remove('status-ok','status-warn','status-err');
            }
        }

        function setGuide(cls) {  // cls: 'ok' | 'warn' | 'counting' | ''
            if (cls === _lastGuideClass) return;
            _lastGuideClass = cls;
            if (cls === 'ok') {
                $guideEl.removeClass('is-warn is-counting').addClass('is-ok');
            } else if (cls === 'warn') {
                $guideEl.removeClass('is-ok is-counting').addClass('is-warn');
            } else if (cls === 'counting') {
                $guideEl.removeClass('is-warn').addClass('is-ok is-counting');
            } else {
                $guideEl.removeClass('is-ok is-warn is-counting');
            }
        }

        function setProgress(visible, pct, label) {
            if (visible !== _lastProgressVis) {
                _lastProgressVis = visible;
                if (visible) $progressEl.show(); else $progressEl.hide();
            }
            if (pct !== null && pct !== _lastProgressPct) {
                _lastProgressPct = pct;
                // style.width langsung ke DOM node — 3-5× lebih cepat dari jQuery .css()
                if ($progressBar[0]) $progressBar[0].style.width = pct + '%';
            }
            if (label !== null && label !== _lastProgressLbl) {
                _lastProgressLbl = label;
                $progressLbl.text(label);
            }
        }

        function resetProgress() {
            setProgress(false, 0, null);
            _lastProgressPct = 0;
            if ($guideEl[0]) $guideEl[0].style.setProperty('--fg-pct', '0%');
        }

        // Pass mahal (landmark 68 titik + descriptor 128-D) hanya sesekali
        // dan selalu fresh sesaat sebelum capture. Menggunakan detectorFull
        // agar hasil embedding lebih presisi dari tick rutin.
        // Selama refreshDescriptor berjalan, loop tick DIJEDA (detecting=true
        // di caller) — sehingga tidak ada dua inference berjalan bersamaan.
        async function refreshDescriptor() {
            if (descriptorBusy) return false;
            descriptorBusy = true;
            try {
                const full = await faceapi
                    .detectSingleFace(video, detectorFull)
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                if (full && full.descriptor) {
                    state.lastFaceDescriptor = Array.from(full.descriptor);
                    return true;
                }
            } catch (e) { /* pakai descriptor terakhir / fallback foto */ }
            finally {
                descriptorBusy = false;
            }
            return false;
        }

        // ── Loop deteksi berbasis requestAnimationFrame + throttle ───────
        // rAF sinkron dengan vsync browser (60fps) sehingga tidak ada frame
        // yang "terlewat" oleh scheduling timer seperti setInterval.
        // Throttle TICK_MS memastikan inference tidak dipanggil lebih cepat
        // dari kemampuan GPU/CPU perangkat — tick yang masih berjalan (flag
        // detecting) dilewati, bukan di-queue, sehingga tidak ada tumpukan.
        window.faceRafRunning = true;

        async function detectionLoop(ts) {
            if (!window.faceRafRunning || !state.stream) return; // kamera ditutup

            // Throttle: lewati frame bila belum cukup waktu sejak tick terakhir
            if (ts - lastTickTs >= TICK_MS && !detecting) {
                lastTickTs = ts;
                detecting = true;

                try {
                    const currentWidth = video.videoWidth || video.clientWidth;
                    const currentHeight = video.videoHeight || video.clientHeight;

                    if (currentWidth <= 0 || currentHeight <= 0) {
                        detecting = false;
                        window.faceRafId = requestAnimationFrame(detectionLoop);
                        return;
                    }

                    if (Math.abs(displaySize.width - currentWidth) > 10 || Math.abs(displaySize.height - currentHeight) > 10) {
                        displaySize.width = currentWidth;
                        displaySize.height = currentHeight;
                        faceapi.matchDimensions(canvas, displaySize);
                    }

                    // Ukur pencahayaan sesekali (setiap LUMA_SAMPLE_EVERY tick).
                    if (++lumaTick % LUMA_SAMPLE_EVERY === 0) {
                        luma = lumaProbe.read(video);
                    }
                    const tooDark = luma >= 0 && luma < DARK_LUMA;

                    // Box-only dengan detectorFast: jauh lebih ringan dari pipeline
                    // full, cukup untuk panduan bingkai & hitungan frame stabil.
                    const detection = await faceapi.detectSingleFace(video, detectorFast);

                    if (ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detection && detection.box) {
                        state.faceDetected = true;
                        missStreak = 0;

                        if (ctx) {
                            const resizedDetection = faceapi.resizeResults(detection, displaySize);
                            faceapi.draw.drawDetections(canvas, resizedDetection);
                        }

                        // Hanya wajah yang pas bingkai yang dihitung & diberi is-ok
                        const guide = evaluateFraming(detection.box, video);
                        // 'counting' akan di-set di bawah saat stableFrames naik;
                        // jangan override ke 'ok' dulu agar tidak ada DOM update ganda.
                        if (guide.inFrame && state.stableFrames === 0) {
                            setGuide('ok');
                        } else if (!guide.inFrame) {
                            setGuide('warn');
                        }

                        if (state.latVal === null) {
                            setStatus('<small class="text-amber-500 font-bold"><i class="bi bi-geo-alt-fill me-1"></i> Wajah Terdeteksi (Menunggu GPS...)</small>');
                        } else if (guide.inFrame) {
                            setStatus('<small class="text-emerald-500 font-bold"><i class="bi bi-person-check-fill me-1"></i> Wajah Terdeteksi</small>');
                        } else if (tooDark) {
                            state.stableFrames = 0;
                            resetProgress();
                            setStatus('<small class="text-amber-500 font-bold"><i class="bi bi-lightbulb me-1"></i> Terlalu gelap — pindah ke tempat lebih terang</small>');
                        } else {
                            // Wajah ada tapi belum pas bingkai: jeda progres & reset streak.
                            state.stableFrames = 0;
                            resetProgress();
                            setStatus('<small class="text-amber-500 font-bold"><i class="bi ' + guide.icon + ' me-1"></i> ' + guide.msg + '</small>');
                        }

                        // Auto-capture ketika wajah stabil (tunggu GPS)
                        if (guide.inFrame && state.autoCaptureEnabled && !state.isAutoCapturing && state.latVal !== null) {
                            // Baca checkbox sekali per tick via cached element
                            const dinasChecked = $dinasCheck.length > 0 && $dinasCheck[0].checked;
                            const wfhChecked   = $wfhCheck.length > 0  && $wfhCheck[0].checked;

                            if (state.inRange === false && !dinasChecked && !wfhChecked) {
                                state.stableFrames = 0;
                                resetProgress();
                                setStatus('<small class="text-amber-500 font-bold"><i class="bi bi-geo-alt-fill me-1"></i> Di luar area. Pilih Mode Dinas atau WFH.</small>');
                                detecting = false;
                                window.faceRafId = requestAnimationFrame(detectionLoop);
                                return;
                            }

                            // Segarkan descriptor sesekali (tiap DESCRIPTOR_EVERY tick).
                            // fire-and-forget — flag descriptorBusy mencegah tumpuk.
                            if (++faceTick % DESCRIPTOR_EVERY === 0 && !descriptorBusy) {
                                refreshDescriptor();
                            }

                            state.stableFrames++;
                            const pct = Math.min(Math.round((state.stableFrames / STABLE_FRAMES) * 100), 100);
                            const lbl = pct < 40 ? 'Deteksi...' : pct < 90 ? 'Tahan, jangan bergerak...' : 'Mengambil foto...';
                            setProgress(true, pct, lbl);
                            // Countdown ring: update CSS custom property di face guide
                            if ($guideEl[0]) $guideEl[0].style.setProperty('--fg-pct', pct + '%');
                            setGuide('counting');

                            if (state.stableFrames >= STABLE_FRAMES) {
                                state.isAutoCapturing = true;
                                setProgress(true, 100, 'Menganalisis wajah...');
                                // Hentikan loop — satu-satunya inference di momen ini
                                window.faceRafRunning = false;
                                await refreshDescriptor();
                                triggerManualCapture();
                                detecting = false;
                                return;
                            }
                        }
                    } else {
                        state.faceDetected = false;
                        missStreak++;
                        setGuide('');
                        // Kedip singkat tidak mereset progres.
                        if (missStreak > BLINK_ALLOWANCE) {
                            state.stableFrames = 0;
                            resetProgress();
                            setGuide('');
                            setStatus(tooDark
                                ? '<small class="text-amber-500 font-bold"><i class="bi bi-lightbulb me-1"></i> Terlalu gelap — pindah ke tempat lebih terang</small>'
                                : '<small class="text-slate-200 fs-blink"><i class="bi bi-person me-1"></i> Posisikan wajah di dalam lingkaran...</small>');
                        }
                    }
                } catch (err) {
                    if (err.message && !err.message.includes('dimensions')) {
                        console.warn("Face detection tick error:", err);
                    }
                } finally {
                    detecting = false;
                }
            }

            // Jadwalkan frame berikutnya
            window.faceRafId = requestAnimationFrame(detectionLoop);
        }

        // Tunda sedikit agar video sudah streaming sebelum loop mulai.
        // 100ms cukup — lebih pendek dari setTimeout 400ms sebelumnya.
        setTimeout(() => {
            if (window.faceRafRunning && state.stream) {
                window.faceRafId = requestAnimationFrame(detectionLoop);
            }
        }, 100);
    }

    /**
     * Restart loop deteksi wajah setelah user membatalkan dari modal.
     * Dipanggil di semua path batal: konfirmasi absen, luar area, alasan dinas.
     * Hanya berjalan kalau kamera masih aktif dan loop belum running.
     */
    function restartDetectionLoop() {
        if (state.stream && !window.faceRafRunning) {
            const vEl = document.getElementById('videoElement');
            if (vEl) startFaceDetection(vEl);
        }
    }

    function closeCamera() {        if (state.stream) {
            state.stream.getTracks().forEach(track => track.stop());
            state.stream = null;
        }

        // Hentikan loop deteksi rAF
        window.faceRafRunning = false;
        if (window.faceRafId) {
            cancelAnimationFrame(window.faceRafId);
            window.faceRafId = null;
        }
        // Kompatibilitas mundur: hentikan interval lama jika masih ada
        if (window.faceInterval) {
            clearInterval(window.faceInterval);
            window.faceInterval = null;
        }

        const canvas = document.getElementById('canvasElement');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            canvas.style.display = 'none';
        }

        state.isAutoCapturing = false;
        state.stableFrames = 0;
        $('#faceGuide').removeClass('is-ok is-warn');
        $('#auto-progress').hide();
        $('#auto-progress-bar').css('width', '0%');
        $('#fsCamOff').addClass('is-hidden');

        $('#cameraModal').removeClass('show').addClass('hidden');
    }

    let dinasReason = '';

    // Guru diblokir absen pulang karena sesi terjadwal belum dijurnal.
    // Tampilkan daftar sesinya dengan tautan langsung ke form jurnal yang
    // sudah terisi slot jadwalnya (Guru\Jurnal::form? id_jadwal).
    function showJurnalGateModal(sesiList, menungguList) {
        // "belum diisi" offer tautan Isi; sesi yang jurnalnya sudah ada tapi
        // belum disetujui ditawarkan tautan Lihat/Perbaiki supaya guru tidak
        // disuruh mengisi ulang jurnal yang sudah terisi.
        menungguList = Array.isArray(menungguList) ? menungguList : [];
        const meta = function (s) {
            const jamKe = escapeHtml(String(s.jam_ke || '').trim());
            const dari = String(s.dari || '').trim();
            const sampai = String(s.sampai || '').trim();
            const rentang = escapeHtml(sampai ? (dari + '–' + sampai) : dari);
            return [jamKe ? 'Jam ' + jamKe : '', rentang].filter(Boolean).join(' · ');
        };
        const rows = (sesiList || []).map(function (s) {
            const idJadwal = parseInt(s.id_jadwal, 10) || 0;
            const kelas = escapeHtml(s.nama_kelas || 'Kelas');
            const mapel = escapeHtml(s.nama_mapel || 'Mapel');
            const params = [];
            if (idJadwal > 0) params.push('id_jadwal=' + idJadwal);
            params.push('kembali=absen');
            const href = apiUrl('guru/jurnal/create') + '?' + params.join('&');

            return `
                <a href="${href}" class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl border border-slate-200 bg-white mb-2 no-underline">
                    <span class="min-w-0">
                        <span class="block text-[13px] font-bold text-slate-800 truncate">${kelas} · ${mapel}</span>
                        ${meta(s) ? `<span class="block text-[11px] text-slate-500">${meta(s)}</span>` : ''}
                    </span>
                    <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold" style="color:#4f46e5;">
                        <i class="bi bi-pencil-square me-1"></i> Isi
                    </span>
                </a>`;
        }).join('');

        const rowsMenunggu = menungguList.map(function (s) {
            const idJurnal = parseInt(s.id_jurnal, 10) || 0;
            const kelas = escapeHtml(s.nama_kelas || 'Kelas');
            const mapel = escapeHtml(s.nama_mapel || 'Mapel');
            const href = idJurnal > 0 ? apiUrl('guru/jurnal/detail/' + idJurnal) : apiUrl('guru/jurnal');
            const ditolak = String(s.validasi_status || '').toUpperCase() === 'REJECTED';
            const warna = ditolak ? '#e11d48' : '#d97706';

            return `
                <a href="${href}" class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl border border-slate-200 bg-white mb-2 no-underline">
                    <span class="min-w-0">
                        <span class="block text-[13px] font-bold text-slate-800 truncate">${kelas} · ${mapel}</span>
                        <span class="block text-[11px] font-bold truncate" style="color:${warna};">${ditolak ? 'Ditolak — perlu diperbaiki' : 'Menunggu validasi piket'}${meta(s) ? ' · ' + meta(s) : ''}</span>
                    </span>
                    <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold" style="color:${warna};">
                        <i class="bi bi-eye me-1"></i> ${ditolak ? 'Perbaiki' : 'Lihat'}
                    </span>
                </a>`;
        }).join('');

        const kepala = menungguList.length > 0 && !rows
            ? ['Validasi Jurnal Belum Selesai', 'Jurnal hari ini sudah diisi tetapi belum disetujui guru piket.']
            : menungguList.length > 0
                ? ['Jurnal Mengajar Belum Lengkap', 'Isi jurnal yang belum ada, dan tunggu/setujui yang sudah diisi.']
                : ['Jurnal Mengajar Belum Lengkap', 'Isi jurnal sesi berikut, lalu ulangi absen pulang.'];

        Swal.fire({
            html: `
                <div class="text-left px-1">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="inline-flex items-center justify-center shrink-0"
                            style="width: 44px; height: 44px; background: #eef2ff; color: #4f46e5; border-radius: 14px;">
                            <i class="bi bi-journal-text" style="font-size: 22px;"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 mb-0" style="font-size: 16px;">${kepala[0]}</h5>
                            <p class="text-[11px] text-slate-500 mb-0">${kepala[1]}</p>
                        </div>
                    </div>
                    <div class="mt-3">${rows}${rowsMenunggu}</div>
                    <p class="text-[11px] text-slate-400 mb-0">
                        <i class="bi bi-info-circle me-1"></i>${menungguList.length > 0
                            ? 'Absen pulang baru terbuka setelah seluruh jurnal disetujui guru piket.'
                            : 'Setelah jurnal tersimpan, ulangi absen pulang.'}
                    </p>
                </div>`,
            showConfirmButton: true,
            confirmButtonText: 'Tutup',
            buttonsStyling: false,
            customClass: {
                popup: 'rounded-3xl border-0 shadow-lg px-2 py-3',
                confirmButton: 'btn-tw btn-light w-full py-2.5 rounded-full font-bold text-slate-700 mt-2',
                actions: 'flex flex-column w-100 px-3 mt-2'
            }
        });
    }

    function showOutsideLocationModal(fotoBase64 = null) {
        const canWfh = state.wfhQuota && state.wfhQuota.wfh_enabled && state.wfhQuota.wfh_remaining > 0 && !state.wfhQuota.already_checked_in;
        let distInfo = '';
        if (state.closestLocation) {
            distInfo = ` (${Math.round(state.closestLocation.dist)}m dari ${state.closestLocation.name})`;
        }

        Swal.fire({
            title: 'Di Luar Area Lokasi Absensi',
            html: `
                <div class="text-left">
                    <p class="mb-2 text-center text-slate-800">Posisi Anda terdeteksi <b>di luar area absensi</b>${distInfo}.</p>
                    <div class="py-2 px-3 rounded-xl mb-3 text-[12px] font-medium" style="background: #fffbeb; border: 1px solid #fef3c7; color: #92400e;">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        <b>Ketentuan:</b> Absen di luar lokasi hanya diperbolehkan jika menggunakan:
                        <ul class="mb-0 ps-3 mt-1">
                            <li><b>Mode Dinas (Tugas Luar)</b>: Memerlukan alasan tugas</li>
                            ${canWfh ? `<li><b>WFH (Work From Home)</b>: Sisa kuota ${state.wfhQuota.wfh_remaining}/${state.wfhQuota.wfh_max} hari</li>` : ''}
                        </ul>
                    </div>
                    <p class="text-slate-400 text-[13px] text-center mb-0">Silakan pilih mode absensi yang sesuai:</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: !!canWfh,
            confirmButtonColor: '#3b82f6',
            denyButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-briefcase me-1"></i> Mode Dinas',
            denyButtonText: '<i class="bi bi-house-door me-1"></i> Mode WFH',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mode Dinas selected
                $('#dinasCheck').prop('checked', true).trigger('change');
                $('#wfhCheck').prop('checked', false);
                state.dinasMode = 1;
                state.wfhMode = 0;

                if (dinasReason) {
                    if (fotoBase64) {
                        processAttendance(fotoBase64, 1);
                    } else if (state.stream) {
                        takeSnapshot();
                    } else {
                        processAttendance(null, 1);
                    }
                } else {
                    $('#cameraModal').removeClass('show').addClass('hidden');
                    Swal.fire({
                        title: 'Alasan Tugas Dinas',
                        input: 'textarea',
                        inputPlaceholder: 'Jelaskan alasan tugas luar / dinas...',
                        inputAttributes: { 'aria-label': 'Alasan dinas' },
                        showCancelButton: true,
                        confirmButtonText: 'Lanjutkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#3b82f6',
                        inputValidator: (value) => {
                            if (!value || value.trim().length < 5) {
                                return 'Masukkan alasan minimal 5 karakter';
                            }
                        }
                    }).then((reasonResult) => {
                        if (reasonResult.isConfirmed) {
                            dinasReason = reasonResult.value.trim();
                            if (fotoBase64) {
                                processAttendance(fotoBase64, 1);
                            } else if (state.stream) {
                                $('#cameraModal').removeClass('hidden').addClass('show');
                                takeSnapshot();
                            } else {
                                processAttendance(null, 1);
                            }
                        } else {
                            state.isAutoCapturing = false;
                            state.stableFrames = 0;
                            $('#dinasCheck').prop('checked', false);
                            state.dinasMode = 0;
                            if (state.stream) {
                                $('#cameraModal').removeClass('hidden').addClass('show');
                            }
                            // Loop mati sejak auto-capture — restart
                            restartDetectionLoop();
                        }
                    });
                }
            } else if (result.isDenied) {
                // Mode WFH selected
                $('#wfhCheck').prop('checked', true).trigger('change');
                $('#dinasCheck').prop('checked', false);
                state.wfhMode = 1;
                state.dinasMode = 0;
                if (fotoBase64) {
                    processAttendance(fotoBase64, 0);
                } else if (state.stream) {
                    takeSnapshot();
                } else {
                    processAttendance(null, 0);
                }
            } else {
                state.isAutoCapturing = false;
                state.stableFrames = 0;
                // Loop dimatikan sebelum sampai ke sini — restart agar kamera tetap aktif.
                restartDetectionLoop();
            }
        });
    }

    function triggerManualCapture() {
        if (!state.stream) {
            showAlert('warning', 'Kamera Belum Aktif', 'Pastikan kamera sudah aktif.');
            return;
        }

        const dinasCheck = $('#dinasCheck');
        const wfhCheck = $('#wfhCheck');
        const isDinas = dinasCheck.length > 0 ? dinasCheck.is(':checked') : false;
        const isWfh = wfhCheck.length > 0 ? wfhCheck.is(':checked') : false;

        // If outside designated area and neither Dinas nor WFH is selected, show modal
        if (state.inRange === false && !isDinas && !isWfh) {
            showOutsideLocationModal();
            return;
        }

        if (isWfh) {
            state.wfhMode = 1;
            setTimeout(() => takeSnapshot(), 100);
        } else if (isDinas) {
            if (dinasReason) {
                state.dinasMode = 1;
                setTimeout(() => takeSnapshot(), 100);
            } else {
                $('#auto-progress').hide();
                $('#cameraModal').removeClass('show').addClass('hidden');
                Swal.fire({
                    title: 'Alasan Tugas Dinas',
                    input: 'textarea',
                    inputPlaceholder: 'Jelaskan alasan tugas luar...',
                    inputAttributes: { 'aria-label': 'Alasan dinas' },
                    showCancelButton: true,
                    confirmButtonText: 'Lanjutkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3b82f6',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    inputValidator: (value) => {
                        if (!value || value.trim().length < 5) {
                            return 'Masukkan alasan minimal 5 karakter';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        dinasReason = result.value.trim();
                        state.dinasMode = 1;
                        state.isAutoCapturing = true;
                        $('#cameraModal').removeClass('hidden').addClass('show');
                        takeSnapshot();
                    } else {
                        state.isAutoCapturing = false;
                        state.stableFrames = 0;
                        $('#cameraModal').removeClass('hidden').addClass('show');
                        // Loop mati sejak auto-capture — restart setelah modal kembali
                        restartDetectionLoop();
                    }
                });
            }
        } else {
            setTimeout(() => takeSnapshot(), 100);
        }
    }

    function takeSnapshot() {
        // Flash effect + shutter beep
        playCaptureShutter();
        const flash = document.getElementById('cameraFlash');
        if (flash) {
            flash.style.opacity = '0.6';
            setTimeout(() => flash.style.opacity = '0', 200);
        }

        // Validation: Exact Location
        if (state.latVal === null) {
            console.warn('takeSnapshot: Location is null, triggering retry...');
            showToast('info', 'Sedang mendeteksi lokasi...');
            detectLocation(); // Retry detection

            // Wait briefly to see if we get it
            setTimeout(() => {
                if (state.latVal === null) {
                    showAlert('warning', 'Sedang Mendeteksi Lokasi', 'Mohon tunggu sebentar, sedang mencari koordinat GPS Anda...', 2000);
                } else {
                    takeSnapshot(); // Retry snap recursively (once)
                }
            }, 1000);
            return;
        }

        // Additional validation for accuracy if needed
        if (state.accuracyVal > 500) {
            showToast('warning', 'Akurasi GPS rendah (' + Math.round(state.accuracyVal) + 'm)');
        }

        // Enforce Face Detection if not Dinas or WFH
        const dinasCheck = $('#dinasCheck');
        const wfhCheck = $('#wfhCheck');
        const isDinas = dinasCheck.length > 0 ? dinasCheck.is(':checked') : false;
        const isWfh = wfhCheck.length > 0 ? wfhCheck.is(':checked') : false;

        // Skip face detection for WFH if wfh_wajib_wajah is 0 (handled server-side, but relax client too)
        if (config.fotoWajib === 1 && !isDinas && !isWfh && state.isModelLoaded && !state.faceDetected) {
            state.isAutoCapturing = false;
            state.stableFrames = 0;
            $('#auto-progress').hide();
            $('#auto-progress-bar').css('width', '0%');
            const Toast = Swal.mixin({
                toast: true,
                position: 'center',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({
                icon: 'warning',
                title: 'Wajah tidak terdeteksi!',
                text: 'Pastikan wajah Anda terlihat di dalam lingkaran.'
            });
            return;
        }

        doCapture();
    }

    function doCapture() {
        const video = document.getElementById('videoElement');
        if (!video || !video.videoWidth || !video.videoHeight) {
            showAlert('error', 'Kamera Tidak Siap', 'Video belum siap, coba lagi.');
            state.isAutoCapturing = false;
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);

        processAttendance(dataUrl);
        closeCamera();
    }

    function confirmNoPhoto() {
        Swal.fire({
            title: config.currentMode === 'checkin' ? 'Absen Masuk?' : 'Absen Pulang?',
            text: 'Lokasi Anda saat ini akan dicatat.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Ya, Absen',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Mendeteksi Lokasi...');
                getLocationPromise().then(pos => {
                    state.latVal = pos.lat;
                    state.lngVal = pos.lng;
                    state.accuracyVal = pos.accuracy;

                    // Evaluate location range
                    let closest = null;
                    if (config.locations && config.locations.length > 0) {
                        let minDist = Infinity;
                        config.locations.forEach(loc => {
                            const d = calculateDistance(pos.lat, pos.lng, parseFloat(loc.lat), parseFloat(loc.lng));
                            if (d < minDist) {
                                minDist = d;
                                closest = { name: loc.nama_lokasi, dist: d, inRange: d <= parseFloat(loc.radius_meter) };
                            }
                        });
                    }
                    state.inRange = closest ? closest.inRange : false;
                    state.closestLocation = closest;

                    if (state.inRange === false && !state.dinasMode && !state.wfhMode) {
                        showOutsideLocationModal(null);
                        return;
                    }

                    processAttendance(null, 0); // Default 0, logic auto-detects
                }).catch(err => {
                    showAlert('error', 'Error', 'Gagal mendapatkan lokasi: ' + err.message);
                });
            }
        });
    }

    // ── Gate wajib: surat teguran belum dikonfirmasi ──
    var TEGURAN_FRASA = 'ya, saya akan meningkatkan disiplin';

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Ambil surat teguran milik guru yang belum dikonfirmasi. Fail-open:
    // bila endpoint gagal, absen tetap lanjut agar tidak macet.
    function fetchPendingTeguran() {
        var baseUrl = (config.baseUrl || '').replace(/\/+$/, '');
        return $.get(baseUrl + '/presensi/surat-saya').then(function (res) {
            return (res && res.status && Array.isArray(res.data)) ? res.data : [];
        }).catch(function () { return []; });
    }

    // Modal wajib non-dismissable: hanya tertutup setelah frasa tepat + ack tersimpan.
    function showTeguranModal(letters) {
        var baseUrl = (config.baseUrl || '').replace(/\/+$/, '');
        var ids = letters.map(function (L) { return L.id; });
        var listHtml = letters.map(function (L) {
            return '<div style="display:flex;align-items:center;gap:10px;text-align:left;background:#fff;border:1px solid #fecdd3;border-left:4px solid #e11d48;border-radius:12px;padding:8px 10px;margin-bottom:8px;">' +
                '<div style="flex:1;min-width:0;">' +
                    '<div style="font-size:10.5px;font-weight:800;color:#b91c1c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(L.nomor) + '</div>' +
                    '<div style="font-size:12px;font-weight:800;color:#1e293b;">' + escHtml(L.level_text) + '</div>' +
                    '<div style="font-size:10.5px;color:#64748b;">' + escHtml(L.bulan_text) + ' ' + escHtml(L.tahun) + '</div>' +
                '</div>' +
                '<a href="' + escHtml(L.download_url) + '" target="_blank" title="Unduh PDF" style="flex-shrink:0;width:38px;height:38px;border-radius:12px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;">' +
                '<i class="bi bi-file-earmark-pdf"></i></a>' +
                '</div>';
        }).join('');

        return Swal.fire({
            html: '<div style="text-align:center;margin-bottom:10px;">' +
                    '<div style="display:inline-flex;width:52px;height:52px;border-radius:18px;background:#fee2e2;color:#dc2626;align-items:center;justify-content:center;font-size:24px;"><i class="bi bi-envelope-exclamation-fill"></i></div>' +
                    '<div style="font-size:16px;font-weight:800;color:#1e293b;margin-top:8px;">Surat Teguran</div>' +
                    '<p style="font-size:12px;color:#64748b;margin:4px 0 0;">Anda memiliki <b style="color:#b91c1c;">' + letters.length + ' surat</b> yang wajib dibaca sebelum absen.</p>' +
                '</div>' +
                '<div style="max-height:26vh;overflow-y:auto;margin:0 -2px;padding:0 2px;">' + listHtml + '</div>' +
                '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:12px;margin-top:6px;">' +
                    '<div style="font-size:11.5px;font-weight:700;color:#334155;">Ketik persis kalimat berikut:</div>' +
                    '<div style="background:#fff;border:1px dashed #fda4af;border-radius:10px;padding:8px 10px;margin:8px 0 10px;font-size:13px;font-weight:800;color:#e11d48;line-height:1.6;">ya, saya akan meningkatkan disiplin</div>' +
                    '<div style="position:relative;">' +
                        '<input id="teguran-frasa" class="swal2-input" style="margin:0;width:100%;text-align:center;font-weight:600;padding-right:38px;" placeholder="ketik di sini..." autocomplete="off">' +
                        '<span id="teguran-match" style="display:none;position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#16a34a;font-size:18px;"><i class="bi bi-check-circle-fill"></i></span>' +
                    '</div>' +
                '</div>',
            width: '26rem',
            padding: '1.1rem 1rem 1rem',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCloseButton: false,
            showCancelButton: false,
            heightAuto: false,
            confirmButtonText: 'Konfirmasi & Lanjutkan Absen',
            confirmButtonColor: '#e11d48',
            customClass: {
                popup: 'tte-popup',
                confirmButton: 'tte-confirm'
            },
            didOpen: function () {
                var btn = Swal.getConfirmButton();
                var input = document.getElementById('teguran-frasa');
                var match = document.getElementById('teguran-match');
                btn.disabled = true;
                btn.style.opacity = 0.5;
                btn.style.width = '100%';
                btn.style.borderRadius = '9999px';
                btn.style.fontWeight = '800';
                input.addEventListener('input', function () {
                    var ok = input.value.trim().toLowerCase() === TEGURAN_FRASA;
                    btn.disabled = !ok;
                    btn.style.opacity = ok ? 1 : 0.5;
                    input.style.borderColor = ok ? '#16a34a' : '';
                    input.style.boxShadow = ok ? '0 0 0 3px rgba(22,163,74,.15)' : '';
                    match.style.display = ok ? 'block' : 'none';
                });
                setTimeout(function () { input.focus(); }, 300);
            },
            preConfirm: function () {
                var val = document.getElementById('teguran-frasa').value || '';
                if (val.trim().toLowerCase() !== TEGURAN_FRASA) {
                    Swal.showValidationMessage('Teks belum tepat. Ketik persis: "ya, saya akan meningkatkan disiplin".');
                    return false;
                }
                var data = { ids: ids, konfirmasi: val };
                data[config.csrfName] = config.csrfToken;
                return $.post(baseUrl + '/presensi/surat-ack', data).then(function (res) {
                    if (res && res.status) return true;
                    Swal.showValidationMessage((res && res.message) || 'Gagal menyimpan konfirmasi.');
                    return false;
                }).catch(function (xhr) {
                    var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Gagal terhubung ke server.';
                    Swal.showValidationMessage(msg);
                    return false;
                });
            }
        }).then(function (result) {
            return !!(result && result.isConfirmed && result.value);
        });
    }

    async function handleScanClick() {
        if (config.currentMode === 'done') {
            showAlert('info', 'Selesai', 'Anda sudah menyelesaikan absensi hari ini.');
            return;
        }

        // Gate wajib: cek surat teguran belum dikonfirmasi sebelum absen
        try {
            var pendingTeguran = await fetchPendingTeguran();
            if (pendingTeguran && pendingTeguran.length) {
                var acked = await showTeguranModal(pendingTeguran);
                if (!acked) return;
            }
        } catch (e) {
            console.warn('Gate teguran dilewati karena error:', e);
        }

        // Holiday Check
        if (config.isHoliday && config.currentMode === 'checkin') {
            Swal.fire({
                title: 'Konfirmasi Hari Libur',
                html: '<p>' + config.holidayReason + '</p><small class="text-slate-400">Apakah Anda yakin ingin tetap melakukan absensi?</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) initiateAttendance();
            });
            return;
        }

        initiateAttendance();
    }

    function initiateAttendance() {
        if (config.fotoWajib === 0) {
            confirmNoPhoto();
        } else {
            openCamera();
        }
    }

    // Process Attendance
    async function processAttendance(fotoBase64, isDinasExplicit = null, isConfirm = 0) {
        // Validasi lokasi
        if (!state.latVal || !state.lngVal) {
            Swal.fire({
                icon: 'error',
                title: 'Lokasi Tidak Valid',
                text: 'Gagal mendapatkan lokasi GPS. Pastikan GPS aktif dan coba lagi.'
            });
            return;
        }

        // Mode confirmation for auto-capture (initial call from camera)
        if (isConfirm === 0 && isDinasExplicit === null) {
            const isMasuk = config.currentMode === 'checkin';
            const modeLabel = isMasuk ? 'Absen Masuk' : 'Absen Pulang';
            const modeIcon = isMasuk ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right';
            const now = witaNow();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            const dateStr = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

            // Lokasi adalah info yang benar-benar menentukan hasil absen → tampilkan.
            const loc = state.closestLocation || {};
            const locName = esc(loc.name || 'Lokasi GPS');
            const distTxt = (loc.dist != null && isFinite(loc.dist)) ? Math.round(loc.dist) + 'm' : null;
            const accTxt = (state.accuracyVal != null && isFinite(state.accuracyVal)) ? '±' + Math.round(state.accuracyVal) + 'm' : null;
            const locSub = [distTxt, accTxt].filter(Boolean).join(' • ') || 'GPS aktif';
            const rangePill = state.inRange === true
                ? '<span style="flex-shrink:0;font-size:10px;font-weight:800;padding:4px 10px;border-radius:999px;background:#dcfce7;color:#15803d;">✔</span>'
                : (state.inRange === false
                    ? '<span style="flex-shrink:0;font-size:10px;font-weight:800;padding:4px 10px;border-radius:999px;background:#ffe4e6;color:#e11d48;">Luar radius</span>'
                    : '<span style="flex-shrink:0;font-size:10px;font-weight:800;padding:4px 10px;border-radius:999px;background:#f1f5f9;color:#64748b;">GPS</span>');

            // Mode khusus layak dikonfirmasi eksplisit (sebelumnya hanya teks generik).
            let modeBadge = '';
            if (state.dinasMode) {
                const reason = esc((dinasReason || '').slice(0, 60));
                modeBadge = '<div style="display:flex;align-items:center;gap:8px;text-align:left;background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:9px 12px;margin-top:8px;font-size:12px;font-weight:700;color:#92400e;">' +
                    '<i class="bi bi-briefcase-fill" style="flex-shrink:0;font-size:15px;"></i>' +
                    '<span style="min-width:0;">Mode Dinas' + (reason ? ' • ' + reason : '') + '</span></div>';
            } else if (state.wfhMode) {
                modeBadge = '<div style="display:flex;align-items:center;gap:8px;text-align:left;background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:9px 12px;margin-top:8px;font-size:12px;font-weight:700;color:#0369a1;">' +
                    '<i class="bi bi-house-door-fill" style="flex-shrink:0;font-size:15px;"></i><span>Mode WFH</span></div>';
            }

            const result = await Swal.fire({
                title: `Konfirmasi ${modeLabel}`,
                html: `
                    <style>
                        .absen-confirm-actions { display:flex !important; flex-direction:row !important; gap:10px !important; width:100% !important; margin-top:18px !important; }
                        .absen-confirm-actions .swal2-styled { flex:1 1 0 !important; min-width:0 !important; margin:0 !important; padding:.8em 0 !important; font-size:.9rem !important; }
                        .absen-confirm-actions .swal2-confirm:focus { box-shadow:0 0 0 3px rgba(225,29,72,.22) !important; }
                    </style>
                    <div style="text-align:center">
                        <div style="display:inline-flex;width:60px;height:60px;border-radius:20px;background:#ffe4e6;color:#e11d48;align-items:center;justify-content:center;font-size:28px;margin:2px 0 10px;">
                            <i class="bi ${modeIcon}"></i>
                        </div>
                        <div style="font-size:12.5px;color:#64748b;margin-bottom:12px;">${dateStr} &bull; <b style="color:#0f172a;">${timeStr}</b></div>
                        <div style="display:flex;align-items:center;gap:10px;text-align:left;background:#f8fafc;border:1px solid #eef2f7;border-radius:14px;padding:10px 12px;">
                            <div style="flex-shrink:0;width:36px;height:36px;border-radius:12px;background:#fff;border:1px solid #eef2f7;color:#e11d48;display:flex;align-items:center;justify-content:center;font-size:17px;"><i class="bi bi-geo-alt-fill"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:800;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${locName}</div>
                                <div style="font-size:11.5px;color:#64748b;">${locSub}</div>
                            </div>
                            ${rangePill}
                        </div>
                        ${modeBadge}
                    </div>
                `,
                icon: null,
                showCancelButton: true,
                confirmButtonText: `Ya, ${modeLabel}`,
                cancelButtonText: 'Batal',
                reverseButtons: true,
                width: 350,
                padding: '26px 20px 20px',
                customClass: {
                    actions: 'absen-confirm-actions',
                    confirmButton: 'absen-confirm-ok',
                    cancelButton: 'absen-confirm-no'
                }
            });

            if (!result.isConfirmed) {
                state.isAutoCapturing = false;
                // Loop dimatikan sebelum triggerManualCapture dipanggil.
                // User membatalkan — restart loop agar kamera tetap bisa dipakai.
                restartDetectionLoop();
                return;
            }

            // Prime audio context during user gesture
            ensureAudioCtx();

            // Proceed with confirmed intent
            return processAttendance(fotoBase64, isDinasExplicit, 1);
        }

        showLoading('Mengirim...');

        const url = apiUrl(config.currentMode === 'checkin'
            ? config.endpoints.checkin
            : config.endpoints.checkout);

        // Determine isDinas: Explicit arg > state.dinasMode (captured during camera) > Default 0
        let isDinas = 0;
        if (isDinasExplicit !== null) {
            isDinas = isDinasExplicit;
        } else {
            isDinas = state.dinasMode;
        }

        const data = {
            lat: state.latVal,
            lng: state.lngVal,
            foto: fotoBase64,
            is_dinas: isDinas,
            is_wfh: state.wfhMode,
            accuracy: state.accuracyVal,
            confirm: isConfirm,
            keterangan: (isDinas === 1 && dinasReason) ? dinasReason : ''
        };
        data[config.csrfName] = config.csrfToken;

        // Face Recognition: extract descriptor from cached frame or captured photo
        if (fotoBase64 && state.isModelLoaded) {
            try {
                if (state.lastFaceDescriptor && state.lastFaceDescriptor.length === 128) {
                    data.face_descriptor = JSON.stringify(state.lastFaceDescriptor);
                } else {
                    const img = new Image();
                    img.src = fotoBase64;
                    await new Promise(resolve => { img.onload = resolve; });
                    const fullDesc = await faceapi
                        .detectSingleFace(img, FACE_OPTS_FULL())
                        .withFaceLandmarks()
                        .withFaceDescriptor();
                    if (fullDesc && fullDesc.descriptor) {
                        data.face_descriptor = JSON.stringify(Array.from(fullDesc.descriptor));
                    }
                }
            } catch (err) {
                console.warn("Face descriptor extraction fallback error:", err);
            }
        }

        console.log('Sending data to:', url, data);

        // --- OFFLINE CHECK ---
        if (!navigator.onLine) {
            // Validate Radius Client-Side BEFORE saving
            if (config.locations && config.locations.length > 0) {
                const isWithinRadius = checkRadiusOffline(state.latVal, state.lngVal, config.locations);
                if (!isWithinRadius) {
                    // Check if DINAS or WFH mode (if dinas or wfh, ignore radius)
                    if (isDinas !== 1 && state.wfhMode !== 1) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal (Offline)',
                            text: 'Posisi Anda di luar jangkauan lokasi kantor. Absen di luar lokasi hanya diperbolehkan jika menggunakan Mode Dinas atau WFH.',
                            footer: 'Pastikan GPS akurat atau gunakan Mode Dinas / WFH jika diizinkan.'
                        });
                        return;
                    }
                }
            }

            saveToQueue(url, data);
            return;
        }
        // ---------------------

        const onSuccess = function (res) {

            if (res.status) {
                const isCheckin = (config.currentMode === 'checkin');
                const isLate = Boolean(isCheckin && res.data && (res.data.status_masuk === 'LATE' || res.data.is_late || (res.data.telat_menit && res.data.telat_menit > 0)));
                const telatMenit = (res.data && res.data.telat_menit) ? parseInt(res.data.telat_menit) : 0;
                const isEarly = Boolean(!isCheckin && res.data && (res.data.status_pulang === 'EARLY' || (res.data.pulang_cepat_menit && res.data.pulang_cepat_menit > 0)));
                const pulangCepatMenit = (res.data && res.data.pulang_cepat_menit) ? parseInt(res.data.pulang_cepat_menit) : 0;

                // 1. Mainkan nada bel / audio chime
                playSuccessSound(isLate || isEarly);

                // 2. Siapkan teks suara TTS
                let voiceText = (res.data && res.data.voice_text) ? res.data.voice_text : '';
                if (!voiceText) {
                    const sapaan = (res.data && res.data.sapaan) ? res.data.sapaan : 'Bapak atau Ibu';
                    const rawNama = (res.data && (res.data.nama_depan || res.data.nama_bersih)) ? (res.data.nama_depan || res.data.nama_bersih) : '';
                    let namaDepan = '';
                    if (rawNama) {
                        const words = rawNama.trim().split(/\s+/).filter(w => w.replace(/\./g, '').length > 1);
                        namaDepan = words[0] || (rawNama.trim().split(/\s+/)[0] || '');
                    }
                    const panggilanLengkap = namaDepan ? `${sapaan} ${namaDepan}` : sapaan;

                    if (isCheckin) {
                        if (isLate && telatMenit > 0) {
                            voiceText = `Perhatian ${panggilanLengkap}. Presensi masuk berhasil, Anda terlambat ${telatMenit} menit. Mohon tingkatkan kedisiplinan.`;
                        } else {
                            voiceText = `Terima kasih ${panggilanLengkap}. Presensi masuk berhasil, hadir tepat waktu. Selamat bertugas!`;
                        }
                    } else {
                        if (isEarly && pulangCepatMenit > 0) {
                            voiceText = `Perhatian ${panggilanLengkap}. Presensi pulang berhasil, Anda pulang ${pulangCepatMenit} menit lebih awal. Hati-hati di jalan dan selamat beristirahat.`;
                        } else {
                            voiceText = `Terima kasih ${panggilanLengkap}. Presensi pulang berhasil. Hati-hati di jalan dan selamat beristirahat.`;
                        }
                    }
                }

                // 3. Eksekusi Umpan Balik: Suara TTS & Modal Sinkron
                let reloadTriggered = false;
                const finishAndReload = () => {
                    if (reloadTriggered) return;
                    reloadTriggered = true;
                    setTimeout(function () {
                        location.reload();
                    }, 400);
                };

                // Ucapkan suara TTS sampai tuntas sebelum reload
                speakAttendanceVoice(voiceText, function () {
                    if (isDinas !== 1) {
                        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                            Swal.close();
                        }
                        finishAndReload();
                    }
                });

                // 4. Modal notifikasi hasil presensi
                // Timer modal diset 10 detik sebagai batas aman jika suara terblokir browser,
                // namun akan otomatis tertutup seketika saat suara TTS selesai diucapkan.
                let swalTimer = (isDinas === 1) ? undefined : 10000;
                let swalShowConfirm = (isDinas === 1);

                let swalTitle = 'Presensi Berhasil';
                let swalHtml = `<div class="text-sm text-slate-600 mt-1">${res.message}</div>`;
                if (isCheckin && isLate && telatMenit > 0) {
                    swalTitle = 'Presensi Masuk (Terlambat)';
                    swalHtml = `
                        <div class="mt-2 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                <i class="bi bi-clock-history"></i> Terlambat ${telatMenit} Menit
                            </span>
                            <div class="text-xs text-slate-500 mt-2">${res.message}</div>
                        </div>
                    `;
                } else if (isCheckin && !isLate) {
                    swalTitle = 'Presensi Masuk (Tepat Waktu)';
                    swalHtml = `
                        <div class="mt-2 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <i class="bi bi-check-circle-fill"></i> Tepat Waktu
                            </span>
                            <div class="text-xs text-slate-500 mt-2">${res.message}</div>
                        </div>
                    `;
                } else if (!isCheckin && isEarly && pulangCepatMenit > 0) {
                    swalTitle = 'Presensi Pulang (Pulang Lebih Awal)';
                    swalHtml = `
                        <div class="mt-2 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                <i class="bi bi-clock-history"></i> Pulang Lebih Awal ${pulangCepatMenit} Menit
                            </span>
                            <div class="text-xs text-slate-500 mt-2">${res.message}</div>
                        </div>
                    `;
                } else if (!isCheckin && !isEarly) {
                    swalTitle = 'Presensi Pulang (Selesai Bertugas)';
                    swalHtml = `
                        <div class="mt-2 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <i class="bi bi-check-circle-fill"></i> Jam Pulang Selesai
                            </span>
                            <div class="text-xs text-slate-500 mt-2">${res.message}</div>
                        </div>
                    `;
                }

                Swal.fire({
                    icon: ((isLate || isEarly) ? 'warning' : 'success'),
                    title: swalTitle,
                    html: swalHtml,
                    timer: swalTimer,
                    showConfirmButton: swalShowConfirm,
                    confirmButtonText: 'Tutup',
                    timerProgressBar: Boolean(swalTimer)
                }).then(() => {
                    finishAndReload();
                });
            } else {
                console.error('Attendance failed:', res.message);
                // Check if error is related to Geofence/Location
                if (res.message && (res.message.toLowerCase().includes('luar area') || res.message.toLowerCase().includes('dinas') || res.message.toLowerCase().includes('wfh'))) {
                    showOutsideLocationModal(fotoBase64);
                } else if (res.message && res.message.toLowerCase().includes('belum waktunya')) {
                    // Handle Early Leave
                    Swal.fire({
                        title: 'Pulang Cepat?',
                        text: "Anda melakukan absen checkout sebelum waktunya. Apakah ini Izin/Pulang Cepat?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Ya, Izin Pulang',
                        cancelButtonText: 'Batal'
                    }).then((retryResult) => {
                        if (retryResult.isConfirmed) {
                            processAttendance(fotoBase64, 2); // 2 = CheckOut Early Signal
                        }
                    });
                } else if (res.blocked) {
                    Swal.fire({
                        title: 'Akses Diblokir',
                        text: res.message,
                        icon: 'error',
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Tutup'
                    });
                } else if (res.require_confirm) {
                    Swal.fire({
                        title: 'Peringatan Absensi',
                        text: res.message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#f59e0b',
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((retryResult) => {
                        if (retryResult.isConfirmed) {
                            processAttendance(fotoBase64, isDinas, 1);
                        }
                    });
                } else if (res.data && ((Array.isArray(res.data.sesi_belum_dijurnal) && res.data.sesi_belum_dijurnal.length > 0)
                        || (Array.isArray(res.data.sesi_menunggu_validasi) && res.data.sesi_menunggu_validasi.length > 0))) {
                    showJurnalGateModal(res.data.sesi_belum_dijurnal || [], res.data.sesi_menunggu_validasi || []);
                } else {
                    showAlert('error', 'Gagal', res.message);
                }
            }
        };

        const onFail = function (xhr, status, error) {
            console.error('AJAX Error:', { xhr, status, error });
            // Gate surat teguran server (HTTP 403). Gate sisi klien di atas
            // bersifat fail-open, jadi cadangannya ada di sini: konfirmasi
            // dulu, lalu kirim ulang presensi yang sama.
            var body = xhr && xhr.responseJSON;
            if (body && body.data && body.data.require_teguran_ack) {
                showTeguranModal(body.data.letters || []).then(function (acked) {
                    if (acked) {
                        processAttendance(fotoBase64, isDinas, isConfirm);
                    }
                });
                return;
            }
            // Token CSRF basi (halaman lama terbuka / disajikan cache PWA).
            // Segarkan sekali lalu ulangi persis satu kali.
            if (xhr.status === 403 && !data.__csrfRetried) {
                var refresh = (typeof window.presensiRefreshCsrf === 'function')
                    ? window.presensiRefreshCsrf(true)
                    : Promise.resolve(false);
                refresh.then(function (ok) {
                    if (!ok) {
                        showAlert('error', 'Sesi Berakhir', 'Sesi Anda berakhir. Muat ulang halaman lalu login kembali.');
                        return;
                    }
                    data[config.csrfName] = config.csrfToken;
                    data.__csrfRetried = 1;
                    $.post(url, data, onSuccess, 'json').fail(onFail);
                });
                return;
            }
            // Check for network error (status 0) or timeout to save to queue
            if (xhr.status === 0 || xhr.readyState === 0) {
                // Should we validate radius here too? 
                // Yes, duplicate validation safety or assume initial GPS check passed?
                // Let's validate to be safe.
                if (isDinas !== 1 && state.wfhMode !== 1 && config.locations && config.locations.length > 0) {
                    if (!checkRadiusOffline(state.latVal, state.lngVal, config.locations)) {
                        showAlert('error', 'Gagal', 'Terjadi kesalahan jaringan & Lokasi Anda terdeteksi di luar jangkauan (Validasi Offline). Absen di luar lokasi hanya diperbolehkan jika Dinas atau WFH.');
                        return;
                    }
                }

                saveToQueue(url, data);
            } else {
                showAlert('error', 'Error', 'Terjadi kesalahan jaringan: ' + xhr.status + ' ' + xhr.statusText);
            }
        };

        $.post(url, data, onSuccess, 'json').fail(onFail);
    }

    // Haversine Formula for Offline Validation
    function checkRadiusOffline(lat1, lon1, locations) {
        // If no locations defined, assume everywhere is valid (or strict? let's be loose for safety)
        if (!locations || locations.length === 0) return true;

        const R = 6371e3; // metres
        const rad = Math.PI / 180;

        for (let loc of locations) {
            const lat2 = parseFloat(loc.lat);
            const lon2 = parseFloat(loc.lng);
            const allowedRadius = parseFloat(loc.radius_meter);

            const φ1 = lat1 * rad;
            const φ2 = lat2 * rad;
            const Δφ = (lat2 - lat1) * rad;
            const Δλ = (lon2 - lon1) * rad;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            const d = R * c; // in metres

            if (d <= allowedRadius) {
                return true; // Found at least one valid location
            }
        }

        return false; // No location matched
    }

    // OFFLINE QUEUE UTILS
    // Jam server (WITA), bukan jam perangkat: server membandingkan timestamp
    // ini dengan jamnya sendiri, jadi perangkat WIB mengirim waktu 1 jam mundur.
    function getFormattedDate() {
        const now = witaNow();
        const pad = n => n < 10 ? '0' + n : n;
        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + ' ' +
            pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }

    function saveToQueue(url, data) {
        // Inject timestamp so server knows when this happened
        data.timestamp = getFormattedDate();

        const queueItem = {
            url: url,
            data: data,
            created: Date.now(),
            userId: config.userId // Security Binding
        };

        let queue = JSON.parse(localStorage.getItem('attendance_queue') || '[]');
        queue.push(queueItem);
        localStorage.setItem('attendance_queue', JSON.stringify(queue));

        playSuccessSound(); // Give feedback as if success
        Swal.fire({
            icon: 'info',
            title: 'Tersimpan Offline',
            text: 'Koneksi bermasalah. Data tersimpan di HP dan akan dikirim otomatis saat online.',
            confirmButtonText: 'Ok, Mengerti'
        }).then(() => {
            closeCamera();
            checkOfflineQueue();
        });
    }

    // Check & Show Offline Alert
    function checkOfflineQueue() {
        let queue = JSON.parse(localStorage.getItem('attendance_queue') || '[]');

        // Filter specific for this user if needed, but showing all pending is also fine for awareness
        // Let's filter by user to be precise
        let myQueue = queue.filter(item => item.userId && String(item.userId) === String(config.userId));

        const alertBox = $('#offline-alert');
        const countSpan = $('#offline-count');

        if (myQueue.length > 0) {
            countSpan.text(myQueue.length);
            alertBox.removeClass('hidden');
        } else {
            alertBox.addClass('hidden');
        }
    }

    function processQueue() {
        if (!navigator.onLine) return;

        let queue = JSON.parse(localStorage.getItem('attendance_queue') || '[]');
        if (queue.length === 0) return;

        // Separate items for current user and others
        let currentUserItems = [];
        let otherUserItems = [];

        queue.forEach(item => {
            // Assume strict matching
            if (item.userId && String(item.userId) === String(config.userId)) {
                currentUserItems.push(item);
            } else {
                otherUserItems.push(item);
            }
        });

        if (currentUserItems.length === 0) {
            // Nothing to sync for THIS user
            if (otherUserItems.length > 0) {
                console.log('Pending offline data exists for other users (' + otherUserItems.length + ').');
            }
            return;
        }

        console.log(`Processing ${currentUserItems.length} items for User ${config.userId}`);

        // Process ONLY the first item of THIS user
        const item = currentUserItems[0];

        $.post(item.url, item.data, function (res) {
            if (res.status) {
                console.log('Queue item synced:', res.message);

                // Re-read fresh queue from storage in case it changed during async
                let freshQueue = JSON.parse(localStorage.getItem('attendance_queue') || '[]');

                // Remove ONLY the specific item we just synced (by created timestamp)
                let updatedQueue = freshQueue.filter(q => q.created !== item.created);
                localStorage.setItem('attendance_queue', JSON.stringify(updatedQueue));

                checkOfflineQueue(); // Update UI

                // Recurse to process next item for this user
                setTimeout(processQueue, 1000);
            } else {
                console.warn('Server rejected offline item:', res.message);
                // If server rejects (e.g. data invalid), we MUST remove it to avoid infinite loop
                let freshQueue = JSON.parse(localStorage.getItem('attendance_queue') || '[]');
                let updatedQueue = freshQueue.filter(q => q.created !== item.created);
                localStorage.setItem('attendance_queue', JSON.stringify(updatedQueue));

                showToast('error', 'Gagal Sync: ' + res.message);
                setTimeout(processQueue, 1000);
            }
        }, 'json').fail(function () {
            // Network fail - Do nothing. Item remains in storage.
            console.log('Sync failed (Network), retrying later...');
        });
    }

    // Listen for online status
    window.addEventListener('online', processQueue);

    // Also check on init
    $(document).ready(function () {
        if (navigator.onLine) {
            setTimeout(processQueue, 3000); // Wait a bit after load
        }
    });

    // History Functions

    /**
     * Riwayat gagal dimuat (status false ATAU network fail). Tanpa varian
     * network, spinner #history-loading tertinggal selamanya karena callback
     * sukses tidak pernah terpanggil.
     */
    function riwayatGagalMuat() {
        $('#history-loading').addClass('hidden');
        $('#history-list').html('<div class="text-center py-5 text-slate-400">'
            + '<i class="bi bi-cloud-slash text-3xl mb-2 block"></i>'
            + '<div class="text-[13px]">Gagal memuat riwayat absensi.</div>'
            + '<button type="button" class="mt-1.5 text-[13px] font-bold text-blue-600 underline" data-muat-history>Coba lagi</button>'
            + '</div>');
        $('#history-data').removeClass('hidden');
    }

    function loadHistory() {
        $('#history-loading').removeClass('hidden');
        $('#history-data').addClass('hidden');

        $.get(apiUrl(config.endpoints.history), function (res) {
            $('#history-loading').addClass('hidden');
            if (!res || !res.status) {
                riwayatGagalMuat();
                return;
            }

            $('#history-data').removeClass('hidden');
            const stats = res.data.stats;
            const records = res.data.week;
            const disciplineRank = res.data.discipline_rank;

            // Render Stats
            renderStats(stats, disciplineRank);

            // Render Calendar
            if (res.data.calendar && res.data.calendar.length > 0) {
                renderCalendar(res.data.calendar);
            }

            // Render List
            renderHistoryList(records);
        });
    }

    function renderStats(stats, disciplineRank) {
        let disciplineHtml = '';
        if (disciplineRank && typeof disciplineRank.rank !== 'undefined' && disciplineRank.rank !== null) {
            const rank = parseInt(disciplineRank.rank, 10) || 0;
            const total = parseInt(disciplineRank.total, 10) || 0;
            const score = (disciplineRank.score !== undefined && disciplineRank.score !== null) ? Number(disciplineRank.score) : 0;
            const ontime = parseInt(disciplineRank.ontime, 10) || 0;
            const late = parseInt(disciplineRank.late, 10) || 0;
            const datangAwal = parseInt(disciplineRank.datang_awal || 0);

            disciplineHtml = `
            <div class="col-span-2">
                <div class="stat-card rank-card rounded-2xl bg-gradient-to-br from-white via-indigo-50/20 to-blue-50/30 p-4 border border-blue-100/80 shadow-sm hover:border-blue-300 transition cursor-pointer" onclick="showTab('ranking')">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-1.5 min-w-0 text-xs font-bold text-slate-700 leading-tight">
                            <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-xs shrink-0">
                                <i class="bi bi-trophy-fill"></i>
                            </span>
                            <span>Ranking Kedisiplinan</span>
                            <i class="bi bi-info-circle text-slate-400 text-[11px] shrink-0" title="Berdasarkan kedisiplinan & ketepatan waktu"></i>
                        </div>
                        <span class="badge-tw bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-full px-2.5 py-0.5 text-[10px] font-bold shrink-0 whitespace-nowrap">
                            Top Performance
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between mt-1">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-blue-600 tracking-tight">#${rank}</span>
                            <span class="text-xs font-semibold text-slate-400">/ ${total} Guru</span>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Skor Disiplin</div>
                            <div class="text-base font-black text-slate-800">${score.toFixed(1)}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs">
                        <div class="bg-white/90 rounded-xl p-2.5 border border-slate-100 shadow-2xs">
                            <div class="text-[10px] text-slate-400 font-semibold uppercase">Awal Datang</div>
                            <div class="font-extrabold text-slate-700 flex items-center gap-1 mt-0.5 text-xs">
                                <i class="bi bi-star-fill text-amber-500"></i> ${datangAwal} Menit
                            </div>
                        </div>
                        <div class="bg-white/90 rounded-xl p-2.5 border border-slate-100 shadow-2xs text-right">
                            <div class="text-[10px] text-slate-400 font-semibold uppercase">Detail Kehadiran</div>
                            <div class="font-extrabold text-slate-700 mt-0.5 text-xs">
                                <span class="text-emerald-600">${ontime} Tepat</span> • <span class="text-rose-500">${late} Telat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        } else {
            disciplineHtml = `
            <div class="col-span-2">
                <div class="stat-card rounded-2xl bg-white p-4 border border-slate-100 shadow-sm text-center">
                    <div class="text-xs font-bold text-slate-500 mb-1">Ranking Kedisiplinan</div>
                    <div class="text-xs text-slate-400 py-1">Data belum tersedia bulan ini</div>
                </div>
            </div>`;
        }

        const statsHtml = `
        <div class="grid grid-cols-2 gap-3">
            <!-- 1. Persentase Kehadiran -->
            <div class="col-span-2">
                <div class="stat-card rounded-2xl bg-white p-4 border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <span class="stat-label text-xs font-bold text-slate-500 flex items-center gap-1.5 mb-1 leading-tight">
                                <i class="bi bi-calendar-check text-brand-600 shrink-0"></i>
                                Kehadiran (Bulan Ini)
                            </span>
                            <div class="stat-value text-3xl font-black text-slate-800 tracking-tight">${stats.percentage}%</div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center gap-1 whitespace-nowrap bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-full px-2.5 py-1 text-xs font-bold shadow-2xs">
                                <i class="bi bi-check2-circle"></i> ${stats.hadir} Hari Hadir
                            </span>
                        </div>
                    </div>
                    <div class="progress-m3 w-full bg-slate-100 rounded-full h-2.5 overflow-hidden mt-3">
                        <div class="progress-m3-bar bg-gradient-to-r from-emerald-500 to-teal-500 h-2.5 rounded-full transition-all duration-500" style="width: ${Math.min(100, Math.max(0, stats.percentage))}%;"></div>
                    </div>
                    <div class="flex justify-between items-center mt-2 text-[11px] text-slate-400 font-medium">
                        <span>Target: 100%</span>
                        <span>${stats.percentage >= 90 ? 'Sangat Baik' : (stats.percentage >= 75 ? 'Cukup' : 'Perlu Ditingkatkan')}</span>
                    </div>
                </div>
            </div>
            
            <!-- 2. Terlambat -->
            <div>
                <div class="stat-card rounded-2xl bg-gradient-to-br from-white to-rose-50/40 p-3.5 border border-rose-100/70 shadow-sm">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="stat-label text-xs font-bold text-slate-500">Terlambat</span>
                        <span class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                            <i class="bi bi-alarm"></i>
                        </span>
                    </div>
                    <div class="stat-value text-2xl font-black text-rose-600 tracking-tight">${stats.telat_kali}<span class="text-xs font-bold text-slate-400 ms-0.5">x</span></div>
                    <div class="stat-sub text-[11px] font-medium text-slate-500 mt-1 truncate">
                        ${stats.telat_menit > 0 ? `<span class="text-rose-600 font-bold"><i class="bi bi-exclamation-circle-fill"></i> ${stats.telat_menit} Menit</span>` : '<span class="text-emerald-600 font-semibold"><i class="bi bi-check-circle-fill"></i> Luar biasa! 0m</span>'}
                    </div>
                </div>
            </div>
            
            <!-- 3. Dinas Luar -->
            <div>
                <div class="stat-card rounded-2xl bg-gradient-to-br from-white to-sky-50/40 p-3.5 border border-sky-100/70 shadow-sm">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="stat-label text-xs font-bold text-slate-500">Dinas Luar</span>
                        <span class="w-7 h-7 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xs">
                            <i class="bi bi-briefcase"></i>
                        </span>
                    </div>
                    <div class="stat-value text-2xl font-black text-sky-600 tracking-tight">${stats.dinas}<span class="text-xs font-bold text-slate-400 ms-0.5">hari</span></div>
                    <div class="stat-sub text-[11px] font-medium text-slate-500 mt-1 truncate">
                        Hari aktif dinas
                    </div>
                </div>
            </div>

            <!-- 4. Izin & Cuti -->
            <div class="col-span-2">
                <div class="stat-card rounded-2xl bg-white p-3.5 border border-slate-100 shadow-sm hover:border-emerald-200 transition cursor-pointer" onclick="showTab('izin')">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                            <i class="bi bi-calendar-minus text-lg"></i>
                        </div>
                        <div class="min-w-0 grow">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Izin & Cuti</span>
                            <div class="font-extrabold text-sm text-slate-800">${stats.izin_hari} Hari Terpakai</div>
                        </div>
                        <div class="text-xs font-semibold text-brand-600 flex items-center gap-0.5 bg-brand-50 px-2.5 py-1 rounded-xl">
                            <span>Kelola</span>
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            ${disciplineHtml}
        </div>`;
        $('#history-stats').html(statsHtml);
    }

    function renderCalendar(calendarData) {
        const today = witaYMD();
        const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        let html = '';
        dayNames.forEach(d => {
            html += `<div class="cal-day-header">${d}</div>`;
        });

        if (calendarData.length > 0) {
            const firstDate = new Date(calendarData[0].date);
            const firstDayOfWeek = firstDate.getDay();

            for (let i = 0; i < firstDayOfWeek; i++) {
                html += '<div class="cal-day" style="background:transparent;border-color:transparent" aria-hidden="true"></div>';
            }

            const statusLabels = {
                present: 'Hadir', late: 'Telat', dinas: 'Dinas Luar',
                holiday: 'Libur', alpha: 'Alpha', none: 'Belum ada data', future: 'Tanggal mendatang'
            };

            calendarData.forEach(day => {
                const isToday = day.date === today ? 'today' : '';
                let style = '';
                let extraClass = '';

                if (isToday) {
                    // Today handled by CSS class .today
                } else if (day.status !== 'none' && day.status !== 'future') {
                    // Light colored background for presence status
                    const bgColor = day.color || '#e2e8f0';
                    style = `background-color: ${bgColor}22; color: ${bgColor};`; // 22 is ~13% opacity in hex
                    extraClass = 'has-status';
                } else if (day.status === 'future') {
                    style = 'background: transparent; color: #cbd5e1; font-weight: 400;';
                }

                const label = statusLabels[day.status] || day.status;
                html += `<div class="cal-day ${isToday} ${extraClass}" style="${style}" title="${day.date} — ${label}">${day.day}</div>`;
            });
        }

        $('#history-calendar').html(html);
    }

    // Jam bisa datang sebagai "HH:MM:SS" atau "YYYY-MM-DD HH:MM:SS"; tanggal nol dianggap kosong
    function fmtJam(value) {
        if (!value) return '-';
        const str = String(value).trim();
        if (!str || str.indexOf('0000-00-00') === 0) return '-';
        const parts = str.split(' ');
        return (parts.length > 1 ? parts[1] : parts[0]).substr(0, 5);
    }

    // "2026-10-28" → "Sen, 28 Okt" (diparse manual agar tidak bergeser karena zona waktu)
    function fmtTglShort(tgl) {
        const raw = String(tgl || '');
        const parts = raw.split('-');
        if (parts.length < 3) return raw || '-';
        const d = new Date(+parts[0], +parts[1] - 1, +parts[2]);
        if (isNaN(d.getTime())) return raw;
        return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
    }

    function renderHistoryList(records) {
        if (!records || records.length === 0) {
            $('#history-list').html('<div class="text-center py-4 text-slate-400 text-[13px]">Belum ada riwayat minggu ini.</div>');
            return;
        }

        let listHtml = '<div class="mt-4"><h6 class="font-bold text-[11px] uppercase tracking-wider text-slate-400 px-1 mb-2">Riwayat Absensi Terakhir</h6>';
        records.forEach(r => {
            let badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            let dot = '#10b981';
            let text = 'Tepat Waktu';
            const st = r.status_masuk || '';
            if (st === 'LATE') {
                badge = 'bg-amber-50 text-amber-700 border-amber-200';
                dot = '#f59e0b';
                text = 'Terlambat ' + (r.telat_menit || 0) + 'm';
            } else if (st.indexOf('DINAS') !== -1) {
                badge = 'bg-sky-50 text-sky-700 border-sky-200';
                dot = '#0284c7';
                text = 'Dinas';
            } else if (st.indexOf('IZIN') !== -1 || st.indexOf('IJIN') !== -1) {
                badge = 'bg-purple-50 text-purple-700 border-purple-200';
                dot = '#7c3aed';
                text = 'Izin';
            } else if (st === 'SAKIT') {
                badge = 'bg-rose-50 text-rose-700 border-rose-200';
                dot = '#e11d48';
                text = 'Sakit';
            } else if (st.indexOf('ALPHA') !== -1 || st === 'ABSEN') {
                badge = 'bg-red-50 text-red-700 border-red-200';
                dot = '#ef4444';
                text = 'Alpha';
            }

            listHtml += `
                <div class="activity-card">
                    <span class="self-start mt-2 h-2 w-2 shrink-0 rounded-full" style="background:${dot}"></span>
                    <div class="grow min-w-0">
                        <div class="flex justify-between items-center gap-2">
                            <div class="font-bold text-base truncate">${escHtml(fmtTglShort(r.tgl))}</div>
                            <span class="badge-tw ${badge} rounded-full shrink-0" style="font-size:0.7rem">${text}</span>
                        </div>
                        <div class="text-slate-400 text-[13px] mt-1">
                            Masuk: ${fmtJam(r.jam_masuk)}
                            | Pulang: ${fmtJam(r.jam_pulang)}
                        </div>
                    </div>
                </div>`;
        });
        listHtml += '</div>';
        $('#history-list').html(listHtml);
    }

    // Koreksi Functions

    /**
     * Pesan di bawah pemilih tanggal koreksi. Tanpa ini, permintaan yang
     * gagal membuat dropdown membeku pada "Memuat data..." dan guru tidak
     * punya jalan keluar sama sekali.
     */
    function koreksiPesanMuat(teks, kosong) {
        const $pesan = $('#koreksiMuatPesan');
        if (!$pesan.length) return;
        if (!teks) {
            $pesan.addClass('hidden').html('');
            return;
        }
        $pesan.removeClass('hidden').html(
            '<i class="bi bi-' + (kosong ? 'inbox' : 'cloud-slash') + ' me-1 opacity-60"></i>' + escHtml(teks) +
            (kosong ? '' : ' <button type="button" class="font-bold text-blue-600 underline" data-muat-koreksi>Coba lagi</button>')
        );
    }

    function muatTanggalKoreksi() {
        const $sel = $('#koreksiAttendanceId');
        $sel.prop('disabled', true).html('<option value="">Memuat data...</option>');
        koreksiPesanMuat('', true);

        const gagal = function () {
            $sel.prop('disabled', true).html('<option value="">—</option>');
            koreksiPesanMuat('Gagal memuat daftar absensi yang bisa dikoreksi.');
        };

        $.get(apiUrl(config.endpoints.myAttendance), function (res) {
            if (!res || !res.status || !Array.isArray(res.data)) {
                gagal();
                return;
            }

            // Tanggal acuan dari server (appTimezone); jam perangkat hanya
            // dipakai bila nilai server belum tersedia.
            const today = config.hariIni || new Date().toLocaleDateString('en-CA');
            let options = '<option value="">-- Pilih Tanggal --</option>';
            res.data.forEach(r => {
                let label = `${r.tgl} - Masuk: ${fmtJam(r.jam_masuk)}`;
                let disabled = '';

                if (r.tgl === today) {
                    label += ' ℹ️ Hari Ini';
                }

                if (r.has_correction) {
                    label += ' (Sudah Dikoreksi)';
                    disabled = 'disabled';
                }

                options += `<option value="${r.id}" ${disabled}>${label}</option>`;
            });
            $sel.prop('disabled', false).html(options);

            if (!res.data.length) {
                koreksiPesanMuat('Belum ada absensi yang bisa dikoreksi.', true);
            }
        }).fail(gagal);
    }

    function muatRiwayatKoreksi() {
        // Load corrections history
        $.get(apiUrl(config.endpoints.myCorrections), function (res) {
            if (res.status && res.data && res.data.length > 0) {
                let html = '';
                res.data.forEach(c => {
                    let badgeClass = 'bg-amber-100 text-amber-500';
                    let dotColor = '#f59e0b';
                    let statusLabel = 'Menunggu';

                    if (c.status === 'approved') {
                        badgeClass = 'bg-emerald-100 text-emerald-500';
                        dotColor = '#10b981';
                        statusLabel = 'Disetujui';
                    } else if (c.status === 'rejected') {
                        badgeClass = 'bg-red-100 text-red-500';
                        dotColor = '#ef4444';
                        statusLabel = 'Ditolak';
                    }

                    const tglParsed = new Date(c.tgl).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                    const fieldLabel = c.field_name === 'jam_masuk' ? 'Jam Masuk' : 'Jam Pulang';

                    html += `
                    <div class="activity-card m3-card-elevated mb-3 shadow-none border-0" style="background: var(--m3-surface);">
                        <div class="grow min-w-0">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <div class="min-w-0">
                                    <div class="font-bold truncate" style="font-size: 13px;">${tglParsed}</div>
                                    <div class="text-blue-600 font-bold uppercase" style="font-size: 11px;">${fieldLabel}</div>
                                </div>
                                <span class="badge-tw ${badgeClass} rounded-full shrink-0" style="font-size: 10px;">${statusLabel}</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-100 mb-2">
                                <div class="text-slate-400 text-[13px] line-through">${escHtml(fmtJam(c.old_value))}</div>
                                <i class="bi bi-arrow-right text-slate-400 text-[13px] shrink-0"></i>
                                <div class="font-bold text-blue-600">${escHtml(fmtJam(c.new_value))}</div>
                            </div>
                            <div class="text-slate-400 text-[11px] leading-snug" style="overflow-wrap: anywhere;">
                                <i class="bi bi-chat-left-text me-1 opacity-50"></i> "${escHtml(c.reason)}"
                            </div>
                        </div>
                    </div>`;
                });
                $('#my-corrections-list').html(html);
            } else if (res && res.status && Array.isArray(res.data)) {
                $('#my-corrections-list').html(`
                    <div class="text-center py-5 opacity-50">
                        <i class="bi bi-clipboard-x text-4xl mb-3 block"></i>
                        <div class="text-[13px]">Belum ada riwayat pengajuan koreksi</div>
                    </div>
                `);
            } else {
                koreksiListGagal();
            }
        }).fail(koreksiListGagal);
    }

    /**
     * Riwayat koreksi gagal dimuat. Dulu bagian ini tidak ditangani sama
     * sekali: tab membuka ruang kosong tanpa penjelasan maupun percobaan lagi.
     */
    function koreksiListGagal() {
        $('#my-corrections-list').html(`
            <div class="text-center py-5 opacity-70">
                <i class="bi bi-cloud-slash text-3xl mb-2 block"></i>
                <div class="text-[13px]">Gagal memuat riwayat pengajuan koreksi.</div>
                <button type="button" class="mt-1.5 text-[13px] font-bold text-blue-600 underline" data-muat-koreksi>Coba lagi</button>
            </div>
        `);
    }

    function loadKoreksiData() {
        muatTanggalKoreksi();
        muatRiwayatKoreksi();
    }

    function submitKoreksi() {
        const form = $('#formKoreksi');
        const attendanceId = $('#koreksiAttendanceId').val();
        const fieldName = form.find('[name="field_name"]').val();
        const newValue = form.find('[name="new_value"]').val();
        const reason = form.find('[name="reason"]').val();

        if (!attendanceId || !fieldName || !newValue || !reason) {
            showAlert('error', 'Error', 'Semua field harus diisi');
            return;
        }

        showLoading('Mengirim...');

        const data = {
            attendance_id: attendanceId,
            field_name: fieldName,
            new_value: newValue,
            reason: reason
        };
        data[config.csrfName] = config.csrfToken;

        $.post(apiUrl(config.endpoints.koreksi), data, function (res) {
            if (res.status) {
                showAlert('success', 'Berhasil', res.message);
                form[0].reset();
                loadKoreksiData();
            } else {
                showAlert('error', 'Gagal', res.message);
            }
        }, 'json');
    }

    // Izin Functions
    function submitIzin() {
        const form = document.getElementById('formIzin');
        const formData = new FormData(form);

        // Get values for validation
        const jenis = formData.get('jenis');
        const kategoriCuti = formData.get('kategori_cuti') || '';
        const tglMulai = formData.get('tgl_mulai');
        const tglSelesai = formData.get('tgl_selesai');
        const bukti = formData.get('bukti');

        // Pengajuan tidak boleh mundur, dan cuti biasa butuh beberapa hari
        // maju. Batasnya dirender server (hari ini versi appTimezone), jadi
        // jam/tanggal perangkat tidak ikut menentukan; server menolak ulang
        // sebagai penjaga.
        const batas = batasIzin(jenis, kategoriCuti);
        const cutiMepet = jenis === 'cuti' && butuhMajuCuti(kategoriCuti) && tglMulai && tglMulai < batas;
        if (!tglMulai) {
            showAlert('warning', 'Tanggal Belum Diisi', 'Tentukan tanggal mulai pengajuan.');
            return;
        }
        if (cutiMepet) {
            showAlert('warning', 'Cuti Harus Diajukan Lebih Awal',
                'Cuti harus diajukan minimal ' + hariMajuCuti() + ' hari sebelum tanggal mulai. '
                + 'Tanggal mulai paling cepat ' + fmtTglID(batas) + '.');
            return;
        }
        if (tglMulai < batas) {
            showAlert('warning', 'Tanggal Tidak Boleh Mundur',
                'Pengajuan hanya untuk ' + fmtTglShort(batas) + ' ke depan. Untuk tanggal yang sudah lewat, gunakan tab Koreksi.');
            return;
        }
        if (tglSelesai && tglSelesai < tglMulai) {
            showAlert('warning', 'Rentang Tanggal Tidak Valid', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
            return;
        }

        // Calculate duration
        let durasi = 1;
        if (tglMulai && tglSelesai) {
            const start = new Date(tglMulai);
            const end = new Date(tglSelesai);
            durasi = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
        }

        // Validation: Check if attachment is required
        let requiresAttachment = false;
        let errorMessage = '';

        if (jenis === 'cuti') {
            // Kategori wajib dipilih bila pegawai punya hak kategori
            // (dropdown ditampilkan). Pegawai tanpa hak kategori boleh
            // mengirim generik (kategori NULL di server).
            const $wrapKat = $('#wrapKategoriCuti');
            const kategoriCutiVal = ($('#kategoriCuti').val() || '').trim();
            if ($wrapKat.length && !$wrapKat.hasClass('hidden')
                && $wrapKat.find('option').length > 1 && !kategoriCutiVal) {
                showAlert('warning', 'Jenis Cuti Belum Dipilih', 'Silakan pilih jenis cuti (Tahunan, Besar, Melahirkan, atau Karena Alasan Penting).');
                return;
            }
            requiresAttachment = true;
            errorMessage = 'Untuk pengajuan Cuti, wajib melampirkan surat cuti.';
        } else if (jenis === 'ijin') {
            requiresAttachment = true;
            errorMessage = 'Untuk pengajuan Izin, wajib melampirkan bukti screenshot ijin via wa/sms ke kepala sekolah.';
        } else if (jenis === 'sakit' && durasi > 3) {
            // Pengecekan di browser memakai hitungan kalender; server memakai
            // hari kerja efektif. Browser yang lebih longgar itu aman —
            // kalau server menolak, guru tinggal melampirkan surat. Yang
            // berbahaya adalah sebaliknya: browser mewajibkan lampiran
            // sementara server ternyata tidak membutuhkannya.
            requiresAttachment = true;
            errorMessage = 'Untuk sakit lebih dari 3 hari kerja, wajib melampirkan surat keterangan dokter.';
        }

        // Check if file is uploaded when required
        if (requiresAttachment && (!bukti || bukti.size === 0)) {
            showAlert('warning', 'Lampiran Diperlukan', errorMessage);
            return;
        }

        formData.append(config.csrfName, config.csrfToken);

        showLoading('Mengirim...');

        $.ajax({
            url: apiUrl('/presensi/izin'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    showAlert('success', 'Berhasil', res.message);
                    form.reset();
                    updateLampiranLabel(); // Reset label
                    updateKategoriCuti();  // Reset dropdown kategori cuti
                    updateBatasTanggalIzin(); // Reset batas tanggal ke "hari ini"
                    // Sisa kuota berubah setelah pengajuan masuk, jadi
                    // angka yang tampil harus di-refresh juga.
                    fetchIzinKuota();
                    bukaTab('home');
                } else {
                    showAlert('error', 'Gagal', res.message);
                }
            },
            error: function () {
                showAlert('error', 'Error', 'Terjadi kesalahan jaringan');
            }
        });
    }

    // Utility Functions
    // Tombol senyap di kepala modal FaceScan menyimpan preferensinya di key
    // yang sama. Jalur lama wajib membaca ulang tiap panggilan supaya guru
    // yang sudah membisukan notifikasi tidak tetap mendengar nada/ucapan
    // dari mesin audio versi lama.
    function suaraNotifikasiAktif() {
        try {
            return localStorage.getItem('presensi_guru_sound') !== '0';
        } catch (e) {
            return true;
        }
    }

    function ensureAudioCtx() {
        if (!suaraNotifikasiAktif()) return null;
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        if (!state.audioCtx) {
            state.audioCtx = new AC();
        }
        if (state.audioCtx.state === 'suspended') {
            state.audioCtx.resume();
        }
        // Prime speech synthesis during user interaction
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.getVoices();
            } catch (e) {}
        }
        return state.audioCtx;
    }

    // Cache voice bahasa Indonesia
    let indonesianVoice = null;
    function getIndonesianVoice() {
        if (!('speechSynthesis' in window)) return null;
        if (indonesianVoice) return indonesianVoice;
        try {
            const voices = window.speechSynthesis.getVoices();
            if (!voices || voices.length === 0) return null;
            indonesianVoice = voices.find(v => v.lang === 'id-ID' || v.lang === 'id_ID')
                || voices.find(v => v.lang && v.lang.toLowerCase().startsWith('id'))
                || voices.find(v => v.name && (v.name.toLowerCase().includes('indonesia') || v.name.toLowerCase().includes('indonesian')))
                || null;
        } catch (e) {}
        return indonesianVoice;
    }

    if ('speechSynthesis' in window) {
        try {
            window.speechSynthesis.onvoiceschanged = function () {
                indonesianVoice = null;
                getIndonesianVoice();
            };
            window.speechSynthesis.getVoices();
        } catch (e) {}
    }

    function speakAttendanceVoice(text, onComplete) {
        if (!suaraNotifikasiAktif() || !('speechSynthesis' in window) || !text) {
            if (typeof onComplete === 'function') onComplete();
            return;
        }
        try {
            window.speechSynthesis.cancel();
            const cleanText = String(text).replace(/['"`]/g, '').trim();
            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'id-ID';
            utterance.rate = 1.0; // Kecepatan normal alami yang jelas dan nyaman didengar
            utterance.pitch = 1.0;

            const voice = getIndonesianVoice();
            if (voice) utterance.voice = voice;

            // PENTING: Simpan referensi ke global agar Chrome Android Garbage Collector
            // tidak mematikan objek suara sebelum pengucapan selesai.
            window._activeAttendanceUtterance = utterance;

            let completed = false;
            let resumeTimer = null;
            let safetyTimer = null;

            const cleanup = () => {
                if (resumeTimer) {
                    clearInterval(resumeTimer);
                    resumeTimer = null;
                }
                if (safetyTimer) {
                    clearTimeout(safetyTimer);
                    safetyTimer = null;
                }
                window._activeAttendanceUtterance = null;
            };

            const done = () => {
                if (!completed) {
                    completed = true;
                    cleanup();
                    if (typeof onComplete === 'function') onComplete();
                }
            };

            utterance.onend = done;
            utterance.onerror = function (err) {
                console.warn('speakAttendanceVoice onerror:', err);
                done();
            };

            // Safety timeout yang cukup panjang sesuai jumlah karakter kalimat (minimal 9.5 detik)
            const safetyDuration = Math.max(9500, cleanText.length * 120);
            safetyTimer = setTimeout(done, safetyDuration);

            // Keep-alive heartbeat untuk mengatasi bug Chrome Android yang sering pause di tengah jalan
            resumeTimer = setInterval(() => {
                if (window.speechSynthesis && window.speechSynthesis.speaking) {
                    window.speechSynthesis.resume();
                } else if (!window.speechSynthesis || !window.speechSynthesis.speaking) {
                    if (resumeTimer) {
                        clearInterval(resumeTimer);
                        resumeTimer = null;
                    }
                }
            }, 300);

            setTimeout(() => {
                try {
                    window.speechSynthesis.speak(utterance);
                } catch (e) {
                    console.warn('speak error:', e);
                    done();
                }
            }, 80);
        } catch (e) {
            console.warn('speakAttendanceVoice error:', e);
            if (typeof onComplete === 'function') onComplete();
        }
    }

    function playSuccessSound(isLate = false) {
        const ctx = ensureAudioCtx();
        if (!ctx) return;
        try {
            const now = ctx.currentTime;
            let freqs;
            if (isLate) {
                // Peringatan terlambat: nada minor turun yang terdengar tegas
                freqs = [440, 349.23];
            } else if (config.currentMode === 'checkout') {
                // Nada pulang: santai & nyaman
                freqs = [783.99, 659.25, 523.25];
            } else {
                // Hadir tepat waktu: nada naik harmonis ceria C5 -> E5 -> G5 -> C6
                freqs = [523.25, 659.25, 783.99, 1046.50];
            }
            freqs.forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = isLate ? 'triangle' : 'sine';
                osc.frequency.setValueAtTime(freq, now + (i * 0.12));
                gain.gain.setValueAtTime(0.12, now + (i * 0.12));
                gain.gain.exponentialRampToValueAtTime(0.001, now + (i * 0.12) + 0.45);
                osc.start(now + (i * 0.12));
                osc.stop(now + (i * 0.12) + 0.5);
            });
        } catch (e) {
            console.warn('Success sound failed', e);
        }
    }

    function playCaptureShutter() {
        const ctx = ensureAudioCtx();
        if (!ctx) return;
        try {
            const now = ctx.currentTime;
            const sr = ctx.sampleRate;
            const len = Math.floor(sr * 0.06);
            const buf = ctx.createBuffer(1, len, sr);
            const d = buf.getChannelData(0);
            for (let i = 0; i < len; i++) {
                d[i] = (Math.random() * 2 - 1) * Math.exp(-i / (sr * 0.012));
            }
            const src = ctx.createBufferSource();
            src.buffer = buf;
            const g = ctx.createGain();
            g.gain.setValueAtTime(0.12, now);
            g.gain.exponentialRampToValueAtTime(0.001, now + 0.06);
            src.connect(g).connect(ctx.destination);
            src.start(now);
        } catch (e) {
            console.warn('Shutter sound failed', e);
        }
    }

    // Tema warna per jenis ikon — selaras dengan gaya popup denda
    // (ikon glowing + tombol pill + backdrop gelap).
    const ALERT_THEMES = {
        info:    { color: '#0284c7', bg: '#e0f2fe', glow: 'rgba(2, 132, 199, 0.15)',  icon: 'bi-info-circle-fill' },
        success: { color: '#16a34a', bg: '#dcfce7', glow: 'rgba(22, 163, 74, 0.15)',  icon: 'bi-check-circle-fill' },
        error:   { color: '#dc2626', bg: '#fee2e2', glow: 'rgba(220, 38, 38, 0.15)',  icon: 'bi-x-circle-fill' },
        warning: { color: '#d97706', bg: '#fef3c7', glow: 'rgba(217, 119, 6, 0.15)',   icon: 'bi-exclamation-triangle-fill' }
    };

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function showAlert(icon, title, text) {
        const theme = ALERT_THEMES[icon] || ALERT_THEMES.info;
        const safeTitle = escapeHtml(title);
        const safeText = escapeHtml(text).replace(/\n/g, '<br>');
        Swal.fire({
            html: `
                <div class="px-1 py-2 text-center">
                    <div class="mb-3 inline-flex items-center justify-center"
                        style="width: 64px; height: 64px; background: ${theme.bg}; color: ${theme.color}; border-radius: 22px; box-shadow: 0 8px 24px ${theme.glow};">
                        <i class="bi ${theme.icon}" style="font-size: 30px;"></i>
                    </div>
                    <h5 class="font-bold text-slate-800 mb-1" style="font-size: 18px; letter-spacing: -0.3px;">${safeTitle}</h5>
                    <p class="text-slate-400 text-[13px] mb-1" style="line-height: 1.5;">${safeText}</p>
                </div>`,
            showConfirmButton: true,
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                popup: 'rounded-3xl border-0 shadow-lg px-2 py-3',
                confirmButton: 'btn-tw btn-brand w-full py-2.5 rounded-full font-bold shadow-sm text-white',
                actions: 'flex flex-column w-100 px-3 mt-3 gap-1'
            },
            didOpen: () => {
                const btn = Swal.getConfirmButton();
                if (btn) {
                    btn.style.backgroundColor = theme.color;
                    btn.style.borderColor = theme.color;
                }
            },
            focusConfirm: false,
            backdrop: 'rgba(15, 23, 42, 0.65)'
        });
    }

    function showToast(icon, title) {
        Swal.fire({
            toast: true,
            position: 'top',
            icon: icon,
            title: title,
            timer: 3000,
            showConfirmButton: false
        });
    }

    function showLoading(title) {
        Swal.fire({
            title: title || 'Loading...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    function updateLampiranLabel() {
        const jenis = $('#jenisIzin').val();
        let label = 'Lampirkan Bukti (Foto/PDF)';

        if (jenis === 'cuti') label = 'Unggah Surat Cuti (PDF/Gambar)';
        if (jenis === 'sakit') label = 'Unggah Surat Keterangan Dokter';
        if (jenis === 'ijin') label = 'Unggah Bukti Izin (Screenshot WA)';

        $('#lampiranLabel').text(label);
    }

    /**
     * Tampilkan/sembunyikan dropdown kategori cuti saat jenis "Cuti"
     * dipilih. Opsi sudah difilter server-side sesuai status kepegawaian
     * (PNS/PPPK) dan jenis kelamin pegawai (CutiKategoriService).
     */
    function updateKategoriCuti() {
        const $wrap = $('#wrapKategoriCuti');
        if (!$wrap.length) return;

        const jenis = $('#jenisIzin').val();
        // Guru tanpa hak kategori cukup bila opsi kosong: dropdown tetap
        // disembunyikan dan cuti dikirim generik (kategori NULL).
        const adaOpsi = $wrap.find('option').length > 1;

        if (jenis === 'cuti' && adaOpsi) {
            $wrap.removeClass('hidden');
            updateKetentuanCuti();
        } else {
            $wrap.addClass('hidden');
            $('#kategoriCuti').val('');
            $('#ketentuanCuti').addClass('hidden').text('');
        }
        renderSisaKuota();
    }

    /** Tampilkan ketentuan singkat kategori cuti yang terpilih. */
    function updateKetentuanCuti() {
        const $opt = $('#kategoriCuti option:selected');
        const $box = $('#ketentuanCuti');
        if (!$box.length) return;

        const ketentuan = ($opt.data('ketentuan') || '').toString();
        if (ketentuan) {
            $box.text(ketentuan).removeClass('hidden');
        } else {
            $box.addClass('hidden').text('');
        }
    }

    /** "2026-11-01" → "01/11/2026" (diparse manual agar tidak bergeser zona waktu) */
    function fmtTglID(iso) {
        const p = String(iso || '').split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : (iso || '-');
    }

    /**
     * Kategori cuti yang dikecualikan dari aturan hari maju. Daftarnya
     * dirender server dari CutiKategoriService::KATEGORI_BEBAS_HARI_MAJU,
     * supaya daftar pengecualian cukup diubah di satu tempat.
     */
    function kategoriBebasHariMaju() {
        const wrap = document.getElementById('izinTanggal');
        return ((wrap && wrap.dataset.kategoriBebasMaju) || '').split(/\s+/).filter(Boolean);
    }

    function butuhMajuCuti(kategori) {
        return kategoriBebasHariMaju().indexOf(String(kategori || '')) === -1;
    }

    /**
     * Batas bawah tanggal mulai untuk kategori terpilih. Kedua angkanya
     * dirender server lewat data-min-* (hari ini versi appTimezone), jadi jam
     * perangkat tidak ikut menentukan. Cuti biasa butuh beberapa hari maju;
     * kategori kedaruratan dan jenis lain cukup mulai hari ini.
     */
    function batasIzin(jenis, kategori) {
        const wrap = document.getElementById('izinTanggal');
        const dasar = (wrap && wrap.dataset.minDasar) || new Date().toLocaleDateString('en-CA');
        return jenis === 'cuti' && butuhMajuCuti(kategori)
            ? ((wrap && wrap.dataset.minCuti) || dasar)
            : dasar;
    }

    /** Banyak hari majunya aturan cuti, dihitung dari kedua batas server. */
    function hariMajuCuti() {
        const wrap = document.getElementById('izinTanggal');
        const dasar = batasIzin(''), cuti = (wrap && wrap.dataset.minCuti) || '';
        if (!cuti || !/^\d{4}-\d{2}-\d{2}$/.test(dasar) || !/^\d{4}-\d{2}-\d{2}$/.test(cuti)) return 0;
        const d = (s) => new Date(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10));
        return Math.max(0, Math.round((d(cuti) - d(dasar)) / 86400000));
    }

    /** Kunci pemilih tanggal + teks bantu mengikuti kategori yang dipilih. */
    function updateBatasTanggalIzin() {
        const mulai = document.getElementById('izinTglMulai');
        const selesai = document.getElementById('izinTglSelesai');
        if (!mulai || !selesai) return;

        const jenis = $('#jenisIzin').val() || '';
        const kategori = $('#kategoriCuti').val() || '';
        const perluMaju = jenis === 'cuti' && butuhMajuCuti(kategori);
        const batas = batasIzin(jenis, kategori);
        mulai.min = batas;
        selesai.min = batas;
        // Nilai terisi yang jadi terlarang dikosongkan, bukan digeser paksa,
        // agar guru melihat sendiri apa yang berubah.
        if (mulai.value && mulai.value < batas) mulai.value = '';
        if (selesai.value && selesai.value < batas) selesai.value = '';

        const hint = document.getElementById('izinTanggalHint');
        if (hint) {
            if (perluMaju) {
                hint.textContent = 'Cuti harus diajukan minimal ' + hariMajuCuti() + ' hari sebelum: mulai '
                    + fmtTglID(batas) + ' dan seterusnya.';
            } else if (jenis === 'cuti') {
                const lbl = ($('#kategoriCuti option:selected').text() || '').trim();
                hint.textContent = (lbl ? lbl + ' ' : 'Cuti ini ')
                    + 'boleh dimulai hari ini (' + fmtTglID(batas) + '), tanpa menunggu '
                    + hariMajuCuti() + ' hari.';
            } else {
                hint.textContent = 'Tanggal pengajuan tidak boleh mundur: mulai ' + fmtTglID(batas) + ' dan seterusnya.';
            }
        }

        // "Sampai Tanggal" tetap mengikuti "Mulai Tanggal" yang sudah terisi.
        $(mulai).trigger('change');
    }

    // Batas durasi kalender per kategori — sisi server (CutiKategoriService)
    // yang menegakkan angka final; ini hanya untuk hint sisa kuota.
    const KATEGORI_CUTI_MAKS = { besar: 90, melahirkan: 90, cap: 30 };

    /**
     * Sisa kuota izin/cuti/sakit/dinas untuk kategori terpilih.
     *
     * Tanpa ini guru baru tahu sisa kuotanya setelah form terisi penuh dan
     * submit ditolak. Kuotanya sendiri ditegakkan server
     * (IzinKuotaService); ini murni tampilan agar angka itu terlihat
     * sebelum kecewa.
     */
    function renderSisaKuota() {
        const $box = $('#sisaKuota');
        if (!$box.length) return;

        const jenis = $('#jenisIzin').val();
        if (!jenis) {
            $box.removeClass('text-rose-600 text-amber-600 text-slate-500')
                .addClass('text-slate-500')
                .text('Pilih kategori untuk melihat sisa kuota tahun ini.');
            return;
        }

        // Kategori cuti besar/melahirkan/CAP bukan bagian dari kuota cuti
        // tahunan — tampilkan batas durasi haknya, bukan saldo kuota.
        const kategoriCuti = $('#kategoriCuti').val() || '';
        if (jenis === 'cuti' && kategoriCuti && kategoriCuti !== 'tahunan') {
            if (kategoriCuti === 'besar') {
                $box.removeClass('text-rose-600 text-amber-600 text-slate-500')
                    .addClass('text-rose-600')
                    .text('Cuti Besar hanguskan Cuti Tahunan tahun berjalan. Durasi pengajuan maksimal 90 hari (3 bulan).');
            } else if (KATEGORI_CUTI_MAKS[kategoriCuti]) {
                $box.removeClass('text-rose-600 text-amber-600 text-slate-500')
                    .addClass('text-slate-500')
                    .text('Hak mandiri — bukan bagian kuota Cuti Tahunan. Durasi pengajuan maksimal ' + KATEGORI_CUTI_MAKS[kategoriCuti] + ' hari.');
            }
            return;
        }

        const q = state.izinKuota && state.izinKuota[jenis];
        if (!q) {
            $box.removeClass('text-rose-600 text-amber-600 text-slate-500')
                .addClass('text-slate-500')
                .text('Sisa kuota tidak tersedia.');
            return;
        }

        $box.removeClass('text-rose-600 text-amber-600 text-slate-500');

        // Kuota tahunan habis = tidak ada jalan lagi sama sekali.
        if (q.batas > 0 && q.sisa <= 0) {
            $box.addClass('text-rose-600').text(
                'Kuota ' + q.label + ' tahun ini sudah habis (' + q.terpakai + '/' + q.batas + ' hari kerja).'
            );
            return;
        }

        let teks = '';
        if (q.batas > 0) {
            teks += 'Sisa kuota ' + q.label + ': ' + q.sisa + ' dari ' + q.batas
                 + ' hari kerja (terpakai ' + q.terpakai + ')';
        } else {
            teks += 'Kuota ' + q.label + ' tahunan tidak dibatasi';
        }

        if (q.batasPengajuan > 0) {
            teks += ' · max ' + q.batasPengajuan + ' hari per pengajuan';
            if (q.sisa > 0 && q.sisa < q.batasPengajuan) {
                $box.addClass('text-amber-600');
                teks += ' — sisa tidak cukup untuk batas per pengajuan';
            } else {
                $box.addClass('text-slate-500');
            }
        } else {
            $box.addClass('text-slate-500');
        }

        $box.text(teks);
    }

    async function fetchIzinKuota() {
        try {
            const res = await $.getJSON(apiUrl('/presensi/izin-kuota'));
            if (res.status && res.data && res.data.kuota) {
                state.izinKuota = res.data.kuota;
            }
        } catch (e) {
            console.warn('Failed to fetch leave quota:', e);
        }
        renderSisaKuota();
    }

    function updateSisaKuota() {
        renderSisaKuota();
    }

    // Public API
    return {
        init: init,
        openCamera: openCamera,
        closeCamera: closeCamera,
        takeSnapshot: takeSnapshot,
        triggerManualCapture: triggerManualCapture,
        handleScanClick: handleScanClick,
        detectLocation: detectLocation,
        showLocationHelp: showLocationHelp,
        submitIzin: submitIzin,
        submitKoreksi: submitKoreksi,
        loadHistory: loadHistory,
        loadKoreksiData: loadKoreksiData,
        playSuccessSound: playSuccessSound,
        updateLampiranLabel: updateLampiranLabel,
        updateKategoriCuti: updateKategoriCuti,
        updateKetentuanCuti: updateKetentuanCuti,
        updateBatasTanggalIzin: updateBatasTanggalIzin,
        fetchIzinKuota: fetchIzinKuota,
        updateSisaKuota: updateSisaKuota,
        config: config
    };
})();

// Expose to global for onclick handlers
// (showTab tidak diekspor di sini: halaman /presensi punya manajer tab
// sendiri — lihat window.showTab di absen_mobile.php — dan modul ini hanya
// memanggilnya lewat bukaTab.)
window.openCamera = function () { AbsensiApp.openCamera(); };
window.closeCamera = function () { AbsensiApp.closeCamera(); };
window.takeSnapshot = function () { AbsensiApp.takeSnapshot(); };
window.triggerManualCapture = function () { AbsensiApp.triggerManualCapture(); };
window.handleScanClick = function () { AbsensiApp.handleScanClick(); };
window.detectLocation = function () { AbsensiApp.detectLocation(); };
window.showLocationHelp = function () { AbsensiApp.showLocationHelp(); };
window.submitIzin = function () { AbsensiApp.submitIzin(); };
window.updateLampiranLabel = function () { AbsensiApp.updateLampiranLabel(); };
window.updateKategoriCuti = function () { AbsensiApp.updateKategoriCuti(); };
window.updateKetentuanCuti = function () { AbsensiApp.updateKetentuanCuti(); };
window.updateBatasTanggalIzin = function () { AbsensiApp.updateBatasTanggalIzin(); };
window.updateSisaKuota = function () { AbsensiApp.updateSisaKuota(); };
window.submitKoreksi = function () { AbsensiApp.submitKoreksi(); };
window.loadHistory = function () { AbsensiApp.loadHistory(); };
window.loadKoreksiData = function () { AbsensiApp.loadKoreksiData(); };
