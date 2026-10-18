# Renaming to English identifiers (standard 10)

In October 2026 every German identifier in BilDaBooky was renamed to English
(classes, methods, variables, routes, views, CSS/JS names, database columns,
stored values, settings keys, commands, files). The user interface did not
change: German UI strings stay the translation keys, English, French and
Spanish live in `lang/*.json`, the manuals under `docs/` stay German.

This table is for people with an **old fork, old scripts or an old backup**.
Existing installations need nothing: the migration
`database/migrations/0001_01_01_001300_rename_to_english.php` converts the
database at start-up (idempotent, also for restored old backups; `down()`
reverses it), old page addresses redirect permanently (`routes/legacy.php`),
old query parameters are still read, old artisan command names and options
keep working, and what the browser stored under old names is taken over once.
Session keys were simply renamed: everybody is signed out once after the
update.

Shared building blocks (Directory, Mfa, TwoFactor, Fox, LegalPages,
ErrorPage, ErrorLog, Totp, `fox.js`, `symbols.js`) use the same
names in CrewGate, LinkHub and BilDaBooky.

## Files and folders

| old | new |
|---|---|
| `app/Support/Verzeichnis.php` | `app/Support/Directory.php` |
| `app/Console/Commands/VerzeichnisExtern.php` | `app/Console/Commands/DirectoryIsolated.php` |
| `app/Support/Fuchs.php` | `app/Support/Fox.php` |
| `app/Support/Rechtliches.php` | `app/Support/LegalPages.php` |
| `app/Support/Fehlerseite.php` | `app/Support/ErrorPage.php` |
| `app/Support/Sitzungen.php` | `app/Support/SessionRequirement.php` |
| `app/Support/Sommerferien.php` | `app/Support/SummerHoliday.php` |
| `app/Support/KlasseLoeschen.php` | `app/Support/ClassDeletion.php` |
| — (2FA methods inside `User`) | `app/Models/Concerns/TwoFactor.php` (shared trait) |
| `app/Models/KlassenSitzung.php` | `app/Models/ClassSession.php` |
| `app/Models/KlassenSitzungAnmeldung.php` | `app/Models/ClassSessionLogin.php` |
| `app/Models/KlassenSitzungVerlaengerung.php` | `app/Models/ClassSessionExtension.php` |
| `app/Http/Controllers/KlassenSitzungController.php` | `ClassSessionController.php` |
| `app/Http/Middleware/EnsureKlassenSitzung.php`, `EnsureLeiter.php`, `FuchsAnlass.php` | `EnsureClassSession.php`, `EnsureTeacher.php`, `FoxOccasion.php` |
| `app/Console/Commands/AdminPasswort.php`, `DomainLeeren.php`, `Sicherung.php`, `SommerferienCommand.php` | `AdminPassword.php`, `ClearDomain.php`, `RunBackup.php`, `SummerHolidayCommand.php` |
| — | `routes/legacy.php` (old addresses) |
| — | `database/migrations/0001_01_01_001300_rename_to_english.php` |
| `resources/views/buecher/*` (`bibliothek`, `editor`, `lesen`, `drucken`, `datei`, `protokoll`, `versionen`) | `resources/views/books/*` (`library`, `editor`, `read`, `print`, `download`, `log`, `versions`) |
| `resources/views/verwaltung/*` (`anmeldung`, `benutzer`, `benutzer-bearbeiten`, `benutzer-felder`, `einstellungen`, `fach-symbol`, `karten`, `klasse-faecher`, `klasse-mitmachen`, `klassen`) | `resources/views/manage/*` (`sessions`, `users`, `user-edit`, `user-fields`, `settings`, `subject-symbol`, `cards`, `class-edit`, `class-join`, `classes`) |
| `resources/views/partials/buchkarte`, `fuchs`, `kinder-regal`, `leser`, `leser-stil`, `seiten`, `sitzung-uhr`, `sprache`, `zugaenge`, `zurueckgeben` | `partials/book-card`, `fox`, `pupil-shelf`, `reader`, `reader-style`, `pagination`, `session-clock`, `language`, `credentials`, `return-dialog` |
| `resources/views/konto/show.blade.php` | `account.blade.php` |
| `resources/views/hilfe.blade.php`, `hilfe-doc.blade.php` | `help.blade.php`, `help-doc.blade.php` |
| `resources/views/rechtliches/seite.blade.php` | `legal/page.blade.php` |
| `resources/views/auth/sitzungscode.blade.php` | `auth/session-code.blade.php` |
| `public/js/buch.js`, `leser.js`, `fuchs.js`, `symbole.js` | `public/js/book.js`, `reader.js`, `fox.js`, `symbols.js` |
| — (book hints inside `editor.js`/`leser.js`) | `public/js/fox-book.js` |
| `public/css/buch.css` | `public/css/book.css` |
| `docs/BENUTZERHANDBUCH.md`, `ADMIN-HANDBUCH.md`, `ARCHITEKTUR.md`, `SICHERHEIT.md`, `DOKUMENTATION.md` | `docs/USER-GUIDE.md`, `ADMIN-GUIDE.md`, `ARCHITECTURE.md`, `SECURITY.md`, `DOCUMENTATION.md` |
| backup suffix before the summer holiday / a class deletion `vor-sommerferien`, `vor-klasse-<class>` | `before-summer-holiday`, `before-class-<class>` (older backups keep their names) |

## Classes, constants, methods

| old | new |
|---|---|
| `Book::EIGEN`, `KLASSE`, `VORLAGE`, `PRIVAT`, `LEHRKRAFT`, `FREIGABE_KLASSE`, `FORMATE` | `Book::KIND_OWN`, `KIND_CLASS`, `KIND_TEMPLATE`, `SHARING_PRIVATE`, `SHARING_TEACHER`, `SHARING_CLASS`, `FORMATS` |
| `Book::erlaubteFaecher()`, `istZurueckgegeben()`, `zurueckgebbarDurch()`, `isKlassenbuch()`, `isVorlage()`, `inKlasse()` | `allowedSubjects()`, `isReturned()`, `returnableBy()`, `isClassBook()`, `isTemplate()`, `inClass()` |
| `BookLog::BUENDELN_MINUTEN`, `diffLeer()`, `diffSumme()`, `beschreibung()` | `MERGE_MINUTES`, `diffIsEmpty()`, `diffSum()`, `description()` |
| `User::LEITER`, `ERSTELLER`, `ROLLEN`, `LOKAL`, `VERZEICHNIS` | `User::TEACHER`, `PUPIL`, `ROLES`, `LOCAL`, `DIRECTORY` (+ `LEGACY_TEACHER`, `LEGACY_PUPIL` for old query values) |
| `User::isLeiter()`, `isErsteller()`, `anrede()`, `istLokal()`, `herkunft()`, `zeigtFuchs()`, `faecherIn()`, `unterrichtet()`, `mfaRichtlinie()` | `isTeacher()`, `isPupil()`, `displayName()`, `isLocal()`, `origin()`, `showsFox()`, `subjectsIn()`, `teaches()`, `mfaPolicy()` |
| `SchoolClass::klassenlehrkraefte()`, `klassenlehrkraefteSetzen()`, `unterricht()`, `creators()` | `classTeachers()`, `setClassTeachers()`, `teaching()`, `pupils()` |
| `Subject::scopeSortiert()`, `anzeige()` | `scopeSorted()`, `displayName()` |
| `KlassenSitzung::MINUTEN`, `MAX_TAGE`, `ENDE_UHRZEIT`, `AUFBEWAHREN_TAGE`, `VERLAENGERN_MINUTEN` | `ClassSession::MINUTES`, `MAX_DAYS`, `END_TIME`, `KEEP_DAYS`, `EXTEND_MINUTES` |
| `anmeldungen()`, `verlaengerungen()`, `spaetestesEnde()`, `scopeAktiv()`, `aktivFuer()`, `istAktiv()`, `brauchtCode()`, `codeKlartext()`, `codeStimmt()`, `neuerCode()`, `endeAmTag()`, `aufraeumen()` | `logins()`, `extensions()`, `latestEnd()`, `scopeActive()`, `activeFor()`, `isActive()`, `needsCode()`, `plainCode()`, `codeMatches()`, `newCode()`, `endOnDay()`, `cleanUp()` |
| `Page::SCHRIFTEN`, `FORMEN`, `SYMBOLE` | `Page::FONTS`, `SHAPES`, `SYMBOLS` (+ `LEGACY_VALUES` for old page JSON) |
| `Media::BILD`, `TON` | `Media::IMAGE`, `AUDIO` |
| `Verzeichnis::SCHLUESSEL`, `VERSCHLUESSELUNG`, `aktiv()`, `ausEinstellungen()`, `anmelden()`, `gehoertZu()`, `einstellungen()`, `speichern()`, `regeln()`, `pruefen()` | `Directory::KEYS`, `ENCRYPTION`, `enabled()`, `fromSettings()`, `authenticate()`, `isMemberOf()`, `settings()`, `save()`, `rules()`, `test()` (shared copy) |
| `Mfa::PFLICHT`, `AUS`, `STUFEN`, `RANG`, `gruppen()`, `stufe()`, `pflichtGruppen()`, `richtlinie()`, `erforderlich()`, `erlaubt()`, `titel()` | `Mfa::REQUIRED`, `OFF`, `LEVELS`, `RANK`, `groups()`, `level()`, `requiredGroups()`, `policy()`, `isRequired()`, `isAllowed()`, `label()` |
| `Fuchs::sichtbar()`, `gast()`, `daten()` | `Fox::visible()`, `guest()`, `data()` |
| `FuchsAnlass::ERNST`, `fehlerSatz()`, `ernst()` | `FoxOccasion::SERIOUS`, `errorLine()`, `serious()` |
| `Rechtliches::ARTEN`, `aktiv()`, `titel()`, `speichern()`, `vorlageDatenschutz()`, `zeile()` | `LegalPages::TYPES`, `enabled()`, `title()`, `save()`, `privacyTemplate()`, `line()` |
| `Fehlerseite::referenz()`, `grund()`, `protokolliert()`, `wartezeit()`, `uploadGrenze()`, `angemeldet()`, `unbekannteAdresse()` | `ErrorPage::reference()`, `reason()`, `isLogged()`, `retryAfter()`, `uploadLimit()`, `signedIn()`, `unknownAddress()` |
| `Sitzungen::pflicht()`, `giltFuer()`, `fuer()` | `SessionRequirement::required()`, `appliesTo()`, `runningFor()` |
| `Sommerferien::STANDARD`, `datum()`, `optionen()`, `letzte()`, `zeitpunkt()`, `faellig()`, `bald()`, `vorschau()`, `ausfuehren()`, `nebenbeiPruefen()` | `SummerHoliday::DEFAULTS`, `date()`, `options()`, `last()`, `runAt()`, `isDue()`, `isSoon()`, `preview()`, `run()`, `checkInBackground()` |
| `KlasseLoeschen::buecher()` | `ClassDeletion::books()` |
| `Branding::schule()`, `farbe()`, `hatLogo()`, `logoVerzeichnis()`, `fusszeile()`, `tinte()`, `kontrast()` | `school()`, `colour()`, `hasLogo()`, `logoDirectory()`, `footerText()`, `ink()`, `contrast()` |
| `Emoji::pfad()`, `eingebettet()` | `path()`, `embedded()` |
| controller actions `faecher`, `faecherSpeichern`, `mitmachen`, `mitmachenSpeichern`, `austragen`, `impressum`, `datenschutz`, `zeigen`, `weiter`, `ohneSitzung`, `ausVerzeichnis` | `edit`, `saveSubjects`, `join`, `saveJoin`, `leave`, `imprint`, `privacy`, `show`, `proceed`, `withoutSession`, `fromDirectory` |
| `HelpController::DOCS` keys `benutzer`, `architektur`, `sicherheit`, `ueberblick` | `user`, `architecture`, `security`, `overview` (+ `LEGACY_DOCS`) |
| `UpgradeService::hatBestand()` | `hasData()` |
| local variables (`$buch`, `$klasse`, `$faecher`, `$sitzung`, `$kinder`, `$seite`, `$wert`, …) | English equivalents (`$book`, `$class`, `$subjects`, `$session`, `$pupils`, `$page`, `$value`, …) |

## Database: columns and stored values

| old | new |
|---|---|
| column `users.fuchs` | `users.fox` |
| column `books.freigabe` | `books.sharing` |
| columns `book_logs.seite`, `book_logs.aktion` | `book_logs.page`, `book_logs.action` |
| column `class_leader.klassenlehrkraft` | `class_leader.is_class_teacher` |
| columns `class_session_extensions.alt_ende`, `neu_ende` | `old_end`, `new_end` |
| `users.role` `leiter` / `ersteller` | `teacher` / `pupil` (`admin` unchanged) |
| `users.mfa_policy` `pflicht` / `aus` | `required` / `off` (`optional`, NULL unchanged) |
| `books.kind` `eigen` / `klasse` / `vorlage` | `own` / `class` / `template` |
| `books.sharing` `privat` / `klasse` / `lehrkraft` | `private` / `class` / `teacher` |
| `books.format` `hoch` / `quadrat` / `quer` | `portrait` / `square` / `landscape` |
| `media.kind` `bild` / `ton` | `image` / `audio` |
| `pages.elements` (JSON) font `rund` / `dick` | `rounded` / `heavy` (`sans`, `serif`, `hand` unchanged) |
| `pages.elements` (JSON) shape `rechteck` / `rund` / `kreis` | `rectangle` / `rounded` / `circle` |
| `book_logs.action` `erstellt`, `kopie`, `titel`, `freigabe`, `klasse`, `fach`, `seite`, `seite_neu`, `seite_weg`, `reihenfolge`, `abgegeben`, `abgabe_zurueck`, `zurueckgegeben` | `created`, `copy`, `title`, `sharing`, `class`, `subject`, `page`, `page_added`, `page_removed`, `order`, `submitted`, `submission_returned`, `returned` |
| `book_logs.details` keys `alt`, `neu`, `notiz`, `von`, `vorlage`, `art` | `old`, `new`, `note`, `from`, `template`, `type` |
| `book_logs.details` of action `page` `neu`, `weg`, `inhalt`, `lage`, `texte`, `hintergrund` | `added`, `removed`, `content`, `layout`, `texts`, `background` |
| values inside `book_logs.details` (sharing, format, kind of a created book) | translated like the columns above |

The column defaults of the old migrations (role `ersteller`, kind `eigen`,
format `hoch`, sharing `privat`) are not altered (that would rebuild the
SQLite tables); the code always writes these columns explicitly. Uploaded
files stay where they are (`/data/medien`, `/data/marke`).

## Settings keys (table `settings`)

| old | new |
|---|---|
| `fuchs_aktiv` | `fox_enabled` |
| `sommer_aktiv`, `sommer_datum`, `sommer_klassenbuecher`, `sommer_klassen`, `sommer_jaehrlich`, `sommer_letzte` | `summer_enabled`, `summer_date`, `summer_class_books`, `summer_classes`, `summer_yearly`, `summer_last` |
| JSON in `summer_last`: `kinder`, `buecher`, `medien`, `klassen`, `fuer`, `am`, `ausloeser` (`Zeitplan`, `Befehl`, `automatisch`), `sicherung` | `pupils`, `books`, `media`, `classes`, `for`, `at`, `trigger` (`schedule`, `command`, `automatic`), `backup` |
| `sitzungspflicht` | `session_required` |
| `lehrkraefte_eintragen` | `teachers_can_join` |
| `ldap_aktiv`, `ldap_verschluesselung`, `ldap_bind_passwort`, `ldap_bind_vorlage` | `ldap_enabled`, `ldap_encryption`, `ldap_bind_password`, `ldap_bind_template` |
| `ldap_benutzer_attribut`, `ldap_benutzer_filter`, `ldap_zertifikat` | `ldap_user_attribute`, `ldap_user_filter`, `ldap_certificate` |
| `ldap_gruppe_admin`, `ldap_gruppe_leiter` | `ldap_group_admin`, `ldap_group_teacher` |
| `mfa_leiter`, `mfa_gruppen_pflicht` | `mfa_teacher`, `mfa_required_groups` |
| values of `mfa_<group>` `pflicht` / `aus` | `required` / `off` |
| `impressum_aktiv`, `_art`, `_text`, `_link` | `imprint_enabled`, `_type`, `_text`, `_url` |
| `datenschutz_aktiv`, `_art`, `_text`, `_link` | `privacy_enabled`, `_type`, `_text`, `_url` |

Form fields of the settings page use the same new names (plus
`reset_colours`, `domain_confirmed` for `farben_zuruecksetzen`,
`domain_bestaetigt`).

`config/bildabooky.php`: `schule`, `quellcode`, `sprachen`, `testsystem`,
`zwei_faktor_pflicht`, `medien_pfad`, `sicherung.pfad`, `sicherung.tage`,
`produkt`, `projektseite` → `school`, `source_url`, `locales`, `test_system`,
`two_factor_required`, `media_path`, `backup.path`, `backup.days`,
`product`, `project_url`. Environment variables are unchanged; the role
`leiter` in `REQUIRE_2FA_ROLES` is still understood as `teacher`.

## Session, cache, browser storage, cookie

| old | new |
|---|---|
| session `klassen_sitzung`, `login.sitzung`, `mfa_richtlinie` | `class_session`, `login.session`, `mfa_policy` (old sessions are signed out once) |
| session `login.2fa` key `bis` | `until` |
| flash `zugaenge`, `hinweis`, `ohne_sitzung`, `fuchs_anlass` | `credentials`, `notice`, `no_session`, `fox_occasion` |
| rate limiter `login:`, `login-name:`, `sitzungscode:` | `sign-in:`, `sign-in-name:`, `session-code:` |
| cache `praesenz:<book>`, `sommerferien:geprueft` | `presence:<book>`, `summer-holiday:checked` |
| schedule name `sitzungen-aufraeumen` | `clean-up-sessions` |
| localStorage `bdb-fuchs-tiefschlaf`, `bdb-fuchs-zuletzt`, `bdb-fuchs-lage` | `bdb-fox-sleep`, `bdb-fox-last`, `bdb-fox-position` |
| localStorage `bw_sticker_zuletzt`, `bw_erklaert` | `bdb-sticker-recent`, `bdb-explained` |
| localStorage `bw-rueckgabe-<book>-<time>`, `bdb-regale-zu` | `bdb-returned-<book>-<time>`, `bdb-sections-collapsed` |
| sessionStorage (book hints) `bdb-fuchs-<book>-<key>` | `bdb-fox-<book>-<key>` |
| cookie `sprache` | `locale` |

Old localStorage values and the old cookie are read once, stored under the
new name and deleted (`theme` is unchanged).

## Addresses (GET redirects 301, query kept)

| old | new | route name old → new |
|---|---|---|
| `/anmelden`, `/anmelden/code`, `/anmelden/sitzungscode`, `/abmelden` | `/login`, `/login/code`, `/login/session-code`, `/logout` | `login.sitzungscode` → `login.session-code` |
| `/sprache/{code}` | `/locale/{code}` | `sprache` → `locale.switch` |
| `/marke/logo` | `/brand/logo` | `marke.logo` → `brand.logo` |
| `/impressum`, `/datenschutz` | `/imprint`, `/privacy` | `impressum`, `datenschutz` → `imprint`, `privacy` |
| `/konto`, `/konto/passwort`, `/konto/fuchs` | `/account`, `/account/password`, `/account/fox` | `konto`, `konto.passwort`, `konto.fuchs` → `account`, `account.password`, `account.fox` |
| `/konto/2fa`, `/konto/2fa/bestaetigen`, `/konto/2fa/codes` | `/account/2fa`, `/account/2fa/confirm`, `/account/2fa/codes` | `konto.2fa.start/bestaetigen/codes/aus` → `account.2fa.start/confirm/codes/disable` |
| `/hilfe` | `/help` | `hilfe` → `help` |
| `/hilfe/handbuch/{doc}` (`benutzer`, `architektur`, `sicherheit`, `ueberblick`) | `/help/manual/{doc}` (`user`, `architecture`, `security`, `overview`) | `hilfe.doc` → `help.doc` |
| `/hilfe/protokoll` (DELETE) | `/help/log` | `hilfe.protokoll` → `help.clear-log` |
| `/buecher` (POST), `/vorschau` | `/books`, `/previews` | `buecher.store`, `buecher.vorschau` → `books.store`, `books.previews` |
| `/buecher/{book}`, `…/bearbeiten`, `…/drucken`, `…/datei` | `/books/{book}`, `…/edit`, `…/print`, `…/download` | `buecher.lesen/bearbeiten/drucken/datei` → `books.read/edit/print/download` |
| `/buecher/{book}/kopie`, `…/starten`, `…/abgeben`, `…/zurueckgeben` | `…/copy`, `…/start`, `…/submit`, `…/return` | `buecher.kopie/starten/abgeben/zurueckgeben` → `books.copy/start/submit/return` |
| `/buecher/{book}/protokoll`, `…/versionen`, `…/stand`, `…/reihenfolge` | `…/log`, `…/versions`, `…/state`, `…/order` | `buecher.protokoll/versionen`, `seiten.stand/reihenfolge` → `books.log/versions`, `pages.state/order` |
| `/buecher/{book}/seiten`, `…/seiten/{page}`, `…/medien` | `/books/{book}/pages`, `…/pages/{page}`, `…/media` | `seiten.*`, `medien.store` → `pages.*`, `media.store` |
| `/medien/{media}` | `/media/{media}` | `medien.show` → `media.show` |
| `/benutzer`, `/benutzer/sammel`, `/benutzer/karten`, `/benutzer/{user}`, `…/passwort`, `…/2fa` | `/users`, `/users/bulk`, `/users/cards`, `/users/{user}`, `…/password`, `…/2fa` | `benutzer.*` → `users.*` (`sammel` → `bulk`, `karten` → `cards`, `passwort` → `password`) |
| `/klassen`, `/klassen/{schoolClass}` | `/classes`, `/classes/{schoolClass}` | `klassen.*` → `classes.*` |
| `/klassen/{schoolClass}/mitmachen` | `…/join` | `klassen.mitmachen(.speichern)`, `klassen.austragen` → `classes.join(.save)`, `classes.leave` |
| `/klassen/{schoolClass}/anmeldung`, `…/anmeldung/{sitzung}/verlaengern`, `…/beenden` | `…/sessions`, `…/sessions/{session}/extend`, `…/close` | `klassen.anmeldung(.start/.verlaengern/.beenden)` → `classes.sessions(.start/.extend/.close)` |
| `/klassen/{schoolClass}/bearbeiten`, `…/faecher` | `…/edit`, `…/subjects` (GET of the old subjects page → `…/edit`) | `klassen.bearbeiten`, `klassen.faecher(.speichern)` → `classes.edit`, `classes.subjects(.save)` |
| `/faecher`, `/faecher/{subject}`, `…/verschieben` | `/subjects`, `/subjects/{subject}`, `…/move` | `faecher.*` → `subjects.*` (`verschieben` → `move`) |
| `/sicherung` | `/backup` | `sicherung` → `backup` |
| `/einstellungen`, `…/verzeichnis-testen`, `…/logo` | `/settings`, `…/test-directory`, `…/logo` | `einstellungen(.update/.verzeichnis/.logo/.logo.loeschen)` → `settings(.update/.test-directory/.logo/.logo.delete)` |

`routes/legacy.php` redirects 29 old GET addresses plus the old manual
address and the old manual slugs under the new address. POST/PUT/DELETE
come from the new pages and have no redirect.

Query parameters (old names are still read): `?klasse=` → `?class=`,
`?rolle=` → `?role=` (also old values `leiter`, `ersteller`), `?suche=` →
`?search=` (user list), `?seite=` → `?page=` (page preview).

Middleware aliases: `leiter`, `passwort`, `sitzung`, `ohne-verlauf` →
`teacher`, `password-changed`, `class-session`, `no-history`.

## Views, sections, CSS, JS

| old | new |
|---|---|
| Blade sections `bild`, `titel`, `tipps`, `neu_laden`, `vollbild` | `symbol`, `title`, `tips`, `reload`, `fullscreen` |
| view names `buecher.*`, `verwaltung.*`, `konto.show`, `hilfe`, `rechtliches.seite`, `partials.*` | see *Files and folders* |
| JS globals `BW`, `BW_TEXTE`, `BUCH`, `BWFuchs.ereignis()`, `BWSymbole.tauschen()` | `BDB`, `BDB_TEXTS`, `BOOK`, `FoxEvents.event()`, `Symbols.swap()` |
| book page classes `.bw-rahmen`, `.bw-seite`, `.bw-el`, `.bw-text`, `.bw-inhalt`, `.bw-image`, `.bw-sticker`, `.bw-shape`, `.bw-form`, `.bw-audio`, `.bw-ton`, `.bw-welle*`, `.bw-puls` | `.bdb-frame`, `.bdb-page`, `.bdb-el`, `.bdb-text`, `.bdb-content`, `.bdb-image`, `.bdb-sticker`, `.bdb-shape`, `.bdb-shape-fill`, `.bdb-audio`, `.bdb-audio-button`, `.bdb-wave*`, `.bdb-pulse` |
| fonts `.bw-f-sans`, `-rund`, `-serif`, `-hand`, `-dick` | `.bdb-f-sans`, `-rounded`, `-serif`, `-hand`, `-heavy` |
| `.mit-bg`, `.gespiegelt`, `.spielt`, `data-bw-vorschau`, `data-vorschau-id`, `data-gezeichnet` | `.with-bg`, `.flipped`, `.playing`, `data-bdb-preview`, `data-preview-id`, `data-drawn` |
| reader `.leser`, `.leser-kopf`, `-buehne`, `-seite`, `-zaehler`, `-weiter`, `-zurueck`, `-vollbild`, `.rein-rechts/-links`, `.blaettern` | `.reader`, `.reader-header`, `-stage`, `-page`, `-counter`, `-next`, `-return`, `-fullscreen`, `.enter-right/-left`, `.page-turn` |
| library `.regal`, `.regal-gruppe`, `.kind-regal`, `.kinder-liste`, `data-regal`, `data-fach`, `data-klasse` | `.library-section`, `.section-group`, `.pupil-section`, `.pupils-list`, `data-section`, `data-subject`, `data-class` |
| sessions `.sitzung-uhr`, `-pille`, `-code`, `-verlaengern`, `-beenden`, `-verlauf` | `.session-clock`, `-pill`, `-code`, `-extend`, `-close`, `-history` |
| general `.zweit`, `.leiste`, `.karte`, `.karten`, `.stempel`, `.gefahr`, `.macher`, `.logzeile`, `.hilfe-liste`, `.sprachwahl`, `.vorschau-marke` (`--vm-*`) | `.secondary`, `.toolbar`, `.card`, `.cards`, `.stamp`, `.danger-zone`, `.maker`, `.log-line`, `.help-list`, `.language-choice`, `.brand-preview` (`--bp-*`) |
| CSS variables `--flaeche`, `--flaeche-hell`, `--gegendreh` | `--surface`, `--surface-light`, `--counter-rotate` |
| `.fehlerseite`, `.fehler-*`, `.rechtliches`, `.rechtlich-*`, `.login-rechtliches` | `.error-page`, `.error-*`, `.legal-page`, `.legal-*`, `.login-legal` |
| fox `.fuchs`, `.fuchs-blase`, `-figur`, `-zu`, `-schlafen`; states `.da`, `.spricht`, `.winkt`, `.huepft`, `.schlaeft`, `.tiefschlaf`, `.im-editor`, `.im-lesen`, `.im-seite`, `.im-anmeldung` | `.fox`, `.fox-bubble`, `-figure`, `-close`, `-sleep`; `.shown`, `.speaking`, `.waving`, `.hopping`, `.asleep`, `.deep-sleep`, `.on-editor`, `.on-reading`, `.on-page`, `.on-login` |
| `id="fuchs"`, `data-fuchs`, `data-ohne-fuchs` | `id="fox"`, `data-fox`, `data-no-fox` |
| fox data keys `gruss`, `kontext`, `tipps`, `sprueche`, `lob`, `buch`, `texte` | `greeting`, `context`, `tips`, `sayings`, `praise`, `book`, `texts` |
| book hint keys `auswahl_*`, `neuer_text`, `malen`, `foto`, `ton_fertig`, `neue_seite`, `rueckgaengig`, `geloescht`, `viele_seiten`, `erklaeren_maus/touch`, `seite_ton`, `lange_ruhe`, `lesen`, `ende` | `select_*`, `new_text`, `paint`, `photo`, `audio_done`, `new_page`, `undo`, `deleted`, `many_pages`, `explain_mouse/touch`, `page_audio`, `idle`, `reading`, `end` |
| symbols `.bw-emoji`, `.bw-symbol`, `.bw-geraet-emoji`, `.luft-links`, `.luft-rechts` | `.emoji-image`, `.emoji-symbol`, `.device-emoji`, `.space-left`, `.space-right` |
| JSON of the editor API `seiten`, `konflikt`, `geaendert`, `andere`, `rechte` (`verwalten`, `seitenOrdnen`, `gemeinsam`, `protokoll`, `teilen`, `abgeben`, …) | `pages`, `conflict`, `changed`, `others`, `rights` (`manage`, `reorderPages`, `collaborative`, `log`, `share`, `canSubmit`, …) |

Old page JSON (fonts, shapes) is still understood by `Page::sanitizeElement()`
and `BDB.LEGACY` in `book.js`.

## Commands and tools

| old | new |
|---|---|
| `php artisan bildabooky:admin-passwort {benutzername} --2fa-aus` | `bildabooky:admin-password {username} --disable-2fa` (old name and option still work) |
| `php artisan bildabooky:sicherung` | `bildabooky:backup` (alias) |
| `php artisan bildabooky:domain-leeren` | `bildabooky:clear-domain` (alias) |
| `php artisan bildabooky:sommerferien --vorschau --jetzt` | `bildabooky:summer-holiday --preview --now` (old name and options still work) |
| `php artisan verzeichnis:extern` | `directory:isolated` (internal, no alias) |
| GitHub workflow inputs `veroeffentlichen`, `abbild` | `publish`, `image` |
| entrypoint function `rechte`, variable `ALT_KEY` | `permissions`, `OLD_KEY` |

## What stays German on purpose

- UI strings (`__('…')`, `t('…')` keys, `lang/*.json` keys, placeholders
  like `:name` inside them), manuals in `docs/`, CHANGELOG entries, the
  German half of comments.
- Data folders `/data/medien` and `/data/marke` and the folder names
  `medien/` and `marke/` inside backup ZIPs — existing data and old backups
  keep working, the restore instructions stay valid.
- Table names and environment variables.
- Demo and test data (class, subject and user names, the words of the easy
  passwords, the German OU/group names of the AD test fixture).
- Legacy aliases listed above (old URLs, query parameters, command names and
  options, old keys in the migration, `LEGACY_*` constants).
