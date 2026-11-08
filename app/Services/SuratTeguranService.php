<?php

namespace App\Services;

/**
 * Penerbitan dan sinkronisasi surat teguran guru.
 *
 * Satu-satunya tempat yang boleh menulis ke absensi_surat_teguran.
 * Sebelumnya logikanya tersebar di tiga tempat — CronController
 * (generateWarningLetters), Guru/Attendance (recordSuratTeguran), dan
 * AttendancePenaltyService (syncSuratTeguranOnAttendanceChange) — dengan
 * aturan yang saling berbeda. Perbedaan itulah sumber bug produksi:
 *
 *   1. Cron memakai `continue` saat slot level sudah terisi, sehingga guru
 *      yang sudah dapat tingkat III lalu menerima tingkat II dan I di
 *      evaluation berikutnya. Terlihat di data: guru id_guru=4 bulan
 *      9/2026 — tingkat 7 (nomor 27, 15 Sep) lalu tingkat 3 (nomor 30,
 *      24 Sep).
 *
 *   2. Pengecekan "alpha >= ambang" dilakukan SEBELUM pengecekan slot, jadi
 *      begitu surat berat dibatalkan (izin disetujui retroaktif) dan alpha
 *      naik lagi, guru "diterobos" ke slot ringan dan mendapat surat baru.
 *      Pola sama ada di produksi: guru id_guru=4 tingkat 7 terbit,
 *      dibatalkan, lalu tingkat 3 terbit dua minggu kemudian.
 *
 *   3. recordSuratTeguran() menulis id_guru = 0 saat resolveIdGuru() gagal.
 *      Surat semacam itu tidak muncul di daftar admin (INNER JOIN), tidak
 *      bisa diunduh, tidak bisa dibatalkan — tapi tetap membakar nomor urut
 *      dan memblokir slot guru lain lewat unique key. Ada 9 baris seperti
 *      ini untuk Juni–Agustus 2026.
 *
 * Aturan yang ditegakkan di sini:
 *
 *   - MONOTON: dalam satu periode (guru, bulan, tahun), surat terbit pada
 *     level terberat yang sudah dilampaui, dan tingkatnya tidak pernah
 *     turun. Kalau slot level terberat terisi — termasuk yang statusnya
 *     'dibatalkan' — tidak ada surat lain yang terbit.
 *
 *   - SLOT PERMANEN: baris yang dibatalkan tetap menempati slotnya karena
 *     suratnya pernah terbit dan notifikasi sudah terkirim. Unique key
 *     (id_guru, bulan, tahun, level) juga menahannya. Karena itu slot
 *     'dibatalkan' bisa diaktifkan KEMBALI oleh sinkronkan(), bukan diisi
 *     baris baru — itulah yang menutup celah "guru sudah berhak atas surat
 *     tapi tidak pernah menerimanya".
 *
 *   - SNAPSHOT: jumlah_alpha disimpan saat surat terbit dan tidak diubah
 *     lagi, karena isi arsip tidak boleh ikut berubah saat data presensi
 *     diperbaiki. Angka yang tidak terbaca (0) diisi minimal ambang surat
 *     daripada dibiarkan menebak.
 *
 *   - ATRIBUSI WAJIB: surat hanya ditulis untuk id_guru yang sudah pasti.
 *     Sembilan baris yatim yang sudah terlanjur ada tidak bisa dikembalikan
 *     ke gurunya — data presensi Mei–Juli 2026 tidak ada, jadi atribusinya
 *     hanya bisa ditebak, dan menebak dokumen legal lebih berbahaya daripada
 *     membiarkannya tidak terlacak.
 */
final class SuratTeguranService
{
    public const STATUS_AKTIF     = 'aktif';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    private const TABEL = 'absensi_surat_teguran';

    /** @var \CodeIgniter\Database\ConnectionInterface */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function tabelAda(): bool
    {
        return $this->db->tableExists(self::TABEL);
    }

    /**
     * Kepala sekolah DIKECUALIKAN dari surat teguran.
     *
     * Beliau adalah penandatangan (TTE) surat teguran guru lain; menerbitkan
     * surat atas nama dirinya sendiri berarti dokumen yang ditandatangani
     * orang yang sama dengan yang ditegur. Kecualikan di pusat — service ini
     * satu-satunya yang boleh menulis ke absensi_surat_teguran — supaya
     * semua jalur (cron alpha, cron evaluasi bulanan, check-in, sinkron
     * koreksi presensi, kandidat manual operator) otomatis tertutup.
     *
     * Deteksi mengikuti pola yang sama dengan Guru\Attendance::isGuruKepalaSekolah():
     * jenis_ptk / tgs_tambahan mengandung "kepala sekolah" / "kepsek",
     * dengan fallback NIP guru sama dengan NIP kepsek di tabel setting.
     */
    public function adalahKepalaSekolah(int $idGuru): bool
    {
        if ($idGuru <= 0) {
            return false;
        }

        try {
            if (! $this->db->tableExists('master_guru')) {
                return false;
            }

            $select = ['nama_guru', 'nip'];
            foreach (['jenis_ptk', 'tgs_tambahan'] as $kolom) {
                if ($this->db->fieldExists($kolom, 'master_guru')) {
                    $select[] = $kolom;
                }
            }

            $guru = $this->db->table('master_guru')
                ->select(implode(', ', $select))
                ->where('id_guru', $idGuru)
                ->get()->getRow();

            if ($guru === null) {
                return false;
            }

            foreach (['jenis_ptk', 'tgs_tambahan'] as $kolom) {
                $nilai = strtolower(trim((string) ($guru->{$kolom} ?? '')));
                if ($nilai !== ''
                    && (str_contains($nilai, 'kepala sekolah') || str_contains($nilai, 'kepsek'))) {
                    return true;
                }
            }

            // Fallback: NIP guru sama dengan NIP kepsek yang tercatat di setting.
            // PresensiAdmin memakai setting->nip untuk mengenali kepsek di
            // daftar guru (kepsekNip) — sumbernya sama di sini.
            if ($this->db->tableExists('setting')) {
                $row       = $this->db->table('setting')->select('nip')->get()->getRow();
                $nipKepsek = strtolower(trim((string) ($row->nip ?? '')));
                $nipGuru   = strtolower(trim((string) ($guru->nip ?? '')));
                if ($nipKepsek !== '' && $nipGuru !== '' && $nipGuru === $nipKepsek) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[SuratTeguran] deteksi kepala sekolah: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Terbitkan surat teguran bila guru berhak dan periodenya belum punya
     * surat pada level itu.
     *
     * @param int    $alpha Akumulasi alpha bulan ini (sudah kanonik)
     * @param string $bulan '01'..'12' atau '1'..'12'
     * @param string $tahun 'YYYY'
     *
     * @return array{id:int,level:int}|null Baris yang dibuat, null kalau tidak terbit
     */
    public function terbitkan(int $idGuru, int $alpha, string $bulan, string $tahun): ?array
    {
        if (! $this->tabelAda()) {
            return null;
        }

        if ($idGuru <= 0 || $alpha <= 0) {
            return null;
        }

        // Kepala sekolah tidak pernah ditegur — penandatangan surat guru.
        if ($this->adalahKepalaSekolah($idGuru)) {
            log_message('info', '[SuratTeguran] kepala sekolah id_guru ' . $idGuru . ' dikecualikan (tidak ditegur).');
            return null;
        }

        $bulan = $this->bulan($bulan);
        $tahun = $this->tahun($tahun);
        if ($bulan === null || $tahun === null) {
            return null;
        }

        // Level terberat yang sudah dilampaui. Null = belum berhak.
        $level = \App\Libraries\SuratTeguranLevel::levelUntukAlpha($alpha);
        if ($level === null) {
            return null;
        }

        foreach (\App\Libraries\SuratTeguranLevel::ambang() as $ambang) {
            // SLOT DULU, alpha setelah. Urutan ini inti perbaikan: begitu
            // slot level terberat terisi — bahkan kalau isinya sudah
            // dibatalkan — loop berhenti dan tidak ada surat ringan yang
            // menyusul.
            if ($this->slotTerpakai($idGuru, $bulan, $tahun, $ambang)) {
                return null;
            }

            if ($alpha < $ambang) {
                continue;
            }

            $id = $this->tulis($idGuru, $bulan, $tahun, $ambang, $alpha);

            return $id === null ? null : ['id' => $id, 'level' => $ambang];
        }

        return null;
    }

    /**
     * Samakan status surat dengan kondisi alpha terkini — dua arah.
     *
     * MEMBATALKAN: alpha turun di bawah ambang (izin/sakit/dinas disetujui
     * retroaktif, atau hari alpha diisi manual). Surat tidak sah lagi.
     *
     * MENGHIDUPKAN KEMBALI: pembatalan di atas beralasan, jadi alasan itu
     * bisa dicabut. Kalau izinnya dibatalkan operator atau absen manualnya
     * dikoreksi kembali, surat yang tadinya gugur harus kembali aktif.
     * Tanpa ini guru tetap kehilangan haknya: slot-nya masih terisi baris
     * yang dibatalkan, dan baris itu tidak akan pernah terbit ulang karena
     * monotonic + slot permanen.
     *
     * Hanya pembatalan OTOMATIS yang dibalik — pembatalan manual operator
     * dianggap keputusan final. Tanpa penanda `dibatalkan_otomatis`,
     * menghidupkan kembali surat yang sengaja dicabut akan sama saja.
     *
     * @param string|null $reason Alasan pembatalan; null saat menghidupkan
     *
     * @return array{batal:list<int>,hidup:list<int>} Id baris yang berubah
     */
    public function sinkronkan(int $idGuru, int $alpha, string $bulan, string $tahun, ?string $reason = null): array
    {
        $hasil = ['batal' => [], 'hidup' => []];

        if ($idGuru <= 0 || ! $this->tabelAda()) {
            return $hasil;
        }

        // Kepala sekolah dikecualikan: surat yang sudah terlanjur ada atas
        // namanya tidak ikut dibalik otomatis — pembatalan/hidup ulang surat
        // pemegang TTE justru memutar dokumen yang tidak seharusnya ada.
        if ($this->adalahKepalaSekolah($idGuru)) {
            return $hasil;
        }

        $bulan = $this->bulan($bulan);
        $tahun = $this->tahun($tahun);
        if ($bulan === null || $tahun === null) {
            return $hasil;
        }

        $punyaOtomatis = $this->punyaKolomOtomatis();

        $select = 'id, level, status';
        if ($punyaOtomatis) {
            $select .= ', dibatalkan_otomatis';
        } elseif ($this->db->fieldExists('keterangan_batal', self::TABEL)) {
            // Fallback untuk database yang belum menjalankan migrasi kolom
            // penanda: pakai teks alasannya sebagai pembeda. Tidak seideal
            // flag, tapi mencegah pembatalan manual ikut dibalik.
            $select .= ', keterangan_batal';
        }

        $rows = $this->db->table(self::TABEL)
            ->select($select)
            ->where('id_guru', $idGuru)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $level    = (int) $row['level'];
            $dibatalkan = ($row['status'] ?? self::STATUS_AKTIF) === self::STATUS_DIBATALKAN;
            $masihSah = \App\Libraries\SuratTeguranLevel::masihSah($level, $alpha);

            if (! $dibatalkan) {
                // Aktif dan masih sah: tidak ada yang perlu diubah.
                if ($masihSah) {
                    continue;
                }

                $update = [
                    'status'           => self::STATUS_DIBATALKAN,
                    'keterangan_batal' => trim((string) ($reason ?? 'Alpha turun di bawah ambang surat'))
                        . ' (sisa alpha: ' . $alpha . ' hari)',
                ];
                if ($punyaOtomatis) {
                    $update['dibatalkan_otomatis'] = 1;
                }

                $this->db->table(self::TABEL)->where('id', $row['id'])->update($update);
                $hasil['batal'][] = (int) $row['id'];

                continue;
            }

            // Dibatalkan. Hidupkan lagi kalau alasannya sudah gugur DAN
            // pembatalannya otomatis.
            if (! $masihSah) {
                continue;
            }

            if (! $this->pembatalanOtomatis($row, $punyaOtomatis)) {
                continue; // manual — keputusan operator, tidak ditimpa
            }

            $update = [
                'status'           => self::STATUS_AKTIF,
                'keterangan_batal' => null,
            ];
            if ($punyaOtomatis) {
                $update['dibatalkan_otomatis'] = 0;
            }

            $this->db->table(self::TABEL)->where('id', $row['id'])->update($update);
            $hasil['hidup'][] = (int) $row['id'];
        }

        return $hasil;
    }

    /**
     * Resolve id_guru dari user, tanpa menebak.
     *
     * Satu-satunya sumber: master_guru berdasarkan id_user. Kalau tidak
     * ada, surat TIDAK ditulis dan pemanggil harus membatalkan penerbitannya.
     * Auto-create baris master_guru seperti versi lama justru menghasilkan
     * surat yatim yang tidak bisa dilacak.
     */
    public function idGuruDariUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        try {
            $row = $this->db->table('master_guru')
                ->select('id_guru')
                ->where('id_user', $userId)
                ->get()
                ->getRow();
        } catch (\Throwable $e) {
            log_message('error', '[SuratTeguranService] idGuruDariUser gagal: ' . $e->getMessage());

            return 0;
        }

        return $row ? (int) $row->id_guru : 0;
    }

    // ── internal ─────────────────────────────────────────────────────

    private function slotTerpakai(int $idGuru, int $bulan, int $tahun, int $level): bool
    {
        return $this->db->table(self::TABEL)
            ->where('id_guru', $idGuru)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('level', $level)
            ->countAllResults() > 0;
    }

    /**
     * Tulis satu baris surat.
     *
     * Nomor urut dialokasikan lewat GET_LOCK per tahun. MAX+1 polos rawan
     * race: dua penerbitan paralel untuk guru BERBEDA (dua check-in yang
     * sama-sama melampaui ambang, atau cron + tombol admin) membaca MAX
     * yang sama dan sama-sama mendapat nomor identis — UNIQUE hanya menjaga
     * (id_guru, bulan, tahun, level), bukan nomor. Dua dokumen legal dengan
     * nomor sama itu rusak arsip; kunci pendek menutupnya tanpa hak
     * istimewa DB. Bila mekanisme lock gagal, tetap lanjut (fail-open):
     * slot tetap dijaga UNIQUE, hanya nomor yang berisiko bentrok.
     */
    private function tulis(int $idGuru, int $bulan, int $tahun, int $level, int $alpha): ?int
    {
        $kunci   = 'absen:teguran:nomor:' . $tahun;
        $dikunci = false;
        try {
            $row      = $this->db->query('SELECT GET_LOCK(?, 5) AS l', [$kunci])->getRow();
            $dikunci = (int) ($row->l ?? 0) === 1;
        } catch (\Throwable $e) {
            log_message('error', '[SuratTeguranService] GET_LOCK gagal: ' . $e->getMessage());
        }

        try {
            $last = $this->db->table(self::TABEL)
                ->selectMax('nomor_urut', 'max_urut')
                ->where('tahun', $tahun)
                ->get()
                ->getRow();

            $row = [
                'id_guru'     => $idGuru,
                'level'       => $level,
                'bulan'       => $bulan,
                'tahun'       => $tahun,
                'nomor_urut'  => (int) ($last->max_urut ?? 0) + 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ];

            // Snapshot alpha saat terbit. Minimal ambang surat: PDF tidak boleh
            // menampilkan angka lebih kecil dari tingkat yang disebut di dalamnya.
            if ($this->db->fieldExists('jumlah_alpha', self::TABEL)) {
                $row['jumlah_alpha'] = max($level, $alpha);
            }
            if ($this->db->fieldExists('status', self::TABEL)) {
                $row['status'] = self::STATUS_AKTIF;
            }

            try {
                $this->db->table(self::TABEL)->insert($row);
            } catch (\Throwable $e) {
                // Dua request paralel bisa lolos pengecekan slot bersamaan;
                // UNIQUE yang menolak. Itu hasil yang benar — surat tetap satu.
                // Error lain (koneksi putus, skema berubah) tidak boleh lenyap
                // tanpa jejak: dulu cabang ini menelan SEMUA error diam-diam
                // begitu kolom jumlah_alpha ada.
                if (! $this->apakahDuplikatSlot($e)) {
                    log_message('error', '[SuratTeguranService] tulis gagal: ' . $e->getMessage());
                }

                return null;
            }

            $id = (int) $this->db->insertID();

            return $id > 0 ? $id : null;
        } finally {
            if ($dikunci) {
                try {
                    $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
                } catch (\Throwable $e) {
                    // Koneksi ditutup di akhir request; lock lepas sendiri.
                }
            }
        }
    }

    /** Apakah error ini penolakan UNIQUE (slot sudah terisi), bukan kegagalan lain? */
    private function apakahDuplikatSlot(\Throwable $e): bool
    {
        if ((int) $e->getCode() === 1062) {
            return true;
        }
        $pesan = (string) $e->getMessage();

        return str_contains($pesan, 'Duplicate entry')
            || str_contains($pesan, 'uniq_surat_teguran')
            || str_contains($pesan, '1062');
    }

    private function punyaKolomOtomatis(): bool
    {
        return $this->db->fieldExists('dibatalkan_otomatis', self::TABEL);
    }

    /**
     * Apakah baris ini dibatalkan oleh sistem (bukan operator)?
     *
     * Sumber utama adalah flag dibatalkan_otomatis. Kalau kolomnya belum
     * ada, teks keterangan_batal dipakai sebagai pengganti: hanya alasan
     * yang menjelaskan alpha turun yang dianggap otomatis. Alasan lain
     * (mis. "sudah dihapus manual") dianggap keputusan operator.
     */
    private function pembatalanOtomatis(array $row, bool $punyaOtomatis): bool
    {
        if ($punyaOtomatis) {
            return (int) ($row['dibatalkan_otomatis'] ?? 0) === 1;
        }

        $teks = mb_strtolower(trim((string) ($row['keterangan_batal'] ?? '')));

        return $teks !== ''
            && (str_contains($teks, 'sisa alpha')
                || str_contains($teks, 'alpha turun')
                || str_contains($teks, 'dispensasi')
                || str_contains($teks, 'izin')
                || str_contains($teks, 'dinas'));
    }

    /** @return int 1..12, null kalau tidak valid */
    private function bulan(string $bulan): ?int
    {
        if (! ctype_digit(trim($bulan))) {
            return null;
        }
        $n = (int) $bulan;

        return ($n >= 1 && $n <= 12) ? $n : null;
    }

    private function tahun(string $tahun): ?int
    {
        if (! ctype_digit(trim($tahun))) {
            return null;
        }
        $n = (int) $tahun;

        return ($n >= 2000 && $n <= 2100) ? $n : null;
    }
}
