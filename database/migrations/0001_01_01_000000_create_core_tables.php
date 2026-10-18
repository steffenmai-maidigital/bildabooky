<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Klassen
        // Classes
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->default('');
            $table->string('username')->unique();
            $table->string('password');                   // bcrypt-Hash / bcrypt hash
            $table->string('role')->default('ersteller'); // admin | leiter | ersteller
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->boolean('must_change_password')->default(false);
            // Nur wenn „Anmeldekarte“ gewählt: Passwort zusätzlich mit APP_KEY
            // verschlüsselt, damit die Karte jederzeit neu gedruckt werden kann
            // Only if “login card” is chosen: password additionally encrypted with APP_KEY
            // so that the card can be reprinted at any time
            $table->text('card_password')->nullable();
            // Zwei-Faktor (TOTP) — Geheimnis und Codes mit APP_KEY verschlüsselt
            // Two-factor (TOTP) — secret and codes encrypted with APP_KEY
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->unsignedBigInteger('two_factor_last_step')->nullable();
            $table->string('locale', 5)->nullable();      // gewählte Sprache / chosen language
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Leiter ↔ geleitete Klassen (eine Lehrkraft kann mehrere haben)
        // Class lead ↔ led classes (a teacher can have several)
        Schema::create('class_leader', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['school_class_id', 'user_id']);
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('title');
            $table->string('format')->default('hoch');    // hoch | quadrat | quer / portrait | square | landscape
            $table->boolean('shared')->default(false);    // in der Klassenbibliothek sichtbar / shown in class library
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('background', 20)->default('#ffffff');
            $table->longText('elements')->nullable();     // JSON
            $table->timestamps();
            $table->index(['book_id', 'position']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind');                       // bild | ton / image | sound
            $table->string('path');
            $table->string('mime');
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['media', 'pages', 'books', 'class_leader', 'users', 'school_classes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
