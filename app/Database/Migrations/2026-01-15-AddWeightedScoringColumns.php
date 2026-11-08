<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to add weighted scoring columns to cbt_nilai table
 * 
 * These columns store detailed scores per question type after
 * implementing the weighted scoring system.
 */
class AddWeightedScoringColumns extends Migration
{
    public function up()
    {
        $forge = \Config\Database::forge();
        $db = \Config\Database::connect();

        // Check if table doesn't exist - skip migration
        $tables = $db->listTables();
        if (!in_array('cbt_nilai', $tables)) {
            return;
        }

        // Get existing fields
        $existingFields = $db->getFieldNames('cbt_nilai');

        // Add new columns for weighted scoring
        $fields = [];

        // Column: nilai_asli - stores original score before katrol
        if (!in_array('nilai_asli', $existingFields)) {
            $fields['nilai_asli'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'pg_nilai'
            ];
        }

        // Column: pg_skor - PG weighted score
        if (!in_array('pg_skor', $existingFields)) {
            $fields['pg_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'nilai_asli'
            ];
        }

        // Column: kompleks_skor - PG Kompleks weighted score
        if (!in_array('kompleks_skor', $existingFields)) {
            $fields['kompleks_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'pg_skor'
            ];
        }

        // Column: jodohkan_skor - Menjodohkan weighted score
        if (!in_array('jodohkan_skor', $existingFields)) {
            $fields['jodohkan_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'kompleks_skor'
            ];
        }

        // Column: isian_skor - Isian Singkat weighted score
        if (!in_array('isian_skor', $existingFields)) {
            $fields['isian_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'jodohkan_skor'
            ];
        }

        // Column: esai_skor - Esai weighted score (manual grading)
        if (!in_array('esai_skor', $existingFields)) {
            $fields['esai_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'isian_skor'
            ];
        }

        // Column: urutan_skor - Mengurutkan weighted score
        if (!in_array('urutan_skor', $existingFields)) {
            $fields['urutan_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'esai_skor'
            ];
        }

        // Column: cloze_skor - Cloze/Isian Rumpang weighted score
        if (!in_array('cloze_skor', $existingFields)) {
            $fields['cloze_skor'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0,
                'after' => 'urutan_skor'
            ];
        }

        // Column: detail_json - JSON string containing detailed scoring breakdown
        if (!in_array('detail_json', $existingFields)) {
            $fields['detail_json'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'cloze_skor'
            ];
        }

        // Execute migration if there are fields to add
        if (!empty($fields)) {
            $forge->addColumn('cbt_nilai', $fields);
        }
    }

    public function down()
    {
        $forge = \Config\Database::forge();
        $db = \Config\Database::connect();

        // Get existing fields
        $existingFields = $db->getFieldNames('cbt_nilai');

        // Remove added columns (reverse migration)
        $columnsToRemove = [
            'nilai_asli',
            'pg_skor',
            'kompleks_skor',
            'jodohkan_skor',
            'isian_skor',
            'esai_skor',
            'urutan_skor',
            'cloze_skor',
            'detail_json'
        ];

        foreach ($columnsToRemove as $column) {
            if (in_array($column, $existingFields)) {
                $forge->dropColumn('cbt_nilai', $column);
            }
        }
    }
}
