<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Fuchs-Helfer: null = Standard (an), false = selbst ausgeschaltet
            // Fox helper: null = default (on), false = switched off by the user
            $table->boolean('fuchs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('fuchs'));
    }
};
