# Sicherheit / Security

*Erst Deutsch, dann Englisch — German first, English below.*

## Deutsch

### Sicherheitslücke melden

Bitte melde Sicherheitslücken **nicht** öffentlich als Issue, sondern
vertraulich an die Betreuer des Projekts, über die private
Sicherheitsmeldung von GitHub (*Security → Report a vulnerability*). Wir
melden uns so schnell wie möglich und sagen Bescheid, sobald eine
Korrektur veröffentlicht ist.

Hilfreich sind: betroffene Version (unter **Hilfe → System**), eine kurze
Beschreibung, Schritte zum Nachstellen und, falls bekannt, mögliche
Auswirkungen.

### Unterstützte Versionen

Korrekturen gibt es für die **jeweils neueste Version** (Schema JJMMTTNN,
siehe [README](README.md#versionen-und-docker-abbilder)). Bitte vor einer
Meldung prüfen, ob das Problem dort noch besteht.

### Eingebaute Schutzmaßnahmen

- **Kein Zugriff ohne Anmeldung:** Bücher, Fotos, Töne, Protokolle und
  Verwaltung nur nach Anmeldung; Medien liegen außerhalb des Webverzeichnisses
  und werden nur an Berechtigte ausgeliefert. Datenbank, Konfiguration und
  Sicherungen sind über das Web nicht erreichbar.
- **Rechte je Rolle und Klasse:** Jede Anfrage prüft, ob die Person das Buch,
  die Seite, das Foto oder den Benutzer sehen bzw. ändern darf — auch bei
  geänderten Nummern in der Adresse.
- **Passwörter** nur als bcrypt-Hash; optional Zwei-Faktor (TOTP).
- **Bremse gegen Passwort-Raten:** je Name und Gerät sowie je Name allein
  (10 Fehlversuche → 30 Minuten gesperrt, unabhängig von der IP-Adresse);
  die Lehrkraft hebt die Sperre durch Zurücksetzen des Passworts auf.
- **Kein Standardpasswort:** Das erste Admin-Passwort ist zufällig und muss
  geändert werden.
- **Gegen eingeschleusten Code:** Buchinhalte gelangen nie als HTML in die
  Seite; strenge Content-Security-Policy (nur eigener Server), Schutz gegen
  Einbetten in fremde Seiten, `nosniff`, CSRF-Schutz aller Formulare.
- **Uploads:** nur Bilder und Töne (Dateityp wird geprüft), höchstens 20 MB,
  höchstens 60 je Minute; SVG-Logos laufen in einer Sandbox.
- **Hinter HTTPS:** Cookies nur verschlüsselt (`Secure`), HSTS.

### Sicherheitstest

Vor jeder Version greifen die Betreuer eine **Testinstanz** mit Demo-Daten
automatisch an (Rechte über Klassen und Rollen hinweg, CSRF, XSS,
SQL-Injection, Passwort-Raten, Header, Standardpasswörter).

### Hinweise für den Betrieb

- Nur über HTTPS betreiben (Reverse Proxy mit Zertifikat).
- Das erste Admin-Passwort sofort ändern; für Admins am besten
  `REQUIRE_2FA_ROLES=admin` setzen.
- `/data/.app_key` mitsichern, aber nicht weitergeben. Er steckt auch in
  jeder Sicherung — den Ordner `backups` deshalb nur für Admins freigeben.
- `APP_DEBUG` im Betrieb immer auf `false` lassen.
- Port `7290` nicht direkt ins Internet freigeben, nur den Reverse Proxy.
- Regelmäßig auf die neueste Version aktualisieren.

## English

### Reporting a vulnerability

Please do **not** report security vulnerabilities as a public issue. Report
them privately to the project maintainers via GitHub's private vulnerability
reporting (*Security → Report a vulnerability*). We will get back to you as
soon as possible and let you know once a fix has been released.

Helpful details: the affected version (under **Help → System**), a short
description, steps to reproduce and, if known, the possible impact.

### Supported versions

Fixes are provided for the **latest version** (scheme YYMMDDNN, see the
[README](README.en.md#versions-and-docker-images)). Please check whether the
problem still exists there before reporting it.

### Built-in protections

- **No access without login:** books, photos, sounds, logs and
  administration only after login; media live outside the web root and are
  only served to authorised people. Database, configuration and backups are
  not reachable over the web.
- **Permissions per role and class:** every request checks whether the
  person may see or change the book, page, photo or user — even if numbers in
  the address are changed.
- **Passwords** only as bcrypt hashes; optional two-factor (TOTP).
- **Protection against password guessing:** per name and device and per name
  alone (10 failures → locked for 30 minutes, independent of the IP
  address); the teacher lifts the lock by resetting the password.
- **No default password:** the first admin password is random and must be
  changed.
- **Against injected code:** book content never reaches the page as HTML;
  strict Content Security Policy (own server only), protection against
  framing, `nosniff`, CSRF protection on all forms.
- **Uploads:** only pictures and sounds (file type is checked), max. 20 MB,
  max. 60 per minute; SVG logos run in a sandbox.
- **Behind HTTPS:** cookies only encrypted (`Secure`), HSTS.

### Security test

Before every version the maintainers automatically attack a **test
instance** with demo data (permissions across classes and roles, CSRF, XSS,
SQL injection, password guessing, headers, default passwords).

### Operating securely

- Only run it over HTTPS (reverse proxy with a certificate).
- Change the first admin password right away; ideally set
  `REQUIRE_2FA_ROLES=admin` for admins.
- Back up `/data/.app_key`, but never share it. It is also contained in every
  backup, so only give admins access to the `backups` folder.
- Always keep `APP_DEBUG` set to `false` in production.
- Do not expose port `7290` directly to the internet, only the reverse proxy.
- Update to the latest version regularly.
