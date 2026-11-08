<?php

namespace App\Services;

/**
 * SiswaDendaService — jembatan presensi siswa → denda rupiah.
 *
 * Poin disiplin siswa sudah otomatis (SiswaPoinService), tapi nominal rupiah
 * hanya dikenal di dunia guru: tarifnya di absensi_setting dibaca
 * AttendancePenaltyService dan hasil akhirnya ditulis ke absensi_harian.denda.
 * Presensi siswa tidak pernah punya angka itu, sehingga sekolah tidak bisa
 * menagih alpha atau bolos siswa. Service ini menutup celah tersebut.
 *
 * SATU SUMBER PENILIAN. Satu hari siswa dinilai oleh
 * attendance_rekap_day_cell() — fungsi yang sama yang mengisi sel tabel rekap
 * HTML, Excel, dan PDF. Denda karenanya tidak bisa berbeda dari kolom Alpha:
 * hari yang di mata rekap masih "belum terjadi", libur, atau BDR tidak pernah
 * tertagih, dan hari yang rekap tandai ✕ karena BARISNYA TIDAK ADA ikut
 * dihitung. Kasus terakhir itulah yang paling sering terjadi di lapangan:
 * siswa yang tidak masuk biasanya memang tidak meninggalkan baris presensi,
 * jadi menagih hanya baris yang ada membuat tagihan selalu jauh di bawah
 * alpha yang terbaca di rekap.
 *
 * Dua hal mengikuti dari situ:
 *   - Alpha tanpa baris tidak bisa ditulis ke presensi_siswa (barisnya memang
 *     tidak ada). Angka itu lahir saat rekap dibaca — lihat ringkasBulan() —
 *     dan dilaporkan terpisah oleh recalcBulanBerjalan() supaya tidak disangka
 *     tagihan yang hilang.
 *   - Hari yang sedang berjalan tidak ditagih dari ketiadaan baris. Side by
 *     side dengan sisi guru, yang missingAlphaFines()-nya juga berhenti di
 *     kemarin ($includeToday = false), dan dengan keputusan sekolah soal bolos:
 *     ditagih setelah hari itu tertutup, bukan sejak jam pulang lewat.
 *
 * Prinsip: REBUILD per baris, tarif terkunci saat pertama menagih.
 *   Status dikoreksi jadi HADIR, atau jam pulang akhirnya di-scan, maka
 *   dendanya ikut hilang — tidak ada tagihan yatim. Sebaliknya, baris yang
 *   masih melanggar jenis yang sama mempertahankan nominalnya: mengganti tarif
 *   di pengaturan tidak menulis ulang tagihan bulan-bulan sebelumnya, sama
 *   seperti absensi_harian.poin_penalti_pulang di sisi guru.
 *
 * Cap bulanan (max_denda_siswa_bulan) TIDAK ditulis ke baris. Ia diterapkan
 * saat angka diakumulasikan (lihat terapkanCap), supaya mengganti plafon tidak
 * mengubah riwayat tagihan yang sudah tercetak — dan supaya baris tetap
 * menunjukkan nominal hari itu apa adanya.
 */
class SiswaDendaService
{
    private ?object $setting = null;

    /** @var array<string, array<int, object>> tanggal => id_kelas => jadwal BDR */
    private array $petaBdrHari = [];

    private ?PresensiBdrService $bdr = null;

    /**
     * Peta [bulan 'Y-m' => [id_kelas => [tgl => true]]: hari mana presensi
     * kelas itu benar-benar tercatat. Dipakai hanya untuk alpha TANPA baris;
     * hari tanpa satu baris pun berarti absensi belum dijalankan, bukan
     * seluruh kelas membolos, jadi tidak boleh ditagih.
     */
    private array $petaTercatat = [];

    /**
     * Tarif & plafon dari absensi_setting. Baris utama = id terkecil, sama
     * seperti AttendanceSettingModel::first().
     */
    private function setting(): object
    {
        if ($this->setting === null) {
            try {
                $row = \Config\Database::connect()
                    ->table('absensi_setting')
                    ->orderBy('id', 'ASC')
                    ->get()->getRow();
            } catch (\Throwable $e) {
                log_message('error', '[SiswaDendaService::setting] ' . $e->getMessage());
                $row = null;
            }
            $this->setting = $row ?: (object) [];
        }

        return $this->setting;
    }

    /** Plafon per siswa per bulan kalender. 0 = tanpa batas. */
    public function capBulan(): float
    {
        return max(0.0, (float) ($this->setting()->max_denda_siswa_bulan ?? 0));
    }

    /** Tarif yang berlaku untuk satu jenis pelanggaran. */
    public function tarif(string $jenis): float
    {
        $kolom = $jenis === 'alpha' ? 'denda_alpha_siswa_per_hari' : 'denda_bolos_siswa_per_hari';

        return max(0.0, (float) ($this->setting()->$kolom ?? 0));
    }

    /**
     * Semua yang dibutuhkan untuk menilai satu hari tanpa bantuan pemanggil:
     * libur atau tidak, hari BDR kelasnya atau bukan, dan batas jam masuk hari
     * itu. Setiap nilai diambil dari fungsi yang sama dengan yang dipakai view
     * rekap, supaya penilaian denda dan tampilan sel tidak pernah memakai dua
     * kalender berbeda.
     *
     * @return array{tgl:string, is_libur:bool, is_libur_mingguan:bool, is_bdr:bool, batas_masuk:string}
     */
    private function konteksHari(string $tanggal, int $idKelas): array
    {
        $tgl = substr(trim($tanggal), 0, 10);
        if ($tgl === '' || strtotime($tgl) === false) {
            return ['tgl' => '', 'is_libur' => true, 'is_libur_mingguan' => false, 'is_bdr' => true, 'batas_masuk' => '07:00:00'];
        }

        return [
            'tgl'               => $tgl,
            'id_kelas'          => $idKelas,
            'is_libur'          => isset(AttendancePolicy::liburNasional()[$tgl]),
            'is_libur_mingguan' => $this->isLiburMingguan($tgl),
            'is_bdr'            => $idKelas > 0 && isset($this->kelasBdr($tgl)[$idKelas]),
            'batas_masuk'       => $this->batasMasuk($tgl),
        ];
    }

    /**
     * Kelas mana yang BDR pada satu tanggal. SATU query per tanggal lalu
     * dipeta-can, sebab satu bulan rekap memakai peta yang sama untuk
     * seluruh siswa.
     *
     * @return array<int, object> id_kelas => jadwal
     */
    private function kelasBdr(string $tgl): array
    {
        if (isset($this->petaBdrHari[$tgl])) {
            return $this->petaBdrHari[$tgl];
        }

        try {
            $peta = $this->bdrService()->kelasTerdampak($tgl);
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::kelasBdr] ' . $e->getMessage());
            $peta = [];
        }

        return $this->petaBdrHari[$tgl] = $peta;
    }

    private function bdrService(): PresensiBdrService
    {
        return $this->bdr ??= new PresensiBdrService();
    }

    /**
     * Terima peta hari tercatat dari pemanggil (view rekap sudah
     * memilikinya) supaya tidak ada query tambahan.
     */
    public function pakaiPetaTercatat(array $peta, int $bulan, int $tahun): void
    {
        $this->petaTercatat[sprintf('%04d-%02d', $tahun, $bulan)] = $peta;
    }

    /**
     * Apakah presensi kelas ini tercatat pada tanggal itu? Bulan yang belum
     * pernah ditanyakan dibangun sekali per bulan, bukan per siswa.
     */
    private function tercatat(string $tgl, int $idKelas): bool
    {
        $bulanKey = substr($tgl, 0, 7);
        if (! isset($this->petaTercatat[$bulanKey])) {
            $this->petaTercatat[$bulanKey] = $this->bangunPetaTercatat($bulanKey);
        }

        return attendance_hari_tercatat($this->petaTercatat[$bulanKey], $tgl, $idKelas);
    }

    /** @return array<int, array<string, bool>> */
    private function bangunPetaTercatat(string $bulanKey): array
    {
        $awal  = $bulanKey . '-01';
        $akhir = date('Y-m-t', strtotime($awal));
        $peta  = [];

        try {
            $rows = \Config\Database::connect()->table('presensi_siswa')
                ->select('id_kelas, tgl')
                ->where('tgl >=', $awal)
                ->where('tgl <=', $akhir)
                ->get()->getResult();
            foreach ($rows as $r) {
                $peta[(int) ($r->id_kelas ?? 0)][substr((string) $r->tgl, 0, 10)] = true;
            }
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::bangunPetaTercatat] ' . $e->getMessage());
        }

        return $peta;
    }

    private function isLiburMingguan(string $tgl): bool
    {
        $ts = strtotime($tgl);

        return $ts !== false && attendance_is_weekly_holiday(
            date('D', $ts),
            (int) date('N', $ts),
            AttendancePolicy::jadwalKerja(),
            AttendancePolicy::hariLiburMingguan()
        );
    }

    /** Batas akhir jam masuk (inklusif sampai detik ke-59) — rumus view rekap. */
    private function batasMasuk(string $tgl): string
    {
        static $peta = [];
        if (isset($peta[$tgl])) {
            return $peta[$tgl];
        }

        return $peta[$tgl] = date('H:i:s', AttendancePolicy::batasAkhirMasukTs(
            $tgl,
            AttendancePolicy::jadwalSiswa($tgl)
        ));
    }

    /**
     * Pelanggaran ber-denda apa yang dilakukan satu hari presensi — TIDAK
     * melibatkan tarif.
     *
     * Pemisahan ini penting: "tidak ada pelanggaran" dan "ada pelanggaran tapi
     * tarifnya sedang 0 (fitur dimatikan)" adalah dua kondisi berbeda. Kalau
     * keduanya disamakan, mematikan tarif sesaat lalu menjalankan sinkron
     * menghapus seluruh tagihan historis siswa diam-diam.
     *
     * @param object|null $rec  baris presensi hari itu; null = tidak ada baris
     * @param array       $hari konteksHari() untuk tanggal yang sama
     * @return 'alpha'|'bolos'|null
     */
    public function pelanggaranSel(?object $rec, array $hari): ?string
    {
        $tgl = (string) ($hari['tgl'] ?? '');
        if ($tgl === '') {
            return null;
        }

        $sel = attendance_rekap_day_cell(
            $rec,
            $tgl,
            (bool) ($hari['is_libur_mingguan'] ?? false),
            (bool) ($hari['is_libur'] ?? false),
            (bool) ($hari['is_bdr'] ?? false),
            (string) ($hari['batas_masuk'] ?? '07:00:00'),
            null,
            // Flag hanya berarti saat barisnya tidak ada; baris yang ada sudah
            // membuktikan presensi hari itu jalan, jadi tidak perlu lookup.
            $rec !== null || $this->tercatat($tgl, (int) ($hari['id_kelas'] ?? 0))
        );

        if ($sel['state'] === 'alpha') {
            if ($rec !== null) {
                // Sekolah/piket menulis ALPA atau status tanpa keterangan:
                // hari itu sudah dinyatakan, tagih apa adanya.
                return 'alpha';
            }
            // Tidak ada baris sama sekali. Hari yang masih berjalan jangan
            // ditagih: siswa boleh jadi belum scan. Seperti sisi guru, alpha
            // jenis ini baru mulai tertagih setelah tanggal berganti.
            return $tgl < date('Y-m-d') ? 'alpha' : null;
        }

        // Bolos: hadir fisik (state present/late dan jam masuk terisi) tapi
        // tidak pernah absen pulang, dan harinya sudah tertutup. Status hadir
        // yang dipakai piket, cron penanda pulang, dan rekap adalah status
        // yang sama karena penilaiannya satu fungsi yang sama.
        if ($sel['state'] !== 'present' && $sel['state'] !== 'late') {
            return null;
        }
        if ($sel['time_in'] === '' || $sel['time_out'] !== '') {
            return null;
        }

        return $this->hariTertutup($tgl, $rec) ? 'bolos' : null;
    }

    /**
     * Hari tertutup = tidak ada lagi perubahan presensi yang masuk akal untuk
     * hari itu: tanggalnya sudah lewat, atau cron penutup hari sudah menandai
     * barisnya sore ini.
     */
    private function hariTertutup(string $tgl, ?object $rec): bool
    {
        $today = date('Y-m-d');
        if ($tgl < $today) {
            return true;
        }
        if ($tgl > $today) {
            return false;
        }

        return (int) ($rec->pulang_tidak_tercatat ?? 0) === 1;
    }

    /**
     * Pelanggaran satu baris presensi, tanpa konteks rekap. Dipakai jalur
     * tulis (scan, piket, cron) yang hanya punya satu baris.
     *
     * @return 'alpha'|'bolos'|null
     */
    public function pelanggaran(object $row): ?string
    {
        return $this->pelanggaranSel(
            $row,
            $this->konteksHari((string) ($row->tgl ?? ''), (int) ($row->id_kelas ?? 0))
        );
    }

    private function keterangan(string $jenis, float $nominal, string $tgl): string
    {
        return sprintf(
            'Otomatis: %s (%s) × Rp %s/hari',
            $jenis === 'alpha' ? 'alpha tanpa keterangan' : 'masuk tanpa absen pulang',
            $tgl,
            number_format($nominal, 0, ',', '.')
        );
    }

    /**
     * Nilai yang harus tersimpan pada sebuah baris presensi.
     *
     * Baris yang sudah tertagih untuk jenis yang sama tidak dihitung ulang dari
     * tarif hari ini: sekolah boleh mengubah tarif tanpa mengubah tagihan yang
     * sudah tercetak di rekap bulan sebelumnya, dan mematikan tarif sementara
     * tidak menghapus riwayat. Yang ditulis ulang hanya baris yang jenis
     * pelanggarannya berubah atau yang belum pernah tertagih.
     *
     * @param string|null $jenis hasil penilaian sel, bila sudah tersedia
     * @return array{denda:float, jenis:string, keterangan:?string}
     */
    public function nilaiBaris(object $row, ?string $jenis = null): array
    {
        $jenis ??= $this->pelanggaran($row);
        $tgl     = substr((string) ($row->tgl ?? ''), 0, 10);
        $lama    = round((float) ($row->denda ?? 0), 2);

        if ($jenis === null) {
            return ['denda' => 0.0, 'jenis' => '', 'keterangan' => null];
        }

        if ($lama > 0 && strtolower(trim((string) ($row->denda_jenis ?? ''))) === $jenis) {
            return ['denda' => $lama, 'jenis' => $jenis, 'keterangan' => $this->keterangan($jenis, $lama, $tgl)];
        }

        $tarif = $this->tarif($jenis);
        if ($tarif <= 0) {
            return ['denda' => 0.0, 'jenis' => '', 'keterangan' => null];
        }

        return ['denda' => $tarif, 'jenis' => $jenis, 'keterangan' => $this->keterangan($jenis, $tarif, $tgl)];
    }

    /**
     * Hitung ulang denda satu baris presensi. Idempoten: baris yang sudah
     * sesuai tidak disentuh, jadi pemanggil boleh mengulang kapan saja.
     *
     * @return array{status:bool, message:string, denda:float, diubah:bool}
     */
    public function recalc(int $presensiId): array
    {
        if ($presensiId <= 0) {
            return $this->hasil('ID presensi tidak valid', 0.0);
        }

        $db = \Config\Database::connect();
        if (! $this->siap($db)) {
            return $this->hasil('Kolom denda siswa belum siap', 0.0);
        }

        try {
            $row = $db->table('presensi_siswa')->where('id', $presensiId)->get()->getRow();
            if (! $row) {
                return $this->hasil('Presensi tidak ditemukan', 0.0);
            }

            $nilai = $this->nilaiBaris($row);
            if ($this->sudahSama($row, $nilai)) {
                return ['status' => true, 'message' => 'Denda sudah sesuai', 'denda' => $nilai['denda'], 'diubah' => false];
            }

            $tertulis = $db->table('presensi_siswa')
                ->where('id', $presensiId)
                ->update([
                    'denda'            => $nilai['denda'],
                    'denda_jenis'      => $nilai['jenis'],
                    'keterangan_denda' => $nilai['keterangan'],
                ]);
            if ($tertulis === false) {
                log_message('error', '[SiswaDendaService::recalc#' . $presensiId . '] update gagal: ' . json_encode($db->error()));
                return $this->hasil('Denda gagal tersimpan', 0.0);
            }

            return [
                'status'  => true,
                'message' => $nilai['keterangan'] ?? 'Denda dihapus (tidak ada pelanggaran)',
                'denda'   => $nilai['denda'],
                'diubah'  => true,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::recalc#' . $presensiId . '] ' . $e->getMessage());
            return $this->hasil('Gagal menghitung denda', 0.0);
        }
    }

    /**
     * Baris sudah memuat angka yang benar? Perbandingan Rupiah dilakukan pada
     * 2 desimal karena kolomnya DECIMAL(10,2).
     */
    private function sudahSama(object $row, array $nilai): bool
    {
        $lama = round((float) ($row->denda ?? 0), 2);

        return abs($lama - round((float) $nilai['denda'], 2)) < 0.005
            && strtolower(trim((string) ($row->denda_jenis ?? ''))) === $nilai['jenis']
            && trim((string) ($row->keterangan_denda ?? '')) === (string) $nilai['keterangan'];
    }

    /**
     * Inti perhitungan ulang: SATU baca untuk seluruh rentang, lalu SATU tulis
     * per kelompok nilai. Satu bulan penuh biasanya hanya butuh segelintir
     * query karena semua siswa pada tanggal yang sama menanggung nominal yang
     * sama; versi sebelumnya membuat satu SELECT + UPDATE per baris, yaitu
     * ribuan query untuk sekolah dengan beberapa ratus siswa.
     *
     * @return array{teriksa:int, diubah:int, tertagih:int, total:float, message:string}
     */
    public function recalcRentang(string $awal, string $akhir): array
    {
        $kosong = ['teriksa' => 0, 'diubah' => 0, 'tertagih' => 0, 'total' => 0.0, 'message' => ''];
        $db = \Config\Database::connect();
        if (! $this->siap($db)) {
            return $kosong + ['message' => 'Kolom denda siswa belum siap — jalankan migrasi AddDendaSiswaSettings'];
        }

        $awal  = substr(trim($awal), 0, 10);
        $akhir = substr(trim($akhir), 0, 10);
        if ($awal === '' || $akhir === '' || $awal > $akhir) {
            return $kosong + ['message' => 'Rentang tanggal tidak valid'];
        }

        $kolom = 'id, id_siswa, id_kelas, tgl, status, jam_masuk, jam_keluar, denda, denda_jenis, keterangan_denda';
        try {
            if ($db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) {
                $kolom .= ', pulang_tidak_tercatat';
            }
            $rows = $db->table('presensi_siswa')->select($kolom)
                ->where('tgl >=', $awal)
                ->where('tgl <=', $akhir)
                ->get()->getResult();
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::recalcRentang] baca gagal: ' . $e->getMessage());
            return $kosong + ['message' => 'Gagal membaca presensi siswa — periksa log aplikasi'];
        }

        $tertagih = 0;
        $total    = 0.0;
        // Baris dengan nilai yang sama persis ditulis dalam satu UPDATE.
        $kelompok = [];
        foreach ($rows as $r) {
            $nilai = $this->nilaiBaris($r);
            if ((float) $nilai['denda'] > 0) {
                $tertagih++;
                $total += (float) $nilai['denda'];
            }
            if ($this->sudahSama($r, $nilai)) {
                continue;
            }

            $kunci = $nilai['denda'] . '|' . $nilai['jenis'] . '|' . (string) $nilai['keterangan'];
            $kelompok[$kunci]['update'] = [
                'denda'            => $nilai['denda'],
                'denda_jenis'      => $nilai['jenis'],
                'keterangan_denda' => $nilai['keterangan'],
            ];
            $kelompok[$kunci]['ids'][] = (int) $r->id;
        }

        $tertulis = 0;
        foreach ($kelompok as $grup) {
            try {
                $db->table('presensi_siswa')->whereIn('id', $grup['ids'])->update($grup['update']);
                $tertulis += count($grup['ids']);
            } catch (\Throwable $e) {
                log_message('error', '[SiswaDendaService::recalcRentang] tulis gagal: ' . $e->getMessage());
            }
        }

        return [
            'teriksa'  => count($rows),
            'diubah'   => $tertulis,
            'tertagih' => $tertagih,
            'total'    => round($total, 2),
            'message'  => count($rows) . ' baris diperiksa, ' . $tertulis . ' diperbarui',
        ];
    }

    /**
     * Hitung ulang seluruh baris pada satu tanggal. Dipakai cron penutup hari,
     * setelah status alpha/bolos hari itu baru saja ditetapkan.
     */
    public function recalcHari(string $tanggal): int
    {
        $tgl = substr(trim($tanggal), 0, 10);

        return $tgl === '' ? 0 : $this->recalcRentang($tgl, $tgl)['diubah'];
    }

    /**
     * Hitung ulang satu bulan — dipakai untuk mengisi baris lama setelah
     * sekolah memasang tarif, dan oleh cron bulanan.
     *
     * @return array{teriksa:int, diubah:int, tertagih:int, total:float, message:string}
     */
    public function recalcBulan(int $bulan, int $tahun): array
    {
        $awal = sprintf('%04d-%02d-01', $tahun, $bulan);

        return $this->recalcRentang($awal, date('Y-m-t', strtotime($awal)));
    }

    /**
     * Hitung ulang denda BULAN BERJALAN.
     *
     * Mengerjakan dua hal yang selama ini berdiri sendiri: menulis nominal pada
     * baris presensi yang ada, lalu menghitung alpha tanpa baris yang hanya
     * bisa muncul saat rekap dibaca. Karena itu 'total_rekap' — bukan 'total'
     * — yang merupakan angka di kolom Denda rekap siswa; 'total' hanya bagian
     * yang benar-benar tersimpan di baris presensi.
     *
     * @return array{teriksa:int, diubah:int, tertagih:int, total:float,
     *               total_rekap:float, tanpa_baris:int, siswa:int,
     *               bulan:int, tahun:int, message:string}
     */
    public function recalcBulanBerjalan(?int $bulan = null, ?int $tahun = null): array
    {
        $bulan = $bulan !== null && $bulan >= 1 && $bulan <= 12 ? $bulan : (int) date('n');
        $tahun = $tahun !== null && $tahun >= 2000 && $tahun <= 2100 ? $tahun : (int) date('Y');

        $awal  = sprintf('%04d-%02d-01', $tahun, $bulan);
        $hasil = $this->recalcRentang($awal, date('Y-m-t', strtotime($awal)));

        $ringkas = [];
        try {
            $ringkas = $this->ringkasBulan($this->konteksBulan($bulan, $tahun));
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::recalcBulanBerjalan] ' . $e->getMessage());
        }

        $totalRekap = 0.0;
        $tanpaBaris = 0;
        foreach ($ringkas as $r) {
            $totalRekap += (float) ($r['netto'] ?? 0);
            $tanpaBaris += (int) ($r['tanpa_baris'] ?? 0);
        }

        return $hasil + [
            'total_rekap' => round($totalRekap, 2),
            'tanpa_baris' => $tanpaBaris,
            'siswa'       => count($ringkas),
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'message'     => sprintf(
                'Denda siswa %02d/%d: %s; %s hari alpha tanpa baris presensi (hanya terlihat di rekap), total tagihan Rp %s',
                $bulan,
                $tahun,
                $hasil['message'],
                number_format($tanpaBaris, 0, ',', '.'),
                number_format($totalRekap, 0, ',', '.')
            ),
        ];
    }

    /**
     * Susun konteks bulanan sendiri — seluruh siswa aktif pada tahun/semester
     * berjalan, sama seperti rekap "Semua Kelas" — untuk pemanggil di luar
     * controller rekap: spark command, tombol sinkron, cron.
     *
     * @return array{bulan:int, tahun:int, students:array, mapBySiswa:array, bdrByKelas:array}
     */
    public function konteksBulan(int $bulan, int $tahun): array
    {
        $db    = \Config\Database::connect();
        $awal  = sprintf('%04d-%02d-01', $tahun, $bulan);
        $akhir = date('Y-m-t', strtotime($awal));

        $tp  = $db->table('master_tp')->where('active', 1)->get()->getRow();
        $smt = $db->table('master_smt')->where('active', 1)->get()->getRow();

        $q = $db->table('master_siswa ms')
            ->select('ms.id_siswa, ms.nama, mk.id_kelas')
            ->join('kelas_siswa ks', 'ks.id_siswa = ms.id_siswa')
            ->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas')
            ->where('ks.id_tp', $tp->id_tp ?? 1)
            ->where('ks.id_smt', $smt->id_smt ?? 1);
        if ($db->tableExists('buku_induk')) {
            // Hanya AKTIF, seperti rekap; baris tanpa buku_induk (data lama)
            // tetap dianggap aktif.
            $q->join('buku_induk bi', 'bi.id_siswa = ms.id_siswa', 'left')
              ->groupStart()->where('bi.status', '1')
              ->orWhere('bi.id_siswa IS NULL', null, false)->groupEnd();
        }
        $students = $q->get()->getResult();

        $kolom = 'id, id_siswa, id_kelas, tgl, status, jam_masuk, jam_keluar, denda, denda_jenis';
        if ($db->fieldExists('pulang_tidak_tercatat', 'presensi_siswa')) {
            $kolom .= ', pulang_tidak_tercatat';
        }
        $mapBySiswa = [];
        $tercatatByKelas = [];
        foreach ($db->table('presensi_siswa')->select($kolom)
                   ->where('tgl >=', $awal)->where('tgl <=', $akhir)
                   ->get()->getResult() as $r) {
            $mapBySiswa[(int) $r->id_siswa][(string) $r->tgl] = $r;
            $tercatatByKelas[(int) ($r->id_kelas ?? 0)][substr((string) $r->tgl, 0, 10)] = true;
        }

        $bdrByKelas = [];
        foreach ($students as $s) {
            $idKelas = (int) ($s->id_kelas ?? 0);
            if ($idKelas > 0 && ! isset($bdrByKelas[$idKelas])) {
                $bdrByKelas[$idKelas] = $this->bdrService()->tanggalUntukKelas($awal, $akhir, $idKelas);
            }
        }

        return [
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'students'   => $students,
            'mapBySiswa' => $mapBySiswa,
            'bdrByKelas' => $bdrByKelas,
            'tercatatByKelas' => $tercatatByKelas,
        ];
    }

    /**
     * Denda per siswa untuk satu bulan rekap — angka yang dilihat sekolah di
     * kolom Denda.
     *
     * Inputnya persis yang dipakai view: daftar siswa, peta presensi, dan peta
     * BDR per kelas. Karena penilaiannya attendance_rekap_day_cell() juga,
     * kolom Alpha dan kolom Denda tidak bisa berbeda untuk siswa yang sama,
     * dan HTML, Excel, serta PDF membaca hasil fungsi yang sama ini.
     *
     * Baris presensi yang ada memakai nominal tersimpan (tarif terkunci, dan
     * bisa dilihat per hari). Alpha tanpa baris memakai tarif yang berlaku
     * sekarang, sebab tidak ada baris tempat menyimpannya.
     *
     * @param array{bulan?:int, tahun?:int, students?:iterable, mapBySiswa?:array, bdrByKelas?:array} $ctx
     * @return array<int, array{brutto:float, netto:float, perBulan:array<string,float>, cap:?float, alpha:int, bolos:int, tanpa_baris:int}>
     */
    public function ringkasBulan(array $ctx): array
    {
        $bulan      = (int) ($ctx['bulan'] ?? date('n'));
        $tahun      = (int) ($ctx['tahun'] ?? date('Y'));
        $students   = (array) ($ctx['students'] ?? []);
        $mapBySiswa = (array) ($ctx['mapBySiswa'] ?? []);
        // Peta BDR milik view dipakai bila pemanggil menyediakannya; tanpa itu
        // (jalur spark/cron) peta dibangun sendiri per tanggal.
        $bdrByKelas = isset($ctx['bdrByKelas']) ? (array) $ctx['bdrByKelas'] : null;
        // Peta hari tercatat milik view, kalau tidak ada dibangun dari tabel.
        if (isset($ctx['tercatatByKelas'])) {
            $this->pakaiPetaTercatat((array) $ctx['tercatatByKelas'], $bulan, $tahun);
        }

        $daysInMonth = (int) date('t', mktime(0, 0, 0, $bulan, 1, $tahun));

        // Kalender bulan ini dihitung sekali, bukan per siswa: 275 siswa ×
        // 31 hari akan mengulang tanggal yang sama ribuan kali.
        $kalender = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $kalender[$d] = [
                'tgl'               => $tgl,
                'is_libur'          => isset(AttendancePolicy::liburNasional()[$tgl]),
                'is_libur_mingguan' => $this->isLiburMingguan($tgl),
                'batas_masuk'       => $this->batasMasuk($tgl),
            ];
        }

        $out = [];
        foreach ($students as $siswa) {
            $siswa   = (object) $siswa;
            $idSiswa = (int) ($siswa->id_siswa ?? 0);
            $idKelas = (int) ($siswa->id_kelas ?? 0);
            if ($idSiswa <= 0) {
                continue;
            }

            $hariSiswa = $mapBySiswa[$idSiswa] ?? [];
            $bdrSiswa  = $bdrByKelas !== null ? ($bdrByKelas[$idKelas] ?? []) : null;

            $tertagih = [];
            foreach ($kalender as $d => $hari) {
                $tgl = $hari['tgl'];
                $rec = isset($hariSiswa[$tgl]) ? (object) $hariSiswa[$tgl] : null;

                $jenis = $this->pelanggaranSel($rec, $hari + [
                    'id_kelas' => $idKelas,
                    'is_bdr' => $bdrSiswa !== null
                        ? isset($bdrSiswa[$tgl])
                        : $idKelas > 0 && isset($this->kelasBdr($tgl)[$idKelas]),
                ]);
                if ($jenis === null) {
                    continue;
                }

                if ($rec !== null) {
                    $rp = (float) $this->nilaiBaris($rec, $jenis)['denda'];
                } else {
                    // Alpha tanpa baris: tidak ada tempat menyimpan nominal,
                    // jadi tarif yang berlaku sekarang yang menagih.
                    $rp = $this->tarif($jenis);
                }
                if ($rp <= 0) {
                    continue;
                }

                $tertagih[] = [
                    'tgl'         => $tgl,
                    'denda'       => $rp,
                    'denda_jenis' => $jenis,
                    'tersimpan'   => $rec !== null,
                ];
            }

            $out[$idSiswa] = $this->terapkanCap($tertagih);
        }

        return $out;
    }

    /**
     * Akumulasi denda satu siswa dengan plafon per bulan kalender.
     *
     * Setiap bulan dipotong sendiri: subtotal bulan itu tidak bisa melewati
     * cap, hari setelahnya tidak menambah apa pun. Cap diterapkan saat
     * pembacaan (bukan saat penulisan baris), jadi menggantinya tidak mengubah
     * riwayat yang sudah tersimpan di presensi_siswa.denda.
     *
     * @param iterable<object|array> $baris hari ber-denda milik SATU siswa
     * @return array{brutto:float, netto:float, perBulan:array<string,float>, cap:?float, alpha:int, bolos:int, tanpa_baris:int}
     */
    public function terapkanCap(iterable $baris, ?float $cap = null): array
    {
        $cap = round(max(0.0, (float) ($cap ?? $this->capBulan())), 2);

        $perBulan = [];
        $brutto = 0.0;
        $alpha = 0;
        $bolos = 0;
        $tanpaBaris = 0;
        foreach ($baris as $b) {
            $b = (object) $b;
            $tgl = substr((string) ($b->tgl ?? ''), 0, 10);
            $rp  = round((float) ($b->denda ?? 0), 2);
            if ($tgl === '' || $rp <= 0) {
                continue;
            }

            // Jenis dibaca eksplisit. Nominal ber-jenis lain tidak boleh
            // menambah "hari alpha" pada tooltip rekap hanya karena besarannya
            // bukan nol.
            $jenis = strtolower(trim((string) ($b->denda_jenis ?? '')));
            if ($jenis === 'bolos') {
                $bolos++;
            } elseif ($jenis === 'alpha') {
                $alpha++;
                if (! ($b->tersimpan ?? true)) {
                    $tanpaBaris++;
                }
            }

            $brutto += $rp;
            $bulan = substr($tgl, 0, 7);
            $perBulan[$bulan] = ($perBulan[$bulan] ?? 0.0) + $rp;
        }
        ksort($perBulan);

        $netto = 0.0;
        foreach ($perBulan as $rp) {
            $netto += $cap > 0 ? min($rp, $cap) : $rp;
        }

        return [
            'brutto'      => round($brutto, 2),
            'netto'       => round($netto, 2),
            'perBulan'    => $perBulan,
            'cap'         => $cap > 0 ? $cap : null,
            'alpha'       => $alpha,
            'bolos'       => $bolos,
            'tanpa_baris' => $tanpaBaris,
        ];
    }

    /**
     * Kolom & tarif siap dipakai? Instalasi yang belum bermigrasi tidak boleh
     * error — ia hanya belum menagih.
     */
    private function siap(object $db): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }

        try {
            $ada = $db->tableExists('presensi_siswa')
                && $db->fieldExists('denda', 'presensi_siswa')
                && $db->fieldExists('denda_jenis', 'presensi_siswa')
                && $db->fieldExists('keterangan_denda', 'presensi_siswa');
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::siap] ' . $e->getMessage());
            $ada = false;
        }

        if (! $ada) {
            log_message('warning', '[SiswaDendaService] kolom presensi_siswa.denda belum ada — denda siswa dilewati, jalankan migrasi AddDendaSiswaSettings');
        }

        return $ok = $ada;
    }

    private function hasil(string $msg, float $denda): array
    {
        return ['status' => false, 'message' => $msg, 'denda' => $denda, 'diubah' => false];
    }

    // ── Pintasan untuk dipanggil dari controller ───────────────────

    /**
     * Hitung ulang denda dari sebuah baris presensi. Tidak pernah melempar
     * error ke pemanggil — kegagalan denda tidak boleh membatalkan absensi.
     */
    public static function sinkron(?int $presensiId = null, ?string $tanggal = null): void
    {
        try {
            $svc = new self();
            if ($presensiId !== null && $presensiId > 0) {
                $svc->recalc($presensiId);
            } elseif ($tanggal !== null && trim($tanggal) !== '') {
                $svc->recalcHari($tanggal);
            }
        } catch (\Throwable $e) {
            log_message('error', '[SiswaDendaService::sinkron] ' . $e->getMessage());
        }
    }
}
