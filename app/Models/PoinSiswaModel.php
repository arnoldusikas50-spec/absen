<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modul Poin Kedisiplinan Siswa
 * Skor = SKOR_AWAL (100) + SUM(poin_siswa_history.poin)
 * Kunci identitas: master_siswa.id_siswa (selaras presensi_siswa).
 */
class PoinSiswaModel extends Model
{
    public const SKOR_AWAL = 100;

    protected $table = 'poin_siswa_history';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id_siswa', 'id_kelas', 'poin', 'kategori_id', 'tipe',
        'keterangan', 'ref_id', 'created_by', 'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Buat tabel + kolom cache bila belum ada (pola runtime-ensure,
     * karena spark migrate tidak selalu dijalankan di server).
     */
    public function ensureTables(): void
    {
        $db = $this->db;
        if (!$db->tableExists('disiplin_kategori')) {
            $db->query("CREATE TABLE IF NOT EXISTS `disiplin_kategori` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `nama` VARCHAR(100) NOT NULL,
                `jenis` ENUM('PELANGGARAN','PENGHARGAAN') NOT NULL DEFAULT 'PELANGGARAN',
                `bobot` INT(11) NOT NULL DEFAULT 5,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`), KEY `jenis` (`jenis`), KEY `is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $this->seedDefaultKategori();
        }
        if (!$db->tableExists('poin_siswa_history')) {
            $db->query("CREATE TABLE IF NOT EXISTS `poin_siswa_history` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_siswa` INT(11) NOT NULL,
                `id_kelas` INT(11) DEFAULT NULL,
                `poin` INT(11) NOT NULL DEFAULT 0,
                `kategori_id` INT(11) UNSIGNED DEFAULT NULL,
                `tipe` ENUM('PELANGGARAN','PENGHARGAAN','KOREKSI','PRESENSI') NOT NULL DEFAULT 'PELANGGARAN',
                `keterangan` VARCHAR(255) DEFAULT NULL,
                `ref_id` VARCHAR(80) DEFAULT NULL UNIQUE,
                `created_by` INT(11) DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`), KEY `id_siswa` (`id_siswa`), KEY `tipe` (`tipe`), KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        if ($db->tableExists('master_siswa') && !$db->fieldExists('poin_disiplin', 'master_siswa')) {
            try {
                $db->query("ALTER TABLE `master_siswa` ADD COLUMN `poin_disiplin` INT(11) NOT NULL DEFAULT 100");
            } catch (\Throwable $e) {
                log_message('error', '[PoinSiswaModel::ensureTables] ' . $e->getMessage());
            }
        }
    }

    /**
     * Skor terkini siswa (100 + akumulasi ledger).
     *
     * null bila ledger tidak terbaca. Dulu mengembalikan SKOR_AWAL pada
     * kegagalan: query yang error membuat siswa berpoin -60 terlihat bersih
     * kembali, dan updateCache() lalu menulis angka palsu itu ke
     * master_siswa.poin_disiplin.
     */
    public function getSkor(int $idSiswa): ?int
    {
        if ($idSiswa <= 0) {
            return null;
        }
        try {
            $row = $this->db->table('poin_siswa_history')
                ->selectSum('poin', 'total')
                ->where('id_siswa', $idSiswa)
                ->get()->getRow();
            return self::SKOR_AWAL + (int) ($row->total ?? 0);
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::getSkor#' . $idSiswa . '] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kunci baris siswa untuk transaksi poin.
     *
     * Ambang "skor tidak boleh di bawah 0" dibaca lalu ditulis. Tanpa kunci,
     * dua pencatatan paralel sama-sama melihat skor 5 dan keduanya lolos
     * sehingga skor final jadi negatif.
     */
    private function kunciSiswa(object $db, int $idSiswa): void
    {
        try {
            $db->query(
                'SELECT id_siswa FROM ' . $db->prefixTable('master_siswa') . ' WHERE id_siswa = ? FOR UPDATE',
                [$idSiswa]
            );
        } catch (\Throwable $e) {
            // SQLite tidak mendukung FOR UPDATE; di sana transaksi sudah seri.
        }
    }

    /**
     * Rombel aktif siswa pada periode berjalan (bukan nilai kiriman klien).
     */
    public function kelasAktifSiswa(int $idSiswa): ?int
    {
        if ($idSiswa <= 0) {
            return null;
        }
        try {
            $dash = new DashboardModel();
            $tp = $dash->getTahunActive();
            $smt = $dash->getSemesterActive();
            if (!$tp || !$smt) {
                return null;
            }
            $row = $this->db->table('kelas_siswa')
                ->select('id_kelas')
                ->where('id_siswa', $idSiswa)
                ->where('id_tp', (int) $tp->id_tp)
                ->where('id_smt', (int) $smt->id_smt)
                ->get()->getRowArray();
            $idKelas = (int) ($row['id_kelas'] ?? 0);
            return $idKelas > 0 ? $idKelas : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Sinkron kolom cache master_siswa.poin_disiplin.
     * Tidak menulis apa pun bila skor terkini tidak terbaca — menulis 100
     * hasil fallback justru menghapus jejak poin siswa di kolom cache.
     */
    public function updateCache(int $idSiswa): ?int
    {
        $skor = $this->getSkor($idSiswa);
        if ($skor === null) {
            return null;
        }
        try {
            if ($this->db->tableExists('master_siswa') && $this->db->fieldExists('poin_disiplin', 'master_siswa')) {
                $this->db->table('master_siswa')->where('id_siswa', $idSiswa)->update(['poin_disiplin' => $skor]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::updateCache] ' . $e->getMessage());
        }
        return $skor;
    }

    /**
     * Riwayat terbaru siswa (join nama kategori).
     */
    public function getRiwayat(int $idSiswa, int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        try {
            return $this->db->table('poin_siswa_history h')
                ->select('h.*, k.nama as kategori_nama, k.jenis as kategori_jenis')
                ->join('disiplin_kategori k', 'k.id = h.kategori_id', 'left')
                ->where('h.id_siswa', $idSiswa)
                ->orderBy('h.created_at', 'DESC')
                ->orderBy('h.id', 'DESC')
                ->limit($limit)
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::getRiwayat] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Leaderboard skor per kelas (atau semua).
     * Kembalikan [{id_siswa, nama, nis, nama_kelas, skor, total_kasus}]
     *
     * Dulu join ke kelas_siswa lalu groupBy('s.id_siswa'): tanpa filter
     * TP/SMT satu siswa punya banyak baris enrollment (riwayat rombel),
     * sehingga nama_kelas yang tampil acak dari periode mana pun, dan
     * kolom non-agregat di SELECT gugur di ONLY_FULL_GROUP_BY — model
     * menelan error itu dan halaman hanya menampilkan daftar kosong.
     */
    public function getLeaderboard(?int $idKelas = null, int $limit = 200): array
    {
        try {
            $tp = null;
            $smt = null;
            try {
                $dash = new DashboardModel();
                $tpRow = $dash->getTahunActive();
                $smtRow = $dash->getSemesterActive();
                $tp = $tpRow ? (int) $tpRow->id_tp : null;
                $smt = $smtRow ? (int) $smtRow->id_smt : null;
            } catch (\Throwable $e) {
            }

            $ketentuanKelas = 'ks.id_siswa = s.id_siswa AND ks.id_kelas IS NOT NULL';
            if ($tp && $smt) {
                $ketentuanKelas .= ' AND ks.id_tp = ' . $tp . ' AND ks.id_smt = ' . $smt;
            }

            $b = $this->db->table('master_siswa s')
                ->select('s.id_siswa, s.nama, s.nis, s.foto,
                    (SELECT c.nama_kelas FROM kelas_siswa ks
                        JOIN master_kelas c ON c.id_kelas = ks.id_kelas
                        WHERE ' . $ketentuanKelas . '
                        ORDER BY ks.id_kelas_siswa DESC LIMIT 1) as nama_kelas,
                    COALESCE((SELECT SUM(h.poin) FROM poin_siswa_history h WHERE h.id_siswa = s.id_siswa), 0) as akumulasi,
                    (SELECT COUNT(*) FROM poin_siswa_history h WHERE h.id_siswa = s.id_siswa) as total_kasus');

            // Hanya siswa AKTIF (buku_induk.status = '1'); siswa tanpa buku induk dianggap aktif.
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $b->where("(SELECT COUNT(*) FROM buku_induk bi WHERE bi.id_siswa = s.id_siswa AND bi.status <> '1') = 0", null, false);
                }
            } catch (\Throwable $e) {
            }

            if ($idKelas) {
                // Kondisi utuh apa adanya: where() biasa memotong string pada
                // tanda '=' lalu meng-escape backtick potongan kirinya, sehingga
                // subquery berkorelasi jadi sintaks salah dan get() mengembalikan
                // false — filter rombel diam-diam selalu kosong.
                $b->where('(SELECT ks2.id_kelas FROM kelas_siswa ks2 WHERE ks2.id_siswa = s.id_siswa AND ks2.id_kelas IS NOT NULL'
                    . ($tp && $smt ? ' AND ks2.id_tp = ' . $tp . ' AND ks2.id_smt = ' . $smt : '')
                    . ' ORDER BY ks2.id_kelas_siswa DESC LIMIT 1) = ' . (int) $idKelas, null, false);
            }

            $hasil = $b->orderBy('akumulasi', 'DESC')->orderBy('s.nama', 'ASC')
                ->limit(max(1, min($limit, 500)))
                ->get();
            if ($hasil === false) {
                // get() menelan exception DB; tanpa cek ini leaderboard jadi
                // kosong tanpa alasan yang bisa dilacak.
                log_message('error', '[PoinSiswaModel::getLeaderboard] query gagal: ' . json_encode($this->db->error()));
                return [];
            }
            $rows = $hasil->getResult();
            foreach ($rows as $r) {
                $r->skor = self::SKOR_AWAL + (int) ($r->akumulasi ?? 0);
            }
            return $rows;
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::getLeaderboard] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Agregasi bulanan setahun untuk grafik detail.
     */
    public function getMonthly(int $idSiswa, int $tahun): array
    {
        $out = array_fill(1, 12, 0);
        try {
            $rows = $this->db->table('poin_siswa_history')
                ->select('MONTH(created_at) as bln, SUM(poin) as total')
                ->where('id_siswa', $idSiswa)
                ->where('YEAR(created_at)', $tahun)
                ->groupBy('bln')->get()->getResult();
            foreach ($rows as $r) {
                $out[(int) $r->bln] = (int) $r->total;
            }
        } catch (\Throwable $e) {
            // abaikan, kembalikan nol
        }
        return $out;
    }

    /**
     * Ringkasan per tipe setahun: [tipe => {poin, count}]
     */
    public function getTypeSummary(int $idSiswa, int $tahun): array
    {
        try {
            $rows = $this->db->table('poin_siswa_history')
                ->select('tipe, SUM(poin) as poin, COUNT(*) as jumlah')
                ->where('id_siswa', $idSiswa)
                ->where('YEAR(created_at)', $tahun)
                ->groupBy('tipe')->get()->getResult();
            $out = [];
            foreach ($rows as $r) {
                $out[$r->tipe] = ['poin' => (int) $r->poin, 'count' => (int) $r->jumlah];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Master kategori ──────────────────────────────────────────

    public function getKategori(bool $activeOnly = true): array
    {
        try {
            $b = $this->db->table('disiplin_kategori')->orderBy('jenis', 'ASC')->orderBy('bobot', 'DESC')->orderBy('nama', 'ASC');
            if ($activeOnly) {
                $b->where('is_active', 1);
            }
            return $b->get()->getResult();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveKategori(array $data, ?int $id = null): array
    {
        $nama = trim((string) ($data['nama'] ?? ''));
        $jenis = strtoupper(trim((string) ($data['jenis'] ?? '')));
        $bobot = (int) ($data['bobot'] ?? 0);
        $active = (int) ($data['is_active'] ?? 1) === 1 ? 1 : 0;

        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
            return ['status' => false, 'message' => 'Nama kategori 3–100 karakter'];
        }
        if (!in_array($jenis, ['PELANGGARAN', 'PENGHARGAAN'], true)) {
            return ['status' => false, 'message' => 'Jenis tidak valid'];
        }
        if ($bobot < 1 || $bobot > 100) {
            return ['status' => false, 'message' => 'Bobot harus 1–100 poin'];
        }
        try {
            $dup = $this->db->table('disiplin_kategori')->where('LOWER(nama)', strtolower($nama));
            if ($id) {
                $dup->where('id !=', $id);
            }
            if ($dup->countAllResults() > 0) {
                return ['status' => false, 'message' => 'Nama kategori sudah ada'];
            }
            $row = [
                'nama' => $nama, 'jenis' => $jenis, 'bobot' => $bobot,
                'is_active' => $active, 'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($id) {
                if ($this->db->table('disiplin_kategori')->where('id', $id)->countAllResults() === 0) {
                    return ['status' => false, 'message' => 'Kategori tidak ditemukan'];
                }
                if ($this->db->table('disiplin_kategori')->where('id', $id)->update($row) === false) {
                    log_message('error', '[PoinSiswaModel::saveKategori] update gagal id=' . $id . ' ' . json_encode($this->db->error()));
                    return ['status' => false, 'message' => 'Gagal memperbarui kategori'];
                }
                return ['status' => true, 'message' => 'Kategori diperbarui'];
            }
            $row['created_at'] = date('Y-m-d H:i:s');
            if ($this->db->table('disiplin_kategori')->insert($row) === false) {
                log_message('error', '[PoinSiswaModel::saveKategori] insert gagal ' . json_encode($this->db->error()));
                return ['status' => false, 'message' => 'Gagal menambahkan kategori'];
            }
            return ['status' => true, 'message' => 'Kategori ditambahkan'];
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::saveKategori] ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal menyimpan kategori'];
        }
    }

    public function deleteKategori(int $id): array
    {
        try {
            if ($id <= 0 || $this->db->table('disiplin_kategori')->where('id', $id)->countAllResults() === 0) {
                return ['status' => false, 'message' => 'Kategori tidak ditemukan'];
            }
            $used = $this->db->table('poin_siswa_history')->where('kategori_id', $id)->countAllResults() > 0;
            if ($used) {
                return ['status' => false, 'message' => 'Kategori sudah dipakai di riwayat — nonaktifkan saja'];
            }
            if ($this->db->table('disiplin_kategori')->where('id', $id)->delete() === false) {
                log_message('error', '[PoinSiswaModel::deleteKategori] gagal id=' . $id . ' ' . json_encode($this->db->error()));
                return ['status' => false, 'message' => 'Gagal menghapus kategori'];
            }
            return ['status' => true, 'message' => 'Kategori dihapus'];
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::deleteKategori] ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal menghapus kategori'];
        }
    }

    // ── Pencatatan ───────────────────────────────────────────────

    /**
     * Catat poin manual — HANYA dari master kategori aktif (master saja).
     */
    public function recordManual(int $idSiswa, int $kategoriId, string $keterangan, ?int $createdBy = null): array
    {
        $keterangan = trim($keterangan);
        if ($idSiswa <= 0) {
            return ['status' => false, 'message' => 'Siswa tidak valid'];
        }
        if (mb_strlen($keterangan) < 5 || mb_strlen($keterangan) > 255) {
            return ['status' => false, 'message' => 'Keterangan 5–255 karakter'];
        }

        try {
            // Siswa harus benar-benar ada. Tanpa ini id_siswa karangan punya
            // "skor 100" sendiri dan ledger-nya tidak akan pernah tampil di
            // halaman mana pun (semua laporan join ke master_siswa).
            $ada = $this->db->table('master_siswa')->select('id_siswa')
                ->where('id_siswa', $idSiswa)->get()->getRowArray();
            if (!$ada) {
                return ['status' => false, 'message' => 'Siswa tidak ditemukan'];
            }

            // Hanya siswa AKTIF boleh dicatat poinnya.
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $st = $this->db->table('buku_induk')->select('status')
                        ->where('id_siswa', $idSiswa)->get()->getRow();
                    if ($st && (string) ($st->status ?? '1') !== '1') {
                        return ['status' => false, 'message' => 'Siswa sudah tidak aktif (lulus/pindah/keluar)'];
                    }
                }
            } catch (\Throwable $e) {
            }

            $kat = $this->db->table('disiplin_kategori')
                ->where('id', $kategoriId)->where('is_active', 1)->get()->getRow();
            if (!$kat) {
                return ['status' => false, 'message' => 'Kategori tidak valid / nonaktif'];
            }
            $poin = $kat->jenis === 'PENGHARGAAN' ? abs((int) $kat->bobot) : -abs((int) $kat->bobot);

            $db = $this->db;
            $db->transBegin();
            try {
                $this->kunciSiswa($db, $idSiswa);

                $skorSekarang = $this->getSkor($idSiswa);
                if ($skorSekarang === null) {
                    $db->transRollback();
                    return ['status' => false, 'message' => 'Skor terkini gagal dibaca — coba lagi sebentar'];
                }
                if ($skorSekarang + $poin < 0) {
                    $db->transRollback();
                    return ['status' => false, 'message' => 'Ditolak: skor tidak boleh di bawah 0'];
                }

                $tertulis = $db->table('poin_siswa_history')->insert([
                    'id_siswa' => $idSiswa,
                    'id_kelas' => $this->kelasAktifSiswa($idSiswa),
                    'poin' => $poin,
                    'kategori_id' => $kat->id,
                    'tipe' => $kat->jenis,
                    'keterangan' => $keterangan,
                    'ref_id' => 'manual-' . $kat->id . '-' . $idSiswa . '-' . time() . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
                    'created_by' => $createdBy,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($tertulis === false || $db->transStatus() === false) {
                    $db->transRollback();
                    log_message('error', '[PoinSiswaModel::recordManual] insert gagal siswa=' . $idSiswa . ' ' . json_encode($db->error()));
                    return ['status' => false, 'message' => 'Gagal menyimpan — tidak ada poin yang tercatat'];
                }
                $db->transCommit();

                $skor = $this->updateCache($idSiswa);
                return [
                    'status' => true,
                    'message' => 'Tersimpan (' . ($poin > 0 ? '+' : '') . $poin . ' poin)',
                    'skor' => $skor,
                ];
            } catch (\Throwable $e) {
                try { $db->transRollback(); } catch (\Throwable $e2) {
                }
                throw $e;
            }
        } catch (\Throwable $e) {
            log_message('error', '[PoinSiswaModel::recordManual] ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal menyimpan'];
        }
    }

    /**
     * Sinkron satu baris presensi_siswa → ledger.
     *
     * Diperbarui: dulu hanya menangani ALPA/TANPA_KETERANGAN, sekarang juga
     * keterlambatan. Logikanya dipegang SiswaPoinService (batas jam memakai
     * jam_masuk + grace_period, sama dengan rekap) dan bersifat rebuild —
     * entri lama dihapus dulu, jadi aman dipanggil berulang dan ikut membatalkan
     * pengurangan yang tak lagi berlaku.
     *
     * Return: ['status'=>bool, 'skipped'=>bool, 'message'=>string]
     */
    public function syncPresensi(object $row): array
    {
        $id = (int) ($row->id ?? 0);
        if ($id <= 0) {
            return ['status' => false, 'skipped' => true, 'message' => 'ID presensi tidak valid'];
        }
        $res = (new \App\Services\SiswaPoinService())->recalc($id);
        return [
            'status'  => (bool) $res['status'],
            'skipped' => (bool) $res['skipped'],
            'message' => (string) $res['message'],
        ];
    }

    private function seedDefaultKategori(): void
    {
        $defaults = [
            ['Terlambat masuk sekolah', 'PELANGGARAN', 5],
            ['Tidak memakai seragam lengkap/atribut', 'PELANGGARAN', 5],
            ['Tidak mengerjakan tugas/PR', 'PELANGGARAN', 5],
            ['Keluar kelas tanpa izin', 'PELANGGARAN', 10],
            ['Merokok di lingkungan sekolah', 'PELANGGARAN', 25],
            ['Berkelahi / tawuran', 'PELANGGARAN', 50],
            ['Bolos sekolah', 'PELANGGARAN', 25],
            ['Membawa HP tanpa izin', 'PELANGGARAN', 10],
            ['Juara lomba / prestasi akademik', 'PENGHARGAAN', 20],
            ['Prestasi non-akademik / ekstrakurikuler', 'PENGHARGAAN', 15],
            ['Membantu kegiatan sekolah', 'PENGHARGAAN', 10],
            ['Hafalan / prestasi tahfidz', 'PENGHARGAAN', 15],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($defaults as [$nama, $jenis, $bobot]) {
            try {
                $exists = $this->db->table('disiplin_kategori')
                    ->where('LOWER(nama)', strtolower($nama))->countAllResults() > 0;
                if (!$exists) {
                    $this->db->table('disiplin_kategori')->insert([
                        'nama' => $nama, 'jenis' => $jenis, 'bobot' => $bobot,
                        'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            } catch (\Throwable $e) {
                // abaikan per-baris
            }
        }
    }
}
