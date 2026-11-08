<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceSettingModel extends Model
{
    protected $table = 'absensi_setting';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'jam_masuk',
        'jam_pulang',
        'radius_meter',
        'foto_wajib',
        'hari_kerja',
        'hari_libur_mingguan',
        'libur_kalender',
        'max_izin_hari',
        'max_cuti_hari',
        'max_sakit_hari',
        // Batas durasi per pengajuan dipisah dari kuota tahunan:|max_izin_hari
        // (label "Hari / Tahun") pernah dipakai dua-duanya sekaligus.
        'max_izin_per_pengajuan',
        'max_cuti_per_pengajuan',
        'max_sakit_per_pengajuan',
        'max_dinas_per_pengajuan',
        // 1 = kuota dihitung hanya dari hari kerja efektif (menyamakan
        // kuota dengan baris absensi yang ditulis approvePermit).
        'kuota_hari_kerja',
        'point_hadir',
        'point_telat',
        'point_pulang_awal',
        'point_izin',
        'point_sakit',
        'point_cuti',
        'point_alpa',
        // Pengurangan poin guru yang masuk tapi tidak absen pulang.
        // 0 = fitur dimatikan; lupa_pulang_dari_tgl watermark cron pertama.
        'point_lupa_pulang',
        'lupa_pulang_dari_tgl',
        'grace_period',
        'max_denda_bulan',
        'denda_per_menit',
        'denda_pulang_awal',
        'denda_tier_1',
        'denda_tier_2',
        'denda_tier_3',
        'denda_alpha_per_hari',
        // Denda siswa (alpha & bolos) — terpisah dari tarif guru di atas.
        // 0 = tidak ada tagihan; max_denda_siswa_bulan adalah plafon per siswa
        // per bulan kalender, bukan milik guru.
        'denda_alpha_siswa_per_hari',
        'denda_bolos_siswa_per_hari',
        'max_denda_siswa_bulan',
        'point_tier_1',
        'point_tier_2',
        'point_tier_3',
        'reward_cap_menit',
        'reward_point_tier_1',
        'reward_point_tier_2',
        'reward_point_tier_3',
        'telegram_bot_token',
        'telegram_chat_id',
        'wa_gateway_url',
        'wa_token',
        'wa_target_number',
        // Gateway WhatsApp OpenWA (migrasi 20261001120000)
        'wa_gateway_mode',
        'wa_base_url',
        'wa_api_key',
        'wa_session_name',
        // Kolom baru (migrasi 20260617000003)
        'min_kehadiran_persen',
        'alert_kehadiran_enabled',
        'notif_kehadiran_rendah',
        // WFH settings
        'wfh_enabled',
        'wfh_mulai',
        'wfh_selesai',
        'wfh_max_per_bulan',
        'wfh_wajib_wajah',
        // Wajib isi jurnal hari ini sebelum absen pulang
        'wajib_jurnal_sebelum_pulang',
        // Jurnal juga harus sudah divalidasi piket (opt-in, default mati)
        'wajib_jurnal_tervalidasi_sebelum_pulang',
        // Lembur settings
        'point_lembur_per_jam',
        // Biometric Face Match
        'face_threshold',
        'face_match_mode',
        // Denda Point
        'denda_per_point',
        // Jam operasional scanner QR siswa
        'qr_scan_aktif',
        'qr_scan_mulai',
        'qr_scan_selesai',
        // Token kiosk opsional untuk /scanner publik (kosong = nonaktif)
        'qr_kiosk_token',
        // Google OAuth settings
        'google_client_id',
        'google_client_secret',
        'google_login_enabled',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Baris utama = id terkecil.
     *
     * Tabel ini pernah terisi ganda, jadi `first()` tanpa ORDER BY
     * mengembalikan baris yang bergantung pada urutan fisik tabel — nilai
     * yang bisa berbeda antar-request untuk isi yang identik. Itu causes
     * pengaturan yang dibaca cron (min_kehadiran_persen,
     * notif_kehadiran_rendah, hari_libur_mingguan, point_alpa, denda_alpha_per_hari)
     * ikut tidak stabil.
     *
     * NotificationService sudah lama memasang bumper untuk 9 kolom kredensialnya
     * dengan cara yang sama ("Baris utama (id terkecil) tetap dipakai").
     * Menorderkan di sini membuat seluruh pembacaan ikut aturan yang sama,
     * bukan cuma kolom notifikasi.
     */
    public function first()
    {
        if (! $this->orderBy) {
            $this->orderBy('id', 'ASC');
        }

        return parent::first();
    }
}
