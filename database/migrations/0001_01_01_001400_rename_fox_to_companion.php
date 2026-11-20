<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aus dem Fuchs-Helfer wird die Begleiterin (Cara, seit 20.11.2026): Die
 * Spalte users.fox heißt jetzt users.companion, die Einstellung fox_enabled
 * heißt companion_enabled. Die Werte bleiben, wie sie sind: Wer den Fuchs
 * für sich ausgeschaltet hatte, hat Cara aus; war er zentral aus, bleibt
 * Cara zentral aus. Idempotent — läuft gefahrlos auf einer schon
 * umgestellten Datenbank und auf einer alten, zurückgespielten Sicherung.
 * Der neutrale Name „companion“ braucht bei einem späteren Figurenwechsel
 * keine weitere Umbenennung.
 *
 * The fox helper becomes the companion (Cara, since 20.11.2026): the column
 * users.fox is now users.companion, the setting fox_enabled is now
 * companion_enabled. The values stay as they are: whoever had switched the
 * fox off for themselves has Cara off; if it was off centrally, Cara stays
 * off centrally. Idempotent — runs safely on a database that is already
 * converted and on an old restored backup. The neutral name "companion"
 * needs no further renaming if the character changes later.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('fox', 'companion', 'fox_enabled', 'companion_enabled');
    }

    /**
     * Rückweg: alte Namen wiederherstellen, Werte bleiben
     *
     * Way back: restore the old names, values stay
     */
    public function down(): void
    {
        $this->rename('companion', 'fox', 'companion_enabled', 'fox_enabled');
    }

    private function rename(string $fromColumn, string $toColumn, string $fromSetting, string $toSetting): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', $fromColumn) && ! Schema::hasColumn('users', $toColumn)) {
            Schema::table('users', fn (Blueprint $t) => $t->renameColumn($fromColumn, $toColumn));
        }
        if (! Schema::hasTable('settings') || ! DB::table('settings')->where('key', $fromSetting)->exists()) {
            return;
        }
        // Gibt es den neuen Schlüssel schon, gilt er und der alte entfällt
        // If the new key exists already, it wins and the old one is removed
        if (DB::table('settings')->where('key', $toSetting)->exists()) {
            DB::table('settings')->where('key', $fromSetting)->delete();

            return;
        }
        DB::table('settings')->where('key', $fromSetting)->update(['key' => $toSetting]);
    }
};
