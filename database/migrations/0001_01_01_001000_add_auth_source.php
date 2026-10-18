<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anmeldung über das Schulverzeichnis (Standard 09): auth_source
 * unterscheidet eigene Konten (local, Passwort in BilDaBooky) von
 * Schulkonten (ldap, Passwort im Verzeichnis). Kinder sind immer local.
 *
 * Sign-in via the school directory (Standard 09): auth_source
 * distinguishes own (local) accounts (local, password in BilDaBooky) from
 * school accounts (ldap, password in the directory). Children are always local.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_source', 10)->default('local');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('auth_source'));
    }
};
