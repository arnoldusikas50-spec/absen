<?php

namespace App\Services;

use App\Models\AttendanceSettingModel;
use App\Models\DashboardModel;

/**
 * RekapPdfService — Penyusun PDF rekap bulanan (denda & presensi).
 *
 * Dipindah verbatim dari PresensiAdmin (buildDendaPdf, buildRekapBulananPdf
 * beserta helper filteredAlphaMonthMap, getYTDDendaStats, gambarBase64)
 * supaya alur cron (`CronController::monthlyPdf`, tgl 1 → PDF bulan lalu)
 * memakai render yang SAMA dengan tombol manual "Kirim ke WA & TG" —
 * bukan dokumen terpisah yang formatnya bisa melenceng.
 *
 * PresensiAdmin tetap memanggil lewat delegator tipis, jadi seluruh call
 * site lama (dashboard, export, kirim manual) tidak berubah perilaku.
 */
class RekapPdfService
{
    /**
     * Render rekap denda periode terpilih menjadi string PDF.
     *
     * @return array{pdf:string, filename:string, bulan:string, tahun:string, jumlah_guru:int}
     * @throws \RuntimeException bila Dompdf tidak menghasilkan output
     */
    public function buildDendaPdf(string $bulan, string $tahun): array
    {
        $db = \Config\Database::connect();

        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->get()->getResult();

        $mapByUser = [];
        foreach ($attendances as $att) {
            if (!isset($mapByUser[$att->user_id]))
                $mapByUser[$att->user_id] = [];
            $mapByUser[$att->user_id][$att->tgl] = $att;
        }

        $setting = (new DashboardModel())->getSetting();
        $absenSettingModel = new AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();

        if (!$setting)
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];

        $pembayaranList = $db->table('absensi_pembayaran_denda')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status !=', 'batal')
            ->get()->getResult();

        $lunasMap = [];
        $bayarTunaiMap = [];
        $potonganPoinMap = [];
        $potonganSaldoMap = [];
        $poinDigunakanMap = [];

        foreach ($pembayaranList as $p) {
            $nominalActual = (float)($p->nominal_bayar ?: $p->total_denda);
            if ($p->status === 'lunas') {
                $lunasMap[$p->user_id] = $p;
            }
            $kelas = \App\Services\AttendancePenaltyService::kelasPembayaranDenda($p->metode_bayar, $p->keterangan ?? null);
            if ($kelas === 'POIN') {
                $potonganPoinMap[$p->user_id] = ($potonganPoinMap[$p->user_id] ?? 0) + $nominalActual;
                $poinDigunakanMap[$p->user_id] = ($poinDigunakanMap[$p->user_id] ?? 0) + (int)($p->poin_digunakan ?? 0);
            } elseif ($kelas === 'SALDO') {
                $potonganSaldoMap[$p->user_id] = ($potonganSaldoMap[$p->user_id] ?? 0) + $nominalActual;
            } else {
                $bayarTunaiMap[$p->user_id] = ($bayarTunaiMap[$p->user_id] ?? 0) + $nominalActual;
            }
        }

        // Progressive alpha for export — peta tersaring kanonik (missing-only).
        $startExp = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endExp   = date('Y-m-t', strtotime($startExp));
        $alphaDetailExp = $this->filteredAlphaMonthMap($startExp, $endExp, $absenSetting);
        $alphaMap = [];
        foreach ($alphaDetailExp as $uid => $info) $alphaMap[$uid] = $info['count'];
        $alphaDetailMap = $alphaDetailExp;

        $ytdStats = $this->getYTDDendaStats($tahun);

        // Kop Sekolah Processing
        $kopBase64 = '';
        if (isset($setting->kopsekolah) && $setting->kopsekolah && file_exists(FCPATH . $setting->kopsekolah)) {
            $type = pathinfo(FCPATH . $setting->kopsekolah, PATHINFO_EXTENSION);
            $imgData = file_get_contents(FCPATH . $setting->kopsekolah);
            $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
        }

        $logoBase64 = '';
        if (isset($setting->logo_kiri) && $setting->logo_kiri && file_exists(FCPATH . $setting->logo_kiri)) {
            $type = pathinfo(FCPATH . $setting->logo_kiri, PATHINFO_EXTENSION);
            $imgData = file_get_contents(FCPATH . $setting->logo_kiri);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
        }

        // TTD Kepala Sekolah (dari /settings) untuk blok tanda tangan
        $ttdBase64 = '';
        if (isset($setting->tanda_tangan) && $setting->tanda_tangan && file_exists(FCPATH . $setting->tanda_tangan)) {
            $type = pathinfo(FCPATH . $setting->tanda_tangan, PATHINFO_EXTENSION);
            $imgData = file_get_contents(FCPATH . $setting->tanda_tangan);
            $ttdBase64 = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
        }

        $data = [
            'setting' => $setting,
            'absenSetting' => $absenSetting,
            'users' => $users,
            'mapByUser' => $mapByUser,
            'alphaMap' => $alphaMap,
            'alphaDetailMap' => $alphaDetailMap,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'lunasMap' => $lunasMap,
            'bayarTunaiMap' => $bayarTunaiMap,
            'potonganPoinMap' => $potonganPoinMap,
            'potonganSaldoMap' => $potonganSaldoMap,
            'ytdStats' => $ytdStats,
            'kopBase64' => $kopBase64,
            'logoBase64' => $logoBase64,
            'ttdBase64' => $ttdBase64
        ];

        $html = view('admin/presensi/denda_pdf', $data);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf = $dompdf->output();
        if ($pdf === null || $pdf === '') {
            throw new \RuntimeException('Dompdf tidak menghasilkan output untuk rekap denda.');
        }

        return [
            'pdf'          => $pdf,
            'filename'     => "Rekap_Denda_{$bulan}_{$tahun}.pdf",
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'jumlah_guru'  => count($users),
        ];
    }

    /**
     * Helper: render rekap_pdf.php menjadi string PDF (via Dompdf).
     *
     * Pola sama dengan buildDendaPdf() — gambar logo/kop di-inline
     * sebagai base64 supaya Dompdf tidak perlu akses jaringan.
     *
     * @return array{pdf:string, filename:string, bulan:string, tahun:string, jumlah_guru:int}
     * @throws \RuntimeException bila Dompdf tidak menghasilkan output
     */
    public function buildRekapBulananPdf(string $bulan, string $tahun): array
    {
        $db = \Config\Database::connect();

        $setting  = (new DashboardModel())->getSetting();
        if (!$setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        // Kepala Sekolah NIP untuk sorting Kepsek di baris teratas
        $kepsekNip = trim((string) ($setting->nip ?? ''));

        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupStart()
                ->where('g.status_aktif', 'Aktif')
                ->orWhere('g.status_aktif IS NULL')
                ->orWhere('g.status_aktif', '')
            ->groupEnd()
            ->get()->getResult();

        usort($users, function ($a, $b) use ($kepsekNip) {
            $isA = $kepsekNip !== '' && trim((string) ($a->nip ?? '')) === $kepsekNip;
            $isB = $kepsekNip !== '' && trim((string) ($b->nip ?? '')) === $kepsekNip;
            if ($isA && !$isB) return -1;
            if (!$isA && $isB) return 1;
            return strcasecmp((string) ($a->nama_guru ?? ''), (string) ($b->nama_guru ?? ''));
        });

        $attendances = $db->table('absensi_harian')
            ->where('MONTH(tgl)', $bulan)
            ->where('YEAR(tgl)', $tahun)
            ->get()->getResult();

        $mapByUser = [];
        foreach ($attendances as $att) {
            if (!isset($mapByUser[$att->user_id])) {
                $mapByUser[$att->user_id] = [];
            }
            $mapByUser[$att->user_id][$att->tgl] = $att;
        }

        $holidays = [];
        if ($db->tableExists('absensi_hari_libur')) {
            $rows = $db->table('absensi_hari_libur')
                ->where('MONTH(tanggal)', $bulan)
                ->where('YEAR(tanggal)', $tahun)
                ->get()->getResult();
            foreach ($rows as $r) {
                $holidays[$r->tanggal] = $r->keterangan;
            }
        }

        $jadwalKerja = [];
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $jadwalRows = $db->table('absensi_jadwal_kerja')->get()->getResult();
            foreach ($jadwalRows as $j) {
                $jadwalKerja[(int) $j->hari] = $j;
            }
        }

        // Peta izin approved bulan ini (konsisten dengan halaman rekap)
        $leaveMap = [];
        if ($db->tableExists('absensi_izin')) {
            try {
                $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
                $endDate   = date('Y-m-t', strtotime($startDate));
                $leaveRows = $db->table('absensi_izin')
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $endDate)
                    ->where('tgl_selesai >=', $startDate)
                    ->get()->getResult();
                foreach ($leaveRows as $l) {
                    $cur = strtotime(max($startDate, (string) $l->tgl_mulai));
                    $end = strtotime(min($endDate, (string) $l->tgl_selesai));
                    while ($cur <= $end) {
                        $leaveMap[(int) $l->user_id][date('Y-m-d', $cur)] = true;
                        $cur = strtotime('+1 day', $cur);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[buildRekapBulananPdf] leaveMap gagal: ' . $e->getMessage());
            }
        }

        $absenSettingModel = new AttendanceSettingModel();
        $absenSetting      = $absenSettingModel->first();

        // Inline gambar supaya Dompdf tidak butuh akses jaringan
        $logoBase64 = $this->gambarBase64($setting->logo_kiri ?? null);
        $kopBase64  = $this->gambarBase64($setting->kopsekolah ?? null);

        $data = [
            'users'       => $users,
            'mapByUser'   => $mapByUser,
            'holidays'    => $holidays,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'setting'     => $setting,
            'absenSetting' => $absenSetting,
            'jadwalKerja' => $jadwalKerja,
            'leaveMap'    => $leaveMap,
            'logoBase64'  => $logoBase64,
            'kopBase64'   => $kopBase64,
        ];

        $html = view('admin/presensi/rekap_pdf', $data);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf  = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $pdf = $dompdf->output();
        if ($pdf === null || $pdf === '') {
            throw new \RuntimeException('Dompdf tidak menghasilkan output untuk rekap bulanan.');
        }

        return [
            'pdf'         => $pdf,
            'filename'    => "Rekap_Presensi_{$bulan}_{$tahun}.pdf",
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'jumlah_guru' => count($users),
        ];
    }

    public function filteredAlphaMonthMap(string $startDate, string $endDate, $setting): array
    {
        $db = \Config\Database::connect();
        $startDate = substr($startDate, 0, 10);
        $endDate = substr($endDate, 0, 10);
        $scanStart = date('Y-m-d', strtotime($startDate . ' -62 days'));

        $svc = new \App\Services\AttendancePenaltyService();
        try {
            $sum = $svc->calculateAllUsersPeriodAlphaSummary($scanStart, $endDate, $setting);
        } catch (\Throwable $e) {
            $sum = [];
        }

        // Baris ALPHA denda 0 (cap tercapai) tetap dianggap tercatat agar tidak
        // diestimasi ganda sebagai missing.
        $recorded = [];
        try {
            $rows = $db->table('absensi_harian')
                ->select('user_id, tgl, status_masuk, denda')
                ->where('tgl >=', $startDate)
                ->where('tgl <=', $endDate)
                ->get()->getResult();
            foreach ($rows as $r) {
                if (empty($r->tgl)) {
                    continue;
                }
                $st = strtoupper(trim((string) ($r->status_masuk ?? '')));
                if ((float) ($r->denda ?? 0) > 0 || in_array($st, ['ALPHA', 'ALPA'], true)) {
                    $recorded[(int) $r->user_id][substr($r->tgl, 0, 10)] = true;
                }
            }
        } catch (\Throwable $e) {}

        $today = date('Y-m-d');
        $map = [];
        foreach ($sum as $uid => $info) {
            $uid = (int) $uid;
            $items = [];
            $allCount = 0;
            foreach (($info['items'] ?? []) as $it) {
                $t = substr($it['tgl'] ?? '', 0, 10);
                if ($t === '' || $t < $startDate || $t > $endDate || $t >= $today) {
                    continue;
                }
                $allCount++;
                if (isset($recorded[$uid][$t])) {
                    continue;
                }
                $items[] = $it;
            }
            if ($allCount > 0 || !empty($items)) {
                $cappedMissing = $svc->capMissingAlphaEstimates($uid, $items, $setting);
                $map[$uid] = [
                    'count' => $allCount,
                    'missing_count' => $cappedMissing['count'],
                    'total_denda' => (float) $cappedMissing['total'],
                    'items' => $cappedMissing['items'],
                ];
            }
        }
        return $map;
    }

    public function getYTDDendaStats($tahun)
    {
        // Hardening: tahun dipakai interpolasi literal di query di bawah.
        // Cast di sini agar aman walau pemanggil lalai memvalidasi.
        $tahun = (int) $tahun;
        $db = \Config\Database::connect();

        // 1. Get total denda harian YTD
        // Batas "hari ini" dari kalender app, bukan CURDATE(): clock MySQL
        // server satu jam di belakang WITA (lihat AttendancePolicy::hariIniWita).
        $hariIni = \App\Services\AttendancePolicy::hariIniWita();
        $dendaHarianRaw = $db->query("
            SELECT user_id, SUM(denda) as total_denda_harian
            FROM absensi_harian
            WHERE tgl >= '{$tahun}-01-01' AND tgl <= '{$hariIni}'
            GROUP BY user_id
        ")->getResult();
        $dendaHarianMap = [];
        foreach ($dendaHarianRaw as $d) {
            $dendaHarianMap[$d->user_id] = (float)$d->total_denda_harian;
        }

        // 2. Get payments YTD split by TUNAI, POIN, and SALDO
        // Tunai = komplemen (bukan poin/saldo), supaya baris dengan metode
        // kosong, huruf kecil, atau nilai lama tidak hilang dari ketiga peta
        // dan membuat guru terlihat masih berutang.
        $kanalBayar = "UPPER(TRIM(COALESCE(metode_bayar, '')))";
        $dibayarTunaiRaw = $db->query("
            SELECT user_id, SUM(COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)) as total
            FROM absensi_pembayaran_denda
            WHERE tahun = {$tahun} AND status != 'batal' AND {$kanalBayar} NOT IN ('POIN', 'VOUCHER', 'SALDO')
            GROUP BY user_id
        ")->getResult();
        $dibayarTunaiMap = [];
        foreach ($dibayarTunaiRaw as $d) {
            $dibayarTunaiMap[$d->user_id] = (float)$d->total;
        }

        $potonganPoinRaw = $db->query("
            SELECT user_id, SUM(COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)) as total
            FROM absensi_pembayaran_denda
            WHERE tahun = {$tahun} AND status != 'batal' AND {$kanalBayar} IN ('POIN', 'VOUCHER')
            GROUP BY user_id
        ")->getResult();
        $potonganPoinMap = [];
        foreach ($potonganPoinRaw as $d) {
            $potonganPoinMap[$d->user_id] = (float)$d->total;
        }

        $potonganSaldoRaw = $db->query("
            SELECT user_id, SUM(COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)) as total
            FROM absensi_pembayaran_denda
            WHERE tahun = {$tahun} AND status != 'batal' AND {$kanalBayar} = 'SALDO'
            GROUP BY user_id
        ")->getResult();
        $potonganSaldoMap = [];
        foreach ($potonganSaldoRaw as $d) {
            $potonganSaldoMap[$d->user_id] = (float)$d->total;
        }

        // 3. Get Alpha YTD — panggil langsung AttendancePenaltyService::missingAlphaFines()
        // untuk setiap guru agar kalkulasi 100% identik dan konsisten dengan Attendance::dendaYtd() guru.
        $absenSettingModel2 = new \App\Models\AttendanceSettingModel();
        $absenSetting2 = $absenSettingModel2->first();
        $alphaDendaMapYtd = [];
        $alphaCountMapYtd = [];
        $currYear = (int)$tahun;
        $ytdStart = $currYear . '-01-01';
        $ytdEnd   = ((int)date('Y') === $currYear) ? date('Y-m-d') : $currYear . '-12-31';

        $penaltySvcYtd = new \App\Services\AttendancePenaltyService();
        $usersListYtd = $db->query("SELECT u.id FROM users u JOIN users_groups ug ON ug.user_id = u.id WHERE ug.group_id = 2 AND u.active = 1")->getResult();
        // Batch: SATU kali summary untuk semua guru (bukan N kali full-recompute).
        $ytdUserIds = array_values(array_unique(array_map(static fn($r) => (int) $r->id, $usersListYtd)));
        $missBatchYtd = $penaltySvcYtd->missingAlphaFinesBatch($ytdUserIds, $ytdStart, $ytdEnd, $absenSetting2, false);
        foreach ($usersListYtd as $uRow) {
            $uId = (int) $uRow->id;
            $miss = $missBatchYtd[$uId] ?? ['count' => 0, 'total' => 0.0];
            $alphaCountMapYtd[$uId] = (int) ($miss['count'] ?? 0);
            $alphaDendaMapYtd[$uId] = (float) ($miss['total'] ?? 0);
        }

        // Settings for alpha fine (fallback)
        $absenSettingModel = new \App\Models\AttendanceSettingModel();
        $absenSetting = $absenSettingModel->first();
        // dendaAlphaPerHari kept for fallback but nominal is progressive map

        // 3b. Redemption APPROVED yang belum masuk pembayaran (voucher ngambang) —
        // wajib dihitung agar sama dengan rekap guru & denda-ytd.
        $unlinkedRewardMap = [];
        if ($db->tableExists('reward_redemptions') && $db->tableExists('rewards')) {
            try {
                $hasNominalCol = $db->fieldExists('nominal', 'rewards');
                $nominalExpr = $hasNominalCol ? 'COALESCE(r.nominal, 0)' : '0';
                $hasRedemptionLink = $db->tableExists('absensi_pembayaran_denda') && $db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda');
                $excludeSql = $hasRedemptionLink
                    ? " AND rr.id NOT IN (SELECT COALESCE(reward_redemption_id, 0) FROM absensi_pembayaran_denda WHERE reward_redemption_id IS NOT NULL)"
                    : "";
                $unlinkedRows = $db->query("
                    SELECT rr.user_id, SUM({$nominalExpr}) as total
                    FROM reward_redemptions rr
                    JOIN rewards r ON rr.reward_id = r.id
                    WHERE rr.status = 'APPROVED' AND YEAR(rr.updated_at) = {$tahun}
                    {$excludeSql}
                    GROUP BY rr.user_id
                ")->getResult();
                foreach ($unlinkedRows as $ur) {
                    $unlinkedRewardMap[(int) $ur->user_id] = (float) ($ur->total ?? 0);
                }
            } catch (\Throwable $e) {}
        }

        // Combine
        $ytdStats = [];
        $users = $db->query("SELECT u.id FROM users u JOIN users_groups ug ON ug.user_id = u.id WHERE ug.group_id = 2 AND u.active = 1")->getResult();
        foreach ($users as $u) {
            $userId = $u->id;
            $harian = $dendaHarianMap[$userId] ?? 0;
            // use progressive map, fallback to flat if service empty
            if (isset($alphaDendaMapYtd[$userId])) {
                $alphaDenda = (float)$alphaDendaMapYtd[$userId];
                $alphaCount = (int)($alphaCountMapYtd[$userId] ?? 0);
            } else {
                $alphaCount = 0;
                $alphaDenda = 0;
            }

            $totalDendaYTD = $harian + $alphaDenda;
            $dibayarTunaiYTD = $dibayarTunaiMap[$userId] ?? 0;
            $potonganPoinYTD = ($potonganPoinMap[$userId] ?? 0) + ($unlinkedRewardMap[$userId] ?? 0);
            $potonganSaldoYTD = $potonganSaldoMap[$userId] ?? 0;
            $totalDibayarYTD = $dibayarTunaiYTD + $potonganPoinYTD + $potonganSaldoYTD;

            $sisa = $totalDendaYTD - $totalDibayarYTD;
            if ($sisa < 0) $sisa = 0;

            $ytdStats[$userId] = [
                'total_denda' => $totalDendaYTD,
                'dibayar_tunai' => $dibayarTunaiYTD,
                'potongan_poin' => $potonganPoinYTD,
                'potongan_saldo' => $potonganSaldoYTD,
                'total_dibayar' => $totalDibayarYTD,
                'sisa' => $sisa
            ];
        }

        return $ytdStats;
    }

    public function gambarBase64(?string $path): string
    {
        if (empty($path)) {
            return '';
        }
        $full = FCPATH . ltrim((string) $path, '/');
        if (!file_exists($full)) {
            return '';
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        $data = @file_get_contents($full);
        if ($data === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
