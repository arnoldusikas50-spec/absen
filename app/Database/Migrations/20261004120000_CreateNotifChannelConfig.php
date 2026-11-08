<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Konfigurasi channel notifikasi per jenis.
 *
 * Setiap baris = satu jenis notifikasi. Empat kolom TINYINT(1) merepresentasikan
 * apakah channel itu aktif:
 *   - tg_grup      : Telegram ke grup/channel sekolah
 *   - wa_grup      : WhatsApp ke nomor/grup tujuan broadcast
 *   - tg_personal  : Telegram ke chat_id personal guru
 *   - wa_personal  : WhatsApp ke nomor personal guru (wa_verified = 1)
 *
 * Default semua = 1 (aktif) saat baris pertama kali dibuat oleh model,
 * kecuali jenis yang secara desain tidak punya target personal
 * (rekap_harian, rekap_denda_mingguan, rekap_denda_bulanan, rekap_bulanan,
 *  ucapan_pagi, ucapan_sore, lupa_checkout, kehadiran_rendah) — kedua
 * kolom personal-nya di-seed = 0.
 *
 * Jenis yang dikenal (JENIS_LIST di NotifChannelConfigModel):
 *   keterlambatan, denda, izin, koreksi, alpha, teguran, dinas,
 *   guru_terawal, rekap_harian, rekap_denda_mingguan, rekap_denda_bulanan,
 *   rekap_bulanan, ucapan_pagi, ucapan_sore, lupa_checkout, kehadiran_rendah
 *
 * CATATAN: migrasi ini sudah pernah jalan di instalasi yang ada, jadi
 * menambah/mengubah jenis di sini tidak mengubah database lama. Untuk
 * instalasi yang sudah migrate, barisnya ditambahkan oleh migrasi
 * 20261005120000_AddRekapBulananNotifChannel dan
 * 20261006120000_SplitRekapDendaChannel (yang terakhir juga mempensiunkan
 * baris gabungan 'rekap_denda').
 */
class CreateNotifChannelConfig extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'jenis' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'comment'    => 'Identifikasi unik jenis notifikasi',
            ],
            'tg_grup' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '1 = kirim ke Telegram grup sekolah',
            ],
            'wa_grup' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '1 = kirim ke WhatsApp grup/nomor broadcast',
            ],
            'tg_personal' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '1 = kirim ke Telegram personal guru',
            ],
            'wa_personal' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '1 = kirim ke WhatsApp personal guru',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('jenis');
        $this->forge->createTable('notif_channel_config', true);

        // Seed default: semua jenis dengan nilai default-nya
        $now = date('Y-m-d H:i:s');
        $rows = [
            // Jenis yang punya target personal
            ['jenis' => 'keterlambatan', 'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'denda',         'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'izin',          'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'koreksi',       'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'alpha',         'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'teguran',       'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'dinas',         'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            ['jenis' => 'guru_terawal',  'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 1, 'wa_personal' => 1],
            // Jenis yang hanya ke grup (personal = 0 by design)
            ['jenis' => 'rekap_harian',    'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'rekap_denda_mingguan', 'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'rekap_denda_bulanan',  'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'rekap_bulanan',   'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'ucapan_pagi',     'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'ucapan_sore',     'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'lupa_checkout',   'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'kehadiran_rendah','tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
            ['jenis' => 'rekap_piket',     'tg_grup' => 1, 'wa_grup' => 1, 'tg_personal' => 0, 'wa_personal' => 0],
        ];
        foreach ($rows as &$r) {
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }
        unset($r);

        // createTable() di atas memakai IF NOT EXISTS, jadi migrasi ini bisa
        // berjalan lagi di DB yang tabelnya sudah ada (mis. batch sebelumnya
        // gagal setelah membuat tabel). Seed harus ikut idempoten: baris
        // yang jenis-nya sudah ada dilewati, kalau tidak insertBatch menabrak
        // unique key `jenis` dan seluruh `spark migrate` gagal.
        $sudahAda = [];
        if ($this->db->tableExists('notif_channel_config')) {
            foreach ($this->db->table('notif_channel_config')->select('jenis')->get()->getResultArray() as $ada) {
                $sudahAda[(string) $ada['jenis']] = true;
            }
            $rows = array_values(array_filter(
                $rows,
                static fn(array $r): bool => !isset($sudahAda[(string) $r['jenis']])
            ));
        }

        if ($rows !== []) {
            $this->db->table('notif_channel_config')->insertBatch($rows);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('notif_channel_config', true);
    }
}
