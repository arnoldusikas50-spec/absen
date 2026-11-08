<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menggabungkan dua sumber data "hari libur mingguan" jadi satu.
 *
 * Sebelumnya ada dua tempat terpisah:
 *   - absensi_jadwal_kerja.is_libur        (tombol "Status Libur" di Jadwal Kerja Mingguan GTK)
 *   - absensi_setting.hari_libur_mingguan  (checkbox "Hari Libur Mingguan")
 *
 * Keduanya disimpan dari form yang sama tapi tidak pernah disinkronkan, dan
 * tiap layar membacanya secara berbeda — sehingga satu hari bisa tampil
 * sebagai libur di halaman Absen Manual tapi tetap dihitung sebagai hari
 * kerja di Rekap, atau sebaliknya.
 *
 * Solusi: absensi_jadwal_kerja.is_libur menjadi sumber kebenaran, dan
 * hari_libur_mingguan diturunkan darinya (kolom lama tetap dipakai kode
 * lama, tapi isinya selalu konsisten).
 *
 * Kebersihan data memakai UNION: hari dianggap libur kalau salah satu
 * sumber menandainya libur. Ini sisi yang aman — tidak ada siswa atau guru
 * yang tiba-tiba kena alpha / surat teguran karena setting lama yang basi.
 * Admin bisa memangkas lewat toggle "Status Libur" di /settings.
 */
class MergeWeeklyHolidaySource extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('absensi_jadwal_kerja')) {
            return;
        }

        // 1. Pastikan 7 baris jadwal ada (1=Senin .. 7=Minggu).
        for ($hari = 1; $hari <= 7; $hari++) {
            if ($db->table('absensi_jadwal_kerja')->where('hari', $hari)->countAllResults() === 0) {
                $db->table('absensi_jadwal_kerja')->insert([
                    'hari'       => $hari,
                    'jam_masuk'  => '07:15:00',
                    'jam_pulang' => '14:00:00',
                    'is_libur'   => 0,
                ]);
            }
        }

        // 2. Baca hari libur mingguan lama dari setting.
        $liburLama = [];
        foreach (['absensi_setting', 'pengaturan'] as $tabel) {
            if (!$db->tableExists($tabel)) {
                continue;
            }
            $row = $db->table($tabel)->where('id', 1)->get()->getRow();
            if ($row && !empty($row->hari_libur_mingguan)) {
                $liburLama = array_map('trim', explode(',', (string) $row->hari_libur_mingguan));
                if ($db->tableExists($tabel) && $db->fieldExists('hari_libur_mingguan', $tabel)) {
                    break;
                }
            }
        }

        $isoDariNama = [
            'Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4,
            'Fri' => 5, 'Sat' => 6, 'Sun' => 7,
        ];

        // 3. UNION: kalau salah satu sumber bilang libur, tetapkan libur.
        $gabungan = [];
        foreach ($db->table('absensi_jadwal_kerja')->select('hari, is_libur')->get()->getResult() as $r) {
            $gabungan[(int) $r->hari] = ((int) $r->is_libur === 1);
        }
        foreach ($liburLama as $nama) {
            $nama = ucfirst(strtolower((string) $nama));
            if (isset($isoDariNama[$nama])) {
                $gabungan[$isoDariNama[$nama]] = true;
            }
        }

        foreach ($gabungan as $hari => $isLibur) {
            $db->table('absensi_jadwal_kerja')
                ->where('hari', $hari)
                ->update(['is_libur' => $isLibur ? 1 : 0]);
        }

        // 4. Tulis balik hari_libur_mingguan sebagai turunan, supaya kolom lama
        //    tidak pernah menyimpang dari jadwal.
        $turunan = [];
        foreach ($isoDariNama as $nama => $iso) {
            if (!empty($gabungan[$iso])) {
                $turunan[] = $nama;
            }
        }
        $csv = $turunan ? implode(',', $turunan) : '';

        foreach (['absensi_setting', 'pengaturan'] as $tabel) {
            if ($db->tableExists($tabel) && $db->fieldExists('hari_libur_mingguan', $tabel)) {
                $db->table($tabel)->where('id', 1)->update(['hari_libur_mingguan' => $csv]);
            }
        }
    }

    public function down()
    {
        // Gabungan bersifat destruktif terhadap mana yang "benar", jadi tidak
        // di-rollback.(absensi_jadwal_kerja.is_libur sudah selalu ada isinya.)
    }
}
