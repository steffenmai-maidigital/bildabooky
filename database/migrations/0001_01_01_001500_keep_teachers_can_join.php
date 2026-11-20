<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * „Lehrkräfte dürfen sich selbst in Klassen eintragen“ ist seit 20.11.2026 ab
 * Werk aus: Sonst kann sich jede Lehrkraft in jede Klasse eintragen und dort
 * Kinder samt Büchern löschen. Bestehende Installationen behalten ihr Verhalten:
 * Wurde der Schalter nie gespeichert und gibt es schon Benutzer, wird hier „an“
 * festgeschrieben. Neue Installationen (noch keine Benutzer) starten mit „aus“.
 *
 * "Teachers may enter themselves into classes" is off by default since
 * 20.11.2026: otherwise any teacher can join any class and delete children
 * with their books there. Existing installations keep their behaviour: if the
 * switch was never saved and users already exist, "on" is written here. New
 * installations (no users yet) start with "off".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('users')) {
            return;
        }
        $saved = DB::table('settings')->where('key', 'teachers_can_join')->exists();
        if (! $saved && DB::table('users')->exists()) {
            DB::table('settings')->insert(['key' => 'teachers_can_join', 'value' => '1', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Rückweg: nichts zu tun – „an“ war bis dahin der Standard
     *
     * Way back: nothing to do – "on" was the default until then
     */
    public function down(): void
    {
    }
};
