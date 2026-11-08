<?php

namespace App\Models;

use CodeIgniter\Model;

class PenilaianGuruModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function initialize()
    {
        $forge = \Config\Database::forge();

        if (!$this->db->tableExists('penilaian_guru_periode')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama' => ['type' => 'VARCHAR', 'constraint' => 255],
                'id_tp' => ['type' => 'INT', 'constraint' => 11],
                'id_smt' => ['type' => 'INT', 'constraint' => 11],
                'tgl_mulai' => ['type' => 'DATE'],
                'tgl_selesai' => ['type' => 'DATE'],
                'bobot_kehadiran' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 30.00],
                'bobot_mengajar' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 40.00],
                'bobot_piket' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 30.00],
                'status' => ['type' => "ENUM('draft','aktif','selesai')", 'default' => 'draft'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('penilaian_guru_periode', true);
        }

        if (!$this->db->tableExists('penilaian_guru_skor')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_periode' => ['type' => 'INT', 'constraint' => 11],
                'id_guru' => ['type' => 'INT', 'constraint' => 11],
                'skor_kehadiran' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
                'skor_mengajar' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
                'skor_piket' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
                'skor_akhir' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
                'predikat' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
                'detail' => ['type' => 'LONGTEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->addKey(['id_periode', 'id_guru'], false, true);
            $forge->createTable('penilaian_guru_skor', true);
        }
    }

    // --- Periode CRUD ---

    public function getPeriodeList()
    {
        return $this->db->table('penilaian_guru_periode p')
            ->select('p.*, tp.tahun, smt.smt')
            ->join('master_tp tp', 'tp.id_tp = p.id_tp', 'left')
            ->join('master_smt smt', 'smt.id_smt = p.id_smt', 'left')
            ->orderBy('p.tgl_mulai', 'DESC')
            ->get()->getResult();
    }

    public function getPeriode($id)
    {
        return $this->db->table('penilaian_guru_periode')
            ->where('id', $id)
            ->get()->getRow();
    }

    public function savePeriode($data)
    {
        if (!empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table('penilaian_guru_periode')->where('id', $id)->update($data);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->table('penilaian_guru_periode')->insert($data);
    }

    public function deletePeriode($id)
    {
        $this->db->table('penilaian_guru_skor')->where('id_periode', $id)->delete();
        return $this->db->table('penilaian_guru_periode')->where('id', $id)->delete();
    }

    // --- Scoring ---

    public function getSkorByPeriode($id_periode)
    {
        return $this->db->table('penilaian_guru_skor s')
            ->select('s.*, g.nama_guru, g.nip')
            ->join('master_guru g', 'g.id_guru = s.id_guru')
            ->where('s.id_periode', $id_periode)
            ->orderBy('s.skor_akhir', 'DESC')
            ->get()->getResult();
    }

    public function getSkorGuru($id_periode, $id_guru)
    {
        return $this->db->table('penilaian_guru_skor')
            ->where('id_periode', $id_periode)
            ->where('id_guru', $id_guru)
            ->get()->getRow();
    }

    public function getSkorById($id)
    {
        return $this->db->table('penilaian_guru_skor s')
            ->select('s.*, g.nama_guru, g.nip, p.nama as nama_periode, p.tgl_mulai, p.tgl_selesai, p.bobot_kehadiran, p.bobot_mengajar, p.bobot_piket')
            ->join('master_guru g', 'g.id_guru = s.id_guru')
            ->join('penilaian_guru_periode p', 'p.id = s.id_periode')
            ->where('s.id', $id)
            ->get()->getRow();
    }

    // --- Scoring Engine ---

    public function hitungSkor($id_periode)
    {
        $periode = $this->getPeriode($id_periode);
        if (!$periode) return ['status' => false, 'message' => 'Periode tidak ditemukan'];

        $id_tp = (int) $periode->id_tp;
        $id_smt = (int) $periode->id_smt;
        $tgl_mulai = $periode->tgl_mulai;
        $tgl_selesai = $periode->tgl_selesai;
        $bobot_kehadiran = (float) $periode->bobot_kehadiran;
        $bobot_mengajar = (float) $periode->bobot_mengajar;
        $bobot_piket = (float) $periode->bobot_piket;

        // All active teachers
        $gurus = $this->db->table('master_guru')
            ->select('id_guru, nama_guru')
            ->orderBy('nama_guru', 'ASC')
            ->get()->getResult();

        // Pre-load holidays & approved leaves
        $holidays = $this->getHolidays($tgl_mulai, $tgl_selesai);
        $approvedLeaves = $this->getApprovedLeaves($tgl_mulai, $tgl_selesai);

        // Pre-load schedule: which teacher teaches what per day
        $scheduleMap = $this->getScheduleMap($id_tp, $id_smt);

        // Pre-load piket schedule
        $piketMap = $this->getPiketMap($id_tp, $id_smt);

        $results = [];

        foreach ($gurus as $g) {
            $id_guru = (int) $g->id_guru;

            // === 1. SKOR KEHADIRAN ===
            $skorHadir = $this->hitungKehadiran($id_guru, $tgl_mulai, $tgl_selesai, $holidays, $approvedLeaves);
            $rataHadir = $skorHadir['rata_rata'];

            // === 2. SKOR MENGAJAR ===
            $skorMengajar = $this->hitungMengajar($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $scheduleMap);
            $rataMengajar = $skorMengajar['persentase'];

            // === 3. SKOR PIKET ===
            $skorPiket = $this->hitungPiket($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $piketMap);
            $rataPiket = $skorPiket['persentase'];

            // === SKOR AKHIR ===
            $totalWeight = $bobot_kehadiran + $bobot_mengajar + $bobot_piket;
            if ($totalWeight > 0) {
                $skor_akhir = ($rataHadir * $bobot_kehadiran + $rataMengajar * $bobot_mengajar + $rataPiket * $bobot_piket) / $totalWeight;
            } else {
                $skor_akhir = 0;
            }
            $skor_akhir = round($skor_akhir, 2);

            $predikat = $this->getPredikat($skor_akhir);

            $detail = json_encode([
                'kehadiran' => $skorHadir,
                'mengajar' => $skorMengajar,
                'piket' => $skorPiket,
            ]);

            // Save or update
            $existing = $this->db->table('penilaian_guru_skor')
                ->where('id_periode', $id_periode)
                ->where('id_guru', $id_guru)
                ->get()->getRow();

            $payload = [
                'id_periode' => $id_periode,
                'id_guru' => $id_guru,
                'skor_kehadiran' => $rataHadir,
                'skor_mengajar' => $rataMengajar,
                'skor_piket' => $rataPiket,
                'skor_akhir' => $skor_akhir,
                'predikat' => $predikat,
                'detail' => $detail,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->db->table('penilaian_guru_skor')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('penilaian_guru_skor')->insert($payload);
            }

            $results[] = [
                'id_guru' => $id_guru,
                'nama_guru' => $g->nama_guru,
                'skor_kehadiran' => $rataHadir,
                'skor_mengajar' => $rataMengajar,
                'skor_piket' => $rataPiket,
                'skor_akhir' => $skor_akhir,
                'predikat' => $predikat,
            ];
        }

        return ['status' => true, 'message' => 'Perhitungan selesai untuk ' . count($results) . ' guru', 'data' => $results];
    }

    // --- Kehadiran Score ---

    private function hitungKehadiran($id_guru, $tgl_mulai, $tgl_selesai, $holidays, $approvedLeaves)
    {
        $absensi = $this->db->table('absensi_harian')
            ->where('id_guru', $id_guru)
            ->where('tgl >=', $tgl_mulai)
            ->where('tgl <=', $tgl_selesai)
            ->orderBy('tgl', 'ASC')
            ->get()->getResult();

        $absensiByDate = [];
        foreach ($absensi as $a) {
            $absensiByDate[$a->tgl] = $a;
        }

        $totalSkor = 0;
        $totalHari = 0;
        $detailHari = [];
        $current = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);

        while ($current <= $end) {
            $tgl = $current->format('Y-m-d');
            $hariNum = (int) $current->format('N');

            // Skip weekends (Saturday=6, Sunday=7)
            if ($hariNum >= 6) {
                $current->modify('+1 day');
                continue;
            }

            // Skip holidays
            if (isset($holidays[$tgl])) {
                $current->modify('+1 day');
                continue;
            }

            $totalHari++;
            $skorHari = 0;
            $status = 'alpha';

            if (isset($absensiByDate[$tgl])) {
                $a = $absensiByDate[$tgl];
                $masuk = trim((string) ($a->status_masuk ?? ''));
                $pulang = trim((string) ($a->status_pulang ?? ''));
                $jamMasuk = $a->jam_masuk;
                $jamPulang = $a->jam_pulang;

                if ($masuk === 'DINAS_APPROVED' || $masuk === 'DINAS' || (strpos($masuk, 'DINAS') === 0 && $masuk !== 'DINAS_REJECTED' && $masuk !== 'DINAS_PENDING')) {
                    $skorHari = 100;
                    $status = 'dinas';
                } elseif ($masuk === 'DINAS_PENDING') {
                    $skorHari = 80;
                    $status = 'dinas';
                } elseif ($masuk === 'ONTIME' || $masuk === 'LATE') {
                    if (!empty($jamMasuk)) {
                        if ($masuk === 'ONTIME' && (!empty($jamPulang) || $pulang === 'ONTIME')) {
                            $skorHari = 100;
                            $status = 'hadir_sempurna';
                        } elseif ($masuk === 'ONTIME' && !empty($jamPulang)) {
                            $skorHari = 90;
                            $status = 'hadir_pulang';
                        } elseif ($masuk === 'ONTIME' && empty($jamPulang)) {
                            $skorHari = 70;
                            $status = 'hadir_belum_pulang';
                        } elseif ($masuk === 'LATE') {
                            if (!empty($jamPulang)) {
                                $skorHari = 70;
                                $status = 'terlambat_pulang';
                            } else {
                                $skorHari = 50;
                                $status = 'terlambat';
                            }
                        }
                    } else {
                        $skorHari = 30;
                        $status = 'tanpa_jam';
                    }
                } else {
                    $skorHari = 30;
                    $status = 'tanpa_status';
                }
            } else {
                // Check approved leave
                $cuti = $approvedLeaves[$id_guru][$tgl] ?? null;
                if ($cuti) {
                    $skorHari = 70;
                    $status = 'izin_' . $cuti;
                } else {
                    $skorHari = 0;
                    $status = 'alpha';
                }
            }

            $totalSkor += $skorHari;
            $detailHari[$tgl] = ['skor' => $skorHari, 'status' => $status];
            $current->modify('+1 day');
        }

        $rataRata = $totalHari > 0 ? round(($totalSkor / ($totalHari * 100)) * 100, 2) : 0;

        return [
            'rata_rata' => $rataRata,
            'total_hari' => $totalHari,
            'total_skor' => $totalSkor,
            'detail' => $detailHari,
        ];
    }

    // --- Teaching Score ---

    private function hitungMengajar($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $scheduleMap)
    {
        $scheduledSessions = 0;
        $taughtSessions = 0;
        $detail = [];

        $current = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);

        // Get all journals for this teacher in period
        $journals = $this->db->table('guru_jurnal')
            ->where('id_guru', $id_guru)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->where('tanggal >=', $tgl_mulai)
            ->where('tanggal <=', $tgl_selesai)
            ->get()->getResult();

        $journalMap = [];
        foreach ($journals as $j) {
            $key = $j->tanggal . '|' . $j->id_kelas . '|' . $j->id_mapel . '|' . $j->jam_ke;
            $journalMap[$key] = $j;
        }

        while ($current <= $end) {
            $tgl = $current->format('Y-m-d');
            $hariNum = (int) $current->format('N');

            if ($hariNum >= 6) { $current->modify('+1 day'); continue; }
            if (isset($holidays[$tgl])) { $current->modify('+1 day'); continue; }

            $hariNama = $current->format('N');
            $kelasMap = $scheduleMap[$id_guru][$hariNama] ?? [];

            foreach ($kelasMap as $sesi) {
                $scheduledSessions++;
                $key = $tgl . '|' . $sesi['id_kelas'] . '|' . $sesi['id_mapel'] . '|' . $sesi['jam_ke'];
                if (isset($journalMap[$key])) {
                    $taughtSessions++;
                    $detail[$tgl][] = ['jam_ke' => $sesi['jam_ke'], 'id_kelas' => $sesi['id_kelas'], 'id_mapel' => $sesi['id_mapel'], 'status' => 'mengajar'];
                } else {
                    $detail[$tgl][] = ['jam_ke' => $sesi['jam_ke'], 'id_kelas' => $sesi['id_kelas'], 'id_mapel' => $sesi['id_mapel'], 'status' => 'tidak_mengajar'];
                }
            }

            $current->modify('+1 day');
        }

        $persentase = $scheduledSessions > 0 ? round(($taughtSessions / $scheduledSessions) * 100, 2) : 100;

        return [
            'persentase' => $persentase,
            'terjadwal' => $scheduledSessions,
            'terlaksana' => $taughtSessions,
            'detail' => $detail,
        ];
    }

    // --- Piket Score ---

    private function hitungPiket($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $piketMap)
    {
        $totalTugas = 0;
        $terlaksana = 0;
        $detail = [];

        $current = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);

        while ($current <= $end) {
            $tgl = $current->format('Y-m-d');
            $hariNum = (int) $current->format('N');

            if ($hariNum >= 6) { $current->modify('+1 day'); continue; }
            if (isset($holidays[$tgl])) { $current->modify('+1 day'); continue; }

            $hariNama = $current->format('l');
            $isPiket = false;
            foreach ($piketMap as $p) {
                if ($p['id_guru'] == $id_guru && $p['hari'] === $hariNama) {
                    $isPiket = true;
                    break;
                }
            }

            if ($isPiket) {
                $totalTugas++;

                $absensi = $this->db->table('absensi_harian')
                    ->where('id_guru', $id_guru)
                    ->where('tgl', $tgl)
                    ->get()->getRow();

                if ($absensi && !empty($absensi->jam_masuk)) {
                    $terlaksana++;
                    $detail[$tgl] = ['status' => 'hadir', 'jam' => $absensi->jam_masuk];
                } else {
                    $detail[$tgl] = ['status' => 'tidak_hadir', 'jam' => null];
                }
            }

            $current->modify('+1 day');
        }

        $persentase = $totalTugas > 0 ? round(($terlaksana / $totalTugas) * 100, 2) : 100;

        return [
            'persentase' => $persentase,
            'total_tugas' => $totalTugas,
            'terlaksana' => $terlaksana,
            'detail' => $detail,
        ];
    }

    // --- Helpers ---

    private function getHolidays($tgl_mulai, $tgl_selesai)
    {
        $rows = $this->db->table('absensi_hari_libur')
            ->where('tanggal >=', $tgl_mulai)
            ->where('tanggal <=', $tgl_selesai)
            ->get()->getResult();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->tanggal] = $r->keterangan;
        }
        return $result;
    }

    private function getApprovedLeaves($tgl_mulai, $tgl_selesai)
    {
        $rows = $this->db->table('absensi_izin')
            ->where('status', 'approved')
            ->where('tgl_mulai <=', $tgl_selesai)
            ->where('tgl_selesai >=', $tgl_mulai)
            ->get()->getResult();

        $result = [];
        foreach ($rows as $r) {
            $start = max(new \DateTime($r->tgl_mulai), new \DateTime($tgl_mulai));
            $end = min(new \DateTime($r->tgl_selesai), new \DateTime($tgl_selesai));
            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1 day'));
            foreach ($period as $d) {
                $tgl = $d->format('Y-m-d');
                $result[(int) $r->id_guru][$tgl] = $r->jenis;
            }
        }
        return $result;
    }

    private function getScheduleMap($id_tp, $id_smt)
    {
        $jadwal = $this->db->table('kelas_jadwal_mapel jm')
            ->select('jm.*, jb.id_guru')
            ->join('jabatan_guru jb', 'jb.id_tp = jm.id_tp AND jb.id_smt = jm.id_smt', 'left')
            ->where('jm.id_tp', $id_tp)
            ->where('jm.id_smt', $id_smt)
            ->get()->getResult();

        $guruMap = [];
        foreach ($jadwal as $row) {
            if (empty($row->id_guru)) continue;
            $mapels = @unserialize($row->mapel_kelas ?? '', ['allowed_classes' => false]);
            if ($mapels === false && !empty($row->mapel_kelas) && $row->mapel_kelas !== 'b:0;') {
                $mapels = json_decode($row->mapel_kelas, true);
            }
            if (!is_array($mapels)) continue;

            $assignedGuru = null;
            foreach ($mapels as $m) {
                if (isset($m['kelas_mapel']) && is_array($m['kelas_mapel'])) {
                    foreach ($m['kelas_mapel'] as $km) {
                        if (isset($km['kelas']) && (int) $km['kelas'] === (int) $row->id_kelas) {
                            $assignedGuru = (int) $row->id_guru;
                            break 2;
                        }
                    }
                }
            }

            if ($assignedGuru) {
                $guruMap[$assignedGuru][(int) $row->id_hari][] = [
                    'id_kelas' => $row->id_kelas,
                    'id_mapel' => $row->id_mapel,
                    'jam_ke' => $row->jam_ke,
                ];
            }
        }

        return $guruMap;
    }

    private function getPiketMap($id_tp, $id_smt)
    {
        $rows = $this->db->table('piket_jadwal')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->getResult();

        $result = [];
        foreach ($rows as $r) {
            $result[] = ['id_guru' => (int) $r->id_guru, 'hari' => $r->hari];
        }
        return $result;
    }

    private function getPredikat($skor)
    {
        if ($skor >= 90) return 'A';
        if ($skor >= 75) return 'B';
        if ($skor >= 60) return 'C';
        if ($skor >= 45) return 'D';
        return 'E';
    }
}
