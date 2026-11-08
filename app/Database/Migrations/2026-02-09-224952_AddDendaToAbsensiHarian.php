<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDendaToAbsensiHarian extends Migration
{
    public function up()
    {
        try {
            $fields = [
                'denda' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0,
                    'after'      => 'points'
                ],
                'keterangan_denda' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                    'after'      => 'denda'
                ]
            ];

            $this->forge->addColumn('absensi_harian', $fields);
        } catch (\Throwable $e) {
            // Column may already exist
        }
    }

    public function down()
    {
        try {
            $this->forge->dropColumn('absensi_harian', ['denda', 'keterangan_denda']);
        } catch (\Throwable $e) {}
    }
}
