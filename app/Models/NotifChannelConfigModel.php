<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * NotifChannelConfigModel — Konfigurasi channel notifikasi per jenis.
 *
 * Setiap baris merepresentasikan satu jenis notifikasi beserta empat
 * switch channel-nya:
 *   - tg_grup     : Telegram → grup/channel sekolah
 *   - wa_grup     : WhatsApp → nomor/grup broadcast
 *   - tg_personal : Telegram → chat personal guru
 *   - wa_personal : WhatsApp → nomor personal guru (wa_verified = 1)
 *
 * Akses utama lewat getConfig() yang selalu mengembalikan semua jenis
 * (auto-seed bila tabel kosong / jenis baru belum ada baris-nya).
 */
class NotifChannelConfigModel extends Model
{
    protected $table      = 'notif_channel_config';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'jenis',
        'tg_grup',
        'wa_grup',
        'tg_personal',
        'wa_personal',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // ── Konstanta ─────────────────────────────────────────────────────

    /** Channel yang dikenal (kolom di tabel). */
    public const CHANNELS = ['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'];

    /**
     * Label tampilan untuk setiap channel.
     */
    public const CHANNEL_LABELS = [
        'tg_grup'     => 'TG Grup',
        'wa_grup'     => 'WA Grup',
        'tg_personal' => 'TG Personal',
        'wa_personal' => 'WA Personal',
    ];

    /**
     * Semua jenis notifikasi yang dikenal beserta metadatanya.
     *
     * 'personal' => true  : jenis ini bisa dikirim ke personal guru
     * 'label'             : nama ramah untuk UI
     * 'pref_key'          : kunci di user_notifikasi.prefs ('' bila tidak ada)
     */
    public const JENIS_LIST = [
        'keterlambatan' => ['label' => 'Keterlambatan Check-in',        'personal' => true,  'pref_key' => 'kehadiran'],
        'denda'         => ['label' => 'Denda Baru',                    'personal' => true,  'pref_key' => 'denda'],
        'izin'          => ['label' => 'Izin / Sakit / Cuti (semua)',   'personal' => true,  'pref_key' => 'izin'],
        'koreksi'       => ['label' => 'Koreksi Jam Absensi (semua)',   'personal' => true,  'pref_key' => 'koreksi'],
        'alpha'         => ['label' => 'Alpha / Tidak Hadir (real-time; cron harian saat ini hanya mengirim Surat Teguran)', 'personal' => true,  'pref_key' => 'alpha'],
        'teguran'       => ['label' => 'Surat Teguran',                 'personal' => true,  'pref_key' => 'teguran'],
        'panggilan'     => ['label' => 'Surat Panggilan Orang Tua',     'personal' => true,  'pref_key' => 'panggilan'],
        'dinas'         => ['label' => 'Dinas (semua)',                  'personal' => true,  'pref_key' => 'izin'],
        'guru_terawal'  => ['label' => 'Guru Paling Cepat Hadir',       'personal' => true,  'pref_key' => 'guru_terawal'],
        // Grup-only
        'rekap_harian'    => ['label' => 'Rekap Harian (Cron)',         'personal' => false, 'pref_key' => ''],
        // Mingguan dan bulanan pisah baris: cron Sabtu 18:05 dan tgl-28
        // 18:10 adalah pengirim berbeda — satu switch gabungan membuat
        // admin tidak bisa mematikan rekap mingguan tanpa ikut mematikan
        // yang bulanan. Baris lama 'rekap_denda' dipensiunkan via migrasi
        // (nilainya disalin ke kedua baris baru).
        'rekap_denda_mingguan' => ['label' => 'Rekap Denda Mingguan (Cron)', 'personal' => false, 'pref_key' => ''],
        'rekap_denda_bulanan'  => ['label' => 'Rekap Denda Bulanan (PDF tgl 1, tombol kirim)', 'personal' => false, 'pref_key' => ''],
        // Dikirim manual dari tombol "Kirim ke WA & TG" di /admin/presensi/rekap.
        // Wajib ada di daftar ini: tanpa barisnya, isEnabled() fail-open dan
        // admin tidak pernah bisa mematikan kanalnya di Pengaturan Notifikasi.
        'rekap_bulanan'   => ['label' => 'Rekap Presensi Bulanan (PDF tgl 1, tombol kirim)', 'personal' => false, 'pref_key' => ''],
        'ucapan_pagi'     => ['label' => 'Ucapan Pagi (Cron)',          'personal' => false, 'pref_key' => ''],
        'ucapan_sore'     => ['label' => 'Ucapan Sore (Cron, hari kerja saja)', 'personal' => false, 'pref_key' => ''],
        'lupa_checkout'   => ['label' => 'Lupa Check-out (Cron)',       'personal' => false, 'pref_key' => ''],
        'kehadiran_rendah'=> ['label' => 'Kehadiran Rendah (Cron)',     'personal' => false, 'pref_key' => ''],
        // Dikirim manual dari tombol "Kirim ke Grup" di /guru/piket/laporan.
        // Wajib ada di daftar ini supaya admin bisa mematikan kanalnya di
        // Pengaturan Notifikasi (tanpa barisnya, isEnabled() fail-open).
        'rekap_piket'     => ['label' => 'Rekap Piket Harian (tombol kirim)', 'personal' => false, 'pref_key' => ''],
    ];

    // ── Cache in-request ──────────────────────────────────────────────

    /** @var array<string,object>|null Indexed by jenis, null = belum dimuat */
    private ?array $cache = null;

    // ── API utama ─────────────────────────────────────────────────────

    /**
     * Ambil semua konfigurasi channel sebagai map [jenis => object].
     *
     * Jenis yang belum punya baris di DB akan di-seed dengan default
     * (personal = 1 bila jenis bisa personal, 0 bila grup-only).
     * Hasilnya di-cache in-request supaya setiap pemanggil notify*() di
     * NotificationService tidak memukul DB lebih dari satu kali per request.
     *
     * @return array<string,object>
     */
    public function getConfig(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            if (! $this->db->tableExists($this->table)) {
                // Tabel belum ada (migrate belum dijalankan) — kembalikan default
                return $this->cache = $this->buildDefaults();
            }

            $rows = $this->findAll();
            $map  = [];
            foreach ($rows as $row) {
                $map[$row->jenis] = $row;
            }

            // Seed jenis yang belum ada baris-nya
            $this->seedMissing($map);

            // Reload setelah seed supaya baris baru ikut masuk
            if (count($map) < count(self::JENIS_LIST)) {
                $rows = $this->findAll();
                $map  = [];
                foreach ($rows as $row) {
                    $map[$row->jenis] = $row;
                }
            }

            return $this->cache = $map;
        } catch (\Throwable $e) {
            log_message('error', '[NotifChannelConfig] getConfig gagal: ' . $e->getMessage());
            return $this->cache = $this->buildDefaults();
        }
    }

    /**
     * Apakah channel tertentu aktif untuk suatu jenis notifikasi?
     *
     * @param string $jenis   Mis. 'denda', 'izin', dsb.
     * @param string $channel Mis. 'tg_grup', 'wa_personal', dsb.
     */
    public function isEnabled(string $jenis, string $channel): bool
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            return false;
        }
        $cfg = $this->getConfig();
        if (! isset($cfg[$jenis])) {
            return true; // jenis tidak dikenal → default aktif (fail-open)
        }
        return (int) ($cfg[$jenis]->$channel ?? 1) === 1;
    }

    /**
     * Simpan konfigurasi dari array yang sudah disanitasi.
     *
     * Format input: ['keterlambatan' => ['tg_grup' => 1, 'wa_grup' => 0, ...], ...]
     *
     * PENTING: nilai di dalam array HARUS sudah berupa 0 atau 1 untuk
     * setiap channel, karena key selalu ada — termasuk saat checkbox-nya
     * tidak dicentang. Memakai isset() untuk membaca nyala/mati akan
     * selalu mengembalikan true karena key-nya ada, sehingga semua
     * channel tersimpan 1 dan admin tidak pernah bisa mematikan satu
     * pun. Sanitasi itu milik controller (PresensiAdmin::saveNotifConfig)
     * dan model ini dipakai juga oleh CLI/cron tanpa controller.
     *
     * @param array<string,array<string,int>> $data
     */
    public function saveConfig(array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $ok  = true;

        try {
            if (! $this->db->tableExists($this->table)) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        foreach (self::JENIS_LIST as $jenis => $meta) {
            if (! isset($data[$jenis])) {
                continue;
            }
            $vals = $data[$jenis];

            $update = [];
            foreach (self::CHANNELS as $ch) {
                // Jenis grup-only: paksa personal tetap 0
                if (! $meta['personal'] && in_array($ch, ['tg_personal', 'wa_personal'], true)) {
                    $update[$ch] = 0;
                    continue;
                }

                // Baca NILAINYA, bukan keberadaan key-nya. Nilai selain 1
                // dianggap mati supaya kolom tidak pernah menyimpan nilai
                // di luar 0/1.
                $update[$ch] = (int) ($vals[$ch] ?? 0) === 1 ? 1 : 0;
            }
            $update['updated_at'] = $now;

            try {
                $existing = $this->where('jenis', $jenis)->first();
                if ($existing) {
                    $ok = $this->update($existing->id, $update) && $ok;
                } else {
                    $update['jenis']      = $jenis;
                    $update['created_at'] = $now;
                    $ok = (bool) $this->insert($update) && $ok;
                }
            } catch (\Throwable $e) {
                log_message('error', '[NotifChannelConfig] saveConfig gagal untuk ' . $jenis . ': ' . $e->getMessage());
                $ok = false;
            }
        }

        // Invalidasi cache in-request setelah simpan
        $this->cache = null;

        return $ok;
    }

    // ── Internal helpers ──────────────────────────────────────────────

    /**
     * Seed baris yang belum ada di DB agar tabel tidak pernah kosong.
     *
     * @param array<string,object> $existing Baris yang sudah ada (by jenis)
     */
    private function seedMissing(array $existing): void
    {
        $now  = date('Y-m-d H:i:s');
        $seed = [];

        foreach (self::JENIS_LIST as $jenis => $meta) {
            if (isset($existing[$jenis])) {
                continue;
            }
            $canPersonal = $meta['personal'] ? 1 : 0;
            $seed[] = [
                'jenis'       => $jenis,
                'tg_grup'     => 1,
                'wa_grup'     => 1,
                'tg_personal' => $canPersonal,
                'wa_personal' => $canPersonal,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if ($seed !== []) {
            try {
                $this->db->table($this->table)->insertBatch($seed);
            } catch (\Throwable $e) {
                log_message('error', '[NotifChannelConfig] seedMissing gagal: ' . $e->getMessage());
            }
        }
    }

    /**
     * Bangun map default in-memory (tanpa menyentuh DB).
     * Dipakai sebagai fallback saat tabel belum ada atau query gagal.
     *
     * @return array<string,object>
     */
    private function buildDefaults(): array
    {
        $map = [];
        foreach (self::JENIS_LIST as $jenis => $meta) {
            $canPersonal = $meta['personal'] ? 1 : 0;
            $map[$jenis] = (object) [
                'jenis'       => $jenis,
                'tg_grup'     => 1,
                'wa_grup'     => 1,
                'tg_personal' => $canPersonal,
                'wa_personal' => $canPersonal,
            ];
        }
        return $map;
    }
}
