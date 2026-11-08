<?php

namespace App\Services;

/**
 * HariSekolahService — menentukan status satu tanggal: hari kerja sekolah,
 * akhir pekan, atau hari libur.
 *
 * Ucapan harian (pagi & sore) memakainya untuk memilih kalimat yang tepat.
 *
 * Urutan pemeriksaan (selaras dengan markAbsentTeachers()):
 *   1. Hari libur mingguan dari `absensi_setting.hari_libur_mingguan` (CSV
 *      nama hari dalam bahasa Inggris, mis. "Sun"). Didahulukan agar hari
 *      Minggu yang juga tercatat di kalender libur tetap dianggap akhir pekan,
 *      bukan "libur nasional".
 *   2. Jadwal per hari dari `absensi_jadwal_kerja.is_libur`.
 *   3. Tanggal merah dari tabel `absensi_hari_libur`.
 *
 * Catatan penting: kalender libur diisi manual. Bila tabel ini belum punya
 * baris untuk tahun berjalan (mis. belum ada data 2027), hari libur nasional
 * TIDAK akan terdeteksi — hasilnya hari tersebut dianggap hari kerja. Fakta
 * ini ikut dikembalikan sebagai `peringatan` supaya bisa dilog.
 */
class HariSekolahService
{
    public const AKTIF      = 'aktif';
    public const AKHIR_PEKAN = 'akhir_pekan';
    public const LIBUR      = 'libur';

    private const NAMA_HARI = [
        'Sun' => 'Minggu',
        'Mon' => 'Senin',
        'Tue' => 'Selasa',
        'Wed' => 'Rabu',
        'Thu' => 'Kamis',
        'Fri' => 'Jumat',
        'Sat' => 'Sabtu',
    ];

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Status satu tanggal.
     *
     * @param  string|null $tanggal Format Y-m-d. Default hari ini.
     * @return array{status:string,keterangan:string,nama_hari:string,tanggal:string,peringatan:?string}
     */
    public function status(?string $tanggal = null): array
    {
        $tanggal  = $this->normalizeDate($tanggal);
        $namaHari = self::NAMA_HARI[date('D', strtotime($tanggal))] ?? date('D', strtotime($tanggal));

        // 1. Libur mingguan (Minggu / hari yang disetel di pengaturan).
        //    Daftar nama hari datang dari AttendancePolicy, jadi 'SUN',
        //    'minggu', dan ' sun ' dikenali sama seperti jalur absensi.
        $mingguan = AttendancePolicy::hariLiburMingguan();
        $singkat  = date('D', strtotime($tanggal));
        if (in_array($singkat, $mingguan, true)) {
            return $this->result(self::AKHIR_PEKAN, $namaHari, $tanggal, 'Hari ' . $namaHari . ' (libur mingguan)');
        }

        // 2. Jadwal per hari (bisa menandai hari sekolah libur tanpa tanggal merah).
        $iso    = (int) date('N', strtotime($tanggal)); // 1=Senin .. 7=Minggu
        $jadwal = AttendancePolicy::jadwalKerja()[$iso] ?? null;
        if ($jadwal !== null && (int) ($jadwal->is_libur ?? 0) === 1) {
            return $this->result(self::LIBUR, $namaHari, $tanggal, 'Libur per jadwal hari kerja');
        }

        // 3. Tanggal merah / libur sekolah dari kalender.
        $petaLibur = AttendancePolicy::liburNasional();
        if (isset($petaLibur[$tanggal])) {
            $ket = $petaLibur[$tanggal];
            return $this->result(self::LIBUR, $namaHari, $tanggal, $ket !== '' ? $ket : 'Hari libur');
        }

        return $this->result(self::AKTIF, $namaHari, $tanggal, 'Hari kerja sekolah');
    }

    /**
     * Hari libur mingguan dari pengaturan, mis. [['kode'=>'Sat','nama'=>'Sabtu','iso'=>6], ...].
     *
     * @return array<int, array{kode:string,nama:string,iso:int}>
     */
    public function hariLiburMingguan(): array
    {
        $iso = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7];

        $hasil = [];
        foreach (AttendancePolicy::hariLiburMingguan() as $kode) {
            if (! isset(self::NAMA_HARI[$kode])) {
                continue;
            }
            $hasil[] = [
                'kode' => $kode,
                'nama' => self::NAMA_HARI[$kode],
                'iso'  => $iso[$kode],
            ];
        }

        return $hasil;
    }

    /**
     * Nama hari berbahasa Indonesia.
     */
    public function namaHari(?string $tanggal = null): string
    {
        $tanggal = $this->normalizeDate($tanggal);

        return self::NAMA_HARI[date('D', strtotime($tanggal))] ?? '';
    }

    /**
     * Tanggal panjang berbahasa Indonesia, mis. "Rabu, 30 September 2026".
     */
    public function tanggalPanjang(?string $tanggal = null): string
    {
        $tanggal = $this->normalizeDate($tanggal);
        $ts      = strtotime($tanggal);

        return $this->namaHari($tanggal) . ', ' . (int) date('j', $ts)
            . ' ' . (self::NAMA_BULAN[(int) date('n', $ts)] ?? date('F', $ts))
            . ' ' . date('Y', $ts);
    }

    /**
     * Peringatan bila kalender libur belum terisi untuk tahun yang diminta.
     *
     * Tanpa ini, hari libur nasional yang belum masuk tabel akan diam-diam
     * dianggap hari kerja dan ucapan pagi memakai preset yang salah.
     */
    public function peringatanKalender(?string $tanggal = null): ?string
    {
        $tanggal = $this->normalizeDate($tanggal);
        $tahun   = (int) date('Y', strtotime($tanggal));

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('absensi_hari_libur')) {
                return 'Tabel absensi_hari_libur belum ada — libur nasional tidak terdeteksi.';
            }
            $jumlah = (int) $db->table('absensi_hari_libur')
                ->where('tanggal >=', $tahun . '-01-01')
                ->where('tanggal <=', $tahun . '-12-31')
                ->countAllResults();

            if ($jumlah === 0) {
                return 'Kalender libur untuk tahun ' . $tahun . ' masih kosong — libur nasional belum terdeteksi.';
            }
        } catch (\Throwable $e) {
            log_message('error', '[HariSekolahService] periksa kalender libur: ' . $e->getMessage());
            return 'Gagal memeriksa kalender libur — periksa log aplikasi.';
        }

        return null;
    }

    public function isAktif(?string $tanggal = null): bool
    {
        return $this->status($tanggal)['status'] === self::AKTIF;
    }

    /**
     * Tanggal hari sekolah pertama setelah $dari (maks. 14 hari).
     * Dipakai roster piket saat hari ini libur agar tidak tampil kosong.
     */
    public function hariSekolahBerikutnya(?string $dari = null, int $maksHari = 14): ?string
    {
        $dari = $this->normalizeDate($dari);

        for ($i = 1; $i <= $maksHari; $i++) {
            $tanggal = date('Y-m-d', strtotime($dari . ' +' . $i . ' day'));
            if ($this->status($tanggal)['status'] === self::AKTIF) {
                return $tanggal;
            }
        }

        return null;
    }

    private function result(string $status, string $namaHari, string $tanggal, string $keterangan): array
    {
        return [
            'status'     => $status,
            'keterangan' => $keterangan,
            'nama_hari'  => $namaHari,
            'tanggal'    => $tanggal,
            'peringatan' => null,
        ];
    }

    private function normalizeDate(?string $tanggal): string
    {
        $tanggal = trim((string) $tanggal);
        if ($tanggal === '') {
            return date('Y-m-d');
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tanggal, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return date('Y-m-d');
    }
}
