<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Akses tabel jabatan_guru dengan kunci periode yang aman.
 *
 * id_jabatan_guru = "{id_guru}-{id_tp}-{id_smt}". Pemisah wajib ada agar
 * kombinasi seperti guru 1/TP 61/SMT 0 dan guru 16/TP 1/SMT 0 tidak
 * menghasilkan kunci yang sama seperti penggabungan angka tanpa pemisah.
 * Baris lama tetap dicari lewat guru + TP + SMT, bukan lewat bentuk kuncinya.
 */
class JabatanGuruModel extends Model
{
    protected $table = 'jabatan_guru';
    protected $primaryKey = 'id_jabatan_guru';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_jabatan_guru',
        'id_guru',
        'id_jabatan',
        'id_kelas',
        'mapel_kelas',
        'ekstra_kelas',
        'id_tp',
        'id_smt',
    ];

    public static function makeKey(int $idGuru, int $idTp, int $idSmt): string
    {
        return $idGuru . '-' . $idTp . '-' . $idSmt;
    }

    /** Baris jabatan milik guru pada satu periode, apa pun format kuncinya. */
    public function findForGuru(int $idGuru, int $idTp, int $idSmt): ?object
    {
        return $this->db->table($this->table)
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->orderBy('id_jabatan_guru', 'ASC')
            ->get()->getRow();
    }

    /**
     * Simpan jabatan guru pada satu periode tanpa membuat baris ganda.
     * Baris lama dicari lewat guru + periode sehingga data dengan kunci
     * format lama tetap ikut diperbarui.
     */
    public function upsert(int $idGuru, int $idTp, int $idSmt, array $data): bool
    {
        $existing = $this->findForGuru($idGuru, $idTp, $idSmt);

        unset($data['id_jabatan_guru'], $data['id_guru'], $data['id_tp'], $data['id_smt']);

        if ($existing) {
            return $this->db->table($this->table)
                ->where('id_jabatan_guru', $existing->id_jabatan_guru)
                ->update($data);
        }

        $data = array_merge([
            'id_jabatan' => 0,
            'id_kelas' => 0,
            'mapel_kelas' => '[]',
            'ekstra_kelas' => '[]',
        ], $data);
        $data['id_jabatan_guru'] = self::makeKey($idGuru, $idTp, $idSmt);
        $data['id_guru'] = $idGuru;
        $data['id_tp'] = $idTp;
        $data['id_smt'] = $idSmt;

        return $this->db->table($this->table)->insert($data);
    }

    /** Lepas status wali dari semua guru lain pada satu kelas. */
    public function releaseWaliKelas(int $idTp, int $idSmt, int $idKelas, ?int $keepGuru = null): void
    {
        if ($idKelas <= 0) return;

        // Baris wali resmi (level 4) dilepas sekaligus jabatannya.
        $builder = $this->db->table($this->table)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('id_kelas', $idKelas)
            ->where('id_jabatan', '4');
        if ($keepGuru !== null) $builder->where('id_guru !=', $keepGuru);
        $builder->update(['id_kelas' => 0, 'id_jabatan' => '']);

        // Data lama bisa menyimpan id_kelas tanpa level 4; lepaskan kelasnya saja.
        $legacy = $this->db->table($this->table)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('id_kelas', $idKelas)
            ->where('id_jabatan !=', '4');
        if ($keepGuru !== null) $legacy->where('id_guru !=', $keepGuru);
        $legacy->update(['id_kelas' => 0]);
    }

    /**
     * Samakan master_kelas.guru_id dengan jabatan wali pada periode aktif.
     * $idKelas = 0 berarti guru ini tidak lagi menjadi wali kelas mana pun.
     */
    public function syncWaliToMasterKelas(int $idGuru, int $idTp, int $idSmt, int $idKelas): void
    {
        if (!$this->db->tableExists('master_kelas') || !$this->db->fieldExists('guru_id', 'master_kelas')) {
            return;
        }

        $this->db->table('master_kelas')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('guru_id', $idGuru)
            ->update(['guru_id' => 0]);

        if ($idKelas > 0) {
            $this->db->table('master_kelas')
                ->where('id_kelas', $idKelas)
                ->where('id_tp', $idTp)
                ->where('id_smt', $idSmt)
                ->update(['guru_id' => $idGuru]);
        }
    }
}
