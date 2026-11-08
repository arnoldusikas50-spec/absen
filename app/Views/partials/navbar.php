<?php
$defaultAvatar = base_url('assets/img/user.png');
$avatar        = !empty($user->foto) ? base_url(ltrim($user->foto, '/')) : $defaultAvatar;
$userName      = !empty($user->nama_lengkap) ? $user->nama_lengkap : 'IKASMEDIA';
$userRoleName  = session()->get('role_name') ?? 'Admin';
$initials      = mb_strtoupper(mb_substr($userName, 0, 1)) . (strpos($userName, ' ') ? mb_strtoupper(mb_substr($userName, strpos($userName, ' ') + 1, 1)) : '');
?>

<div class="flex items-center h-16 px-4 lg:px-5 gap-2">

    <!-- Hamburger toggle -->
    <button id="sidebar-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar"
            class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200
                   text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors shrink-0">
        <i class="bi bi-list text-lg"></i>
    </button>

    <!-- Page breadcrumb / title area (hidden on small) -->
    <div class="hidden md:flex items-center gap-2 ml-1 min-w-0">
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5">
            <i class="bi bi-calendar3 text-blue-500 text-[11px]"></i>
            <span class="text-xs font-semibold text-slate-600">
                <?= isset($tp_active) ? esc($tp_active->tahun) : '—' ?>
            </span>
            <span class="w-px h-3 bg-slate-300 mx-0.5"></span>
            <i class="bi bi-bookmark-fill text-violet-500 text-[10px]"></i>
            <span class="text-xs font-semibold text-slate-600">
                Smt <?= isset($smt_active) ? esc($smt_active->smt) : '—' ?>
            </span>
        </div>
    </div>

    <!-- Spacer -->
    <div class="flex-1"></div>

    <!-- Live clock -->
    <div class="hidden lg:flex items-center gap-2 px-3.5 py-1.5 rounded-xl
                bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm shadow-blue-200">
        <i class="bi bi-clock text-[11px] opacity-80"></i>
        <span id="live-clock" class="font-mono text-xs font-bold min-w-[58px] text-center tabular-nums"></span>
    </div>

    <!-- Notifications (placeholder) -->
    <button class="relative inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200
                   text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors"
            title="Notifikasi">
        <i class="bi bi-bell text-sm"></i>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
    </button>

    <!-- User menu -->
    <div class="relative" id="user-menu-wrap">
        <button id="user-menu-btn" onclick="toggleUserMenu()" aria-haspopup="true" aria-expanded="false"
                class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-xl border border-transparent
                       hover:border-slate-200 hover:bg-slate-50 transition-all">
            <!-- Avatar -->
            <div class="relative">
                <img src="<?= $avatar ?>" alt="<?= esc($userName) ?>"
                     class="w-8 h-8 rounded-full object-cover ring-2 ring-slate-200">
                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-white"></span>
            </div>
            <!-- Name -->
            <div class="hidden md:block text-left min-w-0">
                <p class="text-[13px] font-semibold text-slate-800 leading-tight truncate max-w-[120px]">
                    <?= esc($userName) ?>
                </p>
                <p class="text-[11px] text-slate-400 leading-none"><?= esc($userRoleName) ?></p>
            </div>
            <i class="bi bi-chevron-down text-[10px] text-slate-400 transition-transform duration-200"
               id="user-chevron"></i>
        </button>

        <!-- Dropdown -->
        <div id="user-menu-dropdown"
             class="hidden absolute right-0 top-full mt-2 w-60 bg-white rounded-2xl shadow-xl
                    border border-slate-100 z-50 overflow-hidden"
             role="menu">

            <!-- Header gradient -->
            <div class="px-5 py-4 bg-gradient-to-br from-slate-800 to-slate-900">
                <div class="flex items-center gap-3">
                    <img src="<?= $avatar ?>" alt="<?= esc($userName) ?>"
                         class="w-10 h-10 rounded-full object-cover ring-2 ring-white/25 shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white truncate"><?= esc($userName) ?></p>
                        <p class="text-[11px] text-slate-400 truncate"><?= esc($userRoleName) ?></p>
                    </div>
                </div>
            </div>

            <!-- Links -->
            <div class="p-2" role="none">
                <a href="<?= site_url('settings') ?>" role="menuitem"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-700
                          hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 group-hover:bg-blue-100 flex items-center
                                 justify-center shrink-0 transition-colors">
                        <i class="bi bi-gear text-blue-600 text-sm"></i>
                    </span>
                    <div>
                        <p class="font-medium leading-tight">Profil & Pengaturan</p>
                        <p class="text-[11px] text-slate-400">Edit akun Anda</p>
                    </div>
                </a>

                <div class="h-px bg-slate-100 my-1 mx-2"></div>

                <button onclick="logout()" role="menuitem"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-600
                               hover:bg-red-50 transition-colors group">
                    <span class="w-8 h-8 rounded-lg bg-red-50 group-hover:bg-red-100 flex items-center
                                 justify-center shrink-0 transition-colors">
                        <i class="bi bi-box-arrow-right text-red-500 text-sm"></i>
                    </span>
                    <div>
                        <p class="font-medium leading-tight">Keluar</p>
                        <p class="text-[11px] text-red-400">Akhiri sesi ini</p>
                    </div>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const wrap    = document.getElementById('user-menu-wrap');
    const btn     = document.getElementById('user-menu-btn');
    const menu    = document.getElementById('user-menu-dropdown');
    const chevron = document.getElementById('user-chevron');

    function open() {
        menu.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
        chevron.style.transform = 'rotate(180deg)';
    }
    function close() {
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
        chevron.style.transform = '';
    }

    window.toggleUserMenu = function () {
        menu.classList.contains('hidden') ? open() : close();
    };

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
</script>
