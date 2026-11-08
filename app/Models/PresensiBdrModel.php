<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Jadwal BDR (Belajar Dari Rumah) — tabel `presensi_bdr`.
 *
 * Satu baris = satu periode BDR untuk satu lingkup:
 *  - SEMUA   : seluruh kelas (id_kelas & level_id kosong)
 *  - TINGKAT : satu tingkat/kelas paralel, mis. XII saja (level_id terisi)
 *  - KELAS   : satu kelas spesifik (id_kelas terisi)
 *
 * `tgl_selesai` kosong berarti hanya satu hari (`tgl_mulai`).
 */
class PresensiBdrModel extends Model
{
    protected $table = 'presensi_bdr';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'lingkup', 'id_kelas', 'level_id',
        'tgl_mulai', 'tgl_selesai', 'judul', 'keterangan', 'created_by',
    ];

    public const LINGKUP = ['SEMUA', 'TINGKAT', 'KELAS'];

    /**
     * Baris jadwal yang aktif pada satu tanggal.
     *
     * @return array<int, object>
     */
    public function getRowsUntukTanggal(string $tgl): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $q = $this->db->table($this->table)
            ->where('tgl_mulai <=', $tgl);
        $q->groupStart()
            ->where('tgl_selesai IS NULL', null, false)
            ->orWhere('tgl_selesai >=', $tgl)
            ->groupEnd();

        return $q->orderBy('id', 'ASC')->get()->getResult();
    }

    /**
     * Baris jadwal yang bersinggungan dengan sebuah rentang tanggal.
     *
     * @return array<int, object>
     */
    public function getRowsDalamRentang(string $tglAwal, string $tglAkhir): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $q = $this->db->table($this->table)
            ->where('tgl_mulai <=', $tglAkhir);
        $q->groupStart()
            ->where('tgl_selesai IS NULL', null, false)
            ->orWhere('tgl_selesai >=', $tglAwal)
            ->groupEnd();

        return $q->orderBy('tgl_mulai', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
    }

    /**
     * Daftar jadwal untuk halaman admin, lengkap dengan nama lingkup terbaca.
     *
     * @return array<int, object>
     */
    public function getDaftar(int $limit = 200, ?string $sejak = null): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $q = $this->db->table('presensi_bdr b')
            ->select('b.*, mk.nama_kelas, lk.level', false)
            ->join('master_kelas mk', 'mk.id_kelas = b.id_kelas', 'left')
            ->join('level_kelas lk', 'lk.id_level = b.level_id', 'left')
            ->orderBy('b.tgl_mulai', 'DESC')
            ->orderBy('b.id', 'DESC')
            ->limit($limit);
        if ($sejak !== null) {
            // Jadwal yang masih berlangsung pada/ sesudah tanggal ini
            // (tgl_selesai kosong = satu hari, dipakai apa adanya).
            $q->groupStart()
                ->where('tgl_selesai >=', $sejak)
                ->orWhere('tgl_mulai >=', $sejak)
                ->groupEnd();
        }

        return $q->get()->getResult();
    }

    /** Bentrok tanggal yang sama untuk lingkup/kelas yang sama (cegah dobel). */
    public function adaBentrok(string $lingkup, ?int $idKelas, ?int $levelId, string $tglMulai, ?string $tglSelesai, int $kecualikanId = 0): bool
    {
        if (! $this->db->tableExists($this->table)) {
            return false;
        }
        $tglSelesai = $tglSelesai ?: $tglMulai;

        $q = $this->db->table($this->table)->where('lingkup', $lingkup);
        if ($kecualikanId > 0) {
            $q->where('id !=', $kecualikanId);
        }
        if ($lingkup === 'KELAS') {
            $q->where('id_kelas', (int) $idKelas);
        } elseif ($lingkup === 'TINGKAT') {
            $q->where('level_id', (int) $levelId);
        }

        // Overlap rentang: mulai <= selesaiBaru AND (selesai >= mulaiBaru OR selesai NULL)
        $q->where('tgl_mulai <=', $tglSelesai);
        $q->groupStart()->where('tgl_selesai >=', $tglMulai)->orWhere('tgl_selesai IS NULL', null, false)->groupEnd();

        return $q->countAllResults() > 0;
    }
}
