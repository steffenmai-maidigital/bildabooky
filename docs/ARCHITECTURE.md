# BilDaBooky – Systemarchitektur

## Aufbau

```
Browser ── HTTPS (Reverse Proxy) ──► Apache + PHP 8.4 (Laravel 13)
                                      ├── SQLite   /data/bildabooky.sqlite
                                      ├── Medien   /data/medien (Fotos, Zeichnungen, Ton)
                                      └── Logo     /data/marke
Scheduler-Container: php artisan schedule:work (Sicherung, Sommerferien, Medien und Sitzungen aufräumen)
```

Ein Docker-Abbild, zwei Dienste (`app`, `scheduler`), kein Build-Schritt,
keine externen Dienste, Schriften oder CDNs.

## Schichten

| Schicht | Ort | Inhalt |
|---|---|---|
| Routen | `routes/web.php` | öffentliche Seiten (Anmeldung, Impressum), angemeldete Seiten (`auth`, `class-session`, `password-changed`), Bereiche für Lehrkräfte (`teacher`) und Admins (`admin`); alte deutsche Adressen leiten in `routes/legacy.php` dauerhaft um |
| Controller | `app/Http/Controllers` | Bücher, Seiten (per fetch), Medien, Benutzer, Klassen, Konto, Einstellungen, Hilfe |
| Middleware | `app/Http/Middleware` | SecurityHeaders, SetLocale, EnsureActiveAccount (gesperrte Konten verlieren die Sitzung), AuthenticateSession (Passwortwechsel beendet andere Sitzungen), CompanionOccasion (Art der letzten Aktion für Cara), EnsureClassSession, EnsurePasswordChanged (Passwort- und Zwei-Faktor-Pflicht), Rollen |
| Modelle | `app/Models` | User, SchoolClass, Subject, Book, Page, Media, BookLog, ClassSession, Setting |
| Bausteine | `app/Support` | Branding, Directory (LDAP), Mfa, Totp, AccountConfirmation (Bestätigung mit Passwort bzw. App-Code), Companion (Cara), Emoji, License, LegalPages, SummerHoliday, SessionRequirement, ClassDeletion, Backup, ErrorLog, ErrorPage |
| Oberfläche | `resources/views`, `public/css/app.css`, `public/js` | Blade, ein Stylesheet, Editor (`editor.js`), Leser (`reader.js`), Buchseiten (`book.js`), Cara (`companion.js`, `companion-book.js`), Symbole (`symbols.js`, Twemoji) |

## Datenmodell (Kern)

- `users` (Rolle admin/teacher/pupil, `auth_source` local/ldap, `active`, Zwei-Faktor-Felder, `mfa_policy`, `companion`)
- `school_classes`, `class_leader`, `subjects`, `class_subject_teacher`
- `books` (`kind`: own, class, template; Version einer Vorlage über `template_id`; `sharing`: private, class, teacher), `pages`
  (Elemente als JSON), `media` (`unused_since`: seit wann auf keiner Seite mehr), `book_logs` (Änderungsprotokoll)
- `class_sessions` (Anmelde-Sitzungen), `settings` (Schlüssel/Wert)

## Abläufe

- **Anmeldung**: eigenes Konto → Passwort; sonst Schulverzeichnis
  (`Directory::authenticate`) → Konto anlegen/aktualisieren, Rolle aus
  Gruppen. Danach Zwei-Faktor nach Richtlinie (`Mfa`), Kinder Sitzungscode.
- **Editor**: Seiten werden per `fetch` gespeichert; Fotos werden im Browser
  verkleinert und ohne Standortdaten hochgeladen.
- **Rechte an Büchern** (`Book::visibleTo`, `editableBy`, `manageableBy`,
  `logVisibleTo`): Lehrkräfte der Klasse für Bücher der Kinder, Klassenbücher
  und Vorlagen; eigene Bücher von Lehrkräften (`Book::isTeachersOwn`) nur
  Eigentümerin und Admins, lesend zusätzlich über das eigene Teilen.
- **Sicherung**: `VACUUM INTO` der Datenbank + Medien als ZIP (`Backup::create`);
  `.app_key` nur in den Sicherungen auf dem Server, nicht im Download
  (`BackupController`, POST mit Passwort bzw. App-Code).
- **Medien aufräumen**: `bildabooky:prune-media` (nachts) sammelt die Medien
  aller Seiten aller Bücher, setzt bei unbenutzten `unused_since` und löscht
  sie nach 30 Tagen samt Datei.
- **Sitzungen**: `EnsureActiveAccount` liest das Konto je Anfrage
  (`users.active`); `AuthenticateSession` vergleicht den Passwort-Hash der
  Sitzung mit dem aktuellen.
- **Update**: `app:upgrade` beim Start — Versionsabgleich, Sicherung,
  Migrationen.
