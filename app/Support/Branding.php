<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Erscheinungsbild der Schule: Name, Logo, Farben, Domain.
 * Werte aus den Einstellungen (Admin) haben Vorrang vor der Compose-Datei.
 *
 * Appearance of the school: name, logo, colours, domain.
 * Values from the settings (admin) take precedence over the Compose file.
 */
class Branding
{
    public const DEFAULTS = [
        'brand_accent_color'     => '#ff7a3d',   // Knöpfe, Hervorhebungen / buttons, highlights
        'brand_header_color'     => '#ffffff',   // Kopfzeile / header bar
        'brand_background_color' => '#fbf7f0',   // Seitenhintergrund / page background
    ];

    public static function school(): string
    {
        return Setting::get('brand_company_name', config('bildabooky.school'));
    }

    public static function colour(string $key): string
    {
        $v = Setting::get($key);
        return is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v) ? strtolower($v) : self::DEFAULTS[$key];
    }

    public static function hasLogo(): bool
    {
        $p = Setting::get('brand_logo_path');
        return $p && is_file(self::logoDirectory() . '/' . $p);
    }

    public static function logoUrl(): string
    {
        return self::hasLogo()
            ? route('brand.logo', ['v' => Setting::get('brand_logo_version', '1')])
            : asset('img/logo.svg');
    }

    public static function logoDirectory(): string
    {
        return rtrim(dirname(config('bildabooky.media_path')), '/') . '/marke';
    }

    /**
     * Öffentliche Adresse, z. B. https://buecher.meine-schule.de (leer = APP_URL)
     *
     * Public address, e.g. https://buecher.meine-schule.de (empty = APP_URL)
     */
    public static function domain(): ?string
    {
        $d = Setting::get('domain');
        return $d ? rtrim($d, '/') : null;
    }

    public static function footerText(): ?string
    {
        return Setting::get('brand_footer_text');
    }

    /**
     * Lesbare Schriftfarbe (hell/dunkel) für einen Hintergrund — WCAG-Kontrast
     *
     * Readable text colour (light/dark) for a background — WCAG contrast
     */
    public static function ink(string $hex): string
    {
        return self::contrast($hex, '#ffffff') >= self::contrast($hex, '#26324a') ? '#ffffff' : '#26324a';
    }

    public static function contrast(string $a, string $b): float
    {
        $l = function (string $hex) {
            $hex = ltrim($hex, '#');
            $c = array_map(fn ($i) => hexdec(substr($hex, $i, 2)) / 255, [0, 2, 4]);
            $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);
            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };
        [$x, $y] = [$l($a), $l($b)];
        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    /**
     * CSS-Variablen für das Layout. Im dunklen Modus (html[data-theme="dark"])
     * bleiben Akzentfarbe und eine eigene Kopfzeilenfarbe erhalten; Hintergrund
     * und Flächen kommen aus der dunklen Palette in app.css.
     *
     * CSS variables for the layout. In dark mode (html[data-theme="dark"])
     * the accent colour and a custom header colour are kept; background
     * and surfaces come from the dark palette in app.css.
     */
    public static function css(): string
    {
        $accent = self::colour('brand_accent_color');
        $header = self::colour('brand_header_color');
        $background = self::colour('brand_background_color');
        $accentInk = self::ink($accent);

        $light = ':root{'
            . "--accent:$accent;--accent-ink:$accentInk;"
            . "--header-bg:$header;--header-ink:" . self::ink($header) . ';'
            . "--bg:$background;}";

        $dark = "--accent:$accent;--accent-ink:$accentInk;";
        if ($header !== self::DEFAULTS['brand_header_color']) {
            $dark .= "--header-bg:$header;--header-ink:" . self::ink($header) . ';';
        }

        return $light . 'html[data-theme="dark"]{' . $dark . '}';
    }
}
