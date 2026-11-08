<?php

namespace App\Services;

/**
 * AvatarService — sumber kebenaran tunggal untuk menampilkan foto orang.
 *
 * Sebelum ada service ini, setiap view menyusun URL foto sendiri-sendiri.
 * Akibatnya tiga hal sekaligus rusak:
 *
 *  1. Hanya satu direktori yang ditebak (`uploads/profile_guru/`), padahal
 *     data lama tersimpan di `uploads/users/<id>/profile/`,
 *     `uploads/profiles/`, dan `uploads/foto_guru/` → file "tidak ada" →
 *     foto tidak muncul.
 *  2. Sisi browser (tab Leaderboard) menempelkan nama file ke baseUrl tanpa
 *     memeriksa apa pun → permintaan 404 → ikon gambar rusak.
 *  3. Fallback-nya ui-avatars.com, sebuah server eksternal → di jaringan
 *     sekolah yang tidak bisa keluar, avatar hilang seluruhnya.
 *
 * Sekarang resolusi dilakukan sekali di server: kalau file benar-benar ada
 * → URL-nya; kalau tidak → avatar inisial berupa SVG data-URI lokal. Tidak
 * ada lagi permintaan ke host eksternal dan tidak ada lagi tebakan path.
 */
class AvatarService
{
    /** Direktori (relatif terhadap FCPATH) yang pernah dipakai menyimpan foto guru. */
    public const DIREKTORI_GURU = [
        '',
        'uploads/profile_guru/',
        'uploads/foto_guru/',
        'uploads/profiles/',
        'uploads/users/',
        'uploads/guru/',
    ];

    /** Direktori foto siswa — `profile_siswa/` dipakai kode lama, `foto_siswa/` yang ada isinya. */
    public const DIREKTORI_SISWA = [
        '',
        'uploads/profile_siswa/',
        'uploads/foto_siswa/',
    ];

    /** Nama placeholder bawaan templat lama — berarti "tidak ada foto". */
    private const PLACEHOLDER = ['guru.png', 'siswa.png', 'siswa.jpg', 'default.png', 'default.jpg', 'user.png', 'user.jpg', 'avatar.png', 'null', '0'];

    /** Palet warna tetap untuk avatar inisial (dipilih dari hash nama). */
    private const WARNA = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#db2777', '#7c3aed', '#2563eb', '#0d9488', '#ca8a04'];

    /**
     * URL foto yang pasti tersedia, atau null kalau file-nya tidak ditemukan.
     * Nilai http(s) diteruskan apa adanya; nama tanpa ekstensi dicari dengan
     * ekstensi gambar yang umum dipakai aplikasi ini.
     */
    public static function url(?string $foto, array $direktori = self::DIREKTORI_GURU): ?string
    {
        $nilai = trim((string) $foto);
        if ($nilai === '' || in_array(strtolower($nilai), self::PLACEHOLDER, true)) {
            return null;
        }
        if (preg_match('#^https?://#i', $nilai)) {
            return $nilai;
        }

        $rel = ltrim(str_replace('\\', '/', $nilai), '/');
        foreach (self::kandidat($rel, $direktori) as $calon) {
            if (is_file(FCPATH . $calon)) {
                // ?v=<mtime>: ganti foto langsung tampil, tidak menunggu cache lama.
                return base_url($calon) . '?v=' . (int) filemtime(FCPATH . $calon);
            }
        }

        return null;
    }

    /**
     * Avatar inisial: SVG data-URI, dibuat di server sehingga tidak butuh
     * jaringan dan tidak bisa gagal muat. Tanpa $warna, warna dasar dipilih
     * dari hash nama supaya stabil per orang antar-halaman.
     */
    public static function inisial(?string $nama, int $size = 128, ?string $warna = null): string
    {
        $inisial = self::hurufAwal($nama);
        $warna   = self::safeColor($warna)
            ?? self::WARNA[crc32(mb_strtolower($inisial . '|' . trim((string) $nama))) % count(self::WARNA)];
        $teks    = htmlspecialchars($inisial, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $fontSize = (int) round($size * ($inisial === '?' ? 0.5 : 0.4));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size
            . '" viewBox="0 0 ' . $size . ' ' . $size . '">'
            . '<rect width="100%" height="100%" fill="' . $warna . '"/>'
            . '<text x="50%" y="' . round($size / 2 + $fontSize * 0.35) . '" fill="#ffffff" font-family="Plus Jakarta Sans, Segoe UI, sans-serif"'
            . ' font-size="' . $fontSize . '" font-weight="700" text-anchor="middle">' . $teks . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
    }

    /**
     * Foto kalau ada, avatar inisial kalau tidak — selalu mengembalikan URL.
     * $warna memakai format hex (#rgb/#rrggbb) dan hanya dipakai untuk
     * avatar inisial; lewati argumen terakhir untuk orang selain guru.
     */
    public static function tampilan(?string $foto, ?string $nama, ?string $warna = null, array $direktori = self::DIREKTORI_GURU): string
    {
        return self::url($foto, $direktori) ?? self::inisial($nama, 128, $warna);
    }

    /** Placeholder statis untuk kejadian langka: file ada saat render, 404 saat dimuat. */
    public static function cadangan(): string
    {
        return base_url('assets/img/avatar-cadangan.svg');
    }

    /** Foto terbaik dari baris hasil query leaderboard/rekan (`guru_foto` dulu, lalu `user_foto`). */
    public static function untukBaris(object $r): string
    {
        // Urutan kolom diperiksa satu per satu: `??` saja akan berhenti pada
        // string kosong, padahal master_guru.foto lama-kelamaan diisi '' (bukan
        // NULL) untuk guru tanpa foto — sedangkan fotonya masih ada di users.
        foreach (['guru_foto', 'user_foto', 'foto'] as $kolom) {
            $url = self::url((string) ($r->{$kolom} ?? ''));
            if ($url !== null) {
                return $url;
            }
        }

        return self::inisial(self::namaUtama($r));
    }

    /** Nama tampilan orang: nama guru, lalu gabungan depan-belakang, lalu username. */
    public static function namaUtama(object $r): string
    {
        $nama = trim((string) ($r->nama_guru ?? ''));
        if ($nama === '') {
            $nama = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
        }

        return $nama !== '' ? $nama : (string) ($r->username ?? 'Guru');
    }

    /** Foto siswa dari direktori foto siswa, dengan inisial sebagai fallback. */
    public static function untukSiswa(object $s, ?string $warna = null): string
    {
        $nama = trim((string) ($s->nama ?? $s->nama_siswa ?? '')) ?: '?';

        return self::tampilan((string) ($s->foto ?? ''), $nama, $warna, self::DIREKTORI_SISWA);
    }

    /** Hanya menerima warna hex (#rgb / #rrggbb) — warna lain diabaikan. */
    private static function safeColor(?string $warna): ?string
    {
        $warna = trim((string) $warna);
        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $warna)) {
            return $warna;
        }

        return null;
    }

    /** Dua huruf awal nama; satu huruf bila hanya satu kata; '?' bila kosong. */
    private static function hurufAwal(?string $nama): string
    {
        $kata = preg_split('/\s+/u', trim((string) $nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$kata) {
            return '?';
        }
        $inisial = mb_substr($kata[0], 0, 1);
        if (count($kata) > 1) {
            $inisial .= mb_substr($kata[1], 0, 1);
        }

        return mb_strtoupper($inisial);
    }

    /**
     * Semua path yang layak dicoba, berurutan: persis seperti tersimpan,
     * dengan tiap direktori yang pernah dipakai, lalu dengan ekstensi gambar
     * (data lama ada yang menyimpan nama tanpa ekstensi).
     *
     * @return string[]
     */
    private static function kandidat(string $rel, array $direktori): array
    {
        $udahRelatif = strpos($rel, 'uploads/') === 0;
        $dasar       = $udahRelatif ? [$rel] : array_map(static fn($d) => $d . $rel, $direktori);

        $out = [];
        foreach ($dasar as $path) {
            $out[] = $path;
            if (pathinfo($path, PATHINFO_EXTENSION) === '') {
                foreach (['.jpg', '.jpeg', '.png', '.webp'] as $ext) {
                    $out[] = $path . $ext;
                }
            }
        }

        return array_values(array_unique($out));
    }
}
