<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * LeaveModel — tabel absensi_izin
 * Menyimpan pengajuan izin, sakit, cuti, dan dinas luar guru.
 *
 * Status  : 'pending' | 'approved' | 'rejected'
 * Jenis   : 'ijin' | 'sakit' | 'cuti' | 'dinas'
 * Kategori cuti (jenis 'cuti'):
 *   'tahunan' | 'besar' | 'melahirkan' | 'cap' — lihat
 *   CutiKategoriService. NULL/'' = cuti generik (pegawai non-ASN).
 *
 * CATATAN STATUS LEGACY
 * ---------------------
 * Instalasi lama menyimpan status sebagai angka ('0' pending, '1'
 * approved, '2' rejected). Normalisasinya hanya berjalan di
 * Admin\PresensiAdmin::patchAbsensiIzinTable(), yang tidak pernah dipanggil
 * dari leaveRequest() guru — sehingga baris seperti itu bisa masih ada
 * ketika guru membuka form atau menungguKS verifikasi.
 *
 * Query di bawah karena itu membaca bentuk kanonik DAN bentuk angka
 * sekaligus. Kalau tidak, antrean pending bisa kosong padahal ada
 * pengajuan yang belum diproses, dan kuota bisa lolos dari
 * Akumulasi.
 */
class LeaveModel extends Model
{
    protected $table      = 'absensi_izin';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_id',
        'id_guru',
        'tgl_mulai',
        'tgl_selesai',
        'jenis',
        'kategori_cuti',
        'keterangan',
        'status',
        'bukti',
        'verifikasi_catatan',
        'verified_by',
        'verified_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Bentuk kanonik + bentuk angka lama. */
    public const STATUS_PENDING  = ['pending', '0'];
    public const STATUS_APPROVED = ['approved', '1'];
    public const STATUS_REJECTED = ['rejected', '2'];

    /**
     * Referensi Konstanta status yang SEDANG mengonsumsi kuota
     * (pending + approved, termasuk bentuk angka legacy). Dipakai
     * PresensiAdmin untuk guard input manual, supaya angka yang
     * meng-kunci kuota sama dengan IzinKuotaService::STATUS_MAKAN_KUOTA.
     */
    public const STATUS_MAKAN_KUOTA = ['pending', 'approved', '0', '1'];

    /**
     * Terjemahkan filter status ke sekumpulan nilai yang perlu dicocokkan,
     * termasuk padanan angka legacy-nya.
     */
    public static function statusSet(string $status): array
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending'  => self::STATUS_PENDING,
            'approved' => self::STATUS_APPROVED,
            'rejected' => self::STATUS_REJECTED,
            default    => [$status],
        };
    }

    // ─────────────────────────────────────────────────────────────────────
    // Query helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Semua pengajuan pending (status = 'pending') beserta info guru.
     */
    public function getPending(): array
    {
        return $this->db->table('absensi_izin a')
            ->select('a.*, mg.nama_guru, mg.nip, u.username, u.foto')
            ->join('users u',       'u.id = a.user_id',    'left')
            ->join('master_guru mg','mg.id_user = u.id',   'left')
            ->whereIn('a.status', STATUS_PENDING)
            ->orderBy('a.created_at', 'DESC')
            ->get()->getResult();
    }

    /**
     * Semua pengajuan dengan join lengkap (termasuk verifikator).
     * Filter opsional: bulan, tahun, status, jenis.
     */
    public function getAll(
        string|int $bulan   = '',
        string|int $tahun   = '',
        string     $status  = '',
        string     $jenis   = ''
    ): array {
        $builder = $this->db->table('absensi_izin i')
            ->select('i.*, u.first_name, u.last_name, u.username, u.foto,
                      g.nama_guru, g.nip,
                      adm.first_name AS verifier_first, adm.last_name AS verifier_last')
            ->join('users u',       'u.id = i.user_id',       'left')
            ->join('master_guru g', 'g.id_user = u.id',       'left')
            ->join('users adm',     'adm.id = i.verified_by', 'left');

        if ($bulan && is_numeric($bulan)) {
            $builder->where("(MONTH(i.tgl_mulai) = {$bulan} OR MONTH(i.tgl_selesai) = {$bulan})");
        }
        if ($tahun && is_numeric($tahun)) {
            $builder->where("(YEAR(i.tgl_mulai) = {$tahun} OR YEAR(i.tgl_selesai) = {$tahun})");
        }
        if ($status && $status !== 'all') {
            $filter = strtolower(trim($status));
            $builder->whereIn('i.status', self::statusSet($filter));
        }
        if ($jenis && $jenis !== 'all') {
            // Handle alias ijin/izin
            $j = strtolower($jenis);
            if ($j === 'izin') {
                $builder->groupStart()
                        ->where('i.jenis', 'izin')
                        ->orWhere('i.jenis', 'ijin')
                        ->groupEnd();
            } else {
                $builder->where('i.jenis', $j);
            }
        }

        return $builder->orderBy('i.created_at', 'DESC')->get()->getResult();
    }

    /**
     * Satu record dengan join guru + verifikator.
     */
    public function getWithGuru(int $id): ?object
    {
        return $this->db->table('absensi_izin i')
            ->select('i.*, g.nama_guru, g.nip, u.foto,
                      adm.first_name AS verifier_first, adm.last_name AS verifier_last')
            ->join('users u',       'u.id = i.user_id',       'left')
            ->join('master_guru g', 'g.id_user = u.id',       'left')
            ->join('users adm',     'adm.id = i.verified_by', 'left')
            ->where('i.id', $id)
            ->get()->getRow();
    }
}
