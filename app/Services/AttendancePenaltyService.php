<?php

namespace App\Services;

/**
 * Service untuk kalkulasi denda dan poin presensi secara terpusat.
 *
 * Memastikan konsistensi perhitungan antara:
 * - Guru\Attendance (check-in/out mobile)
 * - Admin\PresensiAdmin (manual absen, approve koreksi, approve izin)
 * - CronController (mark alpha harian)
 *
 * Sistem Denda Progresif 3-Tier:
 *   Tier 1: menit 1–30   → tarif terendah
 *   Tier 2: menit 31–60  → tarif menengah
 *   Tier 3: menit 61+    → tarif tertinggi
 */
class AttendancePenaltyService
{
    // Batas menit antar tier
    private const TIER1_LIMIT = 30;
    private const TIER2_LIMIT = 60;

    /**
     * Hitung denda dan poin untuk keterlambatan atau pulang awal (progresif).
     *
     * @param int    $minutes  Jumlah menit keterlambatan / pulang awal
     * @param object $setting  Object setting dari tabel absensi_setting
     * @param string $type     Keterangan jenis ('Telat Masuk' | 'Pulang Awal')
     * @return array{points: int, denda: float, keterangan: string}
     */
    public function calculatePenalty(int $minutes, object $setting, string $type = 'Telat'): array
    {
        if ($minutes <= 0) {
            return ['points' => 0, 'denda' => 0.0, 'keterangan' => ''];
        }

        $d1 = (int) ($setting->denda_tier_1 ?? 100);
        $d2 = (int) ($setting->denda_tier_2 ?? 200);
        $d3 = (int) ($setting->denda_tier_3 ?? 500);

        $p1 = (int) ($setting->point_tier_1 ?? 1);
        $p2 = (int) ($setting->point_tier_2 ?? 2);
        $p3 = (int) ($setting->point_tier_3 ?? 5);

        $totalDenda  = 0.0;
        $totalPoints = 0;

        // Tier 1: 0 – TIER1_LIMIT menit
        $t1Mins = min($minutes, self::TIER1_LIMIT);
        $totalDenda  += $t1Mins * $d1;
        $totalPoints += $t1Mins * $p1;

        if ($minutes > self::TIER1_LIMIT) {
            // Tier 2: TIER1_LIMIT+1 – TIER2_LIMIT menit
            $remaining = $minutes - self::TIER1_LIMIT;
            $t2Range   = self::TIER2_LIMIT - self::TIER1_LIMIT;
            $t2Mins    = min($remaining, $t2Range);
            $totalDenda  += $t2Mins * $d2;
            $totalPoints += $t2Mins * $p2;

            if ($minutes > self::TIER2_LIMIT) {
                // Tier 3: TIER2_LIMIT+1 menit ke atas
                $t3Mins = $minutes - self::TIER2_LIMIT;
                $totalDenda  += $t3Mins * $d3;
                $totalPoints += $t3Mins * $p3;
            }
        }

        // Cap denda maksimal per kejadian jika ada setting max_denda_bulan
        // (cap per bulan dilakukan di rekap, bukan per kejadian)

        return [
            'points'     => $totalPoints,
            'denda'      => $totalDenda,
            'keterangan' => "{$type} {$minutes} menit (Progresif)",
        ];
    }

    /**
     * Hitung poin reward untuk datang awal atau pulang terlambat (progresif).
     *
     * @param int    $minutes  Jumlah menit datang lebih awal / pulang lebih akhir
     * @param object $setting  Object setting dari tabel absensi_setting
     * @return int Poin reward (positif)
     */
    public function calculateReward(int $minutes, object $setting): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        // Cap menit reward agar tidak tak terbatas
        $cap = (int) ($setting->reward_cap_menit ?? 30);
        if ($cap > 0 && $minutes > $cap) {
            $minutes = $cap;
        }

        $tier1Limit = 15;
        $tier2Limit = 60;

        $p1 = (int) ($setting->reward_point_tier_1 ?? 1);
        $p2 = (int) ($setting->reward_point_tier_2 ?? 1);
        $p3 = (int) ($setting->reward_point_tier_3 ?? 2);

        $totalPoints = 0;

        $t1Mins = min($minutes, $tier1Limit);
        $totalPoints += $t1Mins * $p1;

        if ($minutes > $tier1Limit) {
            $remaining = $minutes - $tier1Limit;
            $t2Range   = $tier2Limit - $tier1Limit;
            $t2Mins    = min($remaining, $t2Range);
            $totalPoints += $t2Mins * $p2;

            if ($minutes > $tier2Limit) {
                $t3Mins = $minutes - $tier2Limit;
                $totalPoints += $t3Mins * $p3;
            }
        }

        return $totalPoints;
    }

    /**
     * Hitung status masuk (ONTIME / LATE) dan menit keterlambatan.
     *
     * Mendelegasikan ke AttendancePolicy::telat supaya jalur mana pun yang
     * memakainya mendapat rumus yang sama (dulu fungsi ini menghitung menit
     * telat dari jam_masuk, bukan dari akhir grace, sehingga hasilnya `grace`
     * menit lebih besar dari jalur tulis yang sedang aktif).
     *
     * @param string $recordedTime  Waktu absen (Y-m-d H:i:s)
     * @param string $date          Tanggal (Y-m-d)
     * @param string $jamMasukBatas Jam batas masuk dari jadwal (H:i:s)
     * @param int    $graceMins     Toleransi dalam menit
     * @return array{status: string, telat_menit: int, datang_awal_menit: int}
     */
    public function calcStatusMasuk(
        string $recordedTime,
        string $date,
        string $jamMasukBatas,
        int $graceMins = AttendancePolicy::GRACE_DEFAULT
    ): array {
        $hit = AttendancePolicy::telat($date, $recordedTime, [
            'jam_masuk'   => $jamMasukBatas,
            'grace_menit' => $graceMins,
        ]);

        return [
            'status'            => $hit['status'],
            'telat_menit'       => $hit['telat_menit'],
            'datang_awal_menit' => $hit['datang_awal_menit'],
        ];
    }

    /**
     * Hitung status pulang (ONTIME / EARLY) dan menit pulang cepat.
     *
     * @param string $recordedTime    Waktu pulang (Y-m-d H:i:s)
     * @param string $date            Tanggal (Y-m-d)
     * @param string $jamPulangBatas  Jam batas pulang dari jadwal (H:i:s)
     * @return array{status: string, pulang_cepat_menit: int, pulang_akhir_menit: int}
     */
    public function calcStatusPulang(
        string $recordedTime,
        string $date,
        string $jamPulangBatas
    ): array {
        $threshold = strtotime($date . ' ' . $jamPulangBatas);
        $checkTime = strtotime($recordedTime);

        if ($checkTime < $threshold) {
            return [
                'status'              => 'EARLY',
                'pulang_cepat_menit'  => (int) ceil(($threshold - $checkTime) / 60),
                'pulang_akhir_menit'  => 0,
            ];
        }

        $diffMinutes = ($checkTime > $threshold) ? (int) floor(($checkTime - $threshold) / 60) : 0;
        $pulangAkhirMenit = ($diffMinutes >= 60) ? ($diffMinutes - 60) : 0;

        return [
            'status'              => 'ONTIME',
            'pulang_cepat_menit'  => 0,
            'pulang_akhir_menit'  => $pulangAkhirMenit,
        ];
    }

    /**
     * Hitung persentase kehadiran guru dalam satu bulan.
     * Mengabaikan hari libur mingguan dan hari libur nasional.
     *
     * @param int    $userId          ID user guru
     * @param int    $bulan           Bulan (1-12)
     * @param int    $tahun           Tahun
     * @return array{
     *   persen: float,
     *   total_hadir: int,
     *   total_hari_efektif: int,
     *   total_alpha: int,
     *   status: string
     * }
     */
    public function hitungPersentaseKehadiran(
        int $userId,
        int $bulan,
        int $tahun,
        float $minPersen = 90.0
    ): array {
        $db = \Config\Database::connect();

        $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endDate   = date('Y-m-t', strtotime($startDate));
        $today     = date('Y-m-d');

        // Jangan hitung hari yang belum datang
        if ($endDate > $today) {
            $endDate = $today;
        }

        // Ambil record kehadiran bulan ini (pisahkan kehadiran nyata, dispensasi izin, dan alpha)
        $records = $db->table('absensi_harian')
            ->select('tgl, status_masuk')
            ->where('user_id', $userId)
            ->where('tgl >=', $startDate)
            ->where('tgl <=', $endDate)
            ->get()->getResult();

        $presentDates = [];
        $leaveDates   = [];
        foreach ($records as $r) {
            $st = strtoupper(trim((string) ($r->status_masuk ?? '')));
            if (in_array($st, ['ALPHA', 'ALPA'], true)) {
                // Alpha tidak dihitung sebagai hadir
                continue;
            } elseif (in_array($st, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true)) {
                $leaveDates[$r->tgl] = true;
            } else {
                // ONTIME, LATE, DINAS*, WFH adalah kehadiran nyata
                $presentDates[$r->tgl] = true;
            }
        }

        // Cek juga pengajuan izin yang disetujui di absensi_izin
        if ($db->tableExists('absensi_izin')) {
            try {
                $approvedLeaves = $db->table('absensi_izin')
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $endDate)
                    ->where('tgl_selesai >=', $startDate)
                    ->get()->getResult();
                foreach ($approvedLeaves as $iz) {
                    $curIz = strtotime((string) $iz->tgl_mulai);
                    $endIz = strtotime((string) $iz->tgl_selesai);
                    while ($curIz <= $endIz) {
                        $d = date('Y-m-d', $curIz);
                        if ($d >= $startDate && $d <= $endDate) {
                            $leaveDates[$d] = true;
                        }
                        $curIz = strtotime('+1 day', $curIz);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $totalEfektif = 0;
        $totalHadir   = 0;

        $current = strtotime($startDate);
        $end     = strtotime($endDate);

        while ($current <= $end) {
            $dateStr = date('Y-m-d', $current);

            // Libur (mingguan ternormalisasi + kalender + jadwal sekolah) bukan
            // hari efektif. Izin/sakit/cuti resmi juga dispensasi sah, bukan
            // dihitung sebagai tidak hadir.
            $skip = \App\Services\AttendancePolicy::isLibur($dateStr)
                || isset($leaveDates[$dateStr]);

            if ($skip) {
                $current = strtotime('+1 day', $current);
                continue;
            }

            $totalEfektif++;
            if (isset($presentDates[$dateStr])) {
                $totalHadir++;
            }

            $current = strtotime('+1 day', $current);
        }

        $persen     = $totalEfektif > 0 ? round(($totalHadir / $totalEfektif) * 100, 2) : 100.0;
        $totalAlpha = max(0, $totalEfektif - $totalHadir);

        $status = 'aman';
        if ($persen < $minPersen) {
            $status = $persen < ($minPersen - 10) ? 'kritis' : 'perhatian';
        }

        return [
            'persen'              => $persen,
            'total_hadir'         => $totalHadir,
            'total_hari_efektif'  => $totalEfektif,
            'total_alpha'         => $totalAlpha,
            'status'              => $status,
        ];
    }

    /**
     * Tegakkan max_denda_bulan: batasi TOTAL denda satu baris absensi agar
     * akumulasi denda user dalam bulan berjalan tidak melebihi setting.
     *
     * @param int      $userId        ID user
     * @param string   $dateYmd       Tanggal baris (Y-m-d) → penentu bulan
     * @param float    $proposedTotal Total denda baris SETELAH perubahan ini
     * @param object   $setting       Setting absensi (memuat max_denda_bulan)
     * @param int|null $excludeId     ID baris ini (tidak dihitung ganda)
     * @param bool     $lock          true = baca dengan FOR UPDATE; WAJIB dipanggil
     *     di dalam transaksi aktif sebelum INSERT/UPDATE, agar dua request
     *     paralel tidak sama-sama lolos cap (race condition).
     * @return array{total: float, capped: bool}
     */
    public function applyMonthlyCap(int $userId, string $dateYmd, float $proposedTotal, object $setting, ?int $excludeId = null, bool $lock = false): array
    {
        $max = (float) ($setting->max_denda_bulan ?? 0);
        if ($max <= 0 || $userId <= 0 || $proposedTotal <= 0) {
            return ['total' => $proposedTotal, 'capped' => false];
        }

        $monthStart = substr($dateYmd, 0, 7) . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $db = \Config\Database::connect();
        if ($lock) {
            $sql = 'SELECT COALESCE(SUM(denda), 0) AS used FROM absensi_harian WHERE user_id = ? AND tgl >= ? AND tgl <= ?';
            $params = [$userId, $monthStart, $monthEnd];
            if ($excludeId) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            $sql .= ' FOR UPDATE';
            $used = (float) ($db->query($sql, $params)->getRow()->used ?? 0);
        } else {
            $builder = $db->table('absensi_harian')
                ->selectSum('denda')
                ->where('user_id', $userId)
                ->where('tgl >=', $monthStart)
                ->where('tgl <=', $monthEnd);
            if ($excludeId) {
                $builder->where('id !=', $excludeId);
            }
            $used = (float) ($builder->get()->getRow()->denda ?? 0);
        }

        $allowed = $max - $used;
        if ($allowed < 0) {
            $allowed = 0;
        }
        if ($proposedTotal > $allowed) {
            return ['total' => $allowed, 'capped' => true];
        }

        return ['total' => $proposedTotal, 'capped' => false];
    }

    /**
     * Batasi estimasi alpha yang belum tercatat oleh max_denda_bulan.
     *
     * Estimasi diproses kronologis per bulan kalender. Sisa cap dihitung dari
     * denda tercatat di absensi_harian ditambah estimasi sebelumnya pada bulan
     * yang sama. Estimasi yang sepenuhnya habis oleh cap dibuang agar tidak
     * dihitung sebagai tunggakan/poin; estimasi terakhir yang terpotong
     * sebagian dipertahankan dengan nominal yang disesuaikan.
     *
     * @param int         $userId ID user guru
     * @param array       $items  Estimasi alpha [['tgl' => Y-m-d, 'denda' => float, ...]]
     * @param object|null $setting Setting absensi (null = ambil dari DB)
     * @param array|null  $recordedMonthTotals Total denda tercatat per 'YYYY-MM'
     *     (null = ambil dari DB; array kosong = tidak ada denda tercatat)
     * @return array{items: array, total: float, count: int}
     */
    public function capMissingAlphaEstimates(int $userId, array $items, ?object $setting = null, ?array $recordedMonthTotals = null): array
    {
        if ($userId <= 0 || $items === []) {
            return ['items' => [], 'total' => 0.0, 'count' => 0];
        }

        $db = \Config\Database::connect();
        if (!$setting) {
            try {
                $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
            } catch (\Throwable $e) {
                $setting = null;
            }
            if (!$setting) {
                $setting = (object) [];
            }
        }

        usort($items, static fn($a, $b) => strcmp((string) ($a['tgl'] ?? ''), (string) ($b['tgl'] ?? '')));

        $max = (float) ($setting->max_denda_bulan ?? 0);
        if ($max <= 0) {
            $total = 0.0;
            foreach ($items as $it) {
                $total += (float) ($it['denda'] ?? 0);
            }

            return ['items' => array_values($items), 'total' => $total, 'count' => count($items)];
        }

        $firstMonth = substr((string) ($items[0]['tgl'] ?? ''), 0, 7);
        $lastItem = $items[count($items) - 1];
        $lastMonth = substr((string) ($lastItem['tgl'] ?? ''), 0, 7);
        if (!preg_match('/^\d{4}-\d{2}$/', $firstMonth) || !preg_match('/^\d{4}-\d{2}$/', $lastMonth)) {
            return ['items' => [], 'total' => 0.0, 'count' => 0];
        }

        $recorded = $recordedMonthTotals ?? [];
        if ($recordedMonthTotals === null) {
            try {
                $rows = $db->table('absensi_harian')
                    ->select('tgl, denda')
                    ->where('user_id', $userId)
                    ->where('tgl >=', $firstMonth . '-01')
                    ->where('tgl <=', date('Y-m-t', strtotime($lastMonth . '-01')))
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $month = substr((string) ($r->tgl ?? ''), 0, 7);
                    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                        continue;
                    }
                    $recorded[$month] = ($recorded[$month] ?? 0.0) + (float) ($r->denda ?? 0);
                }
            } catch (\Throwable $e) {}
        }

        $estimated = [];
        $cappedItems = [];
        $total = 0.0;
        foreach ($items as $it) {
            $date = substr((string) ($it['tgl'] ?? ''), 0, 10);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $month = substr($date, 0, 7);
            $original = (float) ($it['denda'] ?? 0);
            if ($original <= 0) {
                continue;
            }

            $remaining = $max - (($recorded[$month] ?? 0.0) + ($estimated[$month] ?? 0.0));
            if ($remaining <= 0) {
                continue;
            }
            if ($original > $remaining) {
                $it['denda'] = $remaining;
                $it['keterangan'] = trim((string) ($it['keterangan'] ?? '') . ' (dibatasi cap bulanan Rp ' . number_format($remaining, 0, ',', '.') . ')');
            }

            $estimated[$month] = ($estimated[$month] ?? 0.0) + (float) $it['denda'];
            $cappedItems[] = $it;
            $total += (float) $it['denda'];
        }

        return ['items' => $cappedItems, 'total' => $total, 'count' => count($cappedItems)];
    }

    /**
     * Inti ledger sisa denda — murni, tanpa query.
     *
     * Satu-satunya definisi "sisa": per bulan (denda harian tercatat +
     * estimasi alpha bolong, keduanya sudah dibatasi cap saat ditulis/
     * diestimasi) dikurangi pembayaran non-batal bulan itu; lebih bayar
     * menjadi kredit yang digeser FIFO ke bulan tertunggak tertua, lintas
     * tahun. Dipakai rekap mingguan, tabel admin, dan bayar nominal agar
     * angkanya identik di semua tempat.
     *
     * @param array<string,float> $dendaHarianPerBulan 'YYYY-MM' => total
     * @param array<string,float> $alphaPerBulan       'YYYY-MM' => total estimasi
     * @param array<string,float> $bayarPerBulan       'YYYY-MM' => total non-batal
     * @return array{bulan:array<int,array{bulan:int,tahun:int,total_denda:float,sudah_dibayar:float,sisa:float}>,total_sisa:float,kredit_sisa:float}
     */
    public static function hitungSisaLedger(array $dendaHarianPerBulan, array $alphaPerBulan, array $bayarPerBulan): array
    {
        $normal = static function (array $masuk): array {
            $keluar = [];
            foreach ($masuk as $kunci => $nilai) {
                if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', (string) $kunci, $m)) {
                    continue;
                }
                $keluar[$m[1] . '-' . $m[2]] = (float) $nilai;
            }
            return $keluar;
        };
        $harian = $normal($dendaHarianPerBulan);
        $alpha  = $normal($alphaPerBulan);
        $bayar  = $normal($bayarPerBulan);

        $semuaBulan = array_unique(array_merge(array_keys($harian), array_keys($alpha), array_keys($bayar)));
        sort($semuaBulan);

        $bulan = [];
        $kredit = 0.0;
        foreach ($semuaBulan as $kunci) {
            [$thn, $bln] = explode('-', $kunci);
            $total = ($harian[$kunci] ?? 0.0) + ($alpha[$kunci] ?? 0.0);
            $sudah = $bayar[$kunci] ?? 0.0;
            if ($total <= 0) {
                $kredit += $sudah;
                continue;
            }
            if ($sudah > $total) {
                $kredit += $sudah - $total;
                $sudah = $total;
            }
            $sisa = max(0.0, $total - $sudah);
            if ($sisa > 0) {
                $bulan[] = [
                    'bulan'         => (int) $bln,
                    'tahun'         => (int) $thn,
                    'total_denda'   => $total,
                    'sudah_dibayar' => $sudah,
                    'sisa'          => $sisa,
                ];
            } else {
                $kredit += max(0.0, $sudah - $total);
            }
        }

        // Kredit FIFO ke tunggakan tertua.
        if ($kredit > 0) {
            foreach ($bulan as &$b) {
                if ($b['sisa'] <= 0 || $kredit <= 0) {
                    continue;
                }
                $potong = min($b['sisa'], $kredit);
                $b['sudah_dibayar'] += $potong;
                $b['sisa'] -= $potong;
                $kredit -= $potong;
            }
            unset($b);
            $bulan = array_values(array_filter($bulan, static fn($b) => $b['sisa'] > 0));
        }

        $totalSisa = 0.0;
        foreach ($bulan as $b) {
            $totalSisa += $b['sisa'];
        }

        return ['bulan' => $bulan, 'total_sisa' => $totalSisa, 'kredit_sisa' => max(0.0, $kredit)];
    }

    /**
     * Golongkan satu baris absensi_pembayaran_denda ke kanal yang dipakai
     * laporan: 'POIN', 'SALDO', atau 'TUNAI'.
     *
     * Kolom metode_bayar ditulis oleh jalur pembayaran, jadi dia yang
     * menentukan. Kata kunci di keterangan hanya dipakai untuk baris lama
     * yang metodenya kosong — kalau diutamakan, transfer tunai dengan
     * keterangan "potongan reward" tercatat di kolom Poin dan angka Tunai
     * di tabel denda/rekap PDF kurang.
     */
    public static function kelasPembayaranDenda(?string $metode, ?string $keterangan = ''): string
    {
        $metode = strtoupper(trim((string) $metode));
        if ($metode !== '') {
            if ($metode === 'POIN' || $metode === 'VOUCHER') {
                return 'POIN';
            }
            if ($metode === 'SALDO') {
                return 'SALDO';
            }
            return 'TUNAI';
        }
        $ket = strtolower(trim((string) $keterangan));
        if (str_contains($ket, 'voucher') || str_contains($ket, 'poin') || str_contains($ket, 'reward')) {
            return 'POIN';
        }
        if (str_contains($ket, 'saldo')) {
            return 'SALDO';
        }
        return 'TUNAI';
    }

    /**
     * Tahun-tahun yang punya jejak denda/pembayaran user + tahun berjalan.
     * Dipakai saat sisa diminta lintas tahun (null) agar tunggakan lama
     * ikut terhitung dan bisa dibayar sekaligus.
     *
     * @return int[]
     */
    private function tahunLedger(int $userId): array
    {
        $db = \Config\Database::connect();
        $tahun = [(int) date('Y')];
        try {
            $rows = $db->table('absensi_harian')
                ->select('tgl')->where('user_id', $userId)->where('denda >', 0)
                ->get()->getResult();
            foreach ($rows as $r) {
                if (preg_match('/^(\d{4})-\d{2}-\d{2}$/', (string) ($r->tgl ?? ''), $m)) {
                    $tahun[] = (int) $m[1];
                }
            }
        } catch (\Throwable $e) {}
        try {
            if ($db->tableExists('absensi_pembayaran_denda')) {
                $rows = $db->table('absensi_pembayaran_denda')
                    ->select('tahun')->where('user_id', $userId)->where('status !=', 'batal')
                    ->get()->getResult();
                foreach ($rows as $r) {
                    if ((int) ($r->tahun ?? 0) >= 2000) {
                        $tahun[] = (int) $r->tahun;
                    }
                }
            }
        } catch (\Throwable $e) {}
        $tahun = array_values(array_unique($tahun));
        sort($tahun);

        return $tahun;
    }

    /**
     * Sisa denda kanonik per bulan untuk satu guru.
     *
     * $tahun null = lintas tahun (semua jejak + tahun berjalan).
     * Bentuk kembalian identik dengan Admin\PresensiAdmin::getSisaDendaPerBulan
     * lama agar pemanggil tidak berubah.
     *
     * @return array<int,array{bulan:int,tahun:int,total_denda:float,sudah_dibayar:float,sisa:float}>
     */
    public function sisaDendaPerBulan(int $userId, $tahun = null): array
    {
        $db = \Config\Database::connect();
        $tahuns = $tahun ? [(int) $tahun] : $this->tahunLedger($userId);

        $harian = [];
        $alpha = [];
        $bayar = [];

        foreach ($tahuns as $thn) {
            try {
                $rows = $db->table('absensi_harian')
                    ->select('tgl, denda')
                    ->where('user_id', $userId)
                    ->where('denda >', 0)
                    ->where('tgl >=', sprintf('%04d-01-01', $thn))
                    ->where('tgl <=', sprintf('%04d-12-31', $thn))
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $kunci = substr((string) ($r->tgl ?? ''), 0, 7);
                    if (!preg_match('/^\d{4}-\d{2}$/', $kunci)) {
                        continue;
                    }
                    $harian[$kunci] = ($harian[$kunci] ?? 0.0) + (float) ($r->denda ?? 0);
                }
            } catch (\Throwable $e) {}

            try {
                $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
            } catch (\Throwable $e) {
                $setting = null;
            }
            if (!$setting) {
                $setting = (object) [];
            }
            try {
                $miss = $this->missingAlphaFines(
                    $userId,
                    sprintf('%04d-01-01', $thn),
                    min(date('Y-m-d'), sprintf('%04d-12-31', $thn)),
                    $setting,
                    false
                );
                foreach (($miss['items'] ?? []) as $mi) {
                    $kunci = substr((string) ($mi['tgl'] ?? ''), 0, 7);
                    if (!preg_match('/^\d{4}-\d{2}$/', $kunci)) {
                        continue;
                    }
                    $alpha[$kunci] = ($alpha[$kunci] ?? 0.0) + (float) ($mi['denda'] ?? 0);
                }
            } catch (\Throwable $e) {}

            try {
                if ($db->tableExists('absensi_pembayaran_denda')) {
                    $rows = $db->table('absensi_pembayaran_denda')
                        ->select('bulan, tahun, nominal_bayar, total_denda')
                        ->where('user_id', $userId)
                        ->where('status !=', 'batal')
                        ->where('tahun', $thn)
                        ->get()->getResult();
                    foreach ($rows as $r) {
                        $kunci = sprintf('%04d-%02d', (int) ($r->tahun ?? $thn), (int) ($r->bulan ?? 0));
                        if (!preg_match('/^\d{4}-\d{2}$/', $kunci) || (int) substr($kunci, 5, 2) < 1) {
                            continue;
                        }
                        $nominal = (float) ($r->nominal_bayar ?? 0);
                        if ($nominal <= 0) {
                            $nominal = (float) ($r->total_denda ?? 0);
                        }
                        $bayar[$kunci] = ($bayar[$kunci] ?? 0.0) + $nominal;
                    }
                }
            } catch (\Throwable $e) {}
        }

        return self::hitungSisaLedger($harian, $alpha, $bayar)['bulan'];
    }

    /**
     * Total sisa kanonik lintas tahun untuk banyak guru sekaligus.
     * Satu estimator batch + query agregat (bukan N× pindai penuh).
     *
     * @param int[] $userIds
     * @return array<int,float> user_id => total sisa
     */
    public function sisaDendaBatch(array $userIds): array
    {
        $db = \Config\Database::connect();
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_values(array_filter($userIds, static fn($id) => $id > 0));
        $hasil = array_fill_keys($userIds, 0.0);
        if ($userIds === []) {
            return $hasil;
        }

        $harian = [];
        $bayar = [];
        $awalGlobal = date('Y-m-d');
        try {
            $rows = $db->table('absensi_harian')
                ->select('user_id, tgl, denda')
                ->whereIn('user_id', $userIds)
                ->where('denda >', 0)
                ->get()->getResult();
            foreach ($rows as $r) {
                $uid = (int) ($r->user_id ?? 0);
                $kunci = substr((string) ($r->tgl ?? ''), 0, 7);
                if ($uid <= 0 || !preg_match('/^\d{4}-\d{2}$/', $kunci)) {
                    continue;
                }
                $harian[$uid][$kunci] = ($harian[$uid][$kunci] ?? 0.0) + (float) ($r->denda ?? 0);
                if (substr((string) ($r->tgl ?? ''), 0, 10) < $awalGlobal) {
                    $awalGlobal = substr((string) $r->tgl, 0, 10);
                }
            }
        } catch (\Throwable $e) {}

        try {
            if ($db->tableExists('absensi_pembayaran_denda')) {
                $rows = $db->table('absensi_pembayaran_denda')
                    ->select('user_id, bulan, tahun, nominal_bayar, total_denda')
                    ->whereIn('user_id', $userIds)
                    ->where('status !=', 'batal')
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $uid = (int) ($r->user_id ?? 0);
                    $kunci = sprintf('%04d-%02d', (int) ($r->tahun ?? 0), (int) ($r->bulan ?? 0));
                    if ($uid <= 0 || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $kunci)) {
                        continue;
                    }
                    $nominal = (float) ($r->nominal_bayar ?? 0);
                    if ($nominal <= 0) {
                        $nominal = (float) ($r->total_denda ?? 0);
                    }
                    $bayar[$uid][$kunci] = ($bayar[$uid][$kunci] ?? 0.0) + $nominal;
                }
            }
        } catch (\Throwable $e) {}

        $alpha = [];
        try {
            $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        } catch (\Throwable $e) {
            $setting = null;
        }
        if (!$setting) {
            $setting = (object) [];
        }
        try {
            $semua = $this->missingAlphaFinesBatch($userIds, $awalGlobal, date('Y-m-d'), $setting, false);
            foreach ($semua as $uid => $ringkas) {
                foreach (($ringkas['items'] ?? []) as $mi) {
                    $kunci = substr((string) ($mi['tgl'] ?? ''), 0, 7);
                    if (!preg_match('/^\d{4}-\d{2}$/', $kunci)) {
                        continue;
                    }
                    $alpha[$uid][$kunci] = ($alpha[$uid][$kunci] ?? 0.0) + (float) ($mi['denda'] ?? 0);
                }
            }
        } catch (\Throwable $e) {}

        foreach ($userIds as $uid) {
            $hasil[$uid] = self::hitungSisaLedger(
                $harian[$uid] ?? [],
                $alpha[$uid] ?? [],
                $bayar[$uid] ?? []
            )['total_sisa'];
        }

        return $hasil;
    }

    /**
     * Tentukan poin yang diberikan berdasarkan status izin/sakit/cuti.
     */
    public function getStatusPoints(string $status, object $setting): int
    {
        return match (strtoupper($status)) {
            'IJIN', 'IZIN' => (int) ($setting->point_izin ?? 0),
            'SAKIT'         => (int) ($setting->point_sakit ?? 0),
            'CUTI'          => (int) ($setting->point_cuti ?? 0),
            'DINAS', 'DINAS_APPROVED' => (int) ($setting->point_hadir ?? $setting->point_hadir_ontime ?? 10),
            default         => 0,
        };
    }

    /**
     * Normalkan nilai status izin ke format uppercase konsisten.
     * 'sakit' → 'SAKIT', 'ijin' → 'IJIN', 'cuti' → 'CUTI'
     */
    public function normalizeIzinStatus(string $jenis): string
    {
        return match (strtolower(trim($jenis))) {
            'sakit'  => 'SAKIT',
            'cuti'   => 'CUTI',
            'dinas'  => 'DINAS_APPROVED',
            default  => 'IJIN',
        };
    }

    /**
     * Hitung denda alpa FLAT berdasarkan nilai dasar (denda_alpha_per_hari).
     * Fungsi progresif bertingkat sudah dihapus — semua hari alpa
     * dikenakan tarif flat yang sama tanpa kelipatan.
     *
     * @param int    $consecutiveDay Dipertahankan untuk kompatibilitas (diabaikan, selalu flat).
     * @param object $setting        Setting presensi (absensi_setting)
     * @return array{denda: float, multiplier: float, consecutive_day: int, keterangan: string}
     */
    public function calculateConsecutiveAlphaFine(int $consecutiveDay, object $setting): array
    {
        $base = (float) ($setting->denda_alpha_per_hari ?? 5000);

        if ($consecutiveDay <= 0) {
            $consecutiveDay = 1;
        }

        $multiplier = 1.0;
        $fine = $base * $multiplier;
        $ket = 'Alpa (Rp ' . number_format($base, 0, ',', '.') . ')';

        return [
            'denda'           => $fine,
            'multiplier'      => $multiplier,
            'consecutive_day' => $consecutiveDay,
            'keterangan'      => $ket,
        ];
    }

    /**
     * Hitung streak hari alpa berturut-turut bagi seorang guru hingga $targetDate.
     * Mengabaikan hari libur mingguan dan libur nasional resmi (tidak memutuskan streak).
     * Kehadiran atau izin sah akan memutuskan streak.
     *
     * @param int    $userId     ID user guru
     * @param string $targetDate Tanggal evaluasi (Y-m-d)
     * @return int Jumlah hari alpa berturut-turut (minimal 1 jika hari ini alpa, 0 jika hari ini hadir/izin)
     */
    public function getConsecutiveAlphaStreak(int $userId, string $targetDate): int
    {
        $db = \Config\Database::connect();

        // Cek apakah tanggal target adalah hari kerja dan apakah user alpa pada tanggal target
        $targetRecord = $db->table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl', $targetDate)
            ->get()->getRow();

        if ($targetRecord) {
            $st = strtoupper(trim((string) ($targetRecord->status_masuk ?? '')));
            if (!in_array($st, ['ALPHA', 'ALPA'], true) && !empty($targetRecord->jam_masuk)) {
                return 0; // Guru hadir atau izin pada targetDate
            }
        }

        // Cek izin resmi yang disetujui pada targetDate
        if ($db->tableExists('absensi_izin')) {
            $hasLeave = $db->table('absensi_izin')
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->where('tgl_mulai <=', $targetDate)
                ->where('tgl_selesai >=', $targetDate)
                ->countAllResults();
            if ($hasLeave > 0) {
                return 0;
            }
        }

        // Libur (mingguan ternormalisasi + jadwal sekolah + kalender nasional)
        // dibaca dari AttendancePolicy supaya seragam dengan modul lain.
        $streak = 1;
        $checkTime = strtotime($targetDate);

        // Telusuri hari-hari kerja sebelumnya ke belakang (maksimal 60 hari kerja)
        for ($i = 1; $i <= 60; $i++) {
            $checkTime = strtotime('-1 day', $checkTime);
            $dateStr = date('Y-m-d', $checkTime);

            // Skip hari libur (tidak memutuskan streak)
            if (\App\Services\AttendancePolicy::isLibur($dateStr)) {
                continue;
            }

            // Ini adalah hari kerja operasional. Cek status absensi guru:
            $prevRecord = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl', $dateStr)
                ->get()->getRow();

            if ($prevRecord) {
                $pst = strtoupper(trim((string) ($prevRecord->status_masuk ?? '')));
                if (!in_array($pst, ['ALPHA', 'ALPA'], true) && (!empty($prevRecord->jam_masuk) || in_array($pst, ['IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS_APPROVED', 'WFH', 'ONTIME', 'LATE'], true))) {
                    // Hadir atau izin sah: streak terputus!
                    break;
                }
            }

            // Cek izin di tabel absensi_izin
            if ($db->tableExists('absensi_izin')) {
                $hasLeave = $db->table('absensi_izin')
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $dateStr)
                    ->where('tgl_selesai >=', $dateStr)
                    ->countAllResults();
                if ($hasLeave > 0) {
                    // Izin sah: streak terputus!
                    break;
                }
            }

            // Guru tidak hadir (Alpa) pada hari kerja ini
            $streak++;
        }

        return $streak;
    }

    /**
     * KANONIK — estimasi denda alpha untuk tanggal yang BELUM tercatat.
     *
     * Satu-satunya acuan yang boleh dipakai SEMUA tampilan (frontend guru
     * maupun backend admin) agar nominal tidak pernah selisih. Aturannya:
     *  1. Hanya tanggal kerja TANPA baris tercatat yang diestimasi (baris
     *     berdenda denda > 0 ATAU baris ALPHA denda 0 karena cap dianggap tercatat).
     *     Tanggal tercatat selalu memakai nominal tersimpan di absensi_harian
     *     dan tidak pernah dihitung ulang di sini.
     *  2. Hari ini (periode belum selesai) tidak diestimasi untuk tampilan
     *     ($includeToday = false). Proses tulis (cron/recalculate) memanggil
     *     calculateAllUsersPeriodAlphaSummary langsung bila butuh hari ini.
     *  3. Tarif FLAT per hari kerja (denda_alpha_per_hari), tanpa streak/progresif.
     *     Estimasi yang dikembalikan sudah dibatasi max_denda_bulan per bulan
     *     kalender (termasuk denda tercatat pada bulan yang sama).
     *
     * @param int         $userId       ID user guru
     * @param string      $startDate    Awal periode (Y-m-d)
     * @param string      $endDate      Akhir periode (Y-m-d)
     * @param object|null $setting      Setting absensi (null = ambil dari DB)
     * @param bool        $includeToday true = sertakan hari ini
     * @return array{items: array, total: float, count: int}
     */
    public function missingAlphaFines(int $userId, string $startDate, string $endDate, ?object $setting = null, bool $includeToday = false): array
    {
        $batch = $this->missingAlphaFinesBatch([$userId], $startDate, $endDate, $setting, $includeToday);
        return $batch[$userId] ?? ['items' => [], 'total' => 0.0, 'count' => 0];
    }

    /**
     * Versi batch dari missingAlphaFines(): SATU kali summary + query tercatat
     * untuk banyak user sekaligus. Wajib dipakai untuk rekap (YTD/bulanan)
     * agar tidak terjadi N kali kalkulasi seluruh user (beban kuadratik).
     *
     * Aturan per user identik dengan missingAlphaFines().
     *
     * @param int[]       $userIds
     * @return array<int, array{items: array, total: float, count: int}>
     */
    public function missingAlphaFinesBatch(array $userIds, string $startDate, string $endDate, ?object $setting = null, bool $includeToday = false): array
    {
        $db = \Config\Database::connect();
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_values(array_filter($userIds, static fn($id) => $id > 0));
        if ($userIds === []) {
            return [];
        }
        if (!$setting) {
            try {
                $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
            } catch (\Throwable $e) {
                $setting = null;
            }
            if (!$setting) {
                $setting = (object) [];
            }
        }

        $startDate = substr(trim($startDate), 0, 10);
        $endDate = substr(trim($endDate), 0, 10);
        if ($endDate > date('Y-m-d')) {
            $endDate = date('Y-m-d');
        }
        if ($startDate > $endDate) {
            return [];
        }

        // Sekali query tercatat untuk semua user (anti double-count + total cap).
        $recordedDates = [];
        $recordedSums = [];
        try {
            $rows = $db->table('absensi_harian')
                ->select('user_id, tgl, status_masuk, denda')
                ->whereIn('user_id', $userIds)
                ->where('tgl >=', $startDate)
                ->where('tgl <=', $endDate)
                ->get()->getResult();
            foreach ($rows as $r) {
                $uid = (int) ($r->user_id ?? 0);
                if ($uid <= 0 || empty($r->tgl)) {
                    continue;
                }
                $st = strtoupper(trim((string) ($r->status_masuk ?? '')));
                if ((float) ($r->denda ?? 0) > 0 || in_array($st, ['ALPHA', 'ALPA'], true)) {
                    $recordedDates[$uid][substr($r->tgl, 0, 10)] = true;
                }
                $month = substr((string) $r->tgl, 0, 7);
                if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                    $recordedSums[$uid][$month] = ($recordedSums[$uid][$month] ?? 0.0) + (float) ($r->denda ?? 0);
                }
            }
        } catch (\Throwable $e) {}

        // Sekali summary untuk semua user (pindai mundur agar streak kontinu).
        $scanStart = date('Y-m-d', strtotime($startDate . ' -62 days'));
        try {
            $summary = $this->calculateAllUsersPeriodAlphaSummary($scanStart, $endDate, $setting);
        } catch (\Throwable $e) {
            return [];
        }

        $today = date('Y-m-d');
        $result = [];
        foreach ($userIds as $uid) {
            $items = $summary[$uid]['items'] ?? [];
            $out = [];
            foreach ($items as $it) {
                $t = substr($it['tgl'] ?? '', 0, 10);
                if ($t === '' || $t < $startDate || $t > $endDate) {
                    continue;
                }
                if (!$includeToday && $t >= $today) {
                    continue;
                }
                if (isset($recordedDates[$uid][$t])) {
                    continue;
                }
                $out[] = $it;
            }
            $result[$uid] = $this->capMissingAlphaEstimates($uid, $out, $setting, $recordedSums[$uid] ?? []);
        }

        return $result;
    }

    /**
     * Hitung rekap akumulasi denda alpa FLAT untuk seluruh user dalam periode tertentu.
     * Mengembalikan map [$userId => ['count' => int, 'total_denda' => float, 'items' => array]]
     *
     * @param string      $startDate Tanggal mulai (Y-m-d)
     * @param string      $endDate   Tanggal selesai (Y-m-d)
     * @param object|null $setting   Setting absensi
     * @return array<int, array{count: int, total_denda: float, items: array}>
     */
    public function calculateAllUsersPeriodAlphaSummary(string $startDate, string $endDate, ?object $setting = null): array
    {
        $db = \Config\Database::connect();
        if (!$setting) {
            $setting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        }

        $today = date('Y-m-d');
        if ($endDate > $today) {
            $endDate = $today;
        }

        // Ambil semua guru aktif
        $users = $db->query("SELECT u.id FROM users u JOIN users_groups ug ON ug.user_id = u.id WHERE ug.group_id = 2 AND u.active = 1")->getResult();

        // Ambil seluruh record absensi pada periode
        $attRows = $db->table('absensi_harian')
            ->where('tgl >=', $startDate)
            ->where('tgl <=', $endDate)
            ->get()->getResult();

        $attMap = [];
        foreach ($attRows as $a) {
            $attMap[$a->user_id][$a->tgl] = $a;
        }

        // Ambil seluruh izin disetujui pada periode
        $leaveMap = [];
        if ($db->tableExists('absensi_izin')) {
            $leaveRows = $db->table('absensi_izin')
                ->where('status', 'approved')
                ->where('tgl_mulai <=', $endDate)
                ->where('tgl_selesai >=', $startDate)
                ->get()->getResult();
            foreach ($leaveRows as $l) {
                $cur = strtotime(max($startDate, (string) $l->tgl_mulai));
                $end = strtotime(min($endDate, (string) $l->tgl_selesai));
                while ($cur <= $end) {
                    $leaveMap[$l->user_id][date('Y-m-d', $cur)] = true;
                    $cur = strtotime('+1 day', $cur);
                }
            }
        }

        $result = [];
        $startTs = strtotime($startDate);
        $todayTs = strtotime(date('Y-m-d'));

        // Berhenti SEBELUM hari ini, bukan sampai hari ini.
        //
        // Dulu `min($endDate, $todayTs)` memasukkan hari berjalan, sehingga
        // setiap guru yang belum absen pagi itu langsung dihitung ALPHA —
        // padahal sekolah belum selesai (jam_pulang 14:00) dan cron
        // markAbsentTeachers baru menandai alpha setelah jam masuk + 2 jam.
        // Akibatnya halaman Denda menampilkan denda Rp 5.000 per guru untuk
        // hari yang belum selesai; pada 19 guru itu Rp 95.000 angka palsu
        // yang bisa langsung ditagihkan. Rekap sudah benar (pakai
        // tanggal < hari ini), jadi di sini perlu disamakan.
        $endTs = min(strtotime($endDate), $todayTs) - 86400;

        $baseFlat = (float) ($setting->denda_alpha_per_hari ?? 5000);
        $ketFlat = 'Alpa (Rp ' . number_format($baseFlat, 0, ',', '.') . ')';

        foreach ($users as $u) {
            $uid = (int) $u->id;
            $alphaCount = 0;
            $totalDenda = 0.0;
            $items = [];
            $alphaDates = [];

            $curTs = $startTs;
            while ($curTs <= $endTs) {
                $dateStr   = date('Y-m-d', $curTs);

                $curTs = strtotime('+1 day', $curTs);

                // Skip libur (mingguan ternormalisasi + kalender + jadwal sekolah).
                if (\App\Services\AttendancePolicy::isLibur($dateStr)) {
                    continue;
                }

                // Cek apakah ada izin resmi
                if (isset($leaveMap[$uid][$dateStr])) {
                    continue;
                }

                // Cek presensi
                $rec = $attMap[$uid][$dateStr] ?? null;
                $isAlpha = false;

                if ($rec) {
                    $st = strtoupper(trim((string) ($rec->status_masuk ?? '')));
                    if (in_array($st, ['ALPHA', 'ALPA'], true)) {
                        $isAlpha = true;
                    } elseif ($st === 'PENDING_VERIFICATION') {
                        // Satu aturan dengan teacherMonthlyAlphaDates() di bawah:
                        // pending baru menjadi alpha setelah jendela tinjau lewat.
                        // Sebelumnya baris pending yang punya jam_masuk selalu lolos
                        // dari hitungan ini, sehingga dua penghitung alpha memberi
                        // jumlah hari yang berbeda untuk guru yang sama.
                        if (!\App\Services\AttendancePolicy::pendingLewatBatas($dateStr)) {
                            continue;
                        }
                        $isAlpha = true;
                    } elseif (!empty($rec->jam_masuk) || in_array($st, ['ONTIME', 'LATE', 'WFH', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS', 'DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_OUT'], true)) {
                        continue;
                    } else {
                        $isAlpha = true;
                    }
                } else {
                    // Tidak ada record sama sekali di hari kerja
                    $isAlpha = true;
                }

                if ($isAlpha) {
                    $alphaCount++;
                    $alphaDates[] = $dateStr;
                    $totalDenda += $baseFlat;
                    $items[] = [
                        'tgl'             => $dateStr,
                        'consecutive_day' => 1,
                        'multiplier'      => 1.0,
                        'denda'           => $baseFlat,
                        'keterangan'      => $ketFlat,
                    ];
                }
            }

            $result[$uid] = [
                'count'       => $alphaCount,
                'total_denda' => $totalDenda,
                'items'       => $items
            ];
        }

        return $result;
    }

    /**
     * Hitung akumulasi hari alpha (tidak hadir tanpa keterangan) seorang guru pada periode bulan/tahun tertentu.
     * Standar tunggal (single source of truth) untuk penerbitan dan verifikasi Surat Teguran.
     *
     * Aturan presisi:
     * 1. Menghormati hari libur mingguan, hari libur nasional (absensi_hari_libur), dan jadwal libur kerja (absensi_jadwal_kerja).
     * 2. Menghormati izin/sakit/cuti resmi (dari absensi_izin status 'approved' maupun absensi_harian).
     * 3. Menghormati jam kerja berjalan: HARI INI ($today) TIDAK BOLEH dihitung alpha jika waktu sekarang
     *    masih sebelum jam pulang / batas cutoff absen hari ini.
     *
     * @param int         $userId  ID User guru (users.id)
     * @param int         $bulan   1-12
     * @param int         $tahun   Tahun (misal 2026)
     * @param object|null $setting Object setting dari absensi_setting
     * @return int Jumlah hari alpha riil
     */
    public function calculateTeacherMonthlyAlpha(int $userId, int $bulan, int $tahun, ?object $setting = null): int
    {
        return count($this->teacherMonthlyAlphaDates($userId, $bulan, $tahun, $setting));
    }

    /**
     * Daftar tanggal (Y-m-d) hari alpha guru pada periode — sumber tunggal
     * untuk angka di calculateTeacherMonthlyAlpha() dan rincian tanggal di
     * PDF surat teguran. Aturan 1–6 di bawah harus tetap identik dengan
     * logika penghitungannya; jangan ubah satu tanpa yang lain.
     *
     * @return string[] tanggal Y-m-d terurut menaik
     */
    public function teacherMonthlyAlphaDates(int $userId, int $bulan, int $tahun, ?object $setting = null): array
    {
        if ($userId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        if (!$setting) {
            $attSettingModel = new \App\Models\AttendanceSettingModel();
            $setting = $attSettingModel->first() ?? (object) [];
        }

        $startOfMonth = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endOfMonth   = date('Y-m-t', strtotime($startOfMonth));
        $today        = date('Y-m-d');
        $isCurrentMonth = ($today >= $startOfMonth && $today <= $endOfMonth);
        $endDate      = $isCurrentMonth ? $today : $endOfMonth;

        // Jangan hitung tanggal sebelum user dibuat di sistem (proteksi guru baru masuk di tengah bulan)
        if ($db->tableExists('users')) {
            $userRow = $db->table('users')->select('created_on')->where('id', $userId)->get()->getRow();
            if ($userRow && !empty($userRow->created_on)) {
                $userCreatedDate = date('Y-m-d', (int) $userRow->created_on);
                if ($userCreatedDate > $startOfMonth && $userCreatedDate <= $endOfMonth) {
                    $startOfMonth = $userCreatedDate;
                }
            }
        }

        // Libur (mingguan + nasional + jadwal sekolah) diselesaikan
        // AttendancePolicy; peta jadwal per hari ISO tetap dibutuhkan untuk
        // jam pulang hari ini di bawah.
        $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

        // Izin sah dari absensi_izin (approved)
        $approvedLeaves = [];
        if ($db->tableExists('absensi_izin')) {
            try {
                $izs = $db->table('absensi_izin')
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $endDate)
                    ->where('tgl_selesai >=', $startOfMonth)
                    ->get()->getResult();
                foreach ($izs as $iz) {
                    $c = strtotime((string) $iz->tgl_mulai);
                    $e = strtotime((string) $iz->tgl_selesai);
                    while ($c <= $e) {
                        $d = date('Y-m-d', $c);
                        if ($d >= $startOfMonth && $d <= $endDate) {
                            $approvedLeaves[$d] = true;
                        }
                        $c = strtotime('+1 day', $c);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Presensi harian
        $attRecords = [];
        $rows = $db->table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl >=', $startOfMonth)
            ->where('tgl <=', $endDate)
            ->get()->getResult();
        foreach ($rows as $r) {
            $attRecords[$r->tgl] = $r;
        }

        // Hitung alpha
        $alphaDates = [];
        $cur = strtotime($startOfMonth);
        $end = strtotime($endDate);
        $nowTime = date('H:i:s');
        $jamPulangSetting = $setting->jam_pulang ?? '15:00:00';

        while ($cur <= $end) {
            $dateStr = date('Y-m-d', $cur);
            $dayISO  = (int) date('N', $cur);

            // Skip libur (mingguan ternormalisasi + nasional + jadwal sekolah)
            if (\App\Services\AttendancePolicy::isLibur($dateStr)) {
                $cur = strtotime('+1 day', $cur);
                continue;
            }

            // Skip jika ada izin sah approved
            if (isset($approvedLeaves[$dateStr])) {
                $cur = strtotime('+1 day', $cur);
                continue;
            }

            // Jika tanggal adalah HARI INI: cek apakah jam kerja sudah selesai
            if ($dateStr === $today) {
                $jamPulangHariIni = isset($jadwalKerja[$dayISO]->jam_pulang) ? $jadwalKerja[$dayISO]->jam_pulang : $jamPulangSetting;
                // Jika masih dalam jam kerja (belum lewat jam pulang), JANGAN dihitung alpha
                if ($nowTime < $jamPulangHariIni) {
                    $cur = strtotime('+1 day', $cur);
                    continue;
                }
            }

            // Evaluasi kehadiran di absensi_harian
            if (isset($attRecords[$dateStr])) {
                $rec = $attRecords[$dateStr];
                $st = strtoupper(trim((string) ($rec->status_masuk ?? '')));
                // Jika eksplisit ALPHA/ALPA
                if (in_array($st, ['ALPHA', 'ALPA'], true)) {
                    $alphaDates[] = $dateStr;
                } elseif ($st === 'PENDING_VERIFICATION') {
                    // Kehadiran yang menunggu verifikasi admin TIDAK langsung
                    // jadi alpha: guru sudah menunjukkan diri, tinggal
                    // admin-nya yang belum Action. Setelah jendela tinjau
                    // lewat (AttendancePolicy::PENDING_REVIEW_HARI), hari itu
                    // baru dihitung alpha di sini, dan cron
                    // finalisasiPendingVerifikasi() menuliskan status ALPHA-nya
                    // agar denda, surat teguran, dan rekap memakai satu
                    // angka yang sama.
                    if (\App\Services\AttendancePolicy::pendingLewatBatas($dateStr)) {
                        $alphaDates[] = $dateStr;
                    }
                } elseif (in_array($st, ['ONTIME', 'LATE', 'WFH', 'IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS', 'DINAS_APPROVED', 'DINAS_PENDING', 'DINAS_OUT'], true) || !empty($rec->jam_masuk)) {
                    // Hadir atau izin tercatat
                } else {
                    $alphaDates[] = $dateStr;
                }
            } else {
                // Tidak ada record sama sekali di hari kerja yang sudah berlalu -> Alpha
                $alphaDates[] = $dateStr;
            }

            $cur = strtotime('+1 day', $cur);
        }

        sort($alphaDates);

        return $alphaDates;
    }

    /**
     * Sinkronisasi otomatis pembatalan Surat Teguran saat terjadi perubahan absensi
     * (misal persetujuan izin, sakit, dinas, atau koreksi presensi).
     * Jika akumulasi alpha terkini pada bulan tersebut sudah < level surat,
     * surat teguran aktif otomatis ditandai 'dibatalkan'.
     *
     * @param int         $userId  ID User guru
     * @param int         $bulan   1-12
     * @param int         $tahun   Tahun
     * @param object|null $setting Setting presensi
     * @return int Jumlah surat yang dibatalkan
     */
    public function syncSuratTeguranOnAttendanceChange(int $userId, int $bulan, int $tahun, ?object $setting = null, ?string $reason = null): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $db = \Config\Database::connect();

        $svc = new \App\Services\SuratTeguranService($db);
        if (!$svc->tabelAda()) {
            return 0;
        }

        $idGuru = $svc->idGuruDariUser($userId);
        if ($idGuru <= 0) {
            return 0;
        }

        $currentAlpha = $this->calculateTeacherMonthlyAlpha($userId, $bulan, $tahun, $setting);

        // Aturan pembatalan DAN penghidupan-kembali diputuskan di satu service,
        // supaya tidak ada lagi implementasi berbeda antar pemanggil.
        $hasil = $svc->sinkronkan($idGuru, $currentAlpha, (string) $bulan, (string) $tahun, $reason);

        $batal = count($hasil['batal']);
        $hidup = count($hasil['hidup']);

        if ($hidup > 0) {
            log_message('info', sprintf(
                '[SuratTeguran] %d surat dihidupkan kembali untuk guru %d, periode %d/%d '
                . '(alpha kini %d hari) — pembatalan sebelumnya sudah tidak berlaku.',
                $hidup,
                $idGuru,
                $bulan,
                $tahun,
                $currentAlpha
            ));
        }

        return $batal;
    }
}


