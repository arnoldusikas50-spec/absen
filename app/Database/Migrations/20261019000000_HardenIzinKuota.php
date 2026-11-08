<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Memisahkan aturan kuota izin yang tadinya bercampur, dan menandai asal
 * baris absensi yang ditulis oleh sebuah pengajuan.
 *
 * 1. Batas per pengajuan dipisah dari batas tahunan.
 *    `max_izin_hari` (label "Hari / Tahun") selama ini dipakai dua kali:
 *    sebagai batas durasi satu pengajuan (Attendance::leaveRequest) DAN
 *    sebagai kuota kumulatif setahun. Default 3 berarti satu pengajuan
 *    3 hari langsung menghabiskan kuota setahun. Sekarang tiap jenis punya
 *    `max_*_per_pengajuan` sendiri, terpisah dari kuota tahunan.
 *
 * 2. `max_sakit_hari` menutup asimetri: sakit/dinas sebelumnya tanpa
 *    batas sama sekali, sementara izin dibatasi 3 hari/tahun.
 *
 * 3. `kuota_hari_kerja` membuat basis hitung eksplisit. Default 1 =
 *    hitung hanya hari kerja efektif, sama dengan yang ditulis ke
 *    absensi_harian saat approve (hari libur dilewati). Sebelumnya kuota
 *    menghitung hari kalender sehingga guru kehilangan kuota untuk
 *    akhir pekan yang tidak menghasilkan baris absensi sama sekali.
 *
 * 4. `absensi_harian.izin_id` menandai baris yang dibuat oleh pengajuan
 *    tertentu. Tanpa penanda, approve lalu delete izin bisa menghapus
 *    scan asli guru: approve mengosongkan jam_masuk, lalu deletePermit
 *    melihat "jam kosong + status izin" dan menganggapnya baris otomatis.
 */
class HardenIzinKuota extends Migration
{
    private const KOLOM_SETTING = [
        'max_izin_per_pengajuan'  => 3,
        'max_cuti_per_pengajuan'  => 12,
        'max_sakit_per_pengajuan' => 3,
        'max_dinas_per_pengajuan' => 30,
        'max_sakit_hari'           => 12,
    ];

    private const INDEX_HARIAN = 'idx_harian_izin_id';

    public function up()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if ($db->tableExists('absensi_setting')) {
            $db->resetDataCache();
            foreach (self::KOLOM_SETTING as $kolom => $default) {
                if ($db->fieldExists($kolom, 'absensi_setting')) {
                    continue;
                }
                $forge->addColumn('absensi_setting', [
                    $kolom => ['type' => 'INT', 'null' => true, 'default' => $default],
                ]);
            }

            // Basis hitung kuota: 1 = hanya hari kerja efektif (default,
            // menyamakan kuota dengan baris absensi yang benar-benar ditulis).
            if (! $db->fieldExists('kuota_hari_kerja', 'absensi_setting')) {
                $forge->addColumn('absensi_setting', [
                    'kuota_hari_kerja' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => 1],
                ]);
            }
        }

        // Normalisasi status izin sekali saja di sini.
        //
        // Nilai lama 0/1/2 (dan NULL/kosong) masih mungkin ada di instalasi
        // yang sudah lama. patchAbsensiIzinTable() di controller sudah
        // menangani ini, tapi routine itu hanya dipanggil dari controller
        // admin — leaveRequest() guru tidak pernah memanggilnya, sehingga
        // perhitungan kuota bisa melewatkan baris yang statusnya masih
        // angka dan membiarkan hari lama tidak ikut terpotong.
        if ($db->tableExists('absensi_izin')) {
            try {
                $db->query("UPDATE absensi_izin SET status = 'pending' WHERE status = '0' OR status IS NULL OR status = ''");
                $db->query("UPDATE absensi_izin SET status = 'approved' WHERE status = '1'");
                $db->query("UPDATE absensi_izin SET status = 'rejected' WHERE status = '2'");
            } catch (\Throwable $e) {
                log_message('error', '[migrate] normalisasi status izin gagal: ' . $e->getMessage());
            }
        }

        if (! $db->tableExists('absensi_harian')) {
            return;
        }

        $db->resetDataCache();
        if (! $db->fieldExists('izin_id', 'absensi_harian')) {
            $forge->addColumn('absensi_harian', [
                'izin_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
        }

        if (! $this->sudahAdaIndex($db, 'absensi_harian', self::INDEX_HARIAN)) {
            $this->forge->addKey(['izin_id'], false, false, self::INDEX_HARIAN);
            $this->forge->processIndexes('absensi_harian');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->resetDataCache();

        if ($db->tableExists('absensi_harian')) {
            if ($this->sudahAdaIndex($db, 'absensi_harian', self::INDEX_HARIAN)) {
                $this->forge->dropKey('absensi_harian', self::INDEX_HARIAN, false);
            }
            if ($db->fieldExists('izin_id', 'absensi_harian')) {
                $this->forge->dropColumn('absensi_harian', 'izin_id');
            }
        }

        if ($db->tableExists('absensi_setting')) {
            $kolom = array_keys(self::KOLOM_SETTING);
            $kolom[] = 'kuota_hari_kerja';
            foreach ($kolom as $k) {
                if ($db->fieldExists($k, 'absensi_setting')) {
                    $this->forge->dropColumn('absensi_setting', $k);
                }
            }
        }
    }

    private function sudahAdaIndex($db, string $table, string $indexName): bool
    {
        try {
            if ($db->DBDriver === 'MySQLi' || $db->DBDriver === 'MySQL') {
                $res = $db->query(
                    'SHOW INDEX FROM ' . $db->prefixTable($table)
                    . " WHERE Key_name = '" . addslashes($indexName) . "'"
                );
                return $res !== false && $res->getNumRows() > 0;
            }

            if ($db->DBDriver === 'SQLite3') {
                $res = $db->query("PRAGMA index_list('" . $db->prefixTable($table) . "')");
                if ($res === false) {
                    return false;
                }
                foreach ($res->getResultArray() as $row) {
                    if (($row['name'] ?? '') === $indexName) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[migrate] gagal memeriksa index ' . $indexName . ': ' . $e->getMessage());
        }

        return false;
    }
}