<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk tabel siswa_biodata
 * Menyimpan data extended/tambahan peserta didik
 */
class CreateSiswaBiodataTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_siswa' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            // Dokumen
            'no_registrasi_akta' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'nik_kitas' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'comment' => 'NIK/KITAS untuk WNA',
            ],
            'berkebutuhan_khusus' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of berkebutuhan khusus codes',
            ],
            // Alamat tambahan
            'nama_dusun' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'lintang' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Latitude koordinat',
            ],
            'bujur' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Longitude koordinat',
            ],
            'tempat_tinggal' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
                'comment' => 'Kode tempat tinggal (01-05)',
            ],
            'moda_transportasi' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
                'comment' => 'Kode moda transportasi',
            ],
            'anak_keberapa' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => true,
            ],
            // Data Periodik
            'tinggi_badan' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
                'comment' => 'Dalam cm',
            ],
            'berat_badan' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
                'comment' => 'Dalam kg',
            ],
            'lingkar_kepala' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
                'comment' => 'Dalam cm',
            ],
            'jarak_ke_sekolah' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'jarak_km' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'waktu_tempuh_jam' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'null' => true,
            ],
            'waktu_tempuh_menit' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'null' => true,
            ],
            'jumlah_saudara' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => true,
            ],
            // Registrasi
            'kompetensi_keahlian' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'jenis_pendaftaran' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'tanggal_masuk' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'sekolah_asal' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'no_peserta_un' => [
                'type' => 'VARCHAR',
                'constraint' => 25,
                'null' => true,
            ],
            'no_seri_ijazah' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'no_skhun' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            // Keluar
            'keluar_karena' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'tanggal_keluar' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'alasan_keluar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Kesejahteraan
            'jenis_kesejahteraan' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'no_kartu_kesejahteraan' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'nama_di_kartu' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            // KIP
            'punya_kip' => [
                'type' => 'ENUM',
                'constraint' => ['Ya', 'Tidak'],
                'default' => 'Tidak',
            ],
            'peserta_didik_kip' => [
                'type' => 'ENUM',
                'constraint' => ['Ya', 'Tidak'],
                'default' => 'Tidak',
            ],
            'alasan_menolak_pip' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            // Timestamps
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id_siswa');
        $this->forge->createTable('siswa_biodata', true);
    }

    public function down()
    {
        $this->forge->dropTable('siswa_biodata', true);
    }
}
