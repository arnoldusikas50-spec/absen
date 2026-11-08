<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\JurnalGuruModel;
use App\Models\PiketJadwalModel;

/**
 * Gerbang "wajib isi jurnal mengajar sebelum absen pulang".
 *
 * Satu sumber kebenaran untuk aturan yang dulu hanya hidup di aplikasi guru,
 * supaya scanner kiosk di gerbang tidak lagi bisa dilewati:
 * - Guru\Attendance::checkOut()                    (absen wajah)
 * - Guru\Attendance::qrCheckout()                  (QR dari menu guru)
 * - Guru\PresensiSiswaQr::processGuruCheckOut()    (scan kartu guru di kiosk)
 *
 * Sikap: blokir penuh — jam_pulang tidak ditulis sampai jurnal tiap sesi yang
 * sudah dimulai hari ini diisi. Pengecualian status dinas ditangani pemanggil
 * lewat isStatusDinas() (hanya mengikuti kolom status_masuk), supaya jalur
 * wajah, QR, dan kiosk tidak bisa berbeda kesimpulan.
 *
 * Mode ketat (opsional, absensi_setting.wajib_jurnal_tervalidasi_sebelum_pulang):
 * jurnal baru dihitung bila sudah divalidasi guru piket. Yang menahan lalu
 * dibedakan antara "belum diisi" dan "menunggu validasi" supaya tautan "Isi"
 * di aplikasi guru tidak menyesatkan.
 *
 * Fail-open: setiap kegagalan infrastruktur (tabel jurnal belum ada, jadwal
 * tidak terbaca, TP/SMT aktif belum diset) menghasilkan [] — guru tidak boleh
 * terkunci di sekolah karena error teknis.
 */
final class WajibJurnalPulangService
{
    /** Jumlah maksimum sesi yang disebut di dalam pesan. */
    public const MAX_LABEL = 4;

    /** Jumlah maksimum nama petugas validasi yang disebut di dalam pesan. */
    public const MAX_PETUGAS = 3;

    /**
     * Gerbang lengkap: sesi penahan + pesan untuk aplikasi guru + pesan pendek
     * untuk layar scanner.
     *
     * @return array{sesi:list<object>,message:string,message_singkat:string,data:array}|null
     *         null berarti absen pulang boleh dilanjutkan
     */
    public static function gate(
        int $idGuru,
        string $tanggal,
        $setting = null,
        $db = null,
        ?string $waktu = null,
        string $namaGuru = ''
    ): ?array {
        $setting ??= AttendancePolicy::setting();
        $sesi = self::sesiYangMenahan($idGuru, $tanggal, $setting, $db, $waktu);
        if (empty($sesi)) {
            return null;
        }

        $petugas = self::butuhValidasi($setting) ? self::petugasValidasi($tanggal, $db) : [];
        $lengkap = self::pesanLengkap($sesi, $petugas);

        return [
            'sesi'            => $sesi,
            'message'         => $lengkap['message'],
            'message_singkat' => self::pesanKiosk($sesi, $namaGuru),
            'data'            => $lengkap['data'],
        ];
    }

    /**
     * Status check-in yang menandakan guru sedang dinas luar. Dibandingkan
     * tanpa peduli huruf besar/kecil karena agregasi SQL (LIKE 'DINAS%') juga
     * tidak peduli — kalau PHP-nya sensitif kasus, satu jalur bisa mengecualikan
     * guru dinas sementara jalur lain memblokirnya.
     */
    public static function isStatusDinas($status): bool
    {
        return strpos(strtoupper(trim((string) ($status ?? ''))), 'DINAS') === 0;
    }

    /**
     * Sakelar mode ketat aktif. Sekali pun aktif, syaratnya tetap mengikuti
     * gerbang wajib jurnal — tanpa syarat itu tidak ada yang divalidasi.
     */
    public static function butuhValidasi($setting): bool
    {
        return (bool) $setting
            && (int) ($setting->wajib_jurnal_sebelum_pulang ?? 0) === 1
            && (int) ($setting->wajib_jurnal_tervalidasi_sebelum_pulang ?? 0) === 1;
    }

    /**
     * Sesi jadwal hari ini yang menahan absen pulang.
     *
     * Kosong berarti gerbang nonaktif, semua sesi sudah dijurnal, hari libur,
     * atau validasi gagal karena infrastruktur (fail-open — jangan kunci pulang
     * hanya karena tabel/jadwal tidak terbaca).
     *
     * Pada mode ketat, sesi berstatus 'menunggu_validasi' ikut returned.
     *
     * @return array<int,object>
     */
    public static function sesiYangMenahan(
        int $idGuru,
        string $tanggal,
        $setting = null,
        $db = null,
        ?string $waktu = null,
        ?int $hari = null
    ): array {
        $setting ??= AttendancePolicy::setting();
        if (!$setting || (int) ($setting->wajib_jurnal_sebelum_pulang ?? 0) !== 1 || $idGuru <= 0) {
            return [];
        }

        // Hari libur: tidak ada KBM yang harus dijurnal.
        if (AttendancePolicy::isLibur($tanggal)) {
            return [];
        }

        $batas = substr((string) ($waktu ?? date('H:i:s')), 0, 5);
        $hari  = $hari ?? AttendancePolicy::isoHari($tanggal);
        $db    = $db instanceof BaseConnection ? $db : \Config\Database::connect();

        try {
            $tpRow = $db->table('master_tp')->where('active', 1)->get()->getRow();
            $smtRow = $db->table('master_smt')->where('active', 1)->get()->getRow();
            if (!$tpRow || !$smtRow) {
                return [];
            }

            return (new JurnalGuruModel())->getSesiBelumDijurnal(
                $idGuru,
                (int) $tpRow->id_tp,
                (int) $smtRow->id_smt,
                (int) $hari,
                $tanggal,
                $batas,
                self::butuhValidasi($setting)
            );
        } catch (\Throwable $e) {
            log_message('error', '[wajib-jurnal] validasi absen pulang gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guru yang bertugas piket hari itu — satu-satunya yang bisa memvalidasi
     * jurnal. Kosong berarti tidak ada penanggung jawab terjadwal.
     *
     * id_user ikut dibawa untuk notifikasi antrean validasi.
     *
     * @return list<array{nama:string,nip:string,id_guru:int,id_user:int}>
     */
    public static function petugasValidasi(string $tanggal, $db = null): array
    {
        try {
            $db = $db instanceof BaseConnection ? $db : \Config\Database::connect();
            $tpRow  = $db->table('master_tp')->where('active', 1)->get()->getRow();
            $smtRow = $db->table('master_smt')->where('active', 1)->get()->getRow();
            if (!$tpRow || !$smtRow) {
                return [];
            }

            $rows = (new PiketJadwalModel())->getPiketHariIni(
                AttendancePolicy::namaHariId($tanggal),
                (int) $tpRow->id_tp,
                (int) $smtRow->id_smt
            );

            $out = [];
            foreach ($rows as $r) {
                $nama = trim((string) ($r->nama_guru ?? ''));
                $idGuru = (int) ($r->id_guru ?? 0);
                if ($nama === '' || $idGuru <= 0 || isset($out[$idGuru])) {
                    continue;
                }
                $out[$idGuru] = [
                    'nama'    => $nama,
                    'nip'     => trim((string) ($r->nip ?? '')),
                    'id_guru' => $idGuru,
                    'id_user' => (int) ($r->id_user ?? 0),
                ];
            }

            return array_values($out);
        } catch (\Throwable $e) {
            log_message('error', '[wajib-jurnal] gagal memuat jadwal piket: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Akun users milik guru piket terjadwal pada tanggal ini.
     *
     * @return list<int>
     */
    public static function akunPetugasValidasi(string $tanggal, $db = null): array
    {
        $ids = [];
        foreach (self::petugasValidasi($tanggal, $db) as $p) {
            $idUser = (int) ($p['id_user'] ?? 0);
            if ($idUser > 0) {
                $ids[$idUser] = true;
            }
        }

        return array_keys($ids);
    }

    /**
     * Pesan + payload sesi yang menghalangi absen pulang.
     *
     * Teks dan kunci 'sesi_belum_dijurnal' dipakai JS absen_mobile (tombol "Buka
     * Jurnal Mengajar" per sesi) — jangan diubah bentuknya. Sesi yang jurnalnya
     * sudah ada tapi belum disetujui masuk ke 'sesi_menunggu_validasi' agar
     * tautan "Isi" tidak ditawarkan untuk sesi yang sudah terisi.
     *
     * @param array<int,object>        $sesiKurang
     * @param list<array{nama:string}> $petugas
     * @return array{message:string,data:array}
     */
    public static function pesanLengkap(array $sesiKurang, array $petugas = []): array
    {
        $belum    = [];
        $menunggu = [];
        foreach ($sesiKurang as $sesi) {
            if (!empty($sesi->menunggu_validasi)) {
                $menunggu[] = $sesi;
            } else {
                $belum[] = $sesi;
            }
        }

        $payload = [
            'sesi_belum_dijurnal' => self::payloadSesi($belum),
        ];

        if (empty($menunggu)) {
            return [
                'message' => 'Absen pulang belum bisa dilakukan. Lengkapi jurnal mengajar sesi yang sudah dimulai hari ini untuk: '
                    . self::daftarLabel($belum) . '. Buka menu Jurnal Mengajar, lalu coba absen pulang lagi.',
                'data' => $payload,
            ];
        }

        $payload['sesi_menunggu_validasi'] = self::payloadSesi($menunggu);
        $payload['butuh_validasi']   = true;
        $payload['petugas_validasi'] = $petugas;

        $grup = [];
        if (!empty($belum)) {
            $grup[] = 'belum diisi untuk: ' . self::daftarLabel($belum);
        }
        $ditolak = array_filter($menunggu, static fn($s) => strtoupper((string) ($s->validasi_status ?? '')) === JurnalGuruModel::VALIDASI_REJECTED);
        if (!empty($ditolak)) {
            $grup[] = 'ditolak dan perlu diperbaiki untuk: ' . self::daftarLabel($ditolak);
        }
        $menungguSah = array_filter($menunggu, static fn($s) => strtoupper((string) ($s->validasi_status ?? '')) !== JurnalGuruModel::VALIDASI_REJECTED);
        if (!empty($menungguSah)) {
            $grup[] = 'masih menunggu validasi untuk: ' . self::daftarLabel($menungguSah);
        }

        $pesan = 'Absen pulang belum bisa dilakukan. Jurnal mengajar hari ini '
            . implode('; jurnal ', $grup)
            . '. ' . self::frasePetugas($petugas, !empty($ditolak))
            . ' Setelah seluruh jurnal berstatus disetujui, absen pulang dapat dilanjutkan.';

        return [
            'message' => $pesan,
            'data' => $payload,
        ];
    }

    /**
     * Versi satu kalimat untuk banner dan suara scanner. Nama petugas sengaja
     * tidak dibacakan — daftar lengkapnya sudah tampil di banner lewat
     * $lengkap['message'], dan TTS tidak boleh kepanjangan.
     *
     * Jurnal yang ditolak disebut terpisah: menyuruh guru "menunggu validasi"
     * untuk jurnal yang justru harus diperbaiki lebih dulu hanya membuatnya
     * berdiri di gerbang tanpa tahu apa yang harus dilakukan.
     *
     * @param array<int,object> $sesiKurang
     */
    public static function pesanKiosk(array $sesiKurang, string $namaGuru = ''): string
    {
        $si       = $namaGuru !== '' ? $namaGuru : 'Anda';
        $belum    = 0;
        $menunggu = 0;
        $ditolak  = 0;
        foreach ($sesiKurang as $sesi) {
            if (!empty($sesi->menunggu_validasi)) {
                if (strtoupper(trim((string) ($sesi->validasi_status ?? ''))) === JurnalGuruModel::VALIDASI_REJECTED) {
                    $ditolak++;
                } else {
                    $menunggu++;
                }
            } else {
                $belum++;
            }
        }

        if ($ditolak > 0) {
            $bagian = [];
            if ($belum > 0) {
                $bagian[] = "{$belum} sesi jurnal belum diisi";
            }
            $bagian[] = "{$ditolak} sesi perlu diperbaiki (ditolak piket)";
            if ($menunggu > 0) {
                $bagian[] = "{$menunggu} sesi menunggu validasi piket";
            }

            return $si . ' belum bisa absen pulang: ' . self::hubunganKalimat($bagian) . '.';
        }

        if ($menunggu === 0) {
            return $si . " belum bisa absen pulang: jurnal mengajar {$belum} sesi hari ini belum diisi.";
        }

        if ($belum === 0) {
            return $si . " belum bisa absen pulang: jurnal {$menunggu} sesi hari ini menunggu validasi piket.";
        }

        return $si . " belum bisa absen pulang: jurnal {$belum} sesi belum diisi dan {$menunggu} sesi menunggu validasi piket.";
    }

    /** "{Nama} · {Kelas} · {Mapel} (07:00–07:45)" untuk beberapa sesi. */
    private static function daftarLabel(array $sesi): string
    {
        $labels = [];
        foreach (array_slice($sesi, 0, self::MAX_LABEL) as $s) {
            $labels[] = self::labelSesi($s);
        }

        $text = implode('; ', $labels);
        $sisa = count($sesi) - count($labels);
        if ($sisa > 0) {
            $text .= '; dan ' . $sisa . ' sesi lainnya';
        }

        return $text;
    }

    /** "A", "A dan B", "A, B dan C". */
    private static function hubunganKalimat(array $bagian): string
    {
        if (count($bagian) === 1) {
            return $bagian[0];
        }

        $terakhir = array_pop($bagian);

        return implode(', ', $bagian) . ' dan ' . $terakhir;
    }

    /** Arahkan guru ke penanggung jawab validasi. */
    private static function frasePetugas(array $petugas, bool $adaDitolak = false): string
    {
        $nama = self::namaPetugas($petugas);
        $awal = $adaDitolak ? 'Perbaiki jurnal yang ditolak, lalu minta validasi' : 'Minta validasi jurnal';

        return $nama !== ''
            ? $awal . ' kepada guru piket hari ini: ' . $nama . '.'
            : 'Belum ada guru piket terjadwal hari ini sebagai penanggung jawab validasi — hubungi admin presensi.';
    }

    /**
     * Nama guru sering mengandung koma ("NAMA, S.Pd"), jadi dipisah tanda garis
     * miring ganda agar tidak disalahartikan sebagai awal nama baru.
     *
     * @param list<array{nama:string}> $petugas
     */
    private static function namaPetugas(array $petugas): string
    {
        $nama = array_map(static fn($p) => trim((string) ($p['nama'] ?? '')), $petugas);
        $nama = array_values(array_filter($nama, static fn($n) => $n !== ''));
        if (empty($nama)) {
            return '';
        }

        $tampil = array_slice($nama, 0, self::MAX_PETUGAS);
        $sisa   = count($nama) - count($tampil);
        $text   = implode(' / ', $tampil);

        return $sisa > 0 ? $text . ' dan ' . $sisa . ' guru piket lainnya' : $text;
    }

    /** @param array<int,object> $sesi */
    private static function payloadSesi(array $sesi): array
    {
        return array_map(static function ($s) {
            return [
                'id_jadwal' => (int) ($s->id_jadwal ?? 0),
                'id_kelas' => (int) ($s->id_kelas ?? 0),
                'id_mapel' => (int) ($s->id_mapel ?? 0),
                'jam_ke' => (string) ($s->jam_ke ?? ''),
                'dari' => substr((string) ($s->dari ?? ''), 0, 5),
                'sampai' => substr((string) ($s->sampai ?? ''), 0, 5),
                'nama_kelas' => (string) ($s->nama_kelas ?? ''),
                'nama_mapel' => (string) ($s->nama_mapel ?? ''),
                'id_jurnal' => (int) ($s->id_jurnal ?? 0),
                'validasi_status' => (string) ($s->validasi_status ?? ''),
            ];
        }, $sesi);
    }

    /** "Jam 3 · VII-A · Matematika (09:00–10:00)" */
    public static function labelSesi(object $sesi): string
    {
        $jamKe  = trim((string) ($sesi->jam_ke ?? ''));
        $rentang = substr((string) ($sesi->dari ?? ''), 0, 5);
        if (!empty($sesi->sampai)) {
            $rentang = trim($rentang . '–' . substr((string) $sesi->sampai, 0, 5), '–');
        }

        $label = ($jamKe !== '' ? 'Jam ' . $jamKe . ' · ' : '')
            . trim((string) ($sesi->nama_kelas ?? 'Kelas')) . ' · '
            . trim((string) ($sesi->nama_mapel ?? 'Mapel'));
        if ($rentang !== '') {
            $label .= ' (' . $rentang . ')';
        }

        return $label;
    }
}
