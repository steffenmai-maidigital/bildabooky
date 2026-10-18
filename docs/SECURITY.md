# BilDaBooky – Sicherheitsarchitektur

## Grundsatz

Alle Daten bleiben auf dem Schulserver. Keine Cloud, kein Tracking, keine
fremden Dienste. Medien liegen außerhalb des Webverzeichnisses und werden nur
an angemeldete, berechtigte Personen ausgeliefert.

## Anmeldung

| Maßnahme | Umsetzung |
|---|---|
| Passwörter | nur als bcrypt-Hash; Kinder bekommen leichte Startpasswörter, Erwachsene müssen ihr Startpasswort ändern |
| Bremse | je Name und Gerät (6 Versuche → 1 Minute) und je Name (10 Fehlversuche → 30 Minuten, unabhängig von der IP) |
| Schulverzeichnis | nur LDAP-Bind, kein Passwort in BilDaBooky; Servicekonto nur lesend, Passwort mit `APP_KEY` verschlüsselt; LDAPS empfohlen; Eingaben mit `ldap_escape` |
| Zweiter Faktor | TOTP (RFC 6238) mit Wiederherstellungscodes; Richtlinie je Gruppe (Pflicht, wählbar, aus), Verzeichnisgruppen mit Pflicht, Ausnahme je Person; Codes gegen Wiederverwendung gesperrt, 5 Fehlversuche → Pause |
| Kinder | Klassen-Sitzung mit 6-stelligem Code statt zweitem Faktor; Sitzungspflicht abschaltbar |
| Erster Admin | zufälliges Startpasswort im Protokoll, nie `admin/admin`; Notzugang per Artisan |

## Rechte

Jede Anfrage prüft serverseitig Rolle **und** Klasse: Kinder sehen nur eigene
und geteilte Bücher der Klasse, Lehrkräfte nur ihre Klassen, Admins nur
Verwaltung (keine fremden Bücher). Ausgeblendete Knöpfe sind kein Schutz.

## Ausgabe und Eingabe

- Buchinhalte gelangen nie als HTML in die Seite; Blade-Escaping überall.
- Content-Security-Policy nur eigener Server, `frame-ancestors`, `nosniff`,
  Referrer- und Permissions-Policy; hinter HTTPS sichere Cookies und HSTS.
- CSRF-Schutz aller Formulare; Sitzung wird bei Anmeldung erneuert.
- Uploads: nur Bilder und Töne (Typ geprüft), 20 MB, 60 je Minute; SVG-Logos
  in einer Sandbox.

## Protokolle und Sicherung

- Änderungsprotokoll je Buch; Anwendungsprotokoll 30 Tage (Hilfe → Letzte
  Fehler, leerbar).
- Sicherung enthält `.app_key` — der Ordner `backups` ist nur für Admins.
- Sommerferienmodus löscht Schülerkonten endgültig, mit Sicherung vorher und
  Hinweis drei Wochen zuvor.

## Sicherheitstest

Vor jeder Version prüfen die Betreuer automatisch gegen eine Testinstanz: Rechte, CSRF,
XSS, SQL-Injection, Passwort-Raten, Header, Standardpasswörter. Lücken bitte
vertraulich melden (siehe `SECURITY.md`).
