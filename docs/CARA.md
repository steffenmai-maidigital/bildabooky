# Cara – die Begleiterin

*Erst Deutsch, dann Englisch — German first, English below.*

Cara ist die Begleiterin in BilDaBooky. Wer neue Sätze für sie schreibt, ihr
Verhalten ändert oder die Zeichnung anfasst, hält sich an diese Beschreibung.

## Figur

- Eine schwarze Katze: ganz schwarz, bernsteingelbe Mandelaugen, schlank.
- „Cara“ ist Irisch und heißt Freundin. Cara ist „sie“.
- **Sie schläft, bis man sie weckt.** Unten rechts liegt sie eingerollt und
  schläft. Erst wer sie antippt, bekommt etwas von ihr zu hören.
- **Sie hat keine Meinung, nur Fakten.** Sie sagt, wie etwas geht und was
  geschehen ist. Sie bewertet nichts.

## Ton

- Sie **duzt** (wie die ganze Oberfläche von BilDaBooky).
- Kurz, sachlich, freundlich. Ein Gedanke je Satz.
- **Keine Wertung:** keine Superlative, kein „toll“, kein „Ich bin stolz auf
  dich“, keine Sprüche über Schule oder Lernen.
- **Lob benennt, was geschehen ist:** „Gespeichert.“, „Schon fünf Seiten.“,
  „Abgegeben. Deine Lehrkraft sieht es jetzt.“ Ein freundliches Wort ist
  erlaubt, Übertreibung nicht.
- **Ernstes klar und ohne Witz:** Passwort, Zwei-Faktor, Datenschutz,
  Löschen, Sommerferienmodus.
- **Fehler ruhig:** sagen, was zu tun ist.
- Keine Bilder aus Seefahrt, Märchen oder Abenteuer. Keine Werbung für andere
  Produkte.
- Jeder Tipp stimmt mit der Oberfläche überein: Knöpfe heißen im Satz so wie
  auf dem Bildschirm („Neues Buch“, „⋯“, „Als Datei mitnehmen“).

| Anlass | Beispiel |
|---|---|
| Erstes Antippen | „Hallo, Mia! Ich bin Cara. Tipp mich nochmal an, dann bekommst du einen Tipp.“ |
| Tipp | „Tippe einen Text zweimal an, dann kannst du ihn ändern.“ |
| Nach dem Speichern | „Gespeichert.“ · nach Löschen, Passwort usw. nur „Erledigt.“ |
| Im Buch | „Schon :n Seiten.“ · „Abgegeben. Deine Lehrkraft sieht es jetzt.“ |
| Fehler | „Das hat nicht geklappt. Oben steht, was fehlt.“ |
| Ernst | „Dein Passwort kennst nur du. Gib es niemandem weiter.“ |
| Anmeldeseite | „Der Quellcode ist offen. Jeder kann nachlesen, was mit den Daten passiert.“ |

## Verhalten

- **Grundzustand:** eingerollt und schlafend, die Sprechblase ist verborgen.
  **Nichts erscheint von selbst nach Zeit:** keine Begrüßung beim Laden, kein
  Hinweis nach einer Pause, kein Satz beim Warten.
- **Antippen:** Sie setzt sich auf. Das erste Antippen auf einer Seite sagt das
  Passende zur Seite (z. B. auf der Konto-Seite) oder die Begrüßung, jedes
  weitere den nächsten Tipp passend zu Rolle und Ansicht („Noch ein Tipp“).
  Nach dem Schließen der Blase und kurzer Ruhe rollt sie sich wieder ein.
- **Als Antwort auf eine Handlung** spricht sie von sich aus:
  - nach einer gespeicherten Aktion (Erfolgsmeldung): „Gespeichert.“, „Hat
    geklappt.“ oder nach Ernstem schlicht „Erledigt.“,
  - wenn ein Formular mit Fehlern zurückkommt: ein Satz dazu,
  - im Editor nach Aktionen (Text gewählt, Foto, neue Seite, gemalt, Ton
    aufgenommen, gelöscht, rückgängig, abgegeben, 5/10/20 Seiten) und in der
    Leseansicht nach dem Blättern (Seite mit Ton, letzte Seite). Jeder dieser
    Hinweise kommt je Sitzung und Buch nur einmal, mit mindestens 25 Sekunden
    Abstand (beim Lesen 8 Sekunden).
- **„Schlafen legen“** merkt sich der Browser. Dann reagiert sie auf nichts
  mehr, auch nicht nach dem Speichern, bis man sie antippt.
- **Anmeldeseite:** Nach dem Antippen erzählt sie Fakten zu freier Software
  (kostenlos, Daten auf dem Server der Schule, offener Quellcode, Sprachen)
  mit einem Link zum Quellcode.
- **Verschieben** per Ziehen mit Maus oder Finger (ab 6 Pixeln Bewegung,
  kürzer ist ein Antippen). Der Browser merkt sich die Lage je Ansicht
  (Seiten, Editor, Leseansicht). Die Blase klappt zur freien Seite.
- **Tastatur:** Der Fokus liegt auf der Figur. Enter oder Leertaste wecken
  und fragen, die Pfeiltasten verschieben, Escape schließt die Blase.
- **Bildschirmleser:** Die Figur ist ein Knopf mit Beschriftung („Cara
  schläft. Antippen weckt sie.“), die Blase ist eine Statuszeile
  (`role="status"`).
- **Weniger Bewegung:** Mit `prefers-reduced-motion` bewegt sie sich nicht.
- Sie erscheint nie im Druck und nicht auf öffentlichen Seiten ohne
  Anmeldung (Ausnahme: Anmeldeseite).
- Auf dunklem Grund (Dunkelmodus, Leseansicht) hat sie eine feine helle
  Lichtkante, damit die schwarze Katze sichtbar bleibt.

## Technik

| Teil | Datei |
|---|---|
| Texte und Daten | `app/Support/Companion.php` (deutsche Texte = Übersetzungsschlüssel, Übersetzungen in `lang/*.json`) |
| Art der letzten Aktion | `app/Http/Middleware/CompanionOccasion.php` (Sitzung `companion_occasion`: `serious`, `saved`, `done`) |
| Zeichnung und Blase | `resources/views/partials/companion.blade.php` (SVG mit den Haltungen `k-rolle` und `k-sitzt`) |
| Verhalten | `public/js/companion.js`; im Buch `public/js/companion-book.js` mit `window.CompanionEvents.event(key)` |
| Aussehen | Block „Cara“ in `public/css/app.css` (`.companion*`, Teile der Zeichnung `k-*`) |

- Die Daten stehen als JSON im Attribut `data-companion` des Elements
  `#companion`: `first` (erstes Antippen), `tips`, `reply` (Antwort auf die
  eben abgeschickte Aktion, sonst `null`), `events` (nur im Buch), `link`,
  `texts`. Es gibt bewusst keinen Text, der nach Zeit kommt.
- Schalter: zentral `companion_enabled` (Einstellungen → 7), je Person Spalte
  `users.companion` (`null` = an) mit Knopf auf der Seite **Konto**
  (`PUT /account/companion`).
- Browser-Speicher: `bdb-companion-sleep`, `bdb-companion-position`
  (`-position-editor`, `-position-reading`); Hinweise je Buch im
  sessionStorage `bdb-companion-<buch>-<schlüssel>`.
- Der Name im Code ist neutral (`companion`), damit ein späterer
  Figurenwechsel keine Umbenennung braucht.

## English

Cara is the companion in BilDaBooky: a black cat (all black, amber almond
eyes); “Cara” is Irish for friend, and Cara is “she”. **She sleeps until
someone wakes her** and **has no opinions, only facts**. She uses the
informal “du” in German, speaks briefly, factually and kindly, never
judges (no superlatives, no “I'm proud of you”), names what happened when
praising (“Saved.”, “Already five pages.”), treats serious matters
(password, two-factor, privacy, deleting, summer holiday mode) clearly and
without jokes, and calmly says what to do after an error. Every tip must
match the real interface.

Behaviour: curled up and asleep by default, bubble hidden; **nothing appears
after a time**. A tap wakes her: the first tap on a page says what fits the
page or the greeting, every further tap the next tip; after the bubble is
closed and a short rest she curls up again. She only speaks on her own in
direct reply to an action: after a saved action (“Saved.”, “That worked.”,
after serious actions just “Done.”), when a form comes back with errors, and
inside a book after actions (editor) or after turning pages (reader), each
hint once per session and book, at least 25 s apart (8 s while reading).
“Put to sleep” is remembered by the browser; then she reacts to nothing until
tapped. On the sign-in page she tells facts about free software after a tap.
She can be dragged (position remembered per view), focused and woken with
Enter/Space, moved with the arrow keys and closed with Escape; the figure is
a labelled button, the bubble a `role="status"` region. No movement with
`prefers-reduced-motion`, never in print, and a fine light rim on dark
backgrounds.

Code: texts in `app/Support/Companion.php` (German strings are the keys,
translations in `lang/*.json`), occasion in
`app/Http/Middleware/CompanionOccasion.php`, drawing in
`resources/views/partials/companion.blade.php`, behaviour in
`public/js/companion.js` and `public/js/companion-book.js`
(`window.CompanionEvents.event(key)`), styles in `public/css/app.css`. Switches:
setting `companion_enabled`, column `users.companion`, route
`PUT /account/companion`. Identifiers are neutral (`companion`) so that a
later change of character needs no renaming.
