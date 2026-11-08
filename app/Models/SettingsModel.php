<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SettingsModel - Migrated from CI3 Settings_model
 * Handles application settings
 */
class SettingsModel extends Model
{
    protected $table = 'setting';
    protected $primaryKey = 'id_setting';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $allowedFields = [
        'nama_aplikasi',
        'sekolah',
        'nss',
        'npsn',
        'jenjang',
        'satuan_pendidikan',
        'alamat',
        'desa',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        'telp',
        'fax',
        'email',
        'web',
        'kepsek',
        'nip',
        'proktor',
        'nip_proktor',
        'logo_kiri',
        'logo_kanan',
        'stampel',
        'tanda_tangan',
        'ttdproktor',
        'kodescan',
        'kopsekolah',
        'banner1',
        'banner2',
        'banner3',
        'tp_aktif',
        'smt_aktif',
        'tkn_siswa',
        'mode_app',
        'ba_aktif',
        'ba_waktu',
        'api_key',
        'sync_school_uuid',
        'kode_sekolah',
        'surat_teguran_format',
        'surat_teguran_padding',
        'theme_preset',
        'theme_custom',
    ];

    protected $useTimestamps = false;

    /**
     * Get current settings
     * Adds compatibility aliases for property names used in different views
     */
    public function getSetting(): ?object
    {
        $setting = $this->first();

        if ($setting) {
            // Add aliases for compatibility with views
            if (isset($setting->sekolah) && !isset($setting->nama_sekolah)) {
                $setting->nama_sekolah = $setting->sekolah;
            }
            if (isset($setting->logo_kiri) && !isset($setting->logo)) {
                $setting->logo = $setting->logo_kiri;
            }
            if (isset($setting->web) && !isset($setting->website)) {
                $setting->website = $setting->web;
            }
        }

        return $setting;
    }

    /**
     * Update settings
     */
    public function updateSetting(array $data): bool
    {
        $setting = $this->first();

        if ($setting) {
            return $this->update($setting->id_setting, $data);
        }

        return $this->insert($data) !== false;
    }

    /**
     * Get active tahun pelajaran
     */
    public function getActiveTp(): ?int
    {
        $setting = $this->first();
        return $setting ? (int) $setting->tp_aktif : null;
    }

    /**
     * Get active semester
     */
    public function getActiveSmt(): ?int
    {
        $setting = $this->first();
        return $setting ? (int) $setting->smt_aktif : null;
    }
    /**
     * Truncate tables and cleanup
     * @param array $tables
     */
    public function truncate($tables)
    {
        $db = \Config\Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $tb) {
            $db->table($tb)->truncate();
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Delete bank soal files
        helper('filesystem');
        delete_files(FCPATH . 'uploads/bank_soal/');

        // Delete non-admin users
        $builder = $db->table('users a');
        $builder->select('a.id');
        $builder->join('users_groups b', 'a.id=b.user_id');
        $builder->whereNotIn('b.group_id', ['1']);
        $users = $builder->get()->getResult();

        foreach ($users as $user) {
            $db->table('users')->where('id', $user->id)->delete();
        }
    }

    /**
     * Get row count
     */
    public function rowSize($table)
    {
        $db = \Config\Database::connect();
        return $db->table($table)->countAllResults();
    }

    /**
     * Get table content as JSON
     */
    public function toJSON($table)
    {
        $db = \Config\Database::connect();
        $result = $db->table($table)->get()->getResult();
        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
