<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Fächer (legt der Admin an)
        // Subjects (created by the admin)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('symbol', 16)->default('📘');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Welche Fächer hat eine Klasse?
        // Which subjects does a class have?
        Schema::create('class_subject', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->primary(['school_class_id', 'subject_id']);
        });

        // Wer unterrichtet welches Fach in welcher Klasse? (mehrere Lehrkräfte je Fach möglich)
        // Who teaches which subject in which class? (several teachers per subject possible)
        Schema::create('class_subject_teacher', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['school_class_id', 'subject_id', 'user_id']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
        });
        Schema::dropIfExists('class_subject_teacher');
        Schema::dropIfExists('class_subject');
        Schema::dropIfExists('subjects');
    }
};
