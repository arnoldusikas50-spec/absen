<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pecah switch channel 'rekap_denda' menjadi dua baris independen:
 * 'rekap_denda_mingguan' (cron Sabtu 18:05) dan 'rekap_denda_bulanan'
 * (cron tgl-28 18:10 + tombol kirim PDF).
 *
 * Satu switch gabungan membuat admin tidak bisa mematikan rekap mingguan
 * tanpa ikut mematikan yang bulanan. Nilai admin yang sudah ada disalin
 * ke kedua baris baru supaya tidak ada perubahan perilaku saat upgrade,
 * lalu baris lama dihapus agar tidak membingungkan (isEnabled() untuk
 * jenis tak dikenal fail-open, jadi baris yatim hanya menutupi niat).
 *
 * Graceful: kalau tabelnya belum ada (migrasi seed belum dijalankan),
 * dilewati — NotifChannelConfigModel::seedMissing() membuat baris baru
 * otomatis dengan default semua-aktif.
 */
class SplitRekapDendaChannel extends Migration
{
    private const LAMA = 'rekap_denda';

    private const BARU = ['rekap_denda_mingguan', 'rekap_denda_bulanan'];

    public function up(): void
    {
        if (! $this->db->tableExists('notif_channel_config')) {
            return;
        }

        $tabel = $this->db->table('notif_channel_config');

        $lama = $tabel->where('jenis', self::LAMA)->get()->getRow();

        $now = date('Y-m-d H:i:s');
        foreach (self::BARU as $jenis) {
            $ada = $this->db->table('notif_channel_config')
                ->where('jenis', $jenis)
                ->countAllResults() > 0;
            if ($ada) {
                continue;
            }
            $this->db->table('notif_channel_config')->insert([
                'jenis'       => $jenis,
                'tg_grup'     => (int) ($lama->tg_grup ?? 1),
                'wa_grup'     => (int) ($lama->wa_grup ?? 1),
                // Grup-only, sama seperti baris lama.
                'tg_personal' => 0,
                'wa_personal' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $this->db->table('notif_channel_config')
            ->where('jenis', self::LAMA)
            ->delete();
    }

    public function down(): void
    {
        if (! $this->db->tableExists('notif_channel_config')) {
            return;
        }

        $acuan = $this->db->table('notif_channel_config')
            ->where('jenis', 'rekap_denda_bulanan')
            ->get()->getRow();

        foreach (self::BARU as $jenis) {
            $this->db->table('notif_channel_config')
                ->where('jenis', $jenis)
                ->delete();
        }

        $ada = $this->db->table('notif_channel_config')
            ->where('jenis', self::LAMA)
            ->countAllResults() > 0;
        if (! $ada) {
            $now = date('Y-m-d H:i:s');
            $this->db->table('notif_channel_config')->insert([
                'jenis'       => self::LAMA,
                'tg_grup'     => (int) ($acuan->tg_grup ?? 1),
                'wa_grup'     => (int) ($acuan->wa_grup ?? 1),
                'tg_personal' => 0,
                'wa_personal' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }
}
