<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Format penomoran Surat Teguran dapat dikonfigurasi dari /settings.
 * Default dipertahankan sama dengan format lama agar surat yang sudah
 * terbit tidak berubah tampilannya.
 */
class AddSuratTeguranNomorSetting extends Migration
{
    private array $columns = [
        'kode_sekolah' => [
            'type'       => 'VARCHAR',
            'constraint' => 50,
            'null'       => true,
            'default'    => 'SMAN2.AT',
        ],
        'surat_teguran_format' => [
            'type'       => 'VARCHAR',
            'constraint' => 120,
            'null'       => true,
            'default'    => 'SP/421.3/{kode_sekolah}/{nomor_urut}/{tahun}',
        ],
        'surat_teguran_padding' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'null'       => true,
            'default'    => 3,
        ],
    ];

    public function up()
    {
        if (!$this->db->tableExists('setting')) {
            return;
        }

        $add = [];
        foreach ($this->columns as $col => $def) {
            if (!$this->db->fieldExists($col, 'setting')) {
                $add[$col] = $def;
            }
        }
        if (!empty($add)) {
            $this->forge->addColumn('setting', $add);
        }

        // Kolom `kode_sekolah` sebenarnya sudah ada di instance lama (CI3) tetapi
        // tidak pernah dipakai, dan isinya tidak sesuai untuk nomor surat. Set
        // ke nilai bawaan bila kosong atau isinya hanya angka (duplikat NPSN)
        // supaya nomor teguran tetap sama seperti sebelumnya operator mengubahnya.
        $row = $this->db->table('setting')->select('id_setting, kode_sekolah')->limit(1)->get()->getRow();
        if ($row) {
            $kode = trim((string) ($row->kode_sekolah ?? ''));
            if ($kode === '' || preg_match('/^[0-9]+$/', $kode) === 1) {
                $this->db->table('setting')->where('id_setting', $row->id_setting)
                    ->update(['kode_sekolah' => 'SMAN2.AT']);
            }
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('setting')) {
            return;
        }

        foreach (array_keys($this->columns) as $col) {
            if ($this->db->fieldExists($col, 'setting')) {
                $this->forge->dropColumn('setting', $col);
            }
        }
    }
}
