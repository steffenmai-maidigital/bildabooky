<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Lehrkraft gibt eine abgegebene Version zurück (mit optionaler Nachricht ans Kind)
            // Teacher gives a handed-in version back (with an optional message to the child)
            $table->timestamp('returned_at')->nullable();
            $table->string('return_note', 500)->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn(['returned_at', 'return_note', 'returned_by']));
    }
};
