<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Absensi Ikasmedia Application Routes
 *
 * @var RouteCollection $routes
 */

// ============================================
// MAIN / LANDING — arahkan ke /auth
// ============================================
$routes->get('/', 'Auth::index');

// QR Scanner Page (Public - for mobile scan)
$routes->get('qr/(:any)', 'Guru\Attendance::qrPage/$1');

// Health check publik untuk monitoring (tanpa auth, tanpa info sensitif)
$routes->get('healthz', 'Health::index');

// Presensi App routes (selain login — login sudah di /auth)
// Catatan: definisi top-level ini MENANG atas rute identik di grup 'presensi'
// (CI4 tidak menimpa route key yang sudah ada), jadi filter harus terpasang di sini.
$routes->get('presensi/ranking',                  'Guru\Attendance::ranking',        ['filter' => 'auth:guru']);
$routes->post('presensi/auth',                    'Guru\Attendance::loginAuth');     // publik: endpoint login
$routes->get('presensi/logout',                   'Guru\Attendance::logoutApp',      ['filter' => 'auth']);
$routes->get('presensi/google',                   'Guru\Attendance::google');        // publik: mulai alur OAuth
$routes->get('presensi/google-callback',          'Guru\Attendance::googleCallback');// publik: selesai login OAuth
$routes->get('presensi/denda-detail',             'Guru\Attendance::dendaDetail',    ['filter' => 'auth:guru']);
$routes->get('presensi/denda-ytd',                'Guru\Attendance::dendaYtd',       ['filter' => 'auth:guru,admin']);
$routes->get('presensi/rekap-denda',              'Guru\Attendance::rekapDenda',     ['filter' => 'auth:guru']);
$routes->get('presensi/download-teguran/(:num)',  'Guru\Attendance::downloadTeguran/$1', ['filter' => 'auth:guru']);

// URL login lama — semua redirect ke /auth
$routes->addRedirect('login',          'auth');
$routes->addRedirect('presensi/login', 'auth');
$routes->addRedirect('admin/login',    'auth');

// Logout alias
$routes->get('auth/logout',   'Auth::logout');
$routes->get('admin/logout',  'Auth::logout');
$routes->get('presensi/logout', 'Guru\Attendance::logoutApp'); // logout app presensi guru

$routes->addRedirect('lokasi', 'admin/presensi/lokasi');
$routes->addRedirect('denda', 'admin/presensi/denda');
$routes->addRedirect('admin/denda', 'admin/presensi/denda');
$routes->addRedirect('dinas', 'admin/presensi/dinas');
$routes->addRedirect('admin/dinas', 'admin/presensi/dinas');

// Dashboard AJAX Endpoints (Shared)
$routes->group('dashboard', ['filter' => 'auth'], function ($routes) {
    $routes->get('get_status_siswa', 'Dashboard::get_status_siswa');
    $routes->get('get_realtime_stats', 'Dashboard::get_realtime_stats');
    $routes->get('loadtoken', 'Dashboard::loadtoken');
    $routes->get('getlog', 'Dashboard::getlog');
    $routes->get('getpost', 'Dashboard::getpost');
    $routes->post('savekomentar', 'Dashboard::savekomentar');
    $routes->post('savebalasan', 'Dashboard::savebalasan');
});

// Frontend Siswa (Auth Required)
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Profile Routes

    // Absensi Routes
    $routes->get('presensi/denda-ytd', 'Guru\Attendance::dendaYtd');
});

// ============================================
// BACKEND (ADMIN/GURU/PANITIA) ROUTES
// ============================================

// Admin Panel (Auth Required) - using existing Dashboard controller for now
$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Dashboard::index'); // Will be Backend\Dashboard later
    $routes->get('dashboard', 'Dashboard::index');
    
    // Poin Guru & Reward (digabung ke /admin/reward)
    $routes->get('poinguru', function () {
        return redirect()->to('admin/reward');
    });

    // Other admin routes will use existing controllers for now

    // Absensi Guru (Backend) routes - Refactored to use Admin\PresensiAdmin
    $routes->get('absensi', 'Admin\PresensiAdmin::index');
    $routes->get('absensi/settings', 'Admin\PresensiAdmin::setting');
    $routes->post('absensi/settings/save', 'Admin\PresensiAdmin::saveSetting');
    $routes->get('absensi/rekap', 'Admin\PresensiAdmin::rekap');
    $routes->get('absensi/rekap/export', 'Admin\PresensiAdmin::exportRekap');
    $routes->get('absensi/rekap/pdf', 'Admin\PresensiAdmin::exportPdf');
    $routes->post('absensi/rekap/kirim', 'Admin\PresensiAdmin::kirimRekapBulanan');
    $routes->get('absensi/denda', 'Admin\PresensiAdmin::daftarDenda');
    $routes->get('absensi/denda/pdf', 'Admin\PresensiAdmin::exportDendaPdf');
    $routes->post('absensi/denda/lunas', 'Admin\PresensiAdmin::markLunas');
    $routes->post('absensi/denda/batal', 'Admin\PresensiAdmin::batalLunas');
    $routes->post('absensi/denda/bayar-nominal', 'Admin\PresensiAdmin::bayarDendaNominal');
    $routes->get('absensi/denda/preview-distribusi', 'Admin\PresensiAdmin::previewDistribusi');
    $routes->get('absensi/denda/laporan', 'Admin\PresensiAdmin::laporanPembayaran');
    $routes->post('absensi/denda/recalculate', 'Admin\PresensiAdmin::recalculateMonthlyDenda');
    $routes->post('presensi/denda/recalculate', 'Admin\PresensiAdmin::recalculateMonthlyDenda');

// Surat Teguran
    $routes->get('presensi/surat-teguran',              'Admin\PresensiAdmin::suratTeguran');
    $routes->post('presensi/surat-teguran/buat',         'Admin\PresensiAdmin::buatSuratTeguran');
    $routes->post('presensi/surat-teguran/batal/(:num)', 'Admin\PresensiAdmin::batalkanSuratTeguran/$1');
    $routes->post('presensi/surat-teguran/kirim/(:num)', 'Admin\PresensiAdmin::kirimSuratTeguran/$1');
    $routes->get('presensi/download-teguran/(:num)',    'Admin\PresensiAdmin::downloadTeguran/$1');
    // Surat Panggilan Orang Tua (siswa)
    $routes->get('presensi/surat-panggilan',              'Admin\PresensiAdmin::suratPanggilan');
    $routes->post('presensi/surat-panggilan/buat',          'Admin\PresensiAdmin::buatSuratPanggilan');
    $routes->post('presensi/surat-panggilan/notifikasi',    'Admin\PresensiAdmin::kirimNotifikasiPanggilan');
    $routes->post('presensi/surat-panggilan/batal/(:num)',  'Admin\PresensiAdmin::batalkanSuratPanggilan/$1');
    $routes->post('presensi/surat-panggilan/hitung-ulang', 'Admin\PresensiAdmin::hitungUlangSuratPanggilan');
    $routes->get('presensi/surat-panggilan/(:num)/pdf',     'Admin\PresensiAdmin::downloadSuratPanggilan/$1');

    $routes->get('absensi/izin', 'Admin\PresensiAdmin::pendingDinas');
    $routes->get('absensi/persetujuan', 'Admin\PresensiAdmin::pendingPermits');
    $routes->post('absensi/approve-permit/(:num)', 'Admin\PresensiAdmin::approvePermit/$1');
    $routes->post('absensi/reject-permit/(:num)', 'Admin\PresensiAdmin::rejectPermit/$1');
    $routes->get('absensi/koreksi', 'Admin\PresensiAdmin::pendingKoreksi');
    $routes->post('absensi/approve-koreksi/(:num)', 'Admin\PresensiAdmin::approveKoreksi/$1');
    $routes->post('absensi/reject-koreksi/(:num)', 'Admin\PresensiAdmin::rejectKoreksi/$1');
    $routes->post('absensi/approve-dinas/(:num)', 'Admin\PresensiAdmin::approveDinas/$1');
    $routes->post('absensi/reject-dinas/(:num)', 'Admin\PresensiAdmin::rejectDinas/$1');

    // Manual Absen
    $routes->get('absensi/manual', 'Admin\PresensiAdmin::manualAbsen');
    $routes->post('absensi/manual-checkin', 'Admin\PresensiAdmin::processManualCheckin');
    $routes->post('absensi/manual-checkout', 'Admin\PresensiAdmin::processManualCheckout');
    $routes->post('absensi/batch-manual', 'Admin\PresensiAdmin::batchManualAbsen');
    $routes->get('absensi/attendance-status', 'Admin\PresensiAdmin::getAttendanceStatus');
    $routes->get('absensi/batch-attendance-status', 'Admin\PresensiAdmin::batchAttendanceStatus');

    // Geofence location CRUD
    $routes->post('absensi/location/save', 'Admin\PresensiAdmin::saveLocation');
    $routes->post('absensi/location/delete', 'Admin\PresensiAdmin::deleteLocation');
    $routes->get('absensi/location/list', 'Admin\PresensiAdmin::locations');

    // Alias: /admin/presensi -> /admin/absensi (for JavaScript compatibility)
    $routes->get('presensi', 'Admin\PresensiAdmin::index');
    $routes->get('presensi/koreksi', 'Admin\PresensiAdmin::pendingKoreksi');
    $routes->post('presensi/approve-koreksi/(:num)', 'Admin\PresensiAdmin::approveKoreksi/$1');
    $routes->post('presensi/reject-koreksi/(:num)', 'Admin\PresensiAdmin::rejectKoreksi/$1');
    // Antrean verifikasi kehadiran (PENDING_VERIFICATION)
    $routes->get('presensi/verifikasi', 'Admin\PresensiAdmin::pendingVerifikasi');
    $routes->post('presensi/verifikasi-terima/(:num)', 'Admin\PresensiAdmin::verifyVerifikasi/$1');
    $routes->post('presensi/verifikasi-tolak/(:num)', 'Admin\PresensiAdmin::rejectVerifikasi/$1');
    $routes->post('presensi/approve-dinas/(:num)', 'Admin\PresensiAdmin::approveDinas/$1');
    $routes->post('presensi/reject-dinas/(:num)', 'Admin\PresensiAdmin::rejectDinas/$1');
    $routes->post('presensi/approve-permit/(:num)', 'Admin\PresensiAdmin::approvePermit/$1');
    $routes->post('presensi/reject-permit/(:num)', 'Admin\PresensiAdmin::rejectPermit/$1');
    
    // Alias untuk manual absen
    $routes->get('presensi/manual', 'Admin\PresensiAdmin::manualAbsen');
    $routes->post('presensi/manual-checkin', 'Admin\PresensiAdmin::processManualCheckin');
    $routes->post('presensi/manual-checkout', 'Admin\PresensiAdmin::processManualCheckout');
    $routes->post('presensi/batch-manual', 'Admin\PresensiAdmin::batchManualAbsen');
    $routes->post('presensi/manual-batch', 'Admin\PresensiAdmin::batchManualAbsen');
    $routes->post('presensi/delete-attendance', 'Admin\PresensiAdmin::deleteAttendance');
    $routes->get('presensi/attendance-status', 'Admin\PresensiAdmin::getAttendanceStatus');
    $routes->get('presensi/batch-attendance-status', 'Admin\PresensiAdmin::batchAttendanceStatus');
    $routes->post('presensi/recalculate-day', 'Admin\PresensiAdmin::recalculateDayAttendance');
    
    // Maintenance
    $routes->post('presensi/clean-photos', 'Admin\PresensiAdmin::cleanOldPhotos');

    // Kalender Akademik & Hari Libur
    $routes->get('kalender',                 'Admin\Kalender::index');
    $routes->get('kalender/get-events',      'Admin\Kalender::getEvents');
    $routes->get('kalender/get-event/(:num)','Admin\Kalender::getEvent/$1');
    $routes->post('kalender/create',          'Admin\Kalender::create');
    $routes->post('kalender/update/(:num)',   'Admin\Kalender::update/$1');
    $routes->post('kalender/delete/(:num)',   'Admin\Kalender::delete/$1');
    $routes->post('kalender/holiday/save',    'Admin\Kalender::saveHoliday');
    $routes->post('kalender/holiday/delete',  'Admin\Kalender::deleteHoliday');
    $routes->post('kalender/holiday/sync',    'Admin\Kalender::syncHolidays');

    // Jadwal Belajar Dari Rumah (BDR) per kelas/tingkat
    $routes->get('bdr',                        'Admin\PresensiBdr::index');
    $routes->post('bdr/save',                  'Admin\PresensiBdr::save');
    $routes->post('bdr/terapkan/(:num)',       'Admin\PresensiBdr::terapkan/$1');
    $routes->post('bdr/delete/(:num)',         'Admin\PresensiBdr::delete/$1');

    // Jadwal Pelajaran
    $routes->get('jadwal',                       'Admin\Jadwal::index');
    $routes->post('jadwal/get-jadwal',           'Admin\Jadwal::get_jadwal');
    $routes->post('jadwal/save-setting',         'Admin\Jadwal::save_setting');
    $routes->post('jadwal/save-mapel-slot',      'Admin\Jadwal::save_mapel_slot');
    $routes->post('jadwal/delete-mapel',         'Admin\Jadwal::delete_mapel');
    $routes->post('jadwal/delete-jadwal',        'Admin\Jadwal::delete_jadwal');
    $routes->post('jadwal/reformat',             'Admin\Jadwal::reformat_jadwal');
    $routes->post('jadwal/copy',                 'Admin\Jadwal::copy_jadwal');
    $routes->get('jadwal/export-template',       'Admin\Jadwal::export_template_jadwal');
    $routes->post('jadwal/import',               'Admin\Jadwal::import_jadwal');

    // Piket
    $routes->get('piket',                        'Admin\Piket::index');
    $routes->post('piket/save-jadwal',           'Admin\Piket::saveJadwal');
    $routes->post('piket/delete-jadwal',         'Admin\Piket::deleteJadwal');
    $routes->post('piket/generate-jadwal',       'Admin\Piket::generateJadwal');
    $routes->get('piket/monitoring',             'Admin\Piket::monitoring');

    // Lembur Admin
    $routes->get('presensi/lembur',              'Admin\Lembur::index');
    $routes->post('presensi/lembur/approve/(:num)', 'Admin\Lembur::approve/$1');
    $routes->post('presensi/lembur/reject/(:num)',  'Admin\Lembur::reject/$1');

    // Verifikasi Wajah
    $routes->get('presensi/wajah',               'Admin\PresensiAdmin::pendingWajah');
    $routes->post('presensi/wajah/approve/(:num)', 'Admin\PresensiAdmin::approveWajah/$1');
    $routes->post('presensi/wajah/reject/(:num)',  'Admin\PresensiAdmin::rejectWajah/$1');
    $routes->post('presensi/wajah/delete/(:num)',  'Admin\PresensiAdmin::deleteWajah/$1');
    $routes->post('presensi/wajah/clear-history',  'Admin\PresensiAdmin::clearWajahHistory');

    // Verifikasi Izin/Cuti — GET /admin/presensi/izin mengarah ke halaman lengkap
    $routes->get('presensi/izin',                    'Admin\PresensiAdmin::permits');
    $routes->post('presensi/izin/approve/(:num)',     'Admin\PresensiAdmin::approvePermit/$1');
    $routes->post('presensi/izin/reject/(:num)',      'Admin\PresensiAdmin::rejectPermit/$1');
    $routes->post('presensi/izin/save-manual',        'Admin\PresensiAdmin::saveManualPermit');
    $routes->post('presensi/izin/delete/(:num)',      'Admin\PresensiAdmin::deletePermit/$1');
    $routes->get('presensi/izin/pending',             'Admin\PresensiAdmin::pendingPermits');  // legacy

    // Verifikasi Dinas Luar
    $routes->get('presensi/dinas',               'Admin\PresensiAdmin::pendingDinas');

    // QR Code Event
    $routes->get('presensi/qr',                  'Admin\PresensiAdmin::qrList');
    $routes->post('presensi/qr/generate',        'Admin\PresensiAdmin::qrGenerate');
    $routes->post('presensi/qr/toggle',          'Admin\PresensiAdmin::qrToggle');
    $routes->post('presensi/qr/delete',          'Admin\PresensiAdmin::qrDelete');
    $routes->get('presensi/qr/token/(:num)',     'Admin\PresensiAdmin::qrCurrentToken/$1');
    $routes->get('presensi/qr/rekap/(:num)',     'Admin\PresensiAdmin::qrRekap/$1');

    // Rekap Semester
    $routes->get('presensi/rekap-semester',                'Admin\PresensiAdmin::rekapSemester');
    // Generate menulis ulang absensi_rekap_semester → POST agar dilindungi CSRF global.
    $routes->post('presensi/rekap-semester/generate',       'Admin\PresensiAdmin::generateRekapSemester');
    $routes->get('presensi/rekap-semester/export',         'Admin\PresensiAdmin::exportRekapSemester');
    $routes->get('presensi/rekap-semester/pdf',            'Admin\PresensiAdmin::exportRekapSemesterPdf');

    // Shift Kerja
    $routes->get('presensi/shift',               'Admin\PresensiAdmin::shiftPage');
    $routes->post('presensi/shift/save',         'Admin\PresensiAdmin::saveShift');
    $routes->post('presensi/shift/delete',       'Admin\PresensiAdmin::deleteShift');
    $routes->post('presensi/shift/assign',       'Admin\PresensiAdmin::assignShift');
    $routes->post('presensi/shift/unassign',     'Admin\PresensiAdmin::unassignShift');
    $routes->post('presensi/shift/toggle',       'Admin\PresensiAdmin::toggleShift');

    // Reward & Poin Guru (admin panel)
    $routes->get('reward',                            'Admin\Reward::index');
    $routes->post('reward/saveReward',                'Admin\Reward::saveReward');
    $routes->post('reward/deleteReward/(:num)',       'Admin\Reward::deleteReward/$1');
    $routes->post('reward/saveTier',                  'Admin\Reward::saveTier');
    $routes->post('reward/deleteTier/(:num)',         'Admin\Reward::deleteTier/$1');
    $routes->post('reward/approveRedemption/(:num)',  'Admin\Reward::approveRedemption/$1');
    $routes->post('reward/rejectRedemption/(:num)',   'Admin\Reward::rejectRedemption/$1');
    $routes->post('reward/adjustPoin',                'Admin\Reward::adjustPoin');
    $routes->get('reward/getTeacherHistory/(:num)',   'Admin\Reward::getTeacherHistory/$1');
    $routes->get('reward/getTeacherAnnual/(:num)',    'Admin\Reward::getTeacherAnnual/$1');
    $routes->post('reward/seedPoin',                  'Admin\Reward::seedPoin');
    $routes->post('reward/seedPoinAll',               'Admin\Reward::seedPoinAll');

    // Poin Disiplin Siswa
    $routes->get('poin-siswa',                        'Admin\PoinSiswa::index');
    $routes->get('poin-siswa/detail/(:num)',          'Admin\PoinSiswa::detail/$1');
    $routes->post('poin-siswa/record',                'Admin\PoinSiswa::record');
    $routes->post('poin-siswa/kategori/save',         'Admin\PoinSiswa::saveKategori');
    $routes->post('poin-siswa/kategori/delete',       'Admin\PoinSiswa::deleteKategori');
    $routes->get('poin-siswa/history/(:num)',         'Admin\PoinSiswa::getHistory/$1');
    $routes->post('poin-siswa/sync',                  'Admin\PoinSiswa::syncPresensi');

    // Backup & Restore
    $routes->get('sistem/backup',                'Admin\PresensiAdmin::backupDatabase');
    $routes->get('sistem/backup-json',           'Admin\PresensiAdmin::backupDataJson');
    $routes->post('sistem/restore-json',         'Admin\PresensiAdmin::restoreDataJson');
});

// ============================================
// AUTHENTICATION ROUTES
// ============================================
$routes->get('auth',  'Auth::index');                     // Halaman login utama
$routes->group('auth', function ($routes) {
    $routes->get('/',           'Auth::index');
    $routes->get('siswa',       'Auth::siswa');           // → redirect ke /auth?siswa=1
    $routes->post('login',      'Auth::login');
    $routes->get('logout',      'Auth::logout');
    $routes->post('logout',     'Auth::logout');
});

// ============================================
// DASHBOARD ROUTES
// ============================================
$routes->group('dashboard', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('get_realtime_stats', 'Dashboard::get_realtime_stats');
    $routes->get('get_status_siswa', 'Dashboard::get_status_siswa');
    $routes->get('getlog', 'Dashboard::getlog');
    $routes->get('checkTokenJadwal', 'Dashboard::checkTokenJadwal');
    $routes->get('loadtoken', 'Dashboard::loadtoken');
    $routes->get('getpost', 'Dashboard::getpost');
    $routes->post('gantitahun', 'Dashboard::gantiTahun');
    $routes->post('gantisemester', 'Dashboard::gantiSemester');
    $routes->post('savebalasan', 'Dashboard::savebalasan');
    $routes->post('savekomentar', 'Dashboard::savekomentar');
    $routes->post('hapuspost', 'Dashboard::hapuspost');
    $routes->post('hapuskomentar', 'Dashboard::hapuskomentar');
    $routes->post('hapusbalasan', 'Dashboard::hapusbalasan');
    $routes->post('hapuslog', 'Dashboard::hapuslog');
    $routes->post('hapuslog/(:num)', 'Dashboard::deletelog/$1');
});

// ============================================
// MASTER DATA ROUTES
// ============================================
// Data Guru
$routes->group('dataguru', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Dataguru::index');
    $routes->get('data', 'Dataguru::data');
    $routes->get('add', 'Dataguru::add');
    $routes->post('do_import', 'Dataguru::do_import');
    $routes->get('downloadFormat', 'Dataguru::downloadFormat');
    $routes->get('edit/(:num)', 'Dataguru::edit/$1');
    $routes->post('create', 'Dataguru::create');
    $routes->post('save', 'Dataguru::save');
    $routes->post('delete', 'Dataguru::delete');
    $routes->post('forcedelete', 'Dataguru::forcedelete');
    $routes->get('detail/(:num)', 'Dataguru::detail/$1');
    $routes->get('update', 'Dataguru::update');
    $routes->get('downloadData', 'Dataguru::downloadData');
    $routes->post('update_all', 'Dataguru::update_all');
    $routes->post('update_foto', 'Dataguru::update_foto');

    // Additional guru features
    $routes->get('editJabatan/(:num)', 'Dataguru::editJabatan/$1');
    $routes->post('saveJabatan', 'Dataguru::saveJabatan');
    $routes->post('save_signature', 'Dataguru::save_signature');
    $routes->post('delete_ttd', 'Dataguru::delete_ttd');
    $routes->post('uploadFile/(:num)', 'Dataguru::uploadFile/$1');
    // Hapus via POST saja — GET dihapus agar tidak bisa dipicu lewat link/img (CSRF).
    $routes->post('deleteFile/(:num)', 'Dataguru::deleteFile/$1');
    $routes->post('delete_dokumen/(:num)', 'Dataguru::delete_dokumen/$1');
    $routes->post('editLogin', 'Dataguru::editLogin');
});

// Data Siswa
$routes->group('datasiswa', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Datasiswa::index');
    $routes->get('data', 'Datasiswa::data');
    $routes->post('list', 'Datasiswa::list');
    $routes->get('add', 'Datasiswa::add');
    $routes->get('add/(:num)', 'Datasiswa::add/$1');
    $routes->post('do_import', 'Datasiswa::do_import');
    $routes->post('create', 'Datasiswa::create');
    $routes->get('edit/(:num)', 'Datasiswa::edit/$1');
    $routes->post('updateData', 'Datasiswa::updateData');
    $routes->get('update', 'Datasiswa::update');
    $routes->post('delete', 'Datasiswa::delete');
    $routes->post('uploadFile/(:num)', 'Datasiswa::uploadFile/$1');
    $routes->post('delete_foto/(:num)', 'Datasiswa::delete_foto/$1');
    $routes->get('downloadData/(:num)', 'Datasiswa::downloadData/$1');
    $routes->post('update_foto', 'Datasiswa::update_foto');
    $routes->post('updateAll', 'Datasiswa::updateAll');
    $routes->post('editLogin', 'Datasiswa::editLogin');
});

// Presensi Siswa Harian (Masuk + Keluar)
$routes->group('presensisiswa', ['filter' => 'auth'], function ($routes) {
    $routes->get('/',                    'PresensiSiswa::index');
    $routes->post('getSiswa',            'PresensiSiswa::getSiswa');
    $routes->post('save',                'PresensiSiswa::save');
    $routes->post('getPresensi',         'PresensiSiswa::getPresensi');
    $routes->post('getBdr',              'PresensiSiswa::getBdr');
    $routes->post('rekapBulanan',        'PresensiSiswa::rekapBulanan');
    $routes->post('delete',              'PresensiSiswa::delete');
    $routes->post('getMonitoringHarian', 'PresensiSiswa::getMonitoringHarian');
    // Check-in / check-out per-siswa
    $routes->post('checkIn',             'PresensiSiswa::checkIn');
    $routes->post('checkOut',            'PresensiSiswa::checkOut');
    $routes->post('updateJam',           'PresensiSiswa::updateJam');
});

// Monitoring Siswa Harian (link dari sidebar admin)
$routes->get('admin/presensi/siswa/harian', 'PresensiSiswa::monitoringHarian', ['filter' => 'auth']);
$routes->get('admin/presensi/siswa/harian/pdf', 'PresensiSiswa::monitoringHarianPdf', ['filter' => 'auth']);

// Rekap Presensi Siswa Bulanan
$routes->get('admin/presensi/siswa/rekap',        'PresensiSiswa::rekapBulananView', ['filter' => 'auth']);
$routes->get('admin/presensi/siswa/rekap/export', 'PresensiSiswa::exportRekapExcel', ['filter' => 'auth']);
$routes->get('admin/presensi/siswa/rekap/pdf',    'PresensiSiswa::exportRekapPdf',   ['filter' => 'auth']);
$routes->get('admin/presensi/rekap-siswa',        'PresensiSiswa::rekapBulananView', ['filter' => 'auth']);
$routes->get('presensisiswa/rekap',               'PresensiSiswa::rekapBulananView', ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/rekap',         'PresensiSiswa::rekapBulananView', ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/rekap/export',  'PresensiSiswa::exportRekapExcel', ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/rekap/pdf',     'PresensiSiswa::exportRekapPdf',   ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/rekap/kelas',   'PresensiSiswa::kelasRekapOptions', ['filter' => 'auth']);

// Scanner QR Siswa Versi Desktop Standalone (Bisa tanpa login untuk Kiosk / Gerbang)
// URL pendek: /scanner (+ URL lama tetap aktif untuk kompatibilitas bookmark kiosk)
$routes->get('scanner',             'Guru\PresensiSiswaQr::desktop');
$routes->get('scanner/(:num)',      'Guru\PresensiSiswaQr::desktop/$1');
$routes->post('scanner/scan',       'Guru\PresensiSiswaQr::scanQr');
$routes->get('scanner/history',     'Guru\PresensiSiswaQr::getHistory');
// Pendaftaran perangkat kiosk saat token kiosk aktif (halaman kunci → POST).
$routes->post('scanner',            'Guru\PresensiSiswaQr::unlockKiosk');
$routes->post('scanner/(:num)',     'Guru\PresensiSiswaQr::unlockKiosk/$1');
$routes->get('admin/presensi/siswa/scanner',             'Guru\PresensiSiswaQr::desktop');
$routes->get('admin/presensi/siswa/scanner/(:num)',      'Guru\PresensiSiswaQr::desktop/$1');
$routes->post('admin/presensi/siswa/scanner/scan',       'Guru\PresensiSiswaQr::scanQr');
$routes->get('admin/presensi/siswa/scanner/history',     'Guru\PresensiSiswaQr::getHistory');

// Cetak Kartu Absen Siswa QR Code (Per Kelas & Individu)
$routes->get('admin/presensi/siswa/kartu', 'Admin\KartuSiswa::index', ['filter' => 'auth']);
$routes->get('admin/presensi/siswa/kartu/cetak', 'Admin\KartuSiswa::cetak', ['filter' => 'auth']);
$routes->get('admin/presensi/siswa/kartu/siswa/(:num)', 'Admin\KartuSiswa::getSiswaByKelas/$1', ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/kartu', 'Admin\KartuSiswa::index', ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/kartu/cetak', 'Admin\KartuSiswa::cetak', ['filter' => 'auth']);
$routes->get('guru/presensi-siswa/kartu/siswa/(:num)', 'Admin\KartuSiswa::getSiswaByKelas/$1', ['filter' => 'auth']);

// Cetak Kartu Absen Guru / GTK QR Code (Semua & Individu)
$routes->get('admin/presensi/guru/kartu', 'Admin\KartuGuru::index', ['filter' => 'auth']);
$routes->get('admin/presensi/guru/kartu/cetak', 'Admin\KartuGuru::cetak', ['filter' => 'auth']);
$routes->get('guru/kartu-saya', 'Admin\KartuGuru::kartuSaya', ['filter' => 'auth']);

// Data Kelas
$routes->group('datakelas', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Datakelas::index');
    $routes->get('detail/(:num)', 'Datakelas::detail/$1');
    $routes->get('add', 'Datakelas::add');
    $routes->get('edit/(:num)', 'Datakelas::edit/$1');
    $routes->post('save', 'Datakelas::save');
    $routes->post('bulk_create', 'Datakelas::bulk_create');
    // Hapus via POST saja — GET dihapus agar tidak bisa dipicu lewat link/img (CSRF).
    $routes->post('hapus/(:num)', 'Datakelas::hapus/$1');
    $routes->get('manage', 'Datakelas::manage');
    $routes->post('copyallfromsmt1', 'Datakelas::copyallfromsmt1');
    $routes->get('kenaikan', 'Datakelas::kenaikan');
    $routes->post('naikkelas', 'Datakelas::naikkelas');
    $routes->post('naikkan_semua', 'Datakelas::naikkan_semua');
});

// Data Mapel
$routes->group('datamapel', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Datamapel::index');
    $routes->post('getdatakelompok', 'Datamapel::getdatakelompok');
    $routes->post('getdatasubkelompok', 'Datamapel::getdatasubkelompok');
    $routes->post('read', 'Datamapel::read');
    $routes->post('create', 'Datamapel::create');
    $routes->post('save', 'Datamapel::save');
    $routes->post('update', 'Datamapel::update');
    $routes->post('delete', 'Datamapel::delete');
    $routes->post('addkelompokmapel', 'Datamapel::addkelompokmapel');
    $routes->post('hapuskelompok', 'Datamapel::hapuskelompok');
    $routes->get('aktifkan/(:num)', 'Datamapel::aktifkan/$1');
});


$routes->group('datatahun', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Datatahun::index');
    $routes->post('data', 'Datatahun::data');
    $routes->post('add', 'Datatahun::add');
    $routes->post('gantitahun', 'Datatahun::gantiTahun');
    $routes->post('gantisemester', 'Datatahun::gantiSemester');
    $routes->post('savehariefektif', 'Datatahun::saveHariEfektif');
    $routes->post('hapustahun', 'Datatahun::hapusTahun');
});

// ============================================
// SETTINGS ROUTES
// ============================================
$routes->group('settings', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Settings::index');
    $routes->post('/', 'Settings::index'); // Fallback for native form submission
    $routes->get('dbManager', 'Settings::dbManager');
    $routes->post('dbManager/backupdb', 'Settings::backupDb');
    $routes->post('dbManager/backupdata', 'Settings::backupData');
    $routes->post('dbManager/del_backup', 'Settings::delBackup');
    $routes->post('dbManager/hapusbackup', 'Settings::hapusBackup');
    $routes->post('dbManager/restore', 'Settings::restore');
    // Nama file lewat query string (?src=), bukan segmen akhir URL: segmen
    // terakhir yang berakhir .gz / .sql ikut tertangkap aturan file statis di
    // web server (mis. template nginx /www/wwwroot: location ~* .*\.(gz|tar|
    // txt|csv|...)$ return 404), jadi request tidak pernah sampai ke PHP.
    // Dengan bentuk ini URL berakhir di "/download" dan aman di server mana pun.
    $routes->get('dbManager/download', 'Settings::downloadBackup');
    $routes->post('uploadFile/(:segment)', 'Settings::uploadFile/$1');
    $routes->post('deleteFile', 'Settings::deleteFile');
    $routes->post('saveSetting', 'Settings::saveSetting');
    $routes->post('saveTheme', 'Settings::saveTheme');
});

// Alias for singular 'setting' to support legacy URLs
$routes->group('setting', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Settings::index');
    $routes->post('saveSetting', 'Settings::saveSetting');
    $routes->post('uploadFile/(:segment)', 'Settings::uploadFile/$1');
    $routes->post('deleteFile', 'Settings::deleteFile');
});

// Legacy URL Aliases & Redirects
$routes->addRedirect('admin/setting', 'settings');
$routes->addRedirect('admin/presensi/kalender', 'admin/kalender');
$routes->addRedirect('admin/presensi/libur', 'admin/kalender');
$routes->addRedirect('admin/libur', 'admin/kalender');
$routes->addRedirect('kalender', 'admin/kalender');

// ============================================
// UNIFIED USER MANAGEMENT (Admin + Guru + Siswa)
// ============================================
$routes->get('admin/users', 'Admin\UserManagement::index', ['filter' => 'admin']);

// ============================================
// USER MANAGEMENT ROUTES
// ============================================
$routes->group('useradmin', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Useradmin::index');
    $routes->get('data', 'Useradmin::data');
    $routes->get('edit/(:num)', 'Useradmin::edit/$1');
    $routes->post('create', 'Useradmin::create');
    $routes->post('edit_info', 'Useradmin::edit_info');
    $routes->post('edit_status', 'Useradmin::edit_status');
    $routes->post('change_password', 'Useradmin::change_password');
    // Hapus via POST saja — GET delete dihapus agar tidak bisa dipicu lewat link/CSRF
    $routes->post('delete/(:num)', 'Useradmin::delete/$1'); // For ajaxPost
    $routes->post('uploadfile/(:num)', 'Useradmin::uploadFile/$1');
    $routes->post('deletefile', 'Useradmin::deleteFile');
});

$routes->group('userguru', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Userguru::index');
    $routes->get('data', 'Userguru::data');
    $routes->get('edit/(:num)', 'Userguru::edit/$1');
    $routes->post('editlogin', 'Userguru::editlogin');
    $routes->post('activate', 'Userguru::activate');
    $routes->post('aktifkan_all', 'Userguru::aktifkan_all');
    $routes->post('aktifkansemua', 'Userguru::aktifkansemua');
    $routes->post('reset_login', 'Userguru::reset_login');
    $routes->post('reset_password', 'Userguru::reset_password');
    $routes->post('update_iduser', 'Userguru::update_iduser');
    // $routes->post('save', 'Userguru::save'); // If used?
    // $routes->post('delete', 'Userguru::delete'); // If used?
});

$routes->group('usersiswa', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Usersiswa::index');
    $routes->get('data', 'Usersiswa::data'); // JSON
    $routes->post('list', 'Usersiswa::list'); // Manual pagination
    $routes->post('activate', 'Usersiswa::activate');
    $routes->post('aktifkansemua', 'Usersiswa::aktifkansemua');
    $routes->post('reset_login', 'Usersiswa::reset_login');
    $routes->post('reset_password', 'Usersiswa::reset_password');
});

// ============================================
// MEMBER AREA ROUTES
// ============================================
$routes->group('guru', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Guru::index');
    $routes->post('updateProfil', 'Guru::updateProfil');
    // Endpoint halaman profil (dipanggil AJAX dari Views/members/guru/profil.php).
    // Semuanya self-scoped (session user) + guard inGroup('guru') di controller.
    $routes->post('updateLogin', 'Guru::updateLogin');
    $routes->post('uploadFoto', 'Guru::uploadFoto');
    $routes->post('deleteFoto', 'Guru::deleteFoto');
    $routes->post('saveSignature', 'Guru::saveSignature');
    $routes->post('deleteTtd', 'Guru::deleteTtd');
    $routes->post('deleteDokumen', 'Guru::deleteDokumen');

    // Jadwal Pelajaran (Guru View)
    $routes->group('jadwal', function ($routes) {
        $routes->get('/', 'Guru\Jadwal::index');
        $routes->get('get_jadwal/(:any)', 'Guru\Jadwal::get_jadwal/$1');
        $routes->post('pribadi/save', 'Guru\Jadwal::save_pribadi');
        $routes->post('pribadi/delete', 'Guru\Jadwal::delete_pribadi');
    });


    // Absensi Guru - View own attendance records
    $routes->group('absensi', function ($routes) {
        $routes->get('/', 'Guru\\Absensi::index');
        $routes->get('detail/(:segment)', 'Guru\\Absensi::detail/$1');
        $routes->get('export', 'Guru\\Absensi::export');
    });

    // Legacy mobile attendance routes (Guru\Attendance)
    $routes->post('absensi/checkin', 'Guru\\Attendance::checkIn');
    $routes->post('absensi/checkout', 'Guru\\Attendance::checkOut');

    // Lembur / Overtime
    $routes->group('lembur', function ($routes) {
        $routes->get('/', 'Guru\Lembur::index');
        $routes->get('create', 'Guru\Lembur::create');
        $routes->post('save', 'Guru\Lembur::save');
        $routes->post('delete/(:num)', 'Guru\Lembur::delete/$1');
    });

    // Kalender Akademik
    $routes->group('kalender', function ($routes) {
        $routes->get('/', 'Guru\Kalender::index');
        $routes->get('get-events', 'Guru\Kalender::getEvents');
    });

    // Reward / Poin
    $routes->group('reward', function ($routes) {
        $routes->get('/', 'Guru\Reward::index');
        $routes->post('redeem', 'Guru\Reward::redeem');
        $routes->post('apply-voucher', 'Guru\Reward::applyVoucher');
        $routes->post('potong-denda', 'Guru\Reward::potongDenda');
        $routes->get('denda-info', 'Guru\Reward::dendaInfo');
        $routes->get('leaderboard', 'Guru\Reward::leaderboard');
        $routes->get('monthly-report', 'Guru\Reward::monthlyReport');
        $routes->get('monthly-report/(:num)', 'Guru\Reward::monthlyReport/$1');
        $routes->get('monthly-report/(:num)/(:num)', 'Guru\Reward::monthlyReport/$1/$2');
    });

    // Poin Disiplin Siswa (catat guru piket)
    $routes->group('poin-siswa', function ($routes) {
        $routes->get('/', 'Guru\PoinSiswa::index');
        $routes->post('record', 'Guru\PoinSiswa::record');
        $routes->get('history/(:num)', 'Guru\PoinSiswa::history/$1');
    });
});

$routes->group('presensi', ['filter' => 'auth:guru'], function ($routes) {
    $routes->get('/', 'Guru\\Attendance::mobile');
    $routes->get('history', 'Guru\\Attendance::history');
    $routes->get('denda-detail', 'Guru\\Attendance::dendaDetail');
    $routes->get('profile', 'Guru\\Attendance::profile');
    $routes->post('update-profile', 'Guru\\Attendance::updateProfile');
    $routes->post('upload-foto', 'Guru\\Attendance::uploadFoto');
    $routes->post('update-login', 'Guru\\Attendance::updateLogin');
    $routes->get('log', 'Guru\\Attendance::logPage');
    $routes->get('log/pdf', 'Guru\\Attendance::logPdf');
    // Rekap PDF bulanan (dipanggil dari modal "Rekap PDF" halaman home mobile)
    $routes->get('rekap-guru/pdf', 'Guru\\Attendance::rekapGuruPdf');
    $routes->get('notif', 'Guru\\Attendance::notifPage');
    $routes->get('get-notifications', 'Guru\\Attendance::getNotifications');
    $routes->post('telegram-link', 'Guru\\Attendance::telegramLink');
    $routes->post('telegram-unlink', 'Guru\\Attendance::telegramUnlink');
    $routes->post('telegram-set-id', 'Guru\\Attendance::telegramSetId');
    $routes->post('telegram-test', 'Guru\\Attendance::telegramTest');
    $routes->get('telegram-prefs', 'Guru\\Attendance::telegramPrefs');
    $routes->post('telegram-prefs', 'Guru\\Attendance::telegramPrefsSave');
    // Preferensi notifikasi DM (channel-agnostic: TG + WA)
    $routes->get('notif/prefs', 'Guru\\Attendance::getNotifPrefs');
    $routes->post('notif/prefs/save', 'Guru\\Attendance::saveNotifPrefs');
    // Token CSRF segar untuk halaman mobile yang lama terbuka (retry 403)
    $routes->get('csrf-refresh', 'Guru\\Attendance::csrfRefresh');
    // WhatsApp personal via gateway OpenWA
    $routes->post('wa-link', 'Guru\\Attendance::waLink');
    $routes->post('wa-test', 'Guru\\Attendance::waTest');
    $routes->get('kepsek/dinas', 'Guru\\Attendance::kepsekDinasPage');
    $routes->post('kepsek/dinas/approve/(:num)', 'Guru\\Attendance::kepsekApproveDinas/$1');
    $routes->post('kepsek/dinas/reject/(:num)', 'Guru\\Attendance::kepsekRejectDinas/$1');
    $routes->get('kepsek/izin', 'Guru\\Attendance::kepsekPermitsPage');
    $routes->post('kepsek/izin/approve/(:num)', 'Guru\\Attendance::kepsekApprovePermit/$1');
    $routes->post('kepsek/izin/reject/(:num)', 'Guru\\Attendance::kepsekRejectPermit/$1');
    $routes->get('kepsek/teguran', 'Guru\\Attendance::kepsekTeguranPage');
    $routes->get('kepsek/teguran/download/(:num)', 'Guru\\Attendance::kepsekDownloadTeguran/$1');
    $routes->post('izin', 'Guru\\Attendance::leaveRequest');
    $routes->get('izin-kuota', 'Guru\\Attendance::leaveQuota');
    $routes->get('pending-leave-count', 'Guru\\Attendance::getPendingLeaveCount');
    $routes->post('koreksi', 'Guru\\Attendance::correctionRequest');
    $routes->get('my-corrections', 'Guru\\Attendance::myCorrections');
    $routes->get('my-attendance', 'Guru\\Attendance::myAttendance');
    $routes->post('check-in', 'Guru\\Attendance::checkIn');
    $routes->post('check-out', 'Guru\\Attendance::checkOut');
    $routes->post('register-face', 'Guru\\Attendance::registerFace');
    $routes->get('face-status', 'Guru\\Attendance::getFaceRegistrationStatus');
    $routes->get('wfh-quota', 'Guru\\Attendance::getWfhQuota');
    $routes->post('qr-checkin', 'Guru\\Attendance::qrCheckin');
    $routes->post('qr-checkout', 'Guru\\Attendance::qrCheckout');
    $routes->get('qr-events', 'Guru\\Attendance::getActiveQrEvents');
    $routes->get('download-teguran/(:num)', 'Guru\\Attendance::downloadTeguran/$1');
    $routes->get('surat/(:num)', 'Guru\\Attendance::downloadTeguranSurat/$1');
    $routes->get('surat-saya', 'Guru\\Attendance::myTeguran');
    $routes->post('surat-ack', 'Guru\\Attendance::ackTeguran');

});

// Admin Presensi Routes
$routes->group('admin/presensi', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Admin\\PresensiAdmin::index');
    $routes->get('dashboard', 'Admin\\PresensiAdmin::dashboard');
    $routes->get('dinas', 'Admin\\PresensiAdmin::pendingDinas');
    $routes->post('approve-dinas/(:num)', 'Admin\\PresensiAdmin::approveDinas/$1');
    $routes->post('reject-dinas/(:num)', 'Admin\\PresensiAdmin::rejectDinas/$1');
    $routes->get('koreksi', 'Admin\\PresensiAdmin::pendingKoreksi');
    $routes->post('approve-koreksi/(:num)', 'Admin\\PresensiAdmin::approveKoreksi/$1');
    $routes->post('reject-koreksi/(:num)', 'Admin\\PresensiAdmin::rejectKoreksi/$1');
    $routes->get('rekap', 'Admin\PresensiAdmin::rekap');
    $routes->get('rekap/export', 'Admin\PresensiAdmin::exportRekap');
    $routes->get('rekap/pdf', 'Admin\PresensiAdmin::exportPdf');
    $routes->post('rekap/kirim', 'Admin\PresensiAdmin::kirimRekapBulanan');
    $routes->get('siswa/rekap', 'PresensiSiswa::rekapBulananView');
    $routes->get('siswa/rekap/export', 'PresensiSiswa::exportRekapExcel');
    $routes->get('siswa/rekap/pdf', 'PresensiSiswa::exportRekapPdf');

    // Setting
    $routes->get('setting', 'Admin\\PresensiAdmin::setting');
    $routes->post('setting/save', 'Admin\\PresensiAdmin::saveSetting');
    $routes->post('recalculate-day', 'Admin\\PresensiAdmin::recalculateDayAttendance');

    // Lokasi
    $routes->get('lokasi', 'Admin\\PresensiAdmin::locations');
    $routes->post('lokasi/save', 'Admin\\PresensiAdmin::saveLocation');
    $routes->post('lokasi/utama', 'Admin\\PresensiAdmin::setLocationUtama');
    $routes->post('lokasi/delete', 'Admin\\PresensiAdmin::deleteLocation');

    // Izin & Libur
    $routes->get('izin', 'Admin\\PresensiAdmin::permits');
    $routes->post('izin/approve/(:num)', 'Admin\\PresensiAdmin::approvePermit/$1');
    $routes->post('izin/reject/(:num)', 'Admin\\PresensiAdmin::rejectPermit/$1');
    $routes->post('izin/save-manual', 'Admin\\PresensiAdmin::saveManualPermit');
    $routes->post('izin/delete/(:num)', 'Admin\\PresensiAdmin::deletePermit/$1');

    $routes->get('libur', 'Admin\\PresensiAdmin::holidays');
    $routes->post('libur/save', 'Admin\\PresensiAdmin::saveHoliday');
    $routes->post('libur/delete', 'Admin\\PresensiAdmin::deleteHoliday');
    $routes->post('libur/sync', 'Admin\\PresensiAdmin::syncHolidays');

    // Manual & Backup
    $routes->get('manual', 'Admin\\PresensiAdmin::manualAbsen');
    $routes->post('manual-checkin', 'Admin\\PresensiAdmin::processManualCheckin');
    $routes->post('manual-checkout', 'Admin\\PresensiAdmin::processManualCheckout');
    $routes->post('manual-batch', 'Admin\\PresensiAdmin::batchManualAbsen');
    $routes->get('attendance-status', 'Admin\\PresensiAdmin::getAttendanceStatus');
    $routes->get('batch-attendance-status', 'Admin\\PresensiAdmin::batchAttendanceStatus');
    $routes->get('backup', 'Admin\\PresensiAdmin::backupDatabase');
    $routes->get('backup-json', 'Admin\\PresensiAdmin::backupDataJson');
    $routes->post('restore', 'Admin\\PresensiAdmin::restoreDataJson');
    // Denda Management
    $routes->get('denda', 'Admin\\PresensiAdmin::daftarDenda');
    $routes->post('denda/mark-lunas', 'Admin\\PresensiAdmin::markLunas');
    $routes->post('denda/batal-lunas', 'Admin\\PresensiAdmin::batalLunas');
    $routes->post('denda/bayar-nominal', 'Admin\\PresensiAdmin::bayarDendaNominal');
    $routes->get('denda/preview-distribusi', 'Admin\PresensiAdmin::previewDistribusi');
    $routes->get('denda/laporan', 'Admin\PresensiAdmin::laporanPembayaran');
    $routes->get('denda/laporan/pdf', 'Admin\PresensiAdmin::exportLaporanPdf');
    $routes->get('denda/pdf', 'Admin\\PresensiAdmin::exportDendaPdf');
    $routes->post('denda/kirim-rekap', 'Admin\\PresensiAdmin::kirimRekapDenda');
    $routes->post('rekap/kirim', 'Admin\\PresensiAdmin::kirimRekapBulanan');
    $routes->post('denda/recalculate', 'Admin\\PresensiAdmin::recalculateMonthlyDenda');
    // Test Notification
    $routes->post('test-notification', 'Admin\\PresensiAdmin::testNotification');
    // Tautan Telegram guru (notifikasi personal)
    $routes->get('telegram', 'Admin\\PresensiAdmin::telegramLinks');
    $routes->post('telegram/unlink', 'Admin\\PresensiAdmin::telegramUnlinkUser');
    $routes->post('telegram/test', 'Admin\\PresensiAdmin::telegramTestUser');
    // Tautan WhatsApp guru via gateway OpenWA
    $routes->get('whatsapp', 'Admin\\PresensiAdmin::waLinks');
    $routes->post('whatsapp/verify', 'Admin\\PresensiAdmin::waVerifyUser');
    // Papan kondisi notifikasi (gagal kirim cron + DM guru tercepat)
    $routes->get('notifikasi-status', 'Admin\\PresensiAdmin::notifikasiStatus');
    // Konfigurasi channel notifikasi per jenis (TG Grup / WA Grup / TG Personal / WA Personal)
    $routes->get('notif-config', 'Admin\\PresensiAdmin::notifConfig');
    $routes->post('notif-config/save', 'Admin\\PresensiAdmin::saveNotifConfig');
    // QR Code Management
    $routes->get('qr', 'Admin\\PresensiAdmin::qrList');
    $routes->post('qr/generate', 'Admin\\PresensiAdmin::qrGenerate');
    $routes->post('qr/toggle', 'Admin\\PresensiAdmin::qrToggle');
    $routes->post('qr/delete', 'Admin\\PresensiAdmin::qrDelete');
    $routes->get('qr/rekap/(:num)', 'Admin\\PresensiAdmin::qrRekap/$1');
    $routes->get('qr/token/(:num)', 'Admin\\PresensiAdmin::qrCurrentToken/$1');
    // Shift Kerja
    $routes->get('shift', 'Admin\\PresensiAdmin::shiftPage');
    $routes->post('shift/save', 'Admin\\PresensiAdmin::saveShift');
    $routes->post('shift/delete', 'Admin\\PresensiAdmin::deleteShift');
    $routes->post('shift/toggle', 'Admin\\PresensiAdmin::toggleShift');
    $routes->post('shift/assign', 'Admin\\PresensiAdmin::assignShift');
    $routes->post('shift/unassign', 'Admin\\PresensiAdmin::unassignShift');

    // Lembur
    $routes->group('lembur', function ($routes) {
        $routes->get('/', 'Admin\Lembur::index');
        $routes->post('approve/(:num)', 'Admin\Lembur::approve/$1');
        $routes->post('reject/(:num)', 'Admin\Lembur::reject/$1');
    });

});

// ============================================
// CRON / SCHEDULER ROUTES
// ============================================
$routes->get('cron/daily', 'CronController::dailyProcess');
$routes->get('cron/mark-alpha', 'CronController::markAlpha');
$routes->get('cron/poin-siswa', 'CronController::poinSiswa');
$routes->get('cron/rekap-piket', 'CronController::rekapPiket');
$routes->get('cron/semester-recap', 'CronController::semesterRecap');
$routes->get('cron/weekly-denda', 'CronController::weeklyDendaRecap');
$routes->get('cron/monthly-pdf', 'CronController::monthlyPdf');
$routes->get('cron/ucapan-pagi', 'CronController::ucapanPagi');
$routes->get('cron/ucapan-sore', 'CronController::ucapanSore');
$routes->get('cron/apel-kesadaran', 'CronController::apelKesadaran');

$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    // Jadwal Pelajaran (E-Learning)
    $routes->group('jadwal', function ($routes) {
        $routes->get('/', 'Admin\Jadwal::index');
        $routes->get('get_jadwal/(:any)', 'Admin\Jadwal::get_jadwal/$1');
        $routes->post('save_setting', 'Admin\Jadwal::save_setting');
        $routes->post('save_mapel_slot', 'Admin\Jadwal::save_mapel_slot');
        $routes->post('delete_mapel', 'Admin\Jadwal::delete_mapel');
        $routes->post('delete_jadwal', 'Admin\Jadwal::delete_jadwal');
        $routes->post('reformat_jadwal', 'Admin\Jadwal::reformat_jadwal');
        $routes->post('copy_jadwal', 'Admin\Jadwal::copy_jadwal');
    });

    // Manual Attendance
    $routes->get('manual', 'Admin\\PresensiAdmin::manualAbsen');
    $routes->post('manual-checkin', 'Admin\\PresensiAdmin::processManualCheckin');
    $routes->post('manual-checkout', 'Admin\\PresensiAdmin::processManualCheckout');
    $routes->post('manual-batch', 'Admin\\PresensiAdmin::batchManualAbsen');
    $routes->get('attendance-status', 'Admin\\PresensiAdmin::getAttendanceStatus');
    $routes->get('batch-attendance-status', 'Admin\\PresensiAdmin::batchAttendanceStatus');
    $routes->get('setting', 'Admin\\PresensiAdmin::setting');
    $routes->post('setting/save', 'Admin\\PresensiAdmin::saveSetting');
    $routes->get('rekap/export', 'Admin\\PresensiAdmin::exportRekap');
    $routes->get('backup', 'Admin\\PresensiAdmin::backupDatabase');
    $routes->get('backup-json', 'Admin\\PresensiAdmin::backupDataJson');
    $routes->post('restore', 'Admin\\PresensiAdmin::restoreDataJson');

    // Lokasi (Geofence)
    $routes->get('lokasi', 'Admin\\PresensiAdmin::locations');
    $routes->post('lokasi/save', 'Admin\\PresensiAdmin::saveLocation');
    $routes->post('lokasi/utama', 'Admin\\PresensiAdmin::setLocationUtama');
    $routes->post('lokasi/delete', 'Admin\\PresensiAdmin::deleteLocation');
    // Permits (Izin/Cuti)
    $routes->get('izin', 'Admin\\PresensiAdmin::permits');
    $routes->post('izin/approve/(:num)', 'Admin\\PresensiAdmin::approvePermit/$1');
    $routes->post('izin/reject/(:num)', 'Admin\\PresensiAdmin::rejectPermit/$1');

    // Holidays (Hari Libur)
    $routes->get('libur', 'Admin\\PresensiAdmin::holidays');
    $routes->post('libur/save', 'Admin\\PresensiAdmin::saveHoliday');
    $routes->post('libur/delete', 'Admin\\PresensiAdmin::deleteHoliday');
    $routes->post('libur/sync', 'Admin\\PresensiAdmin::syncHolidays');

    // Manajemen Piket (Admin)
    $routes->group('piket', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'Admin\Piket::index');
        $routes->post('save_jadwal', 'Admin\Piket::saveJadwal');
        $routes->post('delete_jadwal', 'Admin\Piket::deleteJadwal');
        $routes->post('generate_jadwal', 'Admin\Piket::generateJadwal');
        $routes->post('generate-jadwal', 'Admin\Piket::generateJadwal');
        $routes->get('monitoring', 'Admin\Piket::monitoring');
        $routes->post('selaras-divergensi', 'Admin\Piket::selarasDivergensi');
    });

});


// ============================================
// PROFIL SEKOLAH (School Profile) ROUTES
// ============================================
$routes->group('profilsekolah', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Profilsekolah::index');
    $routes->post('uploadFile/(:segment)', 'Profilsekolah::uploadFile/$1');
    $routes->post('deleteFile', 'Profilsekolah::deleteFile');
    $routes->post('saveSetting', 'Profilsekolah::saveSetting');
});

// Static Assets Fallback for Local Development issues
$routes->get('assets/(:any)', function ($path) {
    $notFound = static function (): void {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    };

    $root = realpath(FCPATH . 'assets');
    if ($root === false) {
        $notFound();
    }

    // $path datang dari URL dan bisa berisi '..' maupun backslash; di Windows
    // '\' ikut diselesaikan filesystem, jadi FCPATH.'assets/'.$path bisa keluar
    // document root (mis. ..\..\.env). Batas amannya realpath, bukan is_file().
    $rel = ltrim(str_replace('\\', '/', (string) $path), '/');
    if ($rel === '' || str_contains($rel, "\0")) {
        $notFound();
    }

    $file = realpath($root . '/' . $rel);
    if ($file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) {
        $notFound();
    }

    $mime = mime_content_type($file);
    header('Content-Type: ' . ($mime === false ? 'application/octet-stream' : $mime));
    header('Content-Length: ' . (string) filesize($file));
    readfile($file);
    exit;
});

// ============================================
// MASTER JURUSAN ROUTES
// ============================================
$routes->group('datajurusan', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Datajurusan::index');
    $routes->match(['get', 'post'], 'data', 'Datajurusan::data');
    $routes->post('save', 'Datajurusan::save');
    $routes->post('update', 'Datajurusan::update');
    $routes->post('delete', 'Datajurusan::delete');
    $routes->get('get_data/(:num)', 'Datajurusan::get_data/$1');
});

// ============================================
// GURU PRESENSI SISWA QR CODE (Khusus Admin & Guru Piket)
// ============================================
$routes->group('guru/presensi-siswa', ['filter' => 'auth'], function ($routes) {
    $routes->get('/',                 'Guru\PresensiSiswaQr::index');
    $routes->get('kelas/(:num)',      'Guru\PresensiSiswaQr::index/$1');
    $routes->post('scan',             'Guru\PresensiSiswaQr::scanQr');
    $routes->get('history',           'Guru\PresensiSiswaQr::getHistory');
});

// Alias presensi siswa
$routes->group('presensi-siswa', ['filter' => 'auth'], function ($routes) {
    $routes->get('/',                 'Guru\PresensiSiswaQr::index');
    $routes->get('kelas/(:num)',      'Guru\PresensiSiswaQr::index/$1');
    $routes->post('scan',             'Guru\PresensiSiswaQr::scanQr');
    $routes->get('history',           'Guru\PresensiSiswaQr::getHistory');
});

// ============================================
// SISWA AREA (role siswa — login aktif kembali)
// ============================================
$routes->group('siswa', ['filter' => 'auth'], function ($routes) {
    $routes->get('presensi', 'Siswa\Presensi::index');
    $routes->get('presensi/history', 'Siswa\Presensi::history');
});

// ============================================
// GURU PIKET (Teacher Duty) ROUTES
// ============================================
$routes->group('guru/piket', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Guru\Piket::index');
    $routes->get('tamu', 'Guru\Piket::tamu');
    $routes->post('save_tamu', 'Guru\Piket::saveTamu');
    $routes->post('delete_tamu', 'Guru\Piket::deleteTamu');
    $routes->post('tamu_keluar', 'Guru\Piket::updateTamuKeluar');
    $routes->get('kejadian', 'Guru\Piket::kejadian');
    $routes->post('save_kejadian', 'Guru\Piket::saveKejadian');
    $routes->post('delete_kejadian', 'Guru\Piket::deleteKejadian');
    $routes->post('update_kejadian_status', 'Guru\Piket::updateKejadianStatus');
    $routes->get('absensi', 'Guru\Piket::absensi');
    $routes->post('save_absensi', 'Guru\Piket::saveAbsensi');
    $routes->post('save_jam', 'Guru\Piket::saveJam');
    $routes->post('selaras_divergensi', 'Guru\Piket::selarasDivergensi');
    $routes->get('laporan', 'Guru\Piket::laporan');
    $routes->get('laporan/jurnal/(:num)', 'Guru\Piket::jurnalDetail/$1');
    $routes->get('laporan/jurnal/(:num)/pdf', 'Guru\Piket::jurnalPdf/$1');
    $routes->get('laporan/pdf', 'Guru\Piket::laporanPdf');
    $routes->post('laporan/autofill', 'Guru\Piket::autofillPresensiGuru');
    $routes->post('kirim_rekap', 'Guru\Piket::kirimRekap');
    $routes->get('input_laporan', 'Guru\Piket::inputLaporan');
    $routes->post('save_laporan_guru', 'Guru\Piket::saveLaporanGuru');
    $routes->post('validasi_jurnal', 'Guru\Piket::validasiJurnal');
});

// ============================================
// GURU JURNAL (Teacher Journal) ROUTES
// ============================================
$routes->group('guru/jurnal', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Guru\Jurnal::index');
    $routes->get('create', 'Guru\Jurnal::form');
    $routes->get('edit/(:num)', 'Guru\Jurnal::form/$1');
    $routes->post('save', 'Guru\Jurnal::save');
    $routes->get('siswa/(:num)', 'Guru\Jurnal::siswa/$1');
    $routes->get('detail/(:num)', 'Guru\Jurnal::detail/$1');
    // Hapus via POST saja — GET dihapus agar tidak bisa dipicu lewat link/img (CSRF).
    $routes->post('delete/(:num)', 'Guru\Jurnal::delete/$1');
    $routes->get('cetak', 'Guru\Jurnal::cetak');
    $routes->get('cetak/(:num)', 'Guru\Jurnal::cetak/$1');
});

// Admin, Kepala Sekolah & Panitia Monitoring Jurnal
$routes->group('admin/jurnal', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Admin\Jurnal::index');
    $routes->get('export', 'Admin\Jurnal::export');
    $routes->get('detail/(:num)', 'Admin\Jurnal::detail/$1');
    $routes->get('rekap', 'Admin\Jurnal::rekap');
    $routes->get('rekap/export', 'Admin\Jurnal::rekapExport');
    $routes->get('rekap/cetak', 'Admin\Jurnal::rekapCetak');
});

// ============================================
// INTEGRASI DAPODIK ROUTES
// ============================================
// Integrasi Dapodik menyentuh master_guru/master_siswa/master_kelas/master_mapel
// dan menyimpan token web service — hanya admin, bukan sekadar sudah login.
$routes->group('integrasi/dapodik', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Integrasi\Dapodik::index');
    $routes->post('saveSettings', 'Integrasi\Dapodik::saveSettings');
    $routes->post('testConnection', 'Integrasi\Dapodik::testConnection');
    $routes->post('syncApi', 'Integrasi\Dapodik::syncApi');
    // Truncate riwayat: POST, bukan GET (link iseng/prefetch/prerender tidak
    // boleh menghapus jejak audit), dan CSRF ikut bekerja karena CSRF hanya
    // menjalankan dirinya pada method non-GET.
    $routes->post('clearLogs', 'Integrasi\Dapodik::clearLogs');
});

$routes->group('admin/integrasi/dapodik', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Integrasi\Dapodik::index');
    $routes->post('saveSettings', 'Integrasi\Dapodik::saveSettings');
    $routes->post('testConnection', 'Integrasi\Dapodik::testConnection');
    $routes->post('syncApi', 'Integrasi\Dapodik::syncApi');
    $routes->post('clearLogs', 'Integrasi\Dapodik::clearLogs');
});

$routes->get('cron/dapodik_sync', 'Cron::dapodik_sync');

// ============================================
// API EKSTERNAL (auth via API key di controller)
// ============================================
$routes->group('api', function ($routes) {
    $routes->get('rekap-harian', 'Api\RekapHarian::harian');
    // Bot Telegram dua arah (secret URL anti-CSRF; api/* sudah dikecualikan)
    $routes->get('telegram/setup', 'Api\TelegramBot::setup');
    $routes->post('telegram/webhook', 'Api\TelegramBot::webhook');
    // Webhook status dari gateway WhatsApp OpenWA (secret URL; api/* sudah dikecualikan CSRF)
    $routes->get('whatsapp/setup', 'Api\WhatsappWebhook::setup');
    $routes->post('whatsapp/webhook', 'Api\WhatsappWebhook::webhook');
});



// ============================================
// HALAMAN ERROR
// ============================================
// 404 lewat controller sendiri supaya klien fetch/API dapat JSON, bukan HTML
// yang membuat resp.json() di sisi klien pecah. Tanpa override, CI4 melempar
// PageNotFoundException lagi dan selalu mencetak halaman HTML.
// Bentuk string, bukan [Class::class, 'method']: Router hanya menerima override
// berupa string atau yang is_callable, dan method non-statis tidak is_callable.
$routes->set404Override('App\Controllers\Errors::notFound');
