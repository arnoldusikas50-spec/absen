<?= $this->extend('layouts/mobile_tw') ?>

<?= $this->section('styles') ?>
<style>
    /* Spinner tombol uji Telegram/WA: Bootstrap CSS tidak dimuat di layout mobile,
       jadi .spinner-border dulu tampil kosong. Warnanya ikut teks tombol. */
    .btn-spinner {
        display: inline-block;
        width: 13px; height: 13px;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        vertical-align: -2px;
        animation: btnSpin .7s linear infinite;
    }
    @keyframes btnSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .btn-spinner { animation-duration: 1.6s; }
    }

    /* ── Top bar / hero ───────────────────────────────────── */
    .top-app-bar {
        background: transparent;
        padding-top: calc(16px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
        position: absolute;
        top: 0; left: 0; right: 0;
        z-index: 10;
    }

    /* ── Action card press ────────────────────────────────── */
    .action-card { transition: background 0.13s, transform 0.12s; }
    .action-card:active { transform: scale(0.97); }

    /* ── Face register modal (fullscreen) ─────────────────── */
    #faceRegisterModal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        flex-direction: column;
        background: #000;
    }
    #faceRegisterModal.show { display: flex; }

    .reg-header {
        position: absolute;
        top: 0; left: 0; right: 0;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: calc(12px + var(--safe-area-inset-top, 0px)) 16px 12px;
        background: linear-gradient(180deg, rgba(0,0,0,0.75) 0%, transparent 100%);
        color: #fff;
    }
    .reg-close {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        border: none;
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: transform 0.15s;
    }
    .reg-close:active { transform: scale(0.9); }

    .reg-video-wrap {
        position: relative;
        flex: 1;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .reg-video-wrap video {
        width: 100%; height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }
    .reg-video-wrap canvas {
        position: absolute;
        inset: 0;
        transform: scaleX(-1);
    }
    .reg-oval {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: min(280px, 70vw);
        height: min(360px, 88vw);
        border-radius: 50%;
        border: 2.5px dashed rgba(255,255,255,0.4);
        pointer-events: none;
        transition: border-color 0.4s, box-shadow 0.4s;
        z-index: 4;
    }
    .reg-oval.face-ok {
        border-color: #22c55e;
        box-shadow: 0 0 30px rgba(34,197,94,0.3);
    }
    .reg-flash {
        position: absolute;
        inset: 0;
        background: #fff;
        opacity: 0;
        pointer-events: none;
        z-index: 5;
        transition: opacity 0.15s;
    }
    .reg-flash.fire { opacity: 0.6; }

    #regFaceStatus {
        position: absolute;
        bottom: 12px; left: 12px; right: 12px;
        z-index: 6;
        text-align: center;
        font-size: 13px;
        color: rgba(255,255,255,0.9);
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        padding: 6px 12px;
        border-radius: 20px;
    }

    .reg-success-overlay,
    .reg-fail-overlay {
        position: absolute;
        inset: 0;
        z-index: 8;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: rgba(0,0,0,0.65);
        backdrop-filter: blur(6px);
        color: #fff;
        text-align: center;
        padding: 24px;
    }
    .reg-success-overlay.show,
    .reg-fail-overlay.show { display: flex; }

    @keyframes regPopIn {
        0%   { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    .check-icon, .fail-icon {
        width: 80px; height: 80px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem;
        animation: regPopIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .check-icon { background: #22c55e; box-shadow: 0 8px 30px rgba(34,197,94,0.4); }
    .fail-icon  { background: var(--brand-600); }

    .btn-retry {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.25);
        backdrop-filter: blur(8px);
        color: #fff;
        padding: 8px 20px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.15s;
    }
    .btn-retry:active { transform: scale(0.95); }
    .btn-dismiss {
        background: transparent;
        border: none;
        color: rgba(255,255,255,0.6);
        font-size: 13px;
        cursor: pointer;
    }

    .reg-footer {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        z-index: 7;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 16px 16px calc(24px + var(--safe-area-inset-bottom, 0px));
        background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, transparent 100%);
    }
    .reg-progress-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .reg-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        transition: background 0.2s, transform 0.2s;
    }
    .reg-dot.active { background: #22c55e; transform: scale(1.4); }
    .reg-dot.done   { background: #22c55e; }

    .reg-loading-bar {
        width: 200px; height: 3px;
        background: rgba(255,255,255,0.1);
        border-radius: 99px;
        overflow: hidden;
    }
    .reg-loading-bar .bar {
        width: 30%; height: 100%;
        background: linear-gradient(90deg, transparent, #22c55e, transparent);
        animation: regShimmer 1.2s ease-in-out infinite;
    }
    @keyframes regShimmer {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(400%); }
    }

    /* ── Skeleton shimmer ─────────────────────────────────── */
    .skel {
        border-radius: 8px;
        background: linear-gradient(90deg, #ede9f0 25%, #f5f3f7 50%, #ede9f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
    }
    @keyframes shimmer { to { background-position: -200% 0; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$finalSrc = \App\Services\AvatarService::tampilan($guru->foto ?? '', $guru->nama_guru ?? '', '#e11d48');
?>

<!-- ── Hero ──────────────────────────────────────────────── -->
<div class="relative bg-gradient-to-br from-brand-600 via-brand-500 to-brand-400 pb-16"
    style="padding-top: calc(64px + var(--safe-area-inset-top, 0px)); border-radius: 0 0 40px 40px; box-shadow: 0 12px 32px rgb(var(--brand-600-rgb) / 0.25);">

    <!-- Top bar overlay -->
    <div class="top-app-bar px-4 flex items-center justify-between">
        <a href="<?= site_url('presensi') ?>"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-white/15 text-white no-underline">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <h1 class="text-base font-bold text-white m-0">Profil Saya</h1>
        <button onclick="showEditModal()"
            class="w-9 h-9 flex items-center justify-center rounded-full bg-white/15 text-white border-none cursor-pointer">
            <i class="bi bi-pencil text-sm"></i>
        </button>
    </div>

    <!-- Hero info -->
    <div class="flex flex-col items-center text-center px-6 pb-2">
        <div class="text-white/80 text-[11px] font-semibold uppercase tracking-widest mb-1">
            <?= esc($guru->jabatan ?? 'Tenaga Pengajar') ?>
        </div>
        <h2 class="text-white text-xl font-extrabold m-0 leading-tight">
            <?= esc($guru->nama_guru) ?>
        </h2>
        <div class="mt-2 px-3 py-1 bg-white/20 rounded-full text-white text-xs font-bold backdrop-blur-sm">
            <?= esc($guru->nip ?? 'NIP: -') ?>
        </div>
    </div>
</div>

<!-- ── Avatar (overlaps hero) ────────────────────────────── -->
<div class="flex justify-center" style="margin-top: -54px;">
    <div class="relative inline-block">
        <img src="<?= $finalSrc ?>" id="profileAvatar" alt="Foto Profil"
            class="w-[108px] h-[108px] rounded-[28px] object-cover shadow-xl"
            style="border: 4px solid #FEF7FF;">
        <button onclick="document.getElementById('fotoInput').click()"
            class="absolute -bottom-1 -right-1 w-8 h-8 rounded-[10px] bg-rose-600 text-white flex items-center justify-center shadow-md border-[3px] border-[#FEF7FF] cursor-pointer">
            <i class="bi bi-camera-fill text-xs"></i>
        </button>
        <input type="file" id="fotoInput" accept="image/*" class="hidden" onchange="uploadFoto(this)">
    </div>
</div>

<!-- ── Main Content ───────────────────────────────────────── -->
<div class="px-4 pb-8 space-y-5 mt-5">

    <!-- ── Action Menu ─────────────────────────────────────── -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">

        <!-- Edit Informasi -->
        <button onclick="showEditModal()"
            class="action-card w-full flex items-center gap-3 px-4 py-3.5 bg-transparent border-none text-left cursor-pointer">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-person-gear text-blue-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm m-0 leading-none">Ubah Informasi</p>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5">Nama, Email, dan No. HP</p>
            </div>
            <i class="bi bi-chevron-right text-slate-300 text-sm"></i>
        </button>

        <!-- Keamanan Akun -->
        <button onclick="showLoginModal()"
            class="action-card w-full flex items-center gap-3 px-4 py-3.5 bg-transparent border-none text-left cursor-pointer">
            <div class="w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-patch-check text-orange-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm m-0 leading-none">Keamanan Akun</p>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5">Username &amp; Password</p>
            </div>
            <i class="bi bi-chevron-right text-slate-300 text-sm"></i>
        </button>

        <!-- Daftarkan Wajah -->
        <button id="btnRegisterFace" onclick="registerFace()"
            class="action-card w-full flex items-center gap-3 px-4 py-3.5 bg-transparent border-none text-left cursor-pointer">
            <div id="faceIconBox" class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-camera text-emerald-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm m-0 leading-none" id="faceLabel">Daftarkan Wajah</p>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5" id="faceSubLabel">Untuk verifikasi wajah saat absen</p>
            </div>
            <i class="bi bi-chevron-right text-slate-300 text-sm" id="faceChevron"></i>
        </button>

        <!-- Face status alert -->
        <div id="faceStatusAlert" class="hidden px-4 py-3 text-xs font-medium text-amber-700 bg-amber-50 flex items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 flex-shrink-0 mt-0.5"></i>
            <span></span>
        </div>

        <!-- Logout -->
        <a href="<?= site_url('presensi/logout') ?>"
            class="action-card flex items-center gap-3 px-4 py-3.5 no-underline"
            style="border-top: 1px solid var(--brand-50);">
            <div class="w-11 h-11 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-box-arrow-right text-rose-600 text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-rose-600 text-sm m-0 leading-none">Keluar Aplikasi</p>
                <p class="text-[11px] text-slate-400 m-0 mt-0.5">Selesaikan sesi kerja Anda</p>
            </div>
            <i class="bi bi-chevron-right text-rose-300 text-sm"></i>
        </a>
    </div>

    <!-- ── Info Tambahan ────────────────────────────────────── -->
    <div>
        <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1 mb-2.5">Informasi Tambahan</h2>
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">

            <div class="flex items-center px-4 py-3.5 gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-person-badge text-purple-500 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] text-slate-400 m-0 font-medium">Jenis Kelamin</p>
                    <p class="text-sm font-semibold text-slate-700 m-0">
                        <?= esc($guru->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan') ?>
                    </p>
                </div>
            </div>

            <div class="flex items-center px-4 py-3.5 gap-3">
                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-telephone text-green-500 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] text-slate-400 m-0 font-medium">No. Telepon</p>
                    <p class="text-sm font-semibold text-slate-700 m-0"><?= esc($guru->no_hp ?? '-') ?></p>
                </div>
            </div>

            <div class="flex items-center px-4 py-3.5 gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-envelope text-blue-500 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] text-slate-400 m-0 font-medium">Alamat Email</p>
                    <p class="text-sm font-semibold text-slate-700 m-0 truncate"><?= esc($guru->email ?? '-') ?></p>
                </div>
            </div>

        </div>
    </div>


</div>

<!-- ════════════════════════════════════════════════════════ -->
<!-- Modal: Edit Profil (Bottom Sheet)                       -->
<!-- ════════════════════════════════════════════════════════ -->
<div class="tw-modal" id="editModal">
    <div class="tw-modal-box">
        <!-- Handle -->
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-9 h-1 rounded-full bg-slate-200"></div>
        </div>
        <div class="px-6 pb-6 pt-3">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-bold text-slate-800 text-base m-0">Edit Informasi</h2>
                <button type="button" data-tw-dismiss="modal"
                    class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 text-slate-500 border-none cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <form id="formEditProfile" class="space-y-4">
                <!-- Nama -->
                <div>
                    <label class="form-label-tw">Nama Lengkap</label>
                    <div class="relative">
                        <i class="bi bi-person absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" class="m3-input pl-9" name="nama_guru"
                            value="<?= esc($guru->nama_guru) ?>" placeholder="Masukkan nama..." required>
                    </div>
                </div>
                <!-- Email -->
                <div>
                    <label class="form-label-tw">Alamat Surel</label>
                    <div class="relative">
                        <i class="bi bi-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" class="m3-input pl-9" name="email"
                            value="<?= esc($guru->email ?? '') ?>" placeholder="email@sekolah.sch.id">
                    </div>
                </div>
                <!-- No HP (hidden — cukup ditampilkan di profil, edit via field WA Notifikasi) -->
                <input type="hidden" name="no_hp" value="<?= esc($guru->no_hp ?? '') ?>">
                <!-- Jabatan -->
                <div>
                    <label class="form-label-tw">Jabatan / Tugas</label>
                    <div class="relative">
                        <i class="bi bi-briefcase absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" class="m3-input pl-9" name="jabatan"
                            value="<?= esc($guru->jabatan ?? '') ?>" placeholder="Contoh: Guru Matematika">
                    </div>
                </div>
                <!-- ID Telegram -->
                <div>
                    <label class="form-label-tw">ID Telegram</label>
                    <div class="relative">
                        <i class="bi bi-telegram absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" inputmode="numeric" class="m3-input pl-9 font-mono" name="telegram_id"
                            value="<?= esc($guru->telegram_id ?? '') ?>" placeholder="123456789" maxlength="50">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1 mb-0">Denda, surat teguran, dan peringatan alpha dikirim ke chat ini. Ambil ID dengan membuka bot <b>@userinfobot</b> di Telegram lalu tekan Start. Kosongkan untuk melepas.</p>
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" id="btn-tg-test"
                            class="flex-1 px-3.5 py-2 rounded-xl bg-sky-50 text-sky-700 border border-sky-200 text-[12px] font-bold active:scale-95 transition-transform">
                            <i class="bi bi-send"></i> Cek Kirim Pesan
                        </button>
                        <span id="tg-test-status" class="text-[11px] font-semibold flex-1"></span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 mb-0">Kirim pesan percobaan ke chat ini untuk memastikan tautan benar-benar bekerja, bukan hanya tersimpan.</p>
                </div>
                <!-- Nomor WhatsApp Notifikasi -->
                <div>
                    <label class="form-label-tw">Nomor WhatsApp</label>
                    <div class="relative">
                        <i class="bi bi-whatsapp absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="tel" inputmode="tel" class="m3-input pl-9 font-mono" name="wa_number"
                            value="<?= esc($waNotifNumber ?? '') ?>" placeholder="6281234567890" maxlength="20">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1 mb-0">
                        Nomor tujuan notifikasi personal (denda, surat teguran, alpha).
                        Ditulis nomor saja, boleh 08 / +62 / 62. Kosongkan untuk melepas.
                    </p>
                    <?php if (!empty($waNotifVerified)): ?>
                    <p class="text-[11px] text-emerald-600 mt-1 mb-0"><i class="bi bi-check-circle"></i> Nomor ini sudah dikonfirmasi admin dan aktif.</p>
                    <?php elseif (!empty($waNotifNumber)): ?>
                    <p class="text-[11px] text-amber-600 mt-1 mb-0"><i class="bi bi-hourglass-split"></i> Tersimpan, menunggu konfirmasi admin.</p>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" id="btn-wa-test"
                            class="flex-1 px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-[12px] font-bold active:scale-95 transition-transform">
                            <i class="bi bi-send"></i> Cek Kirim Pesan
                        </button>
                        <span id="wa-test-status" class="text-[11px] font-semibold flex-1"></span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 mb-0">Kirim pesan percobaan untuk memastikan nomornya benar sebelum dikonfirmasi admin.</p>
                </div>

                <button type="submit"
                    class="btn-tw btn-brand w-full py-3 rounded-full font-bold text-sm mt-1">
                    <i class="bi bi-check2-circle"></i> Simpan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════ -->
<!-- Modal: Keamanan Akun (Bottom Sheet)                     -->
<!-- ════════════════════════════════════════════════════════ -->
<div class="tw-modal" id="editLoginModal">
    <div class="tw-modal-box">
        <!-- Handle -->
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-9 h-1 rounded-full bg-slate-200"></div>
        </div>
        <div class="px-6 pb-6 pt-3">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base m-0">Keamanan Akun</h2>
                <button type="button" data-tw-dismiss="modal"
                    class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 text-slate-500 border-none cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Warning notice -->
            <div class="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-3 mb-5">
                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xl flex-shrink-0 mt-0.5"></i>
                <p class="text-amber-700 text-xs m-0 leading-relaxed">
                    Perubahan kredensial akan menghentikan sesi login aktif Anda secara otomatis.
                </p>
            </div>

            <form id="formEditLogin" class="space-y-4">
                <!-- Username -->
                <div>
                    <label class="form-label-tw">Username Baru</label>
                    <div class="relative">
                        <i class="bi bi-person-badge absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" class="m3-input pl-9" name="username"
                            value="<?= esc($guru->username ?? '') ?>" required>
                    </div>
                </div>
                <!-- Password -->
                <div>
                    <label class="form-label-tw">Password Baru</label>
                    <div class="relative">
                        <i class="bi bi-shield-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" class="m3-input pl-9" name="new_password"
                            placeholder="Kosongkan jika tak diubah">
                    </div>
                </div>
                <!-- Konfirmasi Password -->
                <div>
                    <label class="form-label-tw">Konfirmasi Password</label>
                    <div class="relative">
                        <i class="bi bi-shield-check absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" class="m3-input pl-9" name="confirm_password"
                            placeholder="Ketik ulang password baru">
                    </div>
                </div>

                <button type="submit"
                    class="btn-tw btn-brand w-full py-3 rounded-full font-bold text-sm mt-1">
                    <i class="bi bi-shield-check"></i> Perbarui Kredensial
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════ -->
<!-- Face Registration Camera Modal (Fullscreen)             -->
<!-- ════════════════════════════════════════════════════════ -->
<div id="faceRegisterModal">
    <!-- Header -->
    <div class="reg-header">
        <button class="reg-close" onclick="closeRegCamera()"><i class="bi bi-x-lg"></i></button>
        <h6 class="m-0 text-white text-sm font-bold"><i class="bi bi-camera me-1"></i> Daftarkan Wajah</h6>
        <span style="width:36px"></span>
    </div>

    <!-- Video area -->
    <div class="reg-video-wrap" id="regVideoWrap">
        <video id="regVideo" autoplay playsinline muted></video>
        <canvas id="regCanvas"></canvas>
        <div class="reg-oval" id="regOval"></div>
        <div class="reg-flash" id="regFlash"></div>

        <div id="regFaceStatus">
            <i class="bi bi-person"></i> Arahkan wajah ke dalam bingkai
        </div>

        <!-- Success overlay -->
        <div class="reg-success-overlay" id="regSuccess">
            <div class="check-icon"><i class="bi bi-check-lg"></i></div>
            <h5 class="text-white font-bold m-0">Wajah Terdaftar!</h5>
            <p class="text-white/70 text-sm m-0">Data wajah berhasil disimpan</p>
        </div>

        <!-- Fail overlay -->
        <div class="reg-fail-overlay" id="regFail">
            <div class="fail-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <h5 class="text-white font-bold m-0">Gagal Mendeteksi</h5>
            <p id="regFailMsg" class="text-white/70 text-sm m-0">Wajah tidak terdeteksi. Pastikan pencahayaan cukup.</p>
            <button class="btn-retry" onclick="retryRegisterFace()"><i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi</button>
            <button class="btn-dismiss" onclick="closeRegCamera()">Tutup</button>
        </div>
    </div>

    <!-- Footer progress -->
    <div class="reg-footer">
        <div class="reg-progress-wrap" id="regProgress">
            <span class="reg-dot active"></span>
            <span class="reg-dot"></span>
            <span class="reg-dot"></span>
            <span class="reg-dot"></span>
            <span class="reg-dot"></span>
        </div>
        <div class="reg-loading-bar" id="regLoadingBar" style="display:none;">
            <div class="bar"></div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    let regStream = null;
    let regInterval = null;
    let regModelsLoaded = false;
    let regCaptureStarted = false;

    function showEditModal()  { twModalShow('#editModal'); }
    function hideEditModal()  { twModalHide('#editModal'); }
    function showLoginModal() { twModalShow('#editLoginModal'); }
    function hideLoginModal() { twModalHide('#editLoginModal'); }

    // ── Upload Foto ────────────────────────────────────────
    function uploadFoto(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('foto', input.files[0]);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            Swal.fire({ title: 'Mengunggah...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                url: '<?= site_url('presensi/upload-foto') ?>',
                type: 'POST', data: formData, processData: false, contentType: false,
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Sukses', 'Foto profil diperbarui', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            });
        }
    }

    // ── Form Edit Profil ───────────────────────────────────
    $('#formEditProfile').on('submit', function (e) {
        e.preventDefault();
        // Validasi ringan ID Telegram sebelum ke server (server tetap memvalidasi).
        const tgInput = $('#formEditProfile input[name="telegram_id"]');
        if (tgInput.length) {
            const raw = (tgInput.val() || '').replace(/[\s@]/g, '').trim();
            if (raw !== '' && !/^-?\d{1,20}$/.test(raw)) {
                Swal.fire('ID Telegram tidak valid', 'Isi hanya angka, boleh diawali tanda minus. Contoh: 123456789', 'warning');
                return;
            }
        }
        // Nomor WA: hanya digit setelah tanda baca dibuang.
        const waInput = $('#formEditProfile input[name="wa_number"]');
        if (waInput.length) {
            const digits = (waInput.val() || '').replace(/\D/g, '');
            if (digits !== '' && digits.length < 7) {
                Swal.fire('Nomor WhatsApp tidak valid', 'Nomor terlalu pendek. Contoh: 081234567890', 'warning');
                return;
            }
        }
        const data = $(this).serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>';
        $.post('<?= site_url('presensi/update-profile') ?>', data, function (res) {
            if (res.status) Swal.fire('Tersimpan', res.message, 'success').then(() => location.reload());
            else Swal.fire('Gagal', res.message, 'error');
        });
    });

    // ── Cek Kirim Pesan Telegram ───────────────────────────
    // Memastikan chat id benar-benar bisa dikirimi pesan, bukan sekadar
    // tersimpan di database. Simpan profil dulu bila ID baru diisi.
    $('#btn-tg-test').on('click', function () {
        const btn = $(this);
        const st = $('#tg-test-status');
        const raw = ($('#formEditProfile input[name="telegram_id"]').val() || '').replace(/[\s@]/g, '').trim();
        const saved = <?= json_encode((string) ($guru->telegram_id ?? '')) ?>;

        if (raw !== '' && raw !== saved) {
            st.textContent = 'Simpan profil dulu';
            st.removeClass('text-emerald-600 text-rose-600').addClass('text-amber-600');
            return;
        }
        if (raw === '' && saved === '') {
            st.textContent = 'ID Telegram masih kosong';
            st.removeClass('text-emerald-600 text-rose-600 text-amber-600').addClass('text-amber-600');
            return;
        }

        btn.prop('disabled', true).html('<span class="btn-spinner"></span> Mengirim...');
        st.textContent = '';

        $.post('<?= site_url('presensi/telegram-test') ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        }, function (res) {
            btn.prop('disabled', false).html('<i class="bi bi-send"></i> Cek Kirim Pesan');
            if (res && res.status) {
                st.textContent = 'Terkirim ✓';
                st.removeClass('text-rose-600 text-amber-600').addClass('text-emerald-600');
                Swal.fire('Pesan terkirim', res.message || 'Cek chat Telegram Anda.', 'success');
            } else {
                st.textContent = 'Gagal';
                st.removeClass('text-emerald-600 text-amber-600').addClass('text-rose-600');
                Swal.fire('Gagal mengirim', (res && res.message) || 'Telegram menolak pengiriman.', 'error');
            }
        }).fail(function () {
            btn.prop('disabled', false).html('<i class="bi bi-send"></i> Cek Kirim Pesan');
            st.textContent = 'Gagal';
            st.removeClass('text-emerald-600 text-amber-600').addClass('text-rose-600');
            Swal.fire('Gagal', 'Tidak bisa menghubungi server.', 'error');
        });
    });

    // ── Cek Kirim Pesan WhatsApp ───────────────────────────
    // Memastikan nomor tujuan benar-benar bisa dikirimi pesan. Nomor WA
    // punya jeda: guru mengisi kolom, menekan Simpan, lalu baru bisa cek.
    $('#btn-wa-test').on('click', function () {
        const btn = $(this);
        const st = $('#wa-test-status');
        const raw = ($('#formEditProfile input[name="wa_number"]').val() || '').replace(/\D/g, '');
        const saved = <?= json_encode((string) ($waNotifNumber ?? '')) ?>;

        if (raw !== saved) {
            st.textContent = 'Simpan profil dulu';
            st.removeClass('text-emerald-600 text-rose-600').addClass('text-amber-600');
            return;
        }
        if (raw === '') {
            st.textContent = 'Nomor masih kosong';
            st.removeClass('text-emerald-600 text-rose-600 text-amber-600').addClass('text-amber-600');
            return;
        }

        btn.prop('disabled', true).html('<span class="btn-spinner"></span> Mengirim...');
        st.textContent = '';

        $.post('<?= site_url('presensi/wa-test') ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        }, function (res) {
            btn.prop('disabled', false).html('<i class="bi bi-send"></i> Cek Kirim Pesan');
            if (res && res.status) {
                st.textContent = 'Terkirim ✓';
                st.removeClass('text-rose-600 text-amber-600').addClass('text-emerald-600');
                Swal.fire('Pesan terkirim', res.message || 'Cek WhatsApp Anda.', 'success');
            } else {
                st.textContent = 'Gagal';
                st.removeClass('text-emerald-600 text-amber-600').addClass('text-rose-600');
                Swal.fire('Gagal mengirim', (res && res.message) || 'Gateway menolak pengiriman.', 'error');
            }
        }).fail(function () {
            btn.prop('disabled', false).html('<i class="bi bi-send"></i> Cek Kirim Pesan');
            st.textContent = 'Gagal';
            st.removeClass('text-emerald-600 text-amber-600').addClass('text-rose-600');
            Swal.fire('Gagal', 'Tidak bisa menghubungi server.', 'error');
        });
    });

    // ── Form Edit Login ────────────────────────────────────
    $('#formEditLogin').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        $.ajax({
            url: '<?= site_url('presensi/update-login') ?>',
            type: 'POST', data: formData, processData: false, contentType: false,
            success: function (res) {
                if (res.status) Swal.fire('Berhasil', 'Login diperbarui', 'success').then(() => location.reload());
                else Swal.fire('Gagal', res.message, 'error');
            }
        });
    });

    // ── Init ───────────────────────────────────────────────
    $(function () {
        preloadFaceModels();
        checkFaceStatus();
    });

    // Status wajah milik sendiri (diisi checkFaceStatus)
    let faceState = { hasFace: false, pending: false };

    async function checkFaceStatus() {
        try {
            const res = await $.get('<?= site_url('presensi/face-status') ?>');
            if (res.status && res.data) {
                const data = res.data;
                faceState.hasFace = !!data.has_face;
                faceState.pending = data.pending_request === 'pending';
                if (data.has_face) {
                    $('#faceLabel').text('Perbarui Wajah');
                    $('#faceSubLabel').text('Wajah sudah terdaftar');
                    $('#faceIconBox')
                        .removeClass('bg-emerald-50')
                        .addClass('bg-orange-50')
                        .html('<i class="bi bi-shield-check text-orange-600 text-lg"></i>');

                    if (data.pending_request === 'pending') {
                        $('#faceLabel').text('Pengajuan Ulang Ditunda');
                        $('#faceSubLabel').text('Menunggu persetujuan admin');
                        $('#faceIconBox')
                            .removeClass('bg-orange-50')
                            .addClass('bg-amber-50')
                            .html('<i class="bi bi-hourglass-split text-amber-600 text-lg"></i>');
                        $('#faceChevron').hide();
                        $('#faceStatusAlert')
                            .removeClass('hidden')
                            .find('span')
                            .text('Pengajuan ulang wajah Anda sedang menunggu persetujuan admin.');
                    }
                }
            }
        } catch (e) { /* silently fail */ }
    }

    // ── Preload Face Models ────────────────────────────────
    async function preloadFaceModels() {
        if (typeof faceapi === 'undefined') return;
        const modelUrl = 'https://justadudewhohacks.github.io/face-api.js/models';
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl)
            ]);
            regModelsLoaded = true;
        } catch (e) {
            console.warn('Face model preload failed, will retry on demand:', e);
        }
    }

    // ── Open Camera Modal ──────────────────────────────────
    async function openRegCamera() {
        const video = document.getElementById('regVideo');
        const canvas = document.getElementById('regCanvas');

        $('#regSuccess').removeClass('show');
        $('#regFail').removeClass('show');
        $('#regFlash').removeClass('fire');
        $('#regFaceStatus').html('<i class="bi bi-arrow-repeat me-1"></i> Mengakses kamera...');
        $('#regLoadingBar').hide();
        $('#regOval').removeClass('face-ok');
        updateRegDots(0);
        regCaptureStarted = false;

        document.getElementById('faceRegisterModal').classList.add('show');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false
            });
            regStream = stream;
            video.srcObject = stream;
            await video.play();

            const displaySize = { width: video.clientWidth, height: video.clientHeight };
            faceapi.matchDimensions(canvas, displaySize);

            video.onloadedmetadata = () => {
                const ds = { width: video.clientWidth, height: video.clientHeight };
                faceapi.matchDimensions(canvas, ds);
            };

            if (!regModelsLoaded) {
                $('#regFaceStatus').html('<i class="bi bi-arrow-repeat me-1"></i> Memuat model AI...');
                $('#regLoadingBar').show();
                await preloadFaceModels();
                $('#regLoadingBar').hide();
                if (!regModelsLoaded) {
                    showRegFail('Gagal memuat model AI. Periksa koneksi internet.');
                    return;
                }
            }

            $('#regFaceStatus').html('<i class="bi bi-phone me-1"></i> Arahkan wajah ke dalam bingkai');
            startRegDetection(video, canvas, displaySize);

        } catch (e) {
            showRegFail('Gagal akses kamera: ' + e.message);
        }
    }

    // ── Real-time Face Detection ───────────────────────────
    function startRegDetection(video, canvas, displaySize) {
        let stableFrames = 0;
        const REQUIRED_STABLE = 6;
        let detectedCount = 0;

        if (regInterval) clearInterval(regInterval);

        regInterval = setInterval(async () => {
            if (!regStream || regCaptureStarted) return;

            try {
                const currentW = video.videoWidth || video.clientWidth;
                const currentH = video.videoHeight || video.clientHeight;
                if (currentW <= 0 || currentH <= 0) return;

                if (Math.abs(displaySize.width - currentW) > 5 || Math.abs(displaySize.height - currentH) > 5) {
                    displaySize.width = currentW;
                    displaySize.height = currentH;
                    faceapi.matchDimensions(canvas, displaySize);
                }

                const detections = await faceapi
                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }));

                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (detections && detections.length > 0) {
                    const resized = faceapi.resizeResults(detections, displaySize);
                    faceapi.draw.drawDetections(canvas, resized);

                    $('#regOval').addClass('face-ok');
                    stableFrames++;
                    detectedCount++;

                    if (detectedCount === 1) {
                        $('#regFaceStatus').html('<i class="bi bi-person-check me-1"></i> Wajah terdeteksi');
                    }

                    const dots = Math.min(Math.floor(stableFrames / 2) + 1, 5);
                    updateRegDots(dots);

                    if (stableFrames >= REQUIRED_STABLE && !regCaptureStarted) {
                        regCaptureStarted = true;
                        $('#regFaceStatus').html('<i class="bi bi-check2-circle me-1"></i> Mengambil data wajah...');
                        captureRegFace(video);
                    }
                } else {
                    stableFrames = 0;
                    $('#regOval').removeClass('face-ok');
                    if (detectedCount > 0) {
                        $('#regFaceStatus').html('<i class="bi bi-phone me-1"></i> Wajah tidak terlihat, arahkan ke bingkai');
                        updateRegDots(0);
                        detectedCount = 0;
                    }
                }
            } catch (err) {
                if (err.message && !err.message.includes('dimensions')) {
                    console.warn('Detection error:', err);
                }
            }
        }, 200);
    }

    // ── Capture Face & Send ────────────────────────────────
    async function captureRegFace(video) {
        const flash = document.getElementById('regFlash');
        flash.classList.add('fire');
        setTimeout(() => flash.classList.remove('fire'), 300);

        const canvas = document.createElement('canvas');
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        try {
            const fullDesc = await faceapi
                .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }))
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!fullDesc || !fullDesc.descriptor) {
                showRegFail('Wajah tidak terdeteksi saat pengambilan. Coba lagi.');
                return;
            }

            const descriptor = JSON.stringify(Array.from(fullDesc.descriptor));
            const csrf = '<?= csrf_token() ?>';
            const hash = '<?= csrf_hash() ?>';

            $('#regFaceStatus').html('<i class="bi bi-cloud-arrow-up me-1"></i> Menyimpan data...');

            $.post('<?= site_url('presensi/register-face') ?>',
                { face_descriptor: descriptor, [csrf]: hash },
                function (res) {
                    if (res.status) {
                        $('#regSuccess .check-icon').html('<i class="bi bi-check-lg"></i>');
                        $('#regSuccess h5').text('Wajah Berhasil Disimpan!');
                        $('#regSuccess p').text('Data biometrik langsung aktif untuk verifikasi absensi.');
                        $('#regSuccess').addClass('show');
                        closeRegStream();
                        setTimeout(() => {
                            document.getElementById('faceRegisterModal').classList.remove('show');
                            $('#regSuccess').removeClass('show');
                            location.reload();
                        }, 2500);
                    } else {
                        showRegFail(res.message || 'Gagal mendaftarkan wajah');
                    }
                }, 'json'
            ).fail(function (xhr) {
                let msg = 'Gagal terhubung ke server';
                try { const r = JSON.parse(xhr.responseText); msg = r.message || msg; } catch (e) {}
                showRegFail(msg);
            });

        } catch (err) {
            showRegFail('Gagal memproses wajah: ' + err.message);
        }
    }

    // ── Utility ────────────────────────────────────────────
    function updateRegDots(activeCount) {
        const dots = document.querySelectorAll('#regProgress .reg-dot');
        dots.forEach((dot, i) => {
            dot.classList.remove('active', 'done');
            if (i < activeCount) dot.classList.add('done');
        });
        if (activeCount > 0 && activeCount <= dots.length) {
            dots[Math.min(activeCount - 1, dots.length - 1)].classList.add('active');
        }
    }

    function showRegFail(msg) {
        $('#regFailMsg').text(msg);
        $('#regFail').addClass('show');
        $('#regFaceStatus').html('<i class="bi bi-exclamation-circle me-1"></i> ' + msg);
        $('#regOval').removeClass('face-ok');
    }

    function closeRegStream() {
        if (regStream) { regStream.getTracks().forEach(t => t.stop()); regStream = null; }
        if (regInterval) { clearInterval(regInterval); regInterval = null; }
        const video = document.getElementById('regVideo');
        video.srcObject = null;
        const canvas = document.getElementById('regCanvas');
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    }

    function closeRegCamera() {
        closeRegStream();
        document.getElementById('faceRegisterModal').classList.remove('show');
        $('#regSuccess').removeClass('show');
        $('#regFail').removeClass('show');
        regCaptureStarted = false;
    }

    function retryRegisterFace() {
        $('#regFail').removeClass('show');
        $('#regOval').removeClass('face-ok');
        $('#regFaceStatus').html('<i class="bi bi-phone me-1"></i> Arahkan wajah ke dalam bingkai');
        updateRegDots(0);
        regCaptureStarted = false;
        startRegDetection(
            document.getElementById('regVideo'),
            document.getElementById('regCanvas'),
            { width: 0, height: 0 }
        );
    }

    async function registerFace() {
        if (typeof faceapi === 'undefined') {
            Swal.fire('Gagal', 'Library FaceAPI tidak tersedia. Refresh halaman dan coba lagi.', 'error');
            return;
        }
        // Pengajuan masih menunggu persetujuan admin — cegah daftar ulang ganda
        if (faceState.pending) {
            Swal.fire({
                icon: 'info',
                title: 'Menunggu Persetujuan',
                text: 'Pengajuan ulang wajah Anda sedang diperiksa admin. Tunggu keputusan sebelum mengajukan lagi.',
                confirmButtonColor: '#e11d48'
            });
            return;
        }
        // Wajah sudah terdaftar — wajib konfirmasi agar tidak salah tekan
        if (faceState.hasFace) {
            const res = await Swal.fire({
                icon: 'warning',
                title: 'Perbarui Wajah?',
                html: '<p class="text-sm text-slate-600 mb-0">Wajah Anda <b>sudah terdaftar</b>. Pengajuan ulang akan mengganti data wajah lama dan <b>memerlukan persetujuan admin</b> sebelum berlaku.</p>',
                showCancelButton: true,
                confirmButtonText: 'Ya, Ajukan Ulang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#e11d48',
                reverseButtons: true
            });
            if (!res.isConfirmed) return;
        }
        await openRegCamera();
    }
</script>
<?= $this->endSection() ?>
