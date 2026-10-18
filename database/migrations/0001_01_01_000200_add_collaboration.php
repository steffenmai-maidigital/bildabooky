<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // eigen = eigenes Buch, klasse = Klassenbuch (alle schreiben mit),
            // vorlage = jedes Kind bekommt eine eigene Version
            // eigen = own book, klasse = class book (everyone writes in it),
            // vorlage = each child gets their own version
            $table->string('kind')->default('eigen');
            $table->foreignId('template_id')->nullable()->constrained('books')->nullOnDelete();
            // Version einer Vorlage „abgegeben“
            // version of a template “handed in”
            $table->timestamp('submitted_at')->nullable();
            // Teilen: privat = nur ich, lehrkraft = auch die Lehrkraft,
            // klasse = die ganze Klasse (Klassenbibliothek, inkl. Lehrkraft)
            // Sharing: privat = only me, lehrkraft = the teacher as well,
            // klasse = the whole class (class library, incl. teacher)
            $table->string('freigabe')->default('privat');
        });
        DB::table('books')->where('shared', true)->update(['freigabe' => 'klasse']);
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('shared'));

        Schema::table('pages', function (Blueprint $table) {
            // steigt mit jedem Speichern — erkennt gleichzeitiges Bearbeiten
            // increases with every save — detects simultaneous editing
            $table->unsignedInteger('version')->default(1);
        });

        // Änderungsprotokoll je Buch (nur Lehrkraft und Admin sehen es)
        // Change log per book (only teacher and admin see it)
        Schema::create('book_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // ohne Fremdschlüssel: Seite kann später gelöscht sein
            // no foreign key: the page may be deleted later
            $table->unsignedBigInteger('page_id')->nullable();
            // Seitennummer zum Zeitpunkt der Änderung
            // page number at the time of the change
            $table->unsignedInteger('seite')->nullable();
            $table->string('aktion');
            $table->text('details')->nullable();                 // JSON
            $table->timestamps();
            $table->index(['book_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_logs');
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn('version'));
        Schema::table('books', function (Blueprint $table) {
            $table->boolean('shared')->default(false);
        });
        DB::table('books')->where('freigabe', 'klasse')->update(['shared' => true]);
        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn(['kind', 'submitted_at', 'freigabe']);
        });
    }
};
