<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konto aktiv (Standard 09: „active = false sperrt“): Admins sperren Konten von
 * Lehrkräften und Admins, ohne sie zu löschen. Gesperrte Konten kommen nicht
 * mehr hinein, eine laufende Sitzung endet bei der nächsten Anfrage. Beim
 * Upgrade sind alle bestehenden Konten aktiv.
 *
 * Account active (standard 09: "active = false locks"): admins lock accounts of
 * teachers and admins without deleting them. Locked accounts no longer get
 * in, a running session ends with the next request. On upgrade all existing
 * accounts are active.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('active')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};
