# Anleitung: Installation mit Docker

*English version: [docker.md](en/docker.md)*

Diese Anleitung gilt für jeden Rechner mit Docker: einen Linux-Server im
Schulnetz, einen Mini-PC oder zum Ausprobieren Docker Desktop auf Mac und
Windows. Für ein Synology- oder QNAP-NAS gibt es eine eigene Anleitung:
[synology.md](synology.md).

## Voraussetzungen

- Docker 24 oder neuer mit Docker Compose (`docker compose version`)
- 1 GB freier Arbeitsspeicher, 2 GB Speicherplatz plus Platz für Fotos und
  Tonaufnahmen
- Internetzugang **nur beim Bauen** (Composer lädt Laravel). Im Betrieb
  braucht BilDaBooky kein Internet.
- Für Kamera und Mikrofon: eine Adresse mit **HTTPS** (siehe unten)

## 1. Dateien holen

```bash
git clone https://github.com/steffenmai-maidigital/bildabooky.git
cd bildabooky
```

Ohne Git: auf GitHub **Code → Download ZIP**, entpacken und in den Ordner
wechseln.

## 2. Einstellungen anpassen

Alle Einstellungen stehen in der `docker-compose.yml`. Eine `.env`-Datei ist
nicht nötig. Die wichtigsten:

| Einstellung | Bedeutung |
|---|---|
| `SCHOOL_NAME` | Name der Schule (lässt sich später auch im Admin-Bereich ändern) |
| `APP_URL` | Adresse, unter der BilDaBooky erreichbar ist, z. B. `https://buecher.meine-schule.de` |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | erster Zugang (nur beim allerersten Start). Leer = zufälliges Passwort im Protokoll; es muss sofort geändert werden |
| `DEMO_MODE` | `"true"` legt Beispiel-Klasse, Lehrerin, Kinder und Bücher an |
| `REQUIRE_2FA_ROLES` | z. B. `"admin"`: Zwei-Faktor-Anmeldung für Admins ist Pflicht |
| `SOURCE_URL` | Link zum Quellcode (AGPL), erscheint in der Fußzeile |
| Port `"7290:80"` | links den gewünschten Port eintragen, z. B. `"8080:80"` |

Die vollständige Liste steht in der [README](../README.md).

## 3. Starten

```bash
mkdir -p backups
docker compose up -d --build
```

Der erste Start baut das Abbild und dauert einige Minuten. Den Fortschritt
siehst du so:

```bash
docker compose logs -f app
```

Fertig ist es, wenn `Admin angelegt: admin`, darunter das **erste
Admin-Passwort**, und
`resuming normal operations` erscheinen. Dann im Browser öffnen:
<http://localhost:7290> (bzw. `http://<Server-IP>:7290`).

## 4. Erste Schritte

1. Mit `admin` und dem Passwort aus dem Protokoll anmelden
   (`docker compose logs app | grep -A1 "Erstes Admin"`) und ein eigenes
   Passwort vergeben.
2. Unter **Konto** die Zwei-Faktor-Anmeldung einrichten (empfohlen).
3. Unter **Einstellungen** Schulname, Logo und Farben eintragen. Die
   **Domain** erst eintragen, wenn HTTPS eingerichtet ist und du BilDaBooky
   schon über diese Adresse öffnest (Schritt 5).
4. Unter **Klassen** die Klassen anlegen.
5. Unter **Benutzer** Lehrkräfte anlegen (Rolle *Leiter*, Klassen
   zuordnen). Die Lehrkräfte legen danach ihre Schüler
   selbst an und drucken die Anmeldekarten.

## 5. HTTPS einrichten (für Kamera und Mikrofon)

Browser erlauben Kamera und Mikrofon nur über HTTPS oder auf `localhost`.
Für den Schulbetrieb gehört deshalb ein Reverse Proxy mit Zertifikat davor.
Am einfachsten geht das mit [Caddy](https://caddyserver.com), das sich das
Zertifikat selbst besorgt.

Neben der `docker-compose.yml` eine Datei `Caddyfile` anlegen:

```
buecher.meine-schule.de {
    reverse_proxy app:80
}
```

und in der `docker-compose.yml` unter `services:` ergänzen:

```yaml
  caddy:
    image: caddy:2
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile:ro
      - caddy-data:/data
    restart: unless-stopped
```

sowie unter `volumes:` den Eintrag `caddy-data:`. Danach `APP_URL` auf die
HTTPS-Adresse setzen und `docker compose up -d` ausführen. Voraussetzung: Der
Name zeigt per DNS auf den Server, und die Ports 80/443 sind erreichbar.

Funktioniert die neue Adresse, BilDaBooky darüber öffnen und erst dann unter
**Einstellungen → Domain** eintragen. Warum diese Reihenfolge wichtig ist und
wie man sich bei einem Fehler wieder hineinholt, steht in der
[README unter „Domain eintragen“](../README.md#domain-eintragen).

Wer schon einen Reverse Proxy hat (nginx, Traefik, Apache), leitet die Domain
einfach auf `http://<Server>:7290` weiter. Wichtig: Läuft der Proxy
nicht im selben Docker- oder Schulnetz, seine Adresse in `TRUSTED_PROXIES`
eintragen (Standard: nur interne Netze). Außerdem Uploads bis 30 MB erlauben
(`client_max_body_size 30m;` bei nginx).

Ausführlich – auch für Nginx Proxy Manager und nginx: [reverse-proxy.md](reverse-proxy.md).

## 6. Betrieb

- Updates, Sicherung und Wiederherstellung:
  [betrieb.md](betrieb.md)
- Der zweite Container `bildabooky-scheduler` erledigt die nächtliche
  Sicherung (2:30 Uhr) und den Sommerferienmodus (3:00 Uhr). Er muss
  mitlaufen.

## Ausprobieren ohne Compose

Für einen schnellen Blick genügt ein einzelner Container (Daten gehen beim
Löschen des Containers verloren):

```bash
docker build -t bildabooky .
docker run --rm -p 7290:80 -e DEMO_MODE=true bildabooky
```

Demo-Zugänge: Lehrkräfte `sommer` / `sommer123` und `winter` / `winter123`, Schüler `mia` / `mia` und
`ben` / `ben`. Das Admin-Passwort steht im Protokoll (`docker logs <container>`),
oder ihr gebt es vor: `-e ADMIN_PASSWORD=…`.
