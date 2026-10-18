<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unveränderliche Kennung des Schulkontos (AD-objectGUID bzw. OpenLDAP-
 * entryUUID, Standard 09): Benennt das Verzeichnis ein Konto um, bleibt es
 * dasselbe Konto in BilDaBooky (Rolle, Klassen, Bücher, Zwei-Faktor).
 * Eigene Konten haben nie eine guid.
 *
 * Immutable id of the school account (AD objectGUID or OpenLDAP entryUUID,
 * Standard 09): if the directory renames an account, it stays the same
 * account in BilDaBooky (role, classes, books, two-factor).
 * Own (local) accounts never have a guid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'guid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('guid', 64)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'guid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['guid']);
                $table->dropColumn('guid');
            });
        }
    }
};
