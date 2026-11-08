<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRewardTables extends Migration
{
    public function up()
    {
        // Reward Tiers
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_tier' => ['type' => 'VARCHAR', 'constraint' => 50],
            'poin_min' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'poin_max' => ['type' => 'INT', 'constraint' => 11, 'default' => 999999],
            'badge' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'bi-star'],
            'warna' => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#6b7280'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('reward_tiers', true);

        // Rewards Catalog
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 255],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'poin_dibutuhkan' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'stok' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'gambar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('rewards', true);

        // Reward Redemptions
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11],
            'reward_id' => ['type' => 'INT', 'constraint' => 11],
            'poin_digunakan' => ['type' => 'INT', 'constraint' => 11],
            'status' => ["type" => "ENUM('PENDING','APPROVED','REJECTED','CLAIMED')", 'default' => 'PENDING'],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'approved_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'claimed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('reward_redemptions', true);

        // Poin History
        // 'tipe' sengaja VARCHAR, bukan ENUM: penulisan poin tersebar di
        // belasan jalur (kehadiran, denda, voucher, refund, manual) dan ENUM
        // lama sudah ketinggalan nilai yang benar-benar dipakai produksi
        // (REFUND, KEHADIRAN, ALPHA, CUTI, PENDING) — di install fresh nilai
        // itu akan ditolak, dan tanpa DISABLE_INSERT_MODELS insert gagal
        // setengah jalan. Struktur ini harus sama dengan yang dibuat
        // PoinHistoryModel::ensureTable() dan dengan kolom di produksi.
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'poin' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ref_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('tipe');
        $this->forge->addKey('created_at');
        $this->forge->createTable('poin_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('poin_history');
        $this->forge->dropTable('reward_redemptions');
        $this->forge->dropTable('rewards');
        $this->forge->dropTable('reward_tiers');
    }
}
