<?php

namespace App\Support;

/**
 * Sticker-Grafiken (Twemoji, CC-BY 4.0) unter public/img/emoji.
 * Der Dateiname folgt Twemoji: Codepunkte in hex, FE0F nur in ZWJ-Folgen.
 *
 * Sticker graphics (Twemoji, CC-BY 4.0) under public/img/emoji.
 * The file name follows Twemoji: code points in hex, FE0F only in ZWJ sequences.
 */
class Emoji
{
    public static function code(string $character): string
    {
        $cps = array_map(fn ($z) => dechex(mb_ord($z, 'UTF-8')), mb_str_split($character, 1, 'UTF-8'));
        if (! in_array('200d', $cps, true)) {
            $cps = array_values(array_filter($cps, fn ($c) => $c !== 'fe0f'));
        }
        return implode('-', $cps);
    }

    /** Pfad der Grafik oder null, wenn es keine gibt. / Path of the graphic, or null if there is none. */
    public static function path(string $character): ?string
    {
        $code = self::code($character);
        if (! preg_match('/^[0-9a-f]+(-[0-9a-f]+)*$/', $code)) {
            return null;
        }
        $path = public_path('img/emoji/' . $code . '.svg');
        return is_file($path) ? $path : null;
    }

    /**
     * { code: data-URI } für alle Sticker der Seiten — für die Datei zum Mitnehmen.
     *
     * { code: data-URI } for all stickers on the pages — for the take-home file.
     */
    public static function embedded(iterable $pages): array
    {
        $out = [];
        foreach ($pages as $page) {
            foreach ($page->elementList() as $el) {
                if (($el['type'] ?? '') !== 'sticker' || ! is_string($el['char'] ?? null)) {
                    continue;
                }
                $code = self::code($el['char']);
                if (! isset($out[$code]) && ($path = self::path($el['char']))) {
                    $out[$code] = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($path));
                }
            }
        }
        return $out;
    }
}
