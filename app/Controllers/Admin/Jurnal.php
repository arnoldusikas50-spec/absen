<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\ElearningModel;
use App\Models\JurnalGuruModel;
use App\Models\PiketModel;
use App\Services\AttendancePolicy;
use App\Services\WajibJurnalPulangService;

class Jurnal extends BaseController
{
    protected $dashboardModel;
    protected $elearningModel;
    protected $jurnalModel;
    protected $piketModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->elearningModel = new ElearningModel();
        $this->jurnalModel = new JurnalGuruModel();
        $this->jurnalModel->initialize();
        $this->piketModel = new PiketModel();
    }

    /** Admin, panitia/pengawas, dan kepala sekolah boleh memantau jurnal. */
    private function canMonitor(): bool
    {
        return $this->isAdmin() || $this->isPanitia() || $this->isKepsek();
    }

    /**
     * Monitoring Jurnal Harian Seluruh Guru
     */
    public function index()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->canMonitor()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $idTp = (int) ($tp->id_tp ?? 1);
        $idSmt = (int) ($smt->id_smt ?? 1);

        $p = $this->monitoringParams();
        $modeBulanan = $p['mode_bulanan'];
        $kolomValidasi = $this->adaKolomValidasi();

        $jurnals = $this->monitoringBuilder($idTp, $idSmt, $p)
            ->orderBy('j.tanggal', 'DESC')
            ->orderBy('j.jam_ke', 'ASC')
            ->get()->getResult();

        // Ambil daftar guru, kelas, dan mapel untuk filter. Opsi kelas mengikuti
        // TP/SMT aktif agar kelas tahun lalu tidak muncul.
        $db = \Config\Database::connect();
        $gurus = $db->table('master_guru')->orderBy('nama_guru', 'ASC')->get()->getResult();
        $kelasBuilder = $db->table('master_kelas')->select('id_kelas, nama_kelas');
        if ($db->fieldExists('id_tp', 'master_kelas')) {
            $kelasBuilder->where('id_tp', $idTp);
        }
        if ($db->fieldExists('id_smt', 'master_kelas')) {
            $kelasBuilder->where('id_smt', $idSmt);
        }
        $kelases = $kelasBuilder->orderBy('nama_kelas', 'ASC')->get()->getResult();
        if (empty($kelases)) {
            $kelases = $db->table('master_kelas')->select('id_kelas, nama_kelas')
                ->orderBy('nama_kelas', 'ASC')->get()->getResult();
        }
        $mapels = $db->table('master_mapel')->select('id_mapel, nama_mapel')
            ->orderBy('nama_mapel', 'ASC')->get()->getResult();

        // Ringkasan sesi terpilih.
        $guruMengajar = [];
        $kelasTercakup = [];
        foreach ($jurnals as $j) {
            $guruMengajar[(int) $j->id_guru] = true;
            $kelasTercakup[(int) $j->id_kelas] = true;
        }
        $totalSesi = count($jurnals);
        $totalGuruMengajar = count($guruMengajar);
        $totalKelasTercakup = count($kelasTercakup);

        // Kehadiran guru untuk cross-check. Kunci per tanggal+guru karena mode
        // bulanan memuat banyak tanggal; baris tanpa jam_masuk (Alpa, Izin,
        // Sakit, Dinas) bukan "Hadir".
        $mulai = $modeBulanan
            ? sprintf('%04d-%02d-01', $p['tahun'], $p['bulan'])
            : $p['tanggal'];
        $selesai = $modeBulanan ? date('Y-m-t', strtotime($mulai)) : $p['tanggal'];
        $presensiRows = $this->presensiRange($mulai, $selesai);

        $guruHadirMap = [];
        $guruHadirList = [];
        foreach ($presensiRows as $ph) {
            $gid = (int) $ph->id_guru;
            $guruHadirMap[$ph->tgl . '|' . $gid] = $ph;

            if (!$modeBulanan && $this->hasCheckIn($ph) && $gid > 0 && !isset($guruHadirList[$gid])) {
                $guruHadirList[$gid] = $ph;
            }
        }

        // Sesi yang seharusnya berjalan menurut jadwal pelajaran. Dihitung
        // sebelum daftar "belum jurnal" agar kewajiban menulis jurnal hanya
        // menyasar guru yang memang punya jadwal pada tanggal ini.
        //
        // Sesi yang belum dimulai dipisahkan: guru baru diwajibkan menulis
        // jurnal setelah jam sesinya tiba, sama seperti gerbang absen pulang.
        $scheduledSessions = [];
        $totalTerjadwal = 0;
        $totalTerjadwalSudah = 0;
        $totalTerjadwalBelum = 0;
        $totalTerjadwalAkan = 0;
        $totalTerjadwalMenunggu = 0;
        $guruWajibJurnal = [];
        $guruSudahJurnal = [];
        $sesiBelumPerGuru = [];
        // Selama mode ketat aktif, jurnal yang belum disetujui piket belum
        // memenuhi kewajiban — angka monitoring harus sama dengan gerbang
        // absen pulang guru, tidak boleh lebih optimis.
        $modeKetat = $kolomValidasi && WajibJurnalPulangService::butuhValidasi(AttendancePolicy::setting());
        if (!$modeBulanan) {
            $scheduledSessions = $this->scheduledSessions($idTp, $idSmt, $p);
            $totalTerjadwal = count($scheduledSessions);
            foreach ($scheduledSessions as $sesi) {
                $gid = (int) ($sesi['id_guru'] ?? 0);
                if ($gid > 0) {
                    $guruWajibJurnal[$gid] = true;
                }
                if (!empty($sesi['terisi'])) {
                    $menunggu = $modeKetat && !empty($sesi['menunggu_validasi']);
                    if (!$menunggu) {
                        $totalTerjadwalSudah++;
                        if ($gid > 0) {
                            $guruSudahJurnal[$gid] = true;
                        }
                        continue;
                    }

                    $totalTerjadwalMenunggu++;
                    $berjalan = !empty($sesi['berjalan']);
                    if ($gid > 0 && $berjalan) {
                        $sesiBelumPerGuru[$gid][] = $this->sesiBelumLabel($sesi);
                    }
                    continue;
                }
                if (empty($sesi['berjalan'])) {
                    $totalTerjadwalAkan++;
                    continue;
                }

                $totalTerjadwalBelum++;
                if ($gid > 0) {
                    $sesiBelumPerGuru[$gid][] = $this->sesiBelumLabel($sesi);
                }
            }
        }

        // Guru yang sudah tercatat hadir tetapi belum melengkapi jurnal pada
        // tanggal terpilih. Kewajiban menulis jurnal hanya melekat pada guru
        // yang punya sesi menurut jadwal; guru tanpa jadwal tidak diwajibkan.
        // Guru yang sudah mengisi sebagian sesi tetap masuk daftar agar sisa
        // sesi yang belum dijurnal bisa ditindaklanjuti.
        $guruBelumJurnal = [];
        $totalGuruHadirBerjadwal = 0;
        $totalGuruSudahJurnal = count($guruSudahJurnal);
        if (!$modeBulanan) {
            foreach ($guruHadirList as $gid => $ph) {
                // Tanpa jadwal pada tanggal ini, guru tidak diwajibkan menulis
                // jurnal — sekalipun ia tercatat hadir.
                if (!isset($guruWajibJurnal[$gid])) {
                    continue;
                }
                $totalGuruHadirBerjadwal++;
                if (!empty($sesiBelumPerGuru[$gid])) {
                    $guruBelumJurnal[] = $ph;
                }
            }
            usort($guruBelumJurnal, static function ($a, $b) {
                return strcmp((string) ($a->nama_guru ?? ''), (string) ($b->nama_guru ?? ''));
            });
        }

        // Mode bulanan: kewajiban melekat pada guru yang punya sesi terjadwal
        // sampai hari ini, bukan pada seluruh guru di master.
        $totalGuruWajibJurnal = count($guruWajibJurnal);
        if ($modeBulanan) {
            $scheduledBulan = $this->scheduledCountPerGuru(
                $idTp,
                $idSmt,
                $mulai,
                min(date('Y-m-t', strtotime($mulai)), date('Y-m-d'))
            );
            $wajibBulanan = array_keys($scheduledBulan['per_guru']);
            $penulisBulanan = [];
            foreach ($db->table('guru_jurnal')->select('id_guru')
                ->where('id_tp', $idTp)
                ->where('id_smt', $idSmt)
                ->where('tanggal >=', $mulai)
                ->where('tanggal <=', $selesai)
                ->groupBy('id_guru')
                ->get()->getResult() as $row) {
                $penulisBulanan[(int) $row->id_guru] = true;
            }
            $totalGuruWajibJurnal = count($wajibBulanan);
            $totalGuruSudahJurnal = count(array_filter(
                $wajibBulanan,
                static fn(int $gid): bool => isset($penulisBulanan[$gid])
            ));
            $totalBelumJurnal = max(0, $totalGuruWajibJurnal - $totalGuruSudahJurnal);
        } else {
            $totalBelumJurnal = count($guruBelumJurnal);
        }

        // Backlog validasi pada periode + filter yang sama, dihitung tanpa
        // saring status agar angkanya tidak berubah saat layar disaring.
        $validasiStats = $this->validasiCounts($idTp, $idSmt, $p);
        $petugasValidasi = ($modeKetat && !$modeBulanan && $p['tanggal'] !== '')
            ? WajibJurnalPulangService::petugasValidasi($p['tanggal'], $db)
            : [];

        $periodeLabel = $modeBulanan
            ? buat_tanggal(date('F Y', mktime(0, 0, 0, $p['bulan'], 1, $p['tahun'])))
            : buat_tanggal(date('l, d F Y', strtotime($p['tanggal'])));

        $data = [
            'title' => 'Monitoring Jurnal Mengajar',
            'judul' => 'Monitoring Jurnal Mengajar',
            'subjudul' => 'Pemantauan Pelaksanaan KBM Guru',
            'user' => $user,
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'jurnals' => $jurnals,
            'tanggal' => $p['tanggal'],
            'mode_bulanan' => $modeBulanan,
            'bulan' => $p['bulan'],
            'tahun' => $p['tahun'],
            'periode_label' => $periodeLabel,
            'id_guru_pilih' => $p['id_guru'],
            'id_kelas_pilih' => $p['id_kelas'],
            'id_mapel_pilih' => $p['id_mapel'],
            'validasi_pilih' => $p['validasi'],
            'kolom_validasi' => $kolomValidasi,
            'mode_validasi_ketat' => $modeKetat,
            'validasi_stats' => $validasiStats,
            'petugas_validasi' => $petugasValidasi,
            'gurus' => $gurus,
            'kelases' => $kelases,
            'mapels' => $mapels,
            'guru_hadir_map' => $guruHadirMap,
            'guru_belum_jurnal' => $guruBelumJurnal,
            'total_sesi' => $totalSesi,
            'total_jurnal_hari_ini' => $totalSesi,
            'total_guru_mengajar' => $totalGuruMengajar,
            'total_guru_sudah_jurnal' => $totalGuruSudahJurnal,
            'total_kelas_tercakup' => $totalKelasTercakup,
            'total_guru_hadir' => count($guruHadirList),
            'total_guru_hadir_berjadwal' => $totalGuruHadirBerjadwal,
            'total_belum_jurnal' => $totalBelumJurnal,
            'total_guru' => count($gurus),
            'total_guru_wajib_jurnal' => $totalGuruWajibJurnal,
            'guru_wajib_jurnal' => $guruWajibJurnal,
            'guru_belum_sesi' => $sesiBelumPerGuru,
            'sesi_terjadwal' => $scheduledSessions,
            'total_terjadwal' => $totalTerjadwal,
            'total_terjadwal_sudah' => $totalTerjadwalSudah,
            'total_terjadwal_menunggu' => $totalTerjadwalMenunggu,
            'total_terjadwal_belum' => $totalTerjadwalBelum,
            'total_terjadwal_akan' => $totalTerjadwalAkan,
        ];

        return view('admin/jurnal/index', $data);
    }

    /**
     * Bandingkan jadwal pelajaran pada tanggal terpilih dengan jurnal yang
     * benar-benar terisi. Satu baris jadwal dapat menghasilkan beberapa baris
     * bila kelas:mapel ditugaskan ke lebih dari satu guru.
     *
     * Pencocokan jurnal memakai aturan yang sama dengan gerbang wajib jurnal
     * di halaman guru (JurnalGuruModel::cocokkanJurnalKeSlot), sehingga admin
     * dan guru tidak pernah berbeda status untuk sesi yang sama.
     */
    private function scheduledSessions(int $idTp, int $idSmt, array $p): array
    {
        $tanggal = (string) ($p['tanggal'] ?? '');
        if ($tanggal === '') return [];

        $weekday = (int) date('N', strtotime($tanggal));
        $db = \Config\Database::connect();

        $builder = $db->table('kelas_jadwal_mapel jm')
            ->select('jm.id_jadwal, jm.id_kelas, jm.id_mapel, jm.jam_ke, jm.dari, jm.sampai, k.nama_kelas, m.nama_mapel')
            ->join('master_kelas k', 'k.id_kelas = jm.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = jm.id_mapel', 'left')
            ->where('jm.id_tp', $idTp)
            ->where('jm.id_smt', $idSmt)
            ->where('jm.id_hari', $weekday)
            ->where('jm.id_mapel >', 0);

        if (!empty($p['id_kelas'])) $builder->where('jm.id_kelas', (int) $p['id_kelas']);
        if (!empty($p['id_mapel'])) $builder->where('jm.id_mapel', (int) $p['id_mapel']);

        $rows = $builder->orderBy('jm.dari', 'ASC')->get()->getResult();
        if (empty($rows)) return [];

        $assignments = $this->elearningModel->getGuruAssignmentMap($idTp, $idSmt);

        // Seluruh jurnal hari itu dikelompokkan per guru, lalu tiap guru
        // dicocokkan ke slot-slotnya sendiri.
        $jurnalByGuru = [];
        $validasiByJurnal = [];
        $kolomValidasi = $this->adaKolomValidasi();
        foreach ($db->table('guru_jurnal')
            ->select($kolomValidasi
                ? 'id_guru, id_jurnal, id_jadwal, id_kelas, id_mapel, jam_ke, validasi_status'
                : 'id_guru, id_jurnal, id_jadwal, id_kelas, id_mapel, jam_ke')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('tanggal', $tanggal)
            ->orderBy('id_jurnal', 'ASC')
            ->get()->getResult() as $j) {
            $jurnalByGuru[(int) $j->id_guru][] = $j;
            if ($kolomValidasi) {
                $validasiByJurnal[(int) $j->id_jurnal] = strtoupper(trim((string) ($j->validasi_status ?? '')));
            }
        }

        $sessions = [];
        $tanpaGuru = [];
        $slotsByGuru = [];
        $namaByGuru = [];

        foreach ($rows as $row) {
            $key = (int) $row->id_kelas . ':' . (int) $row->id_mapel;
            $teachers = $assignments[$key] ?? [];

            $base = [
                'id_jadwal' => (int) $row->id_jadwal,
                'id_guru' => 0,
                'nama_guru' => '',
                'id_kelas' => (int) $row->id_kelas,
                'nama_kelas' => (string) ($row->nama_kelas ?? ''),
                'id_mapel' => (int) $row->id_mapel,
                'nama_mapel' => (string) ($row->nama_mapel ?? ''),
                'jam_ke' => (int) $row->jam_ke,
                'dari' => substr((string) $row->dari, 0, 5),
                'sampai' => substr((string) $row->sampai, 0, 5),
                'terisi' => false,
                'id_jurnal' => 0,
                'validasi_status' => '',
                'berjalan' => $this->sesiSudahMulai($tanggal, substr((string) $row->dari, 0, 5), substr((string) $row->sampai, 0, 5)),
            ];

            if (empty($teachers)) {
                // Slot tanpa guru pengampu tidak pernah bisa dijurnal; tetap
                // ditampilkan agar admin bisa melengkapi penugasannya.
                $tanpaGuru[] = $base;
                continue;
            }

            foreach ($teachers as $idGuru => $namaGuru) {
                $idGuru = (int) $idGuru;
                if (!empty($p['id_guru']) && $idGuru !== (int) $p['id_guru']) continue;

                $namaByGuru[$idGuru] = (string) $namaGuru;
                $slotsByGuru[$idGuru][] = (object) [
                    'id_jadwal' => (int) $row->id_jadwal,
                    'id_kelas' => (int) $row->id_kelas,
                    'id_mapel' => (int) $row->id_mapel,
                    'jam_ke' => (int) $row->jam_ke,
                    'dari' => substr((string) $row->dari, 0, 5),
                    'sampai' => substr((string) $row->sampai, 0, 5),
                    'nama_kelas' => (string) ($row->nama_kelas ?? ''),
                    'nama_mapel' => (string) ($row->nama_mapel ?? ''),
                ];
            }
        }

        foreach ($slotsByGuru as $idGuru => $slots) {
            $status = JurnalGuruModel::cocokkanJurnalKeSlot($slots, $jurnalByGuru[$idGuru] ?? []);
            foreach ($status as $row) {
                $slot = $row['slot'];
                $dari = (string) ($slot->dari ?? '');
                $sampai = (string) ($slot->sampai ?? '');
                $validasi = (string) ($validasiByJurnal[(int) ($row['id_jurnal'] ?? 0)] ?? '');
                $terisi = !empty($row['sudah_dijurnal']);

                $sessions[] = [
                    'id_jadwal' => (int) ($slot->id_jadwal ?? 0),
                    'id_guru' => $idGuru,
                    'nama_guru' => $namaByGuru[$idGuru] ?? '',
                    'id_kelas' => (int) ($slot->id_kelas ?? 0),
                    'nama_kelas' => (string) ($slot->nama_kelas ?? ''),
                    'id_mapel' => (int) ($slot->id_mapel ?? 0),
                    'nama_mapel' => (string) ($slot->nama_mapel ?? ''),
                    'jam_ke' => (int) ($slot->jam_ke ?? 0),
                    'dari' => $dari,
                    'sampai' => $sampai,
                    'terisi' => $terisi,
                    'id_jurnal' => (int) ($row['id_jurnal'] ?? 0),
                    // Jurnal terisi tapi belum disetujui piket: dihitung belum
                    // memenuhi kewajiban selama mode ketat aktif, sama seperti
                    // gerbang absen pulang guru.
                    'validasi_status' => $validasi,
                    'menunggu_validasi' => $terisi && $validasi !== JurnalGuruModel::VALIDASI_APPROVED,
                    'berjalan' => $this->sesiSudahMulai($tanggal, $dari, $sampai),
                ];
            }
        }

        $sessions = array_merge($sessions, $tanpaGuru);

        usort($sessions, static function ($a, $b) {
            return strcmp(
                $a['dari'] . $a['nama_kelas'] . sprintf('%02d', $a['jam_ke']),
                $b['dari'] . $b['nama_kelas'] . sprintf('%02d', $b['jam_ke'])
            );
        });

        return $sessions;
    }

    /**
     * Ringkasan sesi untuk daftar "guru belum melengkapi jurnal".
     * Status validasi ikut dibawa agar admin tahu bedanya sesi yang belum
     * ditulis dan sesi yang tulisannya menunggu persetujuan piket.
     */
    private function sesiBelumLabel(array $sesi): array
    {
        return [
            'jam_ke' => (int) ($sesi['jam_ke'] ?? 0),
            'dari' => (string) ($sesi['dari'] ?? ''),
            'sampai' => (string) ($sesi['sampai'] ?? ''),
            'nama_kelas' => (string) ($sesi['nama_kelas'] ?? ''),
            'nama_mapel' => (string) ($sesi['nama_mapel'] ?? ''),
            'id_jurnal' => (int) ($sesi['id_jurnal'] ?? 0),
            'validasi_status' => (string) ($sesi['validasi_status'] ?? ''),
            'menunggu_validasi' => !empty($sesi['menunggu_validasi']),
        ];
    }

    /**
     * Apakah sesi sudah dimulai pada tanggal tersebut? Kewajiban menulis
     * jurnal mengikuti gerbang absen pulang: sesi yang belum dimulai belum
     * menagih jurnal.
     */
    private function sesiSudahMulai(string $tanggal, string $dari, string $sampai): bool
    {
        $hariIni = date('Y-m-d');
        if ($tanggal < $hariIni) return true;
        if ($tanggal > $hariIni) return false;

        // Slot tanpa jam mulai dan jam selesai tidak bisa dinilai sudah lewat;
        // gerbang absen pulang guru juga tidak menahannya.
        if ($dari === '' && $sampai === '') return false;

        $sekarang = date('H:i');
        if ($dari !== '') {
            return strcmp($dari, $sekarang) <= 0;
        }

        return $sampai === '' || strcmp($sampai, $sekarang) <= 0;
    }

    /**
     * Jumlah sesi terjadwal per guru pada rentang tanggal: kemunculan tiap hari
     * (Senin–Sabtu) dikali slot jadwal pada hari itu. Dipakai rekap bulanan dan
     * kartu "belum jurnal" mode bulanan agar angka tetap konsisten.
     *
     * @return array{per_guru: array<int,int>, total: int}
     */
    private function scheduledCountPerGuru(int $idTp, int $idSmt, string $firstDay, string $endDay): array
    {
        $out = ['per_guru' => [], 'total' => 0];
        if ($firstDay === '' || $endDay === '' || $endDay < $firstDay) {
            return $out;
        }

        $dayCounts = [];
        for ($ts = strtotime($firstDay); $ts <= strtotime($endDay); $ts = strtotime('+1 day', $ts)) {
            $w = (int) date('N', $ts);
            if ($w >= 1 && $w <= 6) {
                $dayCounts[$w] = ($dayCounts[$w] ?? 0) + 1;
            }
        }
        if (empty($dayCounts)) {
            return $out;
        }

        $db = \Config\Database::connect();
        $slots = $db->table('kelas_jadwal_mapel')
            ->select('id_hari, id_kelas, id_mapel')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('id_mapel >', 0)
            ->get()->getResult();
        if (empty($slots)) {
            return $out;
        }

        $assignmentMap = $this->elearningModel->getGuruAssignmentMap($idTp, $idSmt);
        foreach ($slots as $slot) {
            $mult = $dayCounts[(int) $slot->id_hari] ?? 0;
            if ($mult <= 0) continue;

            $teachers = $assignmentMap[(int) $slot->id_kelas . ':' . (int) $slot->id_mapel] ?? [];
            foreach (array_keys($teachers) as $idGuru) {
                $out['per_guru'][(int) $idGuru] = ($out['per_guru'][(int) $idGuru] ?? 0) + $mult;
                $out['total'] += $mult;
            }
        }

        return $out;
    }

    /** Parameter filter monitoring yang dipakai halaman & ekspor CSV. */
    private function monitoringParams(): array
    {
        $bulan = (int) ($this->request->getGet('bulan') ?: 0);
        $tahun = (int) ($this->request->getGet('tahun') ?: 0);
        $modeBulanan = $bulan >= 1 && $bulan <= 12 && $tahun >= 2000 && $tahun <= 2100;

        $tanggalParam = trim((string) ($this->request->getGet('tanggal') ?? ''));
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $tanggalParam);
        $tanggalValid = $d && $d->format('Y-m-d') === $tanggalParam;

        // Tanggal eksplisit menang atas mode bulanan, supaya memilih tanggal
        // dari mode bulanan langsung kembali ke tampilan harian.
        if ($modeBulanan && $tanggalValid) {
            $modeBulanan = false;
        }
        $tanggal = $tanggalValid ? $tanggalParam : date('Y-m-d');
        if ($modeBulanan) {
            $tanggal = '';
        }

        $validasi = strtoupper(trim((string) ($this->request->getGet('validasi') ?? '')));
        if (!in_array($validasi, JurnalGuruModel::validasiStatusList(), true)) {
            $validasi = '';
        }

        return [
            'mode_bulanan' => $modeBulanan,
            'bulan' => $modeBulanan ? $bulan : 0,
            'tahun' => $modeBulanan ? $tahun : 0,
            'tanggal' => $tanggal,
            'id_guru' => (int) ($this->request->getGet('id_guru') ?: 0),
            'id_kelas' => (int) ($this->request->getGet('id_kelas') ?: 0),
            'id_mapel' => (int) ($this->request->getGet('id_mapel') ?: 0),
            'validasi' => $validasi,
        ];
    }

    /** Kolom status validasi baru ada setelah patch; tanpa kolom, filter diabaikan. */
    private function adaKolomValidasi(): bool
    {
        static $ada = null;
        if ($ada === null) {
            try {
                $ada = \Config\Database::connect()->fieldExists('validasi_status', 'guru_jurnal');
            } catch (\Throwable $e) {
                $ada = false;
            }
        }

        return $ada;
    }

    /** Status validasi baris jurnal; kolom kosong (data lama) = belum divalidasi. */
    private function normalisasiValidasi($status): string
    {
        $st = strtoupper(trim((string) ($status ?? '')));

        return in_array($st, JurnalGuruModel::validasiStatusList(), true)
            ? $st
            : JurnalGuruModel::VALIDASI_PENDING;
    }

    private function monitoringBuilder(int $idTp, int $idSmt, array $p, bool $saringValidasi = true)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('guru_jurnal j')
            ->select('j.*')
            ->select('COALESCE(g.nama_guru, CONCAT("Guru #", j.id_guru)) AS nama_guru', false)
            ->select('g.nip')
            ->select('COALESCE(k.nama_kelas, CONCAT("Kelas #", j.id_kelas)) AS nama_kelas', false)
            ->select('COALESCE(m.nama_mapel, CONCAT("Mapel #", j.id_mapel)) AS nama_mapel', false)
            ->join('master_guru g', 'g.id_guru = j.id_guru', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left');

        return $this->terapkanFilterMonitoring($builder, $idTp, $idSmt, $p, $saringValidasi);
    }

    /**
     * Saring query jurnal dengan periode + filter aktif. Hanya kolom bertype
     * jurnal yang dipakai, jadi query agregat tanpa join boleh memakai filter
     * yang sama.
     *
     * $saringValidasi=false dipakai kartu hitungan status: jumlahnya harus tetap
     * menunjukkan seluruh periode walau layar sedang disaring per status.
     */
    private function terapkanFilterMonitoring($builder, int $idTp, int $idSmt, array $p, bool $saringValidasi = true)
    {
        $builder->where('j.id_tp', $idTp)->where('j.id_smt', $idSmt);

        if ($p['mode_bulanan']) {
            $mulai = sprintf('%04d-%02d-01', $p['tahun'], $p['bulan']);
            $builder->where('j.tanggal >=', $mulai)
                ->where('j.tanggal <=', date('Y-m-t', strtotime($mulai)));
        } else {
            $builder->where('j.tanggal', $p['tanggal']);
        }

        if ($p['id_guru'] > 0) $builder->where('j.id_guru', $p['id_guru']);
        if ($p['id_kelas'] > 0) $builder->where('j.id_kelas', $p['id_kelas']);
        if ($p['id_mapel'] > 0) $builder->where('j.id_mapel', $p['id_mapel']);

        $validasi = (string) ($p['validasi'] ?? '');
        if ($saringValidasi && $validasi !== '' && $this->adaKolomValidasi()) {
            if ($validasi === JurnalGuruModel::VALIDASI_PENDING) {
                // Jurnal lama dibuat sebelum fitur validasi ada, jadi ikut
                // antrean "menunggu" dan tidak hilang dari hasil.
                $builder->groupStart()
                    ->where('j.validasi_status', JurnalGuruModel::VALIDASI_PENDING)
                    ->orWhere('j.validasi_status', '')
                    ->orWhere('j.validasi_status', null)
                    ->groupEnd();
            } else {
                $builder->where('j.validasi_status', $validasi);
            }
        }

        return $builder;
    }

    /**
     * Hitungan status validasi pada periode + filter aktif (tanpa saring status).
     *
     * @return array{total:int,disetujui:int,menunggu:int,ditolak:int}
     */
    private function validasiCounts(int $idTp, int $idSmt, array $p): array
    {
        $out = ['total' => 0, 'disetujui' => 0, 'menunggu' => 0, 'ditolak' => 0];
        if (!$this->adaKolomValidasi()) {
            return $out;
        }

        try {
            $rows = $this->terapkanFilterMonitoring(
                \Config\Database::connect()->table('guru_jurnal j')->select('j.validasi_status', false),
                $idTp,
                $idSmt,
                $p,
                false
            )->get()->getResult();

            foreach ($rows as $row) {
                $out['total']++;
                $st = $this->normalisasiValidasi($row->validasi_status ?? '');
                if ($st === JurnalGuruModel::VALIDASI_APPROVED) {
                    $out['disetujui']++;
                } elseif ($st === JurnalGuruModel::VALIDASI_REJECTED) {
                    $out['ditolak']++;
                } else {
                    $out['menunggu']++;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[Admin\Jurnal] hitungan validasi gagal: ' . $e->getMessage());
        }

        return $out;
    }

    /** Baris presensi guru pada rentang tanggal (untuk cross-check jurnal). */
    private function presensiRange(string $mulai, string $selesai): array
    {
        $db = \Config\Database::connect();

        return $db->table('absensi_harian a')
            ->select('a.id_guru, a.tgl, a.status_masuk, a.jam_masuk')
            ->select('COALESCE(g.nama_guru, CONCAT("Guru #", a.id_guru)) AS nama_guru', false)
            ->select('g.nip')
            ->join('master_guru g', 'g.id_guru = a.id_guru', 'left')
            ->where('a.tgl >=', $mulai)
            ->where('a.tgl <=', $selesai)
            ->orderBy('a.jam_masuk', 'ASC')
            ->get()->getResult();
    }

    private function hasCheckIn(?object $row): bool
    {
        if (!$row) return false;

        $jam = trim((string) ($row->jam_masuk ?? ''));
        return $jam !== '' && $jam !== '00:00:00' && !str_starts_with($jam, '0000-00-00');
    }

    /** Label status presensi guru yang ramah dibaca (tanpa menampilkan tanggal). */
    private function presenceMeta(?object $row): string
    {
        if (!$row) return 'Belum Absen';

        $status = strtoupper(trim((string) ($row->status_masuk ?? '')));
        if ($this->hasCheckIn($row)) {
            $jam = (string) $row->jam_masuk;
            $clock = strlen($jam) >= 16 ? substr($jam, 11, 5) : substr($jam, 0, 5);
            return 'Hadir (' . $clock . ')';
        }

        return match ($status) {
            'IJIN' => 'Izin',
            'SAKIT' => 'Sakit',
            'DINAS_APPROVED' => 'Dinas',
            'ALPHA' => 'Alpa',
            default => 'Belum Absen',
        };
    }

    /**
     * Ekspor CSV jurnal sesuai filter monitoring aktif.
     */
    public function export()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->canMonitor()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $idTp = (int) ($tp->id_tp ?? 1);
        $idSmt = (int) ($smt->id_smt ?? 1);
        $p = $this->monitoringParams();

        $jurnals = $this->monitoringBuilder($idTp, $idSmt, $p)
            ->orderBy('j.tanggal', 'ASC')
            ->orderBy('j.jam_ke', 'ASC')
            ->get()->getResult();

        $mulai = $p['mode_bulanan']
            ? sprintf('%04d-%02d-01', $p['tahun'], $p['bulan'])
            : $p['tanggal'];
        $selesai = $p['mode_bulanan'] ? date('Y-m-t', strtotime($mulai)) : $p['tanggal'];

        $hadirMap = [];
        foreach ($this->presensiRange($mulai, $selesai) as $ph) {
            $hadirMap[$ph->tgl . '|' . (int) $ph->id_guru] = $ph;
        }

        $kolomValidasi = $this->adaKolomValidasi();
        $namaValidator = $kolomValidasi ? $this->validatorNames($jurnals) : [];

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, array_merge([
            'Tanggal', 'Hari', 'Jam Ke', 'Guru', 'NIP', 'Kelas', 'Mata Pelajaran',
            'Materi Pokok', 'Tujuan Pembelajaran', 'Model/Metode', 'Kegiatan',
            'Asesmen', 'Profil Pancasila', 'Siswa Tidak Hadir', 'Refleksi',
            'Tindak Lanjut', 'Catatan Khusus', 'Presensi Guru', 'Diperbarui',
        ], $kolomValidasi ? ['Status Validasi', 'Validator', 'Validasi Pada', 'Catatan Validasi'] : []));

        $hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        foreach ($jurnals as $j) {
            $profil = !empty($j->profil_pancasila)
                ? (implode(', ', json_decode((string) $j->profil_pancasila, true) ?: []))
                : '';
            $hadir = $hadirMap[$j->tanggal . '|' . (int) $j->id_guru] ?? null;

            $baris = array_map([$this, 'csvSafe'], [
                $j->tanggal,
                $hari[(int) date('N', strtotime($j->tanggal))] ?? '',
                $j->jam_ke,
                $j->nama_guru,
                $j->nip,
                $j->nama_kelas,
                $j->nama_mapel,
                $j->materi_pokok,
                $j->tujuan_pembelajaran,
                $j->model_belajar,
                $j->kegiatan_belajar,
                $j->jenis_asesmen,
                $profil,
                $j->siswa_absent,
                $j->refleksi,
                $j->tindak_lanjut,
                $j->catatan_khusus,
                $this->presenceMeta($hadir),
                $j->updated_at,
            ]);

            if ($kolomValidasi) {
                $status = $this->normalisasiValidasi($j->validasi_status ?? '');
                $baris = array_merge($baris, array_map([$this, 'csvSafe'], [
                    $status === JurnalGuruModel::VALIDASI_APPROVED ? 'Disetujui'
                        : ($status === JurnalGuruModel::VALIDASI_REJECTED ? 'Ditolak' : 'Menunggu validasi'),
                    $status === JurnalGuruModel::VALIDASI_PENDING
                        ? ''
                        : ($namaValidator[(int) ($j->validasi_oleh ?? 0)] ?? ''),
                    (string) ($j->validasi_pada ?? ''),
                    (string) ($j->validasi_catatan ?? ''),
                ]));
            }

            fputcsv($fh, $baris);
        }

        $csv = stream_get_contents($fh, -1, 0);
        fclose($fh);

        $filename = 'jurnal-mengajar-' . ($p['mode_bulanan']
            ? sprintf('%04d-%02d', $p['tahun'], $p['bulan'])
            : $p['tanggal']) . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /**
     * Nama penanda tangan validasi: users.id => nama. validasi_oleh menyimpan
     * id akun, bukan id_guru, jadi lookup-nya ke tabel users.
     *
     * @param array<int, object> $jurnals
     * @return array<int, string>
     */
    private function validatorNames(array $jurnals): array
    {
        $ids = [];
        foreach ($jurnals as $j) {
            $oleh = (int) ($j->validasi_oleh ?? 0);
            if ($oleh > 0) {
                $ids[$oleh] = true;
            }
        }
        if ($ids === []) {
            return [];
        }

        $nama = [];
        try {
            // users tidak punya kolom `name`; nama dirakit dari first/last name.
            $rows = \Config\Database::connect()->table('users')
                ->select('id, TRIM(CONCAT_WS(" ", first_name, last_name)) AS nama', false)
                ->whereIn('id', array_keys($ids))
                ->get()->getResult();
            foreach ($rows as $r) {
                $nama[(int) $r->id] = trim((string) ($r->nama ?? ''));
            }
        } catch (\Throwable $e) {
            log_message('error', '[Admin\Jurnal] nama validator gagal dimuat: ' . $e->getMessage());
        }

        return $nama;
    }

    /** Cegah formula injection saat CSV dibuka di spreadsheet. */
    private function csvSafe($value): string
    {
        $value = (string) ($value ?? '');
        // '-' tunggal hanya penanda kosong, bukan awal formula.
        if ($value === '-' || $value === '') {
            return $value;
        }
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    /**
     * Baca detail jurnal dari halaman monitoring (read-only).
     */
    public function detail($id)
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->canMonitor()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to('admin/jurnal')->with('error', 'Jurnal tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $jurnal = $db->table('guru_jurnal j')
            ->select('j.*')
            ->select('COALESCE(g.nama_guru, CONCAT("Guru #", j.id_guru)) AS nama_guru', false)
            ->select('g.nip')
            ->select('COALESCE(k.nama_kelas, CONCAT("Kelas #", j.id_kelas)) AS nama_kelas', false)
            ->select('COALESCE(m.nama_mapel, CONCAT("Mapel #", j.id_mapel)) AS nama_mapel', false)
            ->join('master_guru g', 'g.id_guru = j.id_guru', 'left')
            ->join('master_kelas k', 'k.id_kelas = j.id_kelas', 'left')
            ->join('master_mapel m', 'm.id_mapel = j.id_mapel', 'left')
            ->where('j.id_jurnal', $id)
            ->get()->getRow();

        if (!$jurnal) {
            return redirect()->to('admin/jurnal')->with('error', 'Jurnal tidak ditemukan.');
        }

        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();

        $kolomValidasi = $this->adaKolomValidasi();
        $statusValidasi = $kolomValidasi ? $this->normalisasiValidasi($jurnal->validasi_status ?? '') : '';

        // Parse siswa_absent (JSON ID siswa) menjadi daftar nama untuk ditampilkan
        $absensi = $this->piketModel->parseSiswaAbsent($jurnal->siswa_absent ?? null);
        $siswaAbsentNama = $absensi['nama'];
        if (!empty($absensi['ids'])) {
            $siswaAbsentNama = $this->piketModel->getSiswaNamesByIds($absensi['ids']);
        }

        return view('admin/jurnal/detail', [
            'title' => 'Detail Jurnal Mengajar',
            'judul' => 'Detail Jurnal Mengajar',
            'subjudul' => 'Rincian Pelaksanaan KBM',
            'user' => $user,
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'jurnal' => $jurnal,
            'ada_validasi' => $kolomValidasi,
            'status_validasi' => $statusValidasi,
            'nama_validator' => $kolomValidasi && $statusValidasi !== JurnalGuruModel::VALIDASI_PENDING
                ? ($this->validatorNames([$jurnal])[(int) ($jurnal->validasi_oleh ?? 0)] ?? '')
                : '',
            'profil_list' => !empty($jurnal->profil_pancasila)
                ? (json_decode($jurnal->profil_pancasila, true) ?: [])
                : [],
            'can_print' => $this->isAdmin() || $this->isKepsek(),
            'siswa_absent_nama' => $siswaAbsentNama,
            'siswa_absent_count' => count($siswaAbsentNama),
        ]);
    }

    /**
     * Rekap Keterisian Jurnal Bulanan Seluruh Guru
     */
    public function rekap()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->canMonitor()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $r = $this->rekapData();

        return view('admin/jurnal/rekap', [
            'title' => 'Rekap Jurnal Mengajar Guru',
            'judul' => 'Rekap Jurnal Mengajar Guru',
            'subjudul' => 'Akumulasi Pengisian Jurnal Bulanan',
            'user' => $user,
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $r['tp'],
            'smt_active' => $r['smt'],
            'gurus' => $r['gurus'],
            'stat_map' => $r['statMap'],
            'scheduled_map' => $r['scheduledMap'],
            'total_scheduled' => $r['totalScheduled'],
            'bulan' => $r['bulan'],
            'tahun' => $r['tahun'],
            'q' => $r['q'],
            'hanya_belum' => $r['hanyaBelum'],
            'total_guru' => $r['totalGuru'],
            'guru_mengisi' => $r['guruMengisi'],
            'total_jurnal' => $r['totalJurnal'],
            'ada_validasi' => $r['kolomValidasi'],
            'mode_validasi_ketat' => $r['modeKetat'],
            'total_disetujui' => $r['totalDisetujui'],
            'total_menunggu' => $r['totalMenunggu'],
            'total_ditolak' => $r['totalDitolak'],
        ]);
    }

    /** Data rekap yang dipakai halaman, CSV, dan cetak. */
    private function rekapData(): array
    {
        $tp = $this->dashboardModel->getTahunActive();
        $smt = $this->dashboardModel->getSemesterActive();
        $idTp = (int) ($tp->id_tp ?? 1);
        $idSmt = (int) ($smt->id_smt ?? 1);

        $bulan = (int) ($this->request->getGet('bulan') ?: date('m'));
        if ($bulan < 1 || $bulan > 12) $bulan = (int) date('m');
        $bulan = sprintf('%02d', $bulan);
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');

        $q = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);
        $hanyaBelum = (string) $this->request->getGet('hanya_belum') === '1';

        $db = \Config\Database::connect();
        $allGurus = $db->table('master_guru')
            ->select('id_guru, nama_guru, nip')
            ->orderBy('nama_guru', 'ASC')
            ->get()->getResult();

        $kolomValidasi = $this->adaKolomValidasi();
        $statBuilder = $db->table('guru_jurnal')
            ->select('id_guru, COUNT(*) AS total_jurnal, COUNT(DISTINCT tanggal) AS hari_aktif, MAX(tanggal) AS last_tanggal')
            ->where('tanggal >=', $tahun . '-' . $bulan . '-01')
            ->where('tanggal <=', date('Y-m-t', strtotime($tahun . '-' . $bulan . '-01')))
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt);
        if ($kolomValidasi) {
            // Saring per status agar rekap tidak lagi hanya menghitung "ada
            // jurnal", tapi juga berapa banyak yang benar-benar disetujui.
            $statBuilder->select("SUM(validasi_status = '" . JurnalGuruModel::VALIDASI_APPROVED . "') AS jumlah_disetujui", false)
                ->select("SUM(validasi_status = '" . JurnalGuruModel::VALIDASI_REJECTED . "') AS jumlah_ditolak", false);
        }

        $statRows = $statBuilder->groupBy('id_guru')->get()->getResult();

        $statMap = [];
        $totalJurnal = 0;
        $totalDisetujui = 0;
        $totalDitolak = 0;
        foreach ($statRows as $row) {
            $statMap[(int) $row->id_guru] = $row;
            $totalJurnal += (int) $row->total_jurnal;
            if ($kolomValidasi) {
                $totalDisetujui += (int) ($row->jumlah_disetujui ?? 0);
                $totalDitolak += (int) ($row->jumlah_ditolak ?? 0);
            }
        }
        $totalMenunggu = $kolomValidasi ? max(0, $totalJurnal - $totalDisetujui - $totalDitolak) : 0;

        // Sesi terjadwal bulan terpilih, dihitung per guru hingga hari ini.
        $firstDay = $tahun . '-' . $bulan . '-01';
        $endDay = min(date('Y-m-t', strtotime($firstDay)), date('Y-m-d'));
        $scheduled = $this->scheduledCountPerGuru($idTp, $idSmt, $firstDay, $endDay);
        $scheduledMap = $scheduled['per_guru'];
        $totalScheduled = $scheduled['total'];

        $gurus = $allGurus;
        if ($q !== '') {
            $gurus = array_values(array_filter($gurus, static function ($g) use ($q) {
                return mb_stripos((string) $g->nama_guru, $q) !== false
                    || mb_stripos((string) ($g->nip ?? ''), $q) !== false;
            }));
        }
        if ($hanyaBelum) {
            $gurus = array_values(array_filter($gurus, static function ($g) use ($statMap) {
                return !isset($statMap[(int) $g->id_guru]);
            }));
        }

        return [
            'tp' => $tp,
            'smt' => $smt,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'q' => $q,
            'hanyaBelum' => $hanyaBelum,
            'gurus' => $gurus,
            'statMap' => $statMap,
            'scheduledMap' => $scheduledMap,
            'totalScheduled' => $totalScheduled,
            'totalGuru' => count($allGurus),
            'guruMengisi' => count($statMap),
            'totalJurnal' => $totalJurnal,
            'kolomValidasi' => $kolomValidasi,
            'modeKetat' => $kolomValidasi && WajibJurnalPulangService::butuhValidasi(AttendancePolicy::setting()),
            'totalDisetujui' => $totalDisetujui,
            'totalMenunggu' => $totalMenunggu,
            'totalDitolak' => $totalDitolak,
        ];
    }

    /** Ekspor rekap CSV sesuai filter pencarian. */
    public function rekapExport()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->canMonitor()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $r = $this->rekapData();

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        $header = ['No', 'Nama Guru', 'NIP', 'Sesi Terjadwal', 'Total Jurnal', 'Kepatuhan (%)', 'Hari Aktif', 'Terakhir Mengisi'];
        if ($r['kolomValidasi']) {
            $header = array_merge($header, ['Disetujui', 'Menunggu Validasi', 'Ditolak']);
        }
        fputcsv($fh, $header);

        $no = 1;
        foreach ($r['gurus'] as $g) {
            $stat = $r['statMap'][(int) $g->id_guru] ?? null;
            $jurnal = $stat ? (int) $stat->total_jurnal : 0;
            $terjadwal = (int) ($r['scheduledMap'][(int) $g->id_guru] ?? 0);
            $kepatuhan = $terjadwal > 0 ? min(100, (int) round($jurnal / $terjadwal * 100)) : null;
            $baris = [
                $no++,
                $g->nama_guru,
                $g->nip,
                $terjadwal,
                $jurnal,
                $kepatuhan === null ? '-' : $kepatuhan,
                $stat ? (int) $stat->hari_aktif : 0,
                $stat && !empty($stat->last_tanggal)
                    ? date('d/m/Y', strtotime($stat->last_tanggal))
                    : '-',
            ];
            if ($r['kolomValidasi']) {
                $disetujui = $stat ? (int) ($stat->jumlah_disetujui ?? 0) : 0;
                $ditolak   = $stat ? (int) ($stat->jumlah_ditolak ?? 0) : 0;
                $baris[] = $disetujui;
                $baris[] = max(0, $jurnal - $disetujui - $ditolak);
                $baris[] = $ditolak;
            }
            fputcsv($fh, array_map([$this, 'csvSafe'], $baris));
        }

        $csv = stream_get_contents($fh, -1, 0);
        fclose($fh);

        $filename = 'rekap-jurnal-' . $r['tahun'] . '-' . $r['bulan'] . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /** Versi cetak rekap untuk kepala sekolah / arsip. */
    public function rekapCetak()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        if (!$this->canMonitor()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $r = $this->rekapData();

        return view('admin/jurnal/rekap_cetak', [
            'setting' => $this->dashboardModel->getSetting(),
            'tp_active' => $r['tp'],
            'smt_active' => $r['smt'],
            'gurus' => $r['gurus'],
            'stat_map' => $r['statMap'],
            'scheduled_map' => $r['scheduledMap'],
            'total_scheduled' => $r['totalScheduled'],
            'bulan' => $r['bulan'],
            'tahun' => $r['tahun'],
            'q' => $r['q'],
            'hanya_belum' => $r['hanyaBelum'],
            'total_guru' => $r['totalGuru'],
            'guru_mengisi' => $r['guruMengisi'],
            'total_jurnal' => $r['totalJurnal'],
            'ada_validasi' => $r['kolomValidasi'],
        ]);
    }
}
