<?php

namespace App\Services;

/**
 * WhatsAppFormatter — ubah satu pesan berformat HTML Telegram menjadi
 * markup WhatsApp, supaya NotificationService bisa memakai satu sumber
 * string untuk kedua kanal.
 *
 * NotificationService menyusun pesan dengan parse_mode HTML Telegram
 * (<b>, <i>, <code>, <br> dan entitas &amp;/&lt;/&gt;). WhatsApp tidak
 * mengenal HTML sama sekali: markup yang dikenal hanya *bold*, _italic_,
 * ~strikis~, dan ```mono```. Tanpa konversi ini tag <b> muncul apa adanya
 * di dalam chat dan pesan terlihat rusak.
 *
 * Urutan konversi penting: tag diganti ke marker dulu, tag sisanya dibuang,
 * baru entitas HTML di-decode. Kalau dibalik, data pengguna yang sudah
 * di-escape (mis. nama "Ahmad &amp; Sons") akan ikut jadi marker.
 */
class WhatsAppFormatter
{
    /** Batas panjang `text` pada OpenWA send-text (ValidationPipe @MaxLength). */
    public const MAX_LENGTH = 4096;

    /** Sisa ruang yang dipakai untuk nomor urut / penanda sambungan. */
    private const CHUNK_RESERVE = 24;

    /** Tag yang diganti ke marker WhatsApp. */
    private const TAG_MAP = [
        // Bold
        'b'      => '*',
        'strong' => '*',
        // Italic
        'i'      => '_',
        'em'     => '_',
        // Strikis
        's'      => '~',
        'strike' => '~',
        'del'    => '~',
        // Mono — WA butuh tiga backtick di kedua sisi
        'code'   => '```',
        'pre'    => '```',
        // Tanpa padanan WhatsApp: dibuang, bukan ditampilkan mentah
        'u'      => '',
        'ins'    => '',
        'a'      => '',
    ];

    /**
     * Ubah pesan HTML Telegram menjadi pesan WhatsApp.
     *
     * @param string $html  Pesan sumber (boleh HTML Telegram)
     * @param bool   $chunk Pecah menjadi beberapa pesan bila melebihi batas.
     *                      Dimatikan pada hasil diagnosis/test yang perlu
     *                      melihat teks utuh.
     * @return string|array<int,string> string tunggal, atau daftar potongan
     *                                   bila $chunk aktif dan pesan kepanjangan
     */
    public static function toWhatsApp(string $html, bool $chunk = true)
    {
        $text = self::convert($html);

        if (! $chunk) {
            return self::hardClip($text);
        }

        return self::split($text);
    }

    /**
     * Konversi tunggal tanpa pemecahan. Dipisah dari toWhatsApp() agar
     * split() tidak perlu memanggil konversi berkali-kali.
     */
    private static function convert(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // 1. <br> adalah satu-satunya tag yang jadi baris baru.
        $text = preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html;

        // 2. Tag dengan padanan WhatsApp -> marker-nya. Opening dan closing
        //    dibedakan supaya <b>x</b> menjadi *x*, bukan **.
        $text = preg_replace_callback(
            '#</?([a-z]+)[^>]*>#i',
            static function (array $m): string {
                $marker = self::TAG_MAP[strtolower($m[1])] ?? null;
                return $marker === null ? '' : $marker; // null = tag tanpa padanan
            },
            $text
        ) ?? $text;

        // 3. Sisa tag lain (mis. <span data-x="y">) dibuang.
        $text = strip_tags($text);

        // 4. Entitas di-decode TERAKHIR. Data pengguna arrives ter-escape
        //    (Ahmad &amp; Sons) dan harus kembali jadi teks biasa — kalau
        //    ini dilakukan sebelum langkah 2, &lt;b&gt; jadi marker palsu.
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 5. Rapikan whitespace yang sering muncul dari tag yang dibuang.
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Potong pesan yang melebihi batas OpenWA pada batas baris, tidak
     * memotong kata di tengah. Bila satu baris pun lebih panjang dari
     * batas, baris itu dipotong keras — lebih baik daripada 400.
     *
     * @return array<int,string> daftar potongan, minimal satu elemen
     */
    private static function split(string $text): array
    {
        if ($text === '') {
            return [''];
        }
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return [$text];
        }

        $budget = self::MAX_LENGTH - self::CHUNK_RESERVE;
        $lines = explode("\n", $text);
        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            // Satu baris sendiri sudah melebihi budget -> potong keras.
            if (mb_strlen($line) > $budget) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }
                foreach (str_split($line, $budget) as $piece) {
                    $chunks[] = $piece;
                }
                continue;
            }

            $candidate = $current === '' ? $line : $current . "\n" . $line;
            if (mb_strlen($candidate) > $budget) {
                $chunks[] = $current;
                $current = $line;
                continue;
            }
            $current = $candidate;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        // Nomor urut hanya masuk kalau benar-benar terpotong; pesan satu
        // bagian tidak perlu "(1/2)" yang tidak pernah dibacanya.
        $total = count($chunks);
        if ($total < 2) {
            return $chunks;
        }

        foreach ($chunks as $i => $chunk) {
            $label = '(' . ($i + 1) . '/' . $total . ')';
            $chunks[$i] = $chunk . "\n\n" . $label;
        }

        return $chunks;
    }

    /**
     * Potong keras tepat di batas maksimum, tanpa pemecahan_multiple.
     * Dipakai untuk pesan diagnosis yang perlu muat dalam satu balasan.
     */
    private static function hardClip(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }
        return mb_substr($text, 0, self::MAX_LENGTH - 1) . '…';
    }

    /**
     * Bersihkan nomor telepon menjadi chat id OpenWA (`<digits>@c.us`).
     *
     * Nomor Indonesia lazim ditulis 0812…, +62812…, 62812…, atau dengan
     * spasi/strip. OpenWA hanya menerima WID tanpa tanda baca.
     *
     * @return string '' bila tidak ada digit yang cocok
     */
    public static function normalizeNumber(?string $raw): string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';
        if ($digits === '') {
            return '';
        }

        // Buang satu angka nol di depan: 0812 -> 812 (kode negara Indonesia).
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }

        return $digits . '@c.us';
    }

    /**
     * Versi nomor saja tanpa suffix '@c.us', untuk disimpan di
     * user_notifikasi.wa_number dan ditampilkan di UI.
     */
    public static function normalizeDigits(?string $raw): string
    {
        $wid = self::normalizeNumber($raw);
        return $wid === '' ? '' : str_replace('@c.us', '', $wid);
    }

    /**
     * True bila string sudah berbentuk chat id yang bisa dikirim OpenWA.
     * Menerima WID grup (<id>@g.us) maupun kontak (<phone>@c.us / @lid).
     */
    public static function isValidChatId(?string $raw): bool
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return false;
        }

        // Buang '@' di depan bila orang menyalin "@62812@c.us" dari dashboard.
        // Sisanya divalidasi sebagai WID; "@c.us" saja tetap gagal karena
        // tidak ada digit.
        $v = ltrim($v, '@');

        return preg_match('/^[0-9]{6,20}@(c\.us|g\.us|lid|broadcast|newsletter)$/', $v) === 1;
    }
}