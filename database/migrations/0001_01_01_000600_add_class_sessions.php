<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Anmelde-Sitzungen: Kinder einer Klasse können sich nur anmelden, solange eine läuft
        // Sign-in sessions: children of a class can only sign in while one is running
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 200);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            // 6 Ziffern, verschlüsselt — Kinder brauchen ihn zusätzlich zum Passwort
            // 6 digits, encrypted — children need it in addition to their password
            $table->text('code')->nullable();
            $table->timestamp('closed_at')->nullable();   // vorzeitig beendet / ended early
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('close_reason', 200)->nullable();
            $table->timestamps();
            $table->index(['school_class_id', 'ends_at']);
        });

        // Wer hat sich in welcher Sitzung angemeldet?
        // Who signed in to which session?
        Schema::create('class_session_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['class_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_logins');
        Schema::dropIfExists('class_sessions');
    }
};
