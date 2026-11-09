<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    /* Leaflet Map & Custom Markers */
    #map {
        height: 600px;
        width: 100%;
        border-radius: 0 0 0.875rem 0.875rem;
        z-index: 1;
        background-color: #f1f5f9;
    }

    @media (max-width: 768px) {
        #map {
            height: 420px;
        }
    }

    /* Custom Leaflet SVG Pins */
    .custom-leaflet-pin {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .pin-pulse-ring {
        position: absolute;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        animation: pin-pulse 2s infinite ease-out;
        opacity: 0.7;
        pointer-events: none;
    }

    .pin-blue .pin-pulse-ring {
        background: rgb(var(--primary-500-rgb) / 0.35);
    }

    .pin-red .pin-pulse-ring {
        background: rgba(239, 68, 68, 0.45);
    }

    .pin-body {
        width: 34px;
        height: 34px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
        border: 2px solid #ffffff;
        transition: transform 0.2s ease;
    }

    .custom-leaflet-pin:hover .pin-body {
        transform: rotate(-45deg) scale(1.15);
    }

    .pin-body i {
        transform: rotate(45deg);
        color: #ffffff;
        font-size: 15px;
    }

    .pin-blue .pin-body {
        background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
    }

    .pin-red .pin-body {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
    }

    @keyframes pin-pulse {
        0% {
            transform: scale(0.6);
            opacity: 0.9;
        }
        100% {
            transform: scale(1.8);
            opacity: 0;
        }
    }

    /* Search Box & Floating Controls */
    .search-map-overlay {
        position: absolute;
        top: 14px;
        left: 56px;
        z-index: 999;
        width: calc(100% - 130px);
        max-width: 380px;
    }

    .map-badge-counter {
        background: rgba(15, 23, 42, 0.88);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #ffffff;
        padding: 7px 16px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 500;
        position: absolute;
        bottom: 16px;
        left: 16px;
        z-index: 999;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .map-topright-overlay {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 999;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .map-control-btn {
        width: 38px;
        height: 38px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #334155;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .map-control-btn:hover {
        background: #f8fafc;
        color: var(--primary-600);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
    }

    /* Location List Cards */
    .location-scroll-container {
        max-height: 575px;
        overflow-y: auto;
    }

    .location-scroll-container::-webkit-scrollbar {
        width: 5px;
    }

    .location-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .loc-item {
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
    }

    .loc-item:hover {
        border-color: var(--primary-300);
        background-color: #f8fafc;
        transform: translateX(2px);
    }

    .loc-card-active {
        border-left: 4px solid var(--primary-600) !important;
        background-color: var(--primary-50) !important;
        box-shadow: 0 2px 8px rgb(var(--primary-600-rgb) / 0.08);
    }

    /* Realtime autosave toast */
    #geoSaveToast {
        position: absolute;
        top: 14px;
        left: 50%;
        transform: translateX(-50%) translateY(-12px);
        z-index: 1000;
        background: rgba(15, 23, 42, 0.92);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        pointer-events: none;
        transition: all 0.25s ease;
        box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        white-space: nowrap;
    }
    #geoSaveToast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    #geoSaveToast.success { background: rgba(5, 150, 105, 0.95); }
    #geoSaveToast.error { background: rgba(220, 38, 38, 0.95); }
    .resize-handle-pin {
        width: 16px; height: 16px;
        background: #fff;
        border: 3px solid var(--primary-600);
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        cursor: ew-resize;
    }
    .leaflet-marker-dragging .pin-body { transform: rotate(-45deg) scale(1.2); }

    /* Range Slider Styling */
    .radius-range-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 6px;
        border-radius: 5px;
        background: #e2e8f0;
        outline: none;
    }

    .radius-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary-600);
        cursor: pointer;
        box-shadow: 0 2px 6px rgb(var(--primary-600-rgb) / 0.4);
        transition: transform 0.15s ease;
    }

    .radius-range-slider::-webkit-slider-thumb:hover {
        transform: scale(1.2);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="mb-0 font-bold text-slate-800 flex items-center gap-2.5">
                <span class="p-2.5 bg-blue-100 text-blue-600 rounded-xl shadow-sm">
                    <i class="bi bi-geo-alt-fill text-xl"></i>
                </span>
                <span><?= esc($judul ?? 'Lokasi Kantor & Geofence') ?></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">Kelola koordinat GPS dan radius batas presensi kehadiran GTK.</p>
        </div>
        <div class="flex items-center gap-2">
            <nav class="breadcrumb-tw hidden sm:flex">
                <a href="<?= base_url('admin') ?>" class="no-underline">
                    <i class="bi bi-house-door mr-1"></i>Admin
                </a>
                <span class="sep">/</span>
                <a href="<?= base_url('admin/presensi') ?>" class="no-underline">Presensi</a>
                <span class="sep">/</span>
                <span class="active">Lokasi</span>
            </nav>
        </div>
    </div>
</div>

<div class="content-area space-y-6">

    <!-- Main Dual-Pane Section: Map & List -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Interactive Map (Col 7 / 8) -->
        <div class="lg:col-span-7 xl:col-span-8">
            <div class="card-tw overflow-hidden shadow-sm">
                <!-- Map Header -->
                <div class="card-tw-header flex-wrap gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                            <i class="bi bi-map-fill"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-slate-800 text-sm md:text-base leading-none">Peta Geofence Interaktif</h6>
                            <span class="text-xs text-slate-500">Visualisasi lingkaran batas absensi GPS</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-tw btn-tw-sm btn-outline-primary" onclick="locateCurrentGPS()" title="Pusatkan ke posisi GPS browser Anda">
                            <i class="bi bi-crosshair mr-1 text-blue-600"></i> Lokasi Saya
                        </button>
                        <button type="button" class="btn-tw btn-tw-sm btn-light border" onclick="fitAllMarkers()" title="Fokuskan ke seluruh titik lokasi">
                            <i class="bi bi-arrows-angle-contract"></i>
                        </button>
                        <button type="button" class="btn-tw btn-tw-sm btn-primary shadow-sm" onclick="addLocation()">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Lokasi
                        </button>
                    </div>
                </div>

                <!-- Map Body & Floating Controls -->
                <div class="relative">
                    <!-- Nominatim OpenStreetMap Search Box -->
                    <div class="search-map-overlay">
                        <div class="relative shadow-md rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="bi bi-search"></i>
                            </div>
                            <input type="text" id="mapSearchInput" 
                                class="w-full pl-10 pr-20 py-2.5 bg-white/95 backdrop-blur text-xs sm:text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-800 placeholder:text-slate-400 transition"
                                placeholder="Cari gedung, jalan, kota..." 
                                onkeypress="handleSearchKey(event)">
                            <div class="absolute inset-y-0 right-1 flex items-center gap-1">
                                <button type="button" id="btnClearSearch" class="hidden text-slate-400 hover:text-slate-600 p-1 text-xs" onclick="clearMapSearch()">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                                <button type="button" class="btn-tw btn-tw-sm btn-primary h-7 px-2.5 text-xs mr-1 rounded-lg" onclick="searchLocation()" id="btnMapSearch">
                                    Cari
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Floating Map Tool Buttons -->
                    <div class="map-topright-overlay">
                        <button type="button" class="map-control-btn" onclick="toggleMapLayer()" id="btnLayerToggle" title="Ganti Tipe Peta (Jalan / Foto Satelit)">
                            <i class="bi bi-layers text-base"></i>
                        </button>
                        <button type="button" class="map-control-btn" onclick="openGeofenceGuide()" title="Panduan & Rekomendasi Radius Geofence">
                            <i class="bi bi-question-circle text-base"></i>
                        </button>
                    </div>

                    <!-- Leaflet Map Container -->
                    <div id="map"></div>
                    <div id="geoSaveToast"><span id="geoSaveToastIcon"><i class="bi bi-arrow-repeat"></i></span><span id="geoSaveToastText">Menyimpan...</span></div>

                    <!-- Floating Helper Instructions -->
                    <div class="map-badge-counter">
                        <i class="bi bi-info-circle text-cyan-300 text-sm"></i>
                        <span><b>Seret pin biru</b> untuk pindah &amp; tersimpan otomatis | Seret <b>titik putih</b> di tepi lingkaran untuk ubah radius | Klik peta untuk titik baru</span>
                    </div>
                </div>

                <!-- Map Footer & Legend -->
                <div class="px-4 py-3 bg-slate-50/80 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
                    <div class="flex items-center gap-4 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-blue-600 shadow-sm inline-block"></span> Titik Terdaftar
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-red-500 shadow-sm inline-block"></span> Titik Baru / Mode Edit
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-blue-200 border border-blue-400 inline-block"></span> Radius Geofence
                        </span>
                    </div>
                    <div class="text-slate-500 flex items-center gap-1">
                        <i class="bi bi-shield-lock text-emerald-600"></i>
                        <span>Guru hanya valid absen di dalam lingkaran radius.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Location List (Col 5 / 4) -->
        <div class="lg:col-span-5 xl:col-span-4">
            <div class="card-tw shadow-sm flex flex-col">
                <!-- List Header -->
                <div class="card-tw-header">
                    <div>
                        <h6 class="font-bold text-slate-800 text-sm md:text-base leading-tight">Daftar Titik Lokasi</h6>
                        <span class="text-xs text-slate-500">Klik kartu untuk fokus ke peta</span>
                    </div>
                    <span class="badge-tw badge-primary font-bold" id="locCountBadge">
                        <?= count($locations ?? []) ?> Titik
                    </span>
                </div>

                <!-- List Search / Filter Box -->
                <div class="p-3 bg-slate-50 border-b border-slate-100">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="bi bi-funnel text-xs"></i>
                        </div>
                        <input type="text" id="locFilterInput" 
                            class="form-control-tw pl-8 pr-8 text-xs py-2 w-full bg-white" 
                            placeholder="Filter nama lokasi atau keterangan..." 
                            oninput="filterLocationList(this.value)">
                        <button type="button" id="btnClearListFilter" class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs" onclick="clearListFilter()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- List Items Scroll Container -->
                <div class="location-scroll-container p-3 space-y-2.5" id="locationListContainer">
                    <?php if (empty($locations)): ?>
                        <div class="p-8 text-center" id="emptyLocNotice">
                            <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-500 mx-auto flex items-center justify-center text-2xl mb-3 shadow-inner">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <h6 class="font-bold text-slate-700 text-sm mb-1">Belum Ada Titik Lokasi</h6>
                            <p class="text-xs text-slate-400 mb-4 max-w-xs mx-auto leading-relaxed">
                                Tambahkan titik lokasi gedung sekolah agar sistem geofence presensi dapat bekerja.
                            </p>
                            <button class="btn-tw btn-tw-sm btn-primary shadow-sm" onclick="addLocation()">
                                <i class="bi bi-plus-lg mr-1"></i> Tambah Titik Pertama
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($locations as $loc): ?>
                            <div class="loc-item card-tw p-3.5 cursor-pointer bg-white border border-slate-200/80 rounded-xl hover:shadow-md" 
                                id="loc-card-<?= $loc->id ?>"
                                onclick="panTo(<?= (float)$loc->lat ?>, <?= (float)$loc->lng ?>, <?= (int)$loc->radius_meter ?>, <?= $loc->id ?>)">
                                
                                <div class="flex items-start justify-between gap-2.5">
                                    <div class="min-w-0 flex-1">
                                        <!-- Title -->
                                        <div class="font-bold text-slate-800 text-sm leading-snug loc-title flex items-center gap-1.5">
                                            <i class="bi bi-building text-blue-600 text-xs"></i>
                                            <span class="truncate"><?= esc($loc->nama_lokasi) ?></span>
                                        </div>

                                        <!-- Badges: Utama, Radius & Coordinates -->
                                        <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                            <?php if ((int) ($loc->is_utama ?? 0) === 1): ?>
                                                <span class="badge-tw text-[10px] py-0.5 px-2 font-semibold flex items-center gap-1 bg-amber-100 text-amber-800">
                                                    <i class="bi bi-star-fill text-[9px]"></i> Lokasi Utama
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge-tw badge-primary text-[10px] py-0.5 px-2 font-semibold flex items-center gap-1">
                                                <i class="bi bi-bullseye text-[9px]"></i> <?= $loc->radius_meter ?>m
                                            </span>
                                            <span class="badge-tw badge-secondary text-[10px] py-0.5 px-2 font-mono text-slate-600">
                                                <?= number_format((float)$loc->lat, 5) ?>, <?= number_format((float)$loc->lng, 5) ?>
                                            </span>
                                        </div>

                                        <!-- Description -->
                                        <?php if (!empty($loc->keterangan)): ?>
                                            <p class="text-[11px] text-slate-500 mt-1.5 line-clamp-2 loc-desc leading-relaxed">
                                                <?= esc($loc->keterangan) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Dropdown / Buttons -->
                                    <div class="flex items-center gap-1 shrink-0" onclick="event.stopPropagation()">
                                        <button type="button"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition <?= (int) ($loc->is_utama ?? 0) === 1 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 hover:bg-amber-50 text-slate-400 hover:text-amber-600' ?>"
                                            title="<?= (int) ($loc->is_utama ?? 0) === 1 ? 'Lepas penandaan lokasi utama' : 'Jadikan lokasi utama' ?>"
                                            onclick="toggleUtama(<?= (int) $loc->id ?>, <?= (int) ($loc->is_utama ?? 0) === 1 ? 0 : 1 ?>)">
                                            <i class="bi <?= (int) ($loc->is_utama ?? 0) === 1 ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        </button>
                                        <a href="https://www.google.com/maps?q=<?= $loc->lat ?>,<?= $loc->lng ?>" 
                                            target="_blank" rel="noopener noreferrer"
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 flex items-center justify-center text-xs transition" 
                                            title="Buka di Google Maps">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <button type="button" 
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 flex items-center justify-center text-xs transition" 
                                            title="Edit Titik Lokasi"
                                            onclick='editLocation(<?= json_encode($loc) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" 
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 flex items-center justify-center text-xs transition" 
                                            title="Hapus Titik Lokasi"
                                            onclick="deleteLocation(<?= $loc->id ?>, '<?= esc($loc->nama_lokasi) ?>')">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- List Footer Info -->
                <div class="p-3 bg-slate-50/80 border-t border-slate-100 text-center text-xs text-slate-400">
                    Presensi otomatis mencocokkan ke titik terdekat dalam radius.
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Modal Form Lokasi (Tambah & Edit) -->
<div id="modalLocation" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <form id="formLocation">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="id">

                <!-- Modal Header -->
                <div class="modal-header-tw border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-base leading-tight" id="modalTitle">Tambah Titik Lokasi Baru</h5>
                            <span class="text-xs text-slate-400" id="modalSubtitle">Tentukan koordinat GPS dan batas radius presensi</span>
                        </div>
                    </div>
                    <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" data-ui-dismiss="modal">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body-tw p-6 space-y-4">
                    
                    <!-- Nama Lokasi -->
                    <div>
                        <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Gedung / Lokasi <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="bi bi-building"></i>
                            </div>
                            <input type="text" class="form-control-tw pl-10 w-full" name="nama_lokasi" id="nama_lokasi" required
                                placeholder="Contoh: Gedung Utama SMAN 1 / Kampus A...">
                        </div>
                    </div>

                    <!-- Coordinates Grid -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="form-label-tw text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Koordinat GPS <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-slate-400">Geser pin merah di peta untuk mengubah</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] text-slate-500 block mb-1">Latitude (Garis Lintang)</label>
                                <input type="text" class="form-control-tw font-mono text-xs bg-slate-50" name="lat" id="lat" required readonly>
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-500 block mb-1">Longitude (Garis Bujur)</label>
                                <input type="text" class="form-control-tw font-mono text-xs bg-slate-50" name="lng" id="lng" required readonly>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-2">
                            <button type="button" class="btn-tw btn-tw-sm btn-light border text-xs py-1 px-2 text-slate-600" onclick="useCurrentCenterMap()">
                                <i class="bi bi-bullseye mr-1 text-blue-600"></i> Ambil Titik Tengah Peta
                            </button>
                            <button type="button" class="btn-tw btn-tw-sm btn-light border text-xs py-1 px-2 text-slate-600" onclick="locateCurrentGPS()">
                                <i class="bi bi-crosshair mr-1 text-emerald-600"></i> Deteksi GPS Saya
                            </button>
                        </div>
                    </div>

                    <!-- Radius Geofence Section -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="form-label-tw text-xs font-bold text-slate-700 uppercase tracking-wider block leading-tight">
                                    Radius Toleransi Presensi
                                </label>
                                <span class="text-[11px] text-slate-400">Jarak lingkaran di mana guru diperbolehkan absen</span>
                            </div>
                            <span class="badge-tw badge-primary font-bold text-xs px-2.5 py-1" id="radiusBadge">
                                50 Meter
                            </span>
                        </div>

                        <!-- Range Slider & Number Input -->
                        <div class="flex items-center gap-3">
                            <input type="range" class="radius-range-slider flex-1" id="radiusRange" 
                                min="10" max="1000" step="5" value="50" oninput="handleRadiusRange(this.value)">
                            <div class="w-24 shrink-0 relative">
                                <input type="number" class="form-control-tw text-xs py-1.5 pr-7 text-center font-bold" 
                                    name="radius_meter" id="radius_meter" value="50" min="10" max="5000" required 
                                    oninput="handleRadiusInput(this.value)">
                                <span class="absolute inset-y-0 right-2 flex items-center text-[11px] text-slate-400 pointer-events-none">m</span>
                            </div>
                        </div>

                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1.5 flex-wrap pt-1">
                            <span class="text-[11px] text-slate-400 font-medium mr-1">Preset:</span>
                            <button type="button" class="btn-tw btn-tw-sm btn-light border text-[11px] py-0.5 px-2 rounded-lg" onclick="setRadiusPreset(25)">25m</button>
                            <button type="button" class="btn-tw btn-tw-sm btn-outline-primary text-[11px] py-0.5 px-2 rounded-lg font-semibold" onclick="setRadiusPreset(50)">50m (Standar)</button>
                            <button type="button" class="btn-tw btn-tw-sm btn-light border text-[11px] py-0.5 px-2 rounded-lg" onclick="setRadiusPreset(100)">100m (Kampus)</button>
                            <button type="button" class="btn-tw btn-tw-sm btn-light border text-[11px] py-0.5 px-2 rounded-lg" onclick="setRadiusPreset(200)">200m (Luas)</button>
                            <button type="button" class="btn-tw btn-tw-sm btn-light border text-[11px] py-0.5 px-2 rounded-lg" onclick="setRadiusPreset(500)">500m (Kawasan)</button>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="form-label-tw block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Keterangan / Catatan Tambahan (Opsional)
                        </label>
                        <textarea class="form-control-tw w-full text-xs" name="keterangan" id="keterangan" rows="2"
                            placeholder="Contoh: Gedung utama, lapangan upacara, dan area parkir guru"></textarea>
                    </div>

                    <!-- Lokasi Utama -->
                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="is_utama" id="is_utama" value="1"
                                class="mt-0.5 w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                            <span>
                                <span class="block text-xs font-bold text-amber-900">Jadikan sebagai Lokasi Utama</span>
                                <span class="block text-[11px] text-amber-800/90 mt-0.5 leading-relaxed">
                                    Hanya satu lokasi yang bisa menjadi utama. Lokasi ini dipakai otomatis sebagai
                                    koordinat saat admin mengisi absen manual, karena input manual tidak membawa GPS.
                                </span>
                            </span>
                        </label>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer-tw border-t border-slate-100 px-6 py-4 bg-slate-50 flex items-center justify-end gap-2">
                    <button type="button" class="btn-tw btn-light" data-ui-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn-tw btn-primary shadow-sm" id="btnSave">
                        <i class="bi bi-save mr-1.5"></i> Simpan Titik Lokasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Panduan Geofence Presensi -->
<div id="modalGeofenceGuide" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="modal-header-tw border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                        <i class="bi bi-question-circle-fill"></i>
                    </div>
                    <div>
                        <h5 class="font-bold text-slate-800 text-base leading-tight">Panduan Geofence Presensi</h5>
                        <span class="text-xs text-slate-400">Prinsip kerja dan rekomendasi batas radius</span>
                    </div>
                </div>
                <button type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition" data-ui-dismiss="modal">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div class="modal-body-tw p-6 space-y-4 text-xs text-slate-600 leading-relaxed">
                <div class="p-3.5 bg-blue-50/70 border border-blue-200/70 rounded-xl">
                    <h6 class="font-bold text-blue-800 text-xs mb-1 flex items-center gap-1.5">
                        <i class="bi bi-shield-check"></i> Apa itu Geofence Presensi?
                    </h6>
                    <p class="text-blue-900/80">
                        Geofence adalah pembatas geografis virtual berbentuk lingkaran radius di sekitar titik koordinat kantor/sekolah. Saat GTK melakukan presensi, browser mengirimkan koordinat GPS perangkat mereka.
                    </p>
                </div>

                <div class="space-y-2">
                    <h6 class="font-bold text-slate-800 text-xs">Rekomendasi Penentuan Radius:</h6>
                    <ul class="list-disc pl-4 space-y-1.5 text-slate-600">
                        <li><strong>50 – 100 Meter (Sangat Dianjurkan)</strong>: Mengakomodasi ketidakakuratan GPS smartphone saat berada di dalam ruangan/gedung beton bertingkat (GPS drift 10–25m).</li>
                        <li><strong>25 Meter</strong>: Hanya cocok untuk titik kecil atau presensi di ruang terbuka (lapangan/pos satpam).</li>
                        <li><strong>150 – 300 Meter</strong>: Cocok untuk kompleks sekolah atau kampus luas dengan banyak fakultas atau area parkir terpisah.</li>
                    </ul>
                </div>

                <div class="p-3.5 bg-amber-50/70 border border-amber-200/70 rounded-xl">
                    <h6 class="font-bold text-amber-800 text-xs mb-1 flex items-center gap-1.5">
                        <i class="bi bi-exclamation-triangle"></i> Pengecualian Validasi:
                    </h6>
                    <p class="text-amber-900/80">
                        GTK yang ditugaskan <strong>Dinas Luar</strong> atau bekerja dalam mode <strong>WFH (Work From Home)</strong> otomatis dikecualikan dari pengecekan geofence radius ini.
                    </p>
                </div>
            </div>
            <div class="modal-footer-tw border-t border-slate-100 px-6 py-3 bg-slate-50 flex justify-end">
                <button type="button" class="btn-tw btn-primary text-xs" data-ui-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map, tempMarker, tempCircle;
    let locationLayers = {};
    let locations = <?= json_encode($locations ?? []) ?>;
    let currentMapLayerIndex = 0; // 0 = OSM, 1 = Satellite
    let osmLayer, satLayer;

    // Default coordinate (Monas Jakarta fallback)
    const defaultLat = -6.175392;
    const defaultLng = 106.827153;

    // Custom Leaflet SVG Pin Generator
    function createPinIcon(colorClass) {
        return L.divIcon({
            className: 'custom-leaflet-pin ' + colorClass,
            html: `
                <div class="pin-pulse-ring"></div>
                <div class="pin-body">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -32]
        });
    }

    const bluePinIcon = createPinIcon('pin-blue');
    const redPinIcon  = createPinIcon('pin-red');

    $(document).ready(function () {
        initMap();

        $('#formLocation').submit(function (e) {
            e.preventDefault();
            saveLocation();
        });

        // Trigger map resize on window resize
        window.addEventListener('resize', () => {
            if (map) map.invalidateSize();
        });

        // Clean up temp markers when modal is hidden
        $('#modalLocation').on('hidden.bs.modal', function () {
            // If we're closing and not saving, remove temporary edit marker
            if (tempMarker) map.removeLayer(tempMarker);
            if (tempCircle) map.removeLayer(tempCircle);
            tempMarker = null;
            tempCircle = null;
        });
    });

    function initMap() {
        // Basemap: Esri World Street Map (legacy MapServer).
        // Riwayat: tile.openstreetmap.org diblokir OSM melanggar tile
        // usage policy (app embed), lalu Carto (cartocdn) kini juga
        // menyuntik watermark "API KEY REQUIRED" ke tile anonim. Jalur
        // legacy Esri masih bebas-key dan sudah diverifikasi bodoh tile.
        // Satelit bawah juga Esri (World_Imagery) — satu vendor konsisten.
        osmLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.esri.com/">Esri</a> — sumber data: Esri, Maxar, Garmin, <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });

        satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '&copy; Esri, Maxar, Earthstar Geographics'
        });

        // Initial center
        let center = [defaultLat, defaultLng];
        let zoom = 15;
        if (locations && locations.length > 0) {
            center = [parseFloat(locations[0].lat), parseFloat(locations[0].lng)];
        }

        map = L.map('map', {
            center: center,
            zoom: zoom,
            layers: [osmLayer],
            zoomControl: false // We will re-add at bottom-right or keep default
        });

        // Place zoom control at bottom-right
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Add Scale
        L.control.scale({ imperial: false, position: 'bottomright' }).addTo(map);

        // Render Existing Location Markers
        renderAllLocations();

        // Map Click Event -> Drops draggable temp marker and opens modal
        map.on('click', function (e) {
            setTempMarker(e.latlng.lat, e.latlng.lng);
            openModalAdd(e.latlng.lat, e.latlng.lng);
        });

        // If multiple locations, fit bounds
        if (locations && locations.length > 1) {
            fitAllMarkers();
        }
    }

    function toggleMapLayer() {
        if (currentMapLayerIndex === 0) {
            map.removeLayer(osmLayer);
            map.addLayer(satLayer);
            currentMapLayerIndex = 1;
            $('#btnLayerToggle').addClass('bg-blue-50 text-blue-600').attr('title', 'Tampilkan Peta Jalan (Esri)');
        } else {
            map.removeLayer(satLayer);
            map.addLayer(osmLayer);
            currentMapLayerIndex = 0;
            $('#btnLayerToggle').removeClass('bg-blue-50 text-blue-600').attr('title', 'Tampilkan Foto Satelit (Esri)');
        }
    }

    function fitAllMarkers() {
        if (locations && locations.length > 0) {
            const bounds = L.latLngBounds(locations.map(l => [parseFloat(l.lat), parseFloat(l.lng)]));
            map.fitBounds(bounds, { padding: [60, 60] });
        } else {
            map.setView([defaultLat, defaultLng], 15);
        }
    }

    // ── Realtime direct-edit helpers (drag pin & resize radius → autosave) ──
    let autoSaveTimer = {};
    function showGeoToast(text, mode) {
        const t = $('#geoSaveToast');
        if (!t.length) return;
        $('#geoSaveToastText').text(text);
        t.removeClass('success error');
        if (mode) t.addClass(mode);
        t.addClass('show');
        clearTimeout(window._geoToastTO);
        window._geoToastTO = setTimeout(() => t.removeClass('show'), mode ? 2200 : 999999);
    }
    function hideGeoToast(successText) {
        const t = $('#geoSaveToast');
        if (!t.length) return;
        if (successText) {
            $('#geoSaveToastText').text(successText);
            t.addClass('show success');
            clearTimeout(window._geoToastTO);
            window._geoToastTO = setTimeout(() => t.removeClass('show success'), 2000);
        } else {
            t.removeClass('show');
        }
    }
    function refreshPopupLatLng(id) {
        const L0 = locationLayers[id];
        if (!L0) return;
        const p = L0.marker.getLatLng();
        const r = Math.round(L0.circle.getRadius());
        const el = document.getElementById('pp-coord-' + id);
        if (el) el.textContent = p.lat.toFixed(5) + ', ' + p.lng.toFixed(5);
        const badge = document.getElementById('pp-radius-' + id);
        if (badge) badge.innerHTML = '<i class="bi bi-bullseye"></i> Radius: ' + r + 'm';
        const slider = document.getElementById('pp-range-' + id);
        if (slider && document.activeElement !== slider) slider.value = Math.min(r, 1000);
    }
    function updateLocCard(id, lat, lng, radius) {
        const card = document.getElementById('loc-card-' + id);
        if (!card) return;
        const badges = card.querySelectorAll('.badge-tw');
        if (badges[0]) badges[0].innerHTML = '<i class="bi bi-bullseye text-[9px]"></i> ' + radius + 'm';
        if (badges[1]) badges[1].textContent = Number(lat).toFixed(5) + ', ' + Number(lng).toFixed(5);
    }
    function scheduleAutoSave(id) {
        clearTimeout(autoSaveTimer[id]);
        autoSaveTimer[id] = setTimeout(() => autoSaveLocation(id), 800);
    }
    function autoSaveLocation(id) {
        const L0 = locationLayers[id];
        const loc = (locations || []).find(x => String(x.id) === String(id));
        if (!L0 || !loc) return;
        const p = L0.marker.getLatLng();
        const r = Math.round(L0.circle.getRadius());
        const lat = Number(p.lat.toFixed(8)), lng = Number(p.lng.toFixed(8));
        showGeoToast('Menyimpan ' + (loc.nama_lokasi || '') + '...');
        const payload = { id: id, nama_lokasi: loc.nama_lokasi, lat: lat, lng: lng, radius_meter: r, keterangan: loc.keterangan || '' };
        payload[CSRF_NAME] = CSRF_HASH;
        $.ajax({
            url: '<?= base_url('admin/presensi/lokasi/save') ?>',
            type: 'POST', data: payload, dataType: 'json',
            success: function (res) {
                if (res && (res.status === true || res.status === 1 || res === true)) {
                    loc.lat = lat; loc.lng = lng; loc.radius_meter = r;
                    updateLocCard(id, lat, lng, r);
                    refreshPopupLatLng(id);
                    hideGeoToast('Tersimpan ✓ ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ' • ' + r + 'm');
                } else {
                    showGeoToast(res.message || 'Gagal menyimpan', 'error');
                }
            },
            error: function () { showGeoToast('Gagal terhubung ke server', 'error'); }
        });
    }
    function onPopupRadius(id, val) {
        const L0 = locationLayers[id];
        if (!L0) return;
        let r = parseInt(val) || 50;
        r = Math.min(Math.max(r, 10), 5000);
        L0.circle.setRadius(r);
        positionResizeHandle(id);
        refreshPopupLatLng(id);
        scheduleAutoSave(id);
    }
    function positionResizeHandle(id) {
        const L0 = locationLayers[id];
        if (!L0 || !L0.resizeHandle) return;
        const c = L0.circle.getLatLng();
        const r = L0.circle.getRadius();
        // titik tepi timur lingkaran (aproksimasi meter → derajat)
        const dest = c.lng + (r / 111320) / Math.max(Math.cos(c.lat * Math.PI / 180), 0.2);
        L0.resizeHandle.setLatLng([c.lat, dest]);
    }
    function renderAllLocations() {
        // Clear previous layers
        Object.values(locationLayers).forEach(item => {
            if (item.marker) map.removeLayer(item.marker);
            if (item.circle) map.removeLayer(item.circle);
            if (item.resizeHandle) map.removeLayer(item.resizeHandle);
        });
        locationLayers = {};

        if (!locations) return;

        locations.forEach(loc => {
            const lat = parseFloat(loc.lat);
            const lng = parseFloat(loc.lng);
            const rad = parseInt(loc.radius_meter) || 50;

            // Pin BIRU bisa langsung diseret → autosave realtime
            const marker = L.marker([lat, lng], { icon: bluePinIcon, draggable: true, autoPan: true }).addTo(map);
            const circle = L.circle([lat, lng], {
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.16,
                weight: 2,
                radius: rad
            }).addTo(map);
            // Handle resize: titik putih di tepi timur lingkaran, seret untuk ubah radius
            const resizeHandle = L.marker([lat, lng], {
                icon: L.divIcon({ className: '', html: '<div class="resize-handle-pin"></div>', iconSize: [16, 16], iconAnchor: [8, 8] }),
                draggable: true, autoPan: true
            }).addTo(map);

            const gmapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

            const popupContent = `
                <div class="p-1 min-w-[230px] font-sans">
                    <div class="flex items-center gap-1.5 mb-1 text-blue-600">
                        <i class="bi bi-building font-bold"></i>
                        <h6 class="font-bold text-slate-800 text-sm m-0 leading-tight">${escapeHtml(loc.nama_lokasi)}</h6>
                    </div>
                    <div class="flex items-center gap-1.5 my-1.5">
                        <span id="pp-radius-${loc.id}" class="inline-block bg-blue-100 text-blue-800 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                            <i class="bi bi-bullseye"></i> Radius: ${rad}m
                        </span>
                    </div>
                    <div id="pp-coord-${loc.id}" class="text-[11px] text-slate-500 font-mono mb-2 bg-slate-50 p-1 rounded border border-slate-100">
                        ${lat.toFixed(5)}, ${lng.toFixed(5)}
                    </div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Geser untuk ubah radius (auto-save)</label>
                    <input id="pp-range-${loc.id}" type="range" min="10" max="1000" step="5" value="${Math.min(rad, 1000)}"
                        class="radius-range-slider w-full my-1" oninput="onPopupRadius(${loc.id}, this.value)">
                    <div class="text-[10px] text-emerald-600 font-medium mb-1 flex items-center gap-1">
                        <i class="bi bi-lightning-charge-fill"></i> Seret pin / titik putih → tersimpan otomatis
                    </div>
                    ${loc.keterangan ? `<p class="text-[11px] text-slate-600 mb-2 italic">${escapeHtml(loc.keterangan)}</p>` : ''}
                    <div class="flex items-center gap-1 pt-1 border-t border-slate-100">
                        <button type="button" class="btn-tw btn-tw-sm btn-primary text-xs py-1 px-2.5 flex-1 justify-center text-white" onclick='editLocation(${JSON.stringify(loc)})'>
                            <i class="bi bi-pencil-square mr-1"></i> Edit
                        </button>
                        <a href="${gmapsUrl}" target="_blank" class="btn-tw btn-tw-sm btn-light border text-xs py-1 px-2 text-slate-600" title="Buka di Google Maps">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <button type="button" class="btn-tw btn-tw-sm btn-danger text-xs py-1 px-2 text-white" onclick="deleteLocation(${loc.id}, '${escapeHtml(loc.nama_lokasi)}')" title="Hapus Lokasi">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;
            marker.bindPopup(popupContent, { maxWidth: 300 });
            marker.bindTooltip('Seret untuk pindahkan • tersimpan otomatis', { direction: 'top', opacity: 0.9 });

            locationLayers[loc.id] = { marker, circle, resizeHandle };
            positionResizeHandle(loc.id);

            // — Drag pin: lingkaran + handle ngikutin live, lepas → autosave —
            marker.on('drag', function (e) {
                const pos = e.target.getLatLng();
                circle.setLatLng(pos);
                positionResizeHandle(loc.id);
            });
            marker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                circle.setLatLng(pos);
                positionResizeHandle(loc.id);
                refreshPopupLatLng(loc.id);
                scheduleAutoSave(loc.id);
            });
            // — Drag handle tepi: ubah radius live, lepas → autosave —
            resizeHandle.on('drag', function (e) {
                const c = circle.getLatLng();
                const h = e.target.getLatLng();
                let r = Math.round(map.distance(c, h));
                r = Math.min(Math.max(r, 10), 5000);
                circle.setRadius(r);
                refreshPopupLatLng(loc.id);
            });
            resizeHandle.on('dragend', function () {
                positionResizeHandle(loc.id);
                refreshPopupLatLng(loc.id);
                scheduleAutoSave(loc.id);
            });
        });
    }

    function setTempMarker(lat, lng, radius) {
        if (tempMarker) map.removeLayer(tempMarker);
        if (tempCircle) map.removeLayer(tempCircle);

        radius = radius || parseInt($('#radius_meter').val() || 50);

        // Draggable pulsating red pin
        tempMarker = L.marker([lat, lng], { 
            icon: redPinIcon, 
            draggable: true 
        }).addTo(map);

        tempCircle = L.circle([lat, lng], {
            color: '#ef4444',
            fillColor: '#ef4444',
            fillOpacity: 0.22,
            weight: 2,
            radius: radius
        }).addTo(map);

        tempMarker.bindTooltip('Geser pin ini ke titik gedung', { permanent: false, direction: 'top' });

        tempMarker.on('drag', function (e) {
            const pos = e.target.getLatLng();
            $('#lat').val(pos.lat.toFixed(8));
            $('#lng').val(pos.lng.toFixed(8));
            tempCircle.setLatLng(pos);
        });
        tempMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            $('#lat').val(pos.lat.toFixed(8));
            $('#lng').val(pos.lng.toFixed(8));
            tempCircle.setLatLng(pos);
        });
    }

    function handleRadiusRange(val) {
        const rad = parseInt(val) || 50;
        $('#radius_meter').val(rad);
        updateRadiusUI(rad);
    }

    function handleRadiusInput(val) {
        const rad = parseInt(val) || 50;
        $('#radiusRange').val(Math.min(rad, 1000));
        updateRadiusUI(rad);
    }

    function updateRadiusUI(rad) {
        $('#radiusBadge').text(rad + ' Meter');
        if (tempCircle) {
            tempCircle.setRadius(rad);
        }
    }

    function setRadiusPreset(val) {
        $('#radius_meter').val(val);
        $('#radiusRange').val(Math.min(val, 1000));
        updateRadiusUI(val);
    }

    function useCurrentCenterMap() {
        const center = map.getCenter();
        $('#lat').val(center.lat.toFixed(8));
        $('#lng').val(center.lng.toFixed(8));
        setTempMarker(center.lat, center.lng);
    }

    function panTo(lat, lng, radius, id) {
        $('.loc-item').removeClass('loc-card-active');
        if (id) {
            $(`#loc-card-${id}`).addClass('loc-card-active');
        }

        map.setView([lat, lng], 18, { animate: true });

        if (locationLayers[id] && locationLayers[id].marker) {
            locationLayers[id].marker.openPopup();
        }
    }

    function addLocation() {
        const center = map.getCenter();
        openModalAdd(center.lat, center.lng);
        setTempMarker(center.lat, center.lng);
    }

    function openModalAdd(lat, lng) {
        $('#formLocation')[0].reset();
        $('#id').val('');
        // Lokasi baru tidak otomatis jadi utama; biarkan operator yang menentukan.
        $('#is_utama').prop('checked', false);
        $('#modalTitle').text('Tambah Titik Lokasi Baru');
        $('#modalSubtitle').text('Tentukan koordinat GPS dan batas radius presensi gedung');
        $('#lat').val(lat.toFixed(8));
        $('#lng').val(lng.toFixed(8));
        setRadiusPreset(50);
        $('#modalLocation').appModal('show');
    }

    function editLocation(loc) {
        $('#id').val(loc.id);
        $('#nama_lokasi').val(loc.nama_lokasi);
        $('#lat').val(loc.lat);
        $('#lng').val(loc.lng);
        $('#keterangan').val(loc.keterangan || '');
        $('#is_utama').prop('checked', parseInt(loc.is_utama || 0, 10) === 1);
        $('#modalTitle').text('Edit Lokasi Kantor');
        $('#modalSubtitle').text('Perbarui nama gedung, koordinat, atau radius geofence');

        setRadiusPreset(parseInt(loc.radius_meter) || 50);
        setTempMarker(parseFloat(loc.lat), parseFloat(loc.lng), parseInt(loc.radius_meter));
        map.setView([loc.lat, loc.lng], 18);

        $('#modalLocation').appModal('show');
    }

    // Save Location via AJAX (modal) — update peta & list tanpa reload bila edit
    function saveLocation() {
        const btn = $('#btnSave');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1.5"></span> Menyimpan...');
        const editingId = $('#id').val();

        $.ajax({
            url: '<?= base_url('admin/presensi/lokasi/save') ?>',
            type: 'POST',
            data: $('#formLocation').serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.status || res === true) {
                    $('#modalLocation').appModal('hide');
                    if (tempMarker) { map.removeLayer(tempMarker); tempMarker = null; }
                    if (tempCircle) { map.removeLayer(tempCircle); tempCircle = null; }
                    // Jika edit existing → patch locations + layer langsung, tanpa reload
                    if (editingId) {
                        const lat = parseFloat($('#lat').val()), lng = parseFloat($('#lng').val());
                        const r = parseInt($('#radius_meter').val()) || 50;
                        const nm = $('#nama_lokasi').val(), ket = $('#keterangan').val();
                        const loc = (locations || []).find(x => String(x.id) === String(editingId));
                        if (loc) { loc.lat = lat; loc.lng = lng; loc.radius_meter = r; loc.nama_lokasi = nm; loc.keterangan = ket; }
                        const L0 = locationLayers[editingId];
                        if (L0) {
                            L0.marker.setLatLng([lat, lng]);
                            L0.circle.setLatLng([lat, lng]);
                            L0.circle.setRadius(r);
                            positionResizeHandle(editingId);
                            refreshPopupLatLng(editingId);
                        } else {
                            renderAllLocations();
                        }
                        updateLocCard(editingId, lat, lng, r);
                        hideGeoToast('Tersimpan ✓ ' + nm);
                        Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: res.message || 'Data lokasi berhasil diperbarui.', timer: 1200, showConfirmButton: false });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Disimpan!',
                            text: res.message || 'Data lokasi berhasil diperbarui.',
                            timer: 1300,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }
                } else {
                    Swal.fire('Gagal Menyimpan', res.message || 'Gagal menyimpan data lokasi', 'error');
                }
            },
            error: function (xhr) {
                console.error(xhr);
                Swal.fire('Error Sistem', 'Terjadi kesalahan saat memproses data ke server.', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Delete Location via AJAX
    function toggleUtama(id, aktif) {
        $.ajax({
            url: '<?= base_url('admin/presensi/lokasi/utama') ?>',
            type: 'POST',
            data: {
                id: id,
                aktif: aktif,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: aktif ? 'Lokasi Utama' : 'Penandaan Dilepas',
                        text: res.message || (aktif ? 'Absen manual admin akan memakai lokasi ini.' : 'Lokasi utama sekarang kosong.'),
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Tidak bisa mengubah lokasi utama.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
            }
        });
    }

    function deleteLocation(id, name) {
        Swal.fire({
            title: 'Hapus Lokasi ' + (name ? `"${name}"?` : '?'),
            text: "Guru tidak akan bisa absen menggunakan geofence lokasi ini lagi.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Lokasi!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('admin/presensi/lokasi/delete') ?>',
                    type: 'POST',
                    data: {
                        id: id,
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status || res === true) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: res.message || 'Lokasi berhasil dihapus.',
                                timer: 1200,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Gagal Menghapus', res.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                    }
                });
            }
        });
    }

    // Locate Current User GPS
    function locateCurrentGPS() {
        if (!navigator.geolocation) {
            Swal.fire('Tidak Didukung', 'Browser Anda tidak mendukung deteksi lokasi GPS.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Mendeteksi Posisi GPS...',
            text: 'Mohon izinkan browser mengakses lokasi perangkat Anda.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                Swal.close();
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                map.setView([lat, lng], 18);
                setTempMarker(lat, lng);
                
                // If modal is open, fill the lat/lng
                if ($('#modalLocation').is(':visible')) {
                    $('#lat').val(lat.toFixed(8));
                    $('#lng').val(lng.toFixed(8));
                } else {
                    openModalAdd(lat, lng);
                }
            },
            (err) => {
                Swal.close();
                Swal.fire('Gagal Deteksi GPS', 'Pastikan izin lokasi telah diaktifkan di pengaturan perangkat Anda (' + err.message + ').', 'error');
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    }

    // Search Location via OpenStreetMap Nominatim
    function handleSearchKey(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchLocation();
        }
    }

    function searchLocation() {
        const query = $('#mapSearchInput').val().trim();
        if (!query) return;

        const btn = $('#btnMapSearch');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=1`)
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    map.setView([lat, lon], 17);
                    setTempMarker(lat, lon);
                    $('#btnClearSearch').removeClass('hidden');

                    // If modal is open, fill name & coordinates
                    if ($('#modalLocation').is(':visible')) {
                        $('#lat').val(lat.toFixed(8));
                        $('#lng').val(lon.toFixed(8));
                        if (!$('#nama_lokasi').val()) {
                            $('#nama_lokasi').val(data[0].display_name.split(',')[0]);
                        }
                    }
                } else {
                    Swal.fire('Tidak Ditemukan', 'Lokasi tidak ditemukan. Coba ketikkan nama tempat, jalan, atau kota yang lebih spesifik.', 'info');
                }
            })
            .catch(err => {
                console.error('Nominatim search error:', err);
                Swal.fire('Pencarian Gagal', 'Gagal mencari lokasi via layanan peta OpenStreetMap.', 'error');
            })
            .finally(() => {
                btn.prop('disabled', false).html('Cari');
            });
    }

    function clearMapSearch() {
        $('#mapSearchInput').val('');
        $('#btnClearSearch').addClass('hidden');
    }

    // Filter location list on the right sidebar
    function filterLocationList(keyword) {
        keyword = (keyword || '').toLowerCase().trim();
        let visibleCount = 0;

        if (keyword.length > 0) {
            $('#btnClearListFilter').removeClass('hidden');
        } else {
            $('#btnClearListFilter').addClass('hidden');
        }

        $('.loc-item').each(function () {
            const title = $(this).find('.loc-title').text().toLowerCase();
            const desc = $(this).find('.loc-desc').text().toLowerCase();
            if (title.includes(keyword) || desc.includes(keyword)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        $('#locCountBadge').text(visibleCount + ' Titik');
    }

    function clearListFilter() {
        $('#locFilterInput').val('');
        filterLocationList('');
    }

    function openGeofenceGuide() {
        $('#modalGeofenceGuide').appModal('show');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
<?= $this->endSection() ?>