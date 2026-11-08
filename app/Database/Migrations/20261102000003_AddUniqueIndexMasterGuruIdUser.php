<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * UNIQUE index pada master_guru.id_user.
 *
 * Beberapa rekap guru JOIN absensi_harian → users → users_groups → master_guru
 * dan menjumlahkan kolomnya. Satu user yang punya dua baris master_guru akan
 * membuat setiap SUM berlipat dua. users_groups, kelas_siswa, presensi_siswa
 * dan absensi_harian sudah punya penjaga keunikan; master_guru.id_user belum,
 * dan jalur tulisnya (Dataguru, DapodikSync, AuthModel) semuanya INSERT tanpa
 * cek — jadi duplikat tinggal menunggu waktu.
 *
 * Migration ini sengaja TIDAK menghapus data. Kalau duplikat ditemukan,
 * index tidak dipasang dan daftarnya ditulis di log supaya admin memutuskan
 * baris mana yang benar (id_guru dipakai sebagai referensi di tabel lain,
 * jadi menghapusnya bukan keputusan yang bisa taken migration).
 */
class AddUniqueIndexMasterGuruIdUser extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('master_guru') || !$this->db->fieldExists('id_user', 'master_guru')) {
            return;
        }

        if ($this->sudahUnique('master_guru', ['id_user'])) {
            return;
        }

        $dupes = $this->db->query(
            "SELECT id_user, COUNT(*) AS c, GROUP_CONCAT(id_guru ORDER BY id_guru) AS id_guru_list
             FROM master_guru
             WHERE id_user IS NOT NULL AND id_user <> 0
             GROUP BY id_user HAVING c > 1
             ORDER BY c DESC"
        )->getResult();

        if (!empty($dupes)) {
            $ringkas = [];
            foreach ($dupes as $d) {
                $ringkas[] = 'id_user=' . (int) $d->id_user . ' (' . (int) $d->c . ' baris: id_guru ' . $d->id_guru_list . ')';
            }
            log_message('error', '[AddUniqueIndexMasterGuruIdUser] index DIBATALKAN, ada guru ganda: '
                . implode(', ', $ringkas));
            return;
        }

        $this->db->query("ALTER TABLE master_guru ADD UNIQUE KEY `uq_mg_id_user` (`id_user`)");
    }

    public function down()
    {
        if (!$this->db->tableExists('master_guru')) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE master_guru DROP INDEX `uq_mg_id_user`");
        } catch (\Throwable $e) {
            // Index memang belum ada di instalasi ini.
        }
    }

    private function sudahUnique(string $table, array $kolom): bool
    {
        $map = [];
        foreach ($this->db->query("SHOW INDEX FROM {$table}")->getResult() as $r) {
            if ((int) ($r->Non_unique ?? 1) !== 0) {
                continue;
            }
            $map[(string) ($r->Key_name ?? '')][] = (string) ($r->Column_name ?? '');
        }
        foreach ($map as $cols) {
            sort($cols);
            if ($cols === $kolom) {
                return true;
            }
        }
        return false;
    }
}
