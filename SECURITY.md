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
  geänderten Nummern in der Adresse. Eigene Bücher einer Lehrkraft sehen nur
  sie selbst und die Admins.
- **Passwörter** nur als bcrypt-Hash; optional Zwei-Faktor (TOTP). Ein
  Passwortwechsel beendet die anderen Sitzungen der Person.
- **Konten sperren:** Admins sperren Konten von Lehrkräften und Admins
  („Konto aktiv“); eine laufende Sitzung endet bei der nächsten Anfrage.
- **Bremse gegen Passwort-Raten:** je Name und Gerät sowie je Name allein
  (10 Fehlversuche → Kinder 5, sonst 30 Minuten gesperrt, unabhängig von der
  IP-Adresse); wer die Person verwaltet, hebt die Sperre unter **Benutzer** auf.
- **Sicherung zum Herunterladen** nur per Formular mit Passwort bzw. App-Code
  und ohne den App-Schlüssel.
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
- `/data/.app_key` getrennt aufbewahren (z. B. im Passwortmanager), aber nicht
  weitergeben. Er steckt in den Sicherungen auf dem Server — den Ordner
  `backups` deshalb nur für Admins freigeben. Die Sicherung zum Herunterladen
  im Browser enthält ihn nicht.
- `APP_DEBUG` im Betrieb immer auf `false` lassen.
- Port `7290` nicht direkt ins Internet freigeben, nur den Reverse Proxy.
- **`TRUSTED_PROXIES`** steht ab Werk auf allen internen Netzen (`10.0.0.0/8`,
  `172.16.0.0/12`, `192.168.0.0/16`), damit ein Reverse Proxy ohne weitere
  Einstellung funktioniert. Bekannte Folge: Jedes Gerät in diesen Netzen kann
  mit einer gefälschten Kopfzeile `X-Forwarded-For` eine andere Adresse
  vortäuschen; die Anmeldebremse je Adresse greift dann nicht, die Sperre je
  Benutzername schon. Wer das vermeiden will, trägt nur die IP des Reverse
  Proxys ein.
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
  the address are changed. A teacher's own books are seen only by that
  teacher and the admins.
- **Passwords** only as bcrypt hashes; optional two-factor (TOTP). A password
  change ends the person's other sessions.
- **Locking accounts:** admins lock accounts of teachers and admins
  (“Account active”); a running session ends with the next request.
- **Protection against password guessing:** per name and device and per name
  alone (10 failures → locked for 5 minutes for children, otherwise 30,
  independent of the IP address); whoever manages the person lifts the lock
  under **Users**.
- **Backup download** only via a form with password or app code and without
  the app key.
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
- Keep `/data/.app_key` separately (e.g. in a password manager), but never
  share it. It is contained in the backups on the server, so only give admins
  access to the `backups` folder. The backup downloaded in the browser does
  not contain it.
- Always keep `APP_DEBUG` set to `false` in production.
- Do not expose port `7290` directly to the internet, only the reverse proxy.
- **`TRUSTED_PROXIES`** covers all internal networks by default (`10.0.0.0/8`,
  `172.16.0.0/12`, `192.168.0.0/16`) so that a reverse proxy works without
  further settings. Known consequence: any device in these networks can fake
  another address with a forged `X-Forwarded-For` header; the sign-in throttle
  per address then does not apply, the lock per user name still does. To
  avoid this, enter only the reverse proxy's IP.
- Update to the latest version regularly.
