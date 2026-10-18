<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Verlängerungen einer Anmelde-Sitzung (dokumentiert: wer, wann, von → bis)
        // Extensions of a sign-in session (documented: who, when, from → to)
        Schema::create('class_session_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('alt_ende');
            $table->timestamp('neu_ende');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_extensions');
    }
};
