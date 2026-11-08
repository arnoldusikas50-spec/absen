<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="container-fluid ps-3 ps-lg-4">
        <div class="flex flex-wrap gap-4 align-items-center">
            <div class="w-full sm:w-1/2 px-2">
                <h3 class="mb-0 fw-bold text-primary"><?= $judul ?></h3>
                <p class="text-muted small mb-0"><?= $subjudul ?></p>
            </div>
            <div class="w-full sm:w-1/2 px-2 text-sm-end mt-2 mt-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb sm:float-right mb-0 bg-transparent">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content-area">
    <div class="container-fluid px-3 px-lg-4">
        
        <div class="flex flex-wrap gap-4">
            <div class="w-full md:w-2/3 px-2">
                <div class="card card-outline card-primary border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="card-title fw-bold">
                            <i class="bi bi-info-circle-fill me-2"></i> Informasi Absensi
                        </h5>
                        <div class="card-tools">
                            <a href="<?= base_url('guru/absensi') ?>" class="btn-tw btn-tw-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Nama Guru</th>
                                <td>: <?= esc($guru->nama_guru ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td>: <strong><?= date('l, d F Y', strtotime($absensi->tgl ?? '')) ?></strong></td>
                            </tr>
                            <tr>
                                <th>Jam Masuk</th>
                                <td>: 
                                    <?php if (isset($absensi->jam_masuk)): ?>
                                        <span class="badge-tw badge-info text-dark" style="font-size: 1em;"><?= $absensi->jam_masuk ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Belum absen masuk</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Jam Pulang</th>
                                <td>: 
                                    <?php if (isset($absensi->jam_pulang)): ?>
                                        <span class="badge-tw badge-secondary" style="font-size: 1em;"><?= $absensi->jam_pulang ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Belum absen pulang</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Status Masuk</th>
                                <td>: 
                                    <span class="badge-tw badge-primary" style="font-size: 1em;"><?= ucfirst($absensi->status_masuk ?? '-') ?></span>
                                    <?php if (isset($absensi->telat_menit) && $absensi->telat_menit > 0): ?>
                                        <span class="text-danger ms-2">(Telat <?= $absensi->telat_menit ?> menit)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Info Perangkat</th>
                                <td>: <small class="text-muted"><?= esc($absensi->device_info ?? '-') ?></small></td>
                            </tr>
                            <?php if (isset($absensi->lat_masuk) && isset($absensi->lng_masuk)): ?>
                            <tr>
                                <th>Koordinat Masuk</th>
                                <td>: 
                                    <a href="https://www.google.com/maps?q=<?= $absensi->lat_masuk ?>,<?= $absensi->lng_masuk ?>" 
                                       target="_blank" 
                                       class="text-decoration-none">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> 
                                        <?= $absensi->lat_masuk ?>, <?= $absensi->lng_masuk ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if (isset($absensi->lat_pulang) && isset($absensi->lng_pulang)): ?>
                            <tr>
                                <th>Koordinat Pulang</th>
                                <td>: 
                                    <a href="https://www.google.com/maps?q=<?= $absensi->lat_pulang ?>,<?= $absensi->lng_pulang ?>" 
                                       target="_blank" 
                                       class="text-decoration-none">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> 
                                        <?= $absensi->lat_pulang ?>, <?= $absensi->lng_pulang ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <!-- Map (if coordinates available) -->
                <?php if ((isset($absensi->lat_masuk) && isset($absensi->lng_masuk)) || (isset($absensi->lat_pulang) && isset($absensi->lng_pulang))): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="card-title fw-bold">
                            <i class="bi bi-map-fill me-2"></i> Peta Lokasi
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="map" style="height: 400px; width: 100%;"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="w-full md:w-1/3 px-2">
                <!-- Foto Absen Masuk -->
                <?php if (isset($absensi->foto_masuk) && !empty($absensi->foto_masuk)):
                    $fmPath = 'uploads/absensi/' . $absensi->foto_masuk;
                    $fmVer = file_exists(FCPATH . $fmPath) ? filemtime(FCPATH . $fmPath) : time();
                ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="card-title fw-bold">
                            <i class="bi bi-camera-fill me-2"></i> Foto Absen Masuk
                        </h5>
                    </div>
                    <div class="card-body text-center p-0">
                        <img src="<?= base_url($fmPath) ?>?v=<?= $fmVer ?>"
                             alt="Foto Absen Masuk"
                             class="img-fluid"
                             style="max-height: 400px; width: 100%; object-fit: cover;">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Foto Absen Pulang -->
                <?php if (isset($absensi->foto_pulang) && !empty($absensi->foto_pulang)):
                    $fpPath = 'uploads/absensi/' . $absensi->foto_pulang;
                    $fpVer = file_exists(FCPATH . $fpPath) ? filemtime(FCPATH . $fpPath) : time();
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="card-title fw-bold">
                            <i class="bi bi-camera-fill me-2"></i> Foto Absen Pulang
                        </h5>
                    </div>
                    <div class="card-body text-center p-0">
                        <img src="<?= base_url($fpPath) ?>?v=<?= $fpVer ?>"
                             alt="Foto Absen Pulang"
                             class="img-fluid"
                             style="max-height: 400px; width: 100%; object-fit: cover;">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    /* Fix leaflet z-index issue with bootstrap navbar */
    .leaflet-pane { z-index: 1 !important; }
    .leaflet-top, .leaflet-bottom { z-index: 2 !important; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php 
$hasMasuk = isset($absensi->lat_masuk) && isset($absensi->lng_masuk);
$hasPulang = isset($absensi->lat_pulang) && isset($absensi->lng_pulang);

if ($hasMasuk || $hasPulang): 
    // Determine center
    $centerLat = $hasMasuk ? $absensi->lat_masuk : $absensi->lat_pulang;
    $centerLng = $hasMasuk ? $absensi->lng_masuk : $absensi->lng_pulang;
?>
<script>
$(document).ready(function() {
    // Initialize map
    var map = L.map('map').setView([<?= $centerLat ?>, <?= $centerLng ?>], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    var markers = [];

    <?php if ($hasMasuk): ?>
    // Add marker for masuk
    var markerMasuk = L.marker([<?= $absensi->lat_masuk ?>, <?= $absensi->lng_masuk ?>]).addTo(map);
    markerMasuk.bindPopup('<b>Lokasi Absen Masuk</b><br><?= $absensi->jam_masuk ?>').openPopup();
    markers.push(markerMasuk);
    <?php endif; ?>
    
    <?php if ($hasPulang): ?>
    // Add marker for pulang
    var markerPulang = L.marker([<?= $absensi->lat_pulang ?>, <?= $absensi->lng_pulang ?>]).addTo(map);
    markerPulang.bindPopup('<b>Lokasi Absen Pulang</b><br><?= $absensi->jam_pulang ?>');
    markers.push(markerPulang);
    <?php endif; ?>

    // Fit bounds if multiple markers
    if (markers.length > 1) {
        var group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
