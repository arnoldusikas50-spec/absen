<?php

namespace App\Controllers\Admin;

use App\Models\DashboardModel;
use App\Models\KalenderModel;
use App\Services\HariSekolahService;

/**
 * Kalender Akademik & Hari Libur.
 *
 * Dua sumber data yang sengaja dipisah:
 * - kalender_akademik : agenda sekolah (event, ujian, rapat, ...).
 * - absensi_hari_libur: tanggal merah/libur sekolah yang menonaktifkan
 *   alfa & denda absensi guru.
 *
 * Endpoint get-events menggabungkan keduanya untuk FullCalendar, dengan
 * hari libur absensi dikirim sebagai event merah yang tidak dapat diedit
 * dari kalender (kelola lewat tab Hari Libur).
 */
class Kalender extends \App\Controllers\BaseController
{
    private const TIPE_EVENT = ['EVENT', 'UJIAN', 'LIBUR', 'RAPAT', 'DINAS', 'LAINNYA'];

    private const TIPE_WARNA = [
        'EVENT'   => '#3b82f6',
        'UJIAN'   => '#8b5cf6',
        'LIBUR'   => '#ef4444',
        'RAPAT'   => '#f59e0b',
        'DINAS'   => '#06b6d4',
        'LAINNYA' => '#64748b',
    ];

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    protected KalenderModel $kalenderModel;
    protected $db;

    public function __construct()
    {
        $this->kalenderModel = new KalenderModel();
        $this->db = \Config\Database::connect();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HALAMAN UTAMA
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        if (! $this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        [$bulan, $tahun] = $this->bulanTahunDariRequest();
        $tglAwal  = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        $events        = $this->kalenderModel->getEventsByRange($tglAwal, $tglAkhir);
        $holidays      = $this->daftarHariLibur();
        $holidaysBulan = $this->kalenderModel->getHolidaysByRange($tglAwal, $tglAkhir);

        $dashboardModel = new DashboardModel();
        $setting        = $dashboardModel->getSetting();
        $hariService    = new HariSekolahService();

        $weeklyOff    = $hariService->hariLiburMingguan();
        $weeklyOffIso = array_column($weeklyOff, 'iso');
        $jadwalLibur  = $this->jadwalKerjaLiburIso();
        $liburIso     = array_values(array_unique(array_merge($weeklyOffIso, $jadwalLibur)));

        $liburTahun  = 0;
        $cutiTahun   = 0;
        $tanggalLibur = [];
        foreach ($holidays as $h) {
            if (substr((string) $h->tanggal, 0, 4) !== (string) $tahun) {
                continue;
            }
            $liburTahun++;
            $tanggalLibur[$h->tanggal] = true;
            if (stripos((string) $h->keterangan, 'cuti') !== false) {
                $cutiTahun++;
            }
        }

        $totalHari   = (int) date('t', strtotime($tglAwal));
        $hariSekolah = $this->hitungHariSekolah($tahun, $bulan, $weeklyOffIso, $jadwalLibur, $tanggalLibur);

        $peringatan = null;
        if ($liburTahun === 0) {
            $peringatan = 'Belum ada hari libur terdaftar untuk tahun ' . $tahun
                . '. Sinkronkan libur nasional agar absensi dan dashboard mengenali tanggal merah.';
        }

        return view('admin/kalender/index', [
            'events'         => $events,
            'holidays'       => $holidays,
            'holidays_bulan' => $holidaysBulan,
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'nama_bulan'     => self::NAMA_BULAN[$bulan],
            'today_status'   => $hariService->status(),
            'weekly_off'     => $weeklyOff,
            'libur_iso'      => $liburIso,
            'stats'          => [
                'event_bulan'  => count($events),
                'libur_bulan'  => count($holidaysBulan),
                'hari_sekolah' => $hariSekolah,
                'total_hari'   => $totalHari,
                'libur_tahun'  => $liburTahun,
                'cuti_tahun'   => $cutiTahun,
            ],
            'peringatan'     => $peringatan,
            'setting'        => $setting ?? (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'],
            'tp_active'      => $dashboardModel->getTahunActive(),
            'smt_active'     => $dashboardModel->getSemesterActive(),
            'user'           => $this->getUser(),
            'judul'          => 'Kalender & Hari Libur',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KALENDER AKADEMIK — CRUD
    // ─────────────────────────────────────────────────────────────────────────

    private function sanitizeEventInput(): array
    {
        $judul = mb_substr(trim((string) ($this->request->getPost('judul') ?? '')), 0, 255);
        $tipe  = strtoupper(trim((string) ($this->request->getPost('tipe') ?? 'EVENT')));
        if (! in_array($tipe, self::TIPE_EVENT, true)) {
            $tipe = 'EVENT';
        }

        $warna = trim((string) ($this->request->getPost('warna') ?? ''));
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $warna)) {
            $warna = self::TIPE_WARNA[$tipe] ?? '#3b82f6';
        }

        $deskripsi = mb_substr(trim((string) ($this->request->getPost('deskripsi') ?? '')), 0, 2000);

        return [$judul, $tipe, $warna, $deskripsi];
    }

    public function create()
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        [$judul, $tipe, $warna, $deskripsi] = $this->sanitizeEventInput();
        [$tglMulai, $tglSelesai, $error] = $this->validasiTanggalEvent();

        if ($judul === '') {
            return $this->respond(false, 'Judul event wajib diisi');
        }
        if ($error !== null) {
            return $this->respond(false, $error);
        }

        $id = $this->kalenderModel->insert([
            'tgl_mulai'   => $tglMulai,
            'tgl_selesai' => $tglSelesai,
            'judul'       => $judul,
            'deskripsi'   => $deskripsi,
            'tipe'        => $tipe,
            'warna'       => $warna,
            'created_by'  => (int) (session()->get('user_id') ?? 0),
        ]);

        return $this->respond(true, 'Event berhasil ditambahkan', ['id' => (int) $id]);
    }

    public function update($id)
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $event = $this->kalenderModel->find((int) $id);
        if (! $event) {
            return $this->respond(false, 'Event tidak ditemukan');
        }

        [$judul, $tipe, $warna, $deskripsi] = $this->sanitizeEventInput();
        [$tglMulai, $tglSelesai, $error] = $this->validasiTanggalEvent();

        if ($judul === '') {
            return $this->respond(false, 'Judul event wajib diisi');
        }
        if ($error !== null) {
            return $this->respond(false, $error);
        }

        $this->kalenderModel->update((int) $id, [
            'tgl_mulai'   => $tglMulai,
            'tgl_selesai' => $tglSelesai,
            'judul'       => $judul,
            'deskripsi'   => $deskripsi,
            'tipe'        => $tipe,
            'warna'       => $warna,
        ]);

        return $this->respond(true, 'Event berhasil diperbarui', ['id' => (int) $id]);
    }

    public function delete($id)
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $event = $this->kalenderModel->find((int) $id);
        if (! $event) {
            return $this->respond(false, 'Event tidak ditemukan');
        }

        $this->kalenderModel->delete((int) $id);
        return $this->respond(true, 'Event berhasil dihapus');
    }

    public function getEvent($id)
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $event = $this->kalenderModel->find((int) $id);
        if (! $event) {
            return $this->respond(false, 'Event tidak ditemukan');
        }

        return $this->respond(true, 'OK', $event);
    }

    /**
     * Feed FullCalendar. Harus mengembalikan array event mentah (bukan
     * envelope {status,message,data}) agar dapat dibaca FullCalendar.
     */
    public function getEvents()
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $start = (string) ($this->request->getGet('start') ?? '');
        $end   = (string) ($this->request->getGet('end') ?? '');

        if ($this->validDate($start) && $this->validDate($end)) {
            $tglAwal  = $start;
            // end FullCalendar bersifat eksklusif
            $tglAkhir = date('Y-m-d', strtotime($end . ' -1 day'));
            if ($tglAkhir < $tglAwal) {
                $tglAkhir = $tglAwal;
            }
        } else {
            [$bulan, $tahun] = $this->bulanTahunDariRequest();
            $tglAwal  = sprintf('%04d-%02d-01', $tahun, $bulan);
            $tglAkhir = date('Y-m-t', strtotime($tglAwal));
        }

        $events   = $this->kalenderModel->getEventsByRange($tglAwal, $tglAkhir);
        $holidays = $this->kalenderModel->getHolidaysByRange($tglAwal, $tglAkhir);

        $result = [];
        $sudahAda = [];
        foreach ($events as $e) {
            $result[] = $this->payloadEvent($e);
            $sudahAda[$e->tgl_mulai . '|' . mb_strtolower(trim((string) $e->judul))] = true;
        }

        foreach ($holidays as $h) {
            $kunci = $h->tanggal . '|' . mb_strtolower(trim((string) $h->keterangan));
            if (isset($sudahAda[$kunci])) {
                continue;
            }
            $result[] = $this->payloadHariLibur($h);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON($result);
    }

    private function payloadEvent(object $e): array
    {
        $warna = (string) ($e->warna ?: (self::TIPE_WARNA[$e->tipe] ?? '#3b82f6'));

        $payload = [
            'id'              => 'ev_' . $e->id,
            'title'           => $e->judul,
            'start'           => $e->tgl_mulai,
            'allDay'          => true,
            'backgroundColor' => $warna,
            'borderColor'     => $warna,
            'textColor'       => $this->warnaTeksKontras($warna),
            'classNames'      => ['kalender-event'],
            'extendedProps'   => [
                'sumber'      => 'kalender',
                'event_id'    => (int) $e->id,
                'tipe'        => $e->tipe,
                'description' => (string) ($e->deskripsi ?? ''),
                'tgl_mulai'   => $e->tgl_mulai,
                'tgl_selesai' => $e->tgl_selesai,
            ],
        ];

        if (! empty($e->tgl_selesai)) {
            // FullCalendar all-day: end eksklusif
            $payload['end'] = date('Y-m-d', strtotime($e->tgl_selesai . ' +1 day'));
        }

        return $payload;
    }

    private function payloadHariLibur(object $h): array
    {
        return [
            'id'              => 'libur_' . $h->tanggal,
            'title'           => $h->keterangan,
            'start'           => $h->tanggal,
            'allDay'          => true,
            'backgroundColor' => '#fee2e2',
            'borderColor'     => '#fca5a5',
            'textColor'       => '#b91c1c',
            'classNames'      => ['kalender-libur'],
            'extendedProps'   => [
                'sumber'      => 'absensi_libur',
                'holiday_id'  => (int) $h->id,
                'tipe'        => 'LIBUR',
                'description' => 'Hari libur: ' . $h->keterangan,
                'tgl_mulai'   => $h->tanggal,
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HARI LIBUR (absensi_hari_libur)
    // ─────────────────────────────────────────────────────────────────────────

    public function saveHoliday()
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id         = (int) ($this->request->getPost('id') ?? 0);
        $isRange    = $this->request->getPost('is_range') === '1';
        $keterangan = trim((string) ($this->request->getPost('keterangan') ?? ''));

        if ($keterangan === '') {
            return $this->respond(false, 'Keterangan hari libur wajib diisi');
        }
        $keterangan = mb_substr($keterangan, 0, 255);

        // ── Mode EDIT ─────────────────────────────────────────────────────
        if ($id > 0) {
            $existing = $this->db->table('absensi_hari_libur')->where('id', $id)->get()->getRow();
            if (! $existing) {
                return $this->respond(false, 'Data tidak ditemukan');
            }

            $tanggal = trim((string) $this->request->getPost('tanggal'));
            if (! $this->validDate($tanggal)) {
                return $this->respond(false, 'Format tanggal tidak valid');
            }

            $conflict = $this->db->table('absensi_hari_libur')
                ->where('tanggal', $tanggal)
                ->where('id !=', $id)
                ->countAllResults();
            if ($conflict > 0) {
                return $this->respond(false, 'Tanggal ' . date('d/m/Y', strtotime($tanggal)) . ' sudah terdaftar sebagai hari libur lain');
            }

            $this->db->table('absensi_hari_libur')->where('id', $id)->update([
                'tanggal'    => $tanggal,
                'keterangan' => $keterangan,
            ]);

            return $this->respond(true, 'Data hari libur berhasil diperbarui');
        }

        // ── Mode TAMBAH RENTANG ───────────────────────────────────────────
        if ($isRange) {
            $tglMulai   = trim((string) $this->request->getPost('tanggal_mulai'));
            $tglSelesai = trim((string) $this->request->getPost('tanggal_selesai'));

            if (! $this->validDate($tglMulai) || ! $this->validDate($tglSelesai)) {
                return $this->respond(false, 'Tanggal mulai dan tanggal selesai harus diisi dengan format yang benar');
            }
            if ($tglMulai > $tglSelesai) {
                return $this->respond(false, 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai');
            }

            $diffDays = (int) round((strtotime($tglSelesai) - strtotime($tglMulai)) / 86400) + 1;
            if ($diffDays > 366) {
                return $this->respond(false, 'Rentang tanggal tidak boleh lebih dari 366 hari sekaligus');
            }

            $existingRows = $this->db->table('absensi_hari_libur')
                ->where('tanggal >=', $tglMulai)
                ->where('tanggal <=', $tglSelesai)
                ->get()->getResult();
            $existingMap = [];
            foreach ($existingRows as $row) {
                $existingMap[$row->tanggal] = $row;
            }

            $inserted = 0;
            $updated  = 0;
            $current  = strtotime($tglMulai);
            $endTs    = strtotime($tglSelesai);

            $this->db->transBegin();
            try {
                while ($current <= $endTs) {
                    $curDate = date('Y-m-d', $current);
                    if (isset($existingMap[$curDate])) {
                        if ($existingMap[$curDate]->keterangan !== $keterangan) {
                            $this->db->table('absensi_hari_libur')
                                ->where('id', $existingMap[$curDate]->id)
                                ->update(['keterangan' => $keterangan]);
                            $updated++;
                        }
                    } else {
                        $this->db->table('absensi_hari_libur')->insert([
                            'tanggal'    => $curDate,
                            'keterangan' => $keterangan,
                        ]);
                        $inserted++;
                    }
                    $current = strtotime('+1 day', $current);
                }
                $this->db->transCommit();
            } catch (\Throwable $e) {
                $this->db->transRollback();
                log_message('error', 'Kalender::saveHoliday range: ' . $e->getMessage());
                return $this->respond(false, 'Gagal menyimpan rentang libur. Silakan coba lagi.');
            }

            $msg = "Rentang " . date('d/m/Y', strtotime($tglMulai)) . ' – ' . date('d/m/Y', strtotime($tglSelesai))
                . " diproses: {$inserted} hari baru";
            if ($updated > 0) {
                $msg .= ", {$updated} keterangan diperbarui";
            }
            return $this->respond(true, $msg);
        }

        // ── Mode TAMBAH SATU TANGGAL ─────────────────────────────────────
        $tanggal = trim((string) $this->request->getPost('tanggal'));
        if (! $this->validDate($tanggal)) {
            return $this->respond(false, 'Format tanggal tidak valid');
        }

        $row = $this->db->table('absensi_hari_libur')->where('tanggal', $tanggal)->get()->getRow();
        if ($row) {
            $this->db->table('absensi_hari_libur')->where('id', $row->id)->update(['keterangan' => $keterangan]);
            return $this->respond(true, 'Hari libur ' . date('d/m/Y', strtotime($tanggal)) . ' berhasil diperbarui');
        }

        $this->db->table('absensi_hari_libur')->insert(['tanggal' => $tanggal, 'keterangan' => $keterangan]);
        return $this->respond(true, 'Hari libur ' . date('d/m/Y', strtotime($tanggal)) . ' berhasil disimpan');
    }

    public function deleteHoliday()
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->respond(false, 'ID tidak valid');
        }

        $row = $this->db->table('absensi_hari_libur')->where('id', $id)->get()->getRow();
        if (! $row) {
            return $this->respond(false, 'Data tidak ditemukan');
        }

        $this->db->table('absensi_hari_libur')->where('id', $id)->delete();
        return $this->respond(true, 'Hari libur ' . date('d/m/Y', strtotime($row->tanggal)) . ' berhasil dihapus');
    }

    public function syncHolidays()
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $year = (int) ($this->request->getPost('year') ?: date('Y'));
        if ($year < 2000 || $year > 2100) {
            return $this->respond(false, 'Tahun tidak valid');
        }

        $client = \Config\Services::curlrequest();
        $json   = null;

        $apis = [
            "https://dayoffapi.vercel.app/api?year={$year}",
            "https://api-harilibur.vercel.app/api?year={$year}",
        ];

        foreach ($apis as $apiUrl) {
            try {
                $response = $client->get($apiUrl, ['timeout' => 10]);
                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody());
                    if (is_array($data) && count($data) > 0) {
                        $json = $data;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', "Kalender::syncHolidays failed [{$apiUrl}]: " . $e->getMessage());
            }
        }

        if (empty($json)) {
            return $this->respond(
                false,
                "Gagal mengambil data hari libur nasional tahun {$year}. Pastikan koneksi internet server aktif dan coba lagi."
            );
        }

        $addedCount   = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $diproses     = [];

        try {
            $this->db->transBegin();

            foreach ($json as $holiday) {
                $tanggal    = $holiday->tanggal    ?? $holiday->holiday_date ?? null;
                $keterangan = $holiday->keterangan ?? $holiday->holiday_name ?? null;
                $isCuti     = ! empty($holiday->is_cuti);

                $tglFormatted = $this->normalisasiTanggalApi($tanggal);
                if ($tglFormatted === null || ! $keterangan) {
                    $skippedCount++;
                    continue;
                }
                if (substr($tglFormatted, 0, 4) !== (string) $year) {
                    $skippedCount++;
                    continue;
                }
                if (isset($diproses[$tglFormatted])) {
                    $skippedCount++;
                    continue;
                }
                $diproses[$tglFormatted] = true;

                $label = $isCuti ? '[Cuti Bersama] ' . $keterangan : $keterangan;
                $label = mb_substr(trim((string) $label), 0, 255);

                $existing = $this->db->table('absensi_hari_libur')
                    ->where('tanggal', $tglFormatted)
                    ->get()->getRow();

                if ($existing) {
                    if ($existing->keterangan !== $label) {
                        $this->db->table('absensi_hari_libur')
                            ->where('id', $existing->id)
                            ->update(['keterangan' => $label]);
                        $updatedCount++;
                    }
                } else {
                    $this->db->table('absensi_hari_libur')->insert([
                        'tanggal'    => $tglFormatted,
                        'keterangan' => $label,
                    ]);
                    $addedCount++;
                }
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Kalender::syncHolidays DB error: ' . $e->getMessage());
            return $this->respond(false, 'Terjadi kesalahan saat menyimpan ke database. Silakan coba lagi.');
        }

        $msg = "Sinkronisasi {$year} selesai: {$addedCount} ditambahkan, {$updatedCount} diperbarui";
        if ($skippedCount > 0) {
            $msg .= ", {$skippedCount} dilewati";
        }

        return $this->respond(true, $msg);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{0:int,1:int} [bulan, tahun]
     */
    private function bulanTahunDariRequest(): array
    {
        $bulan = (int) ($this->request->getGet('bulan') ?? date('n'));
        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) date('n');
        }

        $tahun = (int) ($this->request->getGet('tahun') ?? date('Y'));
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        return [$bulan, $tahun];
    }

    /**
     * @return array{0:string,1:?string,2:?string} [tgl_mulai, tgl_selesai, error]
     */
    private function validasiTanggalEvent(): array
    {
        $tglMulai = trim((string) $this->request->getPost('tgl_mulai'));
        if (! $this->validDate($tglMulai)) {
            return ['', null, 'Tanggal mulai tidak valid'];
        }

        $tglSelesai = trim((string) $this->request->getPost('tgl_selesai'));
        if ($tglSelesai === '') {
            return [$tglMulai, null, null];
        }
        if (! $this->validDate($tglSelesai)) {
            return [$tglMulai, null, 'Tanggal selesai tidak valid'];
        }
        if ($tglSelesai < $tglMulai) {
            return [$tglMulai, null, 'Tanggal selesai tidak boleh sebelum tanggal mulai'];
        }

        return [$tglMulai, $tglSelesai, null];
    }

    private function validDate(?string $date): bool
    {
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y);
    }

    private function normalisasiTanggalApi($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $dt = \DateTime::createFromFormat('!' . $format, substr($value, 0, 10));
            if ($dt instanceof \DateTime) {
                $hasil = $dt->format('Y-m-d');
                if ($this->validDate($hasil)) {
                    return $hasil;
                }
            }
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        $hasil = date('Y-m-d', $ts);

        return $this->validDate($hasil) ? $hasil : null;
    }

    private function warnaTeksKontras(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#0f172a' : '#ffffff';
    }

    /**
     * @return array<int, object>
     */
    private function daftarHariLibur(): array
    {
        try {
            return $this->db->table('absensi_hari_libur')
                ->orderBy('tanggal', 'ASC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', 'Kalender::daftarHariLibur: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Hari (ISO 1-7) yang ditandai libur pada jadwal kerja.
     *
     * @return array<int, int>
     */
    private function jadwalKerjaLiburIso(): array
    {
        try {
            if (! $this->db->tableExists('absensi_jadwal_kerja')) {
                return [];
            }
            $rows = $this->db->table('absensi_jadwal_kerja')
                ->select('hari')
                ->where('is_libur', 1)
                ->get()->getResult();

            return array_map(static fn($r) => (int) $r->hari, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Jumlah hari sekolah dalam satu bulan: bukan akhir pekan, bukan libur
     * jadwal kerja, dan bukan tanggal merah.
     *
     * @param array<int,int>    $weeklyOffIso
     * @param array<int,int>    $jadwalLiburIso
     * @param array<string,bool> $tanggalLibur
     */
    private function hitungHariSekolah(int $tahun, int $bulan, array $weeklyOffIso, array $jadwalLiburIso, array $tanggalLibur): int
    {
        $totalHari = (int) date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));
        $jumlah    = 0;

        for ($d = 1; $d <= $totalHari; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $iso = (int) date('N', strtotime($tgl));

            if (in_array($iso, $weeklyOffIso, true)) {
                continue;
            }
            if (in_array($iso, $jadwalLiburIso, true)) {
                continue;
            }
            if (isset($tanggalLibur[$tgl])) {
                continue;
            }

            $jumlah++;
        }

        return $jumlah;
    }
}
