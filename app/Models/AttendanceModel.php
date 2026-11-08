<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table = 'absensi_harian';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'id_guru',
        'tgl',
        'jam_masuk',
        'lat_masuk',
        'lng_masuk',
        'lokasi_masuk_id',
        'jarak_masuk',
        'foto_masuk',
        'status_masuk',
        'telat_menit',
        'datang_awal_menit',
        'ip_address',
        'device_info',
        'gps_accuracy',
        'is_mock_location',
        'jam_pulang',
        'lat_pulang',
        'lng_pulang',
        'lokasi_pulang_id',
        'jarak_pulang',
        'foto_pulang',
        'status_pulang',
        'pulang_cepat_menit',
        'pulang_akhir_menit',
        'points',
        'denda',
        'keterangan_denda',
        'keterangan',
        // Penanda baris absensi yang ditulis oleh sebuah pengajuan izin.
        // WAJIB ada di sini: CI4 memanggil doProtectFieldsForInsert() pada
        // setiap insert()/update(), dan field yang tidak terdaftar di
        // $allowedFields dibuang TANPA error. Kalau kolom ini lupa, approve
        // izin tetap terlihat sukses tetapi tidak pernah meninggalkan
        // penanda — sehingga deletePermit()/rejectPermit() tidak bisa tahu
        // baris mana yang miliknya dan revert tidak pernah terjadi.
        'izin_id'
    ];

protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Apakah baris absensi ini ditulis oleh pengajuan izin tertentu?
     *
     * Dipakai saat revert (delete izin / tolak izin) supaya hanya baris
     * yang benar-benar milik pengajuan itu yang disentuh.
     *
     * Dua jalur, dan pemisahnya penting:
     *
     *  - Baris BERTANDA (`izin_id` terisi): jawabannya tegas, penandanya
     *    yang menentukan. Heuristik lama TIDAK ikut dipakai di sini.
     *
     *  - Baris LAMA (`izin_id` NULL, ditulis sebelum kolomnya ada): tidak
     *    ada penanda, jadi jatuh ke heuristik lama — jam kosong + status
     *    persis dari izin ini.
     *
     * Kenapa heuristik lama harus dibatasi ke baris tanpa penanda saja:
     * approve mengosongkan `jam_masuk` baris yang sebelumnya berisi scan
     * asli guru. Kalau heuristik itu boleh dipakai pada baris bertanda,
     * delete izin berikutnya akan membaca "jam kosong + status izin" dan
     * MENGHAPUS baris itu — bukti kehadiran guru hilang permanen.
     *
     * @param object|null $row          Baris absensi_harian.
     * @param int         $izinId       Id baris absensi_izin.
     * @param string      $permitStatus Status absensi hasil normalisasi jenis
     *                                  izin (mis. IJIN / SAKIT / CUTI).
     */
    public static function apakahMilikIzin(?object $row, int $izinId, string $permitStatus): bool
    {
        if ($row === null) {
            return false;
        }

        // Baris yang berisi scan nyata tidak pernah "milik" pengajuan, apa pun
        // tag izin_id di atasnya. Penolakan/penghapusan izin yang memercayai
        // tag itu saja akan mengubah hari yang benar-benar hadir menjadi ALPHA
        // atau menghapusnya — bukti kehadiran guru hilang tanpa jejak.
        if (!empty($row->jam_masuk)) {
            return false;
        }

        $tanda = $row->izin_id ?? null;
        if ($tanda !== null) {
            return (int) $tanda === $izinId;
        }

        return empty($row->jam_masuk)
            && strtoupper((string) ($row->status_masuk ?? '')) === $permitStatus;
    }
}
