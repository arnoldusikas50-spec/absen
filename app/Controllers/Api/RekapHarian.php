<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API Rekap Kehadiran Harian (akses eksternal via API key).
 *
 * GET /api/rekap-harian?tgl=YYYY-MM-DD&key=XXX
 * Header alternatif: X-API-KEY: XXX  atau  Authorization: Bearer XXX
 * API key diambil dari tabel setting kolom api_key (menu Pengaturan).
 *
 * Query opsional:
 *   tgl    : tanggal rekap (default hari ini). Format YYYY-MM-DD, tidak boleh masa depan.
 *   key    : API key (bisa juga via header).
 *   format : json (default) | teks  → teks ringkas siap tempel ke WA.
 */
class RekapHarian extends BaseController
{
    private const KODE = [
        'HADIR'   => 'H',
        'SAKIT'   => 'S',
        'IJIN'    => 'I',
        'DINAS'   => 'D',
        'CUTI'    => 'C',
        'ALPHA'   => 'A',
        'BDR'     => 'B',
        'WFH'     => 'W',
        'PENDING' => 'P',
    ];

    public function harian(): ResponseInterface
    {
        // ── 1. Auth API key ──────────────────────────────────────────
        $key = $this->extractKey();
        try {
            $dashboard = new DashboardModel();
            $setting = $dashboard->getSetting();
        } catch (\Throwable $e) {
            return $this->json(false, 'Koneksi database gagal.', null, 500);
        }

        $expected = trim((string) ($setting->api_key ?? ''));
        if ($expected === '') {
            return $this->json(false, 'API key belum dikonfigurasi. Isi api_key di menu Pengaturan.', null, 503);
        }
        if ($key === '' || !hash_equals($expected, $key)) {
            return $this->json(false, 'API key tidak valid.', null, 401);
        }

        // ── 2. Validasi tanggal ──────────────────────────────────────
        $tgl = $this->request->getGet('tgl') ?: date('Y-m-d');
        if (!is_string($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            return $this->json(false, 'Parameter tgl tidak valid. Format: YYYY-MM-DD.', null, 422);
        }
        [$y, $m, $d] = array_map('intval', explode('-', $tgl));
        if (!checkdate($m, $d, $y)) {
            return $this->json(false, 'Parameter tgl tidak valid (kalender).', null, 422);
        }
        if ($tgl > date('Y-m-d')) {
            return $this->json(false, 'Tanggal tidak boleh masa depan.', null, 422);
        }

        // ── 3. TP/SMT aktif ──────────────────────────────────────────
        try {
            $tp = $dashboard->getTahunActive();
            $smt = $dashboard->getSemesterActive();
        } catch (\Throwable $e) {
            return $this->json(false, 'Gagal membaca tahun/semester aktif.', null, 500);
        }
        if (!$tp || !$smt) {
            return $this->json(false, 'Tahun pelajaran / semester aktif belum diset.', null, 500);
        }

        // ── 4. Rekap ─────────────────────────────────────────────────
        try {
            $db = \Config\Database::connect();
            $guru = $this->rekapGuru($db, $tgl);
            $siswa = $this->rekapSiswa($db, $tgl, (int) $tp->id_tp, (int) $smt->id_smt);
        } catch (\Throwable $e) {
            log_message('error', '[Api\RekapHarian] ' . $e->getMessage());
            return $this->json(false, 'Gagal menyusun rekap.', null, 500);
        }

        $payload = [
            'tanggal' => $tgl,
            'meta' => [
                'tp' => $tp->tahun ?? $tp->id_tp,
                'smt' => $smt->nama_smt ?? $smt->id_smt,
                'generated_at' => date('Y-m-d H:i:s'),
            ],
            'guru' => $guru,
            'siswa' => $siswa,
        ];

        // ── 5. Format teks (siap tempel WA) ──────────────────────────
        if (strtolower((string) $this->request->getGet('format')) === 'teks') {
            return $this->response
                ->setStatusCode(200)
                ->setContentType('text/plain; charset=utf-8')
                ->setBody($this->renderTeks($tgl, $guru, $siswa));
        }

        return $this->json(true, 'OK', $payload, 200);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function json(bool $status, string $message, $data, int $code): ResponseInterface
    {
        $body = ['status' => $status, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }

        return $this->response->setStatusCode($code)->setJSON($body);
    }

    private function extractKey(): string
    {
        $key = $this->request->getGet('key') ?? $this->request->getPost('key') ?? '';
        if ($key === '' || $key === null) {
            $key = $this->request->getHeaderLine('X-API-KEY');
        }
        if ($key === '') {
            $auth = $this->request->getHeaderLine('Authorization');
            if (stripos($auth, 'bearer ') === 0) {
                $key = trim(substr($auth, 7));
            }
        }

        return trim((string) $key);
    }

    /**
     * Normalisasi status_masuk guru absensi_harian ke: HADIR|SAKIT|IJIN|DINAS|CUTI|ALPHA|PENDING
     */
    private function normGuru(string $raw): string
    {
        $st = strtoupper(trim($raw));
        if ($st === 'ONTIME' || $st === 'LATE') {
            return 'HADIR';
        }
        if ($st === 'SAKIT') {
            return 'SAKIT';
        }
        if ($st === 'IJIN' || $st === 'IZIN') {
            return 'IJIN';
        }
        if ($st === 'CUTI') {
            return 'CUTI';
        }
        if ($st === 'DINAS_REJECTED') {
            return 'ALPHA';
        }
        if ($st === 'WFH') {
            return 'WFH';
        }
        if ($st === 'PENDING_VERIFICATION') {
            return 'PENDING';
        }
        if ($st === 'ALPHA' || $st === 'ALPA') {
            return 'ALPHA';
        }
        if ($st === 'DINAS' || strpos($st, 'DINAS') === 0 || strpos($st, 'DINAS') !== false) {
            return 'DINAS';
        }

        return 'ALPHA';
    }

    /**
     * Status izin approved (absensi_izin.jenis) → SAKIT|IJIN|DINAS|CUTI
     */
    private function normIzin(string $jenis): string
    {
        $j = strtolower(trim($jenis));
        if ($j === 'sakit') {
            return 'SAKIT';
        }
        if ($j === 'dinas') {
            return 'DINAS';
        }
        if ($j === 'cuti') {
            return 'CUTI';
        }

        return 'IJIN'; // ijin / izin / lainnya
    }

    private function rekapGuru($db, string $tgl): array
    {
        // Guru aktif
        $users = $db->table('users u')
            ->select('u.id, u.first_name, u.last_name, g.nip, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->where('u.active', 1)
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        // Presensi hari itu
        $rows = $db->table('absensi_harian')
            ->select('user_id, status_masuk, telat_menit')
            ->where('tgl', $tgl)
            ->get()->getResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->user_id] = $r;
        }

        // Izin approved yang mencakup tanggal (untuk user tanpa baris presensi)
        $leaves = [];
        try {
            $leaveRows = $db->table('absensi_izin')
                ->select('user_id, id_guru, jenis')
                ->where('status', 'approved')
                ->where('tgl_mulai <=', $tgl)
                ->where('tgl_selesai >=', $tgl)
                ->get()->getResult();
            foreach ($leaveRows as $lr) {
                if (!empty($lr->user_id)) {
                    $leaves[(int) $lr->user_id] = $this->normIzin((string) $lr->jenis);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan — fallback ke ALPHA
        }

        $rincian = ['hadir' => 0, 'telat' => 0, 'sakit' => 0, 'ijin' => 0, 'dinas' => 0, 'cuti' => 0, 'alpha' => 0, 'pending' => 0, 'wfh' => 0];
        $tidakHadir = [];

        foreach ($users as $u) {
            $nama = $u->nama_guru ?: trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            if (isset($map[(int) $u->id])) {
                $st = $this->normGuru((string) ($map[(int) $u->id]->status_masuk ?? ''));
                $telat = $st === 'HADIR' && strtoupper((string) ($map[(int) $u->id]->status_masuk ?? '')) === 'LATE';
            } elseif (isset($leaves[(int) $u->id])) {
                $st = $leaves[(int) $u->id];
                $telat = false;
            } else {
                // Tanpa record = Alpha (sesuai kesepakatan)
                $st = 'ALPHA';
                $telat = false;
            }

            if ($st === 'HADIR') {
                $rincian['hadir']++;
                if ($telat) {
                    $rincian['telat']++;
                }
            } else {
                $key = strtolower($st) === 'pending' ? 'pending' : strtolower($st);
                if (isset($rincian[$key])) {
                    $rincian[$key]++;
                }
                $tidakHadir[] = [
                    'nama' => $nama,
                    'nip' => $u->nip ?? null,
                    'status' => $st === 'PENDING' ? 'PENDING' : $st,
                    'kode' => self::KODE[$st] ?? '?',
                ];
            }
        }

        $total = count($users);
        $hadir = $rincian['hadir'];

        return [
            'total' => $total,
            'hadir' => $hadir,
            'tidak_hadir' => $total - $hadir,
            // Tambahan (tidak mengubah arti 'tidak_hadir'): pending verifikasi
            // memang belum hadir, tapi juga belum alpha — tanpa pemisahan ini
            // konsumen API membaca jumlah alpa lebih besar dari sebenarnya.
            // 'wfh' ditambahkan bersamaan karena normGuru() tidak memetakan
            // WFH, sehingga hari kerja dari rumah dulu dihitung sebagai ALPHA.
            'pending' => $rincian['pending'],
            'wfh'     => $rincian['wfh'],
            'rincian' => $rincian,
            'tidak_hadir_list' => $tidakHadir,
            'ringkasan_teks' => sprintf(
                'Guru H:%d TH:%d (S:%d I:%d D:%d C:%d A:%d P:%d W:%d)',
                $hadir, $total - $hadir,
                $rincian['sakit'], $rincian['ijin'], $rincian['dinas'], $rincian['cuti'],
                $rincian['alpha'], $rincian['pending'], $rincian['wfh']
            ),
        ];
    }

    private function rekapSiswa($db, string $tgl, int $idTp, int $idSmt): array
    {
        $kelasList = $db->table('master_kelas')
            ->select('id_kelas, nama_kelas')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->orderBy('level_id', 'ASC')
            ->orderBy('nama_kelas', 'ASC')
            ->get()->getResult();

        $grand = ['hadir' => 0, 'sakit' => 0, 'ijin' => 0, 'alpha' => 0, 'bdr' => 0];
        $grandTotal = 0;
        $perKelas = [];

        foreach ($kelasList as $k) {
            $idKelas = (int) $k->id_kelas;

            // Anggota kelas — hanya siswa AKTIF (buku_induk.status = '1').
            $anggotaQ = $db->table('kelas_siswa ks')
                ->select('ms.id_siswa, ms.nama, ms.nis')
                ->join('master_siswa ms', 'ms.id_siswa = ks.id_siswa')
                ->where('ks.id_kelas', $idKelas)
                ->where('ks.id_tp', $idTp)
                ->where('ks.id_smt', $idSmt);
            try {
                if ($db->tableExists('buku_induk')) {
                    $anggotaQ->join('buku_induk bi', 'bi.id_siswa = ks.id_siswa', 'left')
                        ->groupStart()
                            ->where('bi.status', '1')
                            ->orWhere('bi.id_siswa IS NULL', null, false)
                        ->groupEnd();
                }
            } catch (\Throwable $e) {
            }
            $anggota = $anggotaQ->orderBy('ms.nama', 'ASC')
                ->get()->getResult();

            // Presensi hari itu
            $pres = $db->table('presensi_siswa')
                ->select('id_siswa, status')
                ->where('id_kelas', $idKelas)
                ->where('tgl', $tgl)
                ->get()->getResult();
            $pmap = [];
            foreach ($pres as $p) {
                $pmap[(int) $p->id_siswa] = strtoupper(trim((string) $p->status));
            }

            $rincian = ['hadir' => 0, 'sakit' => 0, 'ijin' => 0, 'alpha' => 0, 'bdr' => 0];
            $thList = [];

            // Kelas yang sedang menjadwalkan BDR: siswa yang tidak scan bukan alpha,
            // melainkan belajar dari rumah — meski cron 18:00 belum jalan.
            $bdrKelas = null;
            try {
                $bdrKelas = (new \App\Services\PresensiBdrService())->untukKelas($tgl, $idKelas);
            } catch (\Throwable $e) {
                log_message('error', '[Api\RekapHarian] BDR: ' . $e->getMessage());
            }

            foreach ($anggota as $s) {
                $raw = $pmap[(int) $s->id_siswa] ?? null;
                $tanpaCatatan = $raw === null || $raw === ''
                    || in_array($raw, ['ALPA', 'ALPHA', 'TANPA_KETERANGAN'], true);

                if ($raw === 'HADIR') {
                    $st = 'HADIR';
                } elseif ($raw === 'SAKIT') {
                    $st = 'SAKIT';
                } elseif ($raw === 'IZIN' || $raw === 'IJIN') {
                    $st = 'IJIN';
                } elseif ($raw === 'BDR' || ($bdrKelas !== null && $tanpaCatatan)) {
                    $st = 'BDR';
                } else {
                    // ALPA, TANPA_KETERANGAN, atau tanpa record = Alpha
                    $st = 'ALPHA';
                }

                if ($st === 'HADIR') {
                    $rincian['hadir']++;
                } elseif ($st === 'SAKIT') {
                    $rincian['sakit']++;
                } elseif ($st === 'IJIN') {
                    $rincian['ijin']++;
                } elseif ($st === 'BDR') {
                    $rincian['bdr']++;
                } else {
                    $rincian['alpha']++;
                    $thList[] = [
                        'nama' => $s->nama,
                        'nis' => $s->nis ?? null,
                        'status' => $st,
                        'kode' => self::KODE[$st] ?? '?',
                    ];
                }
            }

            $total = count($anggota);
            $grandTotal += $total;
            foreach ($grand as $kk => $vv) {
                $grand[$kk] += $rincian[$kk];
            }

            $names = array_map(
                static fn ($x) => $x['nama'] . ' (' . $x['kode'] . ')',
                $thList
            );

            // TH tidak memasukkan BDR: siswa belajar dari rumah atas kebijakan sekolah.
            $tidakHadir = $total - $rincian['hadir'] - $rincian['bdr'];

            $perKelas[] = [
                'id_kelas' => $idKelas,
                'nama_kelas' => $k->nama_kelas,
                'total' => $total,
                'hadir' => $rincian['hadir'],
                'tidak_hadir' => $tidakHadir,
                'rincian' => $rincian,
                'tidak_hadir_list' => $thList,
                'ringkasan_teks' => sprintf(
                    'Kelas %s = H:%d TH:%d%s',
                    $k->nama_kelas, $rincian['hadir'], $tidakHadir,
                    $names ? ' : ' . implode(', ', $names) : ''
                ),
            ];
        }

        return [
            'total' => $grandTotal,
            'hadir' => $grand['hadir'],
            'tidak_hadir' => $grandTotal - $grand['hadir'] - $grand['bdr'],
            'rincian' => $grand,
            'per_kelas' => $perKelas,
        ];
    }

    private function renderTeks(string $tgl, array $guru, array $siswa): string
    {
        $lines = [];
        $lines[] = 'Rekap Kehadiran ' . date('d M Y', strtotime($tgl));
        $lines[] = '';
        $lines[] = $guru['ringkasan_teks'] ?? '';
        foreach ($guru['tidak_hadir_list'] ?? [] as $x) {
            $lines[] = '- ' . $x['nama'] . ' (' . $x['kode'] . ')';
        }
        $lines[] = '';
        $lines[] = sprintf('Siswa H:%d TH:%d', $siswa['hadir'] ?? 0, $siswa['tidak_hadir'] ?? 0);
        foreach ($siswa['per_kelas'] ?? [] as $k) {
            $lines[] = $k['ringkasan_teks'];
        }

        return implode("\n", $lines) . "\n";
    }
}
