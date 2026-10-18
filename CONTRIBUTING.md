# Mitmachen / Contributing

Schön, dass du helfen möchtest! *(English below.)*

## Grundsätze

1. **Kinder zuerst.** Große Knöpfe (mindestens 44 px), wenig Text, nichts, was
   versehentlich etwas löscht, ohne nachzufragen.
2. **Daten bleiben in der Schule.** Keine externen Dienste, CDNs, Schriften,
   Analyse- oder Fehlerdienste. Alles, was der Browser lädt, kommt vom eigenen
   Server.
3. **Kein Build-Schritt.** Kein Node und kein npm: CSS und JavaScript liegen
   direkt in `public/`. Das hält den Betrieb auf kleinen Schulservern einfach.
4. **Inhalte nie als HTML.** Buchinhalte gelangen nur per `textContent` und
   Style-Eigenschaften ins DOM. Der Server prüft jedes Element
   (`Page::sanitizeElement`).
5. **Jeder Text übersetzbar.** In PHP und Blade `__('Deutscher Text')`, im
   JavaScript `t('Deutscher Text')`. Danach die Übersetzungen in `lang/*.json` ergänzen.

## Ablauf

Fehler, Ideen und Fragen bitte als **Issue** melden (Vorlagen „Fehler melden“
und „Idee vorschlagen“). Sicherheitslücken nie als Issue, sondern vertraulich
(siehe [SECURITY.md](SECURITY.md)).

Pull Requests sind derzeit nur für eingeladene Mitwirkende möglich. Wer eine
Änderung beitragen möchte, beschreibt sie bitte zuerst in einem Issue – die
Betreuer melden sich dann.

Für eingeladene Mitwirkende:

1. Issue anlegen oder kommentieren, was du vorhast.
2. Branch, Änderung.
3. Lokal testen:
   ```bash
   docker build -t bildabooky:dev .
   docker run --rm -p 7290:80 -e DEMO_MODE=true -e APP_DEBUG=true bildabooky:dev
   ```
4. Pull Request mit kurzer Beschreibung und, bei Oberflächenänderungen, einem
   Bildschirmfoto (Tablet- und Telefonbreite).

### Veröffentlichen

Versionen veröffentlichen die Betreuer. Trag deine Änderung bitte im
`CHANGELOG.md` unter „## [Unveröffentlicht]“ ein; Nummer, Git-Tag und
Docker-Abbild kommen beim Veröffentlichen dazu. Vorher prüfen die Betreuer
Übersetzungen, Doku und Sicherheit mit eigenen Werkzeugen.

Es gilt der [Verhaltenskodex](CODE_OF_CONDUCT.md).

Mit einem Beitrag stimmst du zu, dass er unter der AGPL-3.0-or-later
veröffentlicht wird.

## Englische Namen, Kommentare zweisprachig / English names, bilingual comments

**Deutsch.** Alle Bezeichner im Code sind englisch (Standard 10): Klassen,
Methoden, Variablen, Routen und Routennamen, Ansichten, CSS-Klassen,
JS-Namen, Datenbankspalten, gespeicherte Werte, Einstellungsschlüssel,
Artisan-Befehle, Dateien und Ordner. Deutsch bleiben die Oberflächentexte
(deutsche Übersetzungsschlüssel in `__('…')` und `t('…')`, die anderen
Sprachen in `lang/*.json`), die Handbücher unter `docs/` und die deutsche
Hälfte der Kommentare.

- **Jeder Kommentar steht auf Deutsch und Englisch**: erst Deutsch, dann
  Englisch; in Docblocks durch eine Leerzeile getrennt, bei Zeilenkommentaren
  als zweite Zeile, am Zeilenende als `// Deutsch / English`.
- Begriffe einheitlich: Kind/Ersteller = pupil, Leiter/Lehrkraft = teacher,
  Klasse = class (`SchoolClass`), Fach = subject, Buch = book, Seite = page,
  Klassenbuch = class book, Vorlage = template, Freigabe = sharing, abgeben =
  submit, zurückgeben = return, Klassensitzung = class session, Sommerferien
  = summer holiday, Sicherung = backup, Verzeichnis = directory, Fuchs-Helfer
  = fox.
- Die Datenordner `/data/medien` und `/data/marke` (und dieselben Ordner in
  den Sicherungen) behalten ihre Namen, damit bestehende Daten und alte
  Sicherungen gültig bleiben.
- Wer einen alten Fork oder eine alte Sicherung hat: Die Tabelle aller
  Umbenennungen (alt → neu) steht in [docs/RENAMING.md](docs/RENAMING.md).
  Die Migration `rename_to_english` stellt bestehende Datenbanken beim Start
  automatisch um; alte Adressen leiten dauerhaft um (`routes/legacy.php`).

**English.** All identifiers in the code are English (standard 10): classes,
methods, variables, routes and route names, views, CSS classes, JS names,
database columns, stored values, settings keys, artisan commands, files and
folders. German remains for UI strings (German translation keys in
`__('…')` and `t('…')`, the other languages in `lang/*.json`), the manuals
under `docs/` and the German half of the comments.

- **Every comment is written in German and English**: German first, then
  English; in docblocks separated by a blank line, for line comments as a
  second line, at the end of a code line as `// Deutsch / English`.
- Consistent terms: Kind/Ersteller = pupil, Leiter/Lehrkraft = teacher,
  Klasse = class (`SchoolClass`), Fach = subject, Buch = book, Seite = page,
  Klassenbuch = class book, Vorlage = template, Freigabe = sharing, abgeben =
  submit, zurückgeben = return, Klassensitzung = class session, Sommerferien
  = summer holiday, Sicherung = backup, Verzeichnis = directory, Fuchs-Helfer
  = fox.
- The data folders `/data/medien` and `/data/marke` (and the same folders in
  backups) keep their names so that existing data and old backups stay
  valid.
- If you have an old fork or an old backup: the table of all renames
  (old → new) is in [docs/RENAMING.md](docs/RENAMING.md). The migration
  `rename_to_english` converts existing databases automatically at start-up;
  old addresses redirect permanently (`routes/legacy.php`).

## English

Please report bugs, ideas and questions as **issues** (templates "Bug report"
and "Feature request"); security issues never as an issue, but confidentially
(see [SECURITY.md](SECURITY.md)). Pull requests are currently only possible for
invited collaborators – if you would like to contribute a change, please
describe it in an issue first and the maintainers will get back to you.

- Children first: big touch targets, little text, confirm destructive actions.
- No third-party services, CDNs or web fonts. Everything is served by the
  school's own server.
- No build step (no Node/npm).
- Never render book content as HTML.
- All UI strings must be translatable: `__('German text')` in PHP/Blade and
  `t('German text')` in JS. German strings are the keys. Update `lang/*.json`.

The [Code of Conduct](CODE_OF_CONDUCT.md) applies.

By contributing you agree that your contribution is licensed under the
AGPL-3.0-or-later.
