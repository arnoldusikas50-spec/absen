<?php

namespace App\Services;

use App\Models\PoinSiswaModel;

/**
 * SiswaPoinService — jembatan presensi siswa → poin disiplin siswa.
 *
 * Absensi siswa (tabel `presensi_siswa`) tidak punya kolom poin sama sekali,
 * sedangkan poin siswa hidup di `poin_siswa_history` yang tadinya hanya diisi
 * manual oleh admin. Service ini menutup celah itu: setiap kali baris
 * presensi berubah (scan QR, absen manual, koreksi jam, hapus), poin
 * dihitung ulang dari nol untuk baris tersebut.
 *
 *Prinsip: REBUILD, bukan menumpuk.
 *   Satu baris presensi punya tepat satu entri ledger dengan ref_id =
 *   'presensi-{id}'. Setiap panggilan menghapus entri lama lalu menulis
 *   ulang bila syaratnya masih terpenuhi. Efeknya idempoten dan self-healing:
 *   jam absen dikoreksi jadi tepat waktu, atau barisnya dihapus, maka
 *   pengurangannya ikut hilang — tidak ada poin yatim.
 *
 * Ambang keterlambatan sengaja memakai rumus yang sama dengan rekap
 * (absensi_setting.jam_masuk + grace_period) supaya poin terpotong tepat
 * ketika rekap memberi badge "terlambat".
 *
 * Kill-switch: matikan auto-poin cukup menonaktifkan kategorinya di
 * /admin/poin-siswa (menu Kategori). Kategori nonaktif tidak pernah dipakai.
 */
class SiswaPoinService
{
    /** Status yang menandai kehadiran, sehingga tidak dipoin. */
    private const STATUS_HADIR = ['HADIR', 'HADIR TELAT', 'TERLAMBAT'];

    /** Status absen yang berarti tidak masuk sekolah. */
    private const STATUS_ALPA = ['ALPA', 'TANPA_KETERANGAN', 'BOS', 'ALPA (TIDAK ADA KETERANGAN)'];

    /** Status izin resmi — bukan pelanggaran, tidak dipoin. */
    private const STATUS_IZIN = ['IZIN', 'SAKIT', 'CUTI', 'DINAS'];

    private ?PoinSiswaModel $poin = null;

    private function poin(): PoinSiswaModel
    {
        if ($this->poin === null) {
            $this->poin = new PoinSiswaModel();
            $this->poin->ensureTables();
        }
        return $this->poin;
    }

    /**
     * Batas akhir masuk (jam_masuk + grace, inklusif sampai detik ke-59)
     * untuk satu tanggal, format H:i:s. Sumbernya AttendancePolicy — jadwal
     * per hari (absensi_jadwal_kerja) lalu absensi_setting — sehingga poin,
     * scan, rekap, dan arsip piket memakai angka yang sama.
     */
    public function batasMasuk(?string $tanggal = null): string
    {
        $tgl = $this->normalTanggal($tanggal);

        return date('H:i:s', AttendancePolicy::batasAkhirMasukTs(
            $tgl,
            AttendancePolicy::jadwalSiswa($tgl)
        ));
    }

    /**
     * Menit keterlambatan. 0 bila tepat waktu, tidak absen, atau izin resmi.
     *
     * @param string|null $tanggal Tanggal baris presensi (Y-m-d). Wajib dikirim
     *                             oleh pemanggil yang membaca baris historis —
     *                             tanpa ini jadwal per hari tidak berlaku.
     */
    public function menitTerlambat(?string $jamMasuk, ?string $status = null, ?string $tanggal = null): int
    {
        $status = strtoupper(trim((string) $status));
        if (in_array($status, self::STATUS_IZIN, true) || in_array($status, self::STATUS_ALPA, true)) {
            return 0;
        }
        if (trim((string) $jamMasuk) === '') {
            return 0;
        }

        $tgl = $this->normalTanggal($tanggal);

        return AttendancePolicy::telat($tgl, (string) $jamMasuk, AttendancePolicy::jadwalSiswa($tgl))['telat_menit'];
    }

    /** Y-m-d dari input bebas; fallback ke hari ini. */
    private function normalTanggal(?string $tanggal): string
    {
        $tanggal = trim((string) $tanggal);
        if ($tanggal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $tanggal) === 1) {
            return substr($tanggal, 0, 10);
        }

        return date('Y-m-d');
    }

    /**
     * Kategori disiplin yang cocok untuk sebuah kondisi absensi.
     * Mengembalikan null bila tak ada kategori aktif → poin tidak berubah.
     */
    private function kategori(string $cariNama): ?object
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('disiplin_kategori')) {
                return null;
            }
            return $db->table('disiplin_kategori')
                ->where('jenis', 'PELANGGARAN')
                ->where('is_active', 1)
                ->like('nama', $cariNama, 'both')
                ->orderBy('id', 'ASC')
                ->get()->getRow();
        } catch (\Throwable $e) {
            log_message('error', '[SiswaPoinService::kategori] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pelanggaran apa yang dilakukan sebuah baris presensi — TIDAK termasuk
     * pencarian kategori aktif.
     *
     * Pemisahan ini penting: "tidak ada pelanggaran" dan "ada pelanggaran tapi
     * kategorinya dinonaktifkan/rename" adalah dua kondisi berbeda. Kalau
     * keduanya dianggap sama, lalu kategori "Terlambat" dinonaktifkan, maka
     * recalc berikutnya akan menghapus entri lama tanpa menggantinya dan skor
     * siswa naik diam-diam. Lihat recalc() untuk penanganannya.
     *
     * @return array{cari:string, keterangan:string}|null
     */
    private function pelanggaranPresensi(object $row): ?array
    {
        $status = strtoupper(trim((string) ($row->status ?? '')));
        $tgl    = substr((string) ($row->tgl ?? '-'), 0, 10);

        if (in_array($status, self::STATUS_IZIN, true)) {
            return null; // izin resmi bukan pelanggaran
        }

        if ($status === 'BDR') {
            return null; // belajar dari rumah = kebijakan sekolah, bukan pelanggaran
        }

        if (in_array($status, self::STATUS_ALPA, true)) {
            return [
                'cari'       => 'bolos',
                'keterangan' => 'Otomatis: alpha tanpa keterangan (' . $tgl . ')',
            ];
        }

        $telat = $this->menitTerlambat($row->jam_masuk ?? null, $status, $tgl);
        if ($telat > 0) {
            return [
                'cari'       => 'terlambat',
                'keterangan' => 'Otomatis: terlambat ' . $telat . ' menit (' . $tgl . ')',
            ];
        }

        return null;
    }

    /**
     * Hitung ulang poin untuk satu baris presensi.
     *
     * Aman dipanggil berulang: entri lama dihapus lebih dulu.
     * Dipakai juga untuk "membatalkan" — presensi yang jadi tepat waktu atau
     * dihapus tidak menyisakan pengurangan.
     *
     * @return array{status:bool, message:string, poin:int, skor:?int, skipped:bool}
     */
    public function recalc(int $presensiId): array
    {
        if ($presensiId <= 0) {
            return $this->hasil('ID presensi tidak valid', 0);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('poin_siswa_history')) {
            return $this->hasil('Tabel poin belum siap', 0);
        }

        $ref = 'presensi-' . $presensiId;
        $poinModel = $this->poin();

        // Transaksi: hapus-lama + tulis-baru + cache harus atomik agar
        // dua recalc paralel (scan + cron) tidak saling menimpa.
        // Catatan: MySQLi tetap commit walau sebuah query gagal, jadi hasil
        // setiap tulis diperiksa eksplisit — kalau tidak, poin lama bisa
        // terhapus tanpa pengganti dan skor siswa naik diam-diam.
        try {
            $db->transBegin();
            $row = $db->table('presensi_siswa')->where('id', $presensiId)->get()->getRow();
            if (!$row) {
                // Baris presensi dihapus → lengkapi juga pengurangan point-nya.
                $dihapus = $db->table('poin_siswa_history')->where('ref_id', $ref)->get()->getResult();
                $idSiswa = 0;
                foreach ($dihapus as $d) {
                    $idSiswa = (int) $d->id_siswa;
                }
                if ($idSiswa > 0) {
                    if ($db->table('poin_siswa_history')->where('ref_id', $ref)->delete() === false) {
                        return $this->gagalTrans($db, 'Gagal membatalkan poin presensi yang dihapus');
                    }
                    if ($db->transStatus() === false) {
                        return $this->gagalTrans($db, 'Pembatalan poin presensi gagal tersimpan');
                    }
                    $db->transCommit();
                    return [
                        'status' => true, 'message' => 'Presensi dihapus, poin otomatis dibatalkan',
                        'poin' => 0, 'skor' => $poinModel->updateCache($idSiswa), 'skipped' => false,
                    ];
                }
                $db->transCommit();
                return $this->hasil('Presensi tidak ditemukan', 0);
            }

            $idSiswa = (int) ($row->id_siswa ?? 0);
            if ($idSiswa <= 0) {
                // Transaksi sudah terbuka di atas. Keluar tanpa commit/rollback
                // menaikkan transaction depth dan membuat transRollback()
                // berikutnya membatalkan semua tulisan yang sudah dilaporkan
                // berhasil di baris-baris sebelumnya.
                $db->transRollback();
                return $this->hasil('Siswa tidak valid', 0);
            }

            // Kunci baris siswa sebelum membaca skor: ambang 0 dihitung dari
            // ledger, dan scan + cron bisa memproses siswa yang sama bersamaan.
            try {
                $db->query(
                    'SELECT id_siswa FROM ' . $db->prefixTable('master_siswa') . ' WHERE id_siswa = ? FOR UPDATE',
                    [$idSiswa]
                );
            } catch (\Throwable $e) {
                // SQLite tidak mendukung FOR UPDATE.
            }

            // Siswa non-AKTIF (LULUS/PINDAH/KELUAR) dikecualikan dari
            // perhitungan poin: buang entri otomatisnya lalu lewati.
            try {
                if ($db->tableExists('buku_induk')) {
                    $st = $db->table('buku_induk')->select('status')
                        ->where('id_siswa', $idSiswa)->get()->getRow();
                    if ($st && (string) ($st->status ?? '1') !== '1') {
                        if ($db->table('poin_siswa_history')->where('ref_id', $ref)->delete() === false) {
                            return $this->gagalTrans($db, 'Gagal membatalkan poin siswa nonaktif');
                        }
                        if ($db->transStatus() === false) {
                            return $this->gagalTrans($db, 'Pembatalan poin siswa nonaktif gagal tersimpan');
                        }
                        $skor = $poinModel->updateCache($idSiswa);
                        $db->transCommit();
                        return [
                            'status' => true, 'message' => 'Siswa tidak aktif — poin otomatis dibatalkan',
                            'poin' => 0, 'skor' => $skor, 'skipped' => true,
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }

            // 1) Tentukan dulu: ada pelanggaran, atau tidak?
            $langgar = $this->pelanggaranPresensi($row);

            if ($langgar === null) {
                // Tidak ada pelanggaran → buang entri lama kalau ada.
                if ($db->table('poin_siswa_history')->where('ref_id', $ref)->delete() === false) {
                    return $this->gagalTrans($db, 'Gagal menghapus poin yang tidak lagi berlaku');
                }
                if ($db->transStatus() === false) {
                    return $this->gagalTrans($db, 'Penghapusan poin gagal tersimpan');
                }
                $skor = $poinModel->updateCache($idSiswa);
                $db->transCommit();
                return [
                    'status' => true, 'message' => 'Tidak ada pengurangan poin',
                    'poin' => 0, 'skor' => $skor, 'skipped' => true,
                ];
            }

            // 2) Pelanggaran ada, tapi kategori aktifnya tidak ketemu
            //    (dinonaktifkan atau di-rename). JANGAN hapus entri lama:
            //    kalau dihapus tanpa pengganti, skor siswa naik diam-diam.
            $kat = $this->kategori($langgar['cari']);
            if ($kat === null) {
                $lama = $db->table('poin_siswa_history')->where('ref_id', $ref)->get()->getRow();
                if ($lama) {
                    $skor = $poinModel->updateCache($idSiswa);
                    log_message('warning', '[SiswaPoinService] Kategori "'.$langgar['cari'].'" tidak aktif/berubah nama. Poin lama untuk ref '.$ref.' dipertahankan ('.(int) $lama->poin.' poin).');
                    $db->transCommit();
                    return [
                        'status'  => true,
                        'message' => 'Kategori "' . ucfirst($langgar['cari']) . '" sedang nonaktif atau diganti nama — poin lama (' . (int) $lama->poin . ') dipertahankan, tidak ada perubahan',
                        'poin'    => 0,
                        'skor'    => $skor,
                        'skipped' => true,
                    ];
                }
                $skor = $poinModel->updateCache($idSiswa);
                $db->transCommit();
                return [
                    'status'  => true,
                    'message' => 'Pelanggaran terdeteksi, tapi kategori "' . ucfirst($langgar['cari']) . '" tidak aktif — poin tidak dipotong',
                    'poin'    => 0,
                    'skor'    => $skor,
                    'skipped' => true,
                ];
            }

            // 3) Kategori baru siap → buang entri lama lalu tulis yang baru.
            if ($db->table('poin_siswa_history')->where('ref_id', $ref)->delete() === false) {
                return $this->gagalTrans($db, 'Gagal memperbarui poin presensi');
            }

            $poin = -abs((int) $kat->bobot);
            $skorSekarang = $poinModel->getSkor($idSiswa);
            if ($skorSekarang === null) {
                // Tanpa skor terbaca, ambang 0 tidak bisa dijaga dan
                // updateCache akan menulis angka palsu.
                return $this->gagalTrans($db, 'Skor terkini gagal dibaca — poin tidak diubah');
            }
            if ($skorSekarang + $poin < 0) {
                $poin = -$skorSekarang; // mentok di 0, sama seperti pencatatan manual
            }
            if ($poin === 0) {
                $skor = $poinModel->updateCache($idSiswa);
                $db->transCommit();
                return [
                    'status' => true, 'message' => 'Poin sudah mentok di 0',
                    'poin' => 0, 'skor' => $skor, 'skipped' => true,
                ];
            }

            $tertulis = $db->table('poin_siswa_history')->insert([
                'id_siswa'     => $idSiswa,
                'id_kelas'     => isset($row->id_kelas) ? (int) $row->id_kelas : null,
                'poin'         => $poin,
                'kategori_id'  => (int) $kat->id,
                'tipe'         => 'PRESENSI',
                'keterangan'   => mb_substr($langgar['keterangan'], 0, 255),
                'ref_id'       => $ref,
                'created_by'   => null,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            // Entri lama sudah dihapus di atas: kalau sisipan ini gagal dan
            // tetap di-commit, pengurangan siswa hilang begitu saja.
            if ($tertulis === false || $db->transStatus() === false) {
                log_message('error', '[SiswaPoinService::recalc#' . $presensiId . '] insert ledger gagal: ' . json_encode($db->error()));
                return $this->gagalTrans($db, 'Poin otomatis gagal tersimpan — poin lama dipertahankan');
            }

            $skor = $poinModel->updateCache($idSiswa);
            $db->transCommit();
            return [
                'status'  => true,
                'message' => $langgar['keterangan'] . ' (' . $poin . ' poin)',
                'poin'    => $poin,
                'skor'    => $skor,
                'skipped' => false,
            ];
        } catch (\Throwable $e) {
            try { $db->transRollback(); } catch (\Throwable $e2) {}
            log_message('error', '[SiswaPoinService::recalc#' . $presensiId . '] ' . $e->getMessage());
            return $this->hasil('Gagal menghitung poin otomatis', 0);
        }
    }

    /**
     * Ambil id baris presensi milik seorang siswa pada satu tanggal, lalu
     * hitung ulang. Dipanggil dari koreksi jam / hapus per siswa.
     */
    public function recalcSiswaTanggal(int $idSiswa, string $tgl): array
    {
        if ($idSiswa <= 0 || trim($tgl) === '') {
            return $this->hasil('Parameter tidak valid', 0);
        }
        try {
            $db = \Config\Database::connect();
            $row = $db->table('presensi_siswa')
                ->select('id')
                ->where('id_siswa', $idSiswa)
                ->where('tgl', substr($tgl, 0, 10))
                ->orderBy('id', 'DESC')
                ->get()->getRow();
            if (!$row) {
                // Tidak ada baris: buang sisa pengurangan milik ref yang menunjuk id ini.
                $this->buangSemuaPresensiSiswa($idSiswa, $tgl);
                return $this->hasil('Tidak ada data presensi', 0);
            }
            return $this->recalc((int) $row->id);
        } catch (\Throwable $e) {
            log_message('error', '[SiswaPoinService::recalcSiswaTanggal] ' . $e->getMessage());
            return $this->hasil('Gagal menghitung poin', 0);
        }
    }

    /**
     * Hapus semua entri otomatis milik seorang siswa pada satu tanggal.
     * Dipakai kalau baris presensinya hilang.
     */
    public function buangSemuaPresensiSiswa(int $idSiswa, string $tgl): int
    {
        try {
            $db = \Config\Database::connect();
            $tgl = substr($tgl, 0, 10);
            $n = 0;

            // Cara 1: ref_id 'presensi-{id}' — hanya berlaku kalau baris
            // presensinya masih ada.
            $ids = $db->table('presensi_siswa')->select('id')
                ->where('id_siswa', $idSiswa)->where('tgl', $tgl)->get()->getResult();
            foreach ($ids as $r) {
                if ($db->table('poin_siswa_history')
                    ->where('ref_id', 'presensi-' . (int) $r->id)->delete() !== false) {
                    $n += $db->affectedRows();
                }
            }

            // Cara 2: baris presensinya SUDAH hilang, jadi ref_id di atas
            // mustahil ditemukan dan entri otomatisnya akan menggantung
            // sebagai poin yatim. Dalam kasus ini tapping ke tanda tanggal di
            // keterangan. Semua entri PRESENSI selalu berakhiran "(YYYY-MM-DD)",
            // jadi pencocokannya eksak — bukan sekadar like tanggal yang bisa
            // menimpa tanggal lain.
            if ($db->table('poin_siswa_history')
                ->where('id_siswa', $idSiswa)
                ->where('tipe', 'PRESENSI')
                ->like('keterangan', '(' . $tgl . ')', false)
                ->delete() !== false) {
                $n += $db->affectedRows();
            }

            if ($n > 0) {
                $this->poin()->updateCache($idSiswa);
            }
            return $n;
        } catch (\Throwable $e) {
            log_message('error', '[SiswaPoinService::buangSemuaPresensiSiswa] ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Hitung ulang seluruh baris presensi siswa satu bulan. Hanya baris
     * presensi_siswa yang ADA yang dihitung ulang: siswa yang bolos sebulan
     * penuh tanpa satu pun baris scan TIDAK tertangkap di sini (tidak ada
     * enumerasi master siswa). Untuk itu pakai Admin\PoinSiswa::syncPresensi
     * per kelas atau pastikan piket mencatat ALPA harian.
     *
     * @return array{checked:int, changed:int, message:string}
     */
    public function recalcBulan(int $bulan, int $tahun): array
    {
        $checked = 0;
        $changed = 0;
        try {
            $db = \Config\Database::connect();
            $rows = $db->table('presensi_siswa')
                ->select('id')
                ->where('MONTH(tgl)', $bulan)->where('YEAR(tgl)', $tahun)
                ->get()->getResult();
            foreach ($rows as $r) {
                $checked++;
                $before = $db->table('poin_siswa_history')
                    ->where('ref_id', 'presensi-' . (int) $r->id)->get()->getRow();
                $res = $this->recalc((int) $r->id);
                $after = $db->table('poin_siswa_history')
                    ->where('ref_id', 'presensi-' . (int) $r->id)->get()->getRow();
                if (($before === null) !== ($after === null)
                    || ($before && $after && (int) $before->poin !== (int) $after->poin)) {
                    $changed++;
                }
            }
            return [
                'checked' => $checked,
                'changed' => $changed,
                'message' => "Sinkron poin siswa {$bulan}/{$tahun}: {$checked} baris diperiksa, {$changed} berubah",
            ];
        } catch (\Throwable $e) {
            log_message('error', '[SiswaPoinService::recalcBulan] ' . $e->getMessage());
            return ['checked' => 0, 'changed' => 0, 'message' => 'Gagal sinkron — periksa log aplikasi'];
        }
    }

    private function hasil(string $msg, int $poin): array
    {
        return ['status' => false, 'message' => $msg, 'poin' => $poin, 'skor' => null, 'skipped' => true];
    }

    /**
     * Tutup transaksi yang gagal dengan jujur.
     *
     * Di MySQLi transCommit() tetap melakukan COMMIT walau sebuah query gagal,
     * jadi setiap jalur gagal harus membatalkan sendiri — kalau tidak,
     * penghapusan yang sudah terjadi tersimpan tanpa pasangannya.
     * Kalau recalc dipanggil dari transaksi pemanggil (mis. simpan presensi),
     * rollback hanya menaikkan kedalaman: status transStatus yang false membuat
     * pemilik transaksi ikut tahu.
     */
    private function gagalTrans(object $db, string $pesan): array
    {
        try {
            $db->transRollback();
        } catch (\Throwable $e) {
        }
        log_message('error', '[SiswaPoinService] ' . $pesan);
        return $this->hasil($pesan, 0);
    }

    // ── Pintasan untuk dipanggil dari controller ───────────────────

    /**
     * Hitung ulang poin dari sebuah baris presensi. Tidak pernah melempar
     * error ke pemanggil — kegagalan poin tidak boleh membatalkan absensi.
     */
    public static function sinkron(?int $presensiId = null, ?int $idSiswa = null, ?string $tgl = null): void
    {
        try {
            $svc = new self();
            if ($presensiId !== null && $presensiId > 0) {
                $svc->recalc($presensiId);
            } elseif ($idSiswa !== null && $idSiswa > 0 && $tgl !== null) {
                $svc->recalcSiswaTanggal($idSiswa, $tgl);
            }
        } catch (\Throwable $e) {
            log_message('error', '[SiswaPoinService::sinkron] ' . $e->getMessage());
        }
    }

    /**
     * Setelah baris presensi dihapus, buang pengurangan point-nya.
     */
    public static function batalkan(int $presensiId): void
    {
        try {
            (new self())->recalc($presensiId);
        } catch (\Throwable $e) {
            log_message('error', '[SiswaPoinService::batalkan] ' . $e->getMessage());
        }
    }
}
