<?php

namespace App\Controllers;

use App\Models\SiswaPresensiModel;
use App\Models\DashboardModel;
use App\Models\DropdownModel;
use App\Models\AttendanceSettingModel;
use Dompdf\Dompdf;

/**
 * PresensiSiswa Controller
 * Mengelola absensi harian siswa (masuk + keluar) — diakses Admin (role 1) dan Guru (role 2).
 *
 * Model data: tabel presensi_siswa
 *   - Satu record per siswa per hari (UNIQUE: id_siswa + tgl, key uq_ps_siswa_tgl)
 *   - jam_masuk  : waktu siswa masuk
 *   - jam_keluar : waktu siswa keluar
 *   - status     : HADIR | SAKIT | IZIN | ALPA | TANPA_KETERANGAN | BDR
 *       BDR = belajar dari rumah (mis. kelas X/XI diliburkan saat kelas XII
 *       ujian); dihitung sendiri di rekap dan tidak menurunkan persen hadir.
 */
class PresensiSiswa extends BaseController
{
    protected SiswaPresensiModel $presensiModel;
    protected DashboardModel     $dashboardModel;
    protected DropdownModel      $dropdownModel;
    protected AttendanceSettingModel $absenSettingModel;

    private const VALID_STATUS = ['HADIR', 'SAKIT', 'IZIN', 'ALPA', 'TANPA_KETERANGAN', 'BDR'];

    /** Status keterangan: tidak menyimpan jam kehadiran dan tidak dihitung hadir. */
    private const STATUS_TANPA_JAM = ['SAKIT', 'IZIN', 'ALPA', 'BDR'];

    public function __construct()
    {
        $this->presensiModel  = new SiswaPresensiModel();
        $this->dashboardModel = new DashboardModel();
        $this->dropdownModel  = new DropdownModel();
        $this->absenSettingModel = new AttendanceSettingModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper validasi & konteks
    // ─────────────────────────────────────────────────────────────────────────

    private function validTgl(?string $tgl): bool
    {
        if (!is_string($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) return false;
        [$y, $m, $d] = array_map('intval', explode('-', $tgl));
        return checkdate($m, $d, $y);
    }

    private function validJam(?string $jam): bool
    {
        return is_string($jam) && (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', trim($jam));
    }

    private function normJam(string $jam): string
    {
        $jam = trim($jam);
        return strlen($jam) === 5 ? $jam . ':00' : $jam;
    }

    /** Normalisasi "HH:MM:SS"/"HH:MM" → "HH:MM", fallback bila kosong/tak valid. */
    private static function jamPendek($jam, string $fallback): string
    {
        $s = substr(trim((string) ($jam ?? '')), 0, 5);
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s) ? $s : $fallback;
    }

    /** ID guru pencatat dari sesi (anti-spoofing: abaikan id_guru dari POST). */
    private function resolveGuruId(): ?int
    {
        try {
            $uid = $this->getUserId();
            if (!$uid) return null;
            $row = \Config\Database::connect()->table('master_guru')
                ->select('id_guru')->where('id_user', $uid)->get()->getRow();
            return $row ? (int) $row->id_guru : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Pastikan siswa anggota kelas pada TP/SMT aktif (cegah tulis silang). */
    private function isSiswaKelas(int $idSiswa, int $idKelas): bool
    {
        try {
            $db = \Config\Database::connect();
            $tp = $this->dashboardModel->getTahunActive();
            $smt = $this->dashboardModel->getSemesterActive();
            $b = $db->table('kelas_siswa')
                ->where('id_siswa', $idSiswa)->where('id_kelas', $idKelas);
            if ($tp) $b->where('id_tp', $tp->id_tp);
            if ($smt) $b->where('id_smt', $smt->id_smt);
            if ($b->countAllResults() === 0) {
                return false;
            }
            // Hanya siswa AKTIF (buku_induk.status = '1') boleh diabsen.
            // LULUS (2) / PINDAH (3) / KELUAR (4) ditolak di semua jalur tulis.
            return self::isSiswaAktif($idSiswa);
        } catch (\Throwable $e) {
            // Fail-closed: saat ragu, tolak tulis daripada bocor ke kelas lain
            return false;
        }
    }

    /**
     * Status AKTIF? (buku_induk.status = '1').
     * Tanpa baris buku_induk (data lama) dianggap AKTIF.
     */
    public static function isSiswaAktif(int $idSiswa): bool
    {
        if ($idSiswa <= 0) {
            return false;
        }
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('buku_induk')) {
                return true;
            }
            $row = $db->table('buku_induk')->select('status')
                ->where('id_siswa', $idSiswa)->get()->getRow();
            if (!$row) {
                return true;
            }
            return (string) ($row->status ?? '1') === '1';
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function canManageSiswa(): bool
    {
        return $this->isAdmin() || $this->isGuru();
    }

    /**
     * Kelas yang boleh dikelola pengguna ini; null = semua.
     * - Admin & panitia: semua kelas.
     * - Guru piket hari ini: semua kelas (butuh lintas kelas).
     * - Guru lain yang punya data penugasan TP/SMT aktif: hanya kelas
     *   perwalian (jabatan 4 / master_kelas.guru_id) + kelas yang diampu.
     * - Guru TANPA data penugasan sama sekali: semua (fallback, bukan
     *   berarti bebas — admin wajib melengkapi jabatan agar scoping jalan).
     * - Baris jabatan ada tapi tanpa kelas/mapel (mis. kepala sekolah yang
     *   tidak mengampu kelas): juga semua, karena tidak ada scope untuk
     *   dikunci — daftar kosong akan memblokir seluruh rekap/absen.
     */
    private function allowedKelasIds(): ?array
    {
        if ($this->isAdmin() || $this->isPanitia()) return null;
        $guruId = $this->resolveGuruId();
        if (!$guruId) return [];
        try {
            $db = \Config\Database::connect();
            $tp = $this->dashboardModel->getTahunActive();
            $smt = $this->dashboardModel->getSemesterActive();

            // Piket hari ini bebas lintas kelas
            if ($tp && $smt && $db->tableExists('piket_jadwal')) {
                $onDuty = $db->table('piket_jadwal')
                    ->where('hari', $this->hariIndo(date('l')))
                    ->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)
                    ->where('id_guru', $guruId)->countAllResults() > 0;
                if ($onDuty) return null;
            }

            $ids = [];
            $adaPenugasan = false;
            if ($db->tableExists('jabatan_guru')) {
                $jb = $db->table('jabatan_guru')->select('id_kelas, mapel_kelas')->where('id_guru', $guruId);
                if ($tp && $db->fieldExists('id_tp', 'jabatan_guru')) $jb->where('id_tp', $tp->id_tp);
                if ($smt && $db->fieldExists('id_smt', 'jabatan_guru')) $jb->where('id_smt', $smt->id_smt);
                $rows = $jb->get()->getResult();
                if (!empty($rows)) $adaPenugasan = true;
                foreach ($rows as $r) {
                    if ((int) ($r->id_kelas ?? 0) > 0) $ids[] = (int) $r->id_kelas;
                    foreach (self::parseKelasMapel($r->mapel_kelas ?? null) as $kid) $ids[] = $kid;
                }
            }
            // Fallback wali: master_kelas.guru_id
            try {
                if ($db->tableExists('master_kelas') && $db->fieldExists('guru_id', 'master_kelas')) {
                    $mk = $db->table('master_kelas')->select('id_kelas')->where('guru_id', $guruId);
                    if ($tp && $db->fieldExists('id_tp', 'master_kelas')) $mk->where('id_tp', $tp->id_tp);
                    if ($smt && $db->fieldExists('id_smt', 'master_kelas')) $mk->where('id_smt', $smt->id_smt);
                    $walis = $mk->get()->getResult();
                    if (!empty($walis)) $adaPenugasan = true;
                    foreach ($walis as $r) $ids[] = (int) $r->id_kelas;
                }
            } catch (\Throwable $e) {
            }
            if (!$adaPenugasan) return null; // data admin belum lengkap → jangan kunci
            $ids = array_values(array_unique(array_filter($ids)));
            return $ids !== [] ? $ids : null;
        } catch (\Throwable $e) {
            return []; // fail-closed
        }
    }

    private function kelasAllowed(int $idKelas): bool
    {
        $allowed = $this->allowedKelasIds();
        return $allowed === null || in_array($idKelas, $allowed, true);
    }

    private function hariIndo(string $day): string
    {
        return [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ][$day] ?? $day;
    }

    /** Ambil id_kelas numerik dari kolom mapel_kelas (JSON baru / serialize lama). */
    private static function parseKelasMapel($raw): array
    {
        if ($raw === null || $raw === '' || $raw === 'b:0;') return [];
        $decoded = null;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                try {
                    $tmp = unserialize($raw, ['allowed_classes' => false]);
                    if (is_array($tmp)) $decoded = $tmp;
                } catch (\Throwable $e) {
                    return [];
                }
            }
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }
        if (!is_array($decoded)) return [];
        $ids = [];
        foreach ($decoded as $m) {
            if (!is_array($m) || empty($m['kelas_mapel']) || !is_array($m['kelas_mapel'])) continue;
            foreach ($m['kelas_mapel'] as $km) {
                $kid = (int) ($km['kelas'] ?? 0);
                if ($kid > 0) $ids[] = $kid;
            }
        }
        return $ids;
    }

    /** Deteksi error unique-key (untuk retry upsert anti-balapan). */
    private static function isDuplicateKey(\Throwable $e): bool
    {
        $m = strtolower($e->getMessage() . ' ' . $e->getCode());
        return str_contains($m, 'duplicate') || str_contains($m, '1062')
            || str_contains($m, 'uq_ps') || str_contains($m, 'unique constraint');
    }

    /**
     * Hitung ringkasan harian dari daftar monitoring + agregat model.
     * "Belum" = seluruh siswa tanpa status HADIR/SAKIT/IZIN/ALPA/BDR
     * (mencakup baris TANPA_KETERANGAN maupun yang belum ada barisnya).
     * Kunci 'tanpa' (JSON) dan 'belum' (PDF) sengaja dua-duanya diisi.
     */
    private static function hitungStatHarian(array $data, ?object $stat): array
    {
        $totalSiswa = count($data);
        $hadir = (int) ($stat->hadir ?? 0);
        $sakit = (int) ($stat->sakit ?? 0);
        $izin  = (int) ($stat->izin ?? 0);
        $alpa  = (int) ($stat->alpa ?? 0);
        $bdr   = (int) ($stat->bdr ?? 0);
        $belum = max(0, $totalSiswa - $hadir - $sakit - $izin - $alpa - $bdr);
        return [
            'hadir' => $hadir, 'sakit' => $sakit, 'izin' => $izin,
            'alpa' => $alpa, 'bdr' => $bdr, 'tanpa' => $belum, 'belum' => $belum, 'total' => $totalSiswa,
        ];
    }

    /**
     * Cek apakah tanggal tersebut adalah hari libur (mingguan atau nasional).
     * Mengembalikan string keterangan libur, atau null jika hari kerja.
     * Sumber tunggal: App\Services\AttendancePolicy.
     */
    private function isHariLibur(string $tgl): ?string
    {
        try {
            return \App\Services\AttendancePolicy::alasanLibur($tgl);
        } catch (\Throwable $e) {
            // Biarkan null (tidak memblok)
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN UTAMA — input presensi harian
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        // Halaman input — batasi untuk pengelola & panitia (siswa tak boleh mengisi sendiri)
        if (!$this->canManageSiswa() && !$this->isPanitia()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $user = $this->getUser();
        $tp   = $this->dashboardModel->getTahunActive();
        $smt  = $this->dashboardModel->getSemesterActive();

        $kelass = ($tp && $smt)
            ? $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt)
            : [];

        // Guru non-piket hanya melihat kelasnya sendiri di dropdown
        $allowed = $this->allowedKelasIds();
        if ($allowed !== null) {
            $kelass = array_intersect_key($kelass, array_flip($allowed));
        }

        // Jam default "Semua Hadir" mengikuti /settings (absensi_setting),
        // dengan override jadwal per hari bila ada (sama seperti scan QR).
        $absenSet = $this->absenSettingModel->first();
        $jamMasukDef = self::jamPendek($absenSet->jam_masuk ?? null, '07:15');
        $jamPulangDef = self::jamPendek($absenSet->jam_pulang ?? null, '14:00');
        $jadwalMap = [];
        try {
            $dbSet = \Config\Database::connect();
            if ($dbSet->tableExists('absensi_jadwal_kerja')) {
                foreach ($dbSet->table('absensi_jadwal_kerja')->select('hari, jam_masuk, jam_pulang')->get()->getResult() as $j) {
                    $h = (int) ($j->hari ?? 0);
                    if ($h < 1 || $h > 7) {
                        continue;
                    }
                    $jadwalMap[$h] = [
                        'masuk'  => self::jamPendek($j->jam_masuk ?? null, $jamMasukDef),
                        'pulang' => self::jamPendek($j->jam_pulang ?? null, $jamPulangDef),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        return view('presensi_siswa/index', [
            'user'       => $user,
            'judul'      => 'Presensi Siswa',
            'subjudul'   => 'Kelola Absensi Harian Siswa',
            'setting'    => $this->dashboardModel->getSetting(),
            'tp_active'  => $tp,
            'smt_active' => $smt,
            'kelass'     => $kelass,
            'jam_masuk_default'  => $jamMasukDef,
            'jam_pulang_default' => $jamPulangDef,
            'jadwal_kerja_map'   => $jadwalMap,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Daftar siswa per kelas
    // ─────────────────────────────────────────────────────────────────────────

    public function getSiswa(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $idKelas = (int) $this->request->getPost('id_kelas');

        if (!$idKelas) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak lengkap']);
        }

        // TP/SMT diambil dari server (aktif), bukan dari POST client
        $tp  = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        if (!$tp || !$smt) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tahun Pelajaran / Semester aktif belum diset']);
        }

        $db = \Config\Database::connect();
        if (!$db->table('master_kelas')->where('id_kelas', $idKelas)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak ditemukan']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }

        $siswa = $this->presensiModel->getSiswaByKelas($idKelas, $tp->id_tp, $smt->id_smt);
        foreach ($siswa as $s) {
            $s->foto_url = self::resolveFotoSiswa($s->foto, $s->nis);
        }

        return $this->response->setJSON(['status' => true, 'data' => $siswa]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Data presensi yang sudah ada hari ini (untuk pre-fill form)
    // ─────────────────────────────────────────────────────────────────────────

    public function getPresensi(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $idKelas = (int) $this->request->getPost('id_kelas');
        $tgl     = $this->request->getPost('tgl');

        if (!$idKelas || !$this->validTgl($tgl)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak lengkap']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }

        // Grain harian tak butuh filter TP/SMT (satu baris per siswa per hari)
        $presensi = $this->presensiModel->getPresensiSiswa($idKelas, null, $tgl);

        return $this->response->setJSON(['status' => true, 'data' => $presensi]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Kelas yang BDR (belajar dari rumah) pada satu tanggal
    // Dipakai halaman input: saran status BDR bila kelas terjadwal tidak KBM
    // di sekolah (mis. ujian kelas XII meliburkan kelas X & XI).
    // ─────────────────────────────────────────────────────────────────────────

    public function getBdr(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $tgl = (string) $this->request->getPost('tgl');
        if (!$this->validTgl($tgl)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid']);
        }

        $peta = [];
        try {
            foreach ((new \App\Services\PresensiBdrService())->kelasTerdampak($tgl) as $idKelas => $evt) {
                $peta[(string) $idKelas] = trim((string) ($evt->judul ?? '')) ?: 'Belajar dari rumah';
            }
        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswa::getBdr] ' . $e->getMessage());
        }

        return $this->response->setJSON(['status' => true, 'data' => (object) $peta]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Simpan status kehadiran batch (upsert per siswa)
    // Dipakai untuk input massal status HADIR/SAKIT/IZIN/ALPA/TANPA_KETERANGAN/BDR
    // ─────────────────────────────────────────────────────────────────────────

    public function save(): \CodeIgniter\HTTP\ResponseInterface
    {
        // Panitia boleh input seperti di halaman index (hapus tetap admin/guru)
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $idKelas  = (int) $this->request->getPost('id_kelas');
        $tgl      = $this->request->getPost('tgl');
        $jamMasuk = $this->request->getPost('jam_masuk') ?: date('H:i:s');
        $presensi = $this->request->getPost('presensi');

        if (!$idKelas || !$this->validTgl($tgl) || empty($presensi) || !is_array($presensi)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak lengkap (kelas/tanggal tidak valid)']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        if ($tgl > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa mengisi presensi tanggal masa depan']);
        }
        if (!$this->validJam($jamMasuk)) $jamMasuk = date('H:i:s');
        $jamMasuk = $this->normJam($jamMasuk);
        $idGuru = $this->resolveGuruId();

        $db = \Config\Database::connect();

        // Hari libur: validasi di server. Scan QR sudah diblokir oleh
        // isHariLibur(), tapi POST langsung ke endpoint ini sebelumnya tetap
        // bisa mengisi HADIR pada hari libur — dan itu menggeser rekap
        // kehadiran, hitungan keterlambatan, serta poin disiplin siswa.
        // SAKIT / IZIN / ALPA tetap boleh: itu keterangan keadaan, bukan kehadiran.
        $isHariLibur = $this->isHariLibur($tgl);
        $liburLabel  = $isHariLibur ? $isHariLibur : null;

        $db->transBegin();

        $saved = 0;
        $skipped = 0;
        $ditolakLibur = 0;
        $savedIds = [];

        try {
            foreach ($presensi as $item) {
                $idSiswa    = (int) ($item['id_siswa'] ?? 0);
                $status     = strtoupper(trim($item['status'] ?? 'TANPA_KETERANGAN'));
                $keterangan = mb_substr(trim((string) ($item['keterangan'] ?? '')), 0, 500);
                $jamMasukItem  = trim((string) ($item['jam_masuk'] ?? ''));
                $jamKeluarItem = trim((string) ($item['jam_keluar'] ?? ''));

                if (!$idSiswa || !$this->isSiswaKelas($idSiswa, $idKelas)) {
                    $skipped++;
                    continue;
                }
                if (!in_array($status, self::VALID_STATUS, true)) {
                    $status = 'TANPA_KETERANGAN';
                }
                // Hari libur: HADIR dihitung sebagai hari kerja sehingga harus
                // ditolak. SAKIT/IZIN/ALPA tetap dicatat sebagai keterangan.
                if ($liburLabel !== null && in_array($status, ['HADIR', 'TANPA_KETERANGAN'], true)) {
                    $ditolakLibur++;
                    continue;
                }
                if ($jamMasukItem !== '' && !$this->validJam($jamMasukItem)) $jamMasukItem = '';
                if ($jamKeluarItem !== '' && !$this->validJam($jamKeluarItem)) $jamKeluarItem = '';
                if ($jamMasukItem !== '') $jamMasukItem = $this->normJam($jamMasukItem);
                if ($jamKeluarItem !== '') $jamKeluarItem = $this->normJam($jamKeluarItem);
                // Keluar tak boleh lebih awal dari masuk
                if ($jamMasukItem !== '' && $jamKeluarItem !== '' && $jamKeluarItem < $jamMasukItem) {
                    $jamKeluarItem = '';
                }

                // Satu baris per siswa per hari (UNIQUE id_siswa+tgl).
                // Maks 2x percobaan: bila insert tabrakan unique key karena
                // submit ganda bersamaan, baca ulang lalu update — bukan rollback.
                $done = false;
                for ($attempt = 0; $attempt < 2 && !$done; $attempt++) {
                    $existing = $db->table('presensi_siswa')
                        ->where('id_siswa', $idSiswa)
                        ->where('tgl', $tgl)
                        ->get()->getRow();

                    $rowData = [
                        'id_kelas'   => $idKelas,
                        'id_siswa'   => $idSiswa,
                        'id_mapel'   => null,
                        'id_guru'    => $idGuru,
                        'tgl'        => $tgl,
                        'status'     => $status,
                        'keterangan' => $keterangan !== '' ? $keterangan : null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    try {
                        if ($status === 'HADIR' && $db->fieldExists('sumber_masuk', 'presensi_siswa')) {
                            $rowData['sumber_masuk'] = 'manual';
                        }
                    } catch (\Throwable $e) {
                    }

                    if ($existing) {
                        if (in_array($status, self::STATUS_TANPA_JAM, true)) {
                            // Status berizin tak menyimpan jam kehadiran
                            $rowData['jam_masuk'] = null;
                            $rowData['jam_keluar'] = null;
                        } else {
                            // Jangan timpa jam yang sudah terisi; baris TANPA_KETERANGAN tanpa jam
                            if (empty($existing->jam_masuk)) {
                                $rowData['jam_masuk'] = $status === 'HADIR'
                                    ? ($jamMasukItem !== '' ? $jamMasukItem : $jamMasuk)
                                    : ($jamMasukItem !== '' && $status !== 'TANPA_KETERANGAN' ? $jamMasukItem : null);
                            }
                            if (empty($existing->jam_keluar) && $jamKeluarItem !== '' && $status === 'HADIR') {
                                $rowData['jam_keluar'] = $jamKeluarItem;
                            }
                        }
                        $db->table('presensi_siswa')->where('id', $existing->id)->update($rowData);
                        $savedIds[] = (int) $existing->id;
                        $done = true;
                    } else {
                        $rowData['jam_masuk'] = $status === 'HADIR'
                            ? ($jamMasukItem !== '' ? $jamMasukItem : $jamMasuk)
                            : null;
                        $rowData['jam_keluar'] = ($status === 'HADIR' && $jamKeluarItem !== '') ? $jamKeluarItem : null;
                        $rowData['created_at']  = date('Y-m-d H:i:s');
                        try {
                            $db->table('presensi_siswa')->insert($rowData);
                            $savedIds[] = (int) $db->insertID();
                            $done = true;
                        } catch (\Throwable $ie) {
                            if (!self::isDuplicateKey($ie)) throw $ie;
                            // Balapan insert: iterasi berikut menemukan barisnya lalu update
                        }
                    }
                }
                if (!$done) {
                    throw new \RuntimeException('Gagal menyimpan presensi siswa ' . $idSiswa . ' (konflik tulis)');
                }
                $saved++;
            }

            $db->transCommit();

            // Poin dan denda dihitung setelah commit supaya keduanya tidak
            // berdiri di atas data yang belum benar-benar tersimpan.
            foreach ($savedIds as $sid) {
                \App\Services\SiswaPoinService::sinkron($sid);
                \App\Services\SiswaDendaService::sinkron($sid);
            }

            $msg = "Presensi {$saved} siswa berhasil disimpan";
            if ($skipped > 0) $msg .= " ({$skipped} dilewati: bukan anggota kelas aktif)";
            if ($ditolakLibur > 0) $msg .= " ({$ditolakLibur} ditolak: {$liburLabel})";
            return $this->response->setJSON([
                'status'   => true,
                'message'  => $msg,
                'saved'    => $saved,
                'skipped'  => $skipped,
                'ditolak_libur' => $ditolakLibur,
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[PresensiSiswa::save] ' . $e->getMessage());
            // Detail exception hanya ke log — pesan mentah bisa memuat SQL/struktur data.
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan presensi — tidak ada yang tersimpan, coba lagi atau periksa log aplikasi.']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Check-In satu siswa (catat jam_masuk)
    // POST: id_siswa, id_kelas, tgl, jam_masuk, id_guru
    // ─────────────────────────────────────────────────────────────────────────

    public function checkIn(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $idSiswa  = (int) $this->request->getPost('id_siswa');
        $idKelas  = (int) $this->request->getPost('id_kelas');
        $tgl      = $this->request->getPost('tgl')       ?: date('Y-m-d');
        $jamMasuk = $this->request->getPost('jam_masuk') ?: date('H:i:s');

        if (!$idSiswa || !$idKelas) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak lengkap']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        if (!$this->validTgl($tgl) || $tgl > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid']);
        }
        if (!$this->validJam($jamMasuk)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam tidak valid']);
        }
        $jamMasuk = $this->normJam($jamMasuk);
        if (!$this->isSiswaKelas($idSiswa, $idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Siswa bukan anggota kelas ini']);
        }
        $idGuru = $this->resolveGuruId();

        // Cegah check-in pada hari libur (mingguan/nasional)
        $liburMsg = $this->isHariLibur($tgl);
        if ($liburMsg) {
            return $this->response->setJSON(['status' => false, 'message' => $liburMsg]);
        }

        $db       = \Config\Database::connect();
        // Grain unik = (id_siswa, tgl): cari tanpa filter kelas agar baris yang
        // tercipta di kelas lain (siswa pindah kelas) tetap ketemu lalu
        // dikembalikan ke kelas aktif — selaras checkOut()/scanQr().
        $existing = $db->table('presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->where('tgl', $tgl)
            ->get()->getRow();

        try {
            if ($existing) {
                if (!empty($existing->jam_masuk)) {
                    return $this->response->setJSON([
                        'status'  => false,
                        'message' => 'Siswa sudah check-in pada ' . substr($existing->jam_masuk, 0, 5),
                    ]);
                }
                if (in_array($existing->status, ['SAKIT', 'IZIN', 'ALPA'], true)) {
                    return $this->response->setJSON([
                        'status'  => false,
                        'message' => 'Siswa berstatus ' . $existing->status . '. Ubah status dulu via Simpan Absensi.',
                    ]);
                }
                $manualIn = ['jam_masuk' => $jamMasuk, 'status' => 'HADIR', 'updated_at' => date('Y-m-d H:i:s')];
                if ($idGuru) $manualIn['id_guru'] = $idGuru;
                // Pindah kelas: kembalikan baris ke kelas aktif yang diminta.
                if ((int) ($existing->id_kelas ?? 0) !== $idKelas) $manualIn['id_kelas'] = $idKelas;
                try {
                    if ($db->fieldExists('sumber_masuk', 'presensi_siswa')) $manualIn['sumber_masuk'] = 'manual';
                    $ipM = (string) $this->request->getIPAddress();
                    if ($ipM !== '' && $db->fieldExists('ip_masuk', 'presensi_siswa')) $manualIn['ip_masuk'] = substr($ipM, 0, 45);
                } catch (\Throwable $e) {
                }
                // Guard idempoten: update hanya bila jam_masuk masih kosong,
                // agar submit paralel tidak menulis ganda lalu sinkron poin ganda.
                $db->table('presensi_siswa')
                    ->where('id', $existing->id)
                    ->where('jam_masuk IS NULL', null, false)
                    ->update($manualIn);
                if ($db->affectedRows() === 0) {
                    $finalIn = $db->table('presensi_siswa')->select('jam_masuk')->where('id', $existing->id)->get()->getRow();
                    return $this->response->setJSON([
                        'status'  => false,
                        'message' => 'Siswa sudah check-in pada ' . substr((string) ($finalIn->jam_masuk ?? $jamMasuk), 0, 5),
                    ]);
                }
                \App\Services\SiswaPoinService::sinkron((int) $existing->id);
                \App\Services\SiswaDendaService::sinkron((int) $existing->id);
            } else {
                try {
                    $rowIn = [
                        'id_kelas'   => $idKelas,
                        'id_siswa'   => $idSiswa,
                        'id_mapel'   => null,
                        'id_guru'    => $idGuru,
                        'tgl'        => $tgl,
                        'jam_masuk'  => $jamMasuk,
                        'status'     => 'HADIR',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    try {
                        if ($db->fieldExists('sumber_masuk', 'presensi_siswa')) $rowIn['sumber_masuk'] = 'manual';
                        $ipM2 = (string) $this->request->getIPAddress();
                        if ($ipM2 !== '' && $db->fieldExists('ip_masuk', 'presensi_siswa')) $rowIn['ip_masuk'] = substr($ipM2, 0, 45);
                    } catch (\Throwable $e) {
                    }
                    $db->table('presensi_siswa')->insert($rowIn);
                } catch (\Exception $e) {
                    // Balapan insert ganda (unique key) → anggap sudah ada
                    $m = strtolower($e->getMessage() . ' ' . $e->getCode());
                    if (str_contains($m, 'duplicate') || str_contains($m, 'uq_ps') || str_contains($m, '1062')) {
                        return $this->response->setJSON(['status' => false, 'message' => 'Data sudah dicatat (duplikat dicegah)']);
                    }
                    throw $e;
                }
                \App\Services\SiswaPoinService::sinkron((int) $db->insertID());
                \App\Services\SiswaDendaService::sinkron((int) $db->insertID());
            }

            return $this->response->setJSON([
                'status'    => true,
                'message'   => 'Check-in berhasil pukul ' . substr($jamMasuk, 0, 5),
                'jam_masuk' => substr($jamMasuk, 0, 5),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[PresensiSiswa::checkIn] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Terjadi kesalahan server']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Check-Out satu siswa (catat jam_keluar)
    // POST: id_siswa, id_kelas, tgl, jam_keluar
    // ─────────────────────────────────────────────────────────────────────────

    public function checkOut(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $idSiswa   = (int) $this->request->getPost('id_siswa');
        $idKelas   = (int) $this->request->getPost('id_kelas');
        $tgl       = $this->request->getPost('tgl')        ?: date('Y-m-d');
        $jamKeluar = $this->request->getPost('jam_keluar') ?: date('H:i:s');

        if (!$idSiswa || !$idKelas) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak lengkap']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        if (!$this->validTgl($tgl) || $tgl > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid']);
        }
        if (!$this->validJam($jamKeluar)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam keluar tidak valid']);
        }
        $jamKeluar = $this->normJam($jamKeluar);

        // Sama seperti checkIn(): jangan isi jam pada hari libur. Baris
        // presence di hari libur bisa saja berasal dari data lama sebelum
        // validasi ini ada, jadi penutupannya tidak cukup hanya di check-in.
        $liburMsg = $this->isHariLibur($tgl);
        if ($liburMsg) {
            return $this->response->setJSON(['status' => false, 'message' => $liburMsg]);
        }

        $db       = \Config\Database::connect();
        // Grain unik = (id_siswa, tgl): cari tanpa filter kelas agar baris
        // yang tercatat di kelas lain tetap ketemu, lalu selaraskan kelasnya.
        $existing = $db->table('presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->where('tgl', $tgl)
            ->get()->getRow();

        if (!$this->isSiswaKelas($idSiswa, $idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Siswa bukan anggota kelas ini']);
        }

        if (!$existing) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Siswa belum check-in hari ini',
            ]);
        }

        if ($existing->status !== 'HADIR') {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Absen Pulang hanya untuk siswa berstatus HADIR (saat ini: ' . $existing->status . ')',
            ]);
        }

        if (empty($existing->jam_masuk)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Jam masuk belum tercatat',
            ]);
        }

        if (!empty($existing->jam_keluar)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Siswa sudah check-out pada ' . substr($existing->jam_keluar, 0, 5),
            ]);
        }

        if ($jamKeluar < substr($existing->jam_masuk, 0, 8)) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Jam keluar tidak boleh lebih awal dari jam masuk (' . substr($existing->jam_masuk, 0, 5) . ')',
            ]);
        }

        try {
            $manualOut = ['jam_keluar' => $jamKeluar, 'updated_at' => date('Y-m-d H:i:s')];
            // Pindah kelas: kembalikan baris ke kelas aktif yang diminta.
            if ((int) ($existing->id_kelas ?? 0) !== $idKelas) $manualOut['id_kelas'] = $idKelas;
            try {
                if ($db->fieldExists('sumber_keluar', 'presensi_siswa')) $manualOut['sumber_keluar'] = 'manual';
                $ipO = (string) $this->request->getIPAddress();
                if ($ipO !== '' && $db->fieldExists('ip_keluar', 'presensi_siswa')) $manualOut['ip_keluar'] = substr($ipO, 0, 45);
                // Koreksi manual melengkapi pulang yang tadi ditandai cron.
                if ($db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) $manualOut['pulang_tidak_tercatat'] = 0;
            } catch (\Throwable $e) {
            }
            // Guard idempoten: jangan timpa jam_keluar yang sudah terisi
            // (double-submit/check-out paralel) — update hanya bila masih kosong.
            $db->table('presensi_siswa')
                ->where('id', $existing->id)
                ->where('jam_keluar IS NULL', null, false)
                ->update($manualOut);
            if ($db->affectedRows() === 0) {
                return $this->response->setJSON([
                    'status'  => false,
                    'message' => 'Siswa sudah check-out (dicegah saat menyimpan)',
                ]);
            }
            \App\Services\SiswaPoinService::sinkron((int) $existing->id);
            \App\Services\SiswaDendaService::sinkron((int) $existing->id);

            return $this->response->setJSON([
                'status'     => true,
                'message'    => 'Absen Pulang berhasil pukul ' . substr($jamKeluar, 0, 5),
                'jam_keluar' => substr($jamKeluar, 0, 5),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[PresensiSiswa::checkOut] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Terjadi kesalahan server']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Update jam masuk/keluar satu siswa secara manual
    // POST: id_siswa, id_kelas, tgl, jam_masuk (opsional), jam_keluar (opsional)
    // ─────────────────────────────────────────────────────────────────────────

    public function updateJam(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $idSiswa   = (int) $this->request->getPost('id_siswa');
        $idKelas   = (int) $this->request->getPost('id_kelas');
        $tgl       = $this->request->getPost('tgl');
        $jamMasuk  = $this->request->getPost('jam_masuk');
        $jamKeluar = $this->request->getPost('jam_keluar');

        if (!$idSiswa || !$idKelas || !$this->validTgl($tgl)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Parameter tidak lengkap']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        if ($tgl > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid']);
        }
        if (($jamMasuk === null || $jamMasuk === '') && ($jamKeluar === null || $jamKeluar === '')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada jam yang diubah']);
        }
        if ($jamMasuk !== null && $jamMasuk !== '' && !$this->validJam($jamMasuk)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam masuk tidak valid']);
        }
        if ($jamKeluar !== null && $jamKeluar !== '' && !$this->validJam($jamKeluar)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format jam keluar tidak valid']);
        }

        // Koreksi jam pada hari libur ditolak. Endpoint ini bukan sekadar
        // mengubah angka: ia juga mempromosikan baris TANPA_KETERANGAN
        // menjadi HADIR (lihat di bawah), jadi lewat sini kehadiran bisa
        // dibuat pada hari yang tidak seharusnya ada kehadiran.
        // Kalau memang ada kegiatan pada hari yang kini ditandai libur,
        // ubah dulu status liburnya di /settings, baru koreksi datanya.
        $liburMsg = $this->isHariLibur($tgl);
        if ($liburMsg) {
            return $this->response->setJSON(['status' => false, 'message' => $liburMsg]);
        }

        $db       = \Config\Database::connect();
        // Grain unik = (id_siswa, tgl): cari tanpa filter kelas agar baris
        // yang tercatat di kelas lain tetap ketemu, lalu selaraskan kelasnya.
        $existing = $db->table('presensi_siswa')
            ->where('id_siswa', $idSiswa)
            ->where('tgl', $tgl)
            ->get()->getRow();

        if (!$this->isSiswaKelas($idSiswa, $idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Siswa bukan anggota kelas ini']);
        }

        if (!$existing) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data presensi tidak ditemukan']);
        }

        if (in_array($existing->status, ['SAKIT', 'IZIN', 'ALPA'], true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak bisa mengatur jam untuk status ' . $existing->status]);
        }

        $effMasuk  = ($jamMasuk !== null && $jamMasuk !== '') ? $this->normJam($jamMasuk) : substr((string) ($existing->jam_masuk ?? ''), 0, 8);
        $effKeluar = ($jamKeluar !== null && $jamKeluar !== '') ? $this->normJam($jamKeluar) : substr((string) ($existing->jam_keluar ?? ''), 0, 8);
        if ($effMasuk !== '' && $effKeluar !== '' && $effKeluar < $effMasuk) {
            return $this->response->setJSON(['status' => false, 'message' => 'Jam keluar tidak boleh lebih awal dari jam masuk']);
        }
        // Jam keluar tanpa jam masuk = state yatim: tolak, isi masuk dulu.
        if ($effMasuk === '' && $effKeluar !== '') {
            return $this->response->setJSON(['status' => false, 'message' => 'Isi jam masuk dulu sebelum jam keluar']);
        }

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        // Pindah kelas: kembalikan baris ke kelas aktif yang diminta.
        if ((int) ($existing->id_kelas ?? 0) !== $idKelas) $updateData['id_kelas'] = $idKelas;

        if ($jamMasuk !== null && $jamMasuk !== '') {
            $updateData['jam_masuk'] = $this->normJam($jamMasuk);
        }
        if ($jamKeluar !== null && $jamKeluar !== '') {
            $updateData['jam_keluar'] = $this->normJam($jamKeluar);
        }

        // Isi jam masuk pada baris TANPA_KETERANGAN = kehadiran → promosikan ke HADIR
        if ($existing->status === 'TANPA_KETERANGAN' && isset($updateData['jam_masuk'])) {
            $updateData['status'] = 'HADIR';
        }

        try {
            $db->table('presensi_siswa')->where('id', $existing->id)->update($updateData);

            // Koreksi jam bisa mengubah status keterlambatan → poin dihitung ulang.
            \App\Services\SiswaPoinService::sinkron((int) $existing->id);
            \App\Services\SiswaDendaService::sinkron((int) $existing->id);

            return $this->response->setJSON(['status' => true, 'message' => 'Jam berhasil diperbarui', 'promoted' => ($updateData['status'] ?? null) === 'HADIR']);
        } catch (\Exception $e) {
            log_message('error', '[PresensiSiswa::updateJam] ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Terjadi kesalahan server']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Rekap bulanan per kelas
    // ─────────────────────────────────────────────────────────────────────────

    public function rekapBulanan(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $idKelas = (int) $this->request->getPost('id_kelas');
        $bulan   = (int) $this->request->getPost('bulan');
        $tahun   = (int) $this->request->getPost('tahun');
        $curYear = (int) date('Y');

        if (!$idKelas) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas wajib dipilih']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        if ($bulan < 1 || $bulan > 12 || $tahun < attendance_tahun_tertua() || $tahun > $curYear + 1) {
            return $this->response->setJSON(['status' => false, 'message' => 'Periode tidak valid']);
        }

        $rekap = $this->presensiModel->getRekapBulanan($idKelas, $bulan, $tahun);

        return $this->response->setJSON(['status' => true, 'data' => $rekap]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Hapus satu record presensi
    // ─────────────────────────────────────────────────────────────────────────

    public function delete(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || !$this->canManageSiswa()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Unauthorized']);
        }

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID tidak ditemukan']);
        }

        $db  = \Config\Database::connect();
        $row = $db->table('presensi_siswa')->where('id', $id)->get()->getRow();
        if (!$row) {
            return $this->response->setJSON(['status' => false, 'message' => 'Data tidak ditemukan']);
        }
        // Sanity: baris harus menunjuk ke kelas yang benar-benar ada
        if (!$db->table('master_kelas')->where('id_kelas', $row->id_kelas)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas pada data tidak valid']);
        }
        if (!$this->kelasAllowed((int) $row->id_kelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        $deleted = $db->table('presensi_siswa')->delete(['id' => $id]);

        if ($deleted) {
            // Pengurangan poin otomatis ikut dicabut agar tidak ada poin yatim.
            \App\Services\SiswaPoinService::batalkan($id);
        }

        return $this->response->setJSON([
            'status'  => (bool) $deleted,
            'message' => $deleted ? 'Data berhasil dihapus' : 'Gagal menghapus data',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN: Monitoring harian siswa
    // ─────────────────────────────────────────────────────────────────────────

    public function monitoringHarian(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        // Monitoring memuat NIS/foto seluruh siswa — batasi untuk pengelola & panitia
        if (!$this->canManageSiswa() && !$this->isPanitia()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp  = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $tgl = $this->request->getGet('tgl');
        if (!$this->validTgl($tgl) || $tgl > date('Y-m-d')) {
            $tgl = date('Y-m-d');
        }

        $kelass = ($tp && $smt)
            ? $this->dropdownModel->getAllKelas($tp->id_tp, $smt->id_smt)
            : [];

        $allowed = $this->allowedKelasIds();
        if ($allowed !== null) {
            $kelass = array_intersect_key($kelass, array_flip($allowed));
        }

        return view('presensi_siswa/monitoring', [
            'user'       => $this->getUser(),
            'judul'      => 'Monitoring Presensi Siswa',
            'subjudul'   => 'Pantau kehadiran siswa harian',
            'setting'    => $this->dashboardModel->getSetting(),
            'tp_active'  => $tp,
            'smt_active' => $smt,
            'kelass'     => $kelass,
            'tgl'        => $tgl,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Data monitoring harian per kelas + tanggal
    // ─────────────────────────────────────────────────────────────────────────

    public function getMonitoringHarian(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $idKelas = (int) $this->request->getPost('id_kelas');
        $tgl     = $this->request->getPost('tgl') ?: date('Y-m-d');

        if (!$idKelas) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas wajib dipilih']);
        }
        if (!$this->kelasAllowed($idKelas)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke kelas ini']);
        }
        if (!$this->validTgl($tgl) || $tgl > date('Y-m-d')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tanggal tidak valid']);
        }

        // TP/SMT diambil dari server (aktif), bukan dari POST client
        $tp  = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $idTp  = $tp?->id_tp;
        $idSmt = $smt?->id_smt;

        $db = \Config\Database::connect();
        // Pastikan kelas benar-benar ada (cegah id ngawur)
        if (!$db->table('master_kelas')->where('id_kelas', $idKelas)->countAllResults()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak ditemukan']);
        }

        $data = $this->presensiModel->getMonitoringHarian($idKelas, $tgl, $idTp, $idSmt);
        foreach ($data as $row) {
            $row->foto_url = self::resolveFotoSiswa($row->foto, $row->nis);
        }

        $stat = $this->presensiModel->getStatHarian($idKelas, $tgl);

        return $this->response->setJSON([
            'status' => true,
            'data'   => $data,
            'stat'   => self::hitungStatHarian($data, $stat),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF: Monitoring harian per kelas + tanggal (unduh otomatis)
    // GET: id_kelas, tgl
    // ─────────────────────────────────────────────────────────────────────────

    public function monitoringHarianPdf()
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $idKelas = (int) ($this->request->getGet('id_kelas') ?? 0);
        $tgl = $this->request->getGet('tgl') ?: date('Y-m-d');
        if (!$idKelas || !$this->validTgl($tgl) || $tgl > date('Y-m-d')) {
            return redirect()->to('admin/presensi/siswa/harian')->with('error', 'Pilih kelas dan tanggal yang valid terlebih dahulu');
        }
        if (!$this->kelasAllowed($idKelas)) {
            return redirect()->to('admin/presensi/siswa/harian')->with('error', 'Anda tidak memiliki akses ke kelas ini');
        }

        $tp  = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $db = \Config\Database::connect();
        $kelasRow = $db->table('master_kelas')->where('id_kelas', $idKelas)->get()->getRow();
        if (!$kelasRow) {
            return redirect()->to('admin/presensi/siswa/harian')->with('error', 'Kelas tidak ditemukan');
        }

        $data = $this->presensiModel->getMonitoringHarian($idKelas, $tgl, $tp?->id_tp, $smt?->id_smt);
        $stat = $this->presensiModel->getStatHarian($idKelas, $tgl);

        $html = view('presensi_siswa/monitoring_pdf', [
            'setting'   => $this->dashboardModel->getSetting() ?: (object) ['nama_sekolah' => 'Sekolah'],
            'tgl'       => $tgl,
            'kelasNama' => $kelasRow->nama_kelas ?? ('Kelas ' . $idKelas),
            'data'      => $data,
            'stat'      => self::hitungStatHarian($data, $stat),
        ]);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $kelasClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kelasRow->nama_kelas ?? ('Kelas_' . $idKelas));
        $filename = "Monitoring_Siswa_{$kelasClean}_{$tgl}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    public static function resolveFotoSiswa(?string $foto, ?string $nis): ?string
    {
        $foto = trim((string) $foto);
        $nis  = trim((string) $nis);

        if ($foto !== '' && !in_array(strtolower($foto), ['siswa.png', 'default.png', 'null'], true)) {
            $cleanFoto = ltrim($foto, '/');
            if (file_exists(FCPATH . $cleanFoto) && is_file(FCPATH . $cleanFoto)) {
                return base_url($cleanFoto);
            }
            if (file_exists(FCPATH . 'uploads/foto_siswa/' . $cleanFoto) && is_file(FCPATH . 'uploads/foto_siswa/' . $cleanFoto)) {
                return base_url('uploads/foto_siswa/' . $cleanFoto);
            }
            if (file_exists(FCPATH . 'uploads/foto_siswa/' . $cleanFoto . '.png') && is_file(FCPATH . 'uploads/foto_siswa/' . $cleanFoto . '.png')) {
                return base_url('uploads/foto_siswa/' . $cleanFoto . '.png');
            }
            if (file_exists(FCPATH . 'uploads/foto_siswa/' . $cleanFoto . '.jpg') && is_file(FCPATH . 'uploads/foto_siswa/' . $cleanFoto . '.jpg')) {
                return base_url('uploads/foto_siswa/' . $cleanFoto . '.jpg');
            }
        }

        if ($nis !== '') {
            if (file_exists(FCPATH . 'uploads/foto_siswa/_' . $nis . '.png') && is_file(FCPATH . 'uploads/foto_siswa/_' . $nis . '.png')) {
                return base_url('uploads/foto_siswa/_' . $nis . '.png');
            }
            if (file_exists(FCPATH . 'uploads/foto_siswa/' . $nis . '.png') && is_file(FCPATH . 'uploads/foto_siswa/' . $nis . '.png')) {
                return base_url('uploads/foto_siswa/' . $nis . '.png');
            }
            if (file_exists(FCPATH . 'uploads/foto_siswa/' . $nis . '.jpg') && is_file(FCPATH . 'uploads/foto_siswa/' . $nis . '.jpg')) {
                return base_url('uploads/foto_siswa/' . $nis . '.jpg');
            }
            if (file_exists(FCPATH . 'uploads/foto_siswa/' . $nis . '.jpeg') && is_file(FCPATH . 'uploads/foto_siswa/' . $nis . '.jpeg')) {
                return base_url('uploads/foto_siswa/' . $nis . '.jpeg');
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN REKAP PRESENSI BULANAN SISWA
    // Menampilkan rekapitulasi presensi bulanan siswa seperti guru di /admin/presensi/rekap
    // ─────────────────────────────────────────────────────────────────────────

    public function rekapBulananView(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->canManageSiswa() && !$this->isPanitia()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $data = $this->getRekapBulananData($bulan, $tahun, $this->idKelasRekapParam());
        if (($data['allowedKelas'] ?? null) === []) {
            return redirect()->to('dashboard')->with('error', 'Akun ini belum memiliki penugasan kelas.');
        }

        $data['user']     = $this->getUser();
        $data['judul']    = 'Rekap Presensi Siswa';
        $data['subjudul'] = 'Rekapitulasi Kehadiran Siswa Bulanan';
        $data['title']    = 'Rekap Presensi Bulanan Siswa';

        return view('admin/presensi/rekap_siswa', $data);
    }

    /**
     * Parameter id_kelas: null = belum dipilih (pakai kelas pertama),
     * 0 = eksplisit "Semua Kelas", >0 = kelas tertentu.
     */
    private function idKelasRekapParam(): ?int
    {
        $raw = $this->request->getGet('id_kelas');
        if ($raw === null || $raw === '') {
            return null;
        }
        return max(0, (int) $raw);
    }

    /**
     * Export Rekap Bulanan Siswa ke format Microsoft Excel (.xls)
     */
    public function exportRekapExcel(): \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $data = $this->getRekapBulananData($bulan, $tahun, $this->idKelasRekapParam());
        if (($data['allowedKelas'] ?? null) === []) {
            return redirect()->to('dashboard')->with('error', 'Akun ini belum memiliki penugasan kelas.');
        }

        $data['user'] = $this->getUser();
        $namaKelasClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['kelasInfo']->nama_kelas ?? 'Semua_Kelas');
        $filename = "Rekap_Presensi_Siswa_{$namaKelasClean}_{$data['bulan']}-{$data['tahun']}.xls";

        $body = view('admin/presensi/rekap_siswa_excel', $data);

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($body);
    }

    /**
     * Export Rekap Bulanan Siswa ke format PDF (Dompdf A4 Landscape)
     */
    public function exportRekapPdf(): \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $data = $this->getRekapBulananData($bulan, $tahun, $this->idKelasRekapParam());

        // Guru tanpa satu pun penugasan kelas tidak boleh jatuh ke rekap
        // semua kelas (id_kelas = 0 berarti seluruh kelas yang diizinkan).
        if (($data['allowedKelas'] ?? null) === []) {
            return redirect()->to('dashboard')->with('error', 'Akun ini belum memiliki penugasan kelas.');
        }

        $data['user']  = $this->getUser();
        $data['title'] = 'Rekap Presensi Siswa ' . ($data['kelasInfo']->nama_kelas ?? 'Semua Kelas')
            . ' - ' . $data['bulan'] . '/' . $data['tahun'];

        $html = view('admin/presensi/rekap_siswa_pdf', $data);

        $dompdf = new Dompdf();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $namaKelasClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['kelasInfo']->nama_kelas ?? 'Semua_Kelas');
        $filename = "Rekap_Presensi_Siswa_{$namaKelasClean}_{$bulan}-{$tahun}.pdf";
        $disposition = $this->request->getGet('download') ? 'attachment' : 'inline';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    /**
     * Daftar kelas yang boleh dipakai untuk rekap bulanan pengguna ini.
     * Dipakai dropdown kelas di modal "Rekap PDF" halaman mobile guru.
     */
    public function kelasRekapOptions(): \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isLoggedIn() || (!$this->canManageSiswa() && !$this->isPanitia())) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => false,
                'message' => 'Akses ditolak',
            ]);
        }

        $hasBukuInduk = false;
        try {
            $hasBukuInduk = \Config\Database::connect()->tableExists('buku_induk');
        } catch (\Throwable $e) {
            $hasBukuInduk = false;
        }

        $tp   = $this->dashboardModel->getTahunActive();
        $smt  = $this->dashboardModel->getSemesterActive();
        $idTp = $tp->id_tp ?? 1;
        $idSmt = $smt->id_smt ?? 1;

        [$kelases] = $this->daftarKelasRekap($hasBukuInduk, $idTp, $idSmt);

        $items = [];
        foreach ($kelases as $k) {
            $items[] = [
                'id'     => (int) $k->id_kelas,
                'nama'   => (string) $k->nama_kelas,
                'jumlah' => (int) ($k->jumlah_siswa ?? 0),
            ];
        }

        return $this->response->setJSON(['status' => true, 'kelas' => $items]);
    }

    /**
     * Daftar kelas aktif + batas akses guru untuk rekap bulanan.
     *
     * @return array{0: array<int, object>, 1: array<int, int>|null}
     */
    private function daftarKelasRekap(bool $hasBukuInduk, int $idTp, int $idSmt): array
    {
        $db = \Config\Database::connect();

        if ($hasBukuInduk) {
            $kelases = $db->table('master_kelas mk')
                ->select('mk.id_kelas, mk.nama_kelas, mk.kode_kelas, COUNT(DISTINCT CASE WHEN bi.id_siswa IS NULL OR bi.status = \'1\' THEN ks.id_siswa END) as jumlah_siswa', false)
                ->join("kelas_siswa ks", "ks.id_kelas = mk.id_kelas AND ks.id_tp = {$idTp} AND ks.id_smt = {$idSmt}", 'left')
                ->join('buku_induk bi', 'bi.id_siswa = ks.id_siswa', 'left')
                ->groupBy('mk.id_kelas, mk.nama_kelas, mk.kode_kelas')
                ->orderBy('mk.nama_kelas', 'ASC')
                ->get()->getResult();
        } else {
            $kelases = $db->table('master_kelas mk')
                ->select('mk.id_kelas, mk.nama_kelas, mk.kode_kelas, COUNT(ks.id_siswa) as jumlah_siswa')
                ->join("kelas_siswa ks", "ks.id_kelas = mk.id_kelas AND ks.id_tp = {$idTp} AND ks.id_smt = {$idSmt}", 'left')
                ->groupBy('mk.id_kelas, mk.nama_kelas, mk.kode_kelas')
                ->orderBy('mk.nama_kelas', 'ASC')
                ->get()->getResult();
        }

        $allowed = $this->allowedKelasIds();
        if ($allowed !== null) {
            $kelases = array_values(array_filter($kelases, fn($k) => in_array((int) $k->id_kelas, $allowed, true)));
        }

        return [$kelases, $allowed];
    }

    /**
     * Helper untuk mempersiapkan data rekapitulasi bulanan siswa
     */
    private function getRekapBulananData(?string $bulan, ?string $tahun, ?int $idKelas = null): array
    {
        $db = \Config\Database::connect();

        // Validasi periode: nilai cacat (mis. bulan=abc) tidak boleh menghasilkan
        // rentang tanggal "0000-00" yang membuat semua sel tampak alpha.
        $bulanInt = (int) ($bulan ?? 0);
        if ($bulanInt < 1 || $bulanInt > 12) {
            $bulanInt = (int) date('n');
        }
        $tahunInt = (int) ($tahun ?? 0);
        if ($tahunInt < 2000 || $tahunInt > 2100) {
            $tahunInt = (int) date('Y');
        }
        $bulan = sprintf('%02d', $bulanInt);
        $tahun = (string) $tahunInt;

        $tp   = $this->dashboardModel->getTahunActive();
        $smt  = $this->dashboardModel->getSemesterActive();
        $idTp = $tp->id_tp ?? 1;
        $idSmt = $smt->id_smt ?? 1;

        // Ambil daftar kelas dengan jumlah siswa AKTIF saja
        // (buku_induk.status = '1'; LULUS/PINDAH/KELUAR tidak dihitung).
        $hasBukuInduk = false;
        try {
            $hasBukuInduk = $db->tableExists('buku_induk');
        } catch (\Throwable $e) {
            $hasBukuInduk = false;
        }
        [$kelases, $allowed] = $this->daftarKelasRekap($hasBukuInduk, $idTp, $idSmt);

        // Scoping guru: hanya kelas sendiri yang terdaftar. Permintaan kelas
        // milik guru lain jatuh ke "semua kelas yang diizinkan", bukan ke
        // seluruh sekolah.
        if ($allowed !== null && $idKelas !== null && $idKelas > 0 && !in_array($idKelas, $allowed, true)) {
            $idKelas = 0;
        }

        // Admin/panitia: id_kelas yang tidak dikenal diperlakukan sebagai
        // "belum memilih" agar tidak menampilkan tabel kosong dengan label
        // "Semua Kelas".
        if ($allowed === null && $idKelas !== null && $idKelas > 0) {
            $validKelasIds = array_map(static fn($k) => (int) $k->id_kelas, $kelases);
            if (!in_array($idKelas, $validKelasIds, true)) {
                $idKelas = null;
            }
        }

        // Belum ada pilihan (kunjungan pertama / tautan tanpa id_kelas) →
        // pakai kelas pertama yang punya siswa agar tabel tidak langsung
        // memuat seluruh sekolah. id_kelas=0 yang eksplisit tetap "Semua Kelas".
        if ($idKelas === null) {
            $idKelas = 0;
            if (!empty($kelases)) {
                foreach ($kelases as $k) {
                    if ((int) ($k->jumlah_siswa ?? 0) > 0) {
                        $idKelas = (int) $k->id_kelas;
                        break;
                    }
                }
                if ($idKelas === 0) {
                    $idKelas = (int) $kelases[0]->id_kelas;
                }
            }
        }

        // Kelas yang masuk cakupan rekap ini (dipakai untuk cakupan BDR dan
        // pembagi hari efektif per kelas).
        if ($idKelas > 0) {
            $scopeKelasIds = [$idKelas];
        } elseif ($allowed !== null) {
            $scopeKelasIds = $allowed;
        } else {
            $scopeKelasIds = array_map(static fn($k) => (int) $k->id_kelas, $kelases);
        }
        $scopeKelasIds = array_values(array_unique(array_filter($scopeKelasIds)));

        // Info kelas terpilih
        $kelasInfo = null;
        if ($idKelas > 0) {
            foreach ($kelases as $k) {
                if ((int)$k->id_kelas === $idKelas) {
                    $kelasInfo = $k;
                    break;
                }
            }
            if (!$kelasInfo) {
                $kelasInfo = $db->table('master_kelas')->where('id_kelas', $idKelas)->get()->getRow();
            }
        }

        // Cari wali kelas jika ada
        $waliKelas = null;
        if ($idKelas > 0) {
            $waliRow = $db->table('jabatan_guru jg')
                ->select('mg.nama_guru, mg.nip')
                ->join('master_guru mg', 'mg.id_guru = jg.id_guru')
                ->where('jg.id_kelas', $idKelas)
                ->where('jg.id_jabatan', '4') // 4 = Wali Kelas
                ->where('jg.id_tp', $idTp)
                ->where('jg.id_smt', $idSmt)
                ->get()->getRow();
            if (!$waliRow) {
                // Cek guru_id di master_kelas
                $waliRow = $db->table('master_kelas mk')
                    ->select('mg.nama_guru, mg.nip')
                    ->join('master_guru mg', 'mg.id_guru = mk.guru_id', 'left')
                    ->where('mk.id_kelas', $idKelas)
                    ->get()->getRow();
            }
            $waliKelas = $waliRow;
        }

        // Ambil daftar siswa
        $siswaBuilder = $db->table('master_siswa ms')
            ->select('ms.id_siswa, ms.nama, ms.nis, ms.nisn, ms.foto, ms.jenis_kelamin, mk.id_kelas, mk.nama_kelas')
            ->join('kelas_siswa ks', 'ks.id_siswa = ms.id_siswa')
            ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas')
            ->where('ks.id_tp', $idTp)
            ->where('ks.id_smt', $idSmt);
        if ($idKelas > 0) {
            $siswaBuilder->where('ks.id_kelas', $idKelas);
        } elseif ($allowed !== null) {
            if ($allowed === []) {
                $siswaBuilder->where('1 = 0', null, false);
            } else {
                $siswaBuilder->whereIn('ks.id_kelas', $allowed);
            }
        }
        if ($db->tableExists('buku_induk')) {
            // Hanya AKTIF: filter di WHERE (bukan sekadar kondisi JOIN),
            // baris tanpa buku_induk (data lama) tetap lolos sebagai AKTIF.
            $siswaBuilder->join('buku_induk bi', 'bi.id_siswa = ms.id_siswa', 'left')
                ->groupStart()
                    ->where('bi.status', '1')
                    ->orWhere('bi.id_siswa IS NULL', null, false)
                ->groupEnd();
        }
        $students = $siswaBuilder->orderBy('mk.nama_kelas', 'ASC')->orderBy('ms.nama', 'ASC')->get()->getResult();

        // Ambil presensi siswa selama 1 bulan
        $daysInMonth = (int) date('t', mktime(0, 0, 0, (int)$bulan, 1, (int)$tahun));
        $tglAwal = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tglAkhir = sprintf('%04d-%02d-%02d', $tahun, $bulan, $daysInMonth);

        // Kolom denda (migrasi 20261106000000) dibaca bila ada: instalasi yang
        // belum bermigrasi tetap bisa membuka rekap, hanya tanpa angka denda.
        $kolomDenda = '';
        try {
            if ($db->fieldExists('denda', 'presensi_siswa')
                && $db->fieldExists('denda_jenis', 'presensi_siswa')) {
                $kolomDenda = ', ps.denda, ps.denda_jenis';
                // Bolos hari berjalan baru tertagih setelah cron penutup hari
                // menandai barisnya, jadi penanda itu ikut dibaca ke sel.
                if ($db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) {
                    $kolomDenda .= ', ps.pulang_tidak_tercatat';
                }
            }
        } catch (\Throwable $e) {
        }

        $attBuilder = $db->table('presensi_siswa ps')
            ->select('ps.id, ps.id_siswa, ps.id_kelas, ps.tgl, ps.jam_masuk, ps.jam_keluar, ps.status, ps.keterangan' . $kolomDenda)
            ->where('ps.tgl >=', $tglAwal)
            ->where('ps.tgl <=', $tglAkhir);
        if ($idKelas > 0) {
            $attBuilder->where('ps.id_kelas', $idKelas);
        } elseif ($allowed !== null) {
            if ($allowed === []) {
                $attBuilder->where('1 = 0', null, false);
            } else {
                $attBuilder->whereIn('ps.id_kelas', $allowed);
            }
        }
        if ($hasBukuInduk) {
            // Baris presensi milik siswa non-AKTIF tidak ikut rekap.
            $attBuilder->join('buku_induk bi', 'bi.id_siswa = ps.id_siswa', 'left')
                ->groupStart()
                    ->where('bi.status', '1')
                    ->orWhere('bi.id_siswa IS NULL', null, false)
                ->groupEnd();
        }
        $attendances = $attBuilder->get()->getResult();

        $mapBySiswa = [];
        foreach ($attendances as $att) {
            $mapBySiswa[$att->id_siswa][$att->tgl] = $att;
        }

        // Hari yang benar-benar tercatat per kelas. Tanggal tanpa satu baris
        // pun berarti absensi kelas itu belum dijalankan hari itu — bukan
        // seluruh kelas membolos — jadi selnya 'no_data' dan tidak ditagih
        // maupun memicu surat panggilan. Idem SiswaDendaService.
        $tercatatByKelas = [];
        foreach ($attendances as $att) {
            $kid = (int) ($att->id_kelas ?? 0);
            $tercatatByKelas[$kid][substr((string) $att->tgl, 0, 10)] = true;
        }

        // Hari libur nasional + jadwal kerja: peta milik AttendancePolicy,
        // supaya tampilan rekap dan hitungan server tidak pernah beda sumber.
        $holidays = \App\Services\AttendancePolicy::liburNasional();
        $jadwalKerja = \App\Services\AttendancePolicy::jadwalKerja();

        // Setting sekolah & absensi
        $setting = $this->dashboardModel->getSetting();
        if (!$setting) {
            $setting = (object) ['nama_sekolah' => 'absensi by ikasmedia'];
        }
        $absenSetting = $this->absenSettingModel->first();
        $hariLiburMingguan = \App\Services\AttendancePolicy::hariLiburMingguan();

        // Hari BDR per kelas. Hari yang dijadwalkan BDR tidak dihitung sebagai
        // hari efektif dan tidak boleh memunculkan alpha; BDR di luar jadwal
        // tetap tercatat tapi dikeluarkan dari pembagi persen.
        $bdrByKelas = [];
        try {
            $bdrService = new \App\Services\PresensiBdrService();
            foreach ($scopeKelasIds as $kid) {
                $bdrByKelas[$kid] = $bdrService->tanggalUntukKelas($tglAwal, $tglAkhir, $kid);
            }
        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswa::getRekapBulananData] BDR: ' . $e->getMessage());
        }
        $bdrTanggal = $idKelas > 0 ? ($bdrByKelas[$idKelas] ?? []) : [];

        // Hari efektif dihitung sampai hari ini saja (sama seperti rekap guru),
        // supaya tanggal yang belum terjadi tidak menekan persentase. Periode
        // yang sudah lewat tetap dihitung penuh.
        $today     = date('Y-m-d');
        $endHitung = $tglAkhir > $today ? $today : $tglAkhir;

        $hariEfektifBase = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            if ($tglStr > $endHitung) {
                break;
            }
            if (\App\Services\AttendancePolicy::isLibur($tglStr)) {
                continue;
            }
            $hariEfektifBase++;
        }

        $hariEfektifByKelas = [];
        foreach ($scopeKelasIds as $kid) {
            $cut = 0;
            foreach (array_keys($bdrByKelas[$kid] ?? []) as $bdrDate) {
                if ($bdrDate < $tglAwal || $bdrDate > $endHitung) {
                    continue;
                }
                // BDR di akhir pekan/libur nasional sudah tidak masuk hitungan
                // dasar, jadi jangan dipotong dua kali.
                if (\App\Services\AttendancePolicy::isLibur((string) $bdrDate)) {
                    continue;
                }
                $cut++;
            }
            $hariEfektifByKelas[$kid] = max(0, $hariEfektifBase - $cut);
        }

        $hariEfektif = $idKelas > 0
            ? ($hariEfektifByKelas[$idKelas] ?? 0)
            : $hariEfektifBase;
        $hariEfektifValues = array_values($hariEfektifByKelas);
        $hariEfektifMin = $hariEfektifValues ? min($hariEfektifValues) : $hariEfektifBase;
        $hariEfektifMax = $hariEfektifValues ? max($hariEfektifValues) : $hariEfektifBase;
        $hariEfektifLabel = $hariEfektifMin === $hariEfektifMax
            ? $hariEfektifMin . ' hari efektif'
            : $hariEfektifMin . '–' . $hariEfektifMax . ' hari efektif per kelas';

        // Jam batas masuk per hari dari AttendancePolicy: jadwal kerja per hari
        // → absensi_setting, lalu + grace dan inklusif sampai detik ke-59.
        // Angka yang sama dipakai scan, poin, dan arsip piket.
        $batasUntuk = static function (string $tglStr): string {
            return date('H:i:s', \App\Services\AttendancePolicy::batasAkhirMasukTs(
                $tglStr,
                \App\Services\AttendancePolicy::jadwalSiswa($tglStr)
            ));
        };
        $jamBatasMasuk           = $batasUntuk($today);
        $jamBatasMasukPerHari    = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $jamBatasMasukPerHari[$d] = $batasUntuk(sprintf('%04d-%02d-%02d', $tahun, $bulan, $d));
        }

        // Denda siswa sebulan: inilah angka kolom Denda di rekap HTML, Excel,
        // dan PDF. Perhitungannya ada di SiswaDendaService::ringkasBulan() dan
        // memakai fungsi penilai sel yang sama dengan view, jadi angka ini
        // tidak bisa berbeda dari kolom Alpha siswa yang sama — termasuk alpha
        // yang tidak punya baris presensi sama sekali.
        $dendaSiswa = [];
        try {
            $dendaSvc = new \App\Services\SiswaDendaService();
            $dendaSiswa = $dendaSvc->ringkasBulan([
                'bulan'      => (int) $bulan,
                'tahun'      => (int) $tahun,
                'students'   => $students,
                'mapBySiswa' => $mapBySiswa,
                'bdrByKelas' => $bdrByKelas,
                'tercatatByKelas' => $tercatatByKelas,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[PresensiSiswa::getRekapBulananData] denda siswa: ' . $e->getMessage());
        }

        return [
            'bulan'              => (int) $bulan,
            'tahun'              => (int) $tahun,
            'idKelas'            => $idKelas,
            'kelasInfo'          => $kelasInfo,
            'kelases'            => $kelases,
            'students'           => $students,
            'mapBySiswa'         => $mapBySiswa,
            'dendaSiswa'         => $dendaSiswa,
            'holidays'           => $holidays,
            'bdrTanggal'         => $bdrTanggal,
            'bdrByKelas'         => $bdrByKelas,
            'tercatatByKelas'    => $tercatatByKelas,
            'setting'            => $setting,
            'absenSetting'       => $absenSetting,
            'hariLiburMingguan'  => $hariLiburMingguan,
            'jadwalKerja'        => $jadwalKerja,
            'daysInMonth'        => $daysInMonth,
            'hariEfektif'        => $hariEfektif,
            'hariEfektifByKelas' => $hariEfektifByKelas,
            'hariEfektifLabel'   => $hariEfektifLabel,
            'hariEfektifMin'     => $hariEfektifMin,
            'hariEfektifMax'     => $hariEfektifMax,
            'jamBatasMasuk'      => $jamBatasMasuk,
            'jamBatasMasukPerHari' => $jamBatasMasukPerHari,
            'tp_active'          => $tp,
            'smt_active'         => $smt,
            'waliKelas'          => $waliKelas,
            'allowedKelas'       => $allowed,
        ];
    }
}
