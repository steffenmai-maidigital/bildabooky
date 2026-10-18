<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Einheitliche Schlüssel für das Erscheinungsbild (Standard 03):
 * die bisherigen deutschen Einstellungsnamen werden umbenannt.
 * Bestehende Werte bleiben erhalten.
 *
 * Uniform keys for the appearance (Standard 03):
 * the previous German settings names are renamed.
 * Existing values are kept.
 */
return new class extends Migration
{
    private const NEU = [
        'schule_name'  => 'brand_company_name',
        'logo_pfad'    => 'brand_logo_path',
        'logo_version' => 'brand_logo_version',
        'fusszeile'    => 'brand_footer_text',
        'farbe_akzent' => 'brand_accent_color',
        'farbe_kopf'   => 'brand_header_color',
        'farbe_hinten' => 'brand_background_color',
    ];

    public function up(): void
    {
        foreach (self::NEU as $alt => $neu) {
            $this->umbenennen($alt, $neu);
        }
    }

    public function down(): void
    {
        foreach (self::NEU as $alt => $neu) {
            $this->umbenennen($neu, $alt);
        }
    }

    private function umbenennen(string $von, string $nach): void
    {
        $wert = DB::table('settings')->where('key', $von)->value('value');
        if ($wert === null || DB::table('settings')->where('key', $nach)->exists()) {
            return;
        }
        DB::table('settings')->insert(['key' => $nach, 'value' => $wert, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('settings')->where('key', $von)->delete();
    }
};
