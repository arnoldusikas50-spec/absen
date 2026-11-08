<?php

namespace App\Controllers;

use App\Models\AttendanceModel;
use App\Models\AttendanceSettingModel;
use App\Models\AuditLogModel;
use App\Models\JurnalGuruModel;
use App\Services\NotificationService;
use App\Services\AttendancePenaltyService;
use App\Services\HariSekolahService;

/**
 * CronController — Proses otomatis harian/periodik untuk sistem presensi.
 *
 * Endpoint dilindungi dengan secret key dari .env (CRON_SECRET_KEY).
 * Konfigurasi cron di server (contoh, jalankan jam 18:00 setiap hari):
 *   0 18 * * * curl --silent "https://yourdomain.com/cron/daily?key=YOUR_SECRET"
 */
class CronController extends BaseController
{
    protected AttendanceModel $attendanceModel;
    protected AttendanceSettingModel $settingModel;
    protected NotificationService $notification;
    protected AttendancePenaltyService $penaltyService;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->settingModel    = new AttendanceSettingModel();
        $this->notification    = new NotificationService();
        $this->penaltyService  = new AttendancePenaltyService();
    }

    // ----------------------------------------------------------------
    // ENDPOINT PUBLIK (dilindungi key)
    // ----------------------------------------------------------------

    public function index()
    {
        return redirect()->to('dashboard');
    }

    /**
     * Proses harian lengkap — dijalankan lewat cron jam 18:00.
     * GET /cron/daily?key=...
     */
    public function dailyProcess()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        // Kunci yang sama dipakai /cron/mark-alpha: keduanya menulis alpha
        // harian, jadi tidak boleh berjalan berdampingan.
        if (!$this->cobaKunci('absen-cron-alpha-harian')) {
            return $this->response->setStatusCode(409)->setBody('SKIP: cron harian/alpha masih berjalan.');
        }

        $db      = \Config\Database::connect();
        $setting = $this->settingModel->first();

        if (!$setting) {
            return $this->response->setBody('Setting not found');
        }

        $log = [];

        // Rapikan tautan jurnal lebih dulu: semua langkah di bawah (dan
        // monitoring admin) membaca status "sudah dijurnal" dari tautan itu.
        $log[] = $this->selarasJurnalJadwal($db);
        $log[] = $this->markAbsentTeachers($db, $setting);
        $log[] = $this->markAbsentStudents($db, $setting);
        $log[] = $this->markSiswaBelumPulang($db, $setting);
        // Konversi antrean verifikasi yang mangkrak HARUS sebelum surat
        // teguran dibuat, supaya level surat memakai jumlah alpha yang sama
        // dengan yang baru saja ditulis ke absensi_harian.
        $log[] = $this->finalisasiPendingVerifikasi($db, $setting);
        $log[] = $this->generateWarningLetters($db, $setting);
        $log[] = $this->generateSuratPanggilan($db, $setting);
        $log[] = $this->checkKehadiranThreshold($db, $setting);
        $log[] = $this->sendDailyRecap($db, $setting);
        $log[] = $this->markForgotCheckout($db, $setting);
        // Penalti menyusul pengingat: guru masih punya seluruh jam kerja hari
        // itu untuk absen pulang, dan yang dipotong hanya hari-hari sebelum ini.
        $log[] = $this->penaltiGuruLupaPulang($db, $setting);

        // Catatan: setiap langkah mengembalikan string yang sudah jujur soal
        // sukses/gagalnya — isSendFailure() membaca kata 'GAGAL' di dalamnya.
        // Sebelumnya method ini selalu balas "OK: ..." walau rekap gagal,
        // sehingga `curl --fail || php spark health:check` di CRONTAB.MD
        // tidak pernah masuk ke cabang kedua: pemantau mengira sehat.
        $adaGagal = false;
        foreach ($log as $baris) {
            if ($this->isSendFailure($baris)) {
                $adaGagal = true;
                break;
            }
        }

        $prefix = $adaGagal ? 'GAGAL: ' : 'OK: ';

        $response = $this->response->setBody($prefix . date('Y-m-d H:i:s') . "\n" . implode("\n", array_filter($log)));

        if ($adaGagal) {
            // 500 bukan 200: pemantau cron berbasis HTTP (curl --fail)
            // hinges-nya di sini.
            $response->setStatusCode(500);
        }

        return $response;
    }

    /**
 * Hanya kirim rekap harian (notifikasi).
 * GET /cron/daily-recap?key=...
 *
 * Endpoint ini adalah alat manual operator (dari CRONTAB.MD), jadi punya
 * penjaga yang sama dengan /cron/daily: kalau rekap hari ini sudah
 * terkirim, permintaannya dilewati dan diberi tahu alasannya — bukan
 * diam-diam mengirim ulang.
 *
 * Tambahkan `&force=1` untuk memang sengaja mengirim ulang.
 */
public function dailyRecapNotification()
{
    if (!$this->validateKey()) {
        return $this->response->setStatusCode(403)->setBody('Invalid key');
    }

    $db      = \Config\Database::connect();
    $setting = $this->settingModel->first();

    $force = $this->dimintaForce();

    $result = $this->sendDailyRecap($db, $setting, $force);
    $failed = $this->isSendFailure($result);

        // HTTP 500 supaya pemantau cron (atau crontab yang memindai output)
        // langsung tahu ada masalah, bukan mengira semuanya beres.
        return $this->response
            ->setStatusCode($failed ? 500 : 200)
            ->setBody($result . ' — ' . date('Y-m-d H:i:s'));
    }

    /**
     * Kirim rekap laporan piket harian ke grup WA/Telegram.
     * GET /cron/rekap-piket?key=...
     * Opsional: &tanggal=YYYY-MM-DD (default hari ini), &force=1 kirim ulang.
     *
     * Menyusun payload yang sama dengan tombol manual "Kirim Rekap"
     * (Guru\Piket::kirimRekap) tapi tanpa sesi petugas piket: pengirim
     * dicatat "Cron otomatis". Idempotensi memakai cron_notifikasi_log,
     * jadi kirim manual petugas hari ini membuat cron sore selewati hari itu.
     */
    public function rekapPiket()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        $tanggal = trim((string) $this->request->getGet('tanggal'));
        if ($tanggal !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return $this->response->setStatusCode(400)
                ->setBody('Format tanggal harus YYYY-MM-DD');
        }

        $force  = $this->dimintaForce();
        $result = $this->sendPiketRecap($tanggal !== '' ? $tanggal : null, $force);
        $failed = $this->isSendFailure($result);

        // Kegagalan kirim harus terlihat oleh pemantau cron — HTTP 200 yang
        // merem semua akan menyembunyikan gateway yang down berhari-hari.
        return $this->response
            ->setStatusCode($failed ? 500 : 200)
            ->setBody($result . ' — ' . date('Y-m-d H:i:s'));
    }

    /**
     * Tandai guru yang tidak hadir sebagai ALPHA.
     * GET /cron/mark-alpha?key=...
     */
    public function markAlpha()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        if (!$this->cobaKunci('absen-cron-alpha-harian')) {
            return $this->response->setStatusCode(409)->setBody('SKIP: cron harian/alpha masih berjalan.');
        }

        $db      = \Config\Database::connect();
        $setting = $this->settingModel->first();

        if (!$setting) {
            return $this->response->setBody('Setting not found');
        }

        $result = $this->markAbsentTeachers($db, $setting);
        return $this->response->setBody('Alpha marked: ' . date('Y-m-d H:i:s') . ' | ' . $result);
    }

    /**
     * Hitung ulang poin disiplin siswa untuk satu bulan.
     * Catch-up kalau ada presensi yang terlewat atau admin menandai alpha
     * setelah jam kerjanya selesai.
     * GET /cron/poin-siswa?key=...&bulan=9&tahun=2026
     */
    public function poinSiswa()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        $bulan = (int) ($this->request->getGet('bulan') ?? date('n'));
        $tahun = (int) ($this->request->getGet('tahun') ?? date('Y'));
        if ($bulan < 1 || $bulan > 12 || $tahun < 2020 || $tahun > (int) date('Y') + 1) {
            return $this->response->setStatusCode(400)->setBody(
                'PARAM: bulan harus 1-12 dan tahun antara 2020 dan ' . ((int) date('Y') + 1) . '.'
            );
        }

        if (!$this->cobaKunci('absen-cron-poin-siswa')) {
            return $this->response->setStatusCode(409)->setBody('SKIP: recalc poin siswa masih berjalan.');
        }

        $res = (new \App\Services\SiswaPoinService())->recalcBulan($bulan, $tahun);
        return $this->response->setBody(
            'Poin siswa ' . date('Y-m-d H:i:s') . ' | ' . $res['message']
        );
    }

    /**
     * Generate rekap kehadiran per semester (simpan ke absensi_rekap_semester).
     * GET /cron/semester-recap?key=...
     */
    public function semesterRecap()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        if (!$this->cobaKunci('absen-cron-semester-recap')) {
            return $this->response->setStatusCode(409)->setBody('SKIP: rekap semester masih berjalan.');
        }

        $tahun    = (int) ($this->request->getGet('tahun') ?? date('Y'));
        $semester = (int) ($this->request->getGet('semester') ?? ((date('m') >= 7) ? 1 : 2));

        $result = $this->runSemesterRecap($tahun, $semester);

        // Tiga kondisi yang mungkin: parameter salah (400), kegagalan
        // sistem (500), atau sukses (200). Semula semuanya balas 200,
        // sehingga `?tahun=abc` terlihat sama sehatnya dengan rekap yang
        // benar-benar berhasil.
        if (str_starts_with($result, 'PARAM:')) {
            return $this->response->setStatusCode(400)->setBody('Semester rekap: ' . $result);
        }
        if ($this->isSendFailure($result)) {
            return $this->response->setStatusCode(500)->setBody(
                'Semester rekap: ' . date('Y-m-d H:i:s') . ' | ' . $result
            );
        }

        return $this->response->setBody('Semester rekap: ' . date('Y-m-d H:i:s') . ' | ' . $result);
    }

    /**
     * Jalankan generate rekap untuk tahun/semester tertentu (tanpa validasi key).
     * Dipakai oleh cron (semesterRecap) dan fallback langsung dari admin.
     */
    public function runSemesterRecap(int $tahun, int $semester): string
    {
        if ($tahun < 2000 || $tahun > 2100) {
            // Prefix "PARAM:" membedakannya dari kegagalan sistem: ini
            // kesalahan pemanggil, bukan kegagalan internal.
            // Endpoint-nya membalas 400, bukan 200.
            return 'PARAM: tahun harus antara 2000 dan 2100.';
        }
        if (! in_array($semester, [1, 2], true)) {
            return 'PARAM: semester harus 1 atau 2.';
        }

        $db      = \Config\Database::connect();
        $setting = $this->settingModel->first();

        if (!$setting) {
            return 'GAGAL: tabel absensi_setting kosong — konfigurasi presensi belum dibuat, jalankan php spark migrate dan isi lewat Pengaturan.';
        }

        return $this->generateSemesterRekap($db, $setting, $tahun, $semester);
    }

    // ----------------------------------------------------------------
    // PRIVATE METHODS
    // ----------------------------------------------------------------

    /**
     * Anti-tumpang-tindih untuk endpoint cron yang menulis.
     *
     * Dua jalan bersamaan — cron 18:00 yang masih berjalan plus operator yang
     * memanggil endpoint yang sama manual, atau dua baris crontab pada menit
     * yang sama — menilai baris yang sama dua kali: denda dan surat teguran
     * menjadi dobel.
     *
     * GET_LOCK melekat pada koneksi. Config DB memakai 'pConnect' => false,
     * jadi koneksi ditutup pada akhir request dan lock-nya lepas sendiri; itu
     * sebabnya tidak ada RELEASE_LOCK eksplisit di sini.
     *
     * @return bool true = silakan lanjut; false = proses lain masih memegang.
     */
    private function cobaKunci(string $nama): bool
    {
        try {
            $row = \Config\Database::connect()->query('SELECT GET_LOCK(?, 0) AS l', [$nama])->getRow();
        } catch (\Throwable $e) {
            log_message('error', "[cron] GET_LOCK({$nama}) gagal: " . $e->getMessage());
            // Jangan sampai kerja cron berhenti hanya karena mekanisme lock-nya
            // bermasalah (mis. user DB tanpa hak SELECT_LOCK).
            return true;
        }

        if ((int) ($row->l ?? 0) === 1) {
            return true;
        }

        log_message('warning', "[cron] {$nama} dilewati: proses lain masih memegang lock.");

        return false;
    }

    /**
     * Validasi secret key dari query string vs .env.
     *
     * Fail-closed: bila CRON_SECRET_KEY kosong, semua request ditolak dan
     * keadaan dicatat critical di log. Endpoint cron mengubah data absensi
     * dan poin, jadi tidak boleh ada nilai default yang bisa ditebak.
     */
    private function validateKey(): bool
    {
        $key = $this->request->getGet('key');
        $raw = env('CRON_SECRET_KEY');
        $raw = is_string($raw) ? trim($raw) : '';

        if ($raw === '') {
            static $sudahDilaporkan = false;
            if (!$sudahDilaporkan) {
                $sudahDilaporkan = true;
                log_message('critical', '[CronController] CRON_SECRET_KEY kosong di .env. Semua endpoint cron ditolak (fail-closed) sampai key diisi.');
            }
            return false;
        }

        return hash_equals($raw, (string) $key);
    }

    /**
     * Lepas tautan jurnal ke slot jadwal yang sudah tidak cocok (hari, kelas,
     * mapel, atau periode diganti admin).
     *
     * Selama ini pembersihannya hanya jalan saat admin menyimpan jadwal dari
     * menu jadwal; jadwal yang berubah lewat impor/salin-periode atau langsung
     * di basis data tidak pernah tersentuh, dan tautan basi membuat satu jurnal
     * dianggap memenuhi sesi mapel lain. Jalan harian supaya angka "sudah
     * dijurnal" di monitoring, laporan piket, dan gerbang absen pulang guru
     * memakai tautan yang masih benar.
     */
    private function selarasJurnalJadwal($db): string
    {
        try {
            $tpRow  = $db->tableExists('master_tp') ? $db->table('master_tp')->where('active', 1)->get()->getRow() : null;
            $smtRow = $db->tableExists('master_smt') ? $db->table('master_smt')->where('active', 1)->get()->getRow() : null;
            $idTp   = (int) ($tpRow->id_tp ?? 0);
            $idSmt  = (int) ($smtRow->id_smt ?? 0);
            if ($idTp <= 0 || $idSmt <= 0 || !$db->tableExists('guru_jurnal')) {
                return '';
            }

            $lepas = (new JurnalGuruModel())->detachStaleJadwalLinks($idTp, $idSmt);
            if ($lepas <= 0) {
                return '';
            }

            return 'Tautan jurnal: ' . $lepas . ' tautan ke slot jadwal lama dilepas.';
        } catch (\Throwable $e) {
            // Sengaja tanpa kata 'GAGAL': langkah ini merapikan data, dan
            // kegagalannya tidak boleh membuat seluruh cron harian dianggap
            // tidak sehat oleh pemantau.
            $this->logCronFailure('selaras-jurnal-jadwal', $e->getMessage());
            return 'Lewati perapian tautan jurnal: ' . $e->getMessage();
        }
    }

    /**
     * Tandai guru yang tidak absen hari ini sebagai ALPHA.
     * Hanya berjalan pada hari kerja dan setelah jam cutoff (jam masuk + 2 jam).
     */
    private function markAbsentTeachers($db, $setting): string
    {
        $today = date('Y-m-d');

        // Libur (mingguan ternormalisasi + kalender + jadwal sekolah) — satu
        // sumber dengan modul absensi lain.
        if ($libur = \App\Services\AttendancePolicy::alasanLibur($today)) {
            return 'Skip: ' . $libur;
        }

        // Nomor hari ISO untuk cutoff per jadwal/shift di bawah (1=Sen..7=Min).
        $dayNumISO = (int) date('N');

        // Ambil semua guru aktif
        $guruList = $db->table('users u')
            ->select('u.id, g.id_guru, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->get()->getResult();

        // Ambil yang sudah absen hari ini
        $existing = $db->table('absensi_harian')
            ->select('user_id')
            ->where('tgl', $today)
            ->get()->getResult();

        $existingIds = [];
        foreach ($existing as $e) {
            $existingIds[$e->user_id] = true;
        }

        // Cutoff per user: jam masuk efektif (shift → jadwal → setting) + 2 jam.
        // Guru shift sore tidak ditandai alpha saat cron pagi berjalan.
        $globalJadwal = null;
        if ($db->tableExists('absensi_jadwal_kerja')) {
            $globalJadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayNumISO)->get()->getRow();
        }
        $shiftModel = new \App\Models\ShiftModel();

        $dendaAlpha  = (int) ($setting->denda_alpha_per_hari ?? 5000);
        $pointAlpha  = (int) ($setting->point_alpa ?? 5);
        $alphaCount  = 0;
        $gagalAlpha  = 0;
        $skipCutoff  = 0;

        // Izin approved yang mencakup hari ini (tanpa baris harian) bukan alpha.
        $leaveIds = [];
        if ($db->tableExists('absensi_izin')) {
            try {
                $leaveRows = $db->table('absensi_izin')
                    ->select('user_id')
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $today)
                    ->where('tgl_selesai >=', $today)
                    ->get()->getResult();
                foreach ($leaveRows as $lr) {
                    $leaveIds[(int) $lr->user_id] = true;
                }
            } catch (\Throwable $e) {
                // Izin yang tidak terbaca berarti guru yang sedang cuti
                // bisa salah dihitung ALPHA. Itu konsekuensi serius, jadi
                // dicatat — meski prosesnya tetap lanjut supaya satu tabel
                // bermasalah tidak membekukan seluruh penilaian.
                log_message('error', '[Cron] gagal membaca absensi_izin saat menandai alpha: '
                    . $e->getMessage() . ' — guru yang sedang izin berisiko salah dihitung alpha.');
            }
        }

        foreach ($guruList as $guru) {
            if (isset($existingIds[$guru->id])) {
                continue;
            }
            if (isset($leaveIds[(int) $guru->id])) {
                continue;
            }

            $sched = $shiftModel->getScheduleForUser((int) $guru->id, $dayNumISO, $globalJadwal, $setting);
            $cutoffTime = date('H:i:s', strtotime('+2 hours', strtotime($sched['jam_masuk'])));
            if (date('H:i:s') < $cutoffTime) {
                $skipCutoff++;
                continue;
            }

            // Denda alpa flat per hari (progresif dihapus)
            $penaltyService = new \App\Services\AttendancePenaltyService();

            $dendaHariIni = (float) $dendaAlpha;
            $ketDendaAlpha = 'Alpa (Rp ' . number_format($dendaAlpha, 0, ',', '.') . ')';

            // Tegakkan cap denda bulanan: alpha tidak menambah denda bila cap tercapai.
            // Baca-cap + tulis dibungkus transaksi agar konsisten dengan koreksi
            // manual yang berjalan bersamaan.
            $db->transBegin();
            try {
                $capped = $penaltyService->applyMonthlyCap((int) $guru->id, $today, $dendaHariIni, $setting, null, true);
                if ($capped['capped']) {
                    $dendaHariIni = $capped['total'];
                    $ketDendaAlpha .= ' (dibatasi cap bulanan Rp ' . number_format($dendaHariIni, 0, ',', '.') . ')';
                }

                $insertId = $this->attendanceModel->insert([
                    'user_id'          => $guru->id,
                    'id_guru'          => $guru->id_guru ?? null,
                    'tgl'              => $today,
                    'status_masuk'     => 'ALPHA',
                    'status_pulang'    => 'ALPHA',
                    'points'           => -$pointAlpha,
                    'denda'            => $dendaHariIni,
                    'keterangan'       => 'Otomatis: Tidak melakukan presensi',
                    'keterangan_denda' => $ketDendaAlpha,
                ]);
                $db->transCommit();
            } catch (\Throwable $e) {
                try {
                    $db->transRollback();
                } catch (\Throwable $e2) {
                }
                log_message('error', 'Cron alpha gagal untuk user ' . $guru->id . ': ' . $e->getMessage());
                $gagalAlpha++;
                continue;
            }

            // Sync poin history
            try {
                (new \App\Models\PoinHistoryModel())->syncAttendancePoint($insertId);
            } catch (\Throwable $e) {
                log_message('error', 'Cron alpha sync poin: ' . $e->getMessage());
            }

            // Surat teguran + notifikasi, lewat service yang sama dengan
            // jalur generateWarningLetters() dan check-in guru.
            //
            // Versi sebelumnya menghitung alpha dari absensi_harian dan
            // menerbitkan SETIAP level yang ambangnya terlampaui — jadi satu
            // guru bisa menerima tingkat I, II, dan III sekaligus pada hari
            // yang sama. Aturan itu berbeda dari generateWarningLetters(),
            // sehingga dua jalur cron bisa menerbitkan surat bertentangan
            // untuk guru yang sama.
            try {
                $alphaThisMonth = $this->penaltyService->calculateTeacherMonthlyAlpha(
                    (int) $guru->id,
                    (int) date('m'),
                    (int) date('Y'),
                    $setting
                );

                $idGuru = (int) ($guru->id_guru ?? 0);
                $svcTeguran = new \App\Services\SuratTeguranService($db);

                $sinkron = $svcTeguran->sinkronkan($idGuru, $alphaThisMonth, (string) date('m'), (string) date('Y'));
                if (count($sinkron['batal']) > 0 || count($sinkron['hidup']) > 0) {
                    log_message('info', sprintf(
                        '[Cron] Surat teguran guru %d sinkron: %d dibatalkan, %d dihidupkan kembali.',
                        $idGuru,
                        count($sinkron['batal']),
                        count($sinkron['hidup'])
                    ));
                }

                // Satu surat per evaluation, pada level terberat — service
                // yang memutuskan, bukan loop di sini.
                $terbit = $svcTeguran->terbitkan($idGuru, $alphaThisMonth, (string) date('m'), (string) date('Y'));

                if ($terbit !== null) {
                    try {
                        $this->notification->notifyWarningLetter(
                            $guru->nama_guru,
                            $terbit['level'],
                            (int) date('m'),
                            (int) date('Y'),
                            (int) $guru->id
                        );
                    } catch (\Throwable $e) {
                        // Surat sudah terbit; kegagalannya hanya
                        // memberitahu guru. Dicatat supaya terlihat, bukan
                        // hilang tanpa jejak.
                        $this->logCronFailure(
                            'Notifikasi surat teguran ' . $guru->nama_guru,
                            $e->getMessage()
                        );
                    }
                }
            } catch (\Throwable $e) {
                // Jangan gagalkan proses utama karena notifikasi / surat teguran
            }

            $alphaCount++;
        }

        // Audit log. Panggilannya sebelumnya `saveLog()`, dan method itu
        // tidak pernah ada di AuditLogModel — pemanggilan itu melempar
        // BadMethodCallException yang ditelan catch kosong di bawahnya.
        // Akibatnya tiap penandaan alpha diam-diam TIDAK tercatat, dan
        // tidak ada admin yang bisa melihat cron ini pernah berjalan.
        //
        // Dipakai AuditLogModel::log() yang memang ada, dan dicek dulu
        // method-nya supaya instalasi tanpa tabel audit_logs (atau tanpa
        // class) tidak ikut gagal.
        $this->catatAudit(
            'Cron: Menandai ' . $alphaCount . ' guru ALPA pada ' . $today,
            $alphaCount
        );

        return "Alpha marked: {$alphaCount} guru"
            . ($skipCutoff > 0 ? " ({$skipCutoff} belum lewat cutoff shift)" : '')
            . ($gagalAlpha > 0 ? " — GAGAL: {$gagalAlpha} baris tidak tertulis, periksa log" : '');
    }

    /**
     * Tutup antrean verifikasi yang mangkrak: baris PENDING_VERIFICATION yang
     * tanggalnya sudah lewat jendela tinjau menjadi ALPHA.
     *
     * Kenapa perlu: sebelumnya rekap & surat teguran memperlakukan baris
     * pending sebagai alpha (agar ambang surat tidak macet), tapi status di
     * tabel tetap pending selama admin tidak menekan tombol. Jadi dashboard
     * menyebut guru itu "hadir", surat menagih alphanya, dan tidak ada yang
     * pernah menyelesaikan barisnya. Sekarang: masa tenggang
     * AttendancePolicy::PENDING_REVIEW_HARI hari, dihitung dari tanggal
     * kehadiran (bukan dari kapan baris dibuat), lalu cron yang menutupnya.
     *
     * Batas dihitung dari AttendancePolicy supaya angka ini sama dengan yang
     * dibaca AttendancePenaltyService saat menghitung alpha bulanan — kalau
     * dua-duanya tidak sepakat, jumlah alpha di surat tidak cocok dengan
     * jumlah alpha di tabel.
     *
     * Field yang ditulis sengaja identik dengan jalur "tolak" di
     * PresensiAdmin::rejectVerification() (denda + cap bulanan, poin,
     * keterangan) supaya tidak ada dua versi kebenaran soal alpha.
     */
    private function finalisasiPendingVerifikasi($db, $setting): string
    {
        if (!$db->tableExists('absensi_harian')) {
            return 'Skip: tabel absensi_harian tidak ada';
        }

        $hariTinjau = \App\Services\AttendancePolicy::PENDING_REVIEW_HARI;
        $batas = date('Y-m-d', strtotime('-' . $hariTinjau . ' day'));

        // Jendela bawah: tanpa batas ini, satu kali jalan menyapu SEMUA riwayat
        // PENDING_VERIFICATION dan men-AlPHA-kannya sekaligus — berbulan-bulan
        // denda dan surat teguran muncul dalam satu request. Cron alpha harian
        // memakai rentang yang sama (lihat scanStartAlpha di recalc bulanan).
        $awal = date('Y-m-d', strtotime('-' . ($hariTinjau + 62) . ' day'));

        // Nilai status dipakai apa adanya (bukan UPPER()) pada UPDATE di bawah
        // sebagai penjaga idempoten: kalau admin memproses baris yang sama
        // bersamaan, affectedRows() bukan 1 dan barisnya dilewati.
        try {
            $rows = $db->query(
                "SELECT id, user_id, tgl, status_masuk
                 FROM absensi_harian
                 WHERE UPPER(TRIM(status_masuk)) = 'PENDING_VERIFICATION' AND tgl >= ? AND tgl < ?
                 ORDER BY tgl ASC, id ASC",
                [$awal, $batas]
            )->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', '[Cron] gagal membaca antrean verifikasi: ' . $e->getMessage());
            return 'Pending verifikasi: GAGAL membaca antrean (' . $e->getMessage() . ')';
        }

        if (empty($rows)) {
            return 'Pending verifikasi: 0 baris kedaluwarsa (batas ' . $awal . ' .. ' . $batas . ')';
        }

        // Hari libur dan izin approved bukan alpha, sama seperti cron alpha
        // harian. Tanpa checks ini, baris pending yang tertinggal di hari libur
        // atau di tengah cuti otomatis berubah menjadi alpa berdenda.
        $izinMap = [];
        if ($db->tableExists('absensi_izin')) {
            try {
                $coverRows = $db->table('absensi_izin')
                    ->select('user_id, tgl_mulai, tgl_selesai')
                    ->where('status', 'approved')
                    ->where('tgl_mulai <=', $batas)
                    ->where('tgl_selesai >=', $awal)
                    ->get()->getResult();
                foreach ($coverRows as $cv) {
                    $d = strtotime(max($awal, (string) $cv->tgl_mulai));
                    $e2 = strtotime(min($batas, (string) $cv->tgl_selesai));
                    while ($d <= $e2) {
                        $izinMap[(int) $cv->user_id][date('Y-m-d', $d)] = true;
                        $d = strtotime('+1 day', $d);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[Cron] gagal membaca absensi_izin saat finalisasi pending: '
                    . $e->getMessage() . ' — guru yang sedang izin berisiko salah dihitung alpha.');
            }
        }

        // Nama guru untuk notifikasi, sekali jalan.
        $namaGuru = [];
        try {
            $ids = array_unique(array_map(static fn ($r) => (int) $r['user_id'], $rows));
            foreach ($db->table('master_guru')->select('id_user, nama_guru')->whereIn('id_user', $ids)->get()->getResult() as $g) {
                $namaGuru[(int) $g->id_user] = (string) $g->nama_guru;
            }
        } catch (\Throwable $e) {
            // Notifikasi tanpa nama tidak mengubah penilaian; biarkan kosong.
        }

        $baseAlpha  = (float) ($setting->denda_alpha_per_hari ?? 5000);
        $pointAlpha = abs((int) ($setting->point_alpa ?? 5));
        $perubahan  = 0;
        $gagal      = 0;
        $skipLibur  = 0;
        $skipIzin   = 0;

        foreach ($rows as $row) {
            $id     = (int) $row['id'];
            $userId = (int) $row['user_id'];
            $tanggal = substr((string) $row['tgl'], 0, 10);

            if (\App\Services\AttendancePolicy::isLibur($tanggal)) {
                $skipLibur++;
                continue;
            }
            if (isset($izinMap[$userId][$tanggal])) {
                $skipIzin++;
                continue;
            }

            $db->transBegin();
            try {
                $capped = $this->penaltyService->applyMonthlyCap($userId, $tanggal, $baseAlpha, $setting, $id, true);
                $denda = (float) $capped['total'];
                $ketDenda = 'Alpa (Rp ' . number_format($baseAlpha, 0, ',', '.') . ')'
                    . ($capped['capped'] ? ' (dibatasi cap bulanan Rp ' . number_format($denda, 0, ',', '.') . ')' : '');

                $update = [
                    'status_masuk'     => 'ALPHA',
                    'status_pulang'    => 'ALPHA',
                    'points'           => -$pointAlpha,
                    'denda'            => $denda,
                    'keterangan_denda' => $ketDenda,
                    'keterangan'       => "Alpha otomatis: verifikasi tidak ditindak dalam {$hariTinjau} hari",
                    'updated_at'       => date('Y-m-d H:i:s'),
                ];

                $db->table('absensi_harian')
                    ->where('id', $id)
                    ->where('status_masuk', $row['status_masuk'])
                    ->update($update);
                if ($db->affectedRows() !== 1) {
                    throw new \RuntimeException('baris sudah diproses jalur lain');
                }

                try {
                    (new \App\Models\PoinHistoryModel())->syncAttendancePoint($id);
                } catch (\Throwable $e) {
                    log_message('error', '[Cron] pending→alpha sync poin baris ' . $id . ': ' . $e->getMessage());
                }

                try {
                    $this->penaltyService->syncSuratTeguranOnAttendanceChange(
                        $userId,
                        (int) date('n', strtotime($tanggal)),
                        (int) date('Y', strtotime($tanggal)),
                        $setting
                    );
                } catch (\Throwable $e) {
                    log_message('error', '[Cron] pending→alpha sinkron surat baris ' . $id . ': ' . $e->getMessage());
                }

                $db->transCommit();
                $perubahan++;
            } catch (\Throwable $e) {
                try {
                    $db->transRollback();
                } catch (\Throwable $e2) {
                }
                $gagal++;
                log_message('error', '[Cron] konversi pending→alpha gagal untuk baris ' . $id . ': ' . $e->getMessage());
                continue;
            }

            $this->catatAudit(
                'Cron: Verifikasi kehadiran lewat batas (' . $hariTinjau . ' hari) pada ' . $tanggal . ' dijadikan ALPHA',
                $id
            );

            try {
                $this->notification->notifyVerifikasiKedaluwarsa(
                    $namaGuru[$userId] ?? 'Guru',
                    $tanggal,
                    $hariTinjau,
                    $userId
                );
            } catch (\Throwable $e) {
                $this->logCronFailure('Notifikasi verifikasi kedaluwarsa user ' . $userId, $e->getMessage());
            }
        }

        $pesan = "Pending verifikasi: {$perubahan} baris kedaluwarsa → ALPHA (jendela {$awal} .. {$batas})";
        if ($skipLibur > 0 || $skipIzin > 0) {
            $pesan .= " — dilewati: {$skipLibur} hari libur, {$skipIzin} hari berizin";
        }
        if ($gagal > 0) {
            // Kata 'GAGAL' membuat isSendFailure() mengubah balasan cron
            // menjadi 500, supaya pemantau cron tahu ada baris yang mandek.
            $pesan .= ' — GAGAL: ' . $gagal . ' baris tidak bisa dikonversi';
        }

        return $pesan;
    }

    /**
     * Tutup-buku harian siswa: yang belum scan otomatis ALPHA.
     *
     * Dijalankan dalam /cron/daily (18:00, setelah sekolah selesai).
     * Mendelegasikan aturan layak/libur ke PiketModel::syncAlphaOtomatis
     * (tanggal lampau selalu layak; hari libur dilewati), lalu meneruskan
     * baris baru ke presensi formal (ALPA + poin) via syncPiketKeFormal.
     * Isian manual piket tak pernah ditimpa.
     */
    private function markAbsentStudents($db, $setting): string
    {
        try {
            $today = date('Y-m-d');
            $tpId = 0;
            $smtId = 0;
            try {
                if ($db->tableExists('master_tp')) {
                    $tp = $db->table('master_tp')->where('active', 1)->get()->getRow();
                    $tpId = (int) ($tp->id_tp ?? 0);
                }
                if ($db->tableExists('master_smt')) {
                    $smt = $db->table('master_smt')->where('active', 1)->get()->getRow();
                    $smtId = (int) ($smt->id_smt ?? 0);
                }
            } catch (\Throwable $e) {
            }

            $piket = new \App\Models\PiketModel();
            $sync = $piket->syncAlphaOtomatis($today, null, [], $tpId, $smtId);
            if (!$sync['layak']) {
                return 'Skip: alpha siswa (' . ($sync['alasan'] ?: 'belum layak') . ')';
            }
            if (!empty($sync['batch'])) {
                try {
                    $piket->syncPiketKeFormal($sync['batch'], $today);
                } catch (\Throwable $e) {
                    log_message('error', '[Cron] alpha siswa sync formal: ' . $e->getMessage());
                }
            }
            $this->catatAudit('Cron: Menandai ' . $sync['ditandai'] . ' siswa ALPHA/BDR pada ' . $today, $sync['ditandai']);
            return 'Alpha siswa: ' . ($sync['ditandai'] - (int) ($sync['bdr'] ?? 0)) . ' siswa, BDR: ' . (int) ($sync['bdr'] ?? 0) . ' siswa';
        } catch (\Throwable $e) {
            log_message('error', '[Cron] markAbsentStudents: ' . $e->getMessage());
            return 'GAGAL: alpha siswa (' . $e->getMessage() . ')';
        }
    }

    /**
     * Tutup buku pulang siswa: tandai baris yang punya jam masuk tetapi sampai
     * hari berikutnya tetap tanpa jam keluar.
     *
     * Hanya menandai (pulang_tidak_tercatat = 1); jam_keluar tetap NULL.
     * Mengisinya dengan jam_pulang berarti mengarang jam yang tidak bisa
     * dibatalkan, dan rekap akan memperlakukannya sebagai data nyata.
     *
     * Forward-only: hanya tanggal sejak watermark `pulang_close_dari_tgl`,
     * sehingga belasan ribu baris yang sudah terbuka sebelum fitur ini ada
     * tidak berubah menjadi "tidak tercatat" oleh cron pertama. Batas
     * tunggu-tutup dibatasi 60 hari supaya cron yang mati lama tidak
     * memindai tahun-tahun lama; hari yang cronenya mati di luar itu tetap
     * terbuka dan masih terlihat di daftar tanpa-pulang piket.
     */
    private function markSiswaBelumPulang($db, $setting): string
    {
        $today = date('Y-m-d');

        try {
            if (! $db->tableExists('presensi_siswa')) {
                return 'Skip: tabel presensi_siswa tidak ada';
            }
            if (! $this->pastikanKolom($db, 'presensi_siswa', 'pulang_tidak_tercatat', [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false,
            ])) {
                return 'GAGAL: kolom pulang_tidak_tercatat tidak ada dan tidak bisa dibuat '
                    . '— jalankan migration AddPulangTidakTercatatToPresensiSiswa';
            }
            if (! $this->pastikanKolom($db, 'absensi_setting', 'pulang_close_dari_tgl', [
                'type' => 'DATE', 'null' => true, 'default' => null,
            ])) {
                return 'GAGAL: kolom pulang_close_dari_tgl tidak ada dan tidak bisa dibuat '
                    . '— jalankan migration AddPulangCloseWatermark';
            }

            $dari = trim((string) ($setting->pulang_close_dari_tgl ?? ''));
            if ($dari === '' || strtotime($dari) === false || $dari > $today) {
                $db->table('absensi_setting')->where('id', (int) $setting->id)
                    ->update(['pulang_close_dari_tgl' => $today]);
                return 'Tanda pulang siswa: mulai dihitung dari ' . $today . ' (baris sebelumnya tidak disentuh)';
            }

            $kemarin = date('Y-m-d', strtotime('-1 day'));
            $batas   = max($dari, date('Y-m-d', strtotime('-60 day')));

            $ditandai  = 0;
            $hariDitut = 0;
            $denda     = new \App\Services\SiswaDendaService();
            $dendaTertulis = 0;
            for ($ts = strtotime($batas); $ts <= strtotime($kemarin); $ts = strtotime('+1 day', $ts)) {
                $tgl = date('Y-m-d', $ts);
                if (\App\Services\AttendancePolicy::isLibur($tgl)) {
                    continue;
                }
                $hariDitut++;

                $sblm = (int) $ditandai;

                // updated_at sengaja tidak disentuh: kolom itu menandai
                // perubahan presensi oleh manusia/alat, bukan penanda administratif.
                $db->table('presensi_siswa')
                    ->where('tgl', $tgl)
                    ->where('jam_masuk IS NOT NULL', null, false)
                    ->where("(jam_keluar IS NULL OR jam_keluar = '')")
                    ->where("UPPER(TRIM(status)) IN ('HADIR','HADIR TELAT','TERLAMBAT')")
                    ->where('pulang_tidak_tercatat', 0)
                    ->update(['pulang_tidak_tercatat' => 1]);

                $ditandai += (int) $db->affectedRows();

                // Hari yang baru saja ditutup adalah saat "belum pulang" berubah
                // menjadi bolos: barisnya sendiri tidak pernah ditulis lagi
                // setelah check-in, jadi tanpa langkah ini dendanya tidak akan
                // pernah muncul. Hanya hari yang baru tertutup yang diproses,
                // supaya 60 hari tinjauan tidak dipindai sia-sia setiap sore.
                if ($ditandai > $sblm) {
                    $tertanda = $db->table('presensi_siswa')->select('id')
                        ->where('tgl', $tgl)
                        ->where('pulang_tidak_tercatat', 1)
                        ->get()->getResult();
                    foreach ($tertanda as $b) {
                        if ($denda->recalc((int) $b->id)['diubah']) {
                            $dendaTertulis++;
                        }
                    }
                }
            }

            if ($ditandai > 0) {
                $this->catatAudit('Cron: Menandai ' . $ditandai . ' siswa tanpa absen pulang ('
                    . $batas . '…' . $kemarin . ')'
                    . ($dendaTertulis ? ' dengan ' . $dendaTertulis . ' denda tercatat' : ''), $ditandai);
            }

            return 'Pulang tidak tercatat: ' . $ditandai . ' siswa, ' . $hariDitut . ' hari ditutup ('
                . $batas . '…' . $kemarin . ')'
                . ($dendaTertulis ? ', denda: ' . $dendaTertulis . ' baris' : '');
        } catch (\Throwable $e) {
            log_message('error', '[Cron] markSiswaBelumPulang: ' . $e->getMessage());
            return 'GAGAL: tanda pulang siswa (' . $e->getMessage() . ')';
        }
    }

    /**
     * Pastikan satu kolom ada, tanpa bergantung pada cache metadata CI4:
     * fieldExists() tetap menjawab false pada request yang sama walau
     * addColumn() baru saja berhasil, jadi kolom dibaca lewat SHOW COLUMNS.
     */
    private function pastikanKolom($db, string $tabel, string $kolom, array $definisi): bool
    {
        if ($this->kolomAda($db, $tabel, $kolom)) {
            return true;
        }

        try {
            \Config\Database::forge()->addColumn($tabel, [$kolom => $definisi]);
        } catch (\Throwable $e) {
            log_message('error', "[Cron] buat kolom {$tabel}.{$kolom}: " . $e->getMessage());
            return false;
        }

        return $this->kolomAda($db, $tabel, $kolom);
    }

    private function kolomAda($db, string $tabel, string $kolom): bool
    {
        try {
            foreach ($db->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $tabel) . '`')->getResultObject() as $c) {
                if ($c->Field === $kolom) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', "[Cron] baca kolom {$tabel}.{$kolom}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Generate surat teguran otomatis pada tanggal 7, 14, 21, dan akhir bulan.
     * Menggunakan perhitungan kanonik terstandarisasi yang tidak menghitung jam berjalan.
     */
    private function generateWarningLetters($db, $setting): string
    {
        $dayOfMonth = (int) date('j');
        $lastDayOfMonth = (int) date('t');
        if (!in_array($dayOfMonth, [7, 14, 21, $lastDayOfMonth], true)) {
            return 'Skip: bukan tanggal evaluasi surat teguran';
        }

        if (!$db->tableExists('absensi_surat_teguran')) {
            return 'GAGAL: tabel absensi_surat_teguran tidak ada — jalankan php spark migrate.';
        }

        $bulan = (int) date('m');
        $tahun = (int) date('Y');

        $guruList = $db->table('users u')
            ->select('u.id, g.id_guru, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->groupBy('u.id')
            ->get()->getResult();

        $svc      = new \App\Services\SuratTeguranService($db);
        $generated = 0;
        $revived  = 0;
        $canceled = 0;

        foreach ($guruList as $guru) {
            $idGuru = (int) ($guru->id_guru ?? 0);
            if ($idGuru <= 0) {
                // Guru tanpa master_guru: surat tidak bisa diatribusikan,
                // jadi tidak diterbitkan. Menulis dengan id_guru = 0 akan
                // menghasilkan surat yang tidak terlihat, tidak bisa
                // diunduh, dan memblokir slot guru lain.
                continue;
            }

            if ($svc->adalahKepalaSekolah($idGuru)) {
                // Kepala sekolah adalah TTE surat teguran — tidak jadi
                // kandidat di jalur cron. Gerbang yang sama juga hidup di
                // SuratTeguranService; ini menghemat perhitungan alpha dan
                // membuat keputusannya terlihat di satu tempat.
                continue;
            }

            // Hitung alpha akurat dengan standardisasi kanonik (single source of truth)
            $alpha = $this->penaltyService->calculateTeacherMonthlyAlpha((int) $guru->id, $bulan, $tahun, $setting);

            // Selaraskan dulu: surat yang gugur dibatalkan, surat yang
            // kembali sah dihidupkan — baru terbitkan yang baru.
            $sinkron = $svc->sinkronkan($idGuru, $alpha, $bulan, $tahun);
            $canceled += count($sinkron['batal']);
            $revived   += count($sinkron['hidup']);

            $terbit = $svc->terbitkan($idGuru, $alpha, $bulan, $tahun);
            if ($terbit === null) {
                continue;
            }

            try {
                $this->notification->notifyWarningLetter(
                    $guru->nama_guru,
                    $terbit['level'],
                    $bulan,
                    $tahun,
                    (int) $guru->id
                );
            } catch (\Throwable $e) {
                // Surat sudah tercatat di database; yang gagal hanya
                // memberitahu guru. Dicatat supaya ketahuan, bukan
                // hilang tanpa jejak.
                $this->logCronFailure('Notifikasi surat teguran ' . $guru->nama_guru, $e->getMessage());
            }

            $generated++;
        }

        $baris = "Warning letters generated: {$generated}";
        if ($canceled > 0 || $revived > 0) {
            $baris .= sprintf(
                ' (sinkron: %d dibatalkan, %d dihidupkan kembali)',
                $canceled,
                $revived
            );
        }

        return $baris;
    }

    /**
     * Terbitkan surat panggilan orang tua/wali siswa otomatis.
     *
     * Dievaluasi pada tanggal yang sama seperti surat teguran guru
     * (7, 14, 21, akhir bulan) supaya kedua jenis surat terbit di hari
     * yang sama dan tidak saling mengejutkan sekolah.
     *
     * Satu siswa hanya mendapat SATU surat per evaluasi — yang terberat
     * dari ambang yang sudah dilampaui. Kalau alpha 7 hari dan surat
     * tingkat III terbit, tidak perlu juga surat tingkat I dan II: keduanya
     * sudah terlewati dan hanya menambah surat sia-sia ke orang tua.
     */
    private function generateSuratPanggilan($db, $setting): string
    {
        $dayOfMonth = (int) date('j');
        $lastDayOfMonth = (int) date('t');
        if (!in_array($dayOfMonth, [7, 14, 21, $lastDayOfMonth], true)) {
            return 'Skip: bukan tanggal evaluasi surat panggilan';
        }

        if (!$db->tableExists('absensi_surat_panggilan')) {
            return 'GAGAL: tabel absensi_surat_panggilan tidak ada — jalankan php spark migrate.';
        }

        $bulan = (int) date('m');
        $tahun = (int) date('Y');

        try {
            $absenSetting = $this->settingModel->first();
            $ctx = \App\Services\SuratPanggilanService::buildContext($bulan, $tahun);
            $hasil = \App\Services\SuratPanggilanService::generate($bulan, $tahun, $ctx);

            // Notifikasi WA/Telegram ke wali kelas untuk surat yang baru terbit
            if (!empty($hasil['detail'])) {
                try {
                    $daftar = $this->buildDaftarPanggilanDenganWaliKelas($db, $bulan, $tahun, $hasil['detail']);
                    $terkirim = $this->notification->notifySuratPanggilan($daftar, (string) $bulan, (string) $tahun);
                    log_message('info', '[Cron] Notifikasi surat panggilan: ' . $terkirim . ' wali kelas terkirim.');
                } catch (\Throwable $e) {
                    // Surat sudah tercetak di DB; notifikasi gagal tidak boleh
                    // membatalkan cron. Catat saja ke log.
                    log_message('error', '[Cron] Gagal notifikasi surat panggilan ke wali: ' . $e->getMessage());
                }
            }

            $baris = "Surat panggilan: {$hasil['diterbitkan']} terbit dari {$hasil['dicek']} siswa";
            if ($hasil['gagal'] > 0) {
                return 'GAGAL: ' . $baris . ", {$hasil['gagal']} gagal";
            }

            return $baris;
        } catch (\Throwable $e) {
            $this->logCronFailure('Surat panggilan siswa', $e->getMessage());

            return 'GAGAL: surat panggilan error — ' . $e->getMessage();
        }
    }

    /**
     * Bangun daftar surat panggilan lengkap dengan id_user wali kelas per
     * siswa, untuk notifikasi personal WA/Telegram.
     *
     * Alur: siswa → kelas_siswa.id_kelas → master_kelas.guru_id →
     * master_guru.id_user → user_id (tujuan notifikasi personal).
     *
     * @param array<int,array{id_siswa:int,level:int,alpha:int,terlambat:int}> $detail
     * @return array<int,array{id_siswa:int,level:int,alpha:int,terlambat:int,id_user_wali:int}>
     */
    private function buildDaftarPanggilanDenganWaliKelas(object $db, int $bulan, int $tahun, array $detail): array
    {
        if (empty($detail)) {
            return [];
        }

        $idSiswaList = [];
        foreach ($detail as $d) {
            $idSiswaList[] = (int) $d['id_siswa'];
        }
        $idSiswaList = array_unique($idSiswaList);

        // ── siswa → kelas ─────────────────────────────────
        $kelasSiswa = [];
        try {
            $rows = $db->table('kelas_siswa')
                ->select('id_siswa, id_kelas')
                ->whereIn('id_siswa', $idSiswaList)
                ->get()->getResult();
            foreach ($rows as $r) {
                $kelasSiswa[(int) $r->id_siswa] = (int) $r->id_kelas;
            }
        } catch (\Throwable $e) {
            log_message('error', '[Cron] Gagal baca kelas_siswa untuk notif panggilan: ' . $e->getMessage());
        }

        // Ambil semua id_kelas yang terlibat
        $idKelasList = array_values(array_unique(array_filter($kelasSiswa)));

        // ── kelas → wali guru (guru_id) ───────────────────
        $kelasWaliGuru = [];
        if (!empty($idKelasList)) {
            try {
                $rows = $db->table('master_kelas')
                    ->select('id_kelas, guru_id')
                    ->whereIn('id_kelas', $idKelasList)
                    ->where('guru_id IS NOT NULL')
                    ->where('guru_id !=', 0)
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $kelasWaliGuru[(int) $r->id_kelas] = (int) $r->guru_id;
                }
            } catch (\Throwable $e) {
                log_message('error', '[Cron] Gagal baca master_kelas wali untuk notif panggilan: ' . $e->getMessage());
            }
        }

        // ── guru_id → user_id ─────────────────────────────
        $guruIds = array_values(array_unique(array_filter($kelasWaliGuru)));
        $guruUser = [];
        if (!empty($guruIds)) {
            try {
                $rows = $db->table('master_guru')
                    ->select('id_guru, id_user')
                    ->whereIn('id_guru', $guruIds)
                    ->get()->getResult();
                foreach ($rows as $r) {
                    $guruUser[(int) $r->id_guru] = (int) $r->id_user;
                }
            } catch (\Throwable $e) {
                log_message('error', '[Cron] Gagal baca master_guru user_id untuk notif panggilan: ' . $e->getMessage());
            }
        }

        // Susun daftar akhir
        $daftar = [];
        foreach ($detail as $d) {
            $idSiswa = (int) $d['id_siswa'];
            $idKelas = $kelasSiswa[$idSiswa] ?? 0;
            $idGuru  = $kelasWaliGuru[$idKelas] ?? 0;
            $idUser  = $guruUser[$idGuru] ?? 0;

            $daftar[] = [
                'id_siswa'     => $idSiswa,
                'level'        => (int) $d['level'],
                'alpha'        => (int) $d['alpha'],
                'terlambat'    => (int) $d['terlambat'],
                'id_user_wali' => $idUser,
            ];
        }

        return $daftar;
    }

    /**
     * Cek persentase kehadiran bulan ini, kirim notifikasi jika ada di bawah threshold.
     */
    private function checkKehadiranThreshold($db, $setting): string
    {
        if ((int) ($setting->notif_kehadiran_rendah ?? 1) !== 1) {
            return 'Skip: notif kehadiran rendah dinonaktifkan di Pengaturan > Presensi';
        }

        if ($skip = $this->grupDinonaktifkan('kehadiran_rendah', 'Kehadiran rendah')) {
            return $skip;
        }

        $minPersen = (float) ($setting->min_kehadiran_persen ?? 90.0);

        $guruList = $db->table('users u')
            ->select('u.id, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->get()->getResult();

        $rendahList = [];

        foreach ($guruList as $guru) {
            $result = $this->penaltyService->hitungPersentaseKehadiran(
                $guru->id, (int) date('m'), (int) date('Y'), $minPersen
            );
            if ($result['status'] !== 'aman') {
                $rendahList[] = sprintf('%s (%.1f%%)', $guru->nama_guru, $result['persen']);
            }
        }

        if (empty($rendahList)) {
            return 'Kehadiran check: semua guru di atas ' . $minPersen . '%';
        }

        // Penjaga idempotensi SEBELUM kirim: tanpa ini, pemanggilan kedua
        // /cron/daily di hari yang sama tetap menembak gateway dulu lalu
        // baru bilang "sudah terkirim, dilewati" — pesan dobel.
        $kunciBulan = 'rendah_' . date('Y-m');

        if (! $this->dimintaForce() && $this->notifikasiCronTerkirim('kehadiran_rendah', $kunciBulan)) {
            return sprintf(
                'Kehadiran check: %d guru di bawah %s%% — notifikasi bulan ini sudah terkirim, dilewati',
                count($rendahList),
                $minPersen
            );
        }

        // Kegagalan DIWAKTUKAN, bukan ditelan. Sebelumnya catch-nya kosong
        // dan method-nya void, sehingga notifikasi gagal sama sekali tidak
        // meninggalkan jejak — cron.log tetap menulis "N guru" seolah-olah
        // berhasil. String "GAGAL" di sini yang dibaca isSendFailure()
        // supaya dailyProcess() bisa mengembalikan HTTP 500.
        try {
            $sent = $this->notification->notifyKehadiranRendah($rendahList, $minPersen, date('m/Y'));
        } catch (\Throwable $e) {
            $this->logCronFailure('Kehadiran rendah', $e->getMessage());
            return 'Kehadiran check GAGAL: ' . $e->getMessage()
                . ' (' . count($rendahList) . ' guru di bawah ' . $minPersen . '%)';
        }

        $ringkas = sprintf('Kehadiran check: %d guru di bawah %s%%', count($rendahList), $minPersen);

        if ($sent !== true) {
            $alasan = $this->notification->lastError !== ''
                ? $this->notification->lastError
                : 'tidak ada keterangan';
            $this->logCronFailure('Kehadiran rendah', $alasan);
            $this->catatNotifikasiCron('kehadiran_rendah', $kunciBulan, false, $alasan);

            return 'Kehadiran check GAGAL: ' . $alasan
                . ' (' . count($rendahList) . ' guru di bawah ' . $minPersen . '%)';
        }

        $this->catatNotifikasiCron('kehadiran_rendah', $kunciBulan, true);

        return $ringkas;
    }

    /**
     * Kirim rekap kehadiran harian via notifikasi.
     *
     * String yang dikembalikan ikut masuk cron.log, jadi harus jujur: dulu
     * method ini selalu mengembalikan "Daily recap sent" walau Telegram
     * menolak, sehingga kegagalan tidak pernah terlihat.
     */
    private function sendDailyRecap($db, $setting, bool $force = false): string
    {
        $today = date('Y-m-d');

        if ($skip = $this->grupDinonaktifkan('rekap_harian', 'Rekap harian')) {
            return $skip;
        }

        // Libur (mingguan + kalender + jadwal sekolah) dari AttendancePolicy,
        // sumber yang sama dengan gate absensi, alpha cron, dan rekap.
        if ($libur = \App\Services\AttendancePolicy::alasanLibur($today)) {
            return 'Skip: ' . $libur;
        }

        // Penjaga kirim ulang: `/cron/daily` (18:00) dan `/cron/daily-recap`
        // memanggil method yang sama, dan CRONTAB.MD menyuruh operator
        // memanggil yang kedua untuk mengirim ulang rekap yang gagal. Tanpa
        // penjaga ini, rekap yang sudah berhasil terkirim pukul 18:00 akan
        // terkirim lagi begitu operator menjalankannya.
        //
        // $force dipakai endpoint manual dengan `?force=1` — itu tetap satu
        // pengiriman tambahan, bukan pengulangan otomatis.
        if (! $force && $this->notifikasiCronTerkirim('rekap_harian', $today)) {
            return 'Rekap hari ini sudah terkirim sebelumnya, dilewati. '
                . 'Gunakan /cron/daily-recap?force=1 untuk mengirim ulang.';
        }

        $stats = $db->table('absensi_harian')
            ->select("
                COUNT(*) as total,
                SUM(CASE WHEN status_masuk = 'ONTIME'  THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status_masuk = 'LATE'    THEN 1 ELSE 0 END) as telat,
                SUM(CASE WHEN status_masuk IN ('IJIN','IZIN') THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status_masuk = 'SAKIT'   THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status_masuk IN ('ALPHA','ALPA') THEN 1 ELSE 0 END) as alpha,
                SUM(CASE WHEN status_masuk = 'DINAS_APPROVED' THEN 1 ELSE 0 END) as dinas,
                SUM(CASE WHEN status_masuk = 'WFH'     THEN 1 ELSE 0 END) as wfh,
                SUM(CASE WHEN status_masuk = 'PENDING_VERIFICATION' THEN 1 ELSE 0 END) as pending
            ")
            ->where('tgl', $today)
            ->get()->getRow();

        try {
            $sent = $this->notification->notifyRekapHarian([
                'total' => (int) ($stats->total ?? 0),
                'hadir' => (int) ($stats->hadir ?? 0),
                'telat' => (int) ($stats->telat ?? 0),
                'izin'  => (int) ($stats->izin  ?? 0),
                'sakit' => (int) ($stats->sakit ?? 0),
                'alpha' => (int) ($stats->alpha ?? 0),
                'dinas' => (int) ($stats->dinas ?? 0),
                'wfh'   => (int) ($stats->wfh ?? 0),
                'pending' => (int) ($stats->pending ?? 0),
            ]);
        } catch (\Throwable $e) {
            $this->logCronFailure('Daily recap', $e->getMessage());
            $this->catatNotifikasiCron('rekap_harian', $today, false, $e->getMessage());

            return 'Rekap GAGAL: ' . $e->getMessage();
        }

        if ($sent !== true) {
            $reason = $this->notification->lastError !== ''
                ? $this->notification->lastError
                : 'Telegram tidak menerima (tanpa keterangan)';
            $this->logCronFailure('Daily recap', $reason);
            $this->catatNotifikasiCron('rekap_harian', $today, false, $reason);

            return 'Rekap GAGAL: ' . $reason;
        }

        $this->catatNotifikasiCron('rekap_harian', $today, true);

        return 'Daily recap sent';
    }

    /**
     * Kirim rekap piket (notifikasi). Payload identik dengan
     * Guru\Piket::kirimRekap supaya pesan dari cron dan dari tombol manual
     * petugas tidak bisa dibedakan bentuknya.
     *
     * Sumber data selaras manual path:
     *   - petugas      : PiketJadwalModel::getPiketHariIni (roster aktif)
     *   - siswa/kelas  : PiketModel::getRekapSiswaLaporan
     *   - guru hadir/absen : getAllGuruWithAbsensi + getAlasanGuruMap
     *   - bolos        : sudahLewatJamPulang + getSiswaTanpaPulang
     *
     * Tanggal lampau ($tanggal != hari ini) memakai tp/smt = 0 seperti
     * manual path; baris historis dibaca dari piket_absensi saja.
     */
    private function sendPiketRecap(?string $tanggal, bool $force = false): string
    {
        $today   = date('Y-m-d');
        $tanggal = $tanggal !== null && $tanggal !== '' ? $tanggal : $today;
        $isToday = ($tanggal === $today);

        if ($skip = $this->grupDinonaktifkan('rekap_piket', 'Rekap piket')) {
            return $skip;
        }

        // Hari Minggu/libur/cuti: rekap kosong tidak berguna untuk grup.
        // Gate yang sama dengan rekap harian (AttendancePolicy).
        if ($libur = \App\Services\AttendancePolicy::alasanLibur($tanggal)) {
            return 'Skip: ' . $libur;
        }

        // Idempotensi: satu pengiriman per tanggal, dibagi dengan kirim
        // manual petugas (keduanya menulis jenis 'rekap_piket'). Kirim
        // manual pagi membuat cron sore selewati, bukan dobel.
        if (!$force && $this->notifikasiCronTerkirim('rekap_piket', $tanggal)) {
            return 'Rekap piket ' . $tanggal . ' sudah terkirim sebelumnya, dilewati. '
                . 'Gunakan &force=1 untuk mengirim ulang.';
        }

        $db = \Config\Database::connect();

        // TP/SMT aktif — gate yang sama seperti markAbsentStudents().
        $tpId  = 0;
        $smtId = 0;
        try {
            if ($db->tableExists('master_tp')) {
                $tp = $db->table('master_tp')->where('active', 1)->get()->getRow();
                $tpId = (int) ($tp->id_tp ?? 0);
            }
            if ($db->tableExists('master_smt')) {
                $smt = $db->table('master_smt')->where('active', 1)->get()->getRow();
                $smtId = (int) ($smt->id_smt ?? 0);
            }
        } catch (\Throwable $e) {
        }

        if ($tpId === 0 || $smtId === 0) {
            return 'Rekap piket GAGAL: tahun pelajaran/semester aktif belum diset';
        }

        // Tanggal lampau: ikuti manual path — 0/0 supaya agregasi tidak
        // menyeleksi baris piket_absensi berdasarkan tp/smt yang tak pasti.
        if (!$isToday) {
            $tpId  = 0;
            $smtId = 0;
        }

        $piket         = new \App\Models\PiketModel();
        $piketJadwal   = new \App\Models\PiketJadwalModel();

        $hariIndo  = $this->hariIndo(date('l', strtotime($tanggal)));
        $bulanList = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $ts = strtotime($tanggal);
        $labelTgl = $this->hariIndo(date('l', $ts)) . ', ' . date('j', $ts) . ' '
            . ($bulanList[(int) date('n', $ts)] ?? '') . ' ' . date('Y', $ts);

        // Petugas piket tanggal tersebut (roster hari, TP/SMT aktif).
        $petugas = [];
        try {
            foreach ($piketJadwal->getPiketHariIni($hariIndo, $tpId, $smtId) as $c) {
                if (!empty($c->nama_guru)) $petugas[] = (string) $c->nama_guru;
            }
        } catch (\Throwable $e) {
            log_message('error', '[Cron] rekap piket petugas: ' . $e->getMessage());
        }

        // Siswa per kelas + subtotal — format baris identik manual.
        $siswaRekap = ['kelas' => [], 'total' => []];
        try {
            $siswaRekap = $piket->getRekapSiswaLaporan($tanggal, $tpId, $smtId, 0);
        } catch (\Throwable $e) {
            log_message('error', '[Cron] rekap piket siswa: ' . $e->getMessage());
        }
        $siswaRows = [];
        foreach (($siswaRekap['kelas'] ?? []) as $rk) {
            $siswaRows[] = [
                'nama_kelas'   => (string) ($rk->nama_kelas ?? '-'),
                'total'        => (int) ($rk->total ?? 0),
                'H'            => (int) ($rk->r_hadir ?? 0),
                'S'            => (int) ($rk->r_sakit ?? 0),
                'I'            => (int) ($rk->r_izin ?? 0),
                'A'            => (int) ($rk->r_alpha ?? 0),
                'T'            => (int) ($rk->r_telat ?? 0),
                'masuk'        => (int) ($rk->masuk ?? 0),
                'pulang'       => (int) ($rk->pulang ?? 0),
                'tanpa_pulang' => (int) ($rk->r_tanpa_pulang ?? 0),
                'belum'        => (int) ($rk->r_belum ?? 0),
            ];
        }
        $siswaTotal = [];
        $siswaMap   = [
            'total' => 'total', 'H' => 'r_hadir', 'S' => 'r_sakit', 'I' => 'r_izin',
            'A' => 'r_alpha', 'T' => 'r_telat', 'masuk' => 'masuk', 'pulang' => 'pulang',
            'tanpa_pulang' => 'r_tanpa_pulang', 'belum' => 'r_belum',
        ];
        foreach ($siswaMap as $keluar => $asal) {
            $siswaTotal[$keluar] = (int) (($siswaRekap['total'] ?? [])[$asal] ?? 0);
        }

        // Guru hadir + tidak hadir (+alasan).
        $guruHadirTotal = 0;
        $guruHadirTepat = 0;
        $guruHadirTelat = 0;
        $guruList       = [];
        try {
            $guruList = $piket->getAllGuruWithAbsensi($tanggal);
        } catch (\Throwable $e) {
            log_message('error', '[Cron] rekap piket guru: ' . $e->getMessage());
        }
        $alasanMap = [];
        try {
            $alasanMap = $piket->getAlasanGuruMap($tanggal);
        } catch (\Throwable $e) {
            log_message('error', '[Cron] rekap piket alasan guru: ' . $e->getMessage());
        }
        $guruAbsen = [];
        foreach ($guruList as $g) {
            if (!empty($g->jam_masuk)) {
                $guruHadirTotal++;
                strtolower((string) ($g->status_masuk ?? '')) === 'late'
                    ? $guruHadirTelat++
                    : $guruHadirTepat++;
                continue;
            }
            $als = (string) ($alasanMap[(int) ($g->id_guru ?? 0)] ?? 'Tanpa keterangan');
            $guruAbsen[] = [
                'nama'   => (string) ($g->nama_guru ?? 'Guru'),
                'alasan' => $als,
                'alpha'  => strncasecmp(trim($als), 'alpha', 5) === 0,
            ];
        }

        // Bolos: masuk tanpa absen pulang, hanya nampak setelah lewat jam pulang.
        $bolosRows  = [];
        $bolosResmi = false;
        try {
            $bolosResmi = $piket->sudahLewatJamPulang($tanggal);
            if ($bolosResmi) {
                foreach ($piket->getSiswaTanpaPulang($tanggal, null, $tpId, $smtId) as $b) {
                    $bolosRows[] = [
                        'nama'  => (string) ($b->nama ?? 'Siswa'),
                        'kelas' => (string) ($b->nama_kelas ?? ''),
                        'jam'   => substr((string) ($b->jam_masuk ?? ''), 0, 5),
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Cron] rekap piket bolos: ' . $e->getMessage());
        }

        try {
            $ok = $this->notification->notifyRekapPiket([
                'tanggal'          => $labelTgl,
                'petugas'          => $petugas,
                'siswa'            => $siswaRows,
                'siswa_total'      => $siswaTotal,
                'siswa_bolos'      => $bolosRows,
                'bolos_resmi'      => $bolosResmi,
                'guru_absen'       => $guruAbsen,
                'guru_hadir_total' => $guruHadirTotal,
                'guru_hadir_tepat' => $guruHadirTepat,
                'guru_hadir_telat' => $guruHadirTelat,
                'pengirim'         => 'Cron otomatis',
            ]);
        } catch (\Throwable $e) {
            $this->logCronFailure('Rekap piket', $e->getMessage());
            $this->catatNotifikasiCron('rekap_piket', $tanggal, false, $e->getMessage());

            return 'Rekap piket GAGAL: ' . $e->getMessage();
        }

        if ($ok !== true) {
            $alasan = $this->notification->lastError !== ''
                ? $this->notification->lastError
                : 'gateway notifikasi tidak menerangkan alasannya';
            $this->logCronFailure('Rekap piket', $alasan);
            $this->catatNotifikasiCron('rekap_piket', $tanggal, false, $alasan);

            return 'Rekap piket GAGAL: ' . $alasan
                . ' (' . count($guruAbsen) . ' guru tidak hadir)';
        }

        $this->catatNotifikasiCron('rekap_piket', $tanggal, true, 'Otomatis via /cron/rekap-piket');

        return 'Rekap piket terkirim (' . count($guruAbsen) . ' guru tidak hadir, '
            . count($bolosRows) . ' siswa bolos)';
    }

    /**
     * Nama hari Indonesia — salin mapping Guru\Piket::getHariIndo() yang
     * private, dipakai untuk roster piket (kolom piket_harian.hari berisi
     * "Senin", "Selasa", dst).
     */
    private function hariIndo(string $day): string
    {
        $days = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];

        return $days[$day] ?? $day;
    }

    /**
     * Catat kegagalan notifikasi ke log aplikasi.
     *
     * Cron.log hanya berisi output endpoint; log_message() memastikan penyebab
     * kegagalan juga ada di writable/logs/log-YYYY-MM-DD.log.
     */
    private function logCronFailure(string $step, string $reason): void
    {
        log_message('error', '[Cron] ' . $step . ' GAGAL: ' . $reason);
    }

    /**
     * Pesan Skip bila kedua kanal grup dimatikan di notif-config.
     *
     * Kembalikan null bila minimal satu kanal aktif (lanjut kirim).
     * Pesan sengaja TANPA kata 'GAGAL' supaya isSendFailure() false dan
     * endpoint membalas HTTP 200: mematikan channel adalah keputusan
     * admin yang sah, bukan kegagalan gateway.
     */
    private function grupDinonaktifkan(string $jenis, string $label): ?string
    {
        try {
            if ($this->notification->isGrupDisabled($jenis)) {
                return $label . ' dilewati: channel TG Grup + WA Grup dinonaktifkan di Pengaturan → Channel Notifikasi.';
            }
        } catch (\Throwable $e) {
            // Fail-open: anggap aktif, biar notifyGrup yang memutuskan.
        }

        return null;
    }

    // ── idempotensi notifikasi cron ───────────────────────────────────

    /**
     * Catat hasil satu pengiriman notifikasi cron.
     *
     * Tabel `cron_notifikasi_log` punya UNIQUE(jenis, periode), dan itu
     * yang bekerja sebagai penjaga. Percobaan berulang menaikkan
     * `percobaan`; hanya `terkirim = 1` yang menghentikan pengiriman
     * berikutnya. Kegagalan tidak mengunci periode — kalau begitu, satu
     * gateway yang down dua menit akan mematikan rekap selamanya.
     *
     * @return bool true bila baris tersimpan (termasuk saat percobaan ulang)
     */
    private function catatNotifikasiCron(string $jenis, string $periode, bool $terkirim, ?string $catatan = null): bool
    {
        $db = \Config\Database::connect();

        try {
            if (! $db->tableExists('cron_notifikasi_log')) {
                return false; // migrasi belum dijalankan
            }

            $waktu = date('Y-m-d H:i:s');
            $terkirimInt = $terkirim ? 1 : 0;

            /*
             * INSERT ... ON DUPLICATE KEY UPDATE adalah atomik, jadi race
             * condition (dua proses cek baris secara bersamaan lalu keduanya
             * INSERT) tidak lagi menghasilkan duplicate entry pada
             * UNIQUE(jenis, periode).
             *
             * - Baris baru   : percobaan = 1
             * - Baris sudah ada: percobaan = percobaan + 1, terkirim/catatan/updated_at diupdate
             */
            $db->query("
                INSERT INTO cron_notifikasi_log
                    (jenis, periode, terkirim, percobaan, catatan, created_at, updated_at)
                VALUES (?, ?, ?, 1, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    terkirim  = VALUES(terkirim),
                    percobaan = percobaan + 1,
                    catatan   = VALUES(catatan),
                    updated_at = VALUES(updated_at)
            ", [
                $jenis,
                $periode,
                $terkirimInt,
                $catatan,
                $waktu,
                $waktu,
            ]);

            return true;
        } catch (\Throwable $e) {
            // Penjaga idempotensi tidak boleh menggagalkan pengiriman yang
            // sendiri bisa saja berhasil. Lebih baik kirim dobel daripada
            // tidak terkirim sama sekali — dan operator melihat log error ini.
            log_message('error', '[Cron] gagal mencatat idempotensi ' . $jenis . '/' . $periode
                . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Apakah operator memang meminta pengiriman ulang?
     *
     * Endpoint manual (`/cron/daily-recap`, `/cron/weekly-denda`,
     * `/cron/monthly-pdf`) memakai `&force=1` untuk sengaja mengirim lagi
     * setelah memperbaiki masalah. Tanpa parameter ini, permintaan kedua
     * akan dilewati begitu saja karena penjaga idempotensi.
     */
    private function dimintaForce(): bool
    {
        $v = strtolower(trim((string) ($this->request->getGet('force') ?? '')));

        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Sudah pernah terkirim untuk (jenis, periode) ini?
     *
     * Hanya `terkirim = 1` yang dianggap selesai. Percobaan yang gagal
     * sengaja tidak dihitung supaya operator bisa mengirim ulang lewat
     * endpoint manual setelah memperbaiki masalahnya.
     */
    private function notifikasiCronTerkirim(string $jenis, string $periode): bool
    {
        $db = \Config\Database::connect();

        try {
            if (! $db->tableExists('cron_notifikasi_log')) {
                return false;
            }
            $row = $db->table('cron_notifikasi_log')
                ->where('jenis', $jenis)
                ->where('periode', $periode)
                ->get()
                ->getRow();

            return $row !== null && (int) $row->terkirim === 1;
        } catch (\Throwable $e) {
            log_message('error', '[Cron] gagal membaca idempotensi ' . $jenis . '/' . $periode
                . ': ' . $e->getMessage());
            // Tidak diketahui != sudah terkirim. Mengirim ulang lebih baik
            // daripada diam-diam tidak mengirim.
            return false;
        }
    }

    /**
     * Tandai guru yang lupa checkout (sudah masuk, belum pulang, hari sudah lewat).
     */
    private function markForgotCheckout($db, $setting): string
    {
        $today = date('Y-m-d');

        if ($skip = $this->grupDinonaktifkan('lupa_checkout', 'Lupa check-out')) {
            return $skip;
        }

        // Daftar bebas-nyaring diambil dari AttendancePolicy supaya sama dengan
        // penalti poin (sqlTermBelumPulang). Daftar lokal sebelumnya
        // ['IJIN','SAKIT','ALPHA','CUTI'] lupa ejaan lama (IZIN/ALPA) dan
        // PENDING_VERIFICATION — guru yang barisnya masih antre verifikasi
        // admin tetap dikirim pengingat "lupa pulang", padahal sistem sudah
        // mengecualikannya dari penalti.
        // WFH/DINAS* juga dibebaskan (keputusan 2026-11-02): guru yang bekerja
        // dari rumah atau sedang dinas tidak punya check-out kantor untuk diingatkan.
        // status_pulang ikut diperiksa karena dinas/WFH bisa tercatat di kolom
        // itu sementara status_masuk masih ONTIME/LATE.
        $forgotList = $db->table('absensi_harian a')
            ->select('a.id, a.user_id, mg.nama_guru')
            ->join('master_guru mg', 'mg.id_user = a.user_id')
            ->where('a.tgl', $today)
            ->where('a.jam_masuk IS NOT NULL')
            ->where('a.jam_pulang IS NULL')
            ->where(\App\Services\AttendancePolicy::sqlBebasPulangStatus('a.status_masuk'), null, false)
            ->where(\App\Services\AttendancePolicy::sqlBebasPulangStatus('a.status_pulang'), null, false)
            ->get()->getResult();

        $count      = 0;
        $berhasil   = 0;
        $gagalNames = [];

        foreach ($forgotList as $rec) {
            $count++;

            // Idempotensi per guru per hari. Query di atas memilih guru yang
            // sama lagi di setiap pemanggilan /cron/daily, jadi tanpa penjaga
            // ini setiap eksekusi ulang mengirim ulang pengingat yang sama.
            // Kunci memakai tanggal ringkas (Ymd) supaya muat di kolom
            // periode VARCHAR(20): 'lupa_' + 8 digit + '_' + user_id.
            // Kunci lama ('lupa_<id>' tanpa tanggal) selamanya mengunci
            // guru itu — pengingat besok tidak akan pernah terkirim lagi.
            $kunciLupa = 'lupa_' . date('Ymd') . '_' . (int) $rec->user_id;
            if (! $this->dimintaForce() && $this->notifikasiCronTerkirim('lupa_checkout', $kunciLupa)) {
                continue;
            }

            try {
                if ($this->notification->notifyForgotCheckout($rec->nama_guru, $today)) {
                    $berhasil++;
                    $this->catatNotifikasiCron('lupa_checkout', $kunciLupa, true);
                } else {
                    $gagalNames[] = $rec->nama_guru;
                    $this->catatNotifikasiCron('lupa_checkout', $kunciLupa, false, 'ditolak gateway');
                }
            } catch (\Throwable $e) {
                $gagalNames[] = $rec->nama_guru;
                $this->catatNotifikasiCron('lupa_checkout', $kunciLupa, false, $e->getMessage());
                $this->logCronFailure('Lupa check-out ' . $rec->nama_guru, $e->getMessage());
            }
        }

        // $count dulu selalu bertambah tanpa syarat, jadi baris log ini pernah
        // mengklaim "5 guru" walau kelima kiriman ditolak gateway.
        if ($gagalNames !== []) {
            $this->logCronFailure(
                'Lupa check-out',
                count($gagalNames) . ' dari ' . $count . ' kiriman gagal: ' . implode(', ', array_slice($gagalNames, 0, 5))
            );

            return sprintf(
                'Forgot checkout reminder GAGAL: %d/%d terkirim, gagal: %s',
                $berhasil,
                $count,
                implode(', ', array_slice($gagalNames, 0, 5))
            );
        }

        return "Forgot checkout reminder: {$berhasil} guru";
    }

    /**
     * Potong poin guru yang masuk tapi sampai hari itu berakhir tidak pernah
     * absen pulang.
     *
     * Bedanya dengan markForgotCheckout(): pengingat itu untuk HARI INI
     * (guru masih bisa menyelamatkan kepulangannya), sedangkan penalti hanya
     * boleh jatuh pada hari yang sudah lewat — jam pulang yang diinput pukul
     * 19.00 tetap dihitung pulang. Aturan hari lewat + daftar status bebas
     * diambil dari AttendancePolicy supaya sama dengan angka yang selama ini
     * terlihat di skor (sqlTermBelumPulang).
     *
     * Forward-only lewat watermark lupa_pulang_dari_tgl: sama seperti tanda
     * pulang siswa (markSiswaBelumPulang), cron pertama tidak boleh memotong
     * hari-hari historis yang terbuka sebelum fitur ini ada. Jendela mundur
     * dibatasi 60 hari supaya cron yang lama mati tidak memindai tahun-tahun
     * lama sekaligus.
     *
     * point_lupa_pulang = 0 berarti sekolah tidak menerapkan aturan ini: tidak
     * ada baris yang ditandai, supaya mengaktifkannya kembali nanti tetap
     * menghitung dari tanggal aktivasi, bukan dari hari-hari yang terlewat.
     */
    private function penaltiGuruLupaPulang($db, $setting): string
    {
        $today = date('Y-m-d');
        $poin  = max(0, (int) ($setting->point_lupa_pulang ?? 5));

        if ($poin === 0) {
            return 'Penalti tidak absen pulang guru: dilewati (point_lupa_pulang = 0)';
        }

        try {
            if (! $db->tableExists('absensi_harian')) {
                return 'Skip: tabel absensi_harian tidak ada';
            }
            if (! $this->pastikanKolom($db, 'absensi_harian', 'pulang_tidak_tercatat', [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false,
            ]) || ! $this->pastikanKolom($db, 'absensi_harian', 'poin_penalti_pulang', [
                'type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false,
            ])) {
                return 'GAGAL: kolom penalti tidak absen pulang tidak ada dan tidak bisa dibuat '
                    . '— jalankan migration AddPenaltiLupaPulangGuru';
            }
            if (! $this->pastikanKolom($db, 'absensi_setting', 'lupa_pulang_dari_tgl', [
                'type' => 'DATE', 'null' => true, 'default' => null,
            ])) {
                return 'GAGAL: kolom lupa_pulang_dari_tgl tidak ada dan tidak bisa dibuat '
                    . '— jalankan migration AddPenaltiLupaPulangGuru';
            }

            $dari = trim((string) ($setting->lupa_pulang_dari_tgl ?? ''));
            if ($dari === '' || strtotime($dari) === false || $dari > $today) {
                $db->table('absensi_setting')->where('id', (int) $setting->id)
                    ->update(['lupa_pulang_dari_tgl' => $today]);

                return 'Penalti tidak absen pulang guru: mulai dihitung dari ' . $today
                    . ' (' . $poin . ' poin/hari; baris sebelumnya tidak dipotong)';
            }

            $kemarin = date('Y-m-d', strtotime('-1 day'));
            $batas   = max($dari, date('Y-m-d', strtotime('-60 day')));

            $kandidat = $db->table('absensi_harian')
                ->select('id, user_id, tgl')
                ->where('tgl >=', $batas)
                ->where('tgl <=', $kemarin)
                ->where("(jam_masuk IS NOT NULL AND jam_masuk <> '0000-00-00 00:00:00')", null, false)
                ->where("(jam_pulang IS NULL OR jam_pulang = '0000-00-00 00:00:00')", null, false)
                ->where('pulang_tidak_tercatat', 0)
                ->where(\App\Services\AttendancePolicy::sqlBebasPulangStatus('status_masuk'), null, false)
                ->where(\App\Services\AttendancePolicy::sqlBebasPulangStatus('status_pulang'), null, false)
                ->orderBy('tgl', 'ASC')
                ->get()->getResult();

            $dipotong  = 0;
            $syncGagal = 0;
            $gagalTulis = 0;

            foreach ($kandidat as $r) {
                $db->transBegin();
                try {
                    // points = points - N dikerjakan di sisi database, bukan
                    // baca-ubah-tulis: absen pulang yang tercatat di detik yang
                    // sama tidak boleh tertimpa nilai lama. Penjaga WHERE membuat
                    // baris yang sudah kebagian penalti, atau yang lebih dulu
                    // punya jam pulang, tidak tersentuh (affectedRows = 0).
                    $db->query(
                        "UPDATE absensi_harian
                            SET points = COALESCE(points, 0) - ?,
                                pulang_tidak_tercatat = 1,
                                poin_penalti_pulang = ?
                          WHERE id = ?
                            AND pulang_tidak_tercatat = 0
                            AND (jam_pulang IS NULL OR jam_pulang = '0000-00-00 00:00:00')",
                        [$poin, $poin, (int) $r->id]
                    );

                    if ($db->affectedRows() === 0) {
                        $db->transCommit();
                        continue;
                    }

                    $poinModel = new \App\Models\PoinHistoryModel();
                    if (! $poinModel->syncAttendancePoint($r->id)) {
                        $syncGagal++;
                    }

                    $db->transCommit();
                    $dipotong++;
                } catch (\Throwable $e) {
                    try {
                        $db->transRollback();
                    } catch (\Throwable $e2) {
                    }
                    $gagalTulis++;
                    log_message('error', '[Cron] penalti lupa pulang guru user '
                        . $r->user_id . ' tgl ' . $r->tgl . ': ' . $e->getMessage());
                }
            }

            if ($dipotong > 0) {
                $this->catatAudit(
                    'Cron: Memotong ' . $poin . ' poin untuk ' . $dipotong
                    . ' hari guru tanpa absen pulang (' . $batas . '…' . $kemarin . ')',
                    $dipotong
                );
            }

            return 'Penalti tidak absen pulang guru: ' . $dipotong . ' baris (-' . $poin
                . ' poin) (' . $batas . '…' . $kemarin . ')'
                . ($syncGagal > 0 ? " — {$syncGagal} sinkronisasi poin_history gagal, periksa log" : '')
                . ($gagalTulis > 0 ? " — GAGAL: {$gagalTulis} baris tidak tertulis" : '');
        } catch (\Throwable $e) {
            log_message('error', '[Cron] penaltiGuruLupaPulang: ' . $e->getMessage());
            return 'GAGAL: penalti tidak absen pulang guru (' . $e->getMessage() . ')';
        }
    }

    /**
     * Generate / update rekap kehadiran per semester ke tabel absensi_rekap_semester.
     * Standar SMA: diperlukan untuk laporan Dinas Pendidikan dan penilaian kinerja.
     *
     * Konvensi status (diselaraskan dengan Dashboard & Guru\Absensi):
     * - HADIR  = ONTIME, LATE, WFH + dinas disetujui (DINAS, DINAS_APPROVED, DINAS_OUT,
     *            atau status berawalan DINAS selain DINAS_REJECTED / DINAS_PENDING).
     *            Dinas resmi dihitung HADIR agar guru yang ditugaskan tidak dirugikan.
     * - NETRAL (tidak alfa, tapi menurunkan %) = IJIN/IZIN, SAKIT, CUTI, DINAS_PENDING.
     * - ALPHA  = sisa hari efektif tanpa catatan (mencakup ALPHA/ALPA & DINAS_REJECTED).
     * - Persentase = HADIR / hari_efektif * 100.
     */
    private function generateSemesterRekap($db, $setting, int $tahun = 0, int $semester = 0): string
    {
        if (!$db->tableExists('absensi_rekap_semester')) {
            return 'GAGAL: tabel absensi_rekap_semester tidak ada — jalankan php spark migrate.';
        }

        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');
        if (!in_array($semester, [1, 2], true)) $semester = ((int) date('m') >= 7) ? 1 : 2;

        // Semester: 1 = Juli-Des (ganjil), 2 = Jan-Jun (genap)
        $startBulan  = ($semester === 1) ? 7 : 1;
        $endBulan    = ($semester === 1) ? 12 : 6;
        $startDate   = sprintf('%04d-%02d-01', $tahun, $startBulan);
        $endDate     = date('Y-m-t', mktime(0, 0, 0, $endBulan, 1, $tahun));
        $today       = date('Y-m-d');
        $isPartial   = false;
        if ($endDate > $today) { $endDate = $today; $isPartial = true; }

        $guruList = $db->table('users u')
            ->select('u.id, g.id_guru, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->get()->getResult();

        $minPersen = (float) ($setting->min_kehadiran_persen ?? 90.0);

        // Hitung hari efektif semester (libur: mingguan ternormalisasi +
        // kalender + jadwal sekolah — sumber tunggal AttendancePolicy).
        $effDays = 0;
        $cur = strtotime($startDate);
        $endTs = strtotime($endDate);
        while ($cur <= $endTs) {
            if (!\App\Services\AttendancePolicy::isLibur(date('Y-m-d', $cur))) {
                $effDays++;
            }
            $cur = strtotime('+1 day', $cur);
        }

        $count = 0;
        $statsByUser = [];
        if ($db->tableExists('absensi_harian')) {
            $statsByUser = $db->table('absensi_harian')
                ->select("
                    user_id,
                    SUM(CASE WHEN status_masuk IN ('ONTIME','LATE','WFH') THEN 1 ELSE 0 END) as hadir_biasa,
                    SUM(CASE WHEN status_masuk = 'LATE' THEN 1 ELSE 0 END) as telat,
                    SUM(CASE WHEN status_masuk IN ('IJIN','IZIN') THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status_masuk = 'SAKIT' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status_masuk = 'CUTI' THEN 1 ELSE 0 END) as cuti,
                    SUM(CASE WHEN status_masuk IN ('ALPHA','ALPA') THEN 1 ELSE 0 END) as alpha_rows,
                    SUM(CASE WHEN status_masuk = 'DINAS_PENDING' THEN 1 ELSE 0 END) as dinas_pending,
                    SUM(CASE WHEN status_masuk = 'PENDING_VERIFICATION' THEN 1 ELSE 0 END) as pending_verifikasi,
                    SUM(CASE WHEN (status_masuk = 'DINAS' OR status_masuk = 'DINAS_APPROVED' OR status_masuk = 'DINAS_OUT' OR (status_masuk LIKE 'DINAS%' AND status_masuk NOT IN ('DINAS_REJECTED','DINAS_PENDING'))) THEN 1 ELSE 0 END) as dinas,
                    SUM(CASE WHEN status_masuk = 'DINAS_REJECTED' THEN 1 ELSE 0 END) as dinas_rejected,
                    COALESCE(SUM(points), 0) as total_poin,
                    COALESCE(SUM(denda), 0) as total_denda
                ")
                ->where('tgl >=', $startDate)
                ->where('tgl <=', $endDate)
                ->groupBy('user_id')
                ->get()->getResult();
        }

        $stats = [];
        foreach ($statsByUser as $row) {
            $stats[$row->user_id] = $row;
        }

        $now = date('Y-m-d H:i:s');
        $db->transStart();

        foreach ($guruList as $guru) {
            $recs = $stats[$guru->id] ?? (object) [
                'hadir_biasa' => 0, 'telat' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0,
                'alpha_rows' => 0, 'dinas_pending' => 0, 'dinas' => 0, 'dinas_rejected' => 0,
                'pending_verifikasi' => 0,
                'total_poin' => 0, 'total_denda' => 0,
            ];

            // Dinas resmi = HADIR (guru bertugas, bukan bolos)
            $hadir = (int) ($recs->hadir_biasa ?? 0) + (int) ($recs->dinas ?? 0);
            // Batas atas: satu baris per hari, hadir tak boleh melebihi hari efektif
            if ($hadir > $effDays) $hadir = $effDays;

            $izin         = (int) ($recs->izin ?? 0);
            $sakit        = (int) ($recs->sakit ?? 0);
            $cuti         = (int) ($recs->cuti ?? 0);
            $dinasPending = (int) ($recs->dinas_pending ?? 0);
            $dinasOk      = (int) ($recs->dinas ?? 0);
            // Antrean verifikasi masih terbuka: bukan hadir, bukan alpha
            // (kebijakan yang sama dipakai Dashboard dan AttendancePenaltyService).
            $pendingVerif = (int) ($recs->pending_verifikasi ?? 0);

            // Alpha = hari efektif tanpa catatan hadir maupun izin resmi.
            // DINAS_REJECTED ikut menjadi alpha (penolakan = wajib hadir).
            // Tanpa $pendingVerif di sisi kanan, hari yang menunggu verifikasi
            // diam-diam dihitung alpha di sini — beda dari halaman rekap harian
            // dan beda dari surat teguran.
            $alpha  = $effDays - $hadir - $izin - $sakit - $cuti - $dinasPending - $pendingVerif;
            $alpha  = max(0, $alpha);
            $persen = $effDays > 0 ? round(($hadir / $effDays) * 100, 2) : 100.0;

            $kelayakan = 'layak';
            if ($persen < $minPersen - 10) $kelayakan = 'tidak_layak';
            elseif ($persen < $minPersen)  $kelayakan = 'perhatian';

            $data = [
                'user_id'              => $guru->id,
                'tahun'                => $tahun,
                'semester'             => $semester,
                'total_hari_efektif'   => $effDays,
                'total_hadir'          => $hadir,
                'total_telat'          => (int) ($recs->telat  ?? 0),
                'total_izin'           => $izin,
                'total_sakit'          => $sakit,
                'total_alpha'          => $alpha,
                'total_dinas'          => $dinasOk,
                'persentase_kehadiran' => $persen,
                'total_poin'           => (int) ($recs->total_poin  ?? 0),
                'total_denda'          => (float) ($recs->total_denda ?? 0),
                'status_kelayakan'     => $kelayakan,
                'generated_at'         => $now,
                'updated_at'           => $now,
            ];

            // Upsert
            $existing = $db->table('absensi_rekap_semester')
                ->where('user_id', $guru->id)
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->get()->getRow();

            if ($existing) {
                $db->table('absensi_rekap_semester')
                    ->where('id', $existing->id)
                    ->update($data);
            } else {
                $data['created_at'] = $now;
                $db->table('absensi_rekap_semester')->insert($data);
            }

            $count++;
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return "Gagal menyimpan rekap semester {$semester}/{$tahun} (transaksi dibatalkan)";
        }

        $suffix = $isPartial ? ' (parsial s/d hari ini)' : '';
        return "Semester rekap generated: {$count} guru (Semester {$semester}/{$tahun}, {$effDays} hari efektif{$suffix})";
    }

    // ----------------------------------------------------------------
    // REKAP DENDA (MINGGUAN & BULANAN)
    // ----------------------------------------------------------------

    /**
     * Kirim rekap denda mingguan — dijalankan setiap Sabtu.
     * GET /cron/weekly-denda?key=...
     */
    public function weeklyDendaRecap()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        // Pastikan hari Sabtu (opsional: tetap bisa dipanggil manual)
        $today = date('Y-m-d');
        $dayName = date('D');

        // Periode: Senin – Sabtu minggu ini
        $saturday = ($dayName === 'Sat') ? $today : date('Y-m-d', strtotime('last Saturday'));
        $monday   = date('Y-m-d', strtotime('-5 days', strtotime($saturday)));
        $periode  = date('d/m/Y', strtotime($monday)) . ' – ' . date('d/m/Y', strtotime($saturday));

        // Pengamanan: periode tidak boleh kosong / tidak ter-parse agar
        // kunci idempotensi (`denda_mingguan_<tgl>`) selalu valid dan unik.
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $monday)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $saturday)
            || strtotime($monday) === false
            || strtotime($saturday) === false) {
            log_message('error', '[Cron] weeklyDendaRecap periode tidak valid: monday=' . $monday . ', saturday=' . $saturday);
            return $this->response
                ->setStatusCode(500)
                ->setBody('Weekly denda recap: GAGAL menentukan periode Sabtu (tanggal tidak valid).');
        }

        $result  = $this->sendDendaRecap($monday, $saturday, $periode, $this->dimintaForce());
        $failed  = $this->isSendFailure($result);

        return $this->response
            ->setStatusCode($failed ? 500 : 200)
            ->setBody('Weekly denda recap: ' . date('Y-m-d H:i:s') . ' | ' . $result);
    }

    /**
     * Kirim PDF rekap bulanan (presensi + denda) untuk bulan lalu.
     * Dijadwalkan tiap tanggal 1 (lihat CRONTAB.MD).
     * GET /cron/monthly-pdf?key=...&bulan=9&tahun=2026&force=1
     *
     * Tanpa ?bulan=&tahun=, periode = bulan kalender sebelumnya (dipanggil
     * tgl 1 Oktober → PDF September). Parameter eksplisit untuk backfill
     * manual (mis. bulan yang terlewat karena gateway down).
     *
     * PDF yang dikirim adalah render yang SAMA dengan tombol manual
     * "Kirim ke WA & TG" (RekapPdfService) — bukan dokumen terpisah.
     * Masing-masing PDF menghormati switch channel-nya sendiri di
     * notif-config ('rekap_bulanan' vs 'rekap_denda_bulanan').
     */
    public function monthlyPdf()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        [$bulan, $tahun, $paramError] = $this->targetPeriodePdf();
        if ($paramError !== null) {
            return $this->response->setStatusCode(400)->setBody('PARAM: ' . $paramError);
        }

        $force   = $this->dimintaForce();
        $periode = sprintf('%04d-%02d', $tahun, $bulan);
        $label   = $this->namaBulanIndonesia((int) $bulan) . ' ' . $tahun;

        $pdfService = new \App\Services\RekapPdfService();
        $baris      = [];

        // Presensi — PDF landscape per guru
        $baris[] = $this->kirimSatuPdfBulanan(
            $pdfService,
            'pdf_rekap_bulanan',   // kunci idempotensi di cron_notifikasi_log
            'rekap_bulanan',       // switch channel di notif-config
            $periode,
            $label,
            'presensi',
            static fn() => $pdfService->buildRekapBulananPdf($bulan, $tahun),
            "<b>📋 REKAP PRESENSI GURU</b>\nPeriode: {$label}",
            'rekap_presensi_',
            $force
        );

        // Denda — PDF portrait per guru
        $baris[] = $this->kirimSatuPdfBulanan(
            $pdfService,
            'pdf_denda_bulanan',
            'rekap_denda_bulanan',
            $periode,
            $label,
            'denda',
            static fn() => $pdfService->buildDendaPdf($bulan, $tahun),
            "<b>📄 REKAP DENDA PRESENSI</b>\nPeriode: {$label}",
            'rekap_denda_',
            $force
        );

        $gabungan = implode("\n", $baris);
        $gagal    = $this->isSendFailure($gabungan);

        return $this->response
            ->setStatusCode($gagal ? 500 : 200)
            ->setBody('Monthly PDF ' . $label . ': ' . date('Y-m-d H:i:s') . "\n" . $gabungan);
    }

    /**
     * Tentukan periode PDF: eksplisit (?bulan=&tahun=) atau bulan lalu.
     *
     * @return array{string,string,string|null} [bulan 'm', tahun 'Y', error]
     */
    private function targetPeriodePdf(): array
    {
        $b = $this->request->getGet('bulan');
        $t = $this->request->getGet('tahun');

        if ($b === null && $t === null) {
            return [...self::bulanLaluDari(date('Y-m-d')), null];
        }

        if ($b === null || $t === null) {
            return ['', '', 'isi ?bulan= dan ?tahun= sekaligus, atau kosongkan keduanya untuk bulan lalu.'];
        }

        $bulan = (int) $b;
        $tahun = (int) $t;
        if ($bulan < 1 || $bulan > 12 || $tahun < 2000 || $tahun > 2100) {
            return ['', '', 'bulan harus 1-12 dan tahun antara 2000 dan 2100.'];
        }

        return [sprintf('%02d', $bulan), (string) $tahun, null];
    }

    /**
     * Bulan kalender sebelum tanggal acuan. Murni (tanpa I/O) supaya bisa
     * diuji unit: '2026-01-15' → ['12', '2025'], '2026-03-31' → ['02', '2026'].
     *
     * Jangkar ke tgl 1 dulu: strtotime('-1 month') pada tgl 31 melompat
     * (31 Mar − 1 bulan = 3 Mar, bukan Februari).
     *
     * @return array{string,string} [bulan 'm', tahun 'Y']
     */
    public static function bulanLaluDari(string $ymd): array
    {
        $jangkar = date('Y-m-01', strtotime($ymd));
        $prev    = strtotime($jangkar . ' -1 month');

        return [date('m', $prev), date('Y', $prev)];
    }

    /**
     * Bangun + kirim SATU pdf bulanan, dengan idempotensi + channel guard.
     *
     * @param callable $bangun  Tanpa argumen, mengembalikan array hasil builder
     *                          ['pdf'=>string,'filename'=>string,'jumlah_guru'=>int]
     * @return string Satu baris log (jujur: GAGAL hanya bila benar-benar gagal)
     */
    private function kirimSatuPdfBulanan(
        \App\Services\RekapPdfService $pdfService,
        string $jenisLog,
        string $jenisChannel,
        string $periode,
        string $label,
        string $namaJenis,
        callable $bangun,
        string $caption,
        string $prefixFile,
        bool $force
    ): string {
        if ($skip = $this->grupDinonaktifkan($jenisChannel, 'PDF ' . $namaJenis . ' ' . $label)) {
            return $skip;
        }

        if (! $force && $this->notifikasiCronTerkirim($jenisLog, $periode)) {
            return "PDF {$namaJenis} {$label} sudah terkirim sebelumnya, dilewati. "
                . 'Gunakan /cron/monthly-pdf?force=1 untuk mengirim ulang.';
        }

        try {
            $hasil = $bangun();
        } catch (\Throwable $e) {
            $this->logCronFailure('PDF ' . $namaJenis . ' ' . $label, $e->getMessage());
            $this->catatNotifikasiCron($jenisLog, $periode, false, $e->getMessage());

            return "PDF {$namaJenis} {$label} GAGAL disusun: " . $e->getMessage();
        }

        if (($hasil['jumlah_guru'] ?? 0) === 0) {
            $alasan = 'tidak ada guru aktif — PDF kosong, tidak dikirim';
            $this->logCronFailure('PDF ' . $namaJenis . ' ' . $label, $alasan);
            $this->catatNotifikasiCron($jenisLog, $periode, false, $alasan);

            return "PDF {$namaJenis} {$label} GAGAL: " . $alasan . '.';
        }

        // PDF perlu file di disk: Telegram multipart dan OpenWA membaca
        // dari path, bukan dari string. Nama acak supaya tidak bisa ditebak
        // dari luar dan tidak bentrok antar request.
        $dir = WRITEPATH . 'uploads';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . $prefixFile . bin2hex(random_bytes(8)) . '.pdf';

        try {
            if (@file_put_contents($path, $hasil['pdf']) === false) {
                throw new \RuntimeException('tidak bisa menulis PDF sementara ke disk.');
            }

            $kirim = $this->notification->sendDokumenToGrup(
                $jenisChannel,
                $path,
                $hasil['filename'],
                $caption . "\n" . 'Total guru: ' . $hasil['jumlah_guru'] . ' orang'
            );
        } catch (\Throwable $e) {
            $this->logCronFailure('PDF ' . $namaJenis . ' ' . $label, $e->getMessage());
            $this->catatNotifikasiCron($jenisLog, $periode, false, $e->getMessage());

            return "PDF {$namaJenis} {$label} GAGAL: " . $e->getMessage();
        } finally {
            // PDF memuat data tiap guru — hapus segera, di semua jalur.
            if (isset($path) && is_file($path)) {
                @unlink($path);
            }
        }

        if (! ($kirim['telegram'] || $kirim['whatsapp'])) {
            $alasan = 'Telegram: ' . ($kirim['telegram_error'] ?? '?')
                . ' | WhatsApp: ' . ($kirim['whatsapp_error'] ?? '?');
            $this->logCronFailure('PDF ' . $namaJenis . ' ' . $label, $alasan);
            $this->catatNotifikasiCron($jenisLog, $periode, false, $alasan);

            return "PDF {$namaJenis} {$label} GAGAL: " . $alasan;
        }

        $this->catatNotifikasiCron($jenisLog, $periode, true);

        $lewat = [];
        if ($kirim['telegram']) {
            $lewat[] = 'Telegram';
        }
        if ($kirim['whatsapp']) {
            $lewat[] = 'WhatsApp';
        }

        return "PDF {$namaJenis} {$label} terkirim via " . implode(' + ', $lewat)
            . " ({$hasil['jumlah_guru']} guru).";
    }

    private function namaBulanIndonesia(int $bulan): string
    {
        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $nama[$bulan] ?? (string) $bulan;
    }

    /**
     * Apakah string hasil kirim menandakan kegagalan?
     *
     * Dipakai agar endpoint cron tidak pernah balas HTTP 200 untuk pengiriman
     * yang gagal — pemantau cron menganggap sukses bila tidak ada HTTP 5xx.
     */
    private function isSendFailure(string $result): bool
    {
        return str_contains($result, 'GAGAL')
            || str_contains($result, 'gagal')
            || str_contains($result, 'Gagal');
    }

    /**
     * Catat aksi cron ke audit_logs.
     *
     * `table_name` diisi 'cron' dan `record_id` 0: aksi cron tidak
     * mengubah satu baris tabel tertentu, jadi tidak ada record yang bisa
     * direferensikan. Yang penting jejaknya terekam dan bisa dibaca admin.
     *
     * Semua kegagalan ditelan diam-diam dengan log — audit tidak boleh
     * menggagalkan proses yang sedang berjalan.
     */
    private function catatAudit(string $action, ?int $recordId = null): void
    {
        try {
            if (! class_exists(\App\Models\AuditLogModel::class)) {
                return;
            }
            $model = new \App\Models\AuditLogModel();
            if (! method_exists($model, 'log')) {
                // Nama method pernah beda antar versi; jangan diam-diam
                // hilang jejak tanpa memberi tahu.
                log_message('debug', '[Cron] AuditLogModel tidak punya method log(); audit dilewati.');
                return;
            }
            $model->log('cron', $recordId ?? 0, $action, null, null, null, null, 'cron otomatis');
        } catch (\Throwable $e) {
            log_message('error', '[Cron] gagal menulis audit log: ' . $e->getMessage());
        }
    }

    /**
     * Ucapan pagi pukul 06:00 — SELALU kirim, kalimat menyesuaikan hari.
     * GET /cron/ucapan-pagi?key=...
     *
     * Status hari dari HariSekolahService (sumber yang SAMA dengan ucapan
     * sore dan markAbsentTeachers): hari kerja / Minggu / libur.
     * Bedanya dengan sore: pagi tetap dikirim di hari libur (dengan
     * kalimat libur), sore dilewati total.
     * Param `?tanggal=YYYY-MM-DD` dipakai untuk menguji varian lain tanpa
     * harus menunggu hari tersebut — tetap mengirim pesan sungguhan.
     */
    public function ucapanPagi()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        $tanggal = $this->request->getGet('tanggal');
        $tanggal = is_string($tanggal) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) === 1
            ? $tanggal
            : date('Y-m-d');

        if ($skip = $this->grupDinonaktifkan('ucapan_pagi', 'Ucapan pagi')) {
            return $this->response->setBody($skip);
        }

        // `?tanggal=` membuat endpoint ini bisa diputar ulang untuk tanggal
        // mana pun, dan setiap pemanggilan tetap mengirim pesan sungguhan.
        // Penjaga idempotensi mencegah pengulangan yang tidak disengaja;
        // ?force=1 tetap tersedia untuk uji varian yang memang dimaksudkan.
        if (! $this->dimintaForce() && $this->notifikasiCronTerkirim('ucapan_pagi', $tanggal)) {
            return $this->response->setBody('Ucapan pagi untuk ' . $tanggal
                . ' sudah terkirim sebelumnya, dilewati. Gunakan ?force=1 untuk mengirim ulang.');
        }

        $svc  = new HariSekolahService();
        $info = $svc->status($tanggal);

        $info['tanggal_panjang'] = $svc->tanggalPanjang($tanggal);
        $info['jam_masuk']       = $this->jamMasukHariIni($tanggal);

        $peringatan = $svc->peringatanKalender($tanggal);
        if ($peringatan !== null) {
            log_message('warning', '[UcapanPagi] ' . $peringatan);
        }

        try {
            $sent = $this->notification->notifyUcapanPagi($info);
        } catch (\Throwable $e) {
            $this->logCronFailure('Ucapan pagi', $e->getMessage());
            $this->catatNotifikasiCron('ucapan_pagi', $tanggal, false, $e->getMessage());

            return $this->response->setStatusCode(500)->setBody('Ucapan pagi GAGAL: ' . $e->getMessage());
        }

        if ($sent !== true) {
            $reason = $this->notification->lastError !== ''
                ? $this->notification->lastError
                : 'Telegram tidak menerima (tanpa keterangan)';
            $this->logCronFailure('Ucapan pagi', $reason);
            $this->catatNotifikasiCron('ucapan_pagi', $tanggal, false, $reason);

            return $this->response->setStatusCode(500)->setBody('Ucapan pagi GAGAL: ' . $reason);
        }

        $this->catatNotifikasiCron('ucapan_pagi', $tanggal, true);

        return $this->response->setBody(sprintf(
            'Ucapan pagi terkirim — %s (%s) %s',
            $info['tanggal_panjang'],
            $info['status'],
            $peringatan !== null ? '| PERINGATAN: ' . $peringatan : ''
        ));
    }

    /**
     * Ucapan pulang pukul 15:00 — HANYA di hari kerja sekolah.
     * GET /cron/ucapan-sore?key=...
     *
     * Tidak ada "selamat jalan / hati-hati pulang" di hari libur atau
     * akhir pekan: tidak ada kegiatan sekolah yang selesai hari itu,
     * jadi pesannya salah alamat. Aturan bisnis (bukan teknis), karena
     * itu `?force=1` tidak bisa melewatinya — force hanya melewati
     * penjaga "sudah terkirim", sama seperti skip tanggal evaluasi
     * surat teguran.
     */
    public function ucapanSore()
    {
        if (!$this->validateKey()) {
            return $this->response->setStatusCode(403)->setBody('Invalid key');
        }

        $tanggal = date('Y-m-d');
        $svc     = new HariSekolahService();
        $info    = ['tanggal_panjang' => $svc->tanggalPanjang($tanggal)];

        // Sumber status yang SAMA dengan ucapan pagi (mingguan → jadwal →
        // kalender). Bukan hari kerja = tidak ada yang perlu dipulangkan.
        $statusHari = $svc->status($tanggal);
        if (($statusHari['status'] ?? HariSekolahService::AKTIF) !== HariSekolahService::AKTIF) {
            return $this->response->setBody(
                'Ucapan sore dilewati: ' . $info['tanggal_panjang']
                . ' bukan hari kerja sekolah (' . ($statusHari['keterangan'] ?? $statusHari['status']) . ').'
            );
        }

        if ($skip = $this->grupDinonaktifkan('ucapan_sore', 'Ucapan sore')) {
            return $this->response->setBody($skip);
        }

        if (! $this->dimintaForce() && $this->notifikasiCronTerkirim('ucapan_sore', $tanggal)) {
            return $this->response->setBody('Ucapan sore untuk ' . $tanggal
                . ' sudah terkirim sebelumnya, dilewati. Gunakan ?force=1 untuk mengirim ulang.');
        }

        try {
            $sent = $this->notification->notifyUcapanSore($info);
        } catch (\Throwable $e) {
            $this->logCronFailure('Ucapan sore', $e->getMessage());
            $this->catatNotifikasiCron('ucapan_sore', $tanggal, false, $e->getMessage());

            return $this->response->setStatusCode(500)->setBody('Ucapan sore GAGAL: ' . $e->getMessage());
        }

        if ($sent !== true) {
            $reason = $this->notification->lastError !== ''
                ? $this->notification->lastError
                : 'Telegram tidak menerima (tanpa keterangan)';
            $this->logCronFailure('Ucapan sore', $reason);
            $this->catatNotifikasiCron('ucapan_sore', $tanggal, false, $reason);

            return $this->response->setStatusCode(500)->setBody('Ucapan sore GAGAL: ' . $reason);
        }

        $this->catatNotifikasiCron('ucapan_sore', $tanggal, true);

        return $this->response->setBody('Ucapan sore terkirim — ' . $info['tanggal_panjang']);
    }

    /**
     * Jam masuk efektif pada tanggal tertentu (dari jadwal per hari, atau
     * jam_masuk bawaan bila jadwal tidak ada).
     */
    private function jamMasukHariIni(string $tanggal): string
    {
        try {
            $db = \Config\Database::connect();
            $iso = (int) date('N', strtotime($tanggal));
            if ($db->tableExists('absensi_jadwal_kerja')) {
                $row = $db->table('absensi_jadwal_kerja')->where('hari', $iso)->get()->getRow();
                if ($row !== null && ! empty($row->jam_masuk)) {
                    return substr((string) $row->jam_masuk, 0, 5);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[UcapanPagi] gagal membaca jam masuk: ' . $e->getMessage());
        }

        $setting = $this->settingModel->first();

        return ! empty($setting->jam_masuk) ? substr((string) $setting->jam_masuk, 0, 5) : '';
    }

    /**
     * Hitung dan kirim rekap denda mingguan (Senin–Sabtu).
     *
     * Rekap denda teks BULANAN (tgl 28) sudah dihapus: versi PDF-nya
     * (tgl 1 via monthlyPdf) yang dipakai. Method ini khusus mingguan.
     *
     * @param string $startDate Format Y-m-d (Senin)
     * @param string $endDate   Format Y-m-d (Sabtu)
     * @param string $periode   Label periode untuk ditampilkan
     * @return string Log message
     */
    private function sendDendaRecap(string $startDate, string $endDate, string $periode, bool $force = false): string
    {
        $db      = \Config\Database::connect();
        $setting = $this->settingModel->first();

        if (!$setting) {
            return 'GAGAL: tabel absensi_setting kosong — konfigurasi presensi belum dibuat, jalankan php spark migrate dan isi lewat Pengaturan.';
        }

        if ($skip = $this->grupDinonaktifkan('rekap_denda_mingguan', 'Rekap denda mingguan')) {
            return $skip;
        }

        // Penanda periode memakai tanggal akhir, bukan label yang dikirim ke
        // pesan: label bisa berisi nama bulan bahasa Inggris untuk periode
        // yang tidak valid, sehingga tidak bisa dipakai sebagai kunci unik
        // yang stabil.
        $kunciPeriode = 'denda_mingguan_' . $endDate;

        if (! $force && $this->notifikasiCronTerkirim('rekap_denda', $kunciPeriode)) {
            return "Rekap denda mingguan untuk periode {$periode} sudah terkirim sebelumnya, dilewati.";
        }

        // Ambil semua guru aktif beserta nama
        $guruList = $db->table('users u')
            ->select('u.id, g.nama_guru')
            ->join('master_guru g', 'g.id_user = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->get()->getResult();

        if (empty($guruList)) {
            return 'GAGAL: tidak ada guru aktif di akun ini — cek filter group pengguna.';
        }

        // 1. Denda timbul minggu ini, kanonik: harian tercatat + estimasi
        //    alpha bolong (satu estimator batch, anti double-count, cap
        //    konsisten dengan tabel admin/YTD). Tanpa ini, alpha yang belum
        //    dicatat cron tidak masuk rekap sehingga total selalu lebih kecil
        //    dari daftar denda.
        $dendaHarianRaw = $db->query("
            SELECT user_id, SUM(COALESCE(denda, 0)) as total
            FROM absensi_harian
            WHERE tgl >= ? AND tgl <= ?
            GROUP BY user_id
        ", [$startDate, $endDate])->getResult();
        $dendaHarianMap = [];
        foreach ($dendaHarianRaw as $r) {
            $dendaHarianMap[(int) $r->user_id] = (float) $r->total;
        }

        $estimasiMingguMap = [];
        try {
            $uidsEstimasi = array_map(static fn($g) => (int) $g->id, $guruList);
            $estimasiBatch = (new \App\Services\AttendancePenaltyService())
                ->missingAlphaFinesBatch($uidsEstimasi, $startDate, $endDate, $setting, false);
            foreach ($estimasiBatch as $uidEstimasi => $ringkasEstimasi) {
                $estimasiMingguMap[(int) $uidEstimasi] = (float) ($ringkasEstimasi['total'] ?? 0);
            }
        } catch (\Throwable $e) {
            log_message('error', '[RekapDendaMingguan] estimasi alpha gagal: ' . $e->getMessage());
        }

        // 2. Total pembayaran per guru dalam periode (kas: tanggal bayar).
        //    Sama dengan laporan pembayaran (basis kas). Voucher yang sudah
        //    menjadi baris pembayaran ikut terhitung di sini; APPROVED yang
        //    belum di-apply BUKAN pembayaran — sebelumnya dihitung ganda
        //    (di pembayaran + di redemption) sehingga dibayar terlihat
        //    lebih besar dari kas sebenarnya.
        $dibayarMap = [];
        if ($db->tableExists('absensi_pembayaran_denda')) {
            // Pembayaran berdasarkan tanggal bayar dalam rentang
            $hasTglBayar = $db->fieldExists('tanggal_bayar', 'absensi_pembayaran_denda');
            $hasNominalBayar = $db->fieldExists('nominal_bayar', 'absensi_pembayaran_denda');

            if ($hasTglBayar) {
                $nominalCol = $hasNominalBayar
                    ? 'COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)'
                    : 'COALESCE(total_denda, 0)';
                $bayarRaw = $db->query("
                    SELECT user_id, SUM({$nominalCol}) as total
                    FROM absensi_pembayaran_denda
                    WHERE tanggal_bayar >= ? AND tanggal_bayar <= ?
                      AND status != 'batal'
                    GROUP BY user_id
                ", [$startDate, $endDate])->getResult();
            } else {
                // Fallback: gunakan bulan/tahun
                $bln = (int) date('m', strtotime($startDate));
                $thn = (int) date('Y', strtotime($startDate));
                $nominalCol = $hasNominalBayar
                    ? 'COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)'
                    : 'COALESCE(total_denda, 0)';
                $bayarRaw = $db->query("
                    SELECT user_id, SUM({$nominalCol}) as total
                    FROM absensi_pembayaran_denda
                    WHERE bulan = ? AND tahun = ? AND status != 'batal'
                    GROUP BY user_id
                ", [$bln, $thn])->getResult();
            }

            foreach ($bayarRaw as $r) {
                $dibayarMap[(int) $r->user_id] = (float) $r->total;
            }
        }

        // 3. Sisa kanonik lintas tahun per guru (sumber tunggal yang sama
        //    dengan tabel admin & bayar nominal): harian + estimasi bolong
        //    dikurangi pembayaran, kredit FIFO. Satu estimator batch untuk
        //    semua guru, bukan N× pindai penuh.
        $sisaKanonikMap = [];
        try {
            $uidsSisa = array_map(static fn($g) => (int) $g->id, $guruList);
            $sisaKanonikMap = (new \App\Services\AttendancePenaltyService())->sisaDendaBatch($uidsSisa);
        } catch (\Throwable $e) {
            log_message('error', '[RekapDendaMingguan] sisa kanonik gagal: ' . $e->getMessage());
        }

        // 4. Compile per guru
        $globalTotalDenda   = 0.0;
        $globalTotalDibayar = 0.0;
        $globalSisa         = 0.0;
        $details = [];

        foreach ($guruList as $guru) {
            $uid          = (int) $guru->id;
            // Timbul minggu ini (kanonik) vs dibayar minggu ini (kas) vs
            // sisa akumulasi (kanonik lintas tahun, sama dengan tabel admin).
            $totalDenda   = ($dendaHarianMap[$uid] ?? 0.0) + ($estimasiMingguMap[$uid] ?? 0.0);
            $totalDibayar = $dibayarMap[$uid] ?? 0.0;
            // Fallback minggu (basis lama) hanya bila hitung kanonik gagal.
            $sisa         = array_key_exists($uid, $sisaKanonikMap)
                ? (float) $sisaKanonikMap[$uid]
                : max(0.0, $totalDenda - $totalDibayar);

            $globalTotalDenda   += $totalDenda;
            $globalTotalDibayar += $totalDibayar;
            $globalSisa         += $sisa;

            $details[] = [
                'nama'          => $guru->nama_guru ?? 'Guru',
                'total_denda'   => $totalDenda,
                'total_dibayar' => $totalDibayar,
                'sisa'          => $sisa,
            ];
        }

        $summary = [
            'total_denda'   => $globalTotalDenda,
            'total_dibayar' => $globalTotalDibayar,
            'sisa'          => $globalSisa,
        ];

        // 5. Kirim notifikasi
        try {
            $sent = $this->notification->notifyRekapDendaMingguan($summary, $details, $periode);
        } catch (\Throwable $e) {
            $this->logCronFailure('Rekap denda mingguan', $e->getMessage());
            $this->catatNotifikasiCron('rekap_denda', $kunciPeriode, false, $e->getMessage());

            return 'Notifikasi gagal: ' . $e->getMessage();
        }

        $guruDenda = count(array_filter($details, static fn($d) => $d['total_denda'] > 0));
        $ringkasan = "rekap denda mingguan ({$guruDenda} guru kena denda, "
            . 'timbul Rp ' . number_format($globalTotalDenda, 0, ',', '.') . ', '
            . 'sisa akumulasi Rp ' . number_format($globalSisa, 0, ',', '.') . ')';

        if ($sent !== true) {
            $reason = $this->notification->lastError !== ''
                ? $this->notification->lastError
                : 'Telegram tidak menerima (tanpa keterangan)';
            $this->logCronFailure('Rekap denda mingguan', $reason);
            $this->catatNotifikasiCron('rekap_denda', $kunciPeriode, false, $reason);

            return "GAGAL kirim {$ringkasan} — {$reason}";
        }

        $this->catatNotifikasiCron('rekap_denda', $kunciPeriode, true);

        return "Terkirim {$ringkasan}";
    }
}
