<?php

namespace App\Support;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Eine Klasse vollständig löschen: ihre Kinder samt Büchern, Fotos, Tonaufnahmen
 * und Protokolleinträgen, dazu Klassenbücher und Aufgaben der Klasse.
 * Lehrkräfte bleiben; ihre eigenen Bücher verlieren nur die Klassenzuordnung.
 * Vorher wird eine Sicherung angelegt.
 *
 * Delete a class completely: its children with their books, photos, sound recordings
 * and log entries, plus the class books and assignments of the class.
 * Teachers remain; their own books only lose the class assignment.
 * A backup is created beforehand.
 */
class ClassDeletion
{
    /** Was würde gelöscht? / What would be deleted? */
    public static function preview(SchoolClass $class): array
    {
        $pupils = $class->pupils()->pluck('id');
        return [
            'pupils' => $pupils->count(),
            'books'  => self::books($class, $pupils)->count(),
        ];
    }

    public static function run(SchoolClass $class): array
    {
        // 1. Sicherung vorher — schlägt sie fehl, wird nichts gelöscht
        // 1. Backup first — if it fails, nothing is deleted
        try {
            $backup = basename(Backup::create(config('bildabooky.backup.path'), 'before-class-' . \Illuminate\Support\Str::slug($class->name)));
        } catch (Throwable $e) {
            Log::error('Klasse löschen: Sicherung fehlgeschlagen, nichts gelöscht: ' . $e->getMessage());
            throw $e;
        }

        $pupils = $class->pupils()->pluck('id');
        $counts = self::preview($class) + ['backup' => $backup];

        // 2. Bücher samt Mediendateien (Seiten, Protokolle, Medien-Einträge fallen per Fremdschlüssel mit)
        // 2. Books including media files (pages, logs, media entries go with them via foreign key)
        self::books($class, $pupils)->get()->each->deleteWithMedia();

        DB::transaction(function () use ($class, $pupils) {
            // 3. Spuren der Kinder in übrigen Büchern, 4. Konten, 5. die Klasse
            //    (Anmelde-Sitzungen, Fächer- und Lehrkraft-Zuordnungen fallen per Fremdschlüssel mit)
            // 3. Traces of the children in other books, 4. accounts, 5. the class
            //    (sign-in sessions, subject and teacher assignments go with them via foreign key)
            BookLog::whereIn('user_id', $pupils)->delete();
            User::whereIn('id', $pupils)->delete();
            $class->delete();
        });

        return $counts;
    }

    private static function books(SchoolClass $class, $pupils)
    {
        return Book::where(fn ($q) => $q->whereIn('user_id', $pupils)
            ->orWhere(fn ($k) => $k->where('school_class_id', $class->id)->whereIn('kind', [Book::KIND_CLASS, Book::KIND_TEMPLATE])));
    }
}
