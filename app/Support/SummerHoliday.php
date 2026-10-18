<?php

namespace App\Support;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\Media;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sommerferienmodus: Am eingestellten Tag werden ALLE Schülerkonten
 * (Rolle Ersteller) mit ihren Büchern, Fotos, Tonaufnahmen und
 * Protokolleinträgen endgültig gelöscht — für einen sauberen Start ins
 * neue Schuljahr und damit keine Kinderdaten länger als nötig liegen.
 *
 * Unmittelbar vorher wird eine Sicherung angelegt (wird nach
 * BACKUP_KEEP_DAYS wie jede Sicherung automatisch entfernt).
 *
 * Summer holiday mode: on the configured day ALL pupil accounts
 * (role pupil) with their books, photos, sound recordings and
 * log entries are permanently deleted — for a clean start into the
 * new school year and so that no children's data is kept longer than necessary.
 *
 * Immediately beforehand a backup is created (removed automatically after
 * BACKUP_KEEP_DAYS like every backup).
 */
class SummerHoliday
{
    public static function enabled(): bool
    {
        return Setting::get('summer_enabled') === '1' && self::date() !== null;
    }

    public static function date(): ?Carbon
    {
        $d = Setting::get('summer_date');
        return $d ? Carbon::parse($d)->startOfDay() : null;
    }

    public static function options(): array
    {
        return [
            'class_books' => Setting::get('summer_class_books', '1') === '1',
            // neues Schuljahr: Klassen werden standardmäßig mitgelöscht, Lehrkräfte tragen sich neu ein
            // new school year: classes are deleted as well by default, teachers sign up again
            'classes'     => Setting::get('summer_classes', '1') === '1',
            'yearly'      => Setting::get('summer_yearly', '0') === '1',
        ];
    }

    public static function last(): ?array
    {
        $l = Setting::get('summer_last');
        return $l ? json_decode($l, true) : null;
    }

    /** Zeitpunkt der Durchführung: am gewählten Tag um 3 Uhr nachts / Time of execution: on the chosen day at 3 am */
    public static function runAt(): ?Carbon
    {
        return self::date()?->copy()->setTime(3, 0);
    }

    /**
     * Ist die Löschung dran (Zeitpunkt erreicht) und für diesen Termin noch nicht gelaufen?
     *
     * Is the deletion due (time reached) and not yet run for this date?
     */
    public static function isDue(): bool
    {
        if (! self::enabled() || now()->lt(self::runAt())) {
            return false;
        }
        return (self::last()['for'] ?? null) !== self::date()->toDateString();
    }

    /** Hinweis-Banner: in den letzten 21 Tagen vor dem Termin / Notice banner: in the last 21 days before the date */
    public static function isSoon(): bool
    {
        if (! self::enabled()) {
            return false;
        }
        $days = now()->startOfDay()->diffInDays(self::date(), false);
        return $days >= 0 && $days <= 21;
    }

    /** Was würde jetzt gelöscht? / What would be deleted now? */
    public static function preview(?array $options = null): array
    {
        $o = $options ?? self::options();
        $pupils = User::where('role', User::PUPIL)->pluck('id');
        $books = Book::whereIn('user_id', $pupils);
        // ohne Klasse keine Klassenbücher/Aufgaben
        // without a class, no class books/assignments
        if ($o['class_books'] || $o['classes']) {
            $books->orWhereIn('kind', [Book::KIND_CLASS, Book::KIND_TEMPLATE]);
        }
        $bookIds = $books->pluck('id');
        return [
            'pupils'  => $pupils->count(),
            'books'   => $bookIds->count(),
            'media'   => Media::whereIn('book_id', $bookIds)->count(),
            'classes' => $o['classes'] ? SchoolClass::count() : 0,
        ];
    }

    /** Löschung durchführen. Gibt die Zahlen zurück. / Carry out the deletion. Returns the counts. */
    public static function run(string $trigger = 'schedule'): array
    {
        $o = self::options();
        $for = self::date()?->toDateString() ?? now()->toDateString();

        // 1. Sicherung vorher — falls doch etwas gebraucht wird
        // 1. Backup first — in case something is needed after all
        $backup = null;
        try {
            $backup = basename(Backup::create(config('bildabooky.backup.path'), 'before-summer-holiday'));
        } catch (Throwable $e) {
            Log::error('Sommerferienmodus: Sicherung fehlgeschlagen, Löschung abgebrochen: ' . $e->getMessage());
            throw $e;
        }

        $counts = self::preview($o);
        $pupils = User::where('role', User::PUPIL)->pluck('id');

        // 2. Bücher samt Mediendateien (Protokolle, Seiten, Medien-Einträge fallen per Fremdschlüssel mit)
        // 2. Books including media files (logs, pages, media entries go with them via foreign key)
        $books = Book::whereIn('user_id', $pupils);
        // ohne Klasse keine Klassenbücher/Aufgaben
        // without a class, no class books/assignments
        if ($o['class_books'] || $o['classes']) {
            $books->orWhereIn('kind', [Book::KIND_CLASS, Book::KIND_TEMPLATE]);
        }
        $books->get()->each->deleteWithMedia();

        DB::transaction(function () use ($pupils, $o) {
            // 3. Spuren der Kinder in übrigen Büchern (Protokolleinträge)
            // 3. Traces of the children in other books (log entries)
            BookLog::whereIn('user_id', $pupils)->delete();
            // 4. Konten
            // 4. Accounts
            User::whereIn('id', $pupils)->delete();
            // 5. auf Wunsch die Klassen selbst
            // 5. the classes themselves, if requested
            if ($o['classes']) {
                SchoolClass::query()->delete();
            }
        });
        Cache::flush();   // Anwesenheit im Klassenbuch u. Ä. / presence in the class book and the like

        $report = $counts + [
            'for'     => $for,
            'at'      => now()->format('Y-m-d H:i'),
            'trigger' => $trigger,
            'backup'  => $backup,
        ];
        Setting::put('summer_last', json_encode($report));

        // 6. nächster Termin: jährlich wiederholen oder Modus ausschalten
        // 6. next date: repeat yearly or switch the mode off
        if ($o['yearly']) {
            Setting::put('summer_date', Carbon::parse($for)->addYear()->toDateString());
        } else {
            Setting::put('summer_enabled', '0');
        }

        Log::info('Sommerferienmodus ausgeführt', $report);
        return $report;
    }

    /**
     * Rückfallebene ohne Zeitplan-Container: höchstens einmal pro Stunde nach
     * einer Anfrage prüfen und ggf. ausführen.
     *
     * Fallback without a scheduler container: check at most once an hour after
     * a request and run if due.
     */
    public static function checkInBackground(): void
    {
        try {
            if (Cache::add('summer-holiday:checked', 1, 3600) && self::isDue()) {
                self::run('automatic');
            }
        } catch (Throwable $e) {
            Log::error('Sommerferienmodus: ' . $e->getMessage());
        }
    }
}
