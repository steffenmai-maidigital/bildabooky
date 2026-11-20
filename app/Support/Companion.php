<?php

namespace App\Support;

use App\Models\Book;
use App\Models\Setting;
use App\Models\User;

/**
 * Die Begleiterin unten rechts: Cara, eine schwarze Katze (Figur und Ton:
 * docs/CARA.md). Sie schläft, bis man sie antippt, und hat keine Meinung,
 * nur Fakten. Von sich aus spricht sie nur als Antwort auf eine Handlung:
 * nach dem Speichern (Anlass aus CompanionOccasion), bei einer
 * zurückgegebenen Fehlermeldung und im Buch nach Aktionen
 * (companion-book.js). Nichts löst sich nach Zeit aus. Sie duzt, spricht
 * kurz und sachlich; Ernstes (Passwort, Zwei-Faktor, Datenschutz, Löschen,
 * Sommerferienmodus) klar und ohne Witz. Alle Texte laufen durch __().
 * Die Daten gehen als JSON an companion.js; der Name „Companion“ ist
 * neutral, damit ein späterer Figurenwechsel keine Umbenennung braucht.
 *
 * The companion in the bottom right: Cara, a black cat (character and tone:
 * docs/CARA.md). She sleeps until tapped and has no opinions, only facts.
 * She only speaks on her own in direct reply to an action: after saving
 * (occasion from CompanionOccasion), on a returned error message and inside
 * a book after actions (companion-book.js). Nothing is triggered by time.
 * She uses "du", speaks briefly and factually; serious matters (password,
 * two-factor, privacy, deleting, summer holiday mode) clearly and without
 * jokes. All texts go through __(). The data goes to companion.js as JSON;
 * the neutral name "Companion" means a later change of character needs no
 * renaming.
 */
class Companion
{
    /**
     * Zentral eingeschaltet? (Einstellungen → 7) / Switched on centrally? (settings → 7)
     */
    public static function enabled(): bool
    {
        return Setting::get('companion_enabled', '1') === '1';
    }

    /**
     * Anmeldeseite (noch niemand angemeldet): Fakten zu freier Software und
     * Hilfe beim Anmelden, nur nach Antippen.
     *
     * Sign-in page (nobody signed in yet): facts about free software and
     * help with signing in, only after a tap.
     */
    public static function guest(): array
    {
        // Quellcode wie im Footer (Standard 03): SOURCE_URL, sonst das Projekt auf GitHub
        // Source code as in the footer (standard 03): SOURCE_URL, otherwise the project on GitHub
        $source = config('bildabooky.source_url') ?: config('bildabooky.github');

        return self::package('login', null, __('Hallo! Ich bin Cara. Tipp mich nochmal an, dann erzähle ich dir etwas über BilDaBooky.'), [
            __('BilDaBooky ist freie Software. Jede Schule darf es kostenlos nutzen.'),
            __('Bücher, Fotos und Tonaufnahmen liegen auf dem Server eurer Schule. BilDaBooky schickt nichts an fremde Dienste.'),
            __('Der Quellcode ist offen. Jeder kann nachlesen, was mit den Daten passiert.'),
            __('BilDaBooky zeigt keine Werbung und kostet keine Lizenzgebühr.'),
            __('Die Lizenz heißt AGPL. Schulen dürfen BilDaBooky ändern und weitergeben.'),
            __('BilDaBooky läuft auf dem Server eurer Schule. Kein Anbieter kann es abschalten.'),
            __('Fotos werden schon im Browser verkleinert. Dabei fallen Standortdaten weg.'),
            __('BilDaBooky gibt es auf Deutsch, Englisch, Französisch und Spanisch.'),
            __('Benutzername und Passwort bekommst du von deiner Lehrkraft.'),
            __('Passwort vergessen? Deine Lehrkraft gibt dir ein neues.'),
            self::about(),
        ], self::errorLine(), null, $source ? ['text' => __('Zum Quellcode'), 'url' => $source] : null);
    }

    /**
     * Daten für angemeldete Personen. $praised: Die Seite trägt eine
     * Erfolgsmeldung (session 'ok'). $mode: page, editor oder reading.
     *
     * Data for signed-in people. $praised: the page carries a success
     * message (session 'ok'). $mode: page, editor or reading.
     */
    public static function data(User $user, ?string $route, bool $praised, ?Book $book = null, string $mode = 'page'): array
    {
        // Kam die Seite mit einer Fehlermeldung zurück, geht das vor dem Lob. Editor und
        // Leseansicht zeigen keine Erfolgsmeldung oben – dort sagt Cara die Meldung selbst.
        // If the page came back with an error message, that comes before praise. Editor and
        // reader show no success message at the top – there Cara says the message herself.
        $reply = self::errorLine() ?? ($praised ? self::praise($book !== null) : null);

        if ($book && $mode === 'reading') {
            return self::package('reading', $book, __('Blättere mit ›, mit den Pfeiltasten oder wisch zur Seite.'), self::readingTips($user, $book), $reply, [
                'page_audio' => __('Auf dieser Seite gibt es etwas zu hören. Tippe auf den runden Lautsprecher-Knopf.'),
                'end'        => __('Das war die letzte Seite. Mit ‹ blätterst du zurück.'),
            ]);
        }
        if ($book) {
            return self::package('editor', $book, self::editorStart($user, $book), self::editorTips(), $reply, [
                'select_text'    => __('Tippe den Text nochmal an, dann schreibst du. Größe, Farbe und Schrift wählst du in der Leiste.'),
                'select_image'   => __('Mit dem runden Griff unten rechts änderst du die Größe, mit ↻ drehst du das Bild.'),
                'select_sticker' => __('Sticker kannst du größer machen, drehen und doppeln.'),
                'select_shape'   => __('Mit „Ganz nach hinten“ legst du die Form hinter einen Text.'),
                'select_audio'   => __('Tippe nochmal auf den Lautsprecher, dann hörst du die Aufnahme.'),
                'new_text'       => __('Jetzt kannst du schreiben. Bist du fertig, tippe daneben.'),
                'paint'          => __('Unten wählst du Farbe und Dicke. Mit 🧽 radierst du, mit ✓ Fertig kommt das Bild ins Buch.'),
                'photo'          => __('Das Foto ist drin. Mit dem runden Griff machst du es größer oder kleiner.'),
                'audio_done'     => __('Aufgenommen. Tippe auf den Lautsprecher, dann hörst du dich.'),
                'new_page'       => __('Neue Seite. Unten wechselst du zwischen den Seiten.'),
                'undo'           => __('Rückgängig gemacht.'),
                'deleted'        => __('Gelöscht. Mit ↶ holst du es zurück.'),
                'many_pages'     => __('Schon :n Seiten.'),
                'submitted'      => __('Abgegeben. Deine Lehrkraft sieht es jetzt.'),
                // gewöhnliche Tipps, je nach Gerät einer davon (companion-book.js)
                // ordinary tips, one of them depending on the device (companion-book.js)
                'explain_touch'  => __('Halte einen Knopf gedrückt oder tippe oben auf ❓. Dann siehst du, was er macht.'),
                'explain_mouse'  => __('Zeig mit der Maus auf einen Knopf oder klicke oben auf ❓. Dann siehst du, was er macht.'),
            ]);
        }

        $context = self::context($user, $route);

        return self::package('page', null, $context ?? self::greeting($user), self::pageTips($user), $reply);
    }

    /**
     * Gemeinsamer Aufbau der Daten für companion.js. Es gibt bewusst keinen
     * Text, der nach Zeit kommt: first und tips nur nach Antippen, reply nur
     * als Antwort auf die eben abgeschickte Handlung, events nur nach
     * Aktionen im Buch.
     *
     * Common structure of the data for companion.js. On purpose there is no
     * text that comes after a time: first and tips only after a tap, reply
     * only in answer to the action just submitted, events only after actions
     * in a book.
     */
    private static function package(string $mode, ?Book $book, string $first, array $tips, ?string $reply, ?array $events = null, ?array $link = null): array
    {
        return [
            'mode'   => $mode,
            'book'   => $book?->id,
            'first'  => $first,
            'tips'   => array_values($tips),
            'reply'  => $reply,
            'events' => $events,
            'link'   => $link,
            'texts'  => [
                'close'  => __('Schließen'),
                'sleep'  => __('Schlafen legen'),
                'next'   => __('Noch ein Tipp'),
                'awake'  => __('Cara: antippen für einen Tipp'),
                'asleep' => __('Cara schläft. Antippen weckt sie.'),
            ],
        ];
    }

    /** Wer sie ist (ein Fakt) / who she is (a fact) */
    private static function about(): string
    {
        return __('Ich bin Cara. Mein Name ist irisch und heißt Freundin.');
    }

    /** Begrüßung beim ersten Antippen / greeting on the first tap */
    private static function greeting(User $user): string
    {
        $name = $user->isPupil() ? $user->first_name : $user->name;
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 11 => __('Guten Morgen, :name! Ich bin Cara. Tipp mich nochmal an, dann bekommst du einen Tipp.', ['name' => $name]),
            $hour < 18 => __('Hallo, :name! Ich bin Cara. Tipp mich nochmal an, dann bekommst du einen Tipp.', ['name' => $name]),
            default    => __('Guten Abend, :name! Ich bin Cara. Tipp mich nochmal an, dann bekommst du einen Tipp.', ['name' => $name]),
        };
    }

    /**
     * Das Passende zur Seite – beim ersten Antippen statt der Begrüßung
     *
     * What fits the page – on the first tap instead of the greeting
     */
    private static function context(User $user, ?string $route): ?string
    {
        if (SummerHoliday::isSoon() && ($date = SummerHoliday::date())) {
            return $user->isPupil()
                ? __('Am :datum werden die Schülerkonten mit allen Büchern gelöscht. Nimm deine Bücher vorher mit: beim Buch auf „⋯“, dann „Als Datei mitnehmen“.', ['datum' => $date->format('d.m.Y')])
                : __('Am :datum löscht der Sommerferienmodus alle Schülerkonten mit ihren Büchern.', ['datum' => $date->format('d.m.Y')]);
        }
        if ($route === 'home' && $user->isPupil() && ! $user->books()->exists()) {
            return __('Tippe auf „Neues Buch“. Dann gibst du deinem Buch einen Namen und wählst eine Form.');
        }

        return match ($route) {
            'users.index'      => __('Hast du eine Anmeldekarte hinterlegt, druckst du sie hier neu: Klasse wählen, „Filtern“, dann „Karten der Klasse“.'),
            'classes.index'    => $user->teaches() ? __('Bei deinen Klassen führt „Bücher“ direkt zu den Büchern der Klasse.') : null,
            'classes.sessions' => __('Die Kinder geben den Sitzungscode nach ihrem Passwort ein. Jede Lehrkraft der Klasse kann die Sitzung verlängern oder beenden.'),
            'books.versions'   => __('Mit „Zurückgeben“ schickst du einem Kind sein Buch mit einer Nachricht zurück.'),
            'books.log'        => __('Hier steht, wer wann was an diesem Buch geändert hat. Das sehen nur Lehrkräfte und Admins.'),
            'settings'         => __('Die Farbvorschau zeigt sofort, wie eure Farben aussehen.'),
            'account'          => match (true) {
                $user->isPupil()                                   => __('Dein Passwort kennst nur du. Gib es niemandem weiter.'),
                $user->hasTwoFactor()                              => __('Die Zwei-Faktor-Anmeldung ist eingeschaltet. Bewahre die Wiederherstellungscodes sicher auf.'),
                $user->twoFactorAllowed()                          => __('Mit der Zwei-Faktor-Anmeldung braucht dein Konto zusätzlich einen Code vom Handy. Hier richtest du sie ein.'),
                default                                            => null,
            },
            'help'             => __('Hier findest du die Kurzanleitung und die Handbücher. Tipp mich nochmal an, dann bekommst du einen Tipp.'),
            default            => null,
        };
    }

    /** Tipps auf gewöhnlichen Seiten je Rolle / tips on ordinary pages per role */
    private static function pageTips(User $user): array
    {
        if ($user->isPupil()) {
            return [
                __('Tippe einen Text zweimal an, dann kannst du ihn ändern.'),
                __('Mit dem runden Griff unten rechts machst du ein Teil größer oder kleiner.'),
                __('Mit ↻ über einem Teil drehst du es.'),
                __('Unter „Ton“ nimmst du deine Stimme auf. Im Buch kann man sie dann anhören.'),
                __('Mit „Kamera“ machst du direkt ein Foto für dein Buch.'),
                __('Mit „Seite“ gibst du deiner Seite eine Farbe.'),
                __('Unter deinem Buch tippst du bei „Teilen“ auf „Klasse“. Dann kann deine Klasse es lesen.'),
                __('Über „⋯“ beim Buch druckst du es oder nimmst es als Datei mit nach Hause.'),
                __('Mit „Lesen“ blätterst du durch dein Buch und hörst die Töne.'),
                self::about(),
            ];
        }
        $tips = [
            __('Mit einer Vorlage legt jedes Kind seine eigene Version an. Alle Versionen findest du beim Buch unter „⋯“ und „Versionen der Kinder“.'),
            __('In einem Klassenbuch schreibt die ganze Klasse gleichzeitig.'),
            __('Unter „Benutzer“ und „Schüler anlegen“ legst du eine ganze Klassenliste auf einmal an.'),
            __('Das Änderungsprotokoll zeigt, wer wann was an einem Buch geändert hat. Du findest es beim Buch unter „⋯“.'),
            __('Die Bereiche der Bibliothek lassen sich einklappen. Tippe dazu auf die Überschrift.'),
            __('Über „⋯“ und „Als Datei mitnehmen“ wird ein Buch zu einer Datei. Sie öffnet sich in jedem Browser, auch ohne Internet.'),
        ];
        if (SessionRequirement::required()) {
            $tips[] = __('Unter „Klassen“ schaltest du die Anmeldung deiner Klasse frei. Die Kinder brauchen dann den Sitzungscode.');
        }
        if ($user->isAdmin()) {
            $tips[] = __('Unter „Einstellungen“ trägst du Schulname, Domain, Farben und Logo ein.');
            $tips[] = __('Der Sommerferienmodus löscht zum Schuljahresende die Schülerkonten mit ihren Büchern. Vorher legt er eine Sicherung an.');
            if ($user->twoFactorAllowed() && ! $user->hasTwoFactor()) {
                $tips[] = __('Für Admins ist die Zwei-Faktor-Anmeldung empfohlen. Du richtest sie unter „Konto“ ein.');
            }
        }
        $tips[] = self::about();

        return $tips;
    }

    /** Einstieg im Editor beim ersten Antippen / introduction in the editor on the first tap */
    private static function editorStart(User $user, Book $book): string
    {
        return match (true) {
            $book->isClassBook()                                                     => __('Das ist ein Klassenbuch. Hier schreiben alle zusammen. Oben siehst du, wer gerade dabei ist.'),
            $book->isVersion() && $book->user_id == $user->id && $book->isReturned() => __('Deine Lehrkraft hat dein Buch zurückgegeben. Oben steht der Hinweis dazu.'),
            $book->isVersion() && $book->user_id == $user->id                        => __('Das ist deine eigene Version. Bist du fertig, tippe oben auf „Abgeben“.'),
            $book->isTemplate()                                                      => __('Du bearbeitest eine Vorlage. Kinder, die schon angefangen haben, behalten ihre Version.'),
            $book->user_id != $user->id                                              => __('Du hilfst bei :name. Deine Änderungen stehen im Änderungsprotokoll.', ['name' => $book->owner?->first_name]),
            default                                                                  => __('In der Werkzeugleiste findest du alles für dein Buch: Text, Foto, Kamera, Malen, Ton, Sticker, Form und Seite.'),
        };
    }

    /** Tipps im Editor (für alle Rollen gleich) / tips in the editor (the same for every role) */
    private static function editorTips(): array
    {
        return [
            __('Tippe einen Text zweimal an, dann kannst du ihn ändern.'),
            __('Mit dem runden Griff unten rechts machst du ein Teil größer oder kleiner.'),
            __('Mit ↻ über einem Teil drehst du es.'),
            __('Unter „Ton“ nimmst du deine Stimme auf. Im Buch kann man sie dann anhören.'),
            __('Mit „Kamera“ machst du direkt ein Foto für dein Buch.'),
            __('Mit „Seite“ gibst du deiner Seite eine Farbe.'),
            __('Vertippt? Oben mit ↶ machst du den letzten Schritt rückgängig.'),
            __('Gespeichert wird von selbst. Oben steht dann „Gespeichert“.'),
            __('Mit „Lesen“ oben siehst du dein Buch so, wie andere es lesen.'),
        ];
    }

    /** Tipps in der Leseansicht / tips in the reading view */
    private static function readingTips(User $user, Book $book): array
    {
        $tips = [
            __('Mit ⛶ oben siehst du das Buch im Vollbild.'),
            __('Auf Seiten mit einem runden Lautsprecher-Knopf gibt es etwas zu hören.'),
            __('Mit „Bibliothek“ oben links kommst du zurück.'),
        ];
        if ($book->editableBy($user)) {
            $tips[] = __('Mit „Bearbeiten“ oben änderst du das Buch.');
        }
        $tips[] = self::about();

        return $tips;
    }

    /**
     * Antwort auf eine erfolgreiche Aktion: benennt, was geschehen ist.
     * Nach Ernstem (Löschen, Passwort …) schlicht „Erledigt.“, nach Speichern
     * „Gespeichert.“ – siehe CompanionOccasion. Im Editor und in der
     * Leseansicht (keine Meldung oben) die Erfolgsmeldung selbst.
     *
     * Reply to a successful action: names what happened. After serious
     * actions (deleting, password …) plainly "Done.", after saving "Saved." –
     * see CompanionOccasion. In the editor and the reader (no message at the
     * top) the success message itself.
     */
    private static function praise(bool $fullscreen = false): string
    {
        $message = session('ok');
        if ($fullscreen && is_string($message) && $message !== '' && session('companion_occasion') !== 'serious') {
            return $message;
        }

        return match (session('companion_occasion')) {
            'saved' => __('Gespeichert.'),
            'done'  => __('Hat geklappt.'),
            'back'  => __('Da bin ich wieder.'),
            default => __('Erledigt.'),
        };
    }

    /**
     * Ein Satz zu einer zurückgegebenen Fehlermeldung. Auf Anmelde-, Konto-,
     * Passwort- und Zwei-Faktor-Seiten der ernste Satz.
     *
     * One line about a returned error message. On sign-in, account, password
     * and two-factor pages the serious line.
     */
    private static function errorLine(): ?string
    {
        $errors = session('errors');
        if (! $errors || ! $errors->any()) {
            return null;
        }
        if (request()->routeIs('login', 'login.*', 'account', 'account.*', 'users.password', 'users.2fa')) {
            return __('Das hat nicht gepasst. Prüf deine Angaben und versuch es noch einmal.');
        }

        return __('Das hat nicht geklappt. Oben steht, was fehlt.');
    }
}
