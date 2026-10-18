<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kennzeichnung: Klassenlehrkraft (verantwortlich für die Klasse) — mehrere je Klasse möglich
        // Label: form teacher (responsible for the class) — several per class possible
        Schema::table('class_leader', function (Blueprint $table) {
            $table->boolean('klassenlehrkraft')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('class_leader', fn (Blueprint $table) => $table->dropColumn('klassenlehrkraft'));
    }
};
