# BilDaBooky – Sicherheitsarchitektur

## Grundsatz

Alle Daten bleiben auf dem Schulserver. Keine Cloud, kein Tracking, keine
fremden Dienste. Medien liegen außerhalb des Webverzeichnisses und werden nur
an angemeldete, berechtigte Personen ausgeliefert.

## Anmeldung

| Maßnahme | Umsetzung |
|---|---|
| Passwörter | nur als bcrypt-Hash; Kinder bekommen leichte Startpasswörter, Erwachsene müssen ihr Startpasswort ändern |
| Bremse | je Name und Gerät (6 Versuche → 1 Minute) und je Name (10 Fehlversuche → Kinder 5 Minuten, sonst 30 Minuten, unabhängig von der IP; wer die Person verwaltet, hebt die Sperre unter **Benutzer** auf). Kinder, deren Klasse gerade keine Sitzung hat, bekommen sofort diese Meldung, ohne Prüfung des Passworts und ohne Fehlversuch |
| Schulverzeichnis | nur LDAP-Bind, kein Passwort in BilDaBooky; Servicekonto nur lesend, Passwort mit `APP_KEY` verschlüsselt; LDAPS empfohlen; Eingaben mit `ldap_escape`; hinein kommen nur Mitglieder der Gruppe der Lehrkräfte (Pflicht) oder der Admins |
| Zweiter Faktor | TOTP (RFC 6238) mit Wiederherstellungscodes; Richtlinie je Gruppe (Pflicht, wählbar, aus), Verzeichnisgruppen mit Pflicht, Ausnahme je Person; Codes gegen Wiederverwendung gesperrt, 5 Fehlversuche → Pause |
| Kinder | Klassen-Sitzung mit 6-stelligem Code statt zweitem Faktor; Sitzungspflicht abschaltbar |
| Erster Admin | zufälliges Startpasswort im Protokoll, nie `admin/admin`; Notzugang per Artisan (setzt einen lokalen Admin zurück oder legt einen an, wenn es keinen gibt) |
| Konto aktiv | Admins sperren Konten von Lehrkräften und Admins (eigene Konten und Schulkonten), nie das eigene; das Konto wird je Anfrage aus der Datenbank gelesen, eine laufende Sitzung endet bei der nächsten Anfrage |
| Passwortwechsel | beendet alle anderen Sitzungen der Person (Laravel `AuthenticateSession` vergleicht je Anfrage den Passwort-Hash); beim eigenen Wechsel bleibt die aktuelle Sitzung, beim Zurücksetzen durch Lehrkraft oder Admin enden alle |
| Bestätigung | Zwei-Faktor ausschalten, neue Wiederherstellungscodes und Sicherung herunterladen nur mit Passwort bzw. App-Code; gemeinsame Bremse (5 Fehlversuche → 5 Minuten), ein App-Code gilt nur einmal |

## Rechte

Jede Anfrage prüft serverseitig Rolle **und** Klasse: Kinder sehen nur eigene
und geteilte Bücher der Klasse, Lehrkräfte nur ihre Klassen, Admins nur
Verwaltung (keine Bücher der Kinder, Klassenbücher oder Vorlagen fremder
Klassen). Eigene Bücher einer Lehrkraft sehen und ändern nur sie selbst und
die Admins, nicht die übrigen Lehrkräfte der Klasse; das gilt auch für Fotos,
Töne, Vorschau und Protokoll. Ausgeblendete Knöpfe sind kein Schutz.

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
- Die Sicherungen auf dem Server (nächtlich, vor dem Löschen einer Klasse, vor
  dem Sommerferienmodus) enthalten `.app_key` — der Ordner `backups` ist nur
  für Admins.
- Die Sicherung zum Herunterladen enthält `.app_key` nicht und entsteht nur
  per Formular (POST mit CSRF-Schutz) mit Passwort bzw. App-Code; ein Link
  oder eingebettetes Bild löst keine Sicherung aus.
- Fotos, Zeichnungen und Töne, die auf keiner Seite mehr stehen, werden nach
  30 Tagen gelöscht (Datei und Datensatz).
- Sommerferienmodus löscht Schülerkonten endgültig, mit Sicherung vorher und
  Hinweis drei Wochen zuvor.

## Sicherheitstest

Vor jeder Version prüfen die Betreuer automatisch gegen eine Testinstanz: Rechte, CSRF,
XSS, SQL-Injection, Passwort-Raten, Header, Standardpasswörter. Lücken bitte
vertraulich melden (siehe `SECURITY.md`).
