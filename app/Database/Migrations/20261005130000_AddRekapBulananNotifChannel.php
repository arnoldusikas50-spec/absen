<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah baris konfigurasi channel untuk jenis 'rekap_bulanan'.
 *
 * Jenis ini dipakai PresensiAdmin::kirimRekapBulanan() — tombol
 * "Kirim ke WA & TG" di halaman /admin/presensi/rekap yang merender PDF
 * rekap presensi bulanan guru lalu mengirimkannya ke grup WhatsApp dan
 * grup Telegram.
 *
 * Kenapa butuh migrasi sendiri dan bukan hanya ditambah ke seed
 * 20261004120000: migrasi seed itu sudah pernah jalan di instalasi ini,
 * jadi menambah baris di sana tidak mengubah database yang sudah ada.
 * Tanpa baris ini, NotifChannelConfigModel::isEnabled('rekap_bulanan', …)
 * jatuh ke fail-open — dokumen tetap terkirim ke kanal yang sengaja
 * dimatikan admin di Pengaturan Notifikasi.
 *
 * Graceful: kalau tabelnya belum ada (migrate seed belum dijalankan),
 * dilewati tanpa error supaya instalasi baru tidak ikut gagal.
 */
class AddRekapBulananNotifChannel extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('notif_channel_config')) {
            return;
        }

        $ada = $this->db->table('notif_channel_config')
            ->where('jenis', 'rekap_bulanan')
            ->countAllResults() > 0;

        if ($ada) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('notif_channel_config')->insert([
            'jenis'       => 'rekap_bulanan',
            'tg_grup'     => 1,
            'wa_grup'     => 1,
            // Grup-only: PDF rekap bolanan tidak pernah dikirim per-guru.
            'tg_personal' => 0,
            'wa_personal' => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('notif_channel_config')) {
            return;
        }

        $this->db->table('notif_channel_config')
            ->where('jenis', 'rekap_bulanan')
            ->delete();
    }
}
