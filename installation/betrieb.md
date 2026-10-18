# Anleitung: Betrieb — Updates, Sicherung, Wiederherstellung, Notfälle

*English version: [operations.md](en/operations.md)*

Gilt für Docker allgemein und für Synology/QNAP. Befehle per SSH auf dem
NAS mit `sudo` davor ausführen. Die Beispiele gehen davon aus, dass das
Compose-Projekt `bildabooky` heißt (Ordnername) und im Ordner
`bildabooky` liegt.

## Was liegt wo?

| Ort | Inhalt |
|---|---|
| Volume `bildabooky_app-data` (`/data`) | Datenbank `bildabooky.sqlite`, Fotos/Zeichnungen/Ton unter `medien/`, Schullogo unter `marke/`, Schlüssel `.app_key` |
| Volume `bildabooky_app-storage` | Sitzungen, Zwischenspeicher, Protokolle (30 Tage) |
| Ordner `./backups` | nächtliche Sicherungen als ZIP |

**`.app_key` ist wichtig:** Damit sind die Zwei-Faktor-Geheimnisse und die
hinterlegten Anmeldekarten verschlüsselt. Er ist in jeder Sicherung
enthalten. Deshalb gehört der Ordner `backups` vor fremden Blicken
geschützt (Freigaberechte nur für Admins).

## Updates

Es gibt bewusst keine `composer.lock`: Jeder Neubau holt die aktuellen
Fassungen innerhalb der in `composer.json` erlaubten Spannen.

**Docker:**

```bash
cd bildabooky
git pull                     # bzw. neues ZIP entpacken, eigene docker-compose.yml behalten
docker compose up -d --build
```

**Synology Container Manager:** Projekt stoppen → neue Dateien über die
alten entpacken (eure `docker-compose.yml` vorher sichern und danach
zurückkopieren) → Projekt → **Aktion → Bereinigen und neu erstellen**.

### Was beim Start passiert

Der App-Container führt bei jedem Start `php artisan app:upgrade` aus:

| Schritt | Wirkung |
|---|---|
| Versionsabgleich | Die Version in der Datenbank wird mit der Version des Abbilds verglichen. Ist die Datenbank neuer (**Downgrade**), startet der Container nicht – es wird nichts verändert. |
| Sicherung | Vor jeder Änderung landet eine Kopie der Datenbank in `/data/backups/` (die 10 neuesten bleiben). |
| Migrationen | Neue Tabellen und Spalten werden angelegt. |
| Vermerk | Unter **Hilfe → System** stehen danach die neue Version und Build-Nummer. |

### Versionen

Versionsnummern folgen dem Schema **JJMMTTNN** (Datum plus laufende Nummer
des Tages, z. B. `26101101`). Jeder Bau heißt `bildabooky:latest`; veröffentlichte
Versionen tragen zusätzlich ihre Nummer.
Welche Abbilder vorhanden sind:

```bash
docker image ls bildabooky
```

### Zurück zu einer älteren Version

Weil BilDaBooky kein Downgrade zulässt, braucht der Rückweg auch die
Datenbank von vor dem Update. Medien und Schlüssel bleiben unverändert.

1. Container stoppen: `docker compose down`
2. Die Datenbank-Sicherung von vor dem Update suchen und zurückspielen:

   ```bash
   docker run --rm -v bildabooky_app-data:/data alpine ls -1 /data/backups
   docker run --rm -v bildabooky_app-data:/data alpine sh -c \
     'cp /data/backups/DATEI.sqlite /data/bildabooky.sqlite && rm -f /data/bildabooky.sqlite-wal /data/bildabooky.sqlite-shm'
   ```

3. In der `docker-compose.yml` bei **beiden** Diensten die Zeile
   `build: .` auskommentieren (`# build: .`) und bei `image:` die alte
   Nummer eintragen, z. B. `image: bildabooky:26101101`. Ohne das
   Auskommentieren würde der nächste Start wieder die neue Fassung bauen.
4. `docker compose up -d` bzw. im Container Manager das Projekt neu
   erstellen.

Deshalb alte Abbilder nicht vorschnell aufräumen
(`docker image prune -a` löscht sie).

## Sicherung

- **Automatisch:** jede Nacht um 2:30 Uhr durch den Container
  `bildabooky-scheduler` nach `./backups`
  (`bildabooky_JJJJ-MM-TT_hhmmss.zip`). Aufbewahrt werden die Sicherungen
  `BACKUP_KEEP_DAYS` Tage (Standard 14).
- **Sofort, im Browser:** als Admin unter **Klassen → Sicherung
  herunterladen**.
- **Sofort, per Befehl:**

  ```bash
  docker exec bildabooky php artisan bildabooky:backup
  ```

- **Zusätzlich auslagern:** Den Ordner `backups` regelmäßig auf ein
  zweites Gerät kopieren, auf der Synology z. B. mit **Hyper Backup**.

Eine Sicherung enthält: `bildabooky.sqlite` (konsistente Kopie, auch
während des Betriebs), die Ordner `medien/` (Fotos, Zeichnungen, Ton) und
`marke/` (Schullogo) sowie `.app_key`.

## Wiederherstellung

Getestet mit dem Compose-Projekt `bildabooky`. `DATEI.zip` durch den Namen
der Sicherung ersetzen.

```bash
cd bildabooky
docker compose stop

docker run --rm -v bildabooky_app-data:/data -v "$PWD/backups:/backups:ro" alpine sh -c "
  set -e; mkdir /tmp/r && cd /tmp/r && unzip -q /backups/DATEI.zip
  rm -f /data/bildabooky.sqlite /data/bildabooky.sqlite-wal /data/bildabooky.sqlite-shm
  cp bildabooky.sqlite /data/ && rm -rf /data/medien && cp -r medien /data/medien
  cp .app_key /data/.app_key
  if [ -d marke ]; then rm -rf /data/marke && cp -r marke /data/marke; fi
  echo wiederhergestellt"

docker compose start
```

Die Dateirechte setzt BilDaBooky beim Start selbst.

**Umzug auf einen anderen Server:** Dort BilDaBooky wie in der
Installationsanleitung einrichten und einmal starten, dann die
Wiederherstellung wie oben ausführen.

## Notfälle

### Notfall: Admin-Passwort

Ein anderer Admin kann das Passwort unter **Benutzer → Bearbeiten**
zurücksetzen. Gibt es keinen:

```bash
docker exec bildabooky php artisan bildabooky:admin-password admin
# Handy mit der Authenticator-App verloren? Zwei-Faktor gleich mit zurücksetzen:
docker exec bildabooky php artisan bildabooky:admin-password admin --disable-2fa
```

Der Befehl zeigt ein neues Startpasswort an, das bei der Anmeldung
geändert werden muss.

### Falsche Domain eingetragen

```bash
docker exec bildabooky php artisan bildabooky:clear-domain
```

### Fehlersuche

- Als Admin: **Hilfe → Letzte Fehler** zeigt die Fehler der letzten
  30 Tage (mit Knopf zum Leeren).
- Ausführlich: `docker compose logs app` bzw. im Container Manager
  **Projekt → Protokoll**.
- Für eine genaue Fehlermeldung im Browser kurz `APP_DEBUG: "true"`
  setzen, neu erstellen und danach **unbedingt** wieder auf `"false"`.

### Alles zurücksetzen

`FRESH_DB: "1"` in der `docker-compose.yml` löscht beim nächsten Start
**Datenbank und alle Medien**. Danach sofort wieder auf `"0"` stellen,
sonst passiert es bei jedem Start erneut.

## Wiederkehrende Aufgaben

| Wann | Was |
|---|---|
| Schuljahresbeginn | Klassen anlegen bzw. umbenennen, Lehrkräfte zuordnen, Kinder anlegen und Anmeldekarten drucken |
| Schuljahresende | Sommerferienmodus prüfen (**Einstellungen → 6**): Datum, Optionen, Vorschau. Drei Wochen vorher erinnert BilDaBooky die Kinder, ihre Bücher mitzunehmen. |
| monatlich | Blick in **Hilfe → Letzte Fehler** und in den Ordner `backups` |
| bei Updates | vorher Sicherung herunterladen |

### Nützliche Befehle

| Befehl | Zweck |
|---|---|
| `php artisan bildabooky:backup` | Sicherung sofort anlegen |
| `php artisan bildabooky:summer-holiday --preview` | zeigt, was der Sommerferienmodus löschen würde |
| `php artisan bildabooky:summer-holiday --now` | Sommerferienmodus sofort ausführen (mit Rückfrage) |
| `php artisan bildabooky:admin-password admin` | Notfall-Passwort für einen Admin |
| `php artisan bildabooky:clear-domain` | eingetragene Domain entfernen |

Jeweils mit `docker exec bildabooky` davor (bzw. `docker exec -it` für die
Rückfrage bei `--now`).
