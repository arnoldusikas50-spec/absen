<?php

/**
 * Attendance Helper
 * Fungsi-fungsi utilitas untuk sistem presensi SMA.
 */

if (!function_exists('attendance_status_badge')) {
    /**
     * Render badge HTML untuk status presensi.
     *
     * @param string $status  Status masuk (ONTIME, LATE, IJIN, SAKIT, ALPHA, DINAS_APPROVED, dll)
     * @return string HTML badge
     */
    function attendance_status_badge(string $status): string
    {
        $status = strtoupper(trim($status));
        $map = [
            'ONTIME'           => ['bg-success',   'HADIR'],
            'LATE'             => ['bg-warning text-dark', 'TERLAMBAT'],
            'IJIN'             => ['bg-primary',   'IZIN'],
            'IZIN'             => ['bg-primary',   'IZIN'],
            'SAKIT'            => ['bg-info',      'SAKIT'],
            'CUTI'             => ['bg-secondary', 'CUTI'],
            'ALPHA'            => ['bg-danger',    'ALPHA'],
            'DINAS_PENDING'    => ['bg-warning text-dark', 'DINAS (PENDING)'],
            'DINAS_APPROVED'   => ['bg-primary',   'DINAS'],
            'DINAS_REJECTED'   => ['bg-danger',    'DINAS (DITOLAK)'],
            'EARLY'            => ['bg-warning text-dark', 'PULANG AWAL'],
        ];
        [$cls, $label] = $map[$status] ?? ['bg-secondary', $status ?: '-'];
        return '<span class="badge ' . $cls . '">' . esc($label) . '</span>';
    }
}

if (!function_exists('attendance_persen_badge')) {
    /**
     * Render badge persentase kehadiran dengan warna sesuai threshold.
     *
     * @param float $persen      Persentase kehadiran (0–100)
     * @param float $minPersen   Threshold minimum (default 90)
     * @return string HTML badge
     */
    function attendance_persen_badge(float $persen, float $minPersen = 90.0): string
    {
        if ($persen >= $minPersen) {
            $cls = 'bg-success';
        } elseif ($persen >= $minPersen - 10) {
            $cls = 'bg-warning text-dark';
        } else {
            $cls = 'bg-danger';
        }
        return '<span class="badge ' . $cls . '">' . number_format($persen, 1) . '%</span>';
    }
}

if (!function_exists('attendance_kelayakan_badge')) {
    /**
     * Render badge kelayakan semester.
     *
     * @param string $status 'layak' | 'perhatian' | 'tidak_layak'
     * @return string HTML badge
     */
    function attendance_kelayakan_badge(string $status): string
    {
        $map = [
            'layak'       => ['bg-success', 'Layak'],
            'perhatian'   => ['bg-warning text-dark', 'Perhatian'],
            'tidak_layak' => ['bg-danger', 'Tidak Layak'],
        ];
        [$cls, $label] = $map[$status] ?? ['bg-secondary', '-'];
        return '<span class="badge ' . $cls . '">' . $label . '</span>';
    }
}

if (!function_exists('attendance_format_menit')) {
    /**
     * Format menit ke string human-readable.
     * Contoh: 75 → "1j 15m", 45 → "45m"
     *
     * @param int $menit
     * @return string
     */
    function attendance_format_menit(int $menit): string
    {
        if ($menit <= 0) return '-';
        if ($menit < 60) return $menit . 'm';
        $jam  = intdiv($menit, 60);
        $sisa = $menit % 60;
        return $jam . 'j' . ($sisa > 0 ? ' ' . $sisa . 'm' : '');
    }
}

if (!function_exists('attendance_is_weekly_holiday')) {
    /**
     * Sumber kebenaran tunggal untuk "hari-weekday ini libur atau tidak".
     *
     * Dulu ada dua sumber terpisah (absensi_jadwal_kerja.is_libur dan
     * absensi_setting.hari_libur_mingguan) yang dibaca tidak konsisten oleh
     * hampir setiap layar, sehingga satu hari bisa dianggap libur di satu
     * tempat dan hari kerja di tempat lain. Keduanya kini digabung: status
     * libur hidup di absensi_jadwal_kerja, dan hari_libur_mingguan hanya
     * kolom turunan yang selalu disinkronkan saat jadwal disimpan.
     *
     * Fungsi ini tetap menerima keduanya dan memakai OR, sehingga data lama
     * yang belum tersinkron tidak pernah membuat seseorang tiba-tiba kena
     * alpha atau surat teguran.
     *
     * @param string   $dayEn        Nama hari 3 huruf (Mon, Tue, Sun)
     * @param int      $dayNumISO    1=Senin .. 7=Minggu
     * @param array    $jadwalKerja  Map [hari_iso => object|array{is_libur}]
     * @param string[] $hariLibur    Array nama hari dari setting
     * @return bool
     */
    function attendance_is_weekly_holiday(
        string   $dayEn,
        int      $dayNumISO,
        array    $jadwalKerja = [],
        array    $hariLibur   = []
    ): bool {
        $hariLibur = array_map(
            static fn($d) => ucfirst(strtolower(trim((string) $d))),
            $hariLibur
        );
        if (in_array(ucfirst(strtolower($dayEn)), $hariLibur, true)) {
            return true;
        }
        if (isset($jadwalKerja[$dayNumISO])) {
            $row = $jadwalKerja[$dayNumISO];
            $flag = is_array($row) ? ($row['is_libur'] ?? 0) : ($row->is_libur ?? 0);
            if ((int) $flag === 1) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('attendance_is_holiday_date')) {
    /**
     * Hari libur lengkap: mingguan (gabungan) + nasional per tanggal.
     *
     * @param string   $date        Y-m-d
     * @param array    $jadwalKerja Map [hari_iso => object{is_libur}]
     * @param string[] $hariLibur   Array nama hari dari setting
     * @param array    $nationalHols Array tanggal ['Y-m-d' => true]
     */
    function attendance_is_holiday_date(
        string $date,
        array  $jadwalKerja  = [],
        array  $hariLibur    = [],
        array  $nationalHols = []
    ): bool {
        $dayEn     = date('D', strtotime($date));
        $dayNumISO = (int) date('N', strtotime($date));
        if (attendance_is_weekly_holiday($dayEn, $dayNumISO, $jadwalKerja, $hariLibur)) {
            return true;
        }
        return isset($nationalHols[$date]);
    }
}

if (!function_exists('attendance_holiday_day_names')) {
    /**
     * Daftar nama hari (Mon..Sun) yang berstatus libur, untuk ditampilkan
     * di /settings sebagai ringkasan hasil gabung.
     *
     * @return string[]
     */
    function attendance_holiday_day_names(array $jadwalKerja = [], array $hariLibur = []): array
    {
        $nama = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $out  = [];
        foreach ($nama as $iso => $en) {
            if (attendance_is_weekly_holiday($en, $iso, $jadwalKerja, $hariLibur)) {
                $out[] = $en;
            }
        }
        return $out;
    }
}

if (!function_exists('attendance_is_workday')) {
    /**
     * Cek apakah tanggal adalah hari kerja.
     * Memperhitungkan jadwal mingguan dan hari libur nasional.
     *
     * @param string $date           Format Y-m-d
     * @param array  $hariLibur      Array nama hari ['Sun','Sat'] (dari setting)
     * @param array  $nationalHols   Array tanggal ['Y-m-d' => true]
     * @param array  $jadwalKerja    Map [hari_iso => object{is_libur}]
     * @return bool
     */
    function attendance_is_workday(
        string $date,
        array  $hariLibur      = ['Sun'],
        array  $nationalHols   = [],
        array  $jadwalKerja    = []
    ): bool {
        return !attendance_is_holiday_date($date, $jadwalKerja, $hariLibur, $nationalHols);
    }
}

if (!function_exists('attendance_count_effective_days')) {
    /**
     * Hitung jumlah hari kerja efektif dalam rentang tanggal.
     *
     * @param string $startDate  Y-m-d
     * @param string $endDate    Y-m-d
     * @param array  $hariLibur
     * @param array  $nationalHols
     * @param array  $jadwalKerja
     * @return int
     */
    function attendance_count_effective_days(
        string $startDate,
        string $endDate,
        array  $hariLibur    = ['Sun'],
        array  $nationalHols = [],
        array  $jadwalKerja  = []
    ): int {
        $count   = 0;
        $current = strtotime($startDate);
        $end     = strtotime($endDate);

        while ($current <= $end) {
            $dateStr = date('Y-m-d', $current);
            if (attendance_is_workday($dateStr, $hariLibur, $nationalHols, $jadwalKerja)) {
                $count++;
            }
            $current = strtotime('+1 day', $current);
        }
        return $count;
    }
}

if (!function_exists('attendance_rekap_time')) {
    /**
     * Ambil bagian jam dari kolom TIME/DATETIME ('' bila kosong/tidak valid).
     */
    function attendance_rekap_time($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }
        if (preg_match('/(\d{2}):(\d{2})(?::(\d{2}))?/', $value, $m) === 1) {
            return $m[1] . ':' . $m[2] . ':' . ($m[3] ?? '00');
        }
        return '';
    }
}

if (!function_exists('attendance_hari_tercatat')) {
    /**
     * Apakah presensi kelas ini benar-benar tercatat pada tanggal itu?
     *
     * `$peta` adalah [id_kelas => [Y-m-d => true]] yang dibangun dari baris
     * presensi satu bulan. Kelas yang tidak diketahui (siswa di luar roster
     * berjalan) jatuh ke penanda sekolah: bila ada satu baris pun hari itu,
     * berarti ada yang mencatat.
     */
    function attendance_hari_tercatat(array $peta, string $tgl, ?int $idKelas): bool
    {
        if ($idKelas !== null && $idKelas > 0) {
            return isset($peta[$idKelas][$tgl]);
        }

        foreach ($peta as $hari) {
            if (isset($hari[$tgl])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('attendance_rekap_day_cell')) {
    /**
     * Tentukan isi satu sel rekap presensi bulanan siswa.
     *
     * Modul penulis presensi memakai variasi status berbeda (HADIR,
     * HADIR TELAT, TERLAMBAT, ALPA, ALPHA, IJIN, ...) dan baris lama bisa
     * tanpa status. Fungsi ini menjadi satu sumber tafsir untuk tabel HTML,
     * Excel, dan PDF agar ketiganya tidak lagi berbeda pendapat.
     *
     * presensi hari itu memang dijalankan untuk kelas ini? Bila kelas tidak
     * punya SATU pun baris pada tanggal itu, yang terjadi adalah absensi yang
     * belum dicatat, bukan seluruh kelas membolos: selnya jadi 'no_data',
     * bukan alpha. Surat panggilan orang tua dan denda siswa memakai tafsir
     * yang sama supaya tidak ada yang menagih atau memanggil orang tua dari
     * hari yang datanya memang belum masuk.
     *
     * @return array{state:string, time_in:string, time_out:string}
     *   state: holiday|off|present|late|sakit|izin|bdr_scheduled|bdr_outside|alpha|no_data|future
     */
    function attendance_rekap_day_cell(
        ?object $rec,
        string $dateStr,
        bool $isWeeklyHoliday,
        bool $isHoliday,
        bool $isBdrScheduled,
        string $jamBatasMasuk,
        ?string $today = null,
        bool $adaDataHariIni = true
    ): array {
        $today ??= date('Y-m-d');

        if ($isHoliday) {
            return ['state' => 'holiday', 'time_in' => '', 'time_out' => ''];
        }
        if ($isWeeklyHoliday) {
            return ['state' => 'off', 'time_in' => '', 'time_out' => ''];
        }

        $timeIn  = attendance_rekap_time($rec->jam_masuk ?? null);
        $timeOut = attendance_rekap_time($rec->jam_keluar ?? null);
        $past    = $dateStr <= $today;

        if ($rec === null) {
            if ($isBdrScheduled) {
                return ['state' => 'bdr_scheduled', 'time_in' => '', 'time_out' => ''];
            }
            if (!$past) {
                return ['state' => 'future', 'time_in' => '', 'time_out' => ''];
            }
            if (!$adaDataHariIni) {
                return ['state' => 'no_data', 'time_in' => '', 'time_out' => ''];
            }

            return ['state' => 'alpha', 'time_in' => '', 'time_out' => ''];
        }

        $status = strtoupper(trim((string) ($rec->status ?? '')));
        $status = str_replace([' ', '-'], '_', $status);

        $hadir = ['HADIR', 'HADIR_TELAT', 'TERLAMBAT', 'TELAT', 'LATE', 'ONTIME'];
        $sakit = ['SAKIT'];
        $izin  = ['IZIN', 'IJIN', 'CUTI'];
        $alpha = ['ALPA', 'ALPHA'];
        $bdr   = ['BDR'];

        if (in_array($status, $alpha, true)) {
            // Jadwal BDR menang: siswa tidak masuk karena belajar dari rumah.
            return $isBdrScheduled
                ? ['state' => 'bdr_scheduled', 'time_in' => '', 'time_out' => '']
                : ['state' => 'alpha', 'time_in' => '', 'time_out' => ''];
        }

        if (in_array($status, $bdr, true)) {
            return [
                'state'    => $isBdrScheduled ? 'bdr_scheduled' : 'bdr_outside',
                'time_in'  => $timeIn,
                'time_out' => $timeOut,
            ];
        }

        if (in_array($status, $sakit, true)) {
            return ['state' => 'sakit', 'time_in' => '', 'time_out' => ''];
        }

        if (in_array($status, $izin, true)) {
            return ['state' => 'izin', 'time_in' => '', 'time_out' => ''];
        }

        $late = in_array($status, ['HADIR_TELAT', 'TERLAMBAT', 'TELAT', 'LATE'], true);
        // Perbandingan detik penuh, memakai ambang baku AttendancePolicy
        // (batas inklusif sampai detik ke-59). Sebelumnya pemotongan menit
        // (substr 0,5) membuat '07:30:59' terlihat tepat waktu di rekap mentre
        // layar lain menyebutnya telat.
        if ($timeIn !== '') {
            $tsIn    = \App\Services\AttendancePolicy::waktuKeTs($dateStr, $timeIn);
            $tsBatas = \App\Services\AttendancePolicy::batasTsDariLabel($dateStr, $jamBatasMasuk);
            if ($tsIn !== null && $tsBatas !== null && $tsIn > $tsBatas) {
                $late = true;
            }
        }

        // Status tak dikenal tetap dianggap hadir bila ada jam masuk.
        if (in_array($status, $hadir, true) || $timeIn !== '') {
            return [
                'state'    => $late ? 'late' : 'present',
                'time_in'  => $timeIn,
                'time_out' => $timeOut,
            ];
        }

        if ($isBdrScheduled) {
            return ['state' => 'bdr_scheduled', 'time_in' => '', 'time_out' => ''];
        }

        return ['state' => $past ? 'alpha' : 'future', 'time_in' => '', 'time_out' => ''];
    }
}

if (!function_exists('distance_meter')) {
    /**
     * Hitung jarak dalam meter antara dua titik koordinat (Haversine).
     */
    function distance_meter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

if (!function_exists('attendance_tahun_tertua')) {
    /**
     * Tahun kalender paling awal yang masih punya baris presensi.
     *
     * Batas bawah tetap (mis. 2020) akan mengunci data lama di belakang:
     * ketika tahun berjalan menjauh, data tahun pertama tidak lagi bisa
     * dibuka dari UI walau barisnya tetap utuh di database. Dropdown rekap
     * dan validasi controller memakai nilai ini agar arsip multi-tahun
     * tetap terjangkau. MIN(tgl) memakai indeks kolom tgl, bukan scan tabel.
     */
    function attendance_tahun_tertua(): int
    {
        static $tahun = null;
        if ($tahun !== null) {
            return $tahun;
        }

        $tahun = (int) date('Y');
        try {
            $db = \Config\Database::connect();
            $terlama = 0;
            foreach (['presensi_siswa', 'absensi_harian'] as $tabel) {
                if (!$db->tableExists($tabel)) {
                    continue;
                }
                $row = $db->table($tabel)->selectMin('tgl')->get()->getRowArray();
                $tgl = $row['tgl'] ?? null;
                if ($tgl) {
                    $y = (int) substr((string) $tgl, 0, 4);
                    if ($y > 0 && ($terlama === 0 || $y < $terlama)) {
                        $terlama = $y;
                    }
                }
            }
            if ($terlama > 0) {
                $tahun = $terlama;
            }
        } catch (\Throwable $e) {
            log_message('error', '[attendance_tahun_tertua] ' . $e->getMessage());
        }

        return $tahun;
    }
}
