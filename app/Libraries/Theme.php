<?php

namespace App\Libraries;

/**
 * Dynamic theme resolver.
 *
 * Menyimpan pilihan tema di tabel `setting` (theme_preset, theme_custom) dan
 * menerjemahkannya menjadi CSS custom property (--brand-*, --primary-*,
 * --accent-*) yang dipakai oleh Tailwind dan seluruh halaman.
 */
class Theme
{
    public const DEFAULT_PRESET = 'default';

    /** Palet sumber (nilai Tailwind standar) untuk tiap keluarga warna. */
    private const PALETTES = [
        'rose' => [
            50 => '#fff1f2', 100 => '#ffe4e6', 200 => '#feccd3', 300 => '#fda4af', 400 => '#fb7185',
            500 => '#f43f5e', 600 => '#e11d48', 700 => '#be123c', 800 => '#881337', 900 => '#4c0519',
        ],
        'blue' => [
            50 => '#eff6ff', 100 => '#dbeafe', 200 => '#bfdbfe', 300 => '#93c5fd', 400 => '#60a5fa',
            500 => '#3b82f6', 600 => '#2563eb', 700 => '#1d4ed8', 800 => '#1e40af', 900 => '#1e3a8a',
        ],
        'violet' => [
            50 => '#f5f3ff', 100 => '#ede9fe', 200 => '#ddd6fe', 300 => '#c4b5fd', 400 => '#a78bfa',
            500 => '#8b5cf6', 600 => '#7c3aed', 700 => '#6d28d9', 800 => '#5b21b6', 900 => '#4c1d95',
        ],
        'sky' => [
            50 => '#f0f9ff', 100 => '#e0f2fe', 200 => '#bae6fd', 300 => '#7dd3fc', 400 => '#38bdf8',
            500 => '#0ea5e9', 600 => '#0284c7', 700 => '#0369a1', 800 => '#075985', 900 => '#0c4a6e',
        ],
        'emerald' => [
            50 => '#ecfdf5', 100 => '#d1fae5', 200 => '#a7f3d0', 300 => '#6ee7b7', 400 => '#34d399',
            500 => '#10b981', 600 => '#059669', 700 => '#047857', 800 => '#065f46', 900 => '#064e3b',
        ],
        'orange' => [
            50 => '#fff7ed', 100 => '#ffedd5', 200 => '#fed7aa', 300 => '#fdba74', 400 => '#fb923c',
            500 => '#f97316', 600 => '#ea580c', 700 => '#c2410c', 800 => '#9a3412', 900 => '#7c2d12',
        ],
        // Dipakai untuk gradient yang berpasangan dengan blue/violet.
        'indigo' => [
            50 => '#eef2ff', 100 => '#e0e7ff', 200 => '#c7d2fe', 300 => '#a5b4fc', 400 => '#818cf8',
            500 => '#6366f1', 600 => '#4f46e5', 700 => '#4338ca', 800 => '#3730a3', 900 => '#312e81',
        ],
        'purple' => [
            50 => '#faf5ff', 100 => '#f3e8ff', 200 => '#e9d5ff', 300 => '#d8b4fe', 400 => '#c084fc',
            500 => '#a855f7', 600 => '#9333ea', 700 => '#7e22ce', 800 => '#6b21a8', 900 => '#581c87',
        ],
    ];

    private const PRESETS = [
        'default' => [
            'label'     => 'Default',
            'desc'      => 'Tampilan bawaan: rose pada presensi, biru pada panel admin.',
            'brand'     => 'rose',
            'primary'   => 'blue',
            'accent'    => 'violet',
            'secondary' => '#ec4899',
            'tertiary'  => '#f59e0b',
        ],
        'ocean' => [
            'label'     => 'Ocean Blue',
            'desc'      => 'Nuansa biru laut yang tenang dan profesional.',
            'brand'     => 'sky',
            'primary'   => 'sky',
            'accent'    => 'sky',
        ],
        'emerald' => [
            'label'     => 'Emerald Green',
            'desc'      => 'Hijau segar untuk tampilan yang natural.',
            'brand'     => 'emerald',
            'primary'   => 'emerald',
            'accent'    => 'emerald',
        ],
        'royal' => [
            'label'     => 'Royal Purple',
            'desc'      => 'Ungu mewah dengan aksen yang modern.',
            'brand'     => 'violet',
            'primary'   => 'violet',
            'accent'    => 'violet',
        ],
        'sunset' => [
            'label'     => 'Sunset Orange',
            'desc'      => 'Oranye hangat yang energik.',
            'brand'     => 'orange',
            'primary'   => 'orange',
            'accent'    => 'orange',
        ],
        'rose' => [
            'label'     => 'Rose Pink',
            'desc'      => 'Pink lembut untuk tampilan yang ramah.',
            'brand'     => 'rose',
            'primary'   => 'rose',
            'accent'    => 'rose',
            'secondary' => '#ec4899',
            'tertiary'  => '#f59e0b',
        ],
        'custom' => [
            'label'     => 'Custom',
            'desc'      => 'Pilih warna dasar sendiri; palet dibuat otomatis.',
            'brand'     => 'rose',
            'primary'   => 'blue',
            'accent'    => 'violet',
        ],
    ];

    private static ?array $resolved = null;

    public static function presetKeys(): array
    {
        return array_keys(self::PRESETS);
    }

    /**
     * Pilihan preset untuk UI admin (termasuk label & warna pratinjau).
     */
    public static function presetOptions(): array
    {
        $options = [];
        foreach (self::PRESETS as $key => $preset) {
            if ($key === 'custom') {
                continue;
            }
            $resolved = self::resolve($key, null);
            $options[$key] = [
                'label'  => $preset['label'],
                'desc'   => $preset['desc'],
                'colors' => [
                    'brand'   => $resolved['brand'][400],
                    'primary' => $resolved['primary'][600],
                    'accent'  => $resolved['accent'][800],
                ],
            ];
        }

        return $options;
    }

    /**
     * Tema aktif (dibaca dari tabel setting, di-cache per request).
     */
    public static function current(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $preset = self::DEFAULT_PRESET;
        $custom = null;

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('setting') && $db->fieldExists('theme_preset', 'setting')) {
                $select = 'theme_preset, theme_custom';
                if (!$db->fieldExists('theme_custom', 'setting')) {
                    $select = 'theme_preset';
                }
                $row = $db->table('setting')->select($select)->get(1)->getRowArray();
                if (is_array($row)) {
                    $preset = (string) ($row['theme_preset'] ?? self::DEFAULT_PRESET);
                    $custom = isset($row['theme_custom']) ? (string) $row['theme_custom'] : null;
                }
            }
        } catch (\Throwable $e) {
            log_message('debug', '[Theme] gagal membaca preferensi tema: ' . $e->getMessage());
        }

        return self::$resolved = self::resolve($preset, $custom);
    }

    public static function flushCache(): void
    {
        self::$resolved = null;
    }

    /**
     * Normalisasi input preset/custom menjadi struktur palet lengkap.
     */
    public static function resolve(?string $preset, ?string $custom): array
    {
        $preset = strtolower(trim((string) $preset));
        if (!isset(self::PRESETS[$preset])) {
            $preset = self::DEFAULT_PRESET;
        }

        $custom = self::normalizeHex($custom);

        if ($preset === 'custom' && $custom !== null) {
            $palette = self::paletteFromHex($custom);
            $brand   = $primary = $accent = $palette;
            $indigo  = $purple = $palette;
            $secondary = $palette[400];
            $tertiary  = $palette[700];
        } else {
            if ($preset === 'custom') {
                $preset = self::DEFAULT_PRESET;
            }
            $cfg    = self::PRESETS[$preset];
            $brand  = self::PALETTES[$cfg['brand']];
            $primary = self::PALETTES[$cfg['primary']];
            $accent = self::PALETTES[$cfg['accent']];
            $secondary = $cfg['secondary'] ?? $accent[400];
            $tertiary  = $cfg['tertiary'] ?? $accent[700];
            // Default mempertahankan indigo/purple asli; preset lain
            // menyamakan kedua keluarga itu dengan palet tema.
            $indigo = $preset === self::DEFAULT_PRESET ? self::PALETTES['indigo'] : $primary;
            $purple = $preset === self::DEFAULT_PRESET ? self::PALETTES['purple'] : $accent;
        }

        return [
            'preset'    => $preset,
            'custom'    => $custom,
            'label'     => self::PRESETS[$preset]['label'],
            'brand'     => $brand,
            'primary'   => $primary,
            'accent'    => $accent,
            'indigo'    => $indigo,
            'purple'    => $purple,
            'secondary' => $secondary,
            'tertiary'  => $tertiary,
        ];
    }

    /**
     * CSS declaration untuk semua variabel palet.
     */
    public static function cssVars(?array $theme = null): string
    {
        $theme = $theme ?? self::current();
        $css   = [];

        foreach (['brand', 'primary', 'accent', 'indigo', 'purple'] as $key) {
            foreach ($theme[$key] as $shade => $hex) {
                [$r, $g, $b] = self::hexToRgb($hex);
                $css[] = "--{$key}-{$shade}-rgb: {$r} {$g} {$b}";
                $css[] = "--{$key}-{$shade}: {$hex}";
            }
        }

        $css[] = '--brand-secondary: ' . $theme['secondary'];
        $css[] = '--brand-tertiary: ' . $theme['tertiary'];
        [$sr, $sg, $sb] = self::hexToRgb($theme['secondary']);
        [$tr, $tg, $tb] = self::hexToRgb($theme['tertiary']);
        $css[] = "--brand-secondary-rgb: {$sr} {$sg} {$sb}";
        $css[] = "--brand-tertiary-rgb: {$tr} {$tg} {$tb}";
        $css[] = '--theme-preset: "' . $theme['preset'] . '"';

        return implode(';', $css) . ';';
    }

    /**
     * Tag <style> untuk layout aplikasi.
     */
    public static function styleTag(?array $theme = null): string
    {
        return '<style id="app-theme-vars">:root{' . self::cssVars($theme) . '}</style>';
    }

    /**
     * Tag <style> untuk halaman login: selain variabel palet, menimpa
     * variabel lokal lama (--primary, --secondary, --accent, gradient).
     */
    public static function loginStyleTag(?array $theme = null): string
    {
        $theme = $theme ?? self::current();
        [$r, $g, $b] = self::hexToRgb($theme['brand'][600]);

        $extra = implode(';', [
            '--primary: var(--brand-600)',
            "--primary-rgb: {$r}, {$g}, {$b}",
            '--secondary: var(--brand-secondary)',
            '--accent: var(--brand-tertiary)',
            '--brand-gradient: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-secondary) 50%, var(--brand-tertiary) 100%)',
            '--brand-glow: 0 10px 25px -5px rgb(var(--brand-600-rgb) / 0.35)',
        ]);

        return '<style id="app-theme-vars">:root{' . self::cssVars($theme) . $extra . '}</style>';
    }

    /**
     * Warna meta theme-color. Area default mempertahankan warna lamanya:
     * admin biru, guru rose, siswa violet.
     */
    public static function metaColor(string $area = 'admin'): string
    {
        $theme = self::current();
        if ($theme['preset'] !== 'default') {
            return $theme['brand'][600];
        }

        return match ($area) {
            'guru'  => $theme['brand'][600],
            'siswa' => $theme['accent'][600],
            default => $theme['primary'][600],
        };
    }

    /**
     * Konfigurasi untuk pratinjau langsung di halaman admin.
     */
    public static function clientConfig(): array
    {
        $presets = [];
        foreach (self::presetKeys() as $key) {
            if ($key === 'custom') {
                continue;
            }
            $resolved = self::resolve($key, null);
            $presets[$key] = [
                'brand'     => $resolved['brand'],
                'primary'   => $resolved['primary'],
                'accent'    => $resolved['accent'],
                'indigo'    => $resolved['indigo'],
                'purple'    => $resolved['purple'],
                'secondary' => $resolved['secondary'],
                'tertiary'  => $resolved['tertiary'],
            ];
        }

        return [
            'presets' => $presets,
            'custom'  => self::clientPaletteFromHex('#2563eb'),
        ];
    }

    /**
     * Palet turunan untuk pratinjau JS (harus sama dengan paletteFromHex()).
     */
    public static function clientPaletteFromHex(string $hex): array
    {
        return self::paletteFromHex($hex);
    }

    /**
     * Bangun palet 50..900 dari satu warna dasar (dipakai sebagai shade 500).
     */
    public static function paletteFromHex(string $hex): array
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        $baseL = min(62.0, max(38.0, $l));
        $baseS = min(95.0, max(35.0, $s));

        $lightness = [
            50 => 96.0, 100 => 92.0, 200 => 84.0, 300 => 74.0, 400 => 64.0,
            500 => $baseL, 600 => $baseL - 7, 700 => $baseL - 15, 800 => $baseL - 24, 900 => $baseL - 33,
        ];
        $satFactor = [50 => 0.55, 100 => 0.7, 200 => 0.8, 300 => 0.9, 400 => 1.0, 500 => 1.0, 600 => 1.0, 700 => 1.0, 800 => 1.0, 900 => 1.0];

        $palette = [];
        foreach ($lightness as $shade => $targetL) {
            $palette[$shade] = self::hslToHex($h, $baseS * $satFactor[$shade], min(97.0, max(8.0, $targetL)));
        }

        return $palette;
    }

    public static function normalizeHex(?string $hex): ?string
    {
        $hex = strtolower(trim((string) $hex));
        if ($hex === '') {
            return null;
        }
        if ($hex[0] !== '#') {
            $hex = '#' . $hex;
        }
        if (preg_match('/^#([0-9a-f]{3})$/', $hex, $m)) {
            return '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }

        return preg_match('/^#[0-9a-f]{6}$/', $hex) ? $hex : null;
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l   = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l * 100];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        if ($max === $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }
        $h *= 60;

        return [$h, $s * 100, $l * 100];
    }

    private static function hslToHex(float $h, float $s, float $l): string
    {
        $h = fmod($h, 360);
        if ($h < 0) {
            $h += 360;
        }
        $s /= 100;
        $l /= 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0, $c];
        } else {
            [$r, $g, $b] = [$c, 0, $x];
        }

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255)
        );
    }
}
