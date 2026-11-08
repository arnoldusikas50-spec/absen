<?php

namespace App\Services;

use App\Libraries\TelegramLink;

/**
 * GuruTerawalService — mengumumkan satu guru yang absen paling awal pada
 * suatu hari, langsung ketika absensinya tercatat (bukan lewat cron terpisah).
 *
 * Mengapa tidak sederhana "ambil yang pertama masuk":
 * absensi punya 6 jalur tulis (absen guru, QR guru, QR scanner, absen manual
 * admin, batch manual, koreksi yang disetujui) dan tidak ada satu pun titik
 * temu tunggal — tidak ada CI4 Event, model callback, atau Hooks di proyek ini.
 * Karena itu setiap jalur memanggil `cekDanKirim()` dan method ini yang
 * memutuskan.
 *
 * Idempotensi dijamin tabel `absensi_pengumuman_harian` yang punya
 * UNIQUE(tgl): fascismKlarna request yang berjalan bersamaan hanya satu yang
 * berhasil INSERT, sehingga pengumuman tidak pernah terkirim dua kali.
 *
 * Konsekuensi yang disengaja: bila sebuah koreksi admin mengubah jam masuk
 * sehingga ada orang lain yang jadi lebih awal, pengumuman hari itu TIDAK
 * diulang.
 */
class GuruTerawalService
{
    private ?NotificationService $notification;

    public function __construct(?NotificationService $notification = null)
    {
        $this->notification = $notification;
    }

    /**
     * Pemicu pengumuman dari jalur check-in mana pun.
 *
 * Sengaja static + tidak melempar exception, supaya setiap call site cukup
 * satu baris dan kegagalan notifikasi tidak pernah menggagalkan absensi guru.
 *
 * @param $db      Koneksi database yang sudah dipakai pemanggil
 * @param string   $tanggal Format Y-m-d
 */
public static function afterCheckIn($db, string $tanggal, int $userId = 0): void
    {
        try {
            (new self())->cekDanKirim($tanggal, $userId);
        } catch (\Throwable $e) {
            log_message('error', '[GuruTerawal] afterCheckIn gagal: ' . $e->getMessage());
        }
    }

    /**
     * Dipanggil setiap kali ada absensi masuk tersimpan.
     *
     * Tidak melempar exception: notifikasi tidak boleh membuat absensi guru
     * gagal. Semua masalah dicatat di log dan dikembalikan sebagai array status.
     *
     * @param  string $tanggal Format Y-m-d
     * @return array{terkirim:bool,alasan:string,peminat:?array}
     */
    public function cekDanKirim(string $tanggal, int $triggerUserId = 0): array
    {
        $tanggal = $this->normalizeDate($tanggal);
        $db      = \Config\Database::connect();

        try {
            $peminat = $this->guruTerawal($db, $tanggal);
        } catch (\Throwable $e) {
            log_message('error', '[GuruTerawal] gagal menghitung guru terawal: ' . $e->getMessage());

            return ['terkirim' => false, 'alasan' => 'Gagal menghitung — periksa log aplikasi.', 'peminat' => null];
        }

        if ($peminat === null) {
            return ['terkirim' => false, 'alasan' => 'Belum ada yang absen pada ' . $tanggal, 'peminat' => null];
        }

        // Pre-check: hari ini sudah diklaim? Framework (MySQLi Connection)
        // me-log ERROR + trace untuk SETIAP query gagal — termasuk
        // duplicate-key yang tertangani di bawah. Tanpa pre-check, tiap
        // check-in setelah pengumuman pertama menulis satu ERROR menakutkan
        // ke log (belasan tiap rush pagi) padahal perilakunya normal.
        // UNIQUE(tgl) tetap jadi penolak akhir untuk balapan detik yang sama.
        try {
            $sudahAda = $db->table('absensi_pengumuman_harian')
                ->where('tgl', $tanggal)
                ->countAllResults() > 0;
        } catch (\Throwable $e) {
            $sudahAda = false;
        }
        if ($sudahAda) {
            return ['terkirim' => false, 'alasan' => 'Sudah diumumkan sebelumnya', 'peminat' => $peminat];
        }

        // Klaim hari ini. Duplicate-key berarti ada jalur lain yang lebih dulu
        // mengklaim — pengumuman hari ini sudah ada, jadi berhenti di sini.
        try {
            $db->table('absensi_pengumuman_harian')->insert([
                'tgl'             => $tanggal,
                'user_id'         => (int) $peminat->user_id,
                'nama_guru'       => (string) $peminat->nama_guru,
                'jam_masuk'       => $peminat->jam_masuk,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            if ($this->isDuplicateKeyError($e)) {
                return ['terkirim' => false, 'alasan' => 'Sudah diumumkan sebelumnya', 'peminat' => $peminat];
            }
            log_message('error', '[GuruTerawal] gagal mengklaim pengumuman: ' . $e->getMessage());

            return ['terkirim' => false, 'alasan' => 'Gagal mengklaim — periksa log aplikasi.', 'peminat' => $peminat];
        }

        // Pengiriman sengaja DI LUAR try/catch pertama: kalau Telegram gagal,
        // klaim tetap tercatat agar tidak mencoba mengirim ulang tiap absensi.
        $notif = $this->notification();
        $dmOk  = false;
        $grpOk = false;
        $catatan = [];

        try {
            $res = $notif->notifyGuruTerawal($peminat, $this->chatIdGuru((int) $peminat->user_id));
            $grpOk = ($res['grup'] ?? false) === true;
            $dmOk  = ($res['dm'] ?? false) === true;

            if (! $grpOk) {
                $catatan[] = 'grup gagal: ' . ($res['alasan'] ?? 'tanpa keterangan');
            }
            if (! $dmOk) {
                $catatan[] = 'DM gagal: ' . ($res['alasan_dm'] ?? 'guru belum menautkan WhatsApp/Telegram');
            } elseif (($res['kanal_dm'] ?? '') === 'grup') {
                // DM "berhasil" lewat fallback ke grup. dm_terkirim tetap
                // dicatat 1 — pesan memang sampai ke semua orang — tapi
                // catatannya menyebutkan supaya tidak menyesatkan saat
                // ditinjau di Status Notifikasi.
                $catatan[] = 'DM dikirim lewat grup (guru belum menautkan kanal personal)';
            }
        } catch (\Throwable $e) {
            $catatan[] = 'error: periksa log aplikasi';
            log_message('error', '[GuruTerawal] gagal mengirim pengumuman: ' . $e->getMessage());
        }

        $db->table('absensi_pengumuman_harian')
            ->where('tgl', $tanggal)
            ->update([
                'dm_terkirim'   => $dmOk ? 1 : 0,
                'grup_terkirim' => $grpOk ? 1 : 0,
                'catatan'       => $catatan === [] ? null : mb_substr(implode('; ', $catatan), 0, 255),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

        log_message('info', sprintf(
            '[GuruTerawal] %s: %s (masuk %s) grup=%s dm=%s',
            $tanggal,
            $peminat->nama_guru,
            substr((string) $peminat->jam_masuk, 11, 5),
            $grpOk ? 'ok' : 'gagal',
            $dmOk ? 'ok' : 'gagal'
        ));

        return [
            'terkirim' => $grpOk,
            // Dicatat apa pun yang bermasalah, tidak hanya saat grup gagal —
            // kegagalan DM tetap perlu terlihat meski grupnya berhasil.
            'alasan'  => $catatan === [] ? '' : implode('; ', $catatan),
            'dm_kirim' => $dmOk,
            'peminat' => $peminat,
        ];
    }

    /**
     * Guru dengan jam_masuk paling awal pada tanggal tersebut.
     *
     * Hanya baris yang benar-benar punya jam_masuk (bukan izin/alpha/dinas).
     * Absen yang diisi manual oleh admin (`device_info = 'Manual by Admin'`)
     * dikecualikan — pengumuman "terawal" hanya bermakna untuk kehadiran nyata
     * yang tercatat dari perangkat/scan guru sendiri.
     * Diurutkan jam_masuk ASC lalu id ASC sebagai pemutus saat jam sama persis.
     */
    public function guruTerawal($db, string $tanggal): ?object
    {
        $builder = $db->table('absensi_harian a')
            ->select('a.user_id, a.jam_masuk, a.id, g.nama_guru, g.jenis_kelamin')
            ->join('master_guru g', 'g.id_user = a.user_id', 'left')
            ->where('a.tgl', $tanggal)
            ->where('a.jam_masuk IS NOT NULL')
            ->where('a.status_masuk !=', 'ALPHA');
        if ($db->fieldExists('device_info', 'absensi_harian')) {
            $builder->groupStart()
                ->where('a.device_info !=', 'Manual by Admin')
                ->orWhere('a.device_info IS NULL')
            ->groupEnd();
        }
        $builder->orderBy('a.jam_masuk', 'ASC')
            ->orderBy('a.id', 'ASC')
            ->limit(1);

        $row = $builder->get()->getRow();

        return $row !== null ? $this->isiNama($db, $row) : null;
    }

    /**
     * Pastikan nama & sapaan terisi. Guru yang belum punya baris master_guru
     * (mis. akun admin) tetap bisa dapat nama dari tabel users.
     */
    private function isiNama($db, object $row): object
    {
        $row->nama_guru = trim((string) ($row->nama_guru ?? ''));
        $row->sapaan    = 'Bapak atau Ibu';

        if ($row->nama_guru === '') {
            $u = $db->table('users')->select('first_name, last_name')->where('id', $row->user_id)->get()->getRow();
            if ($u) {
                $row->nama_guru = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            }
        }
        if ($row->nama_guru === '') {
            $row->nama_guru = 'Guru';
        }

        $jk = strtoupper(trim((string) ($row->jenis_kelamin ?? '')));
        if ($jk === 'L' || strpos($jk, 'LAKI') !== false) {
            $row->sapaan = 'Bapak';
        } elseif ($jk === 'P' || strpos($jk, 'PEREMPUAN') !== false) {
            $row->sapaan = 'Ibu';
        }

        return $row;
    }

    /**
     * Chat id personal guru, atau string kosong bila belum menautkan Telegram.
     */
    public function chatIdGuru(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }
        try {
            $db = \Config\Database::connect();
            $id = 0;
            if ($db->tableExists('master_guru')) {
                $g = $db->table('master_guru')->select('id_guru')->where('id_user', $userId)->get()->getRow();
                if ($g) {
                    $id = (int) ($g->id_guru ?? 0);
                }
            }

            return TelegramLink::chatIdForGuru($id, $userId);
        } catch (\Throwable $e) {
            log_message('error', '[GuruTerawal] gagal mencari chat id guru: ' . $e->getMessage());

            return '';
        }
    }

    private function notification(): NotificationService
    {
        return $this->notification ??= new NotificationService();
    }

    private function normalizeDate(string $tanggal): string
    {
        $tanggal = trim($tanggal);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tanggal, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return date('Y-m-d');
    }

    /**
     * Deteksi pelanggaran UNIQUE (kunci unik pada kolom tgl).
     *
     * Tidak boleh hanya mengandalkan kata "duplicate": driver berbeda punya
     * kosakata berbeda dan sering melempar kode 0.
     *   MySQLi  : errno 1062, "Duplicate entry ... for key 'tgl'"
     *   PDO/MariaDB: SQLSTATE 23000
     *   SQLite : kode 0, "UNIQUE constraint failed: ...tgl"
     * Wayang dari PDO dibungkus exception lain, jadi penyebab sebenarnya
     * ikut ditelusuri.
     */
    private function isDuplicateKeyError(\Throwable $e): bool
    {
        for ($cur = $e; $cur !== null; $cur = $cur->getPrevious()) {
            $code = $cur->getCode();
            if ($code === 23000 || $code === '23000' || (int) $code === 1062) {
                return true;
            }

            $msg = strtolower($cur->getMessage());
            foreach (['duplicate', 'unique constraint', 'uq_', 'sqlstate[23'] as $needle) {
                if (str_contains($msg, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }
}
