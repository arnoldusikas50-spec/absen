<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\ElearningModel;
use App\Models\JurnalGuruModel;
use App\Models\PiketModel;
use App\Models\SiswaPresensiModel;

/**
 * Jurnal Mengajar Guru
 *
 * Controller hanya menata alur: mengambil data guru/periode aktif, memanggil
 * query terpusat pada JurnalGuruModel, dan menyiapkan data untuk view.
 * Validasi simpan dilakukan bertahap agar pesan kesalahan mudah diikuti guru.
 */
class Jurnal extends BaseController
{
    protected $dashboardModel;
    protected $elearningModel;
    protected $jurnalModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->elearningModel = new ElearningModel();
        $this->jurnalModel = new JurnalGuruModel();

        // Pastikan tabel dan kolom guru_jurnal tersedia
        $this->jurnalModel->initialize();
    }

    /* ==================================================================
     |  Data dasar & opsi
     |================================================================= */

    /** Data guru yang sedang login beserta periode aktif, atau null bila sesi berakhir. */
    private function getTeacherData(): ?array
    {
        $user = $this->getUser();
        if (!$user) return null;

        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        $db = \Config\Database::connect();
        $guru = $db->table('master_guru')->where('id_user', $user->id)->get()->getRow();

        // Fallback jika id_guru di session ada
        $sessIdGuru = session()->get('id_guru');
        if (!$guru && $sessIdGuru) {
            $guru = $db->table('master_guru')->where('id_guru', $sessIdGuru)->get()->getRow();
        }

        // Fallback untuk admin
        if (!$guru && session()->get('role') == '1') {
            $guru = $db->table('master_guru')->get()->getRow();
        }

        return [
            'user' => $user,
            'tp'   => $tp,
            'smt'  => $smt,
            'guru' => $guru,
        ];
    }

    /**
     * Opsi dropdown kelas pada periode aktif.
     *
     * $jurnal (opsional): saat mengedit jurnal periode lama, kelasnya tetap
     * disertakan sebagai arsip agar form tidak mengirim ID kosong saat disimpan.
     */
    private function kelasOptions(int $idTp, int $idSmt, ?object $jurnal = null): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('master_kelas')->select('id_kelas, nama_kelas');
        if ($db->fieldExists('id_tp', 'master_kelas')) {
            $builder->where('id_tp', $idTp);
        }
        if ($db->fieldExists('id_smt', 'master_kelas')) {
            $builder->where('id_smt', $idSmt);
        }
        $kelases = $builder->orderBy('nama_kelas', 'ASC')->get()->getResult();

        if (empty($kelases)) {
            $kelases = $db->table('master_kelas')->select('id_kelas, nama_kelas')
                ->orderBy('nama_kelas', 'ASC')->get()->getResult();
        }

        if ($jurnal) {
            $kelasId = (int) $jurnal->id_kelas;
            $ada = in_array($kelasId, array_map(
                static fn($k): int => (int) (is_object($k) ? $k->id_kelas : $k),
                $kelases
            ), true);
            if (!$ada) {
                $row = $db->table('master_kelas')->select('id_kelas, nama_kelas')
                    ->where('id_kelas', $kelasId)->get()->getRow();
                // Kelas bisa saja sudah dihapus; tetap tampilkan sebagai arsip
                // agar jurnal lama masih bisa diubah tanpa kehilangan relasi.
                $kelases[] = $row ?: (object) [
                    'id_kelas'   => $kelasId,
                    'nama_kelas' => 'Kelas #' . $kelasId . ' (arsip)',
                ];
            }
        }

        return $kelases;
    }

    /** Dimensi Profil Pelajar Pancasila yang boleh dipilih. */
    private static function profilPancasilaOptions(): array
    {
        return [
            'Beriman, Bertakwa kepada Tuhan YME, & Berakhlak Mulia',
            'Berkebhinekaan Global',
            'Gotong Royong',
            'Mandiri',
            'Bernalar Kritis',
            'Kreatif'
        ];
    }

    /** Model/metode pembelajaran yang boleh dipilih. */
    private static function modelBelajarOptions(): array
    {
        return [
            'Problem Based Learning (PBL)',
            'Project Based Learning (PjBL)',
            'Discovery Learning',
            'Inquiry Learning',
            'Contextual Teaching & Learning',
            'Cooperative Learning',
            'Ceramah Interaktif & Diskusi',
            'Demonstrasi & Praktik Langsung',
            'Diferensiasi Konten / Proses'
        ];
    }

    /**
     * Parse filter ?bulan&tahun&id_kelas&id_mapel&q yang dipakai halaman
     * riwayat dan cetak rekap bulanan.
     */
    private function filterPeriode(): array
    {
        $bulan = (int) ($this->request->getGet('bulan') ?: date('m'));
        if ($bulan < 1 || $bulan > 12) $bulan = (int) date('m');
        $bulan = sprintf('%02d', $bulan);

        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        $mulai = $tahun . '-' . $bulan . '-01';
        $selesai = date('Y-m-t', strtotime($mulai));

        return [
            'bulan'    => $bulan,
            'tahun'    => $tahun,
            'mulai'    => $mulai,
            'selesai'  => $selesai,
            'id_kelas' => (int) ($this->request->getGet('id_kelas') ?: 0),
            'id_mapel' => (int) ($this->request->getGet('id_mapel') ?: 0),
            'q'        => mb_substr(trim((string) $this->request->getGet('q')), 0, 100),
        ];
    }

    /**
     * Status jurnal tiap sesi jadwal guru hari ini (di luar filter bulan),
     * agar guru melihat sesi mana yang belum dijurnal tanpa membuka form.
     *
     * Ikut aturan gerbang absen pulang: bila sekolah menyetujui hanya jurnal
     * yang sudah divalidasi, sesi berstatus PENDING/REJECTED dihitung belum
     * selesai agar angka di halaman ini tidak bertentangan dengan penolakan
     * absen pulang.
     */
    private function statusJadwalHariIni(int $idGuru, int $idTp, int $idSmt): array
    {
        $hanyaTervalidasi = \App\Services\WajibJurnalPulangService::butuhValidasi(
            \App\Services\AttendancePolicy::setting()
        );

        $status = $this->jurnalModel->getStatusJurnalHarian(
            $idGuru,
            $idTp,
            $idSmt,
            (int) date('N'),
            date('Y-m-d'),
            $hanyaTervalidasi
        );
        if (empty($status)) {
            return [];
        }

        $idTerpakai = array_values(array_filter(array_map(
            static fn(array $row): int => (int) ($row['id_jurnal'] ?? 0),
            $status
        )));
        $jurnalRows = $this->jurnalModel->getByIds($idTerpakai);

        $out = [];
        foreach ($status as $row) {
            $jurnalRow = $jurnalRows[(int) ($row['id_jurnal'] ?? 0)] ?? null;
            $absen = $jurnalRow
                ? PiketModel::parseSiswaAbsent($jurnalRow->siswa_absent ?? null)
                : ['ids' => [], 'nama' => [], 'nihil' => true];
            $out[] = [
                'slot'         => $row['slot'],
                'sudah'        => (bool) ($row['sudah_dijurnal'] ?? false),
                'menunggu'     => (bool) ($row['menunggu_validasi'] ?? false),
                'ditolak'      => strtoupper((string) ($row['validasi_status'] ?? '')) === JurnalGuruModel::VALIDASI_REJECTED,
                'id_jurnal'    => (int) ($row['id_jurnal'] ?? 0),
                'materi'       => $jurnalRow ? trim((string) ($jurnalRow->materi_pokok ?? '')) : '',
                'absen_jumlah' => $absen['nihil']
                    ? 0
                    : max(count($absen['ids']), count($absen['nama'])),
            ];
        }
        return $out;
    }

    /* ==================================================================
     |  Halaman riwayat
     |================================================================= */

    /**
     * Halaman Utama Jurnal Mengajar Guru
     */
    public function index()
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) return redirect()->to('auth');

        $guru = $tData['guru'];
        if (!$guru) {
            return redirect()->to('dashboard')->with('error', 'Profil Guru tidak ditemukan. Harap hubungi admin.');
        }

        $idGuru = (int) $guru->id_guru;
        $idTp   = (int) ($tData['tp']->id_tp ?? 1);
        $idSmt  = (int) ($tData['smt']->id_smt ?? 1);

        // Filter periode + kelas/mapel/kata kunci.
        $f = $this->filterPeriode();

        // Riwayat bulan terpilih, sudah dilengkapi ringkasan absen siswa.
        $jurnals = $this->jurnalModel->getRiwayatBulan(
            $idGuru, $idTp, $idSmt, $f['mulai'], $f['selesai'],
            ['id_kelas' => $f['id_kelas'], 'id_mapel' => $f['id_mapel'], 'q' => $f['q']]
        );
        $this->jurnalModel->lampirkanRingkasanAbsen($jurnals);

        // Statistik bulan ini, jurnal hari ini, dan aktivitas 7 hari terakhir
        // digabung dalam satu query agar tidak tiga kali memindai tabel.
        $stat = $this->jurnalModel->getStatistik($idGuru, $idTp, $idSmt, $f['mulai'], $f['selesai']);

        $jadwalHariIni = $this->statusJadwalHariIni($idGuru, $idTp, $idSmt);
        $jadwalHariIniTerisi = count(array_filter($jadwalHariIni, static fn(array $row): bool => $row['sudah']));

        $mapelOptions = $this->elearningModel->getMapelArray() ?: [];
        asort($mapelOptions, SORT_NATURAL | SORT_FLAG_CASE);

        // Opsi kelas mengikuti TP/SMT aktif agar kelas tahun lalu tidak muncul.
        $kelases = $this->kelasOptions($idTp, $idSmt);

        $data = [
            'title'     => 'Jurnal Mengajar',
            'judul'     => 'Jurnal Mengajar Guru',
            'subjudul'  => 'Catatan Pelaksanaan Pembelajaran Mandiri',
            'user'      => $tData['user'],
            'guru'      => $guru,
            'setting'   => $this->dashboardModel->getSetting(),
            'tp_active' => $tData['tp'],
            'smt_active' => $tData['smt'],
            'jurnals'   => $jurnals,
            'total_jurnal'      => (int) ($stat->total_bulan ?? 0),
            'jurnal_hari_ini'   => (int) ($stat->jurnal_hari_ini ?? 0),
            'total_minggu_ini'  => (int) ($stat->minggu_ini ?? 0),
            'jadwal_hari_ini'   => $jadwalHariIni,
            'jadwal_hari_ini_terisi' => $jadwalHariIniTerisi,
            'bulan_pilih'   => $f['bulan'],
            'tahun_pilih'   => $f['tahun'],
            'id_kelas_pilih' => $f['id_kelas'],
            'id_mapel_pilih' => $f['id_mapel'],
            'search_pilih'  => $f['q'],
            'kelases' => $kelases,
            'mapels'  => $mapelOptions,
        ];

        return view('guru/jurnal/index', $data);
    }

    /* ==================================================================
     |  Form tulis / edit / salin
     |================================================================= */

    /** Data prefill saat menyalin jurnal lain sebagai rancangan baru. */
    private function prefillDariSalin(object $sumber): array
    {
        $prefill = [];
        foreach ([
            'id_kelas', 'id_mapel', 'jam_ke', 'materi_pokok', 'tujuan_pembelajaran',
            'model_belajar', 'kegiatan_belajar', 'jenis_asesmen', 'media_alat',
            'refleksi', 'tindak_lanjut', 'catatan_khusus',
        ] as $field) {
            $prefill[$field] = $sumber->{$field} ?? '';
        }

        // Tanggal, tautan jadwal, dan kehadiran siswa selalu dikosongkan.
        $prefill['tanggal'] = date('Y-m-d');
        $prefill['id_jadwal'] = '';
        $prefill['siswa_absent'] = '';
        return $prefill;
    }

    /**
     * Form Buat / Edit Jurnal Mengajar
     */
    public function form($id = null)
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) return redirect()->to('auth');

        $guru = $tData['guru'];
        if (!$guru) {
            return redirect()->to('dashboard')->with('error', 'Profil Guru tidak ditemukan.');
        }

        $idGuru = (int) $guru->id_guru;
        $idTp   = (int) ($tData['tp']->id_tp ?? 1);
        $idSmt  = (int) ($tData['smt']->id_smt ?? 1);

        // Mode "Edit": jurnal harus milik guru yang login.
        $jurnal = null;
        if ((int) $id > 0) {
            $jurnal = $this->jurnalModel->findMilikGuru((int) $id, $idGuru);
            if (!$jurnal) {
                return redirect()->to('guru/jurnal')->with('error', 'Jurnal tidak ditemukan atau Anda tidak memiliki akses.');
            }
        }

        // Salin jurnal periode aktif sebagai titik awal (tanggal & kehadiran siswa dikosongkan).
        $copySource = null;
        $prefillData = [];
        $copyId = (int) ($this->request->getGet('copy') ?? 0);
        if (!$jurnal && $copyId > 0) {
            $copySource = $this->jurnalModel->findSumberSalin($copyId, $idGuru, $idTp, $idSmt);
            if (!$copySource) {
                return redirect()->to('guru/jurnal/create')
                    ->with('error', 'Jurnal sumber tidak ditemukan pada periode aktif.');
            }
            $prefillData = $this->prefillDariSalin($copySource);
        }

        // Penugasan guru pada periode aktif: sumber quick-pick & batas kelas:mapel jurnal.
        $assignedPairs = $this->elearningModel->getGuruAssignedPairs($idGuru, $idTp, $idSmt);

        // Jadwal KBM hari ini untuk pilihan cepat (Minggu memakai jadwal Senin).
        $hariIni = (int) date('N');
        if ($hariIni === 7) $hariIni = 1;
        $jadwalHariIni = $this->elearningModel->getJadwalKbmGuruHariIni($idGuru, $hariIni, $idTp, $idSmt) ?: [];

        // Opsi kelas & mapel; jurnal periode lama tetap mendapat relasinya sebagai arsip.
        $kelases = $this->kelasOptions($idTp, $idSmt, $jurnal);
        $mapels = $this->elearningModel->getMapelArray() ?: [];
        if ($jurnal && !isset($mapels[(int) $jurnal->id_mapel])) {
            $mapels[(int) $jurnal->id_mapel] = 'Mapel #' . (int) $jurnal->id_mapel . ' (arsip)';
        }

        // Prefill dari tautan, mis. kartu jadwal: ?id_jadwal=..&kelas=..&mapel=..
        $prefillKelas = (int) ($this->request->getGet('kelas') ?? $this->request->getGet('id_kelas') ?? 0);
        $prefillMapel = (int) ($this->request->getGet('mapel') ?? $this->request->getGet('id_mapel') ?? 0);
        $prefillJadwal = 0;
        $prefillJam = '';

        if ($copySource) {
            if ($prefillKelas === 0) $prefillKelas = (int) $copySource->id_kelas;
            if ($prefillMapel === 0) $prefillMapel = (int) $copySource->id_mapel;
        }

        // Prefill slot jadwal: hanya bila slot ada di periode aktif dan diampu guru ini.
        $prefillJadwalId = (int) ($this->request->getGet('id_jadwal') ?? 0);
        if (!$jurnal && !$copySource && $prefillJadwalId > 0) {
            $db = \Config\Database::connect();
            $slot = $db->table('kelas_jadwal_mapel')
                ->where('id_jadwal', $prefillJadwalId)
                ->where('id_tp', $idTp)
                ->where('id_smt', $idSmt)
                ->get()->getRow();
            if ($slot && isset($assignedPairs[(int) $slot->id_kelas . ':' . (int) $slot->id_mapel])) {
                $prefillJadwal = (int) $slot->id_jadwal;
                $prefillJam = (string) ($slot->jam_ke ?? '');
                if ($prefillKelas === 0) $prefillKelas = (int) $slot->id_kelas;
                if ($prefillMapel === 0) $prefillMapel = (int) $slot->id_mapel;
            }
        }

        // Dimensi Profil Pancasila terpilih (centang awal saat edit/salin).
        $profilSource = $jurnal ?: $copySource;
        $selectedProfil = [];
        if ($profilSource && !empty($profilSource->profil_pancasila)) {
            $selectedProfil = json_decode((string) $profilSource->profil_pancasila, true) ?: [];
        }

        // Tombol "salin jurnal terakhir" hanya ditawarkan pada mode tulis baru.
        $lastJurnal = (!$jurnal && !$copySource)
            ? $this->jurnalModel->jurnalTerakhir($idGuru, $idTp, $idSmt)
            : null;

        $mode = $jurnal ? 'Edit' : ($copySource ? 'Salin' : 'Tulis');

        $data = [
            'title'    => $mode . ' Jurnal Mengajar',
            'judul'    => $mode . ' Jurnal Mengajar',
            'subjudul' => $copySource ? 'Salin dari jurnal sebelumnya' : 'Agenda Harian Pembelajaran',
            'user'      => $tData['user'],
            'guru'      => $guru,
            'setting'   => $this->dashboardModel->getSetting(),
            'tp_active' => $tData['tp'],
            'smt_active' => $tData['smt'],
            'jurnal'       => $jurnal,
            'copy_source'  => $copySource,
            'prefill_data' => $prefillData,
            'last_jurnal'  => $lastJurnal,
            'jadwal_hari_ini' => $jadwalHariIni,
            'tanpa_penugasan' => empty($assignedPairs),
            'kelases' => $kelases,
            'mapels'  => $mapels,
            'profil_options' => self::profilPancasilaOptions(),
            'model_options'  => self::modelBelajarOptions(),
            'selected_profil' => $selectedProfil,
            'prefill_kelas'  => $prefillKelas,
            'prefill_mapel'  => $prefillMapel,
            'prefill_jadwal' => $prefillJadwal,
            'prefill_jam'    => $prefillJam,
            // Konteks kembali: tautan dari gerbang absen pulang mengirim
            // kembali=absen agar setelah simpan guru langsung ke halaman absen.
            'kembali' => $this->request->getGet('kembali') === 'absen' ? 'absen' : '',
            'hideBottomNav' => true
        ];

        return view('guru/jurnal/form', $data);
    }

    /* ==================================================================
     |  Siswa Tidak Hadir (picker)
     |================================================================= */

    /**
     * Daftar siswa aktif pada kelas terpilih untuk pemilihan "Siswa Tidak
     * Hadir". Gate-nya sama seperti dropdown kelas di form: kelas harus ada
     * pada periode aktif — bukan sekadar kelas diampu, karena dropdown itu
     * sendiri memuat semua kelas periode ini.
     */
    public function siswa($idKelas)
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) {
            return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir']);
        }

        $idKelas = (int) $idKelas;
        $idTp = (int) ($tData['tp']->id_tp ?? 1);
        $idSmt = (int) ($tData['smt']->id_smt ?? 1);

        $db = \Config\Database::connect();
        $kelas = $db->table('master_kelas')
            ->where('id_kelas', $idKelas)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->get()->getRow();
        if (!$kelas) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kelas tidak ditemukan pada periode aktif.']);
        }

        $rows = [];
        foreach ((new SiswaPresensiModel())->getSiswaByKelas($idKelas, $idTp, $idSmt) as $s) {
            $rows[] = [
                'id_siswa' => (int) $s->id_siswa,
                'nama'     => (string) $s->nama,
            ];
        }

        return $this->response->setJSON(['status' => true, 'nama_kelas' => (string) ($kelas->nama_kelas ?? ''), 'data' => $rows]);
    }

    /**
     * Normalisasi "siswa_absent": pilihan picker berupa JSON ID siswa —
     * saring agar hanya ID siswa aktif pada kelas terpilih yang tersimpan.
     * Teks bebas ("Nihil" atau nama manual) dipertahankan apa adanya.
     */
    private function normalisasiSiswaAbsent(string $raw, int $idKelas, int $idTp, int $idSmt): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || $trimmed === '[]') {
            return '';
        }
        if (!str_starts_with($trimmed, '[')) {
            return $trimmed; // teks bebas legacy dari form lama
        }

        $ids = json_decode($trimmed, true);
        if (!is_array($ids)) {
            return ''; // JSON rusak → kosongkan
        }

        $diKelas = [];
        foreach ((new SiswaPresensiModel())->getSiswaByKelas($idKelas, $idTp, $idSmt) as $s) {
            $diKelas[(int) $s->id_siswa] = true;
        }
        $ids = array_values(array_filter(array_map('intval', $ids),
            static fn($id): bool => isset($diKelas[$id])
        ));        return !empty($ids) ? json_encode(array_values(array_unique($ids))) : '';
    }

    /* ==================================================================
     |  Simpan
     |================================================================= */

    /** Kumpulkan & bersihkan seluruh input POST sesuai struktur payload jurnal. */
    private function inputJurnal(): array
    {
        $post = fn(string $name): string => trim((string) ($this->request->getPost($name) ?? ''));

        return [
            'id_jurnal'            => (int) $this->request->getPost('id_jurnal'),
            'tanggal'              => $post('tanggal'),
            'id_kelas'             => (int) $this->request->getPost('id_kelas'),
            'id_mapel'             => (int) $this->request->getPost('id_mapel'),
            'jam_ke'               => $post('jam_ke'),
            'id_jadwal'            => (int) $this->request->getPost('id_jadwal'),
            'materi_pokok'         => $post('materi_pokok'),
            'tujuan_pembelajaran'  => $post('tujuan_pembelajaran'),
            'model_belajar'        => $post('model_belajar'),
            'kegiatan_belajar'     => $post('kegiatan_belajar'),
            'profil_pancasila'     => (array) $this->request->getPost('profil_pancasila'),
            'jenis_asesmen'        => $post('jenis_asesmen'),
            'refleksi'             => $post('refleksi'),
            'media_alat'           => $post('media_alat'),
            'siswa_absent'         => $post('siswa_absent'),
            'tindak_lanjut'        => $post('tindak_lanjut'),
            'catatan_khusus'       => $post('catatan_khusus'),
        ];
    }

    /**
     * Validasi dasar input (tanggal, wajib, panjang field).
     * Mengembalikan pesan kesalahan, atau null bila lolos. jam_ke kosong
     * diisi '-' (nilai legacy kolom).
     */
    private function validasiInputJurnal(array &$in): ?string
    {
        // ── Tanggal: format Y-m-d, tanggal nyata, tidak di masa depan.
        if ($in['tanggal'] === '') {
            return 'Tanggal jurnal wajib diisi.';
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $in['tanggal']);
        if (!$d || $d->format('Y-m-d') !== $in['tanggal']) {
            return 'Format tanggal tidak valid.';
        }
        if ($in['tanggal'] > date('Y-m-d')) {
            return 'Tanggal jurnal tidak boleh melebihi hari ini.';
        }

        // ── Wajib.
        if (!$in['id_kelas'] || !$in['id_mapel'] || $in['materi_pokok'] === '') {
            return 'Harap lengkapi Kelas, Mata Pelajaran, dan Materi Pokok.';
        }

        // ── Panjang mengikuti kolom DB (nilai legacy tetap tersimpan apa adanya).
        if ($in['jam_ke'] === '') {
            $in['jam_ke'] = '-';
        } elseif (mb_strlen($in['jam_ke']) > 20) {
            return 'Jam ke terlalu panjang (maksimal 20 karakter).';
        }
        if (mb_strlen($in['model_belajar']) > 100) {
            return 'Model belajar terlalu panjang (maksimal 100 karakter).';
        }
        if (mb_strlen($in['jenis_asesmen']) > 50) {
            return 'Jenis asesmen terlalu panjang (maksimal 50 karakter).';
        }

        return null;
    }

    /**
     * Saring pilihan Profil Pancasila ke daftar opsi sah + nilai lama,
     * agar dimensi yang sudah tersimpan tidak hilang saat edit.
     */
    private function saringProfilPancasila(array $profil, ?object $existing): array
    {
        $allowedProfil = self::profilPancasilaOptions();
        if ($existing && !empty($existing->profil_pancasila)) {
            $legacyProfil = json_decode((string) $existing->profil_pancasila, true);
            if (is_array($legacyProfil)) {
                $allowedProfil = array_values(array_unique(array_merge($allowedProfil, $legacyProfil)));
            }
        }
        return array_values(array_intersect($profil, $allowedProfil));
    }

    /**
     * Kelas dan mapel harus ada pada periode jurnal. Relasi lama yang sudah
     * terhapus tetap dipertahankan saat edit selama nilai kelas/mapel tidak diubah.
     */
    private function validasiRelasi(int $idKelas, int $idMapel, ?object $existing, int $ownerTp, int $ownerSmt): ?string
    {
        $db = \Config\Database::connect();

        $kelasBuilder = $db->table('master_kelas')->where('id_kelas', $idKelas);
        if ($db->fieldExists('id_tp', 'master_kelas')) $kelasBuilder->where('id_tp', $ownerTp);
        if ($db->fieldExists('id_smt', 'master_kelas')) $kelasBuilder->where('id_smt', $ownerSmt);
        if (!$kelasBuilder->get()->getRow() && !($existing && (int) $existing->id_kelas === $idKelas)) {
            return 'Kelas yang dipilih tidak ditemukan pada tahun pelajaran jurnal.';
        }

        $mapel = $db->table('master_mapel')->where('id_mapel', $idMapel)->get()->getRow();
        if (!$mapel && !($existing && (int) $existing->id_mapel === $idMapel)) {
            return 'Mata pelajaran yang dipilih tidak ditemukan.';
        }

        return null;
    }

    /** Nama kelas & mapel untuk pesan kesalahan penugasan. */
    private function labelKelasMapel(int $idKelas, int $idMapel): string
    {
        $db = \Config\Database::connect();
        $kelas = $db->table('master_kelas')->where('id_kelas', $idKelas)->get()->getRow();
        $mapel = $db->table('master_mapel')->where('id_mapel', $idMapel)->get()->getRow();
        $kelasLabel = trim((string) ($kelas->nama_kelas ?? ('Kelas #' . $idKelas)));
        $mapelLabel = trim((string) ($mapel->nama_mapel ?? ('Mapel #' . $idMapel)));
        return $kelasLabel . ' - ' . $mapelLabel;
    }

    /**
     * Ikat slot jadwal: slot eksplisit dipakai hanya bila cocok kelas, mapel,
     * periode, dan hari tanggal jurnal. Jurnal baru tanpa slot diikat otomatis
     * bila hanya ada satu slot yang cocok, sehingga gerbang pulang tidak
     * perlu menebak pasangan kelas:mapel saat ada beberapa sesi sehari.
     */
    private function ikatSlotJadwal(array $in, int $ownerGuruId, int $ownerTp, int $ownerSmt): int
    {
        $hariTanggal = (int) date('N', strtotime($in['tanggal']));
        if ($hariTanggal === 7) {
            // Samakan dengan quick-pick form: Minggu memakai jadwal Senin.
            $hariTanggal = 1;
        }

        $db = \Config\Database::connect();
        $idJadwal = $in['id_jadwal'];

        if ($idJadwal > 0) {
            $jadwalRow = $db->tableExists('kelas_jadwal_mapel')
                ? $db->table('kelas_jadwal_mapel')->where('id_jadwal', $idJadwal)->get()->getRow()
                : null;
            $slotCocok = $jadwalRow
                && (int) ($jadwalRow->id_kelas ?? 0) === $in['id_kelas']
                && (int) ($jadwalRow->id_mapel ?? 0) === $in['id_mapel']
                && (int) ($jadwalRow->id_tp ?? 0) === $ownerTp
                && (int) ($jadwalRow->id_smt ?? 0) === $ownerSmt
                && (int) ($jadwalRow->id_hari ?? 0) === $hariTanggal;
            if (!$slotCocok) {
                $idJadwal = 0;
            }
        }

        // Jurnal baru tanpa slot eksplisit: ikat otomatis bila jelas.
        // (Edit tidak pernah diikat ulang otomatis agar relasi lama aman.)
        if ($idJadwal <= 0 && $in['id_jurnal'] <= 0 && $ownerGuruId > 0) {
            $slots = $this->elearningModel->getJadwalKbmGuruHariIni($ownerGuruId, $hariTanggal, $ownerTp, $ownerSmt);

            $samePair = [];
            foreach ($slots as $slot) {
                if ((int) ($slot->id_kelas ?? 0) === $in['id_kelas']
                    && (int) ($slot->id_mapel ?? 0) === $in['id_mapel']) {
                    $samePair[] = $slot;
                }
            }

            if (count($samePair) === 1) {
                return (int) ($samePair[0]->id_jadwal ?? 0);
            }

            // Beberapa slot pasangan sama: dibedakan lewat jam ke bila unik.
            $jamCari = JurnalGuruModel::normalizeJamKe($in['jam_ke']);
            if ($jamCari !== '') {
                $sameJam = [];
                foreach ($samePair as $slot) {
                    if (JurnalGuruModel::normalizeJamKe($slot->jam_ke ?? '') === $jamCari) {
                        $sameJam[] = $slot;
                    }
                }
                if (count($sameJam) === 1) {
                    return (int) ($sameJam[0]->id_jadwal ?? 0);
                }
            }
        }

        return $idJadwal;
    }

    /**
     * Simpan Jurnal (Insert / Update)
     */
    public function save()
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => 'Sesi berakhir']);
            }
            return redirect()->to('auth');
        }

        if (!$tData['guru']) {
            return $this->failSave('Profil Guru tidak ditemukan. Harap hubungi admin.');
        }

        $idGuru = (int) $tData['guru']->id_guru;
        $idTp   = (int) ($tData['tp']->id_tp ?? 1);
        $idSmt  = (int) ($tData['smt']->id_smt ?? 1);

        $in = $this->inputJurnal();

        // ── 1. Validasi dasar input.
        if ($err = $this->validasiInputJurnal($in)) {
            return $this->failSave($err);
        }

        // ── 2. Kepemilikan saat edit: jurnal milik guru (atau admin).
        $existing = null;
        if ($in['id_jurnal'] > 0) {
            $existing = $this->jurnalModel->find($in['id_jurnal']);
            if (!$existing || ((int) $existing->id_guru !== $idGuru && session()->get('role') != '1')) {
                return $this->failSave('Akses ditolak untuk mengedit jurnal ini.');
            }
        }

        // ── 3. Periode: jurnal lama tetap pada TP/SMT asal agar edit tidak memindahkan periode.
        $ownerGuruId = $existing ? (int) $existing->id_guru : $idGuru;
        $ownerTp = $existing ? (int) ($existing->id_tp ?: $idTp) : $idTp;
        $ownerSmt = $existing ? (int) ($existing->id_smt ?: $idSmt) : $idSmt;

        // ── 4. Profil Pancasila: hanya dimensi yang sah, nilai lama dipertahankan.
        $profil = $this->saringProfilPancasila($in['profil_pancasila'], $existing);

        // ── 5. Kelas & mapel harus ada pada periode jurnal.
        if ($err = $this->validasiRelasi($in['id_kelas'], $in['id_mapel'], $existing, $ownerTp, $ownerSmt)) {
            return $this->failSave($err);
        }

        // ── 6. Penugasan: pasangan kelas:mapel adalah sumber sesi jadwal,
        //      quick-pick, dan gerbang wajib jurnal. Jurnal baru hanya untuk
        //      pasangan yang diampu guru; jurnal lama yang sudah tidak
        //      ditugaskan tetap bisa diedit selama kelas/mapelnya tidak diubah.
        $pairTetap = $existing
            && (int) $existing->id_kelas === $in['id_kelas']
            && (int) $existing->id_mapel === $in['id_mapel'];
        $pairs = $this->elearningModel->getGuruAssignedPairs($ownerGuruId, $ownerTp, $ownerSmt);
        if (!$pairTetap && !isset($pairs[$in['id_kelas'] . ':' . $in['id_mapel']])) {
            return $this->failSave('Anda tidak ditugaskan mengajar ' . $this->labelKelasMapel($in['id_kelas'], $in['id_mapel'])
                . ' pada tahun pelajaran/semester jurnal ini. Pilih kelas dan mapel yang sesuai atau minta admin memperbarui penugasan.');
        }

        // ── 7. Slot jadwal rujukan.
        $idJadwal = $this->ikatSlotJadwal($in, $ownerGuruId, $ownerTp, $ownerSmt);

        // ── 8. Siswa tidak hadir: ID picker disaring ke siswa kelas terpilih.
        $in['siswa_absent'] = $this->normalisasiSiswaAbsent($in['siswa_absent'], $in['id_kelas'], $ownerTp, $ownerSmt);

        // ── 9. Cegah jurnal ganda guru+periode+tanggal+jam+kelas+mapel.
        if ($this->jurnalModel->adaJurnalGanda(
            $ownerGuruId, $ownerTp, $ownerSmt,
            $in['tanggal'], $in['jam_ke'], $in['id_kelas'], $in['id_mapel'],
            $in['id_jurnal']
        )) {
            return $this->failSave('Jurnal untuk kelas, mapel, dan jam tersebut pada tanggal '
                . date('d/m/Y', strtotime($in['tanggal'])) . ' sudah ada.');
        }

        $payload = [
            'id_guru'              => $ownerGuruId,
            'id_jadwal'            => $idJadwal ?: null,
            'id_kelas'             => $in['id_kelas'],
            'id_mapel'             => $in['id_mapel'],
            'id_tp'                => $ownerTp,
            'id_smt'               => $ownerSmt,
            'tanggal'              => $in['tanggal'],
            'jam_ke'               => $in['jam_ke'],
            'materi_pokok'         => $in['materi_pokok'],
            'tujuan_pembelajaran'  => $in['tujuan_pembelajaran'],
            'model_belajar'        => $in['model_belajar'],
            'kegiatan_belajar'     => $in['kegiatan_belajar'],
            'profil_pancasila'     => !empty($profil) ? json_encode($profil) : null,
            'jenis_asesmen'        => $in['jenis_asesmen'],
            'refleksi'             => $in['refleksi'],
            'media_alat'           => $in['media_alat'],
            'siswa_absent'         => $in['siswa_absent'],
            'tindak_lanjut'        => $in['tindak_lanjut'],
            'catatan_khusus'       => $in['catatan_khusus'],
        ];

        try {
            if ($in['id_jurnal'] > 0) {
                if ($this->jurnalModel->update($in['id_jurnal'], $payload) === false) {
                    throw new \RuntimeException('update gagal');
                }
                $idJurnal = $in['id_jurnal'];

                // Isi berubah → persetujuan/penolakan lama tidak boleh melekat
                // pada teks yang berbeda. Jurnal kembali ke antrean validasi.
                $sudahDinilai = $existing && in_array(
                    (string) ($existing->validasi_status ?? ''),
                    [JurnalGuruModel::VALIDASI_APPROVED, JurnalGuruModel::VALIDASI_REJECTED],
                    true
                );
                if ($sudahDinilai) {
                    $this->jurnalModel->resetValidasi((int) $idJurnal);
                }

                $this->kabariPiketValidasi($ownerGuruId, $in, $sudahDinilai);

                $msg = 'Jurnal mengajar berhasil diperbarui.';
                if ($sudahDinilai) {
                    $msg .= ' Isi berubah — jurnal masuk antrean validasi ulang.';
                }
            } else {
                $idJurnal = $this->jurnalModel->insert($payload);
                if ($idJurnal === false) {
                    throw new \RuntimeException('insert gagal');
                }
                $this->kabariPiketValidasi($ownerGuruId, $in, false);
                $msg = 'Jurnal mengajar berhasil disimpan.';
            }

            // ── Tujuan akhir sesuai konteks asal guru.
            $redirect = $this->redirectSetelahSimpan((string) $this->request->getPost('after'), $idJurnal, $in);
            if ($redirect['lanjut']) {
                // "Simpan & tulis lagi" membuka form baru untuk kelas/mapel yang sama.
                $msg .= ' Lanjutkan jurnal berikutnya.';
            } elseif ($redirect['absen']) {
                $msg .= ' Silakan lanjutkan absen pulang.';
            }

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'    => true,
                    'message'   => $msg,
                    'id_jurnal' => $idJurnal,
                    'redirect'  => site_url($redirect['url'])
                ]);
            }

            return redirect()->to($redirect['url'])->with('success', $msg);
        } catch (\Throwable $e) {
            log_message('error', '[guru/jurnal/save] ' . $e->getMessage());
            return $this->failSave('Gagal menyimpan jurnal. Silakan coba lagi.');
        }
    }

    /**
     * Beri tahu guru piket yang bertugas hari ini bahwa ada jurnal masuk
     * antrean validasi — tanpa ini jurnal menunggu tanpa ada yang tahu.
     *
     * Hanya jalan saat mode ketat aktif: di luar itu sekolah tidak menagih
     * validasi sebelum absen pulang, jadi pesannya hanya menjadi spam.
     * Sekali per guru per tanggal (sehari bisa 5–6 sesi dan piketnya bisa
     * belasan orang), dan isinya menyebut jumlah sesi yang menunggu supaya
     * petugas tetap tahu sisanya.
     *
     * Kegagalan kirim tidak boleh menggagalkan penyimpanan jurnal.
     *
     * @param array{tanggal:string,jam_ke:string,id_kelas:int,id_mapel:int} $in
     */
    private function kabariPiketValidasi(int $ownerGuruId, array $in, bool $validasiUlang): void
    {
        try {
            if (!\App\Services\WajibJurnalPulangService::butuhValidasi(\App\Services\AttendancePolicy::setting())) {
                return;
            }

            $tanggal = (string) ($in['tanggal'] ?? '');
            if ($tanggal === '') {
                return;
            }

            $akun = \App\Services\WajibJurnalPulangService::akunPetugasValidasi(date('Y-m-d'));
            if (empty($akun)) {
                return;
            }

            $db = \Config\Database::connect();
            $menunggu = (int) $db->table('guru_jurnal')
                ->where('id_guru', $ownerGuruId)
                ->where('tanggal', $tanggal)
                ->groupStart()
                ->where('validasi_status', null)
                ->orWhere('validasi_status !=', JurnalGuruModel::VALIDASI_APPROVED)
                ->groupEnd()
                ->countAllResults();
            if ($menunggu <= 0) {
                return;
            }

            // Satu kabar per guru per tanggal per hari.
            $kunci = 'jurnal-validasi-notif|' . $ownerGuruId . '|' . $tanggal . '|' . date('Y-m-d');
            if (cache($kunci) !== null) {
                return;
            }

            $guru = $db->table('master_guru')
                ->select('nama_guru')->where('id_guru', $ownerGuruId)->get()->getRow();

            (new \App\Services\NotificationService())->notifyJurnalMenungguValidasi(
                (string) ($guru->nama_guru ?? ('Guru #' . $ownerGuruId)),
                $tanggal,
                $menunggu,
                $akun,
                $validasiUlang
            );

            cache()->save($kunci, 1, max(60, strtotime(date('Y-m-d 23:59:59')) - time()));
        } catch (\Throwable $e) {
            log_message('error', '[guru/jurnal] notifikasi antrean validasi gagal: ' . $e->getMessage());
        }
    }

    /**
     * URL tujuan setelah simpan: tulis lagi (mode baru), balik ke absen
     * (tautan gerbang absen pulang), atau riwayat pada bulan jurnal agar
     * entri langsung terlihat.
     *
     * @return array{url:string, lanjut:bool, absen:bool}
     */
    private function redirectSetelahSimpan(string $after, int $idJurnal, array $in): array
    {
        $kembaliAbsen = $this->request->getPost('kembali') === 'absen';

        if ($after === 'again' && $idJurnal > 0 && $in['id_jurnal'] <= 0) {
            return [
                'url'    => 'guru/jurnal/create?kelas=' . $in['id_kelas'] . '&mapel=' . $in['id_mapel'],
                'lanjut' => true,
                'absen'  => false,
            ];
        }
        if ($kembaliAbsen) {
            return ['url' => 'presensi', 'lanjut' => false, 'absen' => true];
        }

        return [
            'url'    => 'guru/jurnal?bulan=' . date('m', strtotime($in['tanggal']))
                . '&tahun=' . date('Y', strtotime($in['tanggal'])),
            'lanjut' => false,
            'absen'  => false,
        ];
    }

    /** Respons gagal simpan: JSON untuk AJAX, kembali ke form dengan input terisi. */
    private function failSave(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => false, 'message' => $message]);
        }

        $idJurnal = (int) $this->request->getPost('id_jurnal');
        $target = $idJurnal > 0 ? 'guru/jurnal/edit/' . $idJurnal : 'guru/jurnal/create';
        if ($this->request->getPost('kembali') === 'absen') {
            $target .= '?kembali=absen';
        }

        return redirect()->to($target)->withInput()->with('error', $message);
    }

    /* ==================================================================
     |  Detail & hapus
     |================================================================= */

    /**
     * Tampilkan Detail Jurnal (JSON untuk modal, view penuh saat buka langsung)
     */
    public function detail($id)
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) return redirect()->to('auth');

        $jurnal = $this->jurnalModel->getJurnalDetail((int) $id);
        if (!$jurnal) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => 'Jurnal tidak ditemukan']);
            }
            return redirect()->to('guru/jurnal')->with('error', 'Jurnal tidak ditemukan.');
        }

        $isOwner = $tData['guru'] && (int) $jurnal->id_guru === (int) $tData['guru']->id_guru;
        if (!$isOwner && !$this->isAdmin()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => 'Anda tidak memiliki akses ke jurnal ini']);
            }
            return redirect()->to('guru/jurnal')->with('error', 'Anda tidak memiliki akses ke jurnal ini.');
        }

        // Ringkasan siswa absen memakai parser yang sama dengan halaman riwayat,
        // sehingga JSON ID dan teks mentah tidak tampil polos di detail/modal.
        $absenInfo = (new PiketModel())->infoAbsenJurnal($jurnal);
        $absenJumlah = (int) ($absenInfo['jumlah'] ?? 0);
        $absenLabel = implode(', ', (array) ($absenInfo['nama'] ?? []));

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => true,
                'data'         => $jurnal,
                'profil_list'  => !empty($jurnal->profil_pancasila) ? json_decode($jurnal->profil_pancasila, true) : [],
                'absen_jumlah' => $absenJumlah,
                'absen_label'  => $absenLabel
            ]);
        }

        $data = [
            'title'    => 'Detail Jurnal Mengajar',
            'judul'    => 'Detail Jurnal Mengajar',
            'subjudul' => 'Informasi Pelaksanaan KBM',
            'user'     => $tData['user'],
            'guru'     => $tData['guru'],
            'setting'  => $this->dashboardModel->getSetting(),
            'jurnal'   => $jurnal,
            'profil_list' => !empty($jurnal->profil_pancasila) ? json_decode($jurnal->profil_pancasila, true) : [],
            'absen_jumlah' => $absenJumlah,
            'absen_label'  => $absenLabel
        ];

        return view('guru/jurnal/detail', $data);
    }

    /**
     * Hapus Jurnal.
     *
     * Jurnal yang sudah disetujui validator tidak boleh hilang: persetujuan itu
     * adalah jejak bukti dan ikut menilai kepatuhan guru piket. Yang perlu
     * diperbaiki ya disunting — simpanan otomatis mengembalikan jurnal ke
     * antrean validasi, dan setelah itu jurnal bisa dihapus.
     */
    public function delete($id)
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) return redirect()->to('auth');

        $jurnal = $this->jurnalModel->find((int) $id);
        if (!$jurnal) {
            return redirect()->to($this->journalIndexUrl())->with('error', 'Jurnal tidak ditemukan.');
        }

        $idGuru = (int) ($tData['guru']->id_guru ?? 0);
        if ($jurnal->id_guru != $idGuru && session()->get('role') != '1') {
            return redirect()->to($this->journalIndexUrl())->with('error', 'Anda tidak memiliki hak untuk menghapus jurnal ini.');
        }

        if (strtoupper(trim((string) ($jurnal->validasi_status ?? ''))) === JurnalGuruModel::VALIDASI_APPROVED) {
            return redirect()->to($this->journalIndexUrl())->with('error', 'Jurnal yang sudah divalidasi tidak bisa dihapus. '
                . 'Sunting isinya bila ada yang perlu dikoreksi — jurnal akan masuk antrean validasi ulang dan setelah itu dapat dihapus.');
        }

        $this->jurnalModel->delete((int) $id);
        return redirect()->to($this->journalIndexUrl())->with('success', 'Jurnal mengajar berhasil dihapus.');
    }

    /** Kembali ke riwayat pada periode/filter yang sedang dibuka (bila dikirim form). */
    private function journalIndexUrl(): string
    {
        $bulan = (int) $this->request->getPost('bulan');
        $tahun = (int) $this->request->getPost('tahun');

        if ($bulan < 1 || $bulan > 12 || $tahun < 2000 || $tahun > 2100) {
            return 'guru/jurnal';
        }

        $params = ['bulan' => sprintf('%02d', $bulan), 'tahun' => $tahun];
        $idKelas = (int) $this->request->getPost('id_kelas');
        if ($idKelas > 0) {
            $params['id_kelas'] = $idKelas;
        }
        $q = trim((string) $this->request->getPost('q'));
        if ($q !== '') {
            $params['q'] = $q;
        }

        return 'guru/jurnal?' . http_build_query($params);
    }

    /* ==================================================================
     |  Cetak
     |================================================================= */

    /**
     * Cetak Jurnal Tunggal / Rekap Bulanan
     */
    public function cetak($id = null)
    {
        $tData = $this->getTeacherData();
        if (!$tData || !$tData['user']) return redirect()->to('auth');

        $guru = $tData['guru'];
        if (!$guru) {
            return redirect()->to('dashboard')->with('error', 'Profil Guru tidak ditemukan.');
        }

        $setting = $this->dashboardModel->getSetting();

        if ((int) $id > 0) {
            // ── Cetak 1 jurnal.
            $jurnal = $this->jurnalModel->getJurnalDetail((int) $id);
            if (!$jurnal) return redirect()->to('guru/jurnal')->with('error', 'Jurnal tidak ditemukan.');

            $isOwner = $guru && (int) $jurnal->id_guru === (int) $guru->id_guru;
            if (!$isOwner && !$this->isAdmin() && !$this->isKepsek()) {
                return redirect()->to('guru/jurnal')->with('error', 'Anda tidak memiliki akses ke jurnal ini.');
            }

            $data = [
                'setting'    => $setting,
                'guru'       => $guru,
                'jurnal'     => $jurnal,
                'is_single'  => true,
                'tp_active'  => $tData['tp'],
                'smt_active' => $tData['smt'],
            ];
        } else {
            // ── Rekap bulanan (urutan kronologis, filter kelas/mapel dipertahankan).
            $f = $this->filterPeriode();

            $jurnals = $this->jurnalModel->getRiwayatBulan(
                (int) $guru->id_guru,
                (int) ($tData['tp']->id_tp ?? 1),
                (int) ($tData['smt']->id_smt ?? 1),
                $f['mulai'], $f['selesai'],
                ['id_kelas' => $f['id_kelas'], 'id_mapel' => $f['id_mapel']],
                true
            );

            $data = [
                'setting'    => $setting,
                'guru'       => $guru,
                'jurnals'    => $jurnals,
                'bulan'      => $f['bulan'],
                'tahun'      => $f['tahun'],
                'is_single'  => false,
                'tp_active'  => $tData['tp'],
                'smt_active' => $tData['smt'],
            ];
        }

        return view('guru/jurnal/cetak', $data);
    }
}
