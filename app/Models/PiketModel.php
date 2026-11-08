<?php

namespace App\Models;

use CodeIgniter\Model;

class PiketModel extends Model
{
    protected $table = 'piket_kejadian';
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Filter baris ke siswa AKTIF saja (buku_induk.status = '1').
     * LULUS (2) / PINDAH (3) / KELUAR (4) dikecualikan dari daftar,
     * rekap, dan perhitungan. Tanpa baris buku_induk (data lama)
     * dianggap AKTIF. $exprSiswa = ekspresi kolom id_siswa
     * (mis. 'ps.id_siswa', 'ks.id_siswa', 'p.id_siswa').
     */
    private function filterSiswaAktif($builder, string $exprSiswa): void
    {
        try {
            if (!$this->db->tableExists('buku_induk')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }
        $builder->join('buku_induk bi_aktif', "bi_aktif.id_siswa = {$exprSiswa}", 'left')
            ->groupStart()
                ->where('bi_aktif.status', '1')
                ->orWhere('bi_aktif.id_siswa IS NULL', null, false)
            ->groupEnd();
    }

    public function initialize()
    {
        $forge = \Config\Database::forge();

        // 1. Table piket_tamu
        if (!$this->db->tableExists('piket_tamu')) {
            $forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'tanggal' => ['type' => 'DATE'],
                'jam_masuk' => ['type' => 'TIME', 'null' => true],
                'jam_keluar' => ['type' => 'TIME', 'null' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 255],
                'instansi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'bertemu' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'keperluan' => ['type' => 'TEXT', 'null' => true],
                'foto' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ]);
            $forge->addKey('id', true);
            $forge->addKey('tanggal');
            $forge->createTable('piket_tamu', true);
        }

        // 2. Table piket_kejadian
        if (!$this->db->tableExists('piket_kejadian')) {
            $forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'tanggal' => ['type' => 'DATE'],
                'jam' => ['type' => 'TIME', 'null' => true],
                'jenis' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'Pelanggaran, Kejadian, Kecelakaan, Lainnya'],
                'pelapor' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'terlapor_siswa_id' => ['type' => 'INT', 'null' => true],
                'terlapor_lain' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'penanganan' => ['type' => 'TEXT', 'null' => true],
                'tindak_lanjut' => ['type' => 'TEXT', 'null' => true],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ]);
            $forge->addKey('id', true);
            $forge->addKey('tanggal');
            $forge->createTable('piket_kejadian', true);
        } else {
            // Patch existing table with new columns
            $this->safeAddColumn('piket_kejadian', 'tingkat', ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ringan', 'after' => 'jenis']);
            $this->safeAddColumn('piket_kejadian', 'status', ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'belum', 'after' => 'tindak_lanjut']);
        }

        // 3. Table piket_absensi (Rekap harian siswa manual by piket)
        if (!$this->db->tableExists('piket_absensi')) {
            $forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'tanggal' => ['type' => 'DATE'],
                'id_kelas' => ['type' => 'INT'],
                'id_siswa' => ['type' => 'INT'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 5, 'comment' => 'H, S, I, A, T'],
                'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ]);
            $forge->addKey('id', true);
            $forge->addKey(['tanggal', 'id_siswa'], false, true); // Unique per day per student
            $forge->createTable('piket_absensi', true);
        } else {
            $this->safeAddColumn('piket_absensi', 'created_by', ['type' => 'INT', 'constraint' => 11, 'null' => true]);
            $this->safeAddColumn('piket_absensi', 'updated_by', ['type' => 'INT', 'constraint' => 11, 'null' => true]);
        }
        // 4. Table piket_presensi_guru (Manual check by Piket)
        if (!$this->db->tableExists('piket_presensi_guru')) {
            $forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'tanggal' => ['type' => 'DATE'],
                'id_kelas' => ['type' => 'INT'],
                'jam_ke' => ['type' => 'INT'],
                'id_mapel' => ['type' => 'INT'],
                'id_guru' => ['type' => 'INT'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => 'H, S, I, A'],
                'keterangan' => ['type' => 'TEXT', 'null' => true],
                'is_auto' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ]);
            $forge->addKey('id', true);
            $forge->addKey(['tanggal', 'id_kelas', 'jam_ke'], false, true); // Unique slot per day
            $forge->createTable('piket_presensi_guru', true);
        } else {
            if (!$this->db->fieldExists('is_auto', 'piket_presensi_guru')) {
                $forge->addColumn('piket_presensi_guru', [
                    'is_auto' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                ]);
            }
            // Audit pencatat (diisi migrasi resmi; best-effort di sini)
            $this->safeAddColumn('piket_presensi_guru', 'created_by', ['type' => 'INT', 'constraint' => 11, 'null' => true]);
            $this->safeAddColumn('piket_presensi_guru', 'updated_by', ['type' => 'INT', 'constraint' => 11, 'null' => true]);
        }
    }

    private function safeAddColumn($table, $column, $definition)
    {
        if ($this->db->fieldExists($column, $table)) return;
        try {
            \Config\Database::forge()->addColumn($table, [$column => $definition]);
        } catch (\Throwable $e) {
            log_message('error', "safeAddColumn({$table}.{$column}): " . $e->getMessage());
        }
    }

    // --- Piket Presensi Guru Methods ---
    public function getPresensiGuru($tanggal)
    {
        return $this->db->table('piket_presensi_guru')
            ->where('tanggal', $tanggal)
            ->get()->getResult();
    }

    /**
     * Peta [id_kelas][jam_ke][id_mapel] => row. Kunci '*' = fallback baris lama
     * (sebelum unique per mapel) agar konsumen lama tetap jalan.
     */
    public function getPresensiGuruMap(string $tanggal): array
    {
        $rows = $this->db->table('piket_presensi_guru')
            ->where('tanggal', $tanggal)
            ->get()->getResult();

        $map = [];
        foreach ($rows as $r) {
            $k = (int) $r->id_kelas;
            $j = (int) $r->jam_ke;
            $m = isset($r->id_mapel) ? (int) $r->id_mapel : 0;
            $map[$k][$j][$m] = $r;
            $map[$k][$j]['*'] ??= $r;
        }
        return $map;
    }

    public static function isDuplicateKeyError(\Throwable $e): bool
    {
        $m = strtolower($e->getMessage() . ' ' . $e->getCode());
        return str_contains($m, 'duplicate') || str_contains($m, '1062')
            || str_contains($m, 'unique constraint');
    }

    /**
     * Upsert anti-race: coba update dulu, bila tak ada baris insert; bila
     * insert tabrakan unique key (dua piket bersamaan), fallback update.
     */
    public function savePresensiGuru($data)
    {
        $builder = $this->db->table('piket_presensi_guru');
        $where = [
            'tanggal' => $data['tanggal'],
            'id_kelas' => $data['id_kelas'],
            'jam_ke' => $data['jam_ke'],
            'id_mapel' => $data['id_mapel'],
        ];

        $row = [
            'id_mapel' => $data['id_mapel'],
            'id_guru' => $data['id_guru'],
            'status' => $data['status'],
            'keterangan' => $data['keterangan'],
            'is_auto' => (int) ($data['is_auto'] ?? 0),
        ];
        // updated_by milik baris (boleh NULL bila kolom belum ada / anonim).
        // created_by HANYA untuk insert — update tak boleh menimpa pembuat awal.
        if (array_key_exists('updated_by', $data)) {
            try {
                if ($this->db->fieldExists('updated_by', 'piket_presensi_guru')) {
                    $row['updated_by'] = $data['updated_by'];
                }
            } catch (\Throwable $e) {
            }
        }

        $existing = $builder->where($where)->get()->getRow();
        if ($existing) {
            return $this->db->table('piket_presensi_guru')
                ->where('id', $existing->id)
                ->update($row);
        }

        $insert = array_merge($where, $row);
        try {
            if ($this->db->fieldExists('created_by', 'piket_presensi_guru')
                && isset($data['updated_by'])) {
                $insert['created_by'] = $data['updated_by'];
            }
        } catch (\Throwable $e) {
        }
        try {
            return $this->db->table('piket_presensi_guru')->insert($insert);
        } catch (\Throwable $e) {
            if (!self::isDuplicateKeyError($e)) throw $e;
            // Balapan: baris sudah disisipkan piket lain → update milik kita
            $retry = $this->db->table('piket_presensi_guru')->where($where)->get()->getRow();
            if (!$retry) throw $e;
            return $this->db->table('piket_presensi_guru')
                ->where('id', $retry->id)
                ->update($row);
        }
    }
    // --- Rekonsiliasi piket_absensi (patokan) vs presensi_siswa (formal) ---
    // Isian guru piket = data utama: S/I/A piket selalu menimpa status
    // formal (jam scan dipertahankan); H/T tak pernah difabrikasi; konflik
    // di-flag untuk ditinjau.

    /** Peta status piket → status formal. H/T tak dipetakan (butuh jam). */
    public const PIKET_KE_FORMAL = ['S' => 'SAKIT', 'I' => 'IZIN', 'A' => 'ALPA', 'B' => 'BDR'];

    /** Peta balik formal → piket (untuk resolusi: piket ikut sistem). */
    public const FORMAL_KE_PIKET = ['HADIR' => 'H', 'SAKIT' => 'S', 'IZIN' => 'I', 'ALPA' => 'A', 'BDR' => 'B'];

    public static function formalKePiket(?string $formal, bool $adaJamMasuk): ?string
    {
        if ($adaJamMasuk) return 'H'; // jam tercatat = hadir, apa pun labelnya
        $formal = strtoupper(trim((string) ($formal ?? '')));
        return self::FORMAL_KE_PIKET[$formal] ?? null;
    }

    public static function piketKeFormal(string $st): ?string
    {
        $st = strtoupper(trim($st));
        return self::PIKET_KE_FORMAL[$st] ?? null;
    }

    /**
     * Klasifikasi satu pasangan piket vs formal.
     * @return null bila selaras, atau kode konflik:
     *  - 'HADIR_VS_NONHADIR': piket hadir (H/T), formal tak hadir
     *  - 'NONHADIR_VS_HADIR': piket tak hadir (S/I/A), formal hadir
     */
    public static function konflikKehadiran(string $piket, ?string $formal, bool $adaJamMasuk): ?string
    {
        $piket = strtoupper(trim($piket));
        $piketHadir = in_array($piket, ['H', 'T'], true);
        $formalHadir = ($formal === 'HADIR') || $adaJamMasuk;
        if ($piketHadir && !$formalHadir) return 'HADIR_VS_NONHADIR';
        if (!$piketHadir && $formalHadir) return 'NONHADIR_VS_HADIR';
        return null;
    }

    /**
     * Sinkron S/I/A piket → presensi_siswa sebagai DATA PATOKAN.
     *
     * Isian guru piket adalah data utama: status formal (SAKIT/IZIN/ALPA)
     * selalu mengikuti status piket, termasuk menimpa status yang sudah
     * terisi (mis. HADIR dari scan). Yang TIDAK disentuh:
     *   - jam_masuk/jam_keluar hasil scan QR (bukti kehadiran fisik),
     *   - H/T piket (tak difabrikasi jadi status formal),
     *   - siswa non-AKTIF.
     * Setiap penimpaan menandai sumber_masuk='piket' + keterangan supaya
     * jejak asalnya jelas di rekap. Best-effort: kegagalan per baris
     * dicatat, bukan dilempar.
     * @return array{dibuat:int, diupdate:int, dilewati:int}
     */
    public function syncPiketKeFormal(array $batch, string $tanggal): array
    {
        $hasil = ['dibuat' => 0, 'diupdate' => 0, 'dilewati' => 0];
        if (empty($batch) || !$this->db->tableExists('presensi_siswa')) return $hasil;

        $bisaSumber = false;
        try {
            $bisaSumber = $this->db->fieldExists('sumber_masuk', 'presensi_siswa');
        } catch (\Throwable $e) {
        }
        $now = date('Y-m-d H:i:s');
        $poinIds = [];

        foreach ($batch as $row) {
            $formal = self::piketKeFormal((string) ($row['status'] ?? ''));
            if ($formal === null) continue; // H/T: tak difabrikasi
            $idSiswa = (int) ($row['id_siswa'] ?? 0);
            $idKelas = (int) ($row['id_kelas'] ?? 0);
            if ($idSiswa <= 0 || $idKelas <= 0) {
                $hasil['dilewati']++;
                continue;
            }
            // Lewati siswa non-AKTIF (LULUS/PINDAH/KELUAR).
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $st = $this->db->table('buku_induk')->select('status')
                        ->where('id_siswa', $idSiswa)->get()->getRow();
                    if ($st && (string) ($st->status ?? '1') !== '1') {
                        $hasil['dilewati']++;
                        continue;
                    }
                }
            } catch (\Throwable $e2) {
            }
            try {
                $existing = $this->db->table('presensi_siswa')
                    ->where('id_siswa', $idSiswa)
                    ->where('tgl', $tanggal)
                    ->get()->getRow();
                if (!$existing) {
                    $data = [
                        'id_kelas' => $idKelas,
                        'id_siswa' => $idSiswa,
                        'id_mapel' => null,
                        'id_guru' => null,
                        'tgl' => $tanggal,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'status' => $formal,
                        'keterangan' => 'Sinkron absensi piket (patokan)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($bisaSumber) $data['sumber_masuk'] = 'piket';
                    $this->db->table('presensi_siswa')->insert($data);
                    $newId = (int) $this->db->insertID();
                    if ($newId > 0) $poinIds[] = $newId;
                    $hasil['dibuat']++;
                } elseif (strtoupper(trim((string) ($existing->status ?? ''))) === $formal) {
                    // Sudah selaras — jangan sentuh (hindari churn updated_at
                    // + hitung ulang poin yang sia-sia).
                    $hasil['dilewati']++;
                } else {
                    // Piket = patokan: timpa status formal apa pun.
                    // Jam scan (masuk/keluar) dipertahankan sebagai bukti.
                    $upd = ['status' => $formal, 'keterangan' => 'Sinkron absensi piket (patokan)', 'updated_at' => $now];
                    if ($bisaSumber) $upd['sumber_masuk'] = 'piket';
                    $this->db->table('presensi_siswa')->where('id', $existing->id)->update($upd);
                    $poinIds[] = (int) $existing->id;
                    $hasil['diupdate']++;
                }
            } catch (\Throwable $e) {
                log_message('error', '[PiketModel::syncPiketKeFormal] siswa=' . $idSiswa . ' ' . $e->getMessage());
                $hasil['dilewati']++;
            }
        }

        foreach ($poinIds as $sid) {
            try {
                \App\Services\SiswaPoinService::sinkron($sid);
                \App\Services\SiswaDendaService::sinkron($sid);
            } catch (\Throwable $e) {
            }
        }
        return $hasil;
    }

    /**
     * Sinkron OTOMATIS status TELAT formal → piket.
     *
     * Siswa yang scan masuk melewati ambang (jam_masuk setting +
     * grace_period, via SiswaPoinService::menitTerlambat — rumus yang sama
     * dengan rekap & poin) otomatis dicatat 'T' di piket_absensi, lengkap
     * dengan menit keterlambatan di keterangan.
     *
     * Aturan aman (tak menimpa isian manual):
     *  - HANYA mengisi bila belum ada baris piket (tanggal+id_siswa).
     *    Baris yang sudah ada — termasuk 'H' — tak pernah diubah, karena
     *    'H' manual tak bisa dibedakan dari default dan keputusan piket
     *    selalu menang.
     *  - S/I/A formal (izin resmi) tak pernah jadi 'T'.
     *
     * Best-effort: kegagalan per baris dicatat, bukan dilempar.
     * @param int[] $onlyIds bila diisi, hanya sinkron id tersebut
     * @return array{ditandai:int, dilewati:int}
     */
    public function syncTelatOtomatis(string $tanggal, ?int $idKelas = null, array $onlyIds = []): array
    {
        $hasil = ['ditandai' => 0, 'dilewati' => 0];
        try {
            if (!$this->db->tableExists('presensi_siswa') || !$this->db->tableExists('piket_absensi')) {
                return $hasil;
            }
            $b = $this->db->table('presensi_siswa ps')
                ->select('ps.id, ps.id_siswa, ps.id_kelas, ps.status, ps.jam_masuk')
                ->where('ps.tgl', $tanggal)
                ->where('ps.jam_masuk IS NOT NULL');
            // Hanya siswa AKTIF.
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $b->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e2) {
            }
            if ($idKelas !== null && $idKelas > 0) {
                $b->where('ps.id_kelas', $idKelas);
            }
            $onlyIds = array_values(array_filter(array_map('intval', $onlyIds), static fn($v) => $v > 0));
            if (!empty($onlyIds)) {
                $b->whereIn('ps.id_siswa', $onlyIds);
            }
            $rows = $b->get()->getResult();
            if (empty($rows)) return $hasil;

            $poin = new \App\Services\SiswaPoinService();
            $hasKet = false;
            $hasCreatedBy = false;
            $hasUpdatedBy = false;
            try {
                $hasKet = $this->db->fieldExists('keterangan', 'piket_absensi');
                $hasCreatedBy = $this->db->fieldExists('created_by', 'piket_absensi');
                $hasUpdatedBy = $this->db->fieldExists('updated_by', 'piket_absensi');
            } catch (\Throwable $e) {
            }
            $now = date('Y-m-d H:i:s');

            foreach ($rows as $row) {
                try {
                    $telat = $poin->menitTerlambat($row->jam_masuk ?? null, $row->status ?? null, $row->tgl ?? $tanggal);
                    if ($telat <= 0) {
                        $hasil['dilewati']++;
                        continue;
                    }
                    $idSiswa = (int) $row->id_siswa;
                    $ada = $this->db->table('piket_absensi')
                        ->where('tanggal', $tanggal)
                        ->where('id_siswa', $idSiswa)
                        ->countAllResults() > 0;
                    if ($ada) {
                        $hasil['dilewati']++;
                        continue; // isian piket (manual) menang
                    }
                    $data = [
                        'tanggal' => $tanggal,
                        'id_kelas' => (int) $row->id_kelas,
                        'id_siswa' => $idSiswa,
                        'status' => 'T',
                    ];
                    if ($hasCreatedBy) $data['created_by'] = null;
                    if ($hasUpdatedBy) $data['updated_by'] = null;
                    if ($hasKet) {
                        $data['keterangan'] = 'Otomatis: terlambat ' . $telat . ' menit (scan ' . substr((string) $row->jam_masuk, 0, 5) . ')';
                    }
                    $this->db->table('piket_absensi')->insert($data);
                    $hasil['ditandai']++;
                } catch (\Throwable $e) {
                    log_message('error', '[PiketModel::syncTelatOtomatis] siswa=' . ($row->id_siswa ?? '?') . ' ' . $e->getMessage());
                    $hasil['dilewati']++;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::syncTelatOtomatis] ' . $e->getMessage());
        }
        return $hasil;
    }

    /**
     * Sinkron OTOMATIS status ALPHA (atau BDR) untuk siswa yang belum scan.
     *
     * "Belum scan" = tanpa baris presensi_siswa pada tanggal itu, atau
     * baris kosong (TANPA_KETERANGAN tanpa jam_masuk). Siswa berstatus
     * HADIR/SAKIT/IZIN/ALPA/BDR sudah terhitung → dilewati.
     *
     * Kelas yang termasuk event BDR kalender akademik ('B', mis. kelas X & XI
     * saat ujian kelas XII) ditandai BDR, bukan ALPHA, agar tidak muncul di
     * rekap alpha, poin, maupun surat panggilan.
     *
     * Aturan kelayakan (anti alpha salah sasaran):
     *  - tanggal lampau → selalu layak (scan QR tak bisa susulan).
     *  - hari ini → layak hanya bila jam sekarang sudah lewat jam_pulang
     *    setting (sekolah selesai; yang belum scan = alpha). Pagi/siang
     *    hari "belum scan" masih berarti "mungkin menyusul".
     *  - tanggal depan → tak pernah layak.
     *  - hari libur (mingguan/nasional/jadwal) → tak pernah layak.
     *
     * Seperti sync telat: HANYA mengisi baris piket yang belum ada; isian
     * manual piket selalu menang. Batch baris yang dibuat dikembalikan agar
     * pemanggil bisa meneruskannya ke presensi formal (ALPA + poin).
     *
     * @return array{layak:bool, alasan:string, ditandai:int, bdr:int, dilewati:int, batch:array}
     */
    public function syncAlphaOtomatis(string $tanggal, ?int $idKelas = null, array $onlyIds = [], int $tpId = 0, int $smtId = 0): array
    {
        $hasil = ['layak' => false, 'alasan' => '', 'ditandai' => 0, 'bdr' => 0, 'dilewati' => 0, 'batch' => []];
        try {
            if (!$this->db->tableExists('presensi_siswa') || !$this->db->tableExists('piket_absensi')) {
                $hasil['alasan'] = 'tabel belum siap';
                return $hasil;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $hasil['alasan'] = 'tanggal tidak valid';
                return $hasil;
            }
            $today = date('Y-m-d');
            if ($tanggal > $today) {
                $hasil['alasan'] = 'tanggal masa depan';
                return $hasil;
            }

            // Libur → bukan hari sekolah, tak ada alpha. Satu sumber kebenaran:
            // AttendancePolicy (hari_libur_mingguan ternormalisasi ATAU
            // absensi_jadwal_kerja.is_libur ATAU absensi_hari_libur).
            $alasanLibur = \App\Services\AttendancePolicy::alasanLibur($tanggal);
            if ($alasanLibur !== null) {
                $hasil['alasan'] = $alasanLibur;
                return $hasil;
            }
            $jamPulang = \App\Services\AttendancePolicy::jadwalSiswa($tanggal)['jam_pulang'];

            // Hari ini → tunggu sekolah selesai.
            if ($tanggal === $today && date('H:i:s') < substr($jamPulang, 0, 8)) {
                $hasil['alasan'] = 'sekolah belum selesai (cutoff ' . substr($jamPulang, 0, 5) . ')';
                return $hasil;
            }
            $hasil['layak'] = true;

            // Kelas yang hari itu BDR (belajar dari rumah) tidak dianggap alpha.
            $bdrByKelas = [];
            try {
                $bdrByKelas = (new \App\Services\PresensiBdrService())->kelasTerdampak($tanggal);
            } catch (\Throwable $e) {
                log_message('error', '[PiketModel::syncAlphaOtomatis] BDR: ' . $e->getMessage());
            }

            // Roster siswa dalam lingkup — hanya yang AKTIF.
            $qb = $this->db->table('kelas_siswa ks')->select('ks.id_siswa, ks.id_kelas');
            if ($idKelas !== null && $idKelas > 0) {
                $qb->where('ks.id_kelas', $idKelas);
            }
            try {
                if ($tpId > 0 && $this->db->fieldExists('id_tp', 'kelas_siswa')) $qb->where('ks.id_tp', $tpId);
                if ($smtId > 0 && $this->db->fieldExists('id_smt', 'kelas_siswa')) $qb->where('ks.id_smt', $smtId);
            } catch (\Throwable $e) {
            }
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $qb->join('buku_induk bi', 'bi.id_siswa = ks.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e2) {
            }
            $onlyIds = array_values(array_filter(array_map('intval', $onlyIds), static fn($v) => $v > 0));
            if (!empty($onlyIds)) {
                $qb->whereIn('ks.id_siswa', $onlyIds);
            }
            $roster = $qb->get()->getResult();
            if (empty($roster)) {
                $hasil['alasan'] = 'tanpa siswa dalam lingkup';
                return $hasil;
            }

            // Baris formal yang sudah akuntabel (ada jam ATAU status final).
            $formal = [];
            $ids = array_map(static fn($r) => (int) $r->id_siswa, $roster);
            foreach (array_chunk($ids, 500) as $chunk) {
                $fb = $this->db->table('presensi_siswa')
                    ->select('id_siswa, status, jam_masuk')
                    ->where('tgl', $tanggal)
                    ->whereIn('id_siswa', $chunk);
                if ($idKelas !== null && $idKelas > 0) {
                    $fb->where('id_kelas', $idKelas);
                }
                foreach ($fb->get()->getResult() as $r) {
                    $formal[(int) $r->id_siswa] = $r;
                }
            }

            $hasKet = false;
            $hasCreatedBy = false;
            $hasUpdatedBy = false;
            try {
                $hasKet = $this->db->fieldExists('keterangan', 'piket_absensi');
                $hasCreatedBy = $this->db->fieldExists('created_by', 'piket_absensi');
                $hasUpdatedBy = $this->db->fieldExists('updated_by', 'piket_absensi');
            } catch (\Throwable $e) {
            }

            foreach ($roster as $ang) {
                $idSiswa = (int) $ang->id_siswa;
                try {
                    $f = $formal[$idSiswa] ?? null;
                    if ($f !== null) {
                        $st = strtoupper(trim((string) ($f->status ?? '')));
                        // Baris kosong (placeholder tanpa jam) = belum scan.
                        if (!($st === 'TANPA_KETERANGAN' && empty($f->jam_masuk))) {
                            $hasil['dilewati']++;
                            continue;
                        }
                    }
                    $ada = $this->db->table('piket_absensi')
                        ->where('tanggal', $tanggal)
                        ->where('id_siswa', $idSiswa)
                        ->countAllResults() > 0;
                    if ($ada) {
                        $hasil['dilewati']++;
                        continue; // isian piket (manual) menang
                    }
                    $idKelasSiswa = (int) $ang->id_kelas;
                    $evtBdr = $bdrByKelas[$idKelasSiswa] ?? null;
                    $statusBaru = $evtBdr !== null ? 'B' : 'A';
                    $keterangan = $evtBdr !== null
                        ? 'Otomatis: belajar dari rumah (' . trim((string) ($evtBdr->judul ?? '')) . ')'
                        : 'Otomatis: tanpa scan masuk';
                    $data = [
                        'tanggal' => $tanggal,
                        'id_kelas' => $idKelasSiswa,
                        'id_siswa' => $idSiswa,
                        'status' => $statusBaru,
                    ];
                    if ($hasCreatedBy) $data['created_by'] = null;
                    if ($hasUpdatedBy) $data['updated_by'] = null;
                    if ($hasKet) $data['keterangan'] = $keterangan;
                    $this->db->table('piket_absensi')->insert($data);
                    $hasil['ditandai']++;
                    if ($statusBaru === 'B') $hasil['bdr']++;
                    $hasil['batch'][] = [
                        'tanggal' => $tanggal,
                        'id_kelas' => $idKelasSiswa,
                        'id_siswa' => $idSiswa,
                        'status' => $statusBaru,
                        'updated_by' => null,
                    ];
                } catch (\Throwable $e) {
                    log_message('error', '[PiketModel::syncAlphaOtomatis] siswa=' . $idSiswa . ' ' . $e->getMessage());
                    $hasil['dilewati']++;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::syncAlphaOtomatis] ' . $e->getMessage());
            $hasil['alasan'] = 'kesalahan sistem';
        }
        return $hasil;
    }

    /**
     * Terapkan jadwal BDR ke absensi yang TERLANJUR ditandai alpha.
     *
     * Dipakai bila jadwal BDR baru dibuat setelah cron berjalan (mis. esok
     * paginya admin ingat bahwa kemarin kelas X/Ujian susulan). Yang diubah
     * hanya baris tanpa jam masuk berstatus ALPA/TANPA_KETERANGAN — siswa yang
     * ternyata datang (punya jam_masuk/scan) dan status SAKIT/IZIN dibiarkan.
     * Poin otomatis dihitung ulang supaya pengurangan alpha hilang.
     *
     * @return array{ditandai:int, dilewati:int}
     */
    public function terapkanBdr(string $tanggal): array
    {
        $hasil = ['ditandai' => 0, 'dilewati' => 0];
        try {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)
                || ! $this->db->tableExists('presensi_siswa')) {
                return $hasil;
            }
            $peta = (new \App\Services\PresensiBdrService())->kelasTerdampak($tanggal);
            if ($peta === []) {
                return $hasil;
            }

            $b = $this->db->table('presensi_siswa')
                ->select('id, id_siswa, id_kelas, keterangan')
                ->where('tgl', $tanggal)
                ->whereIn('id_kelas', array_map(static fn ($k) => (int) $k, array_keys($peta)))
                ->whereIn('status', ['ALPA', 'TANPA_KETERANGAN'])
                ->groupStart()
                    ->where('jam_masuk IS NULL', null, false)
                    ->orWhere("jam_masuk = ''", null, false)
                ->groupEnd();
            $rows = $b->get()->getResult();

            $bisaKet = $this->db->fieldExists('keterangan', 'presensi_siswa');
            $adaPiket = $this->db->tableExists('piket_absensi');
            $poinIds = [];

            foreach ($rows as $r) {
                try {
                    $idKelas = (int) $r->id_kelas;
                    $judul = trim((string) ($peta[$idKelas]->judul ?? '')) ?: 'Belajar dari rumah';
                    $upd = ['status' => 'BDR', 'updated_at' => date('Y-m-d H:i:s')];
                    if ($bisaKet) {
                        $upd['keterangan'] = 'Belajar dari rumah: ' . $judul;
                    }
                    $this->db->table('presensi_siswa')->where('id', (int) $r->id)->update($upd);
                    $poinIds[] = (int) $r->id;

                    if ($adaPiket) {
                        // Baris piket 'A' hasil cron ikut dibetulkan agar dua
                        // sumber data tidak salingbantah saat direkap piket.
                        $this->db->table('piket_absensi')
                            ->where('tanggal', $tanggal)
                            ->where('id_siswa', (int) $r->id_siswa)
                            ->where('UPPER(TRIM(status))', 'A')
                            ->update(['status' => 'B', 'keterangan' => 'Otomatis: belajar dari rumah (' . $judul . ')']);
                    }
                    $hasil['ditandai']++;
                } catch (\Throwable $e) {
                    log_message('error', '[PiketModel::terapkanBdr] siswa=' . (int) $r->id_siswa . ' ' . $e->getMessage());
                    $hasil['dilewati']++;
                }
            }

            foreach ($poinIds as $presensiId) {
                try {
                    \App\Services\SiswaPoinService::sinkron($presensiId);
                    \App\Services\SiswaDendaService::sinkron($presensiId);
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::terapkanBdr] ' . $e->getMessage());
        }
        return $hasil;
    }

    /**
     * Rekap kehadiran REAL siswa per kelas untuk tanggal (satu sekolah).
     *
     * Read-only untuk tab Siswa di monitoring piket: total anggota,
     * sudah scan masuk (jam_masuk terisi), sudah pulang (jam_keluar
     * terisi), dan belum scan. Dua-tiga query agregat, bukan N+1.
     *
     * @return array<int, object> list per kelas
     */
    public function getRealSiswaRekapSekolah(string $tanggal, int $tpId, int $smtId): array
    {
        try {
            $kelas = $this->db->table('master_kelas')
                ->select('id_kelas, nama_kelas')
                ->where('id_tp', $tpId)
                ->where('id_smt', $smtId)
                ->where('deleted_at IS NULL')
                ->orderBy('nama_kelas', 'ASC')
                ->get()->getResult();
            if (empty($kelas)) return [];

            $agg = $this->db->table('kelas_siswa')
                ->select('id_kelas, COUNT(*) AS total')
                ->whereIn('id_kelas', array_map(static fn($k) => (int) $k->id_kelas, $kelas));
            try {
                if ($this->db->fieldExists('id_tp', 'kelas_siswa')) $agg->where('id_tp', $tpId);
                if ($this->db->fieldExists('id_smt', 'kelas_siswa')) $agg->where('id_smt', $smtId);
            } catch (\Throwable $e) {
            }
            $this->filterSiswaAktif($agg, 'kelas_siswa.id_siswa');
            $anggota = [];
            foreach ($agg->groupBy('id_kelas')->get()->getResult() as $r) {
                $anggota[(int) $r->id_kelas] = (int) $r->total;
            }

            $stat = [];
            if ($this->db->tableExists('presensi_siswa')) {
                $rows = $this->db->table('presensi_siswa')
                    ->select("presensi_siswa.id_kelas,
                        SUM(CASE WHEN presensi_siswa.jam_masuk IS NOT NULL AND presensi_siswa.jam_masuk <> '' THEN 1 ELSE 0 END) AS masuk,
                        SUM(CASE WHEN presensi_siswa.jam_keluar IS NOT NULL AND presensi_siswa.jam_keluar <> '' THEN 1 ELSE 0 END) AS pulang,
                        SUM(CASE WHEN presensi_siswa.status = 'HADIR' THEN 1 ELSE 0 END) AS hadir,
                        SUM(CASE WHEN presensi_siswa.status IN ('SAKIT','IZIN','ALPA','TANPA_KETERANGAN') THEN 1 ELSE 0 END) AS nonhadir,
                        SUM(CASE WHEN presensi_siswa.status = 'BDR' THEN 1 ELSE 0 END) AS bdr", false)
                    ->where('presensi_siswa.tgl', $tanggal)
                    ->whereIn('presensi_siswa.id_kelas', array_map(static fn($k) => (int) $k->id_kelas, $kelas));
                $this->filterSiswaAktif($rows, 'presensi_siswa.id_siswa');
                $rows = $rows->groupBy('presensi_siswa.id_kelas')
                    ->get()->getResult();
                foreach ($rows as $r) $stat[(int) $r->id_kelas] = $r;
            }

            $out = [];
            foreach ($kelas as $k) {
                $id = (int) $k->id_kelas;
                $total = $anggota[$id] ?? 0;
                $s = $stat[$id] ?? null;
                $masuk = (int) ($s->masuk ?? 0);
                $out[] = (object) [
                    'id_kelas' => $id,
                    'nama_kelas' => $k->nama_kelas,
                    'total' => $total,
                    'masuk' => $masuk,
                    'pulang' => (int) ($s->pulang ?? 0),
                    'hadir' => (int) ($s->hadir ?? 0),
                    'nonhadir' => (int) ($s->nonhadir ?? 0),
                    'bdr' => (int) ($s->bdr ?? 0),
                    'belum' => max(0, $total - $masuk),
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getRealSiswaRekapSekolah] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rekap kehadiran SISWA per kelas untuk laporan piket: status piket
     * (H/S/I/A/T) + hasil scan real (masuk/pulang) + belum terisi.
     *
     * Tambahan breakdown REAL dari presensi_siswa (r_*): klasifikasi
     * kondisi sebenarnya — hadir tepat, telat (lewat batas jam_masuk +
     * grace), sakit, izin, alpha, tanpa absen pulang, belum scan.
     *
     * "Belum terisi" = tanpa baris piket (piket belum mencatat); digabung
     * dengan belum-scan agar laporan jujur soal kelengkapan data.
     *
     * @return array{kelas:object[], total:array}
     */
    public function getRekapSiswaLaporan(string $tanggal, int $tpId, int $smtId, int $filterKelas = 0): array
    {
        $kosong = ['total' => 0, 'H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'T' => 0, 'B' => 0,
            'belum_isi' => 0, 'masuk' => 0, 'pulang' => 0, 'belum_scan' => 0,
            'r_hadir' => 0, 'r_telat' => 0, 'r_sakit' => 0, 'r_izin' => 0,
            'r_alpha' => 0, 'r_bdr' => 0, 'r_tanpa_pulang' => 0, 'r_belum' => 0];
        try {
            $kb = $this->db->table('master_kelas')
                ->select('id_kelas, nama_kelas')
                ->where('id_tp', $tpId)
                ->where('id_smt', $smtId)
                ->where('deleted_at IS NULL');
            if ($filterKelas > 0) $kb->where('id_kelas', $filterKelas);
            $kelas = $kb->orderBy('nama_kelas', 'ASC')->get()->getResult();
            if (empty($kelas)) return ['kelas' => [], 'total' => $kosong];
            $ids = array_map(static fn($k) => (int) $k->id_kelas, $kelas);

            // Anggota per kelas (TP/SMT aktif) — hanya siswa AKTIF.
            $agg = $this->db->table('kelas_siswa')
                ->select('id_kelas, COUNT(*) AS total')
                ->whereIn('id_kelas', $ids);
            try {
                if ($this->db->fieldExists('id_tp', 'kelas_siswa')) $agg->where('id_tp', $tpId);
                if ($this->db->fieldExists('id_smt', 'kelas_siswa')) $agg->where('id_smt', $smtId);
            } catch (\Throwable $e) {
            }
            $this->filterSiswaAktif($agg, 'kelas_siswa.id_siswa');
            $anggota = [];
            foreach ($agg->groupBy('id_kelas')->get()->getResult() as $r) {
                $anggota[(int) $r->id_kelas] = (int) $r->total;
            }

            // Status piket per kelas — hanya siswa AKTIF.
            $piket = [];
            if ($this->db->tableExists('piket_absensi')) {
                $rows = $this->db->table('piket_absensi')
                    ->select("piket_absensi.id_kelas,
                        SUM(CASE WHEN UPPER(TRIM(piket_absensi.status)) = 'H' THEN 1 ELSE 0 END) AS H,
                        SUM(CASE WHEN UPPER(TRIM(piket_absensi.status)) = 'S' THEN 1 ELSE 0 END) AS S,
                        SUM(CASE WHEN UPPER(TRIM(piket_absensi.status)) = 'I' THEN 1 ELSE 0 END) AS I,
                        SUM(CASE WHEN UPPER(TRIM(piket_absensi.status)) = 'A' THEN 1 ELSE 0 END) AS A,
                        SUM(CASE WHEN UPPER(TRIM(piket_absensi.status)) = 'T' THEN 1 ELSE 0 END) AS T,
                        SUM(CASE WHEN UPPER(TRIM(piket_absensi.status)) = 'B' THEN 1 ELSE 0 END) AS B,
                        COUNT(*) AS isi", false)
                    ->where('piket_absensi.tanggal', $tanggal)
                    ->whereIn('piket_absensi.id_kelas', $ids);
                $this->filterSiswaAktif($rows, 'piket_absensi.id_siswa');
                $rows = $rows->groupBy('piket_absensi.id_kelas')
                    ->get()->getResult();
                foreach ($rows as $r) $piket[(int) $r->id_kelas] = $r;
            }

            // Scan real per kelas — hanya siswa AKTIF.
            $real = [];
            if ($this->db->tableExists('presensi_siswa')) {
                $rows = $this->db->table('presensi_siswa')
                    ->select("presensi_siswa.id_kelas,
                        SUM(CASE WHEN presensi_siswa.jam_masuk IS NOT NULL AND presensi_siswa.jam_masuk <> '' THEN 1 ELSE 0 END) AS masuk,
                        SUM(CASE WHEN presensi_siswa.jam_keluar IS NOT NULL AND presensi_siswa.jam_keluar <> '' THEN 1 ELSE 0 END) AS pulang", false)
                    ->where('presensi_siswa.tgl', $tanggal)
                    ->whereIn('presensi_siswa.id_kelas', $ids);
                $this->filterSiswaAktif($rows, 'presensi_siswa.id_siswa');
                $rows = $rows->groupBy('presensi_siswa.id_kelas')
                    ->get()->getResult();
                foreach ($rows as $r) $real[(int) $r->id_kelas] = $r;
            }

            // Breakdown REAL per siswa (kondisi sebenarnya, bukan isian piket).
            // Ambang telat = rumus baku AttendancePolicy (jadwal per hari →
            // absensi_setting, + grace inklusif detik ke-59) untuk tanggal ini.
            $batas = '07:30:59';
            try {
                $batas = (new \App\Services\SiswaPoinService())->batasMasuk($tanggal);
            } catch (\Throwable $e) {
            }
            $realRinci = [];
            if ($this->db->tableExists('presensi_siswa')) {
                $batasEsc = str_replace("'", "''", substr($batas, 0, 8));
                $rows = $this->db->table('presensi_siswa')
                    ->select("presensi_siswa.id_kelas,
                        SUM(CASE WHEN UPPER(TRIM(presensi_siswa.status)) IN ('HADIR') AND presensi_siswa.jam_masuk IS NOT NULL AND presensi_siswa.jam_masuk <> '' AND SUBSTR(presensi_siswa.jam_masuk, -8) <= '{$batasEsc}' THEN 1 ELSE 0 END) AS r_hadir,
                        SUM(CASE WHEN (UPPER(TRIM(presensi_siswa.status)) IN ('HADIR TELAT','TERLAMBAT')) OR (UPPER(TRIM(presensi_siswa.status)) = 'HADIR' AND presensi_siswa.jam_masuk IS NOT NULL AND presensi_siswa.jam_masuk <> '' AND SUBSTR(presensi_siswa.jam_masuk, -8) > '{$batasEsc}') THEN 1 ELSE 0 END) AS r_telat,
                        SUM(CASE WHEN UPPER(TRIM(presensi_siswa.status)) = 'SAKIT' THEN 1 ELSE 0 END) AS r_sakit,
                        SUM(CASE WHEN UPPER(TRIM(presensi_siswa.status)) = 'IZIN' THEN 1 ELSE 0 END) AS r_izin,
                        SUM(CASE WHEN UPPER(TRIM(presensi_siswa.status)) = 'ALPA' THEN 1 ELSE 0 END) AS r_alpha,
                        SUM(CASE WHEN UPPER(TRIM(presensi_siswa.status)) = 'BDR' THEN 1 ELSE 0 END) AS r_bdr,
                        SUM(CASE WHEN UPPER(TRIM(presensi_siswa.status)) IN ('HADIR','HADIR TELAT','TERLAMBAT') AND presensi_siswa.jam_masuk IS NOT NULL AND presensi_siswa.jam_masuk <> '' AND (presensi_siswa.jam_keluar IS NULL OR presensi_siswa.jam_keluar = '') THEN 1 ELSE 0 END) AS r_tanpa_pulang", false)
                    ->where('presensi_siswa.tgl', $tanggal)
                    ->whereIn('presensi_siswa.id_kelas', $ids);
                $this->filterSiswaAktif($rows, 'presensi_siswa.id_siswa');
                $rows = $rows->groupBy('presensi_siswa.id_kelas')
                    ->get()->getResult();
                foreach ($rows as $r) $realRinci[(int) $r->id_kelas] = $r;
            }

            $out = [];
            $total = $kosong;
            foreach ($kelas as $k) {
                $id = (int) $k->id_kelas;
                $tot = $anggota[$id] ?? 0;
                $p = $piket[$id] ?? null;
                $r = $real[$id] ?? null;
                $h = (int) ($p->H ?? 0);
                $s = (int) ($p->S ?? 0);
                $i = (int) ($p->I ?? 0);
                $a = (int) ($p->A ?? 0);
                $t = (int) ($p->T ?? 0);
                $b = (int) ($p->B ?? 0);
                $isi = (int) ($p->isi ?? 0);
                $masuk = (int) ($r->masuk ?? 0);
                $rr = $realRinci[$id] ?? null;
                $rHadir = (int) ($rr->r_hadir ?? 0);
                $rTelat = (int) ($rr->r_telat ?? 0);
                $rSakit = (int) ($rr->r_sakit ?? 0);
                $rIzin = (int) ($rr->r_izin ?? 0);
                $rAlpha = (int) ($rr->r_alpha ?? 0);
                $rBdr = (int) ($rr->r_bdr ?? 0);
                $rTanpaPulang = (int) ($rr->r_tanpa_pulang ?? 0);
                $row = (object) [
                    'id_kelas' => $id,
                    'nama_kelas' => $k->nama_kelas,
                    'total' => $tot,
                    'H' => $h, 'S' => $s, 'I' => $i, 'A' => $a, 'T' => $t, 'B' => $b,
                    'belum_isi' => max(0, $tot - $isi),
                    'masuk' => $masuk,
                    'pulang' => (int) ($r->pulang ?? 0),
                    'belum_scan' => max(0, $tot - $masuk - $rBdr),
                    'r_hadir' => $rHadir, 'r_telat' => $rTelat,
                    'r_sakit' => $rSakit, 'r_izin' => $rIzin,
                    'r_alpha' => $rAlpha, 'r_tanpa_pulang' => $rTanpaPulang,
                    'r_bdr' => $rBdr,
                    // Siswa BDR memang tidak scan di sekolah → bukan "belum".
                    'r_belum' => max(0, $tot - $rHadir - $rTelat - $rSakit - $rIzin - $rAlpha - $rBdr),
                ];
                $out[] = $row;
                foreach (['total', 'H', 'S', 'I', 'A', 'T', 'B', 'belum_isi', 'masuk', 'pulang', 'belum_scan',
                    'r_hadir', 'r_telat', 'r_sakit', 'r_izin', 'r_alpha', 'r_bdr', 'r_tanpa_pulang', 'r_belum'] as $k2) {
                    $total[$k2] += $row->{$k2};
                }
            }
            return ['kelas' => $out, 'total' => $total];
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getRekapSiswaLaporan] ' . $e->getMessage());
            return ['kelas' => [], 'total' => $kosong];
        }
    }

    /**
     * Jam pulang setting (absensi_setting, baris pertama). Dipakai sebagai
     * gerbang: yang masuk tapi belum pulang baru layak disebut bolos bila
     * jam sekarang sudah lewat jam ini (atau tanggalnya sudah lampau).
     */
    public function jamPulangSetting(): string
    {
        try {
            $set = $this->db->table('absensi_setting')->select('jam_pulang')->get()->getRow();
            $jam = trim((string) ($set->jam_pulang ?? ''));
            if ($jam !== '') return substr($jam, 0, 8);
        } catch (\Throwable $e) {
        }
        return '15:00:00';
    }

    /**
     * Apakah tanggal sudah melewati jam pulang (layak tutup-buku pulang)?
     * Tanggal lampau selalu ya; hari ini hanya bila jam sekarang >= cutoff.
     */
    public function sudahLewatJamPulang(string $tanggal): bool
    {
        $today = date('Y-m-d');
        if ($tanggal < $today) return true;
        if ($tanggal > $today) return false;
        return date('H:i:s') >= $this->jamPulangSetting();
    }

    /**
     * Daftar siswa yang masuk tapi tanpa absen pulang (jam_keluar kosong).
     *
     * Hanya baris berstatus hadir (HADIR/HADIR TELAT/TERLAMBAT) — izin/sakit/
     * alpha tak perlu pulang. Diurut nama agar enak dibaca piket.
     *
     * @return array<int, object> {id_siswa, nama, nis, id_kelas, nama_kelas, jam_masuk}
     */
    public function getSiswaTanpaPulang(string $tanggal, ?int $idKelas = null, int $tpId = 0, int $smtId = 0): array
    {
        try {
            if (!$this->db->tableExists('presensi_siswa')) return [];
            $b = $this->db->table('presensi_siswa ps')
                ->select('ps.id_siswa, ms.nama, ms.nis, ps.id_kelas, mk.nama_kelas, ps.jam_masuk')
                ->join('master_siswa ms', 'ms.id_siswa = ps.id_siswa')
                ->join('master_kelas mk', 'mk.id_kelas = ps.id_kelas', 'left')
                ->where('ps.tgl', $tanggal)
                ->where('ps.jam_masuk IS NOT NULL')
                ->where("(ps.jam_keluar IS NULL OR ps.jam_keluar = '')")
                ->where("UPPER(TRIM(ps.status)) IN ('HADIR','HADIR TELAT','TERLAMBAT')");
            // Hanya siswa AKTIF.
            try {
                if ($this->db->tableExists('buku_induk')) {
                    $b->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e2) {
            }
            if ($idKelas !== null && $idKelas > 0) {
                $b->where('ps.id_kelas', $idKelas);
            } elseif ($tpId > 0 || $smtId > 0) {
                // Batasi ke siswa terdaftar TP/SMT aktif via keanggotaan kelas.
                $b->join('kelas_siswa ks', 'ks.id_siswa = ps.id_siswa AND ks.id_kelas = ps.id_kelas', 'left');
                try {
                    if ($tpId > 0 && $this->db->fieldExists('id_tp', 'kelas_siswa')) {
                        $b->where('ks.id_tp', $tpId);
                    }
                    if ($smtId > 0 && $this->db->fieldExists('id_smt', 'kelas_siswa')) {
                        $b->where('ks.id_smt', $smtId);
                    }
                } catch (\Throwable $e) {
                }
            }
            return $b->orderBy('mk.nama_kelas', 'ASC')->orderBy('ms.nama', 'ASC')->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getSiswaTanpaPulang] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Siswa AKTIF yang tidak hadir lebih dari $batas hari — untuk
     * ditindaklanjuti guru piket. Dua periode sekaligus:
     *   - 'bulan'  : alpha bulan berjalan (1 s.d. kemarin),
     *   - 'minggu' : alpha pada Senin–Minggu minggu berjalan.
     *
     * Definisi alpha SAMA dengan acuan surat panggilan
     * (SuratPanggilanService::rekapAlphaBulan): ALPA /
     * TANPA_KETERANGAN / bolong pada hari efektif; hari libur
     * (mingguan/nasional/jadwal) dikecualikan; hari ini belum dihitung.
     * Siswa LULUS/PINDAH/KELUAR tidak masuk daftar.
     *
     * @return array{bulan:array, minggu:array, label_bulan:string, label_minggu:string, batas:int, senin:string, ahad:string}
     *   tiap baris: {id_siswa, nama, nis, nama_kelas, alpha, tanggal[]}
     */
    public function getSiswaSeringAlpha(int $tpId = 0, int $smtId = 0, int $batas = 3, int $limit = 30): array
    {
        $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulan = (int) date('n');
        $tahun = (int) date('Y');
        $senin = date('Y-m-d', strtotime('monday this week'));
        $ahad  = date('Y-m-d', strtotime('sunday this week'));
        $labelBulan = ($namaBulan[$bulan] ?? '') . ' ' . $tahun;
        $labelMinggu = $this->labelRentangMinggu($senin, $ahad, $namaBulan);
        $kosong = [
            'bulan' => [], 'minggu' => [],
            'label_bulan' => $labelBulan, 'label_minggu' => $labelMinggu,
            'batas' => $batas, 'senin' => $senin, 'ahad' => $ahad,
        ];
        try {
            $absenSetting = null;
            $setting = null;
            try {
                if ($this->db->tableExists('absensi_setting')) {
                    $absenSetting = $this->db->table('absensi_setting')->get()->getRow();
                }
                if ($this->db->tableExists('setting')) {
                    $setting = $this->db->table('setting')->get()->getRow();
                }
            } catch (\Throwable $e) {
            }
            $ctx = \App\Services\SuratPanggilanService::buildContext($bulan, $tahun);
            $rekap = \App\Services\SuratPanggilanService::rekapAlphaBulan($ctx);
            if (empty($rekap)) {
                return $kosong;
            }

            // Saring kandidat: alpha bulan ATAU alpha minggu > batas.
            $kandidat = [];
            foreach ($rekap as $sid => $r) {
                $sid = (int) $sid;
                if ($sid <= 0) {
                    continue;
                }
                $alphaBulan = (int) ($r['alpha'] ?? 0);
                $tglMinggu = [];
                foreach ((array) ($r['tanggal_alpha'] ?? []) as $t) {
                    $t = substr((string) $t, 0, 10);
                    if ($t >= $senin && $t <= $ahad) {
                        $tglMinggu[] = $t;
                    }
                }
                rsort($tglMinggu);
                $alphaMinggu = count($tglMinggu);
                if ($alphaBulan > $batas || $alphaMinggu > $batas) {
                    $kandidat[$sid] = [
                        'alpha_bulan' => $alphaBulan,
                        'alpha_minggu' => $alphaMinggu,
                        'tanggal_bulan' => array_values(array_slice((array) ($r['tanggal_alpha'] ?? []), 0, 15)),
                        'tanggal_minggu' => $tglMinggu,
                    ];
                }
            }
            if (empty($kandidat)) {
                return $kosong;
            }

            // Perkaya nama + kelas sekaligus (1 query per 500 id).
            // INNER JOIN ke kelas: hanya siswa yang terdaftar di kelas
            // (TP/SMT berjalan bila tersedia) yang ditampilkan, supaya
            // daftar tindak lanjut selalu jelas wali kelasnya.
            $info = [];
            $kondisiKelas = ($tpId > 0 && $smtId > 0)
                ? "ks.id_siswa = ms.id_siswa AND ks.id_tp = {$tpId} AND ks.id_smt = {$smtId}"
                : 'ks.id_siswa = ms.id_siswa';
            foreach (array_chunk(array_keys($kandidat), 500) as $chunk) {
                $b = $this->db->table('master_siswa ms')
                    ->select('ms.id_siswa, ms.nama, ms.nis, mk.nama_kelas')
                    ->join("kelas_siswa ks", $kondisiKelas, 'inner')
                    ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas', 'left')
                    ->whereIn('ms.id_siswa', $chunk);
                $this->filterSiswaAktif($b, 'ms.id_siswa');
                foreach ($b->get()->getResult() as $row) {
                    $id = (int) $row->id_siswa;
                    // Bila dobel (data kelas ganda), pertahankan yang pertama.
                    if (!isset($info[$id])) {
                        $info[$id] = $row;
                    }
                }
            }

            $listBulan = [];
            $listMinggu = [];
            foreach ($kandidat as $sid => $k) {
                if (!isset($info[$sid])) {
                    continue; // non-AKTIF / tak dikenal → buang
                }
                $base = [
                    'id_siswa' => $sid,
                    'nama' => $info[$sid]->nama ?? ('Siswa #' . $sid),
                    'nis' => $info[$sid]->nis ?? null,
                    'nama_kelas' => $info[$sid]->nama_kelas ?? null,
                ];
                if ($k['alpha_bulan'] > $batas) {
                    $listBulan[] = $base + [
                        'alpha' => $k['alpha_bulan'],
                        'tanggal' => $k['tanggal_bulan'],
                    ];
                }
                if ($k['alpha_minggu'] > $batas) {
                    $listMinggu[] = $base + [
                        'alpha' => $k['alpha_minggu'],
                        'tanggal' => $k['tanggal_minggu'],
                    ];
                }
            }
            usort($listBulan, static fn($a, $b) => $b['alpha'] <=> $a['alpha'] ?: strcasecmp((string) $a['nama'], (string) $b['nama']));
            usort($listMinggu, static fn($a, $b) => $b['alpha'] <=> $a['alpha'] ?: strcasecmp((string) $a['nama'], (string) $b['nama']));
            $kosong['bulan'] = array_values(array_slice($listBulan, 0, max(1, $limit)));
            $kosong['minggu'] = array_values(array_slice($listMinggu, 0, max(1, $limit)));
            return $kosong;
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getSiswaSeringAlpha] ' . $e->getMessage());
            return $kosong;
        }
    }

    /**
     * Label "3–9 Nov 2026" / "29 Sep – 5 Okt 2026" untuk rentang minggu.
     */
    private function labelRentangMinggu(string $senin, string $ahad, array $namaBulan): string
    {
        try {
            $tsAwal = strtotime($senin);
            $tsAkhir = strtotime($ahad);
            if ($tsAwal === false || $tsAkhir === false) {
                return $senin . ' – ' . $ahad;
            }
            $bAwal = $namaBulan[(int) date('n', $tsAwal)] ?? '';
            $bAkhir = $namaBulan[(int) date('n', $tsAkhir)] ?? '';
            $pendek = static fn(string $b): string => mb_substr($b, 0, 3);
            if (date('Y-m', $tsAwal) === date('Y-m', $tsAkhir)) {
                return (int) date('j', $tsAwal) . '–' . (int) date('j', $tsAkhir)
                    . ' ' . $pendek($bAkhir) . ' ' . date('Y', $tsAkhir);
            }
            return (int) date('j', $tsAwal) . ' ' . $pendek($bAwal)
                . ' – ' . (int) date('j', $tsAkhir) . ' ' . $pendek($bAkhir)
                . ' ' . date('Y', $tsAkhir);
        } catch (\Throwable $e) {
            return $senin . ' – ' . $ahad;
        }
    }

    /**
     * Daftar konflik piket vs formal pada tanggal (maks $limit).
     * Hanya baris yang gerçekten bertentangan (H/T vs non-hadir formal dan
     * sebaliknya); H piket tanpa baris formal (belum scan pagi) tak di-flag
     * agar tak berisik.
     */
    public function getDivergensi(string $tanggal, ?int $idKelas = null, int $limit = 50): array
    {
        try {
            if (!$this->db->tableExists('piket_absensi') || !$this->db->tableExists('presensi_siswa')) {
                return [];
            }
            $b = $this->db->table('piket_absensi p')
                ->select('p.id_siswa, p.id_kelas, p.status AS piket, s.nama, s.nis, k.nama_kelas,
                          ps.status AS formal, ps.jam_masuk')
                ->join('master_siswa s', 's.id_siswa = p.id_siswa')
                ->join('presensi_siswa ps', 'ps.id_siswa = p.id_siswa AND ps.tgl = p.tanggal', 'left')
                ->join('master_kelas k', 'k.id_kelas = p.id_kelas', 'left')
                ->where('p.tanggal', $tanggal);
            $this->filterSiswaAktif($b, 'p.id_siswa');
            if ($idKelas !== null && $idKelas > 0) {
                $b->where('p.id_kelas', $idKelas);
            }
            $rows = $b->orderBy('k.nama_kelas', 'ASC')->orderBy('s.nama', 'ASC')
                ->limit(max($limit * 3, 50))->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getDivergensi] ' . $e->getMessage());
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $kode = self::konflikKehadiran(
                (string) ($r->piket ?? ''),
                isset($r->formal) ? (string) $r->formal : null,
                !empty($r->jam_masuk)
            );
            // Tanpa baris formal + piket H/T = belum scan → bukan konflik
            if ($kode === null) continue;
            if (!isset($r->formal) && $kode === 'HADIR_VS_NONHADIR') continue;
            $r->konflik = $kode;
            $r->ket = $kode === 'HADIR_VS_NONHADIR'
                ? 'Piket catat hadir, sistem: ' . ($r->formal ?? 'belum tercatat')
                : 'Piket catat ' . ($r->piket ?? '?') . ', sistem: HADIR';
            $out[] = $r;
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    /**
     * Parse kolom jurnal.siswa_absent yang formatnya ganda: JSON array ID
     * (jalur baru) atau teks bebas nama/"Nihil" (jalur form lama).
     * @return array{ids:int[], nama:string[], nihil:bool}
     */
    public static function parseSiswaAbsent(?string $raw): array
    {
        $t = trim((string) ($raw ?? ''));
        if ($t === '' || $t === '[]') return ['ids' => [], 'nama' => [], 'nihil' => true];
        $low = strtolower($t);
        if ($low === 'nihil' || str_starts_with($low, 'nihil ')) {
            return ['ids' => [], 'nama' => [], 'nihil' => true];
        }
        if (str_starts_with($t, '[')) {
            $dec = json_decode($t, true);
            if (is_array($dec)) {
                $ids = array_values(array_filter(array_unique(array_map('intval', $dec))));
                return ['ids' => $ids, 'nama' => [], 'nihil' => empty($ids)];
            }
            // Bukan JSON valid → jatuh ke perlakuan teks di bawah
        }
        $parts = preg_split('/[,\n;]+/', $t);
        $nama = [];
        foreach ((array) $parts as $p) {
            $p = trim((string) $p);
            if ($p === '' || strtolower($p) === 'nihil') continue;
            $nama[] = mb_substr($p, 0, 100);
            if (count($nama) >= 200) break;
        }
        return ['ids' => [], 'nama' => $nama, 'nihil' => empty($nama)];
    }

    /**
     * Ringkasan absen satu jurnal: jumlah + daftar nama + flag ada.
     * @return array{jumlah:int, nama:string[], ada:bool}
     */
    public function infoAbsenJurnal(object $j): array
    {
        $p = self::parseSiswaAbsent($j->siswa_absent ?? null);
        if ($p['nihil']) return ['jumlah' => 0, 'nama' => [], 'ada' => false];
        if (!empty($p['ids'])) {
            $nama = $this->getSiswaNamesByIds($p['ids']);
            // ID tak dikenal semua (data usang) → tampilkan hitungan ID saja
            $jumlah = max(count($nama), count($p['ids']));
            return ['jumlah' => $jumlah, 'nama' => $nama, 'ada' => true];
        }
        return ['jumlah' => count($p['nama']), 'nama' => $p['nama'], 'ada' => true];
    }

    // --- Tamu Methods ---
    public function getTamuById($id)
    {
        return $this->db->table('piket_tamu')->where('id', (int) $id)->get()->getRow();
    }

    public function getTamu($tanggal = null)
    {
        $builder = $this->db->table('piket_tamu');
        if ($tanggal) {
            $builder->where('tanggal', $tanggal);
        }
        $builder->orderBy('tanggal', 'DESC')->orderBy('jam_masuk', 'DESC');
        return $builder->get()->getResult();
    }

    public function saveTamu($data)
    {
        return $this->db->table('piket_tamu')->insert($data);
    }

    public function deleteTamu($id)
    {
        return $this->db->table('piket_tamu')->delete(['id' => $id]);
    }

    public function updateTamuKeluar($id)
    {
        return $this->db->table('piket_tamu')
            ->where('id', $id)
            ->update(['jam_keluar' => date('H:i:s')]);
    }

    // --- Kejadian Methods ---
    public function getKejadian($tanggal = null, $tp = null, $smt = null)
    {
        $builder = $this->db->table('piket_kejadian k');
        $builder->select('k.*, s.nama as nama_siswa, kls.nama_kelas');
        $builder->join('master_siswa s', 's.id_siswa = k.terlapor_siswa_id', 'left');

        // Join kelas_siswa and master_kelas to get class name.
        // Filter kelas_siswa by TP/SMT juga agar siswa dengan riwayat
        // penempatan multi-tahun tidak menggandakan baris kejadian.
        if ($tp && $smt) {
            $builder->join(
                'kelas_siswa ks',
                'ks.id_siswa = s.id_siswa AND ks.id_tp = ' . $this->db->escape($tp) . ' AND ks.id_smt = ' . $this->db->escape($smt),
                'left'
            );
        } else {
            $builder->join('kelas_siswa ks', 'ks.id_siswa = s.id_siswa', 'left');
        }

        if ($tp && $smt) {
            // Filter class by active TP/SMT in the join to avoid duplicates from history
            $builder->join('master_kelas kls', 'kls.id_kelas = ks.id_kelas AND kls.id_tp = ' . $this->db->escape($tp) . ' AND kls.id_smt = ' . $this->db->escape($smt), 'left');
        } else {
            $builder->join('master_kelas kls', 'kls.id_kelas = ks.id_kelas', 'left');
        }

        if ($tanggal) {
            $builder->where('k.tanggal', $tanggal);
        }

        $builder->orderBy('k.created_at', 'DESC');

        // Group By is needed to squash duplicates if multiple classes somehow match (e.g. data error), 
        // but strict mode hates selecting non-aggregated columns.
        // We can just get the results and unique them in PHP if the count is small, 
        // OR we trust the TP/SMT filter to make it Unique.
        // If we filter by TP/SMT, a student should only be in ONE class. So no Group By needed ideally.

        // If we filtered by TP, we assume unique class per student
        // But if a student has NO class in this TP, kls.* is null. 
        // If a student has MULTIPLE classes in same TP (error), we get duplicates.
        // $builder->groupBy('k.id');

        return $builder->get()->getResult();
    }

    public function saveKejadian($data)
    {
        return $this->db->table('piket_kejadian')->insert($data);
    }

    public function deleteKejadian($id)
    {
        return $this->db->table('piket_kejadian')->delete(['id' => $id]);
    }

    public function updateKejadianStatus($id, $status)
    {
        return $this->db->table('piket_kejadian')
            ->where('id', $id)
            ->update(['status' => $status]);
    }

    public function getKejadianById($id)
    {
        return $this->db->table('piket_kejadian k')
            ->select('k.*, s.nama as nama_siswa')
            ->join('master_siswa s', 's.id_siswa = k.terlapor_siswa_id', 'left')
            ->where('k.id', $id)
            ->get()->getRow();
    }

    // --- Absensi Methods ---
    public function getAbsensiByKelas($tanggal, $id_kelas)
    {
        return $this->db->table('piket_absensi')
            ->where('tanggal', $tanggal)
            ->where('id_kelas', $id_kelas)
            ->get()->getResult();
    }

    /**
     * Kehadiran REAL siswa (hasil scan QR gerbang) untuk tanggal + daftar siswa.
     *
     * Read-only: dipakai halaman absensi piket agar petugas melihat jam
     * masuk/pulang sebenarnya (presensi_siswa.jam_masuk/jam_keluar) tanpa
     * mengubah arsip piket. Relasi logis via id_siswa (tak ada FK di skema).
     *
     * @param int[] $ids
     * @return array<int, object> map id_siswa => baris presensi_siswa
     */
    public function getRealSiswaMap(string $tanggal, array $ids): array
    {
        $out = [];
        try {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $ids = array_values(array_filter($ids, static fn($v) => $v > 0));
            if (empty($ids) || !$this->db->tableExists('presensi_siswa')) return $out;
            $rows = $this->db->table('presensi_siswa')
                ->select('id_siswa, status, jam_masuk, jam_keluar, jam, sumber_masuk, sumber_keluar')
                ->where('tgl', $tanggal)
                ->whereIn('id_siswa', $ids)
                ->get()->getResult();
            foreach ($rows as $r) $out[(int) $r->id_siswa] = $r;
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getRealSiswaMap] ' . $e->getMessage());
        }
        return $out;
    }

    /**
     * Upsert transaksional per baris (grain UNIQUE: tanggal+id_siswa).
     * Aman untuk submit parsial: hanya id yang dikirim yang disentuh, dan
     * id_kelas ikut dimutakhirkan bila siswa pindah kelas.
     */
    public function saveAbsensiBatch($dataBatch)
    {
        if (empty($dataBatch))
            return false;

        $hasCreatedBy = false;
        $hasUpdatedBy = false;
        try {
            $hasCreatedBy = $this->db->fieldExists('created_by', 'piket_absensi');
            $hasUpdatedBy = $this->db->fieldExists('updated_by', 'piket_absensi');
        } catch (\Throwable $e) {
        }

        // Kumpulkan status aktif sekali jalan agar tidak query per baris.
        $nonAktif = [];
        try {
            if ($this->db->tableExists('buku_induk')) {
                $ids = array_values(array_unique(array_map(static fn($r) => (int) ($r['id_siswa'] ?? 0), $dataBatch)));
                $ids = array_values(array_filter($ids, static fn($v) => $v > 0));
                foreach (array_chunk($ids, 500) as $chunk) {
                    foreach ($this->db->table('buku_induk')->select('id_siswa, status')->whereIn('id_siswa', $chunk)->get()->getResult() as $sr) {
                        if ((string) ($sr->status ?? '1') !== '1') {
                            $nonAktif[(int) $sr->id_siswa] = true;
                        }
                    }
                }
            }
        } catch (\Throwable $e2) {
        }

        $this->db->transBegin();
        try {
            foreach ($dataBatch as $row) {
                // Lewati siswa non-AKTIF (LULUS/PINDAH/KELUAR).
                if (isset($nonAktif[(int) ($row['id_siswa'] ?? 0)])) {
                    continue;
                }
                $where = ['tanggal' => $row['tanggal'], 'id_siswa' => (int) $row['id_siswa']];
                $data = [
                    'id_kelas' => (int) $row['id_kelas'],
                    'status' => $row['status'],
                ];
                if ($hasUpdatedBy) $data['updated_by'] = $row['updated_by'] ?? null;
                $existing = $this->db->table('piket_absensi')->where($where)->get()->getRow();
                if ($existing) {
                    $this->db->table('piket_absensi')->where('id', $existing->id)->update($data);
                } else {
                    $insert = array_merge($where, $data);
                    if ($hasCreatedBy) $insert['created_by'] = $row['updated_by'] ?? null;
                    $this->db->table('piket_absensi')->insert($insert);
                }
            }
            $this->db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[PiketModel::saveAbsensiBatch] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalisasi input jam manual piket: terima 'HH:MM' atau 'HH:MM:SS'
     * 24-jam → kembalikan 'HH:MM:SS'; selain itu null (jangan disimpan).
     */
    public static function normalisasiJam(?string $jam): ?string
    {
        $jam = trim((string) ($jam ?? ''));
        if ($jam === '') return null;
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $jam, $m) !== 1) return null;
        return $m[1] . ':' . $m[2] . ':' . ($m[3] ?? '00');
    }

    /**
     * Simpan jam MASUK/PULANG manual oleh guru piket ke presensi_siswa.
     *
     * Cermin aturan scan QR (PresensiSiswaQr::processCheckIn/Out) dengan
     * authorship 'piket':
     *  - MASUK hanya mengisi bila jam_masuk masih kosong milik piket
     *    (scan QR tak pernah ditimpa); SAKIT/IZIN ditolak seperti scan.
     *    Baris baru dibuat berstatus HADIR; baris TANPA_KETERANGAN/ALPA
     *    ikut menjadi HADIR karena kehadiran terbukti manual.
     *  - PULANG butuh jam_masuk (yang sudah ada atau se-request), hanya
     *    mengisi jam_keluar kosong milik piket, dijepit >= jam_masuk.
     *  - id_kelas dimutakhirkan bila siswa pindah kelas (grain tanggal+siswa).
     *
     * @param array $rows tiap baris: ['id_siswa'=>int, 'masuk'=>?string HH:MM:SS, 'pulang'=>?string HH:MM:SS]
     * @return array{disimpan:int[], dibuat:int, diupdate:int, dilewati:list<array{id_siswa:int, alasan:string}>}
     */
    public function saveJamManual(array $rows, string $tanggal, int $idKelas, ?int $pencatatGuruId = null, ?string $ip = null): array
    {
        $hasil = ['disimpan' => [], 'dibuat' => 0, 'diupdate' => 0, 'dilewati' => [], 'peringatan' => [], 'gagal_total' => false];
        if (empty($rows) || !$this->db->tableExists('presensi_siswa')) return $hasil;

        $bisaSumber = false;
        $bisaIpMasuk = false;
        $bisaIpKeluar = false;
        $bisaJam = false;
        try {
            $bisaSumber = $this->db->fieldExists('sumber_masuk', 'presensi_siswa');
            $bisaIpMasuk = $this->db->fieldExists('ip_masuk', 'presensi_siswa');
            $bisaIpKeluar = $this->db->fieldExists('ip_keluar', 'presensi_siswa');
            $bisaJam = $this->db->fieldExists('jam', 'presensi_siswa');
        } catch (\Throwable $e) {
        }
        $now = date('Y-m-d H:i:s');
        $ip = substr(trim((string) ($ip ?? '')), 0, 45);

        $this->db->transBegin();
        try {
            foreach ($rows as $row) {
                $idSiswa = (int) ($row['id_siswa'] ?? 0);
                if ($idSiswa <= 0) continue;
                // Lewati siswa non-AKTIF (LULUS/PINDAH/KELUAR).
                try {
                    if ($this->db->tableExists('buku_induk')) {
                        $st = $this->db->table('buku_induk')->select('status')
                            ->where('id_siswa', $idSiswa)->get()->getRow();
                        if ($st && (string) ($st->status ?? '1') !== '1') {
                            $hasil['dilewati'][] = ['id_siswa' => $idSiswa, 'alasan' => 'siswa tidak aktif'];
                            continue;
                        }
                    }
                } catch (\Throwable $e2) {
                }
                $masuk = !empty($row['masuk']) ? self::normalisasiJam((string) $row['masuk']) : null;
                $pulang = !empty($row['pulang']) ? self::normalisasiJam((string) $row['pulang']) : null;
                // Sisi kosong = jangan sentuh (tidak ada fitur hapus jam).
                // Format tak valid = tolak baris ini agar ketahuan operator.
                if (!empty($row['masuk']) && $masuk === null) {
                    $hasil['dilewati'][] = ['id_siswa' => $idSiswa, 'alasan' => 'format jam masuk tidak valid'];
                    continue;
                }
                if (!empty($row['pulang']) && $pulang === null) {
                    $hasil['dilewati'][] = ['id_siswa' => $idSiswa, 'alasan' => 'format jam pulang tidak valid'];
                    continue;
                }
                if ($masuk === null && $pulang === null) continue;

                $existing = $this->db->table('presensi_siswa')
                    ->where('id_siswa', $idSiswa)
                    ->where('tgl', $tanggal)
                    ->get()->getRow();

                $upd = [];
                $skipMasuk = null;
                $skipPulang = null;

                // ── Sisi MASUK (independen: penolakan di sini tak menggugurkan sisi pulang) ──
                if ($masuk !== null) {
                    if ($existing && !empty($existing->jam_masuk)
                        && !in_array($existing->sumber_masuk ?? null, [null, '', 'piket'], true)) {
                        $skipMasuk = 'sudah scan masuk ' . substr((string) $existing->jam_masuk, 0, 5);
                        $masuk = null;
                    } elseif ($existing && in_array(strtoupper(trim((string) ($existing->status ?? ''))), ['SAKIT', 'IZIN'], true)) {
                        $skipMasuk = 'berstatus ' . $existing->status;
                        $masuk = null;
                    } else {
                    $upd['jam_masuk'] = $masuk;
                    if ($bisaSumber) $upd['sumber_masuk'] = 'piket';
                    if ($bisaIpMasuk && $ip !== '') $upd['ip_masuk'] = $ip;
                    if ($bisaJam && ($existing === null || empty($existing->jam))) $upd['jam'] = $masuk;
                    if ($existing === null || in_array(strtoupper(trim((string) ($existing->status ?? ''))), ['', 'TANPA_KETERANGAN', 'ALPA', 'BDR'], true)) {
                        $upd['status'] = 'HADIR';
                    }
                    }
                }

                // ── Sisi PULANG (independen: penolakan di sini tak menggugurkan sisi masuk) ──
                if ($pulang !== null) {
                    $masukEfektif = $masuk ?? (($existing && !empty($existing->jam_masuk)) ? substr((string) $existing->jam_masuk, 0, 8) : null);
                    if ($masukEfektif === null) {
                        $skipPulang = 'belum ada jam masuk';
                        $pulang = null;
                    } elseif ($existing && !empty($existing->jam_keluar)
                        && !in_array($existing->sumber_keluar ?? null, [null, '', 'piket'], true)) {
                        $skipPulang = 'sudah scan pulang ' . substr((string) $existing->jam_keluar, 0, 5);
                        $pulang = null;
                    } else {
                        if ($pulang < $masukEfektif) $pulang = $masukEfektif; // jepit seperti scan
                        $upd['jam_keluar'] = $pulang;
                        if ($bisaSumber) $upd['sumber_keluar'] = 'piket';
                        if ($bisaIpKeluar && $ip !== '') $upd['ip_keluar'] = $ip;
                    }
                }

                if (empty($upd)) {
                    $hasil['dilewati'][] = ['id_siswa' => $idSiswa, 'alasan' => $skipMasuk ?? $skipPulang ?? 'tidak ada perubahan'];
                    continue;
                }
                $upd['updated_at'] = $now;

                if ($existing) {
                    // Pertahankan pencatat masuk; jangan timpa dengan pencatat koreksi.
                    $upd['id_kelas'] = $idKelas;
                    if (empty($existing->id_guru) && $pencatatGuruId > 0) $upd['id_guru'] = $pencatatGuruId;
                    $this->db->table('presensi_siswa')->where('id', $existing->id)->update($upd);
                    $hasil['diupdate']++;
                } else {
                    // Tanpa baris formal: butuh sisi masuk (pulang saja tak bisa berdiri sendiri).
                    if ($masuk === null) {
                        $hasil['dilewati'][] = ['id_siswa' => $idSiswa, 'alasan' => $skipMasuk ?? 'belum ada jam masuk'];
                        continue;
                    }
                    $ins = array_merge([
                        'id_kelas' => $idKelas,
                        'id_siswa' => $idSiswa,
                        'id_mapel' => null,
                        'id_guru' => $pencatatGuruId > 0 ? $pencatatGuruId : null,
                        'tgl' => $tanggal,
                        'status' => 'HADIR',
                        'created_at' => $now,
                    ], $upd);
                    $this->db->table('presensi_siswa')->insert($ins);
                    $hasil['dibuat']++;
                }
                $hasil['disimpan'][] = $idSiswa;
                if ($skipMasuk !== null || $skipPulang !== null) {
                    $hasil['peringatan'][] = ['id_siswa' => $idSiswa, 'alasan' => $skipMasuk ?? $skipPulang];
                }
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[PiketModel::saveJamManual] ' . $e->getMessage());
            // Transaksi dibatalkan: klaim simpan parsial sebelumnya hangus,
            // jadi kosongkan agar pemanggil tak melaporkan sukses palsu.
            $hasil['disimpan'] = [];
            $hasil['dibuat'] = 0;
            $hasil['diupdate'] = 0;
            $hasil['gagal_total'] = true;
            return $hasil;
        }

        // Pasca-commit: poin + sinkron telat ke arsip piket (best-effort,
        // cermin jalur scan agar keterlambatan manual ikut tercatat).
        foreach (array_unique($hasil['disimpan']) as $sid) {
            try {
                $baris = $this->db->table('presensi_siswa')
                    ->select('id')->where('id_siswa', $sid)->where('tgl', $tanggal)->get()->getRow();
                if ($baris) {
                    \App\Services\SiswaPoinService::sinkron((int) $baris->id);
                    \App\Services\SiswaDendaService::sinkron((int) $baris->id);
                }
            } catch (\Throwable $e) {
            }
            try {
                $this->syncTelatOtomatis($tanggal, $idKelas, [$sid]);
            } catch (\Throwable $e) {
                log_message('error', '[PiketModel::saveJamManual] sync telat: ' . $e->getMessage());
            }
        }
        return $hasil;
    }

    // --- Monitoring Methods ---
    public function getJurnalHarian($tanggal, array $filters = [])
    {
        // Get Jurnal entries for today
        // Left join + COALESCE: jurnal yang referensinya terhapus tetap tampil
        // dengan label pengganti, bukan hilang diam-diam dari monitoring.
        $builder = $this->db->table('guru_jurnal j')
            ->select('j.*')
            ->select('COALESCE(g.nama_guru, CONCAT("Guru #", j.id_guru)) AS nama_guru', false)
            ->select('COALESCE(m.nama_mapel, CONCAT("Mapel #", j.id_mapel)) AS nama_mapel', false)
            ->select('COALESCE(k.nama_kelas, CONCAT("Kelas #", j.id_kelas)) AS nama_kelas', false)
            ->join('master_guru g', 'g.id_guru = j.id_guru', 'left')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->where('j.tanggal', $tanggal)
            ->orderBy('j.jam_ke', 'ASC');

        $q = trim((string) ($filters['q'] ?? ''));
        $idGuru = (int) ($filters['id_guru'] ?? 0);
        $idKelas = (int) ($filters['id_kelas'] ?? 0);
        $idMapel = (int) ($filters['id_mapel'] ?? 0);
        $idTp = (int) ($filters['id_tp'] ?? 0);
        $idSmt = (int) ($filters['id_smt'] ?? 0);

        if ($idGuru > 0) $builder->where('j.id_guru', $idGuru);
        if ($idKelas > 0) $builder->where('j.id_kelas', $idKelas);
        if ($idMapel > 0) $builder->where('j.id_mapel', $idMapel);
        if ($idTp > 0) $builder->where('j.id_tp', $idTp);
        if ($idSmt > 0) $builder->where('j.id_smt', $idSmt);

        if ($q !== '') {
            $builder->groupStart()
                ->like('g.nama_guru', $q)
                ->orLike('m.nama_mapel', $q)
                ->orLike('k.nama_kelas', $q)
                ->orLike('j.materi_pokok', $q)
                ->orLike('j.catatan_khusus', $q)
                ->groupEnd();
        }

        return $builder->get()->getResult();
    }

    public function getAbsensiGuruHarian(string $tanggal): array
    {
        return $this->db->table('absensi_harian a')
            ->select('a.id_guru, a.user_id, a.tgl, a.jam_masuk, a.jam_pulang, a.status_masuk, a.status_pulang')
            ->select('COALESCE(g.nama_guru, CONCAT("Guru #", a.id_guru)) as nama_guru', false)
            ->join('master_guru g', '(g.id_guru = a.id_guru AND a.id_guru <> 0) OR (g.id_user = a.user_id AND (a.id_guru IS NULL OR a.id_guru = 0))', 'left')
            ->where('a.tgl', $tanggal)
            ->get()->getResult();
    }

    public function getAllGuruWithAbsensi(string $tanggal): array
    {
        $guruRows = $this->db->table('master_guru')
            ->select('id_guru, id_user, nama_guru, nip')
            ->orderBy('nama_guru', 'ASC')
            ->get()->getResult();

        $absensiRows = $this->db->table('absensi_harian')
            ->where('tgl', $tanggal)
            ->get()->getResult();

        // Baris absensi lama/system bisa ber-id_guru=0 dan hanya membawa user_id —
        // petakan via id_guru DULU, lalu fallback via id_user agar tidak hilang.
        $byGuruId = [];
        $byUserId = [];
        foreach ($absensiRows as $a) {
            if (!empty($a->id_guru)) {
                $byGuruId[(int) $a->id_guru] = $a;
            } elseif (!empty($a->user_id)) {
                $byUserId[(int) $a->user_id] = $a;
            }
        }

        $result = [];
        foreach ($guruRows as $g) {
            $g->jam_masuk = null;
            $g->jam_pulang = null;
            $g->status_masuk = null;
            $g->status_pulang = null;
            $a = $byGuruId[(int) $g->id_guru]
                ?? ((!empty($g->id_user) && isset($byUserId[(int) $g->id_user])) ? $byUserId[(int) $g->id_user] : null);
            if ($a) {
                $g->jam_masuk = $a->jam_masuk;
                $g->jam_pulang = $a->jam_pulang;
                $g->status_masuk = $a->status_masuk;
                $g->status_pulang = $a->status_pulang;
            }
            $result[] = $g;
        }
        return $result;
    }

    public function getJurnalById(int $id)
    {
        // COALESCE agar referensi yang sudah dihapus tetap terbaca sebagai
        // label pengganti, sama seperti getJurnalHarian().
        return $this->db->table('guru_jurnal j')
            ->select('j.*')
            ->select('COALESCE(g.nama_guru, CONCAT("Guru #", j.id_guru)) AS nama_guru', false)
            ->select('COALESCE(m.nama_mapel, CONCAT("Mapel #", j.id_mapel)) AS nama_mapel', false)
            ->select('COALESCE(k.nama_kelas, CONCAT("Kelas #", j.id_kelas)) AS nama_kelas', false)
            ->select('jm.dari as jam_mulai_slot, jm.sampai as jam_selesai_slot', false)
            ->join('master_guru g', 'g.id_guru = j.id_guru', 'left')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->join('kelas_jadwal_mapel jm', 'jm.id_jadwal = j.id_jadwal', 'left')
            ->where('j.id_jurnal', $id)
            ->get()->getRow();
    }

    public function countSiswaKelas(int $idKelas, ?int $idTp = null, ?int $idSmt = null): int
    {
        $builder = $this->db->table('kelas_siswa')->where('id_kelas', $idKelas);
        if ($idTp !== null) $builder->where('id_tp', $idTp);
        if ($idSmt !== null) $builder->where('id_smt', $idSmt);
        return (int) $builder->countAllResults();
    }

    /**
     * Jumlah siswa AKTIF di kelas (buku_induk.status = '1'), sama dengan
     * basis hitung rekap laporan piket.
     */
    public function countSiswaAktifKelas(int $idKelas, ?int $idTp = null, ?int $idSmt = null): int
    {
        $builder = $this->db->table('kelas_siswa')
            ->select('COUNT(DISTINCT kelas_siswa.id_siswa) AS total', false)
            ->where('id_kelas', $idKelas);
        if ($idTp !== null) $builder->where('id_tp', $idTp);
        if ($idSmt !== null) $builder->where('id_smt', $idSmt);
        $this->filterSiswaAktif($builder, 'kelas_siswa.id_siswa');
        $row = $builder->get()->getRow();
        return (int) ($row->total ?? 0);
    }

    /**
     * Alasan guru yang TIDAK check-in pada tanggal: [id_guru => alasan].
     *
     * Urutan sumber: baris absensi_harian hari itu (status + keterangan),
     * lalu pengajuan izin approved yang mencakup tanggal (jenis +
     * keterangan). Tanpa keduanya → 'Alpha (tanpa keterangan)'.
     * Guru yang sudah check-in (jam_masuk terisi) tidak masuk map.
     */
    public function getAlasanGuruMap(string $tanggal): array
    {
        $out = [];
        try {
            $guru = $this->db->table('master_guru')
                ->select('id_guru, id_user, nama_guru')
                ->get()->getResult();
            if (empty($guru)) return $out;
            $userToGuru = [];
            foreach ($guru as $g) {
                if (!empty($g->id_user)) $userToGuru[(int) $g->id_user] = (int) $g->id_guru;
            }

            // Baris harian per guru (id_guru dulu, fallback user_id).
            $harian = [];
            try {
                foreach ($this->db->table('absensi_harian')->where('tgl', $tanggal)->get()->getResult() as $a) {
                    $gid = (int) ($a->id_guru ?? 0);
                    if ($gid <= 0 && !empty($a->user_id) && isset($userToGuru[(int) $a->user_id])) {
                        $gid = $userToGuru[(int) $a->user_id];
                    }
                    if ($gid > 0 && !isset($harian[$gid])) $harian[$gid] = $a;
                }
            } catch (\Throwable $e) {
            }

            // Izin approved yang mencakup tanggal.
            $izin = [];
            try {
                if ($this->db->tableExists('absensi_izin')) {
                    $rows = $this->db->table('absensi_izin')
                        ->select('user_id, id_guru, jenis, keterangan, status')
                        ->where('tgl_mulai <=', $tanggal)
                        ->where('tgl_selesai >=', $tanggal)
                        ->get()->getResult();
                    foreach ($rows as $r) {
                        $st = strtolower(trim((string) ($r->status ?? '')));
                        if (!in_array($st, ['approved', '1'], true)) continue;
                        $gid = (int) ($r->id_guru ?? 0);
                        if ($gid <= 0 && !empty($r->user_id) && isset($userToGuru[(int) $r->user_id])) {
                            $gid = $userToGuru[(int) $r->user_id];
                        }
                        if ($gid > 0 && !isset($izin[$gid])) $izin[$gid] = $r;
                    }
                }
            } catch (\Throwable $e) {
            }

            $label = static function (string $st): string {
                return match (strtoupper(trim($st))) {
                    'SAKIT' => 'Sakit',
                    'IJIN', 'IZIN' => 'Izin',
                    'CUTI' => 'Cuti',
                    'DINAS', 'DINAS_APPROVED' => 'Dinas',
                    'WFH' => 'WFH',
                    'PENDING_VERIFICATION' => 'Menunggu verifikasi',
                    'LATE' => 'Terlambat',
                    'ONTIME' => 'Hadir',
                    default => 'Tanpa keterangan',
                };
            };

            foreach ($guru as $g) {
                $gid = (int) $g->id_guru;
                $a = $harian[$gid] ?? null;
                if ($a !== null && !empty($a->jam_masuk)) continue; // hadir
                if ($a !== null) {
                    $st = strtoupper(trim((string) ($a->status_masuk ?? '')));
                    if (in_array($st, ['ALPHA', 'ALPA', ''], true)) {
                        // Jatuh ke izin/alpha di bawah.
                    } else {
                        $ket = trim((string) ($a->keterangan ?? ''));
                        $out[$gid] = $label($st) . ($ket !== '' ? ': ' . mb_substr($ket, 0, 80) : '');
                        continue;
                    }
                }
                $iz = $izin[$gid] ?? null;
                if ($iz !== null) {
                    $ket = trim((string) ($iz->keterangan ?? ''));
                    $out[$gid] = $label((string) ($iz->jenis ?? 'izin')) . ($ket !== '' ? ': ' . mb_substr($ket, 0, 80) : '');
                    continue;
                }
                $out[$gid] = 'Alpha (tanpa keterangan)';
            }
        } catch (\Throwable $e) {
            log_message('error', '[PiketModel::getAlasanGuruMap] ' . $e->getMessage());
        }
        return $out;
    }

    public function getSiswaNamesByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids))));
        if (empty($ids)) return [];

        $rows = $this->db->table('master_siswa')
            ->select('id_siswa, nama')
            ->whereIn('id_siswa', $ids)
            ->orderBy('nama', 'ASC')
            ->get()->getResult();

        $names = [];
        foreach ($rows as $r) $names[] = $r->nama;
        return $names;
    }

    public function getJurnalFilterOptions(string $tanggal): array
    {
        $gurus = $this->db->table('guru_jurnal j')
            ->select('g.id_guru, g.nama_guru')
            ->join('master_guru g', 'g.id_guru = j.id_guru')
            ->where('j.tanggal', $tanggal)
            ->groupBy('g.id_guru, g.nama_guru')
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        $kelas = $this->db->table('guru_jurnal j')
            ->select('k.id_kelas, k.nama_kelas')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas')
            ->where('j.tanggal', $tanggal)
            ->groupBy('k.id_kelas, k.nama_kelas')
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()->getResult();

        $mapel = $this->db->table('guru_jurnal j')
            ->select('m.id_mapel, m.nama_mapel')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel')
            ->where('j.tanggal', $tanggal)
            ->groupBy('m.id_mapel, m.nama_mapel')
            ->orderBy('m.nama_mapel', 'ASC')
            ->get()->getResult();

        return [
            'guru' => $gurus,
            'kelas' => $kelas,
            'mapel' => $mapel,
        ];
    }

    public function getStats($tanggal)
    {
        $count = function (string $table, callable $filter): int {
            if (!$this->db->tableExists($table)) return 0;
            try {
                return (int) $filter($this->db->table($table));
            } catch (\Throwable $e) {
                log_message('error', "[PiketModel::getStats] {$table}: " . $e->getMessage());
                return 0;
            }
        };

        $tamu      = $count('piket_tamu', fn($t) => $t->where('tanggal', $tanggal)->countAllResults());
        $kejadian  = $count('piket_kejadian', fn($t) => $t->where('tanggal', $tanggal)->countAllResults());
        $absensi   = $count('piket_absensi', fn($t) => $t->where('tanggal', $tanggal)->whereNotIn('status', ['H', 'T'])->countAllResults());
        $jurnal    = $count('guru_jurnal', fn($t) => $t->where('tanggal', $tanggal)->countAllResults());
        $guru_masuk = $count('absensi_harian', fn($t) => $t->where('tgl', $tanggal)->where('jam_masuk IS NOT NULL')->countAllResults());
        // "Belum" = total guru aktif − sudah masuk (kecualikan soft-delete & nonaktif)
        $total_guru = $count('master_guru', function ($t) {
            try {
                if ($this->db->fieldExists('deleted_at', 'master_guru')) {
                    $t->where('deleted_at IS NULL');
                }
            } catch (\Throwable $e) {
            }
            try {
                if ($this->db->fieldExists('status_aktif', 'master_guru')) {
                    $t->groupStart()
                        ->where('status_aktif !=', 'Tidak Aktif')
                        ->where('status_aktif !=', 'Nonaktif')
                        ->orWhere('status_aktif IS NULL')
                        ->orWhere('status_aktif', '')
                        ->groupEnd();
                }
            } catch (\Throwable $e) {
            }
            return $t->countAllResults();
        });
        $guru_belum = max(0, $total_guru - $guru_masuk);

        return [
            'tamu' => $tamu,
            'kejadian' => $kejadian,
            'siswa_absen' => $absensi,
            'jurnal' => $jurnal,
            'guru_masuk' => $guru_masuk,
            'guru_belum' => $guru_belum,
        ];
    }
}
