<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Integrasi gateway WhatsApp OpenWA.
 *
 * Menambahkan dua hal yang belum ada:
 *
 *  1. Konfigurasi OpenWA di `absensi_setting`. Kolom WA yang lama
 *     (wa_gateway_url / wa_token / wa_target_number) dipakai Fonnte dan
 *     dibiarkan apa adanya — menjaga instalasi lama yang masih memakai
 *     Fonnte tetap jalan. Mode memilih kanal aktif:
 *     - wa_gateway_mode: 'off' | 'openwa' | 'fonnte' (default 'off')
 *     - wa_base_url     : URL dasar OpenWA, mis. http://gateway:2785
 *     - wa_api_key      : X-API-Key OpenWA (role OPERATOR)
 *     - wa_session_name : nama sesi yang dipakai (opsional; fallback ke
 *                        sesi "ready" pertama)
 *
 *  2. Nomor WhatsApp personal per user di `user_notifikasi`, sejajar
 *     dengan telegram_chat_id yang sudah ada. WA tidak punya webhook
 *     inbound yang murah seperti bot Telegram, jadi penautan dilakukan
 *     manual: guru mengetik nomornya, admin mengonfirmasi. Karena itu
 *     tidak ada wa_link_token seperti Telegram — cukup satu nomor.
 *     - wa_number    : digit saja, mis. 6281234567890
 *     - wa_verified  : 1 bila admin sudah mengonfirmasi nomornya
 *
 *  Kolom `wa_number` di `master_guru` ditambahkan hanya sebagai catatan
 *  tampil di UI admin, persis seperti `telegram_id` — sumber runtime-nya
 *  tetap `user_notifikasi`.
 */
class AddOpenWAGateway extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('absensi_setting')) {
            foreach ($this->settingFields() as $name => $def) {
                if (! $this->db->fieldExists($name, 'absensi_setting')) {
                    $this->forge->addColumn('absensi_setting', [$name => $def]);
                }
            }
        }

        if ($this->db->tableExists('user_notifikasi')) {
            foreach ($this->notifikasiFields() as $name => $def) {
                if (! $this->db->fieldExists($name, 'user_notifikasi')) {
                    $this->forge->addColumn('user_notifikasi', [$name => $def]);
                }
            }
        }

        if ($this->db->tableExists('master_guru')) {
            if (! $this->db->fieldExists('wa_number', 'master_guru')) {
                $this->forge->addColumn('master_guru', [
                    'wa_number' => [
                        'type'       => 'VARCHAR',
                        'constraint' => '20',
                        'null'       => true,
                        'comment'    => 'Nomor WhatsApp guru (digit saja, kode negara tanpa 0)',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('master_guru') && $this->db->fieldExists('wa_number', 'master_guru')) {
            $this->forge->dropColumn('master_guru', 'wa_number');
        }
        if ($this->db->tableExists('user_notifikasi')) {
            foreach (['wa_number', 'wa_verified'] as $col) {
                if ($this->db->fieldExists($col, 'user_notifikasi')) {
                    $this->forge->dropColumn('user_notifikasi', $col);
                }
            }
        }
        if ($this->db->tableExists('absensi_setting')) {
            foreach (array_keys($this->settingFields()) as $col) {
                if ($this->db->fieldExists($col, 'absensi_setting')) {
                    $this->forge->dropColumn('absensi_setting', $col);
                }
            }
        }
    }

    private function settingFields(): array
    {
        return [
            'wa_gateway_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'off',
                'null'       => true,
                'comment'    => 'off | openwa | fonnte',
                'after'      => 'wa_target_number',
            ],
            'wa_base_url' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'URL dasar OpenWA, mis. http://gateway:2785 (suffix /api ditambahkan otomatis)',
                'after'      => 'wa_gateway_mode',
            ],
            'wa_api_key' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'X-API-Key OpenWA dengan role OPERATOR',
                'after'      => 'wa_base_url',
            ],
            'wa_session_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'Nama sesi OpenWA yang dipakai; kosong = ambil sesi ready pertama',
                'after'      => 'wa_api_key',
            ],
        ];
    }

    private function notifikasiFields(): array
    {
        return [
            'wa_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
                'comment'    => 'Nomor WhatsApp tujuan notifikasi personal (digit saja)',
                'after'      => 'telegram_chat_id',
            ],
            'wa_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => true,
                'comment'    => '1 bila admin sudah mengonfirmasi nomor ini milik user tersebut',
                'after'      => 'wa_number',
            ],
        ];
    }
}