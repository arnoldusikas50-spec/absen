<?php

namespace App\Models;

use CodeIgniter\Model;

class JurnalGuruModel extends Model
{
    protected $table = 'guru_jurnal';
    protected $primaryKey = 'id_jurnal';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = [
        'id_guru',
        'id_jadwal',
        'id_kelas',
        'id_mapel',
        'id_tp',
        'id_smt',
        'tanggal',
        'jam_ke',
        'materi_pokok',
        'tujuan_pembelajaran',
        'model_belajar',
        'kegiatan_belajar',
        'profil_pancasila', // JSON
        'jenis_asesmen', // Formatif / Sumatif
        'refleksi',
        'media_alat',
        'siswa_absent',
        'tindak_lanjut',
        'catatan_khusus', // Keep for backward compat or other notes
        'validasi_status',
        'validasi_oleh',
        'validasi_pada',
        'validasi_catatan',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;

    // Initialize: Check and create table/columns if not exists
    public function initialize()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->tableExists($this->table)) {
            // Create Table
            $forge->addField([
                'id_jurnal' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'id_jadwal' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'id_kelas' => ['type' => 'INT', 'constraint' => 11],
                'id_mapel' => ['type' => 'INT', 'constraint' => 11],
                'id_tp' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'tanggal' => ['type' => 'DATE'],
                'jam_ke' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'materi_pokok' => ['type' => 'TEXT', 'null' => true],
                'tujuan_pembelajaran' => ['type' => 'TEXT', 'null' => true],
                'model_belajar' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'kegiatan_belajar' => ['type' => 'TEXT', 'null' => true],
                'profil_pancasila' => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON'],
                'jenis_asesmen' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'refleksi' => ['type' => 'TEXT', 'null' => true],
                'media_alat' => ['type' => 'TEXT', 'null' => true],
                'siswa_absent' => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON'],
                'tindak_lanjut' => ['type' => 'TEXT', 'null' => true],
                'catatan_khusus' => ['type' => 'TEXT', 'null' => true],
                'validasi_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING'],
                'validasi_oleh' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'validasi_pada' => ['type' => 'DATETIME', 'null' => true],
                'validasi_catatan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
            $forge->addKey('id_jurnal', true);
            $forge->addKey('id_guru');
            $forge->addKey('id_kelas');
            $forge->addKey('tanggal');
            $forge->createTable($this->table, true);
        } else {
            // Add new columns if missing (Migration style)
            $fieldsToadd = [
                // Tautan jurnal ke slot jadwal. Tabel lama (sebelum quick-pick
                // dari kartu jadwal) tidak punya kolom ini sama sekali, dan
                // tanpa kolom-nya insert jurnal dari jadwal langsung error.
                'id_jadwal' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'id_guru'],
                'model_belajar' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'tujuan_pembelajaran'],
                'profil_pancasila' => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON', 'after' => 'kegiatan_belajar'],
                'jenis_asesmen' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'profil_pancasila'],
                'tindak_lanjut' => ['type' => 'TEXT', 'null' => true, 'after' => 'siswa_absent'],
                // Validasi isian jurnal oleh guru piket / kepala sekolah.
                // Default 'PENDING' terisi otomatis untuk baris lama oleh MySQL.
                'validasi_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING', 'after' => 'catatan_khusus'],
                'validasi_oleh' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'validasi_status'],
                'validasi_pada' => ['type' => 'DATETIME', 'null' => true, 'after' => 'validasi_oleh'],
                'validasi_catatan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'validasi_pada']
            ];

            $existingFields = array_map('strtolower', $db->getFieldNames($this->table));
            $fieldsToAddReally = [];
            foreach ($fieldsToadd as $field => $def) {
                if (!in_array(strtolower($field), $existingFields)) {
                    $fieldsToAddReally[$field] = $def;
                }
            }

            if (!empty($fieldsToAddReally)) {
                try {
                    $forge->addColumn($this->table, $fieldsToAddReally);
                } catch (\Throwable $e) {
                    // Ignore if already exists
                }
            }
        }
    }

    /** Status validasi jurnal mengajar oleh guru piket / kepala sekolah. */
    public const VALIDASI_PENDING  = 'PENDING';
    public const VALIDASI_APPROVED = 'APPROVED';
    public const VALIDASI_REJECTED = 'REJECTED';

    public static function validasiStatusList(): array
    {
        return [self::VALIDASI_PENDING, self::VALIDASI_APPROVED, self::VALIDASI_REJECTED];
    }

    /**
     * Lepas tautan jurnal ke slot yang sudah tidak cocok lagi: slotnya
     * dihapus, atau kelas/mapel/hari/periodenya diganti admin lewat menu
     * jadwal.
     *
     * Tautan id_jadwal menang mutlak atas pencocokan kelas:mapel:jam, jadi
     * tautan yang basi membuat jurnal satu mapel dianggap memenuhi slot
     * mapel lain, dan jurnal sungguhan untuk slot baru tidak pernah
     * ditemukan. Angka kehadiran guru di halaman piket ikut salah karena
     * itu.
     *
     * Hari ikut diperiksa karena satu slot jadwal hidup di satu hari tertentu:
     * jurnal Selasa yang masih menaut slot hari Senin (mis. sesi dipindah)
     * tidak boleh dihitung sebagai pemenuhan slot Selasa. WEEKDAY() bernilai
     * 0 untuk Senin, sehingga +1 sama dengan id_hari ISO (1=Senin).
     */
    public function detachStaleJadwalLinks(int $tp, int $smt): int
    {
        if ($tp <= 0 || $smt <= 0) {
            return 0;
        }

        $db = \Config\Database::connect();
        $db->query(
            'UPDATE guru_jurnal j
                LEFT JOIN kelas_jadwal_mapel s ON s.id_jadwal = j.id_jadwal
               SET j.id_jadwal = NULL
             WHERE j.id_tp = ? AND j.id_smt = ?
               AND j.id_jadwal IS NOT NULL
               AND (s.id_jadwal IS NULL
                    OR s.id_kelas <> j.id_kelas
                    OR s.id_mapel <> j.id_mapel
                    OR s.id_tp <> j.id_tp
                    OR s.id_smt <> j.id_smt
                    OR (s.id_hari > 0 AND s.id_hari <> WEEKDAY(j.tanggal) + 1)',
            [$tp, $smt]
        );

        return $db->affectedRows();
    }

    /**
     * Catat hasil validasi satu jurnal.
     *
     * Update kondisional dari $dariStatus: dua validator yang menekan tombol
     * hampir bersamaan tidak saling menimpa tanpa kabar — yang kedua
     * menerima kode 'berubah' dan UI diminta memuat ulang.
     *
     * @return array{kode:string,status:string}  kode: ok|berubah|hilang
     */
    public function setValidasi(int $idJurnal, string $status, int $olehUserId, ?string $catatan = null, array $dariStatus = []): array
    {
        if (!in_array($status, self::validasiStatusList(), true)) {
            throw new \InvalidArgumentException('Status validasi tidak dikenal: ' . $status);
        }

        $data = [
            'validasi_status'  => $status,
            'validasi_oleh'    => $olehUserId ?: null,
            'validasi_pada'    => date('Y-m-d H:i:s'),
            'validasi_catatan' => $catatan,
        ];

        $builder = $this->db->table($this->table)->where('id_jurnal', $idJurnal);
        if (!empty($dariStatus)) {
            $builder->whereIn('validasi_status', $dariStatus);
        }
        $builder->update($data);

        if ($this->db->affectedRows() === 1) {
            return ['kode' => 'ok', 'status' => $status];
        }

        $row = $this->find($idJurnal);
        if (!$row) {
            return ['kode' => 'hilang', 'status' => ''];
        }

        return ['kode' => 'berubah', 'status' => (string) ($row->validasi_status ?? self::VALIDASI_PENDING)];
    }

    /**
     * Kembalikan jurnal ke antrean validasi — dipakai saat guru mengubah
     * isinya, supaya persetujuan lama tidak melekat pada teks yang berbeda.
     */
    /**
     * Jurnal kembali ke antrean validasi. Keputusan lama (siapa & kapan)
     * dilepas, tetapi catatannya sengaja dipertahankan: guru yang memperbaiki
     * jurnal yang ditolak butuh alasan penolakannya tetap terbaca, dan catatan
     * ini akan tertimpa sendiri begitu validator menilai lagi.
     */
    public function resetValidasi(int $idJurnal): void
    {
        $this->db->table($this->table)
            ->where('id_jurnal', $idJurnal)
            ->update([
                'validasi_status'  => self::VALIDASI_PENDING,
                'validasi_oleh'    => null,
                'validasi_pada'    => null,
            ]);
    }

    public function getJurnalGuru($id_guru, $tp, $smt, $bulan = null)    {
        $builder = $this->db->table($this->table . ' j');
        $builder->select('j.*, k.nama_kelas, m.nama_mapel, m.kode as kode_mapel');
        $builder->join('master_kelas k', 'k.id_kelas = j.id_kelas');
        $builder->join('master_mapel m', 'm.id_mapel = j.id_mapel');
        $builder->where(['j.id_guru' => $id_guru, 'j.id_tp' => $tp, 'j.id_smt' => $smt]);

        if ($bulan) {
            $builder->where('MONTH(j.tanggal)', $bulan);
        }

        $builder->orderBy('j.tanggal', 'DESC');
        $builder->orderBy('j.created_at', 'DESC');

        return $builder->get()->getResult();
    }

    public function getJurnalById($id_jurnal)
    {
        return $this->db->table($this->table . ' j')
            ->select('j.*, k.nama_kelas, m.nama_mapel')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel')
            ->where('j.id_jurnal', $id_jurnal)
            ->get()->getRow();
    }

    /* ==================================================================
     |  Query terpusat untuk Guru\Jurnal (riwayat, statistik, simpan)
     |================================================================= */

    /**
     * Riwayat jurnal guru selama satu bulan dengan filter opsional kelas,
     * mapel, dan kata kunci pencarian.
     *
     * $kronologis = true memakai urutan ASC untuk rekap cetak.
     *
     * @param array{id_kelas?:int,id_mapel?:int,q?:string} $filter
     */
    public function getRiwayatBulan(int $idGuru, int $idTp, int $idSmt, string $mulai, string $selesai, array $filter = [], bool $kronologis = false): array
    {
        $builder = $this->db->table($this->table . ' j')
            ->select('j.*, k.nama_kelas, m.nama_mapel, m.kode as kode_mapel')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->where('j.id_guru', $idGuru)
            ->where('j.id_tp', $idTp)
            ->where('j.id_smt', $idSmt)
            ->where('j.tanggal >=', $mulai)
            ->where('j.tanggal <=', $selesai);

        if ((int) ($filter['id_kelas'] ?? 0) > 0) {
            $builder->where('j.id_kelas', (int) $filter['id_kelas']);
        }
        if ((int) ($filter['id_mapel'] ?? 0) > 0) {
            $builder->where('j.id_mapel', (int) $filter['id_mapel']);
        }
        $search = trim((string) ($filter['q'] ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('j.materi_pokok', $search)
                ->orLike('k.nama_kelas', $search)
                ->orLike('m.nama_mapel', $search)
                ->orLike('j.tujuan_pembelajaran', $search)
                ->groupEnd();
        }

        return $builder->orderBy('j.tanggal', $kronologis ? 'ASC' : 'DESC')
            ->orderBy('j.jam_ke', 'ASC')
            ->get()->getResult();
    }

    /**
     * Statistik ringkas guru dalam satu query: total jurnal bulan terpilih,
     * jurnal hari ini, dan aktivitas 7 hari terakhir.
     */
    public function getStatistik(int $idGuru, int $idTp, int $idSmt, string $mulai, string $selesai): object
    {
        $today = date('Y-m-d');
        $mingguMulai = date('Y-m-d', strtotime('-6 days'));

        return $this->db->query(
            'SELECT SUM(tanggal >= ? AND tanggal <= ?) AS total_bulan,'
            . ' SUM(tanggal = ?) AS jurnal_hari_ini,'
            . ' SUM(tanggal >= ? AND tanggal <= ?) AS minggu_ini'
            . ' FROM ' . $this->table . ' WHERE id_guru = ? AND id_tp = ? AND id_smt = ?',
            [$mulai, $selesai, $today, $mingguMulai, $today, $idGuru, $idTp, $idSmt]
        )->getRow();
    }

    /**
     * Baris jurnal per ID, dipetakan [id_jurnal] => row agar lookup O(1).
     */
    public function getByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        $map = [];
        foreach ($this->db->table($this->table)->whereIn('id_jurnal', $ids)->get()->getResult() as $row) {
            $map[(int) $row->id_jurnal] = $row;
        }
        return $map;
    }

    /**
     * Lampirkan ringkasan siswa tidak hadir (absen_jumlah & absen_label) pada
     * tiap baris jurnal. Memakai parser yang sama dengan monitoring piket agar
     * nilai "[]", JSON ID, dan "Nihil" tidak tampil mentah di daftar.
     */
    public function lampirkanRingkasanAbsen(array $jurnals): void
    {
        if (empty($jurnals)) {
            return;
        }

        $idDicari = [];
        foreach ($jurnals as $row) {
            $idDicari = array_merge($idDicari, PiketModel::parseSiswaAbsent($row->siswa_absent ?? null)['ids']);
        }

        $namaSiswa = [];
        if (!empty($idDicari)) {
            foreach (
                $this->db->table('master_siswa')
                    ->select('id_siswa, nama')
                    ->whereIn('id_siswa', array_values(array_unique(array_map('intval', $idDicari))))
                    ->get()->getResult() as $s
            ) {
                $namaSiswa[(int) $s->id_siswa] = (string) $s->nama;
            }
        }

        foreach ($jurnals as $row) {
            $parsed = PiketModel::parseSiswaAbsent($row->siswa_absent ?? null);
            if ($parsed['nihil']) {
                $row->absen_jumlah = 0;
                $row->absen_label = '';
                continue;
            }
            if (!empty($parsed['ids'])) {
                $nama = [];
                foreach ($parsed['ids'] as $idSiswa) {
                    if (isset($namaSiswa[$idSiswa])) {
                        $nama[] = $namaSiswa[$idSiswa];
                    }
                }
                // ID tak dikenal semua (data usang) tetap dihitung.
                $row->absen_jumlah = max(count($nama), count($parsed['ids']));
                $row->absen_label = implode(', ', $nama);
                continue;
            }
            $row->absen_jumlah = count($parsed['nama']);
            $row->absen_label = implode(', ', $parsed['nama']);
        }
    }

    /**
     * Detail satu jurnal lengkap dengan nama guru, kelas, dan mapel
     * (dipakai modal rincian, halaman detail, dan cetak tunggal).
     */
    public function getJurnalDetail(int $id): ?object
    {
        return $this->db->table($this->table . ' j')
            ->select('j.*, g.nama_guru, g.nip, k.nama_kelas, m.nama_mapel, m.kode as kode_mapel')
            ->join('master_guru g', 'g.id_guru = j.id_guru', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->where('j.id_jurnal', (int) $id)
            ->get()->getRow();
    }

    /** Jurnal milik guru tertentu — penjaga kepemilikan pada form edit. */
    public function findMilikGuru(int $id, int $idGuru): ?object
    {
        return $this->db->table($this->table)
            ->where('id_jurnal', (int) $id)
            ->where('id_guru', (int) $idGuru)
            ->get()->getRow();
    }

    /** Sumber salin: jurnal milik guru pada periode aktif. */
    public function findSumberSalin(int $id, int $idGuru, int $idTp, int $idSmt): ?object
    {
        return $this->db->table($this->table)
            ->where('id_jurnal', (int) $id)
            ->where('id_guru', (int) $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->get()->getRow();
    }

    /** Jurnal terakhir guru pada periode aktif (tombol salin cepat di form). */
    public function jurnalTerakhir(int $idGuru, int $idTp, int $idSmt): ?object
    {
        return $this->db->table($this->table)
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id_jurnal', 'DESC')
            ->limit(1)
            ->get()->getRow();
    }

    /**
     * Kandidat jurnal ganda: kombinasi guru+periode+tanggal+jam+kelas+mapel
     * sudah terpakai (diabaikan satu id saat edit).
     *
     * Jam dibandingkan setelah dinormalisasi, sama seperti pencocokan slot di
     * cocokkanJurnalKeSlot(). Dengan perbandingan string mentah, "1" dan "1-2"
     * dianggap berbeda sehingga guru boleh membuat dua jurnal untuk satu sesi
     * yang sama — dan karena keduanya dinormalisasi ke jam yang sama, hanya
     * satu yang pernah terhubung ke slot; sisanya menggantung dan sesinya
     * terlihat belum dijurnal.
     */
    public function adaJurnalGanda(int $idGuru, int $idTp, int $idSmt, string $tanggal, string $jamKe, int $idKelas, int $idMapel, int $abaikanId = 0): bool
    {
        $jamBaru = self::normalizeJamKe($jamKe);

        $builder = $this->db->table($this->table)
            ->select('id_jurnal, jam_ke')
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('tanggal', $tanggal)
            ->where('id_kelas', $idKelas)
            ->where('id_mapel', $idMapel);
        if ($abaikanId > 0) {
            $builder->where('id_jurnal !=', $abaikanId);
        }

        foreach ($builder->get()->getResult() as $row) {
            if (self::normalizeJamKe($row->jam_ke ?? '') === $jamBaru) {
                return true;
            }
        }

        return false;
    }

    /**
     * Seluruh sesi jadwal guru pada satu hari beserta status jurnalnya.
     *
     * Jurnal ber-id_jadwal dihitung per slot; jurnal lama tanpa id_jadwal
     * dicocokkan per kelas+mapel+jam dan dikonsumsi satu per satu agar dua
     * slot kelas-mapel yang sama tidak saling menutupi.
     *
     * $hanyaTervalidasi: jurnal yang belum berstatus APPROVED (PENDING,
     * REJECTED, atau kolomnya masih kosong) dianggap belum memenuhi slot.
     * Slot yang sebenarnya sudah ada jurnalnya ditandai 'menunggu_validasi'
     * supaya layar bisa membedakan "belum diisi" dan "menunggu validasi".
     *
     * @return array<int,array{slot:object,sudah_dijurnal:bool,id_jurnal:int}>
     */
    public function getStatusJurnalHarian(int $idGuru, int $idTp, int $idSmt, int $hari, string $tanggal, bool $hanyaTervalidasi = false): array
    {
        if ($idGuru <= 0 || $tanggal === '') {
            return [];
        }

        $slots = (new ElearningModel())->getJadwalKbmGuruHariIni($idGuru, $hari, $idTp, $idSmt);
        if (empty($slots)) {
            return [];
        }

        $jurnal = $this->db->table($this->table)
            ->select($hanyaTervalidasi
                ? 'id_jurnal, id_jadwal, id_kelas, id_mapel, jam_ke, validasi_status'
                : 'id_jurnal, id_jadwal, id_kelas, id_mapel, jam_ke')
            ->where('id_guru', $idGuru)
            ->where('tanggal', $tanggal)
            ->orderBy('id_jurnal', 'ASC')
            ->get()->getResult();

        $hasil = self::cocokkanJurnalKeSlot($slots, $jurnal);

        if ($hanyaTervalidasi) {
            $status = [];
            foreach ($jurnal as $j) {
                $status[(int) $j->id_jurnal] = strtoupper(trim((string) ($j->validasi_status ?? '')));
            }

            foreach ($hasil as &$row) {
                $st = $status[$row['id_jurnal']] ?? '';
                if ($row['sudah_dijurnal'] && $st !== self::VALIDASI_APPROVED) {
                    $row['sudah_dijurnal']    = false;
                    $row['menunggu_validasi'] = true;
                    $row['validasi_status']   = $st;
                }
            }
            unset($row);
        }

        return $hasil;
    }

    /**
     * Cocokkan baris jurnal satu guru pada satu tanggal ke daftar slot jadwalnya.
     *
     * Dipakai bersama oleh halaman guru (gerbang wajib jurnal) dan halaman admin
     * (monitoring), supaya status "sudah/belum dijurnal" tidak pernah berbeda
     * antara kedua halaman.
     *
     * Jurnal terhubung jadwal dipetakan lewat id_jadwal. Jurnal legacy tanpa
     * id_jadwal dicocokkan per kelas:mapel:jam bila jamnya terisi. Jurnal tanpa
     * jam hanya dipakai sebagai cadangan untuk data lama, sedangkan jurnal berjam
     * yang tidak cocok tidak boleh menutupi slot lain.
     *
     * @param array<int,object> $slots  baris jadwal (id_jadwal, id_kelas, id_mapel, jam_ke)
     * @param array<int,object> $jurnal baris jurnal guru (id_jurnal, id_jadwal, id_kelas, id_mapel, jam_ke)
     * @return array<int,array{slot:object,sudah_dijurnal:bool,id_jurnal:int}>
     */
    public static function cocokkanJurnalKeSlot(array $slots, array $jurnal): array
    {
        if (empty($slots)) {
            return [];
        }

        $byJadwal = [];
        $byPairJam = [];
        $legacyAnyByPair = [];
        $legacyEmptyByPair = [];
        foreach ($jurnal as $j) {
            $idJurnal = (int) $j->id_jurnal;
            $idJadwal = (int) ($j->id_jadwal ?? 0);
            if ($idJadwal > 0) {
                $byJadwal[$idJadwal] = $idJurnal;
                continue;
            }

            $pair = (int) $j->id_kelas . ':' . (int) $j->id_mapel;
            $jam = self::normalizeJamKe($j->jam_ke ?? '');
            if ($jam === '') {
                $legacyEmptyByPair[$pair][] = $idJurnal;
                continue;
            }

            $byPairJam[$pair . ':' . $jam][] = $idJurnal;
            $legacyAnyByPair[$pair][] = $idJurnal;
        }

        $pairSlotCount = [];
        foreach ($slots as $slot) {
            $pair = (int) ($slot->id_kelas ?? 0) . ':' . (int) ($slot->id_mapel ?? 0);
            $pairSlotCount[$pair] = ($pairSlotCount[$pair] ?? 0) + 1;
        }

        $used = [];
        $out = [];
        foreach ($slots as $slot) {
            $idJurnal = (int) ($byJadwal[(int) ($slot->id_jadwal ?? 0)] ?? 0);
            if ($idJurnal > 0 && isset($used[$idJurnal])) {
                $idJurnal = 0;
            }

            if ($idJurnal <= 0) {
                $pair = (int) $slot->id_kelas . ':' . (int) $slot->id_mapel;
                $jam = self::normalizeJamKe($slot->jam_ke ?? '');
                $pairJamKey = $pair . ':' . $jam;

                if ($jam !== '' && !empty($byPairJam[$pairJamKey])) {
                    while (!empty($byPairJam[$pairJamKey])) {
                        $candidate = (int) array_shift($byPairJam[$pairJamKey]);
                        if (!isset($used[$candidate])) {
                            $idJurnal = $candidate;
                            break;
                        }
                    }
                }

                // Jam legacy bisa berbeda penulisan ("1-2" vs "1"). Bila
                // pasangan ini hanya punya satu slot, jurnal tersebut tetap
                // dianggap milik slot itu.
                if ($idJurnal <= 0 && $jam !== '' && ($pairSlotCount[$pair] ?? 0) === 1
                    && !empty($legacyAnyByPair[$pair])) {
                    while (!empty($legacyAnyByPair[$pair])) {
                        $candidate = (int) array_shift($legacyAnyByPair[$pair]);
                        if (!isset($used[$candidate])) {
                            $idJurnal = $candidate;
                            break;
                        }
                    }
                }

                if ($idJurnal <= 0 && !empty($legacyEmptyByPair[$pair])) {
                    while (!empty($legacyEmptyByPair[$pair])) {
                        $candidate = (int) array_shift($legacyEmptyByPair[$pair]);
                        if (!isset($used[$candidate])) {
                            $idJurnal = $candidate;
                            break;
                        }
                    }
                }
            }

            if ($idJurnal > 0) {
                $used[$idJurnal] = true;
            }

            $out[] = [
                'slot' => $slot,
                'sudah_dijurnal' => $idJurnal > 0,
                'id_jurnal' => $idJurnal,
            ];
        }

        return $out;
    }

    /**
     * Samakan penulisan jam ke ("1", "01", "Jam 1", "1-2") sebelum dicocokkan.
     * Rentang multi-jam diwakili nomor jam pertamanya, sesuai satu baris jadwal
     * untuk sesi gabungan.
     */
    public static function normalizeJamKe($value): string
    {
        $jam = trim((string) $value);
        if ($jam === '' || $jam === '-') {
            return '';
        }

        if (preg_match('/\d+/', $jam, $match)) {
            return (string) (int) $match[0];
        }

        return mb_strtolower(preg_replace('/\s+/', '', $jam));
    }

    /**
     * Sesi jadwal guru pada satu hari yang menahan absen pulang.
     *
     * "Belum diisi" dihitung sejak sesi dimulai: jam mulai lewat, atau — untuk
     * data lama tanpa jam mulai — jam selesai lewat.
     *
     * "Diisi tapi belum disetujui" baru dihitung setelah sesi selesai, sebab
     * persetujuan piket tidak mungkin sudah ada ketika kelasnya masih
     * berlangsung.
     *
     * Slot tanpa jam mulai dan jam selesai tidak pernah menahan: tanpa keduanya
     * tidak ada bukti sesinya sudah lewat, dan data jadwal yang tidak lengkap
     * tidak boleh mengunci guru di gerbang.
     *
     * @return array<int,object> baris jadwal yang belum memenuhi gerbang
     */
    public function getSesiBelumDijurnal(int $idGuru, int $idTp, int $idSmt, int $hari, string $tanggal, string $batasWaktu = '', bool $hanyaTervalidasi = false): array
    {
        $batas = substr(trim($batasWaktu), 0, 5);
        $missing = [];
        foreach ($this->getStatusJurnalHarian($idGuru, $idTp, $idSmt, $hari, $tanggal, $hanyaTervalidasi) as $row) {
            if ($row['sudah_dijurnal']) {
                continue;
            }

            $slot = $row['slot'];
            $menungguValidasi = !empty($row['menunggu_validasi']);
            if ($menungguValidasi) {
                $slot->menunggu_validasi = true;
                $slot->validasi_status   = (string) ($row['validasi_status'] ?? '');
                $slot->id_jurnal         = (int) $row['id_jurnal'];
            }
            $dari = substr((string) ($slot->dari ?? ''), 0, 5);
            $sampai = substr((string) ($slot->sampai ?? ''), 0, 5);

            if ($batas !== '') {
                if ($dari === '' && $sampai === '') {
                    continue;
                }

                if ($menungguValidasi) {
                    if (!$this->slotSudahSelesai($dari, $sampai, $batas)) {
                        continue;
                    }
                } elseif ($dari !== '') {
                    if (strcmp($dari, $batas) > 0) {
                        continue;
                    }
                } elseif ($sampai !== '' && strcmp($sampai, $batas) > 0) {
                    continue;
                }
            }

            $missing[] = $slot;
        }

        return $missing;
    }

    /**
     * Sesi dianggap selesai bila jam selesainya sudah lewat. Data tanpa jam
     * selesai dinilai dari jam mulainya: lewat jam mulai dianggap sesi sudah
     * berjalan penuh.
     */
    private function slotSudahSelesai(string $dari, string $sampai, string $batas): bool
    {
        if ($sampai !== '') {
            return strcmp($sampai, $batas) <= 0;
        }

        return $dari === '' || strcmp($dari, $batas) < 0;
    }
}
