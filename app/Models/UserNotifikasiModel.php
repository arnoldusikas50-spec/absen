<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Mapping kanal personal per user (Telegram chat id + nomor WhatsApp) dan
 * preferensi notifikasi personal.
 */
class UserNotifikasiModel extends Model
{
    protected $table = 'user_notifikasi';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id', 'telegram_chat_id', 'prefs',
        'link_token', 'link_expires',
        'wa_number', 'wa_verified',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public const PREF_DEFAULTS = [
        'denda'        => true,
        'teguran'      => true,
        'alpha'        => true,
        'kehadiran'    => true,
        'izin'         => true,
        'koreksi'      => true,
        'guru_terawal' => true,
        'panggilan'    => true,
    ];

    public function getByUserId(int $userId): ?object
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            return $this->where('user_id', $userId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getByChatId(string $chatId): ?object
    {
        if ($chatId === '') {
            return null;
        }
        try {
            return $this->where('telegram_chat_id', $chatId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getByLinkToken(string $token): ?object
    {
        if ($token === '') {
            return null;
        }
        try {
            return $this->where('link_token', $token)
                ->where('link_expires >=', date('Y-m-d H:i:s'))
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Terbitkan token tautan 15 menit untuk user (ditukar via /start <token>).
     */
    public function issueLinkToken(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            $token = bin2hex(random_bytes(16));
            $row = $this->getByUserId($userId);
            $data = [
                'link_token'   => $token,
                'link_expires' => date('Y-m-d H:i:s', time() + 900),
            ];
            if ($row) {
                if ($this->update($row->id, $data) === false) {
                    // Produksi: update() mengembalikan false, bukan melempar.
                    // Tanpa pemeriksaan ini user tetap diberi token yang tidak
                    // pernah bisa menyelesaikan /start.
                    log_message('error', '[notify] issueLinkToken: gagal menyimpan token untuk user ' . $userId);
                    return null;
                }
            } else {
                $data['user_id'] = $userId;
                if ($this->insert($data) === false) {
                    log_message('error', '[notify] issueLinkToken: gagal membuat baris untuk user ' . $userId);
                    return null;
                }
            }
            return $token;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ikat chat_id ke user pemilik token tautan (satu chat = satu user).
     */
    public function bindChat(string $token, string $chatId): ?int
    {
        $row = $this->getByLinkToken($token);
        if (!$row) {
            return null;
        }
        try {
            // Jalur ini memakai token bertanda tangan yang hanya bisa
            // diselesaikan dari chat milik sendiri, jadi chat_id di sini
            // sudah terbukti milik pemohon. Tetap diperiksa supaya satu
            // chat tidak pernah tertaut ke dua akun.
            $pemilik = $this->where('telegram_chat_id', $chatId)
                ->where('user_id !=', (int) $row->user_id)
                ->first();

            if ($pemilik !== null) {
                log_message('warning', '[notify] bindChat: chat id sudah milik user '
                    . $pemilik->user_id . ', permintaan dari user ' . $row->user_id . ' ditolak.');
                return null;
            }

            $tersimpan = $this->update($row->id, [
                'telegram_chat_id' => $chatId,
                'link_token'       => null,
                'link_expires'     => null,
            ]);
            if ($tersimpan === false) {
                log_message('error', '[notify] bindChat: gagal menyimpan chat id untuk user ' . $row->user_id);
                $this->lastBindError = 'Gagal menyimpan tautan. Coba lagi nanti.';
                return null;
            }
            return (int) $row->user_id;
        } catch (\Throwable $e) {
            log_message('error', 'bindChat gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ikat chat_id ke user secara langsung (tanpa /start <token>).
     * Dipakai saat admin atau guru mengetikkan ID Telegram-nya sendiri.
     *
     * Chat yang sudah tertaut ke user lain TIDAK lagi direbut diam-diam.
     * Sebelumnya baris milik user lain dikosongkan tanpa izin, sehingga
     * guru yang mengetik chat id rekan kerjanya diam-diam mematikan
     * notifikasi rekannya — dan tidak ada yang diberi tahu.
     *
     * Chat yang terlanjur dipakai user lain berarti input salah: satu chat
     * hanya bisa dimiliki satu akun. Penolakan ini Return pesan
     * eksplisit supaya UI bisa menampilkannya ke pengguna.
     */
    public function bindChatDirect(int $userId, string $chatId): bool
    {
        if ($userId <= 0 || $chatId === '') {
            return false;
        }
        try {
            $pemilik = $this->where('telegram_chat_id', $chatId)
                ->where('user_id !=', $userId)
                ->first();

            if ($pemilik !== null) {
                log_message('warning', '[notify] user ' . $userId
                    . ' mencoba memakai chat id yang sudah milik user ' . $pemilik->user_id
                    . ' — ditolak.');
                $this->lastBindError = 'ID Telegram ini sudah tertaut ke akun lain.';
                return false;
            }

            $row = $this->getByUserId($userId);
            if ($row) {
                $this->update($row->id, [
                    'telegram_chat_id' => $chatId,
                    'link_token'       => null,
                    'link_expires'     => null,
                ]);
            } else {
                $this->insert([
                    'user_id'          => $userId,
                    'telegram_chat_id' => $chatId,
                ]);
            }
            $this->lastBindError = '';
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'bindChatDirect gagal: ' . $e->getMessage());
            $this->lastBindError = 'Gagal menyimpan tautan. Coba lagi nanti.';
            return false;
        }
    }

    /** Alasan bindChatDirect()/bindChat() menolak, untuk ditampilkan ke user. */
    public string $lastBindError = '';

    public function unbindByChatId(string $chatId): bool
    {
        try {
            return (bool) $this->where('telegram_chat_id', $chatId)
                ->set(['telegram_chat_id' => null])
                ->update();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getPrefs(int $userId): array
    {
        $row = $this->getByUserId($userId);
        $prefs = self::PREF_DEFAULTS;
        if ($row && !empty($row->prefs)) {
            $decoded = json_decode((string) $row->prefs, true);
            if (is_array($decoded)) {
                foreach ($prefs as $k => $v) {
                    if (array_key_exists($k, $decoded)) {
                        $prefs[$k] = (bool) $decoded[$k];
                    }
                }
            }
        }
        return $prefs;
    }

    /**
     * Simpan preferensi (hanya kunci yang dikenal).
     */
    public function setPrefs(int $userId, array $prefs): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $clean = self::PREF_DEFAULTS;
        foreach ($clean as $k => $v) {
            if (array_key_exists($k, $prefs)) {
                $clean[$k] = (bool) $prefs[$k];
            }
        }
        try {
            $row = $this->getByUserId($userId);
            $data = ['prefs' => json_encode($clean)];
            if ($row) {
                return (bool) $this->update($row->id, $data);
            }
            $data['user_id'] = $userId;
            return (bool) $this->insert($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Daftar akun tertaut untuk halaman admin (nama + chat + prefs).
     *
     * @return array<int, object>
     */
    public function getAllLinked(): array
    {
        try {
            return $this->db->table('user_notifikasi n')
                ->select('n.user_id, n.telegram_chat_id, n.prefs, n.updated_at, u.username, u.first_name, u.last_name, g.nama_guru')
                ->join('users u', 'u.id = n.user_id', 'left')
                ->join('master_guru g', 'g.id_user = n.user_id', 'left')
                ->where('n.telegram_chat_id IS NOT NULL')
                ->where('n.telegram_chat_id !=', '')
                ->orderBy('g.nama_guru', 'ASC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── WhatsApp personal ──────────────────────────────────────────────

    /**
     * Nomor WhatsApp yang sudah dikonfirmasi untuk sebuah user.
     *
     * Hanya baris dengan wa_verified = 1 yang dikembalikan: nomor yang
     * baru diketik guru belum tentu miliknya (salah ketik digit), dan
     * OpenWA tidak punya mekanisme verifikasi inbound seperti bot
     * Telegram. Kiriman ke nomor yang salah berarti membocorkan data
     * absensi ke orang tak dikenal, jadi konfirmasi admin itu wajib.
     *
     * @return string '' bila belum ada / belum dikonfirmasi
     */
    public function getVerifiedWaNumber(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }
        try {
            if (! $this->db->fieldExists('wa_number', $this->table)) {
                return '';
            }
            $row = $this->where('user_id', $userId)
                ->where('wa_number IS NOT NULL')
                ->where('wa_number !=', '')
                ->where('wa_verified', 1)
                ->first();
            return $row ? (string) $row->wa_number : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Nomor WA apa adanya, Terverifikasi atau belum (untuk UI admin). */
    public function getWaNumber(int $userId): string
    {
        $row = $this->getByUserId($userId);
        return ($row && ! empty($row->wa_number)) ? (string) $row->wa_number : '';
    }

    /**
     * Simpan nomor WA untuk sebuah user.
     *
     * Berbeda dengan Telegram, tidak ada "/start <token>": nomor diisi
     * langsung. $verified kontrol apakah nomor langsung aktif atau masih
     * menunggu konfirmasi admin.
     *
     * PENTING: kalau nomornya TIDAK berubah, status verifikasi yang sudah ada
     * dipertahankan. Sebelumnya setiap penyimpanan ulang menulis
     * `wa_verified = 0` apa pun isinya — jadi mengubah satu field lain di
     * profil (mis. alamat email) diam-diam mencabut konfirmasi admin, dan
     * guru harus dikonfirmasi ulang setiap kali menyimpan apa pun.
     *
     * Nomor kosong berarti melepas tautan.
     */
public function setWaNumber(int $userId, string $number, bool $verified = false): bool
{
    if ($userId <= 0) {
        return false;
    }
    try {
        $row = $this->getByUserId($userId);

        if ($row && (string) $row->wa_number === $number && $number !== '') {
            // Nomor sama persis. Status yang ada sudah sah — jangan
            // diturunkan hanya karena form dikirim ulang.
            return true;
        }

        $data = [
            'wa_number'   => $number === '' ? null : $number,
            'wa_verified' => $number === '' ? 0 : ($verified ? 1 : 0),
        ];
        if ($row) {
            return (bool) $this->update($row->id, $data);
        }
        if ($number === '') {
            return true; // tidak ada yang perlu dilepas
        }
        $data['user_id'] = $userId;
        return (bool) $this->insert($data);
    } catch (\Throwable $e) {
        log_message('error', 'setWaNumber gagal: ' . $e->getMessage());
        return false;
    }
}

    /** Tandai nomor WA milik user sebagai sudah dikonfirmasi admin. */
    public function verifyWaNumber(int $userId): bool
    {
        $number = $this->getWaNumber($userId);
        if ($number === '') {
            return false;
        }
        try {
            return (bool) $this->where('user_id', $userId)->set(['wa_verified' => 1])->update();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Lepas tautan WA seorang user. */
    public function unbindWa(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        try {
            return (bool) $this->where('user_id', $userId)
                ->set(['wa_number' => null, 'wa_verified' => 0])
                ->update();
        } catch (\Throwable $e) {
            log_message('error', 'unbindWa gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Daftar akun yang punya nomor WhatsApp, untuk halaman admin.
     *
     * @return array<int, object>
     */
    public function getAllWaLinked(): array
    {
        try {
            return $this->db->table('user_notifikasi n')
                ->select('n.user_id, n.wa_number, n.wa_verified, n.updated_at, u.username, u.first_name, u.last_name, g.nama_guru, g.wa_number as wa_number_guru')
                ->join('users u', 'u.id = n.user_id', 'left')
                ->join('master_guru g', 'g.id_user = n.user_id', 'left')
                ->where('n.wa_number IS NOT NULL')
                ->where('n.wa_number !=', '')
                ->orderBy('g.nama_guru', 'ASC')
                ->get()->getResult();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Berapa user yang sudah punya nomor WA terverifikasi — dipakai untuk
     * ringkasan di halaman pengaturan.
     */
    public function countVerifiedWa(): int
    {
        try {
            return (int) $this->where('wa_verified', 1)
                ->where('wa_number IS NOT NULL')
                ->where('wa_number !=', '')
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
