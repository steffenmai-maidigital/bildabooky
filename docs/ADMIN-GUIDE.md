# BilDaBooky – Administratorhandbuch

Für Admins einer Schule. Schritt-für-Schritt-Anleitungen zur Installation
liegen unter `installation/` (Docker, Synology, Reverse Proxy, Betrieb).

## 1. Überblick

| | |
|---|---|
| **Technik** | Laravel (PHP 8.4), SQLite, Apache – ein Docker-Abbild, zwei Container (`app`, `scheduler`) |
| **Anmeldung** | eigene Konten; Lehrkräfte und Admins optional mit dem Schulkonto (Active Directory, Samba, Univention, OpenLDAP); Kinder immer eigene Konten |
| **Zweiter Faktor** | TOTP (Authenticator-App) für Lehrkräfte und Admins, je Gruppe Pflicht, wählbar oder aus |
| **Daten** | Volume `app-data` (`/data`): Datenbank, Medien, Logo, `.app_key` |
| **Sicherung** | nächtlich als ZIP nach `./backups`, Aufbewahrung `BACKUP_KEEP_DAYS` |

## 2. Erste Schritte

1. `docker compose up -d --build`, dann <http://localhost:7290>.
2. Als `admin` anmelden (Startpasswort im Container-Protokoll oder
   `ADMIN_PASSWORD`), eigenes Passwort vergeben.
3. **Einstellungen**: Schule, Domain (öffentliche https-Adresse), Farben,
   Logo.
4. **Klassen** und Fächer anlegen, **Benutzer**: Lehrkräfte anlegen und
   Klassen zuordnen; Lehrkräfte legen ihre Kinder selbst an.

## 3. Einstellungen (Abschnitte)

| Nr. | Abschnitt | Inhalt |
|---|---|---|
| 1 | Schule | Name, Text in der Fußzeile |
| 2 | Domain | öffentliche Adresse für Links und Anmeldekarten (nur eintragen, wenn schon erreichbar; Notausgang `php artisan bildabooky:clear-domain`) |
| 3 | Farben | Akzent, Kopfzeile, Hintergrund mit Vorschau |
| 4 | Lizenz | rein informativ (Community Edition ohne Schlüssel) |
| 5 | Logo | PNG/JPG/WebP/SVG bis 2 MB |
| 6 | Sommerferienmodus | löscht am gewählten Tag alle Schülerkonten mit Büchern (mit Sicherung vorher) |
| 7 | Fuchs-Helfer | zentral an/aus |
| 8 | Anmeldung und Klassen | Sitzungspflicht für Kinder, Selbsteintrag der Lehrkräfte, **Zwei-Faktor je Gruppe** (Admins, Lehrkräfte: Pflicht / wählbar / aus) und Verzeichnisgruppen mit Pflicht |
| 9 | Schulverzeichnis | Server, Port, LDAPS/STARTTLS, Suchbasis, Servicekonto, Gruppen der Lehrkräfte und Admins, „Verbindung testen“ |
| 10 | Impressum und Datenschutz | einzeln einschaltbar, Text oder Link, Vorlage für die Datenschutzerklärung |

## 4. Benutzer und Rechte

- **Admin** ist ein Haken bei einer Lehrkraft. Admins sehen keine Bücher,
  außer in Klassen, in denen sie selbst unterrichten.
- **Lehrkräfte** verwalten die Kinder ihrer Klassen, **Kinder** nur ihre
  Bücher.
- **Schulkonten** (aus dem Verzeichnis) entstehen beim ersten Anmelden; Rolle
  (Lehrkraft/Admin) kommt aus den Gruppen, Klassen und Fächer ordnet der
  Admin zu. Sie haben kein Passwort und keine Anmeldekarte in BilDaBooky.
- **Zwei-Faktor**: Ausnahme je Person unter Benutzer → Bearbeiten („wie
  Gruppe“, Pflicht, wählbar, aus); Zurücksetzen bei verlorenem Handy
  ebenfalls dort.
- **Notzugang**: `docker exec bildabooky php artisan bildabooky:admin-password`
  setzt ein neues Admin-Startpasswort (`--disable-2fa` setzt auch Zwei-Faktor
  zurück).

## 5. Betrieb

- **Update**: neuen Stand holen, `docker compose up -d --build`. Beim Start
  prüft `app:upgrade` die Version (kein Downgrade), sichert die Datenbank und
  migriert. Details: `installation/betrieb.md`.
- **Sicherung**: nächtlich (ZIP aus Datenbank, Medien, Logo, `.app_key`);
  Download unter Einstellungen → Sicherung. Den Ordner `backups` nur für
  Admins freigeben — er enthält den Schlüssel.
- **Protokolle**: Hilfe → „Letzte Fehler“ zeigt Fehler der letzten 30 Tage;
  „Protokoll leeren“ räumt auf.
- **Testsystem**: zweite Instanz mit `TEST_MODE: "true"` (Banner TESTSYSTEM).

## 6. Umgebungsvariablen

Siehe README → „Einstellungen (Umgebungsvariablen)“. Wichtig:
`APP_URL` (https), `ADMIN_PASSWORD` (nur erster Start), `REQUIRE_2FA_ROLES`
(Startwert für die Zwei-Faktor-Richtlinie, danach Einstellungen → 8),
`LDAP_*` (Startwerte für das Schulverzeichnis, danach Einstellungen → 9).

## 7. Häufige Stolpersteine

| Symptom | Ursache / Lösung |
|---|---|
| Kamera/Mikrofon gehen nicht | kein https; Reverse Proxy mit Zertifikat einrichten |
| Nach Domain-Eintrag keine Seite mehr | `php artisan bildabooky:clear-domain` |
| Schulkonto kann sich nicht anmelden | Einstellungen → 9 „Verbindung testen“; Gruppe der Lehrkräfte prüfen; Benutzerattribut `sAMAccountName` (AD) bzw. `uid` |
| Lehrkraft kommt nicht an der Zwei-Faktor-Pflicht vorbei | gewollt: einrichten unter Konto; Admin kann Ausnahme setzen |
| „Forbidden“ nach eigenem Docker-Bau | Dateirechte auf dem Bauhost (600); das Abbild setzt Leserechte selbst, Quellordner prüfen |
