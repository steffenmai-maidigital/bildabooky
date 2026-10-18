<?php

namespace App\Support;

use App\Models\User;

/**
 * Rua, der Fuchs-Helfer: ein Wikinger-Fuchs aus einem norwegischen Fjorddorf,
 * den die Iren in Dyflin „Rua“ (der Rote) nannten. Er duzt, spricht kurz,
 * warm und ermutigend — für Grundschulkinder gemacht. Höchstens ein Bild vom
 * Meer pro Hinweis; bei Passwörtern, Zwei-Faktor, Datenschutz, Löschen und
 * Sommerferienmodus ohne Bilder und ohne Witz. Sprüche, Lob und Tipps passen
 * zur Rolle, zur Seite und zur Tageszeit. Alle Texte laufen durch __(),
 * damit Rua jede Sprache der Oberfläche spricht.
 *
 * Rua, the fox helper: a Viking fox from a Norwegian fjord village whom the
 * Irish in Dyflin called "Rua" (the red one). He speaks informally, short,
 * warm and encouraging — made for primary school children. At most one sea
 * image per hint; no images and no jokes for passwords, two-factor, privacy,
 * deleting and summer holiday mode. Sayings, praise and tips suit the role,
 * the page and the time of day. All texts go through __(),
 * so Rua speaks every language of the interface.
 */
class Fox
{
    /**
     * Anmeldeseite (noch niemand angemeldet): Rua stellt sich vor, wirbt für
     * freie Software und hilft beim Anmelden.
     *
     * Sign-in page (nobody signed in yet): Rua introduces himself, promotes
     * free software and helps with signing in.
     */
    public static function guest(): array
    {
        $hour = (int) now()->format('G');
        $promotion = [
            __('Gut zu wissen: BilDaBooky ist freie Software. Jede Schule darf es kostenlos nutzen.'),
            __('Eure Bücher, Fotos und Stimmen bleiben auf dem Server eurer Schule. Keine Cloud, kein Tracking.'),
            __('Offener Quellcode heißt: Jede und jeder kann nachsehen, was mit den Daten passiert. Nichts ist versteckt.'),
            __('Keine Werbung, keine Lizenzkosten, kein Kleingedrucktes. So soll Schule sein.'),
            __('Freie Software gehört allen. Schulen dürfen sie anpassen, verbessern und weitergeben, wie eine gute Geschichte.'),
            __('Kein Anbieter kann BilDaBooky abschalten oder teurer machen. Eure Schule hält das Ruder.'),
            __('Fotos werden schon im Browser verkleinert. Standortdaten bleiben draußen.'),
            __('Freie Software lebt vom Mitmachen. Bring deine Ideen und Verbesserungen mit an Bord!'),
            __('BilDaBooky spricht Deutsch, Englisch, Französisch und Spanisch. Weitere Sprachen kann es dazulernen.'),
        ];
        $source = config('bildabooky.source_url') ?: config('bildabooky.community');

        return [
            'editor'   => null,
            'book'     => null,
            'greeting' => match (true) {
                $hour < 11 => __('Guten Morgen! Ich bin Rua, ein Fuchs aus einem Fjorddorf in Norwegen. Wohin soll die Fahrt gehen?'),
                $hour < 18 => __('Heil dir! Ich bin Rua, ein Fuchs aus einem Fjorddorf in Norwegen. Wohin soll die Fahrt gehen?'),
                default      => __('Guten Abend! Ich bin Rua, ein Fuchs aus einem Fjorddorf in Norwegen. Wohin soll die Fahrt gehen?'),
            },
            'context' => [$promotion[array_rand($promotion)]],
            'tips'    => array_merge($promotion, [
                __('Deinen Benutzernamen und dein Passwort bekommst du von deiner Lehrkraft.'),
                __('Passwort vergessen? Kein Problem. Deine Lehrkraft gibt dir ein neues.'),
                __('Meinen Namen haben mir die Iren gegeben. Rua heißt „der Rote“, wegen meines Fells.'),
            ]),
            'sayings' => [],
            'praise'  => null,
            'link'    => $source ? ['text' => __('Zum Quellcode'), 'url' => $source] : null,
            // Fehler und Warten (fox.js) / errors and waiting (fox.js)
            'error'   => self::errorLine(),
            'waiting' => [__('Die Ruder sind im Wasser …'), __('Einen Moment noch, gleich legen wir an.')],
            'texts'   => [
                'close' => __('Schließen'),
                'sleep' => __('Schlafen legen'),
                'wake'  => __('Rua schläft – antippen zum Wecken'),
                'woken' => __('Gähn … ausgeschlafen! Danke fürs Wecken, ich bin wieder an Deck.'),
                'next'  => __('Noch ein Tipp'),
                'fox'   => __('Rua, der Fuchs: antippen für einen Tipp'),
            ],
        ];
    }

    public static function data(User $user, ?string $route, bool $praised, ?\App\Models\Book $book = null, string $mode = 'editor'): array
    {
        $name = $user->isPupil() ? $user->first_name : $user->name;
        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 11 => __('Guten Morgen, :name! Wohin soll die Fahrt heute gehen?', ['name' => $name]),
            $hour < 14 => __('Heil dir, :name! Lust auf eine neue Seite?', ['name' => $name]),
            $hour < 18 => __('Willkommen zurück im Hafen, :name! Was gestalten wir heute?', ['name' => $name]),
            default      => __('Guten Abend, :name! Zeit für eine kleine Geschichte?', ['name' => $name]),
        };

        $sayings = [
            __('Jedes große Buch fängt mit einer leeren Seite an. Leg los!'),
            __('Du hast tolle Ideen. Schreib sie auf, bevor der Wind sie mitnimmt!'),
            __('Ein Fehler ist nur Gegenwind. Mit ↶ machst du ihn rückgängig.'),
            __('Ein Bild erzählt viel. Mal doch eins!'),
            __('Lass dir Zeit. Ein Ruderschlag nach dem anderen.'),
            __('Ich bin stolz auf dich!'),
            __('Kleine Schritte bringen dich weit. Das weiß jeder Entdecker.'),
            __('Neugier ist mein Kompass. Und deiner?'),
            __('Guter Wind heute. Genau richtig für eine Geschichte.'),
            __('Mutig sein heißt: einfach anfangen.'),
            __('Ich bin Rua, ein Fuchs aus Norwegen. In Irland nannten sie mich „der Rote“.'),
        ];

        $tips = $user->isPupil() ? [
            __('Tipp: Tippe zweimal auf einen Text, dann kannst du ihn ändern.'),
            __('Tipp: Mit dem runden Griff unten rechts machst du Dinge größer oder kleiner.'),
            __('Tipp: Mit dem Pfeil über einem Bild drehst du es.'),
            __('Tipp: Unter „Ton“ nimmst du deine Stimme auf. Lies deine Geschichte vor wie ein echter Erzähler!'),
            __('Tipp: Mit „Kamera“ machst du direkt ein Foto für dein Buch.'),
            __('Tipp: Mit „Seite“ gibst du deiner Seite eine schöne Farbe.'),
            __('Tipp: Teile dein Buch mit der Klasse. Dann können es alle lesen.'),
            __('Tipp: Über „⋯“ druckst du dein Buch oder nimmst es mit nach Hause.'),
            __('Tipp: Mit „Lesen“ blätterst du durch dein Buch wie durch ein echtes.'),
        ] : [
            __('Tipp: Mit einer Vorlage bekommt jedes Kind seine eigene Version. Du siehst alle unter „Versionen der Kinder“.'),
            __('Tipp: In einem Klassenbuch schreibt die ganze Klasse gleichzeitig. Alle an die Ruder!'),
            __('Tipp: Unter „Benutzer“ legst du eine ganze Klassenliste auf einmal an.'),
            __('Tipp: Mit hinterlegter Anmeldekarte druckst du verlorene Karten jederzeit neu.'),
            __('Tipp: Das Änderungsprotokoll ist dein Logbuch. Dort siehst du, wer was an einem Buch geändert hat.'),
            __('Tipp: Bereiche in der Bibliothek lassen sich einklappen. So behältst du den Überblick.'),
            __('Tipp: Ein Buch lässt sich als eine Datei mitnehmen. Ideal fürs Elterngespräch.'),
        ];
        if ($user->isAdmin()) {
            $tips[] = __('Tipp: Unter „Einstellungen“ setzt du eure Segel: Logo, Farben und Domain eurer Schule.');
            $tips[] = __('Tipp: Der Sommerferienmodus räumt zum Schuljahresende automatisch auf.');
            $tips[] = __('Tipp: Als Admin solltest du die Zwei-Faktor-Anmeldung nutzen.');
        }

        // passend zur Seite — wird zuerst gesagt
        // suited to the page — said first
        $context = [];
        if (SummerHoliday::isSoon()) {
            $context[] = $user->isPupil()
                ? __('Bald sind Sommerferien! Nimm deine Bücher vorher als Datei mit nach Hause.')
                : __('Denk an den Sommerferienmodus: Bald werden die Schülerkonten gelöscht.');
        }
        if ($route === 'home' && $user->isPupil() && ! $user->books()->exists()) {
            $context[] = __('Leinen los! Tippe auf „Neues Buch“ und erfinde deine erste Geschichte.');
        }
        $context = array_merge($context, match ($route) {
            'users.index'   => [__('Tipp: Neue Kinder bekommen ein leichtes Passwort wie „tiger47“. Das schaffen auch kleine Finger.')],
            'classes.index' => [__('Tipp: Über „Bücher“ kommst du auf kürzestem Weg zu den Büchern einer Klasse.')],
            'settings'      => [__('Tipp: Die Farbvorschau zeigt sofort, wie eure Schulfarben aussehen.')],
            'account'       => [$user->isPupil() ? __('Dein Passwort kennst nur du. Gib es niemandem weiter, auch mir nicht.') : __('Mit Zwei-Faktor ist dein Konto noch sicherer. Hier richtest du es ein.')],
            'help'          => [__('Ich kenne hier jeden Winkel. Tipp mich an, dann bekommst du noch einen Tipp.')],
            default         => [],
        });

        $praise = [
            __('Skål! Erledigt.'),
            __('Geschafft, fest wie ein Runenstein.'),
            __('Klasse, das hat geklappt!'),
            __('Super gemacht! 🎉'),
        ];

        // Editor: Rua reagiert auf das, was gerade passiert
        // Editor: Rua reacts to what is happening right now
        $editor = null;
        if ($book && $mode === 'reading') {
            // Leseansicht: begleitet beim Blättern
            // Reading view: keeps you company while turning pages
            $editor = [
                'start'      => $book->user_id == $user->id
                    ? __('Dein Buch sieht toll aus! Blättere mit › oder wisch zur Seite.')
                    : __('Gute Fahrt durchs Buch! Blättere mit › oder wisch zur Seite.'),
                'page_audio' => __('Hier gibt es etwas zu hören. Tippe auf den runden Knopf!'),
                'end'        => __('Land in Sicht: Das war die letzte Seite. Hat dir das Buch gefallen?'),
                'idle'       => __('Weiter geht\'s mit ›. Ich bin gespannt, wie es weitergeht!'),
            ];
        } elseif ($book) {
            $start = match (true) {
                $book->isClassBook()                    => __('Das ist ein Klassenbuch. Hier schreiben alle zusammen wie eine Mannschaft. Oben siehst du, wer gerade dabei ist.'),
                $book->isVersion() && $book->user_id == $user->id && $book->isReturned() => __('Deine Lehrkraft hat dein Buch zurückgegeben. Lies oben, was sie dir geschrieben hat!'),
                $book->isVersion() && $book->user_id == $user->id => __('Das ist deine eigene Version. Bist du fertig, tippe oben auf „Abgeben“.'),
                $book->isTemplate()                        => __('Du bearbeitest eine Vorlage. Kinder, die schon angefangen haben, behalten ihre Version.'),
                $book->user_id != $user->id               => __('Du hilfst bei :name. Deine Änderungen stehen im Änderungsprotokoll.', ['name' => $book->owner?->first_name]),
                default                                   => __('Auf geht\'s! In der Werkzeugleiste findest du alles, was du in dein Buch packen kannst.'),
            };
            $editor = [
                'start'          => $start,
                'select_text'    => __('Tippe den Text nochmal an, dann schreibst du. Rechts änderst du Größe und Farbe.'),
                'select_image'   => __('Mit dem runden Griff änderst du die Größe, mit dem Pfeil oben drehst du.'),
                'select_sticker' => __('Sticker kannst du groß machen, drehen und doppeln.'),
                'select_shape'   => __('Formen sind gut als Hintergrund für Texte. Rechts wählst du Farbe und Form.'),
                'select_audio'   => __('Tippe nochmal auf den Lautsprecher, dann hörst du deine Aufnahme.'),
                'new_text'       => __('Jetzt tippen! Bist du fertig, tippe daneben.'),
                'paint'          => __('Wähle unten Farbe und Dicke. Mit 🧽 radierst du, mit ✓ Fertig kommt dein Bild ins Buch.'),
                'photo'          => __('Schönes Foto! Mit dem Griff machst du es größer oder kleiner.'),
                'audio_done'     => __('Aufgenommen! Tippe auf den runden Knopf, dann hörst du dich.'),
                'new_page'       => __('Eine neue Seite! Wohin segelt deine Geschichte jetzt?'),
                'undo'           => __('Rückgängig gemacht. Weiter geht die Fahrt!'),
                'deleted'        => __('Weg ist es. Mit ↶ holst du es zurück.'),
                'many_pages'     => __('Schon :n Seiten! Du bist eine echte Autorin, ein echter Autor.'),
                'idle'           => __('Flaute? Wie wäre es mit einem Bild, einem Sticker oder einem Ton?'),
                'submitted'      => __('Abgegeben! Deine Lehrkraft freut sich bestimmt.'),
                'explain_touch'  => __('Tipp: Halte einen Knopf gedrückt oder tippe oben auf ❓ — dann erkläre ich dir, was er macht.'),
                'explain_mouse'  => __('Tipp: Zeig mit der Maus auf einen Knopf oder klicke oben auf ❓ — dann erkläre ich dir, was er macht.'),
            ];
        }

        return [
            // Ereignisse im Editor und beim Lesen (fox-book.js) / events in the editor and while reading (fox-book.js)
            'editor'   => $editor,
            'book'     => $book?->id,
            // im Buch begrüßt Rua mit dem passenden Einstieg / in a book Rua greets with the fitting introduction
            'greeting' => $editor['start'] ?? $greeting,
            'context'  => $editor ? [] : array_values($context),
            'tips'     => $tips,
            'sayings'  => $sayings,
            // nach Ernstem (Löschen, Passwort …) schlicht, sonst mit Bild – siehe FoxOccasion
            // after serious actions (deleting, password …) plain, otherwise with an image – see FoxOccasion
            'praise'   => $praised ? (session('fox_occasion') === 'serious' ? __('Erledigt.') : $praise[array_rand($praise)]) : null,
            // Fehler und Warten (fox.js) / errors and waiting (fox.js)
            'error'    => self::errorLine(),
            'waiting'  => [__('Die Ruder sind im Wasser …'), __('Einen Moment noch, gleich legen wir an.')],
            'texts'    => [
                'close' => __('Schließen'),
                'sleep' => __('Schlafen legen'),
                'wake'  => __('Rua schläft – antippen zum Wecken'),
                'woken' => __('Gähn … ausgeschlafen! Danke fürs Wecken, ich bin wieder an Deck.'),
                'next'  => __('Noch ein Tipp'),
                'fox'   => __('Rua, der Fuchs: antippen für einen Tipp'),
            ],
        ];
    }

    /**
     * Rua zu einer Fehlermeldung: Kam die Seite mit Validierungsfehlern zurück,
     * ein Satz dazu. Auf Anmelde-, Konto-, Passwort- und Zwei-Faktor-Seiten ohne
     * Bild und ohne Witz (docs/RUA.md), sonst Gegenwind statt Drama.
     *
     * Rua on an error message: if the page came back with validation errors,
     * one line about it. On sign-in, account, password and two-factor pages
     * without images or jokes (docs/RUA.md), otherwise headwind, not drama.
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
        $lines = [__('Gegenwind. Versuch es nochmal, ich bleib an Deck.'), __('Da fehlt noch etwas. Schau dir das markierte Feld an.')];

        return $lines[array_rand($lines)];
    }
}
