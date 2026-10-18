# Anleitung: Installation auf Synology (Container Manager) und QNAP (Container Station)

*English version: [synology.md](en/synology.md)*

> **Namen:** Bei **Synology** heißt die Docker-Oberfläche **Container
> Manager** (DSM 7.2 und neuer; auf älteren DSM-Versionen das Paket
> „Docker“). **Container Station** ist das Gegenstück bei **QNAP**. Beide
> Wege stehen in dieser Anleitung; QNAP folgt weiter unten.

## Voraussetzungen

- Synology NAS mit **DSM 7.2** oder neuer
- Paket **Container Manager** aus dem Paket-Zentrum
- mindestens 2 GB Arbeitsspeicher im NAS (ab 4 GB läuft es entspannt)
- Intel/AMD-Modelle (z. B. Plus-Serie) und ARM-Modelle werden unterstützt;
  auf ARM dauert der erste Bau deutlich länger
- Internetzugang des NAS **beim Bauen** (Laravel wird geladen); im Betrieb
  braucht BilDaBooky kein Internet

## 1. Dateien auf das NAS bringen

1. Auf GitHub **Code → Download ZIP** wählen (bzw. das bereitgestellte ZIP
   verwenden).
2. In der **File Station** im freigegebenen Ordner `docker` einen Ordner
   `bildabooky` anlegen.
3. Das ZIP dorthin hochladen, Rechtsklick → **Extrahieren → Hier
   extrahieren**. Danach müssen `Dockerfile`, `docker-compose.yml`,
   `app/` usw. **direkt** in `docker/bildabooky/` liegen (nicht in einem
   Unterordner wie `bildabooky-main/`; dann den Inhalt eine Ebene
   hochschieben).
4. Im Ordner `docker/bildabooky` einen leeren Ordner **`backups`** anlegen.
   Dort landen die nächtlichen Sicherungen.

## 2. Einstellungen anpassen

`docker-compose.yml` mit dem **Text-Editor** des DSM öffnen (Paket „Text
Editor“) oder später im Container Manager unter Projekt → **YAML
konfigurieren** bearbeiten. Mindestens anpassen:

- `SCHOOL_NAME: "Grundschule am Park"`
- `APP_URL: "https://buecher.meine-schule.de"` (bzw. vorerst
  `http://<NAS-IP>:7290`)
- Port `"7290:80"`: links einen freien Port wählen, falls 7290 belegt ist

Alle Werte erklärt die [README](../README.md). Schulname, Logo, Farben und
Domain lassen sich später auch bequem im Admin-Bereich ändern.

## 3. Projekt anlegen

1. **Container Manager** öffnen → **Projekt** → **Erstellen**.
2. **Projektname:** `bildabooky`
3. **Pfad:** den Ordner `docker/bildabooky` wählen.
4. **Quelle:** „Vorhandene docker-compose.yml verwenden“ (wird erkannt).
5. Den Schritt **Webportal** überspringen (das Webportal richten wir in
   Schritt 5 als Reverse Proxy mit HTTPS ein).
6. **Fertig**. Der erste Start baut das Abbild und dauert je nach NAS
   5–20 Minuten. Fortschritt: Projekt `bildabooky` → **Protokoll**.

Fertig ist es, wenn beide Container **`bildabooky`** und
**`bildabooky-scheduler`** grün laufen. Dann im Browser öffnen:
`http://<NAS-IP>:7290`.

## 4. Erste Schritte

1. Das erste Admin-Passwort steht im Protokoll: Container Manager →
   Projekt `bildabooky` → **Protokoll** → Zeile „Erstes Admin-Passwort für
   „admin““. Damit als `admin` anmelden und ein eigenes Passwort vergeben.
   (Wer lieber selbst eins festlegt, trägt es vor dem ersten Start als
   `ADMIN_PASSWORD` in die `docker-compose.yml` ein.)
2. Unter **Konto** die Zwei-Faktor-Anmeldung einrichten (empfohlen).
3. Unter **Einstellungen** Schulname, Logo, Farben eintragen.
4. Unter **Klassen** die Klassen anlegen, unter **Benutzer** die Lehrkräfte
   (Rolle *Leiter*, Klassen zuordnen). Die Lehrkräfte legen ihre
   Schüler selbst an und drucken die Anmeldekarten.

Zum Ausprobieren vorher `DEMO_MODE: "true"` setzen: Dann gibt es eine
Lehrerin (`sommer` / `sommer123`), einen Lehrer (`winter` / `winter123`), zwei Kinder (`mia` / `mia`,
`ben` / `ben`) und Beispielbücher.

## 5. HTTPS über den DSM-Reverse-Proxy (für Kamera und Mikrofon)

Browser erlauben Kamera und Mikrofon nur über **HTTPS**. Das DSM bringt alles
dafür mit:

1. **Zertifikat:** Systemsteuerung → **Sicherheit → Zertifikat** →
   **Hinzufügen** → „Zertifikat von Let's Encrypt abrufen“ für
   `buecher.meine-schule.de`. (Der Name muss per DNS auf das NAS zeigen und
   Port 80 erreichbar sein.)
2. **Reverse Proxy:** Systemsteuerung → **Anmeldeportal → Erweitert →
   Reverse Proxy** → **Erstellen**:
   - Quelle: Protokoll **HTTPS**, Hostname `buecher.meine-schule.de`,
     Port **443**
   - Ziel: Protokoll **HTTP**, Hostname `localhost`, Port **7290**
   - Reiter **Benutzerdefinierte Kopfzeile** → **Erstellen → WebSocket**
     (schadet nicht) und zusätzlich `X-Forwarded-Proto` = `https`
3. Unter Systemsteuerung → Sicherheit → Zertifikat → **Einstellungen** dem
   neuen Eintrag `buecher.meine-schule.de` das Let's-Encrypt-Zertifikat
   zuordnen.
4. BilDaBooky jetzt über `https://buecher.meine-schule.de` öffnen. Klappt
   das, die Adresse **dort** unter **Einstellungen → Domain** eintragen
   (oder in der `docker-compose.yml` `APP_URL` darauf setzen und das
   Projekt neu erstellen). Erst eintragen, wenn die Adresse funktioniert —
   sonst sperrt man sich aus; siehe
   [README unter „Domain eintragen“](../README.md#domain-eintragen).

Nur im Schulnetz und ohne öffentliche Domain? Dann geht auch ein
Zertifikat für einen internen Namen aus der eigenen Zertifizierungsstelle;
die Geräte müssen ihr dann vertrauen.

Ausführlich – auch für Nginx Proxy Manager und nginx: [reverse-proxy.md](reverse-proxy.md).

## 6. Port-Freigaben und Firewall

- Von außen erreichbar sein muss nur **443** (HTTPS) und für Let's Encrypt
  **80**. Den Port **7290** nicht im Router freigeben.
- In der DSM-Firewall den Port 7290 höchstens für das lokale Netz öffnen.

## Daten, Updates, Sicherung

- Datenbank, Fotos, Tonaufnahmen und der Schlüssel `.app_key` liegen im
  Docker-Volume **`bildabooky_app-data`**, Sitzungen und Protokolle in
  **`bildabooky_app-storage`**. Beides bleibt bei Neustart und Update
  erhalten.
- Sicherungen liegen im Ordner `docker/bildabooky/backups` und lassen sich
  mit **Hyper Backup** zusätzlich auf eine externe Platte sichern.
- **Update:** Projekt **stoppen** → die neuen Dateien über die alten in
  `docker/bildabooky` entpacken (**eure angepasste `docker-compose.yml`
  vorher sichern und danach zurückkopieren!**) → Projekt → **Aktion →
  Bereinigen und neu erstellen** bzw. **Erstellen**. Neue
  Datenbankänderungen spielt BilDaBooky beim Start automatisch ein.

Ausführlich: [betrieb.md](betrieb.md).

## Alternative: per SSH

SSH aktivieren (Systemsteuerung → Terminal & SNMP), dann:

```bash
ssh admin@<NAS-IP>
cd /volume1/docker/bildabooky
sudo docker compose up -d --build
sudo docker compose logs -f app
```

Auf DSM 7.1 mit dem alten Paket „Docker“ gibt es keine Projekte. Dort ist
SSH der einfachste Weg (`sudo docker-compose up -d --build`, mit
Bindestrich).

---

## QNAP Container Station

1. In der **File Station** das ZIP nach z. B. `/Container/bildabooky`
   entpacken (Dateien wieder **direkt** im Ordner) und darin einen Ordner
   `backups` anlegen.
2. `docker-compose.yml` wie oben anpassen.
3. **Container Station** öffnen → **Anwendungen** → **Erstellen** →
   Anwendungsname `bildabooky` → den Inhalt der `docker-compose.yml`
   einfügen.
   - Weil BilDaBooky beim ersten Start gebaut wird, braucht Container
     Station den Ordner als Bau-Kontext. Klappt das in der Oberfläche
     nicht, ist SSH der verlässliche Weg:

     ```bash
     ssh admin@<NAS-IP>
     cd /share/Container/bildabooky
     docker compose up -d --build
     ```
4. Danach `http://<NAS-IP>:7290` öffnen und wie in Schritt 4 oben
   weitermachen.
5. **HTTPS:** In QTS/QuTS hero unter **Systemsteuerung → Netzwerk- &
   Dateidienste → Reverse Proxy** (bzw. **myQNAPcloud → Reverse Proxy**)
   eine Regel von `https://buecher.meine-schule.de:443` auf
   `http://localhost:7290` anlegen und ein Let's-Encrypt-Zertifikat
   zuweisen.

## Häufige Probleme

| Problem | Lösung |
|---|---|
| Projekt startet nicht, Bau bricht ab | Protokoll ansehen. Meist fehlte kurz das Internet beim Laden der Pakete: einfach erneut **Erstellen**. |
| „Server Error“ im Browser | `APP_DEBUG: "true"` setzen, Projekt neu erstellen, Fehlermeldung lesen, danach wieder auf `"false"` stellen. Als Admin zeigt auch **Hilfe → Letzte Fehler** die Ursache. |
| Kamera/Mikrofon gehen nicht | Seite wird nicht über HTTPS aufgerufen → Schritt 5. |
| „Zu viele Weiterleitungen“ hinter mehreren Proxys | Zwei Stellen erzwingen HTTPS und leiten hin und her. HTTPS nur an einer Stelle erzwingen, z. B. in Nginx Proxy Manager „Force SSL“ ausschalten. |
| Seite ohne Gestaltung hinter einem Proxy, der intern „http“ meldet | In `docker-compose.yml` `APP_URL: "https://eure-adresse.de"` setzen (mit https) oder unter Einstellungen → Domain eintragen — dann erzeugt BilDaBooky nur noch https-Links. |
| Tonaufnahme oder Foto lässt sich nicht hochladen | BilDaBooky nimmt bis 20 MB je Datei an; Fotos werden schon im Browser verkleinert. Steht ein eigener Proxy davor, dort Uploads bis 30 MB erlauben (nginx: `client_max_body_size 30m;`). |
| Nach Update alte Ansicht | Browser-Cache leeren bzw. Seite mit Umschalt+Neu laden. |
| Ausgesperrt nach falscher Domain (Seiten ohne Gestaltung, Anmelden führt ins Leere) | Domain wieder löschen: `sudo docker exec bildabooky php artisan bildabooky:clear-domain` — oder ohne SSH: Container Manager → Container `bildabooky` → **Terminal** → `php artisan bildabooky:clear-domain` |
| Admin-Passwort vergessen | Zweiten Admin bitten, es unter **Benutzer** zurückzusetzen. Gibt es keinen: siehe [betrieb.md](betrieb.md#notfall-admin-passwort). |
