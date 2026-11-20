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
| 7 | Cara | Begleiterin zentral an/aus (jede Person kann sie unter **Konto** zusätzlich für sich ausblenden) |
| 8 | Anmeldung und Klassen | Sitzungspflicht für Kinder, Selbsteintrag der Lehrkräfte (ab Werk aus; bei Installationen vor dem 20.11.2026 bleibt die bisherige Einstellung), **Zwei-Faktor je Gruppe** (Admins, Lehrkräfte: Pflicht / wählbar / aus) und Verzeichnisgruppen mit Pflicht |
| 9 | Schulverzeichnis | Server, Port, LDAPS/STARTTLS, Suchbasis, Servicekonto, Gruppen der Lehrkräfte (Pflicht) und Admins, „Verbindung testen“. Gruppennamen werden beim Speichern zum vollständigen DN aufgelöst; gibt es einen Namen mehrfach, meldet das Feld den Fehler – dann den DN eintragen |
| 10 | Impressum und Datenschutz | einzeln einschaltbar, Text oder Link, Vorlage für die Datenschutzerklärung |

## 4. Benutzer und Rechte

- **Admin** ist ein Haken bei einer Lehrkraft. Admins sehen keine Bücher der
  Kinder, Klassenbücher oder Vorlagen, außer in Klassen, in denen sie selbst
  unterrichten. Eigene Bücher von Lehrkräften öffnen, ändern und löschen Admins
  immer (z. B. wenn eine Lehrkraft die Schule verlässt).
- **Eigene Bücher von Lehrkräften** (kein Klassenbuch, keine Vorlage) sehen
  nur die Lehrkraft selbst und die Admins, auch wenn eine Klasse eingetragen
  ist. Teilt die Lehrkraft das Buch „mit der Lehrkraft“, lesen es die anderen
  Lehrkräfte der Klasse, „mit der Klasse“ auch die Kinder.
- **Lehrkräfte** verwalten die Kinder ihrer Klassen, **Kinder** nur ihre
  Bücher.
- **Schulkonten** (aus dem Verzeichnis) entstehen beim ersten Anmelden; Rolle
  (Lehrkraft/Admin) kommt aus den Gruppen, Klassen und Fächer ordnet der
  Admin zu. Sie haben kein Passwort und keine Anmeldekarte in BilDaBooky.
  Hinein kommen nur Mitglieder der Gruppe der Lehrkräfte oder der Gruppe der
  Admins. Die Gruppe der Lehrkräfte ist Pflicht, weil ein Schulverzeichnis
  meist auch Schülerkonten enthält; ohne sie lässt sich das Verzeichnis nicht
  einschalten.
- **Konto aktiv**: Unter Benutzer → Bearbeiten sperrt der Haken „Konto aktiv“
  das Konto einer Lehrkraft oder eines Admins (eigene Konten und Schulkonten),
  ohne Bücher und Klassen zu löschen. Das eigene Konto lässt sich nicht
  sperren, Kinder auch nicht (dafür gibt es die Sitzungspflicht). Ein
  gesperrtes Konto kommt nicht mehr hinein; eine laufende Sitzung endet bei
  der nächsten Aktion. Die Benutzerliste zeigt „Konto gesperrt“.
- **Passwort zurücksetzen**: Ein neues Passwort (Knopf „Passwort
  zurücksetzen“ oder Feld „Neues Passwort“) beendet alle Sitzungen dieser
  Person auf allen Geräten. Wer sein eigenes Passwort unter **Konto** ändert,
  bleibt auf dem aktuellen Gerät angemeldet, die anderen Geräte sind
  abgemeldet.
- **Zwei-Faktor**: Ausnahme je Person unter Benutzer → Bearbeiten („wie
  Gruppe“, Pflicht, wählbar, aus); Zurücksetzen bei verlorenem Handy
  ebenfalls dort (die eigene Einrichtung nur unter **Konto**). Eine geänderte
  Stufe gilt sofort: Wer unter die Pflicht fällt, kommt bis zur Einrichtung nur
  noch auf die Seite **Konto** – auch der Admin, der die Pflicht für Admins
  einschaltet. Verzeichnisgruppen mit Pflicht wirken ab der nächsten Anmeldung.
- **Notzugang**: `docker exec bildabooky php artisan bildabooky:admin-password [name]`
  setzt für einen lokalen Admin (eigenes Konto, Standard `admin`) ein neues
  Startpasswort und hebt eine Sperre auf; `--disable-2fa` setzt auch
  Zwei-Faktor zurück. Gibt es keinen lokalen Admin (etwa nur Admins aus dem
  Schulverzeichnis), legt der Befehl einen unter dem angegebenen Namen an. Das
  Startpasswort muss bei der Anmeldung geändert werden. Details:
  `installation/betrieb.md`.

## 5. Betrieb

- **Update**: neuen Stand holen, `docker compose up -d --build`. Beim Start
  prüft `app:upgrade` die Version (kein Downgrade), sichert die Datenbank und
  migriert. Details: `installation/betrieb.md`.
- **Sicherung**: nächtlich (ZIP aus Datenbank, Medien, Logo, `.app_key`);
  dazu automatisch vor dem Löschen einer Klasse und vor dem
  Sommerferienmodus. Den Ordner `backups` nur für Admins freigeben — er
  enthält den Schlüssel.
- **Sicherung herunterladen**: unter **Klassen → Sicherung**, nur mit dem
  eigenen Passwort (Schulkonten: aktueller Code aus der Authenticator-App;
  ohne eingerichtete Zwei-Faktor-Anmeldung geht es nicht). 5 Fehlversuche →
  5 Minuten Pause. Die Datei enthält den App-Schlüssel **nicht**. Er liegt in
  `/data/.app_key`; getrennt aufbewahren, z. B. im Passwortmanager. Ohne ihn
  sind nach einer Wiederherstellung die Zwei-Faktor-Anmeldung und die
  Anmeldekarten unlesbar.
- **Entfernte Medien**: Fotos, Zeichnungen und Töne, die auf keiner Seite
  irgendeines Buchs mehr stehen, löscht die nächtliche Aufgabe
  `bildabooky:prune-media` nach 30 Tagen (Datei und Datensatz). Wird ein
  Medium vorher wieder eingesetzt, bleibt es. Probelauf:
  `php artisan bildabooky:prune-media --dry-run`.
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
| „Die Gruppe … gibt es mehrfach im Verzeichnis“ | gleicher Gruppenname in mehreren OUs: den vollständigen DN eintragen, z. B. `CN=Lehrkraefte,OU=Gruppen,DC=schule,DC=local` |
| „Dieses Konto ist gesperrt“ | Benutzer → Person → Haken „Konto aktiv“ setzen; beim letzten Admin: Notzugang |
| Sicherung herunterladen fragt nach einem Code | Schulkonto: aktueller Code aus der Authenticator-App; ohne Zwei-Faktor-Anmeldung erst unter Konto einrichten |
| „Forbidden“ nach eigenem Docker-Bau | Dateirechte auf dem Bauhost (600); das Abbild setzt Leserechte selbst, Quellordner prüfen |
