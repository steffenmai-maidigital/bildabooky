<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seit wann ein Foto, eine Zeichnung oder ein Ton auf keiner Seite mehr steht.
 * Die nächtliche Aufgabe `media:prune` trägt das Datum ein und löscht Datei und
 * Datensatz nach 30 Tagen; steht das Medium wieder auf einer Seite, wird das
 * Datum gelöscht. Beim Upgrade ist das Feld leer: Die Frist beginnt mit dem
 * ersten Lauf.
 *
 * Since when a photo, drawing or sound is no longer on any page. The nightly
 * task `media:prune` enters the date and deletes file and record after 30
 * days; if the medium is placed on a page again, the date is cleared. On
 * upgrade the field is empty: the grace period starts with the first run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('media', 'unused_since')) {
            Schema::table('media', function (Blueprint $table) {
                $table->timestamp('unused_since')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('media', 'unused_since')) {
            Schema::table('media', function (Blueprint $table) {
                $table->dropColumn('unused_since');
            });
        }
    }
};
