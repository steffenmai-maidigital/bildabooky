<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zwei-Faktor: Ausnahme je Benutzer (null = nach Gruppe), Standard 09
 *
 * Two-factor: per-user exception (null = by group), Standard 09
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('mfa_policy', 10)->nullable());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('mfa_policy'));
    }
};
