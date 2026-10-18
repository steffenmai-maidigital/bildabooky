<?php

namespace App\Support;

/**
 * Übersetzungen der aktuellen Sprache für JavaScript (Editor, Leser)
 *
 * Translations of the current language for JavaScript (editor, reader)
 */
class Lang
{
    public static function forJs(): array
    {
        $locale = app()->getLocale();
        $file = lang_path($locale . '.json');
        if ($locale === 'de' || ! is_file($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?: [];
    }
}
