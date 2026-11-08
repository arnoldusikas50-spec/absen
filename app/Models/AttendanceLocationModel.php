<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceLocationModel extends Model
{
    protected $table = 'absensi_lokasi';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'nama_lokasi',
        'lat',
        'lng',
        'radius_meter',
        'keterangan',
        'is_utama'
    ];

    protected $useTimestamps = false; // Disable to fail-safe
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Lokasi utama yang ditandai (kantor pusat).
     * Null bila belum ada yang ditandai atau tabelnya belum punya kolom.
     */
    public function getUtama(): ?object
    {
        try {
            if (!$this->db->tableExists($this->table)) {
                return null;
            }
            if (!$this->db->fieldExists('is_utama', $this->table)) {
                return null;
            }
            return $this->where('is_utama', 1)->orderBy('id', 'ASC')->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Jadikan satu lokasi sebagai lokasi utama.
     *
     * Hanya satu lokasi boleh berflag is_utama=1. Bila $id null/0, semua
     * flag dilepas (dipakai saat operator membatalkan penandaan).
     *
     * @return ?object lokasi utama setelah perubahan
     */
    public function setUtama($id): ?object
    {
        try {
            if (!$this->db->fieldExists('is_utama', $this->table)) {
                return null;
            }

            $this->db->transStart();
            $this->db->table($this->table)->update(['is_utama' => 0]);
            $id = (int) $id;
            if ($id > 0) {
                $this->db->table($this->table)->where('id', $id)->update(['is_utama' => 1]);
            }
            $this->db->transComplete();

            return $id > 0 ? $this->getUtama() : null;
        } catch (\Throwable $e) {
            log_message('error', 'setUtama gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pastikan tepat satu lokasi utama ada (dipanggil saat menyimpan lokasi).
     * Bila belum ada yang ditandai, lokasi yang sedang disimpan menjadi utama
     * agar absen manual selalu punya acuan koordinat.
     */
    public function ensureAdaUtama(): void
    {
        if ($this->getUtama() !== null) {
            return;
        }
        try {
            $first = $this->orderBy('id', 'ASC')->first();
            if ($first) {
                $this->setUtama((int) $first->id);
            }
        } catch (\Throwable $e) {
            log_message('error', 'ensureAdaUtama gagal: ' . $e->getMessage());
        }
    }
}
