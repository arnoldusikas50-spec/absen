<?php
$page      = uri_string();
$userRole  = session()->get('role');
$isWali    = (isset($guru) && isset($guru->id_jabatan) && $guru->id_jabatan == '4');
$logo_app  = ($setting->logo_kiri ?? null)
    ? base_url(ltrim($setting->logo_kiri, '/'))
    : base_url('assets/img/favicon.png');

// Dipakai juga oleh partial/footer dan beberapa halaman; dibungkus
// function_exists supaya tidak fatal kalau sidebar ter-render dua kali
// dalam satu request (mis. render ulang lewat FeatureTest, atau halaman
// yang menyisipkan layout utama di dalam layout lain).
if (!function_exists('isActive')) {
    function isActive(string $link, string $current): bool
    {
        $cur = trim(explode('?', $current)[0], '/');
        $lnk = trim(explode('?', $link)[0], '/');
        if ($cur === $lnk) {
            return true;
        }
        if ($lnk === 'settings' && in_array($cur, ['admin/presensi/setting', 'admin/setting', 'setting', 'settings'])) {
            return true;
        }
        if ($lnk === 'dashboard') {
            return in_array($cur, ['dashboard', 'admin', 'admin/dashboard'], true);
        }
        if (in_array($lnk, ['admin/presensi', 'presensi', 'admin', 'settings'])) {
            return $cur === $lnk;
        }
        return str_starts_with('/' . $cur . '/', '/' . $lnk . '/');
    }
}

$currUserId = (int) (session()->get('user_id') ?? 0);
$hashParam  = $currUserId > 0 ? '?h=' . \App\Controllers\Guru\PresensiSiswaQr::generateHash($currUserId) : '';

$menus = [];

if ($userRole == '1') {
    $menus = [
        [
            'header' => 'UTAMA',
            'items' => [
                ['name' => 'Dashboard',             'link' => 'dashboard',                  'icon' => 'bi-grid-1x2-fill'],
                ['name' => 'Monitoring Guru',       'link' => 'admin/presensi',             'icon' => 'bi-speedometer2'],
                ['name' => 'Monitoring Siswa',      'link' => 'admin/presensi/siswa/harian','icon' => 'bi-people-fill'],
                ['name' => 'Kalender & Libur',      'link' => 'admin/kalender',             'icon' => 'bi-calendar3'],
            ]
        ],
        [
            'header' => 'PRESENSI & KBM',
            'items' => [
                [
                    'name' => 'Presensi Guru',
                    'icon' => 'bi-person-badge-fill',
                    'sub' => [
                        ['name' => 'Absen Manual',     'link' => 'admin/presensi/manual',      'icon' => 'bi-ui-checks'],
                        ['name' => 'QR Event / Acara',     'link' => 'admin/presensi/qr',          'icon' => 'bi-qr-code-scan'],
                        ['name' => 'Shift Kerja',          'link' => 'admin/presensi/shift',       'icon' => 'bi-clock-history'],
                        ['name' => 'Cetak Kartu QR Guru',  'link' => 'admin/presensi/guru/kartu',  'icon' => 'bi-card-heading'],
                    ]
                ],
                [
                    'name' => 'Presensi Siswa',
                    'icon' => 'bi-people-fill',
                    'sub' => [
                        ['name' => 'Scanner QR',   'link' => 'scanner','icon' => 'bi-display'],
                        ['name' => 'Input Presensi Rombel','link' => 'presensisiswa',              'icon' => 'bi-person-lines-fill'],
                        ['name' => 'Jadwal BDR (Belajar dari Rumah)','link' => 'admin/bdr',          'icon' => 'bi-house-door'],
                        ['name' => 'Cetak Kartu QR Siswa', 'link' => 'admin/presensi/siswa/kartu', 'icon' => 'bi-card-heading'],
                    ]
                ],
                [
                    'name' => 'Jurnal Mengajar',
                    'icon' => 'bi-journal-check',
                    'sub' => [
                        ['name' => 'Monitoring Jurnal',    'link' => 'admin/jurnal',               'icon' => 'bi-journal-text'],
                        ['name' => 'Rekap Jurnal Guru',    'link' => 'admin/jurnal/rekap',         'icon' => 'bi-file-earmark-check'],
                    ]
                ],
                [
                    'name' => 'Piket Guru',
                    'icon' => 'bi-shield-check',
                    'sub' => [
                        ['name' => 'Jadwal Petugas Piket', 'link' => 'admin/piket',                'icon' => 'bi-calendar-range'],
                        ['name' => 'Monitoring Piket',     'link' => 'admin/piket/monitoring',     'icon' => 'bi-shield-shaded'],
                    ]
                ],
            ]
        ],
        [
            'header' => 'PERSETUJUAN & VERIFIKASI',
            'items' => [
                [
                    'name' => 'Pengajuan Guru',
                    'icon' => 'bi-envelope-paper-fill',
                    'sub' => [
                        ['name' => 'Izin / Cuti',     'link' => 'admin/presensi/izin',    'icon' => 'bi-envelope-paper-fill'],
                        ['name' => 'Dinas Luar',      'link' => 'admin/presensi/dinas',   'icon' => 'bi-briefcase-fill'],
                        ['name' => 'Lembur',          'link' => 'admin/presensi/lembur',  'icon' => 'bi-clock-fill'],
                    ]
                ],
                [
                    'name' => 'Koreksi & Verifikasi',
                    'icon' => 'bi-pencil-square',
                    'sub' => [
                        ['name' => 'Koreksi Absen',   'link' => 'admin/presensi/koreksi', 'icon' => 'bi-pencil-square'],
                        ['name' => 'Verifikasi Hadir', 'link' => 'admin/presensi/verifikasi', 'icon' => 'bi-person-check-fill'],
                        ['name' => 'Pengajuan Wajah', 'link' => 'admin/presensi/wajah',   'icon' => 'bi-camera-fill'],
                    ]
                ],
            ]
        ],
        [
            'header' => 'LAPORAN & REKAP',
            'items' => [
                ['name' => 'Rekap Bulanan Guru', 'link' => 'admin/presensi/rekap',          'icon' => 'bi-file-earmark-spreadsheet-fill'],
                ['name' => 'Rekap Semester Guru','link' => 'admin/presensi/rekap-semester', 'icon' => 'bi-file-earmark-bar-graph-fill'],
                ['name' => 'Rekap Denda',        'link' => 'admin/presensi/denda',          'icon' => 'bi-receipt-cutoff'],
                ['name' => 'Rekap Bulanan Siswa','link' => 'admin/presensi/siswa/rekap',    'icon' => 'bi-file-earmark-spreadsheet'],
            ]
        ],
        [
            'header' => 'KINERJA & DISIPLIN',
            'items' => [
                ['name' => 'Poin & Reward Guru', 'link' => 'admin/reward', 'icon' => 'bi-gift-fill'],
                ['name' => 'Poin Disiplin Siswa', 'link' => 'admin/poin-siswa', 'icon' => 'bi-exclamation-diamond-fill'],
            ]
        ],
        [
            'header' => 'SURAT & TINDAKAN',
            'items' => [
                ['name' => 'Surat Teguran Guru',         'link' => 'admin/presensi/surat-teguran',   'icon' => 'bi-envelope-exclamation'],
                ['name' => 'Surat Panggilan Orang Tua',  'link' => 'admin/presensi/surat-panggilan', 'icon' => 'bi-envelope-open'],
            ]
        ],
        [
            'header' => 'DATA MASTER',
            'items' => [
                [
                    'name' => 'Data Guru & Siswa',
                    'icon' => 'bi-people-fill',
                    'sub' => [
                        ['name' => 'Data Guru & GTK',  'link' => 'dataguru',      'icon' => 'bi-person-video3'],
                        ['name' => 'Data Siswa',       'link' => 'datasiswa',     'icon' => 'bi-people-fill'],
                    ]
                ],
                [
                    'name' => 'Data Akademik',
                    'icon' => 'bi-journal-bookmark-fill',
                    'sub' => [
                        ['name' => 'Tahun Pelajaran',  'link' => 'datatahun',      'icon' => 'bi-calendar-check'],
                        ['name' => 'Jurusan',          'link' => 'datajurusan',    'icon' => 'bi-diagram-3'],
                        ['name' => 'Kelas / Rombel',   'link' => 'datakelas',      'icon' => 'bi-building'],
                        ['name' => 'Mata Pelajaran',   'link' => 'datamapel',      'icon' => 'bi-book'],
                        ['name' => 'Jadwal Pelajaran', 'link' => 'admin/jadwal',   'icon' => 'bi-calendar-week'],
                    ]
                ],
                [
                    'name' => 'Integrasi Dapodik',
                    'icon' => 'bi-cloud-arrow-down-fill',
                    'link' => 'integrasi/dapodik',
                ],
            ]
        ],
        [
            'header' => 'PENGATURAN',
            'items' => [
                [
                    'name' => 'Pengaturan Umum',
                    'icon' => 'bi-gear-fill',
                    'sub' => [
                        ['name' => 'Pengaturan Aplikasi', 'link' => 'settings',               'icon' => 'bi-gear-fill'],
                        ['name' => 'Lokasi Kantor',       'link' => 'admin/presensi/lokasi',  'icon' => 'bi-geo-alt-fill'],
                    ]
                ],
                [
                    'name' => 'Notifikasi',
                    'icon' => 'bi-bell-fill',
                    'sub' => [
                        ['name' => 'Channel per Jenis',  'link' => 'admin/presensi/notif-config',       'icon' => 'bi-sliders2'],
                        ['name' => 'Status Pengiriman',  'link' => 'admin/presensi/notifikasi-status',  'icon' => 'bi-activity'],
                        ['name' => 'Tautan Telegram',    'link' => 'admin/presensi/telegram',           'icon' => 'bi-telegram'],
                        ['name' => 'Tautan WhatsApp',    'link' => 'admin/presensi/whatsapp',           'icon' => 'bi-whatsapp'],
                    ]
                ],
                [
                    'name' => 'Manajemen Pengguna',
                    'icon' => 'bi-person-gear',
                    'link' => 'admin/users',
                ],
            ]
        ],
    ];
} elseif ($userRole == '2') {
    $menus = [
        [
            'header' => 'UTAMA',
            'items' => [
                ['name' => 'Dashboard',         'link' => 'dashboard',     'icon' => 'bi-grid-1x2-fill'],
                ['name' => 'Presensi Saya',     'link' => 'guru/absensi',  'icon' => 'bi-calendar-check-fill'],
                ['name' => 'Kartu QR Saya',     'link' => 'guru/kartu-saya','icon' => 'bi-qr-code'],
                ['name' => 'Kalender Akademik', 'link' => 'guru/kalender', 'icon' => 'bi-calendar3'],
            ]
        ],
        [
            'header' => 'KBM & JURNAL',
            'items' => [
                ['name' => 'Jurnal Mengajar',   'link' => 'guru/jurnal',          'icon' => 'bi-journal-check'],
                ['name' => 'Jadwal Mengajar',   'link' => 'guru/jadwal',          'icon' => 'bi-calendar-week'],
                [
                    'name' => 'Presensi Siswa',
                    'icon' => 'bi-people-fill',
                    'sub' => [
                        ['name' => 'Scanner QR Desktop',   'link' => 'scanner','icon' => 'bi-display'],
                        ['name' => 'Scan QR Siswa',        'link' => 'guru/presensi-siswa' . $hashParam, 'icon' => 'bi-qr-code-scan'],
                        ['name' => 'Presensi Harian',      'link' => 'presensisiswa',              'icon' => 'bi-person-lines-fill'],
                        ['name' => 'Rekap Presensi Siswa', 'link' => 'admin/presensi/siswa/rekap', 'icon' => 'bi-file-earmark-spreadsheet-fill'],
                        ['name' => 'Cetak Kartu QR',       'link' => 'guru/presensi-siswa/kartu',  'icon' => 'bi-card-heading'],
                    ]
                ],
            ]
        ],
        [
            'header' => 'PIKET & TUGAS',
            'items' => [
                [
                    'name' => 'Guru Piket',
                    'icon' => 'bi-shield-check',
                    'sub' => [
                        ['name' => 'Panel Piket',       'link' => 'guru/piket',            'icon' => 'bi-shield-fill-check'],
                        ['name' => 'Buku Tamu',         'link' => 'guru/piket/tamu',       'icon' => 'bi-person-badge'],
                        ['name' => 'Catatan Kejadian',  'link' => 'guru/piket/kejadian',   'icon' => 'bi-exclamation-triangle'],
                        ['name' => 'Pemantauan Kelas',  'link' => 'guru/piket/absensi',    'icon' => 'bi-eye-fill'],
                        ['name' => 'Laporan Piket',     'link' => 'guru/piket/laporan',    'icon' => 'bi-file-earmark-text-fill'],
                    ]
                ],
                [
                    'name' => 'Pengajuan & Lembur',
                    'icon' => 'bi-envelope-paper-fill',
                    'sub' => [
                        ['name' => 'Pengajuan Lembur',  'link' => 'guru/lembur',   'icon' => 'bi-clock-fill'],
                        ['name' => 'Izin / Cuti Guru',  'link' => 'presensi',      'icon' => 'bi-send-fill'],
                    ]
                ],
                ['name' => 'Poin & Reward',     'link' => 'guru/reward',   'icon' => 'bi-gift-fill'],
                ['name' => 'Disiplin Siswa',    'link' => 'guru/poin-siswa', 'icon' => 'bi-exclamation-diamond-fill'],
            ]
        ],
    ];

    // Kepala sekolah memantau pelaksanaan KBM seluruh guru.
    if (is_kepsek()) {
        $menus[] = [
            'header' => 'MONITORING KBM',
            'items' => [
                ['name' => 'Monitoring Jurnal', 'link' => 'admin/jurnal',       'icon' => 'bi-journal-text'],
                ['name' => 'Rekap Jurnal Guru', 'link' => 'admin/jurnal/rekap', 'icon' => 'bi-file-earmark-check'],
            ]
        ];
    }
} elseif ($userRole == '4') {
    $menus = [
        [
            'header' => 'MONITORING & PENGAWASAN',
            'items' => [
                ['name' => 'Dashboard',           'link' => 'dashboard',                  'icon' => 'bi-grid-1x2-fill'],
                ['name' => 'Presensi Guru',       'link' => 'admin/presensi',             'icon' => 'bi-speedometer2'],
                ['name' => 'Presensi Siswa',      'link' => 'admin/presensi/siswa/harian','icon' => 'bi-people-fill'],
                ['name' => 'Jurnal Mengajar Guru','link' => 'admin/jurnal',               'icon' => 'bi-journal-check'],
                ['name' => 'Piket & Tamu',        'link' => 'guru/piket/laporan',      'icon' => 'bi-shield-check'],
                ['name' => 'Rekap Presensi Guru', 'link' => 'admin/presensi/rekap',       'icon' => 'bi-file-earmark-spreadsheet-fill'],
                ['name' => 'Rekap Presensi Siswa','link' => 'admin/presensi/siswa/rekap', 'icon' => 'bi-file-earmark-spreadsheet'],
            ]
        ]
    ];
} elseif ($userRole == '3') {
    $menus = [
        [
            'header' => 'UTAMA',
            'items' => [
                ['name' => 'Presensi Saya', 'link' => 'siswa/presensi', 'icon' => 'bi-calendar-check-fill'],
            ]
        ],
    ];
}

// Hanya satu menu paling spesifik yang boleh aktif. Tanpa ini link induk
// (mis. admin/jurnal) ikut ter-highlight bersama subnya (admin/jurnal/rekap)
// karena isActive() memakai prefix match.
$menuLinks = [];
foreach ($menus as $group) {
    foreach ($group['items'] as $item) {
        if (!empty($item['sub'])) {
            foreach ($item['sub'] as $s) {
                $menuLinks[] = trim(explode('?', $s['link'])[0], '/');
            }
        } else {
            $menuLinks[] = trim(explode('?', $item['link'])[0], '/');
        }
    }
}
$activeLink = '';
foreach (array_unique($menuLinks) as $lnk) {
    if (isActive($lnk, $page) && strlen($lnk) > strlen($activeLink)) {
        $activeLink = $lnk;
    }
}

$defaultAvatar = base_url('assets/img/user.png');
$avatar        = !empty($user->foto) ? base_url(ltrim($user->foto, '/')) : $defaultAvatar;
$userName      = !empty($user->nama_lengkap) ? $user->nama_lengkap : 'User';
$userRoleName  = session()->get('role_name') ?? 'Admin';

// Badge notifikasi pending untuk menu verifikasi (khusus admin).
// Dihitung sekali per request dengan COUNT ringan + guard tableExists agar
// tidak mengganggu render bila tabel belum ada.
$badgePending = ['izin' => 0, 'dinas' => 0, 'lembur' => 0, 'koreksi' => 0, 'wajah' => 0, 'verifikasi' => 0];
if ($userRole == '1') {
    try {
        $dbBadge = \Config\Database::connect();
        if ($dbBadge->tableExists('absensi_izin')) {
            $badgePending['izin'] = (int) $dbBadge->table('absensi_izin')
                ->whereIn('status', \App\Models\LeaveModel::STATUS_PENDING)
                ->countAllResults();
        }
        if ($dbBadge->tableExists('absensi_harian')) {
            $badgePending['dinas'] = (int) $dbBadge->table('absensi_harian')->where('status_masuk', 'DINAS_PENDING')->countAllResults();
            $badgePending['verifikasi'] = (int) $dbBadge->table('absensi_harian')->where('status_masuk', 'PENDING_VERIFICATION')->countAllResults();
        }
        if ($dbBadge->tableExists('presensi_lembur')) {
            $badgePending['lembur'] = (int) $dbBadge->table('presensi_lembur')->where('status', 'PENDING')->countAllResults();
        }
        if ($dbBadge->tableExists('absensi_koreksi')) {
            $badgePending['koreksi'] = (int) $dbBadge->table('absensi_koreksi')->where('status', 'pending')->countAllResults();
        }
        if ($dbBadge->tableExists('face_registration_requests')) {
            $badgePending['wajah'] = (int) $dbBadge->table('face_registration_requests')->where('status', 'pending')->countAllResults();
        }
    } catch (\Throwable $e) {
        log_message('error', '[sidebar badge] ' . $e->getMessage());
    }
}
$badgeByLink = [
    'admin/presensi/izin'    => $badgePending['izin'],
    'admin/presensi/dinas'   => $badgePending['dinas'],
    'admin/presensi/lembur'  => $badgePending['lembur'],
    'admin/presensi/koreksi' => $badgePending['koreksi'],
    'admin/presensi/verifikasi' => $badgePending['verifikasi'],
    'admin/presensi/wajah'   => $badgePending['wajah'],
];
if (!function_exists('sidebarBadge')) {
    function sidebarBadge($count): string {
        $count = (int) $count;
        if ($count <= 0) return '';
        $label = $count > 99 ? '99+' : (string) $count;
        return '<span class="sidebar-label inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-[10px] font-bold leading-none shrink-0">' . $label . '</span>';
    }
}
?>

<!-- ===== SIDEBAR ===== -->
<div class="flex flex-col h-full">

    <!-- Brand / Logo -->
    <div class="flex items-center gap-3 h-16 px-5 shrink-0 border-b border-white/10">
        <div class="w-8 h-8 rounded-xl overflow-hidden shrink-0 ring-2 ring-white/20">
            <img src="<?= $logo_app ?>" alt="Logo" class="w-full h-full object-cover">
        </div>
        <div class="sidebar-label min-w-0">
            <p class="text-sm font-bold text-white leading-tight truncate">
                <?= esc($setting->nama_sekolah ?? 'Presensi & KBM') ?>
            </p>
            <p class="text-[10px] text-blue-300 font-medium uppercase tracking-widest">Presensi & Jurnal</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
        <?php foreach ($menus as $group): ?>
        <div>
            <p class="sidebar-label px-2 mb-1.5 text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-500 select-none">
                <?= esc($group['header']) ?>
            </p>
            <ul class="space-y-0.5">
                <?php foreach ($group['items'] as $item): ?>
                <?php if (!empty($item['sub'])): ?>
                    <?php
                    $anyActive = false;
                    $subBadgeTotal = 0;
                    foreach ($item['sub'] as $s) {
                        if (trim(explode('?', $s['link'])[0], '/') === $activeLink) { $anyActive = true; }
                        $subBadgeTotal += (int) ($badgeByLink[trim($s['link'], '/')] ?? 0);
                    }
                    ?>
                    <li>
                        <button type="button"
                            class="sidebar-treeview-btn w-full group flex items-center gap-3 px-3 py-2 rounded-lg text-sm
                                   font-medium transition-all duration-150 select-none
                                   <?= $anyActive
                                       ? 'bg-white/10 text-white'
                                       : 'text-slate-400 hover:bg-white/8 hover:text-white' ?>">
                            <i class="bi <?= esc($item['icon']) ?> text-base shrink-0 w-4 text-center
                                         <?= $anyActive ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' ?>"></i>
                            <span class="sidebar-label flex-1 text-left truncate"><?= esc($item['name']) ?></span>
                            <?= sidebarBadge($subBadgeTotal) ?>
                            <i class="bi bi-chevron-right treeview-arrow sidebar-label text-[10px] shrink-0
                                      text-slate-600 transition-transform duration-200
                                      <?= $anyActive ? 'rotate-90 text-blue-400' : '' ?>"></i>
                        </button>
                        <div class="sidebar-submenu overflow-hidden <?= $anyActive ? '' : 'hidden' ?>">
                            <ul class="mt-0.5 ml-3 pl-3.5 border-l border-white/10 space-y-0.5 py-1">
                                <?php foreach ($item['sub'] as $s):
                                    $active = trim(explode('?', $s['link'])[0], '/') === $activeLink; ?>
                                <li>
                                    <a href="<?= site_url($s['link']) ?>"
                                       class="<?= $active ? 'active-link bg-blue-500/20 text-blue-300 font-semibold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-white/8' ?> group flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-[13px] transition-all duration-150">
                                        <?php if ($active): ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.8)] shrink-0"></span>
                                        <?php else: ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600 group-hover:bg-slate-400 shrink-0 transition-colors"></span>
                                        <?php endif; ?>
                                        <span class="sidebar-label flex-1 truncate"><?= esc($s['name']) ?></span>
                                        <?= sidebarBadge($badgeByLink[trim($s['link'], '/')] ?? 0) ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php else:
                    $active = trim(explode('?', $item['link'])[0], '/') === $activeLink; ?>
                    <li>
                        <a href="<?= site_url($item['link']) ?>"
                           class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                                  transition-all duration-150 relative
                                  <?= $active
                                      ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40'
                                      : 'text-slate-400 hover:bg-white/8 hover:text-white' ?>">
                            <?php if ($active): ?>
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-blue-300 rounded-r-full"></span>
                            <?php endif; ?>
                            <i class="bi <?= esc($item['icon']) ?> text-base shrink-0 w-4 text-center
                                         <?= $active ? 'text-white' : 'text-slate-500 group-hover:text-slate-300' ?>"></i>
                            <span class="sidebar-label truncate"><?= esc($item['name']) ?></span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- User Footer -->
    <div class="shrink-0 p-3 border-t border-white/10">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-white/5 transition-colors group">
            <div class="relative shrink-0">
                <img src="<?= $avatar ?>" alt="Avatar"
                     class="w-8 h-8 rounded-full object-cover ring-2 ring-white/20">
                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-slate-900"></span>
            </div>
            <div class="sidebar-label flex-1 min-w-0">
                <p class="text-[13px] font-semibold text-white truncate leading-tight"><?= esc($userName) ?></p>
                <p class="text-[11px] text-slate-500 truncate"><?= esc($userRoleName) ?></p>
            </div>
            <button onclick="logout()" title="Logout"
                    class="sidebar-label shrink-0 w-7 h-7 flex items-center justify-center rounded-lg
                           text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                <i class="bi bi-box-arrow-right text-sm"></i>
            </button>
        </div>
    </div>

</div>
